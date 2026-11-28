<?php

declare(strict_types=1);

namespace App\Modules\Communication\Data;

use Spatie\LaravelData\Data;

/**
 * CM-03 filters. Empty filters mean every customer who agreed to receive updates.
 */
final class BroadcastAudience extends Data
{
    public function __construct(
        public bool $active_membership = false,
        public ?int $expiring_within_days = null,
        public ?int $service_id = null,
    ) {}

    public function filtersMemberships(): bool
    {
        return $this->active_membership || $this->expiring_within_days !== null || $this->service_id !== null;
    }
}
