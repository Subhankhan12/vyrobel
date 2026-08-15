# Vyrobel — the REMAINING-WORK LEDGER (`DO-BACKEND-AUDIT-1`, 2026-08-15)

**A READ-ONLY audit. Nothing in this pass was fixed, filled, stubbed or tested — the only write is this file.**

**Purpose.** The design programme is complete-in-method and billing is code+build complete (gated on the
founder's Stripe/VAT steps). What remains is the **backend phase**: pages that carry a full UI over a missing,
partial or deliberately-inert backend. Its true scope lives in the registers **and in the code**, not in
memory. This file is the map.

**Inputs.** `docs/design/MISSING-BACKEND-BY-PAGE.md` (the spine — **265** logged item-rows;
176 in table form across 45 page sections, 89 as later-format bullets) · `docs/design/DIVERGENCE-REGISTER.md` · `docs/DECISIONS.md` · `docs/PROJECT-STATE.md` §G.1 / §H /
§H.bis / §I / §J · and the codebase.

> ⚠️ **THE RULE THIS LEDGER IS BUILT ON: "UI exists" ≠ "feature works", and "the register says so" ≠ "it is
> still true".** A register entry is a claim about the moment it was written (§N.8b). Several have since been
> built. Every status below cites a file+line, or says **UNVERIFIED** and why.

---

## 0 · HEADLINE NUMBERS

| | count |
|---|---|
| Register item-rows enumerated | **265** (176 table-format across 45 page sections + 89 bullet-format) |
| Of those, logged as open work when written (`REAL GAP`) | **115** |
| Logged as **not** work — refusal / `ACCOUNT-GATED` / `NOT A GAP` / deferred | **108** ⚠️ *not counted as remaining work* |
| Rows carrying a prose verdict too mixed to classify mechanically | **42** — sampled, not swept (§8) |
| Re-verified against current code **this pass** | **84** (40 deep-traced · 44 mechanically re-checked) |
| **SINCE-DONE** (register stale — built after the entry) | **6** |
| **PARTIAL** (some layers real) | **7** |
| **STILL-OPEN** | **71** |
| **UNVERIFIED** (named, with the reason) | **~73** rows + all perf claims |
| In-code `TODO`/`FIXME`/stub/`abort(501)`/debug leftovers | **0** ✅ |
| Dead routes referenced in Blade/PHP | **0** ✅ |
| Launch-BLOCKING items | **3** (all infrastructure/founder, none feature) |

⚠️ **Two facts worth stating plainly up front, because they set the tone of everything below.**

1. **There is no rot.** A full sweep for `TODO`, `FIXME`, `HACK`, `XXX`, `not implemented`, `abort(501)`,
   `// stub`, `dd()`/`dump()`/`ray()`/`var_dump()` across `app/`, `routes/`, `resources/views/`, `database/`
   and `config/` returns **one** hit — the word "HACK" inside an explanatory sentence
   (`app/Livewire/Owner/OwnerMessages.php:39`). **No stubbed handler, no dead action, no debug leftover.**
2. **Every `route('…')` reference resolves.** 371 distinct route names are referenced across `app/` and
   `resources/views/`; 441 are registered; the 5 apparent misses are 4 `$request->route('param')` *parameter*
   reads and one `@if (Route::has('register'))`-guarded link in Laravel's stock `welcome.blade.php`.
   **No Blade links to a route that 404s.**

**So the remaining work is almost entirely ADDITIVE — features not yet built — not defects to repair.** The
exceptions are in §1, and there are only three of them.

---

## 1 · ⚠️ THE THREE THINGS THAT ARE WRONG, NOT MERELY MISSING

These are the only items where the product currently **says something untrue** or **loses data**. They are
small, and they are first for that reason.

### 1.1 🔴 `payoutStatusMeta` tells an owner a payout in transit is "pending" — and it exists THREE times

**Status: STILL-OPEN · Size S · No dependency · ⚠️ REAL MONEY, owner-facing**

The register logs this **six separate times** — `D16-7`, `DO14-1`, `DO17-1`, `DO21-1`, `DO21-2`, `DO24-2` —
each on a different page, none of them naming it as one defect. It is one helper.

```
app/Livewire/Owner/Concerns/FormatsOwnerData.php:101-106
    'paid'     => 'Paid'
    'failed'   => 'Payout failed'
    'reversed' => 'Reversed'
    default    => 'Payout pending'        ← ⚠️ swallows 'processing' AND 'held'
```

Both swallowed statuses are written by live production code, not hypotheticals:
`app/Services/OwnerPayoutService.php:127` sets `processing`, `:189` sets `held`. The declared enum is
`pending/processing/paid/failed/reversed/held` (`app/Models/Payout.php:19`).

⚠️ **The audit found it is worse than recorded: there are THREE definitions of this one concept**, an §N.8f
violation the register never spotted because each entry looked at one page:

| copy | file:line | `default` behaviour | consequence |
|---|---|---|---|
| owner shared trait | `app/Livewire/Owner/Concerns/FormatsOwnerData.php:99` | `'Payout pending'` | ⚠️ **actively misinforms** — money already sent reads as not started; money held by the tax-hold reads the same |
| operator, private copy | `app/Livewire/Operator/OperatorOwnerDetail.php:658` | `ucfirst($status)` | degrades gracefully → "Processing"/"Held", untranslated, generic tone |
| operator, private copy | `app/Livewire/Operator/PayoutRun.php:405` | `ucfirst($status)` | same |

Call sites: 5 owner surfaces (`OwnerPayouts` ×2, `OwnerDocuments`, `OwnerDashboard`, `OwnerStatementView`)
+ 3 operator. **The owner-facing copy is the dangerous one**, and it is the one that names the states wrongly
rather than bluntly.

**What it needs:** one definition (extend the shared trait to cover `processing`/`held`, delete the two
private copies, point all 8 call sites at it). ⚠️ `OwnerBalance` already models `held`/`processing` correctly
in its clearing identity — **the concept is already right somewhere in the codebase; the label map is what
lags.**

### 1.2 🔴 The operator Schedule PDF prints two statements that are now false

**Status: STILL-OPEN · Size S · Depends on: extending `RosterWeek` (see 3.3) · Register: `D34-1`, `D34-2`**

`resources/views/pdfs/operator-schedule.blade.php` renders, **as visible text to the operator**:

> `:136` — "This rota reflects the LIVE roster — **there is no draft or published state**, so what you see
> here is what the team sees."

and carries two matching source comments (`:5-6`, `:66-67`) asserting *"open shifts is omitted (no
unassigned-shift model — every shift is bound to a person)"*.

