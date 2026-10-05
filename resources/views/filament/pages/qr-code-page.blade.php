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
                Scanning this code opens the public menu for this restaurant.
            </x-slot>

            <div class="flex flex-col items-center gap-6">
                <img
                    src="{{ $qrCode->image_url }}"
                    alt="QR code for {{ $qrCode->url }}"
                    width="320"
                    height="320"
                    class="rounded-xl bg-white p-4"
                />

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
                No QR code has been generated yet.
            </p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
