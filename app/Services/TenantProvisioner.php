<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\TenantWelcomeNotification;

/**
 * Completes a tenant account once it exists: the settings record the public menu
 * reads, a QR code, and the credentials email.
 */
class TenantProvisioner
{
    public function __construct(protected QrCodeService $qrCodes) {}

    public function provision(User $tenant, string $plainPassword): void
    {
        $this->createSettings($tenant);

        $this->ensureQrCode($tenant);

        $tenant->notify(new TenantWelcomeNotification($plainPassword));
    }

    private function createSettings(User $tenant): void
    {
        Setting::query()->firstOrCreate(
            ['user_id' => $tenant->getKey()],
            ['restaurant_name' => $tenant->name],
        );
    }

    /**
     * UserObserver already renders a code the moment the tenant is created, so
     * this only fills the gap instead of writing the same file twice.
     */
    private function ensureQrCode(User $tenant): void
    {
        if ($tenant->qrCode()->exists()) {
            return;
        }

        $this->qrCodes->generateForTenant($tenant);
    }
}
