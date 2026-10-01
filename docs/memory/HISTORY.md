# Vyrobel — HISTORY (every pass, in git order)

Written 2026-09-27 by MEMORY 01, for the next cold session. Drafted read-only from git and the docs; the lines marked
**Verified** were checked by MEMORY 01 the same day.

**How each fact is known.** `(git)` = read from `git log` / `git show` on 2026-09-27.
`(Reported: <doc>, <date>)` = a doc says so; this file did not re-check it. "Inferred" = my reading of git
order where no doc names the pass. Do not upgrade any of these to "measured".

**Pass names** come from the founder's planning chat. JOURNEY 01, LIVE 01/02, DNS 01, SOCIAL 01–03 and CONSOLES 01
appear in no commit and no repo doc, so their mapping to commits is **inferred** and marked so.

## 0 · Before 2026-09-23 — one paragraph, then go to the docs

- **Build, 2026-07-02 → 2026-08-28.** First commit `97416c76` 2026-07-02; 433 commits in July, 499 in August;
  nothing committed 2026-08-29 → 09-13 (git). Phases 0–7, owner exact-match, completeness waves, payout engine,
  OAuth, tax engine, operator/guest audits, front website, doc corrections. Read `docs/DECISIONS.md`,
  `docs/PROJECT-STATE.md` and CLAUDE.md "Current status".
- **The audit and fix programme.** A fifteen-phase browser audit (`docs/AUDIT-PHASE-0..14.md`) and FIX 01–57,
  2026-09-12 → 09-20 (Reported: `docs/AUDIT-SUMMARY.md` pass ledger). First committed 2026-09-14 (`2d1d5110` …
  `19c59931`); founder rulings 2026-09-19 (`5bd01a35`); `docs/FINAL-VERIFICATION.md` (`bd299989`) (git).
  Findings, rulings and method: `docs/AUDIT-SUMMARY.md` §2, §4, §5, §6.
- **DEPLOY 01–10, 2026-09-20/21.** First production on Hostinger (MariaDB). The release layout L12k: `rel_*` dirs +
  one `current` symlink. A CDN nobody owned (L12m). The push to a private GitHub repo (DEPLOY 08).
  (Reported: `docs/AUDIT-SUMMARY.md` pass ledger rows DEPLOY 06–10; `docs/KNOWN-LIMITS.md` L12*, 2026-09-20.)
- **2026-09-21 → 09-22.** ACCESS 02/03: the exposure battery, and a record of the production `.htaccess` (`a827656a`,
  `13f4e7da`). TEST 01B–04: the refusal log, the 19 ungated /app read routes, the email-template permission, and a
  guard against writes to the baseline tenant (`8e015b69` … `80676f13`). APPLY 01/02 added R7 (a checked-in stay
  cannot be terminated, `9db74b53`) and R9 (reverse a check-in, `c6f32164`). The `.view` permission delete was
  **withdrawn**: the brief should never have asked for it (git). Also a route census (`2924120d`) (git).
- **CLOSE 01, 2026-09-23 (`0e147695`, `183ebca5`, git).** It restored `owner.messages.show`, which `2924120d` had
  wrongly removed. The name search could not see tests that build the URL by hand, and without the route the owner
  inbox answered 405 instead of an owner-scoped 404. It wrote `docs/CLOSING-RECORD-2026-09-22.md` and
  `docs/THE-REAL-INVENTORY-2026-09-23.md`: 67 findings all decided, "the system is not ready", and what blocks a
  first customer is configuration. ⚠️ **Mistake:** it said production had "NO WORKER" for the queue. REPAIR 01
  proved that false (git).

## 1 · The passes, 2026-09-23 onward

**REPAIR 01 — 2026-09-23 — `cd6e8a76` (git).** It fixed F-53/F-54 at the cause: a reversed payout now moves to
`reversed`, with the re-check inside a `lockForUpdate`. The `reverse()` quarantine stays; R-REVERSE is still the
founder's. It fixed F-66 at its real mechanism: `fake()->unique()` memory does not span tests, so four factories
now add a ULID suffix. It un-skipped `PerUnitRateDisplayTest`. Suite 3721/3721 on both engines (git).
**Found:** the brief's one launch blocker, "no queue worker", did not exist. A php84 `queue:work` cron runs every
minute. A job parked 55 h cleared within 60 s of an unpark (git). The first `ps` sample (36/36) matched its own
command line; that is why `ops/ps-check.sh` exists. A sequential test cannot prove a lock:
`PayoutReversalConcurrencyTest` had to be built. Editing the tree while a suite ran gave 35 false reds (git).
**Left open:** F-36/A10 was not reached (git).

**DEPLOY 11 — 2026-09-23 — `514b3afc`; cd6e8a76 went live as `rel_D` (swap 2 ms), rolled from `rel_C` (git).**
**Stopped before unparking.** `/signup` was public, with no switch to turn it off (git). **Found:** the exposure
battery counted 503 as "refused". The honest figure was 46/53, not 53/53. Under production config (`log` mailer,
`LOG_LEVEL=warning`) a queued email **appears to succeed and is never sent**. Postmark ("B6") was two steps, not
one: `symfony/postmark-mailer` was not installed. Production held 1 org and 1 user, and 0 platform admins (git).

**MAIL 01 — 2026-09-23 — `6747d128` (git).** It installed the Postmark API transport. That needed **two** packages;
`symfony/http-client` was the second, found only by testing. It proved the failure paths first: a bad token or an
unreachable host lands in `failed_jobs`. From-address mismatch could not be tested without a real token (git).
**Found:** email verification gated nothing, so an unverified signup got full `/app`. Port 25 is blocked on the host.
`OPS_HEARTBEAT_PING_URL` was empty, so the alarm was never armed. Fixed `ps-check.sh` (word-anchor) and the battery's
third verdict (git).

**GATE 01 — 2026-09-23 — `8da27d2b` (git).** Founder ruling: an account must verify its email before `/app`. `/app`
is 212/212 gated (git). **Found by the gate, not a test:** `OwnerInviteService::accept()` set `email_verified_at`
by mass-assign. That column is not fillable, so it was dropped silently. Every invited user would have been locked
out. Fixed with `forceFill` (git). The founder's only production account was verified by a guarded DB update. The
"verify when mail is down" procedure went into `docs/DEPLOYMENT.md` (git; Reported: DEPLOYMENT.md, 2026-09-23).

**JOURNEY 01 (inferred) — 2026-09-23 — `947c72a2`, `17045212` (git).** It deployed GATE 01 as `rel_F`
(`rel_E` → `rel_F`, swap 5 ms, `.release` = 8da27d2b) (git). It built `ops/mass-assign-check.php`, the silent-drop
detector. **Its first version gave 91 false drops**, so it was calibrated against three known cases (git). It created
"Vyrobel Internal — TEST TENANT" with six role users, and wrote `docs/THE-WALK-2026-09-23.md` for the founder to walk.
`17045212` fixed two walk rows that would have produced false bug reports (git). ⚠️ The 5 fields it flagged were
detector false positives (keys inside JSON columns), found in FINISH 01 (Reported: DECISIONS, 2026-09-25).
Whether the founder walked it: unknown — ask him.

