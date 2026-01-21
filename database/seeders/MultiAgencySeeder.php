<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MultiAgencySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding multi-agency data...');

        // Create government agencies
        $agencies = [
            [
                'code' => 'SUPER',
                'name' => 'System Administration',
                'description' => 'System-wide administration and oversight agency',
                'contact_email' => 'admin@pds.gov.ph',
                'contact_phone' => '+63-2-0000-0000',
                'is_active' => true,
            ],
            [
                'code' => 'DILG',
                'name' => 'Department of the Interior and Local Government',
                'description' => 'Promotes peace and order, ensures public safety, and strengthens local government capability',
                'contact_email' => 'dilg@gov.ph',
                'contact_phone' => '+63-2-8925-0332',
                'is_active' => true,
            ],
            [
                'code' => 'DOH',
                'name' => 'Department of Health',
                'description' => 'Ensures equitable, sustainable and quality health for all Filipinos',
                'contact_email' => 'doh@gov.ph',
                'contact_phone' => '+63-2-8651-7800',
                'is_active' => true,
            ],
            [
                'code' => 'DepEd',
                'name' => 'Department of Education',
                'description' => 'Provides quality basic education that is accessible to all',
                'contact_email' => 'deped@gov.ph',
                'contact_phone' => '+63-2-8636-6868',
                'is_active' => true,
            ],
            [
                'code' => 'DA',
                'name' => 'Department of Agriculture',
                'description' => 'Promotes agricultural development',
                'contact_email' => 'da@gov.ph',
                'contact_phone' => '+63-2-8273-2474',
                'is_active' => true,
            ],
            [
                'code' => 'DSWD',
                'name' => 'Department of Social Welfare and Development',
                'description' => 'Provides social protection and promotes social welfare',
                'contact_email' => 'dswd@gov.ph',
                'contact_phone' => '+63-2-8931-8101',
                'is_active' => true,
            ],
        ];

        foreach ($agencies as $agencyData) {
            $agency = Agency::create($agencyData);
            $this->command->info("Created agency: {$agency->name}");
            
            // Create agency admin user
            $this->createAgencyAdmin($agency);
        }

        // Create super admin
        $this->createSuperAdmin();

        $this->command->info('Multi-agency seeding completed!');
    }

    protected function createAgencyAdmin(Agency $agency): void
    {
        $adminRole = Role::firstOrCreate(
            ['slug' => 'agency-admin'],
            ['name' => 'Agency Administrator']
        );

        // Generate secure random password
        $randomPassword = bin2hex(random_bytes(8)); // 16 character random password

        $user = User::create([
            'agency_id' => $agency->id,
            'name' => $agency->code . ' Administrator',
            'email' => strtolower($agency->code) . '-admin@gov.ph',
            'password' => Hash::make($randomPassword),
            'department' => 'Administration',
            'position' => 'System Administrator',
            'employee_id' => $agency->code . '-ADMIN-001',
            'is_active' => true,
        ]);

        $user->roles()->attach($adminRole);

        $this->command->info("  - Created admin user: {$user->email}");
        $this->command->warn("    PASSWORD: {$randomPassword} (save this securely!)");
    }

    protected function createSuperAdmin(): void
    {
        $superAgency = Agency::where('code', 'SUPER')->first();
        
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Administrator']
        );

        // Generate secure random password
        $randomPassword = bin2hex(random_bytes(12)); // 24 character random password

        $superAdmin = User::create([
            'agency_id' => $superAgency->id,
            'name' => 'Super Administrator',
            'email' => 'superadmin@pds.gov.ph',
            'password' => Hash::make($randomPassword),
            'department' => 'IT Department',
            'position' => 'System Administrator',
            'employee_id' => 'SUPER-ADMIN-001',
            'is_active' => true,
        ]);

        $superAdmin->roles()->attach($superAdminRole);

        $this->command->info("Created super admin: {$superAdmin->email}");
        $this->command->warn("PASSWORD: {$randomPassword} (save this securely!)");
    }
}
