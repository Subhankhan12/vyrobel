# Vyrobel — Project State (resume here)

**On a fresh session read THIS first, then `docs/DECISIONS.md` for the dated "why".** This is a
pointer map to the real code, not a copy of it — follow the links on demand. If anything here
conflicts with the repo, **trust the repo** and fix this file.

## Status (verified against the repo)

- **HEAD `39724e6`** on `main`, working tree clean. Suite: **`php artisan test` → 155 tests / 2860
  assertions, all green** on genuine **MySQL 8** (`vyrobel_test`). **PHPStan (Larastan) level 5, 0
  errors** (`--memory-limit=1G`). **Pint clean.** Queue = **Redis/Horizon live** (`QUEUE_CONNECTION=redis`,
  `REDIS_CLIENT=predis`; `database` documented as fallback).
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
- **OPERATOR DESIGN PASS — IN PROGRESS (Screens 1–2 styled + live).** Presentation only, **no backend/
  schema/guarantee change.** A dedicated operator design system was extracted (mirrors the owner one):
  `components/layouts/operator` (same tokens; copied, not shared — owner layout untouched),
  `<x-operator.shell>` (grouped sidebar + topbar, extracted on Screen 1), and
  `App\Livewire\Operator\Concerns\FormatsOperatorData` (viewer + `money()` + **warm-only** status/source
  labels keyed to the real reservation enum). Operator screens are **TENANT-scoped** (not owner-scoped);
  guest NAMES shown (front-desk surface), encrypted email/phone never. DONE: **Screen 1 Operator Calendar
  → `ReservationRack` (`/app/rack`)** `0e1ccaa` and **Screen 2 Operator Front Desk → `FrontDesk`
  (`/app/front-desk`)** `39724e6`. Front Desk **wires the check-in/out mutations** via the reusable
  operator-mutation pattern **POST → controller → existing service action → PRG redirect** (tenant-scoped
  by re-run middleware; `{id}` string param → `findOrFail` → cross-org 404). See "Operator screens —
  STYLED & LIVE" below.
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
- **Owner Accounting (Phase 3 · Module 1)** — `owners` + `management_agreements` (effective-dated,
  `ManagementAgreement::resolveFor()`); **`App\Services\OwnerAttributionService`** (reads folio+
  reservation_units → immutable `owner_ledger`); **`App\Services\OwnerExpenseService`** (`owner_expenses`
  → deduction lines); **`App\Services\OwnerStatementService`** + `App\Services\PeriodLockGuard`
  (`owner_statements`/`owner_statement_lines`, period-locked projection); **`App\Services\OwnerPayoutService`**
  + `App\Payouts\` (`PayoutRail`/`ManualPayoutRail`/`StripeConnectPayoutRail` skeleton, `payouts`);
  **owner isolation** via `App\Support\Owner\OwnerContext` + `App\Models\Scopes\OwnerScope` +
  `App\Http\Middleware\SetOwnerScopeForPortal`; portal reads via `App\Services\OwnerPortalService`.

## INVARIANTS THAT MUST NEVER BREAK (the full guarantee stack)

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
    Payout/OwnerLedgerEntry/OwnerExpense). **FAILS CLOSED** (active + empty set → nothing, never all
    owners); inactive off-portal (operator/CLI/tests see all, subject to tenant scope); activated only
    by `SetOwnerScopeForPortal`, **deactivated on `terminate`** (no leak into operator requests);
    **composes with tenant scope** (both boundaries hold; cross-org 404 both directions).

## Deferred / outstanding register (do NOT build ad hoc; see DECISIONS.md)

**TWO live-integration checkpoints (money/network rails — the only unproven-against-reality items):**
- **Channex staging** — ARI push shape + inbound webhook payload + HMAC scheme are **documented
  guesses** (validated only vs `FakeChannexClient` + simulated signed payloads). Blocked: needs a
  Channex staging account + a public tunnel.
- **Stripe Connect payout rail** — real owner money movement (connected-account onboarding + KYC).
  `StripeConnectPayoutRail.send()` **throws** (config-gated skeleton); **`ManualPayoutRail` is the real,
  tested disbursement path.** Blocked: needs Connect credentials.

**Other deferrals:** promo codes + deposits on the booking engine (schema only); `guests.stripe_customer_id`
/ saved cards (blind-index story); **auto-FX provider** (statements take an operator-supplied rate — no
silent float); **maintenance-ticket source** for maintenance expenses (no maintenance module yet — the
polymorphic `owner_expenses.source` hook is ready); **`UnitFactory` test-infra debt** (hard-codes its own
org, unlike `ChannelFactory`/`OwnerFactory`; owner tests pass `organization_id`/`unit_id` explicitly) —
fix in a dedicated pass, not mid-work; plus standing Phase 1 deferrals (blind-index guest matching,
gap-free invoice numbering, dated out-of-order ranges, `inventory_blocks.quantity`, tenant-custom
amenities, same-day-turnover housekeeping dedupe).

## Regression net — `php artisan test` (MySQL `vyrobel_test`)

**155 tests / 2860 assertions, all green. "Green here means the whole guarantee stack holds"** — Phase 1
(double-booking/tenant/ledger/GDPR/event-discipline), Phase 2 (cross-system contention/sync-reliability/
payments), Owner Accounting (attribution-to-the-cent/append-only-reversal/period-locking/owner-isolation),
the owner-portal design pass (per-screen render/isolation + occupancy-correctness), and the operator
design pass (`OperatorRackViewTest` + `OperatorFrontDeskViewTest` — render/isolation/no-invented-status/
PII, and a real check-in that persists through the action + cross-org 404s), together.
- **TRUE OS-level parallelism** (separate PHP processes + MySQL connections, shared future-timestamp
  barrier, committed data, no RefreshDatabase): `ConcurrentBookingTest`, `DeadlockAvoidanceTest`,
  `CrossSystemConcurrencyTest` (repeat-runs zero-flake).
- **Real Redis + time-travel** (no real sleeps): `SyncReliabilityTest`, `AriPushTest`, cross-system retry.
- **In-memory fakes, no network** — `FakeChannexClient`, `FakeStripeClient` (real HMAC) back the rest;
  **Stripe additionally validated live once** (test-mode, 2026-07-03).
- **Owner Accounting suite (45 named tests):** `tests/Unit/MoneyAllocateTest` + `tests/Feature/`
  `OwnerAttributionTest`, `OwnerExpenseTest`, `OwnerStatementTest`, `OwnerPayoutTest`,
  `OwnerPortalIsolationTest` (+ shared `tests/Support/owner_helpers.php`).

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

Per-screen tests: `OwnerStatementViewTest`, `OwnerDashboardViewTest`, `OwnerPayoutsViewTest`,
`OwnerMyUnitsViewTest` (+ existing `OwnerPortalIsolationTest`). The **design-pass honesty rule** — REAL →
wire live · DERIVED → from real figures only · DEFERRED → stubbed "not yet available" · NO-BACKEND →
disabled "Soon" (never a fake action/IBAN/date/download); reconcile money to the cent; owner isolation
holds; guest PII never exposed — is locked in `docs/DECISIONS.md` (2026-07-11).

## Operator screens — STYLED & LIVE (design pass in progress)

Shared foundation: `resources/views/components/layouts/operator.blade.php` (dedicated operator layout —
same Vyrobel tokens as the owner one, copied not shared) · `resources/views/components/operator/shell.blade.php`
(`<x-operator.shell>` — grouped sidebar + topbar) · `App\Livewire\Operator\Concerns\FormatsOperatorData`
(viewer + `money()` + warm-only status/source labels, real enum only). **TENANT-scoped, presentation only.**

| Route | Component / view | Styled |
|---|---|---|
| `/app/rack` | `App\Livewire\ReservationRack` → `livewire/reservation-rack` | ✅ styled + live (availability + booking bars; warm status tones; drag DEFERRED; overbooked excluded) |
| `/app/front-desk` | `App\Livewire\FrontDesk` → `livewire/front-desk` | ✅ styled + live (arrivals/departures/in-house + real folio balance; **check-in/out WIRED + persist** via POST→`FrontDeskActionController`→`FrontDeskService`→PRG; payment collection DEFERRED) |

Per-screen tests: `OperatorRackViewTest`, `OperatorFrontDeskViewTest`. The **operator-mutation pattern**
(reuse for every operator write): **POST → controller → existing service action → PRG redirect**; the POST
re-runs the web middleware (tenant context set), `{id}` is a **string** param resolved via tenant-scoped
`findOrFail` (never RMB) → cross-org 404. Never a Livewire wire-action for a scoped mutation.

## Remaining UNSTYLED contract stubs (the design pass styles these NEXT — operator + booking)

Deliberately unstyled (no CSS/design system), render REAL data through the services, document their data
contract in the component docblock. **Backends + data contracts are FINAL.**

| Route | Component / view | Contract (data shape) |
|---|---|---|
| `/book/{property}` (+ `/search` `/quote` `/book` `/confirmation/{quote}`) | `BookingEngineController` → `resources/views/booking/stub.blade.php` | public search/quote/book JSON contract (the JSON API is the real final backend) |
| `/app/units` | `App\Livewire\UnitsList` → `livewire/units-list` | units list |
| `/app/reservations/{reservation}` | `App\Livewire\ReservationDetail` → `livewire/reservation-detail` | reservation + units + folio (RMB-safe: string id → tenant-scoped `findOrFail` in `render()`) |
| `/app/housekeeping` | `App\Livewire\HousekeepingBoard` → `livewire/housekeeping-board` | housekeeping tasks board |

**Livewire RMB rule (must hold in the design pass):** never type-hint a scoped model as a public
Livewire prop (route-model binding resolves during `SubstituteBindings`, BEFORE the tenant/owner
middleware). Hold the id as a string; resolve via scoped `findOrFail` in `render()`. **Also (proven on
the owner portal):** scoped-data-dependent interactivity must NOT use Livewire wire-actions — they
re-render via `/livewire/update`, bypassing the route middleware → scope leak. Use Alpine/client-side or
plain GET links (e.g. `?month=`).

## Pointer index (read on demand)

- **Vision / phases / buy-vs-build** → `docs/master-plan.md`
- **Data contracts (column-level source of truth)** → `docs/schema/phase-1.md` · `docs/schema/phase-2.md`
  · `docs/schema/phase-3-owner-accounting.md`
- **Gated build prompts** → `docs/prompts/phase-1.md` · `docs/prompts/phase-2.md` ·
  `docs/prompts/phase-3-owner-accounting.md` (all done/certified) · `docs/prompts/live-integration-checkpoint.md`
  (Stripe leg done; Channex Steps 1–2 outstanding)
- **The dated "why" behind every gate** → `docs/DECISIONS.md`
- **Code style / testing patterns** → `docs/CODING-STANDARDS.md` · **Conventions + commands** → `CLAUDE.md`
- **Code** → `app/Services/` · `app/Payments/` · `app/Services/Channex/` · `app/Jobs/Sync/` ·
  `app/Actions/Reservations/` · `app/Http/Middleware/` · `app/Livewire/` (+ `app/Livewire/Owner/`) ·
  `app/Support/` (`Tenancy/`, `Owner/`, `Money/`, `Events/`) · `app/Models/` · `app/Models/Scopes/` ·
  `database/migrations/` · `tests/Feature/` + `tests/Unit/` + `tests/Support/`.

## NEXT ACTION — continue the OPERATOR screens (design pass)

The **owner portal is DONE** and **operator Screens 1–2 (Calendar/Rack, Front Desk) are styled + live**
(above). **Next = `Operator Housekeeping` → `App\Livewire\HousekeepingBoard` (`/app/housekeeping`)**, then
`Operator Reservation Detail` → `/app/reservations/{id}`, then `Operator Dashboard`; then the public
booking engine (`/book/{property}`). Backends, schema, JSON contracts, and the guarantee stack are FINAL —
presentation only, **zero architectural change.**

**Same discipline** (the reference implementations are the owner portal + operator Screens 1–2): **inherit
the operator design system** (`components/layouts/operator`, `<x-operator.shell>`, `FormatsOperatorData`) —
do NOT redefine it; wire live data through the existing services (call, never modify); honor the **Livewire
RMB rule**; apply the **design-pass honesty rule** (DECISIONS.md 2026-07-11 — REAL / DERIVED / DEFERRED /
NO-BACKEND; no fake data; reconcile money; tenant isolation holds; statuses keyed to the real enum only;
guest names OK on operator surfaces but never encrypted email/phone). **Status-change mutations use the
operator-mutation pattern** proven on Front Desk (POST → controller → existing service action → PRG
redirect; tenant-scoped; `{id}` string → `findOrFail` → cross-org 404); if an action or the kanban drag
isn't cheaply/safely wireable, DEFER it with a flag rather than fake a status move. **Per-gate commit +
per-screen render/isolation (+ action-persistence) test.** Approved design source:
`docs/design/raw/Operator *.html` + `docs/design/CATALOGUE.md`.

Other tracks remain available but are NOT the chosen next step: next Phase 3 module (Unified Inbox), the
Channex staging checkpoint, the Stripe Connect checkpoint.
