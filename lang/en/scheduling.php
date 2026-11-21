<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Schedule',
    'schedule' => ['label' => 'Weekly class', 'plural' => 'Weekly schedule'],
    'closure' => ['label' => 'Closure', 'plural' => 'Holidays and closed days'],
    'session' => ['label' => 'Class', 'plural' => 'Class calendar'],
    'status' => [
        'scheduled' => 'Scheduled',
        'cancelled' => 'Cancelled',
    ],
    'weekdays' => [
        1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
    ],
    'fields' => [
        'instructor' => 'Instructor',
        'weekday' => 'Day',
        'start_time' => 'Start time',
        'starts_at' => 'Date and time',
        'date' => 'Date',
        'capacity' => 'Seats',
        'valid_from' => 'Valid from',
        'valid_until' => 'Valid until',
        'starts_on' => 'From',
        'ends_on' => 'To',
        'reason' => 'Reason',
        'status' => 'Status',
    ],
    'filters' => [
        'upcoming' => 'From today on',
    ],
    'actions' => [
        'participants' => 'Participants',
        'save' => 'Save',
        'confirm_cancel' => 'Cancel class',
        'change' => 'Change',
        'cancel' => 'Cancel',
        'generate' => 'Update calendar',
    ],
    'hints' => [
        'slot_is_fixed' => 'Day and time cannot change. To move the class, set "Valid until" and create a new one.',
        'open_ended' => 'No end date',
        'closure' => 'Classes on these days are cancelled and customers are notified.',
        'cancel' => 'Customers get their credits back and are notified.',
    ],
    'notices' => [
        'changed' => 'The class was updated.',
        'cancelled' => 'The class was cancelled.',
        'generated' => ':count classes were added to the calendar.',
    ],
    'reasons' => [
        'schedule_ended' => 'Weekly schedule ended',
    ],
    'errors' => [
        'already_cancelled' => 'The class is already cancelled.',
        'stale' => 'Someone else changed this class in the meantime. Open it again and repeat your change.',
        'in_past' => 'You cannot change a class that has started or move it into the past.',
        'capacity_below_taken' => 'Seats cannot be fewer than the :taken bookings.',
        'not_instructor' => 'The selected user is not an instructor.',
        'slot_is_fixed' => 'The day and time of a weekly class cannot change.',
        'valid_until_before_from' => 'The end date must be after the start date.',
        'closure_ends_before_start' => 'The closure must end after it starts.',
    ],
];
