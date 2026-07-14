# Vyrobel — Project State (resume here)

**On a fresh session read THIS first, then `docs/DECISIONS.md` for the dated "why".** This is a
pointer map to the real code, not a copy of it — follow the links on demand. If anything here
conflicts with the repo, **trust the repo** and fix this file.

## Status (verified against the repo)

- **HEAD `2c2979a`** on `main`, working tree clean (only the untracked design-source exports under
  `docs/design/` remain uncommitted — the raw Claude Design HTML). Suite: **`php artisan test` → 340 tests
  / 3831 assertions, all green** on genuine **MySQL 8** (`vyrobel_test`). **PHPStan (Larastan) level 5, 0
  errors** (`--memory-limit=1G`). **Pint clean.** Queue = **Redis/Horizon live** (`QUEUE_CONNECTION=redis`,
  `REDIS_CLIENT=predis`; `database` documented as fallback). (This HEAD names the last commit at the time
  of writing; the final docs-only commit advances it by one with no state change.)
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
- **DESIGN PASS — COMPLETE. The whole styled surface over the certified backend is done — 17 screens:**
  **owner portal (4)** · **operator core (5) + Units (1) + Maintenance board & work order (2) + Inbox list &
  conversation (2)** · **public booking storefront (3, incl. the real checkout WRITE)**. All on the certified backends, honesty rule enforced per-screen by a render/
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
  operations (calendar/front-desk/housekeeping/folio/dashboard/units/maintenance), an owner could read their
  statements/payouts/units + download a PDF, and a guest could book direct (browse→room→checkout→
  confirmation, a real guarded write) — the storefront now shows real photos. See "NEXT ACTION".
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
  **`App\Services\GuestMatcher`** (shared with `BookingImportService`). Unstyled operator stub: `App\Livewire\InboxList`
  + `App\Livewire\InboxThread` + `App\Http\Controllers\Operator\InboxActionController` (`/app/inbox`). Design:
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

**Design pass (presentation-only — these govern every styled screen, and hold across all 13):**
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

## Deferred / outstanding register (do NOT build ad hoc; see DECISIONS.md)

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
- **The remaining design-only screens** — **~89** of the ~119-file Claude Design library (`docs/design/
  CATALOGUE.md`) map to FUTURE modules with **no backend today** and were deliberately NOT built: the full
  **guest portal** (~32), **unified inbox** (Operator/Owner/Guest Messages), **reviews**, **subscription
  billing / paywall**, **transactional emails** (~9), and **analytics** (Reports/Insights, ADR/RevPAR
  trends). Do NOT build backend for these ad hoc — each is a gated module. *(The **maintenance** module's
  BACKEND is now built — see 2026-07-14; only its STYLED UI is deferred, an unstyled contract stub renders.)*
  *(The "~89" is an estimate; `docs/design/CATALOGUE.md` is the authoritative per-file inventory and
  backend-status legend — read it before starting any new screen.)*
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

**Other deferrals:** promo codes + deposits on the booking engine (schema only); `guests.stripe_customer_id`
/ saved cards (blind-index story); **auto-FX provider** (statements take an operator-supplied rate — no
silent float); plus standing Phase 1 deferrals (blind-index guest matching,
gap-free invoice numbering, dated out-of-order ranges, `inventory_blocks.quantity`, tenant-custom
amenities, same-day-turnover housekeeping dedupe).

## Regression net — `php artisan test` (MySQL `vyrobel_test`)

**340 tests / 3831 assertions, all green. "Green here means the whole guarantee stack holds"** — Phase 1
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

Per-screen tests: `OwnerStatementViewTest`, `OwnerDashboardViewTest`, `OwnerPayoutsViewTest`,
`OwnerMyUnitsViewTest` (+ existing `OwnerPortalIsolationTest`). The **design-pass honesty rule** — REAL →
wire live · DERIVED → from real figures only · DEFERRED → stubbed "not yet available" · NO-BACKEND →
disabled "Soon" (never a fake action/IBAN/date/download); reconcile money to the cent; owner isolation
holds; guest PII never exposed — is locked in `docs/DECISIONS.md` (2026-07-11).

