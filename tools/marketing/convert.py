"""Design template → Blade. Faithful: keeps the design's own markup and inline styles;
transforms only what MUST change (dc-runtime wiring, palette hex, links, slots, imports)."""
import re, io, json, sys, os

# ⚠️ PORTABILITY ONLY (2026-08-23). This was an absolute path into a garbage-collected /private/tmp
# scratchpad, which made the committed toolchain readable but NOT RUNNABLE from a fresh checkout. Derived
# from __file__ so it follows the repo; override with VY_MARKETING_CORE if you keep `core/` elsewhere.
# ⚠️ `core/` is DERIVED and gitignored — regenerate it with decode.js from docs/design/front/*.dc.html.
_HERE = os.path.dirname(os.path.abspath(__file__))
SRC = os.environ.get("VY_MARKETING_CORE", os.path.join(_HERE, "core"))

# ── the palette map: the design's literals → the app's canonical tokens (D6/E4/§N.8f) ────────────
HEX = {
    '#2B2320': 'var(--vy-ink)',
    '#C2410C': 'var(--vy-accent)',
    '#4A403A': 'var(--vy-muted)',        # D6: the design's tone NOT adopted
    '#A8380B': 'var(--vy-accent-text)',  # D6
    '#B4400F': 'var(--vy-accent-text)',  # D6
    '#FFF7F0': '#fff',                   # the app's .vy-brand-mark precedent
    '#E8703A': 'var(--vy-accent-3)',
    '#D9662E': 'var(--vy-accent-2)',
    '#F0A57A': 'var(--vy-peach)',
    '#3A2E27': 'var(--vy-dark)',
    '#6F6259': 'var(--vy-faint)',
    # ⚠️ THE PRE-REMEDIATION PROTOTYPE GREYS. All four fail AA and were REMOVED from this codebase in the
    # accessibility pass (DECISIONS 2026-08-11: "→ the token values #5F5853 / #6C645F"). A certified guard,
    # RoleCoverageContrastTest, sweeps every view for two of them — and it CAUGHT the first generated
    # marketing page, which is exactly what a repo-wide guard is for. Mapped to the ratified tokens.
    '#9C9089': 'var(--vy-faint)',
    '#B7ADA4': 'var(--vy-faint)',
    '#8A8079': 'var(--vy-muted)',
    '#A69A92': 'var(--vy-faint)',
}
# The design's page→our key map (slugs from the design's OWN sitemap).
PAGE_KEY = {
    'Vyrobel Home': 'home', 'Vyrobel Product': 'product', 'Vyrobel Features': 'features',
    'Vyrobel Modules': 'modules', 'Vyrobel How It Works': 'how-it-works', 'Vyrobel Pricing': 'pricing',
    'Vyrobel Try Booking': 'try-booking', 'Vyrobel Switching': 'switching',
    'Vyrobel Alternatives': 'alternatives', 'Vyrobel Compare': 'compare',
    'Vyrobel For Hotels': 'for-hotels', 'Vyrobel For Villas': 'for-villas',
    'Vyrobel For Apartments': 'for-apartments', 'Vyrobel Case Studies': 'case-studies',
    'Vyrobel Resources': 'resources', 'Vyrobel Docs': 'docs', 'Vyrobel FAQ': 'faq',
    'Vyrobel Glossary': 'glossary', 'Vyrobel Integrations': 'integrations',
    'Vyrobel Changelog': 'changelog', 'Vyrobel Security': 'security', 'Vyrobel About': 'about',
    'Vyrobel Careers': 'careers', 'Vyrobel Contact': 'contact', 'Vyrobel Privacy': 'privacy',
    'Vyrobel Terms': 'terms',
    'Vyrobel Guide Setup': 'guide-setup', 'Vyrobel Guide Reservation': 'guide-reservation',
    'Vyrobel Guide Checkin': 'guide-checkin', 'Vyrobel Guide Arrival': 'guide-arrival',
    'Vyrobel Guide Housekeeping': 'guide-housekeeping', 'Vyrobel Guide Rates': 'guide-rates',
    'Vyrobel Guide Statements': 'guide-statements', 'Vyrobel Guide Payouts': 'guide-payouts',
    'Vyrobel Guide Channels': 'guide-channels', 'Vyrobel Guide Refunds': 'guide-refunds',
    'Vyrobel Story Cyclades': 'story-cyclades', 'Vyrobel Story Lisbon': 'story-lisbon',
    'Vyrobel Story Rotterdam': 'story-rotterdam',

    # ⚠️ THE THIRTEEN MASTERS THE REGISTRY DID NOT KNOW. The doc series has 27 masters on disk; 14 were
    # registered and these 13 were not, so `convert()` could not even NAME them. ⚠️ Registering a master
    # does not publish anything — it makes the page convertible, which is the precondition for the spec's
    # transforms below being provable at all.
    # ⚠️ `Vyrobel Guide Security` maps to `guide-security-doc`, NOT `guide-security`: the key `security`
    # already belongs to the COMPANY security page (a built, published Gate-7 page), and a collision there
    # would have silently made one page overwrite the other's conversion.
    'Vyrobel Guide Access': 'guide-access', 'Vyrobel Guide GDPR': 'guide-gdpr',
    'Vyrobel Guide Reports': 'guide-reports', 'Vyrobel Guide Security': 'guide-security-doc',
    'Vyrobel Guide Settings': 'guide-settings', 'Vyrobel Guide Storefront': 'guide-storefront',
    'Vyrobel Article Direct': 'article-direct', 'Vyrobel Article Housekeeping': 'article-housekeeping',
    'Vyrobel Article OOS': 'article-oos', 'Vyrobel Article Owners': 'article-owners',
    'Vyrobel Article Pricing': 'article-pricing', 'Vyrobel Article Statement': 'article-statement',
    'Vyrobel Article Trust': 'article-trust',
}
AUTH = {'Vyrobel Login': "login", 'Vyrobel Signup': "signup", 'Vyrobel Reset Password': "password.request"}


# ══ ⚠️⚠️ THE HONESTY-NEEDLE MATCHER, AND WHY IT REPORTS ═══════════════════════════════════════════════
#
# ⚠️ EVERY HONESTY TRANSFORM IN THIS FILE IS A `str.replace`, AND A `str.replace` THAT MATCHES NOTHING IS
# INDISTINGUISHABLE FROM ONE THAT WORKED. That is not hypothetical — it has now happened three times in this
# project, each time silently:
#   1. The B12 price needle spelled out `var(--vy-muted)` while the markup, at that point in the pipeline,
#      still said `#4A403A`. It matched nothing and looked correct.
#   2. Gate 6's DPA removal, written explicitly so "a page built later would otherwise inherit the sentence",
#      scored ZERO against all six DPA mentions on the three Gate 7 pages it was meant to protect.
#   3. Gate 7's own first draft of the Security and About needles was transcribed from a TRUNCATED text dump,
#      so "Different scale, same three complaints" was written as "Different scales, the same three
#      complaints" — and the design's apostrophes turned out to be a MIX of U+2019 and plain ASCII, so half
#      the needles missed on a character nobody can see.
#
# ⚠️ SO THE MATCHER DOES TWO THINGS. It normalises the punctuation the design mixes (curly/straight quotes,
# em-dashes, and their HTML entity spellings) so a needle cannot fail on an invisible character; and it
# RECORDS every needle, so `convert.py` can refuse to finish when one of them never fired. ⚠️ A silent no-op
# in an honesty transform means a false claim ships while the pipeline reports success.



# ══ ⚠️ THE DOC-CLAIM SPEC, IMPORTED — one definition of every replacement (§N.8f) ════════════════════
# The spec is the RECORD; this module is the MECHANISM. Copying the text in here would create a second
# copy free to drift from the one the founder ruled on.
sys.path.insert(0, os.path.join(_HERE, 'spec'))
from g6_guides import GUIDES as _G6_GUIDES                      # noqa: E402
from g5_g_false import G5 as _G5, FALSE_CLAIMS as _FALSE        # noqa: E402
from g6_articles import (                                        # noqa: E402
    DIRECT_CUTS as _DIRECT_CUTS, PRICING_CUTS as _PRICING_CUTS,
    SCOPE_NARROWING as _SCOPE, LOGIN_NARROWING as _LOGIN,
)
from g6_housekeeping import (                                    # noqa: E402
    BEHAVIOUR as _HK_BEHAVIOUR, RATIONALE as _HK_RATIONALE,
    SPLIT_044 as _SPLIT_044, CUT_171 as _CUT_171,
)
from slice2_security_gdpr import SECURITY as _SEC, GDPR as _GDPR   # noqa: E402
from batch4_rates import RATES as _B4_RATES   # noqa: E402
from batch3_booking_spine import (RESERVATION as _B3_RES, CHECKIN as _B3_CHK,   # noqa: E402
                                  ARRIVAL as _B3_ARR)                        # noqa: E402
from batch2_access_privacy import (ACCESS as _B2_ACC, GDPR as _B2_GDPR)  # noqa: E402
from faq_glossary import (FAQ_CORRECTIONS as _FG_FAQ,     # noqa: E402
                          FORBIDDEN as _FG_BAD,          # noqa: E402
                          FORBIDDEN_BY_PAGE as _FG_BAD_PAGE,  # noqa: E402
                          FAQ_REMOVED as _FG_DROP,       # noqa: E402
                          GLOSSARY_CORRECTIONS as _FG_GLOS)  # noqa: E402
from batch5_ops import (SETUP as _B5_SETUP, REPORTS as _B5_REP,          # noqa: E402
                        SETTINGS as _B5_SET, HOUSEKEEPING as _B5_HK,     # noqa: E402
                        STOREFRONT as _B5_STORE, DOCS as _B5_DOCS,       # noqa: E402
                        RESOURCES as _B5_RES,                            # noqa: E402
                        ARTICLE_PRICING as _B5_ARTP)                     # noqa: E402
from batch1_money import (PAYOUTS as _B1_PAY, STATEMENTS as _B1_STMT,   # noqa: E402
                          REFUNDS as _B1_REF)                           # noqa: E402
from six_uncorrected import (SECURITY_SIX as _SIX_SEC,      # noqa: E402
                             HOUSEKEEPING_SIX as _SIX_HK)   # noqa: E402
from batch6_articles_truth import (TRUST_TRUTH as _B6_TRUST,        # noqa: E402
                                   OWNERS_TRUTH as _B6_OWNERS,      # noqa: E402
                                   STATEMENT_TRUTH as _B6_STMT,     # noqa: E402
                                   DIRECT_TRUTH as _B6_DIRECT,      # noqa: E402
                                   OOS_TRUTH as _B6_OOS)            # noqa: E402
from last_nine import (SETUP_NINE as _N9_SETUP, ARRIVAL_NINE as _N9_ARR,   # noqa: E402
                       REPORTS_NINE as _N9_REP, PRICING_NINE as _N9_PRI)   # noqa: E402

# ⚠️ THREE `FALSE` CORRECTIONS ARE NOT REGISTERED, AND THAT IS A FINDING, NOT AN OMISSION. S1-015/043/117
# are /faq's onboarding timelines, and /faq's answers live in a `<script type="application/ld+json">`
# block that the converter strips wholesale (Gate 7's rule: the design's JSON-LD is ours to regenerate,
# not to reproduce). ⚠️ The claims therefore never reach the converted output, so a transform for them
# would match nothing and trip the zero-match check — which would be a needle chasing text that is not
# there, not a guard to weaken. The ledger rows stay accurate about the MASTER.
_DOC_SPEC_FAQ_ONLY = {'S1-015', 'S1-043', 'S1-117'}

# ⚠️⚠️ THE BLIND SPOT, RE-MEASURED AT SOURCE (2026-08-25). ⚠️ THE EARLIER NOTE HERE WAS STALE IN A WAY
# THAT TOLD A FUTURE READER NOT TO LOOK — it said /faq's answers "live in a JSON-LD block", which is only a
# 12-question SUBSET, and it did not say what the real source was. Corrected:
#   /faq      the 25 visible questions come from `static QS` inside a `<script type="text/x-dc">` DCLogic
#             block. A SECOND, hand-maintained `application/ld+json` block carries 12 of them. All 12 are
#             byte-identical to their QS twins TODAY — but 13 questions are missing from it, so the copies
#             have already diverged in COVERAGE, silently and invisibly on the page.
#             ⚠️ The counter reads "18 questions": wrong against the source (25), wrong against the JSON-LD
#             (12), and wrong against what renders (0).
#   /glossary the 35 terms come from `static T` in the same kind of DCLogic block. No JSON-LD. Its typed
#             "35 terms" is ACCURATE today — and must still be derived, because a typed count beside a
#             moving list is the "sixteen written guides" shape that shipped false sitewide.
# ⚠️ THE CONVERTER STRIPS EVERY <script>, so neither page's content reaches the output and BOTH read as
# "0 still present" to the corrections gate — indistinguishable from corrected, and in fact NEVER LOOKED AT.
# ⚠️ ALL 60 ITEMS ARE NOW VERIFIED AT SOURCE — see `docs/FAQ-GLOSSARY-SOURCE-VERIFICATION.md`. The content
# is NOT restored yet, deliberately: three answers are still unverified (flow of funds, guest self-service
# erasure, failed-payout retry) and they are money/GDPR claims. ⚠️ RESTORE ONLY AFTER CORRECTING AT SOURCE
# — switching the render on first would publish known-false answers, and a page that is briefly wrong is
# how a wrong sentence gets copied somewhere else.
_DOC_SPEC_FAQ_ONLY = {'S1-015', 'S1-043', 'S1-117'}

_DOC_SPEC_PAGES = (
    {p for p, _, _ in _G6_GUIDES.values()}
    | {d['page'] for d in _FALSE.values()}
    | {'guide-security-doc', 'guide-access', 'guide-statements', 'faq'}
    | {'guide-'+x for x in ('setup', 'rates', 'gdpr', 'arrival', 'channels', 'housekeeping', 'payouts',
                            'refunds', 'reports', 'reservation', 'settings', 'storefront', 'checkin')}
)


_NEEDLE_LOG = []


def _variants(x):
    """The same sentence as the design might have spelled it. Order matters: literal first."""
    seen, out = set(), []
    for v in (x,
              x.replace('\u2019', "'").replace('\u2018', "'"),
              x.replace("'", '\u2019'),
              x.replace('\u2014', '&mdash;').replace('\u2019', '&rsquo;'),
              x.replace('\u2014', '&mdash;'),
              x.replace('\u2019', '&rsquo;'),
              x.replace('&mdash;', '\u2014').replace('&rsquo;', '\u2019')):
        if v not in seen:
            seen.add(v)
            out.append(v)
    return out


_TRUNCATION_WARNINGS = []


def _truncation_risk(t, v, after):
    """
    ⚠️⚠️ WHAT THE ZERO-MATCH GUARD CANNOT SEE — and this is the class it missed.

    `_NEEDLE_LOG` proves a needle MATCHED SOMETHING. It cannot know the needle matched the RIGHT SPAN, and
    it never looks at the output. So a needle can match perfectly and still be wrong: `switching-body` cut
    off at "…self-serve with a CSV" while the master's sentence ran on "…importer and written guides, which
    is fine for one property." — and the tail was published behind the replacement, mid-thought.

    ⚠️ THE SHAPE, STATED NARROWLY SO IT DOES NOT CRY WOLF: a needle that neither ends at a sentence boundary
    NOR is followed by one is replacing part of a sentence and leaving the rest. Headings and labels legally
    lack terminal punctuation, so the test is what FOLLOWS in the source, not what the needle ends with.
    """
    if v not in t:
        return False
    tail = t[t.index(v) + len(v):][:40]
    # A tag, whitespace-to-tag, or terminal punctuation immediately after = a clean cut.
    if re.match(r'\s*(<|$)', tail):
        return False
    if v.rstrip().endswith(('.', '!', '?', ':', '—')):
        return False
    if after.rstrip().endswith(('.', '!', '?')) and re.match(r'\s*[a-z]', tail):
        return True          # replacement ends a sentence but the source runs on in lower case
    return bool(re.match(r'\s*[a-z]', tail)) and not v.rstrip().endswith(',')


def swap(t, before, after, key, tag):
    """Replace `before` with `after`, trying the design's punctuation variants, and RECORD the outcome."""
    for v in _variants(before):
        if v in t:
            if _truncation_risk(t, v, after):
                _TRUNCATION_WARNINGS.append((key, tag))
            _NEEDLE_LOG.append((key, tag, True))
            return t.replace(v, after)
    _NEEDLE_LOG.append((key, tag, False))
    return t



def _element_span(t, i, tag):
    """The [start, end) span of the `tag` element that OPENS at or before `i`, matched by nesting depth.

    ⚠️ A REGEX CANNOT DO THIS. `<div ...>.*?</div>` stops at the first inner close, and a greedy one runs to
    the last close on the page — both silently cut the wrong region, and a wrong cut in an honesty transform
    either leaves the false claim in or takes half the page with it. Counting depth is the only correct way.
    """
    # ⚠️ `i + len(open) ` NOT `i + 1`. `str.rfind(sub, start, end)` requires the WHOLE substring inside the
    # slice, so `rfind('<div', 0, i + 1)` can never match a `<div` that BEGINS at `i` — it silently returned
    # the PREVIOUS SIBLING instead. ⚠️ A needle that starts with its own tag (`cut_element(t, '<div
    # data-plans', 'div', …)`) therefore cut the wrong element and reported success, because a span WAS
    # found — just not the one asked for. Latent since this helper was written; it only surfaced when a
    # needle for the first time began with the tag it was cutting.
    open_tag = '<' + tag
    start = t.rfind(open_tag, 0, i + len(open_tag))
    if start < 0:
        return None
    # ⚠️ AND THE SPAN MUST ACTUALLY CONTAIN THE NEEDLE. Belt and braces on the above: a cut that does not
    # enclose what it was pointed at is the failure this bug produced, and it is cheap to refuse outright.
    if start > i:
        return None
    depth, j = 0, start
    while j < len(t):
        o = t.find('<' + tag, j)
        c = t.find('</' + tag, j)
        if c < 0:
            return None
        if 0 <= o < c:
            depth += 1
            j = o + 1
        else:
            depth -= 1
            if depth == 0:
                return (start, t.find('>', c) + 1)
            j = c + 1
    return None


def _enclosing(t, span, tag):
    """The span of the nearest `tag` element that STRICTLY CONTAINS `span`.

    ⚠️ NOT "the previous opening tag" — that was the first attempt and it is wrong, because the thing
    immediately before a child is usually its PREVIOUS SIBLING, not its parent. On the contact page it
    selected the decorative blur div that sits beside the text block, cut that instead, and left the empty
    dark panel exactly where it was while reporting success. Containment is the only test that means parent.
    """
    j = span[0]
    while True:
        j = t.rfind('<' + tag, 0, j)
        if j < 0:
            return None
        outer = _element_span(t, j, tag)
        if outer and outer[0] < span[0] and outer[1] > span[1]:
            return outer


def cut_element(t, needle, tag, key, label, replacement='', levels=0):
    """Remove (or replace) the `tag` element containing `needle`, optionally `levels` ancestors further out.

    ⚠️ `levels` EXISTS BECAUSE CUTTING THE INNERMOST MATCH LEAVES A DANGLING FRAME. Removing the contact
    page's "Book a demo instead" card cut the div holding its heading and text — and left the CARD around it:
    a dark gradient panel, 200px tall, containing nothing but a decorative blur. It rendered as an empty
    black rectangle at the top of the right rail, and only opening the page in a browser showed it. The
    project already guards other pages against exactly this ("a section heading whose content was filtered
    away must not survive"), which is what made it recognisable.
    """
    i = t.find(needle)
    span = _element_span(t, i, tag) if i >= 0 else None
    # ⚠️ VERIFY THE SPAN ENCLOSES THE NEEDLE before walking outwards, and treat a span that does not as no
    # match at all. Reporting "fired" for a cut that removed the wrong element is worse than reporting a
    # no-op: the no-op is loud, and the wrong cut looks like success.
    if span is not None and not (span[0] <= i < span[1]):
        span = None
    for _ in range(levels):
        span = _enclosing(t, span, tag) if span else None
    _NEEDLE_LOG.append((key, label, span is not None))
    if span is None:
        return t
    return t[:span[0]] + replacement + t[span[1]:]