**LIVE 01 / LIVE 02 (inferred; the split is unknown) — 2026-09-23 — `373599d6`, `78c21063`, `231d7415`,
`6993b75e` (git).** It staged, then applied, a private-preview Basic Auth lock with five `$`-anchored machine-only
exemptions. The site stayed parked (git; see `docs/THE-LOCK-2026-09-23.md`). **Found:** the lock pushed the battery
from 46 to "53/53". The score went up as the site got safer, so the battery was rebuilt to score by *which layer
answered*. Its first honest run: APPLICATION-REFUSED=0 (git). The host is **LiteSpeed**, not Apache (git). A php83
`queue:work` on the same account belongs to **hrmzy.com**, and it nearly overturned the correct worker record. A
10 s `ps` sample missed a worker that a 1 s sample found at once (git).

**DNS 01 — 2026-09-25, pasted together with MAIL 02 (Reported: this session's transcript).** No commit of its own. It
measured the mail records and found MX, SPF, DKIM and DMARC for Hostinger mail already correct. Nothing was changed; MX
was never touched (Reported: this session; git `f6cd45a4`). Its brief is done; it is kept in `briefs/` only because MAIL 02 cites
it (corrected 2026-09-29: this line first said it was not there).

**PUBLIC 01 — 2026-09-23 — `5d631d9f`, `c2486ece` (git; the name is in `ops/reference/public_html.htaccess` and
KNOWN-LIMITS L13).** The founder ruled the site public. Maintenance came off first, then the lock (git). **Found:**
the brief's lift test was wrong. `/up` returns 200 while parked, so only `/up/liveness` or a webhook 503→400 can
show the lift. `/up/liveness` stayed 503 because of stale heartbeats (17 parked hours): working as designed. First
real battery run: REFUSED=7, LAYER-ANSWERED=46, SERVED=0 (git). **L13:** every `/api/owner/*` returned 500 because
the OAuth key file had mode 775. It failed closed. It was hidden for weeks behind the park and the lock (git).

**SHADOW 01 — 2026-09-23 — `69fc656e`, `3347c9a6` (git; name in KNOWN-LIMITS L13/L14).** It closed L13:
`chmod 600` took the API from 500 to 401, after checking that the web process owns the file (git). It built
`ops/config-inventory.php`. **Found L14:** production caches config, so a raw `env()` reads NULL and
`OPS_HEARTBEAT_PING_URL` could never arm the alarm. One of its own checks passed the value through the shell and
wrongly "disproved" this (git). It recorded **L15** (".env is 777"). ⚠️ TONIGHT 01 found that was a misread: 777 was
the *symlink's* mode (Reported: KNOWN-LIMITS L15, 2026-09-25).

**SOCIAL 01 (inferred) — 2026-09-23 — `8cac3d18` (git).** It built `/privacy` and `/terms` over a recorded refusal.
The founder overrode it knowingly, because Google will not publish an External OAuth consent screen without a
privacy URL. The pages print their limits rather than claims. Marketing page pins went 19 → 21 (git). Google sign-in
went live on production that day. No commit; `70e15029` says "went live yesterday" (git). ⚠️ The page comment and
commit quoted **"96 of 142" categories without retention**. That was a stale hand count, quoted four times before
anyone checked it. ADMIN 01 removed it (Reported: DECISIONS ADMIN 01 P1, 2026-09-24;
`app/Support/Privacy/RetentionCoverage.php` docblock).

**CONSOLES 01 — date and commits unknown.** No commit or doc carries the name. The KEYBOARD 01 brief says the Google
and X consoles were done with the session driving a visible browser while the founder signed in and copied every
secret (Reported: `docs/memory/briefs/KEYBOARD-01.md`). The X app existed by 2026-09-24, when `8fb16054` measured
the live X API (git). Ask the founder for the rest.

**SOCIAL 02 (inferred) — 2026-09-24 — `70e15029`, `8fb16054`, `c31ea8c2` (git).** First a refactor with no new
provider, so a regression would have one suspect. Google tests: 30/30 before and after. Then X sign-in and linking
(OAuth2 + PKCE), then "X may create accounts" through a named list (`SocialProviders::TRUSTED_FOR_SIGNUP`). A forged
`emailVerified=true` was not used (git). **Found:** the brief's premise ("linking needs no verification") was false:
the verification guard was on `forLink()`'s first line. The founder's rulings are recorded with their cost. An X
signup can **permanently squat an unclaimed address**; Google stays off the list (git).

**SOCIAL 03 (inferred) — 2026-09-24 — `6a444d09` (git).** Found by audit, and live since the day before: every
social signup (Google **and** X) created an unverified, passwordless founder. The GATE 01 verify gate then locked
that founder out, because mail was `log`. Fixed with `forceFill` on the social path only. A password signup still
verifies by email, and that is mutation-proven (git).

**DESIGN reversal (not in the planning list) — 2026-09-24 — `fedfcd8e`, `c51fbbda`, `1d14df35`, `6f3ba095`
(git); live `rel_L @ fedfcd8e` (Reported: DECISIONS, 2026-09-24).** The founder reversed the separate admin
"control-room" palette. The console now uses the product design system. **Found:** `--ad-danger` was never declared,
plus two other undefined tokens/classes, caught by a token-resolution check. Two deploy traps (KNOWN-LIMITS L12k):
`cp -al` carries a config cache with **absolute paths**, so `rel_L` rendered `rel_K`'s Blade while every check was
green. Never `scp` onto a hardlinked file in a new release (git).

**ADMIN 01 (+ Phase-0 addendum) — 2026-09-24 — Phase 0 `a85a1b27`; P1 `7fa23cde` (`rel_N`); P2 `1152752b`
(`rel_O`); P3 `7440b720` (`rel_P`); P4 `7b8a16d0` (`rel_Q`); P5 `489c67d0` (`rel_R`) (git; releases Reported:
DECISIONS, 2026-09-24).** The platform console: P1 health board, kill switches, append-only ledger, four roles,
L14 closed · P2 real suspension, archive, protected tenants · P3 support tools, break-glass shipped **dormant** ·
P4 revenue from real charges, funnel, privacy desk · P5 statistics, announcements, `/status`. **Found:**
`.mcp.json` untracked and not ignored, running a headless browser the founder could never see (git). A "€297"
value summed free trials; the Phase-0 admin-undercount claim was **wrong on the HTTP path**; L16 recorded
(Reported). Three deploy-script bugs appeared only when run (git `7f47c953`).

**ADMIN 02 — 2026-09-24 (Reported: DECISIONS).** A brief ("build now, walk later"; break-glass off until the default
admin password stops verifying). Its rules were built inside ADMIN 01 P1–P3 (Reported). No commit of its own.

**ADMIN 03 — 2026-09-24/25 — `9d33eee5` (break-glass ON, labelled "ADMIN 03 #0"), `c5439730`, `5752b499`,
`975116b2` (git).** Break-glass was switched on after the founder changed the admin password. The docs say it has
been on "since `rel_S`" (Reported: DECISIONS, 2026-09-25). Then every console page was checked four ways
(functionality, design, content, fonts) at 1440 and 390 px. 3 S1 defects: the audit log showed page views, "0 on
trial" beside "8 in free trial", and "Next due: UTC" (Reported). **Found:** two mutations had proved nothing since
P3 because refactors moved their needles. From that came `tests/Mutations/run.php`, a harness that refuses to count a
mutation that did not land (git `975116b2`). F10 "Arial inputs" was a probe artefact (the hidden `_token` input)
(Reported).

**ADMIN 03B — 2026-09-25 — `9d0bf6ce`, `866b9527`; live `rel_V @ 9d0bf6ce` (git; Reported: DECISIONS).** Signed-in
live walk: 20 of 21 fixes seen live. It found F5 reopened, F25 (a raw value in an error message) and F26: re-assigning
the same plan wrote a ledger row claiming a change. The step-up walks (suspend, invite, last-owner, break-glass) were
not walked live, by the FINISH 01 ruling. They are test-covered only (Reported).

**SESSIONS 01 — 2026-09-25 — `3cd72c26`, `6afbfeb0`, `f53f604a`; live `rel_W @ 3cd72c26` (git; Reported:
KNOWN-LIMITS L16).** L16 closed live. Production sessions were file-based, so "sign out other sessions" and a
password reset ended nothing, and the audit row said they had. Now `SESSION_DRIVER=database`, plus
`EndSessionsOnPasswordChange` and one `signOutOthers`. The deploy signed everyone out once (Reported). **Left
open:** a password reset does not revoke owner OAuth tokens; that is the founder's ruling (Reported: DECISIONS).

**FINISH 01 — 2026-09-25 — Part C `72b454f6`; live `rel_X @ 6afbfeb0` (git; Reported: DECISIONS).** A founder
ruling: run unattended, never type his password or 2FA, leave the probes in place. Part C: silent attribute discards
are loud in tests (454 discards at 30 sites). "The five writes to columns that don't exist" was a **wrong premise**:
they were detector false positives. F-68's likely mechanism: a random CSRF token containing "48h" (Reported).

**MAIL 02 — 2026-09-25 — `f6cd45a4`, `2436cd66` (git); deployed as `rel_Y` (Reported: TONIGHT-01 brief).** Planned
for Postmark (`docs/memory/briefs/MAIL-02.md`), **run on Hostinger SMTP** by the founder's choice. The DNS was
already there and was measured, not changed. Added: an undeliverable-address skip list, `mail_send_log`, and a
`/privacy` line that says "active" only when mail really goes. Mail stays `log` until the founder types the mailbox
password (git). `docs/EMAIL-PRODUCTION.md` is the runbook.

**TONIGHT 01 — Part 1 done 2026-09-25/26 — `687c51a1` (`rel_Z`), `01767eb5` + `61f14059` (`rel_AA`), `bf5d567c`
(git; releases Reported: DECISIONS, 2026-09-25).** Three old `.env` backups moved out of the web root. `.env` is
one shared 600 file, and every deploy re-asserts that. `release.sh recache` is new. The retention inventory now ships
as JSON, so the meter counts on prod. **Found:** sign-up had never sent the first verification link. The mail log
typed every notification "unknown". The console "resend" said "handed to the mailer" for undeliverable addresses.
L15 was a symlink misread. **L18:** the published `sales@` has no mailbox (Reported: KNOWN-LIMITS L15/L18).
**Part 2 is waiting** on the founder typing the mailbox password into `.env` (Reported: the MEMORY 01 job brief,
2026-09-27; `docs/GO-LIVE-REQUIREMENTS.md` B6 still lists the password as his step). No commit shows Part 2 (git).

**ADDRESSES + a test-isolation fix (not in the planning list) — 2026-09-26 — `0d00b13f`, `329f7def` (git).**
Founder ruling: `contact@` everywhere except privacy and terms, which keep `sales@` (it still bounces until the hPanel
alias exists). `SeasonalPricingFormTest` read another tenant's price by date alone. `GuestPricingDisplayAnd403Test`
had no rollback (git).

**ADMIN 04 — 2026-09-26 — `97ec1ceb` (git).** Create and update organisations from the console, and each one's
sign-in activity. A 29-agent adversarial review found 11 defects before deploy. One example: a burst of lockouts, which
anyone can cause, hid real sign-ins (Reported: DECISIONS). **Verified 2026-09-27 (`ops/release.sh status`): it went
live as `rel_AC @ 97ec1ceb`.** The addresses commit before it is `rel_AB @ 329f7def`.

**ADMIN 05 — 2026-09-26 — `9bbccc12`, `1ede4c63`, `91b83801`; live `rel_AE @ 1ede4c63`; `rel_AD @ 9bbccc12` staged
and **never swapped** (git; Reported: DECISIONS).** Permanent organisation delete (Owner only, many refusals) and one
field system for every console form. **Found:** Laravel 13 `Schema::getTables()` lists **every database** the
connection sees. So TONIGHT 01's shipped `data-inventory.json` named six tables from *other projects* on the dev
machine. A former member's cascading FKs would have deleted another org's history. **At the deploy preflight:** the
delete preview took **117,704 ms** on production MariaDB, because each `getForeignKeys()` costs ~1.1 s there. Neither
local engine could show this. One `KEY_COLUMN_USAGE` read fixed it (188 ms live) (Reported). **Left open:** L19 (open
Stripe Checkout invisible to delete), and the signed-in live pass, which needs the founder's sign-in (Reported).

**ADMIN 06 — 2026-09-26/27 — built and gated locally, NOT committed, NOT pushed, NOT deployed (Verified 2026-09-27;
`docs/memory/STATE.md`).** The change is in the working tree (`git status`), including a new migration, the
`CheckoutSession` and `PaymentDispute` models, a new test file and the `admin-06` mutation set. Its record is the
**uncommitted** "2026-09-27 — ADMIN 06" entry in `docs/DECISIONS.md`.
- **What it built:**
  - the delete refuses while any Stripe object is active or pending (B19 included);
  - a **live-money hold pending the lawyer**, which is not a policy;
  - the preview counts every row the delete removes;
  - late webhooks are recorded and answered 202;
  - a founder's link never replaces a password;
  - a replaced logo is removed;
  - `release.sh` ships drifted files from git.
- **Gates on the working tree (Reported, 2026-09-27):** 4,018/4,018 on MySQL and on MariaDB; 291 mutations caught; money
  gate FULL unmoved.
- **Found:**
  - the preview for PW-SIGNUP PROBE said **17 records in 5 tables**, but the delete removes **232**. The founder caught it
    by asking about "about 230 rows";
  - Stripe's Refund carries no `livemode`;
  - an unresolved webhook resent later got a 500 on every retry (pre-existing);
  - **L20:** real Stripe gives no PaymentIntent at Checkout creation (pre-existing, read not run).
- **Two adversarial reviews:**
  - **The first had 16 findings** (6 verified, 1 refuted). The confirmed ones and most of the rest were fixed. One
    pre-existing finding (L20) was recorded, not built. A narrow webhook-versus-deletion race that can give one 500 was
    documented. The logo-in-sent-emails point (raised by both reviews) became ruling (f).
  - **The second had 19 findings:** 4 confirmed, 1 refuted, 14 not verified. **All fixed 2026-09-29** (below), except
    D-TRANSFER, which is pre-existing and recorded.
  - Rulings (a)–(f) are open. PW-SIGNUP PROBE is **not** deleted, and not suspended (Verified).

**MEMORY 01 — 2026-09-27.** A writing pass with no product work.
- Wrote `docs/memory/`: STATE, OPEN, RULES, SYSTEMS and HISTORY; the open briefs, word for word; the proof tools.
- Added a START HERE block to `CLAUDE.md`, with a list of its known stale lines. Two lines got dated corrections: the site
  has been public since 2026-09-23, and mail is Hostinger and off, not Postmark.
- Tested the memory on two fresh readers: 12/12, then 12/12. The second listed 35 contradictions and stale lines, and
  the ones in the memory were fixed.
- Left two of its own read-only scripts on production, in `env-backups/` (`OPEN.md` A6).
- **Found:** the two Pest runs the founder saw were one run (the final MySQL suite) shown as two processes. A Mac sleep
  had stretched a 17-minute run to 8½ hours.

**MEMORY 02 + ADMIN 06 FINISH — 2026-09-29 — the commit that carries this entry (git log).** Started with the MEMORY 02 check: every production
read matched `STATE.md` (Verified 2026-09-27 15:55–16:05 Baku, and again 2026-09-29 11:38), and four small mistakes were found in the memory itself (GitHub was at
`58800419`, not `91b83801`; the DNS 01 brief IS in `briefs/`; the briefs and tools were committed, not untracked;
`SYSTEMS.md` named an old HEAD). The founder then said to make everything that has to be live, live, and to verify it.
- **Review 2's findings, each verified by reading, then fixed with a test that failed first** (table in `docs/DECISIONS.md`,
  ADMIN 06 → "The second adversarial review"). The real defects: an operator's own Connect account update recorded as a
  failure (D-R2-1); a live page paid in its last minute stopped refusing (D-R2-2); an unknown dispute status counted as
  finished, failing open (D-R2-7); a dispute refusal with no way out named (D-R2-8); `recordUnresolved()` could demote a
  claim being processed (D-R2-9); a premise gap (D-R2-10). The rest were coverage or doc gaps.
