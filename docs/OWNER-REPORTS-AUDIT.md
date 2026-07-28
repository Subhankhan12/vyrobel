# Owner Reports — wireframe-EXACT-match audit (deeper pass)

**Date:** 2026-07-28 · **Author:** build session · **Status:** ✅ **BUILT — invariant #63** (GATE 1 `f05a9b8` analytics exact-match + REAL net-by-channel + real year/date-range/export · GATE 2 VAT honest-deferred design + docs). The page now matches the full decoded wireframe on REAL owner-scoped analytics; the commission rate is the REAL 18% agreement rate; net-by-channel is real & reconciles (Σ channels == period net); the prior-year comparison is honest-omitted (no data); the Tax & VAT block is honest-deferred in the wireframe's card chrome (no fabricated figure / filing toggle / VAT-return CSV, #39); exports CAP_EXPORTS-gated. Suite green; PHPStan L5 (0); Pint clean; browser-verified (year dropdown, custom range, export menu, 3 real exports 200, honest VAT). *(The PHASE-1 analysis below is preserved as the audit of record.)*
**Page:** `/owner/reports` · **Component:** `App\Livewire\Owner\OwnerReports` + `resources/views/livewire/owner/reports.blade.php`
**Wireframe:** `docs/design/raw/Owner Reports.html` (decoded via the dc-runtime bundle → `/tmp/owner_reports_decoded.txt`, 817 lines of `<x-dc>` body + a `Component` JS block).

---

## 0. The premise-check finding (read this first)

**The Reports page was ALREADY built to a wireframe-fidelity pass in invariant #61** (`949ea93`, `docs/OWNER-REPORTS-INSIGHTS-AUDIT.md`). That pass got the **data honesty exactly right** and is still correct:

