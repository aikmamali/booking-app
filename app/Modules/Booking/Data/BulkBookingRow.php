<?php

declare(strict_types=1);

namespace App\Modules\Booking\Data;

/**
 * BK-10: the outcome of one customer × session pair. $error is a translated message.
 */
final readonly class BulkBookingRow
{
    public function __construct(
        public int $userId,
        public int $sessionId,
        public ?int $bookingId,
        public ?string $error,
    ) {}

    public function succeeded(): bool
    {
        return $this->error === null;
    }
}
