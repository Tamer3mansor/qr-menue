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
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(999999.99)
                    ->step(0.01)
                    ->suffix(fn (): string => ' '.Setting::currencyFor(Filament::getTenant())),
                FileUpload::make('image')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('items')
                    ->maxSize(2048),
                Toggle::make('is_available')
                    ->default(true),
                Toggle::make('is_featured')
                    ->label('Featured')
                    ->helperText('Featured items can be highlighted on the public menu.')
                    ->default(false),
                TextInput::make('sort_order')
                    ->integer()
                    ->default(0)
                    ->minValue(0),
            ]);
    }
}
