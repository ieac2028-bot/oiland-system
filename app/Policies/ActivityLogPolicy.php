<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;

/**
 * The audit trail is append-only and readable by head office. Branch staff may
 * inspect entries that concern their own branch so they can answer questions
 * about their operations.
 */
class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $user->role === UserRole::SuperAdmin
            || $user->managesBranchId($activityLog->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function delete(User $user, ActivityLog $activityLog): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function restore(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function forceDelete(User $user, ActivityLog $activityLog): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}
