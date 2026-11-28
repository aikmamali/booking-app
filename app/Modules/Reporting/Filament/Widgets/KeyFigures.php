<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Filament\Widgets;

use App\Modules\Billing\Contracts\BillingStats;
use App\Modules\Booking\Contracts\BookingStats;
use App\Modules\Membership\Contracts\MembershipStats;
use App\Shared\BusinessTime;
use App\View\Format;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * RP-01: revenue this month, active memberships, memberships ending this week and the
 * no-show rate of the last 30 days. Numbers come from each module's stats contract.
 */
final class KeyFigures extends StatsOverviewWidget
{
    /** Business figures are for staff with access to the underlying data, not instructors. */
    public static function canView(): bool
    {
        return auth()->user()?->can('view_any_payment') ?? false;
    }

    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = null;

    private function monthName(CarbonImmutable $day): string
    {
        /** @var CarbonImmutable $localized */
        $localized = $day->locale(app()->getLocale());

        return ucfirst($localized->translatedFormat('F Y'));
    }

    /**
     * @return list<Stat>
     */
    protected function getStats(): array
    {
        $time = app(BusinessTime::class);
        $today = CarbonImmutable::parse($time->today());
        $monthStart = $time->toUtc($today->startOfMonth()->format('Y-m-d'), '00:00');
        $tomorrow = $time->toUtc($today->addDay()->format('Y-m-d'), '00:00');
        $noShow = app(BookingStats::class)->noShowRate($tomorrow->subDays(31), $tomorrow);

        return [
            Stat::make(__('reporting.revenue_month'), Format::money(app(BillingStats::class)->revenue($monthStart, $tomorrow)))
                ->description($this->monthName($today)),
            Stat::make(__('reporting.active_memberships'), (string) app(MembershipStats::class)->activeOn($today->format('Y-m-d'))),
            Stat::make(__('reporting.ending_this_week'), (string) app(MembershipStats::class)->endingBetween($today->format('Y-m-d'), $today->endOfWeek()->format('Y-m-d')))
                ->description(__('reporting.ending_hint')),
            Stat::make(__('reporting.no_show_rate'), $noShow === null ? '—' : round($noShow * 100).'%')
                ->description(__('reporting.last_30_days')),
        ];
    }
}
