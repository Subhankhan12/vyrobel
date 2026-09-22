# THE CLOSING RECORD — 2026-09-22

> ⚠️⚠️ **SUPERSEDED ON ITS HEADLINE — AND ONLY ITS HEADLINE — BY `docs/THE-REAL-INVENTORY-2026-09-23.md`.**
> This document is dated, so it is **pointed at rather than edited**: every measurement below stands exactly as
> it was taken. What changed the next day is the framing. §0 here was right to refuse *"what remains is
> decisions, not repairs"*, but it stopped at a refusal; the replacement is **two sentences** — *the audit and
> sweep programme is complete* **and** *the system is not ready* — with a triaged inventory behind the second.
> ⚠️ **Two things below are now known to be wrong, both in this document's favour being overstated:**
> 🔬 §3.4's *"this session has no shell on that host"* was true of that session and **false of the deployment** —
> the access material existed and was merely undocumented, and all four verifications it marked 📄 RECORDED are
> 🔬 MEASURED again in the successor. 🔬 And §3.1b's `R9-MSG` has since been **ruled and applied**.


> ⚠️⚠️ **THIS IS A DATED DOCUMENT.** It is true as of the date in its title and is **never edited, only
> pointed at**. The live surfaces — the register, `KNOWN-LIMITS.md`, `CLAUDE.md`, `GO-LIVE-REQUIREMENTS.md` —
> must be true *now* and are swept when they stop being. This one is a photograph.
>
> ⚠️ **Read `docs/AUDIT-SUMMARY.md` for the findings and the rulings.** This document does not restate them.
> What it does is answer three specific claims, name what was measured, and carry the inventory forward.

---

## 0. ⚠️⚠️ THE THREE CLAIMS THIS RECORD WAS ASKED TO CLOSE ON — AND WHICH OF THEM IS TRUE

The pass was set with a headline: *no known defect blocks a customer · every claim the project makes is
measured rather than asserted · and what remains is decisions, not repairs.*

**Two of those three are true. The third is not, and signing off on it would have been the single most
expensive sentence in this document.**

| claim | verdict | what it rests on |
|---|---|---|
| **No known defect blocks a customer** | ⚠️ **TRUE OF THE CODE, FALSE OF THE PRODUCT** | No open register finding stops a customer transacting. ⚠️ **But the thing that stops a customer today is not a defect — it is configuration that was never done**, and a customer cannot tell the difference. See §6 and §7. |
| **Every claim is measured rather than asserted** | ⚠️ **CLOSER THAN IT HAS EVER BEEN, AND NOT ABSOLUTE** | This pass alone found a document that had asserted a security control it did not have (§2.4) and a control that existed in the HTML and not on the screen (§2.2). ⚠️ **Both were found by measuring something that had been asserted for months.** The honest form of this claim is: *every claim someone has thought to measure*, and **the open count is still a function of how hard someone has looked.** |
| **What remains is decisions, not repairs** | ❌ **FALSE AS STATED** | 🔬 At least four things outstanding are repairs or configuration, not decisions: **F-66** (the suite can redden for reasons unrelated to the code — ⛔ OPEN, deliberately not fixed) · **F-53/F-54** behind the quarantined `reverse()` (real defects, contained by a method that throws) · **F-36/A10** (a refused channel modification surfaces nowhere — deferred with a trigger) · and **every ⛔ BLOCKING row in `GO-LIVE-REQUIREMENTS.md`**, which is mostly secrets and a lawyer. ⚠️ What IS true is the narrower, still-large claim: **nothing outstanding is an unknown.** |

⚠️⚠️ **Why the third claim matters more than the first two.** "What remains is decisions" is the sentence a
founder repeats to an investor, a first customer, or themselves at 1am. Each of the four above is small; none
is secret; all are written down. But *decisions* and *repairs* budget differently, and a plan built on the
wrong one of those two words runs out of time at the worst moment.

---

## 1. THE COMMIT

