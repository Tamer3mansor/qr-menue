<?php

namespace App\Filament\Resources\Offers\Schemas;

use App\Models\Item;
use App\Models\Setting;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
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
                Select::make('items')
                    ->label('المنتجات')
                    ->relationship('items', 'title')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->hint(fn (Get $get): ?string => self::originalPriceHint($get('items'))),
                TextInput::make('title')
                    ->label('عنوان العرض')
                    ->maxLength(255)
                    ->placeholder('عرض اليوم'),
                FileUpload::make('image')
                    ->label('الصورة')
                    ->image()
                    ->nullable()
                    ->disk('public')
                    ->directory('offers')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048),
                TextInput::make('offer_price')
                    ->label('سعر العرض')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(999999.99)
                    ->step(0.01)
                    ->suffix(fn (): string => ' '.Setting::currencyFor(Filament::getTenant()))
                    ->rule(function (Get $get): \Closure {
                        return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                            if (blank($value)) {
                                return;
                            }

                            $total = self::totalSelectedPrice($get('items'));

                            if ($total !== null && (float) $value >= (float) $total) {
                                $fail('سعر العرض لازم يكون أقل من مجموع أسعار المنتجات المختارة.');
                            }
                        };
                    }),
                Toggle::make('is_active')
                    ->label('مفعل')
                    ->default(true),
                DateTimePicker::make('expires_at')
                    ->label('تاريخ الانتهاء')
                    ->seconds(false)
                    ->minDate(now()),
            ]);
    }

    /**
     * @param  array<int, int|string>|int|string|null  $itemIds
     */
    protected static function originalPriceHint(mixed $itemIds): ?string
    {
        $total = self::totalSelectedPrice($itemIds);

        if ($total === null) {
            return null;
        }

        return 'السعر الأصلي: '.Setting::currencyFor(Filament::getTenant()).' '.number_format((float) $total, 2);
    }

    /**
     * The offer is measured against everything it contains, so the "original"
     * price is the combined price of every selected item.
     *
     * @param  array<int, int|string>|int|string|null  $itemIds
     */
    protected static function totalSelectedPrice(mixed $itemIds): ?float
    {
        $itemIds = collect($itemIds ?? [])->filter()->all();

        if ($itemIds === []) {
            return null;
        }

        return (float) Item::query()->whereIn('id', $itemIds)->sum('price');
    }
}
