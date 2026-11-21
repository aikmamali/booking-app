<?php

declare(strict_types=1);

namespace App\Modules\Identity\Settings;

use Spatie\LaravelSettings\Settings;

final class IdentitySettings extends Settings
{
    /** Idle minutes before a session expires (ID-06). "Remember me" keeps PWA users signed in. */
    public int $session_lifetime_minutes;

    /** Version of the terms and privacy policy a customer accepts at registration (ID-01). */
    public string $terms_version;

    public static function group(): string
    {
        return 'identity';
    }
}
