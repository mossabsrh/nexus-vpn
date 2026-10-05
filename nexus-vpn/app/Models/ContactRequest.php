<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactRequest extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'company',
        'seats',
        'message',
        'status',
        'handled_by',
        'handled_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'company' => $this->company,
            'seats' => $this->seats,
            'message' => $this->message,
            'status' => $this->status,
            'handledAt' => $this->handled_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
