<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\TenantDomainChangedNotification;
use App\Services\QrCodeService;

/**
 * Keeps a tenant's menu address in step with their domain.
 *
 * The regeneration lives here rather than on a resource page so that every path
 * which can change the domain is covered: the edit page, inline editing in the
 * table, and anything added later.
 */
class UserObserver
{
    public function __construct(protected QrCodeService $qrCodes) {}

    /**
     * Gives every new tenant a QR code without anyone having to ask for one.
     */
    public function created(User $user): void
    {
        $this->qrCodes->generateForTenant($user);
    }

    /**
     * Tells the platform when the menu address moves.
     *
     * The printed code is addressed by id, so it keeps working across the change
     * and is only rewritten to keep the stored url in step with the app url.
     */
    public function updated(User $user): void
    {
        if (! $user->wasChanged('domain') || ! $this->domainChanged($user)) {
            return;
        }

        $previousDomain = filled($user->getOriginal('domain')) ? $user->getOriginal('domain') : null;

        $qrCode = $this->qrCodes->generateForTenant($user);

        User::query()
            ->where('is_super_admin', true)
            ->each(fn (User $superAdmin) => $superAdmin->notify(
                new TenantDomainChangedNotification(
                    tenantId: (int) $user->getKey(),
                    tenantName: (string) $user->name,
                    previousDomain: $previousDomain,
                    newDomain: $user->domain,
                    qrCodeUrl: (string) $qrCode->url,
                )
            ));
    }

    /**
     * An empty value means "no domain" on both sides, so switching between blank
     * values is not mistaken for a change.
     */
    private function domainChanged(User $user): bool
    {
        return (filled($user->getOriginal('domain')) ? $user->getOriginal('domain') : null)
            !== (filled($user->domain) ? $user->domain : null);
    }
}
