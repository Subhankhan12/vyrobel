# Operator Housekeeping + Insights — exact-match audit (PHASE 1, read-only)

**Pages:** `/app/housekeeping` (`HousekeepingBoard`) + `/app/insights` (`OperatorInsights`) — both exist; being rebuilt to their wireframes.
**Wireframes:** `Operator Housekeeping.html` · `Operator Insights.html` (dc-runtime bundles — decoded).
**Date:** 2026-07-30 · **Status:** PHASE 1 complete; awaiting the elapsed / Expedia / photo-scope decisions.

---

## 0 · Core finding — nearly every backend already EXISTS; this is match-the-design + a few real builds

Both pages have real backends already. Housekeeping = the operator MANAGING the real `HousekeepingTask` (the owner sees the SAME model read-only via `OwnerUnitDetail::cleanings` — must stay undisturbed). Insights = the real `PerformanceMetrics`/`MetricSet`/`BookingCadence` analytics (the same the Dashboard #84 + owner Insights use). The genuine BUILDS are: assign-all/auto-balance, HK photo capture (reuse the media pattern), a real "elapsed" timer (a `started_at` column), and the Insights **real-derived-insight engine** (with the Expedia/brand fabrications REFUSED).

**⚠️ The Expedia clarification:** "#85" in this codebase is Operator Billing, not OTA. The OTA honesty gate is the `OperatorChannels` decision — and **Expedia is genuinely not a connected channel with real data**: `reservations.source` has no OTA brand (an OTA stay is just `ota`), there are no Expedia bookings/commission/sync figures. So the wireframe's "Expedia disconnect costing €4,600", the Booking.com/Airbnb/Expedia **brand** channel-split, and the Marina-Expedia "suggested next step" have **no real data** → REFUSED/honest (#39), never a fabricated euro figure.

---

## 1 · Reuse map (anti-drift)

| Concern | Reuse | Notes |
|---|---|---|
| HK task + status advance | `HousekeepingTask` · `HousekeepingService::updateTaskStatus` · `HousekeepingActionController::updateStatus` | status pending/in_progress/clean/inspected; `completed_at`/`inspected_by` real |
| HK assign | `HousekeepingService::assign` + `StaffRoster::housekeeperIds()` guard · `can:housekeeping.manage` | real, audited |
| Crew | `StaffRoster::housekeepers()` (real Users w/ Housekeeping/Maintenance role) | **no load helper** → compute task-count per assignee |
| HK photos | the `RoomTypePhotoController` media pattern (`image` validate → `public` disk → `Media::create` mediable+collection); `HousekeepingTask` already `HasMedia` | BUILD a HK photo controller |
| Out-of-order | `Unit::whereIn('condition_status',['out_of_order','out_of_service'])` | the certified availability source |
| Owner read-only HK (LEAVE ALONE) | `OwnerUnitDetail::cleanings` | date/type/status only |
| KPIs occ/ADR/RevPAR/rev/LOS/cancel | `PerformanceMetrics::forProperties` → `MetricSet` | reconciles to the availability spine + FolioTotals |
| Monthly trend + vs-LY | `PerformanceMetrics::monthlyTrendForProperties` (+ `->subYear()`) | delta ONLY where prior-year > 0 (owner pattern) |
| Lead-time / weekday / on-the-books | `BookingCadence::*ForProperties` | weekday = nights-fill-first; onTheBooks = pace |
| Channel mix | `MetricSet::sourceNights` + `sourceLabel()` | REAL sources only (no OTA brand) |
| Portfolio scope | `PropertyContext::readIds()` / `$this->currentReadIds` | "all" = N ids |
| Channel status (Expedia honest) | `Channel` (status disconnected/connected/…); no OTA brand, no sync | Expedia has NO real data |

---

## 2 · HOUSEKEEPING — per-element (`Operator Housekeeping.html`)

| Element | Class | Plan |
|---|---|---|
| Header + **live clock** chip | ✅ REAL / presentation | "Housekeeping" + today; the ticking clock is client-side (cosmetic) |
| **Board / List toggle** + density (Cozy/Compact) | 🔨 BUILD | the current board is kanban-only; add a List view + the toggle + density (persisted client-side) |
| Filter (by housekeeper / Unassigned) + search | 🔨 BUILD | GET filters over the real tasks (`?crew=`, `?q=`) |
| **Status summary strip** Dirty·In progress·Clean·Inspected·**Out of order** | ✅ REAL | 4 = real `status` counts; **Out of order** = real `condition_status` units (not a task status) |
| Board **lanes** (Dirty·In progress·Clean·Inspected; last two collapsible) | ✅/🔨 | real status buckets; collapse client-side |
| **Room card** (unit·turnover·sub·urgency·housekeeper/Assign·stage-progress·action) | ✅/🔨 | unit/type/priority/assignee/status all real; **urgency "guest waiting"** = a real reservation arriving that day for the unit; **action buttons** = the real status advance (Start→in_progress / Mark clean / Inspect) |
| **stage-progress "Elapsed {m}/{exp}m"** | 🔨 BUILD (**decision A**) | needs a real start time — ADD `started_at` (set on →in_progress); expected-minutes per type a real constant · OR honest (no timer) |
| **Crew panel** (housekeepers·load·assigned-count·availability·Out-of-order·Schedule) | ✅/🔨 | `StaffRoster::housekeepers()` + a computed **load** (assigned open-task count); Out-of-order real; Schedule → the real roster |
| **Assign** (picker, least-load suggested, priority) | ✅ REAL | the real `assign` write; "suggested" = lowest real load; priority = set `priority=high` |
| **Assign-all / auto-balance** | 🔨 BUILD | a new `HousekeepingService::assignAll` (greedy least-load, waiting-guest first) + controller (`housekeeping.manage`, audited) |
| **Room detail** drawer (pipeline·info·guest·**photos**·action·guest-ready) | 🔨 BUILD | the stage pipeline + info from the real task; **PHOTO CAPTURE** = the media pattern (**decision C**) |
| "Notified on device" | 🚫 HONEST (**decision, stated**) | the task appears in the crew member's real task list; "notified on their device" softened (no fabricated push — no device channel exists) |
| Out of order / Schedule | ✅ REAL | condition_status units; roster link |

## 3 · INSIGHTS — per-element (`Operator Insights.html`)

| Element | Class | Plan |
|---|---|---|
| "Portfolio insights" + **range presets** (YTD/6m/Summer/Q4-on-books/Full-year) | ✅/🔨 | real ranges over `PerformanceMetrics`; the current page has 3 ranges → add the preset set |
| Property filter chips (All + per property) | ✅ REAL | `PropertyContext` — real properties (demo has 2) |
| **KPI cards** Occupancy·RevPAR·ADR·Booking-window + **vs-LY** | ✅ REAL / honest | occ/RevPAR/ADR (`MetricSet`) + window (`BookingCadence` median); **vs-LY** real where prior-year data exists (demo seed), else honest-omit |
| **Occupancy-by-month** (this-year / on-the-books / last-year) | ✅ REAL | `monthlyTrendForProperties` (+ `->subYear()` for LY, honest where none) + on-the-books = future confirmed |
| **Pace by property** (booked% vs LY) | ✅ REAL | per-property forward occupancy; vs-LY honest where none |
| **By-property** table (Occ/ADR/RevPAR YTD) | ✅ REAL | `PerformanceMetrics::forProperties` looped per property |
| **Channel mix** | ⚠️ REAL sources only | `MetricSet::sourceNights` — REAL sources (Direct/OTA/Corporate/…); the wireframe's **Booking.com/Airbnb/Expedia brand split is NOT modelled → REFUSED** (#39) |
| **Nights that fill first** (day-of-week) | ✅ REAL | `BookingCadence::weekdayOccupancy` |
| **"Worth a look" cards** | 🔨 BUILD (**decision B**) | **REAL DERIVED**, threshold-gated: **Saturdays** = the real peak weekday occupancy; **Fridays turnover** = the real check-out-day distribution. **The Expedia €4,600 card + the Marina-Expedia "suggested next step" = REFUSED** (no real Expedia data) → honest-omit or a "connect a channel" gated line. "Decisions stay yours" kept. |
| Suggested next step (dark CTA) | ⚠️ | a REAL derived suggestion (the biggest real occupancy gap) OR honest-omit; NOT the fabricated Marina-Expedia one |
| Multi-property | ✅ REAL | portfolio via `readIds` (honest single-property if only one) |

---

## 4 · The decisions (⛔ present + pause)

- **A · The "elapsed" timer.** The board shows `Elapsed {m}/{exp}m` for in-progress rooms; there's no `started_at` column today. **Recommend:** ADD a `started_at` column (set when a task advances to `in_progress`) so elapsed is a REAL figure; expected-minutes per turnover type a documented constant (Departure 45 / Deep 90 / Stayover 30 / Arrival-prep 20). *Alt: honest — no elapsed bar (show the status stage only).*
- **B · The "Worth a look" insight engine + Expedia.** **Recommend:** a real-derived-insight helper — each card computed from real data (peak-weekday occupancy; check-out-day concentration; the biggest occupancy gap), shown ONLY when it clears a real threshold; **the Expedia €X card + the brand channel-split + the Marina-Expedia suggested-step are REFUSED** (no real Expedia data — #39), replaced by an honest "connect a channel to see channel-level pace" line where the design put the Expedia card. *This is really a confirm — the fabrication can't be built honestly.*
- **C · Photo capture scope.** **Recommend:** a single **"proof" photo collection** on the task (matches the wireframe's "attach proof of clean" / "damage photos" — one block), reusing the `RoomTypePhotoController` media pattern (validated image → `public` disk → `Media` mediable+collection), `housekeeping.manage`-gated, read-only-respecting. *Alt: two collections (before/after).* 
- **(stated, not choices)** crew = real `StaffRoster` (no new model); assign/assign-all = real audited writes; "notified on device" = honest (real task-list entry, no fake push); multi-property = real portfolio; vs-LY = real-where-data-honest-else; the owner read-only HK view stays untouched.

## 5 · Guards (all gates)
🔒 operator-scoped; the `HousekeepingTask` + analytics reused (anti-drift — the owner HK read-only view + shared analytics undisturbed); #20 assign/assign-all/status/photo controller-routed + `can:housekeeping.manage` + audited; the insight cards **REAL-DERIVED-ONLY** (Expedia/brand fabrications refused, #39); read-only (#84) respected on every HK write; #17 the analytics reconcile (shared helpers).

## 6 · Build plan
**GATE 1 (Housekeeping):** `started_at` migration + `assignAll` service + a HK photo controller → the Board/List/density/filter + crew panel + assign/assign-all modals + room-detail drawer (photos) → tests (assign · assign-all balances by load · photo upload · status advance · read-only) → browser → commit.
**GATE 2 (Insights):** the range presets + KPIs/vs-LY + occupancy-by-month(+LY+on-books) + by-property + pace + channel-mix(real sources) + day-of-week → the **real-derived-insight helper** (Saturdays/Fridays/gap; Expedia honest) → tests (insights compute from real data / honest when not; vs-LY honest; channel-mix real sources) → consolidate docs → browser → commit + push.

## RESOLUTION
_(appended at GATE close)_
