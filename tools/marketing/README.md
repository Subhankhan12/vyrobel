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
        │  decode.js  (9 lines — extracts the __bundler/template JSON payload)
        ▼
   core/*.html                     ← DERIVED, not committed (50 files, ~4.5 MB)
        │  convert.py (918 lines — THE CONVERTER; every honesty transform lives here)
        ▼
   resources/views/marketing/*.blade.php
```

`core/` is deliberately **not** committed: `decode.js` regenerates it from the committed masters, so
committing it would be a second copy of derived data.

| file | lines | what it is |
|---|---|---|
| `convert.py` | 918 | ⚠️ **THE CONVERTER.** The palette map (D6), the page-key map, the link/slot rewrites, and **11 honesty transforms**. |
| `decode.js` | 9 | The decoder: one `.dc.html` master → one `core/*.html`. Takes explicit argv paths. |
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

## ⚠️ Defects found on review — REPORTED, NOT FIXED

The recovery pass committed the toolchain **verbatim**. Changing it is a separate pass with its own proof;
silently "improving" a file the moment it enters version control would destroy the very provenance that makes
committing it worthwhile.

1. ⚠️⚠️ **HARDCODED ABSOLUTE SCRATCHPAD PATHS.** `convert.py:5` sets
   `SRC = "/private/tmp/claude-501/…/af05ac42-…/scratchpad/core"`, and both drivers `sys.path.insert` the same
   absolute directory. **So as committed, this toolchain does not run on any other machine, or on this one
   once the scratchpad clears.** It is preserved and readable, but not yet runnable-from-checkout. Making the
   paths relative is the obvious next step and is deliberately left undone here.
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
