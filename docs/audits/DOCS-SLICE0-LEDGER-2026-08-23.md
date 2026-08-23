# Doc rewrite · Slice 0 — the doc claims enter the ledger

**2026-08-23 · ⚠️ 190 entries added · nothing sourced, cut or softened**

---

## 1 · ⚠️ The CI consequence, established BEFORE anything was entered

**The question:** what does Gate 6 do with an unsourced claim on an *unpublished* page?

**Read first, then PROVEN empirically with a two-entry probe** rather than reasoned:

| assertion | with unsourced entries present |
|---|---|
| *"refuses to publish any claim that is unsourced"* | ✅ **green** — they are unpublishable, which is what it asserts |
| *"THE LEDGER ITSELF carries no stale claim"* | ✅ **green** — ⚠️ `stale()` returns **false** for an unsourced claim (`MarketingClaims.php:91`: *"unsourced is its own, louder failure"*), so they never enter `staleClaims()` |
| *"treats a MISSING review date as stale"* | ✅ green — it tests the predicate on a fixture, not the ledger |
| ⚠️ *"holds every audited claim…"* — **`assertCount(12)`** | ❌ **RED — the only failure** |

⚠️ **So entering the claims does NOT break Gate 6's rules.** It trips **one count pin**, whose own comment
states its purpose: *"If the ledger **shrinks**, a claim left the ledger WITHOUT leaving the pages."* It was
written as an exact count to catch **shrinkage**. ⚠️ **Re-pinning 12 → 202 preserves that purpose exactly —
the assertion still fails the moment the ledger shrinks. That is a re-pin, not a weakening**, and the
freshness teeth were re-proven afterwards (a sourced-but-expired claim still REDs).

### ⚠️ Said plainly, not discovered later
**Entering these claims makes all 26 covered documentation pages UNPUBLISHABLE until each claim is sourced
or cut.** That is the intended effect of slice 0. `pageIsCleared()` now returns **false** for every one of
them, where it returned **true** yesterday.

## 2 · ⚠️ The real count: **160 distinct claims → 190 entries**, not ~210

The triage said ~210; **the measured figure is 160 distinct claims**, entered as **190 rows** (one per
page an instance appears on, because `pageIsCleared()` filters by `page` — a single row would leave 15 of
the 16 guides still failing open).

⚠️ **The method, and its limits, stated honestly.** Claims were extracted by pattern (generalisation ·
quantified figure · comparative · regulatory · third-party) from the 27 decoded masters, deliberately
**over-inclusive**: an extra candidate is noise the founder dismisses, a missed one is a false clearance.
**A sweep proves the absence of its own needles** (§N.31.2) — and it did so twice in this pass:

1. ⚠️ **The FAQ came back with six text blocks and no claims**, which reads exactly like a clean page. It is
   not: **all twelve answers live in JSON-LD**, which the extractor stripped along with the dc-runtime
   `<script>` wiring. They carry *"We never hold client funds"*, *"No exit fee"*, *"we store payment
   references rather than card numbers"*. Fixed and re-run.
