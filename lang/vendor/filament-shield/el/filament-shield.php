<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Table Columns
    |--------------------------------------------------------------------------
    */

    'column.name' => 'Όνομα',
    'column.guard_name' => 'Guard',
    'column.team' => 'Team',
    'column.roles' => 'Ρόλοι και δικαιώματα',
    'column.permissions' => 'Δικαιώματα',
    'column.updated_at' => 'Updated At',

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */

    'field.name' => 'Όνομα',
    'field.guard_name' => 'Guard',
    'field.permissions' => 'Δικαιώματα',
    'field.team' => 'Team',
    'field.team.placeholder' => 'Select a team ...',
    'field.select_all.name' => 'Επιλογή όλων',
    'field.select_all.message' => 'Enables/Disables all Permissions for this role',

    /*
    |--------------------------------------------------------------------------
    | Navigation & Resource
    |--------------------------------------------------------------------------
    */

    'nav.group' => 'Χρήστες',
    'nav.role.label' => 'Ρόλοι και δικαιώματα',
    'nav.role.icon' => 'heroicon-o-shield-check',
    'resource.label.role' => 'Ρόλος',
    'resource.label.roles' => 'Ρόλοι και δικαιώματα',

    /*
    |--------------------------------------------------------------------------
    | Section & Tabs
    |--------------------------------------------------------------------------
    */

    'section' => 'Entities',
    'resources' => 'Οθόνες',
    'widgets' => 'Widgets',
    'pages' => 'Σελίδες',
    'custom' => 'Επιπλέον δικαιώματα',

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    'forbidden' => 'You do not have permission to access',

    /*
    |--------------------------------------------------------------------------
    | Resource Permissions' Labels
    |--------------------------------------------------------------------------
    */

    'resource_permission_prefixes_labels' => [
        'view' => 'Προβολή',
        'view_any' => 'Προβολή λίστας',
        'create' => 'Δημιουργία',
        'update' => 'Ενημέρωση',
        'delete' => 'Διαγραφή',
        'delete_any' => 'Μαζική διαγραφή',
        'force_delete' => 'Οριστική διαγραφή',
        'force_delete_any' => 'Μαζική οριστική διαγραφή',
        'restore' => 'Επαναφορά',
        'reorder' => 'Αλλαγή σειράς',
        'restore_any' => 'Μαζική επαναφορά',
        'replicate' => 'Αντιγραφή',
    ],
];