**Both claims were true when written and are false now.** The migration
`database/migrations/2026_07_31_150000_create_schedule_publications_and_open_shifts.php` creates both tables;
`app/Models/OpenShift.php` and `app/Models/SchedulePublication.php` exist; `ScheduleController` really writes
them (`:220` publish, `:250` create open shift, `:263` fill, `:290` remove) behind gated routes
(`routes/web.php:942-944`); and the **screen** shows a real Publish button and a "Published" tag
(`resources/views/livewire/operator/schedule.blade.php:52,58`).

⚠️ **This is the §N.8d class — an assertion the code no longer supports — and it is rendered to a user.** It
is also §N.13.1 exactly: the register flagged the *missing feature* (open shifts absent from the PDF); the
*false sentence* sits three lines away and went unrecorded.

**Root cause is shared and structural:** the PDF and the grid both read `App\Support\Schedule\RosterWeek`,
whose entire public surface is `teamSize() · shiftCount() · hours() · dayView() · coverageByDepartment() ·
build()` — **no open-shift and no publication accessor**. The grid queries the models directly
(`OperatorSchedule.php:145,167`); the PDF cannot, because the anti-drift rule says the PDF renders from the
shared builder. ⚠️ **The fix belongs in `RosterWeek`, never in the template** (the register says this too).

### 1.3 🟠 `joinWaitlist()` has no uniqueness rule at either layer

**Status: STILL-OPEN · Size S · Register: `DO-G10-3`**

`app/Services/GuestWishlistService.php:80` is a bare `GuestWaitlistEntry::query()->create([...])`, and
`database/migrations/2026_07_22_120000_create_guest_wishlist_and_waitlist.php:29-46` gives
`guest_waitlist_entries` two plain indexes and **no unique constraint**. A double-submit writes two rows.

⚠️ **The sibling table in the same migration gets it right** — `:26` puts
`unique(['guest_id','room_type_id'])` on the wishlist. So this is a one-table omission, not a design position.
Client-side guarding is defence-in-depth, not the rule.

---

## 2 · REGISTER RECONCILIATION

| outcome | n | what it means |
|---|---|---|
| **STILL-OPEN** | 71 | verified absent in current code |
| **SINCE-DONE** | 6 | the register is stale — built after the entry was written |
| **PARTIAL** | 7 | some layers real; the gap named per row |
| **CHANGED** | 3 | the gap is real but its shape differs from the record |
| **UNVERIFIED** | ~73 | not re-checked this pass — see §8 |
| **DEFERRED-BY-DECISION** | **108** | refusal-class **69** + `ACCOUNT-GATED` **22** + `NOT A GAP` **16** + deferred **1** — ⚠️ **not remaining work** |

### 2.1 ✅ SINCE-DONE — the register is stale here (do NOT re-open)

| id | register claim | current reality |
|---|---|---|
| `D9-2` | "no open-shifts row in the schedule grid" | **BUILT.** `OperatorSchedule.php:167` renders them; create/fill/remove routes at `routes/web.php:942-944`. *(The PDF half, `D34-1`, is still open — see 1.2.)* |
| `DO5-5` | "no Snoozed tab on My Units" | **BUILT.** `OwnerMyUnits.php:89` → `OwnerAttention::snoozed()` |
| `DO14-2` | "no year filter on Documents" | **BUILT.** `OwnerDocuments.php:70-72` — built from the REAL years present, rendered only when >1 exists |
| `DO16-5` (partly) | "the Next-payout figure has no date" | **BUILT for Payouts.** `OwnerPayouts.php:122` → `PayoutSchedule::nextRunDate()`. *(Still absent elsewhere — see PARTIAL)* |
| `D16-5` | "no per-transfer bank identity on payout-run rows" | **BUILT.** `PayoutRun.php:143-150` — masked IBAN per owner, `payouts.manage`-gated, one batched read |
| `DO10-4` (mostly) | "the ★ colour is hand-rolled in 11 view files" | **LARGELY CLOSED.** `resources/views/components/star-rating.blade.php:41` is the shared component at the AA-safe `#8F5A06`. **3 files still hand-roll** (`livewire/owner/messages.blade.php`, `livewire/owner/reviews.blade.php`, `guest/review.blade.php`) |

### 2.2 🟡 PARTIAL — real in one place, absent in another

| id | what is real | what is not | evidence |
|---|---|---|---|
| `DO1-1` / `DO2-2` | a **real** next-payout date exists | it reaches **only** the Payouts page; the owner Dashboard hero and the Statement dark rail still have none | `nextRunDate` has exactly one consumer: `OwnerPayouts.php:122` |
| `D18-6` | multiple rate plans are loaded and listed | the grid is keyed to **one** `primaryRatePlan` per room type, so the design's three side-by-side plans don't render | `RatesGrid.php:86,103,139,148` |
| `D24-2` | passkeys/WebAuthn are **fully built** — model, service, register + usernameless login | **owner realm only.** Registration lives under `Route::prefix('owner')` (`routes/web.php:964`, routes `:1221-1223`); the operator Security page has none | `app/Support/Auth/PasskeyService.php`, `app/Models/Passkey.php` |
| `D3-4` | the guard for *absent* prior-year data exists (`has_ly`) | the guard for *thin* prior-year data did not | 🏁 **CLOSED 2026-08-15** — replaced by a coverage guard; see §3.1 |
| `D6-1`/`D6-2`/`D6-3` | the register records a deliberate scope call, not pure absence | the composer was omitted, so "no bulk backend is left to reach" | `ReservationsList.php:57-61` — ⚠️ read this before building |
| `DO10-4` | shared `<x-star-rating>` at the corrected colour | 3 files bypass it | above |
| `DO27-1` | thread unread **boolean** | no unread **count** | `OwnerMessages.php:625` |

### 2.3 ⚠️ CHANGED — real, but not the shape recorded

- **`D24-2`** is not "passkeys are missing"; it is "passkeys exist and one realm was never wired". That moves
  it from **M to S** and changes who should do it.
- **`D16-7`** is not one page's KPI blind spot; it is **one helper with three definitions and eight call
  sites** (1.1).
- **`D34-1/2`** is not only a missing PDF section; the PDF **asserts the opposite of the truth** (1.2).

---

## 3 · THE LEDGER, BY FUNCTIONAL AREA

