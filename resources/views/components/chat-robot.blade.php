@props([
    'primaryColor' => '#1E3A2F',
    'secondaryColor' => '#8FAF8A',
    'logoUrl' => null,
    'avatarType' => 'robot',
    'welcomeMessage' => 'أهلاً! كيف أقدر أساعدك؟',
])

@php
    $avatarType = in_array($avatarType, ['robot', 'chef', 'star', 'logo'], true)
        ? $avatarType
        : 'robot';

    if ($avatarType === 'logo' && blank($logoUrl)) {
        $avatarType = 'robot';
    }
@endphp

<div
    class="robot-wrap"
    id="chatBtn"
    data-type="{{ $avatarType }}"
    data-welcome="{{ $welcomeMessage }}"
    role="button"
    tabindex="0"
    aria-label="فتح المساعد الذكي"
    aria-controls="qrChatWindow"
    aria-expanded="false"
>
    @if ($avatarType === 'logo')
        <!-- Logo version -->
        <div class="robot-logo">
            <img src="{{ $logoUrl }}" alt="">
        </div>
    @else
        <!-- SVG robot -->
        <svg
            id="robotSvg"
            width="64"
            height="72"
            viewBox="0 0 64 72"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <defs>
                <radialGradient id="bodyGrad" cx="40%" cy="35%">
                    <stop offset="0%" stop-color="#ffffff"/>
                    <stop offset="100%" stop-color="#e8e8e8"/>
                </radialGradient>
                <radialGradient id="headGrad" cx="40%" cy="30%">
                    <stop offset="0%" stop-color="#ffffff"/>
                    <stop offset="100%" stop-color="#dcdcdc"/>
                </radialGradient>
                <radialGradient id="eyeGlow" cx="50%" cy="50%">
                    <stop offset="0%" stop-color="{{ $primaryColor }}" stop-opacity="1"/>
                    <stop offset="100%" stop-color="{{ $primaryColor }}" stop-opacity="0.3"/>
                </radialGradient>
                <filter id="softShadow">
                    <feDropShadow dx="0" dy="3" stdDeviation="3" flood-color="rgba(0,0,0,0.15)"/>
                </filter>
            </defs>

            <!-- Shadow تحت الجسم -->
            <ellipse cx="32" cy="70" rx="18" ry="4" fill="rgba(0,0,0,0.1)"/>

            <!-- Body - بيضاوي -->
            <ellipse cx="32" cy="52" rx="18" ry="16" fill="url(#bodyGrad)" filter="url(#softShadow)"/>

            <!-- Arms - مدورة -->
            <ellipse cx="10" cy="52" rx="6" ry="9" fill="url(#bodyGrad)" transform="rotate(-10, 10, 52)"/>
            <ellipse cx="54" cy="52" rx="6" ry="9" fill="url(#bodyGrad)" transform="rotate(10, 54, 52)"/>

            <!-- Chest light - accent color -->
            <circle cx="32" cy="54" r="5" fill="{{ $primaryColor }}" opacity="0.9"/>
            <circle cx="32" cy="54" r="3" fill="{{ $primaryColor }}">
                <animate attributeName="opacity" values="1;0.4;1" dur="2s" repeatCount="indefinite"/>
            </circle>

            <!-- Head -->
            <rect x="14" y="8" width="36" height="30" rx="14" fill="url(#headGrad)" filter="url(#softShadow)"/>

            <!-- Head accent top bar -->
            <rect x="22" y="9" width="20" height="5" rx="3" fill="{{ $primaryColor }}" opacity="0.8"/>

            <!-- Face screen - dark -->
            <rect x="17" y="15" width="30" height="18" rx="8" fill="#1a2535"/>

            <!-- Eyes glowing -->
            <ellipse cx="24" cy="24" rx="5" ry="4" fill="url(#eyeGlow)" opacity="0.9">
                <animate attributeName="ry" values="4;0.5;4" dur="4s" begin="1s" repeatCount="indefinite"/>
            </ellipse>
            <ellipse cx="40" cy="24" rx="5" ry="4" fill="url(#eyeGlow)" opacity="0.9">
                <animate attributeName="ry" values="4;0.5;4" dur="4s" begin="1.1s" repeatCount="indefinite"/>
            </ellipse>

            <!-- Eye shine dots -->
            <circle cx="26" cy="22" r="1.5" fill="white" opacity="0.7"/>
            <circle cx="42" cy="22" r="1.5" fill="white" opacity="0.7"/>

            <!-- Ears/Side bumps -->
            <circle cx="13" cy="23" r="4" fill="url(#headGrad)"/>
            <circle cx="51" cy="23" r="4" fill="url(#headGrad)"/>

            <!-- Antenna -->
            <line x1="32" y1="8" x2="32" y2="2" stroke="#cccccc" stroke-width="2" stroke-linecap="round"/>
            <circle cx="32" cy="2" r="3" fill="{{ $primaryColor }}">
                <animate attributeName="opacity" values="1;0.3;1" dur="1.5s" repeatCount="indefinite"/>
            </circle>
        </svg>
    @endif

    <!-- Notification dot -->
    <div class="chat-dot" id="chatDot" aria-hidden="true"></div>
</div>

