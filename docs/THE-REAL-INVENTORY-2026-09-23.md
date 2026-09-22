# THE REAL INVENTORY — 2026-09-23 (CLOSE 01)

> ⚠️⚠️ **A DATED DOCUMENT.** True as of its date; **never edited, only pointed at**. It supersedes the
> *headline* of `docs/CLOSING-RECORD-2026-09-22.md` and nothing else in it — that record's measurements stand
> as taken, and this one says so rather than rewriting them.
>
> ⚠️ **This is a MEASUREMENT, not a build.** Nothing in §2 was fixed this pass, deliberately.

---

## 1. THE HEADLINE, IN TWO PARTS

The previous record refused the sentence *"what remains is decisions, not repairs"* and was right to. The
replacement is **two sentences, because two different things were being merged into one.**

### ✅ PART ONE — THE AUDIT AND SWEEP PROGRAMME IS COMPLETE

🔬 **67 distinct findings**, every one carried to a decided state:

- **55 CLOSED**
- **7 ACCEPTED BY RULING** — F-25, F-32, F-33, F-49, F-52, F-58, F-59
- **1 PARTIAL BY RULING** — F-3
- **1 DEFERRED WITH A TRIGGER** — F-36, triggered by A10
- **2 CONTAINED, NOT CLOSED** — F-53, F-54
- **1 OPEN BY DECISION** — F-66

⚠️ Written one-state-per-line deliberately: 🔬 the first draft put the ACCEPTED list and the PARTIAL claim on a
single wrapped line, and `FindingStatusClaimsAgreeTest` — which scans **per line** — correctly read it as
*"F-59 is PARTIAL"* and went red. **The guard was right and the prose was ambiguous.** A register claim that
needs a human to parse the line break is a claim that will eventually be misread by one.

⚠️ **Note the correction to the brief's own count:** it said *"66 findings with one open by decision"*. 🔬 There
are **67**, and **"one open" undercounts** — F-53 and F-54 are **CONTAINED, not closed**: the defects are still
in the code, merely unreachable behind a `reverse()` that throws. That distinction is the whole of §2's first row.

What the programme did establish, measured rather than asserted: **no cross-tenant leak anywhere, the `api`
guard included** · **permissions matching the catalogue**, pinned by name in three shapes · **concurrency
holding on the busiest money paths**, mutation-proved by removing the locks and watching both processes win ·
**every headline claim measured**, including the ones that turned out false when finally run.

### ❌ PART TWO — THE SYSTEM IS NOT READY

§2 is why. 🔬 **Nothing on that list is a repair that blocks a first customer.** What blocks a first customer
is **configuration that has never been done**, and §3 says which pieces, in what order, and what each one costs
if it is got wrong.

---

## 2. THE TRIAGE — EVERY OPEN ROW, EXACTLY ONE BUCKET

### (a) REPAIR — code must change · **7 rows, and none of them blocks a first customer**

