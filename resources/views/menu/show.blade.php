@php
    $primary = $settings?->primary_color ?: '#000000';
    $secondary = $settings?->secondary_color ?: '#ffffff';
    $hasComboOffers = $comboOffers->isNotEmpty();
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings?->restaurant_name ?? 'القائمة' }}</title>

    <script>document.documentElement.classList.add('js');</script>

    <style>
        :root {
            --brand-primary: {{ $primary }};
            --brand-secondary: {{ $secondary }};
            --brand-tint: color-mix(in srgb, var(--brand-primary) 12%, #ffffff);
            --page-bg: #f6f6f7;
            --text: #16181d;
            --muted: #6b7280;
            --border-color: #e5e7eb;
            --radius: 14px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--page-bg);
            color: var(--text);
            font-family: 'Instrument Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
            line-height: 1.5;
            -webkit-text-size-adjust: 100%;
        }

        .wrap { max-width: 720px; margin: 0 auto; padding: 0 16px 48px; }

        /* Header */
        .menu-header {
            position: relative;
            margin: 0 -16px 20px;
            padding: 28px 20px 24px;
            background-color: var(--brand-primary);
            background-image: var(--header-bg, none);
            background-size: cover;
            background-position: center;
        }

        .menu-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgb(0 0 0 / .45), rgb(0 0 0 / .65));
        }

        .menu-header > * { position: relative; color: #fff; }

        .brand { display: flex; align-items: center; gap: 14px; }

        .brand-logo {
            width: 64px;
            height: 64px;
            flex: 0 0 auto;
            border-radius: 50%;
            object-fit: cover;
            background: #fff;
            padding: 4px;
        }

        .brand-logo--placeholder {
            display: grid;
            place-items: center;
            font-size: 26px;
        }

        .brand-name { margin: 0; font-size: 22px; font-weight: 700; line-height: 1.25; }

        .brand-phone {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 44px;
            margin-top: 6px;
            font-size: 15px;
            color: #fff;
            text-decoration: none;
        }

        /* Category chips */
        .chips {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            margin: 0 -16px 8px;
            padding: 4px 16px 12px;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .chips::-webkit-scrollbar { display: none; }

        .chip {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            min-width: 44px;
            padding: 7px 16px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid var(--brand-primary);
            color: var(--brand-primary);
            font: inherit;
            font-size: 14px;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
            transition: all .2s ease;
        }

        .chip.is-active {
            background: var(--brand-primary);
            border-color: var(--brand-primary);
            color: #fff;
        }

        /* Sections */
        .section { margin-top: 26px; scroll-margin-top: 12px; }

        .section.is-hidden { display: none; }

        .category-section { transition: opacity .4s ease-out, transform .4s ease-out; }

        .js .category-section { opacity: 0; transform: translateY(20px); }

        .js .category-section.is-visible { opacity: 1; transform: none; }

        .section-title {
            margin: 0 0 12px;
            font-size: 17px;
            font-weight: 700;
            padding-inline-start: 10px;
            border-inline-start: 4px solid var(--brand-primary);
        }

        /* Item card */
        .card {
            position: relative;
            display: flex;
            flex-direction: row-reverse;
            align-items: stretch;
            gap: 12px;
            padding: 12px;
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 1px 2px rgb(0 0 0 / .04);
            overflow: hidden;
            transition: opacity .15s ease-out, transform .15s ease-out;
        }

        .card::before {
            content: '';
            position: absolute;
            inset-inline-start: 0;
            top: 12px;
            bottom: 12px;
            width: 3px;
            border-radius: 3px;
            background: var(--brand-primary);
            opacity: .4;
        }

        .card + .card::after {
            content: '';
            position: absolute;
            inset-inline: 12px;
            top: 0;
            height: 1px;
            background: linear-gradient(to left, transparent, var(--border-color), transparent);
        }

        .card.is-hidden { display: none; }

        .card-image {
            width: 90px;
            height: 90px;
            flex: 0 0 auto;
            border-radius: 12px;
            object-fit: cover;
            background: var(--brand-tint);
        }

        .card-body {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .card-title { margin: 0; font-size: 15px; font-weight: 700; }

        .card-desc {
            margin: 4px 0 0;
            font-size: 13px;
            color: var(--muted);
            overflow-wrap: anywhere;
        }

        .card-meta {
            margin-top: auto;
            padding-top: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .price {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            background: color-mix(in srgb, var(--brand-primary) 10%, transparent);
            color: var(--brand-primary);
            font-size: 14px;
            font-weight: 700;
        }

        .price--old { font-size: 13px; font-weight: 400; color: var(--muted); text-decoration: line-through; }

        .badge {
            padding: 3px 9px;
            border-radius: 999px;
            background: var(--brand-primary);
            color: var(--brand-secondary);
            font-size: 12px;
            font-weight: 700;
        }

        .discount {
            padding: 3px 9px;
            border-radius: 999px;
            background: var(--brand-tint);
            color: var(--brand-primary);
            font-size: 12px;
            font-weight: 700;
        }

        .offer-title { font-size: 18px; font-weight: 700; margin: 0 0 2px; }

        .offer-expires { font-size: 13px; color: var(--muted); margin: 0 0 14px; }

        /* Combo offers */
        .combo-thumbs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 4px;
            width: 90px;
            flex: 0 0 auto;
            align-content: start;
        }

        .combo-thumbs img,
        .combo-thumb-placeholder {
            width: 41px;
            height: 41px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--brand-tint);
        }

        .combo-thumb-placeholder {
            display: grid;
            place-items: center;
            font-size: 16px;
        }

        .combo-items {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin: 6px 0 0;
            padding: 0;
            list-style: none;
        }

        .combo-items li {
            padding: 3px 10px;
            border-radius: 999px;
            background: var(--brand-tint);
            color: var(--brand-primary);
            font-size: 13px;
            font-weight: 600;
        }

        .empty {
            padding: 40px 16px;
            text-align: center;
            color: var(--muted);
            background: #fff;
            border-radius: var(--radius);
        }

        @media (min-width: 640px) {
            .card-title { font-size: 16px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>
<body>
<div class="wrap">
    <header
        class="menu-header"
        @if ($settings?->bg_image_url) style="--header-bg: url('{{ $settings->bg_image_url }}')" @endif
    >
        <div class="brand">
            @if ($settings?->logo_url)
                <img class="brand-logo" src="{{ $settings->logo_url }}" alt="{{ $settings->restaurant_name }}" loading="lazy">
            @else
                <div class="brand-logo brand-logo--placeholder" aria-hidden="true">🍽️</div>
            @endif

            <div>
                <h1 class="brand-name">{{ $settings?->restaurant_name ?? 'القائمة' }}</h1>

                @if (filled($settings?->phone))
                    <a class="brand-phone" href="tel:{{ $settings->phone }}">{{ $settings->phone }}</a>
                @endif
            </div>
        </div>
    </header>

    @if ($hasComboOffers)
        <section class="section" id="offers">
            <h2 class="section-title">عروض خاصة</h2>

            <p class="offer-expires">كومبوهات متوفرة الآن</p>

            @foreach ($comboOffers as $offer)
                <article class="card">
                    @if ($offer->image_url)
                        <img class="card-image" src="{{ $offer->image_url }}" alt="{{ $offer->title ?: 'كومبو خاص' }}" loading="lazy">
                    @elseif ($offer->items->contains(fn ($item): bool => filled($item->image_url)))
                        <div class="combo-thumbs">
                            @foreach ($offer->items as $item)
                                @if ($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy">
                                @else
                                    <span class="combo-thumb-placeholder">🍽️</span>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <div class="card-body">
                        <p class="offer-title">{{ $offer->title ?: 'كومبو خاص' }}</p>

                        <ul class="combo-items">
                            @foreach ($offer->items as $item)
                                <li>{{ $item->title }}</li>
                            @endforeach
                        </ul>

                        <div class="card-meta">
                            <span class="price">{{ $currency }} {{ number_format((float) $offer->offer_price, 2) }}</span>

                            @if ($offer->totalItemsPrice() > 0)
                                <span class="price--old">{{ $currency }} {{ number_format($offer->totalItemsPrice(), 2) }}</span>
                            @endif

                            @if (($offer->discountPercentage() ?? 0) > 0)
                                <span class="discount">خصم {{ $offer->discountPercentage() }}%</span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </section>
    @endif

    @if ($categories->isNotEmpty())
        <nav class="chips" aria-label="الأقسام">
            <button type="button" class="chip is-active" data-filter="all">الكل</button>

            @foreach ($categories as $category)
                <button type="button" class="chip" data-filter="{{ $category->id }}">{{ $category->name }}</button>
            @endforeach
        </nav>
    @endif

    @forelse ($categories as $category)
        <section class="section category-section" id="category-{{ $category->id }}">
            <h2 class="section-title">{{ $category->name }}</h2>

            @foreach ($category->items as $item)
                @php $offer = $offersByItem->get($item->id); @endphp

                <article class="card" data-categories="{{ $item->categories->pluck('id')->join(',') }}">
                    @if ($item->image_url)
                        <img class="card-image" src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy">
                    @endif

                    <div class="card-body">
                        <p class="card-title">{{ $item->title }}</p>

                        @if (filled($item->description))
                            <p class="card-desc">{{ \Illuminate\Support\Str::limit($item->description, 60) }}</p>
                        @endif

                        <div class="card-meta">
                            @if ($offer)
                                <span class="price">{{ $currency }} {{ number_format((float) $offer->offer_price, 2) }}</span>
                                <span class="price--old">{{ $currency }} {{ number_format((float) $item->price, 2) }}</span>

                                @if ($offer->discountPercentage($item) > 0)
                                    <span class="discount">خصم {{ $offer->discountPercentage($item) }}%</span>
                                @endif

                                <span class="badge">عرض</span>
                            @else
                                <span class="price">{{ $currency }} {{ number_format((float) $item->price, 2) }}</span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </section>
    @empty
        @unless ($hasComboOffers)
            <p class="empty">لا توجد أصناف متاحة حالياً.</p>
        @endunless
    @endforelse
</div>

<script>
    (function () {
        'use strict';

        var chips = Array.prototype.slice.call(document.querySelectorAll('[data-filter]'));
        var sections = Array.prototype.slice.call(document.querySelectorAll('.category-section'));

        var HIDE_MS = 150;
        var SHOW_MS = 200;
        var STAGGER_MS = 50;

        function matches(card, filter) {
            var categories = (card.getAttribute('data-categories') || '').split(',');

            return filter === 'all' || categories.indexOf(filter) !== -1;
        }

        /**
         * Fades a card out over 150ms, then removes it from the layout so it
         * stops taking up space in the grid.
         */
        function hideCard(card) {
            if (card.classList.contains('is-hidden')) {
                return;
            }

            window.clearTimeout(card._showTimer);

            card.style.transition = 'opacity ' + HIDE_MS + 'ms ease-out, transform ' + HIDE_MS + 'ms ease-out';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';

            card._hideTimer = window.setTimeout(function () {
                card.classList.add('is-hidden');
                card.style.transition = '';
                card.style.opacity = '';
                card.style.transform = '';
            }, HIDE_MS);
        }

        /**
         * Brings a card back in at 200ms with a per-item stagger.
         */
        function showCard(card, delay) {
            window.clearTimeout(card._hideTimer);

            card.classList.remove('is-hidden');
            card.style.transition = 'none';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';

            card._showTimer = window.setTimeout(function () {
                card.style.transition = 'opacity ' + SHOW_MS + 'ms ease-out, transform ' + SHOW_MS + 'ms ease-out';
                card.style.opacity = '1';
                card.style.transform = 'scale(1)';

                window.setTimeout(function () {
                    card.style.transition = '';
                }, SHOW_MS + 20);
            }, delay);
        }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                var filter = chip.getAttribute('data-filter');

                chips.forEach(function (other) {
                    other.classList.toggle('is-active', other === chip);
                });

                sections.forEach(function (section) {
                    var cards = Array.prototype.slice.call(section.querySelectorAll('.card[data-categories]'));
                    var order = 0;
                    var hasVisible = false;

                    cards.forEach(function (card) {
                        if (matches(card, filter)) {
                            hasVisible = true;
                            showCard(card, order * STAGGER_MS);
                            order++;
                        } else {
                            hideCard(card);
                        }
                    });

                    window.setTimeout(function () {
                        section.classList.toggle('is-hidden', !hasVisible);
                    }, hasVisible ? 0 : HIDE_MS);
                });
            });
        });

        // Reveal each category section once as it scrolls into view.
        var reveal = function (section) {
            section.classList.add('is-visible');
        };

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        reveal(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            sections.forEach(function (section) {
                observer.observe(section);
            });
        } else {
            sections.forEach(reveal);
        }
    })();
</script>
</body>
</html>
