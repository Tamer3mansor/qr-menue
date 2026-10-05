<?php

namespace Tests\Feature;

use App\Filament\Pages\ChangePassword;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ChangePasswordPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::auth()->login($this->tenant);
        Filament::setTenant($this->tenant);

        Filament::bootCurrentPanel();
    }

    /**
     * The factory issues every account the same password, so a change that
     * actually happened can be told apart from a no-op.
     */
    private function currentPassword(): string
    {
        return 'password';
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submit(array $overrides = []): Testable
    {
        return Livewire::test(ChangePassword::class)
            ->fillForm(array_merge([
                'current_password' => $this->currentPassword(),
                'new_password' => 'a-much-longer-password',
                'new_password_confirmation' => 'a-much-longer-password',
            ], $overrides))
            ->call('save');
    }

    public function test_the_page_is_listed_in_the_sidebar(): void
    {
        $this->assertSame('Change Password', ChangePassword::getNavigationLabel());

        $this->assertNotNull(ChangePassword::getNavigationIcon());

        $this->assertContains(
            ChangePassword::class,
            array_values(Filament::getPanel('admin')->getPages()),
        );

        $this->actingAs($this->tenant)
            ->get("/admin/{$this->tenant->getKey()}")
            ->assertSuccessful()
            ->assertSee(ChangePassword::getNavigationLabel())
            ->assertSee(ChangePassword::getUrl(tenant: $this->tenant), escape: false);
    }

    /**
     * The save button is only a `type="submit"` button, so it needs the Form
     * component to have rendered the <form wire:submit="save"> around it.
     */
    public function test_the_page_renders_its_form_in_a_real_request(): void
    {
        $response = $this->actingAs($this->tenant)->get(
            ChangePassword::getUrl(tenant: $this->tenant)
        );

        $response->assertSuccessful();
        $response->assertSee('Current password');
        $response->assertSee('New password');
        $response->assertSee('Confirm new password');
        $response->assertSee('<form wire:submit="save"', escape: false);
    }

    public function test_a_guest_is_redirected_to_the_login_page(): void
    {
        auth()->guard('web')->logout();

        Filament::auth()->logout();

        $this->get(ChangePassword::getUrl(tenant: $this->tenant))
            ->assertRedirect(Filament::getPanel('admin')->getLoginUrl());
    }

    public function test_the_password_is_replaced_and_stored_hashed(): void
    {
        $this->submit()->assertHasNoFormErrors();

        $password = $this->tenant->refresh()->password;

        $this->assertNotSame('a-much-longer-password', $password);
        $this->assertTrue(Hash::check('a-much-longer-password', $password));
        $this->assertFalse(Hash::check($this->currentPassword(), $password));
    }

    public function test_a_success_notification_is_sent(): void
    {
        $this->submit()
            ->assertNotified(Notification::make()->title('Password changed.')->success());
    }

    public function test_the_form_is_cleared_after_a_successful_change(): void
    {
        Livewire::test(ChangePassword::class)
            ->fillForm([
                'current_password' => $this->currentPassword(),
                'new_password' => 'a-much-longer-password',
                'new_password_confirmation' => 'a-much-longer-password',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertSet('data.current_password', null)
            ->assertSet('data.new_password', null)
            ->assertSet('data.new_password_confirmation', null);
    }

    public function test_a_wrong_current_password_is_rejected(): void
    {
        $this->submit([
            'current_password' => 'not-the-password',
            'new_password_confirmation' => 'a-much-longer-password',
        ])->assertHasFormErrors(['current_password']);

        $this->assertTrue(Hash::check($this->currentPassword(), $this->tenant->refresh()->password));
    }

    public function test_a_missing_current_password_is_rejected(): void
    {
        $this->submit([
            'current_password' => null,
            'new_password_confirmation' => 'a-much-longer-password',
        ])->assertHasFormErrors(['current_password']);
    }

    public function test_the_new_password_must_be_confirmed(): void
    {
        $this->submit([
            'new_password_confirmation' => 'a-different-password',
        ])->assertHasFormErrors(['new_password']);

        $this->assertTrue(Hash::check($this->currentPassword(), $this->tenant->refresh()->password));
    }

    public function test_the_new_password_must_be_at_least_eight_characters(): void
    {
        $this->submit([
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
        ])->assertHasFormErrors(['new_password']);
    }

    public function test_the_new_password_must_differ_from_the_current_one(): void
    {
        $this->submit([
            'new_password' => $this->currentPassword(),
            'new_password_confirmation' => $this->currentPassword(),
        ])->assertHasFormErrors(['new_password']);
    }

    public function test_only_the_signed_in_tenant_password_is_changed(): void
    {
        $otherTenant = User::factory()->create();

        $this->submit()->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('a-much-longer-password', $this->tenant->refresh()->password));
        $this->assertTrue(Hash::check($this->currentPassword(), $otherTenant->refresh()->password));
    }
}
