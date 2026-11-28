<?php

declare(strict_types=1);

namespace App\Modules\Communication\Models;

use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $broadcast_id
 * @property int $user_id
 * @property ?CarbonImmutable $sent_at
 * @property-read User $user
 */
class BroadcastRecipient extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = ['broadcast_id', 'user_id', 'sent_at'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['sent_at' => 'immutable_datetime'];
    }
}
