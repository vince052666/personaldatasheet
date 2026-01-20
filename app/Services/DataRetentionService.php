<?php

namespace App\Services;

use App\Models\DataRetentionPolicy;
use App\Models\ArchivedRecord;
use App\Models\PersonalDataSheet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DataRetentionService
{
    public function applyRetentionPolicies(): array
    {
        $results = [];
        $policies = DataRetentionPolicy::all();

        foreach ($policies as $policy) {
            $count = $this->applyPolicy($policy);
            $results[$policy->resource_type] = $count;
        }

        return $results;
    }

    public function applyPolicy(DataRetentionPolicy $policy): int
    {
        $count = 0;
        $cutoffDate = now()->subYears($policy->retention_years);

        switch ($policy->resource_type) {
            case 'pds':
                $count = $this->archiveOldPds($cutoffDate, $policy);
                break;
            case 'audit_log':
                // Archive old audit logs
                break;
            case 'document':
                // Archive old documents
                break;
        }

        return $count;
    }

    public function archiveRecord(Model $model, ?string $reason = null): ArchivedRecord
    {
        $policy = DataRetentionPolicy::where('resource_type', $this->getResourceType($model))->first();

        $deleteAfter = $policy && $policy->auto_delete 
            ? now()->addYears($policy->retention_years) 
            : null;

        $archived = ArchivedRecord::create([
            'archivable_type' => get_class($model),
            'archivable_id' => $model->id,
            'archived_at' => now(),
            'delete_after' => $deleteAfter,
            'archive_reason' => $reason,
            'archived_by' => auth()->id(),
            'original_data' => $model->toArray(),
        ]);

        if ($policy?->auto_delete) {
            $model->delete(); // Soft delete
        }

        return $archived;
    }

    public function restoreRecord(ArchivedRecord $archived): ?Model
    {
        $modelClass = $archived->archivable_type;
        
        if (!class_exists($modelClass)) {
            return null;
        }

        return DB::transaction(function () use ($archived, $modelClass) {
            // If model was soft deleted, restore it
            $model = $modelClass::withTrashed()->find($archived->archivable_id);
            
            if ($model && $model->trashed()) {
                $model->restore();
            }

            return $model;
        });
    }

    public function deleteExpiredArchives(): int
    {
        $expiredArchives = ArchivedRecord::where('delete_after', '<=', now())->get();
        $count = 0;

        foreach ($expiredArchives as $archived) {
            $modelClass = $archived->archivable_type;
            $model = $modelClass::withTrashed()->find($archived->archivable_id);
            
            if ($model) {
                $model->forceDelete();
                $count++;
            }
            
            $archived->delete();
        }

        return $count;
    }

    private function archiveOldPds(\DateTime $cutoffDate, DataRetentionPolicy $policy): int
    {
        $oldPds = PersonalDataSheet::where('created_at', '<', $cutoffDate)
            ->whereDoesntHave('archivedRecord')
            ->get();

        foreach ($oldPds as $pds) {
            $this->archiveRecord($pds, "Automatic archival per retention policy ({$policy->retention_years} years)");
        }

        return $oldPds->count();
    }

    private function getResourceType(Model $model): string
    {
        $className = class_basename($model);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }

    public function createPolicy(string $resourceType, int $retentionYears, bool $autoArchive = true, bool $autoDelete = false): DataRetentionPolicy
    {
        return DataRetentionPolicy::updateOrCreate(
            ['resource_type' => $resourceType],
            [
                'retention_years' => $retentionYears,
                'auto_archive' => $autoArchive,
                'auto_delete' => $autoDelete,
            ]
        );
    }
}
