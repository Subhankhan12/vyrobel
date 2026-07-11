# Owner Portal — Design Extraction & Data-Contract Spec

**Scope:** the 4 owner-portal screens that map to real backends/stubs — `Owner Dashboard`,
`Owner Statement`, `Owner Payouts`, `Owner My Units` (in `docs/design/raw/`). The other ~115 design
files are untouched.

**Method (actually rendered, not string-grepped):** each file was loaded in headless Chromium
(Playwright, 1440×900), the SPA JS was allowed to paint, then the **rendered DOM + computed styles**
were extracted (color/background/gradient/border/radius/shadow/font/blur/animation frequencies, CSS
`@keyframes`, dark-mode media queries, component inventory) and a **full-page screenshot** captured.
Raw extractions: `scratchpad/render/out/{dashboard,statement,payouts,units}.{json,png}` (scratchpad,
not committed). CSS tokens below are the **actual computed values** as built.

**Two global truths from the render:**
- **Single light theme — NO dark mode.** `prefers-color-scheme: dark` rules = 0; no dark CSS variables
  on any of the 4 pages. (The dark cards — "Next payout", "Best performer" — are a *surface treatment*,
  not a theme.) The Vyrobel Livewire layout must not assume theme-switching for these.
- **No CSS custom properties** — tokens are inlined literals, not `--vars`. The values below are the
  de-facto design tokens to formalize.

---

## Shared design system (consistent across all 4 screens)

### Typography
- **Display / headings / big numbers:** `Sora, sans-serif` (h1, hero figures, section titles).
- **Body / UI / labels:** `Inter, sans-serif` (dominant — ~85% of nodes). (`Times`/`Arial` appear only
  as SVG/`<title>` fallbacks — noise.)
- **Type scale (computed px):** 16 base · 15 (card/section titles, e.g. "Breakdown") · 14.5 · 14 ·
  13.5 (row labels — the most common label size) · 12.5 · 11.5 · 11 · 10.5 · 9.5 (micro-labels,
  uppercase tracked) · **30–32** (hero KPI numbers) · **58** (statement "Net payable" hero).
- **Weights:** **400 regular (most frequent by node count)** · 500 medium (heavily used for UI/labels) ·
  600 semibold (headings/emphasis). (Only a stray 700×2 — likely an SVG bold — otherwise no 700.)
- **Micro-labels** ("THIS MONTH", "NEXT PAYOUT", "OCCUPANCY") are ~9.5–11.5px, uppercase, letter-spaced,
  muted `rgb(138,128,121)`.

### Color tokens (exact computed rgb)
- **Ink / text:** primary `rgb(43,35,32)` (espresso near-black) · muted `rgb(138,128,121)` · faint
  `rgb(166,154,146)`.
- **Accent — terracotta/burnt-orange ramp:** `rgb(194,65,12)` (primary) · `rgb(180,64,15)` ·
  `rgb(217,102,46)` · `rgb(232,112,58)` · peach `rgb(240,165,122)`. Used for CTAs, active nav, values,
  progress fills, status dots.
- **Warm amber (pending/warning):** `rgb(178,106,8)` / `rgb(224,146,30)` (e.g. "Pending"/"On hold").
- **Dark surfaces (feature cards):** solid `rgb(42,33,28)` or gradient
  `linear-gradient(160deg, rgb(58,46,39) → rgb(37,28,23))` ("Next payout", "Best performer").
- **Neutrals:** white `rgb(255,255,255)`; glass tints below.

### Surfaces, gradients, depth
- **Frosted-glass cards (signature):** background `rgba(255,252,248,0.58)` or `rgba(255,255,255,0.55–0.60)`
  + **`backdrop-filter: blur(22px) saturate(1.15)`** (secondary `blur(10px)`); hairline border
  `rgba(255,255,255,0.75)` or accent `rgba(232,112,58,0.28)`.
- **Signature gradient (buttons, unit-card headers, avatar, logo):**
  `linear-gradient(140deg, rgb(232,112,58) → rgb(194,65,12))`; peach variant
  `linear-gradient(90deg, rgb(240,165,122) → rgb(194,65,12))` (or `140deg, rgb(240,165,122) → rgb(217,102,46)`).
