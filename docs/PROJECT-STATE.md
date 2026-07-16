# Vyrobel — Project State (resume here)

**On a fresh session read THIS first, then `docs/DECISIONS.md` for the dated "why".** This is a
pointer map to the real code, not a copy of it — follow the links on demand. If anything here
conflicts with the repo, **trust the repo** and fix this file.

## Status (verified against the repo)

- **On `main`, working tree clean** (only the untracked design-source exports under `docs/design/raw/` remain
  uncommitted — **gitignored** raw Claude Design HTML, not in a fresh clone). Suite: **`php artisan test` → 511
  tests / 5503 assertions, all green** on genuine **MySQL 8** (`vyrobel_test`) — re-verified this session.
  **PHPStan (Larastan) level 5, 0 errors** (`--memory-limit=1G`) — re-verified. **Pint clean** — re-verified.
  ⚠️ `phpunit.xml` sets **`memory_limit=512M`** — the suite outgrew PHP's 128M CLI default (verified headroom,
  not a leak; see the register). `php artisan test` spawns a child that re-reads php.ini, so `-d` won't reach it.
  Queue = **Redis/Horizon live** (`QUEUE_CONNECTION=redis`, `REDIS_CLIENT=predis`; `database` documented as
  fallback). The three planning docs (`docs/ROADMAP.md`, `docs/TIER1-SPEC.md`, `docs/DESIGN-COVERAGE.md`) were
  authored on branch `docs/roadmap-audit` and **merged into `main`** in this consolidation — a fresh session on
  `main` sees them.
- **CURRENT PHASE: WAVE 2 COMPLETE (2026-07-17) → next = Wave 3.** Five gated sub-gates, a commit each
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
- **AUTH — COMPLETE & SECURE (chapter finished): login/logout + invites (owner + staff) + password reset.**
  Real session auth fronts the app; **the dev-login shim is gone.** Phase A: one shared login
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
  needs a real provider), 2FA. See `docs/DECISIONS.md` 2026-07-13.
- **DESIGN PASS — the original 17-screen pass is COMPLETE, and Waves 1–2 have since added 14 more (~31 total):**
  **owner portal (4 + Wave 1's 6 + 2 PDFs)** · **operator core (5) + Units (1) + Maintenance board & work order
  (2) + Inbox list & conversation (2) + Wave 1's Owner Detail & Payout Run (2) + Wave 2's Reservations list ·
  Refund screen · Guests list · Guest profile (4)** · **public booking storefront (3, incl. the real checkout
  WRITE)**. The per-wave inventories below are authoritative; this line is the count. All on the certified backends, honesty rule enforced per-screen by a render/
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
    Payout/OwnerLedgerEntry/OwnerExpense). **FAILS CLOSED** (active + empty set → nothing, never all
    owners); inactive off-portal (operator/CLI/tests see all, subject to tenant scope); activated only
    by `SetOwnerScopeForPortal`, **deactivated on `terminate`** (no leak into operator requests);
    **composes with tenant scope** (both boundaries hold; cross-org 404 both directions).

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
    - **Search cannot see it, and the page says so.** Encrypted TEXT is never indexed by value, so the CRM
      searches names only and states that in the footer rather than letting an operator conclude the data is
      missing. (The blind-index is a standing deferral.)
    (Locked `docs/DECISIONS.md` 2026-07-17; proven by `OperatorGuestsListTest` + `OperatorGuestProfileTest` +
    `GuestServiceTest`.)

## Deferred / outstanding register (do NOT build ad hoc; see DECISIONS.md)

### 🔴 WAVE 2 OUTSTANDING REGISTER (2026-07-17) — four items found while building, deliberately NOT fixed

Each was surfaced by Wave 2, **verified empirically**, and sequenced AFTER it by explicit decision. **None is a
regression; all four predate Wave 2.** Recorded here so none is silently dropped. Full detail:
`docs/DECISIONS.md` 2026-07-17.

