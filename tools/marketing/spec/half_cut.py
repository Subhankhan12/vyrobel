# -*- coding: utf-8 -*-
"""⚠️⚠️ THE REGISTERS FOR CORRECT WORK THE EVIDENCE RULE CANNOT SEE — and the trap they defuse.

⚠️ THE PROBLEM, EXACTLY. The corrections gate evidences a claim as DONE when the audit's fragment was
PRESENT in the master and is ABSENT after conversion. That rule is right, and it is what stopped an earlier
version of the gate from banking "absent from both" as progress. But it cannot express one thing:

    ⚠️ A CLAIM WHOSE AUDIT QUOTE STRADDLES A FALSE HALF AND A TRUE SENTENCE.

Cut the false half — which is the ONLY correct action — and the true half is still there, so the rule sees
the claim as present and reports OUTSTANDING. The page is now honest and the gate says it is not.

⚠️⚠️ AND THAT HAS A CLOCK ON IT. A future pass reads "outstanding", cuts the true sentence, watches the
gate go green, and believes it closed a defect. It created one, and it removed a true statement to do it.
The rule forbidding that lived only in a brief — which is to say, nowhere the tooling could enforce it.

⚠️ SO THE RULE LEARNS TO EXPRESS IT, RATHER THAN THE EXCEPTION BEING WRITTEN IN A COMMENT. Each entry below
is a DECLARATION WITH TWO CONDITIONS, and the gate VERIFIES BOTH on every run:

    ① the FALSE fragment was in the master and is GONE from the output   → the correction really happened
    ② the TRUE  fragment is STILL in the output                          → nothing true was cut to green it

⚠️⚠️ THE SECOND CONDITION IS THE POINT. If someone deletes the true half to close the row, condition ② FAILS
and the gate REDS — louder than the outstanding row they were trying to clear. The trap inverts: the wrong
fix is now the only one that breaks the build.

⚠️ A DECLARATION IS NOT A DISMISSAL. Nothing here is taken on trust; an entry whose conditions stop holding
is reported as a BROKEN DECLARATION and counted as outstanding again, never silently forgiven.
"""

# ─────────────────────────────────────────────────────────────────────────────────────────────────
# ⚠️ HALF-CUT — the audit quote spans a false half (cut) and a true half (deliberately kept).
# ─────────────────────────────────────────────────────────────────────────────────────────────────

HALF_CUT = {
    '/glossary': [{
        'match': 'What you are billed on',
        'false_fragment': 'What you are billed on',
        'true_fragment': 'Anything you sell independently and clean separately',
        'why': (
            '⚠️⚠️ THIS ROW WAS REGISTERED FOUNDER_BLOCKED ON 2026-08-26 AND THAT WAS WRONG — recorded here '
            'because the mistake is more instructive than the correction. The billing claim had ALREADY been '
            'corrected by an earlier pass; the glossary now reads "Your bill does not move with it: the '
            'subscription is one flat figure per account whatever your unit count." ⚠️ THE ERROR WAS READING '
            'THE MASTER INSTEAD OF THE BUILT OUTPUT — a grep over tools/marketing/core/*.html finds the '
            'pre-correction text on nine pages, because that is what a master IS. The same wrong-layer read '
            'has now produced three separate false findings. ⚠️ ALWAYS grep C.convert(key), never the master. '
            'The audit quoted the whole entry, so the true definition keeps the row visible — a half-cut.'),
    }],
    '/docs/invites-and-access': [{
        'match': 'no reason to see',
        'false_fragment': 'On a multi-property account, choose which properties they work in. A housekeeper',
        'true_fragment': 'has no reason to see another’s arrivals',
        'why': ('⚠️ ALREADY CORRECTED BY AN EARLIER PASS — the output now states the default outright: "Until '
                'you set it, a new account can reach every property", which is verbatim what '
                '`User::accessiblePropertyIds()` does (empty pivot ⇒ every property in the org). The audit '
                'quoted the SENTENCE, which is true and is the reason to scope at all, so it stays.'),
    }],
    '/resources/booking-direct-trust': [{
        'match': 'A recognised card form, on your own domain',
        'false_fragment': 'on your own domain',
        'true_fragment': 'A bank transfer request fails that match instantly',
        'why': (
            'The card form is Stripe’s hosted checkout (SAQ A) and was never on the operator’s domain — '
            'cut. ⚠️ But the same audit quote runs on into "A bank transfer request fails that match '
            'instantly", which is the paragraph’s actual argument and is not a claim about Vyrobel at '
            'all. Cutting it would delete a true sentence to satisfy a matcher.'),
    }],
    '/resources/winning-owners': [{
        'match': 'approvals above a threshold you set',
        'false_fragment': 'above a threshold you set',
        'true_fragment': 'A written spending threshold',
        'why': (
            'The CTA promised a product capability: approvals are real (`owner_expenses.approval_state`) '
            'but NO spending threshold exists — cut. ⚠️ The audit quote also covers the body section "A '
            'written spending threshold", which is advice about what the operator writes into the '
            'MANAGEMENT AGREEMENT and needs nothing from Vyrobel. An operator really can agree it, so it '
            'is true and it stays.'),
    }],
}