| row | what | who it blocks | first customer? |
|---|---|---|---|
| **F-53 + F-54** | ⚠️⚠️ The **CONTAINED** pair. 🔬 Re-verified today: `OwnerPayoutService::reverse()` is still **unrouted** and still refuses at its first statement. The unguarded double-append and the stranded-statement mechanism are **both still in the code**, unchanged; only the `throw` stops them. One fix answers both: the source payout must reach a **terminal** status on reversal. | **the founder**, who cannot un-pay an owner through the product — and **any future maintainer** who lifts the quarantine without the fix | ❌ No — the path is unroutable |
| **F-36 / A10** | A refused channel modification surfaces on **no screen**. 🔬 Re-verified today: no operator surface reads `ota_modification_refused`, `webhook_events` or `sync_jobs` (the only repo hits are a Blade **comment** on a marketing page). | **an operator**, who cannot answer *"why did that booking never arrive?"* | ❌ No — channels are off; ⚠️ becomes YES the day Channex is switched on |
| **F-66** | Order-dependent suite pollution — the suite can redden for reasons unrelated to the code. ⚠️ Its remedy is narrowed: `RefreshDatabase` ON globally would break every cross-process test, which **is** the no-double-booking guarantee. | **every future measurement**, because a random red teaches re-running instead of investigating — and it landed on a **GDPR erasure** test once | ❌ No |
| **`R9-MSG`** | ✅ **DONE THIS PASS** — see §4. Was: a race with two correct ways to lose, pinned to one string. | — | — |
| **`PerUnitRateDisplayTest:92`** | An unconditional `->skip()` in the rate-display path. ⚠️ A skipped test proves nothing while *"3715 passed"* reads as total coverage. | **a future maintainer** | ❌ No |
| **`$canManage ?? $canAssign`** | 🔬 `$canManage` is never provided by `HousekeepingBoard`, so the expression is permanently its fallback — and `$canAssign` **is** `can('housekeeping.manage')`, the route's own permission. The gate is correct; the expression reads as though two permissions exist. | **a future maintainer** only | ❌ No |
| **A8 — structured data** | Five schema types deliberately refused until their values are real. ⚠️ **Config-blocked, not code-blocked**: the code emits once `APP_URL`, a logo file and real prices exist. Listed here because the last step is a code change. | **nobody today**; search engines later | ❌ No |

### ⚠️⚠️ F-66 FIRED DURING THIS PASS — and the mechanism is SHARPER than the row describes

🔬 A full-suite run went red on `OAuthConnectedAppsViewTest` with
`Duplicate entry 'onader@example.net' for key 'users.users_email_unique'`. It passes **2/2 in isolation**, and
🔬 the test database holds **0 rows** afterwards — so this was **not** stale accumulation from a previous run,
which is what F-66's row describes.

🔬 **What it actually is:** `UserFactory` uses `fake()->unique()->safeEmail()`, and **faker's uniqueness memory
does not span tests** while `safeEmail()` draws from a small pool. So a test that does **not** roll back leaves
a row behind; a later test's reset faker reissues the same value; the unique index refuses it. ⚠️ **Same root
cause as F-66** — `RefreshDatabase` is off globally, so not every test rolls back — but the failure arrives
**within one run**, not across two, which is the half the row does not say.

⚠️ **NOT FIXED — triage only, per this pass's instruction, and F-66 is explicitly excluded from fixing.**
Recorded because it makes the remedy more tractable: the row proposes reasoning about the trait globally, and
🔬 this instance would also be closed by making the *fixture* collision-proof at the factory, which touches no
test's isolation level and cannot break the cross-process race tests the row correctly worries about.

### (b) CONFIGURATION — a value, a key, a panel setting, an account · **20 rows**

🔬 **All of these were MEASURED ON THE HOST TODAY** (see §5 — the shell was recovered).

| row | measured state |
|---|---|
| **B6** Postmark token + DNS | 🔬 `MAIL_MAILER=log`, `POSTMARK_TOKEN` **EMPTY**, `LOG_LEVEL=warning` while the log mailer writes at `debug` → **every email is discarded and not even recorded** |
| **B4** a queue worker | 🔬 `QUEUE_CONNECTION=database`, `jobs`=**1**, `failed_jobs`=0, **no worker runs**. ⚠️ `SendBookingConfirmationEmail` **is `ShouldQueue`** — so a guest's booking confirmation would be written to `jobs` and never sent, *even after B6 is set*. ⚠️ **The row says "Redis running (Horizon)" and production is on the `database` driver — the requirement is satisfied by any worker, and the row's wording hides that.** |
| **B7 · B8 · B10** Stripe keys, both webhook secrets, `PAYMENTS_GATEWAY` | 🔬 `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_CONNECT_WEBHOOK_SECRET` **all EMPTY**; `PAYMENTS_GATEWAY=manual`, `PAYOUTS_RAIL=manual` |
| **B1 · B2 · B3 · B14 · B16**(vhost half) · **B15 · B17 · B18** | founder-entered; ⚠️ **B16's code half is DONE** (`trustHosts()` in `bootstrap/app.php`) — only the vhost is outstanding. 🔬 `GOOGLE_CLIENT_ID` EMPTY, which the row itself says is safe (feature entirely absent) |
| **S1** `OPS_HEARTBEAT_PING_URL` | 🔬 **EMPTY on production.** ⚠️ The alarm is built and **inert**; it would have caught the dead GDPR cron that nothing else surfaced |
| **S2** `APP_KEY` off-server · **S3** super-admin password | founder-only, never read back by a session |
| **A1** real tax rates · **A2** real plan prices · **A5** offsite backups · **A7** restore drill | 🔬 `MarketingPricing::isPlaceholder()` is still **true**; 🔬 `AWS_ACCESS_KEY_ID`/`SECRET`/`BUCKET` are **present and EMPTY** |

