<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Modules\Identity\Enums\Locale;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

/**
 * One input per locale for spatie/laravel-translatable attributes. Greek (default) is
 * required, English optional.
 */
final class Translatable
{
    /**
     * @return list<Component>
     */
    public static function text(string $attribute, string $label, int $maxLength = 150): array
    {
        return array_map(
            fn (Locale $locale): Component => TextInput::make("{$attribute}.{$locale->value}")
                ->label("{$label} ({$locale->label()})")
                ->required($locale === Locale::Greek)
                ->maxLength($maxLength),
            Locale::cases(),
        );
    }

    /**
     * @return list<Component>
     */
    public static function textarea(string $attribute, string $label): array
    {
        return array_map(
            fn (Locale $locale): Component => Textarea::make("{$attribute}.{$locale->value}")
                ->label("{$label} ({$locale->label()})")
                ->rows(3)
                ->maxLength(2000),
            Locale::cases(),
        );
    }
}
