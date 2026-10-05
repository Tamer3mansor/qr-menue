<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>انتهى الاشتراك</title>

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
            font-family: 'Instrument Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
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

    <h1>انتهى الاشتراك</h1>

    <p>
        @if (! $user->is_active)
            هذا الحساب غير مُفعّل حاليًا. الرجاء التواصل مع الدعم لتفعيله.
        @else
            انتهت مدة اشتراك هذا المطعم. الرجاء تجديد الاشتراك لعرض القائمة.
        @endif
    </p>
</div>
</body>
</html>