| | |
|---|---|
| **branch** | `main`, working tree clean |
| **this pass added, in order** | `d0513a10` — the bill-to-account control, its undo, and the gate that would not redden<br>`2924120d` — the three unreferenced routes decided<br>`ff4fa7b8` — the OAuth consent correction and the four recorded facts<br>*(one more)* — the reversal of the `owner.messages.show` removal, plus this record |

⚠️ **The fourth commit is where this pass admits it was wrong**, and it is deliberately not folded into the
third: `2924120d` removed `owner.messages.show` and the commit message argued for it at length. Rewriting
history to hide that would remove the only durable evidence of §2.5 — **the reasoning that was wrong is more
useful than a tidy log**, and a reader who finds the removal commit needs to find the restoration next to it.

🔬 Engines left as found: **MySQL 8.4.10 running on 3306 at READ COMMITTED**, MariaDB shut down. Three
organisations reachable. ⚠️ Stated because the engine gate swaps them, and leaving the wrong one running is a
silent trap for the next session.

---

## 2. WHAT THIS PASS ACTUALLY DID

### 2.1 ⚠️⚠️ A REFUSAL NAMED THREE REMEDIES AND ONLY ONE HAD A CONTROL

`CheckOutReservation` refuses a check-out with: *"Take the payment, **bill it to a company account** if they
settle later, or post an adjustment…"*. 🔬 `app.reservations.bill-to-account` had **no view, no Livewire, no
PHP and no URI anywhere** — its only mention in the repository was a test. `unbill-from-account` had not even
that: **zero tests of any kind.**

The control was put where the refusal is **read** — 🖥 the exception lands on the front-desk board via
`backToBoard()`, so the remedy is in the check-out modal, not two screens away. The undo was built beside it,
because a billing action with no undo is a one-way door on a money path.

🖥 **Driven end to end as a real clerk**, in the disposable tenant: refusal on the board → bill through the
modal → **the check-out then succeeded** (the guard already honoured `billed_to_account_id`; only the control
was missing) → balance **still €45.00**, owed by the account rather than the guest. The undo was driven too,
including a **dismissed** confirm, which changed nothing. **No money moved in either direction.**

### 2.2 ⚠️⚠️ AND THE PASS SHIPPED THE EXACT DEFECT IT EXISTS TO REMOVE, THEN CAUGHT IT

The first version of that control put the permission flag on the row mapper but **not into `$rowModal`**, the
compact payload `@js()` hands to Alpine. So `row.can_bill_to_account` was `undefined`, the `<template x-if>`
never rendered, and **the control existed in the HTML and not on the screen.**

⚠️ It was invisible because the test asserted `toContain('can_bill_to_account')` — a string the literal
`x-if="row.can_bill_to_account"` satisfies on its own. **Green for the wrong reason**, and green about
precisely the property under test.

⚠️⚠️ **It was caught by a mutation that refused to redden.** Dropping the permission from the row flag left all
six tests passing. A guard never seen to fire is not a guard — so the question became *why can nothing observe
this flag*, and the answer was that nothing ever read it.

🔬 **And the reason the mutation could not redden even after the payload was fixed is itself worth keeping:**
all four roles holding `reservations.checkout` also hold `folios.manage`, so **the seeded catalogue cannot
produce a clerk who may check out but may not bill.** That is a fact about the seed, not the product —
`app.roles.permissions` lets a Group Admin revoke exactly that permission — so the test now builds that state,
and 🖥 so did the browser, in the disposable tenant, where the control vanished while the rest of the modal
stayed intact. Permission restored and proven restored.

### 2.3 THE LAST THREE UNREFERENCED ROUTES — TWO CONTROLS, AND ONE THAT TURNED OUT NOT TO BE AN ORPHAN

