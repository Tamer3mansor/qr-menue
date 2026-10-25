<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->header(fn (): View => view('filament.orders.stats', [
                'todayOrders' => self::todayQuery()->count(),
                'pendingOrders' => self::todayQuery()->where('status', 'pending')->count(),
                'todayTotal' => (float) self::todayQuery()->sum('total'),
                'currency' => self::currency(),
            ]))
            ->columns([
                TextColumn::make('order_number')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('اسم العميل')
                    ->searchable(),
                TextColumn::make('customer_phone')
                    ->label('رقم الهاتف')
                    ->copyable(),
                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money(fn (Order $record): string => Setting::currencyFor($record->user))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (Order $record): string => $record->statusLabel())
                    ->color(fn (Order $record): string => $record->statusColor()),
                TextColumn::make('created_at')
                    ->label('وقت الطلب')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(Order::statusOptions()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->label('عرض')
                    ->modalHeading(fn (Order $record): string => 'طلب '.$record->order_number)
                    ->schema([
                        Section::make('بيانات الطلب')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('order_number')
                                    ->label('رقم الطلب'),
                                TextEntry::make('status')
                                    ->label('الحالة')
                                    ->badge()
                                    ->state(fn (Order $record): string => $record->statusLabel())
                                    ->color(fn (Order $record): string => $record->statusColor()),
                                TextEntry::make('customer_name')
                                    ->label('اسم العميل'),
                                TextEntry::make('customer_phone')
                                    ->label('رقم الهاتف'),
                                TextEntry::make('total')
                                    ->label('الإجمالي')
                                    ->state(fn (Order $record): string => Setting::currencyFor($record->user).' '.number_format((float) $record->total, 2)),
                                TextEntry::make('created_at')
                                    ->label('وقت الطلب')
                                    ->dateTime(),
                                TextEntry::make('notes')
                                    ->label('ملاحظات')
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ]),
                        Section::make('الأصناف')
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->hiddenLabel()
                                    ->columns(4)
                                    ->schema([
                                        TextEntry::make('item_name')
                                            ->label('الصنف'),
                                        TextEntry::make('quantity')
                                            ->label('الكمية'),
                                        TextEntry::make('item_price')
                                            ->label('السعر')
                                            ->numeric(2),
                                        TextEntry::make('subtotal')
                                            ->label('الإجمالي')
                                            ->numeric(2),
                                    ]),
                            ]),
                    ]),
                Action::make('updateStatus')
                    ->label('تحديث الحالة')
                    ->icon('heroicon-o-arrow-path')
                    ->modalHeading('تحديث حالة الطلب')
                    ->modalDescription('اختر الحالة الجديدة ثم أكد التحديث.')
                    ->modalSubmitActionLabel('تحديث')
                    ->fillForm(fn (Order $record): array => ['status' => $record->status])
                    ->schema([
                        Select::make('status')
                            ->label('الحالة')
                            ->options(Order::statusOptions())
                            ->required()
                            ->native(false),
                    ])
                    ->action(fn (Order $record, array $data) => $record->update(['status' => $data['status']]))
                    ->successNotificationTitle('تم تحديث حالة الطلب'),
            ]);
    }

    /**
     * Today's orders for the tenant viewing the table.
     */
    protected static function todayQuery(): Builder
    {
        return Order::query()
            ->where('user_id', self::tenantId())
            ->whereDate('created_at', today());
    }

    protected static function tenantId(): ?int
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof User) {
            return $tenant->getKey();
        }

        return auth()->id();
    }

    protected static function currency(): string
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof User ? Setting::currencyFor($tenant) : Setting::DEFAULT_CURRENCY;
    }
}
