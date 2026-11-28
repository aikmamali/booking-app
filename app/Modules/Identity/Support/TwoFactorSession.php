<?php

declare(strict_types=1);

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Models\User;
use Illuminate\Contracts\Session\Session;

/**
 * Remembers, for the current login session only, that the user passed the 2FA challenge.
 * Logout invalidates the session, so every new login is challenged again.
 */
final readonly class TwoFactorSession
{
    private const string KEY = 'identity.two_factor_passed';

    public function __construct(private Session $session) {}

    public function markPassed(User $user): void
    {
        $this->session->put(self::KEY, $user->getKey());
    }

    public function hasPassed(User $user): bool
    {
        return $this->session->get(self::KEY) === $user->getKey();
    }
}
