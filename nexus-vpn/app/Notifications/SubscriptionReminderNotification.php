<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Trial-ending, renewal-due and expired notices. Payments are one-off, so the user must pay again to keep access.
 */
class SubscriptionReminderNotification extends Notification
{
    use Queueable;

    public const TRIAL_ENDING = 'trial_ending';

    public const RENEWAL_DUE = 'renewal_due';

    public const EXPIRED = 'expired';

    public function __construct(public Subscription $subscription, public string $kind) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = $this->subscription->plan?->name;
        $date = $this->subscription->current_period_end?->toFormattedDayDateString();
        $url = config('app.frontend_url').'/#pricing';

        return match ($this->kind) {
            self::TRIAL_ENDING => (new MailMessage)
                ->subject('Your NexusVPN trial ends soon')
                ->line("Your {$plan} trial ends on {$date}.")
                ->line('Subscribe before then to keep your VPN connection working.')
                ->action('Choose a plan', $url),
            self::RENEWAL_DUE => (new MailMessage)
                ->subject('Your NexusVPN subscription ends soon')
                ->line("Your {$plan} subscription is paid until {$date}.")
                ->line('Renew to avoid losing access. Payments do not renew automatically.')
                ->action('Renew now', $url),
            default => (new MailMessage)
                ->subject('Your NexusVPN access has ended')
                ->line("Your {$plan} ".($this->subscription->payments()->exists() ? 'subscription' : 'trial')." ended on {$date}.")
                ->line('Your VPN configuration has been deactivated. Subscribe again any time to restore access.')
                ->action('See plans', $url),
        };
    }
}
