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
