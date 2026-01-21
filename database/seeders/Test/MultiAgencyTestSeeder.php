<?php

namespace Database\Seeders\Test;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Seeder;

class MultiAgencyTestSeeder extends Seeder
{
    public function run(): void
    {
        $agencies = [
            [
                'name' => 'Department of Education',
                'code' => 'DEPED',
                'address' => 'DepEd Complex, Meralco Avenue, Pasig City',
                'contact_number' => '(02) 8631-4087',
            ],
            [
                'name' => 'Department of Health',
                'code' => 'DOH',
                'address' => 'San Lazaro Compound, Rizal Avenue, Sta. Cruz, Manila',
                'contact_number' => '(02) 8651-7800',
            ],
            [
                'name' => 'Department of Social Welfare and Development',
                'code' => 'DSWD',
                'address' => 'DSWD Building, Batasan Pambansa Complex, Constitution Hills, Quezon City',
                'contact_number' => '(02) 8931-8101',
            ],
            [
                'name' => 'Department of Public Works and Highways',
                'code' => 'DPWH',
                'address' => 'DPWH Building, Bonifacio Drive, Port Area, Manila',
                'contact_number' => '(02) 8304-3000',
            ],
            [
                'name' => 'Department of Agriculture',
                'code' => 'DA',
                'address' => 'Elliptical Road, Diliman, Quezon City',
                'contact_number' => '(02) 8928-8741',
            ],
            [
                'name' => 'Civil Service Commission',
                'code' => 'CSC',
                'address' => 'CSC Building, Constitutional Hills, Batasan Pambansa, Quezon City',
                'contact_number' => '(02) 8931-8181',
            ],
            [
                'name' => 'Commission on Audit',
                'code' => 'COA',
                'address' => 'Commonwealth Avenue, Quezon City',
                'contact_number' => '(02) 8931-1600',
            ],
        ];

        foreach ($agencies as $agencyData) {
            $agency = Agency::create($agencyData);

            // Create admin user for each agency
            $admin = User::create([
                'name' => "{$agencyData['code']} Admin",
                'email' => strtolower($agencyData['code']) . '.admin@gov.ph',
                'password' => bcrypt('password'),
                'agency_id' => $agency->id,
            ]);
            $admin->assignRole('agency_admin');

            // Create HR encoder
            $encoder = User::create([
                'name' => "{$agencyData['code']} HR Encoder",
                'email' => strtolower($agencyData['code']) . '.encoder@gov.ph',
                'password' => bcrypt('password'),
                'agency_id' => $agency->id,
            ]);
            $encoder->assignRole('hr_encoder');

            // Create HR reviewer
            $reviewer = User::create([
                'name' => "{$agencyData['code']} HR Reviewer",
                'email' => strtolower($agencyData['code']) . '.reviewer@gov.ph',
                'password' => bcrypt('password'),
                'agency_id' => $agency->id,
            ]);
            $reviewer->assignRole('hr_reviewer');
        }

        $this->command->info('Created ' . count($agencies) . ' test agencies with users');
    }
}
