# Operator Completeness Audit (PHASE 1 — read-only)

**Scope:** every BUILT operator page vs its FULL decoded wireframe spec. Method: all 42 operator wireframes decoded from their dc-runtime bundles to readable HTML (`/tmp/wf-op/`), then **9 parallel adversarial audit agents** read each page's component + blade + controller + service + model + tests, classifying every gap. Route→handler map + controller `authorize()` checks verified directly.
**Date:** 2026-07-31. Status: PHASE 1 complete — awaiting founder approval of scope + build order before PHASE 2.

Classes: **MISSING-DESIGN** (element/section absent) · **MISSING-BACKEND** (present but static/non-persisting) · **MISSING-FEATURE** (capability un-built) · **NON-OPERATIONAL** (renders but core function incomplete) · **DEFERRAL** (externally blocked — not a gap) · **REAL** (already-built — preserve).

---

## The honest headline

The operator realm is **far more complete than "every page needs building" implies.** Of ~38 built surfaces:

- **~20 are fully-operational** on certified engines with strong honesty discipline (Owners · Owner Detail · Payout Run · Billing · Refund · Rates · Property Settings · Insights · Portfolio · Guests · Guest Profile · Dashboard · Notifications · Notification Preferences · Email Templates · Email Editor · Roles · Search · Schedule PDF · Maintenance Costs · Maintenance Settings). Several are **richer** than their wireframes.
- **~16 are partial** — real core, with specific real gaps (a built-but-unwired action, an absent section a real engine could back, a collected-but-unpersisted field).
- **1 is non-operational**: **Unit Requests** (read-only; no approve/decline/create workflow exists).
- **1 cross-cutting SECURITY cluster** (below) — the single most important finding, found by reading (echoing the #26–#30 lesson: green tests were necessary, not sufficient).

No fabrication was found anywhere — every honest deferral (OTA/Channex, SMS/Push, live charge, IBAN verification, presence) is correctly surfaced, never faked. That discipline must be preserved.

---

## ⛔ GATE 0 — the SECURITY cluster (highest priority, small, behaviour-changing)

**Finding (VERIFIED at the route + controller level):** a set of operator **write** routes carry NO `can:` middleware, and their controllers (`MaintenanceActionController`, `HousekeepingActionController`, `FrontDeskActionController`) call **no `authorize()`** — only tenant scope + a paused()/read-only check. Their permission-holding siblings ARE gated, so this is an inconsistent **#20/#30 privilege-within-org** gap: a back-office role that lacks the permission (and is correctly hidden from the control in the UI, `$canManage`) can still drive the write with a crafted POST.

| Route | Gated? | Sibling that IS gated | Risk |
|---|---|---|---|
| `maintenance.store/assign/start/resolve/cancel/parts/photos.*` | ❌ none | `maintenance.costs/settings` → `can:maintenance.manage` | **`resolve` posts a REAL deductible owner expense** (money-adjacent) reachable by Front Desk / Reservation Agent / Accountant |
| `housekeeping.status` | ❌ none | `housekeeping.assign/assign-all/photos` → `can:housekeeping.manage` | dirty→clean→inspected advanced by a role with `housekeeping.view` only |
| `reservations.move` | ❌ none | `reservations.charge` → `folios.manage` | an **inventory write** via `AssignmentService` reachable by any back-office role |
| `front-desk.check-in/check-out` | ❌ none | (documented "any back-office") | lower — check-in/out is plausibly every front-desk role's job; confirm the intended gate |

All remain tenant-scoped + audited + integer-money-safe → **privilege-within-org, not a cross-tenant breach.** Fix = add the correct `can:` middleware (each gated with a permission the *legitimate* role already holds — verify the `config/authorization.php` role matrix first so we don't lock out Front Desk from check-in). **Mutation-prove each** (role-without → 403; role-with → 200). This is the #26–#30 pattern applied.

---

## Per-page gap register (real gaps only; fully-operational pages with no material gap omitted from the table)

### Operations
| Page | Status | Gaps (class) |
|---|---|---|
| **Unit Requests** | 🔴 NON-OPERATIONAL | approve/decline/create-unit workflow absent — `OwnerUnitRequestService` has only `submit()`+list, no `approve/decline/fulfil` (NON-OPERATIONAL); no deep-link to create the unit (FEATURE); no owner feedback loop (FEATURE) |
| **Group Booking** | 🟠 partial | block folios post ONLY room — **no tax/VAT/fees** while the single-res flow DOES → **#17 under-bills** (BACKEND); group-discount 0/5/10/15% absent (DESIGN+BACKEND); deposit-to-confirm absent (DESIGN+BACKEND); rooming list / per-unit lead absent (FEATURE); headcount + capacity meter absent (DESIGN+FEATURE); stepper/review/summary-rail (DESIGN) |
| **Front Desk** | 🟠 partial | "Today's timeline" time-of-day viz absent — **no ETA field exists** (DESIGN+BACKEND; needs an `expected_arrival` field or stays KIND-3); walk-in fast modal deferred (FEATURE; walk-in IS creatable via Calendar); check-in/out modal options — ID-verify/keys/collect-balance mostly deferred (FEATURE); inline assign modal (DESIGN) |
| **Reservation Detail** | 🟠 partial | "Notes & requests" card absent — **`notes_guest`/`notes_internal` columns exist unused** (DESIGN, backend ready); no guest-CRM cross-link (FEATURE); audit trail is status-only — **`audit_events` already record every money action, page doesn't read them** (BACKEND); no Rebook on terminal (FEATURE); payment/charge chips (DESIGN) |
| **Calendar / Rack** | 🟠 partial | per-**UNIT** rack rows — build is room-type heatmap, can't see which physical unit a bar sits in (DESIGN, biggest structural); booking bars no cross-link to detail (FEATURE); view-range 7/14/30 switcher (FEATURE); OOO/OTA badges (DESIGN) |
| **Reservations list** | 🟠 partial | "Refunded" row badge — **`payment_status='refunded'` is a real state now** (BACKEND); search excludes unit code (FEATURE); bulk select+message — **send is a real DEFERRAL** but the select UI is a FEATURE gap; row drawer / hover quick-actions (DESIGN); no pagination (scale) |
| **Maintenance board** | 🟠 partial | GATE-0 gating; bulk-select mode absent (FEATURE); **awaiting-parts writer (`setParts`/`setAwaitingParts`) exists but wired to ZERO blades** (BACKEND) |
| **Work Order / Ticket Detail** | 🟠 partial | activity update composer absent — timeline read-only (FEATURE); no photo section on detail (photos.store only in board drawer) (DESIGN+FEATURE); SLA timing card absent from rail — `MaintenanceSla` computed, shown on board only (DESIGN); rich resolve modal (DESIGN) |
| **Housekeeping** | 🟢 operational | GATE-0 (`housekeeping.status` gate); kanban drag (DESIGN, honest — buttons cover it); undo (FEATURE, minor) |

### Finance / owner
| Page | Status | Gaps (class) |
|---|---|---|
| **Reports** | 🟠 partial | **revenue-by-channel table absent** (FEATURE; `PerformanceMetrics::sourceNights` exists); **revenue-by-month chart absent** (FEATURE; `monthlyTrendForProperties` exists); **Export is a dead "Soon"** — buildable via dompdf+Postmark, so a build gap not a deferral (FEATURE); owner-statements summary card absent (DESIGN; lives on Owners page); KPI row diverges Rev/Occ/ADR/RevPAR (DESIGN; `MetricSet` exists) |
| **Property Settings** | 🟢 operational | ⭐ **VAT "Incl./Added" toggle shows but doesn't persist** — `updateTax` writes only rate; `inclusive` drives folio math via `StayEstimator` (BACKEND, #17-adjacent); per-fee/tax **removal** absent — can add, can't retire (FEATURE, archive-not-destroy); per-member "Manage"/invite "Resend" read-only (FEATURE); tabs + global save-bar (DESIGN) |
| **New Reservation** | 🟠 partial | `arrival_window` / `team_note` / `send_confirmation` **collected but not persisted** (BACKEND ×3); rate-plan selector (DESIGN, only if >1 real plan — don't hardcode WF's fake multipliers); per-night breakdown w/ weekend chips (DESIGN); Done/QR screen (DESIGN, low) |
| **Rates** | 🟢 operational | unit/segment grid **filter** absent (FEATURE, data exists); per-day availability lane in header (DESIGN); bulk undo (FEATURE, minor) |
| **Owners** | 🟢 operational | statements filter dropdown + portal-adoption card (DESIGN, low) |
| **Owner Detail** | 🟢 operational | contact Email/Message buttons + "view portal"/YoY (DESIGN, correctly omitted — no backend) |
| **Payout Run** | 🟢 operational | none material (SEPA/Connect/IBAN = honest deferrals) |
| **Billing** | 🟢 operational | cancel modal simplified — reason chips need a column (DESIGN, optional); stale docblock |

### Comms / system
| Page | Status | Gaps (class) |
|---|---|---|
| **Reviews** | 🟠 partial | ⭐ **summary dashboard entirely absent** — overall rating + 5-star histogram + reply-rate + by-channel; **`ReviewRating` helper already exists** (DESIGN+BACKEND, highest Reviews gap); no filter tabs All/Published/Pending/Rejected — columns exist (DESIGN); automation/gating honestly deferred (gating correctly REFUSED) |
| **Inbox / Messages** | 🟠 partial | ⭐ **snooze/reopen/link-reservation NOT rendered** — routes+`InboxService` methods exist+audited → **closed thread is a UI dead-end** (DESIGN, services built); assign internal-note not captured (FEATURE); retry on failed (FEATURE, minor) |
| **Team Schedule** | 🟠 partial | coverage-this-week summary absent — extend `RosterWeek` (FEATURE); day-focus panel (DESIGN); "open shifts" unmodelled (FEATURE, net-new model); assign fires no notification (DESIGN); **`grantLeave` route exists but no UI** (DESIGN) |
| **Security** | 🟠 partial | ⭐ **"Active sessions" shown "Soon" but REAL on Profile** (`ActiveSessions` live) → render the real panel (BACKEND, strongest fix); data export absent — owner has it (FEATURE); hero 4-stat tiles (DESIGN); connected-apps/trusted-devices "Soon" (reuse exists; honest for now); IP allow-list (DEFERRAL, net-new) |
| **Profile** | 🟢 operational | per-session "Sign out" absent — bulk path exists (DESIGN) |
| **Dashboard** | 🟢 operational | arrival rows omit nights·unit·ETA (DESIGN; add nights+unit, ETA only if stored) |
| **Channels** | 🟢 operational (honest-gated) | no on-page channel status action (BACKEND, low — everything downstream Channex-gated) |
| **Notifications · Notif Prefs · Email Templates · Email Editor · Insights · Portfolio · Guests · Guest Profile · Schedule PDF · Roles · Maintenance Costs/Settings** | 🟢 operational | none material |

---

## Honest-deferral register (STAYS deferred — do NOT build/fake)
OTA/Channex sync (Channels · Rates push · Reservations OTA badge · Reports channel commission "not tracked") · WhatsApp/OTA inbound inbox channels + presence/typing/read-receipts (Inbox) · SMS + Push (Notification Preferences) · live card charge / stored card / paid-invoice PDFs (Billing · New Res · Front Desk · Reservation Detail — Stripe test-mode until Subhan's go-live) · owner payout SEPA export + Stripe Connect disbursement + IBAN request (Payout Run) · ID verification/KYC (Front Desk check-in) · IP allow-list (Security, net-new) · review-gating (Reviews — deliberately refused as deceptive) · smart-pricing engine (Rates) · vs-LY deltas where no prior-year data. All confirmed **honestly surfaced**.

## Preserve list (already-real — PHASE 2 must NOT regress)
The certified write paths + guards on every fully-operational page: `AssignmentService` (no-double-book, #29 terminate) · `FolioService` (#27 refund bound, StayEstimator #17) · `OwnerPayoutService`/`PayoutRunService` (#71) · owner-ledger attribution (refund adjust) · `RateAndRestrictionService`/`RateBulkService`/`RateDerivationService` · `HousekeepingService`/`MaintenanceService`/`MaintenanceCostService` · `InboxService` · `NotificationFeed`/`NotificationPreferences` (Email gating mutation-proven) · `RolePermissionService` (#102 matrix) · `SearchService` (#102 blind-index) · `RosterWeek`/`LeaveService` (self-approval impossible) · GDPR `anonymize()` (Guests/Guest Profile, archive-not-destroy) · #28 name-only everywhere except the Guests CRM · #33 door-code guest-only · every "honest empty/Soon" (#39). **Two stale docblocks to fix (not regress):** `GuestsList` (understates real bulk actions) and `ReservationRefund` (says reason/email deferred — both are wired).

---

## Proposed build plan (gated; order = security → non-operational/#17 → real-able-absent → wiring)

- **⛔ GATE 0 — SECURITY hardening** (small, first): capability-gate the ungated operations writes (maintenance.* · housekeeping.status · reservations.move; confirm front-desk check-in/out gate). Mutation-prove each. *(Behaviour-changing — founder confirm.)*
- **⛔ GATE 1 — non-operational + #17 + biggest ops gaps**: Unit Requests approve/decline/create workflow · Group Booking tax/fee/discount/deposit (#17) + rooming/headcount · Reservation Detail (Notes card + audit_events trail + guest-CRM link) · Maintenance (awaiting-parts UI + bulk-select + work-order composer/photos/SLA card).
- **⛔ GATE 2 — finance/analytics real-able-absent**: Reports (channel table + month chart + real PDF/CSV export + owner-statements card) · Reviews (summary dashboard + filter tabs) · Property Settings (VAT inclusive persist + per-fee delete + per-member manage) · Rates (grid filter + availability lane) · New Reservation (persist arrival_window/team_note/send_confirmation).
- **⛔ GATE 3 — wiring + remaining**: Inbox (reopen/snooze/link UI) · Security (real Active-sessions panel + data export) + Profile per-session sign-out · Team Schedule (coverage + grant-leave UI + assign notification + day-focus) · Dashboard (arrival nights/unit) · Reservations list (refunded badge + unit search) · Calendar (bar cross-link + range switcher) · Front Desk (walk-in/settle modals) · Channels on-page status.
- **⛔ FINAL** — docs (this file resolved + `DECISIONS.md` dated + `DIVERGENCE-REGISTER.md` per-page + `PROJECT-STATE.md` + invariants) · full suite + new tests · PHPStan L5 + Pint · `migrate:fresh --seed` · browser-verify each completed page · push per gate.

### Structural items needing a founder call (net-new models, not just wiring)
1. **Front Desk "Today's timeline"** — a truthful time-of-day plot needs an `expected_arrival`/ETA field (schema+model). Build the field, or keep the timeline a documented KIND-3 omission?
2. **Schedule "Open shifts"** — needs an unassigned-shift model (net-new). Build, or keep the honest omission (the PDF already documents it)?
3. **Calendar per-unit rows** — a real structural rebuild (unit-lane grid + per-unit blocks). In scope for this pass, or a separate structural task?
