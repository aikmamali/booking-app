<?php

declare(strict_types=1);

namespace App\Modules\Booking\Contracts;

use Carbon\CarbonImmutable;

/**
 * Read-only view of a booking for other modules (e.g. Communication's emails).
 */
final readonly class BookingSummary
{
    public function __construct(
        public int $id,
        public string $publicId,
        public string $reference,
        public int $userId,
        public string $serviceName,
        public string $instructorName,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public string $status,
        public ?string $cancelReason,
        public string $ics,
        public string $googleCalendarUrl,
    ) {}
}
