@php
    $primary = $settings?->primary_color ?: '#2D6A4F';
    $secondary = $settings?->secondary_color ?: '#52B788';
    $restaurantName = $settings?->restaurant_name ?: $user->name;
    $seoTitle = $settings?->seo_title ?: $restaurantName;
    $seoDescription = $settings?->seo_description ?: $restaurantName;
    $seoKeywords = $settings?->seo_keywords;

    $heroImageUrl = $settings?->hero_image_url;
    $logoUrl = $settings?->logo_url;
    $phone = $settings?->phone;

    $hasOffers = $offers->isNotEmpty();
    $hasBranches = $branches !== [];
    $hasSocial = $socialLinks !== [];

    $maxPrice = (int) $categories
        ->flatMap(fn ($category) => $category->items)
        ->max(fn ($item) => (float) $item->price);

    $menuUrl = route('menu.show', $user->domain ?: $user->getKey());
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    @if (filled($seoKeywords))
        <meta name="keywords" content="{{ $seoKeywords }}">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    @if (filled($heroImageUrl))
        <meta property="og:image" content="{{ $heroImageUrl }}">
    @endif
    <meta property="og:locale" content="ar_EG">

    @if (filled($logoUrl))
        <link rel="icon" href="{{ $logoUrl }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ urlencode($font) }}:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: {{ $primary }};
            --secondary: {{ $secondary }};
            --text: #1A1A2E;
            --muted: #5b6472;
            --bg: #F8F9FA;
            --card-bg: #FFFFFF;
            --border: #e4e7ec;
            --radius: 12px;
            --shadow: 0 1px 2px rgb(26 26 46 / .05), 0 8px 24px rgb(26 26 46 / .06);
            --section-pad: 44px;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: '{{ $font }}', system-ui, -apple-system, 'Segoe UI', sans-serif;
            line-height: 1.6;
        }

        img { max-width: 100%; height: auto; display: block; }

        a { color: inherit; text-decoration: none; }

        .container { width: 100%; max-width: 1080px; margin-inline: auto; padding-inline: 18px; }

        .section { padding-block: var(--section-pad); }
        .section--alt { background: var(--card-bg); }

        .section-title { margin: 0 0 6px; font-size: clamp(1.4rem, 5vw, 2rem); line-height: 1.25; }
        .section-lead { margin: 0 0 26px; color: var(--muted); }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 22px; border: 1px solid transparent; border-radius: var(--radius);
            font: inherit; font-weight: 700; cursor: pointer;
            transition: transform .15s ease, background-color .15s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn--primary { background: var(--primary); color: #fff; }
        .btn--ghost { background: transparent; border-color: var(--primary); color: var(--primary); }
        .btn--light { background: #fff; color: var(--primary); }

        /* Ticker */
        .ticker {
            background: var(--primary); color: #fff; overflow: hidden; padding-block: 10px;
        }
        .ticker__track {
            display: flex; gap: 40px; width: max-content;
            animation: ticker-scroll 28s linear infinite;
        }
        .ticker:hover .ticker__track { animation-play-state: paused; }
        .ticker__item { font-size: .9375rem; font-weight: 600; white-space: nowrap; }
        .ticker__item strong { color: var(--secondary); }
        @keyframes ticker-scroll { from { transform: translateX(0); } to { transform: translateX(50%); } }

        /* Header */
        .site-header {
            position: sticky; top: 0; z-index: 20;
            background: rgb(255 255 255 / .95); backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--border);
        }
        .site-header__inner { display: flex; align-items: center; justify-content: space-between; gap: 14px; min-height: 64px; }

        .brand { display: inline-flex; align-items: center; gap: 10px; font-weight: 800; }
        .brand__logo { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; background: var(--primary); }
        .brand__placeholder {
            display: grid; place-items: center; width: 40px; height: 40px;
            border-radius: 10px; background: var(--primary); color: #fff; font-size: .8125rem; font-weight: 800;
        }

        .site-nav { display: none; }
        .site-nav__link { font-size: .9375rem; font-weight: 600; color: var(--muted); }
        .site-nav__link:hover { color: var(--primary); }
        .header-cta { display: none; }

        /* Hero */
        .hero {
            position: relative; padding-block: clamp(56px, 12vw, 104px);
            background: var(--primary) center / cover no-repeat;
            color: #fff; text-align: center; isolation: isolate;
        }
        /* The overlay is what keeps the text readable on a light photo. */
        .hero::before { content: ''; position: absolute; inset: 0; z-index: -1; background: rgb(0 0 0 / .55); }
        .hero__title { margin: 0 0 12px; font-size: clamp(1.75rem, 7vw, 3rem); line-height: 1.15; }
        .hero__subtitle { max-width: 36rem; margin: 0 auto 26px; font-size: 1.0625rem; opacity: .92; }
        .hero__actions { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }

        /* Grids */
        .grid { display: grid; gap: 16px; grid-template-columns: 1fr; }

        .card {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);
            display: flex; flex-direction: column;
        }
        .card__media { position: relative; aspect-ratio: 4 / 3; background: var(--bg); }
        .card__media img { width: 100%; height: 100%; object-fit: cover; }
        .card__placeholder {
            display: grid; place-items: center; width: 100%; height: 100%;
            color: var(--muted); font-size: 2rem;
        }
        .card__body { padding: 16px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
        .card__title { margin: 0; font-size: 1.0625rem; }
        .card__desc { margin: 0; color: var(--muted); font-size: .875rem; }
        .card__foot { margin-top: auto; display: flex; align-items: center; justify-content: space-between; gap: 10px; }

        .price { font-weight: 800; color: var(--primary); }
        .price--old { color: var(--muted); font-weight: 500; text-decoration: line-through; font-size: .875rem; }

        .badge {
            position: absolute; top: 10px; inset-inline-start: 10px; z-index: 1;
            padding: 4px 10px; border-radius: 999px; font-size: .75rem; font-weight: 700; color: #fff;
        }
        .badge--offer { background: #d92d20; }
        .badge--featured { background: #f79009; }
        .badge--discount {
            position: static; display: inline-block; background: var(--secondary); color: #0b3d2c;
        }

        /* Filters */
        .filters { display: grid; gap: 14px; margin-bottom: 24px; }
        .filter-row { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; }

        .chip {
            flex: none; padding: 8px 16px; border: 1px solid var(--border); border-radius: 999px;
            background: var(--card-bg); font: inherit; font-size: .875rem; font-weight: 600;
            color: var(--muted); cursor: pointer; white-space: nowrap;
        }
        .chip[aria-pressed="true"] { background: var(--primary); border-color: var(--primary); color: #fff; }

        .field { display: flex; flex-direction: column; gap: 6px; }
        .field__label { font-size: .8125rem; font-weight: 700; color: var(--muted); }
        .input {
            padding: 11px 14px; border: 1px solid var(--border); border-radius: var(--radius);
            background: var(--card-bg); font: inherit; font-size: .9375rem; width: 100%;
        }
        .price-range { display: flex; align-items: center; gap: 12px; }
        .price-range output { font-weight: 800; color: var(--primary); min-width: 4rem; text-align: center; }
        input[type="range"] { flex: 1; accent-color: var(--primary); }

        .empty { color: var(--muted); text-align: center; padding: 26px 0; }
        .is-hidden { display: none !important; }

        /* Branches */
        .branch__name { margin: 0 0 4px; font-size: 1.0625rem; }
        .branch__address { margin: 0 0 10px; color: var(--muted); font-size: .9375rem; }
        .branch__phone { font-weight: 700; color: var(--primary); direction: ltr; display: inline-block; }

        /* Footer */
        .site-footer { background: #101827; color: #cbd5e1; padding-block: 34px; }
        .site-footer__inner { display: flex; flex-direction: column; gap: 18px; align-items: center; text-align: center; }
        .site-footer__brand { display: inline-flex; align-items: center; gap: 10px; color: #fff; font-weight: 800; }
        .site-footer__social { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
        .social-link {
            display: grid; place-items: center; width: 40px; height: 40px; border-radius: 50%;
            background: rgb(255 255 255 / .1); font-size: 1.125rem;
        }
        .site-footer__meta { display: flex; flex-direction: column; gap: 6px; font-size: .875rem; }
        .site-footer__meta a { color: var(--secondary); font-weight: 700; }

        @media (min-width: 640px) {
            :root { --section-pad: 60px; }
            .grid { grid-template-columns: repeat(2, 1fr); }
            .filters { grid-template-columns: 1fr; }
            .site-footer__inner { text-align: start; align-items: flex-start; }
        }

        @media (min-width: 900px) {
            .grid { grid-template-columns: repeat(3, 1fr); }
            .site-nav { display: flex; align-items: center; gap: 20px; }
            .header-cta { display: inline-flex; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .ticker__track { animation: none; }
            .btn, .btn:hover { transition: none; transform: none; }
        }
    </style>
</head>
<body>
    @if ($showOffersTicker && $hasOffers)
        <div class="ticker" aria-label="أحدث العروض">
            <div class="ticker__track">
                {{-- Printed twice so the scroll loop has no visible seam. --}}
                @for ($pass = 0; $pass < 2; $pass++)
                    @foreach ($offers as $offer)
                        <span class="ticker__item">
                            {{ $offer->title ?? $offer->item?->title }} —
                            <strong>{{ number_format((float) $offer->offer_price, 2) }} {{ $currency }}</strong>
                        </span>
                    @endforeach
                @endfor
            </div>
        </div>
    @endif

    <header class="site-header">
        <div class="container site-header__inner">
            <a class="brand" href="#home">
                @if (filled($logoUrl))
                    <img class="brand__logo" src="{{ $logoUrl }}" alt="{{ $restaurantName }}">
                @else
                    <span class="brand__placeholder" aria-hidden="true">{{ \Illuminate\Support\Str::of($restaurantName)->substr(0, 2) }}</span>
                @endif
                <span>{{ $restaurantName }}</span>
            </a>

            <nav class="site-nav" aria-label="التنقل الرئيسي">
                <a class="site-nav__link" href="#home">الرئيسية</a>
                <a class="site-nav__link" href="#products">المنتجات</a>
                @if ($hasOffers)
                    <a class="site-nav__link" href="#offers">العروض</a>
                @endif
                @if ($hasBranches)
                    <a class="site-nav__link" href="#branches">فروعنا</a>
                @endif
                @if (filled($phone))
                    <a class="site-nav__link" href="tel:{{ $phone }}">اتصل بنا</a>
                @endif
            </nav>

            <a class="btn btn--primary header-cta" href="{{ $menuUrl }}">عرض QR Menu</a>
        </div>
    </header>

    <main id="home">
        <section class="hero" @if (filled($heroImageUrl)) style="background-image: url('{{ $heroImageUrl }}');" @endif>
            <div class="container">
                @if (filled($settings?->hero_title))
                    <h1 class="hero__title">{{ $settings->hero_title }}</h1>
                @else
                    <h1 class="hero__title">{{ $restaurantName }}</h1>
                @endif

                @if (filled($settings?->hero_subtitle))
                    <p class="hero__subtitle">{{ $settings->hero_subtitle }}</p>
                @endif

                <div class="hero__actions">
                    <a class="btn btn--light" href="{{ $menuUrl }}">اطلب الآن</a>
                    @if (filled($phone))
                        <a class="btn btn--ghost" style="border-color:#fff;color:#fff;" href="tel:{{ $phone }}">اتصل بنا</a>
                    @endif
                </div>
            </div>
        </section>

        @if ($featured->isNotEmpty())
            <section class="section section--alt" id="featured">
                <div class="container">
                    <h2 class="section-title">أبرز منتجاتنا</h2>
                    <p class="section-lead">اختيارات المطعم المفضلة.</p>

                    <div class="grid">
                        @foreach ($featured as $item)
                            @php($offer = $offersByItem->get($item->getKey()))
                            <article class="card">
                                <div class="card__media">
                                    @if (filled($item->image_url))
                                        <img src="{{ $item->image_url }}" alt="{{ $item->title }}">
                                    @else
                                        <div class="card__placeholder" aria-hidden="true">🍽️</div>
                                    @endif
                                    <span class="badge badge--featured">مميز</span>
                                </div>
                                <div class="card__body">
                                    <h3 class="card__title">{{ $item->title }}</h3>
                                    <div class="card__foot">
                                        @if ($offer)
                                            <span class="price">{{ number_format((float) $offer->offer_price, 2) }} {{ $currency }}</span>
                                            <span class="price--old">{{ number_format((float) $item->price, 2) }} {{ $currency }}</span>
                                        @else
                                            <span class="price">{{ number_format((float) $item->price, 2) }} {{ $currency }}</span>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="section" id="products">
            <div class="container">
                <h2 class="section-title">قائمتنا</h2>
                <p class="section-lead">تصفح الأقسام أو ابحث عن صنف.</p>

                <div class="filters">
                    <div class="filter-row" role="group" aria-label="تصفية حسب التصنيف">
                        <button type="button" class="chip" data-category-filter="all" aria-pressed="true">الكل</button>
                        @foreach ($categories as $category)
                            <button type="button" class="chip" data-category-filter="{{ $category->getKey() }}" aria-pressed="false">
                                {{ $category->name }}
                            </button>
                        @endforeach
                    </div>

                    <div class="field">
                        <label class="field__label" for="item-search">ابحث عن صنف</label>
                        <input class="input" type="search" id="item-search" placeholder="اكتب اسم الصنف...">
                    </div>

                    <div class="field">
                        <label class="field__label" for="price-filter">أقصى سعر: <output id="price-output">{{ number_format($maxPrice, 2) }} {{ $currency }}</output></label>
                        <div class="price-range">
                            <input type="range" id="price-filter" min="0" max="{{ max($maxPrice, 1) }}" step="1" value="{{ max($maxPrice, 1) }}">
                        </div>
                    </div>
                </div>

                @forelse ($categories as $category)
                    <div class="menu-group" data-category="{{ $category->getKey() }}" id="group-{{ $category->getKey() }}">
                        <h3 class="section-title" style="font-size:1.2rem;">{{ $category->name }}</h3>

                        <div class="grid">
                            @foreach ($category->items as $item)
                                @php($offer = $offersByItem->get($item->getKey()))
                                <article class="card item"
                                    data-item-id="{{ $item->getKey() }}"
                                    data-name="{{ $item->title }}"
                                    data-price="{{ (float) $item->price }}">
                                    <div class="card__media">
                                        @if (filled($item->image_url))
                                            <img src="{{ $item->image_url }}" alt="{{ $item->title }}">
                                        @else
                                            <div class="card__placeholder" aria-hidden="true">🍽️</div>
                                        @endif

                                        @if ($offer)
                                            <span class="badge badge--offer">عرض</span>
                                        @endif

                                        @if ($item->is_featured)
                                            <span class="badge badge--featured" style="inset-inline-start:auto;inset-inline-end:10px;">⭐ مميز</span>
                                        @endif
                                    </div>
                                    <div class="card__body">
                                        <h4 class="card__title">{{ $item->title }}</h4>
                                        @if (filled($item->description))
                                            <p class="card__desc">{{ \Illuminate\Support\Str::limit($item->description, 70) }}</p>
                                        @endif
                                        <div class="card__foot">
                                            @if ($offer)
                                                <span class="price">{{ number_format((float) $offer->offer_price, 2) }} {{ $currency }}</span>
                                                <span class="price--old">{{ number_format((float) $item->price, 2) }} {{ $currency }}</span>
                                            @else
                                                <span class="price">{{ number_format((float) $item->price, 2) }} {{ $currency }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="empty">لا توجد أصناف متاحة حالياً.</p>
                @endforelse

                <p class="empty is-hidden" id="no-results">لا توجد نتائج مطابقة.</p>
            </div>
        </section>

        @if ($hasOffers)
            <section class="section section--alt" id="offers">
                <div class="container">
                    <h2 class="section-title">عروض اليوم</h2>
                    <p class="section-lead">عروض محدودة المدة على أصناف مختارة.</p>

                    <div class="grid">
                        @foreach ($offers as $offer)
                            <article class="card">
                                <div class="card__media">
                                    @if (filled($offer->item?->image_url))
                                        <img src="{{ $offer->item->image_url }}" alt="{{ $offer->item->title }}">
                                    @else
                                        <div class="card__placeholder" aria-hidden="true">🏷️</div>
                                    @endif
                                    @if ($offer->discountPercentage() !== null)
                                        <span class="badge badge--offer">خصم {{ $offer->discountPercentage() }}%</span>
                                    @endif
                                </div>
                                <div class="card__body">
                                    <h3 class="card__title">{{ $offer->item?->title ?? 'صنف' }}</h3>
                                    @if (filled($offer->title))
                                        <p class="card__desc">{{ $offer->title }}</p>
                                    @endif
                                    <div class="card__foot">
                                        <span class="price">{{ number_format((float) $offer->offer_price, 2) }} {{ $currency }}</span>
                                        <span class="price--old">{{ number_format((float) ($offer->item?->price ?? 0), 2) }} {{ $currency }}</span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($hasBranches)
            <section class="section" id="branches">
                <div class="container">
                    <h2 class="section-title">فروعنا</h2>
                    <p class="section-lead">العناوين وأرقام التواصل.</p>

                    <div class="grid">
                        @foreach ($branches as $branch)
                            <article class="card">
                                <div class="card__body">
                                    <h3 class="branch__name">{{ $branch['branch_name'] ?? '' }}</h3>
                                    @if (filled($branch['address'] ?? null))
                                        <p class="branch__address">{{ $branch['address'] }}</p>
                                    @endif
                                    @if (filled($branch['phone'] ?? null))
                                        <a class="branch__phone" href="tel:{{ $branch['phone'] }}">{{ $branch['phone'] }}</a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </main>

    <footer class="site-footer">
        <div class="container site-footer__inner">
            <div class="site-footer__brand">
                @if (filled($logoUrl))
                    <img class="brand__logo" src="{{ $logoUrl }}" alt="{{ $restaurantName }}">
                @endif
                <span>{{ $restaurantName }}</span>
            </div>

            @if ($hasSocial)
                <div class="site-footer__social">
                    @foreach ($socialLinks as $network => $url)
                        <a class="social-link" href="{{ $url }}" rel="noopener" target="_blank" aria-label="{{ $network }}">
                            @switch($network)
                                @case('facebook') f
                                @case('instagram') ig
                                @case('whatsapp') wa
                                @default {{ \Illuminate\Support\Str::of($network)->substr(0, 2) }}
                            @endswitch
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="site-footer__meta">
                <span>جميع الحقوق محفوظة {{ now()->year }} — {{ $restaurantName }}</span>
                <span>Powered by <a href="{{ route('landing') }}">QR Menu</a></span>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            'use strict';

            var groups = Array.prototype.slice.call(document.querySelectorAll('[data-category]'));
            var items = Array.prototype.slice.call(document.querySelectorAll('.item'));
            var chips = Array.prototype.slice.call(document.querySelectorAll('[data-category-filter]'));
            var search = document.getElementById('item-search');
            var price = document.getElementById('price-filter');
            var priceOutput = document.getElementById('price-output');
            var noResults = document.getElementById('no-results');

            var activeCategory = 'all';
            var term = '';
            var maxPrice = price ? parseFloat(price.value) : Infinity;

            /**
             * Shows only the items matching the category, the search term and the
             * price cap, then hides any category left with nothing in it.
             */
            function apply() {
                var visible = 0;

                groups.forEach(function (group) {
                    var shown = 0;

                    items.forEach(function (item) {
                        if (!group.contains(item)) {
                            return;
                        }

                        var name = (item.dataset.name || '').toLowerCase();
                        var itemPrice = parseFloat(item.dataset.price || '0');

                        var matches =
                            (activeCategory === 'all' || item.closest('[data-category]').dataset.category === activeCategory) &&
                            name.indexOf(term) !== -1 &&
                            itemPrice <= maxPrice;

                        item.classList.toggle('is-hidden', !matches);

                        if (matches) {
                            shown++;
                            visible++;
                        }
                    });

                    group.classList.toggle('is-hidden', shown === 0);
                });

                if (noResults) {
                    noResults.classList.toggle('is-hidden', visible > 0);
                }
            }

            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    activeCategory = chip.dataset.categoryFilter;

                    chips.forEach(function (other) {
                        other.setAttribute('aria-pressed', String(other === chip));
                    });

                    apply();
                });
            });

            if (search) {
                search.addEventListener('input', function () {
                    term = search.value.trim().toLowerCase();
                    apply();
                });
            }

            if (price && priceOutput) {
                price.addEventListener('input', function () {
                    maxPrice = parseFloat(price.value);
                    priceOutput.textContent = maxPrice.toFixed(2);
                    apply();
                });
            }

            // Anchor links scroll natively; this keeps the sticky header from
            // covering the heading that was jumped to.
            document.querySelectorAll('a[href^="#"]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    var target = document.querySelector(link.getAttribute('href'));

                    if (!target) {
                        return;
                    }

                    event.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
        })();
    </script>
</body>
</html>