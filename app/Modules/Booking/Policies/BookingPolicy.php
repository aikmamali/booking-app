<?php

declare(strict_types=1);

namespace App\Modules\Booking\Policies;

use App\Modules\Booking\Models\Booking;
use App\Modules\Identity\Models\User;

final class BookingPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('view_any_booking');
    }

    /** Customers see their own bookings; staff with the permission see all. */
    public function view(User $actor, Booking $booking): bool
    {
        return $booking->user_id === $actor->id || $actor->can('view_booking');
    }

    /** Customers manage their own bookings within the rules; staff need book_for_customer. */
    public function manage(User $actor, Booking $booking): bool
    {
        return $booking->user_id === $actor->id || $actor->can('book_for_customer');
    }

    public function create(User $actor): bool
    {
        return $actor->can('book_for_customer');
    }

    public function markAttendance(User $actor): bool
    {
        return $actor->can('mark_attendance');
    }
}
