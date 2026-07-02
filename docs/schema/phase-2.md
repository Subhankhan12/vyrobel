# Vyrobel — Phase 2 Schema (Distribution & Payments) — DRAFT for Step 0 gate

> **Status: PROPOSAL. Nothing here is built yet.** This is the data contract for Phase 2, grounded
> in the real Phase 1 schema (`docs/schema/phase-1.md`) and the master plan. It is approved at the
> ⛔ Step 0 gate, then built table-by-table in the gated steps that follow.
>
> **Same conventions as Phase 1 apply to every new table** (do not relist per table): ULID PK,
> `organization_id` + `BelongsToOrganization` (global scope + guard) on every business table,
> money = integer `*_minor` + ISO `currency` (never float), UTC datetimes, encrypted PII casts,
> `created_at`/`updated_at`, `deleted_at` where operational, and **registration in the enforced
> morph map** for any polymorphic-capable model.

---

## 0. What we REUSE (extend-don't-duplicate — no new tables here)

Phase 2 leans hard on the certified core. No parallel structures:

- **`reservations`** — OTA bookings are rows with `source='ota'` + `channel_id` + `ota_reservation_id`. The Phase 1 forward hooks already exist (`channel_id` nullable ULID + index, `ota_reservation_id` nullable string, `source` enum includes `ota`). No new reservation tables — ever.
- **`reservation_units`** — OTA imports occupy inventory here, **through `AssignmentService`** (import path added in Step 4). `unit_id` nullable already supports "type-assigned, no physical unit yet" (fresh OTA import).
- **`availability` / `rate_calendar` / `restrictions`** — these ARE the ARI source of truth. The outbound push (Step 3) is a **pure read** of committed state from these three; it adds **no** columns and writes **nothing** here.
- **`payments` / `payment_authorizations`** — Stripe reuses both. `payments.gateway` enum **already includes `stripe`** (verified) → no enum migration. Deposit hold = `authorize`→`capture` maps onto `payment_authorizations`. Refund = new offsetting `payments` row (append-only ledger preserved).
- **`folios` / `folio_items`** — `FolioService` posts Stripe charges/refunds via existing paths.
- **`guests`** — OTA/booking-engine guests are `guests` rows; PII stays encrypted; matching uses `external_refs` (JSON, already present) in Step 4.
- **`PaymentGateway` interface** — `StripeGateway` slots behind the existing contract (`charge/refund/authorize/capture/void`), alongside `ManualPaymentGateway`. No interface change expected.
- **`domain_events`** — the ARI push and import-triggered work are consumers/producers on the existing outbox; no schema change.
- **`webhooks` / `webhook_deliveries`** — these are **OUTBOUND tenant webhooks** (Phase 1). Phase 2's inbound provider webhooks are a **separate** concern → new `webhook_events` table (§7). Do not conflate.

---

## 1. `channels` — the OTA credential vault (per tenant, per property)

One connection from a Vyrobel property to a channel-manager provider (Channex at launch).

| Column | Type | Notes |
|---|---|---|
| `id` | ulid | |
| `organization_id` | ulid FK | tenant-scoped |
| `property_id` | ulid FK → properties | channels are **per property** (Channex property ↔ our property) |
| `provider` | enum(`channex`) | extensible; only `channex` in P2 |
| `name` | string | human label |
| `external_property_id` | string null | Channex property id |
| `credentials` | text **(encrypted cast)** | API key / per-property token — **the credential vault** |
| `settings` | json null | provider-specific options (no secrets) |
| `status` | enum(`disconnected`,`connected`,`error`,`disabled`) default `disconnected` | |
| `last_synced_at` | datetime null | |
| `last_error` | text null | human-readable |

- `unique(organization_id, property_id, provider)` — one Channex connection per property.
- softdeletes. Morph map: **`channel`**.
- **Closes Phase 1 hook (Step 2):** add FK `reservations.channel_id → channels.id` (`nullOnDelete`).

---

## 2. `channel_mappings` — internal ↔ external mapping + health

Maps our `room_type` and `rate_plan` to the provider's ids. One row per mapped entity.

| Column | Type | Notes |
|---|---|---|
| `id` | ulid | |
| `organization_id` | ulid FK | |
| `channel_id` | ulid FK → channels | |
| `kind` | enum(`room_type`,`rate_plan`) | which entity this row maps |
| `room_type_id` | ulid null FK → room_types | set when `kind=room_type` |
| `rate_plan_id` | ulid null FK → rate_plans | set when `kind=rate_plan` |
| `external_id` | string | Channex room_type / rate_plan id |
| `status` | enum(`active`,`pending`,`error`,`disabled`) default `pending` | **mapping health** |
| `last_verified_at` | datetime null | |
| `last_error` | text null | actionable message |

