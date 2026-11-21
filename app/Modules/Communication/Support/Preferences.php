<?php

declare(strict_types=1);

namespace App\Modules\Communication\Support;

use App\Modules\Communication\Enums\Channel;
use App\Modules\Communication\Enums\NotificationType;
use App\Modules\Communication\Models\NotificationPreference;

/**
 * Resolves which channels a customer receives each notification on (CM-02).
 */
final class Preferences
{
    /**
     * @return list<Channel>
     */
    public function channelsFor(int $userId, NotificationType $type): array
    {
        $matrix = $this->matrix($userId);

        return array_values(array_filter(Channel::cases(), fn (Channel $c): bool => $matrix[$type->value][$c->value]));
    }

    /**
     * Every type × channel with the customer's choice or the default.
     *
     * @return array<string, array<string, bool>>
     */
    public function matrix(int $userId): array
    {
        $stored = NotificationPreference::query()->where('user_id', $userId)->get()
            ->mapWithKeys(fn (NotificationPreference $p): array => [$p->type->value.'.'.$p->channel->value => $p->enabled]);
        $matrix = [];

        foreach (NotificationType::cases() as $type) {
            foreach (Channel::cases() as $channel) {
                $matrix[$type->value][$channel->value] = $stored[$type->value.'.'.$channel->value]
                    ?? in_array($channel, $type->defaultChannels(), true);
            }
        }

        return $matrix;
    }
}
