"""Regenerate the three PRICE-BEARING marketing pages from the converter.

⚠️ THIS SCRIPT IS THE POINT, NOT A CONVENIENCE. `/pricing` had become un-regenerable: B12's card binding was
hand-edited into the generated file, so running the converter over it would have silently restored the
design's four hardcoded tiers. The binding now lives in <x-marketing.plan-cards />, the converter swaps the
design's cards section for that tag, and this script is what proves the page can be rebuilt from its source
without losing the fix.

Run: python3 pxbuild.py
"""
import sys, io, html
SP = "/private/tmp/claude-501/-Applications-XAMPP-xamppfiles-htdocs-vyrobel/af05ac42-2005-46d3-adbc-b1ad64cde87a/scratchpad"
sys.path.insert(0, SP)
sys.path.insert(0, SP + "/g7")
import convert as C
from heads_px import HEADS

VIEWS = "/Applications/XAMPP/xamppfiles/htdocs/vyrobel/resources/views/marketing/%s.blade.php"

for key in ("pricing", "home", "switching"):
    meta, page, left = C.convert(key)
    title = html.unescape(meta["title"]).replace('"', "&quot;")
    desc = html.unescape(meta["description"]).replace('"', "&quot;")
    out = (HEADS[key] + "\n@php\n    $testimonialQuotes = [];\n@endphp\n"
           + '<x-marketing.shell\n    title="' + title + '"\n    description="' + desc + '"\n'
           + '    canonical="' + meta["canonical"] + '">\n' + page + "\n</x-marketing.shell>\n")
    io.open(VIEWS % key, "w", encoding="utf-8").write(out)
    print("%-12s %6d chars  (leftover handlebars=%d)" % (key, len(out), left))

missed = [(k, tag) for k, tag, ok in C._NEEDLE_LOG if not ok]
print("\nhonesty needles: %d attempted, %d matched nothing" % (len(C._NEEDLE_LOG), len(missed)))
for k, tag in missed:
    print("  ⚠️ SILENT NO-OP: %s / %s" % (k, tag))
if missed:
    sys.exit(4)
