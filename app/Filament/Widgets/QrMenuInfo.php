<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class QrMenuInfo extends Widget
{
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.qr-menu-info';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'brandName' => Filament::getBrandName(),
            'logoUrl' => is_file(public_path('images/logo.png')) ? asset('images/logo.png') : null,
        ];
    }
}
