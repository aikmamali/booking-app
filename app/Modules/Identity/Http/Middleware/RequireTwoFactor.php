<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\TwoFactorSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ID-07: staff reach the admin panel only after setting up 2FA and passing the challenge
 * in the current session. The two 2FA pages and logout stay reachable.
 */
final readonly class RequireTwoFactor
{
    public const string SETUP_ROUTE = 'filament.admin.two-factor.setup';

    public const string CHALLENGE_ROUTE = 'filament.admin.two-factor.challenge';

    private const array OPEN_ROUTES = [self::SETUP_ROUTE, self::CHALLENGE_ROUTE, 'filament.admin.auth.logout'];

    public function __construct(private TwoFactorSession $session) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! config('identity.two_factor.enforced') || ! $user instanceof User || ! $user->requiresTwoFactor()
            || $request->routeIs(...self::OPEN_ROUTES)) {
            return $next($request);
        }

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route(self::SETUP_ROUTE);
        }

        if (! $this->session->hasPassed($user)) {
            return redirect()->guest(route(self::CHALLENGE_ROUTE));
        }

        return $next($request);
    }
}
