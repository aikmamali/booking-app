<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Data\BookingRequest;
use App\Modules\Booking\Data\BulkBookingRow;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Exceptions\BookingRejected;

/**
 * BK-10: books many customers into many sessions in one step. Each pair goes through
 * BookSession on its own (same rules, locks and credit charge), so one full class or
 * empty membership does not stop the others; failures are reported per row.
 */
final readonly class BookInBulk
{
    /** Guards the admin screen against an accidental huge request. */
    public const int MAX_PAIRS = 300;

    public function __construct(private BookSession $book) {}

    /**
     * @param  list<int>  $userIds
     * @param  list<int>  $sessionIds
     * @return list<BulkBookingRow>
     */
    public function handle(array $userIds, array $sessionIds, int $actorId, ?string $overrideReason = null): array
    {
        $userIds = array_values(array_unique($userIds));
        $sessionIds = array_values(array_unique($sessionIds));

        if ($userIds === [] || $sessionIds === []) {
            throw BookingRejected::because('booking.bulk.empty');
        }

        if (count($userIds) * count($sessionIds) > self::MAX_PAIRS) {
            throw BookingRejected::because('booking.bulk.too_many', ['max' => self::MAX_PAIRS]);
        }

        $rows = [];

        foreach ($sessionIds as $sessionId) {
            foreach ($userIds as $userId) {
                $rows[] = $this->bookOne($userId, $sessionId, $actorId, $overrideReason);
            }
        }

        return $rows;
    }

    private function bookOne(int $userId, int $sessionId, int $actorId, ?string $overrideReason): BulkBookingRow
    {
        try {
            $booking = $this->book->handle(new BookingRequest(
                user_id: $userId,
                session_id: $sessionId,
                source: BookingSource::Staff,
                actor_id: $actorId,
                override_reason: $overrideReason,
            ));

            return new BulkBookingRow($userId, $sessionId, $booking->id, null);
        } catch (BookingRejected $e) {
            return new BulkBookingRow($userId, $sessionId, null, $e->translated());
        }
    }
}
