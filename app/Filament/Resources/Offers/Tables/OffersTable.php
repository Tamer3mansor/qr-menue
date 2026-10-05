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
                    ->label('Title')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item.title')
                    ->label('Item')
                    ->description(fn (Offer $record): string => $record->item === null
                        ? '—'
                        : 'Original price: '.Setting::currencyFor($record->user).' '.$record->item->price)
                    ->searchable(),
                TextColumn::make('offer_price')
                    ->numeric()
                    ->suffix(fn (Offer $record): string => ' '.Setting::currencyFor($record->user))
                    ->sortable(),
                TextColumn::make('discount')
                    ->label('Discount')
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
                ToggleColumn::make('is_active'),
                TextColumn::make('expires_at')
                    ->label('Expires at')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Is active')
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
