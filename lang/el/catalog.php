<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Κατάλογος',
    'category' => ['label' => 'Κατηγορία', 'plural' => 'Κατηγορίες'],
    'service' => ['label' => 'Υπηρεσία', 'plural' => 'Υπηρεσίες'],
    'room' => ['label' => 'Χώρος', 'plural' => 'Χώροι'],
    'sections' => [
        'details' => 'Στοιχεία',
        'booking' => 'Κρατήσεις και χρέωση',
        'overrides' => 'Εξαιρέσεις κανόνων για αυτή την υπηρεσία',
    ],
    'fields' => [
        'name' => 'Όνομα',
        'description' => 'Περιγραφή',
        'image' => 'Φωτογραφία',
        'is_active' => 'Ενεργή',
        'duration_minutes' => 'Διάρκεια',
        'default_capacity' => 'Θέσεις',
        'capacity' => 'Χωρητικότητα',
        'credit_cost' => 'Κόστος σε credits',
        'drop_in_price' => 'Τιμή μεμονωμένου μαθήματος',
    ],
    'overrides' => [
        'cancellation_cutoff_minutes' => 'Όριο ακύρωσης (λεπτά πριν)',
        'late_cancel_allowed' => 'Επιτρέπεται καθυστερημένη ακύρωση',
        'booking_opens_days_ahead' => 'Οι κρατήσεις ανοίγουν (μέρες πριν)',
        'booking_closes_minutes_before' => 'Οι κρατήσεις κλείνουν (λεπτά πριν)',
        'waitlist_enabled' => 'Λίστα αναμονής',
        'waitlist_max_size' => 'Μέγεθος λίστας αναμονής',
    ],
    'hints' => [
        'credit_cost' => 'Σε βήματα του 0,5 (π.χ. 0,5 ή 1).',
        'drop_in_price' => 'Προαιρετικό. Κενό αν η υπηρεσία δεν πωλείται ως μεμονωμένο μάθημα.',
        'overrides' => 'Άφησε κενό ένα πεδίο για να ισχύει ο γενικός κανόνας.',
        'default' => 'Γενικός κανόνας: :value',
    ],
    'units' => ['minutes' => 'λεπτά'],
    'yes' => 'Ναι',
    'no' => 'Όχι',
];
