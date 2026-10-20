<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Super Admin provisions staff accounts and assigns roles or branches.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id
            || $user->isSuperAdmin()
            || ($user->isStaff() && $user->managesBranchId($model->branch_id));
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function update(User $user, User $model): bool
    {
        return $user->isSuperAdmin() || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }

    /**
     * Changing a role or the attached branch is a head office action.
     */
    public function manageRoles(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->id !== $model->id && $model->employee()->doesntExist();
    }
}
