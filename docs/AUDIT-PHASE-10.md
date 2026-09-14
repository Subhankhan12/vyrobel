# AUDIT — PHASE 10 · MAINTENANCE · THE THIRD LADDER

**Role:** Maintenance · `demo-maintenance@vyrobel-demo.test` (Paulo Nunes)
**Realm:** operator (`/app`, `web` guard)
**Instrument:** MCP Playwright → `chrome-headless-shell` via the project `.mcp.json`
**Date:** 2026-09-12
**Application code changed:** none — verified, §8. **Suite not run:** no application code changed, so it
is not required. Stated per the brief.

**Evidence tiers.** 🔬 MEASURED · 🖥 BROWSER · 📖 ASSERTED · 📄 RECORDED. **Only 🔬 and 🖥 are facts.**

**Permissions.** 🔬 **4 — config and DB agree exactly:**
`housekeeping.view · maintenance.manage · schedule.view · units.view`.
*(The same four-permission shape as Housekeeping, differing only in `maintenance.manage` for
`housekeeping.manage` — and carrying the same registered phantom.)*

---

## 0. THE #1 QUESTION — **THE THIRD LADDER IS BOUNDED.** And it is bounded in the right place.

**Said as clearly as a defect, because it is the better finding:** the work-order state machine is guarded
by an allow-list **in the service layer**, and the guard is **reached** on every transition. This is the
correct implementation the other two should be measured against.

🔬 The machine, read from code before probing — **four statuses, not the brief's three:**

| status | |
|---|---|
| `open` → `in_progress` → `resolved` | and `cancelled` from either active state |
| 🔬 seeded | `in_progress` 2 · `resolved` 2 · `open` 1 · total **5** |