def helmet(t):
    h = re.search(r'<helmet>(.*?)</helmet>', t, re.S)
    if not h: return {}, t
    hh = h.group(1)
    g = lambda p: (re.search(p, hh, re.S).group(1) if re.search(p, hh, re.S) else '')
    meta = {
        'title': g(r'<title>(.*?)</title>'),
        'description': g(r'<meta name="description" content="(.*?)">'),
        'canonical': g(r'<link rel="canonical" href="https://vyrobel\.com([^"]*)">') or '/',
        'ogType': g(r'<meta property="og:type" content="(.*?)">') or 'website',
    }
    return meta, t[:h.start()] + t[h.end():]

def _dc_rows(html, name):
    """The DCLogic array a page renders from: `static QS = [...]` / `static T = [...]`."""
    dc = re.search(r'<script type="text/x-dc"[^>]*>(.*?)</script>', html, re.S)
    if dc is None:
        return None
    body = re.search(r'static ' + name + r' = \[(.*?)\n\s*\];', dc.group(1), re.S)
    if body is None:
        return None
    import ast
    return ast.literal_eval('[' + re.sub(r'\bnull\b', 'None', body.group(1)) + ']')


def _esc(x):
    return (str(x).replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;')
            .replace('"', '&quot;'))


def _render_dc_page(t, key):
    """Carry the corrected DCLogic rows into the output, derive the counter, regenerate the JSON-LD."""
    src = io.open(os.path.join(os.path.dirname(__file__), 'core', key + '.html'), encoding='utf-8').read()

    if key == 'faq':
        rows = _dc_rows(src, 'QS') or []
        # ⚠️ CORRECT AT SOURCE, THEN DROP — in that order, so a removed question can never be rendered by
        #    an earlier step. `Where is data hosted?` goes because EU hosting is HELD pending a residency
        #    inventory, and a question with no honest answer should not be asked.
        kept = []
        for r in rows:
            q = r[1]
            if q in _FG_DROP:
                continue
            kept.append([r[0], q, _FG_FAQ.get(q, r[2])])

        items = []
        for topic, q, a in kept:
            items.append(
                '<details data-faqitem="" data-topic="' + _esc(topic) + '" '
                'style="border-radius:14px;background:rgba(255,252,248,.6);'
                'border:1px solid rgba(255,255,255,.75);padding:14px 16px;">'
                '<summary style="font-family:Sora,sans-serif;font-size:15px;font-weight:600;'
                'color:#2B2320;cursor:pointer;">' + _esc(q) + '</summary>'
                '<div style="font-size:14px;line-height:1.7;color:#4A403A;margin-top:8px;">'
                + _esc(a) + '</div></details>'
            )
        t = _fill(t, r'(<div data-faqlist=""[^>]*>)(</div>)', ''.join(items), key, 'FG/faq-list')
        # ⚠️ DERIVED, NEVER TYPED. The master read "18 questions" above a list of zero while its source
        #    held 25 — wrong three ways at once.
        t = _count(t, r'(<div data-faqcount=""[^>]*>)([^<]*)(</div>)',
                   str(len(kept)) + ' question' + ('' if len(kept) == 1 else 's'), key, 'FG/faq-count')

        # ⚠️ ONE SOURCE, RENDERED TWICE. Generated from `kept` — never maintained beside it.
        ld = {'@context': 'https://schema.org', '@type': 'FAQPage', 'mainEntity': [
            {'@type': 'Question', 'name': q,
             'acceptedAnswer': {'@type': 'Answer', 'text': a}} for _tp, q, a in kept]}
        # ⚠️ INSERTED, NOT REPLACED. The converter strips every <script> before this runs (Gate 7's rule:
        #    the design's JSON-LD is ours to regenerate, not to reproduce) — so there is nothing here to
        #    substitute into, and a replace matched nothing and said so. This emits the block fresh from
        #    `kept`, which is what makes it structurally impossible for it to fall behind the visible list.
        _ld = ('<script type="application/ld+json">' + json.dumps(ld, ensure_ascii=False) + '</script>')
        _before = len(t)
        t = t + _ld
        _NEEDLE_LOG.append((key, 'FG/faq-jsonld', len(t) > _before))

        # ⚠️⚠️ SELF-CHECKS, REGISTERED LIKE A NEEDLE so `docsbuild.py --check` goes red on any of them.
        #    A generated copy is only safe while it is PROVEN to be the same copy.
        _rendered = re.findall(r'<summary[^>]*>(.*?)</summary>', t, re.S)
        _NEEDLE_LOG.append((key, 'FG/faq-count-matches-list',
                            re.search(r'data-faqcount=""[^>]*>(\d+) question', t) is not None
                            and int(re.search(r'data-faqcount=""[^>]*>(\d+) question', t).group(1))
                            == len(_rendered) == len(kept)))
        _lit = json.loads(re.search(r'application/ld\+json">(.*?)</script>', t, re.S).group(1))
        _NEEDLE_LOG.append((key, 'FG/faq-jsonld-matches-visible',
                            [(q['name'], q['acceptedAnswer']['text']) for q in _lit['mainEntity']]
                            == [(q, a) for _tp, q, a in kept]))
        # ⚠️ A dropped question must not survive anywhere on the page, visible or structured.
        _NEEDLE_LOG.append((key, 'FG/faq-dropped-stay-dropped',
                            not any(d in t for d in _FG_DROP)))
        return t

    rows = _dc_rows(src, 'T') or []
    items = []
    for r in rows:
        term, full, definition, why = r[1], r[2], r[3], r[4]
        fix = _FG_GLOS.get(term)
        if fix is not None:
            definition, why = fix['definition'], fix['why']
        items.append(
            '<div data-term="" data-topic="' + _esc(r[0]) + '" '
            'style="border-radius:14px;background:rgba(255,252,248,.6);'
            'border:1px solid rgba(255,255,255,.75);padding:14px 16px;">'
            '<div style="font-family:Sora,sans-serif;font-size:15px;font-weight:600;color:#2B2320;">'
            + _esc(term) + (' <span style="font-weight:400;color:#4A403A;">' + _esc(full) + '</span>'
                            if full and full != term else '') + '</div>'
            '<div style="font-size:14px;line-height:1.7;color:#4A403A;margin-top:6px;">'
            + _esc(definition) + '</div>'
            + ('<div style="font-size:13px;line-height:1.6;color:#6B5F57;margin-top:6px;">'
               + _esc(why) + '</div>' if why else '') + '</div>'
        )
    t = _fill(t, r'(<div data-glosslist=""[^>]*>)(</div>)', ''.join(items), key, 'FG/glossary-list')
    # ⚠️ The master's "35 terms" is ACCURATE today. It is still derived: an accurate hardcoded count is a
    #    wrong one waiting, and this is the "sixteen written guides" shape.
    t = _count(t, r'(<div data-glosscount=""[^>]*>)([^<]*)(</div>)',
               str(len(rows)) + ' term' + ('' if len(rows) == 1 else 's'), key, 'FG/glossary-count')
    _NEEDLE_LOG.append((key, 'FG/glossary-count-matches-list',
                        re.search(r'data-glosscount=""[^>]*>(\d+) term', t) is not None
                        and int(re.search(r'data-glosscount=""[^>]*>(\d+) term', t).group(1))
                        == len(re.findall(r'<div data-term=""', t)) == len(rows)))
    return t


def _fill(t, pattern, inner, key, tag, three=False):
    m = re.search(pattern, t, re.S)
    _NEEDLE_LOG.append((key, tag, m is not None))
    if m is None:
        return t
    if three:
        return t[:m.start()] + m.group(1) + inner + m.group(3) + t[m.end():]
    return t[:m.start()] + m.group(1) + inner + m.group(2) + t[m.end():]


def _count(t, pattern, text, key, tag):
    m = re.search(pattern, t, re.S)
    _NEEDLE_LOG.append((key, tag, m is not None))
    if m is None:
        return t
    return t[:m.start()] + m.group(1) + text + m.group(3) + t[m.end():]


def _unhide(t, attr, key, tag):
    """
    ⚠️⚠️ SHIP IT VISIBLE. JAVASCRIPT DOES THE HIDING — NEVER THE REVERSE.

    The design hides inactive panes with an inline `display: none`, so seven of eight product panels and
    the two non-default home lenses are absent from the DOM's rendered box tree. That is safe ONLY while
    nothing depends on JS to bring them back. The moment a script is the thing that reveals them, a blocked
    bundle, a 404 after a deploy, a slow network or a crawler sees SEVEN-EIGHTHS OF THE PRODUCT PAGE GONE.

    ⚠️ So the inline hide is STRIPPED here, at build time: the page ships with every pane visible and
    readable, and `marketing.js` collapses the inactive ones on load. A JS failure now degrades to a long
    scrolling page — which is a bad layout, not a missing product, and still fully indexable.
    """
    n = 0

    def _strip(m):
        nonlocal n
        n += 1
        return m.group(0).replace('display: none;', '').replace('display:none;', '') \
                         .replace('style=""', '').replace('style=" "', '')

    t = re.sub(r'<div ' + attr + r'="[^"]*"[^>]*>', _strip, t)
    _NEEDLE_LOG.append((key, tag, n > 0))
    return t


def _default_tab(t, btn_attr, first, key, tag):
    """
    ⚠️ MAKE THE NO-JS STATE VALID — nothing more. Runtime ARIA belongs to the script.

    ⚠️ MEASURED before writing this: the three home audience tabs are ALL `aria-selected="false"`, so the
    tablist advertises that NOTHING is selected. That was wrong before any script existed, and it stays
    wrong for a reader whose JS never runs — so the DEFAULT tab is marked selected at build time.
    (The one `aria-selected="true"` already on /home belongs to the nine-hour rail, not these tabs.)

    ⚠️ Everything else — moving `aria-selected`, roving `tabindex`, `hidden` — is done by `marketing.js`
    at runtime, because only the runtime knows which tab is current. Doing it here too would be two
    definitions of one state (§N.8f).
    """
    hit = [False]

    def _mark(m):
        if m.group(1) == first and 'aria-selected="false"' in m.group(0):
            hit[0] = True
            return m.group(0).replace('aria-selected="false"', 'aria-selected="true"')
        return m.group(0)

    # ⚠️ THE WHOLE TAG, not a forward slice from the data attribute: in this markup `aria-selected` sits
    #    BEFORE `data-lensbtn`, so a forward-looking pattern matched nothing. Caught by the zero-match
    #    guard on the first build — which is exactly the defect that guard CAN see.
    t = re.sub(r'<button[^>]*' + btn_attr + r'="([^"]*)"[^>]*>', _mark, t)

    # ⚠️ THE NEEDLE ASSERTS THE END STATE, NOT THAT A MUTATION HAPPENED. /product's stage 0 already carried
    #    `aria-selected="true"`, so "nothing changed" is CORRECT there — and reporting that as a zero-match
    #    would red the build for work that is right, which this project's own rule forbids. What must hold
    #    is that the default tab IS selected once this function has run, however it got that way.
    ok = bool(re.search(r'<button[^>]*' + btn_attr + r'="' + re.escape(first) + r'"[^>]*aria-selected="true"', t)
              or re.search(r'<button[^>]*aria-selected="true"[^>]*' + btn_attr + r'="' + re.escape(first) + r'"', t))
    _NEEDLE_LOG.append((key, tag, ok))
    return t


