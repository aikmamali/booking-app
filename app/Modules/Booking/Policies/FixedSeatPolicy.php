<?php

declare(strict_types=1);

namespace App\Modules\Booking\Policies;

use App\Shared\Policies\PermissionPolicy;

final class FixedSeatPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'fixed::seat';
    }
}
