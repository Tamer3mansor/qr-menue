<?php

namespace App\Filament\SuperAdmin\Resources\Tenants;

use App\Filament\SuperAdmin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\SuperAdmin\Resources\Tenants\Pages\EditTenant;
use App\Filament\SuperAdmin\Resources\Tenants\Pages\ListTenants;
use App\Filament\SuperAdmin\Resources\Tenants\Schemas\TenantForm;
use App\Filament\SuperAdmin\Resources\Tenants\Tables\TenantsTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $modelLabel = 'tenant';

    protected static UnitEnum|string|null $navigationGroup = 'Platform';

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    /**
     * Super admins are stored in the same table but are not customers, so they
     * are filtered out of every listing, lookup and relation.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_super_admin', false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}
