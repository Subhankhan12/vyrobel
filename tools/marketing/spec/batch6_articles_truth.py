# -*- coding: utf-8 -*-
"""⚠️ BATCH 6 · THE DOC-TRUTH FALSE CLAIMS ON THE THREE ARTICLES NOBODY HAD CORRECTED.

⚠️⚠️ TWO REGISTERS, ONE PAGE — AND THAT IS WHY THESE SURVIVED. `g6_articles.py` covers the G6
UNSOURCED-OPINION register and deliberately produces RECOMMENDATIONS rather than transforms, because
reframing a persuasive essay guts it. The Gate-5 doc-truth FALSE claims on those same pages are a
DIFFERENT list, and no module had ever addressed them. The `six_uncorrected.py` docblock names this exact
failure shape for `article-housekeeping`; it held for three more pages.

⚠️ THESE ARE ARTICLES, AND THE GENRE CHANGES THE CORRECTION. Two of the three are ADVICE — "Structure it
that way", "Make it link to something", "whatever threshold YOU agreed". An advice sentence addressed to
the reader is not a claim about Vyrobel, and rewriting it into a product description would be the wrong
fix applied carefully. So the rule used throughout below is:

    ⚠️ CORRECT WHERE THE PAGE PROMISES A VYROBEL CAPABILITY. LEAVE ADVICE THAT AN OPERATOR CAN ACT ON
      WITHOUT VYROBEL, EVEN WHERE THE PRODUCT DOES NOT IMPLEMENT IT.

⚠️ EVERY REPLACEMENT WAS CHECKED AGAINST THE CODE BEFORE IT WAS WRITTEN, and the check is cited inline.
⚠️ NO REPLACEMENT NEGATES BY QUOTING — none of them contains the falsehood it replaces.
⚠️ NO NUMBER IS TYPED. Where a figure was false it is REMOVED, never re-estimated.
"""

# ─────────────────────────────────────────────────────────────────────────────────────────────────
# article-trust  ·  1 FALSE claim  ·  ⚠️ THE ONE OUR OWN PAYMENT SWITCH BEARS ON
# ─────────────────────────────────────────────────────────────────────────────────────────────────

TRUST_TRUTH = {
    # ⚠️⚠️ "on your own domain" IS FALSE, AND IT IS FALSE IN THE DIRECTION THAT MATTERS: it tells a
    # prospect the card form lives on their site. It does not, and it never did.
    #
    # VERIFIED IN CODE: `StripeCheckoutSession`'s own docblock — "touches our server (PCI SAQ A). We keep
    # only the id + the redirect `url`". `StripeClient::createPaymentCheckout` creates a Stripe Checkout
    # Session and the guest is REDIRECTED to Stripe's hosted page. `StripeGateway` — "a raw PAN/CVV never
    # reaches this class".
    #
    # ⚠️ CONFIRM-FIRST CUT BOTH WAYS. The paragraph's ARGUMENT — that a guest pattern-matches a familiar
    # card form, and that a bank-transfer request breaks the match — is untouched and stays. Only the
    # location claim was wrong. And the direct-charge switch makes the honest version STRONGER than the
    # false one: the charge is taken on the operator's OWN Stripe account, not the platform's.
    #
    # ⚠️ WHAT IS DELIBERATELY *NOT* CLAIMED: that the guest's bank statement shows the operator's name.
    # On a Standard connected account the statement descriptor comes from the CONNECTED ACCOUNT's own
    # settings, which Vyrobel cannot set — established and recorded when the onboarding screen shipped.
    'A recognised card form, on your own domain, with the padlock.':
        'A card form the guest already recognises, with the padlock. Vyrobel hands the payment step to '
        'Stripe’s hosted checkout, so card details never touch your site — and the charge is '
        'taken on your own Stripe account rather than ours.',
}

# ─────────────────────────────────────────────────────────────────────────────────────────────────
# article-owners  ·  ⚠️ THE CTA IS WHERE THE ADVICE BECOMES A PRODUCT PROMISE
# ─────────────────────────────────────────────────────────────────────────────────────────────────

