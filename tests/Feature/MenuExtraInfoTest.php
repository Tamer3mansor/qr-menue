<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MenuExtraInfoTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Setting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->settings = Setting::factory()->create([
            'user_id' => $this->user,
            'restaurant_name' => 'Kebab Palace',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_payment_method_with_a_value_renders_a_copy_button(): void
    {
        $this->settings->update(['payment_methods' => [
            ['type' => 'vodafone_cash', 'label' => 'فودافون كاش', 'value' => '01012345678', 'enabled' => true],
        ]]);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('فودافون كاش')
            ->assertSee('data-copy="01012345678"', false)
            ->assertSee('onclick="copyPayment(this)"', false);
    }

    public function test_a_payment_method_without_a_value_is_only_displayed(): void
    {
        $this->settings->update(['payment_methods' => [
            ['type' => 'cash', 'label' => 'كاش', 'value' => null, 'enabled' => true],
        ]]);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('كاش')
            ->assertDontSee('data-copy="', false)
            ->assertDontSee('onclick="copyPayment(this)"', false);
    }

    public function test_the_google_rating_is_only_shown_when_it_exists(): void
    {
        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertDontSee('تقييم');

        $this->settings->update([
            'google_rating' => 4.8,
            'google_review_count' => 230,
            'google_maps_url' => 'https://maps.google.com/?cid=123',
        ]);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('4.8')
            ->assertSee('230 تقييم')
            ->assertSee('https://maps.google.com/?cid=123', false);
    }

    public function test_the_open_badge_is_shown_during_working_hours(): void
    {
        $this->freezeAt('2026-10-19 12:00:00');

        $this->settings->update(['working_hours' => [
            'monday' => ['open' => '09:00', 'close' => '23:00', 'closed' => false],
        ]]);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('مفتوح الآن')
            ->assertDontSee('مغلق حالياً');
    }

    public function test_the_open_badge_uses_the_dashboard_default_schedule(): void
    {
        $this->freezeAt('2026-10-19 12:00:00');

        $this->settings->update(['working_hours' => null]);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('مفتوح الآن')
            ->assertDontSee('مغلق حالياً')
            ->assertSee('9 صباحاً - 11 مساءً');
    }

    public function test_the_closed_badge_is_shown_outside_working_hours(): void
    {
        $this->freezeAt('2026-10-19 02:00:00');

        $this->settings->update(['working_hours' => [
            'monday' => ['open' => '09:00', 'close' => '23:00', 'closed' => false],
        ]]);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('مغلق حالياً')
            ->assertDontSee('مفتوح الآن');
    }

    public function test_the_current_day_hours_are_shown(): void
    {
        $this->freezeAt('2026-10-19 12:00:00');

        $this->settings->update(['working_hours' => [
            'monday' => ['open' => '09:00', 'close' => '23:00', 'closed' => false],
        ]]);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('الاثنين')
            ->assertSee('9 صباحاً - 11 مساءً');
    }

    private function freezeAt(string $time): void
    {
        Carbon::setTestNow(Carbon::parse($time, config('app.business_timezone')));
    }
}
