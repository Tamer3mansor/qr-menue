<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pins the admin panel to Arabic.
 *
 * Filament v5 resolves every UI string through the application locale, so the
 * panel sets it per request instead of a panel-level locale option (which was
 * removed after v3). Requests outside the panel keep the configured locale.
 */
class SetPanelLocale
{
    public const LOCALE = 'ar';

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(self::LOCALE);

        return $next($request);
    }
}