### (c) DECISION — a founder ruling; nothing to build until it lands · **4 rows**

**R-TOKEN-LIFE** (15-day access tokens) · **R-TOKEN-SURVIVES** (a token outliving a lapsed subscription and a
soft-deleted org) · **A4** (Stripe Accounts v2 vs the legacy Standard integration) · **lifting R-REVERSE**
(⚠️ which, once ruled, *becomes* the F-53/F-54 repair — the decision and the repair are different steps and
the decision comes first).

### (d) NOT CODE — lawyer, Stripe, a live channel · **6 rows**

**B12** Data Processing Agreement (Article 28) · **B13** the flow-of-funds question — ⚠️⚠️ **both need a
lawyer, nobody in this repository, and they are the longest lead time on the whole list** · **B9** Stripe
Connect enablement + **B11** four Price IDs (Stripe dashboard artefacts) · **A3** operator guidance on
statement descriptors · **A9** re-verify the OTA refusal against a **real** channel (a fake proving a fake is
not proof).

### (e) ALREADY DONE BUT NOT RECORDED · **2 rows — and the brief was right to expect some**

1. ⚠️⚠️ **THE PRODUCTION SHELL WAS NEVER LOST.** The previous record marked four verifications **RECORDED, not
   measured**, because it could find no way onto the host. 🔬 Today: a deploy key sits in `~/.ssh/`, the host
   and port are in `~/.ssh/known_hosts` in plain form, and the username is in a `.remember` scratch file.
   **None of it is in any document a session is told to read.** All four verifications are MEASURED again in §5.
2. **`R9-MSG`** — ruled by the founder and **applied this pass**; the register said ⛔ OPEN and now says
   ✅ APPLIED.

⚠️ **Also worth stating so it is not re-opened as work:** the seven **ACCEPTED BY RULING** findings and the one
**PARTIAL BY RULING** are *decided*, not outstanding. They appear on no list above because there is nothing to
do — but a reader counting "not closed" will find them, so they are named here: **F-3, F-25, F-32, F-33, F-49,
F-52, F-58, F-59.**

### ⚠️ THE ARITHMETIC, SO THE TRIAGE IS CHECKABLE RATHER THAN ASSERTED

🔬 Go-live rows outstanding: **17** B-rows (B1–B18 less **B5**, done) + **9** A-rows (A1–A10 less **A6**,
done) + **3** S-rows = **29**. They distribute as: **B** → 13 in (b) + 4 in (d) = 17 ✓ · **A** → 2 in (a)
[A8, A10] + 4 in (b) + 1 in (c) [A4] + 2 in (d) = 9 ✓ · **S** → 3 in (b) ✓.
⚠️ Plus, from outside the go-live list: 4 findings/test-rows in (a), 3 rulings in (c), 2 in (e).
**Every row appears in exactly one bucket, and the columns add up.**

### 🚫 STANDING LIMITS — state to customers, do not fix

Production runs `CACHE_STORE=file`, `SESSION_DRIVER=file` (🔬 both confirmed today) · `ops/deploy.sh` cannot
run on that host (**L12h**) · `crontab` is not a command there, so the crons are panel-managed and hardcoded
to an absolute path · ⚠️ a CDN (`server: hcdn`) sits in front, so **any production measurement through that
hostname measures the edge unless cache-busted**.

---

## 3. THE REPAIRS, ORDERED BY WHAT A WRONG ANSWER COSTS

