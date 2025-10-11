<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Commands;

use Goldoni\ModelPermissions\Contracts\AuthorizationRepositoryInterface;
use Goldoni\ModelPermissions\Services\RoleAssignmentService;
use Goldoni\ModelPermissions\Services\SyncPermissionsService;
use Illuminate\Console\Command;
use Throwable;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'model-permissions:sync
                            {--dry : Run without creating or modifying anything}
                            {--with-roles : Also create roles and assign permissions}
                            {--reset : With --with-roles, replace existing role permissions instead of adding}';

    protected $description = 'Synchronize permissions for models and global permissions, optionally creating roles and assigning them.';

    public function __construct(
        private readonly SyncPermissionsService $syncPermissionsService,
        private readonly RoleAssignmentService $roleAssignmentService,
        private readonly AuthorizationRepositoryInterface $authorizationRepository
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dry       = (bool) $this->option('dry');
        $withRoles = (bool) $this->option('with-roles');
        $reset     = (bool) $this->option('reset');

        $guard               = (string) config('model-permissions.guard_name', 'web');
        $models              = (array) config('model-permissions.models', []);
        $abilities           = (array) config('model-permissions.abilities', []);
        $globals             = (array) config('model-permissions.global_permissions', []);
        $roles               = (array) config('model-permissions.roles', []);
        $roleAbilityMap      = (array) config('model-permissions.role_ability_map', []);
        $roleModelAbilityMap = (array) config('model-permissions.role_model_ability_map', []);
        $roleGlobalMap       = (array) config('model-permissions.role_global_permissions', []);

        if ($models === [] && $globals === []) {
            $this->warn('Nothing to synchronize.');

            return self::SUCCESS;
        }

        if (!$this->authorizationRepository->isAvailable() && !$dry) {
            $this->error('spatie/laravel-permission is not available.');

            return self::FAILURE;
        }

        $syncResult = $this->syncPermissionsService->sync($models, $abilities, $globals, $guard, $dry);

        $this->info('Model and global permissions');
        $this->line('Created: ' . $syncResult->created . ', Existing: ' . $syncResult->existing);

        if ($withRoles && $roles !== []) {
            $this->newLine();
            $this->info('Roles and assignments');

            foreach ($roles as $role) {
                $permissionNames = $this->roleAssignmentService->buildPermissionNamesForRole(
                    $role,
                    $models,
                    $abilities,
                    $roleAbilityMap,
                    $roleModelAbilityMap,
                    $globals,
                    $roleGlobalMap
                );

                $count = $this->roleAssignmentService->assign($role, $guard, $permissionNames, $dry, $reset);

                $this->line($role . ': ' . $count . ' permissions');
            }
        }

        if ($this->authorizationRepository->isAvailable() && !$dry) {
            try {
                $this->authorizationRepository->clearCache();
                $this->newLine();
                $this->info('Permission cache cleared.');
            } catch (Throwable $exception) {
                $this->warn('Could not clear permission cache: ' . $exception->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
