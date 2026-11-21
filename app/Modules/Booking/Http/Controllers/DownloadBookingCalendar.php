<?php

declare(strict_types=1);

namespace App\Modules\Booking\Http\Controllers;

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Support\BookingCalendar;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class DownloadBookingCalendar
{
    public function __invoke(Booking $booking): Response
    {
        Gate::authorize('view', $booking);
        $booking->load('session.service');

        return response(BookingCalendar::ics($booking), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="booking-'.$booking->reference().'.ics"',
        ]);
    }
}
