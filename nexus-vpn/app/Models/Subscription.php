<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    /**
     * Statuses that count as the user's current subscription. At most one per user (database-enforced).
     */
    public const CURRENT_STATUSES = ['trialing', 'active'];

    protected $fillable = [
        'user_id',
        'plan_id',
        'seats',
        'billing_period',
        'status',
        'started_at',
        'trial_ends_at',
        'current_period_end',
        'canceled_at',
        'trial_reminder_sent_at',
        'renewal_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'current_period_end' => 'datetime',
            'canceled_at' => 'datetime',
            'trial_reminder_sent_at' => 'datetime',
            'renewal_reminder_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class)->withTrashed();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function team(): HasOne
    {
        return $this->hasOne(Team::class);
    }

    public function vpnCredentials(): HasMany
    {
        return $this->hasMany(VpnCredential::class);
    }

    public function scopeCurrent(Builder $query): void
    {
        $query->whereIn('status', self::CURRENT_STATUSES);
    }

    /**
     * Subscriptions whose paid or trial period is still running. A paid subscription canceled by the
     * user keeps access until the end of the period it was paid for.
     */
    public function scopeGrantingAccess(Builder $query): void
    {
        $query->whereIn('status', [...self::CURRENT_STATUSES, 'canceled'])
            ->where('current_period_end', '>', now());
    }

    public function isTrial(): bool
    {
        return $this->status === 'trialing';
    }

    public function grantsAccess(): bool
    {
        return in_array($this->status, [...self::CURRENT_STATUSES, 'canceled'], true)
            && $this->current_period_end !== null
            && $this->current_period_end->isFuture();
    }

    /**
     * User-initiated cancellation. A trial ends immediately; a paid period was paid for up front
     * (Chargily payments are one-off, nothing auto-renews), so access continues until it ends.
     */
    public function cancel(string $reason = 'user'): void
    {
        $this->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'current_period_end' => $this->isTrial() ? now() : $this->current_period_end,
        ]);

        AuditLog::record('subscription.canceled', $this, ['reason' => $reason, 'plan' => $this->plan?->slug]);
        $this->revokeAccessIfEnded($reason);
    }

    /**
     * Revoke VPN peers of the owner (and team members) who no longer have any subscription granting access.
     */
    public function revokeAccessIfEnded(string $reason): void
    {
        $users = collect([$this->user])->merge($this->team?->members ?? [])->filter()->unique('id');

        foreach ($users as $user) {
            if (! $user->hasVpnAccess()) {
                VpnCredential::revokeFor($user, $reason);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'planId' => $this->plan?->slug,
            'planName' => $this->plan?->name,
            'status' => $this->status,
            'billingPeriod' => $this->billing_period,
            'seats' => $this->seats,
            'startedAt' => $this->started_at?->toISOString(),
            'trialEndsAt' => $this->trial_ends_at?->toISOString(),
            'currentPeriodEnd' => $this->current_period_end?->toISOString(),
            'canceledAt' => $this->canceled_at?->toISOString(),
            'grantsAccess' => $this->grantsAccess(),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
