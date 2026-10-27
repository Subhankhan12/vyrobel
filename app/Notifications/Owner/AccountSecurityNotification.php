<?php

declare(strict_types=1);

namespace App\Notifications\Owner;

use App\Notifications\FeedNotification;
use App\Services\NotificationPreferences;

/**
 * Owner: a security-sensitive change to their account (Phase 3) — dispatched on the REAL
 * `owner.payout_method_changed` domain event (a bank/payout-method add, default change, or removal). This is the
 * ALWAYS-ON "Account & security" event: {@see NotificationPreferences} flags it always_on, so it
 * can never be suppressed. Account category; links to the payout-method screen.
 *
 * ⚠️ NO bank values in the payload or the notification body — the event carries only WHICH change happened
 * (added / default_changed / removed), never the IBAN. Sign-in ("new device") alerts are the OTHER half of this
 * group and are deferred until new-device detection exists (there is no such event today).
 */
class AccountSecurityNotification extends FeedNotification
{
    /** @param string $change one of added|default_changed|removed (the `change` from the domain-event payload); an unknown/empty value renders a generic message. */
    public function __construct(private string $change) {}

    public function eventKey(): string
    {
        return 'account_security';
    }

    public function category(): string
    {
        return 'account';
    }

    public function data(object $notifiable): array
    {
        $context = match ($this->change) {
            'added' => $this->t('context', 'php/notifications/owner/account_security_notification.context_added'),
            'default_changed' => $this->t('context', 'php/notifications/owner/account_security_notification.context_default_changed'),
            'removed' => $this->t('context', 'php/notifications/owner/account_security_notification.context_removed'),
            default => $this->t('context', 'php/notifications/owner/account_security_notification.context_updated'),
        };

        return [
            'title' => $this->t('title', 'php/notifications/owner/account_security_notification.title'),
            'context' => $context,
            'url' => route('owner.payout-method', [], false),
            'category' => 'account',
        ];
    }
}
