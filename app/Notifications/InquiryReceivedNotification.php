<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the staff of the branch an inquiry was filed against.
 */
class InquiryReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Ticket $ticket) {}

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
            'heading' => "New {$this->ticket->priority->label()} inquiry {$this->ticket->reference}",
            'lines' => [
                $this->ticket->subject,
                "From {$this->ticket->customer?->name} at {$this->ticket->branch->name}.",
            ],
            'ticket_id' => $this->ticket->id,
            'reference' => $this->ticket->reference,
            'branch_id' => $this->ticket->branch_id,
            'subject' => $this->ticket->subject,
            'customer' => $this->ticket->customer?->name,
            'priority' => $this->ticket->priority->value,
            'message' => str($this->ticket->message)->limit(160)->toString(),
            'url' => route('admin.tickets.show', $this->ticket),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New {$this->ticket->priority->label()} inquiry {$this->ticket->reference}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new customer inquiry has been filed at {$this->ticket->branch->name}.")
            ->line("Subject: {$this->ticket->subject}")
            ->line("Customer: {$this->ticket->customer?->name} <{$this->ticket->customer?->email}>")
            ->action('Open inquiry', route('admin.tickets.show', $this->ticket))
            ->line('Please respond to the customer as soon as possible.');
    }
}
