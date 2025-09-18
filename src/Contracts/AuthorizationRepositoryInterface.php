<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Contracts;

interface AuthorizationRepositoryInterface
{
    public function isAvailable(): bool;

    public function clearCache(): void;

    public function ensureRole(string $roleName, string $guardName): void;

    public function permissionExists(string $permissionName, string $guardName): bool;

    public function ensurePermission(string $permissionName, string $guardName): bool;

    /** @param array<int, string> $permissionNames */
    public function syncRolePermissions(string $roleName, string $guardName, array $permissionNames): void;

    /** @param array<int, string> $permissionNames */
    public function attachRolePermissions(string $roleName, string $guardName, array $permissionNames): void;
}
