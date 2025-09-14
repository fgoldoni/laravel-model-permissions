<?php

declare(strict_types=1);

use App\Models\User;

return [
    'guard_name'       => 'web',
    'super_admin_role' => 'Super Admin',
    'roles'            => [
        'super_admin' => 'Super Admin',
        'manager'     => 'Manager',
        'seller'      => 'Seller',
        'user'        => 'User',
    ],
    'models' => [
        User::class,
    ],
    'abilities' => [
        'viewAny', 'view', 'create', 'update', 'delete', 'deleteAny',
        'restore', 'restoreAny', 'forceDelete', 'forceDeleteAny',
        'replicate', 'reorder', 'attach', 'attachAny', 'detach', 'detachAny',
    ],
    'global_permissions' => [
        'impersonate',
    ],
    'role_ability_map' => [
        'Super Admin' => ['*'],
        'Manager'     => ['viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'restore', 'replicate', 'reorder', 'attach', 'attachAny', 'detach', 'detachAny'],
        'Seller'      => ['viewAny', 'view', 'create', 'update', 'delete', 'deleteAny'],
        'User'        => ['viewAny', 'view', 'create', 'update'],
    ],
    'role_model_ability_map' => [
        'Manager' => [
            User::class        => ['viewAny', 'view', 'update', 'delete', 'deleteAny'],
        ],
        'Seller' => [
            User::class        => ['viewAny', 'view', 'update', 'delete', 'deleteAny'],
        ],
        'User' => [
        ],
    ],
    'role_global_permissions' => [
        'Super Admin' => ['*'],
        'Manager'     => ['impersonate'],
        'Seller'      => [],
        'User'        => [],
    ],
];