- **Review 3 (a review of those fixes) found a real defect review 2 had not:** the billing webhook skipped an ARCHIVED
  (soft-deleted) organisation, so Stripe's reports for one were written down and never applied — and a delete may only
  run on a suspended or archived organisation, so its refusals could never lift. Fixed (`withTrashed()`), with three more
  points (`DECISIONS`, "Review 3").
- **Found by the suite's own guard, in this pass's own test:** a negated `toContain($token, $table)` — the message became a
  second needle, so the check always passed. `NegatedVariadicMatcherGuardTest` caught it; the same trap fired on 2026-09-27.
- **Gates (Verified, this pass's run outputs):** 4,033/4,033 on MySQL 8.4.10 and on MariaDB 11.8.9 (same tree, fingerprint
  `8f8813b4fac362d8`); 323/323 mutations caught across every set; money gate FULL unmoved; PHPStan 0; Pint clean.
- **Deploy — LIVE `rel_AF @ 4c17edd2`, swap 4 ms (Verified 2026-09-29 13:41 Baku).** Preflight on production's MariaDB,
  read-only: the delete preview 144 ms, 232 rows (was shown as 17). Public checks 12/0/0; 1,401/1,401 files match git;
  0 errors since the swap; the 775 log set to 644. ⚠️ First pages after the swap were slow (up to 35 s) under a shared-host
  load near 50, then 0.3–0.5 s. Not done: the signed-in look at the delete screen (needs his sign-in).
