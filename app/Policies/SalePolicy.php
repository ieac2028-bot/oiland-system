<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Sale;
use App\Models\User;

/**
 * Sales rows belong to a branch. Branch staff may only touch their own
 * branch's figures; head office may see and export the whole network.
 *
 * Recording figures is a branch-level job, so a user without a branch (head
 * office) is read-only: that keeps a single day's entry from wiping the
 * network when no branch has been chosen.
 */
class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->isStaff() && $user->managesBranchId($sale->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->isStaff() && $user->branch_id !== null;
    }

    public function update(User $user, Sale $sale): bool
    {
        return $user->managesBranchId($sale->branch_id);
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $user->managesBranchId($sale->branch_id);
    }

    public function restore(User $user, Sale $sale): bool
    {
        return $this->delete($user, $sale);
    }

    public function forceDelete(User $user, Sale $sale): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function import(User $user): bool
    {
        return $this->create($user);
    }

    public function export(User $user): bool
    {
        return $user->isStaff();
    }
}
