# Vyrobel — Complete Project Audit · A/B Dual-Method × Prototype Parity

**Date:** 2026-08-07 · **Commit audited:** `653a5fb` (working tree clean) · **Method:** A/B-1 dual-method verification × A/B-2 prototype-vs-built parity · **Scope:** 7 layers × 5 realms · **Mode:** READ-ONLY on application code

---

## 1. EXECUTIVE SUMMARY

### Verdict in one line

**Vyrobel is a genuinely complete, unusually disciplined product with a sound architecture and no critical defects — but it is NOT production-ready today.** Five HIGH issues must be fixed first, two of which are user-visible breakage that the existing test suite structurally cannot catch. None is a design flaw; all five are localized and fixable in days, not weeks.

### Risk profile

| | |
|---|---|
| **CRITICAL findings** | **0** |
| **HIGH findings** | **7 raised → 5 distinct issues** (two were found twice, independently, by different layers) |
| **MEDIUM** | 35 |
| **LOW** | 20 |
| **TRIVIAL** | 3 |
| **Raised then RETRACTED after adversarial verification** | 1 |
| **Total raised** | 66 |

The **absence of CRITICAL findings is meaningful**, not an artifact of leniency: the audit specifically attacked money, isolation, and privacy with crafted mutations, and every *bounded guard the project claims* held except one (the refund race, below). Tenancy isolation, owner scoping, guest isolation, the privacy perimeter, the door-code window, and money reconciliation all survived direct attack.

### Scope covered

| Dimension | Count |
|---|---|
| Routes enumerated | **446** (180 GET page routes) |
| Pages captured and analysed | **152** |
| Prototype wireframes decoded | **125** |
| Per-page parity verdicts issued | **183** |
| Layers audited | **7** (architecture · security/privacy · running product · data/money · performance · config/deps/deploy · parity) |
| Realms | **5** (operator · owner · guest · public storefront · platform admin) |
| Audit agents | 24 (6 deep layers, 4 parity realms, 14 adversarial verifiers) |

### Dual-method agreement rate

Of **31 material claims** verified two independent ways (drive the real browser/DB **and** read the code):

- **28 AGREED** → high-confidence PASS or high-confidence finding.
- **3 DISAGREED** → all three became findings, and all three are among the most consequential in this report (§6).

The single highest-value result of the whole audit is that the disagreements clustered exactly where the project's own process lesson predicted: **places a green test suite cannot see.**

### Prototype-vs-built parity headline

> **66 FAITHFUL · 40 RATIFIED-DIVERGENCE · 26 GAP · 51 NO-WIREFRAME** (183 verdicts)

Counting only pages that *have* a prototype to compare against (132): **80% are faithful or deliberately-and-documentedly divergent; 20% carry an unratified gap.** Of the 26 gaps, **0 are HIGH**, 9 are MEDIUM and 17 are LOW/TRIVIAL. The parity claim in `docs/PROJECT-STATE.md` substantially holds.

Crucially, the **40 ratified divergences are correct engineering, not debt.** The project's `#39 no-fabrication` rule means the built product refuses to reproduce the prototypes' invented data (fake occupancy, fake channel-sync panels, invented history) and ships an honest empty state instead. This audit classified those as CORRECT, and verified the refusals are real rather than convenient.

### Top themes

1. **The guards that exist are excellent; the gaps are where a guard was never placed.** Nothing that is guarded is guarded *wrongly*. Every HIGH finding is a *missing* control on a path where the sibling path has the correct control — the refund lacks the lock that the payout has; the guest CRM lacks the `can:` gate that 115 other routes have; the queued mail lacks the tenancy restoration that `RetryableSyncJob` has. **The correct pattern is already in the codebase, adjacent to every defect.** That makes remediation unusually cheap and low-risk.
2. **The test suite's blind spot is structural, not lazy.** `Mail::fake()` asserts a job was *queued*, never that it *deserializes and renders* — so a 100%-failing production email passes 1,617 green tests. This is the project's own documented lesson ("green tests are necessary, not sufficient") reappearing in a form the prior three QA passes did not probe.
3. **Performance is structurally, not incidentally, expensive** — and it is invisible today because the dataset is tiny. 210 queries render `/app/insights` with 40 reservations; the counts scale with units/properties/months, not row count.
4. **Deployment readiness is the weakest layer**, and it is the one nobody has yet exercised: no error views, no cron definition, no security headers, no lockfile.

### Top risks (decision-level)

| # | Risk | Why it matters now |
|---|---|---|
| 1 | **Refund over-payment under concurrency** (`L1-01`/`F4-2`) | Invariant #27 — the project's flagship money bound — is defeated by a double-click. Harmless today (gateway is `manual`); becomes **real money leaving twice** the moment the documented Stripe live-key flip happens. **This must be fixed before that flip, not after.** |
| 2 | **No guest receives a booking confirmation** (`F-2b-01`/`P5-04`) | 36 permanently-failed jobs in the live DB, `tries => 1`, silent to the operator. A booking product whose booking email never arrives. |
| 3 | **Lowest-privilege staff can exfiltrate the entire guest PII database** (`F-2a-01`) | A Housekeeping account reaches `/app/guests` and bulk-exports every guest's email and phone. GDPR perimeter #28 depends on a gate that was never applied. |
| 4 | **GDPR erasure is incomplete in two stores** (`F-2b-02`, `F-2b-03`) | An erased guest's first name remains on a **public, indexable** storefront review reply; staff notes retain name/phone/health data verbatim. |
| 5 | **Scale cliff** (`P5-05`) | Missing composite indexes measured at **73× and 200× regressions at 500k reservations**. Not a today problem; a growth wall. |

