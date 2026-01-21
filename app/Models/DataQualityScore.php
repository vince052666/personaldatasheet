<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataQualityScore extends Model
{
    protected $fillable = [
        'personal_data_sheet_id',
        'completeness_score',
        'accuracy_score',
        'consistency_score',
        'overall_score',
        'field_scores',
        'issues',
        'suggestions',
        'last_analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'completeness_score' => 'decimal:2',
            'accuracy_score' => 'decimal:2',
            'consistency_score' => 'decimal:2',
            'overall_score' => 'decimal:2',
            'field_scores' => 'array',
            'issues' => 'array',
            'suggestions' => 'array',
            'last_analyzed_at' => 'datetime',
        ];
    }

    public function personalDataSheet(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class);
    }
}
