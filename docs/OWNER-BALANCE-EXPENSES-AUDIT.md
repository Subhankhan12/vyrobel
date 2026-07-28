# Owner Balance + Expenses — wireframe-fidelity audit (⚠️ Balance touches real-money movement)

**Date:** 2026-07-28 · **Author:** build session · **Status:** PHASE 1 (read-only) complete — **awaiting approval before build.**

Founder brief: make the LIVE owner **Balance** (`/owner/balance`) and **Expenses** (`/owner/expenses`) pages match their
decoded wireframes (`Owner Balance.html`, `Owner Expenses.html`) EXACTLY — design + behaviour — with REAL data + actions
where they exist, HONEST where account-gated. ⚠️ **Balance's "Withdraw early" is instant real-money movement to a bank
account** — the Payouts investigation (#58) established there is **no automated payout/transfer engine**, so instant
withdrawal is account-gated and **never faked** (no fake withdrawal / transfer / "in transit"). Same discipline as the
prior owner exact-match gates (#54–#58) + the banking-honesty overlay from #58.

---

## 0. Method
Decoded both `dc-runtime` wireframes (rendered over `:8899`); read the live components (`OwnerBalance`, `OwnerExpenses`),
views, `OwnerExpense` model + `OwnerExpenseService`, and the `owner_ledger` / `Payout` / `ManagementAgreement` chain;
carried the #58 Payouts findings (no transfer engine; the tax-hold is real); audited both live pages in the browser;
cross-referenced `docs/design/DIVERGENCE-REGISTER.md` rows 129 (Balance · AT PARITY) + 147 (Expenses · AT PARITY).

### What the repo REALLY has (the decisive investigation)
| Capability | Status | Evidence |
|---|---|---|
| Owner **ledger** (append-only, running balance, per-currency) | ✅ REAL | `OwnerBalance` → `OwnerLedgerEntry` (Σ `signedMinor`); the live page already shows it |
| **Paid to date** (Σ paid payouts) · **pending payouts** | ✅ REAL | `Payout.status ∈ pending/paid/failed/reversed` |
| The **tax-hold** ("held until a tax ID is on file") | ✅ REAL | `OwnerPayoutService::pay()` throws `PayoutHeldException` when `!hasTaxDetails`; `OwnerNextPayout.paused` (#58) |
| The inline **Add tax details** path | ✅ REAL | `owner.tax.save` (#58 wired it inline on Payout Method) |
| **Auto-payout cadence** | ✅ REAL | `ManagementAgreement.payout_schedule` (live) + the new `owner_payout_preferences` (#58) |
| The **"Available / On hold / In transit"** clearing states | ✅ REAL (derivable + reconciling — refusal STALE) | derivable from the ledger + payouts + the real tax-hold (see B-1); the live docblock's "no clearing concept exists" predates the #58 tax-hold |
| **Withdraw early** / instant transfer / "in transit" money-movement | 🔒 ACCOUNT-GATED | NO owner-initiated transfer rail (`StripeConnectPayoutRail::send()` is the deferred live seam; `ManualPayoutRail` is operator-run) — a withdrawal/transfer/"in transit" would be **fabricated money movement** |
| Owner **expenses** (deductions), by-category, tri-state deductible | ✅ REAL | `OwnerExpense` + `OwnerExpenseService`; the live page + the Batch-B proportional by-category bars |
| Expense **APPROVAL** (owner approves/queries a pending expense) | 🚫 NOT-BUILT / NOT A REAL WORKFLOW | `OwnerExpense` has NO approval/status field; deductibility is set by the **management agreement**, NOT the owner (the live docblock refuses drawing it — "implies a state that does not exist"). Also NO **vendor** field. |
| Expense **Request maintenance** (owner→operator) | ✅ REAL (relay exists) | the owner↔operator Messages relay (`owner.messages.start`, #28) — like the My Units message-relay; currently "Soon" here |

### The demo's REAL state
6 statements (5 paid + 1 pending/**tax-held**), net position **€3,510** (= €3,690 statemented − a €180 un-statemented
expense), Paid **€2,804.40**, the Sep pending payout **€885.60 tax-held**, **0** non-held pending, and **1** owner expense
(Dishwasher pump replacement · €180 · Maintenance · deductible). No tax details (→ the tax-hold). A default payout method
(···1000, from #58).

---

## 1. DELIVERABLE A — Owner Balance (`/owner/balance`)

### The reconciling clearing model (B-1) — the honest derivation
The wireframe's three KPIs map to REAL, reconciling figures (identity: **Available + On hold + In transit + Paid = Net
position**, per currency, to the cent #17):
- **On hold** = Σ pending payouts that are **tax-held** (the real tax-hold — matches the wireframe's "tax details needed").
- **In transit** = Σ pending payouts **not** held (finalised + released, on their way per the schedule). €0 in the demo
  (the only pending is tax-held). ⚠️ NOT an actively-sent transfer (no engine) — it is "released, awaiting the run".
- **Available** = Σ **unstamped** ledger net (earnings cleared this period, not yet in a finalised statement) = the
  current accrual that flows into the next payout. **NOT instantly withdrawable** (the withdraw is gated).
- **Paid to date** = Σ paid payouts (shown in the ledger / as a sub-figure).
- The current live page conflates ALL pending as "In transit" — the exact-match FIX splits held (On hold) vs non-held
  (In transit), so "In transit" honestly reads €0 for the demo (nothing actively moving).

### Element classification
| # | Wireframe element | Live today | Category | Resolution |
|---|---|---|---|---|
| A1 | 3 KPIs **Available / On hold / In transit** | Net position / In transit (all pending) / Paid | **REAL (refusal STALE)** | The reconciling model B-1 (On hold = tax-held pending; In transit = non-held pending; Available = unstamped ledger net). Reconciles to Net (#17). |
| A2 | **Withdraw early** card + "Withdraw €X" + "arrives 1–2 days to IBAN" | disabled "Withdraw · Soon" + honest note | **🔒 ACCOUNT-GATED** | Exact design; the masked IBAN is REAL (`OwnerNextPayout`, #28, CAP_PAYOUT); the button is honestly disabled/labelled "automated month-end payout — instant withdrawal activates with the live payments integration". NEVER a fake withdrawal/transfer; NO ETA; "in transit" is a real figure (€0), never a staged movement. |
| A3 | **Why is €X held?** + Add tax details | omitted | **REAL (refusal STALE)** | The real tax-hold explainer (a held pending payout + no tax details) + "Add tax details" → the real inline tax path (`owner.tax`). Shown only when a real hold exists. |
| A4 | **Balance activity** ledger (movement · date · statement · amount · running balance) + Export | ✅ present (running balance) | **REAL-NOW** | Match the wireframe's row treatment (state chips Available/On hold/Settled/Paid derived from the real entry + its statement/payout). Export = a real balance CSV (currently deferred). |
| A5 | **Auto-payout schedule** (Monthly · next run) | ✅ present | **REAL-NOW** | Real (agreement `payout_schedule` / the #58 preferences store). The "next run 5 Nov" DATE has no field → omit or a derived cadence label only (no fabricated date, #39). |
| A6 | **How money clears** (dark, descriptive) | omitted | **REAL (descriptive)** | A true description of the real flow (stays clear → statement finalises → refunds/expenses netted → payout). |

### Net for Balance
The whole clearing model + the tax-hold explainer are **REAL** (their backing — the tax-hold — became real in #58; the
live "omitted, no such concept" is stale). The activity ledger + schedule + Export are real. The ONLY account-gated piece
is **Withdraw early** (instant transfer) — exact design, honestly disabled, never faked; "In transit" is a real €0.

---

## 2. DELIVERABLE B — Owner Expenses (`/owner/expenses`)

### Element classification
| # | Wireframe element | Live today | Category | Resolution |
|---|---|---|---|---|
| B1 | **Spent this year** · **Charged to payouts** KPIs | ✅ Total / Deductible | **REAL-NOW** | Match: Spent = Σ live expenses; Charged = Σ deductible charged to statements (real ledger). |
| B2 | **Awaiting your approval** KPI + **Needs your approval** section (Approve / Query cards · "auto-approved in 7 days") | **omitted** | **🚫 NOT A REAL WORKFLOW → honest (D-BE-3)** | `OwnerExpense` has NO approval entity; deductibility is set by the **management agreement**, not owner-approved. NEVER a fake Approve/Query (changes nothing). Render the section's DESIGN as an **honest explainer** (deductibility is agreement-governed + itemised on statements); the 3rd KPI = the real **Pending review** count (deductibility unresolved — honestly labelled, not "your approval"). A real owner-approval subsystem is a net-new feature that changes the money model — NOT recommended. |
| B3 | **Expense history** (date · desc · category · **vendor** · villa · amount · status) + unit filter + Export | ✅ present (no vendor/villa filter) | **REAL-NOW (no vendor)** | Match; real rows via the source (`Maintenance ticket`…); **vendor is NOT modelled** → omit (the wireframe's "Coastal Mechanical" is placeholder). Add the unit (villa) column/filter (real `unit_id`). Export = a real expenses CSV (currently deferred). |
| B4 | **By category** proportional bars | ✅ present (Batch-B bars) | **REAL-NOW (reuse)** | Reuse the Batch-B proportional bars (already reconciles #17). Match the wireframe's per-category colours (cosmetic). |
| B5 | **How expenses work** (dark, descriptive) | ✅ present | **REAL (descriptive)** | Keep/match. |
| B6 | **Need something fixed? · Request maintenance** | disabled "Soon" | **REAL (relay exists) → wire it (D-BE-4)** | Wire to the real owner↔operator Messages relay (`owner.messages.start`, #28 — name-only), like the My Units message-relay. |

### Net for Expenses
Almost all REAL (KPIs, history, by-category [reuse], how-it-works) or a small real wire (Request-maintenance relay, Export
CSV, the unit column/filter). The ONLY genuinely-not-real piece is the **owner expense-approval** workflow (no entity;
agreement-governed) — rendered honestly, never a fake approve/query.

---

## 3. DECISIONS for the founder (please confirm before GATE 1)
- **D-BE-1 — Balance "Withdraw early" = ACCOUNT-GATED, honest, never faked (recommend; non-negotiable).** Exact design;
  the masked IBAN is real (CAP_PAYOUT); the button is honestly disabled/labelled ("automated month-end payout — instant
  withdrawal activates with the live payments integration"). No fake withdrawal/transfer/ETA; "In transit" is a real €0.
- **D-BE-2 — Balance clearing model = the reconciling identity (recommend).** On hold = tax-held pending; In transit =
  non-held pending; Available = unstamped ledger net; **Available + On hold + In transit + Paid = Net position** to the
  cent (#17). Available is honestly labelled "cleared this period" (not "instantly withdrawable"). **Open question:** in
  the demo, Available ≈ −€180 (a pending expense; nothing un-statemented positive) — (a) keep it honest (Available reflects
  the real accrual, small/negative), or (b) seed a little **flagged** un-statemented current-period revenue so the demo
  shows a positive Available (like the prior flagged seeds). *Recommend (b): a small flagged seed* for a compelling demo.
- **D-BE-3 — Expenses "Needs your approval" = HONEST, no fake approve/query (recommend).** Render the section's design as
  an honest explainer (deductibility is set by your management agreement + itemised on statements); the 3rd KPI = the real
  **Pending review** count (honestly labelled). *Do NOT build a fake approval, and do not build a net-new owner-approval
  subsystem* (it contradicts the agreement-based model). *Alternative:* a real approval store — NOT recommended.
- **D-BE-4 — Expenses "Request maintenance" = a real message-relay (recommend).** Wire to `owner.messages.start` (#28,
  name-only), like My Units — replacing the "Soon" stub.
- **D-BE-5 — Optional demo richness (recommend a small flagged seed).** A few more flagged demo expenses (varied
  categories) + the un-statemented earnings from D-BE-2 so both pages demo their full shape. Additive; never blocks.

---

## 4. Honesty + security ledger (MUST hold)
- **#39 no fabrication:** NO fake withdrawal / transfer / "in transit" (Balance); NO fake approve/query (Expenses). "In
  transit" is a real figure (€0 demo); "Available" is a real reconciling residual.
- **#17 reconcile:** the clearing identity ties to the ledger to the cent; the by-category bars reconcile (Batch B).
- **#28:** masked IBAN never full (CAP_PAYOUT); owner-scoped throughout; guest/vendor data — the message-relay is name-only,
  vendor is omitted (not modelled).
- **#20:** any write (Request-maintenance) is a controller route + audited; exports CAP_EXPORTS-gated. Balance has no
  write (withdraw is gated).

---

## 5. Build plan (after approval)
- **⛔ GATE 1 — Balance:** exact design (KPIs, withdraw card, activity ledger, why-held, schedule, how-it-works — every
  token/animation) + the reconciling clearing model (B-1, #17) + the real tax-hold explainer + inline tax + the real
  ledger/schedule + a real Export CSV; **Withdraw early = honest account-gated (never faked)**; masked IBAN (CAP_PAYOUT).
  Tests (reconcile identity, tax-hold banner real-only, no fabricated movement, masked IBAN) + browser-verify + commit.
- **⛔ GATE 2 — Expenses:** exact design + real Spent/Charged/history (+ unit column/filter, no vendor) + the reused
  by-category bars + the real Request-maintenance relay + a real Export CSV; the **approval section = honest** (explainer +
  real Pending-review KPI, never a fake approve/query). Consolidate docs (this file, DECISIONS, DIVERGENCE 129/147,
  PROJECT-STATE, invariant list). Tests + browser-verify + commit + PUSH.

**Bottom line:** both pages are largely REAL — the Balance clearing model (On hold/In transit/Available) is a stale
refusal now derivable + reconciling (the tax-hold became real in #58), and Expenses is real bar the owner-approval
workflow (which isn't a real concept). The only genuinely account-gated piece is Balance's **instant withdrawal** — exact
design, honestly disabled, **never faked**. The only "not a real workflow" is the Expenses **approval** — rendered honestly,
never a fake approve/query.