<style>
    #chatBtn {
        --rc: {{ $primaryColor }};
        --rc2: {{ $secondaryColor }};
        position: fixed;
        bottom: 24px;
        left: 24px;
        right: auto;
        top: auto;
        width: 64px;
        height: 72px;
        cursor: pointer;
        z-index: 1000;
        touch-action: none;
        -webkit-tap-highlight-color: transparent;
        filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.2));
        animation: robot-entrance 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    /* entrance animation */
    @keyframes robot-entrance {
        0% {
            opacity: 0;
            transform: translateY(100px) scale(0.3) rotate(-15deg);
        }
        70% {
            transform: translateY(-8px) scale(1.08) rotate(3deg);
        }
        85% {
            transform: translateY(4px) scale(0.97) rotate(-1deg);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1) rotate(0deg);
        }
    }

    /* floating idle */
    #chatBtn:not(.is-open):not(.dragging) #robotSvg {
        animation: robot-float 3s ease-in-out infinite;
    }

    @keyframes robot-float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-6px); }
    }

    /* hover - head wobble */
    #chatBtn:hover #robotSvg {
        animation: robot-wobble 0.5s ease;
    }

    @keyframes robot-wobble {
        0%, 100% { transform: rotate(0deg) scale(1); }
        25% { transform: rotate(-5deg) scale(1.05); }
        75% { transform: rotate(5deg) scale(1.05); }
    }

    /* click */
    #chatBtn:active #robotSvg {
        transform: scale(0.88);
        transition: transform 0.1s ease;
    }

    #chatBtn.dragging {
        animation: none;
        cursor: grabbing;
    }

    #chatBtn:focus-visible {
        outline: 3px solid var(--rc2);
        outline-offset: 3px;
    }

    /* logo variant */
    #chatBtn .robot-logo {
        width: 64px;
        height: 64px;
        overflow: hidden;
        border-radius: 50%;
        border: 3px solid var(--rc);
        background: white;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    }

    #chatBtn .robot-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* notification dot */
    #chatBtn .chat-dot {
        position: absolute;
        top: 2px;
        right: 0;
        width: 13px;
        height: 13px;
        background: #E8705A;
        border-radius: 50%;
        border: 2px solid white;
        animation: dot-bounce 2s ease infinite;
    }

    @keyframes dot-bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.25); }
    }

    @media (prefers-reduced-motion: reduce) {
        #chatBtn {
            animation: none;
        }

        #chatBtn #robotSvg,
        #chatBtn .chat-dot {
            animation: none !important;
        }
    }
</style>

<script>
    (() => {
        const btn = document.getElementById('chatBtn');

        if (!btn) {
            return;
        }

        const positionKey = 'chatBtnPos';

        let startX = 0;
        let startY = 0;
        let startLeft = 0;
        let startBottom = 0;
        let isDragging = false;
        let suppressClickUntil = 0;

        const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

        const sizeOf = () => ({
            width: btn.offsetWidth || 64,
            height: btn.offsetHeight || 72,
        });

        const setPosition = (left, bottom) => {
            btn.style.left = `${left}px`;
            btn.style.bottom = `${bottom}px`;
            btn.style.right = 'auto';
            btn.style.top = 'auto';
        };

        const restorePosition = () => {
            try {
                const saved = JSON.parse(localStorage.getItem(positionKey) || 'null');

                if (!saved) {
                    return;
                }

                const left = Number.parseFloat(saved.left);
                const bottom = Number.parseFloat(saved.bottom);
                const { width, height } = sizeOf();

                if (Number.isFinite(left) && Number.isFinite(bottom)) {
                    setPosition(
                        clamp(left, 0, Math.max(0, window.innerWidth - width)),
                        clamp(bottom, 0, Math.max(0, window.innerHeight - height)),
                    );
                }
            } catch (error) {
                // A corrupt or unavailable store just leaves the default position.
            }
        };

        restorePosition();

        function dragStart(event) {
            const touch = event.touches ? event.touches[0] : event;
            const rect = btn.getBoundingClientRect();

            startX = touch.clientX;
            startY = touch.clientY;
            startLeft = rect.left;
            startBottom = window.innerHeight - rect.bottom;
            isDragging = false;

            document.addEventListener('mousemove', dragMove);
            document.addEventListener('touchmove', dragMove, { passive: false });
            document.addEventListener('mouseup', dragEnd);
            document.addEventListener('touchend', dragEnd);
        }

        function dragMove(event) {
            if (event.type === 'touchmove') {
                event.preventDefault();
            }

            const touch = event.touches ? event.touches[0] : event;
            const dx = touch.clientX - startX;
            const dy = touch.clientY - startY;

            if (!isDragging && (Math.abs(dx) > 5 || Math.abs(dy) > 5)) {
                isDragging = true;
                btn.classList.add('dragging');
            }

            if (!isDragging) {
                return;
            }

            const { width, height } = sizeOf();
            const newLeft = clamp(startLeft + dx, 0, Math.max(0, window.innerWidth - width));
            const newBottom = clamp(startBottom - dy, 0, Math.max(0, window.innerHeight - height));

            setPosition(newLeft, newBottom);
        }

        function dragEnd() {
            document.removeEventListener('mousemove', dragMove);
            document.removeEventListener('touchmove', dragMove);
            document.removeEventListener('mouseup', dragEnd);
            document.removeEventListener('touchend', dragEnd);

            if (isDragging) {
                suppressClickUntil = Date.now() + 400;

                try {
                    localStorage.setItem(positionKey, JSON.stringify({
                        left: btn.style.left,
                        bottom: btn.style.bottom,
                    }));
                } catch (error) {
                    // Private modes can reject writes; the drag still works.
                }
            }

            setTimeout(() => {
                isDragging = false;
                btn.classList.remove('dragging');
            }, 50);
        }

        btn.addEventListener('mousedown', dragStart);
        btn.addEventListener('touchstart', dragStart, { passive: true });

        btn.addEventListener('click', (event) => {
            if (Date.now() < suppressClickUntil) {
                event.preventDefault();
                event.stopPropagation();

                return;
            }

            if (typeof window.toggleChat === 'function') {
                window.toggleChat();
            }
        });

        btn.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();

            if (typeof window.toggleChat === 'function') {
                window.toggleChat();
            }
        });
    })();
</script>
