<?php

declare(strict_types=1);

namespace App\Modules\Membership\Enums;

enum MembershipStatus: string
{
    /** Awaiting online payment (phase 2). */
    case Pending = 'pending';
    case Active = 'active';
    /** Paused, e.g. for an injury (phase 2). */
    case Frozen = 'frozen';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::Pending => [self::Active, self::Cancelled],
            self::Active => [self::Frozen, self::Expired, self::Cancelled],
            self::Frozen => [self::Active, self::Expired, self::Cancelled],
            self::Expired, self::Cancelled => [],
        }, true);
    }

    public function label(): string
    {
        return __('membership.status.'.$this->value);
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
