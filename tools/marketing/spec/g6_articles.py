# -*- coding: utf-8 -*-
"""G6 · THE ARTICLES — 45 opinion claims across 7 articles, /resources and /faq. Density 15–34%.

⚠️⚠️ WHY THIS FILE IS SHAPED DIFFERENTLY FROM g6_guides.py, AND WHY THAT IS THE FINDING.

A guide is instructional: its argument is "here is how to do X in Vyrobel", and a stray generalisation
is decoration. Reframing it costs nothing, which is why all 28 guide claims have verbatim replacements.

⚠️ AN ARTICLE IS A PERSUASIVE ESSAY WHOSE PREMISE *IS* THE GENERALISATION. "Most operators answer no to
at least two" is not decoration on the out-of-service article — it is the reason the reader should keep
reading. Reframe every such sentence to "in our view…" and you do not get a more honest article; you get
the same article with the confidence removed, which reads as evasion and persuades nobody.

⚠️ SO THE HONEST OUTPUT HERE IS A RECOMMENDATION PER PAGE, NOT 45 HOLLOWED SENTENCES. Producing the
sentences would be doing the wrong thing carefully. Each page below carries: the measured density, what
the page's argument actually rests on, a WORKED REFRAME showing what the ruling produces when applied,
and a recommendation — ⚠️ all of it for the founder's REVIEW, none of it settled.

⚠️ MEASURED, NOT ASSERTED: density is G6 claims ÷ prose blocks on the page.
"""

