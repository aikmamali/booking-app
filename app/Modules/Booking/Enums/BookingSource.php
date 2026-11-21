<?php

declare(strict_types=1);

namespace App\Modules\Booking\Enums;

enum BookingSource: string
{
    case Customer = 'customer';
    case Staff = 'staff';
    case FixedSeat = 'fixed_seat';
    case Waitlist = 'waitlist';
}
