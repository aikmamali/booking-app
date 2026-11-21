<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Policies;

use App\Shared\Policies\PermissionPolicy;

final class ClassSessionPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'class::session';
    }
}
