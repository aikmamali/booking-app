<?php

declare(strict_types=1);

namespace App\Modules\Billing\Policies;

use App\Shared\Policies\PermissionPolicy;

final class PaymentPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'payment';
    }
}
