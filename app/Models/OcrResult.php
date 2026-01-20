<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrResult extends Model
{
    protected $fillable = [
        'document_upload_id',
        'field_name',
        'raw_text',
        'parsed_value',
        'confidence_score',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function documentUpload(): BelongsTo
    {
        return $this->belongsTo(DocumentUpload::class);
    }
}
