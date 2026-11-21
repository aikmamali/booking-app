<?php

declare(strict_types=1);

return [
    'greeting' => 'Hello :name,',
    'channels' => [
        'mail' => 'Email',
        'sms' => 'SMS',
    ],
    'types' => [
        'booking_confirmed' => 'Booking confirmation',
        'booking_cancelled' => 'Booking cancellation',
        'booking_rescheduled' => 'Booking moved',
        'booking_reminder' => 'Class reminder',
        'session_cancelled' => 'Class cancelled by the studio',
        'waitlist_promoted' => 'Seat from the waitlist',
        'waitlist_offered' => 'Seat offer from the waitlist',
        'membership_expiring' => 'Membership expiring',
        'low_balance' => 'Low credit balance',
    ],
    'actions' => [
        'booking_confirmed' => 'View booking',
        'booking_cancelled' => 'View booking',
        'booking_rescheduled' => 'View booking',
        'booking_reminder' => 'View booking',
        'session_cancelled' => 'Book another class',
        'waitlist_promoted' => 'View booking',
        'waitlist_offered' => 'Accept the seat',
        'membership_expiring' => 'My membership',
        'low_balance' => 'My membership',
    ],
    'templates' => [
        'booking_confirmed' => [
            'subject' => 'Booking confirmed: :service, :date :time',
            'body' => "Your booking is confirmed.\n\n:service · :date at :time · :instructor · Booking :reference\n\nA calendar file is attached.",
        ],
        'booking_cancelled' => [
            'subject' => 'Booking cancelled: :service, :date :time',
            'body' => "Your booking was cancelled.\n\n:service · :date at :time · Booking :reference\n\nIf a refund applies, the credit is back on your membership.",
        ],
        'booking_rescheduled' => [
            'subject' => 'Booking moved: :service, :date :time',
            'body' => "Your booking was moved.\n\nNew time: :service · :date at :time · :instructor · Booking :reference",
        ],
        'booking_reminder' => [
            'subject' => 'Reminder: :service, :date :time',
            'body' => "A reminder about your class.\n\n:service · :date at :time · :instructor",
        ],
        'session_cancelled' => [
            'subject' => 'Class cancelled: :service, :date :time',
            'body' => "Sorry, the :service class on :date at :time was cancelled (:reason).\n\nYour credit is back on your membership.",
        ],
        'waitlist_promoted' => [
            'subject' => 'You got a seat: :service, :date :time',
            'body' => "A seat became free and your waitlist booking is confirmed.\n\n:service · :date at :time · Booking :reference",
        ],
        'waitlist_offered' => [
            'subject' => 'Seat available: :service, :date :time',
            'body' => 'A seat became free in :service on :date at :time. We hold it for you until :until.',
        ],
        'membership_expiring' => [
            'subject' => 'Your membership ends on :ends_on',
            'body' => "Your membership \":package\" ends on :ends_on with :balance credits left.\n\nUnused credits are lost when it ends.",
        ],
        'low_balance' => [
            'subject' => 'You have :balance credits left',
            'body' => 'You have :balance credits left on ":package". Renew at the front desk to keep booking.',
        ],
    ],
    'preferences' => [
        'title' => 'Notifications',
        'intro' => 'Choose how you want to hear from us.',
        'type' => 'Notification',
        'marketing' => 'Send me news and offers by email.',
        'saved' => 'Your preferences were saved.',
        'save' => 'Save',
    ],
    'announcements' => [
        'label' => 'Announcement',
        'plural' => 'Announcements',
        'title' => 'News',
    ],
    'admin' => [
        'navigation_group' => 'Communication',
        'templates' => 'Email templates',
        'template' => 'Email template',
        'subject' => 'Subject',
        'body' => 'Text',
        'placeholders' => 'Available fields: :list. An empty field means the default text.',
        'customized' => 'Customised',
        'fields' => [
            'title' => 'Title',
            'body' => 'Text',
            'image' => 'Image',
            'published_from' => 'Published from',
            'published_until' => 'Published until',
        ],
    ],
];
