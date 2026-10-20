<?php

namespace App\Notifications;

use App\Models\Price;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to customers following a branch when one of its prices changes.
 */
class BranchPriceChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Price $price,
        public readonly ?float $previousPrice = null,
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
            'heading' => "Price update at {$this->price->branch->name}",
            'lines' => array_values(array_filter([
                $this->price->catalogItem->displayName().': now '.number_format((float) $this->price->price, 2).' '.$this->price->currency,
                $this->previousPrice !== null
                    ? 'Was '.number_format((float) $this->previousPrice, 2).' '.$this->price->currency.'.'
                    : null,
                'Effective '.$this->price->effective_from->toFormattedDateString().'.',
            ])),
            'price_id' => $this->price->id,
            'branch_id' => $this->price->branch_id,
            'branch' => $this->price->branch->name,
            'catalog_item_id' => $this->price->catalog_item_id,
            'catalog_item' => $this->price->catalogItem->displayName(),
            'previous_price' => $this->previousPrice,
            'price' => (float) $this->price->price,
            'currency' => $this->price->currency,
            'effective_from' => $this->price->effective_from->toDateString(),
            'url' => route('branches.show', $this->price->branch).'#prices',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Price update at {$this->price->branch->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A price you follow at {$this->price->branch->name} has been updated.")
            ->line("Item: {$this->price->catalogItem->displayName()}");

        if ($this->previousPrice !== null) {
            $message->line("Was: {$this->previousPrice} {$this->price->currency}");
        }

        return $message
            ->line("Now: {$this->price->price} {$this->price->currency} per {$this->price->catalogItem->unit}, effective {$this->price->effective_from->toFormattedDateString()}.")
            ->action('View branch prices', route('branches.show', $this->price->branch).'#prices');
    }
}
