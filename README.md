# 🌟 Laravel Model Permissions

> Model-scoped **and** global permissions with a clean naming convention, optional Spatie integration, a reusable base policy, Super Admin override, and artisan sync tooling — built with SOLID principles.

<p align="center">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.2+-777BB3?logo=php&logoColor=white">
  <img alt="License" src="https://img.shields.io/badge/license-MIT-green">
  <img alt="Spatie Optional" src="https://img.shields.io/badge/Spatie-optional-blue">
</p>

---

## ✨ Features

- 🔑 **Permission keys**: `modelNameAbility` (camelCase), e.g. `userViewAny`, `orderUpdate`, `blogPostDeleteAny`
- 🧱 **Policies**: `BaseModelPolicy` + `ChecksModelPermissions` trait
- 🦸 **Super Admin**: configurable role bypass via `Gate::before`
- 👥 **Roles**: Super Admin, Manager, User (configurable)
- 🧩 **Global permissions** (not tied to any model), e.g. `impersonate`
- ⚙️ **Artisan sync**: generate permissions, create roles, assign permissions
- 🧠 **SOLID** design: services, interfaces, swappable repositories (Spatie or null)

---

## 📦 Installation

```bash
composer require goldoni/laravel-model-permissions
````

> Spatie is optional but recommended for storing roles and permissions.

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

Publish this package config:

```bash
php artisan vendor:publish --tag="model-permissions-config"
```

---

## ⚙️ Configuration

`config/model-permissions.php` (excerpt)

```php
return [
    'guard_name' => 'web',
    'super_admin_role' => 'Super Admin',

    'roles' => [
        'super_admin' => 'Super Admin',
        'manager' => 'Manager',
        'user' => 'User',
    ],

    'models' => [
        App\Models\User::class,
        App\Models\Event::class,
        App\Models\Order::class,
        App\Models\Customer::class,
    ],

    'abilities' => [
        'viewAny','view','create','update','delete','deleteAny',
        'restore','restoreAny','forceDelete','forceDeleteAny',
        'replicate','reorder','attach','attachAny','detach','detachAny',
    ],

    'global_permissions' => [
        'impersonate','accessAdmin','viewReports',
    ],

    'role_ability_map' => [
        'Super Admin' => ['*'],
        'Manager' => ['viewAny','view','create','update','delete','deleteAny','restore','replicate','reorder','attach','attachAny','detach','detachAny'],
        'User' => ['viewAny','view','create','update'],
    ],

    'role_model_ability_map' => [
        'Manager' => [
            App\Models\User::class => ['viewAny','view','update','delete','deleteAny'],
            App\Models\Event::class => ['*'],
            App\Models\Order::class => ['*'],
            App\Models\Customer::class => ['viewAny','view','create','update'],
        ],
        'User' => [
            App\Models\Ticket::class => ['viewAny','view','create'],
            App\Models\Order::class => ['viewAny','view'],
            App\Models\Event::class => ['viewAny','view'],
        ],
    ],

    'role_global_permissions' => [
        'Super Admin' => ['*'],
        'Manager' => ['viewReports','accessAdmin'],
        'User' => [],
    ],
];
```

---

## 🚀 Quick Start

Generate permissions for your configured models and abilities:

```bash
php artisan model-permissions:sync
```

Preview without writing anything:

```bash
php artisan model-permissions:sync --dry
```

Create roles and assign permissions from the config maps:

```bash
php artisan model-permissions:sync --with-roles
```

Replace existing role assignments instead of adding:

```bash
php artisan model-permissions:sync --with-roles --reset
```

---

## 🧭 Naming Convention

Permission key = `lcfirst(class_basename(Model)) . ucfirst(ability)`

Examples:

* `App\Models\User` + `viewAny` → `userViewAny`
* `App\Models\Order` + `update` → `orderUpdate`
* `App\Models\BlogPost` + `deleteAny` → `blogPostDeleteAny`

Global permissions are plain strings, e.g. `impersonate`.

---

## 🧑‍⚖️ Using Policies

Extend the base policy and set the model class:

```php
namespace App\Policies;

use App\Models\Post;
use Goldoni\ModelPermissions\Policies\BaseModelPolicy;

class PostPolicy extends BaseModelPolicy
{
    protected string $modelClass = Post::class;
}
```

Register in your `AuthServiceProvider` as usual.

Then authorize as you normally do:

```php
$user->can('postUpdate');
$user->can('postDeleteAny');
```

---

## 🦸 Super Admin Override

If the authenticated user has the configured `super_admin_role` on the configured `guard_name`, all checks are allowed via `Gate::before`. Change both in the config to fit your app.

---

## 🌍 Global Permissions

Add any app-wide abilities under `global_permissions`, for example:

```php
'global_permissions' => ['impersonate','accessAdmin']
```

Assign them per role via:

```php
'role_global_permissions' => [
    'Super Admin' => ['*'],
    'Manager' => ['accessAdmin'],
]
```

Check them with:

```php
$user->can('impersonate');
```

---

## 🧠 Architecture (SOLID)

* **Services**:

    * `SyncPermissionsService` (build + sync permission names)
    * `RoleAssignmentService` (resolve + assign role permissions)
* **Interfaces**:

    * `AuthorizationRepositoryInterface` (storage abstraction)
    * `PermissionNamerInterface` (naming strategy)
* **Repositories**:

    * `SpatieAuthorizationRepository`
    * `NullAuthorizationRepository` (no-op when Spatie is absent)

This keeps the command thin and your domain logic testable and replaceable.

---

## 🧰 Troubleshooting

* ❗ `BindingResolutionException` for services
  Run `composer dump-autoload -o` and ensure the service provider is auto-discovered (it is via `extra.laravel.providers`).

* ❗ Permissions created but roles not updated
  Use `--with-roles` and optionally `--reset`. Ensure Spatie tables are migrated.

* ❗ Super Admin does not bypass
  Verify the role name and guard in `config/model-permissions.php`, and that the user actually has the role on that guard.

* ❗ Keys not matching your expectations
  Confirm models and abilities in config. Naming uses the class basename lowercased first letter + `ucfirst(ability)`.

---

## 🤝 Contributing

Issues and PRs are welcome. Keep code PSR-12, no inline comments, English identifiers.

---

## 📄 License

MIT © Goldoni Bogning Fouotsa

```
```
