# Owner Rates + Remittance Advice PDF — exact-match audit (PHASE 1, read-only)

**Date:** 2026-07-30 · **Surfaces:** `/owner/rates` (`OwnerRates`) vs `Owner Rates.html`; the Remittance Advice PDF (`OwnerRemittanceDocument` + `OwnerRemittancePdfController` + `pdfs/owner-remittance`) vs `Owner Remittance Advice PDF.html`.
**Method:** decoded both dc-runtime bundles + read the real backends (`RateProposal` + `RateProposalService` #67, `OwnerRateService` + weekend-uplift, the guardrail search, `OwnerRemittanceDocument`, `OwnerNextPayout`, the statement/payout math). **Nothing changed.**

---

## 0. Shape of the gap

**Rates** — the owner Rates page today is a **direct-edit** model (the owner sets base + weekend-uplift + min-stay on their own exclusively-owned room types, real via `OwnerRateService`). The wireframe reframes it as a **manager-proposes / owner-governs** page: Proposed changes (approve/decline/**counter**) · read-only Current rates · **Pricing guardrails** · Where you sit. The rate-proposal engine (#67) is REAL (propose/approve/decline/withdraw) but is surfaced only in the attention feed, not on this page. So: **surface the real proposals here**, **BUILD the counter + the guardrails**, **honest-omit the comp-set**, and keep the real direct-edit as an addition.

**Remittance PDF** — **already exists and is already real + reconciling**: `OwnerRemittanceDocument` renders the payout's REAL `owner_statement_lines` (the same rows the statement screen shows, via `MoneyFormatter`), so it reconciles to the statement to the character — and the commission line is **already the real 18%** (it's the certified statement line, never a hardcoded 15%). The gaps vs the wireframe are the letterhead VAT/address, the masked IBAN, the payment-details block, and an est-settle date — which the current doc honestly omitted but the real backends can now supply.

---

## 1. PIECE A — RATES (element-by-element)

| # | Element | Real backing | Verdict |
|---|---|---|---|
| 1 | Header "Rates & pricing · Review the pricing your manager proposes and set the limits they work within." | — | ✅ restyle. |
| 2 | **Proposed rate changes** (count) — villa/season/rationale/effective/from→to/Δ% + **Approve / Decline / Counter…** + approved/declined states | Real `RateProposal` (#67): `approve()` applies via the certified `OwnerRateService`; `decline()` closes. Owner-scoped, CAP_ACTIONS, audited (#20). | ✅ **REAL (approve/decline) → surface on this page** (reuse `OwnerRateProposalController`). 🔨 **BUILD the COUNTER** — no `countered` state exists: add `STATE_COUNTERED` + `countered_rate_minor`/`countered_note`/`countered_at`/`countered_by` (additive; `state` is a `string(20)`, no enum migration) + `RateProposalService::counter()` (owner: pending→countered, nothing applied) + `OperatorRateProposalController::acceptCounter()` (operator: countered→approved, applies the owner's countered rate via `OwnerRateService`) — a real 2-way state, audited, surfaced to the operator (owner-detail proposals list). |
| 3 | **Current rates** — Villa · Base · Weekend · **Peak** · Occ | `OwnerRateService` (base + weekend-uplift real); occupancy real (`PerformanceMetrics`/calendar). **No PEAK tier modelled.** | ✅ **Base/Weekend/Occ REAL → read-only table.** 🚫 **PEAK — HONEST-OMIT** (no peak-season rate entity; the only tiers are weekday base + Fri/Sat weekend — showing a "Peak" number would be fabricated, #39). *(decision below.)* |
| 4 | **Pricing guardrails** — mode Dynamic/Fixed · Auto-approve within ±X% · Floor/night · Ceiling/night · Save | **No guardrail model exists.** | 🔨 **BUILD real + ENFORCING** — a per-owner `rate_guardrails` store (owner-scoped: `pricing_mode`, `auto_approve_pct`, `floor_minor`, `ceiling_minor`) + `owner.rates.guardrails` POST (CAP_ACTIONS, audited #20). **The guardrails GENUINELY constrain operator proposals** (wired into `RateProposalService::propose()`): a proposed rate below floor / above ceiling is **rejected**; within ±auto_approve_pct of current AND in-band → **auto-approved** (applied immediately, no owner action); otherwise → **pending** (needs owner approval). Mutation-proven — NEVER inert limits. (Guardrails constrain the MANAGER's proposals, not the owner's own direct edits — the honest reading of "the limits your manager prices within".) |
| 5 | **Where you sit** — Your average rate vs **Comp set average** + "Priced X% below market" | Your average = REAL (derived from the owner's real rates/ADR). **No market-rate feed exists.** | ✅ **Your average REAL.** 🚫 **Comp-set + "below market" HONEST-OMIT** — external market data Vyrobel does not have; a fabricated comp-set/market-position is #39. Show the real average alone + an honest "Market benchmarking coming" (account-gated). |
| — | **Direct rate edit** (the app's existing real capability — the owner sets their own exclusively-owned rates) | `OwnerRateService::apply` (base/weekend/min-stay). | 📐 **KEEP as a real addition** — the wireframe's Current-rates table is read-only (the manager proposes), but removing the owner's shipped power to set their OWN rates is a regression. Keep an "Adjust rates" editor path; document the divergence. *(decision below.)* |

---

## 2. PIECE B — REMITTANCE ADVICE PDF (element-by-element)

| # | Element | Real backing | Verdict |
|---|---|---|---|
| 1 | Letterhead — managing agent · **VAT** · **address** | `Organization.billing_company` / `billing_vat_id` / `country` (real when set). | 🔨 **BUILD (real, when set)** — add the VAT + country letterhead (same rule as the statement/booking-summary PDFs); the doc currently omits it (stale docblock). |
| 2 | Payout remittance — month · owner · units; **Net paid to owner**; statement ref · period · status | Real payout + statement (`sequence_no`, period, status, net). | ✅ REAL (present). Match the layout + add "RA-YYYY-MM" (period-derived, like "BS-YYYY-MM") + the real statement `sequence_no`. |
| 3 | **How it's calculated** — Gross · **Commission 15%** · Management fee · Cleaning · OTA commission · Prior-period adjustment · Net | The REAL `owner_statement_lines` (already rendered). | ✅ **REAL + RECONCILING (#17)** — the lines ARE the certified statement lines (commission at the **real 18%**, cleaning, `ota_cost`, carried-forward/adjustments, the fees-bucket #70, withholding #73 as present). **FIX = none on the math** (already 18%); style the rows to the wireframe. |
| 4 | Bank transfer · **masked IBAN ···· 4821** · est. date | The owner's payout method (`iban_last4`); `OwnerNextPayout` masks it CAP_PAYOUT-only (#28). Payout `paid_at` (real when paid); the payout-engine schedule (test-mode). | 🔨 **BUILD** — add the masked IBAN via the SAME CAP_PAYOUT rule as Payouts (never the full IBAN); est-date = `paid_at` when paid, else the real schedule / honest-projected. |
| 5 | Payment details — Beneficiary · Account · Method · Reference | `owner.legal_name`/name (real); `RailLabel` (method); `external_ref` (reference, when set). | 🔨 **BUILD** — the payment-details block from the real fields (masked account; method via `RailLabel`; reference the real `external_ref` or a derived `{ORG} {sequence_no}`). |

---

## 3. Reuse map (anti-drift)
- **`RateProposalService` + `RateProposal`** (#67) — the ONE proposal state machine; extended additively for the counter.
- **`OwnerRateService`** — base/weekend/min-stay + the certified apply (approve + auto-approve + direct-edit all go through it).
- **`OwnerRemittanceDocument`** — the ONE remittance builder (already reconciling to the statement lines); extended for VAT/IBAN/payment-details.
- **`OwnerNextPayout`** — the CAP_PAYOUT masked-IBAN rule (reused, not re-implemented).
- **the statement/payout math + `OwnerLedgerAttribution`** — the reconciling breakdown (unchanged).
- **`x-owner.shell` + `--vy-*`** (Rates page) · the `OwnerStatementDocument`/`OwnerBookingSummaryDocument` PDF pattern (Remittance).

## 4. ⚠️ DECISIONS (present for approval)
1. **The COUNTER + the GUARDRAILS are real BUILDS** (per the mandate): the counter is a real `countered` state (owner→operator→apply, audited); the guardrails are a real owner-scoped store that **genuinely enforces** on operator proposals (reject out-of-band / auto-approve in-threshold / else pending), mutation-proven. Confirm.
2. **Comp-set / "below market" — HONEST-OMIT** (no market feed; never fabricated). The real average shows alone + "market benchmarking coming". Confirm.
3. **PEAK column — HONEST-OMIT** (recommended — no peak tier is modelled; the real tiers are weekday base + Fri/Sat weekend). Alternative: keep the column showing a derived seasonal max (weaker). 
4. **Direct-edit — KEEP alongside the wireframe** (recommended — the owner's ability to set their own exclusively-owned rates is a shipped real capability; the wireframe's read-only Current-rates + manager-proposes model would remove it). Keep an "Adjust rates" editor path (documented divergence). Alternative: hide the editor (pure wireframe match, but a regression).

## 5. Build plan (two gates)
- **⛔ GATE 1 — the Rates page.** Rebuild to the wireframe's 4 sections on the shared chrome: **Proposed changes** (surface real `RateProposal`s + Approve/Decline + BUILD the **Counter**), **Current rates** (Base/Weekend/Occ real, Peak omitted; keep the real direct-edit as "Adjust"), **Pricing guardrails** (BUILD the real enforcing store), **Where you sit** (real average; comp-set omitted). New tests: the counter transition (owner→operator→apply), the guardrails ENFORCE (reject/auto-approve/pending — mutation-proven). Browser-verify. Commit.
- **⛔ GATE 2 — the Remittance PDF + consolidate.** Match `pdfs/owner-remittance` to the wireframe (VAT/address letterhead, the reconciling how-it's-calculated at the real 18%, masked IBAN [CAP_PAYOUT], payment-details, est-date); extend `OwnerRemittanceDocument` (VAT/IBAN/payment-details/est-date). Consolidate docs (this audit → resolved, DECISIONS #81, DIVERGENCE-REGISTER, PROJECT-STATE v26). New tests (the remittance reconciles at 18% + the masked IBAN is CAP_PAYOUT-gated). Browser-verify. Commit + push.

## ✅ RESOLUTION (built 2026-07-30 · invariant #81 · two gates)

**GATE 1 — the Rates page (`e5d8adb`).** Rebuilt `/owner/rates` to the wireframe's four sections on the shared chrome:
- **Proposed rate changes** — the REAL `RateProposal`s (#67) surfaced on the page: Approve/Decline reuse `OwnerRateProposalController`. 🔨 **BUILT the COUNTER** — a real `countered` state (`STATE_COUNTERED` + `countered_rate_minor`/`note`/`at`/`by`, additive) : `RateProposalService::counter()` (owner: pending→countered, nothing applied) + `acceptCounter()` (operator: countered→approved, applies the owner's rate via the certified path); `owner.rate-proposals.counter` (CAP_ACTIONS) + `app.rate-proposals.accept-counter` (rates.manage); `withdraw()` also closes a counter. Audited.
- **Pricing guardrails** — 🔨 **BUILT real + ENFORCING** — `rate_guardrails` (per-owner: mode/auto_approve_pct/floor/ceiling) via `owner.rates.guardrails` (CAP_ACTIONS). Wired into `RateProposalService::propose()`: below floor / above ceiling → REJECTED; within ±% of current (dynamic) → AUTO-APPLIED; else pending. Gated on an explicit saved row (absent = today's behaviour) — mutation-proven, never inert.
- **Current rates** — Base/Weekend/**Occ** (real; Occ via `PerformanceMetrics`); **Peak OMITTED** (no tier modelled). **Where you sit** — the REAL average alone; comp-set/market OMITTED ("benchmarking coming" — no feed). Kept the real **direct-edit** editor ("Adjust a rate directly") alongside.
- `OwnerRateService::applyForExclusiveOwner()` added — verifies exclusive ownership WITHOUT the owner scope, so the certified write works owner-side (approve) AND operator-side (auto-approve/accept-counter). `RateProposalTest` +6; `OwnerRatesTest` updated. Browser-verified (proposal + counter→countered, guardrails, honest where-you-sit).

**GATE 2 — the Remittance PDF.** The remittance PDF ALREADY existed + reconciled (it renders the payout's REAL `owner_statement_lines`, so the commission is already the real 18%). Matched to the wireframe by ADDING the real elements it honestly omitted: the **VAT/country letterhead** (`billing_company`/`billing_vat_id`/`country`, when set), the **masked IBAN** (the owner's `iban_last4` via `Iban::masked`, passed CAP_PAYOUT-gated by the controller — #28, never full, omitted otherwise), the **RA-YYYY-MM** reference (period-derived), and the **Payment details** section. The est-settle date stays honest (`paid_on` only once paid — never a projection). `OwnerRemittanceDocument::data($payout, $ibanMasked)` extended; `OwnerRemittancePdfTest` +1 (VAT + masked IBAN + RA + reconcile; IBAN omitted when null). Browser-verified: Gross €540.00 − commission €97.20 (**= 18%**) = net €442.80, masked IBAN ···· 1000, VAT letterhead, RA-2026-04.

Suite **1278/1278**; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean.

## 6. Guardrails held throughout
🔒 #28 owner-scoped + masked IBAN (CAP_PAYOUT) + guest data never on these surfaces · #17 integer money, the breakdown reconciles to the statement to the cent, the real 18% commission (never 15%) · #20 the counter + guardrails + proposal decisions are controller POSTs, capability-gated (CAP_ACTIONS owner / `rates.manage` operator) + audited · #42 the remittance PDF is CAP_EXPORTS-gated + owner-scoped · #39 no fabrication — no fake comp-set/market-position, no fake counter (a real state that really applies), no inert guardrail (it genuinely constrains proposals), no invented Peak tier.
