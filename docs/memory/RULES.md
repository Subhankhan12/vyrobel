# RULES — the rules that don't bend, the lessons behind them, and the instruments

**MEMORY 01, 2026-09-27.** Part 1 is the hard rules. Part 2 is the working rules and the lessons, each with where it is
written. Part 3 is how to run each instrument and what a pass looks like. The canon is the linked docs; this file points at
them. Ten of these rules are repeated at the top of `CLAUDE.md`.

---

## PART 1 — THE HARD RULES

**As the founder's planning side wrote them (Appendix A §2 of the MEMORY 01 brief, word for word):**

**Secrets and access**
- Never type the founder's password or 2FA code.
- Secrets (the mailbox password, Stripe, Google and X secrets, `APP_KEY`, the DB password, the bypass secret, the Healthchecks URL) are typed by the founder into `.env` on the server.
  - They are never pasted into chat, and never seen, echoed or stored by the agent.
  - If one is pasted, refuse it and tell him to rotate it.
- Never print, `cat`, `head` or show a prefix of a secret file. Report modes, owners and digests only.
- Never put the maintenance bypass secret into a tool-call parameter.
- Identifiers are read, never written.

**Money**
- Never initiate a real Stripe transfer. Test mode is free to use. Live transfers are always the founder's. No live charge, ever.
- Never re-baseline money silently. The money gate FULL must stay unmoved.

**Data and organisations**
- Never touch tenant one (local and tests), not even inside a transaction meant to roll back.
- Act only on probe organisations. Never on `Vyrobel Internal`, `Vyrobel Test Workspace` or a real customer.
- Never delete an organisation on production. Only the founder deletes, with his password. Delete never calls Stripe.
- Sign-in links are credentials: count them, never print one.

**Checks and permissions**
- Never grant a permission to make a check pass.
- The demo seeder refuses to run outside local/testing. Never work around it.
- The seeded admin password is published. Never allow-list it.
- Never complete an admin invite link. Never force-sign-out the founder's session.
- No test backdoors, 2FA bypass, or "if testing" branches in production code.

**DNS and email**
- Never touch MX records. Never add a second SPF record. Never change DMARC. Never edit or delete an existing DNS record without the founder's yes.
- Never email a probe or undeliverable address.
- Never switch mail on against an unverified domain, or with unreviewed mail in the queue.

**Code and deploys**
- One suite at a time, and no edits while a suite runs.
- Additive migrations only.
- Never `--force` push. Push only under the four hard stops.
- **Founder said (2026-10-04): no GitHub push per task.** Each task ships live from LOCAL commits (`ops/release.sh` needs no
  GitHub) and is pushed only at the very end, when he says — then under the four hard stops.
- If any public check fails after a deploy, roll back first and diagnose second.
- Don't change the social sign-in rules, the verify gate, `RefuseEmailTaken` or `throttle:5,1` unless a brief says so.

**Hard rules the planning side's list does not carry (added by MEMORY 01, each with where it is written):**
- **Never `db:seed` production.** The seeders create a demo super-admin with the published password `password`.
  (`CLAUDE.md`, Commands)
- **A red pin on a ruled behaviour is the system working. Stop and ask; never adjust the test.** (`docs/MEMORY.md`;
  R-SEASON is the sharpest case)
- **Local demo tenants:**
  - tenant one `vyrobel-demo` is the untouchable baseline;
  - tenant two `northgate-demo-city-rooms` must stay paywalled, so never extend its trial;
  - tenant three `harbourpoint-demo-walkthrough` is the only writable one, and no pin may reference it.

  (the memory index; `docs/KNOWN-LIMITS.md` L8, L9)
- **Only one session deploys at a time.** Don't start while another pass (for example the email pass) is running.
  (`briefs/ADMIN-06.md`)
- **A suite count is whatever the run prints.** Never quote one copied from a doc as current. Quote the money gate's
  own sentence, never "the money is unchanged". (`CLAUDE.md`, Commands)
- **Every fact says how it is known: Verified, Reported or Founder said.** A claim is not a measurement, and an old
  report is not a check. (MEMORY 01 brief; the "no queue worker" and "96 of 142" mistakes)
