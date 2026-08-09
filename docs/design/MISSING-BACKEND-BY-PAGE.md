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

- **Sidebar group labels fail WCAG AA.** `.vy-nav-label` and `.vy-nav-item` hardcode the prototype's greys
  (`#9C9089`, `#8A8079`). Measured in the browser against the sampled gradient behind them:
  **≈2.0:1**, well under the 4.5:1 the polish pass held everything else to. It is **pre-existing** and
  **realm-wide** (the shared operator shell, ~43 pages), so it was reported rather than swept into this
  page's turn. The 2026-08-07 audit's contrast sweep excluded gradient-background cases as false
  positives, which is likely why it survived. **Founder's call whether to darken it realm-wide.**
- **Topbar subtitle.** The shell renders a subtitle under the H1 ("Demo Seaside Property · Sunday, 9 Aug
  2026") that the prototype does not have. Left in place: it is shell chrome shared by every operator
  page, and other pages use the slot for their own real context.
- **Property switcher clips its label** ("Demo Seaside Propert…") at 190px. Shell-level, pre-existing.
- **Main content width.** Prototype `max-width: 1180px`; the live shell uses `1480px` (content 1150px vs
  ~1124px). Shell-level and realm-wide — left alone, noted.
