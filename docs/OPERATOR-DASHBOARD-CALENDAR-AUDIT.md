# Operator Dashboard + Calendar — exact-match audit (PHASE 1, read-only)

**Pages:** `/app/dashboard` (`OperatorDashboard`) + `/app/calendar` (the rack; today `ReservationRack` at `/app/rack`)
**Wireframes:** `Operator Dashboard.html` · `Operator Calendar.html` (dc-runtime bundles — decoded)
**Date:** 2026-07-30 · **Status:** PHASE 1 complete; awaiting the read-only + new-reservation decisions.

---

## 0 · The operator realm already EXISTS — this is REUSE/EXTEND

The operator back office is a mature realm, not net-new:

| Foundation | State |
|---|---|
| **Shell** `<x-operator.shell active title subtitle viewer>` | Real. Nav groups already match the wireframe **exactly**: OVERVIEW (Portfolio/Dashboard/Insights/Search) · FRONT DESK (**Calendar**/Front desk/Reservations/Guest requests/Guests/Reviews/Messages) · OPERATIONS (Housekeeping/Team schedule/Maintenance/Units/Rates & availability/Channels) · FINANCE · SYSTEM. Role-gated items filtered; `channels` is the one honest account-gated null. `--vy-*` tokens + `#[Layout('components.layouts.operator')]`. |
| **Scoping** | TENANT-scoped (every model `BelongsToOrganization`); multi-property via `ResolvesCurrentProperty` / `PropertyContext`. **The operator sees FULL guest data** (they're the property manager — NOT the owner #28 restriction) — but names-only is the existing dashboard/rack convention; still no fabrication. |
| **Dashboard** `/app/dashboard` | Real + substantial (occupancy-tonight, arrivals/departures/in-house, revenue-today, housekeeping, arrivals list). Missing: 7-day occupancy, ADR, work-orders, maintenance, channels, read-only banner. |
| **Calendar** = the **rack** `ReservationRack` `/app/rack` | Real room-type × N-night grid: per-night availability (`AvailabilityService::availabilityRange`), booking bars (real `reservation_unit` lines, real guest/status/source/dates), lane-packing, window nav, tenant-scoped, read-only. New-reservation is a "Soon" stub; no room-type filter / OTA badge / out-of-order cell yet. **The "Calendar" nav already points here.** |
| **New reservation** | Real: `NewReservation` + `ReservationCreateController::store` → certified `AssignmentService::createBooking` (row-lock non-overlap), `UnitAvailabilityService` honest availability, name-only guest search, no raw PAN. Fires `BookingConfirmationMail` on `reservation.confirmed`. |

---

## 1 · The read-only / "workspace paused" mode — the cross-cutting investigate

**There is NO real "paused → read-only" workspace state.** `SubscriptionStatus` is binary: full access (trialing/active) or **lapsed** (expired-trial/past_due/canceled) → **hard paywall redirect** (`EnsureSubscriptionActive`). No state keeps an operator in `/app` with writes disabled; no read-only write-gate exists. The mandate says the pause state is the Billing prompt's job — "consume it, or a consistent stub."

**Decision A — how to build the read-only mode:**
- **Option 1 (recommended): a real, additive, ORTHOGONAL pause.** A nullable `organizations.paused_until` (a genuine operational pause a Billing/admin flow can set) + a shared `App\Support\Operator\OperatorWorkspace` gate (`isReadOnly()`, `resumeLabel()`). The shell renders the banner when set; writes consume the gate (starting with the calendar new-reservation). It's **real** (a genuine column + gate), **honest** (unset in the demo → no banner, because nothing is genuinely paused), and **disturbs nothing** — orthogonal to the subscription paywall (`EnsureSubscriptionActive` untouched; a paused org is still active/trialing, so no redirect fight). Browser-verified by setting `paused_until` via tinker → banner + writes off. Ready for the Billing prompt to drive.
- **Option 2: a pure display stub keyed on `isLapsed()`/`past_due`** — but lapsed orgs are hard-redirected to the paywall, so they'd never reach a read-only page (conflict). Not viable without changing the paywall.

**Recommend Option 1.**

---

## 2 · Dashboard — per-element classification

| Element | Class | Source |
|---|---|---|
| KPI: Arrivals today · Departures today | ✅ REAL-NOW | `FrontDeskService::arrivals/departures` (already computed) |
| KPI: Occupancy tonight (+Δ) | ✅ REAL / 🔨 Δ | `PropertyOccupancy::forProperties` (real); the "+5%" delta = vs yesterday (real derived, or honest-omit if no baseline) |
| KPI: Revenue today | ✅ REAL-NOW | `paymentsOn($date)` (payments ledger) |
| KPI: **Open work orders (N urgent)** | 🔨 BUILD | `MaintenanceTicket` active count + `priority='urgent'` count |
| **Today's tasks** (priority/category / "all caught up") | 🔨 BUILD | derived from the real active `MaintenanceTicket` (urgent/high) + `HousekeepingTask` (to-clean) + arrivals-not-checked-in — grouped, honest-empty |
| Arrivals · today (name/nights/unit/eta/status) | ✅ REAL (mostly) | the real arrivals list; nights/unit/eta enrich from the reservation (eta honest-omit if not modelled) |
| **Occupancy · next 7 days** (per-day %) | 🔨 BUILD | loop `PropertyOccupancy` per night → 7 real bars |
| Tonight (rooms sold X/Y, **ADR**) | ✅ REAL / 🔨 ADR | rooms sold real; **ADR** via `PerformanceMetrics::forProperties(...).adr()` (real — replaces the stale "Not yet available" stub) |
| Housekeeping (to clean / done X/Y) | ✅ REAL-NOW | `HousekeepingTask` status counts (already computed) |
| **Maintenance** (units out of order · on-hold rate) | 🔨 BUILD | out-of-order = distinct `unit_id` on active `takes_out_of_service` tickets; on-hold €/night = Σ nightly rate of those units (real from rate plans; honest-omit € if unpriced) |
| **Channels** (Booking.com/Airbnb/Expedia · last sync) | 🟠 HONEST | `Channel.status` + `last_synced_at` — real if a channel is genuinely connected; **in practice none is → honest "Not connected"**, NEVER a fake "Synced" (#39) |

## 3 · Calendar (rack) — per-element classification

| Element | Class | Source |
|---|---|---|
| The rack grid (units/room-types × days, availability, booking bars) | ✅ REAL-NOW | `ReservationRack` (real `reservation_unit` lines + `AvailabilityService`) |
| Booking-cell badges (Confirmed/Checked-in/Tentative/OTA/Out-of-order) | ✅ REAL / 🔨 | status/source badges real; **Out-of-order** cells from `MaintenanceTicket`/`Unit.condition_status` (build); OTA badge = real `source=ota` (honest — none if no channel) |
| Room-type filter | 🔨 BUILD | a real GET filter over the room-type rows (the toolbar's "Filters · Soon" → real) |
| **New reservation** (guest/stay/unit/source → confirm) | 🔨 BUILD (Decision B) | see §4 — the certified `AssignmentService::createBooking` |
| "Open the full form →" | ✅ REAL | link to `/app/reservations/create` (the existing full `NewReservation`) |
| "Confirmation email sent · appears on the rack" | ✅ REAL | `BookingConfirmationMail` fires on `reservation.confirmed`; the bar appears on the next rack render |

**Decision B — the calendar new-reservation:** the wireframe shows an inline quick-new-reservation panel.
- **Option 1 (recommended): build the inline quick panel** on the rack (guest name-match + adults, stay dates/nights, unit·available "check rack", source, summary → Confirm) posting to the certified `ReservationCreateController` (`AssignmentService::createBooking` — row-lock non-overlap; `UnitAvailabilityService` real availability; the real confirmation email), respecting read-only (disabled when paused). "Open the full form →" links to `/app/reservations/create`. Matches the wireframe exactly; reuses the certified engine (anti-drift).
- **Option 2:** just link "New reservation" → the full form. Simpler, doesn't match the inline panel.

**Recommend Option 1.**

---

## 4 · Reuse map (anti-drift)
`<x-operator.shell>` (+ the new read-only banner) · `PropertyOccupancy` / `PerformanceMetrics` (occupancy/ADR) ·
`FrontDeskService` (arrivals/departures/in-house) · `HousekeepingTask` · `MaintenanceTicket` · `Payment`
(revenue) · `AvailabilityService` + `ReservationRack` (rack) · `ReservationCreateController` +
`AssignmentService::createBooking` + `UnitAvailabilityService` (new reservation — the certified engine) ·
`BookingConfirmationMail` (confirmation) · `Channel` (honest status) · `Organization::billingStatus()`.

## 5 · Guards
🔒 operator/tenant-scoped (never owner-scoped) · the certified `AssignmentService` reused for the new
reservation (non-overlap = the SAME row-lock the owner block calendar's `blockInventory` uses — mutation-
proven) · the read-only gate respected (writes disabled when paused) · #39 no fabrication (no fake
channel-sync/Synced, no fabricated occupancy/ADR/on-hold, no invented reservation) · full guest data is
correct on the operator realm (they're the manager) but the existing name-only convention is kept.

## 6 · Build plan (two gates)
- **GATE 1 — shell read-only banner + Dashboard:** add the shared `OperatorWorkspace` gate +
  `organizations.paused_until` + the shell banner; extend `OperatorDashboard` with work-orders, today's
  tasks, 7-day occupancy, ADR, maintenance, channels (honest); match the design exactly. Tests: the KPIs
  real; the read-only gate; browser-verify.
- **GATE 2 — Calendar (rack + new reservation):** match the rack exactly (room-type filter, out-of-order
  cells, badges); build the inline quick new-reservation via the certified controller (respecting
  read-only); add the `app.calendar` route (repoint the nav). Tests: a real reservation via createBooking
  (non-overlap proven), the read-only gate blocks the write, the rack real. Consolidate docs; bump HEAD.

---

## FOUNDER DECISIONS
- **A — read-only mode:** a real additive `organizations.paused_until` + a shared `OperatorWorkspace` gate
  (recommended), vs a pure display stub.
- **B — calendar new-reservation:** build the inline quick panel via the certified controller
  (recommended), vs link to the existing full form.
- **(honest, not choices)** Channels = real status or "Not connected" (never fake Synced); maintenance
  on-hold €/night = real-from-rate-plans or honest-omit; OTA cells = real `source` (none if no channel).

## RESOLUTION
_(appended at GATE close)_
