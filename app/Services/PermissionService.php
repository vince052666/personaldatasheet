<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function hasPermission(User $user, string $permission): bool
    {
        return Cache::remember(
            "user.{$user->id}.permissions",
            self::CACHE_TTL,
            fn() => $this->getUserPermissions($user)
        )->contains($permission);
    }

    public function getUserPermissions(User $user): \Illuminate\Support\Collection
    {
        return $user->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('name')
            ->unique();
    }

    public function grantPermissionToRole(Role $role, string $permissionName): void
    {
        $permission = Permission::firstOrCreate(['name' => $permissionName]);
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $this->clearCache();
    }

    public function revokePermissionFromRole(Role $role, string $permissionName): void
    {
        $permission = Permission::where('name', $permissionName)->first();
        if ($permission) {
            $role->permissions()->detach($permission->id);
            $this->clearCache();
        }
    }

    public function createPermissionGroup(string $resource, array $actions = ['view', 'create', 'edit', 'delete', 'approve']): array
    {
        $permissions = [];
        
        foreach ($actions as $action) {
            $permissionName = "{$resource}.{$action}";
            $permissions[] = Permission::firstOrCreate([
                'name' => $permissionName,
                'description' => ucfirst($action) . ' ' . str_replace('_', ' ', $resource),
            ]);
        }

        return $permissions;
    }

    public function syncRolePermissions(Role $role, array $permissionNames): void
    {
        $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
        $role->permissions()->sync($permissionIds);
        $this->clearCache();
    }

    public function can(User $user, string $action, string $resource): bool
    {
        return $this->hasPermission($user, "{$resource}.{$action}");
    }

    public function setupDefaultPermissions(): void
    {
        $resources = [
            'pds' => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
            'users' => ['view', 'create', 'edit', 'delete'],
            'roles' => ['view', 'create', 'edit', 'delete'],
            'permissions' => ['view', 'assign'],
            'audit_logs' => ['view', 'export'],
            'reports' => ['view', 'generate', 'export'],
            'approvals' => ['view', 'approve', 'reject', 'reassign'],
            'recruitment' => ['view', 'rank', 'assess'],
        ];

        foreach ($resources as $resource => $actions) {
            $this->createPermissionGroup($resource, $actions);
        }
    }

    private function clearCache(): void
    {
        Cache::flush(); // Or use tags for more granular clearing
    }
}
