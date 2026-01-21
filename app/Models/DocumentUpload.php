<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentUpload extends Model
{
    protected $fillable = [
        'personal_data_sheet_id',
        'document_type',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'parsed_data',
        'status',
        'error_message',
        'uploaded_by',
        'processing_status',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function personalDataSheet(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function ocrResults(): HasMany
    {
        return $this->hasMany(OcrResult::class);
    }
}
