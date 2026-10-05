<?php

namespace Tests\Feature;

use App\Filament\SuperAdmin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\SuperAdmin\Resources\Tenants\Pages\EditTenant;
use App\Filament\SuperAdmin\Resources\Tenants\Pages\ListTenants;
use App\Filament\SuperAdmin\Widgets\ExpiringSubscriptions;
use App\Filament\SuperAdmin\Widgets\TotalCustomers;
use App\Models\QrCode;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\TenantDomainChangedNotification;
use App\Notifications\TenantWelcomeNotification;
use App\Services\QrCodeService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    /**
     * Livewire component tests resolve URLs against the current panel, which
     * defaults to the admin panel, so it is switched only where needed.
     */
    private function actingAsSuperAdmin(): static
    {
        return $this->actingAs($this->superAdmin, 'super_admin');
    }

    /**
     * Livewire component tests build their URLs from the current panel, which
     * defaults to the admin panel. The switch is reset afterwards so it cannot
     * leak into a later request to the customer panel.
     */
    private function onSuperAdminPanel(): static
    {
        Filament::setCurrentPanel('super-admin');

        return $this;
    }

    private function offSuperAdminPanel(): static
    {
        Filament::setCurrentPanel(null);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submitCreateTenant(array $overrides = []): Testable
    {
        Notification::fake();

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        return Livewire::test(CreateTenant::class)
            ->fillForm(array_merge([
                'name' => 'Kebab Palace',
                'email' => 'kebab@example.com',
                'password' => 'secret-password',
                'domain' => 'kebab-palace',
                'is_active' => true,
            ], $overrides))
            ->call('create');
    }

    private function tenantByEmail(string $email = 'kebab@example.com'): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    private function tenantCount(): int
    {
        return User::query()->where('is_super_admin', false)->count();
    }

    public function test_a_super_admin_can_reach_the_super_admin_panel(): void
    {
        $this->actingAs($this->superAdmin, 'super_admin')
            ->get('/super-admin')
            ->assertSuccessful();
    }

    public function test_a_super_admin_can_reach_the_tenant_list(): void
    {
        User::factory()->create(['name' => 'Kebab Palace']);

        $this->actingAs($this->superAdmin, 'super_admin')
            ->get('/super-admin/tenants')
            ->assertSuccessful()
            ->assertSee('Kebab Palace');
    }

    public function test_a_customer_is_forbidden_from_the_super_admin_panel(): void
    {
        $tenant = User::factory()->create();

        $this->actingAs($tenant, 'super_admin')
            ->get('/super-admin')
            ->assertForbidden();
    }

    public function test_a_customer_cannot_manage_tenants_even_with_a_super_admin_session(): void
    {
        $tenant = User::factory()->create();

        $this->actingAs($tenant, 'super_admin')
            ->get('/super-admin/tenants')
            ->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_super_admin_login(): void
    {
        $this->get('/super-admin')
            ->assertRedirect('/super-admin/login');
    }

    public function test_a_super_admin_cannot_enter_the_customer_panel(): void
    {
        // Signed in on the customer guard, but a super admin owns no tenant.
        $this->actingAs($this->superAdmin)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_a_super_admin_is_not_listed_as_a_tenant(): void
    {
        $tenant = User::factory()->create();

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(ListTenants::class)
            ->assertCanSeeTableRecords([$tenant])
            ->assertCanNotSeeTableRecords([$this->superAdmin]);
    }

    public function test_super_admins_are_excluded_from_the_total_customer_count(): void
    {
        User::factory()->count(3)->create();

        Livewire::test(TotalCustomers::class)
            ->assertSee('Total customers')
            ->assertSee('3');
    }

    public function test_the_active_toggle_stores_the_change(): void
    {
        $tenant = User::factory()->create(['is_active' => true]);

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(ListTenants::class)
            ->call('updateTableColumnState', 'is_active', $tenant->getKey(), false);

        $this->assertFalse($tenant->refresh()->is_active);
    }

    public function test_the_active_toggle_revokes_access(): void
    {
        $tenant = User::factory()->create(['is_active' => true]);

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(ListTenants::class)
            ->call('updateTableColumnState', 'is_active', $tenant->getKey(), false);

        $this->offSuperAdminPanel();

        // Refetched because the toggle writes through another instance.
        $deactivated = $tenant->fresh();

        $this->assertFalse($deactivated->is_active);
        $this->assertFalse($deactivated->canAccessPanel(Filament::getPanel('admin')));
        $this->assertFalse($deactivated->hasRunningSubscription());
    }

    public function test_deactivating_a_tenant_closes_their_public_menu(): void
    {
        $tenant = User::factory()->create(['is_active' => true]);
        Setting::factory()->create(['user_id' => $tenant, 'restaurant_name' => 'Kebab Palace']);

        $this->get("/menu/{$tenant->getKey()}")
            ->assertSee('Kebab Palace');

        $tenant->forceFill(['is_active' => false])->save();

        // The subscription page is generic on purpose, so the restaurant name
        // disappearing is what proves the menu was replaced.
        $this->get("/menu/{$tenant->getKey()}")
            ->assertDontSee('Kebab Palace');
    }

    public function test_the_domain_can_be_edited_inline(): void
    {
        $tenant = User::factory()->create(['domain' => null]);

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(ListTenants::class)
            ->call('updateTableColumnState', 'domain', $tenant->getKey(), 'kebab-palace');

        $this->assertSame('kebab-palace', $tenant->refresh()->domain);
    }

    public function test_the_subscription_form_stores_a_null_expiry_as_never_lapsing(): void
    {
        $tenant = User::factory()->create(['subscription_expires_at' => now()->addMonth()]);

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(EditTenant::class, ['record' => $tenant->getKey()])
            ->fillForm([
                'name' => $tenant->name,
                'email' => $tenant->email,
                'domain' => $tenant->domain,
                'is_active' => true,
                'subscription_expires_at' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($tenant->refresh()->subscription_expires_at);
        $this->assertTrue($tenant->hasRunningSubscription());
    }

    public function test_the_qr_code_survives_a_domain_change(): void
    {
        $tenant = User::factory()->create(['domain' => 'old-slug']);

        $expected = "/menu/{$tenant->getKey()}";

        $this->assertStringEndsWith($expected, (string) $tenant->refresh()->qrCode?->url);

        $tenant->forceFill(['domain' => 'new-slug'])->save();

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(ListTenants::class)
            ->callTableAction('regenerateQr', $tenant);

        $this->assertStringEndsWith($expected, (string) $tenant->refresh()->qrCode?->url);
    }

    public function test_regenerating_the_qr_code_creates_one_when_it_is_missing(): void
    {
        $tenant = User::factory()->create();
        $tenant->qrCode()->delete();

        $this->assertNull($tenant->refresh()->qrCode);

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(ListTenants::class)
            ->callTableAction('regenerateQr', $tenant);

        $this->assertNotNull($tenant->refresh()->qrCode);
    }

    public function test_editing_the_domain_regenerates_the_qr_code(): void
    {
        $tenant = User::factory()->create(['domain' => 'old-slug']);

        $expected = "/menu/{$tenant->getKey()}";

        $this->assertStringEndsWith($expected, (string) $tenant->refresh()->qrCode?->url);

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(EditTenant::class, ['record' => $tenant->getKey()])
            ->fillForm([
                'name' => $tenant->name,
                'email' => $tenant->email,
                'domain' => 'new-slug',
                'is_active' => true,
                'subscription_expires_at' => $tenant->subscription_expires_at,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('new-slug', $tenant->refresh()->domain);
        $this->assertStringEndsWith($expected, (string) $tenant->refresh()->qrCode?->url);
    }

    public function test_clearing_the_domain_regenerates_the_qr_code_for_the_id_address(): void
    {
        $tenant = User::factory()->create(['domain' => 'old-slug']);

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(EditTenant::class, ['record' => $tenant->getKey()])
            ->fillForm([
                'name' => $tenant->name,
                'email' => $tenant->email,
                'domain' => null,
                'is_active' => true,
                'subscription_expires_at' => $tenant->subscription_expires_at,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($tenant->refresh()->domain);
        $this->assertStringEndsWith("/menu/{$tenant->getKey()}", (string) $tenant->refresh()->qrCode?->url);
    }

    public function test_saving_without_changing_the_domain_leaves_the_qr_code_alone(): void
    {
        $tenant = User::factory()->create(['domain' => 'same-slug']);

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        $this->mock(QrCodeService::class)
            ->shouldNotReceive('generateForTenant');

        Livewire::test(EditTenant::class, ['record' => $tenant->getKey()])
            ->fillForm([
                'name' => 'Renamed Kebab Palace',
                'email' => $tenant->email,
                'domain' => $tenant->domain,
                'is_active' => true,
                'subscription_expires_at' => $tenant->subscription_expires_at,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed Kebab Palace', $tenant->refresh()->name);
        $this->assertStringEndsWith("/menu/{$tenant->getKey()}", (string) $tenant->refresh()->qrCode?->url);
    }

    public function test_editing_the_domain_inline_also_regenerates_the_qr_code(): void
    {
        $tenant = User::factory()->create(['domain' => 'old-slug']);

        $expected = "/menu/{$tenant->getKey()}";

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(ListTenants::class)
            ->call('updateTableColumnState', 'domain', $tenant->getKey(), 'inline-slug');

        $this->assertSame('inline-slug', $tenant->refresh()->domain);
        $this->assertStringEndsWith($expected, (string) $tenant->refresh()->qrCode?->url);
    }

    public function test_a_domain_change_notifies_super_admins_on_the_database_channel(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $tenant = User::factory()->create(['domain' => 'old-slug']);

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(EditTenant::class, ['record' => $tenant->getKey()])
            ->fillForm([
                'name' => $tenant->name,
                'email' => $tenant->email,
                'domain' => 'new-slug',
                'is_active' => true,
                'subscription_expires_at' => $tenant->subscription_expires_at,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $notification = $superAdmin->notifications()->sole();

        $this->assertSame(TenantDomainChangedNotification::class, $notification->type);
        $this->assertNull($notification->read_at);

        $data = $notification->data;

        $this->assertSame($tenant->getKey(), $data['tenant_id']);
        $this->assertSame('old-slug', $data['previous_domain']);
        $this->assertSame('new-slug', $data['new_domain']);
        $this->assertStringEndsWith("/menu/{$tenant->getKey()}", $data['qr_code_url']);
    }

    public function test_a_tenant_is_not_notified_about_its_own_domain_change(): void
    {
        $tenant = User::factory()->create(['domain' => 'old-slug']);

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(EditTenant::class, ['record' => $tenant->getKey()])
            ->fillForm([
                'name' => $tenant->name,
                'email' => $tenant->email,
                'domain' => 'new-slug',
                'is_active' => true,
                'subscription_expires_at' => $tenant->subscription_expires_at,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertCount(0, $tenant->refresh()->notifications);
    }

    public function test_a_save_that_leaves_the_domain_alone_notifies_nobody(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $tenant = User::factory()->create(['domain' => 'same-slug']);

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(EditTenant::class, ['record' => $tenant->getKey()])
            ->fillForm([
                'name' => 'Renamed Kebab Palace',
                'email' => $tenant->email,
                'domain' => $tenant->domain,
                'is_active' => false,
                'subscription_expires_at' => $tenant->subscription_expires_at,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertCount(0, $superAdmin->refresh()->notifications);
    }

    public function test_a_customer_cannot_reach_a_tenant_record(): void
    {
        $tenant = User::factory()->create();

        $this->actingAs($tenant, 'super_admin')
            ->get("/super-admin/tenants/{$tenant->getKey()}/edit")
            ->assertForbidden();
    }

    public function test_subscriptions_expiring_within_thirty_days_are_counted(): void
    {
        $expiring = User::factory()->create(['subscription_expires_at' => now()->addDays(10)]);
        $farAway = User::factory()->create(['subscription_expires_at' => now()->addDays(90)]);
        $neverExpires = User::factory()->create(['subscription_expires_at' => null]);
        $alreadyLapsed = User::factory()->create(['subscription_expires_at' => now()->subDay()]);

        $this->assertTrue($expiring->isExpiringSoon());
        $this->assertFalse($farAway->isExpiringSoon());
        $this->assertFalse($neverExpires->isExpiringSoon());
        $this->assertFalse($alreadyLapsed->isExpiringSoon());

        $this->assertSame(1, User::query()
            ->where('is_super_admin', false)
            ->subscriptionExpiringWithin()
            ->count());

        Livewire::test(ExpiringSubscriptions::class)
            ->assertSee('Expiring within 30 days');
    }

    public function test_a_lapsed_subscription_is_not_reported_as_expiring(): void
    {
        $this->assertFalse(
            User::factory()->create(['subscription_expires_at' => now()->subMinutes(1)])
                ->isExpiringSoon()
        );
    }

    public function test_a_tenant_can_be_created_with_the_given_details(): void
    {
        $this->submitCreateTenant()->assertHasNoFormErrors();

        $tenant = $this->tenantByEmail();

        $this->assertSame('Kebab Palace', $tenant->name);
        $this->assertSame('kebab-palace', $tenant->domain);
        $this->assertTrue($tenant->is_active);
        $this->assertFalse($tenant->is_super_admin);
        $this->assertSame(
            now()->addYear()->format('Y-m-d'),
            $tenant->subscription_expires_at->format('Y-m-d'),
        );
    }

    public function test_the_new_tenants_password_is_stored_hashed(): void
    {
        $this->submitCreateTenant()->assertHasNoFormErrors();

        $password = $this->tenantByEmail()->password;

        $this->assertNotSame('secret-password', $password);
        $this->assertTrue(Hash::check('secret-password', $password));
    }

    public function test_settings_are_created_for_a_new_tenant(): void
    {
        $this->submitCreateTenant()->assertHasNoFormErrors();

        $tenant = $this->tenantByEmail();

        $this->assertDatabaseHas('settings', [
            'user_id' => $tenant->getKey(),
            'restaurant_name' => 'Kebab Palace',
        ]);
    }

    public function test_a_qr_code_is_created_for_a_new_tenant(): void
    {
        $this->submitCreateTenant()->assertHasNoFormErrors();

        $tenant = $this->tenantByEmail();

        $qrCode = $tenant->refresh()->qrCode;

        $this->assertNotNull($qrCode);
        $this->assertStringEndsWith("/menu/{$tenant->getKey()}", (string) $qrCode->url);
        Storage::disk(QrCode::DISK)->assertExists($qrCode->image_path);
    }

    public function test_the_credentials_email_is_sent_to_a_new_tenant(): void
    {
        $this->submitCreateTenant()->assertHasNoFormErrors();

        $tenant = $this->tenantByEmail();

        Notification::assertSentTo(
            $tenant,
            TenantWelcomeNotification::class,
            function (TenantWelcomeNotification $notification, array $channels, User $notifiable): bool {
                $this->assertSame(['mail'], $channels);

                return implode("\n", $notification->bodyFor($notifiable)) === implode("\n", [
                    'تم إنشاء حسابك في QR Menu',
                    'الإيميل: kebab@example.com',
                    'الباسوورد: secret-password',
                    'رابط الدخول: '.rtrim((string) config('app.url'), '/').'/admin',
                ]);
            },
        );
    }

    public function test_the_email_body_is_rendered_as_a_mail_message(): void
    {
        $this->submitCreateTenant()->assertHasNoFormErrors();

        $tenant = $this->tenantByEmail();

        Notification::assertSentTo(
            $tenant,
            TenantWelcomeNotification::class,
            function (TenantWelcomeNotification $notification, array $channels, User $notifiable): bool {
                $mail = $notification->toMail($notifiable);

                $rendered = implode("\n", array_merge($mail->introLines, $mail->outroLines));

                return str_contains($rendered, 'الباسوورد: secret-password')
                    && str_contains($rendered, '/admin');
            },
        );
    }

    public function test_the_domain_may_be_left_empty(): void
    {
        $this->submitCreateTenant(['domain' => null])->assertHasNoFormErrors();

        $this->assertNull($this->tenantByEmail()->domain);
    }

    public function test_a_new_tenant_is_addressed_by_id_whether_or_not_it_has_a_domain(): void
    {
        $this->submitCreateTenant()->assertHasNoFormErrors();

        $tenant = $this->tenantByEmail();

        $this->assertSame('kebab-palace', $tenant->domain);
        $this->assertStringEndsWith("/menu/{$tenant->getKey()}", (string) $tenant->refresh()->qrCode?->url);
    }

    public function test_the_password_is_required_and_must_be_at_least_eight_characters(): void
    {
        $this->submitCreateTenant(['password' => 'short'])
            ->assertHasFormErrors(['password']);

        $this->assertSame(0, User::query()->where('is_super_admin', false)->count());
    }

    public function test_the_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $tenantsBefore = $this->tenantCount();

        $this->submitCreateTenant(['email' => 'taken@example.com'])
            ->assertHasFormErrors(['email']);

        $this->assertSame($tenantsBefore, $this->tenantCount());
    }

    public function test_the_domain_must_be_unique(): void
    {
        User::factory()->create(['domain' => 'taken-slug']);

        $tenantsBefore = $this->tenantCount();

        $this->submitCreateTenant(['domain' => 'taken-slug'])
            ->assertHasFormErrors(['domain']);

        $this->assertSame($tenantsBefore, $this->tenantCount());
    }

    public function test_the_emailed_credentials_are_valid_for_the_customer_panel(): void
    {
        $this->submitCreateTenant()->assertHasNoFormErrors();

        $tenant = $this->tenantByEmail();

        // The same check the panel login performs.
        $this->assertTrue(Auth::guard('web')->validate([
            'email' => $tenant->email,
            'password' => 'secret-password',
        ]));

        $this->assertFalse(Auth::guard('web')->validate([
            'email' => $tenant->email,
            'password' => 'not-the-password',
        ]));
    }

    public function test_editing_a_tenant_does_not_show_a_password_field(): void
    {
        $tenant = User::factory()->create();

        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(EditTenant::class, ['record' => $tenant->getKey()])
            ->assertFormFieldDoesNotExist('password');
    }

    public function test_the_create_form_shows_a_password_field(): void
    {
        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(CreateTenant::class)
            ->assertFormFieldExists('password');
    }

    public function test_the_tenant_list_offers_a_create_button(): void
    {
        $this->actingAsSuperAdmin()->onSuperAdminPanel();

        Livewire::test(ListTenants::class)
            ->assertOk()
            ->assertActionExists('create')
            ->assertActionHasUrl('create', CreateTenant::getUrl());
    }
}
