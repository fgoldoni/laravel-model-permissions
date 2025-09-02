<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Commands;

use Goldoni\ModelPermissions\Support\ModelLocator;
use Goldoni\ModelPermissions\Support\PermissionNamer;
use Illuminate\Console\Command;

final class SyncRolesCommand extends Command
{
    protected $signature = 'model-permissions:sync-roles
                            {--fresh : Detach all existing permissions before syncing}
                            {--role= : Sync only a single role by name}
                            {--assign-to= : Comma-separated user ids to assign the synced role to}';

    protected $description = 'Create roles from config and assign model-scoped permissions to them.';

    public function handle(): int
    {
        if (!class_exists(\Spatie\Permission\Models\Role::class) || !class_exists(\Spatie\Permission\Models\Permission::class)) {
            $this->error('Spatie Laravel Permission is not installed. Install spatie/laravel-permission to sync roles.');
            return self::FAILURE;
        }

        $rolesConfig = (array) config('model-permissions.roles', []);
        $abilitiesConfig = (array) config('model-permissions.abilities', []);
        $guard = (string) config('model-permissions.guard_name', 'web');
        $allModels = ModelLocator::all();

        $onlyRole = $this->option('role');
        if (is_string($onlyRole) && $onlyRole !== '') {
            $rolesConfig = array_values(array_filter($rolesConfig, function ($role) use ($onlyRole) {
                return isset($role['name']) && $role['name'] === $onlyRole;
            }));
            if (empty($rolesConfig)) {
                $this->warn('No matching role found for --role option.');
                return self::SUCCESS;
            }
        }

        $fresh = (bool) $this->option('fresh');
        $assignedUsers = [];

        foreach ($rolesConfig as $roleDef) {
            $roleName = (string) ($roleDef['name'] ?? '');
            if ($roleName === '') {
                $this->warn('Skipping role with empty name.');
                continue;
            }

            $roleGuard = (string) ($roleDef['guard_name'] ?? $guard);
            $roleModels = $roleDef['models'] ?? ['*'];
            $roleAbilities = $roleDef['abilities'] ?? ['*'];

            if ($roleModels === ['*']) {
                $resolvedModels = $allModels;
            } else {
                $resolvedModels = array_values(array_filter((array) $roleModels, function ($m) {
                    return is_string($m);
                }));
            }

            if ($roleAbilities === ['*']) {
                $resolvedAbilities = $abilitiesConfig;
            } else {
                $resolvedAbilities = array_values(array_filter((array) $roleAbilities, function ($a) {
                    return is_string($a);
                }));
            }

            $permissionNames = [];
            foreach ($resolvedModels as $model) {
                $permissionNames = array_merge($permissionNames, PermissionNamer::permissionNamesFor($model, $resolvedAbilities));
            }
            $permissionNames = array_values(array_unique($permissionNames));

            $permissions = \Spatie\Permission\Models\Permission::query()
                ->where('guard_name', $roleGuard)
                ->whereIn('name', $permissionNames)
                ->get();

            $role = \Spatie\Permission\Models\Role::findOrCreate($roleName, $roleGuard);

            if ($fresh) {
                $role->syncPermissions($permissions);
            } else {
                if ($permissions->isNotEmpty()) {
                    $role->givePermissionTo($permissions);
                }
            }

            $this->info("Role synced: {$roleName} ({$roleGuard}) with {$permissions->count()} permissions.");
        }

        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            try {
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
                $this->info('Permission cache cleared.');
            } catch (\Throwable $e) {
                $this->warn('Unable to clear permission cache.');
            }
        }

        $assignTo = $this->option('assign-to');
        $onlyRoleName = $this->option('role');

        if (is_string($assignTo) && $assignTo !== '' && is_string($onlyRoleName) && $onlyRoleName !== '') {
            $ids = array_values(array_filter(array_map('trim', explode(',', $assignTo)), function ($v) {
                return $v !== '';
            }));

            $userModel = config('auth.providers.users.model');
            if (is_string($userModel) && class_exists($userModel)) {
                $instance = new $userModel();
                $keyName = method_exists($instance, 'getKeyName') ? $instance->getKeyName() : 'id';
                $users = $userModel::query()->whereIn($keyName, $ids)->get();
                $role = \Spatie\Permission\Models\Role::where('name', $onlyRoleName)->first();
                if ($role) {
                    foreach ($users as $user) {
                        if (method_exists($user, 'assignRole')) {
                            try {
                                $user->assignRole($role);
                                $assignedUsers[] = $user->{$keyName};
                            } catch (\Throwable $e) {
                            }
                        }
                    }
                }
            } else {
                $this->warn('Unable to resolve user model for assigning roles.');
            }
        }

        if (!empty($assignedUsers)) {
            $this->info('Assigned role to users: ' . implode(', ', $assignedUsers));
        }

        return self::SUCCESS;
    }
}
