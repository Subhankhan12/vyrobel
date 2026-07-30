# Operator Maintenance (Board + Costs + Settings) — exact-match audit (PHASE 1, read-only)

**Pages:** `/app/maintenance` (`MaintenanceList`) · `/app/maintenance/costs` (`OperatorMaintenanceCosts`) · `/app/maintenance/settings` (`OperatorMaintenanceSettings`) — all exist; being rebuilt to their wireframes.
**Wireframes:** `Operator Maintenance.html` · `Operator Maintenance Costs.html` · `Operator Maintenance Settings.html` (dc-runtime bundles — decoded).
**Date:** 2026-07-30 · **Status:** PHASE 1 complete; awaiting the labor/parts · exports · technician decisions.

---

## 0 · Core finding — the two HARDEST mandates are already CERTIFIED; the rest is design + targeted builds

The operator maintenance module is a mature, certified subsystem. **Two mandates are already built:**
- **⚠️ Mark-out-of-order genuinely blocks selling (Step 2) — CERTIFIED.** A `takes_out_of_service` ticket, when opened/started, sets its unit `condition_status='out_of_service'` (`MaintenanceService::setUnitCondition`) → emits `unit.condition_changed` → the existing `RecomputeAvailabilityOnInventoryChange` listener recomputes → `out_of_service` folds into the availability `out_of_order` term → `Availability::available()` drops → `AssignmentService` Level A (`available() < 1`, under `lockForUpdate`) rejects the booking. **Mutation-provable.** Released only when no other active OOS ticket holds the unit; existing bookings never auto-cancelled (flagged for manual moving).
- **⚠️ Costs tie to the owner Expenses (Step 3) — CERTIFIED + reconciling.** Resolving a costed ticket on an OWNED unit calls `MaintenanceService::postExpenseIfOwned` → `OwnerExpenseService::recordExpense(category:'maintenance', amount_minor:cost_minor, source:$ticket, approve_now:true)` → a real `owner_ledger` deduction line; the owner sees + (already approved) the expense on `/owner/expenses` (reconciled by the `source` morph). **cost_minor === owner_expense.amount_minor.** Operator-owned units record the cost on the ticket only (no owner_expense — explicit).

**So the genuine BUILDS are:** the SLA **breach computation** (config exists, never measured), the auto-assignment **wiring** (config exists, dead), **photos** on tickets (add `HasMedia`), a **labor/parts split + parts-on-order** (single `cost_minor` today), **Costs exports** (none), a **technicians panel** with real load, and the **exact designs**.

**⚠️ Category divergence:** the app's real ticket categories are **plumbing · electrical · appliance · structural · other** (5); the wireframe draws HVAC/Furniture/Windows&doors/Painting/Electronics (8). I'll use the app's real 5 (the `MaintenanceSetting` assignee map is keyed on them) — a documented KIND-3 divergence.

---

## 1 · Reuse map (anti-drift)

