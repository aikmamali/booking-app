<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Catalogue',
    'category' => ['label' => 'Category', 'plural' => 'Categories'],
    'service' => ['label' => 'Service', 'plural' => 'Services'],
    'room' => ['label' => 'Room', 'plural' => 'Rooms'],
    'sections' => [
        'details' => 'Details',
        'booking' => 'Booking and pricing',
        'overrides' => 'Rule exceptions for this service',
    ],
    'fields' => [
        'name' => 'Name',
        'description' => 'Description',
        'image' => 'Photo',
        'is_active' => 'Active',
        'duration_minutes' => 'Duration',
        'default_capacity' => 'Seats',
        'capacity' => 'Capacity',
        'credit_cost' => 'Cost in credits',
        'drop_in_price' => 'Drop-in price',
    ],
    'overrides' => [
        'cancellation_cutoff_minutes' => 'Cancellation cut-off (minutes before)',
        'late_cancel_allowed' => 'Late cancellation allowed',
        'booking_opens_days_ahead' => 'Booking opens (days before)',
        'booking_closes_minutes_before' => 'Booking closes (minutes before)',
        'waitlist_enabled' => 'Waitlist',
        'waitlist_max_size' => 'Waitlist size',
    ],
    'hints' => [
        'credit_cost' => 'In steps of 0.5 (e.g. 0.5 or 1).',
        'drop_in_price' => 'Optional. Leave empty if the service is not sold as a single class.',
        'overrides' => 'Leave a field empty to use the general rule.',
        'default' => 'General rule: :value',
    ],
    'units' => ['minutes' => 'min'],
    'yes' => 'Yes',
    'no' => 'No',
];