OWNERS_TRUTH = {
    # ⚠️⚠️ THE CTA CARRIES TWO FALSE CLAIMS AND TWO TRUE ONES — the S1-044 shape, one sentence, four
    # assertions. Replacing the sentence corrects both false halves and KEEPS both true ones.
    #
    # VERIFIED: (1) "expenses linked to their work orders" — `owner_expenses` carries a polymorphic
    # `source_type`/`source_id`, and `MaintenanceService::correctCost` really does write the ticket as
    # that source, so the DATA link is real. But the owner surface NAMES it and renders no link:
    # `OwnerExpenses` — "the polymorphic source (maintenance ticket / housekeeping task) is still shown
    # when present". There is no attachment of any kind — `owner_expenses` has no document or media
    # relation. So "linked to" overstates a real thing; "names" is exact.
    # (2) "approvals above a threshold you set" — HALF TRUE and newly so. The approval step is REAL
    # (`owner_expenses.approval_state` pending/approved/queried; a deductible expense posts no ledger
    # line until approved). ⚠️ THE THRESHOLD IS NOT: grep finds no spending threshold anywhere. The one
    # `auto_approve_pct` that exists belongs to the PRICING guardrail (`OwnerGuardrailController`, beside
    # `pricing_mode`/`floor`/`ceiling`) and governs rate proposals, not spend.
    # (3) "Statements with the bookings behind them" and (4) "a payout date the owner can see coming"
    # are TRUE and are left exactly as written — `PayoutSchedule::nextRunDate` renders a real next date.
    'Statements with the bookings behind them, expenses linked to their work orders, approvals above a '
    'threshold you set, and a payout date the owner can see coming.':
        'Statements with the bookings behind them, expenses that name the ticket they came from, an '
        'approval step before a deductible cost reaches their statement, and a payout date the owner '
        'can see coming.',

    # ⚠️ The body's demo instruction, same correction, same evidence: the portal shows the source, and
    # opening the ticket from it is not something an owner can do.
    'where an expense links to the work order behind it':
        'where an expense names the ticket behind it',

    # ⚠️ CUT ONLY THE FALSE HALF. Everything else in this sentence is REAL and stays verbatim: a cleaner
    # really can photograph it (`housekeeping.photos.store`), a ticket really is raised
    # (`MaintenanceTicket`), and the unit really can stop selling (`OwnerUnitStatusService` recomputes
    # availability so a paused unit takes no new bookings). ⚠️ Only "with the ticket attached" is false —
    # the cost reaches the statement inside the aggregated `owner_statements.expenses_minor` bucket, and
    # the ticket is named on the expenses page, not attached to the statement line.
    'and you see the cost on your statement with the ticket attached.':
        'and you see the cost on your statement, with the ticket it came from named on your expenses '
        'page.',

    # ⚠️⚠️ A TYPED READING TIME THAT IS ROUGHLY DOUBLE THE REAL ONE. MEASURED, NOT ESTIMATED: the article
    # is 987 words of rendered prose, which is four to five minutes at any ordinary reading speed.
    # ⚠️ THE FIGURE IS REMOVED RATHER THAN RE-TYPED. Replacing one invented number with another number
    # this pass invented is the same error with a better estimate, and the standing rule is not to type
    # one. The publication date is real and stays.
    '· 10 min read · 29 April 2026': '· 29 April 2026',
}

# ─────────────────────────────────────────────────────────────────────────────────────────────────
# article-statement  ·  ⚠️ THE `S1-182` PAGE (founder ruling, DECISIONS 2026-08-24)
# ─────────────────────────────────────────────────────────────────────────────────────────────────

