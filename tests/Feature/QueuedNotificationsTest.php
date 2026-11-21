<?php

declare(strict_types=1);

use App\Modules\Booking\Contracts\BookingSummary;
use App\Modules\Communication\Notifications\BookingCancelledNotification;
use App\Modules\Communication\Notifications\BookingConfirmedNotification;
use App\Modules\Communication\Notifications\BookingReminderNotification;
use App\Modules\Identity\Notifications\InvitationNotification;
use Carbon\CarbonImmutable;

/*
| Regression: notifications with readonly properties could not be rebuilt from the queue
| ("Cannot initialize readonly property"), so emails would never have been sent.
*/
it('survives the round trip through the queue', function (object $notification): void {
    $restored = unserialize(serialize($notification));

    expect($restored)->toEqual($notification);
})->with([
    'invitation' => fn (): InvitationNotification => new InvitationNotification('token'),
    'booking confirmed' => fn (): BookingConfirmedNotification => new BookingConfirmedNotification(summary()),
    'booking cancelled' => fn (): BookingCancelledNotification => new BookingCancelledNotification(summary()),
    'reminder' => fn (): BookingReminderNotification => new BookingReminderNotification(summary()),
]);

function summary(): BookingSummary
{
    return new BookingSummary(1, 'ULID', 'REF', 1, 'Pilates', 'Ελένη', CarbonImmutable::now(), CarbonImmutable::now(), 'confirmed', null, 'ICS', 'https://g');
}
