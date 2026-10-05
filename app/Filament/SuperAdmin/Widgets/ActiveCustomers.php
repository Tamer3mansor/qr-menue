<?php

namespace App\Filament\SuperAdmin\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActiveCustomers extends CustomerCountWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        return [
            Stat::make(
                'Active customers',
                $this->customerQuery()->where('is_active', true)->count(),
            )
                ->description($this->description())
                ->icon(Heroicon::OutlinedSignal),
        ];
    }

    /**
     * Being active is not the same as being paid up, so the gap is spelled out.
     */
    private function description(): string
    {
        $total = $this->customerQuery()->count();
        $active = $this->customerQuery()->where('is_active', true)->count();

        return "{$active} of {$total} customers";
    }
}
