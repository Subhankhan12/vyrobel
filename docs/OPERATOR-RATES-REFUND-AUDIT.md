# Operator Rates & availability + Refund — PHASE 1 Audit (task #100)

**Wireframes:** `docs/design/raw/Operator Rates.html` · `docs/design/raw/Operator Refund.html` (both decoded).
**Existing surfaces:** `/app/rates` (`app.rates`, `RatesGrid` + `RateActionController`) · `/app/reservations/{res}/refund` (`app.reservations.refund-screen`, `ReservationRefund` + `ReservationFolioActionController::refund`). BOTH are exact-match rebuilds of EXISTING pages.
**Date:** 2026-07-31. Status: PHASE 1 read-only complete; PHASE 2 gated build pending founder decisions.

---

## RATES — the ONE certified writer already makes operator rates flow to the owner

Rates are stored **per-room-type** in `rate_calendar` (`rate_plan_id` + `date` + `price_minor`), written by the ONE certified `RateAndRestrictionService::setRate`/`setRateWeekendAware`/`setRestriction`. The operator grid (`RateActionController::apply`), the owner Rates page (`OwnerRateService`), and rate-proposal approvals **all funnel through this same service** → an operator rate write already lands in the SAME model the owner sees (reconciling, anti-drift). The existing grid + 3 KPIs + **cell-edit + bulk-edit all already exist and are REAL** (one POST form → `apply()`; single-cell = start==end, bulk = wider range / `room_type_id='all'`).

