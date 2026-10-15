<div class="fi-section space-y-4 rounded-lg bg-white p-4 dark:bg-gray-900">
    @if ($colors === [])
        <p class="text-sm text-gray-500">
            تعذر استخلاص ألوان من الشعار. جرّب رفع صورة أخرى أو اختر الألوان يدوياً.
        </p>
    @else
        <div class="flex items-center gap-3">
            <span
                class="h-10 w-10 shrink-0 rounded-lg border border-gray-200 dark:border-gray-700"
                style="background: {{ $colors['primary'] }}"
            ></span>
            <div>
                <p class="text-sm text-gray-500">اللون الأساسي</p>
                <p class="font-mono text-sm" dir="ltr">{{ $colors['primary'] }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span
                class="h-10 w-10 shrink-0 rounded-lg border border-gray-200 dark:border-gray-700"
                style="background: {{ $colors['secondary'] }}"
            ></span>
            <div>
                <p class="text-sm text-gray-500">اللون الثانوي</p>
                <p class="font-mono text-sm" dir="ltr">{{ $colors['secondary'] }}</p>
            </div>
        </div>
    @endif
</div>
