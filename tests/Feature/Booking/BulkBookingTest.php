<?php

declare(strict_types=1);

use App\Modules\Booking\Actions\BookInBulk;
use App\Modules\Booking\Data\BulkBookingRow;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Filament\Resources\BookingResource\Pages\ListBookings;
use App\Modules\Booking\Models\Booking;
use App\Modules\Identity\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo('2027-03-20 08:00:00');
    $this->admin = User::factory()->admin()->create();
});

it('books every customer into every session and charges credits', function (): void {
    $a = bookableSession();
    $b = bookableSession(['starts_at' => '2027-03-24 16:30:00', 'ends_at' => '2027-03-24 17:30:00'], $a->service);
    $c1 = customerFor($a);
    $c2 = customerFor($a);

    $rows = app(BookInBulk::class)->handle([$c1->id, $c2->id], [$a->id, $b->id], $this->admin->id);

    expect($rows)->toHaveCount(4)
        ->and(array_filter($rows, fn (BulkBookingRow $r): bool => ! $r->succeeded()))->toBe([])
        ->and(Booking::query()->where('source', BookingSource::Staff)->count())->toBe(4)
        ->and(creditsOf($c1))->toBe('8.00');
});

it('reports failures per row and keeps the successful ones', function (): void {
    $session = bookableSession(['capacity' => 1]);
    $first = customerFor($session);
    $second = customerFor($session);
    $broke = User::factory()->customer()->create();

    $rows = app(BookInBulk::class)->handle([$first->id, $second->id, $broke->id], [$session->id], $this->admin->id);

    expect($rows[0]->succeeded())->toBeTrue()
        ->and($rows[1]->error)->toBe(__('booking.full'))
        ->and($rows[2]->error)->not->toBeNull()
        ->and(Booking::query()->count())->toBe(1);
});

it('refuses empty and oversized requests', function (): void {
    $bulk = app(BookInBulk::class);

    expect(fn () => $bulk->handle([], [1], $this->admin->id))->toThrow(BookingRejected::class, 'booking.bulk.empty')
        ->and(fn () => $bulk->handle(range(1, 31), range(1, 10), $this->admin->id))->toThrow(BookingRejected::class, 'booking.bulk.too_many');
});

it('runs from the bookings list and shows the outcome', function (): void {
    $session = bookableSession(['capacity' => 1]);
    $first = customerFor($session);
    $second = customerFor($session);
    $this->actingAs($this->admin);

    Livewire::test(ListBookings::class)
        ->callAction('bulkBook', ['user_ids' => [$first->id, $second->id], 'session_ids' => [$session->id]])
        ->assertHasNoActionErrors()
        ->assertNotified(__('booking.bulk.result', ['booked' => 1, 'failed' => 1]));

    expect(Booking::query()->sole()->user_id)->toBe($first->id);
});
