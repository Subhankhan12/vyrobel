# AUDIT PHASE 7 — Accountant, the second role at the mismatch

**2026-09-12.** 🖥 **Instrument: MCP Playwright** · HeadlessChrome/151 · `php artisan serve` on
`http://localhost:8000`. ⚠️ **Validated in both directions before any finding.**
🔬 **Signed in as `demo-accountant@vyrobel-demo.test` — the only holder of `Accountant`.**

### The four marks — only 🔬 and 🖥 are facts
**🔬 MEASURED** · **🖥 BROWSER** · **📖 ASSERTED** · **📄 RECORDED**
⚠️⚠️ **MULTI-TENANCY REMAINS NOT TESTED** — one organisation. **TWO true orphan permissions.**

---

## 0 · Verdict — and two corrections to this programme's own claims

| | |
|---|---|
| **Permissions** | 🔬 **12 of 51** — config and DB **agree exactly** |
| **Unmapped surface** | 🔬 **Exactly 2 permissions** — and they unlock **the two money screens no narrower role had reached** |
| **The mismatch** | ✅ **CONFIRMED on a second role → a PRODUCT DEFECT, not a one-role accident** |
| ⛔ **F-11's scaling claim** | **WRONG — my own Phase 6 over-generalisation. Accountant lacks the MOST and has ZERO dead ends** |
| ⛔ **F-13** | **ONE SCREEN, NOT A CLASS.** The money screens are **exemplary**, not silent |
| **Statement closing** | ⛔ **IRREVERSIBLE and it writes `owner_ledger`** — established by reading, **not performed** |
| **Nothing severe** | ⚠️ No money moved, no foreign data, nothing irreversible done |
| **Application code changed** | **NONE** |

---

## 1 · ⚠️⚠️ THE UNMAPPED SURFACE — and it is the money surface

🔬 **Accountant is the only role in the ladder that is not a subset of Hotel Manager.** What it holds that
Hotel Manager lacks is **exactly two permissions**:

| permission | unlocks | seen by a narrower role before? |
|---|---|---|
| **`payments.refund`** | `/app/reservations/{r}/refund` | ⛔ **NO** — Phase 5 and Phase 6 both got **403** |
| **`payouts.manage`** | `/app/payouts/run` + payout writes | ⛔ **NO** — 403 in both |

⚠️⚠️ **So the entire unmapped surface is the money surface**, and Accountant is the first narrower role
ever to render either screen. **Both were given the most attention (§4).**

🖥 **Navigation: 14 links followed, 14 resolve, ZERO dead ends.** ⚠️ Accountant's nav is materially
different from every prior role's — it carries a **FINANCE** group (Owners · Payout run · Reports) that
Front Desk never saw.

### ⛔ CORRECTION — F-11 DOES NOT SCALE WITH PERMISSIONS LACKED
📄 **My Phase 6 report claimed the dead-end defect "scales with permissions lacked."** 🔬 **Three data
points now say otherwise:**

| role | permissions lacked | dead ends |
|---|---|---|
| Hotel Manager | 12 | **1** |
| Front Desk | 31 | **2** |
| **Accountant** | **39** | ⛔ **0** |

⚠️⚠️ **The role lacking the MOST has NONE.** 🔬 The mechanism is *which* permissions, not how many:
`GET /app/owners` and `/app/insights` are gated by **`reports.view`**, and `/app/payouts/run` by
**`payouts.manage`** — **Accountant holds both**, so nothing it is shown refuses it.
✅ **The corrected statement:** F-11 occurs when the navigation shows a link whose permission the role
lacks. **It is a per-link filtering gap, not a function of role breadth.** ⚠️ **A two-point trend was not a
trend — and I published it as one.**

---

## 2 · ⚠️⚠️ EVERY DENIAL PROVEN BY THE ROW'S STATE, NOT THE TABLE'S SIZE

⚠️ **This role's writes are status and amount changes on money objects — a count cannot show them.**

