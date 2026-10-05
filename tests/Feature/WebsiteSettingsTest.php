<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the website settings columns added to the settings table, and the
 * featured flag on items.
 */
class WebsiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_tenant_gets_working_defaults(): void
    {
        $tenant = User::factory()->create();
        $setting = Setting::factory()->create(['user_id' => $tenant->getKey()]);

        $setting->refresh();

        $this->assertTrue($setting->show_offers_ticker);
        $this->assertSame('Cairo', $setting->primary_font);
        $this->assertNull($setting->hero_title);
        $this->assertNull($setting->hero_subtitle);
        $this->assertNull($setting->branches);
        $this->assertNull($setting->social_links);
        $this->assertNull($setting->seo_title);
        $this->assertNull($setting->seo_description);
        $this->assertNull($setting->seo_keywords);
    }

    public function test_the_hero_copy_is_stored(): void
    {
        $setting = $this->setting();

        $setting->update([
            'hero_title' => 'أهلاً بيك',
            'hero_subtitle' => 'أشهى الأكل البيتي',
        ]);

        $setting->refresh();

        $this->assertSame('أهلاً بيك', $setting->hero_title);
        $this->assertSame('أشهى الأكل البيتي', $setting->hero_subtitle);
    }

    public function test_the_offers_ticker_can_be_turned_off(): void
    {
        $setting = $this->setting();

        $this->assertTrue($setting->show_offers_ticker);

        $setting->update(['show_offers_ticker' => false]);

        $this->assertFalse($setting->refresh()->show_offers_ticker);
    }

    public function test_branches_are_stored_as_an_array(): void
    {
        $setting = $this->setting();

        $branches = [
            ['name' => 'فرع المعادي', 'address' => 'شارع 9، المعادي', 'phone' => '01000000001'],
            ['name' => 'فرع مدينة نصر', 'address' => 'شارع Abbas العقاد', 'phone' => '01000000002'],
        ];

        $setting->update(['branches' => $branches]);

        $branches = $setting->refresh()->branches;

        $this->assertIsArray($branches);
        $this->assertCount(2, $branches);
        $this->assertSame('فرع المعادي', $branches[0]['name']);
        $this->assertSame('01000000002', $branches[1]['phone']);
        $this->assertSame($branches, $setting->branchesList());
    }

    public function test_social_links_are_stored_as_an_array(): void
    {
        $setting = $this->setting();

        $setting->update([
            'social_links' => [
                'facebook' => 'https://facebook.com/layali',
                'instagram' => 'https://instagram.com/layali',
                'whatsapp' => 'https://wa.me/201001234567',
            ],
        ]);

        $links = $setting->refresh()->social_links;

        $this->assertIsArray($links);
        $this->assertSame('https://facebook.com/layali', $links['facebook']);
        $this->assertSame('https://wa.me/201001234567', $links['whatsapp']);
        $this->assertCount(3, $setting->filledSocialLinks());
    }

    public function test_blank_social_links_are_left_out_of_the_filled_list(): void
    {
        $setting = $this->setting();

        $setting->update([
            'social_links' => [
                'facebook' => 'https://facebook.com/layali',
                'instagram' => '',
                'whatsapp' => null,
            ],
        ]);

        $filled = $setting->refresh()->filledSocialLinks();

        $this->assertSame(['facebook' => 'https://facebook.com/layali'], $filled);
    }

    public function test_the_seo_fields_are_stored(): void
    {
        $setting = $this->setting();

        $setting->update([
            'seo_title' => 'مطعم ليالي الشرق | منيو رقمي',
            'seo_description' => 'اطلب أشهى الأكل البيتي من مطعم ليالي الشرق',
            'seo_keywords' => 'مطعم, منيو, ليالي الشرق',
        ]);

        $setting->refresh();

        $this->assertSame('مطعم ليالي الشرق | منيو رقمي', $setting->seo_title);
        $this->assertSame('اطلب أشهى الأكل البيتي من مطعم ليالي الشرق', $setting->seo_description);
        $this->assertSame('مطعم, منيو, ليالي الشرق', $setting->seo_keywords);
    }

    public function test_the_primary_font_can_be_changed(): void
    {
        $setting = $this->setting();

        $setting->update(['primary_font' => 'Tajawal']);

        $this->assertSame('Tajawal', $setting->refresh()->primary_font);
    }

    public function test_replacing_the_hero_image_deletes_the_previous_file(): void
    {
        Storage::fake(Setting::DISK);

        $setting = $this->setting();

        $setting->update(['hero_image' => 'heroes/old.png']);
        Storage::disk(Setting::DISK)->put('heroes/old.png', 'old');

        $this->assertTrue(Storage::disk(Setting::DISK)->exists('heroes/old.png'));

        $setting->update(['hero_image' => 'heroes/new.png']);

        $this->assertFalse(Storage::disk(Setting::DISK)->exists('heroes/old.png'));
        $this->assertSame('heroes/new.png', $setting->refresh()->hero_image);
    }

    public function test_a_blank_hero_image_exposes_no_url(): void
    {
        $setting = $this->setting();

        $this->assertNull($setting->hero_image_url);

        $setting->update(['hero_image' => 'heroes/new.png']);

        $this->assertNotNull($setting->refresh()->hero_image_url);
    }

    public function test_items_default_to_not_featured(): void
    {
        $item = $this->item();

        $this->assertFalse($item->is_featured);
    }

    public function test_an_item_can_be_marked_as_featured(): void
    {
        $item = $this->item();

        $item->update(['is_featured' => true]);

        $this->assertTrue($item->refresh()->is_featured);
    }

    private function setting(): Setting
    {
        $tenant = User::factory()->create();

        return Setting::factory()->create([
            'user_id' => $tenant->getKey(),
            'restaurant_name' => 'مطعم ليالي الشرق',
        ]);
    }

    private function item(): Item
    {
        $tenant = User::factory()->create();

        return Item::factory()->create(['user_id' => $tenant->getKey()]);
    }
}
