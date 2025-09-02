<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Commands;

use Goldoni\ModelPermissions\Support\ModelLocator;
use Goldoni\ModelPermissions\Support\PermissionNamer;
use Illuminate\Console\Command;

final class SyncPermissionsCommand extends Command
{
    protected $signature = 'model-permissions:sync {--dry : Show actions without writing to the database}';
    protected $description = 'Synchronize permissions for configured and discovered models using the modelNameAbility format.';

    public function handle(): int
    {
        if (!class_exists(\Spatie\Permission\Models\Permission::class)) {
            $this->error('Spatie Laravel Permission is not installed. Install spatie/laravel-permission to sync permissions.');
            return self::FAILURE;
        }

        $guard = (string) config('model-permissions.guard_name', 'web');
        $abilities = (array) config('model-permissions.abilities', []);
        $models = ModelLocator::all();
        $dry = (bool) $this->option('dry');

        $created = 0;
        $existing = 0;

        foreach ($models as $model) {
            $names = PermissionNamer::permissionNamesFor($model, $abilities);
            foreach ($names as $name) {
                if ($dry) {
                    $this->line($name);
                    $existing++;
                    continue;
                }
                $permission = \Spatie\Permission\Models\Permission::query()->firstOrCreate(
                    ['name' => $name, 'guard_name' => $guard],
                    ['name' => $name, 'guard_name' => $guard]
                );
                if ($permission->wasRecentlyCreated) {
                    $created++;
                } else {
                    $existing++;
                }
            }
        }

        if (!$dry && class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            try {
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
                $this->info('Permission cache cleared.');
            } catch (\Throwable $e) {
                $this->warn('Unable to clear permission cache.');
            }
        }

        $this->info("Permissions processed. Created: {$created}, Existing: {$existing}.");

        return self::SUCCESS;
    }
}
