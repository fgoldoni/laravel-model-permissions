<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Policies;

use Goldoni\ModelPermissions\Policies\Concerns\ChecksModelPermissions;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModelPolicy
{
    use ChecksModelPermissions;

    protected string $modelClass = '';

    public function viewAny(mixed $user): bool
    {
        return $this->can($user, 'viewAny');
    }

    public function view(mixed $user, Model $model): bool
    {
        return $this->can($user, 'view', $model);
    }

    public function create(mixed $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(mixed $user, Model $model): bool
    {
        return $this->can($user, 'update', $model);
    }

    public function delete(mixed $user, Model $model): bool
    {
        return $this->can($user, 'delete', $model);
    }

    public function restore(mixed $user, Model $model): bool
    {
        return $this->can($user, 'restore', $model);
    }

    public function forceDelete(mixed $user, Model $model): bool
    {
        return $this->can($user, 'forceDelete', $model);
    }

    public function deleteAny(mixed $user): bool
    {
        return $this->can($user, 'deleteAny');
    }

    public function restoreAny(mixed $user): bool
    {
        return $this->can($user, 'restoreAny');
    }

    public function forceDeleteAny(mixed $user): bool
    {
        return $this->can($user, 'forceDeleteAny');
    }

    public function replicate(mixed $user, Model $model): bool
    {
        return $this->can($user, 'replicate', $model);
    }

    public function reorder(mixed $user): bool
    {
        return $this->can($user, 'reorder');
    }

    public function attach(mixed $user, Model $model): bool
    {
        return $this->can($user, 'attach', $model);
    }

    public function detach(mixed $user, Model $model): bool
    {
        return $this->can($user, 'detach', $model);
    }

    public function attachAny(mixed $user): bool
    {
        return $this->can($user, 'attachAny');
    }

    public function detachAny(mixed $user): bool
    {
        return $this->can($user, 'detachAny');
    }
}
