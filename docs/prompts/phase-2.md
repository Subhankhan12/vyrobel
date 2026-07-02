# Vyrobel — Phase 2 Build · Claude Code Master Prompt

**How to use**
- This is for a **fresh session** after the Phase 1 consolidation. The session should already have read `docs/PROJECT-STATE.md` and `docs/DECISIONS.md`.
- Save this at `docs/prompts/phase-2.md`, then paste everything below the line.
- Work through it step by step — Claude Code stops at every ⛔ gate for your approval. Same discipline as Phase 1: audit-first, no faked data, tests must really pass, stop-and-approve between steps.

Locked context it builds on: certified Phase 1 (multi-tenant core, two-level availability, the guarded `AssignmentService`, append-only folio ledger, GDPR-safe guests, event→listener discipline, ULID/Money/morph-map conventions).

═══════════════════ PASTE BELOW THIS LINE ═══════════════════

# ROLE & MISSION
You are building **Phase 2 (Distribution & Payments)** of **Vyrobel**, a multi-tenant hotel management SaaS, on top of a certified Phase 1 foundation. Phase 2 delivers: the **Channex channel-manager integration with a bulletproof sync queue**, **real Stripe payments**, and the **public booking engine** (plus a thin staff action layer) — all backend + unstyled contract stubs. Styled UI is deferred to the later design pass.

Before anything: read `docs/PROJECT-STATE.md`, `docs/DECISIONS.md`, `docs/master-plan.md`, and `docs/schema/phase-1.md`. Build only what each step specifies, in order, and **STOP at every ⛔ gate**.

# THE CENTRAL CHALLENGE (this drives every design choice)
Phase 1 proved no-double-booking **within** our system. Phase 2 must hold that guarantee while an **external system (Channex → OTAs) sells the same inventory**. Non-negotiable rules:
1. **Single write path is absolute.** OTA bookings import **through `AssignmentService`** — no parallel OTA reservation/availability path. `sold` is still written only by `AssignmentService`.
2. **Availability pushes OUT fast.** Every availability/rate/restriction change enqueues an ARI push to Channex, to shrink the oversell window. Channex is last-write-wins, so push **current full state** for affected room-type/date ranges, not deltas.
3. **Asymmetric booking policy.** Direct bookings (booking engine / walk-in) still **reject** when full. OTA imports are **never silently dropped** — occupy if possible; if inventory is genuinely unavailable (sync lag oversold it), still record the reservation and **flag an `overbooking_conflict`** for manual resolution. You cannot un-book an OTA reservation by rejecting it locally.
4. **All external I/O is interface + fake + real.** A `ChannelManagerClient` interface with a `FakeChannexClient` (in-memory, deterministic) and a `ChannexClient` (real REST, config-gated). Stripe via the existing `PaymentGateway` interface with a real `StripeGateway` and test-mode/mocked tests. **No test hits the network.** Live integration against Channex staging / Stripe test mode is a separate, credentialed checkpoint you call out — never faked green.

# PHASE 1 INVARIANTS THAT MUST NOT BE WEAKENED
Single inventory write path (`AssignmentService`) · two-level double-booking (Level A counts + Level B unit lock via `SELECT … FOR UPDATE`, deterministic lock ordering) · one `reservations` table, `source` distinguishes channel · money = integer minor units + currency, never float · PII encrypted, GDPR = anonymize · append-only correct-by-reversal financial ledger · `domain_events` emitted post-commit by the write side only; listeners/recompute are pure consumers that never emit and never touch `sold` · enforced morph map · tenant scoping via `BelongsToOrganization` (guard throws without context; super-admin bypass) · ULID keys (spatie tables excepted).

# RULES OF ENGAGEMENT
- **Audit first**, then STOP at every ⛔ gate — never collapse steps.
- **Extend, don't duplicate.** Reuse `AssignmentService`, `AvailabilityService`, `FolioService`, the `PaymentGateway` interface, the domain-event system, the polymorphic infra. No parallel structures.
- **No faked data or dressed-green tests.** Fakes for external systems are legitimate and must be clearly named as fakes; if something needs live credentials to prove, say so explicitly rather than implying it's verified.
- **Secrets** (Channex API key, Stripe keys, webhook signing secrets) live in `.env` (test/sandbox values), never committed. Per-tenant channel credentials are stored **encrypted** (OTA credential vault).
- **Report** after each step, file-cited: files changed, migrations, and how to verify. Update `docs/DECISIONS.md` at each gate.
- Register every new polymorphic-capable model in the enforced morph map.

---

