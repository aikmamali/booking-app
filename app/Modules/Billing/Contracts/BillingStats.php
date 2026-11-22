<?php

declare(strict_types=1);

namespace App\Modules\Billing\Contracts;

use App\Modules\Billing\Enums\PaymentStatus;
use App\Modules\Billing\Models\Payment;
use App\Shared\Money;
use Carbon\CarbonImmutable;

/**
 * Public read contract of Billing for the Reporting dashboard.
 */
final class BillingStats
{
    /**
     * Money collected in [from, to).
     */
    public function revenue(CarbonImmutable $from, CarbonImmutable $to): Money
    {
        return Money::ofCents((int) Payment::query()
            ->where('status', PaymentStatus::Succeeded)
            ->where('paid_at', '>=', $from)
            ->where('paid_at', '<', $to)
            ->sum('amount_cents'));
    }
}
