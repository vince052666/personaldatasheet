<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RecordLock extends Model
{
    protected $fillable = [
        'lockable_type',
        'lockable_id',
        'locked_by',
        'locked_at',
        'expires_at',
        'session_id',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function lockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}
