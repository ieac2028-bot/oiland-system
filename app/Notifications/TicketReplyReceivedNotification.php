<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketReplyReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketReply $reply,
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
        $isCustomer = $notifiable instanceof User && $notifiable->isCustomer();

        return [
            'heading' => $isCustomer
                ? "A new reply was added to inquiry {$this->ticket->reference}"
                : "A customer replied to inquiry {$this->ticket->reference}",
            'lines' => [
                $this->ticket->subject,
                str($this->reply->message)->limit(160)->toString(),
            ],
            'ticket_id' => $this->ticket->id,
            'reference' => $this->ticket->reference,
            'url' => $isCustomer
                ? route('my.tickets.show', $this->ticket)
                : route('admin.tickets.show', $this->ticket),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isCustomer = $notifiable instanceof User && $notifiable->isCustomer();

        return (new MailMessage)
            ->subject($isCustomer
                ? "OiLand replied to inquiry {$this->ticket->reference}"
                : "Customer replied to inquiry {$this->ticket->reference}")
            ->greeting("Hello {$notifiable->name},")
            ->line($isCustomer
                ? "The branch team replied to your inquiry: {$this->ticket->subject}."
                : "The customer sent a new message about: {$this->ticket->subject}.")
            ->line(str($this->reply->message)->limit(500)->toString())
            ->action('Open inquiry', $this->toArray($notifiable)['url']);
    }
}
