<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions;

use Goldoni\ModelPermissions\Commands\MakePoliciesCommand;
use Goldoni\ModelPermissions\Commands\SyncPermissionsCommand;
use Goldoni\ModelPermissions\Commands\SyncRolesCommand;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class ModelPermissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/model-permissions.php', 'model-permissions');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/model-permissions.php' => config_path('model-permissions.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncPermissionsCommand::class,
                SyncRolesCommand::class,
                MakePoliciesCommand::class,
            ]);
        }

        Gate::before(function (Authenticatable $user, string $ability = null) {
            $roleName = config('model-permissions.super_admin_role');
            $guard = config('model-permissions.guard_name');
            if ($roleName && method_exists($user, 'hasRole')) {
                try {
                    if ($user->hasRole($roleName, $guard)) {
                        return true;
                    }
                } catch (\Throwable $e) {
                    return null;
                }
            }
            return null;
        });
    }
}