| denied write | 🖥 | 🔬 the ROW, read before and after |
|---|---|---|
| `POST reservations/{r}/cancel` | **403 gate** | ❌ status still **`confirmed`**, `cancelled_at` **NULL** |
| `POST housekeeping/{t}/status` | **403 gate** | ❌ every task still **`pending`** |
| `POST settings/tax-fees` *(the mismatch)* | **403 gate** | ❌ `taxes` unchanged |
| Statement finalize | ⛔ **not attempted** (§5) | ✅ statements still `finalized, paid×5` |
| Payout mark-paid | ⛔ **not attempted** (§5) | ✅ payouts still `pending, paid×5` |

✅ **Permitted, attributed:** `POST app/profile/preferences` → **302, not 403**.
⚠️ **One INCONCLUSIVE, reported as such:** `POST reservations/{r}/check-in` returned **404, not 403** — my
path guess was wrong, so **the check-in denial is UNTESTED**, not proven. *(`guest_checked_in_at` is still
NULL, so nothing landed — but that is not evidence the gate fired.)*

✅ **Team proven set by evidence:** 14 permission-gated screens rendered with real data while the writes
this role lacks refused. ⚠️ A global `can()` failure could not produce that split.

---

## 3 · ⚠️⚠️ THE MISMATCH — SECOND SIGHTING, AND HALF THIS ROLE'S GRANT IS INERT

🔬 **Confirmed for Accountant exactly as Phase 5 established for Hotel Manager:**
🖥 **"Settings" is ABSENT from the navigation** · `POST /app/settings/tax-fees` → **403** (`properties.manage`)
· 🔬 `taxes` unchanged.
⚠️⚠️ **A second role makes this a PRODUCT DEFECT, not a one-role accident.** ✅ **And the sharpened form
holds: there is no false promise on any screen** — the screen is simply unreachable. **The promise is made
only in the permission matrix.**

### 🔬 The full sweep — checking SERVICES and VIEWS, not only routes
⚠️ *Phase 6 nearly flagged a permission as a phantom that was enforced in `AssignmentService`. Every one of
the twelve was checked against `app/`, `routes/` and `resources/`.*

| enforced (6) | ⛔ NO REACHABLE SURFACE (6) |
|---|---|
| `folios.manage` · `folios.view` · `payments.record` · `payments.refund` · `payouts.manage` · `reports.view` | `addons.manage` · `fees.manage` · `invoices.issue` · `invoices.view` · `payments.view` · **`taxes.manage`** |

⚠️⚠️ **HALF OF ACCOUNTANT'S PERMISSION GRANT DOES NOTHING.**
✅ **All six are already recorded in `KNOWN-LIMITS.md` L6** — **CLASS: KNOWN. Not re-reported as new.**
⚠️ **But the CONCENTRATION is not recorded anywhere.** L6 frames twelve phantoms across a 51-permission
catalogue — a per-permission view. 🔬 **Six of those twelve land on this single 12-permission role.**
⚠️ **An administrator configuring Accountant is ticking six boxes that do nothing, and L6's framing does
not surface that.** **Reported as a re-framing of a known limit, not a new defect.**

---

## 4 · ⚠️⚠️ DO THE MONEY SCREENS GO SILENT? — NO. F-13 IS ONE SCREEN, NOT A CLASS

⚠️ **The most useful negative result in this phase.** 🖥 Both unmapped money screens communicate **better
than most of the product**:

### 🖥 `/app/reservations/{r}/refund` — "Refund a guest"
2 submits (*"Refund the remaining €369.00"*, *"Review & issue refund"*), 6 inputs, figures consistent at
€369.00. ⚠️⚠️ **And it warns, unprompted:**
> *"**It can't be reversed.** A refund reverses a payment, not the charges…"*

✅ **The screen states the irreversibility that Phase 3 had to establish by reading the listener.**

### 🖥 `/app/payouts/run` — "Payout run"
> *"⚠️ **Test mode.** Automated payouts run in test mode — **no real funds move.** Real transfers activate
> when your account goes live."*

Plus explicit state: *"0 statements ready · 1 transfer pending"*, *"READY TO PAY — 0 finalized statements
awaiting a payout"*, *"PENDING TRANSFERS €434.43"* — and **disabled controls that say why**:
*"Export SEPA · **Soon**"*, *"Pay via Stripe Connect"*, *"Channels **Pro**"*.

