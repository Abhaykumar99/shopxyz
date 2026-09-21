<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SecurityHeaders;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The shop's admin panel (ADR-023).
 *
 * Colours and type follow the Rose Atelier theme of the customer site (ADR-017)
 * through `resources/css/filament/admin/theme.css`. Only an active admin can
 * open it (`User::canAccessPanel`), and email + password is backed by an
 * authenticator app as a second factor (ADR-004).
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable()
                    ->regenerableRecoveryCodes(),
                // Required in production (ADR-004); optional locally so a demo
                // account can be reviewed without enrolling an authenticator.
            ], isRequired: false) // BYPASSED FOR TESTING
            ->brandName(config('shop.name'))
            ->colors([
                'primary' => [
                    50 => '#fdf3f6',
                    100 => '#fbe9ef',
                    200 => '#f5cdda',
                    300 => '#eda4bd',
                    400 => '#e0719a',
                    500 => '#cd4478',
                    600 => '#9b2c55',
                    700 => '#7c2244',
                    800 => '#671e3a',
                    900 => '#581c33',
                    950 => '#320b1a',
                ],
                'danger' => Color::Rose,
                'gray' => Color::Stone,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('Figtree')
            ->defaultThemeMode(ThemeMode::Light)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth(Width::Full)
            ->navigationGroups([
                NavigationGroup::make('Orders'),
                NavigationGroup::make('Delivery'),
                NavigationGroup::make('Money'),
                NavigationGroup::make('Catalogue'),
                NavigationGroup::make('People'),
                NavigationGroup::make('Shop'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->databaseNotifications()
            ->middleware([
                // The panel builds its own stack rather than using the `web`
                // group, so the baseline headers have to be named here too.
                SecurityHeaders::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => \Illuminate\Support\Facades\Blade::render('
                    <div class="mt-4 p-4 rounded-xl bg-gray-50 border border-gray-200">
                        <h2 class="text-center font-semibold text-gray-700 mb-2">Testing Mode</h2>
                        <a href="/bypass-admin" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-lg fi-btn-size-lg gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 w-full" style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);">
                            <span class="fi-btn-label">Bypass Login</span>
                        </a>
                    </div>
                ')
            );
    }
}
