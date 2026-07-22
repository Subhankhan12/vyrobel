# Vyrobel — Design-Fidelity Divergence Register

The deep per-page design pass classifies **every** app page against its `docs/design/raw/` prototype into one
of three categories, and records the outcome here. This is a deliberate, honest artifact: it documents every
place the app differs from the prototype **on purpose** — because the difference is real/sparse data (not the
prototype's fabricated fill) or the presentation boundary (a look that would need forbidden logic/data/JS) — so a
future reader knows it's a considered choice, not an oversight.

**The three categories (the sharpened #1 rule):**
- ✅ **RESTRUCTURED** — had KIND-1 gaps (layout/arrangement: KPI row, right-rail panel, progress bar, card-vs-table,
  grouping, spacing, empty-state treatment, icons) → fixed with the shared tokens/components. Presentation-only.
- ⚖️ **AT PARITY** — the presentation already matches the prototype (much of the app was built from
  `docs/design/design-system-brief.md`). No change.
- 📐 **INTENTIONAL DIVERGENCE** — only KIND-2 and/or KIND-3 gaps remain, deliberately not "fixed":
  - **KIND-2 (fabricated content)** — the prototype looks fuller because it's populated with invented data
    (84% occupancy, Marco Reyes, task lists, synced-channel panels, fabricated arrivals). The honesty rule forbids
    replicating it; the real/sparse/empty state is styled to the prototype's *treatment* instead.
  - **KIND-3 (beyond the presentation boundary)** — the prototype's look would require a different data-shape the
    component doesn't fetch, or client-side interaction not built — i.e. a logic/data/JS change the presentation-only
    rule forbids. Recorded with the reason; NOT chased.

**Boundary:** presentation only (Blade/CSS/components/layout/states); never behaviour/logic/services/queries/guards/
routes/permissions. Suite 924+ / PHPStan L5 / Pint green after every page-commit.

---

## Operator app

| Page | Category | What / Why | Evidence |
|---|---|---|---|
| **Shared shell (sidebar)** | ✅ RESTRUCTURED | Regrouped the sidebar into the prototype's sections OVERVIEW / FRONT DESK / OPERATIONS / FINANCE / SYSTEM (was flat OPERATIONS/DISTRIBUTION/OWNERS/ACCOUNT). Propagates to every operator page. Every route + permission gate preserved verbatim; two labels aligned (Inbox→Messages, Rates→Rates & availability). | `bdeb3f7`; `docs/audit/design/gate0-op-dashboard-newsidebar.png` |
| **Dashboard** | ✅ RESTRUCTURED | Uniform 5-tile KPI row (Arrivals · Departures · Occupancy · Revenue · In-house) replacing the dark-tile-in-row; a right-rail dark "Tonight" panel (real occupancy % + rooms sold X/Y + available); a housekeeping progress bar. REFUSED KIND-2: the prototype's 84% occupancy (shows the real 0%), task list, synced-channel + maintenance panels, fabricated arrivals + occupancy chart. Kept the honest deferred-analytics + empty-arrivals states, styled. | `6ae6d3e`; `docs/audit/design/gate1-dashboard-after.png` vs `proto-op-dashboard.png` |
| **Reservation Rack** | 📐 INTENTIONAL DIVERGENCE | KIND-3: prototype is a per-**unit Gantt** with spanning reservation bars; the app is a per-**room-type availability-counts grid**. A Gantt needs per-unit reservation spans the component doesn't fetch — a data/logic change the presentation-only rule forbids. The grid's presentation (toolbar, heat cells, legend, status tones) is at parity. | `docs/audit/design/proto-op-calendar.png` |
| **Front Desk** | 📐 INTENTIONAL DIVERGENCE | KIND-3: prototype uses Arrivals/Departures/In-house **tabs** + an hourly **timeline** + an **Unassigned** count — all JS/data the component doesn't provide. The app already matches the KPI band (incl. the accent "attention" tile = the prototype's Unassigned tile) and the arrival-row treatment (unit · source · nights/guests · balance · Check-in), shown as 3 simultaneous columns. | `docs/audit/design/gate1-frontdesk-current.png` vs `proto-op-frontdesk.png` |
| **Reservations List** | ✅ RESTRUCTURED | KIND-1: the 3 KPI tiles were a dark-hero + 2 light at 3/4 width → now **3 uniform light tiles spanning full width** (`.vy-dash-kpis--3`), matching the prototype's KPI band. The table (Guest+ref · Stay · Unit · Source · Stage · Total · Balance due), filter tabs, search, and New-reservation were already at parity. **KIND-3 (documented):** the prototype's bulk-**Select** + month-picker need JS the app doesn't build. Also fixed a shared-grid regression this pass introduced: `.vy-dash-kpis` (used by 9 pages, 2–5 tiles) is now count-agnostic via `--3/--5` modifiers over a `repeat(4)` default, restoring portfolio/insights/etc. | `docs/audit/design/gate1-reservations-after.png` vs `proto-op-reservations.png` |
| **Guests CRM** | ✅ RESTRUCTURED | KIND-1: KPI band was a dark-hero + 2 light at 3/4 width → **3 uniform light tiles full-width** (`.vy-dash-kpis--3`), matching the prototype. The table (Guest+tags · Contact [email/phone — PII allowed on the CRM per #28] · Nationality · Stays · Total billed · Open balance · Status) + filter tabs + honest search-scope note were already at parity (the app's tag chips are richer). | `docs/audit/design/gate1-guests-after.png` vs `proto-op-guests.png` |
| **Reservation Detail** | ✅ RESTRUCTURED | KIND-1: added the prototype's prominent right-rail dark **Balance due** panel (leads the rail; real reconciled folio figures — balance, of-total, paid, Settled/Due), reusing the shared `.vy-tonight` dark stat panel. The folio ledger + summary + status history + lifecycle/record-payment rail were already at parity. **REFUSED KIND-2:** the prototype's guest card shows **email/phone/country** — contact PII forbidden outside the Guests CRM (invariant #28); the app correctly omits it (name-only). **KIND-3 (documented):** a notes/requests card + a rich actor-level audit trail need data the component doesn't fetch — the app's honest "Status history" (real status transitions) is the equivalent. Balance-panel progress bar omitted to stay strictly Blade-only (no numeric ratio derivable in-view). | `docs/audit/design/gate1-resdetail-after.png` vs `proto-op-resdetail.png` |
