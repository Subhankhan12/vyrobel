<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\FeedNotification;

/**
 * The per-user notification preference store + THE DELIVERY GATE (Phase 3 · Sub-gate 2).
 *
 * ⚠️ PREFERENCES GENUINELY GATE DELIVERY. {@see allows()} is called from {@see FeedNotification::via()}
 * BEFORE a notification is delivered, so an OFF toggle actually suppresses that notification on that channel — it
 * is never cosmetic. Mutation-proven: make via() ignore this and an off toggle stops suppressing.
 *
 * ⚠️ ALWAYS-ON (security). Events flagged `always_on` (sign-ins, bank/payout changes — the "Account & security"
 * group) can NEVER be suppressed: {@see allows()} short-circuits to true, and {@see save()} forces them enabled.
 *
 * ⚠️ TYPED, not KV. The event_key is validated against this fixed {@see CATALOG} (a constrained set), the channel
 * is an enum, the value a bool — a purpose-built typed store, not the dormant generic `settings` scaffold.
 *
 * SELF-ONLY: writes are always for the passed authenticated User (never a request id).
 */
class NotificationPreferences
{
    /** The channels a preference can gate. `in_app` → the database feed (wired now); `email` → mail (Sub-gate 4). */
    public const CHANNELS = ['in_app', 'email'];

    /**
     * The fixed catalog of preference-able events. audience decides which portal's screen shows it; always_on
     * marks the security-critical ones. Defaults are PER CHANNEL: `default` is the in-app default; `email_default`
     * is the email default — email is selective (opt-in) for routine operational events and on for the financial
     * / security / urgent ones, so activating the email channel (Sub-gate 5) doesn't bury users in mail.
     *
     * @var array<string, array{label: string, description: string, group: string, audience: string, always_on: bool, default: bool, email_default: bool}>
     */
    public const CATALOG = [
        // ── Operator ──
        'new_booking' => ['label' => 'New booking', 'description' => 'A booking is confirmed', 'group' => 'Front desk', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => false],
        'arrivals_today' => ['label' => 'Arrivals due today', 'description' => 'Guests arriving that still need a unit', 'group' => 'Front desk', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => false],
        'balance_due' => ['label' => 'Balance due at checkout', 'description' => 'An outstanding balance at check-out', 'group' => 'Front desk', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => false],
        'housekeeping_behind' => ['label' => 'Housekeeping behind', 'description' => 'Turnovers are running late', 'group' => 'Operations', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => false],
        'urgent_work_order' => ['label' => 'Urgent work order', 'description' => 'A high-priority maintenance ticket opens', 'group' => 'Operations', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => true],
        'unit_out_of_order' => ['label' => 'Unit out of order', 'description' => 'A unit is taken out of service', 'group' => 'Operations', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => false],
        'owner_payout_sent' => ['label' => 'Owner payout sent', 'description' => 'A payout is disbursed to an owner', 'group' => 'Finance', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => true],
        'statement_finalized' => ['label' => 'Statement ready', 'description' => 'An owner statement is finalized', 'group' => 'Finance', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => true],
        'new_review' => ['label' => 'New review', 'description' => 'A guest leaves a review', 'group' => 'Reputation', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => false],
        'low_rating' => ['label' => 'Low rating (under 4★)', 'description' => 'A guest leaves a review rated under 4 stars', 'group' => 'Reputation', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => true],
        'channel_sync_error' => ['label' => 'Channel sync error', 'description' => 'A channel stops syncing', 'group' => 'System', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => true],
        'new_device_login' => ['label' => 'Login from a new device', 'description' => 'Your account signs in somewhere new', 'group' => 'System', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => true],
        'daily_summary' => ['label' => 'Daily morning summary', 'description' => 'A digest of the day at 08:00', 'group' => 'System', 'audience' => 'operator', 'always_on' => false, 'default' => false, 'email_default' => false],
        // ── Owner ──
        'statement_ready' => ['label' => 'Statement ready', 'description' => 'Your monthly statement is finalized', 'group' => 'Earnings', 'audience' => 'owner', 'always_on' => false, 'default' => true, 'email_default' => true],
        'payout_sent' => ['label' => 'Payout sent', 'description' => 'The day money leaves for your account', 'group' => 'Earnings', 'audience' => 'owner', 'always_on' => false, 'default' => true, 'email_default' => true],
        'performance_digest' => ['label' => 'Performance digest', 'description' => 'Occupancy & earnings vs last month', 'group' => 'Performance', 'audience' => 'owner', 'always_on' => false, 'default' => true, 'email_default' => false],
        'terms_changed' => ['label' => 'Terms changed', 'description' => 'A change to commission or fees', 'group' => 'Account', 'audience' => 'owner', 'always_on' => false, 'default' => true, 'email_default' => true],
        'unit_request_reviewed' => ['label' => 'Unit request reviewed', 'description' => 'Your add-a-unit request is approved or declined', 'group' => 'Account', 'audience' => 'owner', 'always_on' => false, 'default' => true, 'email_default' => true],
        'account_security' => ['label' => 'Account & security', 'description' => 'Sign-ins and bank changes — always on', 'group' => 'Account', 'audience' => 'owner', 'always_on' => true, 'default' => true, 'email_default' => true],
    ];

