<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Enums\PaymentStatus;
use App\Modules\Identity\Models\User;
use App\Shared\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A collected payment (BL-01). Never deleted: corrections are a refund (phase 2).
 *
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property ?string $payable_type
 * @property ?int $payable_id
 * @property int $amount_cents
 * @property PaymentMethod $method
 * @property PaymentStatus $status
 * @property ?string $note
 * @property ?int $recorded_by
 * @property ?CarbonImmutable $paid_at
 * @property-read User $user
 * @property-read ?User $recorder
 */
class Payment extends Model
{
    use HasUlids;
    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'user_id', 'payable_type', 'payable_id', 'amount_cents', 'method', 'status', 'note', 'recorded_by', 'paid_at',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function amount(): Money
    {
        return Money::ofCents($this->amount_cents);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('billing')->logOnly(['amount_cents', 'method', 'status'])->logOnlyDirty();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'paid_at' => 'immutable_datetime',
        ];
    }
}
