<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Repositories;

use Goldoni\ModelPermissions\Contracts\AuthorizationRepositoryInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SpatieAuthorizationRepository implements AuthorizationRepositoryInterface
{
    public function isAvailable(): bool
    {
        return class_exists(Permission::class) && class_exists(Role::class) && class_exists(PermissionRegistrar::class);
    }

    public function permissionExists(string $name, string $guardName): bool
    {
        return Permission::query()->where('name', $name)->where('guard_name', $guardName)->exists();
    }

    public function ensurePermission(string $name, string $guardName): bool
    {
        if ($this->permissionExists($name, $guardName)) {
            return false;
        }

        Permission::findOrCreate($name, $guardName);
        return true;
    }

    public function ensureRole(string $roleName, string $guardName): void
    {
        Role::findOrCreate($roleName, $guardName);
    }

    public function syncRolePermissions(string $roleName, string $guardName, array $permissionNames): void
    {
        $role = Role::findByName($roleName, $guardName);
        $role->syncPermissions($permissionNames);
    }

    public function attachRolePermissions(string $roleName, string $guardName, array $permissionNames): void
    {
        $role = Role::findByName($roleName, $guardName);
        $role->givePermissionTo($permissionNames);
    }

    public function clearCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
