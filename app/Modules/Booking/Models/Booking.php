<?php

declare(strict_types=1);

namespace App\Modules\Booking\Models;

use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\Casts\CreditsCast;
use App\Shared\Credits;
use Carbon\CarbonImmutable;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A customer's seat in a session. Changes are logged (BK-06 history, BK-07 audit).
 *
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property int $class_session_id
 * @property ?int $membership_id
 * @property ?int $fixed_seat_id
 * @property BookingStatus $status
 * @property Credits $credits_charged
 * @property BookingSource $source
 * @property bool $remind
 * @property ?CarbonImmutable $cancelled_at
 * @property ?string $cancel_reason
 * @property ?int $cancelled_by
 * @property bool $late_cancel
 * @property ?int $rescheduled_to_id
 * @property ?CarbonImmutable $checked_in_at
 * @property ?CarbonImmutable $reminded_at
 * @property CarbonImmutable $created_at
 * @property-read User $user
 * @property-read ClassSession $session
 */
#[UseFactory(BookingFactory::class)]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'user_id', 'class_session_id', 'membership_id', 'fixed_seat_id', 'status', 'credits_charged', 'source', 'remind',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ClassSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    /**
     * Short human-readable reference shown to customers and staff.
     */
    public function reference(): string
    {
        return strtoupper(substr($this->public_id, -8));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', BookingStatus::active());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('booking')
            ->logOnly(['status', 'class_session_id', 'cancel_reason', 'late_cancel'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'source' => BookingSource::class,
            'credits_charged' => CreditsCast::class,
            'remind' => 'boolean',
            'late_cancel' => 'boolean',
            'cancelled_at' => 'immutable_datetime',
            'checked_in_at' => 'immutable_datetime',
            'reminded_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
