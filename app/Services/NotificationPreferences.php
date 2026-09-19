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
        'maintenance_resolved' => ['label' => 'Work order resolved', 'description' => 'A maintenance work order is resolved', 'group' => 'Operations', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => false],
        'maintenance_reassigned' => ['label' => 'Work order reassigned', 'description' => 'A maintenance work order is reassigned to you', 'group' => 'Operations', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => false],
        'owner_payout_sent' => ['label' => 'Owner payout sent', 'description' => 'A payout is disbursed to an owner', 'group' => 'Finance', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => true],
        'statement_finalized' => ['label' => 'Statement ready', 'description' => 'An owner statement is finalized', 'group' => 'Finance', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => true],
        'new_review' => ['label' => 'New review', 'description' => 'A guest leaves a review', 'group' => 'Reputation', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => false],
        'low_rating' => ['label' => 'Low rating (under 4★)', 'description' => 'A guest leaves a review rated under 4 stars', 'group' => 'Reputation', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => true],
        'channel_sync_error' => ['label' => 'Channel sync error', 'description' => 'A channel stops syncing', 'group' => 'System', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => true],
        'new_device_login' => ['label' => 'Login from a new device', 'description' => 'Your account signs in somewhere new', 'group' => 'System', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => true],
        'daily_summary' => ['label' => 'Daily morning summary', 'description' => 'A digest of the day at 08:00', 'group' => 'System', 'audience' => 'operator', 'always_on' => false, 'default' => false, 'email_default' => false],
        'schedule_published' => ['label' => 'Schedule published', 'description' => 'Your week’s roster is published', 'group' => 'Operations', 'audience' => 'operator', 'always_on' => false, 'default' => true, 'email_default' => false],
        // ── Owner ──
        'statement_ready' => ['label' => 'Statement ready', 'description' => 'Your monthly statement is finalized', 'group' => 'Earnings', 'audience' => 'owner', 'always_on' => false, 'default' => true, 'email_default' => true],
        'payout_sent' => ['label' => 'Payout sent', 'description' => 'The day money leaves for your account', 'group' => 'Earnings', 'audience' => 'owner', 'always_on' => false, 'default' => true, 'email_default' => true],
        'performance_digest' => ['label' => 'Performance digest', 'description' => 'Occupancy & earnings vs last month', 'group' => 'Performance', 'audience' => 'owner', 'always_on' => false, 'default' => true, 'email_default' => false],
        'terms_changed' => ['label' => 'Terms changed', 'description' => 'A change to commission or fees', 'group' => 'Account', 'audience' => 'owner', 'always_on' => false, 'default' => true, 'email_default' => true],
        'unit_request_reviewed' => ['label' => 'Unit request reviewed', 'description' => 'Your add-a-unit request is approved or declined', 'group' => 'Account', 'audience' => 'owner', 'always_on' => false, 'default' => true, 'email_default' => true],
        // ⚠️ OWNER FIX 2 — the owner audit found the portal shows bookings, messages and reviews as full
        // PAGES while the owner could not be NOTIFIED of any of them. These three close that.
        // ⚠️ THEY CARRY THEIR OWN KEYS BECAUSE `audience` IS A SINGLE STRING. `new_booking` and `new_review`
        // already exist as `operator`, and one key cannot serve two portals here — reusing them would have
        // meant flipping the audience and silently stranding every operator. The operator keys are untouched.
        // ── GUEST (guest notifications · GATE 1) ──────────────────────────────────────────────────
        // ⚠️ A NEW KEY, NEVER A FLIP — the same reasoning OwnerNewBookingNotification's docblock records:
        // `audience` is a SINGLE string, so one key cannot serve two portals. Reusing the operator's
        // `new_booking` (or the owner's `owner_new_booking`) would have meant flipping its audience and
        // stranding everyone who relies on it. The guest gets its own key; the other 25 are untouched.
        //
        // ⚠️ `email_default => false` IS LOAD-BEARING, NOT A PREFERENCE. The guest ALREADY receives
        // BookingConfirmationMail on this exact event. NotificationPreferences::allows() falls back to
        // defaultFor() for a non-User notifiable, so an `email_default => true` here would add the 'mail'
        // channel in FeedNotification::via() and the guest would be emailed TWICE. This notification is
        // IN-APP ONLY, additive to the existing email — never a replacement for it.
        //
        // ⚠️ NO PER-GUEST TOGGLE (defaults-only, deliberate). NotificationPreference keys on `user_id`, so
        // a Guest cannot hold a stored preference; guests run on these defaults. catalogFor() filters on an
        // exact audience match, so this entry never appears on the operator or owner preferences screen,
        // and NotificationPreferenceController still hard-asserts in_array($audience, ['operator','owner']).
        'guest_booking_confirmed' => ['label' => 'Booking confirmed', 'description' => 'Your booking is confirmed', 'group' => 'Trips', 'audience' => 'guest', 'always_on' => false, 'default' => true, 'email_default' => false],
        'guest_checkin_ready' => ['label' => 'Check-in details ready', 'description' => 'Your self check-in details are available', 'group' => 'Trips', 'audience' => 'guest', 'always_on' => false, 'default' => true, 'email_default' => false],
        'guest_message_received' => ['label' => 'Message from your host', 'description' => 'The property replied to you', 'group' => 'Trips', 'audience' => 'guest', 'always_on' => false, 'default' => true, 'email_default' => false],
        'guest_payment_due' => ['label' => 'Balance due', 'description' => 'A balance is outstanding before your stay', 'group' => 'Trips', 'audience' => 'guest', 'always_on' => false, 'default' => true, 'email_default' => false],
        'guest_booking_cancelled' => ['label' => 'Booking cancelled', 'description' => 'Your booking was cancelled', 'group' => 'Trips', 'audience' => 'guest', 'always_on' => false, 'default' => true, 'email_default' => false],
        'guest_refund_processed' => ['label' => 'Refund processed', 'description' => 'A refund was processed on your booking', 'group' => 'Trips', 'audience' => 'guest', 'always_on' => false, 'default' => true, 'email_default' => false],
        'guest_review_request' => ['label' => 'Review your stay', 'description' => 'An invitation to review a completed stay', 'group' => 'Trips', 'audience' => 'guest', 'always_on' => false, 'default' => true, 'email_default' => false],
        // ⚠️ ALWAYS-ON, mirroring the owner's `account_security`: a pending erasure of your own account is not
        // a preference. allows() short-circuits an always_on key to true BEFORE consulting the defaults, so
        // `email_default` is not read for this one — the double-email guard for G8 is proven by test (the
        // erasure email fires exactly once), not by this value. It is still false, so the row is honest about
        // which channel it means.
        'guest_account_security' => ['label' => 'Account security', 'description' => 'A change to your account or a scheduled deletion', 'group' => 'Account', 'audience' => 'guest', 'always_on' => true, 'default' => true, 'email_default' => false],

        'owner_new_booking' => ['label' => 'New booking', 'description' => 'A booking is confirmed on one of your units', 'group' => 'Performance', 'audience' => 'owner', 'always_on' => false, 'default' => true, 'email_default' => false],
        'owner_review_posted' => ['label' => 'Review posted', 'description' => 'A guest review is published on one of your units', 'group' => 'Performance', 'audience' => 'owner', 'always_on' => false, 'default' => true, 'email_default' => false],
        // ⚠️ NOT "Guest message" (the wireframe's label): owner↔guest messaging does not exist — a thread is
        // `guest_id` XOR `owner_id`, and an owner's threads are with their operator. Naming it after guests
        // would advertise a channel the owner has not got.
        'owner_message' => ['label' => 'New message', 'description' => 'Your management team sends you a message', 'group' => 'Account', 'audience' => 'owner', 'always_on' => false, 'default' => true, 'email_default' => false],
        'account_security' => ['label' => 'Account & security', 'description' => 'Sign-ins and bank changes — always on', 'group' => 'Account', 'audience' => 'owner', 'always_on' => true, 'default' => true, 'email_default' => true],
        // ⚠️ CLUSTER E follow-up — somebody read a stored arrival credential for a unit this owner owns.
        // ALWAYS-ON for the same reason the two rows around it are: a credential to your own building being
        // read is not a preference. ⚠️ A reveal is SILENT where an overwrite is DETECTABLE, so this feed row
        // is the only thing that makes it visible to the owner without them opening an audit log.
        'arrival_secret_revealed' => ['label' => 'Arrival details viewed', 'description' => 'Someone viewed a stored door code or wifi password for your unit — always on', 'group' => 'Account', 'audience' => 'owner', 'always_on' => true, 'default' => true, 'email_default' => true],
        // ⚠️⚠️ R-ARCHIVE (founder ruling, 2026-09-19) — F-24. A property carrying an owner's units can be
        // archived, and until FIX 43 the owner was told NOTHING: their units simply stopped appearing.
        // ⚠️ ALWAYS ON, DELIBERATELY, AND THIS IS A PRODUCT-VISIBLE CHOICE. The ruling is that the owner is
        //    NOTIFIED of an event that CANNOT BE UNDONE — nothing is ever un-archived. A notice the owner can
        //    switch off would satisfy the letter of that and reinstate exactly the silence F-24 names, for the
        //    one event where there is no second chance to find out. It also guarantees the `database` row that
        //    RECORDS the send (FIX 34's lesson: a mail with no record cannot be spoken about afterwards).
        //    ⚠️ The founder can reverse this to a toggle; it is recorded here so the choice is visible.
        'property_archived' => ['label' => 'Property archived', 'description' => 'A property holding your units was archived — permanent, and always on', 'group' => 'Account', 'audience' => 'owner', 'always_on' => true, 'default' => true, 'email_default' => true],
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
