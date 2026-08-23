"""The driver for the 27 DOCUMENTATION pages — guides, articles, /docs, /faq, /glossary, /resources.

⚠️⚠️ IT DOES NOT WRITE, AND THAT IS DELIBERATE. None of these 27 has a Blade view or a route, and all are
`pending` in MarketingUrls. Creating their views here would do two things this pass must not do: it would
PUBLISH pages whose G1/G2/G3 claims are still being sourced, and it would break the marketing page-count
pin (19), which exists so a page cannot appear without a gate saying so.

⚠️ SO WHAT IS IT FOR? It makes the doc pages CONVERTIBLE and their transforms PROVABLE — the registry now
knows all 27 masters, the spec's transforms run over them, and the registry-wide zero-match check refuses
to finish if any needle stops matching. ⚠️ That is the property that was missing: until now the spec was a
document nothing executed, and a rewrite nothing executes is a rewrite that silently rots.

⚠️ WHEN THESE PAGES ARE BUILT (a later slice, after sourcing), this driver gains the write half that
`scbuild.py` and `salesbuild.py` already have — same shape, same refusal-to-overwrite-a-difference rule.
It is written now so the transforms have a runner, not so the pages have views.

Run:  python3 docsbuild.py           (convert all 27, report)
      python3 docsbuild.py --verbose (also print per-page transform counts)
"""
import sys, os

_HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, _HERE)
import convert as C

KEYS = (
    "docs", "faq", "glossary", "resources",
    "guide-access", "guide-arrival", "guide-channels", "guide-checkin", "guide-gdpr",
    "guide-housekeeping", "guide-payouts", "guide-rates", "guide-refunds", "guide-reports",
    "guide-reservation", "guide-security-doc", "guide-settings", "guide-setup",
    "guide-statements", "guide-storefront",
    "article-direct", "article-housekeeping", "article-oos", "article-owners",
    "article-pricing", "article-statement", "article-trust",
)

VERBOSE = "--verbose" in sys.argv
_ROOT = os.path.dirname(os.path.dirname(_HERE))
VIEWS = os.path.join(_ROOT, "resources", "views", "marketing", "%s.blade.php")

built = 0
for key in KEYS:
    before = len(C._NEEDLE_LOG)
    meta, page, left = C.convert(key)
    fired = sum(1 for _, _, ok in C._NEEDLE_LOG[before:] if ok)

    # ⚠️ A HARD REFUSAL, not a flag: if one of these ever acquires a view, writing it is a publication
    # decision and belongs in the slice that makes it, not in a driver run.
    if os.path.exists(VIEWS % key):
        print("⚠️  %-22s HAS A VIEW — this driver does not write. Publication is a separate decision." % key)
        built += 1
        continue

    if VERBOSE or fired:
        print("%-22s %7d chars · %d transform(s) · leftover handlebars=%d" % (key, len(page), fired, left))

missed = [(k, tag) for k, tag, ok in C._NEEDLE_LOG if not ok]
print("\nhonesty needles: %d attempted, %d matched nothing" % (len(C._NEEDLE_LOG), len(missed)))
for k, tag in missed:
    print("  ⚠️ SILENT NO-OP: %s / %s" % (k, tag))
print("pages with a view (would be a publication): %d of %d" % (built, len(KEYS)))
if missed:
    sys.exit(4)
