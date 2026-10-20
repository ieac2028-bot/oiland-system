<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketReplyReceivedNotification;
use App\Notifications\TicketStatusUpdatedNotification;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * The branch staff ticket desk. Every query is clamped to the branch scope of
 * the requesting user so one branch can never read another's inquiries.
 */
class TicketController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Ticket::class);

        $user = $request->user();
        $status = $request->filled('status') ? TicketStatus::tryFrom($request->string('status')->toString()) : null;
        $priority = $request->filled('priority') ? TicketPriority::tryFrom($request->string('priority')->toString()) : null;

        $tickets = Ticket::query()
            ->forBranchScope($user->branch_id)
            ->status($status)
            ->when($priority !== null, fn ($query) => $query->where('priority', $priority->value))
            ->when($request->filled('assigned_to'), fn ($query) => $query->where('assigned_to', $request->integer('assigned_to')))
            ->when($request->filled('q'), fn ($query) => $query->search($request->string('q')->toString()))
            ->with(['branch', 'customer', 'assignee'])
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [TicketStatus::Pending->value])
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'statuses' => TicketStatus::options(),
            'priorities' => TicketPriority::options(),
            'agents' => $this->agentsFor($user),
            'filters' => $request->only(['status', 'priority', 'assigned_to', 'q']),
            'counts' => [
                'pending' => Ticket::forBranchScope($user->branch_id)->where('status', TicketStatus::Pending->value)->count(),
                'in_progress' => Ticket::forBranchScope($user->branch_id)->where('status', TicketStatus::InProgress->value)->count(),
                'resolved' => Ticket::forBranchScope($user->branch_id)->where('status', TicketStatus::Resolved->value)->count(),
            ],
        ]);
    }

    public function show(Request $request, Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);

        $ticket->load([
            'branch',
            'customer',
            'assignee',
            'replies' => fn ($query) => $query->with('author')->oldest(),
        ]);

        return view('admin.tickets.show', [
            'ticket' => $ticket,
            'statuses' => TicketStatus::options(),
            'priorities' => TicketPriority::options(),
            'agents' => $this->agentsFor($request->user()),
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('reply', $ticket);

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'is_internal_note' => ['nullable', 'boolean'],
        ]);

        $isInternal = $request->boolean('is_internal_note');

        $reply = $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_internal_note' => $isInternal,
        ]);

        if (! $isInternal) {
            $this->activityLogger->log(
                ActivityAction::TICKET_REPLIED,
                "{$request->user()->name} replied to inquiry {$ticket->reference}.",
                $ticket,
                ['reference' => $ticket->reference],
                $request->user(),
                $ticket->branch,
            );

            $ticket->customer?->notify(new TicketReplyReceivedNotification(
                $ticket->load('branch'),
                $reply,
            ));
        }

        return back()->with('status', $isInternal ? 'Internal note added.' : 'Reply sent to the customer.');
    }

    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('changeStatus', $ticket);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,resolved'],
        ]);

        $newStatus = TicketStatus::from($validated['status']);

        if ($newStatus === $ticket->status) {
            return back()->with('status', 'Status unchanged.');
        }

        $previous = $ticket->status;

        $ticket->update([
            'status' => $newStatus->value,
            'resolved_at' => $newStatus === TicketStatus::Resolved ? now() : null,
            'resolved_by' => $newStatus === TicketStatus::Resolved ? $request->user()->id : null,
        ]);

        $this->activityLogger->log(
            $newStatus === TicketStatus::Resolved ? ActivityAction::TICKET_RESOLVED : ActivityAction::TICKET_STATUS_CHANGED,
            sprintf(
                '%s moved inquiry %s from %s to %s.',
                $request->user()->name,
                $ticket->reference,
                $previous->label(),
                $newStatus->label(),
            ),
            $ticket,
            ['from' => $previous->value, 'to' => $newStatus->value],
            $request->user(),
            $ticket->branch,
        );

        $latestPublicReply = $ticket->replies()
            ->where('is_internal_note', false)
            ->latest()
            ->value('message');

        $ticket->customer?->notify(new TicketStatusUpdatedNotification(
            $ticket->refresh()->load('branch'),
            $previous,
            $latestPublicReply !== null ? str($latestPublicReply)->limit(200)->toString() : null,
        ));

        return back()->with('status', "Inquiry marked as {$newStatus->label()}.");
    }

    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('assign', $ticket);

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $assigneeId = $validated['assigned_to'] !== null ? (int) $validated['assigned_to'] : null;

        if ($assigneeId !== null) {
            $assignee = User::findOrFail($assigneeId);

            // An agent can only be assigned within the branch that owns the ticket.
            abort_unless(
                $assignee->isSuperAdmin() || $assignee->branch_id === $ticket->branch_id,
                422,
                'That team member does not belong to this branch.',
            );
        }

        $ticket->update(['assigned_to' => $assigneeId]);

        $this->activityLogger->log(
            ActivityAction::TICKET_ASSIGNED,
            sprintf(
                '%s assigned inquiry %s to %s.',
                $request->user()->name,
                $ticket->reference,
                $assigneeId !== null ? (User::find($assigneeId)?->name ?? 'a team member') : 'nobody',
            ),
            $ticket,
            ['assigned_to' => $assigneeId],
            $request->user(),
            $ticket->branch,
        );

        return back()->with('status', 'Ticket assignment updated.');
    }

    public function updatePriority(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('changeStatus', $ticket);

        $validated = $request->validate([
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ]);

        $ticket->update(['priority' => $validated['priority']]);

        return back()->with('status', 'Priority updated.');
    }

    /**
     * Team members who may be assigned a ticket from this user's branch.
     *
     * @return Collection<int, User>
     */
    protected function agentsFor(User $user): Collection
    {
        return User::query()
            ->when($user->branch_id !== null, fn ($query) => $query->where('branch_id', $user->branch_id))
            ->whereIn('role', ['branch_admin', 'branch_staff', 'super_admin'])
            ->orderBy('name')
            ->get();
    }
}
