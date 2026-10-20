<?php

namespace App\Policies;

use App\Enums\PriceChangeStatus;
use App\Enums\UserRole;
use App\Models\PriceChangeRequest;
use App\Models\User;

/**
 * The multi-tier approval workflow: branch staff may submit and withdraw their
 * own requests, head office is the only role that may approve or reject.
 */
class PriceChangeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, PriceChangeRequest $priceChangeRequest): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return $user->managesBranchId($priceChangeRequest->branch_id)
            || $priceChangeRequest->requested_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff() && ! $user->isSuperAdmin();
    }

    public function update(User $user, PriceChangeRequest $priceChangeRequest): bool
    {
        return $this->create($user) && $priceChangeRequest->isPending();
    }

    public function delete(User $user, PriceChangeRequest $priceChangeRequest): bool
    {
        return $this->update($user, $priceChangeRequest)
            || ($user->role === UserRole::SuperAdmin && $priceChangeRequest->isPending());
    }

    public function restore(User $user, PriceChangeRequest $priceChangeRequest): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function forceDelete(User $user, PriceChangeRequest $priceChangeRequest): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function approve(User $user, PriceChangeRequest $priceChangeRequest): bool
    {
        return $user->role === UserRole::SuperAdmin
            && $priceChangeRequest->status === PriceChangeStatus::Pending;
    }

    public function reject(User $user, PriceChangeRequest $priceChangeRequest): bool
    {
        return $this->approve($user, $priceChangeRequest);
    }
}
