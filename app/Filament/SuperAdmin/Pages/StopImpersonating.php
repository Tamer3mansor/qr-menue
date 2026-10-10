<?php

namespace App\Filament\SuperAdmin\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Ends a customer session started through the Login As action.
 *
 * The super admin panel authenticates against its own guard, so the super
 * admin's session is still intact while they are signed in as a customer.
 */
class StopImpersonating extends Page
{
    /**
     * Session key holding the id of the super admin who started impersonating.
     */
    public const SESSION_KEY = 'impersonated_by';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowLeftOnRectangle;

    protected string $view = 'filament.super-admin.stop-impersonating';

    /**
     * The page is shared by the Arabic customer panel and the English super
     * admin panel, so both labels resolve through the translator at runtime.
     */
    public static function getNavigationLabel(): string
    {
        return __('Stop impersonating');
    }

    public function getTitle(): string
    {
        return __('Stop impersonating');
    }

    public static function canAccess(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public function stop(): void
    {
        session()->forget(self::SESSION_KEY);

        Filament::getPanel('admin')->auth()->logout();

        session()->regenerate();

        $this->redirect(Filament::getUrl(), navigate: false);
    }
}
