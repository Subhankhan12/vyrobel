# Vyrobel — Phase 1 Unified Core Schema (Data Contract)

> This is the foundation everything else attaches to. It covers the **Phase 1 core** (tenancy, access, properties, inventory, rates/availability, guests, reservations, folios/payments, housekeeping status, and the cross-cutting plumbing) — designed so that Phase 2–5 modules (channel manager, owner accounting, messaging, automation, AI agents) bolt on **without reworking the core**.
>
> **How to use this:** review it, confirm the handful of decisions in §2, and then this becomes the spec that goes into your Phase 1 Claude Code master prompt (which generates the actual Laravel migrations + models). Nothing here is faked or placeholder — it's the real data model.

---

## 1. Conventions & rules (apply to every table)

These are the non-negotiable rules that keep the platform consistent and make the AI layer possible later.

1. **Tenancy.** Every business table carries `organization_id`. A global query scope applies it automatically, and a guard **throws** if a tenant-scoped query runs without an organization context (except for the super-admin context). Property-level data also carries `property_id` for property-scoped roles. *(Enforced by the `BelongsToOrganization` trait — see §2.)*
2. **Primary keys.** ULIDs proposed (sortable, non-enumerable, multi-tenant-safe, Laravel-native). Avoids exposing row counts across tenants. *(Confirm in §2.)*
3. **Money is never a float.** Store integer **minor units** (`amount_minor` as `bigint`) + ISO-4217 `currency` (char 3). One `Money` value object across the app. This avoids rounding corruption in folios, payouts, and commissions.
4. **Time.** Store all datetimes in **UTC**. Each `property` has a `timezone`; "today's arrivals / departures," shift closing, and check-in-time logic are computed in property-local time. Pure dates (`check_in`, `check_out`) are `date` columns, half-open `[check_in, check_out)`.
5. **Soft deletes vs GDPR.** Operational records use soft deletes. **PII is not soft-deleted-and-kept** — a GDPR erase **anonymizes** the guest row (strips name/email/phone/documents) while preserving financial/aggregate integrity. (See `guests`, `guest_documents`.)
6. **Two parallel event trails.**
   - `audit_events` — security/compliance: *who did what* (actor, before/after, IP). Write-once.
   - `domain_events` — business event stream (outbox): `reservation.confirmed`, `payment.failed`, `unit.checked_out`, etc. This is the **semantic layer** that drives the automation builder (P3) and feeds the AI agents (P3+). Build it now even though consumers come later — it's near-free now and very expensive to retrofit.
7. **One reservation, many channels.** A direct booking, an OTA import, a walk-in, and a corporate booking are **all rows in `reservations`**, differing by `source` (+ `channel_id`/`ota_reservation_id` when from an OTA). **No parallel per-channel tables.** This is the schema-level version of extend-don't-duplicate, and it's what lets channel sync, folios, and agents operate uniformly.
8. **Double-booking is prevented at two levels** (this is the single most important integrity rule — see §5).
9. **Reusable polymorphic tables.** `media`, `documents`, `notes`, `tags` are polymorphic so every later module reuses them instead of inventing its own photo/note/tag tables.
10. **Standard columns.** Every table has `created_at`, `updated_at`; most have `deleted_at`. Not relisted per table below.

---

## 2. Locked technical decisions

These are locked and drive the whole build:

| # | Decision | Choice | Why it matters |
|---|---|---|---|
| 1 | **Database engine** | **MySQL 8 / InnoDB** (genuine MySQL, not MariaDB) | MySQL has no exclusion constraint, so the no-double-booking guarantee is enforced in a single guarded service using transactional row locking (`SELECT … FOR UPDATE`) — see §5. Use only standard InnoDB features; no engine-specific constructs. |
| 2 | **Multi-tenancy** | Single shared database, `organization_id` column + global scope + tenant guard | Every business table is tenant-scoped automatically; the guard throws on any unscoped tenant query. |
| 3 | **Permissions** | `spatie/laravel-permission`, **teams mode** (team = organization) | Roles/permissions scope per tenant automatically. Per-property scoping and field-level permissions ride on top via `property_user` + Laravel policies. |
| 4 | **Primary keys** | **ULID** on all tenant tables | Sortable, non-enumerable, multi-tenant-safe. |