| route | decision | why |
|---|---|---|
| `app.housekeeping.photos.destroy` | **A CONTROL** | 🔬 Three photo families exist (housekeeping · maintenance · room-types) and **two of the three already had a delete affordance**. Housekeeping was the exception: proof photos were uploaded and displayed, and a wrong shot was permanent. A missing member of an established family is an oversight. Built in that family's idiom; 🖥 driven end to end — **row and bytes both gone.** |
| `owner.welcome` | **A CONTROL — the door, not a button** | 🔬 Real, honest, three-test onboarding **nobody was ever sent to**: both invite paths landed on the dashboard, and the 23-item owner nav did not include it. Its four cards each link to a page already in that nav, so its only value is first-run sequencing — worth nothing unless someone is routed through it, and its own terminal CTA ("Go to your dashboard") proves it was built as a pre-dashboard step. The owner-invite branch now lands there; the owner-**team** invite deliberately does not. |
| `owner.messages.show` | ⚠️⚠️ **REMOVED, THEN RESTORED THE SAME DAY — SEE §2.5** | The removal was wrong. Every measurement behind it was true and the conclusion was false. |

### 2.5 ⚠️⚠️ THE DECISION THIS PASS GOT WRONG, AND WHAT CAUGHT IT

`owner.messages.show` was removed as an unreferenced orphan. The census behind that was:

- 🔬 no view, component, Mailable, notification or JS names it — **true**
- 🔬 no test names it — **true, and fatally incomplete**
- 🔬 `OwnerMessageReceivedNotification`, the one thing that would deep-link a thread, points at the **list** — true
- 🔬 710 persisted notification rows carry no `/owner/messages/<id>` URL — true

⚠️⚠️ **Every one of those is true and the conclusion was still false.** Three cases in
`OwnerInboxIsolationTest` reach the route **by URL** — `->get('/owner/messages/'.$thread->id)` — so a search
for the route **name** could not see them. ⚠️ **SWEEP THE ROUTE, NOT THE TEXT.** A name-search is blind to the
callers that build the path themselves, which is the same blind spot that hides a JS-built URI — the very
pattern this pass had *already* had to see past, one item earlier, to find the housekeeping photo pair.

⚠️⚠️ **And the cost was not a dead link — it was an isolation boundary.** Those tests pin that a cross-owner
or guest thread is *"genuinely a 404 through the portal, not merely absent from a list"* and *"not a 403 that
leaks its existence"*. The owner-scoped `findOrFail` in that route **is** the mechanism producing the 404.
🔬 With it gone, the same URL returned **405**, because the POST reply route occupies the identical URI. The
removal did not delete a dead end; it replaced an owner-scoped refusal with an unscoped one, on the two-inbox
boundary.

⚠️ **What caught it was the full suite, not the targeted run.** The item's own tests were green, the three
neighbouring suites I chose to run were green, and the defect surfaced only when everything ran together.
⚠️ **The route is restored, and its comment now says what it is actually for**: the GET half of a live URI,
whose job is to refuse correctly when someone types or guesses a thread id. The redirect to `?c=` is the
convenience; the scoping is the purpose. The *original* comment — *"a deep-link redirects to it"* — was
genuinely wrong, and correcting that was worth doing; **deleting the route on the strength of it was not.**

### 2.4 A DOCUMENT THAT OVERSTATED A SECURITY CONTROL

`OAUTH-DESIGN.md` said the owner *"must actively Authorize"*, unqualified. 🔬 Measured in the installed
Passport: the request is **auto-approved with no screen** when it carries no `prompt=consent` and the owner
already holds a live token for that client covering the requested scopes.

⚠️ **The behaviour was not touched** — it is standard OAuth 2.0. The document moved. **The consequence is now
stated: an app authorized once can silently re-obtain tokens for the same or narrower scopes, so revoke is the
owner's only real control after the first grant.** A document that overstates a control is worse than one that
omits it: it tells a reader a prompt will catch what it will not.

Four measured-but-never-decided facts are recorded in that file's new §12 (a token can be **issued** to a
non-owner and reaches nothing · 15-day access tokens · a token survives a lapsed subscription · and a
soft-deleted organisation). **Two are in the register as ⛔ OPEN rulings** — `R-TOKEN-LIFE` and
`R-TOKEN-SURVIVES` — with measurements and a recommendation each. **This session did not decide them.**

⚠️ **The shape the last two share:** the API did not decide to ignore organisation state, it **inherited the
absence of a gate the web side happens to have**. The certified scopes answer *whose rows?*; these ask
*should this tenant be served at all?* — which no isolation mechanism was ever going to answer.

