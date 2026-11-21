<?php

declare(strict_types=1);

namespace App\Modules\Communication\Enums;

enum Channel: string
{
    case Mail = 'mail';
    case Sms = 'sms';

    public function label(): string
    {
        return __('communication.channels.'.$this->value);
    }
}
