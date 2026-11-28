<?php

declare(strict_types=1);

namespace App\Modules\Communication\Actions;

use App\Modules\Communication\Data\BroadcastAudience;
use App\Modules\Communication\Support\BroadcastRecipients;

/**
 * CM-03: how many customers a bulk message would reach, shown before sending.
 */
final readonly class CountBroadcastRecipients
{
    public function __construct(private BroadcastRecipients $recipients) {}

    public function handle(BroadcastAudience $audience): int
    {
        return count($this->recipients->ids($audience));
    }
}
