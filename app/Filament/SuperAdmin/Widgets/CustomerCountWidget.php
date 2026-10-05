<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Database\Eloquent\Builder;

abstract class CustomerCountWidget extends StatsOverviewWidget
{
    /**
     * Super admins live in the users table but are not customers.
     */
    protected function customerQuery(): Builder
    {
        return User::query()->where('is_super_admin', false);
    }
}
