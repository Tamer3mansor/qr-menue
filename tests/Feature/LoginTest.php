<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login as LoginPage;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_page_renders_the_email_and_password_fields(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertSee('type="password"', false);
        $response->assertSee('wire:model="data.email"', false);
        $response->assertSee('wire:model="data.password"', false);
    }

    public function test_a_customer_can_log_in_with_their_password(): void
    {
        $tenant = User::factory()->create([
            'email' => 'kebab@example.com',
            'password' => bcrypt('secret-password'),
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(LoginPage::class)
            ->fillForm([
                'email' => 'kebab@example.com',
                'password' => 'secret-password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($tenant);
    }

    public function test_a_super_admin_can_log_in_through_the_super_admin_panel(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('secret-password'),
        ]);

        Filament::setCurrentPanel(Filament::getPanel('super-admin'));

        Livewire::test(LoginPage::class)
            ->fillForm([
                'email' => 'admin@example.com',
                'password' => 'secret-password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($superAdmin, 'super_admin');
    }
}
