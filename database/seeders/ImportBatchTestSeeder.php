<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Seeder;

class ImportBatchTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding test import batches...');

        $agencies = Agency::where('code', '!=', 'SUPER')->get();

        foreach ($agencies as $agency) {
            $user = $agency->users()->first();
            
            if (!$user) {
                continue;
            }

            // Create completed import batch
            ImportBatch::create([
                'agency_id' => $agency->id,
                'user_id' => $user->id,
                'filename' => 'sample_import_' . $agency->code . '.csv',
                'status' => 'completed',
                'total_records' => 100,
                'processed_records' => 100,
                'success_count' => 85,
                'error_count' => 10,
                'duplicate_count' => 5,
                'summary' => 'Import completed: 100 total, 85 success, 10 errors, 5 duplicates',
                'started_at' => now()->subDays(5),
                'completed_at' => now()->subDays(5)->addHours(2),
            ]);

            // Create partial import batch
            ImportBatch::create([
                'agency_id' => $agency->id,
                'user_id' => $user->id,
                'filename' => 'partial_import_' . $agency->code . '.csv',
                'status' => 'partial',
                'total_records' => 200,
                'processed_records' => 200,
                'success_count' => 150,
                'error_count' => 45,
                'duplicate_count' => 5,
                'summary' => 'Import completed with errors: 200 total, 150 success, 45 errors, 5 duplicates',
                'started_at' => now()->subDays(2),
                'completed_at' => now()->subDays(2)->addHours(3),
            ]);

            $this->command->info("  - Created test import batches for {$agency->code}");
        }

        $this->command->info('Import batch test seeding completed!');
    }
}
