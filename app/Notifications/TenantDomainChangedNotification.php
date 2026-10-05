<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Tells a super admin that a tenant's menu address moved, because every printed
 * QR code for that tenant now points at the previous address.
 *
 * This arrives on the database channel rather than as a Filament toast: the
 * regeneration happens inside a model observer, which has no idea whether it was
 * triggered from a panel page, an inline table edit, a queued job or the CLI.
 *
 * The tenant is described by value rather than by model reference, because the
 * notifiable is the super admin receiving the notification, not the tenant the
 * notification is about.
 */
class TenantDomainChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $tenantId,
        private readonly string $tenantName,
        private readonly ?string $previousDomain,
        private readonly ?string $newDomain,
        private readonly string $qrCodeUrl,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $previous = $this->labelFor($this->previousDomain);
        $new = $this->labelFor($this->newDomain);

        return [
            'tenant_id' => $this->tenantId,
            'tenant_name' => $this->tenantName,
            'previous_domain' => $this->previousDomain,
            'new_domain' => $this->newDomain,
            'previous_address' => $previous,
            'new_address' => $new,
            'qr_code_url' => $this->qrCodeUrl,
            'message' => "The QR code for {$this->tenantName} was regenerated because the menu address changed from {$previous} to {$new}.",
        ];
    }

    /**
     * A tenant without a domain is addressed by id, so that is how the address
     * reads in the message.
     */
    private function labelFor(?string $domain): string
    {
        return filled($domain) ? $domain : (string) $this->tenantId;
    }
}
