<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;
use App\Support\ActivityAction;

/**
 * Review moderation: publishes, hides and flags customer feedback so only
 * approved content reaches the public branch profile.
 */
class ReviewModerationService
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function publish(Review $review, User $moderator): Review
    {
        $review->update([
            'status' => ReviewStatus::Published->value,
            'is_flagged' => false,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);

        $this->activityLogger->log(
            ActivityAction::REVIEW_PUBLISHED,
            "{$moderator->name} published a {$review->rating}-star review by {$review->user->name} for {$review->branch->name}.",
            $review,
            ['rating' => $review->rating],
            $moderator,
            $review->branch,
        );

        return $review->refresh();
    }

    public function hide(Review $review, User $moderator, ?string $reason = null): Review
    {
        $review->update([
            'status' => ReviewStatus::Hidden->value,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);

        $this->activityLogger->log(
            ActivityAction::REVIEW_HIDDEN,
            "{$moderator->name} hid a review by {$review->user->name} for {$review->branch->name}.",
            $review,
            ['reason' => $reason],
            $moderator,
            $review->branch,
        );

        return $review->refresh();
    }

    /**
     * Flag a review for moderator attention. Flagged reviews are pulled out of
     * the public list immediately, even if they were already published.
     */
    public function flag(Review $review, User $flagger, ?string $reason = null): Review
    {
        $review->update([
            'is_flagged' => true,
            'flagged_at' => now(),
            'flagged_by' => $flagger->id,
        ]);

        $this->activityLogger->log(
            ActivityAction::REVIEW_FLAGGED,
            "{$flagger->name} flagged a review by {$review->user->name} for {$review->branch->name}.",
            $review,
            ['reason' => $reason],
            $flagger,
            $review->branch,
        );

        return $review->refresh();
    }
}
