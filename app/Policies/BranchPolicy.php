<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;

/**
 * Branch records are created and edited by head office. Branch staff may read
 * (and update the operational details of) only their own branch.
 */
class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->isStaff() && $user->managesBranch($branch);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->managesBranch($branch);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function restore(User $user, Branch $branch): bool
    {
        return $this->delete($user, $branch);
    }

    public function forceDelete(User $user, Branch $branch): bool
    {
        return $this->delete($user, $branch);
    }

    /**
     * Managing the per-branch product and service availability list.
     */
    public function manageAvailability(User $user, Branch $branch): bool
    {
        return $user->managesBranch($branch);
    }

    /**
     * Entering sales figures and reading branch level reports.
     */
    public function manageSales(User $user, Branch $branch): bool
    {
        return $user->managesBranch($branch);
    }
}
