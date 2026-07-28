# Owner Payouts + Payout Method — wireframe-fidelity audit (⚠️ REAL MONEY + REAL BANKING)

**Date:** 2026-07-28 · **Author:** build session · **Status:** PHASE 1 (read-only) complete — **awaiting approval before any build.**

Founder brief: make the LIVE **owner Payouts** (`/owner/payouts`) and **Payout Method** (`/owner/payout-method`) pages
match their two decoded wireframes (`Owner Payouts.html`, `Owner Payout Method.html`) EXACTLY — design + behaviour — with
REAL payout data + real actions where they exist, and HONEST labelling of the account-gated banking bits.

> ⚠️ **THIS IS REAL MONEY + REAL BANKING.** Bank accounts, micro-deposit verification, where payouts are sent. A faked
> verification / micro-deposit / payout / bank detail is **dangerous**, not just dishonest. So the investigate step below
> (REAL-NOW vs ACCOUNT-GATED vs NOT-BUILT) is load-bearing, and this audit **pauses for approval** before building.

Same exact-match discipline as Overview #54 / My Units #55 / Bookings #56 / Statement #57, plus the banking honesty overlay.

---

## 0. Method
- **Decoded both `dc-runtime` wireframes** (`JSON.parse(<script __bundler/template>)` → `<x-dc>` markup + tokens) and
  rendered both over `:8899` for the visual. Both are owner APP pages (reuse the shared owner chrome).
- **Investigated the payout backend** — `Payout` + `PayoutMethod` models, `OwnerPayoutService` (+ the tax-hold +
  rail), `OwnerPayoutMethodService`, `OwnerPayoutMethodController`, **`OwnerConnectController`** (Stripe Connect),
  the rails (`ManualPayoutRail` / `StripeConnectPayoutRail`), the `Owner` model fields, the remittance PDF, and
  `docs/STRIPE-PRODUCTION.md`.