- `unique(channel_id, room_type_id)` and `unique(channel_id, rate_plan_id)` (nullable columns; MySQL allows multiple NULLs, so the two uniques coexist).
- **Mapping guard (Step 2):** no ARI push or booking import proceeds for an unmapped `room_type`/`rate_plan` — it records a clear, actionable `sync_logs` error instead of a silent no-op.

---

## 3. `sync_jobs` — durable, business-visible record of every sync operation

Distinct from Laravel's internal `jobs`/`failed_jobs` (queue transport). This is the domain ledger the mapping-health / sync-error dashboard reads, and the anchor for idempotency + dead-letter.

| Column | Type | Notes |
|---|---|---|
| `id` | ulid | |
| `organization_id` | ulid FK | |
| `channel_id` | ulid null FK → channels | |
| `direction` | enum(`outbound`,`inbound`) | |
| `type` | enum(`ari_push`,`booking_import`,`booking_modify`,`booking_cancel`,`mapping_sync`) | operation kind |
| `idempotency_key` | string | **`unique`** — retried jobs never double-apply |
| `status` | enum(`pending`,`processing`,`succeeded`,`failed`,`dead_letter`) default `pending` | |
| `attempts` | unsignedInt default 0 | |
| `max_attempts` | unsignedInt default 5 | |
| `available_at` | datetime null | backoff schedule marker |
| `payload_summary` | json null | **human-readable** summary (room_type, date range, counts) — **no secrets, no raw card data** |
| `last_error` | text null | |
| `processed_at` | datetime null | |
| `dead_lettered_at` | datetime null | |

- indexes: `unique(idempotency_key)`, `index(channel_id)`, `index(status)`, `index(direction, type)`.
- No soft delete (operational ledger; prune by retention later).

## 3b. `sync_logs` — append-only per-attempt log lines (write-once)

| Column | Type | Notes |
|---|---|---|
| `id` | ulid | |
| `organization_id` | ulid FK | |
| `sync_job_id` | ulid FK → sync_jobs | |
| `attempt` | unsignedInt | |
| `level` | enum(`info`,`warning`,`error`) | |
| `message` | text | **human-readable** |
| `context` | json null | sanitized (no secrets) |
| `created_at` | timestamp | **write-once** — no `updated_at`, no soft delete |

- `index(sync_job_id)`. Morph map for `sync_job` if ever used as an event subject.

---

## 4. `overbooking_conflicts` — the crux flag (never silently drop an OTA booking)

When an OTA import can't occupy inventory (sync-lag oversell), the reservation is **still recorded** and a conflict is raised for manual resolution.

| Column | Type | Notes |
|---|---|---|
| `id` | ulid | |
| `organization_id` | ulid FK | |
| `property_id` | ulid FK → properties | |
| `reservation_id` | ulid FK → reservations | the recorded OTA reservation |
| `reservation_unit_id` | ulid null FK → reservation_units | the contested line |
| `room_type_id` | ulid FK → room_types | |
| `channel_id` | ulid null FK → channels | |
| `check_in` / `check_out` | date | contested range |
| `detected_at` | datetime | |
| `status` | enum(`open`,`resolved`,`ignored`) default `open` | |
| `resolution` | enum(`reassigned`,`moved`,`upgraded`,`cancelled_external`,`accepted`,`other`) null | |
| `resolution_notes` | text null | |
| `resolved_by` | ulid null FK → users | |
| `resolved_at` | datetime null | |

- indexes: `index(status)`, `index(property_id, status)`. Morph map: **`overbooking_conflict`**.

---

## 5. `booking_quotes` — booking-engine quote / hold / abandoned capture (single table)

**Recommendation: one table, not two.** Fold "abandoned booking" into the quote lifecycle via `status` + recovery fields, to honor extend-don't-duplicate. (The prompt lists `booking_quotes`/holds and `abandoned_bookings` separately — folding avoids a parallel structure that would carry the same columns. Flagged for gate decision.)

