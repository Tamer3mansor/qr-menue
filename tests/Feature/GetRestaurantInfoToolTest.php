<?php

namespace Tests\Feature;

use App\Ai\Tools\GetRestaurantInfoTool;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class GetRestaurantInfoToolTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected Setting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create();

        $this->settings = Setting::factory()->create([
            'user_id' => $this->tenant->getKey(),
            'restaurant_name' => 'مطعم الاختبار',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_the_restaurant_information(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 12:00:00', config('app.business_timezone')));

        $this->settings->update([
            'phone' => '01000000000',
            'working_hours' => [
                'monday' => ['open' => '09:00', 'close' => '23:00', 'closed' => false],
            ],
            'payment_methods' => [
                ['type' => 'cash', 'label' => 'كاش', 'value' => null, 'enabled' => true],
                ['type' => 'instapay', 'label' => 'انستاباي', 'value' => 'restaurant', 'enabled' => true],
                ['type' => 'visa', 'label' => 'فيزا/ماستركارد', 'value' => null, 'enabled' => false],
            ],
            'social_links' => [
                'facebook' => 'https://facebook.com/restaurant',
                'instagram' => null,
            ],
        ]);

        $data = json_decode(
            (string) (new GetRestaurantInfoTool($this->tenant->getKey()))->handle(new Request),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('01000000000', $data['phone']);
        $this->assertTrue($data['is_open_now']);
        $this->assertSame('09:00', $data['working_hours']['monday']['open']);
        $this->assertSame('23:00', $data['working_hours']['monday']['close']);
        $this->assertSame(['كاش', 'انستاباي'], array_column($data['payment_methods'], 'label'));
        $this->assertSame(
            ['facebook' => 'https://facebook.com/restaurant'],
            $data['social_links'],
        );
    }

    public function test_it_reports_a_closed_restaurant_outside_the_working_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 02:00:00', config('app.business_timezone')));

        $this->settings->update([
            'working_hours' => [
                'monday' => ['open' => '09:00', 'close' => '23:00', 'closed' => false],
            ],
        ]);

        $data = json_decode(
            (string) (new GetRestaurantInfoTool($this->tenant->getKey()))->handle(new Request),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertFalse($data['is_open_now']);
    }

    public function test_it_reports_when_the_restaurant_has_no_settings(): void
    {
        $tenant = User::factory()->create();

        $tool = new GetRestaurantInfoTool($tenant->getKey());

        $this->assertSame('لا توجد بيانات متاحة', (string) $tool->handle(new Request));
    }

    public function test_it_exposes_the_tool_name_the_model_must_call(): void
    {
        $this->assertSame('get_restaurant_info', (new GetRestaurantInfoTool(1))->name());
    }
}