---

## 3. THE INSTRUMENTS, AND WHAT THEY SAID

### 3.1 The suite — both engines, READ COMMITTED, on 3306

⚠️ **The engines swap on 3306; they do not run side by side.** Each run was made alone, verified through
`ops/proc-check.sh` (grep-like exits: **0 = safe to proceed**).

| engine | version | isolation | result |
|---|---|---|---|
| **MySQL** | 8.4.10 | READ-COMMITTED | 🔬 **3716 tests · 3715 passed · 1 skipped · 26479 assertions · green** (887 s) |
| **MariaDB** | 11.8.9 | READ-COMMITTED | 🔬 **run 1: 3714 passed · 1 failed · 1 skipped** (787 s) — the failure is §3.1b · 🔬 **run 2: 3716 tests · 3715 passed · 1 skipped · 26479 assertions · green** (756 s) |

⚠️ **The count is whatever the run printed.** No figure here was copied from another document.

⚠️ **MariaDB does not default to the gate's isolation level** — it came up REPEATABLE-READ under `--no-defaults`
and was set to READ-COMMITTED before the run. 🔬 Stated because an engine gate run at the wrong isolation level
is not the gate, and nothing in the command line says so.

⚠️ **One test is SKIPPED, and it is named rather than waved at:** 🔬 `PerUnitRateDisplayTest:92` carries an
unconditional `->skip('unitDetailOwner fixture shape verified separately — RevenueOnHold covers the resolver
seam')`. It is a deliberate, reasoned skip, not a failure — but ⚠️ **a skipped test is a test that proves
nothing, and "3715 passed" reads as total coverage while one assertion silently does not run.** The only other
skip in the suite is conditional (`PasswordlessAccountGateTest`, on a route that does exist, so it runs).

⚠️⚠️ **BOTH ENGINES NEEDED TWO RUNS, FOR DIFFERENT REASONS, AND BOTH ARE STATED RATHER THAN QUIETLY DROPPED.**
The first **MySQL** run carried **four failures, all caused by this pass** — three of them the subject of §2.5.
The first **MariaDB** run carried **one**, which is §3.1b and is a load-dependent message assertion, not a
defect: it did not reproduce on the second run and passes 4/4 in isolation.

⚠️ **"Green on the second run" is a weaker claim than "green", and it is the one being made for MariaDB.**
🔬 MySQL's second run was green with all four causes fixed and understood; MariaDB's difference between its two
runs is **timing under load**, with the mechanism read out of the code rather than guessed at.

### 3.1b ⚠️⚠️ THE ONE MariaDB FAILURE — INVESTIGATED, NOT RE-RUN AWAY, AND **NOT** ADJUSTED

🔬 The first MariaDB run failed exactly one test: `ReverseCheckInConcurrencyTest` — **R9's two-process race**,
a ruled behaviour built in this programme. ⚠️ **The standing rule is that a red pin on a ruled behaviour is the
system working: stop and ask, never adjust the test.** So it was investigated before anything else.

**What actually failed is the refusal's WORDING, not the guarantee.** Every assertion about the outcome
passed on MariaDB: exactly **1** reversal, exactly **1** refusal, exactly **1** history row, **1** domain
event, **1** audit event. What failed is `str_contains($loser['message'], 'already reversed')` — the loser was
refused with *"Only a checked-in stay can have its check-in reversed; this one is confirmed."* instead.

🔬 **The mechanism, read out of the code:** `ReverseCheckIn::handle()` calls `preview()` → `assertReversible()`
at **line 78, OUTSIDE the transaction**, and only then opens it and takes `lockForUpdate`. So the loser has
**two correct ways to lose**, decided purely by timing:

- it reaches line 78 **before** the winner commits → passes, blocks on the lock, re-reads `confirmed` inside
  it → *"already reversed a moment ago"* (the message the test pins), or
- it reaches line 78 **after** the winner commits → the ordinary transition guard refuses first →
  *"Only a checked-in stay…"*.

⚠️ **Both are correct refusals and both leave exactly one state change.** The test pins one of the two.

