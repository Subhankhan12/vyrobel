# Vyrobel — Missing Backend, by page

**Purpose.** This file is the deliverable of the **design-parity pass** (started 2026-08-09) that drives a
**separate, later BACKEND phase**. The design pass walks the standalone prototypes in `docs/design/raw/`
**one page per turn**, makes the live page's design match the prototype, and records here — **without
building it** — every piece of functionality/backend the prototype implies that the live product lacks.

**Design is fixed as we go. Backend is logged, never built in this pass.**

---

## How to read an entry

Each page gets one section with a table of the backend items that page implies but does not have:

| Column | Meaning |
|---|---|
| **What the prototype shows** | The control, figure, panel or state in the decoded prototype |
| **What the live product lacks** | The specific absence, verified against the live code — not assumed |
| **What it would need** | Route / controller / service / model / migration / job, named concretely |
| **Verdict** | `REAL GAP` (build it later) · `RATIFIED REFUSAL` (do NOT build — #39/§5/divergence-register) · `ACCOUNT-GATED` (a founder go-live flip, not missing code) |

⚠️ **A row marked `RATIFIED REFUSAL` is recorded so the backend phase does not "discover" it and build a
fabrication.** The authorities are `docs/design/DIVERGENCE-REGISTER.md` (the 40+ ratified divergences),
`docs/WIREFRAME-PARITY-SWEEP-2026-08-08.md` §5 (refusals to preserve), and `docs/DECISIONS.md`.

⚠️ **`ACCOUNT-GATED` is not missing work either** — Channex, live Stripe, SMS/Twilio, push, websockets,
live LLM, KYC, tax rates and email DNS are honest deferrals awaiting the founder's cutover
(`docs/PROJECT-STATE.md` §H).

---

## Rules this pass follows (recorded so the record is self-explaining)

1. **Design/markup/styling only.** No backend, service, guard, engine or logic was changed to close a
   design gap. Where a design element needs data the product does not have, the honest empty/placeholder
   state was rendered and the backend logged here.
2. **The prototype is the DESIGN source of truth, never the DATA source of truth.** Prototype figures are
   fabricated; the live product ships real data or an honest empty state (#39).
3. **The `--vy-*` tokens are the styling source.** Prototype hexes are matched by *intent* through the
   token system; `--vy-muted` / `--vy-faint` keep their WCAG-AA darkened values.
4. **Invariants hold**: #17 money · #28 privacy perimeter · #38 isolation · #39 no fabrication.
5. Every page browser-verified (Playwright MCP) before/after; suite kept at the documented baseline
   (7 non-green), PHPStan L5 (0), Pint clean.

---

## Page index

*(Populated one page per turn as the pass proceeds.)*

| # | Page | Route | Role | Design status | Backend items logged |
|---|---|---|---|---|---|
| 1 | Operator Dashboard | `/app/dashboard` | Hotel Manager | ✅ matched | **3** (1 real gap · 1 real gap · 1 refusal) |
| 2 | Operator Calendar | `/app/calendar` | Hotel Manager | ✅ matched | **2** (1 real gap · 1 account-gated) |
| 3 | Operator Insights | `/app/insights` | Hotel Manager | ✅ matched | **3** (2 real gaps · 1 refusal) + ⚠️ **1 figure-honesty finding** |
| 4 | Operator Reports | `/app/reports` | Hotel Manager | ✅ matched | **3** (3 real gaps) |
| 5 | Operator Front Desk | `/app/front-desk` | Hotel Manager | ✅ matched | **2** (1 real gap · 1 refusal) |
| 6a | Operator Reservations (list) | `/app/reservations` | Hotel Manager | ✅ matched | **3** (3 real gaps) |
| 6b | Operator Reservation Detail | `/app/reservations/{id}` | Hotel Manager | ✅ matched | **2** (1 real gap · 1 refusal) |
| 6c | ↳ Cancelled + No-show variants | same | Hotel Manager | ✅ matched | **0** (both refusals held) |
| 7a | Operator Guests (CRM list) | `/app/guests` | Hotel Manager | ✅ matched | **2** (1 real gap · 1 refusal) |
| 7b | Operator Guest Profile | `/app/guests/{id}` | Hotel Manager | ✅ matched | **1** (1 refusal) — Guests area COMPLETE |

---

## Per-page entries

*(Appended below, one section per page, in the order the pass works them.)*

---

## 1 · Operator Dashboard — `/app/dashboard` (Hotel Manager)

**Prototype:** `docs/design/raw/Operator Dashboard.html` (decoded 2026-08-09; byte-identical to the
2026-08-07 audit decode). **Design pass:** complete — see `DECISIONS.md` for the commit.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D1-1 | **Per-task completion.** Each "Today's task" row carries a checkbox that toggles done, a progress bar (`taskPct` / "N of M done") above the list, and an "All caught up" state driven by that progress. The prototype persists it in `localStorage`. | No per-user task-completion state of any kind. The live rows are **derived** from real counts (urgent work orders, rooms to clean, arrivals) and auto-clear when their count reaches zero — there is nothing to tick. | A per-user, per-day completion store (`operator_task_states`: user_id, organization_id, property_id, task_key, completed_at) + a gated POST to toggle + reads in `OperatorDashboard::tasks()`. ⚠️ Must not become a *fabricated* done flag: a task whose real count is still >0 cannot be "done". Decide whether ticking is a personal note or a shared team signal — they are different tables. | **REAL GAP** |
| D1-2 | **Priority / Category grouping toggle.** A two-button segmented control switches the task list between grouping by priority and grouping by category, persisted across reloads. | Tasks carry `priority` (urgent/high/normal) — the design's **default** grouping, now rendered. They carry **no category** dimension, and there is no persistence of the chosen mode. | A `category` on each derived task (work-orders / housekeeping / arrivals — the data already exists in `tasks['groups']`, it is just not attached per-row) + a persisted per-user preference. Cheap: the category values are already computed on the same page. | **REAL GAP** |
| D1-3 | **Arrival ETA column.** Each arrival row shows an expected arrival time (`a.eta`) as its own column, formatted as a clock time. | `reservations.expected_arrival` **is a real column** (`string(60)`, migration `2026_07_31_120000`, written at guest online check-in, covered by `Guest::anonymize()`) — but `OperatorDashboard::arrivals()` does not select or expose it, so the dashboard has no ETA to render. ⚠️ It is **free text, not a parsed time** (a guest may have typed "late afternoon"), so it cannot be formatted as a clock value without a real parse/normalise step. | Expose `expected_arrival` in the arrivals row payload and render it. Decide first whether to (a) render the free text as-is, or (b) introduce a normalised arrival-window field. ⚠️ It is genuinely NULL for most reservations — render nothing when unset, never a default hour: WAVE 3 fixed exactly this class of defect on the guest reservation page (invented `From 15:00` where the property set none, #39). | **REAL GAP** (small) |
| D1-4 | **Channels health card** — Booking.com / Airbnb / Expedia each with a "Synced" dot, plus "Last sync 3 min ago". | Nothing missing. The live card renders **real** `channels.status`, says "Not connected" when nothing is genuinely synced, and prints a sync timestamp only when `channels.last_synced_at` is actually set. On the demo org (Essential plan) the card is omitted entirely because Channels is a Pro feature. | — | **RATIFIED REFUSAL** — the prototype's three "Synced" rows and "Last sync 3 min ago" are the fabricated channel-sync explicitly listed in `WIREFRAME-PARITY-SWEEP-2026-08-08.md` §5 and closed as F-OP-2 in the 2026-08-04 QA fix pass. **Do not build.** Real sync arrives with the Channex cutover (`PROJECT-STATE.md` §H). |

### Also noted on this page (not backend gaps)

- **Sidebar group labels failed WCAG AA — ✅ RESOLVED 2026-08-09 (`93247e7`).** `.vy-nav-label` and
  `.vy-nav-item` hardcoded the prototype's greys (`#9C9089`, `#8A8079`) and measured **1.82:1 / 2.23:1**
  against the real sampled gradient. Fixed realm-wide in **both** shells via two new semantic tokens
  (`--vy-on-grad`, `--vy-on-grad-soft`): now **4.74 / 4.93** operator and **4.71 / 4.93** owner, with the
  active (4.62) and hover (6.52) states measured too. See `DECISIONS.md` 2026-08-09 — it also closes the
  2026-08-07 audit's gradient-background exclusion.
- **Topbar subtitle.** The shell renders a subtitle under the H1 ("Demo Seaside Property · Sunday, 9 Aug
  2026") that the prototype does not have. Left in place: it is shell chrome shared by every operator
  page, and other pages use the slot for their own real context.
- **Property switcher clips its label** ("Demo Seaside Propert…") at 190px. Shell-level, pre-existing.
- **Main content width.** Prototype `max-width: 1180px`; the live shell uses `1480px` (content 1150px vs
  ~1124px). Shell-level and realm-wide — left alone, noted.

---

## 2 · Operator Calendar — `/app/calendar` (Hotel Manager)

**Prototype:** `docs/design/raw/Operator Calendar.html` (decoded 2026-08-09; byte-identical to the
2026-08-07 audit decode). **Live component:** `App\Livewire\ReservationRack`. **Design pass:** complete.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D2-1 | **Unit capacity on each rack row.** Under the unit name, a bed glyph and a capacity string (`u.cap`), so an operator can size a booking against the row without leaving the rack. | `units.max_occupancy` **is a real, populated column**, but `ReservationRack`'s `unit_rows` payload exposes only `code`, `name`, `state` and `state_label` — the capacity never reaches the view, so there is nothing to render. | Add `max_occupancy` to the `unit_rows` payload in `ReservationRack` and render it under the unit code. Purely additive; no query shape change (the units are already loaded). ⚠️ Render nothing when the column is null rather than a default — do not invent a "sleeps 2". | **REAL GAP** (small) |
| D2-2 | **OTA brand badge on the bar.** A white circular letter badge (e.g. `B`) inside the booking bar, plus an `OTA` entry in the legend keyed to that badge. | ⚠️ **The earlier "OTA brand is unmodelled" phrasing is imprecise — corrected here.** The brand **is** modelled: `channels.provider` + `channels.name` exist (the demo row is literally `Booking.com (demo)`), and `reservations.channel_id` links a booking to it. What is missing is the **attribution**: **0 of 40 reservations carry a `channel_id`**, because bookings are only channel-attributed by the live OTA import path, which is the account-gated Channex cutover. A badge today would render for nothing, or would have to invent the brand. | Nothing to build now. It becomes a small, genuinely-backed feature once Channex is live and the import writes `channel_id`: expose the channel name on the bar payload and render the badge from its first letter. | **ACCOUNT-GATED** — not a defect, not "unmodelled". See `PROJECT-STATE.md` §H (Channex). **Do not fabricate a badge before the cutover** (sweep §5). |

### Also noted on this page (not backend gaps)

- **Room-type filter stays a native `<select>`, not the prototype's custom popover.** The prototype opens a
  JS popover with checkmarks. The live control is a GET form because `SetCurrentPropertyForWebRequest` does
  not run on `/livewire/update` (**#20**) — a scope-dependent read must ride a GET link. It was **restyled**
  to the prototype's glass pill, so it reads the same; the behaviour is deliberately the safer one.
- **The room-type availability strip is EXTRA and was kept.** The prototype has only a plain text group
  label above each block of unit rows. The live product renders a full header row with the room type, its
  kind, its unit count and **real per-night availability** from `AvailabilityService`. That is real capability
  beyond the wireframe (§6) — not removed.
- **The legend carries 6 states to the prototype's 5** (it adds *Checked out* and *No-show*, and drops the
  OTA badge entry per D2-2). Real `reservations.status` values — kept.
- **Drag-to-reschedule is EXTRA and untouched.** The prototype has no drag affordance at all. The live bar is
  `draggable` and drops POST to the certified `AssignmentService::reschedule` (server-rendered dates, stay
  length preserved, `Unit::isBookable()` OOS guard). Nothing in this pass went near it — verified after the
  change: bars still carry `draggable="true"` and the "drag to reschedule" affordance.
- **Main content width.** This prototype specifies `max-width: 1280px` where the Dashboard prototype says
  `1180px`; the shared shell uses one `1480px` for every page. A per-page content width would be a shell
  change affecting ~43 pages — left alone, noted.

---

## 3 · Operator Insights — `/app/insights` (Hotel Manager)

**Prototype:** `docs/design/raw/Operator Insights.html` (decoded 2026-08-09; byte-identical to the
2026-08-07 audit decode). **Live component:** `App\Livewire\Operator\OperatorInsights`. **Design pass:**
complete.

**Isolation checked first (#38/#28):** every figure resolves through
`PerformanceMetrics::forProperties($this->currentReadIds, …)` / `BookingCadence::*ForProperties($ids, …)`,
and the per-property rows pass a single `[$pid]`. **No aggregate spans properties or orgs**, and no guest
identity appears anywhere on the page. Nothing to escalate.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D3-1 | **Custom date range.** The range popover offers presets *and* a From/To month-chip grid (12 + 12 chips), so an operator can ask for any arbitrary window. | `resolveRange()` accepts exactly **5 preset keys** (`ytd`, `6m`, `summer`, `q4`, default this-year) via `?range=`. There is no way to express an arbitrary start/end. | Accept `?from=`/`?to=` (validated, clamped, tenant-safe) alongside `range`, and thread them through the existing `PerformanceMetrics`/`BookingCadence` calls — those already take explicit start/end, so the metric layer needs no change. ⚠️ Whatever is chosen must also drive the "vs last year" window, or the delta silently compares mismatched spans. | **REAL GAP** |
| D3-2 | **Per-channel delta + warning dot.** Each channel-mix row carries a change figure beside its share, and a red dot flags a channel needing attention. | `channelMix` returns `label` + `pct` only. There is no prior-period share computed, and no channel-health signal on this page. | A prior-period channel mix over the same-length preceding window, diffed per source; the "needs attention" dot would key off real `channels.status` / `last_synced_at` rather than a threshold on the share. ⚠️ Only render a delta where a prior period genuinely has bookings — an empty prior window must omit it, not print `+100%`. | **REAL GAP** |
| D3-3 | **"Suggested next step" dark card + three narrative observations** ("The Expedia disconnect is costing Marina Suites", "Saturdays sell out group-wide", "Turnover load piles up on Fridays"), each with a euro/percentage figure and a prescriptive action. | Nothing missing. The live "Worth a look" renders only **real derived** observations, and the channel-level one states plainly that per-channel gap analysis needs a connected channel manager rather than estimating a figure. | — | **RATIFIED REFUSAL** — `WIREFRAME-PARITY-SWEEP-2026-08-08.md` §5: *"No generated narrative / 'suggested next step' / smart-pricing suggestions / comp-set benchmark."* **Do not build.** |

### ⚠️ D3-4 · A FIGURE-HONESTY FINDING (not a design gap — surfaced, not fixed)

**The "Last year" series is divided by the availability spine's coverage, not by the month.**

Measured on the live demo data: the `availability` table holds **9 rows in the whole of 2025**, and the
earliest is `2025-08-21`. September 2025 has **3 rows for a 30-day month**, each `total=2, sold=1`. The
chart therefore renders **"Last year 50%"** for September — which is 50% *of the three nights that have
any data at all*, presented under a label that reads as the month's occupancy. Four LY months
(Aug/Sep/Oct/Nov) all read exactly 50% for this reason.

- **Why it is not a design fix:** the number is produced by `PerformanceMetrics::monthlyTrendForProperties`.
  This pass is design-only and explicitly must not change a computation, so it is recorded, not touched.
- **Why it matters:** it is the WAVE-2 class exactly — *a figure under a label that means something else* —
  and it feeds the KPI deltas too (`−40 pts` occupancy is measured against that same thin LY window).
- **What it would need:** a **coverage guard** on the LY series — compute the spine's day-coverage for each
  comparison window and omit the LY bar (and the delta) when coverage is below a threshold, exactly as the
  product already omits vs-LY where no prior-year data exists at all. The honest states already exist in the
  codebase (`has_ly`, `enough`); this is the same idea applied to *partial* coverage rather than *absent*
  coverage.
- **Demo-vs-production note:** the thin 2025 spine is an artifact of `DemoSeeder::seedAnalyticsHistory`
  backdating a handful of bookings. In production the spine is dense going forward — but the same hole opens
  for **any** tenant in its first year, which is every new tenant.

### Also noted on this page (not backend gaps)

- **Property filter chips.** The prototype puts per-property chips on the page. The live product routes
  property selection through the topbar `PropertyContext` switcher (the certified #38 mechanism) and links
  to it — a ratified divergence, kept.
- **REALM-WIDE: accent links on glass were under AA — ✅ RESOLVED 2026-08-09 (`81c5d18`).** `var(--vy-accent)`
  as TEXT measured **4.09–4.46:1** across real sampled card backgrounds (the prototype's own `#B4400F` was
  4.48 — also under). Fixed with a new **`--vy-accent-text: rgb(176,56,8)`** token pointed at the 41 rules
  where accent is text **without its own background**; the 31 chip/tag rules that carry their own tint were
  left alone (measured 4.61, passing), and **`--vy-accent` itself is untouched** so every gradient, button,
  border and icon fill is byte-identical. After: **4.92–5.47** operator, **5.45** owner.
- **KPI label size.** The Insights prototype specifies 10.5px where the Dashboard prototype specifies 11px
  for the same shared `.vy-dk-label`. Left at 11px — a 0.5px scoped override is not worth the drift.

---

## 4 · Operator Reports — `/app/reports` (Hotel Manager)

**Prototype:** `docs/design/raw/Operator Reports.html` (decoded 2026-08-09; byte-identical to the
2026-08-07 audit decode). **Live component:** `App\Livewire\Operator\OperatorReports`. **Design pass:**
complete.

**Checked first:** figures reconcile (#17) — the channel Total renders **€7,080.00**, identical to the
Gross-revenue KPI, because both read the one folio source (`OperatorReportData`, the WAVE-1 HIGH-4 fix).
Isolation (#38) holds — the report resolves through the property context, and no guest identity appears in
any row. **The export generation was not touched**: `OperatorReportsExportController` (CSV via `CsvSafe`,
PDF via `PdfService`) renders from the same `OperatorReportData` the screen builds, so screen == export.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D4-1 | **Excel workbook export** in the export menu ("Trend + channels tabs"). | Genuinely absent, and **honestly labelled "Soon"** in the live menu with "Use the CSV meanwhile" — not a fake download. | A spreadsheet writer (e.g. `phpoffice/phpspreadsheet` — a new dependency, so a founder call) emitting the same `OperatorReportData` rows the CSV already produces, two sheets. ⚠️ Must route through the same assembler so screen == export == workbook. | **REAL GAP** (honestly deferred today) |
| D4-2 | **"Email to owners"** — send the report as a PDF attachment. | Present in the live menu as an honest "Soon". No mail job exists for it. | A queued Mailable attaching the existing `reports.export.pdf` output, plus a recipient rule. ⚠️ Must be tenant-restoring (the #83 pattern — a queued mailable that renders outside tenant context is exactly the 2026-08-07 H2 defect), and owner-scoped so an operator cannot mail one owner another's figures (#28). | **REAL GAP** (honestly deferred today) |
| D4-3 | **Owner statements list in the right rail** — per-owner rows (avatar, name, unit, net) linking to a statement, with a **"Download all 12 (ZIP)"** bundle, plus a net/fee summary line. | The per-owner statement data is **real and already built** — it simply lives on `/app/owners` + Payout run + the owner realm's own statement PDFs, not on Reports. What genuinely does not exist anywhere is the **ZIP bundle** of a period's statements. | To surface the list here: read the existing owner-statement source (no new computation). For the ZIP: a streamed archive of the already-generated per-statement PDFs, `reports.view`-gated and property/tenant-scoped, with `ExportAudit::stamp` like the other bundled exports. ⚠️ Owner figures on an operator surface stay name + money only (#28). | **REAL GAP** (the list is a location divergence; the ZIP is genuinely missing) |

### Also noted on this page (not backend gaps)

- **Kept, not removed (§6 extra work):** the **Revenue breakdown** card (room / ancillary / gross / tax
  pass-through), the **Costs** card (real maintenance cost, with the untracked lines honestly marked
  "NOT TRACKED YET" rather than fabricated), the **Top units** table, and the basis footnote. The wireframe
  has none of these.
- **The wireframe's dark "month so far" hero** is not reproduced: the live KPI band already carries the same
  real figures, and the hero's second line is a narrative sentence. A layout divergence, not a data gap.
- **Shared chart component changed (both consumers verified):** `<x-charts.bars>` had `max(4, pct)%`, so a
  genuinely **€0.00** month drew a visible stub under a "€0.00" label. It now draws nothing (non-zero keeps
  a 2px floor so a small real value stays visible). A `valueTop` opt-in prop was added for this wireframe's
  value-above-the-bar order; the other consumer (owner Insights) does not pass it and was confirmed
  unchanged in-browser — same column order, chart intact.

---

## 5 · Operator Front Desk — `/app/front-desk` (Hotel Manager)

**Prototype:** `docs/design/raw/Operator Front Desk.html` (decoded 2026-08-09; byte-identical to the
2026-08-07 audit decode; 7 declared states incl. Check-in / Check-out / Assign-unit / Walk-in modals).
**Live component:** `App\Livewire\FrontDesk` + `FrontDeskActionController`. **Design pass:** complete.

**Checked first — nothing was rewired.** The check-in / check-out / collect / walk-in forms still carry
their real bound actions (`x-bind:action="'/app/front-desk/' + row.id + '/check-in'"`, POST + CSRF, gated
`reservations.checkin` / `reservations.checkout` / `payments.record`). ⚠️ The literal `action` attribute
reads `.../undefined/...` before a modal opens — that is the **documented Alpine bind-on-open pattern**
which QA Pass 2 investigated and disproved as a defect, not a bug. **#28 verified by probe:** no email or
phone appears anywhere on the rendered page. **#33** untouched — this surface shows no door code.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D5-1 | **Housekeeping readiness on the arrival row.** Each arriving guest can carry a room-status chip (`hkShow` / `hkLabel` / dot), so the desk can see whether the room is clean before handing keys. | `HousekeepingTask` status **is real and already rendered on the Housekeeping board**, but `FrontDeskService`'s arrival rows do not join it, so the front desk cannot show whether the arriving guest's unit is ready. | Join the unit's current housekeeping status into the arrival row payload and render it as a chip. ⚠️ Status must be **text as well as colour** (WCAG 1.4.1), and must render nothing when a unit is unassigned rather than guessing. No new table — the data exists. | **REAL GAP** |
| D5-2 | **Empty-state specifics** — "the last guest was welcomed at 15:00. Tomorrow brings 9 arrivals." | Nothing missing. The empty state was rebuilt to the wireframe's **structure** (icon tile, headline, supporting line, CTA) with copy that states only what is true: "No arrivals are scheduled for {the real date}", and a real next-day link. | — | **RATIFIED REFUSAL** — those two figures are invented; the page computes neither a "last welcomed at" time nor a next-day arrival count. **Do not build the sentence; build the count first if it is ever wanted.** |

### Also noted on this page (not backend gaps)

- **Kept, not removed (§6 extra work):** the guest **preference chips** (Sea view / High floor / Late
  checkout), the in-place **Move / Assign** actions through `AssignmentService`, the **Collect** on-desk
  payment (cash / bank transfer, `payments.record`-gated, with the card terminal honestly named as the
  deferred Stripe seam), the per-tab counts on the segmented control, and the "LIVE INTEGRATIONS" footnote.
  The wireframe has none of these.
- **The timeline rail was already at parity** — the live card already implements the wireframe's axis
  baseline, hour gridlines, arrivals-above / departures-below split, and a pulsing now-line with a "Now
  HH:MM" flag (today only). It renders only when the day genuinely has points, which is why it is absent on
  an empty day.
- **The Unassigned tile is tinted CONDITIONALLY.** The wireframe tints it always; the live product tints it
  only when the count is genuinely non-zero — tinting a "0 unassigned" tile would draw attention to nothing.
  Deliberate divergence.
- **The wireframe's Walk-in CTA inside the empty state** was not duplicated: the walk-in trigger is
  permission-gated and already sits in the toolbar; repeating it would mean duplicating that gate.

---

## 6a · Operator Reservations — LIST — `/app/reservations` (Hotel Manager)

**Prototype:** `docs/design/raw/Operator Reservations.html` (decoded 2026-08-09; byte-identical to the
2026-08-07 audit decode). **Live component:** `App\Livewire\Operator\ReservationsList`.
**Design pass:** complete. ⚠️ **The reservation DETAIL is a separate, larger surface (1,310-line prototype
plus Cancelled and No-show variants) and is the NEXT turn — it is not covered here.**

**Checked first — the #20 contract is intact.** The pager is still plain GET links (`?page=2`), **zero
wire-actions**, and page 2 was driven in the browser: it returned 15 rows (25 + 15 = 40) whose units were
**102 / 103 / A1 / A2 — all belonging to the selected property**, so the property scope genuinely survives a
page change. The search is still a GET form that carries the active stage, and the stage chips are still
plain `<a href>` links. Nothing was converted to a wire-action.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D6-1 | **Select mode + bulk actions.** A "Select" toggle turns the rows into checkboxes, with a floating bulk-action bar and a **bulk message composer** for the selected reservations. | No selection mode, no bulk bar, no bulk composer on this list. (Bulk *guest* actions do exist on the Guests CRM — this is the reservations equivalent, which does not.) | A selection state + a gated bulk endpoint per action. ⚠️ Each bulk action must run through the SAME certified service as its single-row equivalent (no bulk path that bypasses `AssignmentService` / the cancellation policy), and a bulk message must respect the marketing-consent gate and `MarketingUnsubscribe` the same way single sends do. Bulk cancel in particular must stay inside the #29 terminal-status bound per row. | **REAL GAP** |
| D6-2 | **Row hover quick-actions** — a floating pill over the hovered row offering check-in, check-out, message guest and open-full-reservation. | The row links to the detail page; there are no per-row shortcut actions. The underlying actions all exist (Front Desk check-in/out, the inbox, the detail page) — they are simply not surfaced here. | Surface the existing gated actions as row shortcuts. ⚠️ Each must POST to its real route with the real permission gate (`reservations.checkin` / `reservations.checkout`) — a shortcut that skips a guard is worse than no shortcut, and a control shown to a role that cannot use it invites a 403 (the "gate the control too" rule, #30). | **REAL GAP** |
| D6-3 | **Month scope button** ("Sep 2025") beside the filters, scoping the list to a chosen month. | The list has stage chips + search, but no month/date-range narrowing. | A `?month=` (or from/to) GET param threaded into the existing query. ⚠️ It must ride a GET link like every other scope-dependent read here (#20), and the three stat tiles state their own scope ("arrivals this month") — either the tiles follow the chosen month or they keep saying exactly what they measure. Today they are deliberately NOT re-scoped by the chips, and that decision is documented in the view. | **REAL GAP** |

### Also noted on this page (not backend gaps)

- **The "Refunded" chip already exists** (`#104 GATE 3c`): the row payload carries a real
  `payment_status === 'refunded'` flag and renders the wireframe's pill — it simply never appears on the
  current seed because no demo reservation is fully refunded. **Not a gap.** It deliberately shows no figure
  (the amount lives on the folio, #39).
- **Kept, not removed (§6 extra work):** a **BALANCE DUE** column the wireframe does not have, **seven**
  stage chips against the wireframe's five (each labelled from the shared `ReservationStage::meta()` with a
  count that matches what the click actually returns), the "Clear search and filters" empty state, and the
  two honest empty states (fresh tenant vs no-match).
- **Row click navigates to the full detail page**, where the wireframe opens a slide-over drawer. The
  wireframe *also* has a full detail page, so this is a navigation divergence, not a missing surface.
- **Shared `<x-pager>` restyled (not rewired).** Prev/next became the wireframe's round icon buttons across
  all four consumers. ⚠️ The visible **"Page N of M"** was KEPT even though the wireframe omits it — a bare
  chevron pair tells you neither where you are nor whether another page exists, and assistive-tech users
  lose position entirely. GET links, `withQueryString()` and the self-contained inline styles are unchanged.

---

## 6b · Operator Reservation DETAIL — `/app/reservations/{id}` (Hotel Manager)

**Prototype:** `docs/design/raw/Operator Reservation Detail.html` (1,310 lines) + the `Cancelled` and
`No-show` variants (all decoded 2026-08-09, byte-identical to the 2026-08-07 audit decodes).
**Live component:** `App\Livewire\ReservationDetail`. **Design pass:** complete for the main detail.

### ✅ The Cancelled and No-show variants are CLOSED (2026-08-09, `4490b42` + the design gate)

They were unobservable at 6b — the seed held no `cancelled` and no `no_show` row. GATE A added one of each
**through the certified services the UI itself posts to** (`ReservationCancellationService::cancelWithPolicyRefund`
and `AssignmentService::markNoShow`), never a raw status write, so the folio, the availability spine and the
audit trail are genuinely consistent. Both variants were then diffed against their prototypes — see §6c.

### Verified before touching anything

- **#17 reconciles**, re-read live on two reservations: settled — Charges €369.00 − Paid €369.00 = Balance
  €0.00; outstanding — Charges €664.20 − Paid €332.10 = Balance **€332.10**. Both carry the component's own
  "Reconciled to the folio ledger" check.
- **Refunds stay visible.** `FolioTotals` defines `paid_minor === received_minor − refunded_minor`, so
  "Paid" is NET — but a refund posts **its own offsetting ledger line** ("Refund · Reverses a payment",
  correct-by-reversal, the original payment row untouched), so the net figure is always explained by a
  visible row. Confirmed by reading the ledger builder; it could not be observed live because the seed
  contains **zero refunds**.
- **#28 / #33 probed on the rendered page:** no email, no phone, **no door code**.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D6b-1 | **Modify** (dates / guests) and **Void** as first-class actions in the title row. | Both are present in the live rail but honestly marked **"SOON"** — there is no modify-stay or void-line action behind them. | Modify = a date/occupancy change that must go through `AssignmentService` (re-checking availability and the no-overlap lock) and re-price the folio through `FolioService`; Void = an append-only reversing entry, never a delete (the ledger is append-only). ⚠️ Neither may bypass the certified path, and a modify that moves dates must respect the same OOS/paused `isBookable()` guard a move does. | **REAL GAP** (honestly deferred today) |
| D6b-2 | **A cancellation / no-show fee** on the cancelled and no-show variants. | Nothing missing. There is **no fee engine**, and the live cancellation card states the real policy on file plus "Nothing has been calculated against this booking." | — | **RATIFIED REFUSAL** — sweep §5: *"No cancellation / no-show fee figure — no fee engine; a label marked 'Soon' plus an explicit 'no fee was calculated or charged'."* **Do not invent one.** The cancellation outcome must stay the real policy + the #27-bounded refund. |

### Also noted on this page (not backend gaps)

- **Kept, not removed (§6 extra work):** the **Cancellation policy** card (the real policy JSON, stating
  plainly that nothing has been calculated), the **Lifecycle** card (mark-no-show + cancel-with-reason, each
  explaining its own consequence), the per-payment inline **Refund** gated on `payments.refund` *and* real
  remaining headroom (#27), the dedicated refund screen, **Export folio (PDF)**, and the "Reconciled to the
  folio ledger" badge. The wireframe has none of these.
- **Action placement diverges deliberately.** The wireframe puts Modify / Move room / Take payment / ⋯ in
  the title row; the live product keeps them as real, gated forms in the right rail. They were **not**
  moved: relocating them means converting working forms into modal triggers, which is a behaviour change,
  not styling. All five (`no-show`, `cancel`, `payments`, `move`, `charge`) were re-verified POST + CSRF
  after the change.
- **The guest check-in note (WAVE-2) is intact** — `ReservationDetail` still builds a "From the guest" note
  row. It does not render on the two seeded reservations because neither has one, not because it regressed.

---

## 6c · Reservation Detail — CANCELLED + NO-SHOW variants

**Prototypes:** `Operator Reservation Cancelled.html` + `Operator Reservation No-show.html` (decoded
2026-08-09, byte-identical to the audit decodes). **Live:** the two reservations GATE A seeded through the
certified path. **Design pass:** complete. **No backend gaps — both prototype asks are refusals.**

### Verified live on both states

| | Cancelled `R-NFUVRUON` | No-show `R-YINKVAQR` |
|---|---|---|
| **#17 reconciles** | €246.00 − €0.00 = **€246.00** ✓ | €246.00 − €123.00 = **€123.00** ✓ |
| **Refund visible** | ledger shows `Payment −€246.00` **and** `Refund +€246.00` — correct-by-reversal, both lines present | n/a (no refund; the policy produced none) |
| **#29 re-terminate bound** | **no cancel/no-show form rendered** — only `/payments` and `/charge` remain | same |
| **Invented fee** | none — probed | none — probed |
| **#33 door code / #28 contact** | none / none | none / none |

| # | What the prototype shows | Live | Verdict |
|---|---|---|---|
| — | **Cancelled banner:** "Flexible policy — **1-night fee applied** · … · confirmation emailed to the guest". | The banner states the real `cancelled_at`, the real stored reason, and the real inventory release. The fee line reads **"Cancellation fee · Soon — No fee was calculated or charged"**. | **RATIFIED REFUSAL** — §5 *"no cancellation / no-show fee figure — no fee engine"*. |
| — | **No-show banner:** "**2 calls and a message went unanswered** · **1-night fee charged to the Expedia virtual card** · auto-marked at end of arrival day". | Real `marked no-show at` + the real inventory release. No call log exists, no OTA virtual card is modelled, and nothing auto-marks a no-show — a human does. | **RATIFIED REFUSAL** — every clause is fabricated. |
| — | **Waive fee modal** on the no-show. | Absent; the live card reads "No-show fee · waiver — Soon". | **RATIFIED REFUSAL** — §5 *"No waive-fee modal on no-show — waiving a fee that was never posted would create a phantom credit."* |
| ✅ | Terminal state announced as a **tinted banner** with a round icon — rose for cancelled, amber for no-show. | Was a plain glass card with a small eyebrow. | **FIXED** — the tint + icon now key off the real terminal state, and the state is **also named in words**, so colour is never the only carrier (WCAG 1.4.1). |

### Also noted

- **A no-show's stage chip reads "Cancelled" — deliberate, not a defect.** `ReservationStage` buckets
  `no_show` into the Cancelled *stage*, and the detail takes it from that shared resolver **so this screen
  can never disagree with the list** (the list's chip counts them together too). The precise status
  ("No-show") is shown separately in the header card and the sub-title. Changing it here would reintroduce
  exactly the two-readers drift the shared resolver exists to prevent.
- **A cancelled reservation carries a real outstanding balance** (charges stand, the payment was reversed).
  That is the append-only, correct-by-reversal ledger being honest — not a display bug.

---

## 7a · Operator Guests — CRM LIST — `/app/guests` (Hotel Manager)

**Prototype:** `docs/design/raw/Operator Guests.html` (decoded 2026-08-09; byte-identical to the
2026-08-07 audit decode). **Live component:** `App\Livewire\Operator\GuestsList` (`can:guests.view`).
**Design pass:** complete. ⚠️ **The Guest PROFILE is the NEXT turn — not covered here.**

### ⚠️ #28 PII perimeter — measured before and after, unchanged

This is the primary operator PII surface, so the perimeter was **recorded as a baseline before touching
anything** and re-probed after:

| Probe | Before | After |
|---|---|---|
| distinct emails rendered | 4 | **4** |
| distinct phones rendered | 4 | **4** |
| passport / ID / document number | none | **none** |
| card / masked PAN | none | **none** |
| loyalty tier / "member since" | none | **none** |
| "lifetime value" / LTV label | none | **none** |

**No field was added, nothing is decrypted that was not already, and the honest note survives:** *"Search
matches name, city and an exact email address — phone and partial email are encrypted at rest, so they
cannot be searched."* Search remains a **GET** form (#20); the list renders **no pager at 10 rows**, which
is the shared component's documented contract, not a defect.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D7-1 | **"Repeat guests" delta** — the stat carries a `+4%` change chip against a prior period. | The live tile shows the real ratio (**33%**) with its real basis spelled out ("3 of 9 guests who have stayed booked again"), but no prior-period comparison is computed. | A prior-window repeat-rate over the same span, diffed. ⚠️ Only render where a prior window genuinely has guests — a first-year tenant has no comparison, and the WAVE-3 Insights finding is the warning: a delta computed against a thin or empty prior window states something it cannot support. | **REAL GAP** (small) |
| D7-2 | **A "Country" column populated for every row**, and a fuller identity block per guest. | Nothing missing structurally — the live column is `Nationality` and is genuinely **empty ("—") for guests who have no nationality on file**. The prototype shows every row populated because its data is invented. | — | **RATIFIED REFUSAL** (#39) — the honest "—" stays. **Do not backfill a nationality to make the column look full.** |

### Also noted on this page (not backend gaps)

- **The live list shows MORE contact than the wireframe, deliberately kept.** The wireframe's row sub-line
  is the email alone; the live row shows `email · phone`. That is real capability on a permitted surface
  (#28 allows contact on the Guests CRM). It was **not** removed — narrowing an operator's contact reach is
  a product decision, not a styling fix.
- **Kept, not removed (§6 extra work):** the segment chips with real counts (All / In-house / Upcoming /
  VIP), the per-guest tag chips (Corporate / VIP / Repeat / At risk), the **balance-due** line under the
  lifetime figure, the "A–Z by surname" ordering note, and the encryption disclosure above.
- **Fixed:** the stat tiles took this wireframe's tighter 15px/18px padding and 26px figure (scoped to
  `.vy-gst`), and the search became the wireframe's single integrated pill with the magnifier inside the
  field — still a GET form.

---

## 7b · Operator Guest PROFILE — `/app/guests/{id}` (Hotel Manager)

**Prototype:** `docs/design/raw/Operator Guest Profile.html` (decoded 2026-08-09; byte-identical to the
2026-08-07 audit decode). **Live component:** `App\Livewire\Operator\GuestProfile` (`can:guests.view`).
**Design pass:** complete. **With 7a, the Guests area is COMPLETE.**

### ⚠️ #28 PII perimeter — measured before and after on the deepest single-guest view

| Probe | Before | After |
|---|---|---|
| distinct emails | 1 | **1** |
| distinct phones | 1 | **1** |
| passport / document / national-ID number | none | **none** |
| card / masked PAN | none | **none** |
| home address | none | **none** |
| date of birth | none | **none** |
| ID-document images rendered | **0** | **0** |
| fabricated "ID verified" / KYC badge | none | **none** |

**No field added, nothing decrypted that was not already.** The page shows email · phone · city — the
contact set it already showed. Owner isolation is structural: this is an operator-only route
(`can:guests.view`), and the owner realm has no path to it.

### The ID-document and erasure seams

⚠️ **The wireframe has neither.** Grepped for `passport` / `identity` / `verified` / `delete` / `erase` /
`forget` / `GDPR` across the decoded prototype: **zero matches**. So there was no ID-document treatment to
match and no erase control to restyle — and, importantly, **nothing tempting the pass to invent a
"verified" badge**. The real seams remain exactly where they were: `GuestIdDocumentService` (encrypted,
30-day purge) surfaces no raw image here, and `GuestDeletionService`'s 14-day scheduled anonymise is
reached from its own gated control elsewhere, untouched by this pass.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D7b-1 | **Stat-card sub-lines that assert facts the page cannot support** — "3rd underway now", "across 2 units", "2 direct · 1 Booking.com". | The live sub-lines state only what is computed: "cancellations excluded", "across every stay", "3 Direct". The per-brand split ("1 Booking.com") is the same unmodelled OTA-brand attribution logged at D2-2. | — | **RATIFIED REFUSAL** (#39 / §5) — an OTA **brand** split needs channel attribution that no reservation carries until the Channex cutover. **Do not invent the brand breakdown to fill the sub-line.** |

### Also noted on this page (not backend gaps)

- **"Lifetime value" is a real aggregate, not an invented LTV.** Verified in the browser: the three stay
  rows sum to **€1,107.00**, exactly the figure the dark card shows, and its own basis line ("3 stays ·
  9 nights · avg €123.00 a night") divides out to €123.00 at 9 nights. It reconciles (#17) and is a *sum of
  real stays*, not a predictive lifetime-value model.
- **Marketing consent is real and unchanged** — "Prefers email · marketing consent on file" reflects the
  real consent field; nothing on this page implies a send that bypasses `GuestMarketing::canReceive`.
- **All five write forms still POST to their real gated routes** (`/message`, `/notes`, `/tags` ×2, and the
  profile update) — restyled, not rewired; none was submitted.
- **Kept, not removed (§6 extra work):** the **Latest review** card with the operator's own response, the
  **Tags** catalogue control with its "shared across this workspace" disclosure, the internal-notes composer
  marked **INTERNAL ONLY**, the open-folio line on the lifetime card, and the segment/status chips. The
  wireframe has the review and preferences but none of the rest.
- **Fixed:** the stat tiles took this wireframe's tighter 15px/18px padding and 26px figure (scoped to the
  same `.vy-gst` hook 7a introduced).

---

## 8. Operator Housekeeping (`/app/housekeeping`)

**Prototype:** `docs/design/raw/Operator Housekeeping.html` — 1,596 lines, decoded byte-identical to the
audit copy. Four declared states: `Operator Housekeeping` · `Assign housekeeper modal` · `Assign all modal` ·
`Room detail modal`.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D8-1 | A **Filter dropdown** (popover listing each status as a dot / label / count row, click to filter the board). | The live page has the five status tallies as **display-only** pills — they are not links, so a status cannot be filtered on. The crew filter beside them *is* real (GET links, `crew=`). Verified: `tallyAreLinks: 0`, `crewAreLinks: 2`. | A `status=` GET parameter on the board query, mirroring the existing `crew=` filter — the same shape, no new backend concept. Must ride a **GET link**, not a Livewire action: the property middleware does not run on `/livewire/update` (#20). | **REAL GAP** — small, and the pattern to copy already exists on this page. |
| D8-2 | A **"Ready by" time** on each room row (the design shows a clock target per room). | The live `READY` column renders **"—"** on every row. The column exists and is honest; there is no ready-by value to put in it. | A target-ready timestamp on `housekeeping_tasks` (or derived from the arriving reservation's check-in time + the property timezone). Nothing today computes or stores it. | **REAL GAP** — the column is already there and honestly empty, which is the right holding state (#39). Do not fill it with a guess. |

### Also noted on this page (not backend gaps)

- **"Suggested" is genuinely computed, not fabricated.** `HousekeepingBoard::suggestedHousekeeperId()`
  picks the **least-loaded** crew member by real open-task load, with a stable tie-break. Its docblock
  records that this was a WAVE-3 fix: the picker used to badge whoever came back first from
  `StaffRoster::housekeepers()` *while displaying that person's real load beside it* — a suggestion that
  contradicted the number printed next to it (#39). Confirmed by reading, not assumed.
- **Every mutation still POSTs a real gated route** — 7 live `…/status` forms on the page, and the assign
  forms build their action from the real `app.housekeeping.assign` route when the modal opens. The status
  route is gated `can:housekeeping.manage`. **Restyled, not rewired**; nothing was submitted.
- **Status is never carried by colour alone.** Each tally dot is paired with its status **word**
  (verified `everyDotHasLabel: true`), so the strip satisfies WCAG 1.4.1.
- **No guest contact on this surface** (verified: no email/phone pattern anywhere in the rendered text) —
  the #28 perimeter holds; housekeeping sees rooms and crew, not guests.
- **Vocabulary divergence, deliberate:** the app's `pending` is the wireframe's "Dirty". The app's own
  status enum wins — the lanes, the tallies and the existing view test all speak it consistently.
- **Kept, not removed (§6 extra work):** the **Cozy/Compact density toggle** (board view only — my first
  probe read it as absent because it is `x-show="view === 'board'"` and the page loads in List), the
  **drag-to-advance** lane move (forward-only, one lane at a time, POSTing the same certified status route
  as the buttons), the **crew load panel**, and the room-detail modal's **proof photos + elapsed**.
- **Fixed:** the search became the design's integrated pill (icon inside, inline Clear, pill radius) and the
  status tallies became the design's count pills (9px dot, `9px 16px`, 15px/600 figure) with the five status
  colours promoted from **raw hexes in the view** to shared `--vy-hk-*` tokens; the crew filter row stopped
  stretching the full page width. The search's Clear link carries the active `crew=` filter with it (#20).
- **Anti-drift note:** this was the **third** page to need the "integrated search pill". Rather than write
  the rule a third time it was promoted to ONE opt-in `.vy-input-group--pill`, and the `.vy-res` / `.vy-gst`
  copies were deleted and repointed at it — three copies of one rule is exactly how the drift this project
  keeps consolidating starts.

---

## 9. Operator Team Schedule (`/app/schedule`)

**Prototype:** `docs/design/raw/Operator Team Schedule.html` — 1,111 lines, decoded byte-identical to the
audit copy. **Role:** Hotel Manager (`schedule.view` to see, `schedule.manage` to change).

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D9-1 | **Per-person hours** on each staff row — the design's row sub-line is `{{ r.hours }}` beside the department dot. | The live sub-line carries the department **name** instead. `RosterWeek::hours()` computes only a WEEK-WIDE total (`shiftCount() × HOURS_PER_SHIFT`); there is no per-person accessor. | A per-staff hours accessor on `RosterWeek`, derived from the same worked cells the grid already renders (that person's shift count × the shared `HOURS_PER_SHIFT`). It must live in `RosterWeek` — the grid and the schedule PDF both read that builder, and a second definition of "hours per shift" in a Blade file is exactly the drift this project keeps consolidating. | **REAL GAP** — small and purely derived; deliberately **not** built in this presentation pass. |
| D9-2 | An **"Open shifts" row inside the grid**, aligned to the seven day columns, with a per-day **Assign** button. | The live open shifts are a **separate card** (now in the rail) listing slots as rows, not aligned to the day columns. The data is real either way, and the honest empty state is correct. | No new backend — `$openShifts` already carries a date per slot. This is a grid-composition change: an eighth row whose cells map open slots onto the day columns. | **REAL GAP (presentation-shaped)** — logged rather than built because it restructures the grid body, which is beyond a styling pass and deserves its own gate. |

### Also noted on this page (not backend gaps)

- **REAL staff, REAL shifts.** The four rostered people are the real team from `StaffRoster` (an **active
  `Membership`** ∩ a **back-office role**, team-scoped), and the shifts are real `Shift` rows through
  `RosterWeek`. Nothing on the page is a fabricated person, role or shift.
- **No owner and no guest leaks into the roster (#28).** Verified two ways: no email/phone pattern appears
  anywhere in the rendered text, and `StaffRoster`'s own docblock states that portal-only users
  (**`External Owner` / `Guest`**) hold none of the back-office roles and therefore never appear.
  ⚠️ **Naming trap, checked rather than assumed:** the roster row reading **"Demo Admin · Property Owner"**
  is *not* an external owner — `Property Owner` is the **org-admin operator role**
  (`config/authorization.php` flags this exact trap in a comment), and it is a legitimate back-office role.
- **All six schedule actions still POST their real gated routes** with CSRF — `publish`, `shift`,
  `shift/move`, `open-shifts`, `time-off`, `leave`. **Restyled, not rewired**; nothing was submitted.
- **Role is never carried by colour alone** — each department dot sits beside its role word
  (Accountant / Property Owner / Hotel Manager / Housekeeping), so the grid satisfies WCAG 1.4.1.
  ⚠️ This is a place where the **live page is better than the prototype**: the design puts *hours* in that
  slot, which would leave the department encoded in the dot colour alone. Kept the label (see D9-1).
- **Honest empty states throughout** — "No open shifts — every slot is covered.", "No one scheduled",
  "0 people", "Everyone is working.", and a real "No staff on your team yet" that links to Roles & access.
- **Kept, not removed (§6 extra work):** drag-to-move/swap, the leave **conflict flag** (a shift that later
  clashes with approved leave is marked, never silently dropped), Publish week, the Day view, the PDF, the
  read-only mode, and the department filter.
- **Fixed:** the page took the design's **two-column shape** — the roster grid beside a **224px rail**
  carrying coverage + open shifts, instead of three stacked full-width cards; the rail's Open-shifts heading
  took the design's dot + uppercase-label form (with the **real** count); and the grid's column floor was
  relaxed 92px→88px so the seven days fit beside the rail instead of forcing a permanent 10px scroll that
  clipped Sunday.
- **Documented adaptation:** the design's rail also holds "Time off". The live **time-off forms stay
  full-width below** — they carry two date pickers and a select, which a 224px column cannot hold usably.
- **Checked and NOT a defect:** a full-page screenshot shows the warm gradient stopping partway down. The
  gradient is `background-attachment: fixed`, so that is a screenshot-stitching artefact — scrolled in a
  real viewport the gradient covers the full page. Verified before reporting.

---

## 10. Operator Messages / Inbox (`/app/inbox`)

**Prototypes:** three decode to this one page — `Operator Messages.html` (thread list + the assign / new-message
modals, 856 lines), `Operator Conversation.html` (the conversation pane, 646 lines) and `Operator Inbox.html`
(a filters variant, 586 lines). **Role:** Hotel Manager (`guests.view` gates every inbox route).

⚠️ **Correction worth recording for later pages:** a first pass probing rendered TEXT reported internal notes,
templates and Retry as "missing". They are all **built** — they simply were not being exercised by the thread
on screen (no failed message, no note on that thread, templates live inside the New-message modal). Probing
the DOM measures the current *state*, not the page's *capability*; the source is the authority.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D10-1 | **Party** and **Balance** in the conversation's reservation panel. | `reservationCard()` returns id / reference / unit / dates / status only. Party size and folio balance are genuinely absent. | Adding occupancy (adults+children) and a balance to `reservationCard()`, the balance read through the shared `FolioTotals` so it can never disagree with the folio screen (#17). | **REAL GAP** — the strip built this pass renders dates + unit, and stops honestly where the payload stops. |
| D10-2 | A reservation **stage** on the conversation (the design badges the stay). | `reservationCard()` carries the RAW `status`; the shared `ReservationStage::meta()` takes a computed STAGE, so feeding it a status renders "Unknown". | One line in `reservationCard()`: `ReservationStage::for($reservation)`. It must come from that resolver — re-deriving stage words in Blade would be a second definition of a concept this project deliberately keeps in one place. | **REAL GAP** — deliberately not guessed at in the view. |
| D10-3 | **Teammate presence** in the Assign modal — "online", "3 conversations open", "off shift · back tomorrow 08:00". | The assign modal lists real staff with no presence or load at all. | Three different things: live "online" needs websockets (**Reverb is running idle** — §H deferral); "N conversations open" is a countable over real threads; "off shift · back tomorrow" is derivable from the REAL roster (`StaffRoster` / `RosterWeek`). | **SPLIT — "online" is a DEFERRED CHANNEL; the load count and the shift state are REAL GAPS.** Do not fake presence. |
| D10-4 | A **"Suggested"** assignee in the Assign modal. | No suggestion is offered. | The same shape Housekeeping already uses: genuinely least-loaded by real open-thread count. ⚠️ It must be computed, not "first in the list" — that exact bug was fixed on Housekeeping (see §8), and the fix note explains why a badge that contradicts the number beside it is a false claim (#39). | **REAL GAP** — small, with a proven pattern to copy. |
| D10-5 | A **VIP** badge on threads. | Not surfaced in the inbox, although `guests.vip` is a real column the CRM already renders. | Carrying the real flag into the thread/guest card. | **REAL GAP** — small. |
| D10-6 | **"Booking.com"** as a guest's channel in the New-message picker. | Every live thread is `Direct`; no OTA channel appears. | Live OTA message sync (Channex) — the same account-gated integration behind the Channels page. | **ACCOUNT-GATED** (§H) — a channel chip that claims an OTA thread the product cannot send or receive on would be a fabrication (#37/#39). |

### Also noted on this page (not backend gaps)

- **REAL threads, #38-scoped.** Four real `MessageThread` rows through `InboxService`, tenant + property
  scoped, with honest empty states ("No conversations yet." / "No conversations match.").
- **#28 verified and NOT widened.** No email or phone appears anywhere on the page — the inbox is
  **name-only**, and the component docblock states the perimeter explicitly. The strip added this pass shows
  dates + unit, no contact. Its "View guest profile" link points at `app.guests.show`, which is gated by the
  **same `guests.view` permission as the inbox itself** — a link to an already-permitted page, not a new
  disclosure. The page also carries the honest footnote *"contact details stay private."*
- **No owner path to guest threads** — an owner thread renders its own footnote, *"Your owner replies in
  their portal · this thread is not visible to the guest."*
- **Deferred channels stayed honest — nothing fabricated.** Verified with word-boundary checks: **no AI or
  suggested reply, no SMS, no WhatsApp, no presence/typing indicator**. The only channel chip rendered is the
  real `Direct`. ⚠️ A first probe reported an "AI" hit — that was a case-insensitive regex matching
  "det**ai**ls"; re-checked precisely before reporting.
- **All six mutations still POST their real gated routes** — reply, assign, close, snooze, link-reservation,
  start. Search stays a **GET** (a scoped read, #20). Observed read-only; nothing was submitted.
- **Already at parity (not gaps):** the three compose templates (Pre-arrival details · Payment reminder ·
  Thanks & review, each pre-merged through the ONE shared `MessageTemplate`), internal notes ("guests never
  see this"), the failed-delivery **Retry**, delivery states (Delivered / Sent / Sending… / Failed), snooze /
  close / reopen, link-reservation, and the read-only workspace mode.
- **Fixed:** the conversation gained the design's **reservation context strip** under the thread header —
  real dates + real unit + a right-aligned guest-profile link — replacing a bare reference button, so a
  replier can see who they are answering without leaving the thread.

---

## 11. Operator Maintenance board (`/app/maintenance`)

**Prototype:** `docs/design/raw/Operator Maintenance.html` — 1,631 lines, decoded **byte-identical** to the
audit copy. **Role:** Hotel Manager. *(The work-order DETAIL — `Operator Work Order.html` → `/app/maintenance/{id}`
— is NOT covered here; it is the next page-area. Costs and Settings are their own screens.)*

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D11-1 | A **"Recommended"** technician in the Assign modal. | No recommendation. The modal pre-checks the **first** staff member (`$i === 0`) — but it makes **no claim**, so nothing on screen is false today. | A genuinely computed pick — least-loaded by real open-ticket count. The panel beside it already shows each technician's real load ("2 open · 1 urgent"), so the recommendation must agree with that number. | **REAL GAP.** ⚠️ **Do NOT pin a "Recommended" badge onto the existing first-item default** — that is the exact bug §8 records fixing on Housekeeping, where a badge contradicted the load printed next to it (#39). |
| D11-2 | **"logged by Marco Reyes"** on a work-order card. | The card payload has no reporter field (verified against `MaintenanceList::card()`: id/title/description/unit/category/priority/status/assignee/oos/parts/sla only). | Carrying the ticket's reporter onto the card. | **REAL GAP** — small. |
| D11-3 | **"Sends the work order to their phone right away"** under the technician picker. | No device push exists. | Push (FCM/APNs) — an §H account-gated deferral. | **RATIFIED REFUSAL / ACCOUNT-GATED.** The live copy is already the honest version: *"The work order appears in their task list. (Device push isn't wired — no fabricated notification.)"* **Do not restore the prototype's sentence.** |

### Also noted on this page (not backend gaps)

- **REAL work orders, #38-scoped** — five real `MaintenanceTicket` rows through the live board query,
  restricted to the readable properties, with a real property switcher.
- **The technician pool is staff-only and excludes owners — verified at BOTH layers.** In the browser the
  Assign modal offers exactly **4 back-office staff and no owner**. In the source, the pool is
  `StaffRoster::assignableStaff()`, whose docblock records that this **used to select by active membership
  alone and therefore offered External Owners as technicians**; and `MaintenanceActionController` independently
  `abort(422, 'That user is not assignable staff in this organization.')` on **both** assign paths (single and
  bulk). The server guard was not touched.
- **Cost is REAL, not fabricated (#17).** The out-of-order card shows **€100.00 / night · revenue on hold**
  over **1 unit blocked (101)** — a real figure derived from the blocked unit, not a placeholder. Where there
  is nothing to show the page says so ("Nothing on order.", "Awaiting parts 0").
- **Parts-on-order is already at parity** — `part_name` and `part_eta` are real card fields and the panel
  renders "{part} · {unit} · arrives {eta}" exactly as the design does. The current "Nothing on order." is an
  **honest empty** (Awaiting parts = 0), not a missing feature. Checked before classifying.
- **Status is never colour-only** — every chip carries its word (Out of order · Urgent · In progress · Open ·
  Resolved · High · Normal · Low).
- **#28 — no guest contact on a work-order card**; no email or phone appears anywhere on the board.
- **All mutations still POST their real gated routes** (create, bulk assign, bulk resolve, bulk export);
  search and the status filter stay **GET** (scoped reads, #20). Observed read-only; nothing was submitted.
- **Kept, not removed (§6 extra work):** the real **SLA** bar with "Due in 1h" / "5h to target" breach states,
  the property switcher, bulk **Select** mode, the Costs report and Settings links, and the honest footer
  ("Showing 5 orders from the last 7 days · older history lives in the costs report").
- **Fixed:** the empty state took the design's two-line form — **"No work orders match" + "Try a different
  status filter, or search by unit, issue, or technician."** — but only when a search or filter is actually
  active. A genuinely empty board now says **"No work orders yet"**, so the page never implies a filter is
  hiding work orders that do not exist. ⚠️ The first version of this compared `$filter !== 'all'`; the real
  default is **`null`**, which would have shown the filter message on an empty board. Caught by reading the
  component, and corrected before it shipped.
