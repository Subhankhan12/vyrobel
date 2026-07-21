# Vyrobel — Decision Log

A running record of locked decisions — what was decided and why — so the project stays honest across gated phases. Append new decisions with a date as we lock them.

## Locked as of 2 July 2026

- **Product name:** Vyrobel — an independent hotel management SaaS platform.
- **Wedge / ICP:** the hybrid operator (hotel + apartment + villa + multi-unit) with real owner accounting; SMB beachhead in under-served geographies. Not competing head-on with Mews/Cloudbeds on breadth.
- **Architecture:** all-in-one, single unified data model (the "semantic layer" that makes AI agents work natively).
- **Database:** MySQL 8 / InnoDB — genuine MySQL (not MariaDB); no Postgres-specific features.
- **Multi-tenancy:** shared database, `organization_id` column + global scope + tenant guard.
- **Primary keys:** ULID.
- **RBAC:** spatie/laravel-permission, teams mode (team = organization).
- **Double-booking prevention:** two-level (room-type counts + physical-unit non-overlap), enforced in a single guarded `AssignmentService` (app-level locking, since MySQL has no exclusion constraint).
- **Buy-vs-build:** RENT the channel manager (Channex), payments (Stripe + Channex PCI proxy), messaging transport (Meta WhatsApp API / Twilio / SendGrid), smart-lock APIs. BUILD the data model, AI agent layer, hybrid experience, website builder, PWAs, automation builder.
- **Build order:** Phase 1 foundation → Phase 2 distribution/payments → Phase 3 differentiators (website, inbox, owner accounting, AI) → Phase 4 intelligence → Phase 5 platform/moat.

## New decisions

### 2026-07-02 — Phase 1 Step 0 resolutions

1. **Queue driver = `database` for Phase 1** (Horizon+Redis deferred to Phase 2). — **Why:** CLAUDE.md/DECISIONS.md govern over the prompt's general stack line; "Horizon on Redis" describes the end-state, not a Phase-1 requirement, and Phase 1's async needs are trivial. **Corollary (critical):** the authoritative availability mutation (`sold++`/`sold--`) happens **inline and synchronously inside the `AssignmentService` transaction**; the `domain_events` recompute listener is **defense-in-depth only**. Integrity tests must run that listener **synchronously** (not via a queued job) so the double-booking test never depends on a running `queue:work`.
2. **`users.id` converted to ULID now** (skeleton-only data, right moment). — **Why:** Rewrite the skeleton migration to ULID + spec columns (`two_factor_secret`, `two_factor_enabled`, `locale`, `status`, `last_login_at`); switch `sessions.user_id` to `foreignUlid`; `password_reset_tokens` is email-keyed so no PK change. This makes **every** user-referencing FK uniform ULID (`created_by`, `assigned_to`, `posted_by`, `changed_by`, `verified_by`, `audit_events.actor_id`) — no mixed-type FKs anywhere.
3. **ULID on all first-party tenant business tables; third-party package tables keep their native PKs.** — **Why:** spatie `roles`/`permissions` stay integer-keyed; converting their internal keys buys nothing and breaks upgrade paths. This is the governing principle for PK type across the schema.
4. **Soft deletes follow the schema.** SoftDeletes on operational tables; `guests` uses `anonymize()` (never soft-delete-and-retain PII); `audit_events`/`domain_events` are immutable (no soft delete). — **Addition:** posted financial records (`payments`, `invoices`, and **posted** `folio_items`) are **correct-by-reversal** — services never delete them, only void/reverse via new entries, even if a `SoftDeletes` trait sits there as a safety net. Financial integrity comes from an append-only ledger, not deletions.
5. **Spec-gap resolutions:** use the `payments.gateway` superset enum (`stripe,paypal,square,manual,bank_transfer,ota_collect`); `subscription_items` = `subscription_id`, `plan_feature_id`/feature key, `quantity`, nullable `stripe_item_id` (null until Phase 2 Stripe); separate `property_policies` table per the prompt; align `.env.example` to MySQL.

**spatie teams + ULID catch:** with `team_foreign_key => 'organization_id'` and `organizations.id` as ULID, the published spatie migration defaults the `organization_id` column on `roles`, `model_has_roles`, and `model_has_permissions` to `unsignedBigInteger`. These must be **`char(26)`/ULID** to match `organizations.id` (the `model_id` morph is handled via `ulidMorphs`) — otherwise a runtime type-mismatch occurs.

**Step 1 close-out addenda:**

- **Static-analysis bar:** PHPStan (Larastan) **level 5** is the standing minimum (`phpstan.neon`). Ratchet up 1–2 levels as the codebase grows.
- **Tenancy opt-outs (intentional, do not "fix"):** `Membership` (`organization_user`) and `PropertyAssignment` (`property_user`) deliberately do **not** use the tenant global scope — a user must read their memberships across **all** orgs they belong to; these link tables are queried explicitly by `user_id`/`organization_id`.
- **Deferred FKs / forward hooks:**
  - Closed in **Step 2**: `property_user.property_id` foreign key + `PropertyAssignment::property()` relation (properties now exist).
  - Stays a nullable column with **NO FK target** until the owners module (**Phase 3**): `units.owner_id` — forward hook, not a stub.
- **Deferred to Phase 2 / Step 9:** request-scoped middleware that syncs `TenantContext` ↔ spatie team id (`setPermissionsTeamId`) for real HTTP requests. In Phase 1, context is set programmatically (seeders/services/tests).
- **Step 8 watch-item:** the per-org role provisioner must run inside the target org's team context (correct `setPermissionsTeamId`) so the 13 tenant roles attach to the right `organization_id`.

**Step 2 close-out addenda:**

- **`amenities` is a global/standard catalog for Phase 1.** Non-breaking future extension for tenant-custom amenities: add a nullable `organization_id` (null = global, set = tenant-custom); the catalog query becomes `where organization_id is null or organization_id = <current>`. The `amenitiables` join is unaffected.
- **Enforced morph map (standing rule):** `Relation::enforceMorphMap` is on (AppServiceProvider). Every polymorphic-capable model added later (guests→documents, reservations→notes, folios, event subjects, spatie `model_has_roles.model_type`, …) **MUST** be registered in the map or Laravel throws. Treat "add to morph map" as a per-step checklist item.
- **Deferred-FK register:** `room_types.default_rate_plan_id` → close FK in **Step 3** (once `rate_plans` exists). `units.owner_id` stays open until **Phase 3** (owners). `AvailabilityService.sold` term → wired in **Step 5** (reservation_units + AssignmentService); AvailabilityService is a *collaborator* of AssignmentService, not a second write path.

**Step 3 close-out addenda:**

- **Availability event direction (standing rule):** `domain_events` are emitted **only** by the write side (AssignmentService, inventory-block creation, rate/price actions). `AvailabilityService::recompute` is a **pure consumer and MUST NEVER emit events** — prevents feedback loops when Step 5 wires the recompute listener onto `domain_events`.
- **Known P1 limitations + non-breaking extensions (do not build now):** (a) `out_of_order` is date-independent in P1 (from current `condition_status`); dated OOO ranges are a later enhancement. (b) room-type-level `inventory_blocks` count as **1** unit; add nullable `inventory_blocks.quantity` later (null = 1) for "block N units."
- **Step 8 test obligation:** assert the overlap case — a unit both `out_of_order` **and** under a block is subtracted **once**, and `available()` never goes negative.
- **Step 5 design constraint (carry-forward):** AssignmentService must hold the `SELECT … FOR UPDATE` lock covering **both** its own `sold` update **and** any `recompute` run inside the booking transaction, so a concurrent recompute cannot stomp the availability row mid-booking. `sold` is written only by AssignmentService; `total`/`out_of_order`/`blocked` only by recompute.

**Step 4 close-out addenda:**

- **`guest_documents.document_id → documents`** (not a raw `media_id`): reconciles schema Module 4 (`media_id`) with Module 8 ("guest IDs live in `documents`") + the reuse directive. The `Document` carries `signed_url_required`/`media_id`; `guest_documents` wraps it with `type` + `verification_status`.
- **Step 8 test obligation:** `anonymize()` must purge the actual stored file **bytes** (the `Media` storage object on disk), not only the DB rows — a real GDPR erase removes the stored file. (Phase 1 stores no real bytes yet; wire the storage delete when uploads land.)
- **Blind-index for encrypted guest email/phone remains deferred;** likely resurfaces in Phase 2 OTA import (matching incoming bookings to existing guests).

**Step 5 close-out addenda:**