🔬 **Characterised rather than dismissed:** the test passes **4 of 4 in isolation on MariaDB**, and failed only
under full-suite load — so this is a timing sensitivity the busier run exposed, **not a MySQL/MariaDB
behavioural difference**. ⚠️ That distinction matters: an engine difference in a money-adjacent guard would be
a finding; a test that over-specifies which of two correct messages appears is a test problem.

⚠️⚠️ **IT WAS NOT FIXED AND THE TEST WAS NOT TOUCHED.** R9 is ruled, the assertion is the founder's to loosen
or keep, and this is precisely the "re-pin on reflex" the record warns about. ⚠️ **Recommendation (not a
decision):** accept either correct refusal — assert that the loser was refused *and* that its message is one of
the two known-correct ones — or move `assertReversible()` inside the transaction so there is only one way to
lose. The second is a behaviour change to a ruled action and should not be made casually.

⚠️ **And it is F-66's shape, which is why it is written up rather than shrugged at:** *a random red is a
finding, not a flake — re-run it, but then investigate it.*

### 3.2 The money gate — `mode=FULL`

🔬 Run with `VY_SEED_DATE=2026-09-01` exported in the same shell.

```
six aggregates   929 / 10928000 / 0 / 42 / 16 / 0          (unchanged)
row digests      a0e1e01d92d608f6   rate_calendar          (unchanged)
                 cc588f6bb9562863   reservations           (unchanged)
                 2f5598c92aadf105   payments               (unchanged)
                 9bac428a1b859297   owner_ledger           (unchanged)
```

**No digest moved.** ⚠️ Quoted with the gate's own sentence, not as "the money is unchanged": it is scoped to
**tenant one**, and **five money tables are read by no instrument at all** (`folios`, `folio_items`,
`owner_statements`, `owner_expenses`, `subscriptions`).

⚠️ Every write this pass made in a browser was confined to **`harbourpoint-demo-walkthrough`**, the disposable
tenant — a company account, a €45 charge, a housekeeping task and a photo, plus a permission revoked and
restored. 🔬 Tenant one's figures did not move by one, and all three tenants ended at **212**
`role_has_permissions` rows each.

### 3.3 The other named gates

| gate | result |
|---|---|
| **Exposure battery** | 🔬 **53 probes, 53 refused, 0 exposed, 0 unmeasured** — run against `https://vyrobel.com` from outside. |
| **`.htaccess` drift 11/11** | ⚠️ **NOT MEASURABLE FROM HERE.** The check reads `public_html/.htaccess` **on the host**; this session has no shell there. 📄 Carried as RECORDED, not re-measured. |
| **`oauth-private.key`** | 🔬 `-rw-------` (600). The public key is 660. |
| **Both payout triggers** | 🔬 `payouts_one_live_bi` (INSERT) and `payouts_one_live_bu` (UPDATE), **both with `SIGNAL` intact** — read out of `SHOW CREATE TRIGGER`, not from a migration file. |
| **The two status sets** | 🔬 `TERMINAL_STATUSES` = cancelled/no_show/checked_out and `IN_PROGRESS_STATUSES` remain **separate constants**, with the docblock's four-reader warning intact. |
| **RBAC census** | 🔬 52 permissions (global) · 40 roles · 688 `role_has_permissions` · 22 `model_has_roles` across three tenants. ⚠️ Tenant one reconciles to the pin exactly: **212** tenant-role links **+ 52** global-role links = **264**, the figure `SeededBaselineFiguresArePinnedTest` asserts. ⚠️ A first reading of 212 looked like drift against a remembered "260" and was not — **the pin counts the global role too, and 51 → 52 was a recorded, deliberate move.** Scope the query the way the pin does before calling anything drift. |
| **Route census** | 🔬 **111 of 504** named routes are linked by no Blade view. ⚠️ Two left the list this pass (`app.housekeeping.photos.destroy`, `app.reservations.unbill-from-account`). Three remain and each matches a pattern the census's own footer names: `app.reservations.bill-to-account` is a **JS-built form action** (so is `app.front-desk.check-out`, beside it), `owner.welcome` is a **redirect target**, and `owner.messages.show` is reached by a typed or guessed URL — which is its whole job. |
| **Refusal log** | 🔬 Silent: zero `NotFoundHttpException` / `MethodNotAllowedHttpException` and no ERROR-level line naming any route this pass touched. |

