<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Support\InitialsAvatarProvider;
use App\Modules\Identity\Filament\Auth\TwoFactorChallenge;
use App\Modules\Identity\Filament\Auth\TwoFactorSetup;
use App\Modules\Identity\Http\Middleware\ApplySessionLifetime;
use App\Modules\Identity\Http\Middleware\RequireTwoFactor;
use App\Modules\Identity\Http\Middleware\SetLocale;
use App\Shared\Modules;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
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
            // Export completion notices (RP-02) arrive in the bell.
            ->databaseNotifications()
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->brandName(fn (): string => __('app.name'))
            ->colors([
                'primary' => Color::Teal,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([])
            // ID-07: 2FA pages live outside page discovery so they can use the simple layout.
            ->livewireComponents([
                'identity.two-factor-challenge' => TwoFactorChallenge::class,
                'identity.two-factor-setup' => TwoFactorSetup::class,
            ])
            ->authenticatedRoutes(function (): void {
                Route::get('two-factor/challenge', TwoFactorChallenge::class)->name('two-factor.challenge');
                Route::get('two-factor/setup', TwoFactorSetup::class)->name('two-factor.setup');
            })
            ->userMenuItems([
                MenuItem::make()->label(fn (): string => __('identity.two_factor.menu'))
                    ->icon('heroicon-o-shield-check')
                    ->url(fn (): string => route(RequireTwoFactor::SETUP_ROUTE)),
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
            // Persistent, so Livewire requests from admin pages are checked as well.
            ->authMiddleware([
                Authenticate::class,
                SetLocale::class,
                RequireTwoFactor::class,
            ], isPersistent: true);

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