1. **`AssignmentService::terminate()` has NO internal status guard.** Proven: re-cancelling an already-
   cancelled reservation overwrites `cancelled_at`/`cancellation_reason` and writes a junk `cancelled →
   cancelled` status-history row. Wave 2 guards it at the CONTROLLER (`ReservationLifecycleController`'s
   public `CANCELLABLE`/`NO_SHOWABLE` allow-lists, which the component gates its buttons off), so the shipped
   UI cannot reach it — **but the service is the single write path and must guard itself** (defense-in-depth,
   the same shape as #27). ⚠️ A brief asserting "terminate() throws on an illegal transition" was **wrong**;
   the code disagreed. Verify before trusting that claim again.
2. **Two DEAD enum values** — `payments.payment_status = 'refunded'` and `folios.status = 'closed'` exist in
   the schema and are **never written by any code** (`FolioService::paymentStatus()` only ever writes
   paid/partial/unpaid). Designs assume these states; a UI keying off either renders **never, silently**.
   Either write them or drop them — until then **no surface may depend on them**.
3. **🔴 THE UNENFORCED PERMISSION CATALOG** *(real security follow-up)*. `payments.refund` is held by Super
   Admin / Property Owner / Group Admin / Accountant and NOT by Hotel Manager / Front Desk — **and no route
   enforces any permission**. The coarse #26 staff/owner gate is the only real authorization: **any
   back-office role can do anything any back-office role can do.** Lower blast-radius than the `/app` hole
   (that leaked *other owners' financials* to an owner; this is staff-vs-staff inside one tenant), but the
   catalog currently **describes a policy the app does not enforce**.
4. **GDPR — `Guest::anonymize()` has NO UI trigger.** The erase is correct, tested and thorough, but it can
   only be invoked from code, so a real erasure request cannot be served by an operator. *(The second GDPR gap
   — note bodies not redacted — was **CLOSED** in Wave 2: shipping the first note writer would otherwise have
   opened the hole itself. See invariant #28.)*

---

### ✅ CLOSED (2026-07-16) — the `/app` authorization hole. Safe for a real owner login.

The critical item that stood here is **FIXED** (`5155a48` route gate → `f63866a` root cause). An owner-role
user could reach the entire operator back office and every other owner's financials; both layers are now in
place and each is proven to block independently. See **invariant #26** below and `docs/DECISIONS.md`
2026-07-16. Two smaller defects surfaced by that audit remain open — they are recorded in "Other deferrals"
below (**no org-creation path provisions roles**; **`Super Admin` is unassignable**), and neither is
exploitable (both fail closed).

---

**AT A GLANCE — everything else remaining is either ACCOUNT-GATED (needs an external account/credential) or a
net-new BUILDABLE-NOW module (design exists, no backend). The buildable-in-a-vacuum core is essentially
complete.** Detail follows below; this is the honest triage a fresh session needs.

- **ACCOUNT-GATED (blocked on an external account — cannot finish in a vacuum):**
  - **Real inbox channels** — email (inbound-email provider e.g. Postmark/Mailgun + verified sending domain) ·
    whatsapp (Meta WhatsApp Business API approval + a BSP/Twilio number) · booking_com/airbnb (OTA messaging
    API access). The `manual` channel + the whole interface work now; each adapter is an honest throwing skeleton.
  - **Channex staging** — real ARI/webhook/HMAC vs a Channex sandbox account + a public tunnel (fake fully tested).
  - **Stripe Connect payouts** — owner disbursement (connected-account onboarding + KYC). `ManualPayoutRail` is real+tested.
  - **Stripe card checkout** — live card entry (Elements + 3DS) needs live/test keys; manual/pay-on-arrival is the real path now.
  - **Production email delivery** — a real transactional provider (Postmark/SES). Dev/demo = Mailpit (wired).
- **BUILDABLE-NOW (no external dependency — but each is net-new + speculative without customer signal):**
  - Net-new **design-only modules** with NO backend today, each a gated phased build like Owner Accounting:
    **Guest Portal** (~32 screens), **Reviews / reputation**, **Subscription / billing / paywall**,
    **Analytics / reports** (ADR/RevPAR/insights), transactional **email templates** (~9).
  - Smaller in-product items: **CSV / remittance-advice / booking-summary** exports; room **descriptions + reviews**;
    per-screen deferred affordances (logged in each screen's DECISIONS entry).
  - `docs/design/CATALOGUE.md` is the **authoritative per-file design inventory** (~120 screens, backend-status
    legend) — read it before starting any new screen. Most catalogued screens map to the future modules above.

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
  **guest portal** (~32), **reviews**, **subscription billing / paywall**, **transactional emails** (~9), and
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
- **2FA** — schema columns (`two_factor_secret`/`two_factor_enabled`) exist, no flow — not built. (Password
  reset is now DONE — see the auth chapter above / DECISIONS.md 2026-07-13.)

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

**511 tests / 5503 assertions, all green. "Green here means the whole guarantee stack holds"** — Phase 1
(double-booking/tenant/ledger/GDPR/event-discipline), Phase 2 (cross-system contention/sync-reliability/
payments), Owner Accounting (attribution-to-the-cent/append-only-reversal/period-locking/owner-isolation),
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
- **THREE browser-verified user journeys** (Playwright, real demo data — beyond feature tests): **(1)
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

Per-screen tests: `OwnerStatementViewTest`, `OwnerDashboardViewTest`, `OwnerPayoutsViewTest`,
`OwnerMyUnitsViewTest` (+ existing `OwnerPortalIsolationTest`). The **design-pass honesty rule** — REAL →
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
- **Data contracts (column-level source of truth)** → `docs/schema/phase-1.md` · `docs/schema/phase-2.md`
  · `docs/schema/phase-3-owner-accounting.md` · **`docs/schema/maintenance.md`** · **`docs/schema/inbox.md`**
- **Gated build prompts** → `docs/prompts/phase-1.md` · `docs/prompts/phase-2.md` ·
  `docs/prompts/phase-3-owner-accounting.md` (all done/certified) · `docs/prompts/live-integration-checkpoint.md`
  (Stripe leg done; Channex Steps 1–2 outstanding)
- **Design source of truth** → **`docs/design/CATALOGUE.md`** (per-file inventory + backend-status legend, ~120 screens) · `docs/design/design-system-brief.md` · `docs/design/owner-portal-spec.md`. The raw Claude Design HTML lives in **`docs/design/raw/*.html` — GITIGNORED / local-only (not in a fresh clone)**; the `.md` specs above ARE versioned.
- **PLANNING SOURCE OF TRUTH (read before starting any new screen)** →
  **`docs/ROADMAP.md`** (designed-vs-built-vs-remaining across ~16 modules; Tier 1 buildable-now / Tier 2 net-new
  speculative / Tier 3 account-gated) · **`docs/TIER1-SPEC.md`** (per-screen build specs for the Tier 1
  completions — backend reality per feature, write methods, buildability verdict, 5-wave build order) ·
  **`docs/DESIGN-COVERAGE.md`** (exists-vs-missing design audit — 125 files / 124 screens in `docs/design/raw/`,
  near-total coverage; the catalogue's headline count is **reconciled** to 125/124, though those 5 screens are
  still absent from its per-screen tables).
- **Code style / testing patterns** → `docs/CODING-STANDARDS.md` · **Conventions + commands** → `CLAUDE.md`
- **Code** → `app/Services/` (incl. `InboxService`, `GuestMatcher`, `MaintenanceService`, owner-accounting services) ·
  `app/Payments/` · **`app/Payouts/`** · **`app/Messaging/`** (channel interface + ManualChannel + Deferred skeletons + resolver) ·
  `app/Services/Channex/` · `app/Jobs/Sync/` · `app/Actions/Reservations/` · **`app/Mail/`** · `app/Http/Controllers/` (+ `Auth/`, `Operator/`, `Owner/`) ·
  `app/Http/Middleware/` · `app/Livewire/` (+ `app/Livewire/Owner/`, `app/Livewire/Operator/`) ·
  `app/Support/` (`Tenancy/`, `Owner/`, `Money/`, `Events/`, `Media/`, `Pdf/`, `Folio/`) · `app/Models/` (+ `Scopes/`, `Concerns/`) ·
  `database/migrations/` + `database/seeders/DemoSeeder.php` · `tests/Feature/` + `tests/Unit/` + `tests/Support/`.

## NEXT ACTION — Wave 3 (Waves 1 and 2 are DONE; the product is safe for a real owner login)

**WAVE 2 IS COMPLETE** (2026-07-17, five gated sub-gates, `4eb71e2` → `cb8bcce` → `44444e6` → `f86cf4e` →
HEAD): **Reservations list · Cancelled/No-show terminal states · the Refund screen · the Guests CRM list ·
the Guest profile.** Suite **511 tests / 5503 assertions**, PHPStan L5 0 errors, Pint clean,
`migrate:fresh --seed` clean, browser-verified at every gate — including a **real profile write** that
survives a reload, with the phone ciphertext at rest and the audit trail holding only the field NAMES.

1. **⛔ FIRST — clear the WAVE 2 OUTSTANDING REGISTER** (four items, in the register above). The two that
   matter: **`terminate()`'s missing internal status guard** (currently controller-guarded only — the service
   is the single write path and must guard itself) and **the unenforced permission catalog** (`payments.refund`
   et al. are held by role but checked by nothing; only the coarse #26 gate is real). Then the two dead enums
   and the `anonymize()` UI trigger. These are small, and they are security/correctness debt with a written
   decision behind them — do them before more screens.
2. **THEN — Wave 3** per `docs/TIER1-SPEC.md`: the **Owner Messages inbox** (the inbox backend + the operator
   surface both already exist). Then Wave 4 (Rates + Channels UI, New/Group Booking, Portfolio, Block
   Calendar, Roles/Settings). The 3 NEEDS-BACKEND-FIRST items (Global Search, 2FA, Maintenance Settings) last.
   ⚠️ **Global Search still needs the blind-index decision** — the Guests CRM proved the constraint concretely:
   it SHOWS contact detail it cannot SEARCH, because encrypted TEXT is never indexed by value.
3. **In parallel — CUSTOMER VALIDATION is still the highest-value non-build move.** The wedge is demoable at
   full depth (statement → remittance PDF → payout run → the owner sees it paid, reconciling to the cent), and
   operator daily-ops now runs end-to-end (reservations → refund → guest CRM). Tier-2 (Guest Portal ~24,
   Analytics, Reviews, Billing, Notifications, Emails) stays speculative without signal.

**Lessons Wave 2 paid for (do not re-learn them):**
- **A shared definition is the deliverable; the SQL is trivial.** Every Wave 2 headline number was ambiguous
  or self-contradictory in the design (lifetime value netted a refund off *and* counted unpaid charges). The
  work was deciding and naming it once — `ReservationStage`, `GuestSpend`, `GuestHistory`.
- **Extract BEFORE the second screen ships, not after.** `GuestHistory` exists because the inbox had already
  shipped a different stay-count rule and nobody noticed: two screens, two sincere answers, one guest.
- **An audit trail is a PII store.** Encrypting a column at rest is undone the moment its value is copied into
  `audit_events.before/after`, which no erase clears. Record which fields changed, never their values.
- **Ship the erase WITH the writer.** A latent GDPR gap ("notes aren't redacted") becomes a real one the
  moment you build the thing that writes notes.
- **Trust the repo over the plan — again.** Wave 2's briefs were wrong three times: `terminate()` has no
  guard (a brief claimed it throws); events are written in services, never controllers (a plan said controller);
  and `Note` was unregistered in the morph map, so the first `record()` on it would have thrown.

**Lessons Wave 1 paid for (do not re-learn them):**
- **The design exports are decodable** — each embeds its template + logic; the Wave 1 audit recovered all
  ten to exact panels/labels/enums without a browser. **But decoding reveals what the design SAYS, not what
  is true**: they carry fabricated figures, fabricated prior-year history, non-reconciling arithmetic, inert
  buttons and authoring bugs. The backend is the source of truth.
- **Static analysis cannot see a rendered page.** Two real bugs (raw Blade shipped to the user; a Livewire
  prop shadowing view data → 500) passed `php -l` + PHPStan + Pint + Blade-compile and were caught ONLY by
  running the suite and opening a browser. Always do both before claiming a gate.
- **Trust the repo over the plan.** Three planned instructions were factually wrong against the real code
  (ADR/RevPAR aren't in these designs; `statement_id + room_revenue` doesn't reconcile to `gross_minor`;
  `user_id != null` ≠ portal access). Audit first, every time.

---

## (historical) NEXT ACTION — WAVE 1: the owner-accounting styled long-tail — ✅ DONE 2026-07-16

**Honest framing:** every piece that can be built without an external account is done and demoable
end-to-end. Certified backends (multi-tenant PMS + channel manager + Stripe payments + owner accounting/
payouts + maintenance + unified inbox) now carry their **entire built styled surface** — owner portal, operator
back office (calendar · front desk · housekeeping · maintenance · reservation+folio · dashboard · units ·
owners · **inbox**), and the public booking loop (browse → room → checkout → confirmation, a real guarded
write). **Real auth fronts all of it** (Phase A + B: sign-in → invite owner/staff → set password → portal;
dev email via Mailpit). **A real hybrid operator could run day-to-day ops, take a direct booking, message a
guest, and an owner could read statements/payouts reconciling to the cent — today.** The 2026-07-16 ROADMAP
audit set the concrete next target below (see `docs/ROADMAP.md` + `docs/TIER1-SPEC.md` + `docs/DESIGN-COVERAGE.md`).

**IMMEDIATE NEXT TASK — Wave 1: style the owner-accounting long-tail** (per `docs/TIER1-SPEC.md`'s build order;
**all 10 Wave 1 designs EXIST** per `docs/DESIGN-COVERAGE.md` — zero design gate). All READY = pure integration
over the CERTIFIED owner-accounting backend (`OwnerLedgerEntry`, `OwnerExpense`, `ManagementAgreement`,
`OwnerStatement`, `Payout`, `OwnerPayoutService`, `OwnerPortalService`, `PdfService`), on the **owner** design
system (`<x-owner.shell>` + `FormatsOwnerData`) and the **operator** one where noted, owner/tenant-scoped. Four
gated sub-gates (commit per gate; per-screen render/isolation test):

1. **Owner Balance · Owner Expenses · Owner Agreement · Owner Archive** — SIMPLE read screens over
   `OwnerLedgerEntry` (running balance = cumulative sum) / `OwnerExpense` / `ManagementAgreement` / finalized
   `OwnerStatement`. Owner-scoped (`OwnerScope`, fail-closed).
2. **Owner Remittance-Advice PDF + Owner Booking-Summary PDF** — mirror the existing `OwnerStatementPdfController`
   + `Support\Pdf\OwnerStatementDocument`; reconcile to the cent (shared `MoneyFormatter`, no invented lines).
3. **Owner Unit Detail + Operator Owner Detail** — Unit Detail = owner-scoped occupancy (reuse the My Units
   logic — never room-type-wide) + per-unit revenue from `owner_ledger`; Operator Owner Detail = the
   **tenant-scoped** (NOT owner-scoped) operator lens on the same data (`/app/owners/{owner}`; the Owners *list*
   already exists).
4. **Payout Run + Owner Documents** — Payout Run = batch over `OwnerPayoutService::createFromStatement`/`pay`
   (`ManualPayoutRail` is the real path; the Stripe-Connect step stays honestly stubbed); Owner Documents =
   aggregation view over statements/payouts/PDFs.

**Honesty rule (invariant #19) — stub what the design implies but the backend lacks:** ADR/RevPAR/trend charts
(Tier-2 Analytics — show only derivable occupancy/revenue, stub the rest "Soon"), **CSV export** (standing
deferral), **owner-editable rates** (a product-policy decision — default read-only). **After Wave 1:** Wave 2
(reservations list + cancelled/no-show + refund + Guests CRM, over reservations/folio), Wave 3 (Owner Messages
inbox), Wave 4 (Rates + Channels UI, New/Group Booking, Portfolio, Block Calendar, Roles/Settings). The 3
NEEDS-BACKEND-FIRST items (Global Search, 2FA, Maintenance Settings) come last.

**In parallel — CUSTOMER VALIDATION remains the highest-value NON-build move.** The Tier-2 modules (Guest
Portal ~24, Analytics, Reviews, Billing, Notifications, Emails) are **net-new, designed, but speculative without
customer signal.** Standing up Mailpit + demoing the full built loop to a real hybrid operator (public booking →
operator manages → invite owner → owner sees statements/payouts to the cent → message a guest) would let real
feedback prioritise Tier 2/3 over building breadth blind. Wave 1 (completions of an already-shipped module) is
safe to pursue regardless — it strengthens exactly what you'd demo. Account-gated unblocks (real inbox channel ·
Channex staging · Stripe Connect · Stripe card checkout · prod email) proceed only once the external account
exists (`docs/prompts/live-integration-checkpoint.md` for Channex/Stripe).

**If resuming design/build work:** inherit the relevant role design system (owner / operator / public — do
NOT redefine it; the operator system already covers the new maintenance + inbox screens); wire live data
through the existing services (call, never modify); honour the **design-pass honesty rule + scoped-render/
guarded-mutation discipline** (invariants #19–20) + the module invariants (#22 maintenance, #23–25 inbox);
**per-gate commit + per-screen render/isolation (+ action-persistence) test**; wire the certified services,
never a parallel write path. **Read `docs/TIER1-SPEC.md` (the per-screen build spec + backend-reality tags)
and `docs/DESIGN-COVERAGE.md` (which designs exist) first, then `docs/design/CATALOGUE.md`.** **The `.md`
design + planning specs are versioned;** the raw Claude Design HTML (`docs/design/raw/*.html`) is **gitignored /
local-only** — not in a fresh clone (open the relevant `raw/*.html` in a browser at build time to confirm exact
panels, since the bundles are minified SPAs).
