<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Enums;

/**
 * ISO-8601 day of week, matching Carbon's dayOfWeekIso.
 */
enum Weekday: int
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    public function label(): string
    {
        return __('scheduling.weekdays.'.$this->value);
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $day) {
            $options[$day->value] = $day->label();
        }

        return $options;
    }
}
