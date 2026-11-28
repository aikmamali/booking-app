<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Exceptions\TwoFactorRejected;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\RecoveryCodes;
use App\Modules\Identity\Support\Totp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The login challenge: a current code from the app, or a one-time recovery code.
 * Limited to 5 attempts per minute per user, like the login itself (spec section 11).
 */
final readonly class VerifyTwoFactorChallenge
{
    private const int MAX_ATTEMPTS = 5;

    public function __construct(private Totp $totp) {}

    public function handle(User $user, string $code): void
    {
        $key = 'two-factor:'.$user->getKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw TwoFactorRejected::because('identity.two_factor.throttled', ['seconds' => RateLimiter::availableIn($key)]);
        }

        if (! $user->hasTwoFactorEnabled() || ! $this->accepts($user, $code)) {
            RateLimiter::hit($key);

            throw TwoFactorRejected::because('identity.two_factor.invalid_code');
        }

        RateLimiter::clear($key);
    }

    private function accepts(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code): bool {
            $user = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $step = $this->totp->verify((string) $user->two_factor_secret, $code, $user->two_factor_last_step);

            if ($step !== null) {
                $user->forceFill(['two_factor_last_step' => $step])->save();

                return true;
            }

            return $this->consumeRecoveryCode($user, $code);
        });
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $index = array_search(RecoveryCodes::normalize($code), $codes, true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
        activity()->performedOn($user)->causedBy($user)->log('two_factor_recovery_code_used');

        return true;
    }
}
