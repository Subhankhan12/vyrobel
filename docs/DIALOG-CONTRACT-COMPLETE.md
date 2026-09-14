# The dialog contract, applied to all of them

**2026-09-03.** The census moved before anything was applied — and it moved twice.

---

## 1 · The re-census

⚠️ **My own first census was wrong, and I caught it before acting on it.** It looked for the semantics on
the *backdrop*, but they live on the **inner panel** — so `bulkOpen`, one of the four already fixed, came
back listed as *remaining*. A second pass, reading each backdrop's panel, gave the real numbers. **A count
taken from the wrong element is worse than no count.**

| | |
|---|---|
| modal overlays (final, all backdrop shapes) | **37** |
| already carrying `role` + `aria-modal` | **8** (not 4) |
| ⚠️ of those, **incomplete** — looked done, weren't | **2** |
| genuinely needing work | **29** |

⚠️ **The brief said thirty; it is 29 of 37.** And the two "done" ones matter more than the arithmetic:
`portfolio`'s `addOpen` and `editOpen` carried `role`, a resolving name **and** Escape — but **no `x-trap`
and no `tabindex`**. In source they read as finished. That is exactly why a hand-kept list of four had
stopped describing the codebase, and why the guard now **discovers** rather than lists.

**Classed, not counted.** Three shapes were found and treated differently:

| class | example | treatment |
|---|---|---|
| **modal dialog** (backdrop covers the viewport) | `Assign technician`, `Bulk edit rates` | ✅ the full contract |
| **per-row modal** rendered inside a `@foreach` | `unitPriceFor === '{{ $vyU['id'] }}'` | ✅ contract, with a **per-row id** |
| **not a dialog** — inline disclosures, dropdowns | `x-show="open"` on a `margin-top` block | ⛔ untouched; `noscroll` on a non-modal panel would lock the page behind something that was never modal |

⚠️ **My earlier pattern also missed two registered dialogs entirely** (`front-desk`, `operator-security`) —
they use class-based backdrops (`vy-fdm-overlay`, `vyp-scrim`) rather than an inline `position: fixed`. The
final census matches all three shapes.

---

## 2 · Every trap bound to its own show expression

⚠️ **Applying one pattern twenty-nine times is where a copy-paste error hides, so it was not done by hand.**
An applier script derived each trap's expression **from the backdrop's own `x-show`** — it cannot transpose
two dialogs because it never sees two at once — and a **machine census then re-read all 37** and compared
`x-trap` against `x-show` per dialog. **Zero mismatches, zero constants.**

**That comparison is now the guard**, so it re-runs on every dialog forever rather than being a
one-afternoon check. `x-trap="true"` traps focus permanently — worse than the defect — and a mutation
arming one goes red.

⚠️ **`noscroll` only where modal**: it was applied solely to panels behind a viewport-covering backdrop.
Inline disclosures sharing a variable name (`operator-settings` has a second `x-show="open"` on a
`margin-top` block) were left alone.

---

## 3 · Every name resolves — checked three ways, because one is not enough

| check | what it can prove | what it cannot |
|---|---|---|
| **source** — the referenced id exists in the same file | dialogs that render only in states a fixture cannot reach | nothing about Blade interpolation |
| **served page** — every `aria-labelledby` resolves to exactly one rendered id | interpolation, duplicates, `@if` branches | names in unrendered dialogs |
| **live DOM** (CDP) | the browser agrees with the served HTML | — |

⚠️ **I needed all three because a mutation proved it.** Pointing a name at a missing id came back **GREEN**
against the served-page check: `/app/inbox` renders **one of its three** dialogs without a selected
conversation, so the mutated one never reached the page. **The gap was found by a mutation, not by
reasoning**, and the source-level check was added because of it. It now goes red.

**Live DOM result:** `/app/housekeeping` 3 dialogs · `/app/rates` **8** · `/app/guests` 4 — every one with
`aria-modal`, `tabindex="-1"`, a name resolving to **exactly one** element, a non-constant trap, and **zero
duplicate ids on any page**.

