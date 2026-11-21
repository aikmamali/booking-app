<?php

declare(strict_types=1);

namespace App\Modules\Billing\Data;

use App\Modules\Billing\Enums\PaymentMethod;
use Spatie\LaravelData\Data;

/**
 * BL-01: a payment collected at the desk. payable_* links it to what was paid for.
 */
final class PaymentData extends Data
{
    public function __construct(
        public int $user_id,
        public int $amount_cents,
        public PaymentMethod $method,
        public ?string $payable_type = null,
        public ?int $payable_id = null,
        public ?int $recorded_by = null,
        public ?string $note = null,
    ) {}
}
