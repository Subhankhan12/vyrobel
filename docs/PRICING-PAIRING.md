# `/pricing` — the cycle pairing, and every plan state the page must survive

**2026-09-02.** Fixes a hard 500 on the page where money is decided, and the silent case behind it.

---

## 1 · What was wrong, reproduced before it was fixed

Both `/pricing` surfaces rendered **two billing cycles** by building **two independent lists** —
`MarketingPricing::tiersForDisplay('monthly')` and `…('yearly')` — and then pairing them **by position**:

| surface | line | the pairing |
|---|---|---|
| `components/marketing/plan-cards.blade.php` | 89 | `$mkRow = $mkCycleTiers[$loop->parent->index]` |
| `components/marketing/plan-comparison.blade.php` | 79 | `$cmpTiers[$loop->parent->index]['formatted']` |

**Two places, not one.** Fixing only the cards would have left the comparison table one section below
carrying the same defect on the same page.

### 1a · The 500 — reproduced

A third plan, monthly only (`zz_probe_monthly`, €69, active):

```
monthly = ["essential_monthly","zz_probe_monthly","pro_monthly"]   (3)
yearly  = ["essential_yearly","pro_yearly"]                        (2)
GET /pricing -> 500
```

> `Undefined array key 2 (View: …/plan-cards.blade.php)` at compiled line 51 — source line **89**,
> index **2** against a yearly list of length 2.

⚠️ **The admin panel creates ONE plan per interval** (`interval` is a required, single-valued field on
`Admin\PlanController::store`). A founder adding a third tier therefore **cannot avoid** passing through
this state. It also requires activation — a new plan is created inactive and `PlanIntegrity` blocks
activation until a Stripe Price id exists — so the exact sequence is *create → paste Price id → activate →
the public pricing page is down until the second interval is created and activated too.*

### 1b · ⚠️⚠️ The silent case — also reproduced, and worse

`tiers()` orders by `price_minor` **independently per interval**. Two lists of **equal length in a
different order** therefore transpose with **no error at all**. Adding `zz_probe_yearly` at €100:

```
monthly = ["essential_monthly","zz_probe_monthly","pro_monthly"]
yearly  = ["zz_probe_yearly","essential_yearly","pro_yearly"]      ← different rank
GET /pricing -> 200
```

The rendered page:

| card | monthly shown | yearly shown |
|---|---|---|
| **Essential** | €49.00 | **€100.00** ← `zz_probe_yearly` |
| **ProbeTier** | €69.00 | **€490.00** ← `essential_yearly` |
| Pro | €99.00 | €990.00 |

**A false price under the right plan name, HTTP 200.** A status-only test passes. So does
`MarketingPriceSourceTest`, which checks that every figure is *a price the engine charges* — €490 is.

### 1c · Do today's four rows align by luck?

**Yes — by luck, and it should be said plainly.** Nothing enforces that the two lists agree. They line up
only because the yearly prices happen to preserve the monthly rank (€49 < €99 and €490 < €990). A
deliberate annual promotion on the cheaper tier — €600 Essential against €500 Pro — inverts the rank and
silently swaps **both** columns. No mispairing has shipped; nothing was preventing one.

---

## 2 · The fix: pair by identity, or don't pair

`MarketingPricing::tiersWithCycles()` returns **one row per tier carrying every cycle**:

```php
['key' => 'essential', 'name' => 'Essential', 'features' => [...],
 'cycles' => ['monthly' => <row|null>, 'yearly' => <row|null>]]
```

### Why mispairing is now *impossible*, not guarded

A plan row is filed under the group **its own `key`** names, at the cycle **its own `interval`** names.
**No index is computed anywhere**, so there is no arithmetic that can be wrong. A card's yearly figure is
that tier's own yearly row or it is absent. Ordering, list length and price rank cannot enter into it.

⚠️ A bounds check would have been the wrong fix: it stops the crash and leaves §1b untouched.

### The identity

`MarketingPricing::tierKey()` — the interval suffix stripped from the plan code. It replaces
`str($code)->before('_')`, which existed in **two** places, agrees with the new form on every code that
exists today, and is wrong on the first multi-word one: `pro_plus_monthly` → `pro`, colliding with the Pro
tier and handing that card **Pro's feature list**. One definition, and the safer of the two (§N.8f).

### A missing cycle

`null` — never `0`, never blank, and never derived. The block still ships, carrying
`cycleUnavailableNote()` ("Annual billing not offered"):

- **Not omitted**, because with scripts off both cycles are visible and the script collapses one; an
  omitted block leaves a **hole where a price belongs** the moment a visitor presses "Annual".
- **Never 12 × the monthly figure.** That is a price the engine would not charge, on the page where money
  is decided — and `isPlaceholder()` is true, so it would be arithmetic over numbers nobody has set.
- The switcher itself is unchanged: both controls stay, and both cycles remain present in equal number
  (which `MarketingProgressiveEnhancementTest` already pins).

⚠️ The sentence is **copy** and the founder may reword it. What must not change is that it carries no figure.

---

## 3 · Every state an admin can reach — and what `/pricing` does

Probed in a rolled-back transaction against the real catalogue. **Nothing throws in any state.**

