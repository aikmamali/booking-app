<?php

declare(strict_types=1);

namespace App\Modules\Booking\Settings;

use App\Modules\Booking\Enums\WaitlistStrategy;
use App\Shared\Credits;
use App\Shared\Settings\CreditsCast;
use Spatie\LaravelSettings\Settings;

/**
 * Every business number of the booking policy (spec section 4). Defaults live in
 * database/settings. Keys marked "per service" in the spec can be overridden by a service.
 */
final class BookingPolicySettings extends Settings
{
    /** Per service. Cancel or reschedule with credit refund up to this many minutes before start. */
    public int $cancellation_cutoff_minutes;

    /** Per service. Customer may cancel after the cut-off, forfeiting the credit. */
    public bool $late_cancel_allowed;

    /** Per service. */
    public int $booking_opens_days_ahead;

    /** Per service. */
    public int $booking_closes_minutes_before;

    /** Per customer. */
    public int $max_bookings_per_day;

    /** Maximum future bookings per customer; null means no limit. */
    public ?int $max_active_bookings = null;

    /** Per service. */
    public int $default_capacity;

    /** Per service. Steps of 0.5. */
    public Credits $credit_cost;

    /** Per service. */
    public bool $waitlist_enabled;

    /** Per service. */
    public int $waitlist_max_size;

    public WaitlistStrategy $waitlist_strategy;

    /** Only used with the offer strategy. */
    public int $waitlist_offer_ttl_minutes;

    /** No waitlist promotion this many minutes before start. */
    public int $waitlist_cutoff_minutes;

    public int $fixed_seat_max_consecutive_cancellations;

    public int $fixed_seat_pending_credits_hours;

    public bool $no_show_refunds_credit;

    /**
     * When false (phase 1, no check-in at the door), finished sessions close their bookings
     * as attended. When true, bookings without check-in become no_show.
     */
    public bool $checkin_required;

    /** @var list<int> */
    public array $reminder_hours_before;

    public int $membership_expiry_warning_days;

    public int $low_balance_threshold;

    public int $sessions_generation_weeks;

    /** Display time zone. Times are always stored in UTC. */
    public string $timezone;

    public static function group(): string
    {
        return 'booking_policy';
    }

    /**
     * @return array<string, class-string>
     */
    public static function casts(): array
    {
        return [
            'credit_cost' => CreditsCast::class,
        ];
    }
}
