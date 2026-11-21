<?php

declare(strict_types=1);

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * CI-01: the digital card carries the customer's random checkin_token plus an HMAC
 * signature, so a scanned code can be verified (CI-02) and a guessed or edited one is
 * rejected. Reissuing the token invalidates the old card.
 */
final class CheckinCode
{
    private const string PREFIX = 'CHK1';

    public static function for(User $user): string
    {
        return self::PREFIX.'.'.$user->checkin_token.'.'.self::signature($user->checkin_token);
    }

    /**
     * The user a scanned code belongs to, or null if it is forged, malformed or reissued.
     */
    public static function resolve(string $code): ?User
    {
        $parts = explode('.', trim($code));

        if (count($parts) !== 3 || $parts[0] !== self::PREFIX || ! hash_equals(self::signature($parts[1]), $parts[2])) {
            return null;
        }

        return User::query()->where('checkin_token', $parts[1])->whereNull('anonymized_at')->first();
    }

    public static function svg(User $user, int $size = 240): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 2), new SvgImageBackEnd));

        return $writer->writeString(self::for($user));
    }

    private static function signature(string $token): string
    {
        return substr(hash_hmac('sha256', self::PREFIX.$token, (string) config('app.key')), 0, 20);
    }
}
