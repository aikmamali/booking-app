<?php

declare(strict_types=1);

namespace App\Modules\Communication\Policies;

use App\Shared\Policies\PermissionPolicy;

final class AnnouncementPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'announcement';
    }
}
