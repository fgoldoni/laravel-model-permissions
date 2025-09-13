<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Contracts;

interface AuthorizationRepositoryInterface
{
    public function isAvailable(): bool;

    public function permissionExists(string $name, string $guardName): bool;

    public function ensurePermission(string $name, string $guardName): bool;

    public function ensureRole(string $roleName, string $guardName): void;

    public function syncRolePermissions(string $roleName, string $guardName, array $permissionNames): void;

    public function attachRolePermissions(string $roleName, string $guardName, array $permissionNames): void;

    public function clearCache(): void;
}
