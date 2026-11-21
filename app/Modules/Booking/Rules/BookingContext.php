<?php

declare(strict_types=1);

namespace App\Modules\Booking\Rules;

use App\Modules\Booking\Support\EffectivePolicy;
use App\Modules\Membership\Contracts\MembershipChoice;
use App\Modules\Scheduling\Models\ClassSession;
use Carbon\CarbonImmutable;

/**
 * Everything a booking rule needs, gathered once by BookingContextFactory. Rules never
 * query the database or read the clock themselves (CLAUDE.md), so each one is a pure
 * function of this object and easy to unit test.
 */
final readonly class BookingContext
{
    public function __construct(
        public int $userId,
        public ClassSession $session,
        public CarbonImmutable $now,
        public EffectivePolicy $policy,
        public MembershipChoice $membership,
        public int $takenSeats,
        public bool $alreadyBooked,
        public int $bookingsThatDay,
        public int $activeBookings,
        public bool $formsCompleted,
    ) {}
}
