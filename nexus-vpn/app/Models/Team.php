<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    protected $fillable = [
        'name',
        'owner_id',
        'subscription_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id')->withTrashed();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class)->withTrashed();
    }

    /**
     * Seat holders, including the owner (pivot role "owner").
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role', 'created_at');
    }

    public function seatLimit(): int
    {
        return (int) ($this->subscription?->seats ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $this->loadMissing('members', 'subscription.plan');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'ownerId' => $this->owner_id,
            'seats' => $this->seatLimit(),
            'seatsUsed' => $this->members->count(),
            'subscription' => $this->subscription?->toApiArray(),
            'members' => $this->members->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role,
            ])->values(),
        ];
    }
}
