<?php

namespace App\Traits;

use App\Models\User;

/**
 * Turns a public slug into the tenant that owns it.
 *
 * The same slug rules serve the menu and the website, so they live in one place
 * rather than being restated per controller.
 */
trait ResolvesTenant
{
    /**
     * A custom domain wins over the numeric fallback, so a domain that happens to
     * look like an id can never be shadowed by another restaurant.
     */
    protected function resolveTenant(string $slug): User
    {
        $tenant = User::query()->where('domain', $slug)->first();

        if ($tenant) {
            return $tenant;
        }

        if (ctype_digit($slug)) {
            $tenant = User::query()->find((int) $slug);
        }

        abort_if($tenant === null, 404);

        return $tenant;
    }
}
