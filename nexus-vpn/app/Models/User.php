<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function vpnCredentials(): HasMany
    {
        return $this->hasMany(VpnCredential::class);
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withPivot('role', 'created_at');
    }

    /**
     * The user's own active or trialing subscription, regardless of whether its period has ended.
     */
    public function currentSubscription(): ?Subscription
    {
        return $this->subscriptions()->current()->with('plan')->latest('id')->first();
    }

    /**
     * The subscription that currently grants VPN access: the user's own, or a team seat.
     */
    public function accessSubscription(): ?Subscription
    {
        $own = $this->subscriptions()->grantingAccess()->with('plan')->latest('id')->first();

        if ($own) {
            return $own;
        }

        return Subscription::query()
            ->grantingAccess()
            ->whereHas('team.members', fn ($query) => $query->whereKey($this->id))
            ->with('plan')
            ->latest('id')
            ->first();
    }

    public function hasVpnAccess(): bool
    {
        return $this->accessSubscription() !== null;
    }
}
