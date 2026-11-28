<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Filament\Widgets;

use App\Modules\Booking\Contracts\BookingStats;
use App\Modules\Booking\Contracts\SessionOccupancyRow;
use App\Shared\BusinessTime;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;

/**
 * RP-01: today's classes with seats taken, capacity and waitlist.
 */
final class TodaySessions extends Widget
{
    /** Business figures are for staff with access to the underlying data, not instructors. */
    public static function canView(): bool
    {
        return auth()->user()?->can('view_any_class::session') ?? false;
    }

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.reporting.today-sessions';

    /**
     * @return array{rows: list<SessionOccupancyRow>, timezone: string}
     */
    protected function getViewData(): array
    {
        $time = app(BusinessTime::class);
        $today = $time->today();

        return [
            'rows' => app(BookingStats::class)->sessions(
                $time->toUtc($today, '00:00'),
                $time->toUtc(CarbonImmutable::parse($today)->addDay()->format('Y-m-d'), '00:00'),
            ),
            'timezone' => $time->timezone(),
        ];
    }
}
