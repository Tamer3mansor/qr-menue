<?php

namespace App\Http\Controllers;

use App\Ai\Agents\MenuChatAgent;
use App\Ai\Agents\SiteScopeGuard;
use App\Models\User;
use App\Traits\ResolvesTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class ChatController extends Controller
{
    use ResolvesTenant;

    /**
     * Five exchanges, which is enough context for the assistant without letting
     * a session grow forever.
     */
    protected const MAX_HISTORY = 10;

    /**
     * The fixed reply used when a visitor asks something outside the business.
     */
    protected const OFF_TOPIC_REPLY = 'أنا مساعد الموقع فقط، ومش هقدر أجاوب على السؤال ده. تقدر تسألني عن المنتجات، الأسعار، العروض، أو ساعات العمل.';

    public function chat(Request $request, string $slug): JsonResponse
    {
        $tenant = $this->resolveTenant($slug);

        abort_unless($tenant->hasRunningSubscription(), 404);

        $settings = $tenant->settings;

        abort_unless($settings?->chat_enabled ?? true, 404);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $sessionKey = $this->sessionKey($tenant);

        try {
            $guard = (new SiteScopeGuard)->prompt($validated['message']);

            $inScope = $guard instanceof StructuredAgentResponse
                && ($guard->structured['in_scope'] ?? false) === true;

            if (! $inScope) {
                return response()->json([
                    'message' => self::OFF_TOPIC_REPLY,
                    'history' => $this->history($sessionKey),
                ]);
            }

            $agent = new MenuChatAgent(
                $tenant->getKey(),
                $settings?->restaurant_name ?: $tenant->name,
            );

            $response = $this->formatResponse((string) $agent->prompt($validated['message']));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'تعذر الاتصال بالمساعد الذكي، حاول تاني.',
            ], 503);
        }

        $history = $this->history($sessionKey);

        $history[] = ['role' => 'user', 'content' => $validated['message']];
        $history[] = ['role' => 'assistant', 'content' => $response];
        $history = array_slice($history, -self::MAX_HISTORY);

        session([$sessionKey => $history]);

        return response()->json([
            'message' => $response,
            'history' => $history,
        ]);
    }

    public function clearChat(string $slug): JsonResponse
    {
        session()->forget($this->sessionKey($this->resolveTenant($slug)));

        return response()->json(['cleared' => true]);
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    protected function history(string $sessionKey): array
    {
        $history = session($sessionKey, []);

        if (! is_array($history)) {
            return [];
        }

        return array_values(array_filter(
            $history,
            fn (mixed $message): bool => is_array($message)
                && in_array($message['role'] ?? null, ['user', 'assistant'], true)
                && is_string($message['content'] ?? null),
        ));
    }

    protected function sessionKey(User $tenant): string
    {
        return "chat_{$tenant->getKey()}";
    }

    /**
     * The assistant is asked for plain WhatsApp-style text, but models still
     * slip markdown in, so the response is normalised before it reaches the UI.
     */
    protected function formatResponse(string $text): string
    {
        // Remove markdown formatting before the UI renders the reply as text.
        $text = preg_replace('/\*\*(.*?)\*\*/u', '$1', $text) ?? $text;
        $text = preg_replace('/\*(.*?)\*/u', '$1', $text) ?? $text;
        $text = preg_replace('/#{1,6}\s+/u', '', $text) ?? $text;
        $text = preg_replace('/_{1,2}(.*?)_{1,2}/u', '$1', $text) ?? $text;
        $text = preg_replace('/`(.*?)`/u', '$1', $text) ?? $text;

        // Strip bullets and numbered lists, keeping each item on its own line.
        $text = preg_replace('/^[\-\*•]\s+/mu', '', $text) ?? $text;
        $text = preg_replace('/^\d+[\.\)]\s+/mu', '', $text) ?? $text;

        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        // Arabic-Indic digits keep the reply consistent with the chat style.
        $text = str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            $text,
        );

        return trim($text);
    }
}
