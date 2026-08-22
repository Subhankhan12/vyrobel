# The marketing page converter — RECOVERED AND COMMITTED, 2026-08-23

> ⚠️⚠️ **THIS TOOLCHAIN WAS NEVER IN GIT.** For the whole front-website arc — Gates 3b, 4, 4b, 6 and 7 — the
> canon rule *"fixes live IN the converter"* (§N.31.4) was enforced by a file living **only** in a session
> scratchpad under `/private/tmp`, which is garbage-collected. ⚠️ **A rule enforced by an unversioned script
> is not enforced.** It was found intact on 2026-08-23 and committed **verbatim**, unmodified.
>
> ⚠️ **This was luck, not process.** Had the scratchpad cleared first, all 19 published marketing pages would
> have been un-regenerable — the `/pricing` problem multiplied by nineteen, with the generated Blade as the
> only surviving artefact and every honesty transform lost.

## The pipeline

```
docs/design/front/*.dc.html        ← COMMITTED (84 masters, 102 MB)
        │  decode.cjs  (extracts the __bundler/template JSON payload)
        ▼
   tools/marketing/core/*.html     ← DERIVED, gitignored (39 pages regenerate from the masters)
        │  convert.py (918 lines — THE CONVERTER; every honesty transform lives here)
        ▼
   resources/views/marketing/*.blade.php
```

`core/` is deliberately **not** committed: `decode.cjs` regenerates it from the committed masters, so
committing it would be a second copy of derived data (§N.8f). It is gitignored.

| file | lines | what it is |
|---|---|---|
| `convert.py` | 918 | ⚠️ **THE CONVERTER.** The palette map (D6), the page-key map, the link/slot rewrites, and **11 honesty transforms**. |
| `decode.cjs` | 9 | The decoder: one `.dc.html` master → one `core/*.html`. Takes explicit argv paths. ⚠️ `.cjs`, not `.js` — see defect 1. |
| `heads_px.py` | 1 | The per-page provenance docblocks `pxbuild.py` imports. ⚠️ Was missing from the preservation commit. |
| `g7build.py` | 34 | The Gate-7 driver — builds Security · Integrations · About · Contact, and carries their provenance docblocks. |
| `pxbuild.py` | 35 | The **regeneration proof** for the three price-bearing pages. ⚠️ Not "the converter" — see the naming note below. |

## ⚠️ Verified: what it ACTUALLY does vs what five gates CLAIMED

It had been load-bearing across five gates **without ever being read**. It was reviewed on recovery. **Every
claim checked out**; nothing was found to be overstated.

- ✅ **The zero-match rule is real.** `_NEEDLE_LOG` records every needle attempt; `convert.py:913-918` collects
  the misses and **`sys.exit(4)`** when any needle matched nothing. Both drivers replicate it
  (`g7build.py:34`, `pxbuild.py:35`). ⚠️ The reason is written into the file and names its own three
  failures: the B12 price needle spelling `var(--vy-muted)` while the markup still said `#4A403A`; Gate 6's
  DPA removal scoring **zero against all six** DPA mentions on the three pages it was written for; and needles
  transcribed from a truncated dump, against a design that mixes U+2019 with plain ASCII apostrophes.
- ✅ **The punctuation normaliser is real.** `_variants()` tries seven spellings (curly/straight quotes,
  em-dashes, and their HTML-entity forms) so a needle cannot fail on an invisible character.
- ✅ **The DPA transform is real and executes** (`convert.py:858+`) — a `for` loop of before/after pairs, not a
  documented intention. ⚠️ It encodes a genuinely subtle distinction: *asserting the agreement exists* vs
  *specifying that one is required*, so the Privacy draft's "each entry needs a named DPA" survives while
  "our data processing agreement sets that out" is removed.
- ✅ **The 11 honesty needles**: `b12-headline` · `b12-price-binding` · `comparison-table` · `plan-cards` ·
  `seo-per-unit-claim` · `team-block-removed` · `demo-booker-removed` · `demo-anchor-rehomed` ·
  `rail-demo-card` · `form-fields-state` · `form-sent-state`.

## ✅ RUNNABLE FROM A FRESH CHECKOUT (2026-08-23, commit 2 of 2)

```bash
# 1 · decode the committed masters into the DERIVED core/ (gitignored)
python3 - <<'EOF'
import subprocess, sys, os
sys.path.insert(0, 'tools/marketing')
from convert import PAGE_KEY
os.makedirs('tools/marketing/core', exist_ok=True)
for master, key in PAGE_KEY.items():
    subprocess.run(['node', 'tools/marketing/decode.cjs',
                    f'docs/design/front/{master}.dc.html',
                    f'tools/marketing/core/{key}.html'], check=True)
EOF

# 2 · regenerate
python3 tools/marketing/g7build.py     # Security · Integrations · About · Contact
python3 tools/marketing/pxbuild.py     # Pricing · Home · Switching

# 3 · a needle that matched nothing exits 4 — never a silent no-op
```

