<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoluntaryWork extends Model
{
    protected $fillable = [
        'personal_data_sheet_id',
        'organization_name',
        'organization_address',
        'from_date',
        'to_date',
        'number_of_hours',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
        ];
    }

    public function personalDataSheet(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class);
    }
}
