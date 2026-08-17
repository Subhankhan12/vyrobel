# Operator-portal DEEP parity audit — 2026-08-18 (RESUMABLE · READ-ONLY)

> ## ⚠️ READ THIS FIRST — THE AUTHORITATIVE STATE (everything below is a dated running log)
>
> This report was written across 13 runs and **several figures in the middle sections are SUPERSEDED**. A
> reader landing mid-document will otherwise take a stale number as current. The authoritative values are:
>
> | | Authoritative |
> |---|---|
> | **Pages fully diffed** | ✅ **52 of 52 — COMPLETE** (denominator DERIVED in run 29: 54 GET `/app` URIs − 2 aliases) |
> | **REMAINING** | ✅ **0** |
> | **Finding 1 (READ exposure)** | ⚠️ **RUN 13 IS DEFINITIVE: 13 org-data routes reachable by Housekeeping, 7 rendering money, +1 document.** Earlier statements — "one decision not six" (run 1), "19 actionable" (run 8), "25 ungated" (run 8) — are **SUPERSEDED**; they were swept, run 13 is measured. |
> | **Finding 2** | one control without `@can` (`/app/calendar` → `New reservation`) |
> | ⚠️ **Finding 3 (runs 18–19)** | **CONFIRMED AT SOURCE** — `schedule.blade.php:179` gates *"Click a cell to add or change a shift."* inside `@if ($canManage)` but leaves *"Drag a shift to move it…"* OUTSIDE it, in the same span. Housekeeping is told to drag; the move route 403s. Bounded by sweep to **one line in one file**. Open candidate: zero `draggable="true"` nodes for ANY role. |
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

# RUN 19 — THE INSTRUCTION SWEEP · ⚠️ FINDING 3 CONFIRMED AT SOURCE, AND SHARPER

Finding 3's method note said the remaining pages should be read for *instructional prose assuming a
permission*, not only for controls. This run does that as a sweep across every operator view at once.

## The sweep

Imperative instructions in **shipped copy** (Blade comments stripped, so a doc-comment can't be mistaken for
UI text): **7 total, across 3 files.** Only `schedule.blade.php` carries any that are ungated.

## ⚠️⚠️ THE SOURCE — the author gated ONE CLAUSE AND NOT THE OTHER, in the same span

`resources/views/livewire/operator/schedule.blade.php:179`

    <span …>Drag a shift to move it — or drop it on another person's day to swap.
            @if ($canManage)Click a cell to add or change a shift.@endif</span>
    @if ($canManage && ! $readOnly)
        <button …>+ Add shift</button>
    @endif

- **"Click a cell to add or change a shift."** → **inside** `@if ($canManage)` ✅
- **"Drag a shift to move it — or drop it on another person's day to swap."** → ⚠️ **outside it**
- **`+ Add shift`** → gated twice (`$canManage && ! $readOnly`) ✅

⚠️ **This is far stronger evidence than a wholesale miss.** Someone reasoned about permissions **on that exact
line** and covered one clause of one sentence pair. A partial gate is the hardest kind to spot — and it is
precisely why every control-based sweep in runs 1–17 returned clean on this page.

**Finding 3 is therefore CONFIRMED, not candidate**: Housekeeping (holding `schedule.view`, not
`schedule.manage`) is told to drag shifts to move them, while the move route 403s.

## What the sweep also shows — the rest is clean

- `rates-grid.blade.php` — *"Click a cell to prefill · applies to a whole date range"* — the file uses `@can`,
  and the rates page 403s wholesale for roles without `rates.view` (run 3), so no unauthorised reader reaches it.
- `email-templates.blade.php` — the only other hit is *"click tracking isn't collected, so no engagement stats
  are shown"* — ⚠️ **not an instruction at all but an HONEST REFUSAL** (no analytics claimed). A naive
  keyword sweep would have flagged it; reading it does not.
- **No other operator view carries an ungated imperative.** ⚠️ **So Finding 3 is ONE line in ONE file, not a
  pattern** — the sweep bounded it rather than expanding it.

## Updated tally

Pages fully diffed 21 of 50 · **findings 3 (all confirmed at source)** · candidates open **1** (the
unwired-drag question) · refusals confirmed **23** (+ email click-tracking) · auditor self-corrections 7 ·
mutations surfaced/exercised 14/0 · **code changed 0**.

⚠️ **The three findings, final form — each a single, precise omission:**
1. **13 ungated GET routes** (7 rendering money, + the folio PDF) — measured per-role.
2. **`/app/calendar`** renders `New reservation` without `@can` — the same control IS wrapped on `/app/reservations`.
3. **`schedule.blade.php:179`** — one ungated clause beside a gated one.

**All three are omissions in a codebase that gates correctly everywhere else** — 118/135 write routes, every
export, both guest-PII pages, and every action control across eight pages.

---

# RUN 20 — `/app/settings` + `/app/security`

## ⚠️⚠️ THE SAME FABRICATED SECURITY INCIDENT, REFUSED AGAIN — cross-realm

`Operator Security.html` carries:

> **"Suspicious login blocked** — A failed sign-in from an unrecognised device in **Porto, Portugal on
> 28 Sep, 03:41.** If this wasn't you…"

⚠️ **The live page does not render it.** This is the *identical* fabrication the OWNER audit refused
(owner report, page 17 item 9) — a named city, a date, a time, for a security incident the system records no
data to support. **Both realms refuse it independently.**

⚠️ **That cross-realm consistency is the strongest evidence yet that the refusals are a POLICY, not an
accident of what happened to get built.** A fabrication surviving in one realm while being refused in the
other would suggest luck; refused in both suggests intent. Inventing a break-in is the most harmful class of
fabrication a product can ship — the user's rational response is to panic and change credentials.

Also refused on the live page: `4 Connected apps` · `2 Trusted devices` · `2 IP rules` as *decorative
counts* — the live page shows the **real** figures, which for Housekeeping are `CONNECTED APPS 0` ·
`TRUSTED DEVICES 0` · `IP RULES 0`. ⚠️ **Three honest zeroes where the prototype shows populated numbers.**

## `/app/security` — per-role, and correctly SELF-SCOPED

| Role | Renders |
|---|---|
| Property Owner | `Two-factor off` · `ACTIVE SESSIONS 21` · connected apps · trusted devices · IP rules |
| Housekeeping | `Two-factor off` · **`ACTIVE SESSIONS 23`** · `CONNECTED APPS 0` · `TRUSTED DEVICES 0` · `IP RULES 0` · `Update password` · `Enable two-factor` |

