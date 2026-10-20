@php
    $chatAvatarType = in_array($settings?->chat_avatar_type, ['robot', 'chef', 'star', 'logo'], true)
        ? $settings->chat_avatar_type
        : 'robot';
    $chatLogo = $settings?->logo_url;
    $chatName = $settings?->restaurant_name ?: 'المطعم';
    $chatWelcome = filled($settings?->chat_welcome_message)
        ? $settings->chat_welcome_message
        : 'أهلاً! كيف أقدر أساعدك؟';
    $chatEmoji = match ($chatAvatarType) {
        'chef' => '👨‍🍳',
        'star' => '⭐',
        default => '🤖',
    };
@endphp

<div id="qrChat" dir="rtl">
    <button
        type="button"
        class="qr-chat-button"
        id="qrChatButton"
        aria-label="فتح المساعد الذكي"
        aria-controls="qrChatWindow"
        aria-expanded="false"
    >
        @if ($chatAvatarType === 'logo' && $chatLogo)
            <img src="{{ $chatLogo }}" alt="" width="40" height="40" loading="lazy">
        @else
            <span aria-hidden="true">{{ $chatEmoji }}</span>
        @endif

        <span class="qr-chat-dot" id="qrChatDot" aria-hidden="true"></span>
    </button>

    <section class="qr-chat-window" id="qrChatWindow" aria-label="المساعد الذكي" hidden>
        <header class="qr-chat-header">
            <span class="qr-chat-avatar">
                @if ($chatAvatarType === 'logo' && $chatLogo)
                    <img src="{{ $chatLogo }}" alt="" width="32" height="32" loading="lazy">
                @else
                    <span aria-hidden="true">{{ $chatEmoji }}</span>
                @endif
            </span>

            <span class="qr-chat-identity">
                <strong>{{ $chatName }}</strong>
                <small>مساعد ذكي • متصل الآن</small>
            </span>

            <button type="button" class="qr-chat-icon-button" id="qrChatClear" aria-label="مسح المحادثة" title="مسح المحادثة">🗑</button>
            <button type="button" class="qr-chat-icon-button" id="qrChatClose" aria-label="إغلاق المساعد">✕</button>
        </header>

        <div class="qr-chat-messages" id="qrChatMessages" role="log" aria-live="polite">
            <div class="qr-chat-message qr-chat-message--bot">{{ $chatWelcome }}</div>
        </div>

        <div class="qr-chat-quick" id="qrChatQuick">
            <button type="button" data-quick="إيه أحسن أكل؟">إيه أحسن أكل؟</button>
            <button type="button" data-quick="العروض المتاحة">العروض المتاحة</button>
            <button type="button" data-quick="ساعات العمل">ساعات العمل</button>
        </div>

        <form class="qr-chat-form" id="qrChatForm">
            <input
                type="text"
                id="qrChatInput"
                name="message"
                maxlength="500"
                autocomplete="off"
                placeholder="اكتب سؤالك..."
                aria-label="رسالتك"
                required
            >
            <button type="submit" id="qrChatSend" aria-label="إرسال">➤</button>
        </form>
    </section>
</div>

