<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Customer login with throttling: 5 attempts per minute per email and IP (spec section 11).
 */
final class AuthenticateUser
{
    public const int MAX_ATTEMPTS = 5;

    public function handle(string $email, string $password, bool $remember, string $ip): User
    {
        $key = $this->throttleKey($email, $ip);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        if (! Auth::attempt(['email' => $email, 'password' => $password, 'anonymized_at' => null], $remember)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($key);

        /** @var User */
        return Auth::user();
    }

    private function throttleKey(string $email, string $ip): string
    {
        return Str::transliterate(Str::lower($email)).'|'.$ip;
    }
}
