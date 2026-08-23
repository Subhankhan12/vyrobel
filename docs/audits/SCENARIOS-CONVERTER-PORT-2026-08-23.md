# The four example-scenarios pages, ported into the converter

**2026-08-23 · ⚠️ four LIVE pages · the largest §N.31.4 landmine found so far**

---

## 1 · What was actually wrong

`case-studies`, `story-cyclades`, `story-lisbon` and `story-rotterdam` carried ruling A's honesty rewrites
**in generated output only**. Two one-off scripts had edited the committed Blade files; the converter still
emitted the originals. So **regenerating any of these four pages would have republished five invented,
unconsented businesses** — Kastellis Villas · Van Dijk Stays · Hotel Alfama Reyes · Havenzicht Stays ·
Casa Mirante — onto a live public site, silently.

⚠️ These pages also had **no driver at all**, which is why nobody had regenerated them and why the landmine
survived. Roughly a third of the rewrite never lived in the body: the shell `title`/`description`, the
per-page provenance docblock, and `:index="false"` — ⚠️ **the last of which neither existing driver emits.**

## 2 · ⚠️ Provenance — a green diff means two different things

| page | SPEC-DERIVED | RECONSTRUCTED | total |
|---|---|---|---|
| `story-cyclades` | **11** | 1 | 12 |
| `story-lisbon` | **10** | 0 | 10 |
| `story-rotterdam` | 0 (+1 by extension) | **13** | 14 |
| `case-studies` | 0 (+1 by extension) | **24** | 25 |
| | **23** | **38** | **61** |

- **SPEC** — implements `tools/marketing/spec/rewrite.py` / `rewrite_lisbon.py`, the recovered scripts that
  performed the original rewrite. Green means *faithful to the record*.
- **RECON** — reconstructed from the diff, because **no spec survives** for two of the four pages. Green
  means only *reproduces the current file*, which a lucky positional match would also satisfy. Every RECON
  needle therefore anchors on **content** — the invented name, the attributed quote, the fabricated figure,
  the offer's own words — and none on position, index or sibling count.

⚠️ **The two halves were built and proven separately, in that order**, so that any failure in the second
half would have been attributable to the reconstruction rather than to the driver.

## 3 · ⚠️⚠️ THE RECOVERED SPEC IS INCOMPLETE EVEN FOR THE PAGES IT COVERS

`story-cyclades` needed one hunk `rewrite.py` does not contain: the callout card claiming
**"4 days → 1 afternoon"**, a *measured result for a business that does not exist* (condition 3). Its sibling
card survives in the committed page ("Direct share up" — directional, unquantified), so the original rewrite
was **surgical**, and this transform is too.

Something else did that work and left no record. **So "we recovered the spec" was never the same as "we have
the spec"** — worth stating, because it is the difference between a green diff meaning *faithful* and meaning
*reproduces today*.

## 4 · ⚠️ Two ordering findings

1. **The spec's needles are written against the FINISHED file.** Registered mid-pipeline, four matched and
   two did not: at that point the breadcrumb still reads `color: #2B2320`, because the palette→token binding
   runs later. ⚠️ **The fix was to move the block to the end, not to rewrite the needles** — rewriting them
   would have silently forked the port away from the record it exists to implement.
2. **The blockquote step looked obsolete and was not.** Transform (2) already strips the quote *element*
   upstream, so `<blockquote` is gone — but only the element, leaving its **line** as pure indentation, and
   the page stopped reproducing. ⚠️ **There is no content to anchor that hunk on, and that is the nature of
   the hunk: the content is precisely what was removed.** It anchors on a *property* of the line, never a
   position.

## 5 · Verification

- ⚠️ **All four regenerate BYTE-IDENTICAL**, and the driver **refuses to overwrite a page it would change**
  (exit 5) — these are live pages whose honesty is the thing at stake.
- ⚠️ **Idempotent**: regenerated twice, identical both times. The LABEL is emitted as a **replacement**, not
  an insert, so it cannot double.
- ⚠️ **Zero-match mutation-proven on all four pages** — 8 mutations, each RED at exit 4 **naming the exact
  transform**; `convert.py` restored byte-identical (md5 checked).
- ⚠️ **The backstop still fires**: reintroducing "Kastellis Villas" turned `MarketingScenarioTest` RED
  against the **rendered** page; restored → green. A port with a dead guard would have been worse than the
  landmine.
- The other **15 pages** still regenerate byte-identical (`pxbuild.py`, `g7build.py`).

⚠️ **A §N.4p harness failure worth recording:** the first mutation run produced both a false-RED and a
stale result, because a restored `convert.py` matched the previous mutation's **size and mtime second**, so
Python reused a stale `.pyc`. Mutation harnesses over Python modules must run `-B` and clear `__pycache__`.

## 6 · What this brief got wrong

| claim | verdict |
|---|---|
| "4 hunks per spec target the shell `title=`/`description=`" | ⚠️ imprecise — **2 hunks** per spec (title + description), spanning 4 lines |
| the spec covers `story-cyclades` and `story-lisbon` | ✅ confirmed |
| `case-studies` is −70/+20, "most likely three invented customer cards" | ⚠️ **half right** — the cards were **renamed**, not deleted; the −70 is three *results panels*, three quoted `<h2>`s and a **"Want to talk to one of them?" introduction offer** |
| the four pages have no driver | ✅ confirmed — and the shell also needs `:index="false"`, which the brief did not mention |
| the LABEL insert is not idempotent | ✅ correct in principle; in the converter the source is re-read each run, so it is written as a replacement and proven idempotent |

## 7 · Reported, not fixed (#9)

- ⚠️ **`case-studies` and `story-rotterdam` cut their equivalent callout cards differently** — rotterdam
  removes the whole card, cyclades removes only its contents and leaves the empty card. Reproduced as
  committed rather than made consistent: porting, not improving. **Whether that empty card should stay is a
  presentation call for the founder.**
- ⚠️ The `>Illustrative story<` register fix and the two `Van Dijk Stays` cards **could not be collapsed into
  one shared transform** (§N.8f) because the recovered spec assigns **different replacement text per page**.
  Following the record rather than unifying it.
