<?php

namespace App\Providers\Filament;

use App\Filament\SuperAdmin\Pages\StopImpersonating;
use App\Filament\Widgets\QrMenuInfo;
use App\Http\Middleware\SetPanelLocale;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('QR Menu')
            ->brandLogo(fn (): ?string => is_file(public_path('images/logo.png')) ? asset('images/logo.png') : null)
            ->favicon(fn (): ?string => is_file(public_path('images/favicon.png')) ? asset('images/favicon.png') : null)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->tenant(User::class, slugAttribute: 'id', ownershipRelationship: 'user')
            ->tenantSwitcher(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                StopImpersonating::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                QrMenuInfo::class,
            ])
            ->userMenuItems([
                'stop-impersonating' => MenuItem::make()
                    ->label('إيقاف انتحال الهوية')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->url(fn (): string => StopImpersonating::getUrl(tenant: Filament::getTenant()))
                    ->visible(fn (): bool => session()->has(StopImpersonating::SESSION_KEY)),
            ])
            ->middleware([
                // Filament v5 reads its UI locale from the application locale.
                SetPanelLocale::class,
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
            ]);
    }
}
