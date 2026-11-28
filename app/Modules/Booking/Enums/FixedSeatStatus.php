<?php

declare(strict_types=1);

namespace App\Modules\Booking\Enums;

/**
 * FS-02 to FS-04 (spec 6.1). A rejected request ends as revoked with a reason.
 */
enum FixedSeatStatus: string
{
    case Requested = 'requested';
    case Active = 'active';
    case Revoked = 'revoked';
    case Ended = 'ended';

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::Requested => [self::Active, self::Revoked, self::Ended],
            self::Active => [self::Revoked, self::Ended],
            self::Revoked, self::Ended => [],
        }, true);
    }

    /** Counts against the schedule's capacity for new requests. */
    public function holdsPlace(): bool
    {
        return $this === self::Requested || $this === self::Active;
    }

    public function label(): string
    {
        return __('booking.fixed_seats.status.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
