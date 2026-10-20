<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\User;

/**
 * Announcements are network communications, so only head office may write them.
 * Everyone else reads whatever is live and targeted at them.
 */
class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->isSuperAdmin()
            || $announcement->isLive()
            || $announcement->branches()->where('branches.id', $user->branch_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function restore(User $user, Announcement $announcement): bool
    {
        return $this->delete($user, $announcement);
    }

    public function forceDelete(User $user, Announcement $announcement): bool
    {
        return $this->delete($user, $announcement);
    }
}