Areas discovered from the code's own structure (`app/Livewire/{Operator,Owner}`, `app/Http/Controllers/
{Operator,Owner,Guest,Admin}`, `app/Services`, `app/Payouts`, `app/Support`), not an assumed list.

Sizes are **order-of-magnitude only**: **S** = a handful of files / one handler · **M** = a feature across a
few layers · **L** = a subsystem.

### 3.1 Analytics & reporting

| id | item | status | evidence | size |
|---|---|---|---|---|
| `D3-4` | **LY occupancy divided by the availability SPINE, guarded on presence not coverage** | 🏁 **CLOSED 2026-08-15** — `MetricSet` gained `coveredDays`/`periodDays` + `coveragePct()` + `hasComparableCoverage()` (threshold `MIN_COMPARABLE_COVERAGE_PCT = 80`, a named tunable). All **THREE** LY consumers now test coverage — ⚠️ the ledger named ONE; the KPI deltas and the per-property pace row were consuming it too. Thin suppresses whatever its %, a fully-covered month shows however low. ⚠️ **Measured distribution: 6–13% (prior year) vs 100% (live), NOTHING between 36% and 100%** | `app/Support/Analytics/MetricSet.php`; `OccupancyCoverageGuardTest` (10 tests) | — |
| `D3-1` | Insights offered 5 preset periods only, no arbitrary window | 🏁 **CLOSED 2026-08-15** — a `?from=`/`?to=` window resolved by the shared `CustomRange` **inside the one `resolveRange()` seam**, so it feeds the identical metric calls, the identical span-shifted LY, and therefore **inherits D3-4's coverage guard** (asserted, not assumed — a custom path of its own would have re-opened the fake-full-month LY figure D3-4 had just closed). ⚠️ **The max span is 13 months because `monthlyTrend*()` walks under a `$guard < 13`** — a longer window would silently render 13 bars under a heading claiming more (§N.8d). Rejections say so and keep the typed values | `app/Support/Analytics/CustomRange.php`; `InsightsCustomRangeTest` (11 tests) | — |
| `D3-4a` | ⚠️ **FOUNDER CALL — the coverage TREATMENT and THRESHOLD.** Implemented: **SUPPRESS** (an honest absent comparison), threshold **80%**. The alternative is CAVEAT (show the % with "based on N of M days") — not chosen because a caveated number still gets anchored on. ⚠️ The measured distribution is **bimodal with a 36–100% gap**, so the threshold is not delicate; any value in that gap behaves identically. One constant to change | OPEN — founder decision | `MetricSet::MIN_COMPARABLE_COVERAGE_PCT` | S |
| `D3-1a` | ⚠️ **The OWNER Insights has its own `resolveRange()`** (3 presets to the operator's 5) and no custom window. `CustomRange` is realm-agnostic — wiring it there is the same one branch. Not done here: the ledger item is the operator page, and the two preset SETS already differ, which is an existing divergence a range-picker item should not silently unify | STILL-OPEN | `OwnerInsights.php:297`; `CustomRange` | S |
| `D3-2` | No per-channel delta / warning dot on the channel-mix rows | STILL-OPEN | (grep: absent) | S |
| `D4-1` / `D12b-1` | No Excel/workbook export anywhere — no spreadsheet library is installed | STILL-OPEN | no `phpspreadsheet`/`maatwebsite` in `composer.json`; UI is honest: `livewire/operator/reports.blade.php:42` *"Needs a spreadsheet library — coming soon"* | M |
| `D4-2` | No "email this report to owners" from the operator side. ⚠️ The owner-side equivalent **is** built (`owner.reports.email` → `OwnerReportMail`) and is the pattern to copy | STILL-OPEN | `routes/web.php:1037`; `app/Mail/OwnerReportMail.php` | S |
| `D4-3` | Operator Reports has no owner-statements rail | STILL-OPEN | `OperatorReports.php:56-118` — no statement key in the payload | S |
| `D12b-2` / `D12b-3` | Maintenance Costs has no period navigator and no 6-month trend chart | STILL-OPEN | `OperatorMaintenanceCosts.php` | S |
| `DO1-3` | Owner Dashboard: no 3M/6M/12M toggle, no per-bar tooltip | STILL-OPEN | `OwnerDashboard.php` | S |
| `DO18-2` | Owner Bookings chart caps at 8 months, no range selector | UNVERIFIED (register-recorded) | reason: §8 | S |
| `DO25-1` | Owner Reports draws its own `.vy-rep-bar` instead of the shared `<x-charts.bars>` — ⚠️ §N.8f: a second implementation of one concept | STILL-OPEN | no `x-charts.bars` in `livewire/owner/reports.blade.php` | S |

### 3.2 Payouts, statements & owner money ⚠️ REAL MONEY

| id | item | status | evidence | size |
|---|---|---|---|---|
| **1.1** | **`payoutStatusMeta` collapses `processing`+`held`, ×3 definitions, 8 call sites** | STILL-OPEN | §1.1 | **S** |
| `D16-1` | No payout-run headline total ("€96,400 · 12 statements · …") | STILL-OPEN | `PayoutRun.php` | S |
| `D16-6` | "Request IBAN" / "Carry forward" are recorded as buttons on the design; the live rail treats them differently — read the entry's own note before building | UNVERIFIED-BY-DESIGN | `PayoutRun.php:85` carries the reasoning | S |
| `DO3-3` | No failed-payout alert with a Retry action (the status exists; the owner-facing recovery affordance does not) | 🏁 **CLOSED 2026-08-15** — the alert + a **re-queue** retry (`owner.payouts.retry` → the certified `OwnerPayoutService::retry()`), CAP_PAYOUT-gated + owner-scoped. ⚠️ **CONFIRM-FIRST CORRECTION, and it cuts BOTH ways (§N.27.11): the service already existed** (the ledger said only "the status exists") — **and it had NO idempotency whatever.** No lock, no re-check, no uniqueness: two calls wrote two `pending` payouts for ONE statement, and `PayoutRunService::processOwner()` disburses EVERY pending payout → **a genuine double payment**, dormant only because `retry()` had no caller outside one test. Wiring any control without fixing that would have SHIPPED the catastrophic failure. Now: row-lock + in-lock status re-check + the same one-live-payout-per-statement invariant `createFromStatement()` enforces, against the new shared `Payout::LIVE_STATUSES` | `app/Services/OwnerPayoutService.php`; `app/Support/Owner/PayoutRecovery.php`; `OwnerPayoutRetryTest` (14 tests, 6 mutations) | — |
| `DO3-6` | No payout receipt modal | STILL-OPEN | `OwnerPayouts.php` | S |
| `DO3-8` | No "reset to default" for payout preferences | STILL-OPEN | `OwnerPayoutPreferenceController` | S |
| `DO11-1` | `MoneyFormatter::display(0, signed: true)` renders **"+€0.00"** on the zero OTA-commission row | UNVERIFIED — see §8 | register: `§O11` | S |
| `DO17-3` | "In transit" can never be shown populated in the demo (no demo payout is ever `processing`) — a **demo-data** gap, not a code gap | STILL-OPEN | `DemoSeeder` | S |
| `DO24-2` | Archive can't express a held/processing payout — ⚠️ **a symptom of 1.1**, not separate work | STILL-OPEN (fold into 1.1) | `statusMeta()` | — |
| `DO16-5` | The Next-payout figure has no date on Payout Method | PARTIAL (2.2) | | S |

### 3.3 Front desk & daily operations

| id | item | status | evidence | size |
|---|---|---|---|---|
| **1.2** | **The Schedule PDF prints two false statements** | 🏁 **CLOSED** (`d154e48`) — and the silence it left is now filled truthfully by D34-1 | §1.2 | **S** |
| `D34-1` | `RosterWeek` exposes no open shifts / publication state, so the PDF cannot show them | 🏁 **CLOSED 2026-08-15** — `publicationState()` + `openShifts()` + `openShiftCount()` added to the shared builder; the PDF renders all three THROUGH the accessors and still makes no model query of its own | `app/Support/Schedule/RosterWeek.php`; `SchedulePdfPublicationTest` | — |
| `D34-1a` | ⚠️ **§N.8f FOLLOW-UP — the grid still queries the models directly.** `OperatorSchedule::publishedState()`/`openShiftsFor()` remain private copies of what the builder now owns, so there are TWO reads of one concept. The accessors were written grid-shaped precisely so this is a clean drop-in; a parity test currently pins the two together by invoking the grid's own private methods via reflection. ⚠️ Not done in D34-1 — refactoring a working screen was out of scope | STILL-OPEN | `OperatorSchedule.php:138,158` vs `RosterWeek::publicationState()/openShifts()` | S |
| `D34-1b` | The grid's `publishedState()` null-checks `$pub?->published_at !== null`, but `schedule_publications.published_at` is **NOT NULL** by schema — the check can never be false. Harmless, but it is a stale premise in the same family as D34-1's | STILL-OPEN (cosmetic) | `OperatorSchedule.php:150`; migration `2026_07_31_150000` line 24 (`timestamp()`, no `->nullable()`) — verified against the live column | S |
| `D34-1c` | ⚠️ **Open shifts ignore the department filter.** Filter the grid to Front desk and a Housekeeping open slot still shows; the PDF now matches that exactly (parity was the requirement). Whether it SHOULD filter is a real product question — a manager filtering to one department arguably wants that department's gaps | STILL-OPEN (product decision) | `OperatorSchedule.php:158` takes no `dept`; `RosterWeek::openShifts()` matches deliberately | S |
| `D9-1` | No per-person hours on a roster row — `hours()` is the **week total** (`shiftCount × HOURS_PER_SHIFT`) | STILL-OPEN | `RosterWeek.php:64-67` | S |
| `D1-1` | No per-user task-completion store (the dashboard's tickable tasks). ⚠️ The register warns: a task whose real count is >0 must never be "done" | STILL-OPEN | `OperatorDashboard.php:287` derives, stores nothing | M |
| `D1-2` | No task category dimension, no persisted grouping preference | STILL-OPEN | `OperatorDashboard.php` | S |
| `D1-3` | `reservations.expected_arrival` never reached the dashboard's arrivals payload | 🏁 **CLOSED 2026-08-15** — payload + render, matching FRONT DESK character-for-character (same `trim()`→null, same `Arrival · ` label, same omit-when-null). ⚠️ Free text, printed verbatim — never parsed, formatted or defaulted to an hour. Audience confirmed identical (both routes share one gate, neither carries a `can:`). ⚠️ **Ledger correction: the column is NOT populated on demo (0 of 41)** — so the POPULATED case was the unrendered branch, not the null one | `OperatorDashboard.php`; `DashboardExpectedArrivalTest` (8 tests) | — |
| `D1-3a` | ⚠️ **§N.8f FOLLOW-UP — two renderings of one concept.** The dashboard MATCHES Front Desk's arrival-line idiom rather than sharing it; there is no shared arrival-row component and this pass was scoped to leave Front Desk untouched. A parity test reads BOTH sources so drift fails loudly, but extracting `<x-operator.expected-arrival>` (the `<x-auth.passkeys>` pattern) would remove the risk structurally | STILL-OPEN | `OperatorDashboard.php` + `FrontDesk.php`; pinned by `DashboardExpectedArrivalTest` | S |
| `D2-1` | `units.max_occupancy` never reached the rack row payload | 🏁 **CLOSED 2026-08-15** — payload + render as **`Sleeps N`**, the product's EXISTING label (operator Owner-detail, owner Unit-detail) matched rather than reinvented, in the row's existing muted meta style. ⚠️ **The column is `NOT NULL DEFAULT 2`, so NULL is impossible and `0` is the real edge** — 0 maps to null and the element is OMITTED, because "Sleeps 0" asserts a capacity the data never claimed. Display only. ⚠️ Ledger's "populated" HELD this time (8 of 8) — so **ZERO** was the hidden branch, the inverse of D1-3 | `ReservationRack.php`; `RackUnitCapacityTest` (7 tests) | — |
| `D2-1a` | ⚠️ **LOGGED, NOT BUILT — over-capacity detection.** With the unit's capacity now on the rack beside its bookings, comparing it against a reservation's party size (`adults`+`children`) is newly cheap — an over-capacity booking could be flagged. Deliberately NOT built: it is detection LOGIC, not display, and needs a product decision (warn or block? at booking time or on the rack? what about a cot?) | OPEN — product decision | `ReservationRack.php`; `reservations.adults`/`children` | M |
| `D2-1b` | ⚠️ **The two EXISTING capacity surfaces do not guard `0`.** `operator/owner-detail.blade.php` renders `Sleeps {{ $u['sleeps'] }}` unguarded (its comment reasons about NOT NULL, which is true, but 0 is still storable) and `owner/unit-detail.blade.php` likewise. Both would print "Sleeps 0". Out of scope here — the rack is guarded | STILL-OPEN | `operator/owner-detail.blade.php:132`; `owner/unit-detail.blade.php:367` | S |
| `D5-1` | No housekeeping-readiness chip on the arrival row | STILL-OPEN | `FrontDesk.php:329-334` payload | S |
| `D8-1` | No housekeeping status filter (honestly disabled) | STILL-OPEN | `HousekeepingBoard.php:46` | S |
| `D8-2` | No `ready_by` time per room — **no such column exists** | STILL-OPEN | absent from `database/migrations` | M |
| `D11-2` | No "logged by {person}" on a work-order card | STILL-OPEN | `MaintenanceList.php` | S |
| `D12a-1` | No sub-location/Area on a work order — no column | STILL-OPEN | `app/Models/MaintenanceTicket.php` | S |
| `D13-1` | Maintenance Settings isn't property-scoped in its header | STILL-OPEN | `OperatorMaintenanceSettings.php` — no `ResolvesCurrentProperty` | S |
| `D6-1`/`D6-2`/`D6-3` | Reservations list: no bulk-select, no row quick-actions, no month scope | STILL-OPEN — ⚠️ **read the scope note first** | `ReservationsList.php:57-61` | M |

### 3.4 Reservations, rates & inventory ⚠️ MONEY + INVENTORY

| id | item | status | evidence | size |
|---|---|---|---|---|
| `D18-5` | The rates grid's rows are room **types**, not physical units | STILL-OPEN | `RatesGrid.php:33` — `rate_plan_id` belongs to a room type | M |
| `D18-6` | Only the primary rate plan renders per row | PARTIAL (2.2) | `RatesGrid.php:103,139` | M |
| `D19-1` | Stay dates are not the design's two compact fields + nights counter | UNVERIFIED | | S |
| `D19-3` | No per-unit scarcity badge ("1 left") | STILL-OPEN | `NewReservation.php` | S |
| `D19-4` | The booking summary is not itemised Accommodation / Extras / City tax / VAT | UNVERIFIED — a tax-shaped item; see §8 | | M |
| `D20-1`/`D20-2` | Group booking: no combined date field, no block identity card | STILL-OPEN | `GroupBooking.php` | S |
| `DO4-2` | No Peak rate column on owner Rates | STILL-OPEN | `OwnerRateService`, `OwnerRates` | M |
| `DO4-5` | Rate guardrail floor/ceiling not expressed as per-night euros | UNVERIFIED | `OwnerRateService.php:32` is a comment | S |

### 3.5 Owner portal (non-money)

| id | item | status | evidence | size |
|---|---|---|---|---|
| `DO9-1` | `unit_requests.notes` validated + persisted with no field in the wizard | 🏁 **CLOSED 2026-08-15** — a textarea in step 4 ("Anything else we should know? (optional)"), bound to the name the backend already reads. Validation and persistence **unchanged** — the pass only reached them. Optional (the rule is `nullable`); `maxlength` mirrors `max:2000` with the server still authoritative. ⚠️ **The round-trip already existed AND already escaped** — `OperatorUnitRequests::details()` folds notes into the detail line, rendered `{{ }}`; a `<script>` note was submitted through the real wizard and reaches the operator as inert text (no dialog, no injected tag) | `owner/add-unit.blade.php`; `AddUnitNotesFieldTest` (10 tests) | — |
| `DO9-1a` | ⚠️ **OBSERVATION, not a defect — a long note floods the operator's detail line.** `OperatorUnitRequests::details()` joins notes with the bed/bath/amenity counts into one `·`-separated string, so a 2000-character note would dominate that column. Fine for the short notes the field invites; worth a truncate-with-expand if long notes become common. Not changed — the display path was out of scope and is correct, just terse | OPEN — cosmetic | `OperatorUnitRequests.php:108-109` | S |
| `DO9-2` | No draft/resume on the Add-Unit wizard | 🏁 **CLOSED 2026-08-15** — `draft` added to the `status` enum (an ALTER; the state machine EXTENDED, never a parallel draft table) + Save-as-draft/resume on the wizard, both writes through ONE shared `persist()` and ONE shared rule set. ⚠️ **THE REAL FINDING WAS NOT THE MISSING STATE — IT WAS WHAT WOULD HAVE HAPPENED WHEN IT EXISTED:** `OperatorUnitRequests` carried **no status filter whatsoever**, so every draft would have appeared on the operator's desk immediately. Now excluded via the ONE `UnitRequest::scopeFiled()` (list + KPI counts inherit it). ⚠️ Also: `requested_name` was `NOT NULL`, so a draft of an untouched wizard could not physically be stored; and the owner-list `requestStatusMeta()` default would have labelled a draft **"Pending review"** — the exact opposite of the truth on the one screen where the distinction is the feature | `app/Models/UnitRequest.php`; `OwnerUnitRequestService::saveDraft/findDraft`; `OwnerUnitRequestDraftTest` (13 tests, 4 mutations) | — |
| `DO9-3` | The owner cannot withdraw a submitted request | 🏁 **CLOSED 2026-08-15** — `withdrawn` added to the enum (a SOFT transition, never a delete: the row stays so the record still shows what was asked for and that it was retracted), completing **draft → pending → approved \| declined \| withdrawn**. ⚠️ **THE RACE IS THE FEATURE:** the owner's page is a snapshot, so `withdraw()` re-reads the row **under `lockForUpdate` and re-checks `pending` INSIDE the lock** — retracting an already-approved request would tell the owner it was cancelled while a live, bookable unit existed for it. The refusal names the REAL winning state. ⚠️ **LOGGED, NOT FIXED — the REVERSE race is still open:** `approve()`/`decline()` check `pending` **outside** their transaction and take no row lock, so an approve already in flight can land on top of a completed withdrawal. Guarding those two certified operator paths is a separate item | `OwnerUnitRequestService::withdraw()`; `OwnerUnitRequestWithdrawTest` (11 tests, 4 mutations) | — |
| `DO9-5` | Drop tiles are file pickers, not real drag-and-drop | STILL-OPEN | | S |
| `DO5-2` | No unit photo thumbnails on My Units | STILL-OPEN | `OwnerMyUnits.php` | S |
| `DO5-3` | No sort/filter chips on My Units | STILL-OPEN | `OwnerMyUnits.php` | S |
| `DO10-1` | No occupancy delta vs prior period on Unit Detail (`OwnerOccupancy` can already produce it) | UNVERIFIED | | S |
| `DO10-2` | The owner cannot request a cleaning | STILL-OPEN | housekeeping is operator-side | M |
| `DO12-3` | No live "Unsaved changes / All saved" bar on Settings (per-section saves instead) | STILL-OPEN | `owner-settings.blade.php` | S |
| `DO15-2` | Team "Recent activity" covers invites only, not role changes | UNVERIFIED | | S |
| `DO19-1` | No per-booking timeline/event feed | UNVERIFIED | | M |
| `DO19-2` | No per-booking document list | UNVERIFIED | | S |
| `DO23-2` | The Block-Calendar and Unit-Detail day grids are **duplicated markup**, not shared — ⚠️ §N.8f drift risk | STILL-OPEN | two day-cell implementations | S |
| `DO24-1` | The full-archive ZIP has no builder (honestly disabled today) | STILL-OPEN | no Zip usage in `app/Http/Controllers/Owner` | M |
| `DO24-3` | Per-year archive bars are sparse, not contiguous (a year with no statements is skipped) | UNVERIFIED | | S |

### 3.6 Messaging, inbox & notifications

| id | item | status | evidence | size |
|---|---|---|---|---|
| `D10-1` | No party size / balance in the inbox reservation panel | STILL-OPEN | `OperatorInbox.php` | S |
| `D10-2` | no reservation-STAGE badge on the inbox conversation | 🏁 **CLOSED 2026-08-15** — `stage_meta` resolved by the SHARED `ReservationStage` in the COMPONENT (never re-derived in Blade), rendered with the Reservations list's exact badge markup. ⚠️ A previous pass logged this in the Blade with the precise reason it stopped: the card carried the RAW `status` and `meta()` needs a computed STAGE — feeding it a status renders "Unknown". Calling the resolver is what that note was waiting for | `OperatorInbox.php`; `InboxStageVipBadgeTest` (10 tests) | — |
| `D10-5` | no VIP badge on inbox threads | 🏁 **CLOSED 2026-08-15** — `guests.is_vip` on the guest card, rendered as FRONT DESK's badge character-for-character (same classes, dot and word). Omitted for non-VIP and for a thread with no guest | `OperatorInbox.php`; `InboxStageVipBadgeTest` | — |
| `D10-3`/`D10-4` | Teammate presence + "suggested assignee" | DEFERRED-BY-DECISION (presence needs websockets, §H) | `PROJECT-STATE.md` §H | — |
| `D29-5` / `DO6-4` | **No per-notification dismiss in either realm** | 🏁 **CLOSED 2026-08-15** — ONE shared mechanism: `notifications.dismissed_at` + `NotificationController::dismiss` (the same shared controller mark-read uses, self-only by construction) + a single filter in the ONE shared `NotificationFeed`, so the operator page, the owner page AND the bell panel all inherit it. ⚠️ **THE ANTI-CONFLATION IS THE POINT**: `owner_attention_states.dismissed_at` is a DIFFERENT entity (the owner's "needs attention" signal) and is provably untouched — asserted in the guard, mutation-proven, and confirmed live. Dismiss ≠ mark-read (separate columns; a read row still shows) | `NotificationController.php`; `NotificationFeed.php`; `NotificationDismissTest` (10 tests) | — |
| `DO6-2` | No Today/Earlier grouping on the notification feed | STILL-OPEN | `OwnerNotifications.php` | S |
| `D30-3` | No daily digest at a chosen hour | STILL-OPEN | `OperatorNotificationPreferences.php:26` | M |
| `DO27-1` | No per-thread unread count | PARTIAL (2.2) | `OwnerMessages.php:625` | S |
| `DO-G7-1` | No delivery/read state for the guest (absence is honest, not fabricated) | UNVERIFIED | | M |

### 3.7 Identity, auth & RBAC

| id | item | status | evidence | size |
|---|---|---|---|---|
| `D24-2` | **Operator Security has no passkeys** | 🏁 **CLOSED 2026-08-15** — the operator realm mounts the SAME `PasskeyController` on the SAME `PasskeyService`, and both Security pages render ONE shared `<x-auth.passkeys>` card (the owner's markup was EXTRACTED, not copied). ⚠️ Sign-in needed no wiring: already on the shared `web` guard via `RedirectsByRole`. **Proven end-to-end with real crypto** (CDP virtual authenticator): operator registers → `app.passkey_registered` → usernameless sign-in → `/app/dashboard` | `resources/views/components/auth/passkeys.blade.php`; `OperatorPasskeyTest` (12 tests) | — |
| `D24-2a` | ⚠️ **The audit trail said `owner.` for every passkey event.** Found while wiring: the shared controller hardcoded `owner.passkey_registered`/`_removed`, so an OPERATOR registering a passkey would have written a false realm into the audit log — invisible until someone read it back. Fixed to `app.*`/`owner.*` by mount, and confirmed by the real browser flow, not just the source | 🏁 CLOSED with D24-2 | `app/Http/Controllers/Auth/PasskeyController.php` | — |
| `DO13-4` | **Password composition is length-only** | 🏁 **CLOSED 2026-08-15** — one policy (`App\Support\Auth\PasswordPolicy`, registered as `Password::defaults()`): min 8 + letters + numbers + symbols, applied at **NINE** paths with the matching UI sentence on **NINE** screens. ⚠️ **The audit named 4 of the 9** — a sweep found public self-signup, the platform-admin account and all three guest paths. `uncompromised()`/HIBP flagged as a founder call, not bundled | `app/Support/Auth/PasswordPolicy.php`; `PasswordCompositionTest` (14 tests) | — |
| `DO13-4a` | ⚠️ **OPTIONAL, founder call — `uncompromised()` (Have-I-Been-Pwned).** Rejects passwords found in known breaches via a k-anonymity API. Stronger, but it adds an outbound dependency on every password set and rejects passwords users consider fine (a support-load and UX decision, not a code one). One line in `PasswordPolicy::rule()` if wanted; `hint()` must gain a matching sentence in the same change | OPEN — founder decision | `app/Support/Auth/PasswordPolicy.php` | S |
| `D24-1` | No session location (city/country) beside device + IP | STILL-OPEN — needs a geo-IP source (external) | `OperatorSecurity.php` | M |
| `DO13-1` | No new-device email alert, no full sign-in history, no session timeout (named honestly in a "Coming soon" card) | STILL-OPEN | `owner-security.blade.php:301-308` | M |
| `DO13-3` | The data export is an immediate JSON download, not the promised emailed secure link | STILL-OPEN | | S |
| `D23-2`/`D23-3` | Permissions aren't grouped into friendly capability domains; roles aren't a card grid | STILL-OPEN | `OperatorRoles.php` | M |
| `DO-G11-1` | An expired guest link's 403 shows **operator** role/permission copy to a guest | UNVERIFIED — see §8 | register `§G-11` | S |

### 3.8 Guest portal

| id | item | status | evidence | size |
|---|---|---|---|---|
| **1.3** | **`joinWaitlist()` has no dedupe at either layer** | STILL-OPEN | §1.3 | **S** |
| `DO-G10-4` | `resources/views/guest/partials/trip-card.blade.php` is **dead code** duplicating the live trip card | STILL-OPEN (confirmed present) | file exists; ⚠️ **log-don't-fix: not deleted in this pass** | S |
| `DO-G5-1` | No cancellation **review** step (refund breakdown + policy before confirming) | UNVERIFIED | | M |
| `DO-G5-2` | No request-history surface | UNVERIFIED | | S |
| `DO-G6-2` | A pending payment is invisible rather than shown as processing ⚠️ **the same shape as 1.1, on the guest side** | UNVERIFIED — worth checking with 1.1 | | S |
| `DO-G8-2` | No visible moderation timeline on a review | UNVERIFIED | | S |
| `DO-G9-1` | The GDPR erase depends on the daily cron. **The command IS scheduled** (`routes/console.php:38`, `dailyAt('03:00')`) — the exposure is the ONE crontab line, which is a **deployment** item (§3.11), not a code gap | VERIFIED-SCHEDULED | `routes/console.php:38`; `docs/DEPLOYMENT.md` | — |
| `DO-G9-2` | The guest isn't told what deletion *keeps* | UNVERIFIED | | S |
| `DO-G1-2` | The past trip card shows no rating | UNVERIFIED | | S |

### 3.9 Exports & documents (PDF / CSV / XLSX)

| id | item | status | evidence | size |
|---|---|---|---|---|
| `DO20-1` | **No page numbering on any PDF.** `PdfService` stamps a per-page watermark but no "Page X of Y" — a multi-page statement can't be checked for completeness | STILL-OPEN | no page-count token in `app/Services/PdfService.php` | S |
| `DO11-3` | The Statement PDF's footer is single-shot, not a repeating page footer | UNVERIFIED | | S |
| `DO11-4` | The `PdfService` watermark measures 2.70 on white — the one on-paper contrast failure left | STILL-OPEN (design programme, §3.12) | | S |
| `D4-1`/`D12b-1` | Excel exports — no spreadsheet library (§3.1) | STILL-OPEN | | M |
| `DO24-1` | Archive ZIP (§3.5) | STILL-OPEN | | M |

### 3.10 Channels / OTA — ⚠️ DEFERRED-BY-DECISION, **not** remaining work

**`D2-2`, `D26-1..5`, `DO8-3` and the OTA brand split are all ACCOUNT-GATED on the Channex cutover**
(`PROJECT-STATE.md` §H). Verified: `channels.provider`/`name` and `reservations.channel_id` are **modelled**;
what is missing is *attribution*, which only a live import can write. `app/Services/ChannelConnectionService.php`
and `ChannelMappingGuard.php` are the local halves; there is **no Channex client**, correctly.

⚠️ **Do not build a brand badge, a "Synced" dot or a last-sync time before the cutover** — sweep §5 / #37.

### 3.11 Platform & infrastructure — ⚠️ **THE ONLY LAUNCH BLOCKERS**

| item | status | evidence | size |
|---|---|---|---|
| ⛔ **No deploy artifacts at all** — no Dockerfile, no CI/CD, no `.github/`, no deploy script, no backup strategy | STILL-OPEN — **LAUNCH BLOCKING** | verified: nothing matching `Dockerfile`/`docker-compose`/`.github`/`Procfile`/`fly.toml` at repo root. `docs/DEPLOYMENT.md` is a runbook, not automation | **L** |
| ⛔ **The ONE cron line** `* * * * * php artisan schedule:run` — without it **8 scheduled commands silently never run, including GDPR 14-day guest deletion and the 30-day ID purge** | STILL-OPEN — **LAUNCH BLOCKING** (part of deployment) | `routes/console.php:38`; `CLAUDE.md`; `docs/DEPLOYMENT.md` | S |
| ⛔ **The founder's Stripe/VAT go-live steps** — billing code+build is COMPLETE; the flip is not code | BLOCKED-ON-FOUNDER — **LAUNCH BLOCKING** | `PROJECT-STATE.md` §G.1; `docs/BILLING-STRIPE-PRICES.md` | — |
| **Zero caching app-wide** — `Cache::remember` appears **0 times** in `app/` | STILL-OPEN — post-launch | grep | M |
| **Pagination is partial** — only 4 view files use the shared `<x-pager>` | STILL-OPEN — post-launch | grep | M |
| Composite indexes worth 73×–200× at 500k rows; N+1 (210 queries on `/app/insights`, 116 on the storefront) | **UNVERIFIED — not re-measured this pass** | `PROJECT-STATE.md` §I.3 (audit-recorded) | L |

### 3.12 The design programme's own remainders (a separate, already-scoped track)

Not backend work, but genuinely open and tracked in the same register:

| id | item | state |
|---|---|---|
| `DO-EMAIL-5-continued-6` | **~49 of the 67 data-conditional files remain UNMEASURED** on axis 3. ⚠️ "logged-unswept" means unmeasured, **not** "probably fine" | OPEN — throughput, at the founder-chosen depth |
| `DO-STATE-2` | 10 further scrim modals (carry the fix, unmeasured) · inactive tab panels · 8 accordions · 9 focus reveals · toasts · loading · disabled | OPEN |
| `DO-PROBE-3` (C) | 5 dompdf PDFs unreachable by a browser probe | ⛔ **BLOCKED ON TOOLING** — needs `brew install poppler` or `ghostscript` |
| `DO-PROBE-4` | a small label whose box contains an adjacent coloured marker captures the wrong plate | OPEN (logged with reason) |
| `DO-A11Y-1-continued` | further shell-copy misses | a **watch item**, not a task |

---

## 4 · ⚠️ WHAT IS **NOT** REMAINING WORK (do not "discover" and build these)

Counting these as undone would be the single easiest way to corrupt this ledger.

- **69 refusal-class rows.** Fabricated figures, invented history, fake channel sync, guest PII on owner
  surfaces, editable role catalogues, stored cards, open guest self-signup. Authorities:
  `docs/design/DIVERGENCE-REGISTER.md` (40 ratified divergences), `WIREFRAME-PARITY-SWEEP-2026-08-08.md` §5,
  `PROJECT-STATE.md` §H.bis.
- **22 `ACCOUNT-GATED` rows.** Channex · live Stripe charge/payout · SMS (Twilio) · push (FCM/APNs) ·
  websockets/presence · live LLM (a real `FakeLlmClient` today) · third-party KYC (capture-only,
  `verification_status = 'pending'`, never "verified" — `GuestIdDocumentService.php:94`) · tax rates
  (`tax:set-rate`, seeded rates are EXAMPLE only). **These are credentials, not code.**
- **The §6 "extra work to keep"** — capability the product has that the wireframes never asked for. Removing
  it in the name of parity is a regression.

---

## 5 · SUGGESTED SEQUENCE — ⚠️ **a recommendation for the founder, not a decision**

**Wave 0 — correctness (do first; ~1 short pass total).** All three are small and all three are cases where
the product currently misinforms or loses data: **1.1** the payout status map (⚠️ money, owner-facing) →
**1.2** the Schedule PDF's false statements → **1.3** the waitlist dedupe. Nothing depends on them, and they
are the only items in this ledger that are *wrong* rather than *absent*.

**Wave 1 — foundational/unblocking.** `D34-1` extend `RosterWeek` (**unblocks the rest of 1.2**) ·
`DO13-4` password composition rules (⚠️ security, one-line-per-controller) · `D24-2` wire the **already-built**
passkey subsystem into the operator realm · `D3-4` the LY coverage guard (⚠️ affects every new tenant's first
year, and it feeds the KPI deltas).

**Wave 2 — high-value user-facing.** The one-column-away wins first: `D1-3` expected_arrival · `D2-1`
max_occupancy · `DO9-1` the `notes` field — **each is a real, populated column the UI simply never reads.**
Then `D3-1` custom ranges · `DO3-3` failed-payout recovery · `D29-5`/`DO6-4` notification dismiss ·
`D10-2`/`D10-5` inbox stage + VIP (both reuse existing helpers).

**Wave 3 — features proper.** `D1-1` task completion · `D8-2` `ready_by` · `DO9-2`/`DO9-3` draft + withdraw ·
`D18-5`/`D18-6` the rates-grid shape · `D23-2` permission grouping · `DO24-1` archive ZIP · Excel exports.

**Wave 4 — polish + de-duplication.** `DO25-1` and `DO23-2` (§N.8f drift risks) · the 3 remaining hand-rolled
star colours · `DO20-1` PDF page numbering · delete the dead `guest/partials/trip-card.blade.php`.

**Parallel, and the actual gate on launch:** ⛔ the **deployment sprint** (§3.11) and the founder's **Stripe/VAT
steps**. ⚠️ **No feature in Waves 0–4 blocks launch. Deployment does** — and inside it, the single crontab
line, because GDPR deletion depends on it.

**Post-launch, before the first large tenant:** the scale-perf workstream (§3.11, unverified figures).

---

## 6 · PREMISE CORRECTIONS THIS AUDIT PRODUCED

1. ⚠️ **`D16-7`/`DO14-1`/`DO17-1`/`DO21-1`/`DO21-2`/`DO24-2` are ONE defect, and it has THREE definitions.**
   Six register entries, each written from one page, describe a single shared helper. The register never
   spotted the operator's two private copies at all (§1.1).
2. ⚠️ **The Schedule PDF's "there is no draft or published state" is FALSE and rendered to a user.** The
   register logged the missing *feature*; the false *sentence* three lines away went unrecorded (§1.2). This
   is §N.13.1 holding again — the defect sits beside the flagged thing.
3. ⚠️ **`D9-2` is SINCE-DONE** — open shifts were built into the grid after the entry was written. Treating
   the register as current would have rebuilt them.
4. ⚠️ **`D24-2` is mis-sized in the register.** Passkeys are not unbuilt; they are built and wired to one
   realm. **M → S.**
5. ⚠️ **`D3-4` does not affect the owner realm.** `forOwnerUnits()` uses a units×days denominator, not the
   spine — the defect is operator-path-only. The register did not draw that line.
6. ⚠️ **`DO-G9-1` overstates its exposure.** `guests:process-deletions` **is** registered and scheduled
   (`routes/console.php:38`). The real exposure is the deployment crontab line, which belongs in §3.11.
7. ⚠️ **`DO14-2`, `DO5-5`, `D16-5` and most of `DO10-4` are SINCE-DONE.** Four more stale rows.
8. ⚠️ **The register is ~50% larger than a table-scan suggests.** It carries **265** item rows in **three**
   formats — the `| D1-1 |` tables (176), the `### 📌 MISSING BACKEND` bullets the owner gates switched to,
   and the `### 🚩 LOGGED — not built` bullets the guest gates switched to again. A reader (or a script) that
   walks only the tables misses **89 rows**, nearly all of them owner and guest. Worth normalising before the
   backend phase leans on it.
