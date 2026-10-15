<?php

namespace Tests\Feature;

use App\Filament\Widgets\QrMenuInfo;
use Filament\Facades\Filament;
use Filament\Widgets\FilamentInfoWidget;
use Livewire\Livewire;
use Tests\TestCase;

class PanelBrandingTest extends TestCase
{
    public function test_the_admin_panel_is_branded_as_qr_menu(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertSame('QR Menu', $panel->getBrandName());
    }

    public function test_the_admin_dashboard_replaces_the_filament_info_widget_with_the_qr_menu_widget(): void
    {
        $widgets = Filament::getPanel('admin')->getWidgets();

        $this->assertContains(QrMenuInfo::class, $widgets);
        $this->assertNotContains(FilamentInfoWidget::class, $widgets);
    }

    public function test_the_qr_menu_info_widget_renders_the_brand_name(): void
    {
        Livewire::test(QrMenuInfo::class)
            ->assertSee('QR Menu');
    }
}
