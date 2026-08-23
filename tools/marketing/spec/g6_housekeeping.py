# -*- coding: utf-8 -*-
"""`article-housekeeping` — THE RE-ARGUE. Nine sentences, each verified against the code BEFORE it was written.

⚠️⚠️ NINE, NOT TWELVE. The measured split counted 12 G6 ledger rows, but three of them (S1-044, S1-101,
S1-171) were already re-triaged as NOISE — headings and a rhetorical question, `disposition: remove`. They
are not claims and need no argument. ⚠️ The re-argue is nine sentences.

⚠️⚠️ THE HAZARD THIS FILE EXISTS TO MANAGE. Re-arguing "how we built housekeeping and why" swaps
unsourceable claims about OPERATORS for claims about OUR OWN PRODUCT — which is the point, and which makes
every one of them checkable against the code. ⚠️ That is precisely the class the Gate 5 audit found 220
FALSE claims in. So each sentence below was written FROM a verified code fact, never written first and
checked after.

⚠️ THE CLASSIFICATION DECIDES WHAT NEEDS A SOURCE:
    BEHAVIOUR — asserts what the product does. A CLAIM. Enters the ledger, needs a `kind: internal`
                source: a test that FAILS when it stops being true.
    RATIONALE — asserts what WE judged, chose or intended. Legitimately unfalsifiable, needs nothing.
⚠️ Classified by what the sentence ASSERTS, never by how it is phrased: "we believe the board shows the
   deadline" is a BEHAVIOUR claim wearing a hedge — less checkable and no more true. ⚠️ And the reverse
   trap: "we chose X because most operators find Y" is a population claim hiding inside a rationale.
"""

# ── BEHAVIOUR — each carries an internal test, mutation-proven ───────────────────────────────────────
BEHAVIOUR = {
 'S1-071': {
   'original': ('If that ticket can also take the unit off sale until it is fixed, the review you did not '
                'receive is worth more than the maintenance you did.'),
   'replacement': ('A maintenance ticket can be marked as taking the unit out of service, and then it '
                   'does: the unit’s condition changes, availability is recomputed, and it stops being '
                   'sellable until no such ticket still holds it.'),
   'code_fact': ('MaintenanceService: a `takes_out_of_service` ticket, when opened/started, transitions '
                 'its unit to `out_of_service` via setUnitCondition; RecomputeAvailabilityOnInventoryChange '
                 'consumes that and recomputes. It returns to service ONLY when no other active '
                 'takes_out_of_service ticket still holds it.'),
   'test': 'S1-071',
   'nuance_kept': ('⚠️ "can be marked … and then it does" is deliberate. It is NOT automatic for every '
                   'ticket — only for one carrying the flag — and rounding that away would have produced '
                   'a cleaner sentence that is false for most tickets.'),
 },
 'S1-104': {
   'original': 'Not a message, a permanent position on the board that everyone can see.',
   'replacement': ('A status is a stored position, not a message: it stays where it was left until '
                   'somebody advances it, and everyone opening the board reads the same one.'),
   'code_fact': ('HousekeepingTask.status is a persisted column; HousekeepingBoard renders lanes from it. '
                 'updateTaskStatus() is the only writer, inside a transaction, and records a domain event.'),
   'test': 'S1-104',
 },
 'S1-136': {
   'original': ('Teams that add a fifth and a sixth find nobody updates any of them, because the cost of '
                'maintaining the board exceeds the cost of shouting across the corridor.'),
   'replacement': ('The board has four states — pending, in progress, clean, inspected — and we did not '
                   'add a fifth. Every extra state is one more thing that has to be kept true, and a '
                   'state nobody updates is worse than one that does not exist.'),
   'code_fact': "HousekeepingBoard::STATUSES = ['pending','in_progress','clean','inspected'] — four, and "
                "the component's own docblock says 'no invented columns'.",
   'test': 'S1-136',
   'nuance_kept': ('⚠️ The first clause is BEHAVIOUR (four states, testable); the second is RATIONALE. '
                   'They are split at the full stop so the claim half can be sourced and the judgement '
                   'half is not pretending to be one.'),
 },
 'S1-176': {
   'original': 'Usually it settles the question.',
   'replacement': ('A photo attached to the task travels with the task, so “was the room actually clean?” '
                   'is answered from the record rather than from memory.'),
   'code_fact': ('HousekeepingTask uses HasMedia; HousekeepingBoard eager-loads `media` and builds '
                 'photosForTasks() → per-task proof photos on the card.'),
   'test': 'S1-176',
 },
}

