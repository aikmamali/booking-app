<?php

declare(strict_types=1);

namespace App\Modules\Booking\Livewire;

use App\Modules\Booking\Actions\LeaveFixedSeat;
use App\Modules\Booking\Actions\RequestFixedSeat;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\FixedSeat;
use App\Modules\Booking\Models\Season;
use App\Modules\Scheduling\Contracts\WeeklyClasses;
use App\Shared\BusinessTime;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * FS-02, customer side: the customer's fixed seats, and a request for a new one in the
 * current or next season.
 */
#[Layout('components.layouts.app')]
final class MyFixedSeats extends Component
{
    public ?int $seasonId = null;

    public ?int $scheduleId = null;

    public function mount(): void
    {
        $this->seasonId = $this->seasons()->first()?->id;
    }

    public function request(RequestFixedSeat $request): void
    {
        $this->validate(['seasonId' => ['required', 'integer'], 'scheduleId' => ['required', 'integer']]);

        try {
            $request->handle((int) auth()->id(), (int) $this->scheduleId, (int) $this->seasonId);
            session()->flash('status', __('booking.fixed_seats.requested'));
            $this->scheduleId = null;
        } catch (BookingRejected $e) {
            $this->addError('scheduleId', $e->translated());
        }
    }

    public function leave(string $publicId, LeaveFixedSeat $leave): void
    {
        $seat = FixedSeat::query()->where('public_id', $publicId)->where('user_id', auth()->id())->firstOrFail();

        try {
            $leave->handle($seat, (int) auth()->id());
            session()->flash('status', __('booking.fixed_seats.left'));
        } catch (BookingRejected $e) {
            session()->flash('status', $e->translated());
        }
    }

    public function render(WeeklyClasses $classes): View
    {
        $season = $this->seasons()->firstWhere('id', $this->seasonId);
        $seats = $this->mine();

        return view('livewire.booking.my-fixed-seats', [
            'seats' => $seats,
            'classes' => $classes->findMany(array_values(array_map(intval(...), $seats->pluck('schedule_id')->all()))),
            'seasons' => $this->seasons(),
            'choices' => $season === null ? [] : $classes->runningBetween($season->starts_on->toDateString(), $season->ends_on->toDateString()),
        ])->title(__('booking.fixed_seats.mine_title'));
    }

    /**
     * Seasons still running or still to come.
     *
     * @return Collection<int, Season>
     */
    private function seasons(): Collection
    {
        return Season::query()->whereDate('ends_on', '>=', app(BusinessTime::class)->today())->orderBy('starts_on')->get();
    }

    /**
     * @return Collection<int, FixedSeat>
     */
    private function mine(): Collection
    {
        return FixedSeat::query()->with('season')->where('user_id', auth()->id())->latest('id')->limit(30)->get();
    }
}
