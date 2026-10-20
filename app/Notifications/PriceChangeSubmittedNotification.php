<?php

namespace App\Notifications;

use App\Models\PriceChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to Super Admins when a Branch Admin submits a price change for review.
 */
class PriceChangeSubmittedNotification extends Notification
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
            'heading' => "Price change awaiting review: {$this->request->branch->name}",
            'lines' => [
                $this->request->catalogItem->displayName().': '.($this->request->old_price ?? 'no previous price').' → '.$this->request->new_price,
                'Submitted by '.$this->request->requester->name.'.',
            ],
            'price_change_request_id' => $this->request->id,
            'branch_id' => $this->request->branch_id,
            'branch' => $this->request->branch->name,
            'catalog_item' => $this->request->catalogItem->name,
            'old_price' => $this->request->old_price,
            'new_price' => $this->request->new_price,
            'requested_by' => $this->request->requester->name,
            'url' => route('admin.price-changes.show', $this->request),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $oldPrice = $this->request->old_price ?? '—';

        return (new MailMessage)
            ->subject("Price change awaiting review: {$this->request->branch->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->request->requester->name} submitted a price change for approval.")
            ->line("Branch: {$this->request->branch->name}")
            ->line("Item: {$this->request->catalogItem->displayName()}")
            ->line("From: {$oldPrice} — To: {$this->request->new_price}")
            ->action('Review request', route('admin.price-changes.show', $this->request));
    }
}