- **Audited the live pages** logged in as the demo owner + read the two Livewire components + views.
- Cross-referenced `docs/design/DIVERGENCE-REGISTER.md` rows 130 (Payouts · AT PARITY) + 136 (Payout Method ·
  INTENTIONAL DIVERGENCE / account-gated #45) and re-verified their refusals.

### What the repo REALLY has (the decisive investigation)
| Capability | Status | Evidence |
|---|---|---|
| Payout **history** (amount · date · rail · status · statement link) | ✅ REAL | `OwnerPayouts` → `OwnerPortalService::payouts()`, owner-scoped; `Payout.status ∈ pending/paid/failed/reversed` |
| Next-payout **breakdown** (gross − 18% − fees − refund == net) | ✅ REAL, reconciles #17 | the source statement's real `owner_statement_lines` |
| Payout **lifecycle stepper** (Finalized → Pending → Paid, + Failed/Reversed) | ✅ REAL states only | `OwnerPayouts::lifecycle()` — **no** "Scheduled"/"On hold" enum exists |
| **Remittance advice PDF** per payout | ✅ REAL, CAP_EXPORTS | `owner.payouts.remittance` → `OwnerRemittanceDocument` |
| **Bank account** capture (holder · IBAN · label · currency) | ✅ REAL | `OwnerPayoutMethodController::store` → `OwnerPayoutMethodService::add`; IBAN **encrypted** (`PayoutMethod.iban` cast `encrypted`), **mod-97 validated**, **masked** display (`Iban::masked` from `iban_last4`, #28) |
| **Set default / remove** account | ✅ REAL | `setDefault` / `destroy` (self-only, owner-scoped, `$capPayout` co-owner) |
| **Stripe Connect** onboarding (Standard account, hosted KYC) | ✅ REAL **test-mode** · live = a cutover | `OwnerConnectController::start` → `StripeClientInterface::createConnectAccount/AccountLink`; `owner.stripe_connect_account_id`; `stripe_connect_payouts_enabled` flips on the `account.updated` **Connect webhook** (test-mode-proven; the live-key flip is Subhan's — `docs/STRIPE-PRODUCTION.md`) |
| **Live payout transfer** (Connect) | 🔒 ACCOUNT-GATED | `StripeConnectPayoutRail` (double-transfer-guarded); runs only when `PAYOUTS_RAIL=stripe_connect` + a live key; the build uses `ManualPayoutRail` |
| **Micro-deposit verification** / a `Verified`/`Verifying` account STATE | 🚫 NOT MODELLED | `PayoutMethod` has **no** verification/status column — a "Verified" badge would be **fabrication** |
| **Payout preferences** (frequency · payout-day · minimum · pause) | 🚫 NOT-BUILT (no store) | `OwnerPayouts` docblock: "payout-preference controls with no field … disabled Soon"; only `owner.payout_currency` is real |
| **Upcoming-schedule projections** ("~€9,850 trailing average") | 🚫 NOT-BUILT | no forecast engine; the live page shows an honest "not yet available" |
| **Tax & remittance details** (payee type · legal name · VAT · country) | ✅ REAL owner fields | `Owner.type` · `legal_name` · `vat_number` (encrypted) · `tax_country` · `tax_id` (encrypted); currently edited on the **Tax Center**, linked from Payout Method |
| Registered **address** | 🚫 NOT MODELLED | no address column on `Owner` (the wireframe's "[object Object]" is a mock bug) |
| The **tax-hold** ("payout held until tax details on file") | ✅ REAL | `OwnerPayoutService::pay()` throws `PayoutHeldException` when `!owner->hasTaxDetails()`; `OwnerNextPayout` surfaces the paused state |

### The demo owner's REAL state (what these pages show today)
**6 real payouts** (5 paid + 1 pending — created by the GATE-1 statement-history seed; `rail=manual`): paid-this-year
**€2,804.40**, pending **€885.60**, the Sep breakdown reconciling to €885.60. **0 payout methods** (→ the Overview
"Confirm your payout bank account" attention item; no IBAN on the rail). **No tax details** (→ the tax-hold "Paused" +
the "Add tax details" attention). **No Connect account.** So the live Payout Method page is an honest EMPTY state today.

---

## 1. DELIVERABLE A — Owner Payouts page (`/owner/payouts`)

### 1a. Element classification (wireframe → live → category → resolution)

| # | Wireframe element | Live today | Category | Resolution |
|---|---|---|---|---|
| A1 | **On-hold banner** "September payout is on hold · **Verification pending**" + "Complete verification" | none | **ACCOUNT-GATED (the "verification" reason) → render the REAL hold reason** | The bank-**verification** hold is account-gated + NOT modelled → never faked. BUT a REAL hold exists — the **tax-hold** (pending payout + no tax details). Render the banner's exact design ONLY on a real hold, reason = "held until your tax details are on file" → link Tax Center. No fake "verification pending". |
| A2 | Dark **NEXT PAYOUT** rail (amount · status · stepper · gross/fee/net breakdown) | ✅ present | **REAL-NOW** | Match exactly; the breakdown already reconciles (#17). |
| A3 | Rail **IBAN ···4821** pill | none (no method) | **REAL-NOW (needs a method)** | `OwnerNextPayout` masked IBAN (CAP_PAYOUT). Renders once a payout method exists (D-PAY-1 demo seed). |
| A4 | 3 KPIs — Paid this year · Next payout · Pending | ✅ present | **REAL-NOW** | Match exactly. |
| A5 | **Payout history** (month · rail · date · status · amount · Statement · **Receipt**) | ✅ present (Statement + remittance link) | **REAL-NOW** | Match exactly; the per-row "Receipt" = the real remittance PDF (CAP_EXPORTS). |
| A6 | **Export** (payout history) | button (verify wired?) | **REAL-NOW (small build)** | A real owner-scoped payout-history CSV (mirrors the statement-history CSV #57). |
| A7 | **Payout account** card (Revolut · IBAN · Default · **Manage**) | "Manage · Soon" | **REAL-NOW (needs a method)** | Wire to the real default `PayoutMethod` (masked IBAN #28); Manage → `owner.payout-method`. Renders once a method exists. |
| A8 | **Payout preferences** (Frequency · Payout day · Minimum · Currency · **Pause**) | Currency real; rest "Soon" | **currency REAL · rest NOT-BUILT → D-PAY-2** | Build a real owner-scoped preferences store (the owner's PREFERENCE), labelled that the actual cadence/execution is confirmed with the operator + the live rail. NEVER a fake schedule. |
| A9 | **Upcoming schedule** (held · **Projected ~€9,850** trailing average) | "Not yet available" | **NOT-BUILT (forecast)** | Keep honest — the REAL held/pending row is shown; the "Projected" forecast stays an honest "not yet available" (no fabricated projection, #39). |

### 1b. Net for Payouts
Almost everything is **REAL-NOW** (history, KPIs, breakdown, stepper, remittance PDF) or a **small real build** (Export CSV,
the payout-account card + IBAN once a method exists, the preferences store D-PAY-2). The only genuinely **account-gated /
not-built** bits are the **bank-verification** hold reason (→ show the real tax-hold instead), the **projected** upcoming
schedule (→ honest empty), and the live transfer rail (already deferred). Nothing is faked.

---

## 2. DELIVERABLE B — Owner Payout Method page (`/owner/payout-method`)

### 2a. Element classification

| # | Wireframe element | Live today | Category | Resolution |
|---|---|---|---|---|
| B1 | **Connect Stripe** banner + button | ✅ present | **REAL-NOW (test-mode) · live = cutover** | Match exactly; `OwnerConnectController` starts real Connect onboarding (SAQ-A, KYC on Stripe). `stripe_connect_payouts_enabled` flips on the live webhook — labelled honestly. |
| B2 | **Bank accounts** list (multiple · default radio · delete) | ✅ present (empty for demo) | **REAL-NOW** | Match exactly — list the owner's real methods (masked IBAN #28), set-default radio (`setDefault`), delete (`destroy`). |
| B3 | Account **Verified / Verifying · "2 deposits sent" · Confirm** (micro-deposits) | none | **🚫 ACCOUNT-GATED / NOT MODELLED → honest** | `PayoutMethod` has NO verification field. Render an added account as **"on file"** (or "pending verification — activates with your live banking connection"), NEVER a fake "Verified"/micro-deposit/Confirm. The micro-deposit sub-flow is the exact design **disabled/labelled pending live**, never a faked deposit amount. |
| B4 | **Add a bank account** form (holder · IBAN · label · currency) | ✅ present | **REAL-NOW** | Match exactly; encrypted IBAN, mod-97 validated, self-only, `$capPayout`. |
| B5 | Dark **PAYOUTS GO TO** rail (default account · next payout · est. date) | ✅ present (empty for demo) | **REAL-NOW (amount/account) · est. date NOT-BUILT** | Default account + next-payout amount are real; the estimated **date** has no field → omit (as the Statement/Overview do, #39). |
| B6 | **Protected changes** card ("verify via micro-deposits · 48h hold on default change · email on change") | none | **ACCOUNT-GATED policy copy → honest** | Present the card's design; state these protections **activate with the live banking connection** (the micro-deposit + 48h-hold are the Connect rail's, not built). Don't imply they're active today. |
| B7 | **Payout not arriving? · SEPA trace within 2 days** + Contact support | none | **🔒 ACCOUNT-GATED → honest** | SEPA tracing needs the live rail. Keep the card's design; "Contact support" → the real owner↔operator Messages. No fake trace. |
| B8 | **Payout schedule** panel (Frequency · Payout day · Minimum · Currency locked · Save) | none (schedule lives nowhere) | **currency REAL · rest NOT-BUILT → D-PAY-2** | Same preferences store as A8 (ONE store, shown on both pages). |
| B9 | **Tax & remittance details** (Payee type · Legal name · VAT · Country · Address · Save) | linked to Tax Center | **REAL owner fields → D-PAY-3** | `Owner.type`/`legal_name`/`vat_number`/`tax_country` are real. Build inline (matching the wireframe) writing the SAME Owner fields as the Tax Center (ONE store, two entry points — anti-drift), feeding the remittance PDF (today it prints `owner->name`; upgrade to `legal_name` when set). **Address is NOT modelled** → omit (or a plain optional field — founder call). |

### 2b. Net for Payout Method
The **account list + add + set-default + remove + Connect onboarding** are all **REAL** (Connect test-mode-proven). The
**verification / micro-deposit / SEPA-trace / 48h-hold** are genuinely **account-gated** (the live banking rail) — render
the exact design, labelled honestly, **never faked**. **Tax & remittance details** are **REAL** owner fields (D-PAY-3).
The **schedule/preferences** need a real store (D-PAY-2).

---

## 3. Stale-refusal re-verification (DIVERGENCE rows 130 / 136)
- Row 130 (Payouts, AT PARITY) refused the IBAN/payout-account, the preferences editors, and the upcoming schedule. The
  **IBAN/payout-account is now REAL** (`OwnerNextPayout` masked IBAN + the real default `PayoutMethod`); the **preferences**
  become real with a small store (D-PAY-2); the **projections** stay honestly deferred.
- Row 136 (Payout Method, account-gated #45) refused the multi-account/verified/schedule/tax editors. The **account
  list/add/default/remove + Connect** are **REAL** (Connect was wired in Phase 8 · SG3 AFTER that pass — the refusal is
  stale); **verification/micro-deposits** stay genuinely account-gated (never faked); **tax & remittance** is real
  (D-PAY-3); the **schedule** needs the store (D-PAY-2).

---

## 4. DECISIONS for the founder (please confirm before GATE 1)

- **D-PAY-1 — Seed a flagged demo payout method (recommend: YES, WITHOUT tax details).** The demo owner has 0 methods →
  both pages show empty banking. Seed ONE flagged demo `PayoutMethod` (e.g. "Revolut Business", a **real valid IBAN**,
  encrypted, masked ···NNNN, EUR, default) so the pages demo their full shape and the Statement/Overview IBAN chip (#57/#54)
  renders. **Do NOT seed tax details** — that keeps the tax-hold "Paused" + the "Add tax details" attention demo intact
  (only the "Confirm your payout bank account" attention resolves, honestly, because a method now exists). *Alternative:*
  keep 0 methods (fully honest-empty, but sparse + no IBAN anywhere). **Recommend the seeded method, no tax details.**
- **D-PAY-2 — Build a real owner-scoped payout-preferences store (recommend: YES).** A new `owner_payout_preferences`
  store (frequency · payout-day · minimum · pause; currency stays `owner.payout_currency`), written on a `$capPayout`
  controller route (#20), audited — the owner's **stated preference**, labelled honestly that the **actual cadence +
  execution are confirmed with your operator and the live payout rail** (never a fake "next payout on the 3rd"). Mirrors
  the `owner_monthly_target` pattern (#56). *Alternative:* keep the controls "Soon". **Recommend the real store.**
- **D-PAY-3 — Tax & remittance details on Payout Method (recommend: inline, ONE shared store).** Build the tax-details
  form inline (matching the wireframe) writing the SAME `Owner` fields the Tax Center edits (type · legal_name · vat_number
  · tax_country — encrypted where sensitive), via a shared path so the two entry points can't drift; feed the remittance
  PDF (upgrade its payee from `owner->name` to `legal_name` when set). **Address stays omitted (not modelled).**
  *Alternative:* keep the Tax-Center link (current). **Recommend inline + shared store.**
- **D-PAY-4 — The account-gated banking (verification / micro-deposits / SEPA-trace / 48h-hold / the on-hold-verification
  banner): EXACT design, HONESTLY labelled pending the live banking connection — NEVER faked** (no fake Verified, no fake
  deposit amounts, no fake "payout sent", no staged failure). The on-hold banner surfaces ONLY the real tax-hold. **This
  is the non-negotiable banking-safety stance; confirm you're happy with "exact design, honestly disabled" for these.**

---

## 5. Honesty + security ledger (MUST hold)
- **IBAN masked, never full** (#28) — `Iban::masked` from `iban_last4`; the full IBAN stays `encrypted` at rest.
- **No fabricated money-movement or verification** (#39) — no fake payout, no fake verified/micro-deposit, no fake
  projection; real payout states only (the demo's real pending + the real tax-hold); the account-gated bits are the exact
  design, labelled pending-live.
- **Reconciliation** (#17) — the next-payout breakdown = the source statement's real lines (gross − 18% − fees − refund
  == net), to the cent.
- **Writes** — every bank/preference/tax write is a **controller route** (#20), **`$capPayout` co-owner-only** (#28/#42
  — the payout account is co-owner-only; an accountant/view-only can't reach it), **encrypted** where sensitive, audited.
  **Account-holder-must-match / no third-party payout** stays the real rule (Connect onboards the owner's OWN account).

---

## 6. Build plan (after approval)
- **⛔ GATE 1 — Payouts page:** exact design (all states/animations) + the REAL parts (history, KPIs, breakdown, stepper,
  remittance PDF, the payout-account card + masked IBAN, Export CSV) + the real tax-hold banner (real reason only) + the
  preferences store (D-PAY-2) + honest account-gated states (projections/verification). Tests (reconcile #17, masked IBAN
  #28, CAP gating, no fabricated state, no `/livewire/update`) + browser-verify + commit.
- **⛔ GATE 2 — Payout Method page:** exact design + the REAL parts (account list/add/default/remove, Connect test-mode,
  payouts-go-to, tax & remittance inline D-PAY-3) + the account-gated banking (verification/micro-deposits/SEPA/48h-hold)
  as the EXACT design honestly labelled pending-live (never faked). Consolidate docs (this file, DECISIONS, DIVERGENCE rows
  130/136, PROJECT-STATE, invariant list). Tests + browser-verify + commit + PUSH.

**Bottom line:** far more is REAL than the register's old "account-gated #45" suggested — history/breakdown/remittance +
the whole bank-account CRUD + Stripe Connect (test-mode) are real; a small real build adds the payout-account card, Export
CSV, a preferences store, and inline tax details. The genuinely account-gated banking (micro-deposit **verification**, the
live **transfer** rail, SEPA **tracing**, the 48h **hold**) is reproduced EXACTLY but labelled honestly pending the live
connection — and **never faked**, because here a fake is dangerous.