---

## 2. METHODOLOGY

### A/B-1 — dual-method verification

Every material claim was checked **two independent ways**, and the two results reconciled:

- **Method A (empirical):** drive the real running app at `http://127.0.0.1:8901` — real login per role, real CSRF, real middleware, real Playwright browser for rendering/console/motion/a11y — and re-read the **database** after every mutation.
- **Method B (analytical):** read the route → controller/Livewire → service → model → migration chain, plus the DB schema and indexes.

**Agreement → PASS at high confidence. Disagreement → a finding.** A disagreement means one of: a real bug, a comment/doc that lies, a test that encodes wrong behaviour, or a UI claim the data doesn't support. This is precisely how the prior QA caught `F-GUEST-1`; here it was applied systematically (§6).

### A/B-2 — prototype-vs-built parity

All **125** prototypes in `docs/design/raw/` were decoded (124 are `dc-runtime __bundler` bundles whose real markup is a JSON-encoded `<script type="__bundler/template">`; 1 is plain HTML) into `/tmp/audit/*.decoded.html`. Each live page was captured and compared against its prototype, then classified:

- **FAITHFUL** — matches.
- **RATIFIED-DIVERGENCE** — an intentional deviation, **with a citation** to `docs/DECISIONS.md` or `docs/design/DIVERGENCE-REGISTER.md`. Auditors were required to cite; uncited deviations became GAPs.
- **GAP** — an unratified miss (a finding).
- **NO-WIREFRAME** — the route has no prototype.

**Calibration that materially changed the result:** the prototypes are populated with *invented* data. The built product deliberately refuses to reproduce fabrication (`#39`) and ships real data or an honest empty/"Soon" state. **That refusal was scored as CORRECT (RATIFIED), never as a GAP.** Scoring it naively would have produced ~40 false findings.

### Baseline and environment

`php artisan migrate:fresh --seed` → 1 org, 2 properties (`seaside-demo`, `vista-demo`), 40 reservations, 8 units, 10 guests. MySQL 8.4 (genuine MySQL), Redis live, PHP 8.4. All five role credentials exercised.

### Adversarial verification

Every HIGH/CRITICAL finding was handed to an **independent agent instructed to REFUTE it**, defaulting to RETRACTED if it could not independently reproduce the claim. **14 verdicts: 13 upheld, 1 retracted, 6 severities corrected downward.** No severity was inflated. This is why the report contains no CRITICAL findings — two candidates were downgraded on evidence, and one HIGH was withdrawn entirely.

### Honest limitations

- The dataset is **tiny**. All performance findings are therefore *latent*; thresholds are stated but most are extrapolations from measured per-unit costs, explicitly labelled as such.
- **Concurrency findings are analytical, not raced in production.** The refund race is proven by code structure (no lock, no unique key, no gateway idempotency key) and by the existence of the correct pattern in the sibling service — not by an observed double refund.
- The **platform-admin console is under-covered**: 10 of 11 routes are correctly behind mandatory 2FA, and only 1 page was captured unauthenticated. Admin parity leaned on static Blade analysis.
- Findings were produced by parallel agents; **cross-layer duplicates were deduped by hand** (§4) and are called out, because independent rediscovery is corroborating evidence.
- `npm audit` **could not run** — there is no lockfile (itself finding `F6-9`).

---

## 3. LAYER-BY-LAYER FINDINGS

### Layer 1 — Code architecture & quality

**Verdict: strong.** The layering (routes → controllers/Livewire → services → models) is consistently observed; the certified-engine pattern is real. `AssignmentService` genuinely is the sole writer of `reservation_units`/`availability`; the tenancy guard genuinely throws `TenantContextMissingException` on an unscoped tenant query (verified live). PHPStan L5 and Pint are clean. There is **no dead code** of consequence: zero orphan classes in `Services`/`Support`/`Controllers`, zero broken route→controller bindings, zero `FIXME`/`@deprecated`/`WIP` markers.

**Findings:** 1 HIGH, 4 MEDIUM, 4 LOW.

- **`L1-01` (HIGH)** — the refund bound is not concurrency-safe. See §4.
- **`L1-02` (MEDIUM)** — `CsvSafe` is declared "the ONE guard" but is imported by 2 of 12 CSV emitters; 3 controllers carry byte-identical private copies, and **all 7 owner-realm exports apply no sanitisation at all**. The anti-drift rule the project states is violated by the very class that states it.
- **`L1-03` (MEDIUM)** — `MoneyFormatter`, the declared single source for money display, has **four** implementations, and two have already drifted.
- **`L1-04` (MEDIUM)** — audit events for money-affecting changes are recorded in **controllers**, not services, contradicting the stated rule ("events are recorded INSIDE the service — no controller in this repo calls a recorder"). A service called from a job or test therefore records nothing.
- **`L1-05` (MEDIUM)** — per-owner capability is a caller-remembered check; three owner writes target an arbitrary `ownerIds[0]`.
- **`L1-06`…`L1-09` (LOW)** — a room-type id trusted across an org-scoped pre-check; the weekend-uplift formula implemented twice in different units (preview disagrees with the stored price); Blade layout components running Eloquent directly; `audit_events`/`domain_events`/`settings` deliberately outside the global tenant scope.

**Stale-documentation cluster (6, corrected in-place by evidence):** `OperatorDashboard.php:51-53` claims ADR is deferred — it is real and rendered; `GuestsList.php:84-85`, `ReservationsList.php:68-69`, `HousekeepingBoard.php:43-45` each claim a route/service doesn't exist when it ships; `owner/shell.blade.php:10-11` and `admin/shell.blade.php:10` claim placeholder nav items that are all live. **Comments understate the product** — the opposite of the usual failure, but still a trap for the next reader.

### Layer 2 — Security & privacy posture

**Verdict: the isolation architecture is genuinely sound; the authorization layer has a real hole.**

**PASS (verified by crafted attack, not assumption):**
- Four guards genuinely refuse each other: operator→`/admin` → `/admin/login`; operator→`/guest/*` → `/guest/login`; owner→`/app` → **302 → `/owner`** (invariant #26 holds).
- Tenant isolation (#38): a foreign org's resources return **404**, not 403, not data.
- Owner scoping: owned unit **200**, unowned unit **404**.
- Owner sub-user capability matrix is read by both the route gate and the UI — no drift.
- Admin console enforces **mandatory 2FA** on first sign-in (10 of 11 routes correctly gated).
- **PII encrypted at rest confirmed on raw rows** — guest email/phone, payout IBAN, platform integration secrets are ciphertext in the database.
- **#33 door code proven bidirectionally** (§ Layer 3).

**Findings:** 5 HIGH, 4 MEDIUM, 6 LOW, 2 TRIVIAL.

- **`F-2a-01` (HIGH)** — **the Guests CRM has no permission gate.** `routes/web.php:772` and `:786` carry no `Authorize` middleware, and neither do 9 guest write routes. Verified empirically: a **Housekeeping** user (whose role holds only `units.view`, `housekeeping.view`, `housekeeping.manage`, `schedule.view`) loads `/app/guests` and reads real guest emails and phone numbers, and can `POST /app/guests-bulk/export`. The permissions `guests.view`/`guests.manage`/`guests.erase` **exist and are deliberately withheld from Housekeeping** — so the intent is explicit and only the enforcement is missing. Notably `guests-bulk/anonymize` *is* gated: the destructive route was protected, the exfiltration routes were not.
  **Accurate gate coverage** (measured on resolved middleware, not the literal string `can:`): operator 117/176 gated, **32 of 124 state-changing routes ungated**; owner 60/103, 24 of 58 writes ungated. Of the 32 ungated operator writes, ~12 are legitimately self-only (profile, own 2FA, own password, notification preferences, property switch) and **20 are genuinely exposed** — 9 guest routes, 8 inbox routes (including `reply`, which sends an outbound guest-facing email), 4 room-type photo routes.
- **`F-2b-01` (HIGH)** — booking-confirmation emails permanently fail. See §4.
- **`F-2b-02` (HIGH)** — `anonymize()` does not clear `reviews.operator_response`. After a completed GDPR erasure the guest's **first name remains published on a public, indexable storefront page**, tied to a specific stay, unit and date — with the byline reading "A guest" while the reply below reads "Thank you Sofia." The erasure is *visibly* incomplete.
- **`F-2b-03` (HIGH)** — `anonymize()` does not clear `reservations.notes_internal` or `expected_arrival`. Staff free text — full name, phone, allergies, travel companions — survives erasure verbatim. `guests.phone` is an encrypted column precisely because it is sensitive; copying it into plaintext `notes_internal` and never clearing it defeats that control, violating the project's own stated rule.
- **`F-2b-04` (HIGH→MEDIUM on verification)** — guest email is written in **plaintext** to `failed_jobs.payload`, defeating encryption-at-rest and surviving anonymisation. Downgraded because `failed_jobs` is operator-internal, but it compounds `F-2b-01`: the 36 failed jobs each contain a serialized guest.
- **`F-2a-02`/`F-2a-03` (MEDIUM)** — the operator Inbox read page and all 8 write routes are ungated (a Housekeeping user sent an outbound guest-facing message in test); owner financial surfaces are ungated reads — a Housekeeping user sees every owner's statements, payouts and net position.
- **`F-2a-04` (MEDIUM)** — **no per-IP rate limit on any of the four login endpoints.** 30 attempts across distinct emails from one IP were all accepted. Credential stuffing is unthrottled.
- **`F-2b-05` (MEDIUM)** — CSV formula injection reachable from an **unauthenticated** actor: the public storefront validates guest name as `['required','string','max:120']` with no character restriction, that name flows unsanitised into 7 owner exports.
- **LOW/TRIVIAL** — room-type photo routes ungated; owner `view_only` (documented "No actions") can write owner↔operator messages; broad operator reads ungated; `SESSION_SECURE_COOKIE` unset with no production guidance; `booking_quotes` retains guest email/phone with no guest link and no retention job (out of reach of erasure); `/admin` has no IP allowlist while `/app` does.

### Layer 3 — The running product (all realms, all pages)

**Verdict: exceptionally solid at runtime.** 151 of 152 reachable routes return 200. **Zero console errors or warnings** across the JS-heaviest surfaces. **Zero raw-Blade leaks** (the documented `word@directive` trap) and zero unrendered `{{ $ }}` across 20 operator pages. No stack traces or `SQLSTATE` in any captured page.

**Findings (lead auditor):** 3 MEDIUM, 3 LOW.

- **`L3-05` (MEDIUM → HIGH at scale)** — measured query counts on a **40-reservation** dataset: `/app/insights` **210**, `/app/calendar` 162, `/app/guests` 147, `/owner/units` 147, `/owner/bookings` 133, `/app/portfolio` 118, and **the public unauthenticated storefront 116**. Wall-clock is fine today (all <80 ms) purely because the dataset is tiny. *This is a dual-method agreement* — independent code-reading agents predicted ~200 and ~161 for the two insights pages before these numbers were measured.
- **`L3-03` (MEDIUM)** — two of four text tokens **fail WCAG 2.1 AA**, measured against the real painted card background: `--vy-muted` rgb(138,128,121) = **3.77** (needs 4.5) and `--vy-faint` rgb(166,154,146) = **2.68** (fails even the 3.0 large-text bar). Declared identically in the operator, owner and guest shells; referenced by **90** and **80** view files. They carry timestamps, captions, subtitles, placeholders. `--vy-ink` (15.05) and `--vy-accent` (5.06) pass comfortably.
- **`L3-06` (MEDIUM)** — the **guest portal has zero viewport breakpoints** (its only `@media` is `prefers-reduced-motion`), producing **225 px of horizontal overflow at a 390 px phone viewport** on all 14 guest pages; the operator shell's smallest breakpoint is 860 px → **395 px overflow**. No mobile/desktop-only decision is recorded anywhere in the three authoritative docs, while every shell ships `width=device-width`. **The conversion-critical public storefront is correctly fully responsive (0 px).**
- **`L3-01` (LOW)** — `/oauth/device` returns **500** (`BindingResolutionException: DeviceUserCodeViewResponse is not instantiable`). Stock Passport registers the entire device-authorization grant, which the app's OAuth design never intended (authorization-code + PKCE only) and for which no view was published. Bounded: `POST /oauth/device/code` → 401, so no token is mintable anonymously.
- **`L3-02` (LOW)** — the operator/owner boundary is **one-directional**: `/owner` is guarded only by `['auth', SetOwnerScopeForPortal]`, so an operator loads the full owner shell (200). Correctly contained — owner data fails closed (`OwnerScope` applies `1 = 0`, page reads "No units"/"No statements") and **owner writes are refused with 0 rows written**.
- **`L3-04` (LOW)** — no skip link and no `banner` landmark, against 5 nav regions per page.

**Motion & a11y PASS:** all three documented keyframes (`vyFadeUp`/`vyShimmer`/`vyDrift`) are declared and running; entrance animations report `playState: "finished"` (they settle — no perpetual motion); `prefers-reduced-motion` is correctly honoured in **all four** realms. Zero controls without an accessible name, zero unlabelled inputs, zero heading-order jumps, `lang="en"`.

### Layer 4 — Data integrity & the money model

**Verdict: the money model is correct by construction; the concurrency story is not.**

**PASS:** money is integer-minor **everywhere** — no float/double/decimal money column exists. Reconciliation chains proved to the cent, including the public storefront (`540.00` room + `124.20` VAT@23% + `45.00` non-taxable cleaning + `15.00` city tax = **€724.20**, matching the browser exactly). `#29` re-termination, `#68` expense-approval idempotency, and no-double-booking all held under crafted attack. 23 documented passes.

**Findings:** 2 HIGH, 5 MEDIUM, 1 LOW, 1 TRIVIAL.

- **`F4-2` (HIGH)** — the `#27` refund bound is check-then-act with no row lock and no DB constraint. See §4.
- **`F4-1` (HIGH→MEDIUM)** — the owner statement **sums ledger lines across currencies**, producing a silently wrong `net_payable` and payout. Downgraded because the demo is single-currency, but the guard the project claims ("never sum across currencies") is absent on this path.
- **`F4-3` (MEDIUM)** — payout creation/retry are check-then-act with no lock and no unique index; two live payouts can exist for one statement.
- **`F4-4`/`F4-5` (MEDIUM)** — `recordPayment` accepts a foreign-currency `Money` against a folio and `FolioTotals` sums it 1:1; the guest wallet does the same.
- **`F4-6` (MEDIUM)** — the FK cascade graph would **destroy financial history and orphan payments** if any parent were ever hard-deleted.
- **`F4-7` (MEDIUM)** — **append-only is a convention only**: no model hook and no DB constraint prevents an `UPDATE`/`DELETE` on any ledger table. The invariant depends entirely on no caller ever writing that code.

### Layer 5 — Performance & scalability

**Verdict: fine today, structurally expensive, with one measured cliff.**

**Findings:** 5 HIGH (3 corrected to MEDIUM), 7 MEDIUM, 2 LOW.

- **`P5-04` (HIGH)** — every queued booking-confirmation email has permanently failed; `tries => 1` means none is retried. See §4.
- **`P5-05` (HIGH→MEDIUM)** — missing composite indexes, **measured at 73× and 200× regressions at 500k reservations** on a purpose-built probe schema. The single most valuable growth-blocker in this report.
- **`P5-01` (HIGH→MEDIUM)** — public unauthenticated memory-exhaustion on the booking storefront, **HTTP 500 proven over real HTTP**. Downgraded on impact analysis but combined with the measured 116 queries/request and the absent login throttle, the public surface is the least-defended part of the system.
- **`P5-02` (HIGH→MEDIUM)** — the **live Stripe Connect transfer runs inside a DB transaction holding a row lock**, with no timeout. Network I/O inside a lock; a slow Stripe response stalls the payout table.
- **`P5-03` (HIGH→MEDIUM)** — every concurrent booking on the **same unit** fully serializes regardless of date overlap, because the `FOR UPDATE` guard locks more broadly than the overlap it protects.
- **`P5-07`…`P5-12` (MEDIUM)** — **162 `->get()` vs 1 `paginate()`, and 0 `Cache::remember` in the entire application**; `whereDate()` at 140 call sites makes every date predicate non-sargable; 10+ tables grow forever with no pruning; `AvailabilityService::recompute()` costs **1,286 queries per call** inside a transaction; **17 of 19 mail call sites send SMTP inline**; ~109 KB of identical inline CSS re-sent uncached on every page (55% of all HTML bytes).

### Layer 6 — Config, dependencies & deployment-readiness

**Verdict: the weakest layer, and the one never yet exercised.**

**The central claim was tested and largely holds.** The audit specifically attacked "only config flips remain." A HIGH finding was raised claiming Channex is an unbuilt stub — and was **RETRACTED** on verification: the deferral is ratified in `DECISIONS.md:237` (+ 5 more places), has a written gated procedure (`docs/prompts/live-integration-checkpoint.md`), the real Channex-touching surface is **6,398 lines across 33 files** (not the 505 claimed), mapping/booking-pull/cancellation all exist, and the client **fails closed with a loud exception** rather than mis-pushing. The project's honesty about its own deferrals survived a determined attempt to falsify it.

**Findings:** 1 HIGH (retracted), 6 MEDIUM, 4 LOW.

- **`F6-2` (MEDIUM)** — **18 dependency advisories (5 HIGH)** across 3 packages, all fixable by patch/minor bumps. The project's "~10 dompdf advisories" note is out of date. Exploitability was traced end-to-end: dompdf renders 17 PDF Blade views containing user-controlled guest data.
- **`F6-3` (MEDIUM)** — owner **IBAN and account-holder name are written in plaintext to the `sessions` table** on a failed validation, defeating the encrypted column.
- **`F6-4` (MEDIUM)** — **eight business-critical scheduled commands** (guest deletions, ID purge, payouts, pre-arrival emails…) and **nothing in the repo or docs defines a cron entry**. Without one they silently never run — including the GDPR-mandated 14-day deletion and 30-day ID purge.
- **`F6-5` (MEDIUM)** — `.env.example` omits **every app-specific variable**, including the `PAYOUT_ENGINE_ENABLED` go-live flip.
- **`F6-6` (MEDIUM)** — every page in every realm loads fonts from **Google's CDN** — an external runtime dependency and a GDPR exposure for EU visitors.
- **`F6-7` (MEDIUM)** — **the documented tech-debt is wrong.** The suite is exactly as claimed (`1624 tests, 1617 passed, 11,144 assertions`, the 7 non-green being exactly the 6 `BookingImportTest` + 1 `OwnerUnitDetailViewTest`). But `BookingImportTest` passes 11/11 in isolation (genuine ordering flake, claim holds) while **`OwnerUnitDetailViewTest` fails deterministically in isolation** — it is a real defect, not the "random ULID collision" the docs describe.
- **`F6-8`…`F6-11` (LOW)** — **no custom error views exist** (`resources/views/errors/` absent) so every realm serves stock unbranded Laravel errors; **no npm lockfile**; unrotated single-file debug logging; missing `TrustProxies` will break the operator IP allowlist behind any load balancer.
- Also (from `L3`/prior agents): **no security headers of any kind** — no CSP, HSTS, `X-Frame-Options`, `X-Content-Type-Options`, or `Referrer-Policy` anywhere in the codebase. The money surfaces are framable.

### Layer 7 — Parity rollup

See §5.

---

## 4. FINDINGS BY SEVERITY (consolidated, post-verification)

### CRITICAL — none.

### HIGH — 7 raised, 5 distinct

| # | id(s) | Finding | Note |
|---|---|---|---|
| H1 | **`L1-01` + `F4-2`** | **Refund bound `#27` is not concurrency-safe** — no row lock, no DB idempotency key, no Stripe `Idempotency-Key`. Read-check-act with the gateway called *before* any row is written. Two concurrent refunds both pass; a €369 payment can be refunded €738. | **Found independently by Layer 1 and Layer 4.** The sibling `OwnerPayoutService::pay()` implements exactly the two missing layers and documents them as "two layers, both load-bearing." A comment at `ReservationFolioActionController.php:174-177` actively asserts the safety that does not exist. Harmless while the gateway is `manual`; **CRITICAL at the Stripe live-key flip.** |
| H2 | **`F-2b-01` + `P5-04`** | **Every queued guest booking-confirmation email fails permanently** — `TenantContextMissingException` on job deserialization; `tries => 1` so none retries. | **Found independently by Layer 2b and Layer 5, and confirmed by the lead auditor in the live DB:** 36 `failed_jobs`, all `BookingConfirmationMail`, timestamped minutes after a clean re-seed. Invisible to the suite because `Mail::fake()` never deserializes the job. |
| H3 | `F-2a-01` | **Guests CRM and every guest write carry no permission gate.** A Housekeeping account reads and bulk-exports the entire guest PII database. | Permissions exist and are deliberately withheld from that role — only enforcement is missing. Verified empirically. |
| H4 | `F-2b-02` | **`anonymize()` leaves the erased guest's first name on a public, indexable storefront review reply.** | GDPR Art.17 erasure completes "successfully" while remaining visibly incomplete. |
| H5 | `F-2b-03` | **`anonymize()` leaves `reservations.notes_internal` / `expected_arrival`** — name, phone, allergies, companions survive erasure verbatim. | Defeats the encrypted `guests.phone` column by plaintext copy. |

### MEDIUM — 35
Currency-mixing in owner statements (`F4-1`) · plaintext guest email in `failed_jobs` (`F-2b-04`) · payout double-create race (`F4-3`) · foreign-currency payment/wallet applied 1:1 (`F4-4`, `F4-5`) · destructive FK cascades (`F4-6`) · append-only unenforced (`F4-7`) · ungated inbox reads+writes (`F-2a-02`) · ungated owner financial reads (`F-2a-03`) · **no login rate limit** (`F-2a-04`) · CSV injection from unauthenticated input (`F-2b-05`, `L1-02`) · `MoneyFormatter` ×4 with drift (`L1-03`) · audit events in controllers (`L1-04`) · caller-remembered owner capability (`L1-05`) · missing composite indexes / 73×–200× at 500k (`P5-05`) · public storefront memory-exhaustion 500 (`P5-01`) · Stripe transfer inside a locked transaction (`P5-02`) · same-unit booking serialization (`P5-03`) · non-sargable `whereDate` ×140 (`P5-06`) · zero pagination / zero caching (`P5-07`) · unbounded table growth (`P5-08`) · `recompute()` 1,286 queries in a transaction (`P5-09`) · 17/19 mails inline (`P5-10`) · 109 KB uncached inline CSS (`P5-11`) · `/guest/trips` N+1 (`P5-12`) · 18 dependency advisories (`F6-2`) · IBAN plaintext in `sessions` (`F6-3`) · **no cron definition for 8 scheduled commands** (`F6-4`) · `.env.example` incomplete (`F6-5`) · Google-CDN fonts (`F6-6`) · `OwnerUnitDetailViewTest` is a real failure not a flake (`F6-7`) · **no security headers** · WCAG AA contrast (`L3-03`) · guest portal not responsive (`L3-06`) · query counts (`L3-05`) · plus 9 MEDIUM parity gaps (§5).

### LOW — 20 · TRIVIAL — 3
Detailed in the layer sections and §5.

### RETRACTED — 1
`F6-1` "Channex is a stub, not a flip" — see Layer 6.

---

## 5. PROTOTYPE-VS-BUILT PARITY MATRIX

| Realm | Pages | FAITHFUL | RATIFIED | **GAP** | NO-WIREFRAME |
|---|---:|---:|---:|---:|---:|
| Operator `/app` | 54 | 22 | 19 | **4** | 9 |
| Owner `/owner` | 45 | 21 | 6 | **6** | 12 |
| Guest + public storefront | 38 | 19 | 6 | **8** | 5 |
| Platform admin + auth/onboarding/billing/emails | 46 | 4 | 9 | **8** | 25 |
| **TOTAL** | **183** | **66** | **40** | **26** | **51** |

**Of the 132 pages that have a prototype: 80% faithful-or-ratified, 20% gap. Zero HIGH gaps.**

### The 9 MEDIUM gaps

| Realm | Page | Gap |
|---|---|---|
| Operator | **Maintenance board · Work order · Maintenance settings** | **One systemic defect across three surfaces:** the technician-assignment pool offers **External-Owner-realm users** (Demo Owner, Demo Owner Accountant, Demo Owner Viewer). Most consequential on Settings, where auto-assignment **routes new work orders to an owner**. |
| Admin/auth | **System states (404/500/offline)** | The entire prototype is unimplemented — `resources/views/errors/` does not exist; every realm serves stock Laravel error pages. |
| Admin/auth | Subscription paywall / billing | Two independent, contradictory pricing definitions run simultaneously. |
| Admin/auth | Email — payment reminder | Brand regression: a live guest money email renders the generic Vyrobel brand while its siblings render the property. |
| Guest | **VAT Invoice PDF** | Carries **no supplier VAT number and no registered legal name**, though both are real, stored, operator-editable org values. A tax document missing its statutory identifiers. |
| Guest | Statement of Account PDF | Is the Folio Receipt relabelled; the prototype specifies a materially different document (4-tile KPI band incl. Credited, activity table). |
| Owner | Tax Center | Identifier-contract defect — the residency `<select>` emits **country names** as option values. |

### Notable LOW/TRIVIAL gaps worth a sweep
Owner Booking Detail renders `format('D j M · 3:00 PM')` — in PHP `P` is the UTC offset and `M` the month, so the literal is corrupted · Owner Team heading double-escaped (`Team &amp; access`) · Owner Agreement leaks raw JSON (`["other"]`) · payment-reminder preview shows arithmetically impossible totals (Total − Paid = €0 but Balance = full) · Guest Help, Email Preferences and Documents hub have **no route at all** and are logged as open in the project's own docs, not ratified.

---

## 6. DUAL-METHOD DISAGREEMENTS — the highest-value findings

Three claims where **the browser/DB and the code disagreed**. Each is a place a green test suite cannot see.

### D1 — The UI and the suite say confirmation emails are sent; the database says none ever arrived
- **Method A (empirical):** `failed_jobs` = **36**, every one `App\Mail\BookingConfirmationMail`, exception `TenantContextMissingException: No organization context is set for tenant-scoped model [App\Models\Guest]`, timestamped minutes after a clean `migrate:fresh --seed`.
- **Method B (code):** the listener dispatches a delayed queued mailable; the worker deserializes it **outside any tenant context**; the global tenancy guard — the very control the project celebrates — throws. `config/horizon.php:211` sets `tries => 1`, so nothing retries.
- **Why the suite is green:** tests use `Mail::fake()` and `assertQueued`, which asserts the job was *enqueued* and never deserializes or renders it.
- **Severity: HIGH.** A booking product whose booking email never arrives, silently, in every environment with a real worker.

### D2 — A comment asserts refund safety the code does not implement
- **Method B (code):** `ReservationFolioActionController.php:174-177` states "The service is the authority — it re-reads the cumulative total inside the call, so it also covers a concurrent refund landing between the read above and this one."
- **Method A (structure):** `FolioService::refundPayment()` has **no** `DB::transaction` spanning the check, **no** `lockForUpdate`, **no** `idempotency_key` on the refund row (despite `payments` carrying `UNIQUE KEY payments_idempotency_key_unique`), and **no** `Idempotency-Key` on the Stripe call — while `charge`, `authorize` and `capture` all pass one. Re-reading inside the call closes the controller→service window; it cannot close the service's own read→write window without a lock.
- **Severity: HIGH.** The doc is not merely stale — it asserts the exact safety property that is absent, which is how a reviewer would be talked out of looking.

### D3 — The role matrix says Housekeeping cannot see guests; the running app hands over the whole database
- **Method B (code/DB):** `guests.view` / `guests.manage` / `guests.erase` all exist; the Housekeeping role holds **none** of them (only `units.view`, `housekeeping.*`, `schedule.view`). Intent is explicit.
- **Method A (empirical):** logged in as `demo-housekeeper@`, `/app/guests` returns **200** and renders real guest emails and phone numbers; `/app/owners` and `/app/inbox` likewise. `routes/web.php:772` carries no `Authorize`.
- **Severity: HIGH.** The permission model is correct and simply not enforced on the most PII-dense surface in the product.

### Retractions — dual-method working in the other direction
The same discipline **withdrew** six suspicions, and these are reported because they are evidence the method is calibrated, not lenient:
1. A `405` on `/reset-password/{token}` — an artifact of the audit's own parameter substitution (real token → 200).
2. `404`s on email-preview routes — the audit guessed the wrong catalog keys; all 7 return 200.
3. "Operator sees owner money on `/owner`" — the figures were the operator's **own** notification rows; owner data genuinely fails closed and writes are refused with 0 rows.
4. "Reduced motion leaves the operator KPI row invisible" — the audit's *simulation* omitted the `.vy-fade { opacity: 1 }` line that ships inside the real `@media` block. **The simulation was the bug, not the app.**
5. 90 contrast "failures" → 2 real token failures after excluding gradient-background false positives.
6. **`F6-1` "Channex is a stub, not a flip"** — retracted in full against 6 ratifying citations, a written gated procedure, a 6,398-line real surface, and a live fail-closed probe.

---

## 7. RISK REGISTER

| Risk | Now | At scale / at go-live | Mitigation posture |
|---|---|---|---|
| **Refund over-payment** | Contained — gateway is `manual`, no real money moves | **CRITICAL** the moment Stripe live keys are flipped | Must be a **blocker on the go-live checklist** |
| **Guest PII exfiltration by low-privilege staff** | **Live now** in any multi-staff deployment | Worsens with headcount | One-line route gates; permissions already modelled |
| **GDPR erasure incomplete** | **Live now**; public storefront exposure | Regulatory, not technical, escalation | Two `update()` clauses + regression tests |
| **Booking confirmations never delivered** | **Live now** in any real-worker environment | Directly damages the core product promise | Restore tenancy in the queued job; raise `tries` |
| **No login rate limit** | Live now | Credential stuffing at any public exposure | Laravel throttle middleware |
| **Missing indexes / N+1 / no pagination / no cache** | Invisible (<80 ms on 40 reservations) | **73×–200× at 500k reservations**; unbounded list pages | Not a launch blocker; a **growth blocker** — schedule before the first large tenant |
| **Unbounded table growth (10+ tables)** | Fine | Storage and query cost compound yearly; `availability` = units × 365 × years | Needs a retention/partitioning decision, not code today |
| **No cron definition** | Fine in dev | **GDPR deletions and ID purges silently never run in production** | Must be in the deploy runbook |
| **No error views / no security headers / no lockfile / plaintext IBAN in sessions** | Fine in dev | Day-one production exposure and unbranded failure UX | Deployment hardening sprint |
| **Channex + other account-gated integrations** | Honestly deferred, ratified, fails closed | Scoped, gated procedure exists | **Not a risk — verified correct** |

---

## 8. PRIORITIZED BACKLOG

### MUST FIX BEFORE LAUNCH (blockers)

1. **`H1` Refund concurrency** — wrap `refundPayment()` in a transaction, `lockForUpdate()` the original payment, recompute the bound inside the lock, persist an `idempotency_key` on the refund row (the UNIQUE index already exists), and thread a deterministic key through to Stripe. **Then correct the false comment.** Mirror `OwnerPayoutService::pay()`.
2. **`H2` Booking-confirmation emails** — restore tenant context in the queued mailable (the `RetryableSyncJob` pattern is already in-repo), and raise Horizon `tries` above 1 with backoff. **Add a test that renders the queued mailable rather than only asserting it was queued.**
3. **`H3` Guest CRM gates** — add `can:guests.view` to `routes/web.php:772,786` and `can:guests.manage` to the 9 write routes. Then sweep the other 20 genuinely-exposed ungated writes (inbox, room-type photos).
4. **`H4`/`H5` GDPR erasure** — extend `Guest::anonymize()` to clear `reviews.operator_response` and `reservations.notes_internal`/`expected_arrival`; add regression assertions that plant values in every column first.
5. **Login rate limiting** on all four realms.
6. **Cron entry** for the 8 scheduled commands — without it, GDPR deletion and ID purge never run.

### SHOULD FIX BEFORE LAUNCH (hardening)
Security headers (CSP/HSTS/X-Frame-Options/nosniff) · `SESSION_SECURE_COOKIE` + `TrustProxies` + HTTPS forcing · custom error views for all four realms · `CsvSafe` applied to the 7 owner exports (unauthenticated input reaches them) · dependency bumps (18 advisories, 5 HIGH) · IBAN out of `sessions` · guard `DemoSeeder`/`PlatformAdminSeeder` against production · complete `.env.example` · self-host fonts.

### POST-LAUNCH (quality & scale)
Composite indexes and the N+1 hot spots (before the first large tenant) · pagination and caching · the 3-surface technician-pool defect (owners offered as technicians — **arguably a should-fix**, since auto-assignment routes work orders to an owner) · WCAG AA token contrast · guest-portal responsive breakpoints · the 26 parity gaps (VAT invoice identifiers first — it is a statutory document) · `MoneyFormatter` de-duplication · append-only DB-level enforcement · the 6 stale docblocks · `OwnerUnitDetailViewTest` (a real failure, not a flake).

### FOUNDER GO-LIVE FLIPS (unchanged, verified honest)
Stripe live keys + Connect KYC · `PAYOUT_ENGINE_ENABLED` · Channex staging account (scoped procedure exists) · Twilio · FCM/APNs · websockets · live LLM · KYC provider · OAuth keys per env · real tax rates · email DNS. **⚠️ Sequence H1 before the Stripe flip.**

---

## 9. VERDICT

**PRODUCTION-READY: NO — but with conditions that are narrow, well-understood, and days of work rather than weeks.**

This is a mature, carefully-built system. The audit attacked it deliberately across seven layers and could not find a single CRITICAL defect. Tenant isolation, owner scoping, guest isolation, the privacy perimeter, the door-code window, integer-minor money, and the reconciliation chains all held under direct, crafted attack. The project's most distinctive claim — that it refuses to fabricate data and honestly defers what it cannot build — **was specifically targeted for falsification and survived**, including a full retraction of the audit's own attempt to prove the Channex deferral was a hidden gap.

The five HIGH findings share a single, encouraging shape: **in every case the correct pattern already exists elsewhere in the codebase.** The refund needs the lock the payout already has; the queued mail needs the tenancy restoration `RetryableSyncJob` already has; the guest CRM needs the `can:` gate 115 other routes already have; `anonymize()` needs two more clauses in a method that already clears seven other stores. These are omissions in an otherwise coherent design, not architectural faults — which is why the remediation is low-risk.

Two things genuinely change the project's own status assessment:

1. **"QA-COMPLETE, 0 open defects" no longer holds.** Not through any failure of the prior three passes — they were thorough — but because all three probed *behaviour through the UI*, and the two most serious findings live precisely where UI testing cannot reach: a queue worker deserializing outside a request, and a race window in a service. `Mail::fake()` asserting `assertQueued` is the exact blind spot.
2. **"Only external-infra flips + deployment remain" is nearly true but understates deployment.** The flips are real, honest and correctly deferred. Deployment, however, is not a flip: there is no cron definition, no error views, no security headers, no lockfile, and no deployment artifact of any kind. That is a genuine sprint of work that no prior pass scoped, because deployment was always "next."

**Recommended next actions, in order:**
1. Fix the five HIGH findings (est. 2–4 days), each with a regression test that would have caught it — in particular, a queued-mailable render test.
2. Run the hardening list before any public exposure.
3. **Sequence the refund fix strictly before the Stripe live-key flip** — it is the one finding that converts from harmless to unrecoverable at that moment.
4. Treat the performance layer as a scheduled growth workstream, not a launch blocker.
5. Update `docs/PROJECT-STATE.md` to reflect the corrected tech-debt (`OwnerUnitDetailViewTest` is a real failure) and the six stale docblocks.

**Confidence:** high on everything empirically reproduced (all HIGH findings were independently verified, two of them discovered twice by separate agents, and the two most consequential re-confirmed by the lead auditor against the live database). Moderate and explicitly labelled on the concurrency findings, which are proven structurally rather than raced, and on the scale extrapolations.

---

### Appendix — audit artifacts
- Captured page corpus (152 files) + manifest · decoded prototypes (125) · route inventory (446) — audit scratch space.
- Screenshots: `op-01-dashboard.png`, `public-storefront.png`, `.playwright-mcp/`.
- Per-agent structured results: `subagents/workflows/wf_793c133c-8c6/journal.jsonl` (24 agents, 0 errors).
- Test-suite run: 1624 tests / 1617 passed / 11,144 assertions / 353 s.