- **`reservation_units.currency` added** alongside `price_minor` (schema lists `price_minor` only): money is always minor units + ISO currency (convention #3) — the price snapshot carries its own currency rather than silently inheriting the reservation header.
- **Deadlock avoidance:** `AssignmentService` acquires availability locks in **deterministic order** (sort by `room_type_id` + `check_in`) before occupying multi-line bookings — prevents a circular-wait between two multi-room bookings under `SELECT … FOR UPDATE`.
- **Booking tests run on genuine MySQL** (`vyrobel_test`, per `phpunit.xml`); the concurrency test uses **true OS-level parallelism** (two PHP processes, separate connections, shared-timestamp barrier), NOT an injected in-process barrier.
- **Step 8 test-obligation register (accumulated — lock these into the Step 8 suite):**
  1. concurrency double-book under true parallelism (proven in Step 5 — re-assert in the Step 8 suite);
  2. deadlock avoidance — two multi-room bookings acquiring overlapping locks in opposite order serialize cleanly, no deadlock;
  3. availability overlap — a unit both `out_of_order` AND blocked is subtracted once, `available()` never negative;
  4. `anonymize()` purges the actual stored file bytes, not just DB rows.

**Step 6 close-out addenda:**

- **`taxes`/`fees` carry both `rate` (percentage modes) and `amount_minor`+`currency` (fixed modes)** — schema lists only `rate`; fixed-amount taxes/fees are money and need Money columns (convention #3). The service switches on `mode`.
- **Folio ledger sum rule:** `folio.total_minor = SUM(folio_items.amount_minor)`. Tax/fee/add-on are each posted as their own `folio_items` lines; the per-line `tax_minor` is breakdown metadata only and is **NOT** summed (avoids double-counting tax).
- **Invoices numbered per property:** `unique(property_id, number)`. When invoicing goes live (P2/P3), replace `max+1` with a **gap-free sequence/counter** — tax authorities require gap-free numbering and `max+1` races under concurrency. Deferred, not built now.

**Step 7 close-out addenda:**

- **Departure-task idempotency keys on `unit + date + type`** (`housekeeping_tasks` has no `reservation_id`): replay-safe for at-least-once queued delivery later. Known P1 edge — two different reservations turning over the **same unit on the same day** dedupe to one departure task; a deliberate P1 choice, revisit if same-day-turnover tracking is needed.
- **Demo data is flagged by obvious demo naming, NOT an `is_demo` column** (Step 8 forbids new schema): the `DemoSeeder` org/property/guests/reservations use unmistakable "Demo" names + a fixed `vyrobel-demo` slug.
- **`Guest::anonymize()` now deletes the stored file bytes** (`Storage::disk()->delete()`) of ID-document media, not just the DB rows — completes the GDPR obligation for a real erase.

**Step 8 close-out — Phase 1 core certified:**

- **Phase 1 guarantee stack certified at Step 8** (permanent regression net = the full named suite, 19 tests / 103 assertions on genuine MySQL): no-double-booking proven under **true OS-level parallelism** (5×5 stress runs, no flakiness); deadlock-free multi-room locking (deterministic lock order); tenant isolation + guard + super-admin bypass; float-free integer-minor money; append-only correct-by-reversal ledger (refund = new row, hold ≠ paid until capture); GDPR erase incl. **file-byte** purge; one-directional event→listener discipline (checkout→task, no recompute loop).
- **Enforced morph map earned its keep:** the suite surfaced an unregistered `InventoryBlock` used as an event subject (first exercised by the availability-overlap test); registered `inventory_block`. Confirms the enforced map is doing its job rather than silently storing FQCNs.

**Step 9 close-out — stub views + tenant middleware (BUILT, not skipped):**

- **Request-scoped tenant middleware wired** (`SetTenantForWebRequest`, appended to the `web` group in `bootstrap/app.php`): resolves the active org from the authenticated user (session override if a member, else first active membership) and sets **both** `TenantContext` and spatie's team id. **Closes the Step-1 deferred item.** CLI/queue/tests keep programmatic context.
- **Livewire 4.3 installed** (deferred since Step 1). Four UNSTYLED, read-only contract-stub pages (`app/Livewire/`: ReservationRack, ReservationDetail, UnitsList, HousekeepingBoard) that expose/document the data shapes for the later design pass, pulling real data through the services. **Not the design** — no CSS/design system; the design pass replaces these views.
- **Suite grew to 21 tests / 116 assertions** (added `StubViewsTest`: pages render real data through the services; web-layer tenant isolation holds — another org's reservation 404s).
- **Livewire-4 gotchas recorded:** (a) default `component_layout` is the namespaced `layouts::app` (unregistered → 500) — pinned each component with `#[Layout('components.layouts.app')]`; (b) a typed `public Model` prop makes Livewire resolve route-model binding during `SubstituteBindings`, **before** the tenant middleware — so hold the id as a string and resolve via tenant-scoped `findOrFail` in `render()`.
- **No login UI** — stub routes are `auth`-protected and verified via `actingAs`; a login screen belongs to the design/auth pass (Phase 2).

### 2026-07-02 — Phase 2 Step 0 gate resolutions

- Redis client: predis/predis (phpredis not loaded; pure-PHP, sufficient for current volume). REDIS_CLIENT=predis.
- Redis server: user installs/starts (brew install redis); Step 1 must PREFLIGHT-check reachability and STOP clearly if absent — never self-install.
- STANDING RULE (guarantee integrity): the availability recompute + sold mutation stay SYNCHRONOUS/inline (listeners remain non-ShouldQueue). ONLY the outbound ARI push to Channex runs async on the queue. Never move inventory recompute onto the queue — it would create a stale-availability oversell window.
- Booking hold: advisory booking_quotes hold with expiry, NOT counted in sold and NOT a hard-hold through AssignmentService. Inventory is only truly occupied at confirmed payment via AssignmentService, which re-checks availability. Two soft-holds on the last unit is acceptable; only one converts.
- Abandoned bookings: a status on booking_quotes (expired-unconverted), not a separate table.
- Deferred to Phase 3: guests.stripe_customer_id / saved cards — depends on the deferred guest-matching/blind-index story. Phase 2 Stripe uses payment intents + deposit holds against reservation/folio only.

### 2026-07-02 — Phase 2 Step 1 close-out (sync reliability backbone, Redis BLOCKED → RESOLVED, see next entry)

- **Redis preflight FAILED — `QUEUE_CONNECTION` stays `database` for now.** `127.0.0.1:6379` refused the connection (no server running) and `redis-server` is not on PATH. Per the Step 0 standing rule, this was not self-installed. `laravel/horizon` + `predis/predis` ARE installed (composer.json/lock updated), `config/horizon.php` + `HorizonServiceProvider` ARE scaffolded, and `.env`/`.env.example` `REDIS_CLIENT` is now `predis` — but Horizon has never actually been run, and `QUEUE_CONNECTION=redis` has NOT been flipped. **Action needed from the user:** `brew install redis && brew services start redis` (or equivalent), then flip `QUEUE_CONNECTION=redis` in `.env` and run `php artisan horizon` to actually exercise the queue on Redis. Until then, the `database` queue driver (already proven by the Step 1 suite) is what's actually running.
- **Horizon dashboard gate:** `viewHorizon` is restricted to the platform `Super Admin` role (`config('authorization.super_admin_role')`), not a hardcoded email list. Because that role is global (`team_id = null`, per `RoleCatalogSeeder`) while `SetTenantForWebRequest` sets an org-scoped permissions team id on every web request, the gate explicitly clears the team scope for the check (same pattern as `OrganizationRoleProvisioner`) — otherwise a real Super Admin would fail `hasRole()` while browsing as themselves. Horizon still auto-bypasses this gate in the `local` environment (framework default).
- **`sync_jobs`/`sync_logs` built as genuine tenant business data** (`BelongsToOrganization`, not the "stamped, not scoped" `domain_events`/`audit_events` pattern) — they back a future per-tenant mapping-health/sync-error dashboard. This means `RetryableSyncJob` (the abstract base every Step 3/4 sync job extends) has to carry `organizationId` explicitly and run its whole body inside `TenantContext::forOrganization()`, since a queue-worker process has no ambient tenant context — the guard would otherwise throw on the very first query.
- **`sync_jobs.type` is a plain `string`, not a DB enum** — deliberate: Steps 2-4 will introduce `ari_push`/`booking_import`/`booking_modify`/`booking_cancel`/`mapping_sync` values that don't exist yet, and a MySQL enum would need an `ALTER TABLE` each time. `direction` and `status` ARE DB enums since that set is fully closed now.
- **Idempotency mechanism:** `SyncJobDispatcher::dispatch()` does `firstOrCreate` on a unique `idempotency_key`, only enqueuing the job when the row is newly created, and dispatches `afterCommit()` so a worker can never pick up a job before the enqueuing transaction (e.g. a future `AssignmentService` write) actually commits. Hardened against the `firstOrCreate` TOCTOU race (two concurrent callers both missing the `SELECT`) by catching `UniqueConstraintViolationException` and re-fetching the winner's row — mirrors the rigor of the Phase 1 `AssignmentService` locking discipline, at the enqueue layer rather than the row-lock layer.
- **Retry/backoff/dead-letter:** `RetryableSyncJob` (`tries=5`, `backoff()=[10,30,60,120,300]`) atomically claims a `sync_jobs` row (`UPDATE ... WHERE status IN (pending,failed)`) before running `process()`, so two deliveries of the same job can never both execute it — this is the idempotent-execution guarantee, independent of the idempotency-key guarantee at enqueue time. `sync_jobs.available_at` is populated on each retryable failure (dashboard-facing "next retry at") and cleared on dead-letter. `failed()` moves the row to `dead_letter` and writes a final summary `sync_logs` line.
- **Proven by `tests/Feature/SyncReliabilityTest.php`** (5 tests, real `database` queue connection, no faked timing): idempotent enqueue (`Queue::fake()`), enqueue→process→succeed, retry-with-backoff-then-succeed (via `Carbon::setTestNow` time-travel between `queue:work --once` calls — no real sleeping), dead-letter after exhausting `tries` (5 failures + 1 dead-letter summary = 6 `sync_logs` rows) with no reprocessing after, and no-reprocessing-after-success. **No Channex call exists yet** — this step proves the machinery in isolation, per the master prompt. Full suite: **26 tests / 152 assertions**, PHPStan clean, Pint clean.

### 2026-07-02 — Redis switched on (Step 1 blocker resolved)

- User installed + started Redis. Switched `QUEUE_CONNECTION=redis` (`.env`; `database` documented as fallback), `REDIS_CLIENT=predis` confirmed, Redis reachable (`PONG` via socket + `Redis::ping()` through predis).
- Re-ran the full suite on the Redis-configured app — **26/26 green** (suite itself runs with `QUEUE_CONNECTION=sync` per `phpunit.xml`, by design, so tests never depend on external infra; this confirms the app-level switch caused no regression).
- **Horizon processes jobs end to end:** added `sync` to the Horizon supervisor's watched queues (`config/horizon.php` — SyncJobDispatcher enqueues to `sync`, which the default supervisor did not watch). Started `php artisan horizon`, dispatched a `FlakySyncJob` (success case) through `SyncJobDispatcher` to Redis; Horizon picked it up and ran it (`RUNNING → DONE 14.98ms` in its log), `sync_jobs` went `pending → succeeded` (1 attempt), one info `sync_logs` row. Terminated Horizon cleanly. **Redis/Horizon are the live queue path from here.**

### 2026-07-02 — Phase 2 Step 2 close-out (channel mapping model + mapping guard)

- **`channels` = the OTA credential vault, per (organization, property, provider).** `credentials` uses the `encrypted` cast (convention #5) — verified ciphertext at rest, plaintext never stored; `settings` must never carry secrets. `provider` is an `enum(['channex'])` for now (other providers are a Phase 5 concern; enum signals "channex only" and an `ALTER` is acceptable when that changes). `status` enum: disconnected/connected/error/disabled.
- **`channel_mappings` maps room_type OR rate_plan to an external id, keyed by `kind`.** Two coexisting uniques — `unique(channel_id, room_type_id)` and `unique(channel_id, rate_plan_id)` — rely on MySQL allowing multiple NULLs in a unique index: a room_type mapping has `rate_plan_id = NULL` (many share `(channel_id, NULL)` on the rate_plan unique) and vice-versa, so each unique only ever constrains its own kind. Health is `status` (pending/active/error/disabled) + `last_verified_at`/`last_error`.
- **Mapping guard (`App\Services\ChannelMappingGuard`) — "never a silent no-op."** `resolveRoomTypeMapping()` / `resolveRatePlanMapping()` return the ACTIVE mapping or throw `UnmappedChannelEntityException` (carries channel/kind/entity + a reason, with an actionable "create/activate a channel_mapping" message) AND write a `Log::warning('channel.mapping_guard.blocked', …)` first — so even a caller that swallows the exception leaves a loud trace. **Only `status = active` counts as mapped** — pending/error/disabled is treated as unmapped, so a half-configured channel cannot quietly leak inventory or bookings. Steps 3-4 (ARI push / booking import) call this guard before doing anything; Step 4 callers additionally record to `sync_logs` when a live sync context exists. Non-throwing `hasActive*Mapping()` variants exist for callers that want to branch.
- **Closed the Phase 1 hook:** `reservations.channel_id` is now a real FK → `channels` (`nullOnDelete`; direct/walk-in carry NULL), plus `Reservation::channel()` relation. Same add-FK-later pattern as Phase 1's `add_reservation_fk_to_guest_documents`.
- **`ChannelFactory`/`ChannelMappingFactory` are tenant-context-aware:** `organization_id` defaults to `TenantContext::id()` when a context is active, else a fresh `Organization::factory()`. Without this, factory-created channels/mappings landed in a *different* org than the active context and the tenant-scoped guard couldn't see them (caught during Step 2 testing). Registered `channel` + `channel_mapping` in the enforced morph map.
- **Verified:** `migrate:fresh --seed` green (all 15 migrations + RoleCatalog + Demo seeders), full suite **35 tests / 179 assertions** (Phase 1 + Step 1 + 9 new Step 2 tests in `ChannelMappingTest.php`), PHPStan L5 clean, Pint clean. Plus a live-DB runtime smoke check proving the guard blocks unmapped + pending mappings with a clear error, resolves active ones, credentials are encrypted at rest, and org B sees zero of org A's channels/mappings. **No `AssignmentService`/inventory code was touched.**

### 2026-07-02 — Phase 2 Step 3 close-out (Channex client + outbound ARI push)

- **`ChannelManagerClient` interface** (`app/Services/Channex/`) + **`FakeChannexClient`** (in-memory, inspectable — `pushes()`/`pushCount()`/`callCount()`/`lastPush()`, plus a `failUntilCall` knob to simulate N failures then success) + **`ChannexClient`** (real REST skeleton over Laravel `Http`, `POST /properties/{id}/ari`). Bound in `AppServiceProvider::register()` via `services.channex.fake` (default **true**): `CHANNEX_FAKE=true` pinned in `.env` and `phpunit.xml`, so the fake is what runs everywhere until real staging credentials are deliberately configured. **The real `ChannexClient`'s request/response shape is UNVERIFIED against live Channex** — explicitly documented on the class itself as the live-integration checkpoint, not proven by this step.
- **ARI push = current full state, never a delta.** `AriSnapshotBuilder` is a pure reader: `available` per night from `AvailabilityService::availabilityRange()` (already-recomputed, post-recompute state), plus price/restrictions for every rate_plan that has an ACTIVE `channel_mapping` on that channel (rate-plan-scoped restriction wins over room-type-scoped for the same date; unmapped rate plans are silently excluded from `rate_plans` — only a missing ROOM_TYPE mapping blocks the whole push).
- **`RateAndRestrictionService` (new, minimal) closes a real Phase 1 gap:** `rate_calendar`/`restrictions` had schema but zero write path — nothing emitted a domain event on a price/restriction edit, so Step 3's "rate/restriction changes trigger a push" requirement was unbuildable without one. Added `setRate()`/`setRestriction()`: plain per-night upsert in a transaction, then ONE post-commit `rate_calendar.updated`/`restriction.updated` domain event covering the whole edited range (same `{room_type_id, start_date, end_date}` payload shape as `inventory_block.created`). Does not touch inventory/`sold`/`AssignmentService`. **Registered `rate_plan` in the enforced morph map** (first polymorphic use, as this service's domain-event subject).
- **`EnqueueAriPushOnInventoryChange` — pure post-commit consumer, same discipline as `RecomputeAvailabilityOnInventoryChange`.** Registered LAST on `DomainEventRecorded` so it reads availability AFTER any recompute for the same event has already applied. Reacts to: `reservation.confirmed`/`cancelled`/`no_show` (room_type+range derived from the reservation's affected lines, grouped by room_type — a multi-room-type booking gets one push per distinct room_type), `reservation_unit.extended`/`cancelled` (from the line itself), and the payload-carrying inventory/rate/restriction events. **Only pushes to `channels.status = 'connected'`** (a disconnected channel would just guarantee a wasted retry-to-dead-letter cycle). It never mutates availability, writes `sold`, or emits a domain event itself.
- **Idempotency key = `ari_push:{channel_id}:{room_type_id}:{domain_event_id}`, not `{channel}:{room_type}:{range}`.** Deliberate: keying on the range alone would let a booking followed quickly by a cancellation for the same room_type+overlapping-range collapse to ONE `sync_jobs` row — the second operation's push would be silently dropped (dispatcher only enqueues on `wasRecentlyCreated`). Keying on the triggering `domain_event_id` instead means every distinct state-changing event gets its own sync_jobs row and its own push — while a genuinely-redelivered SAME event still dedupes (same event id -> same key). Correctness under re-ordering: **not enforced by ordering at all** — each `AriPushJob::process()` reads CURRENT availability/rate/restriction state at execution time (never a snapshot captured at enqueue time), so regardless of which of two competing jobs runs first or gets delayed by retries, the last one to actually execute always reflects the true current state. This is genuine last-write-wins via "always push current truth," not via dispatch ordering.
- **Unmapped room_type is NOT a retryable failure.** `AriPushJob::process()` catches `UnmappedChannelEntityException` from the guard, writes a `sync_logs` warning via the new `RetryableSyncJob::note()` helper, and returns normally — `sync_jobs.status` ends as `succeeded` (delivery handled, nothing to retry) rather than cycling to dead-letter for a config gap a retry can't fix. The guard's own `Log::warning` still fires per Step 2.
- **Verified:** `migrate:fresh --seed` green, full suite **44 tests / 219 assertions** (Phase 1 + Steps 1-2 + 9 new Step 3 tests in `AriPushTest.php`), PHPStan L5 clean, Pint clean. Tests proved (against `FakeChannexClient` only, real client never touched): exactly one push per booking/cancel/block/rate-change (no missed/duplicate/loop — asserted via `DomainEvent::count()` and `pushCount()`); an unmapped room_type produces zero pushes + a clear logged warning + job still succeeds; a disconnected channel gets zero pushes enqueued; a transiently-failing client retries with real backoff (time-travel, `database` connection) and succeeds on the 3rd attempt with exactly one push recorded; a permanently-failing client dead-letters at exactly 5 attempts with zero pushes ever recorded; `availability.sold` is bit-for-bit identical to a plain Phase 1 booking (the push adds nothing) and no extra domain events are emitted (no loop).
- **`tests/Support/channel_helpers.php` (new):** `channelScenario()` moved out of `ChannelMappingTest.php` into a shared Support file (required from `Pest.php`) so `AriPushTest.php` can reuse it without depending on PHP's incidental cross-file function-load order.

### 2026-07-02 — Phase 2 Step 4 close-out (inbound booking import + overbooking policy)

- **Public webhook endpoint does the minimum, returns fast.** `POST /webhooks/channex/{channel}` (`ChannexWebhookController`, CSRF-exempt via `bootstrap/app.php`, no route-model binding — resolves the channel under `runAsSuperAdmin` then sets tenant context, avoiding the Phase-1 Step-9 binding-before-context gotcha). It validates the HMAC signature, dedupes into `webhook_events` on `(source, external_event_id)`, enqueues a `ProcessChannexWebhookJob` through the Step 1 backbone, and returns **202** — zero inventory work in the request. Signature = HMAC-SHA256 over the raw body keyed by `services.channex.webhook_secret`, **fails closed** (no secret ⇒ reject). ⚠️ **Webhook shape + HMAC scheme are DOCUMENTED GUESSES**, exercised only against simulated signed payloads — same live-integration checkpoint as the ARI request shape.
- **OTA bookings import THROUGH `AssignmentService` — the single write path is absolute.** New `AssignmentService::importBooking()` runs the SAME locking core as direct bookings (deterministic lock order, `SELECT … FOR UPDATE`), but is **accept-and-flag, not reject** (`occupyOrFlag()`): if every night has capacity it seats the line (`status=active`, `sold++`, identical to direct); if any night is full it STILL records the line as **`status=overbooked`** (new enum value, added by a raw `ALTER`), does **NOT** increment `sold`, and raises one `overbooking_conflicts` row. `BookingImportService` only normalizes/maps/guest-matches and delegates — it never writes `reservation_units`/`availability`.
- **Overbooking accounting keeps the invariant clean: `sold` = count of ACTIVE occupying lines, always ≤ `total`.** An `overbooked` line carries no `unit_id`, is excluded from Level B overlap checks, and is excluded from sold-freeing on cancel (it never consumed sold). `releaseLine()` centralizes this: active → free sold; overbooked → resolve its conflict (`cancelled_external`), no sold change. `terminate()` now iterates active+overbooked lines through `releaseLine()`, so cancelling an OTA reservation both frees real sold and closes its conflicts (direct reservations have no overbooked lines → no-op superset). This is why `sold` never inflates past capacity — the excess is modeled explicitly, not by letting `available` go negative.
- **Asymmetry proven:** direct booking / walk-in still throw `InsufficientAvailabilityException` when full (unchanged); only the OTA import path accepts-and-flags. Both assertions live in one test.
- **Two-layer idempotency:** webhook redelivery dedupes on `webhook_events(source, external_event_id)` (controller only enqueues on `wasRecentlyCreated`); the same booking arriving via different events dedupes on `reservations(channel_id, ota_reservation_id)` in `BookingImportService::import()`, backstopped by a DB `unique(channel_id, ota_reservation_id)` (MySQL multi-NULL ⇒ direct bookings never collide). Modification re-syncs lines via `reimportBooking()` (release all current lines, re-occupy-or-flag, reservation stays confirmed); cancellation of an unknown or already-terminal booking is a **safe no-op**.
- **Unmapped INBOUND external id fails loudly, never silently dropped.** `ChannelMappingGuard::resolveInboundRoomTypeMapping()` / `resolveInboundRatePlanMapping()` (reverse lookup by `external_id`) throw `UnmappedChannelEntityException::forInbound()` + log — so the import job fails, `webhook_event` → `failed`, `sync_jobs` → `failed` (retries, then dead-letter) with a readable error, leaving the booking parked for manual mapping rather than lost. (Contrast Step 3's OUTBOUND unmapped, which is a benign skip-and-succeed — an inbound drop would lose a real booking, so it must surface.)
- **Guest matching:** by this channel's external ref only (`external_refs->{channel_id} = ota_guest_id`), else create. Blind-index matching of encrypted email/phone stays deferred to Phase 3.
- **Loop closed, no feedback loop:** an OTA import emits `reservation.imported` (and modify → `reservation.modified`), which `EnqueueAriPushOnInventoryChange` now consumes to push ARI back OUT to the other channels. Verified `sold` is bit-identical to a plain direct booking and the push emits no inventory events. `rangesFromReservation()` simplified to derive the affected room_type+range from ALL of the reservation's lines (any status) — robust across confirm/cancel/import/modify since the push reads current state anyway.
- **New:** `webhook_events`, `overbooking_conflicts` tables + models (morph-mapped `webhook_event`, `overbooking_conflict`); `reservation_units.status` gained `overbooked` (documented via an explicit `@property` on the model because Larastan reads the enum from the original CREATE migration and can't parse the raw `ALTER`). No `AssignmentService` locking-core change — only a new sibling path reusing the same lock/`adjustSold`.
- **Test-isolation hardening (pre-existing latent fragility surfaced by this step):** non-RefreshDatabase tests COMMIT rows, and the suite's first RefreshDatabase test (`AssignmentServiceTest`, alphabetically) runs `migrate:fresh` **before** `BookingImportTest`, so BookingImportTest's committed data was surviving into `TenantIsolationTest`'s super-admin count (13 vs 2). Also the shared `jobs`/`failed_jobs` tables leaked queued jobs across tests/runs, breaking the AriPush/SyncReliability retry tests. Fixed with shared helpers: `flushQueue()` (beforeEach on the three queue-driving files) and `cleanupCommittedTenantData()` (afterEach — force-deletes orgs so FK-cascade wipes tenant data, clears event logs), generalizing `ConcurrentBookingTest`'s existing self-clean discipline.
- **Verified:** `migrate:fresh --seed` green, full suite **55 tests / 279 assertions** (Phase 1 + Steps 1-3 + 11 new Step 4 tests in `BookingImportTest.php`), PHPStan L5 clean, Pint clean. All Channex interaction was against `FakeChannexClient` + simulated signed payloads — **no network; the real client + webhook signature/shape remain unverified against live Channex (the deferred integration checkpoint)**. Cross-system concurrency stress (direct-vs-OTA race) is deliberately **not** built here — that is Step 5.

### 2026-07-02 — Phase 2 Step 5 close-out (cross-system concurrency & sync-reliability crux suite)

- **New permanent suite `tests/Feature/CrossSystemConcurrencyTest.php` (7 named tests) + `tests/Support/cross_race_worker.php`.** No new product surface, no new tables — this step hardens and PROVES what Steps 1-4 built. `AssignmentService` remains the sole inventory writer.
- **Parallelism approach (honest breakdown):**
  - **TRUE OS-level parallelism** (proc_open separate processes, separate MySQL connections, shared future-timestamp barrier, committed data, no RefreshDatabase — the Phase 1 `ConcurrentBookingTest` technique): **Test 1 (direct vs OTA)**, **Test 2 (OTA vs OTA)**, and **Test 4's concurrent leg** (3 processes importing the same booking at once).
  - **In-process against `FakeChannexClient`**: Test 3 (ARI-push-per-change), Test 4's sequential leg, Test 6 (overbooking + resolve), Test 7 (mapping guard both directions).
  - **Redis end-to-end via time-travel** (real Redis transport, `queue:work redis --once` + `Carbon::setTestNow`, no real sleeps): Test 5.
  - **No test hits the network** — all Channex interaction is the fake + simulated signed payloads; the live Channex/Stripe checkpoint remains deferred.
- **Direct-vs-OTA race decider — stated honestly.** The headline races a direct booking (targets the specific last unit → runs Level A + Level B) against a type-assigned OTA import for the SAME last unit (`totalUnits=1`). Because an OTA import carries no `unit_id`, the cross-system contention is decided by the `SELECT … FOR UPDATE` availability-row lock + Level A count (the row lock is the mutex enforcing both levels); whoever wins seats, the other is rejected (direct) or recorded overbooked+conflict (OTA). Pure two-parties-on-the-same-physical-unit Level-B contention is not expressible direct-vs-OTA (OTA never targets a unit) and stays proven by Phase 1's `ConcurrentBookingTest` (re-asserted green). Every round asserts: exactly one active seat, `sold == 1` on every night (never 2), and conflicts = 1 iff the OTA lost the seat.
- **Repeat-run result: 5 runs × 5 internal rounds × 2 race types = 50 real races, zero flakiness** (each run: 2 tests / 55 assertions, ~15.7s). Plus one in-suite pass.
- **Two concurrency safeguards surfaced by Test 4 (allowed hardening, no new surface):** (1) the webhook controller now uses `WebhookEvent::createOrFirst` (not `firstOrCreate`) so two concurrent deliveries of the same event resolve to one row instead of a 500 on the unique index; (2) `BookingImportService::import()` catches `UniqueConstraintViolationException` from the reservation insert and re-fetches the winner, so concurrent duplicate imports both return the one reservation without error. The DB `unique(channel_id, ota_reservation_id)` is the backstop both rely on.
- **Re-asserted no regression:** full suite **62 tests / 380 assertions** green on MySQL `vyrobel_test` (Phase 1's 21 + Steps 1-4's 34 + Step 5's 7), PHPStan L5 clean (analyses `app`+`database`; the crux suite's cross-file Pest helpers are not in scope, by design), Pint clean, `migrate:fresh --seed` clean. The Phase 1 core guarantees (double-booking, deadlock-free multi-room, tenant isolation, append-only ledger, GDPR erase, one-directional event discipline) all remain green — Step 5 added cross-system contention on top without weakening any of them.

### 2026-07-02 — Phase 2 Step 6 close-out (Stripe payment gateway)

- **`StripeGateway implements PaymentGateway` — the existing Phase 1 interface, unchanged.** `charge/authorize/capture/void/refund` map onto Stripe PaymentIntents behind a thin `StripeClientInterface` seam (`app/Payments/Stripe/`): `FakeStripeClient` (in-memory, inspectable — records intents/captures/refunds, `nextIntentStatus` knob for 3DS; **implements Stripe's real `Stripe-Signature` HMAC scheme** so tests sign faithfully) and the SDK-backed `StripeClient` (config-gated, never run by CI). `stripe/stripe-php` ^20.3 installed. No new tables/models — reuses `payments`, `payment_authorizations`, `webhook_events`.
- **Gateway selection is config-driven:** `services.payments.gateway` (`manual`|`stripe`, default manual) drives the `PaymentGateway` container binding; `services.stripe.fake` (default true) gates `StripeClientInterface` → fake vs real. FolioService resolves `PaymentGateway` per use, so a per-context switch is just a config read (per-tenant override is a Phase 3 hook). `ManualPaymentGateway` retained and re-asserted working.
- **Phase 1 guarantees preserved exactly:**
  - **TOKEN-ONLY** — `$token` is a Stripe `payment_method` id, `gateway_ref` is a Stripe intent/refund id; a raw PAN/CVV never reaches the app. Re-asserted: `card_token` holds the token and never matches a PAN pattern.
  - **APPEND-ONLY / correct-by-reversal** — refunds are new offsetting `payment` rows (original untouched); auth→capture advances the `payment_authorizations` lifecycle in place (a hold lifecycle, not a posted money-movement row), captures/refunds are always new ledger entries. `FolioService` remains the SINGLE place folio/reservation totals recompute from the ledger — Stripe results post THROUGH it.
  - **Deposit holds** map onto `payment_authorizations` (authorize → capture / void), integer minor units, hold ≠ paid until captured.
- **SCA/3DS surfaced, not swallowed (European requirement).** A `requires_action` intent returns an UNsuccessful `GatewayResult` carrying the `client_secret` (via a new **optional, additive** `GatewayResult::$clientSecret` — the interface method signatures are unchanged). `FolioService::recordPayment` maps such a result to a **`pending`** payment (not a false `failed`, not counted as paid) and stashes `client_secret` in `metadata`; the existing `payments.status` enum already had `pending`. Manual/synchronous gateways return successful → `succeeded`, so Phase 1 behavior is untouched.
- **Stripe webhooks (real signature, idempotent).** `POST /webhooks/stripe` validates Stripe's `Stripe-Signature` scheme via the client seam (config-gated `services.stripe.webhook_secret`), resolves the target `payment` (hence org) under super-admin then sets context, dedupes on `webhook_events(source='stripe', external_event_id)` via `createOrFirst` (concurrent-safe, Step 5 pattern), and calls the new **`FolioService::settlePayment(pending → succeeded|failed)`** — which only advances a `pending` row (double-apply-safe) and recomputes. Processing is inline (a light, idempotent DB update; Stripe retries on non-2xx). `settlePayment` completes an unsettled payment's lifecycle — it never mutates a settled ledger entry.
- **OTA card data via Channex** uses the tokenized/proxy pattern — the import path stores no card data; a Channex-provided token would flow through the same `card_token`/`payment_method` seam. (Modeled; the live token exchange is part of the deferred Channex checkpoint.)
- **Verified:** `migrate:fresh --seed` green, full suite **68 tests / 429 assertions** (Phase 1 + Steps 1-5 + 6 new Step 6 tests in `StripePaymentTest.php`), PHPStan L5 clean, Pint clean. Tests proved (against `FakeStripeClient` only): config-selected charge posts through FolioService + card_token is a token; deposit hold not paid until capture; refund is a new append-only entry; **3DS → pending (with client_secret), then a webhook confirms → paid**; webhook applied exactly once under N redeliveries + invalid signature rejected with nothing recorded; ManualPaymentGateway still works. **HONESTY: everything is against the mocked Stripe client — no network. Live Stripe TEST-MODE verification with real test keys is a separate deferred checkpoint (parallel to Channex staging); the real `StripeClient` + SDK webhook path are unverified by CI.**

### 2026-07-02 — Phase 2 Step 7 close-out (public booking engine — backend + unstyled contract stub)

- **`booking_quotes` built exactly per the Step 0 lock: one table, advisory hold only.** New `BookingQuote` model (Money-cast subtotal/tax/fees/discount/total, encrypted `email`/`phone`, `requested` json). Abandoned capture is folded in via `status=abandoned` + `recovery_status` — no separate table. Registered `booking_quote` in the enforced morph map. Added a nullable/unique `properties.slug` (new column) — the public URL identifier the booking route resolves on.
- **The hold is genuinely advisory — proven, not just asserted.** `BookingEngineService::quote()` only inserts a `booking_quotes` row with `held_until`/`expires_at` timers; it never calls `AssignmentService`, never touches `availability.sold`. Two quotes for the last unit both succeed (tested). `expireStaleQuotes()` (the abandoned sweep) is a plain `UPDATE ... WHERE expires_at < now()` — no inventory interaction either.
- **Inventory is occupied ONLY at book() time, through `AssignmentService` — no parallel path.** `book()` re-derives the reservation lines from the quote's snapshot and calls `AssignmentService::createBooking()` (`source=direct`), which re-checks availability under its own lock exactly as any other direct booking. A quote is a price/availability snapshot, nothing more — the lock at book time is the only thing that's authoritative. Catches `BookingException` → `status: unavailable` (graceful failure, HTTP 409); **never a fallback to force/overbook** — the Step 4 asymmetry holds (only OTA imports accept-and-flag; direct/public bookings reject when full).
- **Payment posts through the existing `FolioService`/`StripeGateway` — no bypass.** `book()` opens the folio, posts the room charge, then `recordPayment()` (which already handles the Step 6 pending/3DS mapping transparently — `client_secret` surfaces to the API response when SCA is required). The booking's own domain events (`reservation.confirmed`) drive the Step 3 `EnqueueAriPushOnInventoryChange` listener exactly as any other booking — closing the loop so a direct sale updates the OTAs outward. No new event-direction code was needed; the booking engine is a pure caller of already-built, already-proven services.
- **Public tenant resolution is real context, never a bypass.** New `ResolvePublicProperty` middleware resolves `{property}` (a plain string route param, not model-bound — avoids the Phase 1 Step 9 binding-before-middleware trap) under an explicit `runAsSuperAdmin` lookup, then sets **both** `TenantContext` and the spatie team id for the rest of the request — the same mechanism `SetTenantForWebRequest` uses for authenticated web requests, just keyed off a public slug instead of a session membership. `BookingEngineController` re-loads the property tenant-scoped from the stashed id on every action, and every quote/reservation lookup is additionally filtered by `property_id` — a request in property A's context 404s on property B's quote rather than ever reading it.
- **Rate limiting:** a new named limiter (`booking`, `RateLimiter::for` in `AppServiceProvider`), per-IP, `services.booking.rate_limit` (default 60/min, config-driven so tests can tighten it). Applied to the whole `/book/{property}/*` route group via `throttle:booking`.
- **Unstyled contract stub confirmed:** `resources/views/booking/stub.blade.php` has zero CSS classes, `<style>` tags, or stylesheet links (grepped to confirm) — plain HTML documenting the exact JSON contract (search/quote/book/confirmation) for the design pass to replace, same spirit as Phase 1 Step 9's Livewire stubs. The JSON endpoints themselves ARE the real, final backend contract (not stubbed) — only the presentation layer is a placeholder.
- **Deferred, cheaply, per the prompt:** promo codes and deposits have schema support (`booking_quotes.promo_code`/`discount_minor`, `payment_authorizations` for deposits) but no engine logic in Step 7 — not exercised by search/quote/book. Flagged here rather than half-built silently.
- **Verified:** `migrate:fresh --seed` green, full suite **74 tests / 471 assertions** (Phase 1 + Steps 1-6 + 6 new Step 7 tests in `PublicBookingEngineTest.php`), PHPStan L5 clean, Pint clean. Tests proved: search returns correct availability/prices and never another org's room_type; a quote never changes `sold` and expiring it marks `abandoned` (still no `sold` change); book+pay seats at confirm (not hold), pays via the Stripe fake through FolioService, and enqueues an outward ARI push; **two holds on the last unit both succeed, but only one book() seats — the second is cleanly `unavailable`, never overbooked**; a cross-property request 404s rather than reading/booking another org's data; the rate limiter engages (429 past the configured cap). **HONESTY: all Channex/Stripe interaction is against the in-memory fakes — no network. The live Channex staging and Stripe test-mode checkpoints remain deferred, unchanged from Steps 3/4/6.**

### 2026-07-02 — Phase 2 Step 8 close-out (staff action layer — backend + contract stubs)

- **Assembly, not rebuild — the whole point of the step.** The only genuinely new logic is one action + read queries; everything else delegates:
  - **`CheckInReservation` action (new)** — the one missing piece, mirroring the Phase 1 `CheckOutReservation` exactly: a pure `confirmed → checked_in` state transition in a transaction + `reservation_status_history` row + post-commit `reservation.checked_in` event. It does NOT go through `AssignmentService` and touches no inventory (the nights were occupied at booking time). No listener acts on the event today — it's semantic-layer/audit context.
  - **`FrontDeskService` (new, thin)** — the staff-action layer. Check-in/check-out delegate to the `CheckIn`/`CheckOut` actions; `moveRoom()` delegates to `AssignmentService::assignUnit` (the ONLY inventory writer — re-checks Level B on the target, frees the old, throws if occupied); `collectPayment()` delegates to `FolioService::openFolio` + `recordPayment` (ledger stays the single recompute point, through the configured gateway). Each method is 1-3 lines of delegation — no re-implemented inventory/payment/HK logic. The only original code is the read queries (`arrivals`/`departures`/`inHouse`), plain tenant-scoped reads over `reservations`.
  - **Housekeeping reused as-is** — the Phase 1 `HousekeepingService` + the existing Step 9 `HousekeepingBoard` stub cover the cleaning board; nothing new built.
- **Contract stubs:** added one unstyled `FrontDesk` Livewire component + `resources/views/livewire/front-desk.blade.php` (arrivals/departures/in-house tables + the action contracts documented in a comment; zero CSS). Route `/app/front-desk` added to the existing `auth`-protected `app` group. No offline-sync, no styled PWA — the design pass. The typed-model-vs-middleware rule didn't bite here (the front-desk stub takes no route-model param; it reads the first tenant-scoped property like `ReservationRack`).
- **Auth proof without a login route:** there is still no login UI (design/auth pass), so an unauthenticated request can't be asserted via a redirect (no `login` route to resolve → it would 500). Auth protection is proven structurally (`app.front-desk` gathers the `auth` middleware) plus `actingAs` 200s + org isolation — the same approach as Phase 1 Step 9's `StubViewsTest`.
- **Verified:** `migrate:fresh --seed` green, full suite **82 tests / 498 assertions** (Phase 1 + Steps 1-7 + 8 new Step 8 tests in `StaffActionsTest.php`), PHPStan L5 clean, Pint clean. Tests proved: check-in transitions state with `sold` untouched; check-out **reuses** the Phase 1 action and the departure HK task still auto-generates via the listener (proves reuse, not reimplementation); room move goes through `AssignmentService` (frees old / re-checks new) and a move onto an occupied unit is rejected (`UnitUnavailableException`, no overbook); payment collection posts through `FolioService` on both Manual and Stripe gateways (token-only, ledger recompute); arrivals/departures/in-house reads are correct and tenant-scoped (another org sees none); staff surface is auth-protected and org-isolated. **No service logic was duplicated. HONESTY: Stripe via the in-memory fake — no network; live checkpoints unchanged.**

### 2026-07-02 — Phase 2 Step 9 close-out — PHASE 2 CERTIFIED (full integrity suite & consolidation)

- **Phase 2 (Distribution & Payments) is COMPLETE and certified** — the backend + unstyled contract stubs for the channel manager, Stripe payments, public booking engine, and staff action layer. No new features or tables were added in Step 9; it hardens and certifies what Steps 0–8 built. The permanent named suite is the certification.
- **Consolidation was mostly verification, not new tests.** An audit mapped every Step 9 obligation to an already-existing explicit named test across the 18 Feature files. Coverage was complete except for **two ARI-push nuances the Step 9 prompt names explicitly** but that lacked their own named test (they were only implied by other tests). Both were added to `tests/Feature/AriPushTest.php` and prove already-built behavior (invariant #13 in PROJECT-STATE.md):
  1. **Event-keyed idempotency** — *"keys idempotency on the triggering domain event: a redelivered SAME event dedupes to one push, a DIFFERENT event does not collapse."* Redelivering the same `reservation.confirmed` `DomainEvent` through `EnqueueAriPushOnInventoryChange::handle()` recomputes the SAME key `ari_push:{channel}:{room_type}:{domain_event_id}`, so `SyncJobDispatcher::firstOrCreate` returns the existing row and nothing new dispatches (still 1 push); a genuinely different event (the cancellation) carries a new `domain_event_id` → new key → its own push (2 pushes, no collapse). This is exactly why the key is event-scoped, not range-scoped.
  2. **Reads current state at execution time** — *"pushes CURRENT availability read at execution time, not a snapshot captured at enqueue time."* Under a deferred `database` queue: book unit 1 (enqueues push J1 while availability = 1), then book unit 2 (availability now 0, enqueues J2), then run exactly ONE queued job — its snapshot reports `available = 0` (current), not 1 (enqueue-time). The `sync_jobs` payload only ever carries the room_type + date range, never an availability figure; `AriPushJob::process()` reads `availability` fresh via `AriSnapshotBuilder`. This is what makes event-keyed idempotency safe under re-ordering (last job to execute always pushes truth).
- **Honest final gate (all actually run, this session):**
  - `php artisan migrate:fresh --seed` — green (all migrations + RoleCatalog + Demo seeders).
  - `php artisan test` — **84 tests / 509 assertions, all green** on genuine MySQL `vyrobel_test` (was 82/498; +2 tests / +11 assertions = the two new ARI tests). Phase 1's core suite (double-booking, deadlock-free multi-room, tenant isolation, append-only ledger, GDPR erase, one-directional event discipline) re-asserted green — zero regression.
  - PHPStan (Larastan) **level 5** — clean, 0 errors (analyses `app`+`database`; needs `--memory-limit=1G` on this machine, an environment ceiling, not a code issue).
  - Laravel Pint — clean.
  - **Concurrency repeat-run: 5 runs of `ConcurrentBookingTest` + `DeadlockAvoidanceTest` + `CrossSystemConcurrencyTest`, all green (9 tests / 114 assertions each, ~37s/run), zero flakiness.** `CrossSystemConcurrencyTest` runs 5 internal rounds each for the direct-vs-OTA and OTA-vs-OTA races → **50 real cross-system races** across the 5 runs, plus Phase 1's true-parallel `ConcurrentBooking`/`Deadlock` every run.
  - **Horizon processing the sync queue on Redis — proven end-to-end this session:** dispatched a real `FlakySyncJob` through `SyncJobDispatcher` to the Redis `sync` queue (`vyrobel-database-queues:sync`, verified LLEN=1 while pending); started `php artisan horizon`, which picked it up (`Tests\Support\Jobs\FlakySyncJob .. RUNNING → 9.98ms DONE`); `sync_jobs` row went `pending → succeeded` (1 attempt) with one `sync_logs` line and the Redis list drained to 0; terminated Horizon cleanly. `QUEUE_CONNECTION=redis`, `REDIS_CLIENT=predis`, `Redis::ping()` = PONG.
- **Parallelism honesty (which guarantee is proven how):**
  - **TRUE OS-level parallelism** (separate PHP processes via `proc_open`, separate MySQL connections, shared future-timestamp barrier, committed rows, no `RefreshDatabase`): Phase 1 `ConcurrentBookingTest` + `DeadlockAvoidanceTest`; Phase 2 `CrossSystemConcurrencyTest` (direct-vs-OTA race, OTA-vs-OTA race, concurrent webhook redelivery leg).
  - **Real Redis transport + time-travel** (`queue:work --once` + `Carbon::setTestNow`, no real sleeps): retry/backoff/dead-letter in `SyncReliabilityTest`, `AriPushTest`, `CrossSystemConcurrencyTest`; the new execution-time-read test uses a deferred `database` queue to open a gap between enqueue and run.
  - **In-process against fakes — NO network, ever:** `FakeChannexClient` (Channex) and `FakeStripeClient` (Stripe, real HMAC scheme) back every remaining test.
- **⚠️ TWO LIVE-INTEGRATION CHECKPOINTS REMAIN OUTSTANDING — NOT verified, NOT implied done:**
  - **(a) Channex staging** — the real ARI push request shape, the inbound webhook payload shape, and the HMAC signature scheme are **documented guesses**, exercised only against `FakeChannexClient` + simulated signed payloads. The real `ChannexClient` (SDK-less REST skeleton over Laravel `Http`) has never hit a real Channex account.
  - **(b) Stripe test-mode with real keys** — the real `StripeClient` (SDK-backed) and its live webhook path are unexercised by CI; every test runs against `FakeStripeClient`. The webhook signature scheme IS Stripe's real documented HMAC scheme (not a guess), but was never validated against a live-sent webhook.
- **Deferred register carried forward (unchanged, do NOT build ad hoc):** promo codes + deposits on the booking engine (schema support in `booking_quotes.promo_code`/`discount_minor` + `payment_authorizations`, no engine logic wired); `guests.stripe_customer_id`/saved cards (Phase 3 — depends on the guest blind-index story); plus all standing Phase 1 deferrals (blind-index for encrypted guest email/phone, gap-free invoice numbering, `units.owner_id` FK → Phase 3 owners, dated out-of-order ranges, `inventory_blocks.quantity`, tenant-custom amenities, same-day-turnover housekeeping dedupe).
- **Next (Phase 2 is the gate; do NOT start either without a written, gated prompt):** EITHER the **design pass** (styled UI replacing the deliberately-unstyled booking-engine + staff/housekeeping contract stubs) OR **Phase 3** (white-label website builder, unified inbox, owner accounting + payouts, first AI agents). Decide and write that prompt the same gated way.

### 2026-07-03 — Live-integration checkpoint: STRIPE leg VALIDATED against real test-mode (Channex still outstanding)

- **Ran `docs/prompts/live-integration-checkpoint.md` Step 3 (Stripe) only.** The real, SDK-backed `StripeClient` + the real `Stripe-Signature` webhook path were exercised against a real Stripe **TEST-mode** account (Stripe CLI `stripe listen` forwarding live test webhooks to `/webhooks/stripe`), on the dev DB `vyrobel`, through the real `StripeGateway`/`FolioService`. Config-gated live via `.env` (`STRIPE_FAKE=false`, `PAYMENTS_GATEWAY=stripe`); **reverted to fakes (`true`/`manual`) at close.** CI never touches the network — `phpunit.xml` pins `STRIPE_FAKE=true`. **Channex Steps 1–2 NOT run — no staging account (see bottom).**
- **Validated live (all against real Stripe test-mode; token-only, no PAN anywhere):** (a) `pm_card_visa` charge → `succeeded`, posted through `FolioService`; (b) 3DS card → real `requires_action` + `client_secret` → mapped to a **pending** payment (not a false success); (c) authorize→capture **with a card** → `requires_capture`→`succeeded`; (d) refund → real refund `succeeded`, recorded as a NEW offsetting ledger entry (original untouched); (e) real webhooks (`payment_intent.succeeded`, `charge.refunded`) delivered + **signature-verified with the real scheme**, idempotent under redelivery (redelivery → `202 duplicate`, settled once), and an **invalid signature → HTTP 400** from the real client.
- **RECONCILIATION OF RECORD — deposit-hold null-token bug (found live, fixed all four corners).** `FolioService::authorizeDeposit()` authorized with **no** payment method (`gateway->authorize($amount, null)`). Real Stripe returns `requires_payment_method` (you cannot hold funds without a card) and the subsequent capture throws `InvalidRequestException`. `FakeStripeClient` had **masked** this by returning `requires_capture` for a null card and capturing unconditionally. Fixed:
  1. **Real path** — `authorizeDeposit(Folio, Money, ?string $cardToken = null, ?DateTimeInterface $expiresAt = null)` now takes and forwards a card token (also stored on the hold row; token-only).
  2. **Faithful fake** — `FakeStripeClient::createPaymentIntent` returns `requires_payment_method` when `$paymentMethod === null`; `capturePaymentIntent` now THROWS unless the intent is `requires_capture` — so CI would catch this class of bug going forward.
  3. **Test** — the Stripe deposit test passes `pm_card_visa` to `authorizeDeposit`.
  4. **CI pin** — `phpunit.xml` pins `PAYMENTS_GATEWAY=manual` so the suite's default gateway no longer depends on `.env` (which a live checkpoint flips to `stripe`); Stripe tests still opt in per-test via `config()`.
- **Benign differences (documented, no code change):** real intent/refund ids are `pi_…`/`re_…` vs the fake's deliberate `pi_fake_…`/`re_fake_…` marker (the gateway branches on status, never on prefix); real Stripe emits many more event types than the two we settle on (`payment_intent.created`, `charge.succeeded`, `refund.created`, …) — the controller correctly acknowledges all with a matching payment (202) and only settles on `payment_intent.succeeded`/`payment_failed`/`canceled`. The `charge.*` id branch of `StripeEvent::paymentIntentId()` (`data.object.payment_intent`) was confirmed against a real `charge.refunded` payload.
- **ONE residual, documented (NOT a code gap):** the 3DS **settle** half was proven with a **real, signature-verified `payment_intent.succeeded` webhook against a real succeeded intent**, with a local `pending` payment representing the pre-confirmation state. The only step not automatable in CI is completing the **browser 3DS challenge** itself — the code path (pending → webhook → settled → paid) is fully exercised live.
- **Honest gate (all run this session):** full suite **84 tests / 509 assertions green** on MySQL `vyrobel_test` (fakes, no network — same count, now reflecting the corrected contract); Pint clean; PHPStan L5, 0 errors. Dev DB restored to clean demo-seed state (the 5 throwaway live-run orgs + their stamped logs removed; only `vyrobel-demo` remains). No secrets committed (`.env` gitignored; only 4 code/config files + docs changed).
- **CHANNEX checkpoint REMAINS OUTSTANDING** — the real ARI push shape, inbound webhook payload shape, and HMAC scheme are still documented guesses, never network-verified. **Blocker: no Channex staging account provisioned yet** (inbound also needs a public tunnel). It is now the **single** outstanding live checkpoint; run `live-integration-checkpoint.md` Steps 1–2 when a staging account exists.

### 2026-07-03 — Phase 3 · Module 1 (Owner Accounting) Step 0 gate resolutions — APPROVED

Baseline audit green (`php artisan test` = 84 tests / 509 assertions) before any build. Schema drafted at `docs/schema/phase-3-owner-accounting.md` (proposal). The four flagged conflicts were approved as proposed, with these locks:

- **OTA commission source (the biggest gap — there was NO source column):** add **`channels.commission_pct`** (per-connection default, nullable decimal) **+ `reservations.ota_commission_minor`** (nullable per-reservation override, populated later from the real Channex booking payload). **When BOTH are absent → attribute OTA cost = 0 AND flag it "unverified / pending channel reconciliation" VISIBLY on the owner statement** (a line/note the owner sees) — never silently guessed. **Tied to the Channex live checkpoint:** real commissions backfill and the flag clears when Channex goes live. Both columns added now (Step 1) so later steps read real columns.
- **`fees.kind`** (nullable enum `cleaning`/`other`) added so **cleaning-fee attribution is deterministic**, not string-name-matched. Added now (Step 1).
- **`Money` gains `percentage()` + `allocate()`** (largest-remainder / banker's rounding) in **Step 2** — pure additions. **INVARIANT: allocated parts ALWAYS sum back to the original — no cent created or lost** (gets an explicit test in Step 2/7).
- **Cross-currency:** statements record an **operator-supplied `fx_rate` + `fx_source`** explicitly — no silent float, no auto-FX provider (an FX provider is deferred to the register).
- **Attribution reversal is WHOLE-TRANSACTION (new invariant):** a cancel/refund/modify reverses **EVERY** attributed component of that booking together (room, commission, mgmt fee, OTA cost) under the same `attribution_version` — never partial. Half-reversed owner ledgers are forbidden.
- **Attribution rule set of record (as proposed):** room revenue → owner gross; management commission (`commission_pct`) → operator; fixed mgmt fee → owner (pro-rated per interval); OTA commission → **owner by default (overridable to operator)**; city/tourism tax → pass-through (excluded from both); cleaning fee → **owner by default (overridable)**; add-ons → operator; damage/security deposit → excluded (not revenue). `management_agreements.attribution_rules` json stores **overrides only**; a missing key = the documented default. Only OTA-cost-bearer and cleaning-fee-owner are overridable — the rest are fixed/definitional.

### 2026-07-03 — Phase 3 · Module 1 Step 1 close-out (owners & management agreements)

- **`owners` built** (`BelongsToOrganization`, ULID, SoftDeletes): person/company party with encrypted PII (`email`/`phone`/`tax_id`, same discipline as guests), `payout_currency`, optional `user_id` → portal login via the Phase 1 External Owner role, `status`. Relations: `user`/`units`/`agreements`.
- **`management_agreements` built (effective-dated):** `commission_pct`, fixed `management_fee` (MoneyCast on `management_fee_minor`/`_currency`) + `management_fee_interval`, `payout_currency`/`payout_schedule`, `attribution_rules` json (**overrides only**), `effective_from`/`effective_to`, `status`. `unit_id` null = owner-wide, set = per-unit override.
  - **Effective-dated resolution (`ManagementAgreement::resolveFor($ownerId, $unitId, $date)` + `scopeEffectiveOn`):** returns the single governing agreement for a date — **most-specific-wins** (per-unit override sorts before owner-wide via `ORDER BY unit_id IS NULL`), constrained to `status=active` and `effective_from <= date <= effective_to` (null `effective_to` = open-ended). Returns null when no agreement covers the date. This is the read the Step 2 attribution engine will call per-night.
  - `rule($key, $default)` helper reads a single override, falling back to the engine-supplied default (the defaults live in code + DECISIONS.md, not copied into every row).
- **Phase 1 hook CLOSED:** `units.owner_id` is now a real FK → `owners` (`nullOnDelete`, stays nullable — hotel rooms have no owner). Added `Unit::owner()` relation.
- **Contract additions folded in now (per Step 0 lock) so later steps read real columns:** `channels.commission_pct` (nullable decimal(5,2); Channel model fillable+cast) and `fees.kind` (nullable enum `cleaning`/`other`, default `other`; Fee model fillable). No behavior wired yet — these are read by the Step 2 attribution engine.
- **Morph map:** registered `owner` + `management_agreement`. **PHPStan L5 note:** Larastan doesn't infer the `array` cast on a json column, so `ManagementAgreement` carries an explicit `@property array<string,mixed>|null $attribution_rules` (same pattern as the Step 4 overbooked-enum `@property`).
- **Verified (all actually run):** `migrate:fresh --seed` green (5 new migrations + RoleCatalog + Demo seeders); full suite **84 tests / 509 assertions green** on MySQL `vyrobel_test` (zero regression — no new tests this step, tables/FKs added without breaking Phase 1/2); PHPStan L5 **0 errors** (`--memory-limit=1G`); Pint clean. **Live-DB smoke check (9 assertions, all PASS, throwaway orgs force-deleted after):** (1) a unit assigned to an owner via the closed FK reloads with the relation intact; (2a–d) effective-dated resolution returns 10% on a 2025 date, 15% on a 2026 date (term change), 20% for the per-unit override (most-specific wins), and null before any agreement; (3a–b) owners are tenant-scoped (Org A cannot see Org B's owner; sees exactly its own); (3c) both models are morph-mapped. Dev DB left at clean demo state (0 owner rows). **No attribution/statement/payout logic built — that is Step 2+.**

### 2026-07-03 — Phase 3 · Module 1 Step 2 close-out (revenue attribution engine — the crux)

- **`Money::percentage()` + `Money::allocate()` (pure additions).** `allocate($weights)` uses **largest-remainder** distribution so parts **ALWAYS sum back to the original exactly — no minor unit created or lost** (sign preserved for negative totals/reversals). Proven by `tests/Unit/MoneyAllocateTest.php` including a **2000-case deterministic fuzz** over random amounts/weights. `percentage()` is a single rounded portion (half-up); exact complementary splits use `allocate([bp, 10000-bp])`.
- **`owner_ledger` — append-only source of truth.** Each attributed component is its own immutable line (`entry_type` ∈ room_revenue/commission/management_fee/ota_cost/cleaning/addon/expense/adjustment; `direction` credit/debit; `amount_minor` always positive, direction carries the sign; `service_date` for per-night; `attribution_version`; `unverified` flag + `note`; `meta` json audit detail). **No SoftDeletes.** Idempotency is **DB-enforced** by a unique `idempotency_key` (`{rid}:{unit}:{entry_type}:v{ver}` for originals, `rev:{origId}` for reversals, `fee:{agr}:{start}:{end}` for fees) — re-running attribution never double-posts, and a reversal never collides with its original. `statement_id` is a nullable forward hook for Step 4 (no FK target yet). Model `OwnerLedgerEntry` (table `owner_ledger`), morph-mapped `owner_ledger`.
- **`OwnerAttributionService` — READ-ONLY over operational data** (reads `folio_items` + `reservation_units`; never mutates folios/reservations/inventory/availability — asserted). Rule set of record applied:
  - **Room revenue** = the authoritative folio room total (Σ `folio_items` type=room, since `BookingEngineService` posts room as ONE lump line), **allocated across active `reservation_unit` lines** — weighting by `price_minor` when all are priced, else by **night count**. This makes multi-unit *and* mid-stay-move attribution per-night correct, and is robust to `split()` zeroing the moved-out segment's price (a mid-stay move = `split()`+`assignUnit()`). Owner + operator shares reconcile to the folio room revenue **to the cent** (via `allocate`).
  - **Commission** = `allocate(room, [pctBp, 10000-pctBp])` → operator share is the exact complement of owner net. **OTA cost** = `reservations.ota_commission_minor` → else `channels.commission_pct` → else **0 flagged `unverified`/`ota_unverified`** (visible, never guessed); owner-borne by default, overridable to operator. **Cleaning** (`fees.kind='cleaning'`) → owner credit by default, overridable. **Add-ons** → operator by default (no owner line), overridable to owner. **Tax** (pass-through) and **deposits** (holds, not folio revenue) → excluded from both sides. **Null governing agreement on a covered date → attribute NOTHING + post one explicit `adjustment` flag line** (`unverified`, note `no_agreement`).
  - **Contract input added now:** `reservations.ota_commission_minor` (nullable; the per-Step-0-lock per-reservation override, populated later from the real Channex payload).
- **WHOLE-TRANSACTION correct-by-reversal (invariant):** `reverseReservation()` reverses **EVERY** component of the booking's live version together in one transaction — a mirror line per original (`reverses_id` set, direction flipped, same amount) — never partial, never editing originals; the booking nets to zero; idempotent. `reattributeReservation()` reverses the live version wholesale and posts a fresh version (numbers reproducible per version). `attributeManagementFee()` pro-rates a fixed fee to the period (idempotent on the period key).
- **Verified (all actually run):** `migrate:fresh --seed` green (2 new migrations: `owner_ledger`, `reservations.ota_commission_minor`); full suite **113 tests / 2597 assertions green** on MySQL `vyrobel_test` (was 84/509 → +15 `MoneyAllocateTest` incl. fuzz, +14 `OwnerAttributionTest`; **zero regression**); PHPStan L5 **0 errors**; Pint clean. Attribution tests prove every mandated scenario: room+commission exact complementarity; OTA override→channel-pct→0-unverified; cleaning/add-on rules+overrides; tax+deposit excluded; multi-unit split-to-owners; mid-stay move per-night; whole-transaction reversal (mirror per component, nets to zero, idempotent) + re-attribution; null-agreement flag; idempotency + read-only. **Worked example (one OTA booking, 15% mgmt commission, 12% channel):** room_revenue +30000 · commission −4500 · ota_cost −3600 · cleaning +4500 → **owner net 26400**; room credit == folio room (30000); owner_share (25500) + operator_share (4500) == 30000; city tax (600) excluded. **No attribution touches folios/inventory. Statements/payouts are Step 4/5 — not built.**

### 2026-07-03 — Phase 3 · Module 1 Step 3 close-out (owner-attributable expenses)

- **`owner_expenses` built** (`BelongsToOrganization`, ULID, SoftDeletes): `owner_id` (nullable — resolved from the unit), `unit_id`, `category` (cleaning/maintenance/other), `amount` (MoneyCast), `expense_date`, `deductible` (nullable — resolved per agreement), polymorphic **`source`** (reuse hook), `attributed_ledger_id`, `flag` (no_owner/no_agreement), `voided_at`. Morph-mapped `owner_expense`.
- **Reuse, not duplicate:** `source` is a polymorphic pointer to the EXISTING record the cost came from — cleaning links to a real **`housekeeping_task`**; a manual expense leaves it null. **No maintenance-ticket module exists yet** (master-plan lists `MaintenanceTicket` as a later entity), so maintenance expenses attribute via their own `unit_id` and the `source` hook is ready for that future module (the test links a maintenance expense's source to the `unit` record — no parallel cost log invented, nothing faked).
- **`OwnerExpenseService`:** `recordExpense()` resolves owner (from unit), the governing agreement (`resolveFor` on the expense date), and **deductibility** — deductible unless the agreement's `non_deductible_categories` rule excludes the category. A deductible expense posts a `owner_ledger` **deduction line** (`entry_type=expense`, debit) immediately, idempotent on `expense:{id}`. A **non-deductible** expense is recorded with `deductible=false` and posts **NO** ledger line (explicit operator cost, not omission). An expense on an **owner-less unit or with no governing agreement** is recorded and **flagged** (`no_owner`/`no_agreement`) with no ledger line — never silently attributed.
- **Whole-transaction reversal reused for corrections:** `voidExpense()` reverses the expense's ledger line (a new mirror row, `reverses_id` set, `rev:{origId}` key) and marks `voided_at` — append-only, never edits the original, idempotent; the owner nets back to zero.
- **Verified (all actually run):** `migrate:fresh --seed` green (1 new migration); full suite **120 tests / 2632 assertions green** (was 113/2597 → +7 `OwnerExpenseTest`; **zero regression**); PHPStan L5 **0 errors**; Pint clean. Also extracted the shared owner test helpers into `tests/Support/owner_helpers.php` (required from `Pest.php`), so owner tests don't depend on cross-file function-load order (same discipline as `channel_helpers.php`). Tests prove: deductible cleaning-from-housekeeping-task posts one deduction to the right owner and drops net by exactly the amount; maintenance expense attributes to the correct owner/unit via its source; non-deductible expense recorded with NO ledger line; void reverses append-only + nets to zero + idempotent; owner-less/no-agreement recorded + flagged, not attributed; posting idempotent. **Worked example:** a deductible €65.00 cleaning expense (sourced from a real `housekeeping_task`) → one `expense` debit line → owner net drops by exactly 6500. **Statements/payouts are Step 4/5 — not built.**

### 2026-07-03 — Phase 3 · Module 1 Step 4 close-out (owner statements — period-locked, append-only projection)

- **`owner_statements` + `owner_statement_lines` built** (morph-mapped `owner_statement`/`owner_statement_line`). A statement is a **PURE PROJECTION** of `owner_ledger`: breakdown caches (gross/other_revenue/commission/management_fee/expenses/ota_cost/adjustments/net_source, all signed, in `source_currency`) + presentation lines (gross → other → commission → mgmt fee → expenses → OTA → prior-period → **net**). **Component lines sum EXACTLY to `net_source_minor` == the ledger net for the period, to the cent.** Closed the Step 2 forward hook: `owner_ledger.statement_id` FK → `owner_statements`.
- **`OwnerStatementService`:** `generateDraft()` recomputes numbers + lines from the ledger without stamping — **idempotent/reproducible** (re-running a draft yields identical numbers); throws if the period is already locked. `finalize()` (in a transaction) **STAMPS** `owner_ledger.statement_id` on every claimed line, recomputes, sets `locked=true`/`status=finalized`, and assigns a per-owner **gap-free `sequence_no`**. A locked statement is immutable — regenerating throws.
- **Period locking + gap-free carry-forward (the crux):** a statement **claims every UNSTAMPED ledger line with `service_date <= period_end`**. Locked-period lines are already stamped, so the only unstamped lines a later statement picks up are its own period's lines + any **post-lock stragglers** dated into a sealed period → surfaced as clearly-labeled **prior-period adjustments** (bucketed by the explicit `prior_period_adjustment` flag, or `service_date < period_start`). **Every ledger line is claimed by exactly one statement** (statements finalized in period order).
- **Explicit lock boundary (not by convention):** new `App\Services\PeriodLockGuard` (`lockedThrough`/`isSealed`). `OwnerAttributionService` and `OwnerExpenseService` now consult it — any ledger line posted with a `service_date` inside a locked period is marked `prior_period_adjustment=true` (keeps its true date, carries forward). `owner_ledger.prior_period_adjustment` column added. When nothing is locked, `isSealed` is false → **zero behavior change to Steps 2/3** (suite confirms).
- **Cross-currency:** when payout currency ≠ ledger currency, an **operator-supplied `fx_rate` + `fx_source`** are recorded on the statement and `net_payable_minor` = `round(net_source × fx_rate)` (single rounding, no silent float, no auto-FX provider — deferred). Same-currency path: `fx_rate` null, `net_payable == net_source`.
- **Unverified OTA lines surface** on the statement as a visible `ota_cost` line flagged `unverified` (kept even when the amount is 0/pending), plus `owner_statements.has_unverified`.
- **Verified (all actually run):** `migrate:fresh --seed` green (3 new migrations); full suite **126 tests / 2675 assertions green** (was 120/2632 → +6 `OwnerStatementTest`; **zero regression**); PHPStan L5 **0 errors**; Pint clean. Tests prove: projection reconciles (net == Σ lines == ledger net); draft idempotent + finalize locks/stamps + regenerate-locked throws; **post-lock refund does NOT touch the locked statement, lands on the next statement as a prior-period adjustment, gap-free (every line claimed once)**; cross-currency records fx + converts net, same-currency unaffected; unverified OTA shows pending; owner isolation (a statement projects only its owner's ledger). **Worked example:** September finalized/locked at net 25500; a post-lock refund left it UNCHANGED and carried forward onto October as a −25500 prior-period adjustment; 0 unclaimed lines. **Payouts are Step 5 — not built.**

### 2026-07-03 — Phase 3 · Module 1 Step 5 close-out (payouts — calculation + lifecycle; money rail deferred)

- **`payouts` built** (`BelongsToOrganization`, ULID; morph-mapped `payout`): owner, source `owner_statement`, Money `amount` in the owner's payout currency, `rail` (manual/stripe_connect), `status` (pending/paid/failed/reversed), nullable `external_ref`/`connect_account_id` (deferred), `supersedes_id` (append-only chain), `failure_reason`, `paid_at`/`failed_at`, `initiated_by`.
- **`PayoutRail` interface + `PayoutResult`** (mirrors the Phase 1 PaymentGateway seam) + **`ManualPayoutRail`** (records an offline bank transfer as paid). **`StripeConnectPayoutRail` is a config-gated SKELETON whose `send()` THROWS** — real Connect (connected-account onboarding + KYC) is a separate deferred live checkpoint; never bound/exercised in tests. Config-gated binding: `services.payouts.rail` (default `manual`) → `PayoutRail` (same pattern as `payments.gateway`).
- **`OwnerPayoutService`:** `createFromStatement()` — **finalized-only** (throws on a draft), idempotent (returns an existing pending/paid payout), creates a pending payout for a **positive** net. `pay()` disburses through the rail → on success marks the payout `paid` + **flips the statement to `paid`**; on failure marks it `failed`. `retry()` of a failed payout is a **NEW** row (`supersedes_id`), the failed row preserved. `reverse()` of a paid payout is a NEW `reversed` row + statement back to `finalized`. All transitions emit `domain_events` + `audit_events` — **append-only, history always reconstructable**.
- **Negative-net behaviour (chosen + documented):** a statement's net is period activity. **Positive → payout; zero → nothing owed, no payout; negative → NO payout and NEVER a reverse-charge** — the negative balance is **carried forward** as a single `adjustment` ledger line (`note=balance_carried_forward`, `prior_period_adjustment=true`) dated into the next period, so it offsets a future positive payout (standard "balance brought forward"). This is why the Step 4 post-lock-refund period (net −25500) doesn't charge the owner — it reduces the next payout instead.
- **Verified (all actually run):** `migrate:fresh --seed` green (1 new migration); full suite **132 tests / 2709 assertions green** (was 126/2675 → +6 `OwnerPayoutTest`; **zero regression**); PHPStan L5 **0 errors**; Pint clean. Tests prove: payout from a finalized statement for the exact net in the owner's currency (draft refused); manual rail pays + flips statement to paid; **zero/negative net → no positive payout, negative carries forward** (asserted the carry line + date + sign; a fresh activity-free owner produces neither payout nor carry); **append-only** failed+retry = two records with a reconstructable `supersedes_id` chain, original never edited away; Stripe Connect rail config-gated + **not** the active rail; owner isolation on payouts. **Worked example:** finalized statement net 25500 EUR → manual payout (amount/currency/rail) → paid → statement flipped to paid; reconciles.
- **Deferred register (add):** **Stripe Connect payout rail** — real owner disbursement (connected-account onboarding + KYC) is an unverified live checkpoint, built here only as a throwing config-gated skeleton behind `PayoutRail`. Joins the Channex staging checkpoint as an outstanding live-integration item.
- **Next: Step 6** — owner portal reads + the owner-isolation security boundary (unstyled contract stubs). Not started.

### 2026-07-03 — Phase 3 · Module 1 Step 6 close-out (owner portal reads + owner isolation — the new security boundary)

- **Owner isolation is a NEW access boundary, built + tested like tenant isolation.** `App\Support\Owner\OwnerContext` (singleton, mirrors `TenantContext`): a row-level scope WITHIN a tenant. **Inactive by default** — operator/manager/CLI/queue/tests see all owners (subject to tenant scope); activated ONLY on the portal.
- **`OwnerScope` (global scope) + `BelongsToOwner` trait** applied to `Unit`, `OwnerStatement`, `OwnerStatementLine`, `Payout`, `OwnerLedgerEntry`, `OwnerExpense`. Behaviour: inactive → no constraint; active with ids → `owner_id IN (ids)`; **active but EMPTY → FAIL CLOSED (`1 = 0`, nothing) — never falls open to all owners.** Added `owner_statement_lines.owner_id` (denormalized from the parent, set in `writeLines`) so lines scope directly (defense-in-depth).
- **`SetOwnerScopeForPortal` middleware** runs AFTER `SetTenantForWebRequest`: resolves the owner(s) the authed user links to within the active org (`owners.user_id`) and activates the scope; **always activates on the portal** (a non-linked user → empty set → fails closed), and **deactivates on `terminate()`** so it can never leak into a later operator request. `OwnerContext` registered as a singleton.
- **Owner portal stubs (unstyled, contract-only, like Phase 1 Step 9):** `App\Livewire\Owner\OwnerDashboard` (`/owner` — the owner's units + occupancy via `AvailabilityService`, statements, payouts, net totals) and `OwnerStatementView` (`/owner/statements/{statement}` — statement + line breakdown). Reads assemble through `OwnerPortalService` over the owner-scoped models. **RMB rule honoured:** the statement id is a plain string prop resolved owner+tenant-scoped in `render()` (never a typed route-model-bound prop) → a cross-owner id 404s. Routes: `auth` + `SetOwnerScopeForPortal`. No CSS/design system (grep-confirmed); no login UI (auth via `actingAs`).
- **Verified (all actually run):** `migrate:fresh --seed` green (1 new migration); full suite **139 tests / 2747 assertions green** (was 132/2709 → +7 `OwnerPortalIsolationTest`; **zero regression** — the `OwnerScope` on `Unit`/owner models is inert off-portal, so operator/service code is untouched); PHPStan L5 **0 errors**; Pint clean. Security tests prove: owner A sees only A's units/statements/lines/payouts/ledger/expenses and B's specific rows resolve to null (not just absent from a list); **fail-closed** empty scope → 0 on every model; a non-linked portal user sees "No units/No statements" (not everything); a cross-owner statement 404s; an owner's view is a strict subset of the operator aggregate (never the org-wide total); the operator side still sees all owners; owner scope composes with tenant scope (org X owner ∧ org Y tenant → empty; cross-org statement 404 both directions).
- **OWNER ISOLATION FAILS CLOSED — stated explicitly and demonstrated:** with the scope active but the owner set empty, every owner-facing query returns **nothing** (operator saw 3 units; owner A saw 1; empty scope saw 0; operator view restored after). An empty/missing scope never falls open to all owners.
- **Next: Step 7** — full integrity suite & consolidation (owner-isolation re-asserted with the tenant suite; attribution rule set + guarantees restated; deferred Connect checkpoint in the register). Not started.

### 2026-07-03 — Phase 3 · Module 1 Step 7 — OWNER ACCOUNTING MODULE CERTIFIED (full integrity suite & consolidation)

- **Phase 3 · Module 1 (Owner Accounting & Payouts) is COMPLETE and certified** — backend + unstyled owner-portal contract stubs. No new features/tables in Step 7; it consolidates and certifies Steps 0–6. **Coverage audit: every module obligation maps to an explicit, passing NAMED test** — 45 owner-accounting tests across 6 files: `tests/Unit/MoneyAllocateTest.php` (5) + `tests/Feature/` `OwnerAttributionTest`(14) · `OwnerExpenseTest`(7) · `OwnerStatementTest`(6) · `OwnerPayoutTest`(6) · `OwnerPortalIsolationTest`(7).
- **Attribution rule set OF RECORD (certified):** room revenue → owner gross; management commission (`commission_pct`) → operator (exact complement via `Money::allocate`); fixed management fee → owner, pro-rated per interval; OTA commission → **owner by default, overridable to operator**; city/tourism tax → pass-through (excluded from both); cleaning fee (`fees.kind='cleaning'`) → **owner by default, overridable**; add-ons → operator by default, overridable; damage/deposit → excluded. OTA-cost source: `reservations.ota_commission_minor` → `channels.commission_pct` → **0 flagged unverified (visible-pending)**. Only OTA-cost-bearer + cleaning-fee-bearer are overridable (`management_agreements.attribution_rules`, overrides-only).
- **Guarantees certified (the module's invariants — added to PROJECT-STATE.md):**
  - **Attribution is derived + read-only**: reads `folio_items` + `reservation_units`, never mutates folios/reservations/inventory; owner_share + operator_share reconciles to the folio room revenue **to the cent**; multi-unit splits per unit, mid-stay moves attribute per-night.
  - **`owner_ledger` is append-only, correct-by-reversal, WHOLE-TRANSACTION**: a cancel/refund/modify reverses EVERY component of a booking together (mirror rows, `reverses_id`), never partial, never edits originals; nets to zero; DB-idempotent (`idempotency_key`). `Money::allocate` is sum-preserving (fuzz-proven — no cent created/lost).
  - **Owner statements are a pure projection, period-locked**: net == Σ component lines == ledger net for the period; a draft is reproducible; finalize stamps `owner_ledger.statement_id` + locks; a locked statement is immutable (regenerate throws); a post-lock adjustment carries forward onto the next statement as a labeled prior-period adjustment — **gap-free (every ledger line claimed by exactly one statement)**. Cross-currency records an operator-supplied `fx_rate`/`fx_source`; unverified OTA surfaces visibly.
  - **Payouts**: created only from a finalized statement's positive net (in the owner's payout currency); manual rail marks the statement paid; negative net carries forward (never a reverse-charge), zero net → nothing; append-only lifecycle (retry/reversal = new records, `supersedes_id`).
  - **OWNER ISOLATION (new access boundary, treated like tenant isolation)**: a row-level scope WITHIN a tenant. **Fails closed** (active + empty owner set → nothing, never all owners); an owner reads only their own units/statements/lines/payouts/ledger/expenses (another owner's specific rows resolve to null); a non-linked portal user gets nothing; an owner's view is a strict subset of the operator aggregate; the operator side is unaffected (scope inert off-portal, deactivated on `terminate`); **composes with tenant scope** (both boundaries hold; cross-org 404 both directions).
- **HONESTY BOUNDARY — what's proven-against-reality vs deferred:** the **accounting is fully real and tested** — attribution, the append-only ledger, expenses, period-locked statements, payout calculation + lifecycle all run against genuine MySQL with named integrity tests (no fakes, no network needed). **Only the Stripe Connect money RAIL is deferred** — `StripeConnectPayoutRail` is a config-gated skeleton whose `send()` throws; the manual rail is the real, tested disbursement path. Owner disbursement to a real bank/Connect account is the single deferred piece of THIS module.
- **Honest module gate (all actually run this session):** `php artisan migrate:fresh --seed` green; `php artisan test` → **139 tests / 2747 assertions, all green** on MySQL `vyrobel_test` (Phase 1's 21 + Phase 2's 63 + Owner Accounting's 45 + the Money unit tests; **zero regression** to any prior guarantee); PHPStan (Larastan) **level 5, 0 errors** (`--memory-limit=1G`); Pint clean (whole codebase). **Concurrency repeat-run: 3× `ConcurrentBookingTest` + `DeadlockAvoidanceTest` + `CrossSystemConcurrencyTest` — 9 tests / 114 assertions each, ~36.7s/run, ZERO flakiness** (Phase 1 true-parallel double-booking + deadlock-free + Phase 2 cross-system direct-vs-OTA / OTA-vs-OTA races, 5 internal rounds each → 30 real races across the runs). Phase 1/2 core (double-booking, folio ledger, tenant isolation, event discipline, cross-system concurrency) all re-asserted green with the owner-accounting layer added.
- **Deferred register (carried forward — do NOT build ad hoc):**
  - **Stripe Connect payout rail** — real money movement to owners (connected-account onboarding + KYC): a separate credentialed live checkpoint, like the Stripe payments one. Joins the Channex staging checkpoint as an outstanding live-integration item.
  - **Auto-FX provider** — statements take an operator-supplied `fx_rate`/`fx_source`; no automatic FX feed (deliberate — no silent float).
  - **Maintenance-ticket source** for maintenance expenses — no maintenance module exists yet; the polymorphic `owner_expenses.source` hook is ready for it (master-plan `MaintenanceTicket`). Maintenance expenses are manual / unit-sourced until then.
  - **`UnitFactory` test-infra debt** — `UnitFactory` hard-codes its own org (not tenant-context-aware like `ChannelFactory`/`OwnerFactory`); owner tests pass `organization_id`/`unit_id` explicitly. Fix the shared factory in a dedicated pass, not mid-module.
  - Plus all standing Phase 1/2 deferrals (Channex staging live checkpoint; blind-index guest matching; gap-free invoice numbering; dated out-of-order ranges; `inventory_blocks.quantity`; tenant-custom amenities; same-day-turnover housekeeping dedupe; promo codes/deposits on the booking engine; `guests.stripe_customer_id`/saved cards).
- **NEXT (do NOT start without a written, gated prompt):** the next **Phase 3 module — Unified Inbox** (WhatsApp/email/OTA/SMS); OR close the **Channex staging live checkpoint**; OR a **thin design slice** to demo the wedge (styled owner statement/portal over the certified backend). Decide and write that prompt the same gated way.

### 2026-07-11 — DESIGN PASS · honesty rule locked (Owner Statement / Screen 1 shipped)

- **Design pass underway (presentation only — NO backend/schema/guarantee changes).** Screen 1, the
  styled **Owner Statement** (`OwnerStatementView` `/owner/statements/{id}`), is live on real owner-scoped
  data (commit `970b0e7`): the approved Claude Design ported onto the stub via a new reusable owner-portal
  layout (`components/layouts/owner` — formalized tokens: frosted glass blur(22px)/saturate(1.15), warm
  layered shadow, terracotta `#C2410C`, Sora/Inter, real vyFadeUp/vyShimmer/vyDrift/vySwap keyframes).
  Suite 139→141 tests / 2769 assertions green, PHPStan L5 + Pint clean; the stub layout
  (`components/layouts/app`) is untouched → other role stubs unaffected.
- **DESIGN-PASS HONESTY RULE (locked — all owner/operator/guest screens):** never render a financial line
  or figure the backend does not compute. Triage every design element into one bucket and handle it so:
  - **REAL** → wire live through the owner-scoped read services (`OwnerPortalService` etc. — called, never modified).
  - **DERIVED** → compute from REAL figures only (e.g. a "% of gross" sublabel from the statement's own numbers); never invent or persist a derived value.
  - **DEFERRED** → visually stubbed + flagged **"not yet available"** (e.g. by-unit split, occupancy — aggregations not built); never fabricated.
  - **NO-BACKEND** → a disabled **"Soon"** affordance; never a fake action / IBAN / date / download.
  - **Money reconciles to the cent** against the source ledger/statement; **owner isolation must hold** (RMB rule: string id → owner+tenant scoped `findOrFail` in `render()`; cross-owner → 404).
  - **Guest PII is never exposed** on owner surfaces (encrypted `guests.email`/`phone`; e.g. no guest names on owner "upcoming stays").
  - Established + proven by Screen 1 (the invented "card-processing fee" line was OMITTED — a real expense/`ota_cost` line fills that slot when present, labelled truthfully). Enforced per-screen by a render/isolation test.

### 2026-07-11 — DESIGN PASS · owner portal COMPLETE (4 screens styled + live)

- **All 4 owner-portal screens are styled + wired to real owner-scoped data** (presentation only — no
  backend/schema/guarantee change): Owner Statement (`970b0e7`), Dashboard (`8f76dbd`), Payouts
  (`a8f7b77`), My Units (`bff8e05`). Shared foundation: `components/layouts/owner` (design system),
  `<x-owner.shell>` (sidebar + topbar, extracted at the 2nd screen), and the
  `App\Livewire\Owner\Concerns\FormatsOwnerData` trait. Each screen carries a per-screen render/isolation
  test enforcing the honesty rule; owner isolation (fail-closed) holds; guest PII never exposed; money
  reconciles to the cent. My Units additionally proves **owner-scoped occupancy** (excludes non-owner
  units in the same room type — the room-type-wide `availabilityRange` trap is avoided).

### 2026-07-12 — DESIGN PASS · operator screens 1–2 styled + live (operator design system extracted)

- **Operator design pass begun; a dedicated operator design system was extracted** (presentation only —
  NO change to AssignmentService / AvailabilityService / FolioService / CheckIn·CheckOut actions /
  HousekeepingService / schema / any guarantee). Mirrors the owner-portal foundation so the two role
  surfaces stay visually one system while staying independent:
  - `resources/views/components/layouts/operator.blade.php` — a **dedicated, self-contained** operator
    layout carrying the SAME formalized Vyrobel tokens as the owner layout (frosted glass blur(22px)/
    saturate(1.15), terracotta `#C2410C`, Sora/Inter, real `vyFadeUp`/`vyShimmer`/`vyDrift` keyframes) +
    screen-specific CSS (rack timeline grid; front-desk rows/tags/summary). Tokens are **copied, not
    shared**, so the certified owner layout is never edited by an operator change — the same
    "dedicated layout per surface" decision the owner layout already records.
  - `resources/views/components/operator/shell.blade.php` — `<x-operator.shell>` (grouped sidebar +
    topbar), extracted on the FIRST operator screen exactly as `<x-owner.shell>` was, so every later
    operator screen inherits ONE chrome. Nav honesty: only real, built routes link; the rest are
    non-interactive "Soon" placeholders.
  - `App\Livewire\Operator\Concerns\FormatsOperatorData` — the operator mirror of `FormatsOwnerData`
    (viewer chip + `money()` + **warm-only** status/source labels whose keys are EXACTLY the real
    reservation `status` enum — nothing invented).
- **Operator screens are TENANT-scoped** (`BelongsToOrganization`), NOT owner-scoped — the operator
  legitimately sees all of the org's data, and guest NAMES are shown (as a front desk does); encrypted
  `guests.email`/`phone` are still never rendered.
- **Screen 1 — Operator Calendar → `ReservationRack` (`/app/rack`)** (`0e1ccaa`): REAL date window
  (GET-link nav, RMB-safe), per-night availability (`AvailabilityService`), and booking bars from active
  `reservation_unit` lines (real guest/status/source/dates) packed into non-overlapping lanes. Warm
  status tones only. DEFERRED: drag-to-reschedule (mutates inventory via AssignmentService) — labelled,
  never a broken interaction; **overbooked lines excluded** (they hold no inventory). NO-BACKEND: new/
  filter/export "Soon". `ReservationUnit::reservation()` gained the generic relation annotation its
  sibling relations already carried (docblock only).
- **Screen 2 — Operator Front Desk → `FrontDesk` (`/app/front-desk`)** (`39724e6`): REAL arrivals/
  departures/in-house (`FrontDeskService`) with real guest/status/unit-or-"Unassigned"/source/nights/
  guests and the **real folio balance** (`balance_minor`, FolioService-maintained), outstanding balances
  terracotta-flagged. **Check-in / check-out are WIRED and persist** via a NEW pattern reused for every
  operator mutation from here: **POST → controller → existing service action → PRG redirect** (
  `App\Http\Controllers\FrontDeskActionController` → `FrontDeskService` → the Phase 2 Step 8 actions).
  Deliberately NOT a Livewire wire-action — a POST to a normal route re-runs the web middleware so the
  mutation is tenant-scoped; `{reservation}` is a **string** param resolved via tenant-scoped
  `findOrFail` (never RMB) → cross-org 404, mutates nothing. Guarded to sane transitions. DEFERRED:
  "Collect payment" (needs amount + card-token + gateway flow) shown real-looking but flagged — never
  fakes a payment; "Assign unit" links to the rack (the real assignment surface). NO-BACKEND: walk-in/
  filters/print "Soon".
- **Honesty rule (2026-07-11) now enforced across owner + operator** by a per-screen render/isolation
  test. **Verification (all actually run):** full suite **155 tests / 2860 assertions green** on MySQL
  `vyrobel_test` (owner 141 baseline + rack 4 + front-desk 4 vs the 139 pre-design-pass); PHPStan L5 0
  errors; Pint clean. New tests: `OperatorRackViewTest` (render + real window filtering + no-invented-
  status + isolation + PII) and `OperatorFrontDeskViewTest` (render + real balance flag + no-invented-
  status + isolation + **check-in persists through the real action** + cross-org check-in 404s + PII).
- **NEXT: continue the operator screens** — `Operator Housekeeping` (`/app/housekeeping`), then
  `Operator Reservation Detail` (`/app/reservations/{id}`), then `Operator Dashboard`; then the public
  booking engine. Same discipline (inherit the operator system; honesty rule; RMB/PRG for mutations;
  per-gate commit + per-screen test). Other tracks (Unified Inbox module, Channex/Stripe-Connect live
  checkpoints) remain available but are NOT the chosen next step.

### 2026-07-12 — DESIGN PASS · operator Screens 3–4 styled + live (housekeeping, reservation detail)

- **Two more operator screens styled + live** (presentation only — no change to HousekeepingService /
  FolioService / AssignmentService / the append-only ledger / schema / any guarantee), both inheriting
  the operator design system + the operator-mutation pattern from Screens 1–2:
  - **Screen 3 — Operator Housekeeping → `HousekeepingBoard` (`/app/housekeeping`)** (`d715496`): a
    cleaning **kanban** whose **4 columns ARE the real `housekeeping_tasks.status` enum**
    (pending/in_progress/clean/inspected — no invented columns). Per card: real unit, turnover `type`,
    `priority`, `date`, assigned housekeeper (or "Unassigned"). Warm tones only. **Status advance
    (Start / Mark clean / Mark inspected) is WIRED + persists** via POST → `HousekeepingActionController`
    → `HousekeepingService::updateTaskStatus` → PRG — including the unit-cleanliness sync on clean/
    inspected; only real-enum targets accepted (422 otherwise). **DEFERRED (not faked):** drag-to-move
    (offered as the safe POST status button instead — no drag that doesn't persist); **assign-housekeeper
    — there is NO `HousekeepingService` method for it, so the assignee is DISPLAYED but "Assign" is a
    flagged "Soon"** (never a faked assignment).
  - **Screen 4 — Operator Reservation Detail → `ReservationDetail` (`/app/reservations/{id}`)**
    (`b49676d`): RMB-safe (string id → tenant-scoped `findOrFail` in `render()`). **The folio is rendered
    as the real APPEND-ONLY LEDGER** — every `folio_item` + `payment` its own chronological line (charges
    +, payments −, refunds their OWN offsetting line; a prior line is never edited/struck). **Totals are
    derived from the same rows and RECONCILE to the folio's cached figures to the cent.** **No line the
    ledger doesn't contain is rendered** (the owner-statement fake-fee precedent — proven by an absent-
    fabricated-fee assertion). A deposit/pre-auth line shows ONLY when a real `deposit_hold` exists.
    **Money actions — WIRED + persist APPEND-ONLY** via POST → `ReservationFolioActionController` →
    `FolioService` → PRG: **Record payment** (through the configured gateway; manual = offline) and
    **full Refund** per settled payment (a NEW offsetting `refund` row; the original is never touched —
    proven). The refund's target payment is scoped to the reservation (cross-org 404). **DEFERRED (not
    faked):** add charge, partial/custom refund, void, capture-hold, move room (guarded AssignmentService),
    modify/cancel, export/print.
- **Operator design pass now = 4 screens** (Calendar/Rack, Front Desk, Housekeeping, Reservation Detail),
  all on the shared operator system, honesty rule enforced per-screen. **Verification (all actually run):**
  full suite **163 tests / 2910 assertions green** on MySQL `vyrobel_test` (+4 `OperatorHousekeepingViewTest`,
  +4 `OperatorReservationDetailViewTest` vs the 155 after Screens 1–2); PHPStan L5 0 errors; Pint clean.
  `StubViewsTest` (housekeeping) updated to the styled type label; `StaffActionsTest` unchanged + green.
- **NEXT: `Operator Dashboard`** (new page over certified data), then the public booking engine
  (`/book/{property}`), then `/app/units`.

### 2026-07-12 — DESIGN PASS · Operator Dashboard styled + live — OPERATOR CORE COMPLETE

- **Screen 5 — Operator Dashboard → `App\Livewire\Operator\OperatorDashboard` (`/app/dashboard`)**
  (`434e1a9`): a **NEW tenant-scoped page** (no prior stub/route — like the owner Payouts/Units screens),
  on the shared operator design system. Read-only; presentation + thin page only — no service/schema/
  guarantee change. This **completes the operator core** (Screens 1–5).
- **Every KPI is a TRUE org figure** — the metrics-screen risk is a plausible-but-wrong number (the owner
  room-type-wide occupancy trap). Handling per the honesty rule:
  - **REAL:** occupancy tonight % + rooms sold/available — **summed ORG-WIDE from the real `Availability`
    spine** (Σ `total`/`sold`/`available()` across the property's tenant-scoped room types), never a
    mislabeled per-room-type figure; arrivals/departures/in-house counts (`FrontDeskService`);
    housekeeping status counts (real `housekeeping_tasks.status` enum) + "to clean" (pending+in_progress);
    **payments received on the date** (settled payment/capture − succeeded refund, tenant-scoped `Payment`
    ledger); today's arrivals as a **names-only** list.
  - **DERIVED:** occupancy % (sold/total), to-clean — from real figures only.
  - **DEFERRED (flagged, not faked):** revenue / ADR / RevPAR trend — a series aggregation this pass
    doesn't build → a "Not yet available" stub, never a fabricated chart or number.
  - **NO-BACKEND:** custom range / export disabled "Soon".
  - **Guest PII:** arrivals names only; encrypted email/phone never rendered. As-of day via `?date=` GET
    links (RMB-safe); scoped reads resolve in `render()`.
- **The "Dashboard" nav item in `<x-operator.shell>` now points to the real route** (`route('app.dashboard')`).
- **OPERATOR CORE COMPLETE — 5 screens** (Calendar/Rack, Front Desk, Housekeeping, Reservation Detail,
  Dashboard) all on the shared operator system, honesty rule enforced per-screen. **Verification (all
  actually run):** full suite **166 tests / 2926 assertions green** on MySQL `vyrobel_test` (+3
  `OperatorDashboardViewTest`); PHPStan L5 0 errors; Pint clean. The dashboard test proves occupancy is
  the true org figure ("2 of 3 rooms sold", 67%), tenant isolation (org B's 3-of-3 never enters org A's
  2-of-3, no combined 5-of-6), the payments KPI is real + tenant-scoped, no fabricated series, no PII.
- **NEXT: the public booking engine** (`/book/{property}` — styled onto the unstyled stub; the JSON API is
  the real final backend), then `/app/units`. NB it is PUBLIC (context via `ResolvePublicProperty`, not the
  operator `app` group / `SetTenantForWebRequest`).

### 2026-07-12 — DESIGN PASS · public booking storefront Screens 1–2 (Browse, Room Detail) — read-only

- **The public booking storefront design pass began — a NEW PUBLIC design system** (presentation only —
  no change to AvailabilityService / BookingEngineService / AssignmentService / schema / any guarantee).
  Both screens are **READ-ONLY and write NO inventory** (the booking WRITE / checkout is the next screen).
  - **PUBLIC context (no bypass):** unauthenticated; `ResolvePublicProperty` resolves the `{property}` slug
    under an explicit super-admin lookup, sets **real** `TenantContext` + spatie team id to the property's
    org, and stashes the property id — every downstream read runs tenant+property-scoped. A visitor for
    property X can only ever see X's inventory; another property/org's data 404s. This is NOT the operator
    `app` group and NOT `SetTenantForWebRequest`; there is no logged-in user.
  - **`<x-public.shell>`** (`components/public/shell.blade.php`) — a full-page storefront chrome (property
    header + centered content + footer, **NO operator sidebar/nav**), reusing the SAME Vyrobel tokens
    (frosted glass, terracotta, Sora/Inter, keyframes) — copied, self-contained, same "dedicated shell per
    surface" decision. **No Livewire** (server-rendered, rate-limit-friendly for a public page).
- **Screen 1 — Browse / Search → `BookingEngineController@browse` (`/book/{property}`)** (`44ae311`):
  restyled the landing (renamed `stub()`→`browse()`, removed `booking/stub.blade.php`). REAL search
  (GET → re-runs `ResolvePublicProperty`) + the **sellable rooms** for the dates via the certified
  `BookingEngineService::search` (availability via `AvailabilityService`, real prices; only `available≥1`
  + a real full-stay price). Per-night **derived** from the real total. Before a date search → a
  **names-only room teaser** (no availability/price claim); a no-availability search → an honest empty
  state. The Select CTA links forward to Screen 2 (never books).
- **Screen 2 — Room Detail → `BookingEngineController@room` (`/book/{property}/rooms/{roomType}`)**
  (`c933973`): the room-type is resolved **PROPERTY-scoped** (`RoomType where property_id = X` →
  cross-property/org 404). REAL specs from the schema (name/kind/sleeps/adults/children, + bedrooms/
  standard-occupancy shown only when the nullable column is set), REAL amenities via the polymorphic
  amenities catalog (only when catalogued), and REAL availability/price for the searched dates.
  **DEFERRED (flagged, not faked):** photos/gallery (decorative banner, "Photos soon"), amenities when
  none exist ("not yet listed"), description/reviews/map ("coming soon"). "Book this room" is flagged
  "Soon" and performs **no write**. Read the `{roomType}` route param via `$request->route()` (a two-param
  binding quirk fed the method arg the property slug); `RoomType` gained `@property int|null` for
  `bedrooms`/`std_occupancy` (nullable columns whose `integer` cast made Larastan infer non-null — docblock
  only).
- **Verification (all actually run):** full suite **172 tests / 2970 assertions green** on MySQL
  `vyrobel_test` (+3 `PublicBookingBrowseTest`, +3 `PublicBookingRoomTest`); PHPStan L5 0 errors; Pint
  clean. Tests prove: real sellable rooms/prices render with **no auth**; **public isolation** (X's
  storefront never shows Y's rooms; a Y room 404s on X; unknown slug/room 404); nothing fabricated (teaser
  + empty-dates honest). The JSON API contract (`PublicBookingEngineTest`) is unchanged + green.
- **NEXT: the public booking CHECKOUT (the WRITE flow)** — quote → book, routing THROUGH `AssignmentService`
  (never overbooks) + `FolioService` (append-only); the advisory `booking_quotes` hold reserves no
  inventory. Then `/app/units`.

### 2026-07-12 — DESIGN PASS · public booking CHECKOUT (Screen 3) — the first public WRITE, on the real path

- **The public checkout is styled + live — the FIRST public screen that WRITES inventory (anonymous
  visitor), given more care than any prior screen.** Presentation + a thin page: the controller only
  CALLS the certified services — **no change to `AssignmentService`/`FolioService`/the append-only ledger/
  schema/any guarantee, and NO parallel write path.**
  - **GET `/book/{property}/checkout`** — review + guest form (READ-ONLY; real price via the certified
    search path; writes nothing).
  - **POST `/book/{property}/checkout`** — the WRITE: creates the advisory `booking_quotes` HOLD (reserves
    NO inventory, has expiry), then books **THROUGH `BookingEngineService::book`** → `AssignmentService`
    (source=direct, re-checks availability **under its lock** and **REJECTS when full — never overbooks**;
    only OTA imports accept-and-flag) → room charge + payment **THROUGH `FolioService`** (append-only). On
    a lost race → an honest "just booked by someone else" redirect, **never a faked confirmation**. PRG →
    styled confirmation with the **real reservation reference**.
  - **Payment:** the wired method is **manual / pay-on-arrival**, recorded for real through `FolioService`.
    Real card entry (**Stripe Elements + 3DS**) is the **DEFERRED** rail (the deferred Stripe live
    checkpoint) — no fake card field, no fabricated payment.
- **CSRF — deliberate, not disabled (a public write is an attack surface):** the JSON API (`/quote`,
  `/book` — machine clients, token-authenticated by contract) stays CSRF-exempt, but the blanket `book/*`
  exemption was **NARROWED to `book/*/quote` + `book/*/book`** so the styled session form
  (`book/{property}/checkout`) is **session-CSRF-validated** (`@csrf`) on top of the group's
  `throttle:booking` rate limit. (`bootstrap/app.php`.)
- **Public isolation:** `quote()` resolves the room-type **property-scoped** (`RoomType where
  property_id = X`) → a checkout on property X **cannot** book property Y's room (404, nothing written).
  No PII beyond what the guest enters. The room "Book this room" CTA now links forward to checkout.
- **Two route fixes:** read the `{quote}` route param via `$request->route()` (the same two-param binding
  quirk that fed `room()` the slug); the checkout DRYs its offer lookup via a shared `offerFor()` helper.
- **THE CRITICAL GUARANTEE — verified, no regression:** `PublicBookingCheckoutTest` proves a full flow
  creates a **real direct reservation via `AssignmentService`**, reserves inventory **at book, not at
  hold**, takes a **real append-only payment**, and shows the real reference; the advisory hold (and an
  expired hold) **never change `sold`**; and **two holds on the last unit both succeed but only ONE
  `book()` converts — the second is cleanly rejected, `sold` never exceeds 1** (asserted at the service
  level AND through the styled screen). Public isolation (cross-property checkout 404) and the CSRF token
  on the write form are asserted. **Full suite 178 tests / 2997 assertions green; the true-parallel
  no-double-booking suite (`ConcurrentBooking`/`Deadlock`/`CrossSystemConcurrency`, 9/114) re-run green**
  after the checkout screen; PHPStan L5 0 errors; Pint clean. The JSON API contract
  (`PublicBookingEngineTest`) is unchanged + green.
- **NEXT: the last unstyled stub `/app/units`** (operator units list). Deferred booking polish (flagged
  in-product): Stripe card checkout, room photos/descriptions/reviews.

### 2026-07-12 — DESIGN PASS · operator Units styled — the STYLED SURFACE is COMPLETE

- **`/app/units` → `App\Livewire\UnitsList`** styled onto the shared operator design system (reused, not
  redefined). Presentation + a thin read: READS only; no service/schema/guarantee change; tenant-scoped
  (`Unit` `BelongsToOrganization`). **REAL:** each unit's code/name/room-type/kind (`unit_kind`)/sleeps
  (`max_occupancy`) + **cleanliness + condition keyed to the real enums** (`housekeeping_status`
  clean/dirty/inspected, `condition_status` in_service/out_of_order/out_of_service — warm tags) + active
  flag + the **owner LINK where `owner_id` is set** (owner NAME only; units without an owner show
  "Operator-owned" — real, not blank-faked). **DERIVED:** summary counts. **DEFERRED:** per-unit photos
  (no real bytes → not shown). **NO-BACKEND:** add/edit/filter/export "Soon". **PII:** owner name shown (a
  business party, not a guest); no encrypted email/phone. Verified by `OperatorUnitsViewTest` (render +
  real enums + owner link + no PII + tenant isolation).
- **THE DESIGN PASS IS COMPLETE — the whole styled surface over the certified backend is done:** owner
  portal (4 screens) · operator core (5: Calendar/Rack, Front Desk, Housekeeping, Reservation Detail,
  Dashboard) · operator Units · public booking storefront (3: Browse, Room Detail, and the real checkout
  WRITE through AssignmentService + FolioService). No unstyled contract stubs remain. Three role design
  systems: `components/layouts/owner` + `<x-owner.shell>` + `FormatsOwnerData`; `components/layouts/operator`
  + `<x-operator.shell>` + `FormatsOperatorData`; `components/public/shell` (server-rendered, no Livewire).
  The honesty rule (2026-07-11) held across all ~13 screens, each with a render/isolation test; operator
  writes use POST→controller→service-action→PRG; the public write is CSRF-protected (JSON API exempt by
  path). **Suite 180 tests / 3014 assertions green; PHPStan L5 0 errors; Pint clean.** Backends/schema/
  guarantees are UNCHANGED throughout — presentation only.
- **NEXT (needs a written, gated decision — none started):** deferred in-product polish (Stripe card
  entry at checkout, room photos/descriptions/reviews); OR the next Phase 3 module (Unified Inbox); OR a
  live-integration checkpoint (Channex staging / Stripe Connect payout rail).

### 2026-07-12 — BROWSER-VERIFICATION PASS (3 user journeys) — 2 demo-data gaps found + fixed

The styled surface was exercised end-to-end in a real browser (Playwright), not just by feature tests —
three user journeys against the demo data. **No app logic changed; both fixes were demo DATA** (the
`DemoSeeder`), and each gap was a shipped, tested feature left *undemoable* by missing seed data — exactly
what a browser pass (not a unit test) surfaces.
- **Flow 1 — public booking (`6e3ceb6`):** the `DemoSeeder` created the demo property with a **NULL slug**,
  but `/book/{property}` resolves by **property slug** → the whole public storefront was unreachable for the
  demo. Fix: seed `slug = 'seaside-demo'`. Proven in-browser: search → room → checkout → **real** direct
  booking (`source=direct` via `AssignmentService`, inventory reserved at book, real manual payment through
  `FolioService`, real reference). (Plus two browse-copy fixes: `middteman`→`middleman`; stale
  "next screens" footer reworded.)
- **Flow 3 — owner portal, THE WEDGE (`c66e258`):** `demo-owner@vyrobel-demo.test` was **not linked to an
  Owner** and no owner-accounting data was seeded → `/owner` **failed closed** (empty portal), making the
  wedge undemoable despite the certified backend. Fix (demo data **through the certified services** —
  nothing fabricated): the demo user is a real Owner of apartments A1/A2 with an **18% agreement**; bookings
  attributed via `OwnerAttributionService` → `owner_ledger`; a September statement finalized via
  `OwnerStatementService`; a pending payout via `OwnerPayoutService`. **Net €885.60 = gross €1,080 − 18%
  €194.40, reconciles to the cent** (ledger signed-sum == statement net). Proven in-browser across
  dashboard / statement (no invented fee line, no PII) / payouts (real enum states only) / units
  (owner-scoped occupancy) with the scope staying active across GET-link month nav.
- **Flow 2 — operator ops:** exercised (calendar/front-desk/housekeeping/folio/dashboard); no gaps found.
- **Verified:** the demo now demos the full loop (public booking → operator manages → owner sees reconciling
  money). Suite **180 / 3014 green; PHPStan L5 0; Pint clean** — no app logic touched.

### 2026-07-13 — AUTH PHASE A (login/logout) + PHASE B (owner invite flow) — COMPLETE & secure

Real, session-based authentication now fronts the whole app — **the temporary dev-login shim is gone**.
Backends/schema/guarantees are UNCHANGED beyond additive invite plumbing; the flow **calls** the existing
owner-accounting services, it does not reimplement them.

**Phase A — the shared Vyrobel login (`ddbaf47`).** ONE session login for operator + owner
(`LoginController` create/store/destroy, `LoginRequest`, styled `auth/login`):
- **Active-only sign-in** — `Auth::attempt(['email','password','status'=>'active'])`, so an `invited`
  (not-yet-activated) or `disabled` login **cannot** authenticate even with the right password.
- **Rate-limited per (email, ip)** (Breeze pattern) + **generic errors** (no account enumeration) +
  **session regenerated** on login and invalidated on logout. Logout is a **CSRF-protected POST** behind
  `auth`.
- **Role routing:** an authed user linked to an `Owner` (via `owners.user_id`) → `/owner`; otherwise the
  operator app `/app`. `/app/*` + `/owner/*` require `auth` (redirect to `/login`); `/book/*` stays public.
  The two context middleware (`SetTenantForWebRequest`, `SetOwnerScopeForPortal`) simply read
  `$request->user()` — login just authenticates; context resolution was already real.

**Phase B — the owner invite flow (`0bfc48b` · `4b2f196` · `ab78ac3` · `5ab7e96`).** Operator invites an
owner → email → owner sets password → lands in their portal:
- **Token design:** high-entropy `Str::random(64)`; **only its `sha256` hash is persisted**
  (`owner_invites.token_hash`, indexed → O(1) lookup), **never the plaintext**; **single-use** (status
  `pending → accepted`), **7-day expiry**, **revocable**, tied to (email, org). Re-inviting **refreshes**
  the pending invite's token instead of duplicating (`OwnerInviteService`).
- **Operator invite = the proven operator-mutation pattern** — CSRF-protected, `throttle:10,1` **POST →
  `InviteOwnerController` → `OwnerInviteService::invite()` → PRG**. The POST re-runs the web middleware so
  the invite + assigned units are created **org-scoped**; a foreign unit id is **silently dropped**
  (`Unit::whereIn(...)` tenant filter) — never assigned across orgs. Creates an **inactive `invited`
  login** (unusable random password) + a **pending `inactive` owner** + an active membership, then
  dispatches the email.
- **Owner-only, defense-in-depth:** the modal renders **Manager/Accountant DISABLED "Coming soon"**, the
  controller re-validates `Rule::in(['owner'])`, AND the service throws on any non-owner role. The client
  is never trusted.
- **Accept = public, token-authenticated** (`AcceptInviteController`, `/invite/{token}`, `throttle:10,1`):
  `findRedeemable()` matches by sha256 under `runAsSuperAdmin` and returns null for any **unknown / expired
  / spent** token → the **SAME generic "invalid link" page** (no enumeration of which invites exist).
  `accept()` runs `forOrganization(...)` in a DB transaction: sets the password (**`hashed` cast** — never
  plaintext), **activates** the login + owner, **assigns the units**, **creates the `ManagementAgreement`**
  at the invite's commission, and **SPENDS the token**. Then **auto-login + session regenerate** → `/owner`,
  where owner-scope binds to the now-active owner and shows their real data.
- **Email = `OwnerInviteMail`** rendered **email-safe** (table layout, all-inline CSS, no
  backdrop-filter/flex, Arial fallback, bulletproof button, preheader) — brand *evoked*, not the frosted
  app chrome faked.

**DEFERRED (flagged, not faked):**
- **Manager / Accountant invites** — UI-disabled **and** server-rejected; only OWNER is wired in Phase B.
- **Email DELIVERY** — dev uses `MAIL_MAILER=log` (the Mailable is really rendered + logged, proven by the
  end-to-end test, but nothing leaves the box). Real delivery needs a configured driver — **Mailpit/SMTP**
  locally, a real transactional provider in production. The flow is complete; only the transport is
  deferred. Also deferred: **password reset** ("Forgot password?" shows "Soon"), and **2FA** (schema
  columns exist, no flow).

**VERIFIED — one continuous end-to-end proof + the full net.** `PhaseBEndToEndTest` drives the WHOLE
chain in one test (**39 assertions**): operator (logged in, tenant-scoped) invites via the modal action →
`OwnerInviteMail` dispatched with the tokened link (captured from the Mailable) → guest opens `/invite/
{token}` → sets a valid password → login+owner activate, unit assigned, 18% agreement created → auto-login
→ `/owner` shows the owner's own unit → the token is **SPENT** (a reuse gives the **byte-identical** page as
a garbage token → no enumeration). Security recap asserted in-chain: token **hashed** (`token_hash ===
sha256(plain)`, not the plaintext), password **hashed** (`Hash::check` true, not stored raw), **single-use**
(second activation impossible), **auto-login** establishes the session, **owner-scope binds**. Plus the
per-step suites: `AuthLoginTest` (11), `OwnerInviteBackendTest` (5), `OwnerInviteAcceptTest` (7),
`OwnerInviteEmailTest` (2), `OperatorInviteOwnerTest` (6, incl. tenant-scope / cross-org-drop /
role-reject / active-email-graceful / auth-required). **Full suite 212 tests / 3220 assertions green;
PHPStan L5 0 errors; Pint clean.**

### 2026-07-13 — Mailpit wired for dev/demo email (config/docs only — no app logic)

The invite email now **lands in a viewable inbox** for demos instead of only logging. **Mailpit** is the
dev mail catcher: `brew install mailpit`, run `mailpit` (SMTP `127.0.0.1:1025`, UI `http://127.0.0.1:8025`),
`.env` `MAIL_MAILER=smtp` + `MAIL_PORT=1025` (`.env` stays gitignored — no secrets committed; Mailpit needs
no auth). `.env.example` keeps `MAIL_MAILER=log` as the **zero-dependency safe default** (a fresh clone
works with no Mailpit installed) with the Mailpit switch documented inline and `MAIL_PORT` pre-set to 1025.
- **Verified end-to-end (browser):** an operator sent an owner invite through the real modal → the real
  `OwnerInviteMail` arrived in Mailpit (from *Vyrobel*, branded, personalized "Hi …", single-use expiry
  note) → its tokened `/invite/{token}` link opened the **Set your password** screen. No app code changed;
  full suite **212 / 3220** still green (tests use `Mail::fake`, unaffected).
- **HONESTY:** Mailpit is **dev/demo only**. **PRODUCTION still needs a real transactional provider**
  (Postmark/SES/etc.) — that remains the one deferred piece of the email story.

### 2026-07-13 — Invite flow extended to STAFF roles (Hotel Manager + Accountant) — owner-only no longer

The invite flow now covers **three** roles on ONE set of secure machinery (hashed/single-use/expiring
tokens, no enumeration, rate-limit, session-regen, tenant isolation) — NOT a parallel system:
- **OWNER** (unchanged): an external `Owner` party → owner link + `/owner` portal, optional assigned
  units + management agreement. **No spatie role** (portal access comes from the owner link).
- **STAFF — Hotel Manager / Accountant** (new): a `Membership` + a **real spatie catalog role** → the
  operator app `/app`. **No owner record, no agreement, no units.**

**Real RBAC keys (read from `config/authorization.php`, not invented):** the modal's "Manager" maps to
the catalog role **`Hotel Manager`** (there is NO bare "Manager" role); "Accountant" maps to **`Accountant`**.
Slug→role is a whitelist in `OwnerInviteService::STAFF_ROLE_MAP`; **`resolveStaffRole()` fail-safes** —
the slug must map to a catalog role AND that role must be **provisioned for the org** (team-scoped;
`OrganizationRoleProvisioner` installs it at org-creation / demo seed) before assignment. This is checked
at **invite time** (operator gets a clean flash, never a phantom invite) AND again inside the accept
transaction (defense-in-depth — a non-assignable role rolls the whole activation back, never a
half-activated login with no role). The team foreign key is **`organization_id`** (spatie teams mode,
per `config/permission.php`), not `team_id`.

**Schema:** `owner_invites.owner_id` made **nullable** (additive migration) — staff invites carry no
owner. The `OwnerInvite`/`OwnerInviteMail` names are kept (historical; they're the invite records/mail,
now role-aware) rather than a churny rename.

**The fork, handled explicitly:** the accept controller redirects **by the invite** — owner (`owner_id`
set) → `/owner`; staff (`owner_id` null) → `/app` (Phase-A role-routing already sends a member with no
owner link to `/app`). The set-password greeting resolves the name from the owner (owner path) or the
invited login (staff path). The modal **enables** Manager/Accountant (was disabled "Coming soon"),
**hides the owner-only fields** (assign units, commission) when a staff role is selected, and the
title/subtitle/helper + the **email copy** are **role-aware** (a manager is never told about an "owner
portal"). The set-password subtitle was de-ownered ("your Vyrobel account").

- **Server-side (never trusts the client):** `InviteOwnerController` validates `Rule::in(['owner',
  'manager','accountant'])` and drops owner-only fields for staff; the service assigns only REAL,
  validated catalog roles. An unknown role 422s; an unprovisioned/​unmappable role → clean flash.
- **Verified (browser + tests):** an operator invited a Manager → the email landed in **Mailpit** with
  role-aware copy ("Your Hotel Manager access", no "owner portal") → the invitee set a password → activated
  as **status=active, NOT an Owner, roles=[Hotel Manager]**, landing at `/app/dashboard`. New
  `StaffInviteTest` (5) covers manager + accountant activation (role assigned, no owner artifacts, /app),
  the owner path unchanged (owner + agreement + units, /owner, no staff role), role-key correctness (exact
  catalog names; no bare "Manager"), and the fail-safe (unprovisioned role → clean error, nothing written).
  The two old "manager/account rejected" assertions were updated to "unknown role rejected" (the feature
  changed). **Full suite 217 tests / 3280 assertions green; PHPStan L5 0 errors; Pint clean.**
- **DEFERRED still:** email DELIVERY in production (Mailpit is dev only); password reset; 2FA. Manager/
  Account invites are **no longer deferred** — they are wired.

### 2026-07-13 — Tier 1 sweep · password reset (auth chapter complete)

Real forgot/reset password on Laravel's **Password broker**, styled from "Vyrobel Reset Password" onto the
shared auth tokens (login + set-password language). Same security posture as the rest of auth: **no
enumeration** (`/forgot-password` always returns the identical generic status; **ACTIVE users only** — an
invited-but-not-activated user has no password to reset and uses their invite, a distinction never leaked),
**tokened single-use expiring** links (broker-owned), on reset the **password is hashed**, the
**remember-token rotated** (signs out other sessions), the **session regenerated**, and the user signed in
+ **routed by role** (shared `RedirectsByRole` trait, extracted from `LoginController`). **Rate-limited**
request endpoint; **CSRF** on all forms. Custom email-safe `PasswordResetMail` via
`User::sendPasswordResetNotification` (brand-consistent with the invite email) → lands in **Mailpit** in
dev; **production still needs a real provider**. The login "forgot password?" link is now real (was "Soon").
- **Verified (browser + tests):** request reset for an active user → email in Mailpit → link → new password
  → signed in at `/app`. `PasswordResetTest` (8): emailed to active; no-enumeration (ghost + invited get the
  same generic response, no email); full round-trip (hashed, old fails / new works); single-use; expiring;
  rate-limited; login link enabled. **Suite 225 / 3321 green; PHPStan L5 0; Pint clean.**

### 2026-07-13 — Tier 1 sweep · PRODUCT DECISION: check-out dirties the unit

**Decision:** on **check-OUT**, the departed unit becomes **`dirty`** (it needs cleaning before the next
guest). — **Why:** previously check-out generated the departure-clean *task* but left the unit's
`housekeeping_status` untouched, so the Units screen misreported a just-vacated unit as clean, and a later
"mark clean" was a **clean→clean no-op** (the loop the browser pass flagged). Now the state reflects reality
and a dirty→clean cycle is a **real** transition.
- **How (on the real service/event chain, not inline):** the existing `GenerateDepartureCleanTask` listener
  (the one-directional consumer of `reservation.checked_out` that already creates the departure task) now
  ALSO flips each unit dirty **through `HousekeepingService::markUnitHousekeepingStatus`** — never inline in
  the checkout action. That emits `unit.housekeeping_changed` (a **housekeeping-only** event NO inventory
  consumer reacts to → never re-triggers availability recompute; the consumer discipline holds). **Idempotent:**
  the unit is only dirtied when not already dirty; a double check-out is a no-op (the CheckOut action returns
  early → no second event), so nothing doubles.
- **KPI note:** the dashboard "to clean" (= pending + in_progress **tasks**) and the housekeeping board were
  already correct off the departure task; this fix is about the **unit** status (Units screen) and closing
  the dirty→clean cycle.
- **Verified:** `CheckoutDirtiesUnitTest` (5) — checkout → unit dirty + departure task pending + a
  from-clean→dirty log; the board shows it + the dashboard renders the to-clean KPI; a real dirty→clean cycle
  (from-dirty→clean log, not a no-op); idempotent double-checkout; an already-dirty unit isn't re-dirtied.
  **Suite 230 / 3339 green; PHPStan L5 0; Pint clean.**

### 2026-07-13 — Tier 1 sweep · invite prune + UnitFactory test-infra debt

**Expired-invite prune (Step 3):** `invites:prune-expired` (scheduled daily) → `OwnerInviteService::pruneExpired()`.
CONSERVATIVE — over-cleanup is the only real risk, so every guard errs toward leaving things alone: only
**pending + never-accepted + past-expiry** invites are marked `expired`; the pending owner is **soft-removed**
only if still `inactive` and its login isn't active (`owners.status` is an enum → SoftDeletes, not a relabel);
the invited login is marked `expired` only if still `invited` AND has **no remaining redeemable/accepted
invite anywhere** (a login shared with a live invite in another org is left intact). Runs as an explicit
super-admin platform pass (tenant-safe); idempotent. `PruneExpiredInvitesTest` (7) covers the pruned case
AND every untouched case (accepted/active, still-valid, activated login, cross-org shared login) + idempotency.

**UnitFactory test-infra debt RESOLVED (Step 4):** `UnitFactory` now sets
`organization_id => app(TenantContext::class)->id() ?? Organization::factory()` — respecting the active tenant
context, **consistent with `ChannelFactory`/`OwnerFactory`** — instead of always minting a fresh (mismatched)
org. An explicitly passed `organization_id` still wins (backward compatible). **Blast radius was nil in
practice** (`Unit::factory()` is used only inside `HousekeepingTaskFactory`, which no test exercises — tests
build units via `Unit::create`), so **no test needed adjustment**; the fix is for correctness of any future
in-context factory use. Test-infra only, no app code touched. `UnitFactoryContextTest` (2) proves both the
context-aware default and the explicit-override. **Full suite unchanged at 237 pre-existing + 2 new =
239 / 3372 green; PHPStan L5 0; Pint clean.** (`HousekeepingTaskFactory` carries the same latent pattern but
is out of this scope and currently unused.)

### 2026-07-14 — Demo-readiness polish: room photos + PDF export

Two additive pieces to make the validated core look/feel like a real product. No change to the certified
booking/owner-accounting cores beyond additive wiring; honesty rule held throughout.

**Room photos (Part A):** REUSE the shared polymorphic `media` table (convention #7 — never a parallel
photo table): additive migration adds `collection`/`sort_order`/`is_cover`/`alt`; `HasPhotos` on RoomType
(the sellable entity) gives `photos()` + `coverPhoto()`. Operator upload/reorder/cover/remove via
`RoomTypePhotoController` (the operator-mutation POST→PRG pattern). **SECURITY** (the app's first file
upload, treated as an attack surface): accepts only real images (`image` rule validates actual content +
`mimes:jpeg,jpg,png,webp`, so a disguised `.jpg` is rejected), size-capped (5 MB), **generated ULID
filenames** (user's filename never used → no path traversal), stored on the `public` disk deliberately;
**tenant-scoped** (room-type + photo resolve via string ids → cross-org 404, nothing written). Rendered on
the storefront (cover + gallery) + operator/owner unit cards, with the **decorative gradient kept as the
honest fallback** for no-photo rooms ("Photos soon" removed). **Storage:** local `public` disk in dev
(`storage:link`; generated files gitignored, regenerate on seed); **S3/cloud is the production path
(flagged, not hardcoded).** Demo photos are REAL, locally-generated JPEGs (`Support\Media\PlaceholderImage`
via GD) — never hotlinked or fabricated URLs.

**PDF export (Part B):** `barryvdh/laravel-dompdf` (dompdf) ^3.1 + a thin `PdfService`. Branded, **PDF-safe**
documents (table layout + inline CSS + DejaVu Sans for € / U+2212 — NOT the app's frosted glass). Two
documents, both from REAL data (services called, never modified) with **NO invented lines**:
- **Owner statement** (`OwnerStatementPdfController` + `Support\Pdf\OwnerStatementDocument`) — **owner-scoped**
  (id → `findOrFail` under OwnerScope → cross-owner 404, fail-closed for non-linked); gross → commission →
  fee → expenses → OTA → net, provisional lines flagged, FX only on a real conversion.
- **Operator folio** (`FolioPdfController` + `Support\Pdf\FolioDocument`) — **tenant-scoped** (id → `findOrFail`
  → cross-org 404); the append-only ledger (charges +, settled payments −, refunds their own + line).
- Both **reconcile to the on-screen figures to the cent AND to the character** via a shared
  `Support\Money\MoneyFormatter` (the owner/operator screens now delegate to it) + a shared
  `Support\Folio\FolioTotals` (the folio reconciliation formula, now used by both the screen and the PDF —
  removing the duplication). The fake "card-processing fee" never appears. Wired to the previously-"Soon"
  download affordances; **CSV export + remittance/booking-summary PDFs + folio print stay honestly deferred.**

**Verified:** browser — an operator uploaded a real JPEG (stored as a ULID filename, rendered on the
storefront gallery + unit cards); an owner downloaded `vyrobel-statement-1-2026-09.pdf` showing
€1,080.00 − €194.40 + €0.00 (PROVISIONAL) = €885.60 (matching the screen); an operator downloaded a folio
PDF (Room +€300 / Tax +€69 / Payment −€369 → balance €0.00). Tests: `RoomPhotosTest` (7),
`RoomTypePhotoUploadTest` (10), `OwnerStatementPdfTest` (5), `FolioPdfTest` (5). **Full suite 266 tests /
3461 assertions green; PHPStan L5 0 errors; Pint clean.**

### 2026-07-14 — MAINTENANCE MODULE built & certified (tickets + 2 integrations)

A self-contained backend module that slotted into infrastructure deliberately left ready. Design +
audit: `docs/schema/maintenance.md`. Mostly REUSE — one new table (`maintenance_tickets`).

**Tickets core (Step 1):** `maintenance_tickets` (ULID, `BelongsToOrganization`, SoftDeletes, morph
`maintenance_ticket`) + `MaintenanceService` mirroring `HousekeepingService`: guarded lifecycle
`open → in_progress → resolved`, `open|in_progress → cancelled` (illegal jumps throw; idempotent
re-start/assign). Cost = integer minor units. Post-commit `maintenance_ticket.*` events are one-directional
and NOT availability triggers — a plain ticket never touches sold/availability.

**Integration 1 — out-of-service ↔ availability (Step 2):** a `takes_out_of_service` ticket, on open/start,
flips its unit to `out_of_service` via `MaintenanceService::setUnitCondition` (a real `condition_status`
transition + a `unit_status_logs` type=condition row) which emits **`unit.condition_changed`** over the
room-type's published window. The **EXISTING** `RecomputeAvailabilityOnInventoryChange` listener consumes it
— `out_of_service` is already counted into `out_of_order`, so the unit drops out of availability.
**AvailabilityService and the AssignmentService locking core are NOT modified; `sold` is never touched** —
out-of-service is a unit-level exclusion, exactly like OOO/blocks. Return-to-service on resolve/cancel only
when no OTHER active OOS ticket holds the unit (two-tickets case). An existing booking is **NEVER
auto-cancelled** (a physically-assigned reservation on a now-OOS unit is left intact + flagged for manual
move). If the room-type is already fully sold, the row's raw `total−sold−ooo−blocked` reads **negative**
(honest over-committed signal) while the clamped `available()` stays **0** so new bookings are cleanly
rejected — **no oversell, no Phase-1 change**. The **concurrency suite was re-asserted green** (9/114).

**Integration 2 — cost → owner deductible expense (Step 3):** resolving a costed ticket on an OWNED unit
posts via the **certified** `OwnerExpenseService::recordExpense(category:'maintenance', source:$ticket)` →
owner-from-unit + deductibility from the agreement + the `owner_ledger` deduction line → onto the statement.
**NEVER a direct owner_ledger write.** An **operator-owned unit** (owner_id null) records the cost on the
ticket only — **no owner_expense** (explicit, not dropped). `correctCost(ticket, ?Money)` handles corrections
+ voiding the charge **append-only** — reverses via `voidExpense` (a new reversing line; the original is
never edited) then re-posts. **Period-lock respected** by the certified path — a cost dated in a sealed
period lands as a `prior_period_adjustment` on the next statement. This gives the previously-deferred
`owner_expenses.source` hook its real source (the maintenance ticket).

**Stub (Step 4):** an UNSTYLED operator contract stub — `App\Livewire\MaintenanceList` (`/app/maintenance`) +
`App\Livewire\MaintenanceTicketDetail` (`/app/maintenance/{ticket}`, RMB rule → cross-org 404). The styled
maintenance UI is a later design pass; surfacing the manual-move flag to the operator is future work.
**→ RESOLVED the same day by the MAINTENANCE UI design pass (next entry): both the styled UI and the
manual-move flag surfacing are now DONE and removed from the deferred register.**

**Invariant #22** locked in PROJECT-STATE. **DEFERRED (now resolved — see next entry):** styled maintenance
UI + the manual-move flag surfacing. **Verified:** `MaintenanceTicketTest` (7) · `MaintenanceOutOfServiceTest` (6) ·
`MaintenanceCostExpenseTest` (6) · `MaintenanceModuleTest` (3) + the re-run concurrency suite. **Full suite
288 tests / 3552 assertions green; PHPStan L5 0 errors; Pint clean.** No regression to the booking or
owner-accounting cores.

### 2026-07-14 — MAINTENANCE UI design pass — module now COMPLETE end-to-end (board + work order)

The maintenance module's operator surface, styled onto the SAME operator design system as the other
screens (operator layout, `<x-operator.shell>`, `FormatsOperatorData` — reused, never redefined).
**Presentation + wiring of EXISTING actions only** — no schema/service/availability/cost path changed.

**Board (`/app/maintenance`, `MaintenanceList`):** a kanban whose 3 lanes ARE the real active-status enum
(open/in_progress/resolved — no invented columns; cancelled is terminal → off the board). Cards show real
unit/category/priority/assignee; resolved cards show real cost + date. The **"Unit OOS" badge** shows only
when `takes_out_of_service` AND the unit is genuinely `condition_status = out_of_service`. Summary chips +
the **"Units out of service" rail** are DERIVED from real rows. Two REAL writes via POST →
`MaintenanceActionController` → `MaintenanceService` → PRG: **New ticket** (`open()`, own units only) and
**Assign** (active org member). Foreign unit / cross-org ticket → 404; non-member assign → 422.

**Work order (`/app/maintenance/{ticket}`, `MaintenanceTicketDetail`):** RMB rule kept (string id →
tenant-scoped `findOrFail` → cross-org 404). Real facts + description + an **activity timeline built ONLY
from the ticket's real `audit_events`** (opened/assigned/started/resolved/cancelled — actor + timestamp;
nothing fabricated). **Lifecycle writes** (Start/Resolve-with-optional-cost/Assign/Cancel) each offered ONLY
when the real state allows the transition; the service stays the authority — an illegal transition is caught
and flashed cleanly (**never a 500**). **Both integrations made visible:** (1) an **OOS callout** + the
**manual-move flag SURFACED** — the REAL count of active `reservation_unit` rows on the blocked unit (absent
when zero), linking to the rack (surfaces the previously-deferred flag; the actual move still happens via the
existing move-room/rack path — never auto-cancelled); (2) a **cost card** whose destination is read from the
ACTUAL `owner_expense` (source = ticket): present → "charged to {owner} as a deductible expense"; absent →
"operator cost". Never a fabricated charge.

**Model typing (doc-only, PHPStan L5):** added an `@property` block to `AuditEvent`; `currency`/`timezone`
to `Property`; a `BelongsTo<Property>` generic to `Unit::property()`.

**Demo seed:** `DemoSeeder` now seeds a coherent maintenance spread through `MaintenanceService` (real path,
not raw inserts): an OPEN ticket, an IN-PROGRESS ticket, an OOS ticket on room **101** (which has an active
stay → the work order shows the real "1 booking to move" flag), a RESOLVED **owner-cost** ticket on apartment
**A2** (→ a real deductible `owner_expense` for the demo owner) and a RESOLVED **operator-cost** ticket on
room 103 (→ no owner_expense). All clearly demo, coherent with the existing demo org/units/owner.

**Deferred register:** **REMOVED** "styled maintenance UI" and "the manual-move flag surfacing" (both now
done). **Verified:** `MaintenanceBoardViewTest` (7) + `MaintenanceDetailViewTest` (7); `migrate:fresh --seed`
runs clean with the new maintenance seed. **Full suite 302 tests / 3639 assertions green; PHPStan L5 0
errors; Pint clean.** No regression to the booking, owner-accounting, or maintenance-backend cores.

### 2026-07-14 — UNIFIED INBOX module — CORE built (interface + fake + real; real channels deferred)

A guest-messaging inbox: one place to see + reply to guest messages across channels, threads linked to
real guests/reservations. Built with the **interface + fake + real** pattern proven on Channex/Stripe —
the **internal core + a manual channel** work end-to-end now; the **real external adapters (email/WhatsApp/
OTA)** are config-gated skeletons that **throw "not configured"**, account-gated. Design: `docs/schema/inbox.md`.
**Two new tables; everything else reuses existing infra** (no parallel structures).

**Core (Step 1):** `message_threads` + `messages` (ULID, `BelongsToOrganization`, SoftDeletes, morph
`message_thread`/`message`). The **threading anchor** is `unique(org, channel, external_thread_ref)`; **inbound
idempotency** is `unique(org, channel, external_message_id)` + `createOrFirst` catch-the-race (same discipline
as `webhook_events`). `channel` is a communication-medium ENUM (manual/email/whatsapp/booking_com/airbnb) —
**NOT** a FK to the OTA-distribution `channels` table (a different concept). `InboxService` is the single write
path: `startThread` / `postInbound` (idempotent) / `sendOutbound` (via the resolved channel) /
`assign|close|snooze|reopen` (guarded) / `markRead` / `receiveInbound` (backbone). `App\Messaging` mirrors
`App\Payouts`: a `MessageChannel` interface + `MessageDeliveryResult`/`InboundMessage` DTOs + `ManualChannel`
(REAL) + `DeferredChannel` skeletons (`EmailChannel`/`WhatsAppChannel`/`BookingComChannel`/`AirbnbChannel`
throw) picked by `MessageChannelResolver`. Inbound rides the reliability backbone
(`ProcessInboundMessageJob extends RetryableSyncJob`) — queued/retried/dead-lettered + idempotent at BOTH the
sync-job and message levels. Proven under **true OS-level concurrency** (proc_open barrier, the no-double-booking
harness): two concurrent inbound of the same external id → **exactly one message + one thread**, no 500.

**Race-safety (locked):** `postInbound` runs `createOrFirst` in **AUTOCOMMIT** (not inside an outer
transaction) — the losing INSERT's re-SELECT must see the COMMITTED winner, which a long REPEATABLE-READ
snapshot hides. This matches the Channex webhook leg. The unread bump is an **atomic increment**.

**Linking + threading (Step 2):** guest linking **REUSES the existing matcher** — extracted
`App\Services\GuestMatcher` (match by `external_refs->{key}`, else create), now used by BOTH
`BookingImportService` (behaviour-preserving refactor) and the inbox — **no parallel matcher**. The
external-ref VALUE is a **hash** of the identity (non-PII in `external_refs`); the real address lands in the
guest's **encrypted** email/phone. Threading GROUPS (never a thread-per-message): by the provider thread ref,
else by a non-PII participant-hash anchor `p:{hash(channel|identity)}` so a stranger's messages still thread.
`InboxService::linkReservation` (tenant-scoped) attaches booking context. **GDPR:** `Guest::anonymize()` now
also **redacts the guest's inbox conversation** — scrubs message bodies + sender labels + the thread's
encrypted participant_identity/subject, keeping the skeleton for audit (never soft-delete-and-keep-PII).

**Unstyled stub (Step 3):** operator contract stubs `App\Livewire\InboxList` (`/app/inbox`) +
`App\Livewire\InboxThread` (`/app/inbox/{thread}`, RMB rule → cross-org 404). The reply box is a **REAL**
outbound — POST → `InboxActionController` → `InboxService::sendOutbound` → PRG. On the manual channel it
records **delivered** + clears unread; on a DEFERRED channel it leaves an **honest failed record** + a clean
flash (never a faked send, never a 500). **PII:** the surface shows the guest NAME + conversation (front-desk
precedent); the encrypted `participant_identity`/contact is **never rendered**. The **styled inbox UI is
DEFERRED to a design pass**, and the inbox is **NOT yet in the operator nav** (added with the styled UI).

**Demo seed (Step 4):** `DemoSeeder` seeds three coherent conversations through the REAL `InboxService`:
a pre-arrival question from **Demo Traveller** LINKED to their reservation (+ a delivered operator reply), an
UNLINKED stranger enquiry (matcher creates the guest; threads on the participant anchor), and a RESOLVED
(closed) thread from **Demo Walker** (+ a delivered reply).

**DEFERRED (honest — account-gated, NOT faked):**
- **email** channel — needs an inbound-email provider (Postmark/Mailgun inbound parse) + a verified sending domain.
- **whatsapp** channel — needs WhatsApp Business API approval + a BSP (Twilio/Meta) number.
- **booking_com / airbnb** channels — need OTA messaging API access (Channex messaging or the OTA partner API).
- **Styled inbox UI** — a design pass (the stub is unstyled); the inbox joins the operator nav then.

**Inbox invariants (added to PROJECT-STATE):** inbound exactly-once via `external_message_id`; deferred
channels **honest-fail** (throw + failed record, never faked); `Guest::anonymize()` scrubs message bodies +
participant identity; `participant_identity` is the **encrypted routing anchor** (never rendered, never a
competing copy of a linked guest's PII).

**Verified:** `InboxCoreTest` (12) · `InboxLinkingTest` (9) · `InboxConcurrencyTest` (true-parallel, 5×) ·
`InboxModuleTest` (7 — the HTTP integrity suite). `BookingImportTest` stays green after the GuestMatcher
refactor. `migrate:fresh --seed` runs clean with the inbox seed. **Full suite 331 tests / 3790 assertions
green; PHPStan L5 0 errors; Pint clean.** No regression to any Phase 1/2, owner-accounting, maintenance, or
reliability invariant.

### 2026-07-14 — UNIFIED INBOX UI design pass — module now COMPLETE end-to-end (list + conversation + nav)

The Unified Inbox's operator surface, styled onto the SAME operator design system as the other screens
(operator layout, `<x-operator.shell>`, `FormatsOperatorData` — reused, never redefined). **Presentation +
wiring of EXISTING actions only** — no change to `InboxService`, the schema, the channel interface, the
reliability backbone, or the linking/threading/GDPR logic.

**Thread list (`/app/inbox`, `InboxList`):** a scannable conversation list — each row shows the guest NAME,
the real **channel badge** (keyed to the `message_threads.channel` enum, warm tints + a glyph letter — no
brand green/blue), the real last-message preview (bold when unread), an unread dot + count, status, and the
**linked-reservation tag ("{ref} · {unit}") only when genuinely linked**. Filters (All/Unread/Assigned-to-me/
Closed) + a channel filter + search are **Alpine over the REAL rendered rows**; the summary chips are DERIVED
counts aligned to the tab semantics. `FormatsOperatorData` gained `channelMeta()` (label/glyph/warm-tone per
enum) + `channelIsConnected()`.

**Conversation (`/app/inbox/{thread}`, `InboxThread`):** RMB rule kept (string id → tenant-scoped findOrFail
→ cross-org 404; opening marks read). A real transcript — inbound cream / outbound terracotta bubbles by
`direction`, day dividers, real timestamps + the real outbound **delivery status** (Delivered/Sent, and the
honest **FAILED** for a deferred-channel send — invariant #24). The **reply box sends a real outbound over the
thread's channel** (POST → `InboxActionController` → `sendOutbound` → PRG); the manual channel delivers, a
**DEFERRED channel shows an honest "isn't connected yet" composer** (no Send; a forced POST records an honest
`failed` message + a clean flash — never a fake send, never a 500). **Lifecycle actions** (Assign/Close/Snooze/
Reopen) run through the guarded service, offered per the real state (new controller methods + routes). The
**context sidebar** shows the Guest card (NAME + the CHANNEL only — never raw contact, invariant #25 — with a
**derived "N stays · guest since {year}"** from the guest's real reservations, OMITTED when none — never
hard-coded), the Reservation card (ref/unit/dates/status + a View link) or a real tenant-scoped **"Link to a
reservation"** picker, and the Assigned-to card.

**Nav:** "Inbox" is now a top-level operator nav item (near the top — a daily driver), added with the styled
UI as planned.

**Deferred register:** **REMOVED** "styled inbox UI" and the "not yet in the operator nav" note (both done).
The **real external channels (email/whatsapp/booking_com/airbnb) remain deferred** behind the interface,
account-gated (unchanged — the composer honestly reflects this per channel). **Verified:** `InboxListViewTest`
(3) + `InboxThreadViewTest` (6); `migrate:fresh --seed` renders the demo inbox threads on the styled screens;
browser-verified the reply round-trip (Delivered). **Full suite 340 tests / 3831 assertions green; PHPStan L5
0 errors; Pint clean.** The Unified Inbox module is now **COMPLETE end-to-end (backend + styled UI)**.

### 2026-07-16 — ROADMAP AUDIT + planning docs (analysis only, NO code) — the current planning source of truth

The buildable-in-a-vacuum core being complete, this was the gated ROADMAP-AUDIT-then-fork task named in the
prior consolidation. **Read-only analysis** — no app code, schema, or test touched; the only outputs are three
new versioned docs. Every "built/done" claim was cross-checked against the actual repo (routes, `app/Livewire`,
`app/Http/Controllers`, `app/Services`, `app/Models`, `database/migrations`), trusting the repo over the
catalogue's optimism. **These three docs are now the planning source of truth for what to build next** (read
them before starting any new screen):

- **`docs/ROADMAP.md`** — the full designed-vs-built-vs-remaining map across ~16 modules. Headline: **~26 of
  ~124 designed screens done (~21%) — the operational spine + owner-accounting wedge + public booking write +
  real auth.** Remaining work triaged into three tiers: **Tier 1** (~14 buildable-now *completion* workstreams —
  styled UI over certified backends, only 3 need a backend gate first: Global Search, 2FA, Maintenance
  Settings), **Tier 2** (~6 net-new *speculative* modules with no backend — Guest Portal ~24, Analytics,
  Reviews, Billing, Notifications, Emails), **Tier 3** (~8 *account-gated* items — real inbox channels, Channex
  live, Stripe Connect payouts, Stripe card checkout, production email, live billing). **Key ratio: only ~8 of
  ~90 remaining screens are truly account-gated; the bulk is buildable now** — the constraint is build effort +
  product commitment, not external blockers.
- **`docs/TIER1-SPEC.md`** — build-ready per-screen specs for the Tier 1 completions: for each, the design
  feature inventory + the **repo-verified backend reality per feature** (✅ REAL / 🟨 DERIVABLE / 🟥
  NEEDS-BACKEND / ⬜ DEFER, each naming the real model/service/column/route), the write actions (service method
  named or flagged as new), a strength rating, and a **buildability verdict** (READY / MOSTLY-READY /
  NEEDS-BACKEND-FIRST). Ends with a **5-wave build order** grouped by shared backend. **Honesty caveat recorded
  in the doc:** the design HTML files are minified React SPAs that are opaque to text extraction — the design
  feature lists are catalogue-derived *intent* (confirm exact panels in-browser at build time), while the
  **backend verdicts are rigorously repo-grounded** (the load-bearing part).
- **`docs/DESIGN-COVERAGE.md`** — exists-vs-missing design audit. **Ground truth: `docs/design/raw/` has 125
  files / 124 screens** (the catalogue says 120 — **off by 5**; the extras `Operator Inbox`, `Operator
  Conversation`, `Owner Invite Email`, `Vyrobel Invite Owner`, `Vyrobel Set Password` are now-built inbox/auth
  exports). **Coverage is near-total — only ~3 needed screens lack a design** (Operator Units [already built],
  Password Reset Email [already built], 2FA [needs backend anyway]); **every Tier-2 future module is already
  fully designed.** **Design is NOT the bottleneck for any planned work.** Also surfaced an **un-scoped module**
  in the designs (`Operator Team Schedule` + `Operator Schedule PDF` — staff rostering, no backend, not in the
  ROADMAP) — flagged as a potential Tier-2. **Action noted:** reconcile `docs/design/CATALOGUE.md` to 125/124.

**Decision — NEXT = Wave 1 (the owner-accounting long-tail).** Per TIER1-SPEC's build order, and confirmed by
DESIGN-COVERAGE (**all 10 Wave 1 designs EXIST — zero design gate**): four gated sub-gates over the CERTIFIED
owner-accounting backend, owner/tenant-scoped, honesty rule enforced — (1) Owner Balance · Expenses · Agreement
· Archive; (2) Remittance-Advice + Booking-Summary PDFs; (3) Owner Unit Detail + Operator Owner Detail;
(4) Payout Run + Owner Documents. Stub what the design implies but the backend lacks (ADR/RevPAR charts, CSV
export, owner-editable rates). Then Waves 2–4 (reservations/folio · inbox · rates/channels/settings).
**In parallel, customer validation of the already-demoable product remains the highest-value non-build move**
(the Tier-2 bets are speculative without signal). **Verified at this point (unchanged by the analysis): 340
tests / 3831 assertions green on MySQL, PHPStan L5 0 errors, Pint clean.** The three docs were committed on
branch `docs/roadmap-audit` (7fea230 → ae1541b → 9785561) and **merged to `main`** in this consolidation so a
fresh session on `main` sees them.

### 2026-07-16 — 💰 MONEY FIX SHIPPED: cumulative refunds against a payment are now BOUNDED (invariant #27)

**A live financial bug, found while inventorying Wave 2 — closed before the Wave 2 refund screen builds on
top of it.** `FolioService::refundPayment()` checked the refund amount against the **ORIGINAL** on every
call and **nothing summed prior refunds**, so a payment could be refunded without limit. Proven on a dev DB:
a **€369.00 payment accepted three full refunds = €1,107.00** (each call satisfying `36900 <= 36900`).

**It was reachable in the SHIPPED product, not merely latent.** The original payment stays
`type=payment, status=succeeded` after a refund, `ReservationDetail:171` marked **any** settled payment
`refundable => true`, and the controller re-checked only that the payment was settled — so the Refund button
**persisted after refunding** and a second click refunded again. Wave 2's refund screen would have turned
"click twice" into "refund €50 twenty times".

- **Decision: this is a NEW invariant (#27), NOT a violation of #7.** — **Why:** the distinction is the whole
  lesson. Invariant #7 (append-only, correct-by-reversal) held perfectly throughout: every refund WAS a
  legitimate new offsetting row and the original was never touched. **#7 bounds EDITS; it never bounded the
  SUM.** A guarantee stack can be fully green and still leak money if nobody ever wrote down the bound. #7's
  entry in PROJECT-STATE now cross-references #27 so the two are not conflated again.
- **Decision: the guard lives in `FolioService::refundPayment()`, never per-controller.** — **Why:** it is
  the single write path (audited: exactly ONE production caller — `ReservationFolioActionController:57` —
  plus 3 test callers), so one guard covers every path, including the Wave 2 screen and anything added later.
  Same reasoning as `AssignmentService` being the sole inventory writer.
- **Decision: the guard runs BEFORE the gateway call.** — **Why:** `$this->gateway->refund()` is invoked
  before any row is written. A guard placed after it would move REAL money against a live gateway and only
  then reject. This ordering is load-bearing and is documented in the method.
- **Decision: reject with a domain exception, never clamp.** — **Why:** silently clamping a €6,000 refund to
  the €5,000 remaining would post an amount the operator never authorised. `RefundExceedsPaymentException`
  (extends `RuntimeException`, mirroring `App\Exceptions\Booking\*`) is caught by the controller → a clean
  flash, never a 500.
- **Decision: `withTrashed()` when summing prior refunds.** — **Why:** conservative for a money guard. Posted
  financial rows should never be deleted (#7), so this is belt-and-braces — but a soft-delete must never
  become a way to hand back refundable headroom. Only `succeeded` refunds count: a failed refund moved no
  money and must not consume headroom (locked by a test).

**Two further real bugs found in the same method and fixed here:**
1. **Cross-currency refunds passed.** `refundPayment` never checked that the refund currency matched the
   payment's — it stamped the refund with the **amount's** currency and compared raw minor units across
   currencies. **A 100 USD refund against a 100 EUR payment was accepted.** Now rejected: the cumulative sum
   is only meaningful same-currency, so the check is required for the bound to mean anything.
2. **A €0.00 refund was accepted** — a hole in the first cut of *this* fix, caught by its own test: with
   `remaining = 0` the controller called `refundPayment(…, Money(0))`, and `0 > 0` is false, so it posted a
   junk €0.00 row into an append-only ledger and reported "Refund issued.". Non-positive amounts now rejected.

**PROOF — fail-then-pass, then mutation (a money test that cannot fail is worthless):** `RefundGuardTest`
(10) was written **FIRST** and run against the unguarded code — **7 of 10 FAILED** (the over-refund accepted,
the HTTP path not rejecting, the button persisting). After the fix: **10/10**. Removing the guard again fails
**4**; restoring it passes 10/10. Two of my own tests were also wrong and fixed: one asserted on a route
**name** that never renders (only the URL does) so it passed **vacuously**; another misused Pest's
`toThrow()` second argument as a description when it is a **message matcher** — the same class of trap as
`toContain()` in Wave 1.

**Browser-verified on real demo data:** three refund attempts through the real operator UI on the same
€369.00 payment → **€369.00 refunded, ONE row, zero junk rows, original untouched** ("Refund issued." then
"already fully refunded." twice). Also confirmed live: a legitimate partial refund works (10000 → remaining
23210), €0.00 rejected, cross-currency USD rejected, `remaining+1` rejected, `exactly remaining` accepted →
remaining 0.

**Legitimate refunds unregressed** — the two existing partial-refund proofs (`FolioLedgerTest:23`,
`FolioPdfTest:73`) and `StripePaymentTest` pass untouched; the money/ledger paths (folio · payments · owner
attribution/expenses/statements/payouts · the Wave 1 PDFs) re-assert green at 97 tests / 2516 assertions.
Suite **420 → 430 tests / 4835 assertions**, PHPStan L5 0 errors, Pint clean, `migrate:fresh --seed` clean.

**The lesson worth keeping:** this was found by *inventorying a design*, not by a test failing — the whole
certified suite was green over it. Guarantees only hold where someone wrote the bound down.

---

### 2026-07-16 — ✅ SECURITY FIX SHIPPED: the `/app` authorization hole is CLOSED (route gate + owner-only role)

The finding recorded below is **fixed**, in two gated steps: **`5155a48`** (layer 1, the route gate) →
**`f63866a`** (layer 2, the root cause). **The product is now safe for a real owner login.** Suite
**402/4652 → 420 tests / 4795 assertions**, PHPStan L5 0 errors, Pint clean, `migrate:fresh --seed` clean.
Locked as **invariant #26** in `docs/PROJECT-STATE.md`.

**The audit came first, and it inverted the obvious fix** — which is why it was worth doing before touching
code. The naive move ("gate `/app` on a staff role/permission") would have been **exactly backwards on real
data**: the ONLY role assignment in the entire database was the **OWNER** holding `Property Owner`
(`['*']` — all 44 permissions, including `payments.refund`), while the actual staff user held **none**.
Verified empirically: owner `can('reservations.view')` = **true**, staff = **false**. Shipping the obvious
gate would have locked out staff and admitted the owner.

**Ground truth established (Step 0, read-only):**
- `/app` was `Route::middleware('auth')` and nothing else. The **only** role check in the entire codebase was
  the Horizon gate — zero `@can`/`@role` in any view, zero `authorize()`/`Gate::allows` in any controller or
  Livewire component, zero spatie middleware registered, zero `403`s in `app/`.
- **The owner's Membership is LOAD-BEARING and was never the bug.** `SetTenantForWebRequest` resolves the org
  ONLY from active Memberships; `SetOwnerScopeForPortal`'s `Owner::query()` runs under the tenant guard,
  which THROWS with no context. Remove the Membership → `/owner` 500s on every route. Nothing in
  `OwnerContext`/`OwnerScope` reads Membership at all (owner identity is purely `owners.user_id`). So the
  answer to "(a) tenant-context only / (b) load-bearing / (c) confers a role" is: **(a) by intent, (b) in
  effect, never (c)**. The bug was that `/app` treated "authenticated + has a Membership" as "is staff".
- **The naming trap:** `Property Owner` is the **ORG ADMIN** (`['*']`), *not* the unit owner — that is
  `External Owner` (`owner_portal.view` only, provisioned but assigned by nothing). `DemoSeeder` conflated
  them. **Production was sound** (the invite path gives owners no role, staff a real one); **the DEMO data
  was inverted**.

**The fix — defense-in-depth, two layers, each proven to block INDEPENDENTLY:**
- **Layer 1 — `App\Http\Middleware\EnsureBackOfficeAccess` on the `/app` GROUP.** Requires a role from the new
  `config('authorization.back_office_roles')` **allow-list**, checked **team-scoped**. Allow-list, not
  deny-list: no role, a portal-only role, or a role in a DIFFERENT org all fail **closed**, and it composes
  with tenant isolation instead of bypassing it. Owner-linked → redirected to `/owner` (a legitimate user
  gets a home, not a dead end); everyone else → bare 403. Neither response renders operator data.
  - **Keys on a ROLE, not a permission** — no single catalog permission spans every staff role
    (Housekeeping/Maintenance/Marketing Manager/Accountant share none), verified against the matrix.
  - **On the GROUP, never per-route** — a test asserts EVERY `app/` route carries it, so one added later
    cannot forget it.
  - **Ordering is load-bearing:** it must run AFTER `SetTenantForWebRequest` sets spatie's team id, or
    `hasAnyRole()` matches nothing and every operator is locked out. Neither middleware is in
    `Router::$middlewarePriority`, so both sink to the end in declaration order — asserted on the resolved
    chain so a future priority tweak fails loudly rather than silently.
  - **`Super Admin` deliberately NOT listed** — a global (team-null) role a team-scoped check could never
    match, and currently unassignable anyway (see the defect below). Untestable dead code in a security gate
    is worse than an honest note.
- **Layer 2 — `OwnerInviteService` assigns the owner-only `External Owner`** (`owner_portal.view`, nothing
  else) on the owner path. The allow-list then has **nothing to admit** even on a route that forgets the
  middleware. **The Membership stays** (load-bearing, above).
  - **Deliberately NON-throwing when the role is unprovisioned**, unlike `assignStaffRole()` which throws —
    and the asymmetry is the point. A staff member without their role is BROKEN (locked out of `/app`), so
    that path rolls back. An owner's portal access comes from `owners.user_id`, so an owner with **no** role
    reaches `/owner` identically and is **equally** non-operator-capable. Throwing would invent a new failure
    mode blocking a legitimate owner over an unrelated org-setup gap (nothing provisions roles at org
    creation — see below). It logs and proceeds. A role missing from the CATALOG is a code bug and still throws.

**Demo seeder corrected — the demo must model production, or the gate is not what the demo journeys prove.**
demo-owner held `Property Owner` (all 44 permissions incl. `payments.refund`/`guests.erase`/`users.manage`);
demo-staff held **nothing**. Now: demo-owner → `External Owner`, demo-staff → `Hotel Manager`, via the real
provisioner path; a test locks the model. (`StubViewsTest` test 1 was passing only *because* of that wrong
role — it drove OPERATOR pages as the owner; corrected to act as demo-staff.)

**Fixtures corrected, not the gate weakened.** 87 tests began 403ing because ~19 operator test files built
their staff as *"Membership, no role"* — which was never realistic (a real staff invite assigns a role; an
OWNER also holds a Membership — that was the hole). New shared helpers (`tests/Support/auth_helpers.php`:
`operatorUser` / `grantBackOfficeRole`) model a real operator. Owner, stranger, cross-org and non-member
fixtures were deliberately left role-less so their fail-closed/404/422 assertions still prove what they claim.

**Proof (not just green):** `BackOfficeAccessTest` (10) is **mutation-verified** — with the gate removed 5
fail, restored 10/10 pass. `OwnerNotOperatorCapableTest` (7) proves each layer alone: layer 2 asserts an
activated owner's `hasAnyRole(back_office_roles)` is false **with no HTTP at all** (so it holds for an
ungated route); layer 1 strips layer 2 by granting the owner a back-office role and shows the gate is what
stands at the door — each test justifies the other's existence. Browser-verified on real demo data: as
demo-owner, all six previously-leaking routes (`/app/dashboard`, `/app/owners`, `/app/rack`,
`/app/front-desk`, `/app/inbox`, `/app/payouts/run`) land on `/owner` with zero operator data; the owner
portal is fully intact; as demo-staff, all seven operator routes return 200.

**Two sub-defects surfaced and left open (both FAIL CLOSED — not exploitable; recorded in PROJECT-STATE):**
1. **No app-level org creation provisions roles** — `OrganizationRoleProvisioner` is called only by
   `DemoSeeder` + two tests; there is no `Organization::create` in `app/` at all. Any org born outside the
   seeder would have zero roles and the gate would lock out everyone in it. Moot today; **wire provisioning
   into org creation when that lands.**
2. **`Super Admin` is unassignable → the Horizon gate can never pass** outside `local`.
   `model_has_roles.organization_id` is `NOT NULL` and part of the PRIMARY KEY, so a team-null assignment
   cannot be stored while the team-null lookup filters on `IS NULL`. Fixing it needs a migration against
   spatie's own schema — not to be done casually.

**Also:** `phpunit.xml` now sets `memory_limit=512M`. Verified **headroom, not a leak** (the whole suite
passes under a raised limit): dompdf embeds a full DejaVu font per PDF test and every operator test now
provisions a 13-role × 44-permission catalog. Set there because `php artisan test` spawns a child PHP that
re-reads php.ini, so `-d memory_limit` never reaches it.

---

### 2026-07-16 — 🔴 SECURITY FINDING (now FIXED — see the entry above): the `/app` operator back office has NO role gate

**Found while browser-verifying Wave 1 · Sub-gate 3** (logged in as the demo OWNER, loaded the OPERATOR
screen `/app/owners/{owner}` and it rendered every financial detail). **Not a demo-data artifact — this is
production behaviour.** Two facts combine:

1. **`routes/web.php` — the `/app` group is `Route::middleware('auth')` ONLY.** No role, no permission, no
   policy. Tenant scope is enforced (you only see your OWN org), but *any authenticated member* of that org
   reaches every operator screen.
2. **`OwnerInviteService` grants an ACTIVE `Membership` on BOTH paths** — its own comment says so:
   *"Membership (active so the tenant context resolves once the login is activated) — BOTH paths."* So a
   **real, production-invited OWNER** gets an active operator Membership, `SetTenantForWebRequest` resolves
   their org, and `/app/*` opens.

**Impact:** an owner-role user can browse the entire operator back office of their org — rack, front desk,
housekeeping, maintenance, reservations + folios, the unified inbox (guest conversations), the owners list,
and now `/app/owners/{owner}`: **every other owner's balance, statements, payouts, commission and agreement.**
This defeats owner isolation (invariant #18) at the ROUTE layer — `OwnerScope` is deliberately inactive
off-portal, which is correct for a real operator and catastrophic for an owner who can reach `/app`.

- **Pre-existing, NOT introduced by Wave 1** — `/app/owners` has listed every owner since the Auth Phase B
  work (`2d28b32`), and every operator screen has always shared this group. Wave 1 **deepens what is exposed
  there** (a list of owner names → one owner's complete financials), which is how it surfaced.
- **Decision: NOT fixed inline.** — **Why:** Wave 1 is a gated *presentation* pass ("no guarantee change"),
  and this is a cross-cutting **authorization** decision spanning every operator screen: which of the 14
  catalog roles may reach `/app`; whether an owner should hold a Membership at all (it exists so the tenant
  context resolves — removing it needs a different context path for owners); and how staff-vs-owner is
  decided (spatie role vs `owners.user_id`). Bolting a guess onto one screen would be worse than the honest
  finding: it would imply the surface is protected when it is not.
- **This is the TOP post-Wave-1 priority — it must be closed before ANY real owner login.** Recorded as a
  CRITICAL item in `docs/PROJECT-STATE.md`'s outstanding register. Sketch (needs a decision, not a default):
  gate `/app` on a real staff capability (spatie role/permission from `config/authorization.php`), keep
  `/owner` on `SetOwnerScopeForPortal`, and add a test that an owner-role user gets 403 on every `/app` route.
  The demo seeder gives `demo-owner` a Membership too, so the fix must keep the demo coherent.

---

### 2026-07-16 — WAVE 1 COMPLETE — the owner-accounting styled long-tail (4 gated sub-gates, presentation only)

Wave 1 of the ROADMAP audit's build order, executed as four gated sub-gates with a commit each. **9 new
screens/documents over the CERTIFIED owner-accounting backend. Presentation + wiring of existing services
ONLY — mechanically verified at every gate: ZERO files changed under `app/Services`, `app/Models`,
`database/migrations`, `app/Payouts`, `app/Support/Owner` (services/), `app/Support/Money`.** No schema, no
service, no guarantee touched. Suite **340/3831 → 430/4835**, PHPStan L5 0 errors, Pint clean at every gate.

**What shipped** (all owner screens owner-scoped + fail-closed; operator screens tenant-scoped):
- **Sub-gate 1** (`55c9219`) — **Owner Balance** (real ledger activity + per-row running balance; DERIVED
  net position = Σ `signedMinor`; in-transit/paid from the real payout enum) · **Owner Expenses** (real
  `owner_expenses` incl. maintenance-sourced via the polymorphic `source`; the `deductible` **TRI-STATE**
  true/false/**null** never coerced; voided badge; the statement each hit via
  `attributed_ledger_id → ledger.statement_id`) · **Owner Agreement** (real effective-dated terms,
  display-only, honest nulls) · **Owner Archive** (finalized statements w/ the real gap-free `sequence_no`;
  drafts excluded; DERIVED lifetime/counts/span).
- **Sub-gate 2** (`5530f6b`) — **Owner Remittance Advice PDF** (per payout) + **Owner Booking Summary PDF**
  (the bookings behind a statement's revenue), on the existing `PdfService` + shared `MoneyFormatter`.
- **Sub-gate 3** (`9ff2ad9`) — **Owner Unit Detail** (unit facts, owner-scoped occupancy, attribution
  breakdown, real stays) + **Operator Owner Detail** (`/app/owners/{owner}` — the tenant-scoped lens).
- **Sub-gate 4** (this commit) — **Operator Payout Run** (finalized statements → REAL payouts via the
  certified `OwnerPayoutService`) + **Owner Documents** (aggregation over the real generatable PDFs).

**Three shared extractions (reuse-don't-duplicate; each prevents two surfaces from disagreeing):**
- **`App\Support\Payouts\RailLabel`** — the ONE rail→label map (screen + PDF). An unknown rail **THROWS**
  rather than prettifying a column into a fabricated payment method; PHPStan enforces exhaustiveness.
- **`App\Support\Owner\OwnerOccupancy`** — the ONE owner-scoped occupancy derivation (My Units + Unit
  Detail), extracted behaviour-preservingly from the certified `OwnerMyUnits::occupancy()`. **Keyed on
  PHYSICAL UNIT ID, never room type.** `availabilityRange()`/`OwnerPortalService::occupancy()` are
  room-type-wide, and a room type is shared with hotel rooms and OTHER owners' units — reading occupancy
  that way shows an owner a number driven by units they do not own (a cross-owner leak dressed as a metric).
  Proven live: two units in ONE room type, 3 nights each → the unit page shows 3 nights/10%, not 6/20%.
- **`assertNoRawBlade()`** (tests/Support/owner_helpers.php) — see the Blade bug below.

**Two real bugs found — both invisible to static analysis, both caught by actually running/opening things:**
1. **Raw Blade shipped to the user.** Blade does NOT compile a directive whose `@` follows a word character
   (the `\B` guard that lets an email address render literally), so
   `Totals exclude voided expenses@if ($multiCurrency)…@endif` rendered its **raw source** on the owner
   Expenses page. It renders as ordinary *text*, so the whole suite passed straight over it — only a browser
   caught it. Fixed (ternary), and locked for every owner screen by `assertNoRawBlade()`, which was **proven
   to fail on the reintroduced bug** before being trusted.
2. **A Livewire prop/view-data collision 500'd two screens** (10 failing tests). Livewire injects public
   props into the view scope, so `public string $unit` **shadows** the `'unit' => [...]` array from
   `render()` and `$unit['name']` fatals on a string offset. The `$xxxId` suffix on
   `OwnerStatementView`/`ReservationDetail`/`MaintenanceTicketDetail` is **load-bearing, not styling** —
   documented on both new components.

**Three corrections to the plan, found by building against the real code (recorded so the roadmap stops
repeating them):**
- **ADR/RevPAR are absent from all 10 Wave 1 designs** (verified by grep) — the plan's "stub ADR/RevPAR"
  instruction is moot here; they live in Reports/Insights (Tier 2). CSV export and owner-editable rates
  were real and were deferred.
- **`statement_id + entry_type='room_revenue'` does NOT reconcile to `gross_minor`.**
  `OwnerStatementService::buckets()` routes a room_revenue line into **adjustments**, not gross, when
  `prior_period_adjustment` is true or `service_date < period_start`. The Booking Summary mirrors that real
  partition and discloses prior-period rows as a real count rather than silently dropping them.
- **`owners.user_id !== null` does NOT mean "portal access active".** `OwnerInviteService` links `user_id`
  to an **inactive `'invited'` login at invite-creation time**, so that test would assert access nobody
  accepted. The operator screen renders the truthful state (pending invite + real expiry / genuinely-active
  login / the real `OwnerInvite` enum).

**Honesty rule (invariant #19) — the designs demand a great deal of backend that does not exist.** The
exports were **fully decoded** (they are not opaque — see `docs/design/CATALOGUE.md`), which made the gap
explicit. Standing order for this wave: **omit aggressively** — where a control would imply state with no
schema, it is NOT rendered even disabled (a greyed "Approve" asserts an awaiting-your-decision state that
has no state machine).
- **Disabled "Soon"** (a real thing that isn't wired): withdraw-early, CSV export, repair request, agreement
  download, message-manager, archive ZIP, tax summary, Stripe-Connect disbursement.
- **OMITTED** (would imply absent state): the Available/On-hold/In-transit *clearing* machine · the tax-ID
  hold · the owner **approve/query** workflow · e-signature/amendments · owner-editable pricing (`units` has
  **no rate/fee column at all** — the design's €140/€45 are literals) · day-blocking · Live/Snoozed toggle ·
  calendar sync · reviews (no schema) · cleaning schedule/cleaner ratings · the guest-message composer ·
  rank/"Best performer" · channel mix + per-unit payout timeline (payouts are per-statement/per-owner —
  per-unit would be invented attribution) · the operator Edit-owner modal + its three no-column toggles ·
  Remove owner (the design's own button is inert) · IBAN/bank details (no field exists anywhere) ·
  estimated settlement dates · the "Workspace paused" billing banner (a fabricated system status).
- **Design fiction refused:** fabricated prior-year occupancy (the Unit Detail design invents 2024/2023 as
  ×0.9/×0.8 of this year plus a hard-coded jitter array — *never ship fabricated history*) · "3 years · 34
  documents" (derived instead) · the `OS-/RA-/BS-2025-09` ref formats (the real reference is `sequence_no`) ·
  "Booking.com"/"Airbnb" (**the OTA brand is NOT modelled** — `reservations.source` is a 7-value enum and
  `channels.provider` only has `channex`) · "Direct bookings saved you €X" (`directRev × 0.15`) · the Payout
  Run's non-reconciling constants (its 6 rows sum to 66,000 but it claims 89,990; "11 transfers" vs "12
  statements") and its **zero** interactivity (0 onClick, 0 checkboxes — every button inert) · "Scheduled"/
  "On hold"/"In review"/"Sent"/"Ready" (none are real enum values).
- **Occupancy OMITTED from the Booking Summary PDF** — it is not an attributed value, and the only
  occupancy read available there is room-type-wide (the trap above).
- **PII:** no guest name/email/phone on ANY owner surface (tests assert a distinctive guest name is absent
  from the unit page and the booking PDF); `owners.tax_id` + the raw address JSON are never read on the
  operator screen. The owner's **email/phone ARE** rendered on the operator screen — a deliberate, narrower
  call: that is the OPERATOR's own CRM record for a business counterparty they invited (they typed the
  email), and the Owners list already renders the pending-invite email. The guest-PII rule is unchanged.

**Everything reconciles to the cent, cross-surface** (browser-verified on real demo data): ledger net
`70560` → Balance "€705.60" → the operator's owner-detail balance; statement `gross_minor 108000` →
"€1,080.00" on the Booking Summary PDF and the operator statement row; `net_payable_minor 88560` → "€885.60"
on the Remittance PDF, the pending payout and Balance's in-transit; unit A1 `44280` → "€442.80" = 540.00 −
97.20. Money is per-currency throughout — **never a cross-currency sum** (no silent FX, invariant #17).

---

### 2026-07-17 — WAVE 2 COMPLETE — the operator daily-ops long-tail (5 gated sub-gates)

Wave 2 of the ROADMAP build order, five gated sub-gates with a commit each: **Reservations list ·
Cancelled/No-show terminal states · the Refund screen · the Guests CRM list · the Guest profile.** Suite
**430/4835 → 511/5503**, PHPStan L5 0 errors, Pint clean, `migrate:fresh --seed` clean at every gate.
Browser-verified on real demo data at every gate.

Unlike Wave 1 this was **not** presentation-only: Sub-gate 5 adds the first guest write path (a real
`GuestService`), because the design's "Save changes" persisted nothing and a fake save is not a screen.

**THE SIX LOCKED DECISIONS** (taken before building, because each was ambiguous or self-contradictory in the
designs — the SQL was always trivial; the *definition* was the deliverable):

1. **Lifetime spend = BILLED**, labelled "Total billed", with the real open-folio balance BESIDE it. The
   design's "lifetime value" netted a refund off (only coherent for money COLLECTED) while including €701 of
   unpaid charges (only coherent for money BILLED). **It cannot be both.** → `App\Support\Guest\GuestSpend`.
2. **Status semantics = ONE shared resolver, three screens.** In-house = `checked_in`; Upcoming = `confirmed`
   ∧ future check-in; Past = `checked_out` ∨ window closed. → `App\Support\Reservations\ReservationStage`,
   which holds BOTH the badge (`for()`) and the SQL filter (`scope()`) so a chip cannot drift from the badge
   it filters on. `DUE` exists because the three headline buckets don't cover a came-due-never-checked-in
   arrival; `CANCELLED` is deliberately NOT folded into PAST (a cancelled *future* stay never happened).
3. **Stat tiles = current calendar month, by `check_in`, EXCLUDING cancelled, per currency**, with the scope
   on the face of the tile. Every design figure was a constant that reconciled to nothing.
4. **PII = the Guests CRM only.** Guest email/phone are shown on the CRM list + profile and NOWHERE else;
   front desk, reservations and inbox stay name-only. **Enforced by a test that asserts the perimeter from
   the outside, and mutation-proven** (leaking an email onto the reservations list fails it).
5. **Fee engines DEFERRED** — a cancellation-policy subsystem, not a screen. Never compute or fake a fee.
6. **Property Switcher DEFERRED** — no active-property concept exists; solve it once, globally.

**THE SHARED EXTRACTIONS** (the anti-drift discipline — two `match`es for one concept WILL disagree):
- **`Support\Reservations\ReservationStage`** — the stage vocabulary; badge + SQL in one class. Gained
  `mostSignificant()` (which ONE stage speaks for a guest with several stays) + `CANCELLED_STATUSES`.
- **`Support\Guest\GuestSpend`** — the BILLED definition. Reconciles to the ledger **by construction**:
  `FolioService::recompute()` writes the cached folio columns FROM the same append-only rows `FolioTotals`
  reads, and `GuestSpendTest` asserts the identity folio-by-folio so a future drift fails loudly.
- **`Support\Guest\GuestHistory`** — stays · nights · "guest since". **Extracted because three screens were
  about to disagree and one had already shipped**: `InboxThread`'s guest card counted EVERY reservation
  (cancellations included) and dated "guest since" from the earliest of them. Proven live: for a guest with
  one real 2026 stay and one cancelled 2024 booking the inbox said *"2 stays · guest since 2024"* while the
  CRM said *"1 stay · guest since 2026"* — both sincere, on both numbers. The inbox now reads the shared rule.
- **`Guest::reservations()`** — the approved shallow relation, on `lead_guest_id` (the FK every built surface
  already treats as "the guest of this booking"). The `reservation_guests` pivot's fellow-travellers are
  deliberately excluded, or "this guest's reservations" would mean two things on two screens.

**REAL BACKEND ADDED (Sub-gate 5 only)** — `App\Services\GuestService`, the single guest write path:
profile edit · notes · tags. `docs/WAVE2-INVENTORY.md` §A9 proposed "plain Eloquent + a controller"; that was
**wrong against this codebase's conventions** — CLAUDE.md requires an `audit_events` + `domain_events` row per
state change, and every writer records those INSIDE the service (no controller in the repo calls a recorder).

- **⚠️ THE AUDIT TRAIL MUST NOT BECOME A SECOND, UNENCRYPTED PII STORE.** `guests.email`/`phone` are encrypted
  casts; `audit_events.before`/`after` are plain JSON that `anonymize()` does not clear. Recording the old/new
  email there would re-retain the exact PII an erase destroys and silently undo encryption-at-rest. The audit
  therefore records **which fields changed, never their values** — enough to answer "who touched this guest's
  email on Tuesday?", never enough to reconstruct it. (`Guest::anonymize()` already stated this rule for
  itself; it holds for every write.) Asserted by test, verified in the browser.
- **`'note' => Note::class` added to the morph map** — a live tripwire, proven not assumed:
  `(new Note)->getMorphClass()` THREW `ClassMorphViolationException` under `enforceMorphMap`, and both
  recorders call `getMorphClass()` on the subject. Latent only because Note had always been the morph CHILD.
- Tags are **created on first use** (`firstOrCreate`, tenant-scoped): `tags` has been real since Phase 1 and
  **nothing had ever written one**, so a pick-list would be empty in every tenant forever. `unique(org, name)`
  makes a name the identity within a tenant, so create-on-use is the intended shape, not a duplicate factory.
  Detaching keeps the catalogue — it is the workspace's vocabulary, not one guest's property.

**A DELIBERATE DEVIATION FROM "LOG, DON'T FIX" — `Guest::anonymize()` now redacts NOTE BODIES.** The register
below logs GDPR gaps rather than fixing them, and that was right while notes had **zero writers anywhere** —
the gap was unreachable. Sub-gate 5 builds the writer, which promotes it to **live**: operators type
identifying detail into `notes.body` ("travelling with X"), and `anonymize()` already redacts inbox message
bodies for exactly this reason. Shipping the writer without the erase would have broken CLAUDE.md's PII
invariant **with brand-new code**. Three lines, inside the existing transaction, mirroring the message
redaction; the row + author + timestamp survive (never soft-delete-and-keep-PII). The *other* gap —
`anonymize()` has no UI trigger — remains logged.

**Money reconciles to the cent, cross-surface** (browser-verified on real demo data): every guest's rendered
"Total billed"/"Open balance" equals `FolioTotals`' independent read of the append-only rows — Booker €246.00,
Resident €664.20, Traveller €369.00, Walker €664.20 with €332.10 due. The profile and the list show the same
figures **because they read the same helper**, and €369.00 ÷ 3 nights = €123.00/night.

**OMITTED, not faked** (the standing order is OMIT AGGRESSIVELY — each verified absent, not greyed out): the
whole **"Latest review" panel** (~15% of the profile — there is no reviews table, model or migration
anywhere); the **paused-billing banner** (rejected by name in Wave 1, not reopened); the **localStorage refund
netting** and its non-reconciling "€978 open folio" (both branches hard-coded; 978 only holds if the refund is
exactly €277); **"Dublin, Ireland"** + the City input (no residence column — `nationality` is char(2), a
NATIONALITY); **"Prefers email"** (no `preferred_channel`; `language` is a locale); the **8 hard-coded
preference chips** (a designer's guess in the design's own script → the tenant's real tag catalogue); the
**bulk bar, its Send/Delete modals and the CSV export** (a guest is erased via `anonymize()`, never deleted —
a Delete button would keep the PII while looking like an erase); **pagination + sort** (neither has a handler
in the design); the **"+4%" delta** and the 248/38/31% constants (no historical snapshot exists to compute a
trend against); and the **"Shown to housekeeping & front desk" / "Flags arrivals" claims — verified FALSE**
(nothing outside the model reads `is_vip`, and neither surface reads tags). **Marketing consent is shown as a
RECORD, not a promise**: the column is real and this page is its first reader anywhere, but there is no
sending mechanism, so the caption says exactly that.

**The design's vocabulary was wrong and is not used:** "Completed" is not in the status enum (it means
`checked_out`); "In house" is the STAGE of a `checked_in` row; **"Booking.com"/"Expedia"/"Airbnb" are not
modelled at all** (`source` is direct/walk_in/ota/corporate/group/agent/manual with no brand column) — an OTA
booking reads "OTA". (Don't be fooled by `message_threads.channel`, which *does* carry `booking_com`/`airbnb`
— that is the messaging medium, a different enum for a different concept.)

**Also closed in Wave 2:** the **partial-refund CONTROLLER gap** — `FolioService::refundPayment()` always took
an arbitrary `Money`, so partial refunds were never a backend gap; the controller just never sent one. It is
now amount-aware (no `amount` → the full REMAINING, preserving the inline button), rejects €0 **before** the
service (an `InvalidArgumentException` that is not caught would have 500'd), and surfaces an over-refund
rejection as a clean flash that is **never silently clamped** — refunding less than was asked for without
saying so is its own money bug.

---

### 2026-07-17 — WAVE 2 OUTSTANDING REGISTER — four items found while building, deliberately NOT fixed

Each was surfaced by Wave 2, verified empirically, and sequenced AFTER it by explicit decision. Recorded here
so none is silently dropped. **None is a regression; all four predate Wave 2.**

1. **`AssignmentService::terminate()` has NO internal status guard.** Proven: re-cancelling an already-
   cancelled reservation overwrites `cancelled_at`/`cancellation_reason` and writes a junk `cancelled →
   cancelled` status-history row. Wave 2 guards it at the CONTROLLER (`ReservationLifecycleController`'s
   public `CANCELLABLE`/`NO_SHOWABLE` allow-lists, which the component also gates its buttons off), so the
   shipped UI cannot reach it — but **the service is the single write path and must guard itself**
   (defense-in-depth, the same shape as the #27 refund bound). My own brief asserted "both throw for an
   illegal transition"; the code disagreed.
2. **Two DEAD enum values** — `payments.payment_status = 'refunded'` and `folios.status = 'closed'` are in the
   schema and **never written by any code** (`FolioService::paymentStatus()` only ever writes
   paid/partial/unpaid). The designs assume states the code never produces, so a UI keying off either would
   render **never, silently**. Either write them or drop them; until then, no surface may depend on them.
3. **🔴 THE UNENFORCED PERMISSION CATALOG.** `payments.refund` is held by Super Admin / Property Owner /
   Group Admin / Accountant and NOT by Hotel Manager / Front Desk — **and no route enforces any permission.**
   The coarse #26 staff/owner gate is the only real authorization: any back-office role can do anything a
   back-office role can do. Lower blast-radius than the `/app` hole (that leaked *other owners' financials* to
   an owner; this is staff-vs-staff within one tenant) but it is a real security follow-up, and the catalog
   currently **describes a policy the app does not enforce**.
4. **Two GDPR gaps** — (a) `Guest::anonymize()` has **no UI trigger**: the erase is correct and tested but can
   only be invoked from code, so a real erasure request cannot be served by an operator. (b) *(CLOSED
   2026-07-17 — see the entry above: note bodies are now redacted, because Wave 2 shipped the first note
   writer and would otherwise have opened the hole itself.)*

Plus, from the same audit and still open: **Super Admin is unassignable** (pivot PK) and **no org-creation
path provisions roles** — both fail closed, so neither is exploitable.

---

### 2026-07-17 — TOTAL-RECALL CONSOLIDATION (docs only, no code) — the arc verified, four corrections recorded

A full context consolidation so a fresh session can resume with complete understanding. **Documentation only
— zero code changed.** Everything below was verified against the live repo, not against a brief.

**GROUND TRUTH at consolidation:** HEAD `e160eeb` on `main`, local == origin (pushed), working tree clean.
**511 tests / 5503 assertions green** on genuine MySQL 8 (`vyrobel_test`), **PHPStan (Larastan) L5 — 0
errors**, **Pint clean**, `migrate:fresh --seed` clean, queue = Redis/Horizon live. `docs/design/raw/*.html`
(125 files) remains **gitignored** — a fresh clone has the versioned `.md` specs, not the designs themselves.

**THE ARC IS COMPLETE.** All 48 dated entries *preceding this one* verified present and chronological (a `grep -c '^### 20'` returns a higher number — it counts this entry and the ones appended after it; same self-reference class as the HEAD convention): Phase 1 (Step 0 + Steps 1–8
close-out addenda: tenancy/RBAC · property/inventory · rates/availability · guests/encrypted-PII ·
reservations + AssignmentService · folios + FolioService · housekeeping) → Phase 2 (Steps 0–9, CERTIFIED;
Channex, the sync-reliability backbone, Stripe, public booking, staff layer) → the Stripe live-test-mode
validation → Owner Accounting (Steps 0–7, CERTIFIED) → the design pass + browser-verification → Auth A/B +
Mailpit + staff invites + password reset → the Tier 1 sweep → demo-readiness (photos + PDFs) → Maintenance
(built + UI) → Unified Inbox (core + UI) → the ROADMAP planning docs → 💰 the refund bound (#27) → ✅ the
`/app` gate (#26) + its finding → Wave 1 → Wave 2 + its register. **Nothing was missing; nothing was
rewritten** (this log is append-only, and its dated entries are a ledger).

**FOUR CORRECTIONS — the consolidation brief was wrong on each, and the repo won (meta-lesson (a) again):**
1. **Invariants are #1–28, not #1–27.** The brief asked for "#1–27" five times. **#28 (the guest-PII
   perimeter) was locked in Wave 2 the same day** and is real, tested and mutation-proven. Recorded as 28.
2. **The permission catalog holds 39 distinct permissions across 14 roles (11 back-office), not 44.** Counted
   from `config/authorization.php`. The *finding* is unchanged and confirmed by a fresh grep: **not one is
   checked anywhere** (`can:` / `->can(` / `authorize(` / `permission:` return zero hits across
   `routes/web.php`, `app/Http/Controllers/`, `app/Http/Middleware/` outside `EnsureBackOfficeAccess`, which
   checks ROLE, not permission).
3. **~42 of ~124 screens are built (~34%), not ~26 (~21%)** — the brief quoted the pre-Wave-1 figure. The
   ROADMAP was recounted row-by-row against real routes. ⚠️ It is a **column sum with one cross-listed row**
   (`Owner Invite Email` is Done under both Auth and Emails), so **distinct = 41**; the tilde is load-bearing.
4. **Wave 1 shipped 10 screens/documents, not 9** — an arithmetic slip in the original prose; the Wave 1
   tables always listed 10. Corrected in `docs/PROJECT-STATE.md` with the correction noted inline.

Also: the brief said to "reconcile the catalogue count to 125/124 **if not done**" — **it was already done**
(2026-07-16). The honest remainder is that those five screens are still absent from the catalogue's
*per-screen tables*, so a reader looking up "Operator Inbox" still will not find it. And the brief stated
that "cold emails + a demo script were prepared" — **no such artefact exists in this repo.** Not recorded as
fact; if it exists it lives outside version control.

**🔴 A NEW OUTSTANDING ITEM FOUND BY THIS AUDIT — ONE UNIDENTIFIED FLAKY TEST.** Of six consecutive full
runs, five were 511/511 green and **one reported 510 passed / 1 failed**. The failure was not captured (the
run printed only counts) and never reproduced: five further full runs, plus six targeted runs of all four
true-OS-concurrency suites (the prime suspects — they race real processes), were all green. **The 511/5503
headline is real and reproducible; the suite is not 100% reliable run-to-run.** This matters because the
suite is a real gate — a gate that blinks 1-in-6 for unknown reasons will one day hide a regression. Logged
in the register with the instruction to CAPTURE the failure next time rather than re-run.

**ONE ASYMMETRY IN THE LOG, recorded rather than papered over:** Phase 2 has an explicit "PHASE 2 CERTIFIED"
entry and Owner Accounting has "MODULE CERTIFIED" — **Phase 1 has neither.** Its Step 0–8 decisions ARE fully
recorded (2026-07-02), and its certification is asserted in `PROJECT-STATE.md`/`CLAUDE.md` and backed by the
live suite (the double-booking and tenant-isolation gates pass today). No retroactive certification stamp was
back-dated into the ledger — that would be inventing a gate nobody held. Noted here as the honest record.

**Also consolidated:** the two per-wave "lessons" lists were promoted into a durable **META-LESSONS** section
in `PROJECT-STATE.md` (a brief is a hypothesis / green ≠ sufficient / adversarially re-read money+auth /
extract-and-prove-shared / browser-verify / distrust the docs too / ship the erase with the writer), and a
58-line stale "(historical) NEXT ACTION — WAVE 1" block was collapsed — it still read as pending and
contradicted the live next action by two waves.

**NEXT ACTION is unchanged and stated in `PROJECT-STATE.md`:** clear the 🔴 register (the `terminate()`
service-level guard + enforcing the permission catalog) BEFORE Wave 3 — they are real gaps, not polish. Then
Wave 3 (owner inbox) / Wave 4. **In parallel: customer validation. No customer has ever seen this product.**

---

### 2026-07-17 — ⚠️ CORRECTING THE CORRECTION: "44 permissions" was RIGHT; my "39" was wrong

**Reverting forward** (this log is append-only — the entry above stays as written, and this one supersedes
its correction #2). Found by sending three blind readers at the finished docs with instructions to disprove
them; nothing in my own review caught it.

**What I got wrong.** The consolidation entry above "corrected" a brief's claim of **44 permissions** to
**39**, citing the repo — and then quoted that as evidence in META-LESSON (a) that briefs lie. **The brief was
right. The correction was wrong.** Verified:

```
config('authorization.permissions')                      → 44   ← THE CATALOG. Quote this number.
union of permissions NAMED across role definitions       → 39   ← a DIFFERENT question
in the catalog, reachable ONLY via the ['*'] wildcard    →  5   ← organizations.manage, billing.manage,
                                                                    roles.manage, settings.manage, api.manage
```
`OrganizationRoleProvisioner` expands `['*']` to `config('authorization.permissions')` — **all 44** — so
`Property Owner` genuinely holds all 44, and the pre-existing "13 roles × 44 permissions" notes in
`PROJECT-STATE.md` and `tests/Support/auth_helpers.php` were **correct all along**. My script measured the
union across roles and I read its answer as a refutation of a claim it was not measuring.

**Why this is worth a whole entry rather than a quiet edit.** It is the exact failure the same pass warned
about, committed in the same pass: the false number was written *into the prose of security invariant #26*,
and then generalized into a durable "lesson" — so a future session would have inherited both the wrong figure
and a wrong reason to trust it, with the file's own authority behind them. **A measurement that disagrees
with a claim has not refuted it until you have checked that you measured the same thing.** META-LESSON (a)
now carries that corollary, and (f) now names this incident.

**Corrected in place** in `docs/PROJECT-STATE.md` (a snapshot, not a ledger — it should state today's truth):
the register now quotes **44 (catalog) / 14 roles = 13 per-org + 1 global Super Admin / 11 back-office**, and
states both metrics side by side so nobody "recounts" and flips it a third time.

**The security FINDING is untouched and re-confirmed:** **not one permission is enforced anywhere** — zero
hits for `can:` / `->can(` / `authorize(` / `permission:` across `routes/web.php`, `app/Http/Controllers/`,
`app/Http/Middleware/` outside `EnsureBackOfficeAccess`, which checks a ROLE. The count was never the point;
the enforcement gap is.

**Same audit, other real fixes** (all verified, all now corrected in `CLAUDE.md`/`PROJECT-STATE.md`):
- **`E2E: Playwright` was in the LOCKED STACK and Playwright is not installed** — not in `package.json`, no
  config, zero tracked specs. Browser verification is real but is done via the **Playwright MCP server**
  against a live `php artisan serve`; every artefact it produces is gitignored. It is therefore **not part of
  the runnable regression net** and no longer listed as if it were.
- **`PHP 8.2+` in the locked stack; `composer.json` requires `^8.3`** — 8.2 cannot install this project.
- **`docs/prompts/phase-1.md` was billed as "the current build prompt"** — Phase 1 certified long ago.
- **The demo had no documented way in.** The docs' headline is "COMPLETE & DEMOABLE" and their top priority is
  customer validation, yet the credentials existed only inside `DemoSeeder.php`. Now documented — including
  that `demo-owner` is *refused* from `/app` **by design** (invariant #26), which a fresh session would
  otherwise read as a bug.
- **The dead-enum register named the wrong table and undercounted.** It is `reservations.payment_status`
  (not `payments.`), and there are **at least five** dead values, not two.
- **~10 tables are schema-only** (plans/subscriptions/usage_counters/api_keys/webhooks/groups/…): models and
  migrations exist, nothing wires them. The register said billing has "NO backend today", which invites
  exactly the parallel-structure duplication CLAUDE.md forbids.

---

### 2026-07-17 — 🔐 THE SECURITY/INTEGRITY REGISTER CLEARED — three gated fixes before Wave 3

Done deliberately **before** more screens, so Wave 3/4 inherit correct enforcement instead of a retrofit.
Three independent fixes, a gate each, every guard mutation-proven in both directions. Suite **511/5503 →
543/5646**; PHPStan L5, Pint clean; `migrate:fresh --seed` clean; all three browser-verified together.

**⚠️ EVERY ONE OF THESE WAS WORSE THAN THE REGISTER SAID, AND THE AUDIT IS WHY WE KNOW.**

---

**1. ✅ `AssignmentService::terminate()` — the TERMINATION BOUND (invariant #29). `f4a8913`.**

The register said: guarded only at the controller, so a *future* caller could reopen it. **The audit found
there were already TWO callers and they DISAGREED.** `BookingImportService::cancel()` — the OTA/Channex
**inbound webhook** path, i.e. a network-triggered, RETRYING caller — had always reached
`cancelReservation()`, carrying its own guard that checked only `['cancelled','no_show']`. So **an OTA
cancelling a stay the guest had already COMPLETED (`checked_out`) went straight through and stamped
`cancelled_at` onto it**, while the operator controller refused exactly that transition. Two rules, one
concept, silently disagreeing on a network-driven path.

Proven on real demo data before the fix (run, not asserted):
```
1st cancel : reason="Guest called - family emergency"  at=10:17:04  historyRows=2
2nd cancel : reason="oops duplicate webhook"           at=10:17:05  historyRows=3
→ original REASON destroyed · cancelled_at OVERWRITTEN · junk `cancelled -> cancelled` row appended
```

**IT IS #27'S SHAPE AGAIN: a certified service missing a bound.** #7 (append-only) was never violated — every
row a second termination wrote was a legitimate append. **#7 bounds EDITS; nothing bounded a SECOND
TERMINATION**, which is a REWRITE: it destroys the provenance the detail screen renders from those very
columns and re-emits `reservation.cancelled` to every consumer.

- `AssignmentService::TERMINAL_STATUSES = ['cancelled','no_show','checked_out']` — **the ONE definition**.
- The guard sits in the **private** `terminate()`, **before** the transaction (a rejected call rolls nothing
  back and touches no inventory). Both public entries funnel through it → one check, every caller.
- `ReservationAlreadyTerminalException extends BookingException` (→ RuntimeException) so the controller's
  existing catch renders a clean flash, never a 500.
- **The OTA path NO-OPS rather than throws** — it reads the shared constant and returns before asking. On a
  retrying webhook, letting a terminal booking reach the guarded service would turn an ordinary duplicate
  delivery into a **poison message**. Idempotent at the edge, authoritative in the service.
- The controller keeps its allow-list (defense-in-depth, the #26 shape), and a test asserts `CANCELLABLE` is
  the exact **complement** of `TERMINAL_STATUSES` over the real 7-value enum — two constants in two files is
  how drift starts.
- **Mutation-proven:** 5 failed + 1 error pre-guard → 13/13 → neutered → 5 fail again.

---

**2. ✅ THE PERMISSION CATALOG IS NOW ENFORCED (invariant #30). `9123ee7`.**

The catalog was **decorative**: 44 permissions, 14 roles, and **not one checked at any route, middleware or
controller** — the coarse #26 door admitted all 11 back-office roles EQUALLY.

**🔑 THE HEADLINE WAS NOT THE REFUND — IT WAS PRIVILEGE ESCALATION, and gating the refund alone would have
been COSMETIC.** `POST /app/owners/invite` assigns a **real spatie role** and was gated only by that door.
Proven end-to-end before the fix:
```
Housekeeping (units.view, housekeeping.view, housekeeping.manage — cannot refund, cannot manage users)
  → POST /app/owners/invite {role: accountant, email: attacker@controlled.test}
  → ✗ INVITE MINTED role=accountant status=pending
  → Accountant holds payments.refund => true      ← two requests, no operator involved
```
**Never gate a money route without gating the role-granting route in the same change** — the attacker simply
invites themselves the role.

**ENFORCED** — `can:<permission>` route middleware on: `owners.invite → users.manage` ·
`reservations.refund` **+ its GET screen** `→ payments.refund` · `reservations.payment → payments.record` ·
`payouts.create|pay → payouts.manage` · `reservations.cancel|no-show → reservations.cancel`.

- **`payouts.manage` DID NOT EXIST** (catalog 44 → **45**). The payout run had always moved real money with no
  permission to check against. Property Owner*/Group Admin/Accountant — **not** Hotel Manager: remitting an
  owner is an accounting act, same logic as `payments.refund`.
- **⚠️ AUTHORIZATION GOES ON THE ROUTE, NOT THE SERVICE — the deliberate exception to #27/#29's rule.** A
  permission is a REQUEST fact about an ACTOR; a bound is a DATA fact about a ROW. `FolioService::refundPayment()`
  is called by tests and jobs with **no authenticated user**. Do not "unify" them.
- Ordering verified: `Authorize` runs **last** — after #26, after `SetTenantForWebRequest` sets the spatie
  team — and survives Livewire updates. ⚠️ **A null team fails silently to `false`.**
- **THE UI NEVER INVITES A 403** (#27's principle, applied to authorization): the refund button + partial link
  + screen, Record-a-payment, cancel/no-show, every payout control, and the **whole invite modal** (not just
  its button) are `@can`-gated.
- **FIX FIXTURES, NEVER THE CHECK** (the #26 lesson): 28 tests / 7 files moved to correctly-permissioned
  roles. `StaffInviteTest` got a **targeted** `givePermissionTo('users.manage')` rather than a role swap —
  a swap would have destroyed the deliberate non-provisioning its fail-safe test exists to prove.
  ⚠️ **Cross-org isolation tests keep a PERMITTED actor**, or `can:` 403s before the scoped `findOrFail` and
  the `assertNotFound()` silently stops proving tenancy.
- **THE DECISION BEHIND IT** (locked with the user): the catalog is RIGHT — a Hotel Manager cannot hand money
  back; that is a normal, deliberate control. So the demo gains a **second and third account** and
  *demonstrates* the separation instead of hitting a wall.
- **Mutation-proven:** 6 failed pre-enforcement → 10/10 → strip every `can:` → 5 fail again.

**THE FOUR DEMO ACCOUNTS NOW MIRROR PRODUCTION RBAC** (and together they demo the whole story, rather than one
all-powerful login — the inverted-seeder mistake #26 found, in reverse):
```
demo-admin@…      Property Owner  → org admin: invites staff/owners; can do anything
demo-accountant@… Accountant      → refunds + runs the payout run
demo-staff@…      Hotel Manager   → runs the house; NO refund, NO payout, NO invite
demo-owner@…      External Owner  → /owner only; refused from /app entirely (#26)
```
⚠️ `demo-admin` was added because enforcing `users.manage` otherwise left the **invite flow undemoable** —
`demo-staff` could invite before *only because nothing checked*. Caught by seeding and looking, not by a test.

---

**3. ✅ GDPR ERASURE HAS A TRIGGER. `94ef7ad`.**

⚠️ **The reframe: `Guest::anonymize()` was called from NOWHERE** — no route, no controller, no component;
every hit outside the model was a comment or a test. The erase was correct, thorough and tested, and **no
operator could run it**, so a data-subject request could not actually be served. This BUILT the feature.

- `POST /app/guests/{guest}/anonymize` → controller → **the existing `anonymize()`** → PRG. It re-implements
  **no** redaction: that method already nulls the columns, hard-deletes ID documents *and their bytes*,
  redacts inbox message bodies + the thread's participant identity, and redacts note bodies. **A second copy
  would drift and quietly miss a store — which for an erasure means keeping data you said you destroyed.**
- **`can:guests.erase`** (Property Owner / Group Admin only): running the house does not include erasing a
  person. Route AND button gated.
- **NOT ONE-CLICK, AND THE SERVER CHECKS:** a typed `ERASE` validated server-side; the `confirm()` dialog is
  only a softer second layer, because **intent must survive a bookmarked POST**.
- Asserted end-to-end: raw guest row carries no name/phone; note body `''`; every inbox message body `''`;
  thread `participant_identity` null — while **€300.00 of billed history still reconciles** and the row is
  neither deleted nor soft-deleted.
- **Mutation-proven:** 8 failed pre-build → 9/9 → strip `can:guests.erase` → the permission test fails;
  neuter the typed-confirmation rule → the not-one-click test fails.

---

**4. ✅ THE FLAKY TEST — root-caused by CAPTURING it, not re-running.**

`OwnerStatementPdfTest::"renders only real ledger lines"` asserted `toContain($owner->name)` — the **raw**
faker name — against **Blade-rendered (escaped) HTML**. `OwnerFactory` uses `faker->name()`, and **1.68% of
faker names contain an apostrophe** ("Abelardo O'Conner" → `O&#039;Conner`), so ~**1 run in 6** failed on a
page that was **perfectly correct**. A TEST bug, never a product bug. Fixed to `e($owner->name)` and **proven
deterministically** by forcing a real apostrophe-bearing name: old assertion fails, new one passes.

**Two lessons worth more than the fix:** the four true-OS-concurrency suites were the obvious suspects and
were **innocent** (6/6 green under targeted runs) — the answer only came from capturing the failure
(`php artisan test > run.json 2>&1`). And it is a clean case of testing **the needle rather than the
behaviour**.

---

**STILL OPEN (kept in the register, none blocking):** the **dead enum values** (≥5, not 2 — a status-keyed UI
renders never, silently); **no inbox permission exists in the catalog**, so the inbox's six writes — including
a guest-facing reply — remain #26-only (**logged, not invented**: naming it is a product decision about who
may speak to a guest, and it matters directly to Wave 3); and the two fail-closed RBAC defects (Super Admin
unassignable; no org-creation path provisions roles).

**WHAT THIS BUYS THE NEXT BUILD:** the enforcement layer is now correct and patterned, so a Wave 3/4 write
route carries its `can:<permission>` and `@can`-gates its control **by following the established, tested
shape** — rather than shipping unguarded and being retrofitted later. That was the entire point of doing this
before the screens.

---

### 2026-07-17 — WAVE 3 COMPLETE — the OWNER INBOX (owner ↔ operator staff), over the existing InboxService

The owner portal's Messages screen, on the certified Unified Inbox backend. The `messages` nav slot the shell
had reserved as an honest "Soon" is now a real route. Suite **543/5646 → 556/5733**; PHPStan L5, Pint clean;
`migrate:fresh --seed` clean; browser-verified end-to-end, both directions.

**🔴 THE STEP-0 AUDIT CHANGED THE SHAPE — this was NOT presentation-only.** `docs/TIER1-SPEC.md` rated it
**READY** and `docs/ROADMAP.md` called it 🟢 **M**; both mistook a UI-shape match for a backend match. The
truth: `OwnerScope` keys on `owner_id`, `message_threads` had no such column, so `BelongsToOwner` **could not
be applied** — the scope did not "fail closed", **it never ran**. An owner inbox over the model as it stood
would have been tenant-scoped only: **every guest conversation and every other owner's threads, visible to
any owner.** Invariant #26's exact shape. The column landed before a line of the screen.

**THE MINIMAL BACKEND** (confirmed at the gate; nothing else was added):
- **`message_threads.owner_id`** (nullable FK → owners, nullOnDelete) + **`BelongsToOwner` on MessageThread**.
  **`guest_id` XOR `owner_id` IS the discriminator** — deliberately NOT a `participant_type` enum, which
  would be a third source of truth able to disagree with the two FKs (the drift `RailLabel`/`OwnerOccupancy`
  were extracted to prevent).
- **`message_threads.owner_last_read_at`** — because `unread_count` is the **OPERATOR's** ("inbound since
  THEY last read") and `markRead()` zeroes it. An owner-side read touching it would **silently clear the
  operator's badge for work they have not seen**. Hence `InboxService::markReadByOwner()`, which touches only
  the timestamp. The owner's unread = staff replies after it.
- **`InboxService::postOwnerMessage()`** — extends, never reimplements. **Not `postInbound()`**: it runs the
  GuestMatcher (minting a **spurious Guest for the owner**) and is webhook-shaped (find-or-create by
  `external_thread_ref` = a silent wrong-thread risk). **Not `sendOutbound()`**: that is the operator's
  direction and hard-codes the `"Operator"` sender label.
- **`ManualChannel`'s docblock widened — behaviour unchanged.** "Recording IS the delivery" already described
  an in-app message exactly. A distinct `in_app` channel + adapter was considered and **rejected**: it would
  be byte-for-byte identical (reuse, don't duplicate). The enum names the MEDIUM; the FKs say who.
- **The operator inbox now NAMES an owner thread** — it said "Unknown sender", which was both ugly and wrong
  (the owner is the most precisely-known counterparty in the system). Required, or an owner messages into a
  void.

**THE BOUNDARY FALLS OUT OF THE SCOPE — nobody writes a predicate** (see invariant #18):
`/owner` → `owner_id IN (their ids)`, so a guest thread's NULL owner_id is excluded automatically ·
`/app` → OwnerContext inactive → unconstrained, so the operator sees everything and can reply ·
no owner link → `1=0`. Proven **symmetrically** (both owners, both directions, plus never-a-guest-thread and
cross-org) and **mutation-proven**: delete the trait and 4 tests fail, including the guest-thread leak.

**THE TRANSCRIPT IS MIRRORED, and that is the model being right, not a hack.** `messages.direction` is
ORG-relative (`inbound` = into the organization), so on an owner thread the OWNER's own messages are the
`inbound` ones and staff replies are `outbound`. The operator inbox reads the same rows the other way up.
**One column, two correct perspectives, nothing duplicated to drift.** Asserted, not eyeballed.

---

**⚠️ CUT — ARCHITECTURALLY OUT, *NOT* "DEFERRED POLISH".** The distinction matters: these are not things we
ran out of time for, they are things that **cannot exist in this shape**. The design's counterparty list was
three products in one rail:
- **"Vyrobel Support" / "Vyrobel Billing" — the SaaS VENDOR, not the operator.** Vyrobel is multi-tenant;
  `message_threads` is `organization_id`-scoped. **A thread to Vyrobel-the-company is not a tenant
  conversation at all** — it is cross-tenant vendor support, staffed by Vyrobel employees who are in no org's
  `users` table, spanning every tenant. It cannot live in this table without breaking tenancy. That is a
  helpdesk product (identity, routing, staffing, SLA), not a screen. **Do not "add it later" here.**
  (Its seeded thread also promised a *"Payouts → Preferences"* screen that does not exist.)
- **"Maintenance team" — a TEAM, not a user.** There is no user id to address or attribute a reply to; a
  team/queue counterparty is a second model (many recipients, assignment, no single author). The Maintenance
  module is already complete end-to-end and is where repair conversations belong.
- **BUILT INSTEAD: owner ↔ a named operator staff user** — **ONE real query** (the org's back-office members)
  replacing the design's **five fabricated directory entries**, which contradicted themselves inside a single
  file (`ana` was `online: true` in one list and `false` in the other).

**OMITTED POLISH — no backend, flagged with the reason, never faked** (~a third of the design):
- **"✓✓ Read"** — an **INVENTED ENUM VALUE**. `messages.delivery_status` is exactly
  received/queued/sent/delivered/failed; there is no `read`, no `read_at`, no receipt mechanism. Worse, the
  design **defaults every seeded message to it** (asserting staff have read everything the owner ever sent)
  **while omitting `failed`** — the one real state the built inbox honestly surfaces (#24). We render
  `failed` and no receipts.
- **Presence dots** — no presence exists anywhere (`users.last_login_at` is a login timestamp, not a
  heartbeat; there is no websocket layer).
- **"usually replies within an hour"** — not computed: the design picks between two strings using the
  fabricated presence boolean. There is no first-response metric and no SLA. It would make a **response-time
  promise on behalf of staff that the app cannot measure, let alone keep** — the Wave 1 stub-ADR/RevPAR rule.
- **Typing indicator** (a setTimeout on a fake bot) · **stars/reactions** (localStorage — per-browser,
  per-device, invisible to the operator) · **the pin** (persists **nowhere**, not even localStorage) ·
  **attachments** (`blob:` URLs that die on reload; the real `media`/`documents` polymorphs exist, but wiring
  them is its own gate) · **the canned auto-reply bot** — which would demo this as a **live, staffed,
  two-way system with presence and receipts**. It is the single most misleading thing in the file.

**CHEAP-WIN CHECK — nothing cleared the bar; SKIPPED deliberately rather than padding the wave:**
- **Per-thread favourite/mute.** The demo owner has **2 threads** — favouriting/sorting two things is
  meaningless. And **"mute" implies suppressing notifications that do not exist**: there is no notification
  backend (`app/Notifications/` does not exist; `Notifiable` is only Laravel's password-reset mail trait).
  That is the same class as the response-time metric — a promise the app cannot keep.
- **Static-prefill suggested chips.** Value is ~5 seconds of typing. And **2 of the design's 3 chips gesture
  at features the owner nav honestly marks "Coming soon" three inches away**: *"Can I block dates for
  personal use?"* (no owner calendar — `owner.calendar` does not exist) and *"Send me the tax summary"* (no
  tax centre — `owner.tax` does not exist). Shipping them means either the design's copy (misleading) or
  inventing product copy. Not worth it.

**THE OPERATOR SIDE IS COMPLETE — verified, not assumed.** Owner threads appear in the EXISTING operator
inbox, **named**, with the composer enabled (`manual` is a connected channel), and a reply through the real
POST path lands in the owner's portal as **theirs**, marked unread again. The full round trip works both
ways through the real UI; no operator-side affordance was needed.

**STILL STANDS:** the **inbox-permission gap** — the catalog has **no `inbox.*` permission**, so these
threads (like the guest inbox's six writes) are gated by **#26 + the owner scope** only. Naming a permission
is a product decision about who may speak to a guest/owner; still logged, still not invented. And **GDPR
#25**: `Guest::anonymize()` redacts threads **by `guest_id`**, so owner threads are untouched by it — correct
(an owner is not a guest; erasing a guest must not shred the owner's conversations), and stated so nobody
"fixes" it.

**Two more real bugs found by building:**
- **A same-second ordering ambiguity.** Two messages in one conversation routinely share a whole-second
  `created_at` — a reply seconds after a question is the NORMAL case, not an edge — so the "last message"
  preview could show the older one. Fixed with the repo's own ULID tie-break (`created_at DESC, id DESC`).
- **🎯 THE SECOND FLAKY TEST — root-caused, and the pair is now instructive.** `BackOfficeAccessTest`
  asserted `assertDontSee($unit->code)` where `seedScenario` codes units **`U2`** — *two characters* —
  against owner pages carrying **three random 40-char CSRF tokens** (`@livewireScripts`). `Str::random(40)`
  contains "U2" **~1.05% of the time** → **~15% per run** across 8 routes. Fixed with a leak-proof needle
  (`BRUNOUNIT9`), using the repo's own `FOREIGNUNIT9` prior art, and **proven deterministically**.
  **BOTH flakes were the same disease: testing the NEEDLE rather than the BEHAVIOUR** — a raw faker name vs
  escaped HTML, and a 2-char code vs random tokens. In both, the app was perfectly correct and the assertion
  was wrong. **When a test flakes, suspect the assertion's specificity before the app.**

Also: the owner shell gained an optional `subtitle` prop (mirroring `<x-operator.shell>`; the ten screens
that pass none are unchanged), the owner design system gained its first form + transcript CSS (built on OWNER
tokens, not copied from the operator layout), and `inboxOwner()`/`ownerThread()` live in `tests/Support` —
a function declared in one Pest file only loads with that file.

`DemoSeeder` seeds **two real owner↔operator threads through the real service** (one left unread, so the
badge and the Unread filter demo on genuine state).

## 2026-07-17 — WAVE 4 CORE built (Rates · New Reservation · backed Settings) — three gated sub-gates

**The three genuinely-buildable Wave 4 screens per `docs/WAVE4-INVENTORY.md`, each its own gate + commit,
each wiring an EXISTING certified service (never a parallel path) + only the small additions named below.
The other Wave-4 designs (Portfolio, Owner Team, Vyrobel Onboarding, Group Booking, Owner Rates,
Channels-as-drawn, Roles) are SUBSYSTEMS or account-gated — out of scope, see the register + the inventory.**

- **Sub-gate 1 — Operator Rates** (`/app/rates`; `RatesGrid` + `RateActionController`). The FIRST-EVER
  production caller of the certified-but-uncalled `RateAndRestrictionService` (`setRate`/`setRestriction`).
  **PER-ROOM-TYPE, not per-unit** (STRUCT-1: `rate_calendar` is keyed on `rate_plan_id`, so a per-unit grid
  would silently move every unit under a room type). A room-type × 14-day grid of real rates / availability /
  restriction badges + three derived tiles (avg rate PER CURRENCY, booked %, open nights — **no fabricated
  trend**; the design's "+8% vs Aug" has no baseline). **Integer money (#17):** the typed € is parsed to
  minor units by STRING arithmetic (`175.57 → 17557`, never `(int) round($x*100)` which drops a cent).
  Gated `can:rates.view` (page) / `can:rates.manage` (write) — both already in the catalog, correctly scoped
  (Property Owner / Group Admin / Hotel Manager / Revenue Manager, NOT Front Desk). The dead-enum
  channel-sync pills were omitted proactively.

- **Sub-gate 2 — New Reservation** (`/app/reservations/create`; `NewReservation` +
  `ReservationCreateController`). ONE direct booking through the certified single write path
  `AssignmentService::createBooking` (the no-double-booking guarantee is untouched — this screen never
  re-implements it), plus the three guards the audit demanded (the design is "SAFE as drawn: ❌"):
  **GUARD 1 honest availability** — a new `UnitAvailabilityService` mirrors createBooking's Level A
  (room-type `available()≥1` every night) + Level B (unit free of active `reservation_unit` / `inventory_block`
  overlaps) + priced; the controller re-derives it server-side (never trusts a client price), and the row
  lock stays the actual guarantee (a forced booking of a taken unit is rejected, nothing created).
  **GUARD 2 PII** — the existing-guest search is NAME-only (email/phone are encrypted, never rendered or
  queried); a new guest's contact details go to the encrypted columns, never echoed back.
  **GUARD 3 no raw PAN (PCI)** — there is NO card-number field anywhere; an optional payment is the MANUAL
  folio path (`FolioService::recordPayment` over `ManualPaymentGateway` — cash/bank), gated `payments.record`;
  card capture is stubbed "Soon" for the account-gated Stripe tokenization (asserted: no PAN column, card
  method refused, `card_token = null`). Gated `can:reservations.create`; separation of duties — an Agent may
  book but not take payment.

- **Sub-gate 3 — backed Settings slices** (`/app/settings`: `OperatorSettings` + `SettingsActionController`
  + `SettingsService`; `/owner/settings`: `OwnerSettings` + `OwnerProfileController`). The FIRST code in
  `app/` to write `properties` / `property_policies` / `organizations` config (only the seeder did before).
  **Operator + Property settings de-conflicted into ONE surface** (so tax/policy config has one write path,
  not two that drift). BACKED + wired: property name/timezone/star-rating/check-in-out/status, stay policies
  pet/smoking/min-max-stay, org currency/locale/billing-email. Per-section permission split:
  `can:properties.manage` (property + policies; Property Owner + Group Admin) / `can:settings.manage` (org;
  Property Owner only) — both existing, never Front Desk. Taxes/fees rendered READ-ONLY (real data; full CRUD
  is a later, larger surface). Unbacked design fields (branding/white-label, invoice numbering, notification
  routing) stubbed **"Soon", never a toast-only fake save** — the design's `saveGuard:()=>{}` trap.
  **Owner self-profile** (name + encrypted email/phone): the SAFE self-edit the audit named — `Owner` is
  `BelongsToOrganization` NOT `BelongsToOwner`, so `OwnerScope` does not scope it; the write is guarded by an
  EXPLICIT `where('user_id', auth id)` (no owner id anywhere in the request), proven by having owner A's edit
  leave owner B untouched.

**Shared extraction:** `App\Support\Money\MoneyInput::toMinor()` — the ONE string→minor-unit parser (no float
drift), used by both the rate and the payment writes; Sub-gate 1's `RateActionController` was refactored onto
it (one parser for every typed-money surface). Two Larastan relation generics added
(`RoomType::ratePlans`/`defaultRatePlan`, `Property::policies`) so `->first()` infers the model, not `Model`.
**No new permission was needed** — every gate reused an existing catalog permission (the inventory's
`channels.manage`-absent finding does not touch these three screens).

**Verification (all three gates):** suite **588 tests / 5946 assertions** green on MySQL; PHPStan L5 + Pint
clean; `migrate:fresh --seed` clean; each browser-verified with a REAL persisting write — Rates €100.00 →
€155.50 with the avg tile recomputing to a reconciling €141.98; a booking landing on the certified
Reservation Detail with folio Room +€300 / Payment −€150 / Balance €150 "reconciled to the folio ledger"; the
property star-rating and the owner's phone persisting across the PRG.

**KEPT in the register — Wave 4 CORE does NOT close these:** the **dead-enum channel-lifecycle**
(`channels.status` + `channel_mappings.status` are READ as ARI-push gates but written only by a test factory,
so the certified channel manager is INERT in production until a status writer exists — the Channels UI is that
missing writer, not "a thin CRUD controller") · the two payment/folio dead enums (`payment_status='refunded'`,
`folios.status='closed'`) · the two RBAC self-provision defects (Super Admin unassignable; no org-creation
path provisions roles) · no `inbox.*` and no `channels.manage` permission in the catalog.

## 2026-07-17 — CHANGE PASSWORD built (operator + owner) — the auth chapter is complete for the logged-in user

**The one genuinely quick-buildable auth screen from `docs/AUTH-INVENTORY.md` — a logged-in operator or owner
changes their OWN password from within the app.** One gate, reusing the existing reset machinery; no password
crypto reimplemented.

- **The shared path (so reset + change can't drift):** extracted `User::changePasswordTo(string)` — hash (via
  the `password` 'hashed' cast) + rotate `remember_token` (`Str::random(60)`) + save — and refactored
  `PasswordResetController::reset()` ONTO it. RESET and CHANGE now go through ONE hash+rotate path.
- **The controller:** `Auth\ChangePasswordController` (operator + owner methods → a shared `change()`). The
  user is resolved from `auth()` ONLY — no user id anywhere in the request — so a session can only change its
  OWN password (an operator can't touch an owner's and vice-versa; proven by test). 🔒 The **current password
  is verified** (`current_password` rule → `Hash::check`) BEFORE any change — the anti-hijack guard; a wrong
  current password changes nothing (proven). New password meets the reset rules (`min:8`, `confirmed`).
  Rate-limited (`throttle:6,1`) + CSRF + PRG.
- **Session behaviour (matches reset):** the current device stays signed in; rotating the remember token
  invalidates any "remember me" cookie on OTHER sessions. (`Auth::logoutOtherDevices` would additionally end
  other *active* sessions but needs the `AuthenticateSession` middleware — out of scope; the remember-token
  rotation is the reset-flow baseline.)
- **Where it lives:** a lean operator **`/app/security`** page (`OperatorSecurity`) behind `auth` + #26 with
  **NO fine-grained gate** — because `/app/settings` is `properties.manage`-gated (managers only) and EVERY
  back-office user must be able to change their own password (a Housekeeping user reaches `/app/security` but
  NOT `/app/settings` — proven). On the owner side, a change-password card on the existing **`/owner/settings`**
  (every owner reaches it). The operator shell's "Security" nav is now live.
- **Honesty:** the design's Security screens also draw 2FA, active sessions, connected apps and IP rules — all
  **unbacked subsystems** (still 🔴 in `docs/AUTH-INVENTORY.md`) — shown as an honest "Soon", never faked.

**Verification:** `tests/Feature/ChangePasswordTest.php` (9 — happy path + remember-token rotation, wrong
current rejected/unchanged, mismatch + too-short rejected, `auth()`-resolution [A changes, B untouched], owner
side, auth-required, the non-manager reachability of `/app/security`, and the shared path). Suite **597 tests /
5986 assertions** green (the reset flow is unregressed by the refactor); PHPStan L5 + Pint clean;
`migrate:fresh --seed` clean; **browser-verified BOTH sides end-to-end** — change → log out → log in with the
new password (operator + owner).

**The auth chapter is now complete for the logged-in user** (login · forgot/reset · invite/set-password ·
**change password**). The remaining auth items stay **BLOCKED subsystems** (per `docs/AUTH-INVENTORY.md`): SaaS
self-signup / tenant-provisioning, 2FA (TOTP), the whole Guest Portal auth, sessions/connected-apps/IP-rules.

## 2026-07-18 — TWO-FACTOR AUTHENTICATION (TOTP) built — enrollment + recovery codes + login challenge (invariant #31)

**2FA is now a complete, opt-in, hardened subsystem — the first item lifted off the auth BLOCKED list, because it
was never account-gated, only unbuilt** (no third-party credential involved — just TOTP maths a user's own
authenticator app runs). Built in three gated steps (`031716a` enrollment · `aca017c` challenge · this consolidation).
No hand-rolled crypto: `pragmarx/google2fa` (TOTP) + `bacon/bacon-qr-code` (QR SVG); NOT Fortify (it would collide
with this repo's bespoke, hardened auth chapter).

- **The single source of truth is `User::hasTwoFactorEnabled()`** = `two_factor_secret !== null && two_factor_confirmed_at !== null`. The legacy `two_factor_enabled` boolean is a **display mirror only** and is NEVER the gate (it cannot drift the decision).
- **Enrollment is confirm-before-activate (anti-lockout).** `TwoFactorService::startEnrollment()` stores a secret with `two_factor_confirmed_at = null` — which `hasTwoFactorEnabled()` treats as NOT active, so a generated-but-unconfirmed secret never gates login. Only `confirm()` with a **valid 6-digit code** flips it on and issues the recovery codes. An invalid confirm code activates nothing (mutation-proven).
- **🔐 ENCRYPTED AT REST — and this fixed a pre-existing latent exposure.** `two_factor_secret` was a **plaintext** column before 2FA was wired; it is now `encrypted`, and `two_factor_recovery_codes` is `encrypted:array`. Proven by asserting ciphertext in the raw DB column. (Larastan sees only the encrypted string column, so `User` carries an accurate `@property list<array{code,used_at}>|null` hint — a real type annotation, not a silencing cast.)
- **Recovery codes: 8, shown ONCE, single-use.** Stored as `{code, used_at}`; a challenge consumes a match by stamping `used_at` (constant-time `hash_equals`); regenerate invalidates the whole prior set. A used code cannot log in again (proven at rest + in the browser).
- **Disable requires re-auth.** Turning 2FA off (while active) requires the **current password** (`current_password` rule) — an open session cannot silently strip it. Cancelling an unconfirmed *pending* enrollment needs no password (there is nothing to protect yet).
- **Self-only.** Every operation resolves the user from `auth()` / `$request->user()` — never a request-supplied id — so a session can only touch its OWN 2FA (operator ↔ owner isolation proven).
- **THE LOGIN CHALLENGE — the load-bearing change to the login flow.** `LoginRequest::authenticate()` now uses **`Auth::validate`** (verify the password, establish NO session) instead of `Auth::attempt` (which fuses verify+login). `LoginController::store()` then decides: a non-2FA user is logged in immediately (`Auth::login` — validate+login is **equivalent** to the old single `attempt`, so that path is **unregressed**); a 2FA user is held in a **PENDING** state and redirected to `/two-factor-challenge`. The challenge accepts a TOTP **or** a single-use recovery code; success runs `Auth::login` + session `regenerate()` (fixation) + routes by role. Invalid codes are rejected and **rate-limited** (keyed on the pending user + IP — a 6-digit code is brute-forceable otherwise; the throttle blocks even a valid code once tripped).
- **⚠️ THE PENDING STATE GRANTS NOTHING — BY CONSTRUCTION, not by a new check (invariant #31).** The "2FA pending" marker is a **plain session key** (`login.2fa.user_id`), **NEVER the auth guard**. So a password-verified-but-code-pending user is not `Auth::check()`-true, and `/app` + `/owner` (behind `auth`) reject them with **no new authorization code written** — the existing middleware does it. `RequireTwoFactorPending` only fences the challenge PAGE (no pending state → back to login; already authenticated → bounced). **This is the whole reason the flow is safe:** we relied on the guard already there rather than inventing one that could be forgotten.

**Verification.** `TwoFactorEnrollmentTest` (12) + `TwoFactorChallengeTest` (8) — the challenge file **mutation-proves** the security property: a 2FA user who gives the *correct password* cannot reach `/app` or `/owner` until a valid code; valid TOTP completes; valid recovery code completes **and is consumed** (no reuse); invalid is rejected + throttled (a valid code is blocked past the limit); a wrong password never enters the pending state; a **non-2FA user logs in unchanged** (no regression). Full suite **617 tests / 6100 assertions** green; PHPStan L5 + Pint clean; `migrate:fresh --seed` clean. **Browser-verified end-to-end BOTH portals** (operator + owner): enroll → recovery codes → log out → TOTP-challenged login → recovery-code login → disable-with-re-auth (wrong password rejected, 2FA stays on; correct password turns it off) → subsequent login passes straight through. Demo accounts reset to password-only afterward, so the documented demo login is unchanged.

**The auth chapter now includes 2FA.** Remaining BLOCKED auth subsystems (per `docs/AUTH-INVENTORY.md`): SaaS self-signup / tenant-provisioning, the whole Guest Portal auth, and sessions / connected-apps / IP-rules (all still honest "Soon").

## 2026-07-18 — CAPSTONE SESSION CONSOLIDATION (docs only, NO code) — the buildable core is complete; next is customer validation

**A total-recall consolidation at the close of a very large session**, refreshing `docs/PROJECT-STATE.md` +
`CLAUDE.md` to the true current state and adding this dated marker. **No code changed** — this entry records
where the product stands, not a new decision.

- **Ground truth (verified for this capstone):** HEAD `11288d6`, on `main`, **local == origin, tree clean**.
  **`php artisan test` → 617 tests / 6100 assertions, all green** on MySQL 8 (`vyrobel_test`). **PHPStan L5, 0
  errors. Pint clean.** (The doc commit carrying these words advances HEAD by one — the HEAD convention.)
- **The arc this session covered** (all already recorded in their own dated entries above; this is the index):
  Wave 1 (owner-accounting long-tail, 10 screens) → the `/app` authorization fix (**#26**) → the cumulative
  over-refund fix (**#27**) → Wave 2 (reservations + Guests CRM, 5 screens; the `ReservationStage`/`GuestSpend`/
  `GuestHistory` extractions; the notes-redaction GDPR fix) → the security register cleared (`terminate()` guard
  **#29**, the enforced permission catalog **#30**, the `anonymize()` UI trigger **#28**; both flaky tests
  root-caused) → Wave 3 (the owner inbox; `owner_id` + `BelongsToOwner`, **#18** extended) → Wave 4 CORE (Rates,
  New Reservation, Settings; `MoneyInput`, `UnitAvailabilityService`) → **Change Password** → **2FA (TOTP)**,
  invariant **#31**. **Five security/integrity holes closed this session, every one found by READING, not by a
  failing test.**
- **The honest state, recorded plainly:** the buildable-in-a-vacuum, clearly-worth-it, self-contained core is
  **essentially complete — 2FA was the last such subsystem.** What remains is **① subsystems/epics** (real
  gated builds: Portfolio, Owner Team, tenant-provisioning, Group Booking, Owner Rates, Channels-as-drawn,
  Roles, guest auth, sessions/apps), **② speculative modules** (Guest Portal ~24, Analytics, Reviews, Billing,
  Notifications, Emails — designed, zero backend, **no customer signal**), and **③ account-gated** (real inbox
  channels, Channex live, Stripe Connect, Stripe card checkout, prod email). Plus the small **still-open register
  items** (dead enums; no inbox permission; two fail-closed RBAC defects; and — recorded this session — **the
  channel manager is inert in production because nothing writes `channels.status`/`channel_mappings.status`**,
  verified by grep).
- **THE NEXT MOVE IS NOT A BUILD — it is CUSTOMER VALIDATION.** The product demos at full depth and is safe for
  a real owner login; **no customer has ever reacted to it.** An intro email is out to a warm contact; a real
  reaction should DIRECT which ① epic or ③ integration is worth committing to, rather than betting blind. **More
  building has diminishing returns until the signal comes in.** Full tagged triage + the invariant list #1–31 +
  the meta-lessons live in `docs/PROJECT-STATE.md`; read it first on a new session, then this file.

## 2026-07-20 — CUSTOMER SIGNAL ARRIVED + the master GAP ANALYSIS (audit-verified) — the plan is now "build it all, like the prototype"

**The "no signal, don't build blind" framing above is SUPERSEDED.** A warm operator contact (Fatima) **liked the
demo AND the prototype**, and the requirement is now concrete: **the real product must work the way the prototype
shows.** The prototype (all 124 designed screens) is the SPEC for what each screen shows; the repo stays the truth
for what backs it; every gap gets BUILT (not faked). The full phased map is a new durable doc, **`docs/GAP-ANALYSIS.md`**.

- **This was NOT taken on faith — the plan was audit-verified against real code before being recorded** (a
  5-agent verification pass, 2026-07-20, at HEAD `c34669a`; 50 repo-checkable claims → 33 CONFIRMED, 8 INCORRECT,
  8 NUANCED). Meta-lesson (a) held: the plan's structure and every large gap it named were TRUE (guest auth,
  notifications, reviews, wallet, tenant-provisioning, group booking — all verified zero-backing), but the audit
  corrected three things, now baked into `GAP-ANALYSIS.md`:
  - **⚠️ MULTI-PROPERTY IS NOT A SCHEMA EPIC (the consequential correction).** The schema already supports N
    properties/org — `properties.organization_id` is a plain FK, the `property_user` pivot + `User::properties()`/
    `Property::staff()` relations exist, and `property_id` is already on `channels`/`groups`/`rate_plans`. The
    limitation is a UI convention: ~6 files / ~9 call sites resolve "the property" as
    `Property::query()->orderBy('name')->first()` (`FrontDesk`, `ReservationRack`, `OperatorDashboard`,
    `NewReservation`, …). **Portfolio (Phase 6) re-scoped from an L-subsystem to an M** (a property selector/context
    + a property-CRUD path + refactoring those call sites).
  - **CORRECTED COUNTS.** ~40 built screens/artifacts (not "38–41 loosely"), ~84 designed-but-unbuilt. The plan's
    per-front counts double-counted **terminal states** (Reservation Cancelled/No-show are states on Reservation
    Detail, not screens), **POST sections** (Property Settings is a section of the one `/app/settings` screen), and
    a nonexistent separate **owner Security screen** (change-password + 2FA live on `/owner/settings`); and it
    MISSED **Units** (built, styled, no design file). **Guest Portal = ~24** (the authoritative ROADMAP module
    count), not 32 (that's the design-export file count incl. the 4 already-built public-booking screens).
  - **NUANCES baked in:** the generic `settings` KV table + `Setting` model are DORMANT (future settings screens
    follow the typed-column `SettingsService` pattern, not the KV scaffold); maintenance has a hard-coded 5-value
    `category` enum already (only SLA + *configurable* categories are missing); a property-scoped availability
    search exists (`BookingEngineService::search`) — the gap is GLOBAL cross-entity search + the blind-index
    decision for encrypted guest contact.
- **The three TRUE greenfield subsystems the prototype assumes** (verified absent, zero backing): **guest auth**
  (`Guest extends Model`, not `Authenticatable`), **notifications** (no table, no `app/Notifications/`, zero
  `->notify()`), and **reviews + wallet/credits** (no tables). Multi-property is NOT one of them.
- **NEXT ACTION is now the phased build in `docs/GAP-ANALYSIS.md`, starting PHASE 0 (fix & harden):** the inert
  channel manager (a real `channels.status` writer + connect skeleton + demo seed — prove the ARI push fires), the
  dead-enums decision, the two RBAC defects, the stale-docblock cleanup, and a QA walkthrough script
  (`docs/QA-WALKTHROUGH.md`). Sequencing bias: **make Fatima operational soonest** (Phases 0–3 before the large
  Guest Portal phase). PROJECT-STATE + CLAUDE NEXT ACTION updated to point here.

## 2026-07-20 — PHASE 0 (fix & harden) COMPLETE — five gated sub-gates; the product no longer lies to itself

The first phase of `docs/GAP-ANALYSIS.md`, done as five gated sub-gates (per-gate commits, mutation-proven where
there's a guard, browser-verified where there's a screen). Goal: close what was silently broken BEFORE building
new features on top of it.

- **0.1 — the inert channel manager, fixed (`8e3b54c`).** `App\Services\ChannelConnectionService` is the real
  `channels.status` / `channel_mappings.status` writer that was missing: `EnqueueAriPushOnInventoryChange` pushes
  ARI only to `status='connected'` channels (mapping guard only includes `status='active'`), but nothing in
  `app/` ever wrote either — only factories — so the certified sync backbone could never fire for a real
  operator. The writer records audit + domain events inside the service; the `channel.*` events are NOT in the
  ARI trigger set (no loop). The local activation is real; the live Channex handshake stays account-gated behind
  the `ChannelManagerClient` seam. `DemoSeeder` now seeds a connected channel + active mappings.
  **MUTATION-PROVEN** (`ChannelConnectionTest`): a disconnected channel enqueues NO push on a booking; after
  `connect()` an equivalent booking enqueues + processes exactly one. The Channels UI that drives this in
  production (+ `channels.manage`) is Phase 1.
- **0.2 — the dead status enums, resolved per enum (`88b2326`).** `reservations.payment_status='refunded'` is
  now genuinely WRITTEN (`FolioService::paymentStatus()` — a fully-refunded stay, so it no longer reads the small
  lie 'unpaid'); `'charged_back'` dropped (no dispute flow). `folios.status` `'closed'`/`'void'` dropped (no
  close/void lifecycle; a half-built lock would be a worse lie). A migration prunes both enums (safe — zero rows
  carried a removed value). Mutation-proven + a `SHOW COLUMNS` assertion that no unwritable value remains
  (`DeadEnumResolutionTest`). One harmless remnant (`payments.status='refunded'`) documented, out of scope.
- **0.3 — the two fail-closed RBAC defects, re-logged + formally deferred (`863c5b1`).** Neither has a code path
  to fix today, so (the sanctioned outcome for a fail-closed defect) each is deferred with reasons to the gate
  that builds its fix: defect #1 (no org-creation provisions roles) → Phase 7's tenant-provisioning transaction
  MUST call `OrganizationRoleProvisioner::provision()`; defect #2 (`Super Admin` unassignable) → a spatie
  `model_has_roles` schema migration. A global `Organization::created` hook for #1 was CONSIDERED and REJECTED
  (it would add provisioning overhead + shared-permission-table contention to 90 org-creating test files, incl.
  the true-parallel concurrency suites, to guard a path that doesn't exist). The provisioner's docblock, which
  falsely claimed it was "called at organization-creation time", is now honest + loud.
- **0.4 — stale "2FA is Soon" docblocks fixed (`18f4193`).** `OperatorSecurity`'s docblock still called 2FA an
  unbacked "Soon" panel (and change-password its "ONE real feature"), and the owner-settings Blade comment
  listed 2FA among stubbed subsystems — both contradicted by the shipped real TOTP (invariant #31). Corrected;
  the genuinely-unbacked panels stay honestly "Soon". Comment-only.
- **0.5 — the QA walkthrough (`b7b36fd`).** `docs/QA-WALKTHROUGH.md`: a run-it-yourself runbook over every built
  screen, every role — public booking, all 21 operator + 15 owner screens, the security-boundary checks
  (#26/#28/#30 + cross-org 404), the full auth chapter, a role matrix, and a per-surface "Soon" register so
  honest deferrals aren't logged as bugs.

**Verification:** full suite **625 tests / 6125 assertions** green on MySQL (Phase 0 added +8: the 5 channel-
connection + 3 dead-enum tests); PHPStan L5 + Pint clean; `migrate:fresh --seed` clean (demo channel connected +
mappings active, enums pruned); the money screens browser-verified (render intact after the 0.2 docblock edits).
**NEXT = Phase 1** (complete the operator app: Search, Group Booking, Roles, Maintenance Costs/Settings, Operator
Profile).

## 2026-07-20 — PHASE 1 (complete the operator app, buildable set) COMPLETE — five gated sub-gates

The second phase of `docs/GAP-ANALYSIS.md`, done as five gated sub-gates (audit-first · per-gate commit ·
mutation-proven where there's a guard · browser-verified where there's a screen · honesty rule · integer money ·
real enums · tenant/self isolation fail-closed). Six operator screens shipped; the operator app's buildable-set is
now complete (operator 21 → 27 built). **Push held to this final gate** (the five commits landed together). The
recurring lesson: **every one of these screens had a security-critical shape the design did NOT show, and the audit
is why we know** — building any of them as drawn would have shipped a hole.

- **SG1 — Group Booking (`eefd22d`).** `GroupBookingService` + `AssignmentService::createGroupBooking()` — an
  ATOMIC multi-unit block over the EXISTING `groups` + `reservations.group_id` (extend, never a parallel
  per-channel table; `source='group'`). The security-critical decision is the **phantom-event guard**:
  `DomainEventRecorder::record()` dispatches `DomainEventRecorded` SYNCHRONOUSLY, so the group's
  `reservation.confirmed` events are recorded ONLY after the outer transaction commits (one per persisted member)
  — otherwise a rolled-back block would fire ARI-push/recompute listeners for reservations that never existed.
  Prices are re-derived server-side (integer minor units via `UnitAvailabilityService::availableUnitMap`, never a
  client float); the organiser's PII goes to the encrypted `Guest` columns; the write is gated `can:reservations.create`.
  **MUTATION-PROVEN** (`OperatorGroupBookingTest`, 6): pre-book a unit, attempt an overlapping group → the whole
  block rolls back (0 groups persisted) AND the `domain_events` count is UNCHANGED — no phantom event escaped.
- **SG2 — Global Search (`1595583`).** `SearchService` — a GLOBAL cross-entity search (reservations by
  reference/lead-guest name · guests · units · owners), tenant-scoped by construction. The security/honesty
  decision: **guests are searched by NAME only.** `guests.email`/`phone` are `encrypted` casts → the ciphertext is
  not `LIKE`-searchable, so a contact-string search is impossible without a blind index. Rather than silently
  return nothing for an email query, the screen DETECTS a contact-looking query (`looksLikeContact()`) and surfaces
  the limitation. **The blind-index decision is DEFERRED** (it's a schema commitment — a hashed index column per
  searchable field); logged, not faked. Per-entity results are gated on the viewer's abilities
  (`guests.view`/`units.view`), the route on `can:reservations.view`. Proven tenant-isolated + name-not-email
  (`OperatorSearchTest`, 8).
- **SG3 — Roles (`35019e9`) — the security-critical gate.** `RoleAssignmentService` — VIEW the fixed permission
  catalog + ASSIGN/REVOKE a catalog role, gated `can:users.manage`. **The design as drawn was an escalation
  weapon** (New role / Save / Delete = edit the catalog itself) and was **REFUSED** — the catalog is read-only.
  Assignment enforces the **privilege-subset rule**: an actor may only assign a role whose permissions they
  already hold IN FULL (`perms(role) ⊆ perms(actor)`), so a Group Admin cannot grant `Property Owner` (`['*']`) —
  only `['*']` grants `['*']`. Plus: no self-management, tenant-bound target, catalog-roles-only. spatie is
  team-scoped (team = org); `setPermissionsTeamId($orgId)` around every read/write. **MUTATION-PROVEN**
  (`OperatorRolesTest`, 7): a Group Admin's attempt to assign `Property Owner` throws `RoleAssignmentException`
  and grants nothing. (This gate needed Phase 0.3's RBAC re-log as its backdrop — the two fail-closed defects stay
  deferred to their fix gates.)
- **SG4 — Maintenance Costs + Settings (`a4a3ddc`).** `MaintenanceCostService` — every figure a REAL derived
  aggregate over resolved tickets (total · orders · avg · by-category · top-units · by-month · owner-unit vs
  operator-unit split from `units.owner_id`), reconciling to the cent; an empty period is `—`, never a fabricated
  €0.00. **The prototype's labour-vs-parts split was REFUSED, not faked** — the schema holds ONE `cost_minor` per
  ticket, no breakdown to render. `MaintenanceSettingsService` + a new `maintenance_settings` table persists SLA
  hours, category→technician assignees (tenant-validated — a cross-org technician is dropped), and toggles as
  **TYPED COLUMNS via the `SettingsService` pattern — NOT the dormant KV `Setting` scaffold** (the audit's
  standing rule). Notification toggles STORE now; dispatch is honestly **deferred to Phase 3**. `MaintenanceSetting`
  registered in the morph map (it's an audit subject). Both gated `can:maintenance.manage`. Proven
  (`OperatorMaintenanceAdminTest`, 6): aggregates reconcile, typed persistence (not toast-only), cross-org
  assignee dropped, gate holds for Front Desk.
- **SG5 — Operator Profile (this gate).** `OperatorProfileController` — the logged-in operator edits their OWN
  name + work email, via POST → controller → PRG (a REAL persist on the `users` row, not a toast-only save).
  **SELF-ONLY, and the guard is the resolution itself:** the user is `$request->user()` (the authenticated login)
  and there is NO user id anywhere in the request — so there is no id to tamper with to reach another account (the
  change-password / owner-self-edit discipline). `email` is the login identifier (plaintext on `User`, unlike
  encrypted guest PII) and is kept UNIQUE-ignoring-self. Role / status / member-since render read-only; the
  design's unbacked fields (phone / preferred-name / timezone / density) are **OMITTED, not faked** (no column →
  no fake save). Behind auth + #26 with NO fine-grained gate (like `/app/security` — every back-office user has a
  profile). **MUTATION-PROVEN** (`OperatorProfileTest`, 8): a smuggled `user_id`/`id` in the body is ignored (only
  the actor's row changes, the victim's is untouched); email uniqueness; name required; an owner-role user is
  refused (#26 → `owner.dashboard`).

**Deferred out of Phase 1 (logged, not faked):** the **blind-index** contact-search decision (SG2 — a schema
commitment), **notification dispatch** for the maintenance toggles (SG4 → Phase 3's notification subsystem), and
**PDF/Excel exports** of the cost report (SG4 — the figures are real; only the export is deferred).

**Verification:** full suite **660 tests / 6278 assertions** green on MySQL (Phase 1 added +35 across the five
gates); PHPStan L5 + Pint clean; `migrate:fresh --seed` clean; the Operator Profile self-edit browser-verified end
to end (edit name → PRG → the new value round-trips to the topbar chip, identity card AND the form field; role /
status / member-since shown; nav highlights "Your profile"; no raw Blade). **NEXT = Phase 2** (complete the owner
portal's buildable set: Bookings + Detail · Block Calendar · Owner Rates · Tax Center · Payout Method · Add Unit ·
Help).

## 2026-07-20 — Owner Rates: DIRECT edit, no approval (conscious divergence) + the exclusivity guard (Phase 2 · SG3)

Recorded at the gate (the full Phase-2 consolidation lands at SG6). Two decisions worth pinning now:

- **DIRECT edit, NO approval — a CONSCIOUS DIVERGENCE from the design.** The "Owner Rates" prototype is an
  approval/guardrails model: the manager *proposes* rate changes, the owner *approves / declines / counters*, and
  sets floor/ceiling guardrails ("Every change needs your sign-off before it goes live"). The product decision is
  **FULL DIRECT edit** — the owner sets the rate and it is live immediately. There is NO proposal / approval /
  guardrail schema, so those sections (Proposed changes, Guardrails, comp-set, Base/Weekend/Peak tiers) are NOT
  rendered as real and NO fake "pending sign-off" state is shown. An operator-approval workflow would be a clean
  additive layer later (a proposals table + an approve step in front of `setRate`). Recorded so the divergence is
  a decision, not an omission.
- **THE OWNER-SCOPE GUARD ON A PER-ROOM-TYPE MONEY WRITE — exclusivity.** Rates key on rate_plan → room_type, and
  a room type can hold units owned by different parties, so an owner may edit a room type's rate ONLY IF they own
  it EXCLUSIVELY (every unit in it is theirs). The editable set is resolved through ownership
  (`OwnerRateService::editableRoomTypes()`: owner-scoped units → their room types → drop any with a foreign unit),
  never from a request id; a write outside it throws `OwnerRateException` → 403 with nothing written. This is both
  the read-isolation set and the write allow-list — mutation-proven both ways (removing the guard lets owner A set
  owner B's rate AND lets A edit a room type shared with the operator; both 2/2 fail without it). Wired through the
  certified `RateAndRestrictionService` (`setRate`/`setRestriction`) — the SAME path the operator screen drives;
  `RoomType::primaryRatePlan()` was extracted so both screens agree on which plan a room type's rate lives on.
  Integer money via `MoneyInput` (175.57 → 17557, string arithmetic, no float).

## 2026-07-20 — PHASE 2 (complete the Owner portal, buildable set) COMPLETE — six gated sub-gates

The third phase of `docs/GAP-ANALYSIS.md`, done as six gated sub-gates (audit-first · per-gate commit ·
mutation-proven guards · browser-verified · honesty rule · integer money · owner isolation fail-closed). Eight
owner screens + one operator screen (Unit Requests) + the Annual Tax PDF; the owner portal's buildable-set is now
complete (owner 15 → 23 built). **All six commits pushed together at this final gate.** The through-line: **owner
isolation is the #1 concern of the whole phase, and every gate proves it a different way.**

- **SG1 — Owner Bookings + Booking Detail (`0dfc560`).** Owner-scoped THROUGH the units: reservations carry no
  `owner_id` (owner A + owner B share the operator's ORG), so the set is derived from the owner-scoped
  `OwnerPortalService::units()`, and Booking Detail 404s a `{booking}` id unless it touches an owned unit —
  mutation-proven (disable the guard → owner B's booking leaks at 200). "Net to you" flows through the ONE new
  shared `App\Support\Owner\OwnerLedgerAttribution` rule (net-per-currency + gross→net breakdown), and
  `OwnerUnitDetail` was refactored onto it so Unit Detail / Bookings / Booking Detail / the statement can't drift.
  Guest NAME only (invariant #28); real source enum (OTA, never a brand); stage via the shared `ReservationStage`.
- **SG2 — Owner Block Calendar (`598cc9b`).** The owner holds/releases dates on their OWN units via the certified
  `AssignmentService` (single inventory write path), never a parallel system. The owner-scope WRITE guard
  (`OwnerBlockService::ownedUnitOrFail`, resolved from owned units, never a request id) is mutation-proven for
  BOTH block and release. ⚠️ We ALSO closed a latent gap: `blockInventory` now calls `assertUnitFree` for
  unit-level blocks — the overlap enforcement the migration + model docblocks always promised but never actually
  ran — safely (existing block + the 20 concurrency tests stay green). `reason='owner'` (real enum) + the granular
  reason in `notes`; unblock reuses the already-wired `inventory_block.released` event; never touches `sold`.
- **SG3 — Owner Rates (`58f0b49`) — the phase's highest-risk gate: an owner-scoped MONEY write.** FULL direct
  edit via the certified `setRate`/`setRestriction` (same path as the operator; `RoomType::primaryRatePlan()`
  extracted so both agree). ⚠️ Because rates are per-room-type and a room type can hold units of different owners,
  the guard is **exclusive ownership**: `editableRoomTypes()` keeps only room types where EVERY unit is the
  owner's (drops any with a foreign unit) — resolved through ownership, never a request id. Mutation-proven BOTH
  ways (cross-owner write AND the subtle shared-with-operator case both 403 without it). **DIRECT edit, NO
  approval — a conscious divergence** from the design's approval/guardrails model, recorded (the design's
  Proposed-changes/Guardrails/comp-set/tiers have no schema and are NOT faked as "pending"). Integer money.
- **SG4 — Tax Center + Annual Tax PDF (`5119548`).** Self-only (owner by `user_id`), `tax_id`/`vat_number`
  ENCRYPTED at rest (asserted ciphertext; audit records fields not values, #28). The payout HOLD is GENUINE, not
  a label: `OwnerPayoutService::pay()` throws `PayoutHeldException` before the rail send for a tax-incomplete
  owner and releases on save — mutation-proven (disabling it lets a tax-incomplete owner be paid). No regression:
  a `tax_id` default on `OwnerFactory` keeps factory owners tax-complete. Real annual aggregation reconciling by
  construction (a statement's buckets sum to its net); withholding omitted (no engine), not faked.
- **SG5 — Payout Method (`b95228f`).** New `payout_methods` (IBAN ENCRYPTED + never rendered; masked ···· last4
  from cleartext `iban_last4`/`iban_country` so a list never decrypts the whole number). Self-only mutation-proven
  across read / set-default / remove (owner-scoped resolve → cross-owner 404). Real ISO 7064 mod-97 checksum +
  country/last4 from the string; the bank NAME is NOT fabricated (no registry — the owner labels it). Live Stripe
  Connect rail deferred honestly — no fake Verified / micro-deposit / schedule / next-payout-date.
- **SG6 — Add a Unit (request flow) + Help (this gate).** ⚠️ POLICY (stated): an owner does NOT create billable
  inventory directly — Add Unit files a `unit_requests` record (owner-scoped, pending, self-only) the operator
  reviews; a new operator `/app/unit-requests` screen (tenant-scoped, gated `units.view`) is the visibility side;
  the operator's actual unit-creation stays their own manual action (not built). `requested_kind` is the REAL
  `units.unit_kind` enum (no invented types). Help is a STATIC FAQ linking to the real owner screens + the ONE
  real contact route ("Message your manager" → the Wave-3 owner↔operator inbox) — the design's AI Q&A, support
  TICKET queue, article counts and system-status board are unbuilt subsystems and are NOT rendered (no faked
  tickets/chat/status).

**Verification:** full suite **697 tests / 6468 assertions** green on MySQL (Phase 2 added +41); PHPStan L5 + Pint
clean; `migrate:fresh --seed` clean (4 new migrations: group-booking fields were Phase 1; here tax-profile fields,
payout_methods, unit_requests); every screen browser-verified on the demo owner/operator. **NEXT = Phase 3** (the
notification subsystem + transactional emails).

### 2026-07-20 — Phase 3 COMPLETE: the notification subsystem + all transactional emails (5 gated sub-gates)

**The whole chapter is done and pushed.** Events → dispatcher → per-user in-app feeds + genuinely-gating
preferences → the real events wired → the guest transactional emails (Mailpit-real) → owner/operator email
delivery through the same gate → the Email Templates view. Commits `7cca65c` → `7905570` → `640f149` →
`6c51271` → this gate.

- **SG1 — notification CORE (`7cca65c`).** Laravel-native (`Notifiable` + the `notifications` table + a
  `FeedNotification` base). A `DispatchNotificationsForDomainEvent` listener turns REAL `DomainEventRecorded`
  events into per-user database notifications; operator = the org's back-office staff (`NotificationRecipients`),
  owner = the ONE owner via their linked user. **ISOLATION mutation-proven** (a user never reads/marks another's).
  ⚠️ **The sync event bus DOUBLE-DELIVERS** (listeners are auto-discovered AND manually registered) — the
  idempotent inventory listeners masked it; a notification must not double-fire, so a static `$processed` guard
  keyed on the domain-event id makes each event produce its notifications exactly once. Bells + feeds in both
  shells.
- **SG2 — Notification Preferences (`7905570`).** A TYPED `notification_preferences` store (constrained
  `event_key` + `channel` enum + bool), NOT the KV scaffold. `NotificationPreferences` = the fixed CATALOG + THE
  GATE `allows()`, called from `via()` BEFORE delivery. 🪤 **Preferences GENUINELY gate — mutation-proven** (an
  off toggle drops the channel → nothing is written; bypass it and the off-case delivers → test fails). Self-only
  (audience from the route default, never a request id — a user can't change another's). **Always-on** (Account &
  security) can't be disabled — `save()` forces it + `allows()` short-circuits + the UI locks it, all three agree
  (mutation-proven). Resolved the Phase-1 maintenance deferral: `maintenance_ticket.opened` (urgent) gated by the
  org `MaintenanceSetting.notify_new_urgent` AND the per-user pref (two-layer, proven).
- **SG3 — wire the real events (`640f149`).** Audit-first: enumerated the ~40 event types that actually fire
  before wiring. Wired `reservation.checked_out` (folio balance > 0) → BalanceDue · `unit.condition_changed`
  (→out_of_service) → UnitOutOfOrder · `payout.paid` → owner PayoutSent **+** operator OwnerPayoutSent ·
  `owner_statement.finalized` → owner StatementReady **+** operator StatementFinalized · `channel.error` →
  ChannelSyncError · `owner.payout_method_changed` → account-security (always-on). **One service change, stated:**
  `OwnerPayoutMethodService` recorded audit-only — it now ALSO records the `owner.payout_method_changed` DOMAIN
  event (the #events invariant: a state change writes BOTH rows; payload carries only WHICH change, never the
  IBAN). **DEFERRED honestly** (no backing event today, NOT wired): arrivals/housekeeping-behind/SLA/daily-summary
  (need a scheduler) · new-review (Phase 5) · new-device sign-in (needs device detection) · performance-digest
  (needs an aggregator) · terms-changed (no agreement-change event exists). Balance>0 guard + owner isolation both
  mutation-proven; an unmapped real event (`reservation.cancelled`) yields nothing (we defer, never invent).
- **SG4 — guest transactional emails (`6c51271`).** Nine email-safe Mailables on a shared brand layout (the
  owner-invite system; the on-screen frosted design isn't email-safe). Integer money via `MoneyFormatter` (same
  figure as folio/PDFs). **Real triggers now:** booking-confirmation (`reservation.confirmed`) · cancellation
  (`reservation.cancelled`) · refund (`payment.refunded`, successful only) via `SendGuestTransactionalEmails` —
  same bus/idempotency, but ⚠️ **a mail failure is caught so a booking never rolls back on an SMTP hiccup** (the
  correct transaction/side-effect boundary) — and to the guest's OWN address only (no cc/bcc; no address → no
  send). Payment-reminder = the real `emails:send-payment-reminders` command (scheduled daily; balance from the
  shared `FolioTotals`). **Template built · trigger deferred honestly:** pre-arrival (scheduler; access code only
  when a real one exists — never fabricated) · review→Phase 5 · waitlist→Phase 4 · verify→Phase 4 · winback
  (scheduler; NO fabricated discount). **Email Previews** (`/app/email-previews`) render every email with real
  sample data, sending nothing. Root-cause typing: added `@return` generics to `Folio`/`Payment::reservation()`
  + `Reservation::property()` and `@property $address` to `Property` (benefits all callers).
- **SG5 — owner/operator email delivery + Email Templates + consolidate (this gate).** ⭐ **The SG2 email toggle
  is no longer cosmetic:** `FeedNotification::via()` now adds the `mail` channel through the SAME gate
  (`allows(email)`), and `toMail()` renders the branded `emails.notification` view to the user's own address.
  🪤 **Mutation-proven** (force `mail` on unconditionally → the off-toggle test fails). **Email is selective by
  default** — a per-channel `email_default` in the catalog (financial/security/urgent ON, routine operational
  OFF), resolved in ONE `defaultFor()` so the gate and the screen can't disagree; so activating email doesn't
  bury users in mail. Proven from the real seed: only email-default-ON events emailed (owner "Your statement is
  ready" → the owner only; operator "Statement finalized"/"Urgent work order" → the 3 staff only; NO "New
  booking"/"Balance due" emails), isolation holding on the email channel. **Email Templates screen**
  (`/app/email-templates`, back-office gate) — **HONEST CHOICE: the VIEW is real** (every template's subject,
  trigger status, and merge variables + a live-preview link), **editing is deferred** ("Edit templates · Soon",
  NOT a fake save — a per-tenant template store + variable substitution + Mailables reading overrides is a genuine
  subsystem). Guest-level email preferences stay a Phase-4 (guest-portal) concern; production email delivery
  stays account-gated (a provider; Mailpit is dev).

**Phase-3 invariants (added to the register):** preferences gate delivery on BOTH channels (in-app + email),
never cosmetic; always-on security notifications can't be suppressed; notification isolation (owner ↔ operator,
per-user); **a guest-email/notification-mail failure NEVER rolls back the domain transaction that triggered it**;
no PII in domain-event payloads or logs (record WHICH changed, never values); guest emails go to the guest's own
address only.

**Verification:** full suite green on MySQL (Phase 3 added the notification core/preferences/wiring/email
tests) — see the count in `PROJECT-STATE.md`; PHPStan L5 + Pint clean; `migrate:fresh --seed` clean; **Mailpit
(real SMTP, dev) shows the real emails** — booking/refund/reminder to the guest, statement/payout/urgent to the
right owner/operator only. Two `git checkout` traps hit + recovered (an uncommitted `via()` edit reverted during a
mutation pass, twice — re-applied and re-proven both times; the lesson: mutation-restore an untracked/working-tree
guard with perl or Write, not `git checkout`). **NEXT = Phase 4** (guest auth + the Guest Portal core).

### 2026-07-20 — Phase 4a COMPLETE: the GUEST AUTH FOUNDATION (a second auth realm, 4 gated sub-gates)

**The most security-sensitive greenfield in the project — a SECOND auth realm for guests, alongside the
operator/owner `web` realm, WITHOUT disturbing it.** Commits `524a1d3` → `70f4d02` → `e2710ff` → this gate. The
existing auth chapter (login · invites · reset · change-password · 2FA) is fully intact throughout (asserted).

- **SG1 — Guest Authenticatable (`524a1d3`).** Guest implements the Authenticatable / CanResetPassword /
  MustVerifyEmail contracts + traits (mirroring the framework `User`). New `guests` columns: `password`
  (nullable — a booking exists before the account is claimed; `hashed` cast), `remember_token`,
  `email_verified_at`, and **`email_hash`** — a BLIND INDEX (keyed HMAC of the normalised email), because
  `guests.email` is encrypted (non-deterministic → not queryable), kept in lock-step with the email by a saving()
  hook. A SEPARATE `guest` guard + custom `GuestUserProvider` (retrieves by `email_hash`, CLAIMED accounts only,
  querying WITHOUT the tenant scope — guest auth is pre-tenant, the org is derived from the resolved guest: the
  one legitimate cross-tenant read). `Guest::hasPortalAccount()` = password AND verified. GDPR `anonymize()` now
  ALSO destroys the account. **Realm separation mutation-proven** at guard + provider level (a guest is not a web
  user and vice versa; `web->check()` false for a guest → /app + /owner reject by construction).
- **SG2 — booking-tied claim + email verification (`70f4d02`).** A guest claims access for their BOOKING email:
  enter email → if it has a real booking (blind index) + no account → a native HMAC-`signed`, expiring link (the
  MustVerifyEmail primitive; no token stored, no PII in the URL) → set password → claimed + verified + signed in
  on the guest guard. Wires the **Phase-3 Verify Email trigger** (was template-built, deferred). **Booking-tied +
  no-enumeration mutation-proven** (bypassing the has-booking check makes a no-booking email send a link → the
  test fails; the response is identical whether or not a booking/account exists, including the already-claimed
  path). ⚠️ **DIVERGENCE (recorded):** the design's "Join for member rates" FREE sign-up is NOT built — accounts
  are booking-tied; a guest with no booking cannot create one.
- **SG3 — guest login + password reset (`e2710ff`).** Login on the `guest` guard (never web; rate-limited per
  email+IP in a `|guest|` bucket; session regenerated; generic failure; only claimed+verified resolves — the
  provider now requires both). Reset on a SEPARATE `guests` broker + `guest_password_reset_tokens`. ⚠️ **#28
  through the reset surface:** the token table keys on the BLIND INDEX (`getEmailForPasswordReset → email_hash`),
  never plaintext; the reset link is TOKEN-ONLY (no email in the URL); the ONE shared `Guest::changePasswordTo()`
  hash+rotate path (mirrors `User`'s). **Realm separation mutation-proven** (making login attempt on the `web`
  guard breaks guest login entirely — a guest's creds match no `User`, so the guest guard is load-bearing). ⚠️
  Caught in the browser pass: `redirect()->intended()` shares `session('url.intended')` across guards → a stale
  /app|/owner target could cross realms → switched to a direct redirect (intended() is unsafe across auth realms).
- **SG4 — Guest Account + guest isolation + consolidate (this gate).** The authed guest's OWN profile
  (name/phone/country/language — email read-only), change-password (current-verified, `current_password:guest`,
  via the shared `changePasswordTo()`), and account removal. 🔒 **GUEST ISOLATION mutation-proven** — everything
  resolves from the guest guard, NEVER a request id (no `{guest}` param); resolving from a request id instead lets
  A edit B → the isolation test fails. Path-aware `redirectGuestsTo` (a `/guest/*` bounce goes to the GUEST login,
  never the operator login). ⚠️ **CHOICES (stated):** EMAIL change DEFERRED (it's the login identifier + drives
  the blind index → needs re-verify + re-index). ACCOUNT DELETION = **revoke portal access** (null the auth
  fields), KEEPING the guest row + PII + bookings — SAFER than an instant self-anonymize (which would strip a
  guest's name off an upcoming booking / operator records); full GDPR erasure stays operator-mediated
  (`Guest::anonymize()`). This DIVERGES from the design's "This can't be undone" framing (the UI says "remove
  portal access", honestly).

**Phase-4a invariants (register):** GUEST REALM SEPARATION — a guest authenticates only on the `guest` guard and
can never reach /app or /owner (by construction; mutation-proven); GUEST ISOLATION — a guest sees/edits ONLY their
own record (resolved from the guard, never a request id; mutation-proven A-can't-reach-B); BOOKING-TIED CLAIM — an
account exists only for an email with a real booking; NO ENUMERATION across claim/verify/login/reset; #28 EXTENDED
— the blind index keeps the encrypted email queryable for auth without storing plaintext, and the reset token
table + link carry only the hash/token, never the plaintext.

**DEFERRED / ACCOUNT-GATED:** the Guest Portal itself (Trips, bookings, Account detail) → Phase 4b/4c on this
foundation; guest email-change → a later re-verify flow; guest ID verification (KYC) → account-gated; production
email delivery → a mail provider (Mailpit is dev). **Seed:** a claimed demo guest (demo.traveller@vyrobel-demo.test
/ password, booking-tied) so 4b has a real guest login.

**Verification:** guest-auth suite (22: foundation 5 · claim 6 · login/reset 6 · account 5) green; the existing
operator/owner auth UNREGRESSED; full suite green (count in `PROJECT-STATE.md`); PHPStan L5 + Pint clean;
`migrate:fresh --seed` clean; browser + Mailpit verified (claim → Verify Email → set password → home; login →
home, /app + /owner blocked; reset email token-only + blind-index token table; account self-edit persists).
**NEXT = Phase 4b** (the Guest Portal core, on this auth foundation).

## 2026-07-21 — PHASE 4b: the GUEST PORTAL CORE (Trips · Reservation · Manage · Check-in · Arrival) — invariants #32–33

Built on the 4a auth foundation, four gated sub-gates, each guest-isolated + mutation-proven, then pushed:

- **SG1 — Guest Trips** (`28aa439`) — the post-login landing (`/guest/trips`): the authed guest's own bookings,
  upcoming/past, resolved through `Guest::reservations()` (`lead_guest_id`), NEVER a request id. New: the guest
  portal shell (`<x-guest.shell>`), `App\Support\Guest\GuestTrip::card()` (status via `ReservationStage`, money
  via `FolioTotals`+`MoneyFormatter` — the same resolvers the operator/owner surfaces use, so the guest can't
  disagree with them), `ResolvesGuest` trait (the single guest-resolution point), `SetTenantForGuestRequest`
  (sets the guest's OWN org as tenant context so portal reads resolve — explicitly NOT the isolation; the
  isolation is resolving through the guest's relations). Isolation mutation-proven: `Reservation::query()` leaks
  B's booking to A.
- **SG2 — Guest Reservation detail** (`9cac64b`) — one owned booking: stay, charges/balance (reconciling to the
  folio via `GuestReservationView`), a timeline that DELIBERATELY omits the operator staff actor (PII restraint).
  Payment "Settle" stubbed → 4c. Isolation mutation-proven: `Reservation::findOrFail` lets A open B → 200.
- **SG3 — Guest Manage Booking** (`79151f1`) — **THE OPERATOR-CONFIRMED-REQUEST PATTERN (invariant #32).** A
  guest changing dates / adding extras / cancelling touches inventory / the folio / money, so EVERY guest action
  is a `pending` `guest_change_requests` row the operator confirms — the guest NEVER runs the real write and
  can't self-approve. New: `GuestChangeRequest` + `GuestChangeRequestService` (`confirm`/`decline` are STATUS
  ONLY; the real date-change / cancel-and-refund / folio charge stays the operator's certified tools, deferred
  honestly), a REAL read-only date-change quote through the SAME rate path the booking engine uses
  (`BookingEngineService::priceOver()` made public) that says "no published price yet" rather than fabricate a
  figure, and the operator confirm/decline screen (`/app/guest-requests`, guest NAME only · #28). Proven: a real
  cancel injected into the request path fails 3 tests; guest can't self-approve (web guard refuses at `/app`);
  cross-org operator confirm → 404.
  - **🔐 A 500 the green suite MASKED, found by opening it in a browser.** The operator confirm/decline
    route-model-bound `{guestRequest}`, but `SetTenantForWebRequest` is APPENDED to the web group so it runs
    AFTER `SubstituteBindings` — the scoped `GuestChangeRequest::first()` resolved with NO tenant context and
    threw `TenantContextMissing` (HTTP 500). The test passed only because the `TenantContext` singleton lingered
    from `seedScenario` in the same process. Fixed by resolving a STRING id INSIDE the action (the established
    operator-controller pattern — every other operator controller already does this; RMB of a scoped model is the
    anti-pattern), scope intact → cross-org still 404s. The test was HARDENED with `TenantContext::forget()` so it
    exercises the real middleware path — fixing the test's blind spot, not just the bug. The lesson (again):
    static checks + a green suite cannot see a page that 500s.
- **SG4 — Guest Check-in + Arrival Info** (`this commit`) — **DOOR-CODE / ARRIVAL ISOLATION + TIMING (invariant
  #33).**
  - **Online check-in** is the GUEST's pre-check-in intent — it stamps `reservations.guest_checked_in_at`, a
    submitted flag DISTINCT from the operator's authoritative check-in (`FrontDeskService::checkIn` →
    `status='checked_in'`, where the real arrival guards run). The guest confirming their details NEVER moves the
    booking's status. Proven: injecting `status='checked_in'` into the guest submit fails the test.
  - **Passport / ID capture is ACCOUNT-GATED KYC** — an honest "coming soon" stub; no image captured, stored, or
    "verified".
  - **Arrival Info** — directions (the property's real address, the SAME source as the Phase-3 pre-arrival email
    via the shared `ArrivalPack::directions()` — one rule, no drift), check-in/out times, and the sensitive
    access secrets. A SIMPLE arrival store was added on the physical `units` (`door_code` + `wifi_password`
    **encrypted at rest**, `wifi_ssid`, `arrival_notes`). The door code is guarded TWICE: (1) resolved through
    the guest's own relation (foreign → 404), (2) released by the arrival WINDOW inside `ArrivalPack`
    (`LEAD_DAYS=2` before check-in through check-out, and never for a cancelled booking) — a code exposed months
    early or to the wrong guest is a physical-security leak. When no real code exists it says "collect your key at
    reception"; before the window it says "closer to arrival" — never a fabricated code. Copy-to-clipboard for
    code/wifi. Local recommendations honestly "coming soon". Proven: A opening B's arrival → 404 (mutation-proven
    — bypassing the guest relation lets A see `BBB-SECRET`); forcing `released=true` fails the withhold test.
  - **Demo:** the pre-arrival window is real (days, not months) yet the demo must SHOW a released pack — so guest1
    gets a near-term stay (arrives tomorrow, priced + available over just those dates) whose code IS released,
    ALONGSIDE the far Sep stay whose code is still withheld: both states demoable. Demo door codes/wifi seeded on
    the units (flagged demo by living in `DemoSeeder`).

**NEW INVARIANTS.**
- **#32 — Guest-initiated changes are OPERATOR-CONFIRMED REQUESTS.** A guest can propose a date-change / add-on /
  cancellation, but only as a `pending` request record; the guest never performs the real inventory / folio /
  refund write and can't self-approve (the confirm route is `/app`, refused to the guest realm). The operator
  confirms (status only) and then applies the real change with their certified tools. Same shape as the
  operator/owner boundary: the guest may ask, the operator acts.
- **#33 — Door-code / arrival-info isolation + timing.** The arrival pack — ESPECIALLY the door code — is shown
  ONLY for the authed guest's OWN booking (resolved through their own relation, never a request id) AND only
  within the arrival window (withheld before it, and for cancelled bookings). Unset fields are honest; a code is
  never fabricated. `door_code`/`wifi_password` are encrypted at rest beneath both guards.

**DEFERRED (honest, not faked):** online payment / Apple Pay / card → **Phase 4c** (Stripe); wallet + receipts →
4c; guest ID verification (KYC) → account-gated; guest reviews → **Phase 5**; the operator's real execute-on-
confirm of a guest change request → the operator's existing guarded tools (front desk / reservations / folio);
local recommendations → later.

**Verification:** Phase-4b guest-portal tests green (Trips/Reservation isolation · Manage 7 · Check-in+Arrival 8);
full suite **773 / 6738** green on MySQL; PHPStan L5 + Pint clean; `migrate:fresh --seed` clean; **browser-verified
end-to-end** both portals — guest requests a date change → pending → operator confirms → status-only (booking
untouched); guest opens the near-term stay → **released door code + copy** + wifi; the far stay → **withheld**
("closer to arrival"); online check-in → submitted, `status` unchanged. **NEXT = Phase 4c** (guest money +
engagement: payments/wallet/receipts + reviews).

## 2026-07-22 — PHASE 4c: GUEST MONEY + ENGAGEMENT — **THE GUEST PORTAL IS COMPLETE** — invariants #34–35

Four gated sub-gates, each guest-isolated + mutation-proven, then pushed. This completes the Guest Portal.

- **SG1 — Payments + Folio Receipt + money PDFs** (`4e172e0`) — the guest's balance + history across their OWN
  bookings via the shared `FolioTotals` (the exact operator/owner figures, reconciling to the cent), a per-booking
  Folio Receipt screen, and five real PDFs (`GuestDocumentController`): Folio Receipt + Statement reuse
  `FolioDocument`; new **VAT Invoice** (`GuestInvoiceDocument` — net + `type='tax'` = gross, reconciling *by
  construction*, the right way for a legal tax doc) and **Refund Receipt + Credit Note** (`GuestReversalDocument` —
  from real succeeded refunds; rendered ONLY when a real refund exists, else 404 — never fabricated). 🪤 The card
  charge / Apple Pay is DEFERRED (account-gated Stripe, same as New-Reservation card capture): balance shown +
  honest "Soon", no faked payment, no raw PAN, no guest payment write path. Isolation mutation-proven on the
  sharpest leak — bypassing the guest relation in the PDF controller lets A download B's financial documents.
- **SG2 — the Wallet / Credits LEDGER** (`9353d16`) — **invariant #34.** `guest_credit_ledger` mirrors
  `owner_ledger`: `direction` credit/debit · positive `amount_minor` · `reverses_id` self-FK (correct-by-reversal)
  · polymorphic `source` · `expires_at`. **Deliberately NO balance column** — the spendable balance is DERIVED
  (Σ live credits − Σ debits) in `GuestWalletService`. **No overdraw** — `debit`/`applyToBooking` throw
  `WalletOverdrawException` BEFORE any write when the amount exceeds the derived balance; expired credit is excluded
  (query-time; strict FIFO lot accounting is a stated seam). **Redeem a code** — real `promo_codes`, single-use
  (`max_redemptions` + once-per-guest); invalid/expired/already-used refused honestly. **Apply to booking** — a real
  debit AND a folio credit that reduces what's owed, via a new additive `FolioService::applyWalletCredit()` (a
  negative discount line through the single folio write path; `posted_by` null — a guest request's default guard is
  the guest, so it never reads `Auth::id()`, avoiding the FK trap `postCharge` would hit). Both models registered in
  the morph map. Mutation-proven: neutering the overdraw guard lets a debit exceed the balance; dropping the
  `guest_id` filter surfaces another guest's credit. Deferred honestly: loyalty tiers, referral auto-earn.
- **SG3 — Guest Messages** (`5006c7b`) — **invariant #35.** The guest side of the EXISTING Unified Inbox (extended,
  not reimplemented — the third participant surface on one inbox, after operator + owner). `InboxService` gains
  `postGuestMessage()` + `markReadByGuest()`, the exact mirror of the owner methods: a guest message is `inbound`
  (into the org — the operator reads it the other way up), `author_user_id` null, `sender_label` the guest NAME only
  (#28). It bumps `unread_count` (the operator's badge); `markReadByGuest` touches only the new `guest_last_read_at`,
  NEVER `unread_count` (the Wave-3 owner trap, avoided the same way). Threads resolve only through the authed guest's
  `guest_id` (never RMB). Mutation-proven: bypassing the filter lets A open another guest's AND an owner↔operator
  thread. GDPR (#25): `Guest::anonymize()` already redacts these bodies — proven for a portal-authored message.
- **SG4 — Wishlist + Waitlist** (`this commit`) — real, guest-owned stores. `guest_wishlist_items` (saved room
  types; save is idempotent; a "Save to wishlist" button on the public storefront room view, shown only to a
  signed-in guest, ties browse → wishlist). `guest_waitlist_entries` (join for sold-out dates — exact dates OR a
  flexible month; re-confirm extends the expiry; leave). Both self-only, resolved through the guest's `guest_id`;
  isolation mutation-proven (A can't remove B's item / leave B's waitlist entry). 🪤 Deferred honestly: the notify
  TRIGGER ("a spot opens" = a cancellation freeing a waitlisted date → Phase-3 email) + SMS (account-gated) + auto-
  hold (holding inventory has real availability implications, would go through AssignmentService) — the store +
  re-confirm + expiry are real; the trigger/hold are a stated seam, not faked.

**NEW INVARIANTS.**
- **#34 — the guest WALLET is an append-only ledger.** Balance DERIVED (no balance column), integer minor units
  (#17), correct-by-reversal (owner_ledger/folio discipline). It can NEVER overdraw — a debit beyond the derived
  spendable balance is refused before any write, and expired credit doesn't count toward it.
- **#35 — guest inbox isolation.** A guest sees ONLY their own thread(s) (resolved through `guest_id`, never RMB) —
  never another guest's, never an owner↔operator thread. A guest-side read moves only `guest_last_read_at`, never the
  operator's `unread_count`. (The same shape as the owner-inbox extension in Wave 3.)

**THE GUEST PORTAL IS COMPLETE** — auth foundation (4a) + Trips · Reservation · Manage (operator-confirmed requests)
· Check-in · Arrival (4b) + Payments · Folio Receipt · money PDFs · Wallet · Messages · Wishlist · Waitlist (4c),
every screen guest-isolated + mutation-proven, on real data under the honesty rule.

**DEFERRED / ACCOUNT-GATED (honest, not faked):** the card charge / Apple Pay → account-gated Stripe; SMS alerts →
account-gated; loyalty tiers → a loyalty subsystem; referral auto-earn → deferred (a referral credit is real only
when earned); the waitlist notify-trigger + auto-hold → a stated seam (a cancellation listener + Phase-3 email +
AssignmentService hold); production mail provider → Mailpit is dev.

**Verification:** Phase-4c guest tests green (Payments 8 · Wallet 7 · Messages 5 · Saved 4); full suite **797 /
6839** green on MySQL; PHPStan L5 + Pint clean; `migrate:fresh --seed` clean; **browser-verified end-to-end** —
Payments hub reconciles (Paid €738); a real 879KB `%PDF` downloads in the live session; wallet €35 derived → redeem
`WELCOME10` → €45 → apply €20 → wallet €25 AND booking owed €184.50 → €164.50 (both sides reconcile); guest sends a
message → it lands in the operator's existing inbox (unread bumped); a room saved from the public storefront
appears on the Saved page. **NEXT = Phase 5** (the Reviews module — guest review → moderation → operator/owner
surfaces).

## 2026-07-23 — PHASE 5: the REVIEWS MODULE (one module, five surfaces) — invariants #36–37 — COMPLETE

Four gated sub-gates, each guard mutation-proven, then pushed. One cohesive module across guest · moderation ·
operator · owner · public storefront.

- **SG1 — Guest Review** (`83e9d63`) — a guest reviews a REAL completed stay. New `reviews` model (tied to one
  reservation, unique = one review per stay; denormalizes the physical `unit_id` + `room_type_id` from the stay's
  assigned unit — so the owner + storefront surfaces query without re-resolving, and the review is FROZEN to the
  unit stayed in). rating (1–5) · tags · body · visibility (public/private) · status (pending/published/rejected).
  `ReviewService` is the single write path. 🔒 TIED TO A REAL COMPLETED STAY (mutation-proven both ways): the
  reservation resolves through the guest's own relations (foreign → 404) AND must be a completed (PAST) stay
  (future/cancelled → 404); one per stay. A public review is `pending` (never auto-public); "send privately to the
  manager" sets `visibility=private` (operator feedback, never published). **Closed two Phase-3/4b deferrals:** the
  Review-Request email now fires on the real `reservation.checked_out` event (Mailpit-proven), and the Guest Trips
  card shows a "★ Review your stay" link → "★ Reviewed". GDPR: `anonymize()` de-identifies the review (author →
  "Guest") — it flows from the guest anonymization, no review-table change; no PII in the `review.submitted` event.
- **SG2 — Moderation + the shared rating derivation** (`fcf52fb`) — **invariant #37.** `ReviewService::publish()`
  (a pending public review → published; a PRIVATE review can NEVER be published — the guest's choice is binding)
  and `reject()` (→ rejected, unpublishing a published one). `App\Support\Reviews\ReviewRating` is the ONE
  rating-derivation definition (mirrors FolioTotals): `publicScope()` = the single source of "publicly visible"
  (visibility=public AND status=published); `forRoomType`/`forUnit`/`forProperty` → `{count, average, has_reviews}`,
  average DERIVED (never stored), no reviews → `has_reviews=false` ("No reviews yet", never a fabricated number).
  Mutation-proven: dropping the `status=published` filter lets a pending review count → caught.
- **SG3 — Operator Reviews + response + moderate** (`814806c`) — `reviews.view` + `reviews.manage` added to the
  catalog (a reputation task, NOT Front Desk; granted to Hotel Manager / Marketing Manager / Guest Support Agent —
  and Group Admin, to keep the privilege-subset rule intact: adding `reviews.*` to Hotel Manager broke Group
  Admin's ability to assign it — a real regression `OperatorRolesTest` caught). `OperatorReviewsController`
  (`/app/reviews`): list (guest NAME only #28), respond, publish/reject; string `{review}` resolved tenant-scoped
  in-action (never RMB); index on `reviews.view`, actions on `reviews.manage`, controls `@can`-gated.
  **Closed the Phase-3 deferral:** `review.submitted` dispatches `NewReviewNotification`, preference-gated on the
  `new_review` catalog key (which existed since Phase 3, trigger deferred). Response-automation + review-gating
  DEFERRED honestly (the screen says so); deceptive review-gating deliberately NOT built. Mutation-proven: dropping
  `can:reviews.manage` lets Front Desk publish → caught.
- **SG4 — Owner Reviews + public storefront** (`this commit`) — **invariant #36.** Owner Reviews (`/owner/reviews`,
  the pre-existing "Soon" nav item now wired) — READ-ONLY (the operator owns the response); the owner sees the
  PUBLISHED reviews for THEIR OWN units, resolved through the owner-scoped `OwnerPortalService::units()` (owner→units,
  the OwnerScope boundary). 🔒 OWNER ISOLATION mutation-proven (bypassing the owner scope lets A see B's unit
  reviews → caught). Public storefront: the room-detail + browse cards show the DERIVED rating + the published
  reviews (via the shared `ReviewRating`/`ReviewList`); only published shown (a pending/private review never
  appears); no reviews → "No reviews yet"; the byline is `GuestName::reviewByline` = first name + last initial only
  (#28), anonymized → "A guest".

**NEW INVARIANTS.**
- **#36 — reviews tie to REAL completed stays.** A guest reviews only their OWN completed (checked-out/PAST)
  booking — never a future/cancelled/foreign/not-their stay — and at most once per stay. The review freezes the
  physical `unit_id`/`room_type_id` at review time. Owner isolation: an owner sees reviews for THEIR OWN units only.
- **#37 — review moderation + rating are REAL.** A submitted review is `pending` (never auto-public); only a
  published+public review is publicly visible (a pending/rejected/private one never is; a private review can never
  be published). Every rating is DERIVED from published+public reviews via the shared `ReviewRating` — never a
  stored/fabricated number; a subject with none shows "No reviews yet".

**THE REVIEWS MODULE IS COMPLETE** across five surfaces (guest · moderation · operator · owner · storefront), on
real data under the honesty rule. **CLOSED DEFERRALS:** the Phase-3 Review-Request email + new-review notification,
and the 4b Guest Trips review link — all now wired real.

**DEFERRED HONESTLY:** response-automation + review-gating → a subsystem (the manual response + moderation is real;
deceptive gating is deliberately not built) · production mail provider → Mailpit is dev.

**Verification:** Phase-5 tests green (Guest Review 8 · Moderation 7 · Operator 4 · Owner+Storefront 3); full suite
**819 / 6910** green on MySQL; PHPStan L5 + Pint clean; `migrate:fresh --seed` clean (a completed stay + published
reviews seeded); **browser-verified end-to-end** — a guest reviews their completed stay (pending), the operator
publishes it + the new-review notification lands in the feed, the owner sees only their unit's review, and the
public storefront shows the derived rating (★5 / ★4) + the published review with a #28 byline + the operator's
response. **NEXT = Phase 6** (multi-property + Analytics).

## 2026-07-23 — PRE-PHASE-6a TOTAL-RECALL CONSOLIDATION (documentation only, no code changes)

A full context snapshot before Phase 6a, verified against the actual repo. **This entry is the resume anchor: the
product now has THREE full user-facing apps + a public storefront + hardened multi-realm auth, all on the certified
backend; Phases 0–5 of the phased plan (`docs/GAP-ANALYSIS.md`) are COMPLETE.**

**Ground truth (verified this consolidation):** HEAD `8d33d8e` (the actual HEAD is this doc commit, one ahead — the
"advances by one" convention: the docs name HEAD one commit behind because the commit that writes these words IS the
new HEAD). Branch `main`, working tree clean, local == origin (all Phase-0→5 work pushed). Suite **819 tests / 6910
assertions, all green on MySQL 8** (`vyrobel_test`). **PHPStan L5 clean · Pint clean · `migrate:fresh --seed`
clean.** 97 migrations · 49 service classes · ~50+ styled/browser-verified screens · **invariants #1–37**. (One
stray Pint formatting nit in the Phase-4c `guest_credit_ledger` migration — a docblock `@see` FQCN — was auto-fixed
here so the snapshot is genuinely clean; formatting only, no logic.)

**THE COMPLETE BUILD ARC (all recorded above in dated detail; summarized here as the index):**
- **Original build (pre-plan):** Phase 1 core (tenancy/RBAC teams-mode · ULIDs · integer money · inventory +
  AssignmentService [the no-double-booking guarantee] · availability · guests/PII encryption · reservations · folios
  + FolioService · housekeeping) · Phase 2 (Channex + the sync-reliability backbone · Stripe, validated live once ·
  public booking · staff actions) · Owner accounting (attribution → append-only `owner_ledger` → statements →
  expenses → payouts + OwnerContext isolation) — all CERTIFIED. Then: the design pass + browser-verification · Auth
  A/B + password reset + Mailpit · demo-readiness (photos + PDFs) · Maintenance · the Unified Inbox (interface +
  ManualChannel + deferred channels) · the planning docs (ROADMAP/TIER1-SPEC/DESIGN-COVERAGE) · **Wave 1** (owner
  long-tail, 9 screens; OwnerOccupancy/RailLabel/assertNoRawBlade) · **security: /app authz gate #26** · **over-refund
  guard #27** · **Wave 2** (reservations + Guests CRM, 5 screens; ReservationStage/GuestSpend/GuestHistory; notes
  redaction) · **security follow-ups: terminate() guard #29 · the ENFORCED permission catalog #30 · the anonymize()
  UI trigger** · **Wave 3** (owner inbox, owner_id + BelongsToOwner) · **Wave 4 CORE** (Operator Rates · New
  Reservation [3 guards, no raw PAN] · backed Settings; MoneyInput/UnitAvailabilityService) · **Change Password** ·
  **2FA/TOTP #31**.
- **The customer signal + the plan:** Fatima liked the demo AND the prototype → "build the real product like the
  prototype" (SUPERSEDED "no signal, don't build blind"). The master `GAP-ANALYSIS.md` audit corrected multi-property
  from an L to an M (the schema already holds N properties/org).
- **The phased build (all gated, mutation-proven, pushed):** **Phase 0** (fix & harden: channels.status writer · dead
  enums resolved · QA-WALKTHROUGH) · **Phase 1** (operator buildable set: Group Booking · Search · Roles · Maintenance
  Costs/Settings · Profile) · **Phase 2** (owner buildable set: Bookings/Detail · Block Calendar · Owner Rates · Tax
  Center · Payout Method · Add Unit · Help) · **Phase 3** (notifications feed + typed preferences + emails) · **Phase
  4a** (guest auth foundation: the guest guard + blind index + booking-tied claim) · **Phase 4b** (Guest Portal core:
  Trips · Reservation · Manage [#32] · Check-in/Arrival [#33]) · **Phase 4c** (Guest Portal money + engagement:
  Payments/PDFs · Wallet ledger [#34] · Messages [#35] · Wishlist/Waitlist — THE GUEST PORTAL COMPLETE) · **Phase 5**
  (Reviews module: Guest Review [#36] · moderation + ReviewRating [#37] · Operator Reviews · Owner Reviews ·
  storefront).

**NEXT = Phase 6a** (multi-property): the `->first()` refactor (a COMPLETE grep-clean sweep — the schema already
holds N properties/org, so a missed call site is a wrong-property bug), then a property selector, property CRUD, and
the Portfolio screen — proven before Phase 6b (Analytics) builds on it. See `docs/PROJECT-STATE.md` for the full
resume snapshot (invariants #1–37, the tagged register, the account-gated tail, the meta-lessons).

### 2026-07-21 — Phase 6a COMPLETE: MULTI-PROPERTY (property context/selector + the complete `->first()` refactor + property CRUD + Portfolio; 2 gated steps)

**The gap Phase 6a closed (the audit's most consequential correction).** Multi-property was NOT a schema epic — the
schema already held N properties per org (`properties.organization_id` a plain FK; the `property_user` staff pivot
with live `User::properties()`/`Property::staff()`; `property_id` on `channels`/`groups`/`rate_plans`). The real
limitation was a UI/QUERY convention: ~10 call sites resolved "the property" as
`Property::query()->orderBy('name')->first()`, which **silently returns an ARBITRARY property once an org runs more
than one** — a wrong-property read, or worse a wrong-property WRITE (a booking landing in the wrong property). So this
was re-scoped **L → M** and built as a CORRECTNESS refactor + a selector + CRUD + Portfolio. The two steps:

**⛔ STEP 0 — the audit (read-only).** Enumerated every property-resolution site, tiered by risk: **Tier A** (10
explicit `->first()` sites across 8 files — reads vs writes distinguished) + **Tier B** (5 org-wide list screens that
BLEND properties with no `->first()` to flag them — the subtle ones a naive grep misses: RatesGrid, ReservationsList,
HousekeepingBoard, MaintenanceList, UnitsList). Public/slug resolution (`ResolvePublicProperty`, `BookingEngineController`)
+ non-property `->first()`s correctly left alone. Confirmed the **property-access model** and the **selector design**.

**⛔ STEP 1 — the property context/selector + the COMPLETE refactor (`5589ac0`).**
- **`App\Support\Property\PropertyContext`** (singleton, mirrors `TenantContext`/`OwnerContext`) — the current working
  property + the `property_user` allow-list (`accessibleIds`) + an explicit "all properties" roll-up (`readIds`,
  honoured by aggregate reads only). **`id()` ALWAYS returns a concrete property** (the default when "all" is active),
  so a property-scoped WRITE can never target "all". A foreign/stale selection is normalised away — never leaks.
- **PROPERTY-ACCESS MODEL (decision):** the `property_user` pivot is an **OPTIONAL allow-list** — a user with pivot
  rows is restricted to those properties; **a user with NO pivot rows gets the whole org** (empty ⇒ full org). This
  safely activates the dormant forward-hook: no existing user is locked out, single-property behaviour is unregressed,
  and no data migration is needed. `User::accessiblePropertyIds()` is the one rule.
- **`SetCurrentPropertyForWebRequest`** middleware on the `/app` group (after tenant is set) — resolves the per-user
  session selection (`current_property_id`), VALIDATED against the accessible set (a foreign/stale id falls back to the
  default; never 500s). The **topbar switcher** (`x-operator.property-switcher` → `PropertySwitchController`,
  POST→session→PRG, re-validates server-side); a single-property org shows a static label, not a dropdown.
- **`App\Livewire\Concerns\ResolvesCurrentProperty`** — captures the resolved values in `mount()` and holds them as
  **`#[Locked]`** props. **WHY (a real Livewire isolation fix):** a wire-action re-render hits `/livewire/update`,
  which is OUTSIDE the `/app` group, so the context is INACTIVE there; capturing at mount keeps render() correct across
  wire updates, and `#[Locked]` stops a client smuggling a non-assigned property id onto a wire update (cross-property
  access WITHIN an org is not caught by tenant scope). Same family as the invariant-#20 scoped-model-prop trap.
- **THE COMPLETE REFACTOR (grep-clean):** all 10 Tier-A sites resolve through the context (reads show the selected
  property; the writes — GroupBooking, NewReservation, Settings ×3, GroupBookingController — LAND in it);
  ReservationCreateController derives `property_id` from the chosen room type (correct by construction) and now honours
  the allow-list on the write path. The 5 Tier-B screens scope to the read set (selected, or all). `OperatorDashboard`
  aggregates over the read set. **Proven grep-clean** — no property-resolution `->first()` remains (only the
  slug/public sites + docblock references to the old pattern).
- **Mutation-proven** (`tests/Feature/MultiPropertyContextTest.php`, 8 tests): selecting B makes a read/write act on B
  not the alphabetically-first A (and `->first()` is gone); the pivot allow-list refuses a switch to / booking in an
  unassigned property; "all" rolls up both; single-property unregressed.

**⛔ STEP 2 — property CRUD + Portfolio + consolidate (this gate).**
- **`App\Http\Controllers\Operator\PropertyController`** (store + edit) gated **`can:properties.manage`** (Property
  Owner / Group Admin — NOT Front Desk / Hotel Manager, #30, mutation-proven). A created property is **org-scoped** by
  `BelongsToOrganization` (never cross-org), gets a unique storefront slug, and **enters the selector** (and the
  creator's pivot if they are pivoted). An edit resolves its target by a tenant-scoped id (cross-org 404s).
- **`App\Livewire\Operator\OperatorPortfolio`** (`/app/portfolio`, `can:properties.view`) — the multi-property
  overview: an all-properties KPI strip + a card per property with **REAL/DERIVED** data only (unit counts, occupancy
  from the Availability spine via the shared `App\Support\Property\PropertyOccupancy`, arrivals from the front-desk
  read), and a status **DERIVED** from real inventory: **"Finish setup to go live"** when a property has no units yet
  (its genuine pre-operational state, not a faked status). Add/Edit affordances render only for `properties.manage`.
- **HONESTY — deferred, not faked:** the prototype's side-by-side **ADR / RevPAR / revenue comparison table is
  ANALYTICS → deferred to Phase 6b** (never fabricated). "Billed per unit" is a Phase-7 subscription concept (no
  billing subsystem exists yet) — no charge is shown or taken.
- **Anti-drift extraction:** `PropertyOccupancy` is the ONE occupancy-for-a-property derivation, shared by the
  Dashboard and the Portfolio so the two surfaces can't disagree.
- **DEMO:** a 2nd real property (**Demo Vista Apartments** — bookable, honest empty, no stays), sorted after Seaside so
  the demo default is unchanged. **Demo caveat handled, not faked:** `demo-staff` is a Hotel Manager (`properties.view`
  only) → can SEE Portfolio but not create; CRUD browser-verified as **`demo-admin` (Property Owner)** instead.
- **Tests** (`tests/Feature/OperatorPortfolioTest.php`, 6): CRUD org-scope + permission gate (Group Admin creates;
  Hotel Manager + Front Desk 403); Portfolio real data + the honest finish-setup state; tenant isolation (no cross-org
  view/edit); the full add → select → book flow isolated to the new property.

**INVARIANT #38 (multi-property) added** — see PROJECT-STATE. **Verification:** suite **833 tests / 6976 assertions**
green · PHPStan L5 + Pint clean · `migrate:fresh --seed` clean (2 demo properties) · browser-verified (switch →
Rack/Units/New-Reservation/Dashboard all follow, bidirectional, session-persistent; add a property as Property Owner →
"Finish setup to go live" → it enters the switcher). Phase 6a commits: `5589ac0` (Step 1) → this gate (Step 2).

**NEXT = Phase 6b — Analytics** (Insights + Reports, operator per-property + all-properties; owner-scoped views) — the
deferred ADR/RevPAR/revenue comparison lands here. Then Phase 7 (the SaaS layer).

### 2026-07-21 — Phase 6b COMPLETE: the ANALYTICS module (a shared metric layer + operator/owner surfaces; 3 gated sub-gates) · PHASE 6 (a+b) DONE

**The most fabrication-tempting phase, held to the honesty rule.** The prototype's analytics are heavily INVENTED —
multi-year history (2023–2025), "best September on record", named guests, generated narrative ("Saturdays sell out
first", "October is pacing 9 pts behind"). NONE of that is real, and the demo data is recent + sparse, so REAL
analytics show LITTLE — which is CORRECT. Every number in this module is a real aggregate over real bookings/folios/
inventory; a thin/empty period shows an honest empty state; the invented history/narrative is REFUSED (never rendered).

**⛔ SG1 — THE SHARED METRIC LAYER, built + proven FIRST (`8254e26`).** `App\Support\Analytics\PerformanceMetrics`
+ `MetricSet` — the ONE derivation (anti-drift family, alongside `FolioTotals`/`OwnerOccupancy`). **Basis (stated):**
stay-night attribution over `[start, end)` — RN (room-nights sold) = in-range nights of ACTIVE `reservation_units`
(reconciles to the `availability.sold` spine); RR (room revenue) = `price_minor` PRO-RATED by in-range/stay nights
(reconciles to the folio room line); AN (available room-nights) = Σ `availability.total` (property scope) or
unit-count × days (owner scope — unit-level, because a room type is SHARED across owners' units). **ADR = RR÷RN ·
RevPAR = RR÷AN · occupancy = RN÷AN → RevPAR = ADR × occupancy EXACTLY.** Scopes: `forProperties` (single or the 6a
all-properties roll-up) + `forOwnerUnits`. Also real: LOS, cancellation rate, channel mix (`reservations.source`).
Money never sums across currencies (`multiCurrency` flag, #17). **COMPUTABILITY VERDICT (honest):** ✅ REAL —
occupancy · ADR · RevPAR · room revenue · LOS · cancellation rate · channel mix (direct/OTA/walk-in/corporate/group).
📋 DEFER (UI "Soon") — lead time + booking pace (derivable from `booked_at` but too thin in sparse recent data to be
meaningful). 📋 REFUSE — the invented multi-year history + generated narrative. **RECONCILIATION PROVEN** (5 tests):
a known booking → the exact hand-calculated ADR/occupancy/RevPAR/revenue; the RevPAR identity holds exactly; a
boundary-straddling stay pro-rates to its in-range nights; empty → honest zero; owner scope isolates (A sees it, B
sees zero).

**⛔ SG2 — Operator Insights + Reports (`df9c95e`).** Scoped by the 6a property context, gated `can:reports.view` (a
management/analytics baseline — NOT Front Desk / Housekeeping / Reservation Agent, #30, mutation-proven). **Insights:**
real metric cards + a REAL month-by-month trend (only months with real bookings; honest "not enough data yet" below 2
active months) + real channel mix; lead-time/pace deferred "Soon". **Reports:** revenue = Σ REAL folio items
(room/ancillary/tax, reconciles to `FolioTotals`); the ONE real operator cost is MAINTENANCE (resolved tickets on
scope units); the prototype's other cost lines (channel commissions/cleaning/supplies/linen) have NO data source →
listed "Not tracked yet", never fabricated; net = gross − tracked costs; top units by real room revenue. **The
fabrication-absence is TESTED** (`assertDontSee 2023/2024/best September/on record/pts behind`).

**⛔ SG3 — Owner Reports + Insights + the Portfolio comparison + consolidate (this gate).** **Owner analytics**
(`App\Livewire\Owner\OwnerInsights`/`OwnerReports`, `/owner/insights` + `/owner/reports`) — OWNER-SCOPED via
`OwnerPortalService::units()` + the owner-scoped `OwnerLedgerEntry` (never a request id; a non-linked user fails
CLOSED — sees nothing, mutation-proven). **Earnings reconcile to the owner ledger BY CONSTRUCTION** via the shared
`OwnerLedgerAttribution::netPerCurrency` — the exact figure the statements show (per-unit nets sum to the total).
Occupancy/nights = the metric layer over the owner's units. **Owner Insights narrative is real-or-omitted:** the ONE
observation ("your busiest night of the week is …") is genuinely derived from the owner's real bookings and OMITTED
below 5 nights / no clear leader — never fabricated. **The Portfolio comparison** (deferred from 6a) is now the real
side-by-side ADR/RevPAR/revenue per property (this year), via the metric layer, reconciling; a property with no data
shows honest zeros. **New invariant #39.**

**Anti-drift extraction:** `PerformanceMetrics` + `MetricSet` join the shared family; the same numbers appear on
operator Insights, the Portfolio comparison, and (owner-scoped) owner analytics.

**Browser-verified:** operator Insights (Seaside this-year: ADR €128.24, trend Jul €600 + Sep €1,580, Direct+OTA) ·
Reports (room €2,180 + tax €501.40 + maintenance €225 → net €1,955, untracked "Not tracked yet") · Portfolio
comparison (Seaside ADR €128.24/RevPAR €6.86/€2,180 · Vista €0 honest empty · Portfolio group) · owner Reports (net
€705.60 = A1 €442.80 + A2 €262.80, reconciles) · owner Insights (ADR €180, "busiest night is Monday", honest thin
trend). Suite **848 tests / 7099 assertions** green · PHPStan L5 + Pint clean · `migrate:fresh --seed` clean. Phase 6b
commits: `8254e26` (SG1) → `df9c95e` (SG2) → this gate (SG3).

**PHASE 6 (a + b) is COMPLETE — the product is multi-property with real analytics. NEXT = Phase 7 — the SaaS layer**
(tenant provisioning / self-signup — where the two deferred fail-closed RBAC self-provision defects finally get fixed
· subscription billing · Owner Team). Deferred honestly out of 6b: lead time + booking pace + channel-attribution
depth (thin data); the invented history/narrative (refused).
