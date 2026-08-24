"""⚠️ BATCH 1 OF THE ~120 — THE MONEY PAGES. Ordered by CONSEQUENCE, hardest first.

⚠️ A false sentence about how an owner gets PAID or how a guest gets money BACK is the kind a customer acts
on and then disputes. Every sentence below was checked against the code BEFORE it was written; the citations
are inline. ⚠️ Nothing is hedged — a hedge makes a false claim unfalsifiable.

⚠️ THE AUDIT IS A POINTER, NOT A QUOTATION (88 of its 220 fragments are the auditor's paraphrase), so every
`before` here is the PAGE's own wording, taken from the master.

SUBSYSTEMS VERIFIED ONCE (§N.8f) AND APPLIED ACROSS ALL THREE PAGES:
  · payouts    — Payout::LIVE_STATUSES = ['pending','processing','held','paid'] + 'failed'. There is NO
                 "Scheduled" state. `services.payouts.engine_enabled` defaults FALSE (config/services.php:116).
                 `Iban::isValid()` runs as a validation rule at ENTRY (OwnerPayoutMethodController:32).
                 `OwnerPayoutService::hold()` has exactly ONE caller — PayoutRunService:161, the tax hold.
                 `createFromStatement()` excludes only LIVE_STATUSES, so a failed payout is retried with no
                 check that anything changed. `PayoutRecovery::blockers()` covers exactly two Connect states.
  · statements — `service_date` for a stay's room revenue is `check_in` for the WHOLE stay
                 (OwnerAttributionService:311), so a stay is NOT split by night. Commission is always
                 allocated on GROSS room revenue (:346) — there is no configurable base. There is NO
                 scheduled statement generation; the only real caller is the operator route
                 `owners.close-period`. There is NO unpublish/unfinalize/unlock anywhere.
  · refunds    — the cancel path reads the PROPERTY-level `free_until_days`
                 (CancellationPolicy::freeUntilForCheckIn) and answers ONE binary question. There is no
                 refundable-amount calculation. `reschedule()` never reprices (AssignmentService:418).
"""

