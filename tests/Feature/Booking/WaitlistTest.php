<?php

declare(strict_types=1);

use App\Modules\Booking\Actions\AcceptWaitlistOffer;
use App\Modules\Booking\Actions\CancelBooking;
use App\Modules\Booking\Actions\ExpireWaitlistOffers;
use App\Modules\Booking\Actions\JoinWaitlist;
use App\Modules\Booking\Actions\LeaveWaitlist;
use App\Modules\Booking\Actions\PromoteFromWaitlist;
use App\Modules\Booking\Data\CancellationRequest;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\WaitlistStatus;
use App\Modules\Booking\Enums\WaitlistStrategy;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Livewire\BookClass;
use App\Modules\Booking\Livewire\MyBookings;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\WaitlistEntry;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Catalog\Data\PolicyOverrides;
use App\Modules\Communication\Enums\NotificationType;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Actions\CancelSession;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo('2027-03-20 08:00:00');
    $this->session = bookableSession(['capacity' => 1]); // 23 March 16:30 UTC
    $this->holder = customerFor($this->session);
    $this->seat = book($this->holder, $this->session);
});

function joinQueue(User $customer, $session): WaitlistEntry
{
    return app(JoinWaitlist::class)->handle($customer->id, $session->id);
}

function freeTheSeat(Booking $booking): void
{
    app(CancelBooking::class)->handle($booking, new CancellationRequest(actor_id: $booking->user_id));
}

describe('joining (WL-01) and leaving (WL-03)', function (): void {
    it('queues customers in order without reserving credits', function (): void {
        $first = customerFor($this->session);
        $second = customerFor($this->session);

        $a = joinQueue($first, $this->session);
        $b = joinQueue($second, $this->session);

        expect($a->status)->toBe(WaitlistStatus::Waiting)
            ->and($a->position())->toBe(1)
            ->and($b->position())->toBe(2)
            ->and(creditsOf($first))->toBe('10.00');

        app(LeaveWaitlist::class)->handle($a);
        expect($b->position())->toBe(1)
            ->and(joinQueue($first, $this->session)->position())->toBe(2); // rejoining goes to the back
    });

    it('refuses to queue when it makes no sense', function (Closure $arrange, string $reason): void {
        $customer = customerFor($this->session);
        $arrange($this, $customer);

        expect(fn (): WaitlistEntry => joinQueue($customer, $this->session->fresh()))->toThrow(BookingRejected::class, $reason);
    })->with([
        'seats still free' => [fn ($t) => $t->session->update(['capacity' => 2]), 'booking.waitlist.not_full'],
        'already booked' => [fn ($t, $c) => $t->seat->user()->associate($c)->save(), 'booking.duplicate'],
        'already waiting' => [fn ($t, $c): WaitlistEntry => joinQueue($c, $t->session), 'booking.waitlist.already_waiting'],
        'list full' => [function ($t): void {
            $s = app(BookingPolicySettings::class);
            $s->waitlist_max_size = 1;
            $s->save();
            joinQueue(customerFor($t->session), $t->session);
        }, 'booking.waitlist.list_full'],
        'disabled for the service' => [fn ($t) => $t->session->service->update(['policy_overrides' => new PolicyOverrides(waitlist_enabled: false)]), 'booking.waitlist.disabled'],
        'inside the cut-off (60 minutes)' => [fn ($t) => $t->travelTo('2027-03-23 15:30:00'), 'booking.waitlist.closed'],
        'session cancelled' => [fn ($t) => $t->session->update(['status' => 'cancelled']), 'booking.waitlist.closed'],
    ]);

    it('has a unique index for live entries', function (): void {
        $customer = customerFor($this->session);
        joinQueue($customer, $this->session);

        WaitlistEntry::query()->insert([
            'public_id' => (string) Str::ulid(), 'user_id' => $customer->id,
            'class_session_id' => $this->session->id, 'status' => 'waiting', 'created_at' => now(), 'updated_at' => now(),
        ]);
    })->throws(QueryException::class);
});

describe('auto_book (default)', function (): void {
    it('books and charges the first in line when a seat frees up', function (): void {
        Notification::fake();
        $first = customerFor($this->session);
        $second = customerFor($this->session);
        $entry = joinQueue($first, $this->session);
        joinQueue($second, $this->session);

        freeTheSeat($this->seat);

        $booking = Booking::query()->where('user_id', $first->id)->sole();
        expect($booking->source)->toBe(BookingSource::Waitlist)
            ->and($entry->fresh()?->status)->toBe(WaitlistStatus::Promoted)
            ->and($entry->fresh()?->booking_id)->toBe($booking->id)
            ->and(creditsOf($first))->toBe('9.00')
            ->and(Booking::query()->where('user_id', $second->id)->exists())->toBeFalse();
        assertNotified($first, NotificationType::WaitlistPromoted);
    });

    it('skips a customer who cannot be booked and tries the next', function (): void {
        $broke = User::factory()->customer()->create();
        $next = customerFor($this->session);
        $skipped = joinQueue($broke, $this->session);
        joinQueue($next, $this->session);

        freeTheSeat($this->seat);

        expect($skipped->fresh()?->status)->toBe(WaitlistStatus::Expired)
            ->and(Booking::query()->active()->where('user_id', $next->id)->exists())->toBeTrue();
    });

    it('does not promote inside the cut-off', function (): void {
        $waiting = joinQueue(customerFor($this->session), $this->session);
        $this->travelTo('2027-03-23 15:31:00'); // 59 minutes before

        Booking::query()->whereKey($this->seat->id)->update(['status' => 'cancelled']);
        app(PromoteFromWaitlist::class)->handle($this->session->id);

        expect($waiting->fresh()?->status)->toBe(WaitlistStatus::Waiting);

        app(ExpireWaitlistOffers::class)->handle();
        expect($waiting->fresh()?->status)->toBe(WaitlistStatus::Expired);
    });

    it('closes the queue when the session is cancelled', function (): void {
        $waiting = joinQueue(customerFor($this->session), $this->session);

        app(CancelSession::class)->handle($this->session, 'Βλάβη');

        expect($waiting->fresh()?->status)->toBe(WaitlistStatus::Expired)
            ->and(Booking::query()->where('source', 'waitlist')->exists())->toBeFalse();
    });
});

