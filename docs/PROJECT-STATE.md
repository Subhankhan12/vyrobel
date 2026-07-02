# Vyrobel — Project State (resume here)

**Read this first on a fresh session, then `docs/DECISIONS.md` for the "why".** This is a pointer map, not a copy of the code — follow the links on demand.

## Status

- **Phase 1 core CERTIFIED complete at Step 8** — the guarantee stack is proven by the permanent suite (**true-parallel** concurrency, PHPStan level 5, Pint). Steps 1–8 = tenancy/RBAC, property/inventory, rates/availability, guests, reservations + the guarded booking service, folios/payments/tax, housekeeping, factories/demo-seeder/tests.
- **Step 9 (stub views) was BUILT** (unstyled, read-only contract stubs) + the request-scoped tenant middleware — these are throwaway scaffolding for the **design phase** to replace; they are not the design.
- **Current suite: 21 tests / 116 assertions**, all green on MySQL `vyrobel_test` (19 integrity obligations + 2 stub-view tests).
- **Nothing was committed during the build** until the Phase-1 consolidation commit. **Next: Phase 2.**

## What Phase 1 delivered (one line per domain)

- **Tenancy / RBAC** — `BelongsToOrganization` trait (global scope + auto-fill + guard) via `App\Support\Tenancy\TenantContext`; spatie teams (team = organization), 14-role catalog (`config/authorization.php`, `RoleCatalogSeeder`, `OrganizationRoleProvisioner`).
- **Property & inventory** — mixed hotel+apartment under one property; polymorphic `media`/`documents`/`notes`/`tags`/`amenities` reused via `app/Models/Concerns/*`.
- **Rates & availability** — two-level model; `App\Services\AvailabilityService` (recompute total/ooo/blocked; read `available`). Collaborator of AssignmentService, **never** a second write path.
- **Guests** — encrypted `email`/`phone`; `Guest::anonymize()` (GDPR: null PII + purge file bytes, preserve row/id/history).
- **Reservations** — one `reservations` table, `source` distinguishes channel; **`App\Services\AssignmentService`** is the single guarded inventory write path (book/assign/move/extend/split/cancel/no-show/block).
- **Folios/payments/tax** — `App\Services\FolioService` (append-only ledger, correct-by-reversal); `PaymentGateway` interface with **manual/offline impl only** (`app/Payments/`).
- **Housekeeping** — `App\Services\HousekeepingService` + checkout event → `GenerateDepartureCleanTask` listener; status → `unit_status_logs`.

## INVARIANTS THAT MUST NEVER BREAK (the guarantee stack)

1. **Single inventory write path** — ONLY `AssignmentService` inserts/mutates `reservation_units` or `availability.sold`. Nothing else, ever.
2. **Two-level no-double-booking** — Level A (counts) + Level B (physical-unit non-overlap), enforced by `SELECT … FOR UPDATE` on availability rows inside one transaction. **Deterministic lock ordering** by `room_type_id` + `check_in` (deadlock-free). MySQL has no exclusion constraint — the guarantee lives entirely in this service; the concurrency test is mandatory.
3. **One reservations table** — `source` distinguishes direct/OTA/walk-in/corporate/group. Never parallel per-channel tables.
4. **Money = integer minor units + ISO currency**, via the `Money` value object / `MoneyCast`. Never a float. `folio.total_minor = SUM(folio_items.amount_minor)` (tax posted as its own line, not double-counted).
5. **PII** — `guests.email`/`phone` encrypted; GDPR erase = `anonymize()` (null PII, set `anonymized_at`, purge stored file bytes). NEVER soft-delete-and-retain PII.
6. **Append-only financial ledger** — posted `payments`/`invoices`/`folio_items` are correct-by-reversal: never deleted/edited; refunds/voids/credits are NEW offsetting entries. Deposit hold ≠ paid until captured.
7. **Event direction** — `domain_events` emitted **post-commit by the write side ONLY**. `AvailabilityService::recompute` and all listeners are **pure consumers** — never emit events, never touch `sold`.
8. **Enforced morph map** — `Relation::enforceMorphMap` is on (AppServiceProvider). Every polymorphic-capable model MUST be registered or Laravel throws. "Add to morph map" = per-step checklist item.
9. **Tenant scoping** — every business model uses `BelongsToOrganization`; the guard THROWS with no context; explicit super-admin bypass only. Deliberate opt-outs: `Membership`, `PropertyAssignment` (link tables), and the append-only logs / polymorphic-owner tables.
10. **ULID keys everywhere** except spatie's own tables (documented integer-PK exception; their `organization_id`/morph keys are `char(26)` to match).

## Deferred-items register (do NOT build ad hoc; see DECISIONS.md)

- `units.owner_id` FK → **Phase 3** owners module (nullable column, no FK target now).
- Request-scoped tenant middleware → **DONE in Step 9** (`SetTenantForWebRequest`).
- Horizon + Redis (queue) → **Phase 2** (Phase 1 uses the `database` driver).
- Blind-index for encrypted guest email/phone → **Phase 2** OTA import (guest matching).
- Gap-free invoice numbering (replace `max+1`) → when invoicing goes live (P2/P3).
- Dated out-of-order ranges (P1 `out_of_order` is date-independent from `condition_status`).
- `inventory_blocks.quantity` (nullable, null=1) to "block N units" of a type.
- Tenant-custom amenities via nullable `amenities.organization_id` (P1 = global catalog).
- Same-day-turnover housekeeping edge (departure tasks dedupe on `unit+date+type`).

## Regression net — `php artisan test` (MySQL `vyrobel_test`)

The 10 integrity obligations (each a named test in `tests/Feature/`), **green here = the whole guarantee stack holds**:
1. **Concurrency double-book** (true OS-level parallelism, ≥2 units so Level B decides) — `ConcurrentBookingTest`.
2. **Deadlock avoidance** (two multi-room bookings, opposite lock order) — `DeadlockAvoidanceTest`.
3. **Type oversell** (Level A) — `AssignmentServiceTest`.
4. **Availability overlap** subtracted once, never negative — `AvailabilityOverlapTest`.
5. **Move / extend / cancel / no-show** adjust `sold` correctly — `AssignmentServiceTest`.
6. **Tenant isolation** + guard + super-admin bypass — `TenantIsolationTest`.
7. **Money integrity** (integer round-trip; total = SUM, tax own line) — `MoneyIntegrityTest`.
8. **Append-only ledger** (refund = new row; hold ≠ paid till capture) — `FolioLedgerTest`.
9. **GDPR erase** (PII null, row/id/history preserved, file bytes purged) — `GdprEraseTest`.
10. **Event→listener discipline** (checkout→1 task; HK events don't move `sold`) — `HousekeepingEventTest`.
Plus `StubViewsTest` (Step 9): pages render via services + web-layer isolation. Concurrency/deadlock spawn **real PHP processes** on separate MySQL connections.

## Pointer index (read on demand)

- **Vision / phases / buy-vs-build** → `docs/master-plan.md`
- **Data contract (column-level source of truth)** → `docs/schema/phase-1.md`
- **Build steps / gated prompt** → `docs/prompts/phase-1.md`
- **The "why" behind every decision** → `docs/DECISIONS.md`
- **Code style / testing patterns** → `docs/CODING-STANDARDS.md`
- **Conventions + commands** → `CLAUDE.md`
- **Code** → `app/Services/` (AssignmentService, AvailabilityService, FolioService, HousekeepingService) · `app/Models/` (50) · `database/migrations/` (49) · `tests/` · `app/Support/Tenancy/`, `app/Support/Events/`, `app/Payments/`

## Next up — Phase 2 (see `docs/master-plan.md`)

Channex channel manager (ARI/mapping + a bulletproof sync queue), the public booking engine, **real Stripe wiring**, and the front-desk/housekeeping staff PWA — attaching onto the certified core without touching it. Built **gated + test-first**, same discipline (stop at each ⛔ gate; integrity tests are real gates; no faked data).
