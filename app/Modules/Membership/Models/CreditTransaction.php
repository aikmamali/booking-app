<?php

declare(strict_types=1);

namespace App\Modules\Membership\Models;

use App\Modules\Identity\Models\User;
use App\Modules\Membership\Enums\TransactionType;
use App\Shared\Casts\CreditsCast;
use App\Shared\Credits;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One ledger entry. Append-only (spec 7.5): rows are inserted by the CreditLedger and
 * never updated or deleted.
 *
 * @property int $id
 * @property int $membership_id
 * @property ?int $booking_id
 * @property TransactionType $type
 * @property Credits $amount
 * @property Credits $balance_after
 * @property ?string $reason
 * @property ?int $actor_id
 * @property CarbonImmutable $created_at
 * @property-read ?User $actor
 */
class CreditTransaction extends Model
{
    public const null UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['membership_id', 'booking_id', 'type', 'amount', 'balance_after', 'reason', 'actor_id'];

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Credit transactions are append-only.'));
        static::deleting(fn (): never => throw new LogicException('Credit transactions are append-only.'));
    }

    /**
     * @return BelongsTo<Membership, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => CreditsCast::class,
            'balance_after' => CreditsCast::class,
            'created_at' => 'immutable_datetime',
        ];
    }
}
