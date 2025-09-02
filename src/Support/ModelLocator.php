<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;

final class ModelLocator
{
    public static function all(): array
    {
        $models = [];
        $baseModels = config('model-permissions.models', []);
        foreach ($baseModels as $fqcn) {
            if (is_string($fqcn)) {
                $models[] = $fqcn;
            }
        }

        $moduleMapped = config('model-permissions.modules.models', []);
        if (is_array($moduleMapped)) {
            foreach ($moduleMapped as $moduleName => $fqcns) {
                foreach ((array) $fqcns as $fqcn) {
                    if (is_string($fqcn)) {
                        $models[] = $fqcn;
                    }
                }
            }
        }

        $discover = (bool) config('model-permissions.modules.discover', true);
        if ($discover) {
            $discovered = self::discoverModuleModels();
            $models = array_merge($models, $discovered);
        }

        $models = array_values(array_unique(array_filter($models, function ($class) {
            if (!is_string($class)) {
                return false;
            }
            if (!class_exists($class)) {
                return false;
            }
            return is_subclass_of($class, Model::class);
        })));

        sort($models);

        return $models;
    }

    public static function moduleNameFromModel(string $fqcn): ?string
    {
        $prefix = 'Modules\\';
        if (str_starts_with($fqcn, $prefix)) {
            $remainder = substr($fqcn, strlen($prefix));
            $parts = explode('\\', $remainder);
            if (isset($parts[0]) && $parts[0] !== '') {
                return $parts[0];
            }
        }
        return null;
    }

    protected static function discoverModuleModels(): array
    {
        $models = [];
        $directories = (array) config('model-permissions.modules.model_directories', ['Entities', 'Models']);

        if (class_exists(\Nwidart\Modules\Facades\Module::class)) {
            $enabled = \Nwidart\Modules\Facades\Module::allEnabled();
            foreach ($enabled as $module) {
                $moduleName = $module->getName();
                $modulePath = $module->getPath();
                foreach ($directories as $dir) {
                    $path = $modulePath . DIRECTORY_SEPARATOR . $dir;
                    $models = array_merge($models, self::scanDirectoryForModels($moduleName, $dir, $path));
                }
            }
        } else {
            $filesystem = new Filesystem();
            $modulesRoot = base_path('Modules');
            if ($filesystem->isDirectory($modulesRoot)) {
                $moduleNames = array_values(array_filter($filesystem->directories($modulesRoot), function ($path) {
                    return is_dir($path);
                }));
                foreach ($moduleNames as $modulePath) {
                    $moduleName = basename($modulePath);
                    foreach ($directories as $dir) {
                        $path = $modulePath . DIRECTORY_SEPARATOR . $dir;
                        $models = array_merge($models, self::scanDirectoryForModels($moduleName, $dir, $path));
                    }
                }
            }
        }

        return $models;
    }

    protected static function scanDirectoryForModels(string $moduleName, string $dir, string $path): array
    {
        $filesystem = new Filesystem();
        $fqcns = [];
        if ($filesystem->isDirectory($path)) {
            foreach ($filesystem->allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $class = $file->getFilenameWithoutExtension();
                $fqcn = 'Modules\\' . $moduleName . '\\' . trim(str_replace(['/', '\\'], '\\', $dir), '\\') . '\\' . $class;
                if (class_exists($fqcn) && is_subclass_of($fqcn, Model::class)) {
                    $fqcns[] = $fqcn;
                }
            }
        }
        return $fqcns;
    }
}