⚠️ **`f949bc2` committed the toolchain BYTE-IDENTICAL on purpose**, to preserve the provenance of what
actually produced the pages. **This is the separate second commit** that makes it runnable, so the diff
between them shows exactly what changed between *what ran* and *what runs*.

## ⚠️ THE REGENERATION PROOF — and what it caught

Regenerated all seven pages the drivers cover and diffed against the committed Blade:

| page | result |
|---|---|
| security · integrations · about · contact · pricing · home | ✅ **BYTE-IDENTICAL** |
| ⚠️ **switching** | ⚠️ **DIFFERS — and the regeneration is WRONG, not the committed file** |

⚠️⚠️ **THE FINDING: `resources/views/marketing/switching.blade.php` CONTAINS A POST-GENERATION EDIT.** The
`Alternatives`/`Compare` drop (`fa62f82`) removed a dead "Compare the options first" CTA by editing the
**generated Blade directly** instead of adding the transform to the converter. So regenerating `/switching`
today **puts the dropped link back**.

⚠️ **That is §N.31.4 exactly — "a fix applied AFTER generation is undone by the next generation" — committed
by the very pass that was executing the drop.** It was invisible until this pass actually RAN the toolchain,
which is the argument for proving a generator rather than preserving one.

⚠️ **The committed Blade was NOT overwritten.** The regenerated file was discarded and the honest committed
version restored; the converter is the thing that is wrong. ✅ **And the backstop held**: the drop guard in
`MarketingSalesPagesTest` went RED on the regenerated output — *"switching.blade.php still links to the
dropped compare page"* — so this cannot ship silently.

**⛔ OPEN, deliberately not fixed here** (path portability only): the drop transform must move INTO
`convert.py` before `/switching` is regenerated again. Until then, ⚠️ **regenerating that one page
reintroduces a dropped link.**

## ⚠️ Defects found on review — REPORTED, NOT FIXED

The recovery pass committed the toolchain **verbatim**. Changing it is a separate pass with its own proof;
silently "improving" a file the moment it enters version control would destroy the very provenance that makes
committing it worthwhile.

1. ✅ **FIXED 2026-08-23 — and there were SIX path assumptions, not the one that was reported.**
   `convert.py:5` (SRC) · `pxbuild.py:12` (SP) · `:13` and `:14` (two `sys.path.insert`, the second pointing
   at a `g7/` subdirectory) · `pxbuild.py:18` and `g7build.py:15` (VIEWS, this machine's repo path) ·
   `g7build.py:12` (sys.path). All now derived from `__file__`; `VY_MARKETING_CORE` overrides `core/`.
   ⚠️ **And two problems no path audit would have found — only running it did:**
   - ⚠️ **`heads_px.py` was MISSING from the preservation commit.** `pxbuild.py` imports `HEADS` from it, and
     it lived in the scratchpad's `g7/`. The driver could not have run from the repo at all. Now committed.
   - ⚠️⚠️ **`decode.js` BECAME BROKEN BY BEING COMMITTED.** The repo's `package.json` declares
     `"type": "module"`, so a `.js` file here is ESM and `require` is undefined; in the scratchpad there was
     no package.json, so it ran as CommonJS. **Nothing about its contents changed — its context did.**
     Renamed `decode.cjs`.
2. **`convert.bak.py`** (654 lines) is an older copy of the converter left in the scratchpad. Not committed —
   it is superseded, and two copies of a converter is precisely the drift this project keeps closing.
3. ⚠️ **The name in the canon was wrong.** §N.31.4's discussion, the docs-triage report and the unblock report
   all referred to **`pxbuild.py`** as "the converter". It is not — it is a 35-line driver that proves the
   three price pages can be rebuilt. **The converter is `convert.py`.** Corrected in §N.33.

## Not committed, and why

`g5table.py` (Gate-5 audit table builder) · `reach.py` (the reachability sweep instrument) ·
`rewrite.py` / `rewrite_lisbon.py` (one-off page rewrites) · `convert.bak.py` · `core/` (derived) ·
`front-extracted*` (extraction artefacts). These are **audit instruments and one-offs, not the page-generation
toolchain**. ⚠️ `reach.py` is the one with an argument for keeping — it produced the seeder-vs-app reachability
finding and had a documented false-negative bug (it missed `firstOrNew` and `new Model;`) — but it is an
instrument, not a generator, and committing every scratch script is how a `tools/` directory becomes noise.
**Named here so the decision is visible rather than silent.**
