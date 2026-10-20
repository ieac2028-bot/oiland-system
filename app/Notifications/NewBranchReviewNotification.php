<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to Super Admins when a customer review needs moderation.
 */
class NewBranchReviewNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Review $review) {}

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
            'heading' => 'New review to moderate at '.$this->review->branch->name,
            'lines' => [
                sprintf('%s left a %d-star review.', $this->review->user->name, $this->review->rating),
                str($this->review->body)->limit(160)->toString(),
            ],
            'review_id' => $this->review->id,
            'branch_id' => $this->review->branch_id,
            'branch' => $this->review->branch->name,
            'rating' => $this->review->rating,
            'review_title' => $this->review->title,
            'is_flagged' => $this->review->is_flagged,
            'url' => route('admin.reviews.show', $this->review),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New review to moderate at {$this->review->branch->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->review->user->name} left a {$this->review->rating}-star review for {$this->review->branch->name}.")
            ->line(str($this->review->body)->limit(200)->toString())
            ->action('Moderate review', route('admin.reviews.show', $this->review));
    }
}
