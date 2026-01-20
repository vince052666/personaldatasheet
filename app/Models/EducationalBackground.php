<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EducationalBackground extends Model
{
    protected $fillable = [
        'personal_data_sheet_id',
        'level',
        'school_name',
        'degree_course',
        'year_from',
        'year_to',
        'units_earned',
        'year_graduated',
        'scholarship',
    ];

    public function personalDataSheet(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class);
    }
}
