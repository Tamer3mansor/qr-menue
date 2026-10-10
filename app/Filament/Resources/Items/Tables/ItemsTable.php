<?php

namespace App\Filament\Resources\Items\Tables;

use App\Models\Item;
use App\Models\Setting;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->disk('public')
                    ->height(40),
                TextColumn::make('title')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('categories.name')
                    ->label('التصنيفات')
                    ->badge()
                    ->searchable(),
                TextColumn::make('price')
                    ->label('السعر')
                    ->numeric()
                    ->suffix(fn (Item $record): string => ' '.Setting::currencyFor($record->user))
                    ->sortable(),
                ToggleColumn::make('is_available')
                    ->label('متاح')
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('مميز')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->trueColor('success')
                    ->falseIcon('heroicon-o-star')
                    ->falseColor('gray'),
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('categories')
                    ->label('التصنيفات')
                    ->relationship('categories', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_available')
                    ->label('التوفر'),
                TernaryFilter::make('is_featured')
                    ->label('مميز'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
