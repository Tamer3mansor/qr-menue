<div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-sm text-gray-500 dark:text-gray-400">طلبات اليوم</p>
        <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $todayOrders }}</p>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-sm text-gray-500 dark:text-gray-400">طلبات في الانتظار</p>
        <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $pendingOrders }}</p>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-sm text-gray-500 dark:text-gray-400">إجمالي اليوم</p>
        <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $currency }} {{ number_format($todayTotal, 2) }}</p>
    </div>
</div>
