<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Pages;

use App\Filament\SuperAdmin\Resources\Tenants\TenantResource;
use App\Services\TenantProvisioner;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * Held only long enough to hand the customer their password. The model casts
     * it to a hash on the way into the database, so this is never stored.
     */
    private ?string $plainPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->plainPassword = (string) $data['password'];

        return $data;
    }

    protected function afterCreate(): void
    {
        app(TenantProvisioner::class)->provision($this->record, (string) $this->plainPassword);

        Notification::make()
            ->title('Tenant created')
            ->success()
            ->send();
    }
}
