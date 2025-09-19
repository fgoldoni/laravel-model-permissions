<?php

declare(strict_types=1);

namespace Goldoni\ModelPermissions\Events;

final class ModelPermissionsHealthCheckPassed
{
    public function __construct(public readonly array $checks)
    {
    }
}
