<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Items\ItemResource;
use App\Filament\Resources\Offers\OfferResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use LogicException;

class QuickActions extends Widget
{
    protected static ?int $sort = 2;

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.quick-actions';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, string>
     */
    protected function getViewData(): array
    {
        $tenant = $this->tenant();
        $slug = $tenant->domain ?: $tenant->getKey();

        return [
            'itemsCreateUrl' => ItemResource::getUrl('create', tenant: $tenant),
            'offersCreateUrl' => OfferResource::getUrl('create', tenant: $tenant),
            'menuUrl' => route('menu.show', $slug),
            'siteUrl' => route('site.show', $slug),
        ];
    }

    protected function tenant(): User
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof User) {
            throw new LogicException('The quick actions widget requires a resolved tenant.');
        }

        return $tenant;
    }
}
