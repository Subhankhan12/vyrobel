<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Base for every in-app / email notification in the system (Phase 3). Laravel-native — extends the framework's
 * {@see Notification}; the `database` channel writes to the `notifications` table and the feed reads it back
 * per-user. Each concrete notification declares:
 *   • {@see eventKey()} — the preference key (Sub-gate 2 uses it to decide which channels a user allows), and
 *   • {@see data()} — the stored payload the feed renders (title / context / url / category).
 *
 * ⚠️ CHANNELS ARE PREFERENCE-GATED — but that gate lives in {@see via()} and is wired in Sub-gate 2 (here it is
 * database-only). ⚠️ Some notifications are ALWAYS-ON (security): {@see alwaysOn()} returns true for those, so
 * Sub-gate 2's preference check can never suppress them.
 */
abstract class FeedNotification extends Notification
{
    /** The preference key (snake_case) — the event a user toggles on/off. */
    abstract public function eventKey(): string;

    /**
     * The category the feed groups/filters by (e.g. 'front_desk', 'finance', 'maintenance', 'account').
     */
    abstract public function category(): string;

    /**
     * The stored payload: title, one-line context, a RELATIVE in-app url, and the category.
     *
     * @return array{title: string, context: string, url: string, category: string}
     */
    abstract public function data(object $notifiable): array;

    /**
     * Security-critical notifications (sign-ins, bank/payout changes) are always-on — Sub-gate 2's preference
     * gate must never suppress them. Overridden to true by those notifications.
     */
    public function alwaysOn(): bool
    {
        return false;
    }

    /**
     * The channels to deliver on. Database-only in Sub-gate 1; Sub-gate 2 overrides this in a shared trait to
     * consult the notifiable's preferences (adding 'mail' when allowed, honouring {@see alwaysOn()}).
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, context: string, url: string, category: string}
     */
    public function toArray(object $notifiable): array
    {
        return $this->data($notifiable);
    }
}
