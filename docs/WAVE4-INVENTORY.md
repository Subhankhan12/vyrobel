# Vyrobel — WAVE 4 COMPLETE FEATURE INVENTORY

**Purpose.** Guarantee **nothing is silently dropped** when Wave 4 is built, and surface every backend decision *before* a line of UI is written. Every feature on all **13** Wave-4 design pages is listed and tagged, with the exact model/service/column/route that backs it — or the exact gap that doesn't. **Analysis only — no app code was changed producing this. READ-ONLY.**

**The Wave-3 lesson governs this whole document: A UI-SHAPE MATCH IS NOT A BACKEND MATCH.** Wave 3's owner inbox was rated READY, matched an existing service, and *still* needed a schema change to be **SAFE** (its scope key didn't exist, so `OwnerScope` never ran). So every ✅ below is checked twice — does the backend exist, **and** is it safe/correct for *this screen's* shape, scope, and write? Where those diverge, the verdict says **SAFE-vs-WORKS** explicitly.

**Status:** 2026-07-17 · repo at `18c1fde` (Waves 1–3 complete; `/app` gate #26, refund bound #27, terminate bound #29, permission enforcement #30 all live; 556 tests green). Decoded from `docs/design/raw/*.html` (gitignored SPA exports: JSON-in-`<script type="__bundler/template">` markup + a `data-dc-script` logic class). Six clusters, thirteen pages:

| Cluster | Pages |
|---|---|
| **Rates** | Operator Rates · Owner Rates |
| **Channels** | Operator Channels |
| **Booking-creation** | Operator New Reservation · Operator Group Booking |
| **Portfolio** | Operator Portfolio |
| **Settings / RBAC** | Operator Roles · Operator Settings · Operator Property Settings · Owner Settings · Owner Team |
| **Onboarding** | Vyrobel Onboarding · Owner Onboarding |

## The tags
| Tag | Meaning |
|---|---|
| ✅ **BUILD-NOW** | Backend genuinely exists **and is safe for this screen's use**. Model/service/column/route cited + verified. |
| 🔨 **NEEDS-BACKEND** | Real feature, no backing. Exact column/table/service named + sized: **S** (column/scope/derivation) · **M** (table+relation+service) · **L** (subsystem). |
| 📋 **DECORATIVE/FAKE** | Visual-only, hard-coded, client-simulated, `localStorage`-only, or an inert control. Literal quoted so we knowingly do **NOT** fake it. |
| ⬜ **OTHER-MODULE** | Belongs to an unbuilt/account-gated module (billing, live channels, analytics, 2FA). Note + defer. |

---

# 🔴 STOP — THE HEADLINE FINDINGS (read before anything else)

Wave 4 is **not** the "presentation over a certified backend" that Waves 1–2 were. Five findings dwarf every per-element tag below.

### 1. 🪤 The channel manager is INERT in production — an entire enum is READ as a gate but WRITTEN only by a test factory
`channels.status` is `enum('disconnected','connected','error','disabled')` default `disconnected` (`…020000_create_channels_table.php:28`). `EnqueueAriPushOnInventoryChange.php:182` gates the whole outbound ARI push on `->where('status','connected')`. **Nothing in `app/` ever writes that value** — an exhaustive grep finds the *only* writer is `database/factories/ChannelFactory.php:29` (a test factory). The inner gate is the same: `channel_mappings.status='active'` is read by `ChannelMappingGuard.php:116` + `AriSnapshotBuilder.php:113` and written only by `ChannelMappingFactory`. So in production **every channel is `disconnected` forever, the push gate returns empty, and the certified channel manager pushes nothing.** The 556-test suite is green because a factory sets a state the application cannot reach. This is the exact class of the `payment_status='refunded'` dead-enum — an enum that *looks* wired because a test drives it. **The Channels UI is the missing writer.**

### 2. 🪤 Two whole screens have NO backend and would be net-new subsystems, not presentation passes
- **Operator Portfolio** has **no route, no component, no nav entry, and no concept**: there is exactly **one** `Property::create` in the codebase (`DemoSeeder.php:160`), no `PropertyService`, no property-create path, and `organizations.mode` (`single_property|chain|property_manager`) is **read by zero code**. Every operator screen hard-codes `Property::query()->first()`. A multi-property portfolio is the UI that would *first require the entire multi-property subsystem to exist*.
- **Vyrobel (operator) Onboarding** *is* the missing tenant-provisioning path: **`Organization::create` appears in ZERO `app/` files** (every occurrence is a seeder or test), there is **no user-registration controller**, and `OrganizationRoleProvisioner::provision()` — which syncs the 13 roles — has **zero production callers** (only `DemoSeeder.php:75`). The wizard's leaf services exist; its spine (create org → provision roles → create property/room-type/unit → seed availability) does not.

### 3. 🪤 Owner Rates would LEAK across owners — the Wave-3 shape, and worse
`owner_id` lives on **`units` only**. `RatePlan`/`RateCalendar`/`Restriction`/`Availability` do **not** use `BelongsToOwner` and have **no `owner_id`** — so `OwnerScope` **never runs on them**. An owner-portal read of `rate_calendar` returns **every owner's pricing** (exactly Wave 3's `message_threads` hole). Worse than a missing column: `OwnerOccupancy.php:20-31` documents *why you cannot just add `owner_id`* — a room type is shared (a hotel room, an operator unit, and another owner's unit can all sit under one `room_type_id`). Occupancy escapes this by keying on physical `unit_id`; **a rate has no per-unit fact to key on.** Owner Rates needs a schema/policy decision to be **SAFE**, not merely to work.

### 4. 🪤 The Roles page is the escalation weapon Wave 2 warned about — pointed at a catalog it cannot honor
`Operator Roles` draws full role CRUD (New/Delete role) + **20 live per-permission toggles** over an **editable** catalog. Vyrobel's catalog is **fixed** in `config/authorization.php` and provisioned to the DB from code; `OrganizationRoleProvisioner` re-syncs from config (silently overwriting UI edits), and `EnsureBackOfficeAccess` keys on the config role list (a UI-created role can't enter `/app`). And **nothing in the app does general role assignment today** — the only assign call sites are `OwnerInviteService` (3 fixed slugs) + seeders. A Roles screen that assigns/edits roles, reachable by a lesser role, is precisely the Wave-2 escalation (Housekeeping → self-invite Accountant → `payments.refund`) at full power. It must be redesigned to **read the 13 catalog roles + assign one**, gated `can:roles.manage`, with every write re-validated server-side.

### 5. 🪤 Rates are keyed to room-type; both rate designs draw them per physical unit
`rate_plans.room_type_id` (no `unit_id`, cannot have one); `rate_calendar unique(rate_plan_id,date)`. The seeder proves the hazard: `DemoSeeder.php:190` gives room type `doubleRoom` **three units (101/102/103)** under **one** rate plan. Editing "Room 101's rate" in the drawn per-unit grid would write that one plan's calendar — silently changing 101, 102 **and** 103. A per-unit rate grid is a lie over a per-room-type table unless the product commits to (and enforces) 1-unit-per-room-type for owned inventory.

**The through-line:** green tests and matching UI shapes are necessary, not sufficient. Wave 4's most convincing screens (Portfolio's self-consistent fabricated totals; Channels' live sync pills; Roles' permission matrix) are the *least* backed.

---

# 🪤 TRAP REGISTER — consolidated, most-dangerous first

