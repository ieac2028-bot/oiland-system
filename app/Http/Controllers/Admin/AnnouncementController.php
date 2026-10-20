<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnnouncementScope;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Branch;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Time bound announcements that hide themselves once expired and can be
 * targeted at one branch, a selection of branches, or the whole network.
 */
class AnnouncementController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Announcement::class);

        $announcements = Announcement::query()
            ->with(['branches', 'author'])
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->string('q')->toString().'%'))
            ->when($request->filled('scope'), fn ($query) => $query->where('scope', $request->string('scope')->toString()))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('status'), function ($query) use ($request): void {
                match ($request->string('status')->toString()) {
                    'live' => $query->live(),
                    'expired' => $query->expired(),
                    'scheduled' => $query->whereNotNull('publish_at')->where('publish_at', '>', now()),
                    'draft' => $query->where('is_published', false),
                    default => $query,
                };
            })
            ->latest('publish_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.announcements.index', [
            'announcements' => $announcements,
            'scopes' => AnnouncementScope::options(),
            'filters' => $request->only(['q', 'scope', 'status', 'type']),
            'counts' => [
                'live' => Announcement::live()->count(),
                'scheduled' => Announcement::whereNotNull('publish_at')->where('publish_at', '>', now())->count(),
                'expired' => Announcement::expired()->count(),
                'draft' => Announcement::where('is_published', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Announcement::class);

        return view('admin.announcements.create', [
            'announcement' => new Announcement([
                'is_published' => true,
                'scope' => AnnouncementScope::Global->value,
                'publish_at' => now(),
                'expires_at' => now()->addMonth(),
            ]),
            'branches' => Branch::orderBy('name')->get(),
            'scopes' => AnnouncementScope::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Announcement::class);

        $validated = $request->validate($this->rules());
        $branchIds = $this->resolveBranchIds($request, $validated['scope']);

        $announcement = Announcement::create([
            ...$validated,
            'slug' => Str::slug($validated['title']),
            'author_id' => $request->user()->id,
            'is_published' => $request->boolean('is_published'),
            'is_featured' => $request->boolean('is_featured'),
        ]);

        $announcement->branches()->sync($branchIds);

        $this->activityLogger->log(
            ActivityAction::ANNOUNCEMENT_CREATED,
            "Announcement '{$announcement->title}' was published to {$announcement->scope->label()}.",
            $announcement,
            ['scope' => $announcement->scope->value, 'branches' => count($branchIds)],
            $request->user(),
        );

        return redirect()
            ->route('admin.announcements.index')
            ->with('status', "Announcement '{$announcement->title}' created.");
    }

    public function edit(Announcement $announcement): View
    {
        Gate::authorize('update', $announcement);

        $announcement->load('branches');

        return view('admin.announcements.edit', [
            'announcement' => $announcement,
            'branches' => Branch::orderBy('name')->get(),
            'scopes' => AnnouncementScope::options(),
            'selectedBranchIds' => $announcement->branches->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        Gate::authorize('update', $announcement);

        $validated = $request->validate($this->rules());
        $branchIds = $this->resolveBranchIds($request, $validated['scope']);

        $announcement->update([
            ...$validated,
            'slug' => Str::slug($validated['title']),
            'is_published' => $request->boolean('is_published'),
            'is_featured' => $request->boolean('is_featured'),
        ]);

        $announcement->branches()->sync($branchIds);

        $this->activityLogger->log(
            ActivityAction::ANNOUNCEMENT_UPDATED,
            "Announcement '{$announcement->title}' was updated.",
            $announcement,
            ['scope' => $announcement->scope->value, 'branches' => count($branchIds)],
            $request->user(),
        );

        return redirect()
            ->route('admin.announcements.index')
            ->with('status', "Announcement '{$announcement->title}' updated.");
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        Gate::authorize('delete', $announcement);

        $title = $announcement->title;
        $announcement->delete();

        $this->activityLogger->log(
            ActivityAction::ANNOUNCEMENT_DELETED,
            "Announcement '{$title}' was deleted.",
            null,
            ['title' => $title],
            $request->user(),
        );

        return redirect()->route('admin.announcements.index')->with('status', "Announcement '{$title}' deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:10'],
            'type' => ['required', 'in:notice,event,job'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'scope' => ['required', 'in:'.implode(',', AnnouncementScope::values())],
            'publish_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:publish_at'],
            'branches' => ['nullable', 'array'],
            'branches.*' => ['integer', 'exists:branches,id'],
        ];
    }

    /**
     * A global announcement has no branch rows; a targeted one requires at
     * least one branch.
     *
     * @return array<int, int>
     */
    protected function resolveBranchIds(Request $request, string $scope): array
    {
        if ($scope === AnnouncementScope::Global->value) {
            return [];
        }

        $validated = $request->validate([
            'branches' => ['required', 'array', 'min:1'],
            'branches.*' => ['integer', 'exists:branches,id'],
        ]);

        return array_map('intval', $validated['branches']);
    }
}
