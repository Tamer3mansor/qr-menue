<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant for a subdomain request such as demo.example.com.
 *
 * The resolved User is bound as `current.tenant` and returned from the
 * middleware, so a controller can accept it as an argument or read the binding.
 */
class IdentifyTenantBySubdomain
{
    /**
     * Subdomains that belong to the platform rather than to a restaurant, so
     * `admin.example.com` is never looked up as a tenant domain.
     *
     * @var list<string>
     */
    public const RESERVED_SUBDOMAINS = [
        'www',
        'admin',
        'super-admin',
        'superadmin',
        'api',
        'app',
        'dashboard',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = $this->subdomainFrom($request->getHost());

        if ($subdomain === null) {
            abort(404);
        }

        $tenant = User::query()->where('domain', $subdomain)->first();

        if ($tenant === null) {
            abort(404);
        }

        app()->instance('current.tenant', $tenant);

        return $next($request);
    }

    /**
     * The subdomain a host belongs to, or null when the host is not one of ours.
     *
     * Returning null rather than guessing keeps bare domains, localhost, the
     * reserved platform hosts and lookalike domains such as
     * `demo.example.com.evil.test` out of the tenant lookup.
     */
    protected function subdomainFrom(string $host): ?string
    {
        $host = strtolower(trim($host));

        $baseDomain = strtolower(trim((string) config('app.base_domain')));

        // With no base domain configured there is nothing to compare against.
        if ($baseDomain === '') {
            return null;
        }

        $suffix = '.'.$baseDomain;

        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $subdomain = substr($host, 0, -strlen($suffix));

        // A nested host such as a.b.example.com is not a tenant.
        if ($subdomain === '' || str_contains($subdomain, '.')) {
            return null;
        }

        // The host is already lowercased, and the list is lowercase too.
        return in_array($subdomain, self::RESERVED_SUBDOMAINS, strict: true) ? null : $subdomain;
    }
}
