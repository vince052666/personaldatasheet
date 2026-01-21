<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkExperience extends Model
{
    protected $fillable = [
        'personal_data_sheet_id',
        'from_date',
        'to_date',
        'is_present',
        'position_title',
        'department',
        'company',
        'monthly_salary',
        'salary_grade',
        'status_of_appointment',
        'is_government_service',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'is_present' => 'boolean',
            'monthly_salary' => 'decimal:2',
            'is_government_service' => 'boolean',
        ];
    }

    public function personalDataSheet(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class);
    }
}
