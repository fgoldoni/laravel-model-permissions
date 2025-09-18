<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Repositories;

use Goldoni\ModelPermissions\Contracts\AuthorizationRepositoryInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class SpatieAuthorizationRepository implements AuthorizationRepositoryInterface
{
    public function isAvailable(): bool
    {
        return class_exists(Role::class) && class_exists(Permission::class);
    }

    public function clearCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function ensureRole(string $roleName, string $guardName): void
    {
        Role::findOrCreate($roleName, $guardName);
    }

    public function permissionExists(string $permissionName, string $guardName): bool
    {
        return Permission::query()
            ->where('name', $permissionName)
            ->where('guard_name', $guardName)
            ->exists();
    }

    /**
     * @return bool True si la permission vient d'être créée, false si elle existait déjà
     */
    public function ensurePermission(string $permissionName, string $guardName): bool
    {
        $exists = $this->permissionExists($permissionName, $guardName);

        if ($exists) {
            return false;
        }

        Permission::findOrCreate($permissionName, $guardName);

        return true;
    }

    /**
     * @param array<int, string> $permissionNames
     */
    public function syncRolePermissions(string $roleName, string $guardName, array $permissionNames): void
    {

        $role = Role::findByName($roleName, $guardName);

        $permissions = Permission::query()
            ->where('guard_name', $guardName)
            ->whereIn('name', $permissionNames)
            ->get();



        $role->syncPermissions($permissions);
    }

    /**
     * @param array<int, string> $permissionNames
     */
    public function attachRolePermissions(string $roleName, string $guardName, array $permissionNames): void
    {
        $role = Role::findByName($roleName, $guardName);

        $permissions = Permission::query()
            ->where('guard_name', $guardName)
            ->whereIn('name', $permissionNames)
            ->get();

        $role->givePermissionTo($permissions);
    }
}
