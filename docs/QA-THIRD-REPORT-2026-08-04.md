# Vyrobel — QA Third (Final) Pass · 2026-08-04

**Scope of this pass (two parts + a hard STOP gate):**
1. **PART 1 — drive the "resume-point" mutating operations** the deep pass had marked as not-yet-deeply-driven. For each: confirm it **persists** (reload / DB re-read), **reconciles** (#17 where money moves), stays **isolated** (tenant / realm / owner scope), respects the **bounded guards** (#27/#29/#30/#68) and **privacy** rules (#28/#33), and honours the **honest deferrals** (test-mode / no real money / OTA / SMS).
2. **PART 2 — record REAL Playwright VIDEO + TRACE of the motion layer** (the deep pass's one honest limitation: it verified motion only by CSS + computed-style + reduced-motion *emulation*, never a frame-timed recording).

**STOP-GATE result:** ✅ **Playwright MCP CAN record both video and trace.** Video requires a self-contained `recordVideo` browser context (login → page → motion → close); trace runs on any context via `tracing.start/stop`. Both were exercised — **7 `.webm` videos + 7 trace `.zip`s + 28 timed frames (67 MB)** live in `.playwright-mcp/qa3/`. The pass proceeded.

**Method & honesty.** Every operation was driven against the **real controller routes** (the same write path / certified services / route-middleware guards the UI posts to) from a **logged-in Playwright browser session** (real CSRF, real guards, PRG redirects), then **verified by a DB re-read** and cross-checked against the code. Where a browser drive alone couldn't prove a claim (e.g. the inbox retry needed a genuinely-failed deferred send; the #68 net-drop needed a controlled pending expense), the precondition was set up honestly via the certified service under super-admin context and the **mutation itself** was driven through the real route. Read-only on application code; the deliverable is this report + the recorded artifacts. All test writes are **demo data cleared by `migrate:fresh --seed`** (listed at the end); refused attempts wrote nothing.

---

## Executive summary

**Vyrobel is QA-COMPLETE. This third pass drove every previously-deferred mutating operation to persistence + reconciliation + isolation, and recorded real video/trace of the motion layer across all four realms. It found ZERO new defects.**

- **PART 1 — 8 mutating-operation areas, all PASS.** Calendar drag-reschedule · maintenance (OOS → return-to-sale → cost-to-owner → CSV-injection-safe) · team-schedule (move / publish / leave-conflict) · inbox (send / assign-note / link / **real** retry) · group-booking (5-step atomic) · rates→folio + guest-CRM consent gate · owner (calendar / **#68 expense-approval** / payout-method / add-unit) · super-admin (plan-gate / suspend / encrypted integration). Money reconciled **to the cent** in every path that moves money (#17); every bounded guard held (#27 over-refund, #29 re-terminate, #30 route-perms, #68 approval, no-double-booking, no-double-charge); every privacy rule held (#28 owner name-only / operator full contact; PII encrypted at rest; `anonymize()` covers every free-text-about-a-guest store); every honest deferral stayed honest (deferred channels fail visibly, never fake a send; the payout engine stays test-mode; no fabricated figures).
- **PART 2 — motion recorded on video, PASS.** The `vyFadeUp` entrance genuinely staggers (KPIs + nav fade from dimmed → full opacity over ~200 ms); count-ups genuinely animate `0 → value` and **settle** (a t=1.7 s frame is pixel-identical to t=0.65 s — no perpetual motion); **prefers-reduced-motion is honoured on video** (first paint shows full opacity + final values, no fade, no count-from-zero); modals are **perfectly centered** and popovers **correctly anchored** (no `x-show` mis-center); each realm carries its own coherent theme.
- **No new findings.** One candidate (F3-DOC-1, a stale storefront demo URL) was **investigated and retracted** — the authoritative doc (`CLAUDE.md`) already carries the corrected `/book/seaside-demo`; the 404 came from a stale system-reminder snapshot, not the live file.
- **Deep coverage is COMPLETE.** The four gap areas the deep pass opened (mutating ops driven to persistence · second-property isolation · validation/guards · motion) are now all closed, with motion's one caveat (no recorded video) resolved by this pass.

**Verdict: Vyrobel is QA-complete. All prior findings (7 from the first pass + D2-OBS-1 from the deep pass) remain resolved; this pass adds none. The remaining open items are the founder's external-infra go-live flips (Stripe live keys, payout-engine enable, OAuth keys, real tax rates, email DNS) + deployment — none of which are QA defects.**

---

## Coverage matrix — PART 1 (mutating operations)

| # | Area | Operation(s) driven | Persist | Reconcile #17 | Guards / isolation / privacy | Verdict |
|---|------|--------------------|:---:|:---:|------|:---:|
| 1a | Front-desk calendar | Drag-reschedule R-… 12-18→12-20 (server `Y-m-d`) | ✅ exact dates | n/a | No day-roll; via certified `AssignmentService` (lock + no-overbook + paused/OOS guard) | **PASS** |
| 1b | Maintenance | Create OOS ticket → start → resolve(return-to-sale) → bulk-export | ✅ | ✅ €84 cost → owner ledger | Unit A1 OOS→bookable; **CSV-injection-safe** (`=HYPERLINK` → `'=HYPERLINK`); auto-charge per #68 | **PASS** |
| 1c | Team schedule | Assign → **move** (LOCAL date) → grant-leave → assign-on-leave → publish | ✅ exact 08-08 | n/a | No day-roll; **leave-conflict refused** (0 rows written); publish persisted | **PASS** |
| 1d | Unified inbox | Reply(send) → assign+note → link-reservation → **retry failed** | ✅ | n/a | Note under `anonymize()` (Guest.php:294); deferred retry **fails again honestly** (no fake "delivered"); one shared merge path | **PASS** |
| 1e | Group booking | 5-step atomic block: 2 units, 10% discount, 25% deposit, rooming | ✅ | ✅ charges 72992, deposit 18248 = 25% exact | Capacity re-derived; discount = real negative lines; deposit capped at balance; rooming lead name-only (#28); **atomic — re-post refused, 0 partial writes** | **PASS** |
| 1f | Rates + Guest-CRM | Rate €100→€333 (flows to folio); guest edit + consent toggle | ✅ | ✅ derived price = folio source | Certified `RateAndRestrictionService`; edit audited; **consent OFF→marketing blocked / ON→re-opened** via one `GuestMarketing::canReceive` gate | **PASS** |
| 1g | Owner portal | Calendar block/release · **#68 expense approve** · payout-method · add-unit | ✅ | ✅ approve → net −5000 exact; re-approve = no double-charge | Block via certified path (exact dates); IBAN **encrypted at rest**; add-unit → operator round-trip; all CAP_ACTIONS-gated + owner-scoped | **PASS** |
| 1h | Super-admin | Plan feature toggle · customer suspend/reactivate · integration creds | ✅ | price major→minor #17 | Plan edit **flips `planAllows(channels)` false→true** (real gate); suspend honest reversible flag; integration secret **encrypted at rest** (write-only); 2FA-mandatory | **PASS** |

## Coverage matrix — PART 2 (motion, recorded video + trace)

| Realm / element | What the frames/video show | Verdict |
|-----------------|---------------------------|:---:|
| Operator dashboard entrance | KPI cards + nav **dimmed at t=0 → full opacity by t=220 ms** (staggered `vyFadeUp`) | **PASS** |
| Operator count-ups | Revenue €1,537→€8,170→€8,374 · work-orders 6→30→3 — animate `0→value` and **settle** (t=1700 ≡ t=650, no jitter) | **PASS** |
| **Reduced-motion (on video)** | `matchMedia=reduce`; sample opacity=1 / transform=none at first paint; frame shows **final values immediately**, no fade | **PASS** |
| Guest storefront + trips | Hero/search/rooms entrance (honest "pick dates to see prices"); trips count-up hero + countdown pills + **distinct payment states** (Paid-in-full vs €184.50-due) | **PASS** |
| Owner overview + statement | Fade-in → settled **real reconciling data** (€885.60 Sep +23% · €3,690 YTD · Next-payout honestly "Paused") | **PASS** |
| Admin console | Distinct **dark-navy theme**; 2FA-gated (computed TOTP challenge); real platform data (Cust 1 · €49/mo · Properties 2) | **PASS** |
| Popover (notification bell) | **Correctly anchored under the bell** (not mis-centered); shows real notifications generated by this pass's own mutations | **PASS** |
| Modal (walk-in) | **Perfectly centered** + backdrop dim; real "Free tonight" units incl. A1 (back in list = 1b return-to-sale) — **no `x-show` mis-center** | **PASS** |
| Drag feedback | Functionally verified in 1a (reschedule) + 1c (shift move); traces capture the boards | **PASS** |

**Artifacts:** `.playwright-mcp/qa3/video/*.webm` (7) · `.playwright-mcp/qa3/trace/*.zip` (7, with screencast frames) · `.playwright-mcp/qa3/frames/*.png` (28).

---

## Findings by severity

**None.** No CRITICAL, HIGH, MEDIUM, LOW, or TRIVIAL defect was found in this pass.

One candidate was raised and then **RETRACTED after investigation**:

| ID | Candidate | Investigation → outcome |
|----|-----------|------------------------|
| F3-DOC-1 | Demo storefront URL `/book/vyrobel-demo` 404s | The route binds `{property}` to a **property** slug (`seaside-demo` / `vista-demo`); `vyrobel-demo` is the **org** slug and 404s **by design**. The live `CLAUDE.md` (line 56) **already reads `/book/seaside-demo`** with that exact warning — F-DOC-1 (first pass, `1f47ee3`) fixed it. The `/book/vyrobel-demo` I first used came from a **stale system-reminder snapshot**, not the file. Remaining refs are append-only *history* (the prior QA report + `DECISIONS.md:5401` describing the fix). Storefront works. **Non-finding — retracted.** |

*(This is exactly the "verify-before-report / under-report confidence" discipline: a recalled/snapshot fact that no longer matched the live file was checked against the source before being written up.)*

---

## Honesty & security confirmation (re-verified this pass, on real mutations)

- **#17 money reconciles to the cent** — group-booking master folio (charges 72992, discount −5600 = 10%, deposit 18248 = 25%, balance = charges − deposit), maintenance cost → owner ledger (€84 debit, one line), #68 approval (net credits 598000 − debits 146040 = 451960; −5000 exact), rates → derived folio price. Integer minor units throughout; no float drift.
- **#68 expense approval changes the deduction model correctly** — a pending expense charges **nothing** (net unchanged); approval posts the debit (net drops, reconciles); re-approve is **idempotent** (still one ledger line, no double-charge).
- **Bounded guards held** — no double-booking (certified `AssignmentService` on drag + block + walk-in), no re-termination (#29, prior pass), no over-refund (#27, prior pass), no double-charge (payment idempotency + #68 re-approve), route-permission gating (#30, plan/suspend/integration 2FA + CAP_ACTIONS owner writes).
- **Privacy held** — owner surfaces are **name-only** (organiser contact JSON = name only; email/phone stay encrypted on the Guest); operator sees full contact only where allowed; **PII encrypted at rest** re-confirmed on the payout IBAN and the platform integration secret (raw DB = ciphertext); `Guest::anonymize()` audited to cover **every** free-text-about-a-guest store (message bodies + sender labels, thread identity/subject, thread notes, guest notes, reservation guest-notes + arrival window, group-organiser name).
- **Deferrals stayed honest** — a deferred WhatsApp send **fails visibly** ("not configured — deferred skeleton") and its retry **fails again**, never a fabricated "delivered" (#24/#39); the owner "Next payout" shows a real **"Paused"** tax-hold, not an invented date; the payout engine stays **test-mode**; the storefront shows honest "pick dates to see prices" (no fabricated rates, #39); marketing consent genuinely gates the marketing send path (transactional email correctly not gated).
- **Multi-realm isolation held** — operator (`web`) → owner (`web`, owner-scope) → guest (`guest` guard) → platform-admin (`admin` guard, 2FA-mandatory) were each entered via their own real login; the admin's first sign-in was **forced through mandatory 2FA enrollment** (hardened behaviour, confirmed).

---

## FINAL merged backlog (first pass + deep pass + this pass)

| ID | Sev | Source | Status |
|----|-----|--------|--------|
| F-GUEST-1 | HIGH | 1st pass | ✅ **RESOLVED** (`1e564df`) — pay-on-arrival leaves balance DUE, no fabricated payment. **Re-confirmed this pass** (guest trips shows "€184.50 due" vs "Paid in full"). |
| F-OP-1 | MEDIUM | 1st pass | ✅ **RESOLVED** (`e492537`) — Front Desk + Calendar default to **today** via shared `BoardDate`. **Re-confirmed this pass** (dashboard opens on Tue 4 Aug 2026). |
| F-OP-2 | LOW | 1st pass | ✅ **RESOLVED** (`9b8828d`) — Channels card gated on `planAllows`. **Re-confirmed** (super-admin plan-gate toggle flips the gate live). |
| F-OP-3 | LOW | 1st pass | ✅ **RESOLVED** (`9b8828d`) — topbar chip from real authed user. |
| F-OP-4 | TRIVIAL | 1st pass | ✅ **RESOLVED** (`1f47ee3`) — email preview uses a real-total booking. |
| F-OP-5 | LOW | 1st pass | ✅ **RESOLVED** (`9b8828d`) — stale move-room note corrected. |
| F-DOC-1 | TRIVIAL | 1st pass | ✅ **RESOLVED** (`1f47ee3`) — `/book/seaside-demo`. **Re-confirmed this pass** (CLAUDE.md correct; F3-DOC-1 candidate retracted). |
| D2-OBS-1 | LOW | deep pass | ✅ **RESOLVED** (`b2e1d7b`) — distinct `payments.method` column; Cash / Bank-transfer stored + surfaced distinctly, money untouched. **Re-confirmed** (`Payment::methodLabel()` reads the real column). |
| **(this pass)** | — | 3rd pass | **No new findings.** 8 mutating-op areas + full motion layer: all PASS. |

**Open QA defects: 0.** Remaining work is external-infra go-live (founder's flips) + deployment, not QA.

---

## Demo mutations left by this pass (all cleared by `migrate:fresh --seed`)

Group-booking "QA3 Group Block" (+ member reservations + rooming guests); rate €333 on the Double for 2026-10-20; a resolved maintenance ticket + its €84 owner expense; team-schedule shifts + a leave row + a week publication; an owner block (released) + a pending unit-request + a payout method + an approved #68 expense; the platform admin now has **2FA enrolled** and Booking.com demo credentials set. None are relied on by any test; all reset on re-seed.
