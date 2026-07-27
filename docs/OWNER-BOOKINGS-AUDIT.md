# Owner Bookings — page + Booking-Summary PDF: wireframe-fidelity audit

**Date:** 2026-07-28 · **Author:** build session · **Status:** PHASE 1 (read-only) complete — awaiting GATE 1 approval.

Founder brief: make the LIVE **owner Bookings page** (`/owner/bookings`) **and** the **owner Booking-Summary PDF**
(`owner.statements.bookings` → `OwnerBookingSummaryDocument`) match their two decoded wireframes EXACTLY — design **and**
behaviour — on real owner-scoped data. **"Prior deferrals are IN SCOPE."** Two DIFFERENT deliverables. Honesty is
non-negotiable throughout (#39 no fabrication · #28 guest name-only, no contact · #17 reconcile to the cent · #20 owner
writes on controller routes).

This is the same bar the founder already raised for the **Owner Dashboard** (`docs/OWNER-OVERVIEW-AUDIT.md`, invariant
#54, DIVERGENCE row 126) and **My Units / Add-Unit** (invariant #55): *exact wireframe match with REAL backends; prior
AT-PARITY/REFUSED items become REAL once a real backing is added — never faked.*

---

## 0. Method

- **Decoded both wireframes** by rendering the `dc-runtime` bundles (`docs/design/raw/Owner Bookings.html`,
  `docs/design/raw/Owner Booking Summary PDF.html`) over a throwaway static server (`:8899`) in the browser →
  full-page screenshots.
- **Audited the live page** in the browser logged in as the demo owner (`/owner/bookings`) → screenshot + read the
  Livewire component (`app/Livewire/Owner/OwnerBookings.php`) and view (`resources/views/livewire/owner/bookings.blade.php`).
- **Audited the live PDF** by reading `OwnerBookingSummaryDocument` + `resources/views/pdfs/owner-booking-summary.blade.php`
  and generating the real document data for the one demo statement (Sep 2026 · Demo Owner · gross €1,080.00).
- **Verified data availability** for every wireframe figure (see §3) — the decisive question, because it determines
  what is honestly buildable vs what must stay refused.
- Cross-referenced `docs/design/DIVERGENCE-REGISTER.md` rows 127 (Owner Bookings) + the PDF twin, and the escalation
  precedent in row 126 (Owner Dashboard).

**Global chrome is out of scope.** The wireframe's reduced sidebar (Calendar / Balance / Expenses / MANAGE / ACCOUNT
groups) is already a locked decision — DIVERGENCE row 126 **Decision D2**: *"the wireframe's reduced sidebar is matched
in STYLE but the extra real nav items are kept (shared shell — no functional regression)."* We touch the Bookings page
+ the PDF only, reusing the existing owner shell.

---

## 1. DELIVERABLE A — Owner Bookings page (`/owner/bookings`)

### 1a. Wireframe (decoded)
Warm-glass owner chrome (frosted sidebar/topbar). Breadcrumb **‹ Overview / Bookings**. Header **Bookings** + subtitle
**"14 reservations · 3 units"**. Then, top-to-bottom:

1. **4 stat cards** — `UPCOMING 6` · `GUESTS 18` · `NIGHTS 28` · `NET TO YOU €7,352` (uniform 4-across band).
2. **"Bookings over time"** card (≈⅔ width) — subtitle *"Reservations by check-in month, with net revenue"*; legend
   **● Confirmed 4 · ● Pending 2 · •-• Net revenue**; per-check-in-month **segmented status bars** (Confirmed base +
   Pending stacked), a **net-revenue line/point** overlaid (white dot + value bubble `€7.4k /mo`), and a **dashed goal
   line** at the target level (right-labelled `€6k /mo goal`); x-axis month labels (`Oct '25`).
3. **"Monthly goal"** card (≈⅓ width) — a **terracotta progress ring** with `123%` / `OF GOAL`; below it
   `Oct '25 net` / `€7.4k of €6k` / green pill `+€1.4k over`; a bottom control **`Target €6k /mo ✏️`** (editable).
4. **Filters** card — tabs **Upcoming / Past / All**; **search** ("Search guest, unit or confirmation"); dropdowns
   **All sources ▾ · All statuses ▾ · All units ▾** (with counts + a ✓ on the selected); a **date-range** control
   **📅 Any dates ▾** (presets This month / Last month / Next 30 days / This year + custom From–To + Clear dates);
   **⬇ Export**.
