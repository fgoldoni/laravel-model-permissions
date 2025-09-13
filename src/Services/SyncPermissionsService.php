<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Services;

use Goldoni\ModelPermissions\Contracts\AuthorizationRepositoryInterface;
use Goldoni\ModelPermissions\Contracts\PermissionNamerInterface;
use Goldoni\ModelPermissions\DTO\SyncResult;

class SyncPermissionsService
{
    public function __construct(private readonly AuthorizationRepositoryInterface $authorizationRepository, private readonly PermissionNamerInterface $permissionNamer)
    {
    }

    public function generateModelPermissionNames(array $models, array $abilities): array
    {
        $names = [];

        foreach ($models as $model) {
            foreach ($abilities as $ability) {
                $names[] = $this->permissionNamer->buildForModel((string) $model, (string) $ability);
            }
        }

        return array_values(array_unique($names));
    }

    public function sync(array $models, array $abilities, array $globalPermissions, string $guardName, bool $dryRun): SyncResult
    {
        $created              = 0;
        $existing             = 0;
        $modelPermissionNames = $this->generateModelPermissionNames($models, $abilities);
        $allPermissionNames   = array_values(array_unique(array_merge($modelPermissionNames, $globalPermissions)));

        if ($dryRun) {
            foreach ($allPermissionNames as $allPermissionName) {
                if ($this->authorizationRepository->permissionExists($allPermissionName, $guardName)) {
                    ++$existing;
                } else {
                    ++$created;
                }
            }

            return new SyncResult($created, $existing, $allPermissionNames);
        }

        foreach ($allPermissionNames as $allPermissionName) {
            $createdNow = $this->authorizationRepository->ensurePermission($allPermissionName, $guardName);
            if ($createdNow) {
                ++$created;
            } else {
                ++$existing;
            }
        }

        return new SyncResult($created, $existing, $allPermissionNames);
    }
}