9. **Minor:** the audit brief cites `docs/DIVERGENCE-REGISTER.md`; the file is at
   `docs/design/DIVERGENCE-REGISTER.md`.

---

## 7 · METHOD

For each item: **UI → route → controller/Livewire → service → query → table**, plus a grep for the named
mechanism in **both** directions (present *and* absent), plus the code-wide tells sweep (§0). A status was
recorded only where a file+line supports it; everything else says UNVERIFIED and why.

⚠️ **A "— none" grep result was never treated as proof on its own** where the mechanism could be named
differently. Where the first grep was ambiguous, the file was opened and read (that is how `DO5-5`, `DO14-2`,
`D16-5` and the `payoutStatusMeta` triple were resolved — three of them in the SINCE-DONE direction).

---

## 8 · ⚠️ THE AUDIT'S OWN COVERAGE — the honest bound

**Verified deeply** (traced through the layers, code read): **~40 register entries** + the whole of §1 + the
infrastructure facts in §3.11 + the two code-wide sweeps (tells, dead routes). Specifically: the entire
payout-status cluster, the schedule/roster chain, `D1-3`, `D2-1`, `D3-1`, `D3-4`, `D4-1/2/3`, `D5-1`, `D6-*`,
`D9-1/2`, `D18-5/6`, `D24-2`, `D34-1/2`, `DO1-1`, `DO5-5`, `DO6-2/4`, `DO9-1`, `DO10-4`, `DO13-4`, `DO14-2`,
`DO-G9-1`, `DO-G10-3`, `DO25-1`, `DO27-1`.

