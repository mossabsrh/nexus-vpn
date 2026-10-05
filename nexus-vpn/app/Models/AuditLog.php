<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    /**
     * Record a security- or billing-relevant event. Never pass secrets in $metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function record(string $action, ?Model $subject = null, array $metadata = [], ?User $actor = null): self
    {
        $request = request();

        return self::query()->create([
            'actor_id' => $actor?->id ?? $request->user()?->id,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata ?: null,
            // Scheduled commands have no remote client.
            'ip_address' => $request->server('REMOTE_ADDR') ? $request->ip() : null,
        ]);
    }
}
