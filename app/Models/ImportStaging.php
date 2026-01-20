<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportStaging extends Model
{
    protected $table = 'import_staging';

    protected $fillable = [
        'import_batch_id',
        'agency_id',
        'row_number',
        'raw_data',
        'mapped_data',
        'status',
        'duplicate_key',
        'matched_pds_id',
        'validation_errors',
        'notes',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'mapped_data' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function matchedPds(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class, 'matched_pds_id');
    }

    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    public function isDuplicate(): bool
    {
        return $this->status === 'duplicate';
    }

    public function hasErrors(): bool
    {
        return !empty($this->validation_errors);
    }
}
