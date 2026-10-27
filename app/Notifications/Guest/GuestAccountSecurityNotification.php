<?php

declare(strict_types=1);

namespace App\Notifications\Guest;

use App\Mail\AccountDeletionScheduledMail;
use App\Notifications\FeedNotification;
use App\Services\GuestDeletionService;
use App\Services\NotificationPreferences;

/**
 * G8 — an account-security event on the guest's own account: their deletion request was scheduled, or
 * cancelled. Fires from the live {@see GuestDeletionService}.
 *
 * ⚠️ ADDITIVE — {@see AccountDeletionScheduledMail} still sends; that send site is untouched.
 *
 * ⚠️ ALWAYS-ON, like the owner's `account_security`. {@see NotificationPreferences::allows()}
 * short-circuits an always-on event to true, so this one cannot be suppressed. That is deliberate: a pending
 * erasure of your own account is not a preference. ⚠️ It also means `email_default` is NOT consulted for this
 * key — the always_on branch returns before the default lookup — so this type is the ONE place the
 * double-email guard cannot be enforced by the catalog default alone. It is enforced instead by the
 * notification declaring only the database channel matters to it, and pinned by test: the erasure email must
 * still fire exactly once.
 *
 * ⚠️ #28 — the account is the notifiable's own; no other person appears. The row deliberately carries no
 * scheduled DATE: the guest's own account page reads the authoritative one, and a date copied into a pushed
 * payload is a second source that can go stale if the request is cancelled and re-made (§N.15.2 — when the
 * true answer varies, route to the authoritative screen rather than restate it).
 */
class GuestAccountSecurityNotification extends FeedNotification
{
    /** @param  'scheduled'|'cancelled'  $change */
    public function __construct(private string $change) {}

    public function eventKey(): string
    {
        return 'guest_account_security';
    }

    public function category(): string
    {
        return 'account';
    }

    public function data(object $notifiable): array
    {
        $scheduled = $this->change === 'scheduled';

        return [
            'title' => $scheduled
                ? $this->t('title', 'php/notifications/guest/guest_account_security_notification.title_scheduled')
                : $this->t('title', 'php/notifications/guest/guest_account_security_notification.title_cancelled'),
            // ⚠️ No date AND no booking COUNT here, for the same reason: this row is written once and the
            // account page holds the authoritative figures, which move if the request is cancelled and re-made.
            // The CONSEQUENCE is named, though — a guest should not learn from the account page alone that a
            // pending erase cancels their stays.
            'context' => $scheduled
                ? $this->t('context', 'php/notifications/guest/guest_account_security_notification.context_scheduled')
                : $this->t('context', 'php/notifications/guest/guest_account_security_notification.context_cancelled'),
            'url' => route('guest.account', [], false),
            'category' => 'account',
        ];
    }
}
