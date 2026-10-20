<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once an account is created so the new customer knows what to do next.
 */
class WelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly User $user) {}

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
            'heading' => 'Welcome to '.config('app.name'),
            'lines' => [
                'Your account is ready. You can submit inquiries, track replies and review our stations.',
            ],
            'user_id' => $this->user->id,
            'url' => route('branches.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to '.config('app.name'))
            ->greeting("Hello {$notifiable->name},")
            ->line('Your customer account is ready. You can now submit inquiries, track replies and review our branches.')
            ->action('Find a branch', route('branches.index'));
    }
}
