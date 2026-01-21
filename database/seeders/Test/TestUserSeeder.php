<?php

namespace Database\Seeders\Test;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        $agency = Agency::first() ?? Agency::factory()->create();

        $roles = [
            'hr_encoder' => 'Test HR Encoder',
            'hr_reviewer' => 'Test HR Reviewer',
            'selection_board' => 'Test Selection Board Member',
            'agency_admin' => 'Test Agency Admin',
        ];

        foreach ($roles as $role => $name) {
            $user = User::create([
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@test.gov.ph',
                'password' => bcrypt('password'),
                'agency_id' => $agency->id,
            ]);

            $user->assignRole($role);

            $this->command->info("Created test user: {$name} ({$role})");
        }
    }
}
