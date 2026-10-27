@php
    $qrCode = $this->getQrCode();
@endphp

<x-filament-panels::page>
    @if ($qrCode)
        <x-filament::section>
            <x-slot name="heading">
                {{ $qrCode->url }}
            </x-slot>

            <x-slot name="description">
                مسح هذا الكود يفتح القائمة العامة للمطعم.
            </x-slot>

            <div class="flex flex-col items-center gap-6">
                @if ($this->isImageMissing())
                    <div class="w-full rounded-lg bg-warning-50 p-4 text-sm text-warning-700 dark:bg-warning-400/10 dark:text-warning-400">
                        <p class="font-semibold">ملف صورة الكود غير موجود على الخادم.</p>
                        <p class="mt-1">أعد توليد الكود لاستعادة الصورة قبل طباعته أو مشاركته.</p>
                        <x-filament::button
                            color="warning"
                            icon="heroicon-m-arrow-path"
                            class="mt-3"
                            wire:click="regenerate"
                            wire:loading.attr="disabled"
                        >
                            إعادة توليد
                        </x-filament::button>
                    </div>
                @else
                    <img
                        src="{{ $qrCode->image_url }}"
                        alt="كود QR لـ {{ $qrCode->url }}"
                        width="400"
                        height="400"
                        class="rounded-xl bg-white p-4"
                    />
                @endif

                <x-filament::link
                    href="{{ $qrCode->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    icon="heroicon-m-arrow-top-right-on-square"
                >
                    {{ $qrCode->url }}
                </x-filament::link>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                لم يتم إنشاء كود QR بعد.
            </p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