### 3.3b ⚠️ AN INSTRUMENT TRAP, IN A NEW COSTUME — RECORDED BECAUSE IT KEEPS RECURRING

While waiting for the suite, this pass armed a waiter: `until ! pgrep -qf "vendor/pestphp/pest/bin/pest"; do
sleep 15; done`. 🔬 **It would have waited forever.** `pgrep -f` matches full command lines, and the waiter's
own shell command line *contains the pattern it greps for* — so the waiter kept finding itself and concluded
the suite was still running. A second waiter armed beside it made three "pest processes" visible in `ps`,
which briefly looked like the one thing the project forbids: **two suites running at once, corrupting the
shared test database.**

⚠️ **This is the same defect as `ops/proc-check.sh` refusing a legitimate run because a watcher's command line
contained `vendor/pestphp/`** — recorded earlier in this same session — and as *"a source-scanning guard reads
its own needle"* (§N.27.5), which the memory notes is **still firing**. Three costumes, one mechanism: **a
process or scanner that matches on text will match the text of the thing doing the matching.**

⚠️ The fix here was to stop pattern-matching and wait on the **process identity** instead
(`while kill -0 <pid>`), which cannot match itself. ⚠️ **And the general rule the memory already states was
what caught it: verify the verifier before trusting a clean result — including when the "result" is silence.**

### 3.4 Production

🔬 **Measured this pass, from outside:** both hostnames return **HTTP 503** with the real maintenance page
(*"Back in a moment · Vyrobel"*), cache-busted, **without the bypass cookie**; `/up` returns **200**, so the
application is alive behind the park.

⚠️⚠️ **NOT re-measured this pass, and recorded as such:** the production table count, the zero-writes check and
the `.release` commit marker. **This session has no shell on that host and no database connection to it** —
📄 those figures stand as RECORDED by DEPLOY 09, not as measured today. ⚠️ Saying so is the point: *only 🔬
MEASURED and 🖥 BROWSER are facts*, and a carried-forward number dressed as a fresh one is how
*"`reverse()` is a provable inverse"* travelled through four briefs.

---

## 4. WHAT THIS PASS MUTATION-PROVED

A guard never seen to fire is not a guard. Eight mutations, each reverted, each re-verified green after:

| # | mutation | result |
|---|---|---|
| 1 | drop `folios.manage` from the bill-to-account row flag | 🔴 1 red |
| 2 | drop the server-side company-account list gate | 🔴 2 red |
| 3 | drop the flag from the payload Alpine reads | 🔴 2 red |
| 4 | ungate the housekeeping photo-delete control | 🔴 1 red |
| 5 | send the owner invite back to the dashboard | 🔴 1 red |
| 6 | ⚠️ *(withdrawn — the removal it tested was itself reverted; see §2.5. The full suite supplied the real proof: with the route gone, **3 isolation tests went red**)* | 🔴 3 red |
| 7 | change the OAuth access-token lifetime | 🔴 1 red |
| 8 | add the subscription gate to the api group | 🔴 1 red |

⚠️ **Mutations 7 and 8 are pins on OPEN rulings.** A red there is **not a bug** — it means a decision was
taken and the record must catch up in the same commit.

---

## 4b. THE PUSH GATE — the four stops, re-declared

