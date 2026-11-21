<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\WaitlistStatus;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\WaitlistEntry;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Booking\Support\EffectivePolicy;
use App\Modules\Booking\Support\SeatCounter;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * WL-01: join the queue of a full session. No credit is reserved.
 */
final readonly class JoinWaitlist
{
    public function __construct(
        private BookSession $bookSession,
        private BookingPolicySettings $settings,
        private Clock $clock,
    ) {}

    public function handle(int $userId, int $sessionId): WaitlistEntry
    {
        return DB::transaction(function () use ($userId, $sessionId): WaitlistEntry {
            $session = $this->bookSession->lockSession($sessionId);
            $this->guard($userId, $session);

            return WaitlistEntry::query()->create([
                'user_id' => $userId,
                'class_session_id' => $session->id,
                'status' => WaitlistStatus::Waiting,
            ]);
        });
    }

    private function guard(int $userId, ClassSession $session): void
    {
        $policy = EffectivePolicy::for($session->service, $this->settings);
        $live = WaitlistEntry::query()->live()->where('class_session_id', $session->id);

        $reason = match (true) {
            ! $policy->waitlistEnabled => 'booking.waitlist.disabled',
            $session->isCancelled() || $this->clock->now()->gte($this->cutoff($session)) => 'booking.waitlist.closed',
            SeatCounter::taken($session->id) < $session->capacity => 'booking.waitlist.not_full',
            Booking::query()->active()->where('user_id', $userId)->where('class_session_id', $session->id)->exists() => 'booking.duplicate',
            (clone $live)->where('user_id', $userId)->exists() => 'booking.waitlist.already_waiting',
            (clone $live)->count() >= $policy->waitlistMaxSize => 'booking.waitlist.list_full',
            default => null,
        };

        if ($reason !== null) {
            throw BookingRejected::because($reason);
        }
    }

    private function cutoff(ClassSession $session): CarbonImmutable
    {
        return $session->starts_at->subMinutes($this->settings->waitlist_cutoff_minutes);
    }
}
