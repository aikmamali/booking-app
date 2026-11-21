<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Policies;

use App\Shared\Policies\PermissionPolicy;

final class ServicePolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'service';
    }
}
