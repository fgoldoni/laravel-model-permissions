<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Events;

final readonly class ModelPermissionsHealthCheckFailed
{
    public function __construct(public array $checks)
    {
    }
}
