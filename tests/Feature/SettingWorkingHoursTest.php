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
        Carbon::setTestNow('2026-10-19 10:00:00');

        $setting = $this->setting([
            'monday' => ['open' => '09:00', 'close' => '23:00', 'closed' => false],
        ]);

        $this->assertTrue($setting->isOpenNow());
    }

    public function test_it_is_closed_outside_the_opening_hours(): void
    {
        Carbon::setTestNow('2026-10-19 08:30:00');

        $setting = $this->setting([
            'monday' => ['open' => '09:00', 'close' => '23:00', 'closed' => false],
        ]);

        $this->assertFalse($setting->isOpenNow());

        Carbon::setTestNow('2026-10-19 23:30:00');

        $this->assertFalse($setting->isOpenNow());
    }

    public function test_it_is_closed_on_a_closed_day(): void
    {
        Carbon::setTestNow('2026-10-19 12:00:00');

        $setting = $this->setting([
            'monday' => ['open' => '09:00', 'close' => '23:00', 'closed' => true],
        ]);

        $this->assertFalse($setting->isOpenNow());
    }

    public function test_it_returns_null_when_there_is_no_schedule(): void
    {
        $this->assertNull($this->setting(null)->isOpenNow());
        $this->assertNull($this->setting([])->isOpenNow());
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
        Carbon::setTestNow($now);

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
}
