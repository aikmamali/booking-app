<?php

declare(strict_types=1);

namespace App\Modules\Booking\Contracts;

final class NoRequiredForms implements RequiredForms
{
    public function completedBy(int $userId): bool
    {
        return true;
    }
}
