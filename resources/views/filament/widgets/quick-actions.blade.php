<x-filament-widgets::widget class="fi-wi-quick-actions">
    <x-filament::section heading="إجراءات سريعة" icon="heroicon-o-bolt">
        <div class="flex flex-wrap gap-3">
            <x-filament::button tag="a" :href="$itemsCreateUrl" icon="heroicon-o-plus">
                إضافة منتج
            </x-filament::button>

            <x-filament::button tag="a" :href="$offersCreateUrl" icon="heroicon-o-tag">
                إضافة عرض
            </x-filament::button>

            <x-filament::button tag="a" :href="$menuUrl" icon="heroicon-o-qr-code" color="gray" outlined>
                عرض المنيو
            </x-filament::button>

            <x-filament::button tag="a" :href="$siteUrl" icon="heroicon-o-globe-alt" color="gray" outlined>
                عرض الموقع
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