describe('offer', function (): void {
    beforeEach(function (): void {
        $s = app(BookingPolicySettings::class);
        $s->waitlist_strategy = WaitlistStrategy::Offer;
        $s->save();
        Notification::fake();
        $this->first = customerFor($this->session);
        $this->second = customerFor($this->session);
        $this->entry = joinQueue($this->first, $this->session);
        $this->next = joinQueue($this->second, $this->session);
        freeTheSeat($this->seat);
    });

    it('holds the seat for 30 minutes and emails the offer', function (): void {
        expect($this->entry->fresh()?->status)->toBe(WaitlistStatus::Offered)
            ->and($this->entry->fresh()?->offered_until?->toDateTimeString())->toBe('2027-03-20 08:30:00')
            ->and(creditsOf($this->first))->toBe('10.00');
        assertNotified($this->first, NotificationType::WaitlistOffered);

        expect(fn (): Booking => book(customerFor($this->session), $this->session))->toThrow(BookingRejected::class, 'booking.full');
    });

    it('books the customer who accepts in time', function (): void {
        $booking = app(AcceptWaitlistOffer::class)->handle($this->entry->fresh());

        expect($booking->user_id)->toBe($this->first->id)
            ->and($this->entry->fresh()?->status)->toBe(WaitlistStatus::Promoted)
            ->and(creditsOf($this->first))->toBe('9.00');
        assertNotified($this->first, NotificationType::WaitlistPromoted);
    });

    it('passes the seat on when the offer lapses', function (): void {
        $this->travelTo('2027-03-20 08:30:00');

        app(ExpireWaitlistOffers::class)->handle();

        expect($this->entry->fresh()?->status)->toBe(WaitlistStatus::Expired)
            ->and($this->next->fresh()?->status)->toBe(WaitlistStatus::Offered)
            ->and(fn () => app(AcceptWaitlistOffer::class)->handle($this->entry->fresh()))->toThrow(BookingRejected::class, 'booking.waitlist.offer_expired');
    });

    it('passes the seat on when the customer declines', function (): void {
        app(LeaveWaitlist::class)->handle($this->entry->fresh());

        expect($this->next->fresh()?->status)->toBe(WaitlistStatus::Offered);
    });

    it('never lets an offer run past the cut-off', function (): void {
        $this->travelTo('2027-03-23 15:10:00'); // 80 minutes before
        $late = bookableSession(['capacity' => 1, 'starts_at' => '2027-03-23 16:30:00', 'ends_at' => '2027-03-23 17:30:00'], $this->session->service);
        $seat = book(customerFor($late), $late);
        $entry = joinQueue(customerFor($late), $late);

        // Too late for the customer to cancel; the front desk cancels for them.
        app(CancelBooking::class)->handle($seat, new CancellationRequest(actor_id: User::factory()->admin()->create()->id, by_staff: true));

        expect($entry->fresh()?->offered_until?->toDateTimeString())->toBe('2027-03-23 15:30:00');
    });
});

describe('screens', function (): void {
    it('offers the waitlist on a full time and shows the place in the queue', function (): void {
        $customer = customerFor($this->session);

        Livewire::actingAs($customer)->test(BookClass::class, ['serviceId' => $this->session->service_id])
            ->set('date', '2027-03-23')
            ->assertSee(__('booking.waitlist.join'))
            ->assertSee('0 σε αναμονή')
            ->call('joinWaitlist', $this->session->public_id)
            ->assertSee(__('booking.waitlist.your_position', ['position' => 1]))
            ->call('leaveWaitlist', $this->session->public_id)
            ->assertSee(__('booking.waitlist.join'));
    });

    it('lists offers in my bookings and accepts them', function (): void {
        $s = app(BookingPolicySettings::class);
        $s->waitlist_strategy = WaitlistStrategy::Offer;
        $s->save();
        $customer = customerFor($this->session);
        joinQueue($customer, $this->session);
        freeTheSeat($this->seat);
        $entry = WaitlistEntry::query()->where('user_id', $customer->id)->sole();

        Livewire::actingAs($customer)->test(MyBookings::class)
            ->assertSee(__('booking.waitlist.offer_until', ['time' => '10:30']))
            ->call('acceptOffer', $entry->public_id);

        expect(Booking::query()->active()->where('user_id', $customer->id)->exists())->toBeTrue();
    });
});

it('expires lapsed offers every minute', function (): void {
    $event = collect(app(Schedule::class)->events())->first(fn ($e): bool => $e->description === 'expire-waitlist-offers');

    expect($event?->expression)->toBe('* * * * *')->and($event?->withoutOverlapping)->toBeTrue();
});
