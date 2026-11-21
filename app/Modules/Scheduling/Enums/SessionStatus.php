<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Enums;

enum SessionStatus: string
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $next): bool
    {
        return $this === self::Scheduled && $next === self::Cancelled;
    }

    public function label(): string
    {
        return __('scheduling.status.'.$this->value);
    }
}
