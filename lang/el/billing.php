<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Οικονομικά',
    'expense' => ['label' => 'Έξοδο', 'plural' => 'Έξοδα'],
    'expense_categories' => [
        'rent' => 'Ενοίκιο',
        'utilities' => 'Λογαριασμοί (ρεύμα, νερό, τηλέφωνο)',
        'payroll' => 'Μισθοδοσία',
        'equipment' => 'Εξοπλισμός',
        'supplies' => 'Αναλώσιμα',
        'maintenance' => 'Συντήρηση',
        'marketing' => 'Διαφήμιση',
        'taxes' => 'Φόροι και εισφορές',
        'other' => 'Άλλο',
    ],
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
        'category' => 'Κατηγορία',
        'spent_on' => 'Ημερομηνία εξόδου',
        'attachment' => 'Συνημμένο (απόδειξη, τιμολόγιο)',
    ],
    'payables' => [
        'membership' => 'Πακέτο',
        'booking' => 'Μεμονωμένο μάθημα',
        'other' => 'Άλλο',
    ],
    'actions' => [
        'record' => 'Καταχώρηση είσπραξης',
        'record_expense' => 'Καταχώρηση εξόδου',
        'download_attachment' => 'Συνημμένο',
    ],
    'notices' => [
        'recorded' => 'Η είσπραξη καταχωρήθηκε.',
    ],
    'errors' => [
        'amount' => 'Το ποσό πρέπει να είναι θετικό.',
        'expense_in_future' => 'Η ημερομηνία του εξόδου δεν μπορεί να είναι στο μέλλον.',
    ],
    'methods' => [
        'cash' => 'Μετρητά',
        'pos' => 'POS',
        'card' => 'Κάρτα online',
        'transfer' => 'Τραπεζική μεταφορά',
    ],
];