🔬 [`MaintenanceService::assertTransition()`](../app/Services/MaintenanceService.php#L469) — **defined at
:469 and CALLED at four sites:**

| call site | action | legal source states |
|---|---|---|
| `:122` | `start` | `open` |
| `:140` | `resolve` | `open`, `in_progress` |
| `:351` | `cancel` | `open`, `in_progress` |
| `:289` | **correct the cost of** | `resolved` |

plus `assertActive()` for parts/notes. The controller holds **no** status logic of its own — it calls the
service inside `try/catch (RuntimeException)` and renders the refusal as a flash.

### Probed through the product's own forms — and proven by the row

🔬 Five illegal transitions, real session, real CSRF token. **All five returned 302 PRG — exactly the
brief's warning that the status tells you nothing.** The row is the evidence:

| probe | POST | row after |
|---|---|---|
| `start` a `resolved` | 302 PRG | unchanged |
| `start` an `in_progress` (idempotent path) | 302 PRG | unchanged |
| **`resolve` an already-`resolved`, `parts=999`** | 302 PRG | **unchanged — no expense posted** |
| `cancel` a `resolved` | 302 PRG | unchanged |
| `parts` on a `resolved` | 302 PRG | unchanged |

🔬 **Every field on all 5 tickets and all 8 unit condition states byte-identical**, and
`owner_expenses` **6**, `owner_ledger` **29 / 730640**, `cost_sum` **22500** — unmoved.

### And proven at the layer itself, with a positive control

Each call inside a transaction that was **always rolled back**, tenant context set so the probe actually
reached the service:

| call | result |
|---|---|
| `start()` a `resolved` | 🔬 REFUSED — *"Cannot start a resolved maintenance ticket."* |
| `cancel()` a `resolved` | 🔬 REFUSED — *"Cannot cancel a resolved maintenance ticket."* |
| `resolve()` a `resolved` **+ €999 cost** | 🔬 REFUSED — *"Cannot resolve a resolved maintenance ticket."* |
| `correctCost()` on an `open` | 🔬 REFUSED — *"Cannot correct the cost of a open maintenance ticket."* |
| `setAwaitingParts()` on a `resolved` | 🔬 REFUSED — *"Cannot update parts on a resolved maintenance ticket."* |
| **`resolve()` an `open` — LEGAL per the allow-list** | 🔬 **ACCEPTED → `resolved`** (rolled back) |

⚠️ **That last row is the control that makes the other five mean something.** Without a call the guard
*allows*, "everything was refused" is indistinguishable from a broken path. The guard is a real
allow-list, not a blanket refusal.

### The count, stated plainly — **and it is NOT "two of two"**

| ladder | controller | service / action that writes | verdict |
|---|---|---|---|
| **Housekeeping** (F-15) | ❌ target-only | ❌ none | 🔴 **genuinely unbounded — and reachable from the browser** |
| **Reservation** cancel / no-show | ✅ `CANCELLABLE`/`NO_SHOWABLE` | ✅ `terminate()` (#29) | 🟢 bounded, both layers |
| **Reservation** check-in / check-out (F-18) | ✅ status checks | ❌ none at the action | 🟡 bounded at the surface; **latent, unreachable** gap |
| **Maintenance** | (delegates) | ✅ **`assertTransition()`, reached** | 🟢 **bounded in the correct layer** |

**Of three state machines, ONE is actually unbounded.** One is bounded everywhere it can be reached with
a defence-in-depth gap one layer down, and one is exemplary. **This is a narrower problem than the brief
feared, and the fix now has a reference implementation to copy: `MaintenanceService::assertTransition()`.**

---

## 1. #2 — MISSING GUARD OR UNREACHED GUARD? **Every defect found so far is a MISSING check. There is no unreached guard in this product.**

Reported per state machine, as the brief asks, because that is what makes the fix scopable:

| state machine | shape | work required |
|---|---|---|
| Housekeeping task status | **MISSING** — no source-state check at any layer | add a guard (copy maintenance's) |
| Reservation check-in / check-out | **MISSING at the action layer** — the controller's check is correct and reached | add a guard in the action |
| Reservation cancel / no-show | ✅ present and reached at both layers | none |
| Maintenance | ✅ present and reached in the service | none — **this is the model** |

⚠️ **The brief's premise here is wrong, and it matters because it changes the fix.** It states that
F-18 is *"a `canTransitionTo()` that is correct and complete, and `updateStatus()` never calls it."*
🔬 **`canTransitionTo` does not exist anywhere in `app/`, `resources/`, `routes/` or `tests/`**, and
reservations have no `updateStatus()` — that was the *housekeeping* controller's method name. The
dangerous "someone did the thinking and the wiring was lost" shape the brief asked me to hunt for
**has not been observed once in ten phases.** Every defect is an absent check, which is simpler to fix and
easier to test for.

---

## 2. #3 — MONEY: **a parts cost DOES reach the owner ledger — and every path to it is guarded**

**This did not earn the top slot, and the reason is measured, not assumed.**

🔬 A cost is real money: `resolve()` → `postExpenseIfOwned()` → an `OwnerExpense` on owner-owned units,
which feeds the owner statement and ledger. 🔬 `time_spent` is **not** — the code is explicit:
*"Time spent (hours, informational — **NOT priced**, no labour rate is modelled, so it never touches the
owner bill)."* It lands in `labor_hours` and stops there.

**So the money question is really: can an illegal or repeated transition post an expense?** 🔬 No:

- `resolve()` is `assertTransition(['open','in_progress'])` — **a resolved ticket cannot be resolved
  again**, so an expense cannot be double-posted. Probed with `parts=999` through the real form and again
  at the service layer: refused both times, `owner_expenses` still **6**.
- The money guard sits **before** the transaction, so a rejected call writes nothing.

### Can a cost be edited after the work order is closed? **Yes — and it is the correct accounting shape, not the defect shape**

🔬 `correctCost()` is the only path, and it is `assertTransition(['resolved'])` — it works **only** on a
closed ticket, by design. What it does:

1. Finds the live `OwnerExpense` for this ticket and calls `OwnerExpenseService::voidExpense()` —
   **a new reversing ledger line; the original is never edited**
2. Posts a fresh expense for the corrected value
3. Writes `maintenance_ticket.cost_corrected` to **both** `domain_events` and `audit_events`

The docblock states the rule the code keeps: *"the original is never edited… the money history lives in
the append-only ledger."* **A mutable closed cost was the shape to fear; this is an append-only
correction with an audit trail.**

⚠️ 🔬 One asymmetry worth recording, in the *safe* direction: the service allows `open → resolved`
directly, but the UI offers "Mark resolved" only on an `in_progress` ticket. The interface is **stricter**
than the server. That is the inverse of F-16 (where the board claimed a rule the server did not have) and
is not a defect — but it means the brief's "skip straight to done" is a **legal** transition here, not an
illegal one to probe.

---

## 3. #4 — THE KEYBOARD ROW: **the structural fix HOLDS**

⚠️ **I nearly re-reported the old defect.** 🔬 The work-order row *is* a `<div>` carrying
`@click="selecting ? toggleSel('<id>') …"`, with no `tabindex`, no `role`, no key handler, and it does not
accept focus — 3 of 3 rows. Read alone, that is the exact defect the brief describes.

**It is not.** 🔬 Inside each row the ticket title is a real `<button>`. My first sweep looked for
`a[href*="/app/maintenance/"]` and found nothing because the control is a button, not an anchor.

🔬 Measured, keyboard only:

| | Enter | Space |
|---|---|---|
| Focus lands on the title `<button>` | ✅ | ✅ |
| Focus ring | ✅ **`solid 2px`, visible** | ✅ |
| Work order opens (detail drawer renders) | ✅ | ✅ |

🔬 And across the whole screen: **32 keyboard stops, 0 with no visible ring.** The brief's "three stops
focusable with no visible ring" does not reproduce.

### 375px — no regression

🔬 `documentElement.scrollWidth − clientWidth` measured on five screens this role uses:

| screen | body overflow | actions fully on screen |
|---|---|---|
| `app/maintenance` | **0** | — |
| `app/maintenance/{ticket}` | **0** | **4 of 4** |
| `app/units` · `app/schedule` · `app/housekeeping` | **0** | — |

The element-level overflows (table 362px, schedule grid 522px) are **inside their own scroll containers**,
and the 120px items are ambient decoration divs — the same pattern Phase 8 established. **Body overflow is
zero everywhere. No regression.**

---

## 4. #5 — DOES A TRANSITION RECORD *WHO*? **Yes — measured, and it closes Phase 9's open caveat**

Phase 9 could only establish this from code, because both its transitions were irreversible. Here one was
not, so I proved reversibility first (§5) and then performed it.

🖥 Pressed **"Start work"** on the open ticket. 🔬 The resulting row in `audit_events`:

```
action     maintenance_ticket.started
actor_id   01m1qj5wjwk2bg6gj8a8yvbvsc  →  Paulo Nunes <demo-maintenance@vyrobel-demo.test>
subject    maintenance_ticket / 01m1qj606gt2r2ya7c6ncvzxzn
before     {"status": "open"}      after  {"status": "in_progress"}
```

**"Who started this work order" is recoverable, with the before/after states.** 🔬 `domain_events` also
records `maintenance_ticket.started` but carries **no actor column** — confirming again that the domain
outbox alone never answers "who"; the audit row is what does.

🔬 And the whole module audits consistently: `opened` · `assigned` · `started` · `resolved` ·
`parts_updated` · `note_added` · `cost_corrected` · `cancelled` all call `audit->record()`.
**F-17 remains the outlier**: housekeeping is the one module whose status change writes no audit row.

---

## 5. #7 — MEASURED, REVERSIBILITY PROVEN *BEFORE* PERFORMING, RESTORED FIELD BY FIELD

**Proven before acting, not after** (the judgement that has now been right five times): 🔬
`takeUnitOutIfNeeded()` returns early when `! $ticket->takes_out_of_service`, and the target ticket has
`takes_out_of_service = false` — so `start` writes **only** `status` and touches no unit. Everything else
on the path is append-only.

🔬 **Prediction held exactly**: after the transition `unit_status_logs` **6** (unchanged),
`owner_expenses` **6**, `owner_ledger` **730640**, unit condition still `in_service`.

🔬 **Restore verified field by field** — status returned to `open`, then a full re-capture diffed against
the pre-phase snapshot: **all 5 tickets and all 8 units identical**, across `status · cost_minor ·
labor_minor · parts_minor · labor_hours · awaiting_parts · part_name · assigned_to · opened_at ·
resolved_at · resolution_notes · takes_out_of_service · unit condition · unit housekeeping status`.

### Append-only residue, with its cause

| table | start | end | cause |
|---|---|---|---|
| `audit_events` | 156 | **160** | +1 `maintenance_ticket.started` (my probe) · **+3 `maintenance.costs_exported`** — the three cost-export downloads in the §6 walk |
| `domain_events` | 195 | **196** | +1 `maintenance_ticket.started` |

⚠️ The three export rows are worth naming: 🔬 **an export writes an audit row with its actor.** That is
the same asymmetry F-9 recorded from the other side (a password change writes none, an export does) —
confirmed again here, and a point in the product's favour on this screen.

🔬 **Every pinned figure unmoved:** `reservations` 42 · `payments` 16 · `owner_ledger` 29 / **730640** ·
`role_has_permissions` 260 · `reservation_status_history` 51 · `unit_status_logs` 6 · `owner_expenses` 6 ·
`maintenance_tickets` 5 · `cost_sum` 22500.

🔬 **All 14 web-guard demo accounts authenticate** (via `Auth::attempt`, not a hash compare) — zero
failures. 🔬 `DemoDataSafetyGuard::DEVELOPMENT_ENVIRONMENTS` is still `['local','testing']` and still
throws (`:69`) everywhere else.

---

## 6. #6 — THE WALK: all 54

🔬 **29 DENIED · 20 REACHED · 5 DOWNLOAD.** Zero 500s. Zero screens with no `h1`.

⚠️ **A correction to my own first pass.** I initially scored `app/channels` as REACHED. 🔬 It is
**HTTP 403, "Not in your plan"** — a *subscription/plan* gate whose copy my denial regex (written for the
permission gate's "You don't have access to this") did not match. Re-verified all 21 by **HTTP status**:
exactly one was wrong. **Two distinct denial kinds exist, and both explain themselves.**

**Downloads (5, all succeeded, captured as download events):** the three maintenance cost exports
(`csv`/`pdf`/`xlsx` — this role holds `maintenance.manage`), `app/schedule/pdf`, `app/security/export`.

**F-12 CONFIRMED again** — 🔬 `app/maintenance/{ticket}` renders **two `h1`** ("Work order" + the ticket
title). Third phase to observe it; this role lives on that screen.

**`app/inbox/{thread}` DENIED here** (this role lacks `guests.view`), where Phase 9's Reservation Agent
reached it. 🔬 Third data point for **destination, not status** — one route, three verdicts, decided
entirely by what it redirects into.

**Navigation.** 🔬 16 links rendered, 14 reached, **2 dead ends** — "Owners" → `/app/owners` and
"Payout run" → `/app/payouts/run`, both 403. Running measured tally, no prediction made:
**1 · 2 · 0 · 2 · 2 · 2**. Three consecutive roles with 4, 11 and 4 permissions all produce the same two.

### F-13's silence — present, but narrower than it looks

🔬 Five screens this role reaches render **zero write controls and no explanation**: `app/units` ·
`app/housekeeping` · `app/rack` · `app/calendar` · `app/reservations` (1 control). No "read-only", no
"your role", no pointer to an admin.

⚠️ **But I am not scoring all five against F-13.** A calendar and a rack are read surfaces for *every*
role — their silence is not a permission-communication gap. The one that genuinely repeats F-13's shape is
`app/housekeeping`, already recorded. **The contrast Phase 7 drew still holds**: the money screens explain
their disabled states well, and the 403 pages — both kinds — explain themselves.

### Phantom ratio — **1 of 4, and it is the registered one**

| permission | routes | code sites | verdict |
|---|---|---|---|
| `maintenance.manage` | **9** | 2 UI gates (`MaintenanceList:120`, `MaintenanceTicketDetail:94`) | ✅ REAL |
| `schedule.view` | 3 | nav gate | ✅ REAL |
| `units.view` | 1 | search facet + nav gate | ✅ REAL |
| `housekeeping.view` | **0** | **0** | ⚠️ phantom — **registered**: 2× in `PHANTOM-PERMISSIONS.md`, 1× in `PermissionEnforcementContractTest` |

Consistent with Phase 9's correction (§7.1 there): this is a documented, tested, deliberate
non-enforcement awaiting a founder ruling — **not a new finding**.

---

## 7. ⚠️ WHAT THIS BRIEF GOT WRONG

1. **"Two of two state machines have been unbounded."** Phase 9 did not conclude that. It found the
   reservation ladder **bounded at every reachable surface**, with the gap one layer down and
   **explicitly unreachable** ("Reachability today: none"). The accurate count going into this phase was
   *one unbounded, one latent*.
2. **F-18's mechanism is misdescribed.** 🔬 `canTransitionTo()` exists nowhere in the product, and
   reservations have no `updateStatus()`. F-18 is two action classes that check only
   target-equals-current.
3. **"A cancelled booking walked back to confirmed."** Phase 9 measured a cancelled booking going to
   **`checked_in`**, not `confirmed`.
4. **"A checked-out one returned to checked-in with its departure timestamp still set."** Not measured by
   Phase 9 — and `reservations` has no `checked_out_at` column at all.
5. **"open → in progress → done"** understates the machine: there are **four** statuses (`cancelled`), and
   **`open → resolved` is LEGAL**, so "skip straight to done" is not an illegal transition to probe.

None of this changes what was done; it changes what the founder should believe about scope. **The
unbounded-ladder problem is one state machine, not a product-wide class.**

---

## 8. #8 — NOTHING FIXED

🔬 `git diff --name-only` filtered by session mtime returns **zero tracked files**. The only repo writes
are this document and the harness's `.remember/` bookkeeping. **F-1 … F-18 stay open; this phase adds no
new finding of its own** — the one candidate (the keyboard row) was disproved by measurement, and the one
real gap (`resolution` textarea, below) is minor.

**The single new observation worth recording:** 🔬 in the Mark-resolved modal, `time_spent` carries the
accessible name **"Time spent"** and `parts` carries **"Parts cost"** — the brief's check passes — but the
`resolution` **textarea has no accessible name** (no `aria-label`, no associated `<label>`). A screen-reader
user is asked "what was done" by an unnamed field. Small, and on the same screen as the keyboard fix that
held.

---

## 9. NEAR-FALSE-POSITIVES — five new, **28 total**

24. **"The work-order row is a `<div>` no keyboard can reach."** Structurally true of the div, and it is
    the exact historical defect — but the title inside it is a real `<button>` that Enter and Space both
    activate. My selector looked for an `<a href>`. **I would have re-reported a fixed defect.**
25. **"`app/channels` REACHED."** It is HTTP **403 "Not in your plan"** — a plan gate, not a permission
    gate, so the denial copy differed and my regex missed it. **Verify a verdict by HTTP status, not by
    matching one gate's wording.**
26. **"An in-progress ticket offers no resolve control."** The resolve form is inside a modal; filtering
    to *visible* submits dropped it. It exists and works — it must be opened first.
27. **"The maintenance list is empty" (`tableRows: 0`)** while 26 ticket ids sat in the HTML — the list is
    not a `<table>`. **Count what the page actually uses, not what you expect it to use.**
28. **"All five probes refused, therefore the guard works."** Refusals alone cannot distinguish a guard
    from a broken path. Adding the **legal** `resolve()`-an-`open` call, which was **accepted**, is what
    made the other five conclusive. **A guard needs a positive control.**

---

## 10. HAND-FORWARD

**Findings open:** F-1 … F-18. No new numbered finding this phase. F-12 confirmed a third time.

**Baselines for Phase 11 — these moved, with causes in §5:**
`audit_events` **160** · `domain_events` **196**. Unchanged: `unit_status_logs` 6 · `reservations` 42 ·
`payments` 16 · `owner_ledger` 29 / 730640 · `reservation_status_history` 51 · `owner_expenses` 6 ·
`maintenance_tickets` 5 · `role_has_permissions` 260.

**Roles remaining:** **Revenue Manager** · Marketing Manager · Guest Support Agent — then the owner and
guest realms.

### ⛔ REVENUE MANAGER IS THE RISKIEST REMAINING PHASE — flagged as the brief instructs

📖 Applying a season **writes nightly rate rows**, and 📖 nothing records what they replaced. If that
holds, it is **the purest establish-never-perform case in the programme**: a write with no recorded prior
value cannot be restored field by field, which is the discipline every phase since 8 has relied on.
**Verify that claim first, by reading** — if a prior-value trail *does* exist, the phase opens up; if it
does not, the season apply must be established from code and never executed. 📖 The brief also records
that the **agreed-rate disclosure built for that role has never been seen by it** — worth surfacing.

**The remaining state machines to check, now that the pattern is known** — and the method that settles each
is the one used here: read whether a guard exists, then whether it is *called*, then probe at the service
layer inside a rolled-back transaction **with a legal call as the positive control**:

1. **Payouts** — `pending → processing → paid → failed`, plus `held`. Money-bearing; highest value.
2. **Owner statements** — `finalize()` is irreversible (Phase 7); is there a source-state guard?
3. **Owner expenses** — `pending → approved | queried` (#68); moves the ledger.
4. **Rate proposals** — `pending → approved | declined | withdrawn` (#67).
5. **Subscriptions** — trial → active → lapsed, and `SubscriptionStatus` **fails OPEN**.
6. **Reviews** — moderation states.
7. **`reservation_units`** — `active | overbooked | cancelled`, the inventory spine.
8. **Guest change requests** — `GuestChangeRequestService:104` writes `'status' => 'confirmed'` directly.

**Method notes worth keeping:**
- **A guard needs a positive control.** Five refusals prove nothing on their own (#28).
- **Read the layer that writes, not the one that routes.** Maintenance's controller contains no status
  logic at all — judging it by the controller would have called it unguarded.
- **An element can be the old defect and still be fixed.** The div-with-click is really there; the fix
  was to put a real button inside it (#24).
