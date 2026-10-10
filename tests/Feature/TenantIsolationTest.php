<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\QrCode;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_is_their_own_tenant(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->canAccessTenant($user));
        $this->assertTrue($user->getTenants(Filament::getPanel('admin'))->contains($user));
        $this->assertTrue($user->getDefaultTenant(Filament::getPanel('admin'))->is($user));
    }

    public function test_a_user_cannot_access_another_users_tenant(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($user->canAccessTenant($otherUser));
        $this->assertFalse($user->getTenants(Filament::getPanel('admin'))->contains($otherUser));
    }

    public function test_the_panel_redirects_to_the_users_own_tenant(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertRedirect('/admin/'.$user->id);
    }

    public function test_a_user_is_redirected_to_their_tenant_using_the_id_slug(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('filament.admin.pages.dashboard', ['tenant' => $user->id]));

        $response->assertSuccessful();
    }

    public function test_a_user_cannot_open_another_users_tenant_panel(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard', ['tenant' => $otherUser->id]));

        $response->assertNotFound();
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('filament.admin.pages.dashboard', ['tenant' => $user->id]));

        $response->assertRedirect('/admin/login');
    }

    public function test_each_model_resolves_its_owner(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create(['user_id' => $user]);
        $item = Item::factory()->hasAttached($category)->create(['user_id' => $user]);
        $offer = Offer::factory()->hasAttached($item)->create(['user_id' => $user]);
        $setting = Setting::factory()->create(['user_id' => $user]);
        $qrCode = $user->qrCode()->firstOrFail();

        $this->assertTrue($category->user->is($user));
        $this->assertTrue($item->user->is($user));
        $this->assertTrue($offer->user->is($user));
        $this->assertTrue($setting->user->is($user));
        $this->assertTrue($qrCode->user->is($user));

        $this->assertEqualsCanonicalizing([$category->getKey()], $item->categories->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$item->getKey()], $offer->items->pluck('id')->all());
    }

    public function test_the_user_exposes_all_of_their_tenant_records(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $category = Category::factory()->create(['user_id' => $user]);
        $item = Item::factory()->hasAttached($category)->create(['user_id' => $user]);
        $offer = Offer::factory()->hasAttached($item)->create(['user_id' => $user]);
        Setting::factory()->create(['user_id' => $user]);

        $this->assertCount(1, $user->categories);
        $this->assertCount(1, $user->items);
        $this->assertCount(1, $user->offers);
        $this->assertCount(1, $user->qrCode()->get());
        $this->assertCount(1, $user->settings()->get());

        $this->assertCount(0, $otherUser->categories);
        $this->assertCount(0, $otherUser->items);
        $this->assertCount(0, $otherUser->offers);
        $this->assertCount(0, $otherUser->settings()->get());
    }

    public function test_an_inactive_user_cannot_access_the_panel(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard', ['tenant' => $user->id]));

        $response->assertForbidden();
    }

    public function test_deleting_a_user_cascades_to_their_tenant_records(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create(['user_id' => $user]);
        $item = Item::factory()->hasAttached($category)->create(['user_id' => $user]);
        Offer::factory()->hasAttached($item)->create(['user_id' => $user]);
        Setting::factory()->create(['user_id' => $user]);

        $user->delete();

        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('items', 0);
        $this->assertDatabaseCount('offers', 0);
        $this->assertDatabaseCount('settings', 0);
        $this->assertDatabaseCount('qr_codes', 0);
        $this->assertDatabaseCount('category_item', 0);
        $this->assertDatabaseCount('offer_item', 0);
    }

    public function test_a_user_may_only_have_one_qr_code(): void
    {
        $user = User::factory()->create();

        // The observer already issued one, so this second insert must collide.
        $this->assertCount(1, $user->qrCode()->get());

        $this->expectException(QueryException::class);

        QrCode::factory()->create(['user_id' => $user]);
    }
}
