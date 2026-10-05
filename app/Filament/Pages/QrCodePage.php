<?php

namespace App\Filament\Pages;

use App\Models\QrCode;
use App\Models\User;
use App\Services\QrCodeService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QrCodePage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?string $navigationLabel = 'QR Code';

    protected static ?string $title = 'QR Code';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.qr-code-page';

    public ?int $qrCodeId = null;

    public function mount(): void
    {
        $tenant = $this->tenant();

        $this->qrCodeId = ($tenant->qrCode()->first()
            ?? app(QrCodeService::class)->generateForTenant($tenant))->getKey();
    }

    /**
     * Resolved through the tenant on every request so a tampered id can never
     * surface another tenant's code.
     */
    public function getQrCode(): ?QrCode
    {
        if (blank($this->qrCodeId)) {
            return null;
        }

        return $this->tenant()->qrCode()->whereKey($this->qrCodeId)->first();
    }

    public function regenerate(): void
    {
        $this->qrCodeId = app(QrCodeService::class)
            ->generateForTenant($this->tenant())
            ->getKey();

        Notification::make()
            ->title('QR code regenerated.')
            ->success()
            ->send();
    }

    public function download(): ?StreamedResponse
    {
        $qrCode = $this->getQrCode();

        if (blank($qrCode?->image_path)) {
            Notification::make()
                ->title('There is no QR code to download.')
                ->danger()
                ->send();

            return null;
        }

        return Storage::disk(QrCode::DISK)->download(
            $qrCode->image_path,
            basename($qrCode->image_path),
        );
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerate')
                ->label('Regenerate QR Code')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(fn (): null => $this->regenerate()),
            Action::make('download')
                ->label('Download QR Code')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (): ?StreamedResponse => $this->download()),
        ];
    }

    protected function tenant(): User
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof User) {
            throw new LogicException('The QR code page requires a resolved tenant.');
        }

        return $tenant;
    }
}