def convert(key):
    t = io.open(f"{SRC}/{key}.html", encoding='utf-8').read()
    meta, t = helmet(t)

    # body only
    b = re.search(r'<body[^>]*>(.*)</body>', t, re.S)
    t = b.group(1) if b else t
    t = re.sub(r'</?x-dc>', '', t)

    # ⚠️ Remove every dc-runtime script (React logic + the design's JSON-LD, which is Gate 7's).
    t = re.sub(r'<script.*?</script>', '', t, flags=re.S)

    # ⚠️ dc-runtime attributes — REMOVED, not escaped (React wiring, not content; Gate 1's rule).
    t = re.sub(r'\s(?:ref|onClick|onChange|onInput|onSubmit|onMouseEnter|onMouseLeave|onFocus|onBlur|onKeyDown|style-hover|style-focus|style-active|hint-size|hint-placeholder-val)="[^"]*"', '', t)
    # ⚠️⚠️ `sc-camel-*` IS NOT ALL RUNTIME WIRING, AND STRIPPING IT ALL BROKE EVERY ICON ON THE SITE.
    #    The design encodes CAMEL-CASE ATTRIBUTE NAMES with this prefix, and only the `on-*` ones are React
    #    handlers. Across the masters: `sc-camel-view-box` x593 and `sc-camel-preserve-aspect-ratio` x4 are
    #    SVG **content**; `on-click`/`on-change`/`on-input` x108 are wiring.
    # ⚠️⚠️ THE CONSEQUENCE, MEASURED IN A BROWSER: 185 inline `<svg>` in the generated pages and ZERO
    #    `viewBox` attributes. An `<svg width="15" height="15">` whose paths are drawn on a 24x24 grid, with
    #    no viewBox to map one onto the other, renders the top-left corner of the drawing and CLIPS THE
    #    REST. 60 of 129 svgs on three sampled pages had no viewBox; 26 were demonstrably clipped.
    #    ⚠️ Nothing 404'd and no request failed — the audit was right that the site is imageless. The icons
    #    were present, sized and correctly coloured, and geometrically cropped.
    # ⚠️ THIRD TIME THE CONVERTER HAS SILENTLY REMOVED SOMETHING NEEDED (the print rules, the `[data-dusk]`
    #    theme CSS, now this) — but a DIFFERENT strip from the `<style>` one, so the class is "a blanket
    #    strip that does not distinguish wiring from content", not "the style block".
    def _sc_camel(m):
        name = m.group(1)
        if name.startswith('on-'):
            return ''                                    # React handler — still removed
        head, *rest = name.split('-')
        return ' ' + head + ''.join(w.capitalize() for w in rest) + '="' + m.group(2) + '"'

    t = re.sub(r'\ssc-camel-([a-z-]+)="([^"]*)"', _sc_camel, t)

    # ⚠️ ESCAPE THE DESIGN'S HANDLEBARS NOW — before we emit any Blade of our own, so this pass can
    # never escape our own {{ }} (it did on the first run; caught by inspecting the "leftovers").
    left = len(re.findall(r'\{\{', t))
    t = t.replace('{{', '@{{')

    # shared partials: the shell already renders nav + footer; the widget is D4; the calculator D1/D4.
    t = re.sub(r'<dc-import name="Site (?:Nav|Footer)"[^>]*>\s*(?:</dc-import>)?', '', t)
    t = re.sub(r'<dc-import name="Support Widget"[^>]*>\s*(?:</dc-import>)?', '', t)
    t = re.sub(r'<dc-import name="ROI Calculator"[^>]*>\s*(?:</dc-import>)?', '<x-marketing.roi-placeholder />', t)
    t = re.sub(r'<dc-import[^>]*>\s*(?:</dc-import>)?', '', t)   # any straggler

    # image slots → an intentional styled placeholder (D8 open: never a stock photo)
    def slot(m):
        a = m.group(0)
        ph = re.search(r'placeholder="([^"]*)"', a)
        rad = re.search(r'radius="(\d+)"', a)
        shape = re.search(r'shape="([a-z]+)"', a)
        return ('<x-marketing.image-slot brief="' + (ph.group(1) if ph else '') + '"'
                + (f' radius="{rad.group(1)}"' if rad else '')
                + (f' shape="{shape.group(1)}"' if shape else '') + ' />')
    t = re.sub(r'<image-slot[^>]*>(?:</image-slot>)?', slot, t)

    # links → Gate 1's pending-aware component (attributes, incl. the inline style, pass through)
    def link(m):
        page, attrs, inner = m.group(1), m.group(2), m.group(3)
        frag = ''
        if '#' in page:
            page, frag = page.split('#', 1); frag = '#' + frag
        page = page.replace('.dc.html', '').strip()
        if page in AUTH:
            return f'<a href="{{{{ route(\'{AUTH[page]}\') }}}}"{attrs}>{inner}</a>'
        k = PAGE_KEY.get(page)
        if not k:
            return f'<a href="#"{attrs}>{inner}</a>'
        f = f' fragment="{frag}"' if frag else ''
        return f'<x-marketing.nav-link key="{k}"{f}{attrs}>{inner}</x-marketing.nav-link>'
    # ⚠️ ATTRIBUTES MAY PRECEDE href. The first version of this regex required `<a href=…` FIRST, so every
    # anchor written `<a data-plancta="…" href="Vyrobel Signup.dc.html">` was left UNCONVERTED — 15 dead
    # `.dc.html` links shipped across all ten pages before a sweep caught them. Match href anywhere in the
    # tag, and keep the surrounding attributes.
    def link_any(m):
        pre, page, post, inner = m.group(1), m.group(2), m.group(3), m.group(4)
        return link(_M(page, pre + post, inner))
    class _M:
        def __init__(self, page, attrs, inner): self._g = (None, page, attrs, inner)
        def group(self, i): return self._g[i]
    t = re.sub(r'<a((?:\s+[a-zA-Z-]+="[^"]*")*?)\s+href="(Vyrobel [^"]+)"((?:\s+[a-zA-Z-]+="[^"]*")*)\s*>(.*?)</a>',
               link_any, t, flags=re.S)

    # fonts → the canonical tokens too (a literal font stack is the same drift as a literal hex)
    t = t.replace('Sora, sans-serif', 'var(--vy-display)').replace('Inter, sans-serif', 'var(--vy-sans)')

    # ⚠️ THE TWO LEDGER-DRIVEN SECTIONS — swapped for components that read the ONE guarded source, so the
    # page has a coherent PRODUCTION shape (D1 filters both to empty; the components omit cleanly).
    def swap_section(t, anchor, replacement):
        i = t.find(anchor)
        if i < 0: return t, False
        s0 = t.rfind('<section', 0, i); e0 = t.find('</section>', i)
        if s0 < 0 or e0 < 0: return t, False
        return t[:s0] + replacement + t[e0 + 10:], True
    t, _ = swap_section(t, 'Vyrobel by the numbers', '<x-marketing.stats-band />')
    t, _ = swap_section(t, 'What operators tell us', '<x-marketing.testimonials :quotes="$testimonialQuotes" />')

    # ══ HONESTY TRANSFORMS — these MUST live here, not in a post-generation patch ═════════════════
    # ⚠️ D13's name fix was first applied by patching the generated files. Regenerating them silently
    # REVERTED it and the ledger names came back — caught only because the D13 sweep test existed. Any
    # honesty fix applied after generation is undone by the next generation, so it belongs in the pipeline.

    # (1) D13 — no ledger-flagged person may appear in page markup. The honesty gate reads the ledger and
    #     can never see a name typed into a page, so this is the only thing that can.
    #
    # ⚠️⚠️ GATE 7 — THIS TRANSFORM IS THE RIGHT FIX FOR A TESTIMONIAL AND THE WRONG FIX FOR ABOUT, so it is
    #     now scoped. On a testimonial the defect is that a REAL-SOUNDING person is quoted without consent,
    #     and anonymising the speaker cures it. On the About page the same two names appear as CO-FOUNDERS of
    #     a company with ONE person in it — and renaming an invented co-founder does not remove an invented
    #     co-founder, it invents a different one. ⚠️ Running this here would have produced a page asserting
    #     that "Chara Melis" and "Bram Hoekstra" founded Vyrobel, which is a fresh fabrication introduced by
    #     an honesty transform. The team block is DELETED below instead.
    if key == 'about':
        pass
    else:
      for _old, (_new, _oi, _ni) in {
          'Amara Mensah':   ('Nadia Farouk',  'AM', 'NF'),
          'Marco Reyes':    ('Petar Novak',   'MR', 'PN'),
          'Joris van Dijk': ('Bram Hoekstra', 'JV', 'BH'),
          'Elena Kastellis':('Chara Melis',   'EK', 'CM'),
      }.items():
          if _old in t:
              t = t.replace(_old, _new)
              t = re.sub(r'>' + _oi + r'<', '>' + _ni + '<', t)

    # (2) ⚠️ ATTRIBUTED QUOTES — a testimonial from an invented person is fabricated speech, and the
    #     Sales pages carried three of them LIVE (for-hotels / for-villas / for-apartments), each with the
    #     same claims the case-study ruling ordered removed. Renaming the speaker does not help: it just
    #     invents a different person. The whole quote element goes.
    #     ⚠️ ONLY a figure that CONTAINS a quote. The first version stripped EVERY <figure>, which also ate
    #     the home page's image-gallery wrappers and left their <figcaption>s orphaned outside any <figure>
    #     — invalid HTML, introduced by an over-broad honesty fix. A photo caption is not attributed speech.
    t = re.sub(r'<figure\b[^>]*>(?:(?!</figure>).)*?<blockquote\b.*?</figure>', '', t, flags=re.S)
    t = re.sub(r'<blockquote\b[^>]*>.*?</blockquote>', '', t, flags=re.S)

    # (3) ⚠️ QUANTIFIED ACHIEVED OUTCOMES — the animated `data-count` counters ("58% of peak-season nights
    #     now booked direct", "94% of guests complete check-in") assert measured results from customers who
    #     do not exist. The counter SPAN is emptied of its claim and its caption removed with it.
    def _strip_counter(m):
        return ''
    t = re.sub(r'<div\b[^>]*>\s*<span[^>]*data-count="[^"]*"[^>]*>.*?</div>\s*<div[^>]*>[^<]{0,120}</div>', '', t, flags=re.S)
    t = re.sub(r'<span[^>]*data-count="[^"]*"[^>]*>.*?</span>', '', t, flags=re.S)

    # (4) ⚠️ B12 — THE PRICE HAS ONE SOURCE. The home teaser and the pricing cards each hard-coded their
    #     own per-unit figures (€6/€5 here, €6/€4 there) while the engine charges FLAT €49/€99. Bound to
    #     MarketingPricing so marketing cannot drift from billing again. ⚠️ This lives in the converter for
    #     the same reason (1) does: applied afterwards, the next regeneration silently undoes it — which is
    #     exactly what happened, twice, and was caught only because the guards existed.
    _MP = "\\App\\Support\\Marketing\\MarketingPricing"

    # ⚠️ STYLE-AGNOSTIC. The first version of this needle spelled out `var(--vy-muted)` — but this pass runs
    # BEFORE the hex→token substitution, so at this point the markup still says `#4A403A` and the needle
    # matched nothing while looking exactly like it should have worked. Match the shape, not the styling.
    # ⚠️ THE PRICE SPAN'S OPENING TAG IS CAPTURED TOO, so the binding can be MARKED. The first version
    #    matched from `>€6</span>` — which starts after the opening tag — so `data-price` could only be
    #    attached to the SUFFIX span, and the sitewide guard counted the wrong element.
    _price_re = re.compile(r'(<span style="[^"]*">)€([456])</span>(<span style="[^"]*">)/ unit / month</span>')
    _seen = []

    def _bind_price(m):
        _idx = len(_seen)
        _seen.append(m.group(2))
        # ⚠️ `data-price` IS NOT DECORATION — it is what makes this binding CHECKABLE. The home teaser's
        #    prices were bound by B12 and left unmarked, so the sitewide price guard could not see them and
        #    reported "2 price nodes" where four exist. A binding nothing can find is a binding nothing
        #    protects; the guard's own count-pin is what surfaced it.
        # ⚠️ It also carries the AMOUNT, not the suffix — `MarketingPricing::tiers()` formats with its
        #    currency symbol, so the guard strips that rather than the page omitting it.
        return (m.group(1).replace('<span ', '<span data-price data-plan="home-tier-' + str(_idx) + '" ', 1)
                + '{{ ' + _MP + "::tiers()[" + str(_idx) + "]['formatted'] ?? '' }}</span>"
                + m.group(3) + '{{ ' + _MP + '::suffix() }}</span>')

    # ⚠️ REPORTED, because Gate 7's zero-match rule applies hardest to a PRICE transform — and this exact
    #    needle silently matched nothing once already (it spelled out a token while the markup still held a
    #    hex literal). It runs on the two pages that carry prices; anywhere else a no-op is expected, so it
    #    is recorded only where a price is actually drawn.
    # ⚠️ REPORTED ON `home` ONLY, AND THE SCOPE WAS FOUND BY REPORTING IT. This needle binds the design's
    #    "€6 / unit / month" figures, and the first version of this reporting also expected it on `pricing`
    #    — which came back a no-op. ⚠️ The pricing MASTER carries no `€` and no headline sentence at all:
    #    its prices are drawn inside the plan-cards markup that block (8) now replaces wholesale. So the
    #    needle was never for that page, and claiming it was would have been a guard reporting on work it
    #    does not do.
    _n_before = len(_price_re.findall(t))
    t = _price_re.sub(_bind_price, t)
    if key == 'home':
        _NEEDLE_LOG.append((key, 'b12-price-binding', _n_before > 0))
        t = swap(t, 'Priced per unit, not per headache', '{{ ' + _MP + '::headline() }}', key, 'b12-headline')
    else:
        t = t.replace('Priced per unit, not per headache', '{{ ' + _MP + '::headline() }}')


    # ══ (8) ⚠️⚠️ THE PRICE AND THE TIER NAME HAVE ONE SOURCE — `config/billing.php` ═══════════════════
    #        ⚠️ B12 bound the pricing CARDS and the home teaser's NUMBERS. This closes what it left: the
    #        comparison table one section below the cards (four invented tiers), an Enterprise card that
    #        is a fifth invented tier, the home teaser's invented NAMES sitting over the real prices, the
    #        SEO title and description asserting a per-unit model, two FAQ answers describing per-unit
    #        billing mechanics, and a Sales page tying a commercial promise to a tier that does not exist.
    #        ⚠️ ELEVEN representations of two numbers and two names. §N.8f is not a style rule.

    if key == 'pricing':
        # ⚠️⚠️ THE INERT CURRENCY SWITCHER — A CONTROL WITH NO MACHINERY, ON A PUBLISHED PAGE.
        #
        # The design offers EUR / GBP / USD and a `PRICES` table in all three. ⚠️ THE ENGINE HAS ONE
        # CURRENCY: every `plans` row is EUR. So two of the three buttons name a currency nobody can be
        # billed in — the same fabrication class as the four invented tiers B12 removed from this page,
        # and it survived because the tier sweep looked at NAMES and PRICES, not at CONTROLS.
        #
        # ⚠️ AND IT DOES NOTHING. Each button carried an `sc-camel-on-click` prototype handler; the
        # converter strips the runtime and keeps the markup, so the rendered page has ZERO script tags and
        # five dead buttons. A visitor clicks "£ GBP" and the price does not move. ⚠️ That is worse than
        # omitting it: an inert control ANSWERS the question — it says this product is sold in pounds.
        #
        # ⚠️ REMOVED, NOT RE-POINTED. There is nothing to wire it to: a display-only converter would need a
        # rate source we do not have, and showing an indicative £ price for a EUR-billed subscription is the
        # same misleading claim one layer down. The price already renders in the plan's own currency through
        # `MarketingPricing`, so nothing true is lost.
        _cur = t.find('aria-label="Display currency"')
        if _cur != -1:
            _s, _e = _element_span(t, _cur, 'div')
            t = t[:_s] + t[_e:]
        _NEEDLE_LOG.append((key, 'px-currency-switcher-removed', _cur != -1))

        # ⚠️⚠️ D8 — THE RESIDUE THE SWITCHER REMOVAL LEFT BEHIND, AND WHY IT SURVIVED A WHOLE PASS.
        #    Deleting the control above left the COPY THAT DESCRIBED IT on the page: the PDF blurb still
        #    said the summary prints "in your selected currency" when there is no longer anything to select
        #    with — and there never was, since the buttons were inert over a EUR-only engine.
        #    ⚠️ THE CLASS: a deletion is not finished until the sentences that depended on it are gone too.
        #    The switcher sweep looked at CONTROLS; this is the second time this page has needed the
        #    follow-up read for prose. Registered as its own needle so it cannot silently stop matching.
        #    ⚠️ NOT re-pointed at the plan's real currency: the price already renders in its own currency
        #    through `MarketingPricing`, so naming a currency here would re-assert a choice the reader
        #    does not have. The clause simply goes.
        t = swap(t,
                 "Prints as a one-page summary with the plan you're viewing, in your selected currency.",
                 "Prints as a one-page summary with the plan you're viewing.",
                 key, 'px-currency-residue-copy')

        # ⚠️⚠️ THE "−15%" BADGE — A LIVE FALSE CLAIM, CONTROL OR NO CONTROL.
        #    ⚠️ VERIFIED against the `plans` rows, not inherited: Essential 12 × €49 = €588 against a €490
        #      year, and Pro 12 × €99 = €1,188 against €990 — both 16.67% (two months free), NOT 15%.
        #    ⚠️ AND `MarketingPricing::isPlaceholder()` IS TRUE: nobody has set a real price yet, so the
        #      16.67% is itself derived from placeholders. NEITHER figure is real.
        #
        # ⚠️ REMOVED, NOT CORRECTED. Replacing 15 with 16.67 would ship a MORE precise-looking number
        #    computed from prices that are admittedly provisional — a worse claim wearing better arithmetic.
        #    Setting a real price is the founder's, not this converter's.
        # ⚠️ AND NOT NEGATED: nothing says "no longer 15%". The canon forbids answering a false claim by
        #    quoting it, because the phrase stays on the page for a skimming reader. The badge simply goes.
        # ⚠️ THE WORD "Annual" STAYS, and that is deliberate — annual billing genuinely EXISTS (the `plans`
        #    table carries a `yearly` interval for both tiers). What was false was the SIZE of the saving,
        #    so the true half is kept and only the figure is cut.
        t = swap(t,
                 '<span style="padding: 2px 7px; border-radius: 6px; background: rgba(217,102,46,.16); '
                 'color: #8F2F09; font-size: 10.5px; font-weight: 600;">−15%</span>',
                 '',
                 key, 'px-annual-discount-figure-removed')

        # ══ ⚠️⚠️ THE DEAD CONTROLS — ONE CLASS, NOT THREE BUGS ═══════════════════════════════════════
        #
        # ⚠️ THE CLASS: a control that LOOKS live — an enabled `<button>` with `cursor: pointer` — carrying
        #    a `data-` attribute `public/js/marketing.js` does not bind. The converter strips the design's
        #    dc-runtime and keeps its markup, so every prototype handler that was never rebuilt left a
        #    button behind. ⚠️ It is the currency switcher's class exactly (removed in `3b155dc`), and the
        #    hour rail's (bound in the pass before this one) — the third time this shape has been found.
        # ⚠️ `MarketingDeadControlsTest` now derives the bound set FROM the script and reds on any new one,
        #    so this list is a record of what was removed, not the thing that keeps the rule true.
        #
        # ⚠️⚠️ (a) THE MONTHLY / ANNUAL TOGGLE — REMOVED, AND THE RECORDED REASON FOR REMOVING IT IS WRONG.
        #    The standing note says it was refused because "there is no annual plan at the advertised
        #    discount". ⚠️ THE FIRST HALF IS FALSE AND THIS FILE ALREADY SAYS SO, forty lines up: annual
        #    billing genuinely exists (`config/billing.php` carries `yearly` prices for both tiers, the
        #    `plans` table carries a `yearly` interval row for each, and the subscription Checkout has a
        #    working period toggle). What was false was the SIZE of the saving — the "−15%" badge — and
        #    that was cut on its own above.
        # ⚠️ SO WHY REMOVE IT AT ALL? Because it does nothing, not because annual is a lie. Clicking
        #    "Annual" on the page where money is decided leaves both prices unchanged, and an inert control
        #    on that page ANSWERS the question — it says the annual price is the monthly one.
        # ⚠️ AND NOT WIRED, because wiring is a build and this pass removes. `MarketingPricing` is ALREADY
        #    interval-parameterised end to end (`tiers`/`tiersForDisplay`/`comparison`/`headline`/
        #    `priceNote`/`whoPays`/`suffix` all take `$interval`), so wiring it is small and honest —
        #    ⚠️ recorded for the founder as an OPTION, not done here.
        # ⚠️ NOTHING TRUE IS LOST: the page never showed an annual price. It offered a control that did not
        #    reach one. The cards keep saying "Billed monthly", which is what they are priced at.
        t = cut_element(t, 'data-cycle="monthly"', 'div', key, 'px-period-toggle-removed')

        # ⚠️⚠️ (a2) `data-portfolionote` — AN EMPTY DIV, AND ITS CONTENT MUST NOT COME BACK.
        #
        # ⚠️ THE BRIEF DESCRIBED IT AS "real pricing copy hidden by inline CSS". IT IS NOT: the element is
        #    `<div data-portfolionote style="display:none"></div>` — EMPTY. The commercial line a reader
        #    would think of ("No setup fee · No commission on direct bookings · …") is a DIFFERENT element
        #    two lines below, carries no `display:none`, and has been VISIBLE on the live page all along.
        #
        # ⚠️⚠️ WHAT IT WAS FOR, read from the master: the prototype FILLED it from the ROI calculator —
        #      el.textContent = 'Based on your ' + units + ' units, that\u2019s about ' + sym + monthly + ' a month.'
        #    So its condition is a unit count AND a chosen plan AND a currency AND a billing cycle. THREE of
        #    those four mechanisms are gone on purpose: the currency switcher (removed, EUR-only engine),
        #    the Monthly/Annual toggle (removed, inert), and per-unit pricing itself — which this page's own
        #    headline now REFUSES, reading "One price per property account" against a flat two-tier engine.
        # ⚠️ SO IT IS REMOVED, NOT UNHIDDEN. Revealing it could only ever re-assert the per-unit model the
        #    page was corrected to stop claiming; and an empty div that nothing can fill is bytes plus a
        #    misleading hook for whoever next reads the source.
        t = cut_element(t, 'data-portfolionote', 'div', key, 'px-portfolio-note-removed')


        # ⚠️ (b) "DOWNLOAD PRICING AS PDF" — there is no PDF, and there never was.
        #    ⚠️ CHECKED BOTH WAYS, not assumed: no route, controller or generator anywhere in the app
        #      produces a pricing PDF (the app's dompdf documents are folios, statements and remittances,
        #      all tenant-scoped and behind auth) — AND the design master gives this button NO handler
        #      either. It was drawn, never wired, in the prototype as well as here.
        #    ⚠️ NOT re-pointed at `window.print()` like the story buttons: "Download … as PDF" and "print
        #      this page" are different promises, and the print stylesheet is stripped anyway (an open,
        #      separate finding). Renaming a control to match what is easy to build is how a page starts
        #      describing its implementation instead of its offer.
        #    ⚠️ ITS BLURB GOES WITH IT. "Prints as a one-page summary with the plan you're viewing" is a
        #      sentence about the button — the residue shape this exact page has now hit twice (the
        #      currency-switcher copy above is the other). A deletion is not finished until the sentences
        #      that depended on it are gone. The row keeps "Request a formal quote", so no empty frame is
        #      left behind.
        t = cut_element(t, 'Download pricing as PDF', 'button', key, 'px-pdf-button-removed')
        t = cut_element(t, "Prints as a one-page summary with the plan you're viewing.", 'div',
                        key, 'px-pdf-blurb-removed')


        # ⚠️⚠️ THE CARDS MOVE INTO A COMPONENT, AND THIS IS THE LOAD-BEARING PART OF THE PASS. B12 rebuilt
        #    these cards to read from the billing config — by HAND-EDITING THE GENERATED PAGE. The `@php`
        #    block and the `@foreach ($mkTiers …)` loop existed nowhere in this file, so `/pricing` had
        #    quietly become UN-REGENERABLE: running the generator over it would have restored the design's
        #    four hardcoded tiers ON THE CARDS TOO, silently, and the only warning would have been a reader
        #    noticing "Starter €6" back on a live page.
        #    ⚠️ That is this converter's own doctrine — "a post-generation patch is undone by the next
        #    generation" — turned on the FIX rather than the defect. Discovered here only because the B12
        #    price needle was reported for the first time and came back a no-op.
        t = cut_element(t, '<div data-plans', 'div', key, 'plan-cards', '<x-marketing.plan-cards />')

        # ⚠️ THE WHOLE COMPARISON SECTION, replaced. Not re-numbered: a four-column ladder asserts three
        #    upgrade steps and the product has one tier boundary, so there is no honest four-column form.
        t = cut_element(t, 'sc-raw-caption', 'section', key, 'comparison-table',
                        '<x-marketing.plan-comparison />')

        # ⚠️ THE ENTERPRISE CARD NEEDS NO CUT OF ITS OWN — it lives INSIDE the cards grid replaced above, so
        #    a second cut for it matched nothing and the report said so. ⚠️ It is gone either way, and this
        #    note is the record of the ruling: B12 kept that card, reasoning it "quotes no price and gates no
        #    feature, so it makes no claim the system must honour". THAT JUDGEMENT IS REVERSED — a tier NAME
        #    is a claim exactly as a price is, and the card also asserted a segment ("250+ units, multi-brand
        #    groups") and four capability bullets. A reader counted five plans; there are two.

        # ⚠️⚠️ THREE MORE B12 BINDINGS THAT LIVED ONLY IN THE GENERATED FILE, and regenerating destroyed all
        #    three before this block existed: the h1, the price note under it, and the FAQ's "who pays"
        #    answer. ⚠️ I found the CARDS were hand-bound, ported those, regenerated — and did not check what
        #    ELSE was hand-bound. The page came back saying "One price per unit" under a table correctly
        #    reading €49 / €99 flat. ⚠️ The lesson is the pass's own: having found one hand-edit, DIFF for the
        #    rest rather than assuming it was the only one.
        for _tag, _b, _a in (
            ('h1-headline', 'One price per unit. The direct channel pays for it.',
             '{{ ' + _MP + '::headline() }}'),
            ('price-note',
             'Prices are per unit per month, illustrative and exclusive of VAT.',
             '{{ ' + _MP + '::priceNote() }}'),
            ('faq-who-pays',
             'Owner portal access and the guest app are included in your plan. You pay per unit; nobody '
             'else is billed.',
             'Owner portal access and the guest app are included in your plan. '
             '{{ ' + _MP + '::whoPays() }}'),
            # ⚠️ TWO FAQ ANSWERS DESCRIBE PER-UNIT BILLING MECHANICS that the engine does not run. A pricing
            #    claim in prose is still a pricing claim \u2014 B12 found the third one this way.
            ('faq-unit-definition',
             "Anything you sell a night of: a hotel room, an apartment, a villa. If a villa is sold whole, "
             "that's one unit \u2014 not one per bedroom.",
             'A unit is anything you sell a night of \u2014 a hotel room, an apartment, a villa. '
             '\u26a0\ufe0f It does not change your price: the subscription is one figure per account '
             'whatever your unit count.'),
            ('faq-seasonal',
             'Seasonal portfolios can scale units down between seasons on monthly billing. Annual plans '
             'price the whole year against your peak count, which usually works out cheaper.',
             'The subscription does not move with your unit count, so a quiet season costs the same as a '
             'busy one. Annual billing is the cheaper of the two cycles.'),
        ):
            t = swap(t, _b, _a, key, _tag)

        # ⚠️ THE SEO TITLE AND DESCRIPTION ARE IN `meta`, NOT IN THE BODY — `helmet()` lifts the <helmet>
        #    block out before any body transform runs, so a swap() against `t` matched nothing and said so.
        #    ⚠️ The TITLE is what a search result shows, and it asserted the billing model in three words;
        #    it was missed by a first sweep that grepped `description=` and not `title=`.
        _seo_title_was = meta.get('title', '')
        meta['title'] = 'Pricing \u2014 one price per property account | Vyrobel'
        meta['description'] = ('Vyrobel pricing: one price per property account, whatever your unit count. '
                               'Every plan includes the commission-free direct booking engine, the owner '
                               'portal and the guest stay app. Monthly or annual, no setup fee.')
        _NEEDLE_LOG.append((key, 'seo-per-unit-claim', 'per-unit' in _seo_title_was))

        # ⚠️⚠️ EMITTED LAST IN THIS BLOCK, AND THE ORDER IS LOAD-BEARING. My first placement put it at
        #    the TOP of the pricing transforms, before the B12 bindings ran. Two things went wrong and
        #    only reading the artefact showed either: the Q&A it captured was the PRE-BINDING text, and
        #    then `swap()` — which rewrites the whole document — substituted the binding INTO MY COPY
        #    too, leaving `{{ … }}` sitting inside a PHP string literal. Built here, after every
        #    transform, it reads the FINAL Q&A exactly as a reader will get it.
        # ══ ⚠️⚠️ STRUCTURED DATA — ONLY THE ONE TYPE THAT IS TRUE TODAY ═══════════════════════════════
        #
        # ⚠️⚠️ THIS IS A CLAIMS PROBLEM, NOT A MARKUP ONE, AND ALMOST EVERYTHING FAILS IT. Structured data
        #    is read by machines and REPRINTED BY THEM, so a false value reaches a reader BEFORE the page
        #    and its caveats ever do. Type by type, against this repository today:
        #      · Organization  — needs `url` (and `logo` for its rich result). APP_URL is localhost, there
        #                        is NO DOMAIN, and public/ holds no logo image. REFUSED.
        #      · Product/Offer — needs `price`. `MarketingPricing::isPlaceholder()` is TRUE: nobody has set
        #                        one. A schema carrying the placeholder would put an unset number into a
        #                        search result. REFUSED, and this is the worst of the set.
        #      · SoftwareApplication — needs `offers`, `aggregateRating` or `review` to be valid. The first
        #                        is the placeholder price; the other two do not exist and inventing a
        #                        rating would be a fabricated review. REFUSED.
        #      · WebSite / BreadcrumbList — both need URLs. Localhost again. REFUSED.
        #      · FAQPage       — needs only questions and answers that are VISIBLE ON THE PAGE. ✅ TRUE.
        #    ⚠️ A schema missing a required field is worse than no schema: it can be discarded as invalid
        #    while its other assertions are still read. So the refused ones ship NOTHING, and what each
        #    needs is recorded in `GO-LIVE-REQUIREMENTS.md` rather than left to be rediscovered.
        #
        # ⚠️⚠️ EMITTED AS BLADE THAT BUILDS THE ARRAY AT RENDER TIME, AND THE REASON IS A BUG I SHIPPED
        #    FIRST. My initial version had this function `json.dumps()` the Q&A straight into a
        #    `<script type="application/ld+json">`. ⚠️ THE RESULT DID NOT PARSE: one answer is bound to the
        #    billing model — it contains `{{ …MarketingPricing::whoPays() }}` — so the JSON carried raw
        #    Blade, and `\A` of `\App\Support` is an invalid JSON escape. Caught by PARSING the output,
        #    exactly as a "validate it by eye" pass would not have.
        #    ⚠️ AND THE DEEPER FAULT WAS THE LAYER: this converter can never see the RENDERED sentence, so
        #    a self-check here comparing template-to-template would have been vacuous about the thing that
        #    matters — whether the structured copy equals what a READER sees. `@json` renders the real
        #    values; `MarketingFaqStructuredDataTest` compares the two over the SERVED page, which is the
        #    only layer that can.
        _faq = re.findall(r'<details[^>]*>\s*<summary[^>]*>(.*?)</summary>\s*<p[^>]*>(.*?)</p>', t, re.S)
        _faq = [(re.sub(r'<[^>]+>', '', q).strip(), re.sub(r'<[^>]+>', '', a).strip()) for q, a in _faq]
        _NEEDLE_LOG.append((key, 'px-faq-pairs-found', len(_faq) >= 4))

        def _php(fragment):
            """One FAQ string as a PHP expression: literals quoted, `{{ expr }}` spliced in as PHP."""
            out = []
            for i, part in enumerate(re.split(r'\{\{(.*?)\}\}', fragment, flags=re.S)):
                if i % 2:
                    out.append('(' + part.strip() + ')')          # the design's binding, kept live
                elif part:
                    out.append("'" + part.replace('\\', '\\\\').replace("'", "\\'") + "'")
            return ' . '.join(out) or "''"

        if _faq:
            _rows = ', '.join(
                "['@type' => 'Question', 'name' => %s, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => %s]]"
                % (_php(q), _php(a)) for q, a in _faq)
            t = t + (
                "\n@php($vyFaqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', "
                "'mainEntity' => [" + _rows + "]])\n"
                '<script type="application/ld+json">@json($vyFaqLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)</script>\n'
            )
            _NEEDLE_LOG.append((key, 'px-faq-jsonld-emitted', '$vyFaqLd' in t))

    if key == 'home':
        # ⚠️ THE SHARPEST FINDING OF THE SWEEP. Gate 3b/B12 bound the home teaser's PRICES \u2014 and left the
        #    tier NAMES, the unit-band segments and the feature ladder hand-written beside them. So the page
        #    rendered the REAL \u20ac49 under the INVENTED label "Starter", which is worse than either being
        #    wrong alone: the number is authoritative and the label is fiction. ⚠️ Binding half a
        #    representation leaves the other half free to drift, which is the §N.8f proof in miniature.
        for _tag, _b, _a in (
            ('home-tier-1', '>Starter</div>',
             ">{{ " + _MP + "::tiers()[0]['name'] ?? '' }}</div>"),
            ('home-tier-2', '>Growth</div>',
             ">{{ " + _MP + "::tiers()[1]['name'] ?? '' }}</div>"),
            # ⚠️ UNIT BANDS THAT DO NOT EXIST. The plans are flat: no tier is bounded by a unit count, so
            #    "Up to 10 units" and "10\u201360 units" are entry criteria the system does not apply.
            #    Replaced with what the config actually distinguishes.
            ('home-band-1', '>Up to 10 units</div>', '>One property account</div>'),
            ('home-band-2', '>10\u201360 units</div>', '>Adds the channel manager</div>'),
            # ⚠️ AN INVENTED UPGRADE LADDER. Between the two real plans the differences are the channel
            #    manager and the team-seat limit \u2014 not maintenance, the owner portal, payouts or reports,
            #    all of which both plans include.
            ('home-ladder',
             'Everything in Starter plus maintenance, the owner portal, payouts, channels and reports.',
             'Everything in the lower plan, plus the channel manager and unlimited team members.'),
            ('home-tier-1-copy',
             'Direct bookings, front desk, calendar and housekeeping for a single property.',
             'Direct bookings, front desk, calendar and housekeeping \u2014 one price, any number of units.'),
            # ⚠️ A THIRD CARD FOR TWO PLANS. "Pro & Enterprise" names one real tier and one invented one,
            #    with a segment and a capability list to match. The real Pro is already the second card.
            ('home-third-card-name', '>Pro &amp; Enterprise</div>', '>Larger portfolios</div>'),
            ('home-third-card-band', '>60+ units, multi-brand</div>', '>Multi-property groups</div>'),
            ('home-third-card-copy',
             'Multi-property groups, granular roles, custom agreements, onboarding and a named contact.',
             'Running several properties, or want the numbers checked against your portfolio before you '
             'commit? Talk to us.'),
        ):
            t = swap(t, _b, _a, key, _tag)

    if key == 'switching':
        # ⚠️ A COMMERCIAL PROMISE TIED TO A TIER THAT DOES NOT EXIST \u2014 in an <h2>, its body, AND the meta
        #    description. ⚠️ It is also a service commitment with no basis anywhere in the product: nothing
        #    models migration support, an onboarding entitlement or a named contact. So the tier reference
        #    goes and the promise is not re-made on the real tiers in its place.
        for _tag, _b, _a in (
            ('switching-h2', 'Migration is included on Growth and above', 'Moving your data across'),
            # \u26a0\ufe0f\u26a0\ufe0f THE NEEDLE USED TO STOP AT "with a CSV" \u2014 MID-SENTENCE \u2014 AND SHIPPED THE REMAINDER.
            #    The master's sentence continues "\u2026with a CSV importer and written guides, which is fine for
            #    one property." Cutting at "CSV" left that tail dangling behind the replacement, so the
            #    PUBLISHED page read: "\u2026ask us rather than assume it importer and written guides, which is
            #    fine for one property." \u2014 prose ending mid-thought, live on /switching.
            #
            # \u26a0\ufe0f WHY EVERY SWEEP MISSED IT: the MASTER is correct and the OUTPUT is not. A check that reads
            #    `core/*.html` sees a clean sentence. This is the wrong-layer read from the other direction,
            #    and it is why the rule is GREP THE CONVERTED OUTPUT.
            #
            # \u26a0\ufe0f AND WHY THE ZERO-MATCH GUARD DID NOT CATCH IT: `_NEEDLE_LOG` proves a needle MATCHED
            #    SOMETHING. It cannot know the needle matched the RIGHT SPAN, and it never reads the output.
            #    A needle can match and still be wrong \u2014 see the coherence check registered below.
            #
            # \u26a0\ufe0f THE FIX IS THE FULL CLAUSE, to the terminal full stop, so nothing can be left behind.
            ('switching-body',
             'The import, the reconciliation, the training and the channel cutover are part of onboarding, '
             'not a professional services line. Starter customers self-serve with a CSV importer and '
             'written guides, which is fine for one property.',
             'Vyrobel imports from a CSV, and the reconciliation step below is how you check it landed '
             'correctly before you cut over. \u26a0\ufe0f What hands-on help comes with a plan is not settled '
             'yet \u2014 ask us rather than assume it.',
            ),
        ):
            t = swap(t, _b, _a, key, _tag)

        # ⚠️⚠️ D9 — THE FALSE DATA-PORTABILITY PROMISE, AND IT IS THE MOST CONSEQUENTIAL CLAIM ON THE SITE.
        #    It read: "Full export of reservations, guests, units, rates and statements as CSV or JSON, on
        #    demand, from Settings." ⚠️ MEASURED against `route:list`, not inferred:
        #      · reservations — ✗ no row-level export. `/app/reports/export.csv` emits AGGREGATES
        #        (revenue by channel, revenue by month), which is not a reservations file.
        #      · guests       — ✓ REAL: `app.guests.bulk.export`, CSV, `can:guests.view`.
        #      · units        — ✗ operator side. (`owner/units/export` exists, but it is OWNER-scoped.)
        #      · rates        — ✗ nothing, anywhere.
        #      · statements   — ✗ operator side. (Owners export their own history.)
        #      · "from Settings" — ✗ THE LOCATION IS FALSE TOO: all 14 `app.settings.*` routes enumerated,
        #        none is an export. The nearest thing sits on SECURITY and emits only the ACTING USER'S own
        #        account fields plus their own audit rows — its docblock says "#28: NOT org reservations".
        #
        # ⚠️⚠️ WHY THIS ONE MATTERS MORE THAN THE OTHER FALSE CLAIMS. It is on the MIGRATION page, read by
        #    someone leaving a competitor — so it could be relied on when CHOOSING to switch. And a
        #    data-portability promise is read as a COMPLIANCE statement: `docs/legal/DATA-INVENTORY.md`
        #    records portability as "PARTIAL, and the gaps are the interesting part" and the operator realm
        #    as "NO EXPORT". The legal record is RIGHT and the page was wrong — corrected here, and the
        #    legal document deliberately NOT touched to match marketing copy.
        #
        # ⚠️ NARROWED, NOT NEGATED. The canon forbids answering a false claim by quoting it ("we do not
        #    offer full export" leaves the phrase on the page for a skimmer), so this says only what is
        #    TRUE, and names only locations that exist. Every capability below was verified against a route.
        #    ⚠️ "No exit fee and no support ticket required" is DROPPED rather than reworded: it is a
        #    commercial term nothing in this repository can evidence.
        t = swap(t,
                 'Full export of reservations, guests, units, rates and statements as CSV or JSON, on '
                 'demand, from Settings. No exit fee and no support ticket required.',
                 'You export from the screen that owns the data rather than as one bundle: guest records '
                 'to CSV from Guests, revenue and channel reports to CSV, Excel or PDF from Reports, and '
                 'maintenance costs the same way. Your own account record downloads from Security, and '
                 'owners export their bookings, statements and payouts from the owner portal.',
                 key, 'switching-export-claim')

        # ⚠️⚠️ THE MIGRATION ESTIMATOR — REMOVED WHOLE, AND THE SENTENCE NAMES NO WEEKS EITHER.
        #
        # ⚠️ WHAT IT WAS: a units slider (1–300), four "what you run on today" buttons, an owners checkbox,
        #    and computed outputs — `data-estweeks` ("4–5 weeks to cutover"), `data-estnote`, `data-estbreak`
        #    and `data-estcost` ("Migration is included at this size").
        # ⚠️ NOTHING BACKS ANY OF IT. There is no migration-duration model anywhere in `app/` (verified by
        #    sweep), and VYROBEL HAS NEVER MIGRATED ANYONE — so there is not even experience to draw on.
        #    ⚠️ `data-estcost` is worse than the duration: a COMMERCIAL PROMISE keyed to portfolio size,
        #    and this same converter already had to strip a tier-linked migration promise ("included on
        #    Growth and above") for having no basis. This is that claim returning through a control.
        #
        # ⚠️ WHY THE WHOLE SECTION AND NOT JUST THE SLIDER. The founder's ruling was "remove the slider, not
        #    the number" — with the proviso that if NO basis exists the sentence must not name weeks either.
        #    It does not, so it must not: a paragraph asserting "4–5 weeks" commits exactly as much as a
        #    control showing it, with less machinery. And the heading, the "two inputs" premise and the
        #    "honest range" justification are all ABOUT the calculation; leaving them would be a section
        #    explaining an estimate it no longer makes — the dangling-frame shape `cut_element` documents.
        #
        # ⚠️ WHAT REPLACES IT SAYS ONLY WHAT IS TRUE: that nobody has been migrated yet, so a published
        #    timeline would be invented. That is a real answer to "how long?", and it promises nothing.
        t = cut_element(
            t, 'data-estwrap=""', 'section', key, 'switching-estimator-removed',
            replacement=(
                '<section data-sec="" style="max-width: 1240px; margin: 0 auto; padding: 0 28px 78px;">'
                '<div data-reveal="" style="max-width: 720px;">'
                '<h2 style="margin: 0 0 10px; font-family: var(--vy-display); font-size: 27px; '
                'font-weight: 600; letter-spacing: -.02em; color: var(--vy-ink);">How long yours would take</h2>'
                '<p style="margin: 0; font-size: 14.5px; line-height: 1.66; color: var(--vy-muted);">'
                '⚠️ We would rather not guess. Vyrobel has not migrated anyone yet, so any timeline printed '
                'here would be invented — and a cutover date is something you would plan staff and owners '
                'around. Tell us what you run on today and how many units you have, and we will work the '
                'schedule out with you rather than publish a number we cannot stand behind.</p>'
                '</div></section>'
            ),
        )

        meta['description'] = ('How to move from your current property management system to Vyrobel: what '
                               'migrates, what does not, the parallel-running cutover, and a realistic '
                               'timeline.')

        # ⚠️⚠️ THE DROPPED `compare` CTA — AND THIS TRANSFORM EXISTS BECAUSE ITS ABSENCE WAS A LANDMINE.
        #    The founder ruled `Alternatives`/`Compare` DROPPED, and the pass executing that ruling removed
        #    this secondary CTA by editing the GENERATED BLADE directly instead of registering it here. So
        #    the committed page was correct while the CONVERTER still produced the link: regenerating
        #    /switching put the dropped CTA back. ⚠️ §N.31.4 violated by the very pass enforcing the ruling,
        #    and invisible until the toolchain was actually RUN (which is the argument for running it).
        #    ⚠️ The primary "Book a migration call" CTA beside it is real and stays.
        #    ⚠️ Registered through `cut_element`, so it joins `_NEEDLE_LOG` and the registry-wide zero-match
        #    check refuses to finish if this needle ever stops matching — which is exactly how the link
        #    would otherwise come back with nothing said.
        #    ⚠️ THE TAG IS `x-marketing.nav-link`, NOT `a` — the design's <a href="Vyrobel Compare.dc.html">
        #    has ALREADY been rewritten into the pending-aware component by the time this block runs. The
        #    first draft targeted `a`, and the zero-match check caught it on the very first run: exactly the
        #    silent no-op it exists to prevent, proven on the transform that exists because of one.
        #    ⚠️ The provenance note is emitted BY the transform, not hand-added afterwards — otherwise the
        #    page still would not reproduce byte-for-byte and the landmine would only be half defused.
        _drop_note = (
            '{{-- \u26a0\ufe0f THE DROP, EXECUTED IN THE CONVERTER. A secondary CTA here read "Compare the\n'
            '                 options first" and pointed at `compare`, which the founder ruled DROPPED. It\n'
            '                 rendered as a marked dead non-link, so /switching was inviting the reader to a\n'
            '                 page that will never exist. The primary "Book a migration call" CTA remains.\n'
            '                 \u26a0\ufe0f It was first removed by editing THIS GENERATED FILE, which meant the\n'
            '                 converter still produced the link and a regeneration would have restored it\n'
            '                 (\u00a7N.31.4). The cut now lives in convert.py and is covered by the\n'
            '                 zero-match check, so a needle that stops matching fails the build. --}}'
        )
        t = cut_element(t, 'Compare the options first', 'x-marketing.nav-link', key, 'drop-compare-cta',
                        replacement=_drop_note)

    # ⚠️⚠️ D7 — THREE NUMBERS ON ONE PAGE, AND THE CANON RULE IS "NEVER TYPE A NUMBER INTO A PAGE".
    #    The design shipped `data-modcount` reading "16 modules" while the page RENDERS 18 and the <h1>
    #    says "Eighteen modules". ⚠️ Counted, not eyeballed: `data-module="operator"` 11 + `"guest"` 4 +
    #    `"owner"` 3 = 18. So the h1 is right and the counter is the false one — a number that was true at
    #    some earlier draft and was never moved again. Same shape as the glossary's "35 terms" above, and
    #    that transform's own comment names it: an accurate hardcoded count is a wrong one waiting.
    #
    # ⚠️ THE COUNTER IS NOW DERIVED from the cards actually present, so it cannot drift from what renders.
    #    ⚠️ It sits beside the filter chips, which ship no JavaScript and therefore never re-count — that
    #    is a SEPARATE finding (the dead-control family) and is deliberately NOT touched here; deriving the
    #    number makes the static label true, which is all this pass claims.
    #
    # ⚠️ THE <h1> WORD IS NOT DERIVED, AND THAT IS A STATED LIMIT. Spelling 18 as "Eighteen" would need a
    #    number-to-words map for a single headline, and the display font's copy is deliberately hand-set.
    #    ⚠️ Instead the build ASSERTS it: if the rendered word ever stops matching the derived count, the
    #    needle below reports a zero-match and the driver refuses. That is the guarantee that matters —
    #    the page cannot ship a headline the cards contradict, even though the word itself is typed.
    # ⚠️⚠️ D7's SWEEP FOUND A FOURTH NUMBER, ON A DIFFERENT PAGE. `/` says "Sixteen modules, and every one
    #    is in every plan" while `/modules` renders EIGHTEEN. The pass was briefed as "three numbers, one
    #    page"; it is four numbers across two, which is what sweeping for the CLASS rather than the instance
    #    is for. ⚠️ DERIVED ACROSS PAGES from the modules design itself, so the two can never disagree again
    #    — the home page has no module cards of its own to count, and asserting a typed word here would only
    #    move the drift one file over.
    if key == 'home':
        _msrc = io.open(f"{SRC}/modules.html", encoding='utf-8').read()
        _mn = len(re.findall(r'data-module="', _msrc))
        _MW = {16: 'Sixteen', 17: 'Seventeen', 18: 'Eighteen', 19: 'Nineteen', 20: 'Twenty'}
        t = swap(t, 'Sixteen modules, and every one is in every plan',
                 _MW.get(_mn, str(_mn)) + ' modules, and every one is in every plan',
                 key, 'home-module-count-derived')

    # ══ D1/D6 · PROGRESSIVE ENHANCEMENT — THE PROVING SUBSET ═══════════════════════════════════════
    #
    # ⚠️ FOUNDER RULING: BUILD, not remove. Two controls only in this pass — the home AUDIENCE LENSES and
    #    the product STAGE STEPPER — because if the converter route works the rest is repetition, and if it
    #    does not, that is learned on two controls rather than thirty-seven.
    #
    # ⚠️ INTENT RECOVERED, NOT INVENTED. The converter strips the prototype's handlers and keeps the markup,
    #    so the original behaviour survives in the MASTERS as `sc-camel-on-click` attributes:
    #    `lensOperator|lensOwner|lensGuest` and `stage0…stage7`. Those names state their own target, so the
    #    intent is unambiguous. ⚠️ THE MASTERS WERE READ FOR INTENT ONLY — never for live state, which is
    #    the wrong-layer read this project has made three times.
    #
    # ⚠️ WHY THE CONVERTER AND NOT HAND-WRITTEN MARKUP: a generated view is rewritten on every build, so a
    #    hand edit is silently undone; and 37 controls hand-wired across 19 pages is 37 things that drift.
    #    One transform here serves every page and cannot fall out of step with itself.
    if key == 'home':
        t = _unhide(t, 'data-lenscopy', key, 'pe/home-lens-copy-visible')
        t = _unhide(t, 'data-lensmock', key, 'pe/home-lens-mock-visible')
        t = _default_tab(t, 'data-lensbtn', 'operator', key, 'pe/home-lens-default-tab')
        # ⚠️⚠️ THE NINE-HOUR RAIL — ADDED 2026-09-01, AND THE OMISSION IS THE POINT. `_unhide` did not FAIL
        #    on these panels; it was never CALLED on them. Three call sites existed (lens copy, lens mock,
        #    stage panels) and `data-hourpanel` was not among them, so eight of the nine day-in-the-life
        #    panels shipped with the master's inline `display:none` and stayed invisible with or without
        #    JavaScript — permanently. ⚠️ THE ZERO-MATCH NEEDLE GUARD COULD NEVER HAVE CAUGHT THIS: it only
        #    records whether a transform that RAN matched anything, and this transform never ran. A guard
        #    over UNCALLED work has to compare the served output against the rule, which is what
        #    `MarketingProgressiveEnhancementTest` now does for every pane on the page rather than a named few.
        t = _unhide(t, 'data-hourpanel', key, 'pe/home-hour-panels-visible')

        # ⚠️ (c) THE "DAYLIGHT" DUSK TOGGLE — three buttons, one per audience lens, all inert.
        #    ⚠️ WHAT IT DID IN THE PROTOTYPE, read from the master for INTENT ONLY: `setDusk()` put a
        #      `data-dusk` attribute on the hero root, and a block of `[data-dusk] …` CSS repainted the
        #      mock in dark colours. ⚠️ IT CARRIED NO INFORMATION — same panels, same numbers, different
        #      palette — so wiring it would add script and state to change nothing a reader learns.
        #    ⚠️ AND ITS CSS IS ALREADY GONE: the converter strips the design's <style>, so `[data-dusk]`
        #      matches nothing in any generated view (checked: zero occurrences). Removing the buttons
        #      therefore leaves no orphaned rule — the residue shape that outlived the ROI estimator.
        #    ⚠️ `data-duskicon` and `data-dusklabel` sit INSIDE the button and go with it; "Daylight" and
        #      "Dusk" appear nowhere else on the page, so no sentence is left describing a missing control.
        _dusk = 0
        while 'data-duskbtn' in t:
            _before = len(t)
            t = cut_element(t, 'data-duskbtn', 'button', key, 'px-dusk-toggle-removed-%d' % (_dusk + 1))
            if len(t) == _before:
                break        # cut_element already logged the miss; never spin
            _dusk += 1
        # ⚠️ THE COUNT IS ASSERTED, not the presence. Three lenses, three buttons: a page that renders two
        #    or four is a page that changed shape, and the needle registry is where that must show up.
        _NEEDLE_LOG.append((key, 'px-dusk-toggle-count-was-3', _dusk == 3))


    if key == 'product':
        t = _unhide(t, 'data-stagepanel', key, 'pe/product-stage-panels-visible')
        t = _default_tab(t, 'data-stagebtn', '0', key, 'pe/product-stage-default-tab')

    # ══ D1/D6 · THE LOW-RISK GROUPS ═══════════════════════════════════════════════════════════════
    #
    # ⚠️ SCOPE CORRECTED AGAINST THE ROUTER, not inherited: the brief named four groups, but /faq and
    #    /glossary are NOT ROUTED (checked with `route:list`, same as /changelog, /docs and /resources).
    #    Wiring controls on unpublished pages would be work nobody can reach, so only the two published
    #    groups are built: the MODULE FILTERS and the STORY PRINT buttons.
    #
    # ⚠️ THE PRINT BUTTON HAD NO HOOK. Its only attribute is `data-noprint` — a PRINT-STYLESHEET marker
    #    meaning "hide me when printing", carried by other elements too, so binding to it would catch the
    #    wrong things. A dedicated hook is added here instead of overloading a presentational attribute.
    if key in ('story-lisbon', 'story-cyclades', 'story-rotterdam'):
        _pi = t.find('Print this story')
        _pb = t.rfind('<button', 0, _pi) if _pi != -1 else -1
        if _pb != -1:
            _end = t.index('>', _pb)
            t = t[:_pb + len('<button')] + ' data-printbtn=""' + t[_pb + len('<button'):_end + 1] + t[_end + 1:]
        _NEEDLE_LOG.append((key, 'pe/story-print-hook', _pb != -1))

    if key == 'modules':
        _mods = len(re.findall(r'data-module="', t))
        t = _count(t, r'(<span data-modcount=""[^>]*>)([^<]*)(</span>)',
                   str(_mods) + ' module' + ('' if _mods == 1 else 's'), key, 'mod-count-derived')
        _WORDS = {16: 'Sixteen', 17: 'Seventeen', 18: 'Eighteen', 19: 'Nineteen', 20: 'Twenty'}
        _NEEDLE_LOG.append((key, 'mod-count-matches-h1',
                            _mods > 0
                            and re.search(r'data-modcount=""[^>]*>(\d+) module', t) is not None
                            and int(re.search(r'data-modcount=""[^>]*>(\d+) module', t).group(1)) == _mods
                            and _mods in _WORDS and (_WORDS[_mods] + ' modules.') in t))

    # (5) ⚠️ GATE 4 — THE TRY-BOOKING SANDBOX (ruling D3 = (b)). The design's page simulates a booking on a
    #     FICTIONAL property and ends in a FABRICATED confirmation email. Every honesty transform lives
    #     HERE, in the converter, because Gate 3b proved twice that a post-generation patch is undone by the
    #     next generation — silently, and caught only because guards existed.
    if key == 'try-booking':
        # ⚠️ THE INVENTED PROPERTY → the REAL demo property, resolved at render time from the database.
        #    Never a parallel fiction: every name and number on the page is true of something that exists.
        _TB = "\\App\\Support\\Marketing\\TryBookingSandbox"
        t = t.replace('Villa Azure', "{{ " + _TB + "::data()['propertyName'] ?? 'the demo property' }}")
        for _p in ('Paros, Greece · sleeps 6', 'Paros, Greece &middot; sleeps 6'):
            t = t.replace(_p, "{{ " + _TB + "::data()['place'] ?? '' }}")

        # ⚠️ CONSTRAINT 1 — the META PROMISED A REAL BOOKING. "Make a real booking on a demo property" was
        #    false: nothing was booked. Rewritten here so a regeneration cannot restore the promise.
        #    ⚠️ Worded to avoid even the SUBSTRING "a real booking": "priced by the live engine" says the
        #    same true thing without a phrase a reader (or a guard) could mistake for the old promise.
        #    ⚠️ GATE 4b — the picker is BACK (read-only), so the meta says so. The promise it must still
        #    never make is a BOOKING; picking dates and being priced is what actually happens.
        meta['description'] = ('Pick your dates on our real demo property and see the price the live engine '
                               'quotes — and the real confirmation email your guest would receive. ⚠️ A '
                               'preview only: nothing is reserved, charged or sent.')
        meta['title'] = 'Try the booking engine — a Vyrobel direct-booking preview'

        # ⚠️ CONSTRAINT 1, THE PAGE'S OWN COPY — swept, not just the meta. Two lines promised a booking the
        #    page cannot make: the h1 ("Make a booking, the way your guests would") and the hero's claim to
        #    be "the real direct booking engine running against a demo property" — it was a client-side
        #    simulation touching no engine at all. Replaced with what the page actually does.
        t = t.replace('Make a booking, the way your guests would',
                      'See what a direct booking looks like')
        #    ⚠️ THE HERO AND THE CONTROL MUST AGREE — IN BOTH DIRECTIONS. Gate 4 removed the picker and
        #    found this line still saying "Pick your dates, watch the rate build itself": an invitation to
        #    use a control that wasn't there, caught by READING THE RENDER rather than by any grep.
        #    ⚠️ Gate 4b restores the picker, so "Pick your dates" becomes TRUE again and is restored with
        #    it — and MarketingTryBookingTest now pins the biconditional, so neither half can move alone.
        #    ⚠️ What is NOT restored is "watch the rate build itself" (the simulation's animation, which
        #    does not exist) or "the commission that doesn't get charged" (the design's fabricated
        #    "an OTA would have taken €X" figure — no OTA rate is modelled for a direct booking, #39).
        _hero_new = ('Pick your dates on our real demo property and the live engine prices them, from the '
                     'same rate calendar it charges from — then see the confirmation email a guest '
                     'actually receives. Nothing is reserved and nothing is charged.')
        t = t.replace(
            "This is the real direct booking engine running against a demo property. Pick your dates, "
            "watch the rate build itself, and see the commission that doesn't get charged.", _hero_new)
        t = t.replace(
            'This is the real direct booking engine running against a demo property. Pick your dates, '
            'watch the rate build itself, and see the commission that doesn&rsquo;t get charged.', _hero_new)

        # ⚠️ THE FABRICATED CONFIRMATION EMAIL → the REAL BookingConfirmationMail, links defused (#33).
        #    The block is replaced wholesale rather than edited: an invented email cannot be "corrected".
        _i = t.find("lands in the guest")
        if _i != -1:
            _s = t.rfind('<section', 0, _i)
            _e = t.find('</section>', _i)
            if _s != -1 and _e != -1:
                t = t[:_s] + '<x-marketing.sandbox-confirmation />' + t[_e + 10:]

    # ══ (7) ⚠️⚠️ GATE 7 — THE COMPANY PAGES. Every correction below replaces a claim the doc-truth audit
    #        rated FALSE or PARTIAL with the TRUE statement in its place. ⚠️ CORRECTED, NEVER SOFTENED:
    #        swapping a false specific for "we take security seriously" leaves the reader knowing LESS than
    #        before and is a WORSE page, not a safer one. Where the truth is a limitation, the limitation is
    #        what gets printed.
    #        ⚠️ Every needle here was COPIED OUT OF THE SOURCE FILE, never retyped from a rendered text dump
    #        — retyping is what produced a needle reading "Different scales, the same three complaints" for
    #        a page that says "Different scale, same three complaints". And every one goes through swap(),
    #        which records whether it actually fired; see the matcher above.

    if key == 'security':
        for _tag, _b, _a in (
            ('2fa-enforcement',
             'Available on every account and enforceable for staff who touch money.',
             'Available on operator, owner and platform-admin accounts, and mandatory for platform admins. '
             '\u26a0\ufe0f It is opt-in per person: there is no way for an administrator to require it of a '
             'role, including roles that take payments or change payout details. Guest accounts have no 2FA.'),
            ('sessions',
             'Recovery codes, trusted devices and a visible list of active sessions you can end remotely.',
             'Recovery codes, trusted devices, and a real list of your active sessions \u2014 you can end all '
             'other sessions at once, after re-entering your password.'),
            # ⚠️ THE HOUSEKEEPER CLAUSE IS A PRODUCT DEFECT, NOT A COPY DEFECT. `/app/dashboard` carries no
            #    permission gate, so its "Revenue today" tile renders for every back-office role including
            #    Housekeeping. The sentence is cut back to the two clauses that ARE enforced; the defect is
            #    reported to the founder rather than papered over here.
            ('roles-housekeeper',
             "A housekeeper sees rooms, not revenue. An owner sees their own units, never another owner's. "
             'An accountant sees the ledger, not the guest list.',
             'An owner sees their own units, never another owner\u2019s \u2014 a row-level scope that fails '
             'closed rather than falling open, and an owner account cannot leave the owner portal. An '
             'accountant sees the ledger, not the guest list.'),
            ('audit-permission-grants',
             'Refunds, rate overrides, payout changes and permission grants are logged with who did what and '
             'when \u2014 a record you can hand to an owner or an auditor.',
             'Refunds, payments, rate-plan changes and payout transitions are logged with who did what and '
             'when \u2014 a record you can hand to an owner or an auditor. \u26a0\ufe0f Three things are not '
             'in it yet: per-unit rate overrides, owner payout preferences, and granting a role to a '
             'person.'),
            ('eu-hosting-heading', 'Hosted in the EU', 'Encryption and isolation'),
            ('eu-hosting-body',
             "Data stays in European infrastructure, encrypted in transit and at rest, with tenant isolation "
             "so one operator's data can never surface in another's.",
             'Sensitive fields are encrypted with the application key \u2014 guest email and phone, owner tax '
             'and bank details, identity documents, two-factor secrets. The application forces HTTPS and '
             'marks its cookies secure. Tenant isolation is structural: a global scope on every read, and a '
             'guard that refuses a write with no tenant context. \u26a0\ufe0f Hosting is not chosen yet, so '
             'there is no data-residency commitment to give you \u2014 we would rather publish one before '
             'launch than imply it now.'),
            ('id-retention',
             'We collect what a stay requires and no more. Identity documents are captured for check-in, held '
             'only as long as the law requires, then removed.',
             'We collect what a stay requires and no more. Identity documents are encrypted and hard-deleted '
             '30 days after checkout by a scheduled job \u2014 a fixed default applied everywhere, not a '
             'per-jurisdiction legal period.'),
            ('anonymisation-trigger',
             'After the retention window, personal details are anonymised while the financial record survives '
             '\u2014 so your reports stay accurate and your obligations are met.',
             'Erasure is real and thorough: personal details are cleared while the financial record survives, '
             'so your reports stay accurate. \u26a0\ufe0f It runs on request \u2014 yours or the guest\u2019s '
             '\u2014 not on a timer. There is no automatic retention sweep yet.'),
            ('sar-realms',
             'Subject access requests, corrections and erasure are handled from inside the app rather than '
             'through a support queue.',
             'Corrections are self-service in every realm, and a guest can delete their own account from the '
             'guest portal. \u26a0\ufe0f Owners and operators ask us to erase, and a guest cannot yet export '
             'their own data \u2014 two gaps we are closing.'),
            ('account-deletion',
             "Export bookings, guests, statements, payouts and documents at any time in open formats. Delete "
             "an account and the data goes with it, on a schedule we'll tell you in advance.",
             'Export in open formats \u2014 CSV, PDF and XLSX \u2014 with no support request. Cancelling makes '
             'your workspace read-only at the end of the period you paid for. \u26a0\ufe0f Deleting a whole '
             'account, and the schedule that would follow it, is not built: today you ask us and we do it by '
             'hand.'),
            # ⚠️ THE DEFECT SITS BESIDE THE ONE YOU JUST FIXED (§N.13.1). The section's copy was corrected to
            #    say the DPA and sub-processor list are not ready \u2014 and the BUTTON under it still read
            #    "Request the documents", offering to send exactly what the sentence above had just said did
            #    not exist. Found by looking at the rendered page, not by any assertion.
            ('security-cta-button', '>Request the documents', '>Ask us where they stand'),
            ('pre-launch-framing',
             'Hospitality software handles identity documents, card payments and home addresses. Vyrobel is '
             'built in the EU, under GDPR, with data minimisation as an architectural decision rather than a '
             'policy page.',
             'Hospitality software handles identity documents, card payments and home addresses. Vyrobel '
             'treats data minimisation as an architectural decision rather than a policy page. '
             '\u26a0\ufe0f Vyrobel has not launched yet. This page describes what is built and tested, and '
             'says plainly where something is not done.'),
        ):
            t = swap(t, _b, _a, key, _tag)
        meta['title'] = 'Security at Vyrobel \u2014 what is built, and what is not yet'
        meta['description'] = ('How Vyrobel protects operator and guest data: encrypted PII, structural '
                               'tenant isolation, an audit trail on money, and a plain account of the parts '
                               'that are not built yet.')

    if key == 'integrations':
        for _tag, _b, _a in (
            ('booking-com',
             'Two-way availability and rate sync, reservations pulled into the same table as your direct '
             'bookings, with connection health visible on the channels screen.',
             '\u26a0\ufe0f Not connectable yet. The sync pipeline, the rate push and the inbound-booking '
             'importer are built and tested against a simulated channel; a real connection needs a Channex '
             'partner account we have not set up.'),
            ('airbnb',
             'Listings stay in step with your calendar and rate plans, and guest messages land in the same '
             'inbox your team already watches.',
             '\u26a0\ufe0f Not connectable yet, and Airbnb guest messaging is not built \u2014 the inbox '
             'carries in-app and logged conversations only.'),
            ('expedia-vrbo',
             "Connected through the same channel layer, so adding distribution doesn't add another calendar "
             'to reconcile at the end of the week.',
             '\u26a0\ufe0f Not connectable yet. Expedia is in our channel catalogue; Vrbo is not modelled '
             'at all.'),
            ('synced-pill', '>Synced<', '>Not connected<'),
            # ⚠️ THESE THREE WERE WRITTEN, THEN LOST IN A REFACTOR, AND ONLY THE BROWSER FOUND THEM. When
            #    this block was rewritten onto swap() they were dropped, and nothing complained: the needle
            #    report flags a needle that MATCHES NOTHING, and it cannot flag a correction that was never
            #    written. The page shipped with its hero still asserting a live channel sync while the status
            #    pills beneath it read "Not connected" — a contradiction visible in one screenshot and
            #    invisible to every static check.
            ('hero-channel-ready',
             # ⚠️ THE TAIL "No overbooking, no midnight calendar maths, no third tool" IS NOT IN THE SOURCE.
             #    I typed it from memory of a truncated text dump — the third time in this gate that a needle
             #    was transcribed rather than copied, and the third time it silently matched nothing. The
             #    sentence really ends at "everywhere."
             'Vyrobel is channel-ready: your OTA listings draw on the same availability as your direct '
             'storefront, so a booking anywhere closes the night everywhere.',
             'Vyrobel keeps one availability pool with a single write path, so it is built to drive channels '
             'without the calendars ever drifting apart. \u26a0\ufe0f No OTA channel can be connected yet: '
             'what is live today is the direct storefront, Stripe payments, accounting exports and scoped '
             'third-party access.'),
            ('one-pool-present-tense',
             'Every channel reads the same inventory. When a guest books direct, the night closes on '
             'Booking.com and Airbnb too. When maintenance takes a unit out of service, it disappears from '
             'all of them at once.',
             'Every channel would read the same inventory: there is one availability table, one write path, '
             'and taking a unit out of service removes it from that pool immediately. \u26a0\ufe0f Pushing '
             'that state out to Booking.com or Airbnb is built, but not yet live.'),
            ('closing-cta',
             "Tell us which ones you're on and we'll confirm what connecting them looks like.",
             'Tell us which ones you\u2019re on. We will tell you honestly where that channel stands rather '
             'than promise a connection date we cannot keep.'),
            ('exports',
             'Statements, VAT returns, payout histories and booking ledgers export as CSV and PDF in a shape '
             'your accountant can actually import.',
             'Statements and booking ledgers export as CSV and PDF; VAT returns and payout histories export '
             'as CSV. These are files you hand over \u2014 there is no accounting-package sync.'),
        ):
            t = swap(t, _b, _a, key, _tag)
        meta['title'] = 'Integrations \u2014 what connects to Vyrobel today | Vyrobel'
        meta['description'] = ('What Vyrobel connects to today: Stripe payments, accounting exports in CSV '
                               'and PDF, and scoped third-party access over OAuth. OTA channel sync is built '
                               'but not yet connectable.')

    if key == 'about':
        # ⚠️⚠️ THE TEAM BLOCK IS DELETED, NOT RENAMED. Four people are presented as staff. Two of them
        #     ("Elena Kastellis", "Joris van Dijk") are already in the honesty ledger as invented,
        #     unconsented CUSTOMER testimonial authors; a third ("Amara Reyes") is a recombination of two
        #     more ("Amara Mensah" + "Marco Reyes"). Vyrobel is one person. ⚠️ There is no correction to a
        #     fabricated team — only its absence. This is also why the D13 rename is scoped away from this
        #     page above: renaming an invented co-founder invents a different one.
        _i = t.find('run the shift')
        _removed = False
        if _i >= 0:
            _s = t.rfind('<section', 0, _i)
            _e = t.find('</section>', _i)
            if _s >= 0 and _e >= 0:
                t = t[:_s] + t[_e + 10:]
                _removed = True
        _NEEDLE_LOG.append((key, 'team-block-removed', _removed))

        for _tag, _b, _a in (
            # ⚠️ D1'S THREE INVENTED CUSTOMERS, RETURNING BY ANOTHER DOOR — the same Cyclades manager,
            #    Rotterdam family and Lisbon hotel whose case-study pages were refused and rebuilt as
            #    labelled illustrative scenarios. Here they are asserted as businesses we watched.
            ('d1-customers',
             'A manager with forty units in the Cyclades. A family running eighteen apartments in Rotterdam. '
             'A boutique hotel in Lisbon. Different scale, same three complaints:',
             'A villa manager, a family running a handful of apartments, a small independent hotel. '
             'Different scale, same three complaints:'),
            ('industry-origin',
             'Vyrobel started with a simple observation from inside the industry: the best-run properties '
             'were spending their margin on distribution',
             'Vyrobel started from a simple observation: the best-run properties were spending their margin '
             'on distribution'),
        ):
            t = swap(t, _b, _a, key, _tag)
        meta['title'] = 'About Vyrobel \u2014 why it exists and what it is built to do'
        meta['description'] = ('Why Vyrobel exists, what it is built to do for operators and owners, and the '
                               'principles it is built on.')

    if key == 'contact':
        # ⚠️ THE DEMO BOOKER IS REMOVED WHOLESALE (D5, still open). A day-and-slot picker ending in "a
        #    calendar invite is on its way" would book nothing: there is no scheduling package, no meeting
        #    provider and no invite generator anywhere in the product. A picker that names a time and books
        #    nothing is the #39 shape at its worst.
        _i = t.find('Choose a day')
        _removed = False
        if _i >= 0:
            _s = t.rfind('<section', 0, _i)
            _e = t.find('</section>', _i)
            if _s >= 0 and _e >= 0:
                t = t[:_s] + t[_e + 10:]
                _removed = True
        _NEEDLE_LOG.append((key, 'demo-booker-removed', _removed))

        # ⚠️ THE FORM'S CONTENTS BECOME REAL. The design's two states are `sc-if` elements bound to React
        #    (`sent` / `notSent`); the card's own chrome is kept verbatim and only its inside is replaced, so
        #    the page still looks exactly as drawn. ⚠️ The component is a real <form method="POST"> against a
        #    real route — see resources/views/components/marketing/contact-form.blade.php.
        t = cut_element(t, '@{{ sent }}', 'sc-if', key, 'form-sent-state',
                        '<x-marketing.contact-form />')
        t = cut_element(t, '@{{ notSent }}', 'sc-if', key, 'form-fields-state')

        # ⚠️ THE DEFECT SITS BESIDE THE ONE YOU JUST FIXED (§N.13.1). Having removed the demo booker, the
        #    RIGHT RAIL still carried a "Book a demo instead \u2014 thirty minutes, screen shared \u2026 Pick a
        #    time" card pointing at the picker that no longer exists. Removing a feature and leaving its
        #    advertisement is how a dead control survives a cleanup.
        # ⚠️ levels=1 — the OUTER card, not the inner text block. Cutting the inner one left a 200px dark
        #    empty panel at the top of the rail; see cut_element's docstring.
        t = cut_element(t, 'Book a demo instead', 'div', key, 'rail-demo-card', levels=1)


        # ⚠️⚠️ REMOVING THE DEMO SECTION ORPHANED `#demo` FIFTEEN TIMES ACROSS ELEVEN FILES. Every "Book a
        #    demo", "Book a walkthrough", "Book a migration call" and "Talk to us first" on the site is
        #    `<x-marketing.nav-link key="contact" fragment="#demo">`, and the id it targets lived on the
        #    section just deleted. ⚠️ THEY WERE HARMLESS UNTIL THIS GATE: with `/contact` unregistered they
        #    all rendered as marked non-links. PUBLISHING THE PAGE IS WHAT ACTIVATED THEM — the same shape as
        #    "adding an input can weaponize a dormant display", one level up: publishing a page weaponizes
        #    every dormant link into it.
        #    ⚠️ The fix is an anchor, not fifteen edits. `#demo` means "I want a demo", and the honest
        #    destination for that is now the contact form — which offers "A demo" as a topic. Nothing on the
        #    other end claims to book a slot, so the link keeps its meaning and loses its lie.
        _anchor = '<span id="demo" aria-hidden="true"></span>'
        _i = t.find('<x-marketing.contact-form />')
        _NEEDLE_LOG.append((key, 'demo-anchor-rehomed', _i >= 0))
        if _i >= 0:
            t = t[:_i] + _anchor + t[_i:]

        # ⚠️ NOT CUT SEPARATELY, AND THE REPORT IS WHY. The design's "Quoting the GROWTH plan you selected
        #    on Pricing" banner is a real defect \u2014 there is no Growth plan; the billing engine runs two
        #    FLAT plans, Essential and Pro (config/billing.php:63,76) \u2014 but it sits INSIDE the form
        #    region replaced above, so a second cut for it matched nothing and was reported as a no-op. The
        #    component owns that region's content now; a redundant cut would advertise coverage it no longer
        #    provides.

        # ⚠️ FOUR NEEDLES WERE DELETED FROM THIS LIST, AND THE GUARD IS WHY. They corrected the design's two
        #    "we reply within one working day" promises and its two ledger-name placeholders — and once the
        #    `sc-if` surgery above replaced the whole form region, all four had nothing left to match. The
        #    needle report flagged them as silent no-ops on the very next run. ⚠️ Left in place they would
        #    have read as coverage that no longer existed: a list of honesty fixes, none of which ran. The
        #    copy they governed now lives in the contact-form component, where it is written correctly once.
        for _tag, _b, _a in (
            ('quote-promise',
             "A real person reads this. Send us your unit count and where you operate, and we'll come back "
             "with a quote or an honest answer about whether we're the right fit.",
             'A real person reads this. Send us your unit count and where you operate, and you\u2019ll get an '
             'honest answer about whether Vyrobel fits \u2014 including when it doesn\u2019t.'),
            ('support-address', 'support@vyrobel.com', 'sales@vyrobel.com'),
            ('support-hours', 'Existing customers, 7 days a week', 'Anything else'),
            ('offices', 'Lisbon &amp; Amsterdam', 'Remote, in the EU'),
            ('in-app-assistant',
             'Support lives inside the app \u2014 the assistant answers most questions instantly and '
             'escalates the rest to a ticket.',
             'Signed-in operators and owners have a help centre inside the app, and owners can ask a question '
             'from it.'),
            # ⚠️ THE NEEDLE REPORT CAUGHT THIS ONE, AND NOTHING ELSE COULD HAVE. Written as
            #    `placeholder="Elena Kastellis"`, it matched nothing — because transform (1), the D13 rename,
            #    runs EARLIER IN THIS SAME FUNCTION and had already turned the placeholder into
            #    `placeholder="Chara Melis"`. ⚠️ The needle was correct about the source file and wrong about
            #    the pipeline: by the time it ran, the text it was looking for no longer existed. A
            #    transform's input is not the file — it is whatever the transforms above it have left.
        ):
            t = swap(t, _b, _a, key, _tag)
        meta['title'] = 'Contact Vyrobel \u2014 tell us how your properties run'
        meta['description'] = ('Write to Vyrobel about pricing, migrating from another PMS, or whether it '
                               'fits how you operate. A real person reads every message.')

    # (6) ⚠️ GATE 6 — THE DPA CLAIM, REMOVED FOR EVERY PAGE (founder ruling, 2026-08-22).
    #     Gate 5's audit asked whether the data processing agreement two pages assert actually exists.
    #     ⚠️ IT DOES NOT. The founder ruled: remove the claim. It appears only on pages that are still
    #     unbuilt, so there is nothing shipped to correct — which is exactly why the removal belongs HERE,
    #     in the generator: a page built later would otherwise inherit the sentence, and the fix would have
    #     to be remembered rather than enforced (the generation lesson, twice-learned in Gate 3b).
    #     ⚠️ WHAT SURVIVES IS TRUE: Vyrobel IS the processor and DOES act on the operator's instruction.
    #     Only the assertion that a signed agreement sets that out is removed.
    #     ⚠️ AND REMOVING THE CLAIM DOES NOT REMOVE THE DUTY — Article 28 requires the agreement to exist
    #     before a customer's guest data is processed. Recorded as a go-live item in DECISIONS.md, not
    #     drafted here: that is legal work, not a build step.
    #     ⚠️⚠️ GATE 7 — THESE FOUR NEEDLES MATCHED NONE OF THE PAGES THEY WERE WRITTEN TO PROTECT. The
    #     comment above promises that "a page built later would otherwise inherit the sentence", and Gate 7
    #     built exactly those pages: Privacy, Security and Terms carry SIX DPA mentions between them, and the
    #     needle set scored ZERO on all three. The transform looked like forward protection and was in fact a
    #     record of the four phrasings one reader had seen. ⚠️ A GUARD WRITTEN FROM A SURVEY IS BOUNDED BY
    #     THAT SURVEY — so the needles below were derived by extracting every DPA sentence from all ten Gate
    #     7 masters, not by predicting them.
    #
    #     ⚠️ AND ONE MENTION IS DELIBERATELY LEFT ALONE, which is why this can never become a blanket
    #     "remove every DPA mention": the Privacy draft's sub-processor table says each entry "needs a named
    #     entity, its processing location, a SIGNED DATA PROCESSING AGREEMENT, and a transfer mechanism".
    #     That does not assert a DPA exists — it tells the drafting lawyer what each entry must have, which
    #     is the single most useful line on the page. The distinction the needles encode is ASSERTING THE
    #     AGREEMENT EXISTS versus SPECIFYING THAT ONE IS REQUIRED.
    for _dpa_before, _dpa_after in (
        ('on your instruction, under a data processing agreement.', 'on your instruction.'),
        ('on your instruction, and our data processing agreement sets that out.', 'on your instruction.'),
        ('under a data processing agreement', ''),
        ('our data processing agreement sets that out', ''),
        # ⚠️ Gate 7, security.html — "we're the processor. That means a data processing agreement,
        #    documented sub-processors, and no use of your guest data…". Two of the three named artefacts do
        #    not exist; the third is a conduct promise. Replaced with the part that is true today.
        # ⚠️ THE REPLACEMENT AVOIDS THE PHRASE "data processing agreement" DELIBERATELY, and not to game a
        #    guard. CI Gate 5 forbids that phrase on any marketing view, because the founder ruled the CLAIM
        #    removed \u2014 and a substring sweep cannot tell an assertion from a denial, so an honest
        #    "we do not have one yet" trips it exactly like a false "we have one".
        #    ⚠️ The gate was left UNTOUCHED rather than widened: weakening a rule that protects a founder
        #    ruling, in order to publish a sentence, is the wrong direction. The tension is real and is
        #    surfaced as a founder decision in the Gate 7 report; meanwhile the page says the same thing in
        #    plainer words, which loses nothing a reader needs.
        ('That means a data processing agreement, documented sub-processors, and no use of your guest data '
         'for anything but running your business.',
         'That means we act on your instruction, and we never use your guests\u2019 data for anything but '
         'running your business. \u26a0\ufe0f The paperwork that should come with that \u2014 the written '
         'contract governing how we handle your guests\u2019 data, and the list of every company we rely on '
         'to run the service \u2014 is not written yet. We would rather tell you that than let you assume '
         'otherwise.'),
        # ⚠️ Gate 7, security.html — the closing CTA offers to SEND three documents. None exists.
        ("We'll send the data processing agreement, sub-processor list and answers to your security "
         'questionnaire.',
         'Ask us what exists today. Right now the honest answer is: this page, and the engineers\u2019 '
         'account of what is built. The formal documents a procurement team will ask for are not written, '
         'and we will tell you which are ready rather than send you one that is not.'),
        ('Need our DPA or a security review?', 'Planning a security review?'),
        # ⚠️ Gate 7, terms.html — asserts a DPA already accompanies the terms.
        ('We process them to provide the service and under the data processing agreement that accompanies '
         'these terms.',
         'We process them to provide the service. \u26a0\ufe0f The data processing agreement that must '
         'accompany these terms under Article 28 is still to be drafted \u2014 see the open items below.'),
    ):
        t = t.replace(_dpa_before, _dpa_after)

    # ⚠️ palette hex → the canonical tokens (never a second palette — D6/E4/§N.8f)
    for h, v in HEX.items():
        t = re.sub(re.escape(h), v, t, flags=re.I)

    # any leftover handlebars would collide with Blade's — neutralise, and report.
    t = re.sub(r'\n\s*\n\s*\n+', '\n\n', t)

    # ⚠️⚠️ PLACED HERE — AT THE VERY END — DELIBERATELY, AND THE FIRST DRAFT WAS PLACED WRONG.
    #    The recovered spec scripts edited the FINISHED Blade file, so every needle in them is written
    #    against the FINAL shape of the markup. Registered mid-pipeline (before Gate 7), four of them
    #    matched and two did NOT: at that point the breadcrumb still reads `color: #2B2320`, because the
    #    palette-literal → design-token binding a few lines above has not run yet. The zero-match check
    #    caught it immediately and named both transforms.
    #    ⚠️ THE FIX WAS TO MOVE THE BLOCK, NOT TO REWRITE THE NEEDLES. Rewriting them to match the raw hex
    #    would have silently forked the port away from the record it is supposed to implement — and the
    #    same applies to the whitespace collapse just above, which the scripts also ran after. Running last
    #    means every SPEC needle is used exactly as the script that performed the rewrite wrote it.

    # ══ (6) ⚠️⚠️ GATE 3b, RULING (A) — THE FOUR EXAMPLE-SCENARIOS PAGES ══════════════════════════
    #
    # ⚠️⚠️ WHY THIS BLOCK EXISTS AT ALL. These four pages carried ruling A's honesty rewrites in their
    #    GENERATED OUTPUT ONLY — applied by two one-off scripts that edited the committed Blade. So the
    #    converter still emitted the invented, unconsented customer identities (Kastellis Villas · Van Dijk
    #    Stays · Hotel Alfama Reyes · Havenzicht · Casa Mirante), and REGENERATING ANY OF THESE PAGES WOULD
    #    HAVE REPUBLISHED THEM ONTO A LIVE SITE. That is §N.31.4 again, at the largest scale it has occurred:
    #    the /switching CTA was one dead link, this is five fabricated businesses across four pages.
    #
    # ⚠️⚠️ PROVENANCE IS MARKED PER TRANSFORM, AND IT IS NOT A FORMALITY. A green byte-identical diff means
    #    two DIFFERENT things here:
    #      · SPEC  — implements `tools/marketing/spec/rewrite.py` / `rewrite_lisbon.py`, the recovered
    #                scripts that actually performed the rewrite. A green diff means FAITHFUL TO THE RECORD.
    #      · RECON — reconstructed from the diff between generated and committed output, because NO spec
    #                survives for `case-studies` or `story-rotterdam`. A green diff means only REPRODUCES
    #                THE CURRENT FILE — which a lucky positional match would also satisfy. Hence every RECON
    #                needle below anchors on CONTENT (the invented name, the fabricated claim, the block's
    #                own words) and never on position, index or sibling count.
    #
    # ⚠️ The `>Illustrative story<` register fix and the two `Van Dijk Stays` cards are NOT collapsed into a
    #    single shared transform despite appearing on several pages (§N.8f): the recovered spec assigns
    #    DIFFERENT replacement text per page (cyclades says "Eighteen serviced apartments", lisbon says
    #    "An 18-apartment operator"), so one transform cannot express them. Porting, not improving (#9).

    if key in ('case-studies', 'story-cyclades', 'story-lisbon', 'story-rotterdam'):
        # ⚠️ ONE definition of the label, shared by all four pages — §N.8f. Verified byte-identical to the
        #    string both recovered spec scripts insert and to all four committed pages.
        _LABEL = (
            '<p style="margin: 14px 0 0; padding: 11px 15px; border-radius: 13px; '
            'background: rgba(217,102,46,.12); border: 1px solid rgba(217,102,46,.26); '
            'font-size: 13.5px; line-height: 1.55; color: var(--vy-ink); max-width: 640px;" '
            'data-scenario-label>\u26a0\ufe0f <strong>An illustrative scenario, not a customer.</strong> '
            'Vyrobel is new and has no published customer stories yet. This describes how the product is used '
            'by an operation of this shape, composed from onboarding conversations. Nothing here is a claim '
            'about a real business, and no figure is a measured result.</p>'
        )

        # ⚠️ THE LABEL IS EMITTED AS A REPLACEMENT, NEVER AN INSERT. The recovered scripts appended it into
        #    a file they then overwrote, which DOUBLES on a second run — the exact non-idempotence the
        #    /switching lesson warns about. Re-emitting the matched `<p …>` opening tag after the label
        #    makes the transform a pure substitution, so the pipeline is idempotent by construction.

        # ⚠️ SPEC — THE RESIDUE OF AN ELEMENT SOMETHING ELSE ALREADY REMOVED. `rewrite.py` deleted the
        #    whole LINE its blockquote sat on. In today's pipeline transform (2) has already stripped the
        #    quote ELEMENT much earlier — but only the element, so the line survives as pure indentation and
        #    the page no longer reproduces. The spec's rule is therefore still needed, and still its rule:
        #    delete the line, not just the tag.
        #    ⚠️ THERE IS NO CONTENT TO ANCHOR ON HERE, and that is not a gap in the reconstruction — it is
        #    the nature of the hunk: the content is precisely what was removed. The anchor is a PROPERTY of
        #    the line (it holds nothing but whitespace), never its position, so it survives any master
        #    change that moves the block around.
        _before_ws = t.split('\n')
        _kept = [l for l in _before_ws if l.strip() != '' or l == '']
        # ⚠️ PROVENANCE IS PER PAGE HERE, and marking it SPEC everywhere would have overstated the
        #    record: the RULE is the spec's, but only two of the four pages have a spec to take it
        #    from. On the other two, applying it is an INFERENCE from their diff like every other
        #    RECON transform, and the count must say so.
        _res_prov = 'SPEC' if key in ('story-cyclades', 'story-lisbon') else 'RECON'
        _NEEDLE_LOG.append((key, _res_prov + '/strip-removed-element-residue',
                            len(_kept) != len(_before_ws)))
        t = '\n'.join(_kept)

    if key == 'story-cyclades':
        # ── SPEC — every needle below is `tools/marketing/spec/rewrite.py`, verbatim ──────────────────
        meta['title'] = 'A 42-villa portfolio across four islands | Vyrobel example scenario'
        meta['description'] = ('An illustrative scenario: how a 42-villa manager across four islands '
                               'replaced a channel manager, a spreadsheet per owner and a WhatsApp group '
                               'with one platform \u2014 and stopped the month-end phone calls.')
        for _tag, _b, _a in (
            ('SPEC/cyc-printhead',
             '>Customer story \u00b7 42 villas, 31 owners \u00b7 Cyclades<',
             '>Example scenario \u00b7 42 villas, 31 owners \u00b7 four islands<'),
            ('SPEC/cyc-letterfoot',
             '>Kastellis Villas \u00b7 Illustrative story, composed from onboarding conversations \u00b7 vyrobel.com/case-studies<',
             '>Illustrative scenario, composed from onboarding conversations \u00b7 vyrobel.com/case-studies<'),
            ('SPEC/cyc-crumb-collection',
             'font-weight: 500;">Customer stories</x-marketing.nav-link>',
             'font-weight: 500;">Example scenarios</x-marketing.nav-link>'),
            ('SPEC/cyc-crumb-current',
             '<span aria-current="page" style="color: var(--vy-ink); font-weight: 500;">Kastellis Villas</span>',
             '<span aria-current="page" style="color: var(--vy-ink); font-weight: 500;">Villa portfolio</span>'),
            ('SPEC/cyc-eyebrow',
             '>Villas \u00b7 Cyclades, Greece<', '>Villas \u00b7 four islands<'),
            ('SPEC/cyc-label',
             'one screen</h1>\n          <p style="margin: 20px 0 0;',
             'one screen</h1>\n          ' + _LABEL + '\n          <p style="margin: 20px 0 0;'),
            ('SPEC/cyc-standfirst',
             '>Kastellis Villas manages holiday houses across four islands',
             '>This operation manages holiday houses across four islands'),
            ('SPEC/cyc-nextcard-name',
             'line-height: 1.3;">Van Dijk Stays</span>',
             'line-height: 1.3;">Eighteen serviced apartments</span>'),
            ('SPEC/cyc-nextcard-sub',
             'margin-top: 3px;">18 apartments \u00b7 Rotterdam</span>',
             'margin-top: 3px;">A two-person family business</span>'),
            ('SPEC/cyc-register',
             'margin-bottom: 10px;">Illustrative story</div>',
             'margin-bottom: 10px;">Illustrative scenario</div>'),
        ):
            t = swap(t, _b, _a, key, _tag)

        # ⚠️⚠️ RECON — A HUNK THE SPEC DOES NOT COVER, ON A PAGE THE SPEC DOES. `rewrite.py` contains
        #    nothing for this, yet the committed page has it removed, so some earlier hand or an older
        #    converter did it and left no record. ⚠️ THE RECOVERED SPEC IS THEREFORE INCOMPLETE EVEN FOR
        #    THE PAGES IT COVERS — reported, because it changes what a green diff on this page means.
        #    ⚠️ WHAT IT IS: condition 3, no quantified ACHIEVED outcomes. "4 days → 1 afternoon" is a
        #    measured result claimed for a business that does not exist. Its sibling card survives in the
        #    committed page ("Direct share up" — directional, unquantified), so the rewrite was surgical
        #    and this transform is too: it must NOT take the pair.
        #    ⚠️ ANCHORED ON THE CLAIM ITSELF, never on the card's position among its siblings, so it cuts
        #    the block CONTAINING the fabricated figure wherever the master moves it.
        _cyc_claim = '4 days \u2192 1 afternoon'
        _m = re.search(r'<div data-printcallout[^>]*>' + re.escape(_cyc_claim)
                       + r'</div>\s*<div[^>]*>[^<]*</div>', t)
        _NEEDLE_LOG.append((key, 'RECON/cyc-cut-quantified-outcome', _m is not None))
        if _m:
            t = t[:_m.start()] + t[_m.end():]

    if key == 'story-lisbon':
        # ── SPEC — every needle below is `tools/marketing/spec/rewrite_lisbon.py`, verbatim ───────────
        meta['title'] = '96 keys, one city hotel \u2014 an example scenario | Vyrobel'
        meta['description'] = ('A legacy PMS the front desk tolerated and nobody else could open. An '
                               'example scenario: how a 96-key city hotel gets housekeeping, maintenance '
                               'and the front desk onto one system.')
        for _tag, _b, _a in (
            ('SPEC/lis-printhead',
             '>Customer story \u00b7 96 keys \u00b7 Lisbon</span>',
             '>Example scenario \u00b7 96 keys \u00b7 city hotel</span>'),
            ('SPEC/lis-letterfoot',
             '>Hotel Alfama Reyes \u00b7 Illustrative story, composed from onboarding conversations \u00b7 vyrobel.com/case-studies</div>',
             '>Illustrative scenario, composed from onboarding conversations \u00b7 vyrobel.com</div>'),
            ('SPEC/lis-crumb-collection',
             'font-weight: 500;">Customer stories</x-marketing.nav-link>',
             'font-weight: 500;">Example scenarios</x-marketing.nav-link>'),
            ('SPEC/lis-crumb-current',
             '<span aria-current="page" style="color: var(--vy-ink); font-weight: 500;">Hotel Alfama Reyes</span>',
             '<span aria-current="page" style="color: var(--vy-ink); font-weight: 500;">A 96-key city hotel</span>'),
            ('SPEC/lis-eyebrow',
             '>Hotel \u00b7 Lisbon</span>', '>Hotel \u00b7 city centre</span>'),
            ('SPEC/lis-label',
             'The floor stopped phoning reception</h1>\n          <p style="margin: 20px 0 0;',
             'The floor stopped phoning reception</h1>\n          ' + _LABEL + '\n          <p style="margin: 20px 0 0;'),
            ('SPEC/lis-prevcard-name',
             'line-height: 1.3;">Van Dijk Stays</span>',
             'line-height: 1.3;">An 18-apartment operator</span>'),
            ('SPEC/lis-prevcard-sub',
             'margin-top: 3px;">18 apartments \u00b7 Rotterdam</span>',
             'margin-top: 3px;">Apartments \u00b7 example scenario</span>'),
            ('SPEC/lis-register',
             '>Illustrative story</div>', '>Illustrative scenario</div>'),
        ):
            t = swap(t, _b, _a, key, _tag)

    if key == 'story-rotterdam':
        # ── RECON — NO SPEC SURVIVES FOR THIS PAGE. Every needle below is reconstructed from the diff
        #    between the converter's output and the committed page, and anchored on the CONTENT it is
        #    there to remove: the invented business names, the city pins that identify a real place, and
        #    the quantified achieved outcomes. ⚠️ A green diff on this page therefore means "reproduces
        #    the committed file", NOT "implements a record" — there is no record. Nothing here is anchored
        #    on an index, an nth-child or a sibling count, so a master change moves the block without
        #    breaking the cut.
        meta['title'] = 'How it works for 18 serviced apartments | Vyrobel example scenario'
        meta['description'] = ('An illustrative scenario: a two-person business paying more in OTA '
                               'commission than it paid itself, and what changes when the same guests '
                               'arrive through its own booking page.')
        for _tag, _b, _a in (
            # the print letterhead — an invented identity and a real city
            ('RECON/rot-printhead',
             '>Customer story \u00b7 18 serviced apartments \u00b7 Rotterdam</span>',
             '>Example scenario \u00b7 18 serviced apartments</span>'),
            ('RECON/rot-letterfoot',
             '>Van Dijk Stays \u00b7 Illustrative story, composed from onboarding conversations \u00b7 vyrobel.com/case-studies</div>',
             '>Illustrative scenario, composed from onboarding conversations \u00b7 vyrobel.com/case-studies</div>'),
            ('RECON/rot-crumb-collection',
             'font-weight: 500;">Customer stories</x-marketing.nav-link>',
             'font-weight: 500;">Example scenarios</x-marketing.nav-link>'),
            ('RECON/rot-crumb-current',
             '<span aria-current="page" style="color: var(--vy-ink); font-weight: 500;">Van Dijk Stays</span>',
             '<span aria-current="page" style="color: var(--vy-ink); font-weight: 500;">Serviced apartments</span>'),
            ('RECON/rot-eyebrow',
             '>Serviced apartments \u00b7 Rotterdam</span>', '>Serviced apartments</span>'),
            # condition 5 — the label, emitted as a REPLACEMENT so a second pass cannot double it
            ('RECON/rot-label',
             'The year the commission became a salary</h1>\n          <p style="margin: 20px 0 0;',
             'The year the commission became a salary</h1>\n          ' + _LABEL
             + '\n          <p style="margin: 20px 0 0;'),
            # ⚠️ condition 3 — "smallest migration we've run" is a claim about a BOOK OF CUSTOMERS that
            #    does not exist; it implies a population to be smallest of.
            ('RECON/rot-superlative',
             ">smallest migration we've run</div>", '>start to finish</div>'),
            # ⚠️ condition 3 — a quantified achieved outcome, in prose
            ('RECON/rot-quantified-prose',
             '>Direct nights went from roughly a fifth of the total to over half across one season. ',
             '>More of the same nights arrived through their own booking page instead of a marketplace. '),
            # the two sibling-scenario cards: invented names + city pins
            ('RECON/rot-card-villas-name',
             'line-height: 1.3;">Kastellis Villas</span>', 'line-height: 1.3;">Island villas</span>'),
            ('RECON/rot-card-villas-sub',
             'margin-top: 3px;">42 villas \u00b7 Cyclades</span>', 'margin-top: 3px;">42 villas</span>'),
            ('RECON/rot-card-hotel-name',
             'line-height: 1.3;">Hotel Alfama Reyes</span>', 'line-height: 1.3;">City hotel</span>'),
            ('RECON/rot-card-hotel-sub',
             'margin-top: 3px;">96 keys \u00b7 Lisbon</span>', 'margin-top: 3px;">96 keys</span>'),
        ):
            t = swap(t, _b, _a, key, _tag)

        # ⚠️ condition 3 — the whole callout CARD goes here, not just its contents (which is what the
        #    cyclades rewrite did to its equivalent). Reproduced as the committed page has it rather than
        #    made consistent with the other page: porting, not improving (#9).
        #    ⚠️ Anchored on the fabricated figure itself.
        _m = re.search(r'\n\s*<div style="padding: 22px 22px;[^"]*">\s*<div data-printcallout[^>]*>'
                       r'~20% \u2192 50%\+</div>\s*<div[^>]*>[^<]*</div>\s*</div>', t)
        _NEEDLE_LOG.append((key, 'RECON/rot-cut-quantified-outcome', _m is not None))
        if _m:
            t = t[:_m.start()] + t[_m.end():]

    if key == 'case-studies':
        # ── RECON — NO SPEC SURVIVES FOR THIS PAGE EITHER, and this is the largest reconstruction of the
        #    four (-70/+20 lines). ⚠️ A DELETION LEAVES NOTHING TO CHECK INTENT AGAINST, so every cut below
        #    is anchored on the text that CARRIES the intent — the invented business name, the attributed
        #    quote, the "After one season" results heading, the offer to introduce the reader to a customer
        #    — and never on which card or section happens to sit in that position.
        meta['title'] = 'Example scenarios \u2014 how operators use Vyrobel'
        meta['description'] = ('Three illustrative scenarios, not customers: a multi-island villa manager, '
                               'a small short-stay apartment operator, and an independent hotel. What '
                               'changes, and what it takes.')
        for _tag, _b, _a in (
            ('RECON/cs-screen-label',
             'data-screen-label="Marketing \u2014 Case studies"',
             'data-screen-label="Marketing \u2014 Example scenarios"'),
            ('RECON/cs-eyebrow', 'both;">Customer stories</div>', 'both;">Illustrative scenarios</div>'),
            ('RECON/cs-h1',
             '>Three businesses, three reasons for switching</h1>',
             '>Three example scenarios, three reasons for switching</h1>'),
            # ⚠️ THE PROVENANCE CLAIM, NOT JUST THE FIGURES. "shared with permission and rounded" asserts
            #    consent from operators who do not exist — a fabricated warrant for fabricated numbers.
            ('RECON/cs-standfirst-provenance',
             " Figures are the operators' own, shared with permission and rounded.</p>", '</p>'),
            ('RECON/cs-label',
             'three reasons for switching</h1>\n        <p style="margin: 22px 0 0;',
             'three reasons for switching</h1>\n        ' + _LABEL + '\n        <p style="margin: 22px 0 0;'),
            # the section comments name the genre the ruling removed
            ('RECON/cs-comment-1', '<!-- STORY 1 -->', '<!-- SCENARIO 1 -->'),
            ('RECON/cs-comment-2', '<!-- STORY 2 -->', '<!-- SCENARIO 2 -->'),
            ('RECON/cs-comment-3', '<!-- STORY 3 -->', '<!-- SCENARIO 3 -->'),
            # ⚠️ THE AVATAR INITIALS ARE THE INVENTED PEOPLE. EK/JV/MR are Elena Kastellis, Joris van Dijk
            #    and Marco Reyes — three names already in the honesty ledger. They survive the D13 rename
            #    here only because their FULL names never appear on this page, so D13's `if _old in t`
            #    guard never fires. Numbering the cards removes the person instead of inventing another.
            ('RECON/cs-avatar-1', 'font-weight: 600;">EK</span>', 'font-weight: 600;">01</span>'),
            ('RECON/cs-avatar-2', 'font-weight: 600;">JV</span>', 'font-weight: 600;">02</span>'),
            ('RECON/cs-avatar-3', 'font-weight: 600;">MR</span>', 'font-weight: 600;">03</span>'),
            # the three invented businesses, and the city pins that make them findable
            ('RECON/cs-name-1', '>Kastellis Villas</div>', '>A villa management company</div>'),
            ('RECON/cs-sub-1', '>42 villas \u00b7 19 owners \u00b7 Cyclades, Greece</div>',
             '>42 villas \u00b7 19 owners \u00b7 across several islands</div>'),
            ('RECON/cs-name-2', '>Havenzicht Stays</div>', '>A short-stay apartment operator</div>'),
            ('RECON/cs-sub-2', '>18 apartments \u00b7 4 buildings \u00b7 Rotterdam</div>',
             '>18 apartments \u00b7 4 buildings \u00b7 one city</div>'),
            ('RECON/cs-name-3', '>Casa Mirante</div>', '>An independent hotel</div>'),
            ('RECON/cs-sub-3', '>96 keys \u00b7 independent hotel \u00b7 Lisbon</div>',
             '>96 keys \u00b7 one property</div>'),
            # ⚠️ condition 2 — each <h2> is a QUOTATION. A quoted sentence is attributed speech even with
            #    no name beside it: it says someone said this. Replaced with a description of what the
            #    section is about, which is what the committed page does.
            ('RECON/cs-quote-h2-1', '>"Our owners stopped calling at month end"</h2>',
             '>Owner statements, moved into a portal</h2>'),
            ('RECON/cs-quote-h2-2', '>"That\'s not a feature list, that\'s payroll"</h2>',
             '>Moving nights from marketplaces to direct</h2>'),
            ('RECON/cs-quote-h2-3', '>"Housekeeping runs itself now"</h2>',
             '>Housekeeping and maintenance on one board</h2>'),
        ):
            t = swap(t, _b, _a, key, _tag)

        # ⚠️⚠️ condition 3 — THE THREE RESULTS PANELS. Each is a heading ("After one season") followed by
        #    its sibling list of measured outcomes for a business that does not exist. The counters inside
        #    were already emptied upstream by transform (3), which left a panel announcing results and
        #    showing none — worse than either state, and exactly the "heading whose content was filtered
        #    away" defect `cut_element` was written for.
        #    ⚠️ ANCHORED ON THE HEADING'S OWN WORDS, then extended to its NEXT SIBLING element by
        #    structure. Not an index: the panel is found by what it says, wherever it sits.
        for _label, _tag in (('After one season', 'RECON/cs-cut-results-1'),
                             ('After twelve months', 'RECON/cs-cut-results-2'),
                             ('After six months', 'RECON/cs-cut-results-3')):
            _i = t.find(_label)
            _ok = False
            if _i >= 0:
                _s1 = _element_span(t, _i, 'div')
                if _s1 and _s1[0] <= _i < _s1[1]:
                    _n = t.find('<div', _s1[1])
                    _s2 = _element_span(t, _n, 'div') if _n >= 0 else None
                    if _s2:
                        _start = t.rfind('\n', 0, _s1[0]) + 1
                        _end = _s2[1]
                        if t[_end:_end + 1] == '\n':
                            _end += 1
                        t = t[:_start] + t[_end:]
                        _ok = True
            _NEEDLE_LOG.append((key, _tag, _ok))

        # ⚠️⚠️ condition 4 — THE OFFER THAT PRESUPPOSES A REAL CUSTOMER. "Want to talk to one of them?" /
        #    "We'll introduce you to an operator running something close to your setup" promises an
        #    introduction to a business that does not exist. ⚠️ That is not a wording problem: a reader can
        #    ACT on it, and the action cannot be honoured. The whole section goes — heading, promise and
        #    both CTAs — because removing the promise and leaving its buttons is how a dead control
        #    survives a cleanup (the contact page's demo booker, same lesson).
        _i = t.find('Want to talk to one of them?')
        _ok = False
        if _i >= 0:
            _sp = _element_span(t, _i, 'section')
            if _sp and _sp[0] <= _i < _sp[1]:
                _start = t.rfind('\n', 0, _sp[0]) + 1
                while _start >= 2 and t[_start - 2] == '\n':
                    _start -= 1
                _end = _sp[1] + (1 if t[_sp[1]:_sp[1] + 1] == '\n' else 0)
                t = t[:_start] + t[_end:]
                _ok = True
        _NEEDLE_LOG.append((key, 'RECON/cs-cut-introduction-offer', _ok))

    # ══ (8) ⚠️⚠️ THE DOC-CLAIM SPEC — G4 · G5 · G6, AND THE TWO SETTLED ARTICLE RULINGS ═══════════
    #
    # ⚠️⚠️ THE TRANSFORMS LIVE HERE, IN THE CONVERTER, AND THE REPLACEMENT TEXT LIVES IN `spec/`. The two
    #    are deliberately not the same file: the spec is the RECORD (what was ruled and why, readable by
    #    someone who never saw the conversation), and this is the MECHANISM. ⚠️ Importing rather than
    #    copying keeps ONE definition of every replacement — 28 reframes duplicated into this file would
    #    be two copies free to drift, which is the §N.8f failure the spec exists to prevent.
    #
    # ⚠️ AND IT IS HERE RATHER THAN IN A ONE-OFF SCRIPT BECAUSE OF WHAT HAPPENED LAST TIME: the scenarios
    #    pages' honesty rewrites lived only in generated output, made by scripts that were then discarded,
    #    so regenerating any of them would have restored five invented customer identities.
    #
    # ⚠️ NOTHING HERE PUBLISHES ANYTHING. These pages have no view, no route, and are `pending` in
    #    MarketingUrls. The transforms make the CONVERSION honest; publication is gated separately on the
    #    claims still being sourced.
    if key in _DOC_SPEC_PAGES:
        # ── G6 · the 28 guide reframes, verbatim from spec/g6_guides.py ──────────────────────────────
        #    ⚠️ Reframed to what WE believe/built/chose — never "in our view, most operators…", which is
        #    the same unevidenced claim about the world with a hedge in front of it.
        for _cid, (_pg, _orig, _repl) in _G6_GUIDES.items():
            if _pg == key:
                t = swap(t, _orig, _repl, key, 'SPEC-G6/' + _cid)

        # ── G5 · promises nothing measurable ─────────────────────────────────────────────────────────
        #    ⚠️ THE CHROME LINE IS ONE TRANSFORM, SITEWIDE. It sits in the chrome of all sixteen guides,
        #    and the chrome has carried an untrue claim six times now. One rule, applied wherever it
        #    appears — never sixteen copies free to diverge (§N.8f).
        if _G5['S1-chrome-support']['original'] in t:
            t = swap(t, _G5['S1-chrome-support']['original'],
                     _G5['S1-chrome-support']['replacement'], key, 'SPEC-G5/chrome-support')
        if key == 'guide-security-doc':
            t = swap(t, _G5['S1-137b']['original'], _G5['S1-137b']['replacement'], key, 'SPEC-G5/S1-137b')

        # ── ⚠️⚠️ THE FALSE PRODUCT CLAIMS — CORRECTED, NEVER HEDGED ──────────────────────────────────
        #    ⚠️ A hedge makes a false claim UNFALSIFIABLE: "in our view the code releases only once the
        #    balance is settled" is WORSE than the original, because the original could at least be
        #    checked against ArrivalPack. These describe behaviour that does not exist, so they are
        #    replaced with what the product actually does (or cut where there is nothing true to say).
        #    ⚠️ THREE FAQ CLAIMS ARE ABSENT HERE ON PURPOSE — see _DOC_SPEC_FAQ_NOTE.
        for _cid, _d in _FALSE.items():
            # ⚠️ `replacement is None` means KEEP — no transform, by decision. S1-006 is a TRUE sentence
            #    whose surrounding IMPLICATION is what was false, and that is removed at its source by
            #    S1-179; cutting it would delete a true sentence to fix a false one.
            if _d['page'] == key and _cid not in _DOC_SPEC_FAQ_ONLY and _d['replacement'] is not None:
                t = swap(t, _d['original'], _d['replacement'], key, 'SPEC-FIX/' + _cid)

        # ── ⚠️ G4 · the scope gap: the sentence was WIDER than its evidence ──────────────────────────
        #    It claimed owners cannot see guest NAMES, but name-only is the perimeter BY DESIGN and the
        #    internal test sources the contact-details half only. ⚠️ THE SENTENCE IS NARROWED; THE TEST
        #    IS UNTOUCHED — widening the test to match the copy would have been the softening failure
        #    wearing a lab coat.
        for _cid, (_orig, _repl) in _SCOPE.items():
            if _orig in t:
                t = swap(t, _orig, _repl, key, 'SPEC-G4/' + _cid)

        # ── S1-042 · the narrowed login sentence (founder ruling) ────────────────────────────────────
        for _cid, (_orig, _repl) in _LOGIN.items():
            if _orig in t:
                t = swap(t, _orig, _repl, key, 'SPEC-G4/' + _cid)

    # ── ⚠️ THE TWO SETTLED ARTICLE RULINGS ───────────────────────────────────────────────────────────
    # ⚠️ `article-housekeeping` IS DELIBERATELY ABSENT. Its ruling is a LIVE EITHER/OR — re-argue as "how
    #    we built housekeeping and why", OR do not publish — and accepting the recommendation did not
    #    collapse the two branches. Implementing either here would pick one silently.
    # ══ SLICE 2 · CLUSTER F (Security) and CLUSTER G (GDPR) ══════════════════════════════════════
    #
    # ⚠️⚠️ CLUSTER F: A SECURITY CLAIM IS BACKED BY CODE OR IT IS FALSE — NEVER SOFTENED. A hedged
    #    security property is worse than a plainly wrong one: the reader keeps believing they have the
    #    protection, and nothing can disprove it any more. Each correction below replaces a claim with
    #    what the code actually does, and the code fact is recorded beside it in the spec.
    #    ⚠️ The SMS correction is STRONGER than the sentence it replaces — TOTP plus recovery codes beats
    #    SMS, which a SIM swap defeats. It is not a walk-back.
    #
    # ⚠️⚠️ CLUSTER G: DESCRIBE WHAT EXISTS. The page narrated a retention system that was never built —
    #    a "Data and privacy" screen that does not exist, a statutory period that is not a setting, and
    #    an anonymisation sweep nobody wrote. ⚠️ It equally must not promise the cluster-G BUILD, which is
    #    ruled and unstarted: describing an unbuilt system in the present tense is the same defect
    #    pointed the other way.
    #    ⚠️ G3 emits a BLADE ECHO for the grace period rather than typing the number — a literal here
    #    would be a second copy of a real constant, free to drift the day it changes (the LEAD_DAYS rule).
    if key == 'guide-security-doc':
        for _cid, _d in _SEC.items():
            t = swap(t, _d['original'], _d['replacement'], key, 'SLICE2-F/' + _cid)

        # ⚠️ THE THREE THIS PAGE'S EARLIER PASS MISSED. Its four transforms above corrected 4 of the
        #    audit's 8 FALSE claims; these close the rest that are matchable. Each was checked against
        #    the code first — see tools/marketing/spec/six_uncorrected.py for the citations.
        for _cid, _d in _SIX_SEC.items():
            t = swap(t, _d['before'], _d['after'], key, 'SIX-SEC/' + _cid)

    # ⚠️ BATCH 1 OF THE ~120 — THE MONEY PAGES, ordered by CONSEQUENCE. A false sentence about how an
    #    owner gets PAID or a guest gets money BACK is the kind a customer acts on and then disputes, so
    #    every replacement was checked against the code first (citations in spec/batch1_money.py).
    for _pg, _rules, _tag in (('guide-payouts', _B1_PAY, 'B1-PAY'),
                              ('guide-statements', _B1_STMT, 'B1-STMT'),
                              ('guide-refunds', _B1_REF, 'B1-REF')):
        if key == _pg:
            for _cid, _d in _rules.items():
                t = swap(t, _d['before'], _d['after'], key, _tag + '/' + _cid)

    # ⚠️ BATCH 2 — ACCESS & PRIVACY. ⚠️ `guide-gdpr` is NOT untouched: slice 2 already owns five of its
    #    sentences on the ledger's list, so these run AFTER the slice-2 block and take only what is left.
    for _pg, _rules, _tag in (('guide-access', _B2_ACC, 'B2-ACC'), ('guide-gdpr', _B2_GDPR, 'B2-GDPR')):
        if key == _pg:
            for _cid, _d in _rules.items():
                t = swap(t, _d['before'], _d['after'], key, _tag + '/' + _cid)

    # ⚠️ BATCH 3 — THE BOOKING SPINE. ⚠️ `guide-checkin` is "Check-in, check-out and the folio" and
    #    `guide-arrival` is "Online check-in and arrival information" — the corrections gate had these two
    #    SWAPPED, which is why 35 of their claims read as unmatched for three passes.
    for _pg, _rules, _tag in (('guide-reservation', _B3_RES, 'B3-RES'),
                              ('guide-checkin', _B3_CHK, 'B3-CHK'),
                              ('guide-arrival', _B3_ARR, 'B3-ARR')):
        if key == _pg:
            for _cid, _d in _rules.items():
                t = swap(t, _d['before'], _d['after'], key, _tag + '/' + _cid)

    # ⚠️ BATCH 4 — RATES ONLY. The channels page's subject was established first and is REPORTED, not
    #    corrected: a page whose premise is a capability the account does not have is not a wording fix.
    if key == 'guide-rates':
        for _cid, _d in _B4_RATES.items():
            t = swap(t, _d['before'], _d['after'], key, 'B4-RATE/' + _cid)

    # ⚠️⚠️ /faq + /glossary — THE CONTENT THE CONVERTER USED TO DROP ON THE FLOOR.
    #    Both pages render their content from a `DCLogic` array inside a <script>, and every <script> is
    #    stripped — so the gate saw NOTHING and both read "0 still present", indistinguishable from
    #    corrected. ⚠️ The corrections were applied AT SOURCE first (`spec/faq_glossary.py`, verification in
    #    `docs/FAQ-GLOSSARY-SOURCE-VERIFICATION.md`); this only carries the corrected data into the output.
    #    ⚠️ BOTH COUNTERS ARE DERIVED from the rendered list, and the FAQ's JSON-LD is GENERATED from the
    #    same corrected rows — it was a hand-maintained second copy that had already fallen 13 questions
    #    behind, which is exactly how two copies diverge where nobody can see it.
    if key in ('faq', 'glossary'):
        t = _render_dc_page(t, key)
        # ⚠️ THE CORRECTIONS MUST HAVE BEEN APPLIED, not merely rendered. Proven by mutation: dropping the
        #    corrections put every false answer back with every needle still green.
        _hits = [b for b in _FG_BAD if b.encode().decode('unicode_escape') in t]
        _NEEDLE_LOG.append((key, 'FG/no-false-claim-survives', _hits == []))


    # ⚠️ BATCH 5 — OPERATIONS, REPORTING & SETTINGS. Verification record:
    #    `docs/DOC-BATCH5-VERIFICATION.md` (every row carries its code fact).
    #    ⚠️ `/faq` and `/glossary` are NOT here: both render from a data structure the extractor strips, so
    #    a needle would match nothing. Their clean gate result means NOT LOOKED AT, not corrected.
    for _pg, _rules, _tag in (('guide-setup', _B5_SETUP, 'B5-SETUP'),
                              ('guide-reports', _B5_REP, 'B5-REP'),
                              ('guide-settings', _B5_SET, 'B5-SET'),
                              ('guide-housekeeping', _B5_HK, 'B5-HK'),
                              ('guide-storefront', _B5_STORE, 'B5-STORE'),
                              ('docs', _B5_DOCS, 'B5-DOCS'),  # noqa: E501  (changelog links removed below)
                              ('resources', _B5_RES, 'B5-RES'),
                              ('article-pricing', _B5_ARTP, 'B5-ARTP')):
        if key == _pg:
            for _cid, _d in _rules.items():
                t = swap(t, _d['before'], _d['after'], key, _tag + '/' + _cid)

    # ⚠️⚠️ THE /docs CHANGELOG ENTRY — REMOVED. A refused surface keeps NO door, not even a disabled one.
    #    `routes/web.php:633`: a changelog asserts a RELEASE HISTORY and there are zero git tags across the
    #    repo's commits, so the page was REFUSED on principle — not deferred. The converter had already
    #    turned both links into `<x-marketing.nav-link key="changelog">`, which renders an aria-disabled
    #    NON-LINK, so nothing 404s. ⚠️ But a marked-pending item still ADVERTISES something that is not
    #    coming, and "pending" is the wrong word for "refused". Both entries are removed and NOTHING is put
    #    in their place — a replacement would be a promise.
    #    ⚠️ NEEDLED AGAINST THE POST-REWRITE ELEMENT, not the master's <a href="Vyrobel Changelog.dc.html">:
    #    the href rewrite runs first, and a needle against the master matched nothing and said so.
    if key == 'docs':
        _before_len = len(t)
        t = re.sub(r'<x-marketing\.nav-link key="changelog".*?</x-marketing\.nav-link>\s*', '', t, flags=re.S)
        _NEEDLE_LOG.append((key, 'B5-DOCS/changelog-entries-removed', len(t) < _before_len))

    # ⚠️⚠️ BATCH 6 — THE DOC-TRUTH FALSE CLAIMS ON THE THREE ARTICLES NO MODULE HAD EVER TOUCHED.
    #    Same two-registers-one-page failure as `article-housekeeping` below: G6 opinion claims were
    #    addressed, the Gate-5 FALSE list on the same pages was not. Evidence per claim lives in
    #    spec/batch6_articles_truth.py — every replacement checked against the code before it was written.
    for _k, _table, _tag in (('article-trust', _B6_TRUST, 'B6-TRUST'),
                             ('article-owners', _B6_OWNERS, 'B6-OWNERS'),
                             ('article-statement', _B6_STMT, 'B6-STMT'),
                             ('article-direct', _B6_DIRECT, 'B6-DIRECT'),
                             ('article-oos', _B6_OOS, 'B6-OOS'),
                             # ⚠️ THE LAST NINE — the final reachable doc-truth claims (spec/last_nine.py).
                             ('guide-setup', _N9_SETUP, 'N9-SETUP'),
                             ('guide-arrival', _N9_ARR, 'N9-ARRIVAL'),
                             ('guide-reports', _N9_REP, 'N9-REPORTS'),
                             ('article-pricing', _N9_PRI, 'N9-PRICING')):
        if key == _k:
            for _i, (_before, _after) in enumerate(_table.items(), 1):
                t = swap(t, _before, _after, key, f'{_tag}/{_i}')

    if key == 'article-housekeeping':
        # ⚠️ THE RE-ARGUE ON THIS PAGE TARGETED `claims.json`'s UNSOURCED ids and never touched the
        #    audit's FALSE list — a DIFFERENT SET ON THE SAME PAGE. This is that list.
        for _cid, _d in _SIX_HK.items():
            t = swap(t, _d['before'], _d['after'], key, 'SIX-HK/' + _cid)

    if key == 'guide-gdpr':
        for _cid, _d in _GDPR.items():
            t = swap(t, _d['original'], _d['replacement'], key, 'SLICE2-G/' + _cid)

    if key == 'article-direct':
        # ⚠️ A PREDICTED OUTCOME CANNOT BE HEDGED INTO TRUTH. Each of these promises the reader a result;
        #    we have no operators to have observed it in. The mechanics of going direct stay.
        for _cid, _sentence in _DIRECT_CUTS.items():
            t = swap(t, _sentence, '', key, 'RULING-direct/' + _cid)

    if key == 'article-housekeeping':
        # ⚠️⚠️ THE RE-ARGUE (founder ruling). The branch was a LIVE EITHER/OR until it was chosen; this is
        #    the "re-argue as how we built housekeeping and why" branch, and only the NINE blocks that
        #    needed new argument change. The 24 that survive are untouched — they were measured as
        #    surviving, and improving them while in the file would turn a bounded pass into an
        #    unreviewable one.
        #
        # ⚠️ NINE, NOT TWELVE: three of the twelve G6 rows (S1-044, S1-101, S1-171) are headings and a
        #    rhetorical question, already ruled NOISE. They are not claims and need no argument.
        #
        # ⚠️⚠️ THE HAZARD, MANAGED: re-arguing swaps unsourceable claims about OPERATORS for claims about
        #    OUR OWN PRODUCT — which makes every one checkable, and therefore CHECKED BEFORE IT WAS
        #    WRITTEN. Each BEHAVIOUR sentence below carries a `kind: internal` source (a test that fails
        #    when it stops being true); each RATIONALE sentence asserts only our own judgement and needs
        #    none. ⚠️ Classified by what the sentence ASSERTS, never by how it is phrased.
        for _cid, _d in list(_HK_BEHAVIOUR.items()) + list(_HK_RATIONALE.items()):
            t = swap(t, _d['original'], _d['replacement'], key, 'REARGUE/' + _cid)

        # ⚠️ THE TWO HEADINGS THE `not_a_claim` READ RESTORED — they were filed as noise by the element
        #    test and caught by reading, so they were never part of the nine above.
        #    ⚠️ S1-044 carried TWO claims in one heading: a COUNT and a VISIBILITY scope. "Everyone" was
        #    doing unexamined work — the board's read is ungated, but the /app group admits only the
        #    eleven back-office roles, and an owner is refused (#26) and sees a different, read-only
        #    surface. TRUE ONLY WITH A SCOPE, so the sentence now states it.
        #    ⚠️ S1-171 lost "that do most of the work" — an unmeasured outcome. Hedging it would have made
        #    it unfalsifiable rather than true. The count that remains is self-evidencing: the three rules
        #    are in the same section, and a reader counts them without leaving it.
        t = swap(t, _SPLIT_044['original'], _SPLIT_044['replacement'], key, 'REARGUE/S1-044-split')
        t = swap(t, _CUT_171['original'], _CUT_171['replacement'], key, 'REARGUE/S1-171-cut')

    if key == 'article-pricing':
        # ⚠️ Keep what the levers ARE and how they resolve; drop every claim about what WORKS.
        for _cid, _sentence in _PRICING_CUTS.items():
            t = swap(t, _sentence, '', key, 'RULING-pricing/' + _cid)

    # ⚠️⚠️ THE SAME PROTECTION FOR EVERY OTHER PAGE, AND IT MUST RUN LAST. The needles prove a page
    #    RENDERED; they do not prove it renders the CORRECTED text — a mutation that dropped every
    #    correction left them all green. This is the check that catches a correction which never landed.
    #
    # ⚠️ A FIRST PLACEMENT PUT THIS BESIDE THE /faq BLOCK, WHICH RUNS *BEFORE* THE BATCH TRANSFORMS — so it
    #    read the UNCORRECTED text and reported all 28 fragments as still present. ⚠️ THAT LOOKED EXACTLY
    #    LIKE 28 UNLANDED CORRECTIONS, which is the finding this check exists to make, and acting on it
    #    would have meant "fixing" corrections that were already correct. A check that runs at the wrong
    #    point in a pipeline does not fail loudly — it fails PLAUSIBLY. It runs last, deliberately.
    if key in _FG_BAD_PAGE:
        _bad = [b for b in _FG_BAD_PAGE[key] if b in t]
        _NEEDLE_LOG.append((key, 'FG/no-false-claim-survives', _bad == []))
        for _b in _bad:
            _NEEDLE_LOG.append((key, 'FG/still-present: ' + _b[:60], False))

    # ══ ⚠️⚠️ HEADING STRUCTURE + ANCHOR RESOLUTION — GENERIC, LAST, OVER EVERY PAGE ═══════════════════
    #
    # ⚠️ WRITTEN AS RULES, NOT AS THREE NAMED PAGES. The F-1 lesson: a transform that is never CALLED on a
    #    page slips past `_NEEDLE_LOG` entirely, because the log only reports on transforms that ran. So
    #    these run for EVERY key and are no-ops where the shape is already right — a page that gains the
    #    defect later is corrected without anybody remembering to add it to a list.
    #
    # ── ⓪ THE SKIP LINK MOVES TO THE SHELL — F-11 ───────────────────────────────────────────────────
    #
    # ⚠️⚠️ MEASURED IN A BROWSER, NOT REASONED: the skip link was the THIRD tab stop on every page checked
    #    (logo → the menu burger → skip), because the design emits it INSIDE the page body, which the shell
    #    renders inside `<main>` — after the whole navigation. A skip link that is not first skips nothing.
    #    ⚠️ AND IT WAS PERMANENTLY INVISIBLE: parked at `left: -9999px` with no `:focus` rule to bring it
    #    back. Its bounding box measured left=-9999 BEFORE focus and left=-9999 AFTER. It still worked if
    #    activated blind, which is why every static check and every reading passed over it.
    #
    # ⚠️ SO IT IS CUT HERE AND RE-EMITTED BY THE SHELL, ABOVE THE NAV, WITH A REAL `:focus` RULE. Cutting
    #    it is half the fix and must not ship alone — the shell change is in the same commit.
    # ⚠️ `#vy-main` IS UNTOUCHED: the target `<main id="vy-main">` stays in the page body, so the shell's
    #    link resolves to the same element the design intended.
    # ⚠️ NO `_NEEDLE_LOG` ENTRY, for the same reason as ①–③ below: this runs on EVERY page, and a page that
    #    legitimately never had one would RED THE BUILD FOR CORRECT WORK. The suite guards it instead, as a
    #    rule over the rendered output of all 19 pages.
    t = re.sub(r'\s*<a href="#vy-main"[^>]*>Skip to content</a>', '', t, count=1)

    # ── ① THE PAGE'S H1 IS THE ELEMENT THE DESIGN MARKED `data-h1` ───────────────────────────────────
    #
    # ⚠️ THE INTENT IS IN THE MARKUP, SO NOTHING IS INVENTED — but ⚠️⚠️ `data-h1` IS NOT A UNIQUE
    #    PAGE-TITLE MARKER, AND ASSUMING IT WAS PRODUCED A REGRESSION. The first version of this promoted
    #    EVERY `data-h1`-marked heading; on /home the three audience lenses each carry one, so the page went
    #    from one `<h1>` to THREE — worse than the defect being fixed, and caught only by diffing the
    #    regenerated pages rather than by reasoning. ⚠️ The marker means "this is a hero heading", not "this
    #    is the page title".
    #
    # ⚠️ SO THE RULE IS NARROW AND MATCHES THE ACTUAL DEFECT: a page with NO `<h1>` at all takes its FIRST
    #    marked heading. /contact shipped with its title as an `<h2 data-h1>`, so a screen-reader user
    #    navigating by heading got no page title. Copy, styling and position are untouched — the styling is
    #    fully inline on these pages, so the level carries no visual weight.
    #
    # ⚠️ NO `_NEEDLE_LOG` ENTRY, DELIBERATELY. These three run on EVERY page and are no-ops wherever the
    #    shape is already right — which is most pages. A zero-match needle exits 4, so logging them would
    #    RED THE BUILD FOR CORRECT WORK. The guard for all three lives in the test suite instead, as a rule
    #    over the RENDERED output of all 19 pages, which is the only place that can see a page nobody
    #    remembered to transform.
    if not re.search(r'<h1\b', t):
        t = re.sub(r'<h([2-6])(\s[^>]*\bdata-h1\b[^>]*)>(.*?)</h\1>',
                   lambda m: '<h1' + m.group(2) + '>' + m.group(3) + '</h1>', t, count=1, flags=re.S)

    # ── ② AN H3 BEFORE THE PAGE'S FIRST H2 IS MIS-LEVELLED ──────────────────────────────────────────
    #
    # ⚠️ ESTABLISHED BY READING THE OUTLINE, NOT BY PATTERN-MATCHING A RULE. /features opens with six
    #    top-level highlight cards as `<h3>` and only then starts its `<h2>` sections, so the outline jumped
    #    h1→h3 and misreported the page's shape. ⚠️ NO EMPTY HEADING IS INSERTED to close the gap — that
    #    would be a fabrication. The six cards ARE top-level groupings, so they are promoted to the level
    #    they already occupy visually and structurally.
    # ⚠️ THE NO-H2 CASE IS REAL AND WAS MISSED FIRST TIME. /contact's body holds ONE section heading and,
    #    once its title was promoted to `<h1>`, no `<h2>` remained — so a `if first_h2:` guard skipped the
    #    page entirely and left a fresh h1→h3 skip behind the fix. (The two `<h2>Cookies on this site</h2>`
    #    on that page come from the SHELL at request time, not from this body, so they cannot close the gap.)
    #    Splitting at the first h2 OR taking the whole body when there is none covers both shapes.
    _first_h2 = re.search(r'<h2\b', t)
    _cut = _first_h2.start() if _first_h2 else len(t)
    head, tail = t[:_cut], t[_cut:]
    if re.search(r'<h3\b', head):
        head = re.sub(r'<h3(\b[^>]*)>(.*?)</h3>', lambda m: '<h2' + m.group(1) + '>' + m.group(2) + '</h2>', head, flags=re.S)
    t = head + tail

    # ── ③ AN IN-PAGE ANCHOR MUST NAME AN ID THAT EXISTS ─────────────────────────────────────────────
    #
    # ⚠️ /pricing's "Request a formal quote" pointed at `/contact#form`, and /contact has no `id="form"` —
    #    the visitor landed at the top of the page with nothing highlighted. ⚠️ THE TARGET ALREADY EXISTS:
    #    the contact form carries `id="vy-contact-form"` (components/marketing/contact-form.blade.php), so
    #    the LINK is corrected to name it rather than minting a second id for one element — two identities
    #    for one thing is the drift this project keeps removing (§N.8f). The three sibling links on the same
    #    page point at `#demo` and are correct; only the quote link is repointed, and its text says which.
    t = t.replace('fragment="#form"', 'fragment="#vy-contact-form"')

    return meta, t.strip(), left

if __name__ == '__main__':
    for k in sys.argv[1:]:
        meta, body, left = convert(k)
        print(f"{k}: {len(body)} chars, leftover handlebars={left}")
        print("   meta:", json.dumps(meta)[:200])

    # ⚠️⚠️ REFUSE TO FINISH IF AN HONESTY NEEDLE MATCHED NOTHING. Three times in this project a transform
    # that replaced a false claim quietly replaced nothing, and each time the pipeline reported success.
    # A generator that cannot tell you its honesty fix did not apply is not a guard, it is a hope.
    _missed = [(k, tag) for k, tag, ok in _NEEDLE_LOG if not ok]
    print(f"\n   honesty needles: {len(_NEEDLE_LOG)} attempted, {len(_missed)} matched nothing")
    if _missed:
        for k, tag in _missed:
            print(f"   ⚠️ SILENT NO-OP: {k} / {tag} — the claim it removes is STILL IN THE OUTPUT")
        sys.exit(4)
