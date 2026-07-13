# Maintenance module — schema & integration design (Step 0 draft, for approval)

Maintenance tickets on units, with the two integrations that make this a real Vyrobel module rather than a
generic ticket system: **(1) out-of-service ↔ availability** and **(2) cost → owner deductible expense**.
Both slot into infrastructure that was deliberately left ready.

## Audit — what already exists (reuse/respect, do NOT duplicate)

| Concern | Already exists | How maintenance reuses it |
|---|---|---|
| Unit condition | `units.condition_status` enum `in_service \| out_of_order \| out_of_service` (Phase 1) | The OOS transition sets `condition_status = out_of_service`; resolving returns `in_service`. |
| Availability reads condition | `AvailabilityService::recompute` computes `out_of_order` = count of units with `condition_status ∈ {out_of_order, out_of_service}`; `available = total − sold − out_of_order − blocked` | **Out-of-service is ALREADY excluded** — we only need to trigger a recompute. No change to AvailabilityService. |
| Recompute trigger | `RecomputeAvailabilityOnInventoryChange` listens for `unit.condition_changed` (payload `{room_type_id, start_date, end_date}`) → recompute. **Nothing emits it yet** (left ready). | MaintenanceService emits `unit.condition_changed` on an OOS transition → the existing listener recomputes. |
| Booking capacity guard | `AssignmentService::occupy` → `lockAvailability` + `assertCountsAvailable` (Level A room-type count); public bookings pass `unit_id = null` (type-assigned) | Out-of-service reduces `available` → the count gates new bookings. **The locking core is untouched.** |
| Unit status history | `unit_status_logs` (append-only) with `type ∈ {housekeeping, condition}` | Condition transitions write a `type = condition` log (mirrors `HousekeepingService::markUnitHousekeepingStatus`). |
| Owner cost hook | `owner_expenses` with polymorphic `source_type/source_id` + `category` + `deductible`; `OwnerExpenseService::recordExpense(['source' => Model, …])` resolves owner-from-unit + deductibility from the agreement + posts the `owner_ledger` deduction line | Resolving a costed ticket on an OWNED unit calls `recordExpense(source: $ticket, category: 'maintenance')`. **The certified path — never a direct owner_ledger write.** |
| Append-only reversal | `OwnerExpenseService::voidExpense` posts a `rev:` reversal ledger line, respects the period-lock (`prior_period_adjustment` via `PeriodLockGuard::isSealed`) | Correcting/cancelling a resolved ticket voids its expense append-only. |
| Service template | `HousekeepingService` (guarded status transitions, `UnitStatusLog`, post-commit domain events, one-directional consumers) | `MaintenanceService` mirrors this shape exactly. |

**Conclusion:** the ONLY new table is `maintenance_tickets`. Everything else is reuse.

## New table — `maintenance_tickets`

ULID PK, `BelongsToOrganization` (tenant-scoped), `HasUlids`, `SoftDeletes`. Registered in the enforced
morph map as `maintenance_ticket` (it is the polymorphic `source` of an owner_expense).

| Column | Type | Notes |
|---|---|---|
| `id` | ulid PK | |
| `organization_id` | foreignUlid → organizations | tenant scope |
| `unit_id` | foreignUlid → units | the unit the ticket is on |
| `title` | string | short summary |
| `description` | text, nullable | detail |
| `category` | enum `plumbing \| electrical \| appliance \| structural \| other` | |
| `priority` | enum `low \| normal \| high \| urgent`, default `normal` | |
| `status` | enum `open \| in_progress \| resolved \| cancelled`, default `open` | guarded transitions |
| `takes_out_of_service` | boolean, default `false` | if true, the unit goes `out_of_service` while the ticket is active |
| `cost_minor` | bigint, nullable | integer minor units — filled at resolve() |
| `currency` | string(3), nullable | ISO-4217 — set with cost |
| `reported_by` | foreignUlid → users, nullable | |
| `assigned_to` | foreignUlid → users, nullable | |
| `opened_at` | timestamp | |
| `resolved_at` | timestamp, nullable | set at resolve/cancel |
| timestamps + softDeletes | | |

Index: `(organization_id, unit_id, status)`. The owner_expense link is the polymorphic `source` on
`owner_expenses` (query `source_type=maintenance_ticket, source_id=ticket.id`) — no denormalised FK column
on the ticket.

## Status lifecycle (guarded, mirrors HousekeepingService)

