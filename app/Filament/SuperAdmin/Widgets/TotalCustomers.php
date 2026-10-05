<?php

namespace App\Filament\SuperAdmin\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalCustomers extends CustomerCountWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total customers', $this->customerQuery()->count()),
        ];
    }
}
