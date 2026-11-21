<?php

declare(strict_types=1);

return [
    'greeting' => 'Hello :name,',
    'details' => ':service · :date at :time · :instructor · Booking :reference',
    'view_booking' => 'View booking',
    'confirmed' => [
        'subject' => 'Booking confirmed: :service, :date :time',
        'body' => 'Your booking is confirmed. A calendar file is attached.',
    ],
    'cancelled' => [
        'subject' => 'Booking cancelled: :service, :date :time',
        'body' => 'Your booking was cancelled. If a refund applies, the credit is back on your membership.',
    ],
    'reminder' => [
        'subject' => 'Reminder: :service, :date :time',
        'body' => 'A reminder about your class.',
    ],
    'waitlist_promoted' => [
        'subject' => 'You got a seat: :service, :date :time',
        'body' => 'A seat became free and your waitlist booking is confirmed.',
    ],
    'waitlist_offered' => [
        'subject' => 'Seat available: :service, :date :time',
        'body' => 'A seat became free in :service on :date at :time. We hold it for you until :until.',
        'action' => 'Accept the seat',
    ],
];
