<?php

declare(strict_types=1);

use App\Http\Middleware\SecurityHeaders;
use App\Modules\Identity\Http\Middleware\ApplySessionLifetime;
use App\Modules\Identity\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->web(prepend: [ApplySessionLifetime::class], append: [SetLocale::class]);

        $middleware->redirectGuestsTo(fn (): string => route('app.login'));
        $middleware->redirectUsersTo(fn (): string => route('app.home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Error tracking (spec 11): active when SENTRY_LARAVEL_DSN is set.
        Integration::handles($exceptions);
    })->create();
