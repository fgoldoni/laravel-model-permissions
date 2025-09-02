<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Support;

final class PermissionNamer
{
    public static function modelKey(string $modelClass): string
    {
        $class = ltrim($modelClass, '\\');
        $basename = class_basename($class);
        return lcfirst($basename);
    }

    public static function permissionName(string $modelClass, string $ability): string
    {
        return self::modelKey($modelClass) . ucfirst($ability);
    }

    public static function permissionNamesFor(string $modelClass, array $abilities): array
    {
        $names = [];
        foreach ($abilities as $ability) {
            $names[] = self::permissionName($modelClass, (string) $ability);
        }
        return $names;
    }
}
