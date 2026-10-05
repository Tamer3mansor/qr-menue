<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Tables;

use App\Filament\SuperAdmin\Pages\StopImpersonating;
use App\Models\User;
use App\Services\QrCodeService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextInputColumn::make('domain')
                    ->label('Domain')
                    ->rules(['nullable', 'string', 'max:255'])
                    ->searchable(),
                ToggleColumn::make('is_active')
                    ->sortable(),
                TextColumn::make('subscription_expires_at')
                    ->label('Subscription expires at')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable()
                    ->color(fn (User $record): ?string => $record->isExpiringSoon() ? 'danger' : null),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                self::regenerateQrAction(),
                self::impersonateAction(),
                DeleteAction::make(),
            ]);
    }

    /**
     * The printed code is addressed by id, so this refreshes it rather than
     * re-pointing it at a new slug.
     */
    private static function regenerateQrAction(): Action
    {
        return Action::make('regenerateQr')
            ->label('Regenerate QR')
            ->icon(Heroicon::OutlinedQrCode)
            ->requiresConfirmation()
            ->action(function (User $record, QrCodeService $qrCodes): void {
                $qrCode = $qrCodes->generateForTenant($record);

                Notification::make()
                    ->title('QR code regenerated')
                    ->body($qrCode->url)
                    ->success()
                    ->send();
            });
    }

    /**
     * Signs the super admin into the customer panel as the tenant. The super
     * admin's own session uses a separate guard and therefore survives, so they
     * can always come back through the stop impersonating action.
     */
    private static function impersonateAction(): Action
    {
        return Action::make('impersonate')
            ->label('Login As')
            ->icon(Heroicon::OutlinedArrowRightOnRectangle)
            ->color('warning')
            ->requiresConfirmation()
            ->action(function (User $record): void {
                $adminPanel = Filament::getPanel('admin');

                session()->regenerate();
                session()->put(StopImpersonating::SESSION_KEY, Filament::auth()->id());

                $adminPanel->auth()->login($record);

                $this->redirect($adminPanel->getUrl($record), navigate: false);
            });
    }
}
