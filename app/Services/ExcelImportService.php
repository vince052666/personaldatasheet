<?php

namespace App\Services;

use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExcelImportService
{
    public function __construct(
        protected PDSService $pdsService
    ) {}

    public function importFromArray(array $rows): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                try {
                    $this->importRow($row);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $index + 1,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    protected function importRow(array $row): PersonalDataSheet
    {
        // Find or create user
        $user = User::where('email', $row['email'])->first();
        
        if (!$user) {
            $user = User::create([
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => bcrypt('password'), // Default password
                'employee_id' => $row['employee_id'] ?? null,
                'department' => $row['department'] ?? null,
                'position' => $row['position'] ?? null,
            ]);
        }

        // Prepare PDS data
        $pdsData = [
            'surname' => $row['surname'],
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'] ?? null,
            'name_extension' => $row['name_extension'] ?? null,
            'date_of_birth' => $row['date_of_birth'],
            'place_of_birth' => $row['place_of_birth'],
            'sex' => $row['sex'],
            'civil_status' => $row['civil_status'],
            'citizenship' => $row['citizenship'],
            'residential_city' => $row['residential_city'],
            'residential_province' => $row['residential_province'],
            'permanent_city' => $row['permanent_city'],
            'permanent_province' => $row['permanent_province'],
            'mobile_no' => $row['mobile_no'] ?? null,
            'email_address' => $row['email'] ?? null,
        ];

        return $this->pdsService->createPDS($user, $pdsData);
    }

    public function exportTemplate(): array
    {
        return [
            'headers' => [
                'email',
                'name',
                'employee_id',
                'department',
                'position',
                'surname',
                'first_name',
                'middle_name',
                'name_extension',
                'date_of_birth',
                'place_of_birth',
                'sex',
                'civil_status',
                'citizenship',
                'residential_city',
                'residential_province',
                'permanent_city',
                'permanent_province',
                'mobile_no',
            ],
        ];
    }
}
