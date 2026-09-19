<?php

declare(strict_types=1);

namespace App\Notifications\Owner;

use App\Livewire\Owner\OwnerBalance;
use App\Notifications\FeedNotification;
use App\Services\PropertyRemovalService;

/**
 * ⚠️⚠️ R-ARCHIVE (founder ruling, 2026-09-19) — F-24. The owner learns that a property holding their units has
 * been ARCHIVED, and archiving stays irreversible.
 *
 * ⚠️ WHAT WAS WRONG. {@see PropertyRemovalService}'s own docblock recorded it: *"The owner is told
 * nothing. No notification is sent when a property carrying their units is archived, so their units simply stop
 * appearing in their portal's listings."* Nothing is ever un-archived, so an owner had no way to learn what had
 * happened or to ask before it did.
 *
 * ══ ⚠️⚠️ WHY THERE IS NO MONEY FIGURE IN THIS NOTICE ═══════════════════════════════════════════════════
 *
 * 🔬 MEASURED FIRST, because a notice that implies the money is settled when it is not would be worse than the
 * silence it replaces. Archiving a property does NOT touch the owner's money, and the schema is why:
 * `owner_statements` and `payouts` carry NO property or unit column at all — they are owner-scoped — and
 * `owner_ledger` carries `unit_id`, whose units SURVIVE, because a soft delete on `properties` fires no cascade.
 * So statements, ledger history and payouts are all unaffected and all remain reachable in the portal.
 *
 * ⚠️ AND A FIGURE WOULD HAVE BEEN THE WRONG SHAPE ANYWAY, for two reasons worth recording:
 *   · **It goes stale the instant it is sent.** A balance printed into a notification is a snapshot; the owner's
 *     position can move the same day. A stale number in an email is the technically-accurate trap in a new coat.
 *   · **It would be a SECOND computation of one concept.** "Available" is derived inside
 *     {@see OwnerBalance} and is not extracted; re-deriving it here is exactly the drift
 *     FIX 42 spent a pass removing, and extracting it would mean touching the balance path.
 *
 * ⚠️ SO THE NOTICE STATES THE INVARIANT AND LINKS TO THE LIVE POSITION. That sentence is true whenever it is
 * read, which a number never is.
 *
 * ══ ⚠️ WHAT IT MUST NEVER CARRY ════════════════════════════════════════════════════════════════════════
 *
 * ⚠️ The owner's OWN property name, and nothing else: no other owner's name or property, no guest data of any
 * kind, no internal identifier, and no operator's private reason for archiving. Several owners can hold units at
 * one property (owners attach per-UNIT via `units.owner_id`), so each is notified SEPARATELY about their own
 * position — one message naming all of them would be a leak.
 */
class PropertyArchivedNotification extends FeedNotification
{
    public function __construct(private string $propertyName) {}

    public function eventKey(): string
    {
        return 'property_archived';
    }

    public function category(): string
    {
        return 'account';
    }

    /**
     * ⚠️ THE PROPERTY NAME IS PASSED IN, NOT THE MODEL — the property is SOFT-DELETED by the time this is
     * rendered, and a queued notification re-resolving it would find nothing. The name is captured at the
     * moment of archiving, which is also the only moment it is certainly still readable.
     *
     * ⚠️ NO INTERNAL IDENTIFIER: the link goes to the owner's own units listing, not to a property id that no
     * longer resolves and that the owner has no use for.
     *
     * @return array{title: string, context: string, url: string, category: string}
     */
    public function data(object $notifiable): array
    {
        return [
            'title' => 'A property holding your units was archived',
            'context' => $this->propertyName.' has been archived and can’t be restored. Your earnings, '
                .'statements and payouts are not affected and stay in your portal.',
            'url' => route('owner.balance', [], false),
            'category' => 'account',
        ];
    }
}