PAYOUTS = {
    # ⚠️ HALF TRUE, and only the false half goes. A per-owner payout day and offset ARE real
    # (PayoutSchedule), but on the shipped configuration NO run happens at all: `payouts:run` self-guards on
    # `services.payouts.engine_enabled`, which defaults to false and is absent from .env.
    'run-day': {
        'before': 'The run happens on the payout day set for that owner, offset from the statement date by '
                  'the number of days in Settings.',
        'after': 'Each owner has a payout day and an offset from the statement date. Automated runs are off '
                 'until the payout engine is enabled for your account; until then a run is started by hand.',
    },
    # ⚠️ NONE of the three stated causes can occur. An IBAN failing the ISO 7064 mod-97 checksum is rejected
    # at entry and can never reach a payout; there is no account-holder name verification anywhere; and the
    # structured "what to fix" guidance covers exactly two Stripe-Connect conditions, not three bank causes.
    'failure-causes': {
        'before': 'The commonest causes are an IBAN that fails checksum validation, a name mismatch against '
                  'the account, and an account that has been closed. All three are the owner\'s to correct, '
                  'and all three are stated plainly on their dashboard rather than routed through you.',
        'after': 'An IBAN that fails its checksum is rejected when it is entered, so it never reaches a '
                 'payout. What the owner sees on their dashboard is the reason the bank returned, when one '
                 'comes back, plus specific guidance for the two account states we can detect: no connected '
                 'payout account, and payouts not yet enabled on it.',
    },
    # ⚠️ It does NOT return to Available. "Available" is Σ over UNSTAMPED ledger rows (statement_id === null);
    # a failed payout's statement is already stamped, so the amount sits in a separate carried-forward bucket.
    'returns-to-available': {
        'before': 'The amount returns to the owner\'s available balance and the next run picks it up once '
                  'the details are fixed.',
        'after': 'The amount stays with its statement and is carried forward rather than returning to the '
                 'available balance, which counts only earnings no statement has claimed yet.',
    },
    # ⚠️ FLATLY CONTRADICTED BY THE CODE. When the engine is on, `createFromStatement()` re-creates and
    # re-attempts a failed statement's payout on every run, with NO check that anything changed.
    'manual-retry': {
        'before': 'Retries are manual by design. An automatic retry against details that have not changed '
                  'just fails again and, at some banks, costs a fee each time.',
        'after': 'A failed payout is retried on the next run. Fix the account details before that run, '
                 'because a retry against unchanged details fails the same way.',
    },
    # ⚠️ TWO CLAIMS IN ONE SENTENCE. (1) "withdrawable on demand" is false and the product says so: there is
    # NO owner-initiated transfer rail, and "Withdraw early" renders disabled. (2) the Overview's "Paused"
    # state is driven by OwnerNextPayout::rail()['paused'] = (pending payout) AND (no tax details) — the TAX
    # hold, not the owner's own payout pause.
    'paused-withdrawable': {
        'before': 'Paused funds sit in their balance, visible and withdrawable on demand, and the Overview '
                  'page says so rather than showing an unexplained zero.',
        'after': 'Paused funds stay in their balance and stay visible, so the Overview never shows an '
                 'unexplained zero. They are released by the next run once payouts resume — there is no '
                 'withdraw-on-demand.',
    },
    # ⚠️ NO OPERATOR PER-PAYOUT HOLD EXISTS. `hold()` has one caller (the engine's tax hold) and no route
    # reaches it; there is no reason field to attach. ⚠️ Removing this leaves the "Pausing payouts" section
    # shorter but not hollow — the owner-side pause above it is real.
    'operator-hold': {
        'before': 'You can also hold a specific payout from your side, for a disputed expense or an '
                  'outstanding document. Do it with a reason attached; a hold the owner cannot see the '
                  'cause of is the fastest way to lose their trust in the whole statement.',
        'after': 'A payout is held automatically when the owner still owes tax details, and released as soon '
                 'as those are supplied. Holding an individual payout by hand is not something you can do '
                 'from the operator side.',
    },
}

STATEMENTS = {
    # ⚠️ THE OPPOSITE of what the code does: one room_revenue row per stay, stamped service_date = check_in.
    'split-by-night': {
        'before': 'A stay spanning two months is split by night, not assigned whole to one of them.',
        'after': 'A stay is assigned whole to the month it checks in, not split across the two.',
    },
    # ⚠️ There is NO configurable commission base. Commission is ALWAYS allocated on GROSS room revenue,
    # before any OTA deduction.
    'commission-base': {
        # ⚠️ The needle starts after the `</strong>` that splits this text run — the sentence as a reader
        #    sees it is not one string in the markup.
        'before': 'is your commission from the owner\u2019s agreement, applied to the base '
                  'defined there — gross or net of channel commission, which is a decision you make once '
                  'per owner.',
        'after': 'is your commission from the owner\u2019s agreement, always applied to gross '
                 'room revenue — before channel commission is taken off.',
    },
    # ⚠️ HALF TRUE. Generation is REAL (the operator route `owners.close-period` finalizes a period — a
    # caller built after the audit was written), but it is MANUAL: there is no scheduled generation and no
    # month-close trigger of any kind.
    'monthly-schedule': {
        'before': 'Statements generate on the schedule set in Settings. You review rather than assemble.',
        'after': 'You close a period for an owner and the statement is assembled from the ledger. You review '
                 'rather than assemble it.',
    },
    # ⚠️ A DRAFT IS VISIBLE TO THE OWNER. `OwnerPortalService::statements()` applies NO status filter, so
    # drafts are listed on the owner Dashboard, the Payouts page and the Statement history table.
    'draft-invisible': {
        'before': 'A draft statement per owner appears when the month closes. Nothing is sent yet and '
                  'nothing is visible to the owner.',
        'after': 'Closing a period produces the statement for that owner. Nothing is sent yet, but a '
                 'statement is listed in the owner\'s portal as soon as it exists.',
    },
    # ⚠️ There is NO expense approval threshold in the product, and no operator statement-review screen
    # (no statement route exists under /app). ⚠️ Removing the invented triage list leaves the step thinner;
    # what remains is what the statement genuinely shows.
    'flagged-lines': {
        'before': 'Anything unusual is surfaced: an expense above the owner\'s approval threshold, a '
                  'refund, a month with no bookings.',
        'after': 'The statement lists its lines in full — room revenue, commission, expenses and any '
                 'refunds — so an unusual month is visible in the breakdown.',
    },
    # ⚠️ NO unpublish, unfinalize or unlock exists anywhere, and no time window of any kind. finalize()
    # throws on an already-finalized statement.
    'unpublish-window': {
        'before': 'For a genuine error caught before the owner has seen it — within minutes of publishing — '
                  'you can unpublish, fix, and republish. After that, use an adjustment.',
        'after': 'Closing a period locks the statement. A finalized statement cannot be reopened, so a '
                 'correction is made as an adjustment on the next one.',
    },
}