| stop | answer |
|---|---|
| **Private?** | 🔬 `Subhankhan12/vyrobel`, `isPrivate: true`, confirmed from the GitHub API — not from memory. |
| **His?** | 🔬 `Subhan Khan <65254877+Subhankhan12@users.noreply.github.com>` on every commit. |
| **Unrelated / fast-forward?** | 🔬 `3` commits ahead of `origin/main`, **all three from this pass**, each listed in §1. Nothing unrelated is riding along. |
| **Secrets?** | 🔬 `ops/secret-scan.sh --all`: **36 hits, all triaged.** 34 are test/tool/seeder fixtures with literal test passwords (known and accepted). The 2 outside `tests/` are both benign and were read: `RegisterOAuthClient.php:33` prints a freshly-generated client secret to the console once (correct for that command), and `ops/runtime-contract.sh:231` is `DB_PASSWORD="${DB_PASSWORD:-}"` — an environment read with an empty default, no literal. ⚠️ **No hit in any file this pass touched.** 🔬 Separately: no `.env`, `.pem` or `oauth-*.key` is tracked, `storage/oauth-private.key` is gitignored and **600**, and the `APP_KEY` value appears in no tracked file. |

⚠️ Static analysis over the whole tree, not just the diff: **PHPStan L5 — 0 errors**; **Pint — clean**.

## 5. THE INVENTORY, CARRIED FORWARD

⚠️ **Nothing here is closed by this record.** It is carried so the next reader does not have to reassemble it.

**⛔ Blocking, and none of it is code** — `GO-LIVE-REQUIREMENTS.md` §BLOCKING: Postmark token + sending-domain
DNS (**B6**) · Stripe live keys, both webhook secrets, Connect enablement, `PAYMENTS_GATEWAY`, four Price IDs
(**B7–B11**) · Passport keys per environment (**B15**) · `APP_KEY`, `BACKUP_PASSPHRASE`, Redis, session/proxy
settings (**B2–B4, B14, B16**) · owner payout-destination verification before the Connect rail (**B17**) ·
⚠️⚠️ **a Data Processing Agreement and the flow-of-funds question — a lawyer, nobody in this repository
(B12, B13), and the longest lead time on the list.**

**⛔ The next sitting — three items, one sitting:** `OPS_HEARTBEAT_PING_URL` (**S1** — ⚠️ an alarm nobody has
heard ring is a hypothesis, and it would have caught the dead GDPR cron that nothing else surfaced) ·
`APP_KEY` recorded off-server (**S2** — ⚠️ fifth pass owed; it exists in exactly one place and losing it makes
every encrypted guest record permanently unreadable) · a real super-admin password (**S3**).

**⛔ Open findings and rulings:** **F-66** (order-dependent suite pollution — recorded, deliberately not fixed;
⚠️ its remedy is narrowed, because `RefreshDatabase` ON would break every cross-process test, which *is* the
no-double-booking guarantee) · **F-49 / R-REVERSE** (a payout can be made and not un-made; `reverse()`
quarantined and throwing, with **F-53/F-54** contained behind it) · **F-36 / A10** (a refused channel
modification surfaces nowhere — trigger at Channex cutover) · and **three rulings opened by this pass**:
**R-TOKEN-LIFE**, **R-TOKEN-SURVIVES** and **R9-MSG** (§3.1b — whether R9's race loser may be refused by
either of its two correct messages).

**⚠️ Advisable:** real tax rates (**A1**) · real plan prices (**A2**) · offsite backups (**A5**) · a restore
drill (**A7**) · re-verify the OTA refusal against a real channel (**A9**).

**🚫 Standing limits, to state rather than fix:** production runs `QUEUE_CONNECTION=database` with **no worker
at all** · `CACHE_STORE=file`, `SESSION_DRIVER=file` · `ops/deploy.sh` cannot run on that host (**L12h**) ·
`crontab` is not a command there, so the crons are panel-managed and hardcoded to an absolute path · ⚠️ a CDN
(`server: hcdn`) sits in front and **any measurement through that hostname measures the edge unless
cache-busted**.

---

## 5b. FOUND THIS PASS AND **NOT** FIXED — reported, as the rule requires