- **Another project shares the Mac.** `advisor-platform` runs full suites against the same local MySQL; the mutation harness
  refused once because of it (its guard matches any `pest`, `OPEN.md` A7), and the MariaDB engine swap needed the founder
  to pause that project (F 2026-09-29).

**PORTFOLIO REMOVE — 2026-09-29 — the founder's request ("an option to delete property … very cool design").** Designed with
him first (brainstorming, two questions: *safe removal*, and the design, both approved). Built test-first on the certified
archive-not-destroy removal: a remove control on each Portfolio card, a confirmation showing the path (today → last checkout
→ removed) and what is kept and what goes, the name typed to confirm; gone **right away** when nobody is staying and nothing
is booked ahead, otherwise scheduled with **Keep this property**; the **last property can never be removed** (in the
service, so Settings too). **Found and closed:** the nightly rule could archive over a guest checked in past their checkout
date (F-57 extended in its own direction); the page printed every notice twice. **Found by the suite's own guards:**
`$loop->index` inside tag attributes (the `>` of `->` cut the tags short for two source-reading guards), a bare `1fr` grid,
and — before any run — a `run@if` glued to a word. Rulings touched: F-58 (DEC-h). Not done: D-EDIT-SCOPE (DEC-i).
Gates: 4,042/4,042 on both engines; 337/337 mutations; money gate FULL unmoved; browser-walked on tenant three.
**Deploy — LIVE `rel_AG @ e4117aeb`, swap 4 ms (Verified 2026-09-29 16:34 Baku);** preflight on production: the removal
read 1.1 ms median per card; public checks 12/0/0; 1,402/1,402 files match git; 0 errors since the swap.

