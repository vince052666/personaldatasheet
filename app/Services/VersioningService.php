<?php

namespace App\Services;

use App\Models\PersonalDataSheet;
use App\Models\PdsVersion;
use Illuminate\Support\Facades\Auth;

class VersioningService
{
    public function createVersion(
        PersonalDataSheet $pds,
        string $changeDescription = null
    ): PdsVersion {
        $versionData = [
            'personal_data_sheet' => $pds->toArray(),
            'work_experiences' => $pds->workExperiences->toArray(),
            'educational_backgrounds' => $pds->educationalBackgrounds->toArray(),
            'civil_service_eligibilities' => $pds->civilServiceEligibilities->toArray(),
            'trainings' => $pds->trainings->toArray(),
            'voluntary_works' => $pds->voluntaryWorks->toArray(),
            'other_information' => $pds->otherInformation->toArray(),
        ];

        return PdsVersion::create([
            'personal_data_sheet_id' => $pds->id,
            'version_number' => $pds->version,
            'data' => $versionData,
            'change_description' => $changeDescription,
            'created_by' => Auth::id(),
        ]);
    }

    public function getVersionHistory(PersonalDataSheet $pds)
    {
        return $pds->versions()
            ->with('createdBy')
            ->orderBy('version_number', 'desc')
            ->get();
    }

    public function restoreVersion(PersonalDataSheet $pds, int $versionNumber): PersonalDataSheet
    {
        $version = $pds->versions()
            ->where('version_number', $versionNumber)
            ->firstOrFail();

        $data = $version->data;

        // Restore main PDS data
        $pds->update($data['personal_data_sheet']);

        // Restore related records
        $this->restoreRelatedRecords($pds, $data);

        return $pds->fresh();
    }

    protected function restoreRelatedRecords(PersonalDataSheet $pds, array $data): void
    {
        // Clear existing records
        $pds->workExperiences()->delete();
        $pds->educationalBackgrounds()->delete();
        $pds->civilServiceEligibilities()->delete();
        $pds->trainings()->delete();
        $pds->voluntaryWorks()->delete();
        $pds->otherInformation()->delete();

        // Restore from version
        if (isset($data['work_experiences'])) {
            foreach ($data['work_experiences'] as $experience) {
                $pds->workExperiences()->create($experience);
            }
        }

        if (isset($data['educational_backgrounds'])) {
            foreach ($data['educational_backgrounds'] as $education) {
                $pds->educationalBackgrounds()->create($education);
            }
        }

        if (isset($data['civil_service_eligibilities'])) {
            foreach ($data['civil_service_eligibilities'] as $eligibility) {
                $pds->civilServiceEligibilities()->create($eligibility);
            }
        }

        if (isset($data['trainings'])) {
            foreach ($data['trainings'] as $training) {
                $pds->trainings()->create($training);
            }
        }

        if (isset($data['voluntary_works'])) {
            foreach ($data['voluntary_works'] as $work) {
                $pds->voluntaryWorks()->create($work);
            }
        }

        if (isset($data['other_information'])) {
            foreach ($data['other_information'] as $info) {
                $pds->otherInformation()->create($info);
            }
        }
    }
}
