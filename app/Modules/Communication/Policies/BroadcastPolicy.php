<?php

declare(strict_types=1);

namespace App\Modules\Communication\Policies;

use App\Shared\Policies\PermissionPolicy;

final class BroadcastPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'broadcast';
    }
}
