<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Spec section 14 (no-show): there is no check-in at the door in phase 1, so finished
 * sessions close their bookings as attended. Turning this on makes MarkNoShows mark
 * unchecked bookings as no_show instead.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('booking_policy.checkin_required', false);
    }
};