ARTICLES = {
 'article-housekeeping': {
   'claims': 12, 'density': '34%',
   'rests_on': 'That the reader’s team currently has no shared picture of the day. Every section is a symptom of that premise, and the premise is an unevidenced claim about how small operations run.',
   'worked_reframe': (
     'ORIGINAL: "Almost every failure above is the same failure: the state of a room is known to one '
     'person and not to the others."\n'
     'REFRAMED: "We built the board around one idea: that these failures share a cause, which is that '
     'the state of a room is known to one person and not the others."\n'
     '⚠️ The reframe is honest and it survives — but it now describes OUR design reasoning, and the '
     'article was written to describe the READER\'S problem. Twelve of those in a 35-block page and the '
     'piece is about us.'),
   'recommendation': (
     '⚠️ RE-ARGUE OR DO NOT PUBLISH. This is the highest-density page of the twenty-seven. Our '
     'suggestion, for the founder to accept or reject: rewrite it as "how we built housekeeping and '
     'why", which is fully ours to assert, rather than as "the problems you have", which is not.'),
 },
 'article-oos': {
   'claims': 5, 'density': '19%',
   'rests_on': 'A diagnostic opening — "Most operators answer no to at least two" — that tells the reader they have a problem they have not noticed.',
   'worked_reframe': (
     'ORIGINAL: "Most operators answer no to at least two."\n'
     'REFRAMED: "If you answer no to any of these, the rest of this page is written for you."\n'
     '⚠️ This one reframes WELL: it converts a population claim into a conditional addressed to the '
     'reader, asserting nothing about anybody else. ⚠️ Note it is not a hedge — "in our view most '
     'operators answer no" would still be an unsourced claim about operators.'),
   'recommendation': 'PUBLISHABLE after reframing. The conditional-addressed-to-the-reader move works here and is worth reusing elsewhere.',
 },
 'article-pricing': {
   'claims': 8, 'density': '25%',
   'rests_on': 'Assertions about how markets behave ("Friday and Saturday sell themselves in most leisure markets", "in city markets it often behaves like a weekend night") that are the substance of the advice.',
   'worked_reframe': (
     'ORIGINAL: "Most properties price two seasons: high and low, with a hard edge between them."\n'
     'REFRAMED: "We designed seasons so the edge between them can be soft, because a hard edge is the '
     'pattern we most often had to work around."\n'
     '⚠️ Survives, but the article is revenue-management ADVICE, and advice reframed as "why we built '
     'it this way" stops being advice.'),
   'recommendation': (
     '⚠️ THE HARDEST CASE. The page gives pricing advice we cannot source and have not tested against a '
     'portfolio. Options: (a) cut to a mechanics explainer — what the levers ARE and how they interact, '
     'with no claim about what works; (b) publish as explicitly our opinion and accept the hedge '
     'density; (c) do not publish. ⚠️ We would take (a).'),
 },
 'article-direct': {
   'claims': 7, 'density': '20%',
   'rests_on': 'Predicted outcomes of a strategy — "Operators who do only this typically see direct share move several points in a season" — which are the article\'s payoff.',
   'worked_reframe': (
     'ORIGINAL: "Operators who do only this typically see direct share move several points in a season."\n'
     'REFRAMED: "(cut.)"\n'
     '⚠️ THIS ONE CANNOT BE REFRAMED. It predicts a result for the reader. "In our view operators '
     'typically see…" is the same prediction with a hedge, and we have no operators to have seen it in. '
     '⚠️ The only honest versions are to cut it or to state the mechanism without the outcome.'),
   'recommendation': (
     '⚠️ CUT THE OUTCOME CLAIMS, KEEP THE MECHANICS. The article can honestly explain what direct '
     'booking costs and what it requires; it cannot promise what it returns. ⚠️ It also carries B8\'s '
     '"fifteen to eighteen percent" (S1-041) — a G2 claim awaiting the founder\'s sourcing, so this page '
     'is blocked on that regardless of G6.'),
 },
 'article-owners': {
   'claims': 8, 'density': '22%',
   'rests_on': 'Claims about what owners want and how they behave ("Most owners want their own weeks in the house, and many are embarrassed to ask").',
   'worked_reframe': (
     'ORIGINAL: "In most markets the next property comes from the last owner rather than from anything '
     'you advertised, and it comes because their statement made sense."\n'
     'REFRAMED: "We built the owner portal on the belief that the next property comes from the last '
     'owner, and that a statement they can follow is what earns it."\n'
     '⚠️ Works, and is arguably STRONGER — it is a conviction we can defend rather than a statistic we '
     'cannot.'),
   'recommendation': 'PUBLISHABLE after reframing. This page converts better than the others because its claims are about why we built what we built.',
 },
 'article-statement': {
   'claims': 6, 'density': '21%',
   'rests_on': 'Claims about owner behaviour ("Ask any manager which owners phone the most and you get the same answer").',
   'worked_reframe': (
     'ORIGINAL: "Ask any manager which owners phone the most and you get the same answer: the ones whose '
     'statement is a single figure."\n'
     'REFRAMED: "We think the owners who phone most are the ones whose statement is a single figure — '
     'which is why ours itemises."\n'
     '⚠️ Honest and it keeps the argument, because the point was always about our design choice.'),
   'recommendation': 'PUBLISHABLE after reframing.',
 },
 'article-trust': {
   'claims': 6, 'density': '15%',
   'rests_on': 'Claims about guest psychology at the payment step ("It is usually one missing sentence", "This is the single largest gap and the one most operators ignore").',
   'worked_reframe': (
     'ORIGINAL: "This is the single largest gap and the one most operators ignore."\n'
     'REFRAMED: "This is the gap we would close first."\n'
     '⚠️ Loses the "most operators ignore it" sting — which is exactly the part we cannot support.'),
   'recommendation': 'PUBLISHABLE after reframing; lowest density of the seven. ⚠️ S1-003 ("the most common reason a direct attempt is abandoned") must be CUT, not reframed — it is a measured-cause claim we have never measured.',
 },
 'resources': {
   'claims': 5, 'density': '22%',
   'rests_on': 'Card blurbs summarising the articles — so their claims are INHERITED from the articles they advertise.',
   'worked_reframe': '⚠️ Do not reframe these independently: they must be rewritten to match whatever each article becomes, or they will advertise a claim the article no longer makes.',
   'recommendation': '⚠️ RESOLVE LAST, AFTER the articles. Its density is an artefact of being an index.',
 },
 'faq': {
   'claims': 4, 'density': '67% — ⚠️ ARTEFACT, see note',
   'rests_on': 'Onboarding-time predictions (S1-015/043/117), which the re-triage moved to FALSE — we have onboarded nobody.',
   'worked_reframe': '⚠️ Not applicable: three of the four are fabricated figures (see g5_g_false.py), not opinions.',
   'recommendation': (
     '⚠️ THE 67% IS NOT REAL. /faq has only six STATIC prose blocks — its twelve answers live in JSON-LD, '
     'so the denominator is wrong. Do not read this page as the most hedge-dense of the set.'),
 },
}


# ══ THE TWO SETTLED ARTICLE RULINGS, AS CONCRETE SENTENCE LISTS ══════════════════════════════════════
#
# ⚠️ The rulings named the CLASS to remove ("the predicted outcomes", "every claim about what works").
# Slice 1 records exactly WHICH sentences that is, so the next reader never has to re-derive the list from
# the ruling — the same reason this spec exists at all.
#
# ⚠️ A CUT IS A REPLACEMENT WITH '', and each is registered through the zero-match check like any other
# transform: if a sentence stops matching, the build fails loudly rather than quietly cutting nothing.

# `article-direct` → CUT THE PREDICTED OUTCOMES, KEEP THE MECHANICS.
# ⚠️ A predicted outcome cannot be hedged into truth: "in our view operators typically see…" is the same
# prediction, no better evidenced and now unfalsifiable as well. We have no operators to have seen it in.
DIRECT_CUTS = {
 'S1-109': 'Operators who do only this typically see direct share move several points in a season.',
 'S1-110': ('Operators who stay with it for a full season generally find the line crosses somewhere they '
            'didn’t expect, and the commission they stopped paying turns out to have been a salary.'),
 'S1-020': 'Almost nobody who tries it in one move is happy six months later.',
}

