<?php

declare(strict_types=1);

namespace App\Modules\Booking\Livewire;

use App\Modules\Booking\Actions\CancelBooking;
use App\Modules\Booking\Actions\RescheduleBooking;
use App\Modules\Booking\Data\CancellationRequest;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Booking\Support\Availability;
use App\Modules\Booking\Support\BookingCalendar;
use App\Modules\Booking\Support\CancellationTerms;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\BusinessTime;
use App\Shared\Clock;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

/**
 * BK-04 / BK-05 / BK-06: booking details, cancel with refund preview, reschedule.
 */
#[Layout('components.layouts.app')]
final class BookingDetails extends Component
{
    #[Locked]
    public string $publicId;

    public string $reason = '';

    public bool $confirmingCancel = false;

    public bool $rescheduling = false;

    public ?string $newSessionId = null;

    public ?string $error = null;

    public function mount(Booking $booking): void
    {
        $this->authorize('view', $booking);
        $this->publicId = $booking->public_id;
    }

    public function cancel(CancelBooking $cancel): void
    {
        $this->run(fn (): Booking => $cancel->handle($this->booking(), new CancellationRequest(
            actor_id: (int) auth()->id(),
            reason: trim($this->reason) === '' ? null : mb_substr($this->reason, 0, 255),
        )), 'booking.notices.cancelled');
    }

    public function reschedule(RescheduleBooking $reschedule): void
    {
        $target = ClassSession::query()->where('public_id', (string) $this->newSessionId)->first();

        if ($target === null) {
            $this->error = __('booking.session_closed');

            return;
        }

        $moved = $this->run(fn (): Booking => $reschedule->handle($this->booking(), $target->id, (int) auth()->id()), 'booking.notices.rescheduled');

        if ($moved instanceof Booking) {
            $this->redirectRoute('app.bookings.show', $moved->public_id);
        }
    }

    public function render(BusinessTime $time, Clock $clock, BookingPolicySettings $settings, Availability $availability): View
    {
        $booking = $this->booking();
        $terms = CancellationTerms::for($booking, $clock->now(), $settings);

        return view('livewire.booking.booking-details', [
            'booking' => $booking,
            'when' => $time->toLocal($booking->session->starts_at),
            'terms' => $terms,
            'deadline' => $time->toLocal($terms->deadline),
            'history' => Activity::query()->forSubject($booking)->oldest('id')->get(),
            'alternatives' => $this->rescheduling ? $this->alternatives($booking, $availability, $time) : [],
            'googleUrl' => BookingCalendar::googleUrl($booking),
        ])->title(__('booking.details.title', ['reference' => $booking->reference()]));
    }

    /**
     * @return list<array{public_id: string, date: string, time: string, free: int, capacity: int, state: string, mine: bool}>
     */
    private function alternatives(Booking $booking, Availability $availability, BusinessTime $time): array
    {
        $slots = [];
        $day = CarbonImmutable::parse($time->today());

        foreach (range(0, 13) as $i) {
            foreach ($availability->day($booking->session->service, $day->addDays($i)->format('Y-m-d'), $booking->user_id) as $slot) {
                if ($slot['state'] === Availability::AVAILABLE && ! $slot['mine']) {
                    $slots[] = $slot;
                }
            }
        }

        return $slots;
    }

    private function run(Closure $action, string $notice): mixed
    {
        $this->authorize('manage', $this->booking());

        try {
            $result = $action();
        } catch (BookingRejected $e) {
            $this->error = $e->translated();

            return null;
        }

        $this->reset(['confirmingCancel', 'rescheduling', 'reason', 'error']);
        session()->flash('status', __($notice));

        return $result;
    }

    private function booking(): Booking
    {
        return Booking::query()->with(['session.service', 'session.instructor'])->where('public_id', $this->publicId)->firstOrFail();
    }
}
