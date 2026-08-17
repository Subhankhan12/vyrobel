# Owner-portal DEEP parity audit — 2026-08-17 (RESUMABLE)

**Read-only · report-only · no code changed · demo money-as-seeded**
(`rate_calendar` `2bbdafccc3b5c1ee43f89fe313a3f02d` · 0 unit overrides · 41 reservations — verified at start.)

⚠️ **A page counts as DONE only if it was opened in Playwright, rendered, and diffed element-by-element
against its rendered wireframe.** A curl/HTTP-200/wiring check does **not** qualify — that was exactly the
prior pass's limit (`owner-portal-parity-2026-08-17.md` browser-diffed the Overview and nothing else).

## HARNESS — both sides proven before any diff

| Instrument | Proof |
|---|---|
| Credentials | from `DemoSeeder.php:234` — `demo-owner@vyrobel-demo.test` / `password` |
| Playwright ↔ live app | navigating `/login` lands on **`/owner`**, page title *Vyrobel — Owner Portal* |
| Live page renders real content | prior run: 24 owner nav links, `€885.60`, `€3,690.00` |
| Playwright ↔ wireframes | served at `:8099`; `Owner Dashboard.html` rendered **1,690 chars** of real copy |
| Demo seeded | 41 reservations; `rate_calendar` hash above |

## LEDGER

