<?php

declare(strict_types=1);

namespace App\Modules\Communication\Models;

use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * RP-03: a trainer's note about a customer (exercise log), optionally about one booking.
 * Private to staff unless marked visible to the customer.
 *
 * @property int $id
 * @property string $public_id
 * @property int $user_id
 * @property ?int $booking_id
 * @property ?int $author_id
 * @property string $body
 * @property bool $visible_to_customer
 * @property CarbonImmutable $created_at
 * @property-read User $customer
 * @property-read ?User $author
 */
class Note extends Model
{
    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = ['user_id', 'booking_id', 'author_id', 'body', 'visible_to_customer'];

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
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['booking_id', 'visible_to_customer'])->logOnlyDirty();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visible_to_customer' => 'boolean',
            'created_at' => 'immutable_datetime',
        ];
    }
}
