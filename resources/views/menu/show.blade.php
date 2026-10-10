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

    <style>
        :root {
            --brand-primary: {{ $primary }};
            --brand-secondary: {{ $secondary }};
            --brand-tint: color-mix(in srgb, var(--brand-primary) 12%, #ffffff);
            --page-bg: #f6f6f7;
            --text: #16181d;
            --muted: #6b7280;
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
            padding: 4px 0 12px;
            margin: 0 -16px 8px;
            padding-inline: 16px;
            scrollbar-width: none;
        }

        .chips::-webkit-scrollbar { display: none; }

        .chip {
            flex: 0 0 auto;
            padding: 7px 14px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #e5e7eb;
            color: var(--text);
            font: inherit;
            font-size: 14px;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }

        .chip.is-active {
            background: var(--brand-primary);
            border-color: var(--brand-primary);
            color: #fff;
        }

        /* Sections */
        .section { margin-top: 26px; scroll-margin-top: 12px; }

        .section.is-hidden { display: none; }

        .section-title {
            margin: 0 0 12px;
            font-size: 17px;
            font-weight: 700;
            padding-inline-start: 10px;
            border-inline-start: 4px solid var(--brand-primary);
        }

        .card {
            display: flex;
            gap: 12px;
            padding: 12px;
            margin-bottom: 10px;
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 1px 2px rgb(0 0 0 / .06);
            max-height: 204px;
            overflow: hidden;
            animation: card-in .25s ease;
        }

        .card.is-hidden { display: none; animation: none; }

        @keyframes card-in {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: none; }
        }

        .card-image {
            width: 84px;
            height: 180px;
            flex: 0 0 auto;
            border-radius: 10px;
            object-fit: cover;
            background: var(--brand-tint);
        }

        .card-body { flex: 1 1 auto; min-width: 0; padding: 6px 4px; overflow: hidden; }

        .card-title { margin: 0; font-size: 16px; font-weight: 600; }

        .card-desc {
            margin: 4px 0 0;
            font-size: 14px;
            color: var(--muted);
            overflow-wrap: anywhere;
        }

        .card-meta { display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap; }

        .price { font-size: 16px; font-weight: 700; color: var(--brand-primary); }

        .price--old { font-size: 14px; font-weight: 400; color: var(--muted); text-decoration: line-through; }

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
            width: 84px;
            flex: 0 0 auto;
            align-content: start;
        }

        .combo-thumbs img,
        .combo-thumb-placeholder {
            width: 38px;
            height: 38px;
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
                <img class="brand-logo" src="{{ $settings->logo_url }}" alt="{{ $settings->restaurant_name }}">
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
                        <img class="card-image" src="{{ $offer->image_url }}" alt="{{ $offer->title ?: 'كومبو خاص' }}">
                    @elseif ($offer->items->contains(fn ($item): bool => filled($item->image_url)))
                        <div class="combo-thumbs">
                            @foreach ($offer->items as $item)
                                @if ($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->title }}">
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
                        <img class="card-image" src="{{ $item->image_url }}" alt="{{ $item->title }}">
                    @endif

                    <div class="card-body">
                        <p class="card-title">{{ $item->title }}</p>

                        @if (filled($item->description))
                            <p class="card-desc">{{ $item->description }}</p>
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
        var cards = Array.prototype.slice.call(document.querySelectorAll('.card[data-categories]'));
        var sections = Array.prototype.slice.call(document.querySelectorAll('.category-section'));

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                var filter = chip.getAttribute('data-filter');

                chips.forEach(function (other) {
                    other.classList.toggle('is-active', other === chip);
                });

                cards.forEach(function (card) {
                    var categories = (card.getAttribute('data-categories') || '').split(',');
                    var visible = filter === 'all' || categories.indexOf(filter) !== -1;

                    card.classList.toggle('is-hidden', !visible);
                });

                sections.forEach(function (section) {
                    var anyVisible = section.querySelectorAll('.card[data-categories]:not(.is-hidden)').length > 0;

                    section.classList.toggle('is-hidden', !anyVisible);
                });
            });
        });
    })();
</script>
</body>
</html>
