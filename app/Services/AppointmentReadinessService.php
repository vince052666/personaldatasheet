<?php

namespace App\Services;

use App\Models\PersonalDataSheet;
use App\Models\QualificationStandard;
use App\Models\AppointmentReadiness;

class AppointmentReadinessService
{
    public function assessReadiness(PersonalDataSheet $pds, QualificationStandard $standard): AppointmentReadiness
    {
        $checklist = $this->buildChecklist($pds, $standard);
        $missingRequirements = $this->getMissingRequirements($checklist);
        $isReady = empty($missingRequirements);

        return AppointmentReadiness::updateOrCreate(
            [
                'personal_data_sheet_id' => $pds->id,
                'qualification_standard_id' => $standard->id,
            ],
            [
                'is_ready' => $isReady,
                'checklist' => $checklist,
                'missing_requirements' => $missingRequirements,
                'assessed_at' => now(),
            ]
        );
    }

    private function buildChecklist(PersonalDataSheet $pds, QualificationStandard $standard): array
    {
        return [
            'personal_information' => $this->checkPersonalInformation($pds),
            'education' => $this->checkEducation($pds, $standard),
            'experience' => $this->checkExperience($pds, $standard),
            'training' => $this->checkTraining($pds, $standard),
            'eligibility' => $this->checkEligibility($pds, $standard),
            'documents' => $this->checkDocuments($pds),
            'pds_complete' => $this->checkPdsComplete($pds),
            'approval_status' => $this->checkApprovalStatus($pds),
        ];
    }

    private function checkPersonalInformation(PersonalDataSheet $pds): array
    {
        $required = ['surname', 'first_name', 'date_of_birth', 'place_of_birth', 'citizenship'];
        $complete = true;

        foreach ($required as $field) {
            if (empty($pds->$field)) {
                $complete = false;
                break;
            }
        }

        return [
            'status' => $complete,
            'message' => $complete ? 'Complete' : 'Missing required personal information',
        ];
    }

    private function checkEducation(PersonalDataSheet $pds, QualificationStandard $standard): array
    {
        $hasEducation = $pds->educationalBackgrounds->isNotEmpty();
        $meetsRequirement = false;

        if ($hasEducation && $standard->education_requirement) {
            $highestLevel = $this->getHighestEducationLevel($pds);
            $requiredLevel = $this->parseEducationRequirement($standard->education_requirement);
            $meetsRequirement = $highestLevel >= $requiredLevel;
        }

        return [
            'status' => $meetsRequirement,
            'message' => $meetsRequirement 
                ? 'Meets education requirement' 
                : 'Does not meet education requirement',
        ];
    }

    private function checkExperience(PersonalDataSheet $pds, QualificationStandard $standard): array
    {
        $experienceService = app(ExperienceScoringService::class);
        $totalYears = $experienceService->getTotalYearsExperience($pds);
        $meetsRequirement = $totalYears >= $standard->minimum_years_experience;

        return [
            'status' => $meetsRequirement,
            'message' => $meetsRequirement 
                ? "Has {$totalYears} years of experience" 
                : "Requires {$standard->minimum_years_experience} years, has {$totalYears} years",
            'total_years' => $totalYears,
        ];
    }

    private function checkTraining(PersonalDataSheet $pds, QualificationStandard $standard): array
    {
        $hasTrainings = $pds->trainings->isNotEmpty();
        
        return [
            'status' => $hasTrainings || empty($standard->training_requirement),
            'message' => $hasTrainings 
                ? "Has {$pds->trainings->count()} training(s)" 
                : 'No trainings recorded',
            'count' => $pds->trainings->count(),
        ];
    }

    private function checkEligibility(PersonalDataSheet $pds, QualificationStandard $standard): array
    {
        $hasEligibility = $pds->civilServiceEligibilities->isNotEmpty();
        $hasValidEligibility = false;

        if ($hasEligibility) {
            // Check for non-expired eligibilities
            $hasValidEligibility = $pds->civilServiceEligibilities()
                ->where(function($query) {
                    $query->whereNull('validity_date')
                          ->orWhere('validity_date', '>=', now());
                })
                ->exists();
        }

        return [
            'status' => $hasValidEligibility,
            'message' => $hasValidEligibility 
                ? 'Has valid civil service eligibility' 
                : 'No valid eligibility',
        ];
    }

    private function checkDocuments(PersonalDataSheet $pds): array
    {
        $requiredDocTypes = ['birth_certificate', 'transcript_of_records', 'eligibility_cert'];
        $uploadedTypes = $pds->documentUploads->pluck('document_type')->toArray();
        
        $missing = array_diff($requiredDocTypes, $uploadedTypes);
        
        return [
            'status' => empty($missing),
            'message' => empty($missing) 
                ? 'All required documents uploaded' 
                : 'Missing: ' . implode(', ', $missing),
            'missing' => $missing,
        ];
    }

    private function checkPdsComplete(PersonalDataSheet $pds): array
    {
        $completeness = $this->calculateCompleteness($pds);
        
        return [
            'status' => $completeness >= 90,
            'message' => "PDS is {$completeness}% complete",
            'completeness' => $completeness,
        ];
    }

    private function checkApprovalStatus(PersonalDataSheet $pds): array
    {
        $workflow = $pds->approvalWorkflow()->latest()->first();
        $isApproved = $workflow && $workflow->status === 'approved';
        
        return [
            'status' => $isApproved,
            'message' => $isApproved ? 'PDS is approved' : 'PDS pending approval',
        ];
    }

    private function getMissingRequirements(array $checklist): array
    {
        $missing = [];

        foreach ($checklist as $key => $check) {
            if (!$check['status']) {
                $missing[] = [
                    'requirement' => $key,
                    'message' => $check['message'],
                ];
            }
        }

        return $missing;
    }

    private function getHighestEducationLevel(PersonalDataSheet $pds): int
    {
        $levelMap = [
            'Elementary' => 1,
            'Secondary' => 2,
            'Vocational' => 3,
            'College' => 4,
            'Graduate Studies' => 5,
        ];

        $highestLevel = 0;

        foreach ($pds->educationalBackgrounds as $education) {
            $level = $levelMap[$education->level] ?? 0;
            if ($level > $highestLevel) {
                $highestLevel = $level;
            }
        }

        return $highestLevel;
    }

    private function parseEducationRequirement(?string $requirement): int
    {
        if (!$requirement) return 4;

        $requirement = strtolower($requirement);
        
        if (str_contains($requirement, 'graduate') || str_contains($requirement, 'master')) {
            return 5;
        } elseif (str_contains($requirement, 'college') || str_contains($requirement, 'bachelor')) {
            return 4;
        }

        return 4;
    }

    private function calculateCompleteness(PersonalDataSheet $pds): float
    {
        $totalFields = 10;
        $completedFields = 0;

        if (!empty($pds->surname)) $completedFields++;
        if (!empty($pds->first_name)) $completedFields++;
        if ($pds->educationalBackgrounds->isNotEmpty()) $completedFields++;
        if ($pds->workExperiences->isNotEmpty()) $completedFields++;
        if ($pds->civilServiceEligibilities->isNotEmpty()) $completedFields++;
        if ($pds->trainings->isNotEmpty()) $completedFields++;
        if ($pds->documentUploads->isNotEmpty()) $completedFields++;
        if (!empty($pds->date_of_birth)) $completedFields++;
        if (!empty($pds->place_of_birth)) $completedFields++;
        if (!empty($pds->citizenship)) $completedFields++;

        return round(($completedFields / $totalFields) * 100, 2);
    }
}