Two smaller modeling choices (also locked):
- **Users can belong to multiple organizations** (membership pivot) — for property managers/agencies.
- **Folio granularity:** one folio per reservation, splittable (per-unit / per-guest) later.

---

## 3. Schema by module

Format per table: **`table`** — purpose. Then key columns (`name` type — notes) and relationships. Standard timestamps/soft-deletes omitted.

### Module 1 — Tenancy, Access & Platform

**`organizations`** — the tenant (a company/operator account).
- `id` ulid · `name` string · `slug` string unique · `mode` enum(`single_property`,`chain`,`property_manager`) · `default_currency` char3 · `default_locale` string · `country` char2 · `status` enum(`trialing`,`active`,`suspended`,`cancelled`) · `billing_email` string · `custom_domain` string null · `branding` json (logo, colors — white-label)
- has many: properties, memberships, api_keys, webhooks.

**`users`** — global person/account (can join multiple orgs).
- `id` ulid · `name` · `email` unique · `password` · `two_factor_secret` null · `two_factor_enabled` bool · `locale` · `status` enum(`active`,`disabled`) · `last_login_at`
- belongs to many organizations (via memberships).

**`organization_user` (memberships)** — a user's membership + base role in an org.
- `organization_id` · `user_id` · `status` enum(`invited`,`active`,`disabled`) · `invited_at` · (role via permissions package)
- unique(`organization_id`,`user_id`).

**`property_user`** — scopes a staff member to specific properties (for property-level roles).
- `organization_id` · `user_id` · `property_id`
- unique triple.

**`roles` / `permissions` / `role_has_permissions` / `model_has_roles`** — via permissions package, **team/tenant-scoped by `organization_id`**. Add per-property scoping via `property_user`. Seed the role catalog from the master-plan matrix (Super Admin, Property Owner, Group Admin, Hotel Manager, Revenue Manager, Front Desk, Reservation Agent, Housekeeping, Maintenance, Accountant, Marketing Manager, Guest Support Agent, External Owner, Guest).

**`plans` / `plan_features`** — product packages (Starter/Growth/Pro/Enterprise) and their feature/limit entitlements.
- `plans`: `code` · `name` · `price_minor` · `currency` · `interval` enum(`monthly`,`yearly`)
- `plan_features`: `plan_id` · `key` (e.g. `max_units`,`max_messages`,`ai_calls`,`storage_mb`,`website_builder`) · `limit` int null · `enabled` bool

**`subscriptions` / `subscription_items` / `usage_counters`** — tenant billing state (Stripe Billing references).
- `subscriptions`: `organization_id` · `plan_id` · `stripe_subscription_id` · `status` · `trial_ends_at` · `current_period_end`
- `usage_counters`: `organization_id` · `key` · `period` · `used` int (enforce plan limits).

**`api_keys`** — per-tenant API access (P5 public API, but model now).
- `organization_id` · `name` · `hashed_key` · `last_used_at` · `scopes` json · `revoked_at`

**`webhooks` / `webhook_deliveries`** — tenant outbound webhooks (signed).
- `webhooks`: `organization_id` · `url` · `events` json · `secret` · `active`
- `webhook_deliveries`: `webhook_id` · `event` · `payload` json · `status` · `attempts` · `response_code`

**`audit_events`** — security/compliance trail (write-once).
- `organization_id` null · `actor_type` enum(`user`,`agent`,`system`) · `actor_id` null · `action` string · `subject_type` · `subject_id` · `before` json null · `after` json null · `ip` · `user_agent` · `created_at`

**`settings`** — typed config per org/property (polymorphic owner).
- `owner_type` · `owner_id` · `key` · `value` json

### Module 2 — Properties & Inventory

**`properties`** — a physical site (can be mixed-type).
- `id` ulid · `organization_id` · `name` · `brand` null · `type` enum(`hotel`,`aparthotel`,`serviced_apartment`,`apartment_building`,`apartment`,`villa`,`hostel`,`guest_house`,`resort`,`mixed`,`portfolio`) · `address` json · `lat` · `lng` · `timezone` · `star_rating` null · `currency` char3 · `check_in_time` · `check_out_time` · `status` enum(`active`,`inactive`)
- has many: buildings, room_types, units, reservations, rate_plans.

