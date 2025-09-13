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
        Event::class,
        Order::class,
        Ticket::class,
        Artist::class,
        Transaction::class,
        Video::class,
    ],
    'abilities' => [
        'viewAny', 'view', 'create', 'update', 'delete', 'deleteAny',
        'restore', 'restoreAny', 'forceDelete', 'forceDeleteAny',
        'replicate', 'reorder', 'attach', 'attachAny', 'detach', 'detachAny',
    ],
    'global_permissions' => [
        'impersonate', 'accessAdmin', 'viewReports',
    ],
    'role_ability_map' => [
        'Super Admin' => ['*'],
        'Manager'     => ['viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'restore', 'replicate', 'reorder', 'attach', 'attachAny', 'detach', 'detachAny'],
        'User'        => ['viewAny', 'view', 'create', 'update'],
    ],
    'role_model_ability_map' => [
        'Manager' => [
            User::class        => ['viewAny', 'view', 'update', 'delete', 'deleteAny'],
            Event::class       => ['*'],
            Order::class       => ['*'],
            Ticket::class      => ['*'],
            Artist::class      => ['viewAny', 'view', 'create', 'update', 'attach', 'detach'],
            Transaction::class => ['viewAny', 'view', 'create', 'update'],
            Video::class       => ['viewAny', 'view', 'create', 'update'],
        ],
        'User' => [
            Ticket::class => ['viewAny', 'view', 'create'],
            Order::class  => ['viewAny', 'view'],
            Event::class  => ['viewAny', 'view'],
        ],
    ],
    'role_global_permissions' => [
        'Super Admin' => ['*'],
        'Manager'     => ['viewReports', 'accessAdmin'],
        'User'        => [],
    ],
];
