<?php

declare(strict_types=1);

namespace App\Modules\Booking\Support;

use App\Modules\Booking\Models\Booking;
use Carbon\CarbonImmutable;

/**
 * BK-06: .ics export and an "Add to Google Calendar" link for a booking.
 */
final class BookingCalendar
{
    public static function ics(Booking $booking): string
    {
        $session = $booking->session;
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.self::escape((string) config('app.name')).'//Bookings//EL',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$booking->public_id.'@'.(parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'booking'),
            'DTSTAMP:'.self::utc(CarbonImmutable::now()),
            'DTSTART:'.self::utc($session->starts_at),
            'DTEND:'.self::utc($session->ends_at),
            'SUMMARY:'.self::escape($session->service->name),
            'DESCRIPTION:'.self::escape(__('booking.calendar.description', ['reference' => $booking->reference()])),
            'STATUS:'.($booking->status->isActive() ? 'CONFIRMED' : 'CANCELLED'),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines)."\r\n";
    }

    public static function googleUrl(Booking $booking): string
    {
        $session = $booking->session;

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $session->service->name,
            'dates' => self::utc($session->starts_at).'/'.self::utc($session->ends_at),
            'details' => __('booking.calendar.description', ['reference' => $booking->reference()]),
        ]);
    }

    private static function utc(CarbonImmutable $moment): string
    {
        return $moment->utc()->format('Ymd\THis\Z');
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $text);
    }
}
