<?php

namespace Tests\Feature;

use App\Filament\Widgets\QuickActions;
use App\Filament\Widgets\RestaurantStats;
use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create(['domain' => 'demo']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::auth()->login($this->tenant);
        Filament::setTenant($this->tenant);

        Filament::bootCurrentPanel();
    }

    public function test_the_stats_count_only_the_current_tenant_records(): void
    {
        $items = Item::factory()->count(3)->for($this->tenant)->create();

        Category::factory()->count(2)->for($this->tenant)->create();

        Offer::factory()->for($this->tenant)->hasAttached($items->first())->create(['is_active' => true]);
        Offer::factory()->for($this->tenant)->inactive()->create();

        $this->createRecordsForAnotherTenant();

        $stats = Livewire::test(RestaurantStats::class);

        $stats->assertSee('إجمالي المنتجات')
            ->assertSee('إجمالي التصنيفات')
            ->assertSee('عدد العروض النشطة')
            ->assertSee('حالة الاشتراك')
            ->assertSee('دائم ✓');

        $html = $stats->html();

        $this->assertStatValue($html, '3');
        $this->assertStatValue($html, '2');
        $this->assertStatValue($html, '1');
    }

    public function test_the_subscription_status_follows_the_expiry_date(): void
    {
        Livewire::test(RestaurantStats::class)
            ->assertSee('دائم ✓');

        $this->tenant->forceFill(['subscription_expires_at' => now()->addMonth()])->save();

        Livewire::test(RestaurantStats::class)
            ->assertSee('نشط - ينتهي '.$this->tenant->subscription_expires_at->format('Y-m-d'));

        $this->tenant->forceFill(['subscription_expires_at' => now()->subDay()])->save();

        Livewire::test(RestaurantStats::class)
            ->assertSee('منتهي ✗');
    }

    public function test_the_quick_actions_link_to_the_restaurant_resources(): void
    {
        Livewire::test(QuickActions::class)
            ->assertSee('إجراءات سريعة')
            ->assertSee('/admin/'.$this->tenant->getKey().'/items/create', false)
            ->assertSee('/admin/'.$this->tenant->getKey().'/offers/create', false)
            ->assertSee('/menu/demo', false)
            ->assertSee('/site/demo', false);
    }

    /**
     * The current tenant is forced onto every record created while it is set,
     * so another tenant's rows are built with the context cleared.
     */
    private function createRecordsForAnotherTenant(): void
    {
        $other = User::factory()->create();

        Filament::setTenant(null);

        try {
            Category::factory()->for($other)->create();
            Item::factory()->count(2)->for($other)->create();

            $item = Item::factory()->for($other)->create();
            Offer::factory()->for($other)->hasAttached($item)->create(['is_active' => true]);
        } finally {
            Filament::setTenant($this->tenant);
        }
    }

    /**
     * Each stat renders its value alone inside the value element, so matching
     * the whole element keeps "1" from matching "13".
     */
    private function assertStatValue(string $html, string $expected): void
    {
        $this->assertMatchesRegularExpression(
            '/fi-wi-stats-overview-stat-value[^>]*>\s*'.preg_quote($expected, '/').'\s*</',
            $html,
        );
    }
}
