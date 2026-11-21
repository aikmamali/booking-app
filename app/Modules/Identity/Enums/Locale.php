<?php

declare(strict_types=1);

namespace App\Modules\Identity\Enums;

enum Locale: string
{
    case Greek = 'el';
    case English = 'en';

    public function label(): string
    {
        return __('identity.locales.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $locale) {
            $options[$locale->value] = $locale->label();
        }

        return $options;
    }
}