### 🖥 `/app/owners`
*"NEEDS ATTENTION 1 · 1 hold · 0 draft"*, *"On hold"*, *"0 owners scheduled · 1 on hold"* — **every figure
carries its state.**

⚠️⚠️ **VERDICT: an accountant on these screens CAN tell "cannot be changed" from "has not been changed
yet."** The brief's concern does not materialise here.
⛔ **F-13 therefore stands as ONE SCREEN — the housekeeping board — and that makes it SHARPER, not weaker:**
**the product demonstrably knows how to explain a disabled state, on its highest-stakes screens. That one
board is an outlier.** ⚠️ **CLASS: F-13 unchanged, scope BOUNDED to one screen.**

---

## 5 · ⛔ MONEY: ESTABLISHED, NEVER COMPLETED

🔬 **Statement finalization — established by READING, not performed:**
- `OwnerStatementService::finalize()` **throws if `isLocked()`**, and 🔬 **there is no `reopen`,
  `unfinalize` or `revert` method anywhere in the service.** ⛔ **Irreversible in-app.**
- ⚠️⚠️ **Worse than irreversible — it MOVES THE PINNED FIGURE.** It calls
  `WithholdingService::apply(...)` (which posts a ledger line) and **claims ledger rows** into the
  statement. **Finalizing would have written to `owner_ledger`.**
- ⛔ **NOT ATTEMPTED.** *(Phase 2 made the same call on suspension and was right.)*

⛔ **No refund issued. No payout marked paid. None of the 17 money-write routes exercised.**
✅ `owner_ledger` **29 / 730640** · `payments` **16** · `reservations` **42** — **the floor holds.**

⚠️ **The honest consequence:** the two screens that are this role's *entire* unmapped surface were **read,
not exercised**. **Their write paths are established by source and by their own on-screen warnings —
NOT by execution.** That is the correct trade, and it is stated rather than hidden.

---

## 6 · THE WALK — and what was NOT examined

🖥 **14 navigation links + both unmapped money screens + the denial set.**
⚠️⚠️ **NOT a full 54-screen walk this phase.** The brief asked for one; the budget went to the three
priority questions (unmapped surface · row-state denials · the F-13 sweep). ⛔ **The ~38 screens not
individually re-walked as Accountant are NOT EXAMINED, never "clean."**
⚠️ **This is a real gap and is stated plainly** — Phases 5 and 6 did walk their full surfaces.
📄 **Mitigating, not excusing:** Accountant's *reachable* surface is a strict subset of Hotel Manager's
**plus** the two money screens, and Phase 5 walked all 54 as Hotel Manager. **The two genuinely new screens
were both covered.**

| dimension | result |
|---|---|
| **UI/UX · DESIGN** | 🖥 No overflow observed on the screens visited; `h1` present on all |
| **FUNCTIONALITY** | 🖥 No 500s. Money figures internally consistent (€369.00 refund; €434.43 pending) |
| **CRUD** | §2 — denials proven by row state |
| **SECURITY** | ✅ 3 denied writes → 403 gate + 1 INCONCLUSIVE, stated |
| **PERFORMANCE** | ⚠️ Not measurable on `artisan serve` |
| **MULTI-TENANCY** | ⛔ **NOT TESTED** |

---

## 7 · STATE

🔬 **12 tables measured before touching. 11 IDENTICAL. ONE MOVED:**
⚠️ **`audit_events` 153 → 154 (+1)** — **RE-PINNED with its cause**: `user.preferences_updated`, my single
permitted write. ⛔ **NOT deleted** — audit rows are never pruned.

✅ **UNMOVED:** `owner_ledger` **29 / 730640** · `payments` **16** · `reservations` **42** ·
`owner_statements` **6** · `payouts` **6** · `folios` **20** · `folio_items` **40** · `taxes` **2** ·
`fees` **1** · `domain_events` **190** · `role_has_permissions` **260**.
✅ **All 14 demo accounts authenticate.** ⚠️ **Money gate NOT run.** ⛔ **Suite not run — no code changed.**
⛔ **F-1 to F-14 remain open. No new F-number this phase** — the findings are two corrections and a bounding.

