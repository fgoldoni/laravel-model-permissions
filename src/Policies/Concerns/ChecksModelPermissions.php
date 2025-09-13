<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Policies\Concerns;

use Illuminate\Database\Eloquent\Model;
use Throwable;

trait ChecksModelPermissions
{
    protected function can(mixed $user, string $ability, string|Model|null $modelOrClass = null): bool
    {
        $modelClass     = $this->resolveModelClass($modelOrClass);
        $permissionName = $this->buildPermissionName($modelClass, $ability);
        $guardName      = config('model-permissions.guard_name', 'web');

        if (method_exists($user, 'hasPermissionTo')) {
            try {
                return (bool) $user->hasPermissionTo($permissionName, $guardName);
            } catch (Throwable) {
            }
        }

        if (method_exists($user, 'can')) {
            return (bool) $user->can($permissionName, [$guardName]);
        }

        return false;
    }

    protected function buildPermissionName(string $modelClass, string $ability): string
    {
        $modelBase = class_basename($modelClass);
        return lcfirst($modelBase) . ucfirst($ability);
    }

    protected function resolveModelClass(string|Model|null $modelOrClass = null): string
    {
        if ($modelOrClass instanceof Model) {
            return $modelOrClass::class;
        }

        if (is_string($modelOrClass) && $modelOrClass !== '') {
            return $modelOrClass;
        }

        if (property_exists($this, 'modelClass') && $this->modelClass !== '') {
            return $this->modelClass;
        }

        $policyClass = static::class;
        $base        = preg_replace('/Policy$/', '', class_basename($policyClass)) ?? '';

        if ($base !== '') {
            $guessed = 'App\Models\\' . $base;
            if (class_exists($guessed)) {
                return $guessed;
            }
        }

        return $policyClass;
    }
}
