<?php

namespace Tests\Feature;

use App\Filament\Pages\RestaurantSettings;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RestaurantSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create(['name' => 'Kebab Palace']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::auth()->login($this->tenant);
        Filament::setTenant($this->tenant);

        Filament::bootCurrentPanel();
    }

    /**
     * A page with a form but no view of its own renders an empty body, so the
     * fields have to be asserted through a real request rather than the schema.
     *
     * The save button is only a `type="submit"` button, so it needs the Form
     * component to have rendered the <form wire:submit="save"> around it.
     */
    public function test_the_page_renders_its_form_in_a_real_request(): void
    {
        $response = $this->actingAs($this->tenant)->get(
            RestaurantSettings::getUrl(tenant: $this->tenant)
        );

        $response->assertSuccessful();
        $response->assertSee('اسم المطعم');
        $response->assertSee('<form wire:submit="save"', escape: false);
    }

    public function test_it_is_listed_in_the_sidebar(): void
    {
        $this->assertSame('إعدادات المطعم', RestaurantSettings::getNavigationLabel());

        $this->assertNotNull(RestaurantSettings::getNavigationIcon());

        $this->assertContains(
            RestaurantSettings::class,
            array_values(Filament::getPanel('admin')->getPages()),
        );

        $this->actingAs($this->tenant)
            ->get("/admin/{$this->tenant->getKey()}")
            ->assertSuccessful()
            ->assertSee(RestaurantSettings::getNavigationLabel())
            ->assertSee(RestaurantSettings::getUrl(tenant: $this->tenant), escape: false);
    }

    public function test_the_page_creates_a_settings_record_when_none_exists(): void
    {
        $this->assertDatabaseCount('settings', 0);

        Livewire::test(RestaurantSettings::class)
            ->assertOk();

        $this->assertDatabaseCount('settings', 1);

        $setting = Setting::query()->firstOrFail();

        $this->assertTrue($setting->user->is($this->tenant));
        $this->assertSame('Kebab Palace', $setting->restaurant_name);
        $this->assertSame('#000000', $setting->primary_color);
        $this->assertSame('#ffffff', $setting->secondary_color);
    }

    public function test_the_page_reuses_the_existing_settings_record(): void
    {
        $existing = Setting::factory()->create([
            'user_id' => $this->tenant,
            'restaurant_name' => 'Existing Name',
        ]);

        Livewire::test(RestaurantSettings::class)
            ->assertOk()
            ->assertFormSet(['restaurant_name' => 'Existing Name']);

        $this->assertDatabaseCount('settings', 1);
        $this->assertSame($existing->getKey(), Setting::query()->firstOrFail()->getKey());
    }

    public function test_saving_updates_the_existing_settings_record_instead_of_creating_another_one(): void
    {
        $existing = Setting::factory()->create([
            'user_id' => $this->tenant,
            'restaurant_name' => 'Old Name',
        ]);

        Livewire::test(RestaurantSettings::class)
            ->fillForm([
                'restaurant_name' => 'New Name',
                'phone' => '01000000000',
                'primary_color' => '#ff0000',
                'secondary_color' => '#00ff00',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('settings', 1);

        $setting = $existing->refresh();

        $this->assertSame('New Name', $setting->restaurant_name);
        $this->assertSame('01000000000', $setting->phone);
        $this->assertSame('#ff0000', $setting->primary_color);
        $this->assertSame('#00ff00', $setting->secondary_color);
    }

    public function test_saving_creates_the_settings_record_when_it_is_missing(): void
    {
        Livewire::test(RestaurantSettings::class)
            ->call('save');

        $this->assertDatabaseHas('settings', [
            'user_id' => $this->tenant->id,
            'restaurant_name' => 'Kebab Palace',
        ]);
    }

    public function test_saving_sends_a_success_notification(): void
    {
        Livewire::test(RestaurantSettings::class)
            ->fillForm(['restaurant_name' => 'New Name'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified(Notification::make()->title('تم حفظ الإعدادات.')->success());
    }

    public function test_saving_does_not_redirect(): void
    {
        Livewire::test(RestaurantSettings::class)
            ->fillForm(['restaurant_name' => 'New Name'])
            ->call('save')
            ->assertNoRedirect();
    }

    public function test_the_restaurant_name_is_required(): void
    {
        Setting::factory()->create(['user_id' => $this->tenant]);

        Livewire::test(RestaurantSettings::class)
            ->fillForm(['restaurant_name' => null])
            ->call('save')
            ->assertHasFormErrors(['restaurant_name']);

        $this->assertDatabaseMissing('settings', ['restaurant_name' => null]);
    }

    public function test_the_phone_is_optional(): void
    {
        Setting::factory()->create(['user_id' => $this->tenant, 'phone' => '01000000000']);

        Livewire::test(RestaurantSettings::class)
            ->fillForm(['phone' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('settings', [
            'user_id' => $this->tenant->id,
            'phone' => null,
        ]);
    }

    public function test_the_currency_defaults_to_the_default_currency(): void
    {
        Livewire::test(RestaurantSettings::class)
            ->assertFormSet(['currency' => Setting::DEFAULT_CURRENCY]);

        $this->assertDatabaseHas('settings', [
            'user_id' => $this->tenant->id,
            'currency' => 'ج.م',
        ]);
    }

    public function test_the_currency_can_be_changed(): void
    {
        Setting::factory()->create(['user_id' => $this->tenant]);

        Livewire::test(RestaurantSettings::class)
            ->fillForm(['currency' => '$'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('settings', [
            'user_id' => $this->tenant->id,
            'currency' => '$',
        ]);
    }

    public function test_the_currency_is_optional(): void
    {
        Setting::factory()->create(['user_id' => $this->tenant]);

        Livewire::test(RestaurantSettings::class)
            ->fillForm(['currency' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = Setting::query()->firstOrFail();

        $this->assertNull($setting->currency);
        $this->assertSame(Setting::DEFAULT_CURRENCY, Setting::currencyFor($this->tenant));
    }

    public function test_the_logo_is_stored_on_the_public_disk_in_the_logos_directory(): void
    {
        Setting::factory()->create(['user_id' => $this->tenant]);

        Livewire::test(RestaurantSettings::class)
            ->fillForm([
                'logo' => [
                    UploadedFile::fake()->image('logo.png'),
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = Setting::query()->firstOrFail();

        $this->assertNotNull($setting->logo);
        $this->assertStringStartsWith('logos/', $setting->logo);
        Storage::disk('public')->assertExists($setting->logo);
    }

    public function test_the_background_image_is_stored_on_the_public_disk_in_the_backgrounds_directory(): void
    {
        Setting::factory()->create(['user_id' => $this->tenant]);

        Livewire::test(RestaurantSettings::class)
            ->fillForm([
                'bg_image' => [
                    UploadedFile::fake()->image('bg.jpg'),
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = Setting::query()->firstOrFail();

        $this->assertNotNull($setting->bg_image);
        $this->assertStringStartsWith('backgrounds/', $setting->bg_image);
        Storage::disk('public')->assertExists($setting->bg_image);
    }

    public function test_the_logo_rejects_files_larger_than_two_megabytes(): void
    {
        Setting::factory()->create(['user_id' => $this->tenant]);

        Livewire::test(RestaurantSettings::class)
            ->fillForm([
                'logo' => [
                    UploadedFile::fake()->image('huge.png')->size(2049),
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['logo']);
    }

    public function test_the_logo_only_accepts_jpg_png_and_webp(): void
    {
        Setting::factory()->create(['user_id' => $this->tenant]);

        Livewire::test(RestaurantSettings::class)
            ->fillForm([
                'logo' => [
                    UploadedFile::fake()->create('document.pdf', 'application/pdf', 10),
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['logo']);
    }

    public function test_another_user_cannot_open_the_first_users_settings_page(): void
    {
        $otherUser = User::factory()->create();

        Setting::factory()->create([
            'user_id' => $this->tenant,
            'restaurant_name' => 'Secret Name',
        ]);

        $response = $this->actingAs($otherUser)->get(
            RestaurantSettings::getUrl(tenant: $this->tenant)
        );

        // Filament hides the tenant behind a 404 instead of a 403 so that the
        // existence of another tenant is not leaked.
        $response->assertNotFound();
    }

    public function test_another_user_sees_their_own_settings_not_the_first_users_settings(): void
    {
        $otherUser = User::factory()->create(['name' => 'Other Owner']);

        Setting::factory()->create([
            'user_id' => $this->tenant,
            'restaurant_name' => 'Secret Name',
        ]);

        Filament::auth()->login($otherUser);
        Filament::setTenant($otherUser);

        Livewire::test(RestaurantSettings::class)
            ->assertOk()
            ->assertFormSet(['restaurant_name' => 'Other Owner']);

        $this->assertDatabaseCount('settings', 2);
        $this->assertDatabaseHas('settings', [
            'user_id' => $this->tenant->id,
            'restaurant_name' => 'Secret Name',
        ]);
    }

    public function test_another_user_cannot_update_the_first_users_settings(): void
    {
        $otherUser = User::factory()->create(['name' => 'Other Owner']);

        Setting::factory()->create([
            'user_id' => $this->tenant,
            'restaurant_name' => 'Secret Name',
        ]);

        Filament::auth()->login($otherUser);
        Filament::setTenant($otherUser);

        Livewire::test(RestaurantSettings::class)
            ->fillForm(['restaurant_name' => 'Hijacked'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('settings', [
            'user_id' => $this->tenant->id,
            'restaurant_name' => 'Secret Name',
        ]);
    }

    public function test_replacing_the_logo_deletes_the_previous_file(): void
    {
        Storage::disk('public')->put('logos/old-logo.png', 'old');

        $setting = Setting::factory()->create([
            'user_id' => $this->tenant,
            'logo' => 'logos/old-logo.png',
        ]);

        Livewire::test(RestaurantSettings::class)
            ->fillForm([
                'logo' => [UploadedFile::fake()->image('new-logo.png')],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $previous = $setting->getRawOriginal('logo');

        $this->assertNotSame('logos/old-logo.png', $setting->refresh()->logo);
        Storage::disk('public')->assertMissing('logos/old-logo.png');
        $this->assertNotSame($previous, $setting->logo);
    }

    public function test_replacing_the_background_image_deletes_the_previous_file(): void
    {
        Storage::disk('public')->put('backgrounds/old-bg.jpg', 'old');

        $setting = Setting::factory()->create([
            'user_id' => $this->tenant,
            'bg_image' => 'backgrounds/old-bg.jpg',
        ]);

        Livewire::test(RestaurantSettings::class)
            ->fillForm([
                'bg_image' => [UploadedFile::fake()->image('new-bg.jpg')],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertMissing('backgrounds/old-bg.jpg');
        $this->assertStringStartsWith('backgrounds/', $setting->refresh()->bg_image);
    }

    public function test_clearing_the_logo_deletes_the_previous_file(): void
    {
        Storage::disk('public')->put('logos/old-logo.png', 'old');

        Setting::factory()->create([
            'user_id' => $this->tenant,
            'logo' => 'logos/old-logo.png',
        ]);

        Livewire::test(RestaurantSettings::class)
            ->fillForm(['logo' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertMissing('logos/old-logo.png');
        $this->assertDatabaseHas('settings', [
            'user_id' => $this->tenant->id,
            'logo' => null,
        ]);
    }

    public function test_an_untouched_file_is_never_deleted(): void
    {
        Storage::disk('public')->put('logos/keep-logo.png', 'keep');
        Storage::disk('public')->put('backgrounds/keep-bg.jpg', 'keep');

        Setting::factory()->create([
            'user_id' => $this->tenant,
            'logo' => 'logos/keep-logo.png',
            'bg_image' => 'backgrounds/keep-bg.jpg',
        ]);

        Livewire::test(RestaurantSettings::class)
            ->fillForm(['restaurant_name' => 'Renamed Only'])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertExists('logos/keep-logo.png');
        Storage::disk('public')->assertExists('backgrounds/keep-bg.jpg');
    }

    public function test_saving_without_changes_keeps_the_existing_files(): void
    {
        Storage::disk('public')->put('logos/keep-logo.png', 'keep');

        Setting::factory()->create([
            'user_id' => $this->tenant,
            'logo' => 'logos/keep-logo.png',
        ]);

        Livewire::test(RestaurantSettings::class)
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertExists('logos/keep-logo.png');
    }

    public function test_deleting_a_setting_removes_its_files(): void
    {
        Storage::disk('public')->put('logos/gone-logo.png', 'gone');
        Storage::disk('public')->put('backgrounds/gone-bg.jpg', 'gone');

        $setting = Setting::factory()->create([
            'user_id' => $this->tenant,
            'logo' => 'logos/gone-logo.png',
            'bg_image' => 'backgrounds/gone-bg.jpg',
        ]);

        $setting->delete();

        Storage::disk('public')->assertMissing('logos/gone-logo.png');
        Storage::disk('public')->assertMissing('backgrounds/gone-bg.jpg');
    }
}
