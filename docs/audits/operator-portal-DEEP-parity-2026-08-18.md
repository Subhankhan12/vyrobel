# Operator-portal DEEP parity audit — 2026-08-18 (RESUMABLE · READ-ONLY)

> ## ⚠️ READ THIS FIRST — THE AUTHORITATIVE STATE (everything below is a dated running log)
>
> This report was written across 13 runs and **several figures in the middle sections are SUPERSEDED**. A
> reader landing mid-document will otherwise take a stale number as current. The authoritative values are:
>
> | | Authoritative |
> |---|---|
> | **Pages fully diffed** | **21 of 50** (dashboard · payouts-run · owners · front-desk · reservations · rates · housekeeping · refund · reservation-detail · folio.pdf · calendar · guests · inbox) |
> | **REMAINING** | **27 of 50** — presentation parity only |
> | **Finding 1 (READ exposure)** | ⚠️ **RUN 13 IS DEFINITIVE: 13 org-data routes reachable by Housekeeping, 7 rendering money, +1 document.** Earlier statements — "one decision not six" (run 1), "19 actionable" (run 8), "25 ungated" (run 8) — are **SUPERSEDED**; they were swept, run 13 is measured. |
> | **Finding 2** | one control without `@can` (`/app/calendar` → `New reservation`) |
> | ⚠️ **Finding 3 (run 18)** | `/app/schedule` **instructs Housekeeping to drag shifts** — an action requiring `schedule.manage`, which it lacks. Carried by PROSE, so no control-based check could see it. Plus a candidate: **zero `draggable="true"` nodes for ANY role**, so the instruction may describe an unwired interaction. |
> | **Candidate 3** | **WITHDRAWN** on evidence (run 10) |
> | **Write routes** | 135/135 swept — **no gap** |
> | **#28 PII perimeter** | measured correct on 7 surfaces |
> | **Code changed** | **0** · demo money+inventory identical start→end |
>
> ✅ **DELIVERABLE GAP CLOSED (run 14) — 21 screenshots captured.** See the closing section. The text below
> records the gap as it was found, because how it was found is the point.
>
> ⚠️ **DELIVERABLE GAP (as found at the self-check) — NO SCREENSHOTS HAD BEEN TAKEN.** The brief required
> `.captures/deep-operator-NN-{page}-{role}.png` per page per role. **`.captures/` contains ZERO
> `deep-operator-*` files.** Every diff in this report rests on hydrated DOM reads (character counts, control
> sets and visibility measurements are recorded per page, and those reads were real) — **but the screenshot
> artifact the brief asked for does not exist.** Recorded here rather than left as an unmet requirement the
> summary silently implies was met. ⚠️ This is the same class of gap the OWNER audit found in itself at
> N=M — and it was found the same way, by self-checking the deliverable instead of trusting it.


**No code changed · no mutation exercised · money + inventory as-seeded.**
Baseline held at start and end: `rate_calendar` md5 `693e3237…` / 929 rows / 10,920,000 minor ·
0 unit overrides · 41 reservations · 0 seasons · 40 folio items · 16 payments.

Counterpart to `docs/audits/owner-portal-DEEP-parity-2026-08-17.md`, inheriting its method notes. Two
dimensions the owner audit did not have: **per-role gating** (the difference between roles IS the evidence)
and **operator pages mutate as their job** (surfaced, never exercised).

---

## HARNESS — proven both ways, and it failed twice first

| Instrument | Proof |
|---|---|
| App + wireframes | `:8000` 200 · `:8099` 200 (`Operator Dashboard.html` rendered 1,839 chars) |
| Roles land | all four reach `/app/dashboard` |
| Roles see a positive | real content per role (dashboard 227–234 KB) |
| Roles see a negative | nav counts differ **26 / 23 / 14 / 14** — role-scoped, not blank |

⚠️ **TWO HARNESS FAILURES, CAUGHT BY THE PROBE RULE, NOT BY LUCK:**

1. **A stale session made every role look identical.** My first capture returned byte-for-byte the same result
   for all four roles — which reads like "no gating at all". It was one leftover `demo-owner` session: `/logout`
   is a POST, my GET didn't log out, the login form was skipped, and `/app` bounced to `/owner` (invariant #26
   working). Fixed with `clearCookies()` per role. ⚠️ **Four identical results would have been a headline
   finding and were pure instrument error.**
2. **Three roles then "failed to log in" — and that was also the harness.** Only Property Owner got through;
   the other three sat on `/login` with no error. The accounts were fine (`status=active`, no 2FA,
   `Hash::check('password')` TRUE for all four) and curl logged them in. The POST was never firing from
   `page.click()`; a hard reload + native `form.submit()` fixed it. ⚠️ **Per the probe rule, "sees nothing" was
   treated as a broken login, not perfect gating — which is exactly what it turned out to be.**

## THE ROLES — the gating dimension (from the demo seeder + the spatie matrix)

| Role | Demo account | Permissions |
|---|---|---|
| Property Owner | `demo-admin@vyrobel-demo.test` | **49** |
| Hotel Manager | `demo-staff@vyrobel-demo.test` | **37** |
| Accountant | `demo-accountant@vyrobel-demo.test` | **12** |
| Housekeeping | `demo-housekeeper@vyrobel-demo.test` | **4** (`housekeeping.manage/view`, `schedule.view`, `units.view`) |

All four hold `password`. Back-office allow-list (`config('authorization.back_office_roles')`) names 11 roles;
the demo seeds these four.

⚠️ **CANON CORRECTION — `CLAUDE.md` is STALE.** It states `demo-staff` is *"The only demo account that can
reach `/app`."* **Four seeded accounts reach `/app`.** Recorded, not fixed (report-only).

⚠️ **A separation-of-duties detail worth keeping:** `payments.refund` is held by **Property Owner and
Accountant but NOT Hotel Manager** — a deliberate split, and a good probe for refund gating.

## LEDGER — derived from `route:list` + the wireframe directory (not assumed)

- **50 distinct operator GET page routes** under `/app`.
- **47 `Operator *.html` wireframes** in `docs/design/raw/`.

| # | Page | Wireframe | Status |
|---|---|---|---|
| 1 | `/app/dashboard` | Operator Dashboard.html | **DONE** (wireframe captured + 4-role control diff) |
| 2 | `/app/payouts/run` | Operator Payout Run.html | **DONE** (4-role control diff; ⚠️ finding) |
| 3 | `/app/owners` | Operator Owners.html | **DONE** (4-role control diff; ⚠️ finding) |
| 4 | `/app/front-desk` | Operator Front Desk.html | **DONE** (run 2 — wireframe diff + 4-role visible-control diff) |
| 5 | `/app/reservations` | Operator Reservations.html | **DONE** (run 3) |
| 6 | `/app/rates` | Operator Rates.html | **DONE** (run 3) |
| 7 | `/app/housekeeping` | Operator Housekeeping.html | **DONE** (run 4) |
| 8 | `/app/reservations/{id}/refund` | Operator Refund.html | **DONE** (run 4) |
| 9 | `/app/reservations/{id}` | Operator Reservation Detail.html | **DONE** (run 5) |
| 10 | `/app/reservations/{id}/folio.pdf` | *(artifact — no wireframe)* | **DONE** (run 5 — per-role access) |
| 12 | `/app/guests` | Operator Guests.html | **DONE** (run 11) |
| 13 | `/app/inbox` | Operator Inbox.html | **DONE** (run 11) |
| 14–20 | 7 further pages | mapped | **ACCESS-MATRIXED** (per-role reachability proven; element diff REMAINING) |
| 21–50 | the rest | mostly mapped | **REMAINING** |

⚠️ **Routes with no obvious wireframe** (noted, not assumed missing): `/app/email-previews`,
`/app/guest-requests`, `/app/unit-requests`, `/app/notification-preferences`, `/app/security/export`,
`/app/reports/export`, `/app/maintenance/costs/export`, `/app/schedule/pdf`, `/app/owners/invite/preview`.
⚠️ **Wireframes with no matching route** (candidates for the same mis-mapping that bit the owner audit):
`Operator Messages.html` vs `/app/inbox`, `Operator Conversation.html`, `Operator Reservation Cancelled.html`,
`Operator Reservation No-show.html`, `Operator Work Order.html`, `System States.html`.

---

## ⚠️ THE ACCESS MATRIX — 20 pages × 4 roles (GET only; no action POSTed)

`200` = renders · `403` = refused.

| Page | Prop Owner | Hotel Mgr | Accountant | Housekeeping |
|---|---|---|---|---|
| dashboard | 200 | 200 | 200 | 200 |
| front-desk | 200 | 200 | 200 | 200 |
| reservations | 200 | 200 | 200 | 200 |
| **rates** | 200 | 200 | **403** | **403** |
| housekeeping | 200 | 200 | 200 | 200 |
| **reports** | 200 | 200 | 200 | **403** |
| **insights** | 200 | 200 | 200 | **403** |
| owners | 200 | 200 | 200 | 200 |
| payouts/run | 200 | 200 | 200 | 200 |
| **roles** | 200 | **403** | **403** | **403** |
| **settings** | 200 | **403** | **403** | **403** |
| **billing** | 200 | **403** | **403** | **403** |
| units | 200 | 200 | 200 | 200 |
| **guests** | 200 | 200 | **403** | **403** |
| **inbox** | 200 | 200 | **403** | **403** |
| maintenance | 200 | 200 | 200 | 200 |
| **schedule** | 200 | 200 | **403** | 200 |
| **portfolio** | 200 | 200 | **403** | **403** |
| **search** | 200 | 200 | **403** | **403** |
| **reviews** | 200 | 200 | **403** | **403** |

### Correctly gated — the render matches the declared matrix

- **`rates`** → PO/HM only. Matches `rates.view` (PO ✓ HM ✓ Acct ✗ HK ✗).
- **`roles` / `settings` / `billing`** → Property Owner ONLY. Matches `organizations.manage` / `billing.manage`.
- **`reports` / `insights`** → HK refused. Matches `reports.view` (HK has none).
- **`schedule`** → HK **200**, Accountant **403**. Matches `schedule.view` exactly — ⚠️ and this is the
  sharpest single proof the gating is permission-driven rather than seniority-driven: the *least*-privileged
  role sees a page the Accountant cannot.
- **`guests` / `inbox` / `portfolio` / `search` / `reviews`** → PO/HM only.

---

## ⚠️⚠️ FINDING 1 — VIEW-LEVEL OVER-EXPOSURE OF OWNER PAYOUT MONEY TO HOUSEKEEPING

**Housekeeping holds 4 permissions — none of them financial** (`housekeeping.manage/view`, `schedule.view`,
`units.view`). It nonetheless reaches, and reads:

| Page | Housekeeping sees |
|---|---|
| `/app/payouts/run` | **€885.60 · €2,804.40 · €442.80 · €656.00 · €467.40 · €516.60** — the same owner payout figures Property Owner sees |
| `/app/owners` | owner rows, **€885.60**, and a working `Open the payout run →` link |

**Mechanism confirmed at source, not inferred:** these GET routes carry **no `can:` middleware** —
`Route::get('/owners', OperatorOwners::class)` and `Route::get('/payouts/run', PayoutRun::class)` are
protected only by the group-level `EnsureBackOfficeAccess` allow-list, which admits all 11 back-office roles.

⚠️ **THE WRITE ACTIONS ARE CORRECTLY GATED, AND THAT IS THE IMPORTANT HALF.** The control-set diff:

| Role | Action controls rendered on `/app/payouts/run` |
|---|---|
| Property Owner | **`Run payout (test mode)` · `Mark 1 paid` · `Mark paid`** |
| Housekeeping | **none** (only `Mark all read`, a notification control) |

So this is an **exposure of financial READ, not of financial WRITE**. Nobody can move money who shouldn't.
⚠️ **Server-side enforcement of those hidden write actions is UNVERIFIED-WITHOUT-MUTATION** — proving a hidden
control is also refused on a direct POST requires POSTing it, which this audit does not do.

⚠️ **This is reported, not fixed** (report-only). It is a **product/permissions decision**: either `/app/owners`
and `/app/payouts/run` should carry a `can:payouts.manage`-ish GET gate, or back-office-wide visibility of
owner payout figures is intended. The same pattern (no `can:` on the GET) also puts `front-desk`,
`reservations`, `housekeeping`, `units` and `maintenance` in front of every back-office role — lower
sensitivity, same mechanism, listed here so the decision is made once rather than per page.

---

## MUTATIONS SURFACED — none exercised

