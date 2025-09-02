<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Policies;

use Goldoni\ModelPermissions\Policies\Concerns\ChecksModelPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModelPolicy
{
    use ChecksModelPermissions;

    public const MODEL = null;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->can($user, 'viewAny');
    }

    public function view(Authenticatable $user, Model $model): bool
    {
        return $this->can($user, 'view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(Authenticatable $user, Model $model): bool
    {
        return $this->can($user, 'update');
    }

    public function delete(Authenticatable $user, Model $model): bool
    {
        return $this->can($user, 'delete');
    }

    public function restore(Authenticatable $user, Model $model): bool
    {
        return $this->can($user, 'restore');
    }

    public function forceDelete(Authenticatable $user, Model $model): bool
    {
        return $this->can($user, 'forceDelete');
    }

    public function replicate(Authenticatable $user, Model $model): bool
    {
        return $this->can($user, 'replicate');
    }

    public function deleteAny(Authenticatable $user): bool
    {
        return $this->can($user, 'deleteAny');
    }

    public function forceDeleteAny(Authenticatable $user): bool
    {
        return $this->can($user, 'forceDeleteAny');
    }

    public function restoreAny(Authenticatable $user): bool
    {
        return $this->can($user, 'restoreAny');
    }

    public function attach(Authenticatable $user): bool
    {
        return $this->can($user, 'attach');
    }

    public function attachAny(Authenticatable $user): bool
    {
        return $this->can($user, 'attachAny');
    }

    public function detach(Authenticatable $user): bool
    {
        return $this->can($user, 'detach');
    }

    public function add(Authenticatable $user): bool
    {
        return $this->can($user, 'add');
    }
}
