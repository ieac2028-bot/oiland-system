<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewBranchReviewNotification;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        return view('customer.reviews.index', [
            'reviews' => Review::where('user_id', $request->user()->id)
                ->with('branch')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(Request $request, Branch $branch): View
    {
        $this->authorize('create', Review::class);

        return view('customer.reviews.create', [
            'branch' => $branch,
            'existingReview' => Review::where('branch_id', $branch->id)
                ->where('user_id', $request->user()->id)
                ->first(),
        ]);
    }

    public function store(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('create', Review::class);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,'.Review::MAX_RATING],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        $existing = Review::where('branch_id', $branch->id)
            ->where('user_id', $request->user()->id)
            ->first();

        $review = $existing ?? new Review([
            'branch_id' => $branch->id,
            'user_id' => $request->user()->id,
        ]);

        $review->fill($validated);
        // New and edited reviews go back through moderation.
        $review->status = ReviewStatus::Pending->value;
        $review->save();

        $this->activityLogger->log(
            ActivityAction::REVIEW_CREATED,
            "{$request->user()->name} submitted a {$review->rating}-star review for {$branch->name}.",
            $review,
            ['rating' => $review->rating],
            $request->user(),
            $branch,
        );

        $this->notifyModerators($review);

        return redirect()
            ->route('branches.show', $branch)
            ->with('status', 'Thank you! Your review will appear once a moderator approves it.');
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,'.Review::MAX_RATING],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        $review->update([
            ...$validated,
            'status' => ReviewStatus::Pending->value,
            'moderated_by' => null,
            'moderated_at' => null,
        ]);

        return back()->with('status', 'Your review was resubmitted for moderation.');
    }

    public function destroy(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        return back()->with('status', 'Review removed.');
    }

    protected function notifyModerators(Review $review): void
    {
        User::where('role', 'super_admin')
            ->get()
            ->each(fn (User $admin) => $admin->notify(new NewBranchReviewNotification($review)));
    }
}
