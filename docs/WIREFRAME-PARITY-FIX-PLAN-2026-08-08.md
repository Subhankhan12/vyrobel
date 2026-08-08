# Vyrobel — Wireframe-Parity Fix Plan

**Date:** 2026-08-08 · **Source:** `docs/WIREFRAME-PARITY-SWEEP-2026-08-08.md` (READ-ONLY sweep of all 124 wireframes) · **Status:** plan only — **no code has been changed**

⚠️ **Before touching anything, read §5 REFUSALS TO PRESERVE and §6 EXTRA WORK TO KEEP in the sweep report.** Several findings look like "missing features" and are deliberate refusals; closing them by inventing data would be a regression, not a fix.

**Working rules for every wave** (the project's existing discipline): one surface per ⛔ gate · fix by **mirroring the correct pattern that already exists adjacent to the defect**, never by inventing a mechanism · **dual-method verify** (real browser AND code/DB re-read) · ship **a regression test aimed at the exact blind spot that hid it** · preserve every invariant, refusal, honest deferral and the 40 ratified divergences · commit per gate.

---

## WAVE 1 — the five HIGH findings · ✅ COMPLETE (2026-08-08)

> **All five are FIXED, verified two ways and pushed** — `ecf3685` (HIGH-1) · `165dea2` (HIGH-2) · `6312df2` (HIGH-3) · `123bf89` (HIGH-4) · `bca3679` (HIGH-5). Each carries a regression test **proven to fail without its fix**. Full evidence + two corrections to the sweep's own figures: `WIREFRAME-PARITY-SWEEP-2026-08-08.md` §2b.
>
> **⚠️ NEXT = WAVE 2** (the "false sentences" honesty sweep) — the largest remaining item and the one that decides whether the product's copy tells the truth.

### What changed vs. this plan's fix directions

- **HIGH-2 was NOT wired to `reservation.confirmed`** as suggested below. That would have banked €0 and latched `liveVersion` permanently, because attribution reads folio room revenue that `createBooking()` does not post. It fires **after the folio is posted**, at the three real entry points, and refuses to latch a zero. A `owners:backfill-attribution` command covers existing rows (0 candidates on a clean seed).
- **HIGH-4 unified only the two figures ON ONE SCREEN.** `/app/portfolio` was deliberately left on its own basis: `PerformanceMetrics` pro-rates `price_minor` by in-range nights, a folio has no per-night split, and the ADR/RevPAR identities depend on that pro-rating.

---

## WAVE 1 — the five HIGH findings (as planned)

Ordered by **severity-if-live**, not by effort.

### Group A — live today, cheap, user-visible (do first)

#### 1. HIGH-1 · Cancellation-policy JSON is replaced wholesale on every save
- **Where:** `app/Http/Controllers/Operator/SettingsActionController.php:96-97`; `app/Services/SettingsService.php:66-72`
- **Why first:** it is **data-destroying on every save of the Policies card**, and its blast radius is refund eligibility — `isRefundable()` flips to FALSE for every reservation at that property, and the guest-facing free-cancellation promise disappears from the confirmation email, the reservation page and the storefront.
- **Fix direction:** **merge** the tier into the existing `cancellation` array; never hand `fill()` a freshly-built array for a JSON column. Then decide what `tier` is *for* — today it is written and read by nothing but its own form, so either wire it to `CancellationPolicy` or stop presenting it as controlling "how much notice guests need".
- **Adjacent pattern to mirror:** any existing partial-JSON update in the settings/policy layer; the general shape is read-modify-write, not replace. (`OwnerPayoutMethodService::setDefault()` is the house style for a bounded, audited, transactional write.)
- **Regression test:** save the Policies card **twice** and assert `free_until_days` survives; assert `CancellationPolicy::isRefundable()` is unchanged across the save; assert the storefront still advertises the window. *(The blind spot: no existing test covers the policy save round-trip.)*
- **Invariants:** #17 (refund consequences follow the stored policy) · archive-not-destroy.
- ⚠️ **Class note:** this is the **same JSON-column hazard** the project has hit before — a column written as a whole array by one writer and read key-wise by another. Worth a sweep for other `fill()`-on-JSON writers in the same pass.

#### 2. HIGH-3 · Operator Owners short-circuits to "Paid" / €0.00 payable
- **Where:** `app/Livewire/Operator/OperatorOwners.php:134` (payouts loaded grouped by owner at `:51`)
- **Why here:** live today, and it tells an operator that **nothing is owed** while `/app/owners/{owner}` shows €885.60 pending on the same rows. It also makes the hold and needs-review branches unreachable.
- **Fix direction:** resolve pay-status **per statement** using `payouts.owner_statement_id` (already on the model at `:34` and indexed in `2026_07_03_000110`), not "any paid payout for this owner".
- **Adjacent pattern to mirror:** Owner Detail already resolves the payout for a specific statement correctly — make the list read the same way.
- **Regression test:** owner with one paid historical payout **and** a finalized-unpaid current statement → list shows Ready/On-hold (not Paid), the net-payable KPI is non-zero, and the payout-run count matches Owner Detail.
- **Invariants:** #17 · the on-hold branch must stay driven by a real blocker (tax hold / missing payout method).

#### 3. HIGH-4 · Two "room revenue" figures for one concept
- **Where:** `app/Support/Reports/OperatorReportData.php` — `topUnits()` (`reservation_units.price_minor`) vs `revenue()` (`folio_items type='room'`)
- **Why here:** a **63% overstatement** (€11,560 vs €7,080) on one screen, disagreeing across `/app/reports` and `/app/portfolio`, and inherited by the PDF export.
- **Fix direction:** **one shared revenue source** for the concept — the project's own anti-drift rule ("two `match`es for one concept WILL drift"; here it is two queries). Pick the folio basis as authoritative (it is what `#17` reconciles to) and make the unit table read the same source, or state the basis explicitly per panel if both are genuinely wanted.
- **Adjacent pattern to mirror:** the `FolioTotals` / `OwnerStatementBreakdown` family — one definition, consumed by page **and** PDF.
- **Regression test:** assert Σ(top-units revenue) == the gross-revenue KPI for the same period and property; assert `/app/portfolio` agrees with `/app/reports`. Add the reconciliation `PerformanceMetrics` already asserts in its docblock (`:31-33`) as a real test.
- **Invariants:** #17 · anti-drift.
- **Note:** the demo divergence is *caused* by `DemoSeeder::seedAnalyticsHistory` booking through `AssignmentService::createBooking` without posting a folio room charge. Fixing the seed alone would hide the defect — **fix the dual basis, and consider making the seeder use a real booking entry point.**

### Group B — the largest, and the owner-side launch blocker

#### 4. HIGH-2 · Owner attribution has no production trigger
- **Where:** `app/Services/OwnerAttributionService.php:63`; wiring absent from `app/Providers/AppServiceProvider.php:389-401`
- **Why it ranks here:** it is **invisible in demo** (the seeder calls attribution directly) and **total in production** — owner earnings, statements, payouts and the annual tax summary would stay at **zero** while occupancy and ADR climbed. Measured today: **€1,680 of €7,660 booked revenue unattributed.**
- ⚠️ **Already documented at `docs/WAVE2-INVENTORY.md:1068` and still open.** This is a re-surfacing with measurement, not a discovery.
- **Fix direction:** wire `attributeReservation()` to the real reservation lifecycle event (confirmed and/or checked-out — the product decision is *when* revenue is earned), idempotently, plus a **backfill** for existing unattributed reservations.
- **Adjacent pattern to mirror:** `AdjustOwnerLedgerOnRefund` — already listening on `DomainEventRecorded` and already the owner-ledger's only wired listener. Extend that seam rather than inventing a new one.
- **Regression test:** create a booking through a **real entry point** (not the seeder) → assert an `owner_ledger` row exists, the statement picks it up, and re-running attribution is idempotent (no double credit). Assert Owner Reports' analytics and money halves describe the same population.
- **Invariants:** #17 · append-only ledger · `PeriodLockGuard` · attribution must remain idempotent (`reattributeReservation` exists for corrections).
- **Cross-link:** this is the **owner-money launch blocker** — it gates any real owner going live, independently of the Stripe flip.

### Group C — compliance before any marketing send

#### 5. HIGH-5 · No unsubscribe, with a live marketing send path
- **Where:** `app/Http/Controllers/Operator/GuestBulkActionController.php:64-78`; `app/Support/Guest/GuestMarketing.php`; `resources/views/emails/layout.blade.php`
- **Why last in the wave but before launch:** nothing is broken today, but the moment marketing email is sent at any volume this is a **legal exposure** (GDPR/PECR/CAN-SPAM expect an accessible one-click opt-out).
- **Fix direction:** a **signed, no-login unsubscribe route** that writes the existing `guests.marketing_consent` flag, a footer link on marketing-class mail, and a `List-Unsubscribe` header. Keep transactional mail **outside** the gate — that separation is already correct.
- **Adjacent pattern to mirror:** the guest claim/verify flow's **HMAC-signed, expiring, single-use link** discipline; and `GuestMarketing::canReceive` as the single gate (extend it, do not add a second gate).
- **Regression test:** unsubscribe link written into a marketing send → following it flips consent with **no login** → a subsequent bulk marketing send skips that guest → a **transactional** send still reaches them.
- **Invariants:** #28 (the token must not leak identity or enumerate guests) · transactional mail must remain ungated.
- **Related, same pass:** per-category preferences do not exist — pre-arrival, payment-reminder and review-request emails send with **no** preference check. Review requests are the closest to solicitation and are the first candidate for a gate.

---

## WAVE 2 — the "false sentences" honesty sweep · ✅ COMPLETE (2026-08-09)

> **All 23 false-copy items AND all 3 structural MEDIUMs are fixed, verified and pushed**, in six gates:
> `00a9fe5` S1 wallet concurrency · `47cf1ff` S2 write-only notes · `cb4dec0` S3 yearly-switch ·
> `8deb066` wrong figures (#17, #21) · `8437a93` mislabelled figures (#11, #12, #14) ·
> `599a452` anti-drift copy (#3, #5, #8) · `f930dda` unenforced claims (#2, #6, #10, #13, #20, #22) ·
> `babbb74` prototype fiction (#4, #7, #9, #15, #19) · `b44bb64` unwired controls (#1, #16, #18, #23).
> Every gate carries a regression test proven to FAIL without its fix.
>
> **Two items are deliberately COPY-ONLY, by founder decision (2026-08-09):**
> - **#6** — the copy now promises only the PROPOSAL path, which `RateProposalService` genuinely enforces
>   (mode, ±% tolerance AND floor/ceiling). Binding the operator's DIRECT rate write to an owner's
>   floor/ceiling shifts control between the two parties → **deferred as a product decision**.
> - **#10** — the real plan limit is now displayed (Essential 5, Pro unlimited). Whether it should be
>   **enforced** → **deferred with the open billing decision**.
>
> ### Corrections made while fixing
> - **#6 was more nuanced than recorded.** `RateProposalService` DOES enforce the guardrail — on proposals.
>   Only `RateActionController`'s direct write ignores it. The copy was true of one path, false of the other.
> - **#20 had a SECOND copy** of the claim in the hero sub-label that the first pass missed; the browser
>   caught it. The test now asserts case-insensitively.
> - **Three pre-existing tests asserted the fabrications** they were meant to guard against —
>   `OwnerDocumentsViewTest` ("All 12 months"), `OwnerHelpTest` ("All systems operational") and
>   `OwnerMyUnitsViewTest` (relying on the wrong-month default). All three were strengthened, not weakened.

---

## WAVE 2 — the "false sentences" honesty sweep (as planned)

Every item below is **copy asserting behaviour the code does not implement**. For each, the decision axis is: **make the COPY honest**, or **make the CODE match the copy**. The recommendation column says which is right *for that item*; where it is a product call it says so.

| # | Surface | Decision | Recommendation |
|---|---|---|---|
| 1 | verify-email toggle says "it won't send" | **CODE** | Honour the flag like the other 6 templates (`EmailTemplateResolver::enabled`) — **or** remove the toggle for this template, since an account-security email arguably should not be pausable. Either is honest; the current state is not. |
| 2 | "Changing the default holds payouts 48h" | **COPY** | Add the same "— with your live banking connection" qualifier the adjacent bullet already carries. Do **not** implement or simulate a hold. |
| 3 | "Every 2 weeks" vs "Twice monthly" | **COPY** | Both Blades should call the canonical `OwnerPayoutPreference::frequencyLabel()` (`:82`). Anti-drift: one label source. |
| 4 | "All systems operational" | **COPY** (or real check) | Reword so it does not assert live status, **or** build a real health check. Do not fabricate per-service rows. It currently reads green during any outage. |
| 5 | "All 12 months" on the annual tax card | **COPY** | Use the real `statement_count` / `period_start` / `period_end` that `OwnerTaxService::annualSummary` already returns and the PDF already prints. |
| 6 | "Every change needs your sign-off" (Owner Rates) | **CODE** | Make `RateActionController` consult `RateGuardrail` — otherwise the owner's floor/ceiling is unenforceable and the promise is false. (Product call: is the operator meant to be bound by it?) |
| 7 | Payment-reminder promises **Apple Pay** | **COPY** | Drop the claim — the destination explicitly refuses Apple Pay. Revisit only if Stripe Checkout wallets are enabled at go-live, at which point it becomes a checklist item. |
| 8 | Refund ETA "3–5" vs "5–10 business days" | **COPY** | One shared, honest statement (or omit the estimate). Two hardcoded literals for the same money is the drift rule again. |
| 9 | Search help says `VYR-…` | **COPY** | Say `R-…`. Prototype fiction that reached help text; an operator following it gets zero results. |
| 10 | "Team members 7 · unlimited" | **COPY + CODE** | Print the real plan limit; separately decide whether the Essential limit of 5 should be **enforced** (today nothing reads it). |
| 11 | My Units "This month" showing December | **CODE** | Default to the **current** month (Unit Detail already labels its month explicitly — do both). Today Dashboard says A1 19% and My Units says 0%. |
| 12 | My Units "Nightly rate" = achieved ADR | **COPY + CODE** | Show the configured rate (already loaded as `base_display`) under that label, and label the ADR as ADR. |
| 13 | Owner Onboarding card heading | **COPY** | Make the `<h2>` branch on `$hasPayout` like the rest of the card. |
| 14 | Guest Add-ons price under a per-unit label | **CODE** | Render the `unit_price` the controller already computes, or relabel the total. A guest currently reads €132.84 as a nightly per-person rate. |
| 15 | Waitlist "we'll email you if a spot opens" | **COPY** (until the trigger exists) | Say it honestly ("we'll be in touch") — the SMS and auto-hold deferrals in the same sentence are already handled this way. Building the trigger is a separate feature. |
| 16 | Maintenance "No SLA target set" on a fresh org | **CODE** | Seed or lazily create the settings row (`MaintenanceSettingsService::forOrg()` already does `firstOrCreate`) so the SLA signal is live from install. |
| 17 | Dashboard "Revenue today" org-wide | **CODE** | Scope to `$propertyIds` like every sibling KPI. Latent today; wrong the moment a second property takes a same-day payment. |
| 18 | Guests CRM write controls shown to view-only roles | **CODE** | `@can('guests.manage')` the Add/Message/Add-tag controls — the Erase block already shows the pattern. Server gate holds, so this is UX, not security ("never invite a 403"). |
| 19 | Inbox footnote on owner threads | **COPY** | Branch the sentence on thread type. |
| 20 | Owner Reports "Reconciles to your statements" | **COPY** | It reconciles to statements **plus** unstamped ledger; say so, or show the statement figure. Tax Center shows a different number for the same year. |
| 21 | Owner Reports "Latest payout" = oldest | **CODE** | Order by `paid_at` / statement period, not a tied `created_at`. |
| 22 | Owner Reports VAT banner vs footnote | **COPY** | Gate the "configured for PT" footnote on `$vat['is_example']`, which already exists. |
| 23 | Guest Account language preference | **CODE** or **COPY** | Either consume `guests.language` (locale/mail) or stop advertising it as a setting. |

### The three structural MEDIUMs (same wave, higher care)

| # | Item | Fix direction | Regression test |
|---|---|---|---|
| S1 | **Guest wallet has no concurrency protection** — checks outside the transaction, zero `lockForUpdate`, unused UNIQUE `idempotency_key`, and the only guest money forms without `data-guard-submit` | Mirror **`FolioService::refundPayment()` as fixed in `e4775ec`** (transaction + row lock + re-read inside the lock + idempotency key), which itself mirrored `OwnerPayoutService::pay()`. Add the submit guard the sibling pay form already uses | Two-process race on `applyToBooking` (and `redeemCode`): one succeeds, one is rejected, ledger sum never negative, folio reconciles (#17) |
| S2 | **Reservation notes are write-only** — `notes_internal` / `notes_guest` are written and rendered by no view; a guest's online check-in note is never seen | Surface them on Reservation Detail (the wireframe's "Notes & requests" card). Reuse the shared polymorphic notes reader already working on Guest Profile | Guest submits an arrival note at online check-in → it is visible to an operator on the reservation |
| S3 | **"Switch to yearly" hardcodes `plan=pro`** | Use the org's **current** tier and change only the interval; quote the real saving for that tier | Essential org clicks "Switch to yearly" → lands on **Essential** yearly with the correct price |

⚠️ **Cross-link — S3 and the OPEN billing decision.** S3 is a **separable defect** and can be fixed regardless of which pricing model is chosen. The **pricing contradiction itself** (flat tier reaching Stripe vs the per-unit figure displayed) remains the founder's **open business decision** and a go-live blocker — see `docs/DECISIONS.md` (2026-08-08). **Do not resolve it by picking a model in code.** The sweep reported both figures factually and made no recommendation.

---

## WAVE 3 — MINOR / LOW gaps (quick wins, grouped)

**Operator** — pagination on Guests / Reservations / Notifications (all unbounded `->get()`) · property-scope + a period selector on Maintenance Costs · per-lane "Assign all" and the assign-all plan preview on Housekeeping · "Suggested" technician should mean least-loaded, not alphabetically first · priority chip after "Clean next" · consistent Pending/Dirty vocabulary · assignee at creation + `notify` on the board's assign modal · undo on maintenance resolve · Open-shifts + published/provisional state on the Schedule PDF (both stale refusals — the models now exist) · revenue-on-hold on the Work Order hero (stale refusal — `RevenueOnHold` exists and the board already uses it) · rate-plan rows indistinguishable ("Flexible" ×2) · `intdiv` truncation on the rates average (one cent, display-only) · nav unread badges · Portfolio cross-currency roll-up (latent) · Insights By-property table clipped; weekday tiles missing `%`; "pts" vs "pp".

**Owner** — hero/goal chart scaled to the goal so real bars are unreadable · Upcoming-stays shows net (mostly "Not yet attributed") where gross would inform · both unit cards post the **same** `room_type_id` with no disclosure (and a 180-day window here vs 365-day on Unit Detail) · "Net to you" on Bookings excludes expense debits under an unqualified label · Booking Detail timeline shows check-in upcoming above a completed check-out · statement "Next payout" eyebrow on a paid payout · statement occupancy scoped portfolio-wide vs the PDF's statement-scoped · payout year filter computed but never rendered · "Held since" + "View held funds" on the hold banner · Balance next-run date + "hold clearing" state · in-portal pending login-approval queue · two-factor card capped at 560px against full-width siblings · Agreement commission animated through `17.99%` (exclude legal figures from the count-up) and rendered three ways.

**Guest** — Refunded total absent from Payments (`FolioTotals` already returns `refunded_minor`) · per-payment breakdown + letterhead on the receipt; supplier identity differs across the three PDFs because two bypass the shared `OperatorLetterhead` · running balance + documents-on-file on the statement PDF · "Book now" on a saved wishlist row (a wishlist that cannot be converted) · waitlist "Extend" only offered after expiry · reservation page's hardcoded `15:00`/`11:00` fallbacks (use the shared `StayTime`, which returns null so the caller can omit) · charge lines computed then discarded on the reservation page · add-on payload key mismatch (`add_ons` vs `add_on_ids`) · check-in "What this unlocks" title/body collide (`.ci-unlock` flex) · "Things to know" on the public room page · phone not collected at checkout · remember-me checkbox never rendered though the backend honours it.

**Public / auth / system** — workspace picker absent (`active_organization_id` is read and never written) · password reset does not terminate sibling sessions · 60-minute reset and 7-day invite expiries are true but unstated · 404 "popular pages" + a 500 support reference.

---

## Do NOT touch

⚠️ **`docs/WIREFRAME-PARITY-SWEEP-2026-08-08.md` §5 (REFUSALS TO PRESERVE) and §6 (EXTRA WORK TO KEEP).** The refusals — no fake bank "Verified"/micro-deposits, "Withdraw early" disabled, no SEPA export, no raw PAN, KYC pending-never-verified, reversal PDFs 404 without a real reversal, no fabricated fee/tax/withholding/OTA-brand split, guest contact absent from owner and front-desk surfaces, door code guest-only and window-gated, New/Delete-role refused (#26/#30) — are **correct engineering and must not be "closed"**. The extra work must not be removed to match a thinner wireframe.

**If closing any gap would require inventing data the system does not have, stop and reclassify it as a refusal.**
