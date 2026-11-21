<?php

declare(strict_types=1);

namespace App\Modules\Booking\Livewire;

use App\Modules\Booking\Actions\BookSession;
use App\Modules\Booking\Data\BookingRequest;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Booking\Support\Availability;
use App\Modules\Booking\Support\EffectivePolicy;
use App\Modules\Catalog\Models\Service;
use App\Modules\Membership\Contracts\MembershipSelector;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\BusinessTime;
use App\View\Format;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * BK-01 / BK-02: service → month calendar → time → confirmation.
 */
#[Layout('components.layouts.app')]
final class BookClass extends Component
{
    #[Url(as: 'service')]
    public ?int $serviceId = null;

    #[Url]
    public ?string $month = null;

    #[Url]
    public ?string $date = null;

    public ?string $sessionId = null;

    public bool $remind = true;

    public ?string $error = null;

    public function mount(BusinessTime $time): void
    {
        $this->month ??= substr($time->today(), 0, 7);
    }

    public function chooseService(int $serviceId): void
    {
        $this->serviceId = $serviceId;
        $this->reset(['date', 'sessionId', 'error']);
    }

    public function moveMonth(int $delta): void
    {
        $this->month = CarbonImmutable::parse($this->month.'-01')->addMonths($delta)->format('Y-m');
        $this->reset(['date', 'sessionId', 'error']);
    }

    public function chooseDate(string $date): void
    {
        $this->date = $date;
        $this->reset(['sessionId', 'error']);
    }

    public function chooseSession(string $publicId): void
    {
        $this->sessionId = $publicId;
        $this->error = null;
    }

    public function back(): void
    {
        match (true) {
            $this->sessionId !== null => $this->sessionId = null,
            $this->date !== null => $this->date = null,
            default => $this->serviceId = null,
        };
        $this->error = null;
    }

    public function confirm(BookSession $book): void
    {
        $session = $this->selectedSession();
        $key = 'book|'.auth()->id();

        if (! $session instanceof ClassSession || RateLimiter::tooManyAttempts($key, 10)) {
            $this->error = __('booking.session_closed');

            return;
        }

        RateLimiter::hit($key, 60);

        try {
            $booking = $book->handle(new BookingRequest(user_id: (int) auth()->id(), session_id: $session->id, remind: $this->remind));
        } catch (BookingRejected $e) {
            $this->error = $e->translated();

            return;
        }

        session()->flash('status', __('booking.notices.booked'));
        $this->redirectRoute('app.bookings.show', $booking->public_id);
    }

    public function render(Availability $availability, BusinessTime $time): View
    {
        $service = $this->serviceId === null ? null : Service::query()->where('is_active', true)->find($this->serviceId);

        return view('livewire.booking.book-class', [
            'services' => $service === null ? $availability->services() : null,
            'service' => $service,
            'days' => $service !== null && $this->date === null ? $availability->month($service, (string) $this->month) : [],
            'slots' => $service !== null && $this->date !== null ? $availability->day($service, $this->date, (int) auth()->id()) : [],
            'monthStart' => CarbonImmutable::parse($this->month.'-01'),
            'today' => $time->today(),
            'summary' => $this->summary($service, $time),
        ])->title(__('booking.book.title'));
    }

    /**
     * @return array{session: ClassSession, when: CarbonImmutable, cost: string, payable: bool, rejection: ?string, deadline: CarbonImmutable}|null
     */
    private function summary(?Service $service, BusinessTime $time): ?array
    {
        $session = $this->selectedSession();

        if (! $service instanceof Service || ! $session instanceof ClassSession) {
            return null;
        }

        $choice = app(MembershipSelector::class)->choose((int) auth()->id(), $service->id, $service->credit_cost, $time->localDate($session->starts_at)->format('Y-m-d'));
        $policy = EffectivePolicy::for($service, app(BookingPolicySettings::class));

        return [
            'session' => $session,
            'when' => $time->toLocal($session->starts_at),
            'cost' => Format::credits($service->credit_cost),
            'payable' => $choice->isFound(),
            'rejection' => $choice->rejection,
            'deadline' => $time->toLocal($session->starts_at->subMinutes($policy->cancellationCutoffMinutes)),
        ];
    }

    private function selectedSession(): ?ClassSession
    {
        return $this->sessionId === null ? null : ClassSession::query()
            ->with(['service', 'instructor'])
            ->where('public_id', $this->sessionId)
            ->where('service_id', $this->serviceId)
            ->first();
    }
}
