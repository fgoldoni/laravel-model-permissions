<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Contracts;

interface PermissionNamerInterface
{
    public function buildForModel(string $modelClass, string $ability): string;
}
