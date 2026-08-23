"""Regenerate the eight SALES + PRODUCT pages from the converter.

⚠️⚠️ THESE PAGES WERE BUILT AND LIVE WITH NO DRIVER — the same landmine the scenarios port defused, still
armed. `features`, `for-hotels`, `for-apartments`, `for-villas`, `how-it-works`, `modules`, `product` and
`try-booking` are published, and until now nothing could regenerate them. ⚠️ THAT MEANS A POST-GENERATION
EDIT TO ANY OF THEM WAS UNDETECTABLE: the converter's output and the committed file could drift apart with
nothing to notice, which is exactly how four scenarios pages came to hold honesty rewrites that a
regeneration would have destroyed.

⚠️ THIS DRIVER IS WIRING, NOT NEW TRANSFORM WORK. All eight were verified to convert byte-identical to what
is committed BEFORE this file was written — so it makes an existing property checkable, and changes nothing.
⚠️ If that ever stops being true the driver refuses to write (see below) rather than silently overwriting a
live page, which is the rule the scenarios driver established.

Run:  python3 salesbuild.py            (all eight)
      python3 salesbuild.py product    (one)
"""
import sys, io, os, difflib

_HERE = os.path.dirname(os.path.abspath(__file__))
_ROOT = os.path.dirname(os.path.dirname(_HERE))
sys.path.insert(0, _HERE)
import convert as C
from heads_sales import HEADS, SHELLS

VIEWS = os.path.join(_ROOT, "resources", "views", "marketing", "%s.blade.php")
KEYS = ("features", "for-apartments", "for-hotels", "for-villas",
        "how-it-works", "modules", "product", "try-booking")

FORCE = os.environ.get("VY_SALES_FORCE") == "1"
keys = [k for k in sys.argv[1:] if not k.startswith("-")] or list(KEYS)

drift = 0
for key in keys:
    _, page, left = C.convert(key)

    # ⚠️ THE SHELL BLOCK IS REPRODUCED VERBATIM, not rebuilt from meta. These eight differ from one another
    # in which attributes they carry, and reconstructing that from `meta` would be guesswork dressed as
    # generation — the failure the scenarios reconstruction was careful to avoid.
    out = HEADS[key] + "\n@php\n    $testimonialQuotes = [];\n@endphp\n" + SHELLS[key] + "\n" + page + "\n</x-marketing.shell>\n"

    path = VIEWS % key
    old = io.open(path, encoding="utf-8").read() if os.path.exists(path) else None

    if old is not None and old != out and not FORCE:
        drift += 1
        print("⚠️  %-16s DIFFERS from the committed page — NOT WRITTEN." % key)
        for line in list(difflib.unified_diff(old.split("\n"), out.split("\n"),
                                              "committed", "generated", lineterm="", n=1))[:30]:
            print("     " + line[:150])
        continue

    io.open(path, "w", encoding="utf-8").write(out)
    print("%-16s %7d chars  %s" % (key, len(out), "identical" if old == out else "⚠️ WRITTEN (forced)"))

missed = [(k, tag) for k, tag, ok in C._NEEDLE_LOG if not ok]
print("\nhonesty needles: %d attempted, %d matched nothing" % (len(C._NEEDLE_LOG), len(missed)))
for k, tag in missed:
    print("  ⚠️ SILENT NO-OP: %s / %s" % (k, tag))
if missed:
    sys.exit(4)
if drift:
    print("\n⚠️ %d page(s) would change. Every difference is a FINDING, not a file to overwrite." % drift)
    sys.exit(5)
