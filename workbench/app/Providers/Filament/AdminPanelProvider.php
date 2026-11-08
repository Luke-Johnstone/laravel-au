<?php

declare(strict_types=1);

namespace Workbench\App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Workbench\App\Filament\Resources\Suppliers\SupplierResource;
use Workbench\App\Http\Middleware\SignInDemoUser;

/**
 * The demo's Filament panel at /admin.
 */
final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('laravel-au demo')
            ->colors(['primary' => Color::Emerald])
            ->resources([SupplierResource::class])
            ->pages([Dashboard::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                // Here rather than with Authenticate, which Laravel's middleware priority can
                // move ahead of the panel's auth middleware. There's no AuthenticateSession:
                // each build changes the demo user's password hash, and it would log a browser
                // out to a login page this panel doesn't have.
                SignInDemoUser::class,
                ShareErrorsFromSession::class,
                self::csrfMiddleware(),
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Laravel 13 renamed the CSRF middleware and deprecated the old name; Laravel 12 has only the
     * old one.
     *
     * @return class-string
     */
    private static function csrfMiddleware(): string
    {
        return class_exists(PreventRequestForgery::class)
            ? PreventRequestForgery::class
            : VerifyCsrfToken::class;
    }
}