## Operator screens — STYLED & LIVE (COMPLETE — 5 core + Units)

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

Per-screen tests: `OperatorRackViewTest`, `OperatorFrontDeskViewTest`, `OperatorHousekeepingViewTest`,
`OperatorReservationDetailViewTest`, `OperatorDashboardViewTest`. The **operator-mutation pattern**
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

## NEXT ACTION — core product COMPLETE & demoable; choose the next track

**The buildable core is DONE and demoable end-to-end.** The certified backends (multi-tenant PMS +
channel manager + Stripe payments + owner accounting/payouts) now have their **entire styled surface**
live: the owner portal, the operator back office (calendar · front desk · housekeeping · reservation+folio
· dashboard · units · owners), and the public booking loop (browse → room → checkout → confirmation, a real
guarded write). **A real hybrid operator could run day-to-day operations and take a direct booking today,
and an owner could read their statements/payouts.** No unstyled stubs remain. **Real auth now fronts all of
it** (Phase A + B): an operator signs in, invites an owner, and that owner sets a password and reaches
their portal — so a live demo no longer needs a dev-login shim. **Dev email is wired to Mailpit** (invite
emails land in a viewable inbox); the invite flow covers **owner + Hotel Manager + Accountant**. Before a
real external demo, run Mailpit; production still needs a real mail provider (see the auth deferrals above).

**Pick ONE — each needs a written, gated decision; NONE is started. The core product is complete —
further building is lower-leverage than one real customer conversation:**
- **(a) CUSTOMER VALIDATION — RECOMMENDED.** The product is complete & demoable. Stand up **Mailpit**
  (`MAIL_MAILER=smtp` → Mailpit) so the invite email actually lands, then demo the **full loop to a real
  hybrid operator**: public booking → operator manages the reservation → operator invites the owner →
  owner sets a password → owner sees their statements/payouts with **money reconciling to the cent**. The
  wedge (genuine owner accounting for a mixed hotel/apartment/villa portfolio) is now demoable; let real
  feedback drive the roadmap over speculative breadth. No product code — config + a demo + discovery.
- **(b) Demo polish** — any sharpening for a live showing (seed richness, copy, a scripted walkthrough).
  Small, in service of (a). (Mailpit + Manager/Accountant invites are now DONE — see 2026-07-13.)
- **(c) Close a live-integration checkpoint** when accounts exist — **Channex staging** (ARI/webhook/HMAC
  vs a real account) or the **Stripe Connect payout rail** (owner disbursement KYC). The only two money/
  network rails unproven against reality; run `docs/prompts/live-integration-checkpoint.md`.
- **(d) A new design-only module** — **Unified Inbox** (WhatsApp/email/OTA/SMS — the largest remaining
  differentiator), or in-product polish (Stripe card entry at checkout · room photos/descriptions/reviews).
  Each is a gated, phased build like Owner Accounting — no backend exists today.

**If resuming design/build work:** inherit the relevant role design system (owner / operator / public — do
NOT redefine it); wire live data through the existing services (call, never modify); honour the **design-
pass honesty rule + the scoped-render/guarded-mutation discipline** (invariants #19–20 above); **per-gate
commit + per-screen render/isolation (+ action-persistence) test**; wire the certified services, never a
parallel write path. Read `docs/design/CATALOGUE.md` first (per-file backend-status legend). **The `.md`
design specs (`CATALOGUE.md`, `design-system-brief.md`, `owner-portal-spec.md`) ARE versioned;** the raw
Claude Design HTML (`docs/design/raw/*`, 104MB of self-unpacking bundles) is the approved design source but
is **gitignored / local-only** (regeneratable) — not present in a fresh clone.
