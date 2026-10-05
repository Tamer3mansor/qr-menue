<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('You are signed in as a customer') }}
        </x-slot>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('You are browsing the customer panel as a tenant account. Stop impersonating to return to the super admin panel.') }}
        </p>

        <div class="mt-4">
            <x-filament::button wire:click="stop" wire:loading.attr="disabled">
                {{ __('Stop impersonating') }}
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>