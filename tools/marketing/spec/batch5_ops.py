"""⚠️ BATCH 5 — OPERATIONS, REPORTING & SETTINGS. The verification record is `docs/DOC-BATCH5-VERIFICATION.md`
(every row carries its code fact and the file that establishes it). This module is the transforms only.

SUBSYSTEMS VERIFIED ONCE (§N.8f), applied across every page that claims about them:

  · ⚠️ OCCUPANCY DENOMINATOR. Operator occupancy = RN ÷ AN where AN = `Σ availability.total`
    (`PerformanceMetrics` BASIS §). `AvailabilityService:44-62` filters units by `is_active` ONLY; a unit
    whose `condition_status` is `out_of_order`/`out_of_service` is counted into `availability.out_of_order`
    and **stays in `total`**. ⚠️ So out-of-service subtracts from what can be SOLD, never from what
    occupancy is MEASURED AGAINST. A maintenance closure does depress occupancy.
    ⚠️ THIS FINDING WAS CORRECTED ON RE-READING — a first pass cited the OWNER denominator
    (`count(units) × days`) and reached the right verdict by the wrong mechanism.

  · ⚠️ WHAT A CUSTOMER IS ACTUALLY CHARGED. All four seeded `plans` rows are `pricing_type = 'flat'` with
    `unit_rate_minor` NULL — Essential and Pro, monthly and yearly. **The bill does not move with unit
    count, and there is no Starter, Growth, Scale or Enterprise tier.**
    ⚠️⚠️ A CONFIG KEY IS NOT A SHIPPED FACT: `config/billing.php` carries `per_unit_minor` and prose reading
    "billed PER active unit", used by NO plan. That key already misled one reading in this arc.

  · OPERATOR EXPORTS. Exactly two controllers exist — `OperatorReportsExportController` and
    `MaintenanceCostExportController`. ⚠️ No full-account export, and **no JSON writer anywhere**.

  · CHANNELS. Sync is STOPPED and account-gated. ⚠️ No page may imply a live push, and none may promise a
    future date for one.

  · HOUSEKEEPING STATUS vs AVAILABILITY. `HousekeepingService:17-19` — `housekeeping_status`
    (clean/dirty/inspected) does NOT affect availability; only `condition_status` does.

⚠️ ALREADY TRUE, SO CORRECTED NOTHING (confirm-first cuts both ways):
  · `guide-reports` "Reports default to everything you manage" — `OperatorReports::render()` scopes by the
    Phase-6a context, whose pivot-or-all rule (#38) means an empty `property_user` pivot IS the whole org.
  · `guide-setup` "the property's timezone decides when your day rolls over" — `properties.timezone` really
    does drive "today" (`ReservationStage:50`, `StorefrontOffer:83`, `RosterWeek:286`).
  · `guide-settings` "Adding a property does not require a new subscription or a new login" — one org, one
    subscription, many properties.
"""

# ⚠️ PRICING CLAIMS ARE HELD AT THE SECURITY-CLAIM BAR: backed by the `plans` rows, or FALSE. A customer
#    reads these BEFORE deciding what to buy and disputes them afterwards.
SETUP = {
    # ⚠️ NAMES A TIER THAT DOES NOT EXIST. There is also no importer of any kind — so both halves fail, and
    #    the honest correction is that the CSV import is the operator's own work.
    'growth-tier-import': {
        'before': 'CSV from your current system, or we pull them for you on Growth and above.',
        'after': 'CSV from your current system. There is no migration service and no import tool that '
                 'reads another system for you — the file is yours to prepare.',
    },
    # ⚠️ HALF TRUE — the unit-level resolution of housekeeping/maintenance/availability is REAL and stays.
    #    Only the billing half is false: every seeded plan is flat.
    'billed-per-unit': {
        'before': 'This matters because you are billed per unit, and because housekeeping, maintenance and '
                  'availability all resolve at unit level.',
        'after': 'This matters because housekeeping, maintenance and availability all resolve at unit '
                 'level. It does not change your bill: the subscription is one figure per account, '
                 'whatever your unit count.',
    },
}

