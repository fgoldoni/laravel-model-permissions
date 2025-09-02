# Laravel Model Permissions

Model-scoped permission sync, base policies, role provisioning, module-aware discovery, and policy scaffolding with **Super Admin** override.  
Works out-of-the-box with [Spatie Laravel Permission] and optionally with [nWidart/laravel-modules].  
Targets **Laravel 12**, **PHP 8.4**, follows **PSR-12** and Laravel best practices.

> Console messages and identifiers are in English. Permission names follow the `modelNameAbility` convention (camelCase), e.g. `userViewAny`, `blogPostDeleteAny`.

---

## Table of contents

- [Why this package](#why-this-package)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Configuration](#configuration)
  - [Top-level keys](#top-level-keys)
  - [Roles provisioning](#roles-provisioning)
  - [Modules support (nWidart)](#modules-support-nwidart)
- [Permission naming convention](#permission-naming-convention)
- [Commands](#commands)
  - [`model-permissions:sync`](#model-permissionssync)
  - [`model-permissions:sync-roles`](#model-permissionssync-roles)
  - [`model-permissions:make-policies`](#model-permissionsmake-policies)
- [Policies](#policies)
  - [Base policy mapping](#base-policy-mapping)
  - [Registering policies](#registering-policies)
- [Super Admin override](#super-admin-override)
- [Using permissions in your app](#using-permissions-in-your-app)
- [Seeding roles (example)](#seeding-roles-example)
- [Troubleshooting](#troubleshooting)
- [Development (Pint, PHPStan, CI)](#development-pint-phpstan-ci)
- [Versioning](#versioning)
- [License](#license)

---

## Why this package

Authoring and maintaining permissions per Eloquent model can be tedious, especially when your app grows or when you split features into **modules**. This package:

- **Generates permissions automatically** for configured and discovered models.
- **Provides a base policy** that maps Laravel abilities to Spatie permissions.
- **Provisions roles declaratively**: define what each role can do per model, in config.
- **Understands modules**: discovers models inside `Modules/<Name>/(Entities|Models)` and generates policies inside each module.

---

## Requirements

- PHP: **^8.4**
- Laravel: **^12.0**
- Spatie Laravel Permission: optional dependency (you install it in your app)
- nWidart/laravel-modules: optional (only if you use modules)

---

## Installation

1) Require the package:
```bash
composer require goldoni/laravel-model-permissions
````

2. Require Spatie (mandatory for runtime permission/role features):

```bash
composer require spatie/laravel-permission
```

3. Publish Spatie migrations and run them:

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

4. Publish the package config:

```bash
php artisan vendor:publish --provider="Goldoni\ModelPermissions\ModelPermissionsServiceProvider" --tag=config
```

> If you use **nWidart/laravel-modules**, install it in your app as usual. The package will detect it automatically when `modules.discover` is enabled.

---

## Quick start

1. Add your models in `config/model-permissions.php` (or rely on Module discovery).
2. Generate permissions:

```bash
php artisan model-permissions:sync
```

3. Provision roles and attach the right permissions:

```bash
php artisan model-permissions:sync-roles
```

4. Scaffold policy files for every model (framework or module):

```bash
php artisan model-permissions:make-policies
```

5. Map policies in your `AuthServiceProvider`.

Done. Your app now authorizes via Spatie permissions aligned with Laravel abilities.

---

## Configuration

Path: `config/model-permissions.php`

### Top-level keys

```php
return [
    'guard_name' => 'web',

    'models' => [
        App\Models\User::class,
        // App\Models\Order::class,
    ],

    'abilities' => [
        'viewAny',
        'view',
        'create',
        'update',
        'delete',
        'restore',
        'forceDelete',
        'replicate',
        'deleteAny',
        'forceDeleteAny',
        'restoreAny',
        'attach',
        'attachAny',
        'detach',
        'add',
    ],

    'super_admin_role' => 'Super Admin',

    'roles' => [
        [
            'name' => 'Super Admin',
            'guard_name' => 'web',
            'models' => ['*'],
            'abilities' => ['*'],
        ],
        [
            'name' => 'Manager',
            'guard_name' => 'web',
            'models' => [
                App\Models\User::class,
            ],
            'abilities' => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'restore',
                'replicate',
                'deleteAny',
                'restoreAny',
                'attach',
                'attachAny',
                'detach',
                'add',
            ],
        ],
        [
            'name' => 'Viewer',
            'guard_name' => 'web',
            'models' => [
                App\Models\User::class,
            ],
            'abilities' => [
                'viewAny',
                'view',
            ],
        ],
    ],

    'modules' => [
        'discover' => true,
        'model_directories' => ['Entities', 'Models'],
        'policy_directory' => 'Policies',
        'models' => [
            // 'Blog' => [
            //     Modules\Blog\Entities\Post::class,
            // ],
        ],
    ],
];
```

* `guard_name`: the Spatie guard used for permissions/roles.
* `models`: baseline list of model FQCNs for apps without modules or in addition to modules.
* `abilities`: Laravel ability names your policies expose.
* `super_admin_role`: role name that bypasses all authorizations.
* `roles`: declarative list of roles, the models they target, and the abilities they get.
* `modules`: module discovery and conventions.

### Roles provisioning

Each role entry:

* `name`: role name.
* `guard_name`: guard for that role (defaults to top-level `guard_name`).
* `models`: list of FQCNs, or `['*']` to include all discovered/configured models.
* `abilities`: list of ability names, or `['*']` to include all configured abilities.

Run:

```bash
php artisan model-permissions:sync-roles
```

### Modules support (nWidart)

* If `modules.discover` is `true` and `nwidart/laravel-modules` is installed, the package scans each enabled module’s `Entities/` and `Models/` directories for Eloquent models.
* You can add module-specific models in `modules.models` if you prefer explicit control.
* Policy scaffolding will output module policies into:

  ```
  Modules/<ModuleName>/<policy_directory>/<Model>Policy.php
  ```

  with the namespace:

  ```
  Modules\<ModuleName>\<policy_directory as namespace segments>
  ```

---

## Permission naming convention

* Format: `modelNameAbility`
* `modelName` is `lcfirst(class_basename(Model::class))`
* `ability` is `ucfirst(ability)`

Examples:

* `App\Models\User` → `userViewAny`, `userUpdate`, `userDeleteAny`
* `Modules\Blog\Entities\Post` → `postCreate`, `postForceDelete`

This aligns nicely with Laravel policies and is predictable across modules.

---

## Commands

### `model-permissions:sync`

Generate permissions for all configured and discovered models:

```bash
php artisan model-permissions:sync
```

Options:

* `--dry` → preview permission names without writing to the database.

This command also clears Spatie’s permission cache after a non-dry run.

---

### `model-permissions:sync-roles`

Create or update roles and attach the right permissions:

```bash
php artisan model-permissions:sync-roles
```

Options:

* `--fresh` → detach all existing permissions from a role before syncing.
* `--role="Name"` → sync only the specified role.
* `--assign-to="1,2,3"` → assign the synced role to user ids (use with `--role`).

Examples:

```bash
# Sync all roles
php artisan model-permissions:sync-roles

# Fresh sync only "Manager"
php artisan model-permissions:sync-roles --role="Manager" --fresh

# Sync "Viewer" then assign it to users 10 and 20
php artisan model-permissions:sync-roles --role="Viewer" --assign-to="10,20"
```

This command also clears Spatie’s permission cache after changes.

---

### `model-permissions:make-policies`

Scaffold minimal policies for all models (framework + modules):

```bash
php artisan model-permissions:make-policies
```

Options:

* `--force` → overwrite existing policy files.

Output:

* App models → `app/Policies/<Model>Policy.php`
* Module models → `Modules/<Module>/<policy_directory>/<Model>Policy.php`

Each generated class extends `BaseModelPolicy` and defines:

```php
public const MODEL = Model::class;
```

---

## Policies

### Base policy mapping

Extend `Goldoni\ModelPermissions\Policies\BaseModelPolicy` and declare the model:

```php
namespace App\Policies;

use App\Models\Order;
use Goldoni\ModelPermissions\Policies\BaseModelPolicy;

final class OrderPolicy extends BaseModelPolicy
{
    public const MODEL = Order::class;
}
```

Abilities implemented in `BaseModelPolicy`:

* `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`, `replicate`
* Bulk: `deleteAny`, `forceDeleteAny`, `restoreAny`
* Relation: `attach`, `attachAny`, `detach`, `add`

Each method delegates to Spatie `hasPermissionTo()` using the computed permission name and configured guard.

### Registering policies

Map your policies in `app/Providers/AuthServiceProvider.php`:

```php
use App\Models\Order;
use App\Policies\OrderPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Order::class => OrderPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
```

For modules, reference the generated policy namespace under `Modules\<Module>\<PolicyNamespace>\<Model>Policy`.

---

## Super Admin override

At boot, the service provider registers a `Gate::before` rule.
Any user with the role defined in `super_admin_role` (default: `Super Admin`) automatically passes all checks.

```php
$user->assignRole('Super Admin');
$user->can('orderDelete'); // true even if they don't have the specific permission
```

---

## Using permissions in your app

Assign permissions to roles/users via Spatie:

```php
$role = \Spatie\Permission\Models\Role::findByName('Manager', 'web');
$role->givePermissionTo('orderUpdate');

$user->assignRole('Manager');
$user->hasPermissionTo('orderUpdate'); // true
```

Authorize via policies/controllers:

```php
$this->authorize('update', $order);
```

Or via gates:

```php
if (\Gate::allows('update', $order)) {
    // ...
}
```

---

## Seeding roles (example)

You can keep your seeding minimal by relying on the commands, but here is a direct example:

```php
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

final class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('model-permissions.guard_name', 'web');

        $super = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guard]);
        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => $guard]);
        $viewer = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => $guard]);

        $super->syncPermissions(Permission::where('guard_name', $guard)->get());
    }
}
```

Tip: Prefer `model-permissions:sync-roles` to keep role definitions centralized in config.

---

## Troubleshooting

**Composer root version warning**
If you see:

```
Composer could not detect the root package version, defaulting to '1.0.0'
```

Use a git tag (recommended) or set a root version when installing:

```bash
git tag v0.1.0 && git push origin v0.1.0
# or
COMPOSER_ROOT_VERSION=dev-main composer install
```

**No composer.lock**
First-time install will run an update. To create a lock:

```bash
composer update
```

**Permissions not taking effect**

* Ensure Spatie migrations are published and migrated.
* Ensure your `User` model uses `HasRoles` trait.
* Re-run `php artisan model-permissions:sync` then `php artisan model-permissions:sync-roles`.
* The commands clear Spatie’s permission cache automatically after changes.

**Multiple guards**
Set `guard_name` accordingly and make sure roles and permissions are created with the same guard.

**Modules not detected**

* Verify `nwidart/laravel-modules` is installed and modules are enabled.
* Check `modules.discover` is `true`.
* Ensure model classes really extend `Illuminate\Database\Eloquent\Model`.

---

## Development (Pint, PHPStan, CI)

This package ships with:

* `.pint.json` (PSR-12 rules)
* `phpstan.neon.dist` (level=max)
* GitHub Actions workflow that runs Pint and PHPStan on each push/PR.

Local dev:

```bash
composer install
composer lint:test
composer stan
```

---

## Versioning

* Follows semantic versioning.
* Tag releases to let Composer infer package versions correctly.

---

## License

MIT © Goldoni Bogning Fouotsa

[Spatie Laravel Permission]: https://github.com/spatie/laravel-permission
[nWidart/laravel-modules]: https://github.com/nWidart/laravel-modules