**PORTFOLIO OPEN + DIALOG FIELDS — 2026-09-29 — the founder's report.** *Open did nothing:* reproduced in a browser first
— it reused the top-bar switcher, which redirects back, so the page reloaded in place and only a small tag moved; now Open
lands on the property's Dashboard with a notice, through a fixed destination list (never a URL). *The dropdowns looked
basic:* Type is three icon tiles, Status an Active | Inactive toggle, Time zone a searchable list with offsets — two small
progressive operator components over the real form fields (the console field system's contract). **Found by the guards
and the browser:** a component tag inside a CSS comment is compiled by Blade (every page 500'd); `.vy-input` reset the
combobox padding; a tile overflowed at 390 px; the keyboard guard wanted a reasoned exemption for the listbox options
(added with its backing string, PF-M6). Gates: 4,047/4,047 on both engines; 343/343 mutations; money gate FULL unmoved.
**Deploy — LIVE `rel_AH @ 3b012e93`, swap 4 ms (Verified 2026-09-29 18:25 Baku);** public checks 12/0/0; 1,405/1,405 files
match git; 0 errors since the swap.

**GUESTS — 2026-09-29 — the founder's report ("the Add guest modal is not in the centre … the data table should be so
advanced … edit and delete the guest as well").** Design approved first. *The dialogs:* measured in a browser before any
change — every dialog on the page and the profile's Edit centred with an inline `display: flex` beside `x-show`, which
Alpine erases on the first hide (the Add panel's centre sat at 260,242 in 1280×900); all now use `.vy-modal-backdrop`,
and a new guard holds the Guests pages at zero and the 45 other sites at a ceiling. *The page:* one toolbar (search that
follows the typing, honest placeholder, filters with counts, Select, Add guest); sortable Guest / Stays / Last stay
through an allow-list, Last stay by the date the column shows (computed in SQL as the PHP computes the cell); 25 / 50 /
100 per page; a real footer outside the table; links that keep every setting; in-place updates that still fetch the same
GET URL (#20); row actions (Open · Edit · Erase personal data) gated like their routes, coming back to the list through
an allow-list rebuild (never a request URL). **Found and closed:** a list Edit would have wiped the stay preferences
(now `keep_preferences`); the old footer called In-house and Upcoming "flagged at risk"; `UnwiredControlsTest` passed on
an ungated dialog heading because `Livewire::test()` leaves the permission team empty; the shared `.vy-modal` glass put
grey text at 3.52 / 3.25:1 in the Portfolio dialogs (my own work earlier today) — Guests and Portfolio panels now
near-opaque (6.27 / 5.28 and 6.18 / 5.30); a detached row's bindings threw once on a swap. **Two guards caught this
pass's own code:** the Livewire boundary guard (two public static helpers on the component, moved to `GuestListParams`),
and the mutation harness (GL-M21 stayed green because the contrast test counted `class="vy-modal "` with a space). An
independent read-only review of the whole diff found nothing at its confidence bar. Not touched (reported): the Inbox,
Calendar New reservation and Billing dialogs use the same see-through glass (not measured).
Gates: 4,065/4,065 on MariaDB 11.8.9 and on MySQL 8.4.10 (the MySQL run before the last code fix was 4,064/4,065 —
the Livewire boundary guard); 367/367 mutations; money gate FULL unmoved; browser-walked on tenant three (desktop and
390 px, 0 console errors).
**Deploy — LIVE `rel_AI @ 9f704a3a`, swap 8 ms (Verified 2026-09-29 22:02 Baku);** preflight on production: every sort order
ran in ≤ 0.9 ms on MariaDB, and the way back rebuilt to the list from a query carrying junk; public checks 12/0/0; 1,407/1,407
files match git; drift check passes; 0 errors since the swap.

**MAINTENANCE — 2026-09-29 — the founder's request ("now work the same sort of things on the same page", after Guests).**
Design approved first. *The dialogs:* the same inline-display bug as Guests — New work order opened at 260,330 and the
drawer on the LEFT; all now centre by class and the drawer sits on the right by class. The work order page's Reassign and
Mark resolved are real dialogs now (they had no Escape and no name — the keyboard guard's allow-list had them as "reported,
not fixed"). *The page:* the Guests treatment — one toolbar, filter chips with counts inside the search, a table that sorts
by priority · time to target (by the very deadline the column shows) · reported · unit, 25 / 50 / 100 per page, a footer
that says what the board holds, and a ⋯ menu offering only what the ticket's state and the viewer allow (the old board
offered Assign on a resolved work order). New: **Edit** (open or in progress only; the audit names fields, never the text;
the property allow-list checked) and **Cancel** as the honest delete (kept, as cancelled; the room back on sale unless
another open work order holds it). Every action comes back to the board as it was, rebuilt from an allow-list. One shared
list script now drives Guests and Maintenance. **Found and closed:** the sticky-header rule clipped the table's last two
columns at 1180 px (now measured, not assumed — Guests too); Alpine's string `:style` shrank the work order page's switch to
16×6 px and stripped its dialogs' rows and tabs (14 sites; a new guard holds 10 others at a ceiling); the Mark resolved
dialog overflowed; category and priority labels split mid-word; "1 units blocked"; Costs report and Settings were offered
to roles that get a 403; and (**the independent review's one finding**) the board's Mark resolved never told whoever
reported the work order — it now carries the work order page's switch, on by default. **This pass's own mistakes, each caught:** a one-sided comparator mutation (MB-M3) stayed green —
the mutation's fault, rewritten, then caught; a test script's `:nth-of-type` clicked Assign instead of Edit; a script that
typed within ~30 ms of opening the unit picker created a work order on the default unit in tenant three (proven the script's
fault at a human pace). Not changed, reported: creating a work order does not check the property allow-list (D-MT-STORE,
DEC-j).
Gates: 4,087/4,087 on MySQL 8.4.10 and on MariaDB 11.8.9 (tree `3ac61b96bc916ce4`); the final run 4,087/4,087 on MySQL (tree `4d9f12e34c4bdba0`, the code plus these docs); 393/393 mutations;
money gate FULL unmoved; browser-walked on tenant three (1440, 1180 and 390 px; 0 console errors).
**Deploy — LIVE `rel_AJ @ a3604b9c`, swap 16 ms (Verified 2026-09-30 12:22 Baku);** preflight on production: the board's
query ran in 0.7 ms median across 15 organisations, the way back rebuilt to the board from a query carrying junk and an
off-site value stayed on vyrobel.com; public checks 12/0/0; 1,409/1,409 files match git; drift check passes; 0 errors
since the swap.

**UNITS — 2026-09-30 — the founder's request** ("on this page in Add inventory sector, text and label is mess, `Kind` word is
down and its dropdown is small … the same work which you have done with the previous two pages"). An 8-part plan approved
first ("Yes, all of it"). *The alignment, measured:* the labels used `.vy-label` and the edit fields `.vy-inp`, which no
stylesheet defines (16 px body text; unstyled boxes), each field carried a 15 px bottom margin that dropped the buttons
beside them (🖥 718 vs 703), and Kind was a native dropdown whose height the browser picks. *Now:* Add room type and Add unit
are toolbar buttons opening centred dialogs — styled labels, one box height, buttons in a footer; Kind as four tiles; the
unit's room type through the searchable picker. The bottom card became a three-step setup guide shown only while a room type
or a unit is missing. *The page:* the Guests/Maintenance list — five figures, search as you type, chips with counts inside the
search (All · Bookable · Paused · To clean · Out of order), a table sorting by unit (natural: A2 before A10), room type and
sleeps, 25 / 50 / 100 per page, a footer, and a real CSV export (the view, or a selection) replacing "Export · Soon" and
"Filters · Soon". A ⋯ menu per row, each item only where the role and the workspace allow it: Edit unit · Price individually ·
Arrival info · See its work orders · Pause / Resume. "Take out of service" became **Pause / Resume** with a Paused tag — the
owner portal's words, and no longer the same words as the maintenance condition beside it. Room types are cards (cover,
kind, sleeps, units bookable / total, Add unit, photos). Select mode exports the selection and pauses or resumes several
units through the same audited service. **Found and closed:** a duplicate unit code on Add was a 500 (the database was the
only check; Edit had the rule) — now a sentence, and the dialog reopens with what was typed; a table sub-line that rendered
empty pushed the unit code off centre; the setup guide told a manager in a paused workspace to ask for a permission they
hold; and (**the independent review's one finding**) bulk Pause/Resume recomputed a room type's year of availability once
per UNIT (🔬 ~0.5 s and 1,098 queries each; a page of 100 ≈ 50 s locally) with nothing around the loop — now once per room
type, in one transaction, with a test that counts the recomputes and proves a failure changes nothing. **This pass's own mistakes, each caught:** a test regex that could not see through
Livewire's block markers (they contain `>`), and one that missed a count set in `<strong>`; a Python anchor that matched
twice (refused by its own count check); the first full MySQL run went **4,105/4,107** — the new mutation set's top-level
`$rows` (a file path) overwrote the suite loader's own `$rows`, because a required file runs in the scope that requires it
(both loaders now require each set in a scope of its own, `loadMutationSet()`, with a probe test; the set's variable was
renamed too); and a test
title that claimed more than it proved ("nothing that writes in a paused workspace" — Arrival info is still offered there,
as it always was; the title now says what the test proves). Not changed, reported: Add unit resolves the room type by the
tenant scope only, while every other unit write checks the property allow-list (D-UNIT-STORE, DEC-k); the single pause is
not in a transaction (D-UNIT-SINGLE-TXN, shared with the owner portal).
Gates: 4,105/4,107 on MySQL on the first run (the loader error above), then 4,108/4,108 on MariaDB 11.8.9 (tree
`40a51eade6466fc1`; he said "stop advisor and complete vyrobel", so advisor-platform's dev server was stopped with SIGTERM at
16:09 and left stopped); the final MySQL run 4,108/4,108 (tree `ce2dc2f218e1fac9`, the code plus these docs); 424/424 mutations; money gate FULL unmoved; browser-walked on tenant
three (1440 and 390 px; 0 console errors).
**Deploy — LIVE `rel_AK @ a4e2b6e5`, swap 4 ms (Verified 2026-09-30 17:29 Baku);** 10 files shipped, no migration; preflight
on production: every route present and gated, the bulk service present, the way back rebuilt to the list from a query
carrying junk and an off-site value stayed on vyrobel.com, the unit read 0.2 ms median across 15 organisations (3 units), no
row carrying a secret; public checks 12/0/0; 1,412/1,412 files match git; drift check passes; 0 errors since the swap.

**UNITS · DEC-k — 2026-09-30 — his ruling ("Fix this issue too").** Add unit found the room type through the tenant scope
only, so staff limited to one property could add a unit to another property's room type by posting its id (🔬 the new test
was RED first: a 302 where a 404 was due). Now `accessibleRoomType()` — the same `readIds()` rule as `accessibleUnit()`, which
every other unit write uses: another property's room type is a 404 and nothing is created there; the person's own property
still takes a unit, and a manager allowed everywhere, working across all properties, still may add anywhere (pinned both
ways, so the fix cannot lock anyone out). Mutations UL-M32 (the helper loses its narrowing) and UL-M33 (Add unit goes back
to the plain lookup) caught. DEC-i and DEC-j, the same question elsewhere, stay his. **Found beside it, not fixed:** the
three rate-plan writes (add, edit, on/off sale) have the same tenant-only lookup (D-RATE-PLAN-SCOPE, DEC-l) — outside his
ruling, so reported, not changed. A preflight check of my own first matched that rate-plan line instead of Add unit's and
reported the fix missing; it now reads `storeUnit`'s own body.
Gates: 4,109/4,109 on MySQL 8.4.10 and on MariaDB 11.8.9 (tree `758fe3261f6a5721`, the code plus these docs); 426/426
mutations; money gate FULL unmoved.
**Deploy — LIVE `rel_AL @ 8cda8c7f`, swap 4 ms (Verified 2026-09-30 19:13 Baku);** 1 file shipped, no migration; preflight on
production: Add unit still gated `units.manage`, `storeUnit` uses the new lookup and the plain one is gone from it, and 0 people
are limited to particular properties today (so no one's access changed now — it guards the case once someone is); public
checks 12/0/0; 1,412/1,412 files match git; drift check passes; 0 errors since the swap.

**PHOTOS — 2026-09-30 — his report: "when I add photo, the preview is not available … verify … if it's not fixed, fix
it, ship it live".** Not fixed: 🔬 five room photos sat on production's disk and each address (`/storage/room-photos/…`)
answered 404 from outside, cache-busted. The cause was already written down — KNOWN-LIMITS L12p, "it will fail SILENTLY":
the document root is the application root, so the server refuses every `/storage…` path to protect the real storage
folder (logs, the bypass secret). That rule was left exactly as it is. The public disk now answers at **`/media`**
through `PublicMediaController`, registered outside the `web` group (no session, no cookie): images only, judged by the
file's own bytes, only from the directories the app writes photos, logos and avatars into. Every surface that shows
those — Units, the storefront, logos, avatars, housekeeping and maintenance photos — is fixed by the one address change.
Message attachments were deliberately left out (private documents — D-MEDIA-ATTACH, DEC-m). 🖥 Walked locally: a photo
added on Units showed at once as the card's cover and its thumbnail (800×520 decoded), the storefront showed it, 0
console errors. **This pass's own mistakes, each caught by the tests before anything shipped:** `Storage::fake()` drops
the disk's `url`, so the first test still saw `/storage`; and Flysystem's `mimeType()` falls back to the file name, so a
text file named `.jpg` was served — the check now reads the bytes with `finfo`; and the first full run was 4,111/4,112:
`HtmlIsNeverPubliclyCacheableTest` refused my `Cache-Control: public` (production's CDN — L12k/L12m); it is `private`
now, and the guard was not touched.
Gates: 4,112/4,112 on MySQL 8.4.10 and on MariaDB 11.8.9 (tree `41f113a1016595f1`, the code plus these docs); 432/432
mutations; money gate FULL unmoved.
**Deploy — LIVE `rel_AM @ 9e9b1193`, swap 5 ms (Verified 2026-09-30 20:42 Baku);** 3 files shipped, no migration; preflight on
production: the route outside the web group, the disk at `https://vyrobel.com/media`, all 5 of his room photos with `/media`
addresses and served; after the swap 🔬 all 5 answered **200 `image/jpeg`** from outside (real JPEGs, no cookie, `private`),
`/storage/…` still 404, `/media/message-attachments/…` and a folder 404; his live storefront (`villa-azure-lumiere`) shows its
2 covers at `/media`, 0 at `/storage`; public checks 12/0/0; 1,413/1,413 files match git; drift check passes; 0 errors
since the swap.

**ATTACHMENTS · DEC-m — 2026-09-30 — his ruling ("Fix this issue as well … let only people in that conversation open them,
after signing in").** Owner message attachments sat on the PUBLIC disk behind a public address — refused on production
(`/storage`), and open to anyone holding a link had it worked; the operator inbox, the side the owner sends them TO, never
showed them at all. Now: stored on the PRIVATE disk (`MessageAttachments::DISK`), opened only through two signed-in routes
behind one controller — `owner.messages.attachment` (the owner portal's gates, then the owner scope on the thread: that owner
and their team) and `app.inbox.attachment` (`guests.view`, then the organisation's threads); another owner, another
organisation, a staff role without the inbox, or a visitor gets a 404 / 403 / sign-in, never the file. A photo shows in place;
anything else downloads under its own name; `private, no-store`, `nosniff`. The attachment rows are ONE definition shared by
both conversations (`MessageAttachments::rows()`), and the inbox now shows each file under its message. Production held 0
attachments, so nothing needed moving. 🖥 Walked locally: an owner in tenant three sent a PDF and a photo; the photo showed at
800×520 in the owner's conversation AND in the inbox, the PDF downloaded as "Plumber invoice.pdf" on both sides, staff at the
owner door were bounced; 0 console errors. **This pass's own mistakes, each caught before shipping:** a fake PDF with no bytes
made two content checks vacuous (found by a check that failed on `''`); a removal cut `humanSize()` at its first inner brace
(`php -l` caught it); zsh passed 27 test files as one path.
Gates: 4,116/4,116 on MySQL 8.4.10 and on MariaDB 11.8.9 (tree `ffb0b2200eb4da6d`, the code plus these docs); 439/439
mutations; money gate FULL unmoved.
**Deploy — LIVE `rel_AN @ 761cf2dd`, swap 27 ms (Verified 2026-09-30 21:59 Baku);** 9 files shipped, no migration; preflight on
production: both routes present with their gates, the private disk's folder present and writable, 0 attachments on production;
after the swap, signed out, both attachment doors answer 302 → `/login`, `/storage/…` and `/media/…` serve no attachment (404),
his photos still load; public checks 12/0/0; 1,415/1,415 files match git; drift check passes; 0 errors since the swap.

**RATES — 2026-10-01 — his request ("on this page table design must be better, texts must look good … all texts and boxes
must be well aligned … the kind of work which you have done in the past pages … every single operation should be also working
fine … test every single thing … ship them live and verify it"); the 9-part plan approved ("Yes, all of it"), DEC-l included.**
🖥 Measured first (1440×900, tenant three): every dialog opened in the top-left corner; the grid was 1,320 px in a 1,148 px card
and hid its last two nights; one row mixed 34, 36 and 43 px boxes; the season and add-plan inputs had no visible box; "Add a
rate plan" had 16 px labels (`vy-label`, no rule). Found underneath, each with a test RED first: bulk edit and per-unit pricing
posted the editor's ONE-night dates under a two-week heading; a paused workspace could save and APPLY seasons; Refundable off
still made a refundable plan; "All room types" meant the whole organisation; a 100% decrease wrote €0 nights; max stay and
arrival/departure could not be set alone; the grid shaded Sat/Sun while every price rule means Fri/Sat; writes dropped the
window and filter; refusals were plain notices; "Jump to rates" and "Pacing" looked at the wrong nights; per-unit prices and new
plans left no trail; an applied season's edit refusal was never shown. **DEC-l ruled and fixed:** every rate write goes through
`RateScope` (the person's properties). Found in the browser walk and fixed: a second plan could be added but never priced (now
"Set rates" offers the plan, server-checked, and each plan row shows its fortnight's prices and Set prices); long prices ran into
the next night; the tall editor hid its buttons; phone selects clipped their text; "across 6 nights" counted rows; after a
drag, the next click on a night did nothing (the drag's click landed on the row and its flag stayed set). The page: one
Alpine scope, every dialog on the centred backdrop (x-show ceiling 35 → 26), all 14 nights, sticky column and header, today and
Fri–Sat marked, one cell design with a matching legend, click or drag to edit, bulk edit on its own dates, a fourth figure "Next
14 days". Tests: `RatesPageFixesTest` (17), `RatesPageRedesignTest` (13); four older tests moved with the behaviour (a refusal is
now an alert; the ceiling fell). Mutations `rates-page` 47/47 (RP-M12 first survived — its test took ANY refusal; now each
refusal is named). 🖥 Every operation walked locally at 1440 and 390; 0 console errors. **This pass's own mistakes, each caught
before shipping:** an inline `@php(…)` above a later `@php … @endphp` block made Blade swallow the grid as raw PHP (the first test
run: a ParseError); JS comments saying "Bulk edit" leaked the words to view-only roles (a test caught it); a test seeded an error
bag with `withSession`, which JSON sessions return empty (switched to a real round trip); figures read 300 ms after load were
mid count-up (€118.73 for €131.49) — the database was read before chasing it; 40 px boxes plus the shared 11 px padding clipped
text on a phone (the browser caught it); a CSS comment in the shared layout said a dialog "opened", which every operator page
ships, and the email-templates page's no-engagement-figure check failed on it (the full suite: 4,145/4,146). The independent
review's one finding was the drag-then-click bug, already found in the walk and fixed; it found tenancy, redirects, money,
Blade/Alpine and the shared code clean.
Gates: 4,146/4,146 on MySQL 8.4.10 and on MariaDB 11.8.9 (tree `abc822f5672e411a`, the code plus these docs); 486/486
mutations; money gate FULL unmoved.
**Deploy — LIVE `rel_AO @ cac9e677`, swap 4 ms (Verified 2026-10-01 02:10 Baku);** 14 files, no migration; preflight read-only
(every write behind `rates.manage`, views compile, new reads 3.3 ms); public checks 12/0/0; 1,434/1,434 files match git; drift
check passes; 0 errors since the swap. **The live look (he signed in; look only):** every dialog centred at 720,450 and closed
with Escape, the drag and the click after it, the filter, 390 px; 0 console errors from the page. **Two found live and fixed in a
follow-up:** the season form with an empty plan picker where no plan is on sale; a hidden table header escaping its scroll box
and widening the document to 660 px at 390 (both scroll boxes are now containing blocks).
Follow-up gates: 4,148/4,148 on MySQL 8.4.10 and MariaDB 11.8.9 (tree `0f40aa8b2640cd26`); 488/488 mutations; money gate FULL
unmoved.
**Deploy — LIVE `rel_AP @ 9b9b80c8`, swap 3 ms (Verified 2026-10-01 03:21 Baku);** 2 files; public checks 12/0/0; 1,434/1,434
files match git; drift check passes; 0 errors since the swap; both fixes re-checked on production (390 of 390 px; no season form
without a plan on sale); 0 console errors.

**OWNERS — 2026-10-01 — his request ("on Add owner page, modal design and buttons are mess, cancel and send invite buttons look
bad, confirm with your password and its box is not well aligned … modify the entire design of this page like previous page …
fully functional … build a plan for this page and execute it"); a 7-part plan posted and executed as asked.** Read in the old
source: one flex row held Cancel, the password's label, its box, its error and Send invite. Now the shared centred dialog, the
password on its own row, Cancel and Send invite together at one height (🖥 42 px each, at 1440 and 390); refusals beside their
field, focus where the person has to act. Found underneath, each with a test RED first: ⚠️⚠️ an invite could move a unit
away from its owner (offered owned units; acceptance overwrote the owner) — refused at the invite, kept at acceptance, dropped
on a resend; invite units came from every property (now `readIds()`); "resend it any time from the owner's row" with no resend
anywhere (Resend with your password, Cancel; a founder's link left alone); invites recorded nothing (now three trail actions,
never the address; `OwnerInvite` needed a morph alias); a paused owner read Ready and counted in "ready to pay"; the side panel
claimed a monthly close and an automatic payout day that do not happen. Found in the browser walk and fixed: the table scrolled
sideways at 1440 and cut the hold reason (Statement merged into Net payable); a paused owner with nothing due was offered Pause
again (the menu read the pay status, not the preference — test RED first); Export kept the address the page loaded with after an
in-place sort; the invites showed one initial where rows showed two; the "Invite sent" dialog focused Preview, not Done; the
commission line read "— of gross" with no agreement. ⚠️⚠️ **Found by reading the payout run, not fixed (money path, another
page; OPEN A11, DEC-n):** "Run payouts now" selects every organisation's owners — 🔬 a throwaway probe in the test database
had organisation A's operator create and pay organisation B's owner's payout (D-PAYRUN-TENANT); and only the automatic run
reads a pause (D-PAUSE-MANUAL), which the Owners page now says. **This pass's own mistake, caught before commit:** the new CSS
comment first said "measured before: 41 and 35 px, padded twice" — numbers never measured (the old dialog had its own 24 px
panel); rewritten to what the old source shows. Tests `OwnersPageFixesTest` (17), `OwnersPageRedesignTest` (9); three
`OperatorOwnersPayableStateTest` label checks moved to the new label with the same counts (one negative check would have passed
vacuously — it now asserts the zero); `CurrencyNeverDefaultsTest`'s only allow-list entry removed (its line is gone; the
detector finds nothing). Mutations `owners-page` 41/41. 🖥 Walked locally on tenant three at 1440 and 390: invite (wrong
password, then sent), resend (wrong password, then sent), accept, pause and resume from the row menu, search, the no-match state,
chips, sort, export, a staff invite, cancel; console clean but for Chrome's password-manager hint, answered with the hidden
username field the security page uses. The independent review found no defect; its one observation (the payout card did not
say paused owners are left out) was made consistent, with a test.
Gates: 4,174/4,174 on MySQL 8.4.10 and on MariaDB 11.8.9 (tree `201095a330f2e9bb`, the code plus these docs); 529/529
mutations; money gate FULL unmoved.
**Deploy — LIVE `rel_AQ @ 5d391dbe`, swap 5 ms (Verified 2026-10-01 19:08 Baku);** 12 files, no migration; preflight read-only
(every write gated, the export path resolves, views compile, `PAYOUT_ENGINE_ENABLED` off, 0 owners on production, the read
0.5 ms); public checks 12/0/0; 1,438/1,438 files match git; drift check passes; 0 errors since the swap. The live look (he
signed in; look only): the dialog centred, both buttons 42 px on one line, 390 of 390 px on a phone, no console message from
the page; signed out and the browser closed.

**MEMORY — 2026-10-01 — his request ("Update the entire memory of every work you have done so far").** No code. `SYSTEMS.md`
brought up to date (it last changed 2026-09-29): the live release, the read-only preflight and the byte check as standing
practice, the live-look procedure in the browser window, the shared Mac (advisor-platform's server stopped since
2026-09-30), and three new sections — the operator back office's shared kit (§11), photos at `/media` and private message
attachments (§12), owners, invites and the payout engine (§13). The Owners preflight saved as a template
(`tools/preflight-owners.php.txt`). §3 below brought up to date. Outside the repo: the session memory index and its files,
and the `.remember` logs.

## 2 · Mistakes to learn from (each one cited above)

- **Briefs were wrong, often.** "No queue worker" (CLOSE 01 → REPAIR 01). "Type a token" was really 2 packages plus
  config (DEPLOY 11, MAIL 01). "Linking needs no verification" (SOCIAL 02). "Delete the .view permissions"
  (APPLY 02, withdrawn). "`/up` shows the lift" (PUBLIC 01). The admin-undercount claim (ADMIN 01 P1). The "five dead
  columns" (FINISH 01). MAIL 02 was briefed for Postmark and run on Hostinger. **Verify the brief first.**
- **Stale numbers travelled:** "96 of 142" was quoted four times, and "53/53 refused" twice (503, then 401).
  **A metric that improves while the thing does not has moved itself.**
- **A preview can undercount:** 17 of 232 rows (ADMIN 06); a schema read that counted other databases (ADMIN 05).
- **Only production shows some costs:** the 118 s preview was caught because the preflight **timed the new path on
  the host**. Keep doing that for any schema read.
- **Silent drops:** `email_verified_at` not fillable (GATE 01, SOCIAL 03). An undefined CSS custom property (DESIGN).
  An `env()` read under cached config (L14). A mail job that "succeeds" under `log` (DEPLOY 11).
- **Wrong process, wrong sample:** hrmzy's worker; a 10 s sample for a 2 s process; a `ps` grep matching itself.
- **Deploy traps:** config cache with absolute paths; `scp` through hardlinks; hand-copied ops files (ADMIN 06 #6).
- **Self-inflicted:** editing during a suite run (REPAIR 01); removing a route by name search (CLOSE 01); two stale
  mutations that proved nothing (ADMIN 03). ADMIN 06 made three mistakes of its own, each caught by a guard or a review:
  a negated `toContain` with a message, which always passes; a bare `withoutGlobalScopes()`; and a `KNOWN-LIMITS` L19
  line claiming two closures when there was one.
- **Status taken as fact:** the founder wrote "suspended." twice about PW-SIGNUP PROBE, and it was never suspended
  (Verified again 2026-09-27). The planning side listed ADMIN 06 as "not run"; it was built but not committed. **Read the
  state; don't take it from a message.**
- **Wrong window:** on 2026-09-27 a brief for another project (advisor-platform's gate) was pasted into this session. It
  was not run.
- **The machine sleeps:** a suite started at 05:40 finished at 14:12, because the Mac slept. Run long work under
  `caffeinate -i`.
- **A review of the fixes found what the fixes missed** (2026-09-29): an ARCHIVED organisation's Stripe reports were never
  applied, so a delete's refusals could never lift; and one fix was broader than its defect and hid a real problem.
- **Guards catching the author (the Guests pass, 2026-09-29):** two public helpers put on a Livewire component (the boundary
  guard); a contrast check that could not see `class="vy-modal"` without a trailing space (the mutation harness, GL-M21); and
  a pre-existing test that had passed on an ungated look-alike under `Livewire::test()`. Each was caught before commit.
- **The same trap, two days after writing it down:** a negated `toContain` with a message, in this pass's own new test. The
  guard test caught it, not the author.
- **A check that proves nothing looks exactly like one that passes (the Maintenance pass, 2026-09-29):** a mutation that
  flipped one side of a comparator (MB-M3) stayed green because the comparator became inconsistent, not because the test
  was weak; a browser script faster than a person typed into the button behind a picker. Both were read, not assumed, before
  anything was changed — and neither was fixed by changing the test.
- **A test's title is a claim too (the Units pass, 2026-09-30):** "offers … nothing that writes in a paused workspace" sat on
  a test that correctly pinned Arrival info as still offered there. The assertion was right and the title was wrong; caught
  on re-reading, before commit. And a memory lesson drafted from recall ("the menu swallowed focus") was checked against
  the transcript, found to be a change made on reading the code — never a measured failure — and not written as one.

## 3 · Still open (as the docs report it; re-check before acting — `OPEN.md` is the authority)

Mail password + TONIGHT 01 Part 2 (Verified 2026-09-29: `MAIL_PASSWORD` not set) · proving the `sales@` alias (created
2026-09-26, Reported) · ADMIN 06: live since 2026-09-29 · L20 · D-TRANSFER · ADMIN 06 rulings (a)–(g) · ADMIN 05 signed-in
live pass · KEYBOARD 01 (needs him) · OAuth-token-on-reset ruling · Stripe live keys, the lawyer, real tax rates.
**Raised 2026-09-29 → 10-01, waiting on him:** ⚠️⚠️ A11 — "Run payouts now" reaches every organisation's owners
(D-PAYRUN-TENANT; the switch is off on production) · DEC-n — should a pause stop the manual payout run · A12 — the Units
export link · A8 / A9 / A10 — the x-show, see-through-dialog and string `:style` sweeps · DEC-h (F-58 and the Portfolio card)
· DEC-i (Portfolio Edit scope) · DEC-j (maintenance write scope) · the signed-in looks still owed on earlier passes. Where:
`OPEN.md`, `docs/GO-LIVE-REQUIREMENTS.md`, `docs/STRIPE-PRODUCTION.md`, `docs/KNOWN-LIMITS.md`, `docs/AUDIT-SUMMARY.md` §4.

## Sources used

- git: `log --reverse`, `log -1 --format=%b` per hash above, `show 8cac3d18` / `7fa23cde`, `log -S "96 of 142"`,
  `merge-base --is-ancestor`, `status`, `show HEAD:docs/DECISIONS.md` (committed vs working-tree sections).
- `docs/DECISIONS.md` 2026-09-22 → 09-27 (ADMIN 06 in the working tree only) · `docs/AUDIT-SUMMARY.md` ·
  `docs/KNOWN-LIMITS.md` L12k, L13–L20 · `docs/GO-LIVE-REQUIREMENTS.md` B5/B6 · `docs/EMAIL-PRODUCTION.md` ·
  `docs/STATE.md` · `docs/THE-LOCK-2026-09-23.md` · `docs/THE-WALK-2026-09-23.md` · `ops/reference/public_html.htaccess`.
- `docs/memory/briefs/`: MAIL-02, TONIGHT-01, ADMIN-06, KEYBOARD-01, PW-SIGNUP-DELETE-1 and -2-follow-up.
- Not available: the founder's planning chat. The DNS 01 brief is in this session's transcript (done, not saved).
