<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Actions;

use App\Modules\Scheduling\Enums\SessionStatus;
use App\Modules\Scheduling\Events\SessionCancelled;
use App\Modules\Scheduling\Exceptions\SessionChangeRejected;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * SC-03: cancels one session. Refunds (Booking, step 4) and notifications (Communication,
 * step 6) react to SessionCancelled.
 */
final readonly class CancelSession
{
    public function __construct(private Clock $clock) {}

    public function handle(ClassSession $session, string $reason): void
    {
        DB::transaction(function () use ($session, $reason): void {
            /** @var ClassSession $locked */
            $locked = ClassSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(SessionStatus::Cancelled)) {
                throw SessionChangeRejected::because('scheduling.errors.already_cancelled');
            }

            $locked->forceFill([
                'status' => SessionStatus::Cancelled,
                'cancel_reason' => $reason,
                'cancelled_at' => $this->clock->now(),
                'lock_version' => $locked->lock_version + 1,
            ])->save();

            $session->setRawAttributes($locked->getAttributes(), sync: true);

            event(new SessionCancelled($locked->id, $reason));
        });
    }
}
