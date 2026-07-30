# Operator Owners + Owner Detail + Payout Run + Notifications — exact-match audit (#93, PHASE 1, read-only)

**Pages:** `/app/notifications` (`OperatorNotifications`) · `/app/owners` (`OperatorOwners`) · `/app/owners/{owner}` (`OperatorOwnerDetail`) · `/app/payouts/run` (`PayoutRun`) — **all four already exist**; being matched to their wireframes EXACTLY on REAL data.
**Wireframes:** `Operator Notifications.html` · `Operator Owners.html` · `Operator Owner Detail.html` · `Operator Payout Run.html` (dc bundles — decoded; the copy + data are in `scratchpad/wf-inventory-93.md`).
**Date:** 2026-07-31 · **Status:** PHASE 1 complete; awaiting the SEPA / on-hold-gate / payout-run-persistence / notes decisions.

**⚠️ THE OPERATOR SIDE OF THE OWNER FINANCIAL SYSTEM — reuse, never rebuild** (the owner realm + the certified payout engine #71 are the proof they can't drift). Reuse map: certified **`PayoutRunService`/`OwnerPayoutService`** (#71, test-mode) · **`OwnerStatementService`/`OwnerLedgerAttribution`/`OwnerStatementBreakdown`** (the net the owner sees) · **`OwnerCommissionRate`/`ManagementAgreement`** (the real 18%) · **`OwnerPayoutPreference.paused`** (real pause) · carry-forward via `OwnerPayoutService::carryForward` (#P2-1) · **`OwnerInviteService`** (#80 Add-owner) · **`Iban::masked`/`PayoutMethod.iban_last4`** (masked IBAN) · **`NotificationFeed`/`FeedNotification`** (#79 feed).

---

## 1 · NOTIFICATIONS FEED — per-element (`Operator Notifications.html`)

**Already built** (`OperatorNotifications` @ `app.notifications`, shared `NotificationFeed`). Mostly at-parity — a small FIX.

| Element | Verdict | Backend |
|---|---|---|
| Unread count · list rows (title/tag/body/time) · icon tile | ✅ REAL-NOW | `NotificationFeed::forUser` → `notifications` table (real operator notifications) |
| Mark-read (single + all) | ✅ REAL-NOW | `NotificationController` · `notifications.read`/`read-all` (self-only) |
| "in Settings" link | ✅ REAL-NOW | → `app.notification-preferences` |
| Honest empty state | ✅ REAL-NOW | "All caught up" |
| Filter chips (All / Unread / by tag) | 🔨 FIX | today: All + per-category (data-driven from the user's real rows). **BUILD an "Unread" chip.** Wireframe tags: Front desk ✅ / Finance ✅ · **"Reviews" → renders "Reputation"** (real category) · **"Channels" → the real category is `system`** (channel_sync_error) · **"Housekeeping" → NOT-BUILDABLE** (no notification fires it). Honest: show the real categories, don't invent chips. |

---

## 2 · OWNERS LIST — per-element (`Operator Owners.html`)

**Already built** (`OperatorOwners` @ `app.owners`). Add-owner already reuses #80.

| Element | Verdict | Backend |
|---|---|---|
| Owner rows (name/units/status) · pending invites | ✅ REAL-NOW | `OperatorOwners` (`Owner::withCount('units')`, `OwnerInvite`) |
| KPIs: Owners N · units managed · Net-payable·Sep · Needs-attention | ✅ services / 🔨 wire | net from the real statements/ledger (`OwnerStatementService`); needs-attention = holds/drafts (real) |
| Owner rows' net + pay-status | 🔨 BUILD | per-owner net from `owner_statements.net_payable_minor` (reconciling); pay-status from `payouts.status` |
| Payout-run summary ("N scheduled · est. · 1 on hold") | 🔨 BUILD | real: Σ due owners from the engine + the real hold count |
| Statement schedule (finalize 1st · payouts 3rd business day · **Commission 15%→REAL 18%** · portal N of M) | ✅ / 🔨 FIX | schedule real; **commission = the real `ManagementAgreement.commission_pct` (18%), NEVER 15%** |
| **Add owner** (name/email/assign-units/commission/invite) | ✅ REAL (reuse #80) | POST `app.owners.invite` → `InviteOwnerController` → `OwnerInviteService::invite(name,email,unitIds,commissionPct,'owner')` — creates Owner + Membership + OwnerInvite; on accept → ManagementAgreement + unit assignment + "they add their IBAN themselves". **NOT a second invite.** Read-only respected. |

---

## 3 · OWNER DETAIL — per-element (`Operator Owner Detail.html`)

**Already built** (`OperatorOwnerDetail` @ `app.owners.show`, tenant-scoped). Header/KPIs/units/agreement/statements/payouts/portal at-parity; the financial breakdown + edit/remove are BUILD.

| Element | Verdict | Backend |
|---|---|---|
| Header (name/portal-active/units/since/**commission 15%→18%**) + View portal | ✅ / 🔨 FIX | real; commission = the real agreement figure (18%) |
| KPIs: Net-payable·Sep · **Paid this year (N payouts)** · **Avg occupancy** | ✅ / 🔨 BUILD | net real; paid-this-year = Σ paid payouts this year (reuse `OwnerPayouts::metrics` pattern); occupancy = `PerformanceMetrics::forOwnerUnits` (owner's physical unit ids) |
| Statements list · Her units·Sep | ✅ REAL-NOW | `statementRows`/`unitRows` |
| **Net-to-owner·Sep breakdown** (gross + cleaning − commission − fees = net) | 🔨 BUILD (reuse) | the REAL formula via `OwnerLedgerAttribution::breakdown()` / `OwnerStatementBreakdown` — reconciles Σ==net at the real 18% (#17); the wireframe's "gross + cleaning − commission − fees" is a simplification (cleaning folds into other_revenue; more deduction buckets) |
| Contact: email/phone · **IBAN ····4821 · verified** | ✅ masked / 🚫 "verified" | masked IBAN REAL (`Iban::masked($method->iban_last4)`, CAP_PAYOUT, never full, operator never enters it). **"verified" badge NOT-BUILDABLE** — no `verified` column exists and the codebase explicitly refuses to fake it (`OwnerPayoutMethod` docblock) → honest "IBAN on file" vs "none" |
| Notes (add note) | 🔨 BUILD | today a single read-only `owners.notes` text column; a multi-note timeline needs `HasNotes` on `Owner` (decision) |
| **Edit owner** (commission / portal / statement-PDF / **pause-payouts**) | 🔨 BUILD | commission = a new effective-dated `ManagementAgreement` ("applies from next statement"); **pause-payouts = REAL** via `OwnerPayoutPreference.paused` (the certified engine already skips a paused owner — the amount stays in the ledger); statement-PDF-by-email real (existing route); controller-routed + audited (#20) |
| **Remove owner** (units unassigned · bookings/statements/payouts retained · portal ends · final payout honored) | 🔨 BUILD | **archive-not-destroy**: SOFT-DELETE the `Owner` (FK cascades fire only on hard delete → statements/payouts/ledger retained) + explicitly null `units.owner_id` + end the active `ManagementAgreement` + deactivate the linked portal `User`/revoke invites + honor any finalized-unpaid payout; type-the-name confirm + CAP-gated + audited. Never a destructive cascade. Read-only respected. |

---

## 4 · PAYOUT RUN — per-element (`Operator Payout Run.html`, certified engine #71 test-mode)

**Already built** (`PayoutRun` @ `app.payouts.run`, live-derived; writes via `PayoutRunActionController`, `can:payouts.manage`). Reconciles today. The wireframe adds a persisted run + SEPA + IBAN-hold.

| Element | Verdict | Backend |
|---|---|---|
| Send N transfers · Ready-to-send · Run summary | ✅ REAL-NOW + RECONCILING | `PayoutRunActionController::pay` (batch) via `OwnerPayoutService::pay` (ManualPayoutRail — records, moves NO money in test-mode). **Run total = Σ owner nets by construction** (each payout `amount_minor = statement.net_payable_minor`; per-currency, never FX-summed) (#17) |
| **TEST-MODE** boundary | ✅ REAL | `PayoutEngineMode::current()` = `test` unless a live Stripe key AND production; the rail records/simulates — **NEVER a real transfer** (#71/#45) |
| Run header + **"In review"** status + run locks | 🔨 BUILD/FIX | today the page derives live (no persisted run); `PayoutRunService::run(manual)` exists (CLI-only). **"In review" is not a real enum** — wire to a real `PayoutRun` (status running/completed) + lock; don't invent "In review" |
| **On hold** (no verified IBAN → Request IBAN) | 🚫→🔨 honest | **No IBAN-verification concept exists.** The real hold = the certified **tax-hold** (`held` status, missing `tax_id`). Honest additional gate: **no payout method on file** (real — `payout_methods` empty → can't send). "Request IBAN" = a real notification/message to the owner. NO fake "verified". |
| **Carry forward** (held → next-month prior-period adjustment) | ⚠️ partial | carry-forward is REAL + reconciling but **only for a NEGATIVE statement net** (automatic, idempotent `adjustment` ledger row, consumed into next `adjustments_minor` — #P2-1). A **held POSITIVE** amount stays `held`/owed and is paid when the hold clears — it is NOT fabricated into a next-period adjustment. Honest: the held amount remains owed on its finalized statement. |
| **Export SEPA file** | 🚫 NOT-BUILDABLE today | **No SEPA/pain.001 generator exists** — the current page deliberately refuses to name SEPA ("naming SEPA would itself be the lie"). Decision: build a real pain.001 export (test-mode, a file, no money) vs honest-disabled "Soon". |
| Transfers list (name/**IBAN**/ref/status/amount) | ✅ / 🔨 | name/ref/status/amount REAL; **IBAN masked** = join the owner's `PayoutMethod.iban_last4` (CAP_PAYOUT, masked, never full) |
| After-sending: owners notified · receipts in portal · refs attach | ✅ REAL-NOW | `PayoutSentNotification` + `OwnerPayoutSentNotification`; `OwnerRemittancePdfController`; `payouts.external_ref` |
| Read-only respected | 🔨 FIX | ⚠️ payout writes do NOT check `OperatorWorkspace::isReadOnly()` today — **add the guard** |

---

## 5 · The honest reality vs the wireframe (the decisions)

The wireframe assumes four features the codebase **deliberately does not fake**:
- **"IBAN verified"** — no `verified` column; the code explicitly refuses a fake "Verified" badge. → masked IBAN on file (real), no verified badge.
- **"No verified IBAN → hold"** — the real hold is the tax-hold; the honest IBAN gate is "no payout method on file".
- **"Export SEPA file"** — no SEPA generator exists (the page refuses to claim a scheme it can't fulfil).
- **"Carry forward the held amount"** — carry-forward is negative-net-only; a held positive stays owed.

**Real-and-reusable:** the certified payout engine (reconciling + test-mode), the owner statements/ledger/net (the SAME the owner sees), the real 18% commission, `OwnerPayoutPreference.paused` (real pause), `OwnerInviteService` (#80), the masked IBAN, the notification feed.

### Decisions CONFIRMED (founder, 2026-07-31)
- **Payout Run persistence → wire the real `PayoutRunService`** (test-mode): the operator triggers a real persisted `PayoutRun` → real header + status (running→completed, never the fake "In review") + the run locks + reconciles + idempotent.
- **Export SEPA → honest-disabled "Soon"** (no fabricated bank file; the real disbursement is the certified test-mode rail).
- **On-hold gate → the real tax-hold + an honest "no payout method on file" gate** ("Request IBAN" = a real in-app notification to the owner; NO fake "verified" badge anywhere).
- **Owner notes → build a real notes timeline** (`HasNotes` on `Owner` → multi-note authored/timestamped, "Add note" controller-routed + audited).
- (Confirmed non-choices) commission = the real 18% (never 15%); Add-owner reuses #80; pause-payouts = real `OwnerPayoutPreference.paused`; IBAN masked-only (no fake verified); remove-owner = soft-delete archive-not-destroy.

## 6 · Guards (all gates)
🔒 operator-scoped (tenant, `OwnerScope` inactive off-portal — reads `->where('owner_id', …)` explicitly); the certified payout engine #71 reused (reconciling + TEST-MODE — no real money until go-live #45); #17 the run total = Σ owner nets + the net-to-owner reconciles at the REAL 18%; IBAN masked (#28/CAP_PAYOUT, never full, operator never enters it); #20 add/edit/remove/pay controller-routed + audited; **remove archives-not-destroys** (units→null, statements/payouts/ledger/bookings retained); #39 no fabrication (no fake payout/transfer/verified-IBAN/reconcile-break; the on-hold is the real tax/no-method gate; SEPA honest). Read-only respected on every write (add the missing payout guard). Anti-drift — reuse `PayoutRunService`/`OwnerPayoutService` (#71) · `OwnerStatementBreakdown`/`OwnerLedgerAttribution` · `OwnerCommissionRate`/`ManagementAgreement` · `OwnerPayoutPreference` · `OwnerInviteService` (#80) · `Iban`/`PayoutMethod` · `NotificationFeed` (#79) · `PerformanceMetrics`.

## 7 · Build plan
**GATE 1 — Notifications + Owners list:** the notifications FIX (Unread chip + design) · the owners list (real net/needs-attention/payout-run summary reconciling; commission → the real 18%; Add-owner reuses #80) → tests → browser → commit.
**GATE 2 — Owner Detail:** real financials (net-to-owner via `OwnerStatementBreakdown` at 18%; paid-this-year; occupancy) · masked IBAN (no fake verified) · Edit-owner (commission → new agreement; pause-payouts → `OwnerPayoutPreference.paused`; statement-PDF) · **Remove-owner archive-not-destroy** (soft-delete + null units + end agreement + portal-ends + final-payout-honored, type-name confirm, audited) · notes → tests (net reconciles at 18%; remove archives-not-destroys; pause real) → browser → commit.
**GATE 3 — Payout Run:** wire a real persisted `PayoutRun` (test-mode) + lock · on-hold = the real tax/no-method gate + masked IBAN · carry-forward honest (negative-net real) · [SEPA per decision] · read-only guard → CONSOLIDATE docs → tests (run reconciles + test-mode; carry-forward) → browser → commit + PUSH.

## RESOLUTION _(GATE close — 2026-07-31, #93)_

All four pages matched to their wireframes on REAL, reconciling data; all decisions taken. Three per-gate commits, pushed. Suite green; PHPStan L5 (0); Pint clean.

**GATE 1 — Notifications feed + Owners list (`7d33c9a`).** Notifications: added a real **"Unread" filter** (`NotificationFeed` honours `?filter=unread`); Housekeeping/Channels chips honestly absent (no such notification). Owners list rebuilt on `OperatorOwners`: KPIs (owners · units · net-payable · needs-attention) + owner rows with the real statement net + a real pay-status + a payout-run summary + the **statement schedule at the REAL 18% commission** (`ManagementAgreement.commission_pct`, never 15%). The on-hold pay-status is honest (tax-hold OR no payout method on file). Add-owner already reuses #80. +4 `OperatorOwnersListTest`.

**GATE 2 — Owner Detail (`769e6e0`).** The **net-to-owner breakdown** of the latest finalized statement via the shared `OwnerLedgerAttribution` (credits − deductions == net, reconciling to the cent at the real 18%); paid-this-year + occupancy KPI; the **masked IBAN** (`Iban::masked`, CAP_PAYOUT, never full, no fake "verified"); **Edit owner** (commission → a NEW effective-dated `ManagementAgreement`; portal); **Pause payouts** — REAL via `OwnerPayoutPreference.paused` (the engine skips a paused owner); ⚠️ **Remove owner** — a careful **archive-not-destroy** (soft-delete → statements/payouts/ledger/bookings RETAINED, units unassigned, agreement ended, portal ends, type-the-name confirm); a real **note timeline** (`Owner::noteEntries`). All writes controller-routed (`OwnerManageController`), `can:users.manage`, read-only-gated, audited. +6 `OperatorOwnerManageTest`.

**GATE 3 — Payout Run (this commit).** Wired the certified **`PayoutRunService::run` (test-mode)** — a real **persisted `PayoutRun`** that RECONCILES (run total = Σ owner nets, #17) and locks (idempotent on run_key), moving **NO real money** (`PayoutEngineMode` + `ManualPayoutRail`); the **read-only guard added** to every payout write (was missing — a real safety FIX). A **test-mode banner**, a real run header (status/tallies), **masked IBAN** per transfer (CAP_PAYOUT), the honest **On-hold** card (the certified tax-hold + "no payout method on file", with a real "Request IBAN" prompt — NO fake "verified"), **Export SEPA honest-disabled "Soon"** (no fabricated bank file), and the honest carry-forward note (negative-net #P2-1). +4 `OperatorPayoutRunEngineTest` (reconciling + test-mode + read-only + gated); updated `OperatorPayoutRunTest` (SEPA now an honest disabled Soon).

**Honesty held (REFUSED / honest, never faked):** no fake "verified IBAN" (none exists); no fabricated SEPA bank file (honest-disabled); the on-hold is the real tax/no-method gate; carry-forward is the real negative-net mechanism; the payout run moves no real money (test-mode); the masked IBAN is CAP_PAYOUT + last-4 only (never full); remove archives-not-destroys (financials/bookings retained); commission is the real 18% everywhere (#39). Reconciling (#17): the net-to-owner + the run total both hold to the cent at the real 18%.
