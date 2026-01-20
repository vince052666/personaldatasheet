<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'agency_id',
        'user_id',
        'filename',
        'status',
        'total_records',
        'processed_records',
        'success_count',
        'error_count',
        'duplicate_count',
        'summary',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stagingRecords(): HasMany
    {
        return $this->hasMany(ImportStaging::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_records === 0) {
            return 0;
        }
        return round(($this->processed_records / $this->total_records) * 100, 2);
    }

    public function isComplete(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }
}
