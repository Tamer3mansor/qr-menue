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
    $tenantSlug = $user->domain ?: $user->getKey();

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
    <meta name="csrf-token" content="{{ csrf_token() }}">

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
            max-height: 380px;
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

        /* Cart controls */
        .item-actions {
            display: flex; justify-content: flex-end; align-items: center;
            margin-top: auto; padding-top: 10px;
        }
        .item-actions .qty-controls { display: none; }
        .item-actions.is-in-cart .qty-controls { display: inline-flex; }
        .item-actions.is-in-cart .add-to-cart-btn { display: none; }

        .add-to-cart-btn {
            min-height: 44px; padding: 0 16px;
            border: 0; border-radius: 999px;
            background: var(--primary); color: #fff;
            font: inherit; font-size: .875rem; font-weight: 700;
            cursor: pointer;
        }

        .qty-controls {
            display: inline-flex; align-items: center; gap: 2px;
            border: 1px solid var(--primary); border-radius: 999px; overflow: hidden;
        }

        .qty-btn {
            min-width: 44px; min-height: 44px;
            border: 0; background: var(--primary); color: #fff;
            font: inherit; font-size: 1.25rem; line-height: 1;
            cursor: pointer;
        }

        .qty-value {
            min-width: 40px; text-align: center;
            color: var(--primary); font-weight: 700;
        }

        /* Floating cart button */
        .cart-fab {
            position: fixed; inset-inline-end: 18px; bottom: 20px; z-index: 40;
            display: inline-flex; align-items: center; gap: 8px;
            min-height: 56px; padding: 0 18px;
            border: 0; border-radius: 999px;
            background: var(--primary); color: #fff;
            font: inherit; font-size: 1rem; font-weight: 700;
            box-shadow: 0 10px 30px rgb(0 0 0 / .25); cursor: pointer;
        }
        .cart-fab[hidden] { display: none; }

        /* Cart, order, and success modals */
        .cart-modal,
        .order-modal,
        .success-modal {
            position: fixed; inset: 0; z-index: 1000;
            display: none; padding: 16px; background: rgb(0 0 0 / .5);
        }
        .cart-modal.is-open,
        .order-modal.is-open,
        .success-modal.is-open { display: flex; }
        .cart-modal { align-items: flex-end; justify-content: center; }

        .cart-sheet {
            display: flex; flex-direction: column;
            width: 100%; max-width: 640px; max-height: 88vh;
            overflow: hidden; border-radius: 20px 20px 0 0;
            background: #fff; animation: cart-slide-up .25s ease-out;
        }

        .cart-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 18px 8px; border-bottom: 1px solid var(--border);
        }
        .cart-header h3 { margin: 0; font-size: 1.125rem; }

        .icon-btn {
            min-width: 44px; min-height: 44px;
            border: 0; background: transparent; color: var(--muted);
            font-size: 1.25rem; cursor: pointer;
        }

        .cart-items { flex: 1 1 auto; overflow-y: auto; padding: 6px 18px; }
        .cart-empty { padding: 28px 0; text-align: center; color: var(--muted); }

        .cart-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 0; border-bottom: 1px solid #eef0f3;
        }
        .cart-item:last-child { border-bottom: 0; }

        .cart-item-image {
            display: grid; place-items: center;
            width: 52px; height: 52px; flex: 0 0 auto;
            object-fit: cover; border-radius: 10px;
            background: var(--bg); font-size: 1.25rem;
        }

        .cart-item-body { flex: 1 1 auto; min-width: 0; }
        .cart-item-name { margin: 0; font-size: .875rem; font-weight: 700; }
        .cart-item-price { margin: 2px 0 0; font-size: .8125rem; color: var(--muted); }

        .cart-footer { padding: 12px 18px 18px; border-top: 1px solid var(--border); }

        .cart-total {
            display: flex; justify-content: space-between;
            margin-bottom: 10px; font-size: 1rem; font-weight: 700;
        }

        .checkout-btn {
            width: 100%; min-height: 48px;
            border: 0; border-radius: 12px;
            background: var(--primary); color: #fff;
            font: inherit; font-size: 1rem; font-weight: 700; cursor: pointer;
        }
        .checkout-btn:disabled { opacity: .6; cursor: wait; }

        .order-modal,
        .success-modal { align-items: center; justify-content: center; }

        .modal-card,
        .success-card {
            width: 100%; max-width: 420px; padding: 20px;
            border-radius: 20px; background: #fff;
            animation: modal-pop .2s ease-out;
        }
        .modal-card h3 { margin: 0 0 14px; font-size: 1.125rem; }

        .field { display: block; margin-bottom: 10px; }

        .field input,
        .field textarea,
        .field select,
        .track-form input {
            width: 100%; min-height: 48px; padding: 12px 14px;
            border: 1px solid var(--border); border-radius: 12px;
            background: #fff; font: inherit; font-size: .9375rem;
        }
        .field textarea { min-height: 90px; resize: vertical; }

        .delivery-type-btns { display: flex; gap: 10px; margin: 8px 0; }
        .type-btn {
            flex: 1; min-height: 48px; padding: 12px;
            border: 2px solid #e0e0e0; border-radius: 12px;
            background: #fff; color: var(--text);
            font: inherit; font-size: .875rem; cursor: pointer;
            transition: all .2s ease;
        }
        .type-btn.active {
            border-color: var(--primary);
            background: color-mix(in srgb, var(--primary) 8%, #fff);
            color: var(--primary); font-weight: 700;
        }

        .modal-actions { display: flex; gap: 8px; margin-top: 14px; }
        .modal-actions .checkout-btn { flex: 1 1 auto; }

        .secondary-btn {
            min-height: 48px; padding: 0 16px;
            border: 1px solid var(--border); border-radius: 12px;
            background: #fff; color: var(--text);
            font: inherit; font-weight: 700; cursor: pointer;
        }

        .form-error { margin: 10px 0 0; color: #b91c1c; font-size: .8125rem; }

        .success-card { max-width: 380px; text-align: center; }

        .success-icon {
            display: grid; place-items: center;
            width: 64px; height: 64px; margin: 0 auto 12px;
            border-radius: 50%; background: #e8f7ee; color: #17663a;
            font-size: 2rem; font-weight: 700;
        }
        .success-card h3 { margin: 0 0 8px; }
        .success-card p { margin: 4px 0; font-size: .875rem; }
        .track-hint { margin-top: 12px !important; color: var(--muted); }

        .order-number-row {
            display: flex; align-items: center; justify-content: center;
            gap: 10px; margin: 12px 0;
        }
        .order-number-badge {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 16px; border-radius: 50px;
            background: var(--primary); color: #fff;
            font-size: 1.25rem; font-weight: 900;
        }
        .copy-btn {
            padding: 4px 8px; border: 0; border-radius: 8px;
            background: rgb(255 255 255 / .2); color: inherit;
            font-size: 1rem; cursor: pointer;
        }

        .contact-ways { margin: 14px 0 0; }
        .contact-title { color: var(--muted); font-size: .8125rem !important; }
        .contact-link {
            display: inline-flex; align-items: center; gap: 6px;
            margin: 4px; padding: 8px 14px; border-radius: 999px;
            background: var(--bg); color: var(--primary);
            font-size: .8125rem; font-weight: 700;
        }
        .contact-link.whatsapp { background: #e8f7ee; color: #17663a; }
        .contact-link.social { background: color-mix(in srgb, var(--primary) 10%, #fff); }

        .track-btn {
            width: 100%; min-height: 48px; margin-top: 12px;
            border: 0; border-radius: 12px;
            background: var(--primary); color: #fff;
            font: inherit; font-size: 1rem; font-weight: 700; cursor: pointer;
        }

        /* Track order */
        .track-section {
            margin-top: 28px; padding: 16px;
            border-radius: 16px; background: #fff;
            box-shadow: 0 1px 2px rgb(0 0 0 / .05);
        }
        .track-cta {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px;
            margin-top: 28px; padding: 18px 20px;
            border-radius: var(--radius); border: 1px solid var(--border);
            background: var(--card-bg); box-shadow: var(--shadow);
        }
        .track-cta span { color: var(--muted); font-weight: 600; }
        .track-nav-link { color: var(--primary) !important; }
        .track-form { display: flex; gap: 8px; }
        .track-form input { flex: 1 1 auto; }
        .track-form button {
            min-height: 48px; padding: 0 18px;
            border: 0; border-radius: 12px;
            background: var(--primary); color: #fff;
            font: inherit; font-weight: 700; cursor: pointer;
        }
        .track-result { margin-top: 12px; }
        .track-card { padding: 12px; border: 1px solid var(--border); border-radius: 12px; }
        .track-status {
            display: inline-flex; padding: 4px 10px; border-radius: 999px;
            background: var(--bg); color: var(--primary);
            font-size: .8125rem; font-weight: 700;
        }
        .track-items { margin: 10px 0 0; padding: 0; list-style: none; font-size: .875rem; }
        .track-items li {
            display: flex; justify-content: space-between; gap: 12px;
            padding: 4px 0; border-bottom: 1px dashed #eef0f3;
        }
        .track-items li:last-child { border-bottom: 0; }
        .track-total { display: flex; justify-content: space-between; margin-top: 10px; font-weight: 700; }
        .track-error { color: #b91c1c; font-size: .875rem; }

        .copy-toast {
            position: fixed; left: 50%; bottom: 24px; z-index: 1100;
            padding: 8px 16px; border-radius: 999px;
            background: #111827; color: #fff;
            font-size: .8125rem; font-weight: 600;
            opacity: 0; pointer-events: none;
            transform: translate(-50%, 12px);
            transition: opacity .2s ease, transform .2s ease;
        }
        .copy-toast.is-visible { opacity: 1; transform: translate(-50%, 0); }

        body.modal-open { overflow: hidden; }

        @keyframes cart-slide-up {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }

        @keyframes modal-pop {
            from { opacity: 0; transform: translateY(12px) scale(.98); }
            to { opacity: 1; transform: none; }
        }

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
                <a class="site-nav__link track-nav-link" href="{{ route('site.track', ['slug' => $tenantSlug]) }}">📦 تتبع طلبك</a>
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
                            <article
                                class="card card--featured"
                                data-reveal
                                data-item-id="{{ $item->getKey() }}"
                                data-name="{{ $item->title }}"
                                data-price="{{ (float) $item->price }}"
                            >
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
                                <div class="card__body">
                                    <div
                                        class="item-actions {{ isset($cartItems[$item->getKey()]) ? 'is-in-cart' : '' }}"
                                        data-item-id="{{ $item->getKey() }}"
                                    >
                                        <div class="qty-controls">
                                            <button
                                                type="button"
                                                class="qty-btn"
                                                onclick="updateQty({{ $item->getKey() }}, -1)"
                                                aria-label="تقليل كمية {{ $item->title }}"
                                            >−</button>
                                            <span class="qty-value">{{ $cartItems[$item->getKey()]['quantity'] ?? 0 }}</span>
                                            <button
                                                type="button"
                                                class="qty-btn"
                                                onclick="updateQty({{ $item->getKey() }}, 1)"
                                                aria-label="زيادة كمية {{ $item->title }}"
                                            >+</button>
                                        </div>

                                        <button
                                            type="button"
                                            class="add-to-cart-btn"
                                            onclick="addToCart({{ $item->getKey() }})"
                                        >اطلب</button>
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

                                        <div
                                            class="item-actions {{ isset($cartItems[$item->getKey()]) ? 'is-in-cart' : '' }}"
                                            data-item-id="{{ $item->getKey() }}"
                                        >
                                            <div class="qty-controls">
                                                <button
                                                    type="button"
                                                    class="qty-btn"
                                                    onclick="updateQty({{ $item->getKey() }}, -1)"
                                                    aria-label="تقليل كمية {{ $item->title }}"
                                                >−</button>
                                                <span class="qty-value">{{ $cartItems[$item->getKey()]['quantity'] ?? 0 }}</span>
                                                <button
                                                    type="button"
                                                    class="qty-btn"
                                                    onclick="updateQty({{ $item->getKey() }}, 1)"
                                                    aria-label="زيادة كمية {{ $item->title }}"
                                                >+</button>
                                            </div>

                                            <button
                                                type="button"
                                                class="add-to-cart-btn"
                                                onclick="addToCart({{ $item->getKey() }})"
                                            >اطلب</button>
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

                <div class="track-cta">
                    <span>عندك طلب سابق؟</span>
                    <a class="btn btn--ghost" href="{{ route('site.track', ['slug' => $tenantSlug]) }}">تتبع طلبك ←</a>
                </div>
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

        <section class="section" id="trackSection" aria-label="تتبع الطلب">
            <div class="container">
                <h2 class="section-title">تتبع طلبك</h2>
                <p class="section-lead">أدخل رقم الطلب لمعرفة حالته.</p>

                <div class="track-section">
                    <div class="track-form">
                        <input
                            id="trackNumber"
                            type="number"
                            min="1"
                            inputmode="numeric"
                            placeholder="رقم طلبك مثلاً: 47"
                            aria-label="رقم الطلب"
                            autocomplete="off"
                        >
                        <button type="button" onclick="trackOrder()">بحث</button>
                    </div>

                    <div id="trackResult" class="track-result" hidden></div>
                </div>
            </div>
        </section>
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

    <button
        type="button"
        id="cartFab"
        class="cart-fab"
        onclick="openCartTab()"
        aria-label="فتح طلبك"
        @if ($cart['count'] === 0) hidden @endif
    >
        <span aria-hidden="true">🛒</span>
        <span id="cartCount">{{ $cart['count'] }}</span>
    </button>

    <div id="cartModal" class="cart-modal" role="dialog" aria-modal="true" aria-labelledby="cartTitle">
        <div class="cart-sheet">
            <div class="cart-header">
                <h3 id="cartTitle">طلبك</h3>
                <button type="button" class="icon-btn" onclick="closeCart()" aria-label="إغلاق طلبك">✕</button>
            </div>

            <div id="cartItems" class="cart-items"></div>

            <div class="cart-footer">
                <div class="cart-total">
                    <span>الإجمالي</span>
                    <span><span id="cartTotal">{{ number_format($cart['total'], 2) }}</span> {{ $currency }}</span>
                </div>
                <button type="button" onclick="showOrderForm()" class="checkout-btn">تأكيد الطلب</button>
            </div>
        </div>
    </div>

    <div id="orderModal" class="order-modal" role="dialog" aria-modal="true" aria-labelledby="orderTitle">
        <div class="modal-card">
            <h3 id="orderTitle">بيانات الطلب</h3>

            @if (count($branches) > 1)
                <label class="field">
                    <span class="field__label">اختر الفرع</span>
                    <select id="branchSelect" aria-label="اختر الفرع">
                        <option value="">-- اختر الفرع --</option>
                        @foreach ($branches as $index => $branch)
                            <option value="{{ $index }}">{{ $branch['branch_name'] ?? 'فرع '.($index + 1) }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            <div class="field">
                <span class="field__label">نوع الاستلام</span>
                <div class="delivery-type-btns">
                    <button
                        type="button"
                        class="type-btn active"
                        data-type="takeaway"
                        onclick="setDeliveryType('takeaway')"
                    >🏃 تيك أواي</button>
                    <button
                        type="button"
                        class="type-btn"
                        data-type="delivery"
                        onclick="setDeliveryType('delivery')"
                    >🛵 دليفري</button>
                </div>
            </div>

            <div id="addressGroup" class="field" style="display:none">
                <span class="field__label">العنوان</span>
                <textarea
                    id="deliveryAddress"
                    placeholder="اكتب عنوانك بالتفصيل"
                    aria-label="عنوان التوصيل"
                    maxlength="500"
                ></textarea>
            </div>

            <label class="field">
                <input id="customerName" type="text" placeholder="اسمك" aria-label="اسمك" autocomplete="name" maxlength="100">
            </label>

            <label class="field">
                <input id="customerPhone" type="tel" placeholder="رقم تليفونك" aria-label="رقم تليفونك" autocomplete="tel" maxlength="20">
            </label>

            <label class="field">
                <textarea id="orderNotes" placeholder="ملاحظات (اختياري)" aria-label="ملاحظات" maxlength="500"></textarea>
            </label>

            <p id="orderError" class="form-error" hidden></p>

            <div class="modal-actions">
                <button type="button" class="secondary-btn" onclick="closeOrderForm()">إلغاء</button>
                <button type="button" id="submitOrderBtn" class="checkout-btn" onclick="submitOrder()">تأكيد الطلب</button>
            </div>
        </div>
    </div>

    <div id="successModal" class="success-modal" role="dialog" aria-modal="true" aria-labelledby="successTitle">
        <div class="success-card">
            <div class="success-icon" aria-hidden="true">✓</div>
            <h3 id="successTitle">تم استلام طلبك!</h3>

            <div class="order-number-row">
                <span>رقم طلبك:</span>
                <div class="order-number-badge">
                    <strong id="orderNumber"></strong>
                    <button
                        type="button"
                        onclick="copyOrderNumber()"
                        class="copy-btn"
                        title="انسخ الرقم"
                        aria-label="انسخ رقم الطلب"
                    >📋</button>
                </div>
            </div>

            <p id="successName"></p>
            <p id="successPhone"></p>

            <div class="contact-ways">
                <p class="contact-title">تابع طلبك عن طريق:</p>

                @if (filled($phone))
                    <a href="https://wa.me/{{ $phone }}" class="contact-link whatsapp" target="_blank" rel="noopener">
                        <span aria-hidden="true">💬</span>
                        <span>واتساب</span>
                    </a>
                @endif

                @foreach ($socialLinks as $platform => $url)
                    <a href="{{ $url }}" class="contact-link social" target="_blank" rel="noopener">
                        <span>{{ $platform }}</span>
                    </a>
                @endforeach
            </div>

            <p class="track-hint">احتفظ برقم طلبك للمتابعة</p>
            <button type="button" class="track-btn" onclick="goToTrack()">تتبع طلبك الآن</button>
        </div>
    </div>

    <div class="copy-toast" id="copy-toast" role="status" aria-live="polite"></div>

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

    <script>
        window.siteOrder = {
            urls: @js([
                'add' => route('site.cart.add', ['slug' => $tenantSlug]),
                'update' => route('site.cart.update', ['slug' => $tenantSlug]),
                'show' => route('site.cart', ['slug' => $tenantSlug]),
                'submit' => route('site.order.submit', ['slug' => $tenantSlug]),
                'track' => route('site.order.track', ['slug' => $tenantSlug]),
                'trackPage' => route('site.track', ['slug' => $tenantSlug]),
            ]),
            currency: @js($currency),
            cart: @js($cart),
        };
    </script>

    <script>
        (function () {
            'use strict';

            var config = window.siteOrder || {
                urls: {},
                currency: '',
                cart: { items: {}, total: 0, count: 0 },
            };

            var cart = config.cart || { items: {}, total: 0, count: 0 };
            var lastOrderNumber = '';
            var selectedDeliveryType = 'takeaway';
            var toastTimer;

            var csrf = document.querySelector('meta[name="csrf-token"]');
            csrf = csrf ? csrf.getAttribute('content') : '';

            function money(value) {
                return Number(value || 0).toFixed(2);
            }

            function make(tag, className, text) {
                var node = document.createElement(tag);

                if (className) {
                    node.className = className;
                }

                if (text !== undefined) {
                    node.textContent = text;
                }

                return node;
            }

            function showToast(message) {
                var toast = document.getElementById('copy-toast');

                if (!toast) {
                    return;
                }

                toast.textContent = message;
                toast.classList.add('is-visible');

                window.clearTimeout(toastTimer);
                toastTimer = window.setTimeout(function () {
                    toast.classList.remove('is-visible');
                }, 2200);
            }

            function errorMessage(payload, fallback) {
                if (payload && payload.message) {
                    return payload.message;
                }

                if (payload && payload.errors) {
                    var first = Object.values(payload.errors)[0];

                    if (Array.isArray(first) && first.length > 0) {
                        return first[0];
                    }
                }

                return fallback;
            }

            async function request(url, options) {
                var settings = options || {};
                var headers = {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                };

                if (settings.body) {
                    headers['Content-Type'] = 'application/json';
                }

                var response = await fetch(url, {
                    method: settings.method || 'GET',
                    headers: headers,
                    body: settings.body,
                    credentials: 'same-origin',
                });

                var payload = {};

                try {
                    payload = await response.json();
                } catch (error) {
                    payload = {};
                }

                if (!response.ok) {
                    throw new Error(errorMessage(payload, 'حدث خطأ، حاول مرة أخرى.'));
                }

                return payload;
            }

            function findLine(itemId) {
                return (cart.items || {})[String(itemId)] || null;
            }

            function cloneCart() {
                return JSON.parse(JSON.stringify(cart));
            }

            /**
             * Rebuilds totals from the local lines so the optimistic cart still
             * shows the right count and total before the server answers.
             */
            function recalculateLocalCart() {
                var total = 0;
                var count = 0;

                Object.keys(cart.items || {}).forEach(function (key) {
                    var line = cart.items[key];

                    line.price = Number(line.price || 0);
                    line.quantity = Number(line.quantity || 0);
                    line.subtotal = Math.round(line.price * line.quantity * 100) / 100;

                    total += line.subtotal;
                    count += line.quantity;
                });

                cart.total = Math.round(total * 100) / 100;
                cart.count = count;
            }

            function renderCartItems() {
                var container = document.getElementById('cartItems');
                var lines = Object.values(cart.items || {});

                container.replaceChildren();

                if (lines.length === 0) {
                    container.appendChild(make('p', 'cart-empty', 'طلبك فارغ، اختر بعض الأصناف أولاً.'));
                    return;
                }

                lines.forEach(function (line) {
                    var row = make('div', 'cart-item');

                    if (line.image) {
                        var image = document.createElement('img');

                        image.className = 'cart-item-image';
                        image.src = line.image;
                        image.alt = line.name;
                        image.loading = 'lazy';
                        row.appendChild(image);
                    } else {
                        row.appendChild(make('span', 'cart-item-image', '🍽️'));
                    }

                    var body = make('div', 'cart-item-body');

                    body.appendChild(make('p', 'cart-item-name', line.name));
                    body.appendChild(make(
                        'p',
                        'cart-item-price',
                        money(line.price) + ' ' + config.currency + ' × ' + line.quantity
                    ));

                    row.appendChild(body);

                    var controls = make('div', 'qty-controls');
                    var minus = make('button', 'qty-btn', '−');

                    minus.type = 'button';
                    minus.setAttribute('aria-label', 'تقليل الكمية');
                    minus.addEventListener('click', function () {
                        updateQty(line.id, -1);
                    });

                    var plus = make('button', 'qty-btn', '+');

                    plus.type = 'button';
                    plus.setAttribute('aria-label', 'زيادة الكمية');
                    plus.addEventListener('click', function () {
                        updateQty(line.id, 1);
                    });

                    controls.appendChild(minus);
                    controls.appendChild(make('span', 'qty-value', String(line.quantity)));
                    controls.appendChild(plus);

                    row.appendChild(controls);
                    container.appendChild(row);
                });
            }

            function renderCart() {
                var count = Number(cart.count || 0);
                var total = Number(cart.total || 0);

                document.getElementById('cartCount').textContent = String(count);
                document.getElementById('cartFab').hidden = count === 0;
                document.getElementById('cartTotal').textContent = money(total);

                document.querySelectorAll('.item-actions[data-item-id]').forEach(function (actions) {
                    var line = findLine(actions.getAttribute('data-item-id'));
                    var value = actions.querySelector('.qty-value');

                    actions.classList.toggle('is-in-cart', Boolean(line));

                    if (value) {
                        value.textContent = line ? String(line.quantity) : '0';
                    }
                });

                renderCartItems();
            }

            async function refreshCart() {
                cart = await request(config.urls.show);
                renderCart();
            }

            function addToCart(itemId) {
                var previousCart = cloneCart();
                var key = String(itemId);
                var line = findLine(itemId);
                var actions = document.querySelector('.item-actions[data-item-id="' + itemId + '"]');
                var card = actions ? actions.closest('.card') : null;
                var name = card ? (card.getAttribute('data-name') || '') : '';
                var price = card ? parseFloat(card.getAttribute('data-price') || '0') : 0;

                // Optimistic UI: the button becomes quantity controls right away,
                // then the session is updated in the background.
                if (!line) {
                    line = {
                        id: Number(itemId),
                        name: name,
                        price: price,
                        quantity: 0,
                        subtotal: 0,
                        image: null,
                    };

                    cart.items[key] = line;
                }

                line.quantity = Number(line.quantity || 0) + 1;
                recalculateLocalCart();
                renderCart();

                request(config.urls.add, {
                    method: 'POST',
                    body: JSON.stringify({ item_id: itemId }),
                }).then(function (data) {
                    return refreshCart().then(function () {
                        showToast(data.message || 'تم الإضافة');
                    });
                }).catch(function (error) {
                    // The server rejected the add, so put the cart back exactly
                    // where it was before the tap.
                    cart = previousCart;
                    renderCart();
                    showToast(error.message);
                });
            }

            async function setQuantity(itemId, quantity) {
                try {
                    var data = await request(config.urls.update, {
                        method: 'POST',
                        body: JSON.stringify({
                            item_id: itemId,
                            quantity: Math.max(0, quantity),
                        }),
                    });

                    cart = data.cart || cart;
                    renderCart();
                } catch (error) {
                    showToast(error.message);
                }
            }

            function updateQty(itemId, delta) {
                var line = findLine(itemId);

                if (!line) {
                    return;
                }

                return setQuantity(itemId, Number(line.quantity) + delta);
            }

            function openModal(modal) {
                modal.classList.add('is-open');
                document.body.classList.add('modal-open');
            }

            function closeModal(modal) {
                modal.classList.remove('is-open');

                if (!document.querySelector('.cart-modal.is-open, .order-modal.is-open, .success-modal.is-open')) {
                    document.body.classList.remove('modal-open');
                }
            }

            function openCartTab() {
                renderCart();
                openModal(document.getElementById('cartModal'));
            }

            function closeCart() {
                closeModal(document.getElementById('cartModal'));
            }

            function setDeliveryType(type) {
                selectedDeliveryType = type;

                var addressGroup = document.getElementById('addressGroup');

                if (addressGroup) {
                    addressGroup.style.display = type === 'delivery' ? 'block' : 'none';
                }

                document.querySelectorAll('.type-btn').forEach(function (button) {
                    button.classList.toggle('active', button.dataset.type === type);
                });
            }

            function showOrderForm() {
                if (Object.keys(cart.items || {}).length === 0) {
                    showToast('طلبك فارغ، أضف أصنافاً أولاً.');
                    return;
                }

                closeCart();

                var error = document.getElementById('orderError');

                error.hidden = true;
                error.textContent = '';

                setDeliveryType('takeaway');

                openModal(document.getElementById('orderModal'));
                document.getElementById('customerName').focus();
            }

            function closeOrderForm() {
                closeModal(document.getElementById('orderModal'));
            }

            function showOrderError(message) {
                var error = document.getElementById('orderError');

                error.textContent = message;
                error.hidden = false;
            }

            async function submitOrder() {
                var name = document.getElementById('customerName').value.trim();
                var phone = document.getElementById('customerPhone').value.trim();
                var notes = document.getElementById('orderNotes').value.trim();
                var address = document.getElementById('deliveryAddress').value.trim();
                var branchSelect = document.getElementById('branchSelect');
                var branchId = branchSelect && branchSelect.value !== '' ? Number(branchSelect.value) : null;
                var button = document.getElementById('submitOrderBtn');

                if (!name || !phone) {
                    showOrderError('يرجى إدخال الاسم ورقم الهاتف.');

                    return;
                }

                if (branchSelect && branchId === null) {
                    showOrderError('يرجى اختيار الفرع.');

                    return;
                }

                if (selectedDeliveryType === 'delivery' && !address) {
                    showOrderError('يرجى إدخال عنوان التوصيل.');

                    return;
                }

                button.disabled = true;

                try {
                    var data = await request(config.urls.submit, {
                        method: 'POST',
                        body: JSON.stringify({
                            customer_name: name,
                            customer_phone: phone,
                            notes: notes || null,
                            branch_id: branchId,
                            delivery_type: selectedDeliveryType,
                            delivery_address: selectedDeliveryType === 'delivery' ? address : null,
                        }),
                    });

                    lastOrderNumber = String(data.order_number);

                    document.getElementById('orderNumber').textContent = data.order_number_display || ('#' + data.order_number);
                    document.getElementById('successName').textContent = data.customer_name;
                    document.getElementById('successPhone').textContent = data.customer_phone;

                    cart = { items: {}, total: 0, count: 0 };
                    renderCart();

                    document.getElementById('customerName').value = '';
                    document.getElementById('customerPhone').value = '';
                    document.getElementById('orderNotes').value = '';
                    document.getElementById('deliveryAddress').value = '';

                    if (branchSelect) {
                        branchSelect.value = '';
                    }

                    setDeliveryType('takeaway');

                    closeOrderForm();
                    openModal(document.getElementById('successModal'));
                } catch (error) {
                    showOrderError(error.message);
                } finally {
                    button.disabled = false;
                }
            }

            function closeSuccess() {
                closeModal(document.getElementById('successModal'));
            }

            function copyOrderNumber() {
                var element = document.getElementById('orderNumber');
                var value = element ? element.textContent : '';

                function copied() {
                    showToast('تم نسخ رقم الطلب ✓');
                }

                function fallback() {
                    var field = document.createElement('textarea');

                    field.value = value;
                    field.setAttribute('readonly', '');
                    field.style.position = 'fixed';
                    field.style.opacity = '0';

                    document.body.appendChild(field);
                    field.select();

                    try {
                        document.execCommand('copy');
                        copied();
                    } catch (error) {
                        // The browser refused every copy route; nothing else to try.
                    }

                    document.body.removeChild(field);
                }

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(value).then(copied).catch(fallback);
                } else {
                    fallback();
                }
            }

            function goToTrack() {
                if (lastOrderNumber && config.urls.trackPage) {
                    window.location.href = config.urls.trackPage + '/' + encodeURIComponent(lastOrderNumber);

                    return;
                }

                showTrackForm();
            }

            function showTrackForm() {
                closeSuccess();

                var input = document.getElementById('trackNumber');

                if (!input.value) {
                    input.value = lastOrderNumber;
                }

                document.getElementById('trackSection').scrollIntoView({ behavior: 'smooth', block: 'start' });

                window.setTimeout(function () {
                    input.focus({ preventScroll: true });
                }, 300);
            }

            function renderTrackResult(order) {
                var container = document.getElementById('trackResult');
                var card = make('div', 'track-card');

                card.appendChild(make('p', 'track-status', order.status_label || order.status));
                card.appendChild(make('p', null, 'اسم العميل: ' + order.customer_name));

                var list = make('ul', 'track-items');

                (order.items || []).forEach(function (item) {
                    var row = make('li');

                    row.appendChild(make('span', null, item.name + ' × ' + item.quantity));
                    row.appendChild(make('span', null, money(item.subtotal) + ' ' + config.currency));
                    list.appendChild(row);
                });

                card.appendChild(list);

                var total = make('div', 'track-total');

                total.appendChild(make('span', null, 'الإجمالي'));
                total.appendChild(make('span', null, money(order.total) + ' ' + config.currency));
                card.appendChild(total);

                container.replaceChildren(card);
                container.hidden = false;
            }

            function renderTrackError(message) {
                var container = document.getElementById('trackResult');

                container.replaceChildren(make('p', 'track-error', message));
                container.hidden = false;
            }

            async function trackOrder() {
                var input = document.getElementById('trackNumber');
                var orderNumber = input.value.trim();

                if (!orderNumber || !/^\d+$/.test(orderNumber)) {
                    renderTrackError('يرجى إدخال رقم الطلب.');

                    return;
                }

                try {
                    var order = await request(config.urls.track + '?order_number=' + encodeURIComponent(orderNumber));

                    renderTrackResult(order);
                } catch (error) {
                    renderTrackError(error.message);
                }
            }

            var cartModal = document.getElementById('cartModal');
            var orderModal = document.getElementById('orderModal');
            var successModal = document.getElementById('successModal');

            [cartModal, orderModal, successModal].forEach(function (modal) {
                modal.addEventListener('click', function (event) {
                    if (event.target !== modal) {
                        return;
                    }

                    if (modal === cartModal) {
                        closeCart();
                    } else if (modal === orderModal) {
                        closeOrderForm();
                    } else {
                        closeSuccess();
                    }
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') {
                    return;
                }

                if (cartModal.classList.contains('is-open')) {
                    closeCart();
                } else if (orderModal.classList.contains('is-open')) {
                    closeOrderForm();
                } else if (successModal.classList.contains('is-open')) {
                    closeSuccess();
                }
            });

            window.addToCart = addToCart;
            window.updateQty = updateQty;
            window.openCartTab = openCartTab;
            window.closeCart = closeCart;
            window.showOrderForm = showOrderForm;
            window.setDeliveryType = setDeliveryType;
            window.submitOrder = submitOrder;
            window.copyOrderNumber = copyOrderNumber;
            window.goToTrack = goToTrack;
            window.showTrackForm = showTrackForm;
            window.trackOrder = trackOrder;

            renderCart();
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