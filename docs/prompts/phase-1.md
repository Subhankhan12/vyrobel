# Vyrobel — Phase 1 Build · Claude Code Master Prompt

**How to use this**
1. Drop the companion schema spec into your repo at `docs/schema/phase-1.md` (that's the *Vyrobel Phase 1 Unified Schema* document — it holds the exact column-level definitions).
2. Paste everything **below the line** into Claude Code.
3. Work through it step by step. Claude Code stops at every ⛔ **GATE** and waits for your approval before continuing — so you run Phase 1 incrementally, not in one blind shot.

Locked decisions baked in: Laravel · **MySQL 8 (InnoDB)** · Livewire/Blade/Tailwind · Horizon+Redis · **spatie/laravel-permission (teams mode)** · **ULID** keys · **shared-DB tenancy via `organization_id`**.

---
═══════════════════ PASTE BELOW THIS LINE ═══════════════════

# ROLE & MISSION
You are building **Phase 1 (the foundation)** of **Vyrobel**, a multi-tenant hospitality SaaS (PMS for hotels + aparthotels + serviced apartments + villas + multi-unit rentals), in this Laravel repo. Build **only** what each step specifies, in order, and **STOP at every gate**. The authoritative column-level definition for every table is `docs/schema/phase-1.md` — read it before writing any migration. Where this prompt and that spec disagree, **ask me before proceeding**.

# LOCKED TECHNICAL DECISIONS (do not deviate)
- **Laravel** (use the version already in the repo), PHP 8.2+.
- **MySQL 8, InnoDB.** Do **not** use any Postgres-only features (no `EXCLUDE`/gist constraints, no range types).
- **Livewire + Blade + Tailwind** for the web layer.
- **Laravel Queues + Horizon on Redis** for all async work.
- **spatie/laravel-permission** with the **teams feature ENABLED**, where `team = organization`.
- **ULID** primary keys on all tenant-scoped tables.
- **Shared-database multi-tenancy**: every business table has an `organization_id` column, enforced by a global scope + a tenant-context guard.

# NON-NEGOTIABLE CONVENTIONS (apply to everything you build)
1. **Tenancy.** Create a `BelongsToOrganization` trait that: applies a global scope filtering by the current organization, auto-fills `organization_id` on create, and **throws** if a tenant-scoped query/insert runs with no organization context (except inside an explicit super-admin context). Every business model uses it.
2. **ULIDs.** All tenant tables use ULID keys (`HasUlids`).
3. **Money is never a float.** Store integer **minor units** (`*_minor` as `bigint`) + an ISO-4217 `currency` (char 3). Implement a single `Money` value object / cast and use it everywhere money appears.
4. **Time.** Store all datetimes in **UTC**. Each `property` has a `timezone`; "today's arrivals/departures," shift logic, and check-in-time logic compute in property-local time. Stay dates are `date` columns, treated as half-open `[check_in, check_out)`.
5. **PII vs GDPR.** `guests.email` and `guests.phone` use encrypted casts. GDPR erase **anonymizes** (an `anonymize()` method that strips name/email/phone/documents and sets `anonymized_at`) — it does **not** soft-delete-and-retain PII.
6. **Reusable polymorphics.** `media`, `documents`, `notes`, `tags`/`taggables` are polymorphic and reused by every module. Do not create per-module photo/note/tag tables.
7. **Two event trails on every meaningful state change.** Write an `audit_events` row (who did what: actor, before/after, ip) **and** a `domain_events` row (business event outbox, e.g. `reservation.confirmed`). Provide one reusable writer for each. Downstream consumers come later — just record the events now.
8. **One reservation, many channels.** There is exactly one `reservations` table. A direct booking, OTA import, walk-in, corporate, and group booking are all rows distinguished by `source` (+ `channel_id`/`ota_reservation_id` hooks for later). **Never** create parallel per-channel reservation tables.
9. **Single inventory write path.** **All** inventory-affecting writes go through `AssignmentService` (Step 5). No other code may insert or mutate `reservation_units` or `availability` directly — ever.

# RULES OF ENGAGEMENT (for you, Claude Code)
- **Audit first.** Do Step 0 and STOP before writing any code.
- **Stop at every ⛔ GATE.** Do not continue past a gate until I explicitly say to. Never collapse multiple steps together.
- **Extend, don't duplicate.** Shared traits, base classes, and services. Never build a parallel structure for a concept that already exists.
- **No faked data, ever.** Seeders may create *realistic demo* data, but it must be unmistakably labelled as demo (a `is_demo` flag and/or obvious demo names) and never presented as real. If something isn't actually built or a test isn't actually passing, **say so plainly** — do not stub-and-pretend to look finished.
- **Report after each step**, file-cited: list files created/changed (with paths), migrations added, and exactly how to verify. 
- **No surprise dependencies.** Don't install packages beyond those specified without asking.

---

## ⛔ STEP 0 — Audit & plan (GATE — do this, then STOP)
1. Report the repo state: Laravel version, PHP version, DB connection config, and which of these are already present in `composer.json` — spatie/laravel-permission, laravel/horizon, livewire/livewire — plus any existing migrations/models that overlap with Phase 1.
2. Propose (do **not** run yet) the install/config plan: spatie permission with **teams enabled** in its config, Horizon, Redis queue connection, ULID setup.
3. Echo back the full Phase 1 table list grouped by Steps 1–8 below, and confirm you will use `docs/schema/phase-1.md` for the exact columns.
4. Flag any conflict between this prompt, the schema spec, and the existing repo.
**STOP and wait for my approval.**

## ⛔ STEP 1 — Tenancy, Access & Platform plumbing (GATE)
Migrations + models + relations (columns per spec) for: `organizations`, `users`, `organization_user`, `property_user`, the spatie permission tables (teams-scoped), `plans`, `plan_features`, `subscriptions`, `subscription_items`, `usage_counters`, `api_keys`, `webhooks`, `webhook_deliveries`, `audit_events`, `settings`, `domain_events`.
Also build:
- The **`BelongsToOrganization`** trait (global scope + auto-fill + tenant-context guard per convention 1).
- The reusable **`AuditEvent`** and **`DomainEvent`** writers (convention 7). Wire nothing downstream yet.
- A seeder for the **14-role catalog** (Super Admin, Property Owner, Group Admin, Hotel Manager, Revenue Manager, Front Desk, Reservation Agent, Housekeeping, Maintenance, Accountant, Marketing Manager, Guest Support Agent, External Owner, Guest) with their permissions, team-scoped.
Report + **STOP**.

## ⛔ STEP 2 — Properties & Inventory (GATE)
Tables: `properties`, `property_policies`, `buildings`, `floors`, `room_types`, `units` (include the **nullable `owner_id`** forward hook — column only, no FK target yet), `amenities`, `amenitiables`, `unit_relationships`, `inventory_blocks`, `unit_status_logs`, and the polymorphic `media`, `documents`, `notes`, `tags`, `taggables`.
Models, relations, casts. Wire the polymorphics generically so later modules reuse them.
Report + **STOP**.

## ⛔ STEP 3 — Rates & Availability (GATE)
Tables: `rate_plans`, `rate_calendar`, `availability`, `restrictions` (unique constraints per the spec, e.g. unique `(room_type_id, date)`).
Build an **`AvailabilityService`** with: a `recompute(roomTypeId, dateRange)` method, and a read method exposing `available = total − sold − out_of_order − blocked` per date. It will be called by `AssignmentService` and by a domain-event listener in Step 5.
Report + **STOP**.

## ⛔ STEP 4 — Guests (GATE)
Tables: `guests` (encrypted `email`/`phone` casts; `anonymized_at`; an `anonymize()` method per convention 5), `guest_documents` (secure storage references; never inline raw files), `corporate_accounts`.
Report + **STOP**.

## ⛔ STEP 5 — Reservations, Stays & the guarded booking service (GATE) ← the heart of Phase 1
Tables: `reservations` (the `source` enum + nullable `channel_id`/`ota_reservation_id` hooks + statuses per spec), `reservation_units` (the inventory-occupying line item; `unit_id` nullable until assigned), `reservation_guests`, `reservation_status_history`, `groups`.
Then build **`AssignmentService`** — the single path for any inventory-affecting write. **MySQL has no exclusion constraint, so the no-double-booking guarantee lives entirely here.** Implement it as:
- A `confirmAssignment(...)`-style method wrapped in a **DB transaction**:
  1. `BEGIN`.
  2. **Lock** the `availability` rows for the `(room_type_id, each date in range)` with `SELECT … FOR UPDATE` — this serializes concurrent attempts on the same type/dates.
  3. **Level A (counts):** assert `available ≥ requested` for every date in range.
  4. **Level B (unit overlap):** if a specific `unit_id` is supplied, query for any **active** `reservation_unit` on that `unit_id` whose `[check_in, check_out)` overlaps the requested range (`unit_id = ? AND status = 'active' AND check_in < :out AND check_out > :in`), and any overlapping `inventory_block`; **reject** if either exists.
  5. Insert the `reservation_unit`, then update the locked `availability` rows (`sold++`), in the **same** transaction.
  6. `COMMIT` (releases locks). Emit the relevant `domain_events`.
- Provide methods covering every booking path through this service: create booking, **assign/move unit** (re-run the overlap check against the new unit), **split**, **extend** (re-check the new range), **cancel / no-show** (decrement availability).
- Add a domain-event **listener** that recomputes availability on reservation/block changes (defense in depth alongside the inline updates).
- **Hard rule:** no code anywhere else may insert/mutate `reservation_units` or `availability` directly.
Report + **STOP**.

## ⛔ STEP 6 — Folios, Payments & Tax (GATE)
Tables: `folios`, `folio_items`, `invoices`, `payments` (**token-only**, never raw card data; gateway enum incl. `stripe`, `manual`, `bank_transfer`, `ota_collect`), `payment_authorizations`, `taxes`, `fees`, `add_ons`.
Build a **`FolioService`** that posts charges and keeps folio `total/paid/balance` in sync; payments update balance + `payment_status`. Define a `PaymentGateway` interface with a working **manual/offline** implementation only — real Stripe wiring is Phase 2.
Report + **STOP**.

## ⛔ STEP 7 — Housekeeping (GATE)
Table: `housekeeping_tasks`. Auto-generate a **departure-clean** task when a reservation checks out (via a `domain_events` listener). Status transitions write to `unit_status_logs`.
Report + **STOP**.

## ⛔ STEP 8 — Factories, demo seeders & integrity tests (GATE) ← non-skippable
- **Factories** for all models; a **`DemoSeeder`** that builds ONE realistic demo organization with a **mixed** property (hotel room types **and** apartment unit types), units, rate plans + calendar, availability, a handful of guests, and reservations across multiple `source` values — all clearly flagged as demo.
- **REQUIRED tests (must actually pass — do not skip or fake):**
  1. **Concurrency double-book:** two overlapping bookings hit the **same** `unit_id`/dates concurrently (or back-to-back through the locking path); assert **exactly one** succeeds and the second is rejected. *This is the make-or-break test for the whole product.*
  2. **Type oversell:** attempt to confirm more than `total` for a `room_type`/date; assert rejected.
  3. **Availability recompute** correctness after cancel / move / extend.
  4. **Tenant isolation:** org A queries never return org B rows; the guard throws with no tenant context.
  5. **Money integrity:** amounts round-trip as integer minor units with no float drift.
- **Playwright:** a happy-path booking flow against a stub page (create booking → assign unit → folio reflects the charge), plus an attempt to assign an already-occupied unit asserting it's rejected in the UI.
Report results (which pass / fail, file-cited) + **STOP**.

## ⛔ STEP 9 — Stub views with data contracts (GATE — optional, for design integration later)
Minimal, **unstyled** Livewire/Blade stub pages that expose the data contracts (your Claude Design HTML gets integrated later, your usual way): reservation calendar / room rack, reservation detail, units list, housekeeping board. Each page documents the exact data shape it expects.
Report + **STOP**.

═══════════════════ PASTE ABOVE THIS LINE ═══════════════════

---

### What this produces when you've run all gates
A real, tested Phase 1 foundation: multi-tenant core with 14-role RBAC, full property/inventory model (including mixed hotel+apartment properties and the owner-accounting hook), rates + the two-level availability spine, guests with GDPR-safe PII handling, the complete reservation model with **the guarded MySQL booking service that provably can't double-book**, folios/payments/tax scaffolding, housekeeping, realistic demo data, and the integrity test suite that proves the critical guarantees hold. From there, Phase 2 (Channex channel manager + Stripe + booking engine + staff PWA) attaches onto this without touching the core.

### When Phase 1 is in and green
Tell me and I'll write the **Phase 2** master prompt the same way — Channex integration (the ARI/mapping layer + the bulletproof sync queue), the public booking engine, Stripe wiring, and the front-desk/housekeeping staff PWA.
