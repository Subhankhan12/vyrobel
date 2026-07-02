# Vyrobel — Project State (resume here)

**Read this first on a fresh session, then `docs/DECISIONS.md` for the "why".** This is a pointer map, not a copy of the code — follow the links on demand.

## Status

- **Phase 1 CERTIFIED complete** (Steps 0–9): core guarantee stack certified at Step 8 (true-parallel concurrency, PHPStan L5, Pint); Step 9 added unstyled stub views + `SetTenantForWebRequest` tenant middleware.
- **Phase 2 CERTIFIED complete — Steps 0–9 done and committed.** Per-gate commits (`git log --oneline`, oldest→newest):
  `97416c7` bootstrap → `5f89bfd` Phase 1 certified → `f30e849` Step 1 (sync backbone) → `586e279` Step 2 (channel mapping) → `ba65858` Step 3 (ARI push out) → `de2ea27` Step 4 (booking import in) → `d496b18` Step 5 (cross-system concurrency suite) → `0d5e4e0` Step 6 (Stripe) → `03ef12f` Step 7 (public booking engine) → `7f91443` Step 8 (staff action layer) → `409a89c` **Step 9 (full integrity suite & consolidation — Phase 2 CERTIFIED)** → docs-finalize (session handoff, HEAD).
- **NEXT ACTION: close the two live-integration checkpoints** per `docs/prompts/live-integration-checkpoint.md` — validates the real `ChannexClient` (Channex staging) + real `StripeClient` (Stripe test-mode) against actual accounts and reconciles any contract diffs back into the fakes. **Needs Channex staging + Stripe test-mode credentials** to run (Stripe leg can run independently if Channex isn't provisioned). **OR, if deferring those**, start the **design pass** (styled UI over the unstyled contract stubs) — **then Phase 3** (website builder, unified inbox, owner accounting, first AI agents). Neither the design pass nor Phase 3 starts without a written, gated prompt.
- **Current suite: 84 tests / 509 assertions**, all green on MySQL `vyrobel_test`. Queue = **Redis/Horizon live** (`QUEUE_CONNECTION=redis`; `database` documented as fallback), Horizon re-proven processing the `sync` queue end-to-end at the Step 9 gate.
- **⚠️ Two live-integration checkpoints remain OUTSTANDING (proven-against-fakes only, never network):** (a) Channex staging — real ARI/webhook shape + HMAC scheme are documented guesses; (b) Stripe test-mode with real keys — real `StripeClient` + SDK webhook path unexercised by CI. See DECISIONS.md Step 9 close-out.

## What Phase 1 delivered (one line per domain)

- **Tenancy / RBAC** — `BelongsToOrganization` trait (global scope + auto-fill + guard) via `App\Support\Tenancy\TenantContext`; spatie teams (team = organization), 14-role catalog (`config/authorization.php`, `RoleCatalogSeeder`, `OrganizationRoleProvisioner`).
- **Property & inventory** — mixed hotel+apartment under one property; polymorphic `media`/`documents`/`notes`/`tags`/`amenities` reused via `app/Models/Concerns/*`.
- **Rates & availability** — two-level model; `App\Services\AvailabilityService` (recompute total/ooo/blocked; read `available`). Collaborator of AssignmentService, **never** a second write path.
- **Guests** — encrypted `email`/`phone`; `Guest::anonymize()` (GDPR: null PII + purge file bytes, preserve row/id/history).
- **Reservations** — one `reservations` table, `source` distinguishes channel; **`App\Services\AssignmentService`** is the single guarded inventory write path (book/assign/move/extend/split/cancel/no-show/block).
- **Folios/payments/tax** — `App\Services\FolioService` (append-only ledger, correct-by-reversal); `PaymentGateway` interface (Phase 1: manual/offline only).
- **Housekeeping** — `App\Services\HousekeepingService` + checkout event → `GenerateDepartureCleanTask` listener; status → `unit_status_logs`.

## What Phase 2 has delivered so far (one line per step)

- **Step 1 — sync reliability backbone**: `App\Jobs\Sync\RetryableSyncJob` (retry/backoff/dead-letter) + `App\Services\SyncJobDispatcher` (idempotent enqueue), `sync_jobs`/`sync_logs` tables, Horizon+Redis live.
- **Step 2 — channel mapping**: `channels` (encrypted credential vault) + `channel_mappings`, `App\Services\ChannelMappingGuard` (blocks unmapped room_type/rate_plan both directions), `reservations.channel_id` FK closed.
- **Step 3 — outbound ARI push**: `App\Services\Channex\ChannelManagerClient` interface + `FakeChannexClient`/`ChannexClient`, `App\Jobs\Sync\AriPushJob`, `App\Listeners\EnqueueAriPushOnInventoryChange` (pure post-commit consumer, event-keyed idempotency, reads current state at execution — not enqueue time).
- **Step 4 — inbound booking import**: `ChannexWebhookController` (signature + `webhook_events` idempotency), `App\Services\BookingImportService`, `AssignmentService::importBooking()` (accept-and-flag) + `overbooking_conflicts`.
- **Step 5 — cross-system concurrency & sync-reliability suite**: `tests/Feature/CrossSystemConcurrencyTest.php` — 7 named tests proving the whole cross-system guarantee under true OS-level parallelism (50 real races, 5×5, zero flakiness) + Redis end-to-end retry/dead-letter.
- **Step 6 — Stripe**: `App\Payments\Gateways\StripeGateway implements PaymentGateway` behind `App\Payments\Stripe\StripeClientInterface` (+`FakeStripeClient`/`StripeClient`), SCA/3DS → `pending` + webhook confirm, refunds as new offsetting ledger entries.
- **Step 7 — public booking engine**: `App\Services\BookingEngineService` (search/quote/book/expire), `booking_quotes` (advisory hold — never in `sold`), `ResolvePublicProperty` middleware, `/book/{property}/*` routes (rate-limited).
- **Step 8 — staff action layer**: `App\Actions\Reservations\CheckInReservation` (new) + `App\Services\FrontDeskService` (thin delegation to existing services — no new inventory/payment/HK logic), `FrontDesk` Livewire contract stub.
- **Step 9 — full integrity suite & consolidation (Phase 2 CERTIFIED)**: audited every Step 1–8 obligation to an explicit named test; added the only two missing-as-named ARI-push tests (event-keyed idempotency: redelivered same event dedupes / different event doesn't collapse; reads current state at execution, not an enqueue-time snapshot) to `tests/Feature/AriPushTest.php`. No new features/tables. Honest gate re-run (tests/PHPStan/Pint/`migrate:fresh --seed`/Horizon-on-Redis/5× concurrency).

## INVARIANTS THAT MUST NEVER BREAK (the guarantee stack)

**Phase 1 core:**
1. **Single inventory write path** — ONLY `AssignmentService` inserts/mutates `reservation_units` or `availability.sold`. Nothing else, ever — **this now includes OTA imports**, which go THROUGH `AssignmentService::importBooking()`, never a parallel path.
2. **Two-level no-double-booking** — Level A (counts) + Level B (physical-unit non-overlap), enforced by `SELECT … FOR UPDATE` on availability rows inside one transaction. **Deterministic lock ordering** by `room_type_id` + `check_in` (deadlock-free). MySQL has no exclusion constraint — the guarantee lives entirely in this service; the concurrency test is mandatory.
3. **One reservations table** — `source` distinguishes direct/OTA/walk-in/corporate/group. Never parallel per-channel tables.
4. **Money = integer minor units + ISO currency**, via the `Money` value object / `MoneyCast`. Never a float. `folio.total_minor = SUM(folio_items.amount_minor)` (tax posted as its own line, not double-counted).
5. **PII** — `guests.email`/`phone` encrypted; GDPR erase = `anonymize()` (null PII, set `anonymized_at`, purge stored file bytes). NEVER soft-delete-and-retain PII.
6. **Append-only financial ledger** — posted `payments`/`invoices`/`folio_items` are correct-by-reversal: never deleted/edited; refunds/voids/credits (Manual or Stripe) are NEW offsetting entries. Deposit hold ≠ paid until captured. A Stripe SCA/3DS `pending` payment is settled via `FolioService::settlePayment` (webhook), never mutated in place beyond that lifecycle completion.
7. **Event direction** — `domain_events` emitted **post-commit by the write side ONLY**. `AvailabilityService::recompute` and all listeners (including the Step 3 `EnqueueAriPushOnInventoryChange`) are **pure consumers** — never emit inventory events, never touch `sold`.
8. **Enforced morph map** — `Relation::enforceMorphMap` is on (AppServiceProvider). Every polymorphic-capable model MUST be registered or Laravel throws. "Add to morph map" = per-step checklist item.
9. **Tenant scoping** — every business model uses `BelongsToOrganization`; the guard THROWS with no context; explicit super-admin bypass only. Deliberate opt-outs: `Membership`, `PropertyAssignment` (link tables), and the append-only logs / polymorphic-owner tables.
10. **ULID keys everywhere** except spatie's own tables (documented integer-PK exception; their `organization_id`/morph keys are `char(26)` to match).

**Phase 2 additions:**
11. **Advisory booking holds are NEVER counted in `sold`.** `booking_quotes` (Step 7) is a price/availability snapshot with a `held_until` timer only — it never calls `AssignmentService`. Real occupancy happens ONLY at `book()`, which re-checks availability under `AssignmentService`'s lock. Two holds on the last unit can both succeed; only one `book()` seats.
12. **OTA overbooking = accept-and-flag, never silently dropped, never inflated.** When an OTA import can't seat (sync-lag oversold it), `AssignmentService::importBooking()` still records the reservation/line as `status=overbooked` (not `active`) and raises one `overbooking_conflicts` row — `sold` never exceeds `total`. **Direct/public bookings are asymmetric: they still REJECT when full** (never overbook, never fall back to accept-and-flag).
13. **The ARI push is a pure post-commit consumer.** `AriPushJob` reads CURRENT availability/rate/restriction state at EXECUTION time (never a snapshot captured at enqueue time) — this is what makes event-keyed idempotency (`ari_push:{channel}:{room_type}:{domain_event_id}`) safe under re-ordering. It never mutates `sold`, never emits a domain event (no push→event→push loop).
14. **Inbound/outbound idempotency = unique constraint + catch-the-race, never trust check-then-act under concurrency.** `webhook_events(source, external_event_id)`, `reservations(channel_id, ota_reservation_id)`, `sync_jobs(idempotency_key)` are all real DB unique constraints; the app-level `firstOrCreate`/`createOrFirst` calls additionally catch `UniqueConstraintViolationException` and resolve to the winner's row. Proven under true concurrent delivery in Step 5.
15. **Availability recompute + `sold` mutation stay SYNCHRONOUS/inline — never `ShouldQueue`.** Only the OUTBOUND ARI push runs async on the queue. Moving inventory recompute onto a queue would open a stale-availability oversell window (standing rule locked at Phase 2 Step 0).
16. **Staff actions assemble, never duplicate.** The Step 8 pattern: a staff-facing action is a thin wrapper (1–3 lines) delegating to `AssignmentService`/`FolioService`/`HousekeepingService`/the `CheckIn`/`CheckOut` actions. Writing new inventory/payment/housekeeping logic inside a staff action is a smell — stop and reuse.

## Deferred-items register (do NOT build ad hoc; see DECISIONS.md)

**Two live-integration checkpoints (the biggest open item):**
- **Channex staging** — the ARI push request shape, the inbound webhook payload shape, and the HMAC signature scheme are all **documented guesses**, validated only against `FakeChannexClient` + simulated signed payloads. Never exercised against a real Channex account.
- **Stripe test-mode with real keys** — the real `StripeClient` (SDK-backed) and its webhook path are unexercised by CI; all tests run against `FakeStripeClient`. The webhook signature scheme IS Stripe's real documented HMAC scheme (not a guess), just never validated against a live-sent webhook.

**Phase 3+ deferrals:**
- Promo codes + deposits on the booking engine — `booking_quotes.promo_code`/`discount_minor` and `payment_authorizations` exist in schema; no engine logic wired (not exercised by search/quote/book).
- `guests.stripe_customer_id` / saved cards — deferred until the guest blind-index matching story (needed to safely reuse a saved card across bookings).
- `units.owner_id` FK → Phase 3 owners module (nullable column, no FK target yet).
- Blind-index for encrypted guest email/phone (OTA guest matching currently keyed on `external_refs` only).
- Gap-free invoice numbering (replace `max+1`) → when invoicing goes live.
- Dated out-of-order ranges (`out_of_order` is date-independent from `condition_status` in Phase 1).
- `inventory_blocks.quantity` (nullable, null=1) to "block N units" of a type.
- Tenant-custom amenities via nullable `amenities.organization_id` (currently a global catalog).
- Same-day-turnover housekeeping edge (departure tasks dedupe on `unit+date+type`).

## Regression net — `php artisan test` (MySQL `vyrobel_test`)

**84 tests / 509 assertions, all green.** "Green here = the whole cross-system guarantee holds" — Phase 1's core double-booking/tenant/ledger/GDPR/event-discipline guarantees AND Phase 2's cross-channel contention, sync reliability, and payment guarantees, together. (Step 9 added the two ARI-push nuance tests noted above; total went 82→84.)

- **True OS-level parallelism** (separate PHP processes, separate MySQL connections, shared future-timestamp barrier — never RefreshDatabase): `ConcurrentBookingTest`, `DeadlockAvoidanceTest` (Phase 1); `CrossSystemConcurrencyTest` (Phase 2 Step 5 — direct-vs-OTA race, OTA-vs-OTA race, concurrent webhook redelivery).
- **Real Redis transport + time-travel** (no real sleeps): retry/backoff/dead-letter in `SyncReliabilityTest`, `AriPushTest`, and `CrossSystemConcurrencyTest`.
- **In-process against fakes — no network ever**: `FakeChannexClient` (Channex) and `FakeStripeClient` (Stripe, real HMAC scheme) back every other test, including `AriPushTest`, `BookingImportTest`, `StripePaymentTest`, `PublicBookingEngineTest`, `StaffActionsTest`, `ChannelMappingTest`.
- Full file list: `tests/Feature/` (18 files) — Phase 1: `AssignmentServiceTest`, `AvailabilityOverlapTest`, `ConcurrentBookingTest`, `DeadlockAvoidanceTest`, `FolioLedgerTest`, `GdprEraseTest`, `HousekeepingEventTest`, `MoneyIntegrityTest`, `StubViewsTest`, `TenantIsolationTest`. Phase 2: `SyncReliabilityTest`, `ChannelMappingTest`, `AriPushTest`, `BookingImportTest`, `CrossSystemConcurrencyTest`, `StripePaymentTest`, `PublicBookingEngineTest`, `StaffActionsTest`.

## Pointer index (read on demand)

- **Vision / phases / buy-vs-build** → `docs/master-plan.md`
- **Data contract (column-level source of truth)** → `docs/schema/phase-1.md` (core) + `docs/schema/phase-2.md` (channels/sync/booking engine)
- **Build steps / gated prompts** → `docs/prompts/phase-1.md` (done) · `docs/prompts/phase-2.md` (**done — Steps 0–9 all certified**) · `docs/prompts/live-integration-checkpoint.md` (**the NEXT ACTION** — credentialed validation of the real Channex/Stripe clients + reconciliation back into the fakes)
- **The "why" behind every decision, dated, per-gate** → `docs/DECISIONS.md`
- **Code style / testing patterns** → `docs/CODING-STANDARDS.md`
- **Conventions + commands** → `CLAUDE.md`
- **Code** → `app/Services/` (AssignmentService, AvailabilityService, FolioService, HousekeepingService, BookingEngineService, BookingImportService, ChannelMappingGuard, RateAndRestrictionService, SyncJobDispatcher, FrontDeskService) · `app/Services/Channex/` (ChannelManagerClient interface + Fake/real client, ARI DTOs, webhook signature) · `app/Payments/` (PaymentGateway interface, Manual + Stripe gateways, `Stripe/` client seam) · `app/Jobs/Sync/` (RetryableSyncJob, AriPushJob, ProcessChannexWebhookJob) · `app/Actions/Reservations/` (CheckIn, CheckOut) · `app/Http/Controllers/` (Channex + Stripe webhooks, BookingEngineController) · `app/Livewire/` (staff/booking contract stubs) · `app/Models/` (57) · `database/migrations/` (60) · `tests/` (18 Feature files + `tests/Support/*_helpers.php` + race workers).

## Next up

**Phase 2 is certified against fakes (Step 9 done).** The clear NEXT ACTION is to close the biggest open risk before anything new is built:

1. **Live-integration checkpoint (do this next)** — run `docs/prompts/live-integration-checkpoint.md`: switch the config-gated real `ChannexClient`/`StripeClient` on, exercise them against **Channex staging** + **Stripe test-mode** real accounts, and reconcile every contract difference back into the fakes so CI reflects reality. It's a gated, credentialed session (needs the keys in `.env`); the Stripe leg can run independently if Channex staging isn't provisioned yet. Until this runs, the two checkpoints stay OUTSTANDING — "certified against fakes," not "against reality."
2. **If deferring the checkpoint**, the alternative is the **design pass** — styled UI for the booking engine + staff/housekeeping surfaces (currently deliberately-unstyled contract stubs; backends + JSON contracts are final, only presentation is a placeholder).
3. **Then Phase 3** — white-label website builder, unified inbox, owner accounting + payouts, the first AI agents.

Write whichever track you pick as a gated prompt the same way (`docs/prompts/`); the checkpoint prompt is already written.
