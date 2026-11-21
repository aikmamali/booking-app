<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use App\Modules\Identity\Settings\IdentitySettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ID-06: idle timeout comes from settings. Must run before StartSession.
 */
final readonly class ApplySessionLifetime
{
    public function __construct(private IdentitySettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        config(['session.lifetime' => $this->settings->session_lifetime_minutes]);

        return $next($request);
    }
}