- **Page background:** warm peach→cream wash + faint radial highlights
  (`radial-gradient(circle, rgba(255,255,255,0.35), transparent 68%)`, `linear-gradient(120deg,
  rgba(255,240,220,0.5), transparent)`).
- **Radii:** `30px` (pills / large cards) · `20px` (cards) · `12px` (cards, tiles) · `10px` (rows,
  inputs) · `9px` · `50%` (avatars, dots, calendar-day hovers).
- **Shadows (signature layered warm shadow):**
  `rgba(150,90,50,0.16) 0 14px 40px, inset rgba(255,255,255,0.65) 0 1px 0` — soft warm drop + glossy
  top-edge highlight. Accent glow on CTAs: `rgba(194,65,12,0.30) 0 4px 12px`. Dark cards:
  `rgba(60,30,10,0.28) 0 10px 30px`.

### Animation / JS behaviors (CSS `@keyframes` present + client-side)
- `vyFadeUp` — staggered entrance (opacity + translateY) on cards/sections (10–16 elements/page).
- `vyAcctUp` — **number count-up** on hero/KPI figures (the "€10,370.00" ticks up on load).
- `vyShimmer` / `sc-shine` — shimmer/sheen (skeletons, progress highlights).
- `vyDrift` — slow background gradient drift.
- Per-screen: `vyPulse` + `vySpin` (Payouts — the on-hold/verifying spinner & pulsing dot) ·
  `vyPing` (My Units — notification ping) · `vyMenuIn` (dropdown/menu open) ·
  `vySwapNext`/`vySwapPrev` (Statement — period-selector slide swap).
- **Transitions** on 60–116 elements/page (hover states on rows, pills, buttons, cards).
- **Client-side interactivity is presentational only** — these are self-contained static SPAs with
  inlined demo data; segmented toggles/filters/tabs flip an active class, no data fetching or forms.

### App shell (identical on all 4)
- **Left sidebar** (~232px): "V Vyrobel" logo (terracotta-gradient rounded square) + grouped nav:
  **OWNER PORTAL** (Overview, My units, Calendar) · **EARNINGS** (Statements, Payouts, Balance,
  Expenses, Tax center) · **MANAGE** (Reports, Insights, Rates, Reviews, Agreement, Messages) ·
  **ACCOUNT** (Settings, Security, Team & access, Help & support). Active item = frosted white pill.
- **Top bar:** page `<h1>` (Sora) · (page-specific CTA — e.g. **"Add unit"** on My Units) · notification
  bell with count badge (`2`) · user chip ("Amara Okafor" + circular gradient avatar "AO", dropdown caret).
- **Right rail** (Dashboard/Statement/Payouts): stacked cards, led by the dark "Next payout" card.
- Demo identity everywhere: **Amara Okafor · Seaside Villas · Villa Azure / Coralis / Dunes** (3 units),
  Sept 2025, €-denominated. All demo — no backing rows.

### Backend/stub reality for the portal (applies to all 4)
- Existing routes/stubs: `OwnerDashboard` → `/owner`; `OwnerStatementView` → `/owner/statements/{id}`.
  **`Owner Payouts` and `Owner My Units` have NO dedicated route/component yet** (payouts + units data
  is currently surfaced inline on the dashboard stub). They need new owner-scoped Livewire pages.
- Reads flow through `App\Services\OwnerPortalService`: `units()` (Unit + roomType, owner-scoped),
  `statements()` (OwnerStatement, owner-scoped), `payouts()` (Payout, owner-scoped),
  `occupancy(roomTypeId, start, end)` (→ `AvailabilityService::availabilityRange`, available per night).
- Owner isolation (`OwnerScope`, fail-closed) already guarantees every read is the owner's own rows.

---

## 1. Owner Dashboard  →  `OwnerDashboard` `/owner`  (stub exists ✅)

### Layout
Two-column: main feed (~1080px) + right rail. Regions top→bottom.

