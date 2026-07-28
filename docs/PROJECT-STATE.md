# Vyrobel — Project State (resume here)

**On a fresh session read THIS first, then `docs/DECISIONS.md` for the dated "why".** This is a
pointer map to the real code, not a copy of it — follow the links on demand. If anything here
conflicts with the repo, **trust the repo** and fix this file.

## Status (verified against the repo)

> ⭐⭐ **CURRENT STATE — 2026-07-28 (v10) · owner REPORTS exact-match DEEPENED to the full wireframe (#63). This block
> SUPERSEDES every block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md` → this block →
> `docs/DECISIONS.md`.**
>
> **The owner Reports page (`/owner/reports`) — built at #61 with the honest data right — was deepened to the FULL decoded
> `Owner Reports.html` (design + behaviour) on REAL owner-scoped analytics (`docs/OWNER-REPORTS-AUDIT.md`, DECISIONS 2026-07-28
> #63, DIVERGENCE Owner-Reports row). Read-only: year / date-range / export are GETs → no write to gate (#20 has nothing to
> guard here).**
> - **GATE 1 (`f05a9b8`) — analytics exact-match + two real data upgrades:** the reporting-year dropdown driven by the owner's
>   REAL statement years (never the wireframe's fabricated 2024/2023) + a custom From/To date-range (real GET clip) + a richer
>   CAP_EXPORTS-gated Export menu (real PDF/CSV/spreadsheet/annual-tax/all-statements; PDF-pack + Email honest-"Soon"); KPIs
>   (YoY Δ **honest-omitted** — no prior-year data, #39); net-by-month chart (peak bubble; prior-year series omitted); per-unit
>   grid (ADR/RevPAR header + share bars + All-units total); **Booking sources = NET by channel** (NEW REAL — ledger×
>   `reservations.source` via the SHARED `OwnerBookingsReader` labels; **Σ channels == period net EXACTLY**, #17 — replaced
>   nights-by-channel, a stale-refusal re-verification win); gross-to-net keeps the **REAL 18% agreement rate** (`OwnerCommissionRate`,
>   never 15%), reconciles; dark hero (YoY badge omitted); payout card (masked IBAN CAP_PAYOUT-only #28); real Documents;
>   notifications = the shared shell bell (redundant right-rail card dropped).
> - **GATE 2 — Tax & VAT summary honest-deferred in the wireframe's card chrome.** The wireframe's Output/Input/**Net VAT due ·
>   payable to [authority]**/per-quarter/**Mark-filed**/numbers-bearing VAT-return CSV is a KIND-2 fabrication — Vyrobel models
>   **no VAT** (only a stored encrypted `vat_number`). REFUSED the figures; styled the honest deferred state (icon + header +
>   "Not yet available" chip + real tax-country when set + real annual-tax PDF/Tax-Center links; no figure/toggle/return, #39).
> - **Verified:** `OwnerAnalyticsTest` 7→10 (net-by-channel reconciles; real year dropdown; custom range clips; honest VAT).
>   Suite **1089 / 8541 GREEN**; PHPStan **L5 = 0**; Pint clean; `migrate:fresh --seed` clean; browser-verified (year dropdown
>   "2026 · 6 statements YTD", custom range Aug–Oct €2,750.80, 3 real exports 200, net-by-channel Direct €4,460.80 + Walk-in
>   €442.80 + Other −€250.00 = €4,653.60, honest VAT). No schema change, no new writes. **Invariants #1–63.**
>
> Owner exact-match track shipped: Overview (#54) · My Units + Add Unit (#55) · Bookings + PDF (#56) · Statement + PDF (#57) ·
> Payouts + Payout Method (#58) · Balance + Expenses (#59) · Block Calendar + Documents (#60) · Reports + Insights (#61) ·
> Settings hub (#62) · **Reports deepened to full wireframe (#63)**. Remaining built-but-not-yet-exact-matched: EARNINGS —
> Tax-center · MANAGE — Rates/Reviews/Agreement/Messages · ACCOUNT — Security/Team · Help. Everything else below is current; no
> launch-blocking build remains.
>
> ---
>
> ⭐ **CURRENT STATE — 2026-07-28 (v9 · TOTAL-RECALL CONSOLIDATION, verified against the repo: `git log`, `composer.json`,
> `php artisan test`, `route:list`). This block SUPERSEDES every block below it (all kept as historical detail).
> Fresh-session entry point: `docs/RESUME.md` → this block → `docs/DECISIONS.md`. HEAD `d2091b4` on `main` (== origin,
> tree clean).**
>
> **WHAT VYROBEL IS.** A multi-tenant, AI-native **hotel/apartment-management SaaS** — PMS + channel manager + booking
> engine + operator/owner/guest apps + a public storefront + self-signup + a platform Super-Admin panel — for operators
> running a MIX of hotels, aparthotels, serviced apartments, villas and multi-unit rentals.
>
> **STACK (locked — `docs/DECISIONS.md`).** **Laravel 13** (`^13.8`) on **PHP ^8.3** (composer floor; local runs 8.4) ·
> **Livewire 4** + Blade + **Tailwind 4** (Vite) · **MySQL 8 / InnoDB** (genuine MySQL, not MariaDB) · queues on
> **Redis + Horizon** · **ULID** PKs on tenant tables · **spatie/laravel-permission teams-mode** RBAC (team = org) ·
> **shared-DB multi-tenancy** via `organization_id` + the `BelongsToOrganization`/`OrganizationScope` global scope
> (`runAsSuperAdmin` = the ONE sanctioned bypass) · integer minor-unit money (`*_minor` + the `Money` cast) · Pest 4 /
> PHPStan **L5** / Pint. Browser verification = the **Playwright MCP** driving `php artisan serve` (real, but NOT a
> re-runnable suite).
>
> **VERIFIED THIS PASS (2026-07-28).** Suite **1086 tests / 8497 assertions GREEN** (`php artisan test`, exit 0) ·
> PHPStan **L5 = 0** · Pint **clean** · `migrate:fresh --seed` clean · **Invariants #1–62** (the numbered ledger below
> now reaches #62 — reconciled with the prose this pass). Requires a genuine MySQL 8 `vyrobel_test` DB + Redis.
>
> **BUILD STATUS — everything buildable is COMPLETE.**
> - **Phases 0–7 DONE** (the whole tenant product): 0 fix/harden · 1 operator app · 2 owner portal · 3 notifications +
>   transactional emails · 4a/b/c guest portal · 5 reviews · 6a multi-property + 6b analytics · 7a tenant provisioning /
>   self-signup · 7b subscription billing (free-trial) · 7c owner sub-tenancy + owner team · 7d staff rostering.
> - **Phase 8 (account-gated integrations): the two obtainable ones are LIT UP, test-mode / code-complete** — PRODUCTION
>   EMAIL (Postmark; `MailSafetyGuard`; `docs/EMAIL-PRODUCTION.md`) + **LIVE STRIPE across all three seams** (subscription
>   billing · guest card charge · owner payout rail) webhook-driven, signature-verified, multi-layer idempotent, no raw
>   PAN (SAQ A), `StripeSafetyGuard` refuses a live key outside `production`; `docs/STRIPE-PRODUCTION.md`. **The live
>   cutovers are the founder's** (email DNS+token; the Stripe live-key flip) — never done in the build.
> - **Design-fidelity pass (all 4 gates) + polish sweep + gap-sweep Batches A/B/C + add-charge idempotency — DONE.**
> - **THE SUPER ADMIN PANEL (`/admin`) — COMPLETE + AUDITED (invariants #49–53):** Gate 0 (isolated realm, seeded-not-
>   signup, noindex) → Build 1 (Dashboard · Customers [suspend/reactivate/assign-plan] · Plans [DB-backed CRUD +
>   additive feature-gating]) → Build 2 (Security & Activity Log · Integrations [encrypted channel credentials]) → deep
>   Playwright audit (`docs/SUPERADMIN-AUDIT.md`, 0 critical / 0 P1) → Profile & Account page → completeness review
>   (`docs/SUPERADMIN-COMPLETENESS.md`). Launch-ready.
>
> **THE OWNER-PAGE EXACT-MATCH TRACK — matching each LIVE owner page to its decoded wireframe (exact design + REAL
> owner-scoped backends, HONEST where account-gated). DONE + PUSHED, `#54`–`#62`:**
> Overview (**#54**) · My Units + Add Unit (**#55**) · Bookings page + Booking-Summary PDF (**#56**) · Statement page +
> Statement PDF (**#57**) · **Payouts + Payout Method (#58)** · Balance + Expenses (**#59**) · Block Calendar + Documents
> (**#60**) · Reports + Insights (**#61**) · **Settings hub (#62)**. Each was a PHASE-1 read-only audit
> (`docs/OWNER-*-AUDIT.md`) → a gated build (GATE 1 design + real backends · GATE 2 doc twin / second section-group) →
> per-gate commit → suite + PHPStan + Pint + browser-verify. **REMAINING owner pages (built + at-parity but NOT yet
> exact-matched — the next candidates, NOT launch-blocking):** EARNINGS — **Tax center** · MANAGE — **Rates · Reviews ·
> Agreement · Messages** · ACCOUNT — **Security · Team** (dedicated real pages) · **Help**. (`Reports + Insights` and
> `Settings` were prompts-written earlier and are now BUILT — #61/#62.)
>
> **⚠️ #58 PAYOUTS — WHAT IT ACTUALLY IS (verified in the repo; note vs any "Option A / no-IBAN" framing).** Both pages
> match their wireframes real-where-real + honest-where-account-gated. **REAL / built:** the payout **summary + history +
> KPIs + reconciling breakdown + remittance PDF** (all statement-derived) · the masked payout **IBAN** (`OwnerNextPayout`,
> CAP_PAYOUT-only #28) · the **bank-account CRUD** — `PayoutMethod` with an **ENCRYPTED `iban`** (+ `iban_last4`/country),
> the add-route **ISO mod-97-validated** before storing, self-only · **Stripe Connect onboarding** (`OwnerConnectController`,
> **test-mode**, + a signature-verified `webhooks.stripe.connect`). So the app **DOES collect (encrypted) bank details** —
> it is NOT a no-IBAN honest-summary-only page. **DEFERRED = the automated payout EXECUTION/DISBURSEMENT ENGINE** (scheduled
> payouts, micro-deposit/bank verification, SEPA tracing, the 48h hold, instant withdrawal) — a major fintech initiative
> account-gated on live Stripe/banking + PSD2/PCI/GDPR; the pages show real statement-derived money and label these
> pending-live, **never faked**. `ManualPayoutRail` is the real, tested disbursement path; the Balance page's "Withdraw
> early" is honestly disabled (no owner-initiated transfer rail). **If the founder wants a true "Option A" (collect NO
> IBAN until a certified pipeline exists), that is a CODE change — the repo currently collects encrypted IBANs.**
>
> **THE DECODE METHOD (owner wireframes are `dc-runtime` bundles).** `JSON.parse` the `<script type="__bundler/template">`
> content → the `<x-dc>` body = the real markup + inline `style` tokens + `{{ placeholders }}`. Decoder used this pass:
> `node /tmp/decode_wf.mjs "docs/design/raw/Owner X.html"`. Serve `docs/design/raw` over `php -S` to render (it self-
> hydrates; looping animations block full-page screenshots — extract the DOM/tokens instead). **REUSE the shared owner
> chrome** `<x-owner.shell active= title= subtitle= viewer=>` + the layout's warm-glass `--vy-*` tokens (`--vy-grad`,
> `--vy-grad-dark`, `--vy-accent`, `--vy-card`, `--vy-kpi-grid` [`--2`/`--4`], `--vy-track`, `--vy-status`, animations
> `vyFadeUp`/`vyShimmer`/`vyDrift`/`vyToastIn`/`vyCardIn`) — extend, never one-off. **Two live Blade traps:** a directive
> whose `@` follows a word char is NOT compiled (`word@if` ships raw source); and a shell `title="A &amp; B"` DOUBLE-
> escapes through `{{ $title }}` → pass a plain `&`.
>
> **THE HONEST DEFERRALS (do NOT build ad hoc; the register lower down is authoritative).** Stripe billing sync ·
> operator impersonation · suspension enforcement · per-feature usage limits · **real channel sync** (Channex/OTA —
> account-gated; the Settings "Connected channels" shows Direct live + OTA honest-**Pending**, never a fake "Connected") ·
> customer self-connect · re-point the Horizon gate to the `admin` guard · the My-Units action-center snooze/day-detail ·
> the operator approve→create-Unit flow (a `UnitRequest` is reviewed, never auto-created) · **⚠️ the automated PAYOUT
> ENGINE** (above — deferred fintech) · **owner expense-APPROVAL + rate-PROPOSAL workflows** (no such entity — REFUSED as
> fabrication; Expenses/Insights link the real Rates/Tax instead) · **VAT filing-status / VAT computation** (not modelled
> — Reports' Tax&VAT card is honest-deferred) · **SMS + push notifications** (no provider — the real channels are in-app +
> email; Settings labels SMS/Push "soon") · the remaining un-exact-matched owner pages · the Phase-3 tails.
>
> **WHAT'S LEFT.** No LAUNCH-BLOCKING build remains — the product is demo-ready end-to-end and the Super Admin panel is
> production-ready. A **free-trial / early-access** launch works with the panel as-is; a **paid** launch needs only the
> already-scoped **Stripe go-live** + **suspension enforcement**. Then **DEPLOYMENT** (production hosting / domain / HTTPS
> / MySQL / Redis-Horizon) + the founder's **cutovers** (email DNS+token; Stripe live-key). The owner-page exact-match
> work is ongoing **polish, NOT launch-blocking** — and the audits repeatedly find the wireframes describe MORE product
> than exists (approval/proposal/VAT/channel-sync/payout-engine), so real usage should drive **which deferred capability
> to actually build** rather than chasing every prototype affordance.
>
> **LOGIN CREDENTIALS** (all password `password`; `php artisan migrate:fresh --seed` recreates them):
> operator back office `demo-staff@vyrobel-demo.test` (Hotel Manager) or `demo-admin@vyrobel-demo.test` (org admin) → `/app` ·
> owner `demo-owner@vyrobel-demo.test` (External Owner, primary) → `/owner` · guest `demo.traveller@vyrobel-demo.test` →
> `/guest/login` · **platform admin `admin@vyrobel-demo.test` → `/admin` (forced 2FA enrol on first login)**. Public
> storefront `/book/vyrobel-demo`; self-signup `/signup`. (Also seeded: owner sub-users accountant/view-only; operator
> accountant/housekeeper — `database/seeders/DemoSeeder.php` + `PlatformAdminSeeder.php`.)
>
> ---
>
> ⭐⭐⭐⭐⭐⭐⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v8) · owner SETTINGS HUB exact-match (#62). This block SUPERSEDES every
> block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md` → this block →
> `docs/DECISIONS.md`.**
>
> **The owner-page exact-match track reached its largest surface: the owner Settings HUB (`/owner/settings`) — the
> 1,136-line wireframe's 13 sections (`docs/OWNER-SETTINGS-AUDIT.md`, DECISIONS 2026-07-28 #62, DIVERGENCE row 144).**
> A HUB: MOST sections REUSE features already built (anti-drift, not rebuilt); honest where account-gated. Built in two
> gated section-groups.
> - **GATE 1 — identity & money (`7981030`):** Hero (derived completeness + real stats) · Profile (`OwnerAccountController`)
>   · Business & tax (`owner.settings.update` + the ONE `owner.tax.save`) · **Billing** (the REAL management-fee rate via a
>   new shared `OwnerCommissionRate` — 18%, NEVER the wireframe's 15%; the owner has no card/subscription → honest fee view,
>   no fabricated card) · Payout account (real masked IBAN, `OwnerNextPayout`, CAP_PAYOUT #28) · Profile completeness
>   (derived). `OwnerReports` refactored onto the shared `OwnerCommissionRate` (one definition, two surfaces).
> - **GATE 2 — preferences & access:** Language & region (locale real; currency/timezone the property's, read-only) ·
>   Notifications (reuse `owner.notification-preferences` — **in-app + email real; SMS + push honest-pending**, no provider)
>   · Connected channels (**Direct live; OTA honest-Pending**, never a fake "Connected") · Team access (summary + the reused
>   `owner.team`) · Appearance & motion (**real reduce-motion**; theme soon) · Data export (real CAP_EXPORTS exports #28).
> - **Verified:** `OwnerSettingsTest` 5→7 + `OwnerAccountProfileTest` updated; PHPStan **L5 = 0**; Pint clean; the whole
>   13-section hub browser-verified (18% fee, ···· 1000 IBAN, OTA Pending, SMS/Push soon, real team/exports, no raw Blade).
>   🔒 masked IBAN never full + CAP_PAYOUT #28, real fee rate #17, controller-route writes #20, exports #42, nothing
>   fabricated (no fake channel-sync / SMS / push / card) #39/#37. **Invariants #1–62.**
>
> **Owner exact-match track shipped: Overview (#54) · My Units + Add Unit (#55) · Bookings + PDF (#56) · Statement + PDF
> (#57) · Payouts + Payout Method (#58) · Balance + Expenses (#59) · Block Calendar + Documents (#60) · Reports + Insights
> (#61) · Settings hub (#62).** Remaining built-but-not-yet-exact-matched: EARNINGS — Tax-center · MANAGE —
> Rates/Reviews/Agreement/Messages · ACCOUNT — Security/Team (their dedicated pages) · Help. Everything else below is
> current; no launch-blocking build remains.
>
> ---
>
> ⭐⭐⭐⭐⭐⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v7) · owner REPORTS + INSIGHTS exact-match (#61). This block SUPERSEDES
> every block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md` → this block →
> `docs/DECISIONS.md`.**
>
> **The owner-page exact-match track added its 11th + 12th surfaces: the owner Reports (`/owner/reports`) + Insights
> (`/owner/insights`) pages (`docs/OWNER-REPORTS-INSIGHTS-AUDIT.md`, DECISIONS 2026-07-28 #61, DIVERGENCE Reports/Insights
> rows).** Both wireframes decoded; both rebuilt EXACTLY on the REAL Batch-B analytics (`PerformanceMetrics`,
> `BookingCadence`, `OwnerLedgerAttribution` — reused, anti-drift). Two judgment calls founder-approved (VAT honest-defer;
> proposals honest link).
> - **Reports (GATE 1, `949ea93`):** 3 KPIs + Net-earnings-by-month + Performance-by-unit (ADR/RevPAR/nights/occ/net/share)
>   + Booking-sources-by-channel (real `reservations.source`) + the **Gross-to-net** (reconciles to the cent #17 at the
>   **REAL agreement commission rate 18%** — read from `ManagementAgreement`, NEVER the wireframe's hardcoded 15%) + payout
>   summary + real Documents + CAP_EXPORTS-gated exports. **HONEST-DEFER:** the Tax & VAT summary is NOT modelled → an
>   honest deferred card (no fabricated Output/Input/Net VAT figure or "Mark filed", #39); "Email to me" = honest Soon.
>   Notifications = the real self-scoped feed.
> - **Insights (GATE 2):** 2-column rebuild — Occupancy-by-month (this-year + on-the-books), Booking-pace, Rate-performance
>   per unit, lead-time + day-of-week (+ a real derived caption), and **"Worth a look" REAL DERIVED observations**
>   (hottest weekday / median booking window / forward revenue — decisions-stay-yours). **HONEST-OMIT** the "vs last year"
>   series/deltas (no prior-year data, #39). **HONEST** the proposals callout → a real "Open your Rates" link (no fake
>   proposal entity/workflow).
> - **Verified:** suite green (`OwnerAnalyticsTest` 4→7 — gross-to-net at a distinctive real 22% rate, honest VAT, export
>   gating, real insights + honest rates link); PHPStan **L5 = 0**; Pint clean; both browser-verified (gross €5,980 −
>   commission €1,076.40 [18%] − costs €250 = net €4,653.60; real derived insights). 🔒 owner-scoped #28, reconciling #17
>   at the real rate, exports #42, nothing fabricated (no fake VAT/proposal/advice) #39. **Invariants #1–61.**
>
> **Owner exact-match track shipped: Overview (#54) · My Units + Add Unit (#55) · Bookings + PDF (#56) · Statement + PDF
> (#57) · Payouts + Payout Method (#58) · Balance + Expenses (#59) · Block Calendar + Documents (#60) · Reports + Insights
> (#61).** Remaining built-but-not-yet-exact-matched: EARNINGS — Tax-center · MANAGE — Rates/Reviews/Agreement/Messages ·
> ACCOUNT — Settings/Security/Team. Everything else below is current; no launch-blocking build remains.
>
> ---
>
> ⭐⭐⭐⭐⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v6) · owner BLOCK CALENDAR + DOCUMENTS exact-match (#60). This block
> SUPERSEDES every block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md` → this
> block → `docs/DECISIONS.md`.**
>
> **The owner-page exact-match track added its 9th + 10th surfaces: the owner Block Calendar (`/owner/calendar`) +
> Documents (`/owner/documents`) pages (`docs/OWNER-CALENDAR-DOCS-AUDIT.md`, DECISIONS 2026-07-28 #60, DIVERGENCE rows
> 141/148).** Both wireframes decoded; both rebuilt EXACTLY on their REAL owner-scoped backends (both were already real).
> - **Block Calendar (GATE 1, `ab4333b`):** the interactive grid — **click a day or press-and-drag a range** (Alpine) →
>   the Selected-range composer (reason **buttons** Personal stay / Maintenance / Seasonal hold + optional note + a
>   **Repeat** section) → Confirm → a REAL block POST (`OwnerBlockService` → certified `AssignmentService`, non-overlap
>   enforced, `$capActions` #20/#42, audited). **Click a blocked day → a REAL release.** 4-state legend + real stat line;
>   booked cells show the guest **NAME** (name-only #28). **Series = REAL:** REPEAT expands into N REAL individual blocks
>   sharing a new nullable `series_id` (never a fake series #39); an over-taken occurrence is skipped + reported; the
>   Scheduled list collapses a series to one row + Remove; a toast + **Undo**. New: `calendar.release-series`,
>   `OwnerBlockService::blockSeries/releaseSeries`, `InventoryBlock.series_id` (migration + fillable).
> - **Documents (GATE 2):** a year-filter pill row + **one frosted card per real month** (period · No. · Net · STMT/BKG/RA
>   rows + PDF) + a **DARK annual tax-summary card per year** with the **REAL net** (Σ `net_source_minor` = the annual
>   PDF's basis, reconciling #17). The backend was already real (on-demand PDFs, `CAP_EXPORTS`-gated, owner-scoped #28);
>   the fabricated multi-year history + "€98,710" stay refused; the not-in-wireframe ZIP stub is dropped.
> - **Verified:** suite **1081/8394 green** (`OwnerBlockCalendarTest` 10 + `OwnerDocumentsViewTest` 6); PHPStan **L5 = 0**;
>   Pint clean; `migrate` clean; both pages browser-proven (a real drag-select block → release; real statement +
>   annual-tax PDF downloads, %PDF-). 🔒 owner-scoped #28 (guest name-only), controller-routed #20, exports #42,
>   reconciling #17, nothing fabricated / no fake series #39. **Invariants #1–60.**
>
> **Owner exact-match track shipped: Overview (#54) · My Units + Add Unit (#55) · Bookings + PDF (#56) · Statement + PDF
> (#57) · Payouts + Payout Method (#58) · Balance + Expenses (#59) · Block Calendar + Documents (#60).** Remaining
> built-but-not-yet-exact-matched: EARNINGS — Tax-center · MANAGE — Reports/Insights/Rates/Reviews/Agreement/Messages ·
> ACCOUNT — Settings/Security/Team. Everything else below is current; no launch-blocking build remains.
>
> ---
>
> ⭐⭐⭐⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v5) · owner BALANCE + EXPENSES exact-match (#59; ⚠️ Balance is real money).
> This block SUPERSEDES every block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md`
> → this block → `docs/DECISIONS.md`.**
>
> **The owner-page exact-match track added its 7th + 8th surfaces: the owner Balance (`/owner/balance`) + Expenses
> (`/owner/expenses`) pages (`docs/OWNER-BALANCE-EXPENSES-AUDIT.md`, DECISIONS 2026-07-28 #59, DIVERGENCE rows 129/147).**
> A read-only PHASE-1 audit classified EVERY element REAL-NOW / ACCOUNT-GATED / NOT-BUILT (load-bearing — Balance's
> "Withdraw early" is instant real-money movement), founder-approved, then two gated builds.
> - **Balance (GATE 1, `a096a89`) — REAL clearing model:** the KPI band is **Available** (Σ unstamped `owner_ledger` net) /
>   **On hold** (Σ pending payouts held by the REAL tax-hold) / **In transit** (Σ released pending), with the identity
>   **Available + On hold + In transit + Paid == Net position** holding per currency **to the cent** (#17). The
>   Balance-activity ledger keeps its real rows + running balance + a per-row **state chip**; the **"Why is €X held?"** card
>   + **Add tax details** render ONLY on a real tax-hold; the masked **IBAN** is CAP_PAYOUT-only (#28); the auto-payout
>   cadence + **"How money clears"**; a real balance-activity **Export CSV** (CAP_EXPORTS). **⚠️ MONEY-SAFETY:** "Withdraw
>   early" is honestly **disabled** — there is NO owner-initiated transfer rail, so it NEVER fakes a withdrawal / transfer /
>   "in transit" (#39); "In transit" is a real figure (€0 while held). Two prior refusals (Withdraw button, Why-held /
>   How-clears cards) were **stale** and are now built REAL (reconciling now the tax-hold is real, #58).
> - **Expenses (GATE 2) — real, honest:** KPIs **Spent this year** / **Pending review** (count) / **Charged to payouts**; a
>   REAL **Unit** column + unit filter (**vendor REFUSED** — unmodelled, #39); a real **Export CSV** (CAP_EXPORTS); the
>   "Need something fixed?" deferral is now a REAL **relay** into the owner↔operator channel (`owner.messages.start`, writes
>   no expense); a read-only **"Pending review"** callout fills the wireframe's attention slot honestly. **REFUSED (#39):**
>   the **"Awaiting your approval"** KPI + the **Approve/Query** workflow (no owner-approval state machine — deductibility is
>   set by the management agreement). By-category proportional bars retained (Batch B).
> - **Demo (D-BE-5):** `seedOwnerBalanceAccrual` (un-statemented October revenue → a real positive Available **€963.60**) +
>   `seedOwnerExpenses` (a flagged spread across both apartments + all three categories + the deductible tri-state; the demo
>   agreement marks `other` non-deductible so incidental supplies stay off the owner ledger).
> - **Verified:** suite **1077 tests / 8370 assertions green** (`OwnerBalanceViewTest` 6, `OwnerExpensesViewTest` 5);
>   PHPStan **L5 = 0**; Pint clean; `migrate:fresh --seed` clean; both pages browser-verified. 🔒 reconciling to the cent
>   (#17), IBAN masked never full (#28), exports CAP_EXPORTS-gated (#42), nothing fabricated / no money-movement (#39).
>   **Invariants #1–59.**
>
> **Owner exact-match track shipped: Overview (#54) · My Units + Add Unit (#55) · Bookings + PDF (#56) · Statement + PDF
> (#57) · Payouts + Payout Method (#58) · Balance + Expenses (#59).** Remaining built-but-not-yet-exact-matched: EARNINGS —
> Tax-center · MANAGE — Reports/Insights/Rates/Reviews/Agreement/Messages · ACCOUNT — Settings/Security/Team. Everything
> else below is current (tenant Phases 0–7 + polish COMPLETE; email + live Stripe test-mode-proven; Super Admin panel
> complete + audited). No launch-blocking build remains.
>
> ---
>
> ⭐⭐⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v4) · owner PAYOUTS + PAYOUT METHOD exact-match (#58; ⚠️ real money + real
> banking). This block SUPERSEDES every block below it (all kept as historical detail). Fresh-session entry point:
> `docs/RESUME.md` → this block → `docs/DECISIONS.md`.**
>
> **The owner-page wireframe-exact-match track added its 5th + 6th surfaces: the owner Payouts (`/owner/payouts`) + Payout
> Method (`/owner/payout-method`) pages (`docs/OWNER-PAYOUTS-AUDIT.md`, DECISIONS 2026-07-28 #58, DIVERGENCE rows 130/136).**
> A read-only PHASE-1 audit first classified EVERY element REAL-NOW / ACCOUNT-GATED / NOT-BUILT (load-bearing here — a fake
> bank verification or payout is dangerous, not just dishonest), founder-approved with four decisions, then two gated
> builds. **Both pages match their wireframes EXACTLY, real where real, honest where account-gated, nothing faked.**
> - **REAL (wired):** payout **history / KPIs / the reconciling next-payout breakdown (#17) / the lifecycle stepper (real
>   states only) / the remittance PDF**; the **masked payout IBAN** (`OwnerNextPayout`, #28, CAP_PAYOUT-only); the whole
>   **bank-account CRUD** (add/set-default/remove — encrypted IBAN, mod-97-validated, self-only, `$capPayout`); **Stripe
>   Connect onboarding** (`OwnerConnectController`, test-mode; live enablement flips on the `account.updated` webhook — the
>   live-key flip is Subhan's); a NEW real **payout-preferences store** (`owner_payout_preferences` — frequency/day/minimum/
>   pause via `owner.payouts.preferences`, a `$capPayout` controller route #20) shown on both pages; the **payout-history
>   Export CSV**; and the **Tax & remittance details** inline on Payout Method (real `Owner` fields via the ONE
>   `owner.tax.save` path — anti-drift + the real hold-release; feeds the remittance PDF, now printing `legal_name`).
> - **HONEST account-gated (exact design, labelled pending the live banking connection, NEVER faked):** micro-deposit
>   **verification** (no verification column → no fake "Verified"/"Verifying"/"deposits sent"), the live **transfer** rail,
>   **SEPA** tracing, the 48h default-**hold**. The on-hold banner surfaces ONLY the real **tax-hold** (pending payout +
>   no tax details), never a fabricated "verification pending". The projected upcoming schedule stays "not yet available".
> - **Demo (D-PAY-1):** a flagged, mod-97-VALID default payout method (Revolut Business · LT IBAN, encrypted, masked
>   ···1000) — WITHOUT tax details, so the tax-hold "Paused" + "Add tax details" attention demo stays intact.
> - **Verified:** suite **1073 tests / 8324 assertions green** (`OwnerPayoutsViewTest` 2→6 + `OwnerPayoutMethodTest` 7→9);
>   PHPStan **L5 = 0**; Pint clean; `migrate:fresh --seed` clean; both pages browser-verified; the preferences + inline-tax
>   writes proven. 🔒 IBAN masked never full (#28), writes `$capPayout`/`$capActions` controller routes (#20/#42),
>   reconciling to the cent (#17), nothing fabricated (#39). **Invariants #1–58.**
>
> **The owner-page exact-match track has now shipped Overview (#54) · My Units + Add Unit (#55) · Bookings + Booking-Summary
> PDF (#56) · Statement + Statement PDF (#57) · Payouts + Payout Method (#58).** Remaining built-but-not-yet-exact-matched:
> EARNINGS — Balance/Expenses/Tax-center · MANAGE — Reports/Insights/Rates/Reviews/Agreement/Messages · ACCOUNT —
> Settings/Security/Team. Everything else below is current (tenant Phases 0–7 + polish COMPLETE; email + live Stripe
> test-mode-proven; the Super Admin panel complete + audited). No launch-blocking build remains.
>
> ---
>
> ⭐⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v3) · owner STATEMENT exact-match (page + PDF, #57). This block SUPERSEDES every
> block below it (all kept as historical detail). Fresh-session entry point: `docs/RESUME.md` → this block →
> `docs/DECISIONS.md`.**
>
> **The owner-page wireframe-exact-match track added its 4th surface: the owner Statement page + the downloadable
> Statement PDF (`docs/OWNER-STATEMENT-AUDIT.md`, `DECISIONS.md` 2026-07-28, DIVERGENCE row 132 → RESTRUCTURED-exact).**
> Both were rebuilt to their two decoded wireframes (`Owner Statement.html` = the app page; `Owner Statement PDF.html` =
> the print document) EXACTLY, on REAL owner-scoped, reconciling data. **Two page refusals were STALE and are now REAL**
> (their backends shipped in Overview #54 on a sibling surface): the **occupancy card + mini bar chart** (shared
> `OwnerOccupancyTrend`) and the **masked payout IBAN** (shared `OwnerNextPayout`, CAP_PAYOUT-only). Also: the month
> "jump to month" switcher, the plain-glass hero, the gross "N nights · M units" sublabel, a standalone Download-statement,
> a **Statement-history Export CSV + a per-year Export PDF + a per-row PDF** (`owner.statements.history.*`, owner-scoped +
> CAP_EXPORTS, `?year=` clips), and by-unit unit-name links. The **PDF** became the wireframe's letterhead statement —
> real `billing_company`/`country` letterhead + OS-YYYY-MM ref + a net-payable dark headline + real **Occupancy/Nights/ADR**
> stats + a 3-column breakdown + a **Performance-by-unit** table + a payout-note callout (IBAN CAP_PAYOUT-gated) + an
> identity/email footer. **Anti-drift:** three shared helpers extracted — `OwnerNextPayout` + `OwnerOccupancyTrend` (from
> `OwnerDashboard` #54, which now delegates) + `OwnerStatementBreakdown` (page + PDF share ONE per-unit split). **Honesty
> held:** breakdown Σ==net + by-unit Σ==net (page)/gross (PDF) to the cent (#17); NO guest data on either surface (#28);
> exports are CAP_EXPORTS controller routes, no `/livewire/update` (#20); the IBAN CAP_PAYOUT-only (#30); no "payment & ops
> fees" line and no estimated payout date invented (#39). A **flagged demo statement history** (Apr–Aug 2026, real stays →
> attribution → statements finalized in period order → paid) makes the page/Overview demo on a real shape; **September's
> statement is unchanged (net €885.60)**. **Verified:** suite **1066 tests green** (`OwnerStatementViewTest` 5→9 +
> `OwnerStatementPdfTest` 5→7); PHPStan **L5 = 0**; Pint clean; `migrate:fresh --seed` clean; browser-verified page + rendered
> document vs the two wireframes; adversarial review on the full diff. **Invariants #1–57.**
>
> **The rest of the state below is current** — everything buildable is COMPLETE (tenant Phases 0–7 + polish; email + live
> Stripe test-mode-proven; the Super Admin panel complete + audited). The owner-page exact-match track has now shipped
> **Overview (#54) · My Units + Add Unit (#55) · Bookings + Booking-Summary PDF (#56) · Statement + Statement PDF (#57)**;
> the remaining owner pages (EARNINGS — Payouts/Balance/Expenses/Tax-center · MANAGE — Reports/Insights/Rates/Reviews/
> Agreement/Messages · ACCOUNT — Settings/Security/Team) are built + at-parity, the next candidates if the founder continues.
>
> ---
>
> ⭐⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 (v2, TOTAL-RECALL). This block SUPERSEDES every block below it (all kept as
> historical detail). Fresh-session entry point: `docs/RESUME.md` → this block → `docs/DECISIONS.md`.**
>
> **VERIFIED THIS PASS (docs-only consolidation; no feature code changed):** `git` HEAD **`f9da60d`** on `main`
> (== `origin/main`, clean) · suite **1060 tests / 8207 assertions green** (`php artisan test`, exit 0) · PHPStan
> **L5 = 0** · Pint **clean** · `migrate:fresh --seed` **clean** · **Invariants #1–56** (the numbered list below is
> reconciled to #56).
>
> ---
>
> ### WHAT VYROBEL IS
> A multi-tenant, AI-native **hotel / apartment-management SaaS** for operators running a MIX of hotels, aparthotels,
> serviced apartments, villas and multi-unit rentals. Three layers: (1) the **TENANT product** — an operator back office
> (`/app`), an external-owner portal (`/owner`), a guest portal (`/guest`), a public storefront (`/book/{slug}`) and
> public self-signup (`/signup`) that provisions a whole new trial tenant; (2) the **PLATFORM** — a Super Admin panel
> (`/admin`) to run the SaaS; (3) the **integrations tail** (email, payments, channel manager, KYC).
>
> **Stack (locked; see `CLAUDE.md`):** Laravel **13** / PHP 8.4 · MySQL 8 InnoDB · Livewire **4** + Blade + Tailwind **4**
> (Vite) · Redis + **Horizon** · **ULID** PKs on tenant tables · spatie **teams-mode** RBAC (team = org) · shared-DB
> multi-tenancy via `organization_id` + `BelongsToOrganization`/`OrganizationScope` (`runAsSuperAdmin` = the ONE
> sanctioned bypass) · **integer minor-unit money** (`*_minor` + `Money`) · Pest 4 / PHPStan L5 / Pint. Browser
> verification = the **Playwright MCP** driving `php artisan serve` (real, but NOT a re-runnable suite).
>
> ### COMPLETE BUILD STATUS
> **Everything buildable is DONE.** No launch-blocking build remains.
> - **Phases 0–7 — the entire tenant product — COMPLETE + certified** (per-gate detail in `DECISIONS.md`): P0 fix/harden ·
>   P1 operator app · P2 owner portal · P3 notifications + all transactional emails · P4a/b/c the guest portal · P5 reviews ·
>   P6a multi-property (`PropertyContext`, the complete `->first()` refactor) + P6b analytics (`PerformanceMetrics`) ·
>   P7a tenant provisioning/self-signup · P7b subscription billing (free-trial model) · P7c owner sub-tenancy/team ·
>   P7d team schedule/rostering.
> - **Phase 8 — account-gated integrations — the two OBTAINABLE ones are lit up:** **production email** (Postmark;
>   `MailSafetyGuard`; `docs/EMAIL-PRODUCTION.md`) and **live Stripe across all three seams** (subscription billing ·
>   guest card charge · owner payout/Connect) — **test-mode-proven** (webhook-driven, signature-verified, multi-layer
>   idempotent, no raw PAN/SAQ-A, payout double-transfer-guarded; `StripeSafetyGuard` refuses a live key outside
>   `production`). The **live-key flip + DNS/token are Subhan's** documented cutovers (`docs/STRIPE-PRODUCTION.md`).
> - **Cross-cutting hardening DONE:** the full **design-fidelity pass** (4 gates, `docs/design/DIVERGENCE-REGISTER.md` =
>   the per-page source of truth) · the **polish sweep** (`docs/POLISH-SWEEP.md`) · the **GAP-SWEEP batches A/B/C**
>   (`docs/GAP-SWEEP.md` — nav filter, owner PDFs wired, REAL analytics depth #47, operator money-write **idempotency**
>   #48) · the **notification bell→panel** rebuild · the **read-only system audit** (`docs/AUDIT-REPORT.md`).
> - **THE SUPER ADMIN PANEL (`/admin`) — COMPLETE + AUDITED (invariants #49–53).** Built across Gate 0 (isolated realm +
>   hardened auth + **mandatory 2FA**) + Build 1 + Build 2, then a **Playwright audit (0 critical / 0 P1, production-ready)**,
>   a **Profile/Account page**, and a **completeness review** (`docs/SUPERADMIN-AUDIT.md` + `docs/SUPERADMIN-COMPLETENESS.md`).
>   Surfaces: Dashboard (real cross-org metrics) · Customers (list/detail/**suspend**/reactivate/assign-plan) · Plans
>   (DB CRUD + feature-gating + assign) · Security & Activity Log (login-signal capture + audit slice) · Integrations
>   (encrypted platform channel credentials + test-stub) · Account. Admins are created via the `admin:create` CLI.
>   **The panel is LAUNCH-READY for a free-trial / early-access product exactly as-is.**
> - **THE OWNER-PAGE EXACT-MATCH TRACK (ongoing hands-on polish — NOT launch-blocking).** Per-page wireframe-exact
>   rebuilds of the owner portal on REAL backends, "prior deferrals in scope". **DONE + pushed:** owner **Overview**
>   (#54, `docs/OWNER-OVERVIEW-AUDIT.md`) · **My Units + Add Unit** (#55, `docs/OWNER-UNITS-AUDIT.md`) · **Bookings page +
>   Booking-Summary PDF** (#56, `docs/OWNER-BOOKINGS-AUDIT.md`, commits `b571555`+`f9da60d`). **NOT yet exact-matched**
>   (they are built + AT-PARITY, just not through the wireframe-exact rebuild): EARNINGS — Statements/Payouts/Balance/
>   Expenses/Tax-center · MANAGE — Reports/Insights/Rates/Reviews/Agreement/Messages · ACCOUNT — Settings(Profile)/
>   Security/Team. These are the remaining candidates if the founder continues this track.
>
> ### HOW TO DECODE AN OWNER WIREFRAME (the method — reused every exact-match gate)
> The prototypes in `docs/design/raw/*.html` are **`dc-runtime` bundles**, NOT plain HTML — the visible markup is packed
> in a script. To read one: **render it in a browser (Playwright over a throwaway `php -S` static server — `file:` is
> blocked)** and screenshot, OR **`JSON.parse` the `__bundler`/template `<script>` payload → the `<x-dc>` body is the
> real markup + design tokens**. Then match colour/gradient/font/hover/shadow/radius/spacing/animation exactly. (Memory:
> `owner-wireframe-decode`.) Reuse the **shared owner chrome** — `<x-owner.shell>` (frosted sidebar/topbar, breadcrumb,
> account menu) + the **`--vy-*` design tokens** in `resources/views/components/layouts/owner.blade.php`'s `<style>`
> (accent `#C2410C`, `--vy-grad`, glass surfaces, `.vy-card/.vy-kpi/.vy-table/.vy-status`, `.vy-kpi-grid--4`) — and the
> shared bar chart `<x-charts.bars>`. **Never** invent one-off tokens; **page-scope** any page-specific CSS (a class
> name leaking into the shared owner layout can trip another page's `assertDontSee` isolation test).
>
> ### HONEST DEFERRALS (do NOT build ad hoc — see the register below + `docs/SUPERADMIN-COMPLETENESS.md` / `GAP-ANALYSIS.md`)
> - **Platform / paid-launch:** Stripe **billing sync** + revenue/invoices/refunds (needs live Stripe) · **suspension
>   ENFORCEMENT** (Suspend sets a flag today; a suspended customer can still log in) · **impersonation** (support "view as
>   customer") · **per-feature usage limits** (plan `feature_limits` beyond the boolean gate) · **customer self-connect**
>   of their own Stripe/channels · the **Horizon-gate RE-POINT** to the `admin` guard (the founder can't reach Horizon in
>   prod today — the gate is bound to an unassignable role) · multi-admin / GDPR data-request / manual customer-create +
>   trial-adjust (day-two ops).
> - **Tenant-product tails:** **real channel sync** (Channex live) · WhatsApp/OTA inbox channels · SMS · guest ID/**KYC** ·
>   owner payout EXECUTION (Stripe Connect + KYC) · the **My-Units action-center** snooze/dismiss store + richer day-detail
>   (function as real links today) · the **operator approve→create-Unit** flow (Add-Unit lands a real `UnitRequest`, NOT a
>   `Unit` — the operator-side approval that promotes a request to a Unit is deferred) · the **remaining un-exact-matched
>   owner pages** (above) · assorted **P3 tails** (editable email-template store, etc.). **Each is DEFERRED HONESTLY —
>   build the experience, never fake the charge/send/verify.**
>
> ### WHAT'S LEFT / PATH TO LAUNCH
> 1. **Choose the launch model.** *Free-trial / early-access* is **launch-ready NOW** (panel + product as-is). *Paid* needs
>    only the already-scoped **Stripe go-live** (Subhan's live-key flip) + **suspension enforcement**.
> 2. **Deploy.** Runs locally on XAMPP; needs production hosting / domain / HTTPS / MySQL 8 / Redis-Horizon.
> 3. **Subhan's cutovers.** Email DNS + Postmark token (`docs/EMAIL-PRODUCTION.md`) · the Stripe live-key flip
>    (`docs/STRIPE-PRODUCTION.md`).
> 4. **(Ongoing, optional) owner-page exact-match polish** on the remaining pages — quality work, NOT launch-blocking.
>
> ### LOGIN CREDENTIALS (all password `password`; source of truth = `database/seeders/DemoSeeder.php` + `PlatformAdminSeeder.php`)
> | Realm | URL | Email | Role |
> |---|---|---|---|
> | Operator — org admin | `/app` | `demo-admin@vyrobel-demo.test` | Property Owner (org admin, `['*']`) |
> | Operator — manager | `/app` | `demo-staff@vyrobel-demo.test` | Hotel Manager |
> | Owner portal | `/owner` | `demo-owner@vyrobel-demo.test` | External Owner (primary owner) |
> | Guest portal | `/guest/login` | `demo.traveller@vyrobel-demo.test` | booking-tied guest |
> | **Platform admin** | `/admin` | `admin@vyrobel-demo.test` | Super Admin (**forced 2FA enrol on first login**) |
>
> Public storefront (no login): `/book/vyrobel-demo`. Self-signup (provisions a new trial tenant): `/signup`. ⚠️ `demo-owner`
> is REFUSED from `/app` and bounced to `/owner` — invariant #26 WORKING, not a bug. (Also seeded: owner sub-users
> `demo-owner-accountant@` / `demo-owner-viewer@`; operator `demo-accountant@` / `demo-housekeeper@`.)
>
> ---
>
> ⭐⭐⭐⭐ **CURRENT STATE — 2026-07-28 · owner Bookings EXACT-MATCH BUILD complete (page + PDF, #56) on top of the
> total-recall consolidation + owner My Units/Add Unit exact-match (#55). This block SUPERSEDES every block below it
> (all kept as historical detail). For the fresh-session read-order + one-paragraph summary + creds + next action, start
> at `docs/RESUME.md`.** Re-verified against the repo THIS pass: **suite green `1060` tests / `8207` assertions
> (`php artisan test`, exit 0)** · PHPStan **L5 = 0** · Pint clean · `migrate:fresh --seed` clean · **Invariants #1–56**.
> **Owner Bookings (`docs/OWNER-BOOKINGS-AUDIT.md`, invariant #56):** the LIVE page `/owner/bookings` + the Booking-Summary
> PDF are now WIREFRAME-EXACT on REAL owner-scoped data, built as two gated deliverables. The two items once refused as
> fabrication are REAL (never faked): the page's **"Bookings over time" chart** (`reservations.status` stacks + net via
> `OwnerLedgerAttribution` + a real goal line) and **"Monthly goal" ring** (real net ÷ an owner-SET `owner_monthly_target`;
> honest "no target" when unset) — plus a Status filter, date-range, sortable columns, pagination, a name-only CSV Export,
> and the `$capActions` target-write (controller route, currency derived server-side, #20/#30) — page + export share the
> ONE `OwnerBookingsReader`. The **PDF** gained REAL owner-scoped occupancy (`PerformanceMetrics::forOwnerUnits`,
> physical-unit-keyed), the guest NAME-only column (`GuestName`, #28), the real channel commission (`ota_cost`), and a real
> letterhead VAT/country — Σ(rows)===gross unchanged; the OTA **brand** split stays refused (unmodelled). Two adversarial-
> review passes (GATE 1 fixed 4 real bugs — subMonth overflow, sticky sort-dir, chart-legend-vs-truncation, net-sort; GATE
> 2 clean). HEAD on `main`; the only non-doc work since the last consolidation was these two exact-match gates.
>
> **WHAT VYROBEL IS.** A multi-tenant, AI-native **hotel / apartment-management SaaS**, in three layers: the TENANT
> PRODUCT (operator back office `/app` · owner portal `/owner` · guest portal `/guest` · public storefront + booking
> engine `/book/{slug}`), the SaaS LAYER (public self-signup `/signup` → atomic tenant provisioning · subscription
> billing on a free-trial model · owner sub-tenancy · staff rostering), and the **SUPER ADMIN platform panel `/admin`**
> (the founder's console — a separate hardened realm). **Stack (verified against `composer.json`):** **Laravel 13**
> (`laravel/framework ^13.8`) / PHP 8.4 · MySQL 8 / InnoDB · Livewire 4 + Blade + Tailwind 4 · Redis + Horizon · ULID
> PKs · spatie **teams-mode** RBAC · shared-DB multi-tenancy via `organization_id` + the `OrganizationScope` global
> scope (`runAsSuperAdmin` = the ONE sanctioned bypass). Integer minor-unit money (`*_minor` + `Money`).
>
> **BUILD STATUS — EVERYTHING BUILDABLE IS COMPLETE; the active hands-on track is owner-page wireframe-exact-match
> polish (not launch-blocking).**
> - **Tenant product (Phases 0–7): COMPLETE** — harden · operator app · owner portal · notifications + transactional
>   emails · guest auth + portal · reviews · multi-property (`PropertyContext`) · REAL analytics (`PerformanceMetrics`,
>   RevPAR = ADR × occupancy) · atomic tenant provisioning · subscription billing (free-trial) · owner team · schedule.
> - **Phase 8 (account-gated integrations) — the two OBTAINABLE ones LIT UP (test-mode / code-complete):** production
>   email (Postmark + `MailSafetyGuard`; `docs/EMAIL-PRODUCTION.md`) · LIVE STRIPE across all three seams (subscription ·
>   guest card · owner payout/Connect — `StripeSafetyGuard`, webhook-source-of-truth, multi-layer idempotency, payout
>   double-transfer-guarded; `docs/STRIPE-PRODUCTION.md`). The live cutovers (email DNS + token; Stripe live-key flip)
>   are the founder's remaining NON-CODE steps. Still account-gated: Channex live sync · WhatsApp/OTA inbox · SMS · KYC.
> - **Design-fidelity pass (76 surfaces) + owner account rebuild + notification-panel rebuild + Batches A/B/C + the
>   comprehensive polish sweep (`docs/POLISH-SWEEP.md`, Batches 1–6 + P3 tails, FULLY CLEARED) + add-charge server
>   idempotency: ALL COMPLETE.**
> - **⭐ SUPER ADMIN PANEL: COMPLETE + AUDITED + PRODUCTION-READY.** Gate 0 (hardened isolated `/admin` realm —
>   separate `platform_admins` table + `admin` guard, mandatory 2FA, the audited `PlatformQuery::read()/mutate()`
>   wrapping `runAsSuperAdmin`, noindex, seeded-not-signup; **#49**) → Build 1 (Dashboard · Customers
>   [list/detail/suspend/reactivate/assign-plan] · Plans [DB-backed CRUD + feature-gating; **#50**]) → Build 2
>   (Security & Activity Log [login capture, 90-day-retention privacy; **#51**] · Integrations [encrypted channel
>   credentials + management + test-stub; **#52**]) → deep Playwright audit (`docs/SUPERADMIN-AUDIT.md` — 0 critical /
>   0 P1) → Profile & Account page (profile · email re-verify · password · 2FA recovery; **#53**) → completeness review
>   (`docs/SUPERADMIN-COMPLETENESS.md` — launch-ready + the roadmap).
> - **⭐ OWNER-PAGE WIREFRAME-EXACT-MATCH WORK (the recent track — every live owner page matched to its decoded
>   design-component wireframe: EXACT design + REAL owner-scoped backends, one gated page at a time):**
>   - **Owner OVERVIEW** (`/owner`) — exact match + real backends via the shared owner chrome + shared `OwnerAttention` /
>     `OwnerUpcomingStays` / `OwnerOccupancy` / `OwnerLedgerAttribution`. **COMPLETE + pushed** (`d80abdf` design /
>     `f267e2c` backends; **INVARIANT #54**).
>   - **Owner MY UNITS** (`/owner/units`) — exact design + real card/calendar actions (edit-pricing via `OwnerRateService`
>     per-room-type [honest-disabled for shared inventory] · message-relay to the operator [name-only #28] · block-dates
>     via `OwnerBlockService` · month-occupancy CSV export [counts-only #28]; action-center snooze/day-detail honestly
>     deferred) + the calendar heat-grid recolored to the wireframe terracotta scale (`4a3d538`). **COMPLETE + pushed**
>     (`0be1363` / `33e2475`; **INVARIANT #55**).
>   - **Owner ADD UNIT** (`/owner/add-unit`) — the exact **4-step wizard** (basics · space & capacity · photos · rate &
>     terms) + sticky Live preview + Done, on a **rich, real, pending `UnitRequest`** (schema additive; amenities via the
>     shared `amenitiables` pivot; photos via the shared `media` collection, ULID filenames); the write is ONE gated
>     controller POST (never `/livewire/update`, #20); commission is the owner's REAL agreement rate (honest line if
>     none). Proven end-to-end in the browser (a real "Casa Verde Retreat" request filed + surfaced read-only on the
>     operator `unit-requests` screen; a demo artifact `migrate:fresh --seed` clears). **COMPLETE + pushed** (`2193840`;
>     **INVARIANT #55**). Decisions **D1** (action-center = real items + honest-empty; no owner approve/decline entity
>     exists) · **D2** (message-guest = relay to the operator team, name-only; no owner→guest channel, #28) · **D3**
>     (add-unit = a rich real `UnitRequest`, the live `Unit` stays operator-made) — all recorded in `DECISIONS.md`.
>   - **⬅ NEXT (active): Owner BOOKINGS page + Owner BOOKING-SUMMARY PDF.** **PHASE-1 read-only audit is COMPLETE
>     (`docs/OWNER-BOOKINGS-AUDIT.md`, 2026-07-28) — awaiting GATE-1 approval.** Both live surfaces ALREADY exist on real
>     owner-scoped data (the page `App\Livewire\Owner\OwnerBookings`; the PDF **`OwnerBookingSummaryDocument`** via
>     `owner.statements.bookings` + `resources/views/pdfs/owner-booking-summary.blade.php`) — the work is an EXACT-MATCH
>     upgrade, "prior deferrals in scope". The audit's decisive finding: every wireframe figure has an honest owner-scoped
>     source, so the two items once refused as fabrication become REAL — the **"Bookings over time" chart** (real
>     per-check-in-month status counts + net via `OwnerLedgerAttribution` + a **goal line backed by a NEW owner-set
>     `owner_monthly_target`**, owner-supplied not invented) and the **PDF occupancy / per-channel commission /
>     guest-name-only column** (the old PDF-docblock refusals are STALE — `OwnerOccupancy`/`PerformanceMetrics` +
>     `owner_ledger.ota_cost` now back them; guest name-only is an established owner-surface precedent, #28). The ONLY
>     standing refusal is the OTA **brand** split (Booking.com vs Airbnb — unmodelled; rendered as the real `source`
>     enum). GATE 1 = the page (breadcrumb · GUESTS stat · chart + goal ring · Status/date-range filters · sortable
>     table · pagination · Export CSV); GATE 2 = the PDF (letterhead billing_company/VAT/country · BS-YYYY-MM ref ·
>     4-cell KPI incl. occupancy · by-unit occupancy · by-channel commission · guest-name-only · callout footer). See §4
>     of the audit for the full build plan.
>
> **THE DECODE METHOD (owner wireframes).** The attached `docs/design/raw/*.html` are **`dc-runtime` `__bundler`
> bundles**, not plain HTML — the real markup is `JSON.parse(<script type="__bundler/template">)` → the `<x-dc>` body
> (inline-style design tokens + `{{ }}` placeholders); a `<script type="text/x-dc">` DCLogic model holds the sample
> data. **Decode before matching.** Reuse/extend the shared **warm-glass owner chrome** (`<x-owner.shell>`, frosted
> sidebar/topbar) + the `--vy-*` tokens (accent `linear-gradient(140deg,#E8703A,#C2410C)`; peach→grey diagonal page bg;
> Inter + Sora; vyFadeUp/vyDrift/vyShimmer) — never one-offs. A PDF wireframe (white letterhead, no app shell) is a
> DIFFERENT deliverable — match the print document's own design.
>
> **QUALITY STATE (re-verified against the repo THIS pass):** suite **1050 tests / 8149 assertions green** (exit 0);
> PHPStan **L5 = 0**; Pint **clean**; `migrate:fresh --seed` clean. **Invariants #1–55** — the canonical numbered list
> below is now reconciled to #55 (it had lagged: #54 owner Overview, #55 owner My Units + Add Unit added this pass).
>
> **HONEST DEFERRALS (all correctly deferred — NONE block a launch):** Stripe billing sync (the paid-launch
> dependency) · impersonation (high-risk support tool) · suspension access-ENFORCEMENT (today a status flag; needed to
> actually cut off a non-payer) · per-feature-limit enforcement · the real channel sync + customer-self-connect (need
> the channel accounts) · **the Horizon queue gate is unreachable in production** (bound to the unassignable global
> `Super Admin` role — re-point it to the `admin` guard; small + high-value) · the My Units action-center snooze/dismiss
> persistence + a richer calendar day-detail · the operator review/approve→create-live-`Unit` flow · the remaining
> owner pages not yet exact-matched (Bookings/PDF audited-not-built; others further out) · the P3 polish tails.
>
> **WHAT'S LEFT (path to launch).** The product + the Super Admin panel are launch-ready — a **free-trial /
> early-access launch needs nothing more**; a **paid launch** needs only the already-scoped **Stripe go-live +
> suspension enforcement**. Then **DEPLOYMENT** (production hosting / domain / HTTPS / MySQL / Redis-Horizon) + the
> founder's email + Stripe cutovers. The owner-page exact-match work (Bookings + PDF next) is ongoing polish, not
> launch-blocking. **Roadmap:** Phase 0 launch → 1 first-paying (billing sync · real revenue/invoices/refunds ·
> suspension enforcement · re-point the Horizon gate · trial-adjust/cancel/manual-create) → 2 scale (multi-admin +
> roles · alerting · GDPR export/erase · impersonation · usage analytics) → 3 polish.
>
> **RESUME CREDENTIALS** (all password `password`; `php artisan migrate:fresh --seed` recreates them): operator
> **`demo-admin@vyrobel-demo.test`** (Property Owner / org admin) or `demo-staff@vyrobel-demo.test` (Hotel Manager) at
> `/app`; owner **`demo-owner@vyrobel-demo.test`** at `/owner`; guest **`demo.traveller@vyrobel-demo.test`** at
> `/guest/login`; **PLATFORM ADMIN `admin@vyrobel-demo.test`** at `/admin` (forced 2FA enrolment on first login).
> Public storefront (no login) `/book/vyrobel-demo`; self-signup `/signup`.
>
> **THE NEXT ACTION.** No launch-blocking build remains. The active hands-on task is the **owner-page exact-match
> track: Owner Bookings page + Booking-Summary PDF** — the PHASE-1 audit is done (`docs/OWNER-BOOKINGS-AUDIT.md`), so
> the next step is **GATE 1 (the page) then GATE 2 (the PDF)** per §4 of that audit (both on real data; guest name-only
> #28; net reconciling #17; owner writes on controller routes #20; the PDF stays CAP_EXPORTS-gated). In parallel, the
> founder's strategic decisions stand: **choose the launch model** (free-trial now vs paid after Stripe go-live +
> suspension enforcement), then **deploy**. Read `docs/RESUME.md` first.
>
> ---
>
> ⭐⭐⭐ **CURRENT STATE — 2026-07-27 · OWNER OVERVIEW exact-match + real backends (INVARIANT #54).** The owner
> Overview (`/owner`) was rebuilt to the decoded `docs/design/raw/Owner Dashboard.html` wireframe EXACTLY **and**
> wired so every section is operational on REAL owner-scoped data (no stubs, no fabrication). Two gates: **GATE 1**
> (design, `d80abdf`) — peach-diagonal background + ambient orbs/streaks, frosted **terracotta-active** sidebar,
> greeting header + account-menu dropdown, 42px bell, attention bar, glass quick actions, 52px hero + terracotta
> delta, ↗ metric link-cards, segmented earnings toggle, halo dark rail (shared owner chrome extended, not
> one-offs; the 5 extra real nav items KEPT — D2). **GATE 2** (real backends) — occupancy KPI + per-unit bars +
> delta + sparkline (`OwnerOccupancy`), the attention feed (new shared `OwnerAttention`), the next-30-days upcoming
> stays (new shared `OwnerUpcomingStays` — **guest NAME only #28** + owner NET via `OwnerLedgerAttribution` +
> client-side Alpine Source/Status filters — a Livewire round-trip would bypass the owner scope), the masked IBAN +
> tax-hold paused-state, the time-aware greeting, and the 4 quick actions (real routes, capability-gated). Honesty
> held (#39): no earnings goal, no estimated payout date, no invented occupancy (the demo shows a real LOW
> occupancy, never a fake 78%); honest empty states. Mutation-/render-proven (`OwnerUpcomingStaysTest` +
> `OwnerDashboardViewTest` — 30-day clip · name-only · net-reconcile · honesty · `assertNoRawBlade`); browser-
> verified (real data + working filter dropdown + paused rail + account dropdown). Suite **1042**; PHPStan L5 = 0;
> Pint clean; `migrate:fresh --seed` clean. Docs: `docs/OWNER-OVERVIEW-AUDIT.md`, DECISIONS.md 2026-07-27,
> `design/DIVERGENCE-REGISTER.md` (owner Overview → RESTRUCTURED-exact + operational). **Invariants #1–54.**
>
> ---
>
> ⭐⭐ **CURRENT STATE — 2026-07-27 TOTAL-RECALL CONSOLIDATION. This block SUPERSEDES every block below it (all kept
> as historical detail). For the fresh-session read-order + one-paragraph summary + creds + next action, start at
> `docs/RESUME.md`.** Documentation-only consolidation — **no feature code changed** since the account page; HEAD on
> `main`, tree clean after the docs commit.
>
> **WHAT VYROBEL IS.** A multi-tenant, AI-native **hotel / apartment-management SaaS**, in three layers: the TENANT
> PRODUCT (operator back office `/app` · owner portal `/owner` · guest portal `/guest` · public storefront + booking
> engine `/book/{slug}`), the SaaS LAYER (public self-signup `/signup` → atomic tenant provisioning · subscription
> billing on a free-trial model · owner sub-tenancy · staff rostering), and the **SUPER ADMIN platform panel `/admin`**
> (the founder's console — a separate hardened realm). **Stack:** Laravel 13 / PHP 8.4 · MySQL 8 / InnoDB ·
> Livewire 4 + Blade + Tailwind 4 · Redis + Horizon · ULID PKs · spatie **teams-mode** RBAC · shared-DB multi-tenancy
> via `organization_id` + the `OrganizationScope` global scope (`runAsSuperAdmin` = the ONE sanctioned bypass).
>
> **BUILD STATUS — EVERYTHING BUILDABLE IS COMPLETE.**
> - **Tenant product (Phases 0–7): COMPLETE** — harden · operator app · owner portal · notifications + transactional
>   emails · guest auth + portal · reviews · multi-property (`PropertyContext`) · REAL analytics (`PerformanceMetrics`,
>   RevPAR = ADR × occupancy) · atomic tenant provisioning · subscription billing (free-trial) · owner team · team schedule.
> - **Phase 8 account-gated integrations — the two OBTAINABLE ones are LIT UP (test-mode / code-complete):**
>   PRODUCTION EMAIL (Postmark + `MailSafetyGuard`; `docs/EMAIL-PRODUCTION.md`) · LIVE STRIPE across all three seams
>   (subscription · guest card · owner payout/Connect — `StripeSafetyGuard`, webhook-source-of-truth, multi-layer
>   idempotency, payout double-transfer-guarded; `docs/STRIPE-PRODUCTION.md`). The live cutovers (email DNS + token;
>   Stripe live-key flip) are the founder's remaining NON-CODE steps. Still account-gated: Channex live sync ·
>   WhatsApp/OTA inbox · SMS · guest ID/KYC.
> - **Design-fidelity pass (76 surfaces, `docs/design/DIVERGENCE-REGISTER.md`): COMPLETE (all 4 gates).** Owner account
>   rebuild + notification-panel rebuild: COMPLETE. Batch A (cheap wins) + B (analytics depth) + C (operator
>   operations): COMPLETE. The comprehensive polish sweep (`docs/POLISH-SWEEP.md`, all 4 dimensions, Batches 1–6 + P3
>   tails): FULLY CLEARED. Add-charge server idempotency (the last money-path correctness item): CLOSED.
> - **⭐ THE SUPER ADMIN PANEL: COMPLETE + AUDITED + PRODUCTION-READY.** Gate 0 (hardened isolated `/admin` realm —
>   separate `platform_admins` table + `admin` guard, mandatory 2FA, the audited `PlatformQuery::read()/mutate()`
>   wrapping `runAsSuperAdmin`, noindex + robots, seeded-not-signup) → Build 1 (Dashboard · Customers
>   [list/detail/suspend/reactivate/assign-plan] · Plans [DB-backed CRUD + feature-gating, additive]) → Build 2
>   (Security & Activity Log [login capture, 90-day-retention privacy, audit slice] · Integrations [encrypted channel
>   credentials + management + test-stub]) → deep Playwright audit (`docs/SUPERADMIN-AUDIT.md` — 0 critical / 0 P1;
>   UX/polish/honesty fixes) → Profile & Account page (profile · email re-verify · password · 2FA recovery) →
>   **completeness review (`docs/SUPERADMIN-COMPLETENESS.md`)**.
>
> **QUALITY STATE (verified against the repo):** suite **1040 tests green** — the admin work was strictly ADDITIVE, the
> tenant suite never regressed (981 → 997 → 1013 → 1029 → 1031 → 1040); PHPStan **L5 = 0**; Pint clean;
> `migrate:fresh --seed` clean. **Invariants #1–53** — the full canonical numbered list below was extended in THIS pass
> from #41 to #53 (it had lagged the prose).
>
> **HONEST DEFERRALS (all correctly deferred — real priority from `docs/SUPERADMIN-COMPLETENESS.md`; NONE block a
> launch):** Stripe billing sync (the paid-launch dependency) · suspension access-enforcement (a status flag today —
> needed to actually cut off a non-payer) · impersonation (high-risk; a support tool) · per-feature-limit enforcement ·
> real channel sync + customer-self-connect (need the channel accounts) · **the Horizon queue is unreachable in
> production** (its gate is bound to the unassignable global `Super Admin` role — re-point it to the `admin` guard;
> small + high-value) · the residual global-`Super Admin` assignability · the remaining P3 polish tails.
>
> **WHAT'S LEFT (path to launch, from the completeness review):** the panel is **operationally launch-ready** — a
> **free-trial / early-access launch needs nothing more**; a **paid launch** needs only the already-scoped **Stripe
> go-live + suspension enforcement**. Then **DEPLOYMENT** (production hosting / domain / HTTPS / MySQL / Redis-Horizon)
> + the founder's email + Stripe cutovers. **Roadmap:** Phase 0 launch → 1 first-paying (billing sync · real
> revenue/invoices/refunds · suspension enforcement · re-point the Horizon gate · trial-adjust/cancel/manual-create) →
> 2 scale (multi-admin + roles · admin alerting · GDPR export/erase · impersonation · usage analytics) → 3 polish.
>
> **RESUME CREDENTIALS** (all password `password`; `php artisan migrate:fresh --seed` recreates them): operator
> **`demo-admin@vyrobel-demo.test`** (Property Owner / org admin) or `demo-staff@vyrobel-demo.test` (Hotel Manager) at
> `/app`; owner **`demo-owner@vyrobel-demo.test`** at `/owner`; guest **`demo.traveller@vyrobel-demo.test`** at
> `/guest/login`; **PLATFORM ADMIN `admin@vyrobel-demo.test`** at `/admin` (forced 2FA enrolment on first login).
> Public storefront (no login) `/book/vyrobel-demo`; self-signup `/signup`.
>
> **THE NEXT ACTION for a fresh session:** there is NO outstanding build. The next decision is the founder's — **choose
> the launch model** (free-trial now vs paid after the Stripe go-live + suspension enforcement), then **deploy**. If
> building, take the next item from the `docs/SUPERADMIN-COMPLETENESS.md` roadmap (Phase 1). Read `docs/RESUME.md` first.
>
> ---

> ⭐ **CURRENT STATE — 2026-07-23 total-recall save (HEAD `cace277`, `main`, local == origin, tree clean).
> This block supersedes any earlier "next is Phase 7c" / "Rates next" prose below (kept as historical detail).**
>
> **Latest (2026-07-26, see `DECISIONS.md` for detail):** post-audit gap-sweep build batches A/B/C (owner PDFs · REAL
> per-unit + by-unit + Insights-cadence analytics · operator ops: housekeeping-assign / move / add-charge / on-desk
> payment money-gate + tax editing) — **Invariants #1–48** — then the **NOTIFICATION EXPERIENCE rebuild**: the top-bar
> **bell opens a PANEL (Alpine popover, no new dependency) in BOTH shells** instead of navigating (only "Open all
> notifications →" reaches the full page), built in the feed's exact design language (NO separate panel wireframe — the
> proto bell is static, recorded honestly) via the ONE shared `NotificationFeed::recentForUser()` + `<x-notifications.bell>`
> + `<x-notifications.category-icon>`; the full pages gained two parity deltas (square tile + unread wash). REAL-only (fake
> "Channels"/"Housekeeping" pills REFUSED), self-only #28/#26, existing self-only POST mark-read routes. Suite **977/977**.
> Then a **presentation POLISH SWEEP** (`docs/POLISH-SWEEP.md`, Batches 1–6: CSS tokens · hover states · dark-panel/tokenize ·
> the framework-free double-submit-disable · count-up · owner Expenses bars) and — closing that sweep's last money-path flag —
> **ADD-CHARGE SERVER IDEMPOTENCY**: `postCharge` now carries a per-submit server nonce mirroring the on-desk payment gate
> exactly (nullable-unique `folio_items.idempotency_key`; a `charge_nonce`), so a duplicate — even two concurrent POSTs
> bypassing the UI disable — records the charge ONCE (mutation- + browser-proven; extends **#48** to both money-write routes).
> Suite **981/981**; PHPStan L5 = 0; Pint clean. **Then the POLISH SWEEP's P3 tails cleared → the sweep is now FULLY CLEARED
> (P1·P2·P3):** signup step cross-fade (I4) + guest-portal keyboard focus rings (M5) + the password-strength meter on
> set-password & signup (D14 — a client-side VISUAL AID; the `Password::min(8)` server rules UNCHANGED + authoritative,
> 76 password-enforcement tests green) — all browser-proven; D10 chart consolidation re-assessed vs the prototype and
> **finally deferred by decision** (interactive Alpine chart vs pure-static shared component + a per-bar-label regression).
> Presentation + one client-side meter only; money/auth/isolation + invariants #1–48 UNTOUCHED; suite 981/981, PHPStan L5 = 0,
> Pint clean.
>
> **Then SUPER ADMIN · GATE 0 (2026-07-26) — the hardened, ISOLATED `/admin` platform realm (INVARIANT #49; features NOT
> built yet).** A NEW parallel realm ADDED ALONGSIDE the tenant realms, ADDITIVE ONLY — **the prior 981 tests all stay
> green** (suite now **997/997**: 981 existing + 16 new Gate-0 isolation proofs); PHPStan L5 = 0; Pint clean;
> `migrate:fresh --seed` clean (seeded demo admin). A separate `platform_admins` table + `PlatformAdmin` model (NO
> `organization_id`, NO spatie role) + a NEW `admin` session guard — a platform admin is NOT a tenant `User`, so tenant
> guards never resolve it and it never resolves a tenant user (the `guest`-realm discipline). **Cross-tenant reads use the
> EXISTING `TenantContext::runAsSuperAdmin()` via `PlatformQuery` — admin-guard-gated + audited + scope-restored (never
> leaks; `/admin` has no ambient tenant context, so a stray tenant query there fails CLOSED).** MANDATORY 2FA (TOTP),
> hardened rate-limited login, admin actions audited to a SEPARATE `platform_admin_actions` ledger, noindex + robots
> disallow (path not subdomain — SEO untouched), NO public sign-up (seeded via `php artisan admin:create`). A cohesive
> "control-room" admin design (distinct from the warm tenant glass). **Mutation-proven isolated + browser-verified**
> (login → forced 2FA → console; operator refused at `/admin`). **The deferred Phase-7a platform-admin item is RESOLVED
> PROPERLY** — platform admin ≠ org `User` ≠ the unassignable global spatie role (and STEP 0 confirmed there was never an
> `is_super_admin` flag on `users` to move; a test asserts its absence). See `DECISIONS.md` 2026-07-26. **Invariants #1–49.**
>
> **Then SUPER ADMIN · BUILD 1 (2026-07-26) — the core console: Dashboard + Customers + Plans** on the proven Gate-0
> foundation. ADDITIVE ONLY — **the existing 997 tests all stay green** (suite now **1013/1013** = 997 + 16 new). **🔒
> EVERY cross-tenant READ via the audited `PlatformQuery::read()`; every tenant-scoped WRITE via the new sibling
> `PlatformQuery::mutate()`** (both runAsSuperAdmin — scope-restored, admin-guard-gated, audited; no new bypass). **Dashboard:**
> real cross-org metrics (customers/status/subscriptions/properties/reservations/plan-mix/6-mo signups + a recurring
> PLAN VALUE labelled honestly as contracted-not-billed since charging is account-gated) — real (#39), honest zeros.
> **Customers:** list + detail of every org (operational facts only, **#28 — no end-guest PII**), + audited
> suspend/reactivate (`mutate`); **impersonation DELIBERATELY deferred** (a test asserts no such route). **Plans:** the
> Phase-7b `plans`/`plan_features` catalog is now ADMIN-authoritative — CRUD (integer money #17), and **plans DRIVE
> FEATURE-GATING** via the existing `Organization::planAllows` (fail-open): a reusable `EnsurePlanFeature` middleware +
> clean "not in your plan" page (additive — a granting plan behaves EXACTLY as today; only an excluding plan gates),
> assign-a-plan-to-a-customer (audited, drives features). **🔒 Stripe billing sync DEFERRED** (plan mgmt + gating +
> assignment now; the `stripe_price_id`/subscription sync is the go-live seam). `BillingService::catalog()` re-pointed to
> read features from the DB (config = seed + blurb). **Mutation- + browser-proven** (assigning demo Essential→Pro flipped
> `planAllows('channels')` false→true). **NEW INVARIANT #50 — plans are DB-backed + drive feature-gating ADDITIVELY**
> (granting = unchanged behaviour, excluding = a clean gated state; every admin cross-tenant op via the audited
> PlatformQuery). See `DECISIONS.md` 2026-07-26. **Build 2 = Security & activity log + Integrations. Invariants #1–50.**
>
> **Then SUPER ADMIN · BUILD 2 (2026-07-26) — the final two areas → THE PANEL IS COMPLETE.** ADDITIVE ONLY — **the
> existing 1013 tests all stay green** (suite now **1029/1029** = 1013 + 16 new). **Security & Activity Log
> (privacy-built-in):** auth signals (login/failed/lockout) captured by ADDED listeners on the framework auth events —
> **the tenant login flow is NOT modified** (the capture can never break auth); operator/owner use `Auth::validate` (no
> per-attempt Failed) so their signal is the Lockout, guest fires Failed, the admin realm dispatches its own Failed. **🔒
> PRIVACY BUILT IN FROM COMMIT 1 (GDPR):** a 90-day retention prune (`platform:prune-activity`, scheduled), security-
> monitoring purpose (documented), /admin-only, **#28 — no guest email persisted plaintext**. The view = recent sign-ins
> + a 24h failure summary + a cross-tenant account-events slice (audit ledger via `PlatformQuery::read`, audited) +
> per-customer slice. **Integrations:** the founder's master channel credentials (Channex/OTAs) in `platform_integrations`,
> **`encrypted:array` at rest** (like the owner IBAN, #28 — proven the plaintext never lands on disk), **write-only /
> masked (never echoed after save)**, managed via an audited action (records WHICH channel, never the value), a
> test-connection STUB (real sync account-gated, labelled honestly); the customer-self-connect model documented, built
> later. **NEW INVARIANTS #51** (auth signals captured by added-listeners — tenant flow unmodified; privacy-retained) **+
> #52** (platform credentials encrypted-at-rest + admin-managed + never-echoed). **THE SUPER ADMIN PANEL IS COMPLETE
> (Gate 0 + Build 1 + Build 2). Deferred tails (account-gated / documented follow-ups): Stripe billing sync,
> impersonation, suspension access-enforcement, per-feature-limit enforcement, the real channel sync, customer-self-connect.**
> See `DECISIONS.md` 2026-07-26. **Invariants #1–52.**
>
> **Then a DEEP END-TO-END AUDIT of the whole Super Admin panel (2026-07-27, `docs/SUPERADMIN-AUDIT.md`) — a full
> Playwright walk of every area/page/form/action, audited as hard on security as on UI. ZERO CRITICAL + ZERO P1;
> every high-stakes property re-verified LIVE (isolation, no cross-tenant leak [context-less tenant query throws
> after an admin read], every action audited, no credential plaintext leak, mandatory 2FA, no enumeration, #28 no
> guest PII, money #17). Genuine findings were all UX/polish/honesty, fixed additively:** customer-list PAGINATION
> (25/page, filters carried); SUSPENSION HONESTY (it's a status flag, not yet an access block — presentation fix,
> enforcement still deferred); a plans-catalog honesty note; the admin double-submit guard; the wired `plans.toggle`
> button; an admin favicon. Honest deferrals (Stripe sync, impersonation, suspension enforcement, per-feature limits,
> real channel sync, self-connect) confirmed correct + untouched. Additive; the security model preserved (audited
> `PlatformQuery`, no new bypass); tenant realms untouched; suite green (admin 50/50) + PHPStan L5 + Pint clean.
> **The Super Admin panel is AUDITED + FIXED + PRODUCTION-READY.**
>
> **Then the SUPER ADMIN PROFILE & ACCOUNT page (2026-07-27, INVARIANT #53)** — every platform admin manages their
> OWN identity (profile · login email · password · 2FA recovery), a real gap. Additive; existing 1031 tests green
> (suite now **1040/1040** = 1031 + 9 new). A SEPARATE parallel implementation for the admin realm that MIRRORS the
> owner account-rebuild discipline (not its wiring): 🔒 self-only; password change requires the CURRENT password
> (`current_password:admin`) + the real `Password::min(8)` rule + the single hash+rotate path; **email change is
> RE-VERIFIED** — parked in the new `platform_admins.pending_email`, switched only when a SIGNED expiring link
> (sha1-bound) opened from the new inbox confirms it, current-password required, the old email stays the login and
> **the pending email CANNOT log in until verified** (fail-closed on bad-signature/stale-hash/taken-email); 2FA
> recovery-code regeneration (current-password + audited); every change audited to `platform_admin_actions`.
> Mutation- + browser-proven end-to-end. See `DECISIONS.md` 2026-07-27. **Invariants #1–53.**
>
> **THE ENTIRE BUILDABLE PRODUCT IS COMPLETE (Phases 0–7), and Phase 8's two OBTAINABLE integrations are LIT UP.**
> Three full user-facing apps — operator back office (`/app`), owner portal (`/owner`), guest portal (`/guest`) —
> plus a public storefront (`/book/{slug}`) and public self-signup (`/signup`) that atomically provisions a new
> tenant org on a free trial. On a certified multi-tenant backend (PMS + channel manager + Stripe payments + owner
> accounting + maintenance + unified inbox + notifications/emails + reviews + analytics + tenant provisioning +
> subscription billing + owner sub-tenancy + staff rostering), behind **hardened multi-realm auth** (operator/owner
> `web` guard + a separate `guest` guard; login · self-signup · invites · owner-team invites · password reset ·
> change-password · 2FA/TOTP). MULTI-PROPERTY (`PropertyContext`) · REAL analytics (`PerformanceMetrics`, RevPAR =
> ADR × occupancy) · the SaaS layer (atomic provisioning · subscription billing/paywall · owner team · team schedule).
> **Phase 8 — PRODUCTION EMAIL** (Postmark, `MailSafetyGuard` code-guard): code-complete, Subhan's cutover = DNS +
> token (`docs/EMAIL-PRODUCTION.md`). **Phase 8 — LIVE STRIPE across all three seams** (subscription billing · guest
> card charge · owner payout rail/Connect): TEST-MODE-PROVEN (webhook-source-of-truth, signature-verified against the
> raw body, multi-layer idempotency, no raw PAN/SAQ A, the payout double-transfer-guarded, `StripeSafetyGuard`
> code-guard), Subhan's cutover = the live-key flip (`docs/STRIPE-PRODUCTION.md`).
>
> A **full-system Playwright AUDIT** (`docs/AUDIT-REPORT.md`, all 5 apps, 26 screenshots) found ONE demo-blocker +
> follow-ups, now FIXED (the four unstyled operator controller-pages wrapped in `x-layouts.operator`; the storefront
> rate-seed rolling window; the dev expire-trial button = already-gated false positive; the `/livewire/update`
> owner-capability boundary test — gate holds). Verdict: **DEMO-READY**; customer-ready pending the account-gated tail
> + Subhan's cutovers + deployment.
>
> A **DESIGN-FIDELITY PASS is ✅ COMPLETE — ALL FOUR GATES (2026-07-25)** (`docs/design/DIVERGENCE-REGISTER.md` = the
> per-page source of truth, **76 page-rows**; summary folded into `docs/design/CATALOGUE.md`; dated entry in
> `DECISIONS.md`) — presentation-only, per-page, three-category discipline (✅ KIND-1 layout → FIX · 🚫 KIND-2
> fabricated content → REFUSE, style the honest/empty state · 📐 KIND-3 beyond the presentation boundary → DOCUMENT).
> **GATE 0 done** (shell regrouped to OVERVIEW/FRONT DESK/OPERATIONS/FINANCE/SYSTEM) + the pre-audit 4 pages
> (Dashboard, Reservation Detail, Reservations List, Guests CRM RESTRUCTURED; Rack Gantt + Front Desk = KIND-3
> divergences). **✅ GATE 1 — OPERATOR COMPLETE (14 queue items, `4e5efde`→`6467acb`):** RESTRUCTURED (real KIND-1) =
> Rates · Maintenance ×4 (board KPI band · work-order dark `.vy-tonight` status panel · costs real proportional bars ·
> settings two-column) · Notifications feed (category icons) · Inbox (KPI band) · Reports (real KPI band) · Insights &
> Portfolio (dark-hero → uniform light) · Housekeeping (KPI band). DOCUMENTED divergence (no code) = Units (no
> prototype) · Property Settings · **Roles (editable catalog REFUSED for security #30)** · Profile · **Billing
> (account-gated #41)** · Team Schedule (KIND-3). **✅ GATE 2 — OWNER COMPLETE (25 surfaces):**
> 5 RESTRUCTURED (Bookings + Booking-detail 4-up KPI · **Statement** hero-visibility fix [a real invisible-element
> bug: `vy-swap-next` overrode `.vy-fade` with a no-`forwards` keyframe → stuck `opacity:0`; removed] · **Reports**
> 2-up KPI via a new opt-in `.vy-kpi-grid--2` · **Insights** 4-up KPI), 11 at-parity, 9 intentional-divergence
> (Unit Detail #28 · Rates direct-edit · Payout Method #45 · Add Unit request · Reviews #37 · Team matrix-drift ·
> Settings/Help focused subset · Tax multi-year). **✅ GATE 3 — GUEST COMPLETE (17 surfaces, 0 code):** 15 at-parity,
> 2 divergence (Login hero/social · Signup booking-tied-claim); #28/#33/#34/#36/#37/#45 surfaced honestly in the UX.
> **✅ GATE 4 — PUBLIC + ONBOARDING/BILLING COMPLETE (0 code):** Room detail / Checkout (#45 pay-on-arrival) / Signup
> wizard (#40/#41) / Paywall+trial (#41) / Sub-checkout (#45) / Invite-owner / operator auth = at-parity; Browse +
> Search results = scope divergence (per-property storefront vs multi-property collection). **THE WHOLE PASS WAS
> PRESENTATION-ONLY — 3 owner CSS/Blade fixes total, GATES 3+4 zero code; suite green throughout.** Shared UI
> anti-drift generalised: `.vy-tonight` dark stat panel, count-agnostic `.vy-dash-kpis` (`--3`/`--5`) AND owner
> `.vy-kpi-grid` (`--2`/`--4`). `docs/design/raw/*.html` served via `php -S 127.0.0.1:8899` (gitignored) for diffing.
>
> Suite **924 tests / 7517 assertions** green on MySQL 8; PHPStan L5 = 0; Pint clean; `migrate:fresh --seed` clean;
> Redis/Horizon live. **Invariants #1–#45.** Read order for a fresh session: **PROJECT-STATE → DECISIONS →
> GAP-ANALYSIS → AUDIT-REPORT → design/DIVERGENCE-REGISTER.**
>
> **TAGGED OUTSTANDING REGISTER (honest):**
> - **DESIGN-FIDELITY PASS — ✅ COMPLETE (all 4 gates, 2026-07-25)** (operator · owner · guest · public/billing; 76
>   page-rows in `docs/design/DIVERGENCE-REGISTER.md`, summary in `docs/design/CATALOGUE.md`, dated entry in
>   `DECISIONS.md`). Presentation-only throughout — 3 owner CSS/Blade fixes total, GATES 3+4 zero code; suite green.
> - **GAP-SWEEP + Batch A cheap wins — ✅ DONE (2026-07-25)** (`docs/GAP-SWEEP.md` = the READ-ONLY sweep of all four
>   realms; dated entry in `DECISIONS.md`). Wired the 4 cheapest wins whose backends already existed (LINK/RENDER
>   only, no service/route/money/auth touched): operator nav FILTER — role-gated items hidden instead of a misleading
>   "Coming soon" (`687e234`); the owner **booking-summary · remittance · annual-tax** PDFs now REAL
>   `@ownercan(CAP_EXPORTS)`-gated links (`owner.statements.bookings` / `owner.payouts.remittance` / `owner.tax.annual`
>   — all built+tested; remittance offered only when a payout is matched, annual-tax only for years with committed
>   statements). Browser-verified: 4 PDFs 200/application-pdf; a **view-only sub-user gets 403 on all four (#42)**.
>   Suite **945 / 7644** green; PHPStan L5 = 0; Pint clean; `migrate:fresh --seed` clean.
> - **BATCH B — ANALYTICS — ✅ DONE (2026-07-26)** (`docs/GAP-SWEEP.md` §5; dated entry in `DECISIONS.md`;
>   register rows updated). Three features, all REAL (#39): **(1)** the owner Statement **by-unit split** — pure
>   ledger arithmetic reconciling to the net to the cent (#17), owner-scoped (#28) — `dbf07d3`; **(2)**
>   **per-unit analytics** on My Units + Unit Detail (real revenue · nightly rate · best-performer · 6-month NET ·
>   12-month occupancy · channel mix · a real "Needs your attention" feed) via the shared `OwnerUnitAnalytics` +
>   a new shared `<x-charts.bars>` — `1e1269f`; **(3)** operator + owner **Insights booking cadence** (lead-time
>   distribution + median · day-of-week occupancy · on-the-books) via ONE shared
>   `App\Support\Analytics\BookingCadence` (anti-drift), replacing the "Soon" deferrals. A **flagged, demo-only
>   seed** (`DemoSeeder::seedAnalyticsHistory`, backdated `created_at`) gives the demo a real, meaningful shape
>   (24 reservations, median lead 42 days, buckets 21/21/42/17%); the runtime computes real aggregates from real
>   rows (proven by `BookingCadenceTest`). **REFUSED (#39):** the recommendation/pricing engine + the per-element
>   "vs last year" deltas (no fabricated LY; prior-year demo seed scoped out — needs prior-year availability).
>   **INVARIANT #47.** PHPStan L5 = 0; Pint clean; `migrate:fresh --seed` clean.
> - **BATCH C — OPERATOR OPERATIONS — ✅ DONE (2026-07-26)** (`docs/GAP-SWEEP.md` §5; dated entry in
>   `DECISIONS.md`). Wiring EXISTING guarded services, never raw writes: **housekeeping assign** (a real
>   Housekeeping-dept picker → `HousekeepingService::assign`, `housekeeping.manage` — `a728fcc`); **reservation
>   move** (via `AssignmentService::assignUnit` — same-type free units, lock + no-overlap; proven not a raw
>   write) + **add-charge** (`postCharge` #17) + **the on-desk payment MONEY-GATE** — idempotency (a
>   double-submit records ONCE, mutation-proven both ways; a new UNIQUE `payments.idempotency_key`) + a
>   Cash/Bank/Card selector (card = the deferred Stripe seam, "test-mode") — `b6bd623`. **Correction to the
>   sweep:** on-desk CASH payment was ALREADY built; the real gap was idempotency + move + charge. Settings:
>   **Tax & fees editing** now real (bounded, `properties.manage`); **Invoice-numbering + Branding** scoped to a
>   focused follow-up (honest "Soon" — no invoice-series store yet; branding logo is a larger media surface);
>   Notification routing stays "Soon". **INVARIANT #48.** Suite green; PHPStan L5 = 0; Pint clean.
>   **↳ FOLLOW-UP (2026-07-26): add-charge now ALSO carries a per-submit server nonce** — #48 money-idempotency
>   now covers BOTH money-write routes. Batch-4's UI double-submit-disable was the ONLY guard on `postCharge`;
>   it now mirrors the payment path exactly (nullable-unique `folio_items.idempotency_key` + pre-check +
>   catch-`UniqueConstraintViolationException`; a `charge_nonce` hidden field). A duplicate — even two
>   **concurrent** POSTs bypassing the UI disable — records the charge ONCE. Mutation-proven (same key → 1, no
>   key → 2, distinct → 2) + browser-proven (2 concurrent same-nonce POSTs → 1 `folio_item`). Closes
>   POLISH-SWEEP I3's flag; DECISIONS.md 2026-07-26. Suite **981/981**; PHPStan L5 = 0; Pint clean.
> - **ACCOUNT-GATED remaining** (each a focused prompt as the account lands): Channex live OTA sync · WhatsApp/OTA
>   inbox channels · SMS (waitlist/notifications) · guest ID verification / KYC. (Email + Stripe are DONE — pending
>   only Subhan's cutovers.)
> - **RESIDUAL PLATFORM item** (deferred): the global `Super Admin` role's residual assignability — a Horizon-style
>   platform gate + a spatie-PK rework; a founder must NEVER hold it (provisioning already gives them `Property
>   Owner`, #40).
> - **SUBHAN'S LIVE CUTOVERS (not code):** email DNS (SPF/DKIM/DMARC) + Postmark token + one real send
>   (`docs/EMAIL-PRODUCTION.md`); Stripe go-live (activate · live Prices · both webhook endpoints · flip ENV · $1
>   smoke-test → refund, `docs/STRIPE-PRODUCTION.md`).
> - **DEPLOYMENT (not a build):** runs locally on XAMPP — needs production hosting/domain/HTTPS/MySQL/Redis-Horizon.
>
> ---
> *Historical one-paragraph-answer (pre-7c; kept for the arc — trust the CURRENT STATE block above where they differ):*

> **THE ONE-PARAGRAPH ANSWER.** The product is **COMPLETE, DEEP, DEMOABLE end-to-end, and safe for a real owner
> login**: a certified multi-tenant backend (PMS + channel manager + payments + owner accounting + maintenance
> + unified inbox), a **fully hardened auth chapter** (login · invites · password reset · change-password ·
> **2FA/TOTP**), and **~68 styled, browser-verified screens** across the owner portal, the operator back office,
> the public booking storefront, the **COMPLETE guest portal** (Phase 4b/4c) and the **Reviews module** (Phase 5:
> Guest Review · Operator Reviews · Owner Reviews · storefront ratings/reviews) — all on real scoped data under the
> honesty rule. **Five security/integrity
> holes have been found and closed this session, none of them by a failing test:** the `/app` authorization leak
> (**#26**), the cumulative over-refund (**#27**), the missing GDPR-erasure trigger (**#28**), the unguarded
> re-termination (**#29**), and a **privilege escalation** — any back-office role could invite itself an
> Accountant and inherit `payments.refund` — closed by enforcing the permission catalog (**#30**). **The
> security/integrity register is CLEAR**, so every screen inherits correct enforcement instead of needing a
> retrofit. What remains is **subsystems/epics** (real commitments, not quick wins), **speculative** (Tier 2 —
> designed, no backend, no signal), or **account-gated** (needs someone else's credentials), plus the small open
> items in the register. **The buildable-in-a-vacuum, clearly-worth-it core is essentially complete — 2FA was the
> last such subsystem.** ⚠️ **UPDATE (2026-07-20): the customer signal ARRIVED** — a warm operator contact
> (Fatima) liked the demo AND the prototype, so the requirement is now **"make the real product work like the
> prototype"** (the "no customer has reacted / don't build blind" framing that once sat here is SUPERSEDED — see
> the NEXT ACTION section at the foot of this file). **Phases 0, 1, 2, 3, 4a, 4b, 4c, 5, 6 (a+b), 7a AND 7b of
> `docs/GAP-ANALYSIS.md` are DONE — Guest Portal + Reviews complete, MULTI-PROPERTY, real ANALYTICS, public
> SELF-SIGNUP, AND SUBSCRIPTION BILLING (the free-trial model); Phase 7c (Owner Team) is next.** Phase 7b = BILLING:
> plans/tiers (Essential/Pro, PLACEHOLDER prices — Subhan sets real ones) + `App\Support\Billing\SubscriptionStatus`
> (the ONE derivation — trial/active/lapsed, **fails OPEN** on a missing record) + the REAL trial started atomically
> in 7a provisioning + the Paywall + Checkout (company/VAT, period toggle) + access-gating (`EnsureSubscriptionActive`:
> lapsed → paywall, NEVER mid-trial) + feature-gating (`Organization::planAllows` — channels Pro-only). **🪤 THE LIVE
> CHARGE + Stripe webhooks are ACCOUNT-GATED** (deferred, same as the guest card charge / owner payout rail): the
> subscribe action takes no money, stores no card, never flips an org to active (`stripe_subscription_id` null) —
> invariant **#41**. Phase 7a = TENANT
> PROVISIONING: `App\Services\TenantProvisioningService` atomically creates a new operator org (org + provisioned
> roles + founder + `Property Owner` + first property in ONE transaction) — the chicken-and-egg escaped via the
> pre-tenant `TenantContext::runAsSuperAdmin` path (`Organization::create`'s first `app/` caller;
> `OrganizationRoleProvisioner::provision()`'s first real caller) — plus the public `/signup` onboarding wizard
> (rate-limited, neutral enumeration, founder auto-logged-in, honest "finish setup"). **BOTH deferred fail-closed
> RBAC defects FIXED, mutation-proven:** org-creation now provisions roles (#1); the founder holds the ASSIGNABLE
> `Property Owner`, never the global `Super Admin` (#2 self-provision) — invariant **#40**. Phase 6b = ANALYTICS: the shared `App\Support\Analytics\PerformanceMetrics` +
> `MetricSet` metric layer (ADR/RevPAR/occupancy/room revenue, stay-night attribution, **RevPAR = ADR × occupancy
> exactly**, reconciling to `FolioTotals` + the availability spine; scopes property/all/owner-units) → operator
> Insights + Reports (`reports.view`, per-property + all-properties) → owner Reports + Insights (owner-scoped,
> **earnings reconcile to the owner ledger**) → the real Portfolio comparison. **Every figure a REAL aggregate; the
> prototype's invented history/narrative REFUSED; a thin period an honest empty state; lead-time/pace deferred "Soon"
> — invariant #39.** 3 gated sub-gates, mutation-proven (reconciliation + owner isolation), the fabrication-absence
> tested. Phase 6a = MULTI-PROPERTY: a per-user session-backed `PropertyContext` + a topbar
> switcher + the COMPLETE `Property::…->first()` refactor (Tier A 10 sites/8 files + Tier B 5 blend-risk list screens,
> grep-clean — a `->first()` silently returns an ARBITRARY property once an org runs several, so this was a genuine
> wrong-property bug) + property CRUD (`properties.manage`, org-scoped) + the Portfolio overview (real per-property
> data; ADR/RevPAR deferred to 6b). Property access = the `property_user` **pivot-or-all** allow-list (empty ⇒ full
> org — invariant **#38**); `#[Locked]`+mount-capture (`ResolvesCurrentProperty`) fixes the Livewire `/livewire/update`
> boundary. Mutation-proven the selected context governs (2 gated steps). Phase 5 = the REVIEWS module across five surfaces: Guest Review (tied to a real completed stay,
> one per stay — **#36**), moderation (only published+public is public; rating DERIVED, never fabricated — **#37**),
> Operator Reviews (respond + moderate, `reviews.manage`, new-review notification wired), Owner Reviews
> (owner-scoped read), and the public storefront (derived rating + published reviews, #28 byline) — 4 gated
> sub-gates, pushed. Phase 4c = guest money +
> engagement: Payments + Folio Receipt + five money PDFs (card charge account-gated), the Wallet/credits ledger
> (append-only, balance DERIVED, no-overdraw — **invariant #34**), Guest Messages (the Unified Inbox extended, third
> participant surface — **invariant #35**), and Wishlist + Waitlist (real self-only stores; notify-trigger/auto-hold
> deferred honestly) — 4 gated sub-gates, pushed. Phase 4b = the GUEST
> PORTAL CORE on the 4a foundation — Trips · Reservation detail · Manage Booking (operator-confirmed requests,
> **#32**) · Online Check-in (a guest pre-check-in intent, NOT the operator's authoritative check-in) · Arrival
> Info (door code + wifi, isolated to the owned booking AND released only within the arrival window, **#33**);
> every screen guest-isolated + mutation-proven, 4 gated sub-gates, pushed. A 500 the green suite masked (operator
> confirm route-model-bound a scoped model that resolved pre-tenant) was found by opening it in a browser and
> fixed + test-hardened. Phase 4a = the GUEST AUTH FOUNDATION — a
> SECOND auth realm for guests (Guest Authenticatable on a separate `guest` guard + a blind-index email lookup,
> booking-tied claim + email verification, guest login + reset hardened, Guest Account + guest isolation), realm
> separation + guest isolation mutation-proven, the operator/owner auth untouched — 4 gated sub-gates, pushed.
> Phase 3 = the notification subsystem (in-app feeds +
> typed preferences that GENUINELY gate on both channels) + the wired real events + all guest transactional emails
> (Mailpit-real) + owner/operator email delivery + the Email Templates view — 5 gated sub-gates, pushed.

- **On `main`, working tree clean, local == origin** (HEAD advances by one with the doc commit that carries these
  words; check `git log` for the true HEAD — see the HEAD convention below). Only the
  untracked design-source exports under `docs/design/raw/` stay uncommitted — **gitignored** raw Claude Design
  HTML, **not in a fresh clone**. Suite: **`php artisan test` → 869 tests / 7233 assertions, all green** on
  genuine **MySQL 8** (`vyrobel_test`) — re-verified for this capstone. ✅ **BOTH flaky tests are CLOSED**
  (root-caused 2026-07-17 — and they were the same disease: an assertion whose needle was too generic, never a
  product bug. See the register). The suite is a trustworthy gate.
  **PHPStan (Larastan) level 5, 0 errors** (`--memory-limit=1G`) — re-verified. **Pint clean** — re-verified.
  ⚠️ `phpunit.xml` sets **`memory_limit=512M`** — the suite outgrew PHP's 128M CLI default (verified headroom,
  not a leak; see the register). `php artisan test` spawns a child that re-reads php.ini, so `-d` won't reach it.
  Queue = **Redis/Horizon live** (`QUEUE_CONNECTION=redis`, `REDIS_CLIENT=predis`; `database` documented as
  fallback). The three planning docs (`docs/ROADMAP.md`, `docs/TIER1-SPEC.md`, `docs/DESIGN-COVERAGE.md`) live
  on `main`.
- ⚠️ **THE HEAD CONVENTION.** These docs are written and committed *before* the commit that contains them, so
  a "HEAD" named in any entry is the commit **one behind** the one that carries the words. The consolidation
  entry naming `e160eeb` ships in the commit *after* `e160eeb`. Check `git log` for the true HEAD; never treat
  a HEAD quoted in prose as current.
- **LATEST — the AUTH CHAPTER is DONE (2026-07-18).** After Wave 4 Core came two auth gates: **CHANGE PASSWORD**
  (`e6978d8`, operator `/app/security` + owner `/owner/settings`, current-password-verified, reusing the one
  shared `User::changePasswordTo()` hash+rotate path) and **2FA / TOTP** (`031716a` enrollment + recovery codes
  → `aca017c` login challenge → `11288d6` consolidation) — opt-in per user, secrets/codes **encrypted** (fixing
  a pre-existing plaintext `two_factor_secret`), the pending state grants no auth **by construction** (**#31**).
  `google2fa` + `bacon-qr-code`, not Fortify; browser-verified both portals. **2FA was the last buildable-in-a-
  vacuum subsystem.** See `docs/DECISIONS.md` 2026-07-18 + `docs/AUTH-INVENTORY.md`.
- **PRIOR PHASE: WAVE 4 CORE COMPLETE (2026-07-17)** — the three genuinely-buildable Wave 4 screens, three
  gated sub-gates (`8971d20` **Operator Rates** per-room-type over the certified `setRate`/`setRestriction` →
  `6e3b717` **New Reservation** guarded `createBooking` + 3 guards [honest per-unit availability via the new
  `UnitAvailabilityService`, PII name-only, NO raw PAN] → HEAD **backed Settings** [`/app/settings` +
  `/owner/settings`; first `app/` writes of properties/property_policies/organizations config; owner self-edit
  `user_id`-guarded]). Shared `App\Support\Money\MoneyInput` (one typed-money parser). No new permission —
  every gate reused an existing catalog permission. **DEFERRED as subsystems/account-gated** (Portfolio ·
  Owner Team · Vyrobel Onboarding · Group Booking · Owner Rates · Channels-as-drawn · Roles · Stripe card
  tokenization) — per-screen readiness + the trap register live in `docs/WAVE4-INVENTORY.md`. **Prior — WAVE 3
  COMPLETE (2026-07-17):** the owner inbox. **Prior — WAVE 2 COMPLETE:** five gated sub-gates, a commit each
  (`4eb71e2` → `cb8bcce` → `44444e6` → `f86cf4e` → HEAD): **Reservations list · Cancelled/No-show terminal
  states · the Refund screen · the Guests CRM list · the Guest profile.** Suite **430/4835 → 511 tests / 5503
  assertions**. Unlike Wave 1 this was **not** presentation-only — Sub-gate 5 adds the first guest write path
  (`GuestService`), because the design's "Save changes" persisted nothing and a fake save is not a screen.
  Three shared extractions prevent screen-to-screen drift: **`ReservationStage`** (badge + SQL in one class),
  **`GuestSpend`** (the BILLED definition, reconciling to the ledger by construction) and **`GuestHistory`**
  (stays · nights · "guest since" — extracted because the inbox had ALREADY shipped a different rule and said
  *"2 stays · guest since 2024"* where the CRM said *"1 stay · guest since 2026"*). **⚠️ Read the WAVE 2
  OUTSTANDING REGISTER in the deferred register below — four real items, deliberately not fixed.**
  See `docs/DECISIONS.md` 2026-07-17 (two entries).
- **(historical) WAVE 1 COMPLETE + the `/app` AUTHORIZATION HOLE CLOSED (2026-07-16).**
  Wave 1 shipped as four gated sub-gates (`55c9219` → `5530f6b` → `9ff2ad9` → `4d68e8c`): **10 new screens/
  documents over the CERTIFIED owner-accounting backend, presentation only** (⚠️ the original prose said "9"
  — an arithmetic slip, not a change in the facts; the Wave 1 tables below have always listed **10**: owner
  Balance · Expenses · Agreement · Archive · Unit Detail · Documents + 2 PDFs, and operator Owner Detail +
  Payout Run. Corrected 2026-07-17.) (Mechanically verified at every
  gate: **ZERO** files changed under `app/Services`, `app/Models`, `database/migrations`, `app/Payouts`).
  Browser-verifying it then surfaced a **critical pre-existing authorization hole** (an owner-role user could
  reach the whole operator back office and every other owner's financials), now **FIXED in two gated steps**
  (`5155a48` route gate → `f63866a` root cause) — **invariant #26**, defense-in-depth, each layer proven to
  block independently. Suite **340/3831 → 430 tests / 4835 assertions** (Wave 1 + the `/app` gate + the refund bound #27).
  **The product is now safe for a real owner login.**
- **AUTH — COMPLETE & SECURE (chapter finished): login/logout + invites (owner + staff) + password reset +
  in-app change-password + 2FA (TOTP).** **Change password** (`e6978d8`, operator `/app/security` behind #26
  only so every back-office role reaches it · owner card on `/owner/settings`): `auth()`-resolved (never a
  request id), **current-password-verified** (anti-hijack), reusing the ONE shared `User::changePasswordTo()`
  (hash via the 'hashed' cast + rotate remember-token) that reset was refactored onto so they can't drift.
  **2FA (TOTP, `031716a`→`aca017c`→`11288d6`):** enrollment (confirm-before-activate) + one-time recovery codes
  (single-use, `hash_equals`) + a login challenge slotted between password and session (`Auth::validate` →
  challenge → `Auth::login`; non-2FA login unchanged), secrets/codes **encrypted at rest** (fixing the
  pre-existing plaintext `two_factor_secret`), disable-requires-re-auth, self-only, rate-limited — **invariant
  #31** (`TwoFactorService` / `TwoFactorChallengeController` / `RequireTwoFactorPending`; `google2fa` +
  `bacon-qr-code`, NOT Fortify). Real session auth fronts the app; **the dev-login shim is gone.** Phase A: one shared login
  (operator+owner), active-only `Auth::attempt`, per-(email,ip) rate limit, generic non-enumerating errors,
  session regeneration, role-routing (`/app` vs `/owner`), `/book` public. Phase B: an operator invites via a
  **sha256-hashed, single-use, 7-day, revocable** tokened email link → the invitee sets a **hashed**
  password → activates → auto-login. **Three roles on one secure path:** OWNER → owner link + units +
  agreement → `/owner`; **STAFF (Hotel Manager / Accountant)** → Membership + a **real, fail-safe-validated**
  spatie catalog role → `/app`. **Password reset** on Laravel's Password broker — same posture (no
  enumeration, active-only, tokened single-use expiring, hashed, remember-token rotated, session regenerated,
  rate-limited). Proven by `PhaseBEndToEndTest` + `StaffInviteTest` + `PasswordResetTest`. **Dev email wired
  to Mailpit** (invite + reset emails land in a viewable inbox); expired invites are pruned daily
  (`invites:prune-expired`). **DEFERRED, flagged not faked:** **email DELIVERY in production** (dev = Mailpit;
  needs a real provider). **2FA is now BUILT** (see the auth bullet above / DECISIONS.md 2026-07-18). Remaining
  BLOCKED auth subsystems: self-signup/tenant-provisioning, guest auth, sessions/connected-apps/IP-rules. See
  `docs/DECISIONS.md` 2026-07-13 + `docs/AUTH-INVENTORY.md`.
- **DESIGN PASS — the original 17-screen pass is COMPLETE, and Waves 1–4 + auth have since taken it to ~38:**
  **owner portal (4 + Wave 1's 6 + 2 PDFs + Wave 3's Messages + Wave 4's Settings)** · **operator core (5) +
  Units (1) + Maintenance board & work order (2) + Inbox list & conversation (2) + Wave 1's Owner Detail &
  Payout Run (2) + Wave 2's Reservations list · Cancelled/No-show · Refund screen · Guests list · Guest profile
  (5) + Wave 4's Rates · New Reservation · Settings (3) + the `/app/security` change-password/2FA page (1)** ·
  **public booking storefront (3, incl. the real checkout WRITE)** · plus the auth screens (login · reset ·
  set-password · forgot · **2FA challenge**). The per-wave inventories below are authoritative; this line is the count. All on the certified backends, honesty rule enforced per-screen by a render/
  isolation test; **backends/schema/guarantees UNCHANGED** (presentation only). **No unstyled stubs
  remain.** Three role design systems: `components/layouts/owner`+`<x-owner.shell>`+`FormatsOwnerData` ·
  `components/layouts/operator`+`<x-operator.shell>`+`FormatsOperatorData` · `components/public/shell`
  (server-rendered, no Livewire). Operator writes use **POST→controller→service-action→PRG**; the public
  checkout write is session-CSRF-protected and goes through `AssignmentService`/`FolioService`.
- **DEMO-READINESS POLISH — DONE (2026-07-14).** **Room photos:** real operator uploads on the shared
  `media` table (secure upload — content-validated images, ULID filenames, size cap, tenant-scoped),
  rendered on the storefront (cover + gallery) + unit cards with the **gradient fallback kept**; storage =
  local `public` disk in dev (**S3 for prod, flagged**). **PDF export:** the **owner statement**
  (owner-scoped) and the **operator folio** (tenant-scoped) download as branded, PDF-safe documents
  (`barryvdh/laravel-dompdf`) that **reconcile to the on-screen figures to the cent** (shared
  `MoneyFormatter` + `FolioTotals`), no invented lines. Still deferred: descriptions/reviews, CSV export,
  remittance/booking-summary PDFs. See `docs/DECISIONS.md` 2026-07-14.
- **MAINTENANCE MODULE — BUILT & CERTIFIED (2026-07-14).** Maintenance tickets on units
  (`MaintenanceService`, mirrors HousekeepingService — guarded lifecycle open→in_progress→resolved/cancelled)
  with the TWO integrations that make it a real Vyrobel module: **(1) out-of-service ↔ availability** — an
  OOS ticket flips the unit to `out_of_service` and emits the EXISTING `unit.condition_changed` event → the
  existing recompute listener excludes it (AssignmentService/AvailabilityService UNTOUCHED; sold never
  touched; two-tickets + never-auto-cancel + honest negative-over-committed signal handled); **(2) cost →
  owner deductible expense** — a costed ticket on an owned unit posts via the CERTIFIED
  `OwnerExpenseService::recordExpense(source:$ticket)` → owner_ledger → statement (operator-owned = no
  expense; correction reverses append-only; period-lock respected). **STYLED UI COMPLETE (design pass
  2026-07-14):** the **board** (`/app/maintenance` — kanban by real status; "Unit OOS" badge + OOS rail;
  real New-ticket + Assign writes) and the **work order** (`/app/maintenance/{ticket}` — real facts +
  audit-event timeline; state-gated guarded lifecycle writes; **both integrations surfaced** — the OOS
  callout with the **manual-move flag** (real active-booking count on the blocked unit) and the cost card
  read from the real `owner_expense` (charged-to-owner vs operator-cost)). `DemoSeeder` seeds a coherent
  maintenance spread through `MaintenanceService`. See `docs/schema/maintenance.md` + `docs/DECISIONS.md`
  2026-07-14 (two entries).
- **UNIFIED INBOX — COMPLETE end-to-end (backend + styled UI, 2026-07-14).** Guest-messaging inbox (threads/messages linked to real
  guests/reservations), built with the **interface + fake + real** pattern: the **manual channel works
  end-to-end now**; the **real email/WhatsApp/OTA adapters are config-gated skeletons that throw "not
  configured"** (account-gated, never faked). `message_threads` + `messages` (tenant-scoped, morph-mapped);
  **inbound idempotency** via `unique(org, channel, external_message_id)` + createOrFirst catch-the-race
  (proven under TRUE OS-level concurrency); `InboxService` single write path; `App\Messaging` (MessageChannel
  interface + ManualChannel + Deferred skeletons + resolver, mirrors `App\Payouts`); inbound rides the
  reliability backbone (`ProcessInboundMessageJob`). Guest linking **REUSES `App\Services\GuestMatcher`** (the
  same matcher OTA import uses — no parallel matcher; hashed non-PII ref, encrypted address); threading GROUPS
  by ref or a participant-hash anchor (stranger case included); `Guest::anonymize()` **redacts the guest's
  conversation** (GDPR). **STYLED UI COMPLETE (design pass 2026-07-14):** the **thread list** (`/app/inbox` —
  guest name + real channel badge + preview + unread + linked-reservation tag; Alpine filters/chips over real
  rows) and the **conversation** (`/app/inbox/{thread}` — cream/terracotta bubbles, real delivery states incl.
  the honest FAILED; a REAL reply over the thread's channel, deferred → honest "not connected" composer; real
  Assign/Close/Snooze/Reopen; context sidebar with a DERIVED guest stay-stat, the reservation card + link).
  **Inbox is now in the operator nav.** Guest NAME + channel shown; encrypted identity never rendered.
  `DemoSeeder` seeds three real conversations. See `docs/schema/inbox.md` + `docs/DECISIONS.md` 2026-07-14 (three entries).
- **THE CORE PRODUCT IS COMPLETE & DEMOABLE end-to-end** — a real hybrid operator could run day-to-day
  operations (calendar/front-desk/housekeeping/maintenance/folio/dashboard/units/**inbox**), message a guest
  and see delivery states, an owner could read their statements/payouts/units + download a PDF, and a guest
  could book direct (browse→room→checkout→confirmation, a real guarded write) — the storefront shows real
  photos. **The buildable-in-a-vacuum core is essentially complete; what remains is account-gated or net-new
  speculative — see "NEXT ACTION".**
- **Phase 1 CERTIFIED** (multi-tenant core) · **Phase 2 CERTIFIED** (channel manager + payments +
  booking engine + staff layer; **Stripe validated against real test-mode 2026-07-03**) · **Phase 3 ·
  Module 1 — Owner Accounting & Payouts CERTIFIED** (2026-07-03).
- **Owner Accounting commit chain (per-gate):** `e9ccce0` (Step 0 schema/decisions) → `34f7e81`
  (Step 1 owners+agreements) → `b3be74a` (Step 2 attribution engine) → `222cef0` (Step 3 expenses) →
  `863c514` (Step 4 statements) → `bf05b04` (Step 5 payouts) → `079ae0e` (Step 6 portal+isolation) →
  `af25e6b` (Step 7 CERTIFIED).
- **OWNER PORTAL DESIGN PASS — COMPLETE (all 4 screens styled + live).** Presentation only, **no
  backend/schema/guarantee change.** Owner Statement, Dashboard, Payouts, and My Units are now styled and
  wired to real owner-scoped data behind the `components/layouts/owner` design system, `<x-owner.shell>`,
  and the `App\Livewire\Owner\Concerns\FormatsOwnerData` trait. Commit chain: `970b0e7` (Screen 1
  Statement) → `8f76dbd` (Screen 2 Dashboard) → `a8f7b77` (Screen 3 Payouts) → `bff8e05` (Screen 4 My
  Units). The **design-pass honesty rule is locked in `docs/DECISIONS.md`** (2026-07-11) and enforced by
  per-screen render/isolation tests. See "Owner portal — STYLED & LIVE" below.
- **OPERATOR DESIGN PASS — OPERATOR CORE COMPLETE (Screens 1–5 styled + live).** Presentation only, **no
  backend/schema/guarantee change.** A dedicated operator design system was extracted (mirrors the owner one):
  `components/layouts/operator` (same tokens; copied, not shared — owner layout untouched),
  `<x-operator.shell>` (grouped sidebar + topbar, extracted on Screen 1), and
  `App\Livewire\Operator\Concerns\FormatsOperatorData` (viewer + `money()` + **warm-only** status/source/
  housekeeping/priority/folio labels keyed to the real enums). Operator screens are **TENANT-scoped**
  (not owner-scoped); guest NAMES shown (staff surface), encrypted email/phone never. DONE: **Screen 1
  Calendar/Rack** `0e1ccaa` · **Screen 2 Front Desk** `39724e6` · **Screen 3 Housekeeping** `d715496` ·
  **Screen 4 Reservation Detail** `b49676d` · **Screen 5 Operator Dashboard** `434e1a9` (a NEW page —
  route `/app/dashboard`). Screens 2–4 **wire real mutations** via the reusable **operator-mutation
  pattern** — **POST → controller → existing service action → PRG redirect** (tenant-scoped by re-run
  middleware; `{id}` string param → `findOrFail` → cross-org 404; append-only preserved for money):
  check-in/out (Front Desk), status advance (Housekeeping), record-payment + full refund (Reservation
  Detail). Screen 5 is read-only; every KPI is a TRUE org figure (occupancy summed org-wide from the
  Availability spine — not a mislabeled per-type number). See "Operator screens — STYLED & LIVE" below.
- **⚠️ TWO live-integration checkpoints OUTSTANDING** (deferred money/network rails — everything else
  is real + tested): **Channex staging** and **Stripe Connect payout rail** (see Deferred register).

## What's built (one line per area — names the key service + where it lives)

- **Tenancy / RBAC** — `App\Models\Concerns\BelongsToOrganization` (global scope + auto-fill + guard)
  via `App\Support\Tenancy\TenantContext`; spatie teams (team = org), 14-role catalog
  (`config/authorization.php`). `SetTenantForWebRequest` middleware sets context per web request.
- **Property & inventory** — mixed hotel+apartment+villa under one property; polymorphic
  `media`/`documents`/`notes`/`tags`/`amenities` reused via `app/Models/Concerns/*`.
- **Rates & availability** — two-level; `App\Services\AvailabilityService` (recompute total/ooo/blocked,
  read `available`) — a collaborator of AssignmentService, never a second write path.
  `App\Services\RateAndRestrictionService` (price/restriction edits emit domain events).
- **Guests** — encrypted `email`/`phone`; `Guest::anonymize()` (GDPR: null PII + purge file bytes).
- **Reservations** — ONE `reservations` table, `source` distinguishes channel;
  **`App\Services\AssignmentService`** is the single guarded inventory write path (book/assign/move/
  extend/split/cancel/no-show/block/importBooking).
- **Folios / payments / tax** — `App\Services\FolioService` (append-only ledger, correct-by-reversal);
  `App\Payments\` — `PaymentGateway` interface + `ManualPaymentGateway` + **`StripeGateway`** (behind
  `Stripe/StripeClientInterface`, fake + real; **validated live test-mode**).
- **Channel manager (Channex)** — `App\Services\Channex\` (`ChannelManagerClient` + Fake/real client,
  ARI snapshot builder, webhook signature); `App\Jobs\Sync\` (RetryableSyncJob, AriPushJob,
  ProcessChannexWebhookJob); `App\Services\ChannelMappingGuard`; overbooking = accept-and-flag.
- **Public booking engine** — `App\Services\BookingEngineService` (search/quote/book/expire),
  `booking_quotes` advisory holds, `ResolvePublicProperty` middleware, `/book/{property}/*` (rate-limited).
- **Staff action layer** — `App\Services\FrontDeskService` (thin delegation) +
  `App\Actions\Reservations\CheckIn`/`CheckOut`.
- **Maintenance module** — `maintenance_tickets` (`App\Models\MaintenanceTicket`, morph `maintenance_ticket`)
  + **`App\Services\MaintenanceService`** (guarded lifecycle; `setUnitCondition` emits the existing
  `unit.condition_changed` → recompute excludes an out-of-service unit; `resolve`/`correctCost` post/reverse
  the cost via `OwnerExpenseService` on owned units). **Styled** operator screens: `App\Livewire\MaintenanceList`
  (board) + `App\Livewire\MaintenanceTicketDetail` (work order) + `App\Http\Controllers\Operator\MaintenanceActionController`
  (POST store/assign/start/resolve/cancel → PRG). Design: `docs/schema/maintenance.md`.
- **Unified Inbox module** — `message_threads` + `messages` (`App\Models\MessageThread`/`Message`, morph
  `message_thread`/`message`; tenant-scoped) + **`App\Services\InboxService`** (single write path: startThread/
  postInbound[idempotent]/sendOutbound/assign|close|snooze|reopen/receiveInbound). Channel seam **`App\Messaging`**
  (`Contracts\MessageChannel` + `Channels\ManualChannel` [real] + `DeferredChannel` skeletons + `MessageChannelResolver`)
  mirrors `App\Payouts`. Inbound on the backbone: `App\Jobs\Sync\ProcessInboundMessageJob`. Guest linking reuses
  **`App\Services\GuestMatcher`** (shared with `BookingImportService`). **STYLED** operator screens:
  `App\Livewire\InboxList` (thread list) + `App\Livewire\InboxThread` (conversation) +
  `App\Http\Controllers\Operator\InboxActionController` (POST reply/assign/close/reopen/snooze/link-reservation
  → PRG); `channelMeta()`/`channelIsConnected()` on `FormatsOperatorData`. In the operator nav. Design:
  `docs/schema/inbox.md`.
- **Owner Accounting (Phase 3 · Module 1)** — `owners` + `management_agreements` (effective-dated,
  `ManagementAgreement::resolveFor()`); **`App\Services\OwnerAttributionService`** (reads folio+
  reservation_units → immutable `owner_ledger`); **`App\Services\OwnerExpenseService`** (`owner_expenses`
  → deduction lines); **`App\Services\OwnerStatementService`** + `App\Services\PeriodLockGuard`
  (`owner_statements`/`owner_statement_lines`, period-locked projection); **`App\Services\OwnerPayoutService`**
  + `App\Payouts\` (`PayoutRail`/`ManualPayoutRail`/`StripeConnectPayoutRail` skeleton, `payouts`);
  **owner isolation** via `App\Support\Owner\OwnerContext` + `App\Models\Scopes\OwnerScope` +
  `App\Http\Middleware\SetOwnerScopeForPortal`; portal reads via `App\Services\OwnerPortalService`.
- **Auth (Phase A + B)** — `App\Http\Controllers\Auth\LoginController` + `LoginRequest` (shared
  operator/owner session login: active-only, rate-limited, non-enumerating, role-routing) + styled
  `auth/login`; **invite flow (owner + staff)** = `App\Services\OwnerInviteService` (sha256-hashed
  single-use expiring tokens; `owner_invites` table + `App\Models\OwnerInvite`, `owner_id` nullable) →
  `App\Mail\OwnerInviteMail` (email-safe, role-aware copy) → `App\Http\Controllers\Auth\AcceptInviteController`
  (public `/invite/{token}` set-password → activate → auto-login, redirect by role) ← operator side
  `App\Http\Controllers\Operator\InviteOwnerController` + `App\Livewire\Operator\OperatorOwners`
  (`/app/owners` + role-aware invite modal). **Three roles:** owner (owner link + `/owner`) · Hotel Manager
  · Accountant (Membership + real spatie role → `/app`); staff role-map + fail-safe live in the service.
  **Password reset** = `App\Http\Controllers\Auth\PasswordResetController` + `App\Mail\PasswordResetMail`
  (Laravel Password broker; `auth/forgot-password` + `auth/reset-password`; shared
  `App\Http\Controllers\Auth\Concerns\RedirectsByRole`). **Expired-invite prune** =
  `App\Console\Commands\PruneExpiredInvites` → `OwnerInviteService::pruneExpired()` (scheduled daily).
  Email delivery = Mailpit in dev (prod provider deferred).
- **Auth — change-password + 2FA (this session).** **Change password:** `App\Http\Controllers\Auth\ChangePasswordController`
  (operator + owner → one shared `change()`), the ONE shared `App\Models\User::changePasswordTo()` (hash +
  remember-token rotate — reset refactored onto it), operator page `App\Livewire\Operator\OperatorSecurity`
  (`/app/security`, behind #26 only), owner card on `/owner/settings`. **2FA (TOTP):**
  `App\Support\TwoFactor\TwoFactorService` (google2fa + bacon-qr-code — enroll/confirm/recovery-codes/verify/
  verifyChallenge/consumeRecoveryCode/disable), `App\Http\Controllers\Auth\TwoFactorController` (enable/confirm/
  regenerate/disable, self-only) + `TwoFactorChallengeController` (the login challenge) +
  `App\Http\Middleware\RequireTwoFactorPending` (fences the challenge; the pending marker is a session key, NOT
  the guard — **#31**); shared Livewire `App\Livewire\Concerns\PreparesTwoFactor` + partial across both Security
  panels; `users.two_factor_secret`/`two_factor_recovery_codes` **encrypted** casts;
  `LoginRequest`/`LoginController` split `Auth::validate` → challenge → `Auth::login`.
- **Wave 2–4 operator/owner screen services + shared extractions.** First guest write path
  **`App\Services\GuestService`** (Wave 2). Wave 4: **`App\Services\UnitAvailabilityService`** (honest per-unit
  availability for New Reservation) + the Rates grid as the first production caller of `setRate`/`setRestriction`.
  Drift-proof shared definitions (each with a proof-it's-shared test): **`App\Support\Reservations\ReservationStage`**
  (stage badge + SQL filter in one class), **`App\Support\Guest\GuestSpend`** (the BILLED lifetime-spend
  definition, reconciles to the ledger by construction), **`App\Support\Guest\GuestHistory`** (stays · nights ·
  "guest since"), **`App\Support\Money\MoneyInput`** (one typed-money parser), and the Blade-safety guard
  `assertNoRawBlade()` (tests/Support). Owner inbox: `message_threads.owner_id` + `BelongsToOwner` (Wave 3).
- **Photos** — shared `media` table (collection `photos`) via `App\Models\Concerns\HasPhotos` on RoomType;
  operator upload/reorder/cover/remove through `App\Http\Controllers\Operator\RoomTypePhotoController`
  (secure: content-validated images, ULID filenames, size cap, tenant-scoped); `public` disk (S3 for prod).
  Rendered on the storefront + unit/owner cards with a gradient fallback. `App\Support\Media\PlaceholderImage`
  seeds real demo JPEGs.
- **Shared presentation helpers (Wave 1) — "a document and the screen it mirrors must never disagree".**
  Each exists because the same concept was about to live in two places and drift:
  **`App\Support\Payouts\RailLabel`** — the ONE rail→label map (owner Payouts screen + Remittance PDF +
  Payout Run). An unknown rail **THROWS** rather than prettifying the column into a fabricated payment
  method; PHPStan enforces exhaustiveness. **`App\Support\Owner\OwnerOccupancy`** — the ONE owner-scoped
  occupancy derivation (My Units + Unit Detail), extracted behaviour-preservingly from the certified
  `OwnerMyUnits::occupancy()`. **Keyed on PHYSICAL UNIT ID, never room type** — `availabilityRange()` /
  `OwnerPortalService::occupancy()` are room-type-wide and a room type is shared with hotel rooms and OTHER
  owners' units, so reading occupancy that way shows an owner a number driven by units they do not own.
  Same family as `Support\Money\MoneyFormatter` + `Support\Folio\FolioTotals`.
- **PDF export** — `App\Services\PdfService` (barryvdh/laravel-dompdf) renders branded, PDF-safe documents:
  **owner statement** (`App\Http\Controllers\Owner\OwnerStatementPdfController` + `Support\Pdf\OwnerStatementDocument`,
  owner-scoped) · **operator folio** (`App\Http\Controllers\Operator\FolioPdfController` + `Support\Pdf\FolioDocument`,
  tenant-scoped). Both reconcile to the screen to the cent via the shared `Support\Money\MoneyFormatter` +
  `Support\Folio\FolioTotals`.
- **Multi-realm auth (operator/owner `web` + a separate `guest` guard)** — the operator/owner chapter (login ·
  invites [owner/Hotel Manager/Accountant] · password reset · change-password via `User::changePasswordTo` ·
  **2FA/TOTP** [`google2fa` + `bacon-qr-code`, enrollment + recovery codes + login challenge, secrets encrypted]);
  and the **guest realm** (Phase 4a) — `App\Auth\GuestUserProvider` on a separate `guest` guard (`config/auth.php`),
  a keyed **blind index** (`guests.email_hash`) since the encrypted email isn't queryable, booking-tied claim +
  email verification, `Guest::changePasswordTo`. `App\Http\Middleware\SetTenantForGuestRequest` +
  `App\Http\Controllers\Guest\Concerns\ResolvesGuest` (the single guest-resolution point). Realm separation +
  guest isolation mutation-proven; the operator/owner chapter unregressed.
- **Notification subsystem (Phase 3)** — `notifications` table (Laravel-native `Notifiable`) + the
  `App\Listeners\DispatchNotificationsForDomainEvent` listener (real `DomainEventRecorded` events → per-user feed,
  isolation-scoped + double-fire idempotency) + a TYPED `App\Services\NotificationPreferences` store that GENUINELY
  gates BOTH channels via `App\Notifications\FeedNotification::via()` (always-on Account-&-security can't be
  disabled). Operator/owner notifications in `App\Notifications\Operator\*` / `Owner\*`. 9 guest transactional
  emails (`App\Mail\*` on a shared brand layout) via `App\Listeners\SendGuestTransactionalEmails` (booking/
  cancellation/refund/review-request LIVE on their domain events; a mail failure never rolls back the domain txn).
- **THE GUEST PORTAL (Phase 4b/4c) — complete** — on the guest guard, every screen guest-isolated + mutation-proven
  (resolve through the guest's own relation, foreign → 404): **Trips · Reservation detail · Manage Booking**
  (operator-confirmed `guest_change_requests` via `App\Services\GuestChangeRequestService` — #32) **· Online
  Check-in · Arrival Info** (`App\Support\Guest\ArrivalPack`, door-code isolation + timing — #33) **· Payments +
  Folio Receipt + 5 money PDFs** (`App\Support\Guest\GuestPayments` + `App\Support\Pdf\GuestInvoiceDocument`/
  `GuestReversalDocument`; card charge account-gated) **· the Wallet/credits LEDGER** (`App\Services\GuestWalletService`
  + `guest_credit_ledger`/`promo_codes`; append-only, derived balance, no overdraw — #34; `FolioService::applyWalletCredit`
  is the folio-side seam) **· Guest Messages** (the Unified Inbox EXTENDED — `InboxService::postGuestMessage`/
  `markReadByGuest`, `guest_last_read_at` — #35) **· Wishlist + Waitlist** (`App\Services\GuestWishlistService` +
  `guest_wishlist_items`/`guest_waitlist_entries`). Controllers in `App\Http\Controllers\Guest\*`.
- **THE REVIEWS MODULE (Phase 5) — complete across 5 surfaces** — `reviews` model + **`App\Services\ReviewService`**
  (the single write path: submit tied to a real completed stay [#36] · publish/reject moderation · operator
  respond). The ONE rating derivation **`App\Support\Reviews\ReviewRating`** (`publicScope()` = public AND
  published; average DERIVED, never fabricated — #37) + `App\Support\Reviews\ReviewList` (the shared public list,
  #28 byline). Surfaces: guest `App\Http\Controllers\Guest\GuestReviewController` · operator
  `App\Http\Controllers\Operator\OperatorReviewsController` (`reviews.manage`) · owner `App\Livewire\Owner\OwnerReviews`
  (owner-scoped read) · public storefront (`BookingEngineController` room/browse). `App\Notifications\Operator\NewReviewNotification`
  on `review.submitted`; the Review-Request email on `reservation.checked_out`.
- **THE SHARED-EXTRACTION ANTI-DRIFT FAMILY** (the "a document and the screen it mirrors must never disagree" rule —
  one definition, so surfaces can't disagree; several mutation-proven-as-shared): `Support\Money\MoneyFormatter` ·
  `Support\Folio\FolioTotals` · `Support\Owner\OwnerOccupancy` · `Support\Payouts\RailLabel` ·
  `Support\Reservations\ReservationStage` · `Support\Guest\GuestSpend` · `Support\Guest\GuestHistory` ·
  `Support\Money\MoneyInput` · `Services\UnitAvailabilityService` · `User`/`Guest::changePasswordTo` ·
  `Support\Owner\OwnerLedgerAttribution` (the ONE "Net to you") · `Support\Guest\GuestTrip` ·
  `Support\Guest\GuestReservationView` · `Support\Guest\GuestName` (incl. the #28 `reviewByline`) ·
  `Support\Guest\ArrivalPack` · `Support\Reviews\ReviewRating` + `ReviewList` · `Support\Property\PropertyContext`
  (the ONE current-property resolution) + `Support\Property\PropertyOccupancy` (the ONE occupancy-for-a-property, shared
  by the Dashboard + Portfolio) · `Support\Analytics\PerformanceMetrics` + `MetricSet` (the ONE performance-metrics
  derivation — ADR/RevPAR/occupancy/revenue, reconciling; shared by operator Insights/Reports, the Portfolio
  comparison, owner analytics) · `assertNoRawBlade()` (the Blade-safety
  guard). **Before adding a computed figure to a second screen, extract the definition and prove it's shared.**

## INVARIANTS THAT MUST NEVER BREAK — #1–55 (the full guarantee stack)

> **All 55, grouped below.** #1–14 Phase 1 core (single write path · no double-booking · append-only ledger ·
> money in integer minor units · encrypted PII · tenant scoping · event discipline) · #15–18 Owner Accounting
> (attribution · append-only `owner_ledger` · period-locked statements · **never a cross-currency sum**) ·
> #19–20 the design pass (the honesty rule · scoped-render + guarded-mutation) · #21 Auth · #22 Maintenance ·
> #23–25 Unified Inbox (inbound exactly-once + grouping · deferred channels fail HONESTLY · inbox PII/GDPR) ·
> **#26 the `/app` back-office authorization gate** · **#27 the cumulative refund bound** · **#28 the
> guest-PII perimeter (+ the erasure trigger)** · **#29 the termination bound** · **#30 fine-grained
> permission enforcement** · **#31 the 2FA-pending state grants no authenticated access** · **#32 guest-initiated
> changes are operator-confirmed requests** · **#33 door-code/arrival isolation + timing** · **#34 the guest wallet
> is an append-only ledger (derived balance, no overdraw)** · **#35 guest inbox isolation** · **#36 reviews tie to
> real completed stays** · **#37 review moderation + rating are real (only published is public; rating derived)** ·
> **#38 multi-property: every property-scoped operation resolves through the selected `PropertyContext`, never an
> arbitrary `->first()`; a WRITE targets a concrete property, never "all"; property access is the `property_user`
> pivot-or-all allow-list; a created property is org-scoped** · **#39 analytics are REAL aggregates through the ONE
> shared `PerformanceMetrics` layer, reconciling to `FolioTotals` / the availability spine / the owner ledger — NEVER
> fabricated (no invented history/trend/narrative); a thin period shows an honest empty state; owner analytics are
> owner-scoped (their units only)** · **#40 tenant provisioning is ATOMIC + provisions roles: a new org is created
> only through `TenantProvisioningService` (org + 13 roles + founder + `Property Owner` + first property in ONE
> transaction, all-or-nothing); a provisioned org has a valid RBAC state; the founder holds the ASSIGNABLE org-admin
> role, never the global `Super Admin`; org isolation holds for provisioned orgs** · **#41 subscription billing:
> the trial is REAL (from the 7a provisioning timestamp, days derived); access is gated on the real billing state
> AFTER the trial (in-trial/active = full, expired/canceled = the Paywall, NEVER a mid-trial lock-out) and FAILS
> OPEN on a missing subscription; feature-gating is by plan (channels Pro-only); the LIVE Stripe charge + webhooks
> are ACCOUNT-GATED — nothing flips an org to `active` off a fake payment, no raw card stored; billing is
> tenant-scoped + `billing.manage`-gated** · **#42 owner sub-tenancy (Owner Team): a sub-user ⊆ their owner ⊆ their
> org — the NESTED isolation is COMPOSED from the existing `OwnerScope` (owner boundary) + tenant scope (org
> boundary), NO new scope; the 3 fixed owner-team roles (co-owner/accountant/view-only) gate the portal by the
> design's capability matrix (NOT a privilege chain) through `EnsureOwnerCapability`, and the **payout account /
> IBAN is co-owner-ONLY** (money-security, #28); team management is held by NO role — gated on
> `isPrimaryOwner()` (primary-owner-only), so `canAssign()` refuses every sub-user and self-escalation is
> STRUCTURALLY impossible; the owner-realm invite reuses the hardened token discipline (sha256-only, single-use,
> expiring, no-enumeration) and is OWNER-SCOPED (it can only land a sub-user under the invite's own `owner_id`); a
> removed sub-user's orphaned login is disabled (can't log in); the capability gate COMPOSES with — never masks —
> the owner-scope fail-closed (it only 403s users WITH owner access)** · **#43 team schedule (rostering): the
> roster is TENANT + PROPERTY scoped over REAL staff — a shift lands on the SELECTED property
> (`PropertyContext::id()`, never `->first()`, never cross-property/org) and is assigned only to a real staff
> member (`StaffRoster` = active `Membership` ∩ a back-office role; owners/guests/fabricated names excluded);
> managing the roster is manager-gated (`schedule.manage`, NOT Front Desk) while viewing is broad
> (`schedule.view`); time-off is a request→approval SEPARATION (a staff member requests their OWN, only a
> manager approves) and **SELF-APPROVAL is impossible** (`LeaveService` throws if the actor is the person the
> leave is for, or grants their own) — the guest-change-request/owner-add-unit pattern; a shift-on-approved-leave
> is a REAL computed conflict (assign-on-leave is refused; a pre-existing clash is flagged), never fabricated;
> the schedule PDF renders from the SAME `RosterWeek` builder as the grid, so it RECONCILES and is
> property-scoped (no id in the URL → cross-property/org not accessible). Shift-swapping / labor-cost /
> coverage-AI are OUT of scope (deferred honestly, never faked)** · **#44 production email (Phase 8): a real
> email is NEVER sent from a non-production environment — enforced in CODE by `MailSafetyGuard` (a real-sending
> transport [postmark/ses/…] is refused outside `production` and downgraded to `log` at boot, so even
> MAIL_MAILER=postmark in a dev/CI .env can't reach a real person); the test suite runs on a NON-SENDING
> transport (`array`); provider secrets live in ENV only (never committed); the Phase-3 boundary HOLDS with the
> real transport — a provider send failure is caught + logged (never the address, #28) and NEVER rolls back the
> booking/notification (the email is best-effort); mail goes to the correct recipient only (the guest's/owner's
> OWN address, #28). Bounce/complaint suppression is Postmark-native for v1 (an app-side, blind-indexed
> suppression store is documented, not faked); the DNS records + the one controlled real send are Subhan's** ·
> **#45 live Stripe (money paths, test-mode-first): a real charge can NEVER happen outside `production` — a live
> `sk_live_` key is refused by `StripeSafetyGuard` (the fake client is bound); the card NEVER touches the server
> (hosted Checkout — PCI SAQ A, no raw PAN); every money path FULFILS ON THE WEBHOOK, never the redirect (a
> subscription activates / a folio settles / a payout confirms only on checkout.session.completed /
> payment_intent.succeeded / transfer.created); every webhook is SIGNATURE-VERIFIED against the raw body (the
> account + Connect endpoints have SEPARATE secrets) and idempotent on `webhook_events(source,event.id)`;
> double-money is impossible by MULTIPLE layers — a deterministic sending-side Idempotency-Key on every Stripe
> write (retry → the original object), the `event.id` unique index (re-delivery → once), and — for the guest
> charge — `firstOrCreate(gateway_ref)`; **the owner payout CANNOT double-transfer** (a unique Idempotency-Key +
> a `lockForUpdate` row-lock on the payout, re-checked pending inside the lock — proven to transfer twice WITHOUT
> and once WITH) and a transfer only ever goes to ITS OWN owner's connected account; the ledgers stay
> append-only / correct-by-reversal / integer money (#17, #27), reconciling; the LIVE-KEY FLIP (test→live keys +
> the live webhook endpoints) is Subhan's documented go-live step, never done in the build**.
> · **#46 the OWNER ACCOUNT AREA (Profile & account + Security):** the owner self-profile
> (name/login-email/avatar/language/password/2FA/sessions/data-export) is PER-USER — resolved from `auth()`,
> NEVER a request id — so every owner-realm user incl. accountant/view-only SUB-USERS manages their OWN account
> (the old sub-user 404 is gone); the OWNER-PARTY/business details + payout links + team + DEACTIVATE are
> PRIMARY-OWNER-ONLY (`EnsurePrimaryOwner`, #42); a login-email change RE-VERIFIES the new address (a signed
> `temporarySignedRoute` mailed to it, `{hash}`=`sha1(pending_email)`) BEFORE it switches; DEACTIVATE is a
> REVERSIBLE, NON-DESTRUCTIVE `users.status` flag (login is active-only → a paused account can't sign in;
> bookings/statements/payouts STAY; PII erasure remains the #28 `anonymize()` path; reactivation restores);
> active sessions are the REAL `sessions` table (sign-out-others password-gated + rotates the remember-token);
> the account-protection checklist is DERIVED from true state (password set · 2FA on/off · unused recovery
> codes) — NEVER a fabricated score; all writes are controller POSTs (never `/livewire/update`).
>
> **Owner-page + platform cluster (added since the summary above was written):** · **#47** analytics depth (Batch B,
> no fabricated LY/pricing) · **#48** operator money-write idempotency (Batch C) · **#49–53** the SUPER ADMIN platform
> panel (isolated `/admin` realm · DB-backed plans + feature-gating · auth-signal capture + privacy · encrypted platform
> credentials · admin account discipline) · **#54** the owner Overview exact-match + real backends · **#55** the owner
> My Units + Add Unit exact-match + real backends · **#56** the owner **Bookings** exact-match (page + Booking-Summary
> PDF): the "Bookings over time" chart + "Monthly goal" ring are REAL (status counts + `OwnerLedgerAttribution` net + an
> owner-SET `owner_monthly_target`, never a fabricated goal); the page + CSV export read the ONE `OwnerBookingsReader`
> (can't drift; name-only, no contact — #28; the target write is a `$capActions` CONTROLLER route with a server-derived
> currency — #20/#30); the PDF shows REAL owner-scoped occupancy (`PerformanceMetrics::forOwnerUnits`, physical-unit-keyed),
> the guest NAME-only (`GuestName`), the real channel commission (`ota_cost`) and the real letterhead VAT — the OTA
> **brand** stays refused (unmodelled); Σ(rows)===gross to the cent is preserved.
>
> ⚠️ **#26–30 each closed a live hole that the fully-green suite never noticed** (#26 an owner reading every other
> owner's financials · #27 a €369 payment taking €1,107 in refunds · #29 a duplicate OTA webhook rewriting a
> completed stay · #30 any back-office role minting itself an Accountant). Every one was found by **reading**,
> never by a test failing. The guest-realm/reviews guards (#32–37) were all built mutation-proven from the start
> (each proven to FAIL without the guard). See META-LESSONS (b), (e), (i).

**Phase 1 core:**
1. **Single inventory write path** — ONLY `AssignmentService` inserts/mutates `reservation_units` /
   `availability.sold` (this INCLUDES OTA imports via `importBooking()` — never a parallel path).
2. **Two-level no-double-booking** — Level A (room-type counts) + Level B (physical-unit non-overlap)
   under `SELECT … FOR UPDATE` in one transaction; **deterministic lock order** (`room_type_id` +
   `check_in`) → deadlock-free. MySQL has no exclusion constraint — the guarantee lives in this service;
   the concurrency test is mandatory.
3. **One reservations table** — `source` distinguishes direct/OTA/walk-in/corporate/group; never parallel tables.
4. **OTA overbooking = accept-and-flag** (`sold` never exceeds `total`; excess modeled as `overbooked`
   line + `overbooking_conflicts`); **direct/public bookings REJECT when full** (asymmetry is deliberate).
5. **Advisory booking holds are NEVER counted in `sold`** — `booking_quotes` is a price/availability
   snapshot; real occupancy only at `book()` under the AssignmentService lock.
6. **ARI push is a pure post-commit consumer** — reads CURRENT state at execution (not enqueue-time),
   event-keyed idempotency (`ari_push:{channel}:{room_type}:{domain_event_id}`), never mutates `sold`,
   never emits an inventory event (no push→event→push loop).
7. **Append-only correct-by-reversal financial ledger** — posted `payments`/`invoices`/`folio_items`
   never edited/deleted; refunds/voids are NEW offsetting entries; deposit hold ≠ paid until captured.
   ⚠️ **#7 bounds EDITS, not SUMS** — see #27, which is a genuinely different guarantee. Conflating the two
   is exactly how a €369.00 payment once took three full refunds while every rule here held.
8. **Money = integer minor units + ISO currency**, via `App\Support\Money\Money` / `MoneyCast`. Never a float.
9. **PII** — `guests.email`/`phone` encrypted; GDPR erase = `anonymize()` (null PII, purge file bytes).
10. **Event direction** — `domain_events` emitted post-commit by the WRITE side only;
    `AvailabilityService::recompute` + all listeners are PURE consumers. **Availability recompute +
    `sold` mutation stay SYNCHRONOUS/inline — never `ShouldQueue`** (only the outbound ARI push is async).
11. **Idempotency = unique constraint + catch-the-race** — `webhook_events`, `reservations(channel_id,
    ota_reservation_id)`, `sync_jobs(idempotency_key)` are real DB uniques; app catches
    `UniqueConstraintViolationException` and resolves to the winner.
12. **Enforced morph map** — `Relation::enforceMorphMap` on; every polymorphic-capable model registered
    (AppServiceProvider) or Laravel throws.
13. **Tenant scoping** — every business model uses `BelongsToOrganization`; the guard THROWS with no
    context; explicit super-admin bypass only.
14. **ULID keys everywhere** except spatie's own tables (documented integer-PK exception).

**Owner Accounting (Phase 3 · Module 1) additions:**
15. **Attribution is derived + READ-ONLY** — `OwnerAttributionService` reads `folio_items` +
    `reservation_units`, NEVER mutates folios/reservations/inventory/`availability`; owner_share +
    operator_share reconciles to the folio room revenue **to the cent** (`Money::allocate` is
    sum-preserving — no minor unit created/lost; fuzz-proven).
16. **`owner_ledger` append-only + WHOLE-TRANSACTION correct-by-reversal** — a cancel/refund/modify
    reverses EVERY component of a booking together (mirror rows, `reverses_id`), never partial, never
    edits originals; nets to zero; DB-idempotent (`idempotency_key`).
17. **Owner statements = pure projection + period-locked** — net == Σ component lines == ledger net;
    draft reproducible; finalize STAMPS `owner_ledger.statement_id` + locks (immutable; regenerate
    throws); post-lock adjustments carry forward onto the next statement as labeled prior-period
    adjustments — **gap-free (every ledger line claimed by exactly one statement)**; cross-currency uses
    operator-supplied `fx_rate`/`fx_source` (no silent float); payouts finalized-only + positive-net,
    negative net carries forward (never a reverse-charge), append-only lifecycle (`supersedes_id`).
18. **OWNER ISOLATION — a row-level scope WITHIN a tenant, treated like tenant isolation** —
    `OwnerContext` + `OwnerScope` (via `BelongsToOwner` on Unit/OwnerStatement/OwnerStatementLine/
    Payout/OwnerLedgerEntry/OwnerExpense/**MessageThread**). **FAILS CLOSED** (active + empty set → nothing,
    never all owners); inactive off-portal (operator/CLI/tests see all, subject to tenant scope); activated
    only by `SetOwnerScopeForPortal`, **deactivated on `terminate`** (no leak into operator requests);
    **composes with tenant scope** (both boundaries hold; cross-org 404 both directions).
    - **⚠️ THE TRAIT IS THE BOUNDARY — WITHOUT THE COLUMN THE SCOPE DOES NOT FAIL CLOSED, IT NEVER RUNS**
      (proven in Wave 3, and the reason `message_threads.owner_id` landed BEFORE the owner-inbox screen).
      `OwnerScope` keys on `owner_id`; a model without that column simply cannot carry `BelongsToOwner`, so
      an owner-facing read over it is **tenant-scoped only** — every other owner's rows, visible. That is
      invariant #26's shape wearing a different hat. **Before building any owner-facing screen over a model,
      check it has the column and the trait; the scope will not warn you.**
    - **THE BOUNDARY FALLS OUT OF THE SCOPE — never write the predicate yourself.** On `MessageThread` an
      operator↔guest thread has `owner_id = NULL`, so `owner_id IN (their ids)` excludes it automatically:
      the owner portal cannot see a guest conversation without anyone coding that rule. Adding a manual
      `->where('owner_id', …)` alongside would imply the scope were optional.
    - Proven SYMMETRICALLY (both owners, both directions — a scope that returned everything would pass a
      one-way test) and MUTATION-PROVEN: removing the trait fails 4 tests, including the guest-thread leak.
      (`OwnerPortalIsolationTest`, `OwnerInboxIsolationTest`.)

**Design pass (presentation-only — these govern EVERY styled screen, all ~31 of them):**
19. **Design-pass honesty rule** — every styled element is exactly one of: **REAL** (wired to a certified
    read/service), **DERIVED** (computed from real figures only), **DEFERRED** (visually stubbed + "not
    yet available"), or **NO-BACKEND** (disabled "Soon"). NEVER a fabricated figure/status/action/photo/
    IBAN/date. Money reconciles to the cent against its source; statuses/labels are keyed to the **real
    enums only**; warm colours (no green/red). **Backends/schema/guarantees are UNCHANGED by any screen —
    presentation only.** Enforced per-screen by a render/isolation test. (Locked `docs/DECISIONS.md`
    2026-07-11; extended to operator + public.)
20. **Scoped-render + guarded-mutation discipline (isolation holds on every screen)** — a scoped model is
    resolved in `render()` (or the controller) via a tenant/owner/property-scoped `findOrFail` from a
    **string** id, NEVER a route-model-bound prop (RMB resolves during `SubstituteBindings`, before the
    scope middleware → leak/guard-throw). Scope-dependent **mutations** never ride a Livewire wire-action
    (it re-renders via `/livewire/update`, bypassing the route middleware → scope leak); they use
    **POST→controller→existing-service-action→PRG** (operator; tenant-scoped, cross-org id 404) or a
    **session-CSRF POST** (public checkout). The public checkout write still goes through the SINGLE
    guarded path (`AssignmentService`, source=direct — rejects when full, never overbooks), never a
    parallel one; its POST is CSRF-protected (the machine JSON API stays exempt by path
    `book/*/quote`,`book/*/book`). Guest PII (encrypted email/phone) is never rendered; guest NAMES are
    shown only on staff surfaces.

**Auth (Phase A + B — these govern login + the owner invite flow):**
21. **Auth security stack** — sign-in is **active-only** (`Auth::attempt(['status'=>'active'])`, so an
    `invited`/`disabled` login can't authenticate); auth endpoints (login + `/invite/*`) are
    **rate-limited** and return **generic, non-enumerating** errors (no "which email/invite exists"
    leak — a spent invite token yields the byte-identical page as a garbage one); **passwords are hashed**
    (`hashed` cast, never plaintext); **sessions regenerate** on login and on invite-activation.
    Invite tokens are **high-entropy, stored ONLY as `sha256(plain)` (never plaintext), single-use**
    (status `pending→accepted`), **7-day expiring**, and revocable. The operator invite write is
    **tenant-scoped** (POST→controller→`OwnerInviteService`→PRG; a foreign unit id is dropped, never
    assigned cross-org). Three roles share the machinery: **owner** (owner link → `/owner`) + the two
    **staff** roles **Hotel Manager / Accountant** (Membership + a **real, validated** spatie catalog role
    → `/app`; no owner/agreement/units). Staff role assignment is **fail-safe** — the slug must map to a
    catalog role AND be provisioned for the org (validated at invite AND accept; an unknown role 422s,
    never a phantom/cross-org grant). On activation an owner's **owner-scope binds** (invariant #18 still
    fails closed for a non-linked user); staff land in `/app` with their role's permissions. (Locked
    `docs/DECISIONS.md` 2026-07-13.)

**Maintenance module:**
22. **Out-of-service is a unit-level exclusion, NOT a booking-core change** — a maintenance OOS ticket
    transitions the unit's `condition_status` and emits `unit.condition_changed`; the EXISTING availability
    recompute already counts `out_of_service` into `out_of_order` → the unit is not offered. `AvailabilityService`
    and the `AssignmentService` locking core are **UNTOUCHED**; `sold` is never touched (no-double-booking +
    single-write-path hold). A unit returns to service only when no other active OOS ticket holds it; an
    existing booking is **NEVER auto-cancelled** (flagged for manual move); an over-committed room-type shows
    a negative raw available (honest) while the clamped `available()` stays 0 (no oversell). **Maintenance
    cost flows to the owner ONLY through the certified `OwnerExpenseService`** (source = the ticket) →
    `owner_ledger` → statement — never a direct ledger write; operator-owned units create no expense;
    corrections reverse append-only; period-lock respected. **The styled work order SURFACES both
    integrations** — the OOS callout with the real manual-move count, and the cost card read from the real
    `owner_expense` — without changing any guarantee (presentation only). (Locked `docs/DECISIONS.md`
    2026-07-14, two entries.)

**Unified Inbox module:**
23. **Inbound is exactly-once + threads GROUP** — a duplicate provider delivery (same `external_message_id`)
    collapses to one `messages` row via `createOrFirst` on `unique(org, channel, external_message_id)` (proven
    under true OS-level concurrency); messages group into the right thread by the `unique(org, channel,
    external_thread_ref)` anchor (or a non-PII participant hash for a stranger) — never a thread-per-message.
    `postInbound` runs createOrFirst in **autocommit** (not a long transaction) so the losing INSERT's
    re-SELECT sees the committed winner — the same discipline as the Channex webhook leg.
24. **Real channels are honest-fail, never faked** — `ManualChannel` is the only working channel; the
    email/WhatsApp/OTA adapters are config-gated `DeferredChannel` skeletons whose `send()`/`normalizeInbound()`
    **throw "not configured"**, and a reply on a deferred channel leaves an **honest `failed` message** + a
    clean flash (never a fake send). Guest linking reuses the SINGLE `GuestMatcher` (no parallel matcher); the
    external-ref value is a **hash** (non-PII) while the address stays encrypted on the guest.
25. **Inbox PII discipline** — `participant_identity` is the **encrypted routing anchor** (never rendered, never
    a competing copy of a linked guest's PII); the operator surface shows the guest NAME + conversation only.
    `Guest::anonymize()` **scrubs the guest's message bodies + sender labels + participant identity/subject**,
    keeping the skeleton for audit (never soft-delete-and-keep-PII). (Locked `docs/DECISIONS.md` 2026-07-14.)

**The operator/owner boundary (the `/app` authorization gate — 2026-07-16):**
26. **An owner-role user is NOT operator-capable, and is refused `/app` at BOTH the route AND the role
    level.** Two independent layers; each is tested in isolation so a regression in either is caught alone:
    - **ROUTE (layer 1)** — `App\Http\Middleware\EnsureBackOfficeAccess` on the **`/app` GROUP** (never
      per-route, so a route added later cannot forget it; a test asserts EVERY `app/` route carries it).
      It requires a role from the `config('authorization.back_office_roles')` **ALLOW-LIST**, checked
      **team-scoped** — so no role, a portal-only role, or a role held in a DIFFERENT org all **fail
      closed**, and the gate composes with tenant isolation rather than bypassing it. An owner-linked user
      is redirected to `/owner`; everyone else gets a bare 403. Neither response renders operator data.
      **Ordering is load-bearing:** it MUST run after `SetTenantForWebRequest` sets spatie's team id, or
      `hasAnyRole()` matches nothing and EVERY operator is locked out — asserted by a test on the resolved
      middleware chain.
    - **ROLE (layer 2)** — the owner-invite path assigns the owner-only **`External Owner`**
      (`owner_portal.view`, nothing else), so the allow-list has **nothing to admit** even on a route that
      forgets the middleware. Asserted with NO HTTP at all: an activated owner's
      `hasAnyRole(back_office_roles)` is false.
    - **⚠️ `Property Owner` IS a back-office role** — it is the ORG ADMIN (`['*']`, all 44 permissions), NOT
      the unit owner (that is `External Owner`). The names are a trap; `DemoSeeder` fell into it and granted
      a unit owner `payments.refund`. **The demo seeds the production model** (demo-owner → `External
      Owner`, demo-staff → `Hotel Manager`) and a test locks it — otherwise the demo journeys would not
      exercise the boundary they appear to.
    - **The owner's Membership STAYS and is not the bug** — `SetTenantForWebRequest` resolves the org ONLY
      from active Memberships, and `SetOwnerScopeForPortal`'s `Owner::query()` runs under the tenant guard,
      which THROWS with no context: remove it and `/owner` 500s on every route. Nothing in `OwnerContext`/
      `OwnerScope` reads Membership (owner identity is purely `owners.user_id`). **A Membership is NOT
      operator access** — assuming it was IS what created the hole.
    - **Owner-role assignment is deliberately non-throwing** when the role is unprovisioned (unlike
      `assignStaffRole`, which throws): a staff member without their role is broken, but an owner's portal
      access comes from the owner link, so an owner with no role works identically and is equally
      non-operator-capable. It logs and proceeds rather than blocking a legitimate activation. A role
      missing from the CATALOG is a code bug and still throws.
    (Locked `docs/DECISIONS.md` 2026-07-16; proven by `BackOfficeAccessTest` + `OwnerNotOperatorCapableTest`,
    the former mutation-verified — removing the gate fails 5 of its tests.)

**The refund bound (money — 2026-07-16):**
27. **CUMULATIVE refunds against a payment may EQUAL but never EXCEED that payment's original amount.**
    **This is NOT invariant #7 and was not implied by it.** #7 (append-only, correct-by-reversal) says a
    refund is a NEW offsetting row and the original is never edited — it bounds **edits**. Nothing bounded
    the **sum**. Both held simultaneously while a €369.00 payment took **three full refunds = €1,107.00**
    (every refund a legitimate append-only row, original untouched). #27 is the missing bound.
    - **Enforced in `FolioService::refundPayment()` — the single write path, so EVERY caller is covered.**
      Never re-implement it per controller. `remaining = original − Σ prior succeeded refunds`; reject
      `amount > remaining` with `App\Exceptions\Folio\RefundExceedsPaymentException` — a clean domain
      rejection, **never a silent clamp or partial application**. It subsumes the old per-call check (no
      priors ⇒ remaining IS the original), so partial refunds up to the original still work exactly as before.
    - **ORDERING IS LOAD-BEARING:** the guard runs **before** `$this->gateway->refund()`. The gateway is
      invoked before any row is written, so a guard placed after it would move REAL money and only then
      reject. Do not reorder.
    - **The sum** = `payments WHERE type='refund' AND status='succeeded' AND
      metadata->refunds_payment_id = <id> AND currency = <original.currency>` — the link is a **JSON path**
      (there is no FK column). Only `succeeded` counts (a failed refund moved no money and must not consume
      headroom). **`withTrashed()`** — a soft-deleted refund STILL bounds; posted financial rows should never
      be deleted (#7), so this is belt-and-braces: a delete must never hand back refundable headroom.
    - **Same-currency only** — minor units are not comparable across currencies, so the bound and its sum
      would be meaningless. (Previously unchecked: a 100 USD refund against a 100 EUR payment PASSED.)
    - **Non-positive rejected** — a €0.00 refund passes any `> remaining` test and posts a junk row into a
      ledger that can never be tidied away.
    - **The UI must not invite the rejection:** `ReservationDetail` marks a payment refundable only while
      `remainingRefundableMinor() > 0` (and exposes the remainder). The button persisting after a full
      refund is what made the bug reachable in the shipped product. The service stays the authority.
    (Locked `docs/DECISIONS.md` 2026-07-16; proven by `RefundGuardTest` — written FIRST and run against the
    unguarded code: **7 of 10 failed pre-fix, 10/10 pass post-fix, and removing the guard fails 4 again**.)

**The guest-PII perimeter (Wave 2 — 2026-07-17):**
28. **Guest contact PII (`guests.email`/`phone`) is rendered ON THE GUESTS CRM ONLY — the list and the
    profile — and NOWHERE else.** Front desk, reservations list and inbox show the guest NAME only. The CRM
    is where an operator legitimately needs to reach a guest; a booking row is not.
    - **The perimeter is ENFORCED BY A TEST, not by a docblock.** `OperatorGuestsListTest` renders the front
      desk, the reservations list and the inbox and asserts each shows the NAME and NOT the contact — so a
      passing run means "name yes, contact no", never "the page was empty". **Mutation-proven:** adding an
      email to the reservations list fails it.
    - **No public Livewire prop may hold a guest.** A public prop is serialized into the page payload and
      round-trips over `/livewire/update`; both CRM screens hold a plain string id and resolve in `render()`,
      so decrypted contact reaches the HTML and stops there. (This is the same `$xxxId` rule as invariant #20,
      for a second reason.)
    - **⚠️ THE AUDIT TRAIL MUST NOT BECOME A SECOND, UNENCRYPTED PII STORE.** `email`/`phone` are encrypted
      casts; `audit_events.before`/`after` and `domain_events.payload` are plain JSON that
      `Guest::anonymize()` does not clear. **`GuestService` records WHICH FIELDS changed, never their
      values** — enough to answer "who touched this guest's email on Tuesday?", never enough to reconstruct
      it. Writing the old/new address there would re-retain the exact PII an erase destroys and silently
      undo encryption-at-rest. `Guest::anonymize()` already stated this rule for itself ("never put the
      erased PII into the audit before/after"); **it holds for every write, not just the erase.**
    - **Every PII store must be erasable.** `anonymize()` clears the guest's columns, their ID documents,
      their inbox message bodies/participant identity AND (since Wave 2 shipped the first note writer) their
      **note bodies** — a staff note about an identified person is that person's personal data. When a new
      free-text store about a guest is added, it MUST be added to `anonymize()` in the same change.
    - **AND IT IS NOW REACHABLE (2026-07-17).** Until then `anonymize()` was called from **NOWHERE** — the
      erase was correct, thorough and tested, and no operator could run it, so a data-subject request could
      not actually be served. `POST /app/guests/{guest}/anonymize` is the trigger: gated on
      **`can:guests.erase`** (Property Owner / Group Admin only — running the house does not include erasing a
      person), **server-verified typed confirmation** (a client `confirm()` is bypassable by anyone who can
      POST, and an irreversible action's intent check must survive a bookmarked request), and it **calls the
      existing method** rather than re-implementing redaction — a second copy would drift and quietly miss a
      store, which for an erasure means keeping data you said you destroyed. The erased profile still renders,
      and its financial history still reconciles.
    - **Search cannot see it, and the page says so.** Encrypted TEXT is never indexed by value, so the CRM
      searches names only and states that in the footer rather than letting an operator conclude the data is
      missing. (The blind-index is a standing deferral.)
    (Locked `docs/DECISIONS.md` 2026-07-17; proven by `OperatorGuestsListTest` + `OperatorGuestProfileTest` +
    `GuestServiceTest`.)

**The termination bound (2026-07-17):**
29. **A TERMINAL RESERVATION CANNOT BE TERMINATED AGAIN.** Terminal = `AssignmentService::TERMINAL_STATUSES`
    = `['cancelled', 'no_show', 'checked_out']` — **the ONE definition**, read by every layer.
    - **The guard is INSIDE the private `terminate()`**, before the transaction (a rejected call rolls nothing
      back and touches no inventory). Both public entries — `cancelReservation()` and `markNoShow()` — funnel
      through it, so **one check covers every caller that exists or ever will**. That is the whole point.
    - **⚠️ WHY THE SERVICE AND NOT THE CALLERS: there were already TWO callers and they DISAGREED.** The Wave 2
      note claimed the operator controller was the only one; `BookingImportService::cancel()` — the OTA/Channex
      **inbound webhook** path, i.e. a network-triggered, RETRYING caller — had always reached it too, with its
      own guard checking only `['cancelled','no_show']`. So an OTA cancelling a stay the guest had **already
      completed** (`checked_out`) went straight through and stamped `cancelled_at` onto it, while the
      controller refused exactly that. Two rules, one concept.
    - **A SECOND TERMINATION IS A REWRITE, NOT AN APPEND** — which is exactly why #7 never caught it. It
      overwrites `cancelled_at`/`cancellation_reason` (destroying the provenance the detail screen renders from
      those very columns), appends a junk `cancelled → cancelled` row to an append-only trail, and re-emits
      `reservation.cancelled` to every consumer. **Same blind spot, same shape, as #27** bounding the SUM of
      refunds where #7 only bounded their edits.
    - **The OTA path NO-OPS, it does not throw** — it reads `TERMINAL_STATUSES` and returns before ever asking.
      On a retrying webhook, letting a terminal booking reach the guarded service would turn an ordinary
      duplicate delivery into a **poison message**. Idempotent at the edge, authoritative in the service.
    - **The controller keeps its allow-list** (defense-in-depth, the #26 shape), and a test asserts
      `CANCELLABLE` is the exact **complement** of `TERMINAL_STATUSES` over the real 7-value enum — so the UI
      can never offer what the service will refuse.
    (Locked `docs/DECISIONS.md` 2026-07-17; proven by `TerminateGuardTest` — written FIRST: **5 failed + 1
    error pre-guard, 13/13 after, and neutering the guard fails 5 again**. The bug was demonstrated on real
    demo data before the fix.)

**Fine-grained authorization — the catalog is CONSULTED (2026-07-17):**
30. **THE SENSITIVE WRITE ROUTES ENFORCE THEIR CATALOG PERMISSION.** Until now the 45-permission catalog was
    **decorative**: not one permission was checked anywhere, so the coarse #26 door admitted all 11
    back-office roles EQUALLY and `payments.refund` — deliberately withheld from Hotel Manager and Front Desk
    — was effectively held by everyone who could reach `/app`. **A catalog that describes a policy the app
    does not enforce is worse than none: it reads like protection.**
    - **ENFORCED** via `can:<permission>` **route middleware** on the `/app` group:
      `owners.invite → users.manage` · `reservations.refund` **+ its GET screen** `→ payments.refund` ·
      `reservations.payment → payments.record` · `payouts.create|pay → payouts.manage` ·
      `reservations.cancel|no-show → reservations.cancel` · `guests.anonymize → guests.erase`.
    - **🔑 THE HEADLINE WAS PRIVILEGE ESCALATION, NOT THE REFUND.** `POST /app/owners/invite` assigns a REAL
      spatie role. Proven end-to-end before the fix: **Housekeeping → invite self as `accountant` → invite
      minted → Accountant holds `payments.refund`. Two requests, no operator involved.** Gating the refund
      route alone would have been **cosmetic** — the attacker just mints an Accountant. Never gate a money
      route without gating the role-granting route in the same change.
    - **`payouts.manage` DID NOT EXIST** and was added (catalog 44 → **45**). The payout run had always moved
      real money with no permission to check against. Granted to Property Owner\*/Group Admin/Accountant,
      mirroring `payments.refund`; **not** Hotel Manager — remitting an owner is an accounting act.
    - **⚠️ AUTHORIZATION GOES ON THE ROUTE, NOT IN THE SERVICE — deliberately unlike #27/#29.** A permission is
      a REQUEST-level fact about an ACTOR; those are DATA-level bounds about a ROW. `FolioService::refundPayment()`
      is called by tests and jobs with **no authenticated user**; a `can()` there would be meaningless or fatal.
      The bound guards every caller; the permission guards every actor. **Do not "unify" them.**
    - **THE UI MUST NOT INVITE A 403** (#27's principle, applied to authorization): every control on a gated
      route is `@can`-gated — the refund button + partial-refund link, Record-a-payment, cancel/no-show, every
      payout control, the whole invite modal (not just the button that opens it), and the erase card.
    - **ORDERING IS SOUND AND VERIFIED:** `Authorize` runs **last** — after #26 and after
      `SetTenantForWebRequest` sets the spatie team — and is re-applied on Livewire update requests.
      ⚠️ **A null team fails SILENTLY to `false`**, so nothing may be placed above that middleware.
    - **#26 STILL RUNS FIRST.** This is a fine-grained layer ON TOP of the coarse door, never a replacement —
      an owner is bounced before any permission is consulted (asserted).
    - **FIX FIXTURES, NEVER THE CHECK** (the #26 lesson): 28 tests across 7 files moved to
      correctly-permissioned roles. ⚠️ **Cross-org isolation tests must keep a PERMITTED actor**, or `can:`
      403s before the scoped `findOrFail` and the `assertNotFound()` silently stops proving tenancy.
    (Locked `docs/DECISIONS.md` 2026-07-17; proven by `PermissionEnforcementTest` + `GuestErasureTest` —
    written FIRST: **6 and 8 failed respectively; 10/10 and 9/9 after; stripping every `can:` fails 5 again**.)
31. **THE 2FA-PENDING STATE GRANTS NO AUTHENTICATED ACCESS — BY CONSTRUCTION.** When a user with 2FA gives the
    correct password, they are NOT logged in: `LoginRequest` uses `Auth::validate` (verify, no session) and
    `LoginController` holds them in a **pending** state — a plain session key `login.2fa.user_id`, **NEVER the
    auth guard** — then redirects to `/two-factor-challenge`. Because the marker is not the guard, the pending
    user is not `Auth::check()`-true, so `/app` + `/owner` (behind `auth`) **reject them with no new
    authorization code** — the existing middleware does it. `RequireTwoFactorPending` only fences the challenge
    page itself. Only a valid **TOTP or single-use recovery code** runs `Auth::login` (+ session `regenerate`
    for fixation, + route by role); invalid codes are **rate-limited** per pending-user+IP. Recovery codes are
    **single-use** (`used_at` stamped, `hash_equals`); the secret + codes are **encrypted at rest** (the secret
    column was plaintext before 2FA — now fixed). A **non-2FA** login is unchanged (validate+login ≡ the old
    `attempt`). ⚠️ **Do not turn the pending marker into an authenticated session or a guard** — that would hand
    a password-only attacker everything the second factor is meant to stop. (Built `docs/DECISIONS.md`
    2026-07-18; enrollment `031716a`, challenge `aca017c`; proven by `TwoFactorEnrollmentTest` [12] +
    `TwoFactorChallengeTest` [8], the latter mutation-proving that a correct password alone reaches nothing.)

**Guest Portal (Phase 4b/4c) — the guest realm + its guards:**
32. **GUEST-INITIATED CHANGES ARE OPERATOR-CONFIRMED REQUESTS.** A guest changing dates / adding an extra /
    cancelling touches inventory / the folio / money, so it is only ever a `pending` `guest_change_requests` row
    the operator confirms. The guest NEVER performs the real inventory/folio/refund write and can't self-approve
    (the confirm route is `/app`, refused to the guest realm). Same shape as the operator/owner boundary: the
    guest may ask, the operator acts. (Phase 4b · SG3.)
33. **DOOR-CODE / ARRIVAL-INFO ISOLATION + TIMING.** The arrival pack — ESPECIALLY the door code — shows ONLY for
    the authed guest's OWN booking (resolved through their own relation, never a request id) AND only within the
    arrival window (`ArrivalPack`, `LEAD_DAYS=2` before check-in through check-out; withheld before it, and for a
    cancelled booking). `door_code`/`wifi_password` are encrypted at rest; an unset field is honest, a code is
    never fabricated. Leaking a code to the wrong guest — or months early — is a physical-security issue, so both
    guards apply. (Phase 4b · SG4.)
34. **THE GUEST WALLET IS AN APPEND-ONLY LEDGER.** `guest_credit_ledger` mirrors `owner_ledger` — `direction`
    credit/debit, positive `amount_minor`, `reverses_id` self-FK (correct-by-reversal), `expires_at`. There is
    DELIBERATELY NO balance column: the spendable balance is DERIVED (Σ live credits − Σ debits) in
    `GuestWalletService`. It can NEVER overdraw — a debit beyond the derived balance is refused BEFORE any write
    (`WalletOverdrawException`), and expired credit doesn't count. Integer minor units (#8). Apply-to-booking is a
    real debit + a folio credit via the additive `FolioService::applyWalletCredit()` (`posted_by` null — a guest
    request's default guard is the guest, so it never reads `Auth::id()`). (Phase 4c · SG2.)
35. **GUEST INBOX ISOLATION.** A guest sees ONLY their own thread(s) (resolved through `guest_id`, never RMB) —
    never another guest's, never an owner↔operator thread. A guest-side read moves only `guest_last_read_at`,
    never the operator's `unread_count` (the same trap the owner-inbox extension avoided). The guest side EXTENDS
    the one Unified Inbox — `InboxService::postGuestMessage`/`markReadByGuest` mirror the owner methods; no
    reimplementation. (Phase 4c · SG3.)

**Reviews (Phase 5) — the module's two guarantees:**
36. **REVIEWS TIE TO REAL COMPLETED STAYS.** A guest reviews only their OWN completed (checked-out/PAST) booking —
    never a future/cancelled/foreign/not-their stay — and at most once per stay (`reviews.reservation_id` unique).
    The review FREEZES the physical `unit_id`/`room_type_id` at review time (so the owner + storefront surfaces
    don't drift on live re-resolution). Owner isolation: an owner sees reviews for THEIR OWN units only (resolved
    through the owner-scoped `OwnerPortalService::units()`). (Phase 5.)
37. **REVIEW MODERATION + RATING ARE REAL.** A submitted review is `pending` (never auto-public); only a
    published+public review is publicly visible (a pending/rejected/private one never is; a **private** review can
    NEVER be published — the guest's choice is binding). Every rating is DERIVED from published+public reviews via
    the ONE shared `App\Support\Reviews\ReviewRating` (`publicScope()` = the single source of "publicly visible") —
    never a stored/fabricated number; a subject with none shows "No reviews yet". (Phase 5.)

38. **MULTI-PROPERTY: OPERATIONS ACT ON THE SELECTED PROPERTY, NEVER AN ARBITRARY `->first()`.** An org runs N
    properties; before Phase 6a ~10 sites resolved "the property" as `Property::query()->orderBy('name')->first()`,
    which silently returns an ARBITRARY property once there is more than one (a wrong-property read, or a wrong-property
    WRITE — a booking in the wrong property). Every property-scoped read/write now resolves through
    `App\Support\Property\PropertyContext` (the per-user session-backed current property), set by
    `SetCurrentPropertyForWebRequest` on the `/app` group and chosen with the topbar switcher.
    - **A WRITE targets a CONCRETE property, never "all"** — `PropertyContext::id()` always yields a single property
      (the default when "all" is selected); "all properties" (`readIds()`) is an explicit ROLL-UP honoured by aggregate
      READ surfaces only (Dashboard/Portfolio/the Tier-B list screens).
    - **PROPERTY ACCESS = the `property_user` pivot-or-all allow-list.** A user with pivot rows is restricted to those
      properties; a user with NO rows gets the whole org (`User::accessiblePropertyIds()`; empty ⇒ full org, so no
      existing user is locked out and single-property is unregressed). Honoured on BOTH the switcher and the write path
      (a pivoted user can't switch to, or book/create into, an unassigned property).
    - **Livewire boundary:** a wire-action re-render hits `/livewire/update`, OUTSIDE the `/app` group, so the context
      is INACTIVE there — operator screens capture the resolved ids at `mount()` and hold them as **`#[Locked]`** props
      (`App\Livewire\Concerns\ResolvesCurrentProperty`); Locked stops a client smuggling a non-assigned property id
      (cross-property access WITHIN an org is not caught by tenant scope). Same family as invariant #20.
    - **A CREATED property is ORG-SCOPED** — `PropertyController` (gated `properties.manage`, #30) creates through
      `BelongsToOrganization` (organization_id auto-filled, never cross-org) and it enters the selector. The
      **grep-clean** proof (no property-resolution `->first()` remains) is the sweep's completeness guarantee — a missed
      site is a wrong-property bug. Mutation-proven both ways. (Phase 6a.)

39. **ANALYTICS ARE REAL AGGREGATES — NEVER FABRICATED.** Every analytics figure is derived through the ONE shared
    `App\Support\Analytics\PerformanceMetrics` + `MetricSet` — occupancy/ADR/RevPAR/room revenue on a stated
    stay-night basis (RN = in-range nights of active `reservation_units`, reconciling to `availability.sold`; RR =
    `price_minor` pro-rated, reconciling to the folio room line; AN = `availability.total` for a property scope, or
    unit-count × days for an owner scope). **The identity RevPAR = ADR × occupancy holds EXACTLY** (same RN). Reports
    revenue is Σ real `folio_items` (reconciles to `FolioTotals`); operator costs show only what's real (maintenance),
    the rest "Not tracked yet"; owner **earnings reconcile to the owner ledger** via `OwnerLedgerAttribution` (the
    statements' figure). **The prototype's invented multi-year history + generated narrative are REFUSED** — never
    rendered; a real-derived observation ("busiest night is Monday") is fine, an invented one is not; a thin/empty
    period shows an honest empty state, never a fake curve. Money never sums across currencies (#17). **Operator
    analytics gated `reports.view`** (NOT Front Desk); **owner analytics owner-scoped** (their units only — resolved
    through `OwnerPortalService::units()` + owner-scoped `OwnerLedgerEntry`, a non-linked user fails closed).
    Reconciliation + owner isolation mutation-proven; the fabrication-absence tested. **Deferred honestly:** lead time
    + booking pace + channel-attribution depth (derivable but too thin in real data). (Phase 6b.)

40. **TENANT PROVISIONING IS ATOMIC + PROVISIONS ROLES (the chicken-and-egg, solved).** A brand-new operator org is
    created ONLY through `App\Services\TenantProvisioningService`, in ONE `DB::transaction` (all-or-nothing): the
    Organization (via `TenantContext::runAsSuperAdmin` — the pre-tenant escape, since `Organization` is the tenant
    boundary, not a `BelongsToOrganization` model) + its 13 provisioned roles (`OrganizationRoleProvisioner::provision`
    — org-creation now provisions roles, so the org is not dead-on-arrival under #26 — **RBAC DEFECT #1 FIXED**) + the
    founder `User` (hashed, active) + an active `Membership` + `assignRole('Property Owner')` on the org's spatie team
    (the founder holds a legitimate, ASSIGNABLE org-admin role — never the global `Super Admin`, a cross-tenant
    platform role a founder must never hold — **RBAC DEFECT #2 resolved for self-provision**) + the first `Property`. A
    mid-provision failure rolls back the ENTIRE org (no orphan org/user/roles/membership/property); a provisioned org
    is tenant-isolated both directions; the founder belongs only to their org. The public `/signup` surface is
    rate-limited, hardened (hashed password, session regenerated, no email-verification gate — the founder matches
    existing operators), and handles enumeration neutrally (org existence never leaks; a duplicate email fails
    non-committally). Mutation-proven. (Phase 7a.)

41. **SUBSCRIPTION BILLING — the trial is REAL, access is gated on the real state, the charge is ACCOUNT-GATED.**
    Vyrobel bills the operator on the free-trial model. A new org (7a) starts a real `trialing` subscription
    (`trial_ends = created + trial_days`; days DERIVED by `App\Support\Billing\SubscriptionStatus`, never a stored
    countdown), created atomically inside the 7a provisioning transaction. Plans/tiers live in config/billing.php
    with CONFIGURABLE PLACEHOLDER prices (Subhan sets real ones — never fabricated as final), materialised into the
    real `plans`/`plan_features` tables. **ACCESS GATE** (`EnsureSubscriptionActive` on `/app`): a LAPSED org
    (expired trial / canceled) → the Paywall; but the gate bites ONLY after the trial (in-trial/active = full — a
    founder is NEVER locked out mid-onboarding/mid-trial), FAILS OPEN on a missing subscription (a missing billing
    record must never paywall — the opposite of the fail-CLOSED security posture), and always lets the billing
    routes through (so a lapsed org can reach the Paywall). **FEATURE GATE** (`Organization::planAllows`): `channels`
    is Pro-only (Essential refused, Pro/Pro-trial granted), surfaced as a "Pro" nav lock. **🪤 THE LIVE CHARGE IS
    ACCOUNT-GATED** — the subscribe action takes no money, stores no card, and NEVER flips an org to `active`
    (`status='active'` + `stripe_subscription_id` are only ever reached through live Stripe Billing, deferred like
    the guest card charge + owner payout rail). Company/VAT are the org's own business identity (not card data).
    Billing is tenant-scoped + `billing.manage`-gated (founder/Property Owner, not Front Desk). Mutation-proven.
    (Phase 7b.)

42. **OWNER SUB-TENANCY (Owner Team) — nested isolation, COMPOSED not invented.** A sub-user ⊆ their owner ⊆ their
    org: the nesting COMPOSES the existing `OwnerScope` (owner boundary) + tenant scope (org boundary) — NO new scope.
    The 3 fixed owner-team roles (co-owner / accountant / view-only) gate the portal by a capability MATRIX
    (`EnsureOwnerCapability` route gate + `@ownercan` UI, both reading the ONE `OwnerTeamRole` matrix — not a privilege
    chain); the **payout account / IBAN is co-owner-ONLY** (money-security, #28). Team management is held by NO role —
    gated on `isPrimaryOwner()` (`EnsurePrimaryOwner`), so `canAssign()` refuses every sub-user and self-escalation is
    STRUCTURALLY impossible. The owner-realm invite reuses the hardened token discipline (sha256, single-use, expiring,
    no-enumeration) and is OWNER-SCOPED (can only land a sub-user under the invite's own `owner_id`); a removed
    sub-user's orphaned login is disabled. The capability gate COMPOSES with — never masks — the owner-scope
    fail-closed (it only 403s users WITH owner access). (Phase 7c.)
43. **TEAM SCHEDULE (rostering) — tenant + property scoped over REAL staff.** A shift lands on the SELECTED property
    (`PropertyContext::id()`, never `->first()`, never cross-property/org) and only on a real staff member
    (`StaffRoster` = active `Membership` ∩ a back-office role; owners/guests/fabricated names excluded). Managing the
    roster is manager-gated (`schedule.manage`, NOT Front Desk); viewing is broad (`schedule.view`). Time-off is a
    request→approval SEPARATION (a staff member requests their OWN; only a manager approves) and **self-approval is
    impossible** (`LeaveService` throws if actor == subject). A shift-on-approved-leave is a REAL computed conflict
    (assign-on-leave refused; a pre-existing clash flagged), never fabricated. The schedule PDF renders from the SAME
    `RosterWeek` builder as the grid, so it RECONCILES and is property-scoped. Shift-swap / labor-cost / coverage-AI
    are OUT of scope (deferred honestly, never faked).
44. **PRODUCTION EMAIL (Phase 8) — a real email NEVER sends from a non-production environment.** `MailSafetyGuard`
    refuses a real-sending transport outside `production` (downgrades to `log` at boot), so even `MAIL_MAILER=postmark`
    in a dev/CI `.env` can't reach a real person; the suite runs on a non-sending (`array`) transport. The Phase-3
    boundary HOLDS with the real transport — a provider send failure is caught + logged (never the address, #28) and
    NEVER rolls back the booking/notification (best-effort). Mail goes only to the correct OWN recipient. Provider
    secrets live in ENV only. The DNS records + the one controlled real send are the founder's cutover.
45. **LIVE STRIPE (money paths, test-mode-first) — built so it CANNOT charge wrong.** A real charge can NEVER happen
    outside `production` — a live `sk_live_` key is refused by `StripeSafetyGuard` (the fake client is bound in CI).
    The card NEVER touches the server (hosted Checkout — PCI SAQ A, no raw PAN). Every money path FULFILS ON THE
    WEBHOOK, never the redirect; every webhook is SIGNATURE-VERIFIED against the raw body (the account + Connect
    endpoints have SEPARATE secrets) and idempotent on `webhook_events(source,event.id)`. Double-money is impossible by
    MULTIPLE layers (a deterministic sending-side Idempotency-Key + the `event.id` unique index + `firstOrCreate`);
    **the owner payout CANNOT double-transfer** (unique Idempotency-Key + a `lockForUpdate` row-lock, re-checked
    pending inside the lock — proven twice WITHOUT and once WITH) and a transfer only ever goes to ITS OWN owner's
    connected account. The ledgers stay append-only / correct-by-reversal / integer money (#17, #27), reconciling. The
    LIVE-KEY FLIP is the founder's documented go-live step, never done in the build.
46. **THE OWNER ACCOUNT AREA (Profile & account + Security) — per-user, self-resolved.** The owner self-profile
    (name / login-email / avatar / language / password / 2FA / sessions / data-export) is PER-USER — resolved from
    `auth()`, NEVER a request id — so every owner-realm user incl. accountant / view-only SUB-USERS manages their OWN
    account. The owner-party / business details + payout links + team + DEACTIVATE are PRIMARY-OWNER-ONLY
    (`EnsurePrimaryOwner`, #42). A login-email change RE-VERIFIES the new address (a signed `temporarySignedRoute`,
    `{hash}` = `sha1(pending_email)`) BEFORE it switches. DEACTIVATE is a REVERSIBLE, NON-DESTRUCTIVE `users.status`
    flag (login is active-only; financials STAY; PII erasure remains the #28 `anonymize()` path). Sessions are the REAL
    `sessions` table (sign-out-others password-gated + rotates the remember-token). The protection checklist is DERIVED
    from true state, NEVER a fabricated score. All writes are controller POSTs (never `/livewire/update`).
47. **ANALYTICS DEPTH IS REAL (Batch B) — no fabricated LY / pricing / recommendations.** The per-unit + by-unit +
    booking-cadence analytics are REAL aggregates: the owner Statement **by-unit split** reconciles to the net to the
    cent (#17, owner-scoped #28); **per-unit analytics** (revenue · nightly rate · best-performer · 6-mo NET · 12-mo
    occupancy · channel mix · a real attention feed) run through the shared `OwnerUnitAnalytics`; operator + owner
    **Insights booking cadence** (lead-time distribution + median · day-of-week occupancy · on-the-books) runs through
    ONE shared `App\Support\Analytics\BookingCadence` (anti-drift). A flagged, demo-only backdated seed gives shape; the
    runtime computes real aggregates from real rows (proven by `BookingCadenceTest`). REFUSED: the recommendation /
    pricing engine + the "vs last year" deltas (no fabricated LY). Extends #39.
48. **OPERATOR MONEY-WRITES ARE IDEMPOTENT + GO THROUGH GUARDED SERVICES (Batch C).** On-desk operations wire EXISTING
    guarded services, never raw writes: housekeeping assign (`HousekeepingService::assign`), reservation move
    (`AssignmentService::assignUnit` — lock + no-overlap), add-charge (`FolioService::postCharge`, #17). BOTH
    money-write routes (the on-desk payment + add-charge) carry a per-submit server nonce backed by a nullable-UNIQUE
    `idempotency_key` (+ a pre-check + catch-the-race), so a duplicate — even two CONCURRENT POSTs bypassing the UI
    disable — records ONCE (mutation- + browser-proven). Tax & fees editing is bounded + `properties.manage`-gated.
49. **THE SUPER ADMIN PANEL IS A SEPARATE, ISOLATED REALM (the deferred Phase-7a item, resolved properly).** `/admin`
    runs on a NEW `admin` session guard over a separate `platform_admins` table (NO `organization_id`, NO spatie role)
    — a platform admin is NOT a tenant `User`, so tenant guards never resolve it and it never resolves a tenant user
    (the `guest`-realm discipline). `/admin` has NO ambient tenant context, so a stray tenant query there fails CLOSED.
    Every cross-tenant READ goes via `PlatformQuery::read()`, every tenant-scoped WRITE via `PlatformQuery::mutate()` —
    both admin-guard-gated + audited + wrapping `runAsSuperAdmin` with the scope restored in `finally` (never leaks; no
    new bypass). MANDATORY 2FA (TOTP), hardened rate-limited login, actions audited to `platform_admin_actions`, noindex
    + robots-disallow (path not subdomain), NO public sign-up (seeded via `php artisan admin:create`). Confirmed: there
    was never an `is_super_admin` flag on `users` (a test asserts its absence).
50. **PLANS ARE DB-BACKED + DRIVE FEATURE-GATING ADDITIVELY.** The `plans` / `plan_features` catalog is
    ADMIN-authoritative — CRUD (integer money #17) — and drives feature-gating via the existing
    `Organization::planAllows` (fail-open) + a reusable `EnsurePlanFeature` middleware: a GRANTING plan behaves EXACTLY
    as before (additive — no regression), only an EXCLUDING plan gates (a clean "not in your plan" page).
    Assign-a-plan-to-a-customer is audited + drives features. `BillingService::catalog()` reads features from the DB.
    Stripe billing sync (the `stripe_price_id` / subscription sync) is the deferred go-live seam.
51. **AUTH SIGNALS ARE CAPTURED BY ADDED LISTENERS — the tenant flow is NOT modified; privacy is built in.**
    Login / failed / lockout are captured by listeners ADDED on the framework auth events (`RecordAuthActivity` →
    `login_activity`) — the tenant login flow is untouched, so the capture can never break auth (operator/owner use
    `Auth::validate`, so their signal is the Lockout; guest fires Failed; the admin realm dispatches its own Failed).
    Privacy from commit 1 (GDPR): a 90-day retention prune (`platform:prune-activity`, scheduled), security-monitoring
    purpose, /admin-only, **no guest email persisted plaintext (#28)**.
52. **PLATFORM INTEGRATION CREDENTIALS ARE ENCRYPTED-AT-REST + ADMIN-MANAGED + NEVER ECHOED.** The founder's master
    channel credentials (Channex / OTAs) live in `platform_integrations`, cast `encrypted:array` (like the owner IBAN,
    #28 — the plaintext never lands on disk), write-only / masked (never echoed after save), managed via an audited
    action that records WHICH channel, never the value. The test-connection is an honest STUB (real sync account-gated,
    labelled honestly); the customer-self-connect model is documented, built later.
53. **A PLATFORM ADMIN'S ACCOUNT MANAGEMENT IS SELF-ONLY, RE-AUTHED, EMAIL-RE-VERIFIED, AUDITED.** Every action
    resolves `Auth::guard('admin')->user()`, never a request id. Password + email + recovery-code changes require the
    CURRENT password (`current_password:admin`). An email change is parked in `platform_admins.pending_email` and
    switched ONLY when a SIGNED, expiring link (`sha1(pending_email)`-bound) opened from the NEW inbox confirms it — the
    OLD email stays the login and the PENDING email CANNOT log in until verified (fail-closed on bad-signature /
    stale-hash / taken-email). 2FA recovery-code regeneration requires the current password. Every change is audited to
    `platform_admin_actions` (which field, never the value). Mirrors the tenant discipline, never weaker.
54. **THE OWNER OVERVIEW (`/owner`) MATCHES ITS WIREFRAME EXACTLY + IS FULLY OPERATIONAL ON REAL OWNER-SCOPED DATA.**
    Rebuilt to the decoded `Owner Dashboard.html` on the shared owner chrome, with every section wired through the
    SHARED owner derivations (anti-drift — the Overview cannot drift from My Units / Bookings): occupancy
    (`OwnerOccupancy::forMonth`, per PHYSICAL unit — the old "room-type-wide" deferral superseded), the attention feed
    (shared `OwnerAttention`), next-30-days upcoming stays (shared `OwnerUpcomingStays` — guest NAME-only #28, owner NET
    via `OwnerLedgerAttribution`, "Not yet attributed" never a fake €0), the masked IBAN + tax-hold paused rail. The
    interactive Source/Status filters are CLIENT-SIDE (Alpine) over server-rendered rows — a Livewire round-trip would
    bypass `SetOwnerScopeForPortal` and leak. Honest empties; no fabricated goal / estimated-date / occupancy (#39).
    Decision D2: the 5 extra REAL nav items the wireframe omits are KEPT (shared shell — no functional regression).
    Mutation-/render-proven (`OwnerUpcomingStaysTest` + `OwnerDashboardViewTest`; `assertNoRawBlade`).
55. **THE OWNER MY UNITS (`/owner/units`) + ADD UNIT (`/owner/add-unit`) MATCH THEIR WIREFRAMES EXACTLY + ARE FULLY
    OPERATIONAL ON REAL OWNER-SCOPED DATA.** My Units: real card/calendar actions, each on a gated controller route —
    edit-pricing via `OwnerRateService`/`RateAndRestrictionService` (per-ROOM-TYPE, exclusive-owned only; a shared type
    is honestly non-editable, no fake save; browser-proven 180→185 persisted), message-relay to the operator
    (`owner.messages.start`, name-only #28), block-dates via `OwnerBlockService`, month-occupancy CSV export (counts-only
    #28, `exports`-gated); the calendar heat-grid uses the wireframe terracotta scale. Add Unit: the exact 4-step wizard
    + Live preview + Done, on a rich real pending `UnitRequest` (amenities via the shared `amenitiables` pivot, photos
    via the shared `media` collection with ULID filenames), the write ONE controller POST (never `/livewire/update`,
    #20), commission the owner's REAL agreement rate (an honest line if none); a request creates NO inventory — the
    operator reviews it (and sees the rich detail) before creating the real unit. Integer money (#17), guest
    name-/counts-only (#28), nothing fabricated — a proposed request is badged "Pending review", never a live/priced
    unit (#39). Proven end-to-end in the browser; `OwnerMyUnitsViewTest` + `OwnerUnitRequestTest` (6→12).

56. **OWNER BOOKINGS (`/owner/bookings`) + THE BOOKING-SUMMARY PDF MATCH THEIR WIREFRAMES EXACTLY ON REAL DATA.** The two
    items once refused as fabrication are now REAL (stale-refusal re-verification): the "Bookings over time" chart (real
    `reservations.status` stacks + net via `OwnerLedgerAttribution` + a goal line) and the "Monthly goal" ring (real net ÷
    an owner-SET `owner_monthly_target`; honest "no target" when unset). Status filter, date-range, sortable columns,
    pagination, a name-only CSV export, and the `$capActions` target-write (controller route, currency server-derived, #20)
    — page + export share the ONE `OwnerBookingsReader`. The PDF gained real owner-scoped occupancy, the guest NAME-only
    column (`GuestName`, #28), real channel commission (`ota_cost`), a VAT letterhead; Σ(rows)==gross. `OwnerBookings*`
    tests; adversarial-reviewed.

57. **OWNER STATEMENT (`/owner/statements/{id}`) + THE STATEMENT PDF MATCH THEIR WIREFRAMES EXACTLY ON RECONCILING DATA.**
    Two stale refusals (predating #54) are now REAL: the occupancy card + mini bar chart (shared `OwnerOccupancyTrend`) and
    the masked payout IBAN (shared `OwnerNextPayout`, CAP_PAYOUT-only #28). Month switcher, plain-glass hero, gross
    "N nights · M units" sublabel, statement-history Export CSV + per-year/per-row PDF (`owner.statements.history.*`,
    CAP_EXPORTS), by-unit unit links. The PDF is the letterhead statement (net-payable headline + Occupancy/Nights/ADR +
    3-col breakdown + Performance-by-unit + payout callout). Page + PDF share the ONE `OwnerStatementBreakdown`; breakdown
    Σ==net + by-unit Σ==net/gross to the cent (#17); NO guest data (#28); no invented fees/date (#39).

58. **OWNER PAYOUTS + PAYOUT METHOD MATCH THEIR WIREFRAMES — REAL WHERE REAL, HONEST WHERE ACCOUNT-GATED (⚠️ real money).**
    REAL: payout history/KPIs/reconciling breakdown/remittance PDF · the masked payout IBAN (`OwnerNextPayout`, #28,
    CAP_PAYOUT-only) · the **bank-account CRUD** — `PayoutMethod` with an ENCRYPTED `iban`, ISO **mod-97-validated**,
    self-only, `$capPayout` · **Stripe Connect** onboarding (`OwnerConnectController`, **test-mode**, + a signature-verified
    Connect webhook) · a real payout-preferences store · payout-history Export CSV · inline Tax details via the ONE
    `owner.tax.save`. **HONEST account-gated (exact design, labelled pending-live, NEVER faked):** micro-deposit
    verification (no fake Verified/deposits), live transfer, SEPA trace, the 48h hold. **The automated payout
    EXECUTION/DISBURSEMENT engine is DEFERRED** (account-gated fintech; `ManualPayoutRail` is the real tested path). NOTE:
    the page DOES collect encrypted IBANs — it is not a no-IBAN honest-summary-only design.

59. **OWNER BALANCE + EXPENSES MATCH THEIR WIREFRAMES ON REAL / HONEST DATA (⚠️ Balance is real money).** Balance is a REAL
    reconciling **clearing model** — Available (unstamped `owner_ledger` net) + On hold (tax-held pending payouts) +
    In transit (released pending) + Paid == Net position, per currency **to the cent (#17)** — with a per-row state chip,
    the tax-hold "Why is €X held?" card, the masked IBAN (CAP_PAYOUT #28), a real Export CSV. **"Withdraw early" is honestly
    DISABLED** (no owner-initiated transfer rail — never a fake movement/"in transit", #39). Expenses: real KPIs (Spent /
    Pending review / Charged) + a real Unit column/filter (**vendor REFUSED — unmodelled**) + Export CSV + a real
    repair-relay (`owner.messages.start`); the **approve/query workflow is REFUSED** (no owner-approval state machine —
    deductibility is set by the agreement). By-category bars. `OwnerBalanceViewTest` (6) + `OwnerExpensesViewTest` (5).

60. **OWNER BLOCK CALENDAR + DOCUMENTS MATCH THEIR WIREFRAMES ON THE REAL BACKENDS.** Calendar: the interactive grid —
    click a day or press-and-drag a range (Alpine) → a REAL block POST (`OwnerBlockService` → the certified
    `AssignmentService`, non-overlap enforced, `$capActions` #20, audited); click a blocked day → a REAL release; booked
    cells show the guest NAME only (#28). **REPEAT/SERIES expands into N REAL individual blocks sharing a new nullable
    `InventoryBlock.series_id`** (never a fake recurring rule, #39); an over-taken occurrence is skipped + reported; a
    toast + Undo. Documents: month cards + year-filter + a DARK annual-tax card with the REAL net (Σ `net_source_minor` =
    the annual PDF's basis, reconciling #17); the archive is the real on-demand PDFs, CAP_EXPORTS-gated. `OwnerBlockCalendarTest`
    (10) + `OwnerDocumentsViewTest` (6); block/release/series mutation-proven + browser-proven.

61. **OWNER REPORTS + INSIGHTS MATCH THEIR WIREFRAMES ON THE REAL BATCH-B ANALYTICS.** Reports: KPIs + net-by-month +
    per-unit ADR/RevPAR/share + by-channel (real `reservations.source`) + the **Gross-to-net** breakdown (reconciles to the
    cent #17 at the **REAL agreement commission rate — read from `ManagementAgreement` via the shared `OwnerCommissionRate`,
    NEVER the wireframe's hardcoded 15%**) + payout summary + real Documents + CAP_EXPORTS exports; the **Tax & VAT summary
    is HONEST-DEFERRED** (VAT not modelled — no fabricated figure/"Mark filed" #39). Insights (2-col): occupancy-by-month
    (this-year + on-the-books; **vs-last-year honest-omitted** — no prior-year data) + booking-pace + rate-performance +
    lead-time + day-of-week (+ a real derived caption) + **"Worth a look" REAL DERIVED observations**; the **"rate
    proposals" callout is HONEST** — a real link to the Rates page, NO fake proposal entity/workflow. `OwnerAnalyticsTest` (7).

62. **THE OWNER SETTINGS HUB (`/owner/settings`) MATCHES ITS WIREFRAME — 13 SECTIONS, EACH REUSING A REAL BACKEND (ANTI-
    DRIFT) OR HONEST WHERE ACCOUNT-GATED.** REUSED: Profile (`OwnerAccountController`) · Business & tax (`owner.settings.update`
    + the ONE `owner.tax.save`) · Payout account (real masked IBAN, CAP_PAYOUT #28) · Team (summary + the real `owner.team`) ·
    Notifications (`owner.notification-preferences` — in-app + email are the REAL channels) · Data export (the CAP_EXPORTS
    exports #28) · Security (→ `owner.security`) · Reduce-motion (the layout honors `prefers-reduced-motion`); Hero +
    completeness are DERIVED. **HONEST-gated (never faked #39/#37):** Billing shows the REAL management-fee rate (shared
    `OwnerCommissionRate`, 18% — never 15%) with NO owner card/subscription (that's the operator's); OTA channels are
    honest-**Pending** (Direct is the one live channel); **SMS + push are honest-pending** (no provider); currency/timezone
    are the property's (read-only). Every write is a guarded controller POST (#20). `OwnerSettingsTest` (7) +
    `OwnerAccountProfileTest` updated.

## Deferred / outstanding register (do NOT build ad hoc; see DECISIONS.md)

### ⚠️ SCHEMA-ONLY: ~8 models whose tables EXIST but which no code wires (read before building "a new module")

Verified 2026-07-17 — each has a migration **and** an Eloquent model, and **zero references from
`app/Services`, `app/Http`, `app/Livewire` or `app/Actions`**: `Subscription` · `SubscriptionItem` · `Plan` ·
`PlanFeature` · `UsageCounter` · `ApiKey` · `Webhook` · `WebhookDelivery` · `CorporateAccount` · `Group` ·
`Invoice`. They are column-level specified in `docs/schema/phase-1.md`.

**Why this block exists:** the register below calls Billing a module with "NO backend today", and Wave 4 calls
"New/Group Booking" net-new. **That is true of services and routes and FALSE of schema and models** — and it
is wrong in exactly the direction CLAUDE.md's *"Reuse, don't duplicate — never build a parallel structure for
an existing concept"* rule exists to prevent. A future Billing module must extend `subscriptions`/`plans`; a
Group Booking screen must use the existing `groups` table and `reservations.group_id` FK; a public API must
use `api_keys`/`webhooks`.

⚠️ **NAMING TRAP:** `webhooks` + `webhook_deliveries` (tenant OUTBOUND webhooks — inert) are **not**
`webhook_events` (INBOUND Channex idempotency — real, load-bearing, invariant #11). Do not conflate them.

---

### ✅ CLOSED (2026-07-17) — the three 🔴 security/integrity items, and the flaky test

The Wave 2 register is **cleared of its security/integrity items**, done BEFORE Wave 3 so new screens inherit
correct enforcement rather than needing a retrofit. Full detail: `docs/DECISIONS.md` 2026-07-17.

1. ✅ **`terminate()`'s missing internal guard** → **invariant #29**. `f4a8913`. The audit found it was worse
   than logged: there were already **two** callers and they **disagreed** (the OTA webhook path let
   `checked_out` through). One `TERMINAL_STATUSES` definition, guarded in the shared private `terminate()`.
2. ✅ **The unenforced permission catalog** → **invariant #30**. `9123ee7`. 7 routes + the refund screen now
   enforce `can:<permission>`; `payouts.manage` added (catalog 44 → 45). ⚠️ The headline turned out to be
   **privilege escalation** (Housekeeping could mint itself an Accountant), not the refund.
3. ✅ **`Guest::anonymize()` had no UI trigger** → folded into **#28** + **#30**. `94ef7ad`. It was called from
   NOWHERE; now a `guests.erase`-gated, server-confirmed trigger on the guest profile.
4. ✅ **The flaky test** — root-caused, not shrugged off (see below).

### ✅ CLOSED (2026-07-17) — BOTH flaky tests: the same disease, and neither was a product bug

`OwnerStatementPdfTest::"renders only real ledger lines"` asserted `toContain($owner->name)` — the **raw**
faker name — against **Blade-rendered (escaped) HTML**. `OwnerFactory` uses `faker->name()`, and **1.68% of
faker names contain an apostrophe** ("Abelardo O'Conner" → `O&#039;Conner`), so ~**1 run in 6** failed on a
page that was **perfectly correct**. Fixed to assert `e($owner->name)`; **proven deterministically** by
forcing a real apostrophe-bearing faker name (old assertion fails, new one passes).

**THE SECOND ONE (found in Wave 3), same disease:** `BackOfficeAccessTest` asserted
`assertDontSee($unit->code)` where `seedScenario` codes units **`U2`** — *two characters* — against owner
pages carrying **three random 40-char CSRF tokens** (`@livewireScripts`). `Str::random(40)` is alphanumeric,
so it contains "U2" **~1.05% of the time** → **~15% per run** across 8 routes. Fixed with a leak-proof needle
(`BRUNOUNIT9`) — the repo's own `FOREIGNUNIT9` prior art — and proven deterministically.

**THE LESSONS, worth more than either fix:**
1. **BOTH were the same disease: testing the NEEDLE, not the BEHAVIOUR.** A raw faker name vs escaped HTML;
   a 2-char code vs random tokens. In both, the app was perfectly correct and the assertion was wrong.
   **When a test flakes, suspect the assertion's specificity before you suspect the app.**
2. **A needle must be one that could ONLY appear by the bug.** `assertDontSee('U2')` cannot distinguish a
   leak from a coin flip. `assertDontSee('BRUNOUNIT9')` can.
3. **CAPTURE the failure; do not re-run until it goes away.** (`php artisan test > run.json 2>&1`.) The four
   true-OS-concurrency suites were the obvious suspects for the first flake and were **innocent** (6/6 green
   under targeted runs) — re-running would only ever have hidden it.

### 🔴 STILL OPEN — the register's remaining items

1. ✅ **DEAD enum values — RESOLVED in Phase 0.2 (2026-07-20), except one low-priority remnant.**
   | table.column | was dead | resolution |
   |---|---|---|
   | **`reservations`**`.payment_status` | `refunded`, `charged_back` | ✅ `refunded` now **WRITTEN** (`FolioService::paymentStatus()` — a fully-refunded stay); `charged_back` **REMOVED** from the enum (no dispute flow). Mutation-proven (`DeadEnumResolutionTest`). |
   | `folios.status` | `closed`, `void` | ✅ **REMOVED** from the enum (no folio close/void lifecycle; re-add WITH enforcement when `FolioService::closeFolio()` is built). |
   | `payments.status` | `refunded` | ⚠️ **STILL dead but harmless** — a refund creates a NEW `type='refund'` row (never restamps the original), and no UI keys on it. Drop in a future cleanup (out of 0.2's scope, which was the two above). |
   The lesson stands: **verify any status-keyed UI against what the code actually produces**, not the enum the migration declares.

2. **THE CATALOG HAS NO INBOX PERMISSION — logged, not invented** (found 2026-07-17 while enforcing #30).
   `InboxActionController` performs six real writes (reply — which sends a **guest-facing message** — plus
   assign/close/reopen/snooze/link-reservation) and there is **no `inbox.*` / `messages.*` permission in the
   catalog to gate them on**. They remain protected only by the coarse #26 door. Deliberately **not** invented
   during the enforcement gate: naming a permission is a product decision about who may speak to a guest, and
   inventing one to make a table look complete is exactly the fabrication the honesty rule forbids. Decide the
   name + role grants in a dated `docs/DECISIONS.md` entry, then enforce.
   *(The other deferred low-risk surfaces — housekeeping / maintenance / photos / guest notes+tags — DO have
   real permissions and simply were not in the first enforced set. The inbox has none at all.)*
   ⚠️ **Wave 3 widened this, and did not narrow it:** the OWNER inbox (`/owner/messages`) is gated by **#26 +
   the owner scope** (an owner can only ever reach their own threads — invariant #18, mutation-proven), and
   the operator's replies to owner threads run through the same unguarded `InboxActionController`. So the gap
   is unchanged in kind: still no `inbox.*` permission, still a product decision about who may speak to a
   guest or an owner. Still logged, still not invented.

3. ✅ **The two RBAC defects from the 2026-07-16 audit — BOTH FIXED in Phase 7a (2026-07-21), mutation-proven.**
   They were fail-closed and never exploitable, and were formally deferred to "the gate that builds org creation"
   — which is Phase 7a (tenant provisioning).
   - ✅ **Defect 1 (provisioning) — FIXED.** `App\Services\TenantProvisioningService` calls
     `OrganizationRoleProvisioner::provision()` atomically inside the org-creation transaction (the provisioner's
     first real caller), so every self-signup org gets its 13 team-scoped roles and is NOT dead-on-arrival under
     #26. Proven: a provisioned org has ≥13 roles and its founder can manage.
   - ✅ **Defect 2 (Super Admin) — RESOLVED for self-provision.** The founder holds the ASSIGNABLE, team-scoped
     `Property Owner` (org admin, `['*']` within the org) — the legitimate top role for a tenant founder — never
     the global `Super Admin` (a cross-tenant platform role a founder must never hold — that would be an
     over-grant). **RESIDUAL, re-scoped as a SEPARATE deferred PLATFORM item (NOT a self-provision defect):** the
     global `Super Admin` stays unassignable (`model_has_roles.organization_id` is NOT NULL and part of the PK;
     MySQL forbids NULL in a PK, so making it assignable needs a surrogate-PK rework of spatie's OWN table). That
     affects only the Horizon/platform-admin gate — no tenant founder needs it — so it stays deferred to whenever
     the spatie schema is next reworked; `Super Admin` remains out of `back_office_roles` until then. (See
     `docs/DECISIONS.md` 2026-07-21, Phase 7a.)

4. ✅ **THE CHANNEL MANAGER — no longer inert; the `channels.status` writer shipped in Phase 0.1 (2026-07-20).**
   `App\Services\ChannelConnectionService` is the real writer (`connect()` → `connected`, `activateMapping()` →
   `active` — the exact values the ARI push + mapping guard gate on), recording audit + domain events inside the
   service. The `DemoSeeder` now seeds a connected channel + active mappings, so `migrate:fresh --seed` produces
   a non-empty, ARMED channels table. Mutation-proven (`ChannelConnectionTest`: a disconnected channel enqueues
   NO push; after `connect()` a booking pushes exactly once). ⚠️ **What remains is the operator-facing connect
   FLOW: the Channels UI (Phase 1) that lets a real operator create + connect a channel through this writer**
   (+ the `channels.manage` permission), and the live Channex sync itself (account-gated). The backend is no
   longer inert; the UI to drive it in production is Phase 1 (see `docs/GAP-ANALYSIS.md`).

### ✅ CLOSED (2026-07-16) — the `/app` authorization hole. Safe for a real owner login.

The critical item that stood here is **FIXED** (`5155a48` route gate → `f63866a` root cause). An owner-role
user could reach the entire operator back office and every other owner's financials; both layers are now in
place and each is proven to block independently. See **invariant #26** below and `docs/DECISIONS.md`
2026-07-16. Two smaller defects surfaced by that audit remain open — they are recorded in "Other deferrals"
below (**no org-creation path provisions roles**; **`Super Admin` is unassignable**), and neither is
exploitable (both fail closed).

---

**AT A GLANCE — the honest four-way triage a fresh session needs.** The buildable-in-a-vacuum, clearly-worth-it,
self-contained core is **essentially complete** (2FA was the last such subsystem). What remains splits cleanly:

**① REGISTER items — MOST CLOSED in Phase 0 (2026-07-20); detail above.** ✅ **The channel manager is no longer
inert** — the `channels.status` writer shipped (0.1). ✅ **The dead enums are resolved** — `payment_status`
now writes `'refunded'`, and `charged_back`/`folios.status='closed'`/`'void'` were dropped (0.2); one harmless
remnant (`payments.status='refunded'`) documented. ⏸️ **The two fail-closed RBAC defects** (no app-level
org-creation provisions roles → Phase 7; `Super Admin` unassignable → a spatie-schema migration) were reviewed
and **formally deferred** to their fix gates (0.3). Still genuinely open: the **inbox has no catalog permission**
(its writes are #26-only — a product decision, deliberately not invented). None blocking.

**② SUBSYSTEMS / EPICS — buildable now (no external account), but REAL commitments, NOT quick wins.** Each is a
gated phased build with a genuine backend, exactly like Owner Accounting was:
- **Self-signup / tenant-provisioning** (the org-creation epic — and the chicken-and-egg with the RBAC defect
  above: no path provisions roles, so it must `runAsSuperAdmin` outside `/app`).
- **Guest auth** (`Guest` is NOT `Authenticatable` today — a whole auth surface, separate from operator/owner).
- **Sessions / connected-apps / IP-rules** (the "More security" stubs — session-tracking + OAuth-app store + an IP engine).
- **Portfolio** (multi-property subsystem) · **Owner Team** (owner sub-tenancy) · **Group Booking** (atomic
  group write — extend the EXISTING `groups` + `reservations.group_id`, never a parallel table) · **Owner Rates**
  (needs owner-scope rate schema) · **Channels-as-drawn** (CRUD + the missing `channels.status` writer + a live
  connect flow — see register item 4) · **Roles** (a security redesign, needs the RBAC defects resolved first).
  Per-screen readiness + traps: `docs/WAVE4-INVENTORY.md`.

**③ SPECULATIVE MODULES — designed, ZERO backend, NO customer signal. Do NOT build blind (per the ROADMAP).**
**Guest Portal** (~24 authenticated screens — the largest bet; ROADMAP is authoritative, `CATALOGUE.md` counts
32 by including guest-auth/other guest screens) · **Analytics / reports** (ADR/RevPAR/insights) · **Reviews /
reputation** · **Subscription / billing / paywall** (must extend the schema-only `subscriptions`/`plans`) ·
**Notifications** · transactional **email templates** (~9). Smaller in-product items: CSV / remittance-advice /
booking-summary exports; room descriptions + reviews.

**④ ACCOUNT-GATED — cannot be built until an external credential is obtained (blocked in a vacuum).**
- **Real inbox channels** — email (inbound-email provider e.g. Postmark/Mailgun + verified sending domain) ·
  whatsapp (Meta WhatsApp Business API approval + a BSP/Twilio number) · booking_com/airbnb (OTA messaging API).
  The `manual` channel + the whole interface work now; each adapter is an honest throwing skeleton.
- **Channex staging** — real ARI/webhook/HMAC vs a Channex sandbox account + a public tunnel (fake fully tested).
- **Stripe Connect payouts** — owner disbursement (connected-account onboarding + KYC). `ManualPayoutRail` is real+tested.
- **Stripe card checkout** — live card entry (Elements + 3DS) needs live/test keys; New Reservation's card capture
  waits on this; manual/pay-on-arrival is the real path now.
- **Production email delivery** — a real transactional provider (Postmark/SES). Dev/demo = Mailpit (wired).

**Design is NOT a bottleneck.** `docs/design/CATALOGUE.md` is the authoritative per-file inventory
(**125 files / 124 screens** — reconciled 2026-07-16; the denominator the ROADMAP's "~42 of ~124" rests on).
Most catalogued screens map to the ② / ③ modules above.

---

**TWO live-integration checkpoints (money/network rails — the only unproven-against-reality items):**
- **Channex staging** — ARI push shape + inbound webhook payload + HMAC scheme are **documented
  guesses** (validated only vs `FakeChannexClient` + simulated signed payloads). Blocked: needs a
  Channex staging account + a public tunnel.
- **Stripe Connect payout rail** — real owner money movement (connected-account onboarding + KYC).
  `StripeConnectPayoutRail.send()` **throws** (config-gated skeleton); **`ManualPayoutRail` is the real,
  tested disbursement path.** Blocked: needs Connect credentials.

**Design-pass deferrals (flagged in-product, backend-gated — NOT fake; presentation only):**
- **Stripe card entry at checkout** (Stripe Elements + 3DS) — the public checkout wires **manual /
  pay-on-arrival** through `FolioService` (real, append-only); real card capture is tied to the deferred
  Stripe live checkpoint. No fake card field.
- **Room photos — DONE** (no longer deferred). Real operator uploads on the shared `media` table (collection
  `photos`, `HasPhotos` on RoomType); secure upload (content-validated images, ULID filenames, size cap,
  tenant-scoped) on the Operator Units screen; rendered on the storefront (cover + gallery) + unit cards,
  with the **gradient fallback kept** for no-photo rooms. Storage = the local **`public` disk** in dev
  (`storage:link`); **S3/cloud is the production path** (flagged, not hardcoded). **Descriptions + reviews
  stay deferred** (no schema).
- **The remaining design-only screens** — most of the ~120-file Claude Design library (`docs/design/
  CATALOGUE.md`) maps to FUTURE modules with **no backend today** and were deliberately NOT built: the full
  **guest portal** (~24 per ROADMAP), **reviews**, **subscription billing / paywall**, **transactional emails** (~9), and
  **analytics** (Reports/Insights, ADR/RevPAR trends). Do NOT build backend for these ad hoc — each is a gated
  module. *(The **Maintenance** and **Unified Inbox** modules are now COMPLETE end-to-end — backend + styled
  UI — so they're no longer in this future list.)* *(`docs/design/CATALOGUE.md` is the authoritative per-file
  inventory + backend-status legend — read it before starting any new screen.)*
- **PDF export — DONE** (no longer deferred): the **owner statement** (owner-scoped) and the **operator
  folio** (tenant-scoped) both download as branded, PDF-safe documents via `barryvdh/laravel-dompdf` +
  `App\Services\PdfService`, reconciling to the on-screen figures **to the cent** (shared `MoneyFormatter`
  + `FolioTotals`). **Still deferred:** **CSV** export, the **remittance-advice / booking-summary** PDFs,
  and folio **print** — honestly flagged "Soon".
- **Per-screen deferred affordances** are logged in each screen's DECISIONS entry — e.g. rack
  drag-to-reschedule; housekeeping drag + assign-housekeeper (no assign method); reservation-detail
  add-charge / void / partial refund / move-room / cancel; dashboard revenue-trend / ADR / RevPAR;
  owner-portal IBAN/payout-method, approvals/action-center.

**Auth deferrals (Phase A + B complete; these are flagged, not faked — see DECISIONS.md 2026-07-13):**
- **Manager / Accountant invites — WIRED** (no longer deferred). The invite flow now covers **owner +
  Hotel Manager + Accountant** on the same secure machinery. Owner → owner link + `/owner`; STAFF →
  Membership + a real spatie catalog role (`Hotel Manager` / `Accountant`) → `/app` (no owner/agreement/
  units). Role assignment is fail-safe (mapped + provisioned-for-org, validated at invite AND accept).
- **Email DELIVERY** — **DEV/DEMO is now wired to Mailpit** (`brew install mailpit`; run `mailpit` → SMTP
  `127.0.0.1:1025`, UI `http://127.0.0.1:8025`; `.env` `MAIL_MAILER=smtp`, `MAIL_PORT=1025`). A real
  `OwnerInviteMail` lands in the Mailpit inbox with the tokened `/invite/{token}` link clickable
  (browser-verified 2026-07-13). `.env.example` defaults to `MAIL_MAILER=log` (zero-dependency) with the
  Mailpit switch documented inline. **PRODUCTION still needs a real transactional provider**
  (Postmark/SES/etc.) — **Mailpit is dev/demo only.** The flow was always complete; only the prod
  transport remains deferred.
- **2FA (TOTP) — ✅ BUILT & CERTIFIED (2026-07-18), no longer blocked.** Opt-in per user: enrollment
  (confirm-before-activate) + one-time recovery codes + the login challenge, secrets/codes **encrypted**
  (fixing the pre-existing plaintext `two_factor_secret`), disable-requires-re-auth, self-only, rate-limited.
  The pending state grants nothing by construction → **invariant #31**. `google2fa` + `bacon-qr-code`, NOT
  Fortify. Proven by `TwoFactorEnrollmentTest` (12) + `TwoFactorChallengeTest` (8); browser-verified both
  portals. See DECISIONS.md 2026-07-18. (Password reset + change password were done earlier — see the auth
  chapter above.)

**Maintenance module — COMPLETE end-to-end (backend certified + styled UI, design pass 2026-07-14).**
Nothing deferred for this module. (Was: "styled maintenance UI" + "manual-move flag surfacing" — **both now
DONE**: the styled board + work order ship the two integrations, and the work order surfaces the manual-move
flag as the real active-booking count on the blocked unit, linking to the existing move-room/rack path. The
actual room move still routes through the existing guarded `AssignmentService` move path — never auto-cancelled.)

**Unified Inbox module (COMPLETE end-to-end — backend + styled UI, in the operator nav; see DECISIONS.md
2026-07-14). The ONLY remaining deferral is the real external channels:**
- **Real external channels** — the `email`/`whatsapp`/`booking_com`/`airbnb` adapters are config-gated
  `DeferredChannel` skeletons that **throw "not configured"** (never faked; the styled composer honestly shows
  "isn't connected yet" per channel). What unblocks each: **email** — an inbound-email provider (Postmark/
  Mailgun inbound parse) + a verified sending domain; **whatsapp** — WhatsApp Business API approval + a BSP
  (Twilio/Meta) number; **booking_com / airbnb** — OTA messaging API access (Channex messaging or the OTA
  partner API). The internal **manual** channel works today. (Was also deferred: the styled inbox UI + adding
  it to the nav — **both now DONE**.)

**RBAC defects surfaced by the 2026-07-16 authorization audit (both FAIL CLOSED — not exploitable; fix when
the area is next touched):**
- **No application-level org creation provisions roles.** `OrganizationRoleProvisioner` is called ONLY by
  `DemoSeeder` + two tests — there is no `Organization::create` anywhere in `app/`. So any org born outside
  the seeder would have **zero roles**, and the `/app` back-office gate (invariant #26) would lock out every
  user in it. Moot today (no org-creation path exists at all); **wire provisioning into org creation when
  that lands**, or the first real org is dead on arrival. This is also why owner-role assignment is
  non-throwing (an owner must not be blocked by an org-setup gap).
- **`Super Admin` is unassignable, so the Horizon gate can never pass** outside `local`.
  `model_has_roles.organization_id` is `NOT NULL` **and part of the PRIMARY KEY**, so a global (team-null)
  role assignment cannot be stored, while the team-null lookup filters on `organization_id IS NULL` —
  `hasRole('Super Admin')` is therefore always false. Fails closed (safe). Zero such assignments exist. Fixing
  it needs a migration (nullable pivot team column + PK rework) — do NOT do it casually; it is spatie's own
  schema. `Super Admin` is deliberately absent from `back_office_roles` until then (untestable dead code in a
  security gate is worse than an honest note).
- **The suite needs `memory_limit=512M`** (set in `phpunit.xml`). Verified headroom, NOT a leak: dompdf embeds
  a full DejaVu font per PDF-export test, and every operator test provisions its org's 13-role × 44-permission
  catalog. `php artisan test` spawns a child PHP that re-reads php.ini, so a `-d memory_limit` on the parent
  never reaches it — hence the phpunit.xml setting. `tests/Support/auth_helpers.php` memoizes provisioning
  per org to keep the cost down.

**Other deferrals:** promo codes + deposits on the booking engine (schema only); `guests.stripe_customer_id`
/ saved cards (blind-index story); **auto-FX provider** (statements take an operator-supplied rate — no
silent float); plus standing Phase 1 deferrals (blind-index guest matching,
gap-free invoice numbering, dated out-of-order ranges, `inventory_blocks.quantity`, tenant-custom
amenities, same-day-turnover housekeeping dedupe).

## Regression net — `php artisan test` (MySQL `vyrobel_test`)

**819 tests / 6910 assertions, all green on MySQL 8. "Green here means the whole guarantee stack holds"** — Phase 1
(double-booking/tenant/ledger/GDPR/event-discipline), Phase 2 (cross-system contention/sync-reliability/
payments), Owner Accounting (attribution-to-the-cent/append-only-reversal/period-locking/owner-isolation),
**the phased build** (Phases 0–5: operator + owner buildable sets · the notification subsystem · the whole
Guest Portal [auth realm + Trips/Reservation/Manage/Check-in/Arrival + Payments/Wallet/Messages/Saved] · the
Reviews module), and the **mutation-verified security/isolation suites** — authz (#26) · refund bound (#27) ·
2FA (#31) · guest realm-separation + guest isolation (#32/#33/#35) · the wallet ledger no-overdraw (#34) ·
owner isolation · reviews tie-to-real-stay + moderation + owner-review isolation (#36/#37) · permission
enforcement (#30) — **each proven to FAIL without its guard** (the mutation is applied, the test goes red, the
guard is restored). Also covered:
**Auth Phase A + B** (`AuthLoginTest` · `OwnerInviteBackendTest` · `OwnerInviteAcceptTest` ·
`OwnerInviteEmailTest` · `OperatorInviteOwnerTest` · **`StaffInviteTest`** — Hotel Manager / Accountant
activation → `/app` + real spatie role, no owner artifacts, fail-safe on unprovisioned/unknown role · and
**`PhaseBEndToEndTest`** — one continuous invite→email→activate→portal→token-spent chain with the
hashed/single-use/no-enumeration security recap),
the owner-portal design pass (per-screen render/isolation + occupancy-correctness), the operator design
pass (5 screens), and the public booking storefront (`PublicBookingBrowseTest` · `PublicBookingRoomTest` ·
`PublicBookingCheckoutTest` — real sellable rooms/prices with NO auth, public property isolation, and the
**public checkout WRITE: real book via AssignmentService, advisory hold never changes `sold`, two-holds-
one-books with no overbooking**), together. The true-parallel no-double-booking suite (`ConcurrentBooking`
/`Deadlock`/`CrossSystemConcurrency`, 9 tests/114 assertions) re-run green after the checkout screen.
- **TRUE OS-level parallelism** (separate PHP processes + MySQL connections, shared future-timestamp
  barrier, committed data, no RefreshDatabase): `ConcurrentBookingTest`, `DeadlockAvoidanceTest`,
  `CrossSystemConcurrencyTest` (repeat-runs zero-flake).
- **Real Redis + time-travel** (no real sleeps): `SyncReliabilityTest`, `AriPushTest`, cross-system retry.
- **In-memory fakes, no network** — `FakeChannexClient`, `FakeStripeClient` (real HMAC) back the rest;
  **Stripe additionally validated live once** (test-mode, 2026-07-03).
- **Owner Accounting suite (45 named tests):** `tests/Unit/MoneyAllocateTest` + `tests/Feature/`
  `OwnerAttributionTest`, `OwnerExpenseTest`, `OwnerStatementTest`, `OwnerPayoutTest`,
  `OwnerPortalIsolationTest` (+ shared `tests/Support/owner_helpers.php`).
- **Real auth security tests** — `AuthLoginTest` (active-only, rate limit, no enumeration, role-routing,
  session) + the invite suite; `PhaseBEndToEndTest` proves the whole invite chain as one continuous
  browser-less flow (hashed/single-use token, no-enumeration, hashed password, auto-login, owner-scope);
  `PasswordResetTest` (no-enum, active-only, single-use, expiring, rate-limited, hashed round-trip);
  `PruneExpiredInvitesTest` (conservative prune + untouched/idempotent/tenant-safe cases).
- **Tier 1 finishing-sweep tests** — `CheckoutDirtiesUnitTest` (checkout→unit dirty via the real
  service/event chain, real dirty→clean cycle, idempotent) · `UnitFactoryContextTest` (factory respects
  tenant context + explicit-override).
- **Demo-readiness tests** — `RoomPhotosTest` + `RoomTypePhotoUploadTest` (photos on the media table;
  secure upload — rejects non-image/disguised/oversized; cross-org 404; display + gradient fallback) ·
  `OwnerStatementPdfTest` + `FolioPdfTest` (branded PDFs reconcile to the cent; owner/tenant-scoped
  download → cross-owner/cross-org 404; no invented lines; PDF-safe).
- **Maintenance module suite** — `MaintenanceTicketTest` (guarded lifecycle) · `MaintenanceOutOfServiceTest`
  (OOS excludes from availability + returns; two-tickets; cross-unit isolation; negative over-committed with
  no oversell; never auto-cancel) · `MaintenanceCostExpenseTest` (owned → owner_expense via the certified
  path + reconciles; operator-owned → none; append-only reversal; period-lock) · `MaintenanceModuleTest`
  (tenant-scoped render + cross-org 404; end-to-end tie-together). **Styled-UI design pass:**
  `MaintenanceBoardViewTest` (real lanes/fields; OOS badge + rail; real create with the OOS side-effect;
  foreign-unit 404; assign persist; non-member 422 + cross-org 404; tenant isolation + cancelled-off-board)
  · `MaintenanceDetailViewTest` (real facts + audit-event timeline; state-gated lifecycle persist; guarded
  illegal transition → clean error; owned → charged-to-owner + real owner_expense reconciles; operator →
  operator-cost + no owner_expense; OOS callout + real manual-move count; cross-org detail/action 404). The
  concurrency suite is re-run green after the module (no-double-booking + single-write-path intact).
- **Unified Inbox suite** — `InboxCoreTest` (thread/message create; morph map; inbound idempotency;
  outbound via manual; deferred adapters throw [asserted]; honest failed record; encrypted participant_identity;
  guarded transitions; tenant isolation; backbone-job idempotency) · `InboxLinkingTest` (known-address → existing
  guest [reused matcher, no dup]; unknown → created+linked+threaded; grouping by ref AND participant hash;
  reservation link; reply via the thread channel; anonymize redacts the conversation; tenant isolation; cross-org
  reservation-link rejected) · **`InboxConcurrencyTest`** (TRUE OS-parallel, 5× — two concurrent inbound of the
  same external id → exactly one message + one thread, no 500) · `InboxModuleTest` (HTTP integrity: list/thread
  render tenant-scoped; cross-org thread + reply → 404; real reply via manual [delivered, unread cleared];
  deferred reply → honest failed + flash; guest NAME shown, encrypted identity not rendered; end-to-end
  inbound→match→group→reply). `BookingImportTest` stays green after the shared-GuestMatcher refactor.
  **Styled-UI design pass:** `InboxListViewTest` (real channel label + preview; encrypted identity not
  rendered; reservation tag only when linked; tenant isolation) · `InboxThreadViewTest` (transcript + real
  delivery status; deferred-channel honest "not connected" composer; forced deferred reply → honest failed;
  assign/close/reopen/snooze persist + guarded; the guest stay-stat derived-real or omitted; link-reservation
  persists).
- **Security / integrity suite — MUTATION-VERIFIED (each proven to FAIL without its guard; this is the point).**
  `BackOfficeAccessTest` (#26 — an owner-role user is bounced from every `/app` route + the owner-only role;
  each defense-in-depth layer blocks independently) · `RefundGuardTest` (#27 — cumulative refunds bounded,
  same-currency, non-positive rejected; **failed 7/10 pre-fix**) · the `terminate()` service guard (#29 — a
  terminal reservation cannot be re-terminated; both callers funnel one `TERMINAL_STATUSES`) ·
  `PermissionEnforcementTest` (#30 — 7 routes + the refund screen enforce `can:`; **stripping every `can:`
  refails 5**; the privilege-escalation invite path closed) · `GuestErasureTest` (#28 — `guests.erase`-gated,
  typed-ERASE-confirmed anonymize; **8 failed pre-fix**). All found by READING, not by a failing test.
- **Auth — change-password + 2FA.** `ChangePasswordTest` (9 — happy path + remember-token rotation, wrong
  current rejected/unchanged, `auth()`-resolution [A changes, B untouched], owner side, the non-manager
  reachability of `/app/security`, the shared path) · `TwoFactorEnrollmentTest` (12 — confirm-before-activate
  mutation-proven, recovery codes single-use + regenerate-invalidates, disable-requires-re-auth, encrypted at
  rest, self-only) · **`TwoFactorChallengeTest`** (8 — **mutation-proving invariant #31**: a correct password
  alone reaches neither `/app` nor `/owner`; valid TOTP completes; recovery code completes + is consumed [no
  reuse]; invalid rejected + throttled [blocks even a valid code past the limit]; a non-2FA login is unchanged).
- **Wave 2–4 screens + the shared-definition drift proofs** (a shared rule is worthless unless a test proves
  both surfaces use it): `ReservationStage` — its 28-combination status×date partition is asserted exhaustive
  (a chip can't drift from what it filters) · `GuestSpend` — `for()` / `forGuestIds()` return identical figures
  and **reconcile to the ledger by construction** · `GuestHistory` — one stays/nights/"guest since" rule (the
  inbox and CRM had shipped two). Plus the per-screen render/isolation + guarded-write tests for the
  Reservations list, Cancelled/No-show, the Refund screen, the Guests CRM + profile, Operator Rates, New
  Reservation (the 3 guards), Settings (operator + owner), and the owner inbox (`/owner/messages`, owner-scope
  mutation-proven — invariant #18).
- **THREE browser-verified user journeys** — ⚠️ **NOT part of the runnable regression net; do not read this
  line as a gate.** They were an agent-driven **Playwright MCP** pass against a live `php artisan serve`
  (there is no installed Playwright, no specs, and every artefact is gitignored), so unlike every other
  item in this section they **cannot be re-run or re-checked**. Historically real and valuable — the pass
  found and fixed two demo-data gaps — but a manual pass, on real demo data: **(1)
  public booking** (browse→room→checkout→real direct booking), **(2) operator ops**
  (calendar/front-desk/housekeeping/folio/dashboard), **(3) owner portal** (dashboard/statement/payouts/
  units, money reconciles to the cent). This pass **found + fixed the two demo-data gaps** (public slug,
  owner-accounting seeding) — see `docs/DECISIONS.md` 2026-07-12; the demo now demos the full loop
  end-to-end. (The invite modal + full auth login were also browser-verified during Phase B.)

## Owner portal — STYLED & LIVE (design pass complete)

The 4 owner-portal screens are **styled + wired to real owner-scoped data** (no longer unstyled stubs).
Shared foundation: `resources/views/components/layouts/owner.blade.php` (the design system — frosted glass,
terracotta `#C2410C`, Sora/Inter, the real `vyFadeUp/vyShimmer/vyDrift/vySwap` keyframes) ·
`resources/views/components/owner/shell.blade.php` (`<x-owner.shell>` — sidebar + topbar) ·
`App\Livewire\Owner\Concerns\FormatsOwnerData` (money/status/viewer formatting). **Presentation only —
backends/schema/guarantees UNCHANGED.**

| Route | Component / view | Styled |
|---|---|---|
| `/owner` | `App\Livewire\Owner\OwnerDashboard` → `livewire/owner/dashboard` | ✅ styled + live |
| `/owner/statements/{statement}` | `App\Livewire\Owner\OwnerStatementView` → `livewire/owner/statement` | ✅ styled + live (RMB-safe, cross-owner 404) |
| `/owner/payouts` | `App\Livewire\Owner\OwnerPayouts` → `livewire/owner/payouts` | ✅ styled + live (new page; lifecycle uses only the REAL pending/paid/failed/reversed states) |
| `/owner/units` | `App\Livewire\Owner\OwnerMyUnits` → `livewire/owner/units` | ✅ styled + live (new page; **owner-scoped occupancy** — excludes non-owner units of the same room type) |

**WAVE 1 (2026-07-16) — the owner-accounting long-tail. All owner-scoped + fail-closed; presentation only.**

| Route | Component / view | Styled |
|---|---|---|
| `/owner/balance` | `App\Livewire\Owner\OwnerBalance` → `livewire/owner/balance` | ✅ REAL ledger activity + per-row running balance; DERIVED net position (Σ `signedMinor`); in-transit/paid from the real payout enum. Withdraw/CSV disabled "Soon"; the design's clearing-state machine + tax hold OMITTED (no schema). |
| `/owner/expenses` | `App\Livewire\Owner\OwnerExpenses` → `livewire/owner/expenses` | ✅ REAL `owner_expenses` incl. maintenance-sourced (polymorphic `source`); the `deductible` **TRI-STATE** (true/false/**null** — never coerced); voided badge; the statement each hit (`attributed_ledger_id → ledger.statement_id`). The owner approve/query workflow OMITTED (no state machine). |
| `/owner/agreement` | `App\Livewire\Owner\OwnerAgreement` → `livewire/owner/agreement` | ✅ REAL effective-dated terms, **display-only**, honest nulls ("Not set", never "0%"). ⚠️ `ManagementAgreement` + `Owner` are **NOT** owner-scoped → scoped explicitly via `OwnerContext`, fail-closed (leak test locks it). e-signature/amendments OMITTED. |
| `/owner/archive` | `App\Livewire\Owner\OwnerArchive` → `livewire/owner/archive` | ✅ REAL finalized statements w/ the real gap-free `sequence_no`; **drafts excluded**; DERIVED lifetime/counts/span (the design's "3 years · 34 documents" refused). Nav key `statements`. |
| `/owner/units/{unit}` | `App\Livewire\Owner\OwnerUnitDetail` → `livewire/owner/unit-detail` | ✅ REAL unit facts + **owner-scoped occupancy (this unit only)** + attribution breakdown + real stays (no guest PII). Cross-owner/operator-owned unit → 404. Most of the design OMITTED (see DECISIONS 2026-07-16). |
| `/owner/documents` | `App\Livewire\Owner\OwnerDocuments` → `livewire/owner/documents` | ✅ Aggregation over the **real generatable PDFs** only (statement · booking summary · remittance) — every listed row downloads (test follows the links). ZIP/tax-summary "Soon", never linked. |
| `/owner/statements/{s}/bookings` | `Owner\OwnerBookingSummaryPdfController` + `Support\Pdf\OwnerBookingSummaryDocument` | ✅ PDF — the bookings behind a statement's gross. Occupancy + the guest column OMITTED (see below). |
| `/owner/payouts/{payout}/remittance` | `Owner\OwnerRemittancePdfController` + `Support\Pdf\OwnerRemittanceDocument` | ✅ PDF — remittance advice per payout; the amount label is derived from the REAL status ("Net payable" while pending, never "Net paid"). |

**WAVE 3 (2026-07-17) — the OWNER INBOX. Owner-scoped by `BelongsToOwner`; a REAL write path.**

| Route | Component / view | Styled |
|---|---|---|
| `/owner/messages` | `App\Livewire\Owner\OwnerMessages` → `livewire/owner/messages` | ✅ the owner's REAL threads with their operator's staff (a real user + their real back-office role — NOT the design's 5 fabricated directory entries, two of which were the SaaS vendor). Unread badge/filter/count DERIVED from `owner_last_read_at` vs later staff replies (never the operator's `unread_count`). Search hits real `messages.body` — **plaintext, so no blind index needed**, unlike guest contact. Compose = ONE real query (the org's back-office members). |
| `/owner/messages/{thread}` (+ POST `messages.reply` / `messages.start`) | `App\Livewire\Owner\OwnerMessageThread` + `Owner\OwnerMessageActionController` → `livewire/owner/message-thread` | ✅ the transcript, **MIRRORED** (`direction` is org-relative → the owner's own message is `inbound`; the operator inbox reads the same rows the other way up). The reply is a REAL message via `InboxService::postOwnerMessage()` → it lands in the operator's inbox, **named**, and bumps their unread. `failed` rendered honestly (#24); **no "✓✓ Read"** — that value is not in the enum. |

⚠️ **The backend Wave 3 added, and why each piece exists:** `message_threads.owner_id` + `BelongsToOwner`
(**without the column the scope does not fail closed — it never runs**; see invariant #18) · `owner_last_read_at`
(the operator's `unread_count` is theirs, and `markRead()` zeroes it) · `postOwnerMessage()` (not
`postInbound()` — the GuestMatcher would mint a spurious Guest for the owner; not `sendOutbound()` — wrong
direction + hard-codes the "Operator" label) · `markReadByOwner()`. The operator inbox now NAMES owner threads
(was "Unknown sender"). **CUT, not deferred:** owner↔"Vyrobel Support"/"Billing" (the SaaS vendor —
cross-tenant, cannot live in an org-scoped table) and owner↔"Maintenance team" (a team is not a user).

Per-screen tests: `OwnerStatementViewTest`, `OwnerDashboardViewTest`, `OwnerPayoutsViewTest`,
`OwnerMyUnitsViewTest`, **`OwnerMessagesTest`** (the mirror, the real reply, the loop closing into the
operator's inbox, the fabrications absent), **`OwnerInboxIsolationTest`** (symmetric + mutation-proven)
(+ existing `OwnerPortalIsolationTest`). The **design-pass honesty rule** — REAL →
wire live · DERIVED → from real figures only · DEFERRED → stubbed "not yet available" · NO-BACKEND →
disabled "Soon" (never a fake action/IBAN/date/download); reconcile money to the cent; owner isolation
holds; guest PII never exposed — is locked in `docs/DECISIONS.md` (2026-07-11).

## Operator screens — STYLED & LIVE (5 core + Units + Wave 1's 2 + Wave 2's 5)

Shared foundation: `resources/views/components/layouts/operator.blade.php` (dedicated operator layout —
same Vyrobel tokens as the owner one, copied not shared) · `resources/views/components/operator/shell.blade.php`
(`<x-operator.shell>` — grouped sidebar + topbar) · `App\Livewire\Operator\Concerns\FormatsOperatorData`
(viewer + `money()` + warm-only status/source labels, real enum only). **TENANT-scoped, presentation only.**

| Route | Component / view | Styled |
|---|---|---|
| `/app/rack` | `App\Livewire\ReservationRack` → `livewire/reservation-rack` | ✅ styled + live (availability + booking bars; warm status tones; drag DEFERRED; overbooked excluded) |
| `/app/front-desk` | `App\Livewire\FrontDesk` → `livewire/front-desk` | ✅ styled + live (arrivals/departures/in-house + real folio balance; **check-in/out WIRED + persist** via POST→`FrontDeskActionController`→`FrontDeskService`→PRG; payment collection DEFERRED) |
| `/app/housekeeping` | `App\Livewire\HousekeepingBoard` → `livewire/housekeeping-board` | ✅ styled + live (kanban; the 4 columns ARE the real status enum; **status advance WIRED + persists** incl. unit sync via POST→`HousekeepingActionController`→`HousekeepingService`→PRG; drag + assign-housekeeper DEFERRED — no assign method exists) |
| `/app/reservations/{reservation}` | `App\Livewire\ReservationDetail` → `livewire/reservation-detail` | ✅ styled + live (RMB-safe; folio as the real **append-only ledger**, reconciled to the cent, no invented lines; **record-payment + full refund WIRED + persist append-only** via POST→`ReservationFolioActionController`→`FolioService`→PRG; add-charge/void/partial-refund/move/cancel DEFERRED) |
| `/app/dashboard` | `App\Livewire\Operator\OperatorDashboard` → `livewire/operator/dashboard` | ✅ styled + live (NEW page; read-only; every KPI a TRUE org figure — occupancy summed org-wide from the Availability spine, arrivals/HK counts, payments-today from the ledger; revenue/ADR/RevPAR trend DEFERRED) |

**WAVE 1 (2026-07-16) — the operator side of the owner-accounting long-tail. TENANT-scoped (NOT owner-scoped).**

| Route | Component / view | Styled |
|---|---|---|
| `/app/owners/{owner}` | `App\Livewire\Operator\OperatorOwnerDetail` → `livewire/operator/owner-detail` | ✅ the operator's lens on ONE owner (units/agreement/statements/payouts/balance). `Owner` is `BelongsToOrganization` → cross-org 404 free. ⚠️ **OwnerScope is INACTIVE on `/app`**, so every other read is filtered EXPLICITLY by `owner_id` (the test asserts symmetrically both ways that two owners in ONE org never bleed). Portal-access state is the REAL invite state (`user_id` alone does NOT mean access — it is linked at invite time to an inactive login). Owner email/phone rendered (the operator's own CRM record); **tax_id + raw address never read**. Edit/Remove modal + its no-column toggles OMITTED. |
| `/app/payouts/run` (+ POST `payouts.create` / `payouts.pay`) | `App\Livewire\Operator\PayoutRun` + `Operator\PayoutRunActionController` → `livewire/operator/payout-run` | ✅ **REAL money writes** through the certified `OwnerPayoutService` (`createFromStatement` / `pay`) via POST→controller→service→PRG. Ready-set mirrors the service's own rule exactly (finalized+locked, net>0, no pending/paid payout). Idempotent + guarded (an illegal transition → clean flash, never a 500). **`ManualPayoutRail` is the real rail and the copy says so plainly** ("Vyrobel does not move this money — marking a transfer paid RECORDS a bank transfer you already made"); **Stripe Connect is disabled "Soon", never callable**. SEPA export / IBAN / the "no verified IBAN" hold rail OMITTED (no such field exists); the design's non-reconciling constants never ported. |

**WAVE 2 (2026-07-17) — operator daily ops. TENANT-scoped, behind the #26 gate. Locked decisions in
`docs/DECISIONS.md` 2026-07-17.**

| Route | Component / view | Styled |
|---|---|---|
| `/app/reservations` | `App\Livewire\Operator\ReservationsList` → `livewire/operator/reservations-list` | ✅ every booking, one row each. Stage chips + badges BOTH from the shared **`ReservationStage`** (`scope()` for the SQL, `for()` for the badge — a chip cannot drift from what it filters). Chip counts are the REAL count of what the click delivers, search included. Tiles = this calendar month, by `check_in`, cancelled/no-show EXCLUDED, per currency. **OMITTED:** the paused banner, the bulk composer + Send, bulk select, the month picker, sort controls, the "Refunded" badge (a DEAD enum). "Booking.com"/"Completed" never rendered — neither is modelled. |
| `/app/reservations/{reservation}` (extended) | `App\Livewire\ReservationDetail` + `Operator\ReservationLifecycleController` | ✅ **cancelled + no-show terminal states** — real terminal panel, timeline, cancellation policy, and guarded lifecycle writes (`cancel` / `markNoShow`) gated off the controller's public `CANCELLABLE`/`NO_SHOWABLE` allow-lists. ⚠️ Those constants are **load-bearing**: `AssignmentService::terminate()` has **no internal guard** (register item 1). |
| `/app/reservations/{reservation}/refund` | `App\Livewire\Operator\ReservationRefund` → `livewire/operator/reservation-refund` | ✅ the dedicated refund screen over the **#27-guarded** backend. It **re-derives nothing**: shows the real `remainingRefundableMinor()` per payment, caps the input at it, and offers nothing once remaining = 0 — so the UI cannot invite a rejection. The service stays the authority. **Partial refunds were never a backend gap** — the controller is now amount-aware (no `amount` → full remaining), rejects €0 BEFORE the service, and surfaces an over-refund as a clean flash, **never a silent clamp**. |
| `/app/guests` | `App\Livewire\Operator\GuestsList` → `livewire/operator/guests-list` | ✅ the CRM list. Real `is_vip`/`risk_flag`/tags/nationality; **⚠️ PII-visible (invariant #28)** — email/phone decrypted HERE and on the profile only. "Total billed" + open balance from the shared **`GuestSpend`**; stays from the shared **`GuestHistory`**; badge from `ReservationStage`. Search is **name-only and says why** (encrypted TEXT is not indexed by value). **OMITTED:** the bulk bar + Send/Delete modals (a guest is erased via `anonymize()`, never deleted), CSV export, Add guest (no form exists), pagination, sort, the "+4%" delta. |
| `/app/guests/{guest}` (+ POST `guests.update` / `guests.notes.store` / `guests.tags.store`, DELETE `guests.tags.destroy`) | `App\Livewire\Operator\GuestProfile` + `Operator\GuestActionController` → `livewire/operator/guest-profile` | ✅ **REAL writes** through the new `App\Services\GuestService` (the single guest write path) — contact edit (encrypted on save by the cast), VIP/risk/marketing toggles, **notes** (the first writer of the shared `notes` table, ever) and **tags** (created on first use — `unique(org,name)`, so a pick-list would have been empty forever). The design's "Save changes" fired a toast and persisted **nothing**; this survives a reload. **⚠️ The audit records WHICH fields changed, never their values** (invariant #28). **OMITTED:** the whole "Latest review" panel (no reviews table exists), the localStorage refund netting + its non-reconciling "€978", "Dublin, Ireland" (no residence column), "Prefers email" (no column), the 8 hard-coded preference chips, and the "Shown to housekeeping"/"Flags arrivals" claims (**verified FALSE**). |

Per-screen tests: `OperatorRackViewTest`, `OperatorFrontDeskViewTest`, `OperatorHousekeepingViewTest`,
`OperatorReservationDetailViewTest`, `OperatorDashboardViewTest`, **`OperatorOwnerDetailViewTest`**,
**`OperatorPayoutRunTest`** (the real create/pay writes asserted against the DB, idempotency, guarded
double-pay, cross-org 404), and **Wave 2's** `OperatorReservationsListTest` · `ReservationStageTest` ·
`ReservationTerminalStatesTest` · `RefundScreenTest` · **`OperatorGuestsListTest`** (the PII perimeter,
mutation-proven) · **`OperatorGuestProfileTest`** (the save is real, not a toast) · `GuestSpendTest` ·
`GuestHistoryTest` · `GuestServiceTest` (the audit must not hold PII; `anonymize()` redacts notes). The
**operator-mutation pattern**
(reuse for every operator write): **POST → controller → existing service action → PRG redirect**; the POST
re-runs the web middleware (tenant context set), `{id}` is a **string** param resolved via tenant-scoped
`findOrFail` (never RMB) → cross-org 404. Never a Livewire wire-action for a scoped mutation.

## Public booking storefront — STYLED & LIVE (COMPLETE — 3 screens, incl. the real WRITE)

A dedicated **PUBLIC** design system (unauthenticated; context via `ResolvePublicProperty` — the slug sets
real tenant/property scope, NOT the operator `app` group / `SetTenantForWebRequest`, no logged-in user).
Foundation: `resources/views/components/public/shell.blade.php` (`<x-public.shell>` — full-page storefront
chrome: property header + centered content + footer, NO operator sidebar; same Vyrobel tokens, copied). **No
Livewire** (server-rendered, rate-limit-friendly). Browse + Room Detail are READ-ONLY; **Checkout is the one
public WRITE** — a session-CSRF POST that books through the single guarded `AssignmentService` path.

| Route | Controller / view | Styled |
|---|---|---|
| `/book/{property}` | `BookingEngineController@browse` → `booking/browse` | ✅ styled + live (search + real sellable rooms via `BookingEngineService::search`; per-night derived from real total; names-only teaser before a search; Select → room detail) |
| `/book/{property}/rooms/{roomType}` | `BookingEngineController@room` → `booking/room` | ✅ styled + live (property-scoped → cross-property 404; real specs + real amenities/availability/price; photos/description/reviews DEFERRED; Book → checkout) |
| `/book/{property}/checkout` (GET review · **POST write**) + `/reservation/{quote}` | `BookingEngineController@checkout`/`reserve`/`reservation` → `booking/checkout`,`booking/reservation` | ✅ styled + live — **the WRITE flow.** Quote→hold→**book THROUGH AssignmentService** (source=direct, rejects when full, never overbooks)→pay THROUGH FolioService (append-only)→confirmation with the real reference. Manual/pay-on-arrival wired; Stripe card entry DEFERRED. **POST is session-CSRF-protected** (not exempt) + `throttle:booking`. |

Per-screen tests: `PublicBookingBrowseTest`, `PublicBookingRoomTest`, `PublicBookingCheckoutTest` (real
book via AssignmentService; advisory hold never changes `sold`; **CRITICAL — two holds on the last unit,
only one books, `sold` never exceeds capacity**; cross-property checkout 404; CSRF token on the write
form; no auth). The JSON API (`/search` `/quote` `/book` `/confirmation`) is the real final backend,
unchanged (`PublicBookingEngineTest`); its POST endpoints stay CSRF-exempt by path (`book/*/quote`,
`book/*/book`).

Operator Units (the last stub) is now styled too: `/app/units` → `App\Livewire\UnitsList` →
`livewire/units-list` — ✅ styled + live (real unit fields + real cleanliness/condition enums + owner
link where set; `OperatorUnitsViewTest`: render + tenant isolation). **No unstyled contract stubs
remain** — every styled surface over the certified backend is done.

**Livewire RMB rule (must hold in the design pass):** never type-hint a scoped model as a public
Livewire prop (route-model binding resolves during `SubstituteBindings`, BEFORE the tenant/owner
middleware). Hold the id as a string; resolve via scoped `findOrFail` in `render()`. **Also (proven on
the owner portal):** scoped-data-dependent interactivity must NOT use Livewire wire-actions — they
re-render via `/livewire/update`, bypassing the route middleware → scope leak. Use Alpine/client-side or
plain GET links (e.g. `?month=`).

## Pointer index (read on demand)

- **THIS FILE** (`docs/PROJECT-STATE.md`) → read FIRST · then **`docs/DECISIONS.md`** (the dated "why" behind every gate, append-only, full arc Phase 1 → Unified Inbox UI).
- **Vision / phases / buy-vs-build** → `docs/master-plan.md`
- **`docs/BOOTSTRAP-AND-OPERATING-GUIDE.md`** — *(historical: the original zero-to-Phase-1 setup runbook +
  operating model. Superseded as guidance — but its **environment gotchas are still current** (XAMPP/MySQL-not-
  MariaDB, the memory/config file layout), and it is the only place the operating model is written down.)*
  ⚠️ **Environment prerequisites for a green `php artisan test`: a genuine MySQL 8 `vyrobel_test` DB (NOT
  XAMPP's MariaDB) + Redis running** (Horizon queue) + Mailpit for the mail tests' dev transport.
- **Data contracts (column-level source of truth)** → `docs/schema/phase-1.md` · `docs/schema/phase-2.md`
  · `docs/schema/phase-3-owner-accounting.md` · **`docs/schema/maintenance.md`** · **`docs/schema/inbox.md`**
- **Gated build prompts** → `docs/prompts/phase-1.md` · `docs/prompts/phase-2.md` ·
  `docs/prompts/phase-3-owner-accounting.md` (all done/certified) · `docs/prompts/live-integration-checkpoint.md`
  (Stripe leg done; Channex Steps 1–2 outstanding)
- **Design source of truth** → **`docs/design/CATALOGUE.md`** (per-file inventory + backend-status legend; headline count reconciled to **125 files / 124 screens**) · `docs/design/design-system-brief.md` · `docs/design/owner-portal-spec.md`. ⚠️ The raw Claude Design HTML lives in **`docs/design/raw/*.html` — GITIGNORED / local-only, NOT in a fresh clone** (125 files on this machine); the `.md` specs above ARE versioned. **A fresh clone cannot read the designs** — work from `CATALOGUE.md` / `DESIGN-COVERAGE.md` / `WAVE2-INVENTORY.md` / `WAVE4-INVENTORY.md` / `AUTH-INVENTORY.md`, which are versioned and were written from them. ⚠️ **The per-page inventories are per-wave, not total:** Waves 2 and 4 + the auth screens have versioned inventories; a FUTURE build of a design without one (most Tier-2 modules) must first produce its inventory from `raw/` **on a machine that has it** (this one does), or the design is simply unavailable on a clone.
- **PLANNING SOURCE OF TRUTH (read before starting any new screen)** →
  **`docs/ROADMAP.md`** (designed-vs-built-vs-remaining across ~16 modules; **~42 of ~124 screens done (~34%)**
  — a column sum with one cross-listed row, so distinct = 41; the tilde is load-bearing; Guest Portal counted at
  ~24) · **`docs/TIER1-SPEC.md`** (per-screen build specs — backend reality per feature, write methods,
  buildability verdict, the 5-wave order with **Waves 1–4 CORE done**) · **`docs/DESIGN-COVERAGE.md`**
  (exists-vs-missing design audit — 125 files / 124 screens, near-total coverage) · **`docs/WAVE2-INVENTORY.md`**
  + **`docs/WAVE4-INVENTORY.md`** (per-page feature inventories, every element tagged ✅ BUILD-NOW / 🔨
  NEEDS-BACKEND / 📋 DECORATIVE-FAKE / ⬜ OTHER-MODULE — **the template for reading ANY design before building
  it**; WAVE2 is the artefact that found the €1,107 over-refund by inventorying rather than testing) ·
  **`docs/AUTH-INVENTORY.md`** (every auth/onboarding design: BUILT vs quick-buildable vs blocked — carries the
  superseding 2026-07-18 note that change-password + 2FA are now built).
- **Code style / testing patterns** → `docs/CODING-STANDARDS.md` · **Conventions + commands** → `CLAUDE.md`
- **Code** → `app/Services/` (incl. `InboxService`, `GuestMatcher`, `MaintenanceService`, owner-accounting services,
  and the **guest-realm/reviews** services: `GuestAccountService`, `GuestWalletService`, `GuestWishlistService`,
  `ReviewService`) · `app/Payments/` · **`app/Payouts/`** · **`app/Messaging/`** (channel interface + ManualChannel +
  Deferred skeletons + resolver) · `app/Services/Channex/` · `app/Jobs/Sync/` · `app/Actions/Reservations/` ·
  **`app/Mail/`** · `app/Notifications/` (+ `Operator/`) · `app/Listeners/` (domain-event → notifications/emails) ·
  **`app/Auth/`** (`GuestUserProvider` — the custom `guest-eloquent` driver, registered in `AppServiceProvider`) ·
  `app/Http/Controllers/` (+ `Auth/`, `Operator/`, `Owner/`, **`Guest/`** — whose `Concerns/ResolvesGuest` resolves the
  guest FROM the guard, never a request id) · `app/Http/Middleware/` (incl. `EnsureBackOfficeAccess`,
  `SetTenantForGuestRequest`, the guest-guard chain) · `app/Http/Requests/` (incl. `Auth/`, `Guest/GuestLoginRequest`) ·
  `app/Providers/` (morph map in `AppServiceProvider`) ·
  `app/Livewire/` (+ `app/Livewire/Owner/` incl. `OwnerReviews`, `app/Livewire/Operator/`) ·
  `app/Support/` (`Tenancy/`, `Owner/`, **`Guest/`** incl. `GuestName`/`GuestTrip`/`GuestPayments`, **`Reviews/`** =
  `ReviewRating`+`ReviewList`, `Money/`, `Events/`, `Media/`, `Pdf/`, `Folio/`, `Reservations/`, `Payouts/`) ·
  `app/Models/` (+ `Scopes/` incl. `OwnerScope`, `Concerns/` incl. `BelongsToOrganization`/`BelongsToOwner`) ·
  `resources/views/` (+ `operator/`, `owner/`, **`guest/`**, `booking/`, `pdfs/`, `emails/`) ·
  **`config/auth.php`** (the `guest` guard + its `guests` provider on the custom `guest-eloquent` driver, alongside the
  `web` guard/`users` provider — plus the separate `guests` password-reset broker) ·
  **`config/authorization.php`** (THE permission catalog + role→permission map + `back_office_roles` allow-list — the
  single source for #26/#30) · `database/migrations/` + `database/seeders/DemoSeeder.php` ·
  `tests/Feature/` + `tests/Unit/` + `tests/Support/`.

## NEXT ACTION — ⭐ SUPERSEDED by the CURRENT STATE block at the TOP of this file (2026-07-22)

> **⚠️ The authoritative next action is the ⭐ CURRENT STATE block at the top: ALL of Phase 7 is done AND Phase 8's
> email + Stripe are lit up AND the audit + demo-blocker fixes are done — so NEXT = RESUME the DESIGN-FIDELITY PASS
> (operator queue, `Rates` next; see `docs/design/DIVERGENCE-REGISTER.md`). The section below is retained for its
> Phase-7 detail but no longer names the next action.**


> **CURRENT (2026-07-22). Phases 0, 1, 2, 3, 4a, 4b, 4c, 5, 6 (a+b) AND ALL OF PHASE 7 (7a, 7b, 7c, 7d) of the phased
> plan are COMPLETE — the SaaS layer is done** — three full user-facing apps (operator · owner · guest) + a public
> storefront + **public self-signup that provisions a whole new tenant org with a free trial** + **subscription
> billing** + **OWNER TEAM (owner sub-tenancy)** + **TEAM SCHEDULE (staff rostering + time-off + a schedule PDF)** +
> hardened multi-realm auth (operator/owner `web` + a separate `guest` guard, incl. 2FA), all on the certified backend,
> ~89 styled/browser-verified screens on real scoped data under the honesty rule, now **MULTI-PROPERTY with real
> ANALYTICS, SELF-SERVE SIGNUP + BILLING, OWNER SUB-TENANCY, and STAFF ROSTERING**. Suite **895 / 7383** green on MySQL;
> PHPStan L5 + Pint clean; `migrate:fresh --seed` clean (2 demo properties, demo org on a real Essential subscription,
> demo owner + 2 sub-users + a pending invite, a demo roster week + leave + request). Suite **900 / 7409** with
> **PRODUCTION EMAIL lit up** (Postmark wired as the prod transport via ENV; test/prod separation enforced in code by
> `MailSafetyGuard`; the Phase-3 mail-failure boundary re-proven with the real transport; DNS + the one real send are
> Subhan's). Suite **921 / 7496** with **LIVE STRIPE wired TEST-MODE-FIRST across all three seams** (subscription
> billing · guest card charge · owner payout rail/Connect — webhook-driven, multi-layer idempotent, no raw PAN,
> the payout double-transfer-guarded; the live-key flip is Subhan's). The **OWNER ACCOUNT AREA** was then rebuilt
> (Profile & account + Security — the per-user identity split that fixes the sub-user 404; email re-verify;
> avatar/language/sessions/export/reversible-deactivate; #46). Invariants **#1–46**. Suite **942 / 7631**
> after a **full READ-ONLY system audit** (`docs/AUDIT-REPORT.md`, 26 browser screenshots) whose demo-blockers were
> then cleared in a targeted presentation/seed/test pass: `c0ce419` wrapped the four **unstyled operator controller-
> pages** (Reviews/Guest requests/Email previews/Email templates — bare `<x-operator.shell>`, no document `<head>`)
> in `<x-layouts.operator>` — the "static checks can't see a rendered page" lesson, live; `7cd1550` rolled the demo
> `rate_calendar` to a `today→+180d` window (storefront demos real availability, not "0 rooms"); and the
> `/livewire/update` owner-capability caveat is **CLOSED** by `OwnerCapabilityMutationBoundaryTest` (gate holds — owner
> writes are gated controller routes; NO owner Livewire component exposes a mutating action, so nothing rides
> `/livewire/update`). **Next = the design-fidelity pass** (`docs/AUDIT-REPORT.md` §4). **Guest Portal + Reviews
> complete; multi-property + real analytics; tenant provisioning + subscription billing + owner team + team schedule +
> production email + live Stripe (test-mode) ship (the Postmark live token + DNS, and the Stripe live-key flip, are
> Subhan's go-live steps).** (The dated
> per-phase detail is in
> `docs/DECISIONS.md`; the older "customer signal / don't build blind" framing below is HISTORICAL — the signal came
> and every phase since was built to the prototype.)
>
> **✅ PHASE 6b — ANALYTICS (DONE, 2026-07-21, 3 gated sub-gates `8254e26` → `df9c95e` → this gate).** The shared
> `PerformanceMetrics` + `MetricSet` layer (ADR/RevPAR/occupancy/room revenue, stay-night attribution, RevPAR = ADR ×
> occupancy exactly, reconciling to `FolioTotals` + the availability spine) → operator Insights + Reports
> (`reports.view`, per-property + all-properties) → owner Reports + Insights (owner-scoped, **earnings reconcile to the
> owner ledger**) → the real Portfolio comparison. Every figure a REAL aggregate; the invented history/narrative
> REFUSED; a thin period an honest empty state; lead-time/pace deferred "Soon" — invariant **#39**. Reconciliation +
> owner isolation mutation-proven; the fabrication-absence tested. **✅ PHASE 6a — MULTI-PROPERTY** (2 gated steps):
> `PropertyContext` + the topbar switcher + the COMPLETE `->first()` refactor (grep-clean) + property CRUD + the
> Portfolio — invariant **#38**.
>
> **✅ PHASE 7a — TENANT PROVISIONING (DONE, 2026-07-21, 2 gated steps `0c4643d` → this gate).**
> `TenantProvisioningService` atomically creates a new operator org (org + provisioned roles + founder + `Property
> Owner` + first property in ONE transaction; chicken-and-egg escaped via `runAsSuperAdmin`) + the public `/signup`
> onboarding wizard (rate-limited, neutral enumeration, founder auto-logged-in, honest "finish setup"). **BOTH
> deferred fail-closed RBAC defects FIXED, mutation-proven** — invariant **#40**.
>
> **✅ PHASE 7b — SUBSCRIPTION BILLING (DONE, 2026-07-22, 3 gated sub-gates `4a3b5cd` → `5a52696` → this gate).** The
> free-trial model: plans/tiers (placeholder prices) + `SubscriptionStatus` + the real trial (from 7a) + the Paywall +
> Checkout (company/VAT, period toggle) + access-gating (`EnsureSubscriptionActive`: lapsed → paywall, never mid-trial,
> fail-open) + feature-gating (channels Pro-only). **The LIVE Stripe charge + webhooks are ACCOUNT-GATED** — no faked
> payment, no "active" off a fake charge, no raw card. Invariant **#41**.
>
> **⬅ NEXT = PHASE 7c — OWNER TEAM.** Owner sub-tenancy — an owner invites their own users (a permission subsystem).
> Then Team Schedule / rostering (7d).
>
> **THE FULL REMAINING MAP (`docs/GAP-ANALYSIS.md` is authoritative):**
> - **Phase 7c — Owner Team (NEXT):** owner sub-tenancy (an owner invites their own users) · then Team Schedule /
>   rostering (7d).
> - **Phase 8 — account-gated integrations** as accounts land (see the ACCOUNT-GATED tail below) — incl. **LIVE
>   subscription billing** (the Stripe charge + webhooks the 7b Checkout seam is built up to).
>
> **🔒 ACCOUNT-GATED TAIL (external accounts required — CANNOT be built until obtained; deferred HONESTLY throughout
> — build the experience, NEVER fake a charge/send/verify):** the card charge / Apple Pay everywhere (Stripe live) ·
> **LIVE subscription billing** (Stripe Billing — the 7b Checkout seam is built up to the charge + webhooks) · owner
> payout EXECUTION (Stripe Connect + KYC) · live OTA sync (Channex staging) · WhatsApp / OTA inbox channels ·
> SMS (waitlist + notifications) · production email (Mailpit is dev) · guest ID verification / KYC.
> **Subhan's parallel track — START Stripe Connect + an email provider FIRST** (they unblock the most: real
> payments + the owner payout rail + production email + the review/pre-arrival/waitlist email triggers).
>
> **🪤 DEFERRED SUBSYSTEMS (real features, later — NOT account-gated, just not built yet; each stubbed honestly,
> never faked):** response-automation + review-gating (Reviews — **deceptive review-gating deliberately NOT built**;
> the honest equivalent is the guest's own "send privately to the manager") · loyalty tiers (Silver/Gold) · referral
> auto-earn (a referral credit is real only when genuinely earned) · the waitlist notify-trigger + auto-hold ·
> email-template editing · guest email-change (a re-verify flow).
>
> **✅ RESOLVED (Phase 7a, 2026-07-21):** the two fail-closed RBAC self-provision defects are FIXED — org-creation now
> provisions roles (#1), and the founder holds the assignable `Property Owner` (#2). Residual (re-scoped, separate,
> deferred): the global `Super Admin` stays unassignable — a PLATFORM-admin concern (Horizon gate), never a tenant
> founder's, needing a spatie-PK rework whenever that schema is next touched.
>
> ---
> **(HISTORICAL — the framing that led here; kept for the record.)** A warm operator contact (Fatima)
> **liked the demo AND the prototype**, so the requirement is now concrete: **the real product must work the way
> the prototype shows.** The prototype (124 designed screens) is the SPEC; the repo stays the truth for what backs
> it; every gap gets BUILT, not faked. The full phased map — **audit-verified against real code** (5-agent pass,
> 2026-07-20; 50 claims → 33 confirmed / 8 incorrect / 8 nuanced) — is **`docs/GAP-ANALYSIS.md`**.
>
> **✅ PHASE 4a COMPLETE (2026-07-20) — the GUEST AUTH FOUNDATION**, 4 gated sub-gates (`524a1d3` → `70f4d02` →
> `e2710ff` → this gate): a SECOND auth realm for guests, alongside the operator/owner `web` realm WITHOUT
> disturbing it. Guest Authenticatable on a separate `guest` guard + a keyed **blind index** (the encrypted email
> isn't queryable) · BOOKING-TIED claim + email verification (Phase-3 Verify Email wired; NO free sign-up — a
> conscious divergence) · guest login + password reset hardened (#28 through the reset token table + link) · Guest
> Account (self-only) + safe account removal (revoke access, keep records). **REALM SEPARATION** (a guest can't
> reach /app|/owner; a guest ⟂ web at guard + provider level) + **GUEST ISOLATION** (a guest sees/edits only their
> own; resolve from the guard never a request id) + **BOOKING-TIED** + **NO-ENUMERATION** all mutation-proven; the
> existing operator/owner auth chapter UNREGRESSED. A claimed demo guest (demo.traveller@vyrobel-demo.test /
> password) is seeded. NEXT = **Phase 4b** (the Guest Portal core — Trips/bookings/Account detail/etc. — on this
> foundation). Detail: `docs/DECISIONS.md` 2026-07-20 (Phase 4a).
>
> **✅ PHASE 3 COMPLETE (2026-07-20) — the notification subsystem + all transactional emails**, 5 gated sub-gates
> (`7cca65c` → `7905570` → `640f149` → `6c51271` → this gate): the notification CORE (Laravel-native, real events
> → per-user feed, isolation + idempotency) · Notification Preferences (TYPED store that GENUINELY gates delivery,
> mutation-proven, always-on security, Phase-1 maintenance toggle resolved) · the real events wired (checkout
> balance-due, out-of-service, payout/statement to owner+operator, channel error, account-security; deferrals
> precise about why) · the 9 guest transactional emails (Mailpit-real; booking/cancellation/refund/reminder live,
> the rest template-built-trigger-deferred) · owner/operator email delivery through the SAME preference gate
> (selective email defaults; mutation-proven off→no-email) + the Email Templates view (editing deferred honestly).
> **ONE account-gated piece remains for emails: a production mail provider (Mailpit is dev).** NEXT = **Phase 4**
> (guest auth is the true greenfield the ~24-screen Guest Portal sits on).
>
> **The honest position:** **~55 built screens/artifacts · ~70 designed-but-unbuilt.** What remains splits into
> ① subsystems/epics (guest auth, notifications, reviews/wallet, tenant-provisioning, Owner Rates,
> Channels-as-drawn, Portfolio) · ② the ~24-screen Guest Portal (on guest auth) · ③ account-gated (inbox
> channels, Channex, Stripe Connect/cards, prod email). **⚠️ AUDIT CORRECTION:** **multi-property is NOT a schema
> epic** — the schema already holds N properties/org; Portfolio is a property-selector + CRUD + ~8–10 `->first()`
> call-site refactors (re-scoped L→M). The three TRUE greenfield subsystems are **guest auth · notifications ·
> reviews/wallet** — multi-property is not one.
>
> **✅ PHASE 0 (fix & harden) COMPLETE (2026-07-20, `a7068d1`)** — the channel manager un-inerted, the dead
> enums resolved, the two fail-closed RBAC defects re-logged + deferred, stale docblocks fixed,
> `docs/QA-WALKTHROUGH.md` written.
>
> **✅ PHASE 1 (complete the operator app, buildable set) COMPLETE (2026-07-20)** — five gated sub-gates
> (`eefd22d` Group Booking · `1595583` Search · `35019e9` Roles · `a4a3ddc` Maintenance Costs+Settings · HEAD
> Operator Profile). Six screens, five new services (`GroupBookingService`, `SearchService`,
> `RoleAssignmentService`, `MaintenanceCostService`, `MaintenanceSettingsService`) + `OperatorProfileController`.
> The security-critical shapes held: **Group Booking** is atomic over the existing `groups` with a phantom-event
> guard (a rolled-back block fires ZERO `reservation.confirmed` events — mutation-proven); **Search** is
> tenant-scoped and searches guests by NAME only (encrypted email/phone can't be `LIKE`-searched → the
> blind-index decision is DEFERRED and the limit is surfaced, never silent); **Roles** REFUSED the design's
> catalog-edit (an escalation weapon) and enforces the **privilege-subset rule** (`perms(role) ⊆ perms(actor)`,
> so only `['*']` grants `['*']`); **Maintenance Costs** are real aggregates that reconcile to the cent (the
> fabricated labour/parts split refused, not faked); **Maintenance Settings** are typed columns via the
> `SettingsService` pattern (NOT the dormant KV scaffold); **Operator Profile** is a SELF-ONLY name/email write
> (auth-resolved, never a request id — mutation-proven a smuggled `user_id` can't reach another account). Deferred
> out of Phase 1 (logged, not faked): the **blind-index** contact-search decision, **notification dispatch**
> (→ Phase 3), and PDF/Excel **exports** of the cost report.
>
> **✅ PHASE 2 (complete the owner portal, buildable set) COMPLETE (2026-07-20)** — six gated sub-gates
> (`0dfc560` Bookings + Detail · `598cc9b` Block Calendar · `58f0b49` Owner Rates · `5119548` Tax Center + PDF ·
> `b95228f` Payout Method · HEAD Add Unit + Help). Eight owner screens + the operator Unit Requests screen + the
> Annual Tax PDF (owner 15 → 23). **Owner isolation is the phase's #1 concern and every gate proves it a different
> way:** Bookings 404 a cross-owner `{booking}` (reservations have no owner_id → scoped THROUGH the units); Block
> Calendar's write guard is mutation-proven for block AND release (and it un-latented `blockInventory` to enforce
> `assertUnitFree`); Owner Rates — the highest-risk MONEY write — scopes by **exclusive ownership** (a room type
> is editable only if EVERY unit is the owner's), mutation-proven both ways, DIRECT edit with the no-approval
> divergence recorded; Tax Center's payout HOLD is genuine (refused at the money path, released on tax-ID save —
> mutation-proven), tax_id encrypted; Payout Method's IBAN is encrypted + masked, self-only across read/set-default/
> remove; Add Unit is an owner REQUEST (not direct billable-inventory creation), operator-visible. New shared rule
> `OwnerLedgerAttribution` (the ONE "Net to you"); + `OwnerBlockService`, `OwnerRateService`, `OwnerTaxService`,
> `PayoutMethod`/`Iban`, `UnitRequest`. Deferred (logged, not faked): Stripe Connect payout EXECUTION
> (account-gated), owner-rate/unit approval workflows, the operator's unit-creation-from-a-request (their action).
>
> **NEXT = PHASE 3** — the NOTIFICATION subsystem + all transactional emails (events → dispatcher → in-app feeds +
> preferences → the ~9 email templates + triggers; live sending flips on when the provider account exists). This
> completes the operator+owner apps (**make Fatima operational soonest**) before the large Guest Portal phase (4).
> See `docs/GAP-ANALYSIS.md` for the full 0–8 plan + the corrected per-front inventory.

**(historical) The original Wave-4 lead-in:**

**WAVE 2 IS COMPLETE** (2026-07-17, five gated sub-gates, `4eb71e2` → `cb8bcce` → `44444e6` → `f86cf4e` →
HEAD): **Reservations list · Cancelled/No-show terminal states · the Refund screen · the Guests CRM list ·
the Guest profile.** Suite **511 tests / 5503 assertions**, PHPStan L5 0 errors, Pint clean,
`migrate:fresh --seed` clean, browser-verified at every gate — including a **real profile write** that
survives a reload, with the phone ciphertext at rest and the audit trail holding only the field NAMES.

1. ✅ **DONE — the WAVE 2 REGISTER's security/integrity items are CLEAR** (2026-07-17, three gated fixes:
   `f4a8913` #29 · `9123ee7` #30 · `94ef7ad` the erasure trigger; plus the flaky test root-caused). They were
   deliberately done BEFORE Wave 3 **so new screens inherit correct enforcement rather than needing a
   retrofit** — a Wave 3/4 write route now simply carries its `can:<permission>`, and its control is
   `@can`-gated, because the pattern is established and tested. What remains in the register (the dead enums,
   the missing inbox permission, the two fail-closed RBAC defects) is **small and none of it blocks a build**.
2. ✅ **DONE — Wave 3, the Owner Messages inbox** (2026-07-17, `49ab9a6`). ⚠️ **And it was NOT
   presentation-only, though both planning docs said so** — `TIER1-SPEC` rated it READY and `ROADMAP` called
   it 🟢 M. `message_threads` had no `owner_id`, so `BelongsToOwner` could not be applied and `OwnerScope`
   **never ran**: the screen as specced would have shown every owner every guest conversation and every other
   owner's threads. **The lesson for Wave 4: a UI-shape match is not a backend match. Audit the model's
   scoping columns before trusting a READY rating.**
   **NEXT — Wave 4** per `docs/TIER1-SPEC.md`: Rates + Channels UI, New/Group Booking, Portfolio, Block
   Calendar, Roles/Settings. **2FA is now DONE** (2026-07-18 — invariant #31); the 2 remaining
   NEEDS-BACKEND-FIRST items (Global Search, Maintenance Settings) last.
   ⚠️ Two register items bear on Wave 4 directly: the **dead enum values** (a status-keyed UI renders never,
   silently — Rates/Channels are status-heavy) and the fact that **New/Group Booking has schema already**
   (`groups`, `reservations.group_id`) — extend it, never build a parallel structure.
   ⚠️ **Global Search still needs the blind-index decision** — the Guests CRM proved the constraint concretely:
   it SHOWS contact detail it cannot SEARCH, because encrypted TEXT is never indexed by value.
3. **In parallel — CUSTOMER VALIDATION is the highest-value NON-BUILD move, and nothing else changes that.**
   The wedge demos at full depth (statement → remittance PDF → payout run → the owner sees it paid,
   reconciling to the cent), operator daily-ops runs end-to-end (reservations → refund → guest CRM), and the
   operator/owner boundary is now genuinely enforced — **safe for a real owner login**. ⚠️ **State it plainly:
   NO CUSTOMER HAS EVER SEEN THIS PRODUCT.** The buildable-in-a-vacuum core is essentially complete; what
   remains is security-hardening (1), account-gated (needs someone else's credentials), or **speculative**
   (Tier 2: Guest Portal ~24, Analytics, Reviews, Billing, Notifications, Emails — designed, no backend, and
   no evidence anyone wants them). Every additional Tier-2 screen is a bet placed without signal.
   *(There is no outreach material in this repo — no demo script, no cold-email draft. If any exists it lives
   outside version control; do not assume it.)*

---

## META-LESSONS (earned expensively — re-read before any build)

These are not platitudes; each one is here because it caught a real bug or prevented a real lie, repeatedly.

- **(a) A BRIEF IS A HYPOTHESIS; THE REPO IS TRUTH.** Building has caught roughly a dozen confidently-stated,
  factually-wrong brief assumptions — `AssignmentService::terminate()` "throws on an illegal transition" (it
  has **no guard at all**); events belong in controllers (**no controller in this repo calls a recorder —
  services do**); `Note` was morph-mapped (it was **not** — the first `record()` on it would have thrown);
  `statement_id + room_revenue` reconciles to `gross_minor` (it does not); `owners.user_id != null` means
  portal access (it does not); "9 screens" in Wave 1 (the tables always said **10**); the existing
  `two_factor_secret` column was safe to reuse (it was **plaintext** — the 2FA build had to encrypt it); the
  certified channel manager works in production (**nothing writes `channels.status`** — it is inert without a
  connect/activate flow, found 2026-07-18 by grep). **Audit first, every time. Echo the plan against real code before writing any of it.**
  - **⚠️ AND THE COROLLARY THAT COST US: "VERIFY" MEANS MEASURE THE RIGHT THING.** The 2026-07-17
    consolidation "caught" a brief claiming **44 permissions** and *corrected* it to **39** — citing the repo.
    **The brief was right and the correction was wrong.** 44 is the catalog (`config('authorization.permissions')`);
    39 was the union of permissions named across roles — a *different question*, confidently mistaken for the
    same one, then written into a security invariant and quoted here as proof that briefs lie. A measurement
    that disagrees with a claim has not refuted it until you have checked you measured **the same thing**.

- **(b) GREEN TESTS ARE NECESSARY, NOT SUFFICIENT.** **Both** critical security bugs — the €1,107 over-refund
  on a €369 payment, and the `/app` leak of every owner's financials — were found by **READING and
  inventorying**, never by a test failing. The fully-certified suite was green over both, for weeks. A suite
  proves the things you thought to ask; it cannot tell you what you never asked.
- **(c) ADVERSARIALLY RE-READ EVERY MONEY AND AUTH PATH.** That is the technique behind (b). Regression tests
  structurally cannot find a missing invariant — only someone asking "what bounds this?" can. #27 exists
  because someone asked what bounded the SUM when #7 only bounded the EDITS.
- **(d) EXTRACT THE SHARED DEFINITION, AND PROVE IT IS SHARED.** Two `match`es for one concept WILL drift.
  This session caught **three screens silently disagreeing** about the same guest (the inbox said "2 stays ·
  guest since 2024" where the CRM said "1 stay · guest since 2026" — both sincere). The proof matters as much
  as the extraction: assert the two callers return identical results, and mutate the helper to prove the
  screen reads it.
- **(e) BROWSER-VERIFY EVERY SCREEN.** Raw Blade shipped to users (`word@directive` — the `\B` rule) and a
  Livewire prop shadowing view data (a 500) both passed `php -l` **+ PHPStan + Pint + a Blade compile**.
  Static analysis cannot see a rendered page. Run the suite **and** open the thing.
- **(f) EVEN THE DOCS NEED SKEPTICISM — INCLUDING THIS FILE, INCLUDING THIS SECTION.** Two proofs, both from
  the same consolidation: a docs-sweep agent hardened a hedged "~26" into an unhedged "42" and attached a "no
  overlaps, so it reconciles" guarantee that its own next sentence refuted; and the pass that wrote *this very
  lesson* simultaneously "corrected" a TRUE "44 permissions" into a FALSE "39" and enshrined it inside a
  security invariant (see (a)'s corollary). **Writing down "be skeptical" provides no immunity.**
  **Load-bearing hedges are load-bearing.** The only thing that actually caught either error was **three
  independent blind readers sent at the finished docs with instructions to disprove them** — no amount of
  careful self-review did. Do that before calling any consolidation done.
- **(g) SHIP THE ERASE WITH THE WRITER, AND THE GUARD WITH THE GATEWAY.** A latent GDPR gap becomes real the
  moment you build the thing that writes; an ordering detail (guard **before** the gateway call) is the
  difference between rejecting and moving real money then rejecting.
- **(h) A UI-SHAPE MATCH IS NOT A BACKEND MATCH — audit the model's scoping columns before trusting a "READY"
  rating.** Wave 3 (the owner inbox) was rated presentation-only by BOTH planning docs, yet `message_threads`
  had no `owner_id`, so `BelongsToOwner` could not apply and `OwnerScope` **never ran** — building it as specced
  would have shown every owner every other owner's (and every guest's) conversation. A screen that looks like a
  read is a security build if the data isn't scoped for it. **Trust the schema, not the mockup.**
- **(i) SECURITY FIXES HAVE A METHOD, and it is not "add a check".** Every one of this session's five holes
  followed it: **audit-first** (read the real code, find who actually calls the path — `terminate()` had two
  callers that *disagreed*); **put the guard where ALL paths are covered** (`refundPayment()`/`terminate()`
  guard in the single service method, not per-controller; authorization on the ROUTE where a permission is a
  request fact, deliberately unlike the data-bound service guards); **mutation-prove it** (write the test FIRST,
  watch it fail, fix, watch it pass, then strip the guard and watch it fail again — a guard with no red run
  proves nothing); **fix the fixtures, never the check** (a test that fails because its actor lacks a permission
  is telling you to fix the actor); **defense-in-depth** (#26 was two independent layers, each proven to block
  alone). And #31's variant: **prefer relying on a guard that already exists** (the pending state is rejected by
  the standard `auth` middleware) over inventing a new one that a future route could forget.
- **(j) WHEN A TEST FLAKES, SUSPECT THE ASSERTION BEFORE THE APP.** Both flakes closed this arc were the same
  disease — a needle too generic to distinguish the bug from a coin flip (a raw faker name vs escaped HTML; a
  2-char unit code vs random CSRF tokens). The app was correct both times. **A needle must be one that could
  ONLY appear by the bug; CAPTURE the failing run, do not re-run until it hides.** (Detail in the register.)

---

## (historical) NEXT ACTION — WAVE 1 — ✅ DONE 2026-07-16

Removed 2026-07-17: it was a full stale copy of the Wave-1 build plan that still read as pending and
contradicted the live NEXT ACTION above by two waves. Wave 1's record lives where it belongs — the dated
entry in `docs/DECISIONS.md` (2026-07-16) and the Wave 1 tables in the styled-surface inventory above.
