<?php

declare(strict_types=1);

namespace App\Modules\Booking\Models;

use App\Modules\Booking\Enums\FixedSeatStatus;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * FS-02: a customer's place in one weekly class for one season.
 *
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property int $schedule_id
 * @property int $season_id
 * @property FixedSeatStatus $status
 * @property int $consecutive_cancellations
 * @property ?string $revoked_reason
 * @property ?int $decided_by
 * @property ?CarbonImmutable $decided_at
 * @property CarbonImmutable $created_at
 * @property-read User $user
 * @property-read Season $season
 */
class FixedSeat extends Model
{
    use HasUlids;
    use LogsActivity;

    /** @var list<string> */
    protected $fillable = ['user_id', 'schedule_id', 'season_id', 'status'];

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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Season, $this>
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'revoked_reason'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FixedSeatStatus::class,
            'decided_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
