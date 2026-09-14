# One per-unit plan relabelled every flat plan

**2026-09-04.** The last open item in the billing section, first named in `PRICING-PAIRING.md` §4.

## The defect

`shape()` scanned the whole catalogue and returned `per_unit` if **any** plan was — and its answer was
stamped onto **every** card. So the day one per-unit plan went live, every flat plan on `/pricing` would
read **"/ unit / mo"**: an operator seeing **€49 per unit per month** against a plan that charges €49 flat,
on the page where money is decided.

⚠️ **Same shape as the last two passes.** `tierKey()` derived a *tier* from a code and one plan took
another's whole card; `shape()` derived a *suffix* from the catalogue and one plan relabelled all of them.
**A property derived from the catalogue when only the row can answer it.**

## #1 · Every method classified — and the count moved from one to **nine sites**

| method | answers for | verdict |
|---|---|---|
| `tierKey($code)` | a plan (takes a code) | ✅ correct |
| `tiers($interval)` | the catalogue — **each row carries its own `shape`** | ✅ correct |
| **`shape($interval)`** | the catalogue | ⚠️ **defect when read per card**; legitimate for page prose — but it had only two answers |
| **`suffix($interval)`** | the catalogue | ⚠️ **THE RELABEL** |
| **`tiersForDisplay()` → `sub`** | the catalogue | ⚠️ **defect** — the card subtitle |
| **`tiersWithCycles()` line 262** | the catalogue | ⚠️ **the injection point** — stamps one suffix onto every row |
| `cycleUnavailableNote($interval)` | the catalogue | ✅ about an absent cycle, not a shape |
| `comparison()` | the catalogue | ✅ feature rows are shape-agnostic |
| **`headline($interval)`** | the **page** | ✅ legitimately page-level — but its copy asserted one model |
| **`priceNote($interval)`** | the **page** | ✅ page-level — same |
| **`whoPays($interval)`** | the **page** | ✅ page-level — same |
| `isPlaceholder()` | the catalogue | ✅ correct — it *is* a catalogue-wide question |
| `fromConfig($interval)` | the catalogue fallback | ✅ correct |

**Plus two consumers outside the class:** `plan-cards.blade.php` and `plan-comparison.blade.php` read the
row's `suffix` (fixed by the injection point), and ⚠️ **`home.blade.php` called `suffix()` twice directly**
— a second surface reading the same wrong way.

⚠️ **Three are catalogue-wide LEGITIMATELY.** A headline describing the product is not a per-plan fact, and
forcing it per plan would be wrong. What they needed was a **third answer**, not a per-plan read.

## #2 · Read from the row — and the per-plan read already existed

`tiers()` already carried `'shape' => $p->pricing_type` on every row. Nothing new was derived; the fix
**consumes what was already there**. `Plan::isPerUnit()` also already exists, so no third implementation was
written.

⚠️ **`suffix()` now REQUIRES a shape argument** — `suffix(string $shape, string $interval)`. The
catalogue-wide misuse is no longer a mistake anyone can make; it will not compile.

⚠️ **Shape does NOT have the tier's disease.** `plans.pricing_type` is a **real column**; a tier is inferred
from the spelling of a code. The recorded durable answer for the tier — *make it a column* — does not apply
here, because shape already is one. This was purely a matter of asking the wrong object.

## #3 · What a mixed catalogue reads

| surface | flat card | per-unit card |
|---|---|---|
| price suffix | `/ mo` · `/ yr` | `/ unit / mo` · `/ unit / yr` |
| card subtitle | "One price, any number of units" | "Per active unit" |

**The fix is not "make them all flat"** — each card states its own model, which is the minimum a visitor
needs to compare €49 flat against €12 per unit at all.

⚠️ **The comparison table CAN be honest, and needed no refusal.** Its feature rows are shape-agnostic — a
feature is a feature — and only the price header asserts a model, which already renders **each column's own
suffix** from the same injection point. Fixing that fixed the table too.