**`property_policies`** — policies as structured data (or fold into `properties.policies` json; separate table if you want versioning).
- `property_id` · `cancellation` json · `child_policy` json · `pet` · `smoking` · `deposit` · `damage` · `min_stay` · `max_stay` · `long_stay_discounts` json

**`buildings`** — optional grouping (blocks/buildings in a portfolio).
- `property_id` · `name` · `code`

**`floors`** — optional.
- `building_id` · `level` · `name`

**`room_types`** — sellable category (the thing OTAs and the booking engine sell against).
- `id` ulid · `property_id` · `name` · `code` · `kind` enum(`room`,`apartment`,`villa`,`dorm`) · `std_occupancy` int · `max_adults` int · `max_children` int · `bedrooms` int null · `is_virtual` bool · `parent_room_type_id` ulid null (parent-child / virtual room types) · `default_rate_plan_id` null
- has many: units, rate_plans, availability rows.

**`units`** — the physical sellable thing (room / apartment / villa / dorm bed).
- `id` ulid · `organization_id` · `property_id` · `room_type_id` · `building_id` null · `floor_id` null · `code` (e.g. "203", "Apt-B4") · `name` · `unit_kind` enum(`room`,`apartment`,`villa`,`dorm_bed`) · `max_occupancy` int · `owner_id` ulid null **(FK to `owners`, P3 — nullable now; this is the hook for apartment-owner accounting)** · `housekeeping_status` enum(`clean`,`dirty`,`inspected`) · `condition_status` enum(`in_service`,`out_of_order`,`out_of_service`) · `is_active` bool
- belongs to: property, room_type, (owner later). Has many: reservation_units, inventory_blocks, status logs.

**`amenities` / `amenitiables`** — amenity catalog + polymorphic pivot (property/room_type/unit).
- `amenities`: `name` · `icon` · `category`
- `amenitiables`: `amenity_id` · `amenitiable_type` · `amenitiable_id`

**`unit_relationships`** — connected rooms / multi-bedroom composition.
- `unit_id` · `related_unit_id` · `relation` enum(`connects_to`,`part_of`)

**`inventory_blocks`** — date-range blocks that reduce availability (maintenance, owner-hold, manual).
- `id` · `organization_id` · `property_id` · `unit_id` null · `room_type_id` null · `start_date` · `end_date` · `reason` enum(`maintenance`,`owner`,`manual`,`overbooking_protection`) · `notes` · `created_by`
- **Affects availability and must not overlap an active `reservation_unit` on the same unit** (see §5).

**`unit_status_logs`** — history of housekeeping/condition status changes (audit + agent context).
- `unit_id` · `from_status` · `to_status` · `type` enum(`housekeeping`,`condition`) · `changed_by` · `created_at`

### Module 3 — Rates & Availability

> Phase 1 needs basic pricing + the availability spine. Rich revenue management (seasonal/LOS/competitor/AI pricing) is P4, but these tables are designed to carry it without change.

**`rate_plans`** — a sellable price configuration for a room_type.
- `id` ulid · `organization_id` · `property_id` · `room_type_id` · `name` · `code` · `currency` char3 · `is_refundable` bool · `cancellation_policy` json · `derived_from_rate_plan_id` ulid null (derived rates) · `adjustment` json null (e.g. parent −10%) · `is_active`

**`rate_calendar`** — nightly price per rate_plan per date.
- `rate_plan_id` · `date` · `price_minor` bigint · `currency`
- unique(`rate_plan_id`,`date`). Indexed on date for range pulls.

**`availability`** — **the sellable-count source of truth** per room_type per date (what the booking engine checks and the channel manager syncs to OTAs in P2).
- `room_type_id` · `date` · `total` int (physical units of this type) · `sold` int · `out_of_order` int · `blocked` int · *(available = total − sold − out_of_order − blocked, computed)*
- unique(`room_type_id`,`date`). **Recomputed via `domain_events`** whenever a reservation or block changes. (See §5.)

**`restrictions`** — stay rules per room_type/rate_plan per date (thin in P1, full in P2/P4).
- `room_type_id` null · `rate_plan_id` null · `date` · `min_stay` int null · `max_stay` int null · `closed_to_arrival` bool · `closed_to_departure` bool · `stop_sell` bool
- unique on the scope+date.

### Module 4 — Guests (PII — handle per §1.5)

