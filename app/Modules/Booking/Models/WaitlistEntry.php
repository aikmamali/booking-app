<?php

declare(strict_types=1);

namespace App\Modules\Booking\Models;

use App\Modules\Booking\Enums\WaitlistStatus;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Models\ClassSession;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A place in the queue for a full session (WL-01). The queue order is the id order.
 *
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property int $class_session_id
 * @property WaitlistStatus $status
 * @property ?CarbonImmutable $offered_until
 * @property ?int $booking_id
 * @property-read User $user
 * @property-read ClassSession $session
 */
class WaitlistEntry extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['user_id', 'class_session_id', 'status'];

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
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereIn('status', [WaitlistStatus::Waiting, WaitlistStatus::Offered]);
    }

    /**
     * 1-based place among the customers still waiting or holding an offer.
     */
    public function position(): int
    {
        return self::query()->live()->where('class_session_id', $this->class_session_id)->where('id', '<=', $this->id)->count();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WaitlistStatus::class,
            'offered_until' => 'immutable_datetime',
        ];
    }
}
