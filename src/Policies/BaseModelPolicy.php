<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Policies;

use Goldoni\ModelPermissions\Policies\Concerns\ChecksModelPermissions;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModelPolicy
{
    use ChecksModelPermissions;

    protected string $modelClass = '';

    public function viewAny(Model $model): bool
    {
        return $this->hasPermissionTo($model, 'viewAny');
    }

    public function view(Model $user, Model $model): bool
    {
        return $this->hasPermissionTo($user, 'view', $model);
    }

    public function create(Model $model): bool
    {
        return $this->hasPermissionTo($model, 'create');
    }

    public function update(Model $user, Model $model): bool
    {
        return $this->hasPermissionTo($user, 'update', $model);
    }

    public function delete(Model $user, Model $model): bool
    {
        return $this->hasPermissionTo($user, 'delete', $model);
    }

    public function restore(Model $user, Model $model): bool
    {
        return $this->hasPermissionTo($user, 'restore', $model);
    }

    public function forceDelete(Model $user, Model $model): bool
    {
        return $this->hasPermissionTo($user, 'forceDelete', $model);
    }

    public function deleteAny(Model $model): bool
    {
        return $this->hasPermissionTo($model, 'deleteAny');
    }

    public function restoreAny(Model $model): bool
    {
        return $this->hasPermissionTo($model, 'restoreAny');
    }

    public function forceDeleteAny(Model $model): bool
    {
        return $this->hasPermissionTo($model, 'forceDeleteAny');
    }

    public function replicate(Model $user, Model $model): bool
    {
        return $this->hasPermissionTo($user, 'replicate', $model);
    }

    public function reorder(Model $model): bool
    {
        return $this->hasPermissionTo($model, 'reorder');
    }

    public function attach(Model $user, Model $model): bool
    {
        return $this->hasPermissionTo($user, 'attach', $model);
    }

    public function detach(Model $user, Model $model): bool
    {
        return $this->hasPermissionTo($user, 'detach', $model);
    }

    public function attachAny(Model $model): bool
    {
        return $this->hasPermissionTo($model, 'attachAny');
    }

    public function detachAny(Model $model): bool
    {
        return $this->hasPermissionTo($model, 'detachAny');
    }
}