⚠️ **The session counts DIFFER between roles (21 vs 23)** — which is the proof the page is per-user, not
org-wide. Both roles get the same self-service controls (`Update password`, `Enable two-factor`), which is
correct: account security is everyone's own. **A 200 for all four roles here is right**, the same shape as
`security/export` in run 6.

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | `Account security` + `Protected` badge | `Account security` + real 2FA state | **MATCH** |
| 2 | `3 Active sessions` · `4 Connected apps` · `2 Trusted devices` · `2 IP rules` | all four, **real per-user counts** incl. genuine zeroes | **MATCH** |
| 3 | ⚠️ `Suspicious login blocked` + city/date/time | **ABSENT** | ⚠️ **DIVERGENCE — RATIFIED refusal** (no sign-in history recorded; fabricating a break-in is the worst class) |
| 4 | `Secure my account` CTA | absent (follows #3) | **DIVERGENCE — same refusal** |
| 5 | *(none)* | `Update password` · `Enable two-factor` with rules stated | **EXTRA** |

## `/app/settings` vs `Operator Property Settings.html` (Property Owner only — `properties.manage`)

Wireframe **993 chars** · live **12,095** — ⚠️ **twelve times the content.**

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | `Property settings` + property name | identical | **MATCH** |
| 2 | Tabs `General` · `Policies` · `Team access` · `Tax & fees` · `Branding` | a single long-form page (`Property basics`, `DISPLAY NAME`, check-in/out, …) | **DIVERGENCE — layout** (one page vs five tabs) |
| 3 | `Taxes` — `City tax` per guest/night · `VAT` reduced rate | present (the real bounded Tax & fees editor, `properties.manage`) | **MATCH** |
| 4 | `Fees` — `Cleaning fee` departure | present | **MATCH** |

⚠️ **The live settings page is far RICHER than its wireframe**, not poorer — the prototype shows one tab's
worth of content and the build ships the whole surface. Recorded as EXTRA, never a defect.

**Verdict — settings + security: 0 frontend gaps · 0 backend gaps · 2 more refusals, one cross-realm.**

## Updated tally

Pages fully diffed **23 of 50** · findings 3 · candidates 1 · refusals confirmed **26** ·
auditor self-corrections 7 · mutations surfaced/exercised 14/0 · **code changed 0**.

## REMAINING

**25 of 50.**

---

# RUN 21 — `/app/profile` + `/app/notifications` · ⚠️ A HYPOTHESIS I KILLED, AND WHAT IT LEFT

## ⚠️⚠️ "THE OPERATOR FEED IS ORG-WIDE" — TESTED AND FALSE

Housekeeping's notifications page shows **`59 unread`** — the **same number** as Property Owner — and carries
a **`Finance`** category filter. That reads like a shared, org-wide feed leaking finance notifications to a
housekeeper.

**It is not.** One query settled it:

    demo-admin        owns  59 rows,  59 unread
    demo-housekeeper  owns  59 rows,  59 unread

⚠️ **Each user genuinely owns 59 notification rows of their own.** The counts match because the DEMO SEEDER
dispatches the same notifications to every back-office user — not because one feed is shared. The feed is
correctly **self-scoped** (`notifiable_id`), the same posture as the owner realm.

⚠️ **I was one write-up away from filing "the operator notification feed is org-wide" off two equal numbers.**
That would have been the eighth instrument error in this audit. Equal figures are a hypothesis, not a
mechanism — and the mechanism was one `notifiable_id` count away.

## ⚠️ BUT IT SHARPENS FINDING 1 — the question is DISPATCH AUDIENCE, not scoping

Run 13 measured `/app/notifications` rendering **€332.10 · €467.40 · €721.60** to Housekeeping. Now the
mechanism is clear, and it is *not* a scoping bug:

> **Finance-category notifications are DISPATCHED to roles that hold no finance permission.** Housekeeping
> owns them legitimately; the question is why they were addressed to a housekeeper at all.

⚠️ **This is the same shape as the OWNER portal's Fix 2**, where `NotificationPreferences::CATALOG` uses an
`audience` field to decide which realm receives a type. The operator catalogue's audience is `operator` —
**one bucket for eleven roles**. There is no per-permission narrowing, so a `statement_finalized` or
`owner_payout_sent` notification reaches Housekeeping exactly as it reaches the Property Owner.

⚠️ **RECORDED AS A REFINEMENT OF FINDING 1, not a new finding** — the exposure is the same money reaching the
same role; only the mechanism is now correctly named. **The remedy differs, though**: a `can:` on the route
would NOT fix this one, because the notification is genuinely the user's own. It needs the dispatch audience
narrowed (or the category suppressed per-permission).

## `/app/notifications` vs `Operator Notifications.html`

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | `Notifications` + `3 unread` | `Notifications` + `59 unread` (real) | **MATCH** |
| 2 | `Mark all read` | present | **MATCH** |
| 3 | Filters `All` · `Unread` · `Front desk` · `Finance` | `All` · `Unread` · `Finance` · `Front Desk` · **`Maintenance`** · **`Reputation`** | **MATCH** + **EXTRA** (real categories) |
| 4 | Rows: title · category · detail · age | present (`New booking · R-6QEW0DOC` · `Front Desk` · `Direct · 2 Sep – 4 Sep` · `14 hours ago`) | **MATCH** |
| 5 | `Expedia disconnected · Channels` row | **absent** | **DIVERGENCE — RATIFIED** (#37, no live channel to disconnect) |
| 6 | `Owner payout run is due · September · €10,370` | present in kind (real figures) | ⚠️ **MATCH — and this is the Finding-1 row** |
| 7 | *(none)* | per-row `✓` (read) · `×` (dismiss) + `Unread` chip | **EXTRA** |

## `/app/profile` vs `Operator Profile.html`

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | `Your profile` + avatar · name · role · property · `Member since` | present (`DA · Demo Admin · Property Own…`) | **MATCH** |
| 2 | `Personal details` — `FULL NAME` · `PREFERRED NAME` · `WORK EMAIL` · `PHONE` | present | **MATCH** |
| 3 | `LANGUAGE` — English · Português · Español · Français | present | **MATCH** |
| 4 | `Save changes` · `Cancel` | present | **MATCH** |
| 5 | `Sign out` | present | **MATCH** |
| 6 | `Active` status chip | present | **MATCH** |

Self-scoped (`/app/profile` is per-user, correctly ungated — run 13).

**Verdict — profile + notifications: 0 frontend gaps · 0 backend gaps · 1 hypothesis killed · Finding 1's
notifications element re-mechanised.**

## Updated tally

Pages fully diffed **25 of 50** (halfway) · findings 3 · candidates 1 · refusals confirmed **27** ·
⚠️ **hypotheses tested-and-killed 1** · auditor self-corrections 7 · mutations surfaced/exercised 14/0 ·
**code changed 0**.

## REMAINING

**23 of 50.**

---

# RUN 22 — `/app/guest-requests` + `/app/unit-requests` · the last two run-9 candidates, resolved

Run 9 flagged five views whose PAGE gate is weaker than their WRITE gate. Three were measured in run 10.
These are the last two — and they resolve in opposite ways, neither of them "a finding".

## ⚠️ `/app/guest-requests` — the candidate is KILLED by the permission matrix

| Role | `reservations.view` (page) | `reservations.manage` (write) |
|---|---|---|
| Property Owner | Y | **Y** |
| Hotel Manager | Y | **Y** |
| Accountant | n | n |
| Housekeeping | n | n |

⚠️ **No role holds the page permission WITHOUT the write permission.** The two roles that can open the page can
both act on it; the two that cannot act cannot open it (Housekeeping measured **403**). **The "weaker page
gate" is structurally unreachable here** — there is no role for whom the gap exists.

⚠️ **This could not have been settled by rendering alone.** The render shows no approve/decline controls for
anyone — which looks like gating but is actually the empty state (below). **The matrix is what closes it**, and
that is a different instrument from every other verdict in this report.

## ⚠️ `/app/unit-requests` — the candidate is UNTESTABLE on this demo, and is recorded as such

| Role | Page | Approve/Decline visible |
|---|---|---|
| Property Owner (`units.manage` ✓) | 200 | **none** |
| Housekeeping (`units.view` ✓, `units.manage` ✗) | 200 | **none** — honest empty state |

⚠️ **Housekeeping DOES have the gap on paper** — `units.view` without `units.manage` — so this is the one place
the shape is real. **But `unit_requests` has 0 rows**, so no request card renders for ANY role, and therefore
no approve/decline control can render regardless of gating.

⚠️ **Property Owner — who HAS `units.manage` — also sees no controls.** That is the positive control failing:
if the role that *should* see them doesn't, the absence for Housekeeping proves nothing about gating. **It is
data, not a gate.**

⚠️ **RECORDED AS UNTESTABLE-WITHOUT-DATA, not as pass or fail.** Settling it needs a pending unit request,
which means seeding — a mutation this audit does not perform. **The honest verdict is "unknown", and saying so
is the point.** (`unit_requests` is a REAL entity — the owner portal's Add-a-Unit flow writes it, and the
`unit_request_reviewed` notification type exists — so the emptiness is demo data, not an absent feature.)

## Element diffs

Both pages render their shells correctly for the roles that may see them; with zero pending rows on
`unit-requests` and the guest-requests list gated to PO/HM, **there is no populated state to diff against a
wireframe on this demo**. Recorded as **shell-verified, content-untestable**.

## Updated tally

Pages fully diffed **27 of 50** · findings 3 · candidates open **1** (the unwired-drag question) ·
⚠️ **candidates resolved 3** (1 withdrawn on evidence · 1 killed by the matrix · 1 recorded untestable) ·
refusals confirmed 27 · auditor self-corrections 7 · hypotheses killed 1 · mutations surfaced/exercised 14/0 ·
**code changed 0**.

⚠️ **All five of run 9's weaker-page-gate candidates are now closed**: three proven correctly gated (run 10,
re-proven for portfolio in run 17), one structurally unreachable (guest-requests), one untestable without
seeding (unit-requests). **None became a finding.**

## REMAINING

**21 of 50.**

---

# RUN 23 — `/app/email-templates` (+ previews) · the LAST unmeasured weaker-gate view

`/app/email-templates` and `/app/email-templates/{key}/edit` are **UNGATED GETs** (run 8) whose writes require
`properties.manage`. That makes this the strongest remaining case of the run-9 shape: **anyone in the back
office can open the page; only some may act.**

## ⚠️ MEASURED — correctly gated, with a working positive control

| Role | `Edit` control | Edit links |
|---|---|---|
| **Property Owner** (`properties.manage` ✓) | **`Edit`** | 3 real: `booking-confirmation` · `cancellation` · `pre-arrival` |
| **Housekeeping** (`properties.manage` ✗) | **none** | **none** |

⚠️ **The positive control fires** — Property Owner gets three genuine edit links — **so Housekeeping's zero is
gating, not an empty page.** This is the distinction run 22's `unit-requests` could NOT make (where even the
permitted role saw nothing, leaving the question unanswerable). Here the instrument is proven able to find
controls, and it finds none for the role that shouldn't have them.

