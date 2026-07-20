# Vyrobel — Project State (resume here)

**On a fresh session read THIS first, then `docs/DECISIONS.md` for the dated "why".** This is a
pointer map to the real code, not a copy of it — follow the links on demand. If anything here
conflicts with the repo, **trust the repo** and fix this file.

## Status (verified against the repo)

> **THE ONE-PARAGRAPH ANSWER.** The product is **COMPLETE, DEEP, DEMOABLE end-to-end, and safe for a real owner
> login**: a certified multi-tenant backend (PMS + channel manager + payments + owner accounting + maintenance
> + unified inbox), a **fully hardened auth chapter** (login · invites · password reset · change-password ·
> **2FA/TOTP**), and **~59 styled, browser-verified screens** across the owner portal, the operator back office,
> the public booking storefront and the **guest portal** (Phase 4b: Trips · Reservation · Manage · Check-in ·
> Arrival) — all on real scoped data under the honesty rule. **Five security/integrity
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
> the NEXT ACTION section at the foot of this file). **Phases 0, 1, 2, 3, 4a AND 4b of `docs/GAP-ANALYSIS.md` are
> DONE; Phase 4c (guest money + engagement — payments/wallet/receipts + reviews) is next.** Phase 4b = the GUEST
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
  HTML, **not in a fresh clone**. Suite: **`php artisan test` → 773 tests / 6738 assertions, all green** on
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

## INVARIANTS THAT MUST NEVER BREAK — #1–31 (the full guarantee stack)

> **All 31, grouped below.** #1–14 Phase 1 core (single write path · no double-booking · append-only ledger ·
> money in integer minor units · encrypted PII · tenant scoping · event discipline) · #15–18 Owner Accounting
> (attribution · append-only `owner_ledger` · period-locked statements · **never a cross-currency sum**) ·
> #19–20 the design pass (the honesty rule · scoped-render + guarded-mutation) · #21 Auth · #22 Maintenance ·
> #23–25 Unified Inbox (inbound exactly-once + grouping · deferred channels fail HONESTLY · inbox PII/GDPR) ·
> **#26 the `/app` back-office authorization gate** · **#27 the cumulative refund bound** · **#28 the
> guest-PII perimeter (+ the erasure trigger)** · **#29 the termination bound** · **#30 fine-grained
> permission enforcement** · **#31 the 2FA-pending state grants no authenticated access**.
>
> ⚠️ **The last five were all locked in the last three days, and FOUR of them closed a live hole that the
> fully-green suite never noticed** (#26 an owner reading every other owner's financials · #27 a €369 payment
> taking €1,107 in refunds · #29 a duplicate OTA webhook rewriting a completed stay · #30 any back-office role
> minting itself an Accountant). Every one was found by **reading**, never by a test failing. That is the
> single most important fact about this codebase's safety record — see META-LESSONS (b).

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

3. **The two RBAC defects from the 2026-07-16 audit, both fail-closed and neither exploitable — reviewed in
   Phase 0.3 (2026-07-20) and FORMALLY DEFERRED to the gates that build their fix:** `Super Admin`
   is **unassignable** (`model_has_roles.organization_id` is NOT NULL and part of the PK, so a null-team
   assignment cannot be stored — which also makes the Horizon gate dead in practice), and **no org-creation
   path provisions roles** (moot today: no such path exists). ⚠️ Whoever fixes enforcement further needs both
   facts together with #30, or they will wire a Super Admin bypass that can never fire.
   - **Defect 1 (provisioning) → Phase 7 (tenant provisioning).** There is no `Organization::create` in `app/`,
     so nothing to hook onto today; the org-creation transaction Phase 7 builds MUST call
     `OrganizationRoleProvisioner::provision()` atomically, or the first real org is dead on arrival. A global
     `Organization::created` hook was CONSIDERED and REJECTED in 0.3: it would add provisioning overhead +
     shared-permission-table contention to 90 org-creating test files (incl. the true-parallel concurrency
     suites) to guard a path that does not exist. The provisioner's docblock now carries this loudly.
   - **Defect 2 (Super Admin) → whenever the spatie schema is next touched.** The fix needs a migration on
     spatie's OWN `model_has_roles` (nullable team column + PK rework) — not casual; `Super Admin` stays out of
     `back_office_roles` until then. Both are fail-closed (an unprovisioned org is unusable, not insecure; an
     unassignable role grants nothing), so deferral is safe. Logged, not fixed — deliberately.

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

**697 tests / 6468 assertions, all green. "Green here means the whole guarantee stack holds"** — Phase 1
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
- **Code** → `app/Services/` (incl. `InboxService`, `GuestMatcher`, `MaintenanceService`, owner-accounting services) ·
  `app/Payments/` · **`app/Payouts/`** · **`app/Messaging/`** (channel interface + ManualChannel + Deferred skeletons + resolver) ·
  `app/Services/Channex/` · `app/Jobs/Sync/` · `app/Actions/Reservations/` · **`app/Mail/`** · `app/Http/Controllers/` (+ `Auth/`, `Operator/`, `Owner/`) ·
  `app/Http/Middleware/` · `app/Livewire/` (+ `app/Livewire/Owner/`, `app/Livewire/Operator/`) ·
  `app/Support/` (`Tenancy/`, `Owner/`, `Money/`, `Events/`, `Media/`, `Pdf/`, `Folio/`) · `app/Models/` (+ `Scopes/`, `Concerns/`) ·
  `database/migrations/` + `database/seeders/DemoSeeder.php` · `tests/Feature/` + `tests/Unit/` + `tests/Support/`.

## NEXT ACTION — CUSTOMER SIGNAL ARRIVED (2026-07-20); Phases 0–3 + 4a DONE; NEXT = Phase 4b in `docs/GAP-ANALYSIS.md`

> **THE SIGNAL CAME. The "don't build blind" framing below is SUPERSEDED.** A warm operator contact (Fatima)
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
