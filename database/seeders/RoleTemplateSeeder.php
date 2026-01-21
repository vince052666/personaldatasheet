<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RoleTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->createHREncoderRole();
        $this->createHRReviewerRole();
        $this->createSelectionBoardRole();
        $this->createAgencyAdminRole();
    }

    protected function createHREncoderRole(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'hr_encoder'],
            ['description' => 'HR personnel who encode PDS records']
        );

        $permissions = [
            'view-pds',
            'create-pds',
            'edit-pds',
            'submit-pds-for-review',
            'upload-documents',
            'view-own-submissions',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->givePermissionTo($permission);
        }

        $this->command->info("Created hr_encoder role with " . count($permissions) . " permissions");
    }

    protected function createHRReviewerRole(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'hr_reviewer'],
            ['description' => 'HR personnel who review and validate PDS records']
        );

        $permissions = [
            'view-pds',
            'review-pds',
            'approve-pds',
            'reject-pds',
            'request-corrections',
            'view-all-submissions',
            'export-pds',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->givePermissionTo($permission);
        }

        $this->command->info("Created hr_reviewer role with " . count($permissions) . " permissions");
    }

    protected function createSelectionBoardRole(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'selection_board'],
            ['description' => 'Selection board members who review PDS for recruitment']
        );

        $permissions = [
            'view-pds',
            'view-approved-pds-only',
            'export-pds',
            'create-recruitment',
            'view-recruitment',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->givePermissionTo($permission);
        }

        $this->command->info("Created selection_board role with " . count($permissions) . " permissions");
    }

    protected function createAgencyAdminRole(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'agency_admin'],
            ['description' => 'Agency administrator with full access']
        );

        $permissions = [
            'view-pds',
            'create-pds',
            'edit-pds',
            'delete-pds',
            'approve-pds',
            'reject-pds',
            'manage-users',
            'manage-roles',
            'manage-permissions',
            'manage-agency-settings',
            'export-pds',
            'import-pds',
            'view-audit-logs',
            'manage-dpa-requests',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->givePermissionTo($permission);
        }

        $this->command->info("Created agency_admin role with " . count($permissions) . " permissions");
    }
}