<style>
    #qrChat {
        --qr-accent: var(--brand-primary, var(--primary, #1E3A2F));
        --qr-accent-contrast: #ffffff;
        --qr-bg: #f8f9fa;
        --qr-card: #ffffff;
        --qr-text: #1f2937;
        --qr-muted: #6b7280;
        --qr-border: #eceff3;
        position: fixed;
        inset-block-end: 24px;
        inset-inline-start: 24px;
        z-index: 1000;
        font-family: inherit;
    }

    #qrChat *,
    #qrChat *::before,
    #qrChat *::after {
        box-sizing: border-box;
    }

    #qrChat .qr-chat-button {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: var(--qr-accent);
        color: var(--qr-accent-contrast);
        font-size: 28px;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .25);
        transition: transform .2s ease;
    }

    #qrChat .qr-chat-button:hover { transform: scale(1.08); }
    #qrChat .qr-chat-button:focus-visible { outline: 3px solid var(--qr-accent); outline-offset: 3px; }
    #qrChat .qr-chat-button img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }

    #qrChat .qr-chat-dot {
        position: absolute;
        top: 2px;
        inset-inline-end: 2px;
        width: 12px;
        height: 12px;
        border: 2px solid #fff;
        border-radius: 50%;
        background: #E8705A;
        animation: qr-chat-pulse 2s infinite;
    }

    #qrChat .qr-chat-window {
        position: absolute;
        inset-block-end: 76px;
        inset-inline-start: 0;
        display: flex;
        flex-direction: column;
        width: 320px;
        max-width: calc(100vw - 32px);
        height: 440px;
        max-height: calc(100vh - 130px);
        overflow: hidden;
        border-radius: 20px;
        background: var(--qr-card);
        box-shadow: 0 8px 40px rgba(0, 0, 0, .18);
    }

    #qrChat .qr-chat-window[hidden] { display: none; }

    #qrChat .qr-chat-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        background: var(--qr-accent);
        color: var(--qr-accent-contrast);
    }

    #qrChat .qr-chat-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 36px;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .2);
        font-size: 18px;
    }

    #qrChat .qr-chat-avatar img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }

    #qrChat .qr-chat-identity {
        display: flex;
        flex: 1;
        flex-direction: column;
        min-width: 0;
        line-height: 1.3;
    }

    #qrChat .qr-chat-identity strong { font-size: 13px; font-weight: 700; }
    #qrChat .qr-chat-identity small { font-size: 11px; opacity: .75; }

    #qrChat .qr-chat-icon-button {
        flex: 0 0 auto;
        padding: 2px 4px;
        border: 0;
        background: none;
        color: inherit;
        font-size: 17px;
        line-height: 1;
        cursor: pointer;
        opacity: .8;
    }

    #qrChat .qr-chat-icon-button:hover { opacity: 1; }

    #qrChat .qr-chat-messages {
        display: flex;
        flex: 1;
        flex-direction: column;
        gap: 8px;
        overflow-y: auto;
        padding: 12px;
        background: var(--qr-bg);
    }

    #qrChat .qr-chat-message {
        align-self: flex-start;
        max-width: 85%;
        padding: 10px 12px;
        border-radius: 12px 12px 12px 4px;
        background: var(--qr-card);
        color: var(--qr-text);
        font-size: 13px;
        line-height: 1.5;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
    }

    #qrChat .qr-chat-message--user {
        align-self: flex-end;
        border-radius: 12px 12px 4px 12px;
        background: var(--qr-accent);
        color: var(--qr-accent-contrast);
    }

    #qrChat .qr-chat-typing {
        display: inline-flex;
        gap: 4px;
        align-items: center;
        align-self: flex-start;
        padding: 12px 14px;
        border-radius: 12px;
        background: var(--qr-card);
        box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
    }

    #qrChat .qr-chat-typing span {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #9ca3af;
        animation: qr-chat-typing 1.2s infinite;
    }

    #qrChat .qr-chat-typing span:nth-child(2) { animation-delay: .2s; }
    #qrChat .qr-chat-typing span:nth-child(3) { animation-delay: .4s; }

    #qrChat .qr-chat-quick {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 8px 12px;
        border-top: 1px solid var(--qr-border);
        background: var(--qr-card);
    }

    #qrChat .qr-chat-quick[hidden] { display: none; }

    #qrChat .qr-chat-quick button {
        padding: 5px 10px;
        border: 0;
        border-radius: 20px;
        background: color-mix(in srgb, var(--qr-accent) 10%, #ffffff);
        color: var(--qr-accent);
        font-family: inherit;
        font-size: 11px;
        cursor: pointer;
    }

    #qrChat .qr-chat-quick button:hover { background: color-mix(in srgb, var(--qr-accent) 18%, #ffffff); }

    #qrChat .qr-chat-form {
        display: flex;
        gap: 8px;
        align-items: center;
        padding: 10px 12px;
        border-top: 1px solid var(--qr-border);
        background: var(--qr-card);
    }

    #qrChat .qr-chat-form input {
        flex: 1;
        min-width: 0;
        padding: 8px 14px;
        border: 1.5px solid #e0e0e0;
        border-radius: 20px;
        outline: none;
        font-family: inherit;
        font-size: 13px;
    }

    #qrChat .qr-chat-form input:focus { border-color: var(--qr-accent); }

    #qrChat .qr-chat-form button {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 36px;
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 50%;
        background: var(--qr-accent);
        color: var(--qr-accent-contrast);
        font-size: 16px;
        cursor: pointer;
    }

    #qrChat .qr-chat-form button:disabled { opacity: .5; cursor: wait; }

    @keyframes qr-chat-pulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.3); opacity: .7; }
    }

    @keyframes qr-chat-typing {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-6px); }
    }

    @media (prefers-reduced-motion: reduce) {
        #qrChat .qr-chat-button,
        #qrChat .qr-chat-dot,
        #qrChat .qr-chat-typing span {
            animation: none;
            transition: none;
        }
    }
