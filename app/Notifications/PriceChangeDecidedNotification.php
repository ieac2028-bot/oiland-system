<?php

namespace App\Notifications;

use App\Models\PriceChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the requesting Branch Admin once head office approves or rejects.
 */
class PriceChangeDecidedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly PriceChangeRequest $request) {}

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
            'heading' => "Price change {$this->request->status->label()}",
            'lines' => array_values(array_filter([
                $this->request->catalogItem->displayName().' at '.$this->request->branch->name.': '.$this->request->new_price,
                $this->request->review_note !== null ? 'Reviewer note: '.$this->request->review_note : null,
            ])),
            'price_change_request_id' => $this->request->id,
            'branch_id' => $this->request->branch_id,
            'status' => $this->request->status->value,
            'status_label' => $this->request->status->label(),
            'new_price' => $this->request->new_price,
            'review_note' => $this->request->review_note,
            'url' => route('admin.price-changes.show', $this->request),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Price change {$this->request->status->label()}: {$this->request->catalogItem->displayName()}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your price change for {$this->request->catalogItem->displayName()} at {$this->request->branch->name} was {$this->request->status->label()}.")
            ->line("New price: {$this->request->new_price}");

        if (filled($this->request->review_note)) {
            $message->line("Reviewer note: {$this->request->review_note}");
        }

        return $message->action('View request', route('admin.price-changes.show', $this->request));
    }
}