5. **Bookings table** — columns **BOOKING** (guest name-only + `VY-25-####` ref, sortable) · **UNIT** · **DATES**
   (sortable, shows ↑) · **NTS** · **SOURCE** (dot + channel) · **STATUS** (Confirmed/Pending pill) · **NET** (sortable) ·
   chevron.
6. **Footer** — `Showing 1–6 of 6 upcoming bookings` (left) + **Back to overview** link (right). Pagination implied.

### 1b. Live page (current)
Same warm chrome. **No breadcrumb.** Header **Bookings** + subtitle **"2 units"** (units only). 4 stat cards
`UPCOMING 7 · PAST 1 · NIGHTS 18 · NET TO YOU €885.60`. Filters: tabs ✓, search ✓, dropdowns **All sources · All units ·
Any month** (no Status dropdown; a month dropdown instead of a date range; **no Export**). Table columns BOOKING · UNIT ·
DATES · NTS · SOURCE · STATUS · NET-TO-YOU (**not sortable**; Status shows the **stage** badge "Upcoming", not
Confirmed/Pending; SOURCE shows the **real enum** "Direct/Corporate/OTA"). Footer: `Showing 7 bookings · upcoming.`
(no pagination, no "Back to overview"). Data is real + owner-scoped: guest **name-only** (Demo Booker/Demo Resident),
real `R-######` refs, honest **"Not yet attributed"** for unattributed net. **No chart. No goal card.**

### 1c. Gap table — page