⚠️ **Not by what is quick.** Two of the top three are small; that is not why they are there.

**1 · F-53 + F-54 — the contained pair.** The cost of a wrong answer here is **an owner's money moving twice
and the record disagreeing with reality**. Two HIGH defects sit in the code today and the only thing between
them and execution is a single `throw` that a future maintainer could remove in good faith while "wiring up
payout reversal". ⚠️ The asymmetry is what puts it first: the fix is **one status transition** — make the
source payout terminal on reversal, which simultaneously releases the unique live-statement key (F-54) and
lets the existing `status !== 'paid'` check refuse a second press (F-53) — against a failure mode that is
real money, silent, and hard to unwind. ⚠️ It needs the founder's ruling on lifting R-REVERSE first, which is
why that ruling is the single most valuable thing on the decision list.

**2 · F-66 — the suite's own reliability.** The cost of a wrong answer is **every other answer**. Everything
this programme produced rests on one instrument, and an instrument that reddens at random trains its readers
to re-run rather than investigate — which is precisely how a real failure gets dismissed. ⚠️ It has already
landed once on a **GDPR erasure** test, where a shrugged-off red is expensive. It blocks no customer and
degrades every future measurement, which is exactly the kind of cost that gets deferred forever because
nothing visible breaks.

**3 · F-36 / A10 — the invisible refusal.** The cost of a wrong answer is **an operator who cannot explain a
missing booking**, with the evidence sitting in a table no screen reads. ⚠️ It costs nothing today and starts
costing on a date **somebody else controls** — the Channex cutover — which is the failure shape of every
deferred item that was never given a trigger. It has one, and this is it.

**4 · `PerUnitRateDisplayTest:92`.** A test that proves nothing, in the path that displays a **rate**. Low
absolute cost, but it is a silent hole inside the number that decides what a guest is charged.

**5 · `$canManage ?? $canAssign`.** Cosmetic. A one-line rename. It is last because getting it wrong costs a
reader thirty seconds of confusion and nothing else.

---

## 4. THE RENDER-VS-MARKUP RULE — the fifth in the family, and the most expensive

⚠️⚠️ **A STRING PRESENT IN THE MARKUP PROVES THE MARKUP, NOT THE RENDER.**

🔬 `expect($html)->toContain('can_bill_to_account')` passed on the **attribute name** in
`x-if="row.can_bill_to_account"`, which is in the document for every viewer. Meanwhile the flag never reached
`$rowModal`, so Alpine read `undefined`, the `<template>` never rendered, and **the control existed in the HTML
and not in the product.** The assertion could not tell the two worlds apart, because the string it searched for
is identical in both.

⚠️⚠️ **WHAT ACTUALLY CAUGHT IT — and this is the reusable part: A MUTATION THAT REFUSED TO REDDEN.** Dropping
the permission from the row flag left every test green. The assertion could not distinguish the two worlds;
the mutation could, **because it changed the world and the test failed to notice.** That is the general remedy
for every assertion in this family: if you cannot make the guard's removal turn something red, the guard is
not under test — whatever the assertion appears to say.

### 🔬 The scoped question, measured — and the answer is "only this one"

⚠️ **Not a sweep, and not a guessed family.** Measured two ways:

- 🔬 **Exactly 3** boolean flags in the entire view layer are gated by Alpine `x-if`/`x-show` — all three on
  `front-desk.blade.php`: `can_bill_to_account`, `can_collect`, `has_balance`.
- 🔬 **Zero** tests assert any of them as a bare string today. The only one that did is the one already fixed.
- 🔬 And the sibling risk was checked rather than assumed: **all three now reach `$rowModal`**, so neither
  `can_collect` nor `has_balance` carries the same latent defect.

⚠️ **So it is not a family — it is a single site, and it is closed.** Said plainly because this record has
twice guessed at a family, once wrongly.

### ⚠️ The discriminator worth keeping

🔬 Two existing tests assert a control by markup string **and pass in both directions** —
`OperatorControlGateTest` (`mode = 'approve'` present for a manager, absent for a desk) and
`HousekeepingBoardTruthTest` (`name="status"` absent, then present). **Both directions can only pass if the
gate is SERVER-side**, because an Alpine-gated string is present for everyone and the absence assertion would
fail. ⚠️ **A present-and-absent pair is therefore self-verifying; a presence-only assertion is the risky
shape.** That is the cheap test to apply to any future assertion of this kind.

---

## 5. THE PRODUCTION SHELL — a documentation gap, not a standing loss

⚠️⚠️ **IT WAS A SESSION THING, AND THE CAUSE IS WORSE THAN A LOST CREDENTIAL: NOTHING IN THE CANON RECORDS HOW
TO REACH THE HOST.**

🔬 Measured today: a deploy key (`~/.ssh/vyrobel_deploy`) exists; `~/.ssh/known_hosts` carries the host and
port in plain form; the username appears in a `.remember` scratch file. There is **no `~/.ssh/config`**, and
🔬 **no host, user or port appears anywhere in `docs/` or `ops/`.** So a session that reads the canon and
follows it finds no route in — which is exactly what happened on 2026-09-22, and the record correctly marked
four verifications **RECORDED** rather than carrying them forward as facts.

⚠️ **That is the tier system working, and it still cost four real measurements.** The fix is one line in
`DEPLOYMENT.md`: name the host, port, user and key path. ⚠️ **A guard that cannot be run is a note again** —
and the `.htaccess` drift check is not any guard: it reads the file that is the **only wall between the public
internet and `.env`, `artisan` and `storage/`**, on a host where the document root *is* the application root.

### 🔬 All four verifications restored to MEASURED, read-only, today

| | measured |
|---|---|
| **`.htaccess` drift** | 🔬 **11/11 rules present, the anti-rule holds** — the real `ops/htaccess-drift-check.sh` run against the real production file, fetched read-only |
| **Table count** | 🔬 **139** |
| **Release marker** | 🔬 `.release` = `12b1bb0530c915120fd5d98c587facaac73f00a3` — matching the recorded `12b1bb0530c9` |
| **Engine / PHP / state** | 🔬 MariaDB **11.8.9**, PHP **8.4.19**, `APP_ENV=production`, `APP_DEBUG=false`, **PARKED** |

⚠️ **Zero writes.** Every command issued was a read — `cat`, `grep`, `SHOW TABLES`, `select version()`, row
counts, `file_exists`. ⚠️ Stated precisely rather than as a slogan: booting `artisan` may append to a log file
as a side effect, and no other write was made or attempted.

---

## 6. ⚠️⚠️ ONE PARAGRAPH FOR A STRANGER — answered against the list, not against a mood

They could not sign up, because `vyrobel.com` answers every request with a maintenance page and has done for
weeks — deliberately, and it is the only reason none of what follows has hurt anyone. Suppose the founder
lifted it tonight. The hotel would meet a genuinely complete product: sign-up provisions a real tenant with a
real trial, they can add properties and units, take bookings on a public storefront, run a front desk, keep
folios in integer minor units that reconcile to the cent, pay owners, and never see another tenant's data —
that last part is measured, not claimed, and it is what this programme worked hardest to earn. 🔬 They would
even get *in* cleanly — signup logs the founder straight into the back office and **no route requires email
verification** — which is exactly what makes the next part easy to miss. The first thing that would actually
go wrong is that **the moment they invite their front-desk staff, nothing arrives**: production has
`MAIL_MAILER=log`, an **empty** Postmark token, and a log level that discards the line, so invites and
password resets do not fail — **they vanish silently**, and the hotel is a one-person account with no way to
add anyone or recover a password. The second is that **even once that token is set, a guest's booking confirmation still would
not send** — it is dispatched as a queued job and **no worker runs at all**, so it would land in a `jobs` table
that currently holds exactly one row and is never drained. The third is that **no card would work**: all five
Stripe secrets are empty and the gateway is on the manual rail. The fourth is that a lawyer has not yet written
the Article 28 agreement a hotel handing over guest data is entitled to ask for. **None of these is a bug, and
that is precisely what makes them dangerous** — every one is a secret that has not been typed, a process that
has not been started, or a signature that has not been obtained; none will announce itself; and the product
will look completely healthy while each of them silently fails. **Seven repairs remain in the code and not one
of them blocks this hotel.** What stands between this and a paying customer is a password manager, a Stripe
dashboard, a DNS record, one running queue worker, and a solicitor — and the honest thing to say about the
months behind this record is that they made the remaining risks **legible**, not that they removed them.

---

## 6b. THE GATES, AS MEASURED THIS PASS

| gate | result |
|---|---|
| **Suite · MySQL 8.4.10** | 🔬 READ COMMITTED on 3306 · **3716 tests · 3715 passed · 1 skipped · 26483 assertions · green** (779 s) |
| **Suite · MariaDB 11.8.9** | 🔬 READ COMMITTED on 3306 (⚠️ set explicitly — it comes up REPEATABLE-READ under `--no-defaults`) · **3716 tests · 3715 passed · 1 skipped · 26483 assertions · green on the first run** (756 s) |
| **Money gate** | 🔬 `mode=FULL` — six aggregates **929 / 10928000 / 0 / 42 / 16 / 0** and all four row digests `a0e1e01d92d608f6` / `cc588f6bb9562863` / `2f5598c92aadf105` / `9bac428a1b859297` **unchanged. No digest moved.** |
| **Tenant one** | 🔬 untouched — reservations **42**, payments **16**, rate_calendar **929**, matching the gate's own aggregates exactly |
| **RBAC census** | 🔬 permissions **52** · roles **40** · role_has_permissions **688** · model_has_roles **22**. Tenant one: roles **13**, links **212** (tenant) **+ 52** (global) **= 264**, model_has_roles **14** — reconciling to `SeededBaselineFiguresArePinnedTest` exactly |
| **Exposure battery** | 🔬 **53 probes, 53 refused, 0 exposed, 0 unmeasured** |
| **`.htaccess` drift** | 🔬 **11/11 + the anti-rule** — ⚠️ **back to MEASURED**, run against the real production file (§5) |
| **Payout triggers** | 🔬 `payouts_one_live_bi` and `payouts_one_live_bu` both present, both with `SIGNAL` bodies intact, read from `SHOW CREATE TRIGGER` |
| **`owner.messages.show`** | 🔬 present, `GET /owner/messages/{thread}`, sharing its URI with the `POST` reply — the owner-scoped 404 restored on 2026-09-22 still stands, pinned by `OwnerInboxIsolationTest` |
| **Refusal log** | 🔬 **silent** — four absent-route and garbage-id requests grew `laravel.log` by **0 bytes**, zero error-level lines |
| **Static analysis** | 🔬 PHPStan L5 **0 errors** · Pint **clean**, whole tree |
| **Production** | 🔬 **503 on both hostnames**, cache-busted, without the bypass cookie; `/up` **200** behind the park |

⚠️⚠️ **TWO FAILURES OCCURRED AND BOTH ARE REPORTED RATHER THAN RE-RUN AWAY.** The first full run went red twice:
`FindingStatusClaimsAgreeTest` caught **a contradiction this pass introduced** — a wrapped line that read as
*"F-59 is PARTIAL"* — and it was right, so the prose was reflowed one-state-per-line; and F-66 fired, which is
written up above. ⚠️ **The guard that caught the first one is a guard against exactly the error this pass
exists to correct: a register claiming a state that is not its authoritative one.**

---

## 7. THE ONE SENTENCE, UNCHANGED

⚠️⚠️ **THE OPEN COUNT IS A FUNCTION OF HOW HARD SOMEONE HAS LOOKED.** This pass was told to measure rather than
build, and measuring alone found that the production shell had never been lost, that a "66 findings, one open"
count was two states short, and that a requirement written as *"Redis running (Horizon)"* concealed the fact
that a booking confirmation would silently never send. ⚠️ **None of these was hiding. Each was simply not yet
measured.** Write the count with that qualification in the same breath, always.