# ─────────────────────────────────────────────────────────────────────────────────────────────────
# ⚠️ CORRECTED BUT UNMATCHABLE — the falsehood is gone; the AUDIT's quote was never matchable.
#
# The audit paraphrases or quotes with its own punctuation ('"10 min read" (article byline)'), so the
# reconciler rejects the fragment before it can look. That is the reconciler being careful, and it is
# right to be — but the correction still happened, and filing it under "unmatched" makes real work look
# like an unreconciled register entry. ⚠️ Same verification: present in the master, gone from the output.
# ─────────────────────────────────────────────────────────────────────────────────────────────────

CORRECTED_UNMATCHABLE = {
    '/resources/winning-owners': [{
        'match': '10 min read',
        'false_fragment': '10 min read',
        'why': 'A 987-word article. The figure was REMOVED, not re-estimated; the real date stays.',
    }],
    '/resources/growing-direct-bookings': [{
        'match': '14 min read',
        'false_fragment': '14 min read',
        'why': 'A 1,011-word article. Same treatment: removed, never re-typed.',
    }],
    '/resources/out-of-service': [{
        'match': 'blocking every channel immediately',
        'false_fragment': 'blocking every channel immediately',
        'why': (
            'No channel connection is live — `FakeChannexClient` is the bound default. ⚠️ Only that clause '
            'was cut; the other three in the sentence are real and stay. The replacement promises no '
            'future channel sync, because the channels page is stopped by ruling.'),
    }],
}

# ─────────────────────────────────────────────────────────────────────────────────────────────────
# ⚠️ VERIFIED TRUE — the audit called it FALSE and the code says otherwise. CONFIRM-FIRST CUTS BOTH WAYS.
#
# Recorded so nobody re-opens it. ⚠️ These are NOT corrections and are NOT counted as such: they are claims
# that needed no work, and the reason is written down so the next reader does not spend a pass rediscovering
# it — or, worse, "fix" a true sentence because a register row still lists it.
# ─────────────────────────────────────────────────────────────────────────────────────────────────

VERIFIED_TRUE = {
    '/docs/online-check-in': [
        {'match': "eleven of tomorrow's fourteen",
         'why': ('⚠️ BECAME TRUE SINCE THE AUDIT. `reservations.expected_arrival` is real and the front desk '
                 'plots an arrival at its parsed time — `FrontDesk.php`: "An arrival plots at its parsed '
                 '`expected_arrival` time when the guest gave [one]", with an explicit note that it is NOT a '
                 'fabricated live arrival. The sentence claims only that knowing the times changes staffing, '
                 'which is exactly what the board supports.')},
        {'match': 'door code, wifi password and arrival notes',
         'why': ('⚠️ BECAME TRUE SINCE THE AUDIT. `Unit` carries `door_code`, `wifi_password` and '
                 '`arrival_notes` as ENCRYPTED columns; `InventoryActionController` is a real operator writer '
                 'for all three; and `ArrivalPack` releases the access secrets a set number of days before '
                 'check-in (invariant #33). Both halves of the implication now hold.')},
    ],
    '/docs/gdpr-and-data': [
        {'match': 'export their own data',
         'why': ('⚠️ ALREADY CORRECTED BY AN EARLIER PASS, and the surviving half is TRUE. The output now '
                 'reads "A guest with an account can request deletion from their account, without contacting '
                 'you" — the export half is gone, and DELETION IS REAL: `POST /guest/account/delete` → '
                 '`GuestAccountController::destroy` → `GuestDeletionService`, with an undo and a grace period. '
                 '⚠️ THIS CORRECTED `docs/DATA-RESIDENCY-INVENTORY.md`, NOT THE PAGE: the inventory said '
                 '"there is no guest-facing export or erasure route", and the ERASURE half of that was wrong. '
                 'An inventory written for a lawyer that understates the product is still a defect.')},
    ],
    '/resources/growing-direct-bookings': [{
        'match': 'a branded storefront',
        'why': (
            'The storefront really does carry the operator’s own identity: the page is headed "Book direct '
            'with {their name}", it shows their own property photography, and `PropertyBrand` reads real '
            '`brand_accent` / `brand_logo_path` values an operator uploads in Settings. ⚠️ ONE NUANCE, '
            'REPORTED RATHER THAN CUT: `PropertyBrand` is consumed by MAIL, not by the public shell, so the '
            'uploaded logo and accent brand the emails while the storefront carries the name and imagery. '
            'That is narrower than the audit’s objection, not wider — the sentence as written is true.'),
    }],
}


# ─────────────────────────────────────────────────────────────────────────────────────────────────
# ⚠️⚠️ BLOCKED ON A FOUNDER DECISION — outstanding, and NOT this pass's to close.
#
# Recorded as its own category because the alternative is worse in both directions: left bare, the row reads
# as work and someone cuts it; cut, it silently settles a commercial question that is not a documentation
# question. ⚠️ These are NOT counted as corrected.
# ─────────────────────────────────────────────────────────────────────────────────────────────────

FOUNDER_BLOCKED = {
    # ⚠️ EMPTY, AND DELIBERATELY KEPT. Its one entry (the glossary billing basis) was removed on
    # 2026-08-28: the claim was never blocked on a decision — it was already corrected, and the
    # registration rested on a master-level grep. It now sits in HALF_CUT above, where it belongs.
    # ⚠️ The category stays because "outstanding, but a decision rather than a doc defect" is a real
    # state, and the gate prints the row so a future reader can see it is genuinely zero.
}
