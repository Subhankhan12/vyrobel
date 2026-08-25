"""⚠️ `/faq` + `/glossary` — the SOURCE corrections, applied to the DCLogic arrays BEFORE anything renders.

Full verification record (all 25 questions + all 35 terms, each with its code fact):
`docs/FAQ-GLOSSARY-SOURCE-VERIFICATION.md`.

⚠️⚠️ THE MEASURED COST OF THE BLIND SPOT. These pages were invisible to every sweep, so claims that had
already been investigated and ruled ELSEWHERE went on standing here. They are not fresh findings and their
corrections were already written:
  · "owners cannot see guest names" — ruled and narrowed under #28. Owners DO see the name, name-only, by
    design; contact details and documents are correctly never shown. Corrected on the owner surfaces long
    before this page was readable.
  · the four-step rate precedence — batch 4 established TWO levels, not four.
  · "cannot be sold to anyone, at any price" (min stay / orphan night) — corrected on `article-pricing`
    this arc, after our own restriction override made it false again.
  · per-unit billing — corrected on `guide-setup` and `guide-settings` in batch 5.
  · "Growth"/"Starter" tiers — corrected on `guide-setup` in batch 5.
  · channels "connect directly" — the channels page is STOPPED; corrected on `guide-housekeeping`/`docs`.
  · the arrival-secret release condition — #33, corrected on `guide-arrival` in batch 3.
  · CSV-or-JSON account export — corrected on `guide-settings` in batch 5.
  · occupancy excludes out-of-service — `R1`, corrected on `guide-reports` in batch 5.
  · org-wide 2FA enforcement — corrected on `docs` in batch 5.

⚠️ THE THREE HELD CLAIMS ARE NOW SETTLED FROM THE CODE — none had to be held again:
  · FLOW OF FUNDS — **FALSE**. `StripeClient::createPaymentCheckout()` creates the session on the PLATFORM
    account: no `stripe_account`, no `on_behalf_of`, no `transfer_data`, no `application_fee_amount`. Only
    OWNERS carry `stripe_connect_account_id`; an organization has none. Guest money therefore lands in the
    platform's Stripe balance and reaches an owner as a Connect TRANSFER out of it (`createTransfer`).
    ⚠️ "We never hold client funds" is the opposite of what is built. ("We store payment references rather
    than card numbers" IS true — hosted Checkout, no PAN, SAQ A.)
  · GUEST SELF-SERVE ERASURE — **FALSE**. No guest-realm route or view offers export or erasure. The
    operator has both on the Guests CRM; the guest cannot self-serve.
  · FAILED-PAYOUT RETRY — **MOSTLY TRUE, and it is BUILT** (`OwnerPayoutService::retry()`, row-locked with
    an inside-the-lock status re-check; `owner.payouts.retry`, throttled). ⚠️ A previous pass reported
    `DO3-3` as open from a memory note — it is not. Two details in the answer are still wrong: a failed
    payout shows as **Cleared**, not back in Available (`OwnerBalance:111,118`), and "the next run picks it
    up" contradicts the same answer's "retries are manual".
"""

# ⚠️ A ROUTE, NEVER AN OUTCOME. "Contact us and we'll help you migrate" is the same unbuilt promise in
#    different clothes, and it fails during someone's onboarding. These say where to go and NOTHING about
#    what will happen — no timescale, no service level, no "we'll".
#    ⚠️ The route is verified live: `GET /contact` → `marketing.page.contact`, `pending('contact') === false`.
# ⚠️ A FIRST DRAFT READ "…and we will tell you what is possible". That is still a commitment — it
#    promises a reply and an assessment — and the rule is NO "we'll" at all. This names the route and
#    stops.
_CONTACT = 'The contact page is where to ask.'