2. ⚠️ **Tag-stripping fused headings into the sentence after them** (*"1 Choose what you must collect Guest
   names … are legally required"*). A ledger entry whose text is not exactly the claim is half a record, so
   the extractor now respects block boundaries.

⚠️ **`glossary` remains uncovered** — its terms are data-driven in a form the sweep could not reach, so it
has **no entries and still reads as CLEARED**. Recorded in the pin itself, not hidden.

**Nothing was paraphrased.** Every `claim` field is the sentence as it appears in the master.

### Cross-page duplicates recorded
Every entry carries `appears_on`, so **one ruling settles every instance**. ⚠️ The find that matters:
**"Support replies within a working day, and faster during your onboarding."** is in the **chrome of all
sixteen guides** — a service commitment repeated sitewide. **One ruling, sixteen pages.** (The chrome has
carried an untrue claim five times before; this is the sixth.)

## 3 · ⚠️ THE GROUPING — six groups, chosen by *what would settle it*

⚠️ **The axis is "what kind of evidence would dispose of this", because that is exactly the shape of the
founder's decision** — each group needs one kind of ruling, so one decision disposes of the whole group.
Grouping by page would give 26 decisions; by claim, 160.

| group | claims | pages | what a source would have to be | what a cut costs |
|---|---|---|---|---|
| **G1 · industry statistic** | 10 | 5 | a published market report or a marketplace's own documentation | the sentence |
| **G2 · third-party mechanics** | 35 | 13 | that vendor's published terms or docs | usually a paragraph of the explanation |
| **G3 · regulatory** | 27 | 19 | the statute or regulator, **jurisdiction-scoped** — ⚠️ *"in many jurisdictions"* is not sourceable as written | a paragraph, sometimes a section |
| ⚠️ **G4 · our own product** | 5 | 5 | ⚠️ **no URL — settled by CODE or a TEST** | nothing: they are true or they are bugs |
| ⚠️ **G5 · service commitment** | 17 | 16 | ⚠️ **no source can settle a promise** — the founder commits operationally, or it is cut | one line of chrome, sitewide |
| ⚠️ **G6 · unverifiable in principle** | 96 | 23 | ⚠️ **none exists** — *"most…"*, *"usually…"*, *"nobody…"* about operators at large | **cut, or rewrite as explicitly our opinion** |

### ⚠️ The cheap wins — sourceable from our OWN code, not a third party
**G4 (5 claims)** are about Vyrobel itself — *"Everyone who touches Vyrobel gets their own login"*,
*"Vyrobel does not file on your behalf"*. These are settled by reading the code or a test. ⚠️ **The current
schema cannot express that**: `source_url` expects a URL, so they sit unsourced and will block their pages
forever unless a `kind: internal` (with a code/test reference) is added. ⚠️ **Surfaced, not resolved** — I
did not invent that field.

### ⚠️ The ones that can only be cut
**G6 is 60% of the total.** No source exists for an unattributed generalisation about operators at large.
⚠️ **Saying so now saves the founder from trying to source ninety-six claims that cannot be sourced.**
Realistically: cut them, or rewrite them as explicitly Vyrobel's opinion — which is a rewrite, not a source.

## 4 · Nothing sourced, cut or softened
No source was researched, no claim removed, no wording weakened. Every entry is `source_url: null`,
`disposition: 'publish'` — **B8's own state**, meaning *intended for publication, pending evidence*.
⚠️ Choosing any other disposition would have pre-empted the ruling.

## 5 · The coverage pin — ⚠️ and what it is NOT

`MarketingClaimsTest` now asserts all **26 covered pages** have entries **and read as not-cleared**.
**Mutation-proven**: deleting `article-oos`'s 7 entries → **RED**; restored byte-identical. It carries its
own **vacuity check** — a test asserting `glossary` (entered by nobody) still reads as **cleared**, so if
the fail-open premise ever changes, the pin's justification is re-examined rather than silently stale.

⚠️⚠️ **THE PIN IS NOT THE FAIL-OPEN FIX.** It protects *this pass's work*. **Any future page nobody enters
still reads as CLEARED** — including `glossary`, today.

## 6 · ⛔ The residual fail-open — reported, not built

`pageIsCleared()` returns true for a page with no entries. Making it fail **closed** would mean: *a page may
not publish unless the ledger has been consulted for it.*

⚠️ **What it would break:** every page with no entries becomes unpublishable — **including legitimately
claim-free ones**, and including the 19 already-published marketing pages, which would go from publishable
to blocked overnight.

**Options (founder's ruling, not mine):**
1. **An explicit `"no claims"` declaration per page** — a page must be *listed* as claim-free, so silence is
   never the same as clearance. Cheapest; makes the absence deliberate.
2. **A coverage requirement at publish time** — the driver refuses to emit a page absent from the ledger's
   coverage list. Puts the check where publication happens rather than in a test.
3. **A registry of claim-free pages** — a variant of (1) held centrally rather than per page.

⚠️ **I recommend (1)**, because it turns "nobody looked" into a statement somebody had to write.

## 7 · What this brief got wrong

| claim | verdict |
|---|---|
| entering unsourced claims might red CI outright | ⚠️ **it reds exactly one assertion — the count pin**, and only because it is written as an exact number to catch shrinkage |
| `stale()` "defers to the louder unsourced check" | ✅ **confirmed** at `MarketingClaims.php:91` |
| ~210 unsourced claims | ⚠️ **160 distinct** (190 entries). The triage's figure was an estimate |
| the ledger holds 12 entries, all on alternatives/compare | ✅ confirmed |
| "40 groups means this pass failed" | six groups — and the axis is *what evidence would settle it*, which maps 1:1 onto the ruling |
