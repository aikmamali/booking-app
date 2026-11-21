<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\WaitlistStatus;
use App\Modules\Booking\Models\WaitlistEntry;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * Spec 8: offers past offered_until expire and the seat goes to the next customer. Live
 * entries of sessions inside the waitlist cut-off (or cancelled) expire too, since they
 * can no longer be promoted. Idempotent.
 */
final readonly class ExpireWaitlistOffers
{
    public function __construct(
        private PromoteFromWaitlist $promote,
        private BookingPolicySettings $settings,
        private Clock $clock,
    ) {}

    public function handle(): int
    {
        $now = $this->clock->now();

        $sessions = DB::transaction(function () use ($now): array {
            $lapsed = WaitlistEntry::query()->where('status', WaitlistStatus::Offered)->where('offered_until', '<=', $now)->get();
            $lapsed->each(fn (WaitlistEntry $e) => $e->update(['status' => WaitlistStatus::Expired]));

            WaitlistEntry::query()->live()
                ->whereHas('session', fn ($q) => $q->where('starts_at', '<=', $now->addMinutes($this->settings->waitlist_cutoff_minutes))
                    ->orWhere('status', 'cancelled'))
                ->update(['status' => WaitlistStatus::Expired]);

            return $lapsed->pluck('class_session_id')->unique()->values()->all();
        });

        foreach ($sessions as $sessionId) {
            $this->promote->handle((int) $sessionId);
        }

        return count($sessions);
    }
}
