<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Models;

use App\Modules\Catalog\Models\Room;
use App\Modules\Catalog\Models\Service;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Enums\Weekday;
use Carbon\CarbonImmutable;
use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A weekly recurring rule (SC-02): "Tuesday 18:30, Group Training, instructor X, 5 seats".
 *
 * @property int $id
 * @property int $service_id
 * @property int $instructor_id
 * @property ?int $room_id
 * @property Weekday $weekday
 * @property string $start_time
 * @property int $capacity
 * @property CarbonImmutable $valid_from
 * @property ?CarbonImmutable $valid_until
 * @property-read Service $service
 * @property-read User $instructor
 * @property-read ?Room $room
 */
#[UseFactory(ScheduleFactory::class)]
class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'service_id', 'instructor_id', 'room_id', 'weekday', 'start_time', 'capacity', 'valid_from', 'valid_until',
    ];

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

    /**
     * @return HasMany<ClassSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function isValidOn(CarbonImmutable $date): bool
    {
        return $date->gte($this->valid_from->startOfDay())
            && ($this->valid_until === null || $date->lte($this->valid_until->startOfDay()));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => Weekday::class,
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
        ];
    }
}