| Concern | Reuse | Notes |
|---|---|---|
| Work orders + lifecycle | `MaintenanceTicket` · `MaintenanceService` (open/assign/start/resolve/cancel/correctCost) · `MaintenanceActionController` | status open/in_progress/resolved/cancelled; cost at resolve |
| **Out-of-order blocks selling** | `takes_out_of_service` → `setUnitCondition` → availability recompute → `AssignmentService` guard | CERTIFIED — mutation-prove |
| **Costs ↔ owner Expenses** | `postExpenseIfOwned` → `OwnerExpenseService::recordExpense` (approve_now) | CERTIFIED — reconciles cost_minor→amount_minor |
| Revenue-on-hold | `OperatorDashboard::maintenance` `onHoldMinor()` (RateCalendar) | real nightly rate × blocked units; honest null when unpriced |
| SLA config | `MaintenanceSetting::sla_{urgent,high,normal,low}_hours` | stored; **breach NOT computed → BUILD** |
| Auto-assign config | `MaintenanceSetting::assignee_{category}_id` | stored; **NOT wired → BUILD** |
| Policy/notify | `MaintenanceSetting` (block/photo/front-desk + 5 notify toggles) | stored; notify-dispatch deferred (honest) |
| Costs analytics | `MaintenanceCostService::report()` (total/orders/avg/by_category/top_units/by_month/owner-vs-operator) | real; **labor/parts split refused today** |
| Photos | `RoomTypePhotoController` media pattern; add `use HasMedia` to the ticket | zero schema change (Media is generic) |
| Exports | `GuestBulkActionController::export` CSV pattern + `PdfService`/`ExportAudit` | gate on `maintenance.manage` (operator) |
| Technicians | active `Membership` members (`MaintenanceSettingsService::technicians()`) | no maintenance role/dept exists |
| Read-only | `OperatorWorkspace::isReadOnly()` (#84) | honor on every write |
| Timeline/activity | `MaintenanceTicketDetail` builds it from real `audit_events` (`maintenance_ticket.%`) | real |

---

## 2 · MAINTENANCE BOARD — per-element (`Operator Maintenance.html`)

| Element | Class | Plan |
|---|---|---|
| Header + search + Costs/Settings links | ✅ REAL | exists |
| KPIs **Open · Urgent · Awaiting-parts · Out-of-order · Resolved-7d** (as filter chips) | ✅/🔨 | Open/Urgent/OOO/Resolved real; **Awaiting-parts** needs the parts state (decision A) |
| Work-orders list (priority bar · title+OOO · sub · priority · tech/Assign · status) | ✅ REAL | the real tickets; restructure to the wireframe |
| Filters (by status) + search + **select/bulk** (Export CSV · Assign · Resolve) | 🔨 BUILD | bulk = loop the certified writes; CSV reuse the export pattern |
| **New work order** (issue/unit/priority/category/assign/description/**photos**/**mark-OOO**+warning) | ✅/🔨 | create real; **photos** BUILD (decision D); **mark-OOO** REAL (Step 2) + the honest "check the rack first" warning |
| **Assign technician** (recommended-by-category, load, When, Notify) | ✅/🔨 | real assign; "Recommended" = the category assignee; "When" honest; **Notify "sends to their phone" → honest** (in-app, no push) |
| **Work-order detail** (pipeline · facts · description · tech · **parts** · **photos** · **activity**) | ✅/🔨 | pipeline/facts/activity real (audit_events); **parts** (decision A); **photos** (decision D) |
| **Technicians panel** (name/load/status) | 🔨 BUILD | real derived — assignees + open-ticket counts |
| **Parts-on-order panel** | 🔨 BUILD (decision A) | a real parts state, or honest-omit |
| **Out-of-order hero** (count · blocked units · **revenue-on-hold €/night**) | ✅ REAL | reuse the Dashboard `onHoldMinor` |

## 3 · COSTS REPORT — per-element (`Operator Maintenance Costs.html`)

| Element | Class | Plan |
|---|---|---|
| Reporting month/range control | ✅/🔨 | real months/ranges over `MaintenanceCostService` |
| **Export menu** (PDF · CSV · Excel · email-accountant) | 🔨 BUILD (decision B) | PDF + CSV real (gated); Excel/email per the decision |
| KPIs Total-spend(+Δ) · Orders-closed · Avg · Parts-on-order | ✅/🔨 | spend/orders/avg real; **Parts-on-order** needs the parts split (decision A) |
| Spend-by-category · 6-month trend · top-units | ✅ REAL | `MaintenanceCostService` already returns these |
| **Billable work orders** (Date · WO · **Labor · Parts** · Total) + totals | 🔨 BUILD (decision A) | **Labor+Parts=Total (#17)** needs the split; **the totals RECONCILE to the owner Expenses** (the same cost) |
| Labor-vs-parts split | 🔨 BUILD (decision A) | real once the split exists (currently REFUSED) |
| Dark spend hero + **revenue-share** | ✅/⚠️ | spend real; revenue-share = maintenance ÷ real room revenue (`PerformanceMetrics`) |

## 4 · SETTINGS — per-element (`Operator Maintenance Settings.html`)

| Element | Class | Plan |
|---|---|---|
| **Resolution targets / SLA** (per-priority pickers) | ✅ config / 🔨 | the `sla_*_hours` columns EXIST + persist; **BUILD the real breach computation** (opened_at + target < now && active → breach) + the SLA bar on the board (decision, recommended) |
| **Auto-assignment** (master toggle + category→tech map) | ✅ config / 🔨 | the `assignee_*_id` map EXISTS + persists; **BUILD the wiring** (open() auto-assigns) + a master `auto_assign_enabled` toggle (recommended) |
| **Technicians** (list + **Invite**) | ✅/🔨 | list = real members + load; **Invite** real-or-honest (decision C) |
| **Notify-me** (5 toggles) | ✅ REAL | the `notify_*` columns persist (dispatch honest-deferred) |
| **Out-of-order policy** (block-sale · notify-front-desk · require-photo-on-resolve) | ✅ REAL | the columns persist; require-photo enforceable once photos exist (decision D) |

---

## 5 · The decisions (⛔ present + pause)

- **A · Labor/parts split + parts-on-order (the big one).** The Costs "Billable" table (Labor|Parts|Total), the labor-vs-parts split, the board's "Awaiting parts" status + the Parts-on-order panel all need data the app lacks (one `cost_minor` per ticket — the split is currently REFUSED as fabrication). **Recommend:** add `labor_minor`+`parts_minor` (labor+parts=`cost_minor`, reconciling #17) captured at resolve, + a real **awaiting-parts** state (`awaiting_parts` + `part_name`/`part_eta`) for the parts-on-order panel. The `owner_expense` stays = `cost_minor` (total) → the owner tie is unchanged. *Alt: keep the single total, refuse the split + parts (honest, but diverges heavily from both the board and Costs wireframes).*
- **B · Exports scope.** PDF report + CSV billable line-items are clearly real (reuse `PdfService`/the CSV pattern, **`maintenance.manage`-gated + audited**). **Recommend:** PDF + CSV **real**; **Excel = honest** (the CSV is the machine-readable export; an `.xls`-HTML is a hack); **email-to-accountant = a real Mailable** attaching the PDF to the operator's own email (MailSafetyGuard), or honest. *Alt: build Excel (.xls HTML) + a real accountant-email field too.*
- **C · Technician model + invite.** Assignees are any active org member today (no maintenance role/dept). **Recommend:** keep **assign-to-any-active-member**; the **Technicians panel** = the members configured as category assignees + anyone holding open tickets, with a real open-ticket **load**; **"Invite" = honest** (links to the existing staff-invite flow — a dedicated technician role is scope creep). *Alt: introduce a real Maintenance-role technician set (like housekeeping) + a real technician invite.*
- **(recommended, stated)** SLA breach = real computation (build it); auto-assignment = wire open() + a master toggle; photos = add `HasMedia` + the media-upload pattern; revenue-on-hold = reuse `onHoldMinor`; "notified on phone" = honest (in-app; notify-dispatch stays deferred). **Confirmed (reuse + mutation-prove):** out-of-order blocks selling (Step 2); costs↔owner-Expenses (Step 3).

## 6 · Guards (all gates)
🔒 operator-scoped; the `MaintenanceTicket` + `MaintenanceService` + availability + owner-Expense path reused (anti-drift — the Dashboard #84 + owner Expenses undisturbed); **out-of-order genuinely blocks selling (mutation-proven)**; #17 costs reconcile (labor+parts=total; the billable tie === the owner_expense); #20 every write controller-routed + audited; exports `maintenance.manage`-gated + audited; the **SLA is a real breach computation, never fabricated**; read-only (#84) respected; #39 no fabrication (no fake work-order/OOO/cost/SLA/technician/parts).

## 7 · Build plan
**GATE 1 (board):** `HasMedia` + a ticket-photo controller · the parts/labor migration + service (decision A) · auto-assign wiring + the SLA breach helper (for the board's SLA bar) → the board (KPIs/list/filters/bulk, New-WO with photos + mark-OOO, Assign modal, detail drawer with parts/photos/activity, Technicians + Parts-on-order + OOO hero) → tests (create · assign · **mark-OOO blocks selling** · photo · read-only) → browser → commit.
**GATE 2 (costs):** the labor/parts split in `MaintenanceCostService` + the billable table (reconciling to the owner_expense) → the exports (PDF + CSV, gated + audited) → tests (labor+parts=total; billable === owner_expense; export gated) → browser → commit.
**GATE 3 (settings):** the real SLA breach + the auto-assign wiring + the master toggle → the Settings page (SLA pickers, auto-assign map, technicians, notify, OOO policy) → tests (SLA breach real; auto-assign routes) → consolidate docs → browser → commit + push.

## RESOLUTION
_(appended at GATE close)_
