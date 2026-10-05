<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sends a brand new customer the credentials for their account.
 *
 * The password is deliberately sent in plain text because this replaces the
 * "customer sets their own password" flow that has not been built yet. It
 * should be replaced by a one time password reset link.
 */
class TenantWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $plainPassword) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * The exact lines the customer receives.
     *
     * @return list<string>
     */
    public function bodyFor(User $notifiable): array
    {
        return [
            'تم إنشاء حسابك في QR Menu',
            'الإيميل: '.$notifiable->email,
            'الباسوورد: '.$this->plainPassword,
            'رابط الدخول: '.rtrim((string) config('app.url'), '/').'/admin',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your QR Menu account is ready');

        foreach ($this->bodyFor($notifiable) as $line) {
            $message->line($line);
        }

        return $message;
    }
}