| Column | Type | Notes |
|---|---|---|
| `id` | ulid | |
| `organization_id` | ulid FK | |
| `property_id` | ulid FK → properties | |
| `reference` | string null | shown on confirmation |
| `session_token` | string | public (unauth) session id — indexed |
| `check_in` / `check_out` | date | |
| `adults` / `children` | unsignedInt | |
| `requested` | json | requested lines: `[{room_type_id, rate_plan_id, qty}]` |
| `currency` | char3 | |
| `subtotal_minor` / `tax_minor` / `fees_minor` / `discount_minor` / `total_minor` | bigint | Money — price snapshot at quote time |
| `promo_code` | string null | |
| `guest_snapshot` | json null | name captured pre-guest-row |
| `email` | text null **(encrypted cast)** | PII — for abandoned-booking recovery |
| `phone` | text null **(encrypted cast)** | PII |
| `status` | enum(`quoted`,`held`,`converted`,`expired`,`abandoned`,`recovered`) default `quoted` | |
| `held_until` | datetime null | see hold semantics below |
| `expires_at` | datetime | quote validity |
| `recovery_status` | enum(`none`,`pending`,`contacted`,`recovered`,`expired`) default `none` | abandoned-capture workflow |
| `contacted_at` | datetime null | |
| `converted_reservation_id` | ulid null FK → reservations | |

- indexes: `index(session_token)`, `index(status)`, `index(property_id, status)`.
- **PII:** `email`/`phone` encrypted; covered by a retention/anonymize sweep (GDPR consistency with `guests`).
- **Hold semantics (gate decision):**
  - **Recommended (P2):** hold is **advisory** (`held_until` timer). The quote is a price + availability **snapshot**; the authoritative occupation happens atomically at **book** time through `AssignmentService`, which re-checks inside the lock. A racing loser is cleanly rejected at book (correct, safe, no `sold` written outside the service).
  - **Alternative:** a **hard hold** creates a tentative `reservation_unit` via `AssignmentService` that counts toward `sold`, released by an expiry reaper. Stronger UX, more scope (reaper job + tentative status). Deferred unless chosen at the gate.

---

## 6. `webhook_events` — INBOUND provider-webhook idempotency (Channex + Stripe)

**Separate from Phase 1 `webhooks`/`webhook_deliveries` (which are outbound).** At-least-once inbound delivery → dedupe here.

| Column | Type | Notes |
|---|---|---|
| `id` | ulid | |
| `organization_id` | ulid null FK | resolved from channel where possible; may be null until resolved |
| `source` | enum(`channex`,`stripe`) | |
| `channel_id` | ulid null FK → channels | for Channex |
| `external_event_id` | string | provider's event id |
| `event_type` | string | provider event type |
| `signature_verified` | bool default false | |
| `payload` | json | as received (sanitized of card data; retention-limited) |
| `status` | enum(`received`,`processing`,`processed`,`failed`,`ignored`) default `received` | |
| `received_at` | datetime | |
| `processed_at` | datetime null | |
| `error` | text null | |

- **`unique(source, external_event_id)`** — the inbound idempotency guarantee (same event id processed once).
- **Booking-import idempotency backstop (Step 4):** add `unique(reservations.channel_id, reservations.ota_reservation_id)`. MySQL allows multiple NULLs, so direct bookings (both NULL) never collide, while a repeated OTA `(channel, ota_reservation_id)` does — a DB-level guard behind the app-level dedupe.

---

## 7. Stripe bookkeeping — mostly reuse

- No new payment tables. `payments` (+`stripe` gateway), `payment_authorizations`, `folios`/`folio_items` cover it.
- Inbound Stripe webhooks → `webhook_events` (§6), idempotent on `(stripe, event_id)`.
- **Optional (gate):** `guests.stripe_customer_id` (nullable) for tokenized saved cards. Token-only; no raw PAN. Deferred unless wanted in Step 6.

---

## 8. Queue / Horizon — no business tables

Horizon runs on **Redis** (transport), not DB tables. Laravel's `jobs`/`job_batches`/`failed_jobs` already exist (`create_jobs_table` migration present) and remain the DB fallback. The durable, business-visible sync state lives in `sync_jobs`/`sync_logs` (§3), independent of the queue transport.

---

## 9. New morph-map registrations (checklist)

`channel`, `channel_mapping` (if event subject), `sync_job` (if event subject), `overbooking_conflict`, `booking_quote`, `webhook_event` (if event subject). Confirmed per step as each model lands.

---

## 10. Open decisions for the Step 0 gate

1. **Booking hold:** advisory timer (recommended) vs hard-hold-through-`AssignmentService`. → §5
2. **Abandoned bookings:** fold into `booking_quotes` (recommended) vs separate `abandoned_bookings` table. → §5
3. **Redis client:** `predis/predis` (pure PHP, recommended — **phpredis extension is not loaded** on this box) vs installing the `phpredis` PECL extension. `.env` currently says `REDIS_CLIENT=phpredis`.
4. **Stripe saved cards:** add `guests.stripe_customer_id` now vs defer. → §7
5. **Environment prerequisite:** a **Redis server is not installed** locally (`redis-server` not on PATH). Step 1 needs `brew install redis` + a running instance. Confirm before Step 1.
