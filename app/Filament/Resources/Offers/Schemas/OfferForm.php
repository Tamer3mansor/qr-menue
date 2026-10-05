<?php

namespace App\Filament\Resources\Offers\Schemas;

use App\Models\Item;
use App\Models\Setting;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('item_id')
                    ->relationship('item', 'title')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->hint(fn (Get $get): ?string => self::originalPriceHint($get('item_id'))),
                TextInput::make('title')
                    ->maxLength(255)
                    ->placeholder('عرض اليوم'),
                TextInput::make('offer_price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(999999.99)
                    ->step(0.01)
                    ->suffix(fn (): string => ' '.Setting::currencyFor(Filament::getTenant()))
                    ->rule(function (Get $get): \Closure {
                        return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                            $item = Item::find($get('item_id'));

                            if (! $item || blank($value)) {
                                return;
                            }

                            if ((float) $value >= (float) $item->price) {
                                $fail('The offer price must be lower than the item price.');
                            }
                        };
                    }),
                Toggle::make('is_active')
                    ->default(true),
                DateTimePicker::make('expires_at')
                    ->seconds(false)
                    ->minDate(now()),
            ]);
    }

    protected static function originalPriceHint(mixed $itemId): ?string
    {
        $item = filled($itemId) ? Item::find($itemId) : null;

        if (! $item) {
            return null;
        }

        return 'Original price: '.Setting::currencyFor(Filament::getTenant()).' '.$item->price;
    }
}
