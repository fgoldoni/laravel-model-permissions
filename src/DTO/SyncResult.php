<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\DTO;

class SyncResult
{
    public function __construct(public int $created, public int $existing, public array $allPermissionNames)
    {
    }
}
