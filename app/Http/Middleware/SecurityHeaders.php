<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers of spec section 11. The CSP allows inline and eval scripts because
 * Livewire/Alpine and Filament need them; everything is still limited to this origin
 * (plus the font CDN Filament uses). HSTS only in production, behind HTTPS.
 */
final class SecurityHeaders
{
    private const string CSP = "default-src 'self'; "
        ."script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
        ."style-src 'self' 'unsafe-inline' https://fonts.bunny.net; "
        ."font-src 'self' data: https://fonts.bunny.net; "
        ."img-src 'self' data: blob:; "
        ."connect-src 'self'; "
        ."frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'";

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (! file_exists(public_path('hot'))) {
            // The Vite dev server serves assets from another origin; skip CSP while it runs.
            $headers->set('Content-Security-Policy', self::CSP);
        }

        if (app()->isProduction() && $request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
