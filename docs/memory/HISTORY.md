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

**PAYOUTS — 2026-10-02 — his "Lets work on next steps"; the plan approved ("Yes, all of it") and DEC-n ruled ("Yes, a pause
stops every payout").** No migration. ⚠️⚠️ **A11 fixed:** "Run payouts now" passed the engine no owner list, and the engine
then selects every organisation — 🔬 RED first, organisation A's click created and paid organisation B's owner; the button now
names its own organisation's owners and starts nothing when none are due. **Found and fixed on the way:** the manual run key
was unique only by timestamp, so two organisations' runs in one instant were one run (🔬 the suite's frozen clock); and the
payout run page's "Last run" read the newest run row platform-wide — another organisation's counts (🔬 RED first) — it now
reads only this organisation's payouts. **DEC-n built:** one refusal in `OwnerPayoutService` (create, pay inside the lock,
retry), the engine never selects a paused owner, the payout run lists them under "Payouts paused", its buttons skip them and
count them, the owner portal's retry says so honestly, and the Owners and owner pages say a pause stops every payout. Also the
"until ." sentence and A12 (Units export). Tests `PayoutRunScopeAndPauseTest` (11) +3 elsewhere; mutations `payout-run-scope`
26/26; one older mutation (UL-M28) re-anchored. 🖥 Walked locally on tenant three: Create payouts → "1 payout created · 1
paused owner skipped", Mark paid → "1 payout marked paid · 1 paused owner skipped", the paused card and the waiting row, both
owner pages' wording, the Units export after an in-place sort, 390 px; no console messages. **This pass's own mistakes, each
caught before commit:** the first skip count counted statements ("2 paused" for one owner — the walk); my mutation ids reused
the Portfolio set's `PR-` prefix (the harness's own test); defensive `owner_id !== null` and `?->` checks the model types make
dead (PHPStan); a test that switched users in one session read the other user's flash message (fixed with `flushSession`).
**The independent review's one finding, fixed:** the owner portal still offered "Retry payout" to a paused owner — the shared
retry rule (`PayoutRecovery::isRetryable`) had not learned the new refusal (🔬 RED first); it now has, and the banner says why.
**And the full mutation run caught one more of mine:** the new pause-dialog sentence repeated the words an Owners side-panel
test searched the whole page for, so OW-M32 survived; the test now reads the panel only.
Gates: 4,188/4,188 on MySQL 8.4.10 and on MariaDB 11.8.9 (tree `758751245566c93d`, the code plus these docs); 555/555
mutations; money gate FULL unmoved.
**Deploy — LIVE `rel_AR @ d90f40ca`, swap 4 ms (Verified 2026-10-03 17:26 Baku);** 16 files, no migration; preflight read-only
(every payout write gated, the views compile, the engine off, production holds no payouts); public checks 12/0/0; 1,439/1,439
files match git; drift check passes; 0 errors since the swap. The live look (he signed in; look only): the payout run, Owners
and Units pages render, no console message; signed out and the browser closed.

**OWNER MODULE — 2026-10-03 — his "on Add owner page, modal design and buttons are mess … confirm with your password and its
box is not well aligned. Fix all of these problems … all the sort of work you have done in the previous pages … in this module
… make sure everything works very well … test everything with MCP playwright … ship everything live".** The plan posted (five
parts) and executed; no migration. The Add owner dialog (rebuilt 2026-10-01) was measured unchanged; the dialog he described is
**Remove owner** on the owner's page — now the shared centred dialog with "Confirm it's you" on its own row and Keep owner /
Remove owner both 42 px (🖥 measured, centred at 720,450 in 1440×900; 350 px wide at 390). **The owner's page rebuilt:** the
owner and every action in one bar (each only for the role its route accepts), seven dialogs, dates in words, the waiting
invite's actions on the page, 80 inline styles gone. **Four faults behind it, each 🔬 RED first and fixed:** ⚠️⚠️ the
commission change decided against today's agreement — two changes in a month stacked two open-ended agreements on one day (the
payout maths takes whichever the database returns) and an undo wrote nothing; ⚠️⚠️ it dropped the management fee, the term and
the attribution rules; ⚠️⚠️ Edit owner's "portal access" box could never switch access off, while a hand-made request switched
any linked login off (even a colleague's) or opened an invited one — the field is no longer read (DEC-o, his); and every refusal
closed its dialog and said nothing. One rule now, `CommissionChange`. **The payout run:** D-PAYRUN-BUTTON fixed (disabled, with
the reason, while the engine is off); every money write asks first and says Vyrobel moves no money; focus on Cancel; 20 inline
styles gone. **Unit requests:** chips that count, cards with photos, Approve/Decline in dialogs; ⚠️ DEC-k applied to approving
(🔬 RED first: a manager limited to one property created a unit in another); A8 26 → 24. **Owners list:** one line when every
unit has an owner; ⚠️ a false hint of my own (2026-10-01, "you can give units later from the owner's page") corrected — no
screen does that (DEC-p, his). A Remove-owner bullet no code backs was dropped ("any scheduled final payout is honored": the
engine never selects a removed owner). Tests: `OwnerPageFixesTest` (19), `OwnerPageRedesignTest` (5), `PayoutRunPageTest`
(6), `UnitRequestsPageTest` (9), +2 on the Owners list; one gate test's two needles re-pointed to the new markup (the gate
unchanged); mutations `owner-module` 41/41 — three survived the first run (OM-M12, M19, M22: a page-wide search satisfied by
the script, an address the dialogs also carry, an outer guard hiding an inner one) and the tests were scoped. 🖥 Walked locally
on tenant three at 1440 and 390: every dialog; a commission change, its undo ("Replaced before it started · Never applied"); a
wrong password reopening Remove owner; a payout created and marked paid through the confirms (Cancel first: nothing posted);
"Alfama Loft" approved into the one property, the chips moving to Waiting 0 · Approved 1; no console messages. **This pass's
own mistakes, each caught before commit:** the page root reused `vyo-owner` (the Owners list's flex-row cell) and the page laid
out sideways — the browser; the header glow made the header scrollable by 14 px — the browser; the disabled run button looked
pressable; the confirm's focus on the money button; the notice said "changes to 18%" while 18% was the rate; three weak tests
(the mutation run). **The independent review** (read-only): nothing critical or high; fixed from it — the rule ended only the
newest covering agreement (now every one, collapsing an old stack), an owner with no agreement waited for the 1st (now today),
the portal tests were refused by validation before the code they named (now they reach it), "bookings already made keep their
rate" (now "worked out"), plus a save lock, a kept end date, a payout confirm that cannot fail open, honest notification
wording, a removed owner's request (decline only) and the statement's own rate on the net card. Recorded for him: DEC-q (rate
proposals skip the property limit, pre-existing). Mutations `owner-module` 48/48 after it (OM-M5 needed a sharper test).
Gates: 4,229/4,229 on MySQL 8.4.10 and on MariaDB 11.8.9 (tree `17253d581ca575a5`, the code plus these docs; the committed
tree adds the no-agreement dialog sentence and its assertion, the 11 affected files re-run on it 75/75); 603/603 mutations;
money gate FULL unmoved.
**Deploy — LIVE `rel_AS @ 4751d2c9`, swap 4 ms (Verified 2026-10-03 21:32 Baku);** 15 files, no migration; preflight read-only
(every changed write gated as its control is, the views compile, the engine off, production holds no owner, agreement, unit
request or payout — so nothing stacked to collapse); public checks 12/0/0; 1,440/1,440 files match git; drift check passes; 0
errors since the swap. The live look (he signed in; look only): Owners and Add owner, the payout run's disabled run button and its
reason, unit requests' empty state; signed out and the browser closed. The owner's page has no owner to show on production — it
was walked locally.

**NEW RESERVATION — 2026-10-04 — his "on this page there is functionality issues. it is not possible to do reservation. Fix it …
do the same sort of things [as the last five or six pages] … play it with MCP playwright … find out more issues on this page and
fix everything … ship everything live and then verify it via MCP playwright on the server".** The plan posted (eight parts) and
executed; no migration. **Why (🔬 read-only on production):** his workspace's first property has no rooms and the other has no
price on any of the next 60 nights; the wizard — a focused screen with no property switcher — booked into the first and said "No
units are free", or offered only "Set a rate first →", a link that threw the booking away. No error was logged: a dead end, not a
crash. **Built:** "Which property is this booking for?" under All properties (with each property's rooms) and a "Booking into"
picker; one card for a property with no rooms; a "Price a night" box for `rates.manage` that posts the Rates write itself for
exactly the stay's nights and comes back ready to book; every refusal returns the whole booking and reopens at the step to fix —
⚠️ never a new guest's email or phone (#28), which the page asks for again; the override over the whole booking, its rules in
words; steps that refuse to pass and say what is missing; check-out loads what's free; every figure from the server's minor units
in the stay's currency (the unit's price had been parsed out of the displayed text, the symbol hardcoded "€"); a unit's own price
shown; the estimate on the folio's own taxes and fees (it added an included VAT twice and no cleaning fee); keyboard-pickable
unit cards; returning guests told apart by their stays; 0 inline styles (A8 24 → 22). **Found on the walk, each 🔬 RED first:**
⚠️⚠️ "Other" without a note was a 500 (an uncaught exception in the service); ⚠️⚠️ every refused booking left its new guest — name,
email, phone — behind (two "Lena Walk"s with no stay). **The independent review** (read-only, nothing high) — fixed: ⚠️ the
clean-up could strip a booking saved just before a later failure of its guest (now only a guest nothing points at); ⚠️ a browser
draft filled the NEXT booking with its extras, credit and deposit (now this tab only, never payment, the rest only for the same
stay); taxes by the property's currency, not the stay's; the shared submit guard disabling a hidden "Set price" instead of Confirm;
a looser email test than the server's; "No rooms yet" for a property whose rooms are closed; hand-made arrays as 500s; "nothing is
free" for a stay over 90 nights; ⚠️ the done screen showing other properties' bookings by id (pre-existing). Recorded for him:
DEC-r (an existing guest's email on a new guest), D-MONEY-COMMA (🔬 "150,50" read as €15,050.00 by the shared parser — this page
now stops it, 19 other inputs do not), D-STOREFRONT-ORPHAN-GUEST, D-GB-SYMBOL, D-RATE-DERIVED-PRIMARY. Tests:
`NewReservationWizardTest` (27); the inline-display ceiling 22; mutations `new-reservation` 34 — first run 30 caught, two stale
needles (mine) and two tests that passed for the wrong reason (sharpened), then 34/34. 🖥 Walked on tenant three at 1440 and 390:
the no-rooms card, the picker, a price set (a comma refused first), a unit by keyboard, a refusal reopening at Payment with
everything kept, a booking with an override and a €126 deposit (R-VL5P4JZX), the chooser, a returning guest; 0 console messages.
Gates: 4,256/4,256 on MySQL 8.4.10 and on MariaDB 11.8.9 (tree `633895cac1ed8024`, the code plus these docs); 637/637 mutations;
money gate FULL unmoved.
**Deploy — LIVE `rel_AT @ 282109e4`, swap 4 ms (Verified 2026-10-04 04:41 Baku);** 6 files, no migration; preflight read-only
(the gates, the views, his workspace: one property with no rooms, one with 3 unpriced units — exactly the two dead ends); public
checks 12/0/0; 1,440/1,440 files match git; drift check passes; 0 errors since the swap. The live look (he signed in; look only):
the no-rooms card, the price box on both Villa Azure room types, Next's message, 390 px; signed out and the browser closed.

**CALENDAR NEW RESERVATION — 2026-10-04 — his "on `+New reservation~ page, the design is messed, guest first name, last name, and
their boxes are not well aligned" and "also do additional works the way you did for previous pages".** The plan posted (ten parts)
and executed; no migration. **Why (read from the code):** the wireframe has one "Name or existing guest" box beside the adults; the
build split it into two labelled names inside a grid of three columns, so five pieces wrapped: "Last name" ended line one and
its box began line two. **Built:** the Owners dialogs' structure on the shared solid card (A9 for this dialog) — each label above
its box, the dates and adults labelled on their own line, the foot in view, Escape closing it cleanly (it left `new=1` behind);
⚠️⚠️ the guest's name out of the address (#28 — "Check rack" was a GET carrying `gf`/`gl` into history and access logs; now the tab
keeps it, only across its own Check rack and only for the person who typed it); the false "Matches an existing profile" and
"the guest gets a confirmation email" removed; room types with no price or no plan listed with the reason (they vanished: "No
units are free"), a property with no rooms one card; the unit's own price (what the write charges); a taxes-and-fees line; check-in
never in the past by default (his link defaulted to 28 Sep), a past check-in, a reversed stay and >90 nights named and a past
check-in refused on Confirm; impossible dates dropped; **NEW: a "+" on each empty night of a unit's row** opening the dialog with
that unit picked; "Travel agent"; a radio group; "today" in the property's timezone; the hint one sentence; 0 inline styles in
the dialog (the rack view 19 → 8). **Found on the walk, mine:** ⚠️⚠️ a `"` in a comment inside `x-data` ended the attribute — 38
console errors, every server-side test green; fixed and guarded by a test. **The independent review** (read-only, nothing high,
3 medium, 10 low — all fixed): "+" on nights with nothing to sell; a name coming back in the next dialog; "set a price in the full
form" for a room type with no plan; `unit[]`/`rt[]` 500s; the "+" layer over the blocks' tooltips; a past check-in bookable; two
todays; restored date boxes booking other dates; Back stuck at "Booking…"; the taxes note's currency; two over-claiming lines;
wasted queries; accessibility. And `OperatorControlGateTest`'s "write surface" check matched the full form's link — now the panel.
The view's second permission check went: behind the component's, no test could prove it (CN-M26). Recorded: D-RACK-START-TZ.
Tests: `CalendarNewReservationTest` (24 — 🔬 22 fail on the old code; the 2 that pass are guards); mutations
`calendar-new-reservation` 33 — first run 31/32, then 33/33. 🖥 Walked on tenant three at 1440 and 390 (the alignment measured:
each label's left edge on its box's); a booking made from the dialog (R-PRSAG8AH); 0 console messages.
Gates: 4,280/4,280 on MySQL 8.4.10 and on MariaDB 11.8.9 (tree `2224515895cd0245`, the code plus these docs; read from JUnit
logs — ⚠️ a first MySQL run printed nothing, `laravel/pao` failing at shutdown, and was not counted); 670/670 mutations; money
gate FULL unmoved.
**Deploy — LIVE `rel_AU @ 7819226d`, swap 3 ms (Verified 2026-10-04 18:45 Baku);** 5 files, no migration; preflight read-only
(the gates, the views, his workspace: one property with no rooms, one with 3 units and no price tonight); public checks 12/0/0;
1,441/1,441 files match git; drift check passes; 0 errors since the swap. The live look (he signed in — the second time in the
automated window; look only): the no-rooms card, 42 "+" links on Villa Azure, the dialog with each label over its own box, both
room types said to have no price, 390 px, Escape; signed out and the browser closed.

**NEW RESERVATION, SECOND PASS — 2026-10-04 — his "it is not possible to do reservation … deeply analyze this page … more cool,
advanced, minimal and operational … ship everything live", and from now on no GitHub push per task (push at the very end, when he
says).** The plan posted (twelve parts) and executed; no migration. **Why (🔬 read-only on production):** his working property has
no rooms (a dead end with a small picker) and his other property never had a price — the €200 "Winter" season he SAVED on 28 Sep
was never APPLIED, and the page never said so; 0 errors, nothing attempted today. **Built:** the properties that can take a
booking offered from the no-rooms card; a saved season applied from the booking through the Rates page's own "Apply this
season?" disclosure (one partial, one builder `SeasonApplyFacts`; R-SEASON untouched) and back on the same dates; seven steps →
four (Stay · Guest · Extras only where there are some · Confirm); each night's price, "—" or "Full" on the calendar, today in
the property's timezone; house rules in the Settings words, "Not set" where nothing was set, the stay's real free-cancellation
date; the arrival sentence from the real conditions (email off on production — said); the confirmation box only with an
address; the self check-in code only for a guest with an email; countries by name; "Paid now / Left to pay"; a total bar on a
phone. **The independent review:** 2 high, 5 medium, 13 low — all fixed; ⚠️⚠️ H2: my first Apply posted on the first click,
without the disclosure (never shipped); ⚠️ H1: a refused returning guest lost the confirmation. Tests:
`NewReservationBookableTest` 20 (14 RED first, 6 proven by mutation); mutations `new-reservation-bookable` 29. 🖥 Walked on a copy
of his production setup in tenant three at 1440 and 390: the no-rooms card, Winter and Autumn applied (Autumn through the
dialog), a booking with a deposit (R-NGHV2TCX), the four-step path with an extra, a returning guest; 0 console messages.
Gates: 4,300/4,300 on MySQL 8.4.10 and on MariaDB 11.8.9 (tree `b0fdbb9d224d3508`; JUnit logs; ⚠️ the run before the last fix
had one failure, the boundary guard on a public `steps()` — fixed, re-run); 699/699 mutations; money gate FULL unmoved.
**Live 2026-10-04 22:14 Baku: `rel_AV @ bac3997b`, committed locally and NOT pushed — his new rule (push only at the very end).**
Preflight read-only from the staged release; public checks 12/0/0; 1,443/1,443 files match git; drift + self-test pass; 0 errors.
His live look (signed in himself at the second ask): no-rooms card → Villa Azure; Winter offered for 10→13 Oct, its dialog opened
and cancelled (nothing sent); Next refused; 390 px clean; 0 console messages; signed out, browser closed. Seen: his suite's plan
is named "350" (his data); two wordings for his go (D-NR2-NEXT-NOPRICE, D-NR2-SCOPE-WORDING).

