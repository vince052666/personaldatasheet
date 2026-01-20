<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataConsent extends Model
{
    protected $fillable = [
        'user_id',
        'consent_type',
        'consented',
        'consented_at',
        'withdrawn_at',
        'ip_address',
        'version',
        'metadata',
    ];

    protected $casts = [
        'consented' => 'boolean',
        'consented_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->consented && !$this->withdrawn_at;
    }

    public function withdraw(): void
    {
        $this->update([
            'consented' => false,
            'withdrawn_at' => now(),
        ]);
    }
}

class DataSubjectRequest extends Model
{
    protected $fillable = [
        'user_id',
        'request_type',
        'status',
        'description',
        'response',
        'processed_by',
        'requested_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function complete(string $response): void
    {
        $this->update([
            'status' => 'completed',
            'response' => $response,
            'processed_by' => auth()->id(),
            'completed_at' => now(),
        ]);
    }
}