REPORTS = {
    # ⚠️ THE CLAIM IS BACKWARDS, AND IT MATTERS MOST WHEN A FIGURE LOOKS WRONG. Out-of-service leaves the
    #    denominator untouched, so a closure reads as lost occupancy rather than as removed capacity.
    'oos-denominator': {
        'before': 'Units out of service are excluded from available nights, so a maintenance closure does '
                  'not show up as an occupancy failure. That is the correct treatment and it is worth '
                  'knowing when a figure looks better than you expected.',
        'after': 'A unit taken out of service still counts in available nights, so a maintenance closure '
                 'reads as lower occupancy rather than as removed capacity. Out of service subtracts from '
                 'what you can sell, not from what occupancy is measured against — so a month that looks '
                 'worse than it felt may be a closure rather than demand.',
    },
    # ⚠️ HALF TRUE. Property scope is real, but it comes from the global property switcher, not a control on
    #    this page; there is no unit-type and no single-unit filter here at all.
    'filters': {
        'before': 'Narrow to a property, a unit type or a single unit — the charts and the figures both '
                  'respond.',
        'after': 'Narrow to a property with the property switcher in the top bar and the charts and the '
                 'figures both respond. There is no unit-type or single-unit filter on this page; per-unit '
                 'figures live on the owner reports.',
    },
    'bar-heading': {'before': 'Click a bar to drill in', 'after': 'Read the month bars'},
    'clickable-bars': {
        'before': 'Month bars in the revenue chart are clickable and filter the table below to that month. '
                  'The peak month is highlighted so you are not hunting for it.',
        'after': 'Month bars in the revenue chart are a read-out, not a control: clicking one does nothing '
                 'and no month is highlighted for you. Change what the table shows with the period '
                 'selector above the chart.',
    },
    # ⚠️ REMOVING THIS GUTS A NUMBERED STEP, AND WE SAY SO RATHER THAN LEAVING A STUB. The goal line is real
    #    — on the OWNER's reports (`owner_monthly_target`, #56). There is no operator equivalent.
    'revenue-goal-heading': {'before': 'Set a revenue goal', 'after': 'Revenue goals are the owner\'s'},
    'revenue-goal': {
        'before': 'A goal line on the chart flags months below target, and the progress ring shows the '
                  'year against it. Editable at any time; it is a management tool, not a contract.',
        'after': 'An owner can set a monthly target and sees a goal line against it on their own reports. '
                 'There is no equivalent here: nothing on your reports flags a month as below target, and '
                 'there is no progress ring.',
    },
    # ⚠️ THE OPERATOR REPORT CARRIES ONE TAX LINE. The real VAT summary — output, input, net, computed from
    #    configured rates — is the OWNER's (#73), and its rates come from `tax_rates`, not property settings.
    'vat-summary': {
        'before': 'The tax section totals net, VAT and gross for the period at the rate configured in '
                  'Settings, split by the categories your accountant needs — accommodation, add-ons and '
                  'city tax are not always taxed alike.',
        'after': 'The tax section shows one figure: tax collected over the period, as a pass-through. It '
                 'is not split by category and it is not a VAT return. The computed VAT summary — output, '
                 'input and net — is on the owner reports, and it reads the rates configured for the '
                 'jurisdiction rather than a property setting.',
    },
    'filing-status': {
        'before': 'Filing status sits alongside it: the next return due date, whether it has been filed, '
                  'and who marked it.',
        'after': 'There is no filing status here — no due date, no filed marker and no record of who '
                 'marked it. Track that wherever you track your other returns.',
    },
    'statement-pack': {
        'before': 'The Download all statements action packages a period\'s owner statements as a single '
                  'PDF pack — useful at year end, and the thing accountants ask for first.',
        'after': 'Owner statements download one at a time. There is no action that packages a period into '
                 'a single file from here, so a year end means collecting them individually.',
    },
    # ⚠️ THE CLAIM ASSERTS THE OPPOSITE OF THE CODE. The exporter writes `MoneyFormatter::display()` and
    #    human month labels. ⚠️ The CODE is the side that is wrong here — reported as a product defect, not
    #    fixed in a doc pass — but the page must describe what the file actually contains.
    'csv-format': {
        'before': 'CSV exports use ISO dates and unformatted numbers, which is deliberate: the file is for '
                  'a spreadsheet, not for reading.',
        'after': 'CSV exports carry formatted amounts and readable month labels rather than raw values, so '
                 'a spreadsheet will treat the money columns as text until you convert them.',
    },
}