FAQ_CORRECTIONS = {
    # ── PRICING — the security bar. Two plans, Essential and Pro, both FLAT. No number is typed. ──
    'How much does Vyrobel cost?':
        'A flat subscription per account, billed monthly or yearly, exclusive of VAT. It does not change '
        'with your unit count. There are two plans, and the current prices are on the pricing page rather '
        'than repeated here. What differs from the market is what the number includes: the booking engine, '
        'owner portal and guest portal are in the plan rather than priced as modules.',

    'What counts as a unit?':
        'Anything you sell independently and clean separately. A two-bedroom apartment is one unit. A villa '
        'with a separate guest cottage you let apart is two. A connecting room only ever sold as part of a '
        'suite is not. Your unit count does not affect your bill — the subscription is one figure per '
        'account — but it is how housekeeping, maintenance and availability resolve.',

    'Do you charge per property or per user?':
        'Neither, and not per unit either: the subscription is one flat figure per account. Adding a '
        'property or a staff account costs nothing. That is deliberate — charging per seat pushes operators '
        'to share logins, which is exactly what makes an audit trail worthless.',

    'Can I cancel whenever I want?':
        'Yes. There is no exit fee and no notice period. Exports today are per screen and CSV only — '
        'reports and maintenance costs each download their own file — so there is no single action that '
        'produces a copy of the whole account, and no JSON export. If you need everything, ask us.',

    # ── MIGRATION — ⚠️ these described a service that does not exist. Now ROUTES. ──
    'How long does migration take?':
        'We are not going to quote you a timeline. There is no migration service and no import tool behind '
        'this answer, so any number here would be a guess about work nobody has scoped. '+_CONTACT,

    'Will I lose bookings when I migrate?':
        'Nothing about your existing system changes when you open a Vyrobel account, and your channels keep '
        'selling from wherever they sell from today. But there is no importer that reads another system, so '
        'moving future reservations across is manual work, and the size of it depends on your setup. '
        +_CONTACT,

    'What does migration cost?':
        'There is no onboarding package to price, because there is no migration service. '+_CONTACT,

    'Can I run both systems at once?':
        'Nothing stops you, and it is a sensible way to find out whether Vyrobel fits how you work while '
        'nothing is at risk. Be aware that keeping two systems in step is your work rather than ours — '
        'there is no sync between them and no importer.',

    # ── OWNERS ──
    # ⚠️⚠️ THE PRIVACY CLAIM. Already ruled and narrowed under #28 — this page simply never got the memo.
    'What can property owners see?':
        'Performance on their own units, the bookings behind it, expenses charged to them, and their '
        'statements and payouts. A booking shows the guest’s NAME so the owner can recognise a stay on '
        'their own calendar — and nothing else about them: no email, no phone, no documents, no messages. '
        'They cannot see other owners’ units or terms, or your arrangements with anyone else. This is '
        'enforced by scope on every query rather than by hiding parts of a page, so changing a URL reveals '
        'nothing.',

    'How are owner statements produced?':
        'They assemble themselves. Revenue comes from the stays split by night, channel commission from the '
        'booking records, your fee from the owner’s agreement, expenses from resolved maintenance '
        'tickets and from expenses raised against their units. A stay is claimed once the guest has actually '
        'paid, so an unpaid booking stays in the ledger as owed and is picked up by a later statement. '
        'Nothing is typed in at month end, and every deduction links back to the thing that caused it.',

    'What happens when a payout fails?':
        'The reason comes back from the bank and is shown verbatim on the owner’s payouts page rather '
        'than the payout simply not arriving. The amount stays on their statement as cleared but unpaid, '
        'and the owner can ask for another attempt once the details are corrected. Retries are deliberate '
        'rather than automatic, because an automatic retry against unchanged details just fails again.',

    # ── GUESTS ──
    'Which channels do you connect to?':
        'None yet. Channel sync is not switched on, so availability and rates are not pushed anywhere and '
        'no reservations come back — you maintain your marketplace listings where they are today. Reviews '
        'and historical message threads would in any case stay on the channel that collected them; those '
        'are not ours to move.',

    # ⚠️⚠️ A REGULATED CLAIM, AND IT WAS BACKWARDS. Guest money lands in the platform's Stripe balance.
    'Does Vyrobel hold my money?':
        'Guest card payments are taken through our Stripe account, so the money passes through us before it '
        'reaches you or an owner — an owner is paid by a transfer out of that balance. We are not a bank '
        'and we do not offer to hold your money as a service, but "your funds never touch us" would not be '
        'true, so we are not going to say it. We never see or store a card number: payment details are '
        'entered on Stripe’s own hosted page and we keep a reference.',

    'Can guests check in online?':
        'Yes, including ID capture where your jurisdiction requires registration — the document is stored '
        'against the stay, and nothing about it is verified for you. Door codes, wifi and directions are '
        'released a set number of days before arrival and stay available until check-out. That release is '
        'by DATE only: it does not wait for online check-in, for a balance to be settled, or for an ID. A '
        'code released at booking is a code sitting on someone’s phone for three months.',

    # ── SECURITY ──
    'Is Vyrobel GDPR compliant?':
        'We act as your processor for guest data, on your instruction, under a data processing agreement. '
        'Guest registration data is anonymised automatically after a fixed retention window — personal '
        'fields are replaced rather than rows deleted, so your accounts and occupancy history survive. That '
        'window is currently set in the product rather than by you. Erasure and export are run by you from '
        'the guest record; a guest cannot yet do either themselves.',

    'Do you offer two-factor authentication?':
        'Yes, on operator and owner logins, and each person turns it on for themselves. There is no setting '
        'that requires it across an account, so you cannot enforce it for your team from here.',

    # ── FIT ──
    'How big does a portfolio need to be?':
        'Below roughly ten units a spreadsheet and a channel manager genuinely still works. Above forty '
        'units, the thing that breaks first is not effort — it is that nobody has the same picture of the '
        'day.',
}

