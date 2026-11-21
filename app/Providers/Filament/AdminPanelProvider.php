<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Modules\Identity\Http\Middleware\ApplySessionLifetime;
use App\Modules\Identity\Http\Middleware\SetLocale;
use App\Shared\Modules;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn (): string => __('app.name'))
            ->colors([
                'primary' => Color::Teal,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                ApplySessionLifetime::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                SetLocale::class,
            ]);

        return $this->discoverModuleComponents($panel);
    }

    /**
     * Each module keeps its own Filament resources, pages and widgets under <Module>/Filament.
     */
    private function discoverModuleComponents(Panel $panel): Panel
    {
        foreach (Modules::ALL as $module) {
            $path = Modules::path($module, 'Filament');
            $namespace = Modules::namespace($module).'\\Filament';

            $panel
                ->discoverResources(in: $path.'/Resources', for: $namespace.'\\Resources')
                ->discoverPages(in: $path.'/Pages', for: $namespace.'\\Pages')
                ->discoverWidgets(in: $path.'/Widgets', for: $namespace.'\\Widgets');
        }

        return $panel;
    }
}
