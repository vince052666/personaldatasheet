<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DataRetentionPolicy extends Model
{
    protected $fillable = [
        'resource_type',
        'retention_years',
        'auto_archive',
        'auto_delete',
        'description',
        'metadata',
    ];

    protected $casts = [
        'auto_archive' => 'boolean',
        'auto_delete' => 'boolean',
        'metadata' => 'array',
    ];
}

class ArchivedRecord extends Model
{
    protected $fillable = [
        'archivable_type',
        'archivable_id',
        'archived_at',
        'delete_after',
        'archive_reason',
        'archived_by',
        'original_data',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'delete_after' => 'datetime',
        'original_data' => 'array',
    ];

    public function archivable(): MorphTo
    {
        return $this->morphTo();
    }

    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function shouldBeDeleted(): bool
    {
        return $this->delete_after && $this->delete_after <= now();
    }
}
