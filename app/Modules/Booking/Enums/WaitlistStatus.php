<?php

declare(strict_types=1);

namespace App\Modules\Booking\Enums;

enum WaitlistStatus: string
{
    case Waiting = 'waiting';
    /** A seat is held for this customer until offered_until (offer strategy). */
    case Offered = 'offered';
    /** Became a booking. */
    case Promoted = 'promoted';
    /** Offer not accepted in time, auto-book failed, or the session started or was cancelled. */
    case Expired = 'expired';
    /** The customer left the list (WL-03). */
    case Left = 'left';

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::Waiting => [self::Offered, self::Promoted, self::Expired, self::Left],
            self::Offered => [self::Promoted, self::Expired, self::Left],
            self::Promoted, self::Expired, self::Left => [],
        }, true);
    }

    public function isLive(): bool
    {
        return $this === self::Waiting || $this === self::Offered;
    }

    public function label(): string
    {
        return __('booking.waitlist.status.'.$this->value);
    }
}
