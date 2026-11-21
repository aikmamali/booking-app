<?php

declare(strict_types=1);

namespace App\Modules\Booking\Contracts;

/**
 * Whether a customer has signed the forms required before booking (GDPR, health
 * questionnaire). Implemented by Communication with CM-06 (phase 3); until then all
 * customers pass.
 */
interface RequiredForms
{
    public function completedBy(int $userId): bool;
}
