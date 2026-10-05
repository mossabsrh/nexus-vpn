<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Subscription;
use App\Models\VpnCredential;
use App\Notifications\SubscriptionReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Mark ended trials/subscriptions as expired and revoke VPN access that is no longer paid for';

    public function handle(): int
    {
        $expired = 0;

        Subscription::query()
            ->current()
            ->where('current_period_end', '<=', now())
            ->with('user', 'plan')
            ->chunkById(100, function ($subscriptions) use (&$expired): void {
                foreach ($subscriptions as $subscription) {
                    $updated = Subscription::query()->whereKey($subscription->id)->current()
                        ->where('current_period_end', '<=', now())
                        ->update(['status' => 'expired', 'updated_at' => now()]);

                    if ($updated !== 1) {
                        continue;
                    }

                    $expired++;
                    AuditLog::record('subscription.expired', $subscription, ['plan' => $subscription->plan?->slug]);

                    try {
                        $subscription->user?->notify(new SubscriptionReminderNotification($subscription->refresh(), SubscriptionReminderNotification::EXPIRED));
                    } catch (Throwable $exception) {
                        Log::error('Expiry email failed.', ['subscription_id' => $subscription->id, 'exception' => $exception->getMessage()]);
                    }
                }
            });

        // Covers expired rows, canceled paid periods that just ran out, and team seats of ended team plans.
        $revoked = 0;
        VpnCredential::query()->where('status', 'active')->with('user')->chunkById(100, function ($credentials) use (&$revoked): void {
            foreach ($credentials as $credential) {
                if ($credential->user && ! $credential->user->hasVpnAccess()) {
                    $revoked += VpnCredential::revokeFor($credential->user, 'expired');
                }
            }
        });

        $this->info("Expired {$expired} subscription(s), revoked {$revoked} VPN credential(s).");

        return self::SUCCESS;
    }
}
