<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Services;

use Goldoni\ModelPermissions\Contracts\AuthorizationRepositoryInterface;
use Goldoni\ModelPermissions\Contracts\PermissionNamerInterface;

class RoleAssignmentService
{
    public function __construct(private readonly AuthorizationRepositoryInterface $authorizationRepository, private readonly PermissionNamerInterface $permissionNamer)
    {
    }

    public function buildPermissionNamesForRole(
        string $roleName,
        array $models,
        array $abilities,
        array $roleAbilityMap,
        array $roleModelAbilityMap,
        array $globalPermissions,
        array $roleGlobalPermissionsMap
    ): array {
        $names   = [];
        $default = $roleAbilityMap[$roleName] ?? [];

        foreach ($models as $model) {
            $specific = $roleModelAbilityMap[$roleName][$model] ?? null;

            if ($default === ['*'] || $specific === ['*']) {
                $effective = $abilities;
            } elseif (is_array($specific) && $specific !== []) {
                $effective = $specific;
            } else {
                $effective = $default;
            }

            foreach ($effective as $ability) {
                $names[] = $this->permissionNamer->buildForModel((string) $model, (string) $ability);
            }
        }

        $globalsForRole = $roleGlobalPermissionsMap[$roleName] ?? [];

        if ($globalsForRole === ['*']) {
            $names = array_merge($names, $globalPermissions);
        } elseif (!empty($globalsForRole)) {
            $allowed = array_values(array_intersect($globalPermissions, $globalsForRole));
            $names   = array_merge($names, $allowed);
        }

        return array_values(array_unique($names));
    }

    public function assign(
        string $roleName,
        string $guardName,
        array $permissionNames,
        bool $dryRun,
        bool $reset
    ): int {

        $this->authorizationRepository->ensureRole($roleName, $guardName);

        if ($dryRun) {
            return count($permissionNames);
        }

        if ($reset) {
            $this->authorizationRepository->syncRolePermissions($roleName, $guardName, $permissionNames);
        } else {
            $this->authorizationRepository->attachRolePermissions($roleName, $guardName, $permissionNames);
        }

        return count($permissionNames);
    }
}
