<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(public ?Subscription $trial = null) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Welcome to NexusVPN')
            ->greeting('Welcome, '.$notifiable->name.'!')
            ->line('Your NexusVPN account is ready.');

        if ($this->trial) {
            $message->line('Your free trial of the '.$this->trial->plan?->name.' plan runs until '.$this->trial->trial_ends_at?->toFormattedDayDateString().'.');
        }

        return $message
            ->line('Confirm your email address (we sent a separate link) to download your VPN configuration.')
            ->action('Open your dashboard', config('app.frontend_url').'/dashboard');
    }
}
