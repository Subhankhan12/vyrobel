<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Unit;
use App\Models\User;
use App\Notifications\Owner\ArrivalSecretRevealedNotification;
use App\Support\Events\AuditEventRecorder;
use App\Support\Guest\ArrivalPack;

/**
 * CLUSTER E — THE ONE WRITE PATH FOR A UNIT'S ARRIVAL INFO (door code · wifi · arrival notes).
 *
 * ⚠️⚠️ WHY THIS EXISTS. Every reference to `door_code`, `wifi_ssid`, `wifi_password` and `arrival_notes`
 * in the application was a READ — the guest arrival screen, the pre-arrival mail, the arrival PDF. The only
 * WRITER was the demo seeder, which made these the twelfth seeder-only entity and left invariant #33
 * ({@see ArrivalPack}) carefully gating the release of data no operator could enter.
 *
 * ⚠️⚠️ THESE ARE CREDENTIALS TO A PHYSICAL SPACE, NOT FORM FIELDS. Three deliberate positions:
 *
 *   1. AT REST — `door_code` and `wifi_password` already carry the `encrypted` cast on {@see Unit}, which
 *      is the project's convention for sensitive values (as `guests.email`/`phone` do). ⚠️ `wifi_ssid` and
 *      `arrival_notes` are deliberately NOT encrypted: an SSID is broadcast to the street by the router
 *      itself, and the notes are directions, not a secret. Encrypting them would buy nothing and would make
 *      them unqueryable.
 *
 *   2. THE AUDIT RECORDS *WHICH* FIELDS CHANGED — NEVER THEIR VALUES. Writing a door code into
 *      `audit_events.after` would copy it, in plaintext, into a table no erasure sweeps and every admin
 *      surface reads: ⚠️ "an encrypted column is undone the moment its value is copied somewhere
 *      plaintext". The field NAMES are what makes a change attributable; the values would only make the
 *      log a second, weaker copy of the secret.
 *
 *   3. A BLANK FIELD MEANS "LEAVE IT ALONE", NOT "ERASE IT". The form never renders a stored secret back
 *      into HTML, so a blank input carries no information about what is stored — treating blank as an
 *      erase would silently wipe a working door code every time someone edited the wifi name beside it.
 *      Clearing is therefore explicit ({@see self::CLEAR}).
 */
class ArrivalInfoService
{
    /** The explicit "remove this value" token — because blank means "unchanged" (see the class docblock). */
    public const CLEAR = '__clear__';

    /** @var list<string> */
    public const SECRET_FIELDS = ['door_code', 'wifi_password'];

    /** @var list<string> */
    public const FIELDS = ['door_code', 'wifi_ssid', 'wifi_password', 'arrival_notes'];

    public function __construct(
        private AuditEventRecorder $audit,
    ) {}

    /**
     * Apply the posted arrival info. Only keys PRESENT in `$input` are considered, and among those only
     * non-blank values change anything (or the CLEAR token, which empties one).
     *
     * @param  array<string, string|null>  $input
     * @return list<string> the field names that actually changed
     */
    public function update(Unit $unit, array $input): array
    {
        $changed = [];

        foreach (self::FIELDS as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $value = is_string($input[$field]) ? trim($input[$field]) : '';

            if ($value === '') {
                continue;                                  // blank = unchanged, never an erase
            }

            $new = $value === self::CLEAR ? null : $value;

            if ((string) ($unit->{$field} ?? '') === (string) ($new ?? '')) {
                continue;                                  // no-op writes are not changes
            }

            $unit->{$field} = $new;
            $changed[] = $field;
        }

        if ($changed === []) {
            return [];
        }

        $unit->save();

        // ⚠️ FIELD NAMES ONLY — see position 2 in the class docblock. `cleared` distinguishes "a code was
        // removed" from "a code was set", which is the part an investigation actually needs, without ever
        // naming the code itself.
        $this->audit->record(
            'unit.arrival_info_changed',
            $unit,
            null,
            [
                'fields' => $changed,
                'cleared' => array_values(array_filter($changed,
                    fn (string $f): bool => $unit->{$f} === null)),
            ],
        );

        return $changed;
    }

    /**
     * Reveal one unit's stored secrets, recording that it happened.
     *
     * ⚠️ A SEPARATE, AUDITED ACTION RATHER THAN A RENDERED VALUE. The editor never prints a stored door
     * code into the page, because that would put it in every response, every browser cache and every
     * screenshot of the units screen, for everyone who can open it. Reading one is a deliberate act with a
     * record — which is the property that matters if a guest is later let in wrongly.
     *
     * ⚠️ STATED PLAINLY: the reveal DOES put the value on screen once — that is its purpose — and the
     * post-redirect-get transport flashes it through the server-side session for exactly one request. That
     * is a transient store on the server, not a durable one, and it is the cost of not having the value in
     * the page on every load. ⚠️ WHO may reveal is a POLICY question left to the founder: it is currently
     * `units.manage`, the same gate as editing, which is the widest defensible reading.
     *
     * @return array{door_code: ?string, wifi_ssid: ?string, wifi_password: ?string, arrival_notes: ?string}
     */
    public function reveal(Unit $unit): array
    {
        $this->audit->record('unit.arrival_secrets_revealed', $unit);

        // ⚠️⚠️ TELL THE OWNER — *THAT* IT HAPPENED, NEVER *WHAT*. A reveal is SILENT where an overwrite is
        // DETECTABLE (a changed code stops working and someone complains; a read leaves only an audit row
        // nobody opens), so the owner is told. ⚠️ ONLY the field NAMES that held a value travel — the
        // notification's constructor takes strings, never this $unit, so no decrypted attribute is ever one
        // dereference away from a renderer or, if it is ever queued, from a serialised payload.
        // ⚠️ NULL-SAFE ALL THE WAY: operator-owned inventory has no owner, and an owner may have no user
        // account. Either way nobody is notified and THE REVEAL ITSELF IS UNAFFECTED — the operator asked
        // for a credential and must still get it.
        $held = [];
        foreach (self::SECRET_FIELDS as $field) {
            if ($unit->{$field} !== null && (string) $unit->{$field} !== '') {
                $held[] = $field;
            }
        }

        // ⚠️ The actor is resolved explicitly rather than through `auth()->user()?->name ?? …`: the nullsafe
        // there is dead under the analyser's typing, and removing it while keeping `??` would fatal in any
        // context with no authenticated user (a command, a job). An explicit check is honest in both.
        $actor = auth()->user();
        // The fallback actor is a word the OWNER reads, so it is written in the owner's language, not the viewer's (I18N-01).
        $recipient = $unit->owner?->user;

        $recipient?->notify(new ArrivalSecretRevealedNotification(
            (string) ($unit->name ?? $unit->code),
            (string) $unit->property->name,
            $actor instanceof User ? (string) $actor->name : (string) __('php/services/arrival_info_service.actor_fallback', [], $recipient->preferredLocale()),
            $held,
        ));

        return [
            'door_code' => $unit->door_code !== null ? (string) $unit->door_code : null,
            'wifi_ssid' => $unit->wifi_ssid !== null ? (string) $unit->wifi_ssid : null,
            'wifi_password' => $unit->wifi_password !== null ? (string) $unit->wifi_password : null,
            'arrival_notes' => $unit->arrival_notes !== null ? (string) $unit->arrival_notes : null,
        ];
    }
}
