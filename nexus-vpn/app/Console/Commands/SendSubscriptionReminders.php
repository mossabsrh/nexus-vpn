<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\SubscriptionReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSubscriptionReminders extends Command
{
    protected $signature = 'subscriptions:remind {--days=3 : Send reminders this many days before the period ends}';

    protected $description = 'Email users whose trial or paid period ends soon (once per period)';

    public function handle(): int
    {
        $deadline = now()->addDays((int) $this->option('days'));
        $sent = 0;

        $reminders = [
            ['trialing', 'trial_reminder_sent_at', SubscriptionReminderNotification::TRIAL_ENDING],
            ['active', 'renewal_reminder_sent_at', SubscriptionReminderNotification::RENEWAL_DUE],
        ];

        foreach ($reminders as [$status, $column, $kind]) {
            Subscription::query()
                ->where('status', $status)
                ->whereNull($column)
                ->whereBetween('current_period_end', [now(), $deadline])
                ->with('user', 'plan')
                ->chunkById(100, function ($subscriptions) use ($column, $kind, &$sent): void {
                    foreach ($subscriptions as $subscription) {
                        // Claim first so overlapping runs never double-send.
                        $claimed = Subscription::query()->whereKey($subscription->id)->whereNull($column)->update([$column => now()]);

                        if ($claimed !== 1 || ! $subscription->user) {
                            continue;
                        }

                        try {
                            $subscription->user->notify(new SubscriptionReminderNotification($subscription, $kind));
                            $sent++;
                        } catch (Throwable $exception) {
                            Subscription::query()->whereKey($subscription->id)->update([$column => null]);
                            Log::error('Subscription reminder email failed.', ['subscription_id' => $subscription->id, 'exception' => $exception->getMessage()]);
                        }
                    }
                });
        }

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
