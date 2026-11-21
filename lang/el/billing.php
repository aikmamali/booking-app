<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Οικονομικά',
    'payment' => ['label' => 'Είσπραξη', 'plural' => 'Εισπράξεις'],
    'status' => [
        'pending' => 'Σε αναμονή',
        'succeeded' => 'Εισπράχθηκε',
        'failed' => 'Απέτυχε',
        'refunded' => 'Επιστράφηκε',
    ],
    'fields' => [
        'paid_at' => 'Ημερομηνία',
        'customer' => 'Πελάτης',
        'amount' => 'Ποσό',
        'total' => 'Σύνολο',
        'method' => 'Τρόπος',
        'for' => 'Αφορά',
        'status' => 'Κατάσταση',
        'recorded_by' => 'Καταχώρησε',
        'note' => 'Σημείωση',
        'period' => 'Περίοδος',
        'from' => 'Από',
        'until' => 'Έως',
    ],
    'payables' => [
        'membership' => 'Πακέτο',
        'booking' => 'Μεμονωμένο μάθημα',
        'other' => 'Άλλο',
    ],
    'actions' => [
        'record' => 'Καταχώρηση είσπραξης',
    ],
    'notices' => [
        'recorded' => 'Η είσπραξη καταχωρήθηκε.',
    ],
    'errors' => [
        'amount' => 'Το ποσό πρέπει να είναι θετικό.',
    ],
    'methods' => [
        'cash' => 'Μετρητά',
        'pos' => 'POS',
        'card' => 'Κάρτα online',
        'transfer' => 'Τραπεζική μεταφορά',
    ],
];
