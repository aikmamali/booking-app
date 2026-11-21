<?php

declare(strict_types=1);

use App\Modules\Booking\Actions\BookSession;
use App\Modules\Booking\Data\BookingRequest;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Service;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Contracts\CreditLedger;
use App\Modules\Membership\Models\Membership;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\Credits;

/**
 * A session on Tuesday 23 March 2027, 18:30 Athens (16:30 UTC), for a 1-credit service.
 * Tests travel to Saturday 20 March 2027 10:00 Athens first.
 *
 * @param  array<string, mixed>  $attributes
 */
function bookableSession(array $attributes = [], ?Service $service = null): ClassSession
{
    $service ??= Service::factory()->create(['credit_cost' => Credits::of(1), 'duration_minutes' => 60]);

    return ClassSession::factory()->create([
        'service_id' => $service->id,
        'starts_at' => '2027-03-23 16:30:00',
        'ends_at' => '2027-03-23 17:30:00',
        'capacity' => 5,
        ...$attributes,
    ])->fresh() ?? throw new RuntimeException('session');
}

/**
 * A customer with a 10-credit membership covering the session's service.
 */
function customerFor(ClassSession $session, string $credits = '10'): User
{
    $customer = User::factory()->customer()->create();
    soldMembership($customer, ['credits' => Credits::of($credits)], '2027-03-20', $session->service);

    return $customer;
}

function book(User $customer, ClassSession $session, array $request = []): Booking
{
    return app(BookSession::class)->handle(new BookingRequest(...[
        'user_id' => $customer->id,
        'session_id' => $session->id,
        ...$request,
    ]));
}

function creditsOf(User $customer): string
{
    $total = Credits::zero();

    foreach (Membership::query()->where('user_id', $customer->id)->pluck('id') as $id) {
        $total = $total->add(app(CreditLedger::class)->balance($id));
    }

    return $total->toDecimal();
}
