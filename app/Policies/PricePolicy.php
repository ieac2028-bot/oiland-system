<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Price;
use App\Models\User;

/**
 * Live prices are published by head office only. Branch staff may read the
 * prices of their own branch and may propose changes through a request.
 */
class PricePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Price $price): bool
    {
        return $user->isStaff() && $user->managesBranchId($price->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function update(User $user, Price $price): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function delete(User $user, Price $price): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function restore(User $user, Price $price): bool
    {
        return $this->delete($user, $price);
    }

    public function forceDelete(User $user, Price $price): bool
    {
        return $this->delete($user, $price);
    }
}
