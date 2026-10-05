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
            .site-footer__inner { flex-direction: row; justify-content: space-between; text-align: start; }
        }

        @media (min-width: 900px) {
            .features { grid-template-columns: repeat(3, 1fr); }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }

            .btn, .btn:hover { transition: none; transform: none; }
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

        <section class="section section--alt" id="pricing">
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
    </main>

    <footer class="site-footer">
        <div class="container site-footer__inner">
            <span>{{ $appName }} — <a href="{{ $whatsappUrl }}" rel="noopener" target="_blank">واتساب: {{ $whatsappNumber }}</a></span>
            <span>جميع الحقوق محفوظة {{ now()->year }}</span>
        </div>
    </footer>
</body>
</html>