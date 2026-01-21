<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'activity_type',
        'ip_address',
        'user_agent',
        'session_id',
        'description',
        'metadata',
        'performed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'performed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function logActivity(
        string $activityType,
        ?int $userId = null,
        ?string $description = null,
        array $metadata = []
    ): self {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'activity_type' => $activityType,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'description' => $description,
            'metadata' => $metadata,
            'performed_at' => now(),
        ]);
    }
}

class SecurityEvent extends Model
{
    protected $fillable = [
        'event_type',
        'severity',
        'user_id',
        'ip_address',
        'description',
        'metadata',
        'resolved',
        'detected_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'resolved' => 'boolean',
        'detected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }
}
