<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Commands;

use Goldoni\ModelPermissions\Support\ModelLocator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class MakePoliciesCommand extends Command
{
    protected $signature = 'model-permissions:make-policies {--force : Overwrite existing files}';
    protected $description = 'Generate minimal policy classes for all discovered and configured models.';

    public function handle(): int
    {
        $filesystem = new Filesystem();
        $force = (bool) $this->option('force');
        $policyDirName = (string) config('model-permissions.modules.policy_directory', 'Policies');

        $models = ModelLocator::all();
        $generated = 0;
        foreach ($models as $fqcn) {
            $modelBase = class_basename($fqcn);
            $policyClass = $modelBase . 'Policy';

            $moduleName = ModelLocator::moduleNameFromModel($fqcn);
            if ($moduleName) {
                $targetDir = base_path('Modules/' . $moduleName . '/' . trim($policyDirName, '/'));
                $namespace = 'Modules\\' . $moduleName . '\\' . trim(str_replace(['/', '\\'], '\\', $policyDirName), '\\');
            } else {
                $targetDir = app_path('Policies');
                $namespace = 'App\\Policies';
            }

            if (!$filesystem->exists($targetDir)) {
                $filesystem->makeDirectory($targetDir, 0755, true);
            }

            $path = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $policyClass . '.php';
            if ($filesystem->exists($path) && !$force) {
                $this->line("Skipped existing: {$path}");
                continue;
            }

            $contents = $this->policyContents($namespace, $policyClass, $fqcn);
            $filesystem->put($path, $contents);
            $this->line("Generated: {$path}");
            $generated++;
        }

        $this->info("Policy classes generated: {$generated}");

        return self::SUCCESS;
    }

    protected function policyContents(string $namespace, string $class, string $modelFqcn): string
    {
        $modelFqcn = '\\' . ltrim($modelFqcn, '\\');
        $basePolicy = '\\Goldoni\\ModelPermissions\\Policies\\BaseModelPolicy';

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

final class {$class} extends {$basePolicy}
{
    public const MODEL = {$modelFqcn}::class;
}

PHP;
    }
}
