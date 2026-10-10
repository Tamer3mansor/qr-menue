<?php

namespace Tests\Feature;

use App\Filament\Pages\RestaurantSettings;
use App\Filament\Pages\WebsiteSettings;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WebsiteSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create(['name' => 'مطعم ليالي الشرق']);

        $this->onAdminPanelAs($this->tenant);
    }

    public function test_the_page_renders_with_every_setting(): void
    {
        Livewire::test(WebsiteSettings::class)
            ->assertOk()
            ->assertSchemaComponentExists('hero_title')
            ->assertSchemaComponentExists('hero_subtitle')
            ->assertSchemaComponentExists('hero_image')
            ->assertSchemaComponentExists('show_offers_ticker')
            ->assertSchemaComponentExists('branches')
            ->assertSchemaComponentExists('social_links.facebook')
            ->assertSchemaComponentExists('social_links.instagram')
            ->assertSchemaComponentExists('social_links.tiktok')
            ->assertSchemaComponentExists('seo_title')
            ->assertSchemaComponentExists('seo_description')
            ->assertSchemaComponentExists('seo_keywords')
            ->assertSchemaComponentExists('primary_color')
            ->assertSchemaComponentExists('secondary_color')
            ->assertSchemaComponentExists('primary_font');
    }

    public function test_the_page_creates_a_settings_record_when_none_exists(): void
    {
        $this->assertDatabaseCount('settings', 0);

        Livewire::test(WebsiteSettings::class)->assertOk();

        $setting = Setting::query()->firstOrFail();

        $this->assertTrue($setting->user->is($this->tenant));
        $this->assertSame('مطعم ليالي الشرق', $setting->restaurant_name);
        $this->assertTrue($setting->show_offers_ticker);
        $this->assertSame('Cairo', $setting->primary_font);
    }

    public function test_the_hero_copy_is_saved(): void
    {
        Livewire::test(WebsiteSettings::class)
            ->fillForm([
                'hero_title' => 'أهلاً بيك',
                'hero_subtitle' => 'أشهى الأكل البيتي',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = $this->tenant->refresh()->settings;

        $this->assertSame('أهلاً بيك', $setting->hero_title);
        $this->assertSame('أشهى الأكل البيتي', $setting->hero_subtitle);
    }

    public function test_the_offers_ticker_label_is_in_arabic_and_can_be_switched_off(): void
    {
        Livewire::test(WebsiteSettings::class)
            ->assertSchemaComponentExists('show_offers_ticker', checkComponentUsing: fn ($component): bool => $component->getLabel() === 'عرض شريط العروض المتحرك');

        Livewire::test(WebsiteSettings::class)
            ->fillForm(['show_offers_ticker' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($this->tenant->refresh()->settings->show_offers_ticker);
    }

    public function test_branches_are_saved_as_a_list(): void
    {
        Livewire::test(WebsiteSettings::class)
            ->fillForm([
                'branches' => [
                    ['branch_name' => 'فرع المعادي', 'address' => 'شارع 9، المعادي', 'phone' => '01000000001'],
                    ['branch_name' => 'فرع مدينة نصر', 'address' => 'شارع عباس العقاد', 'phone' => null],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $branches = $this->tenant->refresh()->settings->branches;

        $this->assertCount(2, $branches);
        $this->assertSame('فرع المعادي', $branches[0]['branch_name']);
        $this->assertSame('شارع 9، المعادي', $branches[0]['address']);
        $this->assertSame('01000000001', $branches[0]['phone']);
        $this->assertNull($branches[1]['phone']);
    }

    public function test_a_branch_requires_a_name_and_an_address(): void
    {
        Livewire::test(WebsiteSettings::class)
            ->fillForm([
                'branches' => [
                    ['branch_name' => null, 'address' => null, 'phone' => '01000000001'],
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['branches.0.branch_name', 'branches.0.address']);
    }

    public function test_social_links_are_saved_as_an_array(): void
    {
        Livewire::test(WebsiteSettings::class)
            ->fillForm([
                'social_links' => [
                    'facebook' => 'https://facebook.com/layali',
                    'instagram' => 'https://instagram.com/layali',
                    'tiktok' => 'https://tiktok.com/@layali',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $links = $this->tenant->refresh()->settings->social_links;

        $this->assertSame('https://facebook.com/layali', $links['facebook']);
        $this->assertSame('https://instagram.com/layali', $links['instagram']);
        $this->assertSame('https://tiktok.com/@layali', $links['tiktok']);
        // WhatsApp orders go through the phone number instead.
        $this->assertArrayNotHasKey('whatsapp', $links);
    }

    public function test_an_invalid_social_url_is_rejected(): void
    {
        Livewire::test(WebsiteSettings::class)
            ->fillForm(['social_links' => ['facebook' => 'not a url']])
            ->call('save')
            ->assertHasFormErrors(['social_links.facebook']);
    }

    public function test_the_seo_hints_are_shown(): void
    {
        Livewire::test(WebsiteSettings::class)
            ->assertSchemaComponentExists('seo_title', checkComponentUsing: fn ($component): bool => $component->getHint() === 'عنوان الصفحة في جوجل')
            ->assertSchemaComponentExists('seo_description', checkComponentUsing: fn ($component): bool => $component->getHint() === 'الوصف الظاهر في نتائج البحث')
            ->assertSchemaComponentExists('seo_keywords', checkComponentUsing: fn ($component): bool => $component->getHint() === 'كلمات مفتاحية مفصولة بفواصل');
    }

    public function test_the_seo_limits_are_enforced(): void
    {
        Livewire::test(WebsiteSettings::class)
            ->fillForm([
                'seo_title' => str_repeat('a', WebsiteSettings::SEO_TITLE_LIMIT + 1),
                'seo_description' => str_repeat('b', WebsiteSettings::SEO_DESCRIPTION_LIMIT + 1),
            ])
            ->call('save')
            ->assertHasFormErrors(['seo_title', 'seo_description']);
    }

    public function test_the_seo_fields_are_saved(): void
    {
        Livewire::test(WebsiteSettings::class)
            ->fillForm([
                'seo_title' => 'مطعم ليالي الشرق | منيو رقمي',
                'seo_description' => 'اطلب أشهى الأكل البيتي',
                'seo_keywords' => 'مطعم, منيو, ليالي الشرق',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = $this->tenant->refresh()->settings;

        $this->assertSame('مطعم ليالي الشرق | منيو رقمي', $setting->seo_title);
        $this->assertSame('اطلب أشهى الأكل البيتي', $setting->seo_description);
        $this->assertSame('مطعم, منيو, ليالي الشرق', $setting->seo_keywords);
    }

    public function test_every_font_option_can_be_chosen(): void
    {
        foreach (WebsiteSettings::FONTS as $font) {
            Livewire::test(WebsiteSettings::class)
                ->fillForm(['primary_font' => $font])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame($font, $this->tenant->refresh()->settings->primary_font);
        }
    }

    public function test_the_colors_are_shared_with_restaurant_settings(): void
    {
        Livewire::test(WebsiteSettings::class)
            ->fillForm([
                'primary_color' => '#112233',
                'secondary_color' => '#445566',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // The same columns RestaurantSettings writes to.
        Livewire::test(RestaurantSettings::class)
            ->assertFormSet([
                'primary_color' => '#112233',
                'secondary_color' => '#445566',
            ]);
    }

    public function test_it_is_a_page_of_its_own(): void
    {
        $this->assertNotSame(
            RestaurantSettings::getNavigationLabel(),
            WebsiteSettings::getNavigationLabel(),
        );

        $this->assertGreaterThan(
            RestaurantSettings::getNavigationSort(),
            WebsiteSettings::getNavigationSort(),
        );
    }

    public function test_it_is_listed_in_the_sidebar(): void
    {
        $this->assertSame('إعدادات الموقع', WebsiteSettings::getNavigationLabel());

        $this->assertNotNull(WebsiteSettings::getNavigationIcon());

        $this->assertContains(
            WebsiteSettings::class,
            array_values(Filament::getPanel('admin')->getPages()),
        );

        $this->actingAs($this->tenant)
            ->get("/admin/{$this->tenant->getKey()}")
            ->assertSuccessful()
            ->assertSee(WebsiteSettings::getNavigationLabel())
            ->assertSee(WebsiteSettings::getUrl(tenant: $this->tenant), escape: false);
    }

    /**
     * A page with a form but no view of its own renders an empty body, so the
     * fields have to be asserted through a real request rather than the schema.
     */
    public function test_the_page_renders_its_form_in_a_real_request(): void
    {
        $response = $this->actingAs($this->tenant)->get(
            WebsiteSettings::getUrl(tenant: $this->tenant)
        );

        $response->assertSuccessful();
        $response->assertSee('عنوان الواجهة');
        $response->assertSee('عنوان السيو');
        $response->assertSee('<form wire:submit="save"', escape: false);
    }

    public function test_a_customer_cannot_edit_another_tenants_website_settings(): void
    {
        $other = User::factory()->create();

        $this->onAdminPanelAs($other);

        Livewire::test(WebsiteSettings::class)
            ->fillForm(['hero_title' => 'مطعم آخر'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($this->tenant->refresh()->settings?->hero_title);
        $this->assertSame('مطعم آخر', $other->refresh()->settings->hero_title);
    }

    /**
     * Puts Filament in the tenant context the page requires.
     */
    private function onAdminPanelAs(User $user): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::auth()->login($user);
        Filament::setTenant($user);

        Filament::bootCurrentPanel();
    }
}
