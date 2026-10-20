<?php

namespace Tests\Feature;

use App\Ai\Tools\GetMenuDataTool;
use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class GetMenuDataToolTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create();

        Setting::factory()->create([
            'user_id' => $this->tenant->getKey(),
            'restaurant_name' => 'مطعم الاختبار',
        ]);
    }

    public function test_it_returns_the_restaurant_menu_as_json(): void
    {
        $category = Category::factory()->create([
            'user_id' => $this->tenant->getKey(),
            'name' => 'الفطار',
        ]);

        $item = Item::factory()->featured()->create([
            'user_id' => $this->tenant->getKey(),
            'title' => 'فول مدمس',
            'description' => 'طبق شعبي',
            'price' => 35,
        ]);

        $category->items()->attach($item);

        $data = $this->menuData();

        $this->assertSame('مطعم الاختبار', $data['business']);
        $this->assertCount(1, $data['categories']);
        $this->assertSame('الفطار', $data['categories'][0]['name']);
        $this->assertSame([
            [
                'name' => 'فول مدمس',
                'description' => 'طبق شعبي',
                'price' => 35,
                'is_featured' => true,
            ],
        ], $data['categories'][0]['items']);
        $this->assertSame([], $data['offers']);
    }

    public function test_it_returns_only_available_items(): void
    {
        $category = Category::factory()->create(['user_id' => $this->tenant->getKey()]);

        $available = Item::factory()->create([
            'user_id' => $this->tenant->getKey(),
            'title' => 'متاح',
        ]);

        $soldOut = Item::factory()->unavailable()->create([
            'user_id' => $this->tenant->getKey(),
            'title' => 'خلص',
        ]);

        $category->items()->attach([$available->getKey(), $soldOut->getKey()]);

        $items = $this->menuData()['categories'][0]['items'];

        $this->assertSame(['متاح'], array_column($items, 'name'));
    }

    public function test_a_category_with_no_available_items_is_not_reported(): void
    {
        $category = Category::factory()->create(['user_id' => $this->tenant->getKey()]);

        $category->items()->attach(
            Item::factory()->unavailable()->create(['user_id' => $this->tenant->getKey()]),
        );

        $tool = new GetMenuDataTool($this->tenant->getKey());

        $this->assertSame('لا توجد بيانات متاحة', (string) $tool->handle(new Request));
    }

    public function test_it_returns_only_active_offers_with_their_prices(): void
    {
        $first = Item::factory()->create(['user_id' => $this->tenant->getKey(), 'title' => 'كشري وسط', 'price' => 30, 'sort_order' => 1]);
        $second = Item::factory()->create(['user_id' => $this->tenant->getKey(), 'title' => 'سلطة', 'price' => 40, 'sort_order' => 2]);

        $offer = Offer::factory()->create([
            'user_id' => $this->tenant->getKey(),
            'title' => 'عرض الكشري',
            'offer_price' => 45,
        ]);

        $offer->items()->attach([$first->getKey(), $second->getKey()]);

        Offer::factory()->inactive()->create(['user_id' => $this->tenant->getKey(), 'title' => 'عرض متوقف']);
        Offer::factory()->expired()->create(['user_id' => $this->tenant->getKey(), 'title' => 'عرض منتهي']);

        $offers = $this->menuData()['offers'];

        $this->assertCount(1, $offers);
        $this->assertSame('عرض الكشري', $offers[0]['title']);
        $this->assertSame(45, $offers[0]['offer_price']);
        $this->assertSame(70, $offers[0]['original_price']);
        $this->assertSame(['كشري وسط', 'سلطة'], $offers[0]['items']);
    }

    public function test_it_reports_when_the_restaurant_has_no_data(): void
    {
        $tool = new GetMenuDataTool($this->tenant->getKey());

        $this->assertSame('لا توجد بيانات متاحة', (string) $tool->handle(new Request));
    }

    public function test_it_reports_an_unknown_restaurant_as_empty(): void
    {
        $tool = new GetMenuDataTool(999999);

        $this->assertSame('لا توجد بيانات متاحة', (string) $tool->handle(new Request));
    }

    /**
     * @return array<string, mixed>
     */
    private function menuData(): array
    {
        $tool = new GetMenuDataTool($this->tenant->getKey());

        return json_decode((string) $tool->handle(new Request), true, flags: JSON_THROW_ON_ERROR);
    }
}