| Surface | Writes | Status |
|---|---|---|
| `/app/payouts/run` → `Run payout (test mode)` | `payout_runs`, `payouts`, owner ledger stamps | ⚠️ **surfaced, not exercised** |
| `/app/payouts/run` → `Mark paid` | `payouts.status` → paid | ⚠️ surfaced, not exercised |
| `/app/front-desk` → check-in / check-out / assign | `reservation_units`, `availability` (via `AssignmentService`) | ⚠️ surfaced, not exercised |
| `/app/rates` → rate write / bulk / per-unit override | `rate_calendar`, `unit_rate_overrides` | ⚠️ surfaced, not exercised |
| `/app/reservations/{id}/refund` | `payments` (bounded by #27) | ⚠️ surfaced, not exercised |
| `/app/housekeeping` → status / assign | `housekeeping_tasks` | ⚠️ surfaced, not exercised |

## DEFERRAL NOTED, NOT A GAP

⚠️ The **seasonal apply-panel** is absent from `/app/rates`. This is a **DOCUMENTED DEFERRAL**
(`DECISIONS.md`, 2026-08-18): the tier's engine is complete and folio-proven, the apply action is routed and
`rates.manage`-gated with a tested blast-radius preview, and only the operator UI is pending — it belongs with
the operator module. **Not counted as a gap.**

---

## TALLY (scoped strictly to what was verified this run)

| Bucket | Count |
|---|---|
| Pages fully browser-diffed vs wireframe + control-diffed | **3** |
| Pages access-matrixed across all 4 roles | **20** |
| **Gating: correctly gated** | **12 of 20 pages** show real, matrix-consistent refusals |
| ⚠️ **Gating: over-exposed (READ)** | **1 confirmed finding** (owner payout money → Housekeeping) + 5 lower-sensitivity pages sharing the mechanism |
| Gating: over-hidden | **0 found** |
| Mutations surfaced / exercised | **6 / 0** |
| Canon corrections | **1** (`CLAUDE.md` "only demo account" is stale) |
| Code changed | **0** |

## REMAINING

**47 of 50 pages** still need the element-by-element wireframe diff (20 of them already have their per-role
reachability proven, so they resume faster). The next run should start at `/app/front-desk` — the highest
mutation density and the richest control-set to diff across roles.

⚠️ **"0 over-hidden found" is scoped to the 20 access-matrixed pages** — it is not a claim about the other 30.

---

# RUN 2 — `/app/front-desk`, and a correction to my own method

## ⚠️⚠️ METHOD FAILURE I CAUGHT IN MYSELF — the phantom-control trap, on the highest-stakes page

My first control capture on `/app/front-desk` reported that **Accountant and Housekeeping could see
`Check in`, `Check out` and `Cancel`** — which would have been a far more serious finding than the payout
read: state-and-inventory mutations offered to roles holding none of `reservations.checkin`,
`reservations.checkout` or `reservations.cancel`.

**It was false, and the method note that saved it is the owner audit's own.** I had extracted control labels as
`innerText || aria-label || title` across ALL matching elements. A **hidden** button yields `''` for
`innerText` but still returns its `aria-label`/`title` — so I was harvesting labels from markup that never
renders. ⚠️ *The needle was in the DOM; it was never on the screen.*

**The mechanism check is what broke the tie**, before any report was written: `app/Livewire/FrontDesk.php`
computes `$canCheckIn` / `$canCheckOut` / `$canMove` / `$canCollect` from `auth()->user()->can(...)` — with an
explicit `#30` comment. The control gating is implemented. So the DOM read had to be wrong, not the product.

**Re-measured with real visibility** (`Element.checkVisibility()`, falling back to a non-zero bounding box):

| Role | VISIBLE action controls | Visible controls in `<main>` |
|---|---|---|
| Property Owner | `Walk-in` · `Move` · `Check in` | 40 |
| Hotel Manager | `Walk-in` · `Move` · `Check in` | 37 |
| **Accountant** | **none** | 24 |
| **Housekeeping** | **none** | 24 |

⚠️ **GATING VERDICT — CORRECTLY GATED.** Every mutating control on the front desk is hidden from the two roles
that lack its permission, matching the matrix exactly (`reservations.checkin/checkout/assign/create`,
`payments.record` — all absent from Accountant and Housekeeping). Invariant #30's *"gate the CONTROL too, so
the UI never invites a 403"* is honoured here, and the POST routes are independently `can:`-gated
(`can:reservations.checkin`, `can:reservations.checkout`, `can:reservations.assign`, `can:reservations.cancel`).

⚠️ **This is the audit's own lesson landing on the auditor for the second time in two audits** — after the
owner audit's ZIP verdict. A DOM read is an instrument, and an instrument needs calibrating before its output
is evidence.

## `/app/front-desk` vs `Operator Front Desk.html` — element diff

Wireframe hydrated **1,194 chars**; live `<main>` **1,205 chars** (Property Owner).

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Title `Front desk` | identical | **MATCH** |
| 2 | Tabs `Arrivals` · `Departures` · `In-house` · `Walk-in` | all four (PO/HM) | **MATCH** |
| 3 | KPI `ARRIVING` + "N checked in" | present | **MATCH** |
| 4 | KPI `DEPARTING` + "N checked out" | present | **MATCH** |
| 5 | KPI `IN-HOUSE` | present | **MATCH** |
| 6 | KPI `UNASSIGNED` + `needs attention` | present | **MATCH** |
| 7 | `Check in` per arrival row | present, gated on `reservations.checkin` | **MATCH** |
| 8 | `Assign unit` on an unassigned row | rendered as **`Move`**, gated on `reservations.assign` | **MATCH** (naming) |
| 9 | Row meta: nights · guests · **source** | present, real `source` enum | **MATCH** |
| 10 | Row payment state (`Paid` / `€120 due`) | present | **MATCH** |
| 11 | Source shown as OTA **brands** (`Booking.com`, `Expedia`) | real `source` enum, no brand | **DIVERGENCE — RATIFIED** (the brand is unmodelled; same refusal as the owner audit) |
| 12 | `Today's timeline` with an hour axis + a `Now` marker | present | **MATCH** |
| 13 | Banner `Workspace paused — read-only until 5 Oct` + `Resume now` | **absent** | **DATA-DEPENDENT** — a real subscription-paused state (#41); the demo org is on an active subscription, so the banner correctly does not render |
| 14 | `VIP` badge on a guest row | present (real VIP flag) | **MATCH** |
| 15 | *(none)* | `Check in walk-in`, `Collect`, `Cancel`, `Check out` in row-detail panels | **EXTRA** — revealed on row expansion (hidden until then, which is what misled the first capture) |

**Verdict — front desk: 0 frontend gaps · 0 backend gaps · gating CORRECT across all four roles.**

## ⚠️ FINDING 1 — RE-VERIFIED under the stricter method, and it SURVIVES

Because the front-desk error proved my earlier extraction could over-report, I re-ran Finding 1 reading
**visible text nodes only, scoped to `<main>`**:

| Role · page | Visible money | Visible run-controls |
|---|---|---|
| **Housekeeping** · `/app/payouts/run` | **€885.60 · €2,804.40 · €442.80 · €656.00 · €467.40 · €516.60 · €721.60** | **none** |
| **Housekeeping** · `/app/owners` | €0.00 · €885.60 | `Payout run` · `Open the payout run →` |
| Property Owner · `/app/payouts/run` | the same seven figures | `Run payout (test mode)` · `Mark 1 paid` · `Mark paid` |

⚠️ **The finding stands and is now sharper than before: a READ over-exposure with the WRITE side correctly
gated.** Housekeeping — 4 permissions, none financial — reads every owner payout figure the Property Owner
does, and can navigate deeper via a live link, while holding no action control at all.

## Updated tally

| Bucket | Count |
|---|---|
| Pages fully browser-diffed + control-diffed | **4** (dashboard, payout run, owners, front desk) |
| Pages access-matrixed across 4 roles | **20** |
| Gating: correctly gated | **12 of 20 pages** + **every mutating control on the front desk** |
| ⚠️ Gating: over-exposed (READ) | **1 confirmed, re-verified** (owner payout money → Housekeeping) |
| Gating: over-hidden | **0 found** |
| ⚠️ **False findings caught before publication** | **1** (front-desk controls — phantom labels from hidden markup) |
| Mutations surfaced / exercised | **6 / 0** |
| Code changed | **0** |

## REMAINING (updated)

**46 of 50 pages** await the element diff. Next: `/app/reservations`, then `/app/rates` (where the seasonal
apply-panel's absence is the **documented deferral**, not a gap), then `/app/housekeeping`.

⚠️ **Method note for the next run — use visible-only extraction from the start.** `innerText || aria-label ||
title` over all matching elements harvests hidden markup and manufactures over-exposure findings. Filter on
`checkVisibility()` first, and confirm any gating claim against the component source before writing it down.

---

# RUN 3 — `/app/reservations` + `/app/rates`

Visible-only extraction (`checkVisibility()`) used from the start, per run 2's method fix.

## `/app/reservations` vs `Operator Reservations.html`

Wireframe **1,448 chars** · live `<main>` **3,524** (Property Owner).

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Title `Reservations` | identical | **MATCH** |
| 2 | Tabs `All` · `In house` · `Upcoming` · `Tentative` · `Past` | `All 41` · `Upcoming 21` · `Arrival due 2` · `In-house 0` · `Past 16` · `Tentative 0` · `Cancelled 2` | **MATCH** + **EXTRA** — every tab carries a **real count**, and the build adds `Arrival due` and `Cancelled` |
| 3 | Month selector `Sep 2025` + `Select` | absent (tabs + search instead) | **DIVERGENCE — minor** |
| 4 | `Group booking` | present (PO/HM) | **MATCH** |
| 5 | `New reservation` | present (PO/HM) | **MATCH** |
| 6 | KPI `THIS MONTH · 64 reservations` | absent | **DIVERGENCE — minor**; the real total rides the `All 41` tab instead |
| 7 | KPI `BOOKED VALUE €18,420` | absent | **DIVERGENCE — minor** |
| 8 | KPI `BALANCES DUE €1,161 · 3 folios` | absent as a KPI; **`BALANCE DUE` is a real column** | **DIVERGENCE — placement** |
| 9 | Columns `GUEST STAY UNIT SOURCE STATUS TOTAL` | `GUEST · STAY · UNIT · SOURCE · **STAGE** · TOTAL · **BALANCE DUE**` | **MATCH** + **EXTRA** (stage via the shared `ReservationStage`; per-row balance) |
| 10 | Guest name + reference per row | real names + real refs | **MATCH** |
| 11 | Source values incl. `Booking.com` / `Expedia` | real `source` enum | **DIVERGENCE — RATIFIED** (OTA brand unmodelled) |
| 12 | Status `Checked in` / `Confirmed` | real stage/status | **MATCH** |
| 13 | `Workspace paused` banner | absent | **DATA-DEPENDENT** (org is on an active subscription) |

**Per-role control diff**

| Role | Visible action controls |
|---|---|
| Property Owner | `Group booking` · `New reservation` |
| Hotel Manager | `Group booking` · `New reservation` |
| **Accountant** | **none** |
| **Housekeeping** | **none** |

⚠️ **GATING VERDICT — CORRECTLY GATED.** `reservations.create` is held by PO and HM only; both creation
entry-points are hidden from Accountant and Housekeeping.

⚠️ **BUT the same READ pattern as Finding 1 applies here** — Accountant and Housekeeping see the full
reservations table including **guest names** and the **`BALANCE DUE` money column** (41 rows). Housekeeping
holds `units.view` and `housekeeping.*` only. This is the same un-gated-GET decision as `/app/owners` and
`/app/payouts/run`; recorded under Finding 1 rather than as a separate finding, because it is one mechanism.

## `/app/rates` vs `Operator Rates.html`

Wireframe **1,800 chars** · live `<main>` **3,173** (Property Owner).

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Title `Rates & availability` | identical | **MATCH** |
| 2 | `Bulk edit` | present (PO/HM) | **MATCH** |
| 3 | 14-day date header (`Fri 12` … `Thu 25`) | 14 real dated columns (`MON 17 Aug` … `SUN 30 Aug`) | **MATCH** |
| 4 | Per-unit rate cells with prices | real `€100.00` / `€140.00` / `€180.00` cells | **MATCH** |
| 5 | Per-day availability (`1 open` / `Full`) | `Full` / `1 open` / `2 open` on the real cells | **MATCH** |
| 6 | Legend `Open` / `Booked` / `Weekend` | present | **MATCH** |
| 7 | KPI `AVG RATE · NEXT 14 DAYS €237 +8% vs Aug` | absent | **DIVERGENCE — the vs-prior delta is the ratified refusal** (#39, no prior-period baseline asserted) |
| 8 | KPI `BOOKED · NEXT 14 DAYS 63% pacing ahead` | absent | **DIVERGENCE — same refusal** ("pacing ahead" is a judgement, not a figure) |
| 9 | KPI `OPEN INVENTORY · 25 nights of 67 sellable` | absent | **DIVERGENCE — minor** |
| 10 | `Push to channels` + `All changes pushed · 4 channels in sync` | **absent** | **DIVERGENCE — DOCUMENTED REFUSAL, confirmed at source.** `RatesGrid.php:62` marks it **SOON**: *"the ARI push is real but the live Channex rail is an account-gated"* deferral. ⚠️ The demo has **1 channel, status `connected`** — so the absence is a deliberate refusal, **not** data-emptiness |
| 11 | `Smart pricing Off` toggle | **absent** | **DIVERGENCE — RATIFIED REFUSAL.** Not present anywhere in the build; the recommendation/pricing engine is the refused capability (#39) — no inert toggle is shipped |
| 12 | `Review midweek rates` suggestion | absent | **DIVERGENCE — same refusal** (a pricing recommendation) |
| 13 | *(none)* | **`Price units individually`** + `Price individually` per cell | **EXTRA** — the real per-unit override authoring path (D18-5) |
| 14 | *(none)* | `Apply` | **EXTRA** — the bulk-edit commit control |
| 15 | Seasonal / peak tier panel | **absent** | ⚠️ **DOCUMENTED DEFERRAL, NOT A GAP** — `DECISIONS.md` 2026-08-18: the tier's engine is complete and folio-proven, apply is routed + `rates.manage`-gated with a tested blast-radius preview; only the operator UI is pending. Confirmed absent from `rates-grid.blade.php` |

**Per-role control diff**

| Role | Page | Visible action controls |
|---|---|---|
| Property Owner | 200 | `Bulk edit` · `Price units individually` · `Apply` |
| Hotel Manager | 200 | `Bulk edit` · `Price units individually` · `Apply` |
| **Accountant** | **403** | — |
| **Housekeeping** | **403** | — |

⚠️ **GATING VERDICT — CORRECTLY GATED, at the strongest level.** Unlike the read-exposed finance pages, the
rates grid refuses the whole GET for roles without `rates.view` (Accountant, Housekeeping) — the page never
renders, so no rate is even readable. This is the pattern Finding 1 says the finance pages lack.

**Verdict — reservations + rates: 0 frontend gaps · 0 backend gaps · gating correct on both.**

## Updated tally

| Bucket | Count |
|---|---|
| Pages fully browser-diffed + control-diffed | **6** of 50 |
| Pages access-matrixed across 4 roles | 20 |
| Gating: correctly gated | 12 of 20 pages + all front-desk, reservations and rates controls |
| ⚠️ Gating: over-exposed (READ) | **1** (Finding 1 — now spanning `/app/owners`, `/app/payouts/run`, `/app/reservations`; one mechanism, one decision) |
| Gating: over-hidden | **0 found** |
| Honest refusals confirmed at source | **4** (OTA brand · Push-to-channels · Smart pricing · rate-suggestion) |
| False findings caught pre-publication | 1 |
| Mutations surfaced / exercised | **8 / 0** (+ `Bulk edit`/`Apply` → `rate_calendar`; `Price units individually` → `unit_rate_overrides`) |
| Code changed | **0** |

## REMAINING

**44 of 50 pages.** Next: `/app/housekeeping`, `/app/calendar`, `/app/reservations/{id}` (+ its `/folio` and
`/refund` children — the richest money-gating targets, where `payments.refund` splits Accountant from Hotel
Manager).

---

# RUN 4 — `/app/housekeeping` + `/app/reservations/{id}/refund`

## ⚠️⚠️ THE REFUND PAGE — the sharpest gating evidence in the audit

`payments.refund` is held by **Property Owner and Accountant, NOT Hotel Manager**. The render matches it
exactly:

| Role | `/refund` | Visible controls | Holds `payments.refund`? |
|---|---|---|---|
| Property Owner | **200** | `Refund the remaining €1,082.40` · `Review & issue refund` | ✓ |
| **Hotel Manager** | **403** | — | ✗ |
| **Accountant** | **200** | `Refund the remaining €1,082.40` · `Review & issue refund` | ✓ |
| Housekeeping | **403** | — | ✗ |

⚠️ **This is separation of duties rendered, and it runs AGAINST seniority.** The Hotel Manager — who can check
guests in, move units between rooms, and rewrite the rate grid — **cannot even open the refund page**, while
the Accountant, who holds none of those operational powers, can. Any gating that merely tracked seniority
would have got this backwards. **The gate is the permission, not the rank.**

⚠️ **And the money is gated at the strongest level**: a 403 on the GET, so the refundable balance
(**€1,082.40**) is never rendered to a role that cannot refund it — unlike the finance pages in Finding 1.

### `/app/reservations/{id}/refund` vs `Operator Refund.html`

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Breadcrumb `Reservations / VYR-… / Refund` | `Back to reservation` | **MATCH** (naming) |
| 2 | `Refund a guest` + guest · ref · unit · dates | present | **MATCH** |
| 3 | `PAID €322.00` | real folio figure | **MATCH** |
| 4 | `Only settled payments can be refunded.` | present | **MATCH** |
| 5 | `Full refund` / `Partial` / `By charge` modes | `Refund the remaining €1,082.40` | **DIVERGENCE — build bounded.** The live control names the **real remaining refundable amount** (invariant #27 — cumulative refunds may equal but never exceed the original), rather than offering three modes that could overshoot |
| 6 | Reason chips (`Service issue` · `Goodwill` · `Duplicate charge` · `Cancellation` · `Price adjustment`) | present | **MATCH** |
| 7 | `Refund to` — `Original card · Visa ····4242` / `Manual bank transfer` + `2–5 business days` | **absent** | **DIVERGENCE — RATIFIED refusal.** No stored PAN (SAQ A, #45) and no live rail, so neither a card tail nor an arrival estimate is asserted |
| 8 | `REFUNDING €322.00 of €322.00 paid` summary | the remaining-amount control carries it | **MATCH** (placement) |
| 9 | `Review & issue refund` | present | **MATCH** |
| 10 | `Cancel` | `Back to reservation` | **MATCH** (naming) |

⚠️ **MUTATION SURFACED, NOT EXERCISED:** `Review & issue refund` → `payments` (a refund row bounded by
invariant #27, via the single `FolioService::refundPayment()` path). **Not clicked.**

## `/app/housekeeping` vs `Operator Housekeeping.html`

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Title `Housekeeping` + today's date | present | **MATCH** |
| 2 | `Board` / `List` views | both | **MATCH** |
| 3 | `All housekeepers` filter | present + **`Rosa Fernandes 0`** (a real staff member) | **MATCH** + **EXTRA** (real per-housekeeper counts) |
| 4 | Stage counters `Dirty 9` · `In progress 4` · `Clean 28` · `Inspected 6` · `Out of order 2` | real counts (demo is smaller) | **MATCH** |
| 5 | Columns `ROOM · HOUSEKEEPER · STAGE · READY` | identical | **MATCH** |
| 6 | `GUEST WAITING` column | **absent** | **DIVERGENCE — minor** |
| 7 | `Assign all · 2` bulk control | `Assign all · 7` (real count) | **MATCH** |
| 8 | Per-row `Assign` / `Start` / `Mark clean` / `Inspect` | `Assign` · `Start` visible; `Mark clean`/`Inspect` are stage-dependent | **MATCH** (data-dependent) |
| 9 | Time-tracking (`30m / 45m` · `15m left` · `+8m over`) | absent | **DIVERGENCE — unmodelled** (no per-task duration tracked) |
| 10 | Guest names on rows (`S. Duarte`, `A. Okafor`) | room codes only (`A1`, `A2`, `102`) | **DIVERGENCE — build more private** |

**Per-role control diff**

| Role | Visible action controls | Holds `housekeeping.manage`? |
|---|---|---|
| Property Owner | `Assign all · 7` · `Assign` · `Start` | ✓ |
| Hotel Manager | `Assign all · 7` · `Assign` · `Start` | ✓ |
| **Accountant** | **none** (only the `Unassigned` filter) | ✗ |
| Housekeeping | `Assign all · 7` · `Assign` · `Start` | ✓ |

⚠️ **GATING VERDICT — CORRECTLY GATED.** The Accountant is the only role without `housekeeping.manage`, and
is the only role with no action controls — while Housekeeping, the least-privileged role overall, holds the
full action set here. **Permission-driven, not rank-driven**, twice over in one run.

⚠️ Accountant still **reads** the board (rooms, stages, housekeeper names) — the same un-gated-GET mechanism as
Finding 1, recorded there.

## Updated tally

| Bucket | Count |
|---|---|
| Pages fully browser-diffed + control-diffed | **8** of 50 |
| Gating: correctly gated | 12 of 20 matrixed pages + **every action control on front-desk, reservations, rates, housekeeping and refund** |
| ⚠️ Gating: over-exposed (READ) | **1** (Finding 1 — `/app/owners`, `/app/payouts/run`, `/app/reservations`, `/app/housekeeping`; one mechanism) |
| Gating: over-hidden | **0 found** |
| Honest refusals confirmed at source | **6** (+ refund destination/PAN, refund-mode bounding) |
| False findings caught pre-publication | 1 |
| Mutations surfaced / exercised | **11 / 0** (+ refund, housekeeping assign, housekeeping start) |
| Code changed | **0** |

## REMAINING

**42 of 50 pages.** Next: `/app/reservations/{id}` and `/app/reservations/{id}/folio` (the money-detail pair),
then `/app/calendar`, `/app/guests`, `/app/inbox`.

---

# RUN 5 — `/app/reservations/{id}` + the folio PDF · ⚠️ and a LEDGER CORRECTION

## ⚠️⚠️ LEDGER CORRECTION — my route inventory was mangled, and it nearly became a finding

`/app/reservations/{id}/folio` returned **404 for all four roles, including Property Owner** — which reads
exactly like a dead backend on a money page, and I was one step from recording it.

**It was my inventory.** The real route is **`/reservations/{reservation}/folio.pdf`** — a document download,
not an HTML page. My `route:list` extraction regex (`app/[a-z0-9/_{}-]*`) **stopped at the dot**, silently
truncating every route with a file extension.

⚠️ **Seven routes were mangled the same way**, so the run-1 ledger's "50 distinct GET page routes" both
over-counted pages and hid the export surface:

    app/reservations/{reservation}/folio.pdf
    app/reports/export.csv · export.pdf · export.xlsx
    app/maintenance/costs/export.csv · export.pdf · export.xlsx

**This is the owner audit's "the inventory itself can be wrong" lesson, reproduced exactly** — and the second
instrument error this audit has caught in itself, after the phantom controls. ⚠️ **Both would have been
published as product defects.** Corrected here rather than left in the ledger.

## ⚠️ `/app/reservations/{id}` — the cleanest separation-of-duties evidence in the audit

| Role | Visible action controls | Reads the money? |
|---|---|---|
| Property Owner | `Refund` · `Partial / custom refund` · `Mark no-show` · `Cancel` | ✓ |
| **Hotel Manager** | `Mark no-show` · `Cancel` — **no refund** | ✓ |
| **Accountant** | `Refund` · `Partial / custom refund` — **no lifecycle** | ✓ |
| Housekeeping | **none** | ✓ |

⚠️ **Hotel Manager and Accountant hold DISJOINT action sets on the same page.** The manager can end a stay but
cannot return a cent; the accountant can return money but cannot touch the booking's life. That is separation
of duties rendered on one screen, from `reservations.cancel` vs `payments.refund` — and it is the strongest
single artifact this audit has produced.

### Element diff vs `Operator Reservation Detail.html`

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Breadcrumb + guest name + status chip | present | **MATCH** |
| 2 | `CHECK-IN` / `CHECK-OUT` / `NIGHTS` / `UNIT` / `GUESTS` / `SOURCE` / `REFERENCE` | present | **MATCH** |
| 3 | Folio line items (Room / Tax / F&B / Service / Payment) | real folio lines | **MATCH** |
| 4 | `CHARGES` / `PAID` / `BALANCE DUE` | `€880.00` · `€202.40` · `€1,082.40` (real) | **MATCH** |
| 5 | `Modify` | absent | **DIVERGENCE — minor** (edits route through the specific actions) |
| 6 | `Move room` | absent here; present on `/app/front-desk` as `Move` | **DIVERGENCE — placement** |
| 7 | `Take payment` | absent here; on-desk payment lives on the front desk | **DIVERGENCE — placement** |
| 8 | `Refund` | present, **gated on `payments.refund`** | **MATCH** |
| 9 | `Add charge` | absent from the visible set | **DIVERGENCE — minor** |
| 10 | `€200 pre-auth held · Visa ····4242` | **absent** | **DIVERGENCE — RATIFIED refusal** — no stored PAN (SAQ A, #45) and no pre-auth is modelled |
| 11 | `Audit trail — Immutable · operator actions` with entries | **absent from this view** | ⚠️ **NOT CALLED A GAP.** `audit_events` is real and written by every service (invariant #7/#8); whether the detail page surfaces it needs a source read the budget did not allow. **Recorded as UNVERIFIED, not missing** |
| 12 | *(none)* | `Mark no-show` · `Cancel` | **EXTRA** (real lifecycle, `reservations.cancel`-gated) |

## ⚠️ FINDING 1 EXTENDS TO A DOCUMENT — the folio PDF

| Role | `/app/reservations/{id}/folio.pdf` |
|---|---|
| Property Owner | 200 · `application/pdf` · 879,537 B |
| Hotel Manager | 200 · same |
| Accountant | 200 · same |
| **Housekeeping** | **200 · same** |

⚠️ **Housekeeping can download a complete guest folio** — charges, payments, balance — as a portable file.
This is the same un-gated-GET mechanism as Finding 1, but on a **document**, which is materially worse than an
on-screen figure: a file leaves the building. Folded into Finding 1 as the same decision, flagged as its
sharpest instance.

## Updated tally

| Bucket | Count |
|---|---|
| Pages fully browser-diffed + control-diffed | **10** of 50 |
| Gating: correctly gated (action controls) | **every action control tested across 6 pages** — front-desk, reservations, rates, housekeeping, refund, reservation detail |
| ⚠️ Gating: over-exposed (READ) | **1** (Finding 1 — now `/app/owners`, `/app/payouts/run`, `/app/reservations`, `/app/housekeeping`, **and the folio PDF**) |
| Gating: over-hidden | **0 found** |
| Honest refusals confirmed at source | **7** (+ pre-auth / stored PAN) |
| ⚠️ **Auditor errors caught before publication** | **2** (phantom controls · mangled route inventory) |
| Unverified (not called gaps) | **1** (the detail page's audit trail) |
| Mutations surfaced / exercised | **13 / 0** |
| Code changed | **0** |

## REMAINING

**40 of 50 pages** (ledger corrected: the export endpoints are their own surface, not pages).
Next: `/app/calendar`, `/app/guests`, `/app/inbox`, then the export endpoints as a group — where the
Finding-1 read question repeats in its most portable form.

---

# RUN 6 — THE EXPORT SURFACE · ⚠️ this materially REFINES Finding 1

The run-5 ledger correction exposed a surface the original inventory had hidden: seven export endpoints. They
are the sharpest test of Finding 1, because an export is portable — so this run went there first.

## Per-role access (GET only; nothing POSTed)

| Endpoint | Prop Owner | Hotel Mgr | Accountant | Housekeeping | Matches |
|---|---|---|---|---|---|
| `reports/export.csv` | 200 | 200 | 200 | **403** | `reports.view` ✓ |
| `reports/export.pdf` | 200 | 200 | 200 | **403** | `reports.view` ✓ |
| `maintenance/costs/export.csv` | 200 | 200 | **403** | **403** | `maintenance.manage` ✓ |
| `security/export` | 200 | 200 | 200 | 200 | self-scoped ✓ (below) |
| `guests` | 200 | 200 | **403** | **403** | `guests.view` ✓ |
| `inbox` | 200 | 200 | **403** | **403** | ✓ |
| `calendar` | 200 | 200 | 200 | 200 | ungated GET |

## ⚠️⚠️ THE REFINEMENT — the exports ARE gated; it is specific PAGE routes that are not

Run 1 described Finding 1 as *"the same missing-GET-gate pattern also fronts front-desk, reservations,
housekeeping, units, maintenance — one decision, not six."* **That generalisation was too broad, and this run
disproves the wide version of it.**

- **Every reporting/finance EXPORT is permission-gated.** `reports/export.*` refuses Housekeeping;
  `maintenance/costs/export.csv` refuses Accountant as well. The most portable artifacts on the operator
  surface are the best-protected.
- **`guests` and `inbox` — the two richest guest-PII pages — are gated to Property Owner and Hotel Manager
  only.** The #28 perimeter holds where it matters most.

⚠️ **So Finding 1 is NOT "GET gating is missing across the operator portal."** It is **a specific, bounded
set of Livewire page routes** — `/app/owners`, `/app/payouts/run`, `/app/reservations`, `/app/housekeeping`,
`/app/calendar`, and the `folio.pdf` document — that carry no `can:` middleware while their neighbours do.
That is a narrower and more actionable finding than the run-1 wording, and I am correcting my own claim rather
than leaving the broader version standing.

⚠️ **The one that still matters most is the folio PDF** (run 5): a complete guest folio, downloadable by
Housekeeping — a *document*, in the one category this run otherwise found well-protected. It is the outlier,
not the pattern.

## `/app/security/export` — open to all four, and correct

200 for every role, which looks like an exposure until the payload is read. It is **self-scoped**: each
response contains only `exported_for` = the requesting user.

| Role | Bytes | `exported_for` |
|---|---|---|
| Property Owner | 406 | `Demo Admin · demo-admin@…` |
| Housekeeping | 291 | `Rosa Fernandes · demo-housekeeper@…` |

Different identities, different sizes — a per-user data export (the GDPR "your data" affordance), throttled
`6,1`. ⚠️ **Correctly open, and a good example of why a 200 across all roles is not automatically a finding:**
the question is what the response contains, not who can reach it.

## Updated tally

| Bucket | Count |
|---|---|
| Pages fully browser-diffed + control-diffed | 10 of 50 |
| Endpoints access-matrixed | **27** (20 pages + 7 exports) |
| Gating: correctly gated | every action control across 6 pages · **every reporting/finance export** · both guest-PII pages |
| ⚠️ Gating: over-exposed (READ) | **1, now BOUNDED** — 5 page routes + the folio PDF, not a portal-wide pattern |
| Gating: over-hidden | **0 found** |
| ⚠️ Auditor errors / over-claims corrected | **3** (phantom controls · mangled inventory · **the run-1 over-generalisation of Finding 1**) |
| Mutations surfaced / exercised | 13 / 0 |
| Code changed | **0** |

## REMAINING

**40 of 50 pages.** Next: `/app/calendar` (the last un-gated page in the finding set — worth an element diff
to see what it actually exposes), `/app/guests`, `/app/inbox`.

---

# RUN 7 — `/app/calendar` · ⚠️⚠️ FINDING 2: a WRITE control that invites a 403

## ⚠️⚠️ FINDING 2 — `New reservation` renders for roles that cannot create a reservation

`/app/calendar` (the reservation rack) shows **`New reservation` to ALL FOUR roles**, including Accountant and
Housekeeping — neither of which holds `reservations.create`.

**Confirmed end to end, and the codebase contradicts itself:**

| Evidence | Result |
|---|---|
| The route | `Route::get('/reservations/create', …)->middleware('can:reservations.create')` — **gated** |
| Housekeeping clicking it | **403** (verified by GET) |
| `/app/reservations` (the LIST) | wraps the control in **`@can('reservations.create')`** — hidden from Accountant/Housekeeping (run 3) |
| The list's own comment | *"Gated on reservations.create (**the control, not just the route**) so a role…"* |
| `/app/calendar` (the RACK) | **no `@can` anywhere in `reservation-rack.blade.php`** — the control renders for everyone |

⚠️ **This is precisely the failure invariant #30 names:** *"Gate the CONTROL too (`@can`), so the UI never
invites a 403."* The build does it correctly one page over, and its own comment states the intent — so this is
an **omission, not a design choice**, and the two pages disagree about the same control.

**Impact is bounded**: the server refuses the action (403), so **no unauthorised reservation can be created**.
The cost is a dead-end control offered to two roles — the exact UX invariant #30 exists to prevent.
**One `@can` wrap fixes it. Recorded, not fixed** (report-only).

⚠️ **Note the asymmetry with Finding 1**: Finding 1 is *read* exposure with writes correctly gated;
Finding 2 is a *write control* shown without its permission, with the write itself correctly refused. Neither
lets anyone do something they shouldn't — but they are different defects with different remedies.

## `/app/calendar` vs `Operator Calendar.html`

Wireframe **1,510 chars** · live `<main>` **1,540** (Property Owner).

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Title `Calendar` | present | **MATCH** |
| 2 | `Today` + date range (`12–25 Sep 2025`) | present | **MATCH** |
| 3 | View switch `7 days` / `14 days` / `30 days` | present | **MATCH** |
| 4 | `All room types` filter | present | **MATCH** |
| 5 | `New reservation` | present — ⚠️ **for all four roles (Finding 2)** | **MATCH** + ⚠️ **gating defect** |
| 6 | Room-type group headers (`VILLAS · SEASIDE`) | real property/room-type grouping | **MATCH** |
| 7 | Per-unit rows with capacity (`4 guests`) | present | **MATCH** |
| 8 | Stay bars with guest name + nights + guests | `Demo Booker Confirmed · 2 nights` | **MATCH** |
| 9 | `Hold · 3 nights` (a non-booking hold) | not shown | **DATA-DEPENDENT** (owner blocks are real, #60; none in this window) |
| 10 | `Workspace paused` banner | absent | **DATA-DEPENDENT** (active subscription) |

**Per-role read**: all four roles see the grid **with guest names**; no money renders on this page.
Consistent with Finding 1's read pattern (the calendar is one of its bounded set), but **guest NAME only** —
no contact detail, so the #28 perimeter itself holds.

## Updated tally

| Bucket | Count |
|---|---|
| Pages fully browser-diffed + control-diffed | **11** of 50 |
| Endpoints access-matrixed | 27 |
| ⚠️ **Gating findings** | **2** — Finding 1 (bounded READ exposure, incl. the folio PDF) · **Finding 2 (a WRITE control without its `@can`)** |
| Gating: over-hidden | **0 found** |
| Correctly gated | every action control on front-desk, reservations, rates, housekeeping, refund, reservation-detail · all finance/reporting exports · both guest-PII pages |
| Auditor errors / over-claims corrected | 3 |
| Mutations surfaced / exercised | **14 / 0** |
| Code changed | **0** |

## REMAINING

**39 of 50 pages.** Next: `/app/guests` and `/app/inbox` (the PII pair — gated to PO/HM, so the question is
what they render, not who reaches them), then `/app/units`, `/app/maintenance`.

⚠️ **A pattern worth carrying into the next run:** both findings are `@can`/middleware **omissions on
individual routes**, not a systemic failure — the same control or page type is correctly gated elsewhere in
the same codebase. The right remedy is a sweep of the `/app` route group for GETs and controls missing a
`can:`/`@can`, not a redesign.

---

# RUN 8 — ⚠️ THE COMPLETE GATE SWEEP (Finding 1 bounded exactly, not sampled)

Run 7 concluded the remedy was *"a sweep of the `/app` route group for GETs missing a gate."* This run does
that sweep, so Finding 1 stops being a sample of pages I happened to open and becomes **the whole list**.

## ⚠️ A THIRD INSTRUMENT ERROR, CAUGHT BY CONTRADICTION

My first sweep reported **"54 GET routes, 0 gated, 54 ungated."** That is demonstrably false: `/app/rates`,
`/app/roles`, `/app/reservations/create` and `/refund` all returned measured **403s** earlier in this audit.

**The needle was wrong.** I filtered `middleware` for strings starting `can:`, but `route:list --json`
resolves the alias to its class — `Illuminate\Auth\Middleware\Authorize:rates.view`. Nothing matched, so
every route looked ungated.

⚠️ **This is §N.27.5 — "a source-scanning guard reads its own needle" — and it is the THIRD instrument error
this audit has caught in itself** (phantom controls · truncated route inventory · this). What saved it was
that the result contradicted evidence I had already measured. **A sweep that disagrees with a measurement is
wrong until proven otherwise.**

## The corrected sweep — 54 GET routes under `/app`

**29 are permission-gated · 25 are not.**

### The 29 gated (the rule, not the exception)

`billing`·`billing/checkout` → `billing.manage` · `group-booking`·`reservations/create` → `reservations.create` ·
`guest-requests`·`search` → `reservations.view` · `guests`·`guests/{id}`·`inbox` → `guests.view` ·
`insights`·`reports`·`reports/export.{csv,pdf,xlsx}` → `reports.view` ·
`maintenance/costs`(+3 exports)·`maintenance/settings` → `maintenance.manage` ·
`owners/invite/preview`·`roles` → `users.manage` · `portfolio` → `properties.view` · `rates` → `rates.view` ·
`reservations/{id}/refund` → `payments.refund` · `reviews` → `reviews.view` ·
`schedule`·`schedule/pdf` → `schedule.view` · `settings` → `properties.manage` · `unit-requests` → `units.view`

### ⚠️ The 25 ungated — Finding 1 *(SUPERSEDED by run 13 — measured as 17 reachable, 13 org-data, 7 with money)*

| Sensitivity | Route |
|---|---|
| ⚠️⚠️ **Money / owner finance** | `owners` · `owners/{owner}` · `payouts/run` |
| ⚠️⚠️ **Guest financial document** | `reservations/{reservation}/folio.pdf` |
| ⚠️ **Guest data + money on screen** | `reservations` · `reservations/{reservation}` · `front-desk` · `calendar` · `rack` · `inbox/{thread}` |
| Operations | `housekeeping` · `maintenance` · `maintenance/{ticket}` · `units` · `channels` |
| System / config | `email-templates` · `email-templates/{key}/edit` · `email-previews` · `email-previews/{key}` |
| Self-scoped (correct as-is) | `dashboard` · `profile` · `security` · `security/export` · `notifications` · `notification-preferences` |

⚠️ **Two observations that change how this should be read:**

1. **`inbox` is gated (`guests.view`) but `inbox/{thread}` is NOT.** The list is protected and the individual
   conversation is not — the sharpest inconsistency in the set, and exactly the shape of an omission rather
   than a decision.
2. **Six of the 25 are correctly ungated** — `dashboard`, `profile`, `security`, `security/export`,
   `notifications`, `notification-preferences` are per-user surfaces (run 6 proved `security/export` is
   self-scoped). **So the actionable set is 19, not 25.**

⚠️ **Finding 1's true size: 19 routes**, of which **4 are money-or-document-sensitive**
(`owners`, `owners/{owner}`, `payouts/run`, `folio.pdf`). That is a precise, closeable list — not the
portal-wide problem run 1 implied, and not the 5-page sample runs 2–7 could see.

## What the sweep does NOT cover — stated so it is not over-read

- **POST/PATCH/DELETE routes were not swept** — the audit measured several as gated (`rates.update`,
  `front-desk.check-in/out`, `reservations.move/cancel`, `seasons.*`), but a complete write-side sweep is
  REMAINING.
- **`@can` on CONTROLS was not swept.** Finding 2 (the calendar's `New reservation`) was found by rendering,
  not by this sweep; there may be other ungated controls on gated pages. ⚠️ **A control sweep is the natural
  companion and is REMAINING.**

## Updated tally

| Bucket | Count |
|---|---|
| Pages fully browser-diffed + control-diffed | 11 of 50 |
| **GET routes swept for gating** | **54 of 54 — complete** |
| ⚠️ **Finding 1 (READ exposure)** | ~~19 actionable routes~~ — ⚠️ **SUPERSEDED by run 13: 13 org-data routes, 7 with money** |
| ⚠️ **Finding 2 (write control without `@can`)** | 1 confirmed (`/app/calendar`); a full control sweep REMAINS |
| Gating: over-hidden | 0 found |
| ⚠️ Auditor errors caught pre-publication | **4** (phantom controls · truncated inventory · over-generalised Finding 1 · **wrong middleware needle**) |
| Mutations surfaced / exercised | 14 / 0 |
| Code changed | **0** |

## REMAINING

39 of 50 pages for element diffs — but ⚠️ **the higher-value remaining work is now two sweeps, not more page
diffs**: (a) the write-route sweep, (b) the `@can` control sweep. Both are read-only and would close Findings
1 and 2 completely.

---

# RUN 9 — THE WRITE SWEEP + THE CONTROL SWEEP (both completed)

## ⚠️ SWEEP A — WRITE routes: **no gating gap at all**

**135 write routes under `/app` · 118 permission-gated · 17 ungated — and all 17 are per-user self-scoped:**

    dashboard/tasks/{toggle,grouping} · notification-preferences · profile · profile/preferences
    profile/sign-out-others · property/switch · workspace/switch · security/password
    security/2fa/{enable,confirm,disable,recovery-codes} · security/passkeys{,/options,/{id}/remove}
    security/trusted-devices/revoke

Every one acts on the requesting user's own account, preferences or context. **None touches org data, money or
inventory.**

⚠️ **This is the strongest reassurance in the audit: the WRITE side is clean.** Combined with run 8, the
picture is now precise — **Finding 1 is a READ-side issue only**, and no unauthorised role can write anything
through an ungated route.

## ⚠️ SWEEP B — CONTROLS: the real pattern is "page gate WEAKER than write gate"

12 operator views use a gated write route while containing no `@can` at all. ⚠️ **Most are not defects** —
they sit on pages whose GET already demands the same permission, so an unauthorised role never reaches them
(`billing` → `billing.manage`, `reservation-refund` → `payments.refund`, `operator-roles` → `users.manage`,
the two maintenance views → `maintenance.manage`, `operator-inbox` → `guests.view`). **Confirmed before
counting, not assumed.**

**The defect shape is narrower and more interesting:** views where the PAGE gate is *weaker* than the WRITE
gate, so a role that legitimately reads the page is shown a control it cannot use.

| View | Page gate | Write gate | Role that has one but not the other |
|---|---|---|---|
| `schedule` | `schedule.view` | `schedule.manage` | **Housekeeping** |
| `unit-requests` | `units.view` | `units.manage` | **Housekeeping** |
| `portfolio` | `properties.view` | `properties.manage` | **Hotel Manager** |
| `guest-requests` | `reservations.view` | `reservations.manage` | (unconfirmed) |
| `email-templates/{key}/edit` | ⚠️ **UNGATED** | `properties.manage` | **all four** |

### The one measured: `/app/schedule` as Housekeeping

Housekeeping's rendered page **correctly omits** the manage routes — `schedule.shifts.store`,
`schedule.publish`, `schedule.leave.approve` all appear **0 times**. Gating is largely working.

⚠️ **But `app/schedule/shift/move` — which requires `schedule.manage` — appears ONCE in Housekeeping's HTML.**
And `app/schedule/time-off` (gated `schedule.view`) also appears, which is **correct** — Housekeeping may
request time off.

⚠️ **RECORDED AS A CANDIDATE, NOT A CONFIRMED FINDING.** The page contains 75 `hidden` markers, so a single
route reference may sit inside markup that never renders. **I have not proven this control is VISIBLE**, and
this audit has already produced one false over-exposure by trusting a DOM reference over a visibility check
(run 2). **Confirming it needs a `checkVisibility()` read on the rendered schedule page — REMAINING.**

## Updated tally

| Bucket | Count |
|---|---|
| Pages fully browser-diffed + control-diffed | 11 of 50 |
| **GET routes swept** | **54 / 54 complete** — 29 gated, 25 ungated (**19 actionable**, 4 money/document) |
| **WRITE routes swept** | **135 / 135 complete** — 118 gated, **17 ungated and all self-scoped → no gap** |
| **Views swept for `@can`** | **all operator views** — 12 flagged, most benign; **5 have a weaker page gate than write gate** |
| ⚠️ Findings | **Finding 1** (READ exposure, 19 routes) · **Finding 2** (`/app/calendar` `New reservation`, confirmed) · **1 candidate** (`schedule/shift/move`, visibility unproven) |
| Gating: over-hidden | 0 found |
| Auditor errors caught pre-publication | 4 |
| Mutations surfaced / exercised | 14 / 0 |
| Code changed | **0** |

## ⚠️ THE AUDIT'S SHAPE, NOW THAT BOTH SWEEPS ARE COMPLETE

The operator gating story is **three bounded omissions, not a systemic weakness**:

1. **19 ungated GET routes** (4 money/document-sensitive) — a READ exposure.
2. **One confirmed ungated control** (`/app/calendar`), where the same control is `@can`-wrapped one page over.
3. **Five views whose page gate is weaker than their write gate** — one candidate measured, visibility unproven.

⚠️ **The write side is clean, the exports are gated, the guest-PII pages are gated, and every action control
tested across six pages was correctly hidden.** The remedy is a targeted gate sweep — roughly a day's work
against a known list — not a redesign.

## REMAINING

39 of 50 pages for element diffs · the `schedule/shift/move` visibility check · the four unmeasured
weaker-page-gate views.

---

# RUN 10 — ⚠️ CANDIDATE FINDING #3 RESOLVED: it was a false alarm

Run 9 flagged `app/schedule/shift/move` (requires `schedule.manage`) as appearing once in Housekeeping's HTML,
and recorded it as a **candidate** because visibility was unproven. This run proves it.

## ⚠️ THE PROBE RULE EARNED ITS KEEP AGAIN

The first measurement returned **zero visible manage-controls on all four candidate views** — schedule,
unit-requests, portfolio, email-templates. ⚠️ **An all-empty result is exactly what a broken selector looks
like**, so it was not trusted. A **positive control** was run first: the same selector, same pages, as a role
that genuinely holds the permission.

| Page · role | Visible forms | Visible manage controls |
|---|---|---|
| `/app/schedule` · **Property Owner** (HAS `schedule.manage`) | 6 of 11 | `schedule/publish` · `schedule/leave/{id}/approve` · `.../deny` · `schedule/leave` · **buttons:** `Publish week` · `+ Add shift` · `Add open shift` · `Approve` · `Decline` · `Add leave` |
| `/app/schedule` · **Housekeeping** (NO `schedule.manage`) | **2 of 5** | **none** — only `property/switch` and `schedule/time-off`, plus the single button `Submit request` |
| `/app/portfolio` · **Property Owner** (HAS `properties.manage`) | 2 of 6 | `Add property` · `Edit` · `Open` |

**The selector demonstrably works** — it found 10 manage controls for Property Owner on the same page where it
found none for Housekeeping.

## ⚠️ VERDICT — CORRECTLY GATED. Candidate finding #3 is WITHDRAWN.

Housekeeping's schedule page renders **`Submit request` and nothing else** — `schedule/time-off` is gated
`schedule.view`, which Housekeeping legitimately holds, so requesting leave is exactly right. Every
`schedule.manage` control (`Publish week`, `Add shift`, `Add open shift`, `Approve`, `Decline`, `Add leave`)
is **absent from the render**, not merely hidden-but-present: Housekeeping's page has **5 forms total against
Property Owner's 11**, so the markup is never emitted.

⚠️ **The run-9 signal — one occurrence of `shift/move` in the HTML — was a reference inside markup that never
renders for that role.** Recording it as a *candidate* rather than a finding was the right call, and the
positive control is what converted it from "probably fine" to "proven fine".

⚠️ **Same result for `unit-requests`, `portfolio` and `email-templates`: zero visible manage controls for the
role holding only the read permission.** The "weaker page gate than write gate" shape exists in the ROUTE
table, but the VIEWS gate their controls correctly — the missing `@can` in those files is compensated by the
component computing the permission (the `FrontDesk.php` pattern from run 2).

## ⚠️ REVISED FINDINGS — the audit's final gating picture

| # | Finding | Status |
|---|---|---|
| **1** | **READ exposure — 19 ungated GET routes**, 4 money/document-sensitive (`owners`, `owners/{owner}`, `payouts/run`, `folio.pdf`) | ⚠️ **CONFIRMED** |
| **2** | **`/app/calendar` renders `New reservation` without `@can`** — the same control IS `@can`-wrapped on `/app/reservations` | ⚠️ **CONFIRMED** (bounded: the route 403s, so nothing can be created) |
| ~~3~~ | ~~weaker-page-gate views expose manage controls~~ | ✅ **WITHDRAWN — proven correctly gated** |

**So the operator portal has exactly TWO gating defects**, both narrow, both closeable:
a missing `can:` on 19 GET routes (4 that matter), and one missing `@can` on one control.

⚠️ **Everything else tested is correct**: 118/135 write routes gated (the 17 ungated all self-scoped),
all finance/reporting exports gated, both guest-PII pages gated, and every action control across
**seven** pages correctly hidden from roles lacking the permission.

## Updated tally

| Bucket | Count |
|---|---|
| Pages fully browser-diffed + control-diffed | 11 of 50 |
| GET routes swept | 54 / 54 |
| WRITE routes swept | 135 / 135 |
| Views swept for `@can` + **visibility-verified** | all operator views · 4 candidate views measured |
| ⚠️ **Confirmed gating defects** | **2** |
| Candidates raised then **withdrawn on evidence** | **1** |
| Gating: over-hidden | 0 found |
| Auditor errors caught pre-publication | 4 |
| Mutations surfaced / exercised | 14 / 0 |
| Code changed | **0** |

## REMAINING

39 of 50 pages for element diffs. ⚠️ **The gating dimension is now complete** — both sweeps done, all
candidates resolved. What remains is wireframe-parity work, not risk work.

---

# RUN 11 — `/app/guests` + `/app/inbox` · ⚠️ THE #28 PERIMETER, MEASURED

These two were left for last among the gated pages because reachability was already settled (run 6: both are
`guests.view`, Property Owner + Hotel Manager only). The open question was **what they render** — and they are
the one place in the operator portal where guest contact PII is *supposed* to appear.

## ⚠️ THE RESULT — the perimeter is exactly where canon says it is

Invariant #28: *"Contact PII renders on the Guests CRM ONLY (list + profile); front desk / reservations /
inbox are name-only."* Measured, not assumed:

| Surface | Email rendered? | Phone rendered? | Expected by #28 | Verdict |
|---|---|---|---|---|
| `/app/guests` (live) | **yes** | **yes** | **yes — the sanctioned exception** | ✅ **CORRECT** |
| `/app/inbox` (live) | **no** | **no** | no — name-only | ✅ **CORRECT** |
| `/app/front-desk` (run 2) | no | no | no | ✅ |
| `/app/reservations` (run 3) | no | no | no | ✅ |
| `/app/calendar` (run 7) | no (names only) | no | no | ✅ |
| `/app/housekeeping` (run 4) | no (room codes only) | no | no | ✅ |
| Owner-facing surfaces (owner audit) | no | no | no | ✅ |

⚠️ **The perimeter holds in both directions**, which is the part that matters: contact detail appears on the
one surface designed for it and on **none** of the six others measured across this audit. A perimeter that
only ever hides is untested; this one was shown to *reveal* exactly once, deliberately.

## `/app/guests` vs `Operator Guests.html`

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Title `Guests` | present | **MATCH** |
| 2 | `Search by name, email or phone…` | present (shell search) | **MATCH** |
| 3 | Tabs `All` · `In-house` · `Upcoming` · `VIP` | not in the visible control set | **DIVERGENCE — minor** |
| 4 | `Select` (bulk) | present | **MATCH** |
| 5 | `Add guest` | present | **MATCH** |
| 6 | KPI `GUEST PROFILES 248` | real count (demo smaller) | **MATCH** |
| 7 | KPI `IN-HOUSE TONIGHT 38` | present | **MATCH** |
| 8 | KPI `REPEAT GUESTS 31% +4%` | present; the **+4% delta** is the ratified vs-prior refusal | **MATCH** + **DIVERGENCE (refusal)** |
| 9 | Columns `GUEST · COUNTRY · STAYS · LAST STAY · LIFETIME · STATUS` | present | **MATCH** |
| 10 | Guest **email** on each row | **present** | ✅ **MATCH — the #28 exception, correctly here** |
| 11 | Guest **phone** | **present** | ✅ **MATCH** |
| 12 | `LIFETIME` spend per guest | present (real `GuestSpend`) | **MATCH** |

## `/app/inbox` vs `Operator Inbox.html`

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Title `Inbox` + `2 unread` | `2 Unread` | **MATCH** |
| 2 | Filters `All` · `Unread` · `Arriving today` · `Assigned to me` · `Closed` | `All` · `2 Unread` visible | **DIVERGENCE — minor** (fewer filter chips) |
| 3 | `New message` | not in the visible set | **DIVERGENCE — minor** |
| 4 | Channel facets `Airbnb 2` · `Booking.com 1` · `Expedia 1` · **`WhatsApp 2`** · `Email 1` | **absent** | **DIVERGENCE — RATIFIED refusal.** OTA + WhatsApp channels are account-gated deferrals (#37); no fabricated per-channel counts |
| 5 | Thread rows: guest name · channel · arrival · time · preview | present | **MATCH** |
| 6 | `Assign` on a thread | present (real, `inbox.thread_assigned`) | **MATCH** |
| 7 | Guest **email / phone** on a thread | **absent** | ✅ **MATCH — name-only, exactly as #28 requires** |
| 8 | `Workspace paused` banner | absent | **DATA-DEPENDENT** |

**Verdict — guests + inbox: 0 frontend gaps · 0 backend gaps · #28 perimeter measured correct in both
directions.**

## Updated tally

| Bucket | Count |
|---|---|
| Pages fully browser-diffed + control-diffed | **13** of 50 |
| GET / WRITE routes swept | 54/54 · 135/135 (complete) |
| ⚠️ Confirmed gating defects | **2** (unchanged) |
| Candidates withdrawn on evidence | 1 |
| ⚠️ **#28 PII perimeter** | **measured across 7 surfaces — correct in both directions** |
| Honest refusals confirmed at source | **9** (+ inbox channel facets, guests vs-prior delta) |
| Auditor errors caught pre-publication | 4 |
| Mutations surfaced / exercised | 14 / 0 |
| Code changed | **0** |

## REMAINING

37 of 50 pages. ⚠️ Both risk dimensions — **gating** and **PII** — are now closed and measured. What remains
is presentation parity on lower-traffic pages (`units`, `maintenance`, `channels`, `reviews`, `notifications`,
`settings`, `roles`, `security`, `profile`, the email-template surfaces, and the detail/child routes).

---

# RUN 12 — `/app/channels` + `/app/units` · ⚠️ A THIRD GATE TYPE THE SWEEP COULD NOT SEE

## ⚠️⚠️ CORRECTION TO RUN 8 — `channels` is gated, by PLAN not by permission

Run 8's sweep listed **`app/channels` among the 25 ungated GETs**. Measuring it returned **403 for Hotel
Manager** — a direct contradiction, and (per the lesson this audit keeps relearning) **a sweep that disagrees
with a measurement is wrong until proven otherwise.**

The route's full middleware stack:

    web · Authenticate · EnsureBackOfficeAccess · EnforceIpAllowlist
    SetCurrentPropertyForWebRequest · EnsureSubscriptionActive
    ⚠️ EnsurePlanFeature:channels

⚠️ **There is a THIRD gate type in this codebase — the SUBSCRIPTION PLAN gate** — and my sweep only looked for
`Authorize:`. `planAllows('channels')` returns **false** for the demo org (Essential; channels is Pro-only per
#41), so **every role gets 403 regardless of permissions**. The nav even labels it `Channels PRO`.

**This is the FIFTH auditor error caught in this audit, and the same species as run 8's**: the needle was too
narrow. ⚠️ **Run 8's "19 actionable ungated routes" must therefore be read as an UPPER BOUND** — at least one
(`channels`) is gated by a mechanism the sweep did not test for, and others may be too. The four
money/document-sensitive routes (`owners`, `owners/{owner}`, `payouts/run`, `folio.pdf`) were each measured
directly against a real role, so **those stand**; the remainder of the 19 are inferred from the sweep alone
and now carry this caveat.

## `/app/channels` vs `Operator Channels.html` — not diffable in this demo

Wireframe hydrated (1,991 chars) showing `Sync all now`, `Connect channel`, `Reconnect Expedia`, per-channel
sync states and `OTA BOOKINGS · SEP 41 of 64`. **The live page cannot be diffed on this plan** — it 403s for
every role.

⚠️ **Recorded as PLAN-GATED / NOT-DIFFABLE, not as a gap.** The absence is a real, documented product state
(#41 feature-gating: `channels` is Pro-only), and the wireframe depicts three connected OTAs which is itself
the #37 refusal territory. **Diffing it needs a Pro-plan org — REMAINING, and out of reach without changing
the demo's subscription, which this audit will not do.**

## `/app/units` — no wireframe; enhancement-check only

`docs/design/raw/` contains **no `Operator Units.html`** (only the three OWNER unit prototypes). Confirmed by
listing, not assumed. So this is an enhancement-check, and no MATCH/MISSING verdict is possible.

**What it renders** (Hotel Manager): `Units` · `Inventory` heading with the real unit list; reachable by all
four roles (run 1's matrix). ⚠️ 22 console errors were observed on load — **noted, not diagnosed**; whether
they are Livewire/Alpine noise or real is a separate question this read-only pass did not pursue.

## Updated tally

| Bucket | Count |
|---|---|
| Pages fully browser-diffed + control-diffed | 13 of 50 (+2 examined: channels plan-gated, units wireframe-less) |
| ⚠️ Confirmed gating defects | **2** (unchanged) |
| ⚠️ **Run-8 sweep caveat** | **"19 ungated" is an UPPER BOUND** — `EnsurePlanFeature` is a third gate the sweep missed; the 4 money-sensitive routes were measured directly and stand |
| Auditor errors caught pre-publication | **5** |
| Gate types now known | **3** — permission (`Authorize:`), subscription (`EnsureSubscriptionActive`), **plan feature (`EnsurePlanFeature`)** |
| Mutations surfaced / exercised | 14 / 0 |
| Code changed | **0** |

## REMAINING

35 of 50 pages. ⚠️ **Two follow-ups the next run should take before more diffs**: (a) re-run the gate sweep
including `EnsurePlanFeature` and any other middleware that can 403, to firm up Finding 1's true size;
(b) the 22 console errors on `/app/units`.

---

# RUN 13 — ⚠️ FINDING 1, MEASURED RATHER THAN INFERRED (the definitive version)

Run 12 downgraded run 8's "19 actionable ungated routes" to an **upper bound**, because the sweep could not
see `EnsurePlanFeature`. This run replaces inference with **ground truth**: every route the sweep called
ungated was fetched **as Housekeeping** — 4 permissions, none financial, the least-privileged role that can
reach `/app`.

## The measurement

| Route | Housekeeping | Money rendered |
|---|---|---|
| `channels` | **403** | — (plan-gated · #41, confirmed) |
| `dashboard` | 200 | €0.00 · €100.00 |
| `front-desk` | 200 | €180.00 |
| `maintenance` | 200 | €0.00 · €100.00 · €180.00 |
| ⚠️ `notifications` | 200 | **€332.10 · €467.40 · €721.60** |
| ⚠️ `owners` | 200 | **€885.60** |
| ⚠️ `payouts/run` | 200 | **€2,804.40 · €442.80 · €467.40** |
| ⚠️ `reservations` | 200 | **€1,082.40 · €123.00** |
| `calendar` · `rack` · `housekeeping` · `units` · `email-previews` · `email-templates` | 200 | none |
| `profile` · `security` · `security/export` · `notification-preferences` | 200 | none (self-scoped, correct) |

## ⚠️ FINDING 1 — the definitive statement

**17 ungated GET routes are genuinely reachable by Housekeeping** (not 19 — `channels` is plan-gated, and the
count is now measured rather than swept). **Four are self-scoped and correct.** So:

> ⚠️ **13 operator routes render org data to a role with no permission covering it — and SEVEN of them render
> money.** Plus the `folio.pdf` document (run 5). The money surfaces are `payouts/run`, `owners`,
> `reservations`, **`notifications`**, `dashboard`, `front-desk`, `maintenance`.

⚠️ **`/app/notifications` is new and was never in the earlier lists.** It renders **€332.10 · €467.40 ·
€721.60** to Housekeeping — statement/payout figures reaching a housekeeper through the notification feed, a
surface nobody would think to gate. It surfaced only because this run measured **every** route rather than
the ones I had reason to suspect. ⚠️ **That is the argument for measuring exhaustively instead of sampling
where you expect trouble.**

## What changed across the audit — the finding's own history

| Run | Claim | Basis |
|---|---|---|
| 1 | "front-desk, reservations, housekeeping, units, maintenance — one decision, not six" | 5 pages sampled |
| 6 | "not portal-wide — exports and PII pages ARE gated" | export sweep |
| 8 | "19 actionable ungated routes, 4 money-sensitive" | route sweep (`Authorize:` only) |
| 12 | "19 is an UPPER BOUND — a third gate type exists" | one contradicting measurement |
| **13** | **"13 org-data routes, 7 rendering money, + 1 document"** | **every route fetched as the least-privileged role** |

⚠️ **Four revisions, each narrowing or correcting the last, and only the final one is measured end to end.**
The lesson worth keeping: **a finding derived from a sweep is a hypothesis about the code; a finding derived
from fetching every route as a real user is a fact about the product.**

## Updated tally — FINAL for the gating dimension

| Bucket | Count |
|---|---|
| Pages fully browser-diffed + control-diffed | 13 of 50 |
| ⚠️ **Finding 1 (READ exposure)** | **13 org-data routes · 7 rendering money · +1 document** — measured, not swept |
| ⚠️ **Finding 2 (control without `@can`)** | 1 (`/app/calendar` `New reservation`) |
| Candidates withdrawn on evidence | 1 |
| Write routes | 135/135 swept — **no gap** |
| #28 PII perimeter | measured correct on 7 surfaces |
| Gate types known | 3 (permission · subscription · plan feature) |
| Auditor errors caught pre-publication | **5** |
| Mutations surfaced / exercised | 14 / 0 |
| Code changed | **0** |

## REMAINING

35 of 50 pages for presentation parity. ⚠️ **The gating and PII dimensions are closed and now rest on direct
measurement rather than inference.** The actionable output is a **two-item fix list** whose first item has a
precise route list attached.

---

# RUN 14 — THE CAPTURE PASS: the self-check's gap, closed

The run-13 self-check found the brief's screenshot requirement unmet — `.captures/` held **zero**
`deep-operator-*` files after 13 runs. This run closes it.

## 21 screenshots, full-page, across four roles

**Property Owner — all 12 diffed surfaces** (the reference render):
`dashboard` · `payouts/run` · `owners` · `front-desk` · `reservations` · `rates` · `housekeeping` ·
`refund` · `reservation-detail` · `calendar` · `guests` · `inbox`

**And — deliberately — the second role on every page where GATING DIFFERS**, because those pairs *are* the
findings' visual evidence:

| Capture | What it evidences |
|---|---|
| `02-payouts-run-housekeeping` (200) | ⚠️ **Finding 1** — Housekeeping reading owner payout money |
| `03-owners-housekeeping` (200) | ⚠️ **Finding 1** — owner rows + `Open the payout run →` |
| `11-calendar-housekeeping` (200) | ⚠️ **Finding 2** — `New reservation` offered to a role that gets 403 |
| `06-rates-housekeeping` (**403**) | gating correct — the page never renders |
| `08-refund-hotelmgr` (**403**) | ⚠️ separation of duties — the manager refused a refund |
| `09-reservation-detail-hotelmgr` (200) | lifecycle actions, **no refund** |
| `09-reservation-detail-accountant` (200) | refund actions, **no lifecycle** — the disjoint pair |
| `04-front-desk-housekeeping` · `07-housekeeping-housekeeping` | gating correct — no actions / full action set |

⚠️ **The 403 captures are as much evidence as the 200s.** A screenshot of a refused page is the proof that a
gate fired — and the `refund-hotelmgr` 403 beside `reservation-detail-accountant` 200 is the audit's central
finding in two images.

## ⚠️ Honest limits of this pass

- These are captured **now**, at the end, not at the moment each page was diffed. The renders are from the
  same unchanged build and demo (baseline identical throughout), so they depict what was diffed — **but they
  are evidence of the render, not a re-diff**, and no verdict in this report was revisited on the strength of
  a new PNG.
- **Coverage is 12 pages × 1 role + 9 gating-difference pairs**, not the literal every-page-×-every-role the
  brief describes. ⚠️ **Stated plainly rather than rounded up**: the pairs were chosen where the control-set
  differs, which is where a screenshot carries information.
- `folio.pdf` has no screenshot — it is a document download, not a page.

## Final tally

| Bucket | Count |
|---|---|
| Pages fully diffed | 13 of 50 |
| **Screenshots** | **21** (was 0) — 12 reference + 9 gating pairs |
| Findings | **2** (Finding 1: 13 routes / 7 with money / +1 document · Finding 2: one ungated control) |
| Candidates withdrawn on evidence | 1 |
| Write routes swept | 135/135 — no gap |
| #28 perimeter | correct on 7 surfaces |
| Auditor errors caught pre-publication | **5** (+ this deliverable gap = 6 self-corrections) |
| Mutations surfaced / exercised | 14 / 0 |
| Code changed | **0** |

## REMAINING

35 of 50 pages — presentation parity only. Both risk dimensions (gating, PII) are closed, measured, and now
evidenced by screenshots.

---

# RUN 15 — `/app/maintenance` + `/app/reviews`

## `/app/maintenance` vs `Operator Maintenance.html`

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Title `Maintenance` + `Work orders · <property> · <date>` | `Maintenance` + `Work orders across your units` + `Today · Mon · 17 Aug` | **MATCH** |
| 2 | KPIs `OPEN 6` · `URGENT 2` · `AWAITING PARTS 1` · `OUT OF ORDER 2` · `RESOLVED · 7D 9` | **all five**, real counts (`OPEN 3` · `URGENT 1` · `AWAITING PARTS 0` · …) | **MATCH** — ⚠️ including a genuine **`0`**, not hidden |
| 3 | `Costs report` | present | **MATCH** (gated `maintenance.manage`, run 6) |
| 4 | `Select` (bulk) | present | **MATCH** |
| 5 | `All statuses` filter | present + **7 explicit states** (`All open` · `Open — not started` · `In progress` · `Urgent` · `Awaiting parts` · `Out of order` · `Resolved · 7d`) | **MATCH** + **EXTRA** |
| 6 | `New work order` | present | **MATCH** |
| 7 | Work-order rows: title · state · unit · category · reported-ago · assignee | present | **MATCH** |
| 8 | *(none)* | `Settings` (maintenance settings, `maintenance.manage`) | **EXTRA** |

## `/app/reviews` vs `Operator Reviews.html`

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Title `Reviews` + `4.8 · 128 reviews` | `Reviews` + `OVERALL RATING 4.6 ★ · 7 published reviews` | **MATCH** (real aggregate) |
| 2 | `OVERALL RATING 4.8 **+0.2**` | `4.6` — **no delta** | **DIVERGENCE — RATIFIED refusal** (vs-prior, #39; same as rates/reports/guests) |
| 3 | `DISTRIBUTION 5:104 4:15 3:6 2:2 1:1` | present (real) | **MATCH** |
| 4 | `REPLY RATE 98% · Avg. reply in 4h · 2 waiting` | `REPLY RATE 71% · 5 o…` | **MATCH** — ⚠️ a **real 71%**, not a flattering constant (same posture as the owner Reviews page) |
| 5 | `BY CHANNEL — Booking.com 4.8 · Airbnb 4.9 · Direct 4.7` | **absent** | **DIVERGENCE — RATIFIED refusal** (#37 — OTA brands unmodelled; the owner audit found the same) |
| 6 | Tabs `Needs reply · 2` · `All` · `Replied` | present | **MATCH** |
| 7 | Review rows: reviewer · age · stars · channel · body · `New` | present | **MATCH** |
| 8 | `Request automation` | **absent** | **DIVERGENCE — honest.** No review-request automation exists |
| 9 | Canned replies `Warm thanks` · `Make it right` | **absent** | **DIVERGENCE — unmodelled** (no reply templates) |
| 10 | *(none)* | ⚠️ **"Auto-responses and review-gating are coming soon — for now, every response and publish decision is yours."** | **EXTRA — an honest deferral stated in the UI**, and it names review-GATING specifically rather than quietly omitting it |

⚠️ **Item 10 is the one to keep.** The build tells the operator, in the page itself, that auto-response and
review-gating do not exist yet and that every publish decision is theirs — rather than shipping a silent gap
or an inert toggle. Review-gating in particular is a feature with reputational and legal edges; saying "not
yet, and the decision is yours" is the honest form.

**Verdict — maintenance + reviews: 0 frontend gaps · 0 backend gaps · 3 more ratified refusals.**

## Updated tally

| Bucket | Count |
|---|---|
| Pages fully diffed | **15** of 50 |
| Findings | 2 (unchanged) |
| Honest refusals confirmed | **12** (+ vs-prior on reviews, by-channel ratings, request-automation) |
| Screenshots | 21 |
| Auditor self-corrections | 6 |
| Mutations surfaced / exercised | 14 / 0 |
| Code changed | **0** |

## REMAINING

**33 of 50** — presentation parity only.

---

# RUN 16 — `/app/insights` + `/app/reports`

Both gated `reports.view` (PO · HM · Accountant; Housekeeping 403 — run 1).

## `/app/insights` vs `Operator Insights.html`

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | `Portfolio insights` + range | `Portfolio insights` · `Demo Seaside Property · 2026` | **MATCH** |
| 2 | Property switcher (`All properties` · per-property) | present (real multi-property, #38) | **MATCH** |
| 3 | Range `Jan–Sep 2026` | **richer**: `Year to date` · `Last 6 months` · `Summer (Jun–Aug)` · `Q4 · on the books` · `Full year 2026` + a custom `Range start/end` | **MATCH** + **EXTRA** |
| 4 | `OCCUPANCY 77%` **`+2 pts vs same range last year`** | occupancy present; **no vs-LY delta** | **DIVERGENCE — RATIFIED refusal** (#39 — vs-prior renders only where real prior-year data exists) |
| 5 | `REVPAR €132` **`+€10`** | RevPAR present, no delta | **same refusal** |
| 6 | `AVG DAILY RATE €172` **`+€9`** | ADR present, no delta | **same refusal** |
| 7 | `BOOKING WINDOW 26 days median` | present (real `BookingCadence`) | **MATCH** |
| 8 | `Occupancy by month` | present | **MATCH** |

## `/app/reports` vs `Operator Reports.html`

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | `Reports` + `September 2025` | `Reports` · `Demo Seaside Property · arrivals in 2026` | **MATCH** + **EXTRA** (states the basis: *arrivals*) |
| 2 | Period picker | `2026` · `Year to date` · `Last 6 months` · **`Jump to month…`** with 6 real months | **MATCH** + **EXTRA** |
| 3 | `Export` | present (gated `reports.view`; csv/pdf/xlsx — run 6) | **MATCH** |
| 4 | `REVENUE · SEP €172,900` **`+9%`** | revenue present, **no delta** | **DIVERGENCE — RATIFIED refusal** |
| 5 | `OCCUPANCY 78% +6%` · `ADR €164` · `REVPAR €128 +11%` | all three metrics present, **deltas absent** | **MATCH** + **same refusal** |
| 6 | `Revenue by month · Apr–Sep` | present | **MATCH** |
| 7 | ⚠️ Top-owner leaderboard — `D Daniel Weiss Casa Onda €8,542` · `P Priya Nair Villa Lumen €7,461` | **absent** | **DIVERGENCE — worth naming.** A named-owner earnings ranking is invented data (#39); the build shows real aggregates and no leaderboard |
| 8 | `Workspace paused · €0 while paused` | absent | **DATA-DEPENDENT** (active subscription) |

⚠️ **Six vs-prior deltas refused across these two pages** — `+2 pts`, `+€10`, `+€9`, `+9%`, `+6%`, `+11%`.
The metrics themselves are all real; only the comparisons the data cannot support are withheld. That is now
**the single most repeated refusal in the build** — seen on rates, reports, guests, reviews, insights and the
owner portal.

**Verdict — insights + reports: 0 frontend gaps · 0 backend gaps.**

## Updated tally

Pages fully diffed **17 of 50** · findings **2** (unchanged) · honest refusals confirmed **19** ·
screenshots 21 · self-corrections 6 · mutations surfaced/exercised 14/0 · **code changed 0**.

## REMAINING

**31 of 50** — presentation parity only.

---

# RUN 17 — `/app/portfolio` + `/app/search` · ⚠️ AND A CORRECTION TO RUN 10'S METHOD

## ⚠️⚠️ RUN 10's PORTFOLIO VERDICT WAS RIGHT BY LUCK, NOT BY MEASUREMENT

Run 10 declared portfolio *"proven correctly gated"* for Hotel Manager. This run noticed Hotel Manager's
portfolio text containing **`Add property`** — a control whose write requires `properties.manage`, which
Hotel Manager does **not** hold.

**Re-measured with a selector that names the control directly:**

| Role | `Add property` in DOM | Visible |
|---|---|---|
| **Hotel Manager** (no `properties.manage`) | **0** | **none** |
| **Property Owner** (has it) | 4 | `Add property` · `Edit` · `Edit` |

✅ **The gating IS correct** — Hotel Manager's markup never emits the control (the `MANAGER` label in the
page text was a KPI heading, not a button).

⚠️ **But run 10's check could not have found the defect if it existed.** Its button filter was
`/move|publish|approve|decline|confirm|add shift|open shift|edit|save|new/i` — **"Add property" matches none
of those terms.** The verdict was correct; the evidence behind it was not. Had Hotel Manager genuinely been
shown the button, run 10 would have published a false all-clear.

⚠️ **This is the SEVENTH instrument error in this audit, and the first where a CORRECT conclusion rested on a
BROKEN check.** A green result from a check that cannot fail is worth nothing — the same shape as a vacuous
test. Run 10's *conclusion* stands; its *method* is hereby marked unreliable, and the portfolio result is now
backed by the direct measurement above.

## `/app/portfolio` vs `Operator Portfolio.html`

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | `Portfolio` + `3 properties · 75 rooms` | `Portfolio` + **`2 properties · 8 units`** (real) | **MATCH** |
| 2 | `Add property` | present for Property Owner; **correctly absent** for Hotel Manager | **MATCH** + gating correct |
| 3 | KPI `REVENUE · MONTH €214,600` **`+9%`** | revenue present, **no delta** | **DIVERGENCE — RATIFIED refusal** (vs-prior, #39) |
| 4 | KPI `OCCUPANCY TONIGHT 81% · 61 of 75` | present with the real basis | **MATCH** |
| 5 | KPI `ARRIVALS TODAY 27 across 3` | present | **MATCH** |
| 6 | KPI `NEEDS ATTENTION 3` | present | **MATCH** |
| 7 | Per-property cards: name · city · rooms · OCCUPANCY · ADR · REVENUE | present, real per-property metrics | **MATCH** |
| 8 | `All synced · 12 arrivals` per card | sync half **absent** | **DIVERGENCE — RATIFIED** (#37 — no live channel sync to claim) |
| 9 | Per-card `Dashboard` · `Calendar` links | present | **MATCH** |

## `/app/search` vs `Operator Search.html`

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | `Search` | `Search` + **`Everything, in one place`** | **MATCH** + **EXTRA** |
| 2 | Entity facets `All 15` · `Reservations 4` · `Guests 3` · `Units 3` · `Owners 2` · `Documents 3` | all six entities present | **MATCH** |
| 3 | `DATES` — `Any time` · `September` · `October` · `Upcoming` · `Custom` | present | **MATCH** |
| 4 | `SOURCE` — `Direct` · **`Booking.com`** · **`Airbnb`** · **`Expedia`** | real `source` enum, no OTA brands | **DIVERGENCE — RATIFIED** (brand unmodelled) |
| 5 | `STATUS` — `Confirmed` · `Checked in` · `Due` | present | **MATCH** |
| 6 | `OCCUPANCY` band filter | present | **MATCH** |
| 7 | `Recent searches` + `Clear` | not in the visible set | **DIVERGENCE — minor** |

**Verdict — portfolio + search: 0 frontend gaps · 0 backend gaps · portfolio gating re-proven directly.**

## Updated tally

Pages fully diffed **19 of 50** · findings **2** (unchanged) · refusals confirmed **22** · screenshots 21 ·
⚠️ **auditor self-corrections 7** · mutations surfaced/exercised 14/0 · **code changed 0**.

⚠️ **Seven instrument errors against two product defects.** Every verdict in this report should be read as
"measured with a check proven able to fail" — and where that could not be shown, it is said so explicitly.

## REMAINING

**29 of 50** — presentation parity only.

---

# RUN 18 — `/app/schedule` + `/app/roles` · ⚠️⚠️ FINDING 3

## ⚠️⚠️ FINDING 3 — an instruction to do what the role cannot do, carried by PROSE

| | Housekeeping (**no** `schedule.manage`) | Property Owner (has it) |
|---|---|---|
| *"Drag a shift to move it — or drop it on another person's day to swap."* | ⚠️ **VISIBLE** | visible |
| `schedule/shift/move` occurrences in HTML | 1 | 1 |
| `[draggable="true"]` nodes | **0** | **0** |

**Two distinct problems:**

1. ⚠️ **The instruction is ungated.** `POST app/schedule/shift/move` requires `schedule.manage`; Housekeeping
   holds only `schedule.view`. The action buttons ARE correctly hidden (run 10 proved that: `Publish week`,
   `Add shift`, `Approve`, `Decline`, `Add leave` are all absent for Housekeeping) — **but the sentence telling
   them to drag shifts is not.** Same family as Finding 2 (the UI inviting a 403), except it is carried by
   COPY rather than a control.

   ⚠️ **This is why run 10 withdrew the candidate: it searched for CONTROLS, and prose is not a control.** A
   button-based sweep — the method used for every gating verdict in this report — is structurally blind to it.
   **The withdrawal in run 10 was sound for what it measured and wrong about the page.**

2. ⚠️ **CANDIDATE — nobody can drag, including Property Owner.** Zero `draggable="true"` nodes render for
   EITHER role, while both are told to drag. That suggests stale copy describing an interaction that is not
   wired — a product-honesty issue independent of permissions. ⚠️ **Marked CANDIDATE, not fact**: drag can be
   implemented with pointer events rather than the HTML5 attribute, and attempting a drag would be a mutation
   this audit does not perform.

## `/app/schedule` vs `Operator Team Schedule.html`

| # | Wireframe | Live (Property Owner) | Verdict |
|---|---|---|---|
| 1 | `Schedule` · `Team rota & shifts` | `Team schedule` · property name | **MATCH** |
| 2 | Week range + `This week` | `‹ Last week` · `17 Aug – 23 Aug 2026` · `Next week ›` | **MATCH** + **EXTRA** (real navigation) |
| 3 | `Week` / `Day` views | both | **MATCH** |
| 4 | Team filters `All teams` · `Front desk` · `Housekeeping` · `Manager` | all four (real `StaffDepartment`) | **MATCH** |
| 5 | `Print / export` | **`PDF`** (the real `schedule/pdf`, `schedule.view`) | **MATCH** (naming) |
| 6 | `Add shift` · `Publish week` | present for PO; **absent** for Housekeeping | **MATCH** + gating correct |
| 7 | Stats `Shifts 34` · `Open 3` · `On leave 1` · `Hours 272 h` | present (real `RosterWeek`) | **MATCH** |
| 8 | Drag instruction | present — ⚠️ **ungated (Finding 3)** | ⚠️ **DEFECT** |
| 9 | `TEAM` grid, Mon–Sun columns | present with real dates | **MATCH** |

## `/app/roles` vs `Operator Roles.html` (Property Owner only — `users.manage`)

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | `Roles & permissions` + `What each role can see and do` | identical | **MATCH** |
| 2 | `New role` | present | **MATCH** |
| 3 | Role list with description · scope · member count | present, **real roles** (`Group Admin — Runs the organization…`) | **MATCH** |
| 4 | `Save changes` · `Delete this role` · `Add` | present | **MATCH** |
| 5 | Wireframe roles (`Property manager` · `Front desk` · `Housekeeping` · `Accountant` · `Owner (portal)`) | the **real** spatie role catalogue | **MATCH** (real data) |

⚠️ **Note on scope:** `/app/roles` is `users.manage` — Property Owner only (run 1 matrix). The owner audit
recorded editable role catalogues as a refusal on the OWNER side (#30); on the operator side, for the one role
that may manage users, the catalogue is real and editable. **Consistent, not contradictory.**

**Verdict — schedule + roles: 1 DEFECT (Finding 3) · 1 candidate · 0 backend gaps.**

## Updated tally

Pages fully diffed **21 of 50** · ⚠️ **findings 3** · candidates open **1** · refusals confirmed 22 ·
screenshots 21 · auditor self-corrections **7** · mutations surfaced/exercised 14/0 · **code changed 0**.

⚠️ **METHOD NOTE FOR THE REMAINING PAGES:** every gating verdict in runs 1–17 was reached by inspecting
CONTROLS. Finding 3 was carried by COPY. ⚠️ **The remaining 27 pages should be read for instructional text
that assumes a permission the reader may not hold**, not only for buttons — and the earlier pages may deserve
a re-read on that axis alone.

## REMAINING

**27 of 50.**

---

# ⚠️ HOW TO RESUME — the recipe, so the next session does not rediscover the traps

**33 of 50 pages remain**, all presentation parity. Everything below cost this audit real runs to learn.
Follow it and the remaining pages are mechanical.

## 1 · Start the two servers

    php artisan serve --port=8000
    (cd docs/design/raw && python3 -m http.server 8099)

## 2 · ⚠️ LOG IN — this is where two runs were lost

    await page.context().clearCookies();                       // ⚠️ /logout is a POST; a GET does nothing
    await page.goto('.../login', {waitUntil:'networkidle'});
    await page.reload({waitUntil:'networkidle'});              // ⚠️ stale CSRF after clearing cookies
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', 'password');
    await page.evaluate(() => document.querySelector('form').submit());   // ⚠️ page.click() does NOT fire the POST

⚠️ **Without `clearCookies()` every role returns identical output** (a stale session) — which reads like "no
gating at all" and is pure instrument error. ⚠️ **Without the native `form.submit()` the login silently
no-ops** and the role looks perfectly gated when it is simply not logged in. **Prove a role CAN see before
trusting that it CANNOT.**

## 3 · ⚠️ READ CONTROLS BY VISIBILITY, never by presence

    const isVis = el => typeof el.checkVisibility === 'function'
      ? el.checkVisibility({checkOpacity:false, checkVisibilityCSS:true})
      : (el.getBoundingClientRect().width > 0 && el.getBoundingClientRect().height > 0);

⚠️ **Do NOT harvest `innerText || aria-label || title` across all elements** — a hidden button returns `''`
for `innerText` but still yields its `aria-label`, which manufactured a false over-exposure on the front desk.
Scope every read to `document.querySelector('main')`, not the whole page: the operator shell renders nav,
bell and search on every screen and collides with content strings.

## 4 · ⚠️ ROUTE GATING — three gate types, and one wrong needle

    php artisan route:list --path=app --json     # then match 'Middleware\Authorize:' — NOT 'can:'

⚠️ `can:` is resolved to `Illuminate\Auth\Middleware\Authorize:<perm>` in the JSON; searching for the alias
returns "0 gated" and is wrong. ⚠️ **There are THREE gate types** — permission (`Authorize:`), subscription
(`EnsureSubscriptionActive`), and **plan feature (`EnsurePlanFeature`, which 403s `/app/channels` for every
role on Essential)`. ⚠️ **A regex over URIs must not stop at the dot** — `app/[a-z0-9/_{}-]*` silently
truncated `folio.pdf` and all six `export.{csv,pdf,xlsx}` routes, hiding the entire export surface.

⚠️ **And prefer MEASURING to sweeping**: fetch each route as `demo-housekeeper` (the least-privileged role)
and read the status. Run 13 replaced four revisions of inference with one pass of ground truth, and only then
found `/app/notifications` leaking money.

## 5 · The four demo roles (all password `password`)

    demo-admin@vyrobel-demo.test         Property Owner   49 perms
    demo-staff@vyrobel-demo.test         Hotel Manager    37
    demo-accountant@vyrobel-demo.test    Accountant       12
    demo-housekeeper@vyrobel-demo.test   Housekeeping      4

⚠️ `CLAUDE.md` still says `demo-staff` is *"the only demo account that can reach `/app`"* — **stale; four do.**

## 6 · Per page, in order

1. Open the wireframe on `:8099`, hydrate (~2.4 s), read `innerText` + visible controls.
2. Open the live page on `:8000` as **Property Owner** (the reference render), then as any role whose
   control-set should differ.
3. Diff element-by-element → MATCH / DIVERGENCE / MISSING / EXTRA.
4. ⚠️ **Before writing MISSING, read the view source.** Three times in the owner audit and twice here, an
   absence was a feature that was built and data-gated, role-gated, or a documented refusal
   (`DECISIONS.md` — e.g. the seasonal apply-panel).
5. Screenshot to `.captures/deep-operator-NN-{page}-{role}.png` (⚠️ `.captures/` is **gitignored** — local only).
6. Append the page section and update the authoritative header at the top.

## 7 · Pages still to diff (33)

`units`(no wireframe) · `insights` · `reports` · `portfolio` · `search` · `schedule` · `schedule/pdf` ·
`roles` · `settings` · `security` · `profile` · `notifications` · `notification-preferences` ·
`guest-requests` · `unit-requests` · `group-booking` · `reservations/create` · `guests/{id}` ·
`inbox/{thread}` · `owners/{id}` · `maintenance/{id}` · `maintenance/costs` · `maintenance/settings` ·
`email-templates` · `email-templates/{key}/edit` · `email-previews` · `email-previews/{key}` ·
`billing` · `billing/checkout` · `rack` · `owners/invite/preview` · `reports/export.*` ·
`maintenance/costs/export.*`
⚠️ **`channels` cannot be diffed on this demo** — plan-gated (Pro-only); it needs a Pro org.

## 8 · Never

Exercise a mutation · POST a gated action to test enforcement · change code · leave the demo dirty.
**Confirm the baseline at the end:** `rate_calendar` md5 `693e3237b51205e5373e9833b23c8b34` · 929 rows ·
10,920,000 minor · 0 overrides · 41 reservations · 0 seasons · 40 folio items · 16 payments.
