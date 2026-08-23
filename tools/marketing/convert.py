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


def swap(t, before, after, key, tag):
    """Replace `before` with `after`, trying the design's punctuation variants, and RECORD the outcome."""
    for v in _variants(before):
        if v in t:
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
    t = re.sub(r'\ssc-camel-[a-z-]+="[^"]*"', '', t)

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
            ('switching-body',
             'The import, the reconciliation, the training and the channel cutover are part of onboarding, '
             'not a professional services line. Starter customers self-serve with a CSV',
             'Vyrobel imports from a CSV, and the reconciliation step below is how you check it landed '
             'correctly before you cut over. \u26a0\ufe0f What hands-on help comes with a plan is not settled '
             'yet \u2014 ask us rather than assume it',
            ),
        ):
            t = swap(t, _b, _a, key, _tag)
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
