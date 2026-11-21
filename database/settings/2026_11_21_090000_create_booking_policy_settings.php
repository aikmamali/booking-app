<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'cancellation_cutoff_minutes' => 180,
            'late_cancel_allowed' => false,
            'booking_opens_days_ahead' => 14,
            'booking_closes_minutes_before' => 0,
            'max_bookings_per_day' => 1,
            'max_active_bookings' => null,
            'default_capacity' => 5,
            'credit_cost' => '1.00',
            'waitlist_enabled' => true,
            'waitlist_max_size' => 5,
            'waitlist_strategy' => 'auto_book',
            'waitlist_offer_ttl_minutes' => 30,
            'waitlist_cutoff_minutes' => 60,
            'fixed_seat_max_consecutive_cancellations' => 4,
            'fixed_seat_pending_credits_hours' => 24,
            'no_show_refunds_credit' => false,
            'reminder_hours_before' => [24, 2],
            'membership_expiry_warning_days' => 7,
            'low_balance_threshold' => 1,
            'sessions_generation_weeks' => 8,
            'timezone' => 'Europe/Athens',
        ];

        foreach ($defaults as $key => $value) {
            $this->migrator->add('booking_policy.'.$key, $value);
        }
    }
};
