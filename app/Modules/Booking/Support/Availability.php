<?php

declare(strict_types=1);

namespace App\Modules\Booking\Support;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Catalog\Models\Service;
use App\Modules\Scheduling\Enums\SessionStatus;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\BusinessTime;
use App\Shared\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * BK-01: what the customer sees while choosing service → day → time. Read-only; the
 * booking itself re-checks everything under a row lock.
 */
final readonly class Availability
{
    public const string AVAILABLE = 'available';

    public const string FULL = 'full';

    public const string CLOSED = 'closed';

    public const string PAST = 'past';

    public function __construct(
        private Clock $clock,
        private BusinessTime $time,
        private BookingPolicySettings $settings,
    ) {}

    /**
     * @return Collection<int, Service>
     */
    public function services(): Collection
    {
        return Service::query()->where('is_active', true)->orderBy('position')->get();
    }

    /**
     * Status of every day of the month that has sessions, keyed by Y-m-d.
     *
     * @return array<string, string>
     */
    public function month(Service $service, string $month): array
    {
        $first = CarbonImmutable::parse($month.'-01');
        $days = [];

        foreach ($this->slotsBetween($service, $first, $first->addMonth()) as $slot) {
            $days[$slot['date']] = $this->better($days[$slot['date']] ?? null, $slot['state']);
        }

        return $days;
    }

    /**
     * @return list<array{public_id: string, date: string, time: string, free: int, capacity: int, state: string, mine: bool}>
     */
    public function day(Service $service, string $date, ?int $userId = null): array
    {
        $from = CarbonImmutable::parse($date);

        return $this->slotsBetween($service, $from, $from->addDay(), $userId);
    }

    /**
     * @return list<array{public_id: string, date: string, time: string, free: int, capacity: int, state: string, mine: bool}>
     */
    private function slotsBetween(Service $service, CarbonImmutable $fromDate, CarbonImmutable $toDate, ?int $userId = null): array
    {
        $sessions = ClassSession::query()
            ->where('service_id', $service->id)
            ->where('starts_at', '>=', $this->time->toUtc($fromDate->format('Y-m-d'), '00:00'))
            ->where('starts_at', '<', $this->time->toUtc($toDate->format('Y-m-d'), '00:00'))
            ->orderBy('starts_at')
            ->get();

        $ids = array_map(intval(...), $sessions->modelKeys());
        $taken = $this->takenSeats($ids);
        $mine = $userId === null ? [] : $this->bookedBy($userId, $ids);
        $policy = EffectivePolicy::for($service, $this->settings);

        return array_values($sessions->map(fn (ClassSession $s): array => [
            'public_id' => $s->public_id,
            'date' => $this->time->toLocal($s->starts_at)->format('Y-m-d'),
            'time' => $this->time->toLocal($s->starts_at)->format('H:i'),
            'free' => max(0, $s->capacity - ($taken[$s->id] ?? 0)),
            'capacity' => $s->capacity,
            'state' => $this->state($s, $taken[$s->id] ?? 0, $policy),
            'mine' => in_array($s->id, $mine, true),
        ])->all());
    }

    private function state(ClassSession $session, int $taken, EffectivePolicy $policy): string
    {
        $now = $this->clock->now();

        return match (true) {
            $session->starts_at->lte($now) => self::PAST,
            $session->status === SessionStatus::Cancelled,
            $now->lt($session->starts_at->subDays($policy->opensDaysAhead)),
            $now->gte($session->starts_at->subMinutes($policy->closesMinutesBefore)) => self::CLOSED,
            $taken >= $session->capacity => self::FULL,
            default => self::AVAILABLE,
        };
    }

    /**
     * A day shows the best state among its sessions.
     */
    private function better(?string $current, string $candidate): string
    {
        $rank = [self::AVAILABLE => 4, self::FULL => 3, self::CLOSED => 2, self::PAST => 1];

        return $current === null || $rank[$candidate] > $rank[$current] ? $candidate : $current;
    }

    /**
     * @param  array<int, int>  $sessionIds
     * @return array<int, int>
     */
    private function takenSeats(array $sessionIds): array
    {
        return Booking::query()->active()->whereIn('class_session_id', $sessionIds)
            ->selectRaw('class_session_id, count(*) as taken')->groupBy('class_session_id')
            ->toBase()->pluck('taken', 'class_session_id')->map(fn (mixed $n): int => (int) $n)->all();
    }

    /**
     * @param  array<int, int>  $sessionIds
     * @return array<int, int>
     */
    private function bookedBy(int $userId, array $sessionIds): array
    {
        return Booking::query()->where('user_id', $userId)->whereIn('status', BookingStatus::active())
            ->whereIn('class_session_id', $sessionIds)->pluck('class_session_id')->map(fn (mixed $id): int => (int) $id)->values()->all();
    }
}
