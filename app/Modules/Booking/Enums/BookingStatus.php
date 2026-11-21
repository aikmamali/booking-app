<?php

declare(strict_types=1);

namespace App\Modules\Booking\Enums;

/**
 * Booking lifecycle (spec 7.1). Every change goes through canTransitionTo().
 */
enum BookingStatus: string
{
    case Confirmed = 'confirmed';
    case PendingPayment = 'pending_payment';
    case PendingCredits = 'pending_credits';
    case Cancelled = 'cancelled';
    case Attended = 'attended';
    case NoShow = 'no_show';
    case Expired = 'expired';

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::PendingPayment, self::PendingCredits => [self::Confirmed, self::Expired, self::Cancelled],
            self::Confirmed => [self::Cancelled, self::Attended, self::NoShow],
            self::Cancelled, self::Attended, self::NoShow, self::Expired => [],
        }, true);
    }

    /** Holds a seat (counts towards capacity and the one-per-session rule). */
    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Confirmed, self::PendingPayment, self::PendingCredits];
    }

    public function label(): string
    {
        return __('booking.status.'.$this->value);
    }
}
