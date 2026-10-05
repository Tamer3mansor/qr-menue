<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
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
        ];

        if ($isCreating) {
            $components[] = TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                ->required()
                ->minLength(8)
                ->helperText('At least 8 characters. The customer receives it by email.');
        }

        return $schema->components($components);
    }
}
