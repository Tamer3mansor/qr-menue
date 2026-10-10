<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use LogicException;

class RestaurantStats extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $tenant = $this->tenant();

        return [
            Stat::make(
                'إجمالي المنتجات',
                Item::query()->where('user_id', $tenant->getKey())->count(),
            ),
            Stat::make(
                'إجمالي التصنيفات',
                Category::query()->where('user_id', $tenant->getKey())->count(),
            ),
            Stat::make(
                'عدد العروض النشطة',
                Offer::query()->where('user_id', $tenant->getKey())->currentlyActive()->count(),
            ),
            Stat::make('حالة الاشتراك', $this->subscriptionStatus($tenant))
                ->color($this->subscriptionColor($tenant)),
        ];
    }

    /**
     * A null expiry means the subscription never lapses, so only a real date
     * can be reported as running or expired.
     */
    protected function subscriptionStatus(User $tenant): string
    {
        if ($tenant->subscription_expires_at === null) {
            return 'دائم ✓';
        }

        if ($tenant->subscription_expires_at->isFuture()) {
            return 'نشط - ينتهي '.$tenant->subscription_expires_at->format('Y-m-d');
        }

        return 'منتهي ✗';
    }

    protected function subscriptionColor(User $tenant): string
    {
        if ($tenant->subscription_expires_at === null) {
            return 'success';
        }

        return $tenant->subscription_expires_at->isFuture() ? 'success' : 'danger';
    }

    protected function tenant(): User
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof User) {
            throw new LogicException('The restaurant stats widget requires a resolved tenant.');
        }

        return $tenant;
    }
}
