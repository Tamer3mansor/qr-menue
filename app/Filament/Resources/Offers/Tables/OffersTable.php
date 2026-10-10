<?php

namespace App\Filament\Resources\Offers\Tables;

use App\Models\Offer;
use App\Models\Setting;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('العنوان')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('items.title')
                    ->label('المنتجات')
                    ->description(fn (Offer $record): string => $record->items->isEmpty()
                        ? '—'
                        : 'السعر الأصلي: '.Setting::currencyFor($record->user).' '.$record->items->min('price'))
                    ->searchable(),
                TextColumn::make('offer_price')
                    ->label('سعر العرض')
                    ->numeric()
                    ->suffix(fn (Offer $record): string => ' '.Setting::currencyFor($record->user))
                    ->sortable(),
                TextColumn::make('discount')
                    ->label('الخصم')
                    ->state(fn (Offer $record): ?int => $record->discountPercentage())
                    ->formatStateUsing(fn (?int $state): string => match (true) {
                        $state === null, $state <= 0 => '—',
                        default => "خصم {$state}%",
                    })
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null, $state <= 0 => 'gray',
                        default => 'success',
                    }),
                ToggleColumn::make('is_active')
                    ->label('مفعل'),
                TextColumn::make('expires_at')
                    ->label('ينتهي في')
                    ->dateTime()
                    ->placeholder('بدون')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('مفعل')
                    ->queries(
                        true: fn ($query) => $query->currentlyActive(),
                        false: fn ($query) => $query->currentlyInactive(),
                    ),
            ])
            ->defaultSort('expires_at')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
