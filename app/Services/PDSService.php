<?php

namespace App\Services;

use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PDSService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected VersioningService $versioningService
    ) {}

    public function createPDS(array $data, User $user): PersonalDataSheet
    {
        return DB::transaction(function () use ($user, $data) {
            // Set all current PDS to not current
            $user->personalDataSheets()->update(['is_current' => false]);

            // Create new PDS
            $pds = $user->personalDataSheets()->create([
                ...$data,
                'is_current' => true,
                'version' => 1,
            ]);

            // Create related records
            $this->createRelatedRecords($pds, $data);

            // Create initial version
            $this->versioningService->createVersion($pds, 'Initial creation');

            // Audit log
            $this->auditLogService->log($pds, 'created', null, $pds->toArray());

            return $pds->load([
                'workExperiences',
                'educationalBackgrounds',
                'civilServiceEligibilities',
                'trainings',
                'voluntaryWorks',
                'otherInformation',
            ]);
        });
    }

    public function updatePDS(PersonalDataSheet $pds, array $data, ?User $user = null): PersonalDataSheet
    {
        return DB::transaction(function () use ($pds, $data) {
            $oldData = $pds->toArray();

            // Update PDS
            $pds->update($data);
            $pds->increment('version');

            // Update related records
            $this->updateRelatedRecords($pds, $data);

            // Create new version
            $changeDescription = $data['change_description'] ?? 'Updated PDS';
            $this->versioningService->createVersion($pds, $changeDescription);

            // Audit log
            $this->auditLogService->log($pds, 'updated', $oldData, $pds->fresh()->toArray());

            return $pds->load([
                'workExperiences',
                'educationalBackgrounds',
                'civilServiceEligibilities',
                'trainings',
                'voluntaryWorks',
                'otherInformation',
            ]);
        });
    }

    public function deletePDS(PersonalDataSheet $pds): bool
    {
        return DB::transaction(function () use ($pds) {
            $oldData = $pds->toArray();

            $result = $pds->delete();

            // Audit log
            $this->auditLogService->log($pds, 'deleted', $oldData, null);

            return $result;
        });
    }

    protected function createRelatedRecords(PersonalDataSheet $pds, array $data): void
    {
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

    protected function updateRelatedRecords(PersonalDataSheet $pds, array $data): void
    {
        if (isset($data['work_experiences'])) {
            $pds->workExperiences()->delete();
            foreach ($data['work_experiences'] as $experience) {
                $pds->workExperiences()->create($experience);
            }
        }

        if (isset($data['educational_backgrounds'])) {
            $pds->educationalBackgrounds()->delete();
            foreach ($data['educational_backgrounds'] as $education) {
                $pds->educationalBackgrounds()->create($education);
            }
        }

        if (isset($data['civil_service_eligibilities'])) {
            $pds->civilServiceEligibilities()->delete();
            foreach ($data['civil_service_eligibilities'] as $eligibility) {
                $pds->civilServiceEligibilities()->create($eligibility);
            }
        }

        if (isset($data['trainings'])) {
            $pds->trainings()->delete();
            foreach ($data['trainings'] as $training) {
                $pds->trainings()->create($training);
            }
        }

        if (isset($data['voluntary_works'])) {
            $pds->voluntaryWorks()->delete();
            foreach ($data['voluntary_works'] as $work) {
                $pds->voluntaryWorks()->create($work);
            }
        }

        if (isset($data['other_information'])) {
            $pds->otherInformation()->delete();
            foreach ($data['other_information'] as $info) {
                $pds->otherInformation()->create($info);
            }
        }
    }
}
