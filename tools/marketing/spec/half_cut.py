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
