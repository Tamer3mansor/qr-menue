@php
    $phone = $tenant->settings?->phone;
    $whatsapp = filled($phone) ? preg_replace('/\D+/', '', (string) $phone) : null;
@endphp

<div class="fi-section overflow-hidden rounded-lg bg-white dark:bg-gray-900">
    <ul class="divide-y divide-gray-200 dark:divide-gray-800">
        <li class="flex items-center gap-3 px-4 py-3">
            <x-filament::icon icon="heroicon-o-phone" class="h-5 w-5 text-gray-400" />
            @if (filled($phone))
                <a href="tel:{{ $phone }}" dir="ltr" class="text-sm font-medium text-primary-600 hover:underline">
                    {{ $phone }}
                </a>
            @else
                <span class="text-sm text-gray-500">لا يوجد رقم هاتف</span>
            @endif
        </li>
        <li class="flex items-center gap-3 px-4 py-3">
            <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-5 w-5 text-gray-400" />
            @if (filled($whatsapp))
                <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer"
                    class="text-sm font-medium text-primary-600 hover:underline">
                    واتساب
                </a>
            @else
                <span class="text-sm text-gray-500">لا يوجد رقم واتساب</span>
            @endif
        </li>
        <li class="flex items-center gap-3 px-4 py-3">
            <x-filament::icon icon="heroicon-o-envelope" class="h-5 w-5 text-gray-400" />
            <a href="mailto:{{ $tenant->email }}" class="text-sm font-medium text-primary-600 hover:underline">
                {{ $tenant->email }}
            </a>
        </li>
    </ul>
</div>