**`guests`** — guest profile (CRM-ready; loyalty/segments attach in P4).
- `id` ulid · `organization_id` · `first_name` · `last_name` · `email` (encrypted) · `phone` (encrypted) · `nationality` char2 null · `dob` date null · `language` null · `is_vip` bool · `risk_flag` bool · `marketing_consent` bool · `anonymized_at` datetime null (GDPR erase) · `external_refs` json (OTA guest ids)
- has many: reservations, documents, notes, tags.

**`guest_documents`** — IDs/passports/registration cards (sensitive).
- `id` · `organization_id` · `guest_id` null · `reservation_id` null · `type` enum(`passport`,`id_card`,`visa`,`registration_card`) · `media_id` (stored via signed-URL storage, encrypted at rest) · `verification_status` enum(`pending`,`verified`,`rejected`) · `verified_by` null

**`corporate_accounts`** — companies/agents with negotiated terms (CRM/RM hook).
- `organization_id` · `name` · `type` enum(`corporate`,`travel_agent`) · `billing` json · `default_rate_plan_id` null · `commission_pct` null

### Module 5 — Reservations & Stays (the heart)

**`reservations`** — one booking, any source.
- `id` ulid · `organization_id` · `property_id` · `reference` string unique-per-org (human code) · `source` enum(`direct`,`walk_in`,`ota`,`corporate`,`group`,`agent`,`manual`) · `channel_id` ulid null **(FK to `channels`, P2)** · `ota_reservation_id` string null · `status` enum(`enquiry`,`tentative`,`confirmed`,`checked_in`,`checked_out`,`cancelled`,`no_show`) · `check_in` date · `check_out` date · `adults` int · `children` int · `currency` char3 · `total_minor` bigint · `balance_minor` bigint · `deposit_minor` bigint null · `payment_status` enum(`unpaid`,`partial`,`paid`,`refunded`,`charged_back`) · `lead_guest_id` ulid (FK guests) · `group_id` ulid null · `corporate_account_id` ulid null · `booked_at` · `cancelled_at` null · `cancellation_reason` null · `notes_guest` text null · `notes_internal` text null
- has many: reservation_units, status_history, folio(s), payments (via folio).

**`reservation_units`** — **per-unit line item; this is what occupies inventory.** Supports multi-room, room moves, splits, partial-stay extensions.
- `id` ulid · `reservation_id` · `organization_id` · `room_type_id` · `unit_id` ulid null (null = type-assigned but no physical unit yet, e.g. fresh OTA import) · `rate_plan_id` · `check_in` date · `check_out` date · `adults` int · `children` int · `price_minor` bigint (snapshot of agreed price) · `status` enum(`active`,`cancelled`,`moved`)
- **Unit-level non-overlap enforced here** (see §5). Room move = update `unit_id` (+ log). Split = multiple rows. Extend = update `check_out`.

**`reservation_guests`** — additional guests attached to a reservation/unit.
- `reservation_id` · `reservation_unit_id` null · `guest_id`

**`reservation_status_history`** — lifecycle audit + agent context.
- `reservation_id` · `from_status` · `to_status` · `changed_by` · `reason` · `created_at`

**`groups`** — group/block bookings (rooming lists).
- `organization_id` · `property_id` · `name` · `block_code` · `pickup_deadline` date null · `contact` json

### Module 6 — Folios, Payments & Tax

**`folios`** — the financial ledger for a reservation (splittable later).
- `id` ulid · `organization_id` · `reservation_id` · `currency` · `status` enum(`open`,`closed`,`void`) · `total_minor` bigint · `paid_minor` bigint · `balance_minor` bigint

**`folio_items` (charges)** — every line: room, tax, fee, add-on, manual.
- `id` ulid · `folio_id` · `organization_id` · `type` enum(`room`,`tax`,`fee`,`add_on`,`product`,`manual`,`discount`) · `description` · `unit_amount_minor` bigint · `quantity` int · `amount_minor` bigint (computed) · `tax_minor` bigint null · `reference_type` null · `reference_id` null (links to add_on/tax/fee def) · `service_date` date · `posted_by` · `posted_at`

**`invoices`** — issued tax/proforma invoices.
- `id` · `organization_id` · `folio_id` · `number` (sequential per property) · `type` enum(`proforma`,`tax`) · `issued_at` · `tax_breakdown` json · `media_id` (PDF) · `status` enum(`draft`,`issued`,`void`)