- ✅ **Commission rate = the REAL 18%** (via the shared `OwnerCommissionRate::percent()` → `ManagementAgreement.commission_pct`), never the wireframe's hardcoded **15%**. *Verified live:* gross-to-net shows "Commission · 18%" and reconciles.
- ✅ **Tax & VAT summary honest-deferred** — no fabricated VAT figure, no "Mark filed".
- ✅ **Gross-to-net reconciles to the cent** (#17): live shows €5,980.00 − €1,076.40 − €0.00 − €250.00 = **€4,653.60**.
- ✅ Real KPIs / net-by-month / per-unit (ADR/RevPAR/nights/occ/net/share) / real Documents PDFs / self-scoped Notifications feed.

**So this is NOT a from-scratch build and NOT a commission/VAT re-decision.** It is a **deeper EXACT-match pass** against the *fuller* `Owner Reports.html`: the #61 build matched the honest *content* but chose a **simpler layout** than the wireframe. The real delta is almost entirely **presentation** plus **two genuinely-computable data upgrades** (net-by-channel, real year selector) and **one honest-omit** (prior-year comparison — no data). No new fabrication risk beyond the VAT block, which stays deferred.

---

## 1. Backend investigation (verified against the repo + a live DB query)

| Capability | Verdict | Evidence |
|---|---|---|
| **Commission rate** | ✅ REAL 18% | `OwnerCommissionRate::percent(OwnerContext)` reads `ManagementAgreement.commission_pct`, owner-scoped; demo = 18. |
| **KPIs / occupancy / ADR / RevPAR / nights** | ✅ REAL | `PerformanceMetrics::forOwnerUnits()` → `MetricSet` (`occupancyPct/adrMinor/revparMinor/roomNightsSold`). |
| **Net-by-month** | ✅ REAL | owner `OwnerLedgerEntry` (`owner_ledger`) summed per month via `signedMinor()`; reconciles to the total. |
| **Per-unit net + share** | ✅ REAL | ledger grouped by `unit_id` via `OwnerLedgerAttribution`; shares reconcile to 100% of real net. |
| **Gross-to-net** | ✅ REAL, reconciles (#17) | four buckets over `entry_type` (`room_revenue/addon` · `commission` · `ota_cost/management_fee` · `cleaning/expense/adjustment`) sum to net exactly. Demo entry types present: room_revenue×9, commission×9, ota_cost×9, expense×2. |
| **Net-by-CHANNEL (amounts)** | 🟡 **REAL-BUILDABLE** (not built today) | `MetricSet.sourceNights` gives **nights** per source, NOT net. But the ledger **joins to `reservations.source`** (27/29 entries carry `reservation_id`) → a live query returns net by source (direct €6,419.20, walk-in €637.20). **Genuinely computable & reconciling** — a *stale-refusal re-verification win*: the current "room-nights by channel" can become the wireframe's "**net by channel**". The 2 reservation-less rows (expenses) fall into an honest "Other/portfolio" bucket. |
| **Prior-year series + YoY deltas** | 🔴 **HONEST-OMIT** (no data) | Owner ledger spans **2026-04-06 … 2026-10-18, 100% year 2026**; zero 2025 rows. So the chart's dashed prior-year bars + the KPI "+X%" deltas + the hero YoY badge have **no real basis → omit** (the wireframe's `hasPriorAny`/`m.hasPrior` sc-ifs are built to hide exactly this). Never fabricate a comparison (#39). Matches the #61 Insights "no LY data → honest-omit" call. |
| **Year selector (2025/2024/2023)** | 🟡 REAL but different | The wireframe hardcodes 3 fabricated years with statement counts. Real `owner_statements` span **2026 only** (6 statements). → drive the selector from **real statement years** (2026) + the honest footer "Earlier years live in Documents · archived PDFs"; do NOT render fabricated 2024/2023 rows. |
| **Custom date range (From/To)** | 🟡 **REAL-BUILDABLE** | `resolveRange()` already reads a `range` query param; extend to real `from`/`to` clipping the same owner-scoped ledger. Real, owner-scoped, reconciles. |
| **Exports** | 🟡 PARTIAL-REAL | REAL routes: `owner.statements.history.csv`, `owner.statements.history.pdf`, `owner.tax.annual` (`/tax/{year}/summary.pdf`), per-statement `owner.statements.pdf`, plus `owner.{payouts,balance,expenses,units,bookings}.export` CSVs. **No** route for "PDF pack", "Excel workbook", "All statements ZIP". → wire the real ones; **honest-label/defer** the rest (Excel = the CSV honest-labelled; "All statements" → link Documents; "PDF pack" → honest or map to the annual PDF). All `@ownercan(CAP_EXPORTS)`-gated (#42). |
| **"Email to me"** | 🔴 HONEST-DEFER | No report Mailable exists → an honest "Soon", never a fake send. |
| **Notifications popover** | ✅ REUSE (shell bell) | The shared shell top-bar already renders the real self-scoped bell + panel (`<x-notifications.bell>` / `NotificationFeed`, invariant #48). The wireframe's notification popover = **that bell** — reuse it; do not build a page-local one, and **drop the redundant right-rail Notifications card** (the wireframe has none in the body). |
| **VAT computation** | 🔴 **NOT MODELLED → HONEST-DEFER** (see §2) | grep confirms **zero** output/input/net-VAT / vat_rate / filing store anywhere. The ONLY VAT data is a *stored encrypted `vat_number` string* on `Owner` (+ `billing_vat_id` on `Organization`) — an identifier for the statement letterhead, NOT a computation. |
| **Filing status / "Mark filed"** | 🔴 HONEST-OMIT | No filing-status table/state anywhere. → never a fake toggle; omit the Mark-filed/Undo controls. |

---

## 2. ⚠️ THE VAT FINDING (the one dangerous section — confirmed)

The wireframe's **Tax & VAT summary** is a **fully fabricated VAT computation**, hardcoded to a Portuguese setup:

- **Output VAT · 6%** `{{ vatOutputFig }}` "on {{ vatAccomBase }} accommodation"
- **Deductible input VAT · 23%** `{{ vatInputFig }}` "on {{ vatServiceBase }} agent services"
- **Net VAT due** `{{ vatNetFig }}` **"payable to Autoridade Tributária"**
- **Filing status** rows (VAT + income tax) with **"Mark filed" / "Undo"** buttons
- **Net VAT by quarter** (Q1–Q4 chips) + **"VAT return (CSV)"** / **"Download tax summary"**
- Letterhead: *"Seaside Villas Lda · VAT PT 512 034 776"* — all literals in the JS `Component`, editable via `accomVatRate`/`serviceVatRate` design props.

**Vyrobel does not model VAT at all** (verified). A number labelled **"Net VAT due · payable to Autoridade Tributária"** is a figure an owner could file a real tax return on. Fabricating it — even under the wireframe's "Provided for information only — not tax advice" caption — is **unacceptable (#39)** and materially dangerous.

**RESOLUTION (already the founder-confirmed #61 decision; re-affirmed by this audit): HONEST-DEFER the whole VAT block.** Render it in the wireframe's design language (the same card, header, chip, icon) but as an **honest deferred state** — no Output/Input/Net figure, no "payable to [authority]", no "Mark filed" toggle, no VAT-return CSV with invented numbers. Point at the **real** annual tax summary PDF (real earnings figures for the accountant) + the Tax Center. GATE 2 will match the block's *design* more closely than today's plain-text card **without** materialising any fabricated figure or control.

**This needs no new founder decision** (it re-confirms #61) — but it is called out explicitly because the wireframe *looks* like a finished feature and the section list flagged it.

---

## 3. Section-by-section: wireframe vs current build (the exact-match delta)

Layout order in the wireframe: **top control row** → **3-KPI metric row** → **2-col (`1fr 232px`)** → **full-width Tax & VAT**.

| # | Wireframe section | Current build | Delta to close |
|---|---|---|---|
| 1 | **Reporting-year dropdown** (2025/2024/2023 + counts + "Earlier years in Documents") | 3 range **pills** (2026 / YTD / Last 6 months) | Rebuild as the dropdown; drive from **real statement years** (2026) + honest footer; keep YTD/6m as real range options inside it. |
| 2 | **Custom date-range popover** (From/To lists + preview + Apply/Clear) | none | Build REAL from/to clip over owner ledger. |
| 3 | **Export dropdown** (PDF report · PDF pack · CSV monthly&per-unit · Excel workbook · All statements · Email to me) | Export dropdown, 3 items (CSV · annual PDF · Email=Soon) | Match the richer menu design; wire real routes; honest-label Excel/pack/all-statements; Email = Soon. |
| 4 | **Notifications bell popover** (4 categorised rows + Mark-all + Settings) | right-rail Notifications **card** + shell bell | REUSE the **shell bell** (real); **remove** the right-rail card (not in the wireframe body). |
| 5 | **3 KPIs with deltas** (Net earned +YoY · Avg occupancy +Δ · Nights across N units) | 3 KPIs, **no deltas** | Match design; **honest-omit the +Δ** (no prior-year data). |
| 6 | **Net earnings by month** — this-year solid + **prior-year dashed** + peak star bubble + legend | single-series bars, no bubble/legend | Match the bar design + peak bubble + value labels; **omit the prior-year series + legend** (`hasPrior*` false — no data). |
| 7 | **Performance by unit** — ADR/RevPAR header figs + grid rows (nights/occ/net/**share bar**) + **All-units total** | HTML table (adds ADR/RevPAR columns) | Rebuild to the wireframe's grid + share bars + ADR/RevPAR header + "All units" total row. |
| 8 | **Booking sources — NET by channel** (stacked gradient bar + legend name/pct/**amount** + per-unit net bars) | "Room-nights by channel" (nights + %) | Rebuild as **REAL net-by-channel** (ledger×`reservations.source`), stacked bar + amounts. *(the REAL-BUILDABLE upgrade)* |
| 9 | **Gross to net** (Gross − Commission·**15%** − Fees&OTA − Guest refunds == Net to you) | ✅ present, **Commission·18%**, reconciles | Keep as-is (already correct); align labels/order to the wireframe (Guest-refunds line conditional — honest-omit when none). **Never adopt the 15%.** |
| 10 | **Dark hero** ("{period} so far" + net + YoY **badge**) | dark hero (net + "View statements") | Match design; **omit the YoY badge** (no prior-year) or replace with an honest sublabel. |
| 11 | **Next payout card** (status chip + amount + date + method) | Payouts card (amount + status + paid-this-year) | Match the wireframe card design; real payout data (masked, #28). |
| 12 | **Documents** (3 real PDF rows) | ✅ present, 3 real PDFs | Minor design alignment. |
| 13 | **Tax & VAT summary** (full-width, structured) | right-rail plain-text deferred card | Move to **full-width bottom**; match the card/header/chip **design**; **HONEST-DEFER** the contents (no figures/toggles) per §2. |

**Guard-relevant notes:** everything owner-scoped (#28), integer money + reconcile (#17), exports CAP_EXPORTS-gated (#42), no fabrication (#39). No new writes are introduced (the year/date-range/export are all reads; there is NO "Mark filed" write — honest-omit). So invariant #20 (controller-POST-for-writes) has **nothing to gate here** — the page stays read-only, matching #61.

---

## 4. The four judgment points — resolutions

1. **Commission rate** — ✅ **already fixed to real 18%** (`OwnerCommissionRate`). Not a decision; keep. Never 15%.
2. **Tax & VAT summary** — 🔴 **HONEST-DEFER** the whole block (VAT not modelled; a "Net VAT due" figure is dangerous). Re-confirms #61. Design matched, contents honest, no figure/toggle/CSV-with-numbers.
3. **Filing status / "Mark filed"** — 🔴 **HONEST-OMIT** (no filing store; never a fake toggle).
4. **"Email to me"** — 🔴 **HONEST-DEFER** ("Soon"; no report Mailable).

Plus two audit-surfaced calls:
5. **Prior-year comparison (chart series + KPI deltas + hero YoY badge)** — 🔴 **HONEST-OMIT** (no 2025 data). Never fabricate.
6. **Net-by-channel** — 🟢 **BUILD REAL** (genuinely computable from ledger×reservation.source; reconciles). Stale-refusal re-verification win.

---

## 5. Build plan (gated) — PHASE 2

**⛔ GATE 1 — Reports analytics: exact design + real analytics + net-by-channel + real year/date-range/export.**
Rebuild `reports.blade.php` (+ extend `OwnerReports`) to the wireframe's exact layout & tokens (shared `<x-owner.shell>` + `--vy-*`; page-scoped `.vy-rep-*`): the reporting-year dropdown (real statement years), the custom date-range popover (real from/to clip), the richer Export dropdown (real routes + honest labels), the 3 KPIs (design match, Δ honest-omitted), the net-by-month chart (design + peak bubble; prior-year omitted), per-unit performance (grid + share bars + ADR/RevPAR header + All-units total), **net-by-channel (REAL, new)**, gross-to-net (keep 18%, reconciles), the dark hero, next-payout, and Documents. Remove the redundant right-rail Notifications card (reuse the shell bell). Guards: #28/#17/#42/#39. Existing suite green + new assertions (net-by-channel reconciles; 18% not 15%; no prior-year fabrication). Browser-verify + a real export. Per-gate commit.

**⛔ GATE 2 — Tax & VAT summary: honest-deferred in the exact design + filing honest-omit + docs.**
Move the VAT block to the full-width bottom slot and match its design (card/header/country chip/icon) **as an honest deferred state**: no Output/Input/Net VAT figure, no "payable to Autoridade Tributária", no "Mark filed"/"Undo", no VAT-return-CSV-with-numbers; link the real annual tax summary PDF + Tax Center. Consolidate docs (this file → resolved; DECISIONS dated entry; DIVERGENCE-REGISTER Reports row → exact-design + VAT honestly deferred; PROJECT-STATE + invariant bump). Full verify (suite + PHPStan L5 + Pint + migrate:fresh --seed + browser + a real export). Per-gate commit + push.

### Guards (both gates)
🔒 #28 owner-scoped (guest data name/counts-only; masked IBAN CAP_PAYOUT-only) · #17 integer money + reconcile + **real commission rate** · #42 exports CAP_EXPORTS-gated · #39 **nothing fabricated** (no fake VAT figure/filing/return, no fabricated prior-year comparison, no fake email send). Reuse the Batch-B analytics + real statements/PDFs + the shell bell (anti-drift). **DISTURB NOTHING existing** (the green suite is the proof).
