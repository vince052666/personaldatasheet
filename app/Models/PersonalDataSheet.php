<?php

namespace App\Models;

use App\Models\Scopes\AgencyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalDataSheet extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new AgencyScope());
    }

    protected $fillable = [
        'user_id',
        'agency_id',
        'surname',
        'first_name',
        'middle_name',
        'name_extension',
        'date_of_birth',
        'place_of_birth',
        'sex',
        'civil_status',
        'height',
        'weight',
        'blood_type',
        'gsis_id_no',
        'pagibig_id_no',
        'philhealth_no',
        'sss_no',
        'tin_no',
        'agency_employee_no',
        'citizenship',
        'citizenship_type',
        'country',
        'residential_house_no',
        'residential_street',
        'residential_subdivision',
        'residential_barangay',
        'residential_city',
        'residential_province',
        'residential_zip_code',
        'permanent_house_no',
        'permanent_street',
        'permanent_subdivision',
        'permanent_barangay',
        'permanent_city',
        'permanent_province',
        'permanent_zip_code',
        'telephone_no',
        'mobile_no',
        'email_address',
        'spouse_surname',
        'spouse_first_name',
        'spouse_middle_name',
        'spouse_name_extension',
        'spouse_occupation',
        'spouse_employer',
        'spouse_business_address',
        'spouse_telephone_no',
        'father_surname',
        'father_first_name',
        'father_middle_name',
        'father_name_extension',
        'mother_maiden_name',
        'mother_surname',
        'mother_first_name',
        'mother_middle_name',
        'children',
        'questions_answers',
        'references',
        'government_issued_id',
        'is_current',
        'version',
        'status',
        'department',
        'position',
        'reviewed_at',
        'reviewed_by',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'height' => 'decimal:2',
            'weight' => 'decimal:2',
            'children' => 'array',
            'questions_answers' => 'array',
            'references' => 'array',
            'government_issued_id' => 'array',
            'is_current' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function workExperiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class);
    }

    public function educationalBackgrounds(): HasMany
    {
        return $this->hasMany(EducationalBackground::class);
    }

    public function civilServiceEligibilities(): HasMany
    {
        return $this->hasMany(CivilServiceEligibility::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class);
    }

    public function voluntaryWorks(): HasMany
    {
        return $this->hasMany(VoluntaryWork::class);
    }

    public function otherInformation(): HasMany
    {
        return $this->hasMany(OtherInformation::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PdsVersion::class);
    }

    public function documentUploads(): HasMany
    {
        return $this->hasMany(DocumentUpload::class);
    }

    public function dataQualityScore(): HasOne
    {
        return $this->hasOne(DataQualityScore::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->surname,
            $this->first_name,
            $this->middle_name,
            $this->name_extension,
        ]);

        return implode(' ', $parts);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('surname', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('middle_name', 'like', "%{$search}%")
                ->orWhere('email_address', 'like', "%{$search}%");
        });
    }
}

    public function approvalWorkflow(): HasOne
    {
        return $this->hasOne(ApprovalWorkflow::class)->latest();
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(CandidateRanking::class);
    }

    public function appointmentReadiness(): HasMany
    {
        return $this->hasMany(AppointmentReadiness::class);
    }

    public function archivedRecord(): HasOne
    {
        return $this->hasOne(ArchivedRecord::class, 'archivable_id')
            ->where('archivable_type', self::class);
    }

    // Accessor for encrypted fields
    public function getTinAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function getSssNoAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function getPagibigNoAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function getPhilhealthNoAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function getResidentialAddressAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function getPermanentAddressAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    // Mutator for encrypted fields
    public function setTinAttribute($value)
    {
        $this->attributes['tin'] = $value ? encrypt($value) : null;
    }

    public function setSssNoAttribute($value)
    {
        $this->attributes['sss_no'] = $value ? encrypt($value) : null;
    }

    public function setPagibigNoAttribute($value)
    {
        $this->attributes['pagibig_no'] = $value ? encrypt($value) : null;
    }

    public function setPhilhealthNoAttribute($value)
    {
        $this->attributes['philhealth_no'] = $value ? encrypt($value) : null;
    }

    public function setResidentialAddressAttribute($value)
    {
        $this->attributes['residential_address'] = $value ? encrypt($value) : null;
    }

    public function setPermanentAddressAttribute($value)
    {
        $this->attributes['permanent_address'] = $value ? encrypt($value) : null;
    }
}