**`payments`** — money movements (never raw cards).
- `id` ulid · `organization_id` · `folio_id` null · `reservation_id` null · `gateway` enum(`stripe`,`paypal`,`square`,`manual`,`bank_transfer`,`ota_collect`) · `gateway_ref` string null · `type` enum(`payment`,`refund`,`deposit_hold`,`capture`,`void`,`chargeback`) · `amount_minor` bigint · `currency` · `status` enum(`pending`,`succeeded`,`failed`,`refunded`,`authorized`,`captured`,`voided`) · `card_token` string null **(token only)** · `metadata` json · `processed_at`

**`payment_authorizations`** — deposit/incidental holds (auth vs capture lifecycle).
- `payment_id` · `authorized_minor` bigint · `captured_minor` bigint null · `expires_at` · `status`

**`taxes`** — property tax definitions (VAT, city tax, tourism tax).
- `organization_id` · `property_id` · `name` · `kind` enum(`vat`,`city_tax`,`tourism_tax`,`other`) · `mode` enum(`percentage`,`per_night`,`per_person_night`,`flat`) · `rate` · `applies_to` json · `inclusive` bool

**`fees`** — property fee definitions (cleaning, service, extra-guest).
- `organization_id` · `property_id` · `name` · `mode` enum(`flat`,`per_night`,`per_person`,`percentage`) · `amount_minor`/`rate` · `taxable` bool

**`add_ons`** — sellable extras (breakfast, transfer, early check-in, extra bed, tours).
- `organization_id` · `property_id` · `name` · `price_minor` · `currency` · `taxable` bool · `inventory_limit` int null · `is_active`

### Module 7 — Housekeeping (Phase 1 = status & tasks; full PWA = P2)

**`housekeeping_tasks`** — cleaning jobs (drives the staff PWA board).
- `id` · `organization_id` · `property_id` · `unit_id` · `date` · `type` enum(`departure`,`stayover`,`deep`,`arrival_prep`) · `status` enum(`pending`,`in_progress`,`clean`,`inspected`) · `priority` enum(`low`,`normal`,`high`) · `assigned_to` null · `checklist` json null · `inspected_by` null · `completed_at` null
- has many: media (photo proof, via polymorphic `media`).

### Module 8 — Cross-cutting (reused everywhere)

**`media`** — polymorphic files/images (unit photos, property photos, housekeeping proof, invoice PDFs).
- `id` · `organization_id` · `mediable_type` · `mediable_id` · `disk` · `path` · `mime` · `size` · `meta` json

**`documents`** — polymorphic secure documents (separate from media for stricter access/encryption; guest IDs live here).
- `id` · `organization_id` · `documentable_type` · `documentable_id` · `media_id` · `category` · `signed_url_required` bool

**`notes`** — polymorphic notes (guest, reservation, unit).
- `id` · `organization_id` · `notable_type` · `notable_id` · `body` text · `visibility` enum(`internal`,`shared`) · `author_id`

**`tags` / `taggables`** — polymorphic tagging (guest segments, reservations).
- `tags`: `organization_id` · `name` · `color`
- `taggables`: `tag_id` · `taggable_type` · `taggable_id`

**`domain_events`** — the business event outbox (the semantic layer).
- `id` ulid · `organization_id` · `event_type` string (e.g. `reservation.confirmed`) · `subject_type` · `subject_id` · `payload` json · `occurred_at` · `processed_at` null · `available_at` (for scheduled/automation use)
- Consumed by: availability recompute (now), automation builder (P3), AI agents (P3+), webhooks.

---

## 4. Forward-compatibility map (where later phases attach — build nothing yet)

These FKs/hooks already exist in the core so later modules drop in cleanly:

- **Channel manager (P2):** `channels`, `channel_mappings` (room_type/rate_plan ↔ OTA), `sync_queue`, `sync_logs`. Hooks: `reservations.channel_id`, `reservations.ota_reservation_id`, `availability` (already the sync source of truth).
- **Owner accounting (P3):** `owners`, `owner_units` (or `units.owner_id`, already present), `owner_statements`, `owner_statement_lines`, `payouts`, `management_fee_rules`. Hook: `units.owner_id`.
- **Unified inbox (P3):** `conversations`, `messages`, `message_templates`, `channels_inbox` (WhatsApp/email/SMS/OTA threads). Hooks: `guests`, `reservations`, `domain_events`.
- **Automation builder (P3):** `automation_rules`, `automation_runs`. Consumes `domain_events`; acts via the same services as agents.
- **AI agents (P3+):** `agent_runs` (audit of every agent action: inputs, tools called, tokens, approver), `agent_tools_registry` (logical). Reads the unified model; writes via the guardrail engine (auto / approve / forbidden tiers).
- **CRM & loyalty (P4):** `loyalty_accounts`, `loyalty_ledger` (immutable, append-only points ledger), `segments`, `campaigns`. Hook: `guests`.
- **Revenue management (P4):** extends `rate_calendar`/`restrictions`; adds `competitor_rates`, `demand_signals`, `pricing_rules`.
- **Reputation (P4):** `reviews`, `review_replies`, `review_requests`. Hooks: `reservations`, `guests`.
- **Smart locks (P4):** `lock_providers`, `unit_locks`, `access_codes`. Hooks: `units`, `reservation_units`.

---

## 5. Critical integrity: preventing double bookings (read carefully)

This is the rule that, if wrong, destroys customer trust. It operates at **two levels**:

**Level A — Room-type availability (counts).** The `availability` table holds, per `room_type_id` per `date`: `total / sold / out_of_order / blocked`. The booking engine and (in P2) the OTA channel sync sell against `available = total − sold − out_of_order − blocked`. A confirmation that would push `sold + blocked + out_of_order` above `total` is rejected. `availability` is **recomputed via `domain_events`** whenever a `reservation_unit` or `inventory_block` is created/changed/cancelled. OTAs sell by type, not by specific unit — so this level is what keeps OTA inventory honest.

**Level B — Physical-unit non-overlap.** Once a specific `unit_id` is assigned on a `reservation_unit`, **no two active `reservation_units` for the same unit may have overlapping `[check_in, check_out)` ranges**, and no active `reservation_unit` may overlap an `inventory_block` on that unit.

Enforcement of Level B on **MySQL** (locked engine — MySQL has no exclusion constraint, so the guarantee lives in the application):

- Inside a single **transaction**: `SELECT … FOR UPDATE` the affected `availability` row(s) for the date range (this serializes concurrent attempts on the same type/dates), run an overlap-check query against active `reservation_units` for that `unit_id` (and against `inventory_blocks`), then insert and update availability — all within the locked transaction, then commit.
- The Claude Code prompt encodes this as a single guarded service method (`AssignmentService`) that **all** booking paths (manual, walk-in, booking engine, OTA import) must go through. No ad-hoc inserts anywhere.

  ```
  BEGIN
    SELECT … FOR UPDATE  -- lock availability rows for (room_type_id, dates)
    assert available ≥ requested           -- Level A
    assert no active reservation_unit overlaps this unit_id / range  -- Level B
    assert no inventory_block overlaps
    INSERT reservation_unit; UPDATE availability (sold++)
  COMMIT  -- releases locks; emit domain_events
  ```

  *(Note: on PostgreSQL this could instead be a database-level exclusion constraint, but we are on MySQL by design, so the guarantee is enforced in `AssignmentService`. Because it lives in code rather than the schema, the concurrency test in the build is mandatory, not optional.)*

**Both levels are mandatory.** Level A keeps OTA/booking-engine inventory from overselling a type; Level B keeps a specific room/apartment from being assigned twice. Every write path that touches inventory goes through one shared, transactional "assign/confirm" service — never ad-hoc inserts.

---

## 6. Next step

Confirm the four decisions in §2 (engine, tenancy mechanism, permissions package, PK type) and adjust anything in the model you want changed. Once it's locked, I'll turn this into your **Phase 1 Claude Code master prompt** — structured your usual way:

- **Phase 0 audit gate** (Claude Code inspects/echoes the intended schema and conventions before writing anything),
- explicit **stop-and-approve** checkpoints between sub-steps (migrations → models/relations → factories/seeders with realistic-but-clearly-marked demo data → the guarded booking service → stub views with data contracts),
- **extend-don't-duplicate** and **no-faked-data** constraints baked in,
- Playwright verification of the booking-integrity rule (attempt a double-assignment; assert it's rejected).

Tell me the four answers and I'll write that prompt.
