<?php

declare(strict_types=1);

namespace App\Notifications\Owner;

use App\Notifications\FeedNotification;

/**
 * Owner: somebody with `units.manage` READ a stored arrival credential for one of their units.
 *
 * ⚠️⚠️ WHY THE OWNER IS TOLD AT ALL. A reveal is SILENT where an overwrite is DETECTABLE — change a door
 * code and it stops working and someone complains within a day; READ one and the only trace is an audit row
 * nobody routinely opens. The notification is what makes the silent act visible to the person whose property
 * it unlocks.
 *
 * ⚠️⚠️ THE ONE ABSOLUTE CONSTRAINT: THIS SAYS *THAT* A REVEAL HAPPENED, NEVER *WHAT* WAS REVEALED. An
 * encrypted column is undone the moment its value is copied somewhere plaintext, and a notification is the
 * worst possible destination: it lands in the `notifications` table AND, on the mail channel, in an inbox —
 * unencrypted, indefinitely, outside every guard cluster E put around the column. An email carrying the door
 * code would defeat the entire design of the form it reports on.
 *
 * ⚠️ THE CONSTRUCTOR TAKES PRIMITIVES, NOT THE `Unit`. Passing the model would put an object holding the
 * decrypted attribute one `->` away from every renderer, and — if this notification is ever queued — into
 * the serialised job payload. It is not queued today ({@see FeedNotification} does not implement
 * ShouldQueue), but a constructor that cannot carry a secret stays safe if that changes.
 *
 * ⚠️ FIELD NAMES ONLY, exactly as the audit row does. "The door code was viewed" is the whole message;
 * "the door code is 4021" is the disaster.
 *
 * ⚠️ ALWAYS-ON, following the precedent of {@see AccountSecurityNotification} and its guest twin: a
 * credential to your own building being read is not a preference. Recorded as following the established
 * pattern rather than as a fresh policy decision — see the pass report, where suppressibility is surfaced.
 */
class ArrivalSecretRevealedNotification extends FeedNotification
{
    /**
     * @param  string  $unitLabel  the unit's name or code — an identifier, never a credential
     * @param  string  $propertyName  which property it belongs to
     * @param  string  $actorName  who performed the reveal
     * @param  list<string>  $fields  WHICH fields held a value — never the values themselves
     */
    public function __construct(
        private string $unitLabel,
        private string $propertyName,
        private string $actorName,
        private array $fields,
    ) {}

    public function eventKey(): string
    {
        return 'arrival_secret_revealed';
    }

    public function category(): string
    {
        return 'account';
    }

    public function data(object $notifiable): array
    {
        // ⚠️ The field NAMES, rendered for a human. Nothing here can carry a value: the constructor never
        // received one, so there is none in scope to leak by accident.
        $labels = [
            'door_code' => 'door code',
            'wifi_password' => 'wifi password',
        ];

        $named = array_values(array_filter(array_map(
            fn (string $f): ?string => $labels[$f] ?? null,
            $this->fields,
        )));

        $what = $named === [] ? 'arrival details' : implode(' and ', $named);

        return [
            'title' => 'Arrival details viewed',
            'context' => $this->actorName.' viewed the '.$what.' for '.$this->unitLabel
                .' at '.$this->propertyName.'. If this wasn’t expected, contact your manager.',
            'url' => route('owner.units', [], false),
            'category' => 'account',
        ];
    }
}