</style>

<script>
    (() => {
        const root = document.getElementById('qrChat');
        const button = document.getElementById('qrChatButton');
        const windowEl = document.getElementById('qrChatWindow');
        const dot = document.getElementById('qrChatDot');
        const messages = document.getElementById('qrChatMessages');
        const quick = document.getElementById('qrChatQuick');
        const form = document.getElementById('qrChatForm');
        const input = document.getElementById('qrChatInput');
        const sendButton = document.getElementById('qrChatSend');

        if (!root || !button || !windowEl || !form || !input) {
            return;
        }

        const config = {
            chatUrl: @json($chatUrl),
            clearUrl: @json($clearUrl),
            csrf: @json(csrf_token()),
            welcome: @json($chatWelcome),
        };

        let isOpen = false;
        let isBusy = false;

        function messageElement(text, role) {
            const element = document.createElement('div');
            element.className = 'qr-chat-message ' + (role === 'user' ? 'qr-chat-message--user' : 'qr-chat-message--bot');
            element.textContent = text;
            return element;
        }

        function scrollToBottom() {
            messages.scrollTop = messages.scrollHeight;
        }

        function addMessage(text, role) {
            messages.appendChild(messageElement(text, role));
            scrollToBottom();
        }

        function showTyping() {
            const element = document.createElement('div');
            element.id = 'qrChatTyping';
            element.className = 'qr-chat-typing';
            element.innerHTML = '<span></span><span></span><span></span>';
            messages.appendChild(element);
            scrollToBottom();
        }

        function hideTyping() {
            document.getElementById('qrChatTyping')?.remove();
        }

        function setOpen(open) {
            isOpen = open;
            windowEl.hidden = !open;
            button.setAttribute('aria-expanded', String(open));
            dot.style.display = open ? 'none' : 'block';

            if (open) {
                input.focus();
                scrollToBottom();
            }
        }

        function lockSend(locked) {
            isBusy = locked;
            sendButton.disabled = locked;
            input.disabled = locked;
        }

        async function sendMessage(text) {
            const message = text.trim();

            if (message === '' || isBusy) {
                return;
            }

            quick.hidden = true;
            addMessage(message, 'user');
            showTyping();
            lockSend(true);

            try {
                const response = await fetch(config.chatUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    body: JSON.stringify({ message }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'حدث خطأ، حاول تاني.');
                }

                hideTyping();
                addMessage(data.message, 'bot');
            } catch (error) {
                hideTyping();
                addMessage(error.message || 'حدث خطأ، حاول تاني.', 'bot');
            } finally {
                lockSend(false);
                input.focus();
            }
        }

        button.addEventListener('click', () => setOpen(!isOpen));
        document.getElementById('qrChatClose')?.addEventListener('click', () => setOpen(false));

        document.getElementById('qrChatClear')?.addEventListener('click', async () => {
            if (isBusy) {
                return;
            }

            try {
                await fetch(config.clearUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                });
            } catch (error) {
                // The local transcript is cleared even if the request fails.
            }

            messages.replaceChildren(messageElement(config.welcome, 'bot'));
            quick.hidden = false;
            input.value = '';
            input.focus();
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const message = input.value;
            input.value = '';
            sendMessage(message);
        });

        quick.addEventListener('click', (event) => {
            const suggestion = event.target.closest('[data-quick]');

            if (suggestion) {
                sendMessage(suggestion.dataset.quick);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isOpen) {
                setOpen(false);
            }
        });
    })();
</script>
