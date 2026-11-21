<?php

declare(strict_types=1);

namespace App\Modules\Booking\Contracts;

use Carbon\CarbonImmutable;

/**
 * Read-only view of a waitlist offer for other modules (Communication's email).
 */
final readonly class WaitlistOfferSummary
{
    public function __construct(
        public int $userId,
        public string $serviceName,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $offeredUntil,
    ) {}
}
