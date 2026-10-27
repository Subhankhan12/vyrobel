<?php

declare(strict_types=1);

namespace App\Support\Email;

use App\Listeners\SendGuestTransactionalEmails;
use App\Models\Guest;
use App\Models\GuestEmailPreference;

/**
 * THE GUEST'S OWN EMAIL OPT-OUT — the one gate every optional transactional guest email asks before sending.
 *
 * ⚠️ WHY THIS EXISTS AND `NotificationPreferences::allows()` DOES NOT SERVE IT. The guest transactional
 * emails never consult `allows()`: {@see SendGuestTransactionalEmails::sendToGuest()} and the
 * two scheduled commands call `Mail::to()->send()` directly, gated only by
 * {@see EmailTemplateResolver::enabled()}. `allows()` gates the IN-APP feed rows and nothing else — so a
 * guest email opt-out is a NEW GATE AT THE SEND SITE, not a change to the preference service. `allows()` is
 * untouched by this feature, and the operator/owner path with it.
 *
 * ⚠️ THE SHAPE MIRRORS {@see EmailTemplateResolver::enabled()} DELIBERATELY (§N.8f): a static question asked
 * immediately before the send, answering true by default and false only on a stored negative. The two gates
 * are independent and BOTH must pass — the operator's per-org #86 switch, and the guest's own choice.
 *
 * ⚠️ SPARSE / DEFAULT-SEND. A row exists ONLY when the guest opted out, so the default lives in the ABSENCE
 * of data. A guest who never opens the screen keeps receiving what they receive today; nothing had to be
 * backfilled, and no backfill can therefore fail half-way and silently unsubscribe anyone.
 *
 * ⚠️ ONLY OPTIONAL CATEGORIES ARE ANSWERABLE. `txn` (booking confirmation · refund · cancellation) and
 * `pre_arrival` are ALWAYS-ON by ruling and are not in {@see OPTIONAL}: a transactional record is not a
 * preference, and the pre-arrival email carries the door-code nudge — a guest who could switch it off could
 * arrive unable to get in. `mayReceive()` returns true for anything not optional, so a caller cannot
 * accidentally suppress one by passing its key.
 *
 * ⚠️ NOT THE MARKETING CONSENT. `guests.marketing_consent` + the `marketing.unsubscribe` flow govern
 * MARKETING on a separate legal basis and are untouched here. Two controls, two bases, never collapsed.
 */
final class GuestEmailPreferences
{
    /** Optional by ruling — the only categories a guest may switch off, and the only ones ever stored. */
    public const OPTIONAL = [
        'payment' => 'Payment reminders',
        'reviews' => 'Review requests',
    ];

    /**
     * The display label for an optional category, in the reader's language (I18N-01). {@see OPTIONAL}'s English
     * values stay as the code map; a screen that names a category reads it here, by its code.
     */
    public static function label(string $category): string
    {
        return (string) match ($category) {
            'payment' => __('php/support/email/guest_email_preferences.payment'),
            'reviews' => __('php/support/email/guest_email_preferences.reviews'),
            default => $category,
        };
    }

    /**
     * May this email category be sent to this guest?
     *
     * True when: the category is not optional (always-on), the guest is unknown, or no opt-out row exists.
     * False only when this guest has an explicit stored opt-out for an optional category.
     */
    public static function mayReceive(?Guest $guest, string $category): bool
    {
        if (! array_key_exists($category, self::OPTIONAL)) {
            return true;   // always-on: txn, pre-arrival, and anything not modelled here
        }

        if (! $guest instanceof Guest) {
            return true;   // no guest resolved — the send site's own address guard will handle it
        }

        return ! GuestEmailPreference::query()
            ->where('guest_id', $guest->getKey())
            ->where('category', $category)
            ->exists();
    }

    /**
     * The guest's current choices, for the screen: category => receiving?  (true = still receiving).
     *
     * @return array<string, bool>
     */
    public static function forGuest(Guest $guest): array
    {
        $optedOut = GuestEmailPreference::query()
            ->where('guest_id', $guest->getKey())
            ->pluck('category')
            ->all();

        $out = [];
        foreach (array_keys(self::OPTIONAL) as $category) {
            $out[$category] = ! in_array($category, $optedOut, true);
        }

        return $out;
    }

    /**
     * Persist ONE category's choice for ONE guest. Receiving = no row; opted out = exactly one row.
     *
     * ⚠️ SELF-SCOPED BY CONSTRUCTION (#28) — it writes through the guest it is handed, and the only caller
     * hands it the guard-resolved guest. A category outside {@see OPTIONAL} is refused rather than stored, so
     * an always-on email cannot be switched off by posting its key.
     */
    public static function set(Guest $guest, string $category, bool $receiving): void
    {
        if (! array_key_exists($category, self::OPTIONAL)) {
            return;   // always-on — never storable
        }

        if ($receiving) {
            GuestEmailPreference::query()
                ->where('guest_id', $guest->getKey())
                ->where('category', $category)
                ->delete();

            return;
        }

        GuestEmailPreference::query()->firstOrCreate([
            'guest_id' => (string) $guest->getKey(),
            'category' => $category,
        ]);
    }
}
