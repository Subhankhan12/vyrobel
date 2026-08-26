# -*- coding: utf-8 -*-
"""⚠️ THE LAST NINE — the reachable doc-truth claims, and the end of the correction work.

⚠️ COUNTED FROM THE GATE, NOT QUOTED: nine reachable claims across seven pages, and for the first time in
four passes the brief's set matched the data exactly.

⚠️ THREE OF THE NINE NEEDED NO CORRECTION, and saying so is the point of CONFIRM-FIRST. Two had BECOME TRUE
since the audit (the arrivals board really does plot `expected_arrival`; the arrival secrets really do have
an operator writer and an in-window release), and one is not a wording error at all — it is a founder
decision already recorded in code. They are registered in spec/half_cut.py rather than corrected.

⚠️ EVERY REPLACEMENT WAS CHECKED AGAINST THE RUNNING SYSTEM BEFORE IT WAS WRITTEN, and no number is typed.
"""

# ─────────────────────────────────────────────────────────────────────────────────────────────────
# guide-setup  ·  /docs/first-property
# ─────────────────────────────────────────────────────────────────────────────────────────────────

SETUP_NINE = {
    # ⚠️ HALF TRUE, AND THE FALSE HALF IS THE WORD "EVERY". VERIFIED BY COUNTING REFERENCES: `FrontDesk`
    # resolves the property's timezone (`CarbonImmutable::now($property?->timezone …)`), and
    # `HousekeepingBoard` and `OperatorDashboard` contain ZERO timezone references — they roll over on the
    # app default. So the setting is real and it does NOT reach every board.
    # ⚠️ THE CODE SIDE IS ARGUABLY THE WRONG ONE HERE, and it is REPORTED, NOT FIXED: the project convention
    # is to compute day logic in the property's timezone, and two boards do not. Changing that is product
    # behaviour, which this pass may not touch.
    'The timezone decides when your day rolls over on every board in the product, so use the property’s '
    'own, not yours.':
        'Set the timezone to the property’s own rather than yours — the front desk rolls its day over on '
        'it.',
}




# ─────────────────────────────────────────────────────────────────────────────────────────────────
# guide-reports  ·  /docs/reports-and-exports
# ─────────────────────────────────────────────────────────────────────────────────────────────────

REPORTS_NINE = {
    # ⚠️ FALSE, AND BACKWARDS. VERIFIED IN `PropertyContext`: an unset or invalid selection "falls back to
    # the first accessible property", and "All properties" is an EXPLICIT roll-up selection. A report
    # therefore opens on ONE property by default, not on everything the reader manages — the opposite of
    # what the page promises, and the kind of error that makes a figure look wrong rather than narrow.
    'Reports default to everything you manage.':
        'Reports open on your selected property, and “All properties” rolls the whole portfolio up.',
}

# ─────────────────────────────────────────────────────────────────────────────────────────────────
# article-pricing  ·  /resources/pricing-levers
# ─────────────────────────────────────────────────────────────────────────────────────────────────

PRICING_NINE = {
    # ⚠️ ONE SENTENCE, TWO FALSEHOODS — and the second was not in the audit's list at all. It was found
    # because this sentence had to be re-read to correct the first, and re-publishing a falsehood already
    # verified on `article-oos` this week would have been indefensible.
    #
    #   ① "Owners approve rate changes before they go live" — ⚠️ HALF TRUE, and newly so. `rate_proposals`
    #      (operator proposes → owner approves → applied through the certified `OwnerRateService`) is REAL,
    #      and it is OPTIONAL and limited to exclusively-owned units. An operator with `rates.manage` can
    #      still apply a rate directly through `POST /rates`, with no owner involved. So the mechanism is
    #      real and the blanket claim is not.
    #   ② ⚠️ "pushed to every channel" — FALSE, same evidence as `article-oos`: `FakeChannexClient` is the
    #      bound default and no channel connection is live. ⚠️ The replacement promises no future sync;
    #      the channels page is stopped by ruling.
    'Seasons, weekend uplift, per-night overrides and minimum stays in one grid, pushed to every channel. '
    'Owners approve rate changes before they go live.':
        'Seasons, weekend uplift, per-night overrides and minimum stays in one grid. On a unit with a '
        'single owner you can send a rate change for their approval before it goes live.',
}

ARRIVAL_NINE = {
    # ⚠️ THE NEEDLE IS ONE SENTENCE, NOT TWO — AND THAT IS THE FINDING. A first attempt quoted both
    # sentences of this paragraph as they appear in the master and came back a SILENT NO-OP. An earlier pass
    # had INSERTED a sentence BETWEEN them ("With it the ID is captured and held for the retention period…"),
    # so the two are no longer adjacent in the output.
    # ⚠️ AND A TRUNCATED READ OF THAT OUTPUT NEARLY MADE IT WORSE: at 200 characters the paragraph looked as
    # though the second sentence had been REPLACED, which would have meant the claim was already corrected.
    # It had not been — the sentence is intact further along, and the claim is genuinely outstanding. It was
    # briefly filed as half-cut on the strength of that misreading, and the gate's own broken-declaration
    # check is what refused it.
    #
    # ⚠️ WHAT IS FALSE: offering "you copy their passport details onto a form" as the thing online check-in
    # REMOVES implies Vyrobel produces the filing that form is for. VERIFIED: no guest register, police or
    # authority report exists anywhere in the codebase. ID capture itself is real and careful (encrypted at
    # rest on the private disk, purged 30 days after check-out), and the inserted retention sentence already
    # says so — this only stops the paragraph implying a legal output we do not produce.
    # ⚠️ IT STATES WHAT VYROBEL DOES AND DOES NOT DO, AND ASSERTS NOTHING ABOUT WHAT ANY LAW REQUIRES.
    'Without it, a guest arrives, you copy their passport details onto a form, and someone drives across '
    'town with a key.':
        'Without it, a guest arrives and someone drives across town with a key. Vyrobel stores the ID a '
        'guest submits; it does not produce or file a guest register for you.',
}

# ⚠️⚠️ WHY ONLY THREE TABLES EXIST FOR NINE CLAIMS — and the near-miss that produced this note.
#
# Corrections were WRITTEN for guide-gdpr, guide-access and guide-arrival, and all three came back as
# SILENT NO-OPs. ⚠️ THE CAUSE WAS NOT A BAD NEEDLE: an earlier pass had ALREADY CORRECTED ALL THREE PAGES,
# and in two cases in almost the same words this pass had just chosen ("Until you set it, a new account can
# reach every property"). The gate still listed them because the audit's quoted SENTENCE is TRUE and stayed —
# the half-cut shape, in three more places.
# ⚠️ IF THOSE SWAPS HAD MATCHED, THEY WOULD HAVE CORRECTED ALREADY-CORRECT TEXT — appending a second
# statement of the same caveat, or reverting a better sentence to a worse one. The zero-match check caught
# it. They are registered in spec/half_cut.py instead, which is where the shape belongs.
