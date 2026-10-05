<?php

namespace App\Models;

use App\Notifications\PaymentReceiptNotification;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'amount',
        'currency',
        'status',
        'failure_reason',
        'error_code',
        'provider',
        'provider_reference',
        'provider_response',
        'metadata',
        'paid_at',
        'refunded_at',
        'refund_reason',
    ];

    protected $hidden = [
        'provider_response',
    ];

    protected function casts(): array
    {
        return [
            'provider_response' => 'array',
            'metadata' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class)->withTrashed();
    }

    public function plan(): ?Plan
    {
        $planId = $this->metadata['plan_id'] ?? null;

        return $planId ? Plan::withTrashed()->find($planId) : null;
    }

    /**
     * Receipt-level details safe to show to the paying user and to admins.
     *
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $plan = $this->plan();

        return [
            'id' => $this->id,
            'reference' => $this->metadata['payment_id'] ?? null,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'planId' => $plan?->slug,
            'planName' => $plan?->name,
            'billingPeriod' => $this->metadata['billing_period'] ?? null,
            'provider' => $this->provider,
            'checkoutId' => $this->provider_reference,
            'subscriptionId' => $this->subscription_id,
            'failureReason' => $this->failure_reason,
            'paidAt' => $this->paid_at?->toISOString(),
            'refundedAt' => $this->refunded_at?->toISOString(),
            'refundReason' => $this->refund_reason,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Mark a pending payment as paid and apply it to the user's subscription, exactly once.
     *
     * Safe under concurrent verify() and webhook() calls: the pending -> succeeded transition is a
     * conditional update on a locked row, so only one caller can claim it.
     *
     * @param  array<string, mixed>  $providerData
     */
    public function markSucceeded(array $providerData): bool
    {
        $subscription = DB::transaction(function () use ($providerData): Subscription|false|null {
            $payment = self::query()->whereKey($this->id)->lockForUpdate()->first();

            if (! $payment || $payment->status !== 'pending') {
                return false;
            }

            $claimed = self::query()->whereKey($this->id)->where('status', 'pending')->update([
                'status' => 'succeeded',
                'paid_at' => now(),
                'failure_reason' => null,
                'error_code' => null,
                'provider_response' => json_encode(array_merge((array) $payment->provider_response, $providerData)),
            ]);

            if ($claimed !== 1) {
                return false;
            }

            $payment->refresh();
            $subscription = $payment->applyToSubscription();

            if ($subscription) {
                $payment->update(['subscription_id' => $subscription->id]);
            }

            return $subscription;
        });

        if ($subscription === false) {
            return false;
        }

        $this->refresh();
        AuditLog::record('payment.succeeded', $this, [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'subscription_id' => $this->subscription_id,
        ], $this->user);

        if ($subscription) {
            $this->afterActivation($subscription);
        }

        return true;
    }

    public function markFailed(string $reason, ?string $errorCode = null): bool
    {
        $updated = self::query()->whereKey($this->id)->where('status', 'pending')->update([
            'status' => 'failed',
            'failure_reason' => mb_substr($reason, 0, 255),
            'error_code' => $errorCode ? mb_substr($errorCode, 0, 100) : null,
            'updated_at' => now(),
        ]);

        if ($updated === 1) {
            $this->refresh();
            AuditLog::record('payment.failed', $this, ['reason' => $this->failure_reason, 'error_code' => $this->error_code], $this->user);
        }

        return $updated === 1;
    }

    /**
     * Record a refund already made in the Chargily dashboard (Chargily Pay v2 has no refund API or webhook).
     */
    public function recordRefund(string $reason, bool $endSubscription, User $admin): void
    {
        $subscription = DB::transaction(function () use ($reason, $endSubscription): ?Subscription {
            $payment = self::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($payment->status !== 'succeeded') {
                throw new DomainException('Only succeeded payments can be refunded.');
            }

            $payment->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refund_reason' => mb_substr($reason, 0, 255),
            ]);

            $subscription = $payment->subscription_id
                ? Subscription::query()->whereKey($payment->subscription_id)->lockForUpdate()->first()
                : null;

            if ($endSubscription && $subscription && $subscription->grantsAccess()) {
                $subscription->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                    'current_period_end' => now(),
                ]);
            }

            return $subscription;
        });

        $this->refresh();
        AuditLog::record('payment.refunded', $this, [
            'amount' => $this->amount,
            'reason' => $this->refund_reason,
            'subscription_ended' => $endSubscription && $subscription !== null,
        ], $admin);

        if ($subscription) {
            $subscription->refresh()->revokeAccessIfEnded('refund');
        }
    }

    /**
     * Subscription rules for a paid payment:
     * - same plan, already active -> renewal: extend from the later of now / current period end;
     * - same plan, trialing       -> conversion: the trial row becomes the paid subscription;
     * - other current plan        -> plan change: the old one ends now and a new one starts;
     * - nothing current           -> a new subscription, starting after any paid days left on a canceled
     *                                 subscription to the same plan. Canceled/expired rows are history and never revived.
     */
    private function applyToSubscription(): ?Subscription
    {
        $metadata = $this->metadata ?? [];
        $user = User::withTrashed()->find($metadata['user_id'] ?? $this->user_id);
        $plan = Plan::withTrashed()->find($metadata['plan_id'] ?? null);
        $billingPeriod = ($metadata['billing_period'] ?? 'monthly') === 'yearly' ? 'yearly' : 'monthly';

        if (! $user || ! $plan) {
            Log::error('Paid payment could not be applied: user or plan not found.', [
                'payment_id' => $this->id,
                'user_id' => $metadata['user_id'] ?? $this->user_id,
                'plan_id' => $metadata['plan_id'] ?? null,
            ]);
            $this->update(['error_code' => 'activation_failed', 'failure_reason' => 'User or plan not found during activation.']);

            return null;
        }

        $addPeriod = fn (Carbon $from): Carbon => $billingPeriod === 'yearly'
            ? $from->copy()->addYearNoOverflow()
            : $from->copy()->addMonthNoOverflow();
        $current = $user->subscriptions()->current()->lockForUpdate()->latest('id')->first();

        if ($current && $current->plan_id === $plan->id && $current->status === 'active') {
            $from = $current->current_period_end?->isFuture() ? $current->current_period_end : now();
            $current->update([
                'billing_period' => $billingPeriod,
                'current_period_end' => $addPeriod($from),
                'renewal_reminder_sent_at' => null,
            ]);

            return $current;
        }

        if ($current && $current->plan_id === $plan->id) {
            $current->update([
                'status' => 'active',
                'billing_period' => $billingPeriod,
                'started_at' => now(),
                'current_period_end' => $addPeriod(now()),
            ]);

            return $current;
        }

        if ($current) {
            $current->update(['status' => 'canceled', 'canceled_at' => now(), 'current_period_end' => now()]);
        }

        // Resubscribing to a plan canceled earlier: the new period starts after the days already paid for.
        $paidUntil = $current ? null : $user->subscriptions()->grantingAccess()->where('status', 'canceled')
            ->where('plan_id', $plan->id)->max('current_period_end');

        return $user->subscriptions()->create([
            'plan_id' => $plan->id,
            'seats' => 1,
            'billing_period' => $billingPeriod,
            'status' => 'active',
            'started_at' => now(),
            'current_period_end' => $addPeriod($paidUntil ? Carbon::parse($paidUntil) : now()),
        ]);
    }

    private function afterActivation(Subscription $subscription): void
    {
        $user = $subscription->user;

        try {
            if ($user->hasVerifiedEmail()) {
                VpnCredential::issueFor($user, $subscription);
            }
        } catch (Throwable $exception) {
            Log::error('VPN credential provisioning failed after payment.', ['payment_id' => $this->id, 'exception' => $exception->getMessage()]);
        }

        try {
            $user->notify(new PaymentReceiptNotification($this, $subscription));
        } catch (Throwable $exception) {
            Log::error('Payment receipt email failed.', ['payment_id' => $this->id, 'exception' => $exception->getMessage()]);
        }
    }
}
