<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions;

use Override;
use Goldoni\ModelPermissions\Commands\SyncPermissionsCommand;
use Goldoni\ModelPermissions\Contracts\AuthorizationRepositoryInterface;
use Goldoni\ModelPermissions\Contracts\PermissionNamerInterface;
use Goldoni\ModelPermissions\Repositories\NullAuthorizationRepository;
use Goldoni\ModelPermissions\Repositories\SpatieAuthorizationRepository;
use Goldoni\ModelPermissions\Services\RoleAssignmentService;
use Goldoni\ModelPermissions\Services\SyncPermissionsService;
use Goldoni\ModelPermissions\Support\ModelPermissionNamer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class ModelPermissionsServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/model-permissions.php', 'model-permissions');

        $this->app->bind(PermissionNamerInterface::class, ModelPermissionNamer::class);

        $spatieAvailable = class_exists(Permission::class) && class_exists(Role::class) && class_exists(PermissionRegistrar::class);

        if ($spatieAvailable) {
            $this->app->bind(AuthorizationRepositoryInterface::class, SpatieAuthorizationRepository::class);
        } else {
            $this->app->bind(AuthorizationRepositoryInterface::class, NullAuthorizationRepository::class);
        }

        $this->app->singleton(fn ($app): SyncPermissionsService => new SyncPermissionsService(
            $app->make(AuthorizationRepositoryInterface::class),
            $app->make(PermissionNamerInterface::class)
        ));

        $this->app->singleton(fn ($app): RoleAssignmentService => new RoleAssignmentService(
            $app->make(AuthorizationRepositoryInterface::class),
            $app->make(PermissionNamerInterface::class)
        ));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/model-permissions.php' => config_path('model-permissions.php'),
        ], 'model-permissions-config');

        if ($this->app->runningInConsole()) {
            $this->commands([SyncPermissionsCommand::class]);
        }

        Gate::before(function (?Authenticatable $authenticatable, string $ability): ?true {
            if (!$authenticatable instanceof Authenticatable) {
                return null;
            }

            $guardName      = config('model-permissions.guard_name', 'web');
            $superAdminRole = config('model-permissions.super_admin_role', 'Super Admin');

            if (method_exists($authenticatable, 'hasRole')) {
                try {
                    if ($authenticatable->hasRole($superAdminRole, $guardName)) {
                        return true;
                    }
                } catch (Throwable) {
                    return null;
                }
            }

            return null;
        });

        if (class_exists(PermissionRegistrar::class)) {
            try {
                $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
            } catch (Throwable) {
            }
        }
    }
}