### Components (with real tokens)
- **"Needs your attention" card** — frosted glass (`blur(22px)`), radius `20px`, border
  `rgba(232,112,58,0.28)`, warm shadow. Warning icon (gradient square). 3 task chips (Rate change ·
  Maintenance expense €340 · Add tax details) each with an "Approve/Action" pill + "+3 more" ghost chip;
  "Open Action center" link (accent).
- **Quick-action pills row** — 4 frosted pills w/ icons: View calendar · Message guests · Download
  statement · Contact support (radius `30px`, hover transition).
- **Hero earnings block** — micro-label "THIS MONTH · SEPTEMBER 2025"; figure **€10,370.00** (Sora ~32px,
  `vyAcctUp` count-up); "+8% vs August" accent chip; "Next payout €10,370 · est. 3 Nov 2025".
- **"Next payout" card (dark)** — `rgb(42,33,28)`, radius `20px`; €10,370, "Est. 3 Nov 2025", "IBAN
  ···4821" accent pill (bank icon).
- **3 KPI tiles** — frosted, radius `12px`, corner ↗ link: TOTAL UNITS `3` · OCCUPANCY `78% +6%` **with
  a mini line sparkline** · EARNED YTD `€84,200`.
- **Recent statements list** — 4 rows (month + "Seaside Villas · 3 units", status dot Pending/Paid,
  €amount, chevron). "View all" link.
- **Earnings chart** — 6 vertical **bars** (Apr–Sep), gradient fill, last bar solid accent; dashed
  "€9k goal" line; **3M / 6M / 12M** segmented toggle (6M active).
- **Right rail: MY UNITS** — 3 rows each a label + **horizontal progress bar** (Villa Azure 82%,
  Coralis 74%, Dunes 78%) accent fill on track `rgba(120,70,40,0.1)`.
- **Right rail: UPCOMING STAYS** — "Next 30 days", **Earliest** sort + **Filter** dropdown; 6 guest rows
  (status-dot + name + €value + "Villa · N nights" + Confirmed/Pending + date). "Gross booking value"
  label + "View all bookings" link. "Download statements" pill.

### Interactive extras
3M/6M/12M chart range toggle · Earliest sort · Filter dropdown · corner-arrow drill links · hover
states on every row/pill · count-up on the hero figure.

### Data-contract cross-reference
| Design element | Status | Backing (field / service) |
|---|---|---|
| KPI "TOTAL UNITS 3" | ✅ real | `OwnerPortalService::units()->count()` |
| Recent statements (month, status, €net) | ✅ real | `statements()` → `OwnerStatement.period_*`, `status`, `net_payable_minor`, `sequence_no` |
| Hero "THIS MONTH €10,370" + "Next payout €10,370" | ✅ real | latest finalized statement `net_payable_minor` (+ its `Payout.amount_minor`) |
| "EARNED YTD" / "Paid this year" totals | ✅ real (derive) | sum of paid `Payout.amount_minor` / `statements_net_minor` (stub already computes `totals`) |
| MY UNITS occupancy bars (82/74/78%) | 🟡 partial | occupancy **derived** from `occupancy()`=`availabilityRange` (available/night); **% is computed, not a stored field** |
| Upcoming stays (guest name, €, nights, status, date) | 🟡 partial | reservations exist (owner units), but **not exposed by the portal stub**; guest name is encrypted PII — needs an owner-scoped booking read |
| Earnings bar chart (Apr–Sep monthly) | 🟡 partial | derivable from `OwnerStatement` per period; **no monthly-series endpoint yet** |
| "+8% vs August", "+6%" occupancy deltas, sparkline | 🟡 partial | derivable (period-over-period); **not stored** |
| "Next payout … est. 3 Nov 2025" date | 🟡 partial | payout exists; **scheduled/estimated payout date is not a field** (only `paid_at`) |
| "IBAN ···4821" | ⬜ decorative | **no payout-method model** (Stripe Connect deferred; `connect_account_id` empty) |
| "Needs your attention" / Action center (rate-change approve, maintenance approve, add-tax, +3) | ⬜ decorative | **no approvals/tasks/notifications backend**; no maintenance module; no tax-onboarding |
| Quick actions: Message guests · Contact support | ⬜ decorative | **no messaging/support backend** (Unified Inbox is a future module) |
| Download statement(s) | ⬜ decorative | **no PDF generation** yet |

