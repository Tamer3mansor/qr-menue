<x-filament-widgets::widget class="fi-qr-menu-info-widget">
    <x-filament::section>
        <div class="flex flex-col items-center justify-center gap-2 py-2 text-center">
            @if ($logoUrl !== null)
                <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="h-10 w-auto" />
            @else
                <span class="text-2xl font-extrabold tracking-tight text-primary-600 dark:text-primary-400">
                    {{ $brandName }}
                </span>
            @endif

            <p class="text-sm text-gray-500 dark:text-gray-400">
                نظام قوائم الطعام الرقمية
            </p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
