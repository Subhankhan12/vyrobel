# Vyrobel — Project State (resume here)

**Read this first on a fresh session, then `docs/DECISIONS.md` for the "why".** This is a pointer map, not a copy of the code — follow the links on demand.

## Status

- **Phase 3 · Module 1 (Owner Accounting & Payouts) CERTIFIED complete** (Steps 0–7, 2026-07-03): the wedge. Owners + effective-dated management agreements; a rule-driven, read-only revenue **attribution engine** (reconciles owner+operator to the folio room revenue to the cent); an append-only, correct-by-reversal **`owner_ledger`**; owner-attributable **expenses**; **period-locked owner statements** (pure projection, gap-free carry-forward); **payouts** (calculation + lifecycle, manual rail real; Stripe Connect a deferred skeleton); and the **owner-portal + owner-isolation** security boundary (fails closed, composes with tenant scope) — backend + unstyled contract stubs. Per-gate commits `e9ccce0`→`079ae0e` (Step 0 schema/decisions → Step 6 portal); Step 7 = certification.
- **Phase 1 CERTIFIED complete** (Steps 0–9): core guarantee stack certified at Step 8 (true-parallel concurrency, PHPStan L5, Pint); Step 9 added unstyled stub views + `SetTenantForWebRequest` tenant middleware.
- **Phase 2 CERTIFIED complete — Steps 0–9 done and committed.** Per-gate commits (`git log --oneline`, oldest→newest):
  `97416c7` bootstrap → `5f89bfd` Phase 1 certified → `f30e849` Step 1 (sync backbone) → `586e279` Step 2 (channel mapping) → `ba65858` Step 3 (ARI push out) → `de2ea27` Step 4 (booking import in) → `d496b18` Step 5 (cross-system concurrency suite) → `0d5e4e0` Step 6 (Stripe) → `03ef12f` Step 7 (public booking engine) → `7f91443` Step 8 (staff action layer) → `409a89c` **Step 9 (full integrity suite & consolidation — Phase 2 CERTIFIED)** → docs-finalize (session handoff, HEAD).