| # | Trap | Verified fact | Where it bites |
|---|---|---|---|
| **T1** | **DEAD-ENUM (channel lifecycle)** | `channels.status` + `channel_mappings.status` are READ as ARI-push gates but WRITTEN only by factories. No app code transitions a channel. | Channels · Rates (sync pills) · Portfolio ("Expedia disconnected") |
| **T2** | **PARALLEL-STRUCTURE (dormant tables)** | Schema-only, **zero business-code writers**: `groups`+`reservations.group_id`+`Group`, `Subscription`/`Plan`/`Invoice`/`ApiKey`/`Webhook`/`CorporateAccount`, `Setting` (unused). `taxes`/`fees`/`add_ons` ARE wired; `property_policies` IS read. | Group Booking (extend `groups`) · Settings (extend billing tables) · everywhere (use `Setting`, don't invent) |
| **T3** | **OWNER-SCOPE LEAK (no `owner_id`)** | `RatePlan`/`RateCalendar`/`Restriction`/`Availability` have no `owner_id` + no `BelongsToOwner` → `OwnerScope` never runs. `Owner` itself is `BelongsToOrganization`, **not** `BelongsToOwner`. | Owner Rates (rate read leaks all owners) · Owner Settings (self-edit needs explicit `where user_id=auth()->id()`) |
| **T4** | **MISSING PERMISSION** | `channels.manage` is **absent** from the 45-permission catalog; **no owner rate/proposal permission** exists; `roles.manage`/`settings.manage`/`taxes.manage`/`fees.manage`/`billing.manage` are reachable **only** via the `['*']` wildcard (these screens are their first-ever consumers). | Channels · Owner Rates · Roles · Settings — flag, do NOT invent |
| **T5** | **UI-SHAPE ≠ BACKEND (per-unit vs per-room-type)** | Rate tables are per `room_type`; both rate designs + both booking wizards + Portfolio draw per physical **unit**. No **per-unit availability read exists** (`AvailabilityService` returns per-room-type counts). | Rates (grid saves) · New Reservation + Group (unit picker) |
| **T6** | **ATOMICITY (nested transactions + events)** | `createBooking` wraps its **own** `DB::transaction` and fires domain events **after return**. Looping it inside an outer group transaction → phantom `reservation.confirmed` events for rolled-back rows. | Group Booking |
| **T7** | **RBAC self-provision defects (still open)** | `Super Admin` **unassignable** (`model_has_roles.organization_id` NOT NULL + in PK); **no org-creation path provisions roles** (only caller of `provision()` is the seeder). | Roles · both Onboarding wizards |
| **T8** | **FABRICATION (self-consistent)** | Every headline figure on every page is a hard-coded JS literal; **all pricing is client-side float math over major-unit constants** (invariant #17 wants integer minor units + no cross-currency sum). Portfolio's fabricated totals even reconcile internally (rooms sum, revenue sums, RevPAR = ADR×occ). | All 13 pages — do not port a single number |
| **T9** | **PII PERIMETER (#28) / PCI** | New Reservation renders other guests' **email + phone** in the search dropdown (CRM-only per Wave-2 decision #4) and captures a **raw PAN**; owner CSV export emits guest email/phone. | New Reservation · Owner Settings export |
| **T10** | **INERT "SAVE" CONTROLS** | Every "Save changes"/"Push"/"Connect"/"Create role"/"Invite" is a `setState`+toast; owner guardrails `saveGuard: () => {}` is a **literal empty function** that still fires "Guardrails saved". `buDerived` ("update derived plans too") is **never read**. | All pages — the Wave-1 Payout-Run failure mode, at wizard scale |

**Enum-write truth table (the T1/T2 core):**

| Status the UI shows | Real column | Written by app code? |
|---|---|---|
| Channel "Synced/Connected" | `channels.status='connected'` | ❌ factory only → **DEAD** |
| Channel "Syncing/Queued/Live" | — | ❌ not even enum members → **FABRICATED** |
| `channels.status='error'`/`'disabled'` | real enum | ❌ never written → **DEAD** |
| Mapping "active" | `channel_mappings.status='active'` | ❌ factory only → **DEAD** |
| Group "Tentative" hold | `reservations.status='tentative'` | ❌ `createBooking` writes `'confirmed'` → **DEAD** |
| Group "Option expires" | `groups.pickup_deadline` | ❌ never written → **DEAD** |
| Booking-source Phone/Booking.com/Airbnb/Expedia | `reservations.source` enum | ❌ not enum members → **INVENTED** |
| Folio "closed", `payment_status` "refunded/charged_back" | real enums | ❌ never written → **DEAD** |
| Subscription "paused" banner | `subscriptions.status='paused'` | ❌ Subscription has 0 refs → **DEAD** (omit by name) |
| User "suspended/disabled" | `users.status` | ❌ only active/invited/expired written → **DEAD** |
| Owner proposal pending/approved | — | ❌ **no table at all** → ABSENT |
| `restrictions.stop_sell` / `min_stay` | real | ✅ writable via `setRestriction` (no caller yet) — but **not honored by the direct booking engine** |

---

# CONSOLIDATED 🔨 NEEDS-BACKEND — grouped by shared backend (build once)

## Distribution (Channels + the Rates "push")
- **Channel/mapping CRUD** (M) — no controller/route/service/seeder creates a `Channel`; build thin Eloquent CRUD reusing `ChannelMappingGuard`. `SyncJob`/`SyncLog` already back a real "Sync activity" feed; `AriPushJob` already backs "sync now".
- **`channels.status` writer + a real connect flow** (M–L, account-gated) — decide who transitions a channel and when; the Channex seam exposes **only `pushAri()`** (no `connect`/`test`/`pull`), and live Channex is the OUTSTANDING staging checkpoint.
- **`channels.manage` permission** (S) — add to the catalog before any guarded channel write (else `can:channels.manage` 403s everyone, including the org admin).
- **A demo channel seed** (S) — `migrate:fresh --seed` creates **zero** channels → the page renders empty.

## Rates (over the certified-but-uncalled `RateAndRestrictionService`)
- **Route + `RatesGrid` component** (M); flip the shell's reserved `['rates',…,null]` stub.
- **STRUCT-1 grid decision** (decision, then S) — per-room-type rows (with "N units" sub-label) vs a 1-unit-per-room-type rule.
- **Wire `setRate` + `setRestriction`** (S) — both exist, both have **zero callers**; the cell/bulk editor becomes the first. POST→PRG (#20).
- **Derived-plan propagation** (M) — walk `RatePlan::derivedRates()` applying `adjustment`; not seeded, never called; the design's `buDerived` toggle is inert.
- **Occupancy/ADR tiles** (S) — real from `availability`; decide window + per-currency grouping (`rate_calendar.currency` is per-row).

## Booking-creation
- **Per-UNIT availability read** (M) — a `UnitAvailabilityService::freeUnits()` over `reservation_units` ∪ `inventory_blocks`; `AvailabilityService` is room-type-count-keyed. Needed by **both** wizards' unit pickers.
- **Guest search by name** (S) `whereLike` over plaintext names; **by email/phone impossible** (encrypted) → the standing **blind-index** deferral (L, shared with Global Search).
- **`GroupBookingService::create()`** (M) — atomic: `Group::create` + inline the occupy loop for all members in **one** transaction + emit events **once post-commit** (T6). Extend `groups`/`group_id` (T2), don't parallel.
- **Tokenized card capture** (M) — Stripe Elements; raw PAN must never reach the DB (T9). The charge itself is real via `FolioService::recordPayment`.
- **Group folio roll-up / master-vs-individual** (M) — a decision, not a table.

## Settings / RBAC
- **`RoleAssignmentService`** (M) + redesign Roles to read+assign (drop CRUD/toggles) — the #4 headline.
- **A settings write path** (M ×N) — **nothing in `app/` writes `Property`/`PropertyPolicy`/`Tax`/`Fee`/`Setting`**; each surface (general, policies, tax/fee CRUD) needs a tenant-scoped service. Columns exist; writers don't.
- **Owner-self-profile writer** (S) — with an explicit `where user_id = auth()->id()` guard (T3).
- **Net-new subsystems the designs assume:** owner sub-team (L), notification routing/preferences (L), 2FA enforcement (L — columns exist, login never checks them), invoice numbering (M–L, extend the dormant `invoices` table), branding/white-label (S–M, `organizations.branding` json is unused).

## Onboarding (the operator wizard is a subsystem, not a screen)
- **Tenant-provisioning spine** (L) — org create (under `runAsSuperAdmin`) + user registration (no controller exists) + wire `OrganizationRoleProvisioner::provision()` + assign the founder `Property Owner` + property/room-type/unit creation services. The chicken-and-egg (T7) means it **cannot live under `/app`** and **cannot be gated by the normal permission system**.
- **Owner payout account** (L + account-gated) — **no bank/IBAN column exists anywhere** (a locked omission documented by name across six shipped screens); the micro-deposit verification the design promises has no rail (Stripe Connect throws).
- **Onboarding progress persistence** (S) — no step/`onboarded_at` column; both wizards keep step state in client `setState` only.

---

# CONSOLIDATED 📋 DECORATIVE / FAKE — do NOT port these

- **Every headline figure is a literal.** Fabricated people/orgs (Seaside Group/Villas, Amara Okafor, Marco Reyes, …), fabricated metrics (Portfolio "€214,600 +9%", Channels "41 OTA bookings", Rates "+8% vs Aug"/"+9% vs last year", Owner Rates "€465 comp average / 5% below market"), fabricated statuses (all channel sync pills), fabricated references (`VYR-10442`, `GRP-######`).
- **All pricing is client-side float math** over major-unit constants (T8). New Reservation's VAT 10% even contradicts the demo seed (VAT 23%, `DemoSeeder:236`). Never port the JS arithmetic — the real path is integer minor units via `rate_calendar.price_minor` + `FolioService::computeTax`.
- **`localStorage`-as-persistence** — every owner-page write (`vyOwner*`, `vyrobel:owner:*`) and the rates overlay (`vyrobel:rates:*`) masquerade as saved data. They are design-tool state, not a backend contract.
- **Inert controls (markup, no handler / empty handler):** Roles "Add member" + per-card "Manage"; Owner Rates "Counter…" (no handler) and `saveGuard: () => {}`; Channels per-card "Manage/Reconnect"; Portfolio switcher + all drill-down links (carry no property id); the bulk-edit "update derived plans too" checkbox (never read).
- **Already-rejected devices (closed decisions — do NOT reopen):** the "Workspace paused" read-only banner (omitted by name at `OperatorOwnerDetail.php:101-102`); the design's own nav grouping (reuse `<x-operator.shell>` verbatim — it omits built items like Units/Payout run and adds unbuilt Insights/Reports).
- **Fabricated capabilities the product deliberately lacks:** live two-way OTA sync + brand connectors (Booking.com/Airbnb/Expedia/Vrbo — `channels.provider` enum is `channex` **only**); owner micro-deposit bank verification; "monthly performance digest"; email/phone verification (any 6 digits confirms); 2FA (columns exist, no enforcement); smart-pricing demand curve (`sin()`).

---

# BUILD-READINESS + SAFE-vs-WORKS — per page

| Page | WORKS? | SAFE? | Verdict |
|---|---|---|---|
| **Operator Rates** | Core only (edit a rate/restriction, read real occupancy) | ✅ tenant-scoped, no owner dimension | **BUILDABLE after 2 decisions** (STRUCT-1 grid shape; tile window/currency). First caller of a certified service. ~30% fabricated chrome (smart pricing, trends, live sync) — omit/stub. |
| **Owner Rates** | Trivially (static mock) | ❌ **leaks all owners** (T3); owner has **no rate permission** (T4) | **⛔ DECISIONS-FIRST.** Only honest surface today = read-only own occupancy via `OwnerOccupancy`. Proposals/guardrails/comp-set = 3 net-new backends. |
| **Operator Channels** | ❌ empty DB (no seed), no CRUD | Partial (list is tenant-safe; status vocab dead; no permission to gate writes) | **THIN HONEST CONSOLE buildable** (list + real commission + `SyncJob` activity, property-scoped) after seed + `channels.manage`. **NOT the design as drawn** (brand cards, live pills, connect/reconnect are fabricated/gated). |
| **New Reservation** | ✅ the booking **write** (via `createBooking`) | ❌ as drawn — 3 mandatory guards (per-unit availability read; strip PII from picker; tokenize PAN) | **MOSTLY-READY for the write; screen needs N1(M)+N2(S)+R1(M).** Single guarded path preserves no-double-booking for free. |
| **Group Booking** | ❌ **no group write exists** | ❌ naive loop breaks atomicity (T6) + parallel-structure (T2) | **NEEDS-BACKEND.** Buildable on solid foundations (locking core + `groups` + FK exist) but needs the atomic service + billing-model decision + tentative-hold writer. **TIER1-SPEC "MOSTLY-READY" is too optimistic.** |
| **Portfolio** | ❌ no route/component/concept, 1 property | n/a (nothing to scope) | **⛔ DO NOT BUILD.** ~90% fabricated; the forcing-function for the deferred multi-property/property-switcher subsystem (E1). Honest interim = the single-property `OperatorDashboard`. |
| **Operator Roles** | ❌ reads the wrong role set; all writes inert | ❌ escalation weapon if writes persist | **⛔ REDESIGN** to read 13 catalog roles + assign one (`RoleAssignmentService`, `can:roles.manage`, server-side re-validate). Drop CRUD/toggles. |
| **Operator Settings** | Partly (backed columns) | ✅ tenant-scoped | **SLICE IT** — ship General/Policies/currency/timezone writers (S–M each; no write path exists yet); stub VAT-CRUD/invoicing/notification-routing/commission-default (M–L). De-conflict with Property Settings. |
| **Property Settings** | Partly (name/policy/tax/fee over wired tables) | ✅ tenant-scoped | **BUILDABLE (M)** for backed fields; self-checkin/pause-bookings/branding have no columns; delete-property needs a destructive-op review. **One source of truth with Operator Settings.** |
| **Owner Settings** | Only self-profile | ❌ `Owner` unscoped → needs explicit `user_id` guard (T3); export leaks guest PII | **~80% fabrication.** Build only the self-profile writer; stub/omit the rest. |
| **Owner Team** | ❌ nothing backs it | n/a (entities don't exist) | **⛔ DEFER** — net-new owner sub-tenancy subsystem (L). De-duplicate with Owner Settings' team section. |
| **Vyrobel Onboarding** | ❌ nothing writes | ❌ must run outside `/app` as super-admin; no user can be authorized to create an org (T7) | **⛔ NOT READY — tenant-provisioning EPIC, not a screen.** Never ship the channels step. |
| **Owner Onboarding** | Step 1 only (confirm details) | ✅ owner already activated (`/owner` authorizable) | **Ship Step 1 + next-steps card; OMIT Steps 2–3** (IBAN payout has no backend + fabricates micro-deposit verification; prefs have no backend). |

## Where `docs/TIER1-SPEC.md` is wrong (corrections from this inventory)
TIER1-SPEC rates **every** Wave-4 page **MOSTLY-READY** (except Maintenance Settings). Corrections, evidence-backed:

1. **Group Booking: MOSTLY-READY → NEEDS-BACKEND.** No group write exists; the "small method" hides an atomicity hazard (T6) and the parallel-structure trap (T2).
2. **Portfolio: MOSTLY-READY → DO-NOT-BUILD.** No route/component/concept; one property; `organizations.mode` read by zero code.
3. **Roles: MOSTLY-READY → REDESIGN.** Fixed-vs-editable catalog mismatch + the escalation weapon; the design's CRUD cannot be honored.
4. **Owner Rates: MOSTLY-READY (policy gate) → NEEDS-BACKEND-FIRST.** Understates it: a **schema/leak** gate (T3) *and* a policy gate *and* three net-new backends.
5. **Channels: MOSTLY-READY → the CRUD is buildable, but the lifecycle is dead** (T1), `channels.manage` is missing (T4), and no seed exists. TIER1-SPEC's "thin CRUD controller" is accurate for the *list*; the design's live-sync story is not.
6. **New Reservation: MOSTLY-READY holds for the write only** — the screen needs three safety guards (T5/T9) first.
7. **Vyrobel Onboarding: MOSTLY-READY "orchestration glue" → refuted for the spine.** The org/user/property creation code does not exist; only the leaf rate/availability/role/invite services it would call are real.

## RECOMMENDED BUILD ORDER
**Tier A — build now (real backend, small/decided surface):**
1. **New Reservation** — the highest-value operator screen; `createBooking` is certified. Land N1 (per-unit availability read, reused by Group later), N2 (name search), R1 (tokenized card), and the PII strip; then the wizard.
2. **Operator Rates (core)** — first caller of `setRate`/`setRestriction`. Make the STRUCT-1 decision, render per-room-type, wire cell + bulk, real occupancy tiles. Omit smart-pricing/live-sync.
3. **Operator Settings / Property Settings (backed slices)** — one de-conflicted surface; ship the real-column writers (check-in/out, currency, timezone, cancellation/deposit, min/max stay, tax/fee CRUD). Stub the rest honestly.

**Tier B — build after a decision/permission lands:**
4. **Operator Channels (thin honest console)** — after adding `channels.manage`, a demo seed, and deciding the status writer. List + commission + `SyncJob` activity; connect/sync stay "Soon".
5. **Group Booking** — after New Reservation (reuses N1); build the atomic `GroupBookingService` + the master/individual billing decision.
6. **Operator Roles (read+assign redesign)** — after `RoleAssignmentService` + the fixed-catalog product decision.

**Tier C — do not build in Wave 4 (subsystem/decision/account-gated first):**
7. **Owner Rates** (owner-scope schema + policy + 3 backends) · **Portfolio** (multi-property subsystem) · **Owner Team** (owner sub-tenancy) · **Vyrobel Onboarding** (tenant-provisioning epic) · **Owner Onboarding Steps 2–3** (payout/prefs backends). **Owner Onboarding Step 1** may ship with the Owner Settings self-profile writer.

---

# PER-PAGE FULL INVENTORIES

Shared chrome (all pages) is stated once: brand mark, ambient light-streaks + floating colour orbs (gated on an **editor-only** `motion` `data-props` boolean, not app state), `vyDrift`/`vyShimmer` keyframes, the `VYC` currency harness, `window.__vyToast`, and hard-coded nav badges. **Reuse the built `<x-operator.shell>` / owner shell verbatim** — never port the design's nav grouping. These are 📋 and are not repeated per page.

---

## 1. Operator Rates — `/app/rates` (net-new)
14-day × up-to-5-unit rate calendar; per-cell editor (rate/min-stay/stop-sell); bulk-edit modal; fake smart-pricing banner; fake channel-sync ribbon + card; static rate-plans card; pacing panel. 44 on-click; every figure a constant; "push" is a `setTimeout` sim.

**WRITES:** set rate → `RateAndRestrictionService::setRate()` (`:41`, exists, **0 callers**); set min-stay/stop-sell → `setRestriction()` (`:71`, exists, **0 callers**); adjust totals → `AvailabilityService::recompute()`; push → event→`AriPushJob`→`pushAri()` (chain real, default `FakeChannexClient`, live Channex 🔴).
**DEAD-ENUM:** channel sync pills (Synced/Syncing/Queued/Pending) — never written + not enum members (T1). `stop_sell`/`min_stay` — writable but **not honored by the direct booking engine** (flag).
**PARALLEL-STRUCTURE:** `RateAndRestrictionService` (two methods) + the three rate tables already exist — extend, don't recreate (T2). Building this makes it the **first production caller**.
**ISOLATION/PII:** all tables `BelongsToOrganization`; no PII; no owner dimension (operator sees all room types — correct). **SAFE at the tenant boundary.**
**PERMISSIONS:** `rates.view`/`rates.manage`/`availability.manage`/`restrictions.manage` all exist (`:84-88`), held by Property Owner/Group Admin/Hotel Manager/Revenue Manager. **Clean.**

| Feature | Tag | Backing / needs |
|---|---|---|
| Nav "Rates" active | ✅ | flip `shell.blade.php:36` stub → `route('app.rates')` |
| Sync ribbon (3-state) + "4 channels" | 📋 | derived from client `pending`/`pushing`; "4 channels" is a literal array; brands unmodelled |
| "Push now" | 🔨 account-gated | real chain, fake client, 4-stage bar is a sim |
| Window prev/today/next (±14d) + range label | ✅ | date-window → `?from=` GET + `rate_calendar.date` range |
| Unit filter ("5 units"/"3 units") | 🔨 S | counts hard-coded; maps to room_type/unit sets (STRUCT-1) |
| Smart-pricing toggle + per-cell suggestions | 📋 | no pricing engine (`sin()` curve) |
| Avg-rate tile (count-up) | ✅ | mean of in-window `rate_calendar.price_minor`; decide window+currency (ADR-like, Wave-1 trap) |
| "+8% vs Aug" | 📋 | hard-coded trend, no baseline stored |
| Booked-% + open-inventory tiles | ✅ | `availability.sold/total`, `available=total−sold−ooo−blocked` |
| Grid day-headers (occupancy bar) | ✅ | from `availability` |
| Unit rows (5-row array) | 📋 data / STRUCT-1 | real rows are per room_type; per-unit grid misrepresents saves |
| Cell "open" (editable price) | ✅ | `rate_calendar.price` (Money cast) |
| Cell "Booked" | ✅ | `availability.sold>0` / active `reservation_units` |
| Cell "Not for sale" | 🔨 S | `restrictions.stop_sell` via `setRestriction` |
| Cell "Closed" (maintenance) | ⬜ | `units.condition_status`/`availability.out_of_order` (Maintenance, built) — read-only, not a rate write |
| Cell editor: rate ± + min-stay + open toggle + Save | 🔨 S | Save = POST → `setRate` + `setRestriction` (first caller); "€" glyph should use `MoneyFormatter` |
| "Derived on save · Non-refundable €X" | 📋 | no derived plans seeded; propagation not built |
| Bulk modal (scope×nights×mode) + Apply | 🔨 S–M | loop of `setRate`; counts hard-coded |
| "Update derived plans too" checkbox | 📋 | **`buDerived` never read by `_applyBulk`** |
| Rate-plans card + "All channels"/"Direct only" pills | 📋 | derived plans supported but unseeded; no rate_plan↔channel model |
| Channels card (4 rows, sync statuses) | 📋 | dead-enum (T1) |
| Pacing panel + "+9% vs last year" | ✅ pct / 📋 trend | `pacePct` duplicates booked%; trend fabricated |

**SAFE-vs-WORKS:** SAFE; WORKS for the core only. ✅ **buildable after STRUCT-1 + tile-window decisions**, smart-pricing/channel-status omitted, push labelled account-gated.

---

## 2. Owner Rates — `/owner/rates` (net-new)
Read-only rates table (3 villas) + proposals card (Approve/Decline/**Counter…**) + pricing-guardrails card (Dynamic/Fixed, floor/ceiling, auto-approve, **Save**) + comp-set card. The owner **cannot edit any rate; every "save" is fake.**

**WRITES:** approve/decline proposal → **no table, no service**; counter → inert; save guardrails → `saveGuard: () => {}` (literal empty fn). **Every write unbacked.**
**DEAD-ENUM/ABSENT:** proposal pending/approved/declined = React state (no `rate_proposals` table); Dynamic/Fixed = no `pricing_mode` column; occupancy % = real via `OwnerOccupancy`.
**PARALLEL-STRUCTURE:** none to extend — proposals/guardrails/comp-set are three net-new backends.
**ISOLATION/PII (the Wave-3 carry):** ❌ `RatePlan`/`RateCalendar`/`Restriction` have **no `owner_id`, no `BelongsToOwner`** → `OwnerScope` never runs → **reading rates leaks every owner** (T3). No per-unit escape (a rate isn't a per-unit fact).
**PERMISSIONS:** `External Owner` = `owner_portal.view` **only**; **no owner rate/proposal/guardrail permission in the catalog** (T4) — flag, do not invent.

| Feature | Tag | Backing / needs |
|---|---|---|
| Proposals card ("2 to review", from→to, delta%) | 📋 | `PROP` hard-coded; no `rate_proposals` table |
| Approve / Decline | 🔨 L | setState+toast; real = table + apply via `setRate` + owner-scope + a permission owner lacks |
| "Counter…" | 📋 inert | **no handler**; no counter field |
| Approved/Declined badges | 📋 | in-memory `act[id]`, resets on reload |
| Current-rates table (Base/Weekend/Peak, view-only) | 🔨 decision+S | STRUCT-2 leak (T3); resolve owner→unit_ids→room_types or decide owners see no rates |
| Occupancy % (82/74/88) | ✅ | `OwnerOccupancy::forMonth($ownerUnitIds,$month)` (:48) — the SAFE unit-id-keyed derivation |
| Guardrails: Dynamic/Fixed, floor/ceiling, auto-approve | 🔨 M | no `pricing_mode`/`floor_minor`/`ceiling_minor` columns; needs an enforcement layer |
| "Save guardrails" | 📋 inert | `saveGuard: () => {}` + "Guardrails saved" toast — the most misleading control in the cluster |
| Comp-set ("€442 vs €465, 5% below market") | 📋 | all hard-coded, no data source; €442 also hits STRUCT-2 |

**SAFE-vs-WORKS:** neither SAFE nor ready. ⛔ **decisions-first.** Only honest surface = read-only own occupancy.

---

## 3. Operator Channels — `/app/channels` (net-new)
OTA-distribution console: stat strip, connected-channels list (Booking.com/Airbnb/Expedia cards), sync-activity feed, Expedia parity panel, booking-mix bar, Connect + Reconnect modals. 19 on-click; **not one handler reaches a backend.**

**WRITES:** "Sync all" → dispatch `AriPushJob` (fake=no-op, live 🔴; and it's per room-type, not per channel); "Connect"/"Reconnect" → **no `connect()` in the seam**; per-card "Manage" → **inert (no handler)**. **No channel CRUD exists anywhere** (a `Channel` is only ever created by a factory).
**DEAD-ENUM:** the headline (T1) — status vocab mostly dead/fabricated; `last_synced_at`/`last_error` have **no app writer** → any "last synced" is 📋.
**PARALLEL-STRUCTURE:** the whole pipeline exists (channels/mappings/guard/Channex-seam/ARI-push/import/`SyncJob`/`SyncLog`/`OverbookingConflict`) — build **CRUD only**, reuse it. `OverbookingConflict` is built + written + surfaced by **zero UI** (its natural home).
**ISOLATION:** `Channel`/`ChannelMapping`/`SyncJob` all `BelongsToOrganization`; channels are **per-property** (needs a property context). `credentials` = encrypted cast — any form must be **write-only/masked**, never render the key back.
**PERMISSIONS:** 🔴 **`channels.manage` absent from the catalog** (T4) — a `can:channels.manage` route would 403 everyone until added. Money-adjacent (stores creds + `commission_pct` → owner deductions).

| Feature | Tag | Backing / needs |
|---|---|---|
| Nav "Channels" active | ✅ (flip stub) | `shell.blade.php:35` `null` → `route('app.channels')` |
| "Workspace paused" banner | 📋 rejected | omitted by name at `OperatorOwnerDetail:101-102` — do not port |
| Expedia "sync stopped" alert | 📋 | keyed on fabricated `expediaDown`; Expedia unmodelled; would need dead `status='error'` |
| Stat strip (OTA 41/64, Direct 36%/+4%, Sync 2of3) | 🔨 S | derivable by `reservations.source` (date/cancelled ambiguity, B2); "connected" count = 0 today |
| Channel cards: brand name | 🔨 L / render `channels.name` | **OTA brand not modelled** (`provider='channex'` only); render `channels.name` ("Channex…"), never "Booking.com" |
| card status pill | 📋 dead | T1 |
| card bookings count | 🔨 S | `Channel::reservations()` real |
| card commission | ✅ | `channels.commission_pct` (real, read at `OwnerAttributionService:442-446`) |
| card "last sync 3 min ago" | 📋 | `last_synced_at` never written |
| card "Manage/Reconnect" | 📋 inert | no handler |
| Sync activity feed (5 rows) | 🔨 S–M | fabricated as drawn, but **`SyncJob` is the honest backing** — the one panel with purpose-built data |
| Expedia parity panel | 📋 + 🔴 | `expediaConnected` theatre; Expedia unmodelled |
| Booking-mix bar | 🔨 S by source / impossible by brand | `reservations.source` derivable; per-OTA-brand not modelled |
| Connect modal (marketplace picker → done) | 📋 + 🔴 | `setTimeout` theatre; no `connect()`; real create = Eloquent CRUD, creds 🔴-gated |
| Reconnect modal | 📋 + 🔴 | fully fabricated; no reconnect path |

**SAFE-vs-WORKS:** neither as drawn. A read-only list + commission + `SyncJob` activity is buildable (S–M) **after** seed + `channels.manage`. **MOSTLY-READY for a thin honest console; NOT the design as drawn.**

---

## 4. Operator New Reservation — `/app/reservations/create` (net-new)
7-step wizard (Stay→Guest→Rate→Arrival→Rules→Review→Payment) + done, with a live Booking-summary rail. `nextAction`/`advance` only `setState`; done shows fabricated ref `VYR-10442` + a fake QR.

**WRITES (all map to real methods):** `createBooking()` (`:80`, single guarded path: txn + `SELECT…FOR UPDATE` + Level-A count + Level-B unit) → `openFolio` → `postCharge` → `postAddOn`/`applyTax` → `recordPayment` (needs gateway token) → `authorizeDeposit`. Never insert `reservations`/`reservation_units`/`availability` directly (#9). Guest → `Guest::create` (as `BookingEngineService:140`) or new `GuestService::create` (none today).
**DEAD-ENUM:** invents four booking-**source** values (Phone/Booking.com/Airbnb/Expedia) not in the enum; `refunded`/`charged_back`/folio `closed` dead (not set here).
**PARALLEL-STRUCTURE:** none — reuse `AssignmentService`/`FolioService`.
**ISOLATION/PII (❗):** collecting a guest is consistent (encrypted casts); **rendering** the picker's email/phone + lifetime spend **breaches the Wave-2 CRM perimeter (#28)** → name-only search (email/phone search is impossible anyway — encrypted); **raw PAN capture** must become a Stripe token (PCI).
**PERMISSIONS:** `reservations.create` ✅ + `reservations.assign` ✅ (Reservation Agent holds create but **not** assign, and the wizard forces a unit pick — a real decision) + `payments.record` ✅.

| Feature | Tag | Backing / needs |
|---|---|---|
| 7-node stepper / draft badge / resume | 📋 | `setState`; draft = `localStorage`, not persistence |
| Date range (half-open `[in,out)`) | 🔨 S | widget real; "today" hard-coded → server today in property tz |
| Adults/children steppers | ✅ | `reservations.adults/children` |
| Unit list w/ rate + "Sleeps N" | 🔨 N1 + 📋 | units/caps/rates fabricated; **per-unit availability read doesn't exist** (T5) |
| "Available/1 left/Too small" | 🔨 N1 / ✅ (fit via `max_occupancy`) | "1 left" implies a per-unit count that doesn't exist |
| Guest existing/new toggle | ✅ | link `lead_guest_id` vs create |
| Guest search (name+email) | 🔨 N2 / L | name = `whereLike`; email = impossible (encrypted) |
| Guest results (name+**email+phone**+spend) | 📋 + 🚨 PII | perimeter breach (#28) — show name + non-PII stats only |
| New-guest form (name/email/phone/country/note/VIP/consent) | ✅ collect / ⚠️ render | encrypted casts real; `is_vip`/`marketing_consent` real; country/note have no column |
| Rate plans (Flexible/Non-ref/Long-stay, "Save 10/15%") | 🔨 S + 📋 | `rate_plans`+`rate_calendar` real; multipliers invented; `adjustment` never read |
| Per-night breakdown + "Weekend +20%" | 📋 float | money trap (T8); weekend uplift unbacked |
| Extras (Breakfast/Transfer/Late/Cot) | 🔨 R4 | `add_ons`+`postAddOn` real; prices are literals, not `AddOn` rows |
| "Returning-guest credit −€50" | 🔨 S–M | no discount engine |
| Arrival window / transfer / flight no. | 🔨 S | no columns; transfer is an add-on |
| House rules (times/quiet/smoking/pets/deposit/cancellation) | 📋 / 🔨 S | `property_policies` real but wizard hard-codes; deposit pre-auth exists but only shown as text |
| Booking source chips | ✅ / ⚠️ | 4 invented enum values |
| Payment mode + method + card form | ✅ (mode) / 🔨 R1 / 🚨 PCI | `recordPayment` real; raw PAN must tokenize |
| "Charge & confirm" | 📋 today → ✅ | the real write via `createBooking`+`recordPayment` |
| Done: ref `VYR-10442` + QR | 📋 | real ref is `R-XXXXXXXX` (`generateReference():687`) |

**SAFE-vs-WORKS:** WORKS (write) · NOT SAFE as drawn. **MOSTLY-READY for the write; screen needs N1(M)+N2(S)+R1(M)** + honest rate/tax/add-on wiring.

---

## 5. Operator Group Booking — `/app/reservations/group` (net-new)
5-step wizard (Block→Units→Organiser→Terms→Review) + done, with a Block-summary rail. `advance()` only `setState`; done shows fabricated `GRP-######`. **No write anywhere.**

**WRITES:** **none exist.** Needs `GroupBookingService::create()` (T6). `groups.contact` (json) is writable now; occasion/headcount need columns/json.
**DEAD-ENUM:** "Tentative" hold → `reservations.status='tentative'` **never written** (createBooking writes `'confirmed'`); `groups.pickup_deadline` never written.
**PARALLEL-STRUCTURE (❗ T2):** `groups` + `reservations.group_id` + `Group` **all exist, schema-only, zero business writers**. Build MUST extend them — a `group_bookings` table violates the reuse rule.
**ATOMICITY (❗ T6):** cannot loop `createBooking` inside an outer txn — it wraps its own transaction and fires events post-return → nested rollback emits **phantom `reservation.confirmed` events**. Inline the occupy loop; defer all events to one post-commit block.
**ISOLATION/PII:** org-scoped; organiser email/phone in `groups.contact` json is **plaintext** (vs encrypted guest PII) — decide organiser-as-`Guest`.
**PERMISSIONS:** reuse `reservations.create` (+ `reservations.manage` for edits).

| Feature | Tag | Backing / needs |
|---|---|---|
| Group name | ✅ | `groups.name` |
| Occasion chips (Wedding/Corporate/…) | 🔨 S | no column → add `groups.type` or json |
| Date range | ✅ | half-open, feeds each member |
| Headcount | 🔨 S | no `groups.headcount` column |
| Unit catalog (6 villas, Available/Booked) | 🔨 N1 + 📋 | fabricated; **no per-unit availability read** (T5); "Booked" is a literal |
| Multi-select units | ✅ | each → a member reservation line |
| Capacity bar ("Sleeps X of Y") | ✅ | `units.max_occupancy` sum vs headcount |
| Organiser (name/company/email/phone) | ✅ `groups.contact` / ⚠️ PII | json real; email/phone plaintext — decide organiser-as-Guest |
| Rooming list (free-text per unit) | 🔨 S–M | `reservation_guests` pivot keys `guest_id`, not free text |
| Group discount % (0/5/10/15) | 🔨 S–M | no discount engine |
| Deposit-to-confirm % | ✅ | `deposit_minor` / group deposit |
| Hold: Confirmed / **Tentative** | ✅ / 🪤 🔨 S | tentative is a dead enum (no writer) + `pickup_deadline` expiry |
| Payment: Master invoice / Individual | 🔨 M | folios per-reservation; roll-up undecided |
| Review + done (ref `GRP-######`) | 📋 | fabricated ref; email unimplemented |

**SAFE-vs-WORKS:** does not work today · not safe (T2/T6). **NEEDS-BACKEND** (not MOSTLY-READY).

---

## 6. Operator Portfolio — `/app/portfolio` (net-new)
Multi-property overview: KPI strip, per-property cards, side-by-side comparison table, add-property modal. 12 on-click; 2 real `sc-for`.

**WRITES:** one — the add-property modal `apCreate` flips a flag + pushes a fake row, **calls no service** (real add = `Property::create`, only in `DemoSeeder`; no `PropertyService`/route; + billing proration). Effectively read-only.
**DEAD-ENUM:** every trend/"+%" impossible (no snapshot/metric table exists anywhere); `availability.sold` is a live projection, can't reconstruct history.
**PARALLEL-STRUCTURE:** `organizations.mode` (single_property/chain/property_manager) read by zero code.
**ISOLATION:** every model `BelongsToOrganization` (safe by construction) but **two traps**: room-type-wide occupancy must key on physical `unit_id` (reuse `OwnerOccupancy`/`OperatorDashboard::occupancy`); group totals are a **cross-currency SUM** (#17 forbids) → per-currency grouping.
**PERMISSIONS:** reads map to `reports.view`/`properties.view` (both exist) — but **no per-permission read-gating precedent** exists (only the refund screen gates a read); flag, don't invent. `role="Group manager"` is not a real role.

| Feature | Tag | Backing / needs |
|---|---|---|
| Nav "Portfolio" active | 📋 / 🔨 L | **no route, no component, not in the shell** — this *is* the page, and it has no home |
| "Add property" button + modal | 🔨 L / 📋 | client-only; no `PropertyService`; + billing (Tier-2) |
| Subtitle "3 properties · 75 rooms" | 📋 | `propCount`/`roomCount` literals; real counts return **1 property** |
| KPI Revenue "214,600" | 📋 (derivable S–M) | `SUM(folio_items)` but needs period+currency decision |
| KPI delta "+9%" | 📋 impossible | no baseline table |
| KPI Occupancy 81% / Arrivals 27 "across 3" | 📋 (S derivable) | occupancy via Availability spine (1 property); "across 3" fabricated |
| KPI "Needs attention 3" | 📋 / 🔨 M | no such composite |
| Per-property cards (3 fictional) | 📋 / 🔨 L | DB has 1 property |
| card occupancy/ADR/revenue | 🔨 S/M | derivable if property scoping existed (E1); ADR/RevPAR deferred by the dashboard's own ruling |
| card "Expedia disconnected" / "Fix sync" | 📋 / ⬜ | provider=channex only; Channels unbuilt |
| Comparison table + group footer (RevPAR, total) | 📋 / 🔨 M | cross-currency SUM forbidden (#17); ADR/RevPAR deferred |
| drill-down links | 📋 inert | carry no property id (1 property, no per-property route) |

**SAFE-vs-WORKS:** ⛔ **DO NOT BUILD.** ~90% fabricated; forcing-function for the multi-property/property-switcher subsystem (E1). Honest interim = the single-property `OperatorDashboard`.

---

## 7. Operator Roles — `/app/roles` (net-new) — ⚠️ MOST SECURITY-SENSITIVE
6-card role selector, 20-toggle permission matrix, right-rail role summary + member list, New-role + Delete-role modals. Every write is a local-state toast — nothing persists.

**WRITES:** assign a catalog role to a user → **no service exists** (build `RoleAssignmentService`); edit/create/delete a role → fights the fixed catalog. All inert today.
**DEAD-ENUM/MISMATCH:** design shows 6 roles vs the catalog's **14**, 20 toggles vs **45** permissions (several toggles have no 1:1 permission).
**PARALLEL-STRUCTURE:** roles/permissions live in `config/authorization.php`, provisioned by `OrganizationRoleProvisioner` (which **re-syncs from config**, overwriting UI edits); `EnsureBackOfficeAccess` keys on the config list (a UI-created role can't reach `/app`). Editability = a DB-backed catalog + per-org migration (L).
**ISOLATION:** roles are team-scoped — any read/write must set the registrar team to the active org or it reads the wrong tenant/nothing. Super Admin (team=null) can't appear and is unassignable anyway (T7).
**PERMISSIONS:** `roles.manage` exists but only via `['*']` — this is its **first-ever consumer**. The escalation weapon (T4/#30): any persisted assign/edit lets a lesser role grant itself `payments.refund`/`payouts.manage`/`guests.erase`.

| Feature | Tag | Backing / needs |
|---|---|---|
| Role selector (6 cards, member counts) | 🔨 M / 📋 counts | design roles ≠ 14 catalog roles; counts fabricated; real = `Role::where(team=org)` |
| "New role" + modal | 🔨 L / recommend DROP | created role isn't in `back_office_roles` → can't reach `/app` |
| Permission matrix (5×20 toggles) | 🔨 L / recommend READ-ONLY | `role_has_permissions` write is overwritten by provisioner re-sync; 20≠45 |
| "Save changes" | 📋 inert | toast |
| Right-rail role summary | ✅ read | from the real role |
| "Delete this role" + reassignment modal | 🔨 L / DROP | not a supported op |
| Members panel + "Add" | 📋 inert / 🔨 | list = users holding the role (the real Wave-4 job = assign) |

**SAFE-vs-WORKS:** not safe; even the reads read the wrong role set. ⛔ **REDESIGN to read+assign**, gate `can:roles.manage`, `RoleAssignmentService` with server-side catalog re-validation. Drop toggles/create/delete.

---

## 8. Operator Settings — `/app/settings` (net-new)
Single-scroll org/property config: check-in/out, VAT, invoicing identity + numbering, cancellation/deposit presets, booking rules, owner-commission default, currency/timezone, 5×3 notification matrix, invite-teammate modal. 52 on-click; `onSave` is a toast.

**WRITES:** **no write service exists for any setting** (grep: zero writes to Property/PropertyPolicy/Tax/Setting in `app/`). Each field group needs a new tenant-scoped service → PRG.
**DEAD-ENUM:** subscription "paused" (dormant), user "suspended/disabled" (never written) — don't fabricate.
**PARALLEL-STRUCTURE:** `invoices`/`subscriptions` schema-only (extend); `Setting` model exists + unused (the home for unmodeled toggles); `taxes`/`fees` ARE wired to `FolioService`.
**PERMISSIONS:** `settings.manage`/`taxes.manage`/`fees.manage`/`invoices.issue`/`users.manage` exist; `settings.manage` is wildcard-only (first consumer).

| Feature | Tag | Backing / needs |
|---|---|---|
| Check-in/out times | 🔨 S | `properties.check_in_time`/`check_out_time` real, no writer |
| Currency / timezone | ✅ read / 🔨 S write | `organizations.default_currency` / `properties.timezone` |
| VAT accommodation/service | 🔨 M | `taxes` real + wired to `computeTax`; no Tax CRUD |
| Invoicing identity (legal name/VAT/address) | 🔨 S–M | `billing_email` exists; legal_name/vat_no not columns → branding json or new |
| Invoice numbering (prefix/next) | 🔨 M | `invoices` schema-only; no sequence generator |
| Cancellation/deposit presets | 🔨 S | `property_policies.cancellation`/`deposit` json real, read-only |
| Booking rules (min/max stay, window, arrival days) | 🔨 S/partial | min/max_stay exist; window/advance/per-day arrival = no column |
| Owner-commission default | 🔨 M | `management_agreements.commission_pct` is **per owner/unit**; no org-wide default |
| Notification routing (5×3 + recipient) | 🔨 L | no table/dispatcher |
| Invite teammate (admin/fd/hk) | 🔨 M | `OwnerInviteService` maps only manager/accountant; slugs don't match; overlaps Roles + Property Settings |

**SAFE-vs-WORKS:** partially SAFE. **SLICE IT** — ship backed-column writers first; stub VAT-CRUD/invoicing/notification/commission-default. De-conflict with Property Settings.

---

## 9. Operator Property Settings — `/app/properties/{p}/settings` (net-new)
5-tab per-property page: General, Policies, Team access, Tax & fees, Branding + type-to-confirm Delete-property modal. 35 on-click; `save()` is a toast. **Heavy overlap with page 8 (tax/policy/team) — two-sources-of-truth risk.**

**WRITES:** same as page 8 — none exist. Needs `properties.manage`/`taxes.manage`/`fees.manage`.
**DEAD-ENUM:** `properties.status` real (no toggle path).
**PARALLEL-STRUCTURE:** `taxes`/`fees`/`add_ons` wired (`FolioService:101,106`); `property_policies` read (`ReservationDetail:272`) — extend these.

| Feature | Tag | Backing / needs |
|---|---|---|
| General — property name | 🔨 S | `properties.name` real |
| Policies — cancellation/deposit/pets | 🔨 S | `property_policies.cancellation`/`deposit`/`pet` json/string real |
| Policy — **self check-in** | 🔨 S | **no column** — new field |
| Policy — **pause bookings** | 🔨 M | **no column**; real "stop selling" is availability/restriction, not a boolean — do not fake |
| Team access (4 members + "Manage") | 📋 inert | hard-coded; overlaps Roles + page-8 invite |
| Tax & fees — city tax / VAT incl-added | 🔨 M | `taxes` (city_tax/per_person_night/inclusive) real, read-only |
| Extra-fee editor (presets/basis/sample folio) | 🔨 M | `fees` real + wired to `applyFee`; no Fee CRUD; sample math client-side |
| Branding (accent/sender/"Powered by") | 🔨 S–M | `organizations.branding` json exists, unused; no discrete columns |
| Delete property (type-to-confirm) | 🔨 M / ⚠️ | softDeletes exist but cascades reservations/units/folios — destructive-op review; currently a toast |

**SAFE-vs-WORKS:** SAFE for real-column slices; self-checkin/pause/branding have no columns. **BUILDABLE (M); must be de-conflicted with Operator Settings first.**

---

## 10. Owner Settings — `/owner/settings` (net-new) — most fabricated
Single-scroll owner account page: profile+verification, region/locale, sales-channel connect, invoices, CSV/JSON exports, notification prefs + quiet hours, business/tax identity, **owner team management**, appearance. 39 on-click; **every write persists to `localStorage`.**

**WRITES:** only genuinely backable = owner's own email/phone + tax_id/address (`owners.*`, resolved by `owners.user_id`). Everything else net-new or fake.
**PARALLEL-STRUCTURE:** owner sub-team has no schema (T2/owner); invoices schema-only.
**ISOLATION (❗ T3):** `Owner` is `BelongsToOrganization`, **NOT `BelongsToOwner`** — no `OwnerScope` protects it → self-edit **MUST** guard `where user_id = auth()->id()` explicitly, or one owner edits another's profile. CSV export emits guest email/phone (PII/owner-isolation breach).
**PERMISSIONS:** owner holds `owner_portal.view` only — self-edit allowed by ownership, not a catalog permission.

| Feature | Tag | Backing / needs |
|---|---|---|
| Profile email/phone (edit+save) | 🔨 S | `owners.email`/`phone` encrypted; needs explicit `user_id` guard |
| Avatar upload | 🔨 M | FileReader→localStorage; no owner-avatar on `media` |
| Email/phone verification (6-digit) | ⬜ / 📋 | any 6 digits confirms; no backend |
| Region (language/currency/tz/date) | 🔨 S/partial | `users.locale` exists; rest no columns |
| Sales channels (Airbnb/Booking/Vrbo/Google) | ⬜ | Channex operator-level + 🔴; owner-level channel isn't a concept |
| Owner invoices (INV-2025-09…) | 📋 | fabricated; `invoices` schema-only |
| CSV/JSON exports | 📋 / ⚠️ PII | hard-coded rows; guest CSV emits email/phone |
| Notification prefs (6×3 + quiet hours) | 🔨 L | no table/dispatcher |
| Business/tax (name/VAT/reg/country/address) | 🔨 S–M | `owners.tax_id` encrypted + `address` json exist; rest partial |
| Team management (list/edit-role/remove/invite) | 🔨 L | no owner sub-team; roles map to nothing |
| Appearance (reduce-motion/theme) | 📋 | localStorage; no column |

**SAFE-vs-WORKS:** mostly blocked. Build **only** the owner-self-profile writer (explicit `user_id` guard); stub/omit the rest. ~80% fabrication.

---

## 11. Owner Team — `/owner/team` (net-new)
Owner sub-team page: invite form (Co-owner/Accountant/View-only), 2-member roster, pending invites, role-permission matrix, activity feed. **A duplicate of Owner Settings' team section; both `localStorage`-only.**

**WRITES:** none real. **No owner-sub-team model, no owner-scoped roles, no owner→user 1:many** (`owners.user_id` is 1:1). ⚠️ Do NOT wire "invite" to `OwnerInviteService` (T5) — it creates operator-org memberships/owners, mis-provisioning people into the back office.
**ISOLATION:** cannot be assessed — the entities don't exist; there is no `owner_id` on any team/user structure to scope by.

| Feature | Tag | Backing |
|---|---|---|
| Invite form (email + role) | 🔨 L | localStorage; roles map to nothing |
| Member roster + remove | 📋 | hard-coded `MEMBERS` |
| Pending invites + revoke | 📋 | localStorage |
| Role-permission matrix (5×3) | 📋 | hard-coded `MATRIX` |
| Activity feed (4 rows) | 📋 | hard-coded |

**SAFE-vs-WORKS:** ⛔ **DEFER** — net-new owner sub-tenancy subsystem (L). De-duplicate with Owner Settings first.

---

## 12. Vyrobel Onboarding — operator/org first-run (net-new, must live OUTSIDE `/app`)
4-step wizard (Property→Units→Rate→Channels) + done. 22 on-click, all `setState`; **nothing persists.**

**WRITES (the missing spine):** create Organization (**no service** — only `runAsSuperAdmin` seeder + tests) + create User & login founder (**no register controller**) + `OrganizationRoleProvisioner::provision()` (exists, **0 prod callers**) + assign founder `Property Owner` + create Property/Building/Floor (**no service**) + create RoomTypes/Units (**no service**) + `setRate` + `AvailabilityService::recompute` (leaf services real) + connect channels (⬜ account-gated).
**CHICKEN-AND-EGG (T7):** no user can hold `organizations.manage` at org-creation (Super Admin unassignable; Property Owner presupposes the org) → **can't be permission-gated**; `BelongsToOrganization` throws pre-org → must run `runAsSuperAdmin`; `/app` 403s a user with no org → **must live outside `/app`** (like `/login`, `/invite/{token}`); no such route exists.
**DEAD-ENUM:** no step/`onboarded_at` column (resume persists nothing); `organizations.status` writes only `'active'`.

| Feature | Tag | Backing / needs |
|---|---|---|
| Step 1 Property (name/place/kind) | 🔨 M (OP1/OP3) | `organizations`/`properties` columns real; **no create service**; "kind"→`type` vs `mode` decision |
| Step 2 Units (villas/apts steppers) | 🔨 M (OP4) | units need a room_type; **no unit/room-type creation service** |
| Step 3 Rate + currency | 🔨 M (OP5) | `setRate` real but needs a RatePlan (OP4) first; then `recompute`; Money minor units |
| Step 4 Channels (Booking/Airbnb/Expedia) | ⬜ / 📋 | provider=channex only; no `Channel::create`; live 🔴 — **do not ship** |
| Done ("property is ready", chips) | 📋 | presumes OP3–OP5 succeeded; links to real `route('app.dashboard')` if founder holds a role (OP2) |

**SAFE-vs-WORKS:** neither. ⛔ **NOT READY — tenant-provisioning EPIC**, not a screen. Scope as its own epic (self-serve signup vs sales-provisioned); never ship the channels step.

---

## 13. Owner Onboarding — owner post-invite welcome (net-new)
3-step wizard (Confirm details→Payout account→Preferences) + done. 8 on-click, all `setState`. The owner is **already activated** before this screen (via the certified `OwnerInviteService` invite→accept), so it is **not** an activation path — it's a settings/capture page.

**WRITES:** Step 1 (name/statement email) → real columns, needs a small owner-profile writer (S). Step 2 (payout account) → **no backend at all**. Step 3 (prefs) → **no backend**.
**PARALLEL-STRUCTURE:** must NOT become a second activation path — `OwnerInviteService::accept()` already sets password/activates/assigns units/creates the agreement/assigns role/auto-logins.
**ISOLATION:** runs inside a resolved tenant+owner scope (`/owner` = `[auth, SetOwnerScopeForPortal]`); authorizable today.

| Feature | Tag | Backing / needs |
|---|---|---|
| Step 1 confirm name / statement email | 🔨 S | `owners.name`/`email` (encrypted) + `users.name` real, already set; no owner-profile writer |
| Step 1 "3 units · 15% commission" | ✅ data / 📋 literals | real via `units.owner_id` + `management_agreements.commission_pct`; names/% hard-coded |
| Step 2 account holder + **IBAN** + `ibanCk` | 🔨 L + 🔴 | **no bank/IBAN column exists anywhere** (locked omission across 6 screens); `ibanCk` is a cosmetic ≥15-char check |
| Step 2 "two micro-deposits… verify in Payouts" | 📋 fabrication | no micro-deposit rail; Stripe Connect throws |
| Step 3 notification toggles (statement/payout/digest) | 🔨 M / L | no prefs table; "performance digest" implies an analytics job that doesn't exist |
| Done + next-steps card | ✅ targets | `route('owner.dashboard')`/`route('owner.payouts')` real (payouts has no verification/IBAN) |

**SAFE-vs-WORKS:** Step 1 SAFE + small; Steps 2–3 unbacked (Step 2 fabricates bank verification the product refused across 6 screens). **Ship Step 1 + the done card; OMIT Steps 2–3** (honesty rule) — pairs with the Owner Settings self-profile writer.

---

*Read-only inventory. No app code changed; `docs/WAVE4-INVENTORY.md` is the only artifact. Every ✅/🔨/📋 is grounded in a cited file:line, verified against the real migrations, models (`RateAndRestrictionService`, `AssignmentService`, `FolioService`, `Channel`/`ChannelMapping`, `Group`, `Owner`, `OwnerOccupancy`, `OrganizationRoleProvisioner`), `config/authorization.php`, `routes/web.php`, `DemoSeeder`, and the thirteen decoded design logic classes.*