⚠️ **No fabricated equivalence.** Nothing multiplies a unit rate by a unit count; a test asserts the page
never prints €60.00/€120.00/€24.00/€36.00 beside a €12.00 unit rate.

**Page-level prose gained a third state** rather than picking one of two falsehoods:

| | flat | per-unit | **mixed** |
|---|---|---|---|
| headline | "One price per property account…" | "One price per unit…" | "Priced per account or per unit, depending on the plan…" |
| price note | "per account, per month, whatever your unit count" | "per active unit, per month" | "per account or per active unit depending on the plan" |
| who pays | "You pay one subscription…" | "You pay per unit…" | "You pay for the plan you choose…" |

## #4 · The cycle switcher

Both cycles still render on every card with pairing **by identity**; a per-unit plan present in only one
interval shows an honest absent cycle rather than a fabricated price; and both prices still ship in the
served HTML so the scripts-off state stays readable — all asserted with a mixed catalogue.

## #5 · Why billing and marketing differ, and both are right

**Billing reads the SNAPSHOT** — the terms frozen at signup, so a subscriber keeps what they agreed to.
**Marketing reads the LIVE catalogue** — a visitor must see today's offer. Different questions, different
sources, deliberately.

⚠️ **No second source for the shape was found.** Unlike the feature list — which once read `config` while
the gate read `plan_features` — the shape is read from `plans.pricing_type` on both sides: marketing through
`tiers()`, billing through `Plan::isPerUnit()`/`PlanTerms`. They cannot disagree about a live plan.

## #6 · Nothing typed, nothing claimed

Every figure still derives from plan rows. No saving figure, percentage, struck-through price or badge; the
percentage, refused-tone, structured-data and false-claims guards stay green. The placeholder signals still
render — `priceNote()` composes the "indicative" clause around the new wording rather than displacing it. No
new colour or text was introduced, so no new contrast measurement was owed.

⚠️ **One typed claim reported, not fixed:** `home.blade.php:996` hardcodes *"one price, any number of
units"* in prose. It is not derived, so it cannot be relabelled — but it becomes **false** if that tier ever
turns per-unit. Out of this pass's scope (per-plan shape *reads*); recorded here.

## #8 · Mutations — and one found a gap in my own tests

| mutation | landed | result |
|---|---|---|
| **wrong fix** — make the suffix catalogue-wide again | ✅ | **RED** |
| **wrong fix** — make every card read per-unit | ✅ | **RED** |
| card subtitle back to catalogue-wide | ✅ | **RED** |
| drop the MIXED state | ✅ | **RED** |
| the HOME cards back to a catalogue-wide suffix | ✅ | ⚠️ **GREEN twice, then RED** |

⚠️ **The home mutation was green twice, and both reasons were mine.** First, every assertion read
`/pricing` — the home page renders its own two cards from a separate call, and a second surface reading the
same wrong way is exactly how the first one survived. Then, with a home probe added, `toContain('/ unit /
mo')` on the whole document was satisfied by an occurrence **elsewhere on the page**. Fixed by reading the
suffix span that immediately follows each card's own priced span — **asserted per card, not per page.**

M1 re-run against the final code; both files byte-identical after restore.

## ⚠️ Where this brief was wrong

It expected the comparison table might be unable to present mixed shapes honestly and told me to report
rather than invent. **It can** — each column already carries its own price and suffix, so it needed the same
per-plan read and no refusal. And it framed `shape()` as needing to become per-plan; the accurate split is
that **`shape()` is legitimately page-level and needed a third answer**, while the per-card sites needed to
stop calling it at all.

## ⚠️ What I cannot prove

That the mixed wording is the copy the founder wants — it is minimal and true, derived the same way the
existing two branches were, but copy is not this build's business. And no per-unit plan has ever been
activated, so every assertion here rests on a catalogue built in a test, not on one that has ever shipped.
