<?php

declare(strict_types=1);

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Filament\Resources\BookingResource\Pages\ListBookings;
use App\Modules\Booking\Livewire\BookClass;
use App\Modules\Booking\Livewire\BookingDetails;
use App\Modules\Booking\Livewire\MyBookings;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Booking\Support\Availability;
use App\Modules\Identity\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo('2027-03-20 08:00:00');
    $this->session = bookableSession();
    $this->customer = customerFor($this->session);
});

describe('booking flow', function (): void {
    it('walks from service to month to time to confirmation', function (): void {
        $component = Livewire::actingAs($this->customer)->test(BookClass::class)
            ->assertSee($this->session->service->name)
            ->call('chooseService', $this->session->service_id)
            ->assertSet('month', '2027-03')
            ->assertViewHas('days', ['2027-03-23' => Availability::AVAILABLE])
            ->call('chooseDate', '2027-03-23')
            ->assertSee('18:30')
            ->assertSee('5 ελεύθερες θέσεις')
            ->call('chooseSession', $this->session->public_id)
            ->assertSee(__('booking.book.confirm_title'))
            ->assertSee('23/03 15:30') // refundable until 3 hours before
            ->call('confirm');

        $booking = Booking::query()->sole();
        $component->assertRedirect(route('app.bookings.show', $booking->public_id));
        expect($booking->user_id)->toBe($this->customer->id)->and(creditsOf($this->customer))->toBe('9.00');
    });

    it('shows full and closed days', function (): void {
        bookableSession(['capacity' => 0, 'starts_at' => '2027-03-24 16:30:00', 'ends_at' => '2027-03-24 17:30:00'], $this->session->service);
        bookableSession(['status' => 'cancelled', 'starts_at' => '2027-03-25 16:30:00', 'ends_at' => '2027-03-25 17:30:00'], $this->session->service);

        Livewire::actingAs($this->customer)->test(BookClass::class, ['serviceId' => $this->session->service_id])
            ->assertViewHas('days', [
                '2027-03-23' => Availability::AVAILABLE,
                '2027-03-24' => Availability::FULL,
                '2027-03-25' => Availability::CLOSED,
            ]);
    });

    it('explains why a customer cannot book', function (): void {
        $broke = User::factory()->customer()->create();

        Livewire::actingAs($broke)->test(BookClass::class, ['serviceId' => $this->session->service_id])
            ->set('date', '2027-03-23')
            ->call('chooseSession', $this->session->public_id)
            ->assertSee(__('booking.no_membership'))
            ->call('confirm')
            ->assertSet('error', __('booking.no_membership'));

        expect(Booking::query()->count())->toBe(0);
    });
});

