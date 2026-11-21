<?php

declare(strict_types=1);

namespace App\Modules\Communication\Actions;

use App\Modules\Communication\Enums\Channel;
use App\Modules\Communication\Enums\NotificationType;
use App\Modules\Communication\Models\NotificationPreference;
use Illuminate\Support\Facades\DB;

/**
 * CM-02: the customer's channels per notification type.
 */
final class SaveNotificationPreferences
{
    /**
     * @param  array<string, array<string, bool>>  $matrix  type => channel => enabled
     */
    public function handle(int $userId, array $matrix): void
    {
        DB::transaction(function () use ($userId, $matrix): void {
            foreach (NotificationType::cases() as $type) {
                foreach (Channel::cases() as $channel) {
                    NotificationPreference::query()->updateOrCreate(
                        ['user_id' => $userId, 'type' => $type, 'channel' => $channel],
                        ['enabled' => (bool) ($matrix[$type->value][$channel->value] ?? false)],
                    );
                }
            }
        });
    }
}
