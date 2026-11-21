<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\Credits;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A raw booking row without a ledger debit. Tests that need the full flow use BookSession.
 *
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * @return array<model-property<Booking>, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->customer(),
            'class_session_id' => ClassSession::factory(),
            'membership_id' => null,
            'status' => BookingStatus::Confirmed,
            'credits_charged' => Credits::of(1),
            'source' => BookingSource::Customer,
            'remind' => true,
        ];
    }
}
