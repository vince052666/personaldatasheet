<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportError extends Model
{
    protected $fillable = [
        'import_batch_id',
        'import_staging_id',
        'agency_id',
        'row_number',
        'error_type',
        'field',
        'error_message',
        'error_context',
        'severity',
        'resolved',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'error_context' => 'array',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function staging(): BelongsTo
    {
        return $this->belongsTo(ImportStaging::class, 'import_staging_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }
}
