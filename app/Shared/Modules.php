<?php

declare(strict_types=1);

namespace App\Shared;

/**
 * The bounded contexts of the modular monolith (spec section 5.2).
 * Single source of truth for provider registration, Filament discovery and arch tests.
 */
final class Modules
{
    /** @var list<string> */
    public const array ALL = [
        'Identity',
        'Catalog',
        'Scheduling',
        'Booking',
        'Membership',
        'Billing',
        'Communication',
        'Reporting',
    ];

    public static function namespace(string $module): string
    {
        return 'App\\Modules\\'.$module;
    }

    public static function path(string $module, string $subPath = ''): string
    {
        return app_path('Modules/'.$module.($subPath === '' ? '' : '/'.$subPath));
    }
}
