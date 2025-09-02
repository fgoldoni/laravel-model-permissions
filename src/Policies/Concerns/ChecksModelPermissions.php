<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Policies\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;

trait ChecksModelPermissions
{
    public function modelKey(): string
    {
        $model = static::MODEL ?? null;
        $basename = is_string($model) ? class_basename($model) : '';
        return lcfirst($basename);
    }

    public function can(Authenticatable $user, string $ability): bool
    {
        $permissionName = $this->modelKey() . ucfirst($ability);
        $guard = (string) config('model-permissions.guard_name', 'web');

        if (!method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        try {
            return (bool) $user->hasPermissionTo($permissionName, $guard);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
