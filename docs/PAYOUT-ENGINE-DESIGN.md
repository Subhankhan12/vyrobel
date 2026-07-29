# Vyrobel — Automated Payout Engine (TEST-MODE) · DESIGN

**Status:** ✅ **BUILT & TEST-MODE-PROVEN (2026-07-29) — all four phases shipped.** (Invariant #71.)
**Date:** 2026-07-29
**⚠️ TEST-MODE ONLY.** Real money NEVER moves in this build. Going live (live keys, real transfers, PSD2/PCI/GDPR compliance) is a **separate founder cutover — documented, not done** (`docs/STRIPE-PRODUCTION.md` §3b).

> **Build summary (what shipped):**
> - **Phase 1** (`a346d0d`) — `payout_runs` + `PayoutRun`; persisted `held` state + `payout_run_id`/`held_at`;
>   `OwnerPayoutService::hold()/releaseHold()` + widened idempotency guard; `PayoutRunService` core; `PayoutEngineMode`.
>   Proven: amount == reconciling net (#17); held → auto-release; idempotent; isolation; clearing identity.
> - **Phase 2** (`22162e0`) — `PayoutSchedule` (pure cadence: Nth business day / weekly); `runScheduled` +
>   `dueOwnerIds` (paused/minimum/due); `payouts:run` command (config-gated `PAYOUT_ENGINE_ENABLED`, TEST-MODE banner);
>   daily scheduler. Proven: only due owners; paused skipped; below-min rolls forward; same-day key idempotent.
> - **Phase 3** (`cf2bb83`) — the engine drives the certified Connect rail + webhook in TEST MODE (no code change;
>   config-bound rail). Proven end-to-end: transfer → processing → webhook → paid; failure → retry; idempotent.
> - **Phase 4** — Balance clearing model folds in `held`/`processing` (identity preserved #17); Payouts "Next
>   scheduled payout" is the real `PayoutSchedule::nextRunDate`; shared TEST-MODE banner on both surfaces; withdraw
>   stays disabled. Browser-verified as the demo owner (held state + banner + real next date).
>
> The design below is the as-built spec; the phase plan (§11) records what each phase delivered.

---

## 1. What this replaces

Today, owner payouts are **operator-triggered, one statement at a time** (the `/app/payouts/run` page → `PayoutRunActionController` → `OwnerPayoutService`). The owner-facing "next payout" is an honest placeholder: the Payouts page's **"Upcoming schedule"** block reads *"Projected future payouts will appear here once scheduling is wired"* (`payouts.blade.php:240`), and Balance's **"Withdraw early"** is disabled (*"activates with the live payments connection"*). `owner_payout_preferences` (frequency / payout_day / minimum / paused) is stored but **consumed by nothing** — it's a stated preference, not a schedule.

This design builds the **real automated engine** — a scheduled run that consumes those preferences, selects due owners, computes reconciling amounts, and disburses through the **existing** Stripe Connect rail in **test mode** — and upgrades the owner surfaces to reflect real engine state, **clearly labelled TEST MODE until the founder's live cutover.**

---

## 2. What already exists (REUSE — do not rebuild)

The investigation (3 read-only sweeps + firsthand reads) found the payout *primitives* are substantially built and **already test-proven**:

| Primitive | Where | State |
|---|---|---|
| `Payout` model + append-only lifecycle | `app/Models/Payout.php` | ✅ real |
| State machine `pending → processing → paid \| failed \| reversed` | `OwnerPayoutService` (guarded transitions) | ✅ real |
| `createFromStatement()` — amount = statement `net_payable_minor`; idempotent; zero=skip; negative=carry-forward | `OwnerPayoutService:43` | ✅ real |
| `pay()` — **double-transfer guard** (`lockForUpdate` + re-check `pending` inside the txn) + **tax-hold** (throws `PayoutHeldException` before any transfer) | `OwnerPayoutService:98` | ✅ real |
| `confirmTransfer()` — Connect webhook `processing → paid`, idempotent | `OwnerPayoutService:156` | ✅ real |
| `retry()` / `reverse()` — append-only supersede | `OwnerPayoutService:176/203` | ✅ real |
| `StripeConnectPayoutRail` — real `createTransfer`, correct-owner-only, idempotency-keyed, refuses no-account / payouts-not-enabled | `app/Payouts/Rails/StripeConnectPayoutRail.php` | ✅ real (test-mode via `FakeStripeClient`) |
| Connect webhook `account.updated` + `transfer.created/updated` → `confirmTransfer`, `webhook_events` idempotency | `StripeConnectWebhookController` | ✅ real |
| `StripeSafetyGuard` — refuses a **live** key outside production; `sk_test_` allowed | `app/Payments/Stripe/StripeSafetyGuard.php` | ✅ real |
| `owner_payout_preferences` — frequency / payout_day / minimum_minor / paused | `OwnerPayoutPreference` | ✅ stored, **unused by any scheduler** |
| Clearing model `Available + On hold + In transit + Paid == Net position` (#17) | `OwnerBalance` | ✅ real, reconciling |
| Owner Payouts / Balance pages (honest-gated) | `OwnerPayouts` / `OwnerBalance` | ✅ real, placeholders where gated |

**Proven by existing tests** (must stay green): `OwnerPayoutTest`, `OwnerPayoutStripeConnectTest` (transfer + double-transfer guard + webhook), `OperatorPayoutRunTest`, `OwnerPayoutsViewTest`, `OwnerPayoutMethodTest`.

**Conclusion:** the engine should **orchestrate** `OwnerPayoutService` on a schedule — it must **never** reimplement transfer, amount, or state logic. The build is small and mostly *new, additive* code around a certified core.

---

## 3. The genuine gaps (what to BUILD)

1. **A scheduled, preference-driven RUN** — nothing reads `owner_payout_preferences` to select due owners and drive create+pay.
2. **A persisted `held` state** — today a tax-hold is a *thrown exception* that leaves the payout `pending`; the engine needs `held` as a real, visible, auto-releasing status.
3. **A run record** (`payout_runs`) — an auditable sweep: when it ran, which owners, totals, mode (test/live), outcome.
4. **The schedule surfacing** — replace the "Upcoming schedule" placeholder + Balance cadence with the **real** next scheduled run, TEST-MODE-labelled.
5. **A TEST-MODE boundary** — in code, logs, and UI, so no one can mistake a test run for real money.

---

## 4. Data model

### 4.1 `payout_runs` (NEW — platform-level audit record)

A scheduled sweep is cross-tenant (all orgs), so — like `webhook_events` and platform activity — it is a **platform-level** table created/read under `runAsSuperAdmin`. It is an *audit + idempotency* record; owner/operator surfaces still read `payouts`, not this table.

| Column | Type | Notes |
|---|---|---|
| `id` | ulid PK | |
| `run_key` | string, **unique** | `scheduled:YYYY-MM-DD` (one scheduled run per day) or `manual:{ulid}` — the idempotency anchor |
| `trigger` | enum(`scheduled`,`manual`) | |
| `mode` | enum(`test`,`live`) | stamped from `PayoutEngineMode::current()`; **`test` in this entire build** |
| `status` | enum(`running`,`completed`,`failed`) | a run is created `running`, finalised `completed`/`failed` |
| `owners_selected` | uint | due owners this run touched |
| `payouts_created` | uint | new pending payouts created |
| `payouts_paid` | uint | reached `paid`/`processing` this run |
| `payouts_held` | uint | held (tax-hold) this run |
| `payouts_failed` | uint | rail failures this run |
| `totals` | json | `{currency: minor}` — Σ amounts disbursed, per currency (reconciles to the payouts) |
| `notes` | string, nullable | honest summary / first error |
| `started_at` / `finished_at` | timestamp | |
| `timestamps` | | |

### 4.2 `payouts` (EXTEND — additive)

- **Add `held` to the status enum** (raw `ALTER`, mirroring the `processing` migration): `pending, processing, paid, failed, reversed, held`.
- **Add `payout_run_id`** (nullable FK → `payout_runs`, `nullOnDelete`) — links a payout to the run that created it (manual/legacy payouts keep null).
- **Add `held_at`** (nullable timestamp) — when the tax-hold last held it.

### 4.3 `owner_payout_preferences` (READ — unchanged schema)

Consumed as-is: `frequency` (`monthly|twice_monthly|weekly`), `payout_day` (business-day/weekday index 1–5), `minimum_minor` (roll-forward threshold, minor units), `paused`.

**No new ledger entry_type.** A payout is deliberately **not** written to `owner_ledger` — the reconciling clearing model tracks disbursement by *payout status*, not a ledger line, and adding a `payout` ledger row would double-count and break `net == Σ ledger`. This preserves the existing #17 identity (see §7).

---

## 5. The state machine

```
                          (engine or operator)
   finalized statement ──── createFromStatement() ───► [pending]
   (net_payable > 0)                                      │
                                                          │ pay()  (row-locked; tax-hold checked FIRST)
                                   tax-hold ┌─────────────┼─────────────┐ rail failure
                                            ▼             ▼             ▼
                                        [held] ◄──┐   [processing]   [failed]
                                            │     │       │             │ retry()
                                releaseHold()│     │      │ transfer.created webhook
                                (tax now on) │     │      ▼             ▼
                                            └──► [pending]          [paid]  (manual rail: pay()→paid directly)
                                                                        │ reverse()
                                                                        ▼
                                                                   [reversed]
```

- **`held`** is the one NEW persisted state. The engine catches the existing `PayoutHeldException` from `pay()` and calls a NEW `OwnerPayoutService::hold($payout)` → `status='held'`, `held_at=now()`, audited. `pay()` itself is **unchanged** (still throws), so the manual operator path is undisturbed.
- **`releaseHold()`** (NEW) — a later run flips a `held` payout whose owner **now** has tax details back to `pending`, then `pay()` runs normally. Auto-release, no manual step.
- **Idempotency fix (required, careful):** `createFromStatement`'s existing "already has a payout" guard checks `status IN ('pending','paid')`. It must widen to `('pending','processing','held','paid')` so a re-run never creates a **second** payout for a statement that is already processing or held. This is a small change to a certified service — mutation-tested + full existing suite must stay green (Phase 1).

Every transition is audited (`AuditEventRecorder`) + emits a domain event (`DomainEventRecorder`), exactly as today (#20).

---

## 6. The schedule (consume preferences)

`PayoutRunService::selectDueOwners(CarbonImmutable $today)` picks owners where **all** hold:

1. **Not paused** (`paused = false`).
2. **Due today** per `frequency` + `payout_day`:
   - `monthly` → the **Nth business day** of the month equals today (N = `payout_day`, 1–5; weekends skipped).
   - `twice_monthly` → the Nth business day of the month **or** the Nth business day on/after the 16th.
   - `weekly` → today's weekday equals `payout_day` (1=Mon … 5=Fri).
   - The business-day/weekday rule is one small, pure, **unit-tested** helper (`PayoutSchedule`), so the cadence is deterministic and testable (no fabricated dates).
3. **Has payable statements** — ≥ 1 finalized + locked + unpaid statement with `net_payable_minor > 0`.
4. **Meets minimum** — Σ payable `net_payable_minor` (per payout currency) **≥ `minimum_minor`**; below-minimum owners are **skipped** (their statements simply wait for a later run — a real roll-forward, never a partial pay).

`nextRunDate(prefs, today)` (pure) drives the owner-facing "next scheduled payout" date — the first future date the cadence rule fires. This replaces the "Upcoming schedule" placeholder honestly.

`run(trigger, today)`:
1. `firstOrCreate` a `payout_runs` row on `run_key` (**idempotent** — a second run the same day resumes the same record, never double-pays).
2. Under `runAsSuperAdmin`, for each due owner (scoped to their org via `TenantContext::forOrganization`): for each payable statement → `createFromStatement()` → `pay()` (catch `PayoutHeldException` → `hold()`); also `releaseHold()` any of that owner's `held` payouts that are now tax-clear, then `pay()`.
3. Tally counts + per-currency totals; mark the run `completed` (or `failed` + note on a fatal error).

**Reserve** (`net − paid − held − reserve`): a reserve knob is **designed but defaults to 0** (no reserve model exists; inventing a non-zero reserve would be fabrication). Documented as a future extension, not built.

### 6.1 The command + wiring

- `php artisan payouts:run` → `PayoutRunService::run('scheduled', today())`.
- Registered in `routes/console.php` (e.g. `->dailyAt('06:00')`), **guarded by a config flag `payouts.engine_enabled` (`PAYOUT_ENGINE_ENABLED`, default `false`)** — the scheduler is inert until deliberately enabled, so it never auto-runs on a fresh deploy. The command also refuses/records `mode` via `PayoutEngineMode` (below).
- Operators can also trigger a manual run (`trigger='manual'`) via the existing `/app/payouts/run` surface (a thin addition), reusing the same service.

---

## 7. Reconciliation (#17) — the invariant that must never bend

- **Per payout:** `amount_minor == statement.net_payable_minor` (already enforced by `createFromStatement`).
- **Per run:** `totals[currency] == Σ amount_minor` of the payouts that reached paid/processing this run, per currency (never summed across currencies — no silent FX).
- **The clearing identity is preserved:** `Available + On hold + In transit + Paid == Net position`, per currency, to the cent. The engine only moves a payout through its statuses; it adds **no** ledger line, so `net == Σ owner_ledger` is untouched. A payout flows: ledger unstamped (**Available**) → statement finalized/stamped → payout `pending`/`held` (**On hold**) or released `pending`/`processing` (**In transit**) → `paid` (**Paid**). The engine finalises the payout for a statement in the same run it processes it, so the identity holds after every run (the existing demo already relies on this).
- **Mutation-proven** in every phase: the amount equals the reconciling statement net; a held owner is genuinely held (net not disbursed); run totals reconcile to the payouts.

---

## 8. Stripe Connect TEST MODE integration

The rail + webhook already exist. Phase 3 **drives** them from the engine and proves them end-to-end in test mode:

- **Rail:** `PAYOUTS_RAIL=stripe_connect` binds `StripeConnectPayoutRail`. `pay()` → `createTransfer` (idempotency-keyed `owner_payout:{id}`) → `PayoutResult(pending: true)` → `processing`.
- **Test-mode execution:** either the in-memory `FakeStripeClient` (`STRIPE_FAKE=true`, genuine HMAC signatures — used in CI/local) **or** real Stripe **test** API with `sk_test_` keys (`STRIPE_FAKE=false`). `StripeSafetyGuard` structurally forbids a live key outside `production`, so **real money cannot move**.
- **Webhook:** `transfer.created/updated` → `confirmTransfer` → `paid` + statement `paid`; `account.updated` → `stripe_connect_payouts_enabled`. Receiving-side idempotency via the `webhook_events` unique index (replay-safe).
- **Failure:** a rail failure result → `failed` + reason → `retry()` re-queues a new pending row. (Stripe transfers rarely "fail" post-create; a reversed/failed connected-account payout would extend `StripeConnectWebhookController` — noted as a test-mode extension point, driven by `FakeStripeClient` in tests.)
- **Idempotency (both layers):** the DB row-lock + status re-check in `pay()`, and the deterministic Stripe Idempotency-Key — both already proven by `OwnerPayoutStripeConnectTest`.

### 8.1 The TEST-MODE boundary (explicit, unmissable)

- **`PayoutEngineMode::current(): 'test'|'live'`** (NEW, pure) — `live` only when the configured Stripe secret is a live key **and** `app()->isProduction()`; `test` otherwise. In this entire build it returns `test`.
- **Code/logs:** every run logs `PAYOUT RUN [TEST MODE — no real funds move]`; the mode is stamped on `payout_runs.mode`; the command prints a bold test-mode banner.
- **UI:** a clear banner on the affected owner surfaces — *"TEST MODE — automated payouts run in test mode; real transfers activate when your account goes live."* Never implies real money is moving.
- Real money moving is a **separate founder cutover** (§10), never done in this build.

---

## 9. Surfacing the engine (Phase 4) — Option A → real engine, TEST-MODE-labelled

- **Payouts page:** the "Upcoming schedule" placeholder → the **real** next scheduled payout date (`PayoutSchedule::nextRunDate` from the owner's preferences) + the last run's summary + real payout history (now with run linkage + the `held` state in the lifecycle stepper). A **TEST MODE** banner. The still-gated bit (real money movement) stays labelled "activates at go-live".
- **Balance page:** In transit / Available already reflect real payout state (`processing`/`pending`/`held`); add the honest TEST-MODE note. **"Withdraw early" stays disabled** — there is no owner-initiated rail; an off-cycle run is engine/operator-driven, not an owner "withdrawal".
- **Honesty:** nothing implies real money moves; `held`/`in transit`/`paid` are all real engine states; no fabricated dates (the date is a real deterministic cadence computation).

---

## 10. The LIVE cutover (founder — documented, NOT done)

Recorded in `docs/STRIPE-PRODUCTION.md` (extended by this work). Going live requires, as a deliberate founder step:

1. **Keys/config:** live `STRIPE_SECRET` (`sk_live_`), `STRIPE_FAKE=false`, `PAYOUTS_RAIL=stripe_connect`, `PAYOUT_ENGINE_ENABLED=true`, the live account + Connect **webhook secrets**, in a `production` environment (so `StripeSafetyGuard` permits the live key).
2. **Connect onboarding for real:** each owner completes real Stripe Connect (Standard) onboarding + KYC; `stripe_connect_payouts_enabled` flips true only via the real `account.updated` webhook.
3. **Compliance decisions (founder + counsel):** PSD2/SCA, PCI (SAQ A — no raw PAN, already held), GDPR (owner PII, retention), money-transmission/agent-of-payee posture, tax withholding/reporting. These are **business + legal** decisions, not code.
4. **Go-live checklist:** a dry-run scheduled test-mode run reconciles to the cent; enable the flag; monitor the first live run; the double-transfer + idempotency guards are already proven.

Until every step is done by the founder, the engine runs **test-mode only** and the UI says so.

---

## 11. Phase plan (gated; per-gate commit; suite green + PHPStan L5 + Pint throughout)

- **PHASE 1 — the run model + state machine (no Stripe).** `payout_runs` migration + model; `payouts` enum `held` + `payout_run_id` + `held_at`; `OwnerPayoutService::hold()`/`releaseHold()`; the `createFromStatement` idempotency widen; `PayoutRunService` core (select payable statements per owner, create+pay via the **manual** rail, hold on tax-hold, tally the run). **Mutation-prove:** amount == reconciling statement net (#17); a tax-held owner is genuinely `held` (nothing disbursed); states transition; run totals reconcile. Existing suite green.
- **PHASE 2 — the schedule + command.** `PayoutSchedule` (due/next-date, pure + unit-tested); `selectDueOwners` (frequency/day/minimum/paused); `payouts:run` command + `PAYOUT_ENGINE_ENABLED` flag + scheduler entry. **Mutation-prove:** paused skipped; below-minimum skipped (roll-forward); right owners on the right day; **idempotent** (same-day re-run creates no second payout). Existing suite green.
- **PHASE 3 — Stripe Connect TEST-MODE.** Drive the engine with `PAYOUTS_RAIL=stripe_connect` + `PayoutEngineMode` boundary; failure/retry path; extend the webhook if needed. **Mutation-prove (fake + signature-genuine):** a run initiates a real test transfer → `processing`; the webhook → `paid`; a failed transfer → `failed` + retry-able; idempotent (webhook replay safe, no double transfer). Existing suite green.
- **PHASE 4 — surface + consolidate + push.** Upgrade Payouts/Balance to real engine state, TEST-MODE-labelled; consolidate `PAYOUT-ENGINE-DESIGN.md` (built), `STRIPE-PRODUCTION.md` (cutover), `DECISIONS.md`, `PROJECT-STATE.md`, the invariant list; browser-verify a test-mode run; **STOP** with a report. Existing suite green; `migrate:fresh --seed` clean; push all.

---

## 12. Standing guarantees held throughout

⚠️ **TEST-MODE ONLY — real money never moves** (`StripeSafetyGuard` + test keys/fake; a bold boundary in code/logs/UI; live = the founder's separate cutover). Reconciles to the cent (#17). Owner-scoped (#28). Every write/transition controller-routed or command + audited (#20). Reuse the certified `OwnerPayoutService` + Connect rail + webhook + statement computation + preferences (anti-drift — never reimplemented). **Disturb nothing existing** (the green suite is the proof). Mutation-prove every transition + the reconciliation + the test-mode transfers.
