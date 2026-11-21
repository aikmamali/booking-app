<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

/**
 * A business rejection the UI shows to the user as a translated message
 * (e.g. BookingRejected::because('booking.full')).
 */
interface UserFacingException
{
    public function translated(): string;
}