SETTINGS = {
    # ⚠️ HALF TRUE — the payout currency really is stored per owner (`owners.payout_currency`). No FX rate is
    #    captured anywhere and none appears on the remittance advice, so only that half is cut.
    'payout-currency': {
        'before': 'Owners can choose their own payout currency separately where you allow it; the '
                  'conversion happens at the bank, and the rate used appears on the remittance advice.',
        'after': 'Owners can be set to a payout currency of their own. The conversion happens at the bank, '
                 'and the rate it used is not captured here — the remittance advice does not show it.',
    },
    'vat-feeds-reports': {
        'before': 'Your rate and registration number, which appear on guest invoices and feed the VAT '
                  'summary in Reports.',
        'after': 'Your rate and registration number, which appear on guest invoices. They do not drive the '
                 'computed VAT summary on the owner reports — that reads the rates configured for the '
                 'jurisdiction.',
    },
    # ⚠️ REMOVING THIS GUTS A NUMBERED SETTING, AND WE SAY SO. Commission is real, but it lives on each
    #    agreement; there is no org-level default and no configurable base.
    'commission-defaults-heading': {'before': 'Commission defaults', 'after': 'Commission is per agreement'},
    'commission-defaults': {
        'before': 'The default management commission for new owners, and the base it applies to. '
                  'Individual agreements override it; the default only saves typing.',
        'after': 'There is no account-wide default and no setting for the base it applies to. Each '
                 'management agreement carries its own commission rate, and that rate is what every '
                 'statement uses.',
    },
    # ⚠️ PRICING. Every seeded plan is flat — the unit count does not reach the bill.
    'billed-on-units': {
        'before': 'Units are what you are billed on.',
        'after': 'Your unit count does not change what you pay: the subscription is one figure per '
                 'account.',
    },
    'notification-routing': {
        'before': 'Notification routing decides who is emailed about what — worth setting before your team '
                  'starts filtering everything to a folder.',
        'after': 'There is no routing setting: who is emailed about what follows each person\'s own '
                 'notification preferences, not a rule you set for the team.',
    },
    'data-export': {
        'before': 'Data export produces a full copy of your reservations, guests, units, rates and '
                  'statements as CSV or JSON, on demand, without a support request.',
        'after': 'Exports are per screen and CSV only — reports and maintenance costs each download their '
                 'own file. There is no single action that produces a full copy of the account, and no '
                 'JSON export.',
    },
}

HOUSEKEEPING = {
    'inspection-toggle': {
        'before': 'If you do not inspect rooms, turn inspection off in Settings and Clean becomes the '
                  'final state.',
        'after': 'If you do not inspect rooms, simply stop at Clean — there is no setting that removes the '
                 'Inspected state from the board.',
    },
    # ⚠️ HALF TRUE — it really does balance, by COUNT. There is no cleaning-duration field anywhere, so the
    #    "expected cleaning time" half is cut and the fairness claim stays.
    'assign-all': {
        'before': 'Press Assign all on a lane and the system spreads rooms across the team on shift by '
                  'count and expected cleaning time.',
        'after': 'Press Assign all on a lane and the system spreads rooms across the team on shift by '
                 'count, giving each next room to whoever currently holds the fewest. It does not weigh '
                 'how long a room takes — nothing records that.',
    },
    'auto-reorder': {
        'before': 'When a guest is in reception for a room that is not ready, that room moves to the top '
                  'of the board and stays there until it clears. This is the only automatic reordering, '
                  'and it is the one that matters.',
        'after': 'When a guest is in reception for a room that is not ready, move that room to the top of '
                 'the lane yourself. The board does not reorder itself for a waiting guest.',
    },
    # ⚠️ HALF TRUE — the status really does reach the desk in the same transaction. But "releases" overstates:
    #    housekeeping status does not touch availability; only `condition_status` does.
    'inspected-releases': {
        'before': 'Marking a room inspected releases it to the front desk in the same second. Reception '
                  'does not need to be told, and does not need to phone the floor to ask.',
        'after': 'Marking a room inspected shows it as guest-ready to the front desk in the same second. '
                 'Reception does not need to be told, and does not need to phone the floor to ask. It is '
                 'what the desk reads before handing over a key; it does not change what can be sold.',
    },
    'ticket-from-card': {
        'before': 'Raise a maintenance ticket from the room card rather than telling someone.',
        'after': 'Raise a maintenance ticket from the maintenance screen rather than telling someone — the '
                 'housekeeping board has no ticket action on the room card.',
    },
    # ⚠️ HALF TRUE — the block is real and immediate LOCALLY. ⚠️ There is no live channel push, and no future
    #    date is promised for one.
    'block-channels': {
        'before': 'That block stops the unit selling everywhere immediately, including on connected '
                  'channels.',
        'after': 'That block stops the unit selling on your own storefront and at the desk immediately. It '
                 'does not reach a marketplace listing — close those where you manage them.',
    },
}

