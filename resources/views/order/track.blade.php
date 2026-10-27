@php
    $primary = $settings?->primary_color ?: '#2D6A4F';
    $secondary = $settings?->secondary_color ?: '#52B788';
    $restaurantName = $settings?->restaurant_name ?: $tenant->name;
    $logoUrl = $settings?->logo_url;
    $siteUrl = route('site.show', ['slug' => $slug]);
    $trackUrl = route('site.track', ['slug' => $slug]);

    $statusLabels = \App\Models\Order::STATUS_LABELS;
    $statusKeys = array_keys($statusLabels);
    $currentIndex = $order ? array_search($order->status, $statusKeys, true) : false;
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;

    $placedAt = $order?->created_at;
    $placedAtLabel = $placedAt
        ? $placedAt->format('g:i').' '.($placedAt->format('A') === 'AM' ? 'ص' : 'م')
        : null;
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>تتبع الطلب — {{ $restaurantName }}</title>

    @if (filled($logoUrl))
        <link rel="icon" href="{{ $logoUrl }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: {{ $primary }};
            --secondary: {{ $secondary }};
            --text: #1A1A2E;
            --muted: #5b6472;
            --bg: #F8F9FA;
            --card-bg: #FFFFFF;
            --border: #e4e7ec;
            --radius: 14px;
            --shadow: 0 1px 2px rgb(26 26 46 / .05), 0 8px 24px rgb(26 26 46 / .06);
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: 'Cairo', system-ui, -apple-system, 'Segoe UI', sans-serif;
            line-height: 1.6;
        }

        img { max-width: 100%; height: auto; display: block; }

        a { color: inherit; text-decoration: none; }

        .container { width: 100%; max-width: 720px; margin-inline: auto; padding-inline: 18px; }

        .site-header {
            background: #fff;
            border-bottom: 1px solid var(--border);
        }

        .site-header__inner {
            display: flex; align-items: center; justify-content: space-between;
            gap: 14px; min-height: 64px;
        }

        .brand { display: inline-flex; align-items: center; gap: 10px; font-weight: 800; }
        .brand__logo { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; background: var(--primary); }
        .brand__placeholder {
            display: grid; place-items: center; width: 40px; height: 40px;
            border-radius: 10px; background: var(--primary); color: #fff;
            font-size: .8125rem; font-weight: 800;
        }

        .site-nav { display: flex; align-items: center; gap: 14px; font-size: .875rem; font-weight: 700; }
        .site-nav a { color: var(--muted); }
        .site-nav a:hover { color: var(--primary); }
        .site-nav a.is-active { color: var(--primary); }

        main { padding-block: 36px 54px; }

        .page-title { margin: 0 0 6px; font-size: clamp(1.5rem, 6vw, 2rem); }
        .page-lead { margin: 0 0 22px; color: var(--muted); }

        .track-form {
            display: flex; gap: 8px;
            padding: 14px; border-radius: var(--radius);
            background: var(--card-bg); border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .track-form input {
            flex: 1 1 auto; min-height: 48px; width: 100%;
            padding: 12px 14px; border: 1px solid var(--border); border-radius: 12px;
            background: #fff; color: var(--text); font: inherit; font-size: 1rem;
        }

        .track-form input:focus { outline: 2px solid color-mix(in srgb, var(--primary) 35%, #fff); border-color: var(--primary); }

        .track-form button {
            min-height: 48px; padding: 0 22px;
            border: 0; border-radius: 12px;
            background: var(--primary); color: #fff;
            font: inherit; font-weight: 700; cursor: pointer;
        }

        .result { margin-top: 22px; }

        .order-card {
            padding: 20px; border-radius: var(--radius);
            background: var(--card-bg); border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .order-card__head {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; flex-wrap: wrap;
            padding-bottom: 14px; border-bottom: 1px solid var(--border);
        }

        .order-number { margin: 0; font-size: 1.25rem; font-weight: 800; }

        .status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 999px;
            background: color-mix(in srgb, var(--primary) 10%, #fff);
            color: var(--primary); font-size: .8125rem; font-weight: 700;
        }

        .order-meta { display: flex; flex-wrap: wrap; gap: 14px; margin: 14px 0 0; padding: 0; list-style: none; }
        .order-meta li { color: var(--muted); font-size: .875rem; }
        .order-meta strong { color: var(--text); }

        .order-items { margin: 16px 0 0; padding: 0; list-style: none; }
        .order-items li {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; padding: 9px 0; border-bottom: 1px dashed #eef0f3;
            font-size: .9375rem;
        }
        .order-items li:last-child { border-bottom: 0; }
        .order-items .item-qty { color: var(--muted); font-size: .875rem; }

        .order-total {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border);
            font-weight: 800;
        }

        .timeline { margin: 22px 0 0; padding: 0; list-style: none; }
        .timeline__step {
            position: relative;
            display: flex; align-items: center; gap: 12px;
            min-height: 42px; padding-bottom: 10px;
            color: var(--muted); font-size: .9375rem;
        }
        .timeline__step:not(:last-child)::before {
            content: ''; position: absolute; inset-inline-start: 13px; top: 29px;
            width: 2px; height: calc(100% - 19px); background: var(--border);
        }
        .timeline__dot {
            display: grid; place-items: center; flex: 0 0 auto;
            width: 28px; height: 28px; border-radius: 50%;
            border: 2px solid var(--border); background: #fff;
            color: var(--muted); font-size: .8125rem; font-weight: 800;
            z-index: 1;
        }
        .timeline__step.is-done { color: var(--text); }
        .timeline__step.is-done .timeline__dot {
            border-color: var(--primary); background: var(--primary); color: #fff;
        }
        .timeline__step.is-done:not(:last-child)::before { background: var(--primary); }
        .timeline__step.is-current { color: var(--text); font-weight: 800; }
        .timeline__step.is-current .timeline__dot {
            border-color: var(--primary); background: #fff; color: var(--primary);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary) 14%, #fff);
        }
        .timeline__here {
            margin-inline-start: auto; padding: 2px 10px; border-radius: 999px;
            background: color-mix(in srgb, var(--primary) 10%, #fff);
            color: var(--primary); font-size: .75rem; font-weight: 700;
        }

        .track-error {
            margin: 18px 0 0; padding: 14px 16px; border-radius: 12px;
            background: #fdecec; color: #a61b1b; font-size: .9375rem; font-weight: 600;
        }

        .site-footer { padding-block: 26px; color: var(--muted); font-size: .8125rem; text-align: center; }
        .site-footer a { color: var(--primary); font-weight: 700; }

        @media (max-width: 420px) {
            .site-nav { gap: 10px; font-size: .8125rem; }
            .brand span:last-child { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <a class="brand" href="{{ $siteUrl }}">
                @if (filled($logoUrl))
                    <img class="brand__logo" src="{{ $logoUrl }}" alt="{{ $restaurantName }}" loading="lazy">
                @else
                    <span class="brand__placeholder" aria-hidden="true">{{ \Illuminate\Support\Str::of($restaurantName)->substr(0, 2) }}</span>
                @endif
                <span>{{ $restaurantName }}</span>
            </a>

            <nav class="site-nav" aria-label="التنقل الرئيسي">
                <a href="{{ $siteUrl }}">الرئيسية</a>
                <a class="is-active" href="{{ $trackUrl }}" aria-current="page">📦 تتبع طلبك</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <h1 class="page-title">تتبع طلبك</h1>
            <p class="page-lead">أدخل رقم الطلب لمعرفة حالته وتفاصيله.</p>

            <form class="track-form" method="GET" action="{{ $trackUrl }}">
                <label for="orderNumber" class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);">رقم الطلب</label>
                <input
                    id="orderNumber"
                    name="number"
                    type="number"
                    min="1"
                    inputmode="numeric"
                    placeholder="رقم طلبك مثلاً: 47"
                    value="{{ $number }}"
                    autocomplete="off"
                >
                <button type="submit">بحث</button>
            </form>

            <div class="result">
                @if (filled($error))
                    <p class="track-error">{{ $error }}</p>
                @endif

                @if ($order)
                    <article class="order-card">
                        <div class="order-card__head">
                            <h2 class="order-number">طلب {{ $order->displayNumber() }}</h2>
                            <span class="status-badge">
                                <span aria-hidden="true">●</span>
                                {{ $order->statusLabel() }}
                            </span>
                        </div>

                        <ul class="order-meta">
                            <li>نوع الطلب: <strong>{{ $order->deliveryTypeLabel() }}</strong></li>
                            @if (filled($placedAtLabel))
                                <li>وقت الطلب: <strong>{{ $placedAtLabel }}</strong></li>
                            @endif
                        </ul>

                        <ul class="order-items">
                            @foreach ($order->items as $item)
                                <li>
                                    <span>
                                        {{ $item->item_name }}
                                        <span class="item-qty">× {{ $item->quantity }}</span>
                                    </span>
                                    <span>{{ number_format((float) $item->subtotal, 2) }} {{ $currency }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="order-total">
                            <span>الإجمالي</span>
                            <span>{{ number_format((float) $order->total, 2) }} {{ $currency }}</span>
                        </div>

                        <ol class="timeline" aria-label="حالة الطلب">
                            @foreach ($statusLabels as $statusKey => $statusLabel)
                                @php
                                    $index = $loop->index;
                                    $stateClass = $index < $currentIndex ? 'is-done' : ($index === $currentIndex ? 'is-current' : 'is-pending');
                                @endphp
                                <li class="timeline__step {{ $stateClass }}">
                                    <span class="timeline__dot" aria-hidden="true">
                                        {{ $index < $currentIndex ? '✓' : ($index === $currentIndex ? '●' : '○') }}
                                    </span>
                                    <span>{{ $statusLabel }}</span>
                                    @if ($index === $currentIndex)
                                        <span class="timeline__here">أنت هنا</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </article>
                @endif
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            جميع الحقوق محفوظة {{ now()->year }} — {{ $restaurantName }}
        </div>
    </footer>
</body>
</html>