| Rates element | Verdict | Backend |
|---|---|---|
| Rate/availability grid (Open/Booked/rate) | ✅ **REAL** | `RatesGrid` reads `RateCalendar` + `Availability` (`available = total−sold−ooo−blocked`) |
| 3 KPIs (avg rate / booked / open) | ✅ **REAL** (per-currency, #17) | `RatesGrid::tiles()` |
| Cell-edit (base / min-stay / open-for-sale) | ✅ **REAL** (exists) | Alpine prefill → `RateActionController::apply` → `setRate`/`setRestriction` |
| Bulk-edit (units/nights/adjustment increase%/decrease%/set-exact, skip-booked) | ⚠️ **PARTLY** — a bulk range write exists, but %-increase/%-decrease/set-exact + skip-booked + "update derived" is **BUILD** | extend `apply()` |
| **Weekend uplift** on the grid | 🔨 **BUILD** | writer `setRateWeekendAware` exists (real Fri/Sat price); grid controller doesn't expose it |
| **Derived plans** (Non-refundable −10%, Weekly −15%) | 🔨 **BUILD** (Decision R1) | schema exists (`derived_from_rate_plan_id`/`adjustment`/`is_refundable`) but **no derivation code**; grid already says "no derived plans · Soon" |
| Rate-plans list (Base/Non-refundable/Weekly + channel scoping) | 🔨 **BUILD** (depends on R1) | `rate_plans` real; the list UI is omitted today |
| **Channels / Push** | 🚫 **HONEST-GATED (#85)** | `channels.status` has no app writer (dead enum); the real ARI push exists but the live Channex rail is account-gated → "Direct live; push needs OTA setup", never a fake "Synced" |
| **Smart pricing** suggestions | 🚫 **HONEST / real-signal (Decision R3)** | no pricing engine exists (the proto's sin() curve); a fabricated "smart" price is refused |
| Pacing · next 14 days | ⚠️ **real-signal-or-honest** | derivable from real bookings; the "+9% vs last year" trend needs prior-year data |
| Read-only guard on the write | ⚠️ **FIX** | `RateActionController::apply` has NO `isReadOnly()` check (a gap vs peers) |
| Proposals (#67) / guardrails (#81) | 📐 **separate surface** (Decision R2) | the operator grid writes DIRECTLY to tenant inventory; proposals are the operator→owner-**detail** surface where guardrails enforce (reject out-of-band, auto-approve in-tolerance). The grid does NOT route owner-owned units through proposals today |

⚠️ **Per-unit vs per-room-type:** the wireframe draws "Villas · 3 / Apartments · 2" (per-unit rows), but the rate model is **per-room-type** (a per-unit rate would silently edit a shared plan). The grid stays per-room-type (documented divergence) — a "Villa" room type row represents its units.

---

## REFUND — the money path is built + hardened; three real gaps

`FolioService::refundPayment(Payment, Money)` is the SINGLE bounded refund authority (#27): posts a NEW offsetting `refund` Payment row (correct-by-reversal — **not** a `folio_items` line, and never mutates the original), only-settled (`type∈[payment,capture]`/`status∈[succeeded,captured]`), same-currency, non-positive rejected, **cumulative-bounded with the guard BEFORE the gateway**, `recompute()` reconciles (#17). The existing per-payment picker (settled-only, real `remainingRefundableMinor`) + full/partial forms are REAL.

| Refund element | Verdict | Backend |
|---|---|---|
| Reverse a settled charge (reconciling #17) | ✅ **REAL** | `FolioService::refundPayment` — a `refund` Payment row; `recompute` reconciles |
| Only settled / Paid figure / per-payment picker | ✅ **REAL** | `settledPayments()` + `remainingRefundableMinor` + `FolioTotals` |
| Full-paid / custom amount | ✅ **REAL** | existing full + partial forms |
| **Per-charge** selection | 🔨 map to **per-PAYMENT** (real) | there is NO `folio_items↔payments` link (a credit note — different concept); the honest analog is per-settled-payment (which exists) |
| **TEST-MODE** (no real money until go-live) | ✅ **REAL / already safe** | `StripeSafetyGuard` binds `FakeStripeClient` outside production / for live keys — `refundPayment`'s gateway call moves nothing until `APP_ENV=production` + live key (same seam as payout engine #71 / New-Res payment) |
| **Owner statement adjusts** | 🔨 **BUILD (Decision F1)** | `refundPayment` posts NO owner-ledger line; the owner ledger has a generic `adjustment` entry_type but nothing wires a folio refund to it → BUILD a `payment.refunded` listener that posts an owner-scoped `adjustment` so it lands on the next statement close (anti-drift with `OwnerStatementBreakdown`) |
| **Reason** (chips + note) | 🔨 **BUILD (Decision F2)** | `refundPayment` has no `$reason` param + no column; store it on the refund Payment's `metadata` (JSON — no migration) via an additive optional param |
| **Masked card** (····4242) | 🚫 **HONEST-GATED (Decision F3)** | only `card_token` is stored (hidden, SAQ A) — NO brand/last4 exists; every existing surface deliberately shows the real **gateway** instead of a fabricated "····4242" |
| Refund-to (card / manual bank) | 🔨 **BUILD** | card = the gateway refund (test-mode); manual bank = a recorded manual refund (no gateway) — destination stored in metadata |
| Irreversible (confirm + audited) | ✅ **REAL** | append-only; `payment.refunded` audit in the service; add the confirm dialog + a review step |
| **#83 refund email** | ✅ **REAL / LIVE** (+ small BUILD) | `RefundConfirmationMail` fires on `payment.refunded` (succeeded) via `SendGuestTransactionalEmails`; the blade "Soon" stub is STALE. Small BUILD: add the `BrandsGuestMail` trait (#98) so it's brand-aware like the other guest emails |
| Receipt PDF | ✅ **REAL** (guest-side) | `doc.refund` / `GuestReversalDocument` exists (guest portal); link operator-side or honest |
| Read-only on refund | 📐 allowed | no pause gates refund today; the mandate says a refund may be allowed even when paused (returning money) — keep refunds un-blocked by the workspace pause |
| Permission | ✅ | `payments.refund` = Owner / Group Admin / Accountant only (NOT Hotel Manager / Front Desk) |

---

## ⚠️ CONSEQUENTIAL DECISIONS (founder approval before PHASE 2)

**R1 — Derived rate plans (Non-refundable −10%, Weekly −15%).** BUILD a real derivation engine (a derived plan's `rate_calendar` = base × the plan's adjustment; "update derived plans too" recomputes them on a base change — reconciling, never fabricated) **vs** honest-defer (keep the grid's "no derived plans · Soon"). *Recommend BUILD* — the wireframe features it prominently and it's a real, reconciling piece; but it's the largest new rates build.

**R2 — Operator grid write model.** Keep the grid writing **DIRECTLY** to the property's room types (rates flow to the owner via the shared `rate_calendar` — reconciling; proposals #67 + guardrails #81 stay the dedicated operator→owner-detail surface) **vs** route owner-owned units through proposals + enforce guardrails on the grid (a policy change + BUILD). *Recommend DIRECT (as today)* — the grid is the operator managing the org's inventory; the rates already reach the owner. Proposals/guardrails remain their own surface (not duplicated).

**R3 — Smart pricing.** HONEST — show real occupancy/pacing **signals** (no fabricated price suggestion; the "Smart pricing" panel becomes a real, clearly-derived "high-demand nights" hint or an honest "connect dynamic pricing · Soon") **vs** a real simple heuristic engine. *Recommend HONEST signals* — no dynamic-pricing engine exists; a fabricated "smart" price violates #39.

**F1 — Owner statement adjustment (the mandate's refund core).** BUILD a `payment.refunded` listener that posts an owner-scoped `owner_ledger` `adjustment` line (only for owner-attributed reservations) so the refund reduces the owner's net on the next statement close (anti-drift with the Statement/Balance the owner sees). *Recommend BUILD* — this is what "the owner statement adjusts" requires; without it the claim is fabricated.

**F2 — Refund reason.** Store the reason (+ optional note) on the refund Payment's `metadata` (JSON — no migration) via an additive optional param on `refundPayment` (the certified method), shown on the folio/reports. *Recommend BUILD (metadata)* — small, safe, additive.

**F3 — Masked card.** HONEST — show the real **gateway** (Stripe/Cash/Bank), never a fabricated "····4242" (no brand/last4 is stored; capturing it is a separate Stripe-integration change out of scope). *Recommend HONEST* — matches every existing surface's refusal to fabricate.

---

## Build plan (gated)

- **GATE 1 — Rates:** rebuild the grid to the wireframe (ribbon, grid, legend, KPIs, rate-plans list, channels-honest, smart-pricing-honest, pacing, cell-edit, bulk-edit) on the real model; extend `apply()` for %-adjust/set-exact + skip-booked + weekend-uplift + (R1) derived-plan recompute; add the **read-only guard**. Direct writes (R2); channel-push honest (#85, R3 smart pricing honest). Existing suite green + new tests (bulk %-adjust; weekend uplift; derived recompute).
- **GATE 2 — Refund:** rebuild to the wireframe (amount full/per-payment/custom · reason · refund-to card/bank · review · confirm · success) on `refundPayment`; (F1) owner-statement `adjustment` listener; (F2) reason in metadata; (F3) honest gateway (no fake card); test-mode (already safe); the live #83 email (+ brand trait); receipt link. Then consolidate docs + push.

Every write: controller-routed, gated (`rates.manage` / `payments.refund`), audited (in the service), read-only-respecting where apt. 🔒 operator-scoped; #17 refund reconciles + owner statement adjusts; #27 cumulative bound; #28 no card fabrication; #39 no fake rate/derived/sync/suggestion/adjustment; test-mode (no real money).

---

## FOUNDER DECISIONS (2026-07-31 — approved, PHASE 2 authorized)

- **R1 → BUILD the derived-plan derivation engine.** A derived plan's `rate_calendar` = base × its adjustment; a base change recomputes Non-refundable (−10%) + Weekly (−15%); "update derived plans too" in bulk-edit recomputes them. Reconciling, never fabricated.
- **R2 → KEEP grid writes direct.** The operator grid writes rates directly to the org's room types (they reach the owner via the shared `rate_calendar`). Proposals #67 + guardrails #81 stay the dedicated owner-detail surface — not duplicated onto the grid.
- **R3 → smart pricing HONEST.** Real occupancy/pacing signals only; no fabricated "smart" price suggestion (#39). Channel-push honest (#85). Pacing real-signal-or-honest.
- **F1 + F2 → BUILD both.** A `payment.refunded` listener posts an owner-scoped `owner_ledger` `adjustment` line (owner-attributed reservations only) → the refund reduces the owner's net on the next statement close (anti-drift with `OwnerStatementBreakdown`). The reason (+note) is stored on the refund Payment's `metadata` via an additive optional param on `refundPayment`.
- **F3 → HONEST gateway, no fake card.** Show the real gateway (Stripe/Cash/Bank); "manual bank transfer" = a recorded manual refund. Never a fabricated "····4242" (#28) — no brand/last4 is stored.
- Read-only guard added to `RateActionController` (gap); refunds stay un-blocked by the workspace pause (returning money).

---

## RESOLUTION

**GATE 1 — Rates (`01b1302`).** Rebuilt `/app/rates` on the certified rate model (the SAME `RateAndRestrictionService` the owner sees → operator rates flow to the owner, reconciling). NEW: **bulk edit** (`RateBulkService` — increase%/decrease%/set-exact × units × nights [whole/weekends/midweek], skip booked & closed, optional update-derived); the REAL **derived-plan engine** (`RateDerivationService` — derived `rate_calendar` = base × adjustment; Non-refundable −10% + Weekly −15% recompute on a base change; reconciles by construction, mutation-proven); `setRatePerNight` primitive on the certified writer; a rate-plans list; **honest channels** (#85 — Direct live, OTA sync needs setup, never a fabricated "Synced"); **honest pacing** (real on-the-books, no fabricated vs-LY trend); smart-pricing omitted (no engine); the **read-only guard** added (was a gap). Grid writes stay DIRECT (R2); proposals #67/guardrails #81 remain the separate owner-detail surface. Demo derived plans seeded. +4 `OperatorRatesGate1Test`; existing rates/owner-rates/proposal/availability suites green.

**GATE 2 — Refund.** Rebuilt the refund page on `FolioService::refundPayment` (the #27 authority; test-mode already safe). NEW: **the owner statement adjusts** — `AdjustOwnerLedgerOnRefund` listens to `payment.refunded` and posts an owner-scoped `adjustment` via `OwnerAttributionService::attributeRefund` (the owner's net-ratio share of the refund; reconciles from what they actually earned; idempotent; no-op for operator-only reservations; mutation-proven €50 refund → €40 owner debit at 80% net ratio, owner net drops exactly). **Reason** (+note+destination) recorded on the refund `metadata` via an additive `?string $reason` param (the #27 bound intact). The **#83 refund email is brand-aware** (`BrandsGuestMail` #98). **Honest gateway** — the real gateway (Stripe/Cash/Bank), never a fabricated "····4242" (#28); "manual bank transfer" recorded. Refunds stay un-blocked by the workspace pause. The reason chips + refund-to + "owner statement adjusts" explainer render; the stale "Soon" stubs (reason/email) removed; only per-charge stays deferred (needs a folio_items↔payments link). +7 `OperatorRefundGate2Test`; existing refund-screen/folio/attribution/statement/guest-email suites green.
