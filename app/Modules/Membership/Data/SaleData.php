<?php

declare(strict_types=1);

namespace App\Modules\Membership\Data;

use App\Modules\Billing\Enums\PaymentMethod;
use Spatie\LaravelData\Data;

/**
 * MB-02: a package sold at the desk. starts_on is a Y-m-d date in the business time zone.
 */
final class SaleData extends Data
{
    public function __construct(
        public int $user_id,
        public int $package_id,
        public string $starts_on,
        public PaymentMethod $payment_method,
    ) {}
}