    /**
     * THE GATE: may this notification be delivered to this notifiable on this channel? Unknown events fail OPEN
     * (never silently drop a notification); always_on can't be suppressed; otherwise the user's stored preference,
     * or the catalog default when they have none. A non-User notifiable (a guest email address in Phase 4) uses
     * the default (it has no personal preferences yet).
     */
    public function allows(object $notifiable, string $eventKey, string $channel): bool
    {
        $entry = self::CATALOG[$eventKey] ?? null;
        if ($entry === null) {
            return true;
        }
        if ($entry['always_on']) {
            return true;
        }
        if (! $notifiable instanceof User) {
            return $this->defaultFor($entry, $channel);
        }

        $pref = NotificationPreference::query()
            ->where('user_id', $notifiable->getKey())
            ->where('event_key', $eventKey)
            ->where('channel', $channel)
            ->first();

        return $pref !== null ? (bool) $pref->enabled : $this->defaultFor($entry, $channel);
    }

    /**
     * The default on/off for one channel when a user has no stored row — `email_default` for email, `default` for
     * in-app. Keeps the per-channel default in ONE place so the gate ({@see allows()}) and the screen
     * ({@see statesFor()}) can never disagree about what "no preference yet" means.
     *
     * @param  array{always_on: bool, default: bool, email_default: bool, ...}  $entry
     */
    private function defaultFor(array $entry, string $channel): bool
    {
        return $channel === 'email' ? $entry['email_default'] : $entry['default'];
    }

    /**
     * Persist an audience's toggles for the authenticated user (SELF-ONLY). always_on events are forced enabled,
     * so they can never be turned off.
     *
     * @param  array<string, array<string, bool>>  $enabled  event_key → channel → on
     */
    public function save(User $user, string $audience, array $enabled): void
    {
        foreach (self::catalogFor($audience) as $key => $entry) {
            foreach (self::CHANNELS as $channel) {
                $on = $entry['always_on'] ? true : (bool) ($enabled[$key][$channel] ?? false);

                NotificationPreference::query()->updateOrCreate(
                    ['user_id' => $user->getKey(), 'event_key' => $key, 'channel' => $channel],
                    ['enabled' => $on],
                );
            }
        }
    }

    /**
     * The current on/off state for every (event, channel) in an audience — stored preference over catalog
     * default; always_on forced on. Drives the preferences screen.
     *
     * @return array<string, array<string, bool>>
     */
    public function statesFor(User $user, string $audience): array
    {
        $stored = NotificationPreference::query()
            ->where('user_id', $user->getKey())
            ->get()
            ->keyBy(fn (NotificationPreference $p): string => $p->event_key.'|'.$p->channel);

        $out = [];
        foreach (self::catalogFor($audience) as $key => $entry) {
            foreach (self::CHANNELS as $channel) {
                $pref = $stored->get($key.'|'.$channel);
                $out[$key][$channel] = $entry['always_on']
                    ? true
                    : ($pref instanceof NotificationPreference ? (bool) $pref->enabled : $this->defaultFor($entry, $channel));
            }
        }

        return $out;
    }

    /**
     * The catalog entries for one audience ('operator' | 'owner'), preserving order.
     *
     * @return array<string, array{label: string, description: string, group: string, audience: string, always_on: bool, default: bool, email_default: bool}>
     */
    public static function catalogFor(string $audience): array
    {
        return array_filter(self::CATALOG, fn (array $entry): bool => $entry['audience'] === $audience);
    }

    /** Whether an event is ALWAYS-ON (security) — such events ignore quiet hours + can't be toggled off. */
    public static function isAlwaysOn(string $eventKey): bool
    {
        return (bool) (self::CATALOG[$eventKey]['always_on'] ?? false);
    }

    /**
     * The audience's events grouped by their UI group, each with its current on/off state — drives the
     * preferences screen.
     *
     * @return array<string, list<array{key: string, label: string, description: string, always_on: bool, state: array<string, bool>}>>
     */
    public function groupedFor(User $user, string $audience): array
    {
        $states = $this->statesFor($user, $audience);
        $groups = [];

        foreach (self::catalogFor($audience) as $key => $entry) {
            $groups[$entry['group']][] = [
                'key' => $key,
                'label' => $entry['label'],
                'description' => $entry['description'],
                'always_on' => $entry['always_on'],
                'state' => $states[$key],
            ];
        }

        return $groups;
    }
}
