<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::Pending => [self::Succeeded, self::Failed],
            self::Succeeded => [self::Refunded],
            self::Failed, self::Refunded => [],
        }, true);
    }

    public function label(): string
    {
        return __('billing.status.'.$this->value);
    }
}
