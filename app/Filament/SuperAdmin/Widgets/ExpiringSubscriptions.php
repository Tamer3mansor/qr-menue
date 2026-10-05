<?php

namespace App\Filament\SuperAdmin\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpiringSubscriptions extends CustomerCountWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        return [
            Stat::make(
                'Expiring within 30 days',
                $this->customerQuery()->subscriptionExpiringWithin()->count(),
            )
                ->description('A null expiry never lapses and is not counted')
                ->color('warning')
                ->icon(Heroicon::OutlinedClock),
        ];
    }
}
