<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CatalogItem;
use App\Models\User;

/**
 * The shared catalog is a head office responsibility. Branch staff can read it
 * and can toggle availability at the branches they manage.
 */
class CatalogItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, CatalogItem $catalogItem): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function update(User $user, CatalogItem $catalogItem): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function delete(User $user, CatalogItem $catalogItem): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function restore(User $user, CatalogItem $catalogItem): bool
    {
        return $this->delete($user, $catalogItem);
    }

    public function forceDelete(User $user, CatalogItem $catalogItem): bool
    {
        return $this->delete($user, $catalogItem);
    }
}
