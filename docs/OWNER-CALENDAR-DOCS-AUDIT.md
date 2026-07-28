# Owner Block Calendar + Documents — wireframe-fidelity audit

**Date:** 2026-07-28 · **Author:** build session · **Status:** ✅ **BUILT — invariant #60** (GATE 1 Block Calendar `ab4333b` + GATE 2 Documents). Both pages shipped wireframe-exact on the real backends; the two investigate points resolved as proposed (Calendar reason = real in `notes`; Calendar repeat/series = expanded into N REAL blocks sharing a `series_id`, never faked; Documents annual tax summary = the already-real `owner.tax.annual` PDF, now surfaced as the dark card with the REAL net). Suite 1081→ green; PHPStan L5 (0); Pint clean; browser-proven (a real drag-select block + release; real statement + annual-tax PDF downloads). See DECISIONS 2026-07-28 #60 + DIVERGENCE rows 141/148. (The PHASE-1 analysis below is preserved as the audit of record.)

Founder brief: make the LIVE owner **Block Calendar** (`/owner/calendar`) and **Documents** (`/owner/documents`) pages match
their decoded wireframes (`Owner Block Calendar.html`, `Owner Documents.html`) EXACTLY — design + behaviour — on real
owner-scoped backends. Both are largely REAL already: the block/release mechanism exists (`OwnerBlockService`, used by My
Units); the documents are the real statement / remittance / booking-summary PDFs + the annual-tax PDF, already built +
`CAP_EXPORTS`-gated. Same discipline as the prior owner exact-match gates (#54–#59).

Wireframes decoded via the dc-runtime bundle (JSON.parse the `<script type="__bundler/template">` → the `<x-dc>` body):
`/tmp/wf_calendar.txt`, `/tmp/wf_documents.txt`.

---

## PAGE A — BLOCK CALENDAR (`/owner/calendar`)

### What's REAL today (keep)
- Shared owner chrome (`<x-owner.shell active="calendar">`), header + explainer, unit selector (A1/A2), month nav (prev /
  title / next / Today), the month grid with **real** day states (past / booked / blocked / open) for the selected
  owned unit, and a **Scheduled blocks** list — all owner-scoped (`OwnerScope` + `OwnerPortalService::units()`).
- The WRITE path is REAL + guarded: `owner.calendar.block` (POST) + `owner.calendar.release` (DELETE) →
  `OwnerBlockController` → `OwnerBlockService` → the certified `AssignmentService` (single inventory write path #1,
  non-overlap enforced). Owner-scope guard in the service (`ownedUnitOrFail`) — tampering with a foreign unit id 403s.
  Both writes are `$capActions` (co-owner only, #20/#42); the READ is `$capCalendar` (co-owner + view-only).
- The block **reason** is already storable: the granular reason is written to `InventoryBlock.notes` (the `reason`
  column stays the real enum `'owner'`). Proven by `OwnerBlockCalendarTest`.

### DESIGN gaps vs the wireframe (KIND-1 — FIX)
1. **Background + cards** — the wireframe is the warm-glass owner palette (bg `linear-gradient(135deg,#F6C89B,#F1B896,
   #EBD9CF,#D9D6D2)`, frosted cards `rgba(255,252,248,.6)` blur(22px), radius 18–22, shadow `0 14–16px 40–44px
   rgba(150,90,50,.14–.16)`), animations `vyFadeUp / vyShimmer / vyDrift / vyCardIn / vyToastIn`. The shared shell
   already provides the palette; the two-column layout (calendar panel `flex:1.7` + right rail) + the frosted toolbar
   need to match.
2. **Legend** — the wireframe has **4** states (Booked / Owner block / Selecting / Open); the live page has 3 (no
   "Selecting"). Colors: Booked `rgba(232,112,58,.16)`+border, Owner block `#3A2E27` (dark), Selecting gradient
   `#F3B98E→#E8875A`, Open white.
3. **Day cells** — the wireframe marks a booked cell with the **guest name** ({{ c.guest }}). The live page shows
   "BOOKED" with no name (deliberately tighter). Per the brief + the owner-name-only precedent (owner Bookings PDF #56),
   show the **guest NAME only** (#28) via the shared `GuestName`.
4. **Composer** — the live page uses **date-picker inputs** (First night / Last night) + a **reason dropdown**. The
   wireframe is a **Selected-range card** (range · nights · unit) driven by clicking/dragging days, **reason BUTTONS**
   (Personal stay / Maintenance / Seasonal hold, checkmark), an optional **note input**, a **Repeat this block** toggle,
   and Confirm/Cancel. Empty state: "Select one or more open days on the calendar to create a block."

### OPERATIONAL / INTERACTION gaps (KIND-1 behaviour — BUILD)
5. **Click / press-and-drag to select** — the core interaction. Client-side (Alpine): mousedown on an open day starts a
   selection, mouse-enter extends it, mouseup ends it → the composer shows the selected range. Clicking a **blocked**
   day releases it (a real DELETE). This replaces the date-picker form. On submit, a real POST of the range.
6. **Toast + Undo** — after a block/release, a bottom toast ("Dates blocked" / "Block removed") with an **Undo**
   (undo of a just-created block = release it). Real, driven by the PRG flash + the created block id.

### FEATURE gap — the two investigate points
- **(a) REASON** → **REAL, no build.** `OwnerBlockService::block()` already persists the granular reason to `notes`.
  **RESOLUTION:** align `OwnerBlockService::REASONS` to the wireframe's exact three — **Personal stay / Maintenance /
  Seasonal hold** — and render them as the three reason buttons. (Updates the one test that used the old sample values.)
- **(b) REPEAT / SERIES** → **REAL build (expand into individual blocks).** There is no recurring-block engine, and a fake
  "scheduled series" that creates nothing is forbidden (#39). **RESOLUTION:** on submit with Repeat on, expand the
  recurrence (Weekly / 2 weeks / Monthly × Occurrences) into **N real individual `block()` calls** — each occurrence a
  real `InventoryBlock`, owner-scoped, audited, non-overlap-checked. A single shared `series_id` note-tag marks them as a
  series so the Scheduled-blocks list can badge "Series" (real, derived — not a fabricated parent row). Occurrences that
  hit a booked/blocked night are skipped and reported honestly ("N of M nights blocked; the rest were already taken").
  Cap the occurrences (e.g. ≤ 12) to keep it sane. **Never a fake series.**

### REFUSED / honest (#39/#28)
- The wireframe's sample booked-guest surnames (Rossi / Aalto) + the two sample scheduled blocks are the prototype's demo
  data — the app renders the **real** availability + real blocks (honest empty "No upcoming blocks for this unit.").
- Booked cells show the guest **name only** — never contact/PII (#28).

---

## PAGE B — DOCUMENTS (`/owner/documents`)

### What's REAL today (keep) — this page is a real aggregation, not a store
- `OwnerDocuments` derives every row from a **generator that genuinely produces a PDF today** (the load-bearing rule):
  `owner.statements.pdf` (statement), `owner.statements.bookings` (booking summary), `owner.payouts.remittance`
  (remittance advice — one per real payout, at every status). Grouped by **year → period (statement) → docs**, with the
  real reference (`sequence_no`), real dates, real amounts, real status badge + rail label. Owner-scoped (`OwnerScope`),
  finalized/paid statements only (a draft is not a document). Downloads are `$capDocuments`-gated; the PDFs themselves are
  `CAP_EXPORTS`-gated.
- The **annual tax summary already exists** — `owner.tax.annual` → `OwnerAnnualTaxDocument` (`CAP_EXPORTS`-gated), fed by
  `OwnerTaxService::annualSummary($owner, $year)` (aggregates the year's statement nets, reconciling). Currently surfaced
  as a per-year "Tax summary" button + a light bottom card.

### DESIGN gaps vs the wireframe (KIND-1 — FIX)
1. **Layout shape** — the wireframe is: a **year-filter pill row** (only when > 1 year) → each **MONTH is its own frosted
   card** (month header + "Net {{ g.net }}" + doc rows) → **one DARK annual-tax-summary card** at the bottom. The live
   page wraps everything in a single big year card with month sub-sections + a per-year "Tax summary" button + a **light**
   annual card + a "Download everything ZIP" stub. Restructure to the wireframe's month-cards + year-filter pills.
2. **Annual tax card** — the wireframe is a **dark** card: "{year} annual tax summary" + "All 12 months · net
   {{ taxNetStr }} · one PDF for your accountant" + PDF. The live card is light and omits the "All 12 months · net X"
   meta. **RESOLUTION:** render the dark card with the **real annual net** (`OwnerTaxService::annualSummary(...)->net`, the
   SAME figure the PDF reports — reconciling), one card per real year (year-filtered with the months).
3. **Sub-header** — wireframe "Statements, remittances & tax summaries · full archive ›"; live "Statements, booking
   summaries & remittance advices · N documents · since …". Keep the real count/since (honest, derived) but align the
   lead wording + the "full archive ›" link.
4. **"Download everything ZIP"** — NOT in the wireframe. There is no ZIP builder (honestly a "Soon" today). **RESOLUTION:**
   drop it to match the wireframe exactly (each doc already downloads on its own).

### REFUSED / honest (#39) — unchanged, already correct
- The prototype's fabricated multi-year history (2025/2024/2023 · €98,710 …) + invented references (OS-2025-09) + booking
  counts stay refused; the app shows the real years/periods/figures + the real annual net.

---

## BUILD PLAN (gated)

**⛔ GATE 1 — Block Calendar.** Rebuild `block-calendar.blade.php` (page-scoped `.vy-cal-*` CSS) + extend the component to
the wireframe: the frosted two-column layout, the 4-state legend, the day grid with **guest name-only** on booked cells
(`GuestName`), **click/press-drag selection** (Alpine) → the Selected-range composer (reason buttons Personal/Maintenance/
Seasonal + note + Repeat toggle Weekly/2wk/Monthly × occurrences + Confirm/Cancel), the Scheduled-blocks list (real
blocks, reason/nights/**Series** badge/note + Remove-with-confirm), and the **toast + Undo**. Extend `OwnerBlockController`
+ `OwnerBlockService` to accept the optional repeat params → **expand into N real individual blocks** (shared series tag);
align `REASONS` to the three wireframe values. Tests: single block/release (existing, reasons updated), the **series
expansion** (N real blocks), owner-scoped, booked-cell name-only, controller-routed + audited. Browser-verify grid +
drag-select + block + release. Per-gate commit.

**⛔ GATE 2 — Documents.** Rebuild `documents.blade.php` to the wireframe (month-cards + year-filter pills + the **dark**
annual-tax-summary card with the real net); extend the component to pass the real annual net per year (via
`OwnerTaxService::annualSummary`, reconciling). Real PDFs stay `CAP_EXPORTS`-gated + owner-scoped (#28); honest-empty for a
year with no committed statements; drop the ZIP stub. Tests: the archive renders the real grouped docs + the annual card
with the real net; downloads gated + owner-scoped. Browser-verify the archive + a **real PDF download**. Consolidate docs
(this file → gaps-fixed, DECISIONS #60, DIVERGENCE rows 141/148 → RESTRUCTURED, PROJECT-STATE, CLAUDE). Per-gate commit +
PUSH.

**FINALE (in GATE 2 verify):** block a real date range (+ a repeat series) → confirm the real `InventoryBlock` rows exist
(owner-scoped) + show in Scheduled blocks → release one → confirm released. Download a real statement/remittance PDF (+ the
annual summary) → confirm it's the real gated document.

### Guards (both pages)
🔒 #39 no fabrication (honest-empty, never fake blocks/series/documents) · #28 guest name-only + owner-scoped · #20
controller-route writes + audited · exports `CAP_EXPORTS`-gated · reuse the existing `OwnerBlockService` + the built PDFs
(anti-drift). DISTURB NOTHING existing (the suite stays green). Browser-verify both + prove a real block/release + a real
download.
