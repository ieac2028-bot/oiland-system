<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Models\Branch;
use App\Services\ActivityLogger;
use App\Services\NominatimGeocoder;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class BranchController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly NominatimGeocoder $geocoder,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        Gate::authorize('viewAny', Branch::class);

        $branches = Branch::query()
            // Branch staff only ever see their own branch in the admin list.
            ->when($user->branch_id !== null, fn ($query) => $query->whereKey($user->branch_id))
            ->withCount(['staff', 'sales'])
            ->withAvg(['reviews as reviews_avg_rating' => fn ($query) => $query->published()], 'rating')
            ->when($request->filled('q'), fn ($query) => $query->search($request->string('q')->toString()))
            ->when($request->filled('region'), fn ($query) => $query->inRegion($request->string('region')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status')->toString() === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.branches.index', [
            'branches' => $branches,
            'regions' => Branch::orderBy('region')->distinct()->pluck('region'),
            'filters' => $request->only(['q', 'region', 'status']),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Branch::class);

        return view('admin.branches.create', [
            'branch' => new Branch(['is_active' => true, 'is_24_hours' => false]),
            'weekDays' => Branch::WEEK_DAYS,
        ]);
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        Gate::authorize('create', Branch::class);

        $validated = $request->validated();
        $branch = Branch::create($this->withDerivedCoordinates($validated, $request));

        $this->activityLogger->log(
            ActivityAction::BRANCH_CREATED,
            "Branch {$branch->name} ({$branch->code}) was added to the network.",
            $branch,
            ['code' => $branch->code, 'city' => $branch->city],
            $request->user(),
            $branch,
        );

        return redirect()
            ->route('admin.branches.show', $branch)
            ->with('status', "Branch {$branch->name} created.");
    }

    public function show(Branch $branch): View
    {
        Gate::authorize('view', $branch);

        $branch->load([
            'staff' => fn ($query) => $query->orderBy('name'),
            'announcements' => fn ($query) => $query->orderByDesc('publish_at')->take(5),
        ]);

        return view('admin.branches.show', [
            'branch' => $branch,
            'stats' => [
                'prices' => $branch->prices()->active()->count(),
                'catalog_items' => $branch->catalogItems()->wherePivot('is_available', true)->count(),
                'open_tickets' => $branch->tickets()->open()->count(),
                'average_rating' => round((float) $branch->reviews()->published()->avg('rating'), 2),
            ],
            'weekDays' => Branch::WEEK_DAYS,
        ]);
    }

    public function edit(Branch $branch): View
    {
        Gate::authorize('update', $branch);

        return view('admin.branches.edit', [
            'branch' => $branch,
            'weekDays' => Branch::WEEK_DAYS,
        ]);
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        Gate::authorize('update', $branch);

        $validated = $request->validated();
        $branch->update($this->withDerivedCoordinates($validated, $request));

        $this->activityLogger->log(
            ActivityAction::BRANCH_UPDATED,
            "Branch {$branch->name} was updated.",
            $branch,
            ['changes' => array_keys(array_diff_assoc($validated, $branch->getOriginal()))],
            $request->user(),
            $branch,
        );

        return redirect()
            ->route('admin.branches.show', $branch)
            ->with('status', "Branch {$branch->name} updated.");
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse
    {
        Gate::authorize('delete', $branch);

        if ($branch->sales()->exists()) {
            return back()->withErrors([
                'branch' => 'This branch has sales history and cannot be removed. Deactivate it instead.',
            ]);
        }

        $name = $branch->name;
        $branch->delete();

        $this->activityLogger->log(
            ActivityAction::BRANCH_DELETED,
            "Branch {$name} was removed from the network.",
            null,
            ['name' => $name, 'code' => $request->string('code')->toString()],
            $request->user(),
        );

        return redirect()->route('admin.branches.index')->with('status', "Branch {$name} deleted.");
    }

    /**
     * Toggle a branch between active and inactive without deleting history.
     */
    public function toggleActive(Request $request, Branch $branch): RedirectResponse
    {
        Gate::authorize('update', $branch);

        $branch->update(['is_active' => ! $branch->is_active]);

        $this->activityLogger->log(
            $branch->is_active ? ActivityAction::BRANCH_UPDATED : ActivityAction::BRANCH_UPDATED,
            "Branch {$branch->name} was ".($branch->is_active ? 'reactivated' : 'deactivated').'.',
            $branch,
            ['is_active' => $branch->is_active],
            $request->user(),
            $branch,
        );

        return back()->with('status', "Branch {$branch->name} is now ".($branch->is_active ? 'active' : 'inactive').'.');
    }

    /**
     * Look the address up through Nominatim when the operator did not type
     * coordinates by hand, and keep the slug in sync with the branch name.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function withDerivedCoordinates(array $validated, Request $request): array
    {
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_24_hours'] = $request->boolean('is_24_hours');
        $validated['photos'] = array_values(array_filter($validated['photos'] ?? []));
        $uploadDirectory = public_path('uploads/branches');
        File::ensureDirectoryExists($uploadDirectory);

        foreach ($request->file('photo_uploads', []) as $photo) {
            $filename = Str::uuid().'.'.$photo->extension();
            $photo->move($uploadDirectory, $filename);
            $validated['photos'][] = asset('uploads/branches/'.$filename);
        }

        $validated['photos'] = array_slice($validated['photos'], 0, 8);
        unset($validated['photo_uploads']);

        if (blank($validated['latitude'] ?? null) || blank($validated['longitude'] ?? null)) {
            $result = $this->geocoder->geocode(implode(', ', array_filter([
                $validated['address_line1'],
                $validated['city'],
                $validated['region'],
                $validated['country'] ?? null,
            ])));

            if ($result['found']) {
                $validated['latitude'] = $result['latitude'];
                $validated['longitude'] = $result['longitude'];
            }
        }

        $validated['slug'] = Str::slug($validated['name']);

        return $validated;
    }
}
