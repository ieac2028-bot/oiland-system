<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Review;
use App\Models\User;

/**
 * Customers may review the branches they visited and edit their own review.
 * Only head office moderates, which is what keeps hidden content off the
 * public branch profile.
 */
class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Review $review): bool
    {
        return $review->user_id === $user->id
            || ($user->isStaff() && $user->managesBranchId($review->branch_id));
    }

    public function create(User $user): bool
    {
        return $user->isCustomer() || $user->isStaff();
    }

    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id && ! $review->is_flagged;
    }

    public function delete(User $user, Review $review): bool
    {
        return $this->update($user, $review) || $this->moderate($user, $review);
    }

    public function restore(User $user, Review $review): bool
    {
        return $this->moderate($user, $review);
    }

    public function forceDelete(User $user, Review $review): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function flag(User $user, Review $review): bool
    {
        return $user->isStaff() && $user->managesBranchId($review->branch_id);
    }

    public function moderate(User $user, Review $review): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}
