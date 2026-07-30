# Owner Booking Detail — exact-match audit (PHASE 1, read-only)

**Date:** 2026-07-30 · **Page:** `/owner/bookings/{booking}` (`OwnerBookingDetail`) vs the decoded wireframe `docs/design/raw/Owner Booking Detail.html`.
**Method:** decoded the dc-runtime bundle (JSON.parse the `__bundler/template` script → the `<x-dc>` body + the `Component` logic) + read the real backend (`OwnerBookingDetail`, `OwnerLedgerAttribution`, `OwnerCommissionRate`, `FormatsOwnerData`, `OwnerPortalService`, the PDF pattern). **Nothing changed.**

---

## 0. The shape of the gap

The page **already exists and is already correct on the two things that matter most** — it is owner-scoped (a `{booking}` id that doesn't touch one of THIS owner's units 404s, `OwnerBookingsTest`-proven), it **already refuses guest email/phone/country** (#28, with an honest "contact details stay with the management team" line), and its earnings come through the ONE shared `OwnerLedgerAttribution::breakdown()` so "Net to you" can't drift. What it is NOT is the **exact wireframe** — it uses the old catalogued layout (a 4-up KPI grid + a plain net rail), not the wireframe's header/avatar + big check-in/out reservation card + gross→net earnings card + guest card + timeline + dark count-up net hero + booking-details card + actions.

So this is a **presentation rebuild to the exact wireframe on the already-correct backend**, plus **two non-negotiable corrections** the wireframe itself gets wrong, plus **wiring the sections the current page omits** (timeline, booking-details, a real "Message" → the operator relay, a real confirmation PDF).

⚠️ **The two corrections are the whole point:**
1. **GUEST EMAIL + PHONE — REFUSED (#28).** The wireframe exposes `{{ email }}` (`l.andersen@icloud.com`) + `{{ phone }}` (`+45 20 55 18 04`). The entire owner realm shows guest **NAME only** — never contact — on pages, PDFs, CSVs, the API and Messages (an owner seeing a guest's email/phone is a privacy breach AND a platform-bypass/disintermediation risk). These rows are REMOVED; an honest "Guest contact details are handled by your management team" line stands in their place. **"Message {{ guestFirst }}" is NOT owner→guest** — it routes to the **operator relay** (owner→operator staff, the same `owner.messages` relay as My Units/Messages).
2. **COMMISSION "· 15%" → the REAL rate.** The wireframe hardcodes `Math.round(gross * 0.15)` and the label "Management commission · 15%". The real `ManagementAgreement.commission_pct` is **18%** (demo), and the real ledger already carries the commission at that rate (a demo booking: room_revenue €540.00 − commission €97.20 [= 18%] = net €442.80). We render the **real ledger breakdown** and read the rate label from the shared `OwnerCommissionRate` (never a hardcoded 15%).

---

## 1. Section-by-section classification (REAL / REFUSE / FIX)

| # | Section (wireframe) | Real backing | Verdict |
|---|---|---|---|
| 1 | **Breadcrumb + header** (Overview / Bookings / {guestName}; avatar/initials · name · status pill · headSub "{unit} · N nights · {source}") | The owner-scoped reservation + `guestName` (#28) + `stageBadge` + `sourceLabel`. | ✅ **REAL → restyle** to the exact header (the current page uses the shell title). |
| 2 | **Reservation card** (conf · big check-in/out day + "from 3:00 PM"/"by 10:00 AM" + nights pill · Unit/Guests/Nights/Nightly-rate) | Real reservation: `reference`, `check_in`/`check_out`, nights, owned unit(s), `adults+children`, nightly rate. | ✅ **REAL → restyle** to the wireframe's day-hero + dashed connector + 4-col footer. "from 3:00 PM / by 10:00 AM" are the standard property policy (static copy, not per-booking data) — keep as the wireframe's fixed labels. |
| 3 | **Your earnings** (Accommodation · N×rate / Cleaning collected / Gross / **Commission · 15%** / Cleaning paid / Net) | `OwnerLedgerAttribution::breakdown()` — the REAL rows: `room_revenue` (credit) − `commission` (debit, at the real 18%) − `ota_cost`, = net. | ✅ **REAL (reconciles #17) + FIX the rate.** The wireframe's cleaning-collected-then-paid-out model is NOT in the ledger (no `cleaning` entry_type on the demo bookings) → render the **real breakdown lines** in the wireframe's gross→net card style; the Commission line shows the **real rate** via `OwnerCommissionRate` (18%), NEVER 15%. Zero-value rows (e.g. `ota_cost €0`) hidden presentationally (no info, doesn't change net). Empty ledger → honest "Not yet attributed" (unchanged). |
| 4 | **Guest card** (avatar · name · tag · **Email** · **Phone** · Country · "Message {first}") | Guest NAME (#28); the operator relay. | ⚠️ **REFUSE email + phone (#28)** — removed; honest "contact handled by your management team" line. **Country/nationality = a decision** (see §3). **Tag** "Repeat guest / New guest" = REAL, owner-scoped (this guest's prior stays on THIS owner's units). **"Message {first}" = the OPERATOR relay** (`owner.messages`, owner→operator staff — NEVER owner→guest). |
| 5 | **Timeline** (Booking received / Confirmed / Payment / Pre-arrival email / Check-in / Checked out / Payout released / Cancelled) | Real timestamps: `booked_at`, `payment_status`, `guest_checked_in_at`, `check_out`, `cancelled_at`, the statement's paid date. | ✅ **REAL (honest-omit the untracked).** Build from real timestamps/state; **OMIT the fabricated "Pre-arrival email"** (no notification backend — the current page already refuses it). Events not tracked with a timestamp are omitted, never invented (#39). |
| 6 | **Net-to-you dark hero** (count-up · netSub · payout badge + line) | The real net (breakdown) + the REAL `payoutStatus()` (derived from the statement the rows landed on). | ✅ **REAL → restyle** to the dark hero + the count-up (Alpine, presentational only — counts to the REAL net). Payout badge/line map from the existing real `payoutStatus()`. |
| 7 | **Booking details** (Confirmation · Source+dot · Channel · Rate plan · Booked on) | Real `reference`, `source` (+ dot), `booked_at`. | ✅ **REAL → restyle**, with two honest omissions: **Rate plan is NOT modelled per reservation** (no `rate_plan` column) → OMIT (never a fabricated "Standard"); **Channel** = derived from source ("Vyrobel direct" for direct; the OTA name for OTA) — real. |
| 8 | **Actions** (Download confirmation · View unit) | A per-booking confirmation PDF (does NOT yet exist — the existing "Booking Summary PDF" is STATEMENT-level, not per-booking); `owner.units.show`. | 🔨 **BUILD the confirmation PDF** (real, guest name-only #28, `CAP_EXPORTS`-gated, owner-scoped — the `OwnerBookingSummaryDocument` + `PdfService` pattern) — **a decision** (see §3). **View unit** = REAL (`owner.units.show`). |

---

## 2. Reuse map (anti-drift)

- **`x-owner.shell` + `--vy-*` tokens** — the warm-glass frame, Sora/Inter, `vyFadeUp` + the shimmer/drift orbs (the wireframe's background layers).
- **`OwnerLedgerAttribution::breakdown()`** — the ONE gross→net rule (shared with Bookings list, Unit Detail, the statement). Earnings reconcile to the cent (#17).
- **`OwnerCommissionRate::percent()`** — the ONE real rate read (18%, never a hardcoded 15%).
- **`OwnerPortalService::units()`** — the owner-scoped owned-unit set (the 404 boundary).
- **`FormatsOwnerData`** — `guestName` (#28), `sourceLabel`, `stageBadge`, `viewer`.
- **`GuestHistory` / owner-scoped stay count** — the real "Repeat guest / New guest" tag.
- **The relay** (`owner.messages`) — "Message {guest}" → owner↔operator, never owner→guest.
- **`PdfService` + `OwnerBookingSummaryDocument` pattern + `ExportAudit`** — the confirmation-PDF build (CAP_EXPORTS-gated, watermarked, name-only).
- **`OwnerBookingDetail::payoutStatus()`** — the REAL payout badge/line (kept).

---

## 3. ⚠️ DECISIONS (present for input)

1. **Confirmation PDF** — the "Download confirmation" action. No per-booking confirmation PDF exists (the current "Booking Summary" PDF is statement-level). **Recommended: BUILD a real per-booking confirmation PDF** (guest name-only #28, `CAP_EXPORTS`-gated, owner-scoped, the `OwnerBookingSummaryDocument`/`PdfService` pattern) — a clean, real deliverable. Alternatives: honest-omit the action, or point it at the statement PDF (weaker — it's not per-booking).
2. **Guest country / nationality** — the guest has a `nationality` field. It is NOT contact info (no bypass risk) but it IS guest PII. **Recommended: OMIT (strict NAME-only)** — consistent with the current page and the cleanest #28 reading. Alternative: show Country (the wireframe has the row) as a mild non-contact attribute.

(The email + phone refusal and the 18% commission fix are NON-NEGOTIABLE, not decisions.)

---

## 4. Build plan (single gate)

**⛔ GATE 1 — the exact-design page + real data + the two corrections + the confirmation PDF + consolidate.** Rebuild `booking-detail.blade` to the wireframe EXACTLY (header/avatar + reservation day-hero + gross→net earnings + guest card + timeline + dark count-up net hero + booking-details + actions — every gradient/shadow/radius/spacing/animation) on the shared owner chrome + `--vy-*`. Extend `OwnerBookingDetail` to compute the new sections (nightly rate, guest tag, timeline events, source dot, channel, the real commission-rate label). REFUSE email + phone (#28) + the honest line; "Message" → the operator relay; the earnings at the REAL 18% reconciling #17; BUILD the confirmation PDF (if approved). New tests (esp. **the owner surface never exposes guest email/phone**, the earnings reconcile at 18%, the PDF is name-only + gated). Consolidate docs (this audit → resolved, `DECISIONS.md` #78, `DIVERGENCE-REGISTER.md` Booking-detail → exact-design + guest-contact refused, `PROJECT-STATE.md` v23). Suite green + PHPStan L5 + Pint clean; `migrate:fresh --seed` clean; browser-verify. Per-gate commit + push.

## ✅ RESOLUTION (built 2026-07-30 · invariant #78 · single gate)

`/owner/bookings/{booking}` rebuilt to the wireframe EXACTLY on the already-correct owner-scoped backend, with the two corrections applied and the omitted sections wired real:

| Section | Result |
|---|---|
| Header | ✅ Restyled — breadcrumb (shell) + the identity row (avatar · name · the 4-state status pill · "{unit} · N nights · {source}"). |
| Reservation | ✅ Restyled — the day-hero (34px check-in/out + "from 3:00 PM"/"by 10:00 AM" + the dashed-connector nights pill) + the 4-col Unit/Guests/Nights/**Nightly rate** (real: room_revenue ÷ nights, else booked total ÷ nights). |
| Your earnings | ✅ REAL + **18% FIX** — the shared `OwnerLedgerAttribution::breakdown()` lines (Room revenue → Gross → **Commission · 18%** [`OwnerCommissionRate`, never 15%] → Net), reconciling to the cent (#17). Zero-value rows hidden presentationally; empty → honest "Not yet attributed". |
| Guest | ⚠️ **#28 REFUSED** — name + a REAL owner-scoped "New/Repeat guest · N stays" tag ONLY; email/phone/country never reach the view (proven by test); the honest "contact handled by your management team" line; **"Message your team" → the operator relay** (`owner.messages`). |
| Timeline | ✅ REAL — Booking received / Payment (real `payment_status`) / Checked-in (real timestamp) or upcoming Check-in / Checked-out / **Payout released** (the real `Payout.paid_at`) / Cancelled. The fabricated "pre-arrival email" is omitted (#39). |
| Net hero | ✅ Restyled — the dark hero + a **count-up** (Alpine, presentational, ends at the REAL net, respects `prefers-reduced-motion`) + the real payout badge/line. |
| Booking details | ✅ REAL — Confirmation / Source (+dot) / Channel / Booked on. **Rate plan OMITTED** (not modelled per reservation, #39). |
| Actions | 🔨 **BUILT the confirmation PDF** — `OwnerBookingConfirmationPdfController` + `OwnerBookingConfirmationDocument` + `pdfs.owner-booking-confirmation` on the `PdfService`/`ExportAudit` pattern: real letterhead + booking facts + the gross→net earnings (18%) + guest NAME only (#28). Owner-scoped (the SAME through-units 404 guard as the page) + `CAP_EXPORTS`-gated. Route `owner.bookings.confirmation` in the exports group. **View unit** → `owner.units.show`. |

**Decisions taken:** confirmation PDF = BUILD real (done); guest country = OMIT (strict name-only). Proven by `OwnerBookingsTest` (+3): the real 18% rate shows (never 15%); the PDF is owner-scoped + exports-gated; the PDF document renders the guest NAME only (contact never in the data) at the real rate, reconciling. Suite 1265/1265; PHPStan L5 (0); Pint clean; browser-verified (attributed + unattributed states).

## 5. Guardrails held throughout
🔒 **#28** owner-scoped + guest **NAME ONLY** — the email/phone refusal is the critical guard (a test proves the owner surface never exposes guest contact); "Message" = the operator relay, NEVER owner→guest · **#17** integer money, gross − commission − cleaning == net reconciling, the REAL 18% rate (never 15%) · **#20** the relay message is a controller route · **#42** the confirmation PDF is `CAP_EXPORTS`-gated · **#39** no fabrication — the pre-arrival-email timeline event, the un-modelled rate plan, and the cleaning-collected/paid lines that aren't in the ledger are all OMITTED, never invented.