# `article-pricing` → A MECHANICS EXPLAINER. Keep what the levers ARE and how they resolve; drop every
# claim about what WORKS — that is revenue-management advice we have never tested against a portfolio.
PRICING_CUTS = {
 'S1-045': 'Friday and Saturday sell themselves in most leisure markets.',
 'S1-076': 'In city markets it often behaves like a weekend night; in rural leisure ones it does not.',
 'S1-098': 'Most properties price two seasons: high and low, with a hard edge between them.',
 'S1-099': 'Most small operators are leaving money on two of them and losing money on the third.',
 'S1-139': 'That edge is usually in the wrong place and always too abrupt.',
 'S1-064': ('Get those right and you have captured most of what a revenue system would have found, '
            'without the subscription or the daily attention.'),
}

# ⚠️ G4's SCOPE GAP — the sentence was WIDER than its evidence.
# It claimed owners cannot see guest NAMES, but name-only is the perimeter BY DESIGN, and the internal
# test sources the CONTACT-DETAILS half only. ⚠️ NARROW THE SENTENCE, never the test.
SCOPE_NARROWING = {
 'S1-164': (
   'They do not see guest names, contact details or documents; they do not see other owners’ units, '
   'statements or terms; and they do not see your commercial arrangements with anyone else.',
   'They see a guest’s name where it identifies a booking, but never contact details or documents; '
   'they do not see other owners’ units, statements or terms; and they do not see your commercial '
   'arrangements with anyone else.'),
 'S1-163': (
   'They do not see guest contact details, guest documents, other owners’ data, or your commercial '
   'terms with anyone else.',
   'They do not see guest contact details, guest documents, other owners’ data, or your commercial '
   'terms with anyone else — a guest’s name appears only where it identifies a booking.'),
 'S1-162': (
   'They cannot see guest names, contact details or documents, other owners’ units or terms, or your '
   'commercial arrangements with anyone else.',
   'They cannot see guest contact details or documents, other owners’ units or terms, or your '
   'commercial arrangements with anyone else. A guest’s name appears only where it identifies a booking.'),
}

# ⚠️ S1-042 — the narrowed sentence the founder ruled, applied to the page.
LOGIN_NARROWING = {
 'S1-042': ('Everyone who touches Vyrobel gets their own login.',
            'Vyrobel issues a separate account for each person you invite, and stamps the acting account '
            'on every action it audits.'),
}


# ══ ⚠️⚠️ `article-housekeeping` — THE COST OF THE RE-ARGUE, MEASURED (slice 1) ════════════════════════
#
# ⚠️ THE BRANCH IS STILL UNPICKED, AND SLICE 1 DID NOT PICK IT. No transform for this page exists in
# convert.py — deliberately. What slice 1 owed the decision was a COST, not a choice:
#
#     prose blocks on the page ................................ 36
#     G6 opinion claims (need new authored argument) .......... 12   (33% of the prose)
#     blocks carrying no ledger claim (survive untouched) ..... 24
#     G1/G2/G3 claims awaiting the founder's sourcing ......... 0
#
# ⚠️ SO: a re-argue is TWELVE new sentences, not a rewrite of the page — two thirds of it survives
#    untouched. That is a smaller job than "34% hedge density" made it sound.
#
# ⚠️ AND A FACT THAT MATTERS TO THE CHOICE: this page has ZERO claims in the founder's sourcing set, so
#    unlike `article-direct` (blocked by S1-041's unsourced 15–18%) it is not waiting on anything else.
#    ⚠️ Whichever branch is chosen, it can be acted on immediately — which makes "don't publish" a real
#    saving of twelve authored sentences rather than a deferral of work that was blocked anyway.
HOUSEKEEPING_REARGUE_COST = {
    'prose_blocks': 36, 'needs_new_argument': 12, 'survives_untouched': 24,
    'blocked_by_sourcing': 0,
}


# ══ ⚠️ `/resources` CARD BLURBS — EACH RESOLVES WITH ITS OWN ARTICLE (founder ruling) ════════════════
#
# ⚠️ /resources is an INDEX. Its six card blurbs are `<p>` prose that advertises the articles beneath
# them, so each inherits its claims from the article it points at. ⚠️ Rewriting a blurb independently
# would leave the index advertising a claim its article no longer makes — the sitewide-chrome failure in
# a new place.
#
# ⚠️ ALL SIX ARE FILED `publish` AND CONSISTENTLY SO. S1-182 was the one exception: it had been filed
# `remove` as "a card blurb", which made an unsourced population claim stop blocking its page. It is
# restored, and the set now agrees with itself.
#
# ⚠️ /resources STAYS BLOCKED UNTIL ALL SIX RESOLVE. That is the correct state, not a problem to solve.
RESOURCES_BLURBS = {
 'S1-017': 'article-housekeeping',
 'S1-081': 'article-direct',
 'S1-111': 'article-oos',
 'S1-165': 'article-trust',
 'S1-172': 'article-owners',
 'S1-182': 'article-statement',   # ⚠️ the founder's ruling: resolves with the OWNER-STATEMENT rewrite (slice 4)
}
