<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Services\NotificationPreferences;
use Illuminate\Notifications\Notification;

/**
 * Base for every in-app / email notification in the system (Phase 3). Laravel-native — extends the framework's
 * {@see Notification}; the `database` channel writes to the `notifications` table and the feed reads it back
 * per-user. Each concrete notification declares:
 *   • {@see eventKey()} — the preference key ({@see NotificationPreferences} decides which channels a user allows), and
 *   • {@see data()} — the stored payload the feed renders (title / context / url / category).
 *
 * ⚠️ CHANNELS ARE PREFERENCE-GATED (Sub-gate 2). {@see via()} asks {@see NotificationPreferences::allows()} per
 * channel, so an OFF toggle genuinely suppresses this notification — it is not cosmetic. Always-on (security)
 * events can't be suppressed, but that rule lives in the catalog + allows() (not here) — so nothing in this base
 * special-cases it. The `mail` channel is added in Sub-gate 4 (when the Mailables exist), gated the same way on
 * the `email` preference.
 */
abstract class FeedNotification extends Notification
{
    /** The preference key (snake_case) — the event a user toggles on/off (must be a {@see NotificationPreferences::CATALOG} key). */
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
     * The channels to deliver on — the intersection of what this notification supports and what the notifiable's
     * preferences ALLOW. An off `in_app` toggle drops 'database' → nothing is written to the feed (genuine
     * suppression); an always-on event is never dropped because {@see NotificationPreferences::allows()}
     * short-circuits it to true. Sub-gate 4 adds 'mail' here, gated identically on the `email` preference.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $prefs = app(NotificationPreferences::class);
        $channels = [];

        if ($prefs->allows($notifiable, $this->eventKey(), 'in_app')) {
            $channels[] = 'database';
        }

        return $channels;
    }

    /**
     * @return array{title: string, context: string, url: string, category: string}
     */
    public function toArray(object $notifiable): array
    {
        return $this->data($notifiable);
    }
}
