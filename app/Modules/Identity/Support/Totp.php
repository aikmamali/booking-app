<?php

declare(strict_types=1);

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

/**
 * RFC 6238 codes (30-second steps, 6 digits) as used by every authenticator app.
 */
final readonly class Totp
{
    /** Accept one step either side for clock drift. */
    private const int WINDOW = 1;

    public function __construct(private Google2FA $engine = new Google2FA) {}

    public function newSecret(): string
    {
        return $this->engine->generateSecretKey(32);
    }

    /**
     * The step the code matched, or null. Steps at or before $lastStep are refused (replay).
     */
    public function verify(string $secret, string $code, ?int $lastStep): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (preg_match('/^\d{6}$/', $code) !== 1) {
            return null;
        }

        // With an old step the engine returns the matched step instead of true.
        $step = $this->engine->verifyKeyNewer($secret, $code, $lastStep ?? 0, self::WINDOW);

        return is_int($step) ? $step : null;
    }

    public function currentCode(string $secret): string
    {
        return $this->engine->getCurrentOtp($secret);
    }

    public function qrSvg(User $user, string $secret): string
    {
        $url = $this->engine->getQRCodeUrl((string) config('app.name'), $user->email, $secret);
        $writer = new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd));

        return $writer->writeString($url);
    }
}
