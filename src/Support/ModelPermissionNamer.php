<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Support;

use Goldoni\ModelPermissions\Contracts\PermissionNamerInterface;

class ModelPermissionNamer implements PermissionNamerInterface
{
    public function buildForModel(string $modelClass, string $ability): string
    {
        $base = class_basename($modelClass);

        return lcfirst($base) . ucfirst($ability);
    }
}
