<?php

declare(strict_types=1);

namespace App\Modules\Communication\Data;

use App\Modules\Communication\Enums\Channel;
use Spatie\LaravelData\Data;

final class BroadcastData extends Data
{
    /**
     * @param  list<Channel>  $channels
     */
    public function __construct(
        public string $subject,
        public string $body,
        public array $channels,
        public BroadcastAudience $audience,
    ) {}
}
