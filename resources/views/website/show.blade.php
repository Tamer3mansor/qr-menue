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
    $hasComboOffers = $comboOffers->isNotEmpty();
    $hasBranches = $branches !== [];
    $hasSocial = $socialLinks !== [];

    $maxPrice = (int) $categories
        ->flatMap(fn ($category) => $category->items)
        ->max(fn ($item) => (float) $item->price);

    $menuUrl = route('menu.show', $user->domain ?: $user->getKey());

    $isOpenNow = $settings?->isOpenNow();
    $todayHours = $settings?->todayWorkingHours();
    $enabledPaymentMethods = $settings?->enabledPaymentMethods() ?? [];
    $googleRating = $settings?->google_rating;
    $googleReviewCount = $settings?->google_review_count;
    $formatTime = static fn (?string $time): ?string => \App\Models\Setting::formatTimeOfDay($time);

    $paymentIcons = [
        'cash' => '💵',
        'visa' => '💳',
        'vodafone_cash' => '📱',
        'instapay' => '🏦',
        'meeza' => '💳',
    ];
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
    <link href="https://fonts.googleapis.com/css2?family={{ urlencode($font) }}:wght@{{ $fontWeights }}&display=swap" rel="stylesheet">

    <script>document.documentElement.classList.add('js');</script>

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
            position: sticky; top: 0; z-index: 1000;
            background: transparent;
            border-bottom: 1px solid transparent;
            transition: all .3s ease;
        }
        .site-header.is-scrolled {
            background: #fff;
            box-shadow: 0 2px 20px rgb(0 0 0 / .08);
            border-bottom-color: var(--border);
        }
        .site-header__inner { display: flex; align-items: center; justify-content: space-between; gap: 14px; min-height: 64px; }

        .brand { display: inline-flex; align-items: center; gap: 10px; font-weight: 800; }
        .brand__logo { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; background: var(--primary); }
        .brand__placeholder {
            display: grid; place-items: center; width: 40px; height: 40px;
            border-radius: 10px; background: var(--primary); color: #fff; font-size: .8125rem; font-weight: 800;
        }

        /* Mobile drawer: hidden off-canvas until the hamburger is tapped. */
        .site-nav {
            position: fixed; top: 0; right: 0; z-index: 999;
            display: flex; flex-direction: column; align-items: flex-start; gap: 4px;
            width: min(75vw, 340px); height: 100vh;
            padding: 76px 24px 24px;
            background: #fff;
            box-shadow: -4px 0 20px rgb(0 0 0 / .18);
            transform: translateX(100%);
            visibility: hidden;
            transition: transform .3s ease, visibility .3s ease;
        }
        .site-nav.open { transform: translateX(0); visibility: visible; }
        .site-nav__link {
            display: flex; align-items: center; width: 100%; min-height: 44px;
            font-size: 1rem; font-weight: 600; color: var(--muted);
        }
        .site-nav__link:hover { color: var(--primary); }
        .header-cta { display: none; }

        .hamburger {
            display: inline-flex; align-items: center; justify-content: center;
            width: 44px; height: 44px; padding: 0;
            border: 1px solid var(--border); border-radius: 10px;
            background: #fff; color: var(--text);
            font-size: 1.35rem; line-height: 1; cursor: pointer;
        }

        .nav-overlay {
            position: fixed; inset: 0; z-index: 998;
            background: rgb(0 0 0 / .4);
            opacity: 0; visibility: hidden;
            transition: opacity .3s ease, visibility .3s ease;
        }
        .nav-overlay.open { opacity: 1; visibility: visible; }

        /* Hero */
        .hero {
            position: relative; padding-block: clamp(56px, 12vw, 104px);
            background: var(--primary) center / cover no-repeat;
            color: #fff; text-align: center; isolation: isolate;
        }
        /* The overlay is what keeps the text readable on a light photo. */
        .hero::before {
            content: ''; position: absolute; inset: 0; z-index: -1;
            background: linear-gradient(to bottom, rgb(0 0 0 / .3) 0%, rgb(0 0 0 / .6) 100%);
        }
        .hero__title { margin: 0 0 12px; font-size: clamp(1.75rem, 7vw, 3rem); line-height: 1.15; text-shadow: 0 2px 8px rgb(0 0 0 / .5); }
        .hero__subtitle { max-width: 36rem; margin: 0 auto 26px; font-size: 1.0625rem; opacity: .92; text-shadow: 0 2px 8px rgb(0 0 0 / .5); }
        .hero__actions { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }

        /* Grids */
        .grid { display: grid; gap: 16px; grid-template-columns: 1fr; }

        .card {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);
            display: flex; flex-direction: column;
            max-height: 300px;
        }
        .card__media { position: relative; height: 180px; background: var(--bg); }
        .card__media img { width: 100%; height: 180px; object-fit: cover; }
        .card__placeholder {
            display: grid; place-items: center; width: 100%; height: 100%;
            color: var(--muted); font-size: 2rem;
        }
        .card__media img.item-fallback-logo,
        .card--featured .card__media img.item-fallback-logo {
            object-fit: contain;
            padding: 12px;
            background: #f8f8f8;
        }
        .item-no-image {
            display: grid; place-items: center; width: 100%; height: 100%;
            background: var(--primary); color: #fff;
            font-size: 2.5rem; font-weight: 700;
        }
        .card__body { padding: 16px; display: flex; flex-direction: column; gap: 6px; flex: 1; overflow: hidden; }
        .card__title { margin: 0; font-size: .9375rem; font-weight: 700; }
        .card__desc { margin: 0; color: var(--muted); font-size: .8125rem; }
        .card__foot { margin-top: auto; display: flex; align-items: center; justify-content: space-between; gap: 10px; }

        .price { font-weight: 800; color: var(--primary); font-size: .875rem; }
        .price--old { color: var(--muted); font-weight: 500; text-decoration: line-through; font-size: .8125rem; }

        /* Featured cards (Instagram style) */
        .card--featured { max-height: none; }
        .card--featured .card__media { height: auto; aspect-ratio: 4 / 3; }
        .card--featured .card__media img { position: absolute; inset: 0; width: 100%; height: 100%; }
        .card__overlay {
            position: absolute; inset-inline: 0; bottom: 0;
            padding: 12px; color: #fff;
            background: linear-gradient(to top, rgb(0 0 0 / .75), transparent);
        }
        .card__overlay-title { margin: 0; font-size: 1rem; font-weight: 700; }
        .card__overlay-price { margin: 2px 0 0; font-size: .875rem; font-weight: 700; }

        /* Scroll reveal */
        .js [data-reveal] {
            opacity: 0; transform: translateY(20px);
            transition: opacity .4s ease-out, transform .4s ease-out;
        }
        .js [data-reveal].is-visible { opacity: 1; transform: none; }

        @media (min-width: 480px) {
            .card__title { font-size: 1.0625rem; }
            .card__desc { font-size: .875rem; }
        }

        .badge {
            position: absolute; top: 10px; inset-inline-start: 10px; z-index: 1;
            padding: 4px 10px; border-radius: 999px; font-size: .75rem; font-weight: 700; color: #fff;
        }
        .badge--offer { background: #d92d20; }
        .badge--featured { background: var(--primary); }
        .badge--discount {
            position: static; display: inline-block; background: var(--secondary); color: #0b3d2c;
        }

        /* Combo offers */
        .combo-thumbs {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            padding: 8px;
            width: 100%;
            height: 100%;
        }
        .combo-thumbs img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
            background: var(--bg);
        }
        .combo-thumb-placeholder {
            display: grid;
            place-items: center;
            background: var(--bg);
            border-radius: 8px;
            color: var(--muted);
            font-size: 1.25rem;
        }
        .combo-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .combo-list li {
            padding: 3px 10px;
            border-radius: 999px;
            background: var(--bg);
            color: var(--muted);
            font-size: .8125rem;
            font-weight: 600;
        }

        /* Filters */
        .filters { display: grid; gap: 14px; margin-bottom: 24px; }
        .filter-row {
            display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px;
            white-space: nowrap; scrollbar-width: none; -webkit-overflow-scrolling: touch;
        }
        .filter-row::-webkit-scrollbar { display: none; }

        .chip {
            flex: none; display: inline-flex; align-items: center; justify-content: center;
            min-height: 44px; min-width: 44px;
            padding: 8px 16px; border: 1px solid var(--border); border-radius: 999px;
            background: var(--card-bg); font: inherit; font-size: .875rem; font-weight: 600;
            color: var(--muted); cursor: pointer; white-space: nowrap;
            transition: all .2s ease;
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
            display: grid; place-items: center; width: 44px; height: 44px; border-radius: 50%;
            background: rgb(255 255 255 / .1); font-size: 1.25rem; line-height: 1;
        }
        .site-footer__meta { display: flex; flex-direction: column; gap: 6px; font-size: .875rem; }
        .site-footer__meta a { color: var(--secondary); font-weight: 700; }
        .site-footer__payments { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
        .footer-payment {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px; border-radius: 999px;
            background: rgb(255 255 255 / .1);
            font-size: .8125rem; font-weight: 600;
        }

        /* Quick info */
        .quick-info__grid { display: grid; gap: 12px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .quick-card {
            display: flex; flex-direction: column; gap: 8px;
            padding: 16px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .quick-card__title {
            display: flex; align-items: center; gap: 6px;
            margin: 0; font-size: .8125rem; font-weight: 700; color: var(--muted);
        }
        .quick-card__value { margin: 0; font-size: .9375rem; font-weight: 700; }
        .quick-card__note { margin: 0; font-size: .8125rem; color: var(--muted); }
        .quick-card__chips { display: flex; flex-wrap: wrap; gap: 6px; margin: 0; padding: 0; list-style: none; }
        .quick-card__chips li {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 9px; border-radius: 999px;
            background: var(--bg); color: var(--text);
            font-size: .75rem; font-weight: 600;
        }
        .quick-card__link { margin-top: auto; color: var(--primary); font-size: .8125rem; font-weight: 700; }

        .status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            width: fit-content; padding: 4px 10px; border-radius: 999px;
            font-size: .75rem; font-weight: 700;
        }
        .status-badge--open { background: #e8f7ee; color: #17663a; }
        .status-badge--closed { background: #fdecec; color: #a61b1b; }
        .google-stars { letter-spacing: 1px; color: #f5a623; }
        .google-rating__count { color: var(--muted); font-size: .8125rem; font-weight: 500; }

        @media (min-width: 640px) {
            :root { --section-pad: 60px; }
            .grid { grid-template-columns: repeat(2, 1fr); }
            .filters { grid-template-columns: 1fr; }
            .site-footer__inner { text-align: start; align-items: flex-start; }
            .site-footer__payments { justify-content: flex-start; }
        }

        @media (min-width: 769px) {
            .site-nav {
                position: static; width: auto; height: auto;
                flex-direction: row; align-items: center; gap: 20px;
                padding: 0; background: transparent; box-shadow: none;
                transform: none; visibility: visible; transition: none;
            }
            .site-nav__link { width: auto; min-height: 0; font-size: .9375rem; }
            .hamburger, .nav-overlay { display: none; }
        }

        @media (min-width: 900px) {
            .grid { grid-template-columns: repeat(3, 1fr); }
            .quick-info__grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .header-cta { display: inline-flex; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
            .btn:hover { transform: none; }
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
                            {{ $offer->title ?? $offer->items->pluck('title')->join('، ') }} —
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
                    <img class="brand__logo" src="{{ $logoUrl }}" alt="{{ $restaurantName }}" loading="lazy">
                @else
                    <span class="brand__placeholder" aria-hidden="true">{{ \Illuminate\Support\Str::of($restaurantName)->substr(0, 2) }}</span>
                @endif
                <span>{{ $restaurantName }}</span>
            </a>

            <nav class="site-nav nav-links" id="site-nav" aria-label="التنقل الرئيسي">
                <a class="site-nav__link" href="#home">الرئيسية</a>
                <a class="site-nav__link" href="#products">المنتجات</a>
                @if ($hasComboOffers)
                    <a class="site-nav__link" href="#offers">العروض</a>
                @endif
                @if ($hasBranches)
                    <a class="site-nav__link" href="#branches">فروعنا</a>
                @endif
            </nav>

            <a class="btn btn--primary header-cta" href="{{ $menuUrl }}">عرض QR Menu</a>

            <button
                class="hamburger"
                type="button"
                onclick="toggleNav()"
                aria-label="فتح القائمة"
                aria-controls="site-nav"
                aria-expanded="false"
            >☰</button>
        </div>
    </header>

    <div class="nav-overlay" onclick="toggleNav()" aria-hidden="true"></div>

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
                    <a class="btn btn--primary hero__cta" href="{{ filled($phone) ? 'https://wa.me/'.$phone : $menuUrl }}">اطلب الآن</a>
                </div>
            </div>
        </section>

        <section class="section" id="why-us">
            <div class="container">
                <h2 class="section-title">لماذا نحن؟</h2>
                <p class="section-lead">كل ما تحتاج معرفته قبل زيارتك.</p>

                <div class="quick-info__grid" data-reveal-group data-reveal-stagger="80">
                    <article class="quick-card" data-reveal>
                        <h3 class="quick-card__title">🕒 ساعات العمل</h3>

                        @if ($isOpenNow !== null)
                            @if ($isOpenNow)
                                <span class="status-badge status-badge--open">مفتوح الآن 🟢</span>
                            @else
                                <span class="status-badge status-badge--closed">مغلق حالياً 🔴</span>
                            @endif
                        @endif

                        @if ($todayHours)
                            <p class="quick-card__value">
                                @if ($todayHours['closed'])
                                    مغلق اليوم
                                @else
                                    {{ $formatTime($todayHours['open']) }} - {{ $formatTime($todayHours['close']) }}
                                @endif
                            </p>
                            <p class="quick-card__note">{{ $todayHours['label'] }}</p>
                        @else
                            <p class="quick-card__value">غير محددة</p>
                        @endif
                    </article>

                    @if (filled($googleRating))
                        <article class="quick-card" data-reveal>
                            <h3 class="quick-card__title">⭐ تقييم Google</h3>
                            <p class="quick-card__value">
                                {{ number_format((float) $googleRating, 1) }}
                                <span class="google-stars" aria-hidden="true">★★★★★</span>
                            </p>
                            @if (filled($googleReviewCount))
                                <p class="quick-card__note">{{ number_format((int) $googleReviewCount) }} تقييم</p>
                            @endif
                            @if (filled($settings?->google_maps_url))
                                <a class="quick-card__link" href="{{ $settings->google_maps_url }}" target="_blank" rel="noopener">
                                    اقرأ التقييمات على Google
                                </a>
                            @endif
                        </article>
                    @endif

                    @if ($enabledPaymentMethods !== [])
                        <article class="quick-card" data-reveal>
                            <h3 class="quick-card__title">💳 طرق الدفع</h3>
                            <ul class="quick-card__chips">
                                @foreach ($enabledPaymentMethods as $method)
                                    <li>
                                        <span aria-hidden="true">{{ $paymentIcons[$method['type']] ?? '💳' }}</span>
                                        {{ $method['label'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </article>
                    @endif

                    @if (filled($phone))
                        <article class="quick-card" data-reveal>
                            <h3 class="quick-card__title">💬 واتساب مباشر</h3>
                            <p class="quick-card__note">اطلب أو استفسر مباشرة عن أي صنف.</p>
                            <a class="btn btn--primary" href="https://wa.me/{{ $phone }}">تواصل على واتساب</a>
                        </article>
                    @endif
                </div>
            </div>
        </section>

        @if ($featured->isNotEmpty())
            <section class="section section--alt" id="featured">
                <div class="container">
                    <h2 class="section-title">أبرز منتجاتنا</h2>

                    <div class="grid" data-reveal-group data-reveal-stagger="100">
                        @foreach ($featured as $item)
                            @php
                                $offer = $offersByItem->get($item->getKey());
                            @endphp
                            <article class="card card--featured" data-reveal>
                                <div class="card__media">
                                    @if (filled($item->image_url))
                                        <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy">
                                    @elseif (filled($logoUrl))
                                        <img class="item-fallback-logo" src="{{ $logoUrl }}" alt="{{ $restaurantName }}" loading="lazy">
                                    @else
                                        <div class="item-no-image" aria-hidden="true">{{ mb_substr($item->title, 0, 1) }}</div>
                                    @endif
                                    <span class="badge badge--featured">مميز</span>
                                    <div class="card__overlay">
                                        <h3 class="card__overlay-title">{{ $item->title }}</h3>
                                        <p class="card__overlay-price">
                                            @if ($offer)
                                                {{ number_format((float) $offer->offer_price, 2) }} {{ $currency }}
                                            @else
                                                {{ number_format((float) $item->price, 2) }} {{ $currency }}
                                            @endif
                                        </p>
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
                                @php
                                    $offer = $offersByItem->get($item->getKey());
                                @endphp
                                <article class="card item"
                                    data-item-id="{{ $item->getKey() }}"
                                    data-name="{{ $item->title }}"
                                    data-price="{{ (float) $item->price }}">
                                    <div class="card__media">
                                        @if (filled($item->image_url))
                                            <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy">
                                        @elseif (filled($logoUrl))
                                            <img class="item-fallback-logo" src="{{ $logoUrl }}" alt="{{ $restaurantName }}" loading="lazy">
                                        @else
                                            <div class="item-no-image" aria-hidden="true">{{ mb_substr($item->title, 0, 1) }}</div>
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

        @if ($hasComboOffers)
            <section class="section section--alt" id="offers">
                <div class="container">
                    <h2 class="section-title">عروض خاصة</h2>
                    <p class="section-lead">كومبوهات بأفضل سعر.</p>

                    <div class="grid" data-reveal-group data-reveal-stagger="80">
                        @foreach ($comboOffers as $offer)
                            <article class="card" data-reveal>
                                <div class="card__media">
                                    @if ($offer->image_url)
                                        <img src="{{ $offer->image_url }}" alt="{{ $offer->title ?: 'كومبو خاص' }}" loading="lazy">
                                    @elseif ($offer->items->contains(fn ($item): bool => filled($item->image_url)))
                                        <div class="combo-thumbs">
                                            @foreach ($offer->items as $item)
                                                @if ($item->image_url)
                                                    <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy">
                                                @else
                                                    <div class="combo-thumb-placeholder">🍽️</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="card__placeholder" aria-hidden="true">🏷️</div>
                                    @endif

                                    @if (($offer->discountPercentage() ?? 0) > 0)
                                        <span class="badge badge--offer">خصم {{ $offer->discountPercentage() }}%</span>
                                    @endif
                                </div>
                                <div class="card__body">
                                    <h3 class="card__title">{{ $offer->title ?: 'كومبو خاص' }}</h3>

                                    <ul class="combo-list">
                                        @foreach ($offer->items as $item)
                                            <li>{{ $item->title }}</li>
                                        @endforeach
                                    </ul>

                                    <div class="card__foot">
                                        <span class="price">{{ number_format((float) $offer->offer_price, 2) }} {{ $currency }}</span>
                                        @if ($offer->totalItemsPrice() > 0)
                                            <span class="price--old">{{ number_format($offer->totalItemsPrice(), 2) }} {{ $currency }}</span>
                                        @endif
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

                    <div class="grid" data-reveal-group data-reveal-stagger="120">
                        @foreach ($branches as $branch)
                            <article class="card" data-reveal>
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
                    <img class="brand__logo" src="{{ $logoUrl }}" alt="{{ $restaurantName }}" loading="lazy">
                @endif
                <span>{{ $restaurantName }}</span>
            </div>

            @if ($hasSocial)
                <div class="site-footer__social">
                    @foreach ($socialLinks as $network => $url)
                        <a class="social-link" href="{{ $url }}" rel="noopener" target="_blank" aria-label="{{ $network }}">
                            @include('partials.social-icon', ['network' => $network])
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($enabledPaymentMethods !== [])
                <div class="site-footer__payments" aria-label="طرق الدفع">
                    @foreach ($enabledPaymentMethods as $method)
                        <span class="footer-payment">
                            <span aria-hidden="true">{{ $paymentIcons[$method['type']] ?? '💳' }}</span>
                            {{ $method['label'] }}
                        </span>
                    @endforeach
                </div>
            @endif

            <div class="site-footer__meta">
                @if (filled($phone))
                    <span><a href="tel:{{ $phone }}">{{ $phone }}</a></span>
                @endif
                <span>جميع الحقوق محفوظة {{ now()->year }} — {{ $restaurantName }}</span>
                <span>Powered by <a href="{{ route('landing') }}">QR Menu</a></span>
            </div>
        </div>
    </footer>

    <script>
        function toggleNav() {
            var nav = document.querySelector('.nav-links');
            var overlay = document.querySelector('.nav-overlay');
            var button = document.querySelector('.hamburger');

            if (!nav || !overlay) {
                return;
            }

            var isOpen = nav.classList.toggle('open');

            overlay.classList.toggle('open', isOpen);
            document.body.style.overflow = isOpen ? 'hidden' : '';

            if (button) {
                button.setAttribute('aria-expanded', String(isOpen));
            }
        }

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

            // The header gains a solid background once the page scrolls past
            // the hero, and returns to transparent back at the top.
            var header = document.querySelector('.site-header');

            if (header) {
                var syncHeader = function () {
                    header.classList.toggle('is-scrolled', window.scrollY > 80);
                };

                syncHeader();
                window.addEventListener('scroll', syncHeader, { passive: true });
            }

            // Reveal each group once as it scrolls into view, staggered per card.
            var revealGroups = Array.prototype.slice.call(document.querySelectorAll('[data-reveal-group]'));
            var revealAll = function () {
                document.querySelectorAll('[data-reveal]').forEach(function (element) {
                    element.classList.add('is-visible');
                });
            };

            if ('IntersectionObserver' in window) {
                revealGroups.forEach(function (group) {
                    var step = parseInt(group.getAttribute('data-reveal-stagger') || '0', 10);
                    var children = Array.prototype.slice.call(group.querySelectorAll('[data-reveal]'));

                    var observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (!entry.isIntersecting) {
                                return;
                            }

                            children.forEach(function (child, index) {
                                child.style.transitionDelay = (index * step) + 'ms';
                                child.classList.add('is-visible');
                            });

                            observer.unobserve(group);
                        });
                    }, { threshold: 0.1 });

                    observer.observe(group);
                });
            } else {
                revealAll();
            }

            // Anchor links scroll natively; this keeps the sticky header from
            // covering the heading that was jumped to.
            document.querySelectorAll('a[href^="#"]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    var target = document.querySelector(link.getAttribute('href'));

                    if (!target) {
                        return;
                    }

                    if (document.querySelector('.nav-links.open')) {
                        toggleNav();
                    }

                    event.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && document.querySelector('.nav-links.open')) {
                    toggleNav();
                }
            });
        })();
    </script>
@if ($settings?->chat_enabled ?? true)
    @include('partials.chat', [
        'chatUrl' => route('site.chat', ['slug' => $user->domain ?: $user->getKey()]),
        'clearUrl' => route('site.chat.clear', ['slug' => $user->domain ?: $user->getKey()]),
    ])
@endif
</body>
</html>