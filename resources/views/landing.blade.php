<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }} — قائمة طعامك الرقمية في دقائق</title>
    <meta name="description" content="امنح مطعمك أو كافيهك قائمة رقمية احترافية مع QR Code جاهز للطباعة.">

    <style>
        :root {
            --primary: #2D6A4F;
            --accent: #52B788;
            --bg: #F8F9FA;
            --text: #1A1A2E;
            --card-bg: #FFFFFF;
            --radius: 12px;

            --muted: #5b6472;
            --border: #e4e7ec;
            --shadow: 0 1px 2px rgba(26, 26, 46, .05), 0 8px 24px rgba(26, 26, 46, .06);
            --section-pad: 56px;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html { -webkit-text-size-adjust: 100%; scroll-behavior: smooth; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: system-ui, -apple-system, 'Segoe UI', 'Noto Sans Arabic', sans-serif;
            line-height: 1.6;
        }

        img, svg { max-width: 100%; height: auto; }

        a { color: var(--primary); }

        .container {
            width: 100%;
            max-width: 1080px;
            margin-inline: auto;
            padding-inline: 20px;
        }

        .section { padding-block: var(--section-pad); }

        .section--alt { background: var(--card-bg); }

        .section-head {
            text-align: center;
            max-width: 46rem;
            margin: 0 auto 40px;
        }

        .eyebrow {
            margin: 0 0 8px;
            color: var(--primary);
            font-weight: 700;
            font-size: .875rem;
            letter-spacing: .02em;
        }

        .section-title {
            margin: 0 0 12px;
            font-size: clamp(1.5rem, 5vw, 2.125rem);
            line-height: 1.25;
        }

        .section-lead {
            margin: 0;
            color: var(--muted);
            font-size: 1.0625rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border: 1px solid transparent;
            border-radius: var(--radius);
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, background-color .15s ease;
        }

        .btn:hover { transform: translateY(-1px); }

        .btn--primary {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 8px 20px rgba(45, 106, 79, .25);
        }

        .btn--primary:hover { background: #245640; }

        .btn--ghost {
            background: transparent;
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn--ghost:hover { background: rgba(45, 106, 79, .07); }

        /* Header */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(248, 249, 250, .92);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--border);
        }

        .site-header__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            min-height: 64px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 1.125rem;
            color: var(--text);
            text-decoration: none;
        }

        .brand__mark {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            font-size: 1rem;
        }

        /* Hero */
        .hero {
            padding-block: clamp(48px, 10vw, 96px);
            background:
                radial-gradient(60% 55% at 50% 0%, rgba(82, 183, 136, .16), transparent 70%),
                var(--bg);
        }

        .hero__inner { text-align: center; }

        .hero__title {
            margin: 0 0 16px;
            font-size: clamp(1.875rem, 7vw, 3.25rem);
            line-height: 1.15;
        }

        .hero__subtitle {
            max-width: 38rem;
            margin: 0 auto 32px;
            color: var(--muted);
            font-size: clamp(1rem, 2.4vw, 1.1875rem);
        }

        .hero__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }

        /* Steps */
        .steps {
            display: grid;
            gap: 20px;
            grid-template-columns: 1fr;
            list-style: none;
            margin: 0;
            padding: 0;
            counter-reset: step;
        }

        .step {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px 24px;
        }

        .step::before {
            counter-increment: step;
            content: counter(step);
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            margin-bottom: 16px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            font-weight: 800;
        }

        .step__title { margin: 0 0 6px; font-size: 1.125rem; }

        .step__text { margin: 0; color: var(--muted); }

        /* Features */
        .features {
            display: grid;
            gap: 20px;
            grid-template-columns: 1fr;
        }

        .feature {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 26px 22px;
            box-shadow: var(--shadow);
        }

        .feature__icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            margin-bottom: 14px;
            border-radius: 12px;
            background: rgba(82, 183, 136, .15);
            font-size: 1.375rem;
        }

        .feature__title { margin: 0 0 6px; font-size: 1.0625rem; }

        .feature__text { margin: 0; color: var(--muted); font-size: .9375rem; }

        /* Pricing */
        .pricing {
            display: grid;
            gap: 28px;
            place-items: center;
        }

        .price-card {
            width: 100%;
            max-width: 26rem;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: calc(var(--radius) + 6px);
            padding: 34px 28px;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .price-card__name {
            margin: 0 0 4px;
            font-size: 1.125rem;
            color: var(--primary);
            font-weight: 800;
        }

        .price-card__amount {
            margin: 0 0 4px;
            font-size: clamp(2.25rem, 9vw, 3rem);
            font-weight: 800;
            line-height: 1.1;
        }

        .price-card__period { margin: 0 0 24px; color: var(--muted); }

        .price-list {
            list-style: none;
            margin: 0 0 26px;
            padding: 0;
            text-align: start;
            display: grid;
            gap: 10px;
        }

        .price-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .price-list li::before {
            content: '✓';
            flex: none;
            color: var(--primary);
            font-weight: 800;
        }

        /* Demo */
        .demo { text-align: center; }

        .demo__text {
            max-width: 34rem;
            margin: 0 auto 28px;
            color: var(--muted);
            font-size: 1.0625rem;
        }

        /* Phone mockup */
        .phone-showcase__inner {
            display: grid;
            gap: 40px;
            align-items: center;
        }

        .phone-showcase__device {
            display: flex;
            justify-content: center;
        }

        .phone {
            position: relative;
            width: 280px;
            height: 560px;
            border: 8px solid #1a1a1a;
            border-radius: 40px;
            background: #1a1a1a;
            box-shadow: 0 26px 60px rgba(26, 26, 46, .28);
            animation: float 3s ease-in-out infinite;
        }

        .phone__notch {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 22px;
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px;
            background: #1a1a1a;
            z-index: 2;
        }

        .phone__screen {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 32px;
            background: var(--bg);
        }

        .phone-menu__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 34px 16px 14px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
        }

        .phone-menu__logo { font-weight: 800; font-size: .9375rem; }

        .phone-menu__badge {
            padding: 3px 9px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .22);
            font-size: .625rem;
        }

        .phone-menu__cats {
            display: flex;
            gap: 6px;
            padding: 12px 12px 6px;
        }

        .phone-menu__cat {
            flex: none;
            padding: 5px 10px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: #fff;
            color: var(--muted);
            font-size: .625rem;
        }

        .phone-menu__cat--active {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
        }

        .phone-menu__items {
            display: grid;
            gap: 10px;
            padding: 8px 12px 16px;
        }

        .phone-menu__item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
        }

        .phone-menu__thumb {
            flex: none;
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: linear-gradient(135deg, #f6d365, #fda085);
        }

        .phone-menu__item:nth-child(2) .phone-menu__thumb {
            background: linear-gradient(135deg, #84fab0, #8fd3f4);
        }

        .phone-menu__item:nth-child(3) .phone-menu__thumb {
            background: linear-gradient(135deg, #a18cd1, #fbc2eb);
        }

        .phone-menu__meta { display: grid; gap: 2px; }

        .phone-menu__name { font-size: .75rem; font-weight: 700; }

        .phone-menu__price {
            color: var(--primary);
            font-size: .6875rem;
            font-weight: 800;
        }

        /* Before / After */
        .compare {
            display: grid;
            gap: 24px;
            grid-template-columns: 1fr;
        }

        .compare__card {
            padding: 28px 24px;
            border-radius: calc(var(--radius) + 4px);
        }

        .compare__card--before {
            border: 2px solid #fee2e2;
            background: #fff5f5;
        }

        .compare__card--after {
            border: 2px solid #c6f6d5;
            background: #f0fff4;
        }

        .compare__icon {
            display: grid;
            place-items: center;
            width: 48px;
            height: 48px;
            margin-bottom: 12px;
            border-radius: 12px;
            background: #fff;
            font-size: 1.5rem;
        }

        .compare__tag {
            display: inline-block;
            margin-bottom: 8px;
            font-size: .8125rem;
            font-weight: 800;
        }

        .compare__card--before .compare__tag { color: #c53030; }

        .compare__card--after .compare__tag { color: #2f855a; }

        .compare__title { margin: 0 0 16px; font-size: 1.25rem; }

        .compare__list {
            display: grid;
            gap: 12px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .compare__list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .compare__list li::before { flex: none; }

        .compare__card--before .compare__list li::before { content: '❌'; }

        .compare__card--after .compare__list li::before { content: '✅'; }

        /* Reveal on scroll */
        .reveal {
            opacity: 0;
            transition: opacity .6s ease, transform .6s ease;
        }

        .reveal--left { transform: translateX(-48px); }

        .reveal--right { transform: translateX(48px); }

        .reveal.is-visible {
            opacity: 1;
            transform: translateX(0);
        }

        /* Stats */
        .stats {
            display: grid;
            gap: 28px;
            grid-template-columns: 1fr;
            text-align: center;
        }

        .stat__value {
            display: block;
            color: var(--primary);
            font-size: clamp(2.25rem, 8vw, 3rem);
            font-weight: 800;
            line-height: 1.1;
        }

        .stat__label { display: block; color: var(--muted); }

        /* Roadmap */
        .roadmap {
            display: grid;
            gap: 0;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .roadmap__step {
            position: relative;
            display: grid;
            grid-template-columns: 48px 1fr;
            gap: 14px;
            align-items: start;
            padding-bottom: 28px;
        }

        .roadmap__step::before {
            content: '';
            position: absolute;
            top: 48px;
            bottom: 0;
            inset-inline-start: 23px;
            width: 2px;
            background: var(--border);
        }

        .roadmap__step:last-child { padding-bottom: 0; }

        .roadmap__step:last-child::before { display: none; }

        .roadmap__dot {
            position: relative;
            z-index: 1;
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border: 2px solid var(--border);
            border-radius: 50%;
            background: var(--card-bg);
            color: var(--muted);
            font-size: .875rem;
            font-weight: 800;
        }

        .roadmap__step--done .roadmap__dot {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
        }

        .roadmap__step--current .roadmap__dot {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
            transform: scale(1.15);
            animation: pulse 2s infinite;
        }

        .roadmap__step--locked .roadmap__dot {
            background: #f1f3f5;
            color: var(--muted);
        }

        .roadmap__label { display: block; font-weight: 700; }

        .roadmap__note {
            display: block;
            margin-top: 2px;
            color: var(--muted);
            font-size: .875rem;
        }

        .roadmap__now {
            display: inline-block;
            margin-top: 6px;
            padding: 2px 10px;
            border-radius: 999px;
            background: rgba(45, 106, 79, .12);
            color: var(--primary);
            font-size: .75rem;
            font-weight: 800;
        }

        /* Final CTA */
        .cta {
            padding-block: clamp(48px, 9vw, 84px);
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            text-align: center;
        }

        .cta__title {
            margin: 0 0 12px;
            font-size: clamp(1.5rem, 5vw, 2.25rem);
        }

        .cta__text {
            max-width: 36rem;
            margin: 0 auto 28px;
            color: rgba(255, 255, 255, .92);
            font-size: 1.0625rem;
        }

        .cta__btn {
            background: #fff;
            color: var(--primary);
            box-shadow: 0 10px 26px rgba(0, 0, 0, .2);
        }

        .cta__btn:hover { background: #f6f7f8; }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(45, 106, 79, .45); }
            70% { box-shadow: 0 0 0 16px rgba(45, 106, 79, 0); }
            100% { box-shadow: 0 0 0 0 rgba(45, 106, 79, 0); }
        }

        /* Footer */
        .site-footer {
            border-top: 1px solid var(--border);
            background: var(--card-bg);
            padding-block: 32px;
        }

        .site-footer__inner {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
            text-align: center;
            color: var(--muted);
            font-size: .9375rem;
        }

        .site-footer a { text-decoration: none; font-weight: 600; }

        @media (min-width: 640px) {
            :root { --section-pad: 72px; }

            .features { grid-template-columns: repeat(2, 1fr); }
            .steps { grid-template-columns: repeat(3, 1fr); }
            .compare { grid-template-columns: repeat(2, 1fr); }
            .stats { grid-template-columns: repeat(3, 1fr); }
            .site-footer__inner { flex-direction: row; justify-content: space-between; text-align: start; }
        }

        @media (min-width: 900px) {
            .features { grid-template-columns: repeat(3, 1fr); }

            .phone-showcase__inner { grid-template-columns: 1fr auto; gap: 56px; }
            .phone-showcase__text { order: 2; }
            .phone-showcase__device { order: 1; }

            .roadmap {
                grid-auto-flow: column;
                grid-auto-columns: 1fr;
            }

            .roadmap__step {
                grid-template-columns: 1fr;
                justify-items: center;
                gap: 12px;
                padding-bottom: 0;
                text-align: center;
            }

            .roadmap__step::before {
                top: 22px;
                right: 50%;
                bottom: auto;
                width: 100%;
                height: 2px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }

            * {
                animation: none !important;
                transition: none !important;
            }

            .reveal { opacity: 1; transform: none; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <a class="brand" href="/">
                <span class="brand__mark" aria-hidden="true">QR</span>
                <span>{{ $appName }}</span>
            </a>
            <a class="btn btn--primary" href="{{ $whatsappUrl }}" rel="noopener" target="_blank">تواصل معنا</a>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero__inner">
                <h1 class="hero__title">قائمة طعامك الرقمية في دقائق</h1>
                <p class="hero__subtitle">امنح مطعمك أو كافيهك قائمة رقمية احترافية مع QR Code جاهز للطباعة</p>
                <div class="hero__actions">
                    <a class="btn btn--primary" href="{{ $whatsappUrl }}" rel="noopener" target="_blank">تواصل معنا للاشتراك</a>
                    <a class="btn btn--ghost" href="{{ route('menu.show', $demoSlug) }}">عرض توضيحي</a>
                </div>
            </div>
        </section>

        <section class="section phone-showcase" id="preview">
            <div class="container phone-showcase__inner">
                <div class="phone-showcase__text">
                    <p class="eyebrow">معاينة مباشرة</p>
                    <h2 class="section-title">شوف كيف تبدو قائمتك</h2>
                    <p class="section-lead">قائمة رقمية احترافية يفتحها زبائنك في ثانية واحدة من QR Code</p>
                </div>

                <div class="phone-showcase__device" aria-hidden="true">
                    <div class="phone">
                        <div class="phone__notch"></div>
                        <div class="phone__screen">
                            <div class="phone-menu__header">
                                <span class="phone-menu__logo">مطعم الأصيل</span>
                                <span class="phone-menu__badge">مفتوح الآن</span>
                            </div>
                            <div class="phone-menu__cats">
                                <span class="phone-menu__cat phone-menu__cat--active">المشويات</span>
                                <span class="phone-menu__cat">المقبلات</span>
                                <span class="phone-menu__cat">المشروبات</span>
                            </div>
                            <div class="phone-menu__items">
                                <div class="phone-menu__item">
                                    <span class="phone-menu__thumb"></span>
                                    <span class="phone-menu__meta">
                                        <span class="phone-menu__name">مشاوي مشكلة</span>
                                        <span class="phone-menu__price">180 جنيه</span>
                                    </span>
                                </div>
                                <div class="phone-menu__item">
                                    <span class="phone-menu__thumb"></span>
                                    <span class="phone-menu__meta">
                                        <span class="phone-menu__name">كفتة بالطحينة</span>
                                        <span class="phone-menu__price">120 جنيه</span>
                                    </span>
                                </div>
                                <div class="phone-menu__item">
                                    <span class="phone-menu__thumb"></span>
                                    <span class="phone-menu__meta">
                                        <span class="phone-menu__name">عصير مانجو</span>
                                        <span class="phone-menu__price">45 جنيه</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section section--alt" id="how-it-works">
            <div class="container">
                <div class="section-head">
                    <p class="eyebrow">طريقة العمل</p>
                    <h2 class="section-title">ثلاث خطوات وتبدأ</h2>
                </div>

                <ol class="steps">
                    <li class="step">
                        <h3 class="step__title">أرسل لنا اسم مطعمك</h3>
                        <p class="step__text">نجهز لك حسابك على النظام خلال يوم عمل واحد.</p>
                    </li>
                    <li class="step">
                        <h3 class="step__title">أضف منتجاتك من لوحة التحكم</h3>
                        <p class="step__text">أقسام ومنتجات وأسعار وعروض، كلها من مكان واحد.</p>
                    </li>
                    <li class="step">
                        <h3 class="step__title">احصل على QR Code وشاركه</h3>
                        <p class="step__text">حمّل الكود، اطبعه، وضعه على الطاولات.</p>
                    </li>
                </ol>
            </div>
        </section>

        <section class="section" id="features">
            <div class="container">
                <div class="section-head">
                    <p class="eyebrow">المميزات</p>
                    <h2 class="section-title">كل اللي محتاجه في مكان واحد</h2>
                </div>

                <div class="features">
                    <article class="feature">
                        <div class="feature__icon" aria-hidden="true">📋</div>
                        <h3 class="feature__title">قائمة رقمية بتصميم احترافي</h3>
                        <p class="feature__text">تصميم نظيف يعرض أقسامك ومنتجاتك بشكل واضح على كل الشاشات.</p>
                    </article>

                    <article class="feature">
                        <div class="feature__icon" aria-hidden="true">🔳</div>
                        <h3 class="feature__title">QR Code جاهز للطباعة</h3>
                        <p class="feature__text">كود بدرجة طباعة عالية يتحمل على أي طاولة أو واجهة.</p>
                    </article>

                    <article class="feature">
                        <div class="feature__icon" aria-hidden="true">🔄</div>
                        <h3 class="feature__title">تحديث المنتجات والأسعار في أي وقت</h3>
                        <p class="feature__text">عدّل السعر أو احذف منتج ويظهر التعديل فوراً عند الزبون.</p>
                    </article>

                    <article class="feature">
                        <div class="feature__icon" aria-hidden="true">🏷️</div>
                        <h3 class="feature__title">عروض اليوم للزبائن</h3>
                        <p class="feature__text">اعرض خصوماتك اليومية بشكل بارز يشد انتباه الزبون.</p>
                    </article>

                    <article class="feature">
                        <div class="feature__icon" aria-hidden="true">📱</div>
                        <h3 class="feature__title">يعمل على كل الأجهزة</h3>
                        <p class="feature__text">موبايل وتابلت وكمبيوتر بدون تحميل أي تطبيق.</p>
                    </article>

                    <article class="feature">
                        <div class="feature__icon" aria-hidden="true">🎧</div>
                        <h3 class="feature__title">دعم فني مباشر</h3>
                        <p class="feature__text">فريقنا معاك عبر واتساب لو واجهتك أي مشكلة.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section section--alt" id="comparison">
            <div class="container">
                <div class="section-head">
                    <p class="eyebrow">قبل وبعد</p>
                    <h2 class="section-title">الفرق واضح</h2>
                </div>

                <div class="compare">
                    <article class="compare__card compare__card--before reveal reveal--left">
                        <div class="compare__icon" aria-hidden="true">📋</div>
                        <span class="compare__tag">❌ قبل</span>
                        <h3 class="compare__title">المنيو الورقي</h3>
                        <ul class="compare__list">
                            <li>بيتبهدل ويتوسخ</li>
                            <li>التحديث بيكلف طباعة جديدة</li>
                            <li>الزبون مش شايف الصور</li>
                            <li>مش بيعرف الأسعار الجديدة</li>
                            <li>مش قابل للمشاركة</li>
                        </ul>
                    </article>

                    <article class="compare__card compare__card--after reveal reveal--right">
                        <div class="compare__icon" aria-hidden="true">📱</div>
                        <span class="compare__tag">✅ بعد</span>
                        <h3 class="compare__title">QR Menu</h3>
                        <ul class="compare__list">
                            <li>دايماً نظيف ومحدث</li>
                            <li>تغيير الأسعار في ثانية</li>
                            <li>صور شهية لكل صنف</li>
                            <li>عروض اليوم تلقائياً</li>
                            <li>يتشارك على واتساب وفيسبوك</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" id="pricing">
            <div class="container">
                <div class="section-head">
                    <p class="eyebrow">الأسعار</p>
                    <h2 class="section-title">باقة واحدة، كل المميزات</h2>
                    <p class="section-lead">بدون رسوم شهرية ولا التزام إضافي.</p>
                </div>

                <div class="pricing">
                    <div class="price-card">
                        <h3 class="price-card__name">باقة المطعم</h3>
                        <p class="price-card__amount">{{ $price }} <span>{{ $currency }}</span></p>
                        <p class="price-card__period">لكل {{ $period }}</p>

                        <ul class="price-list">
                            <li>لوحة تحكم كاملة</li>
                            <li>QR Code جاهز للطباعة</li>
                            <li>موقع إلكتروني لمطعمك</li>
                            <li>تحديثات مجانية</li>
                            <li>دعم فني</li>
                        </ul>

                        <a class="btn btn--primary" href="{{ $whatsappUrl }}" rel="noopener" target="_blank">اشترك الآن</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="section demo" id="demo">
            <div class="container">
                <h2 class="section-title">شوف كيف تبدو قائمة مطعمك</h2>
                <p class="demo__text">دفعرة على نموذج حقيقي فيها أقسام ومنتجات وعروض، متاح للتجربة قبل ما تشترك.</p>
                <a class="btn btn--ghost" href="{{ route('menu.show', $demoSlug) }}">عرض توضيحي</a>
            </div>
        </section>

        <section class="section section--alt" id="stats">
            <div class="container">
                <div class="stats">
                    <div class="stat">
                        <span class="stat__value" data-count-to="{{ $stats['restaurants'] }}" data-prefix="+">0</span>
                        <span class="stat__label">مطعم وكافيه</span>
                    </div>
                    <div class="stat">
                        <span class="stat__value" data-count-to="{{ $stats['setup_minutes'] }}" data-suffix=" دقيقة">0</span>
                        <span class="stat__label">وقت الإعداد</span>
                    </div>
                    <div class="stat">
                        <span class="stat__value" data-count-to="{{ $stats['satisfaction'] }}" data-suffix="%">0</span>
                        <span class="stat__label">رضا العملاء</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="roadmap">
            <div class="container">
                <div class="section-head">
                    <p class="eyebrow">رحلتنا</p>
                    <h2 class="section-title">رحلتنا مع عملائنا</h2>
                </div>

                <ol class="roadmap">
                    @foreach ($roadmap as $step)
                        @php
                            $status = $step['status'] ?? 'soon';
                            $marker = $status === 'done' ? '✓' : ($status === 'locked' ? '🔒' : '');
                        @endphp
                        <li class="roadmap__step roadmap__step--{{ $status }}">
                            <span class="roadmap__dot" aria-hidden="true">{{ $marker }}</span>
                            <span class="roadmap__body">
                                <span class="roadmap__label">{{ $step['label'] }}</span>
                                @if (! empty($step['note']))
                                    <span class="roadmap__note">{{ $step['note'] }}</span>
                                @endif
                                @if ($status === 'current')
                                    <span class="roadmap__now">نحن هنا</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section class="cta" id="cta">
            <div class="container">
                <h2 class="cta__title">جاهز تحول مطعمك للرقمي؟</h2>
                <p class="cta__text">انضم لأكثر من {{ $stats['restaurants'] }} مطعم وكافيه يستخدمون QR Menu</p>
                <a class="btn cta__btn" href="{{ $whatsappUrl }}" rel="noopener" target="_blank">ابدأ الآن - تواصل معنا</a>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container site-footer__inner">
            <span>{{ $appName }} — <a href="{{ $whatsappUrl }}" rel="noopener" target="_blank">واتساب: {{ $whatsappNumber }}</a></span>
            <span>جميع الحقوق محفوظة {{ now()->year }}</span>
        </div>
    </footer>

    <script>
        (function () {
            var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            var revealTargets = document.querySelectorAll('.reveal');

            if (!('IntersectionObserver' in window) || reduceMotion) {
                revealTargets.forEach(function (el) {
                    el.classList.add('is-visible');
                });
            } else {
                var revealObserver = new IntersectionObserver(function (entries, observer) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15 });

                revealTargets.forEach(function (el) {
                    revealObserver.observe(el);
                });
            }

            var counters = document.querySelectorAll('[data-count-to]');

            function renderCount(el, value) {
                el.textContent = (el.getAttribute('data-prefix') || '')
                    + value
                    + (el.getAttribute('data-suffix') || '');
            }

            function countUp(el) {
                var target = parseInt(el.getAttribute('data-count-to'), 10) || 0;

                if (reduceMotion || !window.requestAnimationFrame) {
                    renderCount(el, target);
                    return;
                }

                var duration = 1500;
                var startedAt = null;

                function frame(timestamp) {
                    if (startedAt === null) {
                        startedAt = timestamp;
                    }

                    var progress = Math.min((timestamp - startedAt) / duration, 1);
                    var eased = 1 - Math.pow(1 - progress, 3);

                    renderCount(el, Math.round(eased * target));

                    if (progress < 1) {
                        window.requestAnimationFrame(frame);
                    }
                }

                window.requestAnimationFrame(frame);
            }

            if (!('IntersectionObserver' in window)) {
                counters.forEach(function (el) {
                    renderCount(el, parseInt(el.getAttribute('data-count-to'), 10) || 0);
                });
            } else {
                var countObserver = new IntersectionObserver(function (entries, observer) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            countUp(entry.target);
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.4 });

                counters.forEach(function (el) {
                    countObserver.observe(el);
                });
            }
        })();
    </script>
</body>
</html>