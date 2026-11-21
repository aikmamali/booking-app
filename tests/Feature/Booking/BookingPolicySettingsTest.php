<?php

declare(strict_types=1);

use App\Modules\Booking\Enums\WaitlistStrategy;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Shared\Credits;

it('loads every default from spec section 4', function (): void {
    $settings = app(BookingPolicySettings::class);

    expect($settings->cancellation_cutoff_minutes)->toBe(180)
        ->and($settings->late_cancel_allowed)->toBeFalse()
        ->and($settings->booking_opens_days_ahead)->toBe(14)
        ->and($settings->booking_closes_minutes_before)->toBe(0)
        ->and($settings->max_bookings_per_day)->toBe(1)
        ->and($settings->max_active_bookings)->toBeNull()
        ->and($settings->default_capacity)->toBe(5)
        ->and($settings->credit_cost->equals(Credits::of('1')))->toBeTrue()
        ->and($settings->waitlist_enabled)->toBeTrue()
        ->and($settings->waitlist_max_size)->toBe(5)
        ->and($settings->waitlist_strategy)->toBe(WaitlistStrategy::AutoBook)
        ->and($settings->waitlist_offer_ttl_minutes)->toBe(30)
        ->and($settings->waitlist_cutoff_minutes)->toBe(60)
        ->and($settings->fixed_seat_max_consecutive_cancellations)->toBe(4)
        ->and($settings->fixed_seat_pending_credits_hours)->toBe(24)
        ->and($settings->no_show_refunds_credit)->toBeFalse()
        ->and($settings->reminder_hours_before)->toBe([24, 2])
        ->and($settings->membership_expiry_warning_days)->toBe(7)
        ->and($settings->low_balance_threshold)->toBe(1)
        ->and($settings->sessions_generation_weeks)->toBe(8)
        ->and($settings->timezone)->toBe('Europe/Athens');
});

it('persists changes, including credits and enums', function (): void {
    $settings = app(BookingPolicySettings::class);
    $settings->credit_cost = Credits::of('1.5');
    $settings->waitlist_strategy = WaitlistStrategy::Offer;
    $settings->max_active_bookings = 10;
    $settings->save();

    app()->forgetInstance(BookingPolicySettings::class);
    $reloaded = app(BookingPolicySettings::class);
    $reloaded->refresh();

    expect($reloaded->credit_cost->toDecimal())->toBe('1.50')
        ->and($reloaded->waitlist_strategy)->toBe(WaitlistStrategy::Offer)
        ->and($reloaded->max_active_bookings)->toBe(10);
});
