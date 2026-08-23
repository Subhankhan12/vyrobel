# -*- coding: utf-8 -*-
"""⚠️ THE G6 BUCKET WAS FOUR DIFFERENT THINGS WEARING ONE PATTERN.

Slice 0 grouped by regex, and "most / usually / typically / nobody" catches a HEDGED PRODUCT CLAIM
just as readily as a population generalisation. Acting on the ruling "G6 → rewrite as our opinion"
without re-reading would have hedged claims that are FALSE — turning a correctable falsehood into an
unfalsifiable one, which is strictly worse. This map is the re-read, by id.

  OPINION  — a genuine unevidenced claim about operators/the world → reframe to what WE believe/built/chose
  INTERNAL — a claim about OUR product that is TRUE → belongs in G4, sourced by a test that fails when it does
  FALSE    — a claim about OUR product that is NOT true → ⚠️ belongs to clusters C/D/E: CORRECT it, never hedge it
  NOISE    — a heading, a rhetorical question or a card blurb → not a claim; no source, no rewrite
"""

KIND = {
    # ── NOISE: headings and rhetorical framing the sweep could not distinguish from prose ──
    'S1-119': 'NOISE', 'S1-044': 'NOISE', 'S1-101': 'NOISE', 'S1-171': 'NOISE',
    'S1-190': 'NOISE', 'S1-103': 'NOISE', 'S1-182': 'NOISE', 'S1-078': 'NOISE',

    # ── ⚠️ FALSE: product claims the Gate-5 audit already found false (clusters D and E).
    #    Hedging these would be actively harmful; they are doc-FIX items, not sourcing items.
    'S1-040': 'FALSE',   # arrival secrets released "only once check-in is complete and the balance is settled"
    'S1-142': 'FALSE',   # "the front desk can override and release manually"
    'S1-144': 'FALSE',   # "the invitation goes out on a schedule you set … repeats once"
    'S1-179': 'FALSE',   # orphan-gap minimum dropping — 0 hits in app/, and cluster D says the same
    'S1-006': 'FALSE',   # the same orphan-gap mechanic, asserted as a consequence, on article-pricing
    'S1-015': 'FALSE',   # "a single property under 30 units is typically live in two to three weeks"
    'S1-043': 'FALSE',   # "fifty to two hundred units takes four to six"
    'S1-117': 'FALSE',   # "spreadsheet-based operations take longest"

    # ── INTERNAL: true claims about our own product, sourceable by a test ──
    'S1-164': 'INTERNAL', 'S1-163': 'INTERNAL', 'S1-162': 'INTERNAL',   # owners/staff cannot see guest PII (#28)
    'S1-021': 'INTERNAL',   # an owner can pause their own payouts
    'S1-106': 'INTERNAL',   # per-property timezone on a multi-property account
    'S1-154': 'INTERNAL',   # the rate grid shows the resolved price per night per unit
    'S1-184': 'INTERNAL',   # the refund screen asks rather than assuming
    'S1-100': 'INTERNAL',   # rooms are not added to the housekeeping board by hand
    'S1-166': 'INTERNAL',   # ⚠️ ABSENCE — "Vyrobel does not file on your behalf"
    'S1-042': 'INTERNAL',   # ⚠️ ABSENCE-ish — "everyone who touches Vyrobel gets their own login"
    'S1-183': 'INTERNAL',   # a move to a more expensive period appears as a balance owing
}
# Everything else in G6 is OPINION.