# ── RATIONALE — our judgement about our own work. Unfalsifiable BY DESIGN, and sourced by nothing ────
# ⚠️ Every one of these is about US. None asserts anything about what operators do, find or experience —
#    that is the population claim the G6 ruling exists to remove, and it does not become acceptable by
#    being placed inside a sentence that starts "we".
RATIONALE = {
 'S1-019': {
   'original': ('Almost every failure above is the same failure: the state of a room is known to one '
                'person and not to the others.'),
   'replacement': ('We built this around a single idea: a room’s state should be one shared fact, not '
                   'something each person keeps their own version of.'),
 },
 'S1-082': {
   'original': "It's that nobody has the same picture of the day.",
   'replacement': ('What we set out to remove was not effort but ambiguity — a status had to mean the '
                   'same thing to everyone reading it.'),
 },
 'S1-102': {
   'original': 'Nobody complains for a month and then somebody leaves.',
   'replacement': ('We would rather a bad day were visible on the board than absorbed quietly by whoever '
                   'happened to be on shift.'),
 },
 'S1-107': {
   'original': ('One person holds the whole day in their head, the cleaners know the properties, and when '
                'something changes there are few enough people that a message reaches everyone who needs it.'),
   'replacement': ('We designed the board for the case where more than one person needs the same answer '
                   'at the same time. Below that, we would not argue you need one.'),
 },
 'S1-114': {
   'original': 'Put the state somewhere everybody can see and most of it stops.',
   'replacement': ('That is the whole reason the board exists: somewhere the state can be read rather '
                   'than asked for.'),
 },
}

# ⚠️ NOT REWRITTEN — already ruled NOISE (headings / a rhetorical question), disposition `remove`.
NOISE_ALREADY_RULED = ('S1-044', 'S1-101', 'S1-171')


# ══ ⚠️ S1-044 SPLIT · S1-171 CUT — the two rows the `not_a_claim` read restored ══════════════════════
#
# ⚠️ These were filed as noise by the element test and caught by READING. They were never part of the
# nine, which is why the re-argue's "0% unresolved" was true of the LEDGER and false of the PAGE.

# ── S1-044 · "Four states, visible to everyone" — TWO claims in one heading ──────────────────────────
#
# ⚠️⚠️ THE VISIBILITY FACT, DETERMINED BEFORE THE SENTENCE WAS WRITTEN:
#   · `Route::get('/housekeeping', HousekeepingBoard::class)` carries NO `can:` — the read is ungated.
#   · The `/app` group applies EnsureBackOfficeAccess, whose allow-list is ALL ELEVEN back-office roles
#     (Property Owner, Group Admin, Hotel Manager, Revenue Manager, Front Desk, Reservation Agent,
#     Housekeeping, Maintenance, Accountant, Marketing Manager, Guest Support Agent) — including roles
#     with no housekeeping business at all.
#   · ⚠️ An External Owner is NOT a back-office role and is refused from /app (#26). Owners see a
#     SEPARATE, read-only cleaning schedule on their own unit (OwnerUnitDetail::cleanings()) — real
#     HousekeepingTask rows, but not this board.
#
# ⚠️ VERDICT: TRUE ONLY WITH A SCOPE. A bare "everyone" invites a reader to include owners (who see a
#    different surface) and guests (who see none). The scope is the operator's back office, so the
#    sentence says it — the G4 scope-gap defect, one word wide, caught BEFORE shipping this time.
SPLIT_044 = {
 'original': 'Four states, visible to everyone',
 'replacement': 'Four states, visible to your whole back-office team',
 'claims': {
   'S1-044':  ('the COUNT — "four states". Sources to S1-136\'s EXISTING test, which is a reflection check '
               'on HousekeepingBoard::STATUSES. ⚠️ Same FACT, same test — that is not one test sourcing two '
               'claims, it is two sentences stating one fact.'),
   'S1-044b': ('the VISIBILITY — "your whole back-office team". ⚠️ ITS OWN test, because a combined one '
               'could not tell you which half broke: if the board\'s read route later gains a permission, '
               'the visibility claim becomes false while the count stays true, and a test asserting both '
               'would still pass on the half that held.'),
 },
}

# ── S1-171 · "Three rules that do most of the work" ──────────────────────────────────────────────────
#
# ⚠️ "that do most of the work" is an UNMEASURED OUTCOME — not sourceable, and hedging it into "in our
#    view" would only make it unfalsifiable. CUT.
# ⚠️ THE COUNT THAT REMAINS WAS VERIFIED AGAINST THE SECTION, not assumed: the three rules that follow
#    are "A guest waiting outranks everything", "Damage becomes a ticket where it is found", and
#    "Assignment is balanced by the system, adjusted by the human". The reader can count them without
#    leaving the section, which is what makes it self-evidencing rather than a claim.
CUT_171 = {
 'original': 'Three rules that do most of the work',
 'replacement': 'Three rules',
}
