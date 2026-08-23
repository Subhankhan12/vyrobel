"""Regenerate the four EXAMPLE-SCENARIOS pages from the converter.

⚠️⚠️ THIS DRIVER IS THE POINT, NOT A CONVENIENCE — and its absence was the whole defect. `case-studies`,
`story-cyclades`, `story-lisbon` and `story-rotterdam` carried ruling A's honesty rewrites in their
GENERATED OUTPUT ONLY, applied by two one-off scripts that edited the committed Blade files. There was no
driver, so nobody could regenerate these pages — and anybody who did would have silently RESTORED five
invented, unconsented customer identities (Kastellis Villas · Van Dijk Stays · Hotel Alfama Reyes ·
Havenzicht · Casa Mirante) onto a live public site.

⚠️ WHAT THIS SUPPLIES BEYOND THE BODY TRANSFORMS. Roughly a third of the rewrite never lived in the body at
all, so a converter-only fix would have been incomplete:
  · the per-page provenance docblock (`heads_sc.py`) — hand-written, and therefore itself a post-generation
    edit that must be EMITTED or the page stays un-reproducible (the /switching precedent);
  · `$testimonialQuotes = []`, as both existing drivers emit;
  · the shell `title=` and `description=`, which come from `convert()`'s meta — the recovered specs rewrite
    both on every page, and neither is a body transform;
  · ⚠️ `:index="false"` — these four are deliberately NOT indexed, and NEITHER EXISTING DRIVER EMITS IT.

⚠️ IT REFUSES TO OVERWRITE A PAGE IT WOULD CHANGE. This is the one deliberate difference from `pxbuild.py`
and `g7build.py`, and it is not gold-plating: these are LIVE pages whose honesty is the thing at stake, and
the committed version is the only known-correct one. A difference means a transform is unfaithful or a hunk
is unaccounted for — both are FINDINGS, and neither is repaired by making the file match. Set VY_SC_FORCE=1
to write anyway, deliberately.

Run:  python3 scbuild.py              (check all four; write only what is already identical)
      python3 scbuild.py story-lisbon (a subset, for a staged proof)
"""
import sys, io, html, os, difflib

_HERE = os.path.dirname(os.path.abspath(__file__))
_ROOT = os.path.dirname(os.path.dirname(_HERE))
sys.path.insert(0, _HERE)
import convert as C
from heads_sc import HEADS

VIEWS = os.path.join(_ROOT, "resources", "views", "marketing", "%s.blade.php")
KEYS = ("case-studies", "story-cyclades", "story-lisbon", "story-rotterdam")

FORCE = os.environ.get("VY_SC_FORCE") == "1"
keys = [k for k in sys.argv[1:] if not k.startswith("-")] or list(KEYS)

drift = 0
for key in keys:
    meta, page, left = C.convert(key)
    title = html.unescape(meta["title"]).replace('"', "&quot;")
    desc = html.unescape(meta["description"]).replace('"', "&quot;")
    out = (HEADS[key] + "\n@php\n    $testimonialQuotes = [];\n@endphp\n"
           + '<x-marketing.shell\n    title="' + title + '"\n    description="' + desc + '"\n'
           + '    canonical="' + meta["canonical"] + '"\n    :index="false">\n'
           + page + "\n</x-marketing.shell>\n")

    path = VIEWS % key
    old = io.open(path, encoding="utf-8").read() if os.path.exists(path) else None

    if old is not None and old != out and not FORCE:
        drift += 1
        print("⚠️  %-16s DIFFERS from the committed page — NOT WRITTEN." % key)
        for line in list(difflib.unified_diff(old.split("\n"), out.split("\n"),
                                              "committed", "generated", lineterm="", n=1))[:40]:
            print("     " + line[:150])
        continue

    io.open(path, "w", encoding="utf-8").write(out)
    print("%-16s %6d chars  %s" % (key, len(out),
          "identical" if old == out else "⚠️ WRITTEN (forced)"))

missed = [(k, tag) for k, tag, ok in C._NEEDLE_LOG if not ok]
print("\nhonesty needles: %d attempted, %d matched nothing" % (len(C._NEEDLE_LOG), len(missed)))
for k, tag in missed:
    print("  ⚠️ SILENT NO-OP: %s / %s" % (k, tag))
if missed:
    sys.exit(4)
if drift:
    print("\n⚠️ %d page(s) would change. Every difference is a FINDING, not a file to overwrite." % drift)
    sys.exit(5)