⚠️ **A page anyone can open, where every write affordance is still correctly withheld** — which is the general
shape of Finding 1: the READ is open, the WRITE is not.

## ⚠️ AN EMPTY TABLE THAT IS NOT AN EMPTY FEATURE

`email_templates` has **0 rows**, yet three templates render with real keys. ⚠️ **The catalogue is
CODE-DEFINED with DB overrides**, not DB-driven — so the empty table means "nothing has been customised", not
"no templates exist". **Had this been judged by the row count alone it would have read as a dead feature.**
(The same trap as the owner audit's data-gated year pills, in a new costume.)

## Element diff vs `Operator Email Templates.html` / `Operator Email Editor.html`

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | Template list with name + status | present (3 real templates, page title `Guest emails`) | **MATCH** |
| 2 | Per-template `Edit` | present for `properties.manage`; **absent otherwise** | **MATCH** + gating correct |
| 3 | Editor: subject/body + `Save` · `Send test` · enable toggle | routes exist (`{key}` POST · `{key}/test` · `{key}/toggle`, all `properties.manage`) | **MATCH** — ⚠️ **editor content UNVERIFIED**: opening it for a role that may write is fine, but the page's write affordances were not exercised |
| 4 | Engagement / open-rate stats | ⚠️ **absent**, replaced by *"click tracking isn't collected, so no engagement stats are shown"* | **DIVERGENCE — RATIFIED refusal** (run 19 confirmed this is a refusal, not an instruction) |

⚠️ **MUTATIONS SURFACED, NOT EXERCISED:** `email-templates/{key}` (POST → template override) · `{key}/test`
(sends a real email) · `{key}/toggle` (enables/disables a guest-facing email). ⚠️ **`{key}/test` sends mail to
a real address — deliberately untouched.**

## Updated tally

Pages fully diffed **29 of 50** · findings 3 · candidates open 1 · candidates resolved **4** ·
refusals confirmed 28 · auditor self-corrections 7 · hypotheses killed 1 ·
mutations surfaced/exercised **17 / 0** · **code changed 0**.

## REMAINING

**19 of 50.**

---

# RUN 24 — `/app/billing` · ⚠️ THE STORED CARD, REFUSED AGAIN

## Gating

| Role | `/app/billing` |
|---|---|
| Property Owner (`billing.manage` ✓) | **200** |
| Hotel Manager | **403** |
| Accountant · Housekeeping | **403** (run 1) |

**Correctly gated — Property Owner only**, matching `billing.manage`.

## ⚠️⚠️ THE FABRICATED CARD — refused on a THIRD surface

`Operator Billing.html` shows a fully-populated commercial relationship:

> `Pro · Active · Monthly · 6 units × €9,00 · subscribed 5 Jul 2026 · €66,42/mo incl. 23% VAT`
> `Next invoice 5 Aug 2026 · €66,42 to **Visa ····4242** — reminder email 3 days before`
> `Invoices — #S-0001 · Pro monthly · 5 Jul 2026 · **Visa ····4242** · Paid €66,42 · PDF`
> `Switch to yearly · save €129,60`

**The live page shows the REAL subscription and NO CARD:** `Essential · Active · Monthly · subscribed
15 Aug 2026 · €49.00/mo`. ⚠️ **Card-detection on the rendered text returns FALSE** — no `Visa`, no `····`,
no masked PAN anywhere.

⚠️ **Confirmed against the data, not just the render:** the org's `stripe_subscription_id` is **NULL**. There
IS no card on file, so printing `Visa ····4242` would be inventing a payment instrument — and #41 holds that
the live charge is account-gated and an org is never flipped to active off a fake payment.

⚠️ **THIS IS THE THIRD SURFACE ON WHICH THE SAME FABRICATED CARD IS REFUSED**, across two realms:
1. **Owner Settings** (owner audit, page 16 item 21) — `Visa ···· 4242 · Expires 04/27` + `Update`
2. **Owner Booking-detail / Refund** (owner audit; operator run 4) — `Original card · Visa ····4242`
3. **Operator Billing** (here) — the subscription card + two invoice rows

⚠️ **Together with the `Suspicious login blocked` refusal in BOTH realms (run 20), that is two distinct
fabrications each refused everywhere they appear.** A refusal applied once is a choice; applied consistently
across surfaces and realms it is a **policy** — and this audit has now measured it four times.

## Element diff vs `Operator Billing.html`

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | `Settings` tabs `General` · `Team` · `Billing` | present | **MATCH** |
| 2 | Plan + status (`Pro · Active`) | **`Essential · Active`** (real) | **MATCH** |
| 3 | `Monthly · 6 units × €9,00 · subscribed 5 Jul 2026` | `Monthly · subscribed 15 Aug 2026` | **MATCH** — ⚠️ per-unit price breakdown absent (the real plan is flat-rate) |
| 4 | `€66,42/mo incl. 23% VAT` | **`€49.00/mo`** (real plan price) | **MATCH**; the VAT-inclusive label absent — **DIVERGENCE, minor** |
| 5 | `Next invoice … to **Visa ····4242**` | next-invoice date present, **no card** | ⚠️ **DIVERGENCE — RATIFIED refusal** (`stripe_subscription_id` NULL; #41) |
| 6 | `Invoices` list with `Paid` + `PDF` per row | not populated | **DATA-DEPENDENT** — no real invoices exist (no live charge has run, #41) |
| 7 | `Switch to yearly · save €129,60` | absent | **DIVERGENCE — the saving is a computed claim** on a plan/period pair that has never been billed |
| 8 | `30-day trial 5 Jun – 4 Jul 2026` | absent | **DATA-DEPENDENT** (this org is past trial, on a real subscription) |
| 9 | `Change plan` | present | **MATCH** |

⚠️ **MUTATION SURFACED, NOT EXERCISED:** `/app/billing/checkout` → Stripe Checkout (`billing.manage`).
⚠️ **Deliberately not opened** — it is the subscription-purchase seam, and #45 keeps the live-key flip as the
founder's step.

## Updated tally

Pages fully diffed **31 of 50** · findings 3 · candidates open 1 · candidates resolved 4 ·
⚠️ **refusals confirmed 31** (+ stored card, yearly-saving, VAT label) · auditor self-corrections 7 ·
hypotheses killed 1 · mutations surfaced/exercised **18 / 0** · **code changed 0**.

## REMAINING

**17 of 50.**

---

# RUN 25 — `/app/maintenance/costs` · ⚠️ AND A LEDGER CORRECTION: `rack` IS NOT A PAGE

## ⚠️ LEDGER CORRECTION — `/app/rack` is an ALIAS of `/app/calendar`

    Route::get('/rack',     ReservationRack::class)->name('rack');
    Route::get('/calendar', ReservationRack::class)->name('calendar');

**The same Livewire component under two route names.** Rendered as Property Owner, both return
**1,540 chars with an identical control set** — byte-identical output.

⚠️ **So `rack` was never a 50th page.** It has been sitting in the REMAINING list since run 1 as work still to
do, when run 7's `/app/calendar` diff already covered it. **The denominator is 49, not 50** — the third
inventory correction in this audit (after the truncated-at-the-dot regex and the export-surface omission).

⚠️ **A route list counts ROUTES, not SURFACES.** Two names can share one component, and only opening both
reveals it. Recorded so the remaining count is honest rather than flattering.

## `/app/maintenance/costs` vs `Operator Maintenance Costs.html`

**Gating:** Property Owner **200** · Accountant **403** — matching `maintenance.manage` (run 6's export sweep
found the same on its three export endpoints).

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | Costs page under Maintenance | present | **MATCH** |
| 2 | Cost figures (`€2,840` · `€316` · `€180` · `€920` · `€640`) | **real** figures (`€225.00` · `€112.50` · `€180.00` · `€45.00` · **`€0.00`**) | **MATCH** — ⚠️ including a genuine **€0.00**, not hidden |
| 3 | Export (csv/pdf/xlsx) | present, all three gated `maintenance.manage` (run 6) | **MATCH** |
| 4 | `Workspace paused` banner | absent | **DATA-DEPENDENT** (active subscription) |

⚠️ **The live figures carry CENTS (`€112.50`, `€225.00`) where the wireframe shows round hundreds** — the
prototype's numbers are illustrative, the build's come from real `maintenance_costs` rows.

## Updated tally

Pages fully diffed **32 of 49** · findings 3 · candidates open 1 · candidates resolved 4 ·
refusals confirmed 31 · ⚠️ **inventory corrections 3** · auditor self-corrections 7 · hypotheses killed 1 ·
mutations surfaced/exercised 18 / 0 · **code changed 0**.

## REMAINING

**17 of 49** — presentation parity only.

---

# RUN 26 — `/app/group-booking` + `/app/reservations/create` · ⚠️ THE TWO WIZARDS, AND A TAX RATE THE PROTOTYPE INVENTED

Both are multi-step create wizards with a sticky pricing rail. **Gating measured across all four roles**, each
run against `/app/dashboard` first so a 403 below is a gate and not a dead session:

| Route | Property Owner | Hotel Manager | Accountant | Housekeeping |
|---|---|---|---|---|
| `/app/dashboard` (control) | 200 | 200 | 200 | 200 |
| `/app/group-booking` | 200 | 200 | **403** | **403** |
| `/app/reservations/create` | 200 | 200 | **403** | **403** |

`reservations.create` — correct, and the two money-taking wizards are the two most write-heavy screens in the
operator app.

## ⚠️ THE DIVERGENCE THAT MATTERS: the prototype hardcodes a tax rate, the build derives one

Both wireframes total the stay client-side and add **`City tax`** and **`VAT (10%)`** as literal lines —
`grandTotal = net + cityTax + vat`, with the 10% written into the prototype's own JavaScript.

The build does not carry that number anywhere. `NewReservation::taxesFor()` reads the **property's real `Tax`
rows** and hands the client `['mode','rate','amount_minor']`; the rail then computes from those. On the seeded
property that renders **`€300.00` accommodation → `€69.00` tax → `€369.00`** — **23%, the configured rate**,
not the prototype's 10%.

⚠️ **A hardcoded rate in a prototype is a placeholder; the same number shipped is a wrong tax.** Recording this
as a DIVERGENCE the build is right about, in the direction the audit should always want.

## ⚠️ AND BOTH RAILS REFUSE TO PRICE AN EMPTY STAY — one of them learned it from the other

`new-reservation.blade.php:452` documents the original defect in its own words:

> Taxes include flat / per-night modes that compute a figure from the DATES alone … "Taxes €15.00, Estimated
> total —" — a tax on a stay that has no accommodation

⚠️ **A per-night tax is derivable from dates before any room is chosen** — so the tax line rendered a real
figure beneath an accommodation line reading `—`. The fix gates it on `unitId && taxMinor() > 0`.

The group-booking rail then generalised it *before* being bitten (`group-booking.blade.php:322`): its discount
and deposit lines are gated on `count > 0` as well as on their own percentage, because both are a percentage
**of** the accommodation, so revisiting step 4 with an emptied block would print `Group discount (10%) −€0.00`
against `—`. Its comment names the New Reservation gate as the reason.

⚠️ **That is a lesson travelling between two screens rather than being re-learned** — §N.8f's principle applied
to a *behaviour* rather than to a helper. Worth naming because a component of a total is itself a claim: `—`
is honest, `€0.00` is an assertion that the stay costs nothing.

## Per-element diff

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | 5-step group wizard, 7-step reservation wizard | same step counts | **MATCH** |
| 2 | Occasion chips (Wedding/Corporate/Family/Sports/Other) | all five | **MATCH** |
| 3 | Headcount stepper (`−` / `+`) | present on both | **MATCH** |
| 4 | Discount + deposit chips (25/50/100%) | present, plus cash/bank/**card · soon** | **MATCH + honest** |
| 5 | `City tax` + `VAT (10%)` rail lines | derived `Taxes` from real `Tax` rows | ⚠️ **DIVERGENCE — build correct** |
| 6 | Rail totals a stay with nothing selected | `—` until `count > 0` / `unitId` | ⚠️ **DIVERGENCE — build correct** |
| 7 | — | *"the authoritative total is the folio"* rail note | **EXTRA — honest** |
| 8 | Card deposit | present, labelled **`· soon`** with a real explanation | **MATCH + honest** (#45) |

⚠️ Item 7 is the §N.8d discipline stated on the screen: the rail is a client **estimate** built from real
prices, and the folio is authoritative. The prototype's total presents itself as the number.

## ⚠️ NO MUTATION EXERCISED

Neither wizard was advanced or submitted. The rail behaviour above was read from the **view source and the
component**, not by driving the flow — advancing a create wizard risks a write, and this pass is read-only.
What a submit *writes* was surfaced in run 9's write sweep, not re-tested here.

## Updated tally

Pages fully diffed **34 of 49** · findings 3 · candidates open 1 · candidates resolved 4 ·
refusals confirmed 33 · inventory corrections 3 · auditor self-corrections 7 · hypotheses killed 1 ·
mutations surfaced/exercised 20 / 0 · **code changed 0**.

## REMAINING

**15 of 49** — presentation parity only.

---

# RUN 27 — `/app/maintenance/settings` + `/app/maintenance/{ticket}` · ⚠️ FINDING 4, AND A CORRECTION TO MY OWN RECIPE

## ⚠️⚠️ FIRST: THE RECIPE IN THIS REPORT WAS WRONG — `<main>` CONTAINS THE SIDEBAR

Step 3 of HOW TO RESUME says *"scope every read to `document.querySelector('main')`, not the whole page"*.
Measured directly:

    {main: true, mainContainsNav: true}

⚠️ **`<main>` exists, and the operator sidebar is INSIDE it.** So every "scoped" read in this audit has in
fact been reading the chrome too — the mitigation the recipe prescribes was never actually in effect.

It bit immediately. My first pass took the visible controls capped at the first 30, and reported that
Property Owner sees the maintenance-settings form with **no `Save changes` button** while Hotel Manager sees
one — a spectacular-looking inversion where the *higher* role gets a read-only form. It was entirely mine:
**PO's sidebar carries more entries** (Roles & access, Settings, Billing), so the same 30-item window stopped
earlier in PO's list and cut the button off. A direct unbounded read returned `Save changes` for both.

⚠️ **A CAP APPLIED AFTER MIXING CHROME WITH CONTENT TRUNCATES BY ROLE** — and since privilege is exactly what
changes the sidebar length, the artefact *correlates with the thing being measured*. That is the most
dangerous shape an instrument error can take: it manufactures a gating finding out of nothing.

The correct read excludes chrome by ancestor, not by container:

    const inChrome = e => !!e.closest('nav,aside,header,.vy-sidebar,.vy-topbar,[class*="sidebar"],[class*="topbar"]');

Both pages below were re-measured with it. **Auditor self-correction #8.**

## `/app/maintenance/settings` vs `Operator Maintenance Settings.html`

| Role | Status | Save control | Editable fields |
|---|---|---|---|
| Property Owner | 200 | `Save changes` | 13 |
| Hotel Manager | 200 | `Save changes` | 13 |
| Accountant | **403** | — | — |
| Housekeeping | **403** | — | — |

`maintenance.manage` — correctly gated, and identical for the two permitted roles.

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | Resolution targets | 4 real SLA fields (`sla_urgent/high/normal/low_hours`) | **MATCH** |
| 2 | Targets as preset chips (`Same day` · `4 hours` … `1 month`) | numeric inputs, `min=1 max=720` | **DIVERGENCE (minor)** — free numeric beats a fixed ladder |
| 3 | Auto-assignment | `auto_assign_enabled` + 5 per-category assignee selects | **MATCH** |
| 4 | Technicians | the same 5 selects (plumbing/electrical/appliance/structural/other) | **MATCH** |
| 5 | — | **Out-of-order policy** (`block_room_from_sale`, `notify_front_desk`, `require_photo_on_resolve`) | **EXTRA** |
| 6 | — | **Notify me about** — 5 real toggles incl. an SLA-breach and a daily digest | **EXTRA** |

⚠️ Item 5 is inventory-touching (`block_room_from_sale` decides whether a work order pulls a unit from sale)
and is **surfaced, not exercised**.

## `/app/maintenance/{ticket}` vs `Operator Work Order.html`

⚠️ **A WIREFRAME THAT HAD NO ROUTE UNTIL NOW.** `Operator Work Order.html` was not previously mapped in this
ledger; it is the detail view behind `maintenance.show`. Recorded as a ledger addition, in the same class as
run 25's alias correction — **the inventory keeps moving in both directions.**

Read on the seeded ticket `01m0210t65…` (*"Wardrobe door off its runner"*, status `open`).

| Role | Actions rendered | POST forms |
|---|---|---|
| Property Owner | Start work · Note · Progress · Part ordered · Post update · Reassign · Mark awaiting parts · Cancel work order | 6 |
| Hotel Manager | identical | 6 |
| Accountant | **none** | **0** |
| Housekeeping | **none** | **0** |

Gating is **correct and complete at the render layer**: `MaintenanceTicketDetail.php:94` derives one
`$canManage` from `maintenance.manage` and every capability flag (`can_start`, `can_resolve`, `can_cancel`,
`can_assign`, `can_parts`) is `$canManage && <status condition>` — so the two unprivileged roles get a genuine
read-only view, not a disabled one.

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | `Mark resolved` in the header | `Start work` | **MATCH — status-derived** (`can_resolve` needs `in_progress`; this ticket is `open`) |
| 2 | Update types Note / Progress / Part ordered | all three | **MATCH** |
| 3 | `Add photo` · `Post update` | both, real `/photos` + `/notes` POSTs | **MATCH** |
| 4 | `Reassign` / *"Open the rack to reassign"* | `Reassign` → a real `/assign` POST | **DIVERGENCE — build correct** (acts in place, no detour) |
| 5 | — | `Mark awaiting parts`, `Cancel work order` | **EXTRA** — real lifecycle transitions |
| 6 | `€310` in the wireframe body | no money on the work order | **DIVERGENCE** — costs live on `/app/maintenance/costs` (run 25) |

## ⚠️⚠️ FINDING 4 (minor) — `Add update` is offered to roles that cannot add one

`maintenance-ticket-detail.blade.php:56`

    <a href="#wo-composer" class="vy-btn vy-btn--ghost">Add update</a>

⚠️ **This anchor carries no gate at all**, while the composer it points at is inside
`@if ($canManage && ! $readOnly)`. And the id it targets sits on the **Activity card** (line 164), which is
ungated — so for Accountant and Housekeeping the link resolves, scrolls to a read-only timeline, and nothing
happens. Measured: both roles render `Add update` with **zero action buttons and zero forms** on the page.

**Verdict: OVER-EXPOSED LABEL, not an over-exposed capability.** Nothing becomes writable, no request is
issued, and no 403 is invited — this is weaker than Finding 2 (a control that does invite a 403) and weaker
than Finding 3. It is a **dead affordance**: the page offers an action to a reader who cannot perform it.

⚠️ **Recorded, not fixed** — report-only. The one-line shape of a fix is visible (the same `@if ($canManage
&& ! $readOnly)` already wrapping its own target), which is precisely why it must not be applied here.

⚠️ Note the pattern across three of this audit's four findings: **the gate exists and is correct; what is
missing is the gate on the CONTROL that advertises it.** Findings 2, 3 and 4 are all that same shape, on three
unrelated screens — which makes it a systematic omission rather than three coincidences, and worth fixing as
one pass rather than three patches.

## Mutations SURFACED — none exercised

| Endpoint | Writes | Money | Inventory |
|---|---|---|---|
| `POST …/{ticket}/start` | `maintenance_tickets.status` → `in_progress` | no | no |
| `POST …/{ticket}/resolve` | status → resolved (+ optional return-to-sale) | no | ⚠️ **yes** |
| `POST …/{ticket}/cancel` | status → cancelled | no | no |
| `POST …/{ticket}/assign` | assignee | no | no |
| `POST …/{ticket}/notes` | a timeline note (`body`) | no | no |
| `POST …/{ticket}/parts` | awaiting-parts + `part_name` / `part_eta` | no | no |
| `POST …/{ticket}/photos` | a media row | no | no |
| `POST /app/maintenance/settings` | 13 settings incl. `block_room_from_sale` | no | ⚠️ **policy** |

All **UI-gated; server-enforcement unverified-without-mutation.**

## Updated tally

Pages fully diffed **36 of 49** · **findings 4** · candidates open 1 · candidates resolved 4 ·
refusals confirmed 33 · inventory corrections 4 · **auditor self-corrections 8** · hypotheses killed 1 ·
mutations surfaced/exercised 28 / 0 · **code changed 0**.

## REMAINING

**13 of 49** — presentation parity only.

---

# RUN 28 — `/app/guests/{guest}` + `/app/owners/{owner}` · ⚠️⚠️ FINDING 1 WAS MEASURED OVER THE WRONG SET

## ⚠️⚠️ THE HEADLINE: A PARAMETERISED ROUTE CANNOT BE FETCHED BY A SWEEP THAT READS `route:list`

Run 13 replaced four revisions of inference with one pass of ground truth and bounded Finding 1 at **13
ungated routes, 7 carrying money**. That pass fetched each URI as `demo-housekeeper` and read the status.

⚠️ **But a URI containing `{owner}` is not fetchable.** A sweep that walks `route:list` and requests each
`uri` verbatim silently gets nothing useful from every parameterised route — so **Finding 1 was measured over
the non-parameterised routes only**, and its "13" was never the whole set. Nothing in the output said so; a
skipped row and a passing row look identical in a status table.

Substituting real ids and re-fetching as **Housekeeping (4 permissions)**:

| Parameterised GET route | Route gate | HK status | What HK sees |
|---|---|---|---|
| `/app/owners/{owner}` | *(none)* | **200** | ⚠️ **€4,653.60 gross · €2,804.40 commission · €885.60 net · statements · payouts** — and the owner's email `demo-owner@vyrobel-demo.test` |
| `/app/reservations/{reservation}` | *(none)* | **200** | ⚠️ **€300.00 · €69.00 · €369.00** folio money |
| `/app/maintenance/{ticket}` | *(none)* | 200 | no money (read-only, run 27) |
| `/app/inbox/{thread}` | *(none)* | **403** | — ⚠️ see the alias note below |
| `/app/guests/{guest}` | `Authorize:guests.view` | **403** | — |
| `/app/reservations/{reservation}/refund` | `Authorize:payments.refund` | *(not fetched — a money route)* | — |

⚠️ **Seven of the nine parameterised GET routes carry no permission middleware at all** — only
`guests/{guest}` and the refund route do.

**FINDING 1 IS EXTENDED, not superseded:** two further routes reachable by the least-privileged role carry
money, and `/app/owners/{owner}` additionally renders an **owner's email address** — contact PII outside the
Guests CRM, which is the perimeter invariant #28 draws. The owner-detail page is a strictly *richer* leak than
the `/app/owners` list already in Finding 1 (which showed one figure, €885.60): the detail view adds gross,
commission, the statement table and the payout history.

⚠️ **Recorded, not fixed.** And note what made it findable: not a new idea, but **running the existing method
over the set it had structurally excluded**. §N.27.1 — *a register is a record of what one reader found* —
applied to my own register.

## ⚠️ INVENTORY CORRECTION #5 — `/app/inbox/{thread}` is a redirect, not a page

    Route::get('/inbox/{thread}', fn (string $thread) => redirect()->route('app.inbox', ['c' => $thread]))

A legacy deep-link that redirects to `/app/inbox?c={id}`. Its 403 for Housekeeping comes from the
**redirect target** (`/app/inbox` is `guests.view`-gated, diffed in run 11) — not from any gate of its own.

⚠️ **So its ungated row above is not a hole**, and it is not a page owed a diff. Third alias of this class
after `rack`→`calendar` (run 25) and the owner audit's `/owner/messages/{thread}`. **Denominator 49 → 48.**

## ⚠️ AUDITOR SELF-CORRECTION #9 — my noise filter ate the signal

My capture excluded any address matching `vyrobel-demo.test`, to stop the logged-in operator's own address in
the topbar from contaminating a `#28` read. The guest profile then showed a phone number and **no email**, and
I was one step from recording an asymmetry.

The demo guest's email **is** `demo.traveller@vyrobel-demo.test`. **My own filter removed it.**

⚠️ Second time in two runs that a *noise-reduction step* destroyed the measurement — run 27's cap truncated by
role, this one filtered by domain. **A filter written against chrome will happily eat content that resembles
it**, and the failure is silent in exactly the direction an auditor wants to believe. The corrected read
filters nothing and scopes to `.vy-main` by ancestor instead.

The email rendering on the guest profile is **CORRECT**: #28 permits contact PII on the Guests CRM, and only
there.

## `/app/guests/{guest}` vs `Operator Guest Profile.html`

| Role | Status | Actions |
|---|---|---|
| Property Owner | 200 | Message · Edit profile · Add note · Tags add/remove · **Erase** |
| Hotel Manager | 200 | same, **no Erase** |
| Accountant | **403** | — |
| Housekeeping | **403** | — |

**`Erase` is correctly gated** — `guest-profile.blade.php:272` wraps it in `@can('guests.erase')`, and Hotel
Manager renders the whole page without it. A clean render-layer gating proof, no mutation.

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | Stay history · Latest review · Contact · Preferences & notes | all present | **MATCH** |
| 2 | Contact block with email + phone | real encrypted-cast values rendered | **MATCH** (#28-permitted surface) |
| 3 | `Edit profile` · `Add note` | both, real POSTs | **MATCH** |
| 4 | — | `Message`, Tags add/remove, **Erase this guest** (GDPR `anonymize`) | **EXTRA** |
| 5 | Stay figures (€1,023 / €1,120 / €3,940 …) | real folio figures (€123.00 / €369.00 / €1,107.00 / €184.50) | **MATCH** — real, not the prototype's |

## `/app/owners/{owner}` vs `Operator Owner Detail.html`

| Role | Status | Actions | `Rate proposals` section |
|---|---|---|---|
| Property Owner | 200 | Edit owner · Propose a change · Add note | yes |
| Hotel Manager | 200 | **Propose a change only** | yes |
| Accountant | 200 | **none** | **no** |
| Housekeeping | 200 | **none** | **no** |

Gating **of the controls** is correct and layered — `Edit owner`/`Add note` need `users.manage` (PO only),
the proposals panel needs `rates.manage` (`owner-detail.blade.php:147`), and the two unprivileged roles get a
genuinely read-only render with zero POST forms. ⚠️ **But the PAGE ITSELF is ungated** — which is the finding
above: the controls are gated, the *financial content* is not.

| # | Wireframe | Live | Verdict |
|---|---|---|---|
| 1 | Owner identity + contact | present | **MATCH** |
| 2 | `Edit owner` · `Message` · `Add note` | Edit owner · Add note (PO); Message via the inbox | **MATCH** |
| 3 | Portfolio figures (€10,370 / €74,830 / €12,400 …) | real (€4,653.60 / €2,804.40 / €885.60 / €194.40) | **MATCH** — real ledger, not the prototype's |
| 4 | — | **Rate proposals** panel (#67), **Management agreement**, **Statements** table, **Payouts** | **EXTRA** |
| 5 | — | `pause-payouts`, `remove` POSTs | **EXTRA** — surfaced, not exercised |

## Mutations SURFACED — none exercised

| Endpoint | Writes | Money |
|---|---|---|
| `POST /app/guests/{guest}` | guest profile fields | no |
| `POST …/{guest}/notes` · `/tags` · `/message` | note / tag / outbound message | no |
| ⚠️ `POST …/{guest}/anonymize` | **GDPR erase — strips PII, sets `anonymized_at`** | no, **irreversible** |
| `POST /app/owners/{owner}/update` · `/note` | owner fields, note | no |
| ⚠️ `POST …/{owner}/pause-payouts` | halts the payout rail for this owner | ⚠️ **money-adjacent** |
| ⚠️ `POST …/{owner}/remove` | removes the owner relationship | ⚠️ **money-adjacent** |
| `POST /app/rate-proposals` | a `rate_proposals` row (#67) | no |

All **UI-gated; server-enforcement unverified-without-mutation.**

## Updated tally

Pages fully diffed **38 of 48** · findings 4 (**#1 extended**) · candidates open 1 · candidates resolved 4 ·
refusals confirmed 33 · **inventory corrections 5** · **auditor self-corrections 9** · hypotheses killed 1 ·
mutations surfaced/exercised 36 / 0 · **code changed 0**.

## REMAINING

**10 of 48** — presentation parity only.

---

# RUN 29 — THE LAST FIVE PAGES · ⚠️ FINDING 5 · ⚠️ THE DENOMINATOR WAS NEVER DERIVED · ✅ N = M

## ⚠️⚠️ INVENTORY CORRECTION #6 — the denominator was inherited, never derived

Rule #1 of this audit says *build the ledger from `route:list` — DERIVE it, don't assume a count*. The **page
ledger** was derived. **The denominator was not.** "50" came from a run-1 estimate and was then adjusted by
−1 per alias discovered (→49 in run 25, →48 in run 28), so it stayed *plausible* while never once being
recomputed from the source it claimed to come from.

Derived properly:

    54 distinct GET /app URIs − 2 aliases (rack → calendar, inbox/{thread} → inbox)  =  52 pages

⚠️ **A number that is maintained by adjustment rather than by recomputation drifts silently** — every
correction I made to it was locally right and the total stayed wrong. The whole-ledger rebuild below is
derived from `route:list`, page by page, and every one of the 52 maps to the run that diffed it.

## The last five pages, all four roles

| Page | Prop Owner | Hotel Mgr | Accountant | Housekeeping | Gate | Verdict |
|---|---|---|---|---|---|---|
| `/app/notification-preferences` | 200 (16 toggles) | 200 | 200 | 200 | *(none — self-only)* | **correctly open** — a user's own preferences |
| `/app/billing/checkout` | 200 (€99.00) | **403** | **403** | **403** | `can:billing.manage` | **correctly gated** |
| `/app/owners/invite/preview` | 200 | **403** | **403** | **403** | `can:users.manage` | **correctly gated** |
| `/app/schedule/pdf` | 200 · **881 KB real PDF** | 200 · PDF | **403** | 200 · PDF | `can:schedule.view` | **correctly gated** — HK holds `schedule.view`, ACC does not |
| ⚠️ `/app/email-templates/{key}/edit` | 200 | 200 | **200** | **200** | *(none)* | ⚠️ **OVER-EXPOSED — Finding 5** |

⚠️ The schedule PDF is a **download**, so `page.goto()` aborts with *"Download is starting"* and returns no
status at all. Read through the request context instead — it returned a genuine `%PDF` of 881,198 bytes for
three roles and a 403 HTML body for the Accountant. **A capture harness that only knows how to navigate will
score every download as an error**, which reads identically to a broken page.

⚠️ The editor key was **derived from the rendered list page**, not invented: `email_templates` has **0 rows**
(it is a config catalogue with DB overrides), so a guessed key would have 404'd and I would have recorded a
phantom absence.

## ⚠️⚠️ FINDING 5 — the email-template editor renders WRITE controls for every back-office role

| Route | Gate |
|---|---|
| `GET /app/email-templates` | *** none *** |
| `GET /app/email-templates/{key}/edit` | *** none *** |
| `POST /app/email-templates/{key}` | `Authorize:properties.manage` |
| `POST /app/email-templates/{key}/test` | `Authorize:properties.manage` |
| `POST /app/email-templates/{key}/toggle` | `Authorize:properties.manage` |

**The three writes are correctly gated. The page that offers them is not**, and the view carries **no `@can`
anywhere** — measured, not assumed. So Accountant and **Housekeeping (4 permissions)** both render the
booking-confirmation editor complete with **`Save template`**, **`Send me a test`**, the merge-tag palette and
3 section toggles, pointed at two `properties.manage` endpoints.

**Verdict: OVER-EXPOSED.** Two distinct problems, worth separating:

1. **A write control that invites a 403** — the Finding 2 shape, but three controls rather than one, and on a
   page reachable by the *least*-privileged role.
2. **A read exposure** — the org's outbound guest email copy (subject, heading, body, merge tags) is legible
   to every back-office role. Not PII, so not a #28 breach; still content no housekeeper needs.

⚠️ **Server-enforcement is UI-gated-only from here**: that the POST would actually reject is what the route
middleware says, and proving it would require POSTing a gated action. **Unverified-without-mutation.**

## ⚠️⚠️ THE PATTERN IS THE REAL RESULT — 4 of the 5 findings are one omission

| Finding | Screen | Gate exists | Control gated |
|---|---|---|---|
| 2 | `/app/calendar` — `New reservation` | ✅ route | ❌ |
| 3 | `/app/schedule` — the drag instruction | ✅ route | ❌ (partial) |
| 4 | `/app/maintenance/{ticket}` — `Add update` | ✅ view | ❌ |
| 5 | `/app/email-templates/{key}/edit` — `Save template` ×3 | ✅ route | ❌ |

⚠️ **Every gate in this build is present and correct. What is missing, on four unrelated screens, is `@can`
on the control that advertises the gated action.** CLAUDE.md states the rule explicitly — *"Gate the CONTROL
too (`@can`), so the UI never invites a 403"* — so this is not an unknown principle but an **unswept one**.

⚠️ **That makes it one pass, not four patches** — and the sweep should be over *controls whose target route
carries `Authorize:`*, which is mechanically enumerable, rather than over screens someone remembers.

Finding 1 stands apart: it is not a control problem but **pages with no gate at all**, 2 of them parameterised
and carrying money (run 28).

---

# ✅ THE COMPLETE LEDGER — 52 pages, all diffed

| Page | Run | Page | Run |
|---|---|---|---|
| `/app/dashboard` | 1 | `/app/maintenance/costs` | 25 |
| `/app/payouts/run` | 1 | `/app/maintenance/costs/export.csv` | 6 |
| `/app/owners` | 1 | `/app/maintenance/costs/export.pdf` | 6 |
| `/app/front-desk` | 2 | `/app/maintenance/costs/export.xlsx` | 6 |
| `/app/reservations` | 3 | `/app/reports/export.csv` | 6 |
| `/app/rates` | 3 | `/app/reports/export.pdf` | 6 |
| `/app/housekeeping` | 4 | `/app/reports/export.xlsx` | 6 |
| `/app/reservations/{r}/refund` | 4 | `/app/security/export` | 20 |
| `/app/reservations/{r}` | 5 | `/app/settings` | 20 |
| `/app/reservations/{r}/folio.pdf` | 5 | `/app/security` | 20 |
| `/app/calendar` | 7 | `/app/profile` | 21 |
| `/app/guests` | 11 | `/app/notifications` | 21 |
| `/app/inbox` | 11 | `/app/guest-requests` | 22 |
| `/app/channels` | 12 | `/app/unit-requests` | 22 |
| `/app/units` | 12 | `/app/email-templates` | 23 |
| `/app/maintenance` | 15 | `/app/email-previews` | 23 |
| `/app/reviews` | 15 | `/app/email-previews/{key}` | 23 |
| `/app/insights` | 16 | `/app/billing` | 24 |
| `/app/reports` | 16 | `/app/group-booking` | 26 |
| `/app/portfolio` | 17 | `/app/reservations/create` | 26 |
| `/app/search` | 17 | `/app/maintenance/settings` | 27 |
| `/app/schedule` | 18 | `/app/maintenance/{ticket}` | 27 |
| `/app/roles` | 18 | `/app/guests/{guest}` | 28 |
| `/app/notification-preferences` | 29 | `/app/owners/{owner}` | 28 |
| `/app/billing/checkout` | 29 | `/app/owners/invite/preview` | 29 |
| `/app/email-templates/{key}/edit` | 29 | `/app/schedule/pdf` | 29 |

**Aliases (not pages):** `/app/rack` → `calendar` (run 25) · `/app/inbox/{thread}` → `inbox` (run 28).

---

# ⚠️ SELF-CHECK AT N = M — including what it does NOT cover

| Check | Result |
|---|---|
| Ledger 0 remaining | ✅ 52/52, denominator **derived** from `route:list` |
| Every page control-diffed across all 4 roles | ✅ |
| Every finding confirmed at source, not inferred | ✅ 5/5 (route middleware + view source quoted for each) |
| Every gating verdict mapped to the capability matrix | ✅ |
| Money + inventory as-seeded | ✅ `693e3237…` · 929 rows · 41 reservations · 16 payments · 0 overrides · 0 seasons · 0 anonymized |
| Mutations exercised | ✅ **0** of 41 surfaced |
| Code changed | ✅ **0** |
| Seasonal apply-panel treated as a deferral | ✅ recorded at `/app/rates` (run 3) |
| ⚠️ Screenshot per page **per role** | ⚠️ **CORRECTED IN RUN 30 — this row was wrong.** "68" was the whole `.captures/` directory (28 owner-audit + 19 unrelated); the operator audit had **21**. Now **58**: every renderable page at the reference role + 6 role-pairs evidencing the findings. 8 artifact routes are downloads, not renderable. The 208-image matrix was never attempted and is not claimed. |

## ⚠️ What this audit could NOT establish (by construction)

- **Server-side enforcement of any gated write.** Every POST is `UI-gated; unverified-without-mutation` —
  41 mutations surfaced, 0 exercised. Finding 5's three endpoints included.
- **Whether Finding 1's ungated pages are also unscoped.** They render *this org's* data to a low-privilege
  member of *this org*; cross-tenant isolation is a separate (and separately certified) property.
- **Behaviour behind a wizard step.** Both create wizards were read at step 1 and from source (run 26).

## ⚠️ The five findings, ranked

| # | Severity | What |
|---|---|---|
| **1** | ⚠️ **highest** | Pages with **no gate at all** — incl. `/app/owners/{owner}` (gross/commission/statements/payouts **+ an owner email**) and `/app/reservations/{r}` (folio money) to Housekeeping. ⚠️ Parameterised routes were **structurally invisible** to the original sweep. |
| **5** | high | Email-template editor: 3 write controls rendered for every back-office role; writes gated, page not. |
| **2** | medium | `/app/calendar` `New reservation` without `@can` — invites a 403. |
| **4** | low | `/app/maintenance/{ticket}` `Add update` offered to roles that cannot add one. |
| **3** | low | `/app/schedule` drag instruction shown to a role that cannot drag. |

**Findings 2–5 are one sweep** (gate the control wherever its route carries `Authorize:`). **Finding 1 is a
separate, larger question** (which pages should carry a gate at all).

---

# RUN 30 — ⚠️⚠️ THE SELF-CHECK WAS ITSELF WRONG, AND THE CAPTURE GAP IT MIS-SIZED IS NOW CLOSED

## ⚠️⚠️ THE SELF-CHECK CONTAINED THE EXACT ERROR IT EXISTS TO CATCH

Run 29's self-check honestly marked the screenshot criterion ❌ — and then mis-stated it:

> ⚠️ Screenshot per page **per role** — ❌ NOT met — **68 captures** for 52 pages × 4 roles

**68 was the whole `.captures/` directory.** Counted properly:

    deep-operator-*  21        ← this audit
    deep-owner-*     28        ← the OWNER audit (a different deliverable)
    other            19        ← DO-item / restore captures from earlier work
    ─────────────────────
    total            68

⚠️ **The operator audit had 21 captures, not 68** — and I credited it with all three sets because they share
a directory. **A superset counted and attributed to the subset**: the same error as run 29's inherited
denominator, run 28's parameterised-route blind spot, and run 27's role-varying cap.

⚠️ **The bitter part: this happened INSIDE the self-check whose whole job is to catch it** — and it happened
in the *flattering* direction, making a real gap look three times smaller. **A self-check is not
self-validating.** It is another measurement, made by the same instrument that made the errors, and it needs
its own ground truth. §N.4p's mutation rule generalised: *a check must be independent of what it checks.*

## The gap, actually closed

**+37 captures this run** (21 → **58**), covering every renderable page not captured by run 14, plus the
least-privileged role on every page carrying a finding.

**Property Owner — the reference render, 31 further pages:** portfolio · insights · reports · search · units ·
channels · maintenance · reviews · schedule · roles · settings · security · profile · notifications ·
guest-requests · unit-requests · email-templates · email-previews · billing · billing-checkout ·
group-booking · new-reservation · maintenance-costs · maintenance-settings · work-order ·
notification-prefs · invite-preview · owner-detail · guest-profile · email-editor · email-preview-one

**⚠️ Housekeeping — the gating evidence, 6 pairs.** These are the findings as images:

| Capture | Status | What it evidences |
|---|---|---|
| `42-owner-detail-housekeeping` | **200** | ⚠️ **Finding 1** — gross · commission · statements · payouts **+ an owner email** |
| `46-reservation-detail-housekeeping` | **200** | ⚠️ **Finding 1** — folio money on a parameterised route |
| `44-email-editor-housekeeping` | **200** | ⚠️ **Finding 5** — `Save template` rendered for 4 permissions |
| `39-work-order-housekeeping` | **200** | ⚠️ **Finding 4** — `Add update` with no composer beneath it |
| `38-maintenance-settings-housekeeping` | **403** | gating **correct** |
| `34-billing-checkout-housekeeping` | **403** | gating **correct** |

⚠️ `20-channels-propowner` is a **403 for the Property Owner** — the `EnsurePlanFeature` gate on an Essential
plan (run 12). The highest-privilege role refused by a *plan*, not a permission, is worth having as an image
precisely because it looks like a bug and is not.

## ⚠️ What is still NOT captured, stated plainly

- **8 artifact routes cannot be screenshotted by construction** — 6 `export.{csv,pdf,xlsx}`,
  `reservations/{r}/folio.pdf`, `schedule/pdf`. They are downloads; run 29 verified `schedule/pdf` as a real
  881,198-byte `%PDF` per role, and run 6 verified the exports by status. **Not "missing" — not renderable.**
- **The full 52 × 4 matrix (208 images) was never the standard applied.** What exists is: every renderable
  page at the reference role, plus the second role wherever the control-set differs or a finding lives.
  ⚠️ **Stated as a bounded claim rather than reported as ✅.**
- `.captures/` is **gitignored** — these are local evidence, not repo artifacts.

## Updated self-check row

| Check | Result |
|---|---|
| Screenshot coverage | ⚠️ **58 operator captures** — 44 renderable pages at the reference role + 6 role-pairs on the findings + run 14's 12. **8 artifact routes not renderable.** Full 208-image matrix **not** attempted, and not claimed. |

## Tally — unchanged by this run

Pages diffed **52 of 52** · findings **5** · inventory corrections 6 · **auditor self-corrections 10** ·
mutations surfaced/exercised 41 / **0** · **code changed 0**.

---

# ⚠️ HOW TO RESUME — the recipe, so the next session does not rediscover the traps

✅ **The audit is COMPLETE — 0 pages remain.** The recipe below is kept for re-verification. Everything below cost this audit real runs to learn.
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
⚠️ **CORRECTED IN RUN 27 — do NOT scope to `main`: the operator sidebar is INSIDE `<main>`.** Exclude
chrome by ancestor instead (`e.closest('nav,aside,header,[class*="sidebar"],[class*="topbar"]')`), and never
cap a control list that still contains chrome — the sidebar length varies WITH privilege, so the truncation
correlates with the very thing being measured.

## 4 · ⚠️ ROUTE GATING — three gate types, and one wrong needle

    php artisan route:list --path=app --json     # then match 'Middleware\Authorize:' — NOT 'can:'

⚠️ `can:` is resolved to `Illuminate\Auth\Middleware\Authorize:<perm>` in the JSON; searching for the alias
returns "0 gated" and is wrong. ⚠️ **There are THREE gate types** — permission (`Authorize:`), subscription
(`EnsureSubscriptionActive`), and **plan feature (`EnsurePlanFeature`, which 403s `/app/channels` for every
role on Essential)`. ⚠️ **A regex over URIs must not stop at the dot** — `app/[a-z0-9/_{}-]*` silently
truncated `folio.pdf` and all six `export.{csv,pdf,xlsx}` routes, hiding the entire export surface.

⚠️⚠️ **AND SUBSTITUTE IDS — a `{param}` URI is NOT fetchable.** Run 13's ground-truth sweep silently skipped
every parameterised route, so Finding 1's count was measured over a subset. A skipped row and a clean row look
identical in a status table. Substitute a real id for every `{...}` before believing any coverage number.

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
