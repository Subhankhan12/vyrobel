# Vyrobel — Deep-Coverage QA Report (follow-up)

**Date:** 2026-08-04 · **Base URL:** `http://127.0.0.1:8901` · **DB:** `migrate:fresh --seed` (seaside-demo = 40 res, vista-demo = 0 res, org vyrobel-demo).
**Method:** Real-browser automation via the **Playwright MCP** (navigate/click/type/submit/evaluate + `emulateMedia` for reduced-motion), each mutation confirmed by a direct DB re-read. Code read only to confirm/classify a finding.
**Scope:** exactly the four coverage gaps the first report's Appendix ("what was NOT covered") listed — deep mutating ops (A), 2nd property + switcher (B), invalid-input validation (C), animation/motion (D).
**Demo mutations created (all cleared by `migrate:fresh --seed`):** a €50 on-desk collect + check-in + check-out on R-8NIO75ZQ; a room move on R-VEE48AFM; a housekeeping status change; the GDPR anonymize of "Demo Traveller"; a property switch to vista. Refused attempts (over-refund, re-terminate, invalid checkout) wrote nothing.

> **Scope-honesty note.** Area A lists ~30 mutating operations. This pass drove the **8 highest-risk** to a real persisted mutation with a DB re-read + invariant check (money reconciliation #17, the bounded guards #27/#29, GDPR-JSON-PII #28, tenant/overbook safety). The remaining Area-A operations (calendar drag-reschedule, maintenance create/bulk, team-schedule drag, inbox send, group-booking 5-step, rates edit, owner calendar/expenses/payout/add-unit, super-admin plan/suspend/integration) were **not driven this pass** — they are listed as a **resume point** in the coverage matrix, not claimed as PASS. Areas B, C, D were covered in full for their key surfaces.

---

## 1. Executive summary

**Result: zero new genuine defects.** Every operation driven to a real mutation **persisted, reconciled, stayed isolated, and respected its bounded guard**. Two things that looked like bugs mid-investigation both turned out to be **measurement/mechanics artifacts, not app defects** (verified before reporting): the front-desk "Check in" button's `undefined` form action (Alpine binds the real id on modal-open) and the dashboard "not re-scoping" (a regex matched the switcher's dropdown text). The critical invariants hold under real mutation — this pass **raises confidence**, it doesn't add to the backlog.

The first report's 7 findings were already fixed in the fix pass (`1e564df`…`1f47ee3`); **the merged backlog below carries only one new LOW observation.**

