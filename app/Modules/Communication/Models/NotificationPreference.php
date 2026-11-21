<?php

declare(strict_types=1);

namespace App\Modules\Communication\Models;

use App\Modules\Communication\Enums\Channel;
use App\Modules\Communication\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;

/**
 * A customer's choice for one notification type on one channel (CM-02). Missing rows mean
 * the type's default channels apply.
 *
 * @property int $user_id
 * @property NotificationType $type
 * @property Channel $channel
 * @property bool $enabled
 */
class NotificationPreference extends Model
{
    /** @var list<string> */
    protected $fillable = ['user_id', 'type', 'channel', 'enabled'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'channel' => Channel::class,
            'enabled' => 'boolean',
        ];
    }
}
