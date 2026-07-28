# Owner Reports + Insights — wireframe-fidelity audit

**Date:** 2026-07-28 · **Author:** build session · **Status:** PHASE 1 (read-only) complete — **awaiting founder input on the VAT + proposals decisions before the gated build.**

Founder brief: make the LIVE owner **Reports** (`/owner/reports`) and **Insights** (`/owner/insights`) match their decoded
wireframes (`Owner Reports.html`, `Owner Insights.html`) EXACTLY — design + behaviour — on REAL owner-scoped analytics,
HONEST where a stateful workflow isn't backed. Most of both is REAL analytics already built (the Batch-B owner layer).

Wireframes decoded via the dc-runtime bundle → `/tmp/wf_reports.txt`, `/tmp/wf_insights.txt`. The real analytics helpers
(all owner-scoped, reconciling): `PerformanceMetrics::forOwnerUnits` / `monthlyTrendForOwnerUnits` (occupancy · ADR ·
RevPAR · room revenue · `sourceNights` for by-channel), `BookingCadence::{leadTime,weekdayOccupancy,onTheBooks}ForOwnerUnits`,
`OwnerLedgerAttribution::{netPerCurrency,netDisplays,breakdown}` (the gross-to-net, reconciles to the statements #17),
`ManagementAgreement.commission_pct` (the REAL commission rate).

---

## INVESTIGATE — the four judgment points (findings)

1. **⚠️ COMMISSION RATE — wireframe shows "Commission · 15%"; the REAL rate is 18%** (the demo `ManagementAgreement.commission_pct`
   = 18.00; it's a per-owner agreement value). The `15%` is a hardcoded literal in the wireframe, not a placeholder. **RESOLUTION:
   render the REAL rate** from the owner's agreement (18% in the demo), never a hardcoded 15%. The commission *figure* is already
   real (`OwnerLedgerAttribution::breakdown`). — **Not a judgment call; fix.**

2. **⚠️ TAX & VAT SUMMARY — NOT MODELLED (Output VAT / Deductible input VAT / Net VAT due / quarterly / Filing status / Mark
   filed).** The wireframe hardcodes a Portuguese VAT setup ("Seaside Villas Lda · VAT PT 512 034 776 · 6% accommodation …") and a
   two-row filing workflow (VAT + income tax) with "Mark filed" / "Undo" buttons. The app has **no VAT computation** — only a
   *stored* `vat_number` string on `Owner` (for the statement letterhead); there is no VAT-rate model, no output/input-VAT base,
   no filing-status store. A fabricated "Net VAT due · payable to Autoridade Tributária" is a number a tax authority would be
   relied on for — **unacceptable to fake** (#39), even under the wireframe's "for information only" caption. **PROPOSAL: HONEST-DEFER
   the whole VAT block** — render it in the wireframe's design as an honest deferred state ("Vyrobel doesn't compute your VAT — your
   annual tax summary gives your accountant the real earnings figures; VAT is filed by you/your accountant", linking the real
   annual-tax PDF + Tax Center), with NO fabricated Output/Input/Net figure and NO "Mark filed" toggle. **← needs founder confirmation.**

3. **⚠️ INSIGHTS "WORTH A LOOK" — REAL derived (keep).** The wireframe's `insights` list ({{ i.title }}/{{ i.body }}/{{ i.cta }})
   under the subtitle *"What the numbers suggest — decisions stay yours."* These are genuinely computable from the real aggregates
   (e.g. "Saturdays run hottest at X%", "Your median lead time is N days", occupancy up/down). **RESOLUTION: build REAL DERIVED
   insights** (computed observations from the owner's own numbers; honest, decisions-stay-yours) — never fabricated advice.

4. **⚠️ INSIGHTS "RATE PROPOSALS waiting" / "proposed −8% October" / "Review proposals" — FABRICATED (refuse).** The booking-pace
   CTA ("The proposed −8% October rate targets this gap — review it") + the dark "Turn insight into rate · 2 rate proposals waiting ·
   Review proposals" callout assert a **rate-proposal entity that does not exist** (already REFUSED in the My Units audit — no owner
   rate-proposal/approval model). **PROPOSAL: HONEST** — no fake proposal count / no fake approval workflow. Replace with an honest
   "Turn insight into rate → open your Rates" link to the real `owner.rates` page (owner CAN adjust their own rates there), with NO
   "N proposals waiting" claim. **← needs founder confirmation.**

5. **Email to me / notifications — Email-a-report is NOT wired** (no report Mailable exists). **RESOLUTION: honest-defer** ("Email to
   me" → an honest "Soon", never a fake send). The Reports **Notifications** panel → reuse the REAL `NotificationFeed` (the same
   self-scoped feed the bell uses) or an honest empty state — never fabricated rows.

---

## PAGE A — REPORTS (`/owner/reports`)

Live today: a MINIMAL page — 2 KPIs (Net earnings / Units) + range pills + View-statements/Tax-center links + a per-unit table
(nights/occupancy/net). The wireframe is far richer; nearly all of it is REAL analytics to wire.

| Wireframe element | Classification | Notes |
|---|---|---|
| Year selector + per-year totals | ✅ REAL-NOW | `resolveRange()` exists; totals via `PerformanceMetrics` + `OwnerLedgerAttribution`. |
| KPIs — Net earned / Avg occupancy / Nights sold | ✅ REAL-NOW | net = `OwnerLedgerAttribution::netDisplays`; occupancy + nights = the owner `MetricSet`. |
| Net-earnings-by-month chart | ✅ REAL-NOW | `monthlyTrendForOwnerUnits` (room revenue) + the ledger net per month. |
| Performance by unit (ADR/RevPAR/nights/occ/net/share) | ✅ REAL-NOW | per-unit `MetricSet` (`adrMinor`/`revparMinor`/occ/nights) + `netByUnit`. |
| Booking sources by channel | ✅ REAL-NOW | `MetricSet.sourceNights` (the real `reservations.source` enum — Direct/OTA/… , not an OTA brand #39). |
| Gross-to-net breakdown | ✅ REAL-NOW (reconciles #17) | `OwnerLedgerAttribution::breakdown` (credits − deductions == net). **Commission rate = 18% (real), not 15%.** |
| Payout summary | ✅ REAL-NOW | the owner's real `Payout` rows (paid / pending), owner-scoped. |
| Documents list | ✅ REAL-NOW | the real statement/booking/remittance PDFs (same as the Documents page), CAP_EXPORTS-gated. |
| Exports — PDF / CSV / Excel / all-statements | ✅ REAL-NOW (partial) | statement/booking/remittance/annual PDFs + the statement-history CSV already exist, CAP_EXPORTS-gated. A dedicated "Excel" = the CSV (honest label). |
| Export → "Email to me" | 🔒 NOT-BUILT → honest-defer | no report Mailable — an honest "Soon", never a fake send. |
| Tax & VAT summary + "Mark filed" | 🔒 NOT-MODELLED → honest-defer | see investigate #2 — no VAT computation / filing store; honest deferred block, no fabricated figure. |
| Notifications panel | ✅ REAL-NOW (reuse) / honest-empty | reuse `NotificationFeed` (self-scoped); honest empty when none. |

## PAGE B — INSIGHTS (`/owner/insights`)

Live today: fairly complete — 4 KPIs, a derived observation, room-revenue-by-month, lead-time distribution, day-of-week occupancy.
The wireframe reshapes to a 2-column layout + adds vs-last-year comparisons + worth-a-look + the (fabricated) proposals.

| Wireframe element | Classification | Notes |
|---|---|---|
| KPIs — Occupancy YTD / Avg nightly / Next 90 days / Median lead | ✅ REAL-NOW | already wired (owner `MetricSet` + `BookingCadence`). |
| Occupancy-by-month (this year vs **last year** + on-the-books) | ✅ REAL-NOW / 📐 honest-omit LY | this-year + on-the-books are real; **last-year has no data in the demo → honest-omit the LY series** (never a fabricated comparison, #39). |
| Booking pace (next 3 months vs LY) | ✅ REAL-NOW / 📐 honest-omit LY | on-the-books forward is real (`onTheBooks`); the vs-LY delta omitted where no LY. |
| Rate performance per villa (occupancy / ADR / Δ vs LY) | ✅ REAL-NOW / 📐 honest-omit Δ | occupancy + ADR are real per-unit; the Δ-vs-LY omitted where no LY. |
| When guests book (lead-time distribution) | ✅ REAL-NOW | `BookingCadence::leadTime…` (already wired). |
| Nights that fill first (occupancy by day-of-week) | ✅ REAL-NOW | `BookingCadence::weekdayOccupancy…`; the caption ("Saturdays run hottest at X%") becomes a REAL derived caption. |
| "Worth a look" insights | ✅ REAL-NOW (derived) | see investigate #3 — real computed observations, decisions-stay-yours. |
| "Rate proposals waiting" / "proposed −8%" / "Review proposals" | 🚫 FABRICATED → honest | see investigate #4 — no proposal entity; replace with an honest link to the real Rates page, no "N waiting" claim. |

---

## BUILD PLAN (gated)

**⛔ GATE 1 — Reports.** Rebuild `reports.blade.php` (page-scoped `.vy-rep-*`) + extend `OwnerReports` to the wireframe: the
year selector, the 3 KPIs, the net-by-month chart, per-unit performance (ADR/RevPAR/nights/occ/net/share), by-channel
(`sourceNights`), the **gross-to-net** (`OwnerLedgerAttribution::breakdown`, **real 18% commission label**, reconciles #17), the
payout summary, the real Documents list, the real exports (CAP_EXPORTS-gated; "Email to me" honest-Soon), the Notifications
panel (real feed), and the **VAT block as an honest deferred state** (no fabricated figure, no "Mark filed"). Tests + browser +
per-gate commit.

**⛔ GATE 2 — Insights.** Rebuild `insights.blade.php` + extend `OwnerInsights` to the 2-column wireframe: KPIs, occupancy-by-month
(LY honest-omitted), booking-pace, rate-performance, lead-time, day-of-week (+ real derived caption), **"Worth a look" real derived
insights**, and the proposals callout **as an honest "adjust your rates" link** (no fake proposal count/workflow). Consolidate docs
(this file → resolved, DECISIONS #61, DIVERGENCE rows for both, PROJECT-STATE, CLAUDE). Tests + browser + commit + PUSH.

### Guards (both)
🔒 #28 owner-scoped · #17 reconcile (gross-to-net, **real commission rate**) · #20 controller writes (only if a real stateful store
is added — none is, per the honest-defer decisions) · exports CAP_EXPORTS-gated · #39 nothing fabricated (**no fake VAT figure /
filing / rate proposal / advice**). Reuse the Batch-B analytics (anti-drift). DISTURB NOTHING existing.