# ⚠️ A QUESTION WITH NO HONEST ANSWER SHOULD NOT BE ASKED. "Where is data hosted?" answered "In the EU".
#    EU hosting is a founder COMMITMENT but it is HELD OUT of this pass pending a data-residency inventory
#    (database, backups, guest ID documents, every sub-processor) — and that inventory feeds the Article 28
#    agreement too. ⚠️ NO placeholder and NO future promise goes in its place: the question is REMOVED, and
#    it comes back when the inventory can answer it.
FAQ_REMOVED = {
    'Where is data hosted?',
}

GLOSSARY_CORRECTIONS = {
    # ⚠️ `R1`, and here it is wrong for every page at once. Corrected on `guide-reports` in batch 5.
    'Occupancy': {
        'definition':
            'The share of your available nights that sold. A unit taken out of service still counts in '
            'available nights, so a maintenance closure reads as lower occupancy rather than as removed '
            'capacity.',
        'why':
            'Out of service subtracts from what you can sell, not from what occupancy is measured against '
            '— so a month that looks worse than it felt may be a closure rather than demand.',
    },
    # ⚠️ THE CHAIN DOES NOT EXIST. Batch 4 established two levels; this taught four.
    'Rate plan': {
        'definition':
            'What a night costs, held as one price per date. A base rate, a season and a weekend uplift are '
            'each written INTO that date rather than layered over it, so the last one applied is the price. '
            'A per-unit override is the one thing resolved separately, and it wins.',
        'why':
            'When a price looks wrong there are only two places to look: the override on that unit, and the '
            'price stored against that date. Re-applying a season overwrites whatever a base rate put there '
            'earlier, and re-applying a base rate overwrites the season.',
    },
    # ⚠️ Direction two — our own restriction override made the absolutism false again.
    'Minimum stay': {
        'definition':
            'The shortest booking the engine will accept on a given date, usually longer in peak season.',
        'why':
            'A five-night minimum against a four-night gap means the engine refuses those nights to anyone '
            'booking on their own. Someone on your team who can take a booking can override the refusal '
            'with a recorded reason — but the minimum keeps refusing everyone after that.',
    },
    'Orphan night': {
        'definition':
            'A gap between two bookings too short to satisfy your own minimum stay, so the booking engine '
            'will not sell it.',
        'why':
            'It can still be sold by someone on your team overriding the refusal. Left alone it earns '
            'nothing, and you did not lose margin on those nights — you lost the entire revenue.',
    },
    # ⚠️ Corrected on `guide-setup`/`guide-settings` in batch 5; it stood here unchanged.
    'Unit': {
        'definition':
            'Anything you sell independently and clean separately.',
        # ⚠️ WORDED WITHOUT ECHOING THE FALSE CLAIM. A first draft read "It is NOT what you are billed on",
        #    which a skimming reader still reads as the claim — and the gate rightly flagged the phrase as
        #    still present on the page. State what IS true instead of negating what is not.
        'why':
            'It is how housekeeping, maintenance and availability resolve. Your bill does not move with it: '
            'the subscription is one flat figure per account whatever your unit count.',
    },
    # ⚠️ NOT flagged by the audit. The block is real locally; there is no channel to reach.
    'Out of service': {
        'definition':
            'A unit deliberately taken off sale — usually for maintenance — so it cannot be '
            'booked on your storefront or at the desk.',
        'why':
            'It does not reach a marketplace listing, because channel sync is not switched on. Close those '
            'where you manage them.',
    },
    # ⚠️ Housekeeping status never touches availability; only condition_status does.
    'Guest-ready': {
        'definition':
            'A room that has been cleaned and, if you inspect, inspected — so the front desk can hand '
            'over a key.',
        'why':
            'It is what the desk reads before an arrival. It does not change what can be sold: taking a '
            'unit off sale is a separate, deliberate act.',
    },
}


