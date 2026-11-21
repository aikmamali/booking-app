<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Actions;

use App\Modules\Scheduling\Exceptions\SessionChangeRejected;
use App\Modules\Scheduling\Models\ClassSession;
use App\Modules\Scheduling\Models\Closure;
use App\Shared\BusinessTime;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * SC-04: a closed day or period. Future sessions already on the calendar for those days
 * are cancelled (customers are refunded and notified through SessionCancelled); new
 * sessions are never generated for them.
 */
final readonly class SaveClosure
{
    public function __construct(
        private Clock $clock,
        private BusinessTime $time,
        private CancelSession $cancelSession,
    ) {}

    public function handle(?Closure $closure, string $startsOn, string $endsOn, string $reason): Closure
    {
        if ($endsOn < $startsOn) {
            throw SessionChangeRejected::because('scheduling.errors.closure_ends_before_start');
        }

        return DB::transaction(function () use ($closure, $startsOn, $endsOn, $reason): Closure {
            $closure ??= new Closure;
            $closure->fill(['starts_on' => $startsOn, 'ends_on' => $endsOn, 'reason' => trim($reason)])->save();

            foreach ($this->sessionsDuring($closure) as $session) {
                $this->cancelSession->handle($session, $closure->reason);
            }

            return $closure;
        });
    }

    /**
     * @return iterable<ClassSession>
     */
    private function sessionsDuring(Closure $closure): iterable
    {
        $from = $this->time->toUtc($closure->starts_on->format('Y-m-d'), '00:00');
        $until = $this->time->toUtc($closure->ends_on->addDay()->format('Y-m-d'), '00:00');

        return ClassSession::query()
            ->scheduled()
            ->where('starts_at', '>=', max($from, $this->clock->now()))
            ->where('starts_at', '<', $until)
            ->get();
    }
}
