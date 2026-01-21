<?php

namespace Database\Seeders\Test;

use App\Models\Agency;
use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Database\Seeder;

class RealisticPDSSeeder extends Seeder
{
    public function run(): void
    {
        $agencies = Agency::all();

        if ($agencies->isEmpty()) {
            $this->command->error('No agencies found. Run MultiAgencyTestSeeder first.');
            return;
        }

        $totalRecords = 0;

        foreach ($agencies as $agency) {
            $recordCount = rand(10, 25);

            PersonalDataSheet::factory()
                ->count($recordCount)
                ->create([
                    'agency_id' => $agency->id,
                ]);

            $totalRecords += $recordCount;
        }

        $this->command->info("Created {$totalRecords} realistic PDS records across {$agencies->count()} agencies");
    }
}
