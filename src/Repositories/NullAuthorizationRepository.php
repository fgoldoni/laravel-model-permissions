<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Repositories;

use Goldoni\ModelPermissions\Contracts\AuthorizationRepositoryInterface;

class NullAuthorizationRepository implements AuthorizationRepositoryInterface
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function permissionExists(string $name, string $guardName): bool
    {
        return false;
    }

    public function ensurePermission(string $name, string $guardName): bool
    {
        return false;
    }

    public function ensureRole(string $roleName, string $guardName): void
    {
    }

    public function syncRolePermissions(string $roleName, string $guardName, array $permissionNames): void
    {
    }

    public function attachRolePermissions(string $roleName, string $guardName, array $permissionNames): void
    {
    }

    public function clearCache(): void
    {
    }
}
