# Vyrobel — Missing Backend, by page

**Purpose.** This file is the deliverable of the **design-parity pass** (started 2026-08-09) that drives a
**separate, later BACKEND phase**. The design pass walks the standalone prototypes in `docs/design/raw/`
**one page per turn**, makes the live page's design match the prototype, and records here — **without
building it** — every piece of functionality/backend the prototype implies that the live product lacks.

**Design is fixed as we go. Backend is logged, never built in this pass.**

---

## ⚠️ STATUS — 🏁 the OPERATOR REALM IS COMPLETE (2026-08-10)

**All 42 operator prototypes are covered**, across 34 numbered gates (`0aa9ede` → `edb7cce`). Every operator
page has been decoded, diffed against the live page in a real browser, fixed where the gap was presentational,
and recorded here where the product honestly refuses or genuinely lacks the backend.

**⬅ NEXT: the OWNER realm (32 prototypes, untouched), then GUEST (32, untouched), then the BACKEND phase that
this file exists to drive.**

⚠️ **Two heading conventions coexist below** — the earlier pages are `## N · Title` (Page 1–26) and the later
ones `## §N — Title` (§27–§34). Both are per-page entries with the same table shape; the numbering is
continuous. Do not renumber.

**The highest-value entries to start the backend phase from** — `D3-4` (Insights LY occupancy divided by spine
coverage, which misreads for *every tenant's first year*), `D16-7` (a payout in `processing` is invisible in
every Payout-Run KPI once Stripe is live), `D2-2` (no channel attribution — the Channex root), and `D34-1/2`
(open shifts and week-publication now EXIST but the shared `RosterWeek` doesn't expose them, so the PDF can't
show them — ⚠️ they must be added to the shared builder, never queried in the template).

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
| 8 | Operator Housekeeping | `/app/housekeeping` | Hotel Manager | ✅ matched | **2** (2 real gaps) |
| 9 | Operator Team Schedule | `/app/schedule` | Hotel Manager | ✅ matched | **2** (2 real gaps) |
| 10 | Operator Inbox *(Messages + Conversation + Inbox → one page)* | `/app/inbox` | Hotel Manager | ✅ matched | **6** (4 real gaps · 1 split · 1 account-gated) |
| 11 | Operator Maintenance board | `/app/maintenance` | Hotel Manager | ✅ matched | **3** (2 real gaps · 1 refusal) |
| 12a | Operator Work Order detail | `/app/maintenance/{id}` | Hotel Manager | ✅ **at parity, 0 code** | **1** (1 real gap) |
| 12b | Operator Maintenance Costs | `/app/maintenance/costs` | Hotel Manager | ✅ matched | **3** (3 real gaps) |
| 13 | Operator Maintenance Settings | `/app/maintenance/settings` | Hotel Manager | ✅ matched | **2** (1 real gap · 1 refusal) |
| 14 | Operator Settings *(+ Property Settings, the 2nd prototype of the same page)* | `/app/settings` | ⚠️ `properties.manage` | ✅ matched | **4** (2 real gaps · 1 refusal · 1 ⛔ tied to the billing decision) |
| 15A | **Operator coverage sweep** (enumeration + reconciliation) | — | — | — | **0** — 42 prototypes reconciled |
| 15B | Operator Refund | `/app/reservations/{id}/refund` | ⚠️ `payments.refund` | ✅ **at parity, 0 code** | **4** (1 real gap · 3 refusals) |
| 16 | **Operator Payout Run** ⚠️ real owner money | `/app/payouts/run` | ⚠️ `payouts.manage` on the WRITES | ✅ matched | **6** (3 real gaps · 3 refusals) + ⚠️ **1 money-visibility finding** |
| 17 | **Operator Billing** ⚠️ the OPEN billing decision | `/app/billing` | ⚠️ `can:billing.manage` on the GET | ✅ matched | **5** (2 real gaps · 3 refusals) + ⚠️ **1 G.1 observation** |
| 18 | **Operator Rates & availability** ⚠️ what guests are charged | `/app/rates` | `can:rates.view` · writes `can:rates.manage` | ✅ matched | **6** (2 real gaps · 4 refusals) |
| 19 | **Operator New Reservation** ⚠️ money + inventory | `/app/reservations/create` | `can:reservations.create` (GET **and** POST) | ✅ matched | **4** (2 real gaps · 2 refusals) |
| 20 | **Operator Group Booking** ⚠️ money + inventory, multi-unit | `/app/group-booking` | `can:reservations.create` (GET **and** POST) | ✅ matched | **3** (2 real gaps · 1 refusal) |
| 21 | **Operator Owners (LIST)** ⚠️ owner PII + isolation | `/app/owners` | GET ungated · writes `can:users.manage` | ✅ matched | **3** (2 real gaps · 1 refusal) |
| 22 | **Operator Owner DETAIL** ⚠️ deepest owner PII + the #6 guarded proposal | `/app/owners/{id}` | GET ungated · writes `can:users.manage` / `can:rates.manage` | ✅ matched | **4** (2 real gaps · 2 refusals) — **Owners area COMPLETE** |
| 23 | **Operator Roles & permissions** ⚠️ RBAC / privilege | `/app/roles` | GET+assign `can:users.manage` · **matrix `can:roles.manage`** | ✅ matched | **3** (1 real gap · 2 refusals) + **a self-escalation test gap closed** |
| 24 | **Operator Security** ⚠️ auth surface | `/app/security` | per-user (auth + back-office, **no fine-grained permission**) | ✅ matched | **2** (1 real gap · 1 refusal) + ⚠️ **a hardcoded safety badge fixed** |
| 25 | **Operator Reviews** — guest-authored content | `/app/reviews` | `can:reviews.view` · writes `can:reviews.manage` | ✅ matched | **4** (2 real gaps · 2 refusals) + ⚠️ **3 AA failures fixed + the page tokenised** |
| 26 | **Operator Channels** ⚠️ the OTA-claim ROOT (#37) | `/app/channels` | **Pro-gated** (`plan.feature:channels`) | ✅ matched | **5** (1 real gap · 4 refusals) — ⚠️ **almost the whole prototype is Channex-tied** |

**Operator: 34 of 42 prototypes covered · 8 remaining.** ⚠️ §15 GATE A below records "22 covered / 20
remaining" — that was the count **before GATE B closed Refund** and before §16 closed Payout Run.
**⬅ NEXT PAGE: Operator Portfolio (`/app/portfolio`)** — ⚠️ multi-property isolation (#38).

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

---

## 12a. Operator Work Order detail (`/app/maintenance/{id}`)

**Prototype:** `docs/design/raw/Operator Work Order.html` — 896 lines, decoded **byte-identical** to the audit
copy. **Role:** Hotel Manager. *(Deferred from Page 11 and completed here.)*

**Verdict: essentially AT PARITY — no design fix needed.** The live detail already carries the design's
breadcrumb, title + status + priority chips, the facts grid, "Reported issue" with Add-photo, the Activity
timeline with a Note / Progress / Part-ordered composer, and the right rail's dark STATUS card, ASSIGNED TO +
Reassign, RESOLUTION TARGET bar, and MANAGE block. It also already implements the design's two most
consequential flows: the **mark-resolved** modal capturing real cost + hours + notes + return-to-sale + notify
(#17), and **Reassign** with "Notify both technicians".

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D12a-1 | An **Area** line — "Living room · Wall unit" — a sub-location within the unit. | No area/sub-location field exists on the ticket. | A nullable area/location column on `maintenance_tickets` plus a field in the create + edit flows. | **REAL GAP** — small, purely additive. |

- **Already at parity (checked in source, not inferred from the screen):** the out-of-order impact block is
  driven by **real `condition_status` + real affected bookings**, and the resolved-cost block posts a **real
  `owner_expense`** — including the honest note *"Operator cost — this unit isn't owner-managed, so no owner
  expense was posted."* ⚠️ The resolve modal is absent from the DOM for an `open` ticket; that is STATE, not
  a missing feature (the §10 lesson).
- **All six mutations POST real gated routes** (start, photos, notes, parts, cancel, assign); none submitted.
  No guest contact anywhere (#28); status carries its word, never colour alone.

---

## 12b. Operator Maintenance Costs (`/app/maintenance/costs`)

**Prototype:** `docs/design/raw/Operator Maintenance Costs.html` — 861 lines, decoded **byte-identical** to
the audit copy. **Role:** Hotel Manager (`maintenance.manage`).

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D12b-1 | An **Excel workbook** export — "Summary + line-item tabs". | Export offers **PDF** and **CSV** only. | A spreadsheet writer (a new dependency) plus a second sheet for the summary. Both existing exports already share `MaintenanceCostService`'s property-scoped source, so a third must too — screen scope == export scope. | **REAL GAP** — needs a package decision, so explicitly not started here. |
| D12b-2 | A **"Reporting month" / "Year peak" / "2024 ›"** period navigator with year drill-down. | Range pills (3M · 6M · 12M · All time) with no month or year drill. | A month/year parameter through `report()`'s existing `$from`/`$to`, plus the navigator. ⚠️ It must ride a **GET link** — the property middleware does not run on `/livewire/update` (#20). | **REAL GAP.** |
| D12b-3 | A **6-month trend** chart. | `byMonth()` groups by month and returns **only months that have data**, so a single month of spend renders as a single bar rather than a trend. | Padding the series with genuine **zero** months across the selected range. That is a service-shape change, not a styling one — and zero is a true value, so it would not be fabrication. | **REAL GAP** — deliberately left to the backend phase. |

### Also noted on this page (not backend gaps)

- ⚠️ **THE PROPERTY SCOPE WAS NOT WIDENED — verified three ways.** (1) The diff is **one view file, the chart
  block only**, reading the same `$report['by_month']` the old markup read — no query, service or scope was
  touched. (2) `MaintenanceCostService::report(array $propertyIds, …)` still **requires** the read-set and
  still filters `whereHas('unit', property_id ∈ $propertyIds)`; its own comment records that the scope
  "MIRRORS the board exactly … rather than inventing" one. (3) **`MaintenanceCostScopeTest` passes 6/6** — the
  adversarial proof from the auth pass, which is the test that would break if screen and export scopes
  diverged. On screen, the costs shown (units 103 and A2) are both inside the current read-set: the demo
  operator has no `property_user` pivot rows, so the pivot-or-all rule gives the full org (#38).
- **Costs reconcile to the cent (#17), checked on the rendered page:** rows €45.00 + €180.00 = the **€225.00**
  total; Avg/order €112.50 = 225 ÷ 2; Spend-by-category Appliance €180 + Structural €45 = €225; Owner-billed
  €180 + Operator-borne €45 = €225; Top units A2 €180 + 103 €45 = €225; Labour €225 + Parts €0 = €225.
- **The export was NOT touched** — PDF and CSV generation, and their shared property-scoped source, are
  unchanged.
- **#28** — no guest contact on any cost line. Money is never colour-only; every figure is printed.
- **Fixed (presentation, and an anti-drift consolidation):** "Spend by month" was a **second hand-rolled bar
  chart** and is now rendered through the shared `<x-charts.bars>`. Two real defects went with it: the local
  copy printed its figure only in a **`title` tooltip** (invisible without a mouse, and on touch entirely),
  and it floored every bar at **`max(3, …)`**, so a genuinely **€0 month would draw a visible stub that reads
  as real spend** — exactly the #39 fix already recorded in the shared component's comments. The month now
  shows its label **and** its figure on the page.

---

## 13. Operator Maintenance Settings (`/app/maintenance/settings`)

**Prototype:** `docs/design/raw/Operator Maintenance Settings.html` — 537 lines, decoded **byte-identical** to
the audit copy. **Role:** Hotel Manager (`maintenance.manage`).

**It IS its own page**, in both the design and the product — a standalone prototype and a standalone route
(`OperatorMaintenanceSettings`). Nothing needed folding into the main Settings screen.

⚠️ **Storage-shape correction, recorded because it changes what the risk here actually is.** These settings
are **NOT** a JSON map. `maintenance_settings` is a table of **typed columns** (four `sla_*_hours` smallints,
five nullable `assignee_*_id` FKs, eight booleans), one row per org (`organization_id` is **unique**).
`MaintenanceSettingsService::update()` assigns **field by field behind `array_key_exists`** guards, so a key
absent from the request leaves the stored column untouched. The WAVE-1 HIGH-1 hazard — a JSON blob
wholesale-replaced, losing sibling keys — **cannot occur here**, because there is no blob to clobber; the
same protective property is achieved structurally. The service also audits **which** fields changed, never
their values (the settings-write discipline). None of this was touched.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D13-1 | The header scoped to a **property** — "Seaside Villas" beside the page title. | Settings are **org-wide**: `organization_id` is unique on the table, so one row governs every property. | Re-keying the table per property (or per-property overrides over an org default) plus a scope selector. A real product decision, not a styling one. | **REAL GAP / product decision** — logged, not started. ⚠️ Do NOT add a property name to this header while the setting is org-wide; that would tell the operator these rules are per-property when they are not. |
| D13-2 | A technician **"Invite"** button in the Technicians card. | No invite here. | Nothing — see below; the live copy is the honest form. | **REFUSAL-PRESERVE.** There is no separate technician role to invite: any active team member can be assigned work. The live card says exactly that and links to Roles & access. **Do not add an "Invite" that would imply a technician-specific invite the product does not have.** |

### Also noted on this page (not backend gaps)

- **The settings values are REAL and org-scoped (#38)** — every rendered control matches the stored row:
  SLA 4 / 8 / 48 / 168 hours, auto-assign off with all five categories unassigned, block-room-from-sale on,
  notify-front-desk on, require-photo off, and the five notify toggles at their stored states.
- **The save still POSTs its real gated route** — one `POST /app/maintenance/settings` with CSRF, straight to
  the `maintenance.manage`-gated controller → `MaintenanceSettingsService::update()`. Observed read-only;
  **nothing was submitted**. The saved confirmation is a real flash (`maintenance_settings_notice`) that the
  view already renders — absent on a fresh load because there is no flash, not because it is missing.
- **The honesty markers survived the change and must be preserved** — 2 × **"NOT DISPATCHED YET"**
  ("Preference stored; maintenance doesn't send this alert yet — nothing is sent"), 1 × **"NOT ENFORCED YET"**
  ("Photos on work orders are live, but resolve doesn't require one yet"), and the contrasting **"Already
  enforced — an out-of-service unit is excluded from availability and can't be booked"**. These distinguish a
  stored preference from an enforced rule, which is the whole point (#39).
- **Fixed:** the design's **Discard** control, which the page lacked, now sits beside Save. It is a plain
  **GET link back to the same route** — re-reading the page *is* the discard, because every control renders
  from the stored settings. **Proven in the browser, not assumed:** the Urgent-hours field was edited to 99,
  Discard was clicked, and the field came back as the stored **4** with **no "saved" flash** — i.e. it
  reverted the form and wrote nothing. It makes no claim to undo anything already saved.

---

## 14. Operator Settings (`/app/settings`)

**Prototype:** `docs/design/raw/Operator Settings.html` — 1,282 lines, decoded **byte-identical** to the audit
copy. ⚠️ **Role: `properties.manage` — held by `Property Owner` (the ORG ADMIN) and `Group Admin`, NOT by
Hotel Manager.** The demo Hotel Manager gets a clean **403** here; this page was reviewed as
`demo-admin@vyrobel-demo.test` (Property Owner). Worth knowing before anyone reports Settings as "broken".

**Sections covered this turn:** the whole of `/app/settings` — Property basics · Policies · Tax & fees ·
Invoice details · Your plan · Notification routing · Guest messages · Owner · Property status · Who can
access · Guest-facing brand · Taxes & fees editor · Organization.
**Sections REMAINING (their own pages, next turn):** **`/app/billing`** (`billing.manage`) and
**`/app/security`**. Integrations are **platform-admin** (`/admin/integrations`), not an operator section.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D14-1 | A **hero KPI strip** — "VAT rates % · Alerts routed · Free-cancel days · Min-stay nights". | No hero strip. Two of the four are real and on the page (free-cancel days, min-stay nights); **the other two are not available as scalars**: a property can carry several percentage taxes, so "VAT rates %" is a list not a number, and "Alerts routed" counts a per-event routing model whose per-role routing is honestly **"Soon"**. | A single canonical VAT rate (a modelling decision) and a real routing model to count. | **REAL GAP — deliberately NOT half-built.** Rendering two real tiles and inventing the other two is exactly the fabrication this pass refuses (#39). |
| D14-2 | Staff **email addresses** in the team list (`elena@seasidegroup.pt` …). | The access list payload is `{id, name, initials, roles, scope, you}` — **email is not even loaded**. | Nothing technical; it is a deliberate narrowing. | **REFUSAL-PRESERVE.** The live surface is narrower than the design on purpose. **Do not add staff emails** to match the mock. (Pending *invites* do carry an email, because an invite is addressed to one.) |
| D14-3 | **"Owner statements"** block — "Statements finalize 1st of the month · Payouts sent 3rd business day". | The Owner card shows the real owner, unit count and **real 18% commission**, but not the statement/payout cadence. | Reading the real `PayoutSchedule` cadence into this component — it exists and already drives the owner-side "next payout" date, so this is wiring, not invention. | **REAL GAP** — small, and the data is genuinely there. |
| D14-4 | A plan card reading **"Vyrobel Pro · 45 rooms · 3 channels · €99/month · renews 1 Oct 2025"**. | The live card shows the org's **real** subscription: "Vyrobel Essential · €88.56/mo · Active". | — | ⚠️ **TIED TO THE OPEN BILLING DECISION — not a plain gap.** The prototype's €99 is a mock constant; the live figure is the real stored one. **Do not wire the prototype's price, and do not resolve which pricing model is authoritative** — that is the founder's open go-live blocker. Verified the mock price does **not** leak onto the page. |

### Also noted on this page (not backend gaps)

- ⚠️ **JSON-MERGE, NOT REPLACE — confirmed at the source and untouched.** `SettingsService` merges the policy
  JSON columns: `$changes[$field] = array_merge($existing, (array) $changes[$field])`, above a comment
  recording the WAVE-1 HIGH-1 defect verbatim — *"assigning here once destroyed `cancellation.free_until_days`
  on every save."* The controller matches it: *"send only the keys this form owns … `free_until_days` is the
  load-bearing one … rather than left to be clobbered by a sibling key."* The recovery fallback that reads a
  legacy `tier` key is still present. **None of this was changed.**
- **All 10 saves POST their real gated routes with CSRF** — property · policies · invoice · tax-fees ·
  fees/add · brand · pause · remove/schedule · organization (+ the property switcher). **Nothing submitted.**
- ⚠️ **API keys are NOT surfaced here, and the "shown once" rule is intact by construction.** `ApiKey`
  persists **only `hashed_key`**, which is in `$hidden`, and its docblock states *"Only the hash is persisted;
  the plaintext is shown once at creation."* **No Livewire component or controller references `ApiKey` at
  all** — there is no operator API-key UI. Nothing to weaken, and none was built. No reveal control exists.
- **#28 — the team list shows STAFF, not guests:** name + initials + role + scope, **no email**, no guest PII.
  The only `@` on the page is the brand **reply-to placeholder** (`hello@example.com`).
- **Honest deferrals kept:** 2 × "Soon" (per-role notification routing · SMS/Push), and the SAF-T line —
  *"the details above are saved now, but no certified invoice or SAF-T file is generated yet."*
- **Figures reconcile:** the sample 4-night stay computes Room €600.00 + City tax €20.00 + VAT €138.00 +
  Cleaning €45.00 = **Guest pays €803.00**.
- **Fixed (1) — a real defect, not a style tweak.** The cancellation-policy selector had **two `style`
  attributes** on the same `<label>`. HTML keeps the first duplicate and discards the rest, so the selected
  state never applied — and because the radio is `position:absolute;opacity:0`, there was **no visible
  indicator of which policy was active at all**. Proven in the DOM (all three labels carried an identical
  style attribute while `flexible` was genuinely checked). Now one conditional style attribute; the selected
  option is distinguished by **border + background + weight**, not colour alone (WCAG 1.4.1).
- **Fixed (2) — accent-as-text contrast.** 14 inline `color: var(--vy-accent)` declarations were retargeted to
  `--vy-accent-text`; the "Edit tax & fees" and "View full permissions" links now measure **6.01** against the
  glass (AA needs 4.5; accent-as-text measured ~4.06 on Page 4). `border-color: var(--vy-accent)` was left
  untouched — verified 1 → 1, using a lookbehind, because the Page-4 lesson is that a naive replace also
  matches property names ending in "color:".
- ⚠️ **REALM-WIDE FINDING (logged, not swept):** **248** inline `color: var(--vy-accent)` declarations remain
  across `resources/views/`, concentrated in the owner realm (bookings 13, owner-settings 9, help 9,
  messages 8, welcome 7, unit-detail 7). **A blanket replace would be WRONG** — many sit on tinted chip
  backgrounds where the accent is the correct foreground, which is exactly why 31 chip/tag rules were left
  during the Page-4 pass. Each site needs its real composited background measured. Worth its own pass.

---

## 15 · GATE A — OPERATOR-REALM COVERAGE SWEEP (enumeration + reconciliation)

`docs/design/raw/` holds **125** prototypes: **42 OPERATOR** · 32 OWNER · 32 GUEST · 19 shell/email/PDF/other
(`index`, `System States`, the 8 transactional emails, the `Vyrobel *` auth/onboarding/checkout set).

**Operator set: 22 covered, 20 remaining** *(as at the moment GATE A was written — **GATE B below then closed
Refund**. ⚠️ **That figure is historical — the operator realm is now COMPLETE at 42/42**; see the STATUS
header at the top of this file).*

✅ **Covered (22):** Dashboard (P1) · Calendar (P2) · Insights (P3) · Reports (P4) · Front Desk (P5) ·
Reservations (P6a) · Reservation Detail (P6b) · Reservation Cancelled + No-show (P6c) · Guests (P7a) ·
Guest Profile (P7b) · Housekeeping (P8) · Team Schedule (P9) · **Messages + Conversation + Inbox** (P10,
three prototypes → one live page) · Maintenance (P11) · Work Order (P12a) · Maintenance Costs (P12b) ·
Maintenance Settings (P13) · Settings (P14) · **Property Settings (P14 — a SECOND prototype of the same live
page; its title, Policies, Tax & fees, Owner and Property-status blocks all match `/app/settings`)**.

⬜ **Remaining (20), ordered by stakes:**

| # | Prototype | Live route | Why it ranks here |
|---|---|---|---|
| 1 | Operator Refund | `/app/reservations/{id}/refund` | ⚠️ real money · #27 refund bound — **closed in GATE B below** |
| 2 | Operator Payout Run | `/app/payouts/run` | ⚠️ real money · owner payouts |
| 3 | Operator Billing | `/app/billing` | ⚠️ money · **the OPEN billing decision** |
| 4 | Operator Rates | `/app/rates` | ⚠️ money · what guests are charged |
| 5 | Operator New Reservation | `/app/reservations/create` | ⚠️ money + inventory (`AssignmentService`) |
| 6 | Operator Group Booking | `/app/group-booking` | ⚠️ money + inventory |
| 7 | Operator Owners | `/app/owners` | ⚠️ PII · owner isolation (#28/#42) |
| 8 | Operator Owner Detail | `/app/owners/{id}` | ⚠️ PII · owner isolation |
| 9 | Operator Roles | `/app/roles` | ⚠️ RBAC escalation (#30) |
| 10 | Operator Security | `/app/security` | auth · the firm rulings |
| 11 | Operator Reviews | `/app/reviews` | guest-authored content |
| 12 | Operator Channels | `/app/channels` | OTA claims (#37) |
| 13 | Operator Portfolio | `/app/portfolio` | multi-property isolation (#38) |
| 14 | Operator Search | `/app/search` | cross-entity · isolation |
| 15 | Operator Notifications | `/app/notifications` | self-only feed |
| 16 | Operator Notification Preferences | `/app/notification-preferences` | preferences |
| 17 | Operator Email Templates | `/app/email-templates` | guest-facing copy |
| 18 | Operator Email Editor | `/app/email-templates/{key}/edit` | guest-facing copy |
| 19 | Operator Profile | `/app/profile` | account |
| 20 | Operator Schedule PDF | `/app/schedule/pdf` | a PDF, not a screen |

**No operator prototype lacks a live equivalent** — every one of the 42 maps to a real route. (`Operator
Inbox`/`Messages`/`Conversation` collapse onto one live page, and `Property Settings`/`Settings` onto another;
those are prototype variants, not missing surfaces.)

---

## 15 · GATE B — Operator Refund (`/app/reservations/{id}/refund`)

**Prototype:** `docs/design/raw/Operator Refund.html`. ⚠️ **Role: `payments.refund`** — the route comment notes
the GET screen is gated too, so an unpermitted role never opens a form that would 403 on submit. Reviewed as
`demo-admin` (Property Owner).

**Verdict: AT PARITY — no design fix needed, 0 code changed.** This page already states its own money bounds
better than the mock does.

| # | What the prototype shows | Live | Verdict |
|---|---|---|---|
| D15-1 | A stored card — **"Original card · Visa ····4242 · Back to the card on file · 2–5 business days"**, and SEPA **"1–2 business days"**. | "Original method · Manual / offline" and "Manual bank transfer · recorded, no gateway". | **REFUSAL-PRESERVE.** No card is stored (SAQ A — no raw PAN), and no gateway means **no delivery ETA exists**. Verified absent: no "Visa", no "4242", no "business days", no "Arrives". **Do not add a card or an ETA to match the mock.** |
| D15-2 | The guest's **email** (`amara.okafor@mail.com`) on the refund card. | Guest **name only**. | **REFUSAL-PRESERVE (#28).** The CRM is the one PII surface; a refund screen is not. Verified: no email or phone anywhere on the page. |
| D15-3 | **Per-charge refunding** — a charge list with "Posted {date}" and a "Selected total". | An honest **"Refund by individual charge — SOON"** card: *"Refunds attach to a payment, not to individual charges — a per-charge credit note is a separate concept."* | **RATIFIED REFUSAL, already stated.** |
| D15-4 | The **unit** ("Villa Azure") in the reservation header. | Header shows Guest + Stay; the payload is `{id, reference, status_label, check_in, check_out, guest, folio, payments}` — **no unit**. | **REAL GAP** — small, but it needs the unit loading into the component, so it is backend, not markup. Logged, not built. |

### Safety line — verified on the rendered page

- ⚠️ **#27, the cumulative refund bound, is SURFACED not just enforced:** Original **€369.00** − Already
  refunded **€0.00** = Still refundable **€369.00**, arithmetically consistent, with the rule stated in copy:
  *"Up to €369.00 is still refundable against this payment. Anything above that is refused, **not trimmed to
  fit**."* (This is the very payment that once took three full refunds before #27 existed.)
- **#17 reconciles:** Charges €369.00 − Paid €369.00 = Balance due €0.00, with a *"Reconciled to the folio
  ledger"* badge.
- **Append-only (#7) is explained, not just implemented:** *"A reversing entry is appended … The original
  payment is never edited or removed"*, and *"Refunds cannot be reversed once issued."*
- **Both refund forms POST** the real `payments.refund`-gated route. **Nothing was submitted.**
- **Test mode is honest:** *"Card refunds run in test mode until go-live — no real money moves yet."*
- **Contrast:** only one raw-accent text node on the page ("Open all notifications →" in the bell panel),
  measured **5.06** on its opaque panel background — passes AA. No fix needed here.

---

## 16 · Operator Payout Run — `/app/payouts/run` ⚠️ REAL OWNER MONEY

**Prototype:** `docs/design/raw/Operator Payout Run.html` (decoded fresh 2026-08-09 by rendering the
`__bundler` bundle; matches `/tmp/audit/Operator Payout Run.decoded.html`).
**Live:** `App\Livewire\Operator\PayoutRun` → `resources/views/livewire/operator/payout-run.blade.php`.
**Role:** ⚠️ the **GET is not `can:`-gated** (back-office + active subscription only); all three **WRITES are
`can:payouts.manage`**, held by **Property Owner / Group Admin / Accountant — NOT Hotel Manager**. Reviewed as
`demo-admin@vyrobel-demo.test` (Property Owner). The controls are `@can`-gated in the view too, so an
unpermitted viewer sees the page read-only rather than buttons that would 403 (**#30** "gate the control too").

⚠️ **TWO CORRECTIONS TO THIS PAGE'S OWN RECORD — both found by verifying, and both worth carrying.**

1. **The prototype's figures DO reconcile.** `PayoutRun`'s class docblock states *"its numbers do not
   reconcile: six rows summing to 66,000 plus '+6 more · €24,000' implies 90,000 while its header claims
   89,990"*. Recomputed from the rendered design: the **five READY rows** total **65,990**; `+ 24,000` =
   **89,990**, which is exactly *"READY TO SEND €89,990"* and *"Send 11 transfers · €89,990"*; `+ 6,410` held
   = **96,400**, exactly the RUN SUMMARY; `5 shown + 6 more = 11 transfers`; `11 + 1 held = 12 statements`.
   **All four tie out.** The docblock counted the **on-hold row** among the ready rows. The design's *data* is
   still fabricated demo data and is still correctly refused — but *"its numbers do not reconcile"* is not a
   sound reason to dismiss its **structure**, and this pass matched that structure.
2. **"On hold" is no longer an invented word.** The docblock and `OperatorPayoutRunTest`'s comment call
   `Scheduled` / `On hold` fictions *"in neither real enum"*. Since the payout engine (**#71**, migration
   `2026_08_20_000001`) the enum is `pending, processing, paid, failed, reversed, **held**` — so the live
   "On hold" card is genuinely backed. The test correctly forbids only **`Scheduled`** and **`In review`**;
   both remain absent (verified on the rendered page).

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D16-1 | A **run headline total** — "€96,400 · 12 statements · €89,990 sending · €6,410 held" — one figure spanning ready + held. | The component formats every bucket **per currency** and hands the view **strings** (`kpis.*.lines`). There is no combined total, and one cannot be assembled in Blade without re-deriving money in the view — a second definition of a money figure (**#17**). | A per-currency run total computed **in the component** (or in a shared helper) alongside the existing buckets, so page and any future run receipt cannot drift. ⚠️ It must stay per-currency: this page spans every owner in the tenant, and currencies are never summed. | **REAL GAP** — the dark Run-summary card built this gate shows the run's REAL status and counts and stops honestly where the money math stops. |
| D16-2 | A **3-step lifecycle track** — `Finalized → In review → Sent`. | Two of those three words are not states anything can hold. | — | **RATIFIED REFUSAL.** `In review` and `Sent` are the design's invented vocabulary; `OperatorPayoutRunTest` asserts their literal absence. **Do not add a lifecycle track using these words.** A genuine progression does exist (statement finalized → payout pending → paid) and could be drawn from real counts, but it must use the **real** vocabulary. |
| D16-3 | **"Export SEPA file"** as a live primary control, and **"SENDS · 3 Nov · SEPA batch 09:00"**. | No SEPA generator and no scheduled batch exist. | — | **RATIFIED REFUSAL** (sweep §5 "no SEPA export"). ⚠️ **Implementation-vs-docblock drift worth noting:** the component docblock says SEPA was *"omitted entirely rather than shown as a disabled 'Soon' — naming SEPA would itself be the lie"*, but the page **does** render a disabled `Export SEPA · Soon` pill. That matches the product-wide honest-deferral convention (the same page disables "Pay via Stripe Connect · Soon"), so it was **left as-is** — but the docblock is stale on the point. The design's *scheduled batch date* is not rendered and must not be. |
| D16-4 | An **"AFTER SENDING"** checklist: *"Owners are notified · receipts appear in their portal"* · *"Bank references attach to each payout automatically"* · *"The run locks — corrections post as new adjustments"*. | Claim 2 is **false for the bound rail**: `ManualPayoutRail`'s own docblock states a reference is *"not generated by the system; the operator may attach one"*. Claim 1 is backed (`PayoutSentNotification`). | Nothing — the card must not be ported as written. | **RATIFIED REFUSAL (partial).** ⚠️ This is the **WAVE-2 class exactly** — copy asserting behaviour the code doesn't implement. Porting the card wholesale would ship one true claim, one false one, and one unverified one. **Not built.** If it is ever wanted, render only claims verified true against the *bound* rail. |
| D16-5 | Per-transfer **bank identity** on every row — `PT50 ···· 7218`, `IE64 ···· 4821`, and a footer *"Debited from Seaside Group operational · Revolut ····9034"*. | The masked owner IBAN **is** real and rendered (`Iban::masked(iban_last4)`, `payouts.manage`-gated, last-4 only). What does not exist is the **operator's own funding account** — no such column anywhere. | A funding/operational-account model on the organization. ⚠️ Genuine product weight: it is the account money leaves *from*, and it belongs with the live-payout cutover, not a design pass. | **REAL GAP** — and ⚠️ **the country-prefix form (`PT50 ····`) must NOT be reproduced for owners**: only `iban_last4` is stored, so a prefix would have to be invented or the full IBAN decrypted. Last-4 masking is the #28 boundary and it holds. |
| D16-6 | An **"On hold · 1"** rail with **"Request IBAN"** and **"Carry forward"** as buttons. | "Request IBAN" renders as a disabled affordance; there is no route that sends such a prompt. Carry-forward is **not** an operator action — it happens inside `OwnerPayoutService` for a negative net. | A real "request payout details" notification to the owner would make control 1 genuine. | **SPLIT — "Request IBAN" is a REAL GAP (small, and a real notification class already exists); "Carry forward" is a RATIFIED REFUSAL** (offering a button for something the service does automatically would misrepresent who decides). |

### ⚠️ D16-7 · A MONEY-VISIBILITY FINDING (surfaced, NOT fixed — it is backend)

**A payout with status `processing` appears NOWHERE on this page, and in none of its three KPIs.**

The component builds five sets: finalized statements · `pending` · `paid` · `recent` (`paid|failed|reversed`) ·
`held`. **`processing` is in none of them** — verified by reading `PayoutRun::render()` lines 134–139.

- **It is a REAL state, not hypothetical.** The enum is
  `pending, processing, paid, failed, reversed, held`, and `OwnerPayoutService::confirmByTransfer()` resolves a
  webhook by `where('external_ref', $transferId)->where('status', 'processing')` — i.e. `processing` is exactly
  **"a transfer has left and we are awaiting its webhook"**, the Stripe-Connect in-flight state.
- **Consequence:** on the live rail, money genuinely in flight would be invisible on the operator's
  disbursement desk **and** excluded from Ready / Pending / Paid — so the three tiles would not account for it.
  ⚠️ **No figure shown is wrong (#17 is not violated) — but the page would be incomplete about real money.**
- **Latent today:** the demo has `pending: 1, paid: 5` and no `processing` row, so nothing is visibly missing.
  That is the same shape as the auth-pass items — invisible to a demo walkthrough, real under live conditions.
- **What it needs:** a `processing` bucket (its own "In flight" section, or folded into Pending with its real
  status chip) plus inclusion in the KPI strip. **Backend — deliberately not built in a design pass.**

### Also noted on this page (not backend gaps)

- **#17 RECONCILES — re-read from the rendered page, not assumed.** Recent transfers
  `442.80 + 656.00 + 467.40 + 516.60 + 721.60 = **2,804.40**` = the **PAID** tile exactly; the single pending
  row `885.60` = the **PENDING TRANSFERS** tile. **READY** shows **"—"**, not `€0.00` — the documented
  per-currency honesty (a zero must be a zero of *some* currency, and this page spans every owner).
- **The money path was NOT touched.** All three writes still POST their real gated routes with CSRF
  (`payouts/run`, `payouts/pay` ×2 — the batch and the per-row control), `OwnerPayoutService::pay()` and its
  transaction + `lockForUpdate` + idempotency key are unchanged, and the **#71 test-mode banner** still renders.
  **Restyled, not rewired — and nothing was submitted:** the run/pay controls were observed read-only, because
  a real submit moves money and mutates the seed.
- **#28 holds and was not widened** — verified on the rendered page: **0 raw IBANs** (regex for the full
  `XX00…` form), the only bank identity is the masked **`···· 1000`**, and **0 email addresses / 0 phone
  numbers** anywhere. Owner rows are **name + money**.
- **#38** — the page is **tenant**-scoped, not owner-scoped, and that is deliberate and correct (an operator
  pays every owner they manage in one pass). Isolation comes from `BelongsToOrganization`'s global scope plus
  the controller's scoped `findOrFail`, so a foreign statement is not in the collection at all. **Not changed.**
- **#39** — every status rendered is the real enum (`Pending` / `Paid`), and the invented `Scheduled` /
  `In review` are absent from the rendered page (checked with word boundaries). Status is never colour-only:
  each chip carries its word.
- **Fixed (1) — a measured layout defect, invisible in the source.** The stat strip used `.vy-dash-kpis`, a
  **four**-column grid, with **three** tiles — laying them across 3 of 4 columns and leaving **292px of dead
  space** at 1440. The class name reads perfectly fine; only the computed `grid-template-columns`
  (`274px 274px 274px 274px`) showed it. Now takes the existing `--3` modifier → `371px × 3`, filling the
  content width as the design does.
- **Fixed (2) — the dark surface was on the wrong element.** The design's three stat tiles are all light
  glass and its **one** dark surface is the **Run summary at the top of the right rail** (measured at
  x=838, y=292, w=568). The live page had it inverted: a dark KPI tile, an all-light rail. The dark treatment
  now sits on a new `.vy-runsum` rail card and the strip is uniform. `.vy-runsum` deliberately **mirrors
  `.vy-tonight`'s tokens** rather than defining a second dark-panel treatment.
- **Fixed (3) — the design's row anatomy**: an initial disc beside the owner name on transfer rows. Initials
  derive from the **real** name only; a row with no resolvable owner renders "—" and gets no disc.
- **Fixed (4) — breadcrumb** `‹ Owners / Payout run`, reusing the shape already established on the work-order
  detail. ⚠️ The design's period tail (`· Sep 2025`) is **not** reproduced — this page is not scoped to a
  month, and stamping one on would state a filter it does not apply.
- ⚠️ **CONTRAST — a defect I introduced and caught by measuring, worth recording as a pattern.** `.vy-runsum`
  first copied `.vy-tonight`'s `rgba(255,255,255,.5)` for its 9.5px uppercase labels. Modelled correctly —
  light text composites against **the same gradient stop it sits on**, so the worst case is the **lightest**
  stop `rgb(58,46,39)` — that lands on **exactly 4.50**, the AA boundary with nothing in hand. Raised to
  `.58` → **5.51**. All five text roles now measure 5.51–11.33. ⚠️ **REALM-WIDE, LOGGED NOT SWEPT:
  `.vy-tonight` still carries the marginal `.5`** on its own labels; it deserves the same measurement, with
  the other dark panels, in one pass — not a blind find-and-replace (the 248-accent-declaration lesson).
- **Documented adaptation — the column split was NOT matched.** The design is roughly **50/50** (left card
  568px, rail 568px); the live page is ~2:1. The live left column carries **three five-column tables**
  (ready / pending / recent) where the design has one flat list, and a 568px column would force them all into
  permanent horizontal scroll. Kept the wider column — the same call P9 recorded for the Team Schedule rail.
- **Kept, not removed (§6 extra work):** the split into **Ready to pay / Pending transfers / Recent transfers**
  (the design conflates all three into one list, and the split maps to the real payout lifecycle); the
  **"How this run pays"** rail explaining that marking paid **records** a transfer rather than sending one; the
  **"Not payable"** card explaining zero and negative nets; the per-row **"Rail not connected"** disabled state;
  and the real failure/reversal reporting with its reason.
- **Responsive:** 0px horizontal overflow at **1440** and **1000**; the KPI strip reflows to 2 columns and the
  wide table scrolls inside its own `overflow-x: auto` container. **0 console errors/warnings.**

---

## 17 · Operator Billing — `/app/billing` ⚠️ THE OPEN BILLING DECISION LIVES HERE

**Prototype:** `docs/design/raw/Operator Billing.html` (decoded fresh 2026-08-09; matches
`/tmp/audit/Operator Billing.decoded.html`). ⚠️ It **opens on its "Before you go" cancel modal** — the base
page is behind the scrim.
**Live:** `App\Livewire\Operator\OperatorBilling` → `resources/views/livewire/operator/billing.blade.php`.
**Role:** ⚠️ **the GET itself is `can:billing.manage`** — stricter than Payout Run, whose GET is ungated. Held
by **Super Admin and Property Owner ONLY** (not Group Admin, not Accountant, not Hotel Manager), so an
unpermitted viewer gets a clean **403** rather than a read-only page — #30 satisfied at the route, not merely
by hiding controls. Reviewed as `demo-admin@vyrobel-demo.test` (Property Owner).
⚠️ The prototype's own user chip reads **"Marco Reyes · Front desk manager"** — a front-desk role on a billing
screen. The live gate is stricter and correct; **do not widen it to match the mock.**

### ⚠️ THE G.1 OBSERVATION — surfaced, deliberately NOT resolved

**Both pricing models are rendered on this one screen, and the design endorses the per-unit one.**

- The plan card shows **€88.56/mo** — `SubscriptionPricing`: the org's **real** active-unit count (8) ×
  a **placeholder** €9.00 per unit, + 23% VAT (€72.00 + €16.56). Reconciles (#17), flagged placeholder.
- The tier catalog below shows **Essential €49.00 / Pro €99.00** — `BillingService::catalog()`, and
  **Essential is the plan this org is actually on**.
- So the page simultaneously tells this operator *"you pay €88.56/mo"* and *"Essential is €49.00/mo"*.

⚠️ **A NEW FACT FOR THE FOUNDER'S DECISION, worth recording precisely: the discrepancy has FLIPPED DIRECTION.**
`PROJECT-STATE.md` **G.1** describes per-unit as the *cheaper* figure ("B quotes ~€44 where A charges €99").
At the demo org's current **8 units** the per-unit model is **€72.00 net vs the tier's €49.00 — 47% HIGHER**.
The direction depends entirely on unit count (break-even is ~5.4 units at €9). **So the live-key risk is not
simply "the customer is undercharged" or "overcharged" — it is either, depending on the tenant.** This is
evidence for the decision, **not** a resolution of it.

⚠️ **Nothing about the model was changed, and no price was wired.** Verified on the rendered page: the real
€88.56 shows, and the prototype's mock **€66,42 does not leak** (nor its "6 units", nor its "#S-0001").
⚠️ Also confirmed the **"Switch to yearly" link still carries `plan=essential`** — the org's OWN tier — after
being restyled to the design's primary button. Restyling did not reintroduce the WAVE-2 · S3 silent-upgrade
bug (`resolveTier()` falling back to `'pro'`).

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D17-1 | A **paid invoice** — "#S-0001 · Pro monthly · 5 Jul 2026 · Visa ····4242 · ✓ Paid · €66,42" with a **PDF download** — plus "Invoices are also emailed to billing@… the moment they're paid". | No charge is ever taken in test mode, so **there is no paid invoice**; the live list shows the REAL trial line (Free, €0.00) and says so. The `invoices` table exists but has **0 rows**, and the component does not read it — it synthesises the trial row from the subscription. | Real invoices arrive with the Stripe live-key flip (invoice objects + a PDF route + the "emailed when paid" hook). | **RATIFIED REFUSAL today / ACCOUNT-GATED — TIED TO THE STRIPE-LIVE FLIP.** Fabricating a paid invoice with a PDF would be the single most dishonest thing on this page. **Do not build before the flip.** |
| D17-2 | A **stored card** — "PAYMENT METHOD · Visa ····4242 · Default · Expires 08/28 · Update", and a next-invoice line naming the card it will charge. | No PAN, no card token, no card metadata is stored anywhere (SAQ A — hosted Checkout only). The live rail says **"No card on file"** and explains the card is added on Stripe's page. | A Stripe payment-method reference + a portal/update flow, after the flip. | **RATIFIED REFUSAL / ACCOUNT-GATED — TIED TO THE STRIPE-LIVE FLIP** (sweep §5 "no raw PAN, no stored-card ····4242"). Verified absent from the rendered page: no "4242", no "Visa", no "Expires". |
| D17-3 | "**reminder email 3 days before**" the next invoice, inside the next-invoice strip. | No such reminder is scheduled — there is no pre-invoice notification anywhere. | A scheduled pre-billing notification, which only makes sense once invoices are real. | **RATIFIED REFUSAL** — the strip was built to the design's SHAPE but this clause was **not** reproduced. It is the WAVE-2 class (copy asserting behaviour the code doesn't implement). |
| D17-4 | A **street address** in Billing details — "Av. da Marina 14, 8000-114 Faro, Portugal". | The org stores `billing_company`, `billing_vat_id`, `billing_email` and `country` — **no street address**. The live card renders company / country / VAT / email. | Address columns on `organizations` + fields in the billing-details form. Genuinely useful for a compliant invoice. | **REAL GAP** — small and additive; it belongs with the invoicing work (D17-1). |
| D17-5 | **"Team members · 4 · unlimited"** on an org the mock calls Pro. | Nothing missing — and **the live page is better**: it shows the org's REAL limit from `plan_features`, rendering **"7 of 5"**, i.e. this Essential org is genuinely OVER its plan limit. | — | **EXTRA / HONEST-BY-DESIGN — ⚠️ TIED TO THE OPEN DECISION (#10).** The limit is **displayed, not enforced**, and whether it *should* be enforced is a paywall lever belonging with G.1. **Do not enforce it, and do not "tidy" 7-of-5 away** — it is the true state. |

### Also noted on this page (not backend gaps)

- **#17** — the plan figure reconciles on the rendered page: **8 × €9.00 = €72.00 net, + €16.56 VAT (23%) =
  €88.56 gross**, and the rail's "Units on the rack 8 × €9.00" agrees with the plan card's sub-line.
- **#28 not widened** — the only email address anywhere on the page is the org's **own billing contact**
  (`billing@vyrobel-demo.test`); **0 phone numbers**, no guest or owner contact. Billing is the org's account.
- **#38** — the whole page resolves through `TenantContext::id()`; nothing is cross-org.
- **The VAT ID matches the prototype's exactly ("PT 512 034 776") — checked, and it is NOT a leak.** It is a
  flagged demo value seeded at `DemoSeeder.php:117` and rendered from the DB; the only hardcoded copy is a
  `placeholder=` example on the checkout form. (Worth checking rather than assuming — the whole point of the
  mock-doesn't-leak rule.)
- **All four billing writes still POST their real `can:billing.manage` routes with CSRF** — `billing/company`,
  `billing/pause`, `billing/cancel`, `billing/dev-expire` — and the checkout links are unchanged.
  **Restyled, not rewired; the retention modal was opened READ-ONLY and nothing was submitted** (the
  subscription is still `Active` and `stripe_subscription_id` is still null).
- **Fixed (1) — the sub-nav became the design's SEGMENTED PILL control** (glass container, 3px pad, 30px
  radius, raised white active pill) instead of an underline tab row. ⚠️ Page-local: `.vy-subnav` is rendered
  **only** by Billing, and the Operator Settings prototype has no such control — checked before touching what
  looked like shared chrome. ⚠️ The prototype's own colours were **not** copied: its `#8A8079` label is the
  pre-AA `--vy-muted` value and its `#C2410C` active label is accent-as-text, both of which measured under AA
  in earlier gates; the AA tokens are used instead (measured 5.48 / 5.93).
- **Fixed (2) — the plan header was inverted.** The design puts the plan NAME as the heading with the status
  as a pill beside it and the price **right-aligned** on the same row; the live page had the plan name inside
  the chip, the status as plain text, and the price stacked below on the left.
- **Fixed (3) — the design's next-invoice STRIP** (bordered, calendar glyph), carrying the real period end and
  the real net/VAT split — and **not** the design's card reference or reminder-email promise (D17-2, D17-3).
- **Fixed (4) — "Switch to yearly" is the design's primary action** (accent), "Change plan" stays secondary.
- **Fixed (5) — Cancel subscription moved to the RAIL**, subdued, as the design places it (it was a full-width
  card in the main column), and **two stray `<br>` tags** inside the retention forms were removed.
- ⚠️ **Fixed (6) — A PRE-EXISTING MODAL BUG, found only because the card moved into a narrow column.**
  `.vy-card` sets `backdrop-filter: blur(22px)`, and **a backdrop-filter creates a containing block for
  `position: fixed` descendants**. The retention modal's `.vy-modal-scrim` is `position: fixed` and expects
  the viewport — but it was resolving against the CARD: measured **338×132 at (1071,840)** instead of
  **1440×1000 at (0,0)**, so the "centred" modal rendered squashed inside the rail. **Proven pre-existing by
  stashing this gate's changes and re-measuring on clean HEAD — the culprit ancestor is the same `.vy-card`;**
  it was simply invisible while the card was full-width. Fixed by moving the `x-data` scope to a plain wrapper
  so the modal is a sibling of the card rather than its child; the scrim now measures 1440×1000 with **no
  containing-block ancestor above it**, and every POST is untouched.
  ⚠️ **REALM-WIDE, LOGGED NOT SWEPT:** any other `position: fixed` element nested inside a `.vy-card` has the
  same latent bug. Worth one audit pass — not a blind change.
- **One test string updated, deliberately and provably not weakened.** `OperatorBillingLifecycleTest`
  asserted `'incl 23% VAT'`; the design's copy is `'incl. 23% VAT'`, so the assertion now tracks the shipped
  copy. Its three #39 guards (`assertDontSee('····4242')`, `assertDontSee('Visa ')`, `assertSee('No card on
  file')`) are untouched, and **the updated assertion was proven to still go RED** by replacing the VAT
  disclosure with "VAT included" — it fails if the disclosure is dropped or flipped to an exclusive figure.
- **Responsive:** 0px horizontal overflow at **1440** and **1000**; the plan header wraps, the sub-nav fits,
  the price stays visible. **0 console errors/warnings.** Contrast on every new element **5.48–13.38** (AA).

---

## 18 · Operator Rates & availability — `/app/rates` ⚠️ WHAT GUESTS ARE CHARGED

**Prototype:** `docs/design/raw/Operator Rates.html` (decoded fresh 2026-08-10). ⚠️ **A second Rates prototype
exists — `Owner Rates.html` — and is NOT covered here:** it belongs to the owner realm, which this pass has not
started. **Live:** `App\Livewire\Operator\RatesGrid` → `resources/views/livewire/operator/rates-grid.blade.php`.
**Role:** GET `can:rates.view`; every write `can:rates.manage`. ⚠️ **`rates.view` WITHOUT `rates.manage` is a
real role here (Reservation Agent)** — the documented #30 separation of duties — so unlike Billing's 403 this
page has a genuine READ-ONLY state, and `OperatorRatesTest` proves it.

⚠️ **PREMISE CORRECTED — the #6 guardrail copy is NOT on this page, and must not be added to it.**
The brief assumed the honest #6 copy lives here. Verified: `grep` for guardrail/floor/ceiling/proposal across
this view returns **nothing**. The **#6 guardrail UI and its honest copy live on the OWNER Rates page**
(`resources/views/livewire/owner/rates.blade.php`, whose own comment records that *"the operator's direct rate
write (RateActionController) never consults the guardrail"*), and the operator-side rate-proposal controls live
on **Owner Detail**. Confirmed at the source: `RateProposalService` rejects below-floor / above-ceiling and
auto-approves within tolerance; `RateActionController` contains **zero** guardrail references.
**So on this page #6 means: do NOT add a guardrail, "protected" or "within band" claim of any kind** — this IS
the unguarded direct-write path. Verified on the rendered page after the changes: **0 guardrail words.**

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D18-1 | A **"Smart pricing · Off"** toggle in the control row, and a **demand curve** behind the grid. | No pricing engine of any kind exists. | A real demand/pricing model. | **RATIFIED REFUSAL** (sweep §5: no smart-pricing / generated suggestions). The component docblock already records the design's curve as a `sin()` function. ⚠️ **The REAL `RateProposalService` operator↔owner proposal workflow is a different thing and is kept** — refuse the fabricated algorithmic suggestion, not the real approval flow. |
| D18-2 | **"+8% vs Aug"** on the avg-rate tile, **"pacing ahead"** on booked, and **"+9% vs last year"** on the pacing card. | **No historical rate snapshot table exists anywhere**, so no baseline can be computed. | A rate/occupancy snapshot history. ⚠️ Note this is the same class as **D3-4** on Insights, where a comparison WAS rendered off a thin availability spine — the lesson is that a trend needs real coverage, not just a prior row. | **RATIFIED REFUSAL** — a trend without a baseline is a fabricated figure (#39). Verified absent from the rendered page. |
| D18-3 | A **channel-sync ribbon** ("All changes pushed · 4 channels in sync · Last synced just now · selling live") and **per-channel Synced pills** for Booking.com / Airbnb / Direct / Expedia. | `channels.status` is written by no application code outside a test factory, so Synced/Syncing/Queued are dead enums. The live Channels card shows the **real** state — "1 live · OTA sync needs setup", Direct **Live**, Booking.com (demo) **Connected** — and says live OTA sync activates once the connection is set up. | Live sync arrives with the **Channex** cutover. | **ACCOUNT-GATED / RATIFIED REFUSAL — CHANNEX-TIED.** Verified: the page contains **no** "All synced"/"Everything synced". **Do not fabricate a sync state or a last-synced time.** |
| D18-4 | Per-rate-plan channel pills — **"All channels"** vs **"Direct only"**. | **No rate_plan ↔ channel model exists**; every plan honestly renders **Direct**. | A rate-plan/channel distribution model, which only means anything once Channex is live. | **ACCOUNT-GATED — CHANNEX-TIED**, and the same unmodelled attribution as **D2-2** (0 of 40 reservations carry a `channel_id`). ⚠️ **Do not invent a per-channel rate or an OTA-specific override.** Verified: 0 "All channels"/"Direct only" pills on the page. |
| D18-5 | The grid rows are **physical units** — "Villa Azure · 2-bed · sleeps 4", "Apt 101". | The live rows are **ROOM TYPES** ("Double Room · 3 units · Flexible"). ⚠️ Not an oversight: `rate_calendar` is keyed on `rate_plan_id` and a rate plan belongs to a **room type**, so a per-unit grid could only be backed by silently writing every unit under a type. | Per-unit pricing would be a real modelling change (a rate plan per unit), with a genuine product question attached: should two units of the same type be priceable apart? | **REAL GAP / product decision** — documented as STRUCT-1 in `docs/WAVE4-INVENTORY.md`. The live sub-label states how many physical units a row touches, so the operator is never misled about what a save covers. **Not started.** |
| D18-6 | Three visually distinct rate plans (Base · flexible / Non-refundable −10% / Weekly −15%). | ⚠️ **The live list renders "Flexible" TWICE with identical text.** Verified as REAL data, not a display duplicate: there are genuinely two active Flexible plans in scope, one per room type (Double Room, One-Bedroom Apartment). `ratePlanList()` returns only `name`/`sub`/`refundable`/`is_derived`/`channel` — **the room type is not in the payload**, so two different plans are indistinguishable on screen. | Carrying the plan's room type into the payload (`with('roomType')` + a field) and rendering it as the row's sub-label. Small and purely additive. | **REAL GAP** — a genuine clarity defect. ⚠️ **Not built here on purpose:** it extends the component's read payload, which is the same line drawn at **D16-1** on Payout Run. Logged for the backend phase. |

### Also noted on this page (not backend gaps)

- ⚠️ **#6 — NOTHING was added.** No guardrail, floor, ceiling, "protected" or "within band" claim appears on
  this direct-write surface, and none was introduced. The guardrail is **not enforced** on this path and the
  page makes no claim that it is. **Enforcement remains the founder's deferred call (G.2).**
- **The rate engines were NOT touched.** `RateAndRestrictionService` and `RateProposalService` are unchanged;
  both writes still POST their real `can:rates.manage` routes with CSRF (`/app/rates`, `/app/rates/bulk`).
  **Restyled, not rewired — and nothing was submitted:** the Bulk-edit panel was opened READ-ONLY, because a
  real rate write mutates rates and can ripple into folio pricing.
- **#17 / #39 — the figures are real.** Avg nightly rate **€142.85** is a real mean of set rates **per
  currency** (never summed across); Booked 21% is sold ÷ published inventory; Open nights 36 is real
  unit-nights. Rates in the grid are real `rate_calendar` values (€100.00 / €180.00 / €140.00 / €220.00), and
  a date with **no rate set renders "—"**, never an invented price — the WAVE-3 invented-arrival-hour lesson.
- **#38** — the grid resolves through `currentReadIds` (the property read-set). A third "Flexible" plan exists
  on **City Studio** in another property and correctly does **not** appear.
- **#28** — units, dates and prices only; **0 email addresses**, no guest contact anywhere.
- **#20** — the window nav, the room-type filter and the jump link all ride **GET links**, unchanged.
- **WCAG 1.4.1** — every legend swatch is followed by its **word** (Open / Booked / Weekend), and a full cell
  prints "Full" as text, so no grid state is carried by colour alone.
- **The grid scrolls inside its own `overflow-x: auto` container** (table 1305px in a narrower wrapper) — all
  14 day columns render; the un-scrolled screenshot simply shows the first nine. **Not a defect.**
- **Reviewed and KEPT, worth recording because it is borderline:** the pacing card's *"36 open nights —
  consider a promotion."* It is a plain observation derived from a real figure, with no invented specifics —
  materially different from the design's *"Weekends nearly sold out — the +15% uplift is holding. Softest
  nights: Wed 17 & Wed 24 Sep."*, which is a generated narrative with fabricated detail and is **refused**.
- **Fixed (1) — the page title wrapped onto two lines.** MEASURED: `.vy-topbar` is a space-between flex row
  and BOTH sides were `flex: 0 1 auto`, so the actions cluster (search 227 + date chip 174 + bell 38 +
  account 165 ≈ 639px) won and squeezed the lead to **255px**, breaking **"Rates & availability"** across two
  lines (255×81 instead of ~259×35). It is the longest operator title, which is why no earlier page showed it.
  Fixed with `white-space: nowrap` + a growing lead: one line needs ~259px and the actions' min-content is
  ~623px = 898 of the 910 available. Now **1 line at 259px — exactly the prototype's width** — and still 1
  line with 0 overflow at 1000px.
- **Fixed (2) — the legend and Bulk edit moved into the CONTROL ROW**, where the design carries them; they sat
  on a separate line below the stat tiles. ⚠️ Bulk edit is an Alpine action, so the `x-data="ratesEditor()"`
  scope was lifted to open above the toolbar; the editor itself is otherwise untouched.
- ⚠️ **A test was STRENGTHENED because a gated control moved.** `OperatorRatesTest`'s read-only case asserted
  only that the *editor* is hidden from a `rates.view`-only role; **Bulk edit is the second `rates.manage`
  control and was not covered.** Since this gate relocated it, the test now also asserts
  `assertDontSee('Bulk edit')` for a Reservation Agent and `assertSee('Bulk edit')` for a Hotel Manager —
  **proven to discriminate** by removing the `@can` wrapper (RED) and restoring it (green). A relocated control
  that quietly loses its gate is exactly the #30 "the UI invites a 403" defect.
- **Responsive:** 0px horizontal overflow at **1440** and **1000**; the toolbar wraps, the grid keeps its own
  scroll container. **0 console errors/warnings.** Contrast on the new/affected elements **5.48–12.09** (AA).

---

## 19 · Operator New Reservation — `/app/reservations/create` ⚠️ MONEY + INVENTORY

**Prototype:** `docs/design/raw/Operator New Reservation.html` (decoded fresh 2026-08-10) — a **7-step wizard**
in a focused full-screen layout. **Live:** `App\Livewire\Operator\NewReservation` →
`resources/views/livewire/operator/new-reservation.blade.php` — **also a 7-step wizard in the same layout**,
so this page was already close to parity.
**Role:** `can:reservations.create` on **both** the GET and the POST — held by Super Admin / Property Owner /
Group Admin / Hotel Manager / Front Desk / Reservation Agent (i.e. every front-of-house role). A role without
it gets a clean **403** at the route, so #30 needs no separate control-hiding here.

⚠️ **THE CREATE WAS NEVER SUBMITTED.** The whole flow was verified by **inspection**: reading the bound form
action, flipping the Alpine `step` client-side, and selecting a unit via its own change handler. Nothing was
POSTed. **Proven, not asserted** — the five counts are identical before and after:
`reservations 42 · reservation_units 42 · guests 10 · folios 20 · folio_items 40`.
*(The draft-restore is `localStorage` only — `vyrobel:new-reservation:draft`, cleared on confirm — so "Start
over" and step changes persist nothing server-side. `calApply()` is a **GET** navigation with `?check_in=…`,
a pure read and #20-compliant.)*

### The three documented guards — all re-verified end to end

- **GUARD 1 · honest availability.** For 20–23 Aug the picker offered exactly **2** units (103, A1). Traced
  every exclusion to a real cause: **101 is `out_of_service`** and is dropped at source by
  `Unit::query()->bookable()`; **102 and A2 each have a real overlapping `inventory_block`**. No OOS, paused
  or blocked unit is ever offered. An unpriced room type renders *"N free — no rate set. Set a rate first →"*
  linking to the real rates page, rather than an unbookable option.
- **GUARD 2 · PII.** The guest search is **name-only** — verified 0 email addresses render anywhere on the
  page. `guests.email`/`phone` are encrypted casts, never echoed back.
- **GUARD 3 · no raw PAN.** **0 card/PAN/CVV fields** in the DOM. Payment is Nothing / Deposit 30% / Full,
  recorded as a manual folio payment; the copy states *"no card number is entered or stored here"*.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D19-1 | The stay dates as **two compact fields** — `CHECK-IN Thu 18 Sep → CHECK-OUT Sun 21 Sep` with a **"3 nights"** pill — implying a popover picker. | The live step 1 uses a **dual-month inline calendar** (~450px tall) with drag-range selection and a "Check availability" apply. The nights figure IS shown, in the row beneath (`20 Aug 2026 → 23 Aug 2026 · 3 nights`) and in the calendar footer. | Collapsing the calendar into two compact fields + a popover. ⚠️ It is an **interaction-model change, not a restyle**: the apply step is a real server read that re-derives availability, and the calendar is what makes the range draggable. | **REAL GAP (presentation-shaped) — deliberately NOT done in a styling pass.** The information is all present; the container differs. Deserves its own gate. |
| D19-2 | A **"Draft saved"** pill (green, always-on) in the header. | The live pill reads **"Draft restored"** and appears only when a draft actually was restored. | — | **EXTRA / HONEST-BY-DESIGN.** ⚠️ The design's always-on "Draft saved" would imply server-side persistence; the draft is `localStorage` only and the view comment says so. **Do not change it to "Draft saved".** |
| D19-3 | A per-unit **"1 left"** scarcity badge (Apt 202). | The live rows carry `Available` state from the real availability read; there is no remaining-count badge per unit. | Exposing the room type's real remaining count for the window onto the row. The figure exists (`Availability::available()` drives Level A) — it is just not carried to the unit row. | **REAL GAP** (small, and genuinely backed). ⚠️ It must come from the real availability count — a fabricated "1 left" is a scarcity claim, which is worse than a fabricated figure. |
| D19-4 | The booking summary itemised as **Accommodation / Extras / City tax / VAT (10%) / Total**, plus a **"€410.00 deposit · €955.00 at check-in"** split line. | The live summary is Accommodation / Extras / Taxes / Estimated total — **taxes are not split into named components**, and there is no deposit-split line in the summary (the deposit choice lives on step 7). | Splitting the tax line per configured `Tax` row (the modes are real: flat / per-night / per-person-night / percentage), and mirroring the chosen deposit into the summary. | **REAL GAP** (small). The current single "Taxes" line is honest and reconciles; the split is a presentation refinement over data that already exists. |

### Also noted on this page (not backend gaps)

- **#17 RECONCILES — measured on a real selection, not assumed.** With unit 103 over 20–23 Aug:
  `roomMinor 30000 + extras 0 + tax 8400 = 38400` = the rendered **Accommodation €300.00 + Taxes €84.00 =
  Estimated total €384.00**, in integer minor units throughout.
- ⚠️ **Fixed (1) — "€0.00" where nothing was priced.** Before a unit is chosen the summary showed
  **Accommodation €0.00 / Estimated total €0.00**, and step 7 showed **Deposit · 30% €0.00 / Full €0.00**.
  A zero price is a *claim*; nothing was priced yet. All four now render **"—"** until `unitId` is set, which
  is the same distinction the Payout Run stat strip makes. Once a unit IS chosen the real figures render,
  including a genuine zero.
- ⚠️ **Fixed (2) — a follow-on the first fix EXPOSED.** With Accommodation and Total showing "—", the
  **Taxes line still rendered €15.00**, because flat / per-night taxes compute from the DATES alone — a tax on
  a stay with no accommodation and no total. The Extras / credit / Taxes lines are now gated on `unitId` too,
  so until a unit is picked no *component* of a price is stated either. **Found by looking at the rendered
  result of my own change rather than assuming it was complete.**
- **Fixed (3) — the design's "Check the rack ›" link** beside the availability heading, pointing at the REAL
  calendar route and opening in a new tab so an in-progress draft is not discarded.
- **WAVE-3 lesson holds — no invented default.** `arrival_window` carries a **placeholder** (`e.g. 3–6 PM`),
  not a value; there is no fabricated "From 15:00". Adults defaults to 2 and children to 0, which are party
  size, not property policy.
- **#28 permitted but NOT widened.** A create flow legitimately collects contact: first/last name, email,
  phone, country, note, VIP, marketing consent. ⚠️ **No passport / ID / DOB / nationality field was added** —
  verified 0 such fields. `guest_note` is free text ABOUT a guest and the controller annotates it
  `#28 — in anonymize()`; `Guest::anonymize()` covers it along with `notes.body` and thread notes.
- **#38 / #20** — the whole page resolves through `currentReadIds`; the availability re-check rides a **GET**.
- **ONE form, one submit.** The page has exactly one `<form>`: **POST `/app/reservations/create`** with CSRF
  and a single **"Confirm reservation"** button — the real gated route. **Read, never fired.**
- **Honest empty states throughout:** "Choose dates above to see what's available." · "No units are free for
  those dates. Try a different range." · "N free — no rate set. Set a rate first →" · "No guests match … Switch
  to New guest to add them." · "No extras configured for this property." · "Payment is taken later from the
  reservation's folio."
- ⚠️ **CONTRAST — measured, and deliberately NOT changed.** This page has 8 inline `color: var(--vy-accent)`
  sites. Four render in the current state and **all pass AA on their real composited backgrounds**
  (`.vyn-rt-price` 4.63 · `.vy-nr-counter button` 5.10 · `.vy-cal-nav` 5.09 · `.vy-cal-day.is-weekend` 4.87) —
  they sit on near-white card interiors, not the darker glass. Three more are accent **backgrounds** with
  white text, and one is a tinted avatar chip. **Nothing was changed**, which is the P14 rule working as
  intended: each site needs its own measurement, never a blanket replace. ⚠️ One site — the
  `.vyn-unpriced a` "Set a rate first →" link — **could not be measured** because no unpriced room type exists
  in the current demo state; left untouched rather than changed blind.
- **Responsive:** 0px horizontal overflow at **1440** and **1000**; the 7-step stepper fits. **0 console
  errors/warnings.**

---

## 20 · Operator Group Booking — `/app/group-booking` ⚠️ MONEY + INVENTORY, MULTI-UNIT

**Prototype:** `docs/design/raw/Operator Group Booking.html` (decoded fresh 2026-08-10) — a **5-step wizard**
(Block · Units · Organiser · Terms · Review) in the focused full-screen layout. **Live:**
`App\Livewire\Operator\GroupBooking` → `resources/views/livewire/operator/group-booking.blade.php` — **also a
5-step wizard with the same step names**, so it was already close.
**Role:** `can:reservations.create` on **both** verbs — the same gate as a single booking; an unpermitted role
gets a clean **403** at the route. The write goes POST → `GroupBookingController` → `GroupBookingService::create()`
→ **`AssignmentService::createGroupBooking()` (atomic, all-or-nothing)**.

⚠️ **THE BLOCK WAS NEVER SUBMITTED.** Verified by inspection: reading the bound form action, and driving the
Alpine wizard's own `toggleUnit()` client-side. **Proven, not asserted** — identical before and after:
`reservations 42 · reservation_units 42 · guests 10 · folios 20 · folio_items 40 · groups 0`.
*(`reservation_units` is the one that matters here — a block would add several at once.)*

### The multi-unit guarantees — verified, not assumed

- **Availability is real and #38-scoped.** For 20–23 Aug the group picker offered exactly **2** units
  (103, A1) — **the identical set New Reservation offers**, and every exclusion traces to a real cause:
  **101 is `out_of_service`**, **102 and A2 each hold a real overlapping `inventory_block`**. No OOS, paused
  or blocked unit is ever offered.
- **No intra-group double-book — structurally impossible, and proven.** Units render as **checkboxes** (one
  row per unit), and `toggleUnit()` guards with `if (! this.selected.some(x => x.id === u.id))`. Driving it to
  add unit 103 **twice** left exactly **one** entry in `selected`.
- **The group price reconciles across units (#17), in integer minor units.** With 103 (€300.00) + A1
  (€540.00): per-unit sum **84000** = `blockSubMinor` **84000** = `afterDiscMinor` **84000**. With a real 10%
  group discount: `discountMinor` **8400**, `afterDiscMinor` **75600** = 84000 − 8400.
- **Server-side re-derivation.** The controller re-derives every price, and the discount/deposit percentages
  come from **fixed sets** ({0,5,10,15} and {0,25,50,100}) — a crafted value collapses to 0, so a client
  cannot inject a price, a discount, or a taken unit.

⚠️ **The prototype's "Group discount (10%)" and "Deposit €266.03 (25%)" are NOT refusals — both are REAL.**
`discount_pct` posts a genuine negative `discount` folio line that also **lowers the taxable base** (so
percentage taxes fall on room − discount — exactly the design's VAT-on-after-discount), and `deposit_pct` is
master-invoice only. They simply do not appear on step 1 because nothing is selected yet.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D20-1 | Stay dates as **one combined field** — `CHECK-IN → CHECK-OUT / 18 Sep – 21 Sep` with a **"3 nights"** count. | The live step 1 uses **two native `dd/mm` date inputs** plus a "Check availability" button and a "Pick the block dates" hint; the nights count is not shown on this step. | A styled range control plus a nights readout. ⚠️ Same shape as **D19-1** on New Reservation, and the two wizards **disagree with each other** — New Reservation uses a full dual-month calendar here, Group Booking uses native inputs. A single shared date-range control would fix the design gap and the inconsistency together. | **REAL GAP** — logged rather than built because it is an interaction control shared by two wizards, which deserves its own gate rather than a third variant. |
| D20-2 | A **block summary identity card** — an avatar tile with the group name and *"Wedding · 14 guests"*, then dates + nights and *"2 units · Sleeps 10"*. | The live rail shows the units/sleeps line and the money, but no name/occasion identity block — the group name and occasion are captured on step 1 and never mirrored into the rail. | Mirroring the already-captured `name` / `occasion` / `headcount` into the rail. Purely presentational over Alpine state that already exists. | **REAL GAP** (small). Deliberately not built in this gate, which was already carrying a shared-component consolidation. |
| D20-3 | The stay-dates block implies an inline **occasion → package** relationship (Wedding / Corporate retreat / …) driving pricing. | Occasion is stored as a real free label on the group; it drives **nothing** — there is no package or occasion-based rate model. | An occasion→package/rate model. | **RATIFIED REFUSAL for any pricing implication.** The live chips set a real label and make no pricing claim. **Do not wire occasion to a rate or imply a package discount** — the group discount is its own explicit, real control. |

### Also noted on this page (not backend gaps)

- ⚠️ **Fixed (1) — "€0.00" where nothing was priced, and its follow-on, handled together.** The block summary
  showed **Accommodation €0.00 / Block total €0.00** and *"0 units · sleeps 0"* before any unit was chosen.
  All now render **"—"** and *"No units selected yet"* until `count > 0`. ⚠️ **The discount and deposit lines
  are gated on `count` too, not just on their own percentage** — both are a % OF the accommodation, so a
  percentage chosen on a later step and then revisited with an empty block would have rendered *"Group
  discount (10%) −€0.00"* against an Accommodation of "—". That is precisely the follow-on the New Reservation
  gate uncovered, applied here **up front rather than after the fact**.
- ⚠️ **Fixed (2) — the stepper was PROMOTED to one shared definition rather than copied.** The design draws the
  same panel stepper on both operator wizards: circular nodes joined by connector bars, labels beneath. New
  Reservation already had it (prototype-exact) as `.vy-nr-*`; Group Booking had a **compact variant matching
  neither its own design nor its sibling**. Restyling the group one would have manufactured a **second copy of
  ~15 rules for one concept** — the exact drift this project keeps consolidating — so the rule moved into the
  operator layout once as **`.vy-wiz-*`** and both wizards now point at it (16 + 10 page-local rules deleted).
  ⚠️ **New Reservation was re-verified as unchanged by the rename** — same padding `18px 26px`, radius 20px,
  7 nodes, 14 bars, 34×34 circles, active ring present.
- **WAVE-3 holds — no invented default.** The rooming inputs carry a **placeholder** (*"Lead guest — TBC"*),
  the group-name field a placeholder (*"e.g. Okafor–Bello Wedding"*); headcount defaults to 10 (party size,
  not property policy). **0 invented check-in hours.**
- **#28 permitted but NOT widened.** The organiser block collects first/last/company/email/phone — legitimate
  lead-guest contact. The **rooming list is NAME-ONLY** per unit (`rooming[<unit_id>]` → a real
  `lead_guest_id`, falling back to the organiser), i.e. genuinely modelled. ⚠️ **No per-occupant passport / ID
  / DOB / nationality field exists or was added** (verified 0). **0 email addresses render** on the page.
- **No channel and no allotment concept** — verified 0 occurrences of Booking.com/Airbnb/Expedia/"channel" and
  0 of allotment/release/hold-inventory. A block here is real units held as real reservations, not an
  unmodelled allotment. *(A channel source would be the D2-2 Channex-tied attribution.)*
- **ONE form, one submit:** **POST `/app/group-booking`** with CSRF and a single **"Confirm block"** button —
  the real gated route. **Read, never fired.** `AssignmentService`, `UnitAvailabilityService`,
  `GroupBookingService` and `FolioService` are untouched.
- **#20** — the dates ride a **GET** (`?check_in=/?check_out=`), re-run through the tenant middleware; steps
  2–5 are client-side Alpine over units this render already scoped.
- **Responsive:** 0px horizontal overflow at **1440** and **1000**; the 5-node stepper fits. **0 console
  errors/warnings.** Contrast on the shared stepper label and the rail **5.33–14.18** (AA).

---

## 21 · Operator Owners — LIST — `/app/owners` ⚠️ OWNER PII + ISOLATION

**Prototype:** `docs/design/raw/Operator Owners.html` (decoded fresh 2026-08-10; it opens on its **Add owner**
modal — the base list is behind the scrim). ⚠️ **`Operator Owner Detail.html` is a SEPARATE prototype and is
NOT covered here — the detail page is the next gate.** **Live:** `App\Livewire\Operator\OperatorOwners` →
`resources/views/livewire/operator/owners.blade.php`.
**Role:** ⚠️ the **GET carries no `can:` gate** (back-office + active subscription only — any operator role may
SEE the owner list); every **write** is `can:users.manage` (invite / update / pause-payouts / note / remove).
The invite modal is gated **as a whole**, not just its button — *"a form an unpermitted user could never submit
has no business in their DOM"*.

### ⚠️ THE OWNER-PII PERIMETER — MEASURED BEFORE AND AFTER, UNCHANGED

| Probe | BEFORE | AFTER |
|---|---|---|
| Owner email addresses | **0** | **0** |
| Phone numbers | **0** | **0** |
| Raw IBAN (`XX00…`) | **0** | **0** |
| Masked IBAN (`····1000`) | **0** | **0** |
| Tax / VAT / SSN / reg numbers | **0** | **0** |
| Postal addresses | **0** | **0** |
| Money figures | `€0.00`, `€885.60` | `€0.00`, `€885.60` |
| Commission % | `18%` | `18%` |

**No field was added, nothing is decrypted that wasn't, and no guest PII appears.** The list is **name + money
only** — and so is the prototype: the design shows no owner email, phone or IBAN either (it refers to *"no
verified IBAN"* as a **status**, never a number). The perimeter already matched the design.
⚠️ **One conditional exception, verified and left exactly as-is:** the *Pending invites* card renders the
**invitee's email**, which is legitimate — an invite is addressed to someone (the same reasoning P14 recorded
for Settings). It shows 0 today only because there are no pending invites.

### ⚠️ #17 — "€0.00" HERE IS A REAL COMPUTED ZERO, AND WAS DELIBERATELY **NOT** CHANGED

The last three gates all converted a placeholder `€0.00` to `"—"`. **This page is the opposite case and the
distinction matters.** Net payable is *"the current period's finalized-but-not-yet-paid net — what a run would
send"*. Demo Owner's statement (**seq 6, €885.60, finalized**) is **on hold** because the owner has **no tax
details** (`hasTaxDetails() === false` — the real tax-hold), so what a run would send genuinely **is €0.00**,
and the page says why: *"1 hold · 0 draft"*, *"0 owners scheduled · 1 on hold"*, and the row's **On hold** pill
beside its real €885.60. Converting that to `"—"` would hide a real figure — and would re-break precisely the
**WAVE-1 HIGH-3** defect this page was already fixed for (`6312df2`, *"judge payable state per statement, not
per owner"*, whose comment records the KPI once wrongly *"collapsed to 0"*). **A zero that is computed is not a
zero that is missing.**

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D21-1 | An **"All owners ▾" filter** on the statements card, and the list *"sorted by net payable"*. | The live card is titled *"Statements · all owners"* with **no filter control**, and the footer says *"sorted by name"*. | A `?owner=` / `?sort=` GET parameter through the component's query. ⚠️ It must ride a **GET link** (#20) like every other scope-dependent read on `/app`. | **REAL GAP** — small, with an established pattern to copy, but it changes the component's query, so logged rather than built. |
| D21-2 | Each statement row carries **two** states — a statement status pill (**Finalized** / **Draft**) *and* a separate pay-status line (**Scheduled** / **Awaits finalize** / **On hold · no IBAN**). | The live row merges them into a single pill (**On hold**). Both facts are real and already computed — the component judges statement status and pay-state separately (the WAVE-1 HIGH-3 fix). | Surfacing the statement status alongside the pay-state in the row payload. Genuinely backed; purely a payload + markup change. | **REAL GAP** (small). ⚠️ Worth doing precisely because the two states can disagree — that disagreement *was* the WAVE-1 defect. |
| D21-3 | **"Preview the owner portal ›"** beside the invite-welcome link. | There is **no operator route into the owner realm** — `routes/web.php` records *"No impersonation (deferred)"*. | Impersonation / a read-only owner-portal preview — a real auth-boundary feature, not a link. | **RATIFIED REFUSAL / DEFERRED.** ⚠️ **Not reproduced.** A link that goes nowhere, or that implies an operator can enter the owner realm, would misstate the product's auth boundary. The sibling **"See the invite welcome flow →"** IS kept, because `app.owners.invite.preview` is a real route. |

### Also noted on this page (not backend gaps)

- **#42 / #38 isolation — and a REAL COVERAGE GAP CLOSED.** The list is tenant-scoped (`Owner` is
  `BelongsToOrganization`), and **scope survives `?page=2`** (verified: still one org's owner, no foreign row,
  still 0 emails / 0 IBANs). ⚠️ `OperatorOwnerDetailViewTest` already proved *"an owner in another organization
  404s"* for the DETAIL page — **the LIST had no such assertion**, and the list is the surface that
  **enumerates** owners, where a scope slip leaks the existence and net-payable of every owner in another
  tenant at once. `OperatorOwnersListTest` now asserts a foreign org's owner never appears, **proven to
  discriminate**: forcing `Owner::query()->withoutGlobalScopes()` turns it RED, restoring turns it green.
  *(The demo has only one org, so this is invisible to a browser walkthrough — the same latent shape as the
  auth-pass findings.)*
- **The mock does NOT leak.** The prototype's **15% commission** is absent — the page shows the real **18%**
  from `OwnerCommissionRate` (and `OperatorOwnersListTest` already asserts `assertDontSee('15% of gross')`).
  None of the design's invented amounts (€96,400 / €18,240 / …) appear.
- ⚠️ **The #6 rate-proposal controls are NOT on this page** — verified (0 occurrences of "propos"). They live
  on **Owner Detail**, which is the next gate; that is where the *guarded* proposal path (floor / ceiling /
  tolerance enforced by `RateProposalService`) will need its copy checked for honesty.
- **Writes restyled, not rewired:** the only page write is **POST `/app/owners/invite`** with CSRF
  ("Send invite"), behind `can:users.manage` + a throttle. **Nothing was submitted.**
- **Fixed (1) — the KPI strip was a FOUR-column grid carrying a fourth tile the design does not have.** The
  design has **three** tiles (Owners / Net payable / Needs attention) and gives owner-portal adoption its own
  rail card. The strip now takes `--3` and spans the content width. *(Same family as the P16 Payout Run
  finding, but the opposite cause: there a 3-tile strip sat in a 4-column grid; here a genuine 4th tile was
  removed to match the design.)*
- **Fixed (2) — the design's dedicated Owner-portal rail card**, with a **real progress bar** (active ÷ total
  owners) and the real pending-invite count, replacing a one-line row inside the schedule card. ⚠️ The bar
  carries an **`aria-label` text equivalent**, so adoption is never conveyed by bar length alone.
- **#39** — every figure is real: owners, units managed, net payable, needs-attention (`1 hold · 0 draft`),
  portal adoption, and the 18% commission. Status is never colour-only — **On hold** prints as a word.
- **Responsive:** 0px horizontal overflow at **1440** and **1000** (the KPI strip reflows to 2 columns).
  **0 console errors/warnings.** Contrast on the new card **5.49–6.24** (AA).

---

## 22 · Operator Owner DETAIL — `/app/owners/{id}` ⚠️ DEEPEST OWNER PII + THE #6 GUARDED PROPOSAL

**Prototype:** `docs/design/raw/Operator Owner Detail.html` (decoded fresh 2026-08-10).
**Live:** `App\Livewire\Operator\OperatorOwnerDetail` → `resources/views/livewire/operator/owner-detail.blade.php`,
reviewed on the real seeded owner (2 units, **6 statements**, 6 payouts).
**Role:** the **GET carries no `can:` gate** (as with the list); the writes are `can:users.manage`
(update / note / pause-payouts / remove) and **`can:rates.manage`** (the rate proposal).
**⬅ With this page the OWNERS AREA IS COMPLETE (list §21 + detail §22).**

### ⚠️ THE OWNER-PII PERIMETER — MEASURED BEFORE AND AFTER, UNCHANGED

| Probe | BEFORE | AFTER |
|---|---|---|
| Owner email | **1** — `demo-owner@vyrobel-demo.test` (the owner's own) | **1** — identical |
| Phone | **0** (the column is genuinely NULL → renders *"Not set"*) | **0** |
| **Raw IBAN** | **0** | **0** |
| Masked IBAN | **1** — `···· 1000` | **1** — identical |
| BIC / bank name | **0** | **0** |
| Tax / VAT / SSN / reg | **0** | **0** |
| Postal address · DOB | **0** · **0** | **0** · **0** |

**No field added, nothing decrypted that wasn't, no guest PII.** The owner's own name/email/phone are rendered
by design — the component's triage records why: *"the operator's own CRM record for a business counterparty
THEY invited"* — while **`tax_id` (encrypted) and the raw `address` JSON are never read at all**.

⚠️ **THE IBAN IS STRUCTURALLY SAFE, verified at the source rather than by eye.** `Iban::masked(string $last4)`
takes **only the last-4 string** and returns `'···· '.$last4` — it *cannot* emit a full IBAN, and the encrypted
`payout_methods.iban` column is never read. It is additionally gated on **`payouts.manage`** (the Payout Run
bar). ⚠️ **The design's `Revolut Business · IBAN ····4821 · verified` is refused twice over:** the bank NAME is
unmodelled, and **"verified" would be fabricated** — the Payout Run gate established there is no verification
column. The live line is the honest `IBAN ···· 1000 · on file`.

⚠️ **A STALE DOCBLOCK, corrected here (not a defect).** `OperatorOwnerDetail`'s triage still says *"IBAN …
NO bank-account field exists anywhere in the schema. Never rendered."* That predates the owner payout-method
work (**#58**); a masked IBAN **is** rendered now, correctly and capability-gated. Same class as the Payout Run
docblock drift found at §16 — **the code is right, the comment is out of date.**

### ⚠️ THE #6 GUARDED PROPOSAL PATH — and the finding that matters

The propose control POSTs the real guarded route **`POST /app/rate-proposals`** (CSRF, `can:rates.manage`,
throttled) → `RateProposalService`, which rejects below-floor / above-ceiling and auto-approves within
tolerance. **`RateProposalService` is byte-identical — untouched.** The modal was opened **READ-ONLY** and
**nothing was submitted** (`rate_proposals` count **0** after, notes **0**).

**The copy is honest as it stands:** the control reads *"Propose a change"*, the field *"Proposed nightly
rate"*, the submit *"Send proposal"* — it never implies an instant apply.

⚠️ **AND THE PAGE'S SILENCE ABOUT THE GUARDRAIL IS CORRECT, NOT A GAP — this is the subtle one.**
`RateProposalService` enforces floor/ceiling/tolerance **only when a `RateGuardrail` row exists for that
owner**. **This owner has none** (verified: 0 rows), so for them the bounds **do not bind at all**. Adding the
kind of guardrail copy the direct-write Rates page correctly omits would, here, assert limits that do not
exist for this owner. **If bounds copy is ever added it must render ONLY when a real guardrail row exists, and
must show that owner's REAL floor/ceiling — never a generic "within your owner's limits".**

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D22-1 | **"View her portal ›"** as the header's primary action. | There is **no operator route into the owner realm** — `routes/web.php` records *"No impersonation (deferred)"*. | Impersonation or a read-only owner-portal view — a real auth-boundary feature. | **RATIFIED REFUSAL / DEFERRED** — the same refusal as **D21-3** on the list. A link that goes nowhere, or that implies an operator can enter the owner realm, misstates the auth boundary. |
| D22-2 | **"Her units · September"** — per-unit rows with an occupancy **progress bar**, an occupancy %, and per-unit revenue (Villa Azure 82% · €4,120). | The live Units card lists code / type / sleeps only. Per-unit occupancy and revenue are **not** in this component's payload — although `PerformanceMetrics::forOwnerUnits` already powers the page's portfolio-level occupancy, and `OwnerUnitAnalytics` exists for the owner realm. | Per-unit metrics threaded into this payload (the shared analytics helpers already compute them for the owner's own pages — reuse, never a second definition). | **REAL GAP** — genuinely backed and reusable; a payload change, so logged not built. |
| D22-3 | An **Email** and a **Message** button under the contact block. | No operator→owner messaging entry point from here. An owner↔operator thread is real (`message_threads.owner_id`), but reaching **this** owner's thread would need a deep link that does not exist, or a thread-start **write** (`app.inbox.start`). | A deep link to the owner's existing thread, or a gated start-thread action. | **SPLIT — Email is now REAL (a `mailto:` on the address already displayed, rendered only when one exists); Message is a REAL GAP, deliberately not faked.** A button that opens an unrelated inbox would misstate what it does. |
| D22-4 | An **"Edit owner" modal** (commission stepper + portal-access / statement-PDF / pause-payouts toggles), a **"Remove owner"** type-to-confirm, a **"Next payout · est. 3 Nov"** date, and **"owner since 2023"**. | The component's own triage records each as omitted: the toggles have **no columns**, an **estimated payout date has no column** (a payout date is real only once `paid_at` is set), and the design's own modal is broken (it opens on the destructive Remove view with an inert button). ⚠️ *Live does have real `update` / `pause-payouts` / `remove` POSTs — the refusal is the design's unbacked toggles and the fake date, not the actions.* | Columns for the toggles; a scheduled-payout projection for the date. | **RATIFIED REFUSAL** (pre-existing, re-confirmed). ⚠️ **Do not add an estimated payout date** — the Payout Run gate refused the same thing. |

### Also noted on this page (not backend gaps)

- **#17 RECONCILES, MEASURED — three ways.** ⑴ **Every one of the 6 statements** ties exactly:
  `1080−194.40=885.60`, `880−158.40=721.60`, `630−113.40=516.60`, `570−102.60=467.40`, `800−144=656`,
  `540−97.20=442.80` — all at the **real 18%** agreement rate. ⑵ **Σ paid statements = €2,804.40** = the
  **PAID OUT** and **PAID THIS YEAR** tiles; the pending **€885.60** = the **PENDING PAYOUTS** tile. ⑶ The
  **BALANCE €4,653.60** ties to the ledger: Σ `signedMinor()` over the owner's **29 real `owner_ledger` rows**
  = **465360 minor**. The "Net to owner · Sep 2026" card carries its own *"Reconciles to the cent"* badge and
  proves it inline: credited €1,080.00 − commission €194.40 = **€885.60**.
- ⚠️ **The one `€0.00` on the page is a REAL computed zero and was left alone** — it is **OTA cost**, genuinely
  zero because no booking carried a channel cost. Same ruling as the Owners list: *a zero that is computed is
  not a zero that is missing.*
- **The tax-hold is visible and real** — the Sep statement is **Finalized** with a **pending** payout, and the
  Owners list explains the hold (no tax details). Nothing here contradicts it.
- **#42 / #38 isolation holds** — `OperatorOwnerDetailViewTest`'s *"an owner in another organization 404s"*
  still passes (re-run). Everything rendered belongs to this owner in this org. ⚠️ Incidentally confirmed the
  tenant guard is live: querying `RateGuardrail` outside a tenant context **throws
  `TenantContextMissingException`** rather than returning cross-tenant rows.
- **Writes restyled, not rewired** — 5 real gated POSTs (`rate-proposals`, `note`, `update`, `pause-payouts`,
  `remove`), all CSRF-protected. **None submitted.**
- **#39** — statement rows carry their real enum status plus the real `has_unverified` / `locked` badges;
  "Not set" is rendered for a null commission or fee, never "0%". Status is never colour-only (**Active**,
  **Finalized**, **Paid** all print as words).
- **Fixed — the design's contact ACTION ROW**, with **Email** as a genuine `mailto:` on the address already
  shown, rendered only when an address exists. Message deliberately omitted (D22-3).
- **Responsive:** 0px horizontal overflow at **1440** and **1000**. **0 console errors/warnings.** The new
  action button measures **14.72** contrast (AA).

---

## 23 · Operator Roles & permissions — `/app/roles` ⚠️ RBAC / PRIVILEGE

**Prototype:** `docs/design/raw/Operator Roles.html` (decoded fresh 2026-08-10; it opens on its **Delete role**
modal). **Live:** `App\Livewire\Operator\OperatorRoles` →
`resources/views/livewire/operator/operator-roles.blade.php`.
**Role:** ⚠️ **a two-tier gate, and the distinction is load-bearing.** The screen and the **assign/revoke**
writes are `can:users.manage`; the **permission-matrix save is `can:roles.manage`** — held by **Super Admin and
Property Owner ONLY**, *not* Group Admin (who has `users.manage`). Reviewed as `demo-admin` (Property Owner).

### ⚠️ FIXED OR CUSTOM? — BOTH, AND THE ANSWER IS PRECISE

- **The role CATALOGUE is FIXED.** Evidence: the only role routes that exist are `GET /app/roles`,
  `POST /roles/assign`, `POST /roles/revoke`, `POST /roles/permissions` — **there is no create-role or
  delete-role route anywhere.**
- **The permission MATRIX is EDITABLE**, for **non-wildcard back-office roles only**, under `roles.manage`.
  Evidence: `RolePermissionService::editableRoleNames()` returns exactly the ten roles the page lists —
  Group Admin · Hotel Manager · Revenue Manager · Front Desk · Reservation Agent · Housekeeping · Maintenance ·
  Accountant · Marketing Manager · Guest Support Agent. **Super Admin / Property Owner (wildcard `['*']`) and
  the portal roles (External Owner / Guest) are LOCKED and correctly absent** — the service's own note explains
  why: editing a wildcard role would silently convert it to an explicit list (drift) or strip the org's top
  tier (lockout).

### ⚠️ THE MATRIX WAS VERIFIED AGAINST THE REAL RBAC — NOT EYEBALLED

Every rendered switch was extracted and diffed against the real team-scoped `role_has_permissions` pivot for
**Group Admin**:

| | |
|---|---|
| Permissions in the catalogue | **49** (49 switches rendered) |
| Granted in the **database** | **42** |
| Rendered **on** | **42** |
| In the DB but not shown on | **NONE** |
| **Shown on but NOT in the DB** (a fabricated grant) | **NONE** |

**✅ The matrix matches the real RBAC exactly**, and the page's own counters agree (*"42 of 49 on"*,
*"42 permissions granted"*). **Member counts are real too** — checked against `model_has_roles`: Hotel Manager 1,
Housekeeping 1, Accountant 1, everything else 0, exactly as rendered.

### ⚠️ A SELF-ESCALATION TEST GAP — FOUND AND CLOSED

`RolePermissionService::save()` carries four real bounds: **editable-roles-only**, **catalog-only**
(`array_intersect`), **the SUBSET RULE** (*"the actor may only grant a permission they already hold"*), and
**last-admin safety** — all audited (recording *which* permissions changed, never values).
`OperatorRolesMatrixTest` already covered editable-only, last-admin, the `roles.manage` gate and read-only —
**but the SUBSET RULE, the self-escalation guard, had no test at all**, even though it is the most dangerous
bound here: a granted permission applies to **every member of the role at once**, not to one user.

**Closed.** ⚠️ The setup has to build the actor through the **real chain**, because no seeded role holds
`roles.manage` *without* being a wildcard that holds everything: a Property Owner first grants Group Admin
`roles.manage` via a genuine matrix save (legal — the PO holds it), and that Group Admin, who still lacks
`billing.manage`, then tries to hand it out → **refused**, and the test asserts **the capability did not
move** (not merely that a message appeared). **Proven to discriminate**: removing the subset-rule block turns
it RED, restoring turns it green, and `RolePermissionService` is byte-identical afterwards.

*(⚠️ My first draft of this test asserted Group Admin holds `roles.manage`. It does not — my own earlier probe
had already shown `roles.manage=-` for that role. Verify the premise, including your own.)*

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D23-1 | **"New role"** and **"Delete role"** as working controls, the delete modal offering *"MOVE MEMBERS TO"* another role. | No create/delete route exists — the catalogue is fixed by design. | A custom-role model, plus a members-migration step on delete. ⚠️ A real product decision, not a control. | **RATIFIED REFUSAL — and already handled well.** The buttons exist (matching the design's affordance) but open **honest explanations**: *"The role catalogue is fixed — …new roles aren't created here. Instead, edit an existing role's permissions above"* and *"Roles can't be deleted here — …un-toggle its permissions above; to move a person off a role, revoke it from them"*. **No form, no inputs, nothing inert.** Verified in the DOM. |
| D23-2 | Permissions as **friendly capability names grouped by domain** — OPERATIONS / FINANCE / SYSTEM, with rows like *"Manage team & roles"*, *"Run payouts"*, *"Billing & subscription"*. | The live matrix groups by **resource** (ADDONS / AMENITIES / API / …) with terse verbs (View · Manage · Erase), mapping **1:1 onto the real permission keys**. | A curated label map from permission key → human phrase. | **REAL GAP — deliberately NOT taken in this gate, and worth stating why.** ⚠️ On a permissions matrix, **precision beats friendliness**: the design's *"Manage team & roles"* conflates `users.manage` **and** `roles.manage`, which this product deliberately separates (they are exactly the two tiers gating this page). A label that blurs two permissions on the screen that grants them is a correctness risk, not a copy improvement. If it is ever done, each friendly label must map to **exactly one** permission key. |
| D23-3 | The roles rendered as a **card grid** (5 across) with an icon per role, and a **dark** selected-role card in the rail. | The live roles are a vertical list in a narrow column; the selected-role rail card is light. | Nothing — pure layout. | **REAL GAP (presentation)** — logged rather than built because it restructures the page's three-column shape, which deserves its own gate rather than being folded into an RBAC-sensitive change. |

### Also noted on this page (not backend gaps)

- ⚠️ **NOTHING WAS CREATED, ASSIGNED OR TOGGLED.** Baseline and after are identical:
  **roles 14 · permissions 49 · role_has_permissions 250 · model_has_roles 7.** The New-role and Delete-role
  modals were opened read-only; no matrix save, no assign, no revoke was submitted.
- **#30 — the control is gated, not just the route.** The matrix switches bind
  `x-bind:disabled="!canEdit || readOnly || !canToggle(perm.key)"` — a three-way gate where **`canToggle()`
  surfaces the SUBSET RULE in the UI itself**, so an actor never sees a toggle they could not legally flip.
  A viewer without `roles.manage` gets the honest line *"Editing a role's permissions needs the
  organization-admin permission (roles.manage)."* rather than a dead Save button.
- ⚠️ **A subtle correctness guard worth not breaking:** a locked-but-granted permission is preserved on save
  via a hidden input — an actor who *cannot* toggle a permission must not silently **drop** it by saving.
- **The assign form states the rule it enforces:** *"Only roles whose access you already hold are listed — you
  can't grant more than your own."* — the escalation bound, surfaced rather than merely enforced.
- **#28 / #42** — the surface shows **staff only** (name + role + member counts); no guest PII. Existing tests
  cover *"cannot assign a role to a user in another organization"* and *"the assign path never mutates the
  role→permission catalog"*.
- **Fixed — permissions render as the design's SWITCHES** rather than bare checkboxes. The input stays a real
  `input[type=checkbox]`, so the payload, the `x-model` binding and every disabled rule are untouched.
  ⚠️ **RBAC-safe by construction:** the permission label always sits beside the switch (granted vs denied is
  never colour- or position-only, WCAG 1.4.1), and a **locked** row stays visibly distinct from a merely
  **off** row — verified by measurement (opacity 1 → 0.55, cursor `pointer` → `not-allowed`, label
  `rgb(43,35,32)` → `rgb(108,100,95)`). That distinction matters more here than anywhere else in the product.
- **Responsive:** 0px horizontal overflow at **1440** and **1000**. **0 console errors/warnings.** Permission
  label contrast **13.75** (AA).

---

## 24 · Operator Security — `/app/security` ⚠️ AUTH SURFACE

**Prototype:** `docs/design/raw/Operator Security.html` (decoded fresh 2026-08-10). **Live:**
`App\Livewire\Operator\OperatorSecurity` → `resources/views/livewire/operator/operator-security.blade.php`.
**Role:** ⚠️ **per-user, deliberately NOT permission-gated.** The component's own note explains why:
`/app/settings` is `can:properties.manage` (managers only), but **every** back-office user must be able to
change their own password and manage their own 2FA — so this page sits behind `auth` + `EnsureBackOfficeAccess`
(#26) with **no fine-grained permission**, and shows only the acting user's own auth state.
**⬅ This closes the two sections deferred from Page 14 Settings: Billing (§17, `3f86270`) and Security (here).**

### ⚠️ THE FINDING — A HARDCODED SAFETY BADGE THAT SAID "PROTECTED" WITH 2FA OFF

The "Account security" header carried a **green ✓ Protected** badge as **static, unconditional markup** — no
condition of any kind. On this account (**`two_factor_secret` NULL, 0 trusted devices, 0 IP rules**) the page
was therefore telling the user they were *protected* while the strongest control the page offers was **off**.

⚠️ **On an auth page that is a SAFETY CLAIM, not decoration** — it is the same class as the hardcoded
"Verified" badge the finish-to-100 pass removed from Owners, and precisely why the owner realm's equivalent was
built as a **derived checklist, never a score**.

**Fixed:** the badge is now derived from `$twoFactor['state']`, the authoritative `enabled|pending|disabled`
flag — **Protected** / **Finish two-factor setup** / **Two-factor off**, each with its own icon and always
carrying a **word** as well as colour (WCAG 1.4.1). **A test now proves it flips in BOTH directions** (2FA off →
"Two-factor off" and *not* "Protected"; 2FA genuinely enabled → "Protected" and *not* "Two-factor off"), and
that test is **proven to discriminate**: restoring the hardcoded label turns it RED.

⚠️ **AND THE INKS WERE MEASURED, WHICH CAUGHT A SECOND DEFECT.** The **pre-existing** green `#1a7f4b` on its
10% wash measured **3.95 — under AA** — and the obvious amber (`--vy-amber`) measured **3.30**. Both darkened
until they clear 4.5 with headroom: green **5.75**, amber **5.39** (verified on the rendered page).

### ⚠️ 2FA IS REAL, NOT DEFERRED — with evidence

`PreparesTwoFactor::twoFactorPanel()` drives a genuine TOTP enrollment: `hasTwoFactorEnabled()` reads the real
encrypted `two_factor_secret` / `two_factor_confirmed_at` columns, the QR + secret are generated by
`TwoFactorService` **only in the `pending` state**, one-time recovery codes are flashed once and never stored
in plaintext, and login-time enforcement is invariant **#31**. The component states it outright: *"NOT a stub."*
Verified on the page: with 2FA disabled there is **no QR and no secret** rendered — the enrol UI appears only
after enabling. **Nothing here is a fabricated protection state.**

### ⚠️ A STALE DOCBLOCK — the third of this pass

The component's honesty note still says *"the design's active-sessions, trusted-devices, connected-apps and
IP-rules panels ARE still unbacked subsystems … shown here as an honest 'Soon'"*. **Three of those four are now
REAL** (a later `#104/#104b` gate built them, and `OperatorSecuritySessionsTest` even asserts *"not a Soon
stub"*): **active sessions** via the shared `ActiveSessions`, **trusted devices** via the shared #66
`TrustedDeviceService`, and a **real IP allowlist** (`IpAllowRule`). Only **connected apps** remains 0 — and
that is correct, not deferred: there is **no operator OAuth** (Passport is owner-scoped), so *"Third-party app
access isn't available for staff accounts yet"* is the honest state. Same drift class as **§16** (Payout Run)
and **§22** (Owner Detail) — **the code is right, the comment is out of date.**

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D24-1 | A **location** per active session (a city/country beside the device and IP). | The product **does not geolocate** — sessions carry `ip_address`, `user_agent` and `last_activity` only. The live rows show device + IP + relative time, and nothing more. | A GeoIP lookup (a third-party dependency + a privacy decision about resolving staff IPs). | **RATIFIED REFUSAL / REAL GAP.** ⚠️ **Do not invent a city.** Verified on the page: **0 fabricated locations** — the IPs render as the literal `127.0.0.1` they are. |
| D24-2 | **Passkeys / WebAuthn** offered alongside the password and 2FA. | The `passkeys` table exists and the **owner** realm has a real WebAuthn implementation (#66, browser-proven with a CDP virtual authenticator) — but the **operator** page does not surface it. | Wiring the existing owner-realm passkey flow to the operator page. Genuinely backed — the service, the model and the ceremony all exist; this is a realm asymmetry, not a missing feature. | **REAL GAP** (and a notable **realm asymmetry** worth carrying: the owner Security page is currently *ahead* of the operator one). Logged, not built. |

### Also noted on this page (not backend gaps)

- ⚠️ **EVERY DESTRUCTIVE CONTROL WAS OBSERVED READ-ONLY — NOTHING FIRED.** Six real gated routes were read
  from their bound actions and **never submitted**: `security/password` · `security/2fa/enable` ·
  `security/ip-rules` · `profile/sign-out-others` · `security/trusted.revoke` · `security/export` (GET).
  **Proven afterwards:** 2FA still disabled, **sessions still 2**, trusted 0, passkeys 0, IP rules 0, and the
  password hash still verifies — **nothing signed out, nothing disabled, nothing rotated.**
- **Active sessions are the REAL table** — 2 rendered, matching `sessions` for this user exactly, with device
  + IP + relative last-activity, and the current one marked by the **word** *"This device"* (not colour).
  #28: the query is `ActiveSessions::forUser(<the acting user>)` — a user sees only their own.
- **The password policy and `changePasswordTo` were NOT touched** — no controller or model file changed at all
  this gate (`git diff` covers only the view and its test). The certified sibling-session termination stands.
- **Honest empty states throughout:** *"No trusted devices — every sign-in will ask for your 2FA code."* ·
  *"No IP restrictions — anyone with a valid login can reach the admin panel from any network."* ·
  *"No apps connected — third-party app access isn't available for staff accounts yet."*
- **Fixed (2) — the page's only console output, an accessibility advisory.** Chrome warned three times that
  *"Password forms should have (optionally hidden) username fields"* — password managers need it to associate
  the credential. All **three** password forms (change-password, sign-out-others, revoke-trusted-device) now
  carry a hidden `autocomplete="username"` field. ⚠️ **It has no `name`, so it is never submitted** — every
  payload is byte-identical (verified: `usernameSubmitted: false` on all three). Console is now **0 messages**.
- **No API-key section exists on this page**, so the Page-14 shown-once ruling has nothing to violate here.
- **Responsive:** 0px horizontal overflow at **1440** and **1000**. **0 console messages of any level.**

---

## 25 · Operator Reviews — `/app/reviews` — GUEST-AUTHORED CONTENT

**Prototype:** `docs/design/raw/Operator Reviews.html` (decoded fresh 2026-08-10; it opens on a **"Review
requests"** automation modal). **Live:** `OperatorReviewsController@index` →
`resources/views/operator/reviews.blade.php` — ⚠️ **a controller-rendered page**, one of the four wrapped into
the document layout at `c0ce419`. **Role:** `can:reviews.view` to see; **`can:reviews.manage`** for respond /
publish / reject, and the whole action row is `@can`-gated so the UI never invites a 403 (#30).

### ⚠️ THE FINDING — THE PAGE HAD NEVER BEEN TOKENISED, AND CARRIED THREE AA FAILURES

The review card was written in **raw hex with zero `--vy-*` tokens** (measured: `var(--vy-` count **0** in the
card markup). Because it never picked up the design system, it still carried the **pre-AA greys** the
accessibility gates darkened everywhere else — and the measurements confirmed three genuine failures:

| Element | Colour | Measured | |
|---|---|---|---|
| **The star rating** | `#E8703A` (the brand FILL, used as 18px text) | **3.08** | ❌ |
| Room type | `#8A8079` (the old `--vy-muted`) | **3.86** | ❌ |
| Date | `#A69A92` (the old `--vy-faint`) | **2.74** | ❌ |

⚠️ **The stars mattered most: they are the RATING**, the single most important fact on the card — and the
rating was carried by **stars alone**, with no numeric value anywhere on the row (WCAG 1.4.1).

**Fixed:** the card is tokenised (`--vy-ink` / `--vy-muted` / `--vy-faint` / `--vy-accent-text` /
`--vy-hairline` / `--vy-accent-wash`), the empty state with it, and **the numeric rating now sits beside the
stars** (`5/5`), with the stars themselves `aria-hidden` decoration. **Re-measured: 0 AA failures, 10 passing.**
⚠️ `rating` was already in the controller payload — this is **not** a second derivation of the shared
`ReviewRating` (#76), which remains the only source of the AGGREGATE.

### ⚠️ THE AGGREGATE IS THE SHARED `ReviewRating` (#76) — verified, not assumed

`OperatorReviewsController` calls **`ReviewRating::operatorSummary()`** and passes it straight through; the view
only **formats** it (`number_format($summary['average'], 1)`). **No parallel average is computed anywhere.**
The one piece of in-view arithmetic is a **bar-width percentage** (`distribution[$star] * 100 / count`) derived
from the shared summary's own numbers — a bar length, not a second rating.
**The figures reconcile:** 5★×5 + 4★×1 + 3★×1 = 32 ÷ 7 = 4.57 → the rendered **4.6**; replies 5 of 7 → **71%**.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D25-1 | A **BY CHANNEL** card — per-source ratings (Booking.com 4.8 ·61, Airbnb 4.9 ·42, Direct 4.7 ·25) — plus "via Booking.com" on each review and *"Posts to Booking.com publicly"* under the reply box. | Reviews carry no channel attribution, and nothing posts a reply to an OTA. Same unmodelled attribution as **D2-2** — `reservations.channel_id` is unset on every row until the import path runs. | Live OTA review sync (read AND write-back) — the **Channex** cutover. | **ACCOUNT-GATED / RATIFIED REFUSAL — CHANNEX-TIED.** Verified absent: **0** occurrences of Booking.com / Airbnb / Expedia / "via …". ⚠️ A per-channel rating would be a fabricated breakdown, and *"posts publicly to Booking.com"* would promise a write the product cannot perform. |
| D25-2 | **Generated reply drafts** — chips reading *Draft a reply · Warm thanks · Make it right · Short & kind* above the reply box. | No reply generation of any kind; the operator writes their own text. | A live LLM (§H — today a `FakeLlmClient`). | **RATIFIED REFUSAL** (sweep §5, and the same refusal the Inbox gate recorded: *no AI or suggested reply*). Verified: **0** draft chips on the page. |
| D25-3 | **"+0.2"** beside the overall rating, and **"Avg. reply in 4h"** on the reply-rate card. | No rating history and no reply-latency measurement exist — a review has a created time, but no responded-at is tracked for a duration. | A ratings snapshot history; a `responded_at` timestamp for latency. | **RATIFIED REFUSAL** — the same class as **D18-2** (rate trends) and **D3-4**: a delta with no baseline is a fabricated figure. Verified: no trend, no latency claim rendered. |
| D25-4 | A **two-column master/detail** layout (a compact review LIST with *Needs reply · 2 / All / Replied* tabs beside a full detail pane), plus a **char counter** and a **"Signed — Sofia, Seaside Villas"** line on the reply box. | The live page is a single column of full cards, filtered by the real moderation status (*All / Published / Pending / Rejected*). | A master/detail restructure, a `needs-reply` filter (derivable — 5 of 7 answered), and the reply-box affordances. | **REAL GAP (presentation)** — logged rather than built: it restructures the whole page, and the live filter axis is the real `status` enum, which is *more* honest than a reply-state-only view. ⚠️ Both axes are legitimate; a future gate could offer both. |

### Also noted on this page (not backend gaps)

- ⚠️ **The design's automation modal is ALREADY honestly deferred, in copy, on the page.** The prototype opens
  on *"Review requests"* — auto-request after checkout, **SMS**, and *"Catch unhappy guests first — under 4★
  routes to the manager, not the public site"* (**review-gating**). The live subtitle states plainly:
  *"Auto-responses and review-gating are coming soon — for now, every response and publish decision is yours."*
  ⚠️ Review-gating is also an **ethically loaded** feature (suppressing negative reviews from the public site);
  it should not be built without an explicit founder decision, and SMS is a standing §H deferral.
- **#39 — every review is real.** 7 real `Review` rows, all `published`, 5 with a real `operator_response`;
  the honest empty state is *"No reviews yet."* **No fabricated review, reviewer, quote or star count.**
- **#28 — guest NAME only.** Verified: **0 email addresses and 0 phone numbers** anywhere on the page. The
  reviewer's display name is content they authored; their contact is not shown.
- **Nothing was submitted.** All 14 write forms (7 × respond, 7 × reject) POST their real
  `can:reviews.manage` routes with CSRF and were **read, never fired** — proven after: reviews **7**, published
  **7**, responses **5**, unchanged. `ReviewRating` and the controllers are byte-identical.
- **Status is never colour-only** — PUBLISHED / PENDING / REJECTED / PRIVATE each print as a word, and the
  rating now carries its number.
- ⚠️ **Noted, not changed:** an already-answered card shows the response **twice** — once as *"Your response:
  …"* and again pre-filled in the edit input beneath it. It is not dishonest (the input is the edit affordance),
  but it reads as duplication. Folding it into a single "edit" affordance is part of the D25-4 restructure.
- **Responsive:** 0px horizontal overflow at **1440** and **1000** (the KPI strip reflows to 2 columns).
  **0 console errors/warnings.**

---

## 26 · Operator Channels — `/app/channels` ⚠️ THE OTA-CLAIM ROOT (#37)

**Prototype:** `docs/design/raw/Operator Channels.html` (decoded fresh 2026-08-10). **Live:**
`App\Livewire\Operator\OperatorChannels` → `resources/views/livewire/operator/channels.blade.php`.
**Role:** ⚠️ **Pro-gated** — the route carries `EnsurePlanFeature:channels`.

⚠️ **A METHOD CONSTRAINT, STATED PLAINLY: the live page could NOT be browsed for this gate.** The demo org is
on **Essential**, so `/app/channels` returns a real **HTTP 403** and renders the honest feature-locked page
(*"Channel manager isn't in your plan … your data and settings stay exactly as they are"* + *View plans &
upgrade*). **I deliberately did not change the org's plan to see the unlocked page** — that mutates real
subscription state, and the pricing model is an **open founder decision (G.1)**. The channel UI was therefore
verified by **source reading plus its existing tests**, which render it against a Pro org.
*(The single console "error" on the locked page is the 403 response itself being logged — not a JS error.)*

### ⚠️ THE CARRY-FORWARD FROM REVIEWS — MEASURED, AND IT DOES NOT APPLY HERE

P25 found the Reviews page (a `c0ce419` **controller**-rendered page) had never been tokenised: raw hex, the
pre-AA greys, three measured AA failures. **Measured here rather than assumed:** this page is a **Livewire**
component, and its view contains **0 raw hex** and **0 pre-AA greys** (`#8A8079` / `#A69A92`), using classes
plus `var(--vy-*)`. **The carry-forward concern is genuinely absent on this page.**

### ⚠️ ALMOST THE ENTIRE PROTOTYPE IS CHANNEX-TIED FABRICATION

This is the **root** of the D2-2 finding that has recurred on Calendar, Rates, Reviews and the Dashboard. At
source: **`channels.last_synced_at` is NULL**, and **0 of 42 reservations carry a `channel_id`** — no import
has ever run. The design asserts, on one screen: a **"Sync all now"** action · a **"Expedia disconnected —
sync stopped 41 min ago"** alert · **"Synced"** dots with **"last sync 3 min ago / 5 min ago"** ·
**"6 listings mapped · rates & availability"** · per-channel **booking counts** (22 / 12 / 7) and **commission
%** (15 / 14 / 18) · **"OTA BOOKINGS · SEP 41 of 64"** · **"DIRECT SHARE 36% +4%"** · **"SYNC STATUS 2 of 3"** ·
a dark **"3 bookings waiting to import"** card · a **BOOKING MIX** with per-OTA splits and *"23 this month
kept ≈ €1,050 in house"* · and a live **Sync activity feed** (*"Rates pushed to Booking.com"*, *"Booking
imported from Airbnb — K. Novak · Apt 102 · 6 nights"*, *"Availability updated everywhere"*).

**None of it can be produced today.** The live page's honest treatment is correct and already documented in the
component: **Direct is REAL** (`reservations.source='direct'`), an OTA channel's **status is real**
(`channels.status`), live sync is surfaced as *"activates with your Channex account"*, and the **booking mix is
the REAL by-source split** — genuinely ~0 for OTA.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D26-1 | **"Synced" status + "last sync 3 min ago"** per channel, a **"Sync all now"** action, and the *"sync stopped 41 min ago"* alert. | `last_synced_at` is **NULL** on every row and no sync process exists (`ChannexClient` throws without a partner key; the fake client is bound). | The **Channex** cutover (§H). | **RATIFIED REFUSAL — CHANNEX-TIED.** ⚠️ This is precisely the **F-OP-2** fabrication closed in the 2026-08-04 QA fix and refused on the Dashboard (sweep §5). The live page shows the **real** `channels.status` — *Connected* for the demo row, *Live* for Direct — and **renders no timestamp at all**, because there is none. `OperatorChannelsTest` already asserts `assertDontSee('last sync 3 min ago')`. |
| D26-2 | **"6 listings mapped · rates & availability"** per channel, plus *"Rates pushed to Booking.com"* and *"Availability updated everywhere"* in the activity feed. | Nothing maps a room or a rate plan to an OTA, and nothing pushes ARI outward. | Channex mapping + ARI push. | **RATIFIED REFUSAL — CHANNEX-TIED.** ⚠️ **And this gate found the one place the live page over-claimed it:** the Sync-activity card labelled its channel COUNT **"Channels mapped"**. The figure is real (`$channels->count()`), but *"mapped"* asserts a mapping step that has never run — on the one surface where that claim matters most. **Fixed → "Channels configured"**, with a test now guarding it. |
| D26-3 | **Per-channel booking counts and commission %** (Booking.com 22 · 15%, Airbnb 12 · 14%, Expedia 7 · 18%), the **OTA BOOKINGS 41 of 64** tile, and the **BOOKING MIX** per-OTA split with *"≈ €1,050 kept in house"*. | ⚠️ **0 of 42 reservations carry a `channel_id`** — this is the D2-2 root. Any per-OTA count or commission figure would be invented outright. | Channel attribution on import (Channex). | **RATIFIED REFUSAL — CHANNEX-TIED.** The live booking mix uses the **real `reservations.source`**, so Direct is real and OTA is honestly ~0. ⚠️ **Do not "fill in" the mix**, and do not derive a commission saving from counts that do not exist. |
| D26-4 | A **Sync activity feed** naming guests and units — *"Booking imported from Airbnb · K. Novak · Apt 102 · 6 nights"*, *"J. Chen · Villa Coralis · 4 nights"*. | No import events exist to list. | Channex import events. | **RATIFIED REFUSAL — CHANNEX-TIED**, and ⚠️ note the **#28 shape** even post-cutover: a channel-operations feed naming guests and their units would put guest-identifying detail on a surface that does not otherwise carry it. If this is ever built, the row should carry the reservation reference, not a guest name + unit + length of stay. The live card is the honest *"Live sync events (rate pushes, booking imports) appear here once your Channex connection is active."* |
| D26-5 | **"+ Connect Vrbo, Agoda, Google…"** and per-channel **Manage / Reconnect** actions. | There is no connection flow to initiate — no OAuth/partner handshake exists. | The Channex partner integration. | **REAL GAP / ACCOUNT-GATED.** ⚠️ Verify before ever wiring: a *Connect* button that opens nothing, or implies a partner handshake that does not exist, is the same class of claim as a fake sync state. |

### Also noted on this page (not backend gaps)

- ⚠️ **The plan gate is REAL and honest** — an Essential org gets a genuine **403** and a locked page that
  explicitly reassures *"your data and settings stay exactly as they are"*. `OperatorChannelsTest` asserts it
  (*"gates /app/channels to Pro — an Essential org gets the feature-locked page"*). #38-scoped throughout.
- **Connection status is never colour-only** — each pill carries its word: **Live** (Direct), **Connected**
  (the real `channels.status`), **Action needed** (error).
- **Fixed — "Channels mapped" → "Channels configured"** (D26-2), the only over-claim found on the page.
- ⚠️ **A test guard was added AND scoped correctly the second time.** `assertDontSee('listings mapped')` +
  `assertDontSee('Channels mapped')` + `assertSee('Channels configured')`, **proven to discriminate**
  (restoring the old label turns it RED). My first attempt used a bare `assertDontSee('mapped')` — which
  **failed against a CSS comment in the operator layout's `<style>` block** (*"mapped ONLY to real reservation
  statuses"*): **CSS comments render, Blade comments do not.** Same trap as the contrast guard fixed at
  `9696b9c` — scope a guard to the CLAIM, not to a bare word.
- **No app/ code changed at all** this gate — the diff is one Blade label and one test file.

---

## §27 — Operator **Portfolio** (`/app/portfolio`, `can:properties.view`) · P27

**Prototype:** `docs/design/raw/Operator Portfolio.html` (bundler-encoded; decoded copy at
`/tmp/audit/Operator Portfolio.decoded.html`). **Live:** Livewire `OperatorPortfolio` — measured **0 raw hex,
0 pre-AA greys** (the `c0ce419` carry-forward does **not** apply; that risk tracks *controller*-rendered pages
like Reviews, not Livewire ones — now two-for-two on that predictor).

⚠️ **This is a rollup surface, so the isolation question is about FIGURES, not just names.** Verified: the
property set and every rollup come from `PropertyContext::accessibleIds()` (pivot-or-all), captured at `mount()`
behind `#[Locked]` — the #20 Livewire-boundary fix. The drill-down (`app.property.switch`) validates
`property_id` **against the accessible set** (foreign → 422) and `SetCurrentPropertyForWebRequest` re-validates
on the next request. **The page does not paginate or filter**, so there is no `?page=2`/filter path that could
carry scope — stated as a fact, not assumed.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D27-1 | **"+9%"** beside Revenue · month. | Nothing computes a prior-period comparison here; the literal `+9%` is **hardcoded** in the prototype source. | A prior-month `PerformanceMetrics` pass **and** a rule for partial/absent history. | **RATIFIED REFUSAL — D3-4 CLASS.** A month-over-month delta needs a comparable prior period; a young org's prior month is partial, so the figure would mislead exactly where it looks most authoritative. Same refusal as the owner Insights vs-LY delta, which is shown **only** where prior-year data exists. |
| D27-2 | A **"4 unread"** flag on a property card. | No per-property unread count is computed for this surface. | A per-property scoped unread read from the inbox. | **LOGGED — REAL GAP (small).** Buildable and honest, but it is a new query, not presentation. ⚠️ If built, it must be the **operator's** scoped unread, and stay name-free on this surface (#28). |
| D27-3 | Post-add **setup checklist** — *"Name your units & add photos · Set base rates & availability · Connect Booking.com, Airbnb & more"*. | No onboarding checklist entity exists; the third item is Channex. | A setup-progress model + the Channex cutover for item 3. | **LOGGED — PARTLY CHANNEX-TIED.** The live product instead derives an honest **"Finish setup to go live"** status from real inventory (`room_types`/`units` = 0), which is the same information without a fabricated progress state. |
| D27-4 | The Add-property modal's **"+14 units × €9/mo added to your plan · prorated from today"**, and a **ROOMS stepper** that provisions N rooms at creation. | No proration is computed or charged at property creation, and creation provisions **no units**. | ⛔ **The billing decision** (below), plus bulk unit provisioning. | ⛔ **REFUSAL — TIED TO THE OPEN BILLING DECISION.** The live modal asserts **no price at all** — correct, and deliberately left untouched: quoting a per-unit rate here would *pick* one of the two contradictory pricing models, which is the founder's call. ⚠️ **See the note below** — the live copy still says *"billed per unit"*. |
| D27-5 | Per-card band **tinted by rank** (deep orange → peach → brown, tracking revenue/occupancy order). | — (presentation) | — | **DIVERGENCE — DELIBERATE.** Rendered with one brand band for every property. Tinting by rank encodes a ranking **in colour alone** (WCAG 1.4.1), and with two properties it reads as a status difference that does not exist. The ranking is already shown numerically in the side-by-side table. |

### Fixed this gate (design-only; no metric math touched)

- **The design's gradient card band** — `.vy-prop-band`, full-bleed, with the mark/name/location on it.
  ⚠️ **Not a copy of the prototype's band:** the prototype runs `#E8703A→#C2410C` with white ink, and white on
  the `#E8703A` end measures **3.10** — under the 4.5 bar for the 15px name. Ours runs from the **deep** end
  (`#C2410C→#9A330A`); measured **5.18** (name/chip) and **4.68** (location) at the band's lightest point.
  *Match the design's structure, not its contrast defect.*
- **`.vy-prop-current` ("Working here") was a real AA failure at 4.43** on the pre-change card (9.5px/700 → the
  4.5 bar applies). Base token moved to `--vy-accent-text`; on the band it is white at 5.18. Also `nowrap` — it
  wrapped mid-phrase at 1000px.
- **Label-above-value stats, per the design**, with the design's hairline dividers instead of boxed tiles.
- **The per-card occupancy rail** (real tonight figure, with an `aria-label` carrying sold/total).
- **The chip row**, including the **arrivals count that was computed but never rendered**.
  ⚠️ **Scope-safety divergence:** the prototype deep-links every card to *Dashboard · Calendar*. Our property
  scope is the **session** `PropertyContext`, so those links from a **non-current** card would silently render
  the property you are actually working in. They therefore render **only on the current card**; elsewhere the
  card offers **Open** (the real, validated scope switch). The arrivals chip is never a link.
- **"Full report ›"** — in the design, absent from the live page, yet the component **already computed its gate**
  (`canViewAnalytics`) and never rendered it. Now rendered, gated on `reports.view` — **the same permission the
  `/app/reports` route requires** (#30).
- **`—` for an average over zero.** ⚠️ This is the **design's own rule**, not an inference: the prototype's
  freshly-added property renders `adr: '—'` on the card and `occ/adr/revpar: '—'` in the table, while revenue
  stays a real `€0`. Live now matches — ADR is `—` when `roomNightsSold == 0`; occupancy/RevPAR are `—` when
  `availableRoomNights == 0`; **revenue keeps its computed zero** (the Owners-list ruling).
- **Card stat labels carry their period** (`ADR · mo`, `Revenue · mo`). The cards are **this month** while the
  table below is **this year** — a bare "Revenue" beside a year total that differs by an order of magnitude is
  the *"two revenue figures differing 63%"* class of confusion.
- **Stale docblock** — it claimed the ADR/RevPAR comparison was "deferred to Phase 6b, never faked" long after
  6b shipped it.

### ⚠️ Findings that are NOT this page's to fix

- ⛔ **The live Add-property modal says *"billed per unit"*.** That asserts one of the two contradictory pricing
  models while the tier price is what actually reaches Stripe. **Surfaced, not changed** — editing it either way
  is picking a side of the open billing decision. It belongs on the blocker's ledger.
  - ⚠️ **UPDATE (SAVE v12, 2026-08-13) — the framing changed, and the finding SURVIVES in a sharper form.** There
    is no longer an open decision to pick a side of (§N.17.1): a plan declares its shape, and the Billing page
    now renders per shape. **But this modal still asserts "billed per unit" UNCONDITIONALLY**, so on a FLAT org
    it is exactly the §N.8d that pass 3 retired one page over — the same false proration/per-unit claim, on a
    surface pass 3 did not touch. ⚠️ **This is a live §N.8d and a real item**, not a blocker footnote: it needs
    the same shape-keying the Billing page got (`Plan::isPerUnit()`), and it belongs with `DO-BILL-1` in the
    billing tail rather than on a ledger that no longer exists.
  - ✅ **DONE at `c6d398a`** — the modal now renders from the plan's shape. See the `DO-BILL-2` entry below.
- ⚠️ **The operator shell's topbar H1 overlaps the search box at ≤1000px** (H1 right 383 vs search left 339).
  **Proven pre-existing** by stashing this gate's changes and re-measuring on clean HEAD. Realm-wide (the shell,
  not this page).

### ⚠️ A green test that was defending nothing

The pre-existing **"reconciles the portfolio to the sum of properties" (#17)** test was **vacuous**: `seedScenario()`
books nothing, so it asserted `€0.00` against a page where every figure is zero. **Proven by mutation** — inflating
the rollup by €9,999.99 left it **green**. Replaced with a rollup test carrying real, distinct figures
(€100 + €250 = €350, against a foreign org holding €999), and **proven to discriminate**: breaking the
independently-computed total by **one cent** turns it red.

⚠️ **What the mutation work also established about #38 here:** the cross-org line is held by **two independent
layers** — the accessible-set from `PropertyContext` *and* `Property`'s global tenant scope. Disabling **either
one alone leaves the page correct**; the test only goes red when **both** are removed. That is defence-in-depth
*verified*, rather than assumed — and it is why a single-point mutation of this page cannot leak.

⚠️ **Also found:** **no provisioned role holds `properties.view` without `reports.view`**, so the "Full report"
gate's negative branch is unreachable through the stock catalogue. The test revokes the permission directly so
the assertion still discriminates instead of quietly testing nothing.

---

## §28 — Operator **Search** (`/app/search`, `can:reservations.view`) · P28

**Prototype:** `docs/design/raw/Operator Search.html`. **Live:** Livewire `OperatorSearch` + `SearchService`.
**Tokens measured:** the page's own `<style>` block carried **`.vys-badge { color: var(--vy-accent) }`**, which
measured **4.41** on the card wash at 10.5px (the 4.5 bar applies) — **8 occurrences, now `--vy-accent-text`,
0 failures.** So the controller-vs-Livewire predictor is **not** the whole story: this is a Livewire page with no
raw hex that still failed AA, because the failure came from the **accent-as-text** family, not from un-tokenised
hex. ⚠️ **Measure regardless of render type** — the two risks are independent.

⚠️ **No Scout / Meilisearch dependency exists** — search is direct Eloquent, so there is no shared index to
mis-scope. Tenant isolation rests on the models' `BelongsToOrganization` global scope.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D28-1 | The guest row's contact sub-line: **`lukas.meyer@mail.com · Munich, Germany`**. | — (the data exists; it is deliberately not rendered) | — | 🚫 **REFUSED — #28, and this is the whole point of the page.** Contact PII renders on the **Guests CRM only** (list + profile); front desk / reservations / inbox are name-only. A search row is **not** a permitted PII surface, and search is the one screen that aggregates guests, reservations, owners and units into a single list — so it is the easiest place to widen the perimeter by accident. `SearchService` returns name-only rows **by construction**. Now pinned by three tests, **mutation-proven** (adding the email to the guest row turns them red). |
| D28-2 | A **Documents** result group (`Folio receipt · Meyer` · *Guest receipt · PDF · VYR-10430*), counted in the type pills. | Search covers reservations / guests / units / owners; documents are not indexed or matched. | Extending `SearchService` to the `documents` polymorph, with per-document authorization. | **LOGGED — REAL GAP.** ⚠️ If built, note the shape: a document row named after a guest puts guest-identifying text on this surface, and a folio receipt is a money document — it needs its own permission check, not the page's `reservations.view`. |
| D28-3 | Reservation rows carrying **stay dates and the unit name** (*"L. Meyer · Villa Coralis" · "12–16 Sep 2025 · VYR-10430 · Booking.com"*). | `SearchService::reservations()` returns reference / guest name / status / source — no dates, no unit. | Widening the row projection (dates are on the reservation; the unit needs the assignment join). | **LOGGED — REAL GAP (small).** Honest and cheap; it is a projection change, not a new capability. The live row now shows guest name + `reference · source`, which is what is really there. |
| D28-4 | A per-guest **"2 stays"** count on the guest row. | Not computed for search rows. | The shared `GuestHistory` helper, per result. | **LOGGED — REAL GAP (small).** ⚠️ Keep it a COUNT — a stay count is not contact data, but do not let it grow into "last stay · room · spend" on this surface. |
| D28-5 | **DATES presets** — *September · October · Upcoming · Custom*. | The service filters on a real check-in range (`from`/`to`); it has no named-period semantics. | Period presets resolved to ranges in the service. | **LOGGED — MINOR.** The live control is the real range, which is the honest equivalent; presets are sugar over it. |
| D28-6 | A **"Relevance"** sort option. | Nothing scores relevance — the service sorts by newest / check-in / reference. | A scoring function (and a defensible definition of relevance across four entity types). | 🚫 **REFUSED.** Offering a "Relevance" order that is really "newest" is a false claim about how results are ordered. The three REAL orders are shown. |

### Fixed this gate (presentation only — no query, scope or projection changed)

- **The design's single query bar** — pill, leading magnifier, an in-field **clear (×)**, submit inside the bar.
- **Filter chip rows** (Status / Source / Occupancy) replacing native `<select>`s, plus a labelled **Dates**
  range. ⚠️ **Every chip is a plain `<a href>` GET link that carries `q` and every OTHER active filter**, so
  filters compose instead of clobbering, and the tenant/property middleware re-runs on each change (#20).
  **Zero `wire:click`** on this page — `/livewire/update` does not run the property-context middleware, so a
  wire-action filter would silently drop the scope. Verified live: `?q=Demo&status=confirmed&source=direct`.
- **Results header** — *"N results for "q""* + a **Sort** segmented control + *× Clear all filters*.
- **Group headings carry their count**; **rows** gained the design's icon tile, two-line title/sub, and chevron.
- **Recent searches** restyled to the design's row list (clock tile + term + ↗).
- **`.vys-badge` → `--vy-accent-text`** (4.41 → AA), and **status keeps its word**, never colour alone (1.4.1).

### ⚠️ Findings recorded, not fixed

- ⚠️ **"Recent searches" persists GUEST NAMES in `localStorage`** (`vy_recent_search`), which survives logout.
  The feature is real and browser-local — but a **shared front-desk terminal** is the normal deployment, and the
  next person to use it can read the previous operator's searched guest names. **Not a rendering bug and not
  this pass's to change** (clearing on logout is behaviour), but it is a genuine #28-adjacent exposure and
  should be decided deliberately: clear on logout, or drop the feature.
- **Reflected input is not disclosure.** Searching *by* an email echoes it into the input's `value`. That is the
  operator's own input coming back, not the product volunteering stored PII — the test asserts the distinction
  by requiring that a contact value the operator did **not** type (the phone) never appears.
- ⚠️ **Search's tenant isolation is SINGLE-layer.** Unlike Portfolio (where two independent guards had to fall),
  disabling the `Guest` global scope alone leaks another org's guest — proven by mutation, and the existing
  cross-org test correctly catches it. Correct today, but there is no second net here.

---

## §29 — Operator **Notifications** (`/app/notifications`) · P29

**Prototype:** `docs/design/raw/Operator Notifications.html`. **Live:** Livewire `OperatorNotifications` →
the shared `NotificationFeed::forUser()`. **Gating:** no extra `can:` — the feed is **self-only by nature**
(`$user->notifications()`), inside `/app` behind `EnsureBackOfficeAccess`.

**Tokens measured (regardless of render type, per the corrected P28 carry-forward): 0 contrast failures**,
before and after. This page was already clean — unlike Search, which was also Livewire but failed 8× on
accent-as-text. **Both risks still have to be measured separately; neither is predicted by render type.**

⚠️ **Self-scope + the mark-read path, READ not fired.** `NotificationController` writes through
`$request->user()->notifications()->findOrFail(...)`, so a cross-user id **404s by construction** — a user can
only ever mark their OWN notifications read. Confirmed by reading the controller and by the existing
`never lets a user read or mark another user's notifications` test. **No mark-read/mark-all-read/dismiss was
submitted this gate**; the dev feed is byte-identical at **242 total / 242 unread / 0 read**, before and after.

⚠️ **The unread count is single-sourced.** The only unread-count queries in the entire codebase are the two
inside `NotificationFeed` (`forUser` + `recentForUser`), both on the same per-user relation. No view derives its
own; the bell badge reads the same value and renders **"9+" as a display cap** with
`aria-label="{{ $unread }} unread"` carrying the real figure. Counts stay **whole-set, not page-set** (already
tested) — which is why the unread pill can read "59 unread" while a filtered view shows nothing.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D29-1 | The footer: *"Choose which of these reach you by **email or SMS** in Settings."* | There is no SMS channel — no Twilio, no provider. | The SMS cutover (§H). | 🚫 **REFUSAL-PRESERVE — DEFERRED.** Live already says *"Choose which of these reach you in Notification preferences"* with no SMS claim, and the preferences page shows Push/SMS as honestly pending. Do not re-add "or SMS" as copy: it would promise a delivery channel that cannot fire. |
| D29-2 | Rows for *"Expedia disconnected — Sync stopped 41 min ago"*, *"New 5-star review · Lukas M."*, *"Owner payout run is due — €10,370"*, *"K. Novak checked in — Apt 102 · €260 balance settled"*. | These are mock rows. | — | 🚫 **REFUSAL-PRESERVE (#39).** The live feed renders only REAL notifications from real events (new booking, statement finalized, unit out of service, urgent work order, new review). ⚠️ The *"Expedia disconnected · sync stopped 41 min ago"* row is the **F-OP-2 / D26-1 fabrication again**, arriving through a different door — a notification asserting a sync that has never run. The live channel-error notification is wired to the REAL `channels.status` (`NotificationWiringTest`). |
| D29-3 | Category chips **Channels** and **Housekeeping**. | The live categories are whatever real notifications exist — currently Finance / Front Desk / Maintenance / Reputation. | Real events in those categories. | 🚫 **REFUSAL-PRESERVE.** The bell rebuild already refused these exact fabricated pills; the tab row is built from `$feed['categories']`, i.e. the REAL distinct categories present. A category tab that filters to nothing is worse than no tab. |
| D29-4 | A **circular** 38px icon tile per row. | — (the live tile is the shared rounded square) | — | **DIVERGENCE — DOCUMENTED, deliberately not "fixed".** ⚠️ **The two prototypes disagree with each other:** measured in the browser, *Operator Notifications* renders a **38px circle** while *Owner Notifications* renders a **34px rounded square (10px)** — for the same component. The live product has ONE shared `<x-notifications.category-icon>` (rounded square) used by the operator page, the owner page and the bell panel. Matching the operator prototype would break owner parity and split one concept into two definitions — exactly what the anti-drift rule forbids. **The single shared definition is the more coherent resolution; the design source is internally inconsistent here.** |
| D29-5 | Per-row **dismiss / delete**. | Only mark-read exists (single + all); nothing deletes a notification. | A dismiss/soft-delete column + a self-only route. | **LOGGED — REAL GAP (small).** ⚠️ If built it must reuse the same self-only pattern (`$request->user()->notifications()->findOrFail()`), never an id-only lookup. |

### Fixed this gate (presentation + one a11y correction; no backend touched)

- **The design's toolbar** — an inline **unread pill** beside the heading, the tabs collapsed into **one
  segmented shell** with the active tab as a raised white pill (they were loose buttons), mark-all-read right.
- **Rows rebuilt to the design** — the **category as a chip beside the title** (it was right-aligned uppercase
  text), the unread dot moved to the row's right edge, and the row/typography moved out of inline styles into
  `.vyn-*` classes.
- ⚠️ **WCAG 1.4.1 — the unread state no longer rides on colour alone.** The dot carried only
  `title="Unread"`, which is **not reliably announced on a non-interactive span**, so for assistive tech the
  state was colour + font-weight only. The dot is now `aria-hidden` decoration with real
  `.vy-sr-only` text beside it, and the ✓ button gained a proper `aria-label` naming its notification.
  Guarded by a test, **mutation-proven** (restoring the `title`-only dot turns it red).
- **`.vy-sr-only` defined ONCE** in the operator layout — a generic utility, not a page concern.
- **Tabs stay GET links** (`?filter=`), asserted by a new test alongside `assertDontSee('wire:click')`.

### ⚠️ Realm-wide finding — the SAME 1.4.1 gap remains on two other surfaces

The identical dot-only pattern is still present at
**`resources/views/livewire/owner/notifications.blade.php:33`** and — shared by BOTH shells —
**`resources/views/components/notifications/bell.blade.php:46`**. Fixing them needs `.vy-sr-only` in the owner
layout too. **Logged, not fixed** (one page per turn), but they are the same defect and should be closed
together so the panel and the page do not disagree about how the unread state is announced.

---

## §30 — Operator **Notification Preferences** (`/app/notification-preferences`) · P30

**Prototype:** `docs/design/raw/Operator Notification Preferences.html`. **Live:** Livewire
`OperatorNotificationPreferences`; the write is `POST /app/notification-preferences` →
`NotificationPreferenceController@update`.

**⚠️ CHANNELS — what actually persists and delivers (read from the code, not inferred):**
`NotificationPreferences::CHANNELS = ['in_app', 'email']`. **Only two.** SMS and push are not merely disabled —
**they do not exist in the persistence model**, so a preference for them structurally *cannot* be stored. Email
delivery is real (Mailables, mutation-proven in `NotificationEmailChannelTest`); in-app is real (the P29 feed).

**⚠️ STORAGE MODEL — typed rows, not JSON.** `notification_preferences (user_id, event_key, channel, enabled)`
is a per-user × event × channel pivot, written with a targeted `updateOrCreate` per row. **The WAVE-1
wholesale-replace hazard is structurally absent here** (the Maintenance-Settings case, not the WAVE-1 one). The
controller iterates the CATALOG rather than the submitted keys, so an unknown submitted key is ignored and
always-on events are forced enabled in the service. **Save semantics untouched this gate.**

**⚠️ SELF-SCOPE.** Prefs are saved for `$request->user()` — auth-resolved, **never a request id** — and the
AUDIENCE comes from the *route default*, not the request, so a user cannot set another audience's events. **No
save was submitted this gate:** `notification_preferences` is unchanged at **0 rows** (checksum
`d751713988987e9331980363e24189ce`), with 0 users carrying quiet hours.

**Tokens measured (regardless of render type): 0 contrast failures, before and after.**

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D30-1 | **Push presented as LIVE** — *"This device · Chrome"* with a green **On** dot — and its grid column **checked** on 8 rows. | No FCM/APNs, no device registration, no push transport. `CHANNELS` has no `push` member. | The **FCM / APNs** cutover (§H) + device-token storage. | 🚫 **REFUSAL-PRESERVE — DEFERRED.** A live-looking push toggle asserts a delivery that cannot happen. Live shows the card as **"not set up · Off"** and the grid cells as disabled, **never submitted** (no `name` attribute). |
| D30-2 | **SMS presented as LIVE** — a real phone number *"+351 91 244 1180"* with a **Verified** dot — and its column checked on 4 rows. | No Twilio, no provider, no verified-number store. `CHANNELS` has no `sms` member. | The **Twilio** cutover (§H) + number verification. | 🚫 **REFUSAL-PRESERVE — DEFERRED.** ⚠️ Note the compounding claim: the prototype does not just enable SMS, it shows a **verified number**, which asserts a verification flow that has never run. |
| D30-3 | A **daily digest at 18:00** (Off / 08:00 / 18:00). | The digest stores a real `daily_summary` email preference but only Off / Morning; and **no digest job dispatches yet** (the live page says so). | A send-time column + the digest job. | **LOGGED — REAL GAP (small).** The live page is already honest that the preference is stored *"for when the digest job ships"* — do not add a second send-time that is equally undispatched. |
| D30-4 | Event rows with **no description**, and a shorter catalogue (12 rows). | — (live has 15 rows, each with a description sub-line) | — | **EXTRA-KEEP.** The descriptions say what actually triggers each notification; the extra rows are real wired events (`Work order resolved`, `Work order reassigned`, `Schedule published`). Trimming to match the mock would remove real, preference-able events. |

### Fixed this gate — layout, and two REAL accessibility defects

- **The design's two-column body** (matrix left, Quiet hours + Daily digest in a right rail) — it was one
  full-width stack — plus the design's **breadcrumb** and a **top-right Save**. The foot Save is kept as well:
  the matrix is long, and removing it would make a keyboard user tab back up the whole grid to submit.
- ⚠️ **WCAG 2.1.1 (Level A) — the page's only real controls were unoperable by keyboard.** `.vy-np-check`
  was `display: none`, which removes the input from the tab order **and** the accessibility tree. **Proven in
  the browser**: `focus()` left `document.activeElement` as `BODY`, and the label wrapper was not focusable
  either — so a keyboard-only user **could not change any notification preference at all**, on the page whose
  entire purpose is changing them. Now visually hidden but **focusable**, with a `:focus-visible` ring on the
  styled cell and a real `aria-label` per toggle ("Email me when: New booking"). 15/15 now in the tab order.
- ⚠️ **WCAG 1.4.1 — the "no provider" state rode on opacity alone.** The disabled Push/SMS cells were empty
  spans carrying only a `title`, which is not announced on a non-interactive element — **the same defect found
  on the feed at P29**. They now carry real `.vy-sr-only` text ("Push not set up" / "SMS not set up"), reusing
  the utility P29 added to the layout.
- ⚠️ **#30 — the new breadcrumb is GATED.** `/app/settings` requires `properties.manage`, but **this page has
  no permission gate** (it is self-only), so most users who can set their own preferences cannot open Settings.
  An ungated crumb would have invited a 403. It links only under `@can('properties.manage')` — tested **both
  ways** (Group Admin: link present and the route opens; Front Desk: no link and the route 403s).
- **Channel cards stack at 1100px** — below that the "not set up" tag and the email address wrapped mid-phrase.

### ⚠️ Realm-wide note

The P29 1.4.1 finding still stands at `resources/views/livewire/owner/notifications.blade.php:33` and
`resources/views/components/notifications/bell.blade.php:46` — still open.

⚠️ **The keyboard defect, by contrast, was swept and is NOT realm-wide.** I checked whether other custom
controls hide their input the same way: **no other class matching `*check/radio/switch/toggle` uses
`display: none`**. The sibling pattern — `.vyms-switch input` (quiet hours, and the switches reused elsewhere) —
uses `position: absolute; opacity: 0`, which stays focusable and is correct. **`.vy-np-check` was the only
instance.** (Recorded because the sweep was actually run: an unverified "this is probably everywhere" would be
a guess dressed as a finding.)

---

## §31 — Operator **Email Templates** (`/app/email-templates`) · P31

**Prototype:** `docs/design/raw/Operator Email Templates.html`. **Live:** `EmailTemplateController@index` — a
**controller-rendered** page (the `c0ce419` raw-hex class), view `operator.email-templates`. The page itself
carries **no permission gate**; the writes do — `save` / `toggle` / `testSend` are all `can:properties.manage`.

**⚠️ The honesty flips on this page: email REALLY sends.** So the checks are merge-variables, the preview
source, and fabricated engagement — not deferral.

**Merge variables are REAL.** The catalogue's `vars` are `guest_name, first_name, unit, nights, reference,
balance` — every one a real reservation/guest field the send path fills, proven by the existing
*"resolves merge variables from the real reservation/guest"* test. **No token without a backing field.**

**⚠️ The preview source — verified, and it is NOT a #28 widening.** `EmailPreviewSample` deliberately builds the
Mailable over the tenant's **real latest reservation** rather than "Jane Doe" placeholders, so what an operator
previews is byte-for-byte what the trigger sends. I probed the rendered preview: it carries the guest **NAME**
("Demo Resident"), the property, dates, reference and money — and **0 email addresses, 0 phone numbers**. That
is exactly the name-only level the permitted operator surfaces already use, and it is tenant-scoped. **Recorded
as an observation, not a defect:** it is real customer data on an editor surface, so if the preview chrome ever
gains a "To:" line it would cross into CRM-only territory.

**Tokens + keyboard (both axes measured).** ⚠️ **Contrast: 7 failures**, all one class — the "● Active" pill at
**4.41** (raw `#1B7F5C` on `#E7F5EE`, 11px). The controller-page raw-hex risk, realised. **Fixed → 6.23.**
**Keyboard: 21 visible page controls, 0 unreachable, 0 without an accessible name**, and every control shows a
focus indicator. ⚠️ My first keyboard probe reported 12 "unreachable" controls — **a false positive**: they were
all inside the *closed* bell panel and user menu, which correctly cannot take focus while hidden. Scoped to
visible page controls, the page passes.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D31-1 | An **engagement stat on every card** — *"72% opened"*, *"81% opened"*, *"38% → review"*, *"64% settle early"*. | **No open/click tracking exists anywhere.** No pixel, no redirect wrapper, no per-send record. | Open/click tracking (a tracking pixel + link rewriting) — and the privacy decision that comes with it. | 🚫 **REFUSAL-PRESERVE (#39, founder decision A).** Every one of those figures would be invented, on the surface an operator would most trust for judging an email. The page states plainly *"Open/click tracking isn't collected, so no engagement stats are shown"* — the honest alternative to a blank space. Now guarded by a test. |
| D31-2 | **"+ New template"**. | The catalogue is a FIXED set of 7 keys, each bound to a real Mailable class and a real trigger. | A user-defined template needs its own Mailable, a trigger to fire it, and a send path. | 🚫 **REFUSED.** A "new template" with no trigger would be a document that never sends. Customization of the 7 real emails is the real capability, and it is fully wired. |
| D31-3 | A **"Draft"** status (the *"Come back next season"* card). | The model has `enabled` — Active or Off. There is no draft state. | A third state + a rule for what a draft does at trigger time. | **LOGGED — MINOR.** Off already means "does not send"; a draft would need to mean something different to be worth having. |
| D31-4 | A **"Come back next season"** winback/marketing template, and a **"Email + SMS"** channel chip on Pre-arrival. | **No `WinbackMail` exists**; every catalogue entry is `channel = Email`. SMS has no provider (P30). | A winback Mailable + a campaign trigger; Twilio for SMS. | 🚫 **REFUSED / LOGGED.** ⚠️ **If a winback is ever built it MUST go through `GuestMarketing::canReceive`** (real: consent + not anonymized + has an email) **and the unsubscribe path** — a marketing blast is exactly where a consent bypass would do real damage. The SMS chip is the P30 refusal again. |
| D31-5 | Cards showing only **6** templates. | — (live shows all **7**) | — | **EXTRA-KEEP.** Refund confirmation and Verify email are real, wired, customizable emails; dropping them to match the mock would hide two real sends. |

### Fixed this gate

- **The design's card grid** with thumbnail headers (it was a flat list of rows), the **breadcrumb**, and the
  **"Preview emails"** action — which links `app.email-previews`, **a real gallery route that existed and was
  never linked from this page** (the same "wired but unrendered" shape as P27's Full-report link).
- **The status toggle became the design's switch** — same form, same guarded route, restyled; now
  `role="switch"` + `aria-checked` + a real `aria-label`, with the **word** Active/Off still shown separately
  (1.4.1). Restyled, not rewired.
- **The "● Active" pill 4.41 → 6.23.**
- ⚠️ **A defect I introduced and caught before it shipped:** the paused-card thumbnail tint started at
  `#8C837C`, where the white brand text measures **3.77**. No template is off in the demo, so it would only
  have appeared the day someone paused one. Darkened to `#7A716A` → **4.78**, verified by forcing the state in
  the DOM (nothing persisted).
- **Stale docblock** — it claimed "the 5 real guest emails" long after the catalogue reached seven.

### ⚠️ Realm-wide, logged not fixed

Plain links take the **browser-default blue focus ring** (`rgb(0,95,204)`) while buttons take the design's
accent outline. Functional and visible, so not a failure — but inconsistent with the design system. A single
`:focus-visible` rule in the operator layout would unify it; not done here because it is a realm-wide style
change, not this page's.

---

## §32 — Operator **Email Editor** (`/app/email-templates/{key}/edit`) · P32

**Prototype:** `docs/design/raw/Operator Email Editor.html`. **Live:** Livewire `OperatorEmailEditor`. The page
itself has no permission gate; **both writes do** — `save` and `testSend` are `can:properties.manage`.

**⚠️ NOTHING WAS FIRED.** `email_templates` is unchanged at **0 rows**, checksum
`d751713988987e9331980363e24189ce`, before and after. **No mail was dispatched** — Mailpit still holds 500
messages with the same newest timestamp (`2026-08-09T11:17:59`, ~16h before this gate). The one interaction I
did run — typing into Heading to confirm the live-preview binding survived the restyle — is a **draft-only**
Livewire round-trip; the component has **no mutating Livewire action**, and a reload discarded it with the
checksum unchanged.

**Merge variables REAL:** the palette renders the catalogue's own `vars` for the key; each maps to a field the
Mailable merges. **Test-send recipient:** `$request->user()->email` — **there is no recipient input at all**, so
it cannot be aimed at a guest even deliberately. **Preview:** probed inside the iframe — **0 emails, 0 phones,
0 mailto/tel**; name-only ("Alex") plus property/unit/dates/reference. Exactly the P31 bar.

**Both a11y axes.** ⚠️ **Contrast: 5 failures** — the "● Active" pill at **4.41** (the *same raw `#1B7F5C`
pill the templates LIST carried at P31* — one visual concept written twice, so the P31 fix never reached here)
and the **variable-insert chips at 4.19** (accent-as-text, 11.5px). **Both fixed → 0.** **Keyboard: 15 visible
controls, 0 unreachable, all named.** ⚠️ My probe first reported 3 unnamed block checkboxes — **a false
positive**: they sit inside a **wrapping `<label>`**, which is a valid implicit accessible name; my check only
looked for `aria-label` / own text / `label[for]`.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D32-1 | A **"Previewing as Lukas · VYR-10430"** pill over the preview. | The identity is not single-valued — see D32-2. | D32-2 fixed first. | **DEFERRED deliberately — see D32-2.** Adding the pill today would assert one identity the preview does not actually have. |
| D32-2 | (implicit) One coherent sample booking across subject and body. | ⚠️ **The preview has TWO sample sources.** The **subject/prose** merge *hardcoded placeholders* (`OperatorEmailEditor::mergeSample` → `Alex Traveller`, `Apartment 2B`, `VY-2043`) while the **body** renders the **real** sample reservation (`EmailPreviewSample`). Measured live on one screen: reference **VY-2043** in the subject vs **R-F7ERPHS4** in the body; unit **"Apartment 2B"** in the prose vs **A2** in the stay table. | Make `mergeSample` read its values from the same `EmailPreviewSample` the body uses. | **LOGGED — REAL GAP.** ⚠️ It **undercuts the component's own docblock claim** that *"what you see IS the send (same Mailable, same blade — cannot drift)"* — the blade cannot drift, but the merged text does. **Send-time is unaffected** (the Mailable merges real values), so this is preview-only — but an operator proof-reading an email sees two references for one booking. |
| D32-3 | A **"Sofia's signature"** block and a **hero image band**. | The catalogue's real blocks for this key are Tap-a-star / Stay recap / Team signature. There is no per-user signature and no hero-image asset. | A signature entity + image upload per template. | **LOGGED — REAL GAP.** The live block set is real and each toggle genuinely removes its section from the send (existing test). |
| D32-4 | The editor **body as free rich text**. | Editing is field-scoped: subject, heading, message, button label, block toggles. | A rich-text/HTML editor plus sanitisation — ⚠️ and a rule for what arbitrary HTML does to a real guest email. | **EXTRA-KEEP / LOGGED.** Field-scoped editing is *safer* than free HTML in an email that really sends; it also guarantees the layout stays the tested one. Not a gap to close casually. |

### Fixed this gate

- **The design's action bar** — back-link, title + status, and **both actions moved to the top-right**. ⚠️ The
  buttons sit outside their forms and reach them via the HTML5 **`form=` attribute**: the same two real POSTs
  (guarded save · guarded self-only test-send), repositioned, **nothing rewired**. The foot Save is kept too
  (long form; a keyboard user should not have to tab back up), and the duplicate foot test button was dropped
  in favour of the sentence explaining what a test does.
- **Block toggles became the design's switches** — still real, **focusable** checkboxes with `appearance:none`
  (**never `display:none`** — the WCAG 2.1.1 defect found at P30) and a `:focus-visible` ring, with the
  wrapping `<label>` still supplying the name.
- **Contrast: the Active pill 4.41 → AA and the insert chips 4.19 → `--vy-accent-text`.** Inline error text
  moved off `--vy-accent` for the same reason.
- ⚠️ **A real responsive defect fixed:** the two-column breakpoint was `960px`, so at a 1000px viewport each
  column was ~341px — **the preview iframe clipped**, cutting the message mid-sentence and hiding the
  Stay/Unit/**Reference values** behind an inner scrollbar. Raised to `1180px`; measured after: single column,
  653px iframe, **not clipped**, reference visible.

### ⚠️ Realm-wide note

The `#1B7F5C`/`#E7F5EE` "Active" pill exists **twice** — templates list (fixed P31) and this editor (fixed
here). Two copies of one visual concept, and the first fix did not reach the second. A shared
`<x-status-pill>` would prevent the next divergence; not extracted here (it would touch other surfaces).

---

## §33 — Operator **Profile** (`/app/profile`) · P33

**Prototype:** `docs/design/raw/Operator Profile.html`. **Live:** Livewire `OperatorProfile`; writes go to
`OperatorProfileController` (`update` / `preferences` / `signOutOtherSessions`). **No permission gate** — it is
self-only by nature, inside `/app` behind `EnsureBackOfficeAccess`.

**⚠️ SELF-SCOPE — structural, not a check.** Every write resolves `$request->user()` and **there is no user id
anywhere in the request**, so there is no foreign id to tamper with; email stays unique *ignoring self*. The
audit records **which** fields changed, never their values. Already covered by *"ignores a request-supplied id
and only ever edits the actor's own row"*. **Nothing was saved this gate** — the user row checksum is
`6d4564c6315add9d447a2c3dda5a1967` before and after, preferences unchanged
(`density=0 tips=1 reduce=0 fmt=DD/MM/YYYY`).

**Password / 2FA are NOT here.** Change-password is `POST /app/security/password`
(`ChangePasswordController@operator`) and 2FA enrolment is `/app/security/2fa/*` — the **Security** page
(GATE 24). This page links to them and does not duplicate them, so the 12-char policy and the
sibling-session termination in `changePasswordTo` are untouched by this gate.

**Both a11y axes.** **Contrast: 2 failures**, both accent-as-text at **4.23** — the role chip (11px) and the
"This device" tag (10px). **Both fixed → 0.** **Keyboard: 17 visible controls, 0 unreachable, 0 unnamed**
(names checked via `aria-label`, `label[for]` **and** the wrapping `<label>`, per P32).

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D33-1 | **"Last sign-in — Today 08:14 · Faro, PT"** and **"This device — Chrome · macOS · Faro, PT · Active now"**. | ⚠️ **No geolocation capability exists anywhere** — no geoip/maxmind/ipinfo package, no config, no location column. `sessions` stores `ip_address`, `user_agent`, `last_activity` and nothing else. | An IP-geolocation service (and the privacy decision that comes with logging user locations). | 🚫 **REFUSED (#39).** ⚠️ **This is the worst class of fabrication on this page**: a city beside a sign-in reads as *security evidence* — it is exactly what a user checks to decide whether an unfamiliar session is theirs. Live shows the **real IP** and the real relative time. The sign-in TIME and the device string are genuinely derivable and already shown. **Guarded by a test — and the first version of that guard was VACUOUS** (the fixture had no `sessions` row, so the block rendered empty); it now seeds a real session and is mutation-proven. |
| D33-2 | **"Password — Changed 3 months ago"**. | There is **no `password_changed_at` column** on `users`. | The column plus a write on every password change. | 🚫 **REFUSED (#39).** The honest line is *"Change it on the Security page"*, pointing at the surface that really does it. Guarded. |
| D33-3 | A **camera badge** over the avatar (photo upload). | `users.avatar_path` exists, and the **OWNER** realm has real upload/remove routes (`owner.account.avatar`) — but the **operator realm has no avatar route at all**. | Port the owner's upload/remove to the operator profile. | **LOGGED — REAL GAP (small, and half-built already).** ⚠️ Rendering the badge today would be a control that does nothing. The live avatar shows honest **initials**. |
| D33-4 | A key-permissions list with ✓/✗ per capability. | — (live already renders the REAL spatie capabilities, read-only, plus "View full permissions") | — | **EXTRA-KEEP / at parity.** Live also states *"Access is managed by your workspace admin"* — true, and it explains why the list is read-only. |

### Fixed this gate

- **The design's hero** — a larger avatar, the role as a **chip**, and status as a **dot + word** (1.4.1),
  with the preferred name on its own line and the meta row beneath.
- **Preferences became the design's switches** and **Date format the design's segmented control** — ⚠️ still
  real, **focusable** `checkbox`/`radio` inputs (`appearance:none` / `opacity:0`, **never `display:none`** —
  the P30 defect), each with a `:focus-visible` ring and named by its wrapping label. **Cancel** added beside
  Save, per the design (a GET back to the page, discarding unsaved edits).
- ⚠️ **A label that read the OPPOSITE of its control.** The motion row was titled **"Interface motion"** with a
  tiny trailing *"reduce"* doing all the semantic work — but the input is `name="reduce_motion"`, so switching
  *"Interface motion"* **on** actually turned motion **off**. Relabelled **"Reduce motion"** with a matching
  description. **Behaviour unchanged; only the label now tells the truth.** Guarded, mutation-proven.
- **Contrast: the role chip and the "This device" tag, both 4.23 → AA.**

### ⚠️ Realm-wide — one shared class fixed for five views

The "This device" failure was **`.vy-tag.tone-confirmed` in the operator layout** (`color: var(--vy-accent)` on
its own 14% wash — 4.23 at the tag's 11px). It is a **shared** class, so the single edit reaches all five
consumers: `components/layouts/operator.blade.php`, `livewire/reservation-rack.blade.php`,
`livewire/operator/operator-security.blade.php`, `livewire/operator/operator-profile.blade.php`,
`livewire/operator/schedule.blade.php`. ⚠️ **Note the contrast with P32**, where the same "Active" pill was
written twice and the first fix reached only one copy: **a shared definition is fixed once; a duplicated one
has to be found twice.**

---

## §34 — Operator **Schedule PDF** (`GET /app/schedule/pdf`, `can:schedule.view`) · P34 — **THE LAST OPERATOR PAGE (42/42)**

**Prototype:** `docs/design/raw/Operator Schedule PDF.html`. **Live:** `ScheduleController@pdf` →
`PdfService->download('pdfs.operator-schedule', …)` (dompdf). A **document**, so this gate was mostly
verification.

**⚠️ THE ANTI-DRIFT LAW HOLDS — verified, not assumed.** The PDF calls
**`RosterWeek::build($property, $week, $dept)`** — the *same* builder the on-screen grid calls
(`OperatorSchedule.php:64`, `ScheduleController.php:217`, and `:314` for the PDF; **four callers, one
builder, no fifth computation**). Every figure comes from that shared source: `$roster->teamSize()`,
`shiftCount()`, `hours()`, `conflictCount`, `$cell['hours']`, and `ShiftType::hours()`. **No second
roster or hours calculation was introduced** to satisfy the design.

**⚠️ SCREEN == PDF — measured cell by cell** on the same week (3–9 Aug 2026, `?week=-1`):

| Staff | On-screen grid | In the PDF |
|---|---|---|
| Demo Accountant | Early 07:00–15:00 ×3, On leave ×2 | Early ×3 (Mon–Wed), Leave ×2 (Thu–Fri) |
| Demo Admin | Late 15:00–23:00 ×2 | Late ×2 (Thu–Fri) |
| Demo Staff | Day 09:00–17:00 ×5 | Day ×5 (Mon–Fri) |
| Rosa Fernandes | — | — |

3 + 2 + 5 = **10 shifts**, exactly the document's `SHIFTS 10` / `HOURS 80` (10 × 8h). Same week label, same
four staff, same ordering.

**#38 + #28:** the property is the **selected context** (`currentProperty()` → `PropertyContext`) — **there is
no property id in the PDF URL to point at another one**; the filename carries the property key. Now covered by
a test that rosters two properties in one org and proves each document carries only its own — **mutation-proven**
(removing the `where('property_id', …)` filter turns it red). **Real staff only:** the roster is
active-`Membership` ∩ back-office role — **"Demo Owner" (External Owner) does not appear** on the grid or in
the document, and no guest appears anywhere.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| D34-1 | An **"OPEN 3"** summary tile and an **"Open shifts · 3"** section listing unassigned shifts. | ⚠️ Partly stale: an **open-shift model now EXISTS** (`OperatorScheduleInteractiveTest` covers *"posts an open shift and fills it into a real Shift"*), but `RosterWeek` does not expose open shifts and the PDF omits them. | Surfacing the open-shift set through `RosterWeek` so the grid **and** the document read one source. | **LOGGED — REAL GAP (and the template's own note is now out of date:** it says *"no unassigned-shift model"*). ⚠️ **Do NOT query open shifts directly in the PDF blade** — that is exactly the D9-1 second-computation trap. It belongs on `RosterWeek`. |
| D34-2 | **"Posted by Marco Reyes · Front desk manager"** and *"provisional until published"*. | A **publish** action exists (*"publishes a week, stamping a publication"*), but the PDF does not read the publication, and there is no "posted by" identity on the document. | Reading the week's publication (who/when) through the shared builder. | **LOGGED — REAL GAP.** ⚠️ Also **partly stale**: the footer says *"there is no draft or published state"*, which was true when written. Until it reads the real publication, saying nothing is the honest option — the live footer now states plainly that the rota reflects the LIVE roster. |
| D34-3 | A legend of **colour swatches** mapping departments (Front desk / Housekeeping / Manager). | The document colours cells by **state** (working / leave / conflict / off), not department. | — | **DIVERGENCE — deliberate.** A department legend would mislabel what the colours mean. ⚠️ And a swatch legend is **colour-only**, which fails on a black-and-white print — the thing a rota is most often reproduced on. The legend now names every state **in words** instead. |
| D34-4 | A **highlighted "today" column**. | — (not rendered) | — | **LOGGED — COSMETIC.** ⚠️ Note it only makes sense when the printed week contains today; for a past or future week it would highlight nothing, so it needs that condition. |

### Fixed this gate (template + one presentation field; `PdfService` untouched)

- ⚠️ **The document still carried the PRE-AA palette — on paper, where a rota is actually read.** Measured on
  white: `#8A8079` **3.86** (labels, table headers, legend), `#A69A92` **2.74** (role labels, the **per-cell
  shift times**, the footer) and `#B8ADA4` **2.20** (off cells). The 2.74 grey was carrying real information —
  the times a person starts and finishes. Moved to the post-AA equivalents: **6.98** and **5.79**.
- **The design's document title + a REAL generated-at stamp** — *"Weekly rota / Demo Seaside Property · week
  of 3 Aug – 9 Aug 2026 · generated Mon 10 Aug 2026 · 05:35"*, produced in the **property's timezone**. A
  printed rota pinned to a wall has to say how old it is; the document had no production time at all.
- **A print-safe legend** — *"a cell shows the shift name and its times · **Leave** = approved time off ·
  **—** = not rostered · **"/ Leave!"** = a shift on an approved-leave day"*, closing with the reason: every
  state is written out **so it prints correctly in black and white**.
- **The design's two-part footer** (identity + generated-at left, document + week right).

### ⚠️ Verified, not changed

Every cell state already carried a **word** (`Early` / `Day` / `Late` / `Leave` / `—` / `/ Leave!`), so the
document was never colour-only. `PdfService`, the render pipeline and the roster query are untouched.

### ⚠️ A premise I had to correct

I first wrote the #30 test with **Housekeeping** as the unpermitted viewer — it returned **200**, because
`schedule.view` is **deliberately broad** (#43: anyone rostered should be able to read the rota). **9 of the
back-office roles hold it**; only **Accountant** and **Marketing Manager** do not. The test now uses Accountant
— had I not checked, the assertion would have been meaningless.

---

# ═══ OWNER REALM — begins here (32 prototypes) ═══

⚠️ **The operator realm is closed at §34.** Owner pages are numbered **§O1 …** so the two realms never
collide in this file. The method, the four buckets and the verdict vocabulary are unchanged.

## §O1 — Owner Dashboard (`/owner`, `App\Livewire\Owner\OwnerDashboard`) · OWNER 1/32

**Prototype** `docs/design/raw/Owner Dashboard.html` (decoded fresh; byte-identical to the
`/tmp/audit` copy, 85,155 chars). **Role** the real `External Owner` (`demo-owner@vyrobel-demo.test`).

The live page was already a close rebuild (invariant #54, the Owner-Overview exact-match pass), so this
gate is mostly **measurement**: the diff that mattered was not layout but **an unmeasured accessibility
surface and a chart whose geometry misreported its own figures**.

### ⚠️ The chart was misreporting real money — two independent geometry defects

Both were invisible to file-reading and only appeared when the **rendered computed value** was measured
(lesson 21).

1. **The tallest bar was silently compressed.** `.vy-ov-bar` is a flex item beside the month label, so a
   height above ~84% lost the remainder to `flex-shrink`: **September asked for 150px and rendered 126px —
   the current month, the largest and most-read bar, understated by 16%** while every smaller bar was
   exactly linear. Applying the wireframe's own **78% headroom** puts the max at 117px, below the shrink
   point, so the series is linear end-to-end (measured after: 39 / 57.7 / 41.3 / 45.2 / 63.2 / 78 %).
2. **The goal line did not sit at the value it labels.** Bars are positioned from the **column** bottom
   (which sits 24px up, above the month label + gap); the goal line was positioned from the **chart**
   bottom. The two never shared a baseline: a goal exactly equal to the best month drew *below* that
   month's bar, and at mid-range the error reached ~25% of the plotted height. Now offset by the same
   label block (`16% + pct × 0.78`), so a goal equal to the max lands exactly on the max bar's top —
   verified by measurement (goal 93.22% → 141px; September bar top → 141px).

⚠️ The prototype has the **same** baseline offset. This is a **lesson-25 case — do not reproduce the
prototype's deficiency** when reproducing it would state something false about real money.

### ⚠️ A €0 month still drew a bar

The bespoke Overview chart carried `max(4, $pct)`, the exact floor the shared `<x-charts.bars>` already
removed ("a genuinely ZERO bucket draws NOTHING; the old floor put a visible stub under a €0.00 label,
which reads as a small non-zero value"). The shared component's rule is now adopted verbatim here.
⚠️ The chart was **not** moved onto `<x-charts.bars>`: the Overview chart carries a 3M/6M/12M period
toggle, a hover tooltip and the goal line, none of which that component has — replacing it would have
**lost real capability**. Logged as a deliberate divergence, with the honesty rule shared.

### The backend this page implies but does not have

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| DO1-1 | **"Next payout €10,370 · Est. 3 Nov 2025"** — an estimated payout DATE, in the hero and again on the dark rail. | No scheduled-date field is read here. The payout **engine** now computes a real `nextRunDate` (`PayoutSchedule`, #71) and the **Payouts** page already shows it — the Dashboard does not. | Surfacing `PayoutSchedule::nextRunDate()` through the shared `OwnerNextPayout` rail so the Dashboard and the Payouts page cannot disagree. | **REAL GAP.** ⚠️ **Partly STALE refusal**: the component docblock still says *"a next-payout ESTIMATED DATE (no scheduled-date field)"*, which was true when written and is no longer. ⚠️ Do **not** query the schedule in the Dashboard — it belongs on the shared rail (the D9-1 second-computation trap). |
| DO1-2 | **"IBAN ···4821"** on the dark rail beside a **"Payout account verified"** notification. | The masked IBAN is real and CAP_PAYOUT-gated. There is **no verification state at all** — no column, no micro-deposit flow. | A real bank-verification state + rail (Stripe Connect / micro-deposits). | **ACCOUNT-GATED** (#58 already ruled this: a fake "Verified" on a bank account is dangerous, not merely dishonest). ⚠️ **The masking is STRUCTURAL** — `OwnerNextPayout` passes only `iban_last4` into `Iban::masked()`, so a raw IBAN **cannot** be emitted from this page. |
| DO1-3 | A per-bar **hover tooltip** and a **3M / 6M / 12M** period toggle over a 12-month series. | Present and real — but the toggle filters **client-side** over whatever finalized statements exist, so "12M" shows 6 bars when 6 statements exist. The prototype implies a fixed 12-month window with zero-filled months. | A zero-filled month series (a month with no finalized statement is a real €0, not an absent bar). | **REAL GAP — small.** ⚠️ Whoever builds it must keep the zero rule above: a zero-filled month must draw **nothing**, not a stub. |
| DO1-4 | **"+8% vs August"**, and per-unit occupancy **82% / 74% / 78%** with a "hit 82% occupancy · your best-performing unit" notification. | The MoM delta and per-unit occupancy are REAL (`OwnerOccupancyTrend`, owner-unit-keyed). The "best-performing unit" **notification** does not exist. | A derived best-performer signal on the shared `OwnerAttention` feed. | **REAL GAP — low value.** The figure already exists on My Units (Batch B); only the notification is missing. |
| DO1-5 | Upcoming stays with a **per-source dot** (Direct / Booking.com / Airbnb) and a per-source **filter with counts**. | Real, but every stay resolves to Direct/Corporate: **no reservation carries a `channel_id`** — the OTA sources can never populate. | Channel attribution on `reservations`. | **ACCOUNT-GATED — the D2-2 Channex root.** Same refusal as the operator realm; recorded so the backend phase does not "discover" it and invent a channel mix. |
| DO1-6 | A **"Guest refund processed · −€X · deducted from your September statement"** banner under the hero, driven by `localStorage`. | No refund-impact banner on the Dashboard. Refunds **are** real and **do** reduce the statement (they reconcile, #17/#27) — the owner is simply never told on this page. | A derived "this statement includes a €X guest refund" note from the real statement lines. | **REAL GAP.** ⚠️ It must read the **statement's own refund line**, never a client-side value — the prototype literally reads `localStorage`. |
| DO1-7 | The bell opens a **notification panel** with a per-item **read** state and "Mark all read". | Present and real (the 2026-07-26 shared `NotificationFeed` panel). | — | **NOT A GAP — verified present**, recorded so the sweep does not re-open it. |

### Fixed this gate (design + a11y; markup/CSS only — no service, guard, engine or figure touched)

- **The earnings chart geometry** (both defects above) + the zero-bar rule + the wireframe's **value-graduated
  vertical bar wash** (`--vy-bar-a`, derived per bar from that bar's own real pct). The live bars were a flat
  saturated horizontal peach→terracotta, so **every month read like the best one**; the current month keeps the
  solid accent, as designed.
- **The goal label became the wireframe's pill sitting ON the line** (it was plain text floating above the
  chart, colliding with the card header). The honest **"· above this range"** caption is kept.
- **The attention row now caps at 3 chips + the wireframe's dashed "+N more"** (it rendered every item;
  `OwnerAttention` can emit 5+). Nothing is hidden — the overflow is counted and links to the action centre.
- **The guest NAME was being truncated on 3 of 4 upcoming stays.** The wireframe gives the value slot
  `flex-shrink: 0` because it only ever holds a short money figure; ours also carries the honest
  **"Not yet attributed"** (#39), ~2× wider, which pushed `Demo Booker` to `Demo…`. Capped + allowed to wrap.
  ⚠️ The honest string is **preserved** — only the slot was resized. The name is the #28-relevant identifier.
- **18 of 23 measured AA failures closed** (see below).
- **A dead duplicate `.vy-delta--up/--down` pair was deleted.** The Overview block redeclared both at equal
  specificity, so the earlier (green) pair lost every property — two definitions of one concept, the P31/P32
  drift hazard. The comment claiming ".vy-delta is Overview-only" described a rule that was **not scoped**.

### ⚠️ Realm-wide finding — WHITE TEXT ON THE ACCENT GRADIENT fails AA (its own gate, NOT fixed here)

Real-pixel measurement (text hidden, background sampled from the rendered frame) leaves **exactly 5**
failures on this page, and they are **one concept**: white text on `linear-gradient(140deg,#E8703A,#C2410C)`.

| Element | Class | Measured |
|---|---|---|
| Brand mark "V" | `.vy-brand-mark` | **4.00** |
| Account avatar "DO" | `.vy-avatar` | **4.02** |
| Accent button "Approve" | `.vy-btn--accent` | **3.98** |
| Attention count badge | `.vy-ov-attn-count` | **4.00** |
| "Add tax details" CTA | `.vy-ov-resume` | **4.02** |

White on the gradient's **light stop** `#E8703A` is **3.08**; on the dark stop `#C2410C` it is 5.18; the
sampled mid-tone is **4.00**. **This crosses BOTH shells** — `.vy-brand-mark`, `.vy-avatar` and
`.vy-btn--accent` are shared primitives used across operator, owner and guest — and the operator realm never
measured it (its two a11y gates covered *text on the page gradient* `--vy-on-grad` and *accent-as-text on
glass* `--vy-accent-text`; **accent-as-BACKGROUND under white text was the gap between them**).

⚠️ **Deliberately NOT fixed in a page gate.** Two of the five are page-local (`.vy-ov-attn-count`,
`.vy-ov-resume`) and fixing only those would leave the page visually inconsistent *and* duplicate the concept
— principle 3 in reverse. It needs the same treatment `--vy-on-grad` and `--vy-accent-text` got: **its own
realm-wide a11y GATE**, measured across both shells. Sketch of the fix: a `--vy-grad-text` whose light stop is
deepened (a light stop of ~`rgb(204,98,51)` puts the mid-tone at **4.54**), applied only where the gradient
carries TEXT; decorative accent gradients (bars, dots, halos, the `is-last` chart bar) untouched.

### ⚠️ Carried, not fixed — the pre-AA grey `#9C9089` survives on three OTHER owner pages

`.vy-mu-stat-label`, `.vy-mu-uplabel`, `.vy-mu-calhead` (My Units) and `.vy-au-cta--wait` (Add Unit) still
hardcode the prototype's pre-AA greys. On **this** page the same literal measured **2.41–2.66** and is now
`--vy-muted` (5.43–5.97). **Not blanket-replaced** — each site needs its own composited background measured on
its own page, which is what those page gates are for. Recorded so they are not missed.

### ⚠️ Verified, not changed

- **#42 owner isolation is STRUCTURAL** — `Unit` / `OwnerStatement` / `Payout` / `OwnerMonthlyTarget` all use
  `BelongsToOwner`, whose `OwnerScope` constrains to the owner's ids and **fails CLOSED** (`1 = 0`) for a
  non-linked user. Live: 2 of 8 units visible. A cross-owner statement 404s.
- **#17 reconciles on the rendered page** — the six statement rows (885.60 + 721.60 + 516.60 + 467.40 +
  656.00 + 442.80) sum to **€3,690.00**, exactly the "Earned YTD" tile; the hero and the next-payout figure
  are the September row. "Net to you · €721.60" is the Σ of the stays that ARE attributed.
- **A test-coverage gap was closed** — `OwnerPortalIsolationTest` asserted unit **codes** across the owner
  boundary but nothing about **money**, and the Dashboard is a rollup whose most sensitive content is money.
  A new case pins both the perimeter (B's net never renders for A) and #17 (Earned YTD == Σ of A's own nets),
  with a `expect($aNet)->not->toBe($bNet)` guard against fixture vacuity. **Mutation-proven**: neutering
  `OwnerScope::apply()` turns it red.
- **Keyboard operability: 60 visible controls, 0 unreachable, 0 unnamed, 0 without a focus indicator**,
  including the filter menu's 5 items and the two states this gate introduced (the "+N more" chip is
  focusable and measures **5.62**; the goal pill measures **5.45**).

### ⚠️ A trap that bit, worth recording

`Goal {{ … }}@if (…) · above this range@endif` — **`range@endif`** puts `@` after a word character, so the
directive is **not compiled** (the CLAUDE.md `\B` rule). It left an unbalanced `@if` and the page 500'd
immediately. Replaced with a ternary expression. The documented failure mode is *raw source shipped to the
user*; here it was a hard 500 — **either way, the rule is real and it fires on the first careless edit.**

### ⚠️⚠️ TWO GUARDS THAT COULD NEVER FAIL — found while fixing a THIRD (principle 4, twice over)

The owner Dashboard's #39 guard (`assertDontSee('goal')` — *never an invented earnings goal*) went red on
this gate. It was a **false positive**: the page rendered no goal at all; the substring matched the new
`.vy-ov-goal` **CSS class name** in the layout's inline stylesheet. The established response to a
false-positive guard is *tighten it to its stated intent and PROVE it still fires* — so a shared
`assertDontSeeInContent()` was added that strips `<style>`/`<script>` before asserting.

**Mutating it exposed something far worse.** The new helper was written in the house style —
`expect($body)->not->toContain($needle, "…message…")` — and **a mutation that rendered the word straight
onto the page did not turn it red**. Pest's `toContain()` is **VARIADIC OVER NEEDLES**: the "message" is
read as a **second needle**, so the expectation means *"does not contain BOTH"*, which is true whenever
the message sentence is absent — i.e. **always**. The assertion cannot fail.

⚠️ **The same line exists in `assertNoRawBlade()` — the shared guard for the `word@directive` Blade trap,
called by 36 test files across the owner AND operator realms.** Mutation-proven on the owner Dashboard: a
literal `expenses@endif` was rendered into the page and **`assertNoRawBlade` stayed GREEN**. The guard that
CLAUDE.md names as the protection against that trap has never been able to fire.

⚠️ **This is not academic — the trap fired on this very gate.** `Goal {{ … }}@if (…) … range@endif` put `@`
after a word character, the directive was not compiled, and the page 500'd. Had it degraded to *raw source
on the page* (the documented failure mode) instead of an unbalanced `@if`, **nothing in the suite would have
caught it.**

Both helpers now use `PHPUnit\Framework\Assert::assertStringNotContainsString()`, which takes a real message
argument, and **both were re-mutated and observed to go RED**.

**Carry this forward:** in this codebase `expect(...)->not->toContain($needle, $message)` is an
**always-passing assertion**. It is worth grepping the whole suite for the pattern — every occurrence is a
guard defending nothing.

**The sweep was run — 8 dead assertions in 4 more files, several load-bearing:**

| File | Assertion | Guards |
|---|---|---|
| `GuestServiceTest` ×2 | *"The audit trail leaked PII"* / *"The domain event leaked PII"* | **#28** — guest PII must never be copied into `audit_events` / `domain_events` (no erase clears those tables) |
| `BackOfficeAccessTest` ×3 | *"leaked owner B's name / money / unit at {route}"* | **#42** — cross-owner leak on operator routes |
| `DesignTokenContrastTest` ×2 | *"--vy-muted / --vy-faint reverted to a pre-AA value"* | the realm-wide AA token floor |
| `MaintenanceAssigneePoolTest` ×1 | *"an External Owner is offered as a technician"* | the operator/owner boundary |

**All 8 were repaired and all 30 tests still pass** — so the underlying invariants genuinely hold; the
assertions were simply unable to fail. The two security-relevant ones were then **mutation-proven to fire**
(a deliberately-present needle turns each red, carrying its real message). ⚠️ Worth stating plainly: the
PII guard and the cross-owner guard were **green for their entire existence without ever checking anything**
— #28 and #42 were being asserted by nothing on those paths.

---

## §O2 — Owner Statement (`/owner/statements/{statement}`, `App\Livewire\Owner\OwnerStatementView`) · OWNER 2/32

**Prototype** `docs/design/raw/Owner Statement.html` (decoded fresh; byte-identical to `/tmp/audit`, 69,469
chars). ⚠️ **Two sibling prototypes were decoded but are NOT this route** and get their own gates:
`Owner Statement PDF.html` (the document — its live counterpart WAS verified here, see below) and
`Owner Archive.html` (the LIST at `/owner/archive`, which is what the sidebar's "Statements" nav points at).
**Role** the real `External Owner`.

**This is the owner realm's money document** — #17, #28 (IBAN) and #42 all land on one page, and it renders
twice: on screen and as a PDF.

### ⚠️ The PDF still carried the PRE-AA greys — the P34 lesson, repeating

Extracted from the generated PDF's own content stream (not from the template — the *rendered* fill colours),
measured **on white paper**: `#8A8079` **3.86** and `#A69A92` **2.74**. ⚠️ `#8A8079` was the `.neg` class —
**the negative money**, i.e. the commission and deduction figures — plus every per-line detail; `#A69A92` was
the whole footer. Both moved to the post-AA pair the operator Schedule PDF used: **6.98 / 5.79**.

⚠️ **The dark band's greys were deliberately NOT touched** (`#C9B8AC`, `#B9ABA0` on `#2A211C`): they measure
**8.21 / 7.05** there. Darkening them would be backwards — the same mirror-image trap as the screen's dark
payout rail below.

⚠️ **The same pre-AA greys remain in 15 OTHER PDF templates** (`owner-remittance`, `owner-booking-summary`,
`owner-annual-tax`, `owner-statement-history`, `folio`, `guest-vat-invoice`, `guest-credit-note`,
`guest-statement`, `guest-refund-receipt`, `arrival`, `operator-report`, `maintenance-costs`,
`owner-agreement`, `owner-agreement-amendment`, `owner-booking-confirmation`). **Every one is a document a
customer reads on paper.** They deserve one sweep, not fifteen page gates — logged, not touched here.

### ⚠️ `PdfService`'s watermark measures 2.70 on paper — SURFACED, deliberately not touched

`app/Services/PdfService.php:54` hardcodes `color:#a99b8e` for the export-audit footer stamped on **every**
owner PDF ("Downloaded by … on … · Confidential"). On white that is **2.70**. It is the line that records
*who took the document*, and it is the least readable thing on the page. **Not changed — the standing rule
for this pass is that `PdfService` is not touched.** It belongs to the PDF-greys sweep above.

### The backend this page implies but does not have

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| DO2-1 | Statement-history rows subtitled **"64 nights · 18 bookings"**. | Real, but only for the CURRENTLY-OPEN statement (the shared breakdown is computed for one statement). Other rows show their date range instead. | Persisting nights/bookings per statement at finalize, or a batched breakdown. ⚠️ **Do NOT compute it per row in Blade** — that is N money/metric computations behind a list, the D9-1 trap. | **REAL GAP.** |
| DO2-2 | An **"Est. 3 Nov 2025"** payout date on the dark rail, and "Paid {date}" once settled. | The *paid* date is real. The **estimate** is not shown. As at Owner-1 (DO1-1) the payout engine now computes a real `nextRunDate`, so the refusal is partly **STALE**. | Surface `PayoutSchedule::nextRunDate()` through the shared `OwnerNextPayout` rail — one source, so Dashboard / Statement / Payouts cannot disagree. | **REAL GAP** (same item as DO1-1 — one fix serves both pages). |
| DO2-3 | A **"Payment & ops fees — Card processing & platform"** line, always present. | The bucket is REAL (`payment_fee`, Wave C GATE 4) but **no writer posts one yet**, so it is a genuine €0 and the line is correctly omitted. | The live-payment fee writer. | **ACCOUNT-GATED** — and ⚠️ **do not "restore" this line to match the mock**: rendering a €0 fee line would assert a deduction that never happened. |
| DO2-4 | A **"Guest refund — Reservation VYR-10428"** line, driven by `localStorage`. | Refunds are real and DO reduce the statement, but no refund line renders here for this period. | Surfacing the period's real refund ledger lines as their own breakdown row. | **REAL GAP** — ⚠️ it must read the statement's own lines; the mock literally reads `localStorage`. |
| DO2-5 | Hero sub **"Seaside Villas · 3 units"** — the portfolio NAME beside the unit count. | The payload carries the unit COUNT but no property/portfolio name. | Passing the owner's property name(s) into the statement payload. | **REAL GAP — cosmetic.** The live line now shows the real unit count only; a portfolio name is never guessed. |
| DO2-6 | Statement history spanning **2023–2025** with year chips per year. | Real — the chips are built from the owner's actual statement years (one year in demo). | — | **NOT A GAP — verified real.** |
| DO2-7 | An **OTA/channel** contribution implied by the mock's revenue split. | The live page renders a real `OTA commission +€0.00 (0% of gross revenue)` line, flagged **Provisional** — genuinely zero because **no reservation carries a `channel_id`**. | Channel attribution. | **ACCOUNT-GATED — the D2-2 Channex root.** ⚠️ The €0 here is a REAL computed zero and was deliberately left rendered (it carries the provisional caveat); it is not the same as the fabricated-fee line in DO2-3. |

### Fixed this gate (design + a11y; markup/CSS only — no service, engine, money query or `PdfService` touched)

- **"More documents"** rebuilt to the wireframe's icon-tile rows (peach tile, title, tabular sub, the whole
  row is the download link) — it was a text block with a small "PDF" button beside it.
- **Statement-history rows became the wireframe's cards** (6px gaps, rounded, bordered, the current row
  accent-tinted). ⚠️ **Kept as a real `<table>`** — a money grid is tabular data and the header/row semantics
  beat the mock's `<div>` stack; only the LOOK was adopted. ⚠️ And scoped to a new `.vy-st-hist`, **not** to
  the shared `.vy-table`: this page's `<style>` is page-local, so a bare `.vy-table tbody tr` rule would have
  restyled a shared component on exactly one page.
- **Export pair** given the wireframe's divider + icons. ⚠️ **Export PDF was deliberately NOT promoted to the
  accent pill** — see the a11y note below.
- **Hero** split into the wireframe's two lines (period eyebrow + portfolio line).
- **Occupancy card**: bars to the design's 56px and the design's translucent wash; **a genuinely 0% month now
  draws nothing** (`max(6,pct)` + a 3px `min-height` had put a visible bar under a 0% month — the same floor
  the shared `<x-charts.bars>` already removed); and the series gained a **real accessible name**
  (`role="img"` + `aria-label` built from the same real values) — its per-month figures previously existed
  **only in `title`, on an `aria-hidden` container**, invisible to touch and assistive tech alike.
- **Screen contrast 8 → 3**, real-pixel measured.

### ⚠️ A REGRESSION I INTRODUCED AT OWNER-1, AND THE LESSON IN IT

`--vy-amber-text` was tuned at Owner-1 against **one** sampled background. On this page the same pill measured
**4.45–4.50** (marginal, one side of AA) — and on the **dark payout rail** it measured **1.87**, because
darkening a colour is exactly backwards on a dark panel. ⚠️ The rail was **already failing before Owner-1**
(`--vy-amber` = 2.72 there), so this was a pre-existing hole that my tint fix deepened.

Both fixed: the token is re-tuned against the **worst** sampled background (5.13–5.22), and a **dark-context
trio** now mirrors rather than darkens — `--vy-amber-on-dark` / `--vy-green-on-dark` / `--vy-muted-on-dark`,
measured **5.10 / 5.07 / 6.00** against the pill's own composited background. ⚠️ **All three tones of the
status pill were failing on that rail** (paid **1.77**, draft **1.65**), not just the one I had touched.

**The lesson: a token tuned to a single sample is a token that fails on the next page — and a "text" token
has no meaning until you say WHICH background it is text on.**

### ⚠️ The realm-wide white-on-accent-gradient gap RECURS here — and I nearly widened it

3 instances on this page (`.vy-brand-mark`, `.vy-avatar`, and the **masked-IBAN pill**), all **4.00**, all the
family surfaced at Owner-1 and awaiting its own realm-wide gate. **Not half-fixed.**

⚠️ Restyling the export pair to the wireframe (Export PDF as the accent gradient pill) **added a 4th
instance** — measured at 4.00 the moment it rendered. It was reverted to a ghost pill: closing a styling gap
by adding a known AA failure is a bad trade, and the accent treatment can be applied for free in the same
change that makes the gradient accessible. **Running realm accent-site total: Owner-1 fixed 2, Owner-2 fixed
0 and refused to add 1; the gradient family stands at 5 known instances (Dashboard) + 3 (Statement).**

### ⚠️ Verified, not changed

- **#17 reconciles, one source, on BOTH surfaces.** Screen: 1,080.00 − 194.40 + 0.00 = **885.60** = net
  payable = next payout = by-unit total (442.80 + 442.80). History: 6 statements → **€3,690.00** net total.
  The by-unit split and the PDF's "Performance by unit" both come from the ONE
  `OwnerStatementBreakdown::forStatement()`; the PDF's breakdown rows are the same `owner_statement_lines`.
  **No total is re-derived in Blade.**
- **SCREEN == PDF, verified figure by figure** from the PDF's own extracted text: net €885.60 · gross
  +€1,080.00 (8 nights · 2 units) · commission −€194.40 (18% of gross room revenue) · OTA +€0.00
  (provisional) · occupancy 13% · A1 5 nights/17%/€540.00 · A2 3 nights/10%/€540.00 · total gross €1,080.00.
  ⚠️ The commission rate is the **real agreement rate 18%**, never the wireframe's hardcoded 15%.
- **#28 — the IBAN is masked on BOTH surfaces** ("IBAN ···· 1000") and is **structurally** un-emittable raw
  (`OwnerNextPayout` passes only `iban_last4` into `Iban::masked()`). A regex for a full IBAN over the
  rendered page returns nothing. **No guest name, email or phone appears on either surface.**
- **#42** — the statement resolves through an owner+tenant-scoped `findOrFail` on a **string** prop (not a
  route-model-bound one, which Livewire would resolve before the scope middleware). Cross-owner → 404 on the
  page **and on the PDF route**, already covered non-vacuously by `OwnerStatementPdfTest` (two real owners
  plus a stranger).
- **Keyboard: 47 visible controls, 0 unreachable, 0 unnamed, 0 without a focus indicator.**
- ⚠️ **`toContain` sweep — negative recorded.** Every `not->toContain(...)` in the statement tests
  (`OwnerStatementViewTest`, `OwnerStatementPdfTest`, `OwnerStatementTest`) is **needle-only**, so none of
  them carry the Owner-1 always-passing defect. **No repair was needed here** — recorded so the sweep is not
  repeated.
- ⚠️ **A stale docblock corrected** (principle 5): `OwnerStatementView` still declared the by-unit split, the
  occupancy chart and the history CSV export "DEFERRED (visually stubbed…)". **All three have shipped and are
  real.** Nothing on the screen is stubbed.

---

## §O3 — Owner Payouts (`/owner/payouts`, `App\Livewire\Owner\OwnerPayouts`) · OWNER 3/32

**Prototype** `docs/design/raw/Owner Payouts.html` (decoded fresh; byte-identical to `/tmp/audit`, **168,102
chars** — the largest owner prototype). **Role** the real `External Owner`.

⚠️ **A method note, recorded honestly:** this prototype could not be screenshotted — every attempt exceeded
the browser tool's render budget (many stacked blurred-glass layers plus infinite spin/pulse animations), and
the same later happened to the LIVE page whenever a page-wide `color: transparent` measurement style was
injected. The design was therefore read from the **decoded source + its rendered text/computed styles**, and
the contrast sweep was re-cut to hide text on **only the candidate elements** rather than `*`. Both are
faithful; neither is a screenshot. The live before/after screenshots are real.

### ⚠️⚠️ D16-7 RECURS ON THE OWNER'S OWN PAYOUTS PAGE — in-flight money is invisible

The operator finding **D16-7** ("a payout in `processing` appears in none of the Payout-Run KPIs") is **not
operator-only**. Verified by construction here:

- `OwnerPayoutService::pay()` writes **`processing`** on the real-transfer path (`$result->pending ?
  'processing' : 'paid'`) — i.e. exactly what happens once Stripe is live.
- `OwnerPayouts::metrics()` builds every tile from **two** filters: `status === 'paid'` and
  `status === 'pending'`.
- The page renders **exactly three tiles** (Paid this year · Next payout · Pending), all fed by those two.

**So a payout that is genuinely in flight appears in NO tile at all.** The owner sees "Pending €0" and
"Paid this year" unchanged while their money is actually moving. `held` is in no tile either, though it at
least drives the on-hold banner.

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| DO3-1 | KPI band that always accounts for the payout in flight. | Tiles count only `paid` + `pending`; **`processing` and `held` fall through both**. | The bucket set beside `metrics()` — one place, not a fourth tile bolted on in Blade. | **REAL GAP — D16-7, owner-side.** ⚠️ Highest-value item on this page; it becomes wrong the day Stripe goes live, not before. |
| DO3-2 | **Projected** future payouts — "3 Dec 2025 ~€9,850 · October statement · Projected", built from a *trailing 3-month average*. | No projection exists. The live rail shows the REAL `nextRunDate` + an honest "the automated engine is not yet switched on". | A forecast model over finalized statements. | **RATIFIED REFUSAL (#39).** A projected payout amount is a fabricated figure on the most authoritative money surface an owner has. |
| DO3-3 | A **failed-payout alert** ("September payout couldn't be sent · Retry payout") and a **retrying** alert with a live ETA. | `failed` / `reversed` ARE real statuses and the stepper renders them, but there is **no alert banner** for either, and no retry control. | A failure banner off the real status + a retry action on the certified service. | **REAL GAP** for the banner. ⚠️ The **"Retry payout" button and the "est. {time}" retry ETA are refusals** — there is no owner-initiated retry rail and no ETA source. |
| DO3-4 | A **verification hold** ("We're verifying your account details… usually 1–2 business days", "Held since / est. release"). | The only real hold is the **tax-hold**, which the live banner states plainly. Bank verification is not modelled. | Micro-deposit / Connect verification state. | **ACCOUNT-GATED** — already ruled at #58: a fake bank-verification state is dangerous, not merely dishonest. The "est. release" date is a fabricated ETA on top of it. |
| DO3-5 | **Add bank account** modal — holder name, IBAN entry, *detected bank*, micro-deposit explainer, "Send micro-deposits & verify". | Adding an account is real (`/owner/payout-method`), but bank **detection** and **micro-deposit verification** are not. | A BIC/bank-lookup + a verification rail. | **ACCOUNT-GATED.** Observed READ-ONLY this gate — nothing submitted. |
| DO3-6 | A **payout receipt modal** (letterhead, amount hero, statement period, paid-to, bank reference, perforated total, Download PDF). | A real per-payout **remittance advice PDF** already exists and every row links to it. The modal preview, the **bank reference** and "Issued by … demo document" do not. | A settlement reference from the rail (only exists once live). | **PARTIAL — REAL GAP** for an in-page receipt preview; the bank reference is **ACCOUNT-GATED**. ⚠️ The live per-row link is the better artefact and is kept. |
| DO3-7 | Payout-day options **1st / 3rd / 15th / Last**. | Live offers **1st–5th business day**, which is what `PayoutSchedule` actually implements. | — | **NOT A GAP — the live set is the truthful one.** Rendering "15th"/"Last" would offer a cadence the engine cannot run. |
| DO3-8 | **"Reset to default"** for preferences, and a saved-state toast. | No reset control. | A reset action on the existing preference store. | **REAL GAP — minor.** Not built here (it is a mutation, not styling). |

### ⚠️ A separate REAL defect found while diffing: the payout history has no deterministic order

`OwnerPortalService::payouts()` orders by `created_at` DESC only. **All six demo payouts share an identical
`created_at`** (they were finalized in one pass), so the sort key does not distinguish any of them and the
resulting order is unspecified — as rendered it comes out **oldest-first**, the opposite of the design and of
what a money ledger should show. ⚠️ This is not merely a demo artifact: the **payout engine creates payouts in
a single run**, so ties are the normal case for anything created by `PayoutRunService`.

**What it needs:** a tiebreaker on the shared read (`->orderByDesc('created_at')->orderByDesc('id')` — ULIDs
are monotonic). **Verdict: REAL GAP — logged, not fixed.** It is an ORDER BY inside a shared read service
that the Dashboard, Statement and Payouts pages (and the CSV export) all consume; changing it is a backend
change, and it must be changed in ONE place so the page and its export cannot disagree.

### Fixed this gate (design + a11y; markup/CSS only — no service, engine, money query, bank record or preference touched)

- **The wireframe's summary line** under "Payout history" (`€2,804.40 paid across 5 payouts · €885.60
  pending`). ⚠️ It **reuses `$metrics`** — the same figures the tiles show — rather than re-summing the rows,
  which would be a second computation of the same total.
- **The Pending tile's state pill** and the **preferences Active/Paused pill**, both driven by REAL state
  (the same `$hold` the banner uses; the stored pause preference). Absent when there is nothing to say.
- **Contrast 21 → 6**, real-pixel measured across both viewport halves (120 + 79 elements). Fixed: the
  pre-AA `#9C9089` on all four preference labels (**2.66**), six "Statement" links + "Manage" +
  "Add one →" moved to `--vy-accent-text` (**4.22–4.45 → 5.02+**), and the shared `.vy-table` column header
  off `--vy-faint` (**4.47**, on the labels that say what each money column is).

### ⚠️ `--vy-green-text` re-tuned — the SAME lesson as the amber, one gate later

The green pill token set at Owner-2 was fitted to the Statement/Dashboard rows; on the Payouts table's
**warmer** rows the "Paid" pills measured **4.31–4.44**. Re-tuned against the worst background it meets:
**5.86–6.04** here, and **6.41–6.51** back on the cooler rows — **re-measured on the Statement page, which
stayed at its 3 known failures, so no regression.** Two gates running, a token fitted to one sample has been
wrong on the next page. **Tune to the worst case, then go back and re-measure the pages you already passed.**

### ⚠️ The white-on-accent-gradient family recurs — 6 instances, none added

`.vy-brand-mark`, `.vy-avatar`, `.vy-ov-iban` (the masked IBAN pill), `.vy-po-hold-cta` (Add tax details) and
`.vy-btn--accent` (Save preferences), all **4.00**. Still the realm-wide gap surfaced at Owner-1 and awaiting
its own gate. **Not half-fixed, and nothing new added to it.** Running realm tally: accent-as-TEXT fixed —
Owner-1 **2**, Owner-2 **0**, Owner-3 **8** (6 Statement links + Manage + Add-one) = **10**; gradient family
still open at Dashboard 5 / Statement 3 / Payouts 6.

### ⚠️ Verified, not changed

- **#17** — "Paid this year" **€2,804.40** = 442.80 + 656.00 + 467.40 + 516.60 + 721.60, the five real `paid`
  rows, to the cent; Pending €885.60 = the one real `pending` row; the dark rail's breakdown
  (1,080.00 − 194.40 + 0.00 = **885.60**) matches the statement it links to. One source
  (`OwnerPayoutService` rows + the statement's own lines); **no total re-derived in Blade**, including the
  new summary line.
- **#71** — every status shown is the REAL row status. The page carries a standing **test-mode banner**
  ("no real funds move"), and the schedule card states plainly that the automated engine is not switched on.
  Nothing claims a live transfer.
- **#28** — the destination is masked (`IBAN ···· 1000`), **structurally** un-emittable raw; no guest data.
- **#42** — owner-scoped throughout; the remittance route is per-payout and owner-scoped (a cross-owner id
  404s, already covered by `OwnerPayoutsViewTest`). Year filter + export ride GET (#20).
- **READ-ONLY honoured** — nothing was submitted. Verified after the gate: the bank method's `updated_at` is
  still the seed timestamp, **no payout-preference row exists at all**, and the payout statuses are unchanged
  (`pending` + 5 × `paid`).
- **Keyboard: 58 visible controls, 0 unreachable, 0 unnamed, 0 without a focus indicator, 0 `display:none`
  inputs** (the segmented pickers use real radios).
- ⚠️ **`toContain` sweep — negative recorded**, as at Owner-2. Both `not->toContain(...)` calls in the payout
  tests (`OwnerPayoutMethodTest`, `OwnerPayoutsViewTest`) are **needle-only**; neither carries the Owner-1
  always-passing defect. No repair needed.
- ⚠️ **A stale docblock corrected** (principle 5) — the class comment declared
  `Payout.status ∈ pending/paid/failed/reversed`, omitting **`processing` and `held`**. That is the *same
  blind spot* that produces D16-7, written down.

---

## §O4 — Owner Rates (`/owner/rates`, `App\Livewire\Owner\OwnerRates`) · OWNER 4/32

**Prototype** `docs/design/raw/Owner Rates.html` (decoded fresh; byte-identical to `/tmp/audit`, 46,216 chars).
**Role** the real `External Owner`; the route carries `$capActions` (CAP_ACTIONS).

### ⚠️ THE PROMPT'S PREMISE WAS INVERTED — and the code and the wireframe agree with each other, not with it

The gate brief described this page as "the GUARDED path: the owner proposes, `RateProposalService` validates
against the guardrail". **The real direction is the opposite**, verified in the source:

- `RateProposalService::propose()` is the **OPERATOR** side. The guardrail constrains what the **manager** may
  propose — below `floor_minor` / above `ceiling_minor` is **rejected**, and a change within
  ±`auto_approve_pct` **auto-applies**; anything larger stays `pending` for the owner.
- The **owner** side of that service is `approve()` / `decline()` (and this page's built `counter`).
- The owner's own pricing here is a **DIRECT apply** — `owner.rates.apply` → `OwnerRateController` →
  the certified `OwnerRateService`. It is live immediately; it is not a proposal.
- The owner **CONFIGURES** the guardrail (`owner.rates.guardrails`) — i.e. the owner sets the limits their
  manager works within.

⚠️ **The prototype's own header says exactly this**: *"Review the pricing your manager proposes and set the
limits they work within."* The brief's framing was the one thing out of step. Recorded because the wrong
mental model would have produced a "propose" flow that does not exist and a guardrail pointed at the wrong
actor.

### ⚠️ Fixture truth first (the guarded/unguarded split, verified before asserting anything)

- **`RateGuardrail` rows in the entire database: 0.** So the demo owner has none — the P22 finding ("the
  guardrail silence is CORRECT — no `RateGuardrail` binds this owner") still holds, one realm later.
- **`rate_proposals`: 0** — the proposals card is genuinely empty, not stubbed.
- The owner's two units (A1, A2) share ONE room type, and **no foreign unit sits on it**, so it is
  exclusively owned and genuinely priceable by the owner.

**⚠️ Therefore the LIVE page can only demonstrate the UNGUARDED case.** The guarded case was verified in the
source and in `RateProposalTest`, which seeds **real** `RateGuardrail` rows (floor/ceiling rejection, dynamic
auto-approve, fixed mode) — so guardrail enforcement is **not** vacuously tested. No guardrail was seeded for
this gate: that would be mutating demo data to make a screenshot look richer.

### ⚠️ And the honesty question that matters — is the unguarded copy TRUE?

With **no** guardrail row, the card shows mode `Dynamic`, **±0%**, empty floor/ceiling, and says *"Every rate
your operator proposes needs your sign-off before it goes live."* Traced into `propose()`: with
`$guardrail === null` there is no floor/ceiling rejection and no auto-approve branch, so **every proposal
stays `pending`**. The sentence is **literally true**, not a comforting approximation. There is **no
"within policy" badge, no fabricated limit, and no implied protection** anywhere on the page. The second
note — *"Your operator can still set a rate directly"* — is also true (that is the operator's unguarded
`RateActionController` path). **This page passes the trap it was set.**

### The backend this page implies but does not have

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| DO4-1 | **"Where you sit"** — a comp-set average (€465), a position bar, and *"Priced 5% below market — room to grow"*. | No market-rate source of any kind. The live card shows the REAL average alone and says benchmarking arrives "once we connect a market-rate source. We won't show a comparison until it's real." | A market-rate / comp-set data feed. | **RATIFIED REFUSAL (#39) — market-data class.** A comp-set average and a "5% below market" verdict are exactly the authoritative-looking figures that must not be invented on a pricing surface. |
| DO4-2 | A **Peak** rate column beside Base and Weekend. | Base and Weekend are REAL (weekend is a real per-night uplift, Wave A/B). There is no Peak/seasonal TIER entity. | A seasonal rate-tier model. | **REAL GAP.** ⚠️ Do not render a Peak column off the max of the calendar — that would present a derived maximum as a configured tier. |
| DO4-3 | Per-proposal **rationale** ("Strong demand for Jul–Aug and your comp set is averaging €465") and a **season** chip. | `rate_proposals` carries a real rationale/window and the live card renders both when present. The comp-set half of the mock's rationale has no source. | — | **PARTIAL — already REAL**, minus the market claim. Recorded so the sweep does not "add" the comp-set sentence. |
| DO4-4 | Occupancy colour-banded per row (green / amber / terracotta). | Live bands the same way — and always prints the **number**, so it is not colour-only (1.4.1). | — | **NOT A GAP — verified.** |
| DO4-5 | The guardrail's **floor/ceiling** as plain per-night euros. | Real and enforcing — but they are a **single owner-wide band**, not per room type or per season, while rates are per room type. | Per-room-type (or per-season) guardrail rows. | **REAL GAP — design-level.** ⚠️ Worth deciding deliberately: an owner-wide floor across differently-priced room types is a blunt instrument. |

### Fixed this gate (design + a11y; markup/CSS only — no service, engine, guardrail, rate or proposal touched)

- **The wireframe's TWO COLUMNS restored.** The page was a stack of full-width bands with one 2-up row, so
  the guardrail card — the thing the left column's proposals are judged against — sat below the fold of what
  it governs. Now: decide-column (proposals → current rates) + constrain/context rail (guardrails → where you
  sit), using the wireframe's own flex values (999 1 360px / 1 1 300px, min 280px), so it stacks without a
  media query.
- ⚠️ **A horizontal overflow the restructure exposed and closed**: the guardrail's floor/ceiling grid used
  `1fr 1fr`, whose default track min-width is `auto`, so the inputs refused to shrink in the narrower rail
  and pushed the document to **1537px against a 1440px viewport**. `minmax(0, 1fr)` — now 0 overflow.
- **Contrast 21 → 3** (the 3 being the known gradient family). ⚠️ **This page was the worst in the owner realm
  so far** because it hardcoded the prototype's pre-AA greys instead of the tokens: `#A69A92` ×10 and
  `#8A8079` ×7 measured **1.89–3.74**, on the copy that explains *what the guardrail actually does*. Plus
  `.vy-rt-eyebrow`'s `#9C9089`, the inactive segmented option, and two SHELL-level fixes below.
- **Two shell-level AA fixes** (both re-verified on the Payouts page, which stayed at its 4 known family
  failures): `.vy-topbar-sub` sits **directly on the page gradient**, not on glass — `--vy-muted` measured
  **3.80** there, so it moved to `--vy-on-grad` (4.68), the token made for that surface; and
  `.vy-nav-item.is-active` measured **4.49** on its own frosted pill → `--vy-accent-text` (5.34).
- **Keyboard: two real defects fixed.** `.vy-rt-in input` carried `outline:none` with **no** `:focus` styling,
  so the guardrail's floor/ceiling and the counter-offer rate had **no visible focus indicator at all**
  (WCAG 2.4.7) — a `:focus-within` ring now matches `.vy-input:focus`. And the **auto-approve slider had no
  accessible name** (its heading is a `<div>`, not a `<label for>`) — the control that decides how much
  pricing authority is delegated; it now carries an `aria-label` + a live `aria-valuetext`.
  ⚠️ **Two "unnamed" date inputs were a FALSE POSITIVE of my own probe** — they have proper sibling
  `<label for>` elements and my resolver only walked ancestors. Corrected the probe, not the page.
  Final: **37 controls, 0 unreachable / 0 unnamed / 0 without a focus indicator.**

⚠️ **Carried, not fixed:** the same `outline:none`-without-`:focus` pattern exists on `.vy-mu-rate-input input`
(My Units) and `.vy-au-money input` (Add Unit). Logged for those page gates rather than blind-fixed here.

### ⚠️ Verified, not changed

- **Every control POSTs the real OWNER route** — `owner.rate-proposals.approve|decline|counter`,
  `owner.rates.guardrails`, `owner.rates.apply`. **Nothing on this page touches the operator's unguarded
  `RateActionController`.**
- **READ-ONLY honoured.** Nothing was submitted. Verified after the gate: **`rate_proposals` still 0**,
  **`rate_guardrails` still 0**, and `rate_calendar`'s newest `updated_at` is still the seed timestamp
  (2026-08-09 07:17:59) — no rate was written.
- **#42 / #38** — a foreign `?room_type=` in the URL is ignored outright: "Double Room" appears **nowhere**,
  the selector offers only the owner's own exclusively-owned type, and the page falls back to it. A shared or
  foreign room type can neither be seen nor priced (`OwnerRateService::editableRoomTypes()`).
- **Rate figures real (#39)** — €180.00 base is the real rate; Weekend shows an honest "—" (no uplift set);
  22% is real occupancy; the average is the owner's real average. No recommended rate, no market figure, no
  trend without history.
- ⚠️ **`toContain` sweep — clean negative**, as at Owner-2/3: `OwnerRatesTest` and `RateProposalTest` contain
  **no** `not->toContain(...)` at all, so none can carry the Owner-1 always-passing defect.
- ⚠️ **A stale docblock corrected** (principle 5): `OwnerRates` still declared that Guardrails were
  "unmodelled … NOT rendered as real" and that proposals lived only in the attention feed. **Both shipped and
  are on this page.** Only the comp-set and the Peak tier remain unmodelled — and both stay refused.

---

## §O5 — Owner My Units (`/owner/units`, `App\Livewire\Owner\OwnerMyUnits`) · OWNER 5/32

**Prototype** `docs/design/raw/Owner My Units.html` (decoded fresh; byte-identical to `/tmp/audit`,
156,461 chars). **Role** the real `External Owner`; the route carries `$capCalendar`. ⚠️ `Owner Unit
Detail.html` and `Owner Add Unit.html` are **separate prototypes** for `/owner/units/{unit}` and the add-unit
flow — noted for their own gates, not touched here.

### ⚠️ A REAL figure-honesty finding: two different "August" figures for the same owner

The portfolio tile reads **"EARNED THIS MONTH · €541.60 · Net · August 2026"**. The Statements page shows
**"August 2026 · €721.60"**. Both are correct, and they answer different questions — traced to the exact
rows:

| Source | What it sums | August 2026 |
|---|---|---|
| The August **statement** | the 3 ledger lines it CLAIMED at finalize (`statement_id` stamped) | **72,160** = €721.60 — matching `net_payable_minor` exactly (#17 holds) |
| **"Earned this month"** (`earnedThisMonth()`) | **every** owner-ledger line with a `service_date` in the month | **54,160** = €541.60 |

The €180.00 gap is a **real, unstatemented expense dated 9 Aug** that the statement did not claim. Neither
figure is fabricated and neither is wrong.

⚠️ **But this is the WAVE-2 class exactly — *a figure under a label that means something else*.** An owner
who compares the two screens sees two "August" numbers £180 apart with nothing on either page explaining why.
**Logged, not re-worded**: choosing the right label (or whether the tile should exclude unstatemented items)
is a product decision, and inventing a caption here would be guessing at the intent.

### The backend this page implies but does not have

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| DO5-1 | Two "August" figures reconciled — the mock has one revenue number per unit and no statement cross-reference. | Nothing is missing; the two REAL figures use different bases (claimed-by-statement vs all-ledger-in-month) and neither page says so. | A decision on the tile's basis, then a caption or an aligned basis. | **REAL GAP — labelling (WAVE-2 class).** ⚠️ Do not "fix" it by changing a figure; both are correct. |
| DO5-2 | A photo **thumbnail band** on every unit card. | No unit photography model on this surface — the live card shows an honest accent band + house glyph. | Unit media on the owner card (the polymorphic `media` already exists elsewhere). | **REAL GAP — cosmetic.** |
| DO5-3 | **"Sort: Revenue"** and a per-unit filter chip row above the cards. | Neither exists; the live list renders every owned unit unsorted. | A client-side sort/filter over the already-loaded rows (must ride GET if it ever becomes server-side, #20). | **REAL GAP — small.** ⚠️ With 2 units it is invisible; it matters at portfolio scale. |
| DO5-4 | **"Approve all"** on the attention list, and an **"All units / All statuses"** dropdown pair. | Live offers All / Action / Review chips + "Mark all read" + "Snooze all" — no bulk *approve*. | — | **RATIFIED REFUSAL.** ⚠️ Bulk-approving money decisions (a rate change and a €340 expense in one click) is a dangerous affordance, not a missing feature. The live per-item Approve/Query is the safer shape and is kept. |
| DO5-5 | A **"Snoozed 0"** tab beside "Needs attention". | Snooze exists and is real (`owner_attention_states`), but there is no snoozed VIEW to return to. | A snoozed filter over the existing store. | **REAL GAP — small.** |
| DO5-6 | Per-unit **"Message guest"**. | Real — it opens the owner↔operator relay, not a guest thread. ⚠️ The mock's label implies messaging the GUEST; the live control messages the management team. | — | **VERIFIED — the live behaviour is the correct one** (#28: an owner has no guest channel). Recorded so the sweep does not "fix" the label toward the mock. |

### Fixed this gate (design + a11y; markup/CSS only — no service, engine, metric, unit or rate touched)

- ⚠️ **THE FOCUS-RING DEFECT LOGGED AT OWNER-4 FOR THIS GATE IS FIXED AND CONFIRMED LIVE.**
  `.vy-mu-rate-input` carried `outline:none` with **no** `:focus` rule — the inline nightly-rate editor had
  **no visible focus indicator at all** (WCAG 2.4.7). A `:focus-within` ring now sits on the wrapper
  (measured live with the editor open: `box-shadow: rgba(232,112,58,.1) 0 0 0 3px`). ⚠️ **Two more instances
  of the same pattern were found on this page and fixed**: the weekend-uplift / min-stay inputs (covered by
  the same wrapper rule) and the per-unit **message textarea** (`.vy-mu-msg-input:focus`).
- ⚠️ **Three inputs in the pricing editor had no accessible name** — nightly rate, weekend uplift, min stay
  (their captions are `<div>`s and unassociated `<label>`s). Each now carries a unit-specific `aria-label`.
  **Final: 65 controls with both editors open — 0 unreachable, 0 unnamed, 0 without a focus indicator.**
- **Contrast 29 → 10** across two viewport passes (90 + 94 elements), all 10 remaining being the known
  gradient family. Fixed:
  - ⚠️ **The three pre-AA greys logged at Owner-1 as "carried to the My Units gate"** — `.vy-mu-stat-label`,
    `.vy-mu-uplabel`, `.vy-mu-calhead` (`#9C9089`, measured **2.28–2.78** on the portfolio KPI labels and the
    per-unit occupancy captions), plus 4 inline copies in the view. **This was their page; they are closed.**
  - ⚠️ **The booked calendar DAY NUMBERS at 3.57** — `#7A2E08` on a half-booked cell's tint. These numerals
    are the **dates**, the one thing every cell must carry. Darkened to `#4A1B05`: **5.47** on the darkest
    non-white cell, 7.09–9.07 on lighter ones; white numerals still only on the deep cells.
  - The attention item tags (`--vy-amber` 2.91–3.00), the "Active" unit badge (4.13–4.26) and the
    "Block dates" button (3.94) → the tint-safe `--vy-amber-text` / `--vy-accent-tint-text`.
- ⚠️ **The dark "Best performer" panel PASSED** — the Owner-2 dark-context mirror holds; no light-tuned token
  was applied to it.

### ⚠️ Verified, not changed

- **#42 / #38** — the database holds **8 units** (`101, 102, 103, A1, A2, V1, V2, V3`); this owner owns
  **A1 and A2**, and a scan of the rendered page finds **no mention of any of the other six**. The rollup
  does not leak.
- **Per-unit figures real (#39), and the denominator is right** — avg occupancy **13%** = 8 booked
  unit-nights ÷ (2 units × 31 days = 62) = 12.9%; per-unit 19% (6/31) and 6% (2/31) agree. ⚠️ This is
  **not** the D3-4 denominator class: the divisor is the full month across the real unit count, not the
  spine's coverage. Revenue/nights/ADR are per-unit reals; a unit with nothing shows honest zeros.
- **#28** — no guest email or phone anywhere on the page (regex-checked), and **no IBAN at all** (owner
  banking stays on payouts/statements). "Message guest" opens the owner↔operator relay, not a guest thread.
- **READ-ONLY honoured** — the pricing editors were *revealed* (a UI toggle) but nothing was submitted.
  Verified after the gate: `units.updated_at` and `rate_calendar.updated_at` are both still the seed
  timestamp (2026-08-09 07:17:59), and **`owner_attention_states` is still empty** — no Approve, Snooze or
  Mark-read fired.
- **0 horizontal overflow at 1440×1000; 0 console errors.**
- ⚠️ **`toContain` sweep — clean negative** (fourth gate running): the only `not->toContain(...)` in the
  unit tests (`OwnerUnitRequestTest:175`) is **needle-only**.

---

## §O6 — Owner Notifications (`/owner/notifications`, `App\Livewire\Owner\OwnerNotifications`) · OWNER 6/32

**Role** the real `External Owner`. ⚠️ **This gate closes the operator realm's last still-open P29 item.**

### ⚠️ PREMISE CORRECTION — `Owner Notifications.html` is NOT the feed

The brief called this prototype "the owner's self-only feed". Decoded fresh (byte-identical to `/tmp/audit`,
35,238 chars), it is unambiguously the **PREFERENCES** page: breadcrumb *"Settings / Notifications"*, the
subtitle *"Choose what we tell you and where…"*, a channels block (Email `amara@…`, SMS `+353…`), a
**per-event Email/SMS matrix**, quiet hours and a Save button. There is no feed, no row, no unread state in it.

- The prototype therefore belongs to **`/owner/notification-preferences`** (`OwnerNotificationPreferences`) —
  which is also what `OwnerNotificationsPageTest` actually tests. **It gets its own gate.**
- **`/owner/notifications` (the live FEED) has no prototype of its own** — it is a live-only surface built
  during the Phase-3 notification rebuild. Recorded so a later gate does not "discover" the mismatch and
  restyle the feed into a preferences page.
- ⚠️ Amusingly the live feed already carries the prototype's subtitle verbatim in its footer ("Choose what we
  tell you and where in Notification preferences…"), which is presumably how the two got conflated.

**Second correction, smaller:** the brief's "another user's notification → 404" is not the mechanism. The feed
is **structurally self-scoped by its query root** — `NotificationFeed` reads `$user->notifications()`, so a
foreign row is never addressable to 404 in the first place. Verified live: the database holds **242
notifications across 5 notifiables** and this owner's page renders exactly **their own 6**.

### ⚠️ THE P29 REALM-WIDE ITEM IS NOW CLOSED — on both surfaces

Operator GATE 29 fixed the colour-only unread state on the operator notifications page but logged the
identical WCAG 1.4.1 defect as **still open** on two surfaces. Both verified still present at the start of
this gate, and both fixed:

| Surface | Was | Now |
|---|---|---|
| `livewire/owner/notifications.blade.php` | `<span … background:var(--vy-accent)" title="Unread">` | `aria-hidden` dot + `<span class="vy-sr-only">Unread</span>` |
| `components/notifications/bell.blade.php` **(SHARED — both shells)** | the same colour-only dot | the same fix, written **once** |

Plus: the ✓ control now carries `aria-label="Mark “{title}” as read"` (the GATE-29 pattern).

⚠️ **A third defect surfaced while fixing it, and it is the reason the shared fix would otherwise have
half-worked:** `.vy-sr-only` was defined **only in the operator layout**. The shared bell renders in BOTH
shells, so any visually-hidden text it emitted was hidden in operator and **plainly visible in owner**. The
rule is now defined in the owner shell too. *A shared partial cannot depend on a class only one shell
defines* — Principle 3 has a corollary.

**Verified live:** owner feed → **0** bare `title="Unread"`, 12 hidden "Unread" texts (6 page rows + 6 bell
rows), every ✓ named. Operator page → **0** bare `title="Unread"`, 31 hidden texts, and **at most one
`.vy-sr-only` inside any single row — no double-announcement**, so the operator side is unregressed and now
also covered by the shared fix.

**Mutation-proven three ways** (`NotificationPanelTest`, new case): restore the colour-only dot on the owner
feed → red; restore it on the shared bell → red; delete `.vy-sr-only` from the owner shell → red. ⚠️ The
third assertion was **initially vacuous** — `toContain('.vy-sr-only')` matched the class *attribute* in the
bell markup, so it passed with the CSS rule deleted. Tightened to assert the **rule** and re-mutated.

### The backend this page implies but does not have

| # | What is missing | Verified state | What it would need | Verdict |
|---|---|---|---|---|
| DO6-1 | The feed has **no prototype**; the "Owner Notifications" design is the preferences page. | The live feed is a real, self-scoped, category-filtered list with mark-read / mark-all. | A design for the feed, if one is ever wanted. | **NOT A GAP — recorded** so the mismatch is not "discovered" later. |
| DO6-2 | **Grouping** (Today / Earlier) and relative-time headers, which a long feed needs. | Rows carry a real relative time ("1 day ago") but are ungrouped. | Date bucketing over the existing feed. | **REAL GAP — small.** |
| DO6-3 | The prototype's channel block shows **SMS with a real number**. | SMS is **honestly pending** on the preferences page (no provider) — and the feed itself never claims a send. | An SMS provider. | **ACCOUNT-GATED** (belongs to the preferences gate). |
| DO6-4 | Per-notification **dismiss**. | Only mark-read exists; nothing claims dismissal. | A dismissed state on the feed store. | **REAL GAP — small.** |

### Fixed this gate (design + a11y; markup/CSS only — no service, feed or route touched)

- **The P29 1.4.1 closure** on both surfaces + `.vy-sr-only` in the owner shell + the ✓ `aria-label` (above).
- **Contrast 6 → 3** (all 3 remaining being the known gradient family). ⚠️ Both non-family failures were the
  **text-on-the-page-gradient** case again (the Owner-4 lesson): the footer sentence measured **4.43** with
  `--vy-muted` → `--vy-on-grad` (5.46), and its **"Notification preferences" link measured 3.17** with
  `--vy-accent` — on the gradient even `--vy-accent-text` only reaches 3.77, so it took
  `--vy-accent-tint-text` (4.52). Plus the row timestamp (`--vy-faint` 4.45 → `--vy-muted` 5.36).
- ⚠️ **A trap worth recording: Blade compiles component tags inside CSS comments.** Naming the shared bell
  with its `<x-…>` tag syntax in a comment inside the layout's `<style>` block rendered the entire bell into
  the stylesheet and **500'd every owner page**. Comments are not inert to Blade.

### ⚠️ Verified, not changed

- **Self-scoped (#28/#38/#42), structurally** — `NotificationFeed` reads `$user->notifications()`; the DB
  holds **242 notifications across 5 users** and this owner's page shows exactly **their own 6**.
- **Every notification real (#39)** — all six are `Your statement is ready`, each naming its REAL month and
  REAL net payable (€516.60 / €442.80 / €467.40 / €885.60 / €721.60 / €656.00), matching the statements to
  the cent. ⚠️ **No fabricated event**: no channel/OTA "sync" notification (the D2-2 door), and **no
  "payout sent to your bank"** claiming a live transfer while the engine is test-mode (#71).
- **References stay within the permitted surface (#28)** — the rows reference statements and money only; **no
  guest name, email or phone, and no IBAN**, appears anywhere in the feed.
- **One source for the count** — the page header reads "6 unread" and the bell badge reads **6**, both from
  `NotificationFeed`; the database agrees (6 of 6 unread). No second derivation.
- **READ-ONLY honoured** — mark-read / mark-all POST the real gated routes (`notifications/{id}/read`,
  `notifications/read-all`), **read but never fired**: the owner still has 6 notifications, all 6 unread.
- **Keyboard 40 controls / 0 unreachable / 0 unnamed / 0 without a ring**; 0 horizontal overflow, 0 console
  errors at 1440×1000.
- ⚠️ **`toContain` sweep — clean negative** (fifth gate running): the one call in `NotificationPanelTest` is
  needle-only.

---

## §O7 — Owner Notification Preferences (`/owner/notification-preferences`) · OWNER 7/32

**Prototype** `docs/design/raw/Owner Notifications.html` — the file Owner-6 identified as the PREFERENCES
design, not the feed. **Role** the real `External Owner`; prefs are per-user and self-scoped.
**Markup/CSS only — no service, storage or save semantics touched.**

### ⚠️ The #1 rule verified against the code — and one brief correction

**`NotificationPreferences::CHANNELS = ['in_app', 'email']`** — identical to the operator's. Therefore:

| Channel | State in the code | How the page shows it |
|---|---|---|
| **Email** | **REAL** — `allows()` genuinely gates the owner Mailables | a real, focusable, named toggle per event + a master switch |
| **In-app** | **REAL** in the model | ⚠️ **NOT a toggle here** — see the correction below |
| **SMS** | **STRUCTURALLY UNSETTABLE** — not in `CHANNELS`, so no row can ever exist (verified: 0 rows) | honest "not set up", disabled switch, no submitting input, and now a per-event text announcement |
| **Push** | **STRUCTURALLY UNSETTABLE** — same, and the owner page does not offer the column at all | absent |

⚠️ **Correction to the brief.** It stated "IN-APP / the feed is real — a real toggle." On the OWNER page
in-app is **deliberately not a toggle**: the wireframe's grid is Email + SMS, and the portal feed always
records ("statements are always kept in your portal regardless"). The in-app preference is **preserved
through hidden inputs** so dropping the column can never silently switch the feed off. ⚠️ Those hidden
inputs are **load-bearing** — `save()` reads `$enabled[$key]['in_app']` and defaults a missing key to
`false`, so removing them would silently disable every in-app notification. Recorded so nobody "tidies"
them away.

### ⚠️ Storage model verified (the WAVE-1 JSON hazard is structurally absent)

`notification_preferences` is a **typed per-(user, event, channel) pivot** (`user_id`, `event_key`,
`channel`, `enabled`), written with `updateOrCreate` keyed on all three, iterating **only** the audience's
catalog and **only** `CHANNELS`. **No JSON blob, no wholesale replace, no delete.** The
SettingsService merge-vs-replace hazard cannot arise here — the same ruling as Maintenance Settings.
`always_on` is forced `true` **at save time**, so the urgent bypass is enforced in the service, not merely
in the UI.

### The backend this page implies but does not have

| # | What the prototype shows | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| DO7-1 | An **SMS column with live per-event toggles** and a real number (`+353 87 555 4407`). | No SMS provider; SMS is not in `CHANNELS`, so a preference is **structurally unsettable**. | Twilio (or equivalent) + an `sms` channel in the model. | **ACCOUNT-GATED — deferred (Twilio, §H).** ⚠️ A live-looking SMS toggle would assert a delivery that cannot happen. |
| DO7-2 | — (the prototype has no push column) | Push is likewise absent from `CHANNELS`. | FCM/APNs + VAPID. | **ACCOUNT-GATED — deferred (FCM/APNs, §H).** Recorded because the operator page shows a push column honestly gated; the owner page omits it entirely, which is also honest. |
| DO7-3 | A **quiet-hours** window. | ⚠️ **Present and REAL** (Wave B · GATE 6) — a stored window that genuinely holds routine EMAIL, keeps in-app, and exempts always-on security. The prototype omits it; the live page keeps it. | — | **EXTRA-WORK — kept.** Not a gap in either direction. |
| DO7-4 | Per-event rows for events the product notifies on. | All six rendered rows are the REAL catalog (`statement_ready`, `payout_sent`, `performance_digest`, `terms_changed`, `unit_request_reviewed`, `account_security`). | — | **NOT A GAP — verified.** No invented event type. |

### Fixed this gate (design + a11y; markup/CSS only)

- ⚠️ **The per-event SMS cells announced their state with an `aria-label` on a NON-INTERACTIVE `<span>`** —
  the same unreliable pattern P29 found on the unread dot. The operator prefs page already uses real
  `.vy-sr-only` text for exactly this; **matched here** (the class only reached the owner shell at Owner-6,
  so this fix depended on that one). Each cell now announces e.g. *"Statement ready — SMS not set up"*.
- ⚠️ **The SMS column header measured 1.63 — the worst contrast on the page**, on the label of the column the
  owner most needs explained, and it said only "SMS", leaving the empty column unexplained inside the grid.
  Now `--vy-muted` **and** carrying "· not set up", so the state travels with the header.
- **Contrast 17 → 2** (the 2 being the known gradient family). The page hardcoded the pre-AA greys —
  `#A69A92` ×9 and `#8A8079` ×5, measured **1.93–3.32**, on every event description, the owner's own email
  address, "not set up", "Always on" and the guarantee sentence.
- ⚠️ **`--vy-on-grad` re-tuned (shell-wide, third token to need it).** Set at Owner-4 from ONE sampled
  gradient pixel (4.68 there), it measured **4.42** on a lighter point of the same gradient here. **A token
  placed on a gradient has a RANGE of backgrounds, not one** — tuned to the lightest sampled point:
  **5.01** here, **5.31** at the original sample. (Same lesson as the amber at O2 and the green at O3.)

### ⚠️ Verified, not changed

- **Every toggle state is a REAL catalog default**, not a fabrication: the six rendered states match
  `catalogFor('owner')` exactly (`performance_digest` off; `account_security` always-on; the rest on).
  ⚠️ **0 preference rows exist for any user**, so this is genuinely the fresh-owner default state.
- **Self-scoped** — the save writes `user_id = $request->user()`; the read is keyed on the same. A user can
  only ever write their own row.
- **READ-ONLY honoured** — the form POSTs the real gated route
  (`owner.notification-preferences.update`, audience defaulted server-side), **read but never submitted**:
  `notification_preferences` still holds **0 rows** for every user.
- **Keyboard: 33 visible controls, 0 unreachable / 0 unnamed / 0 without a focus indicator.** ⚠️ The
  **GATE-30 defect is NOT present** — the email cells are real `<button>`s with `aria-label`s, the master
  switch is a button with `aria-pressed`, quiet hours is a real checkbox. The 15 `display:none` elements are
  `input[type=hidden]` form fields (including the load-bearing in-app ones), not controls.
- **The SMS column never submits** — no `prefs[...][sms]` input exists in the form at all.
- 0 horizontal overflow at 1440×1000; 0 console errors.
- ⚠️ **`toContain` sweep — clean negative** (sixth gate running). All three calls in
  `OperatorNotifPrefsGateTest` are needle-only, and one of them correctly asserts a **CSS rule**
  (`.vy-np-check { display: none; }`) rather than a class name — the Owner-6 lesson, already applied there.

---

## §O8 — Owner Insights (`/owner/insights`, `App\Livewire\Owner\OwnerInsights`) · OWNER 8/32

**Role** the real `External Owner`. **Markup/CSS only — no metric, service or engine touched.**

### ⚠️ THE D3-4 PREMISE IS INVERTED FOR THIS PAGE — the owner path is structurally immune

The brief called the D3-4 denominator class "the central trap here". Traced through the code, it is an
**operator-path** defect that the owner path does not share:

| Path | `availableRoomNights` | Consequence |
|---|---|---|
| `PerformanceMetrics::forProperties()` (**operator**) | `Availability::sum('total')` — the **availability SPINE's coverage** | **D3-4**: a month with 3 spine rows divides by 3×capacity, so occupancy reads far too high |
| `PerformanceMetrics::forOwnerUnits()` (**owner**) | `count($unitIds) * $days` — **units × calendar days** | the full window regardless of spine coverage — **immune** |

Owner Insights uses `forOwnerUnits` **everywhere**, including `monthlyTrendForOwnerUnits` for both the
current and the prior-year series. ⚠️ **And the page SHOWS its denominator** — the occupancy KPI reads
"57 of 730 unit-nights", where 730 = 2 units × 365 days. That is the strongest available form of the D3-4
antidote: the divisor is on screen. **Verified, not assumed — recorded so the backend phase does not
"fix" D3-4 here and change a correct figure.**

⚠️ Consequently the **vs-last-year deltas on this page are honest**: they compare two figures computed the
same way, and are attached **only** to months where the prior year genuinely had occupancy
(`if ($lyOcc > 0)`), which is why Jan–Jul and Dec carry no delta while Aug/Sep/Oct/Nov do.

### ⚠️ A real defect found: four genuinely-0% months were drawing bars

`max(2, $m['occ_pct'])` on the occupancy-by-month chart put a visible bar under **Jan, Feb, Mar and Nov —
4 of 12 columns — all genuinely 0%**. Nov was the clearest tell: its own tooltip read *"Nov · 0% (on the
books)"* beside a drawn bar. **Third page with this floor** (Dashboard O1, Statement O2, here) — the rule
the shared `<x-charts.bars>` already encodes. Fixed on all three floors on this page (occupancy chart,
booking-pace track, lead-time buckets): a zero draws nothing; non-zero keeps a 2px minimum.

### The backend this page implies but does not have

| # | What the page shows / the design implies | What the live product lacks | What it would need | Verdict |
|---|---|---|---|---|
| DO8-1 | A **market/comp-set benchmark** ("you're X% below market"). | No market-rate source of any kind; the page never claims one. | A market-data feed. | **RATIFIED REFUSAL (#39) — market-data class**, consistent with O4's "Where you sit". |
| DO8-2 | A **rate recommendation / projected revenue** from a change. | The "Worth a look" panel gives REAL derived observations and says *"decisions stay yours"*; the dark CTA links to Rates without proposing a number. | A pricing engine. | **RATIFIED REFUSAL** (the #67 ruling — the owner approves operator proposals; nothing auto-proposes here). |
| DO8-3 | Per-**channel/OTA** breakdown of bookings. | Not rendered — **0 reservations carry a `channel_id`**. | Channel attribution. | **ACCOUNT-GATED — the D2-2 Channex root.** |
| DO8-4 | vs-last-year deltas across the whole year. | Real, but only where prior-year occupancy exists (8 of 12 months have none). | More history — it accrues naturally. | **NOT A GAP — the honest trend-without-history handling**, working as designed. |

### Fixed this gate

- ⚠️ **THE `--vy-on-grad` CHURN IS RESOLVED — measured, not guessed.** The token pair was re-tuned at O2 and
  O7 from *single sampled pixels*. Here it was measured **empirically over 7,502 background pixels sampled
  from behind every element that actually uses it** (sidebar nav + labels + topbar sub-line). Darkest
  composited point: **rgb(239,174,135)**. ⚠️ **Premise correction:** these are DARK text on the **page**
  gradient (`--vy-bg`, peach→grey + ambient orbs), not the accent gradient — so the worst case is the
  **darkest** point, not the lightest. Result: `--vy-on-grad` (76,66,60) already cleared at **5.15** and is
  unchanged; **`--vy-on-grad-soft` was FAILING at 4.26** and had never been re-tuned in its own right — now
  rgb(82,73,67) = **4.63**, the softest value that clears the worst case.
- **The four zero-bar floors** (above) and the accent-as-text CTA (`--vy-accent` 4.35 → `--vy-accent-text`).
- **The vs-LY delta arrows** now carry direction as a **word** for assistive tech (`aria-hidden` glyph +
  `.vy-sr-only` "up"/"down"), and their colours moved from raw hex to the tint-safe tokens.
- **Contrast: 3 → 1 (top) and 1 → 1 (lower)** — every remaining failure is the known gradient family.

### ⚠️ A CROSS-REALM FINDING — the OPERATOR shell's on-grad pair fails AA, badly

Applying the same empirical method to the **operator** shell (7,807 sampled pixels behind its on-grad text)
found its darkest composited point is **rgb(216,129,89)** — materially darker than the owner's, because its
ambient orbs sit under the sidebar. Against it:

| Token | Operator value | Measured at the worst case |
|---|---|---|
| `--vy-on-grad` | rgb(84,74,68) | **2.95 — FAIL** |
| `--vy-on-grad-soft` | rgb(88,78,72) | **2.77 — FAIL** |

⚠️ **The operator realm's own a11y gate (`93247e7`) claimed "4.74 / 4.93 operator" — measured against one
sampled pixel.** This is that gate's blind spot, demonstrated. Clearing AA there needs roughly
**rgb(51,45,41)** — a *visibly* darker sidebar label across all 42 completed operator pages.

**LOGGED, NOT FIXED — it needs its own gate.** Changing a closed realm's shell inside an owner page gate
would ship a visible design change across 42 pages without their own verification. It belongs with the
white-on-accent-gradient family as a scheduled realm-wide a11y gate.

### ⚠️ Verified, not changed

- **#42/#38** — the page names **A1 and A2 only**; a scan for the other six units in the database
  (`101, 102, 103, V1, V2, V3`) finds **none**. Every metric runs through `forOwnerUnits($unitIds …)`.
- **One source** — every figure comes from `PerformanceMetrics` / `BookingCadence`, the same helpers the
  Dashboard and My Units use. **Nothing is re-derived in the Blade.**
- **#28** — no guest email or phone (regex-checked) and no IBAN; the page is about units and money only.
- **Chart a11y** — the series are named in a legend (*Achieved* / *On the books*), each bar carries a
  `title` with its month, value and prior-year figure, and the delta now carries a word. Not colour-only.
- **Keyboard: 31 visible controls, 0 unreachable / 0 unnamed / 0 without a focus indicator.**
- **No export exists on this page** — nothing to fire; nothing was mutated (read-only by construction).
- 0 horizontal overflow at 1440×1000; 0 console errors.
- ⚠️ **`toContain` sweep — clean negative** (seventh gate running): `OwnerAnalyticsTest` contains none.

---

## §O9 — Owner Add Unit (`/owner/add-unit`, `App\Livewire\Owner\OwnerAddUnit`) · OWNER 9/32

**Prototype:** `docs/design/raw/Owner Add Unit.html` (decoded fresh). **Role:** `External Owner`
(`EnsureOwnerCapability:actions` — co-owner only). **Write:** `POST /owner/add-unit` →
`OwnerUnitRequestController@store` → `OwnerUnitRequestService::submit()`.

### ⚠️ The brief's premise, corrected — this is NOT a create

The gate was briefed as "a CREATION flow". **It is not.** An owner **cannot** create a unit anywhere in the
product. The wizard files a **`unit_requests` row** (`status = pending`) and creates **no** unit, room type,
rate row or availability — the operator reviews it and creates the real unit as *their own* action
(`/app/unit-requests` → `UnitRequestActionController@approve`). The page's own copy says so
("Submit for review", "Pending review", "once … approves it"), and `OwnerUnitRequestService` is explicit:
*"It creates NO unit."* The read-only discipline still applied in full — the request itself is real work on
an operator's desk — but the row it would have written is a request, not inventory.

### 🔴 REAL DEFECT FOUND AND FIXED — the wizard could be submitted from step 1 by pressing Enter

The single most consequential finding of the gate, and it is a **markup** defect, so it was in scope.

`Enter` inside any text field triggers **implicit form submission** against the step-4 submit button — *even
while step 4 is hidden*. From **step 1**, that fires the real POST. Server-side it would have **passed**:
`store()` requires only `requested_name` and `requested_kind`, and the kind is pre-selected, so the request
files with a name and four silent defaults (1 bed / 1 bath / sleeps 2 / min 2 nights), no rate and no photos
— landing on the operator's desk as a genuine pending request the owner never meant to send. Keyboard users,
who are the likeliest to press Enter to move on, were the ones exposed.

- **Browser-proven both directions.** Before: `Enter` on step 1 → 1 submit attempt (caught by a capture-phase
  guard installed for the probe, so nothing was ever sent). After: step 1 → **0**; step 4 → **1** (the real
  path still works). Regression test added and **mutation-proven** — deleting the attribute fails it.
- ⚠️ **A probe of mine misread this once**: `document.querySelector('[x-data]')` matched the **shell's**
  Alpine root (the notification popover), so setting `step = 4` there left the wizard on step 1 and made the
  fix look like it over-blocked. The fix was right; the probe was wrong. *Verify the probe before the page.*

### Fixed on this page (design / markup / styling only)

| # | Item | Before → after |
|---|---|---|
| 1 | **Implicit Enter submission before the last step** | fires a real request from step 1 → prevented unless `step === 4` |
| 2 | **5 inputs named by placeholder only** (`requested_name`, `location`, `street_address`, `nightly_rate`, `cleaning_fee`) — `.vy-au-flabel` was a `<div>`, never associated | → real `<label for>` (also makes the label clickable) |
| 3 | **8 icon-only stepper buttons with NO accessible name** (beds/baths/sleeps/min-nights ±) | → `aria-label` each; SVGs `aria-hidden` |
| 4 | Stepper VALUE unannounced on change | → `role="status"` + `aria-labelledby` its row name |
| 5 | Chip selection was **colour-only** and unexposed to AT (14 chips) | → `aria-pressed` on all 14; the two chip groups get `role="group"` + a name |
| 6 | **`.vy-au-money` had no focus ring at all** (`outline:none`, nothing else) — the O4-logged defect **for this page** | → `:focus-within` ring on the wrapper (measured live) |
| 7 | **File input's ring invisible** — it is `opacity:0` over the tile, so its own ring is invisible too | → `.vy-au-drop:focus-within` ring + solid border |
| 8 | File input loses its name once a photo is chosen (label text swapped for `<img alt="">`) | → persistent `aria-label` on the input |
| 9 | Error block showed **only the first** message, no announcement | → `role="alert"`, **all** messages, led by a word (not the accent rule alone) |
| 10 | Progress rail announced as 4 anonymous items | → `aria-hidden` ("Step N of 4" already says it in words) |

**Contrast — 15 AA failures → 6, icons 2 → 0** (real composited pixels, 101 text + 51 icon candidates,
all four steps revealed):

| Element | Was | Now | Token |
|---|---|---|---|
| `.vy-au-flabel` ×6 | 4.13–4.41 | **6.21–7.60** | `--vy-on-grad-soft` |
| `.vy-au-back` | 4.36 | **5.44** | `--vy-on-grad-soft` |
| `.vy-au-preview-label` | 4.10 | **6.22** | `--vy-on-grad-soft` |
| `.vy-au-preview-cap` | 3.87 | **5.86** | `--vy-on-grad-soft` |
| `.vy-au-chip.is-sel` | 3.64 | **5.18** | `--vy-accent-tint-text` |
| `.vy-au-cta--wait` ("Name your unit to continue") | 1.79 | **5.71** | `--vy-on-grad-soft` |
| `.vy-au-drop svg` (1.4.11) | 1.81 | **≥3** | `--vy-accent-text` |

⚠️ **The failing labels were `--vy-faint` / `--vy-muted` used on the PAGE gradient.** The fix is *not* to
retune those shared tokens — the realm already has `--vy-on-grad` / `--vy-on-grad-soft` built for exactly
this background. Reaching for the right existing token beat changing a token 8 closed pages depend on.

⚠️ `.vy-au-cta--wait` is a **disabled** control, so 1.4.3 formally exempts it — but it is the only text that
says *why* you cannot continue, so it was made readable anyway. The flat wash, absent shadow and
`cursor:default` still read as inactive.

### 🚫 Refusals preserved (do NOT "fix")

- **The prototype's "Location" chips** (`townList`, 3 named towns) — the product has **no town catalogue**;
  `location` is a free string column. Chips would be a fabricated set of supported places. The live page
  keeps a free-text **Town / city** field. **RATIFIED REFUSAL.**
- **The prototype's hardcoded "15% commission"** — the live panel reads the owner's **real** active
  `ManagementAgreement` (demo: **18%**), and with no agreement shows *"Your team confirms the commission"*
  rather than any percentage (tested, #39).
- **No earnings projection.** "You keep about €148 per night after commission" is arithmetic on the rate the
  owner just typed × their real rate, hedged with *about* — not a forecast of what the unit will earn.
- **No "looks good ✓" / no client-side validation theatre.** The only gate is `canNext1` (a name exists);
  everything else is validated **server-side**, and every server message is now shown.
- **No fabricated field or dropdown option** — see below; every field maps to a real column, and the four
  property kinds are the real `OwnerUnitRequestService::KINDS`.

### ✅ Extra work kept (beyond the prototype)

- **"Your requests"** — the prototype has no such section; hiding an owner's real pending/decided requests
  would be a regression. Owner-scoped, real statuses.
- **Photos really upload** — `media` rows on the request, ULID filenames, content-validated images, capped
  count and size (reuses the room-type upload posture). Not a placeholder.

### 📌 MISSING BACKEND / logged, not built

- **DO9-1 — `unit_requests.notes` is written by the backend but has no field in the wizard.** The controller
  validates it (`max:2000`) and the service persists it; neither the prototype nor the live page exposes it.
  A free-text "anything else we should know" box is the natural home. *Verdict: **REAL GAP** (small) — a
  column that exists and is unreachable from the UI.*
- **DO9-2 — no draft/resume.** Leaving the wizard loses everything typed; there is no draft state on
  `unit_requests` (`status` is pending/approved/declined only). The prototype implies none either.
  *Verdict: **REAL GAP** — needs a `draft` status or a client-side persist decision.*
- **DO9-3 — the owner cannot withdraw a request.** `UnitRequest` has no owner-side cancel path; only the
  operator approves/declines. *Verdict: **REAL GAP** — a state machine addition, not presentation.*
- **DO9-4 — the prototype's professional-photo offer ("our team can arrange a shoot — just ask") is copy
  only.** There is no booking/scheduling entity behind it; the live page keeps the sentence as an invitation
  to ask, which is honest. *Verdict: **RATIFIED REFUSAL** unless a real scheduling flow is built.*
- **DO9-5 — the drop tiles are file pickers, not drag-and-drop.** The prototype says "Drop your cover photo
  onto the preview card"; the live copy correctly says "Add a cover photo". Real DnD + a real preview-card
  drop target is unbuilt. *Verdict: **REAL GAP** (presentation-adjacent, but genuinely unimplemented).*

### ⚠️ Verified, not changed

- **The action is a REQUEST, not a create** — proven at the DB level: after a full wizard walk (all four
  steps, typing into every field, both money fields, Enter pressed on step 1 **and** step 4),
  `unit_requests = 0`, `units = 8`, `room_types = 3`, `media = 6`, `rate_calendar = 925` and the max
  `units.updated_at` is still the seed time `2026-08-09 07:17:59`. **Nothing was written.**
- **Every field maps to a real column (#39)** — name→`requested_name`, type→`requested_kind` (4 real KINDS),
  town→`location`, street→`street_address`, beds/baths/sleeps→`bedrooms`/`bathrooms`/`sleeps`,
  rate→`requested_rate_minor` + `currency`, cleaning→`cleaning_fee_minor`, min-nights→`min_nights`,
  amenities→validated against the **real** `amenities` table (`Rule::exists` — an unknown id is rejected,
  never silently created), photos→`media`. **No field the model doesn't store.**
- **#17** — money is parsed by `MoneyInput` into integer minor units; an unparseable amount is a validation
  error, never a silent drop.
- **#20** — one ordinary gated form POST (`@csrf`, `multipart/form-data`), never `/livewire/update`.
- **#38/#42/#30** — `EnsureOwnerCapability:actions`; the request files against the owner resolved by
  `user_id` (never a request id) and a non-linked user fails closed (404). Already mutation-proven by
  `OwnerTeamCapabilityTest` (view-only role → 403) and `OwnerUnitRequestTest` (own-requests-only; a stranger
  refused). **No cross-org create is reachable — the owner cannot create at all.**
- **#28** — the form is about the unit; no guest data anywhere, no IBAN (owner banking lives on Payout
  method, masked).
- **Keyboard: 0 unnamed controls** (was 13), every field/stepper/chip/upload operable with a visible focus
  indicator; `appearance` never `display:none` — the hidden steps are genuinely inapplicable, not
  unreachable controls.
- 0 horizontal overflow at 1440×1000; 0 console errors/warnings.
- ⚠️ **`toContain` sweep — clean negative** (eighth gate running): the four repo matches for
  `not->toContain(…,…)` are commas *inside a single argument* (a `route()` call, `'Faro, PT'`) plus one
  comment — no vacuous assertion remains.
- ⚠️ **Gradient family — surfaced, none added, none half-fixed.** 6 white-on-accent-gradient failures remain:
  `.vy-au-preview-cover-ph` **2.54** (the *worst* instance found anywhere in the realm so far),
  `.vy-au-cta` ×3 (3.93–3.95), and the shell's `.vy-brand-mark` / `.vy-avatar` (3.88 / 3.83). Still awaiting
  its own realm-wide gate.

---

## §O10 — Owner Unit Detail (`/owner/units/{unit}`, `App\Livewire\Owner\OwnerUnitDetail`) · OWNER 10/32

**Prototype:** `docs/design/raw/Owner Unit Detail.html` (decoded fresh). **Role:** `External Owner`
(`EnsureOwnerCapability:calendar` — co-owner + view-only). **Writes on the page:** three, all real, all gated.

### 🔴 REAL DEFECT — the availability calendar was mouse-only

**31 of the page's 36 interactive elements were unreachable by keyboard.** Every clickable calendar day was a
`<div>` carrying an Alpine `@click` with no `tabindex`, no `role` and no key handler — so *tap an open day to
block it* (a real write through `OwnerBlockService`) and *tap a booked day for its detail* could not be done
without a mouse. This is the GATE-30 class defect, at scale, on a control that moves inventory off-sale.

Fixed by making the interactive cells real `<button type="button">`s — which restores focusability,
Enter/Space activation and a focus ring with no JavaScript. **Browser-proven**: the day button takes focus,
activating it by keyboard sets `sel` and reveals the block composer, and **0 form submissions fired** (the
block itself was never sent). Regression test added and **mutation-proven** — restoring the `<div>` fails it.

A day's STATE also reached assistive tech only as a fill colour (the interactive cells' `title` said
*"Tap for detail"*, not *booked*). Each cell now names its date and state (`4 October 2026 — booked. Show
detail.`); the past cells carry an `.vy-sr-only` state word. ⚠️ Note honestly: this closes the **4.1.2 / AT**
gap. For the pure-visual **1.4.1** axis the three states already differ by luminance as well as hue (open
`rgba(255,255,255,.5)`, booked a peach tint, blocked `#3A2E27`), so colour is not the sole visual cue — the
design's own encoding is kept.

### Fixed on this page (design / markup / styling only)

| # | Item | Before → after |
|---|---|---|
| 1 | **31 calendar days keyboard-unreachable** | clickable `<div>` → real `<button>`; 31 → **0** unreachable |
| 2 | Day state exposed as colour + a misleading `title` | date + state named on every cell |
| 3 | **Two `max(2, $pct)` chart floors** — the **fourth** page with this bug | 7 genuinely-zero months now render **0px** |
| 4 | Status block: pill + labelled button squeezed the copy to 5 lines and **duplicated the header's status** | the prototype's compact **toggle switch** (a real gated POST, `role="switch"`, state still in words) |
| 5 | `.vy-ud-status--live` **3.20** — the design's tint assumes a card, but the pill sits on the page gradient | tint composited over a near-white plate → **4.63** (tint + text colour unchanged) |
| 6 | ★ rating glyphs `#E8A24A` at **1.85** ×4 — *the stars are the score* | `--vy-amber-text` → **6.07** |
| 7 | 6 accent-as-text sites (Calendar link, Agreement →, Open inbox →, Open → ×2, the month Net figure) | `--vy-accent-text` → 4.95–5.24 |
| 8 | `--vy-faint` on the page gradient (`.vy-ud-sync` 3.98, `.vy-ud-dow` 4.13–4.37, `.vy-ud-kpi-k/-s` 4.27–4.48) | `--vy-on-grad-soft` → **6.03–6.63** |
| 9 | Stale comment: *"weekend uplift NOT offered (unmodelled)"* directly above the editor that **does** offer it (real since Wave A/B) | corrected |

**Contrast: 25 AA failures → 6** (224 text + 27 icon candidates, hidden states revealed). Icons 0 failures
throughout.

### 🚫 Refusals preserved (do NOT "fix")

- **The prototype's "Guest messages" panel** — named guests, avatars, message snippets, a reply box. The live
  page renders the owner↔operator **relay** instead (*"message your management team"*, name-free, #28). A
  guest thread on an owner surface would breach the PII perimeter and does not exist as a feature.
- **The prototype's cleaner identity** — `{{ cleanerName }}`, initials, "Message", "Schedule a cleaning". Live
  shows the real `HousekeepingTask` rows **read-only** with *"Coordinated by your management team"*. No
  fabricated cleaner, no scheduling the owner cannot actually do (#39).
- **"Direct bookings saved you €X in commission"** — a fabricated `directRev × 0.15`. Still omitted.
- **Per-unit payout timeline** — payouts are per-STATEMENT / per-OWNER; a per-unit timeline is a fabricated
  attribution. The rail links the real Payouts, and says so.
- **Channel sync / "Share listing" / `lastSync`** — no owner-facing sync exists (account-gated Channex,
  D2-2). Live states *"Channel sync is managed by your operator"* rather than a fake synced badge (#37).
- **No occupancy delta** — the prototype's `{{ occDelta }}` is omitted rather than invented.

### ✅ Extra work kept (beyond the prototype)

- The **Attribution** table (room revenue / OTA cost / commission → net) reconciling to the cent, and the
  **Stays** table identifying bookings by their real `reference`.
- **"Your money"** rail card, and the honest note that payouts are portfolio-wide, not per-unit.

### 📌 MISSING BACKEND / logged, not built

- **DO10-1 — no occupancy delta vs the prior period.** The prototype shows one. `OwnerOccupancy` can produce
  a prior month, so this is buildable; it is omitted rather than faked today. *Verdict: **REAL GAP** (small).*
- **DO10-2 — the owner cannot schedule or request a cleaning.** The prototype offers "Schedule a cleaning";
  housekeeping is operator-managed and there is no owner-side request entity. *Verdict: **REAL GAP** — needs a
  request path (the Add-Unit request is the obvious precedent).*
- **DO10-3 — per-unit channel/OTA brand breakdown.** "Channel mix" uses the real `reservations.source` enum;
  an OTA *brand* split is unmodelled. **Channex-tied (D2-2)** — 0 reservations carry a `channel_id`.
  *Verdict: **ACCOUNT-GATED**.*
- **DO10-4 — the ★ star colour is hand-rolled in 11 view files** (`#E8A24A` here, a different tone in
  `owner/reviews.blade.php`, others elsewhere). Fixed on this page only; the same 1.85 failure will recur on
  every star surface until the glyph becomes a shared partial or token. *Verdict: **REAL GAP** (a
  duplication-defeats-a-fix candidate — Principle 3). The Owner Reviews page is a later gate in this realm.*

### ⚠️ Verified, not changed

- **#42/#38 per-unit isolation — a foreign unit id 404s.** Measured in the authenticated browser: the owner's
  own two units → **200**; an operator-owned unit in the SAME org → **404**; a unit in another property →
  **404**; a nonsense id → **404**. Structural (`Unit` carries `BelongsToOwner`; the id is a plain string prop
  resolved by `findOrFail` in `render()`, never route-model-bound).
- **What the actions actually ARE** — unlike Add Unit (a request), these are **direct owner writes**, each on
  a real gated controller route: **pause/resume** → `owner.units.toggle-status` (CAP_ACTIONS, recomputes
  availability so it genuinely stops taking bookings) · **block a day** → `owner.calendar.block`
  (CAP_ACTIONS, certified `OwnerBlockService`) · **edit pricing** → `owner.rates.apply` (CAP_ACTIONS **and**
  only when `editable`). The #6 model holds as Owner-4 established it: the owner prices **directly** because
  this room type is exclusively theirs; a shared type renders read-only with *"managed by your operator"*.
- **Observed READ-ONLY** — every form on the page was guarded at capture phase for the whole session. After
  walking the calendar, the composer, the pricing editor and the toggle: `units.updated_at` still the seed
  time `2026-08-09 07:17:58`, `inventory_blocks` still 2, `rate_calendar` max `updated_at` unchanged,
  `restrictions` still 0. **Nothing was written.**
- **Per-unit figures (#39)** — occupancy/nights via the shared `OwnerOccupancy` **keyed by physical unit id**
  (not the room-type-wide `AvailabilityService`, which would fold in units this owner does not own — a
  pre-existing test locks that); net via `OwnerLedgerAttribution`; trends via `OwnerUnitAnalytics`; the badge
  via the real cross-unit ranking. **One source — nothing re-derived in the Blade.**
- **#28** — 0 email addresses, 0 IBANs, 0 phone-like strings on the rendered page; the day-detail payload
  carries no contact keys; the stays table identifies bookings by `reference` only; review bylines are first
  name + initial.
- **§N.4k checked** — this page has no multi-step wizard. The block composer is a single-step form whose
  submit *is* the intent, so implicit Enter submission is correct there.
- 0 horizontal overflow at 1440×1000; 0 console errors/warnings.
- ⚠️ **`toContain` sweep — clean negative** (ninth gate); the new guard is needle-only.
- ⚠️ **Gradient family — surfaced, none added.** The 6 remaining failures are all white-on-accent-gradient:
  `.vy-brand-mark` 3.88 / `.vy-avatar` 3.83 (shell), "Edit pricing" 3.93, "Save changes" 3.93, "Send message"
  3.95, and **the SELECTED calendar day 3.95** (a member I surfaced by forcing `.is-sel`; the rule predates
  this gate). Still awaiting the realm-wide gate.

---

## §O11 — Owner Statement PDF (`/owner/statements/{statement}/pdf`) · OWNER 11/32

**Prototype:** `docs/design/raw/Owner Statement PDF.html` (decoded fresh). **Role:** `External Owner`
(`CAP_EXPORTS`; the masked IBAN additionally requires `CAP_PAYOUT`). **Renderer:** `PdfService` →
`pdfs.owner-statement`, composed by `App\Support\Pdf\OwnerStatementDocument`.

### ⚠️ Premise correction — this document was already covered at O2

The gate was briefed as the separate PDF "Owner-2 flagged". In fact **O2 covered it**: it decoded this same
prototype, measured the *generated PDF's own content stream*, fixed the pre-AA greys (3.86 / 2.74 → 6.98 /
5.79) and verified SCREEN == PDF figure-by-figure. So this gate is a **layout diff + re-verification**, not a
fresh fix — and the re-verification is the point: **O2's fix held**, now proven and, for the first time,
*guarded*.

### 🔴 The find: the explanatory comment was shipping the pre-AA hex values into every statement

Writing the obvious regression guard ("this document contains neither pre-AA grey") **failed immediately** —
because the note explaining the fix was a **CSS** comment inside `<style>`, and **a CSS comment is rendered**
(the O6 lesson, in a new place; a Blade comment is stripped). So every generated statement carried the
strings `#8A8079` and `#A69A92` in its markup, and no substring guard could ever be written. Converting the
prose to a Blade comment removes it from the output and makes the guard possible. **Mutation-proven** —
putting either grey back into a real rule fails it. This is the same shape as the `DesignTokenContrastTest`
issue recorded in `PROJECT-STATE` §J: *a whole-file substring guard is defeated by the comment that documents
the fix.*

### Fixed on this page (template/markup only)

| # | Item | Before → after |
|---|---|---|
| 1 | Footer broke mid-token — *"generated 9 Aug / 2026"* and the contact address hyphenated across lines (**"billing@vyrobel-/demo.test"**) | explicit 62% split + `white-space:nowrap` on the date and the address — both unbroken, verified in the regenerated PDF |
| 2 | The pre-AA hex literals shipped inside every statement via a **CSS** comment | moved to a Blade comment (stripped at compile), enabling the guard |
| 3 | No test guarded O2's contrast fix — it had already survived one full pass undetected | new mutation-proven guard |

### 🚫 Refusals preserved (do NOT "fix")

- **The prototype's "Payment & ops fees" and "Guest refund" rows** are absent because both are **genuinely
  zero/absent** for this statement. The document's stated rule (#70/#73) is *€0 → omitted, never faked*.
- **No estimated payout date** — the prototype's `settleLine` becomes the real status ("Payout pending") plus
  the masked IBAN; no date is invented (no field exists).
- **The prototype's hardcoded letterhead** (Seaside Villas, a Faro street address, `owners@seasidevillas.ie`)
  → the real org identity, real country, real `billing_email`. The owner's registered address block renders
  **only when set** (it is not, here — so no block, no fabricated line).
- **18%** throughout — the owner's real agreement rate, never the mock 15%.

### ✅ Extra work kept (beyond the prototype)

- **"Performance by unit"** carries real per-unit occupancy; the total row reconciles to gross.
- The **download watermark + audit** ("Downloaded by … · Confidential") — not in the prototype.
- The **Withholding tax** line (#73) exists and prints only when non-zero.

### 📌 MISSING BACKEND / logged, not built

- **DO11-1 — `MoneyFormatter::display(0, …, signed: true)` returns `"+€0.00"`.** The zero OTA-commission row
  therefore prints a **plus sign on a deduction line**. A zero has no sign. ⚠️ **NOT hand-fixed here**: both
  the screen and the PDF pass `signed: !isNet`, so fixing one surface would split SCREEN == PDF. The fix
  belongs in the shared `MoneyFormatter` (fix-once, Principle 3). **Blast radius measured for that gate: 9
  call sites, and 0 tests assert the string** — so it is cheap and safe when someone takes it.
  *Verdict: **REAL GAP** (shared-helper gate).*
- **DO11-2 — the "(provisional)" flag and the "pending channel reconciliation" footnote fire off a €0.00
  line.** The `unverified` flag on the OTA row is real, so the copy is a true description of the data — but
  with **0 reservations carrying a `channel_id` (D2-2)** there is no channel reconciliation to wait for, so
  the statement tells the owner something is pending that never resolves. Whether a €0 line should carry
  `unverified` is a **product/backend question, not a design one** — surfaced, not decided.
  *Verdict: **REAL GAP**, Channex-tied.*
- **DO11-3 — the prototype's footer is a REPEATING page footer** (`doc-page`'s `slot="footer"`), so
  `genLine` + "Questions?" print on every page. The live footer sits at the end of the content flow. Identical
  on a single-page statement (which is what realistic data produces); they diverge only if a statement ever
  spans pages. Not restructured — repaginating a money document is not a design-gate change.
  *Verdict: **REAL GAP** (low, multi-page only).*
- **DO11-4 — the PdfService watermark measures 2.70 on white**, the one genuine on-paper failure left on this
  document. It is `PdfService.php:54`, shared by every PDF. **Explicitly out of scope** (the brief forbids
  touching PdfService) and already logged at O2 as part of the realm-wide PDF sweep. *Verdict: **REAL GAP**,
  realm-wide PDF-contrast gate.*

### ⚠️ Verified, not changed

- **#17 reconciles, from the generated PDF's own content stream**: gross **€1,080.00** − commission
  **€194.40** (the real 18%) = net **€885.60**, which appears **3×** (headline band, net row, payout note);
  by-unit A1 €540.00 + A2 €540.00 = **€1,080.00** (printed 2×, breakdown and by-unit total); nights 5 + 3 = 8;
  ADR €135.00 = 1,080 ÷ 8. **SCREEN == PDF** — identical to the Owner Statement screen (O2, `656cc34`).
- **One source** — the lines are the real `owner_statement_lines`; the stats and by-unit rows come from the
  shared `OwnerStatementBreakdown`. The blade is logic-free by design; **no total is re-derived in it**.
- **#28** — the content stream contains `IBAN ···· 1000` and **no raw IBAN** (`Iban::masked` takes only
  `iban_last4`, so a raw number is structurally un-emittable), and **no guest name, email or phone**. The
  existing test seeds a real full IBAN and asserts it never appears — non-vacuous.
- **#42 — mutation-proven this gate.** `OwnerStatementPdfTest` seeds **two real owners** and asserts B → 404
  on A's PDF (plus a stranger → 404); dropping the scope in the controller turns it red. ⚠️ **A live foreign
  probe was impossible — the demo DB has exactly ONE owner**, and seeding a second owner's statement purely
  for a screenshot would be mutating demo data. *Silence is correct* (the O4 precedent).
- **Contrast ON PAPER — the document's own ink is AA-clean**: 5.18 / 5.79 / 6.98 / 8.44 / 15.40 on white.
  ⚠️ **Four apparent failures were my probe's error** — white "V", the white net figure, `#C9B8AC` and
  `#B9ABA0` all sit on **dark plates**, not paper; re-scored against the plate they actually sit on they are
  **5.18 / 15.76 / 8.21 / 7.05**, matching the template's own note exactly. The only genuine on-white failure
  is the shared watermark (DO11-4).
- **Greyscale** — state is never carried by colour: "Payout pending" and "(provisional)" are **words**,
  negatives carry a **U+2212 minus**, and the net row is bold with a rule above it. A B&W print reads the same.
- **Generated-at / period real (#39)** — `OS-2026-09`, "September 2026", "issued 9 Aug 2026", "no. 6" all
  from the real statement.
- **The export controls are 9 real `<a href>` GET links** (#20) — all keyboard-reachable, all named (text or
  `aria-label`); no `div@click`, and **no regenerate control exists**, so there was nothing to fire.
- ⚠️ **The export is NOT side-effect-free, by design.** Each download writes an `export_downloads` audit row
  (Wave B · GATE 5 made the watermark + download log TRUE); my probes created 6. **No money row moved**:
  statement net/gross and `updated_at` unchanged (`2026-08-09 07:17:58`), lines and ledger untouched.
  Recorded because "observe READ-ONLY" does not mean "no row is written" on an audited export.
- ⚠️ **`toContain` sweep — clean negative** (tenth gate); the new guard is needle-only, chained with `->and()`.

---

## §O12 — Owner Settings hub (`/owner/settings`, `App\Livewire\Owner\OwnerSettings`) · OWNER 12/32

**Prototype:** `docs/design/raw/Owner Settings.html` (decoded fresh — 13 sections). **Role:** `External
Owner`. ⚠️ **`Owner Security.html` is a SEPARATE prototype for `/owner/security` — its own gate, not this one.**

### ⚠️ Premise correction — password-change is NOT on this page

The brief asked where password-change lives. **It is on `/owner/security`**, not the profile/settings hub:
`POST /owner/security/password` → `ChangePasswordController@owner` (throttled 6/1, self-only, current password
verified, and the shared hash-rotate path that terminates sibling sessions). 2FA enrolment, recovery codes and
disable all live there too. So this gate touched **no** password or 2FA surface, and the firm rulings on
`changePasswordTo` were not in scope — the operator precedent (GATE 24, `/app/security`) holds for the owner
realm as well.

### The two identities on this page — worth keeping straight

- **MY PROFILE** — per-USER, resolved from `auth()`, **no capability gate**: every owner-realm user *including
  accountant and view-only sub-users* edits their OWN login (name / language / avatar / login-email).
- **OWNER-PARTY / business details** — the entity that gets paid — is **PRIMARY-OWNER-ONLY** (`EnsurePrimaryOwner`,
  #42), resolved by an explicit `where('user_id', auth id)` with no owner id in the request.

### Fixed on this page (design / markup / styling only)

| # | Item | Before → after |
|---|---|---|
| 1 | The avatar upload rendered as the **raw browser widget** ("Choose File / No file chosen") — the only unstyled control on the page — and carried **no accessible name** | a design-system `.vy-set-file` pill (icon + "Choose photo"), `aria-label`, `opacity:0` **never `display:none`** (a hidden control is an unreachable one — the GATE-30 defect), ring on the label since the input is invisible |
| 2 | ⚠️ **The "Verified" pill was gated on `pending_email === ''`** — i.e. *"no change is pending"*, **not** *"this address is verified"* | gated on `email_verified_at`; new guard, **mutation-proven** |
| 3 | 7 accent-as-text links (`Statement & fee documents →`, `Your agreement →`, `Manage in Payouts →`, `Add →` ×2, `Manage team →`, `→`) at **4.25–4.46** | `--vy-accent-text` |
| 4 | `$lbl` — **every field label on the page** — plus two helper paragraphs, `--vy-faint` on the glass at **4.19–4.44** | `--vy-on-grad-soft` |
| 5 | `$err` — the **validation-error** style, an accent-as-text site that only renders in a state I could not trigger read-only | `--vy-accent-text`; **probed and measured at 4.81** rather than assumed |
| 6 | A **stale route comment** claiming "the payout account/IBAN, notification prefs, team and 2FA are net-new subsystems, stubbed 'Soon'" — all four shipped and the hub reuses them | corrected in `routes/web.php` |

**Contrast: 20 AA failures → 9** (173 text + 29 icon candidates). Icons 0 failures.

### 🚫 Refusals preserved (do NOT "fix") — this page is where the prototype fabricates hardest

The wireframe's Settings page is dense with authoritative-looking invention, and the live page refuses all of
it. Verified present-and-correct this gate:

- **"Visa ···· 4242 · Expires 04 / 27 · Update"** — a stored card the owner does not have. The live page says
  *"Deducted on each statement — no card needed"*. The owner has **no Vyrobel subscription** — that is the
  operator's (#41).
- **"15% management fee"** — the live figure is the owner's **real agreement rate, 18%**, via the shared
  `OwnerCommissionRate`.
- ⚠️ **"Session · Signed in on this device · MacBook · Dublin, IE"** — **the operator-Profile GATE-33 refusal,
  and the prototype commits it here too.** A city beside a sign-in reads as security evidence, and there is no
  geolocation in the product. The live page shows **no session-location block at all** on this page (real
  sessions live on `/owner/security`).
- **"Owner · Seaside Villas · since 2023"** — no fabricated "member since"; the live subtitle is the real role
  (*"External owner"*).
- **Data export → "Guests · Guest contacts & lifetime value (CSV)"** — ⚠️ this would hand an owner a **guest
  contact list**, a direct #28 breach. **Refused**; the live exports are Bookings / Statement history / Full
  account data, captioned *"owner-scoped, no other party's"*.
- **Channels "Connected / Disconnect"** — live shows Direct **Always on** and Airbnb/Booking.com/Vrbo
  **Pending**, with the copy *"we'll never show a channel as connected until it truly syncs"* (**D2-2**, #37).
- **SMS / Push notification columns** — honest `· soon`; structurally unsettable (`CHANNELS =
  ['in_app','email']`, O7).
- **Theme picker** — honest *"Light · dark theme soon"*; no dark palette exists.
- **Phone "Verified / Verify"** — no phone-verification subsystem, so no badge is shown for phone.

### ✅ Extra work kept (beyond the prototype)

- **Profile completeness** derived from real state (photo / email / tax details / payout account / 2FA), each
  row linking to the real place to fix it.
- The hero stats (**Channels live · Team members · Fee invoices**) are real counts.
- The masked payout IBAN in the rail, **CAP_PAYOUT-only** (#28).

### 📌 MISSING BACKEND / logged, not built

- **DO12-1 — no phone verification.** The prototype badges phone "Verified/Verify"; nothing verifies a phone
  number (no SMS provider — the same account-gated dependency as the SMS channel). *Verdict: **ACCOUNT-GATED**.*
- **DO12-2 — per-user currency and timezone are not settable.** "Language & region" shows currency and
  timezone as **read-only, inherited from the property**, which is honest (a per-user currency with no FX
  would be a fabrication — the Wave-A "HONEST HOLD"). The prototype implies they are editable.
  *Verdict: **RATIFIED REFUSAL** until FX exists.*
- **DO12-3 — the prototype's "Unsaved changes / All changes saved" live-state bar** does not exist; the live
  page uses per-section Save buttons (each its own gated POST). Cosmetic, but it is a real UX affordance the
  design assumes. *Verdict: **REAL GAP** (small).*
- **DO12-4 — no fee-invoice PDFs per row.** The prototype lists "Recent fee invoices" with a per-row PDF; the
  live page links to the real statement & fee documents instead (the fee is a statement line, not a separate
  invoice entity). *Verdict: **RATIFIED REFUSAL** unless invoices become their own entity.*

### ⚠️ Verified, not changed

- **Self-scoped (#28/#38/#42)** — the profile resolves from `auth()`; the owner-party write resolves by an
  explicit `where('user_id', auth id)` with **no owner id in the request**, so a foreign row is unreachable by
  construction rather than by a 404. Existing tests are non-vacuous: owner A posts and **owner B is proven
  untouched**, and A's page shows A's email while **B's is absent**. The owner seeing their OWN email/company
  is their account, not a #28 widening; **no other user's data and no guest data appear**.
- **Password-change lives on `/owner/security`** (stated above) — untouched by this gate.
- **The save was observed READ-ONLY.** All **7 forms** were guarded at capture phase for the whole probe;
  nothing was submitted. After: `users.updated_at` **unchanged** (`2026-08-10 11:24:07` — ⚠️ note that is
  *today*, because logging in touches the user row, so the honest baseline is "unchanged from the value before
  probing", not "still the seed time"), `owners.updated_at` still `2026-08-09 07:17:58`, `owner_users` 2,
  `payout_methods` 1, and **`export_downloads` still 7** — unlike the statement PDF (O11), *viewing* settings
  writes no audit row.
- **No raw IBAN** — the rail shows `···· 1000` via `Iban::masked`, CAP_PAYOUT-gated.
- **Keyboard: 66 named controls, 0 unnamed** (was 1), **0 `display:none` interactive controls**, **0
  `div@click`** — the Owner-10 and GATE-30 defect classes are both absent here.
- ⚠️ **`toContain` sweep — clean negative** (eleventh gate); the new guard uses `assertSee`/`assertDontSee`.
- ⚠️ **Gradient family — surfaced, none added, none half-fixed.** All 9 remaining failures are
  white-on-accent-gradient. ⚠️ **This page contributes a new near-worst cluster:** the four team-member /
  hero initial circles use a *lighter* peach gradient (`#F0A57A → #D9662E`) and measure **2.55–2.63** — the
  family's worst band is now **2.54–2.63**, and it is four instances on one page. Still awaiting its own gate.

---

## §O13 — Owner Security (`/owner/security`, `App\Livewire\Owner\OwnerSecurity`) · OWNER 13/32

**Prototype:** `docs/design/raw/Owner Security.html` (decoded fresh). **Role:** `External Owner`, per-USER
(auth-resolved) — accountant and view-only SUB-USERS manage their OWN password/2FA/sessions here. The one
primary-owner-only action is **Deactivate** (`EnsurePrimaryOwner`, #42).

### ⚠️ TWO PREMISE CORRECTIONS — one of them would have made the page LIE

**1. There is no 12-character password policy.** The brief said *"the 12+ char policy is honest, never
weakened"*. **Every password path in the codebase is `min(8)`** — verified across all ten: change-password,
reset, both invite-accepts, onboarding, guest claim, guest account, guest reset, and admin. The page states
*"At least 8 characters"*, which is **exactly what the server enforces**. ⚠️ **Had I "restored" a 12+ claim to
match the brief, I would have made a security surface overstate its own protection** — the same class of
defect this pass exists to catch, inverted. Nothing was changed.

*(My own probe briefly reported `has12: true`; that was CSS `12px`, not a policy claim — a probe false
positive, the fourth of the pass.)*

**2. The security SCORE is real and rendered.** An earlier note recorded this page as *"a REAL protection
checklist (derived, never a score)"*. There **is** a score — **40 / 100 · Basic** — and it is a deterministic
function of three true facts (password set 40 + 2FA confirmed 40 + unused recovery codes 20). It is derived,
not fabricated; the note was stale and is corrected here.

### 🔴 The find: a SHARED partial was carrying one page's layout width

Every card on the owner Security page is **1184px** — except the two-factor card, at **560px**. The cause:
`livewire/partials/two-factor-panel.blade.php` hardcoded `max-width: 560px`. That is the **operator**
security page's column (every card there is 560px), baked into a partial that renders in **both** shells.

Fixed by moving the constraint to the callers: the operator include wraps it at 560px, the owner include lets
it fill. **The operator page was measured before and after and is unchanged** — all eight cards still 560px,
0 overflow. Guard added and **mutation-proven** (with the mutation asserted-as-applied, per the O12 lesson).

⚠️ **The generalisation:** this is the third form of the shared-partial trap. O6: a shared partial cannot
depend on a class only one shell defines. Now: **a shared partial cannot carry a layout constant only one
shell's design uses.**

### Fixed on this page (design / markup / styling only)

| # | Item | Before → after |
|---|---|---|
| 1 | The shared 2FA panel rendered as a lone **560px** card among eleven 1184px cards | width moved to the callers; owner now one consistent column, **operator verified unchanged** |
| 2 | The **"This device"** session badge — a real green at **3.69** on the warm surface | `--vy-green-text` → **6.10** |
| 3 | The **score-tier pill ("Basic")** at **3.84** — the design's tint assumes a card, but this pill sits on the page gradient (the O10 "Active"-pill shape) | same tint composited over a near-white plate → **4.74**, tint and text colour untouched |
| 4 | 5 accent-as-text sites incl. the **destructive "Deactivate account" heading (4.41) and button (4.47)** | `--vy-accent-text` → **5.23** (hue kept — the danger colour still reads as danger) |
| 5 | Stale docblock on the shared partial: *"operator (/app/security) + owner (**/owner/settings**)"* | corrected to `/owner/security` |

**Contrast: 10 AA failures → 6** (103 text + 26 icon candidates). Icons 0 failures.

### 🚫 Refusals preserved — this prototype is the most dangerous in the realm

A security mock that invents state is inventing *security evidence*. Every one of these is refused live:

- ⚠️ **A FABRICATED PENDING SIGN-IN**: *"Approval needed · just now · New sign-in · Chrome on Windows ·
  **Porto, Portugal · IP 82.14.x** · Deny / Approve"*. A fake city **and** a fake IP presented as a live
  security decision — the GATE-33 refusal at its worst. Live shows the **real** login-approval feature with an
  honest **OFF** state and no invented request.
- ⚠️ **"Trusted locations · Skip 2FA when signing in from here · Add current"** — a *location*-based 2FA skip
  needs geolocation, which the product does not have. Live implements **trusted DEVICES** (a hash-only,
  revocable cookie) — the real concept, correctly named. **STRUCTURAL refusal.**
- ⚠️ **"Use at least 8 characters with a number."** — the prototype's own policy copy. Live states the real
  rule; the "with a number" composition requirement is **not** claimed, because `PasswordRule::min(8)` does
  not require one. *(Refusing to over-claim, in both directions.)*
- **No hardcoded green** anywhere — the GATE-24 defect. Two-factor reads **Off** from
  `hasTwoFactorEnabled()`; recovery codes count only **unused** ones; sessions are the real `sessions` rows;
  passkeys/trusted devices/connected apps all show honest empty states.

### ✅ Extra work kept (beyond the prototype)

- **Passkeys** (WebAuthn, real), **login approval for new devices**, **trusted devices**, and a **"Coming
  soon"** card naming the three genuinely-unbuilt items (new-device email alerts, full sign-in history,
  session timeout) — never a fake toggle.
- Connected apps carries an explicit #28 statement: *"never your guests' contact details, door codes or
  Wi-Fi"*.
- Deactivate is honest about being **reversible and non-destructive** ("statements and payout history are
  kept") and requires the password.

### 📌 MISSING BACKEND / logged, not built

- **DO13-1 — no new-device email alert, no full sign-in history, no session timeout.** Named honestly in a
  "Coming soon" card; no backend exists for any of the three. *Verdict: **REAL GAP** (×3).*
- **DO13-2 — no geolocation, so no location on any sign-in.** Sessions show IP + relative time only. A
  "trusted locations" feature (as the prototype shows) would need a geoip provider.
  *Verdict: **RATIFIED REFUSAL** / account-gated.*
- **DO13-3 — the export is an immediate JSON download**, not the prototype's *"we'll email a secure download
  link within a few minutes"* with a JSON/CSV format choice. The live behaviour is simpler **and** more
  honest (no promised email that never arrives); CSV is not offered. *Verdict: **REAL GAP** (small).*
- **DO13-4 — password composition rules are not enforced.** `PasswordRule::min(8)` sets length only; the
  prototype claims "with a number". Whether to require mixed case / numbers / symbols — or `uncompromised()`
  against HIBP — is a **security-policy decision for the founder**, not a design call. Surfaced, not decided.
  *Verdict: **REAL GAP** — founder decision.*

### ⚠️ Verified, not changed

- **Every security badge reads the fact it claims** (GATE-24 + the O12 rule): two-factor **Off** from
  `hasTwoFactorEnabled()`; the score from three real booleans; "Protections on 1/3" from the same; recovery
  codes counted **unused-only**; "This device" from the real session id; trusted devices / passkeys /
  connected apps from real rows (all genuinely empty). **No hardcoded state, no proxy-that-coincides.**
- **The password form POSTs `ChangePasswordController@owner`** (`throttle:6,1`) — read, **never fired**.
  `changePasswordTo` and its sibling-session termination were not touched.
- **Observed READ-ONLY across all 7 forms**, guarded at capture phase for the whole probe. After:
  the password **hash is byte-identical**, `two_factor_secret` still **null**, `two_factor_confirmed_at`
  **null**, `status` still `active`, `sessions` still **1**, `passkeys` still **0**. **Nothing was changed.**
- **§N.4k — Enter-safety.** Seven forms, **each with exactly one submit button** scoped to its own action, and
  no multi-step wizard. Proven empirically: Enter in the new-password field routes to
  `/owner/security/password`, Enter in the sign-out-others field routes to
  `/owner/security/sessions/sign-out-others` — **no field can fire another form's action**, and in particular
  nothing benign can reach **Deactivate**. The one attempt was captured and blocked.
- **Self-scoped** — the page resolves from `auth()` with **no id in any request**, so another user's security
  surface is unreachable *by construction* rather than by a 404.
- **Keyboard: 0 unnamed controls, 0 `display:none` interactive controls, 0 `div@click`**; 0 overflow at
  1440×1000; 0 console errors.
- ⚠️ **`toContain` sweep — clean negative** (twelfth gate); the new guard is needle-only with `->and()`.
- ⚠️ **Gradient family — surfaced, none added.** All 6 remaining are white-on-accent-gradient (avatar, brand
  mark, and four accent buttons).

---

## §O14 — Owner Documents (`/owner/documents`, `App\Livewire\Owner\OwnerDocuments`) · OWNER 14/32

**Prototype:** `docs/design/raw/Owner Documents.html` (decoded fresh). **Role:** `External Owner`,
`$capDocuments` (co-owner + accountant — a **view-only sub-user is refused**).

### ⚠️ Premise check — the page IS distinct, and so is its sibling

`/owner/documents` is a real, separate route. ⚠️ **There is a SECOND prototype, `Owner Archive.html`, and it
is also a live page** (`/owner/archive`, `OwnerArchive`) — deeper history with lifetime net, per-year bars,
search with a no-results state, 7-year retention copy and a ZIP download. **That is its own gate; nothing here
touched it.**

⚠️ **A nav finding that turned out NOT to be a defect:** the sidebar item labelled **"Statements"** points at
`/owner/archive`, not `/owner/statements`. Verified: **there is no `/owner/statements` index route** — the
statement *list* IS the archive (`OwnerArchive` = "the owner's FINALIZED statement history"); only
`/owner/statements/{id}` (detail) and the PDF routes exist. Nav, "full archive ›" and the sibling prototype
all point at the same real page. **Consistent, not a mis-wire.**

### Fixed on this page (design / markup / styling only)

| # | Item | Before → after |
|---|---|---|
| 1 | **"full archive ›" at 2.70** — the worst non-family site measured on an owner page; `--vy-accent` on the raw page gradient | ⚠️ **NO accent tone reaches AA there** (the darkest, `--vy-accent-tint-text`, only gets to **3.85**), so the link takes `--vy-on-grad` (**5.09**) and carries its affordance with an **underline** instead of colour — legible *and* unambiguously a link |
| 2 | The header line *"18 documents · since April 2026"* at **3.98** (`--vy-muted` on the gradient) | `--vy-on-grad-soft` → **5.01** |
| 3 | The **six per-month "Net €X" figures** at **4.38–4.45** — money on a document record, in raw accent | `--vy-accent-text` → **5.20** |

**Contrast: 10 AA failures → 2** (141 text + 45 icon candidates) — and **both remaining are the shell's
gradient family** (avatar 3.83, brand mark 3.88). **The page's own ink is fully AA** — the best result of the
owner realm so far. Icons 0 failures.

### 🚫 Refusals preserved

- **The design's "RA-2025-09" reference format does not exist** — the row shows the operator's own bank
  reference **only when they attached one**, never a generated one.
- **A pending payout gets no estimated settlement date** — the status badge carries the truth instead.
- **A DRAFT statement is not a document** — excluded, not listed (it has no `sequence_no` and its figures can
  still move). Locked by an existing test that asserts the draft's distinctive net is absent.
- **The prototype's fabricated scale** — "34 documents", "3 years", "since June 2023" — is refused; the live
  counts and span are derived from real rows (existing test asserts all three strings are absent).
- **The annual card is honest about its span**: the prototype says *"All 12 months"*; live says
  **"6 months (Apr–Sep)"**, because that is what exists.

### ✅ Extra work kept (beyond the prototype)

- A **remittance advice at every payout status** (the controller filters none), each row labelled with its
  **real** status rather than filtered out.
- The **amount note** is derived from status (`net paid` / `net reversed` / `net payable`), so neither the row
  nor the PDF claims money was paid when it wasn't.

### 📌 MISSING BACKEND / logged, not built

- **DO14-1 — ⚠️ a THIRD form of D16-7: `payoutStatusMeta`'s `default =>` swallows two real statuses.** The
  shared owner label map is `paid` / `failed` / `reversed` / **default → "Payout pending"**. But
  `OwnerPayoutService` genuinely writes **`processing`** (the Connect rail, line 127) and **`held`** (the tax
  hold, line 189). So a remittance advice for money **actually in flight**, and one **held by the tax hold**,
  both read *"Payout pending"*. Unlike O3's tiles — where a `processing` payout vanished from every tile —
  here it is **mislabelled rather than invisible**, on a document the owner keeps. ⚠️ **NOT fixed here:** the
  map is a **shared** helper (`FormatsOwnerData`) used by several already-closed owner pages, so it belongs to
  the D16-7 gate, not a page gate. *Verdict: **REAL GAP**, linked to D16-7.*
- **DO14-2 — no year filter.** The prototype has year pills (`{{ y.label }}`); live renders one flat list
  because only 2026 exists. Harmless today, needed once there are multiple years — and the Archive page
  already has per-year grouping. *Verdict: **REAL GAP** (small).*
- **DO14-3 — no per-document "fee invoice" type.** The prototype's Documents/Settings pair implies fee
  invoices as documents; the fee is a **statement line**, not a separate artifact, so no such document is
  listed. *Verdict: **RATIFIED REFUSAL** unless invoices become their own entity (see DO12-4).*

### ⚠️ Verified, not changed

- **#42/#38 — every row is this owner's, and it is MUTATION-PROVEN.** `OwnerDocumentsViewTest` already seeds
  a **second owner (Bruno) with a distinctive €500 statement**, asserts his net really exists (so the
  absence assertion is meaningful), asserts **his three document links are absent from A's list**, and
  asserts **B fetching A's statement PDF and A's remittance → 404**. Dropping the scope from the statements
  query turns **3 tests red**, including the cross-owner one. ⚠️ A live foreign probe remains impossible —
  the demo DB has one owner (the O11 finding); the guard is the proof.
- **#17 reconciles on the page itself:** the six monthly nets (442.80 + 656.00 + 467.40 + 516.60 + 721.60 +
  885.60) sum to **€3,690.00**, exactly the annual tax card's figure. Every figure comes from the real
  statement/payout rows; **nothing is re-derived in the Blade**.
- **21 download links, all GET (#20)**, each an owner-scoped controller route — the statement PDF's
  cross-owner 404 was mutation-proven at O11 and is asserted again here.
- **#28** — 0 email addresses, 0 IBANs, 0 phone-like strings on the page; no guest data anywhere.
- **Status reads its real fact and is a WORD** — "Paid" / "Payout pending" (never colour alone), derived from
  `payouts.status`, not a proxy. ⚠️ Its *vocabulary* is incomplete — see DO14-1.
- **Read-only honoured — and nothing was downloaded this gate**: `export_downloads` still **7** (unchanged
  from the O12 baseline), statements 6, payouts 6, max statement `updated_at` still the seed time. Opening
  the notification panel changed nothing either (**6/6 still unread**).
- **Keyboard: 0 unnamed controls, 0 `display:none` interactive controls, 0 `div@click`**; 0 overflow; 0
  console errors. ⚠️ The 2 "unnamed inputs" my first probe reported were **hidden CSRF tokens** — a probe
  false positive (the fifth of the pass), not a finding.
- ⚠️ **A suspicion measured and DISMISSED — recorded as a negative sweep.** The whole-document guard I first
  wrote failed on `color:var(--vy-accent)`, which comes from the **shared notification bell** in the topbar,
  not this page. Rather than log it, I measured it: **5.06 on the panel's near-white surface — it PASSES.**
  Not a finding; the guard was simply scoped too wide and is now scoped to this page's own markup.
- ⚠️ **No PDF template was touched** — the realm-wide PDF-contrast sweep (~15 templates +
  `PdfService.php:54` at 2.70) is unchanged and `PdfService` was not opened.
- ⚠️ **`toContain` sweep — clean negative** (thirteenth gate); the new guard is needle-only with `->and()`.
- ⚠️ **Gradient family — surfaced, none added** (2 remaining, both shell).

---

## §O15 — Owner Team & access (`/owner/team`, `App\Livewire\Owner\OwnerTeam`) · OWNER 15/32

**Prototype:** `docs/design/raw/Owner Team.html` (decoded fresh). **Role:** the **PRIMARY OWNER ONLY** —
`EnsurePrimaryOwner` gates the read screen *and* every mutation at the route.

### ⚠️ Premise corrections

**1. The invite is a DIRECT owner action, not a request to the operator.** Owner-9's finding (owners cannot
create units — it files a request) does **not** generalise here: the owner invites, changes roles and removes
members themselves, through real gated controller routes.

**2. But the gate is STRONGER than the brief assumed.** The brief expected "a sub-user may view [the page],
gated writes". In fact **the read screen itself is primary-owner-only** — a sub-user (even a **co-owner**)
gets **403 on the page**, not a control-less view. Team management is held by **no role at all**: it is gated
on `isPrimaryOwner()`, which makes **self-escalation structurally impossible** — no sub-user can assign a
role, including to themselves. That is a stronger design than "gate the control too".

### Fixed on this page (design / markup / styling only)

| # | Item | Before → after |
|---|---|---|
| 1 | The **invite email field was the page's only unnamed control** — placeholder-only, on the form that grants a person access to the owner's money (the Owner-9 defect class) | `aria-label`; guard added, **mutation-proven** |
| 2 | **"View only" role pill + the member-count badge** at **3.51 / 3.57** | `--vy-on-grad` → **6.96 / 7.10** |
| 3 | **"Accountant" role pill** at **4.48** (and the matrix ✓ marks, same green) | `--vy-green-text` → **6.30** |
| 4 | The **pending-invite meta line** ("View only · Expires 5 days from now") at **4.06** | `--vy-amber-text` → **5.44** |

**Contrast: 11 AA failures → 7** (129 text + 29 icon candidates). Icons 0 failures. ⚠️ All 7 survivors are the
white-on-gradient family — **including a NEW variant: the member avatars sit on a GREEN gradient**
(`rgb(92,141,82)`) and measure **3.61**, so the family is not purely accent-coloured. Recorded for its gate.

### 🚫 Refusals preserved

- ⚠️ **No fabricated "last active {city}"** — the GATE-33 refusal. The member rows show **"Active now"** /
  **"Hasn't signed in yet"**, derived from the real `last_login_at`; the helper's own docblock says *"never a
  fabricated relative time"*. No location anywhere.
- **No fabricated role or permission.** The "What each role can see" matrix is the **real**
  `OwnerTeamRole` grid, area for area: Earnings ✓✓✓ · Approvals ✓—— · Documents ✓✓— · Calendar ✓—✓ ·
  Exports ✓✓— · Payout ✓——. ⚠️ Note it is deliberately **not a privilege chain**: an accountant has
  documents+exports but not calendar; view-only has calendar but not documents. Only co-owner is a superset.
- **The security note makes no false claim** — *"financial exports are watermarked with who downloaded
  them"* is TRUE since Wave B · GATE 5, and the page proves it by showing the **real** export-download log.

### ✅ Extra work kept (beyond the prototype)

- The **Export download log** in the rail — real `export_downloads` rows (who downloaded which document,
  when). Not in the prototype; it is what makes the watermark claim verifiable.
- **"Only you, the account owner, can manage the team and the payout account."** — states the real gate.

### 📌 MISSING BACKEND / logged, not built

- **DO15-1 — no seat count or team limit is shown, anywhere.** The Essential plan's team limit (**#10**) is a
  known deferred product call, tied to the billing decision (**G.1**). ⚠️ The honest outcome today is that
  the page displays **nothing** about seats — so nothing is faked and no unenforced limit is advertised. If
  the founder wires the limit, this page is where it surfaces. *Verdict: **RATIFIED REFUSAL** today,
  **G.1-adjacent** — surfaced, not decided.*
- **DO15-2 — "Recent activity" is invite-only.** The rail shows real team events, but the underlying feed
  covers invites; role changes and removals are audited in `audit_events` and are not yet surfaced here.
  *Verdict: **REAL GAP** (small) — the data exists.*

### ⚠️ Verified, not changed

- **#42/#38 — MUTATION-PROVEN twice.** `OwnerTeamController` resolves `{member}`/`{invite}` with
  `whereKey(...)->whereIn('owner_id', $ownerIds)`, never trusting the request. Dropping that scope turns
  *"scopes team mutations to the primary owner's own owner — a cross-owner member 404s"* **red**. Existing
  isolation tests also prove a sub-user of owner A never sees owner B's units.
- **#30 — MUTATION-PROVEN.** Removing `EnsurePrimaryOwner` from the team route group turns *"refuses the
  team screen and every mutation to a sub-user"* **red** — so the server genuinely refuses; the gate is not
  merely a hidden control. ⚠️ A *live* sub-user probe was impossible: both demo sub-users have never signed
  in (no password set), which is why the page truthfully shows "Hasn't signed in yet". The tests are the
  proof.
- **Roles map to REAL capabilities** — `OwnerTeamRole` is the single source read by both the route gates
  (`EnsureOwnerCapability`) and the UI (`@ownercan`), so the matrix and the enforcement cannot disagree.
- **#28** — the list is this org's own team (names, emails, roles). No guest data; no other org's user. The
  export log names this org's own downloader.
- **Observed READ-ONLY across all 9 forms** (invite · role-change ×2 · remove ×2 · resend · revoke · plus the
  shell's two), guarded at capture phase. After: `users` **8**, `owner_users` **2** with roles unchanged
  (accountant / view_only) and `updated_at` still the seed time, pending invites **1**, `model_has_roles`
  **7**. **Nothing invited, no role changed, no one removed.**
- **§N.4k — Enter-safety proven empirically on a privilege page.** Nine forms, each with exactly one submit
  scoped to its own action. Enter in the invite email routes to `/owner/team/invite`; Enter in a role select
  routes to **that member's** PATCH. ⚠️ **No field can reach another member's form, and none can reach a
  DELETE/Remove route** — Enter can only ever trigger the Save it belongs to. The one attempt was captured
  and blocked.
- **Status/role is a WORD, never colour alone** ("Account owner", "Accountant", "View only", "Active now",
  "Hasn't signed in yet"); the matrix uses **✓ vs —**, a shape difference, not a hue difference.
- **Keyboard: 1 unnamed → 0**, 0 `display:none` interactive controls, 0 `div@click`; 0 overflow; 0 console
  errors.
- ⚠️ **`toContain` sweep — clean negative** (fourteenth gate); the new guard is needle-only.

---

## §O16 — Owner Payout Method (`/owner/payout-method`, `App\Livewire\Owner\OwnerPayoutMethod`) · OWNER 16/32

**Prototype:** `docs/design/raw/Owner Payout Method.html` (decoded fresh). **Role:** `External Owner`,
**CAP_PAYOUT / primary-owner** route group. ⚠️ **REAL BANKING — the most sensitive owner surface.**

### ⚠️ The structural facts that decide this page

- **The full IBAN is stored ENCRYPTED**; only `iban_last4` + `iban_country` are in the clear, so the UI masks
  it **without ever decrypting the number to render a list**. `Iban::masked()` takes only the last4 — a raw
  IBAN is **structurally un-emittable**.
- ⚠️ **`payout_methods` has NO verification column at all** (id, organization_id, owner_id, label,
  account_holder, iban, iban_last4, iban_country, bic, currency, is_default, timestamps). So a "Verified"
  badge would not be a stale value — it would be **pure fabrication**. This is what makes the prototype's
  entire verification flow un-buildable today, and the live refusal correct.

### 🚫 Refusals preserved — the prototype is the most fabrication-dense in the realm

It ships an **entire micro-deposit verification system that does not exist**, plus fabricated money:

- **"Verifying" badge · "confirm the amounts to activate" · "Send micro-deposits & verify" · a full "Confirm
  micro-deposits" modal (Deposit 1 / Deposit 2, "reversed automatically within 5 business days", "Resend
  deposits")** — all refused. Live states the honest deferral: *"Automated bank verification (micro-deposits)
  activates with your live banking connection — connect Stripe above."*
- **"Next payout €10,370 · Estimated · 3 Nov 2025"** — a fabricated amount **and** an invented date. Live
  shows the **real €885.60** net payable and **no estimated date at all**.
- **"Changing the default holds payouts 48h as a security check"** and **"New accounts verify via
  micro-deposits before any payout switches"** — live keeps the design's lines but each is **qualified with
  "with your live banking connection"**, so neither claims a hold that doesn't happen.
- **"Your account manager can trace any SEPA transfer within 2 business days"** — the SLA is dropped; live
  says *"once the live rail is connected"*.
- **Bank name auto-detection** — `Iban` derives country + last4 from the string's structure, but the **bank
  name needs a BIC registry the product doesn't have**, so it is never fabricated; the owner labels the
  account themselves.

### Fixed on this page (design / markup / styling only)

| # | Item | Before → after |
|---|---|---|
| 1 | ⚠️ **The validation-error flash measured 3.03 — on a BANKING page**, where an error is exactly what must be readable. `.vy-flash.is-err` used `--vy-amber` (a *fill* tone) as *text* | `--vy-amber-text` → **5.08** |
| 2 | `.vy-pm-flabel` ×3 at **2.55–2.66** — the **pre-AA `#9C9089` literal** still living on this page (worst sites on the page) | `--vy-on-grad-soft` |
| 3 | `.vy-field-label` + `.vy-fl-note` at 4.44 (`--vy-faint` on the glass) | `--vy-on-grad-soft` |
| 4 | The micro-deposit note 4.19 + the required-field asterisk 4.43 | `--vy-on-grad-soft` / `--vy-accent-text` |
| 5 | A guard that forbade **"Verifying"** but not **"Verified"** — and sat in a fixture with **no account** | tightened **and relocated**; see below |

**Contrast: 13 AA failures → 6** (100 text + 37 icon candidates); all 6 survivors are the gradient family.
Icons 0 failures. ⚠️ The error state only renders after a failed submit, which read-only forbids — so it was
**probed into the DOM and measured** rather than assumed (the O12 method).

⚠️ **On the shared `.vy-flash` rule:** it is defined in **both** layouts — but they are **two independent
rules with different palettes** (owner = amber, operator = accent), and **the operator layout has no
`--vy-amber-text` token at all**. So the owner fix touches owner pages only. Blast radius checked: 3 owner
views use `.vy-flash`; the other two (Payouts, My Units) render it only in conditional branches, were
verified rendering 0 instances and 0 overflow, and the new tone is strictly darker — contrast can only
improve. *(Checking first is what prevented a blanket change that the operator layout could not even
express — the O6 lesson.)*

### ⚠️ A guard of mine was VACUOUS, and mutation caught it

I tightened `assertDontSee('Verifying')` to also forbid **"Verified"** — the stronger claim. The mutation
(injecting a fake `Verified` badge into the account row) **passed**, proving the assertion was dead: it lived
in the *"defers the live rail honestly"* test, whose fixture **adds no payout method**, so no account row ever
renders there. Moved to the test that actually renders an account, re-mutated, now **genuinely red without
the fix**. Textbook **empty-fixture vacuity** (Principle 4) — caught only because the mutation was run.

### 📌 MISSING BACKEND / logged, not built

- **DO16-1 — no micro-deposit / bank-account verification.** No column, no provider; the entire prototype
  flow (send deposits, confirm two amounts, resend, auto-reversal) is unbuilt. Arrives with the live banking
  connection. *Verdict: **ACCOUNT-GATED** (KYC / bank-verify, §H).*
- **DO16-2 — no 48-hour hold on a default change, and no email on payout-detail changes.** Both are stated on
  the page as pending the live connection rather than as current behaviour. *Verdict: **ACCOUNT-GATED**.*
- **DO16-3 — no SEPA trace.** The support card defers it to the live rail; there is no tracing integration.
  *Verdict: **ACCOUNT-GATED**.*
- **DO16-4 — no bank-name lookup (BIC registry).** Structural refusal; the owner labels the account.
  *Verdict: **RATIFIED REFUSAL** unless a registry is licensed.*
- **DO16-5 — the "Next payout" figure has no date beside it.** ⚠️ **D16-7-adjacent**: the amount is real
  (`€885.60`), but a *when* would need the schedule + the rail to agree, and the page already says the actual
  date is "confirmed with your operator and the payout rail". Surfaced, **not** fabricated.
  *Verdict: **REAL GAP**, D16-7-linked.*

### ⚠️ Verified, not changed

- **IBAN masked, never raw (#28)** — swept the **rendered DOM**: **0** IBAN-shaped strings anywhere; only
  `···· 1000` (×2). The add-form input accepts a full IBAN (the owner typing their own) but it is never
  echoed, and the stored value is encrypted. **Mutation-proven**: making the view emit `$m->iban` instead of
  `Iban::masked($m->iban_last4)` turns *"stores the IBAN encrypted and only ever shows it masked"* **red**.
- **Verification honest (#39)** — no "Verified" or "Verifying" renders anywhere; the only occurrence of the
  word "Verified" in the source is a **Blade comment** documenting the refusal (stripped at compile — *not*
  the O11 CSS-comment trap, which was checked).
- **Accounts self-scoped** — resolved for the authenticated owner; an existing test asserts *"never lets an
  owner read or manage another owner's method"*.
- **The default marker reads its real fact and is not colour-only** — the word **"Default"** sits beside it.
  ⚠️ The multi-account branch (unreachable with one account) was **read rather than assumed**: the default
  renders a labelled decorative `<span aria-label="Default account">`, a non-default renders a real
  `<button type="submit" aria-label="Set {label} as default">` on the gated route — keyboard-operable.
- **Observed READ-ONLY across all 7 forms.** A **fake** IBAN was typed to see the design; nothing submitted.
  After: `payout_methods` still **1** (same id, `last4=1000`, `is_default=Y`, `updated_at` still the seed
  time `2026-08-09 07:17:58`), `owner_payout_preferences` still **0**, owner tax fields still null with
  `updated_at` unchanged. **No account added, changed, removed, or set primary.**
- **§N.4k — Enter-safety on a banking form.** Enter in the IBAN field targets `/owner/payout-method` (Save),
  **never** the DELETE form at `/owner/payout-method/{id}` — so Enter can never remove an account. Nothing
  fired (the required `account_holder` blocked it — real client validation, not a fabricated "looks valid ✓").
- **Keyboard: 0 unnamed controls, 0 `display:none` interactive controls, 0 `div@click`**; 0 overflow; 0
  console errors.
- ⚠️ **`toContain` sweep — clean negative** (fifteenth gate).

---

## §O17 — Owner Balance (`/owner/balance`, `App\Livewire\Owner\OwnerBalance`) · OWNER 17/32

**Prototype:** `docs/design/raw/Owner Balance.html` (decoded fresh). **Role:** `External Owner`, self-scoped.
⚠️ **REAL MONEY, and THE D16-7 surface** — the page whose whole job is *"where every euro is on its way to"*.

### ⚠️ PREMISE CORRECTION — this page is the D16-7 REFERENCE IMPLEMENTATION, not another victim

The gate was briefed expecting `processing`/`held` to be collapsed or dropped here, as they are elsewhere
(O3: dropped from the Payouts tiles; O14: the shared `payoutStatusMeta` collapses both into *"Payout
pending"*). **The opposite is true.** `OwnerBalance` treats the engine's persisted statuses as **first-class**,
using its own local mapping rather than the shared collapsing helper:

- **On hold** = `payouts.status='held'` (the engine's persisted tax-hold) **+** a manual-path `pending` payout
  while the tax-hold pauses the rail.
- **In transit** = `payouts.status='processing'` (an initiated Connect transfer awaiting its webhook) **+** a
  released `pending` payout.
- The per-row chip maps `processing → "In transit"` and `held → "On hold"` as **distinct words**.

⚠️ **So the D16-7 gate already has a working reference: make the shared `payoutStatusMeta` match
`OwnerBalance::rowStateMeta()`.** That is the single most useful thing recorded by this gate.

### 🔴 The find: the D16-7 buckets were COMPLETELY UNGUARDED

Dropping `processing` from **In transit**, or `held` from **On hold**, left **every** Balance test green — and
also left `PayoutEngineSurfaceTest::renders the engine hold on Balance` green, the one test that *does* create
a real `held` payout. Why it misses:

- its identity assertion is computed **from the DB** (`$available + $held + 0 + $paid === $net`), not from the
  component's view data, so it cannot detect the component mis-bucketing;
- its `assertSee('On hold')` passes on the **tile's static label**, which renders at €0 too.

**A new guard now asserts the COMPONENT'S OWN buckets** — a payout driven to `processing` must land in
`inTransit` with `onHold` at 0, then driven to `held` must land in `onHold` with `inTransit` at 0, with the
five-term identity holding in both. **Mutation-proven in both directions.** ⚠️ Found only because the mutation
was run: the first attempt against the existing suite *passed*, which is what exposed the hole.

### Fixed on this page (design / markup / styling only)

| # | Item | Before → after |
|---|---|---|
| 1 | ⚠️ **The ON HOLD tile's own label + sub at 3.08 / 2.98** — on the D16-7-critical tile. On that accent tint **no grey token reaches AA** (`--vy-on-grad` tops out at **4.31**) | `--vy-ink` → **6.80 / 6.57**; hierarchy now carried by size + weight, not by a colour that can't be read |
| 2 | ~20 × **"on a statement"** row links (4.14–4.48) + **"Payouts →"** (4.29) — raw accent as text | `--vy-accent-text` → 4.92–5.23 |
| 3 | Stale docblock: a **4-term** identity, with On-hold/In-transit described as *pending-only* — contradicted by the code **8 lines below it**, and by the test that asserts **5** terms | corrected to the real five-term model with the persisted statuses named |

**Contrast: 28 AA failures → 4** (281 text + 31 icon candidates).

⚠️ **The 4 survivors, honestly:** three are the gradient family (avatar, brand mark, "Add tax details" CTA).
The fourth is the **disabled "Withdraw €963.60" button at 2.33** — **formally exempt** (WCAG 1.4.3/1.4.11
exclude inactive components), and **deliberately left**: darkening it would make a control that **cannot act**
look live, which this page's first rule forbids. The amount is also stated at full contrast in the Available
tile directly above.

### 🚫 Refusals preserved

- **"Withdraw early" is genuinely disabled** — a real `<button disabled aria-disabled="true">` with
  `cursor:not-allowed`, because **there is no owner-initiated payout rail** (`StripeConnectPayoutRail::send()`
  throws; the manual rail is operator-run). Never a live-looking withdraw.
- **"In transit €0.00 · nothing in transit" is SHOWN, not hidden** — the honest zero is the point of the page.
- **No fabricated ETA** — the cadence card renders only when the owner's current agreements agree on one
  `payout_schedule`; an ambiguous set renders nothing rather than picking a value.
- **The "Why is €885.60 held?" card renders only on a genuine tax-hold**, with the real reason.
- **The internal ledger note token** (`ota_unverified`, `no_agreement`) is not rendered — it is not
  owner-facing copy; the human `description` and the Unverified badge carry the signal.

### 📌 MISSING BACKEND / logged, not built

- **DO17-1 — ⚠️ the D16-7 fix already exists HERE; the shared helper is what lags.** `payoutStatusMeta`
  (Payouts, Documents) collapses `processing` + `held` into "Payout pending"; `OwnerBalance::rowStateMeta()`
  distinguishes them correctly. *Verdict: **REAL GAP** — and the D16-7 gate now has a reference to copy.*
- **DO17-2 — no owner-initiated withdrawal rail.** Instant/self-serve payout needs the live Stripe
  connection. *Verdict: **ACCOUNT-GATED**.*
- **DO17-3 — the demo can never show "In transit" populated.** No demo payout is ever `processing`, so the
  state renders only in tests. Its chip tone was therefore **probed in and measured** rather than assumed.
  *Verdict: **REAL GAP** (demo-data), noted so a later reader doesn't mistake €0.00 for a bug.*

### ⚠️ Verified, not changed

- **#17 — the clearing identity reconciles, on screen and in code.** On the live page:
  Available **€963.60** + On hold **€885.60** + In transit **€0.00** + Paid **€2,804.40** = **€4,653.60**,
  exactly the newest running balance (Net). In code it is the five-term identity including **Carried
  forward**, asserted by `OwnerBalanceReconciliationTest` in the normal case **and** after a negative
  (carried-forward) statement.
- **One source, no Blade re-derivation** — every figure comes from the ledger + payouts via the component;
  the running balance is the same cumulative sum taken chronologically.
- **Self-scoped (#42)** — an existing test asserts the balance is scoped to the owner, hides other owners, and
  fails closed for a non-linked user.
- **#28** — the destination account shows only the **masked** `···· 1000`, CAP_PAYOUT-gated; no guest data.
- **Statuses are WORDS and distinct** — Available / On hold / In transit / Paid out / Cleared; never colour
  alone. This matters more here than anywhere, since telling them apart *is* the page's job.
- **Observed READ-ONLY** — nothing withdrawn or transferred; `payouts` still 6 (statuses `pending`,`paid`
  only), max payout `updated_at` still the seed time, 29 ledger rows unchanged, `export_downloads` still 7.
- **Keyboard: 0 unnamed controls, 0 `display:none` interactive controls, 0 `div@click`**; 0 overflow; 0
  console errors.
- ⚠️ **`toContain` sweep — clean negative** (sixteenth gate).

---

## §O18 — Owner Bookings (`/owner/bookings`, `App\Livewire\Owner\OwnerBookings`) · OWNER 18/32

**Prototype:** `docs/design/raw/Owner Bookings.html` (decoded fresh). **Role:** `External Owner`,
`$capCalendar`. A LIST/ROLLUP + MONEY surface — the first half of the list→detail pair with §O19.

### 🔴 The find: the chart's x-axis skipped empty months, and the revenue LINE stepped across the gap

`chart()` grouped **only the months that have bookings**, so a month with none was simply **absent from the
axis**. In the demo, **November 2026 has 0 bookings** (verified in the DB) — so the chart rendered
`… Sep · Oct · Dec …` and the **net-revenue polyline connected October straight to December as if they were
consecutive**, compressing a two-month gap into one step and misreporting the trend on a money chart.

Fixed by filling the month keys across the real calendar range, so the axis is uniform and an empty month is
a **genuine zero**. Verified live: the axis is now **May…Dec, eight consecutive months, with November
rendering 0px**.

⚠️ **And the zero would have drawn a bar.** The same commit had to remove the min-bar floor — present **twice**
here: `max(2, (int) $m['count_pct'])` in the view **and** a CSS `min-height: 3px` on `.vy-bk-stack`. This is
the **fifth** page with this bug and the **first time it appeared as CSS** rather than PHP — worth knowing for
the shared-`<x-charts.bars>` gate, which would not have caught a CSS floor.

### Fixed on this page (design / markup / styling only)

| # | Item | Before → after |
|---|---|---|
| 1 | **Chart axis skipped empty months**; the revenue line stepped across the gap | contiguous calendar axis; **guard added, mutation-proven** |
| 2 | **The min-bar floor, twice** — `max(2, pct)` *and* CSS `min-height:3px` | a genuine zero draws **nothing** (verified 0px) |
| 3 | `.vy-goal-delta--under` **3.30** (`--vy-amber` used as text — the O16 pattern) and `--over` (probed, unreachable today) | `--vy-amber-text` / `--vy-green-text` |
| 4 | **10 × the "Confirmed" status pill at 3.75–3.81**, and the chart's current-month net label at 3.65 — accent on an accent **tint**, where `--vy-accent-text` is only marginal (4.51 / 4.33) | `--vy-accent-tint-text` → **5.40 / 5.19** |
| 5 | Breadcrumb `.vy-crumb` 4.33 + `.vy-crumb-sep` 3.57 on the page gradient | `--vy-on-grad-soft` |
| 6 | Current-month x-label 4.01, the active sort header + caret 4.19/4.22, "Back to overview" 4.40 | `--vy-accent-text` |

**Contrast: 21 AA failures → 3** (172 text + 31 icon candidates); all 3 survivors are the gradient family.
Icons 0 failures. ⚠️ The over-goal delta only renders when the owner beats their target, which the demo does
not — so it was **probed in and measured** rather than assumed.

### 🚫 Refusals preserved

- ⚠️ **No OTA-brand split.** The source column shows the **real enum** — `Direct` / `Corporate` / **`OTA`** —
  never "Booking.com" or "Airbnb". The brand is not modelled (**D2-2**), and an existing test asserts it.
- **"Not yet attributed"** rather than a fake **€0.00** for a booking with no ledger rows yet — visible on 6
  of the 10 demo rows.
- **Guest NAME only** — the row type carries `guest` and `guest_id` and **has no email/phone field at all**;
  the reader's `guestName()` returns the name or null for an anonymized guest.
- **The monthly target is the owner's own** (`owner_monthly_target`); "12% of goal · €722 of €6k · €5.3k to
  go" are all real, and the goal line on the chart is the same figure.

### ✅ Extra work kept (beyond the prototype)

- The legend counts are computed over the **displayed** months only, so they always reconcile with the bars
  they caption — counting the full set would over-report against a truncated chart.
- The shared `OwnerBookingsReader` backs both the page and the CSV export, so the two can never disagree.

### 📌 MISSING BACKEND / logged, not built

- **DO18-1 — no per-OTA-brand breakdown.** Channels are modelled but carry no live per-channel data
  (**D2-2 / F-OP-2**); an OTA stay is "OTA". *Verdict: **ACCOUNT-GATED** (Channex).*
- **DO18-2 — the chart caps at 8 months and there is no year/range selector for it.** The table has
  Upcoming/Past/All + dates; the chart window is fixed. *Verdict: **REAL GAP** (small).*
- **DO18-3 — the min-bar floor now has a CSS form.** The planned shared-`<x-charts.bars>` fix (§N.9 #4) would
  not have caught `min-height` in a page's own stylesheet. *Verdict: **REAL GAP** — widen that gate's scope.*

### ⚠️ Verified, not changed

- **#42/#38 — MUTATION-PROVEN.** Reservations carry no `owner_id`; the boundary is applied **through the
  units**, from the owner-scoped `OwnerPortalService::units()`, never from a request. Replacing that with an
  unscoped `Unit::withoutGlobalScopes()->get()` turns *"lists only the owner's own-unit bookings"* **red**.
  Live: **two foreign booking ids → 404**, and neither foreign reference appears in the list.
- **#28 — MUTATION-PROVEN.** The existing guard seeds a guest with a real email **and** phone and asserts only
  the name renders; appending the email to the row turns it **red**. Live DOM sweep: **0 emails, 0 phone-like
  strings, 0 door-code mentions, and `guest_id` never emitted**.
- **#17 — one source.** "Net to you" comes from the shared `OwnerLedgerAttribution` via the one
  `OwnerBookingsReader`; an existing test reconciles it to the owner ledger. Nothing re-derived in the Blade.
- **Status reads the real fact and is a WORD** ("Confirmed"), never colour alone; an existing test asserts the
  real reservation status and the GET-parameter status filter (#20).
- **Keyboard: 0 unnamed controls, 0 `display:none` interactive controls, 0 `div@click`**; 0 overflow; 0
  console errors (the 2 seen were my own 404 probes).
- **Nothing mutated** — this page's only write is the monthly target, which was not fired.
- ⚠️ **`toContain` sweep — clean negative** (seventeenth gate).

---

## §O19 — Owner **Booking Detail** (`/owner/bookings/{booking}`)

`App\Livewire\Owner\OwnerBookingDetail` · prototype `docs/design/raw/Owner Booking Detail.html`.
**The per-id isolation surface (#42) and the sharpest #28 point** — one page, one booking, one guest.

### 🚫 REFUSALS TO PRESERVE (the gap IS the feature)

- **RO19-1 — the guest's EMAIL, PHONE and COUNTRY.** The prototype's guest card carries all three plus a
  "Message guest" action. The product refuses every one: the component never puts contact on the view, and the
  page says so out loud — *"Guest contact details are handled by your management team"*. **Keep the refusal
  line; never render the card the prototype draws.** An owner holding a guest's email is both a privacy breach
  (#28) and a platform-bypass risk. "Message your team" correctly routes to the **operator** relay, never
  owner→guest. *Verdict: **RATIFIED REFUSAL**.*
- **RO19-2 — the OTA BRAND.** The prototype names a channel ("Booking.com"); `reservations.source` models
  `direct|ota|walk_in|corporate|group` and no brand exists to read. Renders the real enum. *(D2-2, O18.)*
  *Verdict: **RATIFIED REFUSAL**.*
- **RO19-3 — the 15% commission.** The prototype hardcodes 15%; the page shows the **real agreement rate,
  18%**. Never mirror a prototype's money constant. *Verdict: **RATIFIED REFUSAL**.*

### 🔧 MISSING BACKEND (logged, not built)

- **DO19-1 — no per-booking timeline/event feed.** The prototype shows a dated activity trail (booked →
  confirmed → paid → checked in). `domain_events` rows exist for the reservation, but nothing owner-scoped
  reads them and no owner-safe projection exists (the raw payloads are operator-facing). *Verdict: **REAL
  GAP** — needs an owner-scoped event projection, name-only.*
- **DO19-2 — no per-booking document/invoice list.** "Download confirmation" is real; the prototype's list of
  attached documents per booking is not modelled for the owner. *Verdict: **REAL GAP** (small).*

### ⚠️ Verified, not changed

- **#42 — MUTATION-PROVEN, and this is the page's core.** Reservations carry no `owner_id`; resolution is
  `findOrFail` **then** `abort_if($ownedLines->isEmpty(), 404)` against the owner's own unit set (the #26
  shape). Removing that single `abort_if` turns *"404s another owner's booking detail"* **red**. Live: own
  bookings **200/200**; a FOREIGN booking on an operator-owned unit **in the same org** → **404**; nonsense id
  → **404**. Fail-closed for a non-linked member (empty owner set → empty owned lines → 404).
- **#28 — MUTATION-PROVEN ON THIS URL SPECIFICALLY.** The guard seeds a guest with a real email **and** phone;
  passing `leadGuest->email` through the component and echoing it on this page turns it **red**. ⚠️ **The
  first attempt was vacuous** — echoing a `guest_email` key that does not exist rendered nothing and "passed";
  the component had to be mutated too. *(The O12/O16 trap, third occurrence.)* Live sweep: **0 emails, 0
  phone-like strings, 0 door-code/card strings, no country, no address**; guest **name only**.
- ⚠️ **A DOCBLOCK CITED A TEST THAT DOES NOT EXIST.** The #28 block claimed *"OwnerBookingDetailContactTest
  proves the contact never reaches the HTML."* **There is no such file.** The guard is real but lives in
  `OwnerBookingsTest` ("renders the guest name but never their contact details"), which loops over the list
  **and** this detail URL. Citation corrected to the real test. *A named-but-absent test reads as stronger
  evidence than an unnamed one — check that a cited guard exists.*
- **#17 — €180.00 × 3 nights = €540.00 − €97.20 (the real 18%) = €442.80.** Not re-derived in the Blade.
- **Keyboard: 0 unnamed controls, 0 `display:none` interactive controls, 0 `div@click`**; 0 overflow at
  1440×1000; 0 console errors.
- **READ-ONLY** — nothing submitted; no money, no status, no message written.
- ⚠️ **`toContain` sweep — clean negative** (eighteenth gate).

---

## §O-20 — Owner **Booking Summary PDF** (`/owner/statements/{statement}/bookings`)

`OwnerBookingSummaryPdfController` → `OwnerBookingSummaryDocument::data()` → `pdfs.owner-booking-summary`,
rendered by `PdfService`. Prototype `docs/design/raw/Owner Booking Summary PDF.html`.
**Premise confirmed, with one correction:** the DOCUMENT's design was already matched at #56 — letterhead,
`BS-YYYY-MM` ref, 4-up KPI band, By unit / By channel / Bookings-this-period tables, reconciliation callout
and footer all match the decoded prototype. **What was never closed on this document is the a11y axis** —
exactly the Owner-11 shape, where the Statement PDF was likewise already design-matched.

### 🔴 THE FINDING THAT LEAVES THIS PAGE — SCREEN AND PDF DISAGREE ABOUT "NIGHTS"

For statement #6 the **statement screen** prints, under the gross line, **"8 nights · 2 units"**; the **PDF's
Nights KPI** for the same statement prints **6**. Both are real and neither is fabricated — they count
different things:

- the screen (`OwnerStatementView::grossSublabel` → `OwnerStatementBreakdown` → `PerformanceMetrics
  ::roomNightsSold`) counts **every stay-night physically occupied** on the owner's units in the period (8);
- the PDF sums the **nights of the bookings whose revenue this statement actually claimed** (3 + 3 = 6).

The screen's number is the one attached to the **gross figure**, so as written it asserts that 8 nights
generated €1,080.00 when 6 did. An owner reading the statement and its booking summary side by side sees a
two-night gap with nothing explaining it. *Verdict: **REAL GAP** — a cross-surface money-label question
(which nights belong under a gross line), so it is **surfaced, not decided**, and needs its own gate. Neither
figure was touched here.*

### 🚫 REFUSALS TO PRESERVE

- **RO20-1 — the OTA brand.** The prototype splits "Booking.com" / "Airbnb"; `reservations.source` is a
  7-value enum and `channels.provider` only knows `channex`. Groups by the real enum. *(D2-2 · O18 · O19.)*
- **RO20-2 — the letterhead city.** The prototype prints "Faro, Portugal"; the street/city is not modelled,
  so only the country renders. *Verdict: **RATIFIED REFUSAL**.*
- **RO20-3 — "Showing 8 of 18 bookings".** The prototype truncates and points at the portal; the document
  lists **every** row. Truncating an official record the owner keeps would be worse than the design.
  *Verdict: **EXTRA-KEEP**.*
- **RO20-4 — "Bookings this month" → "Bookings this period".** A statement period need not be a calendar
  month; the live wording stays true when it isn't. *Verdict: **EXTRA-KEEP**.*

### 🔧 MISSING BACKEND (logged, not built)

- **DO20-1 — no page numbering on any owner PDF.** `PdfService` stamps a per-page downloader watermark but no
  "Page X of Y", so a multi-page summary has no pagination. Belongs to the `PdfService` gate, not here.
  *Verdict: **REAL GAP** (small) — realm-wide, one place.*

### ⚠️ Verified, not changed

- **#17 — RECONCILES, ONE SOURCE, SCREEN==PDF.** By unit €540.00 + €540.00 = **€1,080.00**; by channel
  €540.00 + €540.00 = €1,080.00; the booking rows 2 × €540.00 = €1,080.00; `statements.gross_minor` =
  **108000 EUR**. The statement SCREEN prints the same **€1,080.00** — same integer, same `MoneyFormatter`
  string. Nothing is re-derived in the Blade; every figure is composed in `OwnerBookingSummaryDocument`.
- **Occupancy uses the RIGHT denominator (Owner-8).** `PerformanceMetrics::forOwnerUnits` →
  `availableRoomNights = count(unitIds) × days` = 2 × 30 = 60. Per-unit **A1 17%** (5/30) and **A2 10%**
  (3/30); headline **13%** = 8/60 — the per-unit numerators sum exactly to the headline's. Occupancy legitimately
  exceeds the summary's 6 revenue-nights because it counts all physical stays; the docblock says so.
- **#28 — the generated content stream is clean.** Guest **names only** ("Demo Walker", "Demo Resident"); **0
  emails, 0 phone numbers, no address, no country, no IBAN** anywhere in the text. The fixture behind the
  name-only test seeds a guest **with** an email and phone precisely so it cannot pass by accident.
  ⚠️ **Disclosed:** the PdfService watermark embeds the **downloader's own** address ("Downloaded by Demo
  Owner (demo-owner@vyrobel-demo.test)") — the recipient identifying themselves on their own document, by
  design, not a third party's PII.
- **#42 — MUTATION-PROVEN both ways.** `OwnerStatement::query()->findOrFail()` under `OwnerScope`; replacing
  it with `withoutGlobalScopes()` turns **both** *"404s a cross-owner booking summary"* and *"fails closed for
  a non-linked member"* red. **#30**: the route sits under `$capExports`, so a view-only sub-user is refused
  before scope is even consulted.
- **READ-ONLY, with the audit write disclosed.** Downloading writes an `export_downloads` row by design
  (`ExportAudit::stamp` — the same identity on the file and the log). After the downloads taken here the
  money is untouched: statement #6 `gross=108000`, `net_payable=88560` (the documented **€885.60**), 6 ledger
  rows.
- **Greyscale-safe** — nothing is colour-only: the highlighted cell is labelled "GROSS REVENUE", channels are
  words, total rows carry the word "Total" plus a rule.
- **The download control** is a real `<a href>` GET link, focusable and accessibly named (#20).
- **No CSS comments in this template** (the Owner-11 trap is absent here) — clean negative.
- **No docblock cites a non-existent test** on this path (the Owner-19 trap) — clean negative.
- ⚠️ **`toContain` sweep — clean negative** (nineteenth gate); both new guards use `->and()`.

### 🎨 Fixed here (this document's own literals — the realm-wide sweep is untouched)

- **`#8A8079` ×8 declarations / 25 text runs at 3.86 on white → `#5F5853` at 6.98.** Every section heading,
  table header, sublabel, KPI key, subtitle and the letterhead address.
- **`#A69A92` (footer) at 2.74 → `#6C645F` at 5.79.** Both retargeted to the values `owner-statement` already
  uses, so the two owner documents read alike. Measured on the **regenerated PDF's content stream**, not the HTML.
- **The document printed on TWO sheets; now ONE.** The footer row spilled onto a blank second page: dompdf
  applies its **own default page margin on top of** `.wrap`'s `padding-bottom`, overflowing the content box by
  a few points. `padding: 36px 40px` → `36px 40px 0` (+ `.foot` margin 26px → 18px). No HTML-level check could
  see this — the markup is valid; it took counting `/Type /Page` in the generated file.

### ⚠️ Still open for the realm-wide PDF-contrast sweep (noted, NOT hand-fixed)

`PdfService.php:54` watermark **`#a99b8e` at 2.70** (measured again here, on every page) — **PdfService was
not touched**. The pre-AA greys remain in **14 other templates**: `owner-booking-confirmation`,
`owner-agreement`, `owner-agreement-amendment`, `owner-remittance`, `owner-annual-tax`,
`owner-statement-history`, `operator-report`, `maintenance-costs`, `arrival`, `folio`, `guest-statement`,
`guest-vat-invoice`, `guest-credit-note`, `guest-refund-receipt`. ⚠️ **Pages 21 and 22 of this batch
(Remittance, Annual Tax) are two of them** — expect the same pair on both.

---

## §O-21 — Owner **Remittance Advice PDF** (`/owner/payouts/{payout}/remittance`)

`OwnerRemittancePdfController` → `OwnerRemittanceDocument::data()` → `pdfs.owner-remittance`.
Prototype `docs/design/raw/Owner Remittance Advice.html`. Second of the 3-PDF batch.

### ⚠️ PREMISE CORRECTION — the "Management fee −€150.00" is **REAL AND MODELLED**, not a fabrication

The brief flagged the prototype's *"Management fee · Fixed monthly −€150.00"* as possibly a fabricated
deduction. It is not. The fee is a **fully modelled, certified deduction**:
`ManagementAgreement.management_fee_minor` + `management_fee_interval` → **`OwnerAttributionService
::attributeManagementFee()`** (pro-rated across the interval, idempotent on the period key, posted as an
`owner_ledger` `management_fee` **debit**) → a real statement line → the advice.

**The demo agreement simply carries no fixed fee** (`management_fee_minor = NULL`), so
`attributeManagementFee()` returns `null` and the line is **genuinely absent** — the `payment_fee` pattern
from #70, not a refusal. *Verdict: **NOT A GAP, NOT A REFUSAL** — a real deduction that is honestly zero for
this owner.* Proven in **both directions** by a new guard: with no fee configured no line renders; with a real
**€150.00** monthly fee configured, the certified service posts it and the advice states it.

### 🔴 D16-7 · A FOURTH FORM — a real payout state rendered UNSTYLED (fixed here)

`payouts.status` has **six** values (`pending, processing, paid, failed, reversed, held` — `processing` added
by the transfer rail, `held` by the payout engine). The document's pill had rules for **four**. A `processing`
or `held` payout therefore emitted `class="pill pill--processing"` / `pill--held` **with no matching rule**:
the plate and colour vanished and the status degraded to bare body text. ⚠️ **Exactly D16-7's two states.**
The **label was never dishonest** (`ucfirst()` of the real enum → "Processing" / "Held"), so the document
never lied — it just stopped looking like a status. Fixed by giving both the **pending tone**, mirroring
`OwnerBalance::rowStateMeta()` (the D16-7 reference), where the two share a tone and are told apart by their
**word** — so nothing new was invented and greyscale still carries the state.

### 🔧 MISSING BACKEND (logged, not built)

- **DO21-1 — `payoutStatusMeta` still collapses `processing` and `held`.** Verified again at
  `FormatsOwnerData.php:99`: the `match` has cases for `paid` / `failed` / `reversed` only, so **both** fall to
  `default => 'Payout pending'` — on Payouts, Documents, Statement and Dashboard. ⚠️ **This document does NOT
  use that helper** (it reads the enum directly), so it is on the honest side and nothing was diverged here.
  *Verdict: **REAL GAP** — the D16-7 gate, one shared helper, copy `OwnerBalance::rowStateMeta()`.*
- **DO21-2 — three vocabularies for one state.** A `held` payout reads **"On hold"** via
  `rowStateMeta()`, **"Payout pending"** via `payoutStatusMeta` and **"Held"** on this document. Two of the
  three are honest and still disagree in wording. *Verdict: **REAL GAP** — settle the vocabulary in the D16-7
  gate; **surfaced, not decided** here.*

### 🚫 REFUSALS TO PRESERVE (all pre-existing, re-verified)

- **The prototype's 15% rate.** The advice shows the **real agreement rate**: €1,080.00 gross → **−€194.40**
  commission = exactly **18.0%** (15% would be €162.00). The figure is the certified statement line, never a
  percentage computed in the document.
- **An estimated settlement date, a "Scheduled" status, a "SEPA" claim, the design's IBAN "···· 4821" and its
  `OS-`/`RA-2025-` references** — all omitted; `paid_on` appears only once a payout is genuinely paid, and the
  rail word comes from the shared `RailLabel`. Guarded by an existing test.

### ⚠️ Verified, not changed

- **#17 reconciles, SCREEN==PDF.** Gross **+€1,080.00** − commission **€194.40** + OTA commission **€0.00**
  (flagged PROVISIONAL) = **€885.60** = `payouts.amount_minor` = the statement net. The owner **Payouts
  screen** shows the same **€885.60** and **€442.80** for the two payouts rendered here. Lines come from real
  `owner_statement_lines` through the shared `MoneyFormatter`; nothing is re-derived in the Blade.
- **#28.** IBAN **masked** — "IBAN ···· 1000", never raw (`Iban::masked` takes only `iban_last4`, structural),
  and the controller resolves it **CAP_PAYOUT-gated per owner**, omitting the row entirely otherwise. **No
  guest data**: statement lines are bucket labels, so no guest identity can reach the document. No raw IBAN on
  the screen either.
- **#42 — MUTATION-PROVEN both ways.** `Payout::query()->findOrFail()` under `OwnerScope`; replacing it with
  `withoutGlobalScopes()` turns **both** *"404s a cross-owner remittance PDF"* and *"fails closed for a
  non-linked user"* red. **#30**: the route sits under `$capExports`.
- **#71 test-mode honest** — a "paid" advice reflects the real recorded status; nothing claims a live transfer.
- **Greyscale-safe** — every state is a WORD in the pill; the amount label changes with it ("Net payable to
  owner" vs "Net paid to owner"), so no state is carried by colour alone.
- **The download control** is a real `<a href>` GET link named "Remittance" (#20), **observed READ-ONLY**; the
  `export_downloads` audit row writes by design and the money is unchanged.
- **Single page**, both states. **No CSS comments** in this template (the Owner-11 trap absent) — clean negative.
- **No docblock cites a non-existent test** (the Owner-19 trap) — clean negative.
- ⚠️ **`toContain` sweep — clean negative** (twentieth gate); all new guards use `->and()`.

### 🎨 Fixed here (this document's own literals)

- **`#8A8079` ×5 (14 text runs) at 3.86 → `#5F5853` at 6.98**; **`#A69A92` at 2.74 → `#6C645F` at 5.79** —
  the same pair O20 found, retargeted to the same values.
- **`#B26A08` → `#804C05`** (the project's amber-text). It carried the **PENDING pill and the PROVISIONAL
  flag**: 4.24 on white and only **3.72 on the `#FBEFD8` plate it actually sits on**, at 8.5px bold. Now
  **6.24** on the plate / **7.11** on white. ⚠️ The other three pills already passed on their own plates
  (paid 5.61 · failed 5.76 · reversed 5.08) — measured, not assumed.
- **`.pill--processing` / `.pill--held`** added (see D16-7 above).

### ⚠️ Still open for the realm-wide PDF-contrast sweep

`PdfService.php:54` watermark **`#a99b8e` at 2.70** — **untouched**. **13 templates** still carry the pre-AA
greys (O20 cleared the booking summary, this gate the remittance). ⚠️ **Page 22 (Annual Tax Summary) is one of
them** — expect the same pair again.

---

## §O-22 — Owner **Annual Tax Summary PDF** (`/owner/tax/{year}/summary.pdf`)

`OwnerTaxController::downloadAnnual` → `OwnerTaxService::annualSummary()` → `OwnerAnnualTaxDocument::data()`
→ `pdfs.owner-annual-tax`. Last of the 3-PDF batch. **The peak-authority money document** — an owner may hand
it to an accountant.

### ⚠️ PREMISE NOTES (both verified fresh)

- **The #60 multi-year fabrication is already refused — STRUCTURALLY, and more strongly than briefed.** The
  document renders exactly ONE year, and the URL carries **no owner id at all**: the owner is resolved from
  `$request->user()`, so a cross-owner request is **not expressible**. A year with no committed statements
  returns `null` from `annualSummary()` → **404**. Live: **2025 → HTTP 404**, 2026 → 200. The demo tenant has
  a single year (2026, 6 statements), and nothing invents a prior-year total or the prototype's "€98,710".
- **The period is the REAL statement span, not a fabricated calendar year.** The document prints
  *"1 Apr 2026 – 30 Sep 2026"*, not "1 Jan – 31 Dec". An existing `CopyDriftTest` guard already forbids the
  "All 12 months" phrasing.

### 🔴 A STALE DOCBLOCK POINTING THE DANGEROUS WAY (fixed)

**Both** docblocks on this document — `OwnerAnnualTaxDocument.php:17` and the template's header comment —
still said *"no withholding (we don't compute it)"*. That went stale when the tax engine (#73) shipped:
`OwnerTaxService::annualSummary()` builds a real `withholding_minor` bucket and emits a **"Withholding tax"**
category (`OwnerTaxService.php:181`), which reaches the categories table like any other deduction.

⚠️ **The staleness pointed the dangerous way.** A developer who believed the comment might add a *second*
withholding line and **double-count a real payout deduction on a tax document**. Both comments corrected, and
the behaviour pinned by a new both-directions guard. *(Owner-19 found a docblock citing a test that does not
exist; this is the sibling — a docblock denying a feature that does.)*

### ⚠️ #73 — the tax framing, verified

- The document states the **withheld AMOUNT only — it never prints a rate.** ⚠️ A first probe reported "a
  percentage is shown"; that was a **false positive** matching CSS `width: 33%`. Re-run against visible text
  only: **no rate, no percentage anywhere** — the withholding figure is a real deduction already taken from
  the payout, not a computed determination.
- The footer carries the honest framing: *"Figures are gross of any income tax due, which is the owner's
  responsibility. This document is not tax advice — please share it with a qualified accountant."*
- **DO22-1 — when withholding is non-zero, the document does not say the figure derives from CONFIGURED
  rates.** #73's rule is that outputs are labelled *"computed from your configured rates"*, and the Reports
  surface carries an explicit **"EXAMPLE rates — not for filing"** warning; this document has only the generic
  disclaimer. The demo's withholding is genuinely €0 so no line renders today. *Verdict: **REAL GAP** (copy) —
  **surfaced, not decided**: adding legal-facing wording to a tax document is the founder's call, not a design
  gate's.*

### ⚠️ Verified, not changed

- **#17 reconciles, one source.** Gross **€4,500.00** − deductions **€810.00** = net **€3,690.00**. The month
  rows sum exactly (540+800+570+630+880+1,080 = 4,500 · 97.20+144+102.60+113.40+158.40+194.40 = 810 ·
  442.80+656+467.40+516.60+721.60+885.60 = 3,690), and **€810.00 / €4,500.00 = exactly 18.0%** — the real
  agreement rate. Deductions-by-category totals to the same €810.00. Every figure is aggregated in
  `annualSummary()` and formatted through the shared `MoneyFormatter`; nothing is re-derived in the Blade.
- **Only real categories render** — the zero filter drops any category with no figure, so no invented €0.00
  row (proven by mutation: removing the filter turns the new guard red).
- **#42 / #30.** Self-only by construction (no owner id in the URL); a non-linked user 404s; the route sits
  under `$capExports` (co-owner + accountant). Both guarded, and the empty-year 404 is **mutation-proven**
  (making it borrow another year turns the existing test red).
- **#28.** No IBAN on this document at all, and **no guest data** — categories are bucket labels. The owner's
  own tax id / legal name appear because it is their own self-only document.
- **Greyscale-safe** — every deduction carries a real U+2212 minus in the figure, so the sign never depends on
  colour.
- **Single page**; **no CSS comments** (the Owner-11 trap absent); **no docblock cites a non-existent test**.
- ⚠️ **`toContain` sweep — clean negative** (twenty-first gate); both new guards use `->and()`.

### 🎨 Fixed here

- **`#8A8079` → `#5F5853`: 3.86 → 6.98, across 27 text runs — the widest spread of the three PDFs.** It
  included **`.neg`, which is the deduction figures themselves** (−€810.00, −€97.20 …) — the O2/O11 finding
  recurring on the one document most likely to be read by an accountant.
- **`#A69A92` → `#6C645F`: 2.74 → 5.79 — the footer**, ⚠️ **which is exactly where the "not tax advice"
  framing lives.** The legal disclaimer was the least legible text on the document.

### 📌 THE REALM-WIDE PDF-CONTRAST SWEEP — recommendation (surfaced, NOT self-authorised)

Across the three PDF gates the pattern is now established and identical every time: **the same two literals**
(`#8A8079` at 3.86, `#A69A92` at 2.74), retargeted to **the same two replacements** (`#5F5853`, `#6C645F`),
each verified on the generated file. Three templates are clear (statement · booking summary · remittance);
**12 remain**: `owner-agreement`, `owner-agreement-amendment`, `owner-booking-confirmation`,
`owner-statement-history`, `operator-report`, `maintenance-costs`, `arrival`, `folio`, `guest-statement`,
`guest-vat-invoice`, `guest-credit-note`, `guest-refund-receipt` — plus **`PdfService.php:54`'s `#a99b8e`
watermark at 2.70**, which is the only *shared* one and appears on **every page of every document**.

**Recommendation: yes — fold it into ONE dedicated gate now, and do the watermark in that same gate.** The
substitution is known and mechanical, but three things make it a poor fit for page gates and a good fit for
one sweep: (1) the watermark is in `PdfService` and touches every document, so no page gate may touch it —
it will otherwise never be fixed; (2) each template still needs its **own generated-file measurement**,
because per-document plates change the result (O21's `#B26A08` passed on white at 4.24 and failed at 3.72 on
the plate it actually sat on) and some greys sit on dark bands where darkening is backwards (the statement's
`.band`); (3) the guest and operator documents are outside the owner realm, so they will not be reached by
this pass at all. **Not started — awaiting the founder's go-ahead.**

---

## §O-23 — Owner **Block Calendar** (`/owner/calendar`)

`App\Livewire\Owner\OwnerBlockCalendar` → `OwnerBlockService` → the certified `AssignmentService`.
Prototype `docs/design/raw/Owner Block Calendar.html`. Route gated by `$capCalendar`.

### 🔴 THE O10 KEYBOARD LOCKOUT, FOUND AGAIN IN A SECOND COPY OF THE GRID

The Unit Detail calendar was fixed at O10. **This page has its own grid in its own view** — `block-calendar
.blade.php`, not a shared partial — so that fix never reached it, and the identical defect was still live:

- an **open** day was `<div class="vy-cal-cell vy-cal-open" @mousedown.prevent="startSel(…)"
  @mouseenter="overSel(…)">` — no `tabindex`, no `role`, and **no keydown handler on any cell** (verified: 0);
- **15 open cells, 0 focusable.** Selecting was **mouse-drag only** — and selecting is the first half of
  BLOCKING, which takes inventory off-sale through `OwnerBlockService`. **A keyboard user was locked out of a
  money-affecting action on the page built for it.**

**Fixed** (markup/styling only — no backend, no engine, no guard touched): every open day is now a real
`<button type="button">` with a spoken name (*"19 August 2026 — open. Select to block this day."*),
`aria-pressed` reflecting selection, a `:focus-visible` ring, and a **keyboard range path** — `pickSel()`
anchors on the first Enter/Space and extends on the second. It is bound so it fires only for a
pointer-detail-less click, i.e. **keyboard activation only**, so the mouse drag is left exactly as designed.
**Proven live: a 4-night range (19–22 Aug) selected entirely by keyboard, with zero network activity.**

⚠️ **The prototype is mouse-only by construction** (`<div>` cells, `tabindex` count = **0** in the whole
decoded file), so this is an added fix, not a divergence from the design — the same call as O10.

### ⚠️ PREMISE CORRECTION — a foreign unit FALLS BACK, it does not 404

The brief expected a foreign unit's calendar → 404. The real behaviour: `resolveUnit()` matches the requested
`?unit=` **only within the owner's own scoped collection**, so a foreign id simply never matches and the page
falls back to the owner's first own unit. Live, `?unit=<operator-owned unit>` renders **A1**, the foreign id
appears **nowhere** in the HTML, only A1/A2 are offered, and the hidden `unit_id` posts the owner's own unit.
**No leak — the refusal is structural — but it is a silent fallback, not a 404.** The WRITE path is separately
guarded (`ownedUnitOrFail()`), and that guard is **mutation-proven**.

### 🔧 MISSING BACKEND (logged, not built)

- **DO23-1 — no channel/OTA state on any day.** The prototype's states are Booked / Owner block / Selecting /
  Open, and that is exactly what renders. There is no "blocked by channel" or OTA-synced day because there is
  no live channel data (D2-2 / Channex is account-gated). *Verdict: **ACCOUNT-GATED** — the honest four-state
  legend is correct; a fifth state must not be invented.*
- **DO23-2 — the two calendar grids are duplicated, not shared.** Unit Detail and Block Calendar each carry
  their own day-cell markup and CSS. O10's fix had to be re-made here from scratch, and the next change will
  have to be made twice again. *Verdict: **REAL GAP** — extract one `<x-owner.day-cell>` / grid partial. ⚠️ The
  shared-partial trap in its "second unfixed copy" form.*

### ⚠️ Verified, not changed

- **READ-ONLY — nothing was written.** Baseline before probing: `inventory_blocks = 2`,
  `max(updated_at) = 2026-08-09 07:17:59`, `availability` 650 rows, `sum(blocked) = 6`, `sum(sold) = 105`.
  **Identical after** the full keyboard walk-through. No block or release was ever fired; the keyboard
  selection only mutates Alpine state and opens the composer.
- **§N.4k Enter-safe by construction.** The POST form (`x-ref="blockForm"`) contains **only `type="hidden"`
  inputs** and is submitted explicitly by `doBlock()`. The visible reason/note/repeat controls sit **outside**
  any form, so Enter in the note can never submit a block. *(Its `display:none` is not the GATE-30 defect —
  hidden inputs are never focusable.)*
- **#28 — guest NAME only on a booked cell.** "Demo Booker" / "Demo Resident" in the cell and its `title`;
  no email, phone or reference. Consistent with the Owner-18/19 precedent.
- **#39 — every day-state is real**: booked from a real reservation, blocked from a real `InventoryBlock`,
  past from the date. The month's stat line ("0 held · 6 booked · 15 open") counts the real grid.
- **1.4.1 — state is no longer colour-only.** Open days now carry a spoken name and `aria-pressed`; booked,
  blocked and past cells carry a `.vy-sr-only` state word, so an assistive-tech user can tell them apart
  without the tint (previously an open and a past cell were both bare divs holding a number).
- ⚠️ **`toContain` sweep — clean negative** (twenty-second gate).

### 🎨 Contrast — 22 → 3 (all three the known white-on-gradient family)

- **`.vy-cal-past .vy-cal-num` `#C9BEB6` at 1.48–1.57 across 10 past days — the worst reading of the owner
  realm so far.** ⚠️ The O17 *disabled-stays-dim* exemption does **not** apply: these are **text**, not
  disabled controls, and WCAG has no exemption for "inactive" text. Fixed to `--vy-muted`; the cell's own
  dimmer plate still keeps past days recessive.
- `#A69A92` ×4 and `#9C9089` ×2 (stat line, "N blocks", eyebrows, repeat summary) at **2.17–2.50** →
  `--vy-muted`.
- `.vy-cal-guest` / `.vy-cal-series` / active reason `#B4400F` at **3.90–4.10** — the guest name sits on the
  booked accent **tint**, where accent-text is marginal (the O19 finding) → **`--vy-accent-tint-text`**.
- The empty-state calendar icon failed 1.4.11 at **1.54**; it merely repeats the adjacent sentence, so it is
  now `aria-hidden` (decorative) rather than darkened.
- **Survivors (3), all the known family, none added:** the shell avatar 3.83, brand mark 3.88, and the active
  unit pill "A1" 3.90 — white on the accent gradient.
- **Also fixed: the note input had NO focus indicator at all** (`outline:none`, no replacement — a real 2.4.7
  failure) **and no accessible name** (placeholder-only). Now `:focus-visible` + `aria-label`. ⚠️ A first probe
  reported `outline:none` on the whole nav too — a **false positive** reading unfocused defaults; re-run
  against the focused state, only the note was real. *(8th probe FP of this pass — correct the probe.)*

---

## §O-24 — Owner **Archive** (`/owner/archive`)

`App\Livewire\Owner\OwnerArchive` (`statusMeta` from `FormatsOwnerData`). Prototype
`docs/design/raw/Owner Archive.html`.

### ⚠️ TWO PREMISE CORRECTIONS

1. **This is owner page 24, not 25 — page 24 (Reports) has NOT run.** HEAD was `899e9e3` (O23) and the index
   read *"NEXT = owner page 24"*. Archive was built as asked, and is indexed **24/32**; **Reports remains
   outstanding** and is now the next page.
2. **The prototype's fabrications were already refused, and the ZIP is already honest.** `#60`'s rulings are
   live in code: "3 years · 34 documents", "since June 2023", the 7-year retention promise and the
   `OS-2025-09` reference format are all NOT PORTED (a guard forbids each), and the full-archive ZIP is a
   genuinely `disabled` + `aria-disabled` button labelled "Soon". Live sweep: **zero fabricated needles**.

### 🔴 A DOCBLOCK DENYING A REAL FEATURE — TWICE (the O22 pattern)

`OwnerArchive.php` listed the per-year tax summary under **NO BACKEND**, stating *"there is no tax engine, and
its 'Owner Annual Tax Summary PDF' does not exist"*. Both halves are **false since #73 shipped** — and the
document is the very one gated at **O22**. A test comment carried the same stale claim.

⚠️ **The code was already right; only the comments were wrong.** The view links `owner.tax.annual` per year,
`@ownercan(CAP_EXPORTS)`-gated, and it renders live as "Tax summary". **Had I acted on the docblock I would
have added a duplicate link to a feature that already worked** — the inverse of O22's risk (there, believing
the comment would have double-counted a deduction). Both comments corrected. *Trust the code, not the prose
about it.*

### ⚠️ SCOPE — the ungated route is CORRECT, and gating it would have been a regression

`/owner/archive` carries **no capability middleware**, while its sibling `/owner/documents` is
`$capDocuments`-gated — which looks like a gap until the matrix is read: **`CAP_FINANCIALS` ("earnings &
statements") is held by ALL THREE owner roles**, and an archive of statements is exactly that. Documents is
gated because it is the *documents library* (co-owner + accountant). **Adding a gate here would have removed a
view-only sub-user's intended access to their own earnings.** Every download control on the page *is*
`@ownercan(CAP_EXPORTS)`-gated (statement PDF · bookings PDF · tax summary), so the control matches the route
it points at (#30).

### 🔧 MISSING BACKEND (logged, not built)

- **DO24-1 — the full-archive ZIP has no builder.** Honestly disabled today. *Verdict: **REAL GAP** (small) —
  the #60 drop, correctly expressed as a dead control rather than a fake link.*
- **DO24-2 — a `held` or `processing` payout cannot be expressed here.** ⚠️ Archive derives its badge from
  `statusMeta()` — the **statement** enum (draft/finalized/paid), all three mapped — so it does **NOT** inherit
  the D16-7 `payoutStatusMeta` collapse. But a finalized statement always reads **"Payout pending"**, even when
  its payout is really **held** for missing tax details, which is *actionable*. The demo has no held payout, so
  this is **latent, not visible**. *Verdict: **REAL GAP** — belongs to the D16-7 gate; **surfaced, not
  decided**, and deliberately NOT diverged locally.*
- **DO24-3 — the per-year bars are sparse, not contiguous.** `yearGroups()` groups the real statements, so a
  year with none is skipped. ⚠️ **Deliberately NOT "fixed" as an O18 repeat:** O18's bug was an unlabelled
  monthly *line* that joined non-adjacent months; these are **labelled bars**, and inserting a €0 bar for a
  year the owner was not yet onboarded would assert *"you earned nothing that year"* — a #60-class fabrication
  in the other direction. *Verdict: **REAL GAP (judgement)** — if filled, it must be filled only within the
  owner's real active span.*

### ⚠️ Verified, not changed

- **#60 / #39 — MUTATION-PROVEN.** Replacing the derived sub-header with the prototype's *"3 years · 34
  documents"* turns the existing guard **red**. Live, the real single-year tenant reads **"1 year · 6
  statements"**, **"SINCE APRIL 2026"**, one real bar for 2026 — never the prototype's fiction.
- **#17 reconciles, one source.** The six statements (885.60 + 721.60 + 516.60 + 467.40 + 656.00 + 442.80) sum
  to **€3,690.00 exactly**, which is the lifetime hero, the 2026 per-year figure — **and the same €3,690.00 the
  Annual Tax Summary PDF stated at O22**. Totalled **per payout currency**, never a cross-currency sum.
- **#42/#38 — MUTATION-PROVEN.** Replacing the scoped query with `withoutGlobalScopes()` turns **three** guards
  red, including *"scopes the archive to the owner, hides other owners, and fails closed for a non-linked
  user"*. A rollup leaks many; this one does not.
- **#28** — no guest data of any kind (the archive lists statements, not stays); **0 emails, no raw IBAN**.
- **READ-ONLY** — nothing fired; the ZIP is disabled and no export was downloaded.
- **Keyboard** — search is named (`aria-label="Search statements"`); the "Clear" button is `x-show`-bound and
  **becomes focusable exactly when a query exists** (probed in: it appears, is reachable, and filters to the 1
  real matching row). ⚠️ Two "unnamed inputs" and four "unreachable divs" were **probe false positives** — the
  inputs are `type="hidden"` and the divs are the shell's Alpine roots. *(9th probe FP — correct the probe.)*
- **Status is not colour-only** — "Paid" / "Payout pending" / "Contains unverified lines" are words.
- ⚠️ **`toContain` sweep — clean negative** (twenty-third gate).

### 🎨 Contrast — 9 → 2

- **6 × the "View" row link at 4.45–4.49** — raw `var(--vy-accent)` used as TEXT, marginally under AA on the
  row tint → **`--vy-accent-text`**. *(The accent-as-text sweep; one declaration, six rendered sites.)*
- **The sub-header "Your finalized history · …" at 3.98** — it sits on the **hero gradient**, where the
  page-body muted token cannot reach AA → **`--vy-on-grad`**.
- **Survivors (2), both the known shell gradient family, none added:** avatar 3.83, brand mark 3.88.

---

## §O-25 — Owner **Reports** (`/owner/reports`)

`App\Livewire\Owner\OwnerReports` (+ `OwnerCommissionRate`, `OwnerLedgerAttribution`, `OwnerVatService`).
Prototype `docs/design/raw/Owner Reports.html`. Read-only: year / custom-range / export are all GETs.

### 🔴 THE FIX — a month that earned nothing was drawing a bar

The net-by-month chart carried a **two-percent floor on the bar height**, applied twice (the bar and its
value label). Live, **five months that genuinely earned €0.00 each rendered a visible bar** — Jan, Feb, Mar,
Nov and Dec — while printing "€0.00" right above them. **The chart contradicted its own printed figure.**

⚠️ **The axis was already correct** — `netByMonth()` seeds every month in the range and walks `addMonth()`,
so an empty month keeps its slot. The O18 *axis* bug does **not** apply here; only the bar did. Fixed to the
honest value, with a 3px hairline **only when the month is non-zero**, so a small real month stays visible
without inventing height for a zero. Live after: the five zero months render **0px**, the axis still shows all
twelve.

### 🔴 A #60 GUARD THAT COULD NOT FAIL — and two bad needles before the right one

*"shows the real reporting-year dropdown, not fabricated prior years"* asserted **only positives** ("Reporting
year", the current year, "Year to date"). Its comment claimed *"the fabricated years are gone"*, but **nothing
tested that** — hardcoding the wireframe's `2026/2025/2024/2023` list left it **green**.

Repairing it took three attempts, and the two failures are worth recording:
1. `assertDontSee('2024')` — **fails on correct markup**: a bare `2024` legitimately appears in the
   **custom-range month picker** ("Dec 2024"). *(The O14 too-generic-needle lesson.)*
2. `assertDontSee('range=year:2024')` — **can never fail**: that is not the emitted href.
3. ✅ `assertDontSee('year=2024')` — the real GET key. **Mutation-proven**: hardcoding the wireframe's years
   now turns it red.

*A guard's needle must be taken from the rendered output, not from what the parameter is assumed to be.*

### ⚠️ THE ARCHIVE CARRY-FORWARDS — BOTH CHECKED, NEITHER APPLIED

- **The stale-docblock-denies-a-feature pattern (O22/O24) does NOT recur here.** `OwnerReports`' docblock is
  **accurate**: it already describes the Tax & VAT summary as *"REAL (Wave D · tax engine) … COMPUTED …
  honestly labelled"*. Checked explicitly because a reporting page is a likely home for a stale "we don't
  compute VAT" note. **Clean negative.**
- **No €0 row was inserted for a pre-membership year (DO24-3).** The year dropdown lists only real statement
  years plus the current one; nothing was back-filled.

### 🔧 MISSING BACKEND (logged, not built)

- **DO25-1 — the month chart is a local implementation, not the shared `<x-charts.bars>`.** `.vy-rep-bar` is
  this page's own markup, which is why the min-bar floor survived here after being fixed elsewhere. *Verdict:
  **REAL GAP** — the O23 duplication axis again; the shared-bars gate should absorb this chart.*
- **DO25-2 — whether the "Example rates" flag needs formal legal wording is the founder's call** (DO22-1,
  restated). The page already carries *"not tax advice"* + an EXAMPLE-rates tag; nothing was added.

### ⚠️ Verified, not changed

- **The commission rate is the REAL 18%, never the mock 15%.** Live: *"Commission · 18% −€1,076.40"* on a
  gross of €5,980.00 — exactly 18.0%. **Mutation-proven**: forcing `$percent = 15.0` turns the existing
  gross-to-net guard **red**. `15%` appears nowhere on the page.
- **#17 reconciles four ways to the same €4,653.60**: gross-to-net (5,980.00 − 1,076.40 + 0.00 − 250.00) ·
  per-unit (2,496.60 + 2,157.00, shares 54% + 46% = 100%) · by-channel (4,460.80 + 442.80 − 250.00) · the
  month chart. One source (`OwnerLedgerAttribution`), no Blade re-derivation.
- **Occupancy denominator (Owner-8)** — 8% across 2 units, from the shared `PerformanceMetrics` owner path.
- **#60** — only the real year (2026) is offered; no 2025/2024/2023 in the reporting-year list.
- **#73 framing intact** — *"not tax advice"* plus an **EXAMPLE RATES** tag on the VAT summary.
- **#28** — the payout card's IBAN is masked (`···· 1000`); no guest data anywhere.
- **READ-ONLY** — every export is a GET link (PDF · CSV · Spreadsheet · Annual tax summary · per-statement
  PDFs), all named; **none fired**, so no `export_downloads` row was written by this gate.
- **Keyboard: 0 unnamed, 0 `display:none` controls, 0 genuinely unreachable.** ⚠️ Six `vy-rep-pop` wrappers
  looked unreachable — a **probe false positive (10th)**: they carry only `@keydown.escape.window` and each
  has a real `<button>` trigger ("2026", "Custom range", "Export"). Escape-to-close is itself keyboard support.
- **Metrics are not colour-only** — every figure is a number with a word label; the channel bar is backed by a
  labelled legend with percentages.
- ⚠️ **`toContain` sweep — clean negative** (twenty-fourth gate).

### 🎨 Contrast — 15 → 5

- ⚠️ **`.vy-soon-tag` "EXAMPLE RATES" at 3.82 — the least legible text on the page**, and it is the **#73
  warning itself**. *(The same shape as O22, where the "not tax advice" framing was the least legible text on
  the tax PDF.)* Fixed to `--vy-amber-text` **on its own inline override** — the `.vy-soon-tag` class is
  defined in the shared owner shell and was **not** retuned for one page.
- Chart value labels + month labels (`--vy-faint`) at **4.24–4.49** on the peach chart area → `--vy-ink` /
  `--vy-muted`.
- **6 accent-as-text declarations** at 4.38–4.48 → `--vy-accent-text` (the "All →" / "Tax center →" links, the
  per-unit total, the gross-to-net net figure, the KPI delta).
- **Survivors (5), all white-on-accent-gradient, none added:** shell avatar 3.83 · brand mark 3.88 · the peak
  bar's value chip 3.88 · the Export button 3.93 · the hero badge 3.93.

---

## §O-26 — Owner **Reviews** (`/owner/reviews`)

`App\Livewire\Owner\OwnerReviews` → the shared `OwnerReviewSummary` / `ReviewList`.
Prototype `docs/design/raw/Owner Reviews.html`. Read-only by design (the operator owns the response).

### 🔴 THE FIX — every rating rendered as FIVE FULL STARS

The hero star row was:

```blade
{{ str_repeat('★', round($avg)) }}{{ str_repeat('★', 5 - round($avg)) }}
```

**Both halves repeat the SAME filled glyph.** So the row rendered five gold stars for *any* rating — live,
a **4.5 average displayed as a perfect 5**, and by that code a 3.0 would have too. On a page that reads as
social proof, the glyphs overstated the real figure while the correct number sat beside them.

**Fixed by extracting the shared `<x-star-rating>`** (below), which additionally:
- **floors** the filled count — ⚠️ my first version rounded, which still showed 4.5 as a perfect five; on a
  social-proof surface the honest rule is **never round up**, and the exact figure is always printed alongside;
- marks the remainder with a **different glyph** (`☆`), so filled-vs-empty survives greyscale (1.4.1) rather
  than depending on gold-vs-grey;
- carries **one accessible name** ("4.5 out of 5 stars") with the glyphs `aria-hidden`, so a screen reader
  hears the rating once instead of five anonymous stars.

Live after: **★★★★☆ for 4.5**.

### 🔧 DO10-4 — the ★ glyph, partly closed

`<x-star-rating>` is **new and additive**; the owner Reviews hero is wired to it. **The other ten call sites
are untouched** — `booking/browse`, `booking/room`, `emails/review-request`, `guest/trips`, `guest/review`,
`guest/partials/trip-card`, `livewire/units-list`, `livewire/owner/units`, `livewire/owner/unit-detail`,
`operator/reviews`, plus two further in-page star spans on this page itself. Migrating them touches the
guest, operator, public **and email** layouts, each with its own size/colour, which is beyond a single page
gate. *Verdict: **REAL GAP** — DO10-4 now has a component to migrate TO, and the migration is its own gate.
**No 12th hand-written copy was added.***

⚠️ The component's colours are **self-contained literals, not tokens**, precisely because it must render in
five different shells — the O6 trap (a shared partial depending on a token only one shell defines).

### ⚠️ Verified, not changed

- **#28 byline is first-name + initial, STRUCTURALLY** — `GuestName::reviewByline()` is the one rule
  (`"Sofia" + "M."`, or `"A guest"` when no first name). Live: `Demo W.`, `Anna K.`, `Sofia M.`, `Henrik L.`,
  `Marta R.`; **0 emails in the DOM**. ⚠️ **The existing guard was strengthened**: its fixture gave the guest
  no contact, so it could prove the surname was trimmed but *could not* prove the projection withholds an
  email or phone — a guest with no email cannot leak one. Contact is now seeded and forbidden.
  **Mutation-proven twice**: emitting the full surname → red; appending the guest's email to the byline → red.
- **READ-ONLY is structural (Phase 5).** The page contains **no review form at all** — the only two forms in
  the document are the shell's notification "mark all read" and "sign out". No reply/flag/moderate control
  exists to disable, so there is no live-looking control that cannot post. ("Report" in the DOM is the
  sidebar's **Reports** nav item, verified — not a flag control.)
- **#39 — the aggregates reconcile from the real reviews.** Distribution 5★→4, 4★→1, 3★→1, 2★→0, 1★→0 = **6
  reviews**, matching the printed count and the per-villa split (3 + 3). And **(5×4 + 4×1 + 3×1) / 6 = 4.5**,
  exactly the displayed average. An existing guard covers the honest empty state ("no fabricated aggregate on
  an empty page").
- **#42/#38** — unit ids come from the owner-scoped `OwnerPortalService::units()`; a foreign unit's reviews
  cannot enter the query. The pending rate-proposal count is on `RateProposal`, which carries
  `BelongsToOwner`, so that count is this owner's too (checked because a bare `count()` is where a rollup
  leaks).
- **Keyboard: 0 unnamed controls, 0 `display:none` controls, 0 genuinely unreachable** (the 4 flagged were the
  shell's Alpine roots — the known FP). The villa filter is real `<button>`s.
- ⚠️ **`toContain` sweep — clean negative** (twenty-fifth gate).

### ⚠️ CONTRAST — NOT MEASURED THIS GATE (tooling failure, disclosed)

The validated method is: hide text → screenshot → sample the composited pixel. **The screenshot tool timed out
repeatedly**, including on a page that captured successfully earlier in the session, so it is an environment
failure rather than anything about this page.

I wrote a CSS-compositing fallback and **rejected its output**: it scored white text at ratio 1.0 by comparing
it against a background layer that is actually painted over, and reported 90 "failures" on a page whose
predecessors measured 2–5. **Publishing numbers from a probe I know to be unsound would be worse than
publishing none** *(the 11th probe false positive of this pass — and the first I authored myself under a
broken tool)*. ⚠️ **This page's contrast is therefore OUTSTANDING and must be measured in a follow-up** —
including the new `☆` empty-star colour (`#B9A99A`), which was chosen but **not verified against 1.4.11**.

---

## §O-27 — Owner **Messages** (`/owner/messages`)

`App\Livewire\Owner\OwnerMessages` + `OwnerMessageActionController`. Prototype
`docs/design/raw/Owner Messages.html`. The owner ↔ operator relay.

### 🔴 THE FIX — a hardcoded "1" stood in for an unread count

The conversation row rendered `@if ($row['unread'])<span class="vy-msg-badge">1</span>@endif`. But
`$row['unread']` is a **boolean** (`isUnread($thread)`) — so **a thread holding three unread messages printed
"1"**. Worse, the Unread *tab* badge beside it **is** a real count ("Unread 2"), so two visually identical
badges meant different things: one a count, one a boolean dressed as a count.

Fixed to an **unread dot plus `.vy-sr-only` "Unread"** — no digit the data cannot support. The tab badge kept
its real number and gained screen-reader context ("2 unread"). *A real per-thread unread count is logged
below; the page no longer invents one.*

### 🔧 MISSING BACKEND (logged, not built)

- **DO27-1 — no per-thread unread COUNT.** `isUnread()` returns a boolean from the owner's read position;
  there is no "3 unread in this thread" figure. *Verdict: **REAL GAP** (small) — until it exists the row
  correctly shows a dot, not a number.*

### ⚠️ Verified, not changed

- **#28 — OWNER↔OPERATOR ONLY, AND IT IS STRUCTURAL.** `MessageThread` carries `owner_id` + `BelongsToOwner`;
  `OwnerScope` applies `whereIn(owner_id, ownerIds)`, and **a NULL `owner_id` can never satisfy a `whereIn`**
  — so every operator↔guest thread is excluded by the scope itself, not by a predicate someone could forget.
  The demo has **3 guest-side threads (`owner_id NULL`) and 2 owner-relay threads**; the page renders
  **exactly the 2**, both with "Demo Staff". DOM sweep: **0 emails, 0 phone numbers, no door code, and the
  word "guest" appears nowhere**. **Mutation-proven**: relaxing the scope to `orWhereNull(owner_id)` turns
  *"never shows an owner a guest conversation"* red.
- **SENDING OBSERVED READ-ONLY — nothing written.** Baseline before probing: `message_threads = 5`,
  `messages = 9`, max timestamps `2026-08-09`. **Identical after.** No message was sent, no reaction, star or
  pin fired. Every write is a POST to a real owner-scoped route (`reply` / `react` / `star` / `pin`), each
  resolving `{thread}` via `findOrFail` under `OwnerScope`.
- **§N.4k Enter is SAFE, and deliberately so.** The composer is a single-line input inside the reply form, so
  Enter submits — the intended chat convention. The risk case is the *other* field: the form also holds a file
  input, and Enter there would submit too. It cannot send an empty message: `body` is `required` client-side
  **and** the controller returns *"Type a message or attach a file"* when body and files are both empty.
- **Unread is not colour-only** — it is carried by a dot **and** bold weight **and** now spoken text (the O6
  lesson). The All/Unread filter rides GET (`?filter=unread`, #20).
- **Keyboard: 0 unnamed, 0 `display:none` controls.** The send button has `aria-label="Send"`; the composer
  has `aria-label="Message"`.
- **No shared-partial leak** — the owner view has its own `.vy-msg-*` markup; it does not render through the
  operator inbox's components, so it cannot inherit a guest thread from a shared row.
- ⚠️ **`toContain` sweep — clean negative** (twenty-sixth gate).

### 🎨 Contrast — 15 → 8

⚠️ **The conversation rail is translucent over the page's peach gradient**, so its body text lands on a
saturated ground (≈230,147,106) rather than on a card:
- the timestamp **2.64**, the All/Unread tabs **2.85**, the conversation count **3.37** — first moved to
  `--vy-on-grad`, which was **still not enough** (4.06–4.42), then to **`--vy-ink`**. *This is the O14/O17
  case: on a strong tint no grey reaches AA; hierarchy comes back through size and weight.*
- the two thread-header role lines → `--vy-muted`; the search icon (**2.06**, 1.4.11) → `--vy-on-grad`;
  the "Starred messages" icon (**2.74**, 1.4.11) → `--vy-amber-text`.
- **Survivors (8), all the white-on-accent family, none added:** the message bubbles **3.97** (⚠️ the owner's
  own message text — the largest body of text this family affects anywhere so far), thread avatars 3.61/3.88,
  the tab badge 3.76, the shell avatar 3.83 and brand mark 3.88, and ⚠️ **`.vy-nav-item is-active` at 3.79 —
  the SHARED owner shell nav, deliberately not retuned from a page gate** (it would change every owner page).

### 📌 O26 (Reviews) CONTRAST — the measurement I could not take last gate, now taken

The screenshot tool recovered after a browser restart, so I went back and measured Reviews:
**30 AA failures**, and the shape confirms DO10-4 exactly — **roughly 17 of them are the ★ spans that were
NOT migrated to `<x-star-rating>`** (gold `#E8A63A` at **1.68–2.09**, the pale empty glyph at **1.44–1.61**).
The hero row I did migrate is exempt: its glyphs are `aria-hidden` behind an accessible name, so they are
decorative. The remainder are the familiar family — review avatars (2.46 on amber, 3.61 on green, 3.88 on
accent), the "All villas" filter 3.61, and a tail of marginal greys/accents at 4.12–4.47.
⚠️ Two readings (a badge at 1.03, an avatar at 1.12) sample across an element boundary and are **probe
artifacts, not findings**. **O26's contrast fix is now a scoped, unstarted item** — migrating this page's two
remaining star spans to the component and retoning that tail. It was NOT folded into this gate.

---

## §O-28 — Owner **Help** (`/owner/help`)

`App\Livewire\Owner\OwnerHelp` + `OwnerHelpAskController` → `AskVyrobelService`.
Prototype `docs/design/raw/Owner Help.html`. A claims surface.

### ⚠️ PREMISE CORRECTION — the AI assistant is BUILT, not deferred

The brief carried "live LLM is DEFERRED §H". **That is not true of this surface.** *Ask Vyrobel* shipped in
**Wave D** as a real, grounded assistant: `AskVyrobelService` retrieves over the Help KB first and **escalates
deterministically with NO model call when retrieval is empty** (so out-of-scope and account-specific questions
can never reach the model), applies a per-owner daily token cap, and trips a circuit breaker to the same
grounded fallback on provider failure. It posts to a real controller route (`/owner/help/ask`, PRG). Locally
`LLM_FAKE` defaults to **true** and no key is set, so the demo answers from the grounded fallback — and
`AskVyrobelTest` proves the fake client is forced outside production *even with a real key*.

⚠️ **The page's own docblock listed "the AI Q&A" under REFUSED (#39)** — stale since Wave D, and the **third**
occurrence of a docblock denying a feature that exists (O22, O24). Corrected.

### 🔴 THE FIX — the words came out, the signal stayed

A previous pass (wireframe-parity WAVE 2) removed the line **"All systems operational"**, correctly, because
nothing observes system health — and `OwnerHelpTest` still forbids that string. **The visual half survived**: a
green presence dot (`--vy-green`, on a green-tinted card) sat under a heading reading **"System status"**,
beside a sentence that is not a status at all — *"Your operator is your first line of support"*.

**A green dot under "System status" asserts "operational right now" as loudly as the words did**, with nothing
behind it — the GATE-24 / Owner-12 hardcoded-status trap, and the very board this file's docblock records as
REFUSED. It was also **colour-only** (no text, no `aria`). Removed the dot; the heading now says **"How
support works"**, which is what the line actually is. Guarded and mutation-proven.

### 🔧 MISSING BACKEND (logged, not built)

- **DO28-1 — there is no presence or SLA signal, and the page correctly claims neither.** No "desk online", no
  "replies in under an hour" — the prototype's two SLA claims are simply absent. ⚠️ *Whether to promise an SLA
  at all is the founder's call; the honest position today is to make no timing claim.* *Verdict: **RATIFIED
  REFUSAL** — do not "restore" the prototype's promise.*
- **DO28-2 — no support-ticket queue.** Messaging is the tracked channel; the page says so ("no separate
  ticket queue"). *Verdict: **RATIFIED REFUSAL**.*

### ⚠️ Verified, not changed

- ⚠️ **"replies in" was a FALSE ALARM I raised and cleared.** My needle matched *"your team replies **in your
  inbox**, with full history"* — a **location**, not a time SLA. Read the context before calling a claim.
  *(The O25 needle lesson, this time in the reading direction.)*
- **Contact paths are honest** — there are **no `mailto:` or `tel:` links at all**. The card says the team's
  direct phone and email are *"on your welcome pack and your latest statement"*: it points at where the real
  contact lives instead of inventing one. An existing guard forbids the wireframe's invented number.
- **The assistant is observed READ-ONLY** — nothing was submitted; no cost-ledger row exists to write to
  locally. Its disclosure ("verify important things, message your team for anything account-specific") and the
  escalate relay are present on every path by construction.
- **§N.4k** — the ask form has a **single** field, `required`, `aria-label`led, with a named "Ask" submit, so
  Enter submits the one thing it can and cannot submit empty.
- **Keyboard: 0 unnamed, 0 `display:none` controls, 0 genuinely unreachable** (the 4 flagged are the shell's
  Alpine roots).
- ⚠️ **`toContain` sweep — clean negative** (twenty-seventh gate).

### 🎨 Contrast — 7 → 5 (3 real + 2 artifacts)

- ⚠️ **The AI disclosure line measured 4.48** — the sentence telling the owner to verify what the assistant
  says was **the least legible text on the page**. → `--vy-ink`. *(Third time the honesty text is the worst
  reading: O22's "not tax advice", O25's "EXAMPLE rates", now this.)*
- "Open messages →" 4.21, raw accent-as-text → `--vy-accent-text`.
- **Survivors: 3 white-on-accent** (the "Ask" button 3.79, the "Open messages" button 3.85, the shell brand
  mark 3.88) — the known family, **none added**.
- ⚠️ **2 reported failures are PROBE ARTIFACTS, not findings**: the notification badge (1.04) and shell avatar
  (1.12) sample across an element boundary — their background range spans `(194,65,12)..(254,250,249)`, i.e.
  the sample straddles the edge. The same two measure ~3.83/3.88 cleanly on other pages. Same for the 1.29
  icon reading.

---

## §O-29 — Owner **Onboarding / Welcome** (`/owner/welcome`)

`App\Livewire\Owner\OwnerWelcome`. Prototype `docs/design/raw/Owner Onboarding.html`. Wave B · GATE 6.

### ⚠️ PREMISE CORRECTION — this is NOT a wizard, and there is nothing to submit

The brief treated this as a multi-step MUTATION flow with per-step writes and an Enter-safety risk. **It is a
73-line read-only hand-off page.** There is **no `<form>` in `main` at all** (only the shell's notification and
logout forms) and **no input, textarea or select** — every "next step" is an `<a href>` out to the page that
actually owns the write: My Units, Add Unit, **Payout Method**, Notification Preferences, Dashboard.

Consequences, stated precisely rather than assumed:
- **§N.4k is MOOT** — Enter cannot submit anything, early or otherwise, because nothing on the page submits.
  The Owner-9 Add-Unit trap **does not carry here**; it was checked, not inherited.
- **"Observe READ-ONLY" was structural** — there is no step to complete. Confirmed anyway: `owners` and
  `payout_methods` counts and `max(updated_at)` are unchanged (`2026-08-09 07:17:58`) after the whole pass.

### 🔴 THE FIX — a status flag gated on the wrong fact told owners to redo work they had done

`hasPayout` read `$units->isNotEmpty() && PayoutMethod::query()->exists()`. The unit conjunct has nothing to do
with whether a payout account exists, so **an owner who had already saved a payout method but had no units yet
fell into the empty branch** — which does not merely show a wrong badge, it prints an **instruction**:

> *"So we can send your earnings, add a bank account on the secure Payout Method page."*

Telling someone to do a thing they have already done. Fixed to `PayoutMethod::query()->exists()` — the model
carries `BelongsToOwner`, so `exists()` can only ever see this owner's own. Guarded and mutation-proven
(restoring the conjunct turns the new test red).

⚠️ *This is the GATE-24 / Owner-12 fabricated-status family in its **understating** direction — and the more
damaging one, because a false "not done" issues a false instruction, where a false "done" merely misleads.*

### 🔧 MISSING BACKEND (logged, not built)

- **DO29-1 — IBAN capture, bank verification and micro-deposits are absent here BY DESIGN.** The page hands
  off to the certified Payout Method page, which owns IBAN collection; verification itself remains deferred
  (§H, and Owner-16 established there is no verification column). *Verdict: **RATIFIED REFUSAL** — the thin
  flow is correct; do not restore the prototype's bank step.*

### ⚠️ Verified, not changed

- **No fabricated progress.** There is no progress bar, no "N of 4 complete", no green tick on an undone step
  — the four numbered cards each state a real fact. The only completion claim, *"A payout account is on file —
  you're all set to receive money"*, is conditional on the real owner-scoped `PayoutMethod::exists()`.
- **State is not colour-only** — the payout card's chip changes colour *and* its heading changes wording
  ("Your payout account" vs "**Add** your payout account"), so the state survives greyscale.
- **The real 18% renders** (`OwnerCommissionRate`), never the prototype's 15%; an owner with no agreement gets
  the honest "set by your agreement" line instead of a number.
- **No IBAN anywhere** — 0 raw IBAN-shaped strings, and the words "IBAN", "micro-deposit" and "verified" do
  not appear. An existing guard already forbids all three.
- **Keyboard: 0 unnamed, 0 unreachable, 0 `display:none` controls.** The docblock is accurate — no O22/O24
  staleness on this file.
- ⚠️ **`toContain` sweep — clean negative** (twenty-eighth gate).

### 🎨 Contrast — 12 → 4

⚠️ **The first measurement was UNSOUND and discarded.** It reported 22 failures including text scored against
its own colour (`bg` equal to the text's rgb) — the screenshot had been taken before the hide-text style
painted. The probe now **asserts every candidate computes to a transparent fill before capturing**, and also
freezes the entry animations, since these cards fade in on a delay. *(The O26 rule applied to my own run:
discard the numbers, fix the probe.)*

From the sound baseline of 12:
- the step-number chips (**2.62 / 3.15 / 3.50**) and the accent links (**2.79 / 3.46**) sit on cards that
  composite over the hero peach (≈238,177,145). `--vy-accent-text` reaches only **~3.3** there, and
  `--vy-accent-tint-text` only **3.74**, so both went to **`--vy-ink`** — the links keeping their affordance
  through an **underline + arrow** rather than colour. *(O14/O17 again.)*
- the body sub-lines (**4.05 / 4.31 / 4.43**) → `--vy-ink`, applied **per element**: `.vy-line-sub` is defined
  in the shared owner shell and must not be retuned for one page.
- **Remaining 4, none added and none new:** the shell brand mark **3.88** (the known white-on-accent family);
  the "Go to your dashboard" CTA, which carries that same standard accent gradient and measures ~3.9 on other
  pages — its 1.00 here is a **mis-sample** (the reported background was pure white); and the shell badge/avatar
  **boundary artifacts** seen on the last three gates.

---

## §O-30 — Owner **Expenses & maintenance** (`/owner/expenses`)

`App\Livewire\Owner\OwnerExpenses` + `OwnerExpenseApprovalController` → `OwnerExpenseService`.
Prototype `docs/design/raw/Owner Expenses.html`. Money · list · mutation (#68).

### ⚠️ PREMISE CORRECTION — the third state is **queried**, not "rejected"

The machine is **pending → approved | queried**, not pending/approved/rejected. A *queried* expense is not
refused; it is **held out of the net while a conversation opens** in the owner↔operator relay. Approve posts
the debit (`OwnerExpenseService::approve()` → `postExpense()`); query posts nothing. `isPendingApproval()`
guards a second decision, so neither transition can fire twice. Both routes are `$capActions`-gated —
co-owner only (#30).

### ⚠️ #17 RECONCILES IN BOTH STATES — and the page is stricter than "approved"

The three KPIs answer three different questions, and the live figures tie out exactly against the database:

| KPI | live | derivation | check |
|---|---|---|---|
| Spent this year | **€709.00** · "5 expenses in 2026" | all 2026 expenses, any state | Σ 2026 rows = **€709.00**, count **5** ✓ |
| Awaiting your approval | **€84.00** · "1 to approve · **not charged until you do**" | pending only | the single pending row = **€84.00** ✓ |
| Charged to payouts | **€250.00** · "Approved & deducted" | the **owner_ledger** | Σ `entry_type='expense'` = **€250.00** ✓ |

⚠️ **The interesting part: there are FIVE approved expenses (€745.00) but only TWO ledger rows (€250.00).**
Three approved expenses carry no `attributed_ledger_id` — the migration back-filled historic rows as
`approved` by default, and a non-deductible/unattributable expense never posts a line. **"Charged to payouts"
counts the LEDGER, not the approval state**, so it reports €250.00 rather than €745.00. That is the strict and
correct reading: it claims only what actually hit the owner's money. A pending expense is excluded, and so is
an approved-but-unposted one.

**Mutation-proven in both directions**: removing `postExpense()` from `approve()` turns *"charges the expense
to the ledger and drops net when the owner approves"* red; forcing every expense to be created `approved`
turns *"creates a deductible expense pending and does NOT charge it until approved"* red.

### 🔴 A STALE TEST **NAME** — the fourth stale-claim, one layer out

`OwnerExpensesViewTest` had a test titled *"…and **invents no approval workflow**"*. Its **body** was updated
when Wave C GATE 2 shipped the real machine (a comment in it even says so), but the **name** was not — and the
name is what appears in CI output and in a grep, where it reads as an assertion that no approval workflow
exists. Renamed to what it actually proves. *(O22/O24/O28 were docblocks denying a shipped feature; this is
the same shape in a test name.)*

### ⚠️ Verified, not changed

- **Vendor is REAL** — a real `owner_expenses.vendor` column with real values (*Lisbon Supplies Ltd*,
  *GreenLeaf Grounds*, *SparkleCo Cleaning*, *FixIt Appliances*) and an honest **"—"** where none was
  captured. The Wave-A refused-then-added history is confirmed, and an existing guard covers both branches.
- **READ-ONLY — nothing decided.** Baseline and after are identical: `approved 5 / €745.00`, `pending 1 /
  €84.00`, `max(updated_at) = 2026-08-09 07:17:59`, **2** ledger expense rows. No approve or query was fired.
- **Approval state is a WORD** ("Pending" / "Approved"), not colour alone, and no tick or badge asserts a
  state the data does not hold (the O28 check) — the pending card says outright *"not charged until you do"*.
- **The charged flag is gated on the right fact (the O29 check)** — it reads the ledger, not the count of
  approved rows, and not some unrelated conjunct.
- **#42** — an existing guard covers owner scoping, cross-owner hiding and fail-closed.
- **Keyboard: 0 unreachable, 0 `display:none`.** The Approve/Query submits are named. ⚠️ Two "unnamed"
  controls are the **query-note textarea and a filter input inside the relay form** — both sit within labelled
  sections; noted, not fixed, as they are the same shape as the shell FPs.
- ⚠️ **`toContain` sweep — clean negative** (twenty-ninth gate).

### 🎨 Contrast — 18 → 8 (2 real fixed, 6 artifacts/known)

⚠️ **The KPI band and rows are translucent over the hero gradient** — the fourth consecutive owner page with
this shape. The shell greys composited onto ≈(236,153,110) and measured **3.05–4.35**:
`.vy-kpi-label` · `.vy-kpi-sub` · `.vy-line-sub` · `.vy-row-sub` → **`--vy-ink`**, applied in a **page-scoped
`<style>` block** — ⚠️ **the shell's own definitions are untouched**, since retuning them would change every
owner page. The two accent money figures went to `--vy-accent-text`, and the pending **€84.00** — the amount
the owner is being asked to approve — needed **`--vy-ink`** (accent-text reached only 3.13 there).

**Remaining 8, none added:** the brand mark 3.88 (known white-on-accent family) + **7 mis-samples**: the two
`is-active` pills, "Request a repair", "Approve" and the shell badge/avatar all report 1.00–1.52 with a
background that is pure white or an off-element peach. Each carries the **standard accent gradient**
(`linear-gradient(140deg,#E8703A,#C2410C)`), verified directly, whose true value is the family's ~3.8–3.9.
⚠️ **Small elements are where this probe mis-samples** — a 29px-tall button is easy to miss by a few pixels.

---

## §O-31 — Owner **Tax Center** (`/owner/tax`)

`App\Livewire\Owner\OwnerTaxCenter` + `OwnerTaxController` → `OwnerTaxService`.
Prototype `docs/design/raw/Owner Tax Center.html`. Money · mutation · document.

### 🔴 A FOURTH DOCBLOCK DENYING A SHIPPED FEATURE

The view's header block stated: **"WITHHOLDING is NOT modelled → an HONEST 'no withholding' card"**. That went
stale when the **#73 tax engine** shipped. The component's own docblock is accurate and the code reads the
**real** figure — `withholdingSummary()` sums `withholding_minor` over the owner's committed statements, which
`WithholdingService` posts at finalize from a founder-configured rate.

Corrected. ⚠️ **This is the fourth stale claim of exactly this shape** — O22 (the tax PDF denied withholding),
O24 (the archive denied the annual PDF), O28 (Help denied Ask Vyrobel), and now O31. **Three of the four are
about the tax engine specifically**, which suggests Wave D shipped without a sweep of the surfaces that had
previously refused it.

### ⚠️ Verified, not changed — #73 holds exactly as at O22/O25

- **Withholding is REAL and the page prints an AMOUNT, never a rate.** Live it reads **"None · withheld from
  payouts"** with the honest explanation *"No withholding is configured for your residency — you receive your
  full net earnings and declare your rental income in your own tax return."* That matches the database
  exactly: **Σ `withholding_minor` = 0**. It is a genuine zero, not a refusal.
- **ZERO percentage signs appear anywhere on the page.** The wireframe's 15%/20% is not rendered in any state,
  so no rate is ever presented as a determination. **Mutation-proven**: making the card state *"Withholding is
  applied at 15% of your gross earnings"* turns *"renders an honest withholding state and never a fabricated
  rate"* red. The opposite direction is covered too — `OwnerTaxSurfaceTest` proves the real withheld figure
  appears once a rate **is** configured.
- **No fabricated validity.** There is no "valid ✓" or "verified" anywhere; the tax id is simply present or
  absent, and the real tax-hold is what changes when it is saved.
- **#60 — only the real year.** The year-end list offers **2026** alone and links to
  `/owner/tax/2026/summary.pdf`, the real Annual Tax Summary PDF gated at O22 (whose own #60/#73 rules apply).
- **READ-ONLY — nothing saved.** `tax_id` is still `NULL`, `vat_registered` still `true`, and the owner's
  `updated_at` is unchanged at `2026-08-09 07:17:58`. The settings form POSTs the real `owner.tax.save` path
  ($capActions, encrypted, audited, self-only).
- **#42/#28** — self-resolved from the authenticated user (no owner id in the request); **no raw IBAN**, no
  guest data.
- **Keyboard** — the segmented "Registered as" control is a real `role="group"` of buttons with an
  `aria-label`; every field is labelled.
- ⚠️ **`toContain` sweep — clean negative** (thirtieth gate).

### 🔧 MISSING BACKEND (logged, not built)

- **DO31-1 — the page carries no explicit "not tax advice" line**, unlike the annual PDF (O22) and Reports
  (O25). It states *"ready for your accountant"*. ⚠️ **Structurally it does not need one in the same way** —
  it presents **no rate and no computed determination**, only a real withheld amount and links to the PDF that
  does carry the disclaimer. Whether the surface should nonetheless carry formal wording is **DO22-1, the
  founder's call**. *Verdict: **surfaced, not added** — legal copy is not a design gate's to write.*

### 🎨 Contrast — 16 → 4 (all four artifacts/known)

⚠️ **The honesty text was checked FIRST, per the named O22/O25/O28 pattern** — and this time it was **not** the
worst reading: the withholding explanation sits in the body copy that measured 3.14 and is now ink. *The
pattern held as a priority, not as a prediction.*

- The form labels `.vy-tax-lbl` (`#6E5C50`) measured **2.89–4.03** on the page's peach → `--vy-ink`; the
  helper lines, the required `*` marker, the "Company/Individual" segment (4.23) and the archive sub-line
  followed. **Fourth consecutive page** where the shell greys can't reach AA over the hero gradient.
- ⚠️ **Two readings were the O11 DARK-PLATE FALSE POSITIVE, caught before touching anything**: "About your
  tax" and "Vyrobel keeps records…" reported **1.00** and **1.41**, but both sit inside `.vy-card--dark`
  (`linear-gradient(160deg,#3A2E27,#251C…)`), where white-at-60% actually measures **5.78**. The probe now
  **excludes `.vy-card--dark` subtrees** and scores them separately. *(12th probe FP.)*
- **Remaining 4, none added:** the brand mark 3.88 (known family) + three **mis-samples** (the "Save tax
  details" button and the shell badge/avatar), each verified to carry the standard accent gradient.

---

## §O-32 — Owner **Agreement** (`/owner/agreement` + `/owner/agreement/pdf`) — 🏁 THE LAST OWNER PAGE

`App\Livewire\Owner\OwnerAgreement` + `OwnerAgreementController` (acknowledge) + `OwnerAgreementPdfController`
→ `OwnerAgreementDocument`. Screen + document. The authoritative source of the realm's 18%.

### ⚠️ THE RATE — authoritative, and it FOLLOWS the engine

Live the page shows **18%** and **no 15% anywhere**, matching `management_agreements.commission_pct = 18.00`.
It is rendered through the shared `OwnerCommissionRate::display()`, never a Blade literal — and the existing
guard proves it *follows the data* rather than matching a constant: owner A with `15.00` renders "15%" while
owner B with **22.75** renders "22.75%", a value no hardcoded literal could produce. **Mutation-proven**:
making `display()` return a fixed `'18%'` turns the cross-owner test red. The PDF shows the same **18%**
(and per-villa 18% / 18%) — **SCREEN == PDF**.

### 🔴 A SIXTH STALE CLAIM — a duplicate docblock quoting the MOCK rate

`commissionDisplay()` carried **two stacked docblocks**. Only the second binds to the method; the first was
orphaned text whose worked example read **"15.00%"** — the wireframe's mock rate — sitting on the one page
that *defines* the real one. Merged into a single accurate block with no example rate quoted at all, since
the number belongs to the agreement. *(O22/O24/O28/O30/O31 were denials of shipped features; this is the
same family — a stale claim about the rate, on its source page.)*

### ⚠️ Verified, not changed

- **Acknowledge is observed READ-ONLY and is append-only (#7).** `agreement_amendment_acknowledgments` is
  still **0**, `management_agreements` and `agreement_amendments` both **1** with `max(updated_at)` unchanged
  at `2026-08-09 07:17:58`. Nothing was acknowledged. The control POSTs the real `$capActions` route, and an
  existing guard proves a second acknowledge records no second consent (idempotent — the append-only shape).
- **#42 — MUTATION-PROVEN, and my first attempt proved nothing.** Swapping `AgreementAmendment::query()` for
  `withoutGlobalScopes()` left the test **green**, because the real guard is an **explicit
  `whereIn('owner_id', $ownerIds)`** the swap did not touch. Removing that line turns *"refuses to
  acknowledge another owner's amendment (owner-scoped 404)"* red. *(The O12 lesson: a mutation that misses the
  guard proves nothing — check what you actually removed.)*
- **No fabricated terms.** Five fields render an honest **"Not set"/"—"** where the demo agreement is NULL,
  and an existing guard forbids rendering those as a zero figure. The amendment history is the one real
  amendment; no multi-amendment fiction (#60).
- **The PDF's honest framing is strong and worth preserving**: *"This is a summary of the commercial terms
  your operator has set, for your reference. **It is not a signed legal contract**; your binding agreement is
  held by your operator."* An existing guard forbids inventing a signing flow.
- **No raw IBAN** on either surface; self-scoped; **keyboard: 0 unnamed, 0 unreachable, 0 `display:none`**.
- ⚠️ **`toContain` sweep — clean negative** (thirty-first gate, and the last of the owner realm).

### 🎨 Contrast — screen 7 → 4, PDF 4 → 2

⚠️ **The honesty text was the worst reading on BOTH surfaces** — the fifth and sixth occurrences of the
pattern, and the sharpest yet, because here it is the text that *disclaims the document's authority*:
- **PDF:** `#A69A92` at **2.74** carried *"…not a signed legal contract…"* → `#6C645F` (5.79); `#8A8079`
  across **19 text runs** at 3.86 → `#5F5853` (6.98). Guarded on the **rendered document** and
  mutation-proven. **PdfService untouched.**
- **Screen:** the same `#8A8079` (×5 declarations) at **2.27 / 2.31**, and `.vy-fx-note` at **4.25** — the
  line explaining *what acknowledging legally does* — both → `--vy-ink`; the amendment eyebrow needed ink too
  (accent-text stopped at 3.68).

**Screen survivors (4), none added:** the brand mark 3.88 + 3 shell boundary artifacts. **PDF survivors (2):**
the accent brand plate (probe FP — white on `#C2410C` is 5.18) and `PdfService.php:54`'s watermark at **2.70**.

### 📌 The realm-wide PDF sweep — updated count

Four owner documents are now clear (statement · booking summary · remittance · annual tax · **agreement** —
five). ⚠️ **11 templates plus `PdfService.php:54`'s watermark remain**, and the recommendation stands
unchanged from O22: **one dedicated gate**, because the watermark is untouchable from a page gate and each
template needs its own generated-file measurement.

---

## §O-Invite — Owner Invite Email (`OwnerInviteMail`) 🏁 CLOSES THE OWNER REALM

**Not a page.** A Laravel Mailable rendering into Gmail/Outlook/Apple Mail, so the realm's disciplines change
shape: no `--vy-*` (clients strip custom properties), no Alpine/JS, no external CSS, no flex/grid, no
backdrop-filter — nested `<table>` + inline styles only. **Render path:** `OwnerInviteMail->render()`, which
is also what the real, non-sending operator preview route uses
(`InviteOwnerPreviewController`, `can:users.manage`). **Nothing was sent** — a send mints a redeemable token
and dispatches real mail; every measurement here came from `->render()` on a TRANSIENT unsaved invite.
Verified after: `owner_invites` 0, `jobs` 0, `failed_jobs` 0.

### ⚠️ Premise corrections

1. **A text/plain part does not exist.** `content()` returns `new Content(view: 'emails.owner-invite', …)`
   with no `text:` key, and `$textView` is null on the built Mailable. This is a **REAL GAP**, logged below —
   not fabricated here, because an invented text part immediately drifts from the HTML.
2. **The prototype's two roles are three in the product.** The design covers `owner` + `accountant`; the
   shipped Mailable also handles `manager` with its own honest copy. **EXTRA WORK — kept.**
3. **The email never extended the shared mail layout**, and `emails/layout.blade.php`'s docblock claimed it
   carried *"the SAME brand as the shipped owner-invite email"*. True before this gate, false after it — the
   invite is now a 600px card with a gradient brand band while the layout stays a 480px plain lockup. The
   docblock was corrected in place. *(§N.8b — a comment asserting a relationship that the code stopped
   holding; the seventh instance, and the first this pass created rather than found.)*

### 🎨 Contrast — measured on the RENDERED email; 0 failures across 27 text runs

⚠️ **The computed probe produced a false PASS on the one element that mattered.** `getComputedStyle` scores
text against `background-color`, so the wordmark on the gradient band read **5.18** — that is the
`bgcolor="#C2410C"` *fallback*, which no gradient-capable client ever shows. Sampling the **rendered pixels**
of glyph-free strips of the band gives the true worst point: **`#E66D38` → white = 3.18**. (Two earlier
sampling runs were themselves vacuous — the first read the white glyphs as "background", the second their
antialias haloes, and a third caught the page wash outside the card's rounded corners. §N.8e: when the probe
breaks, publish nothing.)

Consequences, both design divergences and both deliberate:

- **The brand lockup is set at weight 700, not the prototype's 600.** At 3.18 the run clears WCAG *large*
  text (3.0) but not normal text (4.5), and "≥18.66px **bold**" is only unambiguous at 700. This buys the
  classification rather than leaving it arguable.
- **The CTA stays SOLID `#C2410C` (5.18), not the prototype's gradient.** White 15px normal text needs 4.5;
  the band's light end gives 3.18 and **no warm orange lighter than `#C2410C` can reach 4.5** — `--vy-accent-2`
  `#D9662E` measures 3.57, `--vy-accent-3` `#E8703A` 3.09. The gradient CTA is *unreachable* for AA, not
  merely unimplemented.
- **The eyebrow sits on a solid `#B03808` plate (6.15).** It is normal-size text and the gradient cannot carry
  it anywhere; the prototype's `rgba(255,255,255,.82)` on the band measures **2.55**.
- **The prototype's greys are the pre-remediation palette** and all fail here: `#8A8079` (3.86), `#9C9089`,
  `#A69A92` (2.74), `#B7ADA4`. Rendered at `--vy-muted` `#5F5853` and `--vy-faint` `#6C645F` instead. ⚠️ The
  **footer/legal line was checked first** (§N.8d): *"You're receiving this because…"* now measures **4.95**
  on the `#F4ECE4` footer band.

### ⚠️ Literal-vs-token drift — checked, all nine match

Email cannot reference a token, so every literal is a standing drift risk. Verified against the live
declarations in `components/layouts/owner.blade.php`: `#2B2320`=`--vy-ink` · `#5F5853`=`--vy-muted` ·
`#6C645F`=`--vy-faint` · `#B03808`=`--vy-accent-text` · `#C2410C`=`--vy-accent` · `#D9662E`=`--vy-accent-2` ·
`#E8703A`=`--vy-accent-3` · `#F6EBE2`=`--vy-wash` · `linear-gradient(140deg, #E8703A, #C2410C)`=`--vy-grad`.
**No drift.** The remaining literals (`#FFFDFB`, `#F8F1EA`, `#F8F4EF`, `#F4ECE4` and four hairlines) are
opaque stand-ins for washes the app renders as translucent glass, which email cannot do.

⚠️ The docblock names the four *failing* prototype greys and the measured `#E66D38` — **verified stripped from
the rendered output** (Blade comments do not render; CSS comments would have, and shipping a forbidden literal
through a comment is a known trap in this repo).

### 🚩 REAL GAPS — logged, not built

- **`DO-INV-1` · No `text/plain` alternative.** HTML-only is a deliverability and accessibility gap (spam
  scoring, plain-text readers, some screen-reader pipelines). Needs `Content(text: 'emails.owner-invite-text')`
  sharing ONE source for the link / org / inviter / expiry so the two parts cannot drift.
- **`DO-INV-2` · No dark-mode handling.** The template declares `color-scheme: light` / `supported-color-schemes:
  light`, which is honest but is a *request*: Gmail and Outlook.com force-invert regardless, and the warm
  plates (`#F8F4EF`, `#F4ECE4`) will invert to muddy darks with text contrast unmeasured. A real fix needs
  `@media (prefers-color-scheme: dark)` overrides plus per-client testing that this repo has no harness for.
- **`DO-INV-3` · No public help destination.** The prototype's footer offers **"Get help"**. `/owner/help` is
  authed and the invitee has no account yet, and no public support URL or support address is configured
  (`MAIL_FROM_ADDRESS` is a from-address, still the `hello@example.com` placeholder). **Omitted rather than
  pointed at a link that bounces to login.** "Sign in" is kept — `/login` is a real public route.
- **`DO-INV-4` · The shared mail layout's footer grey — a scoped EMAIL-CONTRAST SWEEP.**
  `emails/layout.blade.php` ships `#A99E95`, which measures **2.24** on the `#F6EBE2` wash and carries the
  *"You're receiving this because…"* line on **all 15 emails that extend it** (plus `winback` and
  `operator-guest-message` directly). Same shape as the PDF sweep, and the same recommendation: **one
  dedicated gate**, because each template needs its own rendered measurement. Deliberately not fixed here.

### ✅ REFUSALS TO PRESERVE

- **The prototype's inbox chrome is not the email.** The meta bar (`Vyrobel <hello@vyrobel.com>` … `now`) and
  the floating **Download PDF** button are the mock's simulated mail client. Not rendered.
- **"Edit in Email templates"** (the prototype's footnote link) is operator-app chrome. It must never appear
  in a recipient's inbox — the invitee is not an operator and has no account.
- **Per-feature line icons stay a single check glyph.** The prototype's icons are inline SVG, which Outlook
  desktop and parts of Gmail do not render. The prototype's tile geometry and colour are matched; the glyph
  is the one that renders everywhere.
- **No gradient CTA / no 82%-white eyebrow** — see contrast above. Parity must not buy a look with an AA
  regression.
- **`#28`** — the email carries the org name, the recipient's own name, the role copy and the link. Nothing
  else. Guarded from the outside: the org is seeded with another owner (name + billing company) and a guest
  (name + encrypted email + phone), and the render is asserted free of all of them.
- **`#42`/`#38`** — exactly two destinations in the rendered HTML: the single-use tokened `/invite/{token}`
  and the public `/login`. Asserted as an exhaustive set, so a new link cannot appear unnoticed.

### 🔒 Guards added — all three MUTATION-PROVEN

`tests/Feature/OwnerInviteEmailTest.php` 2 → 5 tests.

| guard | mutation applied | result |
|---|---|---|
| no `--vy-*`, none of the four failing greys, brand literals present | swapped the footer colour for `var(--vy-faint, #A69A92)` | ❌ red |
| `#28` — no other owner, no guest, exactly two hrefs | rendered a sibling owner's `legal_name` into the chip | ❌ red |
| `#39`/`#42` — org name and token BOUND per invite | hardcoded `$orgName = 'Seaside Group'` in the Mailable | ❌ red |

*(The Mailable itself was restored after the third mutation — `app/Mail/OwnerInviteMail.php` is unchanged by
this gate. Send logic, token minting and the copy matrix were not touched.)*

---

## §G-1 — Guest Trips (`/guest/trips`) · GUEST 1/32 · gate 1 of 12 · ⚙️ THE FOUNDATION GATE

`GuestTripsController@index` → `guest/trips.blade.php` inside `<x-guest.shell>`. Auth `auth:guest` +
`SetTenantForGuestRequest`. Walked as the demo guest.

### ⚙️ THE REALM'S CONTRAST METHOD — established here, reused by G2–G12

⚠️ **`getComputedStyle` cannot measure this realm at all.** Every guest view sits on the shell's page
gradient, so walking ancestors for an opaque `background-color` finds none and falls back to white. On this
page the naive probe reported `rgb(255,255,255)` as the background for **all 36 gradient-backed runs** —
scoring white-on-orange as **1.00** (false FAIL) and everything else against a plate it never checked. This
is the same root cause as the invite email's bgcolor-under-gradient false-PASS, seen from the other side:
there a fallback made a 3.18 read as 5.18; here the absence of one makes a 5.18 read as 1.00.

**THE METHOD (`plate-probe.php`):**
1. Stash each run's real `color` on the element **first**.
2. Inject `*{color:transparent}` + `svg{visibility:hidden}` + `animation:none`, force a reflow, then read
   boxes **from that same render**, and screenshot → the true **background plate**.
3. Per run, sample the **central region** only (inset 18% x / 28% y), **excluding element-child boxes**.
4. Score the real foreground against the **worst pixel** found there — a gradient must clear AA at its worst
   point, not its average.

⚠️ **THE PROBE WAS WRONG THREE TIMES BEFORE IT WAS RIGHT.** Publish nothing until the anchors hold.
- **(a)** whole-box extremes → a pill's **rounded-corner pixels belong to the parent**, so 11 runs
  false-FAILED (a CTA read as card-white).
- **(b)** boxes captured before the plate → **removing/adding the style reflowed the page by 20px**, so every
  box pointed 20px above its element. The CTA read 1.13 against a colour it does not sit on.
- **(c)** a box containing a differently-plated **child** (an `<a>` wrapping an accent count badge) scored the
  label against the badge → one more false FAIL.

**TWO VALIDATION ANCHORS — a run of this probe is only trustworthy if both hold:**
- white on `--vy-grad` ⇒ **≈3.4** (the Owner Invite Email measured **3.18** on the same gradient; a narrower
  element samples a slightly darker slice of the 140° ramp).
- white on solid `--vy-accent` ⇒ **exactly 5.18**.

### 🎨 Contrast — 6 real failures → **0 across 44 runs**

| run | was | now | fix |
|---|---|---|---|
| `View reservation` CTA ×2 | white 12.5px on `--vy-grad` = **3.39** | **5.18** | solid `--vy-accent` |
| `V` brand mark | white 17px/700 on `--vy-grad` = **3.50** | **5.18** | solid `--vy-accent` (17px < 18.66px ⇒ NORMAL text, needs 4.5) |
| `Trips` active nav | `--vy-accent` on its 8% wash = **4.20** | **4.99** | new `--vy-accent-text` |
| `Arrival due` badge | `--vy-amber` 11px = **3.79** | **4.70** | new `--vy-amber-text` |
| `€184.50 due` | `--vy-amber` 13px = **4.08** | **5.06** | new `--vy-amber-text` |

**Two new shell tokens, both measured and neither invented:** `--vy-accent-text: rgb(176,56,8)` (the operator
realm's existing value) and `--vy-amber-text: rgb(143,100,20)` — **`#8F6414`, the prototype's OWN amber-on-light**
from its "Share how your stay went" callout. Fills, borders and rails keep `--vy-accent`/`--vy-amber`.

⚠️ **`.vy-btn` was corrected BY CONSTRUCTION, not by a render.** It is `#fff` 13.5px on `--vy-grad` — the
identical construct measured at 3.39 — but **Trips draws no `.vy-btn`**, so it was not measured here. **G2 is
the first gate that renders one and must confirm 5.18.**

### ⌨️ Keyboard — clean

18 interactive elements, **0 unnamed**, **0 unfocusable**, **0 `div@click`**, **0 `onclick`/`onchange`**,
0 horizontal overflow at 1440. §N.4k: two forms — logout (POST, 1 submit, 0 text inputs) and search
(GET, **1 text input, 0 buttons** ⇒ implicit submit is the only submit path, so Enter is unambiguous).

⚠️ **To verify at a later gate:** the year `<select>` carries an inline
`onchange="window.location.href=…"` and renders only when a guest has trips in **more than one** year. The
demo guest has one, so it did not render and was not measured. It is the realm's only JS-dependent control.

### ⚠️ THREE PREMISE CORRECTIONS — two of them correct THIS pass's own recon

1. **`partials/trip-card.blade.php` is DEAD CODE.** `GUEST-RECON.md` listed it as a shared partial to "fix
   once". Nothing includes it — `grep -rn "trip-card" resources/ app/` returns **zero** references, and
   `trips.blade.php` renders its own inline card. Its docblock claims *"shared by Trips (and reused as the
   header on the Reservation detail)"* — **denied by the code (§N.8b)**. It is also **drifted**: it shows
   "Confirmation {ref}" and "View booking →" where the live card shows a reference chip and "View
   reservation", and it has no image band, countdown or Manage link. Second dead guest view after
   `guest/home.blade.php`. **Not deleted at a design gate** — logged as `DO-G1-1`.
2. **Trips is NOT a DO10-4 site.** The recon counted `★` glyph occurrences. All of them here are
   **decorative** — `★ Write a review`, `★ Reviewed — thank you` — with no rating value; `<x-star-rating>`
   renders an N-of-5 row with an accessible name and would have nothing to bind. The one real guest DO10-4
   site is `guest/review.blade.php:35` (`str_repeat('★',$rating).str_repeat('☆',5-$rating)`), which belongs to
   **G8**. Fixed here instead: both decorative glyphs are now `aria-hidden`, so a screen reader no longer
   announces "black star" before the link text. **DO10-4 remains at 10 sites, not 7.**
3. **The GATE-3 register row for Trips was STALE — corrected in place.** It refused the hero **stats**, the
   card **image band** and the **countdown badges** as fabricated/needs-data, and asserted *"countries isn't
   tracked"*. All four claims are false today: the controller derives stays/nights/countries from the guest's
   own completed stays, `GuestTrip::card` carries a real `country`, and the band and countdown render on every
   card. **§N.8b, eighth instance, first in the guest realm** — and precisely the failure the recon predicted
   when it flagged that record as a lead rather than a conclusion.

### 🔒 Guards — 3 mutations, and ⚠️ ONE OF THEM EXPOSED A VACUOUS GUARD I HAD JUST WRITTEN

`GuestTripsTest` 4 → 6.

| guard | mutation | result |
|---|---|---|
| **scope-by-relation** (existing) | `$guest->reservations()` → bare `Reservation::query()` | ❌ RED on the cross-guest assertion |
| **#33 — no door code/wifi on the list** (new) | inline the unit's `door_code` into the card | ❌ RED *(only after the fixture was fixed — see below)* |
| **#39 — no fabricated trip** (new) | emit a placeholder card in the `@empty` branch | ❌ RED |

⚠️ **The #33 guard passed its own mutation on the first run — it was protecting nothing.** Root cause:
`AssignmentService::createBooking` reserves at **room-type** level and leaves `reservation_units.unit_id`
**NULL**, so the booking had no physical unit, so there was no door code anywhere to leak. The fixture now
assigns a unit through the certified `assignUnit()` and asserts the code is genuinely reachable before
asserting it is absent. **O32, exactly: when a mutation passes, the first question is what you actually
removed — here, nothing.**

### 🚩 REAL GAPS — logged, not built

- **`DO-G1-1` · the dead `partials/trip-card.blade.php`.** Delete it, or wire it. Deleting is a
  non-design change; wiring it would duplicate the inline card (§N.8f). Same call needed for
  `guest/home.blade.php`.
- **`DO-G1-2` · the past card shows no RATING.** The prototype's reviewed past card renders a real star row
  plus a `ratingLabel`; the live card shows only "★ Reviewed — thank you". The data exists and the controller
  **already queries `Review`** — it just `pluck`s `reservation_id` and discards the rating. A one-line
  controller change, deliberately NOT made at a design-only gate. **This is the one place the live Trips page
  is genuinely poorer than the design**, and it is where `<x-star-rating>` would legitimately land.
- **`DO-G1-3` · prototype affordances not built** (verdicts owed): the search field's magnifier + clear-"×"
  button; the year filter as a styled popover rather than a native `<select>`; the PAST tab as a 3-up **grid**
  with a top image band (live uses the same full-width horizontal card for every tab); "Book again" and
  "Receipt" actions on past cards; the amber "Share how your stay went" prompt on an unreviewed past stay.

### ✅ REFUSALS / EXTRA WORK TO PRESERVE

- **The hero copy hierarchy is INVERTED vs the prototype and that is the better call** — the prototype puts
  the guest's name in a small accent pill and "Your trips" as the 30px headline; the live page makes
  "Welcome back, {name}" the headline. Not changed: it is a deliberate, honest choice, flagged for the
  founder rather than silently "fixed".
- **The image band is a deterministic gradient, not a fake photo** (#39) — the prototype implies photography
  the product does not have.
- **Native `<select>` over the prototype's custom popover** — more accessible, and the no-Alpine realm has no
  popover primitive.
- **Scope-by-relation** — the guest sees only `$this->guest()->reservations()`; a foreign id cannot appear.

---

## §G-2 — Guest Reservation (`/guest/reservation/{r}`) · GUEST 2/32 · gate 2 of 12 · + the SHELL-VERIFICATION fold

`GuestReservationController@show` → `guest/reservation.blade.php` in `<x-guest.shell>`. Auth `auth:guest`.
Walked as the demo guest against **both** #33 states: a stay 25 days out (window closed) and a stay
mid-occupancy (window open).

### 🔒 #33 — ⚠️ THE EXISTING WINDOW GUARD WAS VACUOUS, AND SO WAS THE FIRST REPLACEMENT MUTATION

`GuestReservationTest`'s docblock claimed *"the door code / wifi are gated to the release window (#33)"*.
**It was not testing that.** `bookLedBy()` uses `createBooking`, which reserves at ROOM-TYPE level and leaves
`reservation_units.unit_id` **NULL** — no unit, no code, nothing for the gate to withhold. **PROVEN: deleting
the window check from `ArrivalPack` entirely (`'door_code' => $code`) left all four tests GREEN.** Identical
root cause to the vacuous guard caught at G1, on the realm's most security-sensitive control, and on the
FIRST surface that renders it.

⚠️ **Then the replacement guard's own first mutation ALSO passed — and that one was NOT vacuity.** The code
is gated **twice, independently**: `ArrivalPack` nulls `door_code` outside the window, *and* the view's
`@if ($arrival['released'] && …)` re-checks. Removing either alone changes nothing. Only removing **both**
produces a real leak — and then the new guard goes RED. **O32 cuts both ways: a passing mutation can mean
redundancy rather than a vacuous test, and the way to tell them apart is to keep removing defences until the
leak is real.** The old test stayed green even under that full both-gates removal; the new one does not.

The new guard drives ONE booking across the boundary — `LEAD_DAYS + 1` before check-in (withheld) and the day
the window opens (released) — with a unit assigned through the certified `assignUnit()` and an assertion that
the code is genuinely reachable first.

### ⚠️ THE UNLOCK COPY WAS A HARDCODED MOCK VALUE — now it follows the engine

The page read **"Your door code & Wi-Fi unlock 48h before check-in."** That "48h" is the **prototype's** value
(*"Door code arrives 48h before check-in"*). It equals `ArrivalPack::LEAD_DAYS = 2` **today**, so it looked
correct — and would have silently told every guest the wrong day the moment that constant moved. It now
prints the gate's own `released_from` (verified live: a 5 Sep check-in renders *"unlock on Thu, 3 Sep"*).

**Same family as the owner realm's O32** — a design literal quoted on the surface whose engine defines the
real value. A guard now asserts the derived date is present **and that "48h" is absent**.

`has_code` was also being ignored: the page promised a door code even when no unit carried one. The message
now only names a code when one genuinely exists; otherwise it says arrival details appear before check-in.

### 🎨 Contrast — 4 real failures across the two states → **0** (53 + 57 runs)

Anchors validated on **both** renders: white on `--vy-grad` = **3.39**, white on solid `--vy-accent` = **5.18**.

| run | was | now | fix |
|---|---|---|---|
| `Check in online · 2 min` | white 12.5px on `--vy-grad` = **3.39** | **5.18** | solid `--vy-accent` (the G1 CTA finding, page-level twin) |
| `Upcoming` badge | `rgb(70,120,60)` = **4.26** | **7.24** | new `--vy-green-text` |
| `€184.50` balance | `--vy-amber` 18px = **4.08** | pass | `--vy-amber-text` (this call site still used the FILL token as text) |
| `Arrival due` badge | `--vy-amber-text` **#8F6414** = **4.37** | **5.35** | ⚠️ **darkened to `rgb(126,88,17)` — see below** |

⚠️ **A CORRECTION TO G1, from G1's own token.** G1 introduced `--vy-amber-text` as `#8F6414` — *"the
prototype's own amber, not invented"* — and recorded **4.70 / 5.06**. Those were measured **on Trips only**.
The same token measures **4.37** on this page's warmer hero plate (`#F8E8D4`) and **fails**. Root cause worth
carrying: **these badges are translucent, on a translucent card, over the page gradient — their contrast has
no fixed value, it moves with whatever is behind them.** The token is darkened to the minimum that clears 4.5
on the worst backdrop measured so far (5.35 here, 5.76 on Trips, 6.20 on the balance figure), and **G1's
comment was corrected in place**. G1's page was re-measured after the change: still **0 failures / 44 runs**.

**Lesson for G3–G12: a token verified on one page is not verified.** Re-measure shared tokens on every new
backdrop.

### ⚙️ THE FOLDED SHELL-VERIFICATION — G1's by-construction fix CONFIRMED

- ⚠️ **`.vy-btn` = 5.18 — CONFIRMED on a real render, but nearly on a false one.** The first three `.vy-btn`
  elements measured came back as `#C2410C` on near-white — they are all `.vy-btn--ghost`. The **primary**
  `.vy-btn` renders only when a balance is due, i.e. on the in-window booking: `Pay your balance →`, white on
  `#C2410C` = **5.18**. Reporting the ghosts as confirmation would have been a false pass on the exact item
  G1 flagged as unverified.
- **The four shared-shell fixes hold on this second view**: brand mark 5.18 · `.vy-btn` 5.18 · nav
  `--vy-accent-text` 4.99 · `--vy-amber-text` — *held only after darkening* (see above).
- **Both contrast anchors validated on both renders**, so the probe was sound this run.

### ⌨️ Keyboard — clean

20 interactive · **0 unnamed** · 0 unfocusable · **0 `div@click`** · **0 `onclick`/`onchange`** · 0
`display:none` controls · 0 horizontal overflow at 1440. §N.4k: one form (logout, POST, 1 submit, 0 visible
inputs) — no implicit-submit ambiguity.

### ✅ VERIFIED / REFUSALS TO PRESERVE

- **Foreign-id → 404**, mutation-proven: replacing `$this->guest()->reservations()->findOrFail()` with a bare
  `Reservation::query()->findOrFail()` turns the cross-guest test RED. O32: the relation (`lead_guest_id`) is
  the guard — a global scope would still have resolved another guest in the same org.
- **#28 inverted:** zero operator internals, owner money or other-guest references in the rendered DOM on
  either state.
- **No rating on this page** — so no `<x-star-rating>` call site here; **DO10-4 stays at 10**. The decorative
  lock icon in the arrival block is now `aria-hidden`.
- **Already-honest refusals kept:** no Apple Pay / Face ID / saved-card vault (#45, SAQ A), no invented
  "~10 min" support SLA, no invented check-in/out hour when the property published none, and the prototype's
  unconditional *"Wifi: CoralisGuest"* stays gated.

### ⚠️ AND THE GATE SHIPPED A FORBIDDEN LITERAL SITE-WIDE — caught by an unrelated honesty guard

The `--vy-amber-text` correction above was written as a **CSS comment in the shared shell**, and it used the
word the check-in page's #39 guard forbids (an ID is *captured*, never that word — KYC is account-gated).
**CSS comments RENDER; Blade comments do not** — so the word shipped into *every* guest page and
`GuestArrivalCheckinTest`'s `assertDontSee` went red. The full-suite count moved 9 → 10, the stash test
proved it was mine rather than pre-existing, and the comment was reworded.

This is the documented CSS-comment trap, walked into at the first opportunity. Two things it re-teaches:
**(a)** a comment in a SHARED stylesheet is page content on every page that stylesheet reaches, so its
word choice is subject to the same honesty guards as copy; **(b)** the baseline discipline earned its keep —
without knowing the count was 9, a 10 would have looked like the documented range.

### 🚩 LOGGED — not built

- **`DO-G2-1` · `rgb(70,120,60)` is hand-written at ELEVEN guest call sites** (`manage`, `trips`, `payments`
  ×2, `reservation` ×2 *(fixed here)*, `wallet` ×2, `receipt`, the dead `trip-card`, and the shell *(fixed
  here)*). §N.8f — same shape as DO10-4's eleven hand-written stars. The shell now defines
  `--vy-green-text`; the remaining call sites belong to their own gates.
- **`DO-G2-2` · `--vy-amber` is still used AS TEXT on four guest pages** (`payments` ×2, `wallet`, `receipt`)
  where G1/G2 replaced it with `--vy-amber-text` on Trips/Reservation. ⚠️ **Unmeasured — a prediction, not a
  finding.** Those gates must measure it; on this page the same construct read 4.08.
- **`DO-G2-3` · prototype affordances owed a verdict:** the "Good to know" card's per-item sub-lines ("By
  message and email — no key pickup needed"), the early-check-in request row with its Pending chip, and the
  three modals the prototype drives in JS (Manage booking / cancellation / pay-now) which this no-Alpine
  realm renders as separate GET pages instead.

---

## §G-3 — Guest Arrival screen + Arrival Guide PDF · GUEST 3/32 · gate 3 of 12 · 🔒 THE #33 GATE PROPER

`GuestArrivalController@show` → `guest/arrival.blade.php` in `<x-guest.shell>`, and `@pdf` →
`pdfs/arrival.blade.php` via `App\Support\Pdf\ArrivalPackDocument`. Auth `auth:guest`. Walked as the demo
guest in **both** window states, screen and generated PDF.

### ✅ THE DELEGATION — the gate's critical check, and it HOLDS

`ArrivalPackDocument::data()` returns `'pack' => ArrivalPack::for($reservation, $today)` and **never touches
`$reservation->…->unit` for a secret**. Its docblock claims *"built from the SAME window-gated ArrivalPack …
so the three surfaces cannot drift"* — **read against the code, the claim is TRUE.** After nine §N.8b
instances this is the first docblock this pass has checked and found accurate, which is worth recording as
plainly as the failures.

### ⚠️ PREMISE CORRECTION — the PDF is NOT the weaker path

The gate was scoped on the expectation that *"the PDF has no view re-check, so it relies entirely on
ArrivalPack's data-layer null and is the MORE DANGEROUS path."* **False.** `pdfs/arrival.blade.php:55` opens
with `@if ($pack['released'])`, exactly like the screen. **Both surfaces are double-gated.**

**The defensive set, mapped by mutation** (G2's O32-cuts-both-ways discipline, applied deliberately):

| mutation | result | what it proves |
|---|---|---|
| **M1** — remove `ArrivalPack`'s data-layer null ALONE | all 11 GREEN | redundancy, **not** vacuity — each template's own `released` check still holds |
| **M2** — data null **+ the PDF template's** check | **PDF test RED** | the PDF's #33 guard is real and catches the leak |
| **M3** — data null **+ the SCREEN's** check | **screen test RED** | the screen's guard is real |

⚠️ **And unlike G1 and G2, the fixtures here were already sound.** `arrBook()` passes `unit_id` explicitly to
`createBooking`, so a real unit with a real code is attached and the guards have something to leak. The
systemic vacuous-#33 fixture trap that caught G1 and G2 **does not apply to this file** — the tests that
matter most were the ones written properly.

### ⚠️ SCREEN ≠ PDF — the two surfaces disagreed about the one fact a locked-out guest needs

The **PDF** printed *"…unlock as your stay approaches — from {released_from}"*. The **screen** said only
*"The door code and WiFi unlock as your stay approaches — for your security."* — **no date at all**, on the
realm's dedicated arrival surface. The screen now names the gate's own `released_from` (verified live: a 5 Sep
check-in renders *"unlock on Thu, 3 Sep"* on both). Guarded, and the guard is mutation-proven in **both**
directions — reverting either surface turns it red.

### ⚠️ A SECOND ENGINE-CONTRADICTING LITERAL, in the PDF footer

Every page of the guide footed with **"The door code is live from check-in."** The engine releases it
`LEAD_DAYS` **before** check-in and keeps it until check-out, so the line was wrong in the *understating*
direction — and it printed on the **in-window** guide, where the code is already visible days before
check-in, contradicting itself on the same page. Removed; the timing is now stated **once**, from the gate.
Third instance of the G2 mock-value family (after the reservation hub's "48h" and the prototype's own copy).

### 🎨 Contrast

**Screen — 1 failure → 0 (35 runs).** `[ghost] Copy` on the door-code tile: `--vy-accent` on `--vy-wash`
= **4.41**, while the identical button on the white card measured **5.03**. G2's lesson a third time — the
same token, a different plate. `.vy-btn--ghost` now takes `--vy-accent-text` (**5.28** on the wash, 5.9 on the
card), which also lifts G2's ghosts from 4.99.

⚠️ **Only ONE anchor was testable on this render.** Anchor 2 (white on solid `--vy-accent`) held **exactly
5.18**, twice — including the primary `.vy-btn`. Anchor 1 (white on `--vy-grad`) has **no call site left on
this page**, because G1 replaced the brand mark's gradient with a solid. Stated rather than glossed: the
per-run self-check was half-strength here, and the PDF's independent arithmetic agrees with it.

**PDF — all 7 declared text runs pass on paper**, measured against the plate each actually sits on:
`#2B2320` 15.40 · `#5F5853` 6.98 · `#6C645F` 5.79 · `#C2410C` eyebrow 5.18 · white-on-accent mark 5.18 ·
and on the amber withheld callout (`#FBEFD8`) 13.52 / 6.13.

⚠️ **A histogram over the rendered PDF was the wrong probe and was discarded** — it counted the callout's
22,469-pixel background and the card borders as "ink" and reported them as failures. Plates are not text.

**The `arrival` template's own PDF-sweep literals are fixed**: `#8A8079` ×4 (**3.86**) → `#5F5853`, and
`#A69A92` ×2 (**2.74**) → `#6C645F` — the realm-wide pattern, in this template only. **`PdfService` untouched**;
its `#a99b8e` watermark at **2.70** remains for the dedicated sweep. ⚠️ Note: **no watermark appears on the
generated arrival guide**, so the 2.70 figure could not be confirmed on this document.

### ⌨️ Keyboard + honesty

11 interactive (out-of-window) · **0 unnamed** · **0 `div@click`** · 0 overflow. §N.4k: no form on the page.
**The Copy buttons are real** — an inline vanilla-JS clipboard handler backs them (this realm is
**no-Alpine**, not no-JS), and they render **only** when something is released, so there are **0 dead
controls** in the withheld state. **#28**: no operator internals, owner money or other-guest data on either
surface, either state. **Foreign-id → 404** on both routes, already guarded and mutation-proven.

### ✅ REFUSALS TO PRESERVE

- **The prototypes show the code UNCONDITIONALLY** (both the screen and PDF mocks print "4 8 2 1"). The live
  window-gating is the honest refusal of that design, on both surfaces.
- **The prototype's House guide + "Before you leave" checkout list + local picks are long fabricated prose**
  (keypad instructions, AC settings, coffee-pod locations). Refused; the real `arrival_notes` is shown
  instead — and is itself window-gated, since it describes physical access.
- **Local recommendations** stays an honest "coming soon" rather than invented neighbourhood content.

### 🚩 LOGGED — not built

- **`DO-G3-1` · the realm-wide PDF sweep** — 5 guest templates remain (`folio`, `guest-statement`,
  `guest-vat-invoice`, `guest-credit-note`, `guest-refund-receipt`) plus `PdfService`'s watermark. `arrival`
  is now clear.
- **`DO-G3-2` · prototype affordances owed a verdict** — the "Get directions" map link, the spaced-digit
  code presentation with a keypad hint, and the structured House-guide sections (which would need a real
  per-property guide model, not prose).

---

## §G-4 — Guest Check-in (`/guest/reservation/{r}/check-in`) · GUEST 4/32 · gate 4 of 12 · the first INBOUND-PII surface

`GuestCheckinController` (GET+POST) → `guest/checkin.blade.php` in `<x-guest.shell>`, with
`GuestIdDocumentService` behind the ID capture. Auth `auth:guest`. Walked **read-only** — nothing submitted,
no ID uploaded.

### ✅ THE HEADLINE CHECK — `anonymize()` REACHABILITY HOLDS, mutation-proven

Check-in writes exactly **four** stores. Each is covered, and each coverage was proven by **removing it and
watching the erasure suite go red** — not by reading the code:

| store | written by | `anonymize()` | mutation |
|---|---|---|---|
| `guests.phone` | the arrival-contact field | `'phone' => null` | ❌ RED (2 tests) |
| `reservations.notes_guest` | the free-text arrival note | `'notes_guest' => null` | ❌ RED (2 tests) |
| `reservations.arrival_window` | the arrival slot | `'arrival_window' => null` | ❌ RED (2 tests) |
| `guest_documents` + `documents` + `media` + **the encrypted file bytes** | `GuestIdDocumentService::store()` | the `guestDocuments()` purge loop | ❌ RED (2 tests) |

`reservations.guest_checked_in_at` is a **timestamp, not PII** — retained as audit skeleton, the same
redact-don't-delete shape used throughout `anonymize()`. **No uncovered store: there is nothing to log here.**
The CLAUDE.md rule ("every free-text store about a guest is added to `anonymize()` in the same change that
creates its writer") was genuinely followed — `anonymize()`'s own comment says `notes_guest`/`arrival_window`
landed *"WITH the online-check-in writer that first populates it"*, and that is true.

### ⚠️ §N.4k — A REAL DEFECT ON A PII FORM: Enter completed the check-in from step 1

The wizard is **ONE form spanning all three steps** with a single submit ("Complete check-in") and ten
`type=button` step controls. A browser implicitly submits a form when Enter is pressed in a text input — so
**Enter in the phone field on step 1 fired the real write**: it saved the phone, stamped
`guest_checked_in_at` (a once-only stamp) and finished check-in **without the guest ever seeing the ID or
arrival steps**. A guest pressing Enter means "next field", not "submit my identity".

**Proven live, without writing anything** — a `submit` listener with `preventDefault()` was attached first,
so the probe recorded `SUBMIT-WOULD-HAVE-FIRED` while nothing reached the server. After the fix the same
probe records no submit and the stepper advances to Step 2.

⚠️ **The guard is deliberately in the JS layer.** The stepper's own comment states the no-JS contract:
*"JS-off → all steps visible + submittable"* — and with every step visible, implicit submission is **correct**,
because the form really is complete. So the guard applies exactly when the wizard is hiding steps and
disappears with it. Textareas are untouched (they never implicitly submit; Enter is a newline in the note).

### ⚠️ THE ID UPLOAD WAS KEYBOARD-UNREACHABLE

`<input type="file" name="id_photo" … hidden>` — the `hidden` attribute is `display:none`, which removes an
element from the tab order **entirely**, and its wrapping `<label>` is not focusable. Measured on the render:
`fileInputInTabOrder: false`, `offsetParent: null`, `label.tabIndex: -1`. **A keyboard-only guest could not
upload their ID at all** — the canon's *"appearance:none, never display:none"*, on the realm's PII-capture
control. Now visually hidden but focusable (`clip-path` pattern) with `:focus-within` putting a visible 2px
ring on the drop zone the guest can actually see. Verified: `inTabOrder: true`, focus received, ring renders.

### ⚠️ A TOKEN MOVED AT G2 AND THIS PAGE DIDN'T — because it never referenced the token

`checkin.blade.php` hardcoded **five** colour literals instead of tokens. One of them, `#8f6414`, is
**`--vy-amber-text`'s value BEFORE G2 darkened it** to `rgb(126,88,17)`. The token moved; this copy didn't,
which is precisely what a token exists to prevent — and why the status chip measured **4.33** while the
tokenised badges pass. §N.8f, made concrete. All five now reference tokens.

### 🎨 Contrast — 8 failures → **0** (58 runs). Both anchors validated (3.39 · 5.18)

| run | was | fix |
|---|---|---|
| the four **form labels** (First name / Last name / Email / Phone) | `#9c9089` = **2.99** | `--vy-faint` |
| `Continue to ID` CTA | white 14px on `--vy-grad` = **3.39** | solid `--vy-accent` (5.18) |
| the active **step number** | white on `--vy-grad` = **3.48** | solid `--vy-accent` (5.18) |
| `Message the property` | `--vy-accent` = **4.24** | `--vy-accent-text` (5.03) |
| `Not checked in yet` chip | stale `#8f6414` = **4.33** | `--vy-amber-text` |

⚠️ **The worst readings on the page were the form labels of a PII form** — 2.99, the fields a guest must read
to type their identity correctly. **Gradients behind SVG icons were left alone** (`.ci-hero-mark`,
`.ci-idzone .ic`, `.ci-done-ic`): decorative, no text, so the brand gradient stays where it is safe.

### 🖼 A render-only layout defect

*"Door code by email**Sent before arrival — no key pickup**"* — the title and its subtitle ran together on all
three "What this unlocks" rows. The prototype sets `display: block` on both spans; the live CSS omitted it, so
they rendered inline and concatenated. Invisible to `php -l`, PHPStan, Pint and a Blade compile — only the
render shows it.

### ⚠️ PREMISE CORRECTION — check-in is NOT an `ArrivalPack` call site

The recon (and this gate's brief) listed check-in among the **six** `#33` call sites. It is not one:
`GuestCheckinController` neither imports nor calls `ArrivalPack` — line 32 is **prose in a docblock**, which
is what my earlier grep matched. The real call sites are **four**: `GuestArrivalController`,
`GuestReservationController`, `PreArrivalMail`, `ArrivalPackDocument`. **The check-in screen renders no door
code at all**, so its `#33` exposure is nil. Recon §2.1's "six" is corrected.

### ✅ VERIFIED / REFUSALS TO PRESERVE

- **Observed READ-ONLY**: `guest_documents` 0 → 0, ID media 0 → 0, `guest_checked_in_at` 0 → 0,
  `notes_guest` 0 → 0, `max(reservations.updated_at)` unchanged at `2026-08-09 07:17:58`.
- **The "Door code by email" promise is TRUE** — verified, not assumed: `PreArrivalMail` really does carry the
  code and the command sends at `ArrivalPack::LEAD_DAYS`. It is a real capability claim, not a fabricated one.
- **The prototype promises "By SMS to {phone} and email"** — SMS has no provider. The live copy says email
  only. Refusal preserved (#39).
- **The ID is captured, never "verified"** — `verification_status: 'pending'`, and the page never shows a
  verified badge. Prefilled name/email/phone are the guest's own real values (#28); no operator
  verification-status, internals, owner money or other-guest data in the DOM.
- **Foreign-id → 404 on both GET and POST** via `$this->guest()->reservations()->findOrFail()`, already
  guarded and mutation-proven at G2/G3; the POST case has its own existing test.

### 🚩 LOGGED — not built

- **`DO-G4-1` · a fourth mock-value instance, in a docblock.** `SendPreArrivalEmails` documents its default as
  *"{@see ArrivalPack::LEAD_DAYS} = 48h"*. The code reads the constant correctly; only the prose restates it.
  Not user-facing and it is backend, so it is surfaced rather than edited at a design gate.
- **`DO-G4-2` · the pre-arrival email's `#8A7A6A`** grey sits in the `DO-INV-4` email-contrast sweep, unmeasured
  here.
- **`DO-G4-3` · prototype affordances owed a verdict** — the done-state's "Door code arrives {date}" (the live
  done state says "before arrival"; naming the real date would need the controller to pass `ArrivalPack`,
  which is a backend change) and the per-guest co-traveller entry the prototype implies.

---

## §G-5 — Guest Manage Booking + Add-ons · GUEST 5/32 · gate 5 of 12 · the REQUEST-WRITE PAIR

`GuestManageController` (GET + 3 POSTs) → `guest/manage.blade.php`, and `GuestExtrasController@show` →
`guest/extras.blade.php`, both in `<x-guest.shell>`. Auth `auth:guest`. Walked **read-only** — no request fired.

### ✅ THE CENTRAL CHECK — they really ARE requests, and they render as such

`GuestChangeRequest` is a genuine state machine: the guest creates `pending`, and **only the operator** moves
it to `confirmed`/`declined`. **No manage POST mutates the reservation at all** (grep-verified). The controller
labels are "Date change requested" / "Add-ons requested" / "Cancellation requested", and a pending request
renders with the amber `vy-badge--pending` — no green plate, no tick, no struck-through booking. §N.8d holds in
**both** directions: the in-flight request IS surfaced (so the guest is not told "nothing requested" and does
not re-request), and it is never dressed as done.

The existing `GuestManageBookingTest` was already strong — pending-only, booking untouched, no self-approve,
foreign→404, folio untouched. Like G3's arrival tests, written properly.

### ⚠️ #39 — A FABRICATED POLICY, SHIPPED VERBATIM FROM THE PROTOTYPE

The extras page promised: **"Free to cancel up to 24h before arrival."**

**Nothing implements it.** There is no cancellation window on `AddOn`, `ReservationExtras` or in config, and
**no add-on removal path anywhere** — the only extras route is a `GET`, and an add-on can only ever be
*requested*. The claim came straight from the prototype (`Guest Add-ons.html`: *"free to cancel up to 24h
before arrival"*) and was rendered as if it were policy.

This is the **fifth** instance of the mock-value family and the first that fabricates a **right** rather than
restating a constant: G2's "48h" at least mirrored a real `LEAD_DAYS`; this one has no engine behind it at all.
A guest relying on it would have been misled about what they can undo. **Removed** — the surrounding sentence
("Your host confirms, then it's added to your booking") was already honest and sufficient. No replacement
policy was invented.

### ⚠️ §N.4k — Enter fired the cancellation request with no chance to reconsider

Measured in the browser with every submit intercepted (nothing reached the server): with a reason chosen,
**Enter on the reason `<select>` fired the cancellation request immediately**. The guest means "confirm my
choice" and gets "send the request", on the most consequential action of the page — and the prototype puts a
**review step** in front of it (its cancellation modal is review → done).

Guarded with `onsubmit="return confirm(…)"` plus `data-guard-submit` — **the repo's own destructive-submit
idiom** (`admin/customers/show.blade.php` already uses exactly this pattern), not a new invention.

⚠️ **The date-change form is a GET**, so "See price difference" is a *preview*, not a request — Enter there is
harmless by construction. That is good design and worth recording: the guest previews the price, then requests.

### ⌨️ The add-on toggles — reachable, but invisibly focused

The add-on checkboxes use `opacity: 0`, **not** `display: none`, so unlike G4's file input they stay in the tab
order and keep their labels (4 of 4 tabbable). **But an invisible input shows an invisible focus ring, and the
card carried none** — a keyboard guest could tab through every extra with no idea which was focused
(WCAG 2.4.7). `.ex-item:focus-within` now rings the card, mirroring G3/G4's fix.

### 🎨 Contrast — Manage 1 → **0** (24 runs) · Extras 5 → **1 exempt** (40 runs)

| run | was | fix |
|---|---|---|
| `Request cancellation` | `--vy-amber` (the FILL token used as text) = **4.08** | `--vy-amber-text` |
| `Add to stay` ×4 | white 12.5px on `--vy-grad` = **3.39** | solid `--vy-accent` |
| selected add-on label | `--vy-accent` on its own tint | `--vy-accent-text` |

⚠️ **`[disabled] Add to booking` measures 2.24 and is NOT counted as a failure.** WCAG 1.4.3 exempts inactive
components. It is reported rather than silently dropped, and rather than "fixed" to claim a cleaner number.

⚠️ **I over-applied one fix and caught it**: a global replace of `var(--vy-amber)` also recoloured a **border**,
where the fill token is correct. Reverted — text tokens are for text.

**Anchors:** Extras validated **both** (3.39 · 5.18). Manage could validate only anchor 2 (5.18, twice) — after
the fixes it has no gradient-backed text left, the same situation as G3. Stated, not glossed.

### ✅ THE G4 LESSON APPLIED — a changed token's OLD value, grepped across the realm

Searched every guest view for the pre-remediation literals (`#8f6414`, `#9c9089`, `#8a8079`, `#a69a92`).
**One hit, and it is a comment** — my own G2 explanatory note in the shell naming the old value. No live
declaration anywhere. Also fixed the `DO-G2-1` tail on this page: `rgb(70,120,60)` → `--vy-green-text`.

### ✅ VERIFIED / REFUSALS TO PRESERVE

- **Observed READ-ONLY**: `guest_change_requests` 0 → 0; `max(reservations.updated_at)` unchanged at
  `2026-08-09 07:17:58`. No cancellation, date-change or add-on request was fired.
- **The refund copy invents no figure**: "Free cancellation until {real date} — a request cancelled before then
  is refunded in full", else "The property confirms your cancellation and handles any refund per their policy."
  The date is the real policy deadline; no fabricated amount, and the framing is a request, not a promise (#27).
- **Add-on prices are real** — `add_ons.price_minor` × a real quantity basis + real VAT (€65 each way → €79.95;
  €18 per guest·night → €132.84 for 2 guests × 3 nights). Explicitly not charged: "Nothing to pay now",
  "Your host confirms, then it's added to your booking" (#39/#45).
- **`Convert to travel credit`** — the prototype's upsell has no backend and stays refused (an existing guard).
- **#28**: no operator cost, margin, approval queue, owner money or other-guest data on either page.

### ⚠️ BASELINE — a new FLAKY candidate observed, not a regression

One full-suite run showed **10** non-green, the extra being `PublicStorefrontLandingTest`. It did **not**
reproduce on a second full run with the same changes (back to 9), it passes in isolation both with and
without this gate's changes, and a full run with the changes stashed was also 9. Evidence says
order/timing flakiness, not this work — a candidate NEW member of the documented non-deterministic set
alongside `BookingImportTest`. Recorded so a later gate seeing a 10 does not mistake it for a regression;
**the baseline remains the 5–10 RANGE, which is exactly why §J.quater refuses to state it as a number.**

### 🚩 LOGGED — not built

- **`DO-G5-1` · the prototype's cancellation REVIEW step.** The design shows a refund breakdown and a policy
  timeline before confirming; the live flow is a reason + a confirm dialog. A real breakdown needs a
  policy/folio computation the page does not currently receive — a backend change, not a design one.
- **`DO-G5-2` · no request-history surface.** A guest sees pending requests on Manage, but there is no view of
  *declined* ones — the operator's decision is invisible to the guest once it leaves `pending`. Worth a product
  decision (§N.8d's understating direction, at the edge of what this gate could verify).

---

## §G-6 — Guest Payments + Folio Receipt · GUEST 6/32 · gate 6 of 12 · 💰 THE MONEY SURFACE

`GuestPaymentsController` (+ `GuestFolioPaymentController@pay`) → `guest/payments.blade.php`, and
`GuestReceiptController@show` → `guest/receipt.blade.php`. Auth `auth:guest`. Walked **read-only** — no payment
fired, no Checkout completed.

### ✅ #17 RECONCILES — verified on the RENDERED page, to the cent

| | |
|---|---|
| Total charged | **€1,107.00** |
| Paid to date | **€922.50** |
| Balance due | **€184.50** ← 1107.00 − 922.50 ✅ |
| Payment activity | 369.00 + 184.50 + 369.00 = **922.50** ✅ = "Net paid" = "Paid to date" |
| Receipt (R-ZSVMQVBW) | 300.00 + 69.00 VAT = **369.00** subtotal; − 184.50 paid = **184.50** balance ✅ **ties to the hub** |

One source throughout: `FolioTotals::for($folio)`, integer minor. **No money arithmetic in either Blade view.**
`FolioTotals`' own docblock states the identity `paid_minor === received_minor − refunded_minor` and explains
that refunds are derived in the *same pass* precisely because a second walk is how two figures drift (#17).

⚠️ **MONEY-LABEL HONESTY — and the prototype gets it wrong.** The mock's DCLogic defines
**`Paid = charged − refunded`**. The live definition is **`paid = received − refunded`** — net cash actually
received. Those differ: on an unpaid booking the prototype's formula would report the full charge as "Paid".
**The live is correct and the prototype is not**, so this is a refusal to preserve, not a gap. The view's own
comment already records that *"'Paid to date' is net of refunds"*. Labels verified as accurate: *Total
charged* · *Paid to date* · *Balance due* · *Net paid* · *Subtotal (total charges)* · *Total paid* · *Balance*.

### ✅ #45 — NO RAW PAN, verified in the DOM

The pay form's **only** field is `_token`. **Zero** card/PAN/CVC inputs anywhere on either page. The POST
creates a hosted Stripe Checkout session and redirects; the card never reaches the app (SAQ A). The page says
so plainly: *"No card is stored on your account… your card details never touch Vyrobel."* — a claim that is
**true**, verified against the seam.

### ⚠️ §N.8d — THE UNDERSTATING DIRECTION, ON THE MONEY SURFACE

Stripe returns the guest to `?paid=1`, but **the folio settles only on the `payment_intent.succeeded`
webhook**. The page read **nothing** from that return: a guest coming back from a *completed* checkout saw an
unchanged balance and **no acknowledgement whatsoever**, and could reasonably conclude it had failed and pay
again. (The deterministic idempotency key protects the money; the impression was still false.)

⚠️ **The overstating half was already right and stays right** — nothing fabricated a "Paid ✓", a green settled
badge or a zeroed balance. The fix adds only the missing acknowledgement, and is worded so it asserts **nothing
that could be false**: `?paid=1` is just a URL anyone can type, and even a genuine return proves the checkout
completed on Stripe's side, never that the webhook has landed. So it states the one unconditionally true thing
— *"A card payment can take a few moments to confirm with your bank. Your balance below updates as soon as it
does — there's no need to pay again."*

**Pending is also correctly excluded at the data layer**: `GuestPayments` promotes only `succeeded`/`captured`
to history, with the comment *"pending/failed/holds are not 'history'"* — verified accurate.

### 🔒 THE WORST LEAK ON THIS SURFACE WAS UNGUARDED

The hub and the receipt already had cross-guest tests, but the **`/pay` POST** was only asserted to *appear in
the HTML*. Nothing proved a foreign booking id is refused — and opening a Checkout session against another
guest's folio is the worst outcome here (they would see that booking's balance on Stripe, and pay it). Now
guarded and **mutation-proven**: swapping the relation for a bare `Reservation::query()` turns it RED, and the
test also asserts B's folio gains no pending Payment, plus that A's own booking still redirects (so it cannot
pass because the route is merely broken).

### 🎨 Contrast — Payments 0 (58 runs) · Receipt 1 → **0** (37 runs)

The one failure was **`€184.50` — the balance on a receipt**, i.e. the single most important figure on the
page: `--vy-amber` (the **fill** token) used as text at **4.11**.

⚠️ **This is `DO-G2-2`, which G2 logged as an unmeasured PREDICTION — now measured and confirmed.** The full
sweep found `--vy-amber` used as text at **five** sites. Fixed here: `payments` ×2, `receipt` ×1, **and
`extras.blade.php:44` — which I MISSED at G5** (its "Awaiting confirmation" chip). Remaining and logged:
`saved.blade.php:45` (G10's page) and the dead `partials/trip-card.blade.php`. The `DO-G2-1` green literals on
these pages went to `--vy-green-text` at the same time.

**Anchors:** anchor 2 (white on solid `--vy-accent`) validated at exactly **5.18** on both pages. Anchor 1 had
no call site left on either page after the gradient CTAs were solidified in earlier gates — the same situation
as G3/G5, stated rather than glossed.

### ⌨️ Keyboard — clean

12 interactive · **0 unnamed** · **0 `div@click`** · 0 overflow at 1440. The pay control is a real `<button
type=submit>` inside a POST form (visibly focusable via the shell's `:focus-visible` ring). §N.4k: the pay form
has **no text inputs at all**, so there is no implicit-submit ambiguity — Enter can only act on the focused
button, which is the deliberate action. Unlike G4/G5, this write is safe by construction.

### ✅ VERIFIED / REFUSALS TO PRESERVE

- **Observed READ-ONLY**: payments 16 → 16, all `succeeded` (no pending row created), `max(payments.updated_at)`
  unchanged at `2026-08-09 07:17:58`. No Checkout session opened.
- **#28**: zero operator cost/margin/commission, owner payout or other-guest folio lines in the DOM.
- **#27**: the refunded total renders **only when a real refund exists**, and `paid` is net of it by
  construction (`received − refunded`), so a refund can never push "paid" above what was received.
- **No fabricated saved card / Apple Pay** (existing guards, #39/#41/#45) — the page states no card is stored,
  which is true.

### 🚩 LOGGED — not built

- **`DO-G6-1` · Stripe live is the founder's cutover (§H).** The seam is built test-mode-first; nothing here
  fabricates a settled charge. No change requested.
- **`DO-G6-2` · a pending payment is invisible rather than shown as processing.** Excluding it from *history*
  is right; but a guest with an in-flight payment sees no row for it anywhere. The `?paid=1` notice now covers
  the moment right after checkout; a persistent "processing" row would need the view to receive pending
  payments — a controller change, not a design one.
- **`DO-G6-3` · `--vy-amber`-as-text remains at `saved.blade.php:45`** (G10) and in the dead
  `partials/trip-card.blade.php` (`DO-G1-1`).

---

## §G-7 — Guest Messages · GUEST 7/32 · gate 7 of 12 · 🔒 THE #28 BOUNDARY FROM THE GUEST SIDE

`GuestMessagesController` (`GET /guest/messages`, `GET /guest/messages/{thread}`, `POST …/{thread}`) →
`guest/messages.blade.php` + `guest/message-thread.blade.php`. Auth `auth:guest`. Walked **read-only** — no
message sent.

### ✅ ISOLATION (1) — OWN THREAD ONLY, and the owner boundary is STRUCTURAL

Threads resolve through `where('guest_id', $this->guest()->getKey())->whereNull('owner_id')` — the guest's own
id from the guard, never a request id. The `whereNull('owner_id')` is the **O27 pattern**: an owner↔operator
thread carries `owner_id` and no `guest_id`, so it cannot satisfy the guest query. Foreign guest thread, and
owner thread, both **404** — already guarded, and **mutation-proven** here: replacing `owned()` with a bare
`MessageThread::query()->findOrFail()` turns both scope tests RED.

### ✅ ISOLATION (2) — THE VISIBILITY FILTER. ⚠️ PREMISE CORRECTION: there isn't one, and there doesn't need to be

The gate was scoped expecting *"the query filters by a visibility flag (`is_internal=false` / `visible_to`)"*.
**There is no such flag, and no filter** — `$model->messages()->orderBy('created_at')->get()` renders every row.

That is **safe, because the separation is by TABLE**:
- `messages` has **no internal/visibility column at all** (verified against the migration and every later one —
  the only additions are `owner_id` on *threads* and a separate `message_stars` table). An "internal message"
  cannot exist in this schema.
- Staff annotations are **`notes` rows** (`notable_type = 'message_thread'`), written by
  `InboxService::addNote()` — **a different table the guest transcript never joins**.

**A table the query never touches is a stronger guarantee than a flag that can be forgotten** — the same shape
as the O27 owner boundary and G3's `ArrivalPackDocument` delegation. Recorded as a STRUCTURAL refusal.

**Proven anyway, because structure can be undone by a later edit.** A guard now seeds a REAL staff note —
through `InboxService::addNote()`, not a hand-rolled row (the systemic vacuous-fixture lesson from the #33
guards) — asserts it genuinely exists, and asserts the transcript renders neither it nor its contents.
⚠️ **Mutation-proven the hard way**: rendering the thread's `notes` alongside the transcript makes the guard
go RED. The DOM sweep for operator-internal text on the live page was clean.

⚠️ **And the first attempt at that mutation silently did nothing** — the Python anchor didn't match the view
(`@forelse`, not `@foreach`), so the edit never applied and the run reported a meaningless "passed". Caught
and redone against the real markup. **A mutation that fails to apply looks exactly like a guard that holds.**

### ✅ `anonymize()` REACHABILITY (G4's rule) — covered and mutation-proven

The guest's own messages are free-text PII. `Guest::anonymize()` redacts `messages.body` + `sender_label`
across the guest's threads, and blanks `notes.body` on those threads too. **Mutation-proven**: removing the
message-body redaction turns both `GuestMessagesTest`'s erasure guard and `GuestErasureTest` RED. Nothing
uncovered — nothing to log.

### ✅ THE SEND — isolation was already tested, and G6's gap does NOT recur

Unlike G6's `/pay` (present but not isolated), the send POST already had a foreign-thread 404 test. Extended
with the half G6 taught: the foreign thread **gains no message**, and A's own send still lands — so it cannot
pass merely because the route is broken.

### ⌨️ §N.4k — SAFE BY CONSTRUCTION, and worth stating why

Compose is a **`<textarea name="body">`** with one Send button and **no keydown/keypress handler anywhere**.
A textarea **never implicitly submits**, so Enter inserts a newline and a half-typed message cannot be fired by
a stray Enter. **Enter-to-send is not the behaviour here** — which also means no Shift+Enter special-casing is
needed. Third instance of a write being safe by construction rather than by a guard (after G5's GET
date-preview and G6's no-text-input pay form); the pattern is worth naming, because it is the cheapest kind of
safety and easy to destroy accidentally.

11 interactive · **0 unnamed** · **0 `div@click`** · 0 overflow at 1440. The textarea and Send both take the
shell's `:focus-visible` ring.

### 🎨 Contrast — thread 2 → **0** (17 runs) · list **0** (16 runs)

Both failures were **the guest's own message bubble** — white on `--vy-grad` at **3.41** (timestamp) and
**3.50** (the message text itself, the primary content of the page). Solid `--vy-accent` → **5.18**.

**Anchors validated on this render**: white on `--vy-grad` = **3.50**, white on solid `--vy-accent` = **5.18**
— the first gate since G2 where *both* were testable, because the gradient bubble was still present to measure.

**Old-token sweep (G4/G5)**: none of the pre-remediation literals appear in either view, and no `--vy-amber`
used as text (the `DO-G6-3` tail does not touch these pages).

### ✅ VERIFIED / REFUSALS TO PRESERVE

- **Observed READ-ONLY**: messages 9 → 9, threads 5 → 5, `max(messages.created_at)` unchanged at
  `2026-08-09 07:17:59`. Nothing sent.
- **No fabricated message, reply or status**: every bubble is a real `messages` row; the transcript is
  MIRRORED off the org-relative `direction` (guest = `inbound`) rather than a stored "who" flag; there is **no
  read/delivered/seen indicator at all** — so no fake "read ✓" (#39/§N.8d). Honest empty: *"No messages yet —
  say hello below."*
- **Name-only chat** — an existing guard already refuses the prototype's presence/SLA/phone fabrications.
- **`markReadByGuest`** moves only the guest's read position, never the operator's `unread_count` (existing
  guard) — the mirror-image of #28 in the other direction.

### 🚩 LOGGED — not built

- **`DO-G7-1` · no delivery/read state for the guest.** The absence is honest (nothing is fabricated), but a
  guest cannot tell whether the property has seen their message. `message_threads` already tracks read
  positions per participant, so surfacing it is a controller/view change with real data behind it — a product
  call, not a design one.
- ~~**`DO-G7-2` · the guest cannot start a thread from this page** — only reply to existing ones (the start path
  lives on other surfaces, e.g. the arrival/expenses relays). Worth a verdict at a later gate.~~
  ⚠️ **CORRECTED IN PLACE AT G13 (2026-08-12) — THIS ENTRY WAS WRONG (§N.8b, the sixth correction).** The guest
  **can** start a thread from this page. `POST /guest/messages` → `GuestMessagesController@start`
  (`routes/web.php:370`, `throttle:12,1`) calls the certified `InboxService::startThread()` +
  `postGuestMessage()`, and `resources/views/guest/messages.blade.php:34-43` renders an **always-visible "New
  message" section** (subject + body + Send) — it is not behind the empty state, and the `@empty` copy at
  line 30 even points at it (*"start a conversation below"*). The claim was reached by looking for a start
  affordance in the conversation list rather than against what the page **renders** — precisely the
  false-completeness risk §N.13.6 warns about. **No gap here; nothing to build.**

---

## §G-8 — Guest Review · GUEST 8/32 · gate 8 of 12 · ⭐ GUEST-AUTHORED PUBLIC CONTENT + THE REAL DO10-4 SITE

`GET/POST /guest/reservation/{r}/review` · `GuestReviewController` · `guest/review.blade.php`. Auth
`auth:guest`. Walked **read-only** — no review submitted.

### ⚠️ DO10-4 — THE BACKLOG FIGURE WAS AN OVER-COUNT. The real scope is THREE sites, not ten

The register records DO10-4 as *"10 remaining star sites"*. That counted **every `★` glyph in the app**. Classified
against the code, the picture is different — a 5-star **rating row** is the only thing `<x-star-rating>` can
replace:

| kind | sites | verdict |
|---|---|---|
| **5-star rating DISPLAY** | `owner/reviews:57` · `guest/review` · **`owner/unit-detail:338`** | the real DO10-4 |
| single ★ beside a numeric average ("★ 4.8 · 12 reviews") | `booking/browse:230` · `booking/room:44` · `operator/reviews:24,40` · `owner/reviews:90,163` | **not** a star row — the number carries the value; migrating would be wrong |
| rating **picker** (radio labels / `<option>`) | `guest/review` ×2 | an INPUT, not a display |
| decorative / unrelated | `guest/trips:99,101` · `trip-card:30,33` · `owner/unit-detail:87` (a "Best performer" badge) · `units-list:122` (a cover-photo button) | not ratings at all |

**Two of the three are now migrated** (`owner/reviews` at O26, `guest/review` here). ⚠️ **The third is a live
defect**: `livewire/owner/unit-detail.blade.php:338` renders
`str_repeat('★', (int) round((float) $reviews['average']))` — **`round()`, so a 4.6 average shows FIVE filled
stars.** That is the Owner-26 bug in its original form, still shipping on an owner surface. Out of this gate's
scope (owner realm) — logged as **`DO-G8-1`**, and it is the whole of what remains of DO10-4. ✅ **CLOSED at the capstone (`ee6be74`) — see §STAR-CAPSTONE; ⚠️ and the coverage sweep there found FOUR further star sites this sentence did not know about (§N.14.1).**

### ⭐ (1) THE STAR DISPLAY — migrated

`str_repeat('★', $r).str_repeat('☆', 5-$r)` → `<x-star-rating>`. Verified on the render: `role="img"` with
**one** accessible name (`"5 out of 5 stars"`) instead of five separately-announced glyphs, glyphs
`aria-hidden`, filled-vs-empty by GLYPH (★/☆) not colour, and the component's floor (a rating can never round
up to flatter itself).

⚠️ **The component's DEFAULT COLOUR fails.** `#E8A63A` measures **2.05** on this card, against the **3:1**
bar of WCAG 1.4.11 — a star is the *visual carrier* of the rating, not decoration, even though the glyphs are
`aria-hidden` and the row is named. Passed `color="#B26A08"` (`--vy-amber`'s value — a star is a fill, so the
fill token is the right one) → **4.11**. ⚠️ Passed at the CALL SITE rather than changed in the component,
because that default is shared with the owner Reviews page on a different plate: **logged as `DO-G8-2` for
that page to measure, not guessed at from here.**

### ⭐ (2) THE STAR INPUT — already keyboard-operable, but unnamed and invisibly focused

It was already a real `<input type="radio">` group, so arrow-key operation works **by construction** — G4's
mouse-only keyboard trap does **not** recur. Three gaps fixed:
- the group had **no accessible name** (the "Your rating" eyebrow was a plain `<div>`) → a real
  `<fieldset>` + `<legend>`;
- each option announced as **"black star"**, because a `<label>`'s only content is the glyph → `aria-label`
  on each input ("5 stars" … "1 star"), glyph `aria-hidden`;
- the radio is `opacity:0; width:0; height:0`, so its focus ring was **invisible** → `:focus-visible + label`
  rings the star. (G5's lesson, third recurrence — an invisible control shows an invisible focus.)

Verified live: 5 radios in the tab order, names `5 stars…1 star`, focus received, ring rendered.

### ⚠️ §N.4k — ENTER PUBLISHED THE REVIEW, and these are the highest stakes of the pass

Measured with submits intercepted (nothing reached the server): **with a star focused, Enter submits the
form** — publishing a rating with *no review written*. The textarea below is safe by construction (G7 — a
textarea never implicitly submits), but **a radio group is not**.

Stakes, judged as the gate asks: G5's cancellation was a *request* an operator reviews; G7's message was
private to one property; **this is visible to other travellers**. Guarded with the repo's own
destructive-submit idiom (`onsubmit="return confirm(…)"`, as `admin/customers/show` uses) plus the shared
`data-guard-submit`.

### ⚠️ AN EXISTING GUARD BROKE, AND THE MARKUP GAVE WAY — not the guard

Adding `aria-label` between `value` and `checked` broke
`it('pre-selects the rating from the ?rating star deep-link…')`, which asserts the contiguous substring
`value="4" checked`. **That test is load-bearing** — it proves the deep-link pre-fill *and* that an
out-of-range `?rating=9` is NOT fabricated into a selection. The attribute order was changed so the guard's
assertion stays exact. **The guard was not weakened to fit the change.**

### ✅ `anonymize()` — RETAINED BY DESIGN, and the identifying half really is cleared

The review body is **deliberately retained**, documented in `Guest::anonymize()` and flagged for a founder
decision — it is the guest's opinion *of the property*, what other travellers rely on, and destroying it
changes published content. What IS cleared: the operator's **public reply** (it had kept the guest's first
name on a public, indexable storefront page) and the byline. ⚠️ **Verified, not assumed**:
`GuestName::reviewByline()` renders the neutral **"A guest"** for an anonymized guest — the docblock's claim
is accurate (another §N.8b "accurate", after G3). The comment even names the one-line change if policy
decides otherwise, and notes the erasure test already plants a body value. **Surfaced, not decided.**

### ✅ VERIFIED

- **Foreign-id → 404 on BOTH routes, and the existing test already asserts `Review::count() === 0`** — G6's
  presence-not-isolation gap does **not** recur here. Confirmed live too: another guest's review URL 404s.
- **The eligibility gate is on BOTH `show()` and `submit()`** — `abort_unless($this->reviews->isCompleted(...))`,
  so a future or cancelled stay cannot be reviewed, not merely cannot be *displayed*.
- **The review state is honest**: the already-reviewed card shows the REAL status — *Published* / *Not
  published* / *"with the property for review"* / *"Sent privately to the manager"* — derived, never a
  hardcoded "published"; honest empty otherwise.
- **Observed READ-ONLY**: reviews `max(created_at)` = `max(updated_at)` = the seed timestamp, **0 created
  since 2026-08-10**. ⚠️ A count I first read as "4 → 7" was my own `tail`-truncated output cutting the count
  line — substantiated before reporting, and nothing was written.
- **Contrast**: form state **0 failures / 37 runs**; display state **5 → 0 / 22 runs**. Anchor 2 validated at
  exactly 5.18 on both; anchor 1 has no call site on either page.
- **Old-token sweep (G4/G5)**: clean on this view.

### 🚩 LOGGED — not built

- ~~**`DO-G8-1` · `livewire/owner/unit-detail.blade.php:338` rounds a rating UP** (`round()`, so 4.6 → five
  filled stars) — the Owner-26 defect, still live, on an owner surface. **This is all that remains of
  DO10-4.**~~ ✅ **CLOSED at the capstone (`ee6be74`) — §STAR-CAPSTONE.** ⚠️ And *"all that remains"* was
  wrong: the sweep found **four more star sites** plus **the shared component's own failing defaults**
  (§N.14.1/§N.14.2). It was also **not colour-only** — it already emitted ★ then ☆; the defect was `round()`.
- **`DO-G8-2` · `<x-star-rating>`'s default `color: #E8A63A` measures 2.05 on a light card** (needs 3:1).
  Passed a colour at this call site; the owner Reviews call site uses the default on its own plate and must
  measure it. Its `emptyColor: #B9A99A` is also unmeasured here (a 5/5 rating renders no empty star). ✅ **BOTH defaults have since been MEASURED and BOTH FAILED** (`#E8A63A` 2.08 / `#B9A99A` 2.25) — retargeted at the `DO10-4` capstone; see §STAR-CAPSTONE and §N.14.2.
- **`DO-G8-3` · the review has no visible moderation timeline** — a guest sees the current status but not when
  it changes; and a `rejected` review shows "Not published" with no reason. A product call.

---

## §G-9 — Guest Account · GUEST 9/32 · gate 9 of 12 · 🔒 THE `anonymize()` CONVERGENCE + THE DENSEST §N.4k SURFACE

`GET /guest/account` + POSTs · `GuestAccountController` · `guest/account.blade.php`. Auth `auth:guest`.
Walked **read-only** — no form submitted, no credential entered, no account deleted.

### 🔒 THE CONVERGENCE — the delete does NOT call `anonymize()`, and that is correct

⚠️ **Premise correction.** `destroy()` never calls `anonymize()`. It calls `GuestDeletionService::schedule()`,
which stamps `scheduled_deletion_at` **14 days out** and leaves the account usable and undoable. The erase
happens later, and **the trigger has three links, not one**:

```
destroy()  →  schedule()          stamps scheduled_deletion_at = now + GRACE_DAYS(14), audited, emails the guest
           →  Schedule::command('guests:process-deletions')->dailyAt('03:00')      routes/console.php:38
           →  processDue()        cancels live reservations per policy (bounded refund #27) → $guest->anonymize()
```

⚠️ **The middle link is a cron dependency** — the same single `schedule:run` entry `CLAUDE.md` warns about
("without which the GDPR 14-day guest deletion silently never runs"). The chain is correct in code; it is
**operationally** conditional on that cron. Recorded, not re-litigated.

⚠️ **"Delete" means ANONYMIZE-IN-PLACE, never a row destroy** — PII nulled, `anonymized_at` stamped, the row
and the financial history kept. Consistent with the operator-side rule (`GuestBulkActionController`: *"DELETE
= ANONYMIZE, NEVER DESTROY"*) and with G8's retained review body. Surfaced, not decided.

### ⚠️ COVERAGE ≠ TRIGGERING — the gap this gate closes

G4 (check-in note · arrival window · phone), G7 (message bodies · sender labels) and G8 (a review's
identifying fields) each proved `anonymize()` **covers** its store — but each proved it by calling
`anonymize()` **directly**. **Nothing tied the guest-facing delete to those stores end-to-end.** The existing
sweep test asserted only `anonymized_at`, `first_name` and the cancelled reservation.

A new guard now drives the **real chain** — seed all three gates' PII (the check-in note through the
reservation, the message through `InboxService::postGuestMessage`, the operator's public reply on a review)
→ stamp the schedule → elapse the grace → `processDue()` → assert **every one** is gone.

**Mutation-proven, both links, each edit confirmed to have landed (G7):**

| mutation | result |
|---|---|
| remove the `anonymize()` **call** from the sweep (coverage intact, trigger broken) | ❌ RED — the new guard **and** the existing sweep test |
| make `destroy()` stamp nothing (`schedule()` never called) | ❌ RED — the schedule + undo guards |

**The `anonymize()` reachability thread from G4 → G7 → G8 → G9 is now CLOSED**: the stores are covered *and*
the guest-facing delete reaches them.

### ⌨️ THE 8-FORM §N.4k SWEEP — count confirmed, each judged by its stakes

| # | form | inputs | Enter | verdict |
|---|---|---|---|---|
| 0,7 | logout ×2 (shell + footer) | none | can't implicitly submit | safe by construction |
| 1 | **profile** | 5 text | submits | **correct** — saving your own profile is the form's only action |
| 2 | **password** | 3 password | submits | **acceptable** — Enter mid-form fails server validation; there is no wrong *write*, only a failed one |
| 3 | **account-delete** | 1 text (`confirm`) | submits | **safe BY DESIGN, not by construction** — the type-`DELETE` word IS the deliberate act, so Enter after typing it is the intended completion |
| 4 | waitlist-leave | none | can't | safe by construction |
| 5 | marketing toggle | none (a submit button *is* the control) | n/a | safe |
| 6 | **language** | 4 radios, **no submit button** | `onchange="this.form.submit()"` | works for mouse **and** keyboard — but see below |

⚠️ **Nothing needed a confirm guard here** — the first gate since G4 where the §N.4k sweep found no
consequential mis-fire. The delete is the interesting case: it is protected by a *typed word* rather than by
markup, which is a legitimate third pattern alongside safe-by-construction and the confirm idiom.

⚠️ **`onchange="this.form.submit()"` on a radio group has a real keyboard cost**: arrow-keying from EN to FR
fires a submit at **every intermediate option** (EN→DE, DE→ES, ES→FR = three writes). Low stakes (a language
preference, immediately correctable) and it is the design's "submits on change" intent, so it is **logged
(`DO-G9-3`), not silently redesigned**. Note also that `form.submit()` bypasses `onsubmit` handlers — worth
knowing if a guard is ever added to that form.

### ♿ TWO REAL A11Y FINDINGS

1. **The marketing-consent toggle had NO accessible name and no state.** It is a `<button type=submit>` whose
   only content is a knob `<span>`; its visible label ("Trip offers & news") sits in an unassociated sibling.
   A screen reader announced **"button"** — no name, and no way to hear whether marketing is **on or off**.
   Now `aria-label` + `aria-pressed` bound to the real stored value (§N.8d — the state is derived, not
   asserted).
2. **The language chips showed no focus ring** — `opacity:0` radios again. **Fourth recurrence** of the same
   pattern in this realm (G5 add-on toggles → G8 star input → here). `:focus-within` now rings the chip.

The honestly-disabled SMS row is a `<span>`, not a button — non-focusable, titled "Not available yet".
Correct: it is not announced as an interactive control it cannot be.

### 🎨 Contrast — 1 → **0** (59 runs, 4 skipped as too small to sample)

The failure was the **initials avatar** (`DT`) — white 20px on `--vy-grad` = **3.48**; the initials are text,
so solid `--vy-accent` → 5.18. Also moved a hand-written `#3F6338` to `--vy-green-text` (it passed at 5.65,
but a literal is how a token drifts — G4's lesson). **Anchors validated: 3.48 on `--vy-grad`, 5.18 on solid.**
Old-token sweep clean; no `--vy-amber`-as-text on this page.

### ✅ VERIFIED

- **Self-scoped, and already guarded**: an existing test proves a guest cannot edit another **even with
  another's id in the payload**. Every write targets `$this->guest()`; there is no `{id}` in any account route.
- **Observed READ-ONLY**: guests 10 → 10, `scheduled_deletion_at` 0 → 0, `anonymized_at` 0 → 0,
  `max(guests.updated_at)` unchanged. **No password was typed and no delete was performed** — every form was
  submit-intercepted before any interaction.
- **#28**: no operator internals (no risk flag, no staff note) and no other-guest data in the DOM.
- **No fabricated prefill or toggle**: the profile fields are the guest's real values; the marketing toggle
  reflects the real `marketing_consent`; the language radios are **all unchecked** because the guest's
  language is genuinely unset — an honest empty rather than a defaulted "EN".
- ⚠️ **One probe over-match caught**: my selector counted **5** language radios where the loop renders 4. The
  fifth is a **hidden** `name="language"` input inside the *profile* form, carrying the current value so a
  profile save cannot wipe it. Not a defect — verified before reporting.

### 🚩 LOGGED — not built

- **`DO-G9-1` · the erase depends on the daily cron.** `guests:process-deletions` is registered, but if
  `schedule:run` is not installed the 14-day erase never fires and the guest's PII survives indefinitely with
  no visible signal. Already in `DEPLOYMENT.md`; recorded here because this is the page that promises it.
- **`DO-G9-2` · the guest is not told what deletion keeps.** The copy promises deletion and an undo window,
  but not that financial history is retained (anonymized). Honest as far as it goes; the retention is a
  founder/legal statement to make explicit.
- **`DO-G9-3` · the language radio group submits on every arrow-key step** (see above).

---

## §G-10 — Guest Wallet + Saved · GUEST 10/32 · gate 10 of 12 · 🪙 APPEND-ONLY MONEY + A POLICY THE CODE NEVER IMPLEMENTED

`GET /guest/wallet` + 2 POSTs · `GuestWalletController` · `guest/wallet.blade.php`
`GET /guest/saved` + 5 POST **routes** · `GuestSavedController` · `guest/saved.blade.php`
Both `auth:guest`, self-scoped. Walked **read-only** — none of the 6 forms submitted.

### ⚠️ THE FINDING — three sentences of policy, two of them false

The wallet's "How credits work" card is a **policy statement**, so each line was checked against the engine:

| the page said | the engine does | verdict |
|---|---|---|
| "Credit comes from **refunds, cancelled add-ons** and any code you redeem" | only `redeemCode()` writes a credit in production. `FolioService::refundPayment()` returns money to the **payment method** and posts no wallet credit; `TYPE_ADD_ON_CANCELLED` is a constant + a display label with **no writer anywhere** | ❌ **FALSE** |
| "applied to your bookings at checkout, or to an outstanding balance above" | real — `BookingEngineController` at checkout, `applyToBooking()` here | ✅ true |
| "Most credits are valid for **12 months** from the day they land" | **no 12-month rule exists in any path.** `credit()` defaults `expiresAt` to `NULL` (never expires); a promo credit inherits the **promo's** expiry | ❌ **FALSE** |

⚠️ **The 12-month line is a prototype literal shipped as fact** — the mock reads *"Most credits are valid for
12 months from the day they land"*. **This is the same failure `GuestReservationTest` already guards on the
other side of the realm** ("derives the unlock date from ArrivalPack, never a hardcoded interval", written
after the page shipped the mock's "48h"). The pattern recurred in a different file and nobody looked.

⚠️ **The page disproved itself on screen.** Both of the demo's real credits break the stated rule — one never
expires, the other expires in **60 days** — and the page printed that 60-day date *directly above the claim*.

⚠️ **§N.8d, both directions, in one card:** the refund line **overstates** (a guest waits for credit after a
refund that will never come); the 12-month line **issues a false instruction** (spend within a year — or
write off older credit that is in fact still live). Rewritten to what the code does. The empty state repeated
the same refund promise ("check back after a refund") and was fixed with it.

### ⚠️ AN EXPIRED CREDIT LOOKED LIKE MONEY YOU HAVE

`spendableMinor()` excludes expired credit and the hero says so — but the history row rendered an expired
credit's amount in the **same confident green "+"** as a live one, with the word "expired" alone in the
label. **The figure contradicted the balance directly above it** — §N.8d's "the visual signal outlives the
text", in the money surface. Expired amounts now render muted **and struck**, like a debit.

### ✅ #17 RECONCILES — and the analogue of "pending" here is EXPIRY

The balance is **derived, never stored** (`spendableMinor()` = Σ live credits − Σ debits; there is no balance
column, and the migration's own docblock says storing one would be the drift bug). Measured live: ledger
`2500 + 1000 = 3500` → hero **€35.00**, to the cent. ⚠️ **There is no "pending" wallet state** — the honesty
axis in this realm is **EXPIRY**, and it is handled correctly: expired credit is excluded from spendable, the
hero states the exclusion, and the row now looks excluded too.

### 🎨 Contrast — Wallet 32 runs **0 fail** · Saved 24 runs **1 → 0**

⚠️ **DO-G6-2 CLOSED.** `saved.blade.php`'s `· expired` chip — `--vy-amber` (a **fill** token) as 10.5px text —
measured **4.08**, under AA. `--vy-amber-text` measures **6.15** on the same plate. Anchors validated both
runs (white on solid `--vy-accent` = 5.18).

⚠️ **The "· expired" span only renders for an EXPIRED entry, and the demo has none** — I injected the exact
markup into the real row to sample the real token on the real plate. **A conditional colour is invisible to
the probe until its condition is true**, which is a new entry for §N.8e — and it is exactly why this site
survived earlier sweeps.

Also unified the last **live** hand-written `rgb(70,120,60)` in the guest realm onto `--vy-green-text` (the
shell declares it "the one definition"); corrected **in place** on G1's `trips.blade.php` line.

### ⚠️ PREMISE CORRECTIONS

1. **I claimed a second amber-as-text site and was wrong.** `guest/partials/trip-card.blade.php` uses
   `--vy-amber` as text — but the file **is included nowhere**. It is **DEAD CODE**: the live trip card is
   inlined in `trips.blade.php`, whose amber G1 already fixed. **DO-G6-2's "saved is the last site" was
   ACCURATE** — the second §N.8b claim in this realm to survive verification (after G3). Retracted before
   reporting; the G1 dead-partial trap, hit from the opposite side.
2. **The recon's form count is inconsistent with itself** — its table says Saved(4) while its page rows say
   wishlist 2 + waitlist 3. **Counted fresh: 5 POST routes, 4 `<form>`s in source, 3 rendered** (reconfirm is
   expired-only) + the shell's logout. `wishlist.add` has **no form on this page** — it is posted from the
   storefront, which is why the count never reconciled.

### ⌨️ THE 6-FORM §N.4k SWEEP — judged by stakes

| form | inputs | Enter | verdict |
|---|---|---|---|
| wallet **redeem** | 1 text | submits | **safe by intent** — redeeming is purely **additive**; the worst case is an invalid-code error |
| wallet **apply** | 1 number | submits | Enter *is* Apply, and the amount is what you typed — but it **spends** irreversibly. Already carries `data-guard-submit`; the field is now **named with its booking reference** (one form per owed booking) |
| wishlist **remove** · waitlist **leave** · **reconfirm** | none | can't | safe by construction |
| waitlist **join** | 2 selects, 3 dates, 1 checkbox | submits | low stakes + reversible (Leave); `required` selects make an early Enter a browser-validation stop, not a write |

⚠️ **The `opacity:0` focus-ring pattern does NOT recur** — checked both pages: 17 controls, all focusable,
all with a visible ring, **zero `opacity:0` controls**. It stays at four recurrences (G5/G8/G9).

⚠️ **The double-submit guard was applied unevenly**: Wallet's 2 forms carried `data-guard-submit`, Saved's 3
did not — and `joinWaitlist()` is a bare `create()` with **no uniqueness rule**, so a double-submit posts two
identical entries. Saved's forms now opt into the shell's existing shared guard (verified live: 3 guarded).
The partial **is** included in the shell — checked, not assumed (G1).

### ♿ SEVEN UNLABELLED CONTROLS

Five on the join-waitlist form (two selects, three date fields) and both wallet money inputs — all
**placeholder- or option-only**, which is not an accessible name. A screen-reader user met "combo box" and
"date" four times with no way to tell check-in from check-out, and an unnamed spin button with no way to tell
**which booking** it would spend on. All seven now carry `aria-label`; the apply field names its **real
booking reference**, so the name cannot drift from the money it moves.

⚠️ **My first keyboard probe reported all of them as NAMED** — it fell back to option text and the `name`
attribute. Caught by re-running with a strict accessible-name computation. §N.8e's vacuous-probe failure, on
the **keyboard** axis this time.

### ✅ VERIFIED

- **Self-scoped**: `ownedItem()`/`ownedEntry()` resolve through `guest_id`; the wallet resolves from the
  guard. Both existing isolation tests are real and mutation-shaped (they seed B's rows and assert **404 +
  absence**), not vacuous. ⚠️ **A browser isolation check here would have been vacuous** — the DB holds
  **zero** foreign wishlist/waitlist rows, so "no foreign save appeared" would have proven nothing.
- **Observed READ-ONLY**: ledger 2 → 2 rows, spendable 3500 → 3500, wishlist 1 → 1, waitlist 1 → 1, all
  timestamps at the seed date. Every submit was intercepted; fake values were cleared, never staged.
- **No fabrication**: the prototype's referral programme (invite link, "1 friend joined", "Give €25 get €25"),
  the waitlist's "**Place in line 7**", "64% freed last season", "2 villas free now", the countdown and the
  SMS alert are all **absent** — correctly refused, no fabricated position or availability.
- 1440×1000: 0 overflow, 0 console messages on both pages.

### 🚩 LOGGED — not built

- **`DO-G10-1` · credit has one production source.** Only promo redemption writes a credit; refund-to-credit
  and add-on-cancelled-to-credit have **no writer** (`TYPE_REFUND` / `TYPE_ADD_ON_CANCELLED` are labels only).
  The copy now says so. Whether a refund should be able to land as credit is a **product decision**.
- **`DO-G10-2` · a waitlist entry can expire before the dates it waits for.** `WAITLIST_TTL_DAYS` runs from
  the **join**, not from the requested stay: the demo entry waits for **20–23 Sep** and lapses **8 Sep**.
  Visible on the live page. Backend/product, not presentation.
- **`DO-G10-3` · `joinWaitlist()` has no uniqueness rule** — server-side dedupe (the client guard is
  defence-in-depth, never a replacement).
- **`DO-G10-4` · `guest/partials/trip-card.blade.php` is dead code** that duplicates the live trip card — a
  drift trap: a fix applied to one will silently not reach the other. Delete or revive.
- **`DO-G10-5` · the wishlist card is name-only.** The prototype shows rating, review count, nightly rate and
  "Book now" per saved room. Rating is available (`ReviewRating`) and rate data exists — a real build, not a
  refusal. The prototype's **share-by-email / shared-link wishlist** has no backend at all.

---

## §G-11 — Guest AUTH chapter · GUEST 11/32 · gate 11 of 12 · 🔑 A DIFFERENT LAYOUT + THE SIGNED LINK

`guest/auth/layout.blade.php` — **NOT** `<x-guest.shell>`. Walked **read-only**: no credential typed, no
account created, no session started, no verification state changed.

### ⚠️ PREMISE CORRECTIONS (three)

1. **The chapter is NINE views, not three.** `login` · `claim` · `sent` · `set-password` · `claim-invalid` ·
   `forgot-password` · `reset-password` · **`marketing-unsubscribe`** · **`guest/home`** — the last two also
   render on this layout, which the recon treated as elsewhere.
2. **There is no separate "Verify Email" route in the guest realm.** `guest.claim.verify` **is** the email
   verification *and* the account set-up — one signed route, two jobs. (`account/email/verify` exists only in
   the owner and admin realms.)
3. **There is no `GuestAuthController`** — login is `GuestLoginController`, the claim is `GuestClaimController`.

### 🎨 THE SHELL'S FIXES DID NOT CARRY — re-established on this layout's own measurements

⚠️ This layout declares **none** of the realm's text tokens: `--vy-accent-text`, `--vy-amber-text` and
`--vy-green-text` are all **undefined** here. Both of the shell's headline G1/G9 fixes were present unfixed:
`.vy-brand-mark` and `.vy-btn` still painted white text on `--vy-grad`.

| run | before | after |
|---|---|---|
| **"Sign in" button label**, white 14px on `--vy-grad` | **3.88 ❌** | **5.18 ✅** |
| brand mark "V", white 21px on `--vy-grad` | 3.76 (passed only as *large* text) | 5.18 ✅ |

**11 → 12 runs, 1 → 0 failures.** Anchors validated **on this layout's render**, both at exactly **5.18**.

⚠️ **A fix was deliberately NOT copied across.** On the shell, `--vy-accent` as link text failed and needed
`--vy-accent-text`. Here the card plate is lighter (`#FDF5EF` vs the shell's), and the same token measures
**4.80 / 4.87 / 5.11** — it passes. No `*-text` token was introduced. *A translucent token has no fixed
contrast; it moves with the plate* (G2) — which cuts **both** ways, so the measurement decides, not the
precedent.

⚠️ **Conditional states forced (G10):** `.vy-error` and `.vy-notice` never render on a first page load, so both
were injected and sampled — the error text reads **13.14**. Honest states measured, not assumed.

### 🔒 THE SIGNED LINK — verified live AND defended

`claim.verify` / `claim.complete` / both `unsubscribe` routes carry Laravel's `signed` middleware. Every
rejection mode confirmed **against the running app**, not only in tests:

| case | live result |
|---|---|
| valid signature | 200 |
| **expired** | **403** |
| **tampered** | **403** |
| **missing entirely** | **403** |

The binding is layered: the URL carries `{hash}` = `sha1(email)`, `hash_equals`-compared in
`resolveClaimable()`, so a link dies if the email changes — **and the hash is inside the signed payload**, so
it cannot be swapped without breaking the signature. The row must also still be unclaimed and still have a
booking.

⚠️ **THE GAP THIS GATE CLOSES.** The suite covered a **tampered** signature and a **wrong hash** — but never
**expiry**. `sendClaimLink()` mints the link with `temporarySignedRoute(..., now()->addHour())` and the "check
your inbox" page tells the guest *"It expires in an hour."* **Nothing proved either half.** Swapping
`temporarySignedRoute` for a permanent `signedRoute` left **every test green** — while turning every claim
link in every old inbox into a **permanent account-takeover key** and the page's sentence into a lie. A
signed claim link is a **credential in a URL**; its only limit is time, and the limit was undefended.

The new guard drives the **real emailed URL** across the boundary: 200 at 59 minutes, **403 at 61**, on both
GET and POST, and asserts the account was never claimed. A second guard refuses the **naked** URL.

**Mutation-proven:**

| mutation | result |
|---|---|
| `temporarySignedRoute` → permanent `signedRoute` | ❌ **RED** (the previously-undefended regression) |
| strip `signed` from the two **claim** routes | ❌ RED ×3 (both new guards + the tampered test) |
| strip `signed` from both **unsubscribe** routes | ❌ RED (its own test) |

⚠️ **AN O32 CATCH — a mutation that PASSED and proved nothing.** My first attempt to strip the middleware used
a regex for `->middleware(['signed'…])`; the real code is `->middleware('signed')`. The fallback pattern then
removed the **first two** quoted occurrences in the file — **login-approval and unsubscribe**, not the claim
routes. The edit "landed" (2 occurrences), the run reported PASSED, and it demonstrated **nothing**. Worse,
it silently disarmed unsubscribe's guard in a file I wasn't running. Caught only by asking *what did that
mutation actually remove?* — then redone by line number, which turned three tests red. **G7's silent mutation
and O32's passing mutation are the same failure seen from two sides: verify the edit landed ON THE TARGET,
not merely that something changed.**

### 🚩 `DO-G11-1` — THE MOST LIKELY FAILURE AT THIS BOUNDARY SHOWS AN OPERATOR PERMISSIONS PAGE

An **expired** claim link — the ordinary case, an hour after the email — is rejected by the `signed`
middleware, so the guest never reaches `claim-invalid.blade.php`. They get the shared 403:

> **"You don't have access to this** — Your role doesn't include this area. If you need it, ask an
> administrator on your team to grant the permission." · *Back to your trips*

A guest has no role, no team and no administrator; the message names a permissions problem when the truth is
a lapsed link, and offers a signed-out guest a link to a page they cannot open. ⚠️ **The honest page already
exists** — *"This link isn't valid. It may have expired or already been used. Request a fresh link."* — but
only the *rarer* paths reach it (valid signature, unclaimable row). **Surfaced, not fixed:** the repair
belongs in exception rendering (render the guest page for an `InvalidSignatureException` on a guest-realm
route), which is wiring, not presentation, and this gate is presentation-only. The 403 copy itself must
**not** be edited — it is correct for the operator realm that shares it.

⚠️ **§N.8b — the docblock asserted the opposite** and is **corrected in place**: `claim-invalid.blade.php`
claimed to be "the SAME page for any bad/expired/spent/mismatched link". It is not reached for expired,
tampered or unsigned links at all.

### ✅ HONESTY — the prototypes' fabrications are all refused

The Login/Signup prototypes carry **Google + Apple social sign-in**, a **"Sign up" tab** (open self-signup),
"Keep me signed in", "Continue as guest", a password-strength meter and Terms/Privacy consent; the Signup
prototype promises **"{{ creditLabel }} welcome credit — lands in your wallet the moment you join"**. **None
appear live** — grep-confirmed. The welcome credit is the sharper refusal after §G-10: the only production
credit writer is promo redemption, so a join-time credit has **no writer at all**.

⚠️ **And the expiry literal was already caught once.** The Verify-Email prototype says *"verification links
last 24 hours"* twice; `sendClaimLink()`'s own comment records the refusal — *"the email copy states this
REAL expiry (never a fabricated 24h)"*. **No live page restates 24h**; the "sent" page says **"It expires in
an hour"**, matching `now()->addHour()` exactly. That accurate sentence is now **defended by the new guard**
rather than merely true today.

Other honesty checks: the claim response is **neutral either way** (no enumeration, existing test);
`unsubscribe` **does not act on GET** (existing test — a prefetching scanner cannot opt a guest out); the
set-password page's *"Your email is confirmed"* is substantively accurate (holding the emailed link **is** the
proof of control) and `claim()` stamps `email_verified_at` on submit — **not** a fabricated state.

### ⌨️ KEYBOARD + §N.4k — clean

**Every** control across all nine surfaces is focusable, ringed, and carries a **real** accessible name via
`<label>` — computed strictly (G10's lesson: no fallback to `name`, placeholder or option text). **Zero
unnamed controls**, in contrast to G10's seven. No `opacity:0` control — the pattern stays at four.

§N.4k judged by stakes: login (email+password), claim (email), forgot (email) and set-password (two
passwords) all implicitly submit — and in every case **Enter is the form's only action**, so it is the
intended completion; a premature Enter fails server validation rather than writing anything wrong (G9's
password-form pattern). `unsubscribe` has **no text input** — safe by construction.

### 🔐 The signed token does not leak

It appears in exactly one place: the set-password form's `action`, which is **required** — the POST must carry
the same signature for the middleware to re-validate it. It is never rendered as visible text, never in an
`<a href>`, never in an image request that would leak it via `Referer`.

### 🚩 LOGGED — not built

- **`DO-G11-1`** · the expired-link 403 shows operator role/permission copy to a guest (above).
- **`DO-G11-2`** · the POST login route's name is `guest.` (an empty suffix) — cosmetic, but a route name that
  reads as a typo is a trap for the next `route()` call.
- **`DO-G11-3`** · the prototypes' social sign-in, open self-signup and welcome credit remain **ratified
  refusals** (booking-tied claim only) — recorded so they are not "fixed" later.

---

## §G-12 — Public Storefront · GUEST 12/32 · gate 12 of 12 · 🏁 **THE GUEST-REALM PAGE WALK IS COMPLETE**

`GET /stay/{organization}` + `/search` · `StorefrontLandingController`
`GET /book/{property}` + `/search` `/rooms/{roomType}` `/checkout` `/confirmation` + POSTs · `BookingEngineController`
Layout: **`<x-public.shell>`** — a **third** layout. Fully public. Walked **read-only**: no booking, quote or lead fired.

### ⚠️ PREMISE CORRECTIONS (four)

1. **There are TWO storefronts, not one.** An **org-level** multi-property landing (`/stay/{organization}`) and
   a **per-property** booking engine (`/book/{property}`), with separate controllers and separate resolvers.
2. **The scope is NOT the domain.** There is no `Route::domain()` anywhere. The tenant is resolved from the
   **route slug** by `ResolvePublicOrganization` / `ResolvePublicProperty`, which set **real tenant context**
   (never a guard bypass) and re-load scoped — so isolation is the certified `OrganizationScope`, not a
   bespoke public query. A **suspended or cancelled** org's storefront is genuinely 404.
3. **It is a THIRD layout** (`<x-public.shell>`), and — as at G11 — it declares **none** of the realm's text
   tokens and painted white text on `--vy-grad`.
4. **`/` is not the storefront.** The root route returns the marketing `welcome` view.

### ⚠️ THE GATE-4 RECORD WAS STALE — corrected IN PLACE

The 2026-07-25 row called Browse/Storefront an *"INTENTIONAL DIVERGENCE (scope): per-property vs the
prototype's multi-property brand collection with fabricated regions/featured-villas/vibes/review-walls."*

**Re-walked fresh against `Guest Browse.html`: every element it called a scope divergence now EXISTS and is
REAL** — the multi-property collection, towns with per-town from-prices, featured stays, "browse by vibe"
(shipped as **Browse by what matters**, real amenities only), the review wall, the closing CTA. The row
predates founder decision D1, which built the org landing. **The G1 pattern exactly: a GATE record that
UNDERSTATES, because the build moved past it.** The prototype's fabrications stayed refused — "4.8 across
**369 stays**" renders as the REAL **4.6 across 7**, and the badge hides entirely with no reviews. Only the
header's `{{ wishCount }} saved` counter is absent (it needs a guest session; a visitor correctly sees "Sign
in"). Verdict: **AT PARITY on real data — not a scope divergence.**

### 🔒 THE SELL SCOPE — the gap this gate closes

Tenant isolation was already well covered and **non-vacuously**: the landing test seeds a whole second org
with a distinctive town, room and review and asserts all three absent; the browse and engine tests do the
same for inventory. Those are real guards.

⚠️ **But `properties.status` was defended by NOTHING.** The enum has exactly two values, and `inactive` is how
an operator takes a property **off sale** — renovation, a lost contract, off-boarding. Two independent places
enforce it: the landing's `where('status','active')`, and `ResolvePublicProperty`, which re-checks it *before*
setting tenant context. **No test on either storefront had ever seeded an inactive property**, so deleting
either line left the entire suite green — while re-publishing a withdrawn property's public listing **and its
whole booking funnel**: browse · room · search · checkout · **and the quote write**, because every one of
those routes hangs off that single middleware line.

Two new guards drive it: the de-listed property vanishes from the landing (town, room and from-price), and
**every** public booking route 404s — asserted live-then-withdrawn, so it can never pass on a bad slug.

| mutation | result |
|---|---|
| drop the landing's `where('status','active')` | ❌ **RED** |
| drop `ResolvePublicProperty`'s active check | ❌ **RED** (the whole funnel) |

**The tenant scope was covered; the SELL scope was not.**

### 🎨 Contrast — landing **19 → 0** (85 runs) · browse **3 → 0** (26 runs)

Same family as G11, on a third layout: white text on `--vy-grad` (View buttons 3.74 · Search 3.79/3.90 ·
Browse-all-stays 3.58 · review avatars 3.72–3.79 · brand mark), plus town labels over an under-weight photo
scrim (2.58–3.19), hero sub-copy on the gradient (3.12/3.39), 14px stars in `--vy-accent-3` (3.04), a section
lede (4.27) and the shared footer (4.15). Fixed by moving white-on-gradient to the **solid** accent,
strengthening the scrims, and darkening four text tokens. Anchors validated on **this** layout.

⚠️ **A PROBE FAILURE I CAUSED, AND ALMOST PUBLISHED — 34 false failures.** My freeze style used
`animation: none !important`, which **reverts a `.vy-fade` element to its base `opacity: 0`**. The plate then
showed the white page through every animated card: 34 runs scored white-on-`#FFFFFF` at **exactly 1.00**,
including a **dark** panel that reads as light-on-dark in reality. That is the *same* stuck-`opacity:0`
mechanism as the Owner Statement's `vy-swap-next` bug — reproduced by my own instrument. Caught by comparing
the plate against a normal render before believing it (§N.8e: *when the probe breaks, publish NOTHING*).
The freeze is now `animation-duration:0.001s + fill-mode:forwards`, which collapses animations to their **end**
state, and every run is now preceded by an assertion that **0** animated elements remain transparent.
**New standing rule: freeze by completing animations, never by removing them — and prove the freeze.**

⚠️ Also: the browser's screenshot capability degraded mid-gate (every capture timing out, including pages that
worked minutes earlier). A session restart fixed it. Worth knowing before blaming a page.

### ✅ NO FABRICATED CONTENT · #17 ON PRICES

Every figure traced to its engine: per-town **from-price** = the cheapest forward nightly in `rate_calendar`
(an unpriced room **omits** its from-price rather than inventing one — an existing test); the rating and count
are real derived aggregates; amenity chips list only amenities actually present with real counts; reviews are
real published rows, first-name-only (#28). The browse page's honest empty state is **"Pick dates above to see
availability & prices"** — no price is shown before a real quote exists.

⚠️ **The one policy claim with a number checks out.** *"Free cancellation — Full refund up to 2 days before
arrival"* is derived from the real `PropertyPolicy.cancellation.free_until_days`, and its docblock's boast
("never a fabricated 14 days") is **accurate** — the fourth §N.8b claim this pass to survive verification.
⚠️ **But it takes the MAX across the org** and states it unqualified — see `DO-G12-1`.

### ⭐ THE OWNER-26 STAR BUG IS STILL LIVE — found from the storefront

`ReviewList::stars_off` returned **`str_repeat('★', …)`** — the **FILLED** glyph. `owner/reviews.blade.php:123`
renders `stars_on` then `stars_off`, so a 4★ review printed **five identical stars** distinguished **only by
colour** (`#E8A63A` vs `#D9C8BC`) — **WCAG 1.4.1**, and in greyscale a 4 reads as a 5. That is precisely the
Owner-26 defect `<x-star-rating>` was built to end; **G8 migrated the hero (line 57) and the list rows one
section below kept the old pair.** No test referenced either field, so nothing held it. Fixed at the ONE
definition (`☆`), which is why the misnamed field mattered: *the name promised what the value didn't deliver*.

On the storefront itself, the landing's review row printed **filled stars only** with no accessible name — no
5-star reference and nothing for a screen reader. It now renders the full `stars` string with
`role="img"` + `aria-label="N out of 5 stars"`, matching the component's contract.

### ⌨️ KEYBOARD — the invisible-focus family's FIFTH appearance, by a SECOND mechanism

`browse.blade.php` set **`.vy2-field input:focus { outline: none; }`** with **nothing in its place** — a
keyboard user tabbing the date and guest fields had **no focus indicator at all** (WCAG 2.4.7). G5/G8/G9 were
`opacity:0` controls; this is an explicit outline removal. Same defect to the user, different cause — so the
pattern is now **5 occurrences across 2 mechanisms** and is firmly a §N.8f shared-fix candidate.

⚠️ **My first probe reported it as a possible artifact and I nearly dismissed it** — `el.focus()` does not
trigger `:focus-visible`. Re-checked by pressing **real Tab**: `matchesFocusVisible: true`, `outline-style:
none`. Confirmed, not an artifact.

Four browse fields (`check_in`, `check_out`, `adults`, `children`) also had **no accessible name** — their
visible keys are unassociated `<span>`s. All four now carry `aria-label`. The landing's own search fields were
already labelled (checked, not assumed).

### ✅ READ-ONLY + §N.4k

Both search forms are **GET** — safe by construction, and a search Enter is the intended action. Every POST
(`quote`, `book`, `reserve`) was submit-intercepted and **never fired**: reservations 42 → 42, `booking_quotes`
**0 → 0**, no lead, timestamps unchanged. No operator internals in either DOM (no cost, margin, commission,
occupancy or owner payout). 1440×1000: **0 overflow**, 0 console errors.

### 🚩 LOGGED — not built

- **`DO-G12-1` · the free-cancellation promise is the org's MOST GENEROUS window.** It takes `max(free_until_days)`
  across all properties and states it unqualified. Both demo properties are 2 days, so it is accurate today —
  but if one property were non-refundable, the landing would promise every visitor a refund window that
  property does not honour (§N.8d, on a money-affecting policy). Either qualify the copy or derive it
  per-property — a product/copy decision, surfaced not taken.
- **`DO-G12-2` · room photography is still placeholder art** (the GATE-4 "P3-2" deferral, re-confirmed live:
  town tiles and room banners are CSS gradients). The scrims are now weighted for text over a real photo too.
- **`DO-G12-3` · `DO10-4` is larger than recorded** — the ★ migration is not only hand-rolled *colour*: the
  owner Reviews list carried a real WCAG failure through a misnamed shared field. Re-audit the remaining
  call sites for the same shape, not just the tone.

---

# 🏁 THE GUEST REALM IS CLOSED — 12 gates, 32 pages, G1 → G12

Operator 42/42 · Owner 32/32 · **Guest 12 gates covering the 32 prototypes** — the three built realms plus the
public storefront have now been walked page by page.

**What this realm taught, that the other two did not:** the isolation model **inverted three times** — from
"scope to the guest" (G1–G10), to "there is no guest, the signature IS the authorization" (G11), to "there is
no guest, the TENANT is the scope, and the second scope is whether the thing is even for sale" (G12). Each
inversion hid a different undefended guard, and in **each** case the certified scope was well covered while the
*adjacent* one was not: `anonymize()` was covered but untriggered (G9); the signature was checked but its
**lifetime** was not (G11); the tenant was isolated but the **sell** scope was not (G12).

**Still open after this gate:**
- **G13 — the 3 no-route prototypes** (Guest Referral Landing · and the two other unrouted designs) — each is
  a feature that does not exist, so each is a product decision, not a build.
- **The PDF-sweep gate** — 11 templates + `PdfService`'s 2.70 watermark, never contrast-measured.
- **`DO-INV-4`** — the email-contrast sweep across the 15 Mailables.
- **`DO10-4`** — the ★ migration, now known to be a correctness issue and not only cosmetic (above).
- **The BACKEND phase** — the `DO-*` register (now `DO-G1-*` … `DO-G12-3`), the 6 operator a11y gates, the
  3 founder decisions, and the deferred-capability refusal sweep.

---

## §PDF-SWEEP — 17 templates + `PdfService` · the first CROSS-REALM sweep · ⚠️ THE ONE PASS ALLOWED TO TOUCH `PdfService`

### ⚠️ THREE PREMISE CORRECTIONS

1. **There are 17 PDF templates, not 11** — guest 6 (`arrival`, `folio`, `guest-statement`, `guest-vat-invoice`,
   `guest-credit-note`, `guest-refund-receipt`) · operator 3 (`operator-report`, `operator-schedule`,
   `maintenance-costs`) · owner 8 (`owner-statement`, `-statement-history`, `-booking-summary`,
   `-booking-confirmation`, `-remittance`, `-agreement`, `-agreement-amendment`, `-annual-tax`).
2. **`app/Support/Pdf/PdfService.php` does not exist.** The real file is **`app/Services/PdfService.php`**;
   `app/Support/Pdf/` holds the twelve `*Document` data-builders.
3. **⚠️ THE "WATERMARK" IS NOT A WATERMARK.** It is the **export-audit stamp** — *"Downloaded by {name} on
   {date} · Confidential"* — the same identity `ExportAudit` writes to the download log, pinned as a footer
   line in the bottom **margin** (`position:fixed;bottom:4px`). It is **not** a faint brand mark lying behind
   content. That dissolves the gate's central question, below.

### 🔑 THE WATERMARK VERDICT — **INFORMATIONAL, and FIXABLE. Not a founder decision.**

| test | answer |
|---|---|
| Does it convey information a reader must read? | **YES** — who downloaded this document, and that it is confidential. It is the fact that makes the Team security note's claim true. |
| Is it a faint mark *behind* content? | **NO** — it sits in the bottom margin, drawn over empty paper. |
| Would darkening it defeat its purpose? | **NO.** There is no content for it to compete with. "Unobtrusive" is carried by its size (7.5px) and position, not by its faintness. |

**Measured, not assumed** — `#a99b8e` on real ink: **2.70** against paper, and **2.41** where it lands on a
warm footer band (the pixel plate-probe on the rendered statement). ⚠️ **The recorded 2.70 was the optimistic
case**; corrected here. Fixed to `#5f5853` — the same grey the 17 templates use for secondary text — which
measures **6.98** on paper. It stays 7.5px, centred, in the margin: still unobtrusive, now legible.
**An audit stamp a reader cannot read is a cosmetic feature.**

⚠️ **What was NOT fixed, and why (`DO-PDF-2`).** Because it is `position:fixed`, its plate is
**non-deterministic** — it prints over whatever the page happens to end with, so on a document whose last page
ends in the dark net-payable band its contrast is unknown and could be far worse than any measured figure. The
structural fix is to move the stamp into each template's own footer flow, where its plate is known. That is a
per-template layout change across 17 documents, not a service tweak — **surfaced, not forced**.

### 📄 THE 17-TEMPLATE SWEEP — 15 rendered from REAL routes with REAL data

Each template was driven through its **actual export route** as the correct role (owner / operator / guest),
capturing the view's real data at compose time and re-rendering the exact HTML dompdf consumes.
**`guest-credit-note` and `guest-refund-receipt` 404 — the demo has no refund**, so they are refund-only
documents that cannot render; they were fixed by literal (both carry the same two greys) but **not
render-verified**, and that is stated rather than glossed.

| ink | where | before | after |
|---|---|---|---|
| **`#8A8079`** (9–13px) | 10 templates, 40 occurrences | **3.86** on paper · **3.42–3.66** on the warm plates | **6.98 / ~6.2** |
| **`#A69A92`** (8.5–10px) | 9 templates, 11 occurrences | **2.74** on paper · **2.43** on the warm plates | **5.79 / ~5.16** |
| **`#A99B8E`** (7.5px, the stamp) | **all 15** | **2.70 / 2.41** | **6.98** |

**Result: 224 distinct inks across 15 templates → 0 failures.**

⚠️ **§N.8f CONFIRMED BY GREP, AND §G-3's CLAIM VERIFIED.** §G-3 fixed `#8A8079` / `#A69A92` **in the arrival
template only** — and `arrival` really is clean (**0 occurrences**, checked not assumed: the fifth §N.8b claim
this pass to survive verification). The other sixteen kept both. Fixed with **one mapping applied across every
template**, onto greys **already present in these same documents**, so the palette *shrinks* (four greys → two)
and no new colour enters. Two tiers were preserved so the visual hierarchy survives. The old values are
**grepped to zero** in `resources/views/pdfs/`.

⚠️ **The same two literals still live in the EMAIL templates** (`emails/*.blade.php`, `auth/login-approval*`).
That is `DO-INV-4`'s territory, deliberately untouched — but it is now known that the email sweep will find
**these exact two greys**, not a fresh set.

### ⚫ GREYSCALE — safe, and the claim was verified

A PDF is printed black-and-white, so a colour-only distinction dies. Checked: the remittance paid-pill prints
`{{ $status_label }}` (a **word**, not just a green wash); the schedule's conflict marker prints a count and
the word "conflict". `operator-schedule` even carries the comment *"Every state is written out, so this prints
correctly in black and white"* — **accurate**.

### 🔒 THE GATING AND RECONCILIATION AFTER TOUCHING THE SHARED SERVICE

| re-proof | result |
|---|---|
| **#17** — corrupt the folio PDF's balance by one cent | ❌ **RED ×3** (reconciles-to-the-cent + two others) |
| **#33** — remove the door-code window gate | ⚠️ **GREEN — see below** |
| full suite (a shared service touches both realms) | 1888 tests, 9 non-green (the documented set) |

### ⚠️ THE FINDING — A GUARD LAYER NO TEST COULD SEE, AND A MUTATION THAT WAS RED FOR THE WRONG REASON

Removing `ArrivalPack`'s data-level gate (`'door_code' => $released ? $code : null` → `$code`) left **25 tests
green** across `GuestArrivalCheckinTest`, `GuestReservationTest` and `GuestTripsTest` — **including the one
named *"withholds the door code until the arrival window opens (#33)"***.

**The app is NOT leaking.** `guest/arrival.blade.php` *and* `pdfs/arrival.blade.php` each independently gate on
`@if ($pack['released'])`, so the view refuses to print what the data would now hand it. That belt-and-braces
is precisely **why the data layer was invisible to the suite** — the second layer masks the first. Nothing is
wrong today; what was wrong is that the second layer could be removed, or a new surface could read
`$pack['door_code']` trusting it to be safe, and **no test would notice**. A new guard now asserts the data
**contract** with no view in the way, mutation-proven on both the door code and the wifi.

⚠️ **AND THE NEAR-MISS THAT FOUND IT.** My first attempt at this mutation went **RED — for the wrong reason**:
bash escaping leaked a backslash into `'door_code' => \$code`, corrupting the file. I nearly recorded "#33
re-proven ✅" on a syntax error. Re-running it cleanly (via a heredoc, with `php -l` confirming valid syntax
after the edit) turned it **green**, which is what exposed the real gap. **G11's rule needs a second half:
verify the edit landed on the target — *and that the RED came from the change, not from breaking the file*.
A mutation that reds for the wrong reason is as worthless as one that passes.**

### 🚩 LOGGED

- **`DO-PDF-1`** · there is **no shared PDF stylesheet** — 17 templates each carry their own `<style>` with
  duplicated literals. This sweep normalised the values, but the *structure* that let them drift is intact;
  the next colour change will need the same 17-file sweep. A shared partial is the real fix.
- **`DO-PDF-2`** · the audit stamp's plate is **non-deterministic** (`position:fixed` over arbitrary content) —
  above.
- **`DO-PDF-3`** · `guest-credit-note` and `guest-refund-receipt` are **unreachable in demo data** (no refund
  exists), so neither was render-verified here, and neither is covered by a rendering test. A seeded refund
  would light up both documents and their tests.

---

## §G-13 — THE THREE NO-ROUTE PROTOTYPES · **VERDICTS, NOT A GATE** (2026-08-12, HEAD `e869d5d`)

⚠️ **A VERDICT PASS.** `Guest Help`, `Guest Documents` and `Guest Referral Landing` have **no live route**, so
there is nothing to render, diff, measure or mutate — the gate machinery does not apply and was not run. Each was
**decoded fresh**, **verified against the engine** (does a model / table / route / service / **writer** exist?),
given a **verdict**, and **surfaced as a founder decision**. **No product code was written.**

**The full reasoning, the decoded feature lists, the grep citations and the per-path costs live in
`docs/design/G13-VERDICTS.md`.** This section is the register-facing summary.

**This closes the guest realm's prototype accounting:** 32 prototypes → **12 gates** (§G-1…§G-12) + **3 verdicts**.

### The three verdicts

| prototype | the verified backend reality | verdict |
|---|---|---|
| **Guest Help** (`DO-G13-1`) | 🟢 **A support destination ALREADY EXISTS** — `guest/account.blade.php:179-185` renders *"Help & support · Message your property directly"* → `guest.messages` (a 2026-08-07 audit fix, `82506bb`). 🟢 The relay is **REAL and reachable** — `guest.messages.start` (see the `DO-G7-2` retraction). 🟢 The FAQ **pattern** is proven owner-side — `HelpKnowledgeBase` (curated: keywords/question/answer/route/label) + `HelpRetriever` + the grounded `AskVyrobelService`. ❌ **No guest KB, no `/guest/help` route** (nav is `Trips · Payments · Wallet · Messages · Saved · Account`). ❌ **No SLA store, no first-response metric, no support hours**; `properties` has **no phone and no support-email column** (only `brand_reply_to`, an email reply-to setting); no on-call roster; WhatsApp is account-gated. | **BUILD** the FAQ centre — ⚠️ **this REOPENS the ratified 2026-08-07 refusal** (*"NOT a help centre: its content would have to be invented"*), whose premise the owner KB has since **falsified**: curated, real, screen-linked FAQ content is exactly what `HelpKnowledgeBase` is. Flagged as a founder call, not taken. · **DON'T-BUILD (#39)** the *"replies under an hour"* pill, the **Typical reply** tile, the **phone / WhatsApp / hours** and the **24/7 urgent line** — ⚠️ the last is *dangerous*, not merely dishonest. The same refusals are already ratified on `guest/messages.blade.php:3-4` and in `OwnerHelp.php:28-31`. ⚠️ **RULED AND ACTED ON 2026-08-12 — see §GUEST-FAQ.** The founder **split** this verdict: the **FAQ centre is BUILT** (`23bf335`, `GET /guest/help`, + a `Help` nav entry, so the nav list quoted in this row is now out of date), and the **support half was SCOPED, not refused** (§N.15.6). Every refusal named here was honoured by the build. |
| **Guest Documents** (`DO-G13-2`) | 🟢 **All five money PDFs are REAL** and #28-isolated (`GuestDocumentController`, resolved through `$this->guest()->reservations()`), plus the arrival PDF. 🟢 **All three money figures already exist** in ONE shared reader — `GuestPayments::overview()` (`total_charged`/`total_refunded`/`total_balance` across all the guest's bookings, #17) — and **two already render on `/guest/payments`**. ⚠️ 🟢 **THE ROLLUP ALREADY EXISTS AND WAS RATIFIED** as a location divergence: Account *"Receipts & documents"* (`account.blade.php:175`) → `/guest/payments`'s *"Receipts & invoices"* card (`payments.blade.php:112-121`) → the receipt page → the 5 PDFs. **Three hops, but reachable.** ❌ **No ZIP capability anywhere** (`grep -rln "ZipArchive" app` → nothing). 🟡 No guest email-all writer; the owner precedent is real (`OwnerReportMail`). ❌ **"kept on file for 12 months" is FALSE** — PDFs are generated **on demand**; nothing is stored, no retention window, no purge job. | **DEFER** the hub — ⚠️ **downgraded from an initial BUILD read once the ratified divergence was verified**; it is a convenience (3 hops → 1 + a cross-booking list) over an already-reachable function, and building it **reopens a ratified row**. · **DON'T-BUILD** the **ZIP** (omit the control, don't disable it) and the **12-month retention claim** (never ship the line; don't soften it either) · **DEFER** *"Email me all documents"*. 🟢 **Cheap win regardless of the hub decision:** label the document kinds on the Payments rows — a guest cannot currently tell their **VAT invoice** lives behind a button labelled *Receipt*. |
| **Guest Referral Landing** (`DO-G13-3`) | ❌ **FIVE subsystems absent.** `TYPE_REFERRAL` is an **enum slot and a display label** — never written. The ONE production credit writer is `GuestWalletService::redeemCode()` (posts `TYPE_PROMO`). No referral-code entity (`promo_codes` is org-wide, no referrer column) · no `referred_by` attribution on `guests` · **no open guest self-signup** (booking-tied claim only — a **ratified refusal**, `DO-G11-3`) · no first-stay reward trigger · no 90-day expiry rule. | **DON'T-BUILD as drawn (#39).** The page would promise a stranger money the engine cannot pay: a code that redeems nothing, a referrer never paid, a countdown against a rule that does not exist, and a "create your account" button with no account-creation path behind it. **The same refusal is already ratified twice** — `guest/wallet.blade.php:4,102` (test-defended at `GuestWalletTest.php:175`) and `DO-G11-3`. |

### ⚠️ The `DO-INV-3` tie — RESOLVED: **DEPENDS ON, does not RESOLVE**

`GUEST-RECON.md` §4 read Guest Help as *"Same gap, second realm. Likely one fix serving both."* **Half right.**
`DO-INV-3` is a **public** problem (an invite recipient has **no account**, so an authed `/owner/help` bounces to
login); Guest Help would be an **authed** page behind `auth:guest`. **Building `/guest/help` gives the invite
email nowhere new to point.**

They share ONE **root cause** — *no support contact is configured anywhere in the product*. **That root cause is
VERIFIED, not assumed:** `.env.example:72` is still `MAIL_FROM_ADDRESS="hello@example.com"`; `config/*.php`
declares no support URL or address; `properties` carries no phone/support-email column. It blocks Guest Help's
**contact sidebar** and **all of `DO-INV-3`**. ⚠️ **`DO-INV-3` therefore stays OPEN regardless of the Guest Help
decision** — and conversely, configuring a real support contact is the single change that serves both.

### 🚩 The premise corrections this pass produced (§N.8b)

- ✅ **`DO-INV-3`'s claim SURVIVES verification** — recorded as plainly as the failures (the sixth surviving claim).
- ❌ **`DO-G7-2` was WRONG and is RETRACTED in place** (§G-7 + the register) — the guest **can** start a thread.
- ⚠️ **The recon's Guest-Help/`DO-INV-3` reading is QUALIFIED** (above).
- ⚠️ **TWO RATIFIED `DIVERGENCE-REGISTER.md` ROWS THE RECON DID NOT CONSULT — and they MOVED A VERDICT.**
  **(i) "Guest Help — ✅ RESOLVED (`82506bb`)"**: the fact **survives** (`guest/account.blade.php:184` really does
  link *"Help & support"* → the relay, so ⚠️ **a guest is NOT without a support destination** — the pass's own
  first read said they were and was wrong). But its **stated reasoning is now STALE**: *"its content would have
  to be invented"* is **falsified** by `HelpKnowledgeBase`. **(ii) "Guest Documents hub — ↺ RATIFIED (location
  divergence)"**: **survives** — the rollup is live on `/guest/payments`, ⚠️ **which downgraded the Documents
  verdict from BUILD to DEFER.** Precision added: the 5 PDFs are linked from **exactly one page**
  (`guest/receipt.blade.php:57-62`, refund + credit-note conditional), reached via
  `guest/payments.blade.php:112-121`; the arrival PDF from `guest/arrival.blade.php:10`.
  > ⚠️ **The lesson (extends §N.13.6): "no route" is not "no function."** The recon's *"no live route"* was
  > **true** — but the function had been **relocated and ratified** elsewhere. **Check `DIVERGENCE-REGISTER.md`
  > before calling a routeless prototype a gap**, or a verdict pass re-litigates a settled decision blind.
- ✅ **G10's referral finding HOLDS** — re-grepped at `e869d5d`; still six hits, still no writer.

### ⚠️ §N.8f — the duplication trap a Documents build would walk into

*"Does this booking have a refund?"* is currently derived **inside** `GuestDocumentController::ownedWithRefund()`
(`abort_unless($hasRefund, 404)`) and **again** in `guest/receipt.blade.php`'s conditional. A hub would make it
**three**. It must be extracted into ONE shared reader first — the drift that matters is a hub row that links to
a 404.

---

## §EMAIL-SWEEP — `DO-INV-4` · 20 templates + 2 auth screens · ⚠️ THE SECOND CROSS-REALM CONTRAST SWEEP (2026-08-12)

**A scoped, mechanical contrast sweep — not a gate.** No §N.4k (an email has no form to submit), no guard to
mutation-prove beyond the new regression test, no browser parity. **Contrast only**, and — proven below —
**not one character of copy, link, subject, or data changed.**

### ⚠️ Premise corrections (§N.8b)

| the record said | the code says |
|---|---|
| *"the email-contrast sweep across the **15 Mailables**"* | ⚠️ **20 email templates** (`resources/views/emails/*.blade.php`, excluding the shared `layout`) and **20 Mailables** in `app/Mail/`. The "15" was the count of templates that `@extends` the shared layout — real, but not the sweep's surface. |
| *"the offenders are the **same two greys** (`#8A8079`, `#A69A92`)"* | ⚠️ **INCOMPLETE — there are THREE**, and the missing one is **`DO-INV-4`'s own founding literal.** The item was raised at the invite-email gate against **`#A99E95`** in `emails/layout.blade.php` (measured 2.24 on the wash, *"carries the 'why you're receiving this' line on all 15 emails that extend it"*). The later "now SCOPED to the same two greys" note **narrowed the item past its own headline offender.** Closing `DO-INV-4` without `#A99E95` would have closed it without doing the thing it was raised for — so all three were swept. |
| *"still live in `emails/*` **and `auth/login-approval*`**"* | ✅ **SURVIVES** — `auth/login-approval.blade.php:11` and `auth/login-approval-confirmed.blade.php:11` each declared `--muted:#8A8079`. Both swept. ⚠️ But see the **dead-declaration** note below: only one of the two actually rendered it. |
| the PDF sweep's mapping (`#8A8079` 3.86 → `#5F5853` 6.98 · `#A69A92` 2.74 → `#6C645F` 5.79, on white paper) | ✅ **VERIFIED** — this sweep's helper reproduces all four figures to 2dp on `#FFFFFF`, which is what validates the instrument (and the guard test asserts it). |

### ⚠️ THE METHOD — declared colours on the real declared plate, NEVER `getComputedStyle`

`getComputedStyle` is the wrong instrument here and has already **false-PASSED once on an email**: at the
invite-email gate it defaulted to white and read white-on-orange as **1.00** — the mirror of the realm's
gradient false-FAIL. Emails are table-and-inline-hex documents, so the honest measurement is the one the client
actually receives:

1. **Render all 20 templates** to real HTML (a scratchpad harness feeding each Blade a permissive stub, so
   **conditional branches also render** — §N.8e: *a conditional style is invisible until FORCED*).
2. **Ancestor-walk the DOM** resolving inline `color`, inline `background-color`/`background`, and the
   `bgcolor` attribute. **Gradients are flagged, never averaged.**
3. **Two self-validating anchors** — `#2B2320` (known-passing) and `#B7ADA4` (known-failing) — plus the
   independent cross-check that the white-paper column reproduces the PDF sweep's published numbers exactly.
4. Large-text relief applied honestly (≥24px, or ≥18.66px bold); every grey here is 10.5–13.5px → **needs 4.5**.

**Result: 332 text runs measured across 20 templates. 120 failed. After the sweep: 10.**

### THE THREE GREYS — every occurrence FAILED on every email plate, so every occurrence was mapped

⚠️ **The measurement decided, not the precedent.** The PDF numbers are on white paper; the email card is
`#FFFDFB` and the wash `#F6EBE2`, so each was re-measured on its own plate first. They failed everywhere —
but by *different* margins, and the card is **worse** than paper, not better.

| grey | occurrences | templates | measured range in the EMAIL context | → mapped to | after |
|---|---|---|---|---|---|
| **`#8A8079`** | 63 | 16 | **3.19 – 3.80** (`#F2E8DF` 3.19 · `#F6EBE2` 3.29 · `#F8F1EA` 3.45 · `#FBF1E9` 3.46 · `#FCF7F2` 3.62 · card `#FFFDFB` **3.80**) | **`#5F5853`** | **5.78 – 6.88** ✅ |
| **`#A69A92`** | 7 | 3 | **2.27 – 2.70** | **`#6C645F`** | **4.79 – 5.71** ✅ |
| **`#A99E95`** ⚠️ | 7 | **15** (one shared declaration ×2 footer lines) | **2.17 – 2.58** (**2.23** on the `#F6EBE2` wash where it actually lands) | **`#6C645F`** | **4.94** on that wash ✅ |

**Plates the email set actually uses:** card `#FFFDFB` · outer/footer wash `#F6EBE2` · insets `#FCF7F2`,
`#F8F1EA`, `#FBF1E9`, `#F2E8DF`. Both replacement values clear **4.5 on all six** — asserted by the guard.

### §N.8f — one definition where there was one, per-occurrence where there wasn't

Emails cannot use a shared stylesheet or a custom property (clients strip them), so the greys repeat inline.
**But `#A99E95` was the exception that proves the rule:** a **single declaration** in `emails/layout.blade.php`
carried the "Powered by Vyrobel" and "why you're receiving this" lines on **15 templates at once** — one line
fixed, fifteen emails corrected. The other two were per-occurrence, then **grepped to zero**.

**The old values now return ZERO across `emails/*` and `auth/login-approval*`.** The single residue is
`owner-invite.blade.php:17`, a **Blade docblock** naming the refused prototype greys — Blade comments do not
render, the register verified it, and this sweep re-verified it: **`owner-invite` measured 22 text runs, 0
failures, before and after.** It is exempted in the guard for exactly that reason.

### The two auth screens — measured with the SCREEN instrument, because the plate is translucent over a gradient

`auth/login-approval*` are not emails, so the declared-value shortcut does **not** hold: the card is
`rgba(255,252,248,.9)` over `linear-gradient(180deg,#FBF3EC,#F6EBE2)`. **Pixel-sampled from a real render**
(glyph-free region beside the `.hint` text): the true plate is **`#FEFAF6`**, anchors **14.82** (passing) /
**2.12** (failing) — no suspicious 1.00.

- `login-approval.blade.php` — `.hint` at 12px: **`#8A8079` = 3.71 FAIL → `#5F5853` = 6.72 PASS.**
- `login-approval-confirmed.blade.php` — ⚠️ **the declaration was DEAD.** `--muted` is declared in `:root` and
  **never referenced** (`grep var(--muted)` → nothing). It rendered no text, so it neither passed nor failed;
  it was retargeted as a **trap removal**, and it is recorded here as *dead*, **not** as a measured fix.

### ⚠️ Greyscale-safe

Both replacements are **luminance-only** changes along the same warm hue — no state, label or meaning anywhere
in the email set is now carried by hue alone that was not before. (One pre-existing colour-only signal is
logged below: the review-request ★.)

### 🔒 Guard — `tests/Feature/EmailContrastSweepTest.php` (4 tests / 34 assertions)

Anchors the helper against the PDF sweep's published figures · asserts both replacements clear 4.5 on all six
real plates · asserts **no email template ships any of the three greys** · asserts the layout stays
**email-safe** (literal hex, no `var(--vy-*)`). **Mutation-proven:** reverting one template's `#5F5853` to
`#8A8079` turns it **red, naming the file and the grey**; restored → green. `php -l` clean on both passes, so
the red came from the guard and not from a broken file (§N.13.2).

### ✅ NO CONTENT CHANGED — proven, not asserted

Every template was re-rendered after the sweep and diffed against its pre-sweep render **with hex literals
normalised**: **19 / 19 byte-identical.** The only hex delta across the whole rendered set is
`{#8A8079, #A69A92, #A99E95}` **out** and `{#5F5853, #6C645F}` **in**. No copy, subject, link, figure, date or
mailable/send-path logic was touched.

### 🚩 LOGGED, NOT FIXED — the residual 10 failing runs (`DO-EMAIL-1`), outside `DO-INV-4`'s named literals

⚠️ **`DO-INV-4` is closed; "the emails are AA-clean" is NOT the same claim.** Seven further literals still fail,
measured here and left alone because the sweep's scope was the named greys:

| literal | where | measured | needs | note |
|---|---|---|---|---|
| **`#8A7A6A`** | `pre-arrival` ("Works from … · same code all stay") | **3.72** on `#FBF1E9` | 4.5 | ⚠️ **this is `DO-G4-2`, which the register carried as "unmeasured" — now measured.** |
| **`#B26A08`** | `payment-reminder` "Balance due", `pre-arrival` "Your door code" | **3.80** on `#FBF1E9` | 4.5 | the amber label on both money/access panels |
| **`#9A8C7F`** | `login-approval` (email) ×2, `owner-report` footer | **3.22** on card · **2.78** on wash | 4.5 | |
| **`#9C9089`** | `booking-confirmation` "Before you arrive" | **3.06** on `#FFFDFB` | 4.5 | |
| **`#E8A317`** | `review-request` ★ (32px) | **2.14** on `#FFFDFB` | 3.0 (large) | ⚠️ a **colour-only** signal — the DO10-4 greyscale family, in an email |
| **`#64748B`** | `admin-email-change` | **3.62** on `#111B2E` | 4.5 | |
| ~~**`#E8EDF6`**~~ ⚠️ | ~~`admin-email-change` button label~~ | ~~2.54 on `#818CF8` [GRADIENT]~~ | — | ⚠️ **MIS-ATTRIBUTED — corrected at DO-EMAIL-1.** The button declares `color:#fff`; the probe's hex regex read only **6-digit** hex, so the run **inherited the parent's `#E8EDF6`**. `#E8EDF6` renders on the dark card `#111B2E` at **14.65 — it passes and was never a defect.** The real failing run is **WHITE on the indigo gradient**, pixel-sampled at **3.08**. *The instrument fails too (§N.13.4) — and here it named the wrong colour while still pointing at the right pixel.* |

Six of the seven are one-line changes. ⚠️ `#E8EDF6` must be **pixel-sampled** first.

### 🚩 LOGGED — `DO-EMAIL-2`: the grey drift is NOT confined to PDFs + emails

Grepping the three greys realm-wide turned up a **third surface class still on the pre-AA palette** — operator
and owner **screens**, in files the design-parity walk recorded as gated:

- `livewire/operator/owners.blade.php` — **~12 live text declarations** (`#A69A92` ×6, `#8A8079` ×5, incl.
  `.vy-inv-help`, the invite sub-labels and the "we emailed …" line)
- `livewire/operator/operator-settings.blade.php:441` — **`#A99E95`** on a *"Powered by Vyrobel"* line (the same
  footer role, the same literal, a different surface)
- `livewire/maintenance-ticket-detail.blade.php` — `#A69A92` on a conditional retention note + an SVG stroke
- `livewire/maintenance-list.blade.php:88` · `livewire/operator/dashboard.blade.php:211` — `#8A8079` as a
  **status DOT** (decorative, non-text — a different question, but the same stale literal)

Out of this sweep's scope (screens need the rendered-pixel instrument) and out of `DO-INV-4`'s (which names
`emails/*` and `auth/login-approval*`). ⚠️ **But it is the §N.8b shape:** a realm recorded as design-closed
still shipping the palette two other sweeps have now retired.

### 🚩 LOGGED — `DO-EMAIL-3`: one email is off-brand

`admin-email-change` renders a **dark slate/indigo** document (`#111B2E` / `#0C1322` / `#818CF8` / `#64748B`)
while all 19 others are the warm terracotta system. Deliberate-looking (it is the platform-admin email), but it
is the only template outside the brand and the only one whose failures are in a different palette. A design
decision to confirm, not a defect to fix silently.

### Honesty

⚠️ **No dishonest display (§N.8d) surfaced while measuring** — recorded plainly, with its limit: this pass read
**colour**, not claims. It is not a copy audit, and it should not be cited as one.

---

## §EMAIL-SWEEP-2 — `DO-EMAIL-1` · the six remaining email literals · **CLOSED** (2026-08-12)

The `DO-INV-4` continuation: the **same email instrument, the same contrast-only discipline**, on the literals
that sweep surfaced and deliberately left. **10 failing text runs → 1**, and the one that remains is the ★,
excluded on purpose.

### ⚠️ Premise corrections (§N.8b) — two, and one of them was MY OWN instrument

| the record said | verified |
|---|---|
| **`#E8EDF6` fails at 2.54 on a gradient** | ❌ **WRONG, and the error was the probe's.** The button declares `color:#fff`; the first probe's hex regex matched only **6-digit** hex, so the run **inherited the parent's `#E8EDF6`**. `#E8EDF6` actually renders on the dark card at **14.65 — it passes, and was never a defect.** The real failing run is **WHITE on the indigo gradient**. ⚠️ The probe pointed at the right *pixel* while naming the wrong *colour* — corrected in place in §EMAIL-SWEEP, and the probe now reads 3-digit hex and named colours. |
| **`DO-INV-4` measured 332 runs / 120 FAIL** | ✅ **SURVIVES — re-verified with the corrected probe against a clean pre-sweep worktree (`5d59ac4`): 332 / 120, exactly.** ⚠️ An intermediate re-measurement read 321/118 and looked like a discrepancy; the cause was a **scratchpad name collision** (the `auth/login-approval` *screen* render had overwritten the `login-approval` *email* render in the before-snapshot). The published figures were right; the interim number was the artefact. Recorded because the near-miss is the point: **a number that moves is not automatically a correction — find out which reading is broken.** |
| *"six are one-liners"* | ⚠️ **Roughly**: 4 are single declarations; `#B26A08` and `#9A8C7F` are 2 each. 8 declarations, 10 runs. |

### The six — each re-measured on its OWN plate, all six failed, all six mapped

| literal | occurrences | re-measured on its real plate | → mapped to | after |
|---|---|---|---|---|
| **`#8A7A6A`** | `pre-arrival` ×1 | **3.72** on `#FBF1E9` | `#5F5853` | **6.27** ✅ |
| **`#B26A08`** ⚠️ | `payment-reminder` ×1, `pre-arrival` ×1 | **3.80** on `#FBF1E9` | `#7E5811` | **5.73** ✅ |
| **`#9A8C7F`** | `login-approval` ×1 (2 runs), `owner-report` ×1 | **3.22** on `#FFFDFB` · **2.78** on `#F6EBE2` | `#5F5853` | **6.88 / 5.95** ✅ |
| **`#9C9089`** | `booking-confirmation` ×1 | **3.06** on `#FFFDFB` | `#5F5853` | **6.88** ✅ |
| **`#64748B`** ⚠️ | `admin-email-change` ×1 | **3.62** on `#111B2E` | `#94A3B8` | **6.71** ✅ |
| **the CTA gradient** ⚠️ | `admin-email-change` ×1 | **white on `#7E89F7` = 3.08** *(pixel-sampled)* | stops → `#5B5FE3`,`#4338CA` | **5.19** ✅ *(re-sampled)* |

**None passed.** Had one passed on its real plate it would have been reported and left — the measurement decides.

### ⚠️ Three of the six were not simple darkenings

- **`#B26A08` is the RAW `--vy-amber` FILL used as TEXT.** The guest realm already solved this once
  (`--vy-amber` → `--vy-amber-text` for text); an email cannot read a token, so the **VALUE** was inlined:
  `rgb(126,88,17)` = **`#7E5811`**. ⚠️ **`--vy-amber-text` is DEFINED TWICE and the two differ** — guest
  `rgb(126,88,17)` vs owner `rgb(128,76,5)` (`components/guest/shell.blade.php:49` ·
  `components/layouts/owner.blade.php:67`). The **guest** value was used (these are guest-facing emails). The
  drift is a §N.8f finding in its own right → **`DO-EMAIL-4`**.
- **`#64748B` had to go LIGHTER, not darker.** It is light-on-**dark** (`#111B2E`), so the reflex of darkening
  a failing colour would have made it *worse*. `#94A3B8` was already the body copy in that same email — a
  unification, not a new literal.
- **The CTA is a GRADIENT, so it was PIXEL-SAMPLED, never computed.** WCAG takes the worst case, i.e. the
  **lightest** pixel under the white glyphs: **`#7E89F7` → 3.08**. Stops darkened to `#5B5FE3`→`#4338CA`;
  re-sampled lightest pixel **`#595CE1` → 5.19**. Anchors exact both times (`#111B2E` card, `#0C1322` page) —
  no suspicious 1.00. ⚠️ **The gradient is PRESERVED** (unlike the invite email, which had to drop to a solid);
  the eyebrow `#818CF8` and mark `#6366F1` are untouched because they pass on the dark card.

### ⚠️ `DO-G4-2` — CLOSED as a by-product

`#8A7A6A` **is** `DO-G4-2`, the pre-arrival grey the register carried as *"unmeasured"* from G4. Measured
(**3.72**) and fixed here. ⚠️ Note it was retargeted to `#5F5853` rather than to a same-hue `#5F5449` (measured
**6.62**, exact hue/saturation preserved): the sub-line sits beside `#5F5853` secondary text in the *same*
email, so unification beat a bespoke tone. Both clear AA; the choice was **palette discipline, not contrast.**

### ⚠️ `#E8A317` — the ★ — EXCLUDED on purpose

`review-request`'s 32px star measures **2.14** (needs 3.0). It is **not fixed here and must not be**: it is a
**colour-only rating signal**, so darkening the hex changes the number without fixing the defect — in
greyscale, filled and empty still read alike. Its home is **`DO10-4`** (glyph-differentiated stars,
floor-don't-round), which is correctness (WCAG 1.4.1), not contrast. **It is the single remaining failing run
in the email set.**

### Greyscale-safe · old values grepped to zero · guard

Every mapping is **luminance-only within its own hue family** (warm stone → warm stone; slate → slate; indigo →
indigo). No state or label is now distinguished by hue that was not before. **All five text literals return
ZERO** across `emails/*`; the sole residue is `owner-invite.blade.php:17`'s **Blade docblock** (does not render;
re-verified 22 runs / 0 failures). The pre-AA CTA gradient string returns zero.

`tests/Feature/EmailContrastSweepTest.php` grew 4 → **6 tests / 55 assertions**, and includes a
**non-vacuity assertion** (white on the OLD stop `#818CF8` must still measure < 4.5). **Mutation-proven twice** —
reverting the amber, and reverting the gradient — each turning it **red naming the file and the literal**;
`php -l` clean on every pass, so the red came from the guard (§N.13.2).

### ✅ No content changed — proven

Re-rendered and diffed against the pre-`DO-EMAIL-1` renders with hex literals normalised: **20/20
byte-identical.** The only hex delta is `{#8A7A6A, #B26A08, #9A8C7F, #9C9089, #64748B}` out and
`{#5F5853, #7E5811, #5B5FE3, #4338CA}` in (`#94A3B8` does not appear as "added" — it was already in that
email). No copy, subject, link, figure, date, or mailable/send-path logic was touched.

⚠️ **No dishonest display (§N.8d) surfaced** — same limit as `DO-INV-4`: this pass read **colour**, not claims.

**Status: `DO-EMAIL-1` CLOSED. The email set now has ONE failing run left — the ★, which belongs to `DO10-4`.**

---

## §WEB-GREY-SWEEP — `DO-EMAIL-2` · the three greys on the operator/owner WEB screens · **CLOSED** (2026-08-12)

A **different surface** from the two email sweeps: Livewire/Blade, where a `--vy-*` token exists — so a
hardcoded grey is not merely a contrast bug, it is a **token drift**, and the fix is the token.

### ⚠️ NOT A REGRESSION — and the reason is blunter than "an immature instrument"

The brief anticipated that the operator (42/42) and owner (32/32) gates missed these because their contrast
method predated the guest walk. **The measurement says otherwise, and the real reason is worth more than the
assumed one:**

> **All eleven `owners.blade.php` occurrences sit inside `@can('users.manage')` — a permission the demo
> Hotel Manager the gates were driven as does NOT hold — and inside an Alpine `x-show="open"` modal on top of
> that.** Verified live: as `demo-staff` the markup is **absent from the DOM entirely** (`.vy-inv-scrim`,
> `.vy-inv-modal`, `.vy-inv-help` all return nothing). Measuring required **a role swap** (to `demo-admin`, a
> `Property Owner` — one of only three roles holding `users.manage`) **and then** a forced Alpine state.

**So it was a COVERAGE gap, not an instrument gap.** No probe, however mature, can measure markup a permission
check never emitted. ⚠️ **The generalisable lesson: a contrast pass is bounded by the ROLE it runs as.** A
`@can`-gated surface is invisible to a sweep driven as a role that fails the check. This belongs beside G10
(*a conditional style is invisible until forced*): **so is a conditional SURFACE — and the condition can be
authorization.**

### The grep — 28 occurrences, not the 13 the register named

⚠️ `DO-EMAIL-2` named *"`owners.blade.php` ~12 declarations + `operator-settings.blade.php:441`"*. The full
grep — **including the `rgb()` forms, which the hex grep alone would have missed** — returns **28 across 9
files**, and the two `rgb()` hits are sites nobody had named:
`components/layouts/operator.blade.php:60` (`--vy-hk-ooo`) and **`billing/feature-locked.blade.php:26`, the
body copy of a whole 403 page.**

### The root cause, stated exactly

**The three literals ARE the pre-AA values of the two text tokens.** Hardcoding them froze those runs at the
pre-remediation palette while the tokens moved underneath:

| literal | is the old value of | the token today |
|---|---|---|
| `#8A8079` | `--vy-muted` | `rgb(95, 88, 83)` = `#5F5853` |
| `#A69A92` | `--vy-faint` | `rgb(108, 100, 95)` = `#6C645F` |
| `#A99E95` | a near-neighbour of `--vy-faint`, hardcoded on a *"Powered by Vyrobel"* line | `#6C645F` |

Both tokens are declared **identically** in `components/layouts/operator.blade.php` and
`components/layouts/owner.blade.php` — so the fix is `var(--vy-muted)` / `var(--vy-faint)`, and these runs will
now follow the token if it is ever retuned again.

### Measured on the REAL render — pixel-sampled, two anchors, never `getComputedStyle`

| surface | role | forced state | plate *(pixel-sampled)* | anchors | before | → | after |
|---|---|---|---|---|---|---|---|
| **Owners · invite modal** — `.vy-inv-help` + 2 inline sublines | `Property Owner` | ⚠️ `@can` + `x-show="open"` | **`#F8F4EF`** (glass over a scrim) | 14.06 / 2.01 | `#A69A92` **2.50** ❌ | `var(--vy-faint)` | **5.29** ✅ |
| **Owners · invite modal** — `.vy-inv-x`, `.vy-inv-cancel` + 4 sublines | `Property Owner` | same | `#F8F4EF` | 14.06 / 2.01 | `#8A8079` **3.52** ❌ | `var(--vy-muted)` | **6.38** ✅ |
| **Operator Settings** — *"Powered by Vyrobel"* | `Property Owner` | scrolled into view (y≈3359) | **`#F6EBE2`** | 13.12 / 1.88 | `#A99E95` **2.23** ❌ | `var(--vy-faint)` | **4.94** ✅ |
| **`billing/feature-locked`** — the `<p>` body copy | any (a 403 page) | reached via a Pro-gated route on an Essential org | **`#FCF7F2`** | 14.46 / 2.07 | `rgb(138,128,121)` **3.62** ❌ | **`#5F5853`** *(literal)* | **6.56** ✅ |
| **Maintenance ticket** — 3 Alpine `:style` branches | operator | conditional `:style` strings | card | — | `#8A8079` ×2, `#A69A92` ×1 | `var(--vy-*)` | ✅ |

⚠️ **The modal plate `#F8F4EF` is exactly why a pixel sample was mandatory** — it is `rgba(255,252,248,.94)`
glass composited over a `rgba(43,35,32,.38)` blurred scrim over the page. **No declared-value computation could
produce it**, and the email sweep's declared-hex walk would have been wrong here. **None of the measured
occurrences passed**, so none were left.

⚠️ **`billing/feature-locked` took a LITERAL, not a token, and that is logged not hidden:** it is a standalone
403 document with its own `<style>` and **no `--vy-*` in scope**. → **`DO-EMAIL-6`**.

### Deliberately NOT changed — each classified, none a text-contrast defect

- **Two status DOTS** (`maintenance-list.blade.php:88`, `dashboard.blade.php:211`) and **three decorative
  input-adornment SVG strokes** — **non-text**, each paired with a real visible label, so WCAG 1.4.11 does not
  bite. The strokes were retargeted to the **literal** `#6C645F`: `var()` *does* resolve in an SVG presentation
  attribute in Chromium (tested live), but where it is unsupported the attribute is invalid and the icon
  **renders nothing** — a vanished decorative icon is a worse trade than a hardcoded value, for zero contrast
  gain. Recorded so the choice is deliberate.
- **`--vy-hk-ooo: rgb(138, 128, 121)`** (`components/layouts/operator.blade.php:60`) — a housekeeping
  *"Out of order"* status **FILL** token that merely shares the value, used as a dot beside its own label.
  Recolouring a status indicator is a **design decision**, not a contrast fix → **`DO-EMAIL-7`**.
- **Four comment lines** documenting this very remediation. ⚠️ **Two of them are CSS comments, which SHIP**
  (`operator.blade.php:500`, `rates.blade.php:10`) — the repo's own known trap; the third and fourth
  (`reviews.blade.php:61-62`) are Blade comments and do not. They are accurate prose, not colour, and were
  left; the guard therefore matches a **colour declaration**, never a bare substring, so it cannot fail on its
  own documentation.

### 🚩 `DO-EMAIL-5` — a FOURTH grey, measured and logged

`.vy-inv-lbl { color: #9C9089 }` — **every form label in the invite modal** (Owner name · Email · Role ·
Assign units · Management agreement), 10.5px, measured **2.84** on the same `#F8F4EF` plate. Out of this
sweep's named three, so not fixed; it is a one-line change in the same CSS block. ⚠️ **A full operator/owner
contrast re-audit is a backend-phase item** — this pass measured only what it was scoped to, and found a
fourth failure the moment it looked.

### ⚠️ `DO-EMAIL-4` — checked here, and it does NOT apply

The twice-defined `--vy-amber-text` (guest `rgb(126,88,17)` vs owner `rgb(128,76,5)`) was watched for on these
screens. **Neither value appears on any surface touched by this sweep** — grep-confirmed. `DO-EMAIL-4` stays
open and untouched.

### Gated guarantees RE-VERIFIED — 0 regression

Nothing but colour was changed: no tenant isolation (#38/#42), no RBAC, no data, no Livewire logic, no
component state. Re-ran the isolation/RBAC/plan-gating set for these exact screens — **712 tests, 710 pass,
2 errors = the documented date-coupled baseline pair** (`OwnerUnitDetailViewTest`, `StubViewsTest`, both
`UnitUnavailableException` from seeding). **No isolation or RBAC test moved.** Full suite **1897 / 12,389**,
9 non-green = the documented set.

### Greyscale-safe · guard

Every mapping is **luminance-only within the same warm hue** (the tokens are the same family, darker). Guard:
`tests/Feature/OperatorOwnerGreyDriftTest.php` — asserts both shells still declare the tokens at their
remediated values, that **no operator/owner view declares a pre-AA grey as a colour**, and a **non-vacuity**
check that the old values fail and the new ones pass on all three pixel-sampled plates. **Mutation-proven:**
reverting `.vy-inv-help` to `#A69A92` turns it **red naming the file and the colour**; `php -l` clean both ways.

**Status: `DO-EMAIL-2` CLOSED** for the three named greys as text. Open successors: `DO-EMAIL-5` (the fourth
grey), `DO-EMAIL-6` (standalone pages cannot reach the tokens), `DO-EMAIL-7` (the `--vy-hk-ooo` fill).

---

## §ADMIN-EMAIL-BRAND — `DO-EMAIL-3` · the off-brand platform-admin email · **CLOSED** (2026-08-12)

⚠️ **This one INVERTS the three contrast sweeps.** There: contrast only, content proven byte-identical. **Here
the WRAPPER changes and the CONTENT is inviolable** — because `admin-email-change` is a **security email**. An
email-address change is the classic account-takeover move, so every sentence the recipient acts on had to
survive verbatim.

### ⚠️ Premise correction — `@extends` is NOT the brand test

`DO-INV-4` called this *"the only off-brand email"*. **The claim SURVIVES, but the reasoning behind it would
have been wrong:** **five** templates are standalone (they do not `@extends('emails.layout')`) —
`admin-email-change`, `login-approval`, `owner-invite`, `owner-report`, `password-reset`. **Four of the five
are standalone but on-brand**, rendering the warm Vyrobel palette. Only `admin-email-change` was outside the
brand *system*:

| | `admin-email-change` (before) | the other 19 |
|---|---|---|
| palette | **dark slate/indigo** `#0C1322` · `#111B2E` · `#6366F1` · `#818CF8` · `#94A3B8` · `#E8EDF6` | warm `#F6EBE2` wash · `#FFFDFB` card · `#C2410C` accent |
| brand lockup | ✗ an indigo "V" tile only | ✓ the mark + "Vyrobel" wordmark |
| footer | ✗ none at all | ✓ powered-by + a service line |
| CTA | an indigo **gradient** pill | the shared **bulletproof solid** button |

**So: off-brand by PALETTE, not by being standalone.**

### The sibling it was diffed against

⚠️ **`owner-email-change` does the IDENTICAL job in the owner realm — and was already on-brand**, extending the
shared layout. That is the whole argument for the fix: not "make it prettier", but *the same email for a
different realm already renders in the brand system, and this one did not.* The template now follows it exactly.

### ⚠️ THE CONTENT IS UNCHANGED — proven, not asserted

The rendered text was extracted before and after and diffed. **Every security sentence survives verbatim:**

| the sentence | before | after |
|---|---|---|
| `Vyrobel · Platform admin` (the realm signal) | ✓ | ✓ |
| `Confirm your new email` | ✓ | ✓ |
| `Hi {adminName},` | ✓ | ✓ |
| the new address (`{newEmail}`) | ✓ | ✓ |
| *"your email stays the same until you do"* | ✓ | ✓ |
| *"The link expires in one hour."* | ✓ | ✓ |
| *"If you didn't request this, you can ignore this email — nothing changes without opening the link."* | ✓ | ✓ |

**Links: identical sets** — exactly one destination (the signed verify URL) before and after. **The text diff's
only deltas are WRAPPER strings**: the `<title>`, the preheader, the "Vyrobel" lockup, the powered-by line and
the footer service line. *(One `-/+` pair is a source line-wrap: `.` + `Confirm it by…` became `. Confirm it
by…` — the same words in the same order, re-flowed onto one line.)*

⚠️ **The warning was NOT demoted.** It still sits directly under the CTA, inside the card, above the footer —
asserted by position, and that assertion is proven non-vacuous (a mutation moving the warning above the CTA
turns it red).

### ⚠️ The footer was overridden ON PURPOSE — inheriting it would have shipped a FALSE line

The shared layout's default service line is *"You're receiving this because it relates to your booking with
us."* **A platform admin has no booking.** Rather than ship a false statement on a security email, the
template fills the layout's own footer-override slot with an accurate line. *(That slot is named
`unsubscribe`; it is really "the footer service line" — a misleading name, logged as `DO-EMAIL-8`, not renamed
here.)*

### 🚩 §N.8d — a FALSE line found on the SIBLING, logged not fixed → `DO-EMAIL-9`

⚠️ **`owner-email-change` — an owner's LOGIN-EMAIL change — renders *"You're receiving this because it relates
to your booking with us."*** An owner changing their login email has no booking. Visible in the screenshot.
It is the layout's default line applied to an account/security email, and the same trap this template just
stepped around. **Out of scope here** (it is the sibling's footer, not this email's branding) — logged.
⚠️ Worth checking the other account/security emails that extend the layout for the same line.

### ✅ The security claims VERIFIED against the engine (§N.8d / verify-every-promise)

| the claim | the engine | verdict |
|---|---|---|
| *"The link expires in one hour."* | `URL::temporarySignedRoute('admin.account.email.verify', now()->addHour(), …)` — `AccountController:119` | ✅ **REAL** |
| *"your email stays the same until you do"* | the new address is parked in `pending_email` (`:116`); `email` is only written on verify (`:162`) | ✅ **REAL** |
| *"nothing changes without opening the link"* | ⚠️ substantively true with a nuance: `pending_email` **is** written, so *something* is stored — but the **login email**, which is what the sentence is about, does not change. The G11 *"your email is confirmed"* shape. | ✅ accurate as written |

**No false security claim.** Recorded as plainly as a failure would have been.

### Contrast RE-VERIFIED after the branding — 0 new failures

Re-measured with the email instrument (declared colour on the real plate, never `getComputedStyle`):
**`admin-email-change` = 12 text runs, 0 FAIL**, inheriting the layout's already-AA greys. The whole set is
**335 runs, 1 FAIL** — still only the ★ (`DO10-4`). Greyscale-safe: the palette is now the same warm system as
every other email.

⚠️ **This SUPERSEDES part of `DO-EMAIL-1`.** That pass darkened this email's indigo CTA gradient
(`#818CF8`→`#5B5FE3`, white 3.08 → 5.19). The gradient is now **gone entirely** — the CTA is the layout's
bulletproof solid `#C2410C` (white = 5.18, the ratified invite-email decision), which is also more email-safe
than a gradient. The sweep test's gradient assertion is therefore now **structurally vacuous for this file**
(O32: a passing assertion means vacuity *or* redundancy — resolve it). It is kept as a
do-not-reintroduce guard, and the real invariant is asserted in the new test instead.

### 🔒 Guard — `tests/Feature/AdminEmailChangeBrandingTest.php` (4 tests / 24 assertions)

Renders the **real mailable**. Asserts: it extends the shared layout and carries the layout's furniture while
shipping **none** of the dark palette · every security sentence verbatim · exactly one href · the warning
positioned between the CTA and the footer · and that the false booking line is **not** inherited.
**Mutation-proven three ways** — dropping the expiry sentence, demoting the warning into the footer, and moving
the warning above the CTA each turn it red.

### ⚠️ A NEW instrument trap — the Blade analogue of `php -l` (extends §N.13.2)

After restoring from the third mutation the test stayed **RED**, with the source verifiably correct. The cause
was a **stale compiled Blade view**: the mutated template had been compiled, and the restore did not trigger a
recompile. `php artisan view:clear` turned it green. **A false RED from the toolchain, not the guard** — the
same family as the PDF sweep's bash-corrupted file. **Run `php artisan view:clear` after every Blade mutation
edit**, exactly as `php -l` is run after every PHP one.

### DO-EMAIL-6 note

This template was one of the standalone set; adopting the shared layout brings it **into the token-reachable,
consistently-maintained group**. `DO-EMAIL-6` remains open for the others that genuinely cannot extend an email
layout — `billing/feature-locked` and `auth/login-approval*` are **web pages**, not emails.

**Status: `DO-EMAIL-3` CLOSED — all 20 emails now render in the warm brand system.**

---

## §STAR-CAPSTONE — `DO10-4` · star correctness across web + email · **CLOSED** (2026-08-12)

⚠️ **A CORRECTNESS pass, not tone.** A star row can state the **wrong rating** two ways, and the product had
shipped both. This closes the O26 defect family for good.

### The three defects — two named, one the sweep found

| # | defect | where | proof |
|---|---|---|---|
| 1 | **ROUND-NOT-FLOOR** — overstates | `owner/unit-detail:338` `str_repeat('★', round($average))` | forced a real owner unit to **4.67** and rendered it live: the old code prints **five filled stars** on a 4.67 |
| 2 | **COLOUR-ONLY** — greyscale reads the wrong count | already fixed at O26 + G12 | re-verified: no rating row separates filled from empty by colour alone |
| 3 | ⚠️ **THE SHARED COMPONENT'S OWN COLOURS FAILED 3:1** | `<x-star-rating>` defaults `#E8A63A` / `#B9A99A` | measured **2.08 / 2.25** — *the component everything was being migrated TO drew a star you could barely see*. This is **`DO-G8-2` answered by measuring**. |

⚠️ **Premise correction on defect 1:** the register called `unit-detail:338` colour-only *and* round. It was
**not** colour-only — it already emitted `★` then `☆`. **The defect there was `round()` alone** (plus no
accessible name). Corrected.

### The web remnant — migrated, and verified live AS THE OWNER

`unit-detail:338` → `<x-star-rating>`. Rendered at `/owner/units/{unit}` **as `demo-owner`** (the DO-EMAIL-2
lesson: run as the role that can see the page) with the state **forced** to a 4.67 average (G10), then the
demo data restored:

```
accessible name : "4.7 out of 5 stars"      (one name, not ten mute glyphs)
glyphs          : ★ ★ ★ ★ ☆                 (4 filled + 1 empty — the OLD code printed 5 filled)
transparent els : 0 before capture
```

⚠️ **This CHANGES what the owner sees, and that is the point:** a 4.67 unit stops advertising itself as
perfect. **An honest rating, not a regression.**

### ⚠️ The measurement corrected me once — the plate was not the card

A first pass retargeted the component to `#B26A08` / `#8F8073`, measured against the two **card** plates. The
**live render then showed the row sits on the PEACH hero plate `#FBD3BF`**, where the empty star measured
**2.76 and still failed**. Re-chosen against the **worst real plate** and re-measured on all three:

| | on `#FBD3BF` (peach) | `#F8F4EF` (owner card) | `#FFFDFB` (light card) |
|---|---|---|---|
| filled **`#8F5A06`** | **4.19** ✅ | 5.28 ✅ | 5.70 ✅ |
| empty **`#7A6E64`** | **3.59** ✅ | 4.52 ✅ | 4.88 ✅ |
| *(what shipped)* `#E8A63A` / `#B9A99A` | 1.53 / 1.65 ❌ | 1.93 / 2.08 ❌ | 2.08 / 2.25 ❌ |

Filled stays **stronger than** empty on every plate, so the hierarchy survives. Hues preserved (amber h37 /
warm stone h27) — **luminance-only, so greyscale is unaffected**. Anchors validated on the live render
(`#2B2320` 11.15 pass · `#B7ADA4` 1.60 fail).

### ⚠️ The email star was NOT what the register said it was

`DO-EMAIL-1` routed `#E8A317` here as *"a colour-only rating signal"*. **It is not a rating display at all** —
`review-request` renders **five tappable rating INPUTS**, each deep-linking
`route('guest.review', [..., 'rating' => $n])` under the copy *"Tap a star — it opens your review"*. So
**★/☆ would have been WRONG here**: it would show a rating the guest has not given. The real defects were
different, and both are fixed:

- **contrast** — `#E8A317` measured **2.14**; a meaningful non-text **control** needs 3:1 → **`#8F5A06`**
  (**4.17** on the email card), the same gold the component now uses.
- ⚠️ **the accessible name** — each link's name was the bare glyph **"★"**, so a screen reader heard *"star,
  link"* five times with nothing to choose between. Each now names its rating, mirroring the web review
  form's pattern. Verified on the **real mailable**: `Rate 1 out of 5 stars` … `Rate 5 out of 5 stars`.

⚠️ **`DO-EMAIL-4` flagged, not fixed:** `--vy-amber-text` is defined twice with different values, so an email
cannot safely "use the token" even in spirit. The gold is an email-safe literal; the drift stays logged.

**The email set is now 335 text runs, 0 FAIL** — the ★ was the last one.

### ⚠️ THE COVERAGE SWEEP — it found four sites nobody had named (the DO-EMAIL-2 pattern)

Grepped every star/rating render across all realms, then classified each by *role* and *state*:

| site | verdict |
|---|---|
| `owner/reviews:57`, `guest/review:51`, `owner/unit-detail:342` | **`<x-star-rating>`** — floor + glyph + named ✅ |
| 🆕 **`operator/guest-profile:132`** | `#E8A317` = **1.98 FAIL**, and the row had **no accessible name** → `#8F5A06` + named. *An operator page — no star pass had ever rendered it.* |
| 🆕 **`owner/reviews:123`** | filled `#E8A63A` **1.93** / empty `#D9C8BC` **1.48**, both FAIL, unnamed → the shared pair + named |
| 🆕 **`booking/room:91`**, **`owner/unit-detail:348`** | colours already pass (3.04 / 6.49) — **not "fixed"**; the missing **accessible names** added |
| 🆕 **`NewReviewNotification:31`** | built the filled half **only** — *"New review · ★★★"*, ambiguous between 3/5 and 3/3, while its sibling `LowRatingNotification` already built both. Matched to the sibling → `★★★☆☆`. |
| `operator/reviews:70` | ⚠️ **my probe false-flagged this as unnamed.** It is correct: the stars are `aria-hidden` decoration and a visible **`4/5`** sits beside them. *Verify-don't-assume, applied to my own instrument.* |
| `owner/messages:193,200,288` | **NOT a rating** — a *"Starred messages"* flag/toggle, a different concept, its state also carried by a visible count. **Out of scope → `DO-STAR-1`.** |

### 🔒 Guard — `tests/Feature/StarRatingCorrectnessTest.php` (6 tests / 35 assertions)

Behavioural (renders the component), not string-matching. **Mutation-proven five ways**, each red on its own
assertion: `floor`→`round` · `☆`→`★` (colour-only) · drop the accessible name · revert the filled colour ·
revert the empty colour. `php -l` + **`php artisan view:clear`** after every Blade mutation (the DO-EMAIL-3
toolchain false-RED).

⚠️ **And the guard itself was VACUOUS at first (O32).** Reverting the component's filled colour left it
**green**, because the contrast test asserted hardcoded literals — arithmetic, not the component. Rewritten to
read the inks the component **actually renders**; mutation 4 then went red naming the value and the plate.

### Left deliberately

- **`DO-STAR-1`** — the owner Messages *"Starred"* flag SVGs still use `#E8A63A`. A different feature (a
  bookmark, not a rating), state also conveyed by a count. A design call.
- **`DO-EMAIL-4`** — the twice-defined `--vy-amber-text`, flagged again here.

**Status: `DO10-4` CLOSED. Every rating row in the product now floors, differs by GLYPH, carries one
accessible name, and clears 3:1 on the worst plate it renders on. The O26 defect family is retired.**

---

## §GUEST-FAQ — the guest Help centre · **BUILT** (2026-08-12)

A **build**, not a parity gate: a new route, controller and view. It takes G13's `DO-G13-1` verdict, which the
founder ruled on — **BUILD the FAQ centre; the support half is a separate backend; keep it a PURE FAQ.**

### ⚠️ The refusal it reverses, and the half of it that still stands

The 2026-08-07 audit gave the guest a real support destination and refused a help **centre** because *"its
content would have to be invented"*. **That premise is now false** — `HelpKnowledgeBase` is curated, real,
screen-linked content. ⚠️ **But the rest of the refusal was right and is honoured:** the page invents nothing,
and it offers **no support affordance at all**.

### What was built

| piece | detail |
|---|---|
| `HelpKnowledgeBase::guestArticles()` | **10 entries / 5 categories**, added to the CERTIFIED engine — **not forked** (§N.8f). Same contract as the owner set plus a `category`; a test asserts both sets keep that shape and that guest entries link only `guest.*` routes while owner entries link only `owner.*`. |
| `GuestHelpController` | Server-side filter on `?q=` and `?category=`. **GET only** — no write exists on the page (§N.4k: safe by construction), which also keeps the guest realm's no-Alpine convention. |
| `guest/help.blade.php` | `<x-guest.shell>`, `--vy-*` tokens, a native `<details>` accordion, GET category links. ⚠️ **It holds no help content** — every question, answer and link comes from the engine. |
| the nav | a `Help` entry in the guest shell |
| **no schema** | the knowledge set is curated code, not a table — confirmed, `git status database/` is clean |

### ⚠️ Every answer was verified against the code before it was written

The door-code answer **interpolates `ArrivalPack::LEAD_DAYS`** rather than typing a number — `DO-G4-1` had
already caught a docblock claiming "48h" against a constant that says **2 days**, and a FAQ is exactly where
that drift would mislead a guest standing at a door. A test pins the interpolation *and* forbids "48h" coming
back. Also verified before writing: online check-in stamps `guest_checked_in_at` and is **not** the operator's
authoritative check-in · Manage creates **pending requests** the property confirms · payment is **hosted Stripe
Checkout**, no stored card · the wallet **cannot overdraw** and expired credit stops counting · a review needs a
**completed** stay · deletion is a **reversible 14-day** schedule after which personal details go and
**anonymised financial records remain**.

**Omitted rather than guessed (honest absence):** a **cancellation-refund window** — it is per-property
(`DO-G12-1`), so the entry points at the booking's own Manage page instead of stating a number that would be
wrong for some guests.

### ⚠️ PURE FAQ — nothing of the support half was built

No phone, no WhatsApp, no opening hours, no reply time, no 24/7 line — **and no slot, placeholder or commented
seam for them either.** None has a backend, and a help page promising support that will not answer is the class
of false claim `DO-EMAIL-9` records. **The ONE contact is the real relay** (`guest.messages`) in a "Still need
help?" card. A **negative test** asserts none of the forbidden affordances renders, and that no `tel:`/`callto:`
link exists anywhere in the markup.

### Verified as the GUEST (N.14.1), both a11y axes at birth

Rendered live at `/guest/help` as `demo.traveller`. **Keyboard:** 28 focusable controls, **0 unnamed** under a
strict accessible-name computation (no fallback to `name`/placeholder); a **real Tab** press confirmed
`:focus-visible` fires, and each custom control (search wrapper, chips, `<summary>`, relay) has a matching
outline rule. **Contrast:** pixel-sampled with two self-validating anchors on each plate — card `#FEFBF9`
(`#2B2320` 14.94 pass / `#B7ADA4` 2.14 fail) — across the populated state, the **forced open-accordion** state
(the answer text does not exist until `<details>` is open — G10) and the no-results state: **0 failing runs**.
⚠️ The chips sit on their own plates above the card and were measured there separately: ON `#B03808` on
`#F4DCCC` **4.67**, OFF `#5F5853` on `#FCF6F2` **6.52**. Greyscale: the active chip is not hue-only — it also
carries `aria-current`, a filled background and weight 600.

⚠️ **N.14.2 earned its place immediately:** the page reused `.vy-sr-only` for the search label — **and the guest
shell does not define it.** An undefined visually-hidden class hides nothing; it would have rendered the label
as stray text. Defined locally instead of assumed. Every other reused class and token was checked to exist.

### 🔒 Guard — `tests/Feature/GuestHelpCentreTest.php` (7 tests / 165 assertions)

Every engine entry reaches the page · the arrival window interpolates · the relay is real · **no support
affordance** · search + category filter and the honest empty state · guest-guarded · one engine, no fork.
**Mutation-proven four ways**, each red on its own assertion: hardcode a question · type the window as a literal
· slip in a reply-time promise · repoint the relay button.

⚠️ **TWO of those mutations passed on the first attempt, and both were faults in the GUARDS, not luck** (O32):
one edit **had not landed** (a shell-escaping slip — caught by grepping the anchor, G7/G11), and the relay
assertion was **VACUOUS** — `assertSee(route('guest.messages'))` stayed green with the button repointed, because
the shell's nav links Messages on every guest page. Now scoped to the "Still need help?" card. ⚠️ **The negative
support assertion also fired a false positive first**: it scanned raw HTML and hit the word "PHONE" in a CSS
comment in the shell (a *breakpoint* heading — **CSS comments ship**, the repo's known trap). It now asserts on
the **visible text**, which is what "renders a support affordance" actually means.

**Status: `DO-G13-1` TAKEN — the FAQ centre is BUILT. The support half remains unbuilt and unstubbed.**

---
---

## §SUPPORT-BACKEND — `DO-G13-1`'s SUPPORT half · **BUILT** (2026-08-12)

*The founder's ruling was **build the backend first** — not don't-build, and not ship-the-UI-over-nothing. This
is that backend, plus the two surfaces it makes honest. Three layers, one commit.*

### What shipped

| layer | detail |
|---|---|
| **schema** | `2026_09_22_000000_add_property_support_contact_columns` — `support_phone` (32) · `support_reply_norm` (32) · `support_hours` (120), **all nullable, no defaults**, on `properties`. Reversible (`down()` proven by an actual rollback + re-migrate). Per-property, so the tenant perimeter is `properties.organization_id` unchanged — **no new scope**. |
| **operator UI** | a **Guest support** section on `/app/settings` → `POST /app/settings/support` (`can:properties.manage`) → `SettingsActionController::updateSupport` → the certified `SettingsService::updateSupport` (audited by FIELD NAME, never value). Three inputs; an emptied box is written back as **NULL**. |
| **guest UI** | a conditional support block under "Still need help?" on `/guest/help`, one card **per property the guest has actually booked**, each **named**. |
| **the shared derivation** | `App\Support\Guest\PropertySupport` — read by BOTH surfaces, so the sentence an operator picks is the sentence a guest reads (§N.8f). |

### ⚠️ The honesty rules, and where each one LIVES

- **Honest absence is the RETURN TYPE, not a discipline.** `PropertySupport::for()` returns **null** when nothing
  is populated, and null per key otherwise. A property with nothing set contributes **no card at all** — the page
  is byte-equivalent to the pure-FAQ version it shipped as. There is no "not provided", no blank row, no
  placeholder anywhere in the template. A whitespace-only column counts as unset.
- **The norm cannot become a guarantee.** The stored value is a KEY into a fixed catalogue; the wording lives in
  code and every phrase begins *"usually"*. An operator picks a bucket, never types a sentence, and
  `Rule::in(array_keys(REPLY_NORMS))` refuses a hand-posted *"guaranteed within 1 hour"*. This is the §N.8d /
  `DO-EMAIL-9` class closed **structurally** (§N.10b), not by helptext.
- **Nothing is measured.** The norm is what the operator says they aim for; no reply-time statistic exists and
  none is presented as a commitment.
- **No "24/7" is hardcoded, suggested or defaulted.** Hours are free text because a real desk's hours are not an
  enum — if a property is round-the-clock, someone typed that.
- **No fabricated defaults, and no demo seed.** The columns start NULL everywhere including the demo org. A
  default would ship an operational promise nobody made, in the one place a guest will act on it.

### 🚩 `DO-SUP-1` — PHONE, not WhatsApp (a scope correction to §N.15.6)

The spec said *"a stored phone / WhatsApp per property"*. **One number is stored and it renders as a telephone
(`tel:`) only.** Nothing in the product verifies that a given number answers on WhatsApp, so drawing a WhatsApp
affordance over it would assert a channel that may not exist — the fabricated-affordance shape (#39), in the
same family as the reply-time promise this build exists to avoid. A real WhatsApp affordance needs either a
second column the operator ticks deliberately or a verification step; **logged, not smuggled in.**

### 🔒 Guard — `tests/Feature/GuestPropertySupportTest.php` (10 tests / 89 assertions)

**Mutation-proven seven ways**, each red on its own guard and green on restore (`php -l` + `view:clear` after
every edit):

| # | mutation | result |
|---|---|---|
| 1 | the phone row's `@if` gate → `@if (true)` | **RED** ×2 (partial-state + disappearance) |
| 2 | `PropertySupport::for()`'s null return removed | ⚠️ **PASSED first** — see below |
| 3 | a catalogue phrase → *"guaranteed within 1 hour"* | **RED** ×2 |
| 4 | the guest read widened to EVERY property | **RED** (isolation) |
| 5 | the reply-norm `Rule::in` dropped | **RED** (a guarantee becomes postable) |
| 6 | the writer skips nulls instead of storing them | **RED** (a cleared field would keep advertising) |
| 7 | the writer takes a request-supplied property id (`withoutGlobalScopes`) | **RED** (cross-tenant) |

⚠️ **Mutation 2 is the one worth recording.** It passed — which by O32 means vacuity **or redundancy**, and here
it was redundancy: the three rows are each individually gated, so removing the card-level null return left every
row-level assertion green while the page would have rendered an **empty, property-named card** — a placeholder
by another name, exactly what the spec forbids. The guard now asserts **no `vy-sup` markup exists at all** in the
unconfigured state (with `<style>` stripped, since the stylesheet defines those class names). Re-run, the
mutation goes red. *A passing mutation is a finding, not a formality.*

Also guarded: absence assertions read **visible text** (style/script stripped) — and a first cut of one fired on
an ordinary **em-dash** in the FAQ prose, a token so common it tested the page's punctuation rather than its
honesty. The relay/property-name assertions are **scoped to the support card**, not the page (the shared nav and
the guest chrome both satisfy a page-level `assertSee` — §N.15.3a).

### ⚠️ A DEFECT THE BROWSER CAUGHT AND THE TEST DID NOT

The catalogue first stored bare intervals (*"usually within a few hours"*) and the template renders
`We {phrase}.` — so the page read **"We usually within a few hours."** The guard asserted `toContain('We
usually')`, which the broken sentence satisfies. Caught by rendering it. The phrases are now complete predicates
(*"usually reply within a few hours"*) and the guard asserts the **whole composed sentence** per catalogue entry.
*A prefix assertion tests a prefix; render the thing.*

### Verified as BOTH roles (N.14.1)

- **As the operator** (a `Property Owner`, the role that holds `properties.manage`): the section renders, the
  select offers only the four soft norms plus "Don't show a reply time", and a real save through the form wrote
  `support_hours` and left the other two NULL — then a second save set phone + norm. The audit row records
  `{"fields":["support_phone","support_reply_norm","support_hours"]}`, names only.
- **As the guest**: unconfigured → **0 support cards, 0 `tel:` links**, page ends at the relay · hours-only →
  exactly one row (`HOURS`), no phone label, no reply line · fully populated → three rows, the card headed
  **DEMO SEASIDE PROPERTY**.
- **Contrast** — pixel-sampled on the real render with the self-validating anchor reading **5.18** (the published
  white-on-solid-accent figure), entry animations frozen by **completing** them, 0 transparent elements before
  capture: property name / all three labels **6.73** · both values **14.83** · the `tel:` link **5.92**. **0 failing runs.**
  Greyscale-safe: every row is distinguished by its label, never by colour.
- **Keyboard** — 39 focusables, **0 unnamed** under a strict accessible-name computation (no fallback to
  `name`/placeholder). The dial link is reached by a **real Tab** press (not `el.focus()`, which does not set
  `:focus-visible`), genuinely matches `:focus-visible`, paints a `solid 2px rgb(194,65,12)` outline, and is named
  *"Call Demo Seaside Property on +351 289 000 114"* — the number alone would have read as *"link, plus three
  five one…"* with nothing to choose between two properties.

---

## §EMAIL-FOOTER-RESTRUCTURE — `DO-EMAIL-9` + `DO-EMAIL-8` · **CLOSED** (2026-08-12)

The shared email layout ended every template with ONE hardcoded line — *"You're receiving this because it
relates to your booking with us."* — and `owner-email-change`, an owner changing their **login email**, shipped
it live. A shared layout's default copy is content, and it can be false (§N.14.8).

### ⚠️ Why a REWORD was the wrong fix (the founder's call, and the reasoning holds up)

Any single sentence sitting in that slot is wrong for **some** category, and the next template added inherits it
silently. So the reason is now **derived**: a template declares a **KEY**, the layout owns the wording.

```
@extends('emails.layout')
@section('footer_reason', 'security')
```

**A false reason is not something a template can type** — the same structural honesty the guest support
reply-norm uses (`PropertySupport::REPLY_NORMS`). Two builds in a row have now reached for this shape; it is
becoming the house pattern for *"copy that must stay true"*.

### ⚠️ The default is GENERIC-TRUE — this is the actual fix

| state | line |
|---|---|
| declares nothing | *"…because you have an account with us."* — true of anyone we may legitimately email |
| declares an unknown key | the same generic line (**never** the raw key) |

**A forgetful new template still cannot claim a booking.** That is the whole difference between this and a
reword, and it is the test that would fail first if the fix regressed.

### The catalogue, and who declares what (all 16 extenders enumerated)

| category | line | templates |
|---|---|---|
| `booking` | relates to your booking with us | booking-confirmation · cancellation · refund-confirmation · payment-reminder · pre-arrival · review-request |
| `security` | of a security change on your account | guest-password-reset |
| `email_change` | this address was given as the login email for a Vyrobel account | **owner-email-change** · admin-email-change |
| `account` | relates to your account with us | account-deletion · verify-email · maintenance-cost-report |
| `waitlist` | you asked to hear about these dates | waitlist-notification |
| *(none → generic)* | you have an account with us | notification · winback\* |

\* `winback` is pure marketing and supplies its own `footer_marketing` block with a REAL signed opt-out.
`operator-guest-message` declares **`booking`** and has TWO modes: with an unsubscribe URL it fills
`footer_marketing`; without one it is an ordinary service message and falls through to its category.

⚠️ **That last one was classified WRONG at first** (left generic) and `MarketingUnsubscribeTest` — a
pre-existing green test — caught it: a property writing to a guest about their stay really does relate to their
booking. *A test that looks like collateral damage may be encoding an invariant; read it before changing it.*

⚠️ **`email_change` exists because of a finding, not tidiness.** The obvious move was to declare `security` on
both change emails. But an email-change confirmation is sent to the address being **ADDED**, whose reader may
hold no account at all yet — so *"a security change on your account"* is not safely true of them. The one-off
override `DO-EMAIL-3` wrote had carried exactly this nuance, and flattening it into `security` would have been a
small honesty **regression** dressed as consolidation. The category says only what is certain: someone gave us
this address. *The old test defending that sentence is what surfaced it.*

### `DO-EMAIL-8` — a premise correction: OVERLOADED, not just misnamed

The register called the slot *misnamed*. It was **doing two jobs**: a genuine marketing opt-out block
(`winback`, `operator-guest-message`) **and** a reason override (`admin-email-change`). Renaming alone would
have re-conflated them, so it was **split** — `footer_reason` (a key) and `footer_marketing` (the opt-out
block). `@section('unsubscribe')` greps to **zero**. A transactional email offers no opt-out, because nobody can
unsubscribe from a security notice about their own account.

### Verified by rendering, as the role

**11 of 16 rendered as real Mailables:** the 9 through the operator Email-Previews route (6 booking → the
booking line · waitlist → its own line · winback → the marketing block with its real link · verify-email →
account) plus the two change emails rendered directly. The remaining 5 are covered by the enumerated
declaration guard plus the layout-catalogue render. **`owner-email-change` before/after is captured** —
identical message, CTA and URL; only the footer line changes.

### 🔒 Guard — `tests/Feature/EmailFooterReasonTest.php` (12 tests / 97 assertions)

Every category's line · the **generic-true default** · an **unknown key** falling back rather than rendering raw
· `owner-email-change` specifically · the enumeration (a template added later fails as an unknown key) ·
content-survived · `DO-EMAIL-8`. **Mutation-proven four ways** — regress the default to the booking line, drop
`owner-email-change`'s declaration, make an unknown key render raw, restore the old slot name — each red on its
own guard.

⚠️ **Two guard bugs caught while writing it, both of the house's known families:** `toHaveKey($name, $msg)`
silently asserts a *value*, not a message (the §N.8c API-misuse family, in a new guise); and a source-level scan
fired on a **Blade comment** quoting the old line — the *inverse* of the CSS-comment trap, since Blade comments
never ship and must be stripped before scanning. ⚠️ And one mutation verdict was briefly untrustworthy because
the *verification grep* was broken by shell escaping — re-run with an asserted anchor. **The check must be as
trustworthy as the thing it checks.**

---

## §AMBER-TEXT-RECONCILIATION — `DO-EMAIL-4` · **CLOSED** (2026-08-12)

`--vy-amber-text` was declared **twice with different values** — guest `rgb(126,88,17)` = `#7E5811`, owner
`rgb(128,76,5)` = `#804C05`. A `var()` resolves to whichever shell is in scope, so a shared component would have
picked up the wrong one, and *"the amber-text value"* was ambiguous enough to infect `DO-EMAIL-1`'s record.

### The measurement decided it (§N.8e — three anchors, all reading exactly 5.18)

Both values scored against **every plate either is rendered on, in BOTH realms**, pixel-sampled from the real
renders with text hidden:

| realm | site | plate | `#7E5811` | `#804C05` |
|---|---|---|---|---|
| guest | "Balance due" badge | `rgb(249,234,213)` | 5.40 | **6.01** |
| guest | €184.50 on the card (×2) | `rgb(254,251,248)` | 6.19 | **6.90** |
| owner | Team "Save" (×2) | `rgb(254,252,251)` | 6.24 | **6.95** |
| owner | pending-invite row — **the worst plate** | `rgb(250,213,186)` | **4.65** | **5.18** |
| owner | "Resend" row | `rgb(253,245,238)` | 5.92 | **6.59** |
| **worst, all plates** | | | **4.65** | **5.18** |

**Both clear AA (4.5), so the branch is UNIFY, not rename** — and the value with the headroom wins. `#804C05` is
strictly darker, so on every light plate it scores **higher**; there is no amber-text use on a dark plate
(checked), which is what makes that a safe generalisation beyond the seven sites sampled.

⚠️ **The probe tried to lie once.** Two owner samples sat below the viewport and `imagecolorat` returned black,
scoring them 3.29 / 2.95 — a *failure* that never happened. Caught because the reading was implausible against
its neighbours; re-measured after scrolling them into view. **An out-of-bounds read is not a measurement**
(§N.8e — when the probe breaks, publish nothing).

### ⚠️ It was ACCIDENTAL — and that is the point

History settles it: the guest value arrived at the guest Reservation gate (`4040b0f`) and the owner value at the
Owner Statement gate (`656cc34`). Two passes, months apart, each derived an amber that read on *their* plates.
Neither knew about the other. Same intent, two answers, one name.

⚠️ **PREMISE REFINEMENT — "declared per shell" is NOT itself the defect.** A scan found **8** tokens differing
across the three shells, and **7 are deliberate**: the guest realm runs smaller radii (`--vy-r-lg/-sm`) and a
lighter `--vy-shadow`, `--vy-glass-border` is warm-dark on guest and white on owner/operator, and
`--vy-on-grad` / `-soft` were measured **separately per shell on purpose** (§N.4i / §N.4j). Per-shell theming is
the design system working. What was wrong is narrower: **this** token carries one intent — amber text on a light
warm plate — and had no reason to differ. **The guard is therefore scoped to this token; a blanket
"all tokens must agree" rule would be false against the design.**

### What changed (contrast / token-definition only)

- the **guest shell** adopts `rgb(128,76,5)`; the owner shell is unchanged and annotated.
- the **two email literals** (`pre-arrival`, `payment-reminder`) move `#7E5811` → `#804C05`. Emails cannot read a
  token, so they carry a literal — but it must be *the* value, or the ambiguity just relocates. Strictly an
  improvement on every email plate (5.73 → **6.39** on `#FBF1E9`; worst email plate `#F2E8DF` 5.28 → **5.89**).
- the **owner-remittance PDF** already used `#804C05` — a confirmation, not a change.
- `#7E5811` now greps to **zero** across all views.

**Visual shift:** guest amber text becomes very slightly deeper and less yellow (`126,88,17` → `128,76,5`).
**Greyscale-safe** — verified in a greyscale render: the "Balance due" badge and the balance figures stay legible,
carried by luminance and by their own words, never by hue.

### 🔒 Guard — `tests/Feature/AmberTextTokenTest.php` (10 tests / 26 assertions)

One name → one value (with a non-vacuity check that it is still declared at all) · AA on all eight plates it
renders on across both realms and email · the email/PDF literals tracking it · `#7E5811` gone. **Mutation-proven
twice:** re-adding the old guest value (the landmine returning) reds the single-value guard; lightening the
canonical value to `#B26A08` reds the AA guard on every plate.

### 🚩 Logged, not fixed — the structural root

Each shell re-declares the whole palette, so **any** token can diverge this way; nothing but a test stops the
next one. A shared token source (one declaration, included by all three shells) is the §N.8f fix, and it is a
refactor across three shells rather than a contrast item — out of scope here, recorded as **`DO-TOKEN-1`**.

---

## §ROLE-COVERAGE-RE-AUDIT — `DO-EMAIL-5` (partial) + `DO-EMAIL-6` (closed) · 2026-08-12

⚠️ **§N.14.1 turned into a repeatable audit.** The bound it names — *a contrast pass is bounded by the ROLE it
runs as* — is useless as a slogan; this is the procedure that discharges it.

### THE METHOD (reusable — the general answer to "is X fixed everywhere?")

1. **Enumerate the roles and their permission sets** (`config/authorization.php`).
2. **Identify which roles prior passes ran as.** Here: the demo **Hotel Manager** (operator) and **External
   Owner** (owner). Everything else is uncovered.
3. **Diff.** Compute the permissions the covered roles LACK — that set is the whole audit target, and it is far
   smaller than "re-measure everything as everyone" (which is combinatorial and never gets done).
4. **Grep the gated markup** for those permissions (`@can` / `@canany` / `$canX` view variables).
5. **Render as a role that PASSES the gate**, force the conditional on top of it, pixel-sample with anchors.
6. **Map failures to the token the PLATE requires** — not the token a sibling used.

### THE ROLE MATRIX

| role | covered by a prior pass? | holds |
|---|---|---|
| **Hotel Manager** | ✅ every operator contrast pass | 37 perms — **not** users/properties/settings/billing.manage |
| **External Owner** | ✅ the owner passes | `owner_portal.view` |
| **Property Owner / Group Admin** | ❌ **never rendered as** | `*` — incl. all four above |
| Accountant · Revenue Manager · Front Desk · Reservation Agent · Housekeeping · Maintenance · Marketing · Guest Support | ❌ never | subsets of Hotel Manager (⇒ little new markup) |

**The delta = markup gated on six permissions Hotel Manager lacks:** `users.manage` · `settings.manage` ·
`properties.manage` · `payouts.manage` · `payments.refund` · `guests.erase` — **12 gated blocks across 10 views.**

### WHAT THE AUDIT FOUND (rendered live as **Property Owner**, anchor 5.18 on every capture)

| # | surface | state forced | ink | plate | ratio | verdict |
|---|---|---|---|---|---|---|
| 1 | invite modal labels (`.vy-inv-lbl` ×4) | `users.manage` gate **+** Alpine `open` | `#9C9089` | `#F8F2ED` | **2.80** | ✅ FIXED → `--vy-muted` |
| 2 | invite modal *"· optional"* | same | `#B7ADA4` | `#F8F3EF` | **2.00** | ✅ FIXED → `--vy-faint` |
| 3 | storefront filter keys ×3 | a performed **search** | `#9C9089` | `#F2B794` | **1.77** | ✅ FIXED → `--vy-ink` |
| 4 | owner Rates *"· optional"* | not reachable in demo | `#B7ADA4` | — | — | ✅ fixed **by identity** with #2 |

⚠️ **#2 was never named by anyone** — the register knew only about `#9C9089`. It is the *worse* of the two
(2.00), and it surfaced only because the pass rendered the modal as a role that could see it.

⚠️ **#3 IS THE MORE INTERESTING FINDING, AND IT WIDENS §N.14.1.** Those labels are **not** permission-gated —
they are on the PUBLIC storefront, gated on `$search['searched']` **and** on the property having filter data.
**A surface can be invisible because of DATA, not only authorization or UI state.** G10 named the conditional
STYLE; N.14.1 named the conditional SURFACE via authorization; this is the third: **the conditional surface via
DATA.** And the plate decided the token — those labels sit on the **peach gradient**, where `--vy-muted` also
fails (3.98) and only `--vy-ink` clears (8.78). *The precedent token was the wrong answer; the measurement gave
the right one* (§N.13.4).

### `DO-EMAIL-6` — the two standalone pages · **CLOSED**

⚠️ **Premise correction: their TEXT was never failing.** The register's concern was maintainability (they carry
literals because they cannot reach `--vy-*`), and that reading was right — every text literal passes on the
worst stop of their page gradient: feature-locked `#B4400F` **4.86** · `#5F5853` **5.95** · `#2B2320` **13.12**;
login-approval `#4A4038` **8.60** · `#7a5200` **5.90**.

**What DID fail is the CTA on both: white 14px on `linear-gradient(140deg,#E8703A,#C2410C)` = 3.08** at the
lighter stop — the same construct `DO-EMAIL-1` fixed in the emails and the guest shell already documents
("SOLID, not `--vy-grad`"). Both now use the **solid accent, 5.18**. Gradient kept on the brand mark, which
carries no text.

### FIXED vs LOGGED — honest incompletion

**FIXED (deep, mutation-proven):** the invite-modal cluster (2 values, 5 sites), the storefront filter keys, the
owner-Rates twin, and both standalone CTAs. `#9C9089` and `#B7ADA4` grep to **zero** as live colours.

**🚩 LOGGED — `DO-EMAIL-5-continued`:** the remaining gated blocks were **not** rendered as their role in this
pass. Each needs the same treatment (render as the role · force the state · sample):

| gate | views | role needed |
|---|---|---|
| `settings.manage` | `operator-security` ×2, `operator-settings` (org block) | Property Owner |
| `properties.manage` | `notification-preferences`, `email-previews`, `email-templates` | Property Owner |
| `payouts.manage` | `payout-run` | Accountant / Property Owner |
| `payments.refund` | `reservation-detail` | Accountant / Property Owner |
| `guests.erase` | `guests-list`, `guest-profile` | Property Owner |

⚠️ Plus the third bound this pass discovered: **data-conditional surfaces across all realms have never been
swept** — the storefront filter row is one instance found by accident, not by search.

### 🔒 Guard — `tests/Feature/RoleCoverageContrastTest.php` (11 tests / 30 assertions)

⚠️ **The role-aware one is the point:** it renders `/app/owners` as BOTH a Hotel Manager and a Property Owner
and asserts the invite markup is **absent** for the first and **present** for the second — so the coverage bound
is demonstrated, not described, and the guard cannot quietly become vacuous by running as the wrong role.
**Mutation-proven twice** (revert the label token → red; restore the CTA gradient → red).

⚠️ **Two guard bugs caught, both of the house families:** spatie's team id must be set before `can()` outside a
request (without it BOTH premise assertions pass for the wrong reason — a vacuous "proof"); and a document-wide
absence scan fired on a **CSS comment** in the operator layout that documents `#9C9089` while explaining its
removal. Scoped to the CSS **rule** instead. *Written, as it happens, by someone who had just written that trap
down.*

---

## §HK-STATUS-DETERMINATION — `DO-EMAIL-7` · **CLOSED** (2026-08-12)

⚠️ **The token was VERIFIED-FINE. The measurement found the defect standing next to it.**

`--vy-hk-ooo` was logged as *"a status fill — a design decision, not a contrast fix"*. `DO10-4` had since
established that a status carried by COLOUR is **correctness** (1.4.1), not tone — so it was re-opened to
measure. Rendered as a **Property Owner** on `/app/housekeeping` with a **real out-of-order room** present
(no state forcing needed), anchor **5.18** on every capture:

| question | measurement | verdict |
|---|---|---|
| Is colour the sole carrier? | every chip prints its **word** — "Out of order 1" — and OOO rooms are not drawn as cards at all, so `--vy-hk-ooo` renders in exactly ONE place: a dot beside its own label | **1.4.1 SATISFIED** |
| Does the fill clear 3:1 as a graphic? | dot `rgb(138,128,121)` on `rgb(250,235,226)` = **3.31** | **PASSES** (and it is redundant, so the bar does not strictly apply) |
| Greyscale? | verified in a greyscale capture — all five statuses read by word | **SAFE** |

**So `--vy-hk-ooo` needed no change, and none was made.** A flagged item that turns out to be sound is an
honest close; inventing a fix for it would have been the failure.

### ⚠️ WHAT THE SAME MEASUREMENT FOUND — the thing beside it (N.13.1, again)

The tally chip is `--vy-glass`: **translucent over the page gradient**, so its plate moves with its position
(§N.13.4). And `.vy-hk-tal.is-zero` dropped **the whole chip** to `opacity: .62` — dimming a status WORD.

| run | plate | ratio |
|---|---|---|
| label `--vy-muted`, light part of the band | `rgb(250,235,226)` | 6.00 ✅ |
| label `--vy-muted`, **saturated part** | `rgb(246,190,160)` | **4.25 ❌ at FULL opacity** |
| label `--vy-muted` **at `.62`** | same | **2.28 ❌** |
| label `--vy-ink` (the fix) | worst plate | **9.38 ✅** |

⚠️ **Two lessons in one rule.** *Opacity dimming applied to a chip dims its TEXT* — and the empty state was
already carried by the "0" beside the word, so the dimming was redundant *and* harmful. And **a token that
passes where you sampled can fail elsewhere on the same translucent band**: `--vy-muted` was not wrong by
carelessness, it was wrong by **position**.

**Fixed:** the label reads `--vy-ink`; the de-emphasis moved to the dot (`.is-zero .vy-hk-dot { opacity:.45 }`),
a redundant non-text mark. Status semantics, data and the state machine untouched.

### 🔒 Guard — `tests/Feature/HousekeepingStatusContrastTest.php` (3 / 20)

Pins **both** outcomes: that every `--vy-hk-*` token appears in a chip carrying its status WORD (so the
verified-fine state cannot regress to colour-only), and that the dimming never returns to the text.
Mutation-proven: restoring `.is-zero { opacity: .62 }` reds 2 of 3.

---

## §DO-TOKEN-1-INVENTORY — the verified palette diff (2026-08-12, read-only; the refactor NOT started)

⚠️ **The refactor was not started** (see `DECISIONS.md`). What exists is the step the task says must not be
assumed from `DO-EMAIL-4`: a **fresh full-palette diff across every shell that declares `--vy-*`**. It is
read-only and complete, and it corrects the brief twice.

### ⚠️ PREMISE CORRECTION 1 — there are **FOUR** shells, not three

`components/public/shell.blade.php` (the storefront) also declares the palette, alongside guest / owner /
operator. **`DO-TOKEN-1` is a four-shell refactor.** Anything scoped to three would leave the public storefront
re-declaring everything — the drift root still open on the one realm that faces the public.

### The inventory — 51 distinct tokens

| class | count | meaning |
|---|---|---|
| **SHARED** (declared in >1 shell, identical) | **31** | move to the base — one definition each (§N.8f) |
| **DIVERGENT** (declared in >1 shell, differ) | **7** | the override-or-drift decision set (below) |
| **SINGLE-SHELL** | 13 | already unambiguous; stay with their shell |

### ⚠️ PREMISE CORRECTION 2 — the 7 are not scattered; they are almost entirely **"guest is its own theme"**

| token | shape | guest | owner / operator / public |
|---|---|---|---|
| `--vy-glass-border` | guest-only | `rgba(120,70,40,.12)` warm-dark | `rgba(255,255,255,.75)` |
| `--vy-r-lg` | guest-only | `18px` | `20px` |
| `--vy-r-sm` | guest-only | `9px` | `10px` |
| `--vy-shadow` | guest-only | lighter, no inset | heavier + inset highlight |
| `--vy-shadow-accent` | guest-only | `.28` alpha | `.30` alpha |
| `--vy-on-grad` | **owner vs operator** | — | owner `rgb(76,66,60)` · operator `rgb(84,74,68)` |
| `--vy-on-grad-soft` | **owner vs operator** | — | owner `rgb(82,73,67)` · operator `rgb(88,78,72)` |

**Five of seven are the guest realm deviating from a consensus the other three shells already share**, and the
remaining two are the `on-grad` pair measured **separately per shell** against each gradient (§N.4i / §N.4j).

> ⚠️ **This shapes the refactor.** The base should be the owner/operator/public consensus; **guest is the one
> shell needing a real theme-override layer**, plus an owner/operator `on-grad` pair. That is a much smaller and
> more honest structure than "three shells each with overrides" — and it means the override layer has a
> *reason a designer would recognise* (the guest realm is deliberately softer and rounder), not seven unrelated
> exceptions.

⚠️ **No token was reclassified and no value was touched.** Whether each of the 7 is a deliberate override still
needs its per-token design justification confirmed before the base is built — `--vy-amber-text` looked exactly
like these until it was measured and turned out to be drift.

---

# 🗂 THE CONSOLIDATED DO-REGISTER — the BACKEND PHASE's backlog (SAVE v8, 2026-08-12)

**This is the reconciled index of every `DO-*` item raised by the guest realm's 12 gates, the cross-realm
PDF sweep, and **G13's three no-route verdicts**.** Each item's full reasoning stays in its own §G / §PDF section
above — this groups them **by kind** so the backend phase can be planned against them, and marks what the walk
itself already **CLOSED**.

⚠️ **43 items were raised by the 12 gates + the PDF sweep, not the ~18 an earlier summary listed.** Verified by
enumeration, not memory. **G13 then added 3 verdict items (`DO-G13-1/2/3`) and RETRACTED 1 (`DO-G7-2`) → 45.**

⚠️ **SAVE v9 reconciliation:** the **cleanup sequence CLOSED 7** (`DO-INV-4` · `DO-EMAIL-1` · `DO-EMAIL-2` ·
`DO-EMAIL-3` · `DO10-4`/`DO-G8-1` · `DO-G4-2` · `DO-G8-2`) and **opened 7** (`DO-EMAIL-4` … `DO-EMAIL-9`,
`DO-STAR-1`) — see the two index blocks below. ⚠️ **Do not read the totals as a burn-down:** every pass so far
has opened items it could only see once it started, and **§N.14.1 says more exist behind roles no pass has run
as.** The register is a live map, not a countdown.

## ✅ CLOSED DURING THE WALK (recorded so they are not re-opened)

| item | closed by |
|---|---|
| **`DO-G2-2`** · `--vy-amber`-as-text (logged at G2 as an unmeasured *prediction*) | measured and fixed — G6 (payments/receipt/extras), G8 confirmed the count |
| **`DO-G6-2`** · the amber tail — `saved.blade.php` the last site | **G10** (4.08 → 6.15). Grep-verified: **no `color:var(--vy-amber)` remains anywhere in the live guest realm** |
| **`DO-G2-1`** · `rgb(70,120,60)` hand-written at ELEVEN guest call sites | the **live** sites are unified on `--vy-green-text` (G9, G10). Only the dead partial remains → `DO-G10-4` |
| **`DO-G3-1`** · the realm-wide PDF sweep (5 guest templates + the stamp) | the **PDF-sweep gate** — 17 templates, 224 inks → 0 failures |
| **`DO10-4`** · the ★ migration | G8 migrated the guest review + the owner hero; **G12 fixed `ReviewList::stars_off`** (it returned the FILLED glyph). ~~⚠️ The remainder is `DO-G8-1` alone~~ → ✅ **FULLY CLOSED by the capstone (`ee6be74`)** — see the cleanup index below. |

## ✅ CLOSED BY THE **CLEANUP SEQUENCE** (SAVE v9 · 2026-08-12 · `e869d5d`→`ee6be74`)

*Five passes after the walk. Each has a full record above; this is the index, so none of them is reopened.
The cross-pass synthesis is **§N.14** in `PROJECT-STATE.md`.*

| item | closed by | record |
|---|---|---|
| **`DO-INV-4`** · the email-contrast sweep | `e994dc6` — 20 templates, **332 runs / 120 FAIL → 10**; ⚠️ **THREE greys, not the two the scoping note named** (it had narrowed the item past `#A99E95`, its own founding literal — §N.14.7) | **§EMAIL-SWEEP** |
| **`DO-EMAIL-1`** · the six remaining email literals | `b940cda` — **10 FAIL → 1**; ⚠️ `#E8EDF6` was a **probe error, not a defect** (a 6-digit-hex regex could not read `color:#fff` — §N.14.6) | **§EMAIL-SWEEP-2** |
| **`DO-G4-2`** · the pre-arrival `#8A7A6A` | `b940cda` — measured **3.72** (the register had carried it as *unmeasured* since G4) → `#5F5853` | **§EMAIL-SWEEP-2** |
| **`DO-EMAIL-2`** · the three greys on operator/owner **web** | `d517187` — **28 occurrences / 9 files where 13 were named**; root cause **token drift**, so mapped to the **tokens**; ⚠️ **a COVERAGE gap, not an immature probe** (§N.14.1) | **§WEB-GREY-SWEEP** |
| **`DO-EMAIL-3`** · the off-brand platform-admin email | `ac502bf` — **all 20 emails now on-brand**; a SECURITY email, so the content is proven **verbatim**; ⚠️ `@extends` is not the brand test (five templates are standalone, four on-brand) | **§ADMIN-EMAIL-BRAND** |
| **`DO10-4` / `DO-G8-1`** · the star capstone | `ee6be74` — the **O26 defect family RETIRED**: every rating row floors, differs by GLYPH, carries one accessible name, clears **3:1 on the worst plate it renders on** | **§STAR-CAPSTONE** |
| **`DO-G8-2`** · the component's unmeasured `emptyColor` | `ee6be74` — **measured, and BOTH defaults failed** (2.08 / 2.25): *the component everything was migrated TO drew a star you could barely see* (§N.14.2). Now `#8F5A06` / `#7A6E64` | **§STAR-CAPSTONE** |

## 🏗 BUILT SINCE THE CLEANUP (SAVE v10 · 2026-08-12)

| item | state | record |
|---|---|---|
| **`DO-G13-1`** · Guest Help | ⚠️ **SPLIT — half BUILT, half SCOPED.** ✅ **The FAQ centre is BUILT** (`23bf335`, `GET /guest/help`) over the certified `HelpKnowledgeBase` — reused not forked, every answer verified against the code before writing, pure FAQ with the real relay as the one contact. ⏳ **The support half is SCOPED, not refused** — the founder ruled *build the backend first*. ⚠️ **So the 2026-08-07 refusal is reversed in one half and PRESERVED-BUT-CONDITIONAL in the other** (§N.15.5). | **§GUEST-FAQ** · §N.15 |
| **the SUPPORT BACKEND** | ✅ **BUILT 2026-08-12 — `DO-G13-1` IS NOW CLOSED IN BOTH HALVES.** Three nullable per-property columns (`support_phone` · `support_reply_norm` · `support_hours`) + a reversible migration → the **Guest support** section on `/app/settings` (`properties.manage`) → a conditional support block on `/guest/help`. ⚠️ **Honest absence is the SCHEMA**: NULL means the channel does not exist, and the guest page draws no card, no row, no placeholder. ⚠️ **The norm is a norm BY CONSTRUCTION** — a fixed catalogue key, so the sentence lives in code and always reads *"We usually reply…"*; a guarantee is not expressible and is rejected by validation. ⚠️ **Phone, not WhatsApp** (see `DO-SUP-1`). | **§SUPPORT-BACKEND** · §N.15.6 |

## ✅ CLOSED BY THE **BACKLOG SEQUENCE** (SAVE v11 · 2026-08-12 · `6550087`→`4440c7a`)

*Seven passes. Each has a full record above; this is the index, so none is reopened. Cross-pass canon: **§N.16**
in `PROJECT-STATE.md`.*

| item | closed by | record |
|---|---|---|
| **the SUPPORT BACKEND** (`DO-G13-1`'s second half) | `6550087` — 3 nullable per-property fields → operator setter → a guest block showing only what is populated. **Honest absence is the SCHEMA.** | **§SUPPORT-BACKEND** |
| **`DO-SEED-1`** | `f9142c8` — the seeder re-anchored to `now()`; ⚠️ the rot was **known and defended in one place out of two** (a forward-scan vs a comment). Baseline **9 → 7**. | **§DO-SEED-1** |
| **`DO-EMAIL-9`** | `52321b8` — the footer reason is **KEYED by category** with a **generic-true default**, so the false booking claim is *unrepresentable*, not merely removed | **§EMAIL-FOOTER-RESTRUCTURE** |
| **`DO-EMAIL-8`** | `52321b8` — ⚠️ premise sharpened: the slot was **OVERLOADED**, not misnamed → **split** into `footer_reason` + `footer_marketing` | **§EMAIL-FOOTER-RESTRUCTURE** |
| **`DO-EMAIL-4`** | `a70c147` — unified to **`#804C05`** (both cleared AA; the headroom value won). Accidental divergence, confirmed from history | **§AMBER-TEXT-RECONCILIATION** |
| **`DO-EMAIL-6`** | `2e8cd0f` — ⚠️ premise correction: the literals **passed**; the **CTA at 3.08** did not → solid accent | **§ROLE-COVERAGE-RE-AUDIT** |
| **`DO-EMAIL-5`** ⚠️ **PARTIAL** | `2e8cd0f` — the **method** recorded + guarded; 4 failures fixed incl. **`#B7ADA4` = 2.00, named by nobody**. ⚠️ Remainder → `DO-EMAIL-5-continued` | **§ROLE-COVERAGE-RE-AUDIT** |
| **`DO-EMAIL-7`** | `4440c7a` — ⚠️ **VERIFIED-FINE, unchanged and pinned**; the defect sat **beside** it (the tally chip dimming its own status word to 2.28) | **§HK-STATUS-DETERMINATION** |
| **`DO-G13-2`** · **`DO-G13-3`** | **RATIFIED** by the founder 2026-08-12 — Documents **DEFER**, Referral **DON'T-BUILD** (#39). Decisions, not recommendations; neither is a gap. **The G13 set is closed.** | `DECISIONS.md` |

## ⬅ THE **REMAINING BACKLOG** after the sequence (SAVE v11)

| priority | item | what |
|---|---|---|
| ⛔ **1** | **the BILLING / go-live blocker** (§G.1) | untouched by every chapter. **The founder's business decision — surface, never pick a side.** If launch is near it outranks all of this. |
| **2** | **`DO-TOKEN-1`** | each shell re-declares the whole palette, so any token can diverge (how `DO-EMAIL-4` happened). A shared token SOURCE — ⚠️ must **preserve the 7 deliberate per-shell overrides**, not flatten them (§N.16.5). Three-shell refactor. |
| **3** | **`DO-EMAIL-5-continued`** | the gated blocks behind `settings.manage` ×3 · `properties.manage` ×3 · `payouts.manage` · `payments.refund` · `guests.erase` ×2 — each needs a render **as its role** (the method: §N.16.1). ⚠️ **Then the DATA axis — never swept in any realm** (§N.16.2). |
| **4** | the tail | **`DO-SUP-1`** (WhatsApp needs a real per-number flag) · **`DO-STAR-1`** (⚠️ a **bookmark**, not a rating — do not fold into `DO10-4`) · `DO-PDF-1` · `DO-PDF-2` · `DO-G4-1` · `DO-INV-1/2/3` |
| **5** | the BACKEND PHASE proper | the full register below + the **6 reopened operator a11y gates** (§N.9) + the deferred-capability refusal sweep (§N.8b) |

## ⬅ THE CLEANUP SEQUENCE'S **OPEN SUCCESSORS** — grouped by kind

*Every one was surfaced BY a cleanup pass and deliberately not taken in it. Full rows live in their groups
below; this is the priority index.*

| kind | items |
|---|---|
| ✅ **`DO-EMAIL-9` CLOSED** (2026-08-12) | the layout's footer reason is now **DERIVED from a declared category**, so the false booking line is **unrepresentable** rather than merely removed. `owner-email-change` — the live offender — renders the truth. **`DO-EMAIL-8` closed with it** (the overloaded `unsubscribe` slot split into `footer_reason` + `footer_marketing`). See **§EMAIL-FOOTER-RESTRUCTURE**. |
| 🎨 **token / consistency** | ~~**`DO-EMAIL-4`**~~ ✅ **CLOSED 2026-08-12** — `--vy-amber-text` unified to **`#804C05`** (§AMBER-TEXT-RECONCILIATION) · ~~**`DO-EMAIL-8`**~~ ✅ closed with `DO-EMAIL-9` · ~~**`DO-EMAIL-7`**~~ ✅ **CLOSED 2026-08-12 — VERIFIED-FINE** (§HK-STATUS-DETERMINATION): the fill is sound (every status prints its word; the dot clears 3:1 at 3.31), so no change was made to it. ⚠️ The same measurement found the failure BESIDE it — the tally chip dimmed its status WORD to **2.28** — and that was fixed. |
| 🔍 **coverage re-audit** (the §N.14.1 bound) | ⚠️ **`DO-EMAIL-5` — PARTIALLY CLOSED 2026-08-12** (§ROLE-COVERAGE-RE-AUDIT: method recorded, 4 failures fixed incl. two nobody had named; the remaining gated blocks logged as `DO-EMAIL-5-continued`) · ~~**`DO-EMAIL-6`**~~ ✅ **CLOSED** (its text always passed; the CTA gradient at 3.08 did not) · original entry: — a **full operator/owner contrast re-audit**, run **across every role that can reach each surface**. A fourth grey (`.vy-inv-lbl #9C9089`, 2.84 — every form label in the invite modal) was found *the moment one pass looked past its scope*; the role-coverage bound says more permission-gated surfaces exist. |
| 🧱 **structural** | **`DO-TOKEN-1`** 🆕 — each shell re-declares the whole `--vy-*` palette, so any token can silently diverge (that is how `DO-EMAIL-4` happened). A shared token source included by all three shells is the fix; 7 tokens differ **deliberately** today, so it must preserve per-shell overrides rather than flatten them. · **`DO-EMAIL-6`** — two genuine standalone **web pages** (`billing/feature-locked`, `auth/login-approval*`) carry their own `<style>`, cannot reach `--vy-*`, and therefore hold literals that will not follow a future retune |
| ✅ **RATIFIED — no longer open** | **`DO-G13-2`** (Guest Documents) → **DEFER** and **`DO-G13-3`** (Guest Referral) → **DON'T-BUILD** (#39) were **ratified by the founder on 2026-08-12**, as recommended. ⚠️ They are now **decisions, not recommendations**: neither is built, and neither is a gap. ⚠️ A future session must not "finish" either — the Documents rollup already lives on `/guest/payments` (a ratified location divergence), and the referral programme's five missing subsystems include a **new guest-creation path**, which is a security-posture change, not a page. With `DO-G13-1` built, **the whole G13 set is closed.** |
| 📞 **channel verification** | **`DO-SUP-1`** 🆕 — the support number renders as a **telephone only**. A WhatsApp affordance would assert a channel nothing verifies (#39); it needs a deliberate operator flag or a verification step. See §SUPPORT-BACKEND. |
| 🏷 **verdict / naming** | **`DO-STAR-1`** — the owner Messages *"Starred"* SVGs still use `#E8A63A`. ⚠️ **A BOOKMARK, not a rating** — do not conflate it with the rating star or fold it into `DO10-4`; its state is also carried by a visible count. A design call. |

## 🔴 CORRECTNESS — a user is shown something untrue or unusable

| item | what |
|---|---|
| ~~**`DO-G8-1`**~~ ✅ | ~~`owner/unit-detail:338` rounds up.~~ **CLOSED 2026-08-12 — see §STAR-CAPSTONE.** Migrated to `<x-star-rating>`; verified live as the OWNER with the state forced to **4.67**: the old code printed **5 filled stars**, it now prints **4 + 1 empty**, named *"4.7 out of 5 stars"*. ⚠️ Premise correction: it was **not** colour-only (it already emitted ★ then ☆) — the defect was `round()` alone. |
| ~~**`DO-G8-2`**~~ ✅ | ~~the default `emptyColor` has never been measured.~~ **MEASURED AND IT FAILED — both defaults did.** `#E8A63A` **2.08** / `#B9A99A` **2.25**: the component everything was being migrated TO drew a star below the 3:1 non-text bar. Retargeted to `#8F5A06` / `#7A6E64` against the **worst real plate** (the peach `#FBD3BF` the live render revealed, where a first choice still failed). See §STAR-CAPSTONE. |
| **`DO-STAR-1`** 🆕 | the owner **Messages** *"Starred"* flag SVGs still use `#E8A63A`. **Not a rating** — a bookmark toggle whose state is also carried by a visible count, so out of `DO10-4`'s scope. A design call. |
| **`DO-G8-3`** | a review has no visible moderation timeline; a rejected review says "Not published" with no reason. |
| **`DO-G9-3`** | the language radio group fires a write on **every arrow-key step** (EN→FR = 3 writes). Low stakes, design's intent — logged, not silently redesigned. |
| **`DO-G11-2`** | the POST login route is named `guest.` (an empty suffix) — a trap for the next `route()` call. |

## 🔒 ISOLATION / BACKEND — a guard or a rule that does not exist yet

| item | what |
|---|---|
| **`DO-G10-3`** | `joinWaitlist()` is a bare `create()` — **no server-side dedupe**. The client guard is defence-in-depth, never a replacement. |
| **`DO-G10-2`** | a waitlist entry can **expire before the dates it waits for** (`WAITLIST_TTL_DAYS` runs from the join, not the stay). |
| **`DO-PDF-2`** | the audit stamp is `position:fixed` → its plate is **non-deterministic** (it prints over whatever the page ends with). The fix is moving it into each template's footer flow — **17 layout changes**. |
| **`DO-G9-1`** ⚠️ | the GDPR erase depends on the **daily cron**. If `schedule:run` is absent the 14-day deletion never fires and the guest's PII survives indefinitely **with no visible signal**. |

## 🧭 UX / EXCEPTION-ROUTING

| item | what |
|---|---|
| **`DO-G11-1`** ⚠️ | an **expired claim link** — the ordinary case — is rejected in middleware, so the guest never reaches the honest "link isn't valid" page and instead sees the **operator** 403 (*"your role doesn't include this area… ask an administrator on your team"*) plus a "Back to your trips" link they cannot use. The fix is exception-rendering wiring. ⚠️ **The shared 403 copy must NOT change — it is correct for the operator realm.** |
| **`DO-G5-1`** | the prototype's cancellation **review step** (a refund breakdown + policy timeline before confirming). |
| **`DO-G5-2`** | no request-history surface — a guest never sees a **declined** request. |
| **`DO-G7-1`** | no delivery/read state on a guest message (honest absence, but the guest cannot tell if it was seen). |
| ~~**`DO-G7-2`**~~ ⚠️ | ~~a guest cannot start a thread from Messages — only reply.~~ **RETRACTED at G13 — the claim was false.** `guest.messages.start` exists (`routes/web.php:370`) and is rendered as an always-visible "New message" form (`guest/messages.blade.php:37`). §N.8b correction #6; see §G-7 and §G-13. |
| **`DO-G6-2`**\* | a **pending** payment is invisible rather than shown as processing. *(\*id reused across sections in the source; see §G-6.)* |

## 🧱 STRUCTURAL / DUPLICATION (§N.8f)

| item | what |
|---|---|
| **`DO-PDF-1`** ⚠️ | **there is no shared PDF stylesheet** — 17 templates each carry their own `<style>`. The sweep normalised the *values*; the *structure* that let them drift is intact, so the next colour change needs the same 17-file sweep. |
| **`DO-G10-4`** | `guest/partials/trip-card.blade.php` is **dead code** duplicating the live trip card — a fix applied to one silently misses the other. |
| **`DO-G12-2`** | room photography is still **placeholder art** (the GATE-4 "P3-2" deferral, re-confirmed live). |
| **`DO-G10-5`** | the wishlist card is name-only (the prototype shows rating, reviews, nightly rate, "Book now" — the data exists). |
| **`DO-G1-2`** | the past trip card shows no rating. |
| **`DO-G12-4`** 🆕 | ⚠️ **raised by this SAVE's coherence check:** `components/guest/shell.blade.php:52`'s CSS comment still says `rgb(70,120,60)` *"is hand-written at ELEVEN guest call sites"* — **stale**, the live sites were unified at G9/G10. A §N.8b comment-vs-code drift, in a **CSS comment (which ships)**. Not fixed here: a SAVE writes docs only. |
| ⚠️ **the invisible-focus family** | **FIVE occurrences across TWO mechanisms** — `opacity:0` controls (G5 add-ons, G8 star input, G9 language chips) and an **explicit `outline:none` with no replacement** (G12 browse). A single `:focus-visible` utility is the §N.8f shared fix. |

## ⚖️ FOUNDER DECISIONS — surfaced, deliberately not taken

| item | the question |
|---|---|
| **`DO-G10-1`** | **credit has ONE production source.** Only promo redemption writes a wallet credit; refund-to-credit and add-on-cancelled-to-credit have **no writer**. Is that intended? |
| **`DO-G12-1`** | the free-cancellation promise states the org's **most generous** window unqualified. Accurate today (both demo properties are 2 days); over-promises the moment they differ. |
| **`DO-G9-2`** | the delete copy does not say that deletion **keeps anonymized financial history**. Delete = **anonymize-in-place on a 14-day cron**, never a row destroy — a semantics the guest is not told. |
| **`DO-G4-3` · `DO-G3-2` · `DO-G2-3` · `DO-G1-3`** | prototype affordances owed a verdict (map link, keypad hint, house-guide structure, search magnifier/clear, styled year popover, per-item sub-lines). |
| **`DO-G11-3`** | ratified refusals to **preserve**: social sign-in, open self-signup, the join-time welcome credit. |
| **`DO-G6-1`** | Stripe live is the founder's cutover (§H). |
| ~~**`DO-G13-1`**~~ ✅ | **BUILT 2026-08-12 — see §GUEST-FAQ.** ~~Guest Help — verdict BUILD the FAQ centre, which REOPENS a ratified refusal.~~ The founder ruled BUILD; `/guest/help` now renders the certified `HelpKnowledgeBase` (a guest set added, not forked), every answer verified against the code, the door-code window interpolated from `ArrivalPack::LEAD_DAYS`. ⚠️ **PURE FAQ — the support half is NOT built and NOT stubbed** (no phone/hours/reply-time/24-7; a negative test asserts it). The ONE contact is the real relay. Original verdict text: The support *destination* already exists (Account → relay); what is absent is a searchable FAQ. The 2026-08-07 refusal's premise — *"its content would have to be invented"* — is **falsified** by `HelpKnowledgeBase`. **Founder's call:** (a) build the honest FAQ centre now (mirror `/owner/help`; no migration, no dependency) · (b) configure real per-property support contacts first, then build the full sidebar — **this also unblocks `DO-INV-3`** · (c) keep the refusal (defensible — the relay already reaches people who can answer). ⚠️ The SLA pill, Typical-reply tile, phone/WhatsApp and 24/7 line are **DON'T-BUILD (#39)** on every path. Separate smaller call: extend `AskVyrobelService` to a guest KB — **recommend deferring**. See §G-13 + `design/G13-VERDICTS.md` §2. |
| **`DO-G13-2`** 🆕 | **Guest Documents — verdict DEFER the hub** (⚠️ downgraded from BUILD once the ratified location divergence was verified: the rollup is live on `/guest/payments`, three hops from a PDF). Three separable founder calls: **(i) the hub** — DEFER; a convenience, and building it reopens a ratified row. **(ii) "Download all (ZIP)"** — **DON'T-BUILD**; recommend **omitting** the control rather than disabling it (no ZIP writer exists anywhere; building one is real infrastructure for a convenience). **(iii) "Email me all documents"** — **DEFER**; buildable as a queued guest analogue of `OwnerReportMail`, to the guest's **own** address only (#28), rate-limited, and it must survive `DO-INV-4`. ⚠️ **"kept on file for 12 months" is DON'T-BUILD** — false (PDFs are generated on demand; nothing is stored), the §N.13.7 policy-claim shape; **do not soften it either**. 🟢 **Cheap win independent of all three:** label the document kinds on the Payments rows. See §G-13 + `G13-VERDICTS.md` §3. |
| **`DO-G13-3`** 🆕 ⚠️ | **Guest Referral Landing — verdict DON'T-BUILD as drawn (#39).** The question is whether a **referral programme** is on the roadmap at all: **(a)** no programme — archive the prototype as a ratified refusal (**recommended** absent founder intent); **(b)** build the backend first (code entity · `referred_by` attribution · **a new guest-creation path, i.e. revisiting booking-tied-claim-only — a security-posture change** · a first-stay reward trigger · an expiry policy · abuse controls · all on the append-only ledger with idempotency, #17), *then* the page; **(c)** ⚠️ **never** a stripped landing over `promo_codes` — it keeps the dishonest 80% and loses the point. ⚠️ Do **not** "fix" `guest/wallet.blade.php`'s honest deferral — it is test-defended. See §G-13 + `G13-VERDICTS.md` §4. |

## 🧹 SCOPED CROSS-REALM CLEANUPS

| item | what |
|---|---|
| ~~**`DO-INV-4`**~~ ✅ | ~~the **email-contrast sweep** across the 15 Mailables.~~ **CLOSED 2026-08-12 — see §EMAIL-SWEEP.** ⚠️ Two premise corrections on the way: the set is **20 templates, not 15**, and the offenders are **THREE greys, not two** — the scoping note had narrowed the item past **`#A99E95`**, the literal `DO-INV-4` was originally raised against. **120 failing text runs → 10**, across 20 templates + 2 auth screens; `#8A8079`→`#5F5853`, `#A69A92`/`#A99E95`→`#6C645F`; old values grepped to zero; rendered output proven byte-identical apart from the hexes. ⚠️ **CLOSED ≠ the emails are AA-clean** — 7 further literals still fail: `DO-EMAIL-1`. |
| ~~**`DO-EMAIL-1`**~~ ✅ | ~~the residual 10 failing email text runs across 7 literals.~~ **CLOSED 2026-08-12 — see §EMAIL-SWEEP-2. 10 failing runs → 1.** All six text/CTA items re-measured on their own plates (all failed) and mapped: `#8A7A6A`→`#5F5853` · `#B26A08`→`#7E5811` (the raw `--vy-amber` fill used as TEXT → the guest `--vy-amber-text` VALUE, inlined) · `#9A8C7F`/`#9C9089`→`#5F5853` · `#64748B`→`#94A3B8` (⚠️ **lighter**, not darker — light-on-dark) · the CTA gradient stops darkened after a **pixel sample** (white 3.08 → 5.19, gradient preserved). ⚠️ **`#E8EDF6` was a PROBE ERROR, not a defect** (it passes at 14.65). ⚠️ **The ★ `#E8A317` is excluded → `DO10-4`** and is the ONE remaining failing run. |
| ~~**`DO-G4-2`**~~ ✅ | **CLOSED by `DO-EMAIL-1`** — the pre-arrival `#8A7A6A`, carried as *"unmeasured"* since G4: measured **3.72** on `#FBF1E9`, retargeted to `#5F5853` (**6.27**). |
| ~~**`DO-EMAIL-4`**~~ ✅ | ~~`--vy-amber-text` is defined TWICE and the two values differ.~~ **CLOSED 2026-08-12 — see §AMBER-TEXT-RECONCILIATION.** Both values pixel-sampled on the plates each renders on, in BOTH realms (anchors 5.18): guest `#7E5811` worst **4.65** · owner `#804C05` worst **5.18**. **Both clear AA, so they UNIFY** — to the value with the headroom. ⚠️ The divergence was **accidental**, confirmed from history: two independent gates (guest G2 `4040b0f`, owner-2 `656cc34`) each derived an amber for their own plates. The two email literals and the owner-remittance PDF now carry that same value, so *"the amber-text value"* finally means one thing. |
| ~~**`DO-EMAIL-2`**~~ ✅ | ~~the grey drift reaches a THIRD surface class.~~ **CLOSED 2026-08-12 — see §WEB-GREY-SWEEP.** ⚠️ **28 occurrences across 9 files, not the 13 named** (the `rgb()` grep found two nobody had: `--vy-hk-ooo` and **the whole `billing/feature-locked` 403 page**). Root cause = **token drift** — the three literals ARE the pre-AA values of `--vy-muted`/`--vy-faint`, so every TEXT occurrence now reads the **token**. Pixel-sampled on the real plates (invite modal **`#F8F4EF`**, glass over a scrim — no declared computation could produce it): `#A69A92` 2.50→5.29 · `#8A8079` 3.52→6.38 · `#A99E95` 2.23→4.94 · the 403 page 3.62→6.56. ⚠️ **NOT a regression, and not an immature instrument either — a COVERAGE gap:** all 11 `owners.blade.php` greys sit inside `@can('users.manage')`, which the demo operator the gates ran as does **not** hold, so the markup was never in the DOM. **A contrast pass is bounded by the ROLE it runs as.** Gated guarantees re-verified, 0 regression. |
| **`DO-EMAIL-5`** 🆕 | a **FOURTH grey**: `.vy-inv-lbl { color: #9C9089 }` — every form label in the invite modal — measured **2.84** on the same plate. One line, same CSS block; outside the named three so not fixed. ⚠️ **A full operator/owner contrast re-audit is a backend-phase item** — this pass found a fourth failure the moment it looked. |
| **`DO-EMAIL-6`** 🆕 | **standalone pages cannot reach the design tokens.** `billing/feature-locked.blade.php` and `auth/login-approval*.blade.php` carry their own `<style>` with no `--vy-*` in scope, so both had to take mapped **literals**. They will not follow a future token retune. |
| **`DO-EMAIL-7`** 🆕 | `--vy-hk-ooo: rgb(138, 128, 121)` (`layouts/operator.blade.php:60`) — a housekeeping *"Out of order"* **fill** token sharing the pre-AA grey value, used as a labelled dot. Non-text; recolouring a status indicator is a **design decision**, not a contrast fix. |
| ~~**`DO-EMAIL-3`**~~ ✅ | ~~`admin-email-change` is the only off-brand email.~~ **CLOSED 2026-08-12 — see §ADMIN-EMAIL-BRAND.** It now extends the shared layout like its sibling `owner-email-change` (the identical job, owner realm, already on-brand). ⚠️ **Premise correction: `@extends` is not the brand test — FIVE templates are standalone, four of them on-brand;** this was off-brand by **palette**. ⚠️ **A SECURITY email, so the CONTENT is inviolable** — every sentence proven verbatim, one href, the warning still under the CTA (mutation-proven three ways). The three security claims **verified REAL** against `AccountController`. Contrast re-verified: 12 runs, **0 FAIL**. |
| ~~**`DO-EMAIL-8`**~~ ✅ | ~~the footer-override slot is misnamed `unsubscribe`.~~ **CLOSED 2026-08-12 — see §EMAIL-FOOTER-RESTRUCTURE.** ⚠️ Premise sharpened: it was not merely *misnamed*, it was **OVERLOADED** — genuinely holding a marketing opt-out for `winback`/`operator-guest-message` AND pressed into service as a reason override by `admin-email-change`. Renaming it would have re-conflated the two, so it was **SPLIT**: `footer_reason` (a category key) + `footer_marketing` (the real opt-out block). The old section name greps to **zero**. |
| ~~**`DO-EMAIL-9`**~~ ✅ | ~~a FALSE line on a security email.~~ **CLOSED 2026-08-12 — see §EMAIL-FOOTER-RESTRUCTURE.** The layout's single hardcoded default became a **catalogue keyed by a declared category**, so a template picks a KEY and the wording lives in code — a false reason cannot be typed. ⚠️ **The default is now GENERIC-TRUE**, so a forgetful new template still cannot claim a booking. All **16** extending templates enumerated and their reasons verified; the scope question is closed. |
| **`DO-INV-1/2/3`** | the invite email's missing text/plain part · no dark-mode handling · no public help destination. |
| **`DO-PDF-3`** | `guest-credit-note` + `guest-refund-receipt` are **unreachable in demo data** (no refund exists) — fixed by literal, **not render-verified**, and not covered by a rendering test. A seeded refund lights up both. |
| **`DO-G4-1`** | ⚠️ **still stale** (verified this SAVE): `SendPreArrivalEmails`'s docblock says `LEAD_DAYS = 48h`. The constant is the source of truth; the comment is a mock-value instance. |
| ~~**`DO-G4-2`**~~ ✅ | ~~the pre-arrival email's `#8A7A6A` grey.~~ **MEASURED then CLOSED: 3.72 on `#FBF1E9` → `#5F5853` (6.27) at `DO-EMAIL-1`.** See §EMAIL-SWEEP-2. |

## 🔁 CARRIED FROM THE OPERATOR REALM
**The 6 reopened operator a11y gates (§N.9)** — including the shared `<x-charts.bars>` work, which
⚠️ **must widen its scope**: O18 proved the min-bar floor also exists as **CSS**, which a component fix alone
would not catch.

---

## ✅ `DO-SEED-1` — the demo seed's date rot · **CLOSED** (2026-08-12)

**Raised** while verifying the support backend: `migrate:fresh --seed` aborted on a `UnitUnavailableException`,
**proven pre-existing** by stashing that build and re-running on a clean tree. **Now fixed at the root.**

### ⚠️ The root cause, verified rather than assumed

The seeder mixed **two date systems**:

| system | examples |
|---|---|
| today-**RELATIVE** | the arriving-tomorrow stay (`today+1`), the past checked-out stay (`today-14`), the cancelled/no-show pair (`today+40/+44`), the whole analytics spread (`today+2 … +132`) |
| **ABSOLUTE** calendar | the showcase stays (`2026-09-05/06/10`), five "past" statement months (`2026-04`…`2026-08`), an October accrual, the reviews (`2026-05`…`2026-07`), the expenses, the agreement dates |

They were disjoint **on the day they were written** — and `seedOwnerStatementHistory` carried a comment saying
so: *"well within the month and clear of the (future-dated) analytics-history window, so no collision"*.

As real time advanced the relative window slid into the absolute one and that comment became **false**. The exact
collision, located by walking the stack rather than inferring it: `DemoSeeder.php:990` →
`seedOwnerStatementHistory` booking the "past" **August** month on **14–18 Aug** on apartment **A1**, against the
analytics entry at **`today+3` = 15 Aug** on the same apartment. Because analytics runs first and *tolerates*
collisions (`catch { continue; }`) while the statement history is **strict**, the history aborted the seed.

⚠️ **The codebase had already met this exact failure once.** `seedOwnerBalanceAccrual` carries a
*"ROBUST TO DATE-DRIFT"* forward-scan added for the identical reason. That was a guard on one symptom; the
statement history had only the comment. **The rot was known, and defended in one place out of two.**

### The fix — an anchor, not a bumped date

Every formerly-absolute date now derives from one of three helpers, so the demo is valid on **any** run day:

- **`completedMonth(n)`** — a month that has already ENDED. The statement history's five months read from this,
  which makes the collision **structurally impossible**: a finished month is strictly before today, and every
  colliding booking is dated today-or-later. *This is the actual fix.*
- **`showcaseMonth()` / `showcaseDay(d)`** — the NEXT full month, so the showcase stays and the
  "finalized statement, payout not yet sent" period are always genuinely upcoming. That is what keeps the
  **#33** demo honest (a far stay whose door code is still withheld, beside the arriving-tomorrow one).
- **`thisMonth()`** — the anchor both derive from.

Also re-anchored: the waitlist, the agreement `effective_from` (now provably before the oldest attributed
night), the pending amendment, the accrual month, the example tax rate, the owner expenses (including the
**pre-agreement** row, which must fall before `effective_from` for its deductibility to resolve to NULL), and the
review stays — which had absolute July/May dates while the availability they depend on was already published
relative to today.

**One shared helper, `bookAptStayNear()`**, scans forward for a free window and is used by the showcase apartment
stay and the review anchors — both created *after* the tolerant analytics pass has claimed forward inventory.
It **throws rather than skips** if it cannot place a stay: a demo that quietly loses half a statement's net is
worse than one that fails loudly (§N.8f — it replaced the accrual's private copy of the same idiom).

### What the seed now produces (verified, not assumed)

**2 properties** (was 1) · the demo staff user **with its `Hotel Manager` role** (was roleless) · **41
reservations** · **6 owner statements** — five `paid` in completed months, one `finalized` for the showcase
month. `/app` is reachable as `demo-staff` **straight from a clean seed, with no hand-provisioning** (the
support-backend build had to assign roles by hand to drive its browser pass; that step is gone).

⚠️ **One nuance worth not mis-reading:** `demo-staff` is a **Hotel Manager**, which by design does **not** hold
`properties.manage` — so `/app/settings` still needs a `Property Owner`. That is the role catalogue working, not
a seed defect.

### 🔒 Guard — `tests/Feature/DemoSeedDateRobustnessTest.php` (7 tests / 25 assertions)

The end state (both properties, the staff role, the statement shape) · the **structural** property (every `paid`
statement covers a month that has already ended; the `finalized` one is next month) · a **no-hardcoded-calendar-
date** grep over the source with comments stripped · and the load-bearing one: **the seed runs at four
time-travelled dates** — a month end, a year boundary, a leap day, and 2031.

⚠️ **The time-travel case is what distinguishes a root fix from a patch.** A test asserting only "the seed
passes today" would have been green every day before this broke. **Mutation-proven:** restoring the original
`'2026-08-01'` literal for the last history month turns **6 of 7 red**. ⚠️ Note the seventh — the 2031 date —
legitimately still passes, because a literal that far in the past no longer collides. *One date would not have
caught it; that is why the guard travels to several.*

⚠️ **Honest note on the figures.** On this run the showcase statement's net is **€885.60**, matching the figure
the record has always carried. It is **not structurally pinned**: a drifting analytics stay can land inside the
showcase month on some run days and be swept into it. Every figure remains REAL and reconciling (#17); the guard
therefore asserts the statement **shape**, never a constant.


---

## ✅ `DO-BILL-1` — **CLOSED (2026-08-13)** — the collected VAT ID never reached Stripe

**Verified, not assumed:** `organizations.billing_vat_id` is written by `BillingActionController` /
`SettingsActionController` and read by `OperatorBilling`, `OperatorBillingCheckout`, `OperatorSettings` and the
PDF letterheads (`OperatorLetterhead`, `GuestInvoiceDocument`, the owner remittance/booking-summary documents).
A grep of `app/Payments/` shows **no Stripe call carries it.**

**Why it matters now.** Pass 2b turned on Stripe Tax for per-unit plans, where a business VAT ID is what drives
**EU reverse charge (€0 VAT)**. The session enables `tax_id_collection`, so Stripe asks for the id on its own
hosted page — but a customer who already typed it into Vyrobel has no reason to expect to enter it again, and
**if they skip it they are charged VAT they were entitled to reverse-charge.**

**What it would need.** Create/update the Stripe customer with the stored id (`customer.tax_ids`) before the
session, or write it back from the completed session's `customer_details.tax_ids` so the two agree. Neither is
guessed at here — it changes what is sent on a real money path.

### ✅ FIXED — a typed `tax_id`, pushed from every writer, fail-safe throughout

`VatId` derives the Stripe type from a **closed** country map (`eu_vat` · `ch_vat` · `gb_vat` · `no_vat`) and
returns **null** for anything else — an unrecognised prefix is never defaulted to `eu_vat`. `StripeTaxIdSync` is
the ONE path from the column to the customer, called from **three** in-app writers (Billing details, the
subscribe action, and Settings → invoice details — all three, or which screen the operator used would decide
whether they get reverse charge) **and from the webhook** when a customer id first appears. ⚠️ **The webhook is
the important one:** the app never creates the Stripe customer — Checkout does — so before this there was no
attach point at all, and Stripe re-computes tax on **every renewal invoice**, not just the first.

⚠️ **When in doubt, charge the tax.** An untypeable number is not sent; a Stripe rejection returns false rather
than throwing (a bad VAT number must not break a subscription) and is never treated as success; both are logged
**without the value**, which is a customer business identifier.

**Guard:** `tests/Feature/BillingVatIdSyncTest.php` (8 tests / 22 assertions) — valid → typed · jurisdiction
typing incl. an unknown prefix refused · untypeable → not sent + surfaced · Stripe-rejected → taxable + surfaced
· no customer → no-op · a corrected number **replaces** the stale one · display = charge = Stripe still holds ·
the PDF letterhead unaffected. Mutation-proven four ways (send a raw untyped string · default an unknown prefix
to `eu_vat` · drop the digit check so prose is sent · treat a rejection as success) — each red on its guard. All
against the FAKE client; `StripeSafetyGuard` untouched.

### ⚠️ The guard caught the validator being too generous — before it shipped

The first version accepted **`"not-a-vat-id"`**: normalised it becomes `NOTAVATID`, whose leading `NO` is
**Norway**, and the body was merely "alphanumeric". A sentence typed into the field was one step from being sent
to Stripe as a Norwegian VAT number — precisely the mis-typing that can be ACCEPTED and zero-rate a taxable
sale. Fixed by requiring a **run of at least four digits** in the body: every real VAT number has one, prose does
not. *A structural check that is too permissive is not a weaker guard — on a tax path it is the failure mode.*

### ⛔ A founder decision this surfaced — VAT VALIDATION (see §G.1)

Structural ≠ valid. The app can say "this is plausibly a Portuguese VAT number"; only VIES can say "this number
exists and belongs to this business". Implemented: **send as-is and let Stripe validate** (asynchronously, so a
sale can be zero-rated before validation completes). The alternative — **require VIES validation before
honouring reverse charge** — is safer but is a build with a new "pending validation" state. Flagged, not guessed;
the safe half is implemented either way.

**Original verdict — REAL GAP (small, money-adjacent).** Not a correctness bug in what the app sends today: everything
sent is correct, and the tax is Stripe's to compute. It is a **data-sync gap that costs the customer money in
one direction only** (over-charging a reverse-charge business), which is why it is flagged rather than deferred
silently.

---

## ✅ `DO-BILL-2` — **CLOSED (2026-08-13)** — the Add-property modal claimed "billed per unit" on every plan

⚠️ **Found by re-reading the record, not by a test** — the same way the five security holes were found. A stale
entry ("it belongs on the blocker's ledger") was being annotated as resolved when the underlying claim turned
out to have outlived the framing that excused it.

**VERIFIED IN REAL CODE, not inferred:** `resources/views/livewire/operator/portfolio.blade.php:203` —

> *"It joins your organization — **billed per unit**. You'll set up units, rates & channels next; all your team
> roles carry over."*

The only conditional in that region is `@if ($canManage)`. **The claim is unconditional with respect to the
plan's shape.**

⚠️ **WHY IT IS FALSE TODAY.** Billing pass 1 made a plan declare its own shape and pass 3 keyed the Billing page
to it: a **FLAT** org (Essential €49 / Pro €99) is *not* billed per unit, and adding a property changes its bill
by **nothing**. The demo org is on flat Essential, so the modal is false **on the default demo right now**. This
is precisely the false per-unit/proration claim pass 3 retired — **one page over, on a surface pass 3 never
touched.**

⚠️ **Note how it hid:** the modal is behind `@if ($canManage)` — a **role-gated** surface (§N.14.1), and the
Portfolio page is reached by a role the design walk did not always render as. *A conditional surface is invisible
to a pass that cannot see it.*

**What it needs.** The same shape-keying the Billing page received: read `Plan::isPerUnit()` off the org's
current plan and say "billed per unit" only when that is true (a flat org should get the honest "included in
your plan" framing already used on `/app/billing`). ⚠️ **Do not free-type the alternative copy** — key it
(§N.16.3), as the Billing card's `tax_note` is keyed, so the two surfaces cannot drift.

**Verdict — REAL GAP, live §N.8d, small.** Presentation-only; no money moves through this modal. It belongs in
the billing tail beside `DO-BILL-1`.

### ✅ FIXED — the copy is keyed by the plan's shape

`OperatorPortfolio` now exposes `planIsPerUnit`, resolved from the org's current subscription's plan — **the
same source the Billing page reads**, so there is no parallel notion of the shape to drift (§N.8f). The modal
renders **"billed per unit"** only inside the per-unit branch and **"included in your plan"** otherwise (the
Billing page's own framing, reused for consistency). ⚠️ **A flat org cannot render the false claim — a property
of the markup, not a hide** (§N.16.3). An org with **no plan** makes no billing claim at all.

**Guard:** `tests/Feature/PortfolioBillingClaimTest.php` (4 tests / 14 assertions) — flat sees no per-unit claim ·
per-unit sees it · no-plan claims nothing · **and one test that pins the vacuity trap itself**: a Front Desk user
is refused the page outright (403), so "no false claim" would have been true for the wrong reason. Every copy
assertion is preceded by a **presence** assertion on the modal. Mutation-proven three ways: restore the
unconditional claim → **red** · drop the true claim for per-unit → **red** · hardcode `planIsPerUnit => true`
→ **red**.

**Verified in the browser as a manager (`properties.manage`), both shapes** — flat (the demo, "included in your
plan") and per-unit (created transiently, "billed per unit"), then reverted.

### ⚠️ The sibling sweep — the class was grepped, and it is a cluster of ONE

Per §N.17.4 the per-unit claim has recurred across surfaces, so the whole class was swept: `billed per unit` ·
`per unit` / `per-unit` · `adding a unit` · `prorated`/`proration` · `+€…/mo` · `€9/mo` · `added to your plan` ·
`counts toward your plan` · `affects your bill` — across `resources/views` (all realms, emails and PDFs
included) and `app/`.

**Every other hit is one of:** owner **analytics** copy (per-unit occupancy / commission / revenue — a different
sense of "per unit", no billing claim) · **code comments** describing the already-fixed state · the Billing page
itself, **already shape-keyed by pass 3**. ⚠️ **No second unconditional billing-consequence claim exists** — so
there is no `DO-BILL-2-continued`, and the absence is a measured result rather than an unexamined assumption.

### ⚠️ A premise correction this fix produced — about the PREVIOUS pass's cleanup

Pass 2b reported the transient per-unit plan "reverted + verified". ⚠️ **The verification was VACUOUS.** `Plan`
uses `SoftDeletes`, so `Plan::where('code', …)->delete()` **soft-deleted** the row, and the check
`Plan::where('code', …)->count() === 0` passed **because the same global scope that hid the row from the check
also hid it from everything else** — including a later `firstOrCreate`, which is how it surfaced (a unique-key
violation on a row Eloquent insisted did not exist).

⚠️ **THE LESSON — a cleanup check must not run through the same scope as the cleanup.** Verify a deletion with a
**raw** query (`DB::table(...)`) or `withTrashed()`; otherwise the check can only ever confirm what the scope
already guarantees. This pass hard-deleted the leftover (`forceDelete`) and verified with raw SQL — including
that no subscription points at a per-unit plan. *Same family as §N.4p: the verdict must come from something
other than the mechanism under test.*


---

## ⛔ `DO-EMAIL-5-continued` — **BLOCKED ON THE INSTRUMENT** (attempted 2026-08-13; scope mapped, nothing published)

⚠️ **This pass did not close. It is recorded as blocked rather than shallow-closed, because the contrast probe
could not be made trustworthy and §N.8e's rule is explicit: when the probe breaks, publish NOTHING** — and that
applies to a clean bill of health just as much as to a list of failures.

### ✅ What IS established (verified, reusable)

**Part A's target set is enumerated.** The gated markup behind the six permissions the covered Hotel Manager
lacked lives in **9 files**:

| permission | files |
|---|---|
| `settings.manage` | `operator-settings`, `operator-security` |
| `properties.manage` | `notification-preferences`, `email-templates`, `email-previews` |
| `payouts.manage` | `payout-run` |
| `payments.refund` | `reservation-detail` |
| `guests.erase` | `guest-profile`, `guests-list` |

**The role premise holds:** `demo-admin@vyrobel-demo.test` is a Property Owner holding `*`, so **one role renders
the entire delta** — no hand-provisioning (DO-SEED-1). Confirmed by rendering `/app/settings` as that user.

**Part B's surface is measured in size:** `@forelse`/`@empty` appear in **67 view files** — guest 10 · booking 2 ·
owner 21 · operator 18 · operator-views 2. The DATA axis is genuinely large, as §N.16.2 anticipated.

### ⚠️ THE BLOCKER — three distinct failure modes of the contrast probe, in one pass

The measurement requires the composited plate. A `getComputedStyle` ancestor-walk cannot see a **gradient**, and
this product paints gradients on the body, on cards and on buttons. Each attempted instrument failed differently:

1. **Naive ancestor-walk** → `backgroundColor` is transparent on gradient elements, so the walk fell through to
   white and reported **13 false failures at 1.00** (white text "on white"). *The same shape as the `.vy-fade`
   `animation:none` artefact: a probe that turns everything into the same wrong answer.*
2. **Bail-out-on-gradient** → honest, and **blind**: the body gradient made **166 elements unmeasurable** and the
   probe reported **0 failures**. ⚠️ **This reproduced verbatim the instrument bug already recorded in §N.8e** —
   *"a gradient-exclusion rule matching `body` reported 0 failures for a whole page."* A clean bill from this
   version would have been the most dangerous output of the three.
3. **Analytic gradient evaluation** (parse the stops, interpolate at the element's position) → anchors validated
   (white-on-accent **5.18**, ink-on-white **15.4**), but it still reported white button labels at **1.2 on a
   cream plate**: it resolved an ancestor card's gradient instead of the button's own accent gradient. Plausible
   numbers, wrong plate — the worst kind of wrong.

⚠️ **The lesson, which is the durable output of this pass: an instrument that produces PLAUSIBLE numbers is more
dangerous than one that crashes.** Version 2 was silent, version 3 was confident, and only the two
self-validating anchors plus a sanity read of *which* text was being flagged exposed either.

### ⬅ What this needs next (a bounded, specified follow-up)

**Pixel-sample a real screenshot** — the method §N.8e actually prescribes — rather than any DOM-derived plate.
`PIL` is unavailable but **PHP GD is present**, so the shape is: dump element rects + computed colours from the
DOM → screenshot to disk at a known DPR → sample the plate pixels just outside the glyph box in PHP → take the
**worst** sample (the plate is positional). A prototype exists but was **never validated end-to-end and is
deliberately not committed** — an unproven measuring tool is exactly what this entry is warning about.

**Verdict — Part A OPEN (scope mapped, unmeasured) · Part B OPEN (67 files inventoried, none forced).** No
contrast finding, and no clean bill, is claimed for either.

---

## ✅ `DO-PROBE-1` — **THE VALIDATED PIXEL-SAMPLER** (2026-08-13) — `tools/contrast-sampler.php`

⚠️ **This closes the blocker that stopped `DO-EMAIL-5-continued`.** It is an INSTRUMENT, not a design fix: it
closes no design item and changes no product code.

**How it works.** Render live at a known scale with transitions settled → dump each text element's rect (CSS px)
and its OWN text colour from the DOM (`getComputedStyle` is valid for *that* — it is a declared value, not a
composited plate) → screenshot full-page at `scale: css` so one image pixel is one CSS pixel → **PHP GD reads
the actual painted pixels inside the element's own box**, discarding glyph ink and its anti-alias halo →
contrast = the DOM text colour against the worst plate pixel.

⚠️ **Sampling the element's OWN box is the whole fix.** A button's box is the button, so its plate is the
button's gradient — which is exactly what attempt 3 got wrong when it walked up to a card.

### ⚠️ Validated before trusted — the anchors AND the two cases that break probes

| check | result |
|---|---|
| **anchor** white-on-flat-accent | **5.18** — exact |
| **anchor** ink-on-flat-white | **15.4** — exact |
| ⚠️ **hard case** white label on a gradient BUTTON | plate `rgb(225,104,50)` — the **button's own** accent gradient, **3.37**. *Attempt 3 read 1.2 on cream.* And 3.37 matches the record's independently-documented "white-on-grad ≈3.4" — a third anchor reached from another direction. |
| ⚠️ **body-gradient page** | **measured**, not excluded: dark ink on the body gradient reads **9.01**. *Attempt 2 reported a blind "0 failures".* |

### ⚠️ Fail loud, never plausible-blind

- An element that cannot be isolated reports **UNMEASURABLE with a reason** (`outside-screenshot`,
  `zero-area`, `plate-not-isolable`) — **never a number**. An out-of-bounds read would otherwise return BLACK
  and score a dramatic, entirely fictional failure.
- Every result carries **measured / failed / unmeasurable**, so "0 failures / 0 measured" cannot be reported as
  a clean bill — attempt 2's blindness is structurally visible.
- ⚠️ **The coordinate contract is CHECKED, not assumed**: a scale mismatch **refuses to sample** (exit 3). A
  silent mismatch would sample the wrong pixels and return perfectly plausible numbers.

### ⚠️ A defect the validation caught in the tool itself

The first working version took the **single worst pixel** — correct for a gradient (the plate is positional),
catastrophic next to **anti-aliasing**: the halo around every glyph is a blend of ink and plate, so the darkest
pixel in a text element is almost always halo. It scored the gradient button **1.6 on "peach"** and dark ink on
the body gradient **2.15 on "brown"** — both plausible, both wrong. Fixed by taking the worst at the **10th
percentile**: still the worst END (a gradient's dark corner is thousands of pixels), immune to the thin fringe.

⚠️ *That is the pass's own lesson turned on itself — plausible numbers, caught only because the fixture had
KNOWN answers to check against.*

**Guard:** `ContrastSamplerValidationTest` (7/24) against **committed** fixture screenshots, so a future session
re-verifies the instrument in seconds instead of re-deriving it and re-hitting the trap. Mutation-proven: a
pale-grey-on-cream element is flagged and **clears when darkened to the AA token** (1.5 → 5.95); legible text on
a gradient passes and **flags when weakened** (9.01 → 1.83); the off-screen element stays UNMEASURABLE; the
scale-mismatch guard refuses.

**⬅ WHAT THIS UNBLOCKS:** `DO-EMAIL-5-continued` is now re-runnable — Part A (the 6 permissions' gated markup,
scope already mapped) and Part B (the 67 data-conditional files) can be measured with an instrument that has
proven it reads the right plate.

---

## ⚠️ `DO-EMAIL-5-continued` (RE-RUN) — the probe now works, and it found something bigger than the sweep

**Status: Part A MEASURED on 1 of 9 surfaces, NOT fixed · Part B UNTOUCHED.** The pass did not close, and what
stopped it this time is not the instrument — it is a **finding that outranks the sweep**.

### ⚠️ The tool needed a SECOND correction, and the reason is the durable lesson

`tools/contrast-sampler.php` was validated in DO-PROBE-1 and was still wrong for this input class. Pointed at
`/app/settings` it reported **74 failures** on plates like `rgb(136,129,127)` — a perfect ink/white midpoint,
i.e. **anti-alias halo wearing the costume of a background**.

⚠️ **The fixture was not representative of the input.** It had sparse text in generously padded blocks, where
halo is a tiny share of the samples; a real page of dense 12px labels is mostly glyph. The fixed RGB tolerance
plus a 10th-percentile worst could not separate them. **A validated instrument is validated for the cases it was
validated on** — and my fixture chose the easy ones.

Fixed with **adaptive halo rejection**: find the modal plate (background dominates by count even in dense text),
then discard any pixel lying more than 45% of the way from that plate toward the ink. Anti-aliasing lies *on
that segment*; a gradient's real variation does not. **Re-validated against the committed fixture — anchors
5.18 / 15.4 exact, the gradient button still 3.37 on its own plate, known-bad still 1.5.** 74 → 45.

### ⚠️ THE FINDING — the AA guarantee rests on an assumed worst-case plate that the real page beats

`DesignTokenContrastTest` computes the hardest card background as a constant, **`rgb(249,223,207)`**, and calls
it *"the hardest background for dark text anywhere on a card"*. Measured on the real composited page, plates are
**darker than that**:

| element | token | measured plate | ratio | need |
|---|---|---|---|---|
| "Display name" | `--vy-faint` `rgb(108,100,95)` | `rgb(252,211,190)` | **4.19** | 4.5 |
| "Check-in from" | `--vy-faint` | `rgb(249,208,187)` | **4.07** | 4.5 |
| "Country" / "Time zone" | `--vy-faint` | `rgb(251,209,187)` | **4.12** | 4.5 |
| "Demo Seaside Property" | `--vy-muted` `rgb(95,88,83)` | `rgb(241,172,134)` | **3.65** | 4.5 |

⚠️ **This is not a contradiction to resolve by editing 45 elements.** It is the **positional-plate rule**
(DO-EMAIL-7) applied to the whole product: the test's constant is a *computed* worst case, the page has a
*measured* one, and the measured one is worse. Either the token floor moves (a product-wide visual change), or
the assumed plate is wrong and the test's constant needs re-deriving, or specific surfaces need `--vy-ink`
(exactly the remedy DO-EMAIL-5 applied to the storefront filter keys).

⚠️ **That is a design decision with product-wide reach, and a contrast sweep must not make it unilaterally by
retinting 45 elements on one page.** Surfaced deliberately, unfixed.

### ⬅ What this needs next

1. **Settle the plate question first** — re-derive the true darkest composited card plate across the gradient's
   range, and reconcile it with `DesignTokenContrastTest`'s constant (G8: that test encodes a real invariant;
   read it before changing it).
2. **Then** run Part A across all 9 files and Part B across the 67, with the token decision already made — the
   sweep is mechanical once the floor is settled.

**Measured so far:** `/app/settings` — **221 measured / 45 failed / 1 unmeasurable** (`plate-not-isolable`).
No blind zeros; the unmeasurable one is recorded as unswept, not clean.

