<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            ['name' => 'View PDS', 'slug' => 'view-pds', 'description' => 'View personal data sheets'],
            ['name' => 'Create PDS', 'slug' => 'create-pds', 'description' => 'Create new personal data sheets'],
            ['name' => 'Edit PDS', 'slug' => 'edit-pds', 'description' => 'Edit personal data sheets'],
            ['name' => 'Delete PDS', 'slug' => 'delete-pds', 'description' => 'Delete personal data sheets'],
            ['name' => 'View All PDS', 'slug' => 'view-all-pds', 'description' => 'View all employee personal data sheets'],
            ['name' => 'Approve PDS', 'slug' => 'approve-pds', 'description' => 'Approve personal data sheets'],
            ['name' => 'Export PDS', 'slug' => 'export-pds', 'description' => 'Export personal data sheets to PDF/Excel'],
            ['name' => 'Import PDS', 'slug' => 'import-pds', 'description' => 'Import personal data sheets from Excel'],
            ['name' => 'Manage Users', 'slug' => 'manage-users', 'description' => 'Manage system users'],
            ['name' => 'Manage Roles', 'slug' => 'manage-roles', 'description' => 'Manage user roles and permissions'],
            ['name' => 'View Audit Logs', 'slug' => 'view-audit-logs', 'description' => 'View system audit logs'],
            ['name' => 'View Reports', 'slug' => 'view-reports', 'description' => 'View PDS reports'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        // Create Roles
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrator', 'description' => 'Full system access']
        );

        $hrRole = Role::firstOrCreate(
            ['slug' => 'hr'],
            ['name' => 'HR Manager', 'description' => 'Human Resources Manager with access to all employee PDS']
        );

        $employeeRole = Role::firstOrCreate(
            ['slug' => 'employee'],
            ['name' => 'Employee', 'description' => 'Standard employee with access to own PDS']
        );

        // Assign all permissions to Admin
        $adminRole->permissions()->sync(Permission::all());

        // Assign HR permissions
        $hrPermissions = Permission::whereIn('slug', [
            'view-pds',
            'create-pds',
            'edit-pds',
            'view-all-pds',
            'approve-pds',
            'export-pds',
            'import-pds',
            'view-audit-logs',
            'view-reports',
        ])->pluck('id');
        $hrRole->permissions()->sync($hrPermissions);

        // Assign Employee permissions
        $employeePermissions = Permission::whereIn('slug', [
            'view-pds',
            'create-pds',
            'edit-pds',
            'export-pds',
        ])->pluck('id');
        $employeeRole->permissions()->sync($employeePermissions);
    }
}