## ⛔ STEP 0 — Audit, Phase 2 schema draft & plan (GATE)
1. Audit the repo: confirm Phase 1 is intact (run `php artisan test` — the full suite must be green), and report the current queue driver (still `database`), whether Redis/Horizon are installed, and the state of `reservations.channel_id` / `ota_reservation_id` (Phase 1 hooks).
2. **Draft `docs/schema/phase-2.md`** — the data contract for the new tables, grounded in the real Phase 1 schema and the master plan. Propose columns (do not build yet) for: `channels` (per-tenant, per-property; provider e.g. channex; encrypted credentials; status), `channel_mappings` (room_type ↔ external room type, rate_plan ↔ external rate plan; mapping health/status), `sync_jobs`/`sync_logs` (direction, payload summary, status, attempts, last error — human-readable), `overbooking_conflicts` (reservation, unit/room_type, detected_at, status, resolution), booking-engine tables (`booking_quotes`/holds and `abandoned_bookings`), and any Stripe/webhook bookkeeping (`webhook_events` for inbound idempotency if not already covered). Reuse existing tables where possible; only add what's needed.
3. Propose the package/infra plan (NOT run yet): `laravel/horizon` + Redis client (predis or phpredis), `stripe/stripe-php`, Channex via a thin HTTP client (Laravel HTTP). Confirm the interface+fake+real approach for both externals.
4. Echo the Phase 2 step list below and flag any conflict with Phase 1 or the schema.
**STOP for approval.**

## ⛔ STEP 1 — Queue & sync-reliability foundation (GATE)
- Install and configure **Horizon on Redis**; switch `QUEUE_CONNECTION=redis` (keep `database` fallback documented). This is the deferred-to-Phase-2 item from `DECISIONS.md`.
- Build the **sync backbone** before any real Channex call: `sync_jobs`/`sync_logs`, a base queued job with **retry + exponential backoff + dead-letter** on permanent failure, and an **idempotency mechanism** (idempotency keys so retried jobs never double-apply). Human-readable error logging.
- No Channex calls yet — this step proves the reliability machinery in isolation (enqueue → process → retry → dead-letter, all idempotent).
**Report + STOP.**

## ⛔ STEP 2 — Channel mapping model (GATE)
- Build `channels` (encrypted per-tenant credentials — the OTA credential vault), `channel_mappings` (room_type ↔ external, rate_plan ↔ external), mapping health/status.
- A **mapping guard**: no ARI push or booking import proceeds for an unmapped room_type/rate_plan — it logs a clear, actionable mapping error instead of silently doing nothing.
- Close the Phase 1 hook: `reservations.channel_id` FK now that `channels` exists.
**Report + STOP.**

## ⛔ STEP 3 — Channex client + ARI push (outbound sync) (GATE)
- `ChannelManagerClient` interface + `FakeChannexClient` (in-memory, deterministic, inspectable) + `ChannexClient` (real REST skeleton, config-gated, never called in tests).
- **ARI push:** every availability / rate / restriction change enqueues a push of the **current full state** for the affected room_type + date range (last-write-wins, idempotent). Wire it to the write side: `AssignmentService` inventory changes, `AvailabilityService.recompute` results, inventory-block changes, and rate/restriction edits emit domain events that enqueue the push. **The push is a pure consumer of committed state — it never mutates availability and never emits inventory events** (same one-directional rule as Phase 1 listeners).
- Prove with the fake client that a booking/block/rate change results in exactly one queued push carrying the correct current ARI.
**Report + STOP.**

## ⛔ STEP 4 — Booking import (inbound sync) (GATE)
- Channex **webhook** endpoint: validate signature, dedupe on `(channel, external_event_id)` (idempotent — webhooks are at-least-once), enqueue processing.
- **Import an OTA booking as a `reservation` (source=ota) THROUGH `AssignmentService`**, idempotent on `(channel, ota_reservation_id)`. Map the external room/rate via `channel_mappings`; create/match the guest (note: guest matching may need the deferred blind-index later — for now match on external ref, create otherwise).
- **Overbooking policy (the crux):** add an `AssignmentService` import path that, inside the lock, occupies inventory if available; if genuinely unavailable, still records the reservation/reservation_unit and creates an `overbooking_conflict` (never silently drops, never crashes, never double-counts `sold`). Direct-booking paths are unchanged (still reject when full).
- Handle OTA **modification** and **cancellation** imports idempotently (adjust `sold` via `AssignmentService`, resolve/close conflicts as appropriate).
**Report + STOP.**

