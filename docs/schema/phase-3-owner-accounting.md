# Vyrobel — Phase 3 · Module 1 Schema (Owner Accounting & Payouts) — DRAFT for Step 0 gate

> **Status: PROPOSAL. Nothing here is built yet.** This is the data contract for Owner Accounting,
> grounded in the real Phase 1/2 schema (`docs/schema/phase-1.md`, `docs/schema/phase-2.md`) and the
> master plan (§I owner portal & accounting, role table). It is approved at the ⛔ Step 0 gate, then
> built table-by-table in the gated steps that follow.
>
> **Same conventions as Phase 1/2 apply to every new table** (not relisted per table): ULID PK,
> `organization_id` + `BelongsToOrganization` (global scope + guard) on every business table,
> money = integer `*_minor` + ISO `currency` (never float), UTC datetimes, `created_at`/`updated_at`,
> `deleted_at` where operational, and **registration in the enforced morph map** for any
> polymorphic-capable model.

---

## 0. What we REUSE (extend-don't-duplicate — no new parallel structures)

Owner accounting is a **read-side projection** built on the certified core. It NEVER writes to
folios/reservations/inventory — attribution reads committed state and records its own ledger.

- **`folio_items`** — the revenue source of truth. Attribution reads posted lines and classifies by
  `type` (`room`/`tax`/`fee`/`add_on`/`product`/`manual`/`discount`) and by the polymorphic
  `reference` (→ `tax`/`fee`/`add_on` definition). `service_date` on each line supports per-night
  attribution. Append-only correct-by-reversal is the pattern we mirror for `owner_ledger`.
- **`reservation_units`** — tells us *which unit was occupied on which nights* (`unit_id`,
  `room_type_id`, `check_in`, `check_out`, `status` = `active`/`cancelled`/`moved`). This is the join
  from revenue → owner (`unit_id → units.owner_id`). `moved` status is exactly what a mid-stay unit
  move produces, so per-night attribution can follow the occupied unit.