| # | Wireframe element | Live | Kind | Resolution |
|---|---|---|---|---|
| A1 | Breadcrumb "‹ Overview / Bookings" | absent | KIND-1 | Add breadcrumb (shell already supports it on other owner pages). |
| A2 | Subtitle "N reservations · M units" | "M units" | KIND-1 | Add the real reservation count to the subtitle. |
| A3 | Stat card #2 = **GUESTS** (distinct guests) | **PAST** | KIND-1 | Real: distinct lead-guest count across the owner set. (Keep Past reachable — it's the tab.) |
| A4 | **"Bookings over time"** chart (segmented status bars + net line + goal line) | absent | **KIND-1 (was KIND-2 refused)** | **REAL** — status counts per check-in month (real `reservations.status`), net/month (`OwnerLedgerAttribution`), goal line (owner's real target §A6). See §4. |
| A5 | **"Monthly goal"** ring `123% of goal` + `Target €/mo` editable | absent | **KIND-1 (was KIND-2 refused)** | **REAL once a target backing exists** — add a per-owner monthly target (`owner_monthly_target`, minor units + currency) + a `can:`-gated controller-route write (#20); `% = real net ÷ real target`. The "fabricated goal with no backing column" becomes real by *adding the column the owner sets themselves*. See §4. |
| A6 | **Status** dropdown (with counts + ✓) | absent | KIND-1 | Real: the reservation statuses present in the owner set. GET filter. |
| A7 | **Date-range** control (This month/Last month/Next 30/This year/custom/Clear) | "Any month" dropdown | KIND-1 | Replace the month dropdown with the date-range control; real GET filter over real rows (keeps the owner-scope-middleware re-run — no wire action, #20). |
| A8 | **Export** (CSV) | absent | KIND-1 | Real: a new `owner.bookings.export` controller route streaming the owner-scoped rows as CSV (mirrors the existing `owner.units.export`), export-gated. |
| A9 | Sortable **Booking / Dates / Net** columns (↑/↓) | not sortable | KIND-1 | Real: GET `?sort=&dir=` over the owner-scoped set (server-side, so scope middleware still runs). |
| A10 | Pagination + "Showing 1–N of T" + **Back to overview** | count line only | KIND-1 | Real: paginate the owner set; add the "Back to overview" link. |
| A11 | SOURCE shows **Booking.com / Airbnb** brands | real enum ("Direct/OTA") | **KIND-2 → keep honest** | The OTA **brand is not modelled** (`reservations.source` is a 7-value enum; `channels.provider` = 'channex' only). Keep the **real source label**; the brands are placeholders. (Live is already correct here.) |
| A12 | STATUS = **Confirmed / Pending** pill | **stage** badge ("Upcoming") | KIND-1 | Show the real reservation **status** (confirmed/pending/…) as the wireframe does; the temporal split stays on the tabs. Keep the shared `ReservationStage` for the tab logic. |

**Net for the page: 11 of 12 rows are KIND-1 and buildable on REAL data.** The only KIND-2 refusal is the OTA-brand
label (A11) — already handled correctly in the live page. The two items formerly refused as fabrication (chart A4, goal
A6) become **real** because their figures are real aggregates and the goal is a value the **owner supplies**, not one we
invent.

---

## 2. DELIVERABLE B — Booking-Summary PDF

### 2a. Wireframe (decoded)
White letterhead print document.

- **Letterhead:** terracotta "V" mark + **Seaside Villas Lda** (bold) / *Managing agent · Faro, Portugal · VAT PT 512 034 776*.
  Top-right: eyebrow **BOOKING SUMMARY** / ref **BS-2025-09**.
- **Title** *Booking summary* + subtitle *September 2025 · Amara Okafor · Seaside Villas · 3 units*.
- **KPI band (4 cells, bordered):** `BOOKINGS 18` · `NIGHTS 64` · `OCCUPANCY 78%` · **`GROSS REVENUE €12,400`**
  (last cell highlighted peach + terracotta).
- **By unit** table: `UNIT · BOOKINGS · NIGHTS · OCCUPANCY · REVENUE` (Villa Azure 7/24/82%/€4,660 …), **Total** row
  (terracotta, top border) reconciling to €12,400.
- **By channel:** `Direct · 7 bookings · no commission · €4,820` / `Booking.com · 7 bookings · €620 commission · €4,960` /
  `Airbnb · 4 bookings · via channel manager · €2,620`.
- **Bookings this month** table: `REF · GUEST · UNIT · DATES · NIGHTS · REVENUE` (VYR-10419 · J. Okonkwo · Villa Dunes ·
  9–12 Sep · 3 · €1,010 …), **8 rows shown**.
- **Footer callout** (peach box + left terracotta bar): reconciliation note, references **statement OS-2025-09**, ends
  *"Showing 8 of 18 bookings — the full list is available in your portal."*
- **Footer line:** `Seaside Villas Lda · VAT PT 512 034 776` (left) · `Booking summary · September 2025` (right).

### 2b. Live PDF (current)
Vyrobel-branded document. Header: "V Vyrobel" + operator name muted; right eyebrow "Booking summary" + `September 2026`
+ `Statement #{sequence_no}`. **Meta table** (Owner / Period / Currency). **3-cell KPI** (Bookings / Nights / Gross —
**no occupancy**). **By unit** (Unit/Bookings/Nights/Revenue — **no occupancy column**). **By source** (real enum — no
commission notes). **Bookings in this period** (Ref / Unit / Dates / Nights / Revenue — **no Guest column**, never
truncated). Plain-text reconciliation note + real reversed/prior-period disclosures. Footer: *"Guest details are not
shown on owner documents."* Every figure reconciles to `gross_minor` to the cent (Σ room_revenue rows === gross).

### 2c. Gap table — PDF

| # | Wireframe element | Live | Kind | Resolution |
|---|---|---|---|---|
| B1 | Letterhead: **managing-agent name + VAT + country** | "Vyrobel" + operator name | KIND-1 | **REAL** — `Organization.billing_company` (fallback `name`) + `billing_vat_id` + `country` (all real, captured at subscription checkout). Render *"Managing agent · {Country} · VAT {billing_vat_id}"*; omit the street/city (**not modelled** — the wireframe's "Faro" is a placeholder). VAT line only when `billing_vat_id` is set. |
| B2 | Ref **BS-YYYY-MM** (top-right) | "Statement #{seq}" | KIND-1 | **REAL (period-derived)** — `BS-{period_start Y-m}` as the doc id; cross-reference the **real statement** by its actual `sequence_no` in the footer callout (the wireframe's "OS-2025-09" is the statement placeholder → bind to the real number). |
| B3 | Subtitle line *period · owner · property · N units* | meta table (Owner/Period/Currency) | KIND-1 | Restructure to the single subtitle line; all real (period, owner name, property/org, unit count). Currency stays implicit in every € figure (+ optional footnote if non-EUR). |
| B4 | KPI band **4 cells incl. OCCUPANCY**, gross highlighted | 3 cells, no occupancy | **KIND-1 (was refused)** | **REAL** — occupancy over `[period_start, period_end)` via `PerformanceMetrics::forOwnerUnits(ownUnitIds,…)->occupancyPct()` (owner-unit-scoped, fails closed). **The docblock's "no honest owner-scoped occupancy" refusal is STALE** — it predates `OwnerOccupancy` / `PerformanceMetrics`. Highlight the Gross cell. |
| B5 | **By unit** adds **OCCUPANCY** column | absent | **KIND-1 (was refused)** | **REAL** — per-unit occupancy via `PerformanceMetrics::forOwnerUnits([unitId],…)` (or `OwnerOccupancy::forMonth` per-unit). Revenue column stays the **attributed** figure (keeps the Σ===gross reconciliation intact). |
| B6 | **By channel** with **commission notes** | "By source", no commission | **KIND-1 + honesty** | **REAL** — group by the real `source` enum (keep — brands unmodelled, A11); add the **real** channel-commission note per group from `owner_ledger` **`ota_cost`** lines (`Direct → no commission` is trivially true; OTA groups show real `ota_cost`). No fabricated Booking.com/Airbnb split. Heading may read "By channel". |
| B7 | **Bookings this month** adds **GUEST** column (name-only) | absent (guests omitted) | **KIND-1 (precedent reversal)** | **REAL name-only (#28)** — the owner Bookings **page** and Booking **Detail** already show guest **names** (name-only, no contact), proven by `OwnerBookingsTest`; the PDF's guest omission is the outlier. Add name-only Guest; **replace** the footer "Guest details are not shown" line accordingly. NO email/phone/contact — ever. |
| B8 | Ref format **VYR-#####** | real `reservations.reference` | KIND-2 → keep honest | Placeholder; render the **real** reference (already correct). |
| B9 | Footer **callout** styling (peach box + left bar) | plain text | KIND-1 | Restyle the reconciliation note as the callout; keep the real reversed/prior-period disclosures inside/after it. |
| B10 | Footer line: **identity + VAT** / doc-label + period | "Guest details not shown · Vyrobel · {operator}" | KIND-1 | Two-column footer (billing_company + VAT ‖ "Booking summary · {period}"). |
| B11 | **"Showing 8 of 18"** truncation | full list, never truncated | **DECISION** | *Recommend keep the full list* (truncating a document whose whole purpose is to reconcile to gross undermines it, and the list is short in practice) **but adopt the callout styling**. If the founder wants the exact truncation, cap at N with an honest *"Showing N of T — full list in your portal"* note (real counts). Flagged for the gate. |

**Net for the PDF: every wireframe figure now has an honest owner-scoped source EXCEPT the OTA brand split (B8/by-channel
brands), which stays the real source enum.** The three items the old docblock refused — **occupancy (B4/B5), the guest
column (B7), the commission note (B6)** — are all now buildable on real data (occupancy/commission backends shipped after
that docblock was written; guest name-only is an established owner-surface precedent). The one genuine, standing refusal
is fabricating channel **brands**.

---

## 3. Data-availability findings (the decisive checks)

| Wireframe figure | Honest source in the codebase | Verdict |
|---|---|---|
| Chart status counts / month | `reservations.status` grouped by check-in `Y-m` (owner set) | ✅ real |
| Chart net-revenue line / month | `OwnerLedgerAttribution` net per month (owner-scoped ledger) | ✅ real |
| Goal ring % + "€X of €Y" + over/under | **needs a real target store** → add `owner_monthly_target` (minor units + currency) + a `can:`-gated write route; `% = net ÷ target` | ✅ real once backing added |
| Export CSV | new `owner.bookings.export` controller (mirrors `OwnerUnitsExportController`) | ✅ real |
| PDF headline + per-unit **occupancy** | `PerformanceMetrics::forOwnerUnits(unitIds, start, end, ccy)->occupancyPct()` (arbitrary range) / `OwnerOccupancy::forMonth` — **owner-unit-scoped, fails closed** | ✅ real (refusal was stale) |
| PDF per-channel **commission** | `owner_ledger.entry_type = 'ota_cost'` (also `commission`, `management_fee`) attributed per (reservation, unit) | ✅ real |
| PDF **guest name** column | `reservations.leadGuest` name-only (as the page/Booking-Detail already do, #28) | ✅ real (name-only) |
| Letterhead **VAT / company / country** | `Organization.billing_company`, `billing_vat_id`, `country` | ✅ real (street/city not modelled → omit) |
| PDF/page **OTA brand** (Booking.com vs Airbnb) | **not modelled** — source enum only | 🚫 stays honest (real source label) |
| Revenue reconciliation (Σ === gross) | unchanged — attributed `owner_ledger` room_revenue | ✅ preserved |

---

## 4. Build plan

### GATE 1 — Owner Bookings page (design + real backends)
1. **Backing store (goal):** migration `owner_monthly_target` (`owner_id`, `currency`, `target_minor`, unique
   `(owner_id, currency)`) + model; a `can:`-gated **controller-route** write `owner.bookings.target` (POST, #20 — never a
   Livewire mutation, so `SetOwnerScopeForPortal` runs). Owner-scoped; primary-owner/co-owner per the capability matrix.
2. **Export:** `OwnerBookingsExportController` → `owner.bookings.export` (GET, export-gated), streaming the owner-scoped
   rows as CSV. Mirrors `OwnerUnitsExportController`.
3. **Component (`OwnerBookings`):** extend `render()` to also compute — the **GUESTS** distinct count; **status** filter +
   options-with-counts; **date-range** filter (presets + custom, GET); **sort** (`?sort=&dir=`); **pagination**; the
   **chart series** (per-check-in-month status counts + net + the target line); the **goal** block (net-this-month vs the
   real target). All server-side over the already-owner-scoped set (no wire action → scope middleware intact, #20).
4. **View (`bookings.blade.php`):** breadcrumb; subtitle "N reservations · M units"; 4-card band (`GUESTS`); the
   **"Bookings over time"** chart (reuse `.vy-chart`/`.vy-chart-col`; add **segmented-stack** + **net-line** +
   **goal-line** presentation); the **"Monthly goal"** ring; the filter row (Status dropdown + date-range + Export);
   sortable headers; pagination + "Back to overview".
5. **Owner layout CSS (`components/layouts/owner.blade.php`):** add the chart-segment / net-line / goal-line classes and
   a `.vy-ring` donut, using existing `--vy-*` tokens (operator layout's `.vy-bar.tone-*` is the precedent for status
   colours). Presentation only; existing `repeat(3)` pages untouched.
6. **Tests:** extend `OwnerBookingsTest` — target write is owner-scoped + capability-gated + mutation-proven; export is
   owner-scoped + export-gated (cross-owner 404); chart/goal figures reconcile to `OwnerLedgerAttribution`; guest stays
   name-only (#28); no wire-action mutation (#20). `assertNoRawBlade()`.

### GATE 2 — Booking-Summary PDF (design + real data)
1. **`OwnerBookingSummaryDocument`:** add per-unit + headline **occupancy** (via `PerformanceMetrics::forOwnerUnits`,
   own units only), the per-source **commission** note (real `ota_cost`), the **guest name-only** field, the letterhead
   **billing_company / billing_vat_id / country**, the **BS-YYYY-MM** doc ref + the real statement cross-ref. Revenue
   totals + the Σ===gross reconciliation **unchanged**.
2. **`pdfs/owner-booking-summary.blade.php`:** 4-cell KPI band (occupancy + highlighted gross), By-unit occupancy column,
   By-channel commission notes, the Guest column, the subtitle line, the callout-styled footer note, the two-column
   footer. Remove the "guests not shown" line. Keep PDF-safe HTML4/CSS2.1 (dompdf).
3. **Consolidate docs:** update `docs/design/DIVERGENCE-REGISTER.md` rows (Bookings + PDF), append a dated
   `DECISIONS.md` entry + a new invariant, refresh `docs/PROJECT-STATE.md` + `CLAUDE.md` status.
4. **Tests:** extend the PDF test — occupancy is owner-unit-scoped (no room-type leak), commission ties to real
   `ota_cost`, guest is name-only, reconciliation Σ===gross still holds; the PDF renders (200/application-pdf).
5. **Verify:** `php artisan test` green · PHPStan L5 · Pint · browser page vs wireframe · regenerate the PDF vs wireframe.
   Per-gate commit; push after GATE 2.

---

## 5. Honesty ledger (what changes, and why it's still honest)

- **Formerly refused, now REAL (backing exists or was added):** the "Bookings over time" chart, the "Monthly goal" ring
  (owner-supplied target — not invented), PDF occupancy (headline + per-unit), PDF per-channel commission, the PDF guest
  **name-only** column. Each is a real aggregate or a real user-set value; none is fabricated.
- **Stays REFUSED (genuinely unmodelled):** the **OTA brand split** (Booking.com vs Airbnb) — rendered as the real
  `source` enum; street/city in the letterhead (only `country` is modelled).
- **Unchanged invariants:** guest **name-only, no contact** (#28); Σ(rows)===gross to the cent (#17); owner writes on
  **controller routes**, filters/sort ride GET so `SetOwnerScopeForPortal` re-runs (#20); PDF stays **CAP_EXPORTS-gated**;
  fail-closed owner isolation on an empty unit set.

**Bottom line:** the founder's "exact match, deferrals in scope" is achievable **without a single fabricated figure** —
the two items previously blocked as fabrication are now backed by real data (chart/commission/occupancy) or by a value
the owner sets themselves (the goal). The one thing we still refuse to invent is a channel brand the system doesn't model.