| Area | Coverage | Result |
|---|---|---|
| A — deep mutating ops | 8 criticals driven (of ~30 listed) | all **PASS**; rest = resume point |
| B — 2nd property + switcher | full (reservations + dashboard + storefront) | all **PASS** (#38 isolation holds) |
| C — invalid-input validation | key money/booking forms | all **PASS** (no 500 / no silent-accept / no fabricated-success) |
| D — animation/motion | reduced-motion gate + keyframe survey + modal function | **PASS** (with honest video limitation) |

### Findings
| Severity | Count | IDs |
|---|---|---|
| CRITICAL / HIGH / MEDIUM | 0 | — |
| LOW | 1 | D2-OBS-1 (payment method label stored generically) |
| Verified-correct (not bugs) | 2 | check-in modal "undefined", dashboard re-scope |

---

## 2. Coverage matrix

### A — Deep mutating operations
| Operation | Role | Driven → Result |
|---|---|---|
| On-desk **Collect** (front desk) | Hotel Mgr | €50 partial → "Payment recorded", balance €184.50→**€134.50** (reconciles #17); DB payment 5000 **integer-minor** succeeded. **PASS** |
| **Check-in** | Hotel Mgr | Alpine modal-confirm → status confirmed→**checked_in** persisted. **PASS** |
| **Check-out** | Hotel Mgr | modal → checked_in→**checked_out** persisted. **PASS** |
| **Re-termination guard #29** | Hotel Mgr | crafted `POST /cancel` on terminal res → status stayed **checked_out**, cancelled_at null, crafted reason not written. **PASS (refused)** |
| **Over-refund guard #27** | Property Owner | crafted €999 refund on €664.20 payment → **0 refund rows**, nothing written (RefundExceedsPaymentException). **PASS (refused)** |
| **GDPR anonymize** (incl. JSON PII) | Property Owner | UI "type ERASE" → DB: name/email/phone/city/nationality/language NULL, **`preferences` JSON col = NULL (raw-verified)**, external_refs null, password destroyed, marketing_consent false. **PASS — no JSON-PII missed** |
| **Room-move** (F-OP-5) | Property Owner | detail Move (1 free same-type unit offered) → line unit_id moved, **1** active line on target (**no overbook**). **PASS — persists safely** |
| **Housekeeping status** | Property Owner | Start → task dirty→**in_progress** persisted (per-card buttons + drag share `/status`). **PASS** |
| Reservation cancel/no-show controls on a terminal res | — | correctly **hidden** (UI-level #29). **PASS** |
| Refund screen for Hotel Manager | Hotel Mgr | **403** (payments.refund gated #30). **Correct** |
| Calendar drag-reschedule · maintenance create/bulk/resolve-OOS · team-schedule drag/publish · inbox send/assign-note/link/retry · group-booking 5-step · rates edit · guest-CRM edit · owner calendar/expenses/payout/add-unit · super-admin plan/suspend/integration | — | **NOT DRIVEN this pass — resume point** |

### B — Second property + switcher (#38)
| Check | Result |
|---|---|
| Switcher seaside→vista (`POST /app/property/switch`) | **PASS** — persisted the switch |
| Reservations list re-scopes | **PASS** — "0 reservations" (vista's real count) |
| Dashboard re-scopes | **PASS** — subtitle "Demo Vista Apartments", 0 of 3 units |
| Cross-property isolation | **PASS** — no seaside guest name/reservation leaked into vista's views |
| Vista storefront `/book/vista-demo` | **PASS** — shows vista's own rooms (Apartments/Studio, not seaside's Double Room), no leak |

### C — Invalid-input validation
| Form / input | Result |
|---|---|
| Storefront reserve — **bad email** (`not-an-email`) | **PASS** — redirect-back, no 500, **no booking created** (count stayed 40) |
| Storefront reserve — **reversed dates** (check_out < check_in) | **PASS** — redirect-back, no 500, no booking, no "Bad Input" guest |
| Refund — **over-amount** (€999 > €664.20) | **PASS** — refused (#27), nothing written |
| Client-side | HTML5 `required` + `type=email` gate the happy path; server validation is the real guard (proven above) |
| New-reservation wizard / group-booking / rates edit invalid paths | resume point (server rule `check_out after:check_in` confirmed in `BookingEngineController`; not UI-driven this pass) |

### D — Animation / motion
| Check | Result |
|---|---|
| `prefers-reduced-motion` CSS present | **PASS** — 20 files; public/guest gate is comprehensive (`*,*::before,*::after{animation/transition:none!important}; .vy-fade{opacity:1}`) |
| Reduced-motion **emulated** on the storefront | **PASS** — normal: `.vy-fade` opacity **0** (vyFadeUp runs); reduced: `matchesReduce=true` + `.vy-fade` opacity **1** (content forced visible, motion gated) |
| Keyframe inventory | present: vyFadeUp, vyDrift/adDrift (orbs), vyShimmer, vyModalIn, vyMenuIn, vyCalToastIn (toast), vyTlPulse (timeline), vyPing, vySwapNext/Prev |
| Modal transitions (collect / check-in) | **PASS** — opened + interactable in Area A (no x-show mis-centering) |
| Count-up 0→value animation | keyframe/JS present; **not frame-timed** (see limitation) |

---

## 3. Findings

**D2-OBS-1 (LOW, observation) — ✅ RESOLVED (2026-08-04, `add_method_to_payments`).** The on-desk Collect previously left the real Cash/Bank choice only inside `metadata` while the queryable `gateway` read a generic `"manual"`. Fixed with a distinct, additive, nullable **`payments.method`** column (backfilled from `metadata->method`), populated by `FolioService::recordPayment` from the operator's choice; a `Payment::methodLabel()` helper renders it, surfaced on the reservation-detail folio ledger ("Received · Cash" / "· Bank transfer"). **Label only — the amount, integer-minor storage, #17 reconciliation, and idempotency are untouched**; the card path stays the honest test-mode Stripe seam. Browser + DB verified: Cash→`cash`, Bank transfer→`bank_transfer` (distinct, not `manual`), money unchanged, folio reconciles. Regression test added.

**Verified-correct (NOT findings), documented so they aren't re-flagged:**
- **Front-desk "Check in" `undefined` form action** — the visible button is an Alpine trigger (`openCheckin`) that opens a confirm modal; the modal form binds the real id via `x-bind:action` on open (`/app/front-desk/<real-id>/check-in`). The pre-open `undefined` is inert template state. Check-in persists correctly when the modal is confirmed.
- **Dashboard "not re-scoping" to vista** — a false alarm from a regex that matched the switcher dropdown's option text; the dashboard header/subtitle + KPIs **do** re-scope to "Demo Vista Apartments".

---

## 4. Data-honesty + security confirmation (under real mutation)

- **#17 reconciliation** — the €50 collect dropped the balance to exactly €134.50 (integer minor throughout); no float money seen.
- **#27 over-refund** — a €999 refund on a €664.20 payment wrote **zero** rows (guard fires before any write).
- **#29 re-termination** — a crafted cancel on a checked-out reservation changed nothing (no status flip, no junk cancellation data).
- **#28 privacy / GDPR** — anonymize cleared **all** PII including the `preferences` **JSON column** (raw-DB-verified) and destroyed the portal account; the known "JSON-PII missed by anonymize" class does **not** apply (the code also scrubs message bodies, notes, `notes_guest`, stored document files, and the `groups.contact->name` JSON path).
- **#30 RBAC** — Hotel Manager is correctly 403'd from the refund screen; Property Owner can refund.
- **#38 tenant/property isolation** — switching to vista re-scopes every checked surface with **no** seaside data leak; the vista storefront shows only vista's rooms.
- **No overbooking** — the room move left exactly one active line on the target unit.
- **Honest deferrals intact** — the on-desk card terminal is labelled the deferred Stripe seam; no real money/OTA/SMS was moved.

---

## 5. Motion summary

The design's motion system is real and reduced-motion-safe: a broad keyframe set (fade-up, ambient orbs, shimmer, modal, menu, toast, timeline pulse, swap transitions) and a comprehensive `prefers-reduced-motion` gate that both disables animation/transition globally **and** forces `.vy-fade` content visible (so nothing is stuck invisible). Emulation confirmed the gate flips behavior correctly. Modals open and function (no x-show mis-centering).

**Honest limitation:** a true Playwright **video/trace was not recorded** through the MCP. Motion was verified by CSS inspection + computed-style probing + `emulateMedia` reduced-motion emulation (more rigorous than eyeballing a video for the gate) + functional modal interaction. The **count-up 0→value** animation was not visually frame-timed (its keyframe/JS is present). A dedicated video pass remains the way to sign off subjective smoothness/jank across every page.

---

## 6. Prioritized fix backlog (merged with the first report)

**From the first report (`docs/QA-FULL-REPORT-2026-08-04.md`):** all 7 findings (F-GUEST-1 … F-DOC-1) were **RESOLVED** in the fix pass (`1e564df`, `e492537`, `9b8828d`, `1f47ee3`, `8b06081`). **No open items remain.**

**From this pass:**
| # | Sev | ID | Item | Status |
|---|---|---|---|---|
| 1 | LOW | D2-OBS-1 | On-desk collect stored cash/bank as a generic `manual` label | ✅ **RESOLVED** — distinct `payments.method` column (backfilled), populated from the operator's choice, surfaced on the folio ledger; money untouched. Browser+DB verified; regression test added. |

**No open items remain.**

**Recommended next QA (the resume point — not defects, just uncovered):** drive the remaining Area-A mutations — calendar drag-reschedule (LOCAL-date/no-overbook), maintenance create/assign/**bulk** (CSV-injection-safe + honest done/skipped counts) → resolve-OOS-release, team-schedule drag/publish (LOCAL date/time), inbox send/assign-note(anonymize)/link/retry, group-booking 5-step atomic (capacity/discount-line/deposit-cap/rooming), rates grid edit, owner calendar-block/**expenses-approve #68 (net-drop reconcile)**/payout-method/add-unit round-trip, super-admin plan-edit-persist/suspend-flag/integration-encrypt; plus a dedicated **motion video pass** per realm.