```
open ──start──▶ in_progress ──resolve(cost?)──▶ resolved
  │                   │
  └────── cancel ─────┴──▶ cancelled
open ──resolve(cost?)──▶ resolved        (allow resolving straight from open)
```
- `open → in_progress → resolved`, `open → resolved`, `open|in_progress → cancelled`.
- **Illegal jumps throw** (e.g. `resolved → in_progress`, cancel a resolved ticket, resolve a cancelled one).
- `resolve()` and `cancel()` are terminal; idempotent where sensible (re-resolving with the same state is a no-op-or-throw — resolving an already-resolved ticket throws; assigning is idempotent).

## Integration design 1 — OUT OF SERVICE ↔ availability

- A `takes_out_of_service` ticket, when **opened or started**, transitions its unit to `out_of_service`
  via `MaintenanceService::setUnitCondition(Unit, 'out_of_service')` — NOT inline. That method: updates
  `condition_status`, writes a `unit_status_logs` (`type=condition`) row, and emits **`unit.condition_changed`**
  with `{room_type_id, start_date, end_date}` = the unit's room-type **published availability window**
  (min..max of existing `availability.date`; if none published, a minimal `[today, today+1)`).
- The **existing** `RecomputeAvailabilityOnInventoryChange` listener recomputes → `out_of_order` rises →
  `available` drops → the unit's capacity is no longer offered (public search + operator + checkout).
- **Resolve/cancel** returns the unit to `in_service` — but ONLY if **no other active** (`open`/`in_progress`)
  `takes_out_of_service` ticket still holds that unit. Two OOS tickets → the unit stays out until BOTH close.
- **AssignmentService is NOT modified.** Out-of-service is a unit-level exclusion read by availability,
  exactly analogous to `out_of_order`/blocks. No-double-booking + single-write-path are untouched: new
  bookings are gated by the reduced count; `sold` is never touched by this path.
- **Existing-booking policy (never auto-cancel):** taking a unit out of service reduces **forward**
  availability; it does **NOT** cancel an in-house guest or a future reservation. Public bookings are
  type-assigned (`unit_id = null`), so they aren't tied to a specific physical unit; a *physically-assigned*
  reservation on the now-OOS unit is left intact and flagged for **manual handling** (the operator can move
  it via the existing guarded move-room path). If a unit goes OOS while its room-type is already fully sold,
  `available` may read negative — an honest "over-committed, resolve manually" signal, never hidden, and it
  still blocks new bookings (`assertCountsAvailable`).

## Integration design 2 — COST → OWNER EXPENSE (certified path)

- `resolve(ticket, cost)` records `cost_minor`/`currency` on the ticket. Then:
  - **OWNED unit** (`unit.owner_id !== null`): call `OwnerExpenseService::recordExpense([...'category' => 'maintenance',
    'source' => $ticket, 'unit_id', 'amount_minor' => cost, 'expense_date' => resolved date])`. Deductibility
    is resolved from the owner's agreement (`maintenance` deductible unless the agreement lists it in
    `non_deductible_categories`); a deductible expense posts an `owner_ledger` deduction line → flows onto
    the owner statement through the **certified** attribution/statement path. **Never a direct owner_ledger write.**
  - **OPERATOR-owned unit** (`unit.owner_id === null`): the cost is recorded on the ticket only — **NO
    owner_expense is created** (the operator's cost, explicit, not silently dropped).
- **Correction / cancel-after-resolve:** voiding/correcting a resolved ticket's cost calls
  `OwnerExpenseService::voidExpense($expense)` — an append-only reversal (never edits the original line),
  respecting the period-lock (a reversal landing in a sealed period carries as a `prior_period_adjustment`).
- **Period-locking:** a cost whose `expense_date` falls in an already-finalized period is claimed by the
  next statement as a prior-period adjustment — handled entirely by the existing `OwnerStatementService`
  gap-free projection + `PeriodLockGuard`. Maintenance does not touch it.

## Invariants preserved
Single inventory write path (AssignmentService only) · no-double-booking (count-gated; `sold` untouched) ·
append-only owner ledger (expense via `recordExpense`, reversal via `voidExpense`) · period-locking ·
tenant scoping (`BelongsToOrganization`) · enforced morph map · money in integer minor units · one-directional
event consumers (maintenance events never loop into inventory recompute except the intended
`unit.condition_changed`) · honesty rule (operator-owned cost is explicit, never dropped; never auto-cancel a booking).

## Deferred (not this module)
Styled maintenance UI (a later design pass — Step 4 ships an **unstyled** contract stub only) · maintenance
scheduling/preventive-maintenance · parts/vendor tracking · photo attachments on tickets (the `media` table
is ready if wanted later).