| # | state | `/pricing` | verdict |
|---|---|---|---|
| 1 | a tier in **one interval only** | 3 cards; that tier shows the honest note for the missing cycle | ✅ **legitimate** |
| 2 | a tier that is **annual only** | a column of its own, monthly noted absent | ✅ **legitimate** — an annual-only enterprise tier is a normal product |
| 3 | **two plans sharing a name** | two cards, both labelled the same | ✅ renders correctly; confusing to a reader, not wrong |
| 4 | one interval **deactivated** | that cycle shows the honest note | ✅ **legitimate** |
| 5 | one interval **soft-deleted** | ⚠️ **still advertised** | ❌ **a real defect — see §4** |
| 6 | **interval edited** after creation | correct; the tier moves in the ordering | ✅ survives |
| 7 | a plan with **no `plan_features` rows** | falls back to the config catalogue's labels | ✅ documented behaviour |
| 8 | a **multi-word code** (`pro_plus_monthly`) | its own tier, distinct from `pro` | ✅ (would have collided before) |
| 9 | a **per-unit plan beside flat ones** | ⚠️ every card reads "/ unit / mo" | ❌ **a real defect — see §4** |

---

## 4 · ⚠️ REPORTED, NOT ADDED — plan validation is a money path

None of the following were changed. Each is its own decision.

**4a · A soft-deleted plan keeps selling — ✅ FIXED 2026-09-03, see `docs/SOFT-DELETE-SCOPE.md`.** It was
one of **twelve** sites of the same mechanism, all now closed and guarded as a class. ⚠️ Two corrections to
what was written below: the scope list was **checked, not guessed** — `Plan` carries *only*
`SoftDeletingScope`, so the fix was to drop the call entirely rather than name a tenant scope that does not
exist on it; and there is **no admin delete path for plans at all**, which makes this latent rather than
live. The serious finding was elsewhere: a live subscriber's **billing page 404s** on a deleted plan.
The original note read: `tiers()` uses `Plan::withoutGlobalScopes()`, which drops the
`SoftDeletingScope` along with the tenant scopes. An archived plan whose `is_active` is still true stays on
the public pricing page. *Deactivating* works correctly; *deleting* does not. The narrow fix is
`->withoutGlobalScopes([OrganizationScope::class])` or an explicit `->whereNull('deleted_at')` — but the
scope list is shared with `Plan::chargeFor()`'s neighbours, so it wants checking, not guessing.

**4b · One per-unit plan relabels every flat plan.** `shape()` returns `per_unit` if **any** tier is
per-unit, and `suffix()`/`sub` follow it for the whole page. A mixed catalogue therefore prints
"/ unit / mo" over a flat plan's price. Pre-existing — `shape()` has always been catalogue-wide — and it
becomes reachable the moment a second pricing shape is sold.

**4c · A derived-key collision drops a plan silently.** Two plans in one interval deriving the same
`tierKey` (`growth` and `growth_monthly`) — the first is kept and the second never appears. `plans.code`
is unique, so this is not reachable by accident, but nothing forbids it. A uniqueness rule on
(`tierKey`, `interval`) belongs in `PlanIntegrity::rules()`.

**4d · `/pricing` ships a dead second copy of the cards.** The served HTML carries a complete second
rendering of the plan-cards markup **inside the stylesheet** (`<style>` spans lines 101–612 of the
response; the copy sits at ~419). The browser DOM has exactly **one** grid, **three** cards and **one**
radiogroup, so nothing is duplicated visually — but it inflates every source-scanning guard's counts,
which is the trap this project has hit repeatedly. Present in the pre-edit baseline; not introduced here.

---

## 5 · What is guarded

`tests/Feature/MarketingPricingPairingTest.php` — 5 tests, 40 assertions, built on **the founder's actual
next action**: three plans, one of them single-interval.

- **pairing, not status** — each figure's `data-plan` attribute names the `plans` row it came from, and
  the row's tier key must equal the card's. A 200 with transposed prices fails.
- the **equal-length-different-order** case, with the three correct pairings **written out by hand** rather
  than derived from the same grouping the page used (§N.18.1).
- the **comparison table** asserted separately — "fixed the cards" is not "fixed the page".
- the **missing cycle**: the block ships, states the fact, and carries no derived figure (`828.00` — 12 ×
  €69 — is asserted absent).
- the **derived column count** and the `minmax(0, 1fr)` floor.

⚠️ **What the suite cannot assert: pixels.** Geometry was measured in `chrome-headless-shell` over CDP —
at **1280px**: 3 columns, cards at x = 28 / 443 / 857, **0px overflow**; at **375px**: 1 column, all cards
x = 18, **0px overflow**. The test asserts the *rule* that produces that; the browser measured the result.

**Mutations** (each applied, confirmed landed, run, reverted): positional pairing restored in the cards →
**RED (4)** · tier count hardcoded to 2 → **RED (1)** · positional pairing restored in the comparison table
→ **RED (4)**.

⚠️ **Two mutations could not be run: there is nothing to mutate.** The previous pass's `viewBox` restoration
and its move of the feature source to `plan_features` have **no guard at all** — no test in the suite names
either. Both are unprotected against a converter regeneration. Reported; writing those guards is outside
this pass's scope.