## ⛔ STEP 5 — Cross-system concurrency & sync-reliability suite (GATE — the crux, tests mandatory)
Prove, with named tests (fakes for Channex, true parallelism where contention matters):
1. **Direct vs OTA race:** a direct booking and an OTA import racing for the last unit of a type — exactly one occupies cleanly; the other is rejected (direct) or flagged as overbooking (OTA) — never both occupy, `sold` never exceeds capacity.
2. **ARI push on every change:** each availability-affecting change (book/cancel/block/rate) enqueues exactly one correct current-state push (no missed pushes, no loops).
3. **Webhook idempotency:** the same OTA booking webhook delivered N times imports exactly one reservation; the same event id processed once.
4. **Retry / backoff / dead-letter:** a failing sync retries with backoff and lands in dead-letter after max attempts, with a readable log — and is not applied twice.
5. **Overbooking detection:** an OTA import against full inventory records the reservation + one `overbooking_conflict`, and `sold` stays correct (not inflated).
6. **Mapping guard:** unmapped room_type/rate_plan blocks push/import with a clear error, not a silent no-op.
Re-run the **full Phase 1 suite** — it must still be green (no regression to the core guarantee). State honestly which tests use true OS-level parallelism vs the fake client.
**Report + STOP.**

## ⛔ STEP 6 — Stripe payment gateway (GATE)
- Real `StripeGateway implements PaymentGateway` (the Phase 1 interface): payment intents, **card tokenization (token-only — never store raw PAN, matches Phase 1)**, deposit-hold **authorize → capture** (mapped onto `payment_authorizations`), refunds as **new offsetting entries** (append-only ledger preserved), **SCA/3DS** support, and **Stripe webhooks** for async status (signature-validated, idempotent on event id).
- Keep the `ManualPaymentGateway` for offline/pay-at-property. Selection is per-tenant/per-context config.
- OTA card data arriving via Channex uses the **tokenized/proxy** pattern — never raw card storage.
- Test against Stripe **test mode / a mocked client** (no live calls in the suite). Call out that live test-mode verification with real test keys is a separate checkpoint.
**Report + STOP.**

## ⛔ STEP 7 — Public booking engine (backend + unstyled contract stub) (GATE)
- Public (unauthenticated) flow: availability **search** via `AvailabilityService`; **quote/hold**; **book** routed **THROUGH `AssignmentService`** (never a parallel write path) + **pay** via `StripeGateway`; confirmation; **abandoned-booking capture**. Rate limiting on the public endpoints; promo/deposit supported.
- Tenant/property resolved from the public context (subdomain or property slug) — reuse the tenant-context mechanism; the public path sets context explicitly and safely (no guard bypass leaks).
- **Unstyled contract stub only** (like Phase 1 Step 9): document the exact data contract each screen consumes; no CSS/design system. Styled booking UI is the later design pass.
**Report + STOP.**

## ⛔ STEP 8 — Staff action layer (backend + contract stubs) (GATE)
- Assemble existing services into staff-facing **actions** (thin, reusing Phase 1): check-in, check-out (exists), room move (`AssignmentService.assignUnit`), payment collection (`FolioService` + `StripeGateway`), housekeeping task updates (exists). Add only what's missing; do not duplicate service logic.
- **Unstyled contract stubs** for the front-desk and housekeeping surfaces (arrivals/departures/in-house, cleaning board) — data contracts documented, styled PWA deferred to the design pass (including offline-sync, which is a design-phase concern).
**Report + STOP.**

## ⛔ STEP 9 — Full Phase 2 integrity suite & consolidation (GATE)
- Consolidate every Phase 2 obligation (Steps 1–8) into the permanent named suite alongside Phase 1's. Re-assert the entire Phase 1 suite is green (no regression).
- Update `docs/PROJECT-STATE.md` and `docs/DECISIONS.md`: Phase 2 status, what's proven-against-fakes vs what needs **live-integration checkpoints** (Channex staging, Stripe test-mode with real keys), and the new deferred register.
- Honest gate: `php artisan test` green on MySQL, PHPStan clean, Pint passing, Horizon processing the sync queue, and an explicit statement of parallelism and of any live checkpoint still outstanding.
**Report + STOP. Phase 2 done.**

═══════════════════ PASTE ABOVE THIS LINE ═══════════════════

---

### What's deliberately deferred (so scope stays honest)
- **Styled UI** — the booking-engine pages and the staff/housekeeping PWA screens (including offline-first sync and push) belong to the dedicated design pass, per your standing decision. Phase 2 builds their backends + unstyled contract stubs.
- **Live external integration** — running against real Channex staging and Stripe test-mode keys is a separate, credentialed checkpoint. The build proves the logic against fakes/mocks; it will *say so* rather than imply live verification.
- **Direct OTA certifications** (bypassing Channex) remain a far-future Phase-5 consideration, not this phase.

### After Phase 2
When it's certified, the next natural move is either **Phase 3** (differentiators: white-label website builder, unified inbox, owner accounting + payouts, the first AI agents) or the **design pass** to give the booking engine and staff surfaces their real UI. Tell me which and I'll write it the same gated way.
