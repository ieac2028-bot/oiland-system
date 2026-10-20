<?php

namespace App\Notifications;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the customer whenever their inquiry moves to a new status.
 */
class TicketStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketStatus $previousStatus,
        public readonly ?string $latestReply = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'heading' => "Inquiry {$this->ticket->reference} is now {$this->ticket->status->label()}",
            'lines' => array_values(array_filter([
                "{$this->ticket->branch->name}: {$this->ticket->subject}",
                "Changed from {$this->previousStatus->label()} to {$this->ticket->status->label()}.",
                $this->latestReply !== null ? 'Latest update: '.$this->latestReply : null,
            ])),
            'ticket_id' => $this->ticket->id,
            'reference' => $this->ticket->reference,
            'branch_id' => $this->ticket->branch_id,
            'subject' => $this->ticket->subject,
            'previous_status' => $this->previousStatus->value,
            'status' => $this->ticket->status->value,
            'status_label' => $this->ticket->status->label(),
            'latest_reply' => $this->latestReply,
            'url' => route('my.tickets.show', $this->ticket),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Inquiry {$this->ticket->reference} is now {$this->ticket->status->label()}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your inquiry at {$this->ticket->branch->name} changed status from {$this->previousStatus->label()} to {$this->ticket->status->label()}.");

        if (filled($this->latestReply)) {
            $message->line("Latest update: {$this->latestReply}");
        }

        return $message
            ->action('View inquiry', route('my.tickets.show', $this->ticket))
            ->line('Thank you for using our network.');
    }
}
