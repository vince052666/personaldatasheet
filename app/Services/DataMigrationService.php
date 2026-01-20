<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\ImportBatch;
use App\Models\ImportError;
use App\Models\ImportStaging;
use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DataMigrationService
{
    protected $deduplicationFields = ['email', 'employee_id', 'sss_no', 'tin_no'];

    public function createBatch(Agency $agency, User $user, string $filename, int $totalRecords): ImportBatch
    {
        return ImportBatch::create([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'filename' => $filename,
            'total_records' => $totalRecords,
            'status' => 'pending',
        ]);
    }

    public function stageRecords(ImportBatch $batch, array $records): void
    {
        DB::transaction(function () use ($batch, $records) {
            foreach ($records as $index => $record) {
                $duplicateKey = $this->generateDuplicateKey($record);
                
                ImportStaging::create([
                    'import_batch_id' => $batch->id,
                    'agency_id' => $batch->agency_id,
                    'row_number' => $index + 2, // +2 for header row
                    'raw_data' => $record,
                    'duplicate_key' => $duplicateKey,
                    'status' => 'pending',
                ]);
            }
        });
    }

    public function processStaging(ImportBatch $batch, int $chunkSize = 100): void
    {
        $batch->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        ImportStaging::where('import_batch_id', $batch->id)
            ->where('status', 'pending')
            ->chunk($chunkSize, function ($stagingRecords) use ($batch) {
                foreach ($stagingRecords as $staging) {
                    $this->processStagingRecord($batch, $staging);
                }
            });

        $this->finalizeBatch($batch);
    }

    protected function processStagingRecord(ImportBatch $batch, ImportStaging $staging): void
    {
        try {
            // Step 1: Validate data
            $validationResult = $this->validateRecord($staging->raw_data);
            
            if (!$validationResult['valid']) {
                $this->recordValidationErrors($batch, $staging, $validationResult['errors']);
                $staging->update([
                    'status' => 'error',
                    'validation_errors' => json_encode($validationResult['errors']),
                ]);
                $batch->increment('error_count');
                $batch->increment('processed_records');
                return;
            }

            // Step 2: Map data to PDS structure
            $mappedData = $this->mapDataToPds($staging->raw_data);
            $staging->update(['mapped_data' => $mappedData]);

            // Step 3: Check for duplicates
            $duplicate = $this->checkForDuplicate($batch->agency_id, $staging);
            
            if ($duplicate) {
                $staging->update([
                    'status' => 'duplicate',
                    'matched_pds_id' => $duplicate->id,
                    'notes' => 'Duplicate detected: ' . $duplicate->full_name,
                ]);
                $batch->increment('duplicate_count');
                $batch->increment('processed_records');
                
                $this->logDuplicateError($batch, $staging, $duplicate);
                return;
            }

            // Step 4: Import the record
            $pds = $this->importRecord($batch->agency_id, $mappedData);
            
            $staging->update([
                'status' => 'imported',
                'matched_pds_id' => $pds->id,
            ]);
            
            $batch->increment('success_count');
            $batch->increment('processed_records');

        } catch (\Exception $e) {
            $this->recordSystemError($batch, $staging, $e);
            $staging->update(['status' => 'error']);
            $batch->increment('error_count');
            $batch->increment('processed_records');
        }
    }

    protected function validateRecord(array $data): array
    {
        $rules = [
            'surname' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'date_of_birth' => 'nullable|date',
            'sex' => 'nullable|in:Male,Female',
        ];

        $validator = Validator::make($data, $rules);

        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()->toArray(),
        ];
    }

    protected function mapDataToPds(array $rawData): array
    {
        // Map Excel/CSV columns to PDS fields
        return [
            'surname' => $rawData['surname'] ?? $rawData['last_name'] ?? null,
            'first_name' => $rawData['first_name'] ?? $rawData['firstname'] ?? null,
            'middle_name' => $rawData['middle_name'] ?? $rawData['middlename'] ?? null,
            'date_of_birth' => $rawData['date_of_birth'] ?? $rawData['birthdate'] ?? null,
            'sex' => $rawData['sex'] ?? $rawData['gender'] ?? null,
            'email' => $rawData['email'] ?? null,
            'telephone_no' => $rawData['telephone_no'] ?? $rawData['phone'] ?? null,
            'mobile_no' => $rawData['mobile_no'] ?? $rawData['mobile'] ?? null,
            // Add more field mappings as needed
        ];
    }

    protected function generateDuplicateKey(array $record): ?string
    {
        foreach ($this->deduplicationFields as $field) {
            if (!empty($record[$field])) {
                return strtolower($field . ':' . $record[$field]);
            }
        }
        return null;
    }

    protected function checkForDuplicate(int $agencyId, ImportStaging $staging): ?PersonalDataSheet
    {
        $data = $staging->raw_data;

        // Check by email
        if (!empty($data['email'])) {
            $pds = PersonalDataSheet::where('agency_id', $agencyId)
                ->where('email', $data['email'])
                ->first();
            if ($pds) return $pds;
        }

        // Check by employee_id
        if (!empty($data['employee_id']) || !empty($data['agency_employee_no'])) {
            $empId = $data['employee_id'] ?? $data['agency_employee_no'];
            $pds = PersonalDataSheet::where('agency_id', $agencyId)
                ->where('agency_employee_no', $empId)
                ->first();
            if ($pds) return $pds;
        }

        // Check by SSS number
        if (!empty($data['sss_no'])) {
            $pds = PersonalDataSheet::where('agency_id', $agencyId)
                ->where('sss_no', $data['sss_no'])
                ->first();
            if ($pds) return $pds;
        }

        return null;
    }

    protected function importRecord(int $agencyId, array $data): PersonalDataSheet
    {
        return DB::transaction(function () use ($agencyId, $data) {
            // Create user if email provided
            $userId = null;
            if (!empty($data['email'])) {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'agency_id' => $agencyId,
                        'name' => trim(($data['first_name'] ?? '') . ' ' . ($data['surname'] ?? '')),
                        'password' => bcrypt('TempPassword123!'), // Temporary password
                    ]
                );
                $userId = $user->id;
            }

            $data['agency_id'] = $agencyId;
            $data['user_id'] = $userId;

            return PersonalDataSheet::create($data);
        });
    }

    protected function recordValidationErrors(ImportBatch $batch, ImportStaging $staging, array $errors): void
    {
        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                ImportError::create([
                    'import_batch_id' => $batch->id,
                    'import_staging_id' => $staging->id,
                    'agency_id' => $batch->agency_id,
                    'row_number' => $staging->row_number,
                    'error_type' => 'validation',
                    'field' => $field,
                    'error_message' => $message,
                    'severity' => 'error',
                ]);
            }
        }
    }

    protected function logDuplicateError(ImportBatch $batch, ImportStaging $staging, PersonalDataSheet $duplicate): void
    {
        ImportError::create([
            'import_batch_id' => $batch->id,
            'import_staging_id' => $staging->id,
            'agency_id' => $batch->agency_id,
            'row_number' => $staging->row_number,
            'error_type' => 'duplicate',
            'error_message' => 'Record matches existing PDS: ' . $duplicate->full_name,
            'error_context' => [
                'existing_pds_id' => $duplicate->id,
                'duplicate_key' => $staging->duplicate_key,
            ],
            'severity' => 'warning',
        ]);
    }

    protected function recordSystemError(ImportBatch $batch, ImportStaging $staging, \Exception $e): void
    {
        ImportError::create([
            'import_batch_id' => $batch->id,
            'import_staging_id' => $staging->id,
            'agency_id' => $batch->agency_id,
            'row_number' => $staging->row_number,
            'error_type' => 'system',
            'error_message' => $e->getMessage(),
            'error_context' => [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ],
            'severity' => 'critical',
        ]);
    }

    protected function finalizeBatch(ImportBatch $batch): void
    {
        $batch->refresh();
        
        $summary = sprintf(
            "Import completed: %d total, %d success, %d errors, %d duplicates",
            $batch->total_records,
            $batch->success_count,
            $batch->error_count,
            $batch->duplicate_count
        );

        $status = $batch->error_count === $batch->total_records ? 'failed' : 
                  ($batch->error_count > 0 ? 'partial' : 'completed');

        $batch->update([
            'status' => $status,
            'summary' => $summary,
            'completed_at' => now(),
        ]);
    }

    public function resumeBatch(ImportBatch $batch, int $chunkSize = 100): void
    {
        if ($batch->isComplete()) {
            throw new \Exception('Batch is already complete');
        }

        $this->processStaging($batch, $chunkSize);
    }

    public function getBatchSummary(ImportBatch $batch): array
    {
        return [
            'batch_id' => $batch->id,
            'filename' => $batch->filename,
            'status' => $batch->status,
            'progress' => $batch->getProgressPercentage(),
            'total_records' => $batch->total_records,
            'processed_records' => $batch->processed_records,
            'success_count' => $batch->success_count,
            'error_count' => $batch->error_count,
            'duplicate_count' => $batch->duplicate_count,
            'started_at' => $batch->started_at,
            'completed_at' => $batch->completed_at,
            'errors_by_type' => $batch->errors()
                ->selectRaw('error_type, COUNT(*) as count')
                ->groupBy('error_type')
                ->pluck('count', 'error_type')
                ->toArray(),
        ];
    }
}
