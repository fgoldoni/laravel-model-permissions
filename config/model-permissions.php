<?php

declare(strict_types=1);

use App\Models\Artist;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Video;

return [
    'guard_name'       => 'web',
    'super_admin_role' => 'Super Admin',
    'roles'            => [
        'super_admin' => 'Super Admin',
        'manager'     => 'Manager',
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
        'User'        => ['viewAny', 'view', 'create', 'update'],
    ],
    'role_model_ability_map' => [
        'Manager' => [
            User::class        => ['viewAny', 'view', 'update', 'delete', 'deleteAny'],
        ],
        'User' => [
        ],
    ],
    'role_global_permissions' => [
        'Super Admin' => ['*'],
        'Manager'     => ['impersonate'],
        'User'        => [],
    ],
];
