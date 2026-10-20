<?php

namespace App\Http\Controllers\Customer;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\InquiryReceivedNotification;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $tickets = Ticket::query()
            ->where('customer_id', $user->id)
            ->with(['branch', 'assignee'])
            ->when($request->filled('status'), function ($query) use ($request): void {
                $query->where('status', $request->string('status')->toString());
            })
            ->when($request->filled('branch_id'), function ($query) use ($request): void {
                $query->where('branch_id', $request->integer('branch_id'));
            })
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('customer.tickets.index', [
            'tickets' => $tickets,
            'statuses' => TicketStatus::options(),
            'branches' => Branch::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['status', 'branch_id']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('customer.tickets.create', [
            'branches' => Branch::active()->orderBy('name')->get(['id', 'name', 'city']),
            'categories' => Ticket::CATEGORIES,
            'selectedBranchId' => $request->integer('branch_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'subject' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(Ticket::CATEGORIES))],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $branch = Branch::findOrFail($validated['branch_id']);

        $ticket = Ticket::create([
            ...$validated,
            'customer_id' => $request->user()->id,
            'status' => TicketStatus::Pending->value,
        ]);

        $this->activityLogger->log(
            ActivityAction::TICKET_CREATED,
            "Inquiry {$ticket->reference} opened at {$branch->name}.",
            $ticket,
            [
                'reference' => $ticket->reference,
                'category' => $ticket->category,
                'priority' => $ticket->priority->value,
            ],
            $request->user(),
            $branch,
        );

        $this->notifyBranchStaff($ticket);

        return redirect()
            ->route('my.tickets.show', $ticket)
            ->with('status', "Inquiry {$ticket->reference} submitted. The branch has been notified.");
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['branch', 'replies' => fn ($query) => $query->with('author')->oldest()]);

        return view('customer.tickets.show', [
            'ticket' => $ticket,
            // Internal notes are branch-internal, so they are never handed to a
            // customer view even though the relation is eager loaded.
            'replies' => $ticket->replies->where('is_internal_note', false)->values(),
        ]);
    }

    /**
     * Alert the staff of the branch the inquiry was filed against. Super Admins
     * stay in the loop so unassigned branches are never missed.
     */
    protected function notifyBranchStaff(Ticket $ticket): void
    {
        $ticket->loadMissing('branch');

        $recipients = $ticket->branch->staff()
            ->whereIn('role', ['branch_admin', 'branch_staff'])
            ->get()
            ->merge(User::superAdmins()->get())
            ->unique('id');

        $recipients->each(fn ($recipient) => $recipient->notify(new InquiryReceivedNotification($ticket)));
    }
}
