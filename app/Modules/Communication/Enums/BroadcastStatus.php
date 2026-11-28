<?php

declare(strict_types=1);

namespace App\Modules\Communication\Enums;

enum BroadcastStatus: string
{
    case Queued = 'queued';
    case Completed = 'completed';

    public function canTransitionTo(self $next): bool
    {
        return $this === self::Queued && $next === self::Completed;
    }

    public function label(): string
    {
        return __('communication.broadcasts.status.'.$this->value);
    }
}
