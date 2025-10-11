<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Policies\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ChecksModelPermissions
{
    protected function resolveGuardName(): string
    {
        $guard = (string) config('model-permissions.guard_name', '');

        return $guard !== '' ? $guard : (string) config('auth.defaults.guard', 'web');
    }

    protected function hasPermissionTo(Model $model, string $ability, string|Model|null $modelOrClass = null): bool
    {
        $modelClass = $this->resolveModelClass($modelOrClass);
        $permission = $this->buildPermissionName($modelClass, $ability);
        $guard      = $this->resolveGuardName();

        if (! method_exists($model, 'hasPermissionTo')) {
            return false;
        }

        return (bool) $model->hasPermissionTo($permission, $guard);
    }


    protected function buildPermissionName(string $modelClass, string $ability): string
    {
        $base = class_basename($modelClass);

        return lcfirst($base) . ucfirst($ability);
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

        $policy = static::class;
        $base   = preg_replace('/Policy$/', '', class_basename($policy)) ?: '';

        if ($base !== '') {
            $guess = 'App\\Models\\' . $base;

            if (class_exists($guess)) {
                return $guess;
            }
        }

        return $policy;
    }
}
