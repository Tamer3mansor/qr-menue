<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SettingWorkingHoursTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_is_open_inside_the_opening_hours(): void
    {
        $this->freezeAt('2026-10-19 10:00:00');

        $setting = $this->setting([
            'monday' => ['open' => '09:00', 'close' => '23:00', 'closed' => false],
        ]);

        $this->assertTrue($setting->isOpenNow());
    }

    public function test_it_is_closed_outside_the_opening_hours(): void
    {
        $this->freezeAt('2026-10-19 08:30:00');

        $setting = $this->setting([
            'monday' => ['open' => '09:00', 'close' => '23:00', 'closed' => false],
        ]);

        $this->assertFalse($setting->isOpenNow());

        $this->freezeAt('2026-10-19 23:30:00');

        $this->assertFalse($setting->isOpenNow());
    }

    public function test_it_is_closed_on_a_closed_day(): void
    {
        $this->freezeAt('2026-10-19 12:00:00');

        $setting = $this->setting([
            'monday' => ['open' => '09:00', 'close' => '23:00', 'closed' => true],
        ]);

        $this->assertFalse($setting->isOpenNow());
    }

    public function test_the_default_schedule_is_used_when_none_was_saved(): void
    {
        $this->freezeAt('2026-10-19 12:00:00');

        $setting = $this->setting(null);

        $this->assertTrue($setting->isOpenNow());
        $this->assertSame('09:00', $setting->todayWorkingHours()['open']);
        $this->assertSame('23:00', $setting->todayWorkingHours()['close']);
    }

    public function test_the_default_schedule_closes_on_friday(): void
    {
        $this->freezeAt('2026-10-23 12:00:00');

        $setting = $this->setting([]);

        $this->assertFalse($setting->isOpenNow());
    }

    public function test_working_hours_are_judged_in_the_business_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 08:30:00', 'UTC'));

        $setting = $this->setting([
            'monday' => ['open' => '09:00', 'close' => '23:00', 'closed' => false],
        ]);

        // 08:30 UTC is 11:30 in Cairo, so the restaurant is open.
        $this->assertTrue($setting->isOpenNow());
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function overnightTimes(): array
    {
        return [
            'after opening' => ['2026-10-19 23:00:00', true],
            'after midnight' => ['2026-10-20 01:00:00', true],
            'after closing' => ['2026-10-20 03:00:00', false],
        ];
    }

    #[DataProvider('overnightTimes')]
    public function test_a_shift_that_crosses_midnight_is_handled(string $now, bool $expected): void
    {
        $this->freezeAt($now);

        $setting = $this->setting([
            'monday' => ['open' => '22:00', 'close' => '02:00', 'closed' => false],
        ]);

        $this->assertSame($expected, $setting->isOpenNow());
    }

    public function test_the_default_schedule_marks_friday_as_closed(): void
    {
        $setting = $this->setting();

        $this->assertTrue($setting->workingHours()['friday']['closed']);
        $this->assertFalse($setting->workingHours()['monday']['closed']);
    }

    public function test_payment_methods_are_merged_with_the_defaults(): void
    {
        $setting = $this->setting();

        $methods = $setting->paymentMethods();

        $this->assertCount(5, $methods);
        $this->assertTrue($methods[0]['enabled']);
        $this->assertSame('cash', $methods[0]['type']);
    }

    /**
     * @param  array<string, mixed> | null  $workingHours
     */
    private function setting(?array $workingHours = null): Setting
    {
        $tenant = User::factory()->create();

        return Setting::factory()->create([
            'user_id' => $tenant->getKey(),
            'working_hours' => $workingHours,
        ]);
    }

    private function freezeAt(string $time): void
    {
        Carbon::setTestNow(Carbon::parse($time, config('app.business_timezone')));
    }
}
