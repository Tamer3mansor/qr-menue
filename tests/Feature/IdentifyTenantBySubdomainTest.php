<?php

namespace Tests\Feature;

use App\Http\Middleware\IdentifyTenantBySubdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class IdentifyTenantBySubdomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.base_domain' => 'example.com']);
    }

    public function test_it_binds_the_tenant_that_owns_the_subdomain(): void
    {
        $tenant = $this->tenantWithDomain('demo');

        $this->middlewareFor('demo.example.com');

        $this->assertTrue(app('current.tenant')->is($tenant));
    }

    public function test_it_binds_the_matching_tenant_and_not_another(): void
    {
        $this->tenantWithDomain('demo');
        $expected = $this->tenantWithDomain('kebab');

        $this->middlewareFor('kebab.example.com');

        $this->assertTrue(app('current.tenant')->is($expected));
    }

    public function test_an_unknown_subdomain_is_a_404(): void
    {
        $this->tenantWithDomain('demo');

        $this->assertStatus(404, fn () => $this->middlewareFor('nobody.example.com'));
    }

    public function test_a_tenant_without_a_domain_is_never_matched(): void
    {
        User::factory()->create(['domain' => null]);

        $this->assertStatus(404, fn () => $this->middlewareFor('nobody.example.com'));
    }

    public function test_a_super_admin_domain_is_not_a_tenant_website(): void
    {
        User::factory()->superAdmin()->create(['domain' => 'admin']);

        $this->assertStatus(404, fn () => $this->middlewareFor('admin.example.com'));
    }

    /**
     * @return list<array{string}>
     */
    public static function reservedSubdomains(): array
    {
        return array_map(
            fn (string $subdomain): array => [$subdomain],
            IdentifyTenantBySubdomain::RESERVED_SUBDOMAINS,
        );
    }

    #[DataProvider('reservedSubdomains')]
    public function test_a_reserved_subdomain_is_a_404(string $subdomain): void
    {
        User::factory()->create(['domain' => $subdomain]);

        $this->assertStatus(404, fn () => $this->middlewareFor("{$subdomain}.example.com"));
    }

    public function test_the_apex_domain_is_not_a_tenant(): void
    {
        User::factory()->create(['domain' => 'example.com']);

        $this->assertStatus(404, fn () => $this->middlewareFor('example.com'));
    }

    public function test_the_base_domain_itself_is_a_404(): void
    {
        $this->assertStatus(404, fn () => $this->middlewareFor('example.com'));
    }

    public function test_a_foreign_domain_is_a_404(): void
    {
        $this->tenantWithDomain('demo');

        $this->assertStatus(404, fn () => $this->middlewareFor('demo.other.com'));
    }

    /**
     * A host that merely ends with the base domain as a string, such as
     * "notexample.com", must not resolve a tenant.
     */
    public function test_a_lookalike_domain_is_a_404(): void
    {
        $this->tenantWithDomain('demo');

        $this->assertStatus(404, fn () => $this->middlewareFor('demo.example.com.evil.test'));
        $this->assertStatus(404, fn () => $this->middlewareFor('demoexample.com'));
        $this->assertStatus(404, fn () => $this->middlewareFor('demo.notexample.com'));
    }

    public function test_a_nested_subdomain_is_a_404(): void
    {
        $this->tenantWithDomain('demo');

        $this->assertStatus(404, fn () => $this->middlewareFor('a.demo.example.com'));
    }

    public function test_an_uppercase_host_still_resolves(): void
    {
        $tenant = $this->tenantWithDomain('demo');

        $this->middlewareFor('DEMO.EXAMPLE.COM');

        $this->assertTrue(app('current.tenant')->is($tenant));
    }

    public function test_localhost_is_a_404(): void
    {
        $this->tenantWithDomain('demo');

        $this->assertStatus(404, fn () => $this->middlewareFor('demo.localhost'));
        $this->assertStatus(404, fn () => $this->middlewareFor('localhost'));
    }

    public function test_nothing_resolves_without_a_base_domain(): void
    {
        config(['app.base_domain' => '']);

        $this->tenantWithDomain('demo');

        $this->assertStatus(404, fn () => $this->middlewareFor('demo.example.com'));
    }

    private function tenantWithDomain(string $domain): User
    {
        return User::factory()->create(['domain' => $domain]);
    }

    /**
     * Runs the middleware for a host and returns the response, or aborts.
     */
    private function middlewareFor(string $host): mixed
    {
        $request = Request::create("https://{$host}/");

        $middleware = new IdentifyTenantBySubdomain;

        return $middleware->handle($request, fn ($request) => new Response('ok'));
    }

    private function assertStatus(int $expected, callable $callback): void
    {
        try {
            $callback();

            $this->fail("Expected the middleware to abort with {$expected}.");
        } catch (HttpException $exception) {
            $this->assertSame($expected, $exception->getStatusCode());
        }
    }
}