| thing | why it is not a fix |
|---|---|
| `$canManage ?? $canAssign` in `housekeeping-board.blade.php` | 🔬 `$canManage` is **never provided** by `HousekeepingBoard`, so the expression is permanently the fallback, and `$canAssign` **is** `can('housekeeping.manage')` — the route's own permission. Harmless, and the gate is correct. ⚠️ But it reads as though two different permissions are in play when there is one, and the new delete control had to adopt the same idiom to stay consistent with the upload beside it. **A one-line rename, and out of scope for a closing pass.** |
| The route census's three remaining `/app`-side entries | 🔬 Each matches a pattern the census's own footer names as legitimate (JS-built action · redirect target · typed URL). ⚠️ **They are not findings, and the census says so** — recorded here only so the next reader does not re-open them as three more orphans. |
| `PerUnitRateDisplayTest:92` skipped | Deliberate and reasoned, with the reason written into the skip. ⚠️ Named in §3.1 rather than fixed, because unpicking it means verifying a fixture shape the skip says is covered elsewhere — a measurement, not an edit. |

## 6. ⚠️⚠️ ONE PARAGRAPH FOR A STRANGER — WHAT WOULD HAPPEN IF A REAL HOTEL SIGNED UP TOMORROW

They could not. `vyrobel.com` answers every request with a maintenance page and has done for weeks — that is
deliberate, and it is the only reason none of what follows has hurt anybody. Suppose the founder lifted it
tonight. The hotel would reach a genuinely complete product: they could sign up, get a real organisation and a
free trial, add properties and units, take bookings on a public storefront, run a front desk, keep folios in
integer minor units that reconcile to the cent, pay owners, file nothing they had not configured, and never
see another tenant's data — that last point is measured, not asserted, and it is the claim this programme
worked hardest to earn. Then the first thing that would actually happen is that **their invitation email would
never arrive.** Production has `MAIL_MAILER=log` with an empty Postmark token and a log level that discards
the line, and no queue worker runs at all, so invites, password resets and booking confirmations do not fail —
they vanish. The second thing is that **no card would work**, because the Stripe keys are test-mode and the
live-key flip is a deliberate, documented, founder-only step. The third is that a lawyer has not yet written
the Article 28 processing agreement that a hotel handing over guest data is entitled to ask for. **None of
these is a bug, and that is exactly what makes them dangerous**: every one is a secret that has not been typed
or a signature that has not been obtained, none will announce itself, and the product will look completely
healthy while each of them silently fails. The engineering is, as far as anyone has managed to measure, done.
What stands between this and a paying customer is a password manager, a Stripe dashboard, a DNS record and a
solicitor — and the honest thing to say about the months of work behind this record is that it made the
remaining risks **legible**, not that it removed them.

> ⚠️ **PROVENANCE OF THE PARAGRAPH ABOVE, because it makes factual claims about a machine this session could
> not reach.** 🔬 **Measured today:** the park on both hostnames without the bypass cookie, and `/up` alive
> behind it. 📄 **RECORDED, not re-measured:** production's `MAIL_MAILER=log` with an empty Postmark token and
> a `LOG_LEVEL` that discards the line, `QUEUE_CONNECTION=database` with no worker, and the test-mode Stripe
> keys — all measured **2026-09-21** and carried in `CLAUDE.md`. ⚠️ This session has no shell and no database
> connection to that host, so it could not confirm them, and **a carried-forward figure dressed as a fresh one
> is the exact failure this programme spent four briefs unlearning.** Anyone acting on that paragraph should
> re-measure those four on the host before trusting them.

---

## 7. THE ONE SENTENCE

⚠️⚠️ **THE OPEN COUNT IS A FUNCTION OF HOW HARD SOMEONE HAS LOOKED.** This pass — the closing one, on a
programme that had already reached zero open findings twice — found a control that rendered for nobody, a
security document that promised a prompt the product does not show, a photo that could be added and never
removed, an onboarding page nobody was ever sent to, and a route whose own comment described a caller that did
not exist. **None of these was hiding. Each was simply not yet measured.** Write the count with that
qualification in the same breath, always.

⚠️⚠️ **And the sharpest instance is the one this pass produced against itself.** It removed a route on four
measurements that were each individually true, and the removal quietly widened an isolation boundary. The
thing that caught it was not cleverness and not review — it was **running everything, after being satisfied
by running the part that seemed relevant.** ⚠️ A targeted run tells you your change works. Only the whole
suite tells you what else it touched, and the gap between those two sentences is where this programme found
most of what it found.
