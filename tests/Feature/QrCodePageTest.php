<?php

namespace Tests\Feature;

use App\Filament\Pages\QrCodePage;
use App\Models\QrCode;
use App\Models\User;
use App\Services\QrCodeService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class QrCodePageTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected QrCodeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://qr-menu.test']);

        $this->service = app(QrCodeService::class);

        $this->tenant = User::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::auth()->login($this->tenant);
        Filament::setTenant($this->tenant);

        Filament::bootCurrentPanel();
    }

    protected function login(User $user): void
    {
        Filament::auth()->login($user);
        Filament::setTenant($user);

        Filament::bootCurrentPanel();
    }

    public function test_a_new_user_receives_a_qr_code_automatically(): void
    {
        $this->assertCount(1, $this->tenant->qrCode()->get());

        $qrCode = $this->tenant->qrCode()->firstOrFail();

        $this->assertSame('https://qr-menu.test/menu/'.$this->tenant->id, $qrCode->url);
        $this->assertSame("qrcodes/{$this->tenant->id}.svg", $qrCode->image_path);

        Storage::disk(QrCode::DISK)->assertExists($qrCode->image_path);

        $this->assertStringContainsString('<svg', Storage::disk(QrCode::DISK)->get($qrCode->image_path));
    }

    public function test_the_url_uses_the_id_even_when_the_tenant_has_a_domain(): void
    {
        $user = User::factory()->create(['domain' => 'kebab-palace']);

        $this->assertSame(
            "https://qr-menu.test/menu/{$user->id}",
            $user->qrCode()->firstOrFail()->url
        );
    }

    public function test_the_url_is_unchanged_by_a_domain_change(): void
    {
        $user = User::factory()->create(['domain' => 'old-slug']);

        $before = $user->qrCode()->firstOrFail()->url;

        $user->forceFill(['domain' => 'new-slug'])->save();

        $this->assertSame($before, $user->refresh()->qrCode()->firstOrFail()->url);
    }

    public function test_the_url_falls_back_to_the_id_when_the_domain_is_empty(): void
    {
        $user = User::factory()->create(['domain' => '']);

        $this->assertSame(
            "https://qr-menu.test/menu/{$user->id}",
            $user->qrCode()->firstOrFail()->url
        );
    }

    public function test_the_url_is_built_from_the_app_url(): void
    {
        config(['app.url' => 'https://example.test/']);

        $qrCode = $this->service->generateForTenant($this->tenant);

        $this->assertSame("https://example.test/menu/{$this->tenant->id}", $qrCode->url);
    }

    public function test_the_generated_image_is_a_svg(): void
    {
        $qrCode = $this->service->generateForTenant($this->tenant);

        $svg = Storage::disk(QrCode::DISK)->get($qrCode->image_path);

        $this->assertStringStartsWith('<?xml', $svg);
        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_the_encoded_url_actually_changes_the_image(): void
    {
        $before = $this->service->generateForTenant($this->tenant)->image_path;
        $beforeSvg = Storage::disk(QrCode::DISK)->get($before);

        config(['app.url' => 'https://a-completely-different-host.test']);

        $after = $this->service->generateForTenant($this->tenant)->image_path;
        $afterSvg = Storage::disk(QrCode::DISK)->get($after);

        $this->assertNotSame($beforeSvg, $afterSvg);
    }

    public function test_generating_twice_updates_the_same_record_and_file(): void
    {
        $first = $this->service->generateForTenant($this->tenant);

        $second = $this->service->generateForTenant($this->tenant);

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('qr_codes', 1);
        $this->assertSame("qrcodes/{$this->tenant->id}.svg", $second->image_path);
    }

    public function test_regenerating_rewrites_the_image(): void
    {
        $qrCode = $this->tenant->qrCode()->firstOrFail();

        Storage::disk(QrCode::DISK)->put($qrCode->image_path, 'corrupted');

        $this->assertSame('corrupted', Storage::disk(QrCode::DISK)->get($qrCode->image_path));

        Livewire::test(QrCodePage::class)
            ->call('regenerate')
            ->assertHasNoActionErrors();

        $rewritten = Storage::disk(QrCode::DISK)->get($qrCode->image_path);

        $this->assertNotSame('corrupted', $rewritten);
        $this->assertStringContainsString('<svg', $rewritten);
        $this->assertDatabaseCount('qr_codes', 1);
    }

    public function test_regenerating_sends_a_success_notification(): void
    {
        Livewire::test(QrCodePage::class)
            ->callAction('regenerate')
            ->assertNotified(Notification::make()->title('تم إعادة توليد كود QR.')->success());
    }

    public function test_the_page_renders_the_image_and_a_clickable_link(): void
    {
        $qrCode = $this->tenant->qrCode()->firstOrFail();

        Livewire::test(QrCodePage::class)
            ->assertOk()
            ->assertSee($qrCode->url)
            ->assertSee($qrCode->image_url)
            ->assertSeeHtml('href="'.$qrCode->url.'"');
    }

    public function test_the_page_generates_a_code_for_a_tenant_that_has_none(): void
    {
        $user = User::factory()->create();

        $user->qrCode()->delete();

        $this->assertCount(0, $user->qrCode()->get());

        $this->login($user);

        Livewire::test(QrCodePage::class)->assertOk();

        $this->assertCount(1, $user->qrCode()->get());
    }

    public function test_downloading_returns_the_svg_file(): void
    {
        $qrCode = $this->tenant->qrCode()->firstOrFail();

        $response = Livewire::test(QrCodePage::class)->instance()->download();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(
            "qrcodes/{$this->tenant->id}.svg",
            $qrCode->image_path
        );
    }

    public function test_another_tenant_cannot_open_the_first_tenants_qr_code_page(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->get(
            QrCodePage::getUrl(tenant: $this->tenant)
        );

        $response->assertNotFound();
    }

    public function test_another_tenant_sees_their_own_qr_code(): void
    {
        $otherUser = User::factory()->create();

        $this->assertNotSame(
            $this->tenant->qrCode()->firstOrFail()->url,
            $otherUser->qrCode()->firstOrFail()->url
        );

        $this->login($otherUser);

        Livewire::test(QrCodePage::class)
            ->assertOk()
            ->assertSee($otherUser->qrCode()->firstOrFail()->url)
            ->assertDontSee($this->tenant->qrCode()->firstOrFail()->url);
    }

    public function test_another_tenant_cannot_point_the_page_at_the_first_tenants_qr_code(): void
    {
        $otherUser = User::factory()->create();

        $this->login($otherUser);

        Livewire::test(QrCodePage::class)
            ->set('qrCodeId', $this->tenant->qrCode()->firstOrFail()->getKey())
            ->assertDontSee($this->tenant->qrCode()->firstOrFail()->url);

        $this->assertNull(
            Livewire::test(QrCodePage::class)
                ->set('qrCodeId', $this->tenant->qrCode()->firstOrFail()->getKey())
                ->instance()
                ->getQrCode()
        );
    }
}
