<?php

declare(strict_types=1);

namespace App\Modules\Communication\Models;

use App\Modules\Communication\Enums\BroadcastStatus;
use App\Modules\Communication\Enums\Channel;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CM-03: one bulk message and the audience it was sent to.
 *
 * @property int $id
 * @property string $public_id
 * @property string $subject
 * @property string $body
 * @property list<string> $channels
 * @property array<string, int|bool|null> $audience
 * @property BroadcastStatus $status
 * @property int $recipients_count
 * @property int $sent_count
 * @property ?int $created_by
 * @property ?CarbonImmutable $completed_at
 * @property CarbonImmutable $created_at
 * @property-read ?User $creator
 */
class Broadcast extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['subject', 'body', 'channels', 'audience', 'status', 'recipients_count', 'created_by'];

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
     * @return list<Channel>
     */
    public function channelList(): array
    {
        return array_map(Channel::from(...), $this->channels);
    }

    /**
     * @return HasMany<BroadcastRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(BroadcastRecipient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'audience' => 'array',
            'status' => BroadcastStatus::class,
            'completed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
