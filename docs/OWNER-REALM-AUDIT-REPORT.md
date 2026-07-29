# Vyrobel — OWNER REALM comprehensive audit (READ-ONLY)

**Date:** 2026-07-29 · **Scope:** the entire owner realm — 24 pages + detail/PDF/CSV surfaces + the OAuth API + the cross-cutting engines (attention store, rate-proposal + expense-approval state machines, payout engine, OAuth/connected-apps, tax/VAT + withholding, Support AI).
**Method:** read-only. Four parallel code investigators (logic/data, invariants/security, design/UX, completeness — the last fanned into 4 page-groups) + a firsthand browser pass logged in as `demo-owner@vyrobel-demo.test` (LOOK + CLICK: real mutations exercised) + code-health (full suite, PHPStan, Pint, composer audit, migrate:fresh).
**Nothing was changed.** This is FIND-and-REPORT; fixing is a separate triaged pass.

---

## 1. EXECUTIVE SUMMARY

**The owner realm is solid, deep, and honest.** All 24 owner pages render (HTTP 200, no raw-Blade leak, no exception in server render), every detail page + all four PDFs + all six CSV exports produce correct content-types, the API is gated (401 without a token), and the full suite is **1240/1240 green · PHPStan L5 = 0 · Pint clean · `migrate:fresh --seed` clean**. Interactive mutations were exercised live and behave correctly with real, reconciling money effects (evidence below). The invariant guardrails (#17 reconcile, #28 owner-scope + name-only, #33 arrival secrets, #20 write discipline, #39 no-fabrication, the Wave-D isolations) **hold**, with one genuine cross-owner weakness and two latent reconciliation gaps found by reading.

**This is a mature product.** The overwhelming majority of findings are **P3 doc-vs-code comment rot** — header "HONESTY MAP" comments and component docblocks that still say "REFUSED / NO BACKEND / no invented line" for features that were subsequently built (payment_fee bucket #70, withholding #73, expenses-approval #68, payout engine #71, OAuth #72, Support AI). The *code is right; the comments lie* — which matters precisely because this project uses those comments as the per-surface source of truth.

### Findings by severity
| Severity | Count | Nature |
|---|---|---|
| **P0 blocker** | **0** | No broken page, no money moving wrong, no fabrication, no immediately-exploitable hole in the default config. |
| **P1 serious** | **1** | Cross-owner union-role over-grant (multi-owner sub-tenancy). |
| **P2 minor** | **4** | 2 latent reconciliation gaps · 1 scoped-but-unbuilt UI · the most-misleading stale comment. |
| **P3 polish** | **~16** | Doc/comment rot cluster · a11y (placeholder-as-label) · no submit-flash on 4 forms · residual mobile overflow · consistency (test-mode banner, next-run date, held visual) · edge cases (FX, failed payout) · sweep-doc staleness. |

### Top 5 to fix first
1. **[P1] Cross-owner union-role over-grant** — a user who is co-owner of Owner A *and* a lesser role on Owner B (same tenant) acts with the **highest** role across **both**, and `isPrimaryOwner` is a global boolean → they get co-owner money powers + team management over Owner B. Needs a per-owner role check. *(Reasoned from code; confirm with a mutation test.)*
2. **[P2] Balance clearing identity breaks after a post-lock refund** — a negative-net statement posts an unstamped carry-forward that no Balance tile bucket represents → the page's stated "Available + On hold + In transit + Paid == Net, to the cent" fails permanently once any negative statement exists (#17).
3. **[P2] Reports "Gross to net" omits `payment_fee` + `withholding` buckets** — reconciles today (both €0) but will visibly fail to add up the moment withholding is configured; already disagrees with the annual tax summary (which includes them). The computed `reconciles` flag is never read by the view.
4. **[P2] My Units richer day-detail popover not back-ported** — scoped in the sweep, delivered on Unit Detail, never brought to My Units (which shows only a booked count). The one genuine scoped-but-unbuilt UI gap.
5. **[P2] Honesty-comment rot** — most misleadingly, `expenses.blade.php:15-16` still says the Approve/Query workflow is "REFUSED (no backend)" directly above the built, working workflow. A future reader is told a real feature is fake. Sweep a cluster of these (§5).

---

## 2. P0 BLOCKERS

**None.** No owner page 500s or leaks; no money figure moves incorrectly (every state machine exercised reconciles); no fabricated data; the account-gated deferrals (payout test-mode, VAT/withholding unconfigured, OTA/SMS/push, live banking verification) are all honestly labelled. The one security weakness (P1 below) requires a specific multi-owner arrangement and exposes no raw card/IBAN, so it is not a P0.

---

## 3. P1 SERIOUS

### P1-1 · Cross-owner union-role over-grant within a tenant · invariant-violation (#42, #28) / security
**Surface:** the whole owner realm (every `$capActions`/`$capPayout`/`EnsurePrimaryOwner` gate + owner-scoped data) + the OAuth API.
**Evidence:**
- `app/Http/Middleware/SetOwnerScopeForPortal.php:52-63` — a portal user's `ownerIds = primaryOwnerIds ∪ subMembership.owner_id`; `role = highestRole(all sub-roles)`; `isPrimaryOwner = (primaryOwnerIds !== [])` — a **global** boolean, not per-owner.
- `app/Support/Owner/OwnerContext.php:93-96` — `can()` evaluates that single union role.
- `app/Models/Scopes/OwnerScope.php:44` — rows constrained to `owner_id IN (union)`.
- No per-owner role check exists (`grep isPrimaryOwnerOf|roleForOwner` → 0 hits).

**Failure scenario:** User X is invited **co_owner of Owner A** and **view_only of Owner B** (same org). The invite guard only blocks an already-**active** user (`app/Services/OwnerTeamInviteService.php:73-76`), and a fresh invitee is `status=invited`, so both invites succeed and `accept()` (`:144`) re-checks neither. On login X's `role = co_owner`, `ownerIds = [A,B]` → X can, **on Owner B's data**: read B's masked payout IBAN/country (`OwnerNextPayout` gates on the union `can(CAP_PAYOUT)`), approve/decline B's rate proposals + expenses (real money), edit B's rates (`OwnerRateService::editableRoomTypes()` resolves over the union), and set B's payout preferences. Separately, **primary owner of A + sub-user of B** → `isPrimaryOwner()` is globally true → `EnsurePrimaryOwner` passes on `/owner/team/*`, and `OwnerTeamController` resolves the target via `whereIn('owner_id', $ownerIds)` (`:79,90,106,117`, union includes B) → X invites/re-roles/removes **Owner B's** team members (violates #42 "team management is primary-owner-only").
**The OAuth API inherits the same union role** via `EnsureOwnerApiScope → OwnerContext::can()`, so the "no over-grant" API guarantee weakens identically for a multi-membership token.
**Severity note (honest):** reasoned from code, **not runtime-proven** — the read of B's masked IBAN + the capability pass are confirmed by the code paths; an actual approve-as-dual-membership-user was not executed. Reachability requires a specific multi-owner arrangement, and no raw card/IBAN is exposed (masked last-4 + country only) — but the cross-owner *write* powers over money decisions make it P1. **Fix direction:** compute and enforce role **per owner** (resolve the acting owner from the request/route and check the role for *that* owner), and make `isPrimaryOwner` a per-owner predicate; add a mutation test for the dual-membership scenario.

---

## 4. P2 MINOR

### P2-1 · Balance clearing identity breaks after a post-lock refund (negative statement) · invariant-violation (#17)
**Surface:** Owner Balance. **Evidence:** `OwnerPayoutService.php:63-66` (net<0 → no payout) + `carryForward()` `:281-304` (posts an **unstamped** carry debit); `OwnerBalance::netPosition()` `:159-169` sums all ledger rows including the carry, but the negative statement's stamped rows sit in no payout bucket. **Failing input** (mirrors `tests/Feature/OwnerPayoutTest.php:82-107`): Sept net +17000 → paid; a post-lock refund → Oct net −17000 (stamped, no payout) + carry −17000 (unstamped). Net position = −17000 (correct), but tiles Available(−17000)+Paid(17000)+OnHold(0)+InTransit(0) = €0 → **off by 17000**, permanently, once any negative statement exists. The existing test asserts the carry line exists but never the identity, so it's green over this. **No money is wrong — the tiles mislead.** Fix: represent the carry as a matched c/f pair, or fold carry-forward into the tile reconstruction.

### P2-2 · Reports "Gross to net" omits `payment_fee` + `withholding` → latent non-reconciliation · invariant-violation (latent)
**Surface:** Owner Reports. **Evidence:** `OwnerReports::grossToNet()` `:348-372` buckets only room/addon/commission/ota/mgmt/cleaning/expense/adjustment; the `net` line is Σ over ALL entries, so `payment_fee`/`withholding` land in no bucket → gross+commission+fees+adjustments ≠ net the moment either posts. The computed `reconciles` flag `:370` correctly flips false but **the view never reads it** (`reports.blade.php:362-372`). €0 today, so it reconciles now; activates on `tax:set-rate`. Contrast `OwnerTaxService::annualSummary` (`:141`) which *does* include both → the two owner surfaces will disagree. Fix: add both types to the fees/adjustments sums (match `OwnerStatementService.php:29-40`).

### P2-3 · My Units richer day-detail popover never back-ported · missing-feature
**Surface:** My Units occupancy calendar. **Evidence:** `units.blade.php:451-458` shows only a booked *count* ("X of Y units booked"); `OwnerMyUnits.php` has no `dayDetails` (grep clean). The sweep master-gap list (`OWNER-COMPLETENESS-SWEEP.md:64`, PHASE 2) scoped a full day-detail popover (check-in/out, guest name-only #28, owner-net) — which was delivered on **Unit Detail** (Wave B GATE 2, `unit-detail.blade.php:127-145`) but never back-ported to My Units. The blade comment `units.blade.php:451` even says "GATE 2 adds guest/revenue day-detail" — but GATE 2 was a different page. Low impact, but scoped-and-unbuilt. Fix: port the Unit-Detail popover + correct the stale comment.

### P2-4 · Honesty-comment rot — a real feature documented as "REFUSED" · doc-vs-code (honesty)
**Surface:** Expenses (worst case) + a cluster. **Evidence:** `expenses.blade.php:15-16` HONESTY-MAP says *"REFUSED — the design's owner APPROVE / QUERY workflow + 'Awaiting your approval' (no backend, #39)"* — directly above the built, working workflow (KPI `:42-45`, Approve/Query `:53-82`, `OwnerExpenses.php:50-57`). `OwnerExpenses::metrics()` docblock `:202-203` repeats it. This is the single most misleading comment (a reader is told a real, money-affecting feature is fake). See §7 By-Dimension for the full stale-comment cluster (statement/payouts/balance/reports/rates/security). Fix: a comment-sweep pass. *(P2 for this one because it inverts the truth on a money feature; the rest are P3.)*

*(A measured mobile horizontal-overflow was reclassified P3 — see §5 — because the structural 2-column page layouts DO collapse via media queries [design investigator verified]; the residual overflow is unclipped ambient decor + KPI cards.)*

---

## 5. P3 POLISH (grouped)

**Doc/comment rot (code correct, comments stale — a cleanup sweep):**
- `statement.blade.php:12-14` + `pdfs/owner-statement.blade.php:4-5` — "no payment/ops-fees line (#39)" but the generic line loop now renders the real `payment_fee`/`withholding` lines.
- `OwnerPayouts.php:48-51` — a large "NO BACKEND … omitted/disabled Soon" docblock; every listed item (hold banner, IBAN card, schedule/next-date, Export CSV, preferences editor) is now built.
- `OwnerPayouts.php:270-272` — "no invented payment-fee line" but `breakdown()` maps all lines.
- `balance.blade.php:180` + `OwnerBalance.php` comment — "No fabricated next-run date" but `PayoutSchedule` now produces a real one (shown on Payouts).
- `reports.blade.php:14-15` + duplicate contradictory block `:422-427` (stale) vs `:428-430` (current) — VAT "not computed"/"Email to me = Soon" both now false.
- `OwnerRates.php:35` — "Proposed-changes has NO backing schema" but `RateProposal` (#67) now exists (surfaced on My Units/Overview/Insights, not the Rates page — acceptable design choice).
- `OwnerSecurity.php:32-33` — lists passkeys/login-approval/trusted-devices/connected-apps as "Soon" though all are now REAL; `owner-security.blade.php:3` vs `:27-29` contradict on "no score" wording (the derived /100 score is real).
- `docs/OWNER-COMPLETENESS-SWEEP.md:174-180` HARD-DEFER list marks VAT/withholding (#73), OAuth connected-apps (#72), and Support-AI (Wave D) as deferred — all since built.

**Consistency / small gaps:**
- **Payout Method missing the `<x-owner.payout-test-mode>` banner** that Payouts + Balance show (`payout-method.blade.php`), despite offering (test-mode) Connect onboarding — consistency + honesty.
- **Real next-run date not surfaced on Balance / Statement / Payout Method** though `PayoutSchedule::nextRunDate` exists and Payouts shows it.
- **Payouts lifecycle has no distinct "On hold" visual** for the persisted `held` status (grouped with Pending; self-acknowledged `OwnerPayouts.php:311-314`).
- **Agreement "Message your manager" is a disabled "Soon"** (`agreement.blade.php:243`) though the real owner↔operator relay now exists (used by Help/Expenses) — candidate to wire up.
- `OwnerLedgerAttribution` `TYPE_ORDER`/`entryTypeLabel()` `:34-37,142-155` miss `payment_fee`/`withholding` → generic `ucfirst` label (still reconciles — `orderedTypes()` appends unknowns; cosmetic only).
- `OwnerSettings.php:111` — `hero.channels_live` hardcoded `1` (honest while OTA is gated; won't reflect future activation without a code change).

**Edge cases (not demo-reachable):**
- Balance identity also breaks for a `failed`/`reversed` payout (stamped rows counted in Net, absent from buckets until retried) and under genuine FX (payout currency ≠ booking currency — ledger rows vs payout land under different currency keys). Demo is single-currency EUR. (`OwnerBalance.php:42-44,91-99`.)

**Design / UX (blade-level — design investigator):**
- **A11y — placeholder-as-label / no label association** (WCAG gap, inherited from the prototype; visually usable): `team.blade.php:139` invite-email input (no `<label>`, no `aria-label` — the file has zero labels), `expenses.blade.php:74` note input, `expenses.blade.php:249` repair textarea, and the whole **Add-Unit wizard** (zero `for=`/`id=` associations). Cheapest fix: add `aria-label`s. (Bookings search + Settings/Payout-Method/Tax/Rates forms *are* well-labelled — credit.)
- **No explicit submit feedback on 4 POST forms** — `add-unit`, `dashboard` (attention snooze/dismiss), `expenses` (relay/note), `notifications` (mark-read) render no success/error flash (each has *implicit* feedback — a new request row, a cleared dot, a redirect — so nothing silently fails; the other 15 form pages *do* flash). Also: no `wire:loading`/client submit-disable anywhere (architecturally consistent — all writes are controller POSTs, not wire actions — but a double-click isn't client-guarded except where server idempotency keys exist).
- **Residual mobile horizontal-scroll (~119px at 390px)** — the structural 2-col layouts collapse via `@media` queries (verified), but ambient decor (`vy-amb-orb/streak`) isn't clipped and KPI cards don't shrink at phone widths. Decorative-clipping + KPI-grid-min-width polish, not a broken layout. *(Open question: is mobile in scope?)*

**Security (pre-existing, flagged separately):**
- `composer audit` → **10 dompdf advisories** (2 packages; CVE-2026-59943/59942/59941/56722 + others) — a dompdf bump, tracked separately from this audit.

**Methodology note (recorded for future raw-Blade audits):** the one `\w@directive` grep hit — `payouts.blade.php:230` (`@endif@if`) — compiles **correctly** through the real booted Livewire-4 app compiler (tinker: 0 raw leaks, no syntax error); a bare `new BladeCompiler` miscompiles it (false positive). **Always test the raw-Blade trap through `app('blade.compiler')`, never an isolated compiler.**

---

## 6. WHAT'S SOLID (credit where due)

- **Every page renders + every export works.** 24/24 GET routes 200, no raw-Blade, no exception; statement/booking/unit detail 200; statement + booking-summary + remittance + annual-tax PDFs all `application/pdf`; 6 CSV exports all `text/csv`; API 401 without a token.
- **Money reconciles (verified by reading + live):** the Statement breakdown (component lines Σ == net_source; per-unit Σ == net/gross by construction), withholding (pre-net, non-circular, idempotent, net drops exactly), VAT (correct bases, integer half-up, informational), Reports by-channel (Σ == period net), the annual tax summary (includes all buckets). Money integer discipline (`Money::percentage`/`allocate`) correct; divide-by-zero guarded everywhere (`MetricSet`).
- **State machines correct:** rate-proposal (approve applies via certified `OwnerRateService`, decline/withdraw no-op, guards pending), expense-approval (pending not charged → approved posts debit → reconciles → net drops; query charges nothing), payout no-double-transfer (`lockForUpdate` + re-check pending + rail idempotency; tax-hold guard *before* the rail), payout schedule business-day math (no off-by-one), unit pause (genuinely refuses new bookings, keeps existing).
- **Invariants hold:** #28 owner-scope via `findOrFail` never RMB (every `{id}` controller cited), name-only guest PII via shared `GuestName` on every surface incl. the API, #33 arrival secrets absent everywhere, #20 write discipline (zero mutating owner Livewire actions — every write a gated controller `<form>`), #39 honest states, OAuth triple-gate (non-owner 403, cross-org 403, scope+capability, immediate revoke) — all modulo P1-1's union role.
- **Live mutation proofs (browser, demo owner):** expense approval → Awaiting €84→€0, Charged €250→€334 (reconciles) and Balance Available €963.60→€879.60 (exactly −€84); rate apply → €195 + weekend €224.25 (+15%, correct) + min-stay 2n, persisted; tax-details save → held payout released (On hold €885.60→€0, In transit →€885.60); attention snooze → Undo shown, no error. Ask Vyrobel grounded + escalate verified in the prior session.
- **Code health:** suite 1240/1240 · PHPStan L5 = 0 · Pint clean · `migrate:fresh --seed` clean · no TODO/FIXME/HACK of concern (the two `'todo'` hits in `OwnerPayouts.php` are step-state string literals, not markers).

---

## 7. BY DIMENSION

### Dimension 1 — Design / UX fidelity (29 blades + 29 classes reviewed)
**Strong template health — 5 of 8 checks CLEAN, only 2 concrete P3 actionables:**
- **CLEAN — raw-Blade:** exactly one `\w@directive` grep hit (`payouts.blade.php:230` `@endif@if`), verified to compile correctly through the real app compiler (see §5 methodology note). No content leak anywhere.
- **CLEAN — Livewire prop-shadowing:** every scoped-model component holds a **string id** (`OwnerBookingDetail.php:48 $bookingId`, `OwnerStatementView.php:51 $statementId`, `OwnerMessageThread.php:54 $threadId`, `OwnerUnitDetail.php:145 $unitId`), never a model or colliding array key — two even carry explicit trap-documenting comments. Exemplary.
- **CLEAN — empty states:** every dynamic list has an honest empty branch (bookings/balance/expenses/payouts/reviews/team/insights/reports/messages/archive/documents all cited with `@empty`/`vy-empty`). No blank/broken/error-on-empty loop.
- **CLEAN — consistency:** all 29 blades render inside `<x-owner.shell>` with `--vy-*` tokens + shared `vy-card`/`vy-kpi-grid`/`vy-empty`/`vy-input` chrome; the `<x-owner.payout-test-mode>` banner is a shared component (no drift).
- **CLEAN — responsive layout:** two-column layouts use `grid minmax(0,1fr) NNNpx` **paired with `@media` single-column collapse** (bookings/payout-method/reports/unit-detail cited); remaining fixed widths are intrinsic (rings/avatars/popovers). *(Residual decorative overflow at 390px → §5, P3.)*
- **P3 — a11y** (placeholder-as-label): team-invite email, expenses note + repair textarea, Add-Unit wizard fields lack label/aria association.
- **P3 — feedback:** 4 forms render no explicit success flash (implicit feedback exists); no `wire:loading` (consistent with the controller-POST architecture).
- **Fidelity:** no *undocumented* structural divergence surfaced; per-page divergences are documented in `DIVERGENCE-REGISTER.md` (76 rows). A pixel-level wireframe diff was out of scope for this template-health pass.

### Dimension 2 — Functional bugs (browser)
- **No functional bug found.** All pages load; all exercised mutations persist + reflect + reconcile (see §6 live proofs); all links resolve; filters/sort/pagination present on Bookings; PDFs/CSVs download. No console error except my own API-probe 401s (expected).

### Dimension 3 — Logic / data correctness
- **P2-1** Balance identity on negative statement; **P2-2** Reports gross-to-net omits withholding/payment_fee; **P3** Balance identity on failed/reversed payout + FX; **P3** ledger-attribution labels. Everything else reconciles (§6).

### Dimension 4 — Invariant / security
- **P1-1** cross-owner union-role over-grant. All other invariants SOLID and credited (§6). Open question: `OwnerContext::forOwners()` (`:121-129`) is a full-privilege programmatic escape hatch — not a request path, but note it must never be called from request code.

### Dimension 5 — Completeness
- **P2-3** My Units day-detail not back-ported — the ONLY genuine scoped-but-unbuilt UI gap. Everything else the sweep (Waves A/B/C) + Wave D claims is genuinely built and wired (all four page-groups confirmed). No UI-without-backend, no backend-without-UI. Honest-deferrals correctly labelled: OTA/channel sync, SMS, push, dark theme, session history/new-device alerts/session-timeout, full-archive ZIP, live banking verification (micro-deposit/SEPA/48h hold), instant withdrawal, Messages' read-receipts/presence/reactions/attachments — all account-gated or unmodelled, none faked.

### Dimension 6 — Code health
- Suite 1240/1240 · PHPStan L5 = 0 · Pint clean · `migrate:fresh --seed` clean · 10 pre-existing dompdf advisories (separate bump). Root cause of most findings: shipped engines whose per-surface HONESTY-MAP comments weren't updated (§5 doc-rot cluster).

---

## 8. BY PAGE (× findings)

| Page | Findings |
|---|---|
| Overview / Dashboard | Clean. Goal line real, attention feed real, proposal chips real. |
| My Units | **P2-3** day-detail popover not back-ported + stale comment `units.blade.php:451`. |
| Add Unit | **P3** a11y (wizard fields lack label association); **P3** no submit flash. Otherwise clean (4-step wizard incl. amenities + photos, real commission panel). |
| Unit Detail | Clean (weekend editor, richer day-detail, read-only cleaning schedule, pause). P3 instant-book subtitle not mirrored (honest simplification). |
| Bookings (+ PDF) | Clean (chart+goal-line+ring, filters, sortable, CSV, name-only). |
| Statement (+ PDF) | **P3** stale "no fees line" comments (`:12-14`, PDF `:4-5`). Breakdown reconciles. |
| Payouts | **P3** large stale "NO BACKEND" docblock (`:48-51`); no distinct `held` visual; next-run shown here (good). |
| Payout Method | **P3** missing test-mode banner; **P3** no real next-run date. Honest pending-live verification (correct). |
| Balance | **P2-1** identity breaks on negative statement; **P3** stale "no next-run date" comment; **P3** FX/failed edges. |
| Expenses | **P2-4** "REFUSED" comment over a built workflow (`:15-16`, `OwnerExpenses.php:202-203`); **P3** a11y (note/repair inputs no label); vendor column real; dual-section vs single wireframe (open question). |
| Block Calendar | Clean (drag-select block, release, series+Undo, name-only). |
| Documents | Clean (real per-month PDFs, annual-tax card reconciles, ZIP honest-Soon). |
| Reports | **P2-2** gross-to-net omits withholding/payment_fee; **P3** stale VAT/email comments + duplicate block. VAT computed + example-flagged (correct). |
| Insights | Clean (vs-LY, lead-time, day-of-week, worth-a-look, proposals link — all real). |
| Rates | **P3** stale `OwnerRates.php:35` proposal-schema comment. Nightly+weekend+min-stay + preview all real (verified live). |
| Tax Center | Clean (withholding card real, not fabricated 15%/20%; save releases hold — verified live). |
| Team | **P3** a11y (invite-email input no label/aria). Subject to **P1-1**. Otherwise clean (invite/role/remove/resend/revoke, real matrix, export download-log + watermark). |
| Security | **P3** stale docblock (`:32-33`) + score-wording contradiction. Passkeys/login-approval/trusted-devices/connected-apps all real. Honest "Soon": new-device alerts / sign-in history / session timeout. |
| Settings | **P3** `channels_live` hardcoded. 13 sections present; SMS/push/OTA/theme honest-deferred; billing = real fee. |
| Messages (+ thread) | Clean core relay; ~1/3 of the mock honestly omitted (read-receipts/presence/reactions/attachments/bot). |
| Reviews | Clean (read-only, real operator responses, name-only, honest empty). Only response-rate metric deferred. |
| Agreement | **P3** "Message your manager" disabled though relay exists. Otherwise fully real, read-only. |
| Onboarding / Welcome | Clean (thin IBAN-less flow, real fee, payout hand-off). |
| Help (Ask Vyrobel) | Clean — exceeds "honest FAQ": real grounded assistant + real relay + honest refusals. |
| OAuth API / Connected apps | Triple-gate holds; **P1-1** union-role weakens no-over-grant for a multi-membership token. |

---

## 9. OPEN QUESTIONS (founder's call)

1. **Is mobile in scope for the owner portal?** (P2-5 horizontal scroll — fix only if yes.)
2. **P1-1 exploitability:** confirm the write blast-radius with a mutation test (dual-membership user approving Owner B's expense/rate). Is multi-owner-per-person sub-tenancy a supported arrangement? If never, downgrade to P3.
3. **Expenses dual-section** ("Needs your approval" approval-state + "Pending review" deductibility tri-state) vs the single wireframe block — intended, but wants a design sign-off.
4. **Should the real next-run date + `held` visual + test-mode banner be unified** across Balance/Statement/Payout-Method/Payouts for consistency, or is Payouts the intended single home?
5. **Doc-rot policy:** sweep the stale HONESTY-MAP comments now (they're load-bearing as source-of-truth), or defer to the next per-page pass?

---

## 10. WHAT'S GENUINELY WELL-BUILT (summary)

The owner realm is a **deep, honest, reconciling** product: three real approval state machines, a real (test-mode) payout engine, a real OAuth server, a real VAT/withholding engine, a real grounded support assistant — all surfaced, all capability-gated, all reconciling to the cent, none faked. The security perimeter (#28/#33/#20/#39) holds under scrutiny. The single serious finding (P1-1) is a *design-completeness* gap in the multi-owner role model, not a broken guardrail; the two P2 reconciliation gaps are *latent/edge* (one needs a negative statement, one needs withholding configured). The dominant residue is comment rot — the truest sign that the code outran its own annotations. **Nothing here blocks a launch in the single-owner-per-user configuration; P1-1 should be closed before multi-owner sub-tenancy is offered.**
