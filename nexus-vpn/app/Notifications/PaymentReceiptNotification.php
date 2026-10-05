<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceiptNotification extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment, public Subscription $subscription) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your NexusVPN payment receipt')
            ->greeting('Thank you for your payment')
            ->line('Receipt: '.($this->payment->metadata['payment_id'] ?? '#'.$this->payment->id))
            ->line('Plan: '.$this->subscription->plan?->name.' ('.$this->subscription->billing_period.')')
            ->line('Amount: '.number_format($this->payment->amount).' '.$this->payment->currency)
            ->line('Paid on: '.$this->payment->paid_at?->toDayDateTimeString().' UTC')
            ->line('Access until: '.$this->subscription->current_period_end?->toFormattedDayDateString())
            ->action('View billing history', config('app.frontend_url').'/dashboard');
    }
}