describe('my bookings', function (): void {
    it('lists upcoming, week, month and history', function (): void {
        $booking = book($this->customer, $this->session);
        $component = Livewire::actingAs($this->customer)->test(MyBookings::class);

        $component->call('show', 'upcoming')->assertSee($this->session->service->name)
            ->call('show', 'month')->assertSee($this->session->service->name)
            ->call('show', 'week')->assertSee('15/03 – 21/03/2027')->assertSee(__('booking.mine.empty'))
            ->call('move', 1)->assertSee('22/03 – 28/03/2027')->assertSee($this->session->service->name);

        $component->call('show', 'history')->assertSee(__('booking.mine.empty'));

        $this->travelTo('2027-03-24 08:00:00');
        $component->call('show', 'history')->assertSee($this->session->service->name)
            ->call('show', 'upcoming')->assertSee(__('booking.mine.empty'));
        expect($booking)->not->toBeNull();
    });

    it('shows details, history and calendar links, and cancels with refund', function (): void {
        $booking = book($this->customer, $this->session);

        $this->actingAs($this->customer)->get(route('app.bookings.show', $booking->public_id))
            ->assertOk()->assertSee($booking->reference())->assertSee('calendar.google.com');

        Livewire::actingAs($this->customer)->test(BookingDetails::class, ['booking' => $booking])
            ->assertSee(__('booking.history.booked'))
            ->assertSee('23/03 15:30')
            ->set('reason', 'Ταξίδι')
            ->call('cancel');

        expect($booking->fresh()?->status)->toBe(BookingStatus::Cancelled)->and(creditsOf($this->customer))->toBe('10.00');
    });

    it('reschedules to another time', function (): void {
        $booking = book($this->customer, $this->session);
        $target = bookableSession(['starts_at' => '2027-03-26 16:30:00', 'ends_at' => '2027-03-26 17:30:00'], $this->session->service);

        $component = Livewire::actingAs($this->customer)->test(BookingDetails::class, ['booking' => $booking])
            ->set('rescheduling', true)
            ->assertSee('26/03')
            ->set('newSessionId', $target->public_id)
            ->call('reschedule');

        $moved = Booking::query()->where('class_session_id', $target->id)->sole();
        $component->assertRedirect(route('app.bookings.show', $moved->public_id));
    });

    it('disables cancelling after the cut-off and explains why', function (): void {
        $booking = book($this->customer, $this->session);
        $this->travelTo('2027-03-23 14:00:00');

        Livewire::actingAs($this->customer)->test(BookingDetails::class, ['booking' => $booking])
            ->assertSee(__('booking.details.too_late', ['time' => '23/03 15:30']))
            ->assertDontSee(__('booking.details.cancel'));
    });

    it('downloads an .ics file', function (): void {
        $booking = book($this->customer, $this->session);

        $this->actingAs($this->customer)->get(route('app.bookings.ics', $booking->public_id))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertSee('DTSTART:20270323T163000Z', false);
    });

    it('keeps other customers out', function (): void {
        $booking = book($this->customer, $this->session);
        $stranger = User::factory()->customer()->create();

        $this->actingAs($stranger)->get(route('app.bookings.show', $booking->public_id))->assertForbidden();
        $this->actingAs($stranger)->get(route('app.bookings.ics', $booking->public_id))->assertForbidden();
    });

    it('shows the next classes on the home page', function (): void {
        book($this->customer, $this->session);

        $this->actingAs($this->customer)->get(route('app.home'))->assertOk()->assertSee($this->session->service->name);
    });
});

describe('admin', function (): void {
    beforeEach(fn () => $this->actingAs($this->admin = User::factory()->admin()->create()));

    it('books for a customer, overriding a limit with a reason', function (): void {
        $settings = app(BookingPolicySettings::class);
        $settings->booking_opens_days_ahead = 1;
        $settings->save();

        Livewire::test(ListBookings::class)
            ->callAction('book', ['user_id' => $this->customer->id, 'session_id' => $this->session->id, 'override_reason' => 'Τηλεφωνική κράτηση'])
            ->assertHasNoActionErrors()
            ->assertNotified(__('booking.notices.booked'));

        expect(Booking::query()->sole()->source->value)->toBe('staff');
    });

    it('cancels late with a refund override and marks attendance', function (): void {
        $late = book($this->customer, $this->session);
        $attended = book(customerFor($this->session), $this->session);
        $this->travelTo('2027-03-23 15:00:00');

        Livewire::test(ListBookings::class)
            ->callTableAction('cancel', $late, ['refund_override_reason' => 'Ασθένεια']);
        expect(creditsOf($this->customer))->toBe('10.00');

        $this->travelTo('2027-03-23 16:40:00');
        Livewire::test(ListBookings::class)->callTableAction('attended', $attended);
        expect($attended->fresh()?->status)->toBe(BookingStatus::Attended);
    });

    it('filters the participants of one session', function (): void {
        $mine = book($this->customer, $this->session);
        $elsewhere = Booking::factory()->create();

        Livewire::test(ListBookings::class)
            ->filterTable('session', ['session' => $this->session->public_id])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$elsewhere]);
    });

    it('keeps customers out of the admin bookings', function (): void {
        $this->actingAs($this->customer)->get('/admin/bookings')->assertForbidden();
    });
});
