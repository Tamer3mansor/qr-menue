<?php

namespace Tests\Feature;

use App\Ai\Agents\MenuChatAgent;
use App\Ai\Agents\SiteScopeGuard;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected Setting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create(['name' => 'مطعم الاختبار']);

        $this->settings = Setting::factory()->create([
            'user_id' => $this->tenant->getKey(),
            'restaurant_name' => 'مطعم الاختبار',
        ]);

        SiteScopeGuard::fake(fn (): array => ['in_scope' => true]);
    }

    public function test_the_chat_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('menu.chat'));
        $this->assertTrue(Route::has('menu.chat.clear'));
        $this->assertTrue(Route::has('site.chat'));
        $this->assertTrue(Route::has('site.chat.clear'));
    }

    public function test_a_message_returns_a_json_response_and_updates_the_session(): void
    {
        MenuChatAgent::fake(['أهلاً بيك!']);

        $response = $this->postJson(route('menu.chat', ['slug' => $this->tenant->getKey()]), [
            'message' => 'عايز حاجة تتاكل',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'أهلاً بيك!',
                'history' => [
                    ['role' => 'user', 'content' => 'عايز حاجة تتاكل'],
                    ['role' => 'assistant', 'content' => 'أهلاً بيك!'],
                ],
            ]);

        $this->assertSame($response->json('history'), session("chat_{$this->tenant->getKey()}"));
    }

    public function test_the_site_route_uses_the_same_chat(): void
    {
        MenuChatAgent::fake(['أهلاً']);

        $this->postJson(route('site.chat', ['slug' => $this->tenant->getKey()]), [
            'message' => 'مرحبا',
        ])->assertOk()->assertJsonPath('message', 'أهلاً');
    }

    public function test_the_session_keeps_only_the_last_ten_messages(): void
    {
        MenuChatAgent::fake(['الرد الجديد']);

        $old = [];

        for ($i = 0; $i < 10; $i++) {
            $old[] = [
                'role' => $i % 2 === 0 ? 'user' : 'assistant',
                'content' => "قديم {$i}",
            ];
        }

        $this->withSession(["chat_{$this->tenant->getKey()}" => $old])
            ->postJson(route('menu.chat', ['slug' => $this->tenant->getKey()]), [
                'message' => 'سؤال جديد',
            ])
            ->assertOk();

        $history = session("chat_{$this->tenant->getKey()}");

        $this->assertCount(10, $history);
        $this->assertSame('قديم 2', $history[0]['content']);
        $this->assertSame('سؤال جديد', $history[8]['content']);
        $this->assertSame('الرد الجديد', $history[9]['content']);
    }

    public function test_the_message_is_required_and_limited_to_500_characters(): void
    {
        MenuChatAgent::fake();

        $this->postJson(route('menu.chat', ['slug' => $this->tenant->getKey()]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');

        $this->postJson(route('menu.chat', ['slug' => $this->tenant->getKey()]), [
            'message' => str_repeat('ا', 501),
        ])->assertUnprocessable()->assertJsonValidationErrors('message');
    }

    public function test_clear_chat_forgets_the_session(): void
    {
        $sessionKey = "chat_{$this->tenant->getKey()}";

        $this->withSession([$sessionKey => [
            ['role' => 'user', 'content' => 'مرحبا'],
        ]])
            ->postJson(route('menu.chat.clear', ['slug' => $this->tenant->getKey()]))
            ->assertOk()
            ->assertJson(['cleared' => true]);

        $this->assertNull(session($sessionKey));
    }

    public function test_chat_is_unavailable_when_the_toggle_is_off(): void
    {
        MenuChatAgent::fake();

        $this->settings->update(['chat_enabled' => false]);

        $this->postJson(route('menu.chat', ['slug' => $this->tenant->getKey()]), [
            'message' => 'مرحبا',
        ])->assertNotFound();

        MenuChatAgent::assertNeverPrompted();
    }

    public function test_an_unknown_tenant_cannot_chat(): void
    {
        $this->postJson(route('menu.chat', ['slug' => 999999]), [
            'message' => 'مرحبا',
        ])->assertNotFound();
    }

    public function test_off_topic_questions_are_refused_without_calling_the_menu_agent(): void
    {
        SiteScopeGuard::fake([['in_scope' => false]]);
        MenuChatAgent::fake();

        $this->postJson(route('menu.chat', ['slug' => $this->tenant->getKey()]), [
            'message' => 'اشرحلي إزاي أعمل حملة تسويق',
        ])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'أنا مساعد الموقع فقط، ومش هقدر أجاوب على السؤال ده. تقدر تسألني عن المنتجات، الأسعار، العروض، أو ساعات العمل.',
            )
            ->assertJsonPath('history', []);

        MenuChatAgent::assertNeverPrompted();
    }

    public function test_the_chat_widget_is_shown_on_both_public_pages(): void
    {
        $this->get("/menu/{$this->tenant->getKey()}")
            ->assertOk()
            ->assertSee('id="qrChat"', false);

        $this->get("/site/{$this->tenant->getKey()}")
            ->assertOk()
            ->assertSee('id="qrChat"', false);
    }

    public function test_the_chat_widget_is_hidden_when_the_toggle_is_off(): void
    {
        $this->settings->update(['chat_enabled' => false]);

        $this->get("/menu/{$this->tenant->getKey()}")
            ->assertOk()
            ->assertDontSee('id="qrChat"', false);
    }
}
