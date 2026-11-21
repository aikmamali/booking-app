<?php

declare(strict_types=1);

namespace App\Modules\Booking\Data;

use Spatie\LaravelData\Data;

/**
 * BK-04 / BK-07. Staff may cancel after the cut-off (a late cancel without refund) and may
 * still refund it by giving an override reason.
 */
final class CancellationRequest extends Data
{
    public function __construct(
        public ?int $actor_id = null,
        public ?string $reason = null,
        public bool $by_staff = false,
        public ?string $refund_override_reason = null,
    ) {}
}