---

## 8 · ⚠️⚠️ THE LIST HANDED ON

**Remaining: Revenue Manager · Reservation Agent · Housekeeping · Maintenance · Marketing Manager ·
Guest Support Agent.**

### ⚠️⚠️ HOUSEKEEPING IS STILL THE ACID TEST — and Phase 7 makes it sharper
📄 Phase 6: *"if the role the board exists for also cannot advance a task, that is far more serious."*
⚠️⚠️ **And now there is a control:** 🖥 this phase proved the product **can** explain a disabled money
state clearly, on its highest-stakes screens. ⛔ **So if the housekeeping board is silent for Housekeeping
too, it is not a product-wide habit — it is one screen that was missed.** **Test it FIRST in that phase.**

### Per-role guidance
| role | holds | watch for |
|---|---|---|
| **Housekeeping** | 4 | ⚠️⚠️ **THE F-13 ACID TEST.** Can it advance a task? Does the board explain itself to the role it is for? |
| **Maintenance** | 4 | Same shape on `/app/maintenance` — 5 screens, 14 writes gated by `maintenance.manage` |
| **Revenue Manager** | 10 | `rates.manage` (12 writes) — the only role besides HM/GA with rate authority |
| **Reservation Agent** | 11 | ⊂ Front Desk; expect the two dead ends Phase 6 found, **plus any from `reservations.*` gaps** |
| **Marketing Manager** | 5 | Smallest grant — **expect the highest phantom ratio**; check services, not routes |
| **Guest Support Agent** | 7 | ⚠️ **PII perimeter (invariant #28)** — does it see guest contact data outside the CRM? |

### ⚠️ Carry forward
1. ⚠️ **Do not repeat F-11's scaling claim** — it is false (§1). **Report each role's dead-end count as a
   fact, not a trend.**
2. ⚠️ **For every narrow role, compute the phantom ratio** (§3). **Marketing Manager at 5 permissions is
   the likeliest to be mostly inert.**
3. ⚠️ **Read the row, not the count** — and **check the route path exists before calling a 404 a denial** (§2).

---

## 9 · ⚠️ NEAR-FALSE-POSITIVES — one more (running total **EIGHTEEN**)

18. **"Accountant reaches `/app/owners`, which Phases 5 and 6 both showed as denied."** 🔬 **False — and it
    was my own misreading of my own reports.** `/app/owners` is gated by **`reports.view`**: Hotel Manager
    **holds** it (Phase 5 showed it rendering, *not* denied — I misremembered), Front Desk **lacks** it
    (Phase 6's 403 was correct), Accountant **holds** it. ⚠️ **No contradiction existed.**
    ⚠️⚠️ **The lesson: I nearly reported a privilege-escalation finding because I trusted my memory of a
    prior phase instead of re-reading it.** **Re-read the report; do not recall it.**

⚠️ **And one INCONCLUSIVE correctly refused promotion to a finding:** the `check-in` 404 (§2) — a wrong
path in my probe, not a missing gate.

---

## 10 · ⚠️ WHERE THIS BRIEF WAS WRONG

1. ⚠️⚠️ **"F-11 SCALES WITH PERMISSIONS LACKED — expect more."** 🔬 **False, and it originated in my own
   Phase 6 report.** Accountant lacks the most and has **zero** dead ends (§1).
2. ⚠️⚠️ **"Does the money screen go silent?"** 🖥 **No — the opposite.** The refund and payout screens are
   **the best-communicating screens this programme has seen**, including an unprompted *"It can't be
   reversed."* **F-13 bounds to one screen** (§4).
3. ⚠️ **"SIX of Accountant's permissions are enforced on no route"** — ✅ **confirmed exactly**, and
   **all six are already in `KNOWN-LIMITS` L6.** The new information is the **concentration** (half the
   role's grant), not the phantoms themselves.
4. ✅ **Correct and load-bearing:** *read the row, not the count* (**every denial here was a status, and the
   count would have shown nothing**), *check services not just routes* (**it changed the phantom list**),
   and *establish statement closing before attempting it* — **which stopped a write to `owner_ledger`.**