- **STRIPE live checkpoint CLOSED (2026-07-03)** — the real `StripeClient` + SDK webhook path were validated against Stripe test-mode and the fake reconciled to the real contract (see DECISIONS.md 2026-07-03; a deposit-hold null-token bug was found + fixed). **NEXT ACTION: the Channex live checkpoint is now the single outstanding one** (`docs/prompts/live-integration-checkpoint.md` Steps 1–2) — **blocked: no Channex staging account yet**. **OR** start the **design pass** (styled UI over the unstyled contract stubs) — **then Phase 3** (website builder, unified inbox, owner accounting, first AI agents). Neither the design pass nor Phase 3 starts without a written, gated prompt.
- **Current suite: 139 tests / 2747 assertions**, all green on MySQL `vyrobel_test` (Phase 1's 21 + Phase 2's 63 + Owner Accounting's 45 + Money unit tests). Queue = **Redis/Horizon live** (`QUEUE_CONNECTION=redis`; `database` documented as fallback), Horizon re-proven processing the `sync` queue end-to-end at the Phase 2 Step 9 gate.
- **⚠️ TWO live-integration checkpoints remain OUTSTANDING (deferred money/network rails; everything else is real + tested):** (a) **Channex staging** — real ARI/webhook shape + HMAC scheme are documented guesses (blocked on a staging account); (b) **Stripe Connect payout rail** — real owner disbursement (connected-account onboarding + KYC), a config-gated skeleton (`send()` throws), the manual payout rail is the real tested path. **The Stripe PAYMENTS checkpoint is CLOSED** (validated against real test-mode 2026-07-03). See DECISIONS.md 2026-07-03.

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

## What Phase 3 · Module 1 (Owner Accounting) delivered (one line per step)

- **Step 0–1 — owners & agreements**: `owners` (org-scoped party, encrypted PII, payout currency, optional linked portal user) + `management_agreements` (effective-dated; `commission_pct`, fixed fee + interval, `attribution_rules` overrides-only). `ManagementAgreement::resolveFor()` (most-specific/per-unit wins, date-in-effect). Closed the Phase 1 `units.owner_id` FK; added `channels.commission_pct` + `fees.kind`.
- **Step 2 — attribution engine (the crux)**: `App\Services\OwnerAttributionService` — READS `folio_items` + `reservation_units`, writes immutable `owner_ledger` lines; room total allocated across active lines (price-weight, night-count fallback for splits/moves); commission exact-complement; OTA cost override→channel-pct→0-unverified; whole-transaction reversal + re-attribution. `Money::percentage()`/`allocate()` (largest-remainder, sum-preserving).
- **Step 3 — expenses**: `owner_expenses` (+ `App\Services\OwnerExpenseService`) — deductible resolved per agreement → `owner_ledger` deduction; non-deductible recorded with NO ledger line (operator cost); owner-less/no-agreement flagged; void = append-only reversal. Cleaning links to real `housekeeping_task`; maintenance source is a forward hook.
- **Step 4 — statements**: `owner_statements` + `owner_statement_lines` (+ `App\Services\OwnerStatementService`, `App\Services\PeriodLockGuard`) — pure projection (net == Σ lines == ledger net); draft idempotent; finalize stamps `owner_ledger.statement_id` + locks; **gap-free post-lock carry-forward** as labeled prior-period adjustments; operator-supplied FX.
- **Step 5 — payouts**: `payouts` (+ `App\Services\OwnerPayoutService`, `App\Payouts\` `PayoutRail`/`ManualPayoutRail`/`StripeConnectPayoutRail` skeleton) — finalized-only, positive-net → payout; manual pay flips statement to paid; negative-net carries forward; append-only (`supersedes_id`).
- **Step 6 — owner portal + isolation**: `App\Support\Owner\OwnerContext` (singleton) + `OwnerScope`/`BelongsToOwner` + `SetOwnerScopeForPortal` middleware — row-level owner scope WITHIN the tenant, **fails closed**, deactivates on terminate. Unstyled `OwnerDashboard`/`OwnerStatementView` stubs (`/owner/*`) via `App\Services\OwnerPortalService`.
- **Step 7 — CERTIFIED**: 45 named integrity tests (each obligation explicit); full suite 139/2747 green; concurrency re-run zero-flake; PHPStan L5 + Pint clean.

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

**Phase 3 · Module 1 (Owner Accounting) additions:**
17. **Attribution is derived + READ-ONLY.** `OwnerAttributionService` reads `folio_items` + `reservation_units`; it NEVER mutates folios, reservations, inventory, or `availability`. Owner revenue is a projection of the folio ledger — if the two ever disagree, the folio ledger wins. Owner_share + operator_share reconciles to the folio room revenue **to the cent** (via `Money::allocate`, which is sum-preserving — no minor unit created or lost).
18. **`owner_ledger` is append-only + WHOLE-TRANSACTION correct-by-reversal.** A line is never edited/deleted; a cancel/refund/modify reverses EVERY component of a booking together (mirror rows, `reverses_id`, flipped direction) — never partial. Idempotent per `idempotency_key`. Attribution/expense posting is DB-idempotent.
19. **Owner statements are a pure projection + period-locked.** net == Σ component lines == ledger net for the period; a draft is reproducible; **finalize stamps `owner_ledger.statement_id` and locks** — a locked statement is immutable (regenerate throws). A post-lock adjustment carries forward onto the next open statement as a labeled prior-period adjustment — **gap-free: every ledger line is claimed by exactly one statement.** Cross-currency uses an operator-supplied `fx_rate`/`fx_source` (no silent float). Payouts are finalized-only, positive-net; negative net carries forward (never a reverse-charge); lifecycle is append-only (`supersedes_id`).
20. **OWNER ISOLATION — a row-level scope WITHIN a tenant, treated like tenant isolation.** `OwnerContext` + `OwnerScope` (via `BelongsToOwner` on `Unit`/`OwnerStatement`/`OwnerStatementLine`/`Payout`/`OwnerLedgerEntry`/`OwnerExpense`). **FAILS CLOSED** — active with an empty owner set returns NOTHING, never all owners. Inactive off-portal (operator/CLI/tests see all, subject to tenant scope); activated only by `SetOwnerScopeForPortal` and **deactivated on `terminate`** (no leak into operator requests). An owner reads only their own rows (another owner's specific rows resolve to null); **composes with tenant scope** (both boundaries hold together).

## Deferred-items register (do NOT build ad hoc; see DECISIONS.md)

**One live-integration checkpoint remains (the biggest open item):**
- **Channex staging** — the ARI push request shape, the inbound webhook payload shape, and the HMAC signature scheme are all **documented guesses**, validated only against `FakeChannexClient` + simulated signed payloads. Never exercised against a real Channex account (blocked: no staging account yet; inbound also needs a public tunnel).

**Closed 2026-07-03 — Stripe test-mode (validated live):** the real `StripeClient` (SDK-backed) + its `Stripe-Signature` webhook path were exercised against real Stripe test-mode and the contract reconciled into `FakeStripeClient` (see DECISIONS.md 2026-07-03). A deposit-hold null-token bug was found + fixed (`authorizeDeposit` now requires a card token; fake made faithful; `phpunit.xml` pins `PAYMENTS_GATEWAY=manual`). No longer outstanding.

**Owner Accounting deferrals (2026-07-03 — module certified; accounting is real, only these deferred):**
- **Stripe Connect payout rail** — real money movement to owners (connected-account onboarding + KYC): a separate credentialed live checkpoint, like the Stripe payments one. `StripeConnectPayoutRail` is a config-gated skeleton whose `send()` throws; the **manual rail is the real, tested disbursement path**. Now a second outstanding live-integration checkpoint alongside Channex.
- **Auto-FX provider** — owner statements use an operator-supplied `fx_rate`/`fx_source`; no automatic FX feed (deliberate — no silent float).
- **Maintenance-ticket source** for maintenance expenses — no maintenance module exists yet; the polymorphic `owner_expenses.source` hook is ready for it (master-plan `MaintenanceTicket`). Maintenance expenses are manual / unit-sourced until then.
- **`UnitFactory` test-infra debt** — hard-codes its own org (not tenant-context-aware like `ChannelFactory`/`OwnerFactory`); owner tests pass `organization_id`/`unit_id` explicitly. Fix in a dedicated pass, not mid-module.

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

**139 tests / 2747 assertions, all green.** "Green here = the whole cross-system guarantee holds" — Phase 1's core double-booking/tenant/ledger/GDPR/event-discipline guarantees, Phase 2's cross-channel contention/sync-reliability/payment guarantees, AND Owner Accounting's attribution-to-the-cent / append-only-reversal / period-locking / owner-isolation guarantees, together. (Owner Accounting added 45 named tests + the Money-allocate fuzz unit tests; total went 84→139.)

- **True OS-level parallelism** (separate PHP processes, separate MySQL connections, shared future-timestamp barrier — never RefreshDatabase): `ConcurrentBookingTest`, `DeadlockAvoidanceTest` (Phase 1); `CrossSystemConcurrencyTest` (Phase 2 Step 5 — direct-vs-OTA race, OTA-vs-OTA race, concurrent webhook redelivery).
- **Real Redis transport + time-travel** (no real sleeps): retry/backoff/dead-letter in `SyncReliabilityTest`, `AriPushTest`, and `CrossSystemConcurrencyTest`.
- **In-process against fakes — no network ever**: `FakeChannexClient` (Channex) and `FakeStripeClient` (Stripe, real HMAC scheme) back every other test, including `AriPushTest`, `BookingImportTest`, `StripePaymentTest`, `PublicBookingEngineTest`, `StaffActionsTest`, `ChannelMappingTest`.
- Full file list: `tests/Feature/` — Phase 1: `AssignmentServiceTest`, `AvailabilityOverlapTest`, `ConcurrentBookingTest`, `DeadlockAvoidanceTest`, `FolioLedgerTest`, `GdprEraseTest`, `HousekeepingEventTest`, `MoneyIntegrityTest`, `StubViewsTest`, `TenantIsolationTest`. Phase 2: `SyncReliabilityTest`, `ChannelMappingTest`, `AriPushTest`, `BookingImportTest`, `CrossSystemConcurrencyTest`, `StripePaymentTest`, `PublicBookingEngineTest`, `StaffActionsTest`. Owner Accounting: `OwnerAttributionTest`, `OwnerExpenseTest`, `OwnerStatementTest`, `OwnerPayoutTest`, `OwnerPortalIsolationTest` + `tests/Unit/MoneyAllocateTest` + shared `tests/Support/owner_helpers.php`.

## Pointer index (read on demand)

- **Vision / phases / buy-vs-build** → `docs/master-plan.md`
- **Data contract (column-level source of truth)** → `docs/schema/phase-1.md` (core) + `docs/schema/phase-2.md` (channels/sync/booking engine) + `docs/schema/phase-3-owner-accounting.md` (owners/agreements/ledger/statements/expenses/payouts)
- **Build steps / gated prompts** → `docs/prompts/phase-1.md` (done) · `docs/prompts/phase-2.md` (**done — Steps 0–9 all certified**) · `docs/prompts/phase-3-owner-accounting.md` (**done — Steps 0–7 all certified**) · `docs/prompts/live-integration-checkpoint.md` (**Stripe leg done 2026-07-03; Channex Steps 1–2 still outstanding**)
- **The "why" behind every decision, dated, per-gate** → `docs/DECISIONS.md`
- **Code style / testing patterns** → `docs/CODING-STANDARDS.md`
- **Conventions + commands** → `CLAUDE.md`
- **Code** → `app/Services/` (AssignmentService, AvailabilityService, FolioService, HousekeepingService, BookingEngineService, BookingImportService, ChannelMappingGuard, RateAndRestrictionService, SyncJobDispatcher, FrontDeskService) · `app/Services/Channex/` (ChannelManagerClient interface + Fake/real client, ARI DTOs, webhook signature) · `app/Payments/` (PaymentGateway interface, Manual + Stripe gateways, `Stripe/` client seam) · `app/Jobs/Sync/` (RetryableSyncJob, AriPushJob, ProcessChannexWebhookJob) · `app/Actions/Reservations/` (CheckIn, CheckOut) · `app/Http/Controllers/` (Channex + Stripe webhooks, BookingEngineController) · `app/Livewire/` (staff/booking contract stubs) · `app/Models/` (57) · `database/migrations/` (60) · `tests/` (18 Feature files + `tests/Support/*_helpers.php` + race workers).

## Next up

**Phase 1, Phase 2, and Phase 3 · Module 1 (Owner Accounting) are all certified.** The owner-accounting *accounting* is fully real + tested; only the Stripe Connect money rail is deferred. Options for the next gated track (none starts without a written prompt in `docs/prompts/`):

1. **Next Phase 3 module — Unified Inbox** (WhatsApp + email + OTA + SMS): the next differentiator on the wedge.
2. **Channex staging live checkpoint** — `docs/prompts/live-integration-checkpoint.md` Steps 1–2: real `ChannexClient` (outbound ARI) + inbound webhook, reconcile the real shape + HMAC back into `FakeChannexClient`. **Blocked: needs a Channex staging account + public tunnel.**
3. **Thin design slice** to demo the wedge — styled owner statement/portal (or booking engine) over the certified backends; presentation only, contracts are final.
4. **Stripe Connect payout-rail live checkpoint** — real owner disbursement (connected-account onboarding + KYC), when credentials exist.

Write whichever track you pick as a gated prompt the same way. The design pass over all the unstyled contract stubs (booking engine, staff, owner portal) remains open across the board.
