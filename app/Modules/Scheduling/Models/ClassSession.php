<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Models;

use App\Modules\Catalog\Models\Room;
use App\Modules\Catalog\Models\Service;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Enums\SessionStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ClassSessionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One occurrence on the calendar. Public contract of the Scheduling module: Booking locks
 * this row (SELECT ... FOR UPDATE) to guard capacity.
 *
 * @property int $id
 * @property string $public_id
 * @property ?int $schedule_id
 * @property int $service_id
 * @property int $instructor_id
 * @property ?int $room_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property int $capacity
 * @property SessionStatus $status
 * @property ?string $cancel_reason
 * @property ?CarbonImmutable $cancelled_at
 * @property bool $is_customized
 * @property int $lock_version
 * @property-read Service $service
 * @property-read User $instructor
 * @property-read ?Room $room
 * @property-read ?Schedule $schedule
 */
#[UseFactory(ClassSessionFactory::class)]
class ClassSession extends Model
{
    /** @use HasFactory<ClassSessionFactory> */
    use HasFactory;

    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'schedule_id', 'service_id', 'instructor_id', 'room_id', 'starts_at', 'ends_at', 'capacity',
        'status', 'cancel_reason', 'cancelled_at', 'is_customized',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<Schedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === SessionStatus::Cancelled;
    }

    public function hasStartedAt(CarbonImmutable $now): bool
    {
        return $this->starts_at->lte($now);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', SessionStatus::Scheduled);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'status' => SessionStatus::class,
            'is_customized' => 'boolean',
        ];
    }
}
