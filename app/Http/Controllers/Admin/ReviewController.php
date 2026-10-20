<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Review;
use App\Services\ReviewModerationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Review moderation queue. Head office decides what is public; branch staff
 * can flag a review on their own branch for moderator attention.
 */
class ReviewController extends Controller
{
    public function __construct(private readonly ReviewModerationService $moderation) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Review::class);

        $user = $request->user();
        $status = $request->filled('status') ? ReviewStatus::tryFrom($request->string('status')->toString()) : null;

        $reviews = Review::query()
            ->when($user->branch_id !== null, fn ($query) => $query->where('branch_id', $user->branch_id))
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->when($request->boolean('flagged'), fn ($query) => $query->flagged())
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request): void {
                $like = '%'.$request->string('q')->toString().'%';
                $query->where('body', 'like', $like)->orWhere('title', 'like', $like);
            }))
            ->with(['branch', 'user', 'moderator'])
            ->orderByDesc('is_flagged')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'statuses' => ReviewStatus::options(),
            'filters' => $request->only(['status', 'flagged', 'branch_id', 'q']),
            'counts' => [
                'pending' => Review::when($user->branch_id !== null, fn ($q) => $q->where('branch_id', $user->branch_id))->where('status', ReviewStatus::Pending->value)->count(),
                'published' => Review::when($user->branch_id !== null, fn ($q) => $q->where('branch_id', $user->branch_id))->published()->count(),
                'flagged' => Review::when($user->branch_id !== null, fn ($q) => $q->where('branch_id', $user->branch_id))->flagged()->count(),
            ],
            'canModerate' => $user->isSuperAdmin(),
            'branches' => $user->isSuperAdmin()
                ? Branch::orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function show(Review $review): View
    {
        Gate::authorize('view', $review);

        $review->load(['branch', 'user', 'moderator', 'flagger']);

        return view('admin.reviews.show', [
            'review' => $review,
            'canModerate' => request()->user()->isSuperAdmin(),
        ]);
    }

    public function publish(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('moderate', $review);

        $this->moderation->publish($review, $request->user());

        return back()->with('status', 'Review published.');
    }

    public function hide(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('moderate', $review);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->moderation->hide($review, $request->user(), $validated['reason'] ?? null);

        return back()->with('status', 'Review hidden from the public branch page.');
    }

    public function flag(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('flag', $review);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->moderation->flag($review, $request->user(), $validated['reason']);

        return back()->with('status', 'Review flagged for moderation.');
    }

    public function destroy(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('delete', $review);

        $review->delete();

        return redirect()->route('admin.reviews.index')->with('status', 'Review deleted.');
    }
}
