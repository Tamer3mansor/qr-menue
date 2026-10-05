<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SuperAdmin;
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
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The platform operator panel.
 *
 * It is deliberately not tenanted and deliberately not the default panel: it
 * sits on its own path and its own session guard, and only users flagged as
 * super admins may enter it.
 */
class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('super-admin')
            ->path('super-admin')
            ->authGuard('super_admin')
            ->login()
            ->colors([
                'primary' => Color::Red,
            ])
            ->databaseNotifications()
            ->discoverResources(
                in: app_path('Filament/SuperAdmin/Resources'),
                for: 'App\Filament\SuperAdmin\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/SuperAdmin/Pages'),
                for: 'App\Filament\SuperAdmin\Pages',
            )
            ->discoverWidgets(
                in: app_path('Filament/SuperAdmin/Widgets'),
                for: 'App\Filament\SuperAdmin\Widgets',
            )
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
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
                SuperAdmin::class,
            ]);
    }
}
