<?php

declare(strict_types=1);

use App\Modules\Communication\Enums\NotificationType;
use App\Modules\Communication\Notifications\CustomerNotification;
use App\Modules\Identity\Notifications\InvitationNotification;

/*
| Regression: notifications with readonly properties could not be rebuilt from the queue
| ("Cannot initialize readonly property"), so emails would never have been sent.
*/
it('survives the round trip through the queue', function (object $notification): void {
    $restored = unserialize(serialize($notification));

    expect($restored)->toEqual($notification);
})->with([
    'invitation' => fn (): InvitationNotification => new InvitationNotification('token'),
    'customer notification' => fn (): CustomerNotification => new CustomerNotification(NotificationType::BookingConfirmed, ['service' => 'Pilates'], 'https://x', 'ICS'),
]);