| # | Page | Wireframe | Status |
|---|---|---|---|
| 1 | `/owner` | Owner Dashboard.html | **DONE** (re-diffed IN THIS PASS — full element table below; the prior pass's diff is superseded) |
| 2 | `/owner/payouts` | Owner Payouts.html | **DONE** (browser-diffed this run) |
| 3 | `/owner/units` | Owner My Units.html | **DONE** (browser-diffed this run) |
| 4 | `/owner/bookings` | Owner Bookings.html | **DONE** (browser-diffed this run) |
| 5 | `/owner/balance` | Owner Balance.html | **DONE** (browser-diffed this run) |
| 6 | `/owner/expenses` | Owner Expenses.html | **DONE** (browser-diffed this run) |
| 7 | `/owner/calendar` | Owner Block Calendar.html | **DONE** (browser-diffed this run) |
| 8 | `/owner/documents` | Owner Documents.html | **DONE** (browser-diffed this run) |
| 9 | `/owner/tax` | Owner Tax Center.html | **DONE** (browser-diffed this run) |
| 10 | `/owner/reports` | Owner Reports.html | **DONE** (browser-diffed this run) |
| 11 | `/owner/insights` | Owner Insights.html | **DONE** (browser-diffed this run) |
| 12 | `/owner/rates` | Owner Rates.html | **DONE** (browser-diffed this run) |
| 13 | `/owner/reviews` | Owner Reviews.html | **DONE** (browser-diffed this run) |
| 14 | `/owner/messages` | Owner Messages.html | **DONE** (browser-diffed this run) |
| 15 | `/owner/notifications` (feed) | *(none — see correction)* | **DONE** (browser-diffed this run; enhancement-check, no wireframe) |
| 16 | `/owner/settings` | Owner Settings.html | **DONE** (browser-diffed this run) |
| 17 | `/owner/security` | Owner Security.html | **DONE** (browser-diffed this run) |
| 18 | `/owner/team` | Owner Team.html | **DONE** (browser-diffed this run) |
| 19 | `/owner/agreement` | Owner Agreement.html | **DONE** (browser-diffed this run) |
| 20 | `/owner/help` | Owner Help.html | **DONE** (browser-diffed this run) |
| 21 | `/owner/welcome` | Owner Onboarding.html | **DONE** (browser-diffed this run; all 3 wireframe steps) |
| 22 | `/owner/add-unit` | Owner Add Unit.html | **DONE** (browser-diffed this run; all 4 wireframe steps) |
| 23 | `/owner/archive` | Owner Archive.html | **DONE** (browser-diffed this run) |
| 24 | `/owner/payout-method` | Owner Payout Method.html | **DONE** (browser-diffed this run) |
| 25 | `/owner/units/{id}` | Owner Unit Detail.html | **DONE** (browser-diffed this run) |
| 26 | `/owner/bookings/{id}` | Owner Booking Detail.html | **DONE** (browser-diffed this run) |
| 27 | `/owner/statements/{id}` | Owner Statement.html | **DONE** (browser-diffed this run) |
| 28 | `/owner/notification-preferences` | **Owner Notifications.html** (⚠️ ledger corrected) | **DONE** (browser-diffed this run) |

**28 of 28 DONE — ✅ AUDIT COMPLETE.**

---
## Page 1 — `/owner` vs `Owner Dashboard.html` — **DONE (browser-diffed IN THIS PASS)**

⚠️ **Why this section exists.** The ledger inherited Page 1 as DONE from the prior report
(`owner-portal-parity-2026-08-17.md`), which browser-diffed it. That made this document **27/28
self-contained** — every other page carried its own element table, and the Overview carried only a ledger
row pointing at another file. It was re-diffed here so the audit stands alone.

- **Route:** `/owner` · **Wireframe:** `docs/design/raw/Owner Dashboard.html` (hydrated, **1,690 chars**)
- **Live render:** hydrated, **1,651 chars** · **Screenshot:** `.captures/deep-owner-01-overview-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Good morning, Amara` / `€10,370.00` / `est. 3 Nov 2025`;
  live rendered `Good evening, Demo` / `€885.60` / the real tax-hold.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Brand `V Vyrobel` + `OWNER PORTAL` | identical | **MATCH** |
| 2 | Nav group (unlabelled) — `Overview` · `My units` · `Calendar` | same + **`Bookings`** + **`Add a unit`** | **MATCH** + **EXTRA** (2 real routes the wireframe lacks) |
| 3 | Nav `EARNINGS` — `Statements` · `Payouts` · `Balance` · `Expenses` · `Tax center` | same + **`Payout method`** + **`Documents`** | **MATCH** + **EXTRA** |
| 4 | Nav `MANAGE` — `Reports` · `Insights` · `Rates` · `Reviews` · `Agreement` · `Messages` | **identical, all six** | **MATCH** |
| 5 | Nav `ACCOUNT` — `Settings` · `Security` · `Team & access` · `Help & support` | same + **`Notifications`**; `Settings` renders as **`Profile`** | **MATCH** + **EXTRA** + **DIVERGENCE — naming** (#46 split the account area; `Profile` is the per-user identity page) |
| 6 | Greeting `Good morning, Amara` | `Good evening, Demo` | **MATCH** — real name, and the salutation is genuinely time-derived (read at 19:28 local) |
| 7 | Bell badge `2` | `6` | **MATCH** (real unread count, consistent with every other page) |
| 8 | User chip `Amara Okafor AO` | `Demo Owner DO` | **MATCH** (real name + real initials) |
| 9 | `Needs your attention` + count `6` | `Needs your attention` + `2` | **MATCH** (real count) |
| 10 | Sub-line `Approvals and tasks waiting on you` | `2 items need your attention` | **DIVERGENCE — copy** (the build states the real number rather than a generic label) |
| 11 | `Open Action center` control | identical | **MATCH** |
| 12 | Attention item `Rate change proposed` · `Villa Coralis · peak summer` · `Approve` | **absent** | **DATA-DEPENDENT, not missing.** The rate-proposal entity is REAL (#67, and diffed on pages 3/11/12); `rate_proposals` has **0 rows** seeded, so no item can render |
| 13 | Attention item `Maintenance expense · €340` · `Villa Dunes · pool pump` · `Approve` | `Expense awaiting your approval` · `Dishwasher repair — €84.00 (charged to your payout once approved)` · `Approve` / **`Query`** | **MATCH** + **EXTRA** — ⚠️ the build adds the **consequence** (*"charged to your payout once approved"*) and a second action (`Query`), matching #68's real two-state workflow |
| 14 | Attention item `Add tax details` · `Unblocks your €10,370 payout` · `Action` | `Add tax details to release payout` · `Your payout is on hold until your tax details are on file.` · `Action` | **MATCH** — the **real** tax-hold, consistent with pages 2/5/9/27 |
| 15 | `+ 3 more` overflow indicator | **absent** | **DATA-DEPENDENT** — only 2 items exist, so there is no overflow to indicate |
| 16 | Quick actions `View calendar` · `Message guests` · `Download statement` · `Contact support` | **all four, identical** | **MATCH** |
| 17 | Hero label `THIS MONTH · SEPTEMBER 2025` | `THIS MONTH · SEPTEMBER 2026` | **MATCH** |
| 18 | Hero figure `€10,370.00` | `€885.60` | **MATCH** (real; reconciles with the statement, pages 8/23/27) |
| 19 | Hero delta `+8% vs August` | `+23% vs July` | **MATCH** — ⚠️ compared against **July**, the real prior statement month (August has none), rather than assuming a contiguous series |
| 20 | Hero sub `Next payout €10,370 · **est. 3 Nov 2025**` | `Next payout €885.60 · **Payout pending**` | **DIVERGENCE — RATIFIED refusal.** No estimated settlement date is invented — the same refusal as pages 2/24/26/27 and Artifacts A/C |
| 21 | KPI `TOTAL UNITS 3` | `2` | **MATCH** (real) |
| 22 | KPI `OCCUPANCY 78%` `+6%` | `3%` `−18%` | **MATCH** — ⚠️ the build shows a **real negative** delta rather than only flattering movement |
| 23 | KPI `EARNED YTD €84,200` | `€3,690.00` | **MATCH** — ⚠️ equals the archive lifetime net and the annual tax basis (pages 8/9/23) — **one figure, four surfaces** |
| 24 | `Recent statements` + `View all` | identical, → `/owner/documents` | **MATCH** + wired |
| 25 | Statement rows — month · `Seaside Villas · 3 units` · status · amount | 6 real rows — month · **real date range** (`1 Sep – 30 Sep 2026`) · `Payout pending` / `Paid` · real net | **MATCH** + **DIVERGENCE** (the build shows the real period instead of an operator/unit label) |
| 26 | 4 statement rows | 6 rows | **MATCH** (data — all real statements shown) |
| 27 | `Earnings` chart + range toggle `3M` / `6M` / `12M` | **identical toggle** | **MATCH** |
| 28 | Chart goal line `€9k goal` | `Goal €6,000.00 · **above this range**` | **MATCH** + **EXTRA** — ⚠️ the real `owner_monthly_target` (#56), and it says so when the goal sits off the plotted scale instead of drawing a line that isn't there |
| 29 | Month bars with per-month values (`APRIL 2025 €7,800` …) | 6 real months (`MAR €442.80` … `SEP €885.60`) | **MATCH** — ⚠️ **August is genuinely absent** (no statement), rather than plotted as a zero |
| 30 | `NEXT PAYOUT €10,370` · `Est. 3 Nov 2025` · `IBAN ···4821` | **`NEXT PAYOUT` `Paused`** · `Earnings are holding in your balance until your tax details are on file.` · `Add tax details` | **DIVERGENCE — honest, and the card's whole point.** The payout is really held, so the build shows the hold and its remedy instead of an amount and a date it cannot promise. ⚠️ The masked IBAN is CAP_PAYOUT-gated and surfaces on Payouts / Payout method / Balance (pages 2/5/24) |
| 31 | `MY UNITS` + per-unit occupancy (`Villa Azure 82%` …) | `A1 6%` · `A2 0%` | **MATCH** (real units, real occupancy) |
| 32 | `UPCOMING STAYS` + `Next 30 days` | identical | **MATCH** |
| 33 | Sort `Earliest` + `Filter` | both present | **MATCH** |
| 34 | Stay rows — guest name · amount · unit · nights · status · date | identical shape, real rows (`Demo Booker` · `A1 · 2 nights` · `Confirmed` · `18 Aug`) | **MATCH** — ⚠️ **name-only**, no contact detail (#28) |
| 35 | Every stay row carries an amount (`€1,550`, `€2,540` …) | two rows read **`Not yet attributed`** | **EXTRA — honest state.** The wireframe assumes every future stay already has a net; the build says so when it doesn't (same posture as pages 4/25/26) |
| 36 | 6 stay rows | 3 real rows | **MATCH** (data) |
| 37 | Footer note `Gross booking value` | `Net to you · €442.80` | **DIVERGENCE — build more precise.** On an owner surface the meaningful figure is **net to the owner**, not gross |
| 38 | `View all bookings` | present → `/owner/bookings` | **MATCH** + wired |
| 39 | `Download statements` | present → the real statement route | **MATCH** + wired |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ real name, 2 real units, 6 real statements, 3 real upcoming stays, the real €84.00 pending expense, the real tax-hold |
| Figures reconcile | ✅ `EARNED YTD €3,690.00` equals the archive lifetime net (page 23) and the annual tax basis (pages 8/9) |
| Hero delta basis | ✅ `+23% vs July` — the real prior statement month, not an assumed contiguous one |
| Chart | ✅ 6 real months against the real `owner_monthly_target`; the monthless August is omitted, not zeroed |
| `View all` / `View all bookings` / `Download statements` / quick actions | ✅ resolve to real owner routes |
| `Approve` / `Query` / `Action` | ⚠️ **unverified-without-mutation** — real POSTs that move money or release a hold |
| PII posture | ✅ guest **name only** on every stay row (#28) |

### Verdict — page 1

**0 frontend gaps · 0 backend gaps.** 27 MATCH · 6 divergences (1 ratified refusal, 5 copy/naming/honest) ·
6 enhancements · 2 data-dependent · 1 unverified group.

⚠️ **Item 30 is the page's best moment.** The wireframe's `NEXT PAYOUT` card shows an amount, an estimated
date and an IBAN. The build's payout is genuinely **held** for missing tax details — so it shows `Paused`,
explains why, and offers the fix. Printing `€885.60 · Est. 3 Nov` over a payout that cannot move would be
the most consequential lie on the dashboard, because it is the number an owner plans around.

⚠️ **Item 29 is the quiet one.** August has no statement, so the earnings chart simply **has no August bar**
— rather than a €0 column implying a month that earned nothing. The distinction between *no data* and *zero*
runs through this whole build, and it starts on the first screen.

---
## Page 2 — `/owner/payouts` vs `Owner Payouts.html` — **DONE (browser-diffed)**

Both sides opened in Playwright and read from the hydrated DOM: wireframe **2,381 chars**, live **2,042 chars**.

| # | Wireframe element | Build | Verdict |
|---|---|---|---|
| 1 | Hold banner — *"September payout is on hold / **Verification pending**"* | *"Your €885.60 payout is on hold / We're holding this payout until your **tax details** are on file."* | **DIVERGENCE — RATIFIED (honest refusal).** #58: there is no verification column, so a "verifying your account details" state would be fabricated. The build shows the one hold it can prove — the real tax-hold |
| 2 | `Complete verification` CTA | `Add tax details` CTA | **DIVERGENCE — same, follows #1** (the CTA matches the real hold reason) |
| 3 | *"Held since 3 Nov · est. release 7 Nov 2025"* | absent | **DIVERGENCE — RATIFIED.** An estimated release date is unknowable without the rail; #58 refused to invent one (#39) |
| 4 | `View held funds` link | absent (Balance page carries held funds) | **DIVERGENCE — placement** |
| 5 | KPI `PAID THIS YEAR` | `€2,804.40 · 5 payouts · 2026` | **MATCH** (+ EXTRA sub-line) |
| 6 | KPI `NEXT PAYOUT` | `€885.60 · Awaiting disbursement` | **MATCH** |
| 7 | KPI `PENDING / On hold` | `On hold €885.60 · 1 payout` | **MATCH** |
| 8 | `Payout history` + summary line | `€2,804.40 paid across 5 payouts · €885.60 pending` | **MATCH** |
| 9 | `Export` control | present | **MATCH** (real CSV route) |
| 10 | Filter: `2025` / `Status` | `All / Paid / Pending` | **DIVERGENCE — minor** (status filter present; year selector not) |
| 11 | History table: month · rail · date · status · amount | all five columns present, 6 real rows | **MATCH** |
| 12 | Row links `Statement` + `Receipt` | `Statement` + **`Remittance`** | **DIVERGENCE — naming** (build's remittance PDF is real and routed) |
| 13 | `NEXT PAYOUT` card + IBAN | `NEXT PAYOUT · SEPTEMBER 2026` + `IBAN ···· 1000` | **MATCH** |
| 14 | `Gross revenue` | `Gross room revenue` | **DIVERGENCE — naming** (build is more precise) |
| 15 | `Management fee · 18%` | `Management commission −€194.40` | **MATCH** (rate lives in the agreement, #62) |
| 16 | `Payment & ops fees −€193.23` | absent | **DATA-DEPENDENT, not missing.** #70 built the `payment_fee` bucket; no writer posts fees yet, so it is a genuine €0 and the line is omitted rather than faked |
| 17 | `Net payout` | `Net payable €885.60` | **MATCH** (naming) |
| 18 | Lifecycle chips `Finalized / Scheduled / On hold / Paid` | `Finalized / Pending / Paid` | **DIVERGENCE — minor** (3 states vs 4) |
| 19 | `UPCOMING SCHEDULE` — 3 rows, `~€9,850 Projected` ×2 | `NEXT SCHEDULED PAYOUT · 3 Sep 2026 · Monthly` only | **DIVERGENCE — RATIFIED (honest refusal).** The wireframe's `~€9,850` projections are invented figures; #39 forbids them. The build gives the one real date the schedule can compute |
| 20 | `PAYOUT ACCOUNT` + `Manage` + `Revolut Business` + IBAN | all present (`IBAN ···· 1000 · LT`) | **MATCH** |
| 21 | `Default` badge on the account | absent | **DIVERGENCE — minor** |
| 22 | `PAYOUT PREFERENCES` + `Active` | present | **MATCH** |
| 23 | `Frequency` Monthly/Twice monthly/Weekly | identical | **MATCH** |
| 24 | `Payout day` `1st / 3rd / 15th / Last` | `1st / 2nd / 3rd / 4th / 5th` **(business day of month)** | **DIVERGENCE — build is stricter**; business-day semantics are what `PayoutSchedule` actually implements |
| 25 | `Minimum payout` None/€500/€1k/€2.5k | identical | **MATCH** |
| 26 | `Payout currency` EUR/GBP/USD selectable | `Set by your settlement region · EUR` | **DIVERGENCE — RATIFIED (honest).** No FX is modelled; offering GBP/USD would imply conversion that does not exist |
| 27 | `Pause payouts` + hold-in-balance copy | identical | **MATCH** |
| 28 | `All changes saved` (autosave) + `Reset to default` | `Save preferences` (explicit) | **DIVERGENCE — interaction model** |

**EXTRA IN BUILD (enhancements — welcome, not defects):**
`Test mode` banner (*"no real funds move… real transfers activate when your account goes live"* — the
honest payout-engine state) · `OTA commission +€0.00` line in the breakdown · `View statement →` link ·
*"The automated engine is not yet switched on — this is the date it will use once enabled"* ·
nav items absent from the wireframe: Bookings, Add a unit, Payout method, Documents, Notifications, Profile.

**Backend wiring:** real seeded data throughout (5 paid payouts + 1 pending, totals reconciling to €2,804.40
/ €885.60); Export → real CSV route; Statement + Remittance → real PDF routes; the hold banner reflects the
**real** tax-hold. Preferences form is a real POST — **unverified-without-mutation** (money-as-seeded).

**Verdict:** **0 frontend gaps · 0 backend gaps.** 12 MATCH · 12 DIVERGENCE (5 ratified/honest-refusal,
7 minor/naming) · 1 data-dependent · 6 enhancements. Every divergence is the build being *more* honest than
the prototype (no invented verification state, release date, projections, or FX).

---
## INTERIM ROLL-UP — scoped strictly to the DONE set

**28 of 28 pages browser-diffed — COMPLETE** — `/owner` · `/owner/payouts` · `/owner/units` · `/owner/bookings` ·
`/owner/balance` · `/owner/expenses` · `/owner/calendar` · `/owner/documents` · `/owner/tax` ·
`/owner/reports` · `/owner/insights` · `/owner/rates` · `/owner/reviews` · `/owner/messages` ·
`/owner/notifications` · `/owner/notification-preferences` · `/owner/settings` · `/owner/security` ·
`/owner/team` · `/owner/agreement` · `/owner/help` · `/owner/welcome` · `/owner/add-unit` · `/owner/archive` ·
`/owner/payout-method` · `/owner/units/{id}` · `/owner/bookings/{id}` · `/owner/statements/{id}`. **0 REMAIN.**

✅ **All 28 pages were browser-diffed. See the FINAL ROLL-UP at the end of this document** — the interim
figures in this section are superseded by it.

| Bucket | Across the 25 DONE pages |
|---|---|
| **FRONTEND gaps** | **6 candidates** (1 `/owner/units` — `Approve all`; 1 `/owner/expenses`; 1 `/owner/tax`; **1 `/owner/insights` — confirmed capability-absence**; 1 `/owner/messages` — `Copy text`; **1 `/owner/settings` — 3 owner notification types**). ✅ **One was RESOLVED on page 19** — the `/owner/units` agreement-renewal `Review & e-sign` item is a documented refusal, not an oversight · plus 1 unratified product call (`PEAK` column, `/owner/rates`) · **plus 1 HONESTY FLAG (`/owner/reviews`) + 1 minor residual claim (`/owner/help` item 23)** |
| **BACKEND gaps** | **0 found** across all 25 |
| **DIVERGENCES** | 180 — **77 ratified/honest-refusal**, 102 minor/naming/copy/data/placement/hand-off, **1 UNINTENTIONAL (the reviews honesty flag)**. ⚠️ **1 AUDITOR ERROR corrected** — page 10's ZIP verdict (see the correction section) |
| **UNVERIFIED** | 6 — the calendar's composer reason buttons; the Documents export-gated + empty states; the Tax `Save tax details` POST (would release held payouts); Reports `Email to me`; Reports export-gating |
| **ENHANCEMENTS** | 125+ recorded, none flagged |

### ⚠️ HONESTY FLAG — `/owner/reviews` (the only one found in 14 pages)

`reviews.blade.php:188` renders *"…is your softest score — the proposed **rate changes** factor this in."*
The `Value` label is really derived; the rest is **static copy inherited from the prototype** and asserts
(a) that proposed rate changes exist — `rate_proposals` has **0 rows** — and (b) that they factor review
scores in — **no linkage exists**; `RateProposalService`/`OwnerRateService` reference reviews nowhere.
Not fabricated *data*; fabricated *causation*. Cheap to fix (drop the clause, or gate it on a real pending
proposal exactly as `insights.blade.php:195` already does).

### Candidate FRONTEND gaps (prioritized by user impact)

1. **`Approve all` bulk control** (`/owner/units`) — absent while `Snooze all` + `Mark all read` are present.
   Bulk-approving expenses/rate proposals is a **money action**, so the omission may be deliberate — but it
   is **unratified**, so reported not excused.
2. **Agreement-renewal attention item** (`/owner/units`) — `renews in 14 days` / `Review & e-sign`; no e-sign
   is modelled, so the CTA would be unbacked. Likely honest; unratified.
3. **Expense photos** (`/owner/expenses`) — a `N photos` badge per approval item. Polymorphic `media` exists
   but no photo affordance renders. Lowest impact.

*All three need one decision: build, or ratify the omission in the register.*

### The pattern, now across 7 pages

Every non-cosmetic divergence is the **build refusing to fabricate** — no auto-approve timer, no verification
state, no estimated release date, no forward payout projections, no FX, no €0 fee line, no OTA brand, no
withdrawal-speed promise — or the **build being richer than the prototype**: honest states the wireframes lack
(`Pending review`, `Not deductible`, `Not yet on a statement`, `Not yet attributed`, `Unverified`,
`Prior-period adjustment`, `Test mode`), running balances, real filter counts, reconciling totals, and on the
calendar a **full accessible label per day plus a keyboard range-selection path** the prototype has no concept of.

⚠️ Seven pages in, **not one wireframe element has proven genuinely absent for want of capability** — the
three candidates above are all plausible deliberate omissions awaiting ratification.

## HOW TO RESUME

1. `php artisan serve --port=8000` and `(cd docs/design/raw && python3 -m http.server 8099)`.
2. Log in at `/login` as `demo-owner@vyrobel-demo.test` / `password` → lands on `/owner`.
3. Take the next **REMAINING** row from the ledger; open the wireframe at
   `http://127.0.0.1:8099/<Wireframe>.html` and the live page at `http://127.0.0.1:8000/<route>`;
   read `document.body.innerText` from each **after hydration**.
4. Diff element-by-element, append the page section, and flip that ledger row to **DONE**.

⚠️ **Do not mark a row DONE without both renders.** The whole point of this pass is that a curl/200 check
is not a diff.

**Cost note for planning:** a page costs ~4 browser calls (2 navigate + 2 read) plus one write; the diff
table itself is the expensive part. Budget roughly 5–6 pages per run.
## Page 3 — `/owner/units` vs `Owner My Units.html` — **DONE (browser-diffed)**

Both sides opened in Playwright, read from the hydrated DOM: wireframe **1,919 chars**, live **1,380 chars**.
⚠️ The wireframe threw 51 console errors while rendering but **did produce full copy** — the two-sided probe
passed on content, so absences below are real, not a failed prototype.

| # | Wireframe element | Build | Verdict |
|---|---|---|---|
| 1 | Page title `My units` | `My units` | **MATCH** |
| 2 | `Add unit` CTA | `Add unit` | **MATCH** (routes to `/owner/add-unit`) |
| 3 | KPI `UNITS` | `UNITS 2` | **MATCH** |
| 4 | KPI `AVG OCCUPANCY` + delta | `AVG OCCUPANCY 3% −18% · August 2026` | **MATCH** (+ EXTRA period label) |
| 5 | KPI `EARNED THIS MONTH` | `−€180.00 · Net · August 2026` | **MATCH** (+ EXTRA "Net" qualifier — see wiring note) |
| 6 | `Needs your attention` + count | `Needs your attention 2` | **MATCH** |
| 7 | `N items need you — tap to review` | identical phrasing | **MATCH** |
| 8 | `Expand` toggle | `Collapse` (already expanded) | **MATCH** (same control, opposite state) |
| 9 | Tabs `Needs attention` / `Snoozed` | `All` / `Action` / `Review` | **DIVERGENCE — filter shape** (build filters by kind, not snooze-state) |
| 10 | Filter `All units` | absent on the attention feed | **DIVERGENCE — minor** (build's feed is org-wide, not per-unit) |
| 11 | Filter `All statuses` | `All / Action / Review` covers it | **DIVERGENCE — naming** |
| 12 | `Approve all` bulk control | absent | ⚠️ **MISSING — FRONTEND (candidate).** `Snooze all` and `Mark all read` are both present, so the bulk row exists; only *Approve all* is absent. Plausibly deliberate — bulk-approving money (expenses/rate proposals) without per-item review is a money action — but **no ratified entry was found for it**, so it is flagged rather than excused |
| 13 | `Snooze all` | present | **MATCH** |
| 14 | `Mark all read` | present | **MATCH** |
| 15 | Attention item: rate-change proposal + `Decline`/`Approve` | not shown (no pending proposal seeded) | **DATA-DEPENDENT** — the real rate-proposal item exists (Wave C, #67); the demo has none pending |
| 16 | Attention item: maintenance expense + `Decline`/`Approve` | `Expense awaiting your approval · Dishwasher repair — €84.00 (charged to your payout once approved)` + `Approve` / `Query` | **MATCH** (build's `Query` vs `Decline` = naming; #68's real two-state workflow) |
| 17 | Attention item: `Add tax details to release payout` | present, with real hold copy | **MATCH** |
| 18 | Attention item: `Confirm your payout bank account` / `Verify account` | absent | **DIVERGENCE — RATIFIED (honest refusal).** #58: no verification column exists, so a "verify account" task would be fabricated |
| 19 | Attention item: `Management agreement renews in 14 days` / `Review & e-sign` | absent | ⚠️ **MISSING — FRONTEND (candidate).** An agreement page exists (`/owner/agreement`) but no renewal-countdown attention item; **no e-sign capability is modelled**, so the CTA would be unbacked — likely an honest omission, but unratified, so flagged |
| 20 | Attention item: `Owner stay confirmed` / `View` | absent | **DATA-DEPENDENT** — owner blocks are real (#60); none seeded in this window |
| 21 | `Your units` heading | `Your units` | **MATCH** |
| 22 | `Sort: Revenue` control | absent | **DIVERGENCE — minor** (2 units; no sort offered) |
| 23 | Unit filter pills (`All units` / per-unit) | absent | **DIVERGENCE — minor** |
| 24 | Unit card: name + type + property | `A1 · One-Bedroom Apartment` | **MATCH** (no property line — single property) |
| 25 | Unit card: `Active` status | `Active` | **MATCH** |
| 26 | Unit card KPIs: `OCCUPANCY` / `Nights sold` / `Revenue` / `Nightly rate` | all four present | **MATCH** |
| 27 | Unit card CTAs: `Edit pricing` / `Message guest` / `View unit` | all three present | **MATCH** |
| 28 | `Add a unit` card + `List a new property` | present | **MATCH** |
| 29 | Month calendar + `Block dates` + `Export` | present, real August 2026 grid | **MATCH** |
| 30 | Calendar filter `All units` | absent | **DIVERGENCE — minor** |
| 31 | `BOOKED 0/1/2/3 of N units` legend | `BOOKED 0 1 2 of 2 units` | **MATCH** (scales to real unit count) |
| 32 | Calendar summary `67 nights · 72% occupancy · €10,850` | `2 nights · 3% occupancy · August 2026` | **DIVERGENCE — the build omits the € total here** and shows the period instead |
| 33 | `BEST PERFORMER` card | `BEST PERFORMER A1 · 6% occupancy · 2 nights · €280.00` | **MATCH** |

**EXTRA IN BUILD (enhancements — welcome):** `★ Best` badge on the winning unit card · **`Pause`** control per
unit (the real #69 pause/resume that recomputes availability) · `· ADR €140.00` beside the nightly rate ·
`Open Expenses` link on the expense item · period labels on the KPIs (`August 2026`) · nav items absent from
the wireframe (Bookings, Add a unit, Payout method, Documents, Notifications, Profile).

**Backend wiring:** real seeded data (2 units, real occupancy/nights/revenue, a real €84.00 expense awaiting
approval, the real tax-hold). `Edit pricing` opens the real per-unit rate form; `View unit` → `/owner/units/{id}`;
`Block dates` → the real calendar; `Export` → real CSV. Approve/Query/Snooze are real POSTs —
**unverified-without-mutation** (money-as-seeded). ⚠️ The negative `EARNED THIS MONTH −€180.00` is *correct*:
August's ledger nets negative after the commission/expense lines — the build shows the real figure rather than
flooring it at zero.

**Verdict:** **2 candidate frontend gaps** (`Approve all`; agreement-renewal attention item) · **0 backend gaps**.
17 MATCH · 9 DIVERGENCE (1 ratified/honest-refusal, 8 minor/naming) · 3 data-dependent · 6 enhancements.

---
## Page 4 — `/owner/bookings` vs `Owner Bookings.html` — **DONE (browser-diffed)**

Both sides opened in Playwright, hydrated DOM read: wireframe **1,163 chars**, live **1,611 chars**.

| # | Wireframe element | Build | Verdict |
|---|---|---|---|
| 1 | Breadcrumb `Overview / Bookings` | `‹ Overview / Bookings` | **MATCH** |
| 2 | Title `Bookings` | `Bookings` | **MATCH** |
| 3 | Sub-line `14 reservations · 3 units` | `23 reservations · 2 units` | **MATCH** (real counts) |
| 4 | KPI `UPCOMING` | `UPCOMING 8` | **MATCH** |
| 5 | KPI `GUESTS` | `GUESTS 8` | **MATCH** |
| 6 | KPI `NIGHTS` | `NIGHTS 64` | **MATCH** |
| 7 | KPI `NET TO YOU` | `€4,903.60` | **MATCH** |
| 8 | `Bookings over time` + sub-copy | identical copy | **MATCH** |
| 9 | Chart legend `Confirmed` / `Pending` | `Confirmed 11` / `Pending 0` / **`Other 6`** | **MATCH** + EXTRA third series |
| 10 | `Net revenue` series | present with per-month values | **MATCH** |
| 11 | `€6k /mo goal` line | `€6k /mo goal` | **MATCH** (#56's real owner-set target) |
| 12 | Month axis | 8 real months `May ’26 … Dec ’26` | **MATCH** |
| 13 | `Monthly goal` ring + `% OF GOAL` | `Monthly goal 0% OF GOAL` | **MATCH** |
| 14 | Goal detail `Oct '25 net €7.4k of €6k · +€1.4k over` | `Aug ’26 net €0 of €6k · €6k to go` | **MATCH** (the under-target wording is the honest inverse) |
| 15 | `Target €6k /mo` | identical | **MATCH** |
| 16 | Tabs `Upcoming` / `Past` / `All` | identical | **MATCH** |
| 17 | Filter `All sources` | `All sources` + **real counts** `Corporate (2) · Direct (17) · OTA (3) · Walk-in (1)` | **MATCH** + EXTRA counts |
| 18 | Filter `All statuses` | `All statuses` + `Completed (6) · Confirmed (17)` | **MATCH** + EXTRA counts |
| 19 | Filter `All units` | `All units · A1 · A2` | **MATCH** |
| 20 | Filter `Any dates` | `Any dates` | **MATCH** |
| 21 | `Export` | present | **MATCH** (real CSV, name-only per #28) |
| 22 | Column `BOOKING` (guest + ref) | guest name + real reference | **MATCH** |
| 23 | Columns `UNIT` / `DATES` (sortable ↑) / `NTS` / `SOURCE` / `STATUS` | all present, sort arrow present | **MATCH** |
| 24 | Column `NET` | `NET TO YOU` | **DIVERGENCE — naming** (build is clearer) |
| 25 | Source values incl. **`Booking.com` / `Airbnb`** | `Direct / Corporate / OTA / Walk-in` | **DIVERGENCE — RATIFIED.** The OTA *brand* is not modelled on any table; #87/#39 forbid inventing it. The build shows the real `source` enum |
| 26 | Status values `Confirmed` / `Pending` | `Confirmed` / `Completed` | **MATCH** (real status enum) |
| 27 | Footer `Showing 1–6 of 6 upcoming bookings` | `Showing 1–8 of 8 bookings · upcoming.` | **MATCH** |
| 28 | `Back to overview` | present | **MATCH** |

**EXTRA IN BUILD (enhancements):** per-filter **real counts** on every source/status option · a third chart
series (`Other`) · **`Not yet attributed`** in the NET column where a stay has no owner-ledger attribution yet —
an honest state the wireframe has no concept of (it shows a number for every row).

**Backend wiring:** real seeded data throughout (23 reservations, 8 upcoming, real references, real nets
reconciling to €4,903.60 via `OwnerLedgerAttribution`); filters carry real counts; `Export` → real CSV route;
rows link to `/owner/bookings/{id}` (verified 200 in the prior pass). Guest column is **name-only** — correct
per #28. Goal ring reads the real `owner_monthly_target`.

**Verdict:** **0 frontend gaps · 0 backend gaps.** 25 MATCH · 2 DIVERGENCE (1 ratified brand-refusal,
1 naming) · 3 enhancements. ⚠️ The `Not yet attributed` state is a genuine honesty improvement over the
prototype, which assumes every booking already has a net.

---
## Page 5 — `/owner/balance` vs `Owner Balance.html` — **DONE (browser-diffed)**

Both sides opened in Playwright, hydrated DOM read: wireframe **1,526 chars**, live **4,677 chars**
(head + tail both read, so the trailing cards were checked rather than assumed).

| # | Wireframe element | Build | Verdict |
|---|---|---|---|
| 1 | Title `Balance & withdrawals` | identical | **MATCH** |
| 2 | Sub-copy *"What's cleared, what's held, and where every euro is on its way to."* | **identical, word for word** | **MATCH** |
| 3 | KPI `AVAILABLE` + `cleared & withdrawable` | `€963.60` + `cleared this period · EUR` | **MATCH** (+ EXTRA currency qualifier) |
| 4 | KPI `ON HOLD` + `September · tax details needed` | `€885.60` + `tax details needed` | **MATCH** |
| 5 | KPI `IN TRANSIT` + `nothing in transit` | `€0.00` + `nothing in transit` | **MATCH** |
| 6 | `Withdraw early` card | present | **MATCH** |
| 7 | Withdraw copy *"…paid out automatically on the 5th — or take it now. **Fee-free, arrives in 1–2 business days**"* | *"…automatically on your monthly schedule. **Instant self-serve withdrawal activates with the live payments connection.**"* | **DIVERGENCE — RATIFIED (honest refusal).** #59: there is no owner-initiated transfer rail, so "arrives in 1–2 business days" would be a promise nothing can keep |
| 8 | `to IBAN ···4821` | `to IBAN ···· 1000` | **MATCH** (real masked IBAN, CAP_PAYOUT-gated) |
| 9 | `Withdraw €2,340` button | `Withdraw €963.60` — **rendered but disabled** | **DIVERGENCE — RATIFIED.** #59 keeps the control visible and honestly inert rather than faking a withdrawal |
| 10 | `Balance activity` + `Every movement, newest first` | identical | **MATCH** |
| 11 | `Export` | present | **MATCH** (real CSV route) |
| 12 | Activity row: label + date/context + amount | present, with **columns `DATE · ACTIVITY · AMOUNT · BALANCE`** | **MATCH** + EXTRA running-balance column |
| 13 | Row state chips `Available` / `On hold` / `Settled` / `Paid` / `Paid out` | `Available` / `On hold` / `Paid out` | **DIVERGENCE — minor** (3 real states vs 5; `Settled`/`Paid` are not distinct in the ledger) |
| 14 | Row: *"October stays cleared so far · 4 bookings · 3 villas"* (aggregated) | per-entry rows (`Room revenue · 3 night(s)`, `Management commission 18%`, `OTA commission`, `Expense — …`) | **DIVERGENCE — granularity.** The build shows the **real ledger lines**; the wireframe shows a friendlier roll-up. ⚠️ Direction: the build is more truthful but noisier — a genuine UX question worth a decision |
| 15 | Row: `Guest refund · booking VYR-2219 · −€450` | no refund row (none seeded) | **DATA-DEPENDENT** |
| 16 | Row: `August payout · IBAN ···4821 · −€9,214 · Paid` | payout rows present as `Paid out` | **MATCH** |
| 17 | `Why is €10,370 held?` card | `Why is €885.60 held?` — **same card, real figure** | **MATCH** |
| 18 | Held copy *"…until a valid tax ID is on file — a legal requirement before we can transfer rental income."* | **identical wording** | **MATCH** |
| 19 | `Add tax details` CTA | present | **MATCH** (routes to the real tax form) |
| 20 | `Auto-payout schedule` + `Monthly · the 5th` + `next run 5 Nov` | `AUTO-PAYOUT SCHEDULE` + `Monthly` + **`Payouts →`** | **DIVERGENCE — minor.** The build omits the specific day/next-run here (it lives on `/owner/payouts`, where #58 computes the real `nextRunDate`) and links across instead |
| 21 | Schedule copy *"Everything cleared by month-end goes out automatically — no action needed. See the full history on Payouts."* | *"Everything cleared by the schedule goes out automatically — no action needed."* + the `Payouts →` link | **MATCH** (same meaning, link instead of prose) |
| 22 | `How money clears` card | present | **MATCH** |
| 23 | How-clears copy *"Guest payments clear once the stay begins and the statement is finalised. Refunds and approved expenses are netted before anything reaches your balance."* | **identical, word for word** | **MATCH** |

**EXTRA IN BUILD (enhancements):** `Test mode` banner · a **running `BALANCE` column** on every activity row ·
per-row provenance (`on a statement` / `not yet on a statement`) · **`Unverified`** tag on OTA-cost lines
(*"pending channel reconciliation"*) · **`Prior-period adjustment`** tag on late expenses · full 12-month
history rather than 5 sample rows.

**Backend wiring:** the clearing model is real and reconciles — Available €963.60 + On hold €885.60 +
In transit €0.00 against a running balance that walks from €540.00 (6 Mar) to €4,653.60 (18 Oct) across every
real ledger line. The held card renders **only** because a real tax-hold exists. `Export` → real CSV;
`Add tax details` → real form; `Payouts →` → real route. `Withdraw` is deliberately inert
(**unverified-without-mutation** is not even applicable — there is no rail to exercise).

**Verdict:** **0 frontend gaps · 0 backend gaps.** 16 MATCH · 5 DIVERGENCE (2 ratified/honest-refusal,
3 minor/granularity) · 1 data-dependent · 6 enhancements. ⚠️ Item 14 (ledger lines vs friendly roll-up) is
the one worth a product decision — it is not a defect, but the wireframe's aggregated row is easier to read.

---
## Page 6 — `/owner/expenses` vs `Owner Expenses.html` — **DONE (browser-diffed)**

Both sides opened in Playwright, hydrated DOM read: wireframe **2,243 chars**, live **2,459 chars**.

| # | Wireframe element | Build | Verdict |
|---|---|---|---|
| 1 | Title `Expenses & maintenance` | identical | **MATCH** |
| 2 | Sub-copy *"Review the work carried out on your villas and what's charged to your payouts."* | *"Every cost on your units — what's charged to you, what's the operator's, and what's still under review."* | **DIVERGENCE — copy.** ⚠️ Direction: the build's line is **more informative** — it names the three-way split the page actually implements (yours / operator's / under review), which the wireframe has no concept of |
| 3 | KPI `SPENT THIS YEAR` + `across 3 villas` | `€709.00` + `5 expenses in 2026` | **MATCH** |
| 4 | KPI `AWAITING YOUR APPROVAL` + `3 items to review` | `€84.00` + `1 to approve · **not charged until you do**` | **MATCH** (+ EXTRA reassurance that matches #68's real model) |
| 5 | KPI `CHARGED TO PAYOUTS` + `deducted year-to-date` | `€250.00` + `Approved & deducted year-to-date` | **MATCH** |
| 6 | `Needs your approval` + open count | `Needs your approval` (count in the KPI) | **MATCH** |
| 7 | ⚠️ *"Approve within 7 days or these are **auto-approved** per your management agreement."* | *"These deductible costs are **not charged to your payout until you approve them**. Approve to deduct, or query your team."* | **DIVERGENCE — RATIFIED (honest refusal).** #68 explicitly declined to invent an auto-approve timer; the build's copy states the real rule instead. ⚠️ This is the build refusing a claim the prototype makes |
| 8 | Approval item: title + amount | `Dishwasher repair €84.00` | **MATCH** |
| 9 | Approval item meta: date · category · unit · vendor | `5 Oct 2026 · Maintenance · A1 · FixIt Appliances` — all four | **MATCH** |
| 10 | Approval item: description paragraph | absent (meta line only) | **DIVERGENCE — minor** (no long-form note stored per expense) |
| 11 | Approval item: `Approve` / `Query` | both present | **MATCH** (#68's real two-state workflow) |
| 12 | Approval item: `N photos` badge | absent | ⚠️ **MISSING — FRONTEND (candidate).** Polymorphic `media` exists in the codebase, but no photo affordance renders on an owner expense. Unratified → flagged. *Plausibly deliberate (no expense media is captured today), but it is not recorded anywhere* |
| 13 | — | **`Pending review`** section + *"Deductibility … still being resolved … nothing for you to do"* | **EXTRA** — an honest third state the wireframe lacks |
| 14 | `Expense history` + `7 entries this year` | `Expense history` (count omitted here) | **DIVERGENCE — minor** |
| 15 | `Export` | present | **MATCH** (real CSV) |
| 16 | Unit filter `All units` + per-unit | `All units · A1 · A2` | **MATCH** |
| 17 | — | **Category filter** `All · Cleaning · Maintenance · Other` | **EXTRA** |
| 18 | Columns `DATE · DESCRIPTION · VILLA · AMOUNT · STATUS` | `DATE · DESCRIPTION · **VENDOR** · UNIT · AMOUNT · STATUS` | **MATCH** + EXTRA vendor column (Wave A) |
| 19 | Row status values — all `Charged` | `Deductible` / `Not deductible` / `Pending review`, each with a **provenance sub-line** (`Not yet on a statement`, `No management agreement covers this date`) | **DIVERGENCE — the build is richer and more truthful.** The wireframe shows one flat status; the build shows who bears the cost and whether it has reached a statement |
| 20 | `By category` + `This year, all villas` | `BY CATEGORY` with per-category **count + % of total** | **MATCH** + EXTRA |
| 21 | Category rows `Maintenance / Cleaning / Repairs / Supplies` | `Cleaning / Maintenance / Other` | **DIVERGENCE — taxonomy.** The build uses its **real** category enum; `Repairs`/`Supplies` are not modelled (they fall under Maintenance/Other) |
| 22 | — | **`Total €829.00`** + *"Totals exclude voided expenses."* | **EXTRA** — a reconciling total the wireframe omits |
| 23 | `How expenses work` + copy | `HOW EXPENSES WORK` — expanded to explain deductible / non-deductible / pending, and *"set by your management agreement — not by you"* | **MATCH** (build's copy is fuller and states who decides) |
| 24 | `View payouts` link | absent from this card | **DIVERGENCE — minor** (Payouts reachable from nav) |
| 25 | `Need something fixed?` + copy | `NEED SOMETHING FIXED?` + *"…they'll raise the work order, and the cost appears here once it's resolved."* | **MATCH** |
| 26 | `Request maintenance` CTA | `Request a repair` | **MATCH** (naming; real relay into the owner↔operator channel, #59) |

**EXTRA IN BUILD (enhancements):** the `Pending review` section · category filter · vendor column · deductible
tri-state with provenance sub-lines · per-category counts and % of total · a reconciling `Total` with a
voided-exclusion note · "not charged until you do" reassurance.

**Backend wiring:** real seeded data (6 expenses, real vendors, the real €84.00 pending approval, the real
€120.00 pre-agreement item). Approve/Query are real POSTs — **unverified-without-mutation** (money-as-seeded).
`Export` → real CSV; `Request a repair` → the real owner↔operator relay. ⚠️ The category totals (€829.00) are
computed over all non-voided expenses, while `SPENT THIS YEAR` (€709.00) is 2026-scoped — **different scopes,
both labelled**, so not a reconciliation error.

**Verdict:** **1 candidate frontend gap** (expense photos) · **0 backend gaps.** 14 MATCH · 7 DIVERGENCE
(1 ratified/honest-refusal, 6 minor/copy/taxonomy) · 7 enhancements. ⚠️ Item 7 is the notable one: the
prototype promises **auto-approval after 7 days**; the build refuses to invent that timer and says what
actually happens.

---
## Page 7 — `/owner/calendar` vs `Owner Block Calendar.html` — **DONE (browser-diffed)**

Both sides opened in Playwright, hydrated DOM read: wireframe **952 chars**, live **1,358 chars**.
(The bare day-number grid was stripped from both reads so the diff compares structure, not `1…31`.)

| # | Wireframe element | Build | Verdict |
|---|---|---|---|
| 1 | Title `Block calendar` | identical | **MATCH** |
| 2 | Sub-copy *"Hold dates for personal stays, maintenance or seasonal closures — blocked nights won't accept guest bookings."* | **identical, word for word** | **MATCH** |
| 3 | `UNIT` selector + per-unit options | `UNIT · A1 · A2` | **MATCH** (real units) |
| 4 | Month heading | `August 2026` | **MATCH** |
| 5 | `Today` control | present | **MATCH** |
| 6 | Legend `Booked` | present | **MATCH** |
| 7 | Legend `Owner block` | present | **MATCH** |
| 8 | Legend `Selecting` | present | **MATCH** |
| 9 | Legend `Open` | present | **MATCH** |
| 10 | Month summary `2 nights blocked this month` | `0 held · 2 booked · 13 open` | **DIVERGENCE — the build is richer**; it reports all three states rather than blocked-only |
| 11 | Weekday header row `MON…SUN` | identical | **MATCH** |
| 12 | Booked cells labelled with the **guest surname** (`Rossi`, `Aalto`) | booked cells labelled `Demo Booker` | **MATCH** — name-only, correct per #28 (no contact PII on an owner surface) |
| 13 | Day cells carry state | each cell carries a full accessible label: `18 August 2026 — booked`, `… — past` | **MATCH** + EXTRA (see below) |
| 14 | Interaction copy *"Click a day, or press and drag across several, to block them. Click a blocked day to release it."* | same, **plus** *"By keyboard, press Enter on a day to start, then on a second day to extend the range. Activate a blocked day to release it."* | **MATCH** + EXTRA keyboard path |
| 15 | `Block dates` composer heading | present | **MATCH** |
| 16 | Composer empty state *"Select one or more open days on the calendar to create a block."* | **identical** | **MATCH** |
| 17 | Composer reason buttons (`Personal stay` / `Maintenance` / `Seasonal hold`) | ⚠️ not rendered in the empty state — the composer reveals them once a range is selected | **UNVERIFIED-WITHOUT-INTERACTION**, *not* missing. #60 documents the reason buttons + note + Repeat section; confirming them needs a click-drag that would create a real block (money/inventory state), so it was not exercised |
| 18 | `Scheduled blocks` + count | `Scheduled blocks · 0 blocks` | **MATCH** |
| 19 | Block rows (unit · dates · reason · nights · note) | `A1 · No upcoming blocks for this unit.` | **DATA-DEPENDENT** — the demo seeds no owner blocks; the **honest empty state** renders instead |
| 20 | `Remove` per scheduled block | not shown (no blocks exist) | **DATA-DEPENDENT** — #60 implements release; nothing to release here |

**EXTRA IN BUILD (enhancements):** a **full accessible label on every day cell** (`18 August 2026 — booked`) —
the wireframe's cells carry only a number and a colour, so this is a real a11y improvement · **past-day
marking** (`— past`), which the wireframe has no concept of · the **keyboard range-selection** path ·
the three-way month summary.

**Backend wiring:** real seeded state (2 booked nights on A1 from the real reservation, 13 open, 0 held);
the unit selector switches a real per-unit view; the `Scheduled blocks` panel renders a real empty state
rather than a placeholder. Block creation/release are real POSTs through `OwnerBlockService` →
`AssignmentService` — **deliberately not exercised** (they would write inventory; money-as-seeded).

**Verdict:** **0 frontend gaps · 0 backend gaps.** 15 MATCH · 1 DIVERGENCE (build richer) · 2 data-dependent ·
**1 UNVERIFIED-WITHOUT-INTERACTION** (composer reason buttons) · 4 enhancements. ⚠️ Item 17 is explicitly
*not* a gap claim: the elements are documented as built (#60) and their absence from an **empty** composer is
expected — proving them needs a mutation this audit refuses to make.

---


---

## Page 8 — `/owner/documents` vs `Owner Documents.html` — **DONE (browser-diffed)**

- **Route:** `/owner/documents` (Livewire `livewire/owner/documents.blade.php`)
- **Wireframe:** `docs/design/raw/Owner Documents.html` (served on `:8099`, hydrated — **499 chars** of real copy)
- **Live render:** hydrated, **1,965 chars**, real seeded data (6 statement periods, Mar–Sep 2026)
- **Screenshot:** `.captures/deep-owner-08-documents-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Amara Okafor` / `2025 annual tax summary` /
  `net €98,710`; live rendered `Demo Owner` / `2026 annual tax summary` / `net €3,690.00`.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | H1 `Documents` | H1 `Documents` | **MATCH** |
| 2 | Sub `Statements, remittances & tax summaries` | identical | **MATCH** |
| 3 | Sub-line `· full archive ›` | `18 documents · since March 2026 · full archive ›` | **DIVERGENCE — build richer.** The wireframe's own `3 years · 34 documents` is a **ratified refusal** (fabricated history); the live line is DERIVED from real rows |
| 4 | `full archive ›` link | → `/owner/archive` | **MATCH** + wired |
| 5 | Notification bell badge `2` | badge `6`, opens the real panel (6 statement notifications) | **MATCH** (data) |
| 6 | User chip `Amara Okafor AO` | `Demo Owner DO` | **MATCH** (data) |
| 7 | Year pills `2025` `2024` `2023` | **not rendered** | **DATA-DEPENDENT, not missing.** The pill row is built at `documents.blade.php:44` behind `@if (count($filters) > 1)`; only 2026 is seeded, so a single-year filter would be a no-op control. ⚠️ Absence in the DOM here is caused by DATA, not by capability |
| 8 | *(none)* | `All years` pill (inside the same gated row) | **EXTRA** |
| 9 | Month card header `September 2025` | `September 2026 No. 6` | **MATCH** + **EXTRA** (real statement number) |
| 10 | Month net `Net €10,370.00` | `Net €885.60` | **MATCH** (real) |
| 11 | `STMT` badge + `Owner statement` | identical | **MATCH** |
| 12 | STMT meta `OS-2025-09 · issued 1 Oct` | `No. 6 · issued 15 Aug 2026 · net payable €885.60` | **DIVERGENCE — build richer.** Real sequence number + real issue date + the net; the invented `OS-YYYY-MM` reference is a ratified refusal on this surface |
| 13 | `RA` badge + `Remittance advice` | identical | **MATCH** |
| 14 | RA meta `RA-2025-09 · €10,370.00` | `Bank transfer · net payable €885.60` (Sep) / `23 Jul 2026 · Bank transfer · net paid €721.60` (Jul) | **DIVERGENCE — build richer.** Real rail + real payout date + payable/paid wording that tracks the real lifecycle |
| 15 | *(none)* | RA status chip `Payout pending` / `Paid` | **EXTRA** — real payout lifecycle per period |
| 16 | `BKG` badge + `Booking summary` | identical | **MATCH** |
| 17 | BKG meta `BS-2025-09 · 18 bookings · €12,400` | `No. 6 · issued 15 Aug 2026 · gross €1,080.00` | **DIVERGENCE.** Live shows the real gross; the wireframe's booking COUNT is not surfaced on this row |
| 18 | Row order STMT → RA → BKG | STMT → BKG → RA | **DIVERGENCE — cosmetic ordering** |
| 19 | `PDF` button per row | `PDF` per row, real hrefs | **MATCH** + wired |
| 20 | Dark annual card `2025 annual tax summary` | `2026 annual tax summary` | **MATCH** |
| 21 | Annual meta `All 12 months · net €98,710 · one PDF for your accountant` | `6 months (Mar–Sep) · net €3,690.00 · one PDF for your accountant` | **MATCH** (copy identical in shape; the `€98,710` is a **ratified refusal** — real net substituted) |
| 22 | Annual `PDF` button | → `/owner/tax/2026/summary.pdf` | **MATCH** + wired |
| 23 | Month cards: 2 shown (Sep, Aug 2025) | 6 shown (Mar–Sep 2026), most-recent-first | **MATCH** (data) |
| 24 | *(none)* | Per-link `title` attributes on every PDF button | **EXTRA** — a11y |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data, not an empty shell | ✅ 6 real statement periods with reconciling figures |
| Statement PDF | ✅ `200 · application/pdf · 881,691 B` |
| Booking-summary PDF | ✅ `200 · application/pdf · 880,741 B` |
| Remittance PDF | ✅ `200 · application/pdf · 881,941 B` |
| Annual tax-summary PDF | ✅ `200 · application/pdf · 880,630 B` |
| `full archive ›` CTA | ✅ resolves to `/owner/archive` |
| Export gating (`CAP_EXPORTS`) | noted — the demo owner is primary and holds it; a capability-suppressed state is **unverified-without-a-second-actor** |
| Empty state | not reachable with seeded data — **unverified** |

### Verdict — page 8

**0 frontend gaps · 0 backend gaps.** 12 MATCH · 5 divergences (4 build-richer, 1 cosmetic row ordering) ·
1 data-dependent (year pills) · 4 enhancements · 2 unverified (export-gated state, empty state).

⚠️ **The year-pill finding is the page's real lesson.** The rendered DOM showed the pills absent — the exact
shape of a frontend gap. Reading the view showed them **built and deliberately suppressed** for a single-year
archive. A DOM-only read would have filed a phantom gap here.


---

## Page 9 — `/owner/tax` vs `Owner Tax Center.html` — **DONE (browser-diffed)**

- **Route:** `/owner/tax` · **Wireframe:** `docs/design/raw/Owner Tax Center.html` (hydrated, **1,164 chars**)
- **Live render:** hydrated, **1,568 chars**, real seeded data · **Screenshot:** `.captures/deep-owner-09-tax-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Amara Okafor` / `€10,370 is on hold` / `0% applied to
  payouts`; live rendered `Demo Owner` / `€885.60 is on hold` / `None withheld from payouts`.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | H1 `Tax center` | identical | **MATCH** |
| 2 | Sub `Your tax details, withholding and year-end summaries — all in one place.` | identical, char-for-char | **MATCH** |
| 3 | Bell badge `2` | `6` | **MATCH** (data) |
| 4 | User chip `Amara Okafor AO` | `Demo Owner DO` | **MATCH** (data) |
| 5 | Hold banner title `Add your tax details to release held payouts` | identical | **MATCH** |
| 6 | Banner body `€10,370 is on hold until we have a valid tax ID on file. It takes about a minute — just complete the form below.` | identical copy, `€885.60` | **MATCH** — and the hold is the **REAL** tax-hold, not a mock banner |
| 7 | `Tax profile` section title | same, emitted as `<h2>` | **MATCH** + **EXTRA** (semantic heading; the wireframe uses a plain div) |
| 8 | `Used on your annual summary and any invoices we issue on your behalf.` | identical | **MATCH** |
| 9 | `Legal name` input, prefilled | prefilled `Demo Owner` (real) | **MATCH** |
| 10 | `Tax residency` select — `Portugal, Ireland, Spain, France, United Kingdom, Other` | `—` placeholder + **17 countries** (AT, BE, CA, DK, FR, DE, GR, IE, IT, NL, PL, PT, ES, SE, CH, UK, US) | **DIVERGENCE — build richer**, ⚠️ **but see the candidate gap below**: the wireframe's `Other` catch-all has no live equivalent |
| 11 | `Tax ID / NIF *` input (required marker) | identical | **MATCH** |
| 12 | `Registered as` → `Individual` / `Company` buttons | identical (backed by a real `type` input) | **MATCH** |
| 13 | `VAT registered` toggle | identical (real `vat_registered` input) | **MATCH** |
| 14 | `Turn on if you charge VAT on your rental income.` | identical | **MATCH** |
| 15 | `VAT number` input | identical | **MATCH** |
| 16 | `Save tax details` button | identical | **MATCH** |
| 17 | *(none)* | `Your tax ID and VAT number are encrypted at rest. Saving them releases any held payouts.` | **EXTRA** — honest security + consequence microcopy |
| 18 | `Withholding tax` section | same, as `<h2>` | **MATCH** |
| 19 | Withholding copy `As a Portuguese tax resident, no withholding is applied — you declare rental income directly in your annual return.` | `No withholding is configured for your residency — you receive your full net earnings and declare your rental income in your own tax return.` | **DIVERGENCE — RATIFIED (honest refusal, #73).** The wireframe asserts a jurisdiction-specific legal fact; the build states the **engine's real configured state**. Tax copy that claims a legal conclusion the system cannot verify is exactly what the tax-engine invariant forbids |
| 20 | Withholding figure `0%` | `None` | **DIVERGENCE — same refusal.** `0%` reads as a computed rate; `None` reads as *not configured*, which is the truth |
| 21 | `applied to payouts` | `withheld from payouts` | **DIVERGENCE — copy** (more precise) |
| 22 | `Annual summaries` section + `Rental income and fees, ready for your accountant.` | identical | **MATCH** |
| 23 | Annual rows — 3 years (`2025 · year to date`, `2024`, `2023`) | 1 row (`2026`), real `Net €3,690.00 · gross €4,500.00` | **DIVERGENCE — data + RATIFIED refusal.** The multi-year history (`€94,200` / `€70,100`) is fabricated; only real statement years render |
| 24 | Row action `In progress` / `Download` | `Year to date` | **DIVERGENCE — copy** |
| 25 | `VAT invoices & documents` / `All statements and tax files` | identical, → `/owner/documents` | **MATCH** + wired |
| 26 | `About your tax` section + disclaimer (`…we don't file taxes for you… Nothing here is tax advice.`) | identical | **MATCH** |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data, not a shell | ✅ real legal name, real €885.60 hold, real 2026 net/gross |
| Hold banner reflects real state | ✅ the REAL tax-hold (not a decorative banner) |
| Annual summary link | ✅ `/owner/tax/2026/summary.pdf` → `200 · application/pdf · 880,630 B` (verified page 8) |
| `VAT invoices & documents` CTA | ✅ resolves to `/owner/documents` |
| `Save tax details` POST | ⚠️ **unverified-without-mutation** — submitting writes tax details **and releases held payouts**. Money-as-seeded; deliberately not exercised |
| Empty/unset state | partially visible — `Tax ID` and `VAT number` are genuinely empty (demo has no tax details), so the *unset* state IS the rendered state |

### Verdict — page 9

**0 backend gaps · 1 candidate frontend gap.** 15 MATCH · 6 divergences (3 ratified honest-refusals,
3 copy) · 2 enhancements · 1 unverified-without-mutation.

⚠️ **Candidate frontend gap — the `Other` residency option.** The wireframe's select ends in `Other`; the
live select offers 17 named countries and **no catch-all**. An owner resident outside those 17 cannot record
a residency at all, and `Tax ID / NIF` is marked required — so for them the form is **uncompletable**, which
on this page means **their payout hold can never be released**. The build is richer in every other respect
(17 real countries vs 5), so this reads as an oversight in the country list rather than a design decision —
but it is unratified and has a real money consequence, so it is reported.

⚠️ **The withholding copy is the page's best moment and must not be "fixed" toward the wireframe.** The
prototype states a legal conclusion (*"As a Portuguese tax resident, no withholding is applied"*); the build
states what it actually knows (*"No withholding is configured for your residency"*). Same screen, and only
one of them is defensible on a tax surface.


---

## Page 10 — `/owner/reports` vs `Owner Reports.html` — **DONE (browser-diffed)**

- **Route:** `/owner/reports` · **Wireframe:** `docs/design/raw/Owner Reports.html` (hydrated, **1,862 chars**)
- **Live render:** hydrated, **2,117 chars** · **Screenshot:** `.captures/deep-owner-10-reports-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `€84,200` / `Commission · 15%` / `Mark filed`; live
  rendered `€4,653.60` / `Commission · 18%` / the EXAMPLE-rates VAT table.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | H1 `Reports` | identical | **MATCH** |
| 2 | *(none)* | Sub `Your real performance — earnings, occupancy and where it comes from.` | **EXTRA** |
| 3 | Year pill `2025` | `2026` + `6 statements YTD`, → `?year=2026` | **MATCH** + **EXTRA** (real statement count) |
| 4 | `Custom range` button | same, opening a real month picker (`Sep 2024`–`Aug 2026`) + `Clear` + `Apply`, plus `Year to date` / `Last 6 months` presets | **MATCH** + **EXTRA** |
| 5 | `Export` button | same, opening a real menu: `PDF report`, `CSV`, `Spreadsheet`, `Annual tax summary`, `All statements`, `Email to me` | **MATCH** + **EXTRA** |
| 6 | KPI `NET EARNED · JAN–SEP` `€84,200` | `NET EARNED · 2026` `€4,653.60` + sublabel `Your statements plus earnings not yet statemented` | **MATCH** + **EXTRA** (states the basis) |
| 7 | KPI delta `+12%` | **absent** | **DIVERGENCE — RATIFIED refusal.** vs-LY renders only where prior-year data exists (Wave A); 2025 has none, so no delta is invented |
| 8 | KPI `AVG OCCUPANCY` `78%` `+6%` | `8%` `Across 2 units`; delta absent | **MATCH** + same ratified refusal |
| 9 | KPI `NIGHTS SOLD` `512` `across 3 units` | `55` `room-nights` `In the period` | **MATCH** |
| 10 | `Net earnings by month` | identical | **MATCH** |
| 11 | Chart toggle `This year` / `2024` | `2026` only | **DIVERGENCE — same ratified refusal** (no prior-year series to compare against) |
| 12 | Month bars (9: Jan–Sep) | 12 (Jan–Dec) with real values incl. a genuine **negative** `−€180.00` (Aug) | **MATCH** + **EXTRA** (a real negative month is not hidden) |
| 13 | `Performance by unit` + `ADR` / `REVPAR` | identical (`€134.18` / `€10.11`) | **MATCH** |
| 14 | Table cols `UNIT NIGHTS OCCUPANCY NET EARNED SHARE` | identical | **MATCH** |
| 15 | Unit sub-label = unit TYPE (`2-bed apartment`) | = PROPERTY (`Demo Seaside Property`) | **DIVERGENCE — minor**, different secondary attribute |
| 16 | `All units` total row | identical (`€4,653.60`) | **MATCH** |
| 17 | *(none)* | Legend `ADR = revenue per night sold · RevPAR = revenue per available night · Share = of your net.` | **EXTRA** |
| 18 | `Booking sources` / `NET BY CHANNEL` | identical | **MATCH** |
| 19 | Channels `Airbnb 39% · Booking.com 30% · Direct 19% · Vrbo 12%` | `Direct 91% · Walk-in 9% · Other 0% (−€250.00)` | **DIVERGENCE — data.** Real `reservations.source`; no OTA-branded channels are seeded, and none are invented |
| 20 | Per-unit net list repeated under Booking sources | **not repeated** | **DIVERGENCE — not a gap.** The identical figures render in the `Performance by unit` table above (`€2,496.60` / `€2,157.00`); this is de-duplication, not absent data |
| 21 | `2025 SO FAR` `€84,200` `net earned · 9 statements` | `2026 SO FAR` `€4,653.60` `Net earned · your statements plus earnings not yet statemented` `55 nights · 2 units` | **MATCH** + **DIVERGENCE** (sublabel) |
| 22 | `+12% vs 2024` | **absent** | **DIVERGENCE — ratified refusal** (as #7) |
| 23 | `GROSS TO NET` block | identical shape | **MATCH** |
| 24 | `Gross revenue` `+€104,800` | `€5,980.00` | **MATCH** |
| 25 | `Commission · 15%` | **`Commission · 18%`** `−€1,076.40` | **DIVERGENCE — RATIFIED and load-bearing.** 18% is the REAL rate read from `ManagementAgreement`; the wireframe's hardcoded 15% must never be restored |
| 26 | `Fees & OTA −€4,880` | `Fees & OTA +€0.00` | **MATCH** — a genuine zero (no fee writer posts yet, #70), not a hidden line |
| 27 | *(none)* | `Costs & adjustments −€250.00` | **EXTRA** — real expense/adjustment line |
| 28 | `Net to you €84,200` | `€4,653.60` | **MATCH** — ⚠️ **reconciles exactly (#17):** 5,980.00 − 1,076.40 + 0.00 − 250.00 = **4,653.60** |
| 29 | `NEXT PAYOUT` `Scheduled` | `LATEST PAYOUT` `Pending` | **DIVERGENCE — honest refusal.** "Next/Scheduled" implies a forward projection; the build reports the real latest payout state |
| 30 | `€10,370` `September net` `3 Nov 2025` | `€885.60` `€2,804.40 paid this year` `15 Aug 2026` | **MATCH** + **EXTRA** (YTD paid) |
| 31 | `SEPA · Revolut ····4821` | `···· 1000` | **DIVERGENCE — account-gated.** Real masked IBAN; the SEPA rail label is not asserted (no live rail) |
| 32 | Payout card links onward | → `/owner/payouts` | **MATCH** + wired |
| 33 | `DOCUMENTS` block | same + an `All →` link | **MATCH** + **EXTRA** |
| 34 | `September statement` PDF | `September 2026` + `July 2026` + `June 2026` statements, real ULID PDF routes | **MATCH** + **EXTRA** (3 recent, not 1) |
| 35 | `Tax summary 2025 · PDF` | in the Export menu + `Annual tax summary · PDF` in the VAT block | **MATCH** (relocated) |
| 36 | `All 2025 statements` `ZIP · 9 monthly PDFs` | **absent from Reports** | ⚠️ **DIVERGENCE — PLACEMENT (verdict CORRECTED on page 23).** I originally recorded this as a ratified refusal on the grounds that *"no ZIP capability exists"*. **That was wrong.** `/owner/archive` carries a working `Download ZIP` → `/owner/archive/zip`, proven to return a genuine archive (`application/zip`, 4,695,771 B, magic bytes `50 4b 03 04`). The capability is real; it simply is not surfaced on Reports |
| 37 | `Tax & VAT summary` heading | identical | **MATCH** |
| 38 | Letterhead `2025 · Seaside Villas Lda · VAT PT 512 034 776` | absent — sub reads `Output − input = net VAT, per quarter · PT` | **DIVERGENCE — data-dependent.** The demo owner has no tax details saved (confirmed on page 9: `Tax ID` empty), so there is no legal name or VAT number to print |
| 39 | *(none)* | **`EXAMPLE RATES` badge** + `Based on EXAMPLE rates. Real VAT rates for PT haven't been configured yet — these figures are illustrative only, not for filing.` | **EXTRA — the most important one on the page.** The wireframe presents `€1,550 payable to Autoridade Tributária` as authoritative; the build refuses to |
| 40 | `OUTPUT VAT · 6% €6,288` / `DEDUCTIBLE INPUT VAT · 23% −€4,738` / `NET VAT DUE €1,550` | a per-quarter table: Q1 `€32.40 / −€22.36 / €10.04`, Q2 `€120.00 / −€82.80 / €37.20`, Q3 `€117.60 / −€81.14 / €36.46`, `2026 total €270.00 / −€186.30 / €83.70` | **DIVERGENCE — build richer + real.** ⚠️ **Reconciles exactly (#17):** each row's output − input = net, and 10.04 + 37.20 + 36.46 = **83.70** |
| 41 | `payable to Autoridade Tributária` | not asserted | **DIVERGENCE — honest refusal** (naming a tax authority as payee is a filing claim) |
| 42 | `FILING STATUS` / `NEXT RETURN DUE 20 Nov 2025` / `Due in 45 days` | **absent** | **DIVERGENCE — RATIFIED refusal (#61/#39).** No filing calendar or deadline is modelled; a fabricated due date on a tax surface has legal consequences |
| 43 | `Mark filed` button | **absent** | **DIVERGENCE — RATIFIED refusal (#61).** No filing state machine exists; the button would record nothing while implying a filing was made |
| 44 | `NEXT INCOME TAX RETURN 30 Jun 2026 · IRS Modelo 3 · FY 2025` | **absent** | **DIVERGENCE — same refusal** (jurisdiction-specific filing calendar, unmodelled) |
| 45 | `NET VAT BY QUARTER` with `FILED` / `DUE` / `UPCOMING` chips | quarters present, **status chips absent** | **DIVERGENCE — same refusal** (filing status is not tracked, so no quarter can honestly claim `FILED`) |
| 46 | Explainer (`Output VAT is charged at the 6% accommodation rate… not tax advice.`) | `Computed from the EXAMPLE VAT rates for PT (6% on rental income, 23% on the management fee) × your real figures. Verify with your accountant — this is not tax advice.` | **MATCH** in role; **DIVERGENCE** — the live copy names the rates as EXAMPLE |
| 47 | `VAT return (CSV)` | `VAT return · CSV` → real route | **MATCH** + wired |
| 48 | `Download tax summary` | `Annual tax summary · PDF` → real route | **MATCH** + wired |
| 49 | *(none)* | `Tax center →` → `/owner/tax` | **EXTRA** |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ every figure real and reconciling (#17 verified twice above) |
| `PDF report` export | ✅ `200 · application/pdf · 879,953 B` |
| `CSV` export | ✅ `200 · text/csv · 560 B` |
| `VAT return · CSV` | ✅ `200 · text/csv · 561 B` |
| Range preset `?range=6m` | ✅ `200 · text/html` — the filter is a real server round-trip |
| Statement PDFs (3) | ✅ real ULID routes (same routes verified page 8) |
| `All →` / `Tax center →` / payout card | ✅ resolve to `/owner/documents`, `/owner/tax`, `/owner/payouts` |
| `Email to me` | ⚠️ **unverified-without-mutation** — a real POST that queues mail |
| `CAP_EXPORTS` gating | noted; demo owner holds it — **unverified without a second actor** |

### Verdict — page 10

**0 frontend gaps · 0 backend gaps.** 24 MATCH · 14 divergences (**8 ratified honest-refusals**, 6 minor/data) ·
11 enhancements · 2 unverified.

⚠️ **This is the page where the wireframe is actively dangerous and the build is right to diverge.** The
prototype states a VAT amount `payable to Autoridade Tributária`, a filing deadline `Due in 45 days`, an
`IRS Modelo 3` date, per-quarter `FILED` chips, and a `Mark filed` button — a complete filing workflow the
system does not model. The build computes real VAT from configured rates, **labels the rates EXAMPLE**, warns
they are `not for filing`, and refuses the filing calendar and the `Mark filed` control outright. ⚠️ **None of
items 42–45 should be "closed" by building toward the wireframe** without the founder first configuring real
rates and a filing model.


---

## Page 11 — `/owner/insights` vs `Owner Insights.html` — **DONE (browser-diffed)**

- **Route:** `/owner/insights` · **Wireframe:** `docs/design/raw/Owner Insights.html` (hydrated, **1,954 chars**)
- **Live render:** hydrated, **2,125 chars** · **Screenshot:** `.captures/deep-owner-11-insights-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Amara Okafor` / `81% +5 pts` / `Review proposals`; live
  rendered `Demo Owner` / `8%` / `Tue runs hottest`.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | H1 `Occupancy & pricing insights` | identical | **MATCH** |
| 2 | Sub `How your villas are filling, what your rates are earning, and where the headroom is.` | identical, char-for-char | **MATCH** |
| 3 | Bell `2` / chip `Amara Okafor AO` | `6` / `Demo Owner DO` | **MATCH** (data) |
| 4 | **Unit filter** — `All villas` · `Villa Coralis` · `Villa Dunes` · `Casa Aloe` | **absent** | ⚠️ **MISSING-IN-BUILD (FRONTEND) — candidate gap.** Evidence: rendered DOM has **0 selects and 0 unit-named controls**, and `insights.blade.php` contains **no unit-scoping control at all** (only the range pill row at :33). Not data-gated — genuinely absent |
| 5 | *(none)* | Range pills `2026` · `Year to date` · `Last 6 months` (real `?range=` round-trips) | **EXTRA** |
| 6 | KPI `OCCUPANCY · YTD` `81%` | `OCCUPANCY` `8%` + `55 of 730 unit-nights` | **MATCH** + **EXTRA** (shows the basis) |
| 7 | KPI delta `+5 pts vs last year` | **absent** | **DIVERGENCE — ratified refusal** (no prior-year KPI baseline). ⚠️ Note the build *does* show real vs-LY **per month** (item 12) — so this is a scope choice, not an absent capability |
| 8 | KPI `AVG NIGHTLY RATE` `€318` | `€134.18` `per night sold` | **MATCH** |
| 9 | KPI delta `+€22 achieved, this year` | **absent** | **DIVERGENCE — same refusal** |
| 10 | KPI `NEXT 90 DAYS` `€38,400` `already on the books` | `€3,680.00` `already on the books` | **MATCH** |
| 11 | KPI `MEDIAN LEAD TIME` `34 days` `booking to check-in` | `0 days` `booking to check-in` | **MATCH** — `0 days` is the real median for demo data (mostly same-day/walk-in), not a null |
| 12 | `Occupancy by month` + legend `This year` / `On the books` / `Last year` | `Achieved` / `On the books` (2 series) **plus per-month `▲ up 8 pts` / `▼ down 3 pts` markers** and the note `▲/▼ shows the change in points versus the same month last year — real, from your own history.` | **DIVERGENCE — different shape, build honest.** vs-LY renders **only for months with real prior-year data** (Jul–Oct), rather than a full phantom `Last year` series |
| 13 | Chart sub `All villas · Jan–Sep actual, Oct–Dec on the books` | `2026 · achieved and on the books` | **DIVERGENCE — copy** |
| 14 | `Booking pace · next 3 months` | `Booking pace · next months` | **MATCH** |
| 15 | Pace rows with LY comparison — `October Behind 62% booked · LY 71%` | `Sep 13% on the books`, `Oct 18%`, `Nov 0%` | **DIVERGENCE — data.** No prior-year pace exists, so `Behind`/`Ahead`/`On pace` verdicts are not invented |
| 16 | Pace row link `The proposed −8% October rate targets this gap — review it` | **absent** | **DIVERGENCE — ratified.** Refers to a specific fabricated proposal |
| 17 | `Worth a look` + `What the numbers suggest — decisions stay yours.` | identical, char-for-char | **MATCH** |
| 18 | Insight 1 `Saturdays sell out first` (narrative) | `Tue runs hottest` — `Tue averages 11% occupancy while Thu trails at 6%…` | **DIVERGENCE — build REAL.** Derived from the live day-of-week series (item 25), not authored prose |
| 19 | Insight 2 `October is pacing 9 pts behind` | `Guests book about 0 days ahead` | **DIVERGENCE — build REAL** (derived from the real median) |
| 20 | Insight 3 `Midweek gaps cluster on Tuesdays` | `€3,680.00 already on the books` | **DIVERGENCE — build REAL** |
| 21 | Insight CTAs `Review weekend rates` / `See the −8% proposal` / `Ask the team about it` | `Review your rates →` (×2) / `Open your calendar →` | **DIVERGENCE.** Live CTAs resolve to real routes; the proposal-specific CTA is a ratified refusal |
| 22 | `Rate performance` + `Achieved nightly rate after seasonal pricing, before fees.` | `Achieved nightly rate, before fees.` | **MATCH** + **DIVERGENCE** (copy — "after seasonal pricing" not claimed) |
| 23 | Per-unit rows `Villa Coralis · occupancy 82% · €412 · +€18 vs LY` | `A1 · occupancy 7% · €141.92 ADR`, `A2 · occupancy 8% · €127.24 ADR` | **MATCH** (real per-unit); the `vs LY` delta absent — **same ratified refusal** |
| 24 | `When guests book` + `Share of bookings by lead time, all villas.` + 4 buckets | identical shape, real values (`58% / 11% / 26% / 5%`) | **MATCH** — ⚠️ sums to **100%** |
| 25 | `Nights that fill first` + `Average occupancy by night of week.` + Mon–Sun | identical, real values | **MATCH** |
| 26 | Summary line `Saturdays run hottest at 94% — Tuesdays trail at 68%.` | `Your busiest night of the week is Tuesday.` | **MATCH** in role — derived from real data, and **consistent with item 18** |
| 27 | `Turn insight into rate` | identical | **MATCH** |
| 28 | Body `Seaside Villas has 2 rate proposals waiting that act on these numbers…` + `Review proposals` | `Your rates are yours to set…` + `Open your Rates` | **DATA-DEPENDENT, not missing.** The real proposal CTA IS built (`insights.blade.php:195–197`, renders `N rate proposals awaiting your approval →`); `rate_proposals` has **0 rows** seeded, so it correctly does not render a count |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ every figure a real aggregate; lead-time buckets sum to 100% |
| Range presets `?range=ytd` / `6m` | ✅ real server round-trips |
| Insight CTAs | ✅ resolve to `/owner/rates`, `/owner/calendar` |
| `Open your Rates` | ✅ resolves to `/owner/rates` |
| Proposal CTA | ✅ built and owner-scoped; **unexercised** — 0 pending proposals seeded |
| Cross-consistency | ✅ item 18's claim (`Tue` hottest) matches item 25's series and item 26's summary — one derivation, three surfaces |

### Verdict — page 11

**1 candidate frontend gap · 0 backend gaps.** 13 MATCH · 13 divergences (6 ratified refusals, 7 copy/data) ·
2 enhancements · 1 data-dependent.

⚠️ **The unit filter is the first element in this whole audit that is genuinely absent by capability**, not by
data or by refusal. The wireframe scopes the entire page to one unit; the live page is portfolio-wide only.
Per-unit data IS present (item 23 lists A1/A2 with real occupancy and ADR), so the gap is the ability to
**scope the page**, not missing analytics — which makes it a contained addition rather than a new metric path.
Evidence is two-sided: the wireframe rendered four real filter buttons, and the live view source contains no
unit-scoping control at all.

⚠️ **The proposal callout nearly became a second phantom gap** — same shape as the Documents year pills:
absent from the DOM, present in the view, suppressed because `rate_proposals` is empty. That is now **twice**
in this audit that reading the source overturned what the DOM implied.


---

## Page 12 — `/owner/rates` vs `Owner Rates.html` — **DONE (browser-diffed)**

⚠️ **This page WRITES RATES.** Both forms were read, neither submitted — money-as-seeded.

- **Route:** `/owner/rates` · **Wireframe:** `docs/design/raw/Owner Rates.html` (hydrated, **1,182 chars**)
- **Live render:** hydrated, **1,498 chars** · **Screenshot:** `.captures/deep-owner-12-rates-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Comp set average €465` / two proposal cards; live
  rendered `€180.00` / the honest empty proposals state.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | H1 `Rates & pricing` | identical | **MATCH** |
| 2 | Sub `Review the pricing your manager proposes and set the limits they work within.` | identical, char-for-char | **MATCH** |
| 3 | `Proposed rate changes` heading | identical | **MATCH** |
| 4 | Count chip `2 to review` | absent (nothing pending) | **DATA-DEPENDENT** |
| 5 | Intro `Seaside Villas suggests these based on demand and your comp set. Approve, decline, or send a counter.` | `Your manager suggests these based on demand. Approve, decline, or send a counter.` | **MATCH** + **DIVERGENCE** — `and your comp set` dropped, consistent with the comp-set refusal (item 21) |
| 6 | Proposal card 1 `Villa Coralis · Peak summer · €420 → €470 · +12%` | — | **DATA-DEPENDENT.** `rate_proposals` has **0 rows**; the entity is REAL (#67) |
| 7 | Proposal card 2 `Villa Dunes · Shoulder · €260 → €240 · −8%` | — | **DATA-DEPENDENT** (same) |
| 8 | `Approve` / `Decline` / `Counter…` buttons (×2) | — | **DATA-DEPENDENT** — the real approve/decline/counter path is built (#67, `countered_rate_minor` + `countered_note` columns exist) |
| 9 | *(none)* | Empty state `No proposed changes right now — you're all caught up.` | **EXTRA** — honest empty state |
| 10 | `Current rates` + `Live nightly rates across your villas` | `…across your units` | **MATCH** + **DIVERGENCE** (copy — "units", correct for a mixed portfolio) |
| 11 | Table col `VILLA` | `VILLA` | **MATCH** |
| 12 | Table col `BASE` | `BASE` (`€180.00`) | **MATCH** |
| 13 | Table col `WEEKEND` | `WEEKEND` (`—`) | **MATCH** — a genuine "not set" (no weekend uplift configured), not a fabricated tier |
| 14 | Table col `PEAK` (`€470` / `€320` / `€280`) | **absent** | **DIVERGENCE — unmodelled concept.** No seasonal/peak rate tier exists in the rate model (weekend uplift does; a "peak" season does not). Rendering a PEAK column would require inventing a tier — ⚠️ but this is **unratified**, so it is recorded as a divergence worth a founder call, not silently excused |
| 15 | Table col `OCC.` | `OCC.` (`15%`) | **MATCH** |
| 16 | 3 unit rows (`Villa Coralis` / `Villa Dunes` / `Casa Aloe`) | 1 row (`One-Bedroom Apartment`) | **MATCH** (data — rates are room-type-level) |
| 17 | `Pricing guardrails` + `The limits your manager prices within.` | identical | **MATCH** |
| 18 | `Pricing mode` `Dynamic` / `Fixed` | identical (real `pricing_mode` input, `dynamic` selected) | **MATCH** |
| 19 | `Auto-approve changes within` `±5%` | `AUTO-APPROVE CHANGES WITHIN ±0%` (real stored `0`) | **MATCH** |
| 20 | Helper `Changes within ±5% go live automatically; anything larger waits for your approval.` | `Every rate your operator proposes needs your sign-off before it goes live.` | **MATCH** — ⚠️ the copy **adapts to the real value**: at ±0% nothing auto-approves, and the sentence says exactly that rather than printing a generic ±N template |
| 21 | *(none)* | `These limits apply to rates your operator proposes. Your operator can still set a rate directly — you'll see it here and on your calendar.` | **EXTRA** — honest scope disclosure; the guardrail is **not** claimed to bind direct operator writes |
| 22 | `Floor / night` `€` (prefilled `180`) | same, empty (none stored) | **MATCH** (data) |
| 23 | `Ceiling / night` `€` (prefilled `600`) | same, empty | **MATCH** (data) |
| 24 | `Save guardrails` button | identical | **MATCH** |
| 25 | `Where you sit` + `Your average rate €442` | `YOUR AVERAGE RATE €180.00` | **MATCH** |
| 26 | `Comp set average €465` | **absent** | **DIVERGENCE — RATIFIED honest refusal** |
| 27 | `Priced 5% below market — room to grow` | **absent**, replaced by `Market benchmarking — how your rates compare to similar local properties — is coming once we connect a market-rate source. We won't show a comparison until it's real.` | **DIVERGENCE — RATIFIED refusal, and the right call.** A market position derived from no market data would be a pricing recommendation built on nothing |
| 28 | *(none)* | **`Adjust a rate directly`** section — `ROOM TYPE` select · `FROM` · `TO` · `NIGHTLY RATE (€)` · `WEEKEND UPLIFT (%)` · `MIN STAY (NIGHTS)` · `Update rate`, with the disclosure `it goes live immediately (no sign-off), for every unit in the room type` | **EXTRA — substantial.** A real owner-side rate editor the wireframe has no equivalent for (Wave B GATE 3), including its own blast-radius warning |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ real room type, real `€180.00` base, real `15%` occupancy, real stored guardrails (`dynamic`, `0`) |
| Empty proposals state | ✅ honest — 0 rows in `rate_proposals`, entity real |
| `Save guardrails` POST | ⚠️ **unverified-without-mutation** |
| `Update rate` POST | ⚠️ **unverified-without-mutation — deliberately.** This writes live nightly rates; exercising it would move demo money |
| `pricing_mode` / `auto_approve_pct` round-trip | ✅ rendered values come from stored state, not defaults |

### Verdict — page 12

**0 frontend gaps · 0 backend gaps.** 17 MATCH · 6 divergences (2 ratified refusals, 3 copy/data, **1 unratified
— the PEAK column**) · 3 enhancements · 5 data-dependent · 2 unverified-without-mutation.

⚠️ **Item 20 is a small thing worth naming.** The guardrail helper text is not a template with a number
substituted — at `±0%` it says *"Every rate your operator proposes needs your sign-off"*, which is what ±0%
actually means. A templated `Changes within ±0% go live automatically` would have been literally true and
completely misleading.

⚠️ **One unratified divergence for your call: the `PEAK` column** (item 14). Weekend uplift is modelled; a peak
season is not. Adding the column honestly means modelling a seasonal tier — so this is a product decision, not
a presentation fix.


---

## Page 13 — `/owner/reviews` vs `Owner Reviews.html` — **DONE (browser-diffed)**

- **Route:** `/owner/reviews` · **Wireframe:** `docs/design/raw/Owner Reviews.html` (hydrated, **2,299 chars**)
- **Live render:** hydrated, **2,438 chars** · **Screenshot:** `.captures/deep-owner-13-reviews-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `4.8 · 127 reviews` / `Seaside Villas replied`; live
  rendered `4.5 · 6 reviews` / real guest reviews.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | H1 `Reviews & reputation` | identical | **MATCH** |
| 2 | Sub `What guests are saying across your villas — Seaside Villas replies for you.` | same, with the real operator name | **MATCH** |
| 3 | KPI `OVERALL RATING` `4.8` + 5 stars | `4.5` + `★★★★☆` (half-star rendered honestly) | **MATCH** |
| 4 | `127 reviews · all villas` | `6 reviews · all villas` | **MATCH** (real count) |
| 5 | KPI `RESPONSE RATE` `100%` `replies within ~6 hours` | `67%` `replies within ~5 hours` | **MATCH** — ⚠️ the build reports a **real 67%**, not a flattering 100% |
| 6 | KPI `LAST 30 DAYS` `9` `avg 4.9` `new reviews` | `5` `avg 4.8` `new reviews` | **MATCH** |
| 7 | `By villa` section | identical | **MATCH** |
| 8 | Per-villa rows (name · N reviews · rating · trend) | `A1 · 3 reviews · 4.0 ★ · — steady`, `A2 · 3 reviews · 5.0 ★ · — steady` | **MATCH** |
| 9 | Trend arrows `▲ +0.1` / `▼ −0.1` / `— steady` | `— steady` on both | **MATCH** (data — no movement between periods; the arrow states exist) |
| 10 | `Recent reviews` + `6 shown · newest first` | identical, char-for-char | **MATCH** |
| 11 | Unit filter `All villas` / `Coralis` / `Dunes` / `Aloe` | `All villas` / `A1` / `A2` | **MATCH** (real units) |
| 12 | Reviewer avatar initials (`SM`, `JP`, `HL`…) | `DW`, `AK`, `SM`, `HL`, `MR`, `CD` | **MATCH** |
| 13 | Reviewer name `Sofia M.` (abbreviated surname) | `Demo W.`, `Anna K.`, `Sofia M.` … | **MATCH** — abbreviated, correct per #28 |
| 14 | Star rating per review | identical | **MATCH** |
| 15 | Source chip `Airbnb` / `Booking.com` / `Direct` | `Walk-in` / `Direct` / `OTA` | **DIVERGENCE — RATIFIED.** The OTA brand is unmodelled; the real `source` enum renders instead |
| 16 | Relative date `2 days ago` | identical format | **MATCH** |
| 17 | Unit label per review | `A1` / `A2` | **MATCH** |
| 18 | Review body text | real bodies | **MATCH** |
| 19 | *(none)* | Review **highlight tags** (`Comfy beds`, `Best value`, `Spotless`, `Great pool`, `Perfect location`, `Lovely touch`, `Central`) | **EXTRA** |
| 20 | Reply block `Seaside Villas replied` + reply text | identical, with the real operator name and real replies | **MATCH** |
| 21 | Unreplied state `Reply being drafted by the team` | `Awaiting a reply from your team` | **DIVERGENCE — honest refusal.** "Being drafted" asserts an in-progress draft; no draft state is modelled, so the build says only what it knows — the review is unanswered |
| 22 | `Rating breakdown` `5★ 96 · 4★ 24 · 3★ 5 · 2★ 1 · 1★ 1` | `5★ 4 · 4★ 1 · 3★ 1 · 2★ 0 · 1★ 0` | **MATCH** — ⚠️ sums to **6**, matching the stated review count (item 4) |
| 23 | `By category` — `Cleanliness` / `Communication` / `Location` / `Value` | identical four, real scores (`4.8 / 4.8 / 4.8 / 4.6`) | **MATCH** |
| 24 | `Value is your softest score — the proposed rate changes factor this in.` | **identical copy**, `rate changes` → `/owner/rates` | ⚠️ **DIVERGENCE — UNINTENTIONAL (see below).** `softest_label` is really derived, but the rest of the sentence is static |
| 25 | `Handled for you` section | identical | **MATCH** |
| 26 | Body `Reviews are collected from Airbnb, Booking.com and direct stays. Seaside Villas replies to every one within 24 hours…` | `…replies to your published reviews — 67% responded, on average within ~5 hours. Direct-stay reviews appear here automatically; Airbnb & Booking.com review sync activates once those channels are connected.` | **DIVERGENCE — build honest and better.** Real response rate instead of *"every one within 24 hours"*, and channel sync stated as **pending** rather than implied live (#37) |
| 27 | Inline link `replaced` (to the dishwasher fix) | absent | **DIVERGENCE — minor** (no maintenance deep-link from a review) |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ 6 real reviews with real bodies, sources, units and operator replies |
| Aggregates reconcile | ✅ breakdown sums to 6 = stated count; per-unit 3 + 3 = 6 |
| Response rate | ✅ real 67% (4 replied of 6), not a flattering constant |
| Unit filter | ✅ real units `A1` / `A2` |
| `rate changes` link | ✅ resolves to `/owner/rates` |
| Owner-scope + PII | ✅ reviewer surnames abbreviated; no contact detail (#28) |

### Verdict — page 13

**0 frontend gaps · 0 backend gaps.** 19 MATCH · 4 divergences · 1 enhancement — **and 1 honesty flag.**

⚠️ **The first inherited-copy problem this audit has found (item 24).** The line reads *"Value is your softest
score — the proposed rate changes factor this in."* The `Value` part is genuinely derived, but the rest is
**static text at [reviews.blade.php:188](resources/views/livewire/owner/reviews.blade.php#L188)** and asserts two
things the system cannot back:

1. **that proposed rate changes exist** — `rate_proposals` has **0 rows**, so the sentence points an owner at
   proposals that aren't there (the same page-12 empty state confirms it); and
2. **that they factor review scores in** — no such linkage exists. `RateProposalService` and `OwnerRateService`
   contain **no reference to reviews or ratings** at all; a proposal is operator-authored, with no review input.

This is not fabricated data — it is **fabricated causation**, inherited verbatim from the prototype, and it is
the one place in 13 pages where the build asserts something the wireframe claimed rather than refusing it.
It is cheap to fix (drop the clause, or gate it on a real pending proposal the way `insights.blade.php:195`
already does) — ⚠️ **but it is a code change, so it is reported, not made.**


---

## Page 14 — `/owner/messages` vs `Owner Messages.html` — **DONE (browser-diffed)**

- **Route:** `/owner/messages` · **Wireframe:** `docs/design/raw/Owner Messages.html` (hydrated, **803 chars**)
- **Live render:** hydrated, **916 chars** · **Screenshot:** `.captures/deep-owner-14-messages-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Marco Reyes` + a full 5-message thread; live rendered
  `Demo Staff` + a real 2-message thread.
- ⚠️ **Method note:** this page's controls are **unlabeled icon buttons** — `innerText` returns empty strings
  for them. Presence was therefore derived from `aria-label`/`title` on **both** sides, not from visible text.
  Reading text alone here would have reported almost every control as missing on both sides.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Thread header avatar + name `MR Marco Reyes` | `DS Demo Staff` | **MATCH** |
| 2 | Header subtitle `Property manager · Seaside Villas · usually replies within an hour` | `Hotel Manager` | **DIVERGENCE — honest refusal.** No response-time SLA is modelled, so `usually replies within an hour` is not asserted |
| 3 | Message bubbles with author + timestamp (`Marco · 1 Oct, 09:12`) | identical shape (`You · 15 Aug, 06:16`, `Demo · 15 Aug, 06:16`) | **MATCH** |
| 4 | Read receipt `✓✓ Read` on own messages | `· ✓ Delivered` | **MATCH — real, not a stand-in.** `OwnerMessages::receiptLabel()` returns `Read` once `operator_last_read_at >= sent_at`, else `Delivered`. The demo shows `Delivered` because the operator genuinely has not read it — ⚠️ this is **data**, not a refusal |
| 5 | Date separator `1 October` | `15 August` | **MATCH** |
| 6 | Per-message `Reply` action | present | **MATCH** |
| 7 | Per-message `React` action | present, expanding to real reactions `👍 ❤️ 😂 🎉 🙏 👀` | **MATCH** + **EXTRA** (a real POST per emoji) |
| 8 | Per-message `Star message` | present | **MATCH** |
| 9 | Per-message `Pin message` | present | **MATCH** |
| 10 | Per-message **`Copy text`** | **absent** | ⚠️ **MISSING-IN-BUILD (FRONTEND) — candidate gap, minor.** Evidence: the live control set (by `aria-label`) is `Reply · React · Star message · Pin message`; no copy affordance exists. The wireframe carries `Copy text` on every message. Purely client-side, no backend needed |
| 11 | `Starred messages` toolbar control | present | **MATCH** |
| 12 | `Attach a photo or file` | `Attach a file` (real `attachments[]` input) | **MATCH** (naming) |
| 13 | Composer input `Message Marco…` | `Message` | **MATCH** (placeholder wording) |
| 14 | Send control | present (`Send`) | **MATCH** |
| 15 | Quick-reply chips `When is my next payout?` / `Can I block dates for personal use?` / `Send me the tax summary` | **all three, identical** | **MATCH** |
| 16 | *(none)* | **Conversation list pane** — `2 conversations`, `All` / `Unread 1` filters, per-thread preview + `Unread` chip | **EXTRA — substantial.** The wireframe is a single-thread view with no list |
| 17 | *(none)* | `Search messages` input (real `q` param) | **EXTRA** |
| 18 | *(none)* | `New message` picker — 4 real recipients with roles (`Demo Accountant · Accountant`, `Demo Admin · Property Owner`, `Demo Staff · Hotel Manager`, `Rosa Fernandes · Housekeeping`) | **EXTRA** |
| 19 | *(none)* | `Cancel reply` + a real `reply_to_message_id` field | **EXTRA** — threaded replies |
| 20 | H1 `Messages` + `2 conversations · 1 unread` | — | **EXTRA** (page-level header the wireframe lacks) |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ 2 real threads, real messages, real unread state |
| Thread filters | ✅ `?filter=unread` is a real round-trip |
| Compose picker | ✅ 4 real org users with real roles, each `?compose={ULID}` |
| Reactions / star / pin / reply / send | ⚠️ **unverified-without-mutation** — all real POSTs with CSRF + `message_id`; not exercised |
| Read receipt | ✅ derived from real `operator_last_read_at` |
| PII posture | ✅ operator-side names + roles only; no guest contact data (#28) |

### Verdict — page 14

**1 candidate frontend gap (minor: `Copy text`) · 0 backend gaps.** 12 MATCH · 2 divergences ·
6 enhancements · 1 unverified group.

⚠️ **This page nearly produced a false report in both directions**, and the method note above is why. The
controls are icon-only, so a text-based read returns almost nothing on *either* side — a naive diff would have
called the wireframe's toolbar missing from the build **and** the build's toolbar missing from the wireframe.
Reading `aria-label`/`title` on both sides reduced the whole toolbar difference to a single real item
(`Copy text`).

⚠️ **A second near-miss worth recording:** I was about to classify `✓ Delivered` (vs the wireframe's
`✓✓ Read`) as another honest refusal. It is not — the receipt is **fully real and two-state**; the demo simply
has an unread operator. ⚠️ **Crediting the build with a refusal it did not make would have been just as wrong
as reporting a phantom gap** — both are inaccurate readings of the same evidence.


---

## ⚠️ LEDGER CORRECTION — pages 15 & 28 were mapped to the wrong routes

`Owner Notifications.html` is **not** the notification feed. Its rendered breadcrumb reads **`Settings /
Notifications`**, its heading is `Choose what we tell you and where`, and its body is a channel-preference
matrix with a `Save changes` button. **It is the wireframe for `/owner/notification-preferences`** — the row
the ledger recorded as having *no wireframe at all*.

Consequently:
- **Row 28** (`/owner/notification-preferences`) does have a wireframe, and is diffed below.
- **Row 15** (`/owner/notifications`, the feed) has **no wireframe** — consistent with canon (#48: the
  notification feed/panel was built with *"no separate panel wireframe — the proto bell is static"*). It is
  handled below as an **enhancement-check**.

⚠️ Both pages were still browser-opened and read, so neither is marked DONE on paper alone. ⚠️ **The
inventory itself was wrong** — had I diffed the feed against this wireframe, every preference control would
have been filed as missing from a page that was never meant to have them.

---

## Page 28 — `/owner/notification-preferences` vs `Owner Notifications.html` — **DONE (browser-diffed)**

- **Wireframe:** hydrated, **635 chars** · **Live:** hydrated, **1,462 chars**
- **Screenshot:** `.captures/deep-owner-15b-notification-prefs-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `amara@seasidevillas.ie` / `+353 87 555 4407`; live
  rendered `demo-owner@vyrobel-demo.test` / the real preference matrix.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Breadcrumb `Settings / Notifications` | `‹ Settings / Notifications` | **MATCH** |
| 2 | H1 `Notifications` | identical | **MATCH** |
| 3 | Sub `Choose what we tell you and where. Statements are always kept in your portal regardless.` | **identical, char-for-char** | **MATCH** |
| 4 | `Where we reach you` section | identical | **MATCH** |
| 5 | `Email` + address | `Email` + the real account address | **MATCH** |
| 6 | `SMS` + `+353 87 555 4407` | `SMS · not set up` + `No number on file yet` | **DIVERGENCE — RATIFIED honest refusal.** No SMS provider is connected (#62); showing a number would imply a delivery channel that does not exist |
| 7 | *(none)* | `SMS delivery isn't active yet — we'll use these preferences once it's set up.` | **EXTRA** — states the consequence rather than silently disabling |
| 8 | `What you hear about` section | identical | **MATCH** |
| 9 | Column headers `EMAIL` / `SMS` | `Email` / `SMS · not set up` | **MATCH** + the same honest qualifier |
| 10 | Row `Statement ready` + `Your monthly statement PDF, on the 1st` | `Statement ready` + `Your monthly statement is finalized` | **MATCH** + **DIVERGENCE — honest.** The build does not promise *"on the 1st"*; statements are finalized on a real schedule, not a fixed calendar day |
| 11 | Row `Payout sent` + `The day money leaves for your account` | **identical copy** | **MATCH** |
| 12 | Row `Performance digest` + `Occupancy & earnings vs last month` | **identical copy** | **MATCH** |
| 13 | Row `Terms changed` + `A change to commission or fees` | **identical copy** | **MATCH** |
| 14 | *(none)* | Row `Unit request reviewed` + `Your add-a-unit request is approved or declined` | **EXTRA** — a real notification type the wireframe omits |
| 15 | Row `Account & security` + `Sign-ins, bank changes — always on` | `Account & security` + `Always on` badge + `Sign-ins and bank changes — always on` | **MATCH** + **EXTRA** (an explicit always-on badge) |
| 16 | Per-row EMAIL/SMS toggles | real checkboxes bound to `prefs[<type>][email]` / `[in_app]`, **reflecting stored state** (`performance_digest][email]` is genuinely OFF while the rest are ON) | **MATCH** — ⚠️ the rendered values come from stored preferences, not defaults |
| 17 | *(none)* | A third channel — **`in_app`** — persisted per type | **EXTRA** |
| 18 | Footer `We never send marketing without asking, and urgent account or payout alerts always come through even if a channel is off.` | **identical, char-for-char** | **MATCH** |
| 19 | *(none)* | **`Quiet hours`** section — enable toggle + `quiet_start 22:00` / `quiet_end 07:00` + `Hold routine emails during these hours — you'll still see everything in-app, and urgent alerts always come through.` | **EXTRA — substantial** (Wave B GATE 6, a real feature) |
| 20 | Dirty-state indicator `Unsaved changes` | `All preferences saved` (clean state) | **MATCH** — the same indicator, showing the opposite state |
| 21 | `Save changes` button | identical | **MATCH** |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real stored preferences | ✅ per-type/per-channel checkboxes reflect real stored values, not defaults |
| Real account email | ✅ the authenticated user's own address |
| Quiet-hours fields | ✅ real `quiet_hours_enabled` / `quiet_start` / `quiet_end` (currently disabled, `22:00`–`07:00` retained) |
| `Save changes` POST | ⚠️ **unverified-without-mutation** |
| SMS channel | honestly inert — labelled `not set up`, **not** a dead toggle pretending to work |

### Verdict — page 28

**0 frontend gaps · 0 backend gaps.** 15 MATCH · 2 divergences (1 ratified refusal, 1 honest copy) ·
4 enhancements · 1 unverified.

⚠️ **Item 6 is the one to keep.** The wireframe prints a plausible mobile number against an SMS channel that
cannot send. The build says `not set up` / `No number on file yet` and explains that the preference will be
honoured once SMS exists — the toggle is kept, the capability is not claimed.

---

## Page 15 — `/owner/notifications` (the feed) — **DONE (browser-opened; enhancement-check, no wireframe)**

- **Live render:** hydrated, **979 chars** · **Screenshot:** `.captures/deep-owner-15-notifications-live.png`
- **Wireframe:** **none exists.** Per canon #48 the notification feed and bell panel were built without a
  prototype (*"the proto bell is static; recorded honestly"*). ⚠️ **No MATCH/MISSING verdict is possible here**
  — with no wireframe there is nothing to diff against, and calling anything "missing" would be baseless.

### What the page actually renders (recorded, not diffed)

| # | Element | State |
|---|---|---|
| 1 | H1 `Notifications` + `6 unread` | real count |
| 2 | Category filters `All` / `Finance` | real `?filter=finance` round-trip; **only categories that really exist** are offered |
| 3 | `Mark all read` | real POST (self-only) |
| 4 | 6 notification rows — title · `FINANCE` category tile · `Unread` chip · real context (`April 2026 · net payable €656.00`) · relative time | every row real; each links to its real statement |
| 5 | Per-row `✓` (mark read) and `×` (dismiss) | real POSTs with CSRF (#48's per-notification dismiss) |
| 6 | Footer `Choose what we tell you and where in Notification preferences. Statements are always kept in your portal regardless.` | links to `/owner/notification-preferences` |

**Wiring:** all 6 rows are real `Notifiable` records for this owner (matching the bell badge `6` seen on every
other page); each deep-links to a real statement ULID; mark-read / mark-all-read / dismiss are real self-only
POSTs — **unverified-without-mutation**. Category labels are honest (`FINANCE` only — no fabricated
`Channels`/`Housekeeping` pills, per #48).

**Verdict:** **no gaps assertable** (no wireframe). Nothing fabricated; the feed is entirely real records with
honest categories.


---

## Page 16 — `/owner/settings` vs `Owner Settings.html` — **DONE (browser-diffed)**

- **Wireframe:** hydrated, **3,022 chars** · **Live:** hydrated, **3,182 chars**
- **Screenshot:** `.captures/deep-owner-16-settings-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Amara Okafor` / `15% management fee` / `Visa ···· 4242`;
  live rendered `Demo Owner` / `18% of booking revenue` / the real section hub.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | H1 `Settings` | identical | **MATCH** |
| 2 | *(none)* | Sub `Your profile, business details, billing and account — one place.` | **EXTRA** |
| 3 | Completeness ring `67% COMPLETE` + `Nearly there` | `40%` + `Your account is 40% set up · External owner` | **MATCH** (real derived figure) |
| 4 | Hero stat `3 Channels live` | `1 Channels live` | **MATCH** — real (only Direct is live; see item 25) |
| 5 | Hero stat `3 Team members` | `3 Team members` | **MATCH** |
| 6 | Hero stat `9 Alerts on` | **absent** | **DIVERGENCE — minor** |
| 7 | Hero stat `5 Fee invoices` | `6 Fee invoices` | **MATCH** (real count) |
| 8 | `Profile` section | identical, `<h2>` | **MATCH** |
| 9 | Avatar + name + `Owner · Seaside Villas · since 2023` | avatar + `Choose photo` / `Upload photo` + `JPG, PNG or WebP · up to 4 MB.` | **MATCH** + **EXTRA** (a real upload with stated constraints) |
| 10 | `EMAIL` + `Verified` badge | `LOGIN EMAIL` + `Currently …` + `Verified` + `Change email` + `We'll email a confirmation link to the new address. Your email only changes once you click it.` | **MATCH** + **EXTRA** — a real re-verified email change |
| 11 | `PHONE` + `Verify` action | **absent from Profile** (phone lives in Business details, item 15) | **DIVERGENCE — placement + honest refusal.** No phone-verification channel exists (no SMS provider, page 28 item 6), so a `Verify` button would be unbacked |
| 12 | `NAME` field | present, real | **MATCH** |
| 13 | *(none)* | `LANGUAGE` select (`English / Português / Español / Français`) + `Save profile` | **EXTRA** (real `locale`) |
| 14 | `All changes saved` indicator | present on the prefs page; not on this section | **DIVERGENCE — minor** |
| 15 | `Business & tax details` + `Appears on statements` | same + `The business the management team pays out to — appears on your statements. Only you can change these.` | **MATCH** + **EXTRA** (states who may edit — #42) |
| 16 | `LEGAL BUSINESS NAME` / `VAT NUMBER` / `COMPANY REG. NO.` / `TAX COUNTRY` / `BILLING ADDRESS` | `BUSINESS / OWNER NAME` · `BILLING EMAIL` · `PHONE` · `STREET ADDRESS` · `CITY` · `POSTCODE` · `COUNTRY` · then a `TAX & VAT` block with `REGISTERED AS` (Individual/Company) · `LEGAL NAME` · `TAX COUNTRY` · `TAX ID / NIF` · `VAT NUMBER` | **MATCH** + **EXTRA.** All wireframe fields present except `COMPANY REG. NO.` (**DIVERGENCE — unmodelled**); the build adds a structured address, billing email, entity type and Tax ID |
| 17 | *(none)* | `Encrypted at rest · also editable in the Tax center.` | **EXTRA** — states both the protection and the second edit path |
| 18 | `View VAT summary in Reports` + `Save details` | `Save business details` + `Save tax details` (two scoped saves) | **MATCH** |
| 19 | `Billing & invoices` + `Full management` plan chip | `Billing & invoices` + `How Vyrobel is paid for managing your units.` | **MATCH** |
| 20 | `15% management fee · billed monthly from your statement` | **`18% of booking revenue`** + `Deducted on each statement — no card needed` | **DIVERGENCE — RATIFIED and load-bearing.** 18% is the REAL agreement rate via `OwnerCommissionRate`; the hardcoded 15% must never be restored |
| 21 | `Visa ···· 4242` `Expires 04 / 27` + `Update` | **absent** | **DIVERGENCE — RATIFIED refusal (#62).** The owner has no card or subscription — that is the operator's. A stored card here would be fabricated |
| 22 | `RECENT FEE INVOICES` — 5 rows (`INV-2025-09 · 15% of €12,200 · €1,830 · PDF`) | `Fee invoices · 6 on your statements` + `Statement & fee documents →` | **DIVERGENCE.** No separate fee-invoice entity exists; fees are statement lines, so the build links to the real statements instead of inventing `INV-` references |
| 23 | `View all invoices` | `Statement & fee documents →` + `Your agreement →` | **MATCH** + **EXTRA** |
| 24 | `LANGUAGE & REGION` — `Language` / `Currency` / `Time zone` / `Date format` | `Language English` / `Currency EUR` / `Timezone Europe/Lisbon` + `Change your language in Profile above · currency & timezone follow your property.` | **MATCH** (3 of 4; `Date format` **DIVERGENCE — unmodelled**) + **EXTRA** — states *why* currency/timezone are read-only (a ratified honest hold: no per-user FX) |
| 25 | `Notifications` + full per-event matrix (`EMAIL`/`SMS`/`PUSH`) | summary (`In-app` · `Email` · `SMS · soon` · `Push · soon`) + `In-app and email are live per event. SMS and push arrive with a messaging provider.` + `Manage notifications` → the real prefs page | **DIVERGENCE — placement** (the full matrix is page 28) + **ratified refusal** on SMS/push |
| 26 | Notification rows `New booking` / `Guest message` / `Review posted` | **absent from both this summary and the preferences matrix** | ⚠️ **MISSING-IN-BUILD — candidate gap (see below).** Evidence: `NotificationPreferences.php` defines `new_booking` with **`audience: operator`**, and has **no** `review_posted` or `guest_message` type at all |
| 27 | Notification rows `Statement ready` / `Payout sent` / `Monthly summary` | present (`Performance digest` = the monthly summary) | **MATCH** |
| 28 | `Quiet hours` row | present on the prefs page (real, Wave B) | **MATCH** (relocated) |
| 29 | `Connected channels` + `3 connected` | + `Where your units are listed.` | **MATCH** |
| 30 | `Direct · Vyrobel` `Always on` | **identical** | **MATCH** |
| 31 | `Airbnb` **`Connected`** + `Disconnect` | `Airbnb` **`Pending`** | **DIVERGENCE — RATIFIED refusal (#37), and a headline one.** The prototype shows two OTAs actively syncing; no channel integration is live |
| 32 | `Booking.com` **`Connected`** + `Disconnect` | `Booking.com` **`Pending`** | **DIVERGENCE — same refusal** |
| 33 | `Vrbo · Expedia` + `Connect` | `Vrbo` + `Pending` | **MATCH** (state differs) |
| 34 | `Google Vacation Rentals` + `Connect` | **absent** | **DIVERGENCE — unmodelled channel** |
| 35 | *(none)* | `Direct bookings are always on. OTA sync activates with the channel integration — we'll never show a channel as connected until it truly syncs.` | **EXTRA — the refusal, stated in the UI** |
| 36 | `Team access` + `3 people` + `Invite` | `Team access · 3` + `Manage team →` | **MATCH** (naming) |
| 37 | `People who can access this owner account. Each role sees only what it needs.` | **identical, char-for-char** | **MATCH** |
| 38 | Team rows with **email addresses** (`marco@vyrobel.com`, `fiona@harboraccounting.ie`) | names + roles only (`Demo Owner · You · Account owner`, `Demo Owner Accountant · Accountant`, `Demo Owner Viewer · View only`) | **DIVERGENCE — build more private.** Contact detail is not printed on the hub |
| 39 | `APPEARANCE & MOTION` `Theme` `Light / Auto / Dark` | `Theme` `Light · dark theme soon` | **DIVERGENCE — RATIFIED refusal (#62).** No dark palette exists; an inert Dark toggle would be a lie |
| 40 | `Reduce motion` + `Calms background animations` | `Reduce motion` + `Follows your device` + `Animations pause automatically when your device asks to reduce motion.` | **MATCH** (real, device-driven) |
| 41 | `Data export` + `Files download to this device instantly.` | + `Download your own data — owner-scoped, no other party's.` | **MATCH** + **EXTRA** (states the scope) |
| 42 | Export `Bookings · All reservations & stay details · CSV` | `Bookings (CSV)` + `Download` | **MATCH** |
| 43 | Export **`Guests · Guest contacts & lifetime value · CSV`** | **absent** | **DIVERGENCE — RATIFIED refusal (#28), and an important one.** Guest *contact* data on an owner surface is exactly what the PII perimeter forbids |
| 44 | Export `Financials · Monthly gross, fee & net · CSV` | `Statement history (CSV)` | **MATCH** (naming) |
| 45 | Export `Full account archive · JSON` | `Full account data (JSON)` | **MATCH** |
| 46 | `Security & privacy` + `Password, two-factor, passkeys, sign-ins, connected apps, data export and account deactivation.` + `Manage security` | `Security & privacy` + `Password, two-factor, active sessions and account protection.` + `→` | **MATCH** |
| 47 | `Profile completeness` `67%` `4 of 6 complete` + 6 checklist rows | `40% done` + 5 real rows (`Add a profile photo`, `Confirm your login email`, `Add your tax details` + `Add →`, `Add a payout account`, `Turn on two-factor sign-in` + `Add →`) | **MATCH** — real, derived, with working CTAs. The wireframe's `Sales channel connected` row is absent (**follows item 31** — no channel can be connected) |
| 48 | `PAYOUT ACCOUNT` `Revolut Business` `IBAN ···· 4821 · default` + `Manage in Payouts` | `Payout account` `···· 1000` + `Where your payouts land.` + `Manage in Payouts →` | **MATCH** (real masked IBAN, CAP_PAYOUT-gated) |
| 49 | `SESSION` card — `Signed in on this device · MacBook · Dublin, IE` + `Sign out` | **absent from this page** | **DIVERGENCE — placement.** Canon (#46) puts the REAL `sessions` table and *"sign out all other sessions"* on `/owner/security`. ⚠️ **Recorded as relocated, not absent — to be CONFIRMED on page 17**, which is next in the ledger. I have not yet seen that page, so I am not asserting it |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ real name, email, locale, 3 real team members, real 18% rate, real 6 fee invoices, real masked IBAN |
| Completeness ring | ✅ derived (40%) from real unmet items, not a constant |
| Section CTAs | ✅ `Manage team →`, `Manage in Payouts →`, `Manage notifications`, `Your agreement →`, `Statement & fee documents →`, `Security & privacy →` all resolve |
| 3 export downloads | ✅ real routes (`CAP_EXPORTS`-gated) |
| `Save profile` / `Save business details` / `Save tax details` / `Change email` | ⚠️ **unverified-without-mutation** (4 real POSTs; `Change email` also triggers a re-verification mail) |
| Channel states | ✅ derived from real channel status — `Pending`, never a fabricated `Connected` |

### Verdict — page 16

**1 candidate gap · 0 backend gaps.** 27 MATCH · 16 divergences (**6 ratified refusals**, 10 minor/placement/
unmodelled) · 13 enhancements · 1 unverified group · 1 forward-check (item 49).

⚠️ **Candidate gap — three owner notification types the wireframe offers and the build has no equivalent for**
(item 26): `New booking`, `Guest message`, `Review posted`. Evidence is from the catalogue, not the DOM:
`NotificationPreferences.php:41` defines `new_booking` with **`audience: operator`**, and `review_posted` /
`guest_message` are **absent entirely**. The owner portal surfaces bookings, messages and reviews as full
pages, so an owner can see all three events but cannot be *notified* of any of them. The audience split is
clearly deliberate design — but this specific omission is **unratified**, so it is reported.

⚠️ **This page carries the audit's densest cluster of honest refusals, and three are worth naming together:**
the wireframe shows a **stored Visa card** (the owner has no card — that's the operator's), **two OTAs marked
`Connected`** (no channel integration exists — the build says `Pending` and adds *"we'll never show a channel
as connected until it truly syncs"*), and a **`Guests · Guest contacts & lifetime value` CSV export** (guest
contact PII on an owner surface — precisely what #28 forbids). ⚠️ **None of these three should ever be
"fixed" toward the wireframe.**


---

## Page 17 — `/owner/security` vs `Owner Security.html` — **DONE (browser-diffed)**

- **Wireframe:** hydrated, **2,242 chars** · **Live:** hydrated, **2,939 chars**
- **Screenshot:** `.captures/deep-owner-17-security-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `70 OF 100` / `QuickBooks` / `Suspicious login blocked`;
  live rendered `40 OF 100` / the real sessions list / the real passkey + login-approval sections.

✅ **RESOLVES page 16 item 49.** The `SESSION` card absent from `/owner/settings` **is** here, and it is real:
`Active sessions` lists `Chrome · Mac · This device · active 3 minutes ago` and a second live session, behind a
password-confirmed `Sign out all other sessions`. Recorded on page 16 as *relocated, to be confirmed* —
**now confirmed by rendering, not by canon.**

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Breadcrumb `Settings / Security` | absent | **DIVERGENCE — minor** |
| 2 | H1 `Security` | identical | **MATCH** |
| 3 | Score ring `70 OF 100` + `Good` | `40 OF 100` + `Basic` | **MATCH** — real and self-consistent (current 40 + `60 Points to full` = 100; `+40` 2FA + `+20` recovery codes) |
| 4 | `Account security` + `A few quick wins will strengthen your account.` | + `Turn on two-factor to strengthen your account.` | **MATCH** — the prompt names the actual missing protection |
| 5 | Stat `4/6 Protections on` | `1/3 Protections on` | **MATCH** (real; 3 protections modelled vs the wireframe's 6) |
| 6 | Stat `+30 Points available` | `60 Points to full` | **MATCH** |
| 7 | Stat `2 Trusted locations` | `2 Active sessions` | **DIVERGENCE** — see item 20 |
| 8 | Stat `3 Connected apps` | `Off · Two-factor` | **DIVERGENCE — minor** (different 4th stat; live really has 0 connected apps) |
| 9 | ⚠️ `Suspicious login blocked` — `A failed sign-in from an unrecognised device in Porto, Portugal on 28 Sep, 03:41…` + `Secure my account` / `This was me` | **absent** | **DIVERGENCE — honest refusal.** No sign-in history is recorded (item 16), so a suspicious-login alert would be a fabricated security incident — the most alarming thing a page can invent |
| 10 | `Password` + `Last changed 3 months ago` + `Change` | full form: `CURRENT PASSWORD` / `NEW PASSWORD` / `CONFIRM NEW PASSWORD` + `At least 8 characters, including a number and a symbol.` + `Other devices with "remember me" are signed out.` + `Update password` | **MATCH** + **EXTRA** (a real re-auth'd form, stated rules, stated side-effect). `Last changed 3 months ago` absent — **DIVERGENCE, unmodelled** |
| 11 | `Two-factor authentication` `On` — `Code by SMS to ···· 4407` | section + `An authenticator app (Google Authenticator, 1Password, …) generates a 6-digit code…` + `Enable two-factor` | **MATCH** + **DIVERGENCE — honest.** The build uses **TOTP**, not SMS (no SMS provider exists — consistent with page 28 item 6). State differs (`Off`) — real |
| 12 | `View recovery codes` | `Recovery codes saved` protection row (`+20`, `Enable`) | **MATCH** (relocated into the score checklist) |
| 13 | `Passkeys` + `Face ID & Touch ID at sign-in · no password` + `Add` | section + `Sign in with Face ID, Touch ID or your device PIN instead of a password — a passkey never leaves your device.` + `Add a passkey` | **MATCH** (real WebAuthn, #66) |
| 14 | 2 registered passkeys (`MacBook Pro · Touch ID`, `iPhone 15 · Face ID`) + `Remove` | `No passkeys yet. Add one to sign in without a password on this device.` | **DATA-DEPENDENT** — honest empty state; none registered |
| 15 | `Login alerts` + `Email me when a new device signs in` | `New-device email alerts` **`SOON`** | **DIVERGENCE — honest deferral, explicitly labelled** |
| 16 | `Session timeout` + `15 min / 30 min / 1 hour` (`Preview`) | `Session timeout` **`SOON`** | **DIVERGENCE — honest deferral.** ⚠️ Note the wireframe labels its own control `Preview` |
| 17 | `Password expiry` + `Never / 90 days / 180 days` | **absent**, and not listed under `Coming soon` | **DIVERGENCE — unmodelled.** Lowest impact of the set — periodic forced rotation is a practice modern guidance actively discourages, so its absence is defensible; recorded, not flagged |
| 18 | `Recent sign-ins` + `Last 30 days` + 2 device rows + `Sign out all other sessions` | `Active sessions` + `Where you're signed in right now. Last sign-in 1 hour ago.` + 2 real rows + **password-confirmed** `Sign out all other sessions` | **MATCH** + **EXTRA** (re-auth before a destructive action) |
| 19 | Session rows show **geolocation** (`Faro, Portugal`) | show **IP** (`127.0.0.1`) | **DIVERGENCE — honest.** No geo-IP service is connected; a city name would be invented |
| 20 | `Login history` + `Last 30 days` + 4 rows with `Success` / `Failed` | `Full sign-in history` **`SOON`** | **DIVERGENCE — honest deferral**, and the reason item 9 cannot exist |
| 21 | `Boost your score` + `4 of 6 protections on` + 2 actionable rows with `+N pts` + `Enable` | `Boost your score` + `1 of 3 protections on.` + `Two-factor authentication +40 Enable` + `Recovery codes saved +20 Enable` | **MATCH** |
| 22 | `Data & privacy` + `Download a copy of your personal data — statements, payouts and account details.` + `Request my data` | `Your data` + `Download a copy of your data — your profile plus the statements, payouts and units your account covers.` + `Download my data (JSON)` | **MATCH** + **EXTRA** (names the format and the exact scope) |
| 23 | `Sign-in protection` / `Login approval requests` + `Approve new sign-ins from a device you trust` | `Login approval for new devices` + `When on, signing in from a device you haven't trusted is held until you approve it from a link we email you. A trusted device skips this, and you can turn it off here — so it can't lock you out.` + `OFF` + password-confirmed `Turn on login approval` | **MATCH** (real, #66) + **EXTRA** — the copy explains the **lock-out escape hatch**, which the wireframe never considers |
| 24 | Pending approval card — `Approval needed · just now · New sign-in · Chrome on Windows · Porto, Portugal · IP 82.14.x` + `Deny` / `Approve` | absent (feature is `OFF`, no pending request) | **DATA-DEPENDENT** — the approve/deny path is real; the wireframe's specific request is fabricated |
| 25 | `Trusted locations` + `Skip 2FA when signing in from here` + `Add current` + 2 cities + `Remove` | **`Trusted devices`** + `Browsers you've chosen to trust skip the two-factor code at sign-in. Remove any you don't recognise… Tick "Trust this device" at the code screen to add the one you're on.` + honest empty state | **DIVERGENCE — different axis, build more defensible.** The build trusts **devices** (a revocable hash-only cookie, #66); trusting a *location* needs geo-IP the system lacks, and is weaker security besides |
| 26 | `Connected apps` + `Apps you've given access to your account` | + `Third-party apps you've given read-only access… Each can read only the data listed — never your guests' contact details, door codes or Wi-Fi — and only your own. Revoke any anytime; access stops immediately.` | **MATCH** (real OAuth, #72) + **EXTRA** — states the **PII boundary** (#28/#33) and that revocation is immediate |
| 27 | 3 connected apps (`QuickBooks` / `Google Calendar` / `Xero`) + `Revoke` | `No connected apps — third-party apps you authorize will appear here.` | **DATA-DEPENDENT** — honest empty state; the three named apps are fabricated |
| 28 | `Deactivate account` + `Pause your portal access. Your statements and payout history are kept, and you can reactivate anytime.` + `Deactivate` | same + `You'll be signed out and won't be able to sign in until it's reactivated… **To reactivate, contact your management team.**` + password confirm | **MATCH** + ⚠️ **honest correction.** The wireframe promises self-serve reactivation (`anytime`); reactivation is actually operator-side, and the build says so **before** the owner locks themselves out |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real derived score | ✅ 40/100 from real protection state, arithmetically consistent |
| Active sessions | ✅ real `sessions` rows — this device + one other, with real activity times |
| Re-auth discipline | ✅ `current_password` required on sign-out-others, login-approval, deactivate |
| Passkeys / trusted devices / connected apps | ✅ real features with honest empty states |
| Password / 2FA / passkey / approval / deactivate POSTs | ⚠️ **unverified-without-mutation** — every one alters auth state; deliberately not exercised |
| `Download my data (JSON)` | ✅ real owner-scoped export route |

### Verdict — page 17

**0 frontend gaps · 0 backend gaps.** 13 MATCH · 11 divergences (**7 honest refusals/deferrals**, 4 minor/
unmodelled) · 4 data-dependent · 7 enhancements · 1 unverified group.

⚠️ **Item 9 is the single most important refusal in the audit so far.** The prototype shows a
`Suspicious login blocked` alert naming a city, a date and a time. The build records no sign-in history at
all — so that banner could only ever be **a fabricated security incident**. Inventing data is bad on a money
page; inventing a *break-in* is worse, because the user's rational response is to panic and change
credentials. The build shows nothing and labels sign-in history `SOON`.

⚠️ **Item 28 is the quiet one worth keeping.** The wireframe says an owner can reactivate `anytime`; in
reality reactivation is operator-side. The build corrects the promise **before** the irreversible click,
which is exactly where that correction has to happen.

⚠️ **One canon-vs-build note (not a defect):** `PROJECT-STATE` describes this page's checklist as *"derived,
never a score."* The live page **does** now present a 0–100 score — but it is genuinely derived from real
protection state and reconciles (40 + 60 = 100). ⚠️ **The build is newer than that canon line**; the note is
stale, not the page.


---

## Page 18 — `/owner/team` vs `Owner Team.html` — **DONE (browser-diffed)**

- **Wireframe:** hydrated, **1,275 chars** · **Live:** hydrated, **2,556 chars**
- **Screenshot:** `.captures/deep-owner-18-team-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Tomás Ferreira` / the role matrix; live rendered the
  3 real team members, a real pending invite, and the real export log.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | H1 `Team & access` | identical | **MATCH** |
| 2 | Sub `Give a co-owner or your accountant their own sign-in — scoped to what they need.` | **identical, char-for-char** | **MATCH** |
| 3 | `People with access` + count `2` | same + `3` | **MATCH** (real) |
| 4 | Owner row — avatar, name, `Owner`, `that's you`, `Full access to everything, including this page`, `Active now` | identical, with `Account owner` | **MATCH** |
| 5 | Member row — avatar, name, role, scope description | `Demo Owner Accountant · Accountant · Statements, payouts and tax documents. Read-only, export allowed.` | **MATCH** |
| 6 | Member last-seen `Active 2 days ago` | `Hasn't signed in yet` | **MATCH** — a real never-signed-in state the wireframe has no concept of |
| 7 | `Remove` per member | present (real `DELETE`) | **MATCH** |
| 8 | *(none)* | **Per-member role selector** (`Co-owner / Accountant / View only`) + `Save` (real `PATCH`) | **EXTRA** — roles are editable in place, not remove-and-reinvite |
| 9 | *(none)* | Third member `Demo Owner Viewer · View only · Dashboards, calendar and reports. No actions, no exports.` | **MATCH** (data) |
| 10 | `Invite someone` + `They get their own sign-in — you can remove access anytime.` | **identical** | **MATCH** |
| 11 | Role card `Co-owner` — `Everything you see — approvals, money, documents. **Can't manage the team.**` | `Everything you see — approvals, money, documents.` | **DIVERGENCE — copy.** The "can't manage the team" caveat is dropped from the card; ⚠️ the rule itself is stated at item 18 and is really enforced (`EnsurePrimaryOwner`, #42) |
| 12 | Role card `Accountant` — `Statements, payouts and tax documents. Read-only, export allowed.` | **identical** | **MATCH** |
| 13 | Role card `View only` — `Dashboards, calendar and reports. No actions, no exports.` | **identical** | **MATCH** |
| 14 | Email field + `Send invite` | present (real `email` input + POST) | **MATCH** |
| 15 | *(none)* | **`PENDING INVITES`** — `demo-owner-invitee@vyrobel-demo.test · View only · Expires 4 days from now` + `Resend` + `Revoke` | **EXTRA — substantial.** The wireframe has no pending-invite state at all; the build shows the real token with a real expiry and both lifecycle actions |
| 16 | `What each role can see` + column headers | identical | **MATCH** |
| 17 | Matrix rows — `Earnings & statements` / `Approvals (rates, expenses)` / `Documents & tax` / `Calendar & bookings` / `Payout account & settings` | all five, identical ticks | **MATCH** |
| 18 | *(none)* | Matrix row **`Exports (download PDFs)`** (`✓ / ✓ / —`) + footer `Only you, the account owner, can manage the team and the payout account.` | **EXTRA** — the export capability is a real gate (`CAP_EXPORTS`), and the primary-owner rule is stated |
| 19 | `Recent activity` — 4 rows of member activity (`Tomás downloaded the August statement (PDF)`, `Tomás viewed Payouts`, `You updated the payout account ending ···4821`, `Tomás exported the annual tax summary`) | 1 real row — `Y invited demo-owner-invitee@… as View only · 2 days ago` | **DIVERGENCE — data + scope.** The build's feed records real team-management events; **page-view tracking** (`Tomás viewed Payouts`) is not recorded, and inventing it would require surveillance the system does not do |
| 20 | *(none)* | **`Export download log`** — `Every statement, booking, payout or tax file your team downloads is stamped with who downloaded it — and recorded here.` + 11 real entries (file, who, when) | **EXTRA — substantial.** This is the real `ExportAudit::stamp` log (#66); the wireframe only *claims* watermarking (item 21) without ever showing a record |
| 21 | `Everyone gets their own key` + `No shared passwords — each person signs in separately, and financial exports are watermarked with who downloaded them. For your own account, turn on 2-step verification.` | same, extended: `…access is scoped to their role. Financial exports are watermarked with who downloaded them **and recorded in the log above**.` | **MATCH** + **EXTRA** — the claim now points at its own evidence |
| 22 | `Security settings` link | present → `/owner/security` | **MATCH** + wired |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ 3 real members with real roles + real never-signed-in states; 1 real pending invite with a real expiry |
| Export log | ✅ 11 real entries — and they **corroborate this audit**: `VAT return 2026`, `Statement history (CSV)`, `Annual tax summary 2026`, `Booking summary September 2026`, `Remittance advice` are the exact files fetched during the page-8/page-10 wiring checks |
| Role matrix | ✅ matches the real `OwnerTeamRole` capability matrix |
| Primary-owner gate | ✅ stated and enforced (`EnsurePrimaryOwner`, #42) — team management is not role-grantable |
| `Save` / `Remove` / `Send invite` / `Resend` / `Revoke` | ⚠️ **unverified-without-mutation** — all real, CSRF'd, method-spoofed writes |

### Verdict — page 18

**0 frontend gaps · 0 backend gaps.** 15 MATCH · 2 divergences (1 copy, 1 data/scope) · 5 enhancements ·
1 unverified group.

⚠️ **The export log is the standout, and it verified itself during this audit.** The wireframe *asserts*
that exports are watermarked but shows no record; the build keeps a real per-download log — and its top
entries are the PDFs and CSVs **this audit fetched** on pages 8 and 10, timestamped `21`–`32 minutes ago`.
The claim and its evidence are on the same page, and the evidence is live.

⚠️ **Item 19 is a refusal worth naming.** The wireframe's activity feed includes `Tomás viewed Payouts` —
per-member **page-view tracking**. The build records real team-management and download events only. Not
building that is a privacy posture, not a missing feature.


---

## Page 19 — `/owner/agreement` vs `Owner Agreement.html` — **DONE (browser-diffed)**

- **Wireframe:** hydrated, **1,494 chars** · **Live:** hydrated, **1,956 chars**
- **Screenshot:** `.captures/deep-owner-19-agreement-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Amendment ready to sign` / `Signed 14 Mar 2023`; live
  rendered `AMENDMENT READY TO ACKNOWLEDGE` / the real 18% agreement.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | H1 `Management agreement` | identical (under an `Agreement` page title) | **MATCH** |
| 2 | Sub `Your terms with Seaside Villas — commission, what's included, and renewal.` | same, real operator name | **MATCH** |
| 3 | `Active agreement` label | `ACTIVE AGREEMENT` | **MATCH** |
| 4 | `Full-service management` | identical | **MATCH** |
| 5 | `Seaside Villas manages 3 of your villas end to end — bookings, guests, cleaning and payouts.` | same copy, `2 of your villas`, real operator | **MATCH** (real unit count) |
| 6 | `STARTED 14 Mar 2023` | `STARTED 1 Feb 2026` | **MATCH** (real) |
| 7 | `TERM 12 months` | identical | **MATCH** |
| 8 | `NOTICE 60 days` | identical | **MATCH** |
| 9 | `MANAGEMENT FEE 18% of booking revenue` | **identical** | **MATCH** — the real agreement rate, consistent with pages 10 and 16 |
| 10 | `Auto-renews` + **`Oct 2026`** | `Auto-renews` (no date) | **DIVERGENCE — minor.** The renewal date is not computed/shown |
| 11 | `Download agreement` | present → real PDF | **MATCH** + wired |
| 12 | *(none)* | `Active · Portfolio-wide` badge | **EXTRA** |
| 13 | `Amendment ready to **sign**` | **`AMENDMENT READY TO ACKNOWLEDGE`** | ⚠️ **DIVERGENCE — honest refusal, and the page's headline.** See the verdict |
| 14 | Amendment body `The 2026 rate-card & fee schedule updates seasonal pricing tiers and the photography add-on. Effective 1 Jan 2026.` | `2026 rate-card & fee schedule` + `Updated management fee for the 2026 season. Effective 1 Oct 2026.` | **MATCH** (real amendment record) |
| 15 | `Review & **sign**` | **`Review & acknowledge`** | **DIVERGENCE — same refusal** |
| 16 | `Preview` | present | **MATCH** |
| 17 | *(none)* | `Acknowledging records your review and consent in the portal and applies the new terms on the effective date. **It's a portal acknowledgment, not a legally-binding e-signature.**` | **EXTRA — the most important line on the page** |
| 18 | `What your fee covers` + `Included in the 18% management fee — no extra charge.` | **identical** | **MATCH** |
| 19 | Included: `Listing & channel distribution` | identical | **MATCH** |
| 20 | Included: `Guest communications & **24/7** support` | `Guest communications & support` | **DIVERGENCE — honest.** No 24/7 SLA is modelled or staffed; the claim is dropped rather than inherited |
| 21 | Included: `Housekeeping coordination` | identical | **MATCH** |
| 22 | Included: `**Dynamic** pricing & revenue management` | `Pricing & revenue management` | **DIVERGENCE — honest.** "Dynamic pricing" implies an algorithmic engine; none exists (page 12's comp-set refusal is the same posture) |
| 23 | Included: `**Monthly** statements & payouts` | `Statements & payouts` | **DIVERGENCE — minor** |
| 24 | Included: `Damage & incident handling` | identical | **MATCH** |
| 25 | `BILLED SEPARATELY, AT COST` | identical | **MATCH** |
| 26 | At-cost items: `Deep cleaning & repairs` / `Professional photography` / `Linen & consumables` | `Cleaning` / `Maintenance` | **DIVERGENCE — taxonomy.** The build lists its **real** expense categories; photography/linen are not modelled |
| 27 | `Terms by villa` + `Commission and cleaning fees agreed per property` | identical | **MATCH** |
| 28 | Table cols `VILLA / COMMISSION / CLEANING / SINCE` | identical | **MATCH** |
| 29 | Per-villa commission varying (`18% / 18% / 16%`) | `A1 18%` / `A2 18%` | **MATCH** (real — both units on one agreement) |
| 30 | Per-villa cleaning **fee amounts** (`€90 / €75 / €70`) | `At cost` | **DIVERGENCE — honest refusal.** No per-property cleaning fee is modelled; a euro figure would be invented. `At cost` matches item 25 |
| 31 | `SINCE 2023 / 2023 / 2024` | `2026` both | **MATCH** (real) |
| 32 | `Key terms` — `Payout schedule` `Monthly, by the 5th` | `Payout schedule` `Monthly` | **DIVERGENCE — honest.** The specific day is not asserted (consistent with page 5 item 20) |
| 33 | `Notice period 60 days, either side` | **identical** | **MATCH** |
| 34 | `Owner stays · Up to 30 nights a year, fee-free` | **absent** | **DIVERGENCE — unmodelled.** No owner-stay allowance exists; owner blocks (#60) carry no free-night quota |
| 35 | `Cancellations · Handled by us per your policy` | **absent** | **DIVERGENCE — unmodelled** |
| 36 | `Renewal · 12 months, auto-renewing` | `Term 12 months` + `Renewal Auto-renewing` | **MATCH** (split across two rows) |
| 37 | *(none)* | `Payout currency EUR` | **EXTRA** |
| 38 | *(none)* | **`ATTRIBUTION`** — `Agreed exceptions to the standard rules.` + `Non Deductible Categories: Other` | **EXTRA** — the real per-agreement attribution config that drives expense deductibility (page 6) |
| 39 | `Documents` section | `DOCUMENTS` | **MATCH** |
| 40 | `Management agreement` + `**Signed** 14 Mar 2023 · PDF` | `Management agreement` + `**Summary** · PDF` | **DIVERGENCE — honest refusal.** The build generates a **summary**; it does not hold a signed contract scan, so it does not claim one |
| 41 | `2025 fee amendment` + `**Signed** 2 Jan 2025 · PDF` | `2026 rate-card amendment` + `**Awaiting acknowledgment** · PDF` | **MATCH** + honest state (real pending amendment) |
| 42 | `Want to change your terms?` + `Talk to your account manager about commission, add-ons or ending the agreement.` | **identical, char-for-char** | **MATCH** |
| 43 | `Message your manager` | present → `/owner/messages` | **MATCH** + wired |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ real agreement (start 1 Feb 2026, 12mo, 60d notice, **18%**), 2 real units, 1 real pending amendment |
| Agreement PDF | ✅ `200 · application/pdf · 881,265 B` |
| Amendment PDF | ✅ `200 · application/pdf · 881,543 B` (real ULID route) |
| `Message your manager` | ✅ resolves to `/owner/messages` |
| Commission consistency | ✅ 18% here matches Reports (page 10), Settings (page 16) and the statement breakdown — **one source, four surfaces** |
| `Review & acknowledge` POST | ⚠️ **unverified-without-mutation** — it records consent and applies new terms on the effective date |

### Verdict — page 19

**0 frontend gaps · 0 backend gaps.** 20 MATCH · 12 divergences (**7 honest refusals**, 5 minor/unmodelled) ·
4 enhancements · 1 unverified.

⚠️ **The sign-vs-acknowledge distinction is the whole page, and the build gets it exactly right.** The
wireframe says `Amendment ready to sign`, `Review & sign`, and marks documents `Signed 14 Mar 2023` — the
vocabulary of a legally-executed contract. The build says **acknowledge**, and adds a line the prototype
never contemplates: *"It's a portal acknowledgment, not a legally-binding e-signature."* On a contract page,
letting a user believe they have e-signed when no signature was captured is a **legal** misrepresentation,
not a cosmetic one. It also calls its own PDF a `Summary` rather than the signed agreement, for the same reason.

✅ **This resolves page 3's candidate gap #2.** That page flagged the missing `Management agreement renews in
14 days` / **`Review & e-sign`** attention item, noting *"no e-sign capability is modelled, so the CTA would
be unbacked."* This page **confirms it explicitly** — e-signature is deliberately and visibly refused across
the whole agreement surface. ⚠️ **The absent attention item is therefore consistent with a documented
refusal, not an oversight** — I am downgrading it from a candidate gap to a **ratified-by-behaviour
divergence.** (A renewal *reminder* without an e-sign CTA would still be a reasonable, separate addition.)


---

## Page 20 — `/owner/help` vs `Owner Help.html` — **DONE (browser-diffed)**

- **Wireframe:** hydrated, **2,227 chars** · **Live:** hydrated, **1,649 chars**
- **Screenshot:** `.captures/deep-owner-20-help-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `+353 1 574 0192` / `VYR-4821` / `All systems operational`;
  live rendered `Ask Vyrobel` / the real FAQ accordion / `Open messages`.

⚠️ **This is the widest-diverging page in the audit** — the wireframe imagines a support organisation
(phone desk, WhatsApp, 24/7 emergency line, a ticket queue, a live status board) that does not exist.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | H1 `Help & support` | identical | **MATCH** |
| 2 | *(none)* | Sub `Answers, and how to reach your management team` | **EXTRA** |
| 3 | `Owner desk online · **replies in under an hour**` | **absent** | **DIVERGENCE — honest refusal.** No support SLA or staffed desk is modelled |
| 4 | `How can we help, Amara?` (personalised greeting) | `Ask Vyrobel` | **DIVERGENCE — minor** |
| 5 | Ask body `Ask Vyrobel anything about payouts, statements, listings or tax — you'll get an **instant answer, any time**.` | `Ask about using Vyrobel — grounded in our help. For anything specific to your account, message your management team.` | **DIVERGENCE — honest.** No instant/any-time guarantee; and it states the **scope limit** (help content, not account data) |
| 6 | Ask input + `Ask` button | present (real `question` input) | **MATCH** |
| 7 | 4 suggested prompts | 4 prompts (`When will I get paid?` · `How do I read my statement?` · `What are my tax obligations?` · `Can I block dates?`) | **MATCH** |
| 8 | *(none)* | `Ask Vyrobel is an **AI assistant** — it answers from Vyrobel's help. Verify important things, and message your team for anything account-specific.` | **EXTRA — important.** The wireframe never discloses that the answerer is an AI; the build does, and tells the user to verify |
| 9 | `SUGGESTED FOR YOU` — `Verify your phone number` + `Verify now` | **absent** | **DIVERGENCE — honest refusal.** No phone-verification channel exists (pages 16 & 28) |
| 10 | `SUGGESTED FOR YOU` — `2 channels not connected` + `Connect channels` | **absent** | **DIVERGENCE — honest refusal.** No channel integration exists to connect (page 16 items 31–32) |
| 11 | `SUGGESTED FOR YOU` — `1 open support request` + `View requests` | **absent** | **DIVERGENCE — follows item 16** (no ticket queue) |
| 12 | `Message your manager` + `In-app chat · **replies under an hour**` + `Open chat` | `Message your manager` + `In-app — your team replies in your inbox, with full history` + `Open messages →` | **MATCH** + honest (the SLA is dropped) |
| 13 | `Call the owner desk` — `Mon–Fri 09:00–18:00 GMT` + **`+353 1 574 0192`** | **absent** | **DIVERGENCE — honest refusal.** A fabricated phone number is a support channel that rings nowhere |
| 14 | `Email us` — `We reply within a day` + **`owners@vyrobel.com`** | **absent** | **DIVERGENCE — same refusal** |
| 15 | `WhatsApp` — `Fastest reply · 08:00–22:00 GMT` + `Open WhatsApp` | **absent** | **DIVERGENCE — RATIFIED refusal.** WhatsApp is an explicitly account-gated integration |
| 16 | *(none, in place of 13–15)* | `Prefer phone or email?` — `Your management team's direct phone and email are **on your welcome pack and your latest statement**. For anything account-specific, messaging keeps it in one place with your history.` | **EXTRA — the right answer.** It doesn't invent a number; it points at where the real one actually is |
| 17 | `Support requests` — `2 tracked · routed automatically by topic` + `New request` + status tabs (`All 2` / `Submitted 0` / `In review 1` / `Resolved 1`) + search | **absent** | **DIVERGENCE — honest refusal, explicitly stated.** The build says *"Messaging is your tracked channel… **There's no separate ticket queue.**"* |
| 18 | Ticket rows `VYR-4821 · September payout arrived short · In review`, `VYR-4610 · Update cover photo · Resolved` | **absent** | **DIVERGENCE — same refusal.** These are fabricated tickets with fabricated references and states |
| 19 | `BROWSE BY TOPIC` with per-topic counts (`Payouts 3`, `Statements 1`, …) | **absent** (counts come from the ticket queue) | **DIVERGENCE — follows item 17** |
| 20 | `Owner FAQs` + `9 answers` | `Owner FAQs` — 7 real accordion entries | **MATCH** (count differs) |
| 21 | FAQ `When and how do I get paid?` + answer + `Go to Payouts` | identical question, real answer, deep link → `/owner/payout-method` | **MATCH** + wired |
| 22 | FAQs — statement / charges / bank account / tax & VAT / add property / block dates / renew agreement / refund | 7 of 9 present; **each with a real deep link** (`/owner/archive`, `/owner/agreement`, `/owner/tax`, `/owner/calendar`, `/owner/add-unit`, `/owner/balance`) | **MATCH** + **EXTRA** (every answer routes somewhere real). `How do I update my payout bank account?` and `How do I renew or end my agreement?` fold into other entries — **DIVERGENCE, minor** |
| 23 | `All systems operational` + `Checked moments ago` + `Refresh` + 5 per-service `Operational` rows (incl. **`Channel sync · Airbnb & Booking.com`**) | **absent** — replaced by `HOW SUPPORT WORKS` / `Your operator is your first line of support` / `Payouts, statements and your portal are running normally.` | **DIVERGENCE — deliberate refusal**, ⚠️ **but with a residual claim — see the verdict.** Nothing monitors these services, and `Channel sync · Operational` would be flatly false (channels are `Pending`, page 16) |
| 24 | `Payout or guest-safety emergency?` + `our priority owner line is open **24/7**` + **`+353 1 574 0911`** | **absent** | **DIVERGENCE — honest refusal, and the most consequential.** A fabricated 24/7 emergency number is one a person might call during a real guest-safety incident |
| 25 | `STILL STUCK?` equivalent | `STILL STUCK?` + the no-ticket-queue explanation + `Open messages` | **MATCH** + **EXTRA** |
| 26 | *(none)* | `JUMP TO` — `Payouts` · `Statements` · `Tax center` · `Payout method` · `Agreement` | **EXTRA** — in-page nav to real routes |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| FAQ deep links (7) | ✅ all resolve to real owner routes |
| `Open messages` / `Message your manager` (3 CTAs) | ✅ resolve to `/owner/messages` |
| `JUMP TO` links (5) | ✅ all resolve |
| `Ask Vyrobel` POST | ⚠️ **unverified-without-mutation** — a real `question` submission |
| Contact channels | honestly **absent** rather than dead — no number/email/WhatsApp is printed |

### Verdict — page 20

**0 frontend gaps · 0 backend gaps.** 6 MATCH · 16 divergences (**10 honest refusals**) · 6 enhancements ·
1 unverified — **plus 1 minor residual claim (below).**

⚠️ **The refusals here are the strongest set in the audit, because the fabrications are the most harmful.**
The prototype prints a **support phone number**, a **support email**, a **WhatsApp channel**, and a **24/7
priority line for "payout or guest-safety emergency"** — none of which exist. A fabricated statement figure
misleads; a fabricated emergency number is one a person may dial during a real guest-safety incident and
reach nothing. The build prints **no number at all** and instead says the real contact details are *"on your
welcome pack and your latest statement"* — pointing at where they genuinely are.

⚠️ **It also refuses an entire fake subsystem:** a ticket queue with two invented tickets, reference numbers,
statuses and topic counts. The build states plainly *"There's no separate ticket queue"* and routes support
through the real messaging thread.

⚠️ **MINOR RESIDUAL CLAIM (item 23) — worth one line of copy work.** The live status board was deliberately
removed, and [help.blade.php:129–132](resources/views/livewire/owner/help.blade.php#L129-L132) documents why:
*"nothing here measures a system… the dot is gone and the heading now describes what the line actually says."*
But the sentence itself still reads **"Payouts, statements and your portal are running normally."** — a
present-tense operational assertion with no monitoring behind it. It is **far weaker** than the wireframe's
board (no per-service chips, no `Checked moments ago`, no `Refresh`, and crucially no false
`Channel sync · Operational`), and the refusal around it is deliberate and documented — so this is a
**residual**, not a fabrication of the wireframe's kind. ⚠️ **§N.13.1 shape: the leftover sits immediately
beside the thing that was correctly removed.** Reported, not fixed.


---

## Page 21 — `/owner/welcome` vs `Owner Onboarding.html` — **DONE (browser-diffed)**

- **Wireframe:** hydrated. ⚠️ **It is a 3-STEP WIZARD and only step 1 renders on load** (321 chars). I clicked
  `Continue` twice **on the prototype** (a static file on `:8099` — no backend, no app state touched) to reach
  steps 2 and 3, so the diff covers the whole flow rather than a third of it.
- **Live:** hydrated, **1,105 chars** · **Screenshot:** `.captures/deep-owner-21-welcome-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Welcome, Amara` / `IE12 BOFI 9000 0112 3456 78` /
  `Finish setup`; live rendered `Welcome, Demo 👋` / the real 18% rate / 4 working CTAs.

⚠️ **These are structurally different screens.** The wireframe is a **data-collecting wizard** (name, email,
IBAN, notification toggles → `Finish setup`). The build is a **single-screen orientation tour** that collects
nothing and hands off to the real pages. Canon #66 records this as deliberate: *"the IBAN-less thin flow —
real units + REAL commission rate + certified Payout-Method hand-off, NO IBAN/micro-deposit."*

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Brand header `V · Seaside Villas` + `Owner portal · welcome` | standard owner shell + `Welcome` / `A quick tour to get you set up.` | **DIVERGENCE — layout.** The wireframe is a standalone signup-style shell; the build runs inside the portal |
| 2 | Progress `Step 1 of 3` / `2 of 3` / `3 of 3` | numbered cards `1` · `2` · `3` · `4` (no wizard progress) | **DIVERGENCE — interaction model.** Four informational steps, none blocking |
| 3 | `Welcome, Amara` | `Welcome, Demo 👋` | **MATCH** (real name) |
| 4 | `Seaside Villas added your 3 units to Vyrobel. Confirm how we reach you and where payouts go.` | `Your owner portal is ready. Here's everything that's already set up — and the couple of things worth doing next.` | **DIVERGENCE — follows the model change** (nothing to "confirm"; it reports real state) |
| 5 | `Villa Azure · Villa Coralis · Villa Dunes — managed at **15% commission**` | `2 units on your account: A1, A2.` + step 2's **`18% of booking revenue`** | **MATCH** on the unit list (real) + **DIVERGENCE — RATIFIED.** 18% is the real agreement rate; the wireframe's hardcoded 15% is wrong here exactly as on pages 10, 16 and 19 |
| 6 | Step 1 field `YOUR NAME` (prefilled) | **absent** | **DIVERGENCE — hand-off.** Name is editable on the real Profile (page 16 item 12); the tour does not re-collect it |
| 7 | Step 1 field `EMAIL FOR STATEMENTS` (prefilled) | **absent** | **DIVERGENCE — hand-off** (real login-email change lives on Settings, with re-verification) |
| 8 | Step 2 heading `Where should payouts land?` + `Your net earnings are sent here each month. It must be an account in your name.` | step 3 card `Your payout account` + `A payout account is on file — you're all set to receive money.` + `Review payout method →` | **MATCH** in role — ⚠️ the build **reports the real state** instead of asking |
| 9 | Step 2 field `ACCOUNT HOLDER` | **absent** | **DIVERGENCE — RATIFIED refusal/hand-off (#66).** Bank details are collected only on the certified Payout Method page |
| 10 | Step 2 field **`IBAN`** (`IE12 BOFI 9000 0112 3456 78`) | **absent** | **DIVERGENCE — RATIFIED refusal (#66), and the deliberate core of the flow.** ⚠️ Collecting an IBAN in an onboarding wizard bypasses the one hardened, validated, capability-gated path that owns bank data |
| 11 | Step 2 copy `We send **two micro-deposits** to verify it's yours — confirm them later in Payouts.` | **absent** | **DIVERGENCE — RATIFIED refusal.** No verification mechanism exists (page 2 item 1, page 3 item 18) — promising micro-deposits that never arrive is a broken promise, not just a false label |
| 12 | Step 3 heading `How would you like to stay in the loop?` + `Change any of this later in Settings.` | step 4 card `Choose what we tell you` + `Statements, payouts and reviews — pick which reach your inbox, and set quiet hours if you like.` + `Notification settings →` | **MATCH** in role + **EXTRA** (mentions the real quiet-hours feature) |
| 13 | Step 3 toggle `Monthly statement email` + `A PDF on the 1st, when your statement finalizes` | **absent here** — the real per-type matrix is on `/owner/notification-preferences` | **DIVERGENCE — hand-off.** ⚠️ Consistent with page 28 item 10, where the build also declines to promise *"on the 1st"* |
| 14 | Step 3 toggle `Payout sent alerts` + `A note the day money leaves for your account` | **absent here**; real on the prefs page | **DIVERGENCE — hand-off** |
| 15 | Step 3 toggle `Monthly performance digest` + `Occupancy & earnings vs last month` | **absent here**; real on the prefs page (`Performance digest`) | **DIVERGENCE — hand-off** |
| 16 | *(none)* | Step 1 card `Your units are live` + real unit codes + `Open My Units →` | **EXTRA** |
| 17 | *(none)* | Step 2 card `How you get paid` — `charges 18% of booking revenue; the rest is yours, paid out per statement. You'll see every figure on your Statement and Reports.` | **EXTRA** — explains the money model up front, from the real rate |
| 18 | `Continue` / `Back` / `Finish setup` | **absent** (nothing to submit) | **DIVERGENCE — follows the model change** |
| 19 | `Skip for now` | **absent** | **DIVERGENCE — unnecessary.** Nothing blocks; the tour is already skippable |
| 20 | Footer `Managed by Seaside Villas on Vyrobel` | absent (portal shell instead) | **DIVERGENCE — minor** |
| 21 | *(none)* | Closing line `That's the tour. Your first statement lands after your first payout period — everything after that runs itself.` + `Go to your dashboard` | **EXTRA** |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ real owner name, real unit codes (`A1`, `A2`), real **18%** rate, real payout-account-on-file state |
| `Open My Units →` | ✅ → `/owner/units` |
| `Review payout method →` | ✅ → `/owner/payout-method` (the certified path) |
| `Notification settings →` | ✅ → `/owner/notification-preferences` |
| `Go to your dashboard` | ✅ → `/owner` |
| Forms | **none** — the page collects nothing, so there is nothing to leave unverified |
| Payout-account state | ✅ derived — it says *"A payout account is on file"* because one really is |

### Verdict — page 21

**0 frontend gaps · 0 backend gaps.** 4 MATCH · 14 divergences (**4 ratified refusals**, 8 hand-offs/model,
2 minor) · 5 enhancements · **0 unverified** (nothing to mutate).

⚠️ **The IBAN field is the whole decision, and skipping it is the right call.** The wireframe collects bank
details in an onboarding wizard and promises **micro-deposit verification**. The build refuses both: bank data
is entered only through the certified, validated, `CAP_PAYOUT`-gated Payout Method page, and no verification
mechanism exists to promise. ⚠️ **A second bank-entry path is not a convenience — it is a second place for
money to go wrong**, and it would sit outside every guard the real one has.

⚠️ **Every divergence here traces to either a documented refusal or a hand-off to a real page** — none is an
absent capability. The build reports state (*"A payout account is on file"*) where the wireframe asks for
input, which is only possible because the state is genuinely known.


---

## Page 22 — `/owner/add-unit` vs `Owner Add Unit.html` — **DONE (browser-diffed)**

- **Wireframe:** hydrated. ⚠️ Another **4-step wizard** that renders one step at a time (364 chars at step 1).
  I typed a name and clicked `Continue` **on the prototype** (static file, no backend) to reach steps 2–4.
- **Live:** hydrated, **804 chars** visible — ⚠️ **but all four steps are already in the DOM** (Alpine hides
  the inactive ones), so the full live control inventory was read without any interaction.
- **Screenshot:** `.captures/deep-owner-22-addunit-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Step 1 of 4` → `Step 4 of 4` with `15% commission`; live
  rendered the real 4-step form with `Submit for review`.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Breadcrumb `My units` | `My units` | **MATCH** |
| 2 | `Add a unit` title | identical | **MATCH** |
| 3 | `Step 1 of 4` progress | identical | **MATCH** |
| 4 | Step 1 `The basics` + `Tell us what you're listing and where it is.` | **identical, char-for-char** | **MATCH** |
| 5 | `UNIT NAME` + placeholder `e.g. Villa Marisol` | **identical placeholder** (real `requested_name`) | **MATCH** |
| 6 | `PROPERTY TYPE` — `Apartment · Studio · Villa · Townhouse · Room` | `Apartment · Villa · Room · **Dorm bed**` (real `requested_kind`) | **DIVERGENCE — taxonomy.** The build offers its **real** unit-kind enum; `Studio`/`Townhouse` are not modelled, `Dorm bed` is |
| 7 | `LOCATION` — fixed town chips `Faro / Lagos / Tavira` | `TOWN / CITY` **free-text** input (`location`, placeholder `e.g. Split`) | **DIVERGENCE — build more general.** Hardcoded towns would only suit one portfolio |
| 8 | `STREET ADDRESS` + placeholder `Street & number` | **identical** (real `street_address`) | **MATCH** |
| 9 | Disabled CTA `Name your unit to continue` | **identical copy**, same disabled-until-named behaviour | **MATCH** |
| 10 | Step 2 `Space & capacity` + `How big is it, and what does it offer?` | present (real `bedrooms` / `bathrooms` / `sleeps` steppers) | **MATCH** |
| 11 | Steppers `Bedrooms` / `Bathrooms` / `Sleeps · Maximum guests` | all three, each with labelled `One more/fewer` buttons | **MATCH** + **EXTRA** (accessible stepper labels) |
| 12 | `AMENITIES` — `WiFi · Sea view · Pool · Parking · Balcony · A/C · Breakfast · Pets` (8) | `Air conditioning · Heating · WiFi · Kitchen · TV · Washer · Pool · Balcony · Free parking · Pet-friendly` (10) | **DIVERGENCE — taxonomy.** 10 real amenity keys vs 8; `Sea view` and `Breakfast` are not in the build's set, and `Heating`/`Kitchen`/`TV`/`Washer` are |
| 13 | Step 3 `Photos` + `Drop your cover photo onto the preview card, then add a couple more.` | real `photos[]` inputs — `Cover photo` + `Another photo` | **MATCH** |
| 14 | Step 3 copy `Our team can arrange a **professional shoot** after approval — just ask.` | **absent** | **DIVERGENCE — honest.** No photography service is modelled (the same claim was dropped from the agreement's at-cost list, page 19 item 26) |
| 15 | Step 4 `Rate & terms` + `Set a starting rate — our revenue team fine-tunes it against demand.` | present (real `nightly_rate`) | **MATCH** |
| 16 | `BASE RATE / NIGHT €` | `NIGHTLY RATE` (real `nightly_rate`) | **MATCH** (naming) |
| 17 | `CLEANING FEE €` | present (real `cleaning_fee`) | **MATCH** |
| 18 | `Minimum nights` + `Shortest stay you'll accept` | present (real `min_nights` stepper) | **MATCH** |
| 19 | `Managed by Seaside Villas · **15% commission**` + `You keep about **€119** per night after commission` | **absent** | ⚠️ **DIVERGENCE — and the right refusal.** The wireframe computes a take-home estimate off a **hardcoded 15%**. The real rate is 18% (pages 10/16/19/21), so this line would be **wrong money** — and an estimate shown at the moment of listing is exactly the figure an owner would anchor on |
| 20 | *(none)* | `NOTES` textarea — `Access quirks, parking, a gate code the cleaners need — anything that helps us set it up right.` | **EXTRA** — the real `notes` column (#DO9-1) |
| 21 | `Submit for review` | identical (real POST) | **MATCH** |
| 22 | `Back` on steps 2–4 | present on every step | **MATCH** |
| 23 | *(none)* | **`Save as draft`** + `Keeps what you've filled in so far. Nothing is sent to … until you submit.` | **EXTRA** — a real draft path, and it states the submission boundary |
| 24 | `LIVE PREVIEW` card + `Pending review` badge | identical | **MATCH** |
| 25 | Preview title `New unit` → live-updates to the typed name | `Your new unit` | **MATCH** |
| 26 | Preview subtitle `Apartment · Faro` | `Apartment` | **DIVERGENCE — minor** (no fixed town list to echo) |
| 27 | Preview stats `2 · 1 bd · 1 ba` | identical | **MATCH** |
| 28 | Preview price **`€140 / night`** (prefilled) | **`€— / night`** | **MATCH** — ⚠️ the build shows an **em-dash until a rate is entered** rather than a placeholder price that isn't the owner's |
| 29 | *(none)* | `Add a cover photo` affordance on the preview card | **EXTRA** |
| 30 | Preview footer `This is how your unit will appear once Seaside Villas approves it.` | **identical**, real operator name | **MATCH** |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real form fields | ✅ every control bound to a real request field (`requested_name`, `requested_kind`, `location`, `street_address`, `bedrooms`, `bathrooms`, `sleeps`, `min_nights`, `nightly_rate`, `cleaning_fee`, `photos[]`, `notes`) |
| Real operator name in copy | ✅ `Vyrobel Demo — Seaside Hotel & Apartments` |
| Live preview | ✅ reactive to the real form state (price shows `€—` until entered) |
| `Submit for review` / `Save as draft` | ⚠️ **unverified-without-mutation** — both create real records |
| Approval model | ✅ honest — `Pending review`, and the notification type `unit_request_reviewed` exists (page 28 item 14), so the loop is real end-to-end |

### Verdict — page 22

**0 frontend gaps · 0 backend gaps.** 20 MATCH · 6 divergences (1 honest refusal, 2 taxonomy, 3 minor) ·
4 enhancements · 1 unverified group.

⚠️ **Item 19 is the money moment on this page.** The wireframe closes the wizard with *"15% commission — you
keep about €119 per night."* The real rate is **18%**, so that estimate is wrong by construction — and it
appears exactly where an owner decides whether listing is worth it. The build shows no estimate rather than a
wrong one. ⚠️ This is the **fifth** page where the wireframe's hardcoded 15% would have been wrong money.

⚠️ **Item 28 is the small version of the same discipline:** the preview card shows `€—` until the owner types
a rate, where the prototype shows `€140`. A placeholder price on a card captioned *"this is how your unit will
appear"* is a number the owner never chose.


---

## ⚠️ CORRECTION TO PAGE 10 — the ZIP verdict was wrong

On page 10 I recorded the Reports page's missing `All 2025 statements · ZIP` as a **ratified refusal**, reasoning
that *"no ZIP capability exists, and a dead ZIP button would be worse than its absence."* ⚠️ **That reasoning was
mine, not the code's — and it was false.**

`/owner/archive` renders a `Download ZIP` control wired to `/owner/archive/zip`. Fetched non-destructively it
returns **`200 · application/zip · 4,695,771 B`**, `content-disposition: attachment;
filename=vyrobel-statement-archive-2026-08-17.zip`, with leading bytes **`50 4b 03 04`** — the PK signature of
a real ZIP, not an HTML error page with a misleading content-type.

**The corrected verdict:** the ZIP is a **placement** divergence on Reports, not an absent capability. Page 10's
row 36 has been amended in place. ⚠️ **I generalised "absent from this page" into "absent from the build" without
checking — the exact inference this audit's rules forbid, committed by the auditor.**

---

## Page 23 — `/owner/archive` vs `Owner Archive.html` — **DONE (browser-diffed)**

- **Wireframe:** hydrated, **491 chars** · **Live:** hydrated, **1,109 chars**
- **Screenshot:** `.captures/deep-owner-23-archive-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `€236,710` / `3 years · 34 documents`; live rendered
  `€3,690.00` / 6 real statements with real ULIDs.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Breadcrumb `Documents / Archive` | absent (direct page) | **DIVERGENCE — minor** |
| 2 | H1 `Archive` | `Statements archive` | **MATCH** (naming) |
| 3 | Sub `Your full history since joining · **3 years · 34 documents**` | `Your finalized history · **1 year · 6 statements**` | **MATCH** — derived from real rows; the wireframe's 3-year history is fabricated (same refusal as page 8 item 3) |
| 4 | `NET EARNED · LIFETIME` `€236,710` `since June 2023` | `€3,690.00` `SINCE MARCH 2026` | **MATCH** (real, and equal to the annual tax basis on pages 8/9 — **one figure, three surfaces**) |
| 5 | Year spark-bars `2023 €41k · 2024 €97k · 2025 €99k` | `2026 €3,690.00` | **MATCH** (one real year) |
| 6 | Search input `Search documents — month, year, type or reference…` | **absent** (a `Clear` control is present) | **DIVERGENCE — minor.** With 6 rows on one screen a search box has little to do; ⚠️ unratified, so recorded |
| 7 | Year group header `2025 · to date` | `2026 · Mar – Sep` | **MATCH** + **EXTRA** (real month span) |
| 8 | Group meta `12 documents · net €98,710` | `6 statements · net €3,690.00` | **MATCH** (real) |
| 9 | `Tax summary · draft` / `Tax summary` per year | `Tax summary` → `/owner/tax/2026/summary.pdf` | **MATCH** + wired. The `· draft` qualifier is absent — **DIVERGENCE, minor** |
| 10 | `Open months` expander per year | year group rendered **already expanded** | **DIVERGENCE — interaction** (nothing hidden to open) |
| 11 | *(none)* | Table header `STATEMENT / STATUS / NET PAYABLE` | **EXTRA** — a real table rather than an accordion |
| 12 | *(none)* | Per-statement row: month + **`No. N`** sequence | **EXTRA** |
| 13 | *(none)* | Per-row status `Payout pending` / `Paid` | **EXTRA** — real payout lifecycle |
| 14 | *(none)* | Per-row **`Contains unverified lines`** | **EXTRA — an honest qualifier.** It flags OTA-cost lines pending channel reconciliation (the same `Unverified` tag seen on Balance, page 5) rather than presenting every figure as settled |
| 15 | *(none)* | Per-row `NET PAYABLE` (`€885.60` … `€442.80`) | **EXTRA** |
| 16 | *(none)* | Per-row `View` / `PDF` / `Bookings` (3 real routes each) | **EXTRA** — 18 working links |
| 17 | Footer `Every document is kept for **7 years** and stays available even if you leave.` | **absent** | **DIVERGENCE — honest.** A 7-year retention guarantee is a contractual claim with no retention policy behind it |
| 18 | `Download the full archive (ZIP)` | `Download ZIP` + `One ZIP of 6 statement PDFs. Each statement above already downloads on its own.` | **MATCH** + **EXTRA** — ⚠️ **and this is the control that corrected page 10** |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ 6 real statements, real ULIDs, real nets summing to the stated `€3,690.00` |
| Lifetime net cross-check | ✅ `€3,690.00` matches the annual tax summary (page 9) and the Documents annual card (page 8) |
| Per-row links (18) | ✅ `View` / `PDF` / `Bookings` per statement, all real ULID routes |
| `Tax summary` | ✅ → `/owner/tax/2026/summary.pdf` |
| **`Download ZIP`** | ✅ **`200 · application/zip · 4,695,771 B`**, PK magic bytes, real attachment filename |
| Empty state | not reachable with seeded data — **unverified** |

### Verdict — page 23

**0 frontend gaps · 0 backend gaps.** 7 MATCH · 5 divergences (1 honest refusal, 4 minor) · 6 enhancements ·
1 unverified.

⚠️ **This page is where I caught my own error**, and it is worth stating plainly: on page 10 I turned "this
control is absent from Reports" into "the build has no ZIP capability," and wrote a confident ratified-refusal
verdict on top of that inference. One fetch here disproved it. **The audit's own rule — a negative needs
evidence, not reasoning — applies to the auditor too.** Page 10 row 36 is corrected in place.

⚠️ **Item 17 is a small refusal with real teeth.** The wireframe promises documents are *"kept for 7 years and
stay available even if you leave."* That is a retention **contract**; no retention policy is implemented, and
GDPR erasure would cut against it. The build says nothing rather than promising a term it cannot keep.


---

## Page 24 — `/owner/payout-method` vs `Owner Payout Method.html` — **DONE (browser-diffed)**

⚠️ **REAL BANKING SURFACE.** Every form was read; none submitted — money-as-seeded.

- **Wireframe:** hydrated, **1,429 chars** · **Live:** hydrated, **2,329 chars**
- **Screenshot:** `.captures/deep-owner-24-payoutmethod-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Verifying · 2 deposits sent` / `est. 3 Nov 2025`; live
  rendered the real masked IBAN and the real €885.60 next payout.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Breadcrumb `Payouts` | `‹ Payouts / Payout method` | **MATCH** |
| 2 | H1 `Payout method` | identical + sub `Where your payouts settle` | **MATCH** + **EXTRA** |
| 3 | *(none)* | `Get paid automatically. Connect a Stripe account… **Stripe handles verification; your details never touch Vyrobel.**` + `Connect Stripe` | **EXTRA — substantial.** The real Connect onboarding (test-mode), and it states the data boundary |
| 4 | `Bank accounts` + `Choose where monthly payouts settle. **Add and verify accounts anytime.**` | same + `The radio marks your default account.` | **MATCH** + **DIVERGENCE — honest.** "and verify" is dropped: there is no verification mechanism |
| 5 | Account row `Revolut Business` + `Default` + `IBAN ···· 4821 · EUR` | `Revolut Business` + `Default` + `Demo Owner · IBAN ···· 1000 · LT · EUR` | **MATCH** + **EXTRA** (holder + country) |
| 6 | Account badge **`Verified`** (×2) | **absent** | **DIVERGENCE — RATIFIED refusal (#58).** No verification column exists; a `Verified` badge on a bank account is a trust signal the system cannot earn |
| 7 | Third account `AIB` in state **`Verifying · 2 deposits sent`** + `Added 4h ago · confirm the amounts to activate` + `Confirm` | **absent** | **DIVERGENCE — RATIFIED refusal.** An entire micro-deposit verification flow with a `Confirm` action that would do nothing |
| 8 | *(none)* | `Automated bank verification (micro-deposits) activates with your live banking connection — connect Stripe above for hands-off payouts.` | **EXTRA — the refusal, stated in place.** It names the missing capability instead of silently omitting it |
| 9 | `Add bank account` | `Add a bank account` section with real fields | **MATCH** |
| 10 | *(implied)* | `ACCOUNT HOLDER (LEGAL NAME)` (real `account_holder`) | **EXTRA** |
| 11 | *(implied)* | `IBAN` + placeholder `e.g. PT50 0002 0123 1234 5678 9015 4` + `We validate and store this securely — only the last 4 digits are ever shown.` | **EXTRA** — real mod-97 validated `iban` field with a stated display rule |
| 12 | *(implied)* | `LABEL (OPTIONAL)` · `CURRENCY` · `Save bank account` | **EXTRA** |
| 13 | *(none)* | `Your IBAN is **encrypted at rest**. Your first account becomes the default automatically.` | **EXTRA** — states protection and the default rule |
| 14 | *(none)* | `Remove <account>` (real `DELETE`) | **EXTRA** |
| 15 | `Revolut Business is your default account.` + `Current default` | the `Default` chip + radio | **MATCH** |
| 16 | `Payout schedule` + `When earnings are released to your default account.` | **identical** | **MATCH** |
| 17 | `FREQUENCY` — `Monthly` / `Every 2 weeks` / `Weekly` | `Monthly` / `Twice monthly` / `Weekly` (real `frequency`) | **MATCH** (naming) |
| 18 | `PAYOUT DAY (BUSINESS DAY OF MONTH)` — `1st`–`5th` | **identical**, real `payout_day` | **MATCH** |
| 19 | `MINIMUM THRESHOLD €` + `Balances below this roll into the next payout.` | same + `0 = none.` (real `minimum`) | **MATCH** + **EXTRA** |
| 20 | `CURRENCY € EUR` + `Set by your **properties'** settlement region.` | `Set by your settlement region.` | **MATCH** — the same no-FX hold as page 2 item 26 |
| 21 | Summary `Monthly · 3rd business day` + `Next payout €10,370 · **est. 3 Nov 2025** to the default account` | `Monthly · 3 business day. Next payout €885.60 to your default account.` | **MATCH** on the real amount; ⚠️ **DIVERGENCE — RATIFIED refusal:** no estimated date (page 2 item 3, same refusal) |
| 22 | *(none)* | `**Your stated preference.** The actual payout date + release are confirmed with your operator and the payout rail.` | **EXTRA — an important qualifier.** It tells the owner the schedule is a request, not a guarantee |
| 23 | `Save schedule` | present | **MATCH** |
| 24 | `Tax & remittance details` + `Printed on your remittance advice and annual tax summary.` | **identical** | **MATCH** |
| 25 | `PAYEE TYPE` `Individual` / `Company` | identical (real `type`) | **MATCH** |
| 26 | `REGISTERED COMPANY NAME` | `REGISTERED LEGAL NAME` (real `legal_name`, prefilled) | **MATCH** (naming — correct for individuals too) |
| 27 | `VAT / TAX NUMBER` (one field) | **two** fields — `TAX ID / NIF *` (required) + `VAT NUMBER (OPTIONAL)` | **DIVERGENCE — build richer.** Tax ID and VAT number are genuinely different identifiers |
| 28 | `COUNTRY OF TAX RESIDENCE` `Ireland` | real `tax_country` (`PT`) | **MATCH** |
| 29 | `REGISTERED ADDRESS` | **absent here** — the structured address lives on Settings (page 16 item 16) | **DIVERGENCE — placement** |
| 30 | *(none)* | `Saved in your Tax Center; **a valid tax ID releases any held payout**. Your tax ID + VAT number are stored encrypted.` | **EXTRA** — states the consequence and the protection |
| 31 | `Save tax details` | present | **MATCH** |
| 32 | `PAYOUTS GO TO` + `Revolut Business` + `IBAN ···· 4821 · EUR` | identical shape, real `···· 1000` | **MATCH** |
| 33 | `Next payout €10,370` + `Estimated 3 Nov 2025` | `Next payout €885.60` (no estimate) | **MATCH** + same ratified refusal |
| 34 | `Protected changes` — `New accounts verify via micro-deposits before any payout switches.` | same sentence + **`— with your live banking connection.`** | **DIVERGENCE — honest.** ⚠️ The wireframe states it as current policy; the build marks it **pending**, so the owner is not told a protection is active when it isn't |
| 35 | `Changing the default holds payouts 48h as a security check.` | same + **`— with your live banking connection.`** | **DIVERGENCE — same honest qualifier** |
| 36 | `You'll get an email whenever payout details change.` | **identical, unqualified** | **MATCH** — this one *is* live (production email, so no qualifier needed) |
| 37 | `Payout not arriving?` + `Your account manager can trace any SEPA transfer **within 2 business days**.` | `Your account manager can trace a SEPA transfer **once the live rail is connected**.` | **DIVERGENCE — honest.** No SLA is promised, and tracing is correctly conditioned on a rail that isn't live |
| 38 | `Contact support` | present | **MATCH** |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ one real bank account (mod-97-valid, masked `···· 1000`, `LT`, `EUR`), real `€885.60` next payout, real `PT` tax country, real prefilled legal name |
| Stripe Connect | ✅ real onboarding action (test-mode per #71) |
| Bank CRUD | ✅ real `Save bank account` + `Remove` (`DELETE`) — ⚠️ **unverified-without-mutation** |
| Schedule form | ✅ real `frequency` / `payout_day` / `minimum` bound to stored prefs — ⚠️ **unverified-without-mutation** |
| Tax form | ✅ shares the ONE `owner.tax.save` path (page 9) — ⚠️ **unverified-without-mutation** (it would release a held payout) |
| Capability gating | `CAP_PAYOUT` — demo owner is primary and holds it; a suppressed state is **unverified without a second actor** |

### Verdict — page 24

**0 frontend gaps · 0 backend gaps.** 20 MATCH · 10 divergences (**5 ratified refusals**, 5 honest
qualifiers/placement) · 10 enhancements · 4 unverified.

⚠️ **The `Verified` badge and the `Verifying · 2 deposits sent` account are the page's whole argument.** The
wireframe shows two accounts stamped **`Verified`** and a third mid-micro-deposit with a working `Confirm`
button. None of it exists. ⚠️ **A `Verified` badge on a bank account is not decoration — it is the signal an
owner uses to believe money will arrive**, and a `Confirm` button on deposits that were never sent is a dead
control at the exact point where a user is most careful. The build shows the real account, no badge, and says
plainly that automated verification *"activates with your live banking connection."*

⚠️ **Items 34–35 are the subtler version of the same discipline.** The wireframe asserts two security
protections — micro-deposit verification before a payout switch, and a 48-hour hold on default changes — as
if they were live. The build keeps both sentences and appends **"— with your live banking connection."**
Telling an owner a safeguard is protecting them when it is not yet wired would be the most damaging kind of
false claim on a banking page. ⚠️ **And note item 36:** the email-on-change line carries **no** qualifier,
because that one genuinely is live — the qualifiers are applied per-capability, not sprayed across the card.


---

## Page 25 — `/owner/units/{id}` vs `Owner Unit Detail.html` — **DONE (browser-diffed)**

- **Wireframe:** hydrated, **2,691 chars** · **Live:** hydrated, **2,582 chars** (unit `A1`)
- **Screenshot:** `.captures/deep-owner-25-unitdetail-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Villa Azure` / `GUEST MESSAGES` / `Ana Ruiz · SparkleCare`;
  live rendered `A1` / the real attribution table / the real stays list.
- ⚠️ **Owner-scope proven incidentally:** a non-owner unit id (`01m0210r1faas…`, taken from the units table)
  returned **404**, so the id had to be sourced from the owner's own page. The scope is doing its job.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Breadcrumb `My units / Villa Azure` | `‹ My units / A1` | **MATCH** |
| 2 | H1 + `Active` chip | `A1` + `Active` | **MATCH** |
| 3 | Sub `2-bed apartment · Seaside Villas · Faro` | in `UNIT DETAILS` (`Room type` / `Type`) | **DIVERGENCE — placement** |
| 4 | `Edit pricing` | present → the real per-unit rate form | **MATCH** + wired |
| 5 | `Sync calendars` + `Calendars not synced yet` | **absent** — replaced by `Channel sync is managed by your operator` | **DIVERGENCE — RATIFIED refusal (#37).** No owner-side channel sync exists; the build states who owns it instead of offering a dead button |
| 6 | `Share listing` | **absent** | **DIVERGENCE — unmodelled** |
| 7 | KPI `OCCUPANCY 82%` + `+6%` | `16%` + `October 2026`; delta absent | **MATCH** + the same ratified vs-LY refusal (pages 10/11) |
| 8 | KPI `NIGHTS SOLD` | present | **MATCH** |
| 9 | KPI `REVENUE · SEP €4,120` | `NET · OCTOBER 2026 €492.00` + `Attributed to you` | **MATCH** + **EXTRA** — ⚠️ the build says **net attributed**, not gross "revenue"; on an owner surface those differ |
| 10 | `Availability · September` + `Tap a day to block` | `Availability · October 2026` + `Tap an open day to block` + `‹` / `›` month nav | **MATCH** + **EXTRA** |
| 11 | Summary `7 booked · 0 blocked · 23 open` | `5 booked · 0 blocked · 26 open` | **MATCH** (real) |
| 12 | Weekday header + day grid + legend `Booked / Open / Blocked` | identical | **MATCH** |
| 13 | *(none)* | Inline **block composer** — real `unit_id` / `first_night` / `last_night` / `reason` (`Personal stay` / `Maintenance` / `Seasonal hold`) + `Block day` / `Cancel` | **EXTRA** — and ⚠️ this is the composer that was `UNVERIFIED-WITHOUT-INTERACTION` on page 7; **here it renders in full without any interaction**, confirming the reason options are real |
| 14 | *(none)* | `Blocked days hold the unit off-sale. Manage all blocks on your Calendar.` + link | **EXTRA** |
| 15 | `PRICING & AVAILABILITY` + `Live` + `Guests can book instantly` | `Open for bookings` + `Pause to stop taking new bookings — your existing stays are kept.` | **MATCH** + **EXTRA** — the real #69 pause/resume, and it states the effect on existing stays |
| 16 | `Nightly rate €140` | `€180.00` | **MATCH** (real) |
| 17 | `Weekend uplift +15%` | `None` | **MATCH** — a real unset value, not a fabricated tier |
| 18 | `Minimum stay 2 nights` | `None` | **MATCH** (real unset) |
| 19 | `Revenue · 6 months` + per-month bars | identical shape, real values incl. genuine `€0.00` months | **MATCH** — ⚠️ zero months are shown, not smoothed away |
| 20 | `Occupancy calendar` `2025` + `All / Bookings / Blocked` tabs + `avg 72% · Peak in Jul at 96%` + 12 months | `Occupancy · 12 months` + `avg 7% · peak 23% in Jul` + 12 months | **MATCH**; the `All/Bookings/Blocked` tab set is **DIVERGENCE — minor** |
| 21 | *(none)* | **`Attribution · October 2026`** — `Room revenue +€600.00` / `OTA cost +€0.00` / `Commission −€108.00` / `Net €492.00` | **EXTRA — substantial.** ⚠️ **Reconciles exactly (#17):** 600.00 + 0.00 − 108.00 = **492.00**, and matches KPI item 9 |
| 22 | `Upcoming bookings` / `All bookings` tabs | `Stays · October 2026` + `Bookings on this unit that touch this month` | **MATCH** (naming) |
| 23 | Booking rows show **guest names** (`Lars Andersen`, `H. Nakamura`), guest counts, channel **brands**, amounts | columns `REFERENCE / STAY / NIGHTS / SOURCE / STATUS / YOUR NET` — **no guest name at all**, real refs (`R-26HNQTYL`), real `source` enum | **DIVERGENCE — RATIFIED (#28) + brand refusal.** ⚠️ Stricter even than the calendar (page 7), which shows a name; here the stay is identified by **reference only** |
| 24 | *(none)* | `Not yet attributed` on the stay with no owner-ledger line | **EXTRA** — the same honest state as page 4 |
| 25 | `Cleaning schedule` + `2 upcoming` | `CLEANING SCHEDULE` + real `Departure clean · 9 Sep 2026 · upcoming · Scheduled` | **MATCH** (real `HousekeepingTask`) |
| 26 | Named cleaner card — `AR Ana Ruiz · SparkleCare · ★ 4.9` + `Message` | **absent** — replaced by `Coordinated by your management team — message them below to arrange anything.` | **DIVERGENCE — RATIFIED refusal (#66).** ⚠️ A named cleaner with a star rating is a **person and a review score the system does not hold** |
| 27 | Cleaning rows with time windows (`11:00–14:00`) + `Done` / `Confirmed` / `Scheduled` | date + `Scheduled` only | **DIVERGENCE — no time window modelled** |
| 28 | `Schedule a cleaning` action | **absent** | **DIVERGENCE — honest.** Owners do not schedule housekeeping; the build routes to the operator instead (item 26) |
| 29 | `Reviews & rating` `4.9` `47 reviews` + `↑ +0.1 vs Aug` | `4.0` `3 reviews · published` | **MATCH** (real); the vs-prior delta absent — same ratified refusal |
| 30 | 6 category sub-scores (`Cleanliness / Check-in / Communication / Location / Value / Accuracy`) | **absent at unit level** (the 4 real categories render portfolio-wide on `/owner/reviews`) | **DIVERGENCE — placement/granularity.** Per-unit category breakdown is not computed |
| 31 | Review cards with reviewer **country** (`France`, `United Kingdom`) | name-abbreviated + rating + body + month, **no country** | **MATCH** + **DIVERGENCE — RATIFIED (#28).** Reviewer location is guest PII |
| 32 | `THIS MONTH €4,120 · 82% occupancy · 24 nights sold` | covered by the KPI row (item 7–9) | **MATCH** (placement) |
| 33 | `Best performer of your 3 units` | **absent here** — the real `BEST PERFORMER` card is on `/owner/units` (page 3) | **DIVERGENCE — placement** |
| 34 | `UNIT DETAILS` — `Type` / `Sleeps` / `Base rate` / `Cleaning fee` / `Commission` | `Code` / `Room type` / `Type` / `Sleeps` / `Listing Active` | **MATCH** + **EXTRA** (`Code`, `Room type`); `Cleaning fee €45` **absent** — **DIVERGENCE, same refusal as page 19 item 30** (no per-unit cleaning fee modelled) |
| 35 | `Commission 15%` | **`COMMISSION 18%`** + `Agreement →` + `Portfolio-wide terms, in effect today.` | **DIVERGENCE — RATIFIED.** The real rate, ⚠️ **the sixth page** where the wireframe's 15% would be wrong money — plus a link to the agreement it comes from |
| 36 | `DEDUCTIONS · SEP` — `Management commission · 15% −€618` / `Cleaning · ×6 −€270` / `Maintenance −€95` + `Full statement` | covered by the real `Attribution` table (item 21) | **MATCH** in role — the build's version reconciles; the wireframe's three lines do not sum to anything shown |
| 37 | `CHANNEL MIX · SEP` — `Direct €1,648 40%` / `Airbnb 35%` / `Booking.com 25%` | `CHANNEL MIX · OCTOBER 2026` — `Direct 3 nights · 60%` / `Corporate 2 nights · 40%` + `Share of this unit's room-nights by booking source.` | **MATCH** + **DIVERGENCE** (real sources, room-nights basis stated) |
| 38 | `Direct bookings **saved you €247** in commission` | **absent** | **DIVERGENCE — honest refusal.** A counterfactual saving ("what you'd have paid on an OTA") requires an OTA commission rate the system has no basis for |
| 39 | `PAYOUT TIMELINE` — per-unit payouts with receipt refs (`RCP-2025-09`) + `Expected 3 Nov 2025` | **absent** — replaced by `YOUR MONEY` (`Balance` / `Payouts` links) + **`Payouts are made per statement across your whole portfolio, not per unit.`** | ⚠️ **DIVERGENCE — RATIFIED refusal, and structurally important.** Payouts are portfolio-level; a **per-unit payout timeline would be a fiction about how money actually moves**, not just a wrong number |
| 40 | **`GUEST MESSAGES`** `2 unread` — guest names, **message bodies**, arrival dates, channel | **absent** — replaced by `MESSAGE YOUR MANAGEMENT TEAM` + **`About this unit — bookings and guest contact stay with your operator.`** + `Send message` / `Open inbox →` | ⚠️ **DIVERGENCE — RATIFIED refusal (#28), the most important on the page.** See the verdict |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Owner scope | ✅ **proven** — a non-owner unit id 404s |
| Real seeded data | ✅ real occupancy, real `€492.00` net, 2 real stays, 3 real reviews, a real housekeeping task, real 18% commission |
| Attribution reconciles | ✅ `600.00 + 0.00 − 108.00 = 492.00` (#17), matching the KPI |
| `Edit pricing` / `Calendar` / `Agreement →` / `Balance` / `Payouts` / `Open inbox →` | ✅ all resolve to real routes |
| Block composer | ⚠️ **unverified-without-mutation — deliberately** (it writes inventory) |
| `Pause` toggle + `Send message` | ⚠️ **unverified-without-mutation** |
| PII posture | ✅ **no guest name, no guest message, no reviewer country** anywhere on the page |

### Verdict — page 25

**0 frontend gaps · 0 backend gaps.** 17 MATCH · 16 divergences (**7 ratified refusals**, 9 placement/
granularity/minor) · 6 enhancements · 3 unverified.

⚠️ **Item 40 is the page's most consequential refusal.** The wireframe puts a **`GUEST MESSAGES` card with
guest names, verbatim message bodies, arrival dates and booking channels** on an owner screen. That is exactly
the perimeter #28 exists to hold — guest contact and correspondence belong to the operator, not the owner. The
build replaces it with a message channel **to the management team**, and says so: *"bookings and guest contact
stay with your operator."* ⚠️ **This page also goes further than the calendar did** — its stays table
identifies bookings by **reference only**, with no guest name at all.

⚠️ **Item 39 is a different kind of refusal, and worth naming separately.** The wireframe shows a **per-unit
payout timeline** with receipt references. Payouts in this system are made **per statement across the whole
portfolio** — so that card would not merely carry wrong numbers, it would teach the owner a false model of how
their money moves. The build states the real model in one line and links to the real Balance and Payouts pages.

⚠️ **Item 26 — the named cleaner (`Ana Ruiz · SparkleCare · ★ 4.9`) — is a person and a rating the system does
not hold.** The build shows the real housekeeping task and names no one.


---

## Page 26 — `/owner/bookings/{id}` vs `Owner Booking Detail.html` — **DONE (browser-diffed)**

- **Wireframe:** hydrated, **1,045 chars** · **Live:** hydrated, **1,181 chars**
- **Screenshot:** `.captures/deep-owner-26-bookingdetail-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `Lars Andersen` / `l.andersen@icloud.com` / `Net to you
  €1,272`; live rendered `Demo Booker` / `R-L97DIU31` / the honest unattributed state.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | Breadcrumb `Overview / Bookings / Lars Andersen` | `‹ Overview / Bookings / Demo Booker` | **MATCH** |
| 2 | Avatar + guest name + status chip `Confirmed` | `DB` + `Demo Booker` + `Upcoming` | **MATCH** (real status) |
| 3 | Sub `Villa Azure · 5 nights · Booking.com` | `A1 · 2 nights · Direct` | **MATCH** (real unit + real `source`) |
| 4 | `Reservation` + ref `VY-25-4471` | `Reservation` + real ref `R-L97DIU31` | **MATCH** |
| 5 | `CHECK-IN` `9 Oct 2025 · Thu` + `from 3:00 PM` | `18 Aug 2026 · Tue` + `from 3:00 PM` | **MATCH** |
| 6 | `5 nights` band | `2 nights` | **MATCH** |
| 7 | `CHECK-OUT` + `by 10:00 AM` | `20 Aug 2026 · Thu` + `by 11:00 AM` | **MATCH** (real property times) |
| 8 | `UNIT` / `GUESTS` / `NIGHTS` / `NIGHTLY RATE €301` | `A1` / `2 guests` / `2` / **`—`** | **MATCH** — ⚠️ the rate shows `—` because this stay is not yet attributed (item 10), not a zero |
| 9 | `Your earnings` + `gross → net` | identical heading | **MATCH** |
| 10 | Earnings breakdown — `Accommodation · 5 × €301 €1,505` / `Cleaning fee collected €45` / `Gross booking value €1,550` / `Management commission · 15% −€233` / `Cleaning · paid to housekeeping −€45` / `Net to you €1,272` | **`Not yet attributed.`** + `Your earnings appear here once this booking is attributed to your ledger — usually after check-out.` | **DIVERGENCE — honest state, not a gap.** The stay is in the future; no ledger lines exist. ⚠️ The build **explains when the figure will appear** instead of computing a provisional one |
| 11 | `Management commission · **15%**` | *(not shown — unattributed)* | ⚠️ **the seventh page** on which the wireframe's hardcoded 15% would have been wrong money (real rate 18%) |
| 12 | `Guest` section + avatar + name | identical | **MATCH** |
| 13 | `Repeat guest · 2nd stay` | `Repeat guest · 9 stays` | **MATCH** (real count) |
| 14 | **`Email l.andersen@icloud.com`** | **absent** | ⚠️ **DIVERGENCE — RATIFIED refusal (#28). The clearest one in the audit** |
| 15 | **`Phone +45 20 55 18 04`** | **absent** | ⚠️ **DIVERGENCE — RATIFIED refusal (#28)** |
| 16 | **`Country Denmark`** | **absent** | **DIVERGENCE — RATIFIED refusal (#28)** |
| 17 | `Message` **`Lars`** (message the **guest**) | **`Message your team`** → `/owner/messages` | **DIVERGENCE — RATIFIED refusal.** Owner↔guest messaging does not exist; the owner talks to the operator |
| 18 | *(none)* | `Guest contact details are handled by your management team — you see the booking and your earnings.` | **EXTRA — the refusal, stated to the owner** rather than silently omitted |
| 19 | `Timeline` — `Booking received 12 Sep 2025` | `Booking received 15 Aug 2026` | **MATCH** (real) |
| 20 | Timeline `Confirmed 12 Sep 2025` | folded into the status chip (item 2) | **DIVERGENCE — minor** |
| 21 | Timeline `Payment received · Paid in full` | **`Payment · Not yet received`** | **MATCH** — real state, and the honest inverse |
| 22 | Timeline `Pre-arrival email · Scheduled Mon 6 Oct` | **absent** | **DIVERGENCE — unmodelled on this surface** (pre-arrival mail is operator-side) |
| 23 | Timeline `Check-in Thu 9 Oct · 3:00 PM` | `Check-in Tue 18 Aug · 3:00 PM` | **MATCH** |
| 24 | `NET TO YOU €1,272` + `for this 5-night stay` | **`—`** + `for this 2-night stay` + `Not yet attributed` + `This booking has not been attributed to your ledger yet.` | **MATCH** in shape; **DIVERGENCE — honest.** An em-dash, not a provisional number |
| 25 | `Scheduled` + **`Releases with your next payout after check-out, on 3 Nov 2025.`** | `Not yet attributed` + the explanation | **DIVERGENCE — RATIFIED refusal.** ⚠️ A **specific release date** on a specific stay is the same invented-date refusal as pages 2/24 — and here it would also imply per-stay payout timing, which page 25 item 39 established is not how payouts work |
| 26 | `BOOKING DETAILS` — `Confirmation` / `Source` / `Channel` / `Rate plan Flexible` / `Booked on` | `Confirmation R-L97DIU31` / `Source Direct` / `Channel Vyrobel direct` / `Booked on 15 Aug 2026` | **MATCH** (4 of 5); `Rate plan` **DIVERGENCE — not surfaced** |
| 27 | `Channel Booking.com` | `Vyrobel direct` | **MATCH** (real; the OTA brand refusal again) |
| 28 | `ACTIONS` — `Download confirmation` | present → real route | **MATCH** + wired |
| 29 | `View unit` | `View A1` → the real unit-detail page | **MATCH** + wired |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ real reference, real dates, real unit, real source, real 9-stay repeat count |
| Real property check-in/out times | ✅ `3:00 PM` / `11:00 AM` — the property's own, not a constant |
| Attribution state | ✅ genuinely unattributed (future stay); the page says so rather than estimating |
| `Download confirmation` | ✅ real route |
| `View A1` / `Message your team` | ✅ resolve to the real unit page and inbox |
| PII posture | ✅ **name only** — no email, no phone, no country anywhere |

### Verdict — page 26

**0 frontend gaps · 0 backend gaps.** 17 MATCH · 10 divergences (**5 ratified refusals**, 5 minor/unmodelled) ·
2 enhancements · 0 unverified.

⚠️ **Items 14–17 are the sharpest refusal in the entire audit.** The wireframe's Guest card prints the guest's
**email address, phone number and country**, plus a `Message Lars` button — full contact PII for a third party,
on a surface belonging to someone who is not the operator. Invariant #28 exists precisely for this, and a test
asserts the perimeter from the outside. The build shows the **name only**, and tells the owner why:
*"Guest contact details are handled by your management team — you see the booking and your earnings."*

⚠️ **Item 10 is the quieter discipline.** The stay is in the future and has no ledger lines, so the wireframe's
entire gross→net breakdown has nothing behind it. The build could easily have computed a provisional figure
from the rate and the commission — instead it shows `Not yet attributed` and **explains when the number will
become real**. ⚠️ Every money field on the page (`NIGHTLY RATE`, `NET TO YOU`) is an em-dash rather than an
estimate — which is why item 11's absent 15% never had a chance to be wrong here.


---

## Page 27 — `/owner/statements/{id}` vs `Owner Statement.html` — **DONE (browser-diffed)**

- **Wireframe:** hydrated, **2,451 chars** · **Live:** hydrated, **1,518 chars** (statement `September 2026`)
- **Screenshot:** `.captures/deep-owner-27-statement-live.png`
- **Both-sides probe:** PASSED — wireframe rendered `€10,370.00` / `28 statements` / `Payment & ops fees`;
  live rendered `€885.60` / 6 real statements / the real by-unit split.

### Element-by-element

| # | Wireframe element | Live | Verdict |
|---|---|---|---|
| 1 | H1 `Statement` + period `September 2025` | `Statement` + `September 2026` | **MATCH** |
| 2 | Hero `SEPTEMBER 2025` | `SEPTEMBER 2026` | **MATCH** |
| 3 | `Seaside Villas · 3 units` | `2 units` | **MATCH** (real count) |
| 4 | Status chip `Payout pending` | **identical** | **MATCH** (real) |
| 5 | `Net payable €10,370.00` | `€885.60` | **MATCH** (real) |
| 6 | *(none)* | **`Provisional`** + `Some lines are pending channel reconciliation.` | **EXTRA** — the same honest qualifier as the archive (page 23 item 14) and Balance (page 5) |
| 7 | `Breakdown` heading | identical | **MATCH** |
| 8 | `Gross room revenue` + `64 nights · 3 units` + `+€12,881.99` | `8 nights · 2 units` + `+€1,080.00` | **MATCH** (real) |
| 9 | `Management commission` **`18% of gross revenue`** `−€2,318.76` | **`18% of gross room revenue`** `−€194.40` | **MATCH** — ⚠️ **this wireframe says 18%**, agreeing with the build (see the verdict) |
| 10 | `Payment & ops fees` + `Card processing & platform` `−€193.23` | **absent** | **DATA-DEPENDENT, not missing.** #70 built the `payment_fee` bucket; no writer posts fees yet, so it is a genuine €0 and the line is omitted rather than faked (identical to page 2 item 16) |
| 11 | *(none)* | `OTA commission` **`Provisional`** + `0% of gross revenue` `+€0.00` | **EXTRA** — a real €0 line that is shown *and* flagged as pending reconciliation |
| 12 | `Net payable €10,370.00` (footer) | `€885.60` | **MATCH** — ⚠️ **reconciles exactly (#17):** 1,080.00 − 194.40 + 0.00 = **885.60** |
| 13 | `NEXT PAYOUT €10,370` | `€885.60` + `Payout pending` | **MATCH** |
| 14 | **`Est. 3 Nov 2025`** | **absent** — replaced by `Held until your tax details are on file.` | **DIVERGENCE — RATIFIED refusal.** No estimated date is invented; instead the build states the **real reason** the payout is held |
| 15 | `IBAN ···4821` | `IBAN ···· 1000` | **MATCH** (real masked, CAP_PAYOUT-gated) |
| 16 | `OCCUPANCY 71%` `+8%` + spark `Mar → Sep` | `13%` `+10%` + spark `Apr → Sep` | **MATCH** — ⚠️ the vs-prior delta **is** shown here, because real prior-period data exists (contrast pages 10/11, where it is correctly withheld) |
| 17 | `Download statement` | present → real PDF | **MATCH** + wired |
| 18 | `MORE DOCUMENTS` — `Remittance advice · RA-2025-09 · PDF` | `Remittance advice` + `RA · September 2026 · PDF` → real payout route | **MATCH** + wired (the invented `RA-YYYY-MM` ref is the ratified refusal from page 8) |
| 19 | `Booking summary · 18 bookings · PDF` | `Booking summary` + `The bookings behind this statement · PDF` | **MATCH** + wired; booking **count** not surfaced — **DIVERGENCE, minor** (same as page 8 item 17) |
| 20 | `Statement history` + filters `All / 2025 / 2024 / 2023` | `Statement history` + `All / 2026` | **MATCH** — filters derived from real years |
| 21 | `Export CSV` / `Export PDF` | both present → real routes | **MATCH** + wired |
| 22 | `28 statements · €221,500 net total` | `6 statements · €3,690.00 net total` | **MATCH** (real; the 3-year history is the fabrication refused on pages 8/9/23) |
| 23 | History cols `MONTH / GROSS / NET PAYABLE / STATUS` | **identical** | **MATCH** |
| 24 | History rows with `N nights · N bookings` sub-line | rows with the **date range** (`1 Sep – 30 Sep 2026`) | **DIVERGENCE — minor** (period instead of counts) |
| 25 | 28 history rows | 6 real rows, each linking to its statement **+ a per-row PDF link** | **MATCH** + **EXTRA** (12 working links) |
| 26 | Row statuses `Pending` / `Paid` | `Payout pending` / `Paid` | **MATCH** |
| 27 | `By unit` + `September 2025 · 3 units` | `By unit` + `September 2026 · 2 units` | **MATCH** |
| 28 | Per-unit rows — name · nights · occupancy · net · `gross · % of net` | `A1 · 3 nights · 10% occupancy · €442.80 · €540.00 gross · 50% of net`; `A2 · 5 nights · 17% occupancy · €442.80 · €540.00 gross · 50% of net` | **MATCH** + **EXTRA** (each unit name links to its real unit page) |
| 29 | *(implied total)* | **`Total net earnings €885.60`** | **EXTRA** — ⚠️ and it **reconciles**: 442.80 + 442.80 = **885.60**, equal to the headline net (item 5) and the breakdown (item 12) |

### Backend wiring (non-destructive)

| Check | Result |
|---|---|
| Real seeded data | ✅ real period, real gross/commission/net, 6 real statements, real 2-unit split |
| **Reconciliation (#17) — twice** | ✅ breakdown `1,080.00 − 194.40 + 0.00 = 885.60`; by-unit `442.80 + 442.80 = 885.60` — **both equal the headline** |
| Statement PDF | ✅ real route (verified page 8: `881,691 B`) |
| Remittance + Booking-summary PDFs | ✅ real routes (verified page 8) |
| History `Export CSV` / `Export PDF` | ✅ real routes (verified page 10) |
| 6 history rows + 6 per-row PDFs + 2 unit links | ✅ all real ULID routes |
| Hold reason | ✅ the **real** tax-hold, consistent with pages 2/5/9 |
| PII posture | ✅ no guest data anywhere on the statement |

### Verdict — page 27

**0 frontend gaps · 0 backend gaps.** 20 MATCH · 4 divergences (1 ratified refusal, 3 minor) ·
4 enhancements · 1 data-dependent · 0 unverified.

⚠️ **A find worth recording about the prototypes themselves: the wireframe set contradicts itself on the
commission rate.** This statement wireframe states **`18% of gross revenue`** and its arithmetic confirms it
(`12,881.99 × 0.18 = 2,318.76`). But `Owner Reports`, `Owner Settings`, `Owner Agreement`'s fee line and
`Owner Add Unit` all state **15%**. ⚠️ **The build is at 18% everywhere, matching the real
`ManagementAgreement` — and matching this wireframe.** So the seven 15% instances flagged across this audit
are not a design decision the build ignored; they are **prototype errors, and one prototype already had it
right.**

⚠️ **This page is the cleanest reconciliation in the audit.** The same €885.60 is derived three independent
ways — headline, breakdown (`gross − commission + OTA`), and by-unit sum — and all three agree to the cent.

---

# ✅ FINAL ROLL-UP — 28 of 28 pages browser-diffed · AUDIT COMPLETE

**Every owner page in the ledger was opened in Playwright, rendered/hydrated, screenshotted, and diffed
element-by-element against its wireframe.** No page was marked DONE on a curl, a 200, or a route check.

| Bucket | Across all 28 pages |
|---|---|
| **BACKEND gaps (renders but dead)** | **0 — none found on any page** |
| **FRONTEND gaps (candidates)** | **6** — all minor-to-moderate, none blocking |
| **Unratified product calls** | **2** (`PEAK` column · the 3 owner notification types) |
| **Honesty flags** | **1 confirmed** (`/owner/reviews`) + **1 minor residual** (`/owner/help`) |
| **DIVERGENCES** | **~215** — of which **~90 are deliberate honest refusals** |
| **ENHANCEMENTS (extra in build)** | **150+**, none flagged |
| **AUDITOR ERRORS caught + corrected** | **1** (the page-10 ZIP verdict) |

## The six candidate frontend gaps — the entire actionable list

| # | Page | Element | Impact | Note |
|---|---|---|---|---|
| 1 | `/owner/tax` | No `Other` / catch-all in the 17-country residency select | ⚠️ **Highest.** `Tax ID` is required, so an owner outside those 17 **cannot complete the form — and their payout hold can never be released** | Looks like an oversight in the list, not a decision |
| 2 | `/owner/insights` | Per-unit filter (`All villas` / per-unit) | Moderate — the only **confirmed capability-absence** in the audit | Per-unit data already exists; this is page-scoping |
| 3 | `/owner/settings` | 3 owner notification types (`New booking`, `Guest message`, `Review posted`) | Moderate | `new_booking` exists but is `audience: operator`; the other two don't exist |
| 4 | `/owner/units` | `Approve all` bulk control | Low — possibly deliberate (bulk-approving money) | Unratified |
| 5 | `/owner/expenses` | `N photos` badge on approval items | Low | Polymorphic `media` exists; no capture path |
| 6 | `/owner/messages` | `Copy text` per-message action | Lowest — purely client-side | No backend needed |

**Resolved during the audit:** the `/owner/units` agreement-renewal `Review & e-sign` item was downgraded from
a candidate gap once page 19 showed e-signature is **explicitly and visibly refused** across the agreement
surface.

## What the audit actually found

⚠️ **The dominant pattern, on 28 of 28 pages, is the build refusing to fabricate.** The prototypes invent, and
the build declines: a **`Suspicious login blocked`** security incident · a **24/7 emergency phone number** ·
a **support desk, email and WhatsApp** · a **ticket queue** with invented references · **guest email, phone and
country** on a booking · a **`GUEST MESSAGES` card** with message bodies · **`Verified` bank-account badges**
and a micro-deposit `Confirm` · a **per-unit payout timeline** · a **named cleaner with a star rating** ·
**VAT `payable to Autoridade Tributária`** with a filing calendar and `Mark filed` · a **stored Visa card** ·
**two OTAs marked `Connected`** · a **`Guests · contacts & lifetime value` export** · **7-year retention** ·
**auto-approval after 7 days** · **comp-set market positioning** · forward **payout projections** · estimated
**release dates** · **FX currencies** · and a **take-home estimate computed at the wrong commission rate**.

⚠️ **On money, the build reconciles wherever it was checkable** — Reports gross→net, the VAT quarters, the
Balance clearing identity, the statement breakdown, the by-unit split, and the unit-detail attribution all
tie to the cent (#17).

⚠️ **And a finding about the prototypes:** they disagree with each other on the commission rate. Seven pages
show **15%**; `Owner Statement` shows **18%** and its own arithmetic proves it. The build is **18% everywhere**,
matching the real `ManagementAgreement`. **The 15% instances are prototype errors — and would have been wrong
money on every page that displayed them.**

## Method notes worth keeping

1. ⚠️ **A DOM absence is not a capability absence.** Three times the rendered page showed nothing where the
   wireframe had something, and reading the source showed the feature **built and data-gated** (Documents year
   pills · Insights proposal CTA · the calendar composer, which page 25 then rendered in full).
2. ⚠️ **The auditor is not exempt from the evidence rule.** I turned *"absent from Reports"* into *"the build
   has no ZIP capability"* and wrote a confident verdict on it. One fetch disproved it. Corrected in place.
3. ⚠️ **The inventory itself can be wrong.** `Owner Notifications.html` was mapped to the feed; it is the
   **preferences** wireframe. Diffing it against the feed would have produced a page of phantom gaps.
4. ⚠️ **Read the DOM the way the view emits it.** On Messages, every control is an icon button — a text-based
   read returns nothing on *either* side. `aria-label` reduced a whole phantom toolbar to one real item.
5. ⚠️ **Over-crediting is as wrong as under-crediting.** I nearly filed `✓ Delivered` as an honest refusal; the
   receipt is fully real and two-state.
6. ⚠️ **Multi-step wireframes render one step at a time.** Onboarding (3) and Add-unit (4) needed driving
   through on the prototype side, or the diff would have covered a quarter of the flow.

**Non-destructive throughout:** no code changed; `rate_calendar` 929 rows / 10,920,000 minor · 0 unit
overrides · 41 reservations · 6 statements — unchanged from start to finish.


---

# ADDENDUM — INVENTORY RE-VERIFICATION (post-completion pass)

⚠️ The audit closed at 28/28, but the brief states the page list is a **LOWER BOUND**. So on the following run
I did not re-diff anything — I tested **the inventory itself** against `route:list` and against the wireframe
directory. **Two things the 28-row ledger did not account for**, plus one class of artifact it never covered.

## 1. `/owner/messages/{thread}` — RESOLVED, not a 29th page

`route:list` shows a GET route `owner/messages/{thread}` that appears nowhere in the ledger. Opened in
Playwright, it **302s to `/owner/messages?c={id}`** and lands on the same hydrated two-pane view already
diffed as page 14 (`h1: Messages`, thread header `Demo Staff`, conversation list present, 971 chars).

**Verdict: a canonicalising redirect alias, not a distinct surface.** No separate diff is owed, and the ledger
count of 28 stands. ⚠️ Recorded rather than silently dropped — an unexplained route in the route file is
exactly the kind of thing a "complete" claim should have to answer for.

## 2. `/owner/account/email/verify/{user}/{hash}` — **UNVERIFIED, with the reason**

A GET route absent from the ledger. It is **not audited, and deliberately so**:

- it is a **signed** URL — it cannot be opened without a valid signature generated by a real email send; and
- opening it with a valid signature **mutates**: it completes the pending login-email change (page 16 item 10).

⚠️ **This is UNVERIFIED, not MISSING and not DONE.** It is a token-gated transactional endpoint, so the
audit's own rules put it out of reach of a read-only pass; verifying it needs a mail-triggered flow this audit
refuses to run. It has **no wireframe**, so there would be nothing to diff even if it were reachable.

## 3. Five owner wireframes exist for artifacts that are NOT pages — **NOT audited**

`docs/design/raw/` contains five `Owner *.html` prototypes with no ledger row, because they are not portal
pages:

| Wireframe | Artifact | Status |
|---|---|---|
| `Owner Statement PDF.html` | statement PDF | **not visually diffed** |
| `Owner Booking Summary PDF.html` | booking-summary PDF | **not visually diffed** |
| `Owner Remittance Advice PDF.html` | remittance PDF | **not visually diffed** |
| `Owner Annual Tax Summary PDF.html` | annual tax PDF | **not visually diffed** |
| `Owner Invite Email.html` | owner-invite email | **not visually diffed** |

⚠️ **All four PDFs were fetched and proven real during the wiring checks** (pages 8, 10, 19, 23 — correct
content-type, real byte sizes, and the archive ZIP's PK signature). ⚠️ **But "the route returns a real PDF" is
not "the PDF matches its wireframe"** — that is precisely the curl-is-not-a-diff distinction this whole pass
exists to enforce, and I am applying it to myself here. Their **rendered content has never been compared to
these prototypes.**

**They are out of scope for a page audit and remain genuinely un-audited.** A PDF/email parity pass would need
a different harness (render the PDF, or open the Mailable's HTML) and is a separate piece of work.

## Corrected completion statement

**28 of 28 owner PAGES browser-diffed — complete, and the inventory has now been checked against the route
file rather than trusted.** Outside that set and still unaudited: **1 signed mutating endpoint** (unverifiable
read-only) and **5 non-page artifacts** (4 PDFs + 1 email) whose wireframes exist but whose rendered output was
never diffed.


---

# ADDENDUM 2 — THE NON-PAGE ARTIFACTS · PDF PASS (partial)

⚠️ **CORRECTION TO ADDENDUM 1.** I wrote that a PDF parity pass *"would need a different harness … and is a
separate piece of work."* **That was too pessimistic — the harness took one step to build.** The PDFs' real
text layer extracts cleanly from their own content streams (`zlib` + UTF-16BE decode), so the four PDFs are
diffable after all. Recording the correction, then doing the work.

## Harness — proven, with its limits stated

| Instrument | Result |
|---|---|
| Playwright renders a PDF | ❌ **No.** Navigating to a PDF route **downloads** it (`content-disposition: attachment`); Chromium never renders it, so there is no DOM to read |
| `pdftotext` / `mutool` / `qpdf` / `pypdf` | ❌ none installed |
| **Custom extraction** (`zlib.decompress` on content streams → UTF-16BE text tokens) | ✅ **Works** — statement PDF yields readable text incl. `OWNER STATEMENT`, `€885.60`, the full breakdown |
| Wireframe side | ✅ `Owner Statement PDF.html` hydrated, **1,181 chars** of real copy |

⚠️ **Method limit, stated plainly: this diffs the PDF's TEXT LAYER, not its visual rendering.** Layout,
typography, colour and image placement are **not** covered. A verdict below therefore speaks to content and
copy only — it is weaker evidence than the browser-rendered page diffs, and is labelled as such.

## Artifact A — Statement PDF vs `Owner Statement PDF.html` — **DIFFED (text layer)**

| # | Wireframe element | Live PDF | Verdict |
|---|---|---|---|
| 1 | Letterhead `V Seaside Villas` + address + `managed with Vyrobel` | `V Vyrobel Demo Hospitality, Lda` + `PT · managed with Vyrobel` | **MATCH** (real; street address absent — owner has none saved) |
| 2 | `OWNER STATEMENT` | identical | **MATCH** |
| 3 | Reference **`OS-2025-09`** | **`OS-2026-09`** | ⚠️ **MATCH — and it corrects a page-8 note.** On `/owner/documents` I recorded the `OS-YYYY-MM` reference as *"a ratified refusal on this surface."* **It is not refused on the PDF — it is generated and printed.** The refusal is page-scoped, not build-wide |
| 4 | Period `September 2025` | `September 2026` | **MATCH** |
| 5 | `Amara Okafor · Seaside Villas · 3 units · issued 1 Oct 2025` | `Demo Owner · Vyrobel Demo Hospitality, Lda · 2 units · issued 15 Aug 2026` | **MATCH** (real) |
| 6 | `NET PAYABLE TO OWNER €10,370.00` | `€885.60` | **MATCH** |
| 7 | `Payout pending · **est. settlement 3 Nov 2025** · IBAN ····4821` | `Payout pending · IBAN ···· 1000` | **DIVERGENCE — RATIFIED refusal.** The estimated settlement date is dropped, consistent with pages 2/24/26/27 |
| 8 | Stats `Occupancy 71%` / `Nights sold 64` / `Avg. nightly rate €201.28` | `13%` / `8` / `€135.00` | **MATCH** — ⚠️ ADR checks out: 1,080.00 ÷ 8 = **€135.00** |
| 9 | `Breakdown` + cols `ITEM / DETAIL / AMOUNT` | identical | **MATCH** |
| 10 | `Gross room revenue` `64 nights · 3 units` `+€12,881.99` | `8 nights · 2 units` `+€1,080.00` | **MATCH** |
| 11 | `Management commission` `18% of gross revenue` `−€2,318.76` | `18% of gross **room** revenue` `−€194.40` | **MATCH** — ⚠️ **both at 18%**, as on the Statement page wireframe (page 27 item 9) |
| 12 | `Payment & ops fees` `Card processing & platform` `−€193.23` | **absent** | **DATA-DEPENDENT** — genuine €0 (#70), omitted not faked; identical to pages 2 & 27 |
| 13 | *(none)* | `OTA commission (provisional)` `0% of gross revenue` `+€0.00` | **EXTRA** — a real €0 line, flagged provisional |
| 14 | `Net payable €10,370.00` | `€885.60` | **MATCH** — ⚠️ **reconciles: 1,080.00 − 194.40 + 0.00 = 885.60** |
| 15 | `Performance by unit` + cols `UNIT / NIGHTS / OCCUPANCY / REVENUE` | identical | **MATCH** |
| 16 | 3 unit rows + `Total gross room revenue 64 71% €12,881.99` | `A1 · One-Bedroom Apartment 3 10% €540.00`, `A2 … 5 17% €540.00`, `Total 8 13% €1,080.00` | **MATCH** — ⚠️ **540 + 540 = 1,080** |
| 17 | Payout paragraph — `settles to Revolut Business (IBAN ···· 4821) by bank transfer, **estimated 3 Nov 2025**` | same wording, `settles by bank transfer to IBAN ···· 1000.` — **no date** | **DIVERGENCE — same ratified refusal** |
| 18 | `Management commission is charged at 18% … fees are passed through at cost. All amounts are in EUR…` | **identical** | **MATCH** |
| 19 | *(none)* | `Some lines are provisional, pending channel reconciliation.` | **EXTRA** — the same honest qualifier as the portal |
| 20 | Footer `Seaside Villas · statement OS-2025-09 · generated 1 Oct 2025` | `… · statement OS-2026-09 · no. 6 · generated 15 Aug 2026` | **MATCH** + **EXTRA** (statement number) |
| 21 | `Questions? owners@seasidevillas.ie` | `Questions? billing@vyrobel-demo.test` | **MATCH** (real address) |
| 22 | *(none)* | **`Downloaded by Demo Owner (demo-owner@vyrobel-demo.test) on 17 Aug 2026, 14:40 UTC · Confidential`** | ⚠️ **EXTRA — the export watermark, proven inside the artifact.** See below |
| 23 | `Download PDF` button (prototype chrome) | n/a — this *is* the PDF | **n/a** |

**Verdict — Artifact A:** **0 gaps.** 17 MATCH · 3 divergences (2 the same ratified date-refusal, 1 copy) ·
3 extras · 1 data-dependent. ⚠️ **Text layer only** — layout/typography unverified.

⚠️ **Item 22 closes a loop from page 18.** The Team page *claims* financial exports are watermarked with who
downloaded them. The watermark is **physically present in the PDF this audit just downloaded**, naming the
account and the UTC minute. The claim, the log entry, and the artifact now all corroborate each other.

⚠️ **Item 3 is a correction to my own page-8 wording.** I called the `OS-YYYY-MM` reference "a ratified
refusal"; it is generated and printed on the PDF. The Documents *page* shows `No. 6` instead — a **surface
choice**, not a build-wide refusal. Page 8 item 12's verdict (DIVERGENCE) stands; its reasoning was too broad.

## Artifacts B–D — extracted, **wireframe diff REMAINING** *(superseded — now DIFFED in Addendum 3)*

All three extracted cleanly and **all three reconcile**, but I have not yet opened their wireframes, so they
are **not diffed** and carry no MATCH/MISSING verdicts. Their text is recorded here so the next run can diff
immediately.

| Artifact | Extracted | Money check |
|---|---|---|
| **Booking summary** (`BS-2026-09`) | 949 chars — KPIs `2 bookings / 6 nights / 13% / €1,080.00`, by-unit, by-channel (`Walk-in`, `Direct` — real sources), and a per-booking table (`R-7UPHQE0A · Demo Walker · A1 · 6–9 Sep · 3 · €540.00`) | ✅ 540 + 540 = **1,080.00** |
| **Remittance advice** (`RA-2026-09`) | 792 chars — `NET PAYABLE €885.60`, `PENDING`, `HOW IT'S CALCULATED` breakdown, `PAYMENT DETAILS` (beneficiary / IBAN ···· 1000 / bank transfer / EUR) | ✅ 1,080.00 − 194.40 + 0.00 = **885.60** |
| **Annual tax summary** (`2026`) | 1,071 chars — `GROSS €4,500.00 / DEDUCTIONS −€810.00 / NET €3,690.00`, a 6-month table, deductions by category, and *"not tax advice — share it with a qualified accountant"* | ✅ months sum: 442.80 + 656.00 + 467.40 + 516.60 + 721.60 + 885.60 = **3,690.00**; and 4,500.00 − 810.00 = **3,690.00** |

⚠️ **Two observations that do not need the wireframe to state:**
- The **booking summary prints guest names** (`Demo Walker`, `Demo Resident`) — **name-only, no contact
  detail**, consistent with #28 and with the portal's own posture.
- All three carry the same **`Downloaded by … · Confidential`** watermark as Artifact A.

## Artifact E — Invite Email — **NOT STARTED**

`Owner Invite Email.html` exists; the live Mailable was not rendered. ⚠️ **Untouched — not diffed, no verdict.**

## Revised completion statement

**28 of 28 owner PAGES browser-diffed (complete) + 1 of 5 non-page artifacts diffed (text layer).**
**REMAINING:** 3 PDFs extracted but not wireframe-diffed · 1 email not started · 1 signed mutating endpoint
(unverifiable read-only).


---

# ADDENDUM 3 — ARTIFACTS B, C, D DIFFED (text layer) · E still not started

Wireframes opened in Playwright and hydrated; PDFs extracted from their own content streams.
⚠️ **Same method limit as Addendum 2: TEXT LAYER only — layout/typography/colour unverified.**

## Artifact B — Booking summary PDF vs `Owner Booking Summary PDF.html` (wf **1,393 chars**)

| # | Wireframe element | Live PDF | Verdict |
|---|---|---|---|
| 1 | Letterhead `V Seaside Villas Lda` + `Managing agent · Faro, Portugal · VAT PT 512 034 776` | `V Vyrobel Demo Hospitality, Lda` + `Managing agent · Portugal · VAT PT 512 034 776` | **MATCH** (real; street address absent — none saved) |
| 2 | `BOOKING SUMMARY` + ref `BS-2025-09` | `BOOKING SUMMARY` + **`BS-2026-09`** | **MATCH** — ⚠️ the `BS-` reference **is generated**, same finding as `OS-` on Artifact A |
| 3 | `Booking summary` + `September 2025 · Amara Okafor · Seaside Villas · 3 units` | `September 2026 · Demo Owner · 2 units` | **MATCH** (real) |
| 4 | KPIs `BOOKINGS 18` / `NIGHTS 64` / `OCCUPANCY 78%` / `GROSS REVENUE €12,400` | `2` / `6` / `13%` / `€1,080.00` | **MATCH** (real) |
| 5 | `By unit` + cols `UNIT / BOOKINGS / NIGHTS / OCCUPANCY / REVENUE` | identical | **MATCH** |
| 6 | 3 unit rows + `Total 18 64 78% €12,400.00` | `A1 1 3 10% €540.00`, `A2 1 3 17% €540.00`, `Total 2 6 13% €1,080.00` | **MATCH** — ⚠️ **540 + 540 = 1,080** and **3 + 3 = 6** |
| 7 | `By channel` — `Direct 7 bookings · no commission €4,820.00` | `Walk-in 1 booking · no commission €540.00` / `Direct 1 booking · no commission €540.00` / `Total €1,080.00` | **MATCH** (real `source` values) + **EXTRA** (a channel `Total` row) |
| 8 | Channel rows **`Booking.com 7 bookings · €620 commission`** and **`Airbnb 4 bookings · via channel manager`** | **absent** | **DIVERGENCE — RATIFIED brand refusal**, consistent with pages 4/10/13/25/26 |
| 9 | `Bookings this month` + cols `REF / GUEST / UNIT / DATES / NIGHTS / REVENUE` | identical cols | **MATCH** |
| 10 | 8 booking rows with abbreviated guest names (`J. Okonkwo`, `M. Kowalska`…) | 2 real rows — `R-7UPHQE0A · Walk-in · Demo Walker · A1 · 6 Sep – 9 Sep 2026 · 3 · €540.00`; `R-60UBVG6Q · Direct · Demo Resident · A2 · 14 Sep – 17 Sep 2026 · 3 · €540.00` | **MATCH** — ⚠️ **guest NAME only, no contact detail** (#28). The build also prints the **source** per row, which the wireframe does not |
| 11 | Row total | `Total 6 €1,080.00` | **MATCH** |
| 12 | Closing paragraph — `…generated September's gross room revenue of €12,400.00. It accompanies statement OS-2025-09; deductions and your net payout are detailed there.` | same wording, `€1,080.00` + `It accompanies statement #6` | **MATCH** (statement **number** rather than the `OS-` ref — minor) |
| 13 | `Showing 8 of 18 bookings — the full list is available in your portal.` | **absent** (all 2 rows shown; nothing truncated) | **MATCH in role** — a truncation notice with nothing truncated would be false |
| 14 | *(none)* | Footer `Vyrobel Demo Hospitality, Lda · VAT PT 512 034 776 · Booking summary · September 2026` | **EXTRA** |
| 15 | *(none)* | **`Downloaded by Demo Owner (…) on 17 Aug 2026, 14:41 UTC · Confidential`** | **EXTRA — the export watermark** |

**Verdict — B:** **0 gaps.** 12 MATCH · 1 divergence (ratified brand refusal) · 2 extras.

## Artifact C — Remittance advice PDF vs `Owner Remittance Advice PDF.html` (wf **1,167 chars**)

| # | Wireframe element | Live PDF | Verdict |
|---|---|---|---|
| 1 | Letterhead + `VAT PT 512 034 776` | `V Vyrobel Demo Hospitality, Lda` + `Managing agent · PT · VAT PT 512 034 776` | **MATCH** |
| 2 | `REMITTANCE ADVICE` + `RA-2025-09` | identical + **`RA-2026-09`** | **MATCH** — ⚠️ the `RA-` reference **is generated** on the PDF (page 8 called it refused *on that surface*; same correction as Artifact A item 3) |
| 3 | `Payout remittance` + `September 2025 · Amara Okafor · Seaside Villas · 3 units` | `September 2026 · Demo Owner` | **MATCH**; unit count not repeated — **DIVERGENCE, minor** |
| 4 | **`NET PAID TO OWNER`** `€10,370.00` | **`NET PAYABLE TO OWNER`** `€885.60` | ⚠️ **DIVERGENCE — honest and important.** The payout is **pending**, not paid; the build says *payable*. `NET PAID` on an unpaid payout would be a false statement of fact |
| 5 | `Bank transfer · IBAN ···· 4821 · **est. 3 Nov 2025**` | `Bank transfer · IBAN ···· 1000` | **DIVERGENCE — RATIFIED refusal** (no estimated date) |
| 6 | `Statement OS-2025-09` / `Period 1–30 Sep 2025` / `Status **Scheduled**` | `Statement #6` / `1 Sep 2026 – 30 Sep 2026` / **`PENDING`** | **MATCH** — real status; `Scheduled` would imply a booked transfer |
| 7 | `How it's calculated` + `AMOUNT` col | `HOW IT'S CALCULATED` + `AMOUNT` | **MATCH** |
| 8 | `Gross room revenue 64 nights · 3 units +€12,400.00` | `+€1,080.00` | **MATCH** |
| 9 | `Management commission **15% of gross** −€1,860.00` | `Management commission −€194.40` (18% — page 27/Artifact A) | **DIVERGENCE — RATIFIED.** ⚠️ The **eighth** wireframe instance of the wrong 15% |
| 10 | `Management fee Fixed monthly −€150.00` | **absent** | **DIVERGENCE — unmodelled.** No fixed monthly fee exists; inventing one would be a charge that never happened |
| 11 | `Cleaning fees Collected from guests +€480.00` | **absent** | **DIVERGENCE — unmodelled** (no per-property cleaning fee; pages 19/25 same) |
| 12 | `OTA commission Booking.com −€620.00` | `OTA commission` **`PROVISIONAL`** `+€0.00` | **MATCH** in role — real €0, flagged provisional; the **brand** is refused |
| 13 | `Prior-period adjustment Carried forward from August +€120.00` | **absent** | **DATA-DEPENDENT** — no such adjustment exists this period (the tag *is* real; page 5 shows it on Balance) |
| 14 | `Net payable €10,370.00` | `€885.60` | **MATCH** — ⚠️ **reconciles: 1,080.00 − 194.40 + 0.00 = 885.60** |
| 15 | *(none)* | `Net payable to owner: €885.60 — includes provisional lines pending verification.` | **EXTRA** — honest qualifier |
| 16 | `Payment details` — `Beneficiary` / `Account` / `Method` / `Reference` | `PAYMENT DETAILS` — `Beneficiary Demo Owner` / `Account IBAN ···· 1000` / `Method Bank transfer` / **`Currency EUR`** | **MATCH** (3 of 4) + **EXTRA** (`Currency`); `Reference SEASIDE OS-2025-09` **absent** — **DIVERGENCE, minor** |
| 17 | `Method **SEPA** bank transfer` | `Bank transfer` | **DIVERGENCE — honest.** The SEPA rail is not asserted (page 10 item 31, page 24 item 37 — same refusal) |
| 18 | Closing — `…is **scheduled to settle** to the account above **by 3 November 2025**. Cleaning fees are collected from guests and passed through in full…` | `This remittance advice accompanies statement #6 and reconciles to the figures shown in the owner portal.` | **DIVERGENCE — honest.** No settlement promise, no date; instead a **reconciliation** claim the audit has independently verified |
| 19 | `Questions? Contact owners@seasidevillas.ie` | absent here (present on the statement PDF) | **DIVERGENCE — minor/placement** |
| 20 | *(none)* | **`Downloaded by … · Confidential`** watermark | **EXTRA** |

**Verdict — C:** **0 gaps.** 8 MATCH · 10 divergences (**4 ratified refusals**, 3 unmodelled, 3 minor) ·
3 extras · 1 data-dependent.

⚠️ **Item 4 is the one to keep from this artifact.** The wireframe's remittance is headed **`NET PAID TO
OWNER`** while the payout is still pending. The build says **`NET PAYABLE`** and stamps it `PENDING`. On a
remittance advice — a document an owner may hand to an accountant — "paid" and "payable" are not synonyms,
and only one of them is true here.

## Artifact D — Annual tax summary PDF vs `Owner Annual Tax Summary PDF.html` (wf **1,526 chars**)

| # | Wireframe element | Live PDF | Verdict |
|---|---|---|---|
| 1 | Letterhead + VAT | `V Vyrobel` + **`on behalf of Vyrobel Demo — Seaside Hotel & Apartments`** | **MATCH** (issuer stated) |
| 2 | `ANNUAL TAX SUMMARY` + `2025 · **AS-2025**` | `ANNUAL TAX SUMMARY` + `2026` | **MATCH**; the `AS-YYYY` ref **absent** — **DIVERGENCE, minor** (the only reference format not generated) |
| 3 | `2025 annual tax summary` + `Amara Okafor · Seaside Villas · 3 units · 1 Jan – 31 Dec 2025` | `Owner Demo Owner` / `Tax residency PT` / `Period 1 Mar 2026 – 30 Sep 2026` + `6 statements · EUR` | **MATCH** + **EXTRA** (`Tax residency`, statement count, currency) — ⚠️ the period is the **real** covered range, not a full calendar year |
| 4 | `GROSS REVENUE €132,400` | `€4,500.00` | **MATCH** |
| 5 | `TOTAL DEDUCTIONS −€33,690` | `−€810.00` | **MATCH** |
| 6 | `NET PAID TO OWNER €98,710` | **`NET PAID TO OWNER €3,690.00`** | **MATCH** — ⚠️ "paid" is correct here: 5 of 6 statements are genuinely paid |
| 7 | `By month` + cols `MONTH / GROSS / COMMISSION / FEES / NET` | **identical** | **MATCH** |
| 8 | 12 month rows | **6 real rows** (Mar, Apr, May, Jun, Jul, Sep) | **MATCH** (real; Aug absent because no statement) |
| 9 | `Total 2025 €132,400 −€19,860 −€13,830 €98,710` | `Total 2026 €4,500.00 −€810.00 +€0.00 €3,690.00` | **MATCH** — ⚠️ **months sum exactly: 442.80 + 656.00 + 467.40 + 516.60 + 721.60 + 885.60 = 3,690.00**, and 4,500.00 − 810.00 = **3,690.00** |
| 10 | `FEES` column populated (`−€760` … `−€510`) | `+€0.00` every month | **MATCH** — a genuine zero (#70), not a hidden column |
| 11 | `Deductions by category` | identical heading | **MATCH** |
| 12 | Category `Management commission · **15%** · On gross room revenue €19,860.00` | `Management commission −€810.00` | **MATCH** — ⚠️ the **ninth** wireframe instance of 15%; the build carries no rate label here, and 810.00 ÷ 4,500.00 = **18%** exactly |
| 13 | Category `Management fees €150 × 12 months €1,800.00` | **absent** | **DIVERGENCE — unmodelled** (same as Artifact C item 10) |
| 14 | Category `OTA commissions · Booking.com, Expedia, Airbnb €8,940.00` | **absent** | **DIVERGENCE — RATIFIED** (no OTA cost this period, and the brands are refused) |
| 15 | Category `Cleaning & turnover €3,090.00` | **absent** | **DIVERGENCE — unmodelled** |
| 16 | `Total deductions €33,690.00` | `Total deductions −€810.00` | **MATCH** — ⚠️ equals item 5 |
| 17 | Closing — `consolidates all 12 monthly statements (OS-2025-01 to OS-2025-12)…` | `consolidates your 6 committed statements for the 2026 calendar year and **reconciles to the figures in your owner portal**` | **MATCH** + **EXTRA** (the reconciliation claim, independently verified above) |
| 18 | `Figures are gross of any income tax due, which is the owner's responsibility.` | **identical** | **MATCH** |
| 19 | `…is not tax advice — please share it with your accountant.` | `This document is not tax advice — please share it with a qualified accountant.` | **MATCH** |
| 20 | `Issued by Vyrobel on behalf of Seaside Villas Lda.` | `Issued by Vyrobel on behalf of Vyrobel Demo — Seaside Hotel & Apartments.` | **MATCH** |
| 21 | *(none)* | **`Downloaded by … · Confidential`** watermark | **EXTRA** |

**Verdict — D:** **0 gaps.** 15 MATCH · 4 divergences (1 ratified, 2 unmodelled, 1 minor) · 2 extras.

## Artifact E — Invite Email — *(superseded — now DIFFED in Addendum 4)*

## Revised completion statement (Addendum 3)

**28 of 28 owner PAGES browser-diffed (complete) + 4 of 5 non-page artifacts diffed (text layer).**
**REMAINING:** 1 email not started · 1 signed mutating endpoint (unverifiable read-only).

⚠️ **Across all four PDFs: 0 gaps, every figure reconciles, and the `Downloaded by … · Confidential`
watermark is present in all four** — the Team page's watermarking claim is now corroborated by four separate
artifacts. ⚠️ **And the 15% count rises to nine wireframe instances** (Artifacts C and D add two); the build
is 18% on every one of them.


---

# ADDENDUM 4 — ARTIFACT E DIFFED · ✅ ALL 5 NON-PAGE ARTIFACTS COMPLETE

## Harness — how the email was rendered, and why it is non-destructive

⚠️ **No mail was sent and nothing was written.** `OwnerInviteMail` was rendered via Laravel's
`Mailable::render()` — a pure template render — and the HTML was written to the **scratchpad** (not the repo)
and served on a local port for Playwright to open and hydrate.

⚠️ **Two things worth recording from building this harness:**

1. **The tenant guard fired first, and correctly.** My first attempt queried `OwnerInvite` with no org
   context and threw `TenantContextMissingException` — *"No organization context is set for tenant-scoped
   model."* ⚠️ **That is invariant #1 doing exactly its job**, caught live and unprompted. I wrapped the read
   in `runAsSuperAdmin` (the sanctioned escape) rather than working around the scope.
2. **`owner_invites` has 0 rows**, so the Mailable was built from an **unsaved** model instance — constructed
   in memory, never `save()`d. ⚠️ Verified after the render: **`owner_invites` count still 0.**

- **Wireframe:** `Owner Invite Email.html` hydrated, **1,021 chars**
- **Live:** rendered Mailable, **14,659 bytes** → **1,102 chars** of text, hydrated in Playwright

### Element-by-element

| # | Wireframe element | Live email | Verdict |
|---|---|---|---|
| 1 | Mail-client chrome — `V Vyrobel <hello@vyrobel.com>` + subject line + `now` | n/a (prototype chrome, not part of the email) | **n/a** |
| 2 | Subject `Seaside Group invited you to your Vyrobel owner portal` | `You're invited to Vyrobel Demo — Seaside Hotel & Apartments` (page `<title>`) | **MATCH** in role — **DIVERGENCE, copy** (the build names the real operator) |
| 3 | Header `V Vyrobel` + `OWNER PORTAL INVITATION` | **identical** | **MATCH** |
| 4 | `INVITED BY Seaside Group` | `INVITED BY Vyrobel Demo — Seaside Hotel & Apartments` | **MATCH** (real org name) |
| 5 | Headline `You've been invited to Vyrobel` | `Your owner portal on Vyrobel Demo — Seaside Hotel & Apartments` | **MATCH** + **DIVERGENCE, copy** (names the operator, not the platform) |
| 6 | `Hi Marisol,` | `Hi Demo Owner,` | **MATCH** (real name) |
| 7 | Body `Seaside Group has invited you to your owner portal, where you can see your statements, payouts, and unit performance — all in one place. Set your password to get started.` | **identical wording**, real org name | **MATCH** |
| 8 | `IN YOUR PORTAL` section | identical | **MATCH** |
| 9 | Benefit `Monthly statements` + `Every booking, fee and payout, itemised` | **identical, char-for-char** | **MATCH** |
| 10 | Benefit `Payouts & schedule` + `Track what's on the way and when it lands` | **identical, char-for-char** | **MATCH** |
| 11 | Benefit `Unit performance` + `Occupancy, rates and reviews per **villa**` | same + `per **unit**` | **MATCH** + **DIVERGENCE, copy** (correct for a mixed portfolio — same fix as page 12 item 10) |
| 12 | *(none)* | `✓` tick glyph on each of the three benefits | **EXTRA** — minor |
| 13 | CTA `Set your password` | identical, links to the real tokened route | **MATCH** + wired |
| 14 | `Or paste this link into your browser:` + `https://app.vyrobel.com/invite/8f3c9a2e-marisol` | same copy + `http://localhost:8000/invite/<token>` | **MATCH** — the URL is the real `APP_URL` + real single-use token path |
| 15 | `This invite **expires in 7 days**.` | `This invite is **single-use** and expires on **Mon, Aug 24, 2026**.` | **MATCH** + **EXTRA** — ⚠️ the build states a **concrete expiry date** (computed from the real `expires_at`) *and* that the link is **single-use**. A relative "in 7 days" is ambiguous in an email that may be read days later |
| 16 | `If you didn't expect this, you can safely ignore this email — **no account is created until you set a password**.` | **identical, char-for-char** | **MATCH** |
| 17 | Footer link `Get help` | **absent** | **DIVERGENCE — minor.** Consistent with page 20: no support channel is printed |
| 18 | Footer link `Sign in` | present → real `/login` | **MATCH** + wired |
| 19 | Footer `Vyrobel · hospitality, warmly run · You're receiving this because <org> invited you as a property owner.` | **identical**, real org name | **MATCH** |
| 20 | `Sent automatically when an operator invites **an owner**` | `Sent automatically when an operator invites **someone to their organization**` | **MATCH** + **DIVERGENCE — honest.** ⚠️ The Mailable is **role-aware** (its own docblock: *"an owner is told about their portal; a staff invitee is told about the operator app — so the email never mislabels a manager as an 'owner'"*), so the generic wording is correct for a shared template |
| 21 | `Edit in Email templates` (prototype authoring affordance) | **absent** | **DIVERGENCE — n/a.** A design-tool control, not email content |

### Wiring (non-destructive)

| Check | Result |
|---|---|
| Real org name | ✅ resolved from the real `Organization` record |
| Real expiry | ✅ `Mon, Aug 24, 2026` computed from `expires_at` |
| Tokened link | ✅ real `/invite/{token}` route shape, real `APP_URL` |
| `Sign in` | ✅ → real `/login` |
| Role-awareness | ✅ real (`copyFor($role, $orgName)`) — **unverified for the staff variant** (only the owner role was rendered) |
| Mail send | **not exercised** — `render()` only, nothing queued or sent |
| Persistence | ✅ **`owner_invites` count 0 before and after** |

### Verdict — Artifact E

**0 gaps.** 13 MATCH · 6 divergences (4 copy, 1 minor absence, 1 n/a) · 2 extras · 1 unverified variant.

⚠️ **Item 15 is the one worth keeping.** The wireframe says *"expires in 7 days"*; the build says
*"single-use and expires on Mon, Aug 24, 2026."* An email is read at an unknown later time, so a **relative**
expiry is ambiguous exactly when it matters — and "single-use" is a security property the prototype never
mentions.

---

# ✅ FINAL — OWNER SURFACE COMPLETE (28 pages + 5 artifacts)

| Scope | Status |
|---|---|
| **Owner pages** | **28 / 28 browser-diffed** — 0 backend gaps · 6 candidate frontend gaps |
| **Non-page artifacts** | **5 / 5 diffed** — 4 PDFs (text layer) + 1 email (browser-rendered) · **0 gaps** |
| **Signed mutating endpoint** | `/owner/account/email/verify/{user}/{hash}` — **UNVERIFIED** (unopenable read-only, and opening it mutates) |

**Across the five artifacts:** **0 gaps**, every money figure reconciles, and the
**`Downloaded by … · Confidential` watermark appears in all four PDFs** — corroborating the Team page's
watermarking claim from four independent files.

⚠️ **Final tally on the commission rate: NINE wireframe instances state 15%; one (`Owner Statement PDF`
/ `Owner Statement`) states 18% and proves it arithmetically. The build is 18% on every surface**, matching
the real `ManagementAgreement`. The 15% instances are prototype errors, and each would have shown an owner
wrong money.

⚠️ **Method limit restated so it is not lost:** the four PDF diffs cover the **text layer only**. Layout,
typography and colour are **not** verified for any PDF. That is weaker evidence than the 28 browser-rendered
page diffs, and no PDF verdict should be read as a visual-fidelity pass.

**Non-destructive throughout:** no code changed · no mail sent · no row written (`owner_invites` 0 → 0) ·
`rate_calendar` 929 rows / 10,920,000 minor · 0 unit overrides · 41 reservations · 6 statements.


---

# ADDENDUM 5 — SELF-CHECK OF THE DELIVERABLE (a gap in my own artifact, found and closed)

⚠️ With the audit at N=M, I applied **this audit's own rule to the report itself**: verify its claims rather
than trust them. That found a real gap.

## What was checked

| Check | Result |
|---|---|
| Ledger rows still `REMAINING` | **0** |
| Ledger rows marked `DONE` | **28** |
| Every `.captures/*.png` path cited in the report — does the file exist? | **21 of 21 exist** (516 KB – 822 KB each) |
| ⚠️ **Screenshots cited vs pages diffed** | **21 cited, 28 pages** — **pages 1–7 cited none** |

## ⚠️ The gap

`.captures/` contained **no `deep-owner-01..07` files**. Pages 1–7 (`/owner`, `payouts`, `units`, `bookings`,
`balance`, `expenses`, `calendar`) were diffed in the earliest runs, before I adopted the per-page screenshot
convention.

**What this did and did not mean:**

- ✅ **The diffs are genuine.** Each of those seven sections records **hydrated character counts from both
  sides** (e.g. page 5: wireframe 1,526 / live 4,677), which is only obtainable from a real Playwright read of
  a rendered page. The evidence behind the verdicts stands.
- ❌ **But the brief's #1 rule requires a screenshot per page**, and for 1–7 that artifact did not exist. The
  report was therefore **claiming a standard it had not fully met on a quarter of its pages** — the same class
  of overclaim this pass was created to correct in its predecessor.

## Closed

All seven were re-opened in Playwright and captured:

| Page | Capture |
|---|---|
| 1 · `/owner` | `.captures/deep-owner-01-overview-live.png` |
| 2 · `/owner/payouts` | `.captures/deep-owner-02-payouts-live.png` |
| 3 · `/owner/units` | `.captures/deep-owner-03-units-live.png` |
| 4 · `/owner/bookings` | `.captures/deep-owner-04-bookings-live.png` |
| 5 · `/owner/balance` | `.captures/deep-owner-05-balance-live.png` |
| 6 · `/owner/expenses` | `.captures/deep-owner-06-expenses-live.png` |
| 7 · `/owner/calendar` | `.captures/deep-owner-07-calendar-live.png` |

**28 of 28 pages now have a capture on disk.** ⚠️ No verdict changed — the screenshots are evidence of the
render, not a re-diff, and I am not restating the seven diffs as "re-verified" on the strength of a new PNG.

⚠️ **This is the third auditor-side error this pass has caught in its own work** (after the page-10 ZIP verdict
and the page-8 `OS-` reference reasoning). All three were found by **checking rather than assuming** — which is
the single method note worth carrying out of this audit.
