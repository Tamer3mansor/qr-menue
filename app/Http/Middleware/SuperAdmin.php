<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a panel to super admins only.
 *
 * The panel's own guard is resolved from the current panel rather than the
 * default guard, because the super admin panel authenticates against its own
 * session guard.
 */
class SuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->currentUser($request)?->is_super_admin) {
            abort(403);
        }

        return $next($request);
    }

    private function currentUser(Request $request): ?Authenticatable
    {
        $panel = Filament::getCurrentPanel();

        return $panel !== null ? $panel->auth()->user() : $request->user();
    }
}
