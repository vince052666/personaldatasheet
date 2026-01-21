<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualificationStandard extends Model
{
    protected $fillable = [
        'position_title',
        'salary_grade',
        'item_number',
        'education_requirement',
        'experience_requirement',
        'training_requirement',
        'eligibility_requirement',
        'competency_requirements',
        'minimum_years_experience',
        'metadata',
    ];

    protected $casts = [
        'competency_requirements' => 'array',
        'metadata' => 'array',
    ];

    public function requirements(): HasMany
    {
        return $this->hasMany(PositionRequirement::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(CandidateRanking::class);
    }
}

class PositionRequirement extends Model
{
    protected $fillable = [
        'qualification_standard_id',
        'requirement_type',
        'requirement_text',
        'weight',
        'mandatory',
        'metadata',
    ];

    protected $casts = [
        'mandatory' => 'boolean',
        'metadata' => 'array',
    ];

    public function qualificationStandard(): BelongsTo
    {
        return $this->belongsTo(QualificationStandard::class);
    }
}

class CandidateRanking extends Model
{
    protected $fillable = [
        'personal_data_sheet_id',
        'qualification_standard_id',
        'total_score',
        'education_score',
        'experience_score',
        'training_score',
        'eligibility_score',
        'rank',
        'meets_requirements',
        'score_breakdown',
        'calculated_at',
    ];

    protected $casts = [
        'meets_requirements' => 'boolean',
        'score_breakdown' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function personalDataSheet(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class);
    }

    public function qualificationStandard(): BelongsTo
    {
        return $this->belongsTo(QualificationStandard::class);
    }
}

class AppointmentReadiness extends Model
{
    protected $table = 'appointment_readiness';

    protected $fillable = [
        'personal_data_sheet_id',
        'qualification_standard_id',
        'is_ready',
        'checklist',
        'missing_requirements',
        'notes',
        'assessed_at',
    ];

    protected $casts = [
        'is_ready' => 'boolean',
        'checklist' => 'array',
        'missing_requirements' => 'array',
        'assessed_at' => 'datetime',
    ];

    public function personalDataSheet(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class);
    }

    public function qualificationStandard(): BelongsTo
    {
        return $this->belongsTo(QualificationStandard::class);
    }
}
