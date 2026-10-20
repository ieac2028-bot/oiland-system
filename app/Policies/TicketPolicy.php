<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;

/**
 * Customers see and manage only their own inquiries; branch staff see the
 * inquiries routed to the branch they manage; head office sees everything.
 */
class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($ticket->customer_id === $user->id) {
            return true;
        }

        return $user->isStaff() && $user->managesBranchId($ticket->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->isStaff() || $user->isCustomer();
    }

    /**
     * Customers may edit their own inquiry while it is still open.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        return $ticket->customer_id === $user->id && ! $ticket->isResolved();
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->role === UserRole::SuperAdmin
            || ($ticket->customer_id === $user->id && ! $ticket->isResolved());
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && $user->managesBranchId($ticket->branch_id);
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && $user->managesBranchId($ticket->branch_id);
    }
}
