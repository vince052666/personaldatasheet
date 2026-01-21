<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CivilServiceEligibility extends Model
{
    protected $fillable = [
        'personal_data_sheet_id',
        'eligibility',
        'rating',
        'date_of_examination',
        'place_of_examination',
        'license_number',
        'license_validity',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:2',
            'date_of_examination' => 'date',
            'license_validity' => 'date',
        ];
    }

    public function personalDataSheet(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class);
    }
}
