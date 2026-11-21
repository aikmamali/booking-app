<?php

declare(strict_types=1);

namespace App\Modules\Booking\Data;

use App\Modules\Booking\Enums\BookingSource;
use Spatie\LaravelData\Data;

/**
 * BK-02 / BK-07: who books which session. A staff override needs a reason, which is
 * written to the audit log.
 */
final class BookingRequest extends Data
{
    public function __construct(
        public int $user_id,
        public int $session_id,
        public BookingSource $source = BookingSource::Customer,
        public bool $remind = true,
        public ?int $actor_id = null,
        public ?string $override_reason = null,
    ) {}

    public function isOverride(): bool
    {
        return $this->override_reason !== null && trim($this->override_reason) !== '';
    }
}
