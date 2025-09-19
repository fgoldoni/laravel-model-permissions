<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Events;

final class ModelPermissionsHealthCheckFailed
{
    public function __construct(public readonly array $checks)
    {
    }
}
