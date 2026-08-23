# -*- coding: utf-8 -*-
"""G5 (service commitments) and the FALSE product claims the re-triage pulled out of G6.

Each entry: the ORIGINAL verbatim, the REPLACEMENT verbatim, and WHY — so slice 1 can implement it
without inferring intent from a diff, which is the whole lesson of the scenarios port.
"""

G5 = {
    # ⚠️ THE CHROME LINE — in all SIXTEEN guides. ONE change, sixteen pages (§N.8f).
    'S1-chrome-support': {
        'original': 'Support replies within a working day, and faster during your onboarding.',
        'replacement': 'Questions about this guide? Email support@vyrobel.com.',
        'appears_on': 16,
        'why': (
            '⚠️ THE TEST IS "DOES IT PROMISE ANYTHING MEASURABLE?" — "within a working day" does, and '
            'nothing in the product measures or guarantees it. ⚠️ THE OBVIOUS REWRITE IS ALSO WRONG: '
            '"we usually reply quickly" is still a promise, merely one nobody can hold us to — worse, '
            'because it is unfalsifiable. ⚠️ AND "we answer support email ourselves" IS A CLAIM WITH A '
            'FUSE: true today, false the day a first hire starts, and nobody will remember it is here. '
            'The replacement asserts nothing at all: it gives the reader the address and stops.'
        ),
    },
    'S1-137b': {
        'original': 'We never email passwords, never ask for one in support, and no member of our team can see yours.',
        'replacement': 'We never email passwords and never ask for one in support. Passwords are stored hashed, so nobody — including us — can read yours back.',
        'appears_on': 1,
        'why': (
            '⚠️ MIXED: the first two clauses are practice commitments (keepable, and they promise nothing '
            'measurable — they describe what we do not do). The third, "no member of our team can see '
            'yours", is a PRODUCT property and is TRUE — passwords are hashed, not encrypted, so there is '
            'no readable form to see. ⚠️ The replacement keeps the practice clauses and restates the third '
            'as the mechanism rather than as a promise about staff behaviour, which is what actually makes '
            'it true. It is a candidate for kind:internal once a test names it.'
        ),
    },
}

# ⚠️⚠️ NOT SOURCING ITEMS — DOC-FIX ITEMS. These describe product behaviour that DOES NOT EXIST. The
# slice-0 pattern swept them into G6 because they carry "typically"/"usually", and the G6 ruling is
# "rewrite as our opinion". ⚠️ APPLYING THAT RULING HERE WOULD BE ACTIVELY HARMFUL: hedging a false
# statement makes it unfalsifiable instead of correcting it — "in our view the code releases only once
# the balance is settled" is worse than the original, because the original could at least be checked.
FALSE_CLAIMS = {
    'S1-040': {
        'page': 'guide-arrival',
        'original': 'Door code, wifi password, parking and directions are held until a moment you choose — typically the afternoon before arrival, and only once check-in is complete and the balance is settled.',
        'replacement': 'Door code, wifi password, parking and directions are released two days before check-in and stay available until checkout. The release is on a schedule, not on check-in or payment.',
        'evidence': 'ArrivalPack::LEAD_DAYS = 2; ArrivalPack::for() consults neither guest_checked_in_at nor the folio balance. Cluster E, confirmed again this pass.',
    },
    'S1-142': {
        'page': 'guide-arrival',
        'original': 'The front desk can override and release manually for anyone standing in front of them.',
        'replacement': '(cut — there is no manual release.)',
        'evidence': 'No override path exists. ⚠️ The operator REVEAL added in cluster E shows a member of staff the stored code; it does not release it to the guest early, so it does not make this sentence true.',
    },
    'S1-144': {
        'page': 'guide-arrival',
        'original': 'The invitation goes out on a schedule you set, usually a few days before arrival, and repeats once if it is not completed.',
        'replacement': 'The pre-arrival email goes out two days before check-in to every confirmed booking. It does not repeat, and the timing is not configurable. You can turn it off per property.',
        'evidence': 'SendPreArrivalEmails mails once, on the LEAD_DAYS schedule; there is no configurable timing and no repeat.',
    },
    'S1-179': {
        'page': 'guide-rates',
        'original': 'Vyrobel drops the minimum automatically when the remaining gap between two bookings is shorter than the minimum itself — otherwise those nights cannot be sold to anyone, at any price.',
        'replacement': '(cut — orphan-gap minimums are not implemented. A minimum stay applies as set, including across a short gap.)',
        'evidence': '⚠️ ZERO orphan-gap logic in app/ (verified this pass), and cluster D reached the same conclusion independently: "No orphan-gap logic".',
    },
    'S1-006': {
        'page': 'article-pricing',
        'original': 'A five-night minimum against a four-night gap between two bookings means those four nights cannot be sold at all, at any price, to anyone.',
        'replacement': '(keep the sentence — it is TRUE and describes the problem. ⚠️ What must go is the article\'s implication that Vyrobel solves it automatically; see S1-179.)',
        'evidence': '⚠️ This sentence is a correct statement about minimum stays in general. It is flagged only because the surrounding argument promises a mechanism that does not exist.',
    },
    'S1-015': {
        'page': 'faq',
        'original': 'A single property under 30 units is typically live in two to three weeks.',
        'replacement': '(cut — we have no onboarding history to base a timeline on.)',
        'evidence': '⚠️ Zero customers have been onboarded. "Typically" describes a population that does not exist; this is a fabricated figure, not an unsourced one.',
    },
    'S1-043': {
        'page': 'faq',
        'original': 'Fifty to two hundred units takes four to six, and most of that is waiting on channel reconnections rather than on data.',
        'replacement': '(cut — same reason as S1-015, and it additionally asserts a cause we have never observed.)',
        'evidence': '⚠️ No onboarding has happened, and channels are not connectable at all today (cluster A).',
    },
    'S1-117': {
        'page': 'faq',
        'original': 'Spreadsheet-based operations take longest, because the rate rules usually live in someone’s head rather than in a column.',
        'replacement': '(cut — same reason.)',
        'evidence': '⚠️ A comparative timeline across customer types we have never had.',
    },
}