STATEMENT_TRUTH = {
    # ⚠️ THE EXAMPLE STATEMENT IS ILLUSTRATIVE — "Villa Azure", inside an advice article about how to
    # DESIGN a statement. It is not a screenshot. But it is published on Vyrobel's own site under
    # Vyrobel's name, so a line a prospect reads as "this is what I will get" has to be one they can get.
    #
    # ⚠️⚠️ "Management fee · 15% of net" IS WRONG TWICE OVER, and the second way is the one the
    # article itself would object to.
    #   ① THE LABEL CONTRADICTS ITS OWN ARITHMETIC. €1,280.04 is the fee on gross minus channel
    #      commission (9,420.00 − 886.40). "Net" in this very table is the LAST line, €6,923.56.
    #      An article whose thesis is "six lines, the owner can add them up" cannot mislabel its own
    #      basis — that is precisely the call it is teaching operators to avoid.
    #   ② THE RATE READS AS OURS. The real management-fee rate is per-owner, read from the governing
    #      `ManagementAgreement` through the shared `OwnerCommissionRate`, and the demo's is not this
    #      figure. A hardcoded 15% on a Vyrobel page is the exact error the owner Reports and Settings
    #      work was careful not to make.
    # ⚠️ THE NUMBER IS REMOVED, NOT REPLACED. Naming the basis is what makes the line addable; asserting
    # a rate is what made it false.
    'Management fee · 15% of net':
        'Management fee · the rate in your agreement, on revenue after channel commission',

    # ⚠️ THE OTA BRAND SPLIT IS NOT MODELLED AND CANNOT BE. VERIFIED: `reservations.source` is an enum of
    # `direct, walk_in, ota, corporate, group, agent, manual` — there is no Booking.com and no Airbnb
    # in it, and nothing downstream can recover one. Naming the brands promises a breakdown the product
    # cannot produce; "OTA bookings" is exactly what the data supports.
    'Channel commission · Booking.com, Airbnb':
        'Channel commission · OTA bookings',

    # ⚠️ THE TWO "view" AFFORDANCES. No owner surface renders a link from a statement line to the thing
    # behind it — there is no such link and no attachment. ⚠️ The example keeps NAMING what broke,
    # which is the article's actual advice and which the product does do.
    # ⚠️⚠️ THE NEEDLE DELIBERATELY CARRIES NO COLOUR. The master writes these spans with a hex that an
    # EARLIER pass rewrites to a design token (`#A8380B` → `var(--vy-accent-text)`), so a needle quoting
    # either one is chained to a value the pipeline owns — it matched the master, found nothing at swap
    # time, and the zero-match check caught it. Matching from the semicolon onwards is independent of
    # both, and `str.replace` takes both occurrences, which is correct: they are the same affordance.
    'font-size: 12.5px;">view</span>': '</span>',

    # ⚠️ …and the sentence that depended on those links. "Names itself" is TRUE and stays; "link to the
    # thing that caused them" is the false half and is the only part cut.
    'Every deduction names itself, and the two that are not routine link to the thing that caused them.':
        'Every deduction names itself, and the two that are not routine say what caused them.',

    # ⚠️ ADVICE VYROBEL CANNOT SUPPORT, ON VYROBEL'S OWN PAGE. `owner_expenses` has no document, media or
    # invoice relation — nothing can be attached to an expense, so "a paper trail the owner can open"
    # describes an affordance that does not exist. ⚠️ The surrounding advice is kept intact, including
    # "Name what broke, not the category" (true, and the product supports it) and the threshold sentence,
    # which is about the AGREEMENT the operator writes and needs nothing from us.
    'Attach the work order or the invoice, so the figure has a paper trail the owner can open.':
        'Name the ticket the cost came from, so the figure has a source the owner can trace.',

    # ⚠️ NO OWNER CAN "OPEN THE REFUND". VERIFIED: `payments.refund_borne_by` is real and is a genuinely
    # useful distinction (owner vs operator — an operator-borne refund does not reduce the owner's net),
    # but it is an ATTRIBUTION, not a narrative, and no owner surface renders a refund reason an owner
    # can read. ⚠️ The paragraph's argument is kept whole — the contrast between a figure that vanishes
    # unexplained and one that carries its reason is the section's point, and it survives being stated
    # as something the operator writes rather than something the portal opens.
    'An owner who can open the refund and read that a boiler failed on the second night of a '
    'five-night stay usually agrees it was the right call.':
        'An owner who is told that a boiler failed on the second night of a five-night stay usually '
        'agrees it was the right call.',

    # ⚠️ THE CTA — the one place this page stops advising and starts promising. Same evidence as the
    # owners article: the source is named, never attached. ⚠️ "Owners see performance and never a guest's
    # private data" is TRUE (invariant #28, and the owner surfaces are name-only) and is kept verbatim.
    'Every statement, the bookings behind it, each expense with its work order attached, and the payout '
    'that followed.':
        'Every statement, the bookings behind it, each expense naming the ticket it came from, and the '
        'payout that followed.',
}


