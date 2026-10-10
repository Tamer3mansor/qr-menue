<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        $isCreating = $schema->getLivewire() instanceof CreateRecord;

        $components = [
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('domain')
                ->label('Domain (slug)')
                ->maxLength(255)
                ->unique(table: User::class, column: 'domain', ignoreRecord: true)
                ->helperText('Optional. The menu is always reachable by customer id, so a domain only adds a readable address.')
                ->placeholder('kebab-palace'),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->helperText('Inactive customers lose panel access and their public menu returns the subscription page.'),
            DateTimePicker::make('subscription_expires_at')
                ->label('Subscription expires at')
                ->seconds(false)
                // A new tenant gets a year. Editing must never invent an expiry
                // for a subscription that was deliberately left open ended.
                ->default(fn (): mixed => $isCreating ? now()->addYear() : null)
                ->helperText('Leave empty for a subscription that never expires.'),
            TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                // Required while creating; on edit this same call becomes
                // Filament's `nullable` rule, so a blank field is allowed
                // through untouched.
                ->required($isCreating)
                ->minLength(8)
                ->confirmed()
                // A blank password is dropped from the dehydrated data, so an
                // untouched field never overwrites the stored hash.
                ->dehydrated(fn (mixed $state): bool => filled($state))
                ->helperText($isCreating
                    ? 'At least 8 characters. The customer receives it by email.'
                    : 'At least 8 characters. Leave empty to keep the current password.'),
            TextInput::make('password_confirmation')
                ->label('Confirm password')
                ->password()
                ->revealable()
                ->dehydrated(false)
                ->visible(fn (Get $get): bool => filled($get('password'))),
        ];

        return $schema->components($components);
    }
}