REFUNDS = {
    # ⚠️ The cancel path reads the PROPERTY-level policy, not the rate plan's.
    'rate-plan-policy': {
        'before': 'The policy on the rate plan decides the default.',
        'after': 'The property\'s cancellation policy decides the default.',
    },
    # ⚠️ TWO CLAIMS IN ONE PASSAGE. There is no refundable-amount calculation and no working shown — the
    # policy answers ONE binary question (is the cancellation on or before check-in minus free_until_days?).
    # And the cancel form has exactly one input, a free-text reason capped at 255 characters: there is no
    # amount to override and therefore no override to attribute.
    'proposes-amount': {
        'before': 'Vyrobel calculates what is refundable under the cancellation policy attached to the '
                  'booking, and shows the working. You can override it — the override is recorded with '
                  'your name on it.',
        'after': 'Vyrobel checks one thing: whether the cancellation falls inside the free-cancellation '
                 'window. Inside it, the payment is refunded in full. Outside it, nothing is refunded '
                 'automatically — issue any refund yourself on the refund screen, where the reason is '
                 'recorded against your name.',
    },
    # ⚠️ NO SUCH CHOICE EXISTS. The cancel form offers no destination at all; the refund screen's "Refund to"
    # radio is Original method vs Manual bank transfer, and it feeds a note — not a wallet credit.
    'refund-or-credit': {
        # ⚠️ NEEDLE NARROWED: the sentence that followed this one is already owned by a G6 RATIONALE
        #    reframe, so this transform must not reach into it. Only the false clause is replaced — the
        #    cancel flow offers no refund-or-credit choice at all.
        'before': 'Money back to the original payment method, or wallet credit for a future stay.',
        'after': 'Money back to the original payment method, or by a bank transfer you make yourself. '
                 'Wallet credit is issued from the guest\u2019s wallet, not as part of a cancellation.',
    },
    # ⚠️ There is no countdown anywhere in the guest surfaces — the wallet renders a static expiry DATE.
    'countdown': {
        'before': 'The guest sees the countdown on their account.',
        'after': 'The guest sees the credit and its expiry date in their wallet.',
    },
    # ⚠️ HALF TRUE, and the true half stays. The nights DO release to availability immediately (the cancel
    # path is synchronous). What is false is "across every channel": the container resolves the channel
    # client to FakeChannexClient because CHANNEX_FAKE is true and no API key is set, so nothing reaches a
    # marketplace on the shipped configuration.
    'every-channel': {
        'before': 'The nights release to availability immediately and go back on sale across every channel.',
        'after': 'The nights release to availability immediately and go straight back on sale on your own '
                 'storefront. They reach a marketplace once that channel connection is live.',
    },
    # ⚠️ `reschedule()` NEVER REPRICES. It locks availability over old ∪ new nights, releases and re-claims,
    # updates the dates, and returns — it never opens the folio.
    'reprice-on-move': {
        'before': 'When a change moves the booking to a more expensive period, the difference appears on '
                  'the folio as a balance owing. When it moves cheaper, you choose whether to refund the '
                  'difference, hold it as credit, or keep it under your policy.',
        'after': 'Moving a booking changes its dates and its unit; it does not reprice the stay. If the new '
                 'dates should cost more or less, post the difference to the folio yourself.',
    },
    # ⚠️ Reports never net refunds off, in any period. Operator report revenue is summed from folio_items —
    # the charge side — while a refund is a `payments` row and creates no folio item.
    'reports-net-off': {
        # ⚠️ `Reports</strong> net it off …` — the needle starts after the closing tag, and the following
        #    sentence lives in a SEPARATE paragraph, so only the false clause is replaced.
        'before': ' net it off revenue for the period the stay fell in, not the period you issued it.',
        'after': ' show revenue from what was charged on the folio, while a refund is recorded against the '
                 'payment — so read the two together rather than expecting one net figure.',
    },
    # ⚠️ There is NO goodwill-credit feature. "Goodwill" is one option in the refund screen's REASON
    # dropdown, and choosing it issues a MONEY refund (bounded by invariant #27) — not a wallet credit, and
    # not something issued without cancelling.
    #
    # ⚠️⚠️ AND THE SECOND HALF IS WORSE THAN WRONG — IT IS BACKWARDS. The page says who bears the cost
    # "depends on cause"; nothing asks, and `AdjustOwnerLedgerOnRefund` debits the owner's ledger on every
    # succeeded refund against an owner-attributed reservation. ⚠️ REPORTED AS A PRODUCT DEFECT, NOT FIXED
    # HERE — this pass corrects prose.
    'goodwill-credit': {
        'before': 'A goodwill credit is issued against the booking without cancelling anything — the guest '
                  'keeps their stay history and gains wallet credit toward the next one.',
        'after': 'A goodwill refund is issued against the booking without cancelling anything, so the guest '
                 'keeps their stay history. It is money back against the original payment, not wallet '
                 'credit.',
    },
    # ⚠️⚠️ THE SECOND HALF LIVES IN ITS OWN PARAGRAPH — and it is not merely wrong, it is BACKWARDS.
    #    Nothing asks who bears the cost; `AdjustOwnerLedgerOnRefund` (wired in AppServiceProvider:439)
    #    debits the owner's ledger on every succeeded refund against an owner-attributed reservation.
    #    ⚠️ REPORTED AS A PRODUCT DEFECT — this pass corrects the prose, not the behaviour.
    'goodwill-bearer': {
        # ⚠️ THE WHOLE PARAGRAPH GOES. The audit's cell is compound — its second half, "Vyrobel asks you
        #    which, and does not guess", is the more dangerous sentence: it promises a control that does
        #    not exist AND asserts the product does not do the thing it in fact always does.
        'before': 'Whether that lands on the owner or on you depends on cause. A credit for a broken '
                  'boiler is a property cost and goes to the owner\u2019s statement; a credit for a '
                  'service failure by your team is an operator cost. Vyrobel asks you which, and does not '
                  'guess, because guessing wrong is an argument.',
        # ⚠️ UPDATED IN THE SAME PASS AS THE PRODUCT CHANGE. The previous correction said the cost ALWAYS
        #    lands on the owner — true when it was written, FALSE the moment the borne-by override shipped.
        #    ⚠️ Leaving it would have re-created, by this arc's own hand, the exact defect the arc exists to
        #    remove: a page describing behaviour the product no longer has.
        'after': 'A refund against an owner-attributed booking is deducted from that owner\u2019s share by '
                 'default. When it is your team\u2019s cost rather than theirs, mark it as yours on the '
                 'refund screen and the owner is not charged \u2014 they see it noted on their booking, '
                 'without a deduction. The choice is recorded with the refund and '
                 'cannot be changed afterwards \u2014 correct a mistake with an adjustment on the next '
                 'statement.',
    },
}
