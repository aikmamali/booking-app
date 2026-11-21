<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Policies;

use App\Shared\Policies\PermissionPolicy;

final class SchedulePolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'schedule';
    }
}
