<?php

namespace App\Filament\SuperAdmin\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EstimatedRevenue extends CustomerCountWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        return [
            Stat::make(
                'Estimated monthly revenue',
                $this->estimate(),
            )
                ->description($this->description())
                ->icon(Heroicon::OutlinedBanknotes),
        ];
    }

    private function estimate(): float
    {
        $activeCustomers = $this->customerQuery()->where('is_active', true)->count();

        return $activeCustomers * $this->planPrice();
    }

    private function planPrice(): float
    {
        return (float) config('subscription.plan_price');
    }

    /**
     * The figure is an estimate derived from a configured price, not from
     * payments, and the description says so wherever the price is still unset.
     */
    private function description(): string
    {
        $price = $this->planPrice();

        if ($price <= 0) {
            return 'Set SUBSCRIPTION_PLAN_PRICE to estimate revenue';
        }

        return 'Active customers at '.number_format($price, 2).' each';
    }
}