- **`units.owner_id`** — the Phase 1 **forward hook** (nullable ULID, indexed, **no FK yet**). Step 1
  closes the FK to `owners`. Not every unit has an owner (hotel rooms typically don't).
- **`payments`** — refunds are already append-only offsetting rows; a refund is the trigger for a
  reversing `owner_ledger` entry (attribution reacts to it, never mutates it).
- **`Money` value object** (`app/Support/Money/Money.php`) + `MoneyCast` — every figure is
  integer minor units + ISO currency. (See §8 "Money gaps to close in Step 2" — it has no
  percentage/allocation helper yet.)
- **`domain_events`** — attribution and statement generation emit post-commit business events on the
  existing outbox; no schema change. Attribution is a **consumer** of reservation/folio events and a
  **producer** of owner-accounting events (`owner_ledger.posted`, `owner_statement.finalized`,
  `payout.paid`) — never an inventory event.
- **`housekeeping_tasks`** — a source for owner-attributable cleaning expenses (§6). No maintenance
  module exists yet → maintenance expenses are **manual** for now (forward hook, not a stub).
- **External Owner role** — already in `config/authorization.php` (`External Owner` → `owner_portal.view`);
  master-plan role table: *"Own units only … their calendar, statements, payouts, documents — nothing
  else."* Owner isolation (§7 / Step 6) is the enforcement layer for that role.

**No parallel revenue table.** Owner figures are a *derivation* of the folio ledger, not a second
copy of revenue. If the two ever disagree, the folio ledger wins.

---

## 1. `owners` — the external owner/investor party (org-scoped)

A person or company that owns one or more `units` and receives payouts. Org-scoped (belongs to the
operator's organization), optionally linked to a `User` for portal login.

| Column | Type | Notes |
|---|---|---|
| `id` | ulid PK | |
| `organization_id` | ulid FK | tenant-scoped (`BelongsToOrganization`) |
| `type` | enum(`individual`,`company`) default `individual` | party kind |
| `name` | string | display name (person or legal entity) |
| `legal_name` | string null | for statements/invoices when different |
| `email` | string **(encrypted cast)** | PII — encrypted like `guests.email` |
| `phone` | string null **(encrypted cast)** | PII |
| `tax_id` | string null **(encrypted cast)** | VAT/tax number (PII) |
| `address` | json null | structured postal address |
| `payout_currency` | char(3) | ISO — the currency the owner is *paid* in (may ≠ booking currency) |
| `user_id` | ulid FK → users, null | linked portal login (External Owner role); null = no portal access yet |
| `status` | enum(`active`,`inactive`) default `active` | |
| `notes` | text null | operator-internal |
| timestamps + softDeletes | | |

- Index: `organization_id`, `user_id`. Morph map: **`owner`**.
- **PII:** owner email/phone/tax_id encrypted (same discipline as guests). GDPR erase story for owners
  is a later concern (owners are B2B counterparties, not guests) — flagged, not built.
- `user_id` is nullable: an owner can exist for accounting before they're given a portal login.

---

## 2. `management_agreements` — the terms (effective-dated)

The commercial terms between operator and owner: commission, fees, and the **attribution rules**.
**Effective-dated** so terms can change over time without rewriting history — attribution for a given
night uses the agreement in effect on that night.

| Column | Type | Notes |
|---|---|---|
| `id` | ulid PK | |
| `organization_id` | ulid FK | tenant-scoped |
| `owner_id` | ulid FK → owners | |
| `unit_id` | ulid FK → units, null | **null = applies to all the owner's units**; set = a per-unit override |
| `commission_pct` | decimal(5,2) null | management commission % on owner gross (e.g. 15.00) |
| `management_fee_minor` | bigint null | fixed periodic fee (Money) |
| `management_fee_currency` | char(3) null | |
| `management_fee_interval` | enum(`monthly`,`quarterly`,`yearly`) null | pro-rated per statement period |
| `payout_currency` | char(3) null | overrides `owners.payout_currency` if set |
| `payout_schedule` | enum(`monthly`,`quarterly`) default `monthly` | statement/payout cadence |
| `attribution_rules` | json | the per-component rule overrides (see §9); null keys = use documented defaults |
| `effective_from` | date | inclusive |
| `effective_to` | date null | null = open-ended (current) |
| `status` | enum(`active`,`ended`) default `active` | |
| timestamps + softDeletes | | |

- Index: `owner_id`, `unit_id`, `(effective_from, effective_to)`. Morph map: **`management_agreement`**.
- **Resolution order** (most specific wins): per-`unit` agreement in effect that night → owner-wide
  agreement in effect that night. Overlapping agreements for the same scope+date are a validation
  error at write time (Step 1) — never silently resolved.
- `attribution_rules` json holds only *overrides*; a missing key means "use the documented default"
  (§9). Storing overrides-only keeps the defaults in one authoritative place (code + this doc), not
  copied into every row.

---

## 3. `owner_ledger` — append-only attributed lines (SOURCE OF TRUTH)

The heart of the module. Every attributed component is its own immutable row — this is what makes a
statement explainable line-by-line. Mirrors `folio_items`' correct-by-reversal discipline exactly.

| Column | Type | Notes |
|---|---|---|
| `id` | ulid PK | |
| `organization_id` | ulid FK | tenant-scoped |
| `owner_id` | ulid FK → owners | whose ledger this line belongs to |
| `unit_id` | ulid FK → units, null | the unit the line is attributed to (null for owner-wide fees) |
| `entry_type` | enum | see below — the component kind |
| `direction` | enum(`credit`,`debit`) | credit = increases owner net; debit = decreases |
| `amount_minor` | bigint | Money, **always positive**; `direction` carries the sign |
| `currency` | char(3) | the *booking/source* currency (converted to payout currency only at statement time) |
| `description` | string | human-readable ("Room revenue · Unit 4B · 3 nights") |
| `source_type` / `source_id` | nullable ulidMorphs | polymorphic pointer to the origin (`reservation`, `folio_item`, `owner_expense`, `owner_ledger` for a reversal) |
| `reservation_id` | ulid FK → reservations, null | denormalized for fast per-booking grouping/attribution idempotency |
| `service_date` | date | the night/date the value is attributed to (drives which statement period it lands in) |
| `attribution_version` | unsignedInteger default 1 | bumped when a booking is re-attributed (modification) |
| `reverses_id` | ulid FK → owner_ledger, null | set on a reversing line → points at the line it reverses |
| `statement_id` | ulid FK → owner_statements, null | set when swept into a finalized statement (locks the line to a period) |
| `posted_at` | timestamp | |
| timestamps | | **NO softDeletes** — append-only, correct-by-reversal only |

**`entry_type` values** (one per attribution component):
`room_revenue`, `management_commission`, `management_fee`, `ota_commission`, `cleaning_fee`,
`cleaning_expense`, `maintenance_expense`, `addon_revenue` (informational — operator revenue, excluded
from net by default), `pass_through_tax` (informational — excluded), `adjustment`, `reversal`.

- **Idempotency key of attribution:** `(reservation_id, unit_id, entry_type, attribution_version)` is
  unique for non-reversal lines → re-running attribution for a booking never double-posts. A
  modification bumps `attribution_version` and posts a fresh set (plus reversals of the prior version).
- **No line is ever edited or deleted.** A cancellation/refund posts `reversal` rows (`direction`
  flipped, `reverses_id` set) dated into the current open period.
- Indexes: `(owner_id, service_date)`, `(reservation_id, unit_id, entry_type, attribution_version)`
  unique-where-not-reversal, `statement_id`. Morph map: **`owner_ledger`** (it's a valid `source_type`
  for reversals).

---

## 4. `owner_statements` — period projection (lockable, append-only)

A statement is a **pure projection** of `owner_ledger` for one period. Regenerating a *draft* yields
identical numbers. Finalizing LOCKS the period.

| Column | Type | Notes |
|---|---|---|
| `id` | ulid PK | |
| `organization_id` | ulid FK | tenant-scoped |
| `owner_id` | ulid FK → owners | |
| `period_start` / `period_end` | date | half-open or inclusive — lock as `[start, end]` inclusive of full days |
| `currency` | char(3) | the owner's payout currency for this statement |
| `status` | enum(`draft`,`finalized`,`paid`) default `draft` | |
| `locked` | boolean default false | true once finalized — the period is closed |
| `gross_minor` | bigint | Σ room_revenue credits (cache; derived from lines) |
| `commission_minor` | bigint | Σ management_commission |
| `management_fee_minor` | bigint | Σ management_fee |
| `expenses_minor` | bigint | Σ deductible expenses (cleaning/maintenance) |
| `ota_cost_minor` | bigint | Σ ota_commission |
| `net_payable_minor` | bigint | **= sum of statement lines** (the invariant) |
| `fx_rate` | decimal(20,10) null | booking→payout rate used, if a conversion happened |
| `fx_source` | string null | provenance of the rate (manual/provider) — no silent float |
| `finalized_at` / `finalized_by` | timestamp / ulid FK users, null | |
| `sequence_no` | unsignedBigInteger null | per-owner gap-free statement number, assigned at finalize |
| timestamps | | no softDeletes (a superseded draft is regenerated, not soft-deleted) |

- `unique(owner_id, period_start, period_end)` — one statement per owner per period.
- `unique(owner_id, sequence_no)` — gap-free numbering assigned **at finalize** (draft has null).
- Morph map: **`owner_statement`**. Cache columns are derived; the `owner_statement_lines` are the
  detail and `net_payable_minor` must equal their sum (asserted in Step 7 money-integrity test).

## 4b. `owner_statement_lines` — the human-readable breakdown

Derived, ordered lines that render the statement: gross → commission → management fee → deductible
expenses → OTA cost → **net payable**. Each links back to the `owner_ledger` rows it aggregates.

| Column | Type | Notes |
|---|---|---|
| `id` | ulid PK | |
| `organization_id` | ulid FK | tenant-scoped |
| `owner_statement_id` | ulid FK → owner_statements (cascade) | |
| `line_type` | enum(`gross`,`commission`,`management_fee`,`expense`,`ota_cost`,`adjustment`,`net`) | |
| `description` | string | |
| `amount_minor` | bigint | signed presentation value (credits +, deductions −) |
| `currency` | char(3) | statement currency |
| `sort_order` | unsignedInteger | render order |
| timestamps | | |

- Regeneration of a *draft* statement replaces its lines wholesale (the draft isn't locked). Once the
  parent is `finalized`/`locked`, neither the statement nor its lines change — later corrections flow
  to the **next** statement via an `adjustment` ledger line (§Step 4 period-locking).

---

## 5. `payouts` — calculated payout record + lifecycle (money rail deferred)

Created from a finalized statement's `net_payable`. Money movement is deferred (Stripe Connect is a
later credentialed checkpoint) — this records the *decision to pay* and its lifecycle.

| Column | Type | Notes |
|---|---|---|
| `id` | ulid PK | |
| `organization_id` | ulid FK | tenant-scoped |
| `owner_id` | ulid FK → owners | |
| `owner_statement_id` | ulid FK → owner_statements | the source statement |
| `amount_minor` | bigint | Money — the net payable |
| `currency` | char(3) | owner's payout currency |
| `rail` | enum(`manual`,`stripe_connect`) default `manual` | which `PayoutRail` handled it |
| `status` | enum(`pending`,`paid`,`failed`,`reversed`) default `pending` | |
| `external_ref` | string null | bank ref / Stripe transfer id (deferred; null for now) |
| `connect_account_id` | string null | Stripe Connect acct (deferred) |
| `reverses_id` | ulid FK → payouts, null | a reversed/failed payout is a NEW row pointing here |
| `paid_at` / `failed_at` | timestamp null | |
| `initiated_by` | ulid FK users, null | |
| `notes` | string null | |
| timestamps | | no softDeletes — append-only |

- A `paid` payout marks its `owner_statement.status = paid`. Both are append-only: a reversal/failure
  is a **new** `payouts` row (`reverses_id` set), never an edit. Morph map: **`payout`**.
- `unique(owner_statement_id)` **where status in (pending,paid)** — one live payout per statement
  (a reversal + retry is allowed as new rows). *(If a partial unique index is awkward on MySQL, enforce
  in the service + a plain index; decided in Step 5.)*

---

## 6. `owner_expenses` — owner-attributable costs

Costs charged against an owner (cleaning, maintenance, other). Posted to `owner_ledger` as deduction
lines during statement generation (only if `deductible`).

| Column | Type | Notes |
|---|---|---|
| `id` | ulid PK | |
| `organization_id` | ulid FK | tenant-scoped |
| `owner_id` | ulid FK → owners | |
| `unit_id` | ulid FK → units, null | the unit it relates to |
| `category` | enum(`cleaning`,`maintenance`,`other`) | |
| `description` | string | |
| `amount_minor` | bigint | Money |
| `currency` | char(3) | |
| `expense_date` | date | drives which statement period it lands in |
| `deductible` | boolean default true | per agreement — non-deductible = tracked but not netted |
| `source_type` / `source_id` | nullable ulidMorphs | link to a `housekeeping_task` where one exists; else manual |
| `attributed_to_ledger_id` | ulid FK → owner_ledger, null | set once swept into the ledger (idempotency) |
| `created_by` | ulid FK users, null | |
| timestamps + softDeletes | | |

- Morph map: **`owner_expense`**. Maintenance-record source is a **forward hook** (no maintenance
  module yet) — cleaning can link to `housekeeping_tasks`; everything else is manual for now.

---

## 7. Owner isolation — the new access boundary (Step 6, designed here)

**Not a table — an enforcement layer.** An External-Owner user shares the org with the operator and
other owners, so `BelongsToOrganization` is necessary but **not sufficient**. Proposed mechanism:

- An `OwnerContext` (mirrors `TenantContext`) resolved from the authenticated user's linked `owner_id`
  (via `owners.user_id`). Set by a `SetOwnerScopeForPortal` middleware on the owner-portal route group,
  *after* tenant context is set.
- An `owner-scoped` query path for owner-facing reads (a `BelongsToOwner`-style scope or explicit
  `->where('owner_id', OwnerContext::id())` in the portal read services) covering `owners`,
  `units` (their own), `owner_statements`, `owner_statement_lines`, `payouts`, `owner_ledger`,
  `owner_expenses`. The operator/manager side does NOT apply this scope (sees all owners).
- **The owner side never reads folios/payments/other owners' rows.** Owner figures come only from
  `owner_statements`/`owner_ledger` (their own), never from the operator's raw financial tables.
- Livewire RMB rule (Phase 1 Step 9): typed models resolved via tenant+owner-scoped `findOrFail` in
  `render()`, never route-model-bound before middleware.
- Proven in Step 7 like tenant isolation: owner A ↛ owner B's rows; owner ↛ operator financials.

---

## 8. Morph-map additions (register in `AppServiceProvider`)

`owner`, `management_agreement`, `owner_ledger`, `owner_statement`, `owner_statement_line` (if it
becomes a polymorphic subject; else omit), `owner_expense`, `payout`. Added at the step that
introduces each model.

---

## 9. Attribution rule set — see the Step 0 message (separate section)

The default rules and the two flagged conflicts are in the Step 0 gate write-up (not duplicated here).
`management_agreements.attribution_rules` stores per-component overrides; missing keys = documented
default. This doc is approved together with that rule set.
