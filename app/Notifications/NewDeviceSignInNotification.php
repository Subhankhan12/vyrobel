<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Listeners\NotifyNewDeviceSignIn;
use App\Support\Security\NewDeviceSignIn;

/**
 * "A new device signed in to your account" — the login alert (2026-10-24, the founder's design), sent by
 * {@see NotifyNewDeviceSignIn} when {@see NewDeviceSignIn::isNew()} says the device is new to the account.
 *
 * It is the person's own sign-in, so it goes to them alone: the bell always (unless a staff member switched it off), and
 * email through the same preference gate as every notification — which, while Vyrobel sends no email, sends nothing. The
 * payload is the device as the log names it, the IP and the time: no place (there is no location service), no session id.
 */
class NewDeviceSignInNotification extends FeedNotification
{
    public function __construct(
        private string $eventKey,
        private string $device,
        private ?string $ip,
        private string $when,
        private string $securityUrl,
    ) {}

    public function eventKey(): string
    {
        return $this->eventKey;
    }

    public function category(): string
    {
        return 'account';
    }

    public function data(object $notifiable): array
    {
        return [
            'title' => $this->t('title', 'php/notifications/new_device_sign_in_notification.title', ['device' => $this->device]),
            'context' => $this->ip !== null
                ? $this->t('context', 'php/notifications/new_device_sign_in_notification.context_ip', ['ip' => $this->ip, 'when' => $this->when])
                : $this->t('context', 'php/notifications/new_device_sign_in_notification.context', ['when' => $this->when]),
            'url' => $this->securityUrl,
            'category' => 'account',
        ];
    }
}
