<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketReplyReceivedNotification;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Customers can add follow-up messages to their own open inquiries.
 */
class TicketReplyController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('reply', $ticket);

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $reply = $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_internal_note' => false,
        ]);

        $this->activityLogger->log(
            ActivityAction::TICKET_REPLIED,
            "{$request->user()->name} added a message to inquiry {$ticket->reference}.",
            $ticket,
            ['reference' => $ticket->reference],
            $request->user(),
            $ticket->branch,
        );

        $recipients = $ticket->branch->staff()
            ->whereIn('role', ['branch_admin', 'branch_staff'])
            ->get()
            ->merge(User::superAdmins()->get())
            ->unique('id');

        $recipients->each(fn (User $recipient) => $recipient->notify(
            new TicketReplyReceivedNotification($ticket->load('branch'), $reply),
        ));

        return back()->with('status', 'Message sent.');
    }
}
