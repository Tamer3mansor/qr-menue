<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $user->name }}</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f6f6f7;
            color: #16181d;
            font-family: 'Cairo', system-ui, -apple-system, 'Segoe UI', sans-serif;
            text-align: center;
        }

        .box {
            max-width: 420px;
            padding: 32px 24px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgb(0 0 0 / .08);
        }

        .icon { font-size: 40px; }

        h1 { margin: 12px 0 8px; font-size: 21px; }

        p { margin: 0; color: #6b7280; font-size: 15px; }
    </style>
</head>
<body>
<div class="box">
    <div class="icon" aria-hidden="true">🔒</div>

    <h1>{{ $user->name }}</h1>

    <p>
        @if (! $user->is_active)
            الحساب موقوف حالياً. رجّع المحل للتشغيل مباشرة، أو تواصل معنا لتفعيل الحساب من جديد.
        @else
            انتهى الاشتراك. جدد الاشتراك وارجع المطعم يظهر قائمته مباشرة للعملاء.
        @endif
    </p>
</div>
</body>
</html>