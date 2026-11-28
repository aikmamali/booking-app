<?php

declare(strict_types=1);

namespace App\Modules\Identity\Support;

use Illuminate\Support\Str;

final class RecoveryCodes
{
    public const int COUNT = 8;

    /**
     * @return list<string>
     */
    public static function generate(): array
    {
        $codes = [];

        for ($i = 0; $i < self::COUNT; $i++) {
            $codes[] = Str::lower(Str::random(5).'-'.Str::random(5));
        }

        return $codes;
    }

    public static function normalize(string $code): string
    {
        return Str::lower(trim($code));
    }
}