**CALENDAR, SECOND PASS — 2026-10-04 — his "https://vyrobel.com/app/calendar?start=2026-09-28&days=14 … it is not possible to do
reservation. Fix it … if there's any other issue on this page".** Plan posted and executed (his rule: plan, then keep working);
no migration. **Why (🔬 read-only on production, 19:02 UTC):** the New reservation page's reason — no rooms on his working
property, no price on any night of Villa Azure, "Winter" saved and never applied, 0 reservations, 0 errors — and the Calendar's
dialog offered only "Set a price in the full form" (or "Go to Units"). 🔬 Reproduced first in tenant three with a copy of his
setup. **Built:** Set price inside the dialog (the Rates write, `return=calendar`) and the saved season's Apply inside it (the
Rates page's own disclosure, the dialog turned into an "Apply this season?" view), each back INTO the dialog on the same stay
(`CalendarReturn`: every part checked or dropped, never the guest's name — `kept=1` brings it back from the tab), the clicked
unit kept and its room type first, the next step said from what the dialog shows; the properties with rooms offered from the
no-rooms dialog and the empty Calendar (`BookableProperties`, moved out of the New reservation page, as was `SavedSeasonOffer`);
a booking made here comes back here ("Reservation R-… created. Open it"); the grid marks the nights it cannot sell (a dashed
underline, the legend, one note naming the room types and the unapplied season) and greys the nights gone; drag-to-move only
for `reservations.assign` (seven of eleven back-office roles were offered a drop the server refused); a refused move an alert;
the room-type filter kept on every way round, and a foreign one ignored. **The independent review:** no high or medium; eight
low, all fixed (the note offered ways a person lacked; "can't be booked" overclaimed — the storefront sells any fully priced plan,
so "here"; a part-season promised a booking; Escape in the Apply view closed the booking and forgot the name; two price boxes a
screen reader could not tell apart; room types with no bookable unit had no way on; work done for people who cannot use it; 7/14/30
in three places) and one of its two unverified cases (a message lost on a property with no rooms). Mine on the way: Set price and
Apply for dates changed but not checked (now locked like Confirm); a doubled quote I wrote into a button; and "€" inside the
dialog's attribute, caught by the full suite's currency guard (moved into a script block, as the full page has it). Tests:
`CalendarBookableTest` 35 (29 RED first; 3 pin what must stay; 3 proven by mutation); `CalendarNewReservationTest` changed with the
behaviour and tightened (CN-M9 and CN-M11 stayed green once the grid's note and card said the same words). Mutations
`calendar-bookable` 61, all caught; six older needles re-pointed to the moved code. 🖥 Walked at 1440 and 390 on the copy:
the grid's note and marks, Apply Winter through the view (Back returns focus; nothing sent), Apply for real → back in the dialog
with the name and 101 picked → booked (R-CJWSOXYT) → back on the Calendar with "Open it"; Set price at 390 ("€350.00 for the night
of 10 Oct. SV is picked — confirm to book it"); the no-rooms card and dialog → Villa Walk; the filter through 7n and the arrows;
after the review, Escape in the Apply view steps back with the name kept (a second Escape closes) and Set price locks once the
dates change; 0 console messages. Gates: 4,335/4,335 on MySQL 8.4.10 and on MariaDB 11.8.9 (tree `bc45dca32a733a7e`; JUnit logs;
⚠️ the run before the last fix had one failure, the currency guard on "€" in the dialog's attribute — fixed, re-run); 760/760
mutations; money gate FULL unmoved. **Live 2026-10-05 01:51 Baku: `rel_AW @ a2831e72`, committed locally and NOT pushed.**
Preflight read-only from the staged release; public checks 12/0/0; 1,447/1,447 files match git; drift + self-test pass; 0 errors.
His live look (signed in himself at the first ask): the no-rooms card → Villa Azure; the note, the greyed and marked nights; "+" on
101 → Double room first, Winter and Set price; the Apply view and Escape back (nothing sent); the name boxes still aligned; 390 px
clean; 0 console messages; signed out, browser closed. Seen: the sidebar cuts his property's name (D-SHELL-PROPNAME), the top
bar's date pill a day behind the property's (D-RACK-START-TZ).

**GUESTS, SECOND PASS — 2026-10-05 — his "https://vyrobel.com/app/guests — on this page there is design issue. Text is not visible
completely. Serach box design is not good and its symbol is not well-aligned. Fix it … the same work as on the previous pages … in
terms of design functionality and everything else".** Plan posted and executed (his rule: plan, then keep working); no migration.
**Why (🔬 measured locally on a copy with long names, a long email, tags and stays — his workspace holds one guest):** below ~1,180 px
the table (min-width 760 px) was wider than its card, so Status was cut ("No booki…") and at 390 px Nationality sat under the row's
sticky menu; an email broke mid-word; the search box sat squeezed between the filter chips and the buttons, its icon off-centre on
Safari's own search styling and an "i" button pressed into its edge; the top bar's "Search reservations, guests…" wrapped onto two
lines at 1,024 px. 📖 Read in the code: "Message" said "Sent by email · replies land in your inbox" and recorded a sent message in
each guest's inbox while production's mailer is `log` (`STATE.md`, Switches); the repeat-guests figure said "who have stayed" of
bookings still ahead; "Last stay" showed an arrival to come as a bare date; nationality was two typed letters. **Built:** the card is
a size container — at 960 px of card and below each guest stacks (name and status on top, the facts below, each named), under 520 px
the status joins the facts and an empty Lifetime hides; status and the stay date never wrap, a country name only when over 16
characters and only where the card is narrow; email and phone each on its own line. The search box redone ONCE in the shared list kit
(Guests, Owners, Units, Maintenance): 40 px like the buttons, a centred 16 px icon, the browser's own search styling off, the "i" gone
(its sentence lives in the placeholder and the empty search). The toolbar like the newer pages. Honest words, through one question,
`MailSafetyGuard::sendingNow()` (shared with the New reservation page): while email isn't sent the Message dialog says so, its box,
switch and Send are off, and the server refuses with nothing written; when it is, the message goes out AS THE PROPERTY (its name
and its reply-to address, like every other guest email) and the dialog names that property and says where replies go — or that
they won't reach the operator until a reply-to address is set (and, to a role that cannot open Settings, whom to ask); the email
invites a reply only when one would reach the property. "N of M guests with a booking booked again" (and "no bookings yet —
cancellations and no-shows don't count"); the column is "Stay" — In house · Arrives … · Arrives today · Was due … · Tentative · …
· Left … — and sorts by the date it shows. Nationality picked by name (249, the host's ICU names); Edit opens on the saved code and
keeps one the list lacks, "Kosovo (as saved)". The top bar's search keeps one line ("Search…" at 1,240 px and below; its spoken
name holds the words it shows).
**The independent review** (read-only; 13 findings, the stacked-table screen-reader one only weighed): **one HIGH** — "Left <date>"
for stays nobody left: an arrival due today and one overdue read "Left <a date still ahead>", a booking never checked in "Left …"
(the label took a guest-level stage and one booking's dates); fixed from that booking's own status, and the sort key with it. **Four
MEDIUM**, all fixed: Edit opened on "Not given" for a code the list lacks and Save erased it (🖥 measured: the select's value was
"" — x-model chose before x-if added the option); my contact rule was page-wide and stacked the Units sub-line; "replies land in
your inbox" was never true of email (no inbound mail; no reply-to, so replies went to Vyrobel's sender); the paused-workspace test
passed for the wrong reason once a mailer that sends nothing also refused. **Low**, fixed: a Lifetime holding only "… due" hidden on
a phone; the full table still 939 px (969 with Select) in 890–940 px cards — 🖥 measured, so the stacking point moved to 960 and long
country names may wrap there; two new labels in the grey that measures 4.48:1 on a row; "nobody has booked yet" with only cancelled
bookings; the top bar's spoken name ("and") not holding its visible words; the untested rules. Recorded, not changed (outside the
page or his call): `OPEN.md` D-MAIL-CLAIMS, D-INBOX-DELIVERED, D-REVIEW-REPLY, D-MAIL-GUARD-SHAPES, D-GS2-STACK-A11Y, DEC-s.
**The second review** (of the fixes; read-only): the Stay labels and the sort key agree in every status, and the Edit fix holds against
the bundled Alpine — but nothing tested that `syncNat()` is CALLED (only that it exists), and "add a reply-to address in Settings" was
shown to Hotel Manager, Front Desk and Reservation Agent, who may send but cannot open Settings (a 403 invited). Both fixed; the one
sending property is now `GuestMessageSender::property()` (the page and the controller asked separately) and the dialog names it;
"Tentative · …", the badge's own word, from the shared stage resolver; "cancellations and no-shows"; a loose regex anchored; the sort
test given a stay only the departure key orders right. Its width question (cards of 961–969 px with Select on) was 🖥 measured: inside
the card. Mailpit counting as "not sending" stays, written down: the sending state is first seen in the email pass (`OPEN.md` A2).
Tests: `GuestsListSecondPassTest` (24; each new one RED first, the rest proven by mutation); `OperatorGuestBulkTest`'s paused test now
refuses with mail "working"; three older tests changed with the behaviour he asked for. Mutations `guests-second` 58, all caught
(GS-M19 stayed green at first — the phone rule's own `nowrap` answered for the base rule; tightened); `guests-list` GL-M2 re-pointed
to the new sort key. Gates: 4,359/4,359 on MySQL 8.4.10 and on MariaDB 11.8.9 (tree `0597738d6d3eaf01`; JUnit
logs); 818/818 mutations; money gate FULL unmoved; PHPStan 0, Pint clean. **Live 2026-10-05 05:25 Baku: `rel_AX @ 8345c86e`, committed
locally and NOT pushed.** Preflight read-only from the staged release (email sending "no"; 249 countries; his one guest's nationality
code is one the list lacks — the review's case; 0 portal accounts anywhere); public checks 12/0/0; 1,448/1,448 files match git; drift
+ self-test pass; 0 errors (re-read at 17:47). His live look (signed in himself at the first ask, ~12 hours after the deploy; look
only): every width 390–1,440 inside the card; the search box 40 px, icon centred; Edit opened on his guest's saved code "(as saved)"
and closed unsaved; Message's honest sentence with everything off; the shared search box on Units, Owners, Maintenance; 0 console
messages; signed out, browser closed.

**FIVE PAGES — 2026-10-05 — his "https://vyrobel.com/app/inbox — on ~ New message page design is not good. 2 text is repeated …
/app/housekeeping … Text is not visible completely. Serach box design is not good and its symbol is not well-aligned. Board and list
buttons design is not good … /app/maintenance … Text is not visible completely. And note progress part ordered buttons design is not
cool … /app/units … there must be an option to edit and delete the unit. And Add inventory section design and functionality is not
good. It is so confusing … /app/security — this page design is not good … the same work as the last pages … creativity, advanced
features … build a plan and execute it … ship everything live … test everything via MCP playwright".** Plan posted and executed (his
rule); no migration.

**Inbox — New message.** 🖥/📖 Why: the dialog said the same thing twice (its subtitle and a note under the picker), the finder's
"In-house" was said of an arrival due today, a reply in a `manual` thread was labelled "Delivered" though nothing is sent, and its
three dialogs sat on hand-made backdrops. Built: the three dialogs on the shared solid card; the guest finder with each stay said
by the shared `StayLabel` (Guests' Stay column and the finder can no longer disagree); one sentence where two said the same; a
`manual` thread's reply is "Saved"; the empty finder sends to Guests. `InboxComposeRedesignTest` (11), `inbox-compose` 17/17.

**Housekeeping.** 🔬 Why: the page never used the shared list kit — its own search pill whose icon lost a cascade fight (top of the
row); Board and List were bare buttons in the browser's own look; the list was a 720 px table in a card narrower than that between
~860 and ~1,010 px windows; "In progress" tags had no colour (the tone was never defined); "Today’s cleaning" stood over every day's
tasks. Built: the shared search box; Board | List as one segmented control (pressed state, focus ring); the list fits its card at
every width (a size container — 820 px of card and below each room stacks, every fact named); each room says its day (Today ·
Tomorrow · Overdue · <date>); "Cleaning across your rooms, by day"; housekeeper chips that count what clicking shows; Assign hidden
while nobody is on the roster, with one sentence and (for who may) a link to Roles & access; the detail reopens after a photo; the
dialogs on the shared card; the lane's arrow by class (A10). `HousekeepingPassFiveTest` (14), `housekeeping-five` 24/24.

**Maintenance.** 🔬 Why: the board's table was 945 px — 213 px past its card at a 1,024 px window, 589 px at 390 — so the right
columns slid behind a hidden scrollbar; the work order's Note / Progress / Part ordered were inline-styled 11.5 px pills chosen by
colour alone, with no keyboard or screen-reader state; 📖 "Part ordered" only wrote a note — the board's "Awaiting parts" never
followed. Built: the board fits its card (980 px of card and below each work order stacks); "Assigned to"; the kind of update is one
radio group, 13 px, chosen by a tick as well as colour, keyboard and screen-reader state included; "Part ordered" names the part and
its date and marks the work order waiting on it, so the board's tag and panel follow; Post off until there is text; Mark resolved
once, for an open work order too (as the board and the service allow); the details wrap on a phone; honest words ("It shows under
their name on this board. Nothing is sent to their phone."; no "Busy" / "Available" guessed from a count; "tonight’s rate for the
units blocked"); a photo added from the board comes back to the board. `MaintenancePassFiveTest` (13), `maintenance-five` 22/22.

**Units.** 📖/🔬 Why: Edit lived only in the row's ⋯ menu and there was NO Delete (a test pinned its absence: a hard delete cascades a
unit's history and orphans money); room types could not be edited; units were added one per dialog; the setup guide's third step
never completed, so the guide left while nothing could be booked; under "All properties" a new room type went to the first property
unasked; 🔬 a "Dorm" room type's units failed with a server error ("Data truncated for column 'unit_kind'" — the two enums differ in
one word); the 900 px table ran 168 px past its card at 1,024 px; a leftover `.vy-units { display: flex }` (for unit pills no page
uses) matched the page's own wrapper; paused workspaces could still change arrival info and photos, and the photo routes found a room
type through the tenant only (DEC-k). Built: room types first, as compact cards — what each is, its units and how many take bookings,
what keeps it from being booked (No units yet · No unit taking bookings · No prices yet → Set prices · Units and prices set), Edit
and Delete on the card, photos in a dialog of their own that reopens after each change; then the units, with Edit on every row and
Delete in its menu. **Delete removes only what nothing has recorded** — one rule, `InventoryDeletion`, read by the page (each row
and card says beforehand whether it can, and why not) and asked again by the controller inside the delete's transaction with the
row locked; for a unit with history it names what is on record and offers Pause; a room type only when empty (its rate plans,
prices, seasons and photos go with it). Edit room type: name and who it sleeps; its kind only while it has no units; the standard
occupancy shown to guests follows the adults when it had followed them. **Add units:** one code, a list or a range ("101-110,
120"), up to 100, previewed as typed ("Adds 4 units: G1–G3, G10"), codes already used refused by name — and a room type can bring
its units in the same dialog. "All properties" asks which property (and refuses one the person does not work in). The setup guide
stays until there is a price, and says what a room type and a unit are. A Dorm's units are dorm beds. The table fits its card (860
px of card and below each unit stacks). Arrival info and photos refused while paused (the reveal, a read, stays). The words "adding
inventory is read-only" became "changes to units and room types are read-only". `UnitsPassFiveTest` (17), `units-five` 34/34;
`UnitLifecycleTest`'s "THERE IS NO DELETE ROUTE" became "DELETES ONLY A UNIT NOTHING HAS RECORDED" (his request; the reason kept).

**Security.** 🖥 Why: a 560 px column of cards beside full-width ones with the right half of the page empty, "Current password"
labelled twice, four dialogs on an undefined `vyp-scrim` class with inline positioning, 101 inline styles, flash colours under AA.
📖 Beneath: "Sign out of all other devices" landed on Profile; one wrong password showed in three cards at once (the key
`current_password` is shared by five forms); removing the allowlist rule that admits you locked you out on the next request (the
add path had a guard; the remove path none); disconnecting X said "Google disconnected." (every link message said Google); a passkey
added from Safari or Firefox was named "This device" for ever; "a passkey never leaves your device" (a synced one does). Built: one
header — the state derived from two-factor and four real counts — then Signing in (change password beside two-factor and passkeys;
sign-in accounts below) and Devices and access (sessions, trusted devices, IP allowlist, connected apps), and Export your data; every
dialog on the shared card; each device signed out from its row (found by a one-way token, never its session id — a session id is a
sign-in), "Sign out of all other devices" back here; each error once, in its own form, its dialog reopened; the allowlist remove
refuses the rule that admits you and says what to do, and removing the last rule says the allowlist is off; X is X; a passkey is
named for its platform; honest empty states (trusted devices with two-factor off; sign-in methods not switched on). The shared
passkey card and two-factor panel changed for the owner page too (scoped errors, honest copy, unique ids). `SecurityPassFiveTest`
(14), `security-five` 19/19.

Contracts lowered: A8 (x-show beside an inline display) 22 → 14 sites; A10 (string :style) 10 → 9.
**The independent reviews** (three, read-only, one per group of pages). Inbox / Housekeeping / Maintenance: one finding — the work
order's Mark resolved dialog headed "What was done" with a `<div>`, so the box was named by its placeholder (its neighbours and the
board's own were real labels); fixed, test first. Security: one — a wrong two-factor code showed twice (the page's banner and under
the field: the confirm form had no `_form` mark); fixed, and the reviewer's note that the sessions comment overstated ("no sign-in
sets remember me" — the guest portal's does) corrected. Units: nothing at the bar; two smaller ones fixed anyway — a channel
connected to one of a room type's RATE PLANS did not keep the room type (that mapping CASCADEs too), and two codes the column's
collation counts as one ("Cafe" / "Café", or one added elsewhere at the same moment) ended in an error page (🔬 "Duplicate entry …
for key 'units.units_property_id_code_unique'") — now a sentence. **The full suite found one more:** `InboxStageVipBadgeTest` had
asked for `?thread=`, a parameter the inbox never read, so it opened the default conversation and passed only because the New message
list printed "In-house" elsewhere on the page; when that list said "In house" it went red. It now opens the conversation it names and
reads the badge itself. **Two mutations survived at first** (SC-M20, SC-M21): with a form's mark removed its error moved to the top
banner, still once — the tests counted, they did not place; they now post the form's own hidden fields (read from the page) and
assert where the message is. **The second review** (of the fixes, read-only): one finding — the "already used" catch wrapped the whole save, and the availability
rebuild inside it has its own unique index (room type, date): a race there would have been told as a code problem. Now only the
units' (property, code) index is answered in words; any other is rethrown (test first, UF-M37). Two notes tidied: "1 channel
connection, 1 channel connection" (a mapping on the room type and one on a plan) is one count; the shared two-factor panel's comment
no longer claims "here only" on the owner page, whose own forms are unmarked (D-OWNER-SEC-ERRORS). It confirmed the rest: every 2FA
error shows exactly once on the operator page; only the guest portal's guard offers "remember me"; the badge test and the hidden-field
helper sound.
Recorded for his go: `OPEN.md` D-OWNER-SEC-ERRORS (the owner Security page prints one password error under six fields),
D-STAY-WORD ("In-house" / "In house"). Tests: `InboxComposeRedesignTest` 11, `HousekeepingPassFiveTest` 14, `MaintenancePassFiveTest`
14, `UnitsPassFiveTest` 20, `SecurityPassFiveTest` 15 (each RED first); older tests changed with what he asked for (Units' no-delete
pin, the setup guide, the action list, the dialog count) or because they tested the wrong thing (`InboxStageVipBadgeTest`). Mutations
`inbox-compose` 17, `housekeeping-five` 24, `maintenance-five` 23, `units-five` 37, `security-five` 22 — all caught; `units-list`
UL-M8/M22/M23 and `rates-page` RP-M32 re-anchored. Gates: **4,432/4,432 on MySQL 8.4.10** (32,538 assertions; tree `aba564f6e42d92a0` = this code with the docs before these figures; 20:47–21:05 Baku) · **4,432/4,432 on MariaDB 11.8.9** (tree `73ef8334b2507a1f` = the same plus UL-M8's re-aim, a mutation-set file; 21:43–22:01 Baku) — both read from the runs' JUnit logs: 0 failed, 0 errors, 0 skipped · mutations **941/941 landed and caught across every set** (34 sets; baseline 527/527 green; 21:05–21:32 Baku, UL-M8 re-aimed and caught at 21:36 — the full run had it green: the new database-level refusal answered for the pre-check, so it is now aimed at the test that tells the two layers apart) · the money gate **FULL, unmoved** (`929 / 10928000 / 0 / 42 / 16 / 0`, digest `a0e1e01d92d608f6 / cc588f6bb9562863 / 2f5598c92aadf105 / 9bac428a1b859297`; reseeded 22:02 Baku) · PHPStan L5 0 errors and Pint clean on the whole codebase · the 54 doc-reading test files. ⚠️ The first MariaDB attempt was discarded: the MariaDB tools are keg-only (not on the PATH), so the server never started and that run had no database; and stopping it left its `pest` worker running against MySQL until it was found and stopped. After the runs, two lines of the Security view were rewritten so the secret scan stops reading `$hasPassword ? '…password'` as a password (the same words; the targeted tests re-run, 50 green) — the commit's tree differs from the suites' by those two lines and the UL-M8 aim. Committed locally, not pushed (his rule); the deploy and his live look follow. **Live 2026-10-05 22:08 Baku: `rel_AY @ c73a97cd`, committed locally and NOT pushed.** Preflight read-only from the staged release
(PHP 8.4.19, MariaDB 11.8.9: the seven route gates in place; sessions on the database driver, so a device's Sign out removes that
sign-in; the codes field's parser on production's PHP — "101-103, 105" read as "101–103, 105", "1-500" refused; the eleven changed
views compile; only Vyrobel Internal has inventory — 2 properties, 2 room types, 0 priced, 3 units, 2 that Delete would remove, every
blocker read in 29 ms; 0 allowlist rules anywhere; one work order in progress, none waiting on a part); public checks 12/0/0;
1,452/1,452 files match git; the drift check and its self-test pass; 0 errors since the swap (re-read 2026-10-06 02:38). His live look
(he signed in himself in the automated window, ~02:25 Baku on 2026-10-06; look only): Units — the guide, the room-type cards, the
table, the Delete dialog for unit 101 opened and cancelled; Security and Housekeeping at 1,280 px as built; all five pages at 1,024 and
390 px with no sideways scroll and nothing past a card — except the Inbox's filter tabs at 390 px, "Closed" cut to "Clos" (older than
this pass) → fixed next. ⚠️ **The look ended early, at the edge:** at ~02:30 the CDN's "Checking your browser" page (403) stood in
front of the automated browser, which then reached only the sign-in page. The app had not ended the session (V 02:44, a read-only
production read of times only: its row untouched, last activity 02:27:56, expiring by itself at 04:27:56) — the browser's cookie had
been replaced by a signed-out one (D-EDGE-SIGNOUT). Maintenance at 1,280 px and the New message dialog were not opened live. ⚠️ **A
public-checks run at 02:31–02:38 then answered 1 PASS · 10 FAIL** — the edge answering this Mac (504s and 307s to the same address in
~80 ms, `/up` included, which the app never redirects; a curl from this Mac at 02:35 got 200s). At 02:38 the server, asking through
the public hostname from its own address, got 200 on every page; 0 errors logged; the re-run (02:39–02:44) 12/0/0. **No rollback** —
rule 8 says roll back first; a control ran first, and the ruling is his (DEC-t; D-EDGE-CHECKS). The browser's vyrobel.com cookies
cleared and the browser closed.

**THE INBOX'S TABS ON A PHONE — 2026-10-06 — seen on his live look of `rel_AY`.** 🖥 At 390 px the five filter tabs ran 24 px past
their box and "Closed" read "Clos": the shared `.vy-seg` box has `overflow: hidden`, which lets a flex item shrink below its own
words, and its options do not wrap. Built (the Inbox's own stylesheet; the shared box unchanged for its other pages): at 560 px and
below the tabs take the toolbar's whole line in two even rows — All · Unread · Arriving today over Assigned to me · Closed (a six-column
grid, three spans of two over two of three: no lone tab beside an empty cell) — a label wrapping rather than cut on the narrowest
phones, New message across the line beneath; above 560 px, where the toolbar always gives the tabs a line (they need ~394 px), one row
as before. The tab shown carries `aria-current="page"` — a screen reader hears which filter is on, not only the white fill. The
toolbar's inline margin moved into the stylesheet. 🖥 Local walk (tenant three) at 320 / 360 / 375 / 390 / 430 / 560 / 561 / 768 /
1,024 / 1,280 px: no tab clipped, none past its box, no sideways scroll; at 320 "Arriving today" takes two lines. 🖥 The shared box's
other pages, measured at 390 and 320 px: Reservations (its seven tabs wrap), Billing checkout and Notification preferences (two
options each) — no option cut; the owner Tax Center not measured (an owner's sign-in). That measurement found Notification preferences
scrolling sideways at 320 px (D-NOTIFPREFS-320, his go). Tests: `InboxComposeRedesignTest` +2 (13; both RED first — the first draft
of one pinned the codebase's whitespace and counted the shell's own `aria-current`, corrected before any code). Mutations
`inbox-compose` IB-M18…M24, 24/24 caught. Gates: **4,434/4,434 on MySQL 8.4.10** (32,565 assertions; tree `d24833fe404086c0`, 02:44–03:02 Baku — before two comment dates were corrected) · **4,434/4,434 on MariaDB 11.8.9** (32,565 assertions; tree `78d633402013dbf0` = the final code before these docs; 03:03–03:20 Baku) — both from the runs' JUnit logs: 0 failed, 0 errors, 0 skipped · `inbox-compose` 24/24 caught (no other set aims at the changed view) · the money gate **FULL, unmoved** (`929 / 10928000 / 0 / 42 / 16 / 0`, digest `a0e1e01d92d608f6 / cc588f6bb9562863 / 2f5598c92aadf105 / 9bac428a1b859297`; reseeded 03:20 Baku) · PHPStan L5 0 errors (1,089 files, from a cleared cache), Pint clean · the doc-reading tests — 69 files, the list re-derived from the tests (630 tests, 0 failed). **Live 2026-10-06 03:29 Baku: `rel_AZ @ 59799d4d`, committed locally and NOT pushed.** One file shipped (the Inbox view); preflight read-only from the staged release (the seven route gates, sessions on `database`, all eleven views compile on PHP 8.4.19 / MariaDB 11.8.9); public checks 12/0/0 (03:30–03:35); 1,452/1,452 files match git, 0 differ; drift + self-test 3/3; 0 errors since the swap (11 warnings, all `security.refusal`, from the exposure battery; the counter shown to count — 44 warnings since midnight UTC on the same log). His look at a phone's width is left: the Inbox is behind his sign-in.

**TEAM CHAT AND A LIVE MESSAGES PAGE — 2026-10-06 — his "https://vyrobel.com/app/inbox — messages must be owner and the employees as
well … there should be option that owner can do the conversation with employees … some drop down … toggle for client and
employees … attachment sending option picture sending option voice sending option … notification icon … notification sound … chat
should be Ajax based runtime … complete this page and upon completion test everything with MCP playwright".** Read as: "owner" = the
account owner in the back office, "employees" = the staff, "client" = guests (stated in the plan; unit owners keep their own
conversations). Design `docs/TEAM-CHAT-DESIGN.md`; plan posted and executed (his rule).

📖 The audit, three readers in parallel: no staff-to-staff messaging anywhere; nothing polled (every reply a reload); the Inbox is
`guests.view`, which Housekeeping, Maintenance, Accountant and Revenue Manager do not hold — the employees he named could not open
Messages at all; guest and owner threads share one table and ONE unread counter for all staff, so a staff conversation stored there
would have shown in every front-desk agent's list and its files would have opened through a door that checks only the organisation;
when a guest or an owner wrote, nobody on the staff was told (`inbox.message_received` had no handler); no audio anywhere; the guest
portal showed no file at all; our own header switched the microphone off on every page. 🔬 Production replaces our CSP with
`upgrade-insecure-requests` (the host) — `OPEN.md` D-PROD-CSP.

**Gate 1 — team chat (`/app/inbox/team`).** Its own tables (`team_conversations`, `team_conversation_members`, `team_messages`):
a conversation belongs to its MEMBERS — each with their own read position (a message id: ULIDs sort by the millisecond, a timestamp
is one second wide). Every back-office user may use it (no new permission — the RBAC pins stay); only members see a conversation,
its messages or its files (anyone else: a 404). One writer `TeamChatService` (start — one direct conversation per pair, a group of
two or more; send; markRead — forward only), one reader `TeamChatReader` (the page's first draw and the poll read the same rule),
one file rule `TeamChatFiles` (private disk; pictures in place, voice notes played with byte ranges for Safari, everything else
downloaded; types from the file's own bytes — 🔬 Chrome's audio-only WebM reads as `video/webm`, Safari's MP4 as `video/mp4`).
The page: conversations and the open one side by side (one at a time on a phone, with a way back); New conversation (one colleague
→ your direct conversation, several → a group, optionally named); Enter sends; pictures, documents (also pasted or dropped in) and
a voice note up to 3 minutes, heard before it is sent; "Sending…", and a message not sent comes back into the box with the reason;
day dividers, names in groups, "Seen" / "Seen by Anna, Ben"; "Load earlier messages". Live: the open conversation every 3 s (20 s
hidden, nothing after 30 minutes idle). A support (break-glass) session borrows a staff member's view, not their conversations: the
page says they are private and every read is a 404. A paused workspace reads, never sends. **The Guests | Team toggle** at the top of
Messages, each side with its live count; someone without `guests.view` sees Team only, and the sidebar's Messages — hidden from four
of the eleven roles before — leads them there.
**The bell, the badge, the chime (every back-office page — `partials/live-pulse`).** `app/pulse` every 20 s: the bell's own count,
team unread, guests unread (for who may read them); the bell's badge always rendered (hidden at 0) and its list re-rendered from the
same component when the count moves; Messages' badge and the tab title; a chime synthesised in the browser (no file), on unless
switched off in the chat, after the first click or key. One bell entry per conversation ("Max Brown sent you 2 messages", "Anna Lee
wrote in Morning shift"), in-app only, names only — reading the conversation reads it.

**Gate 2 — the Guests side live.** A guest's or an owner's message now rings the bell of the person the conversation is assigned to,
else everyone with `guests.view` (never a housekeeper): one entry per conversation ("2 new messages from Ines Walk"), the
conversation's name by the Inbox's own rule (`ThreadName`, extracted), and opening it reads it for everyone (the unread counter is
shared). The open Inbox asks a small stamp every 5 s (`app.inbox.live`) and, when it moves, re-reads itself and swaps the counts, the
list, the conversation's header and its messages in place — never the reply being written.

**Gate 3 — files and voice notes to guests and owners.** A reply may carry up to five pictures or documents and a voice note — where
the other side can open them: an owner (their portal) or a guest's Direct conversation (their guest account); refused in words on
Booking.com, Airbnb, WhatsApp and email. Sent without a reload (a plain form still works without JavaScript). One shared definition
of a chat file (`ChatFiles`); one writer of message attachments (`MessageAttachments`, the owner portal's too); the guest portal's own
door (`guest.messages.attachment` — that guest's own conversation only); the guest's page and the owner's portal show pictures and
documents and play voice notes. GDPR: a guest's erasure takes the files in their conversations too. The recorder and the player are
ONE script (`partials/chat-media`) for both pages.

**The independent review, and what changed** (`TEAM-CHAT-DESIGN.md` §9). Three of its findings were checked against the code and
were real before anything was fixed: (1) a colleague's message could be skipped — the page asked "after the newest message I hold",
and that could be your own; and sending moved your read position, so theirs counted as read; (2) the open Inbox read conversations
nobody looked at, and refreshed itself for ever — its refresh re-read the page, an opened conversation is read on every read, and a
read moves the stamp; (3) the refresh could swap in a different conversation, the newest, when the address named none. Also: a support
session saw the staff member's team-chat bell entries; ex-staff were still rung; read bell entries piled up; download names kept
invisible direction marks and their claimed extension; a guest's erasure left the staff's bell entries naming them — and the older
"Conversation assigned to you · <name>" entry, which the same line now takes too; a reply's file refused after the words went; erased
files deleted before the erase was kept; two microphones at once; the pulse giving up on a passing error. Each fixed with a test that
failed first or a mutation that proves its test can fail. 🔬 And the walk's own measurement: this Mac's PHP takes **2 MB a file and
8 MB a request** (PHP's defaults) — "up to 10 MB" was not true here; the pages and the refusals now read the real ceilings
(`ChatFiles::limits()`), and production's are read by the preflight.

🖥 **Local walks (tenant three, two people and a guest at once, two separate browsers).** Before the review: a direct conversation —
sent live, "Seen" within one poll, the reply on the other screen in 3.0 s; the other person's dashboard badge, bell and tab title
updated without a reload; a picture and a PDF in 2.8 s; a real 3-second microphone recording heard before sending, received in 1.2 s,
played (206 `audio/webm`); a named group; the phone layout; the sound switch remembered; signed out → the page said so. Guests side:
a guest's message rang the bell; while the conversation was open her next message appeared by itself; a reply with text, a picture, a
PDF and a voice note went out with no reload; the guest saw and heard all of it in her account. Found and fixed on that walk: the chat's
search box grew 300 px tall; the phone's back arrow showed on a desktop; the bell stayed at 1 after reading; a stale subtitle; photos
moving the conversation as they loaded; the browser's blue focus ring on a photo. ⚠️ A transient 500 on the poll was my own mistake:
a wrong trait namespace in a new invokable controller — Laravel loads it while registering routes, so EVERY request failed until it
was fixed (`php artisan route:list` sees it; `php -l` cannot).
After the review: with Lina's poll held back and her own message her newest, Tomas's earlier message arrived in its place when the
poll came back (the old code skipped it); with Tomas's window unfocused Lina's message came in unread and his bell said 1, read the
moment he touched the page; a draft and a picture stayed with Lina's conversation through a visit to Morning shift; a send refused
(500) after Tomas had moved on came back to ITS conversation with its picture and reason, a notice said so and cleared when he opened
it; a 500 on the poll slowed it, a 419 stopped it and said "signed out" (the pulse the same); the microphone's "Starting…" could be
cancelled, a double press started one recording, a recording still going stopped when Lina left the conversation and a finished one
waited for her; the voice slider by keyboard ("0:05 of 0:14"); the open Inbox: a guest's message → one live refresh (nothing read),
one read POST because Tomas was there, one more refresh, then only the stamp every 5 s — the loop gone; a second guest's conversation
becoming newest left the open one in place, unread; Enter a new line and ⌘+Enter sent; a 3 MB file and five files over 7 MB together
refused before sending; a refresh during a playing voice note waited for it; a header swapped live kept its Assign button working.
Found and fixed on that walk, all on this page: a reply's line breaks collapsed in its bubble (the guest's and owner's pages kept
them); the list's unread dot sat on the time's last digit; the "Direct" chip squeezed into the shell's 34 × 34 Channels tile (a
shared class name, live on production since the Channels page); a conversation opened at its OLDEST message, so a live arrival
landed out of sight; a stale "not sent" notice; Laravel's "Too Many Attempts." shown as the reason (now our own words except for our
own refusals).

Tests: `TeamChatTest` (27), `InboxLiveTest` (9), `InboxAttachmentsTest` (10) — each RED first except the reply's file-handling pair
and the limits test, written after their code and proven by their mutations; mutation sets `team-chat` (29) and `inbox-live` (28) —
the whole harness re-run on the final code — **1,005/1,005 landed and caught across all 36 sets** (`team-chat` 29 and `inbox-live` 28 new; V 18:21–18:53 Baku); 0 not caught, 0 did not land. ⚠️ On their first run two survived, both the tests' fault: TC-M8 ("every chat message is also emailed") — the mail fake
cannot see a notification's mail channel; and IA-M5 — no data shape ever reached the guest door's "never an owner's conversation"
layer. Both tests sharpened (the NotificationSending event; a conversation naming both an owner and the guest), both caught.
Gates: **4,480/4,480 on MySQL 8.4.10** (32,952 assertions; 17:38–17:58 Baku) · **4,480/4,480 on MariaDB 11.8.9** (32,952 assertions; 18:00–18:19 Baku) — both on tree `0b3f80cfe6b338b6` (the code before one Blade comment was corrected and these docs written) and read from the runs' JUnit logs: 0 failed, 0 errors, 0 skipped · PHPStan L5 0 errors (a whole run, 31 s at 18:21 Baku, from a result cache deleted by hand — a run under the output wrapper had read the old cache in 0 s) · Pint clean (the changed files) · money gate FULL `929 / 10928000 / 0 / 42 / 16 / 0`, digest unchanged (V 17:59 Baku, reseeded 17:58).

**Deploy and live look.** ✅ LIVE on `rel_BA @ 16b15e2f` (V: swap 2026-10-06 18:59:10 Baku, 5 ms; 45 files shipped; the additive migration — the three team chat tables — run from the staged release before the swap, 1 s; the read-only preflight from the staged release: the nine route gates, this host's finfo reads all three browser recording formats as playable, the private disk's folder writable, PHP's ceilings 256 MB (so our 10 MB stands), the twelve views compile on PHP 8.4.19 / MariaDB 11.8.9; public checks 12/0/0; 1,457/1,457 deployable files match git; the drift check passes, self-test 3/3; 0 errors since the swap — 11 warnings, all `security.refusal`, from the exposure battery — and nothing logged during his look). **His live look is done** (he signed in himself; look only, his choice — nothing sent, nothing started): Messages with the Guests | Team toggle and its honest empty states; Team with "No conversations yet" and New conversation (the dialog centred, 480 px, focus inside, his colleagues listed, Escape closes it); the microphone allowed on Team only (`microphone=(self)` there, `()` on the dashboard); the page's own limits from the web PHP — 10 MB a file, 255 MB a request; the stamp every 5 s and the pulse, all 200, no read sent; 390 px with no sideways scroll on both pages; no console message; 0 team rows and 0 team or Inbox bell entries on production afterwards (read-only). ⚠️ A correction, said to him: I told him his workspace had one back-office person — that is "Vyrobel Test Workspace"; he works in the organisation whose working property is "Internal Test Property", where team chat lists five colleagues. He was signed out, the site's cookies cleared and the automated browser closed. Left: a two-person team chat test on production (his go — it writes in a protected workspace).

**GITHUB AND MEMORY — 2026-10-06 — his "update the entire memory each and everything and do all the GitHub work … make sure
everything is correct no any bug no any issues remaining".** 🔬 Read first: GitHub's `main` at `fc1ee357` (2026-10-05) with eleven
local commits since; every other local branch contained in `main` or already on GitHub; one workflow, `.github/workflows/ci.yml` —
and **every one of its 106 runs (2026-08-20 → 2026-10-04) had failed or been cancelled: 104 + 2, 0 passes**, each at "Prepare the
environment": `set: Illegal option -o pipefail`, because GitHub runs a container job's steps with `sh -e` and the workflow writes bash.
**No gate had ever run on GitHub**, while `DEPLOYMENT.md` §7.6 said every gate ran on every push (corrected there, dated; `OPEN.md`
D-CI-NEVER-RAN). Fixed: `defaults.run.shell: bash` on the gates job. Then what the first real run would meet: the container runs as
**root**, which ignores folder permissions, and two fixtures refused a write or a delete with a read-only folder — the Inbox's "disk
refuses" test (mine, this pass's morning) and ADMIN 05's "stuck file" test. Rebuilt to refuse by structure (a file where the folder must
go; a folder where the file must be), the assertions unchanged; both mutations re-run and caught (A05-M16, IA-M11); Pint over the whole
repo clean. The memory: `RULES.md` writes out the four hard stops (named, never defined in the canon before) and the lessons;
`SYSTEMS.md` §17 (GitHub and its checks); `STATE.md`; `OPEN.md`. The hard stops, the push and the first real run follow below.
**The first push** — the four hard stops one at a time (2026-10-06 19:5x Baku): private = `true` · the identity `Subhankhan12` ·
`origin/main` `fc1ee357` an ancestor of `92719a10` (12 commits, a fast-forward) · the history scan: 8,600 blobs, 128 hits in 40
locations — the recorded baseline, none in any of the 157 files the 12 commits touch. Pushed 19:56:40 (`fc1ee357..92719a10`);
`git ls-remote` read `92719a10` back. **GitHub's first run that could reach the gates** (37491735299): "Prepare the environment",
Gate 0 (the runtime contract), the assets, Pint and PHPStan all PASSED for the first time — and the suite DIED after ~9 minutes:
`Maximum execution time of 300 seconds exceeded`, inside dompdf. 🔬 The cause was ours: the admin delete called `set_time_limit(300)`
to give a large deletion time — and where the limit is 0 (the command line; the whole test run is one process) that call IMPOSED
300 seconds on everything after it; GitHub's slower machine ran out. Fixed — raise, never lower (`$limit !== 0 && $limit < 300`),
two tests (one RED on the old line) and two mutations (A05-M50, A05-M51, caught). And read before the next run: all nine concurrency
test files hardcoded their workers' database as `127.0.0.1:3306` — inside the container MySQL is the service `mysql`, so every
worker would have failed to connect (the same cause as `CLAUDE.md`'s "workers ignore an exported DB_PORT"). They now take the
suite's own resolved connection (`raceWorkerDbEnv()`); 19/19 pass here.
**The second push** (`92719a10..d0dd97aa`, 20:23:10 Baku, after the four hard stops again — the history scan the same 128 hits in
40 locations, none in the commit's 21 files). **GitHub's second run** (37495290215) ran the whole suite for the first time:
**26 failed, 2 skipped, 4,454 passed** — every failure an environment difference, none a product fault on this Mac (4,482/4,482 on
MySQL 20:23–20:40 and on MariaDB 20:41–20:58 Baku, the same commit). Each cause was read from the run's own messages, then
**reproduced here** with a temporary `.env.testing` copied from `.env.example`, as the workflow builds its `.env` (deleted after):
- **OAuth, 14 tests — "Invalid key supplied":** Passport's signing keys live in `storage/` and are never committed; the workflow
  never made any. It now runs `php artisan passport:keys --force` for each throwaway runner.
- **The cookie notice, 3 tests, and the secure cookie, 1 — `.env.example` was wrong for any new environment:** `APP_NAME=Laravel`
  made the session cookie `laravel-session`, which the notice does not disclose (production's is `vyrobel-session`); and
  `SESSION_SECURE_COOKIE=` / `FORCE_HTTPS=` were EMPTY lines — an empty line is a value (`''`), so a deploy copied from the
  template got non-secure session cookies and plain-http URLs, the very defect the 2026-08-07 audit had added the line to close.
  🔬 Production itself is right (read-only: `vyrobel-session`, secure `true`). The template now says `APP_NAME=Vyrobel` and keeps
  the two lines commented out, with why.
- **Billing, 3 tests — and a hidden network call:** the production-path tests let the container build the REAL Stripe client. Here it
  found the local test-mode secret and called Stripe's test API on every run (🔬 0.27–0.53 s per test, against Price ids that do not
  exist there — "passing" only because Stripe's own error is not a RuntimeException); on GitHub, with no secret, it refused to start.
  The guards still run on the real production path (they read the environment and `services.stripe.fake`), but the transport is the
  fake now and the tests read what it recorded — the real Price ids (0.03 s each; nothing leaves the machine).
- **Country names, 5 tests:** names come from ICU (`ext-intl`, here and on production — 🔬 production: Japan); GitHub's image leaves
  it out ON PURPOSE (DEPLOYMENT §7.5.1b: the guarded fallback is the two-letter code). The tests assumed it; they now assert the names
  where intl is loaded and the documented fallback where it is not.
The two skips are the auto-memory tests (that file lives on this Mac only — by design). Mutation sets aimed at the changed tests
re-run: ADMIN 05 51/51, ADMIN 06 87/87, Guests second 58/58, New reservation bookable 29/29 — 225 landed and caught, 0 not caught, 0 did not land (V 21:18–21:23 Baku).
**The third push** (`d0dd97aa..693b68fc`, 21:27:29 Baku, the four hard stops again — the history scan unchanged, 128 hits in 40
locations, none in the commit's 14 files). GitHub's run 37503697885 on `693b68fc` (2026-10-06 21:27–22:09 Baku) is **the first green run in the project's history — every gate**: the runtime contract, the assets, Pint, PHPStan L5, the full suite (**4,480 passed, 2 skipped** — the two auto-memory tests, whose file lives on this Mac only — 32,957 assertions, 2,125 s), the money gate (FULL, `929 / 10928000 / 0 / 42 / 16 / 0`), and the marketing content (9), external-claim (23) and cookie forward-guard (15) gates (V: `gh run view`, the run's own log). Production was not redeployed: it
stays `rel_BA @ 16b15e2f`, and nothing in the commits after it changes what a web request does (the delete's time limit raises a
web request's limit exactly as before — ⚠️ not at exactly 300 s, production's own value: see the fourth push, below; the rest is
tests, the workflow, the template and docs). The final docs commit is pushed after this one, under the same four stops; its own run
is read, not recorded here.
**The fourth push, and a sentence that was not true** (22:14–22:31 Baku). `693b68fc..02946350` at 22:14:51, after the four hard
stops (private · Subhankhan12 · a fast-forward of one commit · the history scan unchanged, 128 hits in 40 locations, none in the
commit's 5 files); `git ls-remote` read `02946350` back. Then, checking the record against the host before closing: the paragraph
above and STATE said the delete's new time-limit line raises a web request's limit "exactly as before". 🔬 Production's web PHP
runs `max_execution_time = 300` (V 22:30:53 Baku: `/opt/alt/php84/etc/php.ini`; the selector's `alt_php.ini` sets only
extensions; no `.htaccess` or `.user.ini` override; `set_time_limit` not disabled — and the same `php.ini`'s `post_max_size = 256M`
is the 255 MB the live team chat page reads, so these are the web PHP's files). At exactly 300 the line (`$limit < 300`) did
nothing, where the live code starts a fresh 300 s: true below 300, untrue at production's own value. Fixed: `$limit <= 300` — a
fresh 300 s at 300 or less, as before; "no limit" and a longer limit are kept. Reading the guard again found a gap: "never lower"
has two halves and only "no limit" was tested — a mutant dropping the `<= 300` clause would have cut a 600-second host to 300 with
every test green. Now a test (600 kept) and mutation A05-M52, caught. The boundary itself (300 restarted, or left running) is
invisible to a test — `ini_get` reads 300 either way — so it rests on the code comment and this record. Gates: the delete's test
file 28/28 on MySQL 8.4.10 and 28/28 on MariaDB 11.8.9 (249 assertions each; JUnit 0 failed, 0 errors, 0 skipped); ADMIN 05
mutations 52/52; PHPStan L5 0 errors (21 s, cold cache); Pint clean. And `DEPLOYMENT.md` §7.5.4's `max_execution_time` row, which
said "no long-running web path: exports and PDFs are queued", now names the two that are: the delete, and the PDFs that 15
controllers render in the request (`PdfService`). GitHub's run on `02946350` (37509822810, 22:14:55–22:45:49 Baku): green, every
gate — the suite 4,480 passed + 2 skipped (32,957 assertions, 1,519 s), money FULL with the same six figures and row digest,
marketing 9, claims 23, cookies 15 (V: the run's own log).
**The fifth push** (`02946350..1f79446c`, 22:50:41 Baku — the four hard stops: private · Subhankhan12 · a fast-forward of one
commit · the history scan over 8,644 blobs, 128 hits in the same 40 locations, none in the commit's 7 files; `git ls-remote` read
`1f79446c` back). GitHub's run 37514423506 on it (22:50:46–23:13:34 Baku): green, every gate — the suite **4,481 passed** + 2
skipped (32,960 assertions, 1,101 s; one more than before — the new test), money FULL with the same six figures, marketing 9,
claims 23, cookies 15 (V: the run's own log). The record commit after it is pushed under the same four stops; its own run is read,
not recorded here.

**ROLE SIGN-IN — 2026-10-09 — his "I can't log into any of the role it just tell me that you don't have access to this and when I
click on back to dashboard it is also not working … make sure all of the roles that we have created … we should be able to log in all of
them".** 🔬 Read first (production, read-only): the refusal log since 2026-10-07 holds one entry shape only — one user of one
organisation refused `GET /app/billing` (layer `policy`), ten times on 7 Oct and once at 12:43 UTC today (16:43 Baku, a minute before his
message), five of them inside nine seconds — the "Back to the dashboard" clicks. The two ids are ULIDs: the organisation was made
2026-09-23 12:05:16 UTC and the user 1 s later — "Vyrobel Internal — TEST TENANT (not a customer)", built by JOURNEY 01 through
`TenantProvisioningService` with a 14-day trial (HISTORY, JOURNEY 01), so its trial ended 2026-10-07 12:05 UTC, four hours before the
first refusal. ⚠️ A read-only database read to confirm it was refused by this session's permission layer and not worked around; the
founder-approved write below read the state back instead.

**Why.** `EnsureSubscriptionActive` sends every `/app` page of a lapsed workspace to the Paywall, and the Paywall is
`can:billing.manage` — `Property Owner` alone in the catalogue. So the admin saw the Paywall and every other role got the 403, whose
"Back to the dashboard" went to `/app/dashboard`, which redirected straight back: a loop (the F-50 class — a refusal whose way out is
not one). The workspace switcher was caught by the same redirect. `BillingAccessGateTest` only ever signed in as `Property Owner`, so
the other ten back-office roles were never driven through a lapsed workspace.

**His workspace (F: "Extend its trial", 2026-10-09, AskUserQuestion; the date offered was 31 Mar 2027).** One guarded write on
production from the live release: premises checked first (the organisation's id and name, not suspended, one subscription row,
`trialing`, no Stripe id, trial already ended — any failure aborts with nothing written), one row updated inside a transaction with
an audit row (`subscription.trial_extended`, actor `system`, before/after dates and the reason), then read back. **V:** before
`trial_ends_at 2026-10-07T12:05:16Z`, lapsed yes, `is_internal` yes, status `active`; after `2027-03-31T23:59:59Z`, on trial, lapsed
no, 174 days left. ⚠️ The first attempt threw inside the transaction (the morph map has no `Subscription`, so an audit row cannot
name one) and rolled back whole; the second run's premise check — the trial still ended — is what proved the rollback. The audit
row names the organisation and carries the subscription id.

**The fix (code).** `EnsureSubscriptionActive`: a lapsed workspace still closes (#41), but only someone who holds `billing.manage`
— by the permission, so a role granted it on the Roles page counts — is sent to the Paywall; everyone else gets a plain 403 page
("This workspace’s subscription has ended … Nothing has been deleted … ask your administrator") with the suspended page's actions
(Switch to another workspace, Sign out, Contact Vyrobel) and never a link back into the loop; a background request gets a JSON 403;
the switcher is let through. The "other workspaces" list moved out of `RefuseSuspendedOrganization` into
`App\Support\Platform\OtherWorkspaces` so the two pages cannot offer different lists. `LapsedWorkspaceStaffTest` (7; three RED
first — a 302 where the page was due); mutation set `lapsed-staff` 9/9 landed and caught; the three existing mutations on the
suspended gate (P2-M1, P2-M18, P3-B16) re-run, 3/3 caught.

Gates: **4,490/4,490 on MySQL 8.4.10** (33,151 assertions; 17:04–17:26 Baku) · **4,490/4,490 on MariaDB 11.8.9** (33,151 assertions; 17:27–17:49 Baku) — both on the final code before these docs, read from the runs' JUnit logs: 0 failed, 0 errors, 0 skipped · the engine swap asked first (F: "Yes, swap", this pass only — `OPEN.md` F9) and checked: nothing connected to 3306, no advisor-platform process · mutations: `lapsed-staff` 9/9 landed and caught; P2-M1, P2-M18, P3-B16 3/3; the harness lists **1,017** across 37 sets (`--list`, V 17:5x Baku) · PHPStan L5 0 errors (a whole run, 31 s, from a cleared cache, `PAO_DISABLE=1`) · Pint clean (the changed files) · the money gate **FULL** `929 / 10928000 / 0 / 42 / 16 / 0`, digest `a0e1e01d92d608f6 / cc588f6bb9562863 / 2f5598c92aadf105 / 9bac428a1b859297` (V 17:49 Baku, reseeded at 2026-09-01).

**Deploy.** ✅ LIVE on `rel_BB @ 6ca6a10b` (V: `ops/release.sh stage` — 6 files shipped, nothing drifted on the host, no migration, every
shipped file its own inode; `swap` 2026-10-09 17:53:48 Baku, 10 ms; `.release` read back; public checks **12 PASS · 0 FAIL · 0 could not
measure**; 0 error-level lines since the swap — 11 warnings, all `security.refusal` (layer `abort`), from the exposure battery). His look
is next: the six role accounts are his to sign in to.

**SIDEBAR POSITION — 2026-10-09 — his "if I open the owner or I open the reports, upon the page refresh … the sidebar is going up
again … it shouldn't be shaking, it shouldn't be moving … fix that issue across the entire application".** 🔬 Read first: three shells
have a sidebar — the operator back office (`.vy-side`), the owner portal (`.vy-side`, whose MENU `.vy-nav` is the box that scrolls)
and the admin console (`.ad-side`); each is a sticky 100vh scroll box, and every menu link loads a whole new page, so the new
sidebar started at the top with the item just opened off screen; and each replayed a 0.45 s slide-in (`vyFadeUp`/`adFadeUp`) on
every page. The guest portal has no sidebar.

**Built.** One component, `<x-sidebar-position for key>`, inline straight after each sidebar so it runs before the first paint:
it puts the box back where this tab left it (sessionStorage, per realm), brings the current item (`aria-current="page"`) into view
when nothing is remembered or a shorter window would hide it, and remembers on scroll, on a click in the sidebar and on
`pagehide`; every storage call guarded. The sidebar's entrance animation removed in all three shells (the page content still fades
in). 🖥 The walk found two creeps in the first version, both fixed and named in the component: rounding (520.5 saved as 521 — a
pixel a page) and Chrome's scroll anchoring (the Messages badge appears after load, the box grows a pixel and the position shifted
half a pixel, which was remembered — `overflow-anchor: none` on the box); and that the owner portal's scrolling box is its menu, not
its sidebar (the first version restored nothing there).

🖥 Walked locally (Playwright, 1280 × 600; demo accounts): operator — 520 px held over six loads (three clicks, three reloads),
current item in view each time; a direct link to the last item in a fresh tab opens with it in view; a remembered top that would hide
Security in a 400 px window scrolls to it; 390 px: the sidebar is not a scroll box, page 390 wide, no error. Owner — 580 px over six
loads, a direct link to Help & support in view. Admin — 313 px over four loads (the local demo admin's two-factor set up in the
test browser to reach it; local database only). No console error anywhere (Chrome's verbose password-form hints only).
`SidebarPositionTest` (5); mutation set `sidebar-position` 11/11 landed and caught.

Gates: **4,495/4,495 on MySQL 8.4.10** (33,187 assertions; 18:23–18:42 Baku) · **4,495/4,495 on MariaDB 11.8.9** (33,187 assertions; 18:43–19:01 Baku) — both on the final code before these docs, read from the runs' JUnit logs: 0 failed, 0 errors, 0 skipped · the engine swap asked (F: "Yes, swap", this pass only) and checked (nothing on 3306, no advisor process); MySQL restored 19:02 · the harness lists **1,028** across 38 sets · the money gate **FULL** `929 / 10928000 / 0 / 42 / 16 / 0`, digest unchanged (V 19:02 Baku, reseeded at 2026-09-01) · no PHP application code changed (views, a component, tests), so PHPStan and Pint have nothing new to read beyond the two test files (Pint clean).

**Deploy.** ✅ LIVE on `rel_BC @ 2e2194e5` (V: stage — 6 files, nothing drifted on the host, no migration,
every shipped file its own inode; swap 2026-10-09 19:07:25 Baku, 3 ms; public checks **12 PASS · 0 FAIL · 0 could not measure**; the
component present in all three shells on the host; 0 error-level lines since — 11 warnings, all `security.refusal`, from the
exposure battery). His look: done — F 2026-10-09 ~19:18 Baku, "Cool it works fine."

**OWNER TOP BAR — 2026-10-09 — his "Do this too" on D-OWNER-TOPBAR-FADE** (recorded by the SIDEBAR POSITION pass). The owner portal's
top bar (`.vy-topbar` in `layouts/owner.blade.php`) lost its `vyFadeUp .45s .06s` entrance, so it stands still on every page like the
sidebars; read first that the operator's top bar never animated and the admin console's (`.ad-top`) sits outside its animated
content, so the owner portal's was the only one. `SidebarPositionTest` +1 (all three top bars, comments stripped before the check);
`sidebar-position` SP-M12, the set 12/12 landed and caught. 🖥 Locally as the demo owner (1280 × 600): on Statements, Reports and Team
and a reload, the bar's animation `none`, 0 running, opacity 1 from the moment it exists; the menu still kept its place; no page error.
Gates: **4,496/4,496 on MySQL 8.4.10** (33,197 assertions; 19:26–20:21 Baku — 55 minutes, another tool's video render held the load average near 19) · **4,496/4,496 on MariaDB 11.8.9** (33,197 assertions; 20:22–20:49 Baku) — JUnit 0 failed, 0 errors, 0 skipped · the swap asked (F: "Yes, swap") and checked · the harness lists **1,029** across 38 sets · the money gate **FULL** `929 / 10928000 / 0 / 42 / 16 / 0`, digest unchanged (V 20:50 Baku).

**Deploy.** ✅ LIVE on `rel_BD @ 906b7dd9` (V: stage — 1 file, nothing drifted on the host, no migration; swap
2026-10-09 20:53:32 Baku, 4 ms; public checks **12 PASS · 0 FAIL · 0 could not measure**; the rule on the host carries no animation;
0 error-level lines since — 11 warnings, all `security.refusal`, from the exposure battery). ⚠️ While the gates ran, `restiox/admin`
started its own `phpunit` (XAMPP's PHP, its database on port 3310 — not ours on 3306); identified by its working folder, left alone.

**TOP BAR BELL — 2026-10-09 — his "on the top name bar, the notification icon is completely messed because there is no any proper
spacing. Please fix that too. And make it proper. Cool looking."** 🔬 Measured first (owner portal, 1440 × 800, demo owner): the
shared bell (`<x-notifications.bell-glyph>`, used by the operator, owner and guest shells) put the unread badge INSIDE the circle, 9px
from the top and 10px from the right — the decoded prototype's placement — so a 19px-wide "9+" sat at the very position of the 18px
bell glyph and hid it; and the account chip stood 10px away. Built: the badge on the circle's OUTER top-right corner (`-5px` / `-6px`,
the prototype's gradient kept, 19px, 10px bold tabular figures, a 2px `#FFF8F2` ring, a soft shadow, `pointer-events: none` so a tap
reaches the bell); the operator (38px) and guest (36px) shells' own badge offsets removed — the corner fits every size; the operator
and owner top bars' items 14px apart (were 12 and 10). ⚠️ `NotificationBellAppearanceTest` guard 4 PINNED the old inside placement
(the prototype match): it went RED on the change as it should and was rewritten to pin the new placement, at his word, said in the
same turn — plus two guards (no shell overrides the offsets; 14px gaps). Mutation set `topbar-bell` 7/7 landed and caught. 🖥 Walked
locally: owner (42px bell), operator (38px) and guest (36px, link mode) — the bell fully visible, the badge 5px past the circle and 4px
above it, 9px of air to the account chip in both top bars; 390px clean; nothing clips the badge (only the body, sideways); no page
error.
Gates: **4,498/4,498 on MySQL 8.4.10** (33,214 assertions; 21:24–21:55 Baku) · **4,498/4,498 on MariaDB 11.8.9** (33,214 assertions; 21:56–22:19 Baku) — JUnit 0 failed, 0 errors, 0 skipped · the swap asked (F: "Yes, swap") and checked · the harness lists **1,036** across 39 sets · the money gate **FULL** `929 / 10928000 / 0 / 42 / 16 / 0`, digest unchanged (V 22:19 Baku).

**Deploy.** ✅ LIVE on `rel_BE @ 79d0cb60` (V: stage — 4 files, nothing drifted on the host, no migration; swap
2026-10-09 22:21:55 Baku, 5 ms; public checks **12 PASS · 0 FAIL · 0 could not measure**; the glyph on the host carries the corner
offsets; 0 error-level lines since — 11 warnings, all `security.refusal`, from the exposure battery). His look is next.

**THE VYROBEL SELECT, ON /app/profile — 2026-10-09 — his "https://vyrobel.com/app/profile On this page, the drop-downs are looking
very basic or very disaster … make me very cool drop-downs and when I approve … apply it in the entire application. But first work on
this page … ship it live, test it … unique minimal and advanced … also for the select as well … let me know I will check it first".**
🔬 Read first: the page's two drop-downs are native selects, Language and Timezone, opening the operating system's plain list; the
account menu is already a custom popover and the sidebar's property switcher is a native select in the shell (both left for the
rollout, after his approval). ⚠️ And a defect: Timezone offered FIVE hard-coded zones while the `timezone` rule accepts every one, so a
person elsewhere (he is in Baku) saw "Europe/Lisbon" — no option matched, so the first showed — and saving the form for any other
reason wrote Lisbon over their real zone.

**Built.** `<x-select>` (`components/select.blade.php`), one definition for the app-wide rollout: closed, a field like every
`.vy-input`; open, a floating glass panel TELEPORTED to `<body>` and fixed to the field (each card is a backdrop-filter layer — a panel
left inside one is painted under the next card), opening upward when there is no room below and following the field on scroll; an
optional search (automatic above 8 options); quiet group headings; a terracotta tick on the chosen row, the active row tinted; an
option may carry a hint and a `clock` (the live time in that zone, on the row and on the closed field); a "Use this device's
timezone" row when the browser's zone differs. The form value is a hidden input rendered with the saved value on the server. The
APG combobox + listbox pattern: ↓/↑/Enter/Space/typing open; ↓/↑, Page Down/Up, Home/End, Enter, Esc, Tab; type-to-find without a
search; `aria-activedescendant`; reduced motion respected. `App\Support\Time\TimezoneOptions::choices()` (the existing helper the
Portfolio already used, extended — its `all()` and `offsetLabel()` unchanged): every zone the rule accepts, city (and region) first,
the offset as the hint ("UTC+4", "UTC+5:30", "UTC−3", searchable with "-3"), grouped by region, a saved zone outside the list kept.
Language shows each language in its own name with the English name beside it.

⚠️ **Two duplicates, caught by the first full run and corrected before shipping.** The run went RED once
(`KeyboardReachabilityContractTest`: the option rows' click handler), and its allow-list named what a search for "select" had missed:
the app already had a searchable drop-down, `<x-operator.combobox>` (2026-09-29, five places — Maintenance, Units, Portfolio ×2 —
all in dialogs), and a timezone helper, `App\Support\Time\TimezoneOptions`. The helper: the second copy I had written
(`App\Support\Forms\TimezoneOptions`) was folded into the existing one and deleted. The drop-down: his request is a NEW design,
approved on this page before it spreads, so `<x-select>` is the candidate to replace the combobox — both exist until his approval, and
the rollout folds the five combobox places in and deletes it (`OPEN.md` D-SELECT-ROLLOUT, said to him). The contract got a reasoned
entry (the panel's Enter handler is its backing string; VS-M12 proves it).

🖥 Walked locally (demo staff, 1440 × 900): closed — "Lisbon UTC+1" and its live time; open — centred on Lisbon; "baku" → Baku
UTC+4 23:03; "+5:30" → Colombo and Kolkata; nonsense → the empty line; "new york" + Enter chooses it and the hidden field takes the
value; Esc returns focus to the field; Language by Enter, "p" → Português, Enter; a click outside closes; at 1280 × 640 near the
window's bottom it opens upward, fully on screen; at 390 px it fits (39–379) and a tap chooses; no console error. The walk found and
fixed three faults of the first version: it opened at the top of the list (a scroll asked of a not-yet-shown panel — now one frame
later); then 104–195px off centre (screen rects measured during the 98.5% opening scale — now layout offsets); and the app's 14px
page scrollbar in the list (now a slim one). ⚠️ The save itself was not done in the browser: tenant one is never written, and the
writable tenant's founder stops at email verification — it is proven by a test (Asia/Baku and "es" saved).
`VySelectTest` (5); mutation set `vy-select` 12/12 landed and caught.

Gates: the first MySQL run **4,502/4,503** — the one RED was the keyboard contract above (23:09–23:29 Baku); after the fixes **4,503/4,503 on MySQL 8.4.10** (33,260 assertions; 23:33–23:53 Baku) · **4,503/4,503 on MariaDB 11.8.9** (33,260 assertions; 23:53–00:14 Baku) — JUnit 0 failed, 0 errors, 0 skipped · the swap asked (F: "Yes, swap") and checked · PHPStan L5 0 errors (a whole run from a cleared cache, after the fixes) · Pint clean · the harness lists **1,048** across 40 sets · the money gate **FULL** `929 / 10928000 / 0 / 42 / 16 / 0`, digest unchanged (V 2026-10-10 00:15 Baku).

**Deploy.** ✅ LIVE on `rel_BF @ 94c56965` (V: a read-only preflight from the staged release on the host's PHP — now
8.4.26 — 419 zones, Baku "UTC+4", the Portfolio's list unchanged, both views compile; stage — 4 files, nothing drifted, no
migration; swap 2026-10-10 00:18:16 Baku, 3 ms; public checks **12 PASS · 0 FAIL · 0 could not measure**; 0 error-level lines since —
11 warnings, all `security.refusal`, from the exposure battery). His look and his approval are next.

**THE ONE SELECT ENGINE, EVERYWHERE — 2026-10-10 — his "that's very cool. I really like it. Now I need the same sort of select and
same sort of drop down in the entire application, everywhere … ship it everywhere and … test everywhere, MCP Playwright … on live
server".** 🔬 Read first: 95 `<select>` tags in 44 views across six realms — form posts, Alpine `x-model`, `onchange` auto-submitting
filters, `required`, `disabled`; NONE `wire:model`; two `multiple` — the guest portal and the public pages load NO Alpine; five
`<x-operator.combobox>` places; the admin console's own searchable drop-down (ADMIN 05's field system: a checklist mode and flags);
six menu families (account menus, the bell, the row ⋯ menus, the owner statement and report pickers).

**Built — one engine, `components/vy-selects.blade.php`, included once from every shell's head (19 layouts; the error pages and
Laravel's unused welcome page aside), no Alpine needed.** ⚠️ The NATIVE select stays in charge — element, name, options, form,
`x-model`, `onchange`, validation and every test unchanged; the engine changes only what is seen and clicked: a CSS skin on every
closed select (no class added — a Livewire morph would strip it; the chevron forced past a field's own `background` shorthand; the
app's focus ring in place of the browser's blue one), and a mouse press or a key opens the Vyrobel panel in place of the operating
system's list. A choice sets `selectedIndex` and fires `input` and `change`. Focus never leaves the select (the APG select-only
combobox): typing searches (above 8 options, never a list of plain numbers), ↑/↓, Page Up/Down, Home/End, Enter, Esc (stopped, so
a dialog behind it stays open), Tab. Inside a dialog the panel lives IN the dialog (its trap and click-outside see it as inside),
placed from a still probe when the dialog becomes the containing block; as wide as its longest choice up to 420px; opens upward
without room below. A finger keeps the phone's own picker. Opt-out: `data-native` (no view uses it). The rich select component was
rebuilt on it (a real select with `<optgroup>`s, data-hint / data-clock, the hint and live clock drawn over the field — an x-model
change is followed every 0.4s, since x-model fires no event); the five combobox places moved onto it and the combobox and its CSS
were deleted; the Portfolio's two zone pickers now use the same rich list as the profile. The menus share the panel's surface, rows,
tint and opening motion. The console's searchable drop-down keeps its mechanics (checklist, flags — `OPEN.md` D-ADMIN-COMBO) and
takes the same look from the engine's sheet; an open one now lifts its card, which fixed a pre-existing overlap (the next card
painted over the list's last organisation).

🖥 Walked locally (Playwright, 1440 × 900) with the demo accounts: 28 operator pages as the organisation admin (every visible select
skinned, opened by mouse and by keyboard with the same choices as the select, closed by Esc, no page error); Maintenance settings'
five assignee selects work once auto-assignment is on (off, the card blocks them by design); dialogs — Portfolio's Add property zone
(in the dialog, searchable, the device row, Esc closes only the list, "baku" + Enter sets Asia/Baku and the clock), Units' room type
and Guests' nationality (250, "portu" → PT), Rates' bulk edit and Schedule's add shift (x-model follows the choice); 24 owner pages
(Bookings' auto-submitting filter navigates with `status=checked_out`); the guest portal with no Alpine (Saved 2, Manage 1, Review
4); the storefront (`/stay/vyrobel-demo`, adults chosen); signup (type, currency); 20 admin pages (the console's plain selects on the
engine; its searchable ones restyled). The walk found and fixed: the empty search box's caret before its icon; a numbers-only list
with a search box; a narrow filter cutting its choices ("Changes & sign-ins"); the console overlap above.

Tests: `SelectEngineTest` (8), `VySelectTest` rewritten for the real select, `PortfolioOpenAndFieldsTest` (its intent — a searchable
zone list with offsets — pinned on the engine's markup), the keyboard contract's two retired entries removed. ⚠️ Two of my own test
slips caught by mutation: Pest's `toContain()` takes many needles (a message became a needle — PHPUnit's Assert used), and
`data-search\b` matched `data-search-placeholder`. Mutation sets: `vy-select` rewritten (11), `select-engine` new (13), PF-M6
repointed; the admin field system's own count test keeps counting only its block (the engine names that panel
`[class~="ad-combo-panel"]`, said in a comment).

Gates: a first MySQL run **4,509/4,511** — two existing contracts caught the engine: the owner Messages page forbids the word "Read" (a fabricated receipt) and a script comment began "Read from a still probe" (rephrased; every comment in that script ships in every page); and the control-name census, reading each file alone, called the component's `aria-labelledby="{{ $labelledby }}"` dangling (dropped — the real select is named by the page's own label[for]; the label's id rides as data-labelledby to name the open list). After the fixes: **4,511/4,511 on MySQL 8.4.10** (33,898 assertions; 02:24–02:43 Baku) · **4,511/4,511 on MariaDB 11.8.9** (33,898 assertions; 02:44–03:32 Baku, slowed by another tool's load) — JUnit 0 failed, 0 errors, 0 skipped · the swap asked (F: "Yes, swap") and checked · the WHOLE harness re-run **1,060/1,060 landed and caught across 41 sets** (01:09–01:37 Baku; the sets touched again after — vy-select 11, select-engine 13, PF-M6 — re-run whole, all caught) · PHPStan L5 0 errors (a cleared cache) · Pint clean · the money gate **FULL** `929 / 10928000 / 0 / 42 / 16 / 0`, digest unchanged (V 03:33 Baku).

**Deploy.** ✅ LIVE on `rel_BG @ 6bde9910` (V: the read-only preflight from the staged release — the host's PHP 8.4.26, all 24
changed views compile, 419 zones, the combobox gone; stage — 25 files, 1 deleted, nothing drifted, no migration; swap 2026-10-10
03:38:26 Baku, 4 ms; public checks **12 PASS · 0 FAIL · 0 could not measure**; 🖥 live signup (Playwright): both selects open the
panel with their three choices and close with Esc, no page error — the CDN's "Checking your browser" answered first and then let the
page through; 0 error-level lines since — 11 warnings, all `security.refusal`, from the exposure battery). The signed-in live walk
(the back office and the console behind his sign-in) was offered with the sign-in page open in the automated browser; he chose to
skip it — the local walk across the six realms stands for those.

**MEMORY AND GITHUB, 2026-10-10 — his "update the entire memory of everything we have done so far and most important … update the
GitHub as well. Push everything to the GitHub. Make sure our GitHub is completely updated without any bugs".** The memory brought up to
date with the day's six passes: `SYSTEMS.md` §18 (the lapsed-workspace page), §19 (the shells' still chrome — sidebar position, top bars,
the bell) and §20 (the one select engine, the rich field, the menus, the console's own drop-down), and production's PHP corrected to
8.4.26 there and in `CLAUDE.md`'s stale-lines list; `STATE.md` (the GitHub line, the live code re-measured — **1,460 of 1,460 deployable
files match git at `6bde9910`**, every line counted OK — his approval of the select, item 8g); `OPEN.md` F9 (five more swaps, each
asked); `RULES.md` Part 2 (six lessons of the two days); the auto-memory index and a new page for the two days. Then the push under
the four hard stops, and GitHub's checks read after it. **The push:** `9e4f717f..351cd844` (04:10:46 Baku) after the four hard stops, each
run on its own — private `true`; identity `Subhankhan12`; `origin/main` an ancestor of HEAD (a fast-forward of 14 commits); the history
scan over 8,749 blobs, **128 hits in the same 40 locations** as every earlier push (the published demo password in test fixtures —
known and accepted), none in the push's 56 files; `git ls-remote` read `351cd844` back. **GitHub's checks: green end to end** — run
38007913457 on `351cd844` (04:10–04:34 Baku): the parity image, the runtime contract, the assets, Pint, PHPStan L5, the full suite
**4,509 passed + 2 skipped** (the auto-memory tests, which live on this Mac only — by design) = the 4,511 of the local runs, 33,894
assertions; the money gate FULL `929 / 10928000 / 0 / 42 / 16 / 0`; marketing 9 · claims 23 · cookies 15. Then this record, pushed the
same way. **GitHub's run on that record went RED** — run 38010017082 on `8f460c2d` (a docs-only commit; 04:39–05:21 Baku):
`OwnerSettingsTest` › "no fabricated card", `Not to contain: 4242`. A docs commit changes no page, so the page differed by chance:
🔬 rendered 300 times locally, `/owner/settings` held "4242" ONCE — inside a Livewire snapshot's `checksum`, a fresh sha256 on every
render. A flaky check from before this pass, not a defect of the product (Verified). **Fixed in the tests only:**
`withoutRandomTokens()` (`tests/Support/random_token_helpers.php`, required from `tests/Pest.php`) blanks the random VALUES a page
carries — the checksum, the CSRF token, ULIDs, signed-link signatures — and nothing else; the snapshot's data stays readable. The
class swept: every whole-page absence check of a short number reads through it (`4242` ×3 — owner settings, the refund screen, new
reservation; `0154` on the search page and `4821` on payouts, both Livewire; the storefront's three-digit `369` ×2). The PDF, email
and JSON checks carry no checksum and stay as they were. `RandomTokenHelperTest` (2) proves both halves, and three planted leaks
were each caught and reverted: a visible 4242 on owner settings; `0154` held only in a Livewire property (so present only in the
snapshot's data); `369` on the storefront. The 7 affected files: 70 passed (469 assertions); Pint clean. Pushed the same way; the run
on that commit is read with `gh run list`.

**FAVICON, 2026-10-21 — his "update the favicon on the front website and all roles panel … I like the v logo and I want it as
favicon", then "Ship everything live".** Scope held to the favicon only — no DB, no logic, no previous work touched. Found: the
platform admin panels already carried an inline terracotta "V" data-URI favicon (`admin/shell`, `admin/auth`); every other surface
had none, and `public/favicon.ico` was a 0-byte empty file. The brand "V" is the marketing logo mark — a rounded square, the
terracotta gradient `#E8703A → #C2410C`, a white bold V. Built: `public/favicon.svg` (the gradient V, 547 bytes) and ONE
`resources/views/components/favicon.blade.php` (`<x-favicon>`) as the single source of truth — an SVG `rel=icon` (guarded `filemtime`
cache-bust, the marketing shell's own pattern), the admin panels' inline data-URI kept as the fallback that needs no file, and an
`apple-touch-icon`. Wired into 22 views: the front website (`marketing/shell`, `public/shell`), the operator/owner/guest layouts,
both admin panels (their inline favicon folded into the component), and every shared front door (login, forgot/reset/set-password,
2FA, verify-email, onboarding, invite-invalid, login-approval ×2, guest auth layout, errors layout, status, oauth consent,
feature-locked). Left untouched on purpose: `welcome.blade.php` (the Laravel splash, no longer routed) and `components/layouts/app`
(the "Contract Stub" sibling layouts keep untouched); PDFs and emails (no browser tab). Local gate: `view:cache` compiled every
view, the component rendered correct HTML, the SVG parsed as XML. Committed `e72ce69d` (24 files, +49/−3). **Deploy.** ✅ LIVE on
`rel_BH @ e72ce69d` (V: the read-only preflight from the staged release on the host's PHP — `favicon.svg` present 547 bytes, the
component present, all 14 edited shells/front-doors compile, the component renders both the svg link and the data-URI fallback;
stage — 24 files, 0 deleted, nothing drifted, no migration; swap 2026-10-21 ~20:01 Baku, 9 ms; public checks **12 PASS · 0 FAIL · 0
could not measure** — the 5xx sweep clean over 168 public routes, exposure battery SERVED=0; `https://vyrobel.com/favicon.svg`
serves HTTP 200 `image/svg+xml` 547 bytes with the gradient-V content, re-read after the swap). ⚠️ Not pushed to GitHub — his rule
is to push at the very end; this pass stopped at the live swap he asked for. Rollback target: `rel_BG @ 6bde9910`.

**CALENDAR ROOM-TYPE FILTER, 2026-10-21 — his "on this page the design of the 'all room types' dropdown should look cool and better
sizing, fix it" (`/app/calendar`), then "ship it live".** Scope held to that one control. Found: the room-type filter
(`reservation-rack.blade.php`) is a glass pill `.vy-rack-filter` wrapping a borderless native `<select class="vy-propsw-select">`
with `onchange="this.form.submit()"`. The one select engine (`components/vy-selects.blade.php`) skins every bare select and forces
`min-height:40px` + `padding-right:36px` on it — so inside the pill the field ballooned the pill to ~54px, out of line with the
~34px glass nights pill (7n/14n/30n) beside it, the chevron floating far from the edge, no icon. Fix (`.vy-cal .vy-rack-filter` CSS
in `layouts/operator.blade.php`, the class used nowhere else): the pill fixed at 34px to sit level with the nights pill; the
engine's forced `min-height`/padding neutralised INSIDE the pill (`!important`, overriding `:where()` base specificity) while its
chevron and open panel are kept for consistency (D-SELECT-ROLLOUT); a leading room-type grid icon (brand terracotta) added to the
markup; hover (brighter glass, tinted border) and focus-within (white, accent ring) polish. The native select keeps its name,
options, auto-submit and GET — posting and every test unchanged. Local gate: `view:cache` compiled; git scope 2 files. Committed
`f3a7ab82` (2 files, +16/−4). **Deploy.** ✅ LIVE on `rel_BI @ f3a7ab82` (V: the read-only preflight from the staged release on the
host's PHP — both changed views compile, the icon markup and the three CSS changes present; stage — 2 files, 0 deleted, nothing
drifted, no migration; swap 2026-10-21 ~20:20 Baku, 3 ms; public checks **12 PASS · 0 FAIL · 0 could not measure** — the 5xx sweep
clean over 168 public routes, exposure battery SERVED=0; `/app/calendar` serves 302→/login for anon, no 500). ⚠️ Not pushed to
GitHub — his rule is to push at the very end. Rollback target: `rel_BH @ e72ce69d`.

**INSIGHTS DATE PICKER, 2026-10-21 — his "on this page there are two date pickers, I didn't like the design of those, make them
colourful as per theme and also upon opening use the coolest ever date picker" (`/app/insights?range=ytd`), then "ship everything
live".** Found: the custom range on Operator Insights (`livewire/operator/insights.blade.php`) was two plain native
`<input type="date" class="vy-pill">` with inline styles, inside a no-JS GET form that the code explicitly values (D3-1). A native
date input's OPEN calendar can't be themed (the browser owns it), so the "coolest picker on open" needs a custom popover. Built a new
reusable component `components/date-field.blade.php` (`<x-date-field>`): the native input stays the truth (name/value, submitted, no-JS
fallback), and Alpine (ships with Livewire) ENHANCES — hides the native control, shows a colourful terracotta-gradient trigger pill
(calendar icon + caret + pretty date), and on open a custom calendar popover (gradient month title, prev/next nav, Monday-first grid,
a terracotta-gradient selected day with shadow, a today ring, hover/active states, Today + Clear). A choice writes the ISO date back
onto the native input and fires input/change, so the form/URL/server read is unchanged. ⚠️ Pattern note: the operator layout has NO
`@stack('scripts')`, so the component's Alpine fn is an inline `@once <script>` (the same way group-booking / new-reservation register
theirs), not a push. CSS scoped to `.vy-df` / `.vy-ins-range` in `layouts/operator.blade.php`; native hidden via
`:has(.vy-df-trigger:not([x-cloak]))` once Alpine drops x-cloak. Local gate: `view:cache` compiled; the component rendered correct
HTML in tinker (native input with name, trigger, popover, fn). Committed `abcc5384` (3 files, +199/−6). **Deploy.** ✅ LIVE on
`rel_BJ @ abcc5384` (V: the read-only preflight from the staged release on the host's PHP — the 3 changed views compile, the component
renders native input + trigger + calendar + its Alpine fn, insights uses `<x-date-field>` and the old inputs are gone, the popover and
gradient-selected-day CSS present; stage — 3 files, 0 deleted, nothing drifted, no migration; swap 2026-10-21 ~20:45 Baku, 6 ms;
public checks **12 PASS · 0 FAIL · 0 could not measure** — the 5xx sweep clean over 168 public routes, exposure battery SERVED=0;
`/app/insights?range=ytd` serves 302→/login for anon, no 500). ⚠️ Not pushed to GitHub — his rule is to push at the very end.
Rollback target: `rel_BI @ f3a7ab82`.

**INSIGHTS DATE PICKER OVERLAP FIX, 2026-10-21 — his "upon opening date picker there is overlaping issue in both of them … fix those
and ship it live".** Cause (a classic stacking-context trap): the `.vy-fade` entrance animation (`vyFadeUp`) leaves a `transform` on
both the toolbar (`.vy-rack-toolbar`) and the KPI cards container (`.vy-dash-kpis`) below it; a non-`none` transform makes each a
STACKING CONTEXT, so the popover's `z-index:60` was confined inside the toolbar's own context and the later cards (a sibling context,
later in paint order) drew over the open calendar. Fix (CSS only, `layouts/operator.blade.php`, scoped to `.vy-ins`): the toolbar gets
`position:relative`, and `.vy-ins .vy-rack-toolbar:has(.vy-df-trigger.is-open)` raises its whole context to `z-index:60` only while a
picker is open — so the calendar paints above the cards, and nothing changes when it is closed. No overflow clip on the Insights
wrappers to cut it. Local gate: `view:cache` compiled; git scope 1 file. Committed `cb378e72` (1 file, +6). **Deploy.** ✅ LIVE on
`rel_BK @ cb378e72` (V: the read-only preflight from the staged release on the host's PHP — the 3 views compile, both fix rules
present; stage — 1 file, 0 deleted, nothing drifted, no migration; swap 2026-10-21 ~21:10 Baku, 3 ms; public checks **12 PASS · 0
FAIL · 0 could not measure** — the 5xx sweep clean over 168 public routes, exposure battery SERVED=0; `/app/insights?range=ytd`
serves 302→/login for anon, no 500). ⚠️ Not pushed to GitHub — his rule is to push at the very end. Rollback target:
`rel_BJ @ abcc5384`.

**SECURITY PASSWORD SHOW/HIDE, 2026-10-21 — his "on this page there are password fields, it must have show and hide buttons … in all
password fields" (`/app/security`), then "ship it live and update the entire memory".** Found: five `type=password` inputs on Operator
Security (`livewire/operator/operator-security.blade.php`) — the change/set-password card (current + new + confirm), the
sign-out-others dialog, and the revoke-device dialog — none with a reveal. The auth pages (reset/set-password) already had a
`.vy-eye` + `data-toggle` reveal, but it is page-local; this is the first back-office use. Built a reusable component
`components/password-field.blade.php` (`<x-password-field>`): the input defaults to `type=password`, and an Alpine eye button
(`x-data="{ show:false }"`, `:type="show ? 'text' : 'password'"`) toggles it, swapping an open/closed eye icon. ⚠️ The input keeps its
`name`, `required`, `autocomplete` and all passed attributes, so posting/validation are unchanged and it degrades to an ordinary
password field with no JS; the eye is `type=button` + `tabindex=-1`, so it never submits and never fights the dialogs' focus traps
(`x-trap`). All five fields converted; CSS `.vy-pw` / `.vy-pw-eye` (eye inset at the input's right edge) scoped in
`layouts/operator.blade.php`. Local gate: `view:cache` compiled; the component rendered correct HTML in tinker (password input with
name + required, `:type` toggle, eye button). Committed `b30e5570` (3 files, +39/−5). **Deploy.** ✅ LIVE on `rel_BL @ b30e5570`
(V: the read-only preflight from the staged release on the host's PHP — the 3 views compile, the component renders the input keeping
required and toggling `:type`, the page uses exactly 5 `<x-password-field>` with 0 native password inputs left; stage — 3 files, 0
deleted, nothing drifted, no migration; swap 2026-10-21 ~21:35 Baku, 4 ms; public checks **12 PASS · 0 FAIL · 0 could not measure** —
the 5xx sweep clean over 168 public routes, exposure battery SERVED=0; `/app/security` serves 302→/login for anon, no 500). ⚠️ Not
pushed to GitHub — his rule is to push at the very end. Rollback target: `rel_BK @ cb378e72`.

**SEARCH DATE PICKER, 2026-10-21 — his "same date picker color and style issue on this page as well, fix it" (`/app/search`).** The
Operator Search page (`livewire/operator/operator-search.blade.php`) had two native `<input type="date" class="vys-date">` for the
check-in range, inside its GET filter form. Replaced both with `<x-date-field>` (the same component from the Insights pass — colourful
trigger + custom calendar popover), and added the same stacking-context fix: the form is `.vy-fade` (a transform → a stacking context)
with the result rows below it, so the popover would be trapped; `.vys-form` (a class added to the form) gets `position:relative` and
`:has(.vy-df-trigger.is-open){z-index:60}` so the calendar sits above the rows. The native input stays the truth and submits with the
Search button unchanged (no auto-submit here, so no event wiring needed). CSS in the view's own `<style>`. Local gate: `view:cache`
compiled; 0 native date inputs, 2 component tags. Committed `0790af9d` (1 file, +8/−3). **Deploy.** ✅ LIVE on `rel_BM @ 0790af9d`
(V: the read-only preflight from the staged release on the host's PHP — the 2 views compile, the page uses exactly 2 `<x-date-field>`
with 0 native date inputs left, the form stacking fix present; stage — 1 file, 0 deleted, nothing drifted, no migration; swap
2026-10-21 ~22:00 Baku, 4 ms; public checks **12 PASS · 0 FAIL · 0 could not measure** — the 5xx sweep clean over 168 public routes,
exposure battery SERVED=0; `/app/search` serves 302→/login for anon, no 500). ⚠️ Not pushed to GitHub — his rule is to push at the very
end. Rollback target: `rel_BL @ b30e5570`.

**SEARCH 500 HOTFIX, 2026-10-21 — his "there is some error on this page, it's not opening and working" (`/app/search`).** The SEARCH
DATE PICKER pass above had broken the page FOR SIGNED-IN USERS (anon still 302'd before render, so it looked fine). The live log named
it: `ViewException: syntax error, unexpected end of file, expecting "elseif" or "else" or "endif" (View: operator-search.blade.php)`.
Cause: the CSS comment added to explain the stacking fix contained the literal date-field component tag text, and **Blade compiles a
component tag even inside a `<style>`/CSS comment** — it was read as an unclosed component that swallowed the rest of the file, so the
compiled PHP had an unterminated `@if`. Reworded the comment to never name a component tag (and added a warning not to). Committed
`978b8f71` (1 file). **Deploy.** ✅ LIVE on `rel_BN @ 978b8f71` (V: preflight — the view compiles AND its compiled PHP token-parses on
the host; only the 2 real tags remain; stage — 1 file, nothing drifted, no migration; swap 17:39:23 UTC, 16 ms; live log — last
`operator-search` error 17:39:00, 23 s before the swap, none since; `/app/search` 302→/login for anon). Rollback target:
`rel_BM @ 0790af9d`.
- ⚠️⚠️ **THE LESSON (new, and a repeat of a known one):** a component tag inside a CSS/script comment or a `<style>` block STILL
  COMPILES and breaks the page — already written in `SYSTEMS.md` §20 ("no component TAG is written in a comment… the page breaks"), yet
  it happened again in a CSS comment. **And the preflight was too weak:** `view:cache` and `$compiler->compile()` only GENERATE the
  compiled PHP; they do NOT parse/execute it, so a compiled-PHP syntax error sailed through, and the anon smoke test 302'd before any
  render. **From now, a view preflight must token-parse the COMPILED PHP** (`token_get_all($code, TOKEN_PARSE)` or `php -l`), not just
  compile it — that is the check that actually catches this.

**LOGIN PASSWORD SHOW/HIDE, 2026-10-21 — his "password hide and show should be also working on `https://vyrobel.com/login`".** The
login page (`auth/login.blade.php`) had one `type=password` field with no reveal. It is a self-contained view (its own
`<head>`/`<style>`/`<script>`, not the operator shell, no Alpine), so the new back-office `<x-password-field>` (Alpine) was the wrong
fit; used the auth-page pattern instead (as reset/forgot/set-password already do): the input wrapped in `.vy-input-wrap` with a
`.vy-eye` button + `data-toggle="password"`, the eye-open/eye-closed icons swapped, and a small vanilla toggle added to the page's
existing `<script>`. The password input keeps name/autocomplete/required and submits unchanged; enhancement only. Committed `1910a860`
(1 file). **Deploy (hardened preflight, applying the SEARCH 500 lesson).** ✅ LIVE on `rel_BO @ 1910a860` (V: preflight — the login
view COMPILES AND its compiled PHP TOKEN-PARSES on the host, the eye markup/CSS/JS present, the password field keeps its name; stage —
1 file, nothing drifted, no migration; swap 2026-10-21 ~22:35 Baku, 4 ms; **the LIVE public page confirmed — `/login` renders HTTP 200
and the served HTML carries `vy-eye`, `data-toggle="password"` and the toggle script**; public checks 12/0/0). ⚠️ Not pushed to GitHub —
his rule is to push at the very end. Rollback target: `rel_BN @ 978b8f71`.

**INBOX DESIGN POLISH, 2026-10-21 — his "improve the design of `/app/inbox` more and make there are no spacing issues".** The page
(`livewire/operator/operator-inbox.blade.php`) was already well-built, so this was a tightening pass, CSS + one markup wrapper only (no
logic): the stacked header sections given a consistent 16px rhythm (stat strip / toolbar `.vy-ibx-bar` / subbar were 16/10/12); the
3-pane grid gap 16→18px; the stat cards redone as a two-line number-over-label (new `.vy-istat-text` wrapper) with a bigger figure
(20→22px), roomier padding, softer radius and a hover lift on the clickable Unread card; conversation rows slightly roomier with a
terracotta left-accent on the active one. Committed `ab5f6c93` (1 file, +25/−14). **Deploy (hardened preflight).** ✅ LIVE on
`rel_BP @ ab5f6c93` (V: the inbox view COMPILES AND its compiled PHP TOKEN-PARSES on the host, the new wrapper/accent/gap present;
stage — 1 file, nothing drifted, no migration; swap 2026-10-21 ~22:07 UTC, 13 ms; the live log shows NO `operator-inbox` error;
`/app/inbox` 302→/login for anon; public checks 12/0/0). ⚠️ Not pushed to GitHub — his rule is to push at the very end. Rollback
target: `rel_BO @ 1910a860`.

**INBOX THREAD-ACTIONS FIX, 2026-10-21 — his "these 4 buttons design, height and alignment is still messed — Assign / Snooze /
R-… / Change link — fix those and make them look more cool".** The thread-header actions on `/app/inbox` are mixed element types: a
`<button>` (Assign), a `<button>` inside an inline `<form style=display:inline>` (Snooze/Reopen), and an `<a>` (the reservation
reference + Change link). With only `.vy-btn`'s padding and no fixed height they rendered at slightly different heights, and the inline
form wrapper threw off the flex baseline. Fix (CSS + markup, `operator-inbox.blade.php`): a scoped `.vy-thread-actions` rule — every
control `height:34px` on a centered baseline, `.vy-ta-form` set `inline-flex; margin:0`, `flex-wrap` right-aligned (32px on ≤560px); a
small icon on each button; and the reservation reference restyled as a distinct terracotta code chip (`.vy-ta-ref`) instead of a plain
oversized button. Committed `abb626d6` (1 file, +35/−8). **Deploy (hardened preflight).** ✅ LIVE on `rel_BQ @ abb626d6` (V: the inbox
view COMPILES AND its compiled PHP TOKEN-PARSES on the host, the `vy-ta-btn`/`vy-ta-ref`/fixed-height rules present, the old inline-form
style gone; stage — 1 file, nothing drifted, no migration; swap 2026-10-21 ~22:36 UTC, 4 ms; the live log shows NO `operator-inbox`
error; `/app/inbox` 302→/login for anon; public checks 12/0/0). ⚠️ Not pushed to GitHub — his rule is to push at the very end. Rollback
target: `rel_BP @ ab5f6c93`.

**INBOX COMPOSER RESTYLE, 2026-10-21 — his "the bottom type box and buttons are not looking cool, make them cool".** The reply
composer at the foot of a thread (`operator-inbox.blade.php`) was a plain side-by-side flex (`.vy-composer-form { display:flex;
align-items:flex-end }`). Rebuilt as one rounded glass "composer card": column layout (the error/tray/rec/textarea stack, then the
action row), a focus-within terracotta ring (white bg + glow when the type box is focused), a borderless auto-sizing textarea (46–180px,
14px), the action-row buttons at a consistent 38px, and the Reply/Send button a pill with a paper-plane icon (its dynamic Sending…/Reply
text moved into a bound `<span>` so the icon can sit beside it). CSS + markup only; no logic. Committed `186da56a` (1 file, +14/−5).
**Deploy (hardened preflight).** ✅ LIVE on `rel_BR @ 186da56a` (V: the inbox view COMPILES AND its compiled PHP TOKEN-PARSES on the
host, the composer-card focus-within CSS + send icon + label binding present; stage — 1 file, nothing drifted, no migration; swap
2026-10-21 ~22:50 UTC, 5 ms; the live log shows NO `operator-inbox` error; `/app/inbox` 302→/login for anon; public checks 12/0/0).
⚠️ Not pushed to GitHub — his rule is to push at the very end. Rollback target: `rel_BQ @ abb626d6`.

**INBOX COMPOSER REFINEMENTS, 2026-10-21 — his "still need a lot of improvements, make it more cool and user friendly" (asked; he chose
to keep refining the composer).** Further to the composer card: the reply textarea now AUTO-GROWS (Alpine `grow(el)` on `@input`/
`x-init`, capped at 180px then scrolls, reset after a send); the attach/image/mic tools became rounded tiles (radius 11) with a
terracotta hover + press animation; the ⌘/Ctrl+Enter hint shown as keycap chips (`.vy-kbd`); the Send button bolder with a hover lift
and a dimmed disabled state; softer placeholder. CSS + Alpine only, no server logic. Committed `432cdcbc` (1 file). **Deploy (hardened
preflight).** ✅ LIVE on `rel_BS @ 432cdcbc` (V: the inbox view COMPILES AND its compiled PHP TOKEN-PARSES on the host, the auto-grow
binding/`grow()`/keycap/tool-tiles/send-disabled present; stage — 1 file, nothing drifted, no migration; swap 2026-10-21 ~23:00 UTC,
10 ms; the live log shows NO `operator-inbox` error; `/app/inbox` 302→/login for anon; public checks 12/0/0). ⚠️ Not pushed to GitHub —
his rule is to push at the very end. Rollback target: `rel_BR @ 186da56a`.

**SIGNUP PASSWORD SHOW/HIDE, 2026-10-21 — his "password hide and show should be on signup page as well `https://vyrobel.com/signup`".**
The onboarding page (`auth/onboarding.blade.php`) has two password inputs (Password + Confirm) in the non-social path and had no reveal.
It is a self-contained view with vanilla JS (no Alpine), so — as with login — used the auth-page `.vy-eye` + `data-toggle` pattern:
each input wrapped in `.vy-input-wrap` with an eye button (eye-open/eye-closed swap), and a small vanilla toggle added to the page's
existing `<script>` (`querySelectorAll('.vy-eye[data-toggle]')`, safe when the social path shows no password fields). The strength meter
and double-submit provisioning guard untouched; inputs keep name/autocomplete/required. Committed `4923e342` (1 file). **Deploy
(hardened preflight).** ✅ LIVE on `rel_BT @ 4923e342` (V: the onboarding view COMPILES AND its compiled PHP TOKEN-PARSES on the host,
two eye toggles + script + CSS present, both password fields keep their names; stage — 1 file, nothing drifted, no migration; swap
2026-10-21 ~23:10 UTC, 4 ms; **the LIVE public page confirmed — `/signup` HTTP 200 with 12 `vy-eye` refs, 2 `data-toggle` buttons and
the toggle script**; public checks 12/0/0). ⚠️ Not pushed to GitHub — his rule is to push at the very end. Rollback target:
`rel_BS @ 432cdcbc`.

⬆ The 2026-10-21 UI passes (favicon → `rel_BH`, calendar filter → `rel_BI`, insights date picker → `rel_BJ`, its overlap fix →
`rel_BK`, security show/hide → `rel_BL`, search date picker → `rel_BM`, its 500 hotfix → `rel_BN`, login show/hide → `rel_BO`, inbox
design polish → `rel_BP`, inbox thread-actions fix → `rel_BQ`, inbox composer restyle → `rel_BR`, inbox composer refinements → `rel_BS`,
signup show/hide → `rel_BT`) each shipped through `ops/release.sh`, committed locally and NOT pushed — GitHub is behind `main` by those
commits (as of the signup pass, `main` is ahead of `origin/main` by 25). The reusable components added: `<x-favicon>`, `<x-date-field>`
(on Insights AND Search), `<x-password-field>` (the back-office password reveal; the auth pages — `/login` and `/signup` included —
keep their own page-local `.vy-eye` pattern).

**SECURITY REDESIGN (PARTS 1+2), 2026-10-24 — his screenshot of the `Owner Security.html` wireframe: "Ignore the sidebar … if you see the
central page data and color combination this is what is missing inside our security page, i love the features that we have in our
security page and also the design but color and most of important things are missing … plz audit it and give me some planning … once i
approved then start building it".** Audited both pages against it (rendered locally at 1,440 px, every feature traced to its backend), a
three-part plan posted; **F: "Both pages (Recommended)", "All of it (Recommended)"** — two deploys (Parts 1+2, then 3).

🔬 Found: the owner page a single column with its password boxes 184 px wide, 70% white with a white border on a white card; one wrong
owner password printed under every field (D-OWNER-SEC-ERRORS — 🔬 5 times); the back office with no score at all and the owner page scoring
three protections its own way; ⚠️⚠️ login approval switchable ON while production discards every email, so a held sign-in could never be
finished (D-LOGIN-APPROVAL-MAIL); Notification preferences' "Login from a new device" switch backed by nothing (D-NEWDEVICE-INERT, Part 3).

**Built (Parts 1+2).** One kit for both pages (`SYSTEMS.md` §22): the score ring derived from real settings by ONE rule
(`SecurityScore` — only offered protections count, shares rounded so the ring and the rows add up), its tier and next step, four tiles; the
warm cards, icon chips, gradient and dark buttons and switches of the design; three columns (Signing in · Boost + data · Sign-in protection +
the rest); compact rows whose forms open in centred dialogs with focus in the first field; a real "Sign-in blocked" card from the sign-in log
(lockouts on your own address, 90 days) with "Secure my account" and "This was me" (`users.security_alerts_seen_at`, additive migration);
recovery codes left; the owner page's dialogs each marked so an error shows once, in place; login approval refused ON while email is not
sent, and its switch says why. **Refused (fabrication):** a place name ("Porto, Portugal" — no location service), "trusted locations",
"Code by SMS", re-showing recovery codes (shown once by design), sample apps. **Mine, caught:** a test needle that the shared stylesheet
satisfied on every page (`vy-sx-alert`); a test posting `_form` by hand (SX-M21 survived until it posted the form's own fields); a queued run
with zsh's unsplit `$FILES`; boost rows wrapping to five lines and a stray divider (the browser). **The independent review** (read-only):
the IP may be the CDN's (D-SX-IP — measured at the deploy), accounts already holding login approval stay held while email is off (counted at
the deploy), the scores differ once email is on (D-SX-SCORE-APPROVAL, Part 3 closes it), `failed` rows read as "paused" (now lockouts only),
a decide-then-read race (now decided on the row), "Enable" missing from its buttons' names (now "Enable: …"), lost row headings (now
`h3`), an unreachable disabled switch (now `aria-disabled`), a 320 px sideways scroll (now `minmax(min(320px, 100%), 1fr)`). Also re-aimed:
CB-M48 (`calendar-bookable`), left unable to land by the 2026-10-21 Calendar filter pass, which ran no gates.

Tests: `SecurityRedesignTest` (15; 13 RED first — the other two are guards, proven by mutation: SX-M16, SX-M25), `SecurityPassFiveTest` rewritten to the layout he
asked for (its two-group, label and dialog-count pins), `OperatorSecuritySessionsTest` (the badge became the tier and the two-factor
chip — still never "Protected" with two-factor off), `OwnerSecurityTest` 1/3 → 1/4, `LoginApprovalTest` (switch-on now needs email),
`XShowInlineDisplayContractTest` ceiling 14 → 12. Mutations `security-redesign` 25/25, `security-five` 22/22 (8 re-aimed), CB-M48 caught;
all 1,085 anchors land.
Gates (the final code): **4,528/4,528 on MySQL 8.4.10** (22:37–23:11 Baku) and **4,528/4,528 on MariaDB 11.8.9** (23:12–23:33), 34,049
assertions, JUnit 0 failed / 0 errors / 0 skipped; PHPStan L5 0 (106 s, cleared cache); Pint clean; money gate FULL unmoved. Committed
`c763ff59`. **Deploy.** ✅ LIVE on `rel_BU @ c763ff59` (V: stage — 22 files, nothing drifted, the additive migration from the staged release,
12 ms; the read-only preflight — views compile and parse on the host, 0 accounts with login approval on, email sent no, and 🔬 the logged IP
is the visitor's (a marked request vs this Mac's SSH address, yes/no only); swap 2026-10-24 19:36:58 UTC, 5 ms; public checks 12/0/0;
1,476/1,476 deployable files match git; 0 errors since — 11 warnings, all `security.refusal`, the battery). ⚠️ Not pushed (his rule).
Rollback target: `rel_BT @ 4923e342`.

**SECURITY REDESIGN, PART 3, 2026-10-24/25 — the rest of his approved plan ("All of it").** Built on the kit (`SYSTEMS.md` §22): **login
alerts** — `NewDeviceSignIn::isNew()` (the account's own sign-ins in the log's 90 days; a device = its browser and system as `DeviceLabel`
names them; never on an account's first sign-in; never the sign-in happening now — the log keeps whole seconds, so this second's rows are not
history, proven by its own test rather than by listener order) → `NewDeviceSignInNotification` through the preference gate (the bell; email
once email is sent); staff toggle it on the Security page or in Notification preferences — ONE setting (`NotificationPreferences::setEvent`);
an owner's is `account_security`, "always on" in the owner settings since before this existed (D-NEWDEVICE-INERT fixed). **Session
timeout** — 15 / 30 / 60 minutes or off (the store's own 2 hours), `EnforceSessionTimeout` in the web group, standing aside for a support
session; the bell's pulse, the Inbox's stamp and team chat's poll never count as the person. **Password last changed** — the User model's
`saving` hook dates every password write (seven paths); older accounts say "Not recorded yet". **Login approval on the back office** and
**Approve / Deny of a waiting sign-in from a signed-in device** on both pages — `AccountSecurityController` serves both realms (the owner
page's old `setLoginApproval` moved into it; ON still only while email is sent). Additive migration `password_changed_at` +
`session_timeout_minutes`. **Mine, caught:** the timeout tests first relied on a session carried between test requests (none is — a test sends
no session cookie), rewritten to give the session its last-seen time; the new-device time filter compared whole-second log rows with a
microsecond clock (a same-second row would have counted) — now `startOfSecond()`, with a frozen-clock test; the session-timeout row squeezed
its words into one column at 320 px (stacked); two Parts 1+2 tests moved by the new protection (+35 → +33 pts; and "Email isn't being sent"
now said by two rows, so SX-M19's test asserts the approval note's own sentence). Tests `SecurityProtectionsTest` (13; 11 RED first, the
rest guards proven by mutation); mutations `security-protections` 14/14; `security-redesign` re-run 25/25. **Gates:** MySQL 4,540/4,541 —
the one red was mine: the new set's ids `SP-M1`–`SP-M12` were already the sidebar set's; renamed `SXP-M`, re-run 14/14, the file green
alone · MariaDB 4,541/4,541 (34,120 assertions) · PHPStan L5 0 · Pint clean · money gate FULL unmoved. ⚠️ **Pick a new set's id prefix
by grepping every set first** — the suite caught it, but only after a 28-minute run.
**Live on `rel_BV @ f426f449`** (V): stage 21 files, nothing drifted; the additive migration 118 ms; the read-only preflight on the host —
routes, middleware, columns, 13 views compile and parse, email sent no, 0 timeouts, 0 approvals waiting, 559 ms for the new-device check
over all 20 accounts; all 20 take the staff key because production has 0 owner accounts (checked from the raw tables too); swap
2026-10-24 21:04:50 UTC, 12 ms; public checks 12/0/0; 1,485/1,485 files match git; 0 errors since; both pages 302 → /login for anon.
Committed locally, not pushed. **His look, signed in: F 2026-10-25 "its look good".** Nothing removed — 🔬 checked against `rel_BT`:
0 of 69 security-area routes gone, 0 form fields gone, 0 tests dropped (3 renamed for the new layout).
**The push (his word, 2026-10-25: "yes update the github"):** `d3c3ef9a..89cb2647` (02:44:12 Baku), 30 commits — every pass since
2026-10-10, `rel_BH` to `rel_BV`, all already live — after the four hard stops, each on its own: private `true`; identity `Subhankhan12`;
`origin/main` an ancestor of HEAD (a fast-forward, 0 behind); the history scan over 8,909 blobs, **131 hits in 43 locations** — the 40
of every earlier push (their blobs already on GitHub) plus **3 new, each read**: `login.blade.php:220` and `onboarding.blade.php:290`
are the eye button's own `aria-label` ("Show password" / "Hide password", matched exactly), `SecurityProtectionsTest.php:34` the shared
fixture password as a helper's default; none a secret. `git ls-remote` read `89cb2647` back. **GitHub's checks: green end to end** —
run 40139824815 (02:44–03:26 Baku): the parity image, the runtime contract, assets, Pint, PHPStan L5, the full suite **4,539 passed + 2
skipped** (the auto-memory tests, by design) = the local 4,541, 34,116 assertions; money FULL `929 / 10928000 / 0 / 42 / 16 / 0`;
marketing 9 · claims 23 · cookies 15. Then this record, pushed the same way.

**I18N-01, ⛔ G1 (2026-10-27) — the translation brief: inventory and plan.** His brief (every screen of every realm and role in
English + 27 languages, hrmzy's pipeline and quality bar, plus a permanent `lang:check` / `lang:todo`) saved word for word as
`briefs/I18N-01.md`. Read the canon it names (D7/E1, D-MONEY-COMMA, `SetLocale`, `available_locales`, `PdfService`, the Mailables,
the bell) and hrmzy's pipeline (its memory, the agent briefs, the tool headers). Built a draft scanner and the coverage map
(`docs/i18n/INVENTORY.md`): the route table → each page's component or controller → the views it reaches → strings, and every
role → the pages its gates open. The scanner was checked by hand on two files before any count was used (the login page: an
HTTP header name counted as text, fixed; the Security controller: 12 of 12 flashes). Found on the way (V): the bell stores finished
English sentences; no Mailable knows its recipient's language; the guest Account page offers a language the app cannot show and
saves any 10 characters; the booking engine never sets a guest's language; the checkout shows no terms or privacy text at all
(`OPEN.md` D-BOOK-NO-TERMS). Production read, counts only: no saved non-English language anywhere. Plan and proposals in
`docs/i18n/PLAN.md`; stopped for his rulings (`OPEN.md` F10). Nothing in the app changed; no deploy.

**I18N-01, ⛔ G2 (2026-10-27/28) — Phase A + B: every screen's words become keys, English unchanged; LIVE on `rel_BW`.** His
rulings (F 2026-10-27, `docs/i18n/PLAN.md` §0): booking engine English by default + every finished language; each account, guest
and recipient gets their own language in emails, notifications and PDFs; Arabic and Persian right-to-left; "the rest … you can
decide". **Phase B** (commit `cf557b0b`): locale resolution (signed-in user → organisation default → English; guest → its own;
signed out → `?lang=` → `vy_lang` cookie → English, never the browser), only FINISHED languages applied (`available_locales = [en]`),
`Locales` (28 planned codes), RTL mirroring for ar/fa, `HasLocalePreference` on User/Guest, the bell storing keys (reader's
language), PDFs in the document's language with Arabic/Thai shaping and CJK/Hangul/Thai fonts, `lang:check` + `lang:todo`, the
reviewed hardcoded baseline (232 accepted strings with their text), the per-key glued-placeholder list, the `ops/i18n` pipeline.
**Phase A** (`83a42500` admin · `9df8be07` auth/guest/booking · `008876ea` owner · `e7216e54` emails/PDFs/notifications ·
`6ab41dab` operator + shared · `bd345edc` tests): 86 conversion batches by agents, then my follow-ups — template emails follow the
organisation's language once its words are edited (and the editor no longer freezes unedited defaults), legal PDFs pinned to
English, the VAT invoice/credit note in the organisation's language, team-chat/inbox bell titles built from facts, labels by code
where a filter or a view compared a label (help categories, cancel reasons, review tags, customer source), JS plurals built on the
server, browser dates in the page language, `Locales::inSentence()` instead of lowercasing a translated label, `owner_attention.approve`
split, Iban country names from `Countries`. **Found and fixed on the way (V):** Carbon's locale is a static and leaked Arabic dates
across tests (reset per test); Livewire's asset injection leaks across tests (off in the RTL probe); Pint moved two ops scripts'
imports below their first use (render tool crashed); the checker read glued placeholders differently after a Latin vs an Arabic
letter. **Gates on the final tree (V):** MySQL 8.4.10 **4,563/4,563** (34,221) · MariaDB 11.8.9 **4,563/4,563** (34,221), engine
swap checked first (no other DB client, no test/serve/queue process) and MySQL restored · PHPStan L5 0 · Pint clean · `lang:check` 0 ·
`lang:todo` 0/0/0/0 · mutation anchors 0/1,099 dead (144 re-anchored by agents; the harness run of each is NOT done yet) · English
render diff: 929 renders, status counts identical, 9 pages differ only by words now written by the page script (guests list,
roles) — browser-checked ("1 selected", "Send to 1/2 guest(s)", "45 permissions granted") · money gate `✅ MONEY AS SEEDED · FULL`
(`929 / 10928000 / 0 / 42 / 16 / 0`, digest unchanged). ⚠️ **Test edits, said plainly** (the brief asked for "no assertion edits"):
~30 test files changed — every one a source-reading test now reading the English through the lang files, an escaped-apostrophe
compare, a constant→method token, or a decoded payload; no rendered-English expectation was weakened. **Deploy:** stage 1,260 files,
nothing drifted, no migration; read-only host preflight (PHP 8.4.27, intl yes): 307 views compile AND their compiled PHP parses,
600 lang files load, English only, keys resolve; swap 2026-10-27 23:31:06 UTC, 5 ms; public checks **12/0/0** (5xx sweep 168 routes);
public pages 200 with no raw key or raw Blade; log since swap: 12 warnings, all `security.refusal` (the exposure battery), 0 errors.
Not pushed (his rule). Rollback target `rel_BV @ f426f449`. **Phase C started in parallel** (outside the repo): glossary, prompts
adapted from hrmzy, German 29/29 batches translated + reviewed + consistency-edited (0 errors), Arabic 12/29 translated, 7 reviewed.

**D-MONEY-COMMA, 2026-10-28 — typed amounts read the way the page's language writes them; LIVE on `rel_BX`.** He asked how each
language writes amounts (ICU: 19 of the 27 use a decimal comma; Persian its own ٫ and digits) and ruled on the recommendation —
"Go ahead and make sure it real". **Found while building (V):** besides MoneyInput's 100× ("150,50" = €15,050.00), the folio's
payment/charge/refund parser was a float cast: "150,50" recorded €150.00 and "1,234.50" recorded €1.00 — silently. **Built:**
`LocalNumber` (one reader; ambiguous "1,234" on a decimal-comma page refused, never guessed) wired into every money path and every
`numeric` money/percent field; its script copy fuzz-checked against PHP (`ops/i18n/number-parity.php`: 0 differences on 80,100
inputs in en/de/fa/fr; two planted defects caught); the wizard's and rack's "use a dot" refusals replaced by the same reader;
payment/charge/refund fields made text + decimal keypad (a number field refused "150,50" in an English browser) with a readout
("Records €150.50"); the refund readout keeps the real cap at the remaining refundable. **Gates (V):** MySQL 4,569/4,569 · MariaDB
4,569/4,569 (34,281) · PHPStan 0 · Pint · lang tools 0 · anchors 0/1,099 · money gate FULL · MoneyCommaTest 6/55 ·
OperatorRefundScreenTest re-aimed (cap = the readout's data-max-minor, same figure). Two first-run failures were mine (a quote in
an x-data comment, a CSS rule in a partial instead of the layout), fixed. Deploy: 27 files, preflight on the host (309 views
parse; "150,50" → 15050; de "1.234,50" → 1234.50; de "1,234" refused), swap 01:20:36 UTC 4 ms, public checks 12/0/0, 0 errors
since. Not pushed.

**I18N-01, ⛔ G3 (2026-10-28/29) — the pilot pair: German and Arabic LIVE on `rel_BY`.** All 29 batches per language translated
→ reviewed by a different agent → one consistency editor (de 94 fixes, ar 62); DELTA ran twice for texts added since the export
(030 money readout + approve split, 031 the fixes below, 032 plan copy). **Rendering both languages found what keys alone could
not (V, `ops/i18n/english-left.php`, new):** older bell rows, owner ledger and statement lines stored as finished English; review
tags, roles, agreement rules, the calendar's room-type kind, console modules/events and plan copy printed as codes or config
text; 30 dates printed with `format()`; the console had no language at all (no column) and a signed-in admin was reset to English
by the visitor middleware; Arabic PDFs printed "???" for the page number and the download stamp (a font with no Arabic) with
left-aligned headers; the RTL mirror moved centred tooltips a full width off the screen (an Arabic phone page scrolled sideways);
German compounds cut off in the sidebar; "14n"/"pp" left Latin in Arabic. All fixed, English byte-identical except the bell now
says "OTA"/"Walk-in" (was "Ota"/"Walk_in"). Rows are never rewritten: `StoredEnglish` matches a stored sentence to its key (most
specific first) and `FeedParams` stores dates/sources as data. Tooling: lang:check compares plural markup per form (24 false
errors on Arabic's six forms), lang:todo counts joined prefixes and whole groups, `ops/release.sh stage` BLOCKS on lang:check
and WARNS lang:todo, `EveryMailableRendersTest` renders every mail in every applied language, RULES Part 2 "Translations".
**Two test files re-aimed** for German being applied (I18nLocaleTest sets its own English-only list; a known bell category is
the reader's word) — claims kept. **Gates (V):** MySQL 4,582/4,582 · MariaDB 4,582/4,582 (34,348) · PHPStan 0 · Pint · lang:check
0 · money gate FULL unchanged · 70 Arabic pages at phone width, none scrolls sideways · 21 mails × 2 languages. **Production
(read-only):** users.locale en × 17, empty × 3; guests.language empty × 18; organizations.default_locale en × 15 — nobody had de/ar.
Deploy: stage (gate passed in the script) → host preflight → migrate (additive) → swap 01:40:50 UTC 4 ms → public checks 12/0/0 →
live de/ar sign-in and booking pages checked, cache-busted → 0 errors. Screenshots: `~/Desktop/vyrobel-translation/{de,ar}/`.
**For his signed-in look:** /app/dashboard, /app/calendar, a reservation, /app/front-desk, /app/schedule (staff); /owner,
a statement, /owner/balance, the statement PDF (owner); the guest's trips and a reservation on a phone; /admin/account (pick a
language). Open from it: D-AGREEMENT-BEARER-WORD (his), D-GUEST-TILE-NARROW, D-I18N-DATE-ORDER, D-I18N-STORED-RECORDS. Not pushed.

**I18N-01 — Turkish LIVE on `rel_BZ` (2026-10-29 → 11-02), with the date order and the number fields.** Turkish: 32 batches
translated → reviewed → one consistency editor. **D-I18N-DATE-ORDER closed:** `LocalDate::pattern()` turns each English pattern into
the language's order with ICU (English identical; an unplanned code unchanged; a UTF-8 bug — `ctype_alpha` took a lead byte for a
letter — caught by the Japanese test); 395 call sites. **D-NUMBER-FIELDS closed** (8 fields). Stored bell text is now matched through
the notification's parent classes too. **What the gates caught (V):** the first MySQL run had 2 failures, both real — my own date
test used a negated two-argument `toContain` (always true; the guard test found it), and four saved mutations no longer found their
target after the date wrapping (re-anchored, 4/4 caught; the harness needs PAO on to read results). Reverting an unrelated ordinal
change in `OperatorOwners.php` had also undone its date wrapping — the "no bare pattern" test caught it. **Gates (V):** MySQL and
MariaDB 4,587/4,587 (36,212) · PHPStan 0 · Pint · lang:check 0 · money gate FULL. **Production (read-only):** nobody had tr saved.
Deploy: stage → host preflight → swap (4 ms) → public checks 12/0/0 → live tr sign-in page checked. Open from it: D-MONEY-DISPLAY
(his). French (reviewed + consistency, 67 fixes) and Spanish (translated, in review) follow. Not pushed.

**I18N-01 — French LIVE on `rel_CA` (2026-11-02), with what its render found.** French: 32 batches translated → reviewed →
one consistency editor (67 fixes). **The French render found three things keys alone could not (V):** (1) the owner welcome
page (/owner/welcome) was entirely typed English — `lang:todo`'s out-of-scope pattern `welcome\.blade` (meant for the marketing
root `welcome.blade.php`) also matched `livewire/owner/welcome.blade.php`, so the instrument never counted it; anchored, the page
keyed (24 texts, DELTA 033). (2) The star-rating accessible name was built in English inside the component's `@php` block — keyed
(DELTA 034); a sweep of every view `@php` block found no other. (3) Older bell rows "New message from …" stayed English in every
language: `StoredEnglish` skipped plural values; it now tries each form (count from the row, or the form's own `{1}`) —
mutation-shaped test in German. DELTA 033/034 translated into de, ar, tr, fr (and es, pt). **Gates (V):** MySQL and MariaDB
4,588/4,588 (36,216) · PHPStan 0 · Pint · lang:check 0 · money gate FULL. Deploy: stage → preflight → swap 22:25:40 UTC (4 ms) →
public checks 12/0/0 → live fr sign-in page. **Found for later (OPEN):** D-OWNER-VILLA-COLUMN (his), D-I18N-CONSOLE-REGISTER;
Turkish and Portuguese had given the two unit conditions (out_of_order / out_of_service — different-coloured chips) one word —
fixed in the pipeline for the next release. Not pushed.

**I18N-01 — Spanish LIVE on `rel_CB`, Dutch LIVE on `rel_CC` (2026-11-02/03).** Spanish: 33 batches translated → reviewed →
consistency; with it Turkish's two unit conditions were split (« Arızalı » / « Bakımda », 13 files). Dutch: 34 batches translated →
34 independent reviews → one consistency editor (60 fixes; rulings recorded in TODO-nl: Systeemstatus, « de folio », Accounthouder,
Defect / In onderhoud, Reisbureau). A reviewer-found one-night label ("min stay :nightsn" → "1 nuits" / "1 nachten") was fixed in
French, Arabic and Dutch before the MariaDB run, which covers it. **Gates (V), each release:** MySQL and MariaDB 4,588/4,588
(36,216) · lang:check 0 · lang:todo 0 · money gate FULL · render 929 pages 0 problems. Deploy each: read-only saved-language count
(none) → stage → preflight → swap → public checks → live sign-in page. **Found for later (OPEN):** D-NOSHOW-REFUSAL-COPY (his),
D-I18N-PAYOUT-MODE, D-I18N-SHARED-NAME, D-I18N-ONE-UNITS, the "Spotless villa" tag. In the pipeline meanwhile: Brazilian Portuguese
translated, reviewed and consistent; Russian translated, under review. Not pushed.

**I18N-01 — European Portuguese LIVE on `rel_CD` (2026-11-02).** 34 batches translated → reviewed → consistency (Faro is the first
market, so Portuguese hotel software and Booking.com pt-PT were the reference). **Gates (V):** MySQL and MariaDB 4,588/4,588 (36,216)
· lang:check 0 · lang:todo 0 · money gate FULL · render 929 pages 0 problems. Deploy: saved-language count (none) → stage → preflight →
swap 00:43:39 UTC → public checks 12/0/0 → live sign-in page (`lang="pt-PT"`). Not pushed.

**I18N-01 — Italian LIVE on `rel_CE` (2026-11-02).** 34 batches translated → reviewed → consistency. **Gates (V):** MySQL and
MariaDB 4,588/4,588 (36,216) · lang:check 0 · lang:todo 0 · money gate FULL · render 929 pages 0 problems (English left = demo data
and same-words only). Deploy: saved-language count (none) → stage → preflight → swap 01:25:29 UTC → public checks 12/0/0 → live
sign-in page (`lang="it"`). Also: the es/nl/pt entries were dated 2026-11-03; git and the swap clock say 2026-11-02, corrected.
Found for later (OPEN): D-AGREEMENT-DOUBLE-WORD, D-FD-COLLECT-JARGON. Not pushed.

**I18N-01 — Brazilian Portuguese LIVE on `rel_CF` (2026-11-02).** 34 batches translated → reviewed → consistency (kept apart from
European Portuguese: «você», Brazilian hotel and booking-site words). **Gates (V):** MySQL and MariaDB 4,588/4,588 (36,216) · lang:check
0 · lang:todo 0 · money gate FULL · render 929 pages 0 problems. Deploy: saved-language count (none) → stage → preflight → swap
02:08:45 UTC → public checks 12/0/0 → live sign-in page (`lang="pt-BR"`; `pt` still `pt-PT`). Not pushed.

**I18N-01 — Russian LIVE on `rel_CG` (2026-11-02).** 34 batches translated → reviewed → consistency (three Slavic plural forms in every
count string). **Gates (V):** MySQL and MariaDB 4,588/4,588 (36,216) · lang:check 0 · lang:todo 0 · money gate FULL · render 929 pages
0 problems. Deploy: saved-language count (none) → stage → preflight → swap 02:56:33 UTC → public checks 12/0/0 → live sign-in page
(`lang="ru"`). Not pushed.

**I18N-01 — ten more languages built locally, NOT shipped (2026-11-02 → 11-05).** After the founder's word to finish all
languages on localhost and ship together: Romanian and Polish (before), then Bulgarian, Traditional Chinese, Hindi, Persian, Thai,
Serbian, Estonian, Albanian, Greek, Georgian (and Indonesian, Vietnamese, Simplified Chinese, Japanese, Korean, already translated earlier, installed 2026-11-05) — each 34 batches translated → reviewed (groups A–H) → consistency → check → apply →
committed locally. Two usage limits (weekly, then 5-hour) stopped agents; every one was resumed from its saved work. Then a
code-fix pass (dates, 24-hour time, Persian calendar, Georgian case/ordinals, decimals, list separators) and a PDF-font fix found
by the render pass (Thai/CJK/Korean PDFs printed no glyphs; Georgian title inside an English guest invoice). **Gates (V):** MySQL
and MariaDB 4,599/4,599 (36,299) · PHPStan L5 0 · Pint clean · money gate FULL · render 929 pages × 10 languages, 0 problems. SSH to
production was closed on 2026-11-02 and reopened by 2026-11-05. **Shipped 2026-11-05 as ONE release, `rel_CH @ a5e68e16`** (stage fix to
`ops/release.sh` first: xargs split a 10,400-file list), public checks 12/0/0, all 27 languages answer on `/login?lang=`.

**I18N-01 — leftovers fixed, translated, shipped (2026-11-06).** His word: fix the 10 code/key items and the 11 English-copy items.
Code + English `a30d4252` (a plain-words rewrite of each copy item; Georgian "F Y" without the comma; sentence-end key; typed
sealed-statement reason; mode/interval/refund-method words; pause with no date in properties AND inbox), DELTA `a1e9d990` (15 new + 13
changed texts × 27 languages, 10,448 keys each; translator + check.php only). GitHub's first run of the 27-language code (run
42075504542) FAILED — 23 tests red, `Class "Locale" not found`: the CI image had no ext-intl; fixed in `9edcda4e` (image installs it,
contract asserts it). Gates (V): MySQL 4,619/4,619 (36,364) · MariaDB 11.8.9 4,619/4,619 (36,364) · PHPStan 0 · Pint clean · money gate
FULL · render 929 pages × 10 languages 0 problems · 63 pages × 7 languages fetched signed in, no 5xx/raw key/raw Blade. Shipped as `rel_CI @ 1daeed71` (public checks 12/0/0). GitHub CI GREEN on `c92685bf` (run 42129793979) after two fixes (intl in the image; a case-sensitive font-name test assertion).

**OPEN LIST, ONE PASS (2026-11-11, his word: "Do all these … make sure you test everything live … update the github").** He pasted the status report (live `rel_CI @ 1daeed71`, GitHub `4267652c`, 210 migrations, the stale "Live" lines) and then the whole waiting-on-his-go list. Read from the server and the repo first (V): live `rel_CI`, local = origin = `4267652c`, clean tree. Then five read-only investigators (A8/A9/A10 · the small UI and wording items · the property-scope items · Stripe, mail and logo · team chat and tooling) before any edit — each report was a claim, checked against the code as it was used. Built in six local commits, each guard's test RED without its fix (proven by stashing the fix) and the safety/money guards mutation-proven:
- **UI sweeps** (`7c379cc6`): A8 and A10 to a ZERO ceiling (both contract tests now assert none and prove the scanner on a planted tree), A9 Billing. Seen in a browser: the schedule form/chips, the roles dialogs (centred on first and second open, solid card), owner quiet-hours and the style labels.
- **Scope + small items** (`b64ab629`): one `PropertyScope` for the Portfolio Edit, every maintenance and housekeeping write (and the CSV export, and the work-order page), and the rate proposals; stay-word, group-booking symbol, the checkout's type code, refusal status words, CSV "1 units" (de/es/pt were "1 Nächte"), maintenance copy, the Rates unapplied-season banner, the New reservation sentences, the sidebar property box, guest tiles by container width, the notification page at 320 px, table roles, the Guests ⋯ race, Arabic/Persian chevrons.
- **Behaviour** (`8078ba08`): storefront orphan guest, the single pause/resume in one transaction (availability locked first — a booking's order), a derived main plan refuses a price, the Calendar/Front Desk/walk-in on the property's own day, an acknowledged amendment through `CommissionChange`.
- **Stripe, mail, logo** (`b7b264e2`, `9025e8f7`): a hold judged by its authorization (D-HOLD-STUCK narrowed, not removed), a late test-mode checkout expiry `ignored` (D-R3-3), a replaced logo kept and recorded as the property's (DEC-f — reverses part of ADMIN 06), review/win-back reply lines only with a reply-to, the mail guard reading the transport behind a mailer name (fails closed; `sendingNow()` true only when every reachable transport is real), and every "email sent" claim made true (email-only actions refuse; an invite says "saved").
- **Tooling and uploads** (`e6e8f6b5`): `proc-check.sh --here` (by working folder, fails closed; its own lsof control), the owner composer's limits and spoken refusals.
- **Team chat** (`909d468c`, built by one implementation agent, reviewed and mutation-proven here): edit/delete/reactions/search/typing/online/desktop notifications/owner conversations, and the guest-name scrub in `anonymize()`. 59 + 5 mutations caught; two stale anchors of mine re-anchored.
- **Translations:** 80 new + 13 changed texts × 27 languages (`~/vyrobel-local/i18n`, batches 037/038); translator + `check.php` only, no second reviewer — machine translation with AI review, no native check.
- **Production:** the two leftover scripts in `env-backups/` deleted and read back (A6).
**GitHub's first run of the pushed commit (`a48165c3`, run 43000193565) FAILED after it was already live — 4,696 passed, 2 skipped (the known auto-memory tests), 5 red, all in my new `ProcCheckTest`:** the CI image has no `ps` (`sh: ps: not found`) and no `lsof`, so `ops/proc-check.sh` correctly refused (exit 3, its own positive control) and the tests that assumed a developer's Mac failed. Production was untouched (a developer-tooling test); fixed in `fcdbef36` — the five tests now SKIP with their reason where `ps`/`lsof` are missing (proven both ways: 5 skipped with the tools off the PATH, 6 passed with them), and run — mutation-proven — wherever the tools exist. **The lesson, again:** a test of tooling that reads the machine (`ps`, `lsof`, a working folder) is an environment-dependent test, and "green on the Mac, on both engines" never ran it on the CI image.
**Mistakes of mine, caught before they shipped:** the first `--here` test used an lsof stub that matched the wrong argument and so passed for the wrong reason (mutation PC-M1 was NOT CAUGHT until it was fixed); two mutation anchors went stale under my own edits (IB-M4 matched a different, deeper line by substring; IB-M16); PHPStan found two always-true null checks I had added; a `Mailable::$replyTo` property clash; zsh word-splitting an unquoted file list (again). **Not done / left for him:** D-SUBDISPUTE (needs a Stripe read and a schema choice), the Alpine pre-check for uploads, the top-bar date pill and Housekeeping on the property's day, the other hard-coded text arrows in Blade for Arabic/Persian, the Inbox reactions' emoji collation, a signed-in look at team chat with two people.
**Gates (V), on `09e591b6` (the commit that is staged):** MySQL 8.4.10 **4,703/4,703** (37,164 assertions; JUnit 0 failed · 0 errors · 0 skipped; 13:00–13:19 Baku) · MariaDB 11.8.9 **4,703/4,703** (37,164; 13:20–13:38) — the engine swap asked ("Yes, swap") and checked first (nothing on 3306, no advisor process), MariaDB's test DB migrated first, and ⚠️ MariaDB had to be stopped by SIGTERM (`mariadb-admin shutdown` was denied — no SHUTDOWN privilege) before MySQL 8.4.10 was restored alone on 3306 · PHPStan L5 0 · Pint clean · `lang:check` 0 (27 languages, 10,528 keys) · `lang:todo` 0 new · 0 changed · 0 hardcoded (4 "removed" were dead keys of mine — deleted, languages regenerated; not re-measured) · money gate **"✅ MONEY AS SEEDED · FULL"**, digest `a0e1e01d…` unchanged · mutations: team-chat 59/59, inbox-compose 29/29 (two of mine re-anchored), proc-check 4/4, property-scope 7/7, and every touched older set (admin-06, owner-module, guests-second, mail-02, maintenance-board) caught · render 940 pages × Arabic and German, 0 problems. ⚠️ The FIRST full MySQL run, on `6b395cfb`, was 4,700/4,703: the three failures were all mine and the full suite found them — a team-chat message's tools were `display: none` so Tab could never reach them (a real keyboard gap, fixed with a focusable row), two mutation anchors my own edits had staled (MB-M8, OM-M45), and a test that chose between two audit rows by a timestamp the suite's frozen clock makes equal. Secret scan: 436 hits, all known — the published demo password, throwaway fixtures in older tests, and `lang/.applied/*.json` (a translation key name containing "password"/"secret" beside a 16-hex hash of English text); the lines this push adds to the flagged files hold nothing secret-shaped.

**OPEN LIST, SECOND PASS (2026-11-11, his word: "Go ahead and do all these" — the "code I can do on your go" list of a status report).** Read first (V): STATE/OPEN/RULES, the previous pass's record, the code behind every item; an item's size changed once it was read (the team chat and the Inbox composer already had a browser upload pre-check — only the owner composer lacked one; the "console privacy register" was just the sub-processor list). Built in local commits, each guard's test RED without its fix and the safety/money guards mutation-proven (`tests/Mutations/sets/open-list-two.php`, 19 mutations, all caught):
- **Inbox reactions (D-INBOX-REACTION-COLLATION) — fixed by VALUE, not by migration.** The brief said "an additive migration"; `ops/release.sh` refuses any `->change()`, so a collation change cannot ship through the script (I wrote it first, ran it on both local databases, found the refusal in the script, rolled both back and deleted it). Each reaction is stored as a plain ASCII key and "the same emoji" is decided in PHP; older rows are still read and toggled. Works under any collation, no schema change.
- **Arabic/Persian text arrows (D-RTL-TEXT-ARROWS):** 17 sites in 14 views + two controllers; `Locales::forward()` for text a class or script builds; a scanner test over every non-marketing view. 🖥 seen in Arabic.
- **The property's own day (D-DAY-ELSEWHERE):** `PropertyDay` — the top-bar pill (also on a Livewire update, via the session), Housekeeping's Today/Tomorrow/Overdue and "guest arriving today", "Assign all"'s order. ⚠️ **A pre-existing bug found beside it:** "Assign all"'s three sort keys were one-argument closures given to `sortBy([...])`, which calls them as COMPARATORS (two rooms) — each looked at the first room only; the order held by luck on two rooms and not on six (a six-room test was RED on the old code).
- **Typing sweep:** `team-chat:prune-typing`, hourly. **Owner composer upload pre-check:** both owner forms check size/count against `ChatFiles::limits()` in the browser (🖥 3 MB file → "big3mb.bin is over 2 MB.", selection dropped; a small file clears it).
- **Edge checks (D-EDGE-CHECKS):** `ops/public-checks.sh` asks a failed probe again from the server; stand-in curl/ssh tests; a FAIL stands when the server cannot be asked; DEC-t (rule 8's wording) left to him.
- **Release a card hold (D-HOLD-STUCK):** `FolioService::releaseDeposit` (row lock first, processor asked inside it — exactly once; a refusing processor writes nothing), a `payments.refund`-gated route and the folio's "Release hold" button; the deletion refusal now names the button (its old pin changed WITH that fact). Test mode only — the fake client; no live hold exists.
- **Words:** owner pages say "unit" not "villa" (11 texts); "Cleaning fee goes to" / "Add-ons go to" for the two labels that said "bearer"; folio lines the system wrote are said in the reader's language (`FolioItem::label()`; the guest's tax invoice still prints what was recorded); the console's sub-processor register keyed (rows have a stable `key` now — the marketing privacy page looked a row up BY ITS ENGLISH NAME). 60 texts × 27 languages (batches 039 new / 040 changed; translator agent per language + `check.php` only, no second reviewer, no native check).
- **Found by LOOKING, never by a test:** the previous pass's A8 fix named a span's new class `.vy-cal-confirm` — the class of the gradient "Block these dates" button on the same page: the span took the button's fill and padding and the button lost its layout (green on both engines). Found in a browser on the owner Block Calendar, renamed, and a scanner now refuses a class given two jobs in one stylesheet. The other seven owner `x-show` rows were opened twice each in a browser: flex both times. D-MODAL-PAD: one shared rule (`:has(.vy-modal-head)`), 🖥 Guests fields 233 → 285px of 350 at 390px.
- **⚠️ Found while applying the translations — the PREVIOUS pass's 13 CHANGED texts never reached any language.** `ops/i18n/apply.php` merges every `out/<lang>/*.json`, and the leftover `delta-old.json` (the OLD translations the translator is shown) sorts AFTER the batch files, so it overrode them: "ticket → work order" etc. stayed the old translation in all 27 languages, `lang:todo` said CHANGED 0 (the applied hash is of the NEW English), and nothing went red. Live `rel_CJ` carries the stale ones. Fixed: the old-translation files now live in `~/vyrobel-local/i18n/delta-old-store/`, and every language was applied from outputs only; this release carries the 13.
- **A8:** `Organization` + `WebSite` on the home page and `BreadcrumbList` elsewhere, on a real public https address only (never localhost/IP/http/.test); Product/Offer/SoftwareApplication/rating stay REFUSED (placeholder price, no reviews). **D-SX-IP:** the access note corrected in the migration docblock, the model and the data inventory. **D-GL-FLAKE: NOT reproduced** — 30 varied-timing attempts: 0 select-mode, 0 scrolled; 14 menus shut when the click landed while a search was in flight (the list re-rendered under it), 0 otherwise; no code changed.
**Not done / left for him:** the signed-in look at the new team chat with two people (F12), D-SUBDISPUTE, the guest review tag "Spotless villa", the other sectioned dialogs' padding (only Guests and Portfolio were opened), Housekeeping's UTC clock times, DEC-t, the lawyer's rows for the login-activity access note; an edge failure was not provoked on purpose to watch the second look classify a real CDN challenge.
**Mistakes of mine, caught before they shipped:** the migration (above); a first assign-all test that passed for the wrong reason (it ordered by date, so the arrival rule was never the thing under test — a six-room version was the real one); a scanner regex that found one rule per stylesheet, not two (`(?:^|\})` consumes the brace — a control test caught it); `git stash` of a test-only fix twice; `php artisan migrate --env=testing` migrates the DEV database (`.env.testing` does not exist) — rolled back and the test database migrated with `DB_DATABASE=vyrobel_test`.
**Gates (V), on `4a2f0583` (the last code commit; docs commits follow):** MySQL 8.4.10 **4,749/4,749** (37,562 assertions; JUnit 0 failed · 0 errors · 0 skipped; 21:12–21:32 Baku) · MariaDB 11.8.9 **4,749/4,749** (37,562; 21:34–21:54) — the engine swap asked ("Yes, swap") and checked first (only MySQL's own listener on 3306, no advisor process; another project's phpunit ran in its own folder and was left alone), MariaDB's test DB migrated first, MariaDB stopped by SIGTERM (my own process) and MySQL 8.4 restored and answering · PHPStan L5 0 · Pint clean · `lang:check` 0 (27 languages, 10,572 keys) · `lang:todo` 0 new · 0 changed · 0 hardcoded · 0 removed · money gate **"✅ MONEY AS SEEDED · FULL"** (`929 / 10928000 / 0 / 42 / 16 / 0`, digest `a0e1e01d…` unchanged) · mutations: open-list-two **19/19** (one re-anchored: OL2-M16 first targeted a redundant guard and was NOT CAUGHT — the `localhost` check sits behind the dot rule), admin-p4 21/21, admin-06 93/93, housekeeping-five 24/24, property-scope 7/7 · render 940 pages × Arabic and German, 0 problems. ⚠️ The FIRST full MySQL run, on the commit before a test-only fix, was 4,747/4,749: (1) the auto-memory index `MEMORY.md` (outside the repo; read by `MemoryIndexStaysUnderItsLoadLimitTest`) had grown to 141 lines — condensed by one blank line, nothing dropped; (2) the New reservation page's "every class has a rule" test met `vy-flip-rtl`, a hook the right-to-left middleware styles (exempted in the test with its reason). The second full MySQL run is the one above. Secret scan: not run (nothing is pushed).

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
- **ROLE SIGN-IN (2026-10-09):** the mutation harness was run with `PAO_DISABLE=1` and reported "no result" for every test — it reads
  Pest's agent-mode JSON, so that switch is for gates only, never for the harness; a test was named "ten of them" with nothing
  asserting ten (renamed); a production write's audit row named a model the morph map lacks and the transaction rolled back (the
  premise check on the re-run proved it); `mariadbd-safe` is keg-only and not on the shell's PATH — call it by its full path.
- **SIDEBAR POSITION (2026-10-09):** the first version passed its own tests and still crept half a pixel a page — rounding and Chrome's
  scroll anchoring, both visible only by loading six pages in a row in a browser; and it restored nothing in the owner portal, whose
  scrolling box is the menu inside the sidebar. A position fix is proven by repeated loads, never by one.
- **THE VYROBEL SELECT (2026-10-09):** I built a select and a timezone helper while both already existed — the search was for the
  word "select", and the existing ones are a "combobox" and `Time\TimezoneOptions`. The keyboard contract's allow-list named the first.
  Before building a component, search for the CONCEPT under every name it may carry (select, dropdown, combobox, picker, listbox).
- **THE ONE SELECT ENGINE (2026-10-10):** the same lesson, a third copy found the next day — the console's ADMIN 05 searchable drop-down
  (`ad-combo`), surfaced only when the walk's clicks landed on hidden selects. And two of my test slips (Pest's multi-needle
  `toContain` swallowing a message; `data-search\b` matching `data-search-placeholder`) were caught only by mutation — a test written
  after its code is not proven until a mutation turns it red.
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
- **A test that reads the wrong copy of the thing (the New reservation pass, 2026-10-04):** the "set a price" test asserted the
  form's address and hidden fields — which a second, hidden copy of the form still carried when the visible field was removed;
  and a list of hand-made inputs that a first check (the date) dropped never reached the second check (the shape) it was written
  for. Both stayed green under their mutation and were sharpened. And a step message computed live from a field Alpine does not
  watch (an email box) never re-rendered — caught by the browser, not by a test: the message is now state.
- **I offered a money write without its confirmation (the New reservation second pass, 2026-10-04):** "Apply season" posted
  on the first click while the Rates page applies only through a disclosure of what it overwrites. The independent review
  caught it before commit; the fix shares the one dialog rather than copying it. ⚠️ And a PUBLIC static helper on a Livewire
  component again — the Guests pass's slip, a week later; the mechanism (`OperatorLivewireMutationBoundaryTest`) caught it in
  the full suite, as it is there to.
- **A page-wide check proved nothing (the Calendar second pass, 2026-10-04):** "the empty Calendar links to Units" passed with
  the link removed — the sidebar links to Units on every page; the mutation harness (CB-M28) caught it, and the check now looks
  inside the card. And two behaviours I added on the walk (the clicked unit's room type first, the season's way back) had no
  test until the mutation set asked for one.
- **My own quote broke a whole dialog (the Calendar pass, 2026-10-04):** a `"` written in a comment inside `x-data` ended the
  attribute; every binding failed in the browser while every server-side test stayed green. Caught on the walk, before commit,
  and now refused by a test. And a guard I had doubled (the view behind the component) was caught by the mutation harness as
  unprovable, not by reading.
- **A test's title is a claim too (the Units pass, 2026-09-30):** "offers … nothing that writes in a paused workspace" sat on
  a test that correctly pinned Arrival info as still offered there. The assertion was right and the title was wrong; caught
  on re-reading, before commit. And a memory lesson drafted from recall ("the menu swallowed focus") was checked against
  the transcript, found to be a change made on reading the code — never a measured failure — and not written as one.

- **Mine, caught before commit (the Guests second pass, 2026-10-05):** a label built from the guest's stage and one booking's dates
  ("Left <a date still ahead>"); a page rule on a shared kit class that reached Units; an `x-model` select that chose before its
  `x-if` option existed, so Edit would have erased a saved nationality; a dialog sentence ("replies land in your inbox") kept from
    before without checking it; a stacking point set from one measurement and missed by 59 px. The independent review found the first
  four; the browser walk measured the fifth. And a test that kept passing for a new reason (the paused workspace) — the review again.
  The second review, of the fixes: a test that proved a function EXISTS, not that it is CALLED; and advice pointing three roles at a
  page that refuses them.

- **Mine, caught before commit (the team chat pass, 2026-10-06):** a wrong trait namespace in a new invokable controller that failed
  EVERY request (Laravel loads it while registering routes); a poll cursor taken from my own message, and a read position moved by
  sending — the review; a live refresh that read for the person and refreshed itself for ever — the review; two tests that could not
  fail (TC-M8 asserted "never emailed" with a fake blind to it; IA-M5's layer no data reached) — the mutation harness; a "writes
  nothing" preflight that wrote a probe file — re-reading; "up to 10 MB" stated without reading PHP's ceiling — the walk measured 2.

- **Mine, caught before the pass closed (the GitHub and memory pass, 2026-10-06):** "exactly as before" written about production
  without reading production's value — true at a typical limit (30 s), untrue at the host's own (300 s); found by checking the
  record against the host. The guard it described had one tested half of two ("no limit" yes, "a longer limit" no) — reading it
  again. And a check of my own: a version query that named the wrong database because Laravel had already built the connection —
  it failed loudly, and was redone with the connection purged. Same lesson as the team chat pass's "up to 10 MB": a sentence about
  a host is a claim until the host's own value is read. And two process slips: the follow-up's engine swap (22:28–22:30) checked
  only for test runs, not who was connected or whether advisor-platform ran, as today's earlier swaps did (`OPEN.md` F9 — nothing
  was disturbed); and the mutation harness, started in the same command line as a test run, refused on its own parent shell — split
  the commands (RULES Part 3).
- **Mine, caught before commit (the MEMORY AND GITHUB pass, 2026-10-10):** the flaky-check helper's first draft blanked the whole
  `wire:snapshot`. The snapshot carries the component's data to the browser, so a number leaking through a Livewire property would
  have passed. Narrowed to the checksum alone, and proven with a planted `0154` held only in a property.
- **Mine, caught before commit (the Security redesign, 2026-10-24):** a check that read its own needle in the page's own stylesheet; a test
  that typed the form's marker instead of reading it; zsh's unsplit variable, again; and ~30 minutes idle behind another project's test run
  that could never touch ours — he asked why work had stopped. Read the other run's database connection first (`RULES.md` Part 2).

## 3 · Still open (as the docs report it; re-check before acting — `OPEN.md` is the authority)

Mail password + TONIGHT 01 Part 2 (Verified 2026-09-29: `MAIL_PASSWORD` not set) · proving the `sales@` alias (created
2026-09-26, Reported) · ADMIN 06: live since 2026-09-29 · L20 · D-TRANSFER · ADMIN 06 rulings (a)–(g) · ADMIN 05 signed-in
live pass · KEYBOARD 01 (needs him) · OAuth-token-on-reset ruling · Stripe live keys, the lawyer, real tax rates.
**Raised 2026-09-29 → 10-03, waiting on him:** A8 / A9 / A10 — the x-show, see-through-dialog and string `:style` sweeps ·
DEC-h (F-58 and the Portfolio card) · DEC-i (Portfolio Edit scope) · DEC-j (maintenance write scope) · DEC-o (portal access
from the owner's page) · DEC-p (giving an existing owner units) · DEC-q (rate proposals and the property limit) · DEC-r (an
existing guest's email on a new guest) · D-MONEY-COMMA (a comma read as thousands in every money field) · D-RACK-START-TZ (the
calendar's default week on the app's day) · A13 (flag unapplied seasons on the Rates page) · the signed-in looks still owed on
earlier passes. (A11, A12
and DEC-n were done 2026-10-02; D-PAYRUN-BUTTON 2026-10-03.) **Raised 2026-10-09/10:** D-INTERNAL-TRIALS (the other workspaces'
lapsed trials) · D-ADMIN-COMBO (fold the console's own searchable drop-down into the one select engine) · the signed-in live look at
the select engine (offered, skipped at his choice). (D-LAPSED-LOOP, D-OWNER-TOPBAR-FADE and D-SELECT-ROLLOUT were done.) Where:
`OPEN.md`, `docs/GO-LIVE-REQUIREMENTS.md`, `docs/STRIPE-PRODUCTION.md`, `docs/KNOWN-LIMITS.md`, `docs/AUDIT-SUMMARY.md` §4.

## Sources used

- git: `log --reverse`, `log -1 --format=%b` per hash above, `show 8cac3d18` / `7fa23cde`, `log -S "96 of 142"`,
  `merge-base --is-ancestor`, `status`, `show HEAD:docs/DECISIONS.md` (committed vs working-tree sections).
- `docs/DECISIONS.md` 2026-09-22 → 09-27 (ADMIN 06 in the working tree only) · `docs/AUDIT-SUMMARY.md` ·
  `docs/KNOWN-LIMITS.md` L12k, L13–L20 · `docs/GO-LIVE-REQUIREMENTS.md` B5/B6 · `docs/EMAIL-PRODUCTION.md` ·
  `docs/STATE.md` · `docs/THE-LOCK-2026-09-23.md` · `docs/THE-WALK-2026-09-23.md` · `ops/reference/public_html.htaccess`.
- `docs/memory/briefs/`: MAIL-02, TONIGHT-01, ADMIN-06, KEYBOARD-01, PW-SIGNUP-DELETE-1 and -2-follow-up.
- Not available: the founder's planning chat. The DNS 01 brief is in this session's transcript (done, not saved).