⚠️ **The eight on `/app/rates` are the case source could not settle**: they render per unit from a loop, so
their ids are Blade-interpolated and appear once in the file and eight times on the page. Each is unique.

**Naming, per dialog:** wherever a heading or title element existed it was pointed at (`<h2>`, `<h3>`,
`.vy-fdm-title`). ⚠️ **Four dialogs had no title element at all** and the applier **refused to name them**
rather than invent one. Three turned out to render their title inside a shared component
(`<x-rates.unit-price-form>`) — which is used **4–5 times per page, some inside loops**, so a fixed id there
would have been duplicated. It gained a **caller-supplied `headingId`** instead, and each dialog passes its
own. The fourth (`schedule`) had a real `<h2>` my window had simply missed.

---

## 4 · Escape, per dialog

⚠️ **Never quoted in aggregate** — the audit's count was wrong once by conflating "dismissible by mouse on
the backdrop" with "has a keyboard escape". The guard asserts an Escape handler **on each dialog's own
backdrop**, and a mutation removing one from a single dialog goes red.

⚠️ **Each file's own directive convention was respected**: files already using `x-on:` got
`x-on:keydown.escape.window`, files using `@` got `@keydown.escape.window`. The applier read the convention
from the file rather than assuming one.

---

## 5 · The guest realm — censused for the first time, and the answer is none

⚠️⚠️ **The guest realm has ZERO modal overlays. So does the storefront booking flow.** Nobody had counted
either. That is worth knowing precisely because the guest realm is the one a paying customer's customers
use, and a guest cannot ask a colleague for help — there is simply nothing there to fix.

**All 37 dialogs are operator-shell.** The owner shell has none either.

---

## 6 · ⚠️ The background is still NOT inert

Focus is trapped and **no more**. A screen-reader user browsing by virtual cursor can still reach content
behind an open dialog. **This pass did not change that**, and nothing in the code, the tests or this document
implies otherwise — the guard's own header says so where a reader meets it.

**Is `inert` viable now without a dependency? — reported, not built.** The HTML `inert` attribute is
supported by every current browser and needs no library, so the mechanism is available. What is *not*
settled is **what to apply it to**: these dialogs are rendered inside the page body, not in a portal, so
inerting "everything else" means marking the shell's `<main>`, `<header>` and nav — per shell, per dialog,
and undoing it correctly on close. **That is a design decision about shell structure, not a mechanical
addition, and it stays the founder's.**

---

## 7 · One contract, one guard

The existing `DialogFocusContractTest` was **extended, not duplicated** — the census moved into
`tests/Support/dialog_helpers.php` and the assertions now walk it, exactly as the last pass did with the
heading rule. The four original dialogs keep their named assertions; the new ones cover all 37 by discovery.

⚠️ **The third-shell question is moot** — the guest shell has no dialogs, so there is nothing to extend the
contract to. If it ever gains one, the discovery guard covers it the day it appears.

⚠️ **A non-vacuity floor** asserts the census finds at least 37: a discovery guard that finds nothing would
otherwise pass every assertion built on it.

---

## 8 · What cannot be proven, and the mutations

⚠️⚠️ **Unchanged and restated:** this proves the contract is **WIRED**. It does **not** prove focus moves
into the dialog, is trapped, or returns to the trigger — those are runtime facts, there is still no browser
harness, and `x-trap` is Alpine's own directive relied upon to do them.

**Mutations** — each applied, **confirmed landed**, run, reverted:

| mutation | |
|---|---|
| a trap armed with a **constant** | **RED (2)** |
| a name pointing at a **missing id** | ⚠️ **GREEN first** → gap found → guard strengthened → **RED** |
| **Escape** removed from one dialog | **RED (1)** |
| the contract broken in **one dialog only** | **RED (1)** |

⚠️ **The GREEN was recorded honestly and acted on rather than explained away** — and it produced the only
real improvement to the guard in this pass.

⚠️ **One more instrument note:** the actor assertion (`hasRole('Property Owner')`) failed on first run
because spatie runs in **teams mode** and `hasRole()` without a team set **fails silently to false** — the
same shape invariant #30 warns about. The team is now set before the check rather than the assertion removed.