STOREFRONT = {
    'custom-domain-heading': {'before': 'Point a domain at it', 'after': 'It lives on a Vyrobel address'},
    # ⚠️ TWO SWAPS, NOT ONE: the master splits this across a <p> and a sibling <div>, so a single needle
    #    spanning both matched nothing — the zero-match check caught it. The NEEDLE was wrong, not the check.
    'custom-domain': {
        'before': 'Your own domain, not a subdomain of ours. Guests pattern-match on the address bar, and '
                  'a booking page on a vendor domain converts worse.',
        'after': 'The storefront is served on a Vyrobel address, not a domain of your own. Guests do '
                 'pattern-match on the address bar, so link to it from your own site rather than treating '
                 'it as your homepage.',
    },
    # ⚠️⚠️ THIS SENTENCE WAS ALREADY CORRECTED ONCE, UNDER A FALSE PREMISE. `g6_guides.py:97-98` fixed its
    #    propagation claim ("usually within an hour" → propagation is the registrar's to time) and left
    #    standing the premise that a custom domain exists to point. It does not. ⚠️ So this needles the G6
    #    OUTPUT, not the master text — a deliberate chain, the way batch 2 runs after slice 2.
    'custom-domain-dns': {
        'before': 'We provide the DNS records; your registrar applies them. How long propagation takes is '
                  'set by them, and we do not predict it.',
        'after': 'There are no DNS records to add and nothing to set up with your registrar.',
    },
    # ⚠️ THE CARD FORM IS REFUSED BY DESIGN, NOT MISSING BY ACCIDENT: card details never touch our servers
    #    (hosted checkout, SAQ A, #45). And a "test with a real card" instruction cannot stand where the
    #    live-key flip has not happened.
    'connect-payments-heading': {'before': 'Connect payments', 'after': 'How payment is taken'},
    'connect-payments': {
        'before': 'A recognised card form on your domain. Test with a real card and refund yourself; a '
                  'payment path nobody has walked is not a payment path.',
        'after': 'Card details are entered on the payment provider\'s own hosted page rather than a form '
                 'on your site, so no card number ever reaches us. Walk the path yourself once it is live: '
                 'a payment path nobody has walked is not a payment path.',
    },
    'book-it-yourself': {
        'before': 'Book on it yourself, on a phone, with a real card, as though you had never seen the '
                  'property.',
        'after': 'Book on it yourself, on a phone, as though you had never seen the property.',
    },
    'keep-channels': {
        'before': 'Keep the channels running. The storefront is for the guests who already found you; the '
                  'marketplaces are still finding the ones who have not.',
        'after': 'Keep your marketplace listings going, managed where they are today. The storefront is '
                 'for the guests who already found you; the marketplaces are still finding the ones who '
                 'have not.',
    },
}

DOCS = {
    'channels-card': {
        'before': 'Mapping units to listings, what syncs each way, and reading connection health.',
        'after': 'What a channel connection would cover, and why sync is not switched on.',
    },
    'inviting-team-card': {
        'before': 'Which role to give a housekeeper, a receptionist and a bookkeeper.',
        'after': 'Roles and scoping are covered under Team, security and settings.',
    },
    # ⚠️ HALF TRUE — sessions and connected apps are real (OAuth, #72) and 2FA is real per user. There is no
    #    org-wide enforcement: no policy column, no middleware, no command.
    '2fa-enforcement-card': {
        'before': 'Enforcing 2FA for the team, revoking a session, and reviewing connected apps.',
        'after': 'Turning on 2FA for your own login, revoking a session, and reviewing connected apps.',
    },
    'currency-tenant-card': {
        'before': 'Set once per tenant; every figure in the product follows. Changing it later, safely.',
        'after': 'Set per property, not once for the account — every figure follows the property it '
                 'belongs to. Changing it later, safely.',
    },
    'importer-card': {
        'before': 'The export format we accept, and how the reconciliation sheet works.',
        'after': 'Preparing your own CSV — there is no importer that reads another system for you.',
    },
}

RESOURCES = {
    'newsletter': {
        'before': 'One email a month, no drip sequence',
        'after': 'No mailing list yet',
    },
    'newsletter-body': {
        'before': 'New guides and the occasional product note. Unsubscribe link at the top, where it '
                  'belongs.',
        'after': 'There is no list to join and nothing to unsubscribe from. New guides appear here.',
    },
}

# ⚠️ DIRECTION TWO — A CLAIM OUR OWN WORK INVALIDATED, TWICE. Enforcing `min_stay` (`RestrictionGuard`) made
#    this TRUE; granting `reservations.restriction_override` made it FALSE again. The override is keyed,
#    audited, and held by everyone who can take a booking — so the nights CAN be sold, deliberately.
ARTICLE_PRICING = {
    'orphan-absolutism': {
        'before': 'A five-night minimum against a four-night gap between two bookings means those four '
                  'nights cannot be sold at all, at any price, to anyone.',
        'after': 'A five-night minimum against a four-night gap between two bookings means the booking '
                 'engine refuses those four nights, at any price, to anyone arriving on its own. Anyone on '
                 'your team who can take a booking can override the refusal with a recorded reason — but '
                 'the minimum keeps refusing every booking after that one, so the gap stays shut unless '
                 'somebody notices it.',
    },
}