# ─────────────────────────────────────────────────────────────────────────────────────────────────
# article-direct  ·  ⚠️ THE PAGE NO REPORT HAD NAMED — found by counting the gate, not by reading a list
# ─────────────────────────────────────────────────────────────────────────────────────────────────

DIRECT_TRUTH = {
    # ⚠️⚠️ THE SAME FALSEHOOD AS `article-trust`, ON A PAGE THE LAST PASS NEVER OPENED — and here it is
    # bigger, because it is the BOOKING ENGINE and not only the card form.
    #
    # ⚠️ CONFIRM-FIRST NEARLY WENT THE OTHER WAY: `organizations.custom_domain` EXISTS as a column, which
    # reads like support for exactly this. VERIFIED THAT IT IS INERT — its only appearance outside the
    # migration is in `$fillable`; nothing reads it; and `Route::domain(` appears NOWHERE in the codebase.
    # The storefront is reachable only at Vyrobel's own host under `/book/{property}`.
    # ⚠️ The inert column is reported as a code-side finding, NOT fixed here.
    'Get a booking engine on your own domain that shows live availability and takes a card.':
        'Get a booking engine that shows live availability from the same calendar your front desk uses '
        'and takes a card.',

    # ⚠️ Same evidence; the sibling sentence on the same page. The ARGUMENT — that a familiar card form
    # reassures and a bank-transfer request reads as risk — is true and is kept whole.
    'A recognised card form on your own domain.':
        'A recognised card form, handled by Stripe\u2019s hosted checkout.',

    # ⚠️ MEASURED: 1,011 words, which is four to five minutes. The figure is REMOVED, not re-estimated —
    # the same rule applied to the owners article's byline. The publication date is real and stays.
    '\u00b7 14 min read \u00b7 22 July 2026': '\u00b7 22 July 2026',
}


# ─────────────────────────────────────────────────────────────────────────────────────────────────
# article-oos  ·  ⚠️ ALSO FOUND BY COUNTING — its one claim was filed UNMATCHED, so no list showed it
# ─────────────────────────────────────────────────────────────────────────────────────────────────

OOS_TRUTH = {
    # ⚠️ ONE FALSE CLAUSE IN A SENTENCE OF FOUR, and the other three are REAL — so three-quarters of this
    # sentence stays exactly as written. VERIFIED:
    #   • "Raised from the room with a photo" — REAL (`housekeeping.photos.store`, `MaintenanceTicket`).
    #   • ⚠️ "blocking every channel immediately" — FALSE. `FakeChannexClient` is the bound default
    #     (`services.channex.fake`, default true); no channel connection is live, so nothing is blocked
    #     on any OTA. ⚠️ The replacement states what DOES happen and promises NO future channel sync —
    #     the channels page is stopped by ruling and nothing here may imply otherwise.
    #   • "listing the bookings that need moving" — REAL: `maintenance-ticket-detail.blade.php` renders
    #     "real affected bookings", and the list view warns that booked nights will need moving.
    #   • "putting the repair cost on the owner\u2019s statement when it resolves" — REAL
    #     (`MaintenanceService::correctCost` \u2192 `OwnerExpense` \u2192 owner ledger \u2192 statement).
    'Raised from the room with a photo, blocking every channel immediately, listing the bookings that '
    'need moving, and putting the repair cost on the owner\u2019s statement when it resolves.':
        'Raised from the room with a photo, taking the unit off sale immediately, listing the bookings '
        'that need moving, and putting the repair cost on the owner\u2019s statement when it resolves.',
}