---

## 2. Owner Statement  →  `OwnerStatementView` `/owner/statements/{id}`  (stub exists ✅)

### Layout
Main column (statement) + right rail (payout / occupancy / documents); full-width history + by-unit below.

### Components (with real tokens)
- **Period selector** — `‹` `September 2025 ⌄` `›` pill trio (calendar icon); `vySwapNext/Prev` slide on
  change.
- **Statement hero card** — frosted, radius `20px`; "SEPTEMBER 2025 · Seaside Villas · 3 units";
  **"Payout pending"** amber badge (clock icon); "Net payable" label; figure **€10,370.00** (~58px Sora,
  count-up).
- **Breakdown card** — line items (label + sublabel, right-aligned signed amount):
  Gross room revenue ("64 nights · 3 units") **+€12,881.99** · Management commission ("18% of gross
  revenue") **−€2,318.76** · Payment & ops fees ("Card processing & platform") **−€193.23** · **Net
  payable €10,370.00** (accent bold). Hairline dividers `rgba(120,70,40,0.1)`.
- **Right rail:** dark "Next payout" card (as dashboard) · **OCCUPANCY** card `71% +8%` with a 7-bar
  mini bar-chart (Mar–Sep) · "Download statement" pill · **MORE DOCUMENTS** card (Remittance advice
  "RA-2025-09 · PDF", Booking summary "18 bookings · PDF", download icons).
- **Statement history table** — filter pills **All / 2025 / 2024 / 2023**; **Export CSV** / **Export PDF**
  (accent) buttons; "28 statements · €221,500 net total"; columns **MONTH | GROSS | NET PAYABLE |
  STATUS | ⤓**; rows = month + "N nights · M bookings", gross, net (accent bold), status dot
  (Pending amber / Paid), per-row download; active row highlighted.
- **By unit card** — per unit: name + "N nights · X% occupancy", right **€net** + "€gross · Y% of net",
  full-width **progress bar** (accent). "Total net earnings €10,370".

### Interactive extras
Period `‹ ›`/dropdown · year filter pills · Export CSV/PDF · per-row + header download · active-row
highlight · hover states.

### Data-contract cross-reference
| Design element | Status | Backing (field / service) |
|---|---|---|
| Hero "Net payable €10,370.00" | ✅ real | `statement.net_payable_minor` (`OwnerStatementView`) |
| Period "September 2025" + status "Payout pending" | ✅ real | `period_start/period_end`, `status` (draft/finalized/paid) |
| Breakdown "Gross room revenue +€12,881.99" | ✅ real | statement line `line_type=gross` (`amount_minor`, `description`) |
| Breakdown "Management commission … −€2,318.76" (amount) | ✅ real | line `line_type=commission` (`amount_minor`) |
| ↳ "18% of gross revenue" sublabel | 🟡 partial | derivable from `ManagementAgreement.commission_pct` (or `owner_ledger.meta`); **NOT stored on the statement line** (`owner_statement_lines` has no `meta` column) and **not projected by `OwnerStatementView`** |
| Breakdown **"Payment & ops fees −€193.23 / Card processing & platform"** | ⬜ **decorative — no field** | statement line types are gross/other_revenue/commission/management_fee/expense/**ota_cost**/prior_period_adjustment/net. **No payment-processing-fee line exists** (closest `ota_cost` = OTA channel commission, different concept). **Wire = add a new attribution component or map to ota_cost.** |
| Statement history (MONTH/GROSS/NET/STATUS, 28 rows) | ✅ real | `OwnerPortalService::statements()` → per-statement `gross_minor`, `net_payable_minor`, `status`, period |
| "€221,500 net total" | ✅ real (derive) | Σ `net_payable_minor` across owner's statements |
| "By unit" (per-unit gross/net/% of net) | 🟡 partial | `owner_ledger` carries `unit_id` on room_revenue/commission lines → derivable; **statement lines are bucket-aggregated, not per-unit → needs a per-unit projection query** |
| "By unit" occupancy % | 🟡 partial | derived from `availabilityRange`; not stored |
| Occupancy mini bar-chart (Mar–Sep) | 🟡 partial | derivable per period; no series endpoint |
| "Booking summary · 18 bookings" | 🟡 partial | reservation count derivable; not on the stub |
| Cross-currency (would show fx) | ✅ real (unused here) | `statement.fx_rate`, `fx_source`, `source_currency` (EUR==EUR here → hidden) |
| "Remittance advice RA-2025-09 · PDF" · Export CSV/PDF · per-row download | ⬜ decorative | **no PDF/CSV generation**; `sequence_no` exists but no "RA-…" doc numbering |
| "Next payout … IBAN ···4821" | ⬜ decorative | payout amount real; **date + IBAN not backed** (see Dashboard) |

---

## 3. Owner Payouts  →  Payout backend + `OwnerPortalService::payouts()` (data real; **no dedicated stub/route yet** 🟡)

### Layout
Main column (banner + KPIs + history) + right rail (next-payout breakdown, schedule, account, preferences).

### Components (with real tokens)
- **On-hold alert banner** — frosted, accent-tinted; spinner icon (`vySpin`+`vyPulse`); "September payout
  is on hold" + "Verification pending" pill; body copy; **Complete verification** (accent CTA) · "Held
  since 3 Nov · est. release 7 Nov 2025" · "View held funds".
- **3 KPI tiles** — PAID THIS YEAR `€74,830` · NEXT PAYOUT `€10,370` ("on hold · under review") ·
  PENDING `€10,370` ("On hold" badge).
- **Payout history** — **search input** ("Search by month or status", radius `10px`) · **Export ⌄** ·
  **2025 ⌄** · **Status ⌄**; "€74,830 paid across 8 payouts · €10,370 scheduled"; rows = month + property,
  **"Bank transfer"** rail pill (bank icon), date, **status pill** (On hold amber / Paid), €amount,
  "Statement" + "Receipt ›" links.
- **Right rail — dark "Next payout" card** — €10,370, "Verification pending · on hold", IBAN pill;
  breakdown (Gross revenue €12,881.99 / Management fee 18% −€2,318.76 / Payment & ops fees −€193.23 /
  Net payout €10,370.00); **horizontal lifecycle stepper: Finalized ● → Scheduled ● → On hold ●(active)
  → Paid ○**.
- **UPCOMING SCHEDULE card** — "3rd business day"; timeline dots: held since 3 Nov €10,370 (On hold),
  3 Dec ~€9,850 (Projected), 3 Jan ~€9,850 (Projected); note "Later months use your trailing 3-month
  average."
- **PAYOUT ACCOUNT card** — "Revolut Business · Default · IBAN ···· 4821" + "Manage".
- **PAYOUT PREFERENCES card** — "Active"; **Frequency** (Monthly•/Twice monthly/Weekly) · **Payout day**
  (1st/3rd•/15th/Last) · **Minimum payout** (None•/€500/€1k/€2.5k) · **Payout currency** (EUR•/GBP/USD) —
  all segmented toggles; **Pause payouts** switch (`role=switch`, off) "Hold funds in your Vyrobel
  balance"; "All changes saved" · "Reset to default".

### Interactive extras
Search · Export/year/Status dropdowns · 4 segmented preference groups · pause toggle · lifecycle stepper ·
"Complete verification" CTA · per-row Statement/Receipt links.

### Data-contract cross-reference
| Design element | Status | Backing (field / service) |
|---|---|---|
| Payout history rows (€amount, date, rail, status, statement link) | ✅ real | `Payout.amount_minor`, `paid_at`, `rail` (**manual = "Bank transfer"**), `status`, `owner_statement_id` |
| "PAID THIS YEAR €74,830" | ✅ real (derive) | Σ paid `Payout.amount_minor` |
| NEXT PAYOUT / PENDING €10,370 | ✅ real | finalized statement's `net_payable_minor` / pending `Payout` |
| Next-payout breakdown (Gross / Management fee / Net) | ✅ real | source statement lines (as Statement screen); **"Payment & ops fees" line = no field** (same gap) |
| Lifecycle stepper **Finalized → Scheduled → On hold → Paid** | 🟡 partial | `Payout.status` enum = **pending/paid/failed/reversed** + statement `status`. **"Scheduled"/"On hold" are NOT enum values** — either add states or map (finalized-not-paid→"Scheduled"; there is no on-hold/verification concept) |
| On-hold banner · "Verification pending" · "Complete verification" · held/est-release dates | ⬜ decorative | **no KYC/verification/on-hold model** (Stripe Connect onboarding deferred); no `held_at`/`released_at` |
| "Receipt ›" (per payout) | ⬜ decorative | **no receipt PDF**; `external_ref` exists but empty on manual rail |
| Upcoming schedule projections (~€9,850, trailing 3-mo avg) | ⬜ decorative | **no projection engine** |
| PAYOUT ACCOUNT (Revolut Business, IBAN ···4821, Manage) | ⬜ decorative | **no payout-method model** (Connect deferred) |
| PAYOUT PREFERENCES — Frequency | 🟡 partial | `ManagementAgreement.payout_schedule` = **monthly/quarterly** only (design offers Monthly/Twice-monthly/Weekly) |
| PAYOUT PREFERENCES — Payout currency | ✅ real | `Owner.payout_currency` / `ManagementAgreement.payout_currency` |
| PAYOUT PREFERENCES — Payout day / Minimum payout / Pause payouts | ⬜ decorative | **no fields** (no payout-day, no minimum-threshold, no pause/hold-in-balance) |
| Export / search / Status filter | ⬜ decorative | client-side only; no export/search endpoint |

---

## 4. Owner My Units  →  `OwnerPortalService::units()` + `occupancy()` (data real; **no dedicated stub/route yet** 🟡)

### Layout
KPIs → "Needs your attention" (collapsed) → "Your units" card grid → "Add a unit" → occupancy calendar + best-performer.

### Components (with real tokens)
- **Top-bar "Add unit" CTA** — accent gradient button, top-right (distinct from the "Add a unit" dashed
  grid card below).
- **3 KPI tiles** — UNITS `3` · AVG OCCUPANCY `78% +6%` · EARNED THIS MONTH `€10,370`.
- **"Needs your attention" banner** — collapsed (bell icon `vyPing`, "6 items", **Expand**). Expanded
  content (from DOM): tabs Needs attention(6)/Snoozed(0); All units / All statuses filters; bulk Approve
  all / Snooze all / Mark all read; items = Seasonal rate change (Villa Azure +12% Oct–Dec, Decline/
  Approve), Maintenance expense €340 (Villa Dunes, Decline/Approve), Add tax details (blocks €10,370),
  Confirm payout bank account (Villa Coralis €3,050 paused, Verify account), Management agreement renews
  in 14 days (Villa Dunes, Review & e-sign), Owner stay confirmed (Villa Coralis Oct 12–15, View).
- **Units toolbar** — "Sort: Revenue ⌄" · filter pills All units / Villa Azure / Coralis / Dunes.
- **Unit cards (3-col grid)** — radius `20px`; **terracotta-gradient header** (`linear-gradient(140deg,
  #E8703A→#C2410C)`) w/ home icon; name + "2-bed apartment · Seaside Villas" + **Active** badge;
  "OCCUPANCY 82%" + **progress bar** "This month"; Nights sold `24` / Revenue `€4,120`; Nightly rate
  `€172`; **Edit pricing** / **Message guest** buttons; "View unit ›" link.
- **"Add a unit" card** — dashed frosted card, `+` gradient circle, "List a new property".
- **Occupancy calendar** — header "July 2026" `‹ ›` · **Block dates** · **Export** · "All units ⌄";
  MON–SUN grid, days 1–31 as rounded tiles **heat-mapped by units-booked/day** (light peach→deep
  terracotta); legend "BOOKED 0 · 1 · 2 · 3 of 3 units"; summary "79 nights · 85% occupancy · €12,805".
- **"Best performer" card (dark)** — Villa Azure 82% occupancy, "24 nights · €4,120 this month".

### Interactive extras
Sort dropdown · unit filter pills · attention tabs + bulk actions + per-item Approve/Decline/Verify/
e-sign/View · calendar month nav + Block dates + Export + unit filter · Add-unit · Edit pricing /
Message guest / View unit · hover states · `vyPing` on the attention bell · `vyMenuIn` on dropdowns.

### Data-contract cross-reference
| Design element | Status | Backing (field / service) |
|---|---|---|
| KPI "UNITS 3" | ✅ real | `OwnerPortalService::units()->count()` |
| Unit card name / type / status | ✅ real | `Unit.name`+`code`, `RoomType.name`/`kind` ("Studio/2-bed…"), `Unit.is_active` (Active) |
| Occupancy % + **calendar heatmap** (units booked/day) | ✅ real | `occupancy()` = `AvailabilityService::availabilityRange` (available/night → booked = total−available). Heatmap is a direct render of this. **% figure is derived, not stored.** |
| Nights sold / occupancy summary ("79 nights · 85%") | 🟡 partial | derivable from availability; not a stored field |
| Revenue per unit (€4,120) / EARNED THIS MONTH | 🟡 partial | `owner_ledger` room_revenue lines carry `unit_id` → derivable; **no per-unit revenue projection built** |
| Nightly rate (€172) | 🟡 partial | `rate_calendar` / `RateAndRestrictionService` → derivable; not on the portal stub |
| Block dates | 🟡 partial | `inventory_blocks` + `AssignmentService::block` exist; **owner-initiated blocking not wired to the portal** |
| "Best performer" | 🟡 partial | derivable (max occupancy/revenue); not stored |
| AVG OCCUPANCY / "+6%" deltas | 🟡 partial | derived; not stored |
| "Needs your attention" (rate-change approve, maintenance approve, add-tax, verify bank, agreement e-sign, owner stay) | ⬜ decorative | **no approvals/tasks/notifications backend**; no maintenance module; no e-sign; no tax-onboarding; owner-stay isn't a modelled concept |
| "Add a unit" (owner self-serve) | ⬜ decorative | units exist, but **owner self-service unit creation is not wired** |
| Edit pricing / Message guest | ⬜ decorative | owner rate-editing not exposed; **no messaging backend** |
| Export | ⬜ decorative | client-side; no export endpoint |

---

## Consolidated "wire vs stub" summary

**Wire to real data now (backed today):** unit list + count; statement list + per-statement gross/net/
status/period + net-payable hero + the **Gross → commission → net** breakdown; statement-history table
and net totals; payout history (amount/date/rail/status/statement link) + paid-year total + next/pending;
payout currency; occupancy **calendar heatmap** and occupancy figures (via `availabilityRange`). These
map cleanly onto `OwnerPortalService` + `OwnerStatementView` + the owner-scoped models.

**Derivable but needs a new read/aggregation (🟡):** per-unit revenue & "by-unit" statement split
(from `owner_ledger.unit_id`); monthly earnings series / sparklines / period-over-period deltas;
per-unit occupancy %; upcoming-stays list (owner-scoped bookings, guest PII); nightly rate; owner-
initiated block dates. **Also: `Owner Payouts` and `Owner My Units` need their own routes/components** —
today only `/owner` and `/owner/statements/{id}` exist.

**Decorative / no backend field — stub or defer (⬜):** the **"Payment & ops fees / card-processing"
statement line** (no such attribution component — decide: add one or fold into `ota_cost`); everything
KYC/verification/on-hold and all **payout-method/IBAN** UI (Stripe Connect deferred); **payout
preferences** beyond currency (frequency-weekly/day/minimum/pause have no fields; schedule is
monthly/quarterly only); payout **lifecycle states "Scheduled"/"On hold"** (enum is pending/paid/failed/
reversed); the entire **"Needs your attention"/Action-center** approvals/tasks surface; **messaging**
(Message guests/guest), **maintenance** approvals, **e-sign** agreement renewal, **owner self-serve
add-unit**, **PDF/CSV** exports (statements, remittance, receipts), and **projection** (trailing-average)
figures. None of these have a backing field in the certified backend.
