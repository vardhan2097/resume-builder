<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once to an inactive account, a week before it is deleted.
 * Not queued: the host has no queue worker, and the daily run is small.
 */
class InactiveAccountWarning extends Notification
{
    use Queueable;

    public function __construct(public CarbonInterface $deleteOn) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = config('app.name');

        return (new MailMessage)
            ->subject("Your {$app} account will be deleted soon")
            ->greeting("Hi {$notifiable->name},")
            ->line("You haven't used {$app} for a while. To protect your privacy, we delete inactive accounts.")
            ->line("Your account, resumes and photo will be permanently deleted on **{$this->deleteOn->format('j F Y')}** unless you log in before then.")
            ->action('Log in to keep your account', route('login'))
            ->line('If you no longer need your account, you don\'t have to do anything.');
    }
}
