<?php

declare(strict_types=1);

namespace App\Modules\Booking\Livewire;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Shared\BusinessTime;
use App\Shared\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * BK-06: Upcoming, Week, Month and History views of the customer's bookings.
 */
#[Layout('components.layouts.app')]
final class MyBookings extends Component
{
    public const array VIEWS = ['upcoming', 'week', 'month', 'history'];

    #[Url]
    public string $view = 'upcoming';

    #[Url]
    public int $offset = 0;

    public function show(string $view): void
    {
        $this->view = in_array($view, self::VIEWS, true) ? $view : 'upcoming';
        $this->offset = 0;
    }

    public function move(int $delta): void
    {
        $this->offset += $delta;
    }

    public function render(BusinessTime $time, Clock $clock): View
    {
        [$from, $to] = $this->range($time, $clock);

        return view('livewire.booking.my-bookings', [
            'groups' => $this->groups($this->query($clock, $from, $to)->get(), $time),
            'from' => $from,
            'to' => $to?->subDay(),
        ])->title(__('booking.mine.title'));
    }

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable} local dates [from, to)
     */
    private function range(BusinessTime $time, Clock $clock): array
    {
        $today = $time->localDate($clock->now());

        return match ($this->view) {
            'week' => [$s = $today->startOfWeek()->addWeeks($this->offset), $s->addWeek()],
            'month' => [$s = $today->startOfMonth()->addMonths($this->offset), $s->addMonth()],
            default => [null, null],
        };
    }

    /**
     * @return Builder<Booking>
     */
    private function query(Clock $clock, ?CarbonImmutable $from, ?CarbonImmutable $to): Builder
    {
        $time = app(BusinessTime::class);
        $now = $clock->now();
        $query = Booking::query()->with('session.service')->where('user_id', auth()->id())
            ->whereNot('bookings.status', BookingStatus::Expired)
            ->join('class_sessions', 'class_sessions.id', '=', 'bookings.class_session_id')
            ->select('bookings.*');

        return match ($this->view) {
            'upcoming' => $query->whereIn('bookings.status', BookingStatus::active())->where('class_sessions.starts_at', '>', $now)->orderBy('class_sessions.starts_at'),
            'history' => $query->where(fn ($q) => $q->where('class_sessions.starts_at', '<=', $now)->orWhereNotIn('bookings.status', BookingStatus::active()))->orderByDesc('class_sessions.starts_at')->limit(100),
            default => $query->where('class_sessions.starts_at', '>=', $time->toUtc((string) $from?->format('Y-m-d'), '00:00'))
                ->where('class_sessions.starts_at', '<', $time->toUtc((string) $to?->format('Y-m-d'), '00:00'))
                ->orderBy('class_sessions.starts_at'),
        };
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Booking>  $bookings
     * @return Collection<string, Collection<int, Booking>>
     */
    private function groups($bookings, BusinessTime $time): Collection
    {
        return $bookings->toBase()->groupBy(fn (Booking $b): string => $time->toLocal($b->session->starts_at)->format('Y-m-d'));
    }
}