# ⚠️⚠️ THE PHRASES THAT MUST NEVER REACH THE OUTPUT AGAIN — one per verified-FALSE claim.
#
# ⚠️ WHY THIS LIST EXISTS AND THE ZERO-MATCH CHECK WAS NOT ENOUGH. A mutation that stopped applying
# `FAQ_CORRECTIONS` entirely — putting every original answer back on the page — left every needle GREEN,
# because the needles prove the page RENDERED, not that it renders the CORRECTED text. That is a
# "no test covers this" finding, and this is the test.
#
# ⚠️ Each entry is a distinctive fragment of the FALSE original, chosen so no honest correction could
# contain it. ⚠️ THAT IS ALSO A DRAFTING RULE FOR THE CORRECTIONS THEMSELVES: never negate a false claim by
# quoting it ("it is NOT what you are billed on"), because a skimming reader still reads the claim — and
# the corrections gate rightly reported that phrasing as still present on the page.
FORBIDDEN = [
    'roughly \u20ac4 to \u20ac6',                        # a per-unit price band; the plans are flat
    'we bill per unit in the portfolio',              # per-unit billing
    'units are what you are billed on',               # per-unit billing
    'as CSV or JSON at any time from Settings',       # no JSON writer, no full-account export
    'Nothing on Growth and above',                    # a tier that does not exist
    'Starter customers self-serve with a CSV importer',   # a tier and an importer that do not exist
    'Starter exists for operators',                   # a tier that does not exist
    'typically live in two to three weeks',           # a migration timeline for a service that does not exist
    'imported and reconciled line by line',           # there is no importer
    'They cannot see guest names',                    # ⚠️ PRIVACY: owners DO see the name, name-only, by design
    'Airbnb and Booking.com connect directly',        # channel sync is STOPPED
    'We never hold client funds',                     # ⚠️ guest money lands in the platform Stripe balance
    'only once check-in is complete and the balance is settled',   # #33 releases on a DATE
    'the statutory period you set',                   # the retention window is fixed in code
    'Guests can export or request erasure of their own data',      # no guest self-serve route exists
    'admins can require it account-wide',             # no org-wide 2FA enforcement
    'Units out of service are excluded from the denominator',      # R1
    'An override always beats a season',              # the chain does not exist
    'cannot be sold to anyone, at any price',         # our own override made this false
    'What you are billed on',                         # per-unit billing (glossary)
]
