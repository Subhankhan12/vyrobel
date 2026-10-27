<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Services\NotificationPreferences;
use App\Support\Notifications\QuietHours;
use App\Support\Tenancy\TenantContext;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for every in-app / email notification in the system (Phase 3). Laravel-native — extends the framework's
 * {@see Notification}; the `database` channel writes to the `notifications` table and the feed reads it back
 * per-user. Each concrete notification declares:
 *   • {@see eventKey()} — the preference key ({@see NotificationPreferences} decides which channels a user allows), and
 *   • {@see data()} — the stored payload the feed renders (title / context / url / category).
 *
 * ⚠️ CHANNELS ARE PREFERENCE-GATED (Sub-gate 2, both channels live Sub-gate 5). {@see via()} asks
 * {@see NotificationPreferences::allows()} per channel, so an OFF toggle genuinely suppresses this notification on
 * that channel — it is not cosmetic, on EITHER in-app or email. Always-on (security) events can't be suppressed,
 * but that rule lives in the catalog + allows() (not here) — so nothing in this base special-cases it.
 */
abstract class FeedNotification extends Notification
{
    /**
     * I18N-01 — the key and parameters behind each text data() returned, recorded by {@see t()} and stored beside the
     * sentence (`data.i18n`), so the feed can show the row in its READER's language later (NotificationFeed). Parameters
     * must be plain values (names, dates already formatted, amounts already formatted).
     *
     * @var array<string, array{0: string, 1: array<string, scalar|null>, 2?: int}>
     */
    private array $i18n = [];

    /**
     * Translate one of data()'s texts and remember how: `'title' => $this->t('title', 'php/notifications/x.title', [...])`.
     * Laravel sends each notification in its recipient's language (HasLocalePreference), so the stored sentence is theirs.
     *
     * @param  array<string, scalar|null>  $params
     */
    protected function t(string $field, string $key, array $params = []): string
    {
        $this->i18n[$field] = [$key, $params];

        return (string) __($key, $params);
    }

    /**
     * The plural form of {@see t()}: `'context' => $this->tc('context', 'php/notifications/x.nights', $n, ['unit' => …])`. The count is
     * stored with the key, so the feed picks the right plural form in the READER's language too.
     *
     * @param  array<string, scalar|null>  $params
     */
    protected function tc(string $field, string $key, int $count, array $params = []): string
    {
        $params = ['count' => $count] + $params;
        $this->i18n[$field] = [$key, $params, $count];

        return (string) trans_choice($key, $count, $params);
    }

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
     * preferences ALLOW. An off `in_app` toggle drops 'database' → nothing is written to the feed; an off `email`
     * toggle drops 'mail' → no email is sent (genuine suppression on BOTH channels). An always-on event is never
     * dropped because {@see NotificationPreferences::allows()} short-circuits it to true. This is the ONE gate for
     * both channels — the SG2 email toggle is not cosmetic: mutation-proven that off → no email.
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
        if ($prefs->allows($notifiable, $this->eventKey(), 'email')) {
            // QUIET HOURS (Wave B · GATE 6) — hold routine EMAIL during the user's quiet window; the in-app feed
            // row above is unaffected (nothing is lost). Always-on (security) events ignore quiet hours entirely.
            $held = $notifiable instanceof User
                && ! NotificationPreferences::isAlwaysOn($this->eventKey())
                && QuietHours::active($notifiable);
            if (! $held) {
                $channels[] = 'mail';
            }
        }

        return $channels;
    }

    /**
     * The email form of this notification (Sub-gate 5) — the SAME title/context/link as the in-app feed row,
     * rendered through the shared branded email layout. The mail channel routes it to the notifiable's own email;
     * it is only ever built when {@see via()} allowed the `email` channel. The feed's RELATIVE url is made
     * absolute for the email.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->data($notifiable);

        return (new MailMessage)
            ->subject($data['title'])
            ->view('emails.notification', [
                'title' => $data['title'],
                'context' => $data['context'],
                'url' => url($data['url']),
                'category' => $data['category'],
            ]);
    }

    /**
     * ⚠️ ADMIN 05 — the stored row also names the organisation it is about (when there is one): a person can belong to
     * several, and deleting one must find ITS notifications in their feed without touching the rest.
     *
     * @return array{title: string, context: string, url: string, category: string, organization_id?: string}
     */
    public function toArray(object $notifiable): array
    {
        $organization = app(TenantContext::class)->id();

        $data = $this->data($notifiable);

        return $data + ($organization !== null ? ['organization_id' => $organization] : []) + ($this->i18n !== [] ? ['i18n' => $this->i18n] : []);
    }
}
