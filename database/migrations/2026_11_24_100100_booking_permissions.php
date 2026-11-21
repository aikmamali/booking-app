<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permissions for bookings. book_for_customer covers BK-07 (staff booking and cancelling for
 * a customer); override_booking_rules allows bypassing limits with a recorded reason.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $names = [
        'view_any_booking', 'view_booking', 'book_for_customer', 'override_booking_rules', 'mark_attendance',
    ];

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->insert(array_map(
            fn (string $name): array => ['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            $this->names,
        ));
        app()['cache']->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', $this->names)->delete();
        app()['cache']->forget(config('permission.cache.key'));
    }
};
