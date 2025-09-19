<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Console\Commands;

use Goldoni\ModelPermissions\Contracts\PermissionNamerInterface;
use Goldoni\ModelPermissions\Events\ModelPermissionsHealthCheckFailed;
use Goldoni\ModelPermissions\Events\ModelPermissionsHealthCheckPassed;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class ModelPermissionsHealthCheckCommand extends Command
{
    protected $signature = 'model-permissions:health {--json : Output as JSON}';
    protected $description = 'Validate Goldoni Model Permissions setup';

    public function handle(): int
    {
        $results = collect();

        $results->push($this->check('php.version', fn() => version_compare(PHP_VERSION, '8.4.0', '>='), PHP_VERSION));
        $results->push($this->check('laravel.version', fn() => version_compare(App::version(), '12.0.0', '>='), App::version()));
        $results->push($this->check('config.loaded', fn() => is_array(Config::get('model-permissions')), (string) json_encode(Config::get('model-permissions'), JSON_UNESCAPED_SLASHES)));
        $results->push($this->check('config.guard_name', fn() => is_string(Config::get('model-permissions.guard_name')) && Config::get('model-permissions.guard_name') !== '', (string) Config::get('model-permissions.guard_name')));
        $results->push($this->check('config.roles', fn() => is_array(Config::get('model-permissions.roles')) && Config::get('model-permissions.roles') !== []));
        $results->push($this->check('config.models', fn() => $this->allClassesExist((array) Config::get('model-permissions.models', [])), (string) json_encode(Config::get('model-permissions.models'))));
        $results->push($this->check('config.abilities', fn() => is_array(Config::get('model-permissions.abilities')) && Config::get('model-permissions.abilities') !== []));
        $results->push($this->check('command.sync.registered', fn() => $this->commandExists('model-permissions:sync')));

        $spatie = class_exists(Permission::class) && class_exists(Role::class) && class_exists(PermissionRegistrar::class);
        $results->push($this->check('spatie.available', fn() => $spatie));

        if ($spatie) {
            $results->push($this->check('db.table.roles', fn() => Schema::hasTable(config('permission.table_names.roles', 'roles'))));
            $results->push($this->check('db.table.permissions', fn() => Schema::hasTable(config('permission.table_names.permissions', 'permissions'))));
            $results->push($this->check('db.table.model_has_roles', fn() => Schema::hasTable(config('permission.table_names.model_has_roles', 'model_has_roles'))));
            $results->push($this->check('db.table.role_has_permissions', fn() => Schema::hasTable(config('permission.table_names.role_has_permissions', 'role_has_permissions'))));
            $results->push($this->check('db.table.model_has_permissions', fn() => Schema::hasTable(config('permission.table_names.model_has_permissions', 'model_has_permissions'))));
            $results->push($this->check('db.columns.roles', fn() => Schema::hasColumns(config('permission.table_names.roles', 'roles'), ['id','name','guard_name'])));
            $results->push($this->check('db.columns.permissions', fn() => Schema::hasColumns(config('permission.table_names.permissions', 'permissions'), ['id','name','guard_name'])));
            $results->push($this->check('spatie.guard.cache.clearable', fn() => $this->canClearPermissionCache()));
            $results->push($this->check('config.super_admin_role', fn() => is_string(Config::get('model-permissions.super_admin_role')) && Config::get('model-permissions.super_admin_role') !== '', (string) Config::get('model-permissions.super_admin_role')));
            $results->push($this->check('roles.exist', fn() => $this->rolesExistWithGuard(), (string) Config::get('model-permissions.guard_name')));
            $results->push($this->check('globals.exist', fn() => $this->globalsExistWithGuard(), (string) Config::get('model-permissions.guard_name')));
            $results->push($this->check('namer.builds', fn() => $this->namerBuilds()));
        }

        $ok = $results->every(fn(array $r) => $r['status'] === 'ok');

        if ($ok) {
            Event::dispatch(new ModelPermissionsHealthCheckPassed($results->toArray()));
        } else {
            Event::dispatch(new ModelPermissionsHealthCheckFailed($results->toArray()));
        }

        if ($this->option('json')) {
            $this->line(json_encode(['ok' => $ok, 'checks' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Check', 'Status', 'Detail'], $results->map(fn($r) => [$r['key'], $r['status'], (string) ($r['detail'] ?? '')]));
            $this->line($ok ? '<info>OK</info>' : '<error>FAIL</error>');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    protected function check(string $key, callable $fn, mixed $detail = null): array
    {
        try {
            $passed = (bool) value($fn);
            if ($detail instanceof \Closure) {
                $detail = value($detail);
            }
            return ['key' => $key, 'status' => $passed ? 'ok' : 'fail', 'detail' => $detail];
        } catch (\Throwable $e) {
            return ['key' => $key, 'status' => 'error', 'detail' => $e->getMessage()];
        }
    }

    protected function commandExists(string $name): bool
    {
        try {
            $app = $this->getApplication();
            if ($app) {
                return array_key_exists($name, $app->all());
            }
            $all = Artisan::all();
            return array_key_exists($name, $all);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function allClassesExist(array $classes): bool
    {
        foreach ($classes as $class) {
            if (!is_string($class) || $class === '' || !class_exists($class)) {
                return false;
            }
        }
        return true;
    }

    protected function canClearPermissionCache(): bool
    {
        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function rolesExistWithGuard(): bool
    {
        $guard = (string) Config::get('model-permissions.guard_name', 'web');
        $labels = array_values((array) Config::get('model-permissions.roles', []));
        if ($labels === []) {
            return false;
        }
        $table = config('permission.table_names.roles', 'roles');
        foreach ($labels as $label) {
            $exists = DB::table($table)->where('name', $label)->where('guard_name', $guard)->exists();
            if (!$exists) {
                return false;
            }
        }
        return true;
    }

    protected function globalsExistWithGuard(): bool
    {
        $guard = (string) Config::get('model-permissions.guard_name', 'web');
        $globals = (array) Config::get('model-permissions.global_permissions', []);
        if ($globals === []) {
            return true;
        }
        $table = config('permission.table_names.permissions', 'permissions');
        foreach ($globals as $perm) {
            $exists = DB::table($table)->where('name', $perm)->where('guard_name', $guard)->exists();
            if (!$exists) {
                return false;
            }
        }
        return true;
    }

    protected function namerBuilds(): bool
    {
        try {
            $namer = app(PermissionNamerInterface::class);
            $models = (array) Config::get('model-permissions.models', []);
            $abilities = (array) Config::get('model-permissions.abilities', []);
            if ($models === [] || $abilities === []) {
                return true;
            }
            $model = (string) $models[0];
            $ability = (string) $abilities[0];
            $name = $namer->buildForModel($model, $ability);
            return is_string($name) && $name !== '';
        } catch (\Throwable) {
            return false;
        }
    }
}
