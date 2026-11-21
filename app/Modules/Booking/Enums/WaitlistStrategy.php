<?php

declare(strict_types=1);

namespace App\Modules\Booking\Enums;

enum WaitlistStrategy: string
{
    /** The first waiting customer is booked automatically when a seat frees up. */
    case AutoBook = 'auto_book';

    /** The first waiting customer gets a time-limited offer to accept the seat. */
    case Offer = 'offer';
}
