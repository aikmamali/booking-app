<?php

declare(strict_types=1);

namespace App\Modules\Membership\Models;

use App\Modules\Identity\Models\User;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Shared\Casts\CreditsCast;
use App\Shared\Credits;
use Carbon\CarbonImmutable;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A package a customer bought, with a snapshot of its terms (spec 6.2). The balance is
 * never stored here: it is the balance_after of the latest ledger entry.
 *
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property int $package_id
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 * @property MembershipStatus $status
 * @property ?CarbonImmutable $frozen_until
 * @property ?int $payment_id
 * @property string $package_name
 * @property Credits $credits
 * @property int $price_cents
 * @property int $vat_rate
 * @property int $validity_days
 * @property list<int> $service_ids
 * @property ?int $sold_by
 * @property ?CarbonImmutable $expiry_warned_at
 * @property ?CarbonImmutable $low_balance_warned_at
 * @property-read User $user
 * @property-read Package $package
 */
#[UseFactory(MembershipFactory::class)]
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory;

    use HasTranslations;
    use HasUlids;

    /** @var list<string> */
    public array $translatable = ['package_name'];

    /** @var list<string> */
    protected $fillable = [
        'user_id', 'package_id', 'starts_on', 'ends_on', 'status', 'package_name', 'credits', 'price_cents',
        'vat_rate', 'validity_days', 'service_ids', 'sold_by',
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
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class)->withTrashed();
    }

    /**
     * @return HasMany<CreditTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function covers(int $serviceId): bool
    {
        return in_array($serviceId, $this->service_ids, true);
    }

    public function isValidOn(string $date): bool
    {
        return $date >= $this->starts_on->format('Y-m-d') && $date <= $this->ends_on->format('Y-m-d');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'frozen_until' => 'immutable_date',
            'status' => MembershipStatus::class,
            'credits' => CreditsCast::class,
            'service_ids' => 'array',
            'expiry_warned_at' => 'immutable_datetime',
            'low_balance_warned_at' => 'immutable_datetime',
        ];
    }
}
