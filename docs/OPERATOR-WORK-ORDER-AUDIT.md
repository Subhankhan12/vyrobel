# Operator Work Order detail — exact-match audit (`/app/maintenance/{ticket}`)

**Wireframe:** `docs/design/raw/Operator Work Order.html` · **Page:** the EXISTING `app/Livewire/MaintenanceTicketDetail.php` (+ `resources/views/livewire/maintenance-ticket-detail.blade.php`), built in #89 · **Model:** `MaintenanceTicket` (the SAME the Maintenance board `MaintenanceList` uses — anti-drift already satisfied by construction).

PHASE 1, read-only. Decode (dc-runtime `__bundler` bundle → `JSON.parse` the `type="__bundler/template"` script → the 897-line `<x-dc>` body + the trailing `data-dc-script` Component state) + 7-dimension investigation (existing page, model+timeline, SLA, out-of-order/rack, resolve-cost/owner-Expenses, technicians/photo/read-only). Extracted markup: `scratchpad/work-order-template.html`.

## Headline

The page **already exists on the right design system** (`x-operator.shell active="maintenance"` + `--vy-*`), with the ticket facts, the **real `audit_events` activity timeline**, state-gated lifecycle actions, the **real shared SLA** (`MaintenanceSla` #89 — the SAME the board reads), the **real OOS impact** (blocked-from-sale + affected-booking count), and the **real cost destination** (owner Expense) all wired. The wireframe is a **richer visual layout** (2-col grid, separate right-rail cards, two MODALS, a left-column Activity composer, Reported-issue photos). So this is an **exact-match restyle + gap-fill**, reusing every existing service — the board, availability/rack, costs, owner-Expenses and #88 attachment pattern all **stay untouched**.

## Per-element classification

### Header + chrome
| Element | Verdict | Reuse / plan |
|---|---|---|
| Operator shell (sidebar/Maintenance active+badge, switcher, bell, paused banner, ambient bg) | ✅ REAL-NOW | `x-operator.shell active="maintenance"` — already used, matches. No work. |
| Breadcrumb `‹ Maintenance / {ref}` | ✅ REAL-NOW | `route('app.maintenance')` back-link (present). The `WO-2041` tail → **see Reference (NOT-BUILDABLE)**. |
| h1 title + `In progress` + `Urgent` + `⊘ Out of order` badges + sub-line | ✅ REAL-NOW | `FormatsOperatorData::maintenanceStatusMeta`/`maintenancePriorityMeta`/`maintenanceCategoryLabel`; OOS badge only when `$isOos` (`takes_out_of_service && unit.condition_status==='out_of_service'`). Restyle only. |
| `Add update` button (focus composer) | 🔨 BUILD | Alpine focus/scroll to the composer textarea (depends on the composer, below). |
| `Mark resolved` button → **modal** | 🔨 BUILD | `route('app.maintenance.resolve')` exists; build the modal (Alpine), keep state-gating (Start on open, Resolve on active). |

### Details panel (4-col, 8 facts)
| Fact | Verdict | Note |
|---|---|---|
| Unit / Category / Priority / Reported(by) / Assignee / Reference(property) | ✅ REAL-NOW | All already loaded; restyle to the 4-col grid. |
| Priority sub `Same-day target` | 🔨 BUILD | Derive from `MaintenanceSla::targetHours ≤ 24h` (a small label map beside `slaLabel`). |
| `Open for 2h 03m · since 09:12` | 🔨 BUILD | `now − opened_at`, server-computed static render (real `opened_at`). |
| **Area** (`Living room / Wall unit`) | 🚫 NOT-BUILDABLE | No `area` column; capturing it means touching the board's **create** flow (must stay undisturbed) → **OMIT** (honest reduced grid). |
| **Reference `WO-2041`** (sequential) | 🚫 NOT-BUILDABLE | Tickets are ULIDs; no `WO-####` counter → show a short ULID tail (clearly not a sequential #) or drop the crumb tail. Property name IS real for the sub. |
| Category sub / Assignee specialty (`Air conditioning` / `Plumbing & HVAC`) | 🚫 NOT-BUILDABLE | No sub-descriptor / specialty columns → OMIT or show the member's real role. |

### Reported issue + Activity
| Element | Verdict | Reuse / plan |
|---|---|---|
| Description | ✅ REAL-NOW | `ticket.description`. |
| Reported-issue **photos** + `Add photo` | 🔨 BUILD (backend real) | `MaintenanceTicket` `HasMedia('mt_photo')` + `route('app.maintenance.photos.store'/'.destroy')` **already exist** (#88/#89) — **surface** real thumbnails + upload; honest empty state. |
| Activity **timeline** (dot/line/action/time/actor/note) | ✅ REAL-NOW | `MaintenanceTicketDetail::timeline()` from `audit_events` (`maintenance_ticket.%`). Restyle: per-type dot colours (presentation map) + merge note bodies. |
| Activity **composer** (Note / Progress / Part-ordered + textarea + Post update) | 🔨 BUILD | Reuse `Note` + `HasNotes` (add `notes()` to `MaintenanceTicket`) + a **`MaintenanceService::addNote(ticket, body, kind)`** writer (records audit like the lifecycle methods) + `route('app.maintenance.notes.store')`. Add a nullable **`kind`** column on `notes` for Note/Progress/Part-ordered (additive; **reuse Note — never a parallel updates table**). Timeline merges `audit_events ∪ notes` by time. |

### Right rail
| Card | Verdict | Reuse / plan |
|---|---|---|
| Dark **Status hero** (status + context sub + Mark-resolved) | ✅ REAL-NOW | Already the `.vy-tonight` hero. Restyle to the dark gradient. |
| Status hero **`€X / night on hold`** (count-up) | 🚫 REFUSED (#39) | The nightly rate is real (`UnitAvailabilityService`/`RateCalendar`) but **"affected nights" has no real value** — a ticket has no end/reopen date (`part_eta` optional) → the horizon is fabricated. **Keep refused** (as today). The real number is the affected-arrival **count** (below). |
| **Assignee** card (name + Reassign) | ✅ REAL-NOW / 🔨 restyle | `ticket.assignedTo.name`; Reassign → the modal (below). Specialty / `On site` presence 🚫 NOT-BUILDABLE → omit or show role. |
| **SLA** card (`Same-day target ~4h left` + progress bar + Reported/Due-by) | ✅ REAL-NOW / 🔨 surface | `MaintenanceSla::evaluate` (already `$sla`) — surface `deadline` (Due by), `remaining_hours` (~Xh left), progress % = elapsed/target, and `opened_at` (Reported). **The ONE helper — board + detail can't drift.** |
| **Out-of-order impact** (`N arrival tonight needs a room` + blocked-from-sale + open-the-rack) | ✅ REAL-NOW / 🔨 narrow | Component already computes `$oos.bookings_to_move = ReservationUnit::active()->where('unit_id')->count()`; **narrow to `whereDate('check_in', today())`** for "arrival tonight" (upcoming: `>= today`). Guest via `ReservationUnit→reservation→leadGuest` (name-only #28 — the same read the rack uses). Rack link `route('app.rack')`. **Caveat:** the rack is read-only (drag-move deferred) → the link lands the operator on the calendar to see/manage, but the actual move isn't wired there yet (honest). |

### Mark-resolved MODAL (GATE 2)
| Field | Verdict | Reuse / plan |
|---|---|---|
| Modal shell (overlay/glass/close/CTA) | 🔨 BUILD | Alpine (Livewire-4, no dep), same pattern as the notif-bell popover. |
| **Parts cost** (€) | ✅ REAL-NOW | `resolve()` already accepts it → `parts_minor`; on an owner-owned unit posts a deductible `OwnerExpense` (**reconciles #17, automatic**). Just move into the modal. |
| **Labour cost** (€) | ✅ REAL-NOW | `resolve()` accepts `labor` → `labor_minor`; `costSplit()` enforces `labor+parts===cost` (#17). The owner-billed total = labor+parts. **(The wireframe conflates "time spent" with cost — we keep the labour MONEY field so the owner bill stays real; see Time spent.)** |
| **Time spent** (hours, `e.g. 1.5h`) | 🔨 BUILD (new real column) | No `labor_hours` column → add a nullable real `labor_hours` decimal captured at resolve (informational, real). **Honest split:** hours can't be auto-priced (no labour rate modelled → would fabricate), so hours are informational and the owner bill stays the entered money. |
| **What was done** | 🔨 BUILD (new real column) | No `resolution_notes` column → add a nullable `resolution_notes` text captured at resolve **and** posted to the Activity timeline (via the note writer). |
| **Return to sale** toggle | 🔨 BUILD | Default ON = the real behaviour (`resolve → releaseUnitIfClear`). OFF path = add an optional `keep_blocked` flag to `resolve()` that **skips** `releaseUnitIfClear` (leaves `out_of_service`) — additive, inside the service. Drives the CTA label. |
| **Resolution outcome** chips (Fixed / Part replaced / …) | 🚫 NOT-BUILDABLE | No `resolution` enum → OMIT (or a real nullable enum later; not needed for the money/lifecycle path). |
| **Notify the front desk** toggle | 🔨 BUILD (real) or honest-defer | The `FeedNotification` + `DispatchNotificationsForDomainEvent` infra exists (UnitOutOfOrder/UrgentWorkOrder already dispatch). A resolve→"back online" notification off the real `maintenance_ticket.resolved` domain event reuses `toOperators` — build real (recommended) rather than a dead toggle. |

### Reassign-technician MODAL (GATE 1)
| Element | Verdict | Reuse / plan |
|---|---|---|
| Modal + tech radio-list + **load** (`N open · M urgent`) + Reassign CTA | 🔨 BUILD | `MaintenanceTicketDetail::orgStaff()` (real members) + a **real load count** (`MaintenanceTicket where assigned_to=member, status in open/in_progress`); select → `route('app.maintenance.assign')`. `Current` pill = compare `ticket.assigned_to`. |
| Technician **availability** (Busy/Available) | 🚫 NOT-BUILDABLE | Not modelled → omit or show role. |
| **Reason** chips (optional) | 🔨 BUILD (small) or omit | Capture the reason into the `assign` audit/domain-event payload as a real recorded field (never a chip that persists nothing). |
| **Notify both technicians** toggle | 🔨 BUILD (real) or honest-defer | Reuse the dispatch infra off `maintenance_ticket.assigned` (new assignee gets it; prior is told). |

## Reuse map (anti-drift — reuse, never duplicate)
`MaintenanceTicket` (the ONE model, board+detail) · `MaintenanceService` (the ONE guarded write path: `open/start/assign/resolve/cancel/setAwaitingParts` + `takeUnitOutIfNeeded`/`setUnitCondition`/`releaseUnitIfClear` + `postExpenseIfOwned`) · `MaintenanceActionController` (PRG routes, **already read-only-guarded** via `paused()`) · `App\Support\Maintenance\MaintenanceSla` (the ONE SLA helper) + `MaintenanceSetting.sla_*_hours` · `AvailabilityService::recompute` (the ONE certified availability spine — OOS drives `out_of_order`, the exact parallel of `OwnerUnitStatusService` #69) · `AssignmentService::assertCountsAvailable` (the room-type refusal that makes the block real) · `ReservationUnit::active()` + `Reservation::leadGuest` (affected arrival, name-only #28) · `MaintenanceCostService::report` (Costs report — same `cost_minor`) · `OwnerExpenseService::recordExpense` (owner bill — same integer-minor, **reconciles #17**) · `HasMedia('mt_photo')` + the photos routes (#88/#89) · `Note` + `HasNotes` (composer) · `FormatsOperatorData` (status/priority/category/SLA labels) · `FeedNotification` + `DispatchNotificationsForDomainEvent` (notify) · `x-operator.shell` + `--vy-*` tokens.

## The ⚠️ centerpiece decisions (real vs honest)
- **Out-of-order genuinely blocks — REAL, mutation-provable.** `setUnitCondition('out_of_service')` → `unit.condition_changed` → `AvailabilityService::recompute` counts it into `out_of_order` → `available` drops → `AssignmentService::assertCountsAvailable` refuses the booking (at **room-type capacity** — not a per-physical-unit hard refusal, which is the existing certified semantics). Resolve/cancel → `releaseUnitIfClear`. **Provable both ways** (blocked while OOS, sellable after resolve).
- **Affected arrival — REAL** (reuse `ReservationUnit::active()`, narrow to today/upcoming; guest name-only #28; the rack link is real — the actual drag-move on the rack stays honestly deferred).
- **Revenue-on-hold `€X/night` — REFUSED (#39).** Rate is real but the affected-nights horizon is fabricated. Keep refused; show the real affected-arrival count instead.
- **Mark-resolved cost — already REAL + reconciling #17, automatic.** `resolve()` posts the `OwnerExpense` inline (owner-owned units), same integer-minor as `cost_minor`, and the Costs report sums the same column. The BUILD is exposing labour + parts inputs, plus new **real** `labor_hours` (informational) + `resolution_notes` (also to the timeline).
- **Return-to-sale — REAL, mutation-provable** (default ON = today's release; new `keep_blocked` option for OFF).
- **Reassign / photos / activity-update — REAL** (reuse assign path + orgStaff + real load counts; the #88 media pattern; the `Note` store).

## Recommended founder decisions (proceeding under the standing pre-approval)
1. **NOT-BUILDABLE proto fields → OMIT honestly** (never fabricate): Area, `WO-####` sequential ref (use a short ULID tail), category sub-descriptor, assignee specialty, `On site` presence, technician availability, resolution outcome chips, `€/night on hold`. Style the honest reduced state.
2. **BUILD the real resolve captures** the mandate calls ⚠️-real: `labor_hours` (Time spent, informational) + `resolution_notes` (What was done, also to the timeline) as **new real columns**; parts/labour **cost** stay the reconciling money that bills the owner (#17). Honest note: hours are not auto-priced (no rate modelled).
3. **BUILD the composer for real** — `Note` + `HasNotes` on the ticket + a nullable `kind` column (Note/Progress/Part-ordered) + `MaintenanceService::addNote`; **reuse Note**, no parallel table.
4. **BUILD return-to-sale OFF** as a real `keep_blocked` option on `resolve()`.
5. **BUILD notify real** (front-desk on resolve; both technicians on reassign) off the existing domain events + `FeedNotification` infra — not a dead toggle.
6. **Reassign reason** → capture into the assign audit/domain payload (real) or omit; **do not** render a chip that persists nothing.
7. **Migration is additive-only** (`maintenance_tickets`: `labor_hours` decimal null, `resolution_notes` text null; `notes`: `kind` string null) — **no change to the board's create flow** (undisturbed).

## Build plan (gated, per-gate commit + push)
- **⛔ GATE 1 — Work Order detail exact-match:** the 2-col layout; header/badges (real); Details 4-col (real facts, honest omissions); Reported-issue + **photos** (surface #88 media + upload); **Activity timeline + composer** (real notes + `kind`); dark Status hero (real, `€/night` refused); **SLA card** (surface `MaintenanceSla` due-by/open-for/progress); **Out-of-order impact** (real affected-arrival narrowed to today + rack link); **Reassign modal** (real orgStaff + load + assign). Read-only respected (already). Tests: OOS blocks + affected-arrival; SLA computes; reassign; photo; composer note. Browser-verify. Commit + push.
- **⛔ GATE 2 — Mark-resolved (+ Reassign notify) + consolidate:** the Mark-resolved modal — parts/labour cost (real, #17) + `labor_hours` + `resolution_notes` (new real columns, to the timeline) + **return-to-sale** (real `keep_blocked`) + **notify front desk** (real); Reassign **notify both** + reason. Tests: resolve captures cost (#17 reconciles to `OwnerExpense`) + returns-to-sale (mutation-proven un-block) + keep_blocked path; notify sends. Consolidate docs (this file gaps→resolved · `DECISIONS.md` dated · `DIVERGENCE-REGISTER.md` Work Order → exact-design · `PROJECT-STATE.md` + invariant bump). Full suite green + PHPStan L5 + Pint + `migrate:fresh --seed`. Browser-verify. Commit + push.

---

## OUTCOME — gaps → resolved (2026-07-31, PHASE 2 delivered, gated; invariant #108)

The Work Order detail (`/app/maintenance/{ticket}`) matched to `Operator Work Order.html` EXACTLY on the REAL `MaintenanceTicket` — the DETAIL of the SAME model the board lists (anti-drift; the board stays untouched).

**GATE 1 (`9e92450`) — exact-match detail + SLA + OOS impact + reassign/photos/composer.** The 2-col layout; dark Status hero; 4-col Details; Reported-issue + real photos; Activity timeline + composer; Assignee; SLA; Out-of-order impact; two modals (Reassign; Mark-resolved shell). REAL: the **Activity composer** posts a real staff update (Note / Progress / Part-ordered) via a NEW `MaintenanceService::addNote` through the SHARED polymorphic `Note` store (nullable `notes.kind`; never a parallel table), and the timeline **MERGES** real `audit_events` ∪ real notes with per-type dot colours; the **SLA** is surfaced from the ONE `MaintenanceSla` helper the board reads (due-by, countdown, progress bar, open-for) — honestly ABSENT when no target is configured; the **out-of-order impact** is the real block + a real affected-booking count **narrowed to "N arrival(s) tonight"** with the real rack link; the **Reassign modal** lists real org staff with a REAL open-work load (N open · M urgent); **photos** are real Media (mt_photo). HONEST omissions (#39): Area, sequential WO-#### (a real ULID tail instead), technician specialty / "On site" / availability, resolution-outcome chips, and the "€X / night on hold" revenue-on-hold figure (unmodelled horizon). Also improved the shared `HasMedia::media()` with a generic (mirrors `HasNotes`). +7 `OperatorWorkOrderTest`; `MaintenanceDetailViewTest` copy updated; maintenance suite 76/76.

**GATE 2 (`4874e53`) — Mark-resolved (cost #17 + hours + notes + return-to-sale + notify) + reassign flow.** The full **Mark-resolved** modal: What-was-done → a new real `resolution_notes` column ALSO posted to the timeline; **Time-spent** → a new real `labor_hours` column (informational — hours are NOT auto-priced, no labour rate is modelled, so they never touch the owner bill); **Parts-cost** (€) bills the owner via the certified `OwnerExpenseService` (**parts_minor === cost_minor**, the SAME integer-minor the Costs report sums — reconciles #17); **Return-to-sale** (default ON) genuinely un-blocks the unit via the certified `releaseUnitIfClear`, and toggled OFF a new `keepBlocked` option leaves it out of order (both mutation-proven; `sold` never touched); **Notify-front-desk** = a real, preference-gated `MaintenanceResolvedNotification` to the reporter, sent ONLY on the toggle. The **Reassign flow** gained reason chips (a real recorded field on the assign audit/domain payload) + a "Notify both technicians" toggle → real `MaintenanceReassignedNotification` to the new (+ prior) assignee. Two new CATALOG entries. Caught a **raw-blade `word@if` trap** in the modal copy before it shipped. +4 `OperatorWorkOrderTest` (cost #17 reconciles to the owner expense + returns-to-sale un-block; keep-blocked stays out of order; notify toggle-respecting; reassign notifies both + records the reason).

**Migration** is additive-only (`notes.kind`; `maintenance_tickets.labor_hours`/`resolution_notes` — no positional `after()` so it survives a fresh migrate despite the later-dated parts migration). Every write is `can:maintenance.manage` (#30) + read-only-respected + audited; the board / availability-rack / Costs report / owner-Expenses / #88 attachment pattern are undisturbed. Suite green; PHPStan L5 (0); Pint clean; `migrate:fresh --seed` clean. **Browser-verified end-to-end** as demo-staff: composer → real Progress update; reassign → Rosa Fernandes (real load counts); SLA card counts down; full Mark-resolved → resolved with €45 parts + 1.5h + notes, **unit 101 returned to sale (in_service)**, the resolution note in the timeline.

**Presentation follow-ups (documented, not built):** surfacing open shifts / a "published" stamp on the schedule PDF is unrelated; here the only deferred niceties are the wireframe's proto-only motion (count-up, JS toast — the app uses the real flash + `.vy-fade`) and the NOT-BUILDABLE fields listed above (omitted honestly, would require new columns / fabrication).