**Mechanically re-checked** (targeted grep of the named mechanism, hit/miss recorded, code not read):
**~44 entries** — most of §3.3–§3.7's smaller rows.

**NOT re-verified this pass — status carried from the register, marked UNVERIFIED (~36 rows):**
- **Most guest-realm bullet items** (`DO-G1-*` … `DO-G12-*`). Reason: the guest sections use a third register
  format and the pass ran out of depth budget after the operator + owner realms. Sampled, not swept.
- **`§O-20` … `§O-32`** bullets beyond the ones cited. Reason: same.
- **`DO11-1` (`+€0.00`)**, **`DO11-3`**, **`D19-4`** (VAT/city-tax itemisation), **`DO4-5`**. Reason: each
  needs a *rendered* surface with the right data forced to confirm, not a grep — and forcing data was out of
  scope for a read-only pass.
- **All performance figures** (index gains, the 210/116 query counts). Reason: not re-measured; they are
  audit-recorded from `PROJECT-STATE.md` §I.3.
- **The 5 dompdf PDFs.** Reason: ⛔ genuinely unreachable — no rasterizer in this environment.

**Not reached at all:** the 69 refusal-class rows were classified from the register and its authorities
rather than re-argued (correctly — re-litigating a ratified refusal is how fabrication gets rebuilt); the
`docs/design/raw/` prototypes were not re-decoded; the test suite was not re-read for coverage gaps.

⚠️ **What this means for the reader.** The **shape** of the remaining work is trustworthy and the §1 items are
proven. The **count** of open items is a floor, not a ceiling: the ~36 unverified rows will resolve into some
mix of STILL-OPEN and SINCE-DONE, and — on the evidence of this pass, where **6 of 84 checked rows had already
been built** — expect roughly **1 in 14 of them to be stale**.

---

## 9 · READ-ONLY CONFIRMATION

- **No code, test, stub, migration, config or view was touched.** The only write is this file.
- Items found trivially fixable were **logged, not fixed** — the dead `guest/partials/trip-card.blade.php`,
  the 3 hand-rolled star hexes, the Schedule PDF's false sentence, the `payoutStatusMeta` triple. Fixing them
  mid-audit would have cost the map its neutrality.
- Baseline **unchanged and unmeasured by this pass** (recorded from the run immediately preceding it):
  suite **2076/2085**, 13,460 assertions, 7 non-green (the documented set, inside the 5–10 band);
  PHPStan L5 **0**; Pint clean.
