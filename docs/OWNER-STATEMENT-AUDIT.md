# Owner Statement — page + Statement PDF: wireframe-fidelity audit

**Date:** 2026-07-28 · **Author:** build session · **Status:** PHASE 1 (read-only) complete — awaiting GATE 1 approval.

Founder brief: make the LIVE **owner Statement page** (`/owner/statements/{statement}`) **and** the **owner Statement PDF**
(`owner.statements.pdf` → `OwnerStatementPdfController` → `OwnerStatementDocument` → `pdfs/owner-statement.blade.php`)
match their two decoded wireframes EXACTLY — design **and** behaviour — on real owner-scoped financial data that
**RECONCILES to the cent**. Two DIFFERENT deliverables: the page reuses the shared owner chrome; the PDF is a print
document (white letterhead). **"Prior deferrals are IN SCOPE."** Honesty is non-negotiable (#39 no fabrication · #28 guest
name-/counts-only · #17 reconcile to the cent · #20 owner writes on controller routes · exports CAP_EXPORTS-gated).

Same bar as the prior owner exact-match gates: **Overview** (#54, `OWNER-OVERVIEW-AUDIT.md`), **My Units / Add Unit**
(#55, `OWNER-UNITS-AUDIT.md`), **Bookings page + Booking-Summary PDF** (#56, `OWNER-BOOKINGS-AUDIT.md`). The decisive
pattern proven in those gates and applied again here: **a prior "refused as fabrication" becomes REAL the moment a real
backing exists** — and two of this page's refusals (occupancy, the payout IBAN) went stale when the Overview #54 work
shipped `OwnerOccupancy` + `Iban::masked` on a sibling owner surface.

---

## 0. Method
- **Decoded both wireframes** from their `dc-runtime` bundles (`docs/design/raw/Owner Statement.html`,
  `docs/design/raw/Owner Statement PDF.html`) — `JSON.parse(<script type="__bundler/template">)` → the `<x-dc>` markup +
  inline-style tokens + `{{ }}` placeholders — and rendered both over a throwaway `:8899` static server for the visual.
- **Audited the live page** logged in as the demo owner (`/owner/statements/{id}`) → screenshot + read
  `app/Livewire/Owner/OwnerStatementView.php` + `resources/views/livewire/owner/statement.blade.php`.
- **Audited the live PDF** — generated it (`owner.statements.pdf` downloaded `vyrobel-statement-1-2026-09.pdf`, 200) and
  read its source (`app/Support/Pdf/OwnerStatementDocument.php` + `resources/views/pdfs/owner-statement.blade.php`).
  *(poppler is not installed, so the generated PDF was audited from source rather than rendered to an image; the new PDF
  will be rendered at GATE-2 verification.)*
- **Verified the reconciliation engine** — `app/Services/OwnerStatementService.php` (the projection), `OwnerStatement` +
  `OwnerStatementLine` models, `App\Support\Owner\OwnerLedgerAttribution` (the by-unit net), `PerformanceMetrics::forOwnerUnits`
  (occupancy/nights/ADR), and the real demo statement in the DB.
- **Cross-referenced** `docs/design/DIVERGENCE-REGISTER.md` row 132 (Owner Statement) and re-verified its refusals.

**Global chrome is out of scope** (locked decision D2, DIVERGENCE row 126): the wireframe's reduced sidebar is matched in
STYLE; the extra real owner nav items stay (shared shell — no functional regression). We touch the Statement page + the PDF.

### The real statement engine (source of truth — determines what is honest)
`OwnerStatementService` projects `owner_ledger` into fixed buckets → `owner_statement_lines`, which sum EXACTLY to the
`net` line (reconciles by construction, #17). The buckets/line-types are a CLOSED set:

`gross` (room_revenue) · `other_revenue` (cleaning + add-ons) · `commission` (management commission) · `management_fee`
· `expenses` · `ota_cost` (OTA commission) · `adjustments` (prior-period) · **`net`** (Σ components).

**There is NO "payment / card-processing fee" bucket.** The wireframe's *"Payment & ops fees · Card processing &
platform"* line has **no backing attribution component** — it is mock fill. The app renders only the real engine lines,
so the wireframe's specific fee line stays honestly absent (see A5). This is the ONE line that must NOT be fabricated.

### The real demo statement (what the demo shows)
Exactly **1 finalized statement**: **#1 · September 2026 · Payout pending** — gross **€1,080.00**, management commission
**−€194.40** (exactly 18%), an **OTA commission €0.00 line kept visible because it's unverified/provisional**, net
**€885.60**. Reconciles: `1,080.00 − 194.40 − 0.00 = 885.60` ✓. By-unit (real, via `OwnerLedgerAttribution`): **A1
€442.80** (3 nights · 10% occ · €540 gross) + **A2 €442.80** (5 nights · 17% occ · €540 gross) = **€885.60** net ·
€1,080 gross ✓. A matched **pending payout** exists (so the rail + remittance-advice PDF are live). Org letterhead
identity is real+flagged-demo (added in #56): `billing_company` "Vyrobel Demo Hospitality, Lda" · VAT PT 512 034 776 ·
country PT · billing@vyrobel-demo.test.

---

## 1. DELIVERABLE A — Owner Statement page (`/owner/statements/{id}`)

### 1a. Wireframe (decoded)
Warm-glass owner chrome ("Statements" active). Header **Statement** + a **month switcher** `‹ [📅 September 2025 ▾] ›`
where the month is a **"Jump to month"** dropdown (each option = label + net figure). Then:
1. **Net-payable HERO** (left, plain glass) — eyebrow `SEPTEMBER 2025 · 3 units`, status pill `⏱ Payout pending`
   (top-right), "Net payable" + **€10,370.00** (Sora 600 ~52px).
2. **Breakdown** card — `Gross room revenue` (64 nights · 3 units, +€12,881.99) · `Management commission` (18% of
   gross revenue, −€2,318.76) · `Payment & ops fees` (Card processing & platform, −€193.23) · **Net payable**
   (€10,370.00, terracotta). *(reconciles 12,881.99 − 2,318.76 − 193.23 = 10,370.00.)*
3. **Right rail:** a dark **NEXT PAYOUT** panel (€10,370 · "Est. 3 Nov 2025" · `🏛 IBAN ···4821` chip) · an **OCCUPANCY**
   card (`71% +8%` + a bar sparkline Mar→Sep) · a standalone **Download statement** button · a **MORE DOCUMENTS** card
   (Remittance advice `RA-2025-09 · PDF` + Booking summary `18 bookings · PDF`).
4. **Statement history** (full width) — `28 statements · €221,500 net total` + tabs All/2025/2024/2023 + **Export CSV** /
   **Export PDF** + a table `MONTH · GROSS · NET PAYABLE · STATUS · ⬇` with a per-row download.
5. **By unit** (full width) — `September 2025 · 3 units`; per unit `name › · N nights · X% occupancy · NET · gross · % of
   net` + a bar; **Total net earnings**.

### 1b. Live page (current)
Same chrome. Period selector is **arrows + a STATIC month label** (no jump-to-month dropdown). Hero uses
**`vy-card--accent`** (accent border, wireframe is plain glass) + a real "Provisional — some lines pending
reconciliation" note. Breakdown = the real engine lines (Gross +€1,080.00 [**no nights·units sublabel**], Management
commission −€194.40 [18% of gross room revenue], OTA commission +€0.00 [Provisional], Net payable €885.60). Rail: dark
**NEXT PAYOUT** (€885.60 · "Payout pending" pill · "Bank transfer" — **no IBAN chip, no date**); **OCCUPANCY = a "NOT YET
AVAILABLE" stub + skeleton bars**; the **Download statement** button lives INSIDE the More-Documents card; More
Documents = Booking summary + Remittance advice (both `PDF export`, no ref/count sublabels). Statement history = real
rows + year filter, but **Export CSV / Export PDF / the per-row download are DISABLED "Soon"**. By-unit = REAL + reconciles
(A1/A2), but the unit name is **not a link**. All data real + owner-scoped + reconciling.

### 1c. Gap table — page

| # | Wireframe element | Live | Kind | Resolution |
|---|---|---|---|---|
| A1 | Month switcher = a **"Jump to month" dropdown** (label + net per option) | arrows + static month label | KIND-1 | Add a real month-picker dropdown over the owner's real statements (each option → `owner.statements.show`; label + net). Client-side popover, no wire mutation. |
| A2 | Hero = **plain glass** (border rgba white .75), 52px figure, terracotta status pill | `vy-card--accent` (accent border) | KIND-1 | Match the plain-glass hero treatment (drop the accent border — same fix as the Overview hero #54); keep the real Provisional note (honest). |
| A3 | Gross line sublabel **"N nights · M units"** | gross has no sublabel | KIND-1 | Add the real nights (`PerformanceMetrics` roomNightsSold) · units-count sublabel to the gross line. |
| A4 | Breakdown row treatment (item/sub/amount, net emphasis) | close | KIND-1 | Match exact spacing/typography/rule; keep the real engine lines (incl. the honest provisional OTA €0.00). |
| A5 | **"Payment & ops fees · Card processing & platform"** line | absent | **KIND-2 → stays honest** | **No such engine bucket** — the app renders only real lines (gross · commission · other_revenue · management_fee · expenses · ota_cost · adjustments · net). NOT fabricated. Match the row STYLE for whatever real lines exist. |
| A6 | **NEXT PAYOUT** rail: **`IBAN ···4821` chip** | "Bank transfer", no IBAN | **KIND-1 (STALE refusal → REAL)** | Add the real masked IBAN (`Iban::masked`, `OwnerPayoutMethodService` default method), **CAP_PAYOUT-gated (co-owner only)** — exactly the Overview #54 pattern. The old "no IBAN field" refusal predates #54. |
| A7 | NEXT PAYOUT rail: **"Est. 3 Nov 2025"** date | absent | **KIND-2 → stays refused** | No scheduled-payout-date field exists (Overview #54 also omitted it, #39). Keep the real status (Payout pending) — never fabricate a date. |
| A8 | **OCCUPANCY** card: `71% +8%` + bar sparkline | "NOT YET AVAILABLE" stub + skeleton | **KIND-1 (STALE refusal → REAL)** | Wire real occupancy % + MoM delta + sparkline via `OwnerOccupancy::forMonth` + `PerformanceMetrics::monthlyTrendForOwnerUnits` — the exact Overview #54 primitives (anti-drift). Demo shows a real low occupancy (~13%), honest — never a fake 71%. |
| A9 | **Download statement** = standalone button | inside the More-Documents card | KIND-1 | Match: standalone Download-statement button, then a separate More-Documents card. |
| A10 | MORE DOCUMENTS: RA ref + booking count sublabels; order (Remittance, Booking) | "PDF export"; Booking first | KIND-1 | Add the real RA ref (payout/statement `sequence_no`) + real booking count sublabels; match order. Both already real, CAP_EXPORTS-gated. |
| A11 | Statement history: **Export CSV / Export PDF real** + a real per-row download | all DISABLED "Soon" | **KIND-1 (real)** | **Export CSV** → a new owner-scoped history CSV controller (CAP_EXPORTS). **Per-row download** → the existing real per-statement PDF (`owner.statements.pdf`). **Export PDF** → see Decision D-STMT-3. |
| A12 | By-unit: unit name is a **link (`name ›`)** to Unit Detail | not a link | KIND-1 | Link the unit name → `owner.units.show`. The rest (nights · occ · net · gross · % of net · bar · total) is already REAL + reconciles — match exact visual treatment. |
| A13 | History magnitude (28 statements · €221,500 · year tabs 2023–25) | 1 real statement · 2026 tab | **honest (data reality)** | Render the real rows (structure identical; the year filter already works). See Decision D-STMT-1 (optional flagged demo history seed for a fuller demo). |

**Net for the page: every wireframe element is KIND-1 (buildable on real data) except A5 (fee line — no engine bucket, stays
honest) and A7 (payout date — no field, stays refused).** Two former refusals (A6 IBAN, A8 occupancy) are **REAL now** —
their backends shipped in Overview #54 on a sibling owner surface, so the register-132 refusals are STALE.

---

## 2. DELIVERABLE B — Owner Statement PDF

### 2a. Wireframe (decoded)
White letterhead print document (`#FCF7F2` field / white page, `#E8703A→#C2410C` accent):
- **Letterhead:** terracotta "V" mark + **Seaside Villas** (bold) / `Av. da Marina 14, 8000-114 Faro · managed with
  Vyrobel`. Top-right eyebrow **OWNER STATEMENT** / ref **OS-2025-09**.
- **Title:** **September 2025** + subtitle `Amara Okafor · Seaside Villas · 3 units · issued 1 Oct 2025`.
- **Net-payable headline row** (split): a **dark left panel** — `NET PAYABLE TO OWNER` / **€10,370.00** / `Payout pending
  · est. settlement 3 Nov 2025 · IBAN ····4821` — beside a **light-peach stats panel** — `Occupancy 71% · Nights sold 64
  · Avg. nightly rate €201.28`.
- **Breakdown table** (`ITEM · DETAIL · AMOUNT`): Gross room revenue (64 nights · 3 units, +€12,881.99) · Management
  commission (18% of gross revenue, −€2,318.76) · Payment & ops fees (Card processing & platform, −€193.23) · **Net
  payable** (€10,370.00, terracotta rule).
- **Performance by unit** table (`UNIT · NIGHTS · OCCUPANCY · REVENUE`): Villa Azure · 2-bed apt / 24 / 80% / €4,843.63 …
  + a **Total gross room revenue** row (64 / 71% / €12,881.99). *(reconciles: 4,843.63+3,606.96+4,431.40 = 12,881.99.)*
- **Payout note** callout (peach box + left terracotta bar): net settles to the bank by transfer, est. date; commission
  18% of gross; fees at cost; EUR + taxes note.
- **Footer:** `Seaside Villas · statement OS-2025-09 · generated 1 Oct 2025` (left) · `Questions? owners@seasidevillas.ie`
  (right).

### 2b. Live PDF (current)
Generic Vyrobel-branded doc (dompdf, DejaVu Sans, table-based). Header: `V Vyrobel` + operator name muted; right eyebrow
`Owner statement` / period / `Statement #1 · Finalized`. Meta table (Owner / Period / Currency / FX). A **2-column
breakdown table** (description + amount) of the real lines + a net row. A one-line reconcile note + a generic footer.
**No letterhead identity/VAT/address, no OS-YYYY-MM ref, no net-payable headline, no occupancy/nights/ADR stats, no
performance-by-unit table, no payout-note callout, no contact footer.** Every figure reconciles to `net_payable_minor`.

### 2c. Gap table — PDF

| # | Wireframe element | Live | Kind | Resolution |
|---|---|---|---|---|
| B1 | **Letterhead** — managing-agent name + address + "managed with Vyrobel" | "V Vyrobel" + operator name | **KIND-1 (real)** | Real `Organization.billing_company` (fallback `name`) + `country` + "managed with Vyrobel". Street/city NOT modelled → the wireframe's "Av. da Marina 14, Faro" is a placeholder; render country only. (Same as the Booking-Summary PDF letterhead #56 — reuse/extract a shared letterhead.) |
| B2 | Ref **OS-YYYY-MM** (top-right) | "Statement #1" | **KIND-1 (real)** | Period-derived `OS-{period_start Y-m}`; cross-reference the real `sequence_no`. (Mirrors `BS-YYYY-MM` #56.) |
| B3 | Subtitle `owner · org · N units · issued {date}` | in the meta table | KIND-1 | Restructure to the single subtitle line (all real: owner name, org, unit count, `finalized_at`/period). |
| B4 | **Net-payable headline** (dark) + **stats panel** (Occupancy · Nights · ADR) | absent | **KIND-1 (real — refusal stale)** | Dark net-payable panel (net + status + IBAN if CAP_PAYOUT, see D-STMT-2; est. date omitted, #39) beside real stats via `PerformanceMetrics::forOwnerUnits` (occupancy · nights · `adrMinor`). Same primitive #56 used for occupancy. |
| B5 | Breakdown **DETAIL column** (nights·units, 18%, fee detail) | 2-col (no detail) | KIND-1 | 3-column `ITEM · DETAIL · AMOUNT`; real detail sublabels (gross nights·units; commission "18% of gross"); only the real engine lines (no fabricated fee line, A5). |
| B6 | **Performance by unit** table (`nights · occupancy · revenue`) + Total gross row | absent | **KIND-1 (real — refusal stale)** | Per-unit nights/occupancy via `PerformanceMetrics::forOwnerUnits([unitId],…)`; **revenue = per-unit gross room_revenue**, so Σ rows == Total gross (reconciles to gross). Owner's own units only (#28). Same backend #56 used. |
| B7 | **Payout note** callout | absent | KIND-1 | Real callout: net settles by the real rail (IBAN if CAP_PAYOUT), commission 18% of gross, fees at cost, EUR + taxes. No fabricated date. |
| B8 | **Footer** — identity + statement ref + generated date + **email** | generic footer | KIND-1 | Two-part footer: `{billing_company} · statement OS-YYYY-MM · generated {date}` ‖ `Questions? {billing_email}` (real org fields). |
| B9 | Occupancy/ADR figures | absent | data | REAL via `PerformanceMetrics` (#56 precedent). |

**Net for the PDF: every wireframe figure has an honest owner-scoped source** — occupancy/nights/ADR + per-unit
performance (`PerformanceMetrics::forOwnerUnits`, already used by the Booking-Summary PDF #56), the letterhead
identity/VAT/country/email (real org fields, #56), the OS-YYYY-MM ref (period-derived), the masked IBAN (`Iban::masked`).
**No standing refusal on the PDF** except the payout **date** (no field) and any fabricated fee line (A5).

---

## 3. Data-availability findings (the decisive checks)

| Figure | Honest source | Verdict |
|---|---|---|
| Breakdown (gross − commission − … − net) | `owner_statement_lines` (sum to net by construction) | ✅ real, reconciles #17 |
| By-unit NET split (page) | `OwnerLedgerAttribution::netPerCurrency` over the statement's claimed ledger lines | ✅ real, Σ == net #17 |
| By-unit GROSS/occupancy (PDF) | per-unit `room_revenue` + `PerformanceMetrics::forOwnerUnits` | ✅ real, Σ == gross |
| Occupancy % + delta + sparkline (page) | `OwnerOccupancy::forMonth` + `PerformanceMetrics::monthlyTrendForOwnerUnits` | ✅ real (refusal STALE — shipped in #54) |
| Occupancy / Nights / ADR (PDF stats) | `PerformanceMetrics::forOwnerUnits` (`occupancyPct` · `roomNightsSold` · `adrMinor`) | ✅ real (#56 precedent) |
| Masked IBAN (page rail + PDF) | `OwnerPayoutMethodService` default → `Iban::masked`, CAP_PAYOUT | ✅ real (refusal STALE — shipped in #54) |
| Letterhead company / VAT / country / email | `Organization.billing_company` / `billing_vat_id` / `country` / `billing_email` | ✅ real (#56) |
| Month switcher options | the owner's real statements (label + net) | ✅ real |
| History CSV / per-row PDF | new owner-scoped CSV controller / existing `owner.statements.pdf` | ✅ real |
| "Payment & ops fees" line | **no engine bucket** | 🚫 stays honest (render real lines only) |
| Payout **date** ("Est. 3 Nov") | **no scheduled-date field** | 🚫 stays refused (#39) |

---

## 4. Decisions for the founder (before GATE 1)

- **D-STMT-1 — Demo statement history (recommend: seed a small FLAGGED demo history).** The demo owner has ONE real
  statement, so the history table + year filter + occupancy sparkline render sparse vs the wireframe's rich look. To make
  the page demo its full shape, seed ~5–6 backdated monthly statements for the demo owner from backdated `owner_ledger`
  entries + reservations (so occupancy is real too), **clearly flagged demo-only** — exactly the precedent set by Batch B
  (`DemoSeeder::seedAnalyticsHistory`) and the #56 demo target. The computation stays 100% real (#39); only the underlying
  demo rows are seeded. *Alternative:* ship with the single real statement (fully honest, just sparse). **Recommend the
  flagged seed** for a compelling demo; it is additive and never blocks GATE 1.
- **D-STMT-2 — IBAN on the PDF (recommend: CAP_PAYOUT-gated).** The page rail shows the masked IBAN only to a
  payout-capable role (CAP_PAYOUT = co-owner). The PDF is CAP_EXPORTS-gated (co-owner **+ accountant**), so an accountant
  can export it. **Recommend:** render the IBAN on the PDF only when the exporter also holds CAP_PAYOUT; otherwise show
  the rail label without the account number (never leak the IBAN to a non-payout role via the export).
- **D-STMT-3 — History "Export PDF" (recommend: per-row PDF now; a history-summary PDF later).** The per-row download and
  Export CSV are cleanly real (each row is a real statement PDF; CSV is a real history dump). A single top-level "Export
  PDF" of the *whole filtered history* has no existing artifact. **Recommend:** wire Export CSV (real) + the per-row
  download (→ the real per-statement PDF) at GATE 1, and treat the top-level "Export PDF" as either (a) a real
  per-year statement-history summary PDF (small dompdf build, reconciles) or (b) an honest "download each statement's
  PDF from its row". I lean to (a) if it stays clean; will confirm in-build.
- **D-STMT-4 — Anti-drift shared helpers (recommend: extract two).** (1) The masked-IBAN + tax-hold "next payout" logic is
  currently a private method in `OwnerDashboard` (#54); the Statement page + PDF need the same → extract a shared
  `App\Support\Owner\OwnerNextPayout` read by all three (so they can't drift). (2) The per-unit statement split (net for
  the page, gross/occupancy for the PDF) → extract a shared `OwnerStatementBreakdown` so page + PDF reconcile identically.
  Both are pure reads over existing primitives; the existing suite staying green is the proof nothing is disturbed.

---

## 5. Honesty ledger (what changes, and why it's still honest)
- **Formerly refused, now REAL (backing already exists):** the page occupancy card + sparkline (A8) and the payout IBAN
  chip (A6) — their backends shipped in Overview #54; the PDF occupancy/nights/ADR + performance-by-unit (B4/B6) — the
  same `PerformanceMetrics::forOwnerUnits` the Booking-Summary PDF #56 uses; the PDF letterhead identity/VAT/country/email
  (B1/B8) — real org fields (#56); the history CSV + per-row PDF (A11).
- **Stays honest / refused (genuinely unbacked):** the **"Payment & ops fees" line** (no engine bucket — render only real
  engine lines, A5); the **payout date** "Est. 3 Nov" (no scheduled-date field, A7/B4/B7); street/city in the letterhead
  (only country modelled).
- **Unchanged invariants:** the breakdown sums to `net` and the by-unit split sums to net (page) / gross (PDF) **to the
  cent** (#17); guest data name-/counts-only (**the statement surfaces carry NO guest data at all** — only units/money;
  #28); owner writes on **controller routes** (the target/CSV are GET/controller, filters ride GET so
  `SetOwnerScopeForPortal` re-runs, #20); the PDF stays **CAP_EXPORTS-gated**; IBAN stays **CAP_PAYOUT-gated**; fail-closed
  owner isolation on an empty unit set.

---

## 6. Build plan

### ⛔ GATE 1 — Statement page (exact design + real reconciling data)
1. **Shared reads (anti-drift, D-STMT-4):** extract `App\Support\Owner\OwnerNextPayout` (masked IBAN + tax-hold/pending
   state, CAP_PAYOUT) from `OwnerDashboard` and reuse on the Statement page; extract `OwnerStatementBreakdown` (per-unit
   net + gross + occupancy/nights) from `OwnerStatementView::byUnitPayload` so the PDF can reuse it verbatim.
2. **Component (`OwnerStatementView`):** add the occupancy payload (`OwnerOccupancy` + `monthlyTrendForOwnerUnits`), the
   next-payout IBAN (via `OwnerNextPayout`), the gross nights·units sublabel, the month-switcher option list, and the
   history CSV/per-row links. All server-side/GET over the owner-scoped set (no wire mutation, #20).
3. **View (`statement.blade.php`):** month-picker dropdown (A1); plain-glass hero (A2); gross sublabel (A3); breakdown row
   treatment (A4); dark rail with the real IBAN chip (A6, no date A7); real occupancy card + sparkline (A8); standalone
   Download-statement button (A9) + a real More-Documents card with ref/count sublabels (A10); history Export CSV + real
   per-row download (A11); by-unit unit-name links (A12). Extend the shared `--vy-*` tokens; page-scope any
   Statement-specific CSS (kept OUT of the shared owner layout so it can't trip other pages' `assertDontSee` guards).
4. **New route/controller:** `GET owner.statements.history.csv` (CAP_EXPORTS) — owner-scoped statement history CSV (no
   guest data). Per-row download reuses the existing `owner.statements.pdf`. Export-PDF per D-STMT-3.
5. **Demo:** the flagged demo statement history seed per D-STMT-1 (if approved).
6. **Tests + verify:** extend `OwnerStatementViewTest` — occupancy real + owner-scoped, IBAN CAP_PAYOUT-gated
   (view-only/accountant don't see it), by-unit Σ == net #17, CSV owner-scoped + CAP_EXPORTS (cross-owner 404), no wire
   mutation #20, `assertNoRawBlade`. Suite green + PHPStan L5 + Pint + `migrate:fresh --seed` clean; browser-verify vs the
   wireframe + reconciliation. Per-gate commit.

### ⛔ GATE 2 — Statement PDF (exact document design + real reconciling data) + consolidate + verify
1. **`OwnerStatementDocument`:** add the letterhead (billing_company/country/"managed with Vyrobel"), the OS-YYYY-MM ref +
   sequence, the subtitle line, the net-payable dark headline + stats (occupancy/nights/ADR via `PerformanceMetrics`), the
   3-column breakdown with detail, the **performance-by-unit** table (via `OwnerStatementBreakdown`, gross/occupancy,
   reconciling to gross), the payout-note callout, the identity/email footer. IBAN per D-STMT-2. Σ==net + Σ==gross
   UNCHANGED.
2. **`pdfs/owner-statement.blade.php`:** rebuild to the wireframe's document layout/typography/accent (PDF-safe HTML4/CSS2.1,
   DejaVu Sans). Keep the view logic-free (composed strings in the builder — the #56 lesson).
3. **Consolidate docs:** this file (gaps→fixed) + `DECISIONS.md` (dated entry + new invariant #57) + `DIVERGENCE-REGISTER.md`
   (row 132 → RESTRUCTURED exact-match + operational; the PDF twin) + `PROJECT-STATE.md` + `RESUME.md` + `CLAUDE.md` status.
4. **Verify (STOP):** suite green + PHPStan L5 + Pint + `migrate:fresh --seed` clean; browser-verify the page; **generate +
   render the PDF** (install poppler or render the blade HTML) vs the wireframe; prove the reconciliation (breakdown ==
   net, by-unit == net [page] / gross [PDF], to the cent). Per-gate commit + **push all**.

**Bottom line:** the founder's "exact match, deferrals in scope" is achievable with **zero fabricated figures** — every
wireframe figure has a real owner-scoped source, two of the page's old refusals (occupancy, IBAN) are simply stale (their
backends already shipped in #54), and the PDF's rich content reuses the exact `PerformanceMetrics`/letterhead machinery
#56 already proved. The only things we still refuse to invent are the "payment & ops fees" line (no engine bucket) and the
estimated payout date (no field).

---

## 7. BUILD LOG — GATE 1 (Owner Statement page) ✅ COMPLETE (commit `ddf1f55`)

Shipped exactly to §6's plan; honesty held (§5). The two stale refusals are now REAL:
- **Occupancy card + mini bar chart** (was a "Not yet available" stub) via the shared **`OwnerOccupancyTrend`** (real
  `OwnerOccupancy` over the statement's month + a 6-month trend). **Masked IBAN chip** on the dark rail via the shared
  **`OwnerNextPayout`** — CAP_PAYOUT only (an accountant/view-only sub-user never sees it); the estimated payout **date**
  stays refused (no field, #39).
- Month **"jump to month"** switcher (real statements + net); **plain-glass hero**; the gross line's real **"N nights · M
  units"** sublabel; a standalone **Download statement** + a **More documents** card; **Statement history** with a real
  **Export CSV** + a per-year **Export PDF** + a per-row statement PDF (all `owner.statements.history.*`, owner-scoped +
  **CAP_EXPORTS**, `?year=` clips never widens; a distinct `/statement-history/*` path so `/statements/{statement}/pdf`
  can't shadow it); by-unit **unit-name links**. The by-unit split still reconciles Σ per-unit net == the statement net
  (#17); owner's own units only (#28); no `/livewire/update` mutation (#20); no invented "Payment & ops fees" line (#39).
- **Anti-drift (D-STMT-4):** extracted `OwnerNextPayout` + `OwnerOccupancyTrend` from `OwnerDashboard` (#54, which now
  delegates) + `OwnerStatementBreakdown` from `OwnerStatementView` (reused by the GATE-2 PDF). Fixed a `word@directive`
  raw-Blade trap (`transfer@endif`).
- **Demo (D-STMT-1):** a flagged demo statement history (Apr–Aug 2026 — real stays → attribution → statements finalized
  IN PERIOD ORDER, marked paid) so the page/Overview demo on a real shape; **September still claims only its own lines
  (net €885.60 unchanged)**. Every net = real gross − the real 18% commission.
- **Verification:** suite **1064 / 8250** green; PHPStan L5 = 0; Pint clean; `migrate:fresh --seed` clean (6 statements,
  Sep €885.60 preserved); browser-verified vs the wireframe (month picker with 6 real months, occupancy 13% + bars, history
  €3,690 net reconciling, by-unit → €885.60). Tests: `OwnerStatementViewTest` 5 → 9 (occupancy/switcher/gross sublabel +
  IBAN CAP_PAYOUT gating + history CSV owner-scoped/EXPORTS-gated/year-clip + history PDF EXPORTS-gated + `assertNoRawBlade`).

## 8. BUILD LOG — GATE 2 (Owner Statement PDF) ✅ COMPLETE

Shipped to §2c/§6's plan; the old PDF was a generic branded doc → now the wireframe's letterhead statement, all REAL:
- **Letterhead** — real `billing_company` (fallback `name`) + `country` + "managed with Vyrobel" (street/city not modelled,
  omitted); the **OS-YYYY-MM** doc ref; the subtitle (owner · company · N units · issued {finalized_at}).
- **Net-payable dark headline** + a light stats panel — **Occupancy · Nights sold · Avg. nightly rate**, all real via the
  shared `OwnerStatementBreakdown` (occupancy == the by-unit Total row; ADR = gross ÷ nights, divide-by-zero-safe). The
  masked **IBAN** shows only for a **CAP_PAYOUT** exporter (`$canSeePayout` from `OwnerContext` in the controller); an
  accountant export omits it (#28). No estimated date (#39).
- **3-column breakdown** (Item · Detail · Amount) — the real engine lines + truthful detail (gross "N nights · M units",
  commission "18% of gross room revenue"); no invented fee line. **Performance by unit** (nights · occupancy · revenue) via
  the shared helper + a **Total gross room revenue** row — Σ per-unit gross == the statement gross (#17). A **payout-note
  callout** (net + rail + IBAN-if-payout-capable + the 18% commission note + EUR/taxes) and a two-part identity/email footer.
- The breakdown lines still sum to the net line; occupancy/nights/ADR are presentation, never summed into money. NO guest
  data on the document (#28). PDF-safe (dompdf HTML4/CSS2.1, DejaVu Sans, table-based); the view is logic-free (composed in
  `OwnerStatementDocument`) — the #56 "top-of-file @php dropped the &lt;head&gt;" lesson; a second `word@directive` trap
  (`transfer@if`) fixed.
- **Verification:** the rendered document was browser-verified against `Owner Statement PDF.html` — real Sep 2026 data:
  Occupancy 13% · Nights 8 · ADR €135.00, breakdown €1,080 − €194.40 = €885.60, by-unit €540 + €540 = €1,080, OS-2026-09,
  the payout callout, the `billing@vyrobel-demo.test` footer. `OwnerStatementPdfTest` 5 → 7 (real occupancy/ADR + by-unit
  Σ==gross + IBAN CAP_PAYOUT gating; the existing reconciliation/isolation/no-invented-line/no-guest tests unchanged).

**Invariant #57 — Owner Statement exact-match (page + PDF):** the page + PDF match their wireframes exactly on REAL,
owner-scoped, reconciling data — the occupancy card/stats + masked IBAN (CAP_PAYOUT) are REAL (stale refusals, backed by
`OwnerOccupancyTrend`/`OwnerNextPayout`); the breakdown sums to net and the by-unit split sums to net (page) / gross (PDF)
to the cent (#17); the page + PDF share the ONE `OwnerStatementBreakdown` (can't drift); guest data absent (#28); exports
are CAP_EXPORTS controller routes, the IBAN CAP_PAYOUT-only; no "payment & ops fees" line and no estimated payout date are
invented (#39).