- **Never stop, kill or measure against another project's processes.** Identify a process by its path or working
  folder, never by the look of its command line. (MEMORY 01, 2026-09-27: a `phpunit` in `restiox/admin` and Chromium
  from the global `ms-playwright` cache were running beside this repo's work)

---

## PART 2 — THE WORKING RULES AND LESSONS

**As the planning side wrote them (Appendix A §3 of the MEMORY 01 brief, word for word).** Where each is recorded is in 2b:

**Proving things**
- A write is proven by reading the value back.
- Any failing probe gets a control run before it's believed.
- A test that supplies a value by a route the real caller doesn't use proves nothing.
- A metric that improves for free is measuring the wrong thing.
- Check the checker. Detectors have bugs too (the "five dead columns" were a detector bug).
- A founder action is a precondition to verify, never a belief.

**Reasoning**
- Reason from the consumer, not the config. "No queue worker" was wrong; a worker cron existed.
- Don't reason about one layer and assume the layers next to it comply. SOCIAL 02 missed a non-nullable DTO this way.

**Production specifics**
- Production caches config: raw `env()` returns NULL at runtime (L14). Every `.env` change needs the cache rebuilt, and is proven by behaviour, not by reading the file.
- `/up` is exempt from every layer. Never use it as a probe.
- A 302 from `/auth/google/redirect` proves the client ID, not the secret. Only a completed sign-in proves the secret.
- A fix a deploy undoes is not a fix.
- The mutation runner refuses to count a mutation that didn't land. A stale mutation fails a test.
- `preventSilentlyDiscardingAttributes` is on in tests only. It found about 30 silent drops.

### 2a · Every L-entry in docs/KNOWN-LIMITS.md (one line each: what broke → what it taught → status)

All lines: (Reported: docs/KNOWN-LIMITS.md, date shown). Status words are the doc's own.

- **L1** One Stripe Connect account per org; Stripe fixes its country and we never set it → state an irreversible choice *before* it is made → told in product (billing card); pre-sale copy is a founder decision, open.
- **L2** No OTA channel connects: `FakeChannexClient` is bound, and live sync "never started" → say "never started", not "stopped" → told on `/integrations`; account-gated.
- **L3** A guest can erase their data but not export it. The operator answers Art.15 by hand. The legal record contradicts itself (§299 vs §170) → open; the author and a lawyer fix legal text, never a build pass.
- **L4** Retention windows are fixed in code (`RetentionPolicy::WINDOWS`), one for every jurisdiction → the obligation moves to the DPA → by design; recorded in DATA-RESIDENCY-INVENTORY.
- **L5** An org cannot require 2FA of its staff, and cannot even see who enrolled → name the "can't check" half too → open; founder decides whether `/security` says it.
- **L6** 17 of 51 permissions gate nothing. Both counts were once wrong: the matcher read comments, and the denominator skipped `'*'` → a census must tokenise and count wildcards → pinned. Options (remove / wire / label) are the founder's (see docs/PHANTOM-PERMISSIONS.md).
- **L7** The back office is desktop-only. At 375px the nav is unreachable. The obvious fix (FIX 11) was worse → ruled a POSITION (F-33, 2026-09-19); the counter-argument is housekeeping staff on phones. No surface states it yet: founder decision.
- **L8** The money gate at FULL expires tenant two's trial, so its `/app` is paywalled → both gates are right. NEVER extend the trial, move the clock or re-seed later → internal limit, standing.
- **L9** Tenant three (`harbourpoint-demo-walkthrough`) exists to be walked and destroyed → NO pinned figure may reference it → standing rule.
- **L10** A guest on a tentative group hold gets no message until confirm, because the old "confirmed" email was false (F-64) → don't invent a new message inside a defect fix → open; product decision.
- **L11** A coverage % for money-write routes gives 3/47/163/190 at call depths 0–3 → there is no honest number, so name what was driven and don't count it → standing (AUDIT-SUMMARY §5).
- **L12** Production (Hostinger shared) is not the verified stack: MariaDB 11.8.9, a database queue run by cron, file cache (2026-09-20) → every difference is measured below. (Sessions are now `database`, see L16.)
- **L12a** MariaDB cannot index the one-live-payout generated column (1901). The migration's `hasColumn` guard then skipped silently → "Ran" is not "installed" → ✅ restored by INSERT+UPDATE triggers (2026-09-20). `SIGNAL` text is capped at 128 chars.
- **L12b** At REPEATABLE READ, MariaDB `FOR UPDATE` raises 1020. Prod was on READ COMMITTED only by the host's default → the app states its own isolation → ✅ fixed via PDO init command, pinned `DatabaseIsolationLevelTest` (2026-09-20).
- **L12c** A failed job was invisible → the heartbeat now throws when `failed_jobs` is non-empty; the alert goes through Healthchecks' own mail, never ours → ✅ built, ⛔ inert until `OPS_HEARTBEAT_PING_URL` is set (L14).
- **L12c-old** The original finding (no Horizon, no route, no external switch), kept for the record.
- **L12d** A reboot cannot be tested on shared hosting → stays unknown, permanently.
- **L12e** Laravel's `mariadb` driver makes `notifications.id` a native UUID, which breaks ULIDs → ✅ closed: stay on `mysql`.
- **L12f** Queue worst case is ≈60 s, and unbounded while parked. Correction: the Stripe webhook never queues. Do NOT add `--force` to the worker → every figure carries its scope.
- **L12g** A Stripe event during a park gets a 503 before the controller: no row, no log → only Stripe's Dashboard knows → open, narrowed by L12k to migration parks.
- **L12h** `ops/deploy.sh` cannot run on the host (no `.git`, default php 8.3, no node, no `crontab`). Its ERR trap is inert → a script that documents a recovery it lacks is worse than none → it now REFUSES to run. Don't "fix" it by adding `-E`. Superseded by L12k.
- **L12i** The heartbeats stop when maintenance is on → ruled NOISY, never LYING (`HeartbeatIsMaintenanceGatedTest`) → after L12k only a schema park can ring it.
- **L12j** THE risk statement: Stripe retries are bounded (📖 3 days live, a few hours in sandbox), a park is not → open, narrowed to migration deploys.
- **L12k** The release layout: `releases/rel_X` plus one `current` symlink. Swap 3 ms, 0/220 non-200 at the origin. Prune only ≥180 s after a swap. Compiled caches bake ABSOLUTE PATHS, so rebuild config+route caches in EVERY release. Never `scp` onto a hardlinked file. An `artisan` shim keeps the panel cron alive → ✅ adopted 2026-09-20, live.
- **L12m** A CDN (`server: hcdn`) nobody owned cached a transient 301 for 2+ hours. `/up` is exempt from it → keeping HTML uncacheable is load-bearing; the webhook passes straight through → open: a purge, or disabling it, is the founder's call.
- **L12n** Does the alarm bound a park? Yes: detection ≤ 60 s + grace (≤ ~6 min at 5 min). Expand-then-contract is not a prerequisite → but neither the ping nor a `/up/liveness` poller was configured (2026-09-20).
- **L12p** The document root IS the app root. `.env` is protected by a rewrite plus a host rule; `FilesMatch` is inert → `/app` collision ✅ fixed. `/storage` media 404 did fail silently ("the preview is not available", 2026-09-30) → worked around: the public disk answers at `/media` (`PublicMediaController`); the server's `/storage` block stays. Founder step still: move the docroot.
- **L13** `/api/owner/*` returned 500 on a mode-775 key → a route nothing can reach is a route nothing has tested. Check the web user before a chmod → ✅ closed 2026-09-23, proven by 500→401.
- **L14** A raw `env()` returns NULL under cached config, so the heartbeat was never armed. A shell-env test once "proved" it worked → ✅ fixed 2026-09-24 (`config/ops.php`), proven A/B on a cached config. The ping URL is still unset (founder).
- **L15** "777 `.env`" was the symlink's own mode → read `stat -L` → ✅ closed 2026-09-25, not a defect. The real finding beside it: 9 old config caches at 644, now 600. `stage` re-asserts link+600.
- **L16** On the `file` session driver, "sign out others" and a password reset did nothing, and the audit row said they did → ✅ closed 2026-09-25, rel_W @ 3cd72c26 (git): `database` driver + AuthenticateSession, proven live both ways.
- **L17** The admin audit ledger is append-only in the app, not in the DB → open; a DB trigger is proposed, founder's call.
- **L18** Published `sales@` has no mailbox. Mail is rejected, privacy requests included → ruled to stay on privacy/terms (2026-09-26). **Reported 2026-09-26: the alias `sales@` → `contact@` WAS created in hPanel by the session.** Delivery is not proven (`OPEN.md` F8).
- **L19** A deletion could not see an open Stripe Checkout page → ✅ CLOSED by ADMIN 06 (2026-09-27; finished 2026-09-29) via `stripe_checkout_sessions`, with a 24 h fallback. A LIVE page Stripe has not reported on still refuses (D-R2-2), and the guest page's half is tested (D-R2-4).
- **L20** The guest card Checkout cannot open with real Stripe: `payment_intent` is null at creation, while the fake returns one → ⛔ OPEN, dormant, GO-LIVE B20. First confirm it with ONE test-mode session (📖, entry of 2026-09-27).

### 2b · The founder's planning-side lessons (short)

- **A write is proven by reading the value back**, never by the success message (Reported: briefs KEYBOARD-01, MAIL-02, TONIGHT-01).
- **Any failing probe gets a control run before it's believed** (Reported: docs/memory/briefs/KEYBOARD-01.md).
- **A test that supplies a value by a route the real caller doesn't use proves nothing.** The shell-env "proof" in L14 (Reported: KNOWN-LIMITS L14, 2026-09-23).
- **A metric that improves for free is measuring the wrong thing.** The battery went 46→53/53 when a lock was added (Reported: docs/MEMORY.md §1).
- **Check the checker.** "Five writes to dead columns" were the detector's false positives (keys inside JSON); `ops/mass-assign-check.php` was fixed and a planted drop proved it (Reported: DECISIONS.md FINISH 01 Part C).
- **A founder action is a precondition to verify, never a belief.** Example: "ping URL set" is proven only by a ping arriving (Reported: founder's planning notes; see L14, L18).
- **Reason from the consumer, not the config.** "No queue worker" was false: a php84 worker cron existed (Reported: CLAUDE.md, corrected 2026-09-23).
- **Don't reason about one layer and assume its neighbours comply.** SOCIAL 02 missed a non-nullable DTO (Reported: founder's planning notes. unknown — no "SOCIAL 02" or DTO record found by grep of docs/ and git log).
- **Production caches config, so a raw `env()` is NULL at runtime (L14).** Every `.env` change needs the cache rebuilt (`ops/release.sh recache`), and is proven by BEHAVIOUR, never by reading the file (Reported: L14; KEYBOARD-01).
- **`/up` is exempt from every layer** (maintenance, the lock, the CDN). Never use it as a probe (Reported: L12m; CLAUDE.md).
- **A 302 from `/auth/google/redirect` proves the client ID, not the secret.** Only a completed sign-in proves the secret (Reported: KEYBOARD-01 Job 1).
- **A fix a deploy undoes is not a fix.** Put the fix where the deploy re-applies it (L13 storage is shared; L15 `stage` re-asserts 600; ADMIN 06 drift check).
- **The mutation runner refuses to count a mutation that didn't land** (git: tests/Mutations/run.php header, commit 975116b2).
- **`preventSilentlyDiscardingAttributes` is on in tests only** (git: tests/TestCase.php:90; no hit in app/). It found 454 discards at 30 sites (Reported: DECISIONS.md FINISH 01 Part C).

### 2c · Other load-bearing rules (from docs/MEMORY.md and the auto-memory `audit-method-and-traps.md`)

- **The brief is wrong about half the time, and so is the fix. Sweep your own diff** (audit-method Tier 1).
- **Check the register (AUDIT-SUMMARY §4 rulings) BEFORE the work, and check it for the identifier too.** A red pin on a ruled behaviour means STOP and ask. Never adjust the test (audit-method Tier 1; auto-memory index, R-SEASON).
- **An instrument reads its own needle.** Tokenise, bracket-split, skip comments (MEMORY §2; `ops/references.php`, `ops/ps-check.sh`).
- **A layer in front answers for the thing you measure** (503, 401, CDN). Score by WHO answered, not by the status (MEMORY §2).
- **A status code is not a result.** Assert on content that only the new code emits, and take the BEFORE reading before the swap (L12k, 2026-09-24).
- **A 0-second step must prove it succeeded.** Capture every exit code (L12g).
- **A sparse sample cannot see a short process.** A parked worker lives ~2 s, so sample every 1 s. A correctly identified process may be someone else's: `hrmzy.com`'s php83 `queue:work` (CLAUDE.md; MEMORY §2).
- **A test that reaches a capability through a door the user lacks proves the capability, not the access** (R3 `ReverseCheckOut`, audit-method).
- **An alarm that shares a failure mode with what it watches is not an alarm.** Noisy beats lying (L12c, L12i).
- **Pin what must never move; don't pin what legitimately moves.** Re-pin only with a named cause (MEMORY §3).
- **Unreachable is not clear: report a third verdict. Report, never repair. Prove both directions** (MEMORY §1).
- **Never widen access to green a gate. Fix fixtures, never the check** (MEMORY §1; CLAUDE.md #30).
- **`RefreshDatabase` has two opposite failure modes.** OFF lets fixtures accumulate; ON hides rows from worker processes (audit-method).
- Shell traps: in `cmd | sed; echo $?`, the `$?` is sed's status. zsh does not word-split an unquoted `$VAR`. `artisan serve` serves stale classes (MEMORY §2; audit-method Tier 2).

### 2d · Lessons from 2026-09-26/27 (ADMIN 05, ADMIN 06, MEMORY 01)

- **Time any new schema-reading path ON THE PRODUCTION HOST before a swap.** One `Schema::getForeignKeys()` takes 1.1 s on
  production MariaDB, so the delete preview took 118 s there while it was fast locally on both engines. Found at preflight,
  never shipped (Reported: DECISIONS ADMIN 05).
- **A count someone reads before an irreversible click must count what actually goes.** The preview showed 17 of 232 rows.
  It is now proven against the whole database, table for table (Reported: DECISIONS ADMIN 06 #3b).
- **A fake can hide the real API's shape.** The fake Stripe client returns a PaymentIntent at Checkout creation; real Stripe
  does not (L20). Stripe's Refund object has no `livemode` at all (Reported: ADMIN 06).
- **Stripe does not deliver events in order, and a payment can carry more than one dispute.** Order by the event's own time and
  key on the object's own id (📖 Stripe docs, read 2026-09-27).
- **Pest's `toContain` is variadic.** A "message" becomes a second needle, and a negated one then always passes.
  `NegatedVariadicMatcherGuardTest` catches it. A bare `withoutGlobalScopes()` is banned; `SoftDeleteScopeGuardTest` catches it.
  Both fired on this session's own code (Reported: 2026-09-27).
- **Run long local runs under `caffeinate -i`, and check what else is running first.** The Mac sleeps, and other projects share
  it (`restiox`, `advisor-platform`). A process is identified by its working folder, never by its command line (Verified 2026-09-27).
- **An adversarial review of your own fixes finds things.** Two rounds on ADMIN 06 found real defects each time, including in the
  first round's fixes (Reported: 2026-09-27). ⚠️ **A THIRD round, on the second round's fixes, found a real defect both had missed**
  (Stripe's reports for an ARCHIVED organisation were never applied, so a delete's refusals could never lift) — and that one
  of the fixes was broader than its defect and hid a real problem (Verified 2026-09-29).
- **The negated-`toContain` trap fired again, in a new test of a pass that had written this rule down two days earlier**
  (`->not->toContain($token, $table)` always passes). The guard test caught it. A rule written down is a note; the guard is
  the rule (Verified 2026-09-29).
- **Never put `->` inside a Blade tag's attributes** (e.g. `id="x-{{ $loop->index }}"`): the source-reading guards
  (`ControlNameContractTest`, `DialogFocusContractTest`) read a tag up to its first `>`, so the arrow cuts the tag short and
  hides `name`, `tabindex` and `x-trap`. Key such ids on a value with no arrow (`{{ $p['id'] }}`). And the `word@directive`
  trap fired again in new copy (`run@if`): use `{{ cond ? '…' : '' }}` inside words (Verified 2026-09-29).
- **Blade compiles a component TAG even inside a CSS or HTML comment** (`<x-operator.choices>` in a `/* */` comment in the
  layout broke every page with a PHP parse error). Never write `<x-…>` in a comment; name the file instead (Verified 2026-09-29).
- **Which MySQL a neighbour uses decides whether the engine swap touches it:** `advisor-platform` is on 3306 (pause it);
  `restiox` is on its own server on **3310** (the swap does not reach it) — check the connection's port, not the name.
- **Another project shares this Mac's MySQL.** `advisor-platform`'s suites block the mutation harness (its premise matches any
  `pest`), and the MariaDB engine swap stops the server its tests and its Octane dev server use: ask him to pause it first
  (F 2026-09-29). Identify its processes by working folder; never stop them.
- **`x-show` erases an inline `display`.** It hides by writing `display: none` and shows by REMOVING the inline display, so
  an element styled `x-show="open" style="display: flex"` comes back as `block` after its first hide — every Guests dialog
  opened in the top-left corner (🖥 260,242 instead of 640,450). Put the display in a class (`.vy-modal-backdrop`).
  `XShowInlineDisplayContractTest` holds the Guests, Maintenance and Units pages at 0 and 35 other sites at a ceiling (45 before
  the Maintenance pass fixed its six, 39 before the Units pass fixed its four) (Verified 2026-09-30, scanned).
- **`Livewire::test()` skips the web middleware that sets the permission team**, so `@can` there answers a silent FALSE (the
  #30 note). A test asserting a gated control through it can pass on an ungated look-alike — `UnwiredControlsTest` did, on a
  dialog heading. Render gated controls through the real route (🔬 probe: team=NULL → `can()`=false) (Verified 2026-09-29).
- **A public method on an operator Livewire component is an action** callable over `/livewire/update` (no route middleware).
  `OperatorLivewireMutationBoundaryTest` caught two pure static helpers on the first full run; they moved to a support class
  (`GuestListParams`). Keep components to lifecycle methods (Verified 2026-09-29).
- **`TestResponse::session()` starts the session store**, and flashed input is then gone before a follow-up request in the same
  test: `->assertSessionHasErrors()` followed by a `get()` saw no old input. Assert a refusal by what the next page renders
  (Verified 2026-09-29, by probe).
- **Livewire wraps `@foreach`/`@if` output in `<!--[if BLOCK]-->` markers** — a regex over closing tags misses them. Read a
  block by position (Verified 2026-09-29). ⚠️ **The markers contain `>`** (`<!--[if BLOCK]><![endif]-->`), so a `[^>]*`
  between two tags stops inside one: skip them as `<!--.*?-->`. And an element whose `@if` rendered nothing still holds the
  markers and the whitespace around them, so CSS `:empty` never matches it — 🖥 an empty sub-line kept its height and pushed
  the unit code off centre. Render the element only when it has content (Verified 2026-09-30).
- **The shared `.vy-modal` panel is see-through glass**; over the scrim, grey text falls below AA (🖥 sampled on the Portfolio's
  Add dialog: 3.52:1 sub-text, 3.25:1 labels). A dialog panel is also a `vy-modal-card` (near-opaque; 6.18 / 5.30 after)
  (Verified 2026-09-29).
- **Sort a derived column by exactly what it shows.** "Last stay" shows a departure for a past stay, so sorting by check-in
  put a long stay that ended last before a short one that ended sooner. The SQL mirrors the PHP cell, and the test's cast is
  chosen so the naive key gives a different order (Verified 2026-09-29).
- **The mutation harness found a weak check of this pass's own:** a count of `class="vy-modal "` WITH a trailing space never
  saw a panel whose class is exactly `vy-modal` (GL-M21 stayed green). Run the harness on your own guards (Verified 2026-09-29).
- **Close-on-scroll menus close themselves** under smooth or coasting scrolls: anchor a popup to its trigger while anything
  scrolls, and close it only when the trigger leaves the screen (🖥 390 px, Verified 2026-09-29).
- **A string `:style` replaces the whole `style` attribute; an object merges.** `style="width:38px;…"` beside
  `:style="on ? 'background:…' : '…'"` loses every static property the moment Alpine binds it (🖥 the work order page's
  Notify switch rendered 16×6 px). Bind an object with the same values. `AlpineStyleBindingContractTest` holds both Maintenance
  pages at 0 and 10 other sites at a ceiling (Verified 2026-09-29).
- **A window breakpoint cannot know a table's width.** Sideways scrolling switched off above 1100 px (for a sticky header)
  clipped the Maintenance table's last two columns at 1180 px (🖥 995 px of table in an 877 px card that clips). Scroll by
  default; stick only where the table was MEASURED to fit (`is-roomy`, set by the shared list script) (Verified 2026-09-29).
- **A picker's search box takes focus only after its opening transition** (Alpine holds `$nextTick` until then). A test script
  that typed within ~30 ms of the click typed into the button behind it, and the work order went to the default unit — the
  script's fault, proven by re-running at a human pace. Wait for focus before typing (Verified 2026-09-29).
- **`:nth-of-type` counts tags, not classes:** a script clicked the third `<button>` in a menu ("Assign"), not its third
  entry ("Edit"). Pick a control by role and name (Verified 2026-09-29).
- **A mutation must express the regression it names.** Flipping only one of a comparator's two "no deadline" branches made
  an inconsistent comparator whose result depends on the order the sort compares items; it stayed green (MB-M3). Flipped
  together, it was caught. A NOT CAUGHT can be the mutation's fault — read it before touching the test (Verified 2026-09-29).
- **Padding stacks inside the shared dialog panel.** The older `.vy-modal { padding: 26px }` adds to the head, body and foot's
  own 24 px; on a phone it left 250 px of a 350 px dialog and words broke mid-word. Fixed on the Maintenance board only
  (`OPEN.md` D-MODAL-PAD) (Verified 2026-09-29).
- **A class that looks right can style nothing.** The old Units forms used `.vy-label` and `.vy-inp`; no stylesheet defines
  either, so the labels were 16 px body text and the edit fields unstyled — the founder's "Kind is down" came from that plus a
  15 px field margin that dropped the buttons (🖥 718 vs 703). Before trusting a class, find it in the layout's CSS
  (`.vy-field-label`, `.vy-input` are the real ones) (Verified 2026-09-30).
- **A unique index is not a validation rule.** `units` is unique on (property, code); Edit had the rule, Add did not, so a
  duplicate code on Add reached the index and came back as a 500 (🔬 RED before the fix). When one write path of a column has
  a rule, check every other path that writes it — the defect sits beside the flagged thing (Verified 2026-09-30).
- **A known limit that says "it will fail silently" is a defect with a date not yet chosen.** L12p predicted the `/storage`
  photo 404 in September; the founder met it as "the preview is not available" (2026-09-30). When a limit names its
  own symptom, check whether the symptom is already live (Verified 2026-09-30).
- **`Storage::fake()` keeps only `throw` from the real disk config** — its `url` is gone, so a faked public disk answers
  `/storage/…` whatever the config says. When the address is what a test is about, pass it: `Storage::fake('public',
  ['url' => config('filesystems.disks.public.url')])` (Verified 2026-09-30).
- **A disk's `mimeType()` is not a content check.** Flysystem falls back to the EXTENSION when the content is inconclusive,
  so 🔬 a text file named `.jpg` read as `image/jpeg`. To judge a file by its bytes, use `finfo` on its path
  (Verified 2026-09-30).
- **`UploadedFile::fake()->create()` only REPORTS a size — its file is EMPTY.** A test that compares served bytes with the
  stored ones then compares `''` with `''` and proves nothing (🔬 the DEC-m test did, until a `not->toContain('')` failed on
  it). Give a file real bytes with `createWithContent()` whenever content is what is checked (Verified 2026-09-30).
- **Larastan cannot see the two columns `ulidMorphs()` makes** (`*_type`, `*_id`) — document them on the model with
  `@property`; never silence the error (Verified 2026-09-30, `Media`).
- **zsh does not split an unquoted variable into words:** `php artisan test $files` passed ONE path made of 27 and failed
  with "not found". Use `${=files}` (Verified 2026-09-30).
- **A required file runs in the scope that requires it.** A mutation set's top-level `$rows = '…'` overwrote the `$rows` of
  the function that loaded it, and the suite errored on "[] operator not supported for strings" (🔬 4,105/4,107, the
  first full run of the Units pass). The harness never hit it — its loop used other names. Load such files through
  `loadMutationSet()` (its own scope), and name a set's helper variables for what they hold (Verified 2026-09-30).
- **Before looping a service over a selection, read what ONE call costs.** The single unit pause recomputes the unit's whole
  room type for a year of nights (🔬 ~0.5 s, 1,098 queries); called once per selected unit, a page of 100 would have been
  ~100 identical recomputes, ~50 s locally, with no transaction around them — found by the independent review, not by the
  author or the suite. Do the per-item part per item and the group part once per group, in one transaction
  (Verified 2026-09-30).
- **The "another run is active" check also sees restiox.** `ops/proc-check.sh` refused because restiox's phpunit was running
  (its own database, port 3310 — not ours). Identify the process by working folder, wait for a gap, and never weaken the check
  (`OPEN.md` A7) (Verified 2026-09-30).
- **Blade matches `@php … @endphp` across the whole file.** An inline `@php($x = …)` ABOVE a later block form made Blade read
  everything between them as raw PHP — the Rates grid vanished into a ParseError (🔬 the first test run, 2026-10-01). In a view
  that has any `@php` block, write every `@php` as a block (Verified 2026-10-01).
- **Sessions here are JSON, so a seeded error bag comes back empty.** `withSession(['errors' => new ViewErrorBag…])` is emptied
  by `Store::marshalErrorBag()`, which reads only the stored array shape — the page showed no errors and the test saw nothing.
  Test what a page shows after a refusal with a real round trip: post, `followingRedirects()`, read the page (Verified
  2026-10-01).
- **Figures read straight after a page load may be mid count-up.** 300 ms after load the Rates figures read €118.73 and 38
  where the data said €131.49 and 42 (`partials.count-up` animates to the real figure). Read the data, or wait for the
  animation, before chasing a phantom (Verified 2026-10-01).
- **When two guards can refuse the same request, a test that accepts ANY refusal cannot tell them apart.** Removing the "All
  room types" refusal stayed green because the next guard refused too (mutation RP-M12). Assert each refusal's own sentence
  (Verified 2026-10-01).
- **A fixed height plus inherited vertical padding clips text.** A later `.vy-input` rule gives 11 px padding; a 40 px box kept
  16 px for 17 px of text and clipped "All room types on this page" on a phone. Where a box's height is fixed, zero its vertical
  padding (Verified 2026-10-01).
- **A comment in shipped CSS or JS is page text.** The layout's `<style>` goes to every operator page, so a CSS comment saying
  a dialog "opened" in the corner failed the email-templates page's check that it shows no "opened" engagement figure (🔬 the
  full suite, 4,145/4,146); a JS comment naming "Bulk edit" put the words on the page for a role that must not see the
  control. Keep interface words out of comments that ship; Blade comments (`{{-- --}}`) do not ship (Verified 2026-10-01).
- **A scroll box must be the containing block of what it scrolls.** An absolutely placed `vy-sr-only` header inside an
  `overflow-x: auto` table wrapper took `<main>` as its block, escaped the box and made the document 660 px wide at 390 — only
  production's data (a season) rendered that table, and the body's `overflow-x: hidden` hid it from the eye. Give every scroll
  box `position: relative`, and measure `scrollWidth` with real data, not just a screenshot (Verified 2026-10-01).
- **Follow what a create action makes to where it is used.** "Add rate plan" made a second plan that no screen could price (the
  grid prices each room type's main plan), and its success message sent the operator to the grid anyway; the storefront then
  skipped it for want of prices (Verified 2026-10-01).
- **A figure in a "measured" line must come from a measurement in this pass.** The Owners stylesheet comment first said
  "measured before: 41 and 35 px, padded twice" — never measured, and wrong for the old dialog (its own 24 px panel). Caught
  on re-reading before the commit and rewritten to what the old source shows. Write "read in the source" or "measured" only
  for what was (Verified 2026-10-01).
- **A control outside the swapped regions goes stale when the list changes in place.** The Owners Export link kept the address
  the page loaded with after an in-place sort; it now takes the view's address when clicked. Anything outside a
  `data-gl-region` that encodes the view must follow it (Verified 2026-10-01; the Units page has the same shape, `OPEN.md` A12).
- **A control that sets a preference reads the preference, not a status derived from it.** The row menu offered Pause again to
  a paused owner with nothing due: it read the pay status, which says "paused" only while money is due (Verified 2026-10-01).
- **Renaming a label can make a negative assertion vacuous.** `assertDontSee('1 draft')` passes for ANY count once the label
  changes; assert the zero in the new words (Verified 2026-10-01).
- **A Livewire page response is not a view response.** `viewData()` throws "The response is not a view"; read the page's own
  JSON island or markup (Verified 2026-10-01).
- **A guard read on one path is not a guard on the others.** The payout pause is read by the automatic run only; the manual
  flow ignores it — and the manual "Run payouts now" reaches every organisation (🔬 a throwaway probe in the test database,
  `OPEN.md` D-PAYRUN-TENANT). Before a page says what a setting does, read every path that acts on it (Verified 2026-10-01).
- **Probe a suspected cross-organisation path with a throwaway test, then delete it.** Two organisations in the test database,
  one request, read the other's rows — and report it as verified in the test database, not on production (Verified 2026-10-01).
- **A platform-level record shown on a tenant page is a leak, even read-only.** Payout run rows carry no organisation; the
  payout run page showed the newest one — another organisation's counts. Derive what a tenant sees from the tenant's own rows
  (Verified 2026-10-02).
- **A key unique across the platform, built only from a timestamp, collides across tenants.** Two organisations' manual runs in
  one instant shared `manual:<timestamp>`, and the second was handed the first's finished run. The suite's frozen clock made it
  certain; production would make it rare and silent. Put the tenant in the key (Verified 2026-10-02).
- **One test session switching users carries the first user's flash into the second's page.** A "not shown to B" assertion read
  A's own success message. Call `flushSession()` between users (Verified 2026-10-02).
- **A new refusal in a service must reach every "can this happen?" rule that mirrors it.** DEC-n taught `OwnerPayoutService`
  to refuse a paused owner, but `PayoutRecovery::isRetryable()` — the rule the owner portal's Retry button reads — did not
  learn it, so the button was offered and then refused. When a service gains a refusal, search for the predicates that
  pre-flight it (`isRetryable`, `can_*`, `*Rows` statuses) and teach them in the same change (Verified 2026-10-02, the
  independent review).
- **A page-wide search can be satisfied by new text anywhere on the page.** The Owners side-panel test looked for "from the
  payout run" in the whole page; a new pause-dialog sentence said it too, so the test passed whatever the panel claimed and
  OW-M32 survived the full run. Scope an assertion to its section, assert what must NOT be there too, and run every set each
  pass — that is what caught it (Verified 2026-10-02).
- **Count in the unit the sentence names.** "2 paused, skipped" counted statements for one paused owner; the page listed one.
  The browser walk caught it; a test now gives the paused owner two statements (Verified 2026-10-02).
- **Decide a dated change against the row that will govern its start day, not today's.** The commission write compared with
  the agreement in effect TODAY, so a second change in a month stacked a second open-ended agreement on the same day, and an
  undo matched today's rate and wrote nothing while the waiting change still came into force. When a change starts later, read
  what will govern THAT day — and make the "which row governs" read return one row, not the database's choice (Verified
  2026-10-03, `OwnerPageFixesTest`).
- **A second copy of a field list drops fields.** The commission write rebuilt the agreement from two named fields; the fee, the
  term and the attribution rules were lost. Carry the one list the other writer already uses (`AgreementAmendmentService::
  TERM_KEYS`) — never a hand-picked subset (Verified 2026-10-03).
- **An unticked checkbox posts nothing.** "Owner portal access" could only ever switch access ON from the form, while the server
  trusted a hand-made OFF for any login. A toggle needs a hidden 0 — and a field that can switch a login off needs the same
  sparing rule as every other path that does (`OwnerLoginLifecycle`), or it should not be read at all (Verified 2026-10-03).
- **A refused dialog that closes says nothing.** Every write on the owner's page refused by redirecting to a page that showed
  no error: a validation bag nobody rendered, a flash under a closed dialog. Name the form (`_form`), send its input back, and
  reopen that dialog with the reason beside the field (Verified 2026-10-03).
- **A class already used elsewhere styles your new element.** The owner page's root took `vyo-owner` — the Owners list's
  owner-cell class, `display: flex` — and the whole page laid out in a row. The class check only finds a class WITHOUT a rule;
  check every new name for an existing one before using it (Verified 2026-10-03, the browser).
- **A hint can promise an action no screen offers.** "You can give units later from the owner's page" — no screen sets a unit's
  owner after the invite. Before a sentence says something can be done later, find the write that does it (Verified 2026-10-03,
  every writer of `units.owner_id` read; this pass's own earlier sentence).
- **A test's search can be satisfied by the page's own script or by a second copy of the same address.** "The period has to end
  on or after the day it starts" is also the script's live hint, and the resend address sits in the dialog as well as on the
  card's button — so both tests passed without what they named (OM-M12, OM-M19). Assert the element that carries it (the
  field's error box, the button) (Verified 2026-10-03, the mutation run).
- **A test refused by validation tests nothing past it.** The portal tests posted no commission, so the new `required` rule
  refused them before the code they named — green whatever that code did, and the mutation (inserted before validation)
  agreed. Send a valid request so it reaches the line under test (Verified 2026-10-03, the independent review).
- **Close every row that covers the day, not just the one you read.** The first rule ended only the newest agreement on the
  start day; rows an older write had stacked stayed active beside the new one (Verified 2026-10-03, the review).
- **An outer guard hides an inner one from a test.** The header's buttons sit inside "can manage OR can pay", so removing the
  inner "can pay" changed nothing for a role holding neither (OM-M22). Test the role that splits them — one permission without
  the other (Verified 2026-10-03).
- **A dead end is not an error.** "It is not possible to do reservation" logged nothing: the page rendered, offered nothing, and
  said "No units are free". Read the data the page is given on production (read-only, names and counts) before reading code —
  it named both causes in one run (Verified 2026-10-04, `HISTORY.md`, "NEW RESERVATION").
- **A focused screen loses the shell's context.** The wizard has no app shell, so no property switcher: under "All properties"
  it quietly booked into the first one. A screen that drops the shell must show — and let you change — what the shell carried
  (Verified 2026-10-04).
- **Create the dependent row last, or undo it on every refusal — and never undo what something else now points at.** The write
  created the guest before trying the booking, so each refusal left a guest with their contact details. The clean-up, first
  written as "no reservation variable", would also have removed the guest of a booking SAVED before a later failure (the
  service records its events after the commit), nulling that booking's guest. Check the rows, not the variable (Verified
  2026-10-04, the independent review).
- **A browser draft must not outlive the booking it belongs to.** A localStorage draft filled the next booking — another day,
  another operator — with its extras, credit and deposit. Keep a draft per tab, restore only what the current screen shows
  across a change, and never restore money (Verified 2026-10-04, the independent review).
- **A message computed from a field the framework does not watch never re-renders.** "That email doesn't look right" read the
  email box directly; Alpine re-rendered nothing when it changed, so a refused Next said nothing. Make the message state, set
  when the step refuses (Verified 2026-10-04, the browser).
- **A shared parser's habit is a page's bug.** `MoneyInput` reads "150,50" as 15,050.00 — 100× a European amount. A page can
  refuse the ambiguous shape before it is sent; the shared rule is the founder's call (D-MONEY-COMMA, Verified 2026-10-04).
- **A double quote inside an `x-data="…"` ends the attribute — even inside a comment.** The Calendar dialog's script said "Check
  rack" in a comment: Alpine got half an object, every binding failed (38 console errors), and every server-side test stayed
  green. Only the browser's console showed it. Keep `"` out of attribute scripts; a test can refuse it (Verified 2026-10-04).
- **A guard behind another guard cannot be proven — keep one.** The view re-checked the permission after the component had
  already refused to open the dialog; removing it changed nothing, so its mutation was NOT CAUGHT. Keep the one that also saves
  the work, and prove it (Verified 2026-10-04, CN-M26).
- **A GET form puts everything it holds in the history and the access logs.** The dialog's "Check rack" carried the guest's
  name. Give personal fields no `name` in a GET form, keep them in the tab, and send them only in the POST (#28, Verified
  2026-10-04).
- **A value kept in the browser must know whose it is.** sessionStorage outlives a sign-out in the same tab. Mark it with the
  person and restore it only for them, only on the way back from the form that kept it (Verified 2026-10-04, the browser).
- **An address in a test can match the wrong link.** "The write surface opens" was `toContain(route('app.reservations.store'))`
  — the same path as the full form's GET link inside the dialog. Assert what only the thing under test renders (Verified
  2026-10-04, the review).
- **A money write offered in a second place must carry its confirmation there too.** The New reservation page's first "Apply
  season" posted on the first click — the Rates page applies only through "Apply this season?", which says what it overwrites,
  that it cannot be undone and what an owner negotiated (R-RATE). One partial and one builder now serve both (Verified
  2026-10-04, the independent review's H2).
- **Ask production's data why before reading code.** "Not possible to do reservation" was a season SAVED and never APPLIED —
  found by reading the workspace's seasons and audit rows (read-only, names and counts), not by any error (Verified 2026-10-04).
- **Under the suite's frozen clock two rows share a `created_at`.** `latest('created_at')` picked either booking and a test
  flickered; take the id from the response itself (the redirect's `created=`) (Verified 2026-10-04).
- **`$nextTick` is too early to scroll to what `x-show` just revealed.** The element had no box yet and the scroll did nothing
  — the message stayed 1,600 px below on a phone; two animation frames later it lands (Verified 2026-10-04, the browser).
- **A sentence about an email must ask whether email is sent at all.** "Directions reach the guest by email" was false on
  production (mailer `log`), for stays starting within two days, and with the template off — say it from
  `MailSafetyGuard::isSending`, the template and the date (Verified 2026-10-04).
- **A run that prints nothing has not passed.** The first full MySQL run of 2026-10-04 ended with an empty output file: the
  agent-output package had failed at shutdown and taken the result with it. Read the counts from a file the run writes itself
  (Part 3, the suite; Verified 2026-10-04).
- **A design's sentence is a promise.** "Matches an existing profile" and "Confirmation email sent" came from the wireframe and
  were never true here. Check every sentence a design hands over against what the code does (Verified 2026-10-04, read).
- **A page-wide "it links to X" passes on the app shell.** The sidebar links to Units on every page, so a check that the empty
  Calendar links there stayed green with the link removed (CB-M28). Look inside the block the check is about (Verified
  2026-10-04, the mutation harness).
- **Livewire 4 marks each Blade `@if` with an HTML comment** (`<!--[if BLOCK]><![endif]-->`). A check that spans an `@if` boundary
  must allow it, or it fails on correct output (Verified 2026-10-04).
- **Say the next step from what the page shows.** "Pick a unit to continue" came back above a unit already picked; the server
  could not know. The dialog now says it from its own state (Verified 2026-10-04, the browser).
- **A row's words must come from the record whose dates they show.** The Stay cell took the GUEST's stage and ONE booking's dates,
  so an arrival due today read "Left <its check-out>" — a date still ahead (Verified 2026-10-05, the independent review; the sort key
  had the same mix and moved with it).
- **Alpine's `x-model` on a `<select>` picks before a child `x-if` has added the option.** Edit opened on "Not given" for a saved
  code the list lacks, and Save would have erased it; set the select again on `$nextTick` (Verified 2026-10-05, 🖥 the select's
  value read `""`).
- **A rule on a shared kit class reaches every page that uses the class.** `.vy-gl-contact` stacked on Guests also stacked the Units
  sub-line ("Name / · Door code set"). Scope a page's rule to its own root (Verified 2026-10-05, the review, then 🖥 on Units).
- **When a new refusal shares a path with an old one, the old test can pass for the new reason.** The paused-workspace test kept
  passing with the pause check deleted once a mailer that sends nothing refused too; give each test a fixture where only its own
  reason applies (Verified 2026-10-05, the review; GS-M50).
- **A table shares its shrinking across every column that may wrap.** "Wrap a long country name" was taken at 1,440 px too, where
  nothing needed it; allow it only in the band that needs it — and measure the band (Verified 2026-10-05, 🖥 390–1,600 px).
- **An email's "just reply" is a promise about where the reply goes.** With no reply-to, a guest's reply reached Vyrobel's own
  sender while the email said "we'll see it" and the page said "replies land in your inbox" — no email reply ever does (Verified
  2026-10-05, read: no inbound mail; the review).
- **A figure caught mid-count is not a wrong figure.** The KPI read 5 where the data said 6: the count-up animation, screenshot
  too early (Verified 2026-10-05, 🖥).
- **A test that a function EXISTS does not prove it is CALLED.** The Edit fix's tests read `syncNat()`'s body; deleting either call
  put the defect back with every test green (Verified 2026-10-05, the second review; GS-M56, GS-M57).
- **Before a sentence points at a page, check the reader can open it.** "Add a reply-to address in Settings" was shown to three of
  the five roles that may send, and Settings refuses all three (`properties.manage`) — "the UI never invites a 403" covers words as
  well as buttons (Verified 2026-10-05, the second review; `config/authorization.php` read).


---

## PART 3 — THE INSTRUMENTS

Rule for all of them: **one run at a time**. A guard never seen to fire is not a guard, so use the self-test or
prove-red mode where one exists. Commands are copied from each script's header (git). Where a header and the code
disagree, it says so.

**Suite on MySQL (local).** Prerequisites: a `vyrobel_test` DB and Redis. Run it ALONE:
`ops/proc-check.sh -q '[p]hpunit|[p]est|artisan [t]est' && php artisan test`
Pass = the suite is green. The count is whatever the run prints. Last reported for the LIVE commit: 3995/3995 MySQL
(Reported: DECISIONS ADMIN 05). After the Portfolio Open + fields (2026-09-29): **4,047/4,047 on MySQL 8.4.10 and on MariaDB 11.8.9**
(Verified: run outputs). ⚠️ **Run it under `caffeinate -i`:** on 2026-09-27 the Mac slept mid-run, and a
~17-minute suite took 8½ hours of wall time. `RefreshDatabase` is OFF, so fixtures need unique ids (CLAUDE.md).
⚠️⚠️ **Read a gate's result from a JUnit log, not from the printed line.** `laravel/pao` (installed with the tools) swaps Pest's
output for one JSON line when it detects an agent. On 2026-10-04 it failed at shutdown (`stream_filter_remove(): Unable to
flush filter`, in the app log) and a full run printed NOTHING, exit 1 — a run with no result, not a pass. Run a gate as
`PAO_DISABLE=1 php artisan test --log-junit=<file>` (its own switch) and count `tests`/`failures`/`errors`/`skipped` from the
file (Verified 2026-10-04: the re-run, 4,280/4,280).

**The MariaDB engine swap (both engines are a gate).** The engines SWAP on 3306 and never run side by side: on
another port the concurrency workers still hit 3306. Steps: `brew services stop mysql@8.4`, then
`mariadbd-safe --no-defaults --basedir=/opt/homebrew/opt/mariadb@11.8 --datadir=/opt/homebrew/var/mariadb118 --port=3306 --socket=/tmp/mysql.sock`,
run the suite, then `brew services start mysql@8.4`. `--no-defaults` is required. The app sets READ COMMITTED itself (L12b).
(Reported: CLAUDE.md Commands.)

**Money gate FULL — local only, never production.** It wipes the local DB and paywalls tenant two (L8):
`export VY_SEED_DATE=2026-09-01; php artisan migrate:fresh --seed --force; bash ops/money-as-seeded.sh`
Pass = `mode=FULL` + `✅ MONEY AS SEEDED · FULL`, exit 0. `mode=INVARIANT` / `⚠️ PARTIAL — 4 of 6` is NOT the gate.
EXPORT the date; a `VAR=x cmd1 && cmd2` prefix reaches cmd1 only. Quote its own sentence ("tenant one's six aggregates…"),
never "the money is unchanged". The constants live in the script (git). The suite never runs this.

**Mutation runner.** `php tests/Mutations/run.php --set=admin-06` · `--only=A06-M7,A06-M8` · `--list` · no args = every set.
Verdicts: `LANDED · CAUGHT` (good) · `LANDED · NOT CAUGHT` (guard unproven) · `DID NOT LAND` (harness error, never a result).
It refuses to run beside another test run or with cached routes/config. It self-checks every run (git: header).
Sets: tests/Mutations/sets/*.php (git ls). Last: **343/343 caught across every set, 0 not caught, 0 did not land** (Verified: harness,
2026-09-29; `admin-06` = 87, `admin-05` = 49, `portfolio-remove` = 14, `portfolio-fields` = 6). ⚠️ Its "another test run is active" premise matches ANY `pest` on the Mac, another
project's included (`advisor-platform` runs suites here): wait for a gap, and never weaken the check (`OPEN.md` A7).

**`ops/proc-check.sh`** — is a test run already going? `ops/proc-check.sh -q '<regex>'`; `--kill '<regex>'` kills by PID and confirms.
Exit **0 = SAFE** (a trusted zero) · 1 = matches found · 3 = BLIND (its positive control failed) · 4 = `--kill` refused.
The exit codes were inverted 2026-09-22 so that `&&` fails safe (git: header).

**`ops/ps-check.sh`** — self-match-proof `ps | grep`. `ops/ps-check.sh 'queue:work|horizon'`, or `-c` for the count.
Exit 0 = matches printed · 1 = none. ⚠️ These exits are grep-like, the OPPOSITE of proc-check. It cannot tell WHOSE process it
found, so pin the php84 binary or the CRONJOBID. Sample every 1 s for short-lived workers (git: header; CLAUDE.md).

**`ops/exposure-battery.sh`** — the wall around `.env`/`artisan`/`storage` (L12p). It never prints a body.
`ops/exposure-battery.sh [BASE]` · `--prove-red` (adds `/robots.txt`, which MUST go red) · `--self-test`.
(History: while the private-preview lock was on, it had to run with `VY_BATTERY_NETRC=<path to a netrc file>`. **The lock and the
park were turned OFF on 2026-09-23 (`5d631d9f`), so the site is public: Verified 2026-09-27.** Never pass a password in an env var or argv.)
Pass = exit 0, `SERVED=0` with `APPLICATION-REFUSED` > 0. Exit 1 = something SERVED. Exit 2 = unmeasured, OR nothing reached the
app ("a weather report"), OR the PHP-marker premise failed (git: script). (The "46/53 proven" figure dates from when the lock was on.)

**`ops/htaccess-drift-check.sh`** — are the rules of `public_html/.htaccess` still PRESENT (not byte-identical)? Run it ON the host:
`ops/htaccess-drift-check.sh [PATH]` · `--self-test` (deletes a rule from a copy and must go red).
Exit 0 = every rule present · 1 = missing / anti-rule violated · 2 = cannot run. Reference copy: `ops/reference/public_html.htaccess`.
It catches a CHANGE; the battery catches an EFFECT. Neither replaces the other (git: header).

**`ops/public-checks.sh [BASE]`** — after EVERY swap. It checks /login + both social buttons, both social 302s, /signup, the anonymous /admin redirects and POST refusals, a 5xx sweep of public GETs, and battery SERVED=0.
Pass = `PASS N · FAIL 0 · COULD NOT MEASURE 0`, exit 0. Exit 2 = a CDN browser challenge (403 "Checking your browser"): control-run it.
**Any FAIL → roll back FIRST, diagnose after.** Last: PASS 12 · FAIL 0 · CNM 0 on `rel_AH` (Verified 2026-09-29, after the Portfolio Open + fields swap).

**`php artisan tinker --execute='require "ops/config-inventory.php";'`** (on the host with the php84 binary, inside the release).
It reports which config keys are EMPTY and what each emptiness does: SILENT / LOUD / INERT / UNCLASSIFIED. It never prints a value.
Pass = calibration ✓ and `0 EMPTY+SILENT`, exit 0. Exit 1 = ≥1 silent empty · 2 = calibration failed / no Laravel context.
It also prints the cached-config hazard and the stray `env()` sites. ⚠️ The header also lists `ops/config-inventory.php --json`, but
the file is not executable, loads no autoloader and has no `--json` branch (📖 read, not run). Use the tinker form.
The same class feeds the admin console health board (git: header).

**`php ops/references.php '<literal>'`** — is a name REALLY referenced, or only mentioned? It walks the booted router's `can:`
gates, tokenises PHP and compiled Blade, and splits PROD from TEST. Flags: `--all` · `--routes` (named routes no view links; ≠ unreachable).
Exit 0 = referenced by code · 1 = no real reference (pending a second method) · 2 = usage. It is blind to runtime-built names,
DB rows and .sh/.md. ⚠️ The header says `ops/references.php …`, but the file has no shebang, so call it with `php` (📖 read, not run).

**`ops/secret-scan.sh tree|history [--all]`** — run `history` before any push: it covers every blob in every ref, which tree mode misses.
Exit 0 = no hits · 1 = hits (triage them) · 2 = usage. Matches are redacted. There is NO allow-list, so the demo seed password is
reported every time, correctly (git: header). Trust it only after it has found a planted needle (Reported: auto-memory index).

**`ops/release.sh`** (L12k as a script; runs over SSH with the founder's deploy key; do not run it from a read-only pass):
`status` (live release + commit read from `.release`) → `stage rel_<LETTERS>` → `migrate rel_X` (pending only; ADDITIVE only, refuses
drop/rename/change) → `swap rel_X` → `ops/public-checks.sh` → prune by hand, ≥180 s later. `rollback rel_<prev>` repoints `current`.
`recache rel_X` = the same commit, with config+routes rebuilt from the current `.env` (the L14 path for an `.env`-only change).
`stage` refuses a dirty tree, an already-live HEAD, and composer/package/vite/resources css/js changes. Its pass lines:
`✓ every shipped file has its own inode — the live release is untouched`, `.release = <HEAD>`, and
`.env → ../../public_html/.env · shared file mode 600` (git: script).
⚠️ **Byte-for-byte live check (ADMIN 06 #6, committed 2026-09-29):** `stage` now builds a sha256 manifest
of the deployable files (app bootstrap config database public resources routes ops artisan). It excludes bootstrap/cache, public/build
and resources/css|js; vendor/, `.env` and storage/ are not in it. The manifest is built at the LIVE commit, and
`sha256sum -c` runs it inside the live release. Each file that differs or is missing ships from git at the new commit.
Pass line: `drifted from git on the host: none`. It found 4 of 1,395 drifted on rel_AE and shipped them on its first run
(ADMIN 06 deploy, 2026-09-29); after it, 1,401 of 1,401 match (Verified).
Never use `ops/deploy.sh` on the host: it refuses by design (L12h).

**The PW-SIGNUP delete proof tools** live in `docs/memory/tools/` (saved by MEMORY 01; see its README). **Verified 2026-09-27:
byte-identical (sha256) to the server copies** in the private folder `~/domains/vyrobel.com/env-backups/`.
Run the PHP ones from a release directory with the php84 binary. They are read-only, print counts and fingerprints only, and read
tables and keys from `information_schema`, independent of `OrganizationDeletion` (git: headers).
(Stored as `*.php.txt` so the style gate does not lint them; copy each to its `.php` name to run it.)
- `php census.php <out.json> <organisation id>` — per-org row counts and money sums, every table.
- `php proof.php before <organisation id> <dir>`, then `php proof.php after <dir>` — premises, controls, census, orphans, footprint.
- `php footprint.php before <ids.json> <out.json> <organisation id>` / `after <ids.json> <out.json>` — every row still naming it.
- `python3 compare.py before.json after.json` — pass = exit 0 (the probe's rows gone, every other org unchanged, volatile tables excepted).
Status: the before-measurements exist (27 Sept). PW-SIGNUP PROBE was NOT yet deleted, and no after-count exists (Reported: DECISIONS.md
ADMIN 06 #0; V 2026-09-29: still not suspended). ⚠️ Retake the backup and the baselines before his click: these predate ADMIN 06's migration. The preview said 17 records; the deletion removes 232 (same source).

Other `ops/` scripts not covered here (git ls): backup.sh · restore-drill.sh · liveness-drill.sh · runtime-contract.sh ·
probe-signup.sh · mass-assign-check.php · host/mark-internal.php · deploy.sh (refuses to run). Read each header before use.

---

## Sources

- docs/KNOWN-LIMITS.md (L1–L20) · docs/MEMORY.md (2026-09-05) · docs/DECISIONS.md
  (FINISH 01 Part C, SESSIONS 01, ADMIN 06) · CLAUDE.md (Commands, worker-cron correction)
- docs/DEPLOYMENT.md · docs/STRIPE-PRODUCTION.md · docs/GO-LIVE-REQUIREMENTS.md (B19, B20) · docs/GOOGLE-SIGN-IN.md ·
  docs/OAUTH-DESIGN.md · docs/THE-WALK-2026-09-23.md · docs/STATE.md · docs/AUDIT-SUMMARY.md (rulings §4, untested §5) ·
  docs/INSTRUMENT-ERRORS.md · docs/PHANTOM-PERMISSIONS.md
- Script headers (git): ops/exposure-battery.sh · ops/ps-check.sh · ops/proc-check.sh · ops/config-inventory.php ·
  ops/htaccess-drift-check.sh · ops/public-checks.sh · ops/release.sh (+ `git diff ops/release.sh`) · ops/secret-scan.sh ·
  ops/references.php · ops/money-as-seeded.sh · tests/Mutations/run.php · tests/TestCase.php:90
- docs/memory/briefs/{ADMIN-06,DNS-01,KEYBOARD-01,MAIL-02,TONIGHT-01,PW-SIGNUP-DELETE-1,-2}.md · docs/memory/tools/* (committed in `58800419`)
- Auto-memory (outside the repo): `~/.claude/projects/-Applications-XAMPP-xamppfiles-htdocs-vyrobel/memory/` —
  MEMORY.md (index) and audit-method-and-traps.md (Tier 1/2)
- git: log (91b83801, 975116b2, 3cd72c26, 6a444d09), `git status`, `git diff --stat`
