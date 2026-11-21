<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Data;

use Spatie\LaravelData\Data;

/**
 * Per-service overrides of BookingPolicySettings (spec section 4, "per service" column).
 * Null means "use the global setting". Capacity and credit cost are service columns.
 */
final class PolicyOverrides extends Data
{
    public function __construct(
        public ?int $cancellation_cutoff_minutes = null,
        public ?bool $late_cancel_allowed = null,
        public ?int $booking_opens_days_ahead = null,
        public ?int $booking_closes_minutes_before = null,
        public ?bool $waitlist_enabled = null,
        public ?int $waitlist_max_size = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'cancellation_cutoff_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'late_cancel_allowed' => ['nullable', 'boolean'],
            'booking_opens_days_ahead' => ['nullable', 'integer', 'min:0', 'max:365'],
            'booking_closes_minutes_before' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'waitlist_enabled' => ['nullable', 'boolean'],
            'waitlist_max_size' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    /**
     * Builds overrides from raw form input, where "" means "use the default".
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromInput(array $input): self
    {
        $int = fn (string $key): ?int => self::blank($input[$key] ?? null) ? null : (int) $input[$key];
        $bool = fn (string $key): ?bool => self::blank($input[$key] ?? null) ? null : filter_var($input[$key], FILTER_VALIDATE_BOOLEAN);

        return new self(
            cancellation_cutoff_minutes: $int('cancellation_cutoff_minutes'),
            late_cancel_allowed: $bool('late_cancel_allowed'),
            booking_opens_days_ahead: $int('booking_opens_days_ahead'),
            booking_closes_minutes_before: $int('booking_closes_minutes_before'),
            waitlist_enabled: $bool('waitlist_enabled'),
            waitlist_max_size: $int('waitlist_max_size'),
        );
    }

    public function isEmpty(): bool
    {
        return array_filter($this->toArray(), fn (mixed $value): bool => $value !== null) === [];
    }

    private static function blank(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
