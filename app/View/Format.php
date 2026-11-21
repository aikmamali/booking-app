<?php

declare(strict_types=1);

namespace App\View;

use App\Shared\Credits;
use App\Shared\Money;
use Carbon\CarbonImmutable;
use NumberFormatter;

/**
 * Locale-aware display of value objects ("1,5", "12,50 €"). Presentation only.
 */
final class Format
{
    public static function credits(Credits $credits): string
    {
        $text = $credits->halves() % 2 === 0
            ? (string) intdiv($credits->halves(), 2)
            : rtrim(rtrim($credits->toDecimal(), '0'), '.');

        return app()->getLocale() === 'el' ? str_replace('.', ',', $text) : $text;
    }

    public static function money(Money $money): string
    {
        $formatter = new NumberFormatter(app()->getLocale() === 'el' ? 'el_GR' : 'en_IE', NumberFormatter::CURRENCY);

        return (string) $formatter->formatCurrency($money->cents / 100, $money->currency);
    }

    public static function date(CarbonImmutable $date): string
    {
        return $date->format('d/m/Y');
    }
}
