<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Models\Setting;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('categories')
                    ->label('التصنيفات')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->label('اسم المنتج')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('الوصف')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->label('السعر')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(999999.99)
                    ->step(0.01)
                    ->suffix(fn (): string => ' '.Setting::currencyFor(Filament::getTenant())),
                FileUpload::make('image')
                    ->label('الصورة')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('items')
                    ->maxSize(2048),
                Toggle::make('is_available')
                    ->label('متاح')
                    ->default(true),
                Toggle::make('is_featured')
                    ->label('مميز')
                    ->helperText('المنتجات المميزة ممكن تظهر بشكل بارز في القائمة العامة.')
                    ->default(false),
                TextInput::make('sort_order')
                    ->label('الترتيب')
                    ->integer()
                    ->default(0)
                    ->minValue(0),
            ]);
    }
}
