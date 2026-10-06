# Vyrobel — Deployment runbook

> Created 2026-08-08 as part of the audit fix pass (`docs/AUDIT-COMPLETE-2026-08-07.md`, finding **F6-4**).
>
> ⚠️ **The gap this document exists to close.** The application's 9 scheduled commands are correctly
> registered in `routes/console.php` and `php artisan schedule:list` shows all of them — but **nothing in the
> repository or the docs ever defined the one cron entry that makes Laravel's scheduler actually fire.**
> Without it every command below silently never runs, in a way that produces no error and no alert. Two of
> them are legally load-bearing: the **GDPR 14-day guest deletion** and the **30-day ID-document purge**.
> A "successful" deploy with no cron entry means erasure requests are accepted and never executed.

---

## ⚠️⚠️ HOW TO VERIFY AN ACCOUNT WHEN MAIL IS DOWN — added 2026-09-23 (GATE 01)

⚠️⚠️ **THE CAPABILITY THAT, UNWRITTEN, THE NEXT SESSION DOES NOT HAVE.** With `verified` on the `/app` group,
an unverified account cannot reach the back office — and the "resend" link is **mail**, so while Postmark is
dark there is no self-service way back in. 🔬 On 2026-09-23 production held **exactly one** account
(`founder.test@vyrobel.com`) and it was **unverified**: shipping the gate without this step would have locked
the only human out of his own system.

```php
// On the host:  cd ~/domains/vyrobel.com/public_html/current
//               /opt/alt/php84/usr/bin/php artisan tinker
$email = 'someone@example.com';

// ⚠️ GUARD FIRST. A blanket UPDATE on `users` is exactly the shape that goes wrong quietly.
$n = DB::table('users')->where('email', $email)->count();
if ($n !== 1) { throw new RuntimeException("expected exactly 1 row, found {$n}"); }

DB::table('users')->where('email', $email)->whereNull('email_verified_at')
    ->update(['email_verified_at' => now()]);   // ⚠️ must report affected = 1
```

⚠️ **Read the before and after, and confirm the affected count is 1** — not "it seemed to work".
⚠️ It is exactly what clicking the signed link does; it forges no token and bypasses no other gate.
⚠️ **Reversible:** set the column back to `NULL` to put the account behind the door again.
⚠️ **This is a break-glass step for an operator you can identify out-of-band, not a routine one.** The
supported path is the emailed signed link; this exists because mail can be down precisely when you need in.

## ⚠️⚠️ HOW TO REACH THE PRODUCTION HOST — added 2026-09-23, because it was nowhere and that cost four measurements

```
ssh -i ~/.ssh/vyrobel_deploy -p 65002 u545270279@82.25.102.188
app root:  ~/domains/vyrobel.com/public_html/current   (the `current` symlink — L12k)
php:       /opt/alt/php84/usr/bin/php                  ⚠️ the host default `php` is 8.3 and artisan exits 255
```

⚠️⚠️ **WHY THIS BLOCK EXISTS.** On 2026-09-22 a pass marked four production verifications **RECORDED rather
than MEASURED** because it could find no documented way onto the host. 🔬 The access material was on the
machine the whole time — the key above, the host and port in `~/.ssh/known_hosts`, the username in a
`.remember` scratch file — but **none of it was in any document a session is told to read**, and there is no
`~/.ssh/config`. ⚠️ **The tier system worked** (nothing false was carried forward) **and it still cost four
real measurements**, one of them `ops/htaccess-drift-check.sh` — which reads the only wall between the public
internet and `.env`, `artisan` and `storage/` on a host where the document root **is** the application root
(**L12p**). ⚠️ **A guard that cannot be run is a note again.**

⚠️ **The drift check expects to run ON the host** (`FILE="$HOME/domains/…/.htaccess"`). To run it read-only
from a workstation, fetch the file into a mirrored path and override `HOME` — never copy the script up, which
would be a production write:

```
D=/tmp/prodhome/domains/vyrobel.com/public_html; mkdir -p "$D"
ssh -i ~/.ssh/vyrobel_deploy -p 65002 u545270279@82.25.102.188 \
  'cat ~/domains/vyrobel.com/public_html/.htaccess' > "$D/.htaccess"
HOME=/tmp/prodhome ops/htaccess-drift-check.sh
```

⚠️ **READS ONLY.** Production writes are forbidden outside an explicitly authorised, bounded exception.

## 0. RUNTIME REQUIREMENTS (check these BEFORE provisioning)

> ⚠️ **Added 2026-08-16 by the deployment-scoping pass, which found the repository's own declared floor was
> wrong.** `composer.json` required `php: ^8.3` — and had done since long before the dependencies that broke
> it landed. The installed production graph requires **PHP ≥ 8.4.1**: `openspout/openspout` (added by D4-1
> for the Excel exports) requires `~8.4.0 || ~8.5.0`, and the whole `symfony/*` v8.1 stack requires
> `>= 8.4.1`. A server provisioned to the floor this repository advertised **could not install the
> application at all** — `composer install --no-dev` would fail outright, at deploy time, on a correctly
> followed instruction. The constraint is now `^8.4.1`, which is what the code actually needs.

| Requirement | Version | Why / evidence |
|---|---|---|
| **PHP** | **≥ 8.4.1** | `composer why-not php 8.4.0` lists the blockers. Do **not** provision 8.3 |
| **MySQL** | 8.x (InnoDB) | Genuine MySQL, **not MariaDB** — the app uses standard InnoDB locking (`SELECT … FOR UPDATE`) on the no-double-booking and payout paths |
| **Redis** | any current | Queue + Horizon (`QUEUE_CONNECTION=redis`) |
| **ext-zip** | — | Declared in `composer.json`. Owner statement-archive ZIP (DO24-1) **and** required by `openspout` |
| **ext-dom / ext-filter / ext-libxml / ext-xmlreader** | — | Required by `openspout`; Composer enforces them transitively, so they need no separate declaration here |
| **Node** | for the build only | `npm ci && npm run build`; not needed at runtime |

⚠️ **`ext-gd` is deliberately NOT a production requirement.** It is used by `App\Support\Media\PlaceholderImage`,
whose only callers are `DemoSeeder` and a test — and production is never seeded (§4). Declaring it would
harden a dev-only dependency into a production install requirement, which is the opposite of correct.

### The pre-flight that catches all of this

Run **on the target machine**, before the first deploy — it compares the lock file against the real PHP
binary and its extensions:

```bash
composer check-platform-reqs --no-dev    # every line must read "success"
```

⚠️ This is the check that would have caught the wrong PHP floor above, and it is the same class of failure as
a missing extension discovered at runtime. Cheap, definitive, and it belongs in the deploy script when that
lands.

---

## 1. THE CRON ENTRY (do this, or nothing below runs)

Exactly one line, as the user that owns the application directory:

```cron
* * * * * cd /path/to/vyrobel && php artisan schedule:run >> /dev/null 2>&1
```

Laravel's scheduler is invoked every minute and decides internally which commands are due — you do **not**
add a cron line per command. Verify after deploying:

```bash
php artisan schedule:list     # must list all 9 commands with their next due time
```

### The 9 scheduled commands

| Cadence | Command | What breaks without it |
|---|---|---|
| daily 03:00 | `guests:process-deletions` | **GDPR.** The 14-day scheduled guest deletion never executes — erasure requests are accepted and silently never served |
| daily 03:30 | `guest-documents:purge-expired` | **GDPR.** Encrypted guest ID documents are retained past their 30-day window, indefinitely |
| daily 06:00 | `payouts:run` | Owners are never paid on schedule (test-mode until the founder's go-live flip) |
| daily 02:00 | `properties:process-removals` | Scheduled property archival never happens |
| daily 09:00 | `emails:send-pre-arrival` | Guests get no pre-arrival email |
| daily 09:00 | `emails:send-payment-reminders` | Outstanding balances are never chased |
| daily | `invites:prune-expired` | Expired invite tokens stay accepted-looking |
| daily | `platform:prune-activity` | Platform activity log grows without bound |
| daily 04:00 | `retention:prune` | Log-like tables grow without bound (added 2026-08-29 · audit M-6). ⚠️ `audit_events` and `domain_events` are deliberately NEVER pruned — they are the evidence for a disputed owner figure |

⚠️ **IF ONE OF THESE THROWS, ALMOST NOTHING CHANGES — VERIFIED 2026-08-31.** Laravel catches it, writes **one `error` log line** through the exception handler (so the cron's `>> /dev/null` does not swallow it) and moves on. `schedule:run` still exits **0**, the §7.2 heartbeat still beats and `/up/liveness` still returns **200**. There is no failed job and no alert. **The log is the only place a failure shows.**

Test one without waiting for its slot:

```bash
php artisan guests:process-deletions   # each command is individually runnable and self-guarding
```

---

## 2. QUEUE WORKER (Horizon)

The queue is **not optional** — booking-confirmation emails, ARI pushes and exports all ride it. Run Horizon
under a process supervisor so it restarts on failure and on deploy:

```ini
[program:vyrobel-horizon]
process_name=%(program_name)s
command=php /path/to/vyrobel/artisan horizon
autostart=true
autorestart=true
user=<app-user>
redirect_stderr=true
stdout_logfile=/path/to/vyrobel/storage/logs/horizon.log
stopwaitsecs=3600
```

On each deploy: `php artisan horizon:terminate` (Horizon restarts with the new code).
Watch `failed_jobs` — a non-zero count is a real signal, not noise. (The audit found 36 dead
booking-confirmation jobs there, which is how a 100% email-delivery failure was discovered.)

---

## 3. REQUIRED ENVIRONMENT

Beyond the framework defaults, set at minimum:

```dotenv
APP_ENV=production
APP_DEBUG=false                  # config default is already false — do not override it
APP_URL=https://<your-domain>
SESSION_SECURE_COOKIE=true       # audit: absent from .env.example; ships insecure without it
QUEUE_CONNECTION=redis
```

⚠️ **`APP_URL` MUST match the host the app is actually served on.** `Storage::url()` builds ABSOLUTE media
URLs from `APP_URL`, so a mismatch makes every room photo cross-origin — and with the CSP now in place
(`img-src 'self'`) the browser BLOCKS them rather than merely loading them from the wrong host. Found while
verifying the CSP: serving on `:8901` while `APP_URL` said `:8000` produced exactly this. Same-origin in
production is the correct posture; the fix is a correct `APP_URL`, not a looser policy.

**Behind a load balancer or reverse proxy**, configure trusted proxies before enabling the operator IP
allowlist — otherwise every request appears to come from the proxy and the allowlist blocks everyone
(audit F6-10; the direction is safe, but the control silently stops working).

### Founder go-live flips (deliberately deferred, each honest in-product today)
`STRIPE_*` live keys + Connect · `PAYOUT_ENGINE_ENABLED=true` · `CHANNEX_*` (+ run
`docs/prompts/live-integration-checkpoint.md` steps 1–2) · Twilio · FCM/APNs · `passport:keys` per
environment · `tax:set-rate` per jurisdiction (the seeded rates are **EXAMPLE only**) · email SPF/DKIM/DMARC.
See `docs/STRIPE-PRODUCTION.md`, `docs/EMAIL-PRODUCTION.md`, `docs/OAUTH-DESIGN.md`.

---

## 4. ⚠️ DO NOT SEED PRODUCTION

`database/seeders/DatabaseSeeder.php` unconditionally runs `DemoSeeder` and `PlatformAdminSeeder`, and **no
seeder carries an environment guard** (audit F6-2/F5). Running `php artisan db:seed --force` against
production would create a **platform super-admin `admin@vyrobel-demo.test` / `password`** — a realm that sits
above tenancy.

Deploy with `php artisan migrate --force` and **never** `--seed`. Production platform admins are created
out-of-band:

```bash
php artisan admin:create
```

---

## 5. DEPLOY SEQUENCE

⚠️ **These are the same steps `ops/deploy.sh` now runs — see §7.3.** Prefer the script: it adds the
pre-flight refusals, the pre-migration backup, the scheduler-cron install, the `/up` health check and a
rollback path, and it cannot skip or reorder a step under pressure. The list below is the mental model.

```bash
php artisan down --render="errors::503"
git pull && composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force            # never --seed
php artisan config:cache route:cache view:cache
php artisan horizon:terminate
php artisan up
```

Then confirm: `php artisan schedule:list` (8 commands) · `php artisan about` · Horizon dashboard reachable
(Super-Admin gated) · `failed_jobs` empty.

---

## 6. STILL OUTSTANDING (not closed by this document)

Tracked in `docs/AUDIT-COMPLETE-2026-08-07.md` — these are real work, not config:

- ✅ ~~No security headers~~ — **DONE 2026-08-08** (`App\Http\Middleware\SecurityHeaders`, prepended to the
  `web` group): CSP, X-Frame-Options SAMEORIGIN, nosniff, Referrer-Policy, Permissions-Policy on every realm;
  HSTS only over a genuinely secure connection. The CSP is built from an inventory of what the app really
  loads — see the class docblock for the four relaxations it needs and what each would take to tighten.
- ✅ ~~No custom error views~~ — **DONE** (`resources/views/errors/`, branded 404/403/419/429/500/503).
- ✅ ~~No npm lockfile~~ — **DONE**; `npm ci` and `npm audit` both work.
- ✅ ~~Dependency advisories~~ — **DONE**; `composer audit` is clean.
- ✅ ~~Seeders have no production guard~~ — **DONE**; they now refuse outright.
- ⬜ **Still missing: Dockerfile / CI workflow / deploy script / backup strategy.** These are the remaining
  deployment sprint, not config. **Scoped and ranked below (2026-08-16).**

---

## 7. THE DEPLOYMENT SPRINT — gap map and ranked plan (scoping pass, 2026-08-16)

⚠️ **Grounded in the repository, not in this document's own claims.** Every "present" below is a file that
exists; every "absent" is a path that does not.

| Concern | State | Grounded in |
|---|---|---|
| Cron / scheduler | **Documented, not enforced** | §1 above; nothing in the repo *verifies* it |
| Runtime requirements | **Present (fixed this pass)** | §0; `composer check-platform-reqs --no-dev` passes |
| Queue / Horizon | **Documented, not shipped** | §2 has the supervisor block; no `supervisord.conf` in-repo |
| Env template | **Present and adequate** | `.env.example` (85 keys); every critical key checked present, incl. `SESSION_SECURE_COOKIE`; **zero `env()` calls outside `config/`**, so `config:cache` in §5 is safe |
| Security headers · error views · npm lockfile · dependency advisories · seeder guards | **Done** | closed by the audit pass, above |
| **Backups** | **ABSENT — nothing at all** | no script, no documented policy, no restore drill |
| **Monitoring / alerting** | **ABSENT** | nothing would report a stopped scheduler, a stalled queue or a failed payout run |
| Deploy script | **Absent** (a manual sequence exists) | §5 is a human checklist; no `deploy.sh` |
| Secrets management | **Absent** | `.env` on disk only |
| Dockerfile / reproducible runtime | **Absent** | no `Dockerfile`, no compose file |
| CI pipeline | **Absent** | no `.github/`, no CI config of any kind |

### 7.1 BACKUPS + A PROVEN RESTORE — ✅ BUILT 2026-08-16 (rank 1)

⚠️ **The restore is the deliverable, not the backup.** A script that produces a file proves only that a file
was produced; `ops/restore-drill.sh` restores the archive into a **scratch database** and asserts the data
matches. Run it after any change to either script — and periodically, because a backup nobody has restored
is a hypothesis.

```bash
BACKUP_PASSPHRASE='…' ops/backup.sh          # → storage/backups/vyrobel-<UTC>.tar.gz.enc
BACKUP_PASSPHRASE='…' ops/restore-drill.sh   # → restores into a scratch DB and compares; exit 0 = passed
```

**What is captured** — everything unrecoverable if the volume dies:

| Captured | Why |
|---|---|
| The MySQL database (`--single-transaction`) | Every folio, ledger, payout, statement. 130 tables |
| `storage/app` | Media blobs **including guest ID documents encrypted at rest**; nothing regenerates them |

**Deliberately excluded:** Redis/Horizon queue state (in-flight jobs, not a system of record),
`bootstrap/cache`, `storage/framework`, `vendor/`, `node_modules/` — all rebuilt by a deploy.

⚠️ **`APP_KEY` IS NOT IN THE ARCHIVE, AND THAT IS DELIBERATE — this is the part that will bite.** Guest
email/phone columns and the ID-document blobs are encrypted with it. **Without it a restore produces rows
whose PII is permanently unreadable; with it stored beside the backup, the encryption protecting that PII is
worthless** — one stolen archive would carry both ciphertext and key. ⛔ **Founder step: store `APP_KEY` in a
separate secret manager. Losing it is as fatal as losing the backup.**

⚠️ **The backup REFUSES to run without `BACKUP_PASSPHRASE`** (exit 2) rather than writing guest personal data
in the clear — a plaintext archive would be a GDPR breach committed by the backup itself. Retention defaults
to 14 days (`BACKUP_RETAIN_DAYS`) and is pruned **after** a successful run, so a failed backup can never
delete the last good archive.

**What the drill asserts** — proven on the real dataset, not an empty one:

- **Row counts for all 130 tables**, compared per table (a total would let +1/−1 cancel)
- **Non-vacuity**: it FAILS if the source has no reservations, because an empty DB restores perfectly and
  proves nothing
- **Field-by-field spot-checks** on a reservation, a payout and a statement
- **Money totals**: payouts, statement net, folio items
- **Referential integrity**: 270 foreign keys restored, and no orphaned rows
- **The file set**: count plus a SHA-256 checksum on a real file

⚠️ **It refuses to run if the scratch database name equals the source**, and drops the scratch schema on
exit — a drill that restored over the live schema would destroy the thing it exists to protect. Verified:
a deliberately damaged archive exits **1** with the mismatch named; a good one exits **0**.

**Schedule it** (alongside the §1 scheduler line, as the app user):

```cron
30 2 * * * cd /path/to/vyrobel && BACKUP_PASSPHRASE="$(cat /etc/vyrobel/backup.pass)" ops/backup.sh >> storage/logs/backup.log 2>&1
```

⛔ **STILL FOUNDER STEPS (not faked here):** an **offsite destination** — the archive currently lands on the
same machine it protects, which is not a backup against disk loss or a compromised host; provision a bucket
and add the upload. The `APP_KEY` and passphrase secret store. And a calendar reminder to run the drill.

---

### 7.2 SCHEDULER / QUEUE LIVENESS ALERTING — ✅ BUILT 2026-08-16 (rank 2)

⚠️ **The failure this detects is silent.** If the §1 crontab line is missing or cron dies, all eight scheduled
commands stop — the **GDPR 14-day guest deletion**, the **30-day ID purge** and **`payouts:run`** among them —
and nothing reports it. No exception, no log line, no symptom until an erasure is legally overdue or an owner
was never paid. §1 documents the crontab; **a runbook cannot detect its own omission.** This closes that.

| Piece | Where | What it proves |
|---|---|---|
| Scheduler heartbeat | `Schedule::call(…)->everyMinute()` | cron is really firing `schedule:run` |
| Queue heartbeat | `QueueHeartbeatJob`, stamped in `handle()` | a **worker** is really running — dispatch alone proves nothing |
| Liveness endpoint | `GET /up/liveness` → **200 / 503** | what an external monitor polls |
| Dead-man's-switch | `->pingOnSuccessIf(OPS_HEARTBEAT_PING_URL)` | survives the whole box dying |

⚠️ **Every minute, deliberately.** All eight real commands run *daily*, so at their cadence a cron that died
at 09:01 would look healthy until tomorrow. Only a minute-resolution beat detects a stall while it still
matters.

⚠️ **Two heartbeats, because they fail separately.** A stalled scheduler with a fresh queue means cron; a
fresh scheduler with a stalled queue means Horizon. One combined signal could not tell an operator which to
go and fix.

⚠️ **The detector must be OUTSIDE the thing it watches.** A check running inside the scheduler cannot report
the scheduler being dead — it simply would not run, which looks exactly like healthy silence. So the app only
*publishes* evidence; the alarm belongs to an external monitor polling `/up/liveness`, and to the
dead-man's-switch, which is the only detector that survives power loss or a crashed host.

⚠️ **503, not a JSON flag.** Uptime monitors alert on a non-2xx; almost none parse a body. Returning 200 with
`{"healthy":false}` would report the outage to nobody.

**Tuning (alert fatigue is itself a failure mode — a muted check restores the silence):** scheduler stale
after **5 min**, queue after **15 min**. A missed beat inside tolerance does not alarm. ⚠️ **A heartbeat that
never existed counts as STALE, not healthy** — a fresh deploy whose crontab was forgotten is the exact case
this exists for.

**Re-run the proof any time:**

```bash
APP_URL=https://<your-domain> ops/liveness-drill.sh   # forces each stall, asserts the alarm; exit 0 = passed
```

⛔ **FOUNDER STEPS — the mechanism is built and proven, but nothing is watching it yet:**
1. Point an **external uptime monitor** at `GET /up/liveness` (alert on non-2xx). Unpolled, it is evidence
   nobody is reading.
2. Optionally set `OPS_HEARTBEAT_PING_URL` (Healthchecks.io / Better Stack / Cronitor) for the
   dead-man's-switch. **Unset, it is inert by design** — no ping is attempted and nothing pretends one was.
3. Decide **on-call routing** — who the alert wakes.

---

### 7.3 THE DEPLOY SCRIPT — ✅ BUILT 2026-08-18 (rank 3)

⚠️ **The risk this closes is not ignorance — §7 named it exactly: *"a skipped or REORDERED step under
pressure."*** §5 above is the same sequence run by hand, and three of its orderings are load-bearing:

| Ordering | Why it matters |
|---|---|
| `migrate` **before** the caches | a config/route cache built against the old schema is stale from the moment it is written |
| `horizon:terminate` **after** the new code is placed | terminate makes workers respawn **into** the new release; run earlier they respawn into the old one |
| the cron verified **before** `up` | its failure mode is **silence** — there is nothing to notice later |

```bash
BACKUP_PASSPHRASE='…' ops/deploy.sh                    # deploy origin HEAD of the current branch
BACKUP_PASSPHRASE='…' ops/deploy.sh --ref v1.2.0       # deploy a specific tag
ops/deploy.sh --dry-run                                # print all 12 steps, execute none (safe anywhere)
ops/deploy.sh --rollback-to <sha>                      # revert CODE to a release
```

**The twelve steps:** pre-flight → **backup** → maintenance mode → fetch code → `composer install --no-dev`
→ `vite build` → **`migrate --force`** → config/route/view/event caches → `horizon:terminate` +
`queue:restart` → **scheduler cron** → `up` → **`/up` health check**.

#### ⚠️ What the script will REFUSE to do

| Guard | Behaviour |
|---|---|
| **No destructive command exists in it** | there is no `migrate:fresh`, `migrate:refresh`, `db:wipe` or seeder on any executable line — verified by inspecting the file with comments stripped |
| **No backup, no deploy** | without `BACKUP_PASSPHRASE` it exits **before** maintenance mode. A migration with no restorable snapshot has no way back |
| `APP_DEBUG=true` + `APP_ENV=production` | **refuses** — that combination leaks stack traces, env values and query contents publicly |
| Empty `APP_KEY` | **refuses** — encrypted guest PII is permanently unreadable without it (§7.4) |
| Unreachable database | **refuses** before touching anything |

⚠️ **It holds no secrets.** Credentials are read from `.env` at run time through the same `env_get` helper
`ops/backup.sh` uses. Secrets management proper is §7.4.

⚠️ **The backup is `ops/backup.sh`, not a dump written inside the deploy** — that is the path
`ops/restore-drill.sh` actually proves. A second dump would be an unproven backup wearing a proven name.

#### ⚠️ Rollback is deliberately ASYMMETRIC

`--rollback-to <sha>` reverts **code** — reinstalling dependencies, rebuilding assets, re-caching and
restarting workers. **It does not restore the database**, and that is a decision, not an omission: restoring
over live production discards everything written since the snapshot. If step 7 ran a migration, a code
rollback leaves the **new** schema in place; the script prints the restore command and stops so a human
chooses. Rehearse restores with `ops/restore-drill.sh` **before** you need one, never during an incident.

#### ⚠️⚠️ STEP 10 — the cron the compliance jobs depend on

`guests:process-deletions` (the **14-day GDPR erasure**) and `guest-documents:purge-expired` (the **30-day ID
purge**) are invoked **only** by `schedule:run`. Without the cron there is **no error, no failed job and no
alert** — erasure requests are accepted and silently never served, and that stays invisible until an audit
asks for evidence. **So the script installs it rather than warning about it:**

```cron
* * * * * cd /path/to/vyrobel && php artisan schedule:run >> /dev/null 2>&1
```

- Runs as **the user that owns the application directory** (the same user Horizon runs as).
- The install is an **idempotent append** — if a `schedule:run` line already exists, it is left alone.
- Opt out with `DEPLOY_NO_CRON_INSTALL=1`; the script then **fails loudly** and prints the line to add.

**Verify it, every time — three independent checks:**

```bash
crontab -l | grep schedule:run          # 1. the line exists, for the right user and path
php artisan schedule:list               # 2. all 9 commands listed with a next-due time
grep -c . storage/logs/schedule-*.log   # 3. evidence it actually FIRED, not merely that it is configured
```

⚠️ **Checks 1 and 2 prove configuration; only check 3 proves execution.** §7.2's heartbeat is what catches
the case where it silently stops later.

#### ⚠️ ENVIRONMENT BRING-UP — the Mailpit dependency (seeded environments ONLY)

**This is not a production step — production never seeds (§4).** It applies to **staging, demo and any fresh
environment that runs the seeder**.

⚠️ **A seeded environment needs a catchable mail transport before you seed.** `DemoSeeder` creates invites and
bookings that **dispatch notifications**; with no reachable transport the seeder **dies mid-run**, leaving a
**half-populated database** and a stack frame pointing into the notification dispatcher — **which reads like a
code bug and is not one.** This cost real debugging time.

```bash
mailpit &                        # SMTP 127.0.0.1:1025 · UI http://127.0.0.1:8025
# — or, if you don't need to read the mail —
MAIL_MAILER=log php artisan migrate:fresh --seed
```

⚠️ **Symptom to recognise:** a seeder failure whose trace ends in the mailer or notification stack, with the
database partly populated. **The fix is the transport, not the code.**

#### The founder's verification order

1. **`ops/deploy.sh --dry-run`** — read all 12 steps; it executes nothing and is safe anywhere.
2. **Staging**, with a real `BACKUP_PASSPHRASE` — full run, then confirm `/up` is 200, Horizon is reachable,
   `failed_jobs` is empty and `schedule:list` shows 8 commands.
3. **Rehearse the rollback on staging** — `--rollback-to <sha>` and confirm the site returns. ⚠️ A rollback
   path first exercised during an outage is a hypothesis.
4. **Production**, only then.

⛔ **This pass wrote and reviewed the script; it did NOT run a deploy.** Verification was `bash -n`, a
full `--dry-run`, and an executable-line inspection proving no destructive command is present.

**Connects to:** §7.4 (secrets — the script reads `.env` today) · §7.5 (a pinned image would replace the
`composer`/`npm` steps) · §7.6 (CI publishing a built artifact would turn step 6 into a fetch).

---

### 7.4 SECRETS — ✅ DOCUMENTED 2026-08-20 (rank 4)

⚠️ **This section contains NO secret values and never will.** It records what each secret is *for*, *where* it
is read, whether production *requires* it, and what breaks *without* it. Every real value is the founder's,
on the founder's infrastructure. `.env.example` carries **placeholders only** — every secret slot in it is
blank by design.

#### ⚠️ 7.4.0 The finding: the template was already adequate — the INVENTORY was what was missing

`.env.example` already holds **85 keys**, grouped, with 60 lines of comment, every secret slot blank, and a
Stripe block that already documents the safety guard and the two distinct webhook endpoints. §7's own gap map
called it *"Present and adequate"* and that survives inspection. **What did not exist was this inventory** —
purpose, read-site, requirement and consequence per secret — and the two custody procedures below.

⚠️ **`BACKUP_PASSPHRASE` is deliberately NOT in `.env.example`, and adding it would be a regression.** See
§7.4.4.

#### 7.4.1 What the deploy pre-flight refuses without — the inventory must agree with this

`ops/deploy.sh` refuses to proceed on exactly four conditions. **All four are reconciled in the table below.**

| Pre-flight check | Line | Behaviour |
|---|---|---|
| `APP_KEY` empty | `:84` | `fail` + **exit 2** — *"Encrypted guest PII is unreadable without it (§7.4)"* |
| `APP_ENV=production` **and** `APP_DEBUG=true` | `:87` | refuses — leaks stack traces, env values and query contents publicly |
| `BACKUP_PASSPHRASE` unset **in the shell env** | `:104` | `fail` — no pre-migration backup can be taken, so a migration would have no way back |
| Database unreachable | `:92` | `php artisan db:monitor --max=1000` → **exit 2** |

✅ **Inventory ↔ pre-flight: AGREE.** No secret the pre-flight checks is missing from the inventory, and no
inventory entry claims a pre-flight guard that does not exist.

#### 7.4.2 THE SECRET INVENTORY

**Legend — consequence:** 🔴 won't boot / unusable · 🟠 boots insecure · 🟡 a feature silently fails · ⛔ the
deploy pre-flight refuses.

| Key | Purpose | Read at | Prod | Consequence if missing / wrong |
|---|---|---|---|---|
| `APP_KEY` | Laravel encryption key — **encrypts `guests.email` / `guests.phone`** | `config/app.php` | **required** | ⛔🔴 pre-flight exits 2. ⚠️ **Lost ⇒ every encrypted guest record is permanently unreadable.** See §7.4.3 |
| `APP_ENV` | environment | `config/app.php` | `production` | 🟠 and ⚠️ **load-bearing for LIVE Stripe** — see §7.4.5 |
| `APP_DEBUG` | debug output | `config/app.php` | `false` | ⛔🟠 `true` + prod is refused by the pre-flight |
| `DB_PASSWORD` · `DB_USERNAME` · `DB_HOST` · `DB_DATABASE` · `DB_PORT` | database credentials | `config/database.php` | **required** | ⛔🔴 pre-flight `db:monitor` fails → exit 2 |
| `REDIS_PASSWORD` (+ `REDIS_HOST`/`PORT`) | cache · sessions · **queue/Horizon** | `config/database.php` | **required** | 🔴 queue stops: emails, payout runs and every scheduled job stop being processed |
| `MAIL_MAILER` | transport selector | `config/mail.php` | **required** | 🟡 defaults to **`log`** — ⚠️ mail silently goes to a logfile and no guest ever receives anything |
| `POSTMARK_TOKEN` | production mail (Postmark) | `config/services.php` | **required** *(with the postmark mailer)* | 🟡 every transactional email fails to send |
| `MAIL_PASSWORD` · `MAIL_USERNAME` | SMTP credentials (dev/Mailpit or an SMTP provider) | `config/mail.php` | only if `MAIL_MAILER=smtp` | 🟡 sends fail |
| `STRIPE_FAKE` | **master switch** for the payment gateway | `config/services.php` | **`false`** | 🟡 while `true` **no other Stripe secret is read at all** — nothing charges. See §7.4.5 |
| `STRIPE_SECRET` · `STRIPE_KEY` | Stripe API keys | `config/services.php` | **required** | 🟡 no charge can be created. ⚠️ Guard interaction in §7.4.5 |
| `STRIPE_WEBHOOK_SECRET` | signature secret for `/webhooks/stripe` | `config/services.php` | **required** | ⚠️⚠️ **THE SILENT FAILURE** — see §7.4.5 |
| `STRIPE_CONNECT_WEBHOOK_SECRET` | signature secret for `/webhooks/stripe/connect` | `config/services.php` | **required for PAYOUTS** | ⚠️ **currently ABSENT from `.env`**. Owner-payout rail only — does **not** block a guest paying a folio |
| `STRIPE_PRICE_*` (×4) | subscription price ids | `config/services.php` | required for billing | 🟡 subscription checkout cannot price a plan |
| `PASSPORT_PRIVATE_KEY` · `PASSPORT_PUBLIC_KEY` | OAuth signing keypair | `config/passport.php` | **required** *(if the owner API is used)* | 🔴 token issue/verify fails. Normally **files** generated per environment by `php artisan passport:keys` (gitignored), not env values |
| `BACKUP_PASSPHRASE` | AES-256 passphrase for the encrypted DB dump | ⚠️ **the shell env**, not `.env` — `ops/backup.sh:73` `openssl … -pass env:BACKUP_PASSPHRASE` | **required at deploy time** | ⛔ pre-flight refuses. ⚠️ **Lost ⇒ every backup is permanently unreadable.** See §7.4.4 |
| `AWS_ACCESS_KEY_ID` · `AWS_SECRET_ACCESS_KEY` (+ bucket/region) | object storage / offsite backup target | `config/filesystems.php` | required *if* S3 is the disk or the offsite target | 🟡 uploads / offsite copies fail |
| `CHANNEX_API_KEY` · `CHANNEX_WEBHOOK_SECRET` | channel manager | `config/services.php` | account-gated | 🟡 `CHANNEX_FAKE` defaults **true**; live sync stays off until set |
| `ANTHROPIC_API_KEY` | LLM features | `config/services.php` | optional | 🟡 `LLM_FAKE` defaults **true** |
| `SLACK_BOT_USER_OAUTH_TOKEN` · `LOG_SLACK_WEBHOOK_URL` | ops alerting | `config/logging.php` | optional | 🟡 alerts do not arrive |

⚠️ **Safe-by-default posture, worth keeping:** `STRIPE_FAKE`, `CHANNEX_FAKE` and `LLM_FAKE` **all default to
`true`**. An environment provisioned with none of those secrets runs harmlessly on fakes rather than
half-integrating with live third parties. Going live is a series of deliberate opt-ins, never an accident of
a missing variable.

⚠️ **The `MAIL_MAILER` trap deserves its own line:** its default is **`log`**. A production box that forgets
it does not error — it writes every guest email to a file and reports success. That is a silent failure of
the same family as the Stripe webhook below.

#### 7.4.3 APP_KEY — CUSTODY PROCEDURE (a procedure, never the key)

**What it protects.** `guests.email` and `guests.phone` are encrypted casts. `APP_KEY` is the only thing that
can read them back.

- **Where it lives.** One of: the platform's secret manager (Fly/Render/Heroku config, AWS Secrets Manager,
  1Password/Bitwarden), or the server's environment. ⚠️ **Never in git, never in a screenshot, never pasted
  into a chat or an issue.** It reaches the server as an environment variable, and `.env` on the box is the
  only file that should ever hold it.
- **Generate** with `php artisan key:generate` **on the target environment**, once, at provisioning.
- **Back it up separately from the database.** ⚠️ A backup encrypted with a key stored *only* alongside that
  database is one incident away from being unreadable.
- **Rotation.** Laravel supports `APP_PREVIOUS_KEYS` (already referenced in `config/app.php`): set the new
  `APP_KEY`, move the old value into `APP_PREVIOUS_KEYS`, re-encrypt, then retire the old one. ⚠️ **Rotating
  without `APP_PREVIOUS_KEYS` orphans every existing encrypted value.**
- **Recovery.** ⚠️ **There is none.** Lost ⇒ encrypted guest PII is permanently unreadable. Leaked ⇒ treat
  every encrypted value as exposed: rotate via `APP_PREVIOUS_KEYS`, then assess disclosure obligations.

#### 7.4.4 BACKUP_PASSPHRASE — ITS HOME (a home, never the value)

⚠️ **It is deliberately NOT in `.env`, and must not be added there.** `ops/deploy.sh:104` reads
`${BACKUP_PASSPHRASE:-}` from the **shell environment**, and `ops/backup.sh:73` passes it to
`openssl enc -aes-256-cbc -pbkdf2 -pass env:BACKUP_PASSPHRASE`. It is an **invocation-time** secret that never
rests in a file on the server:

```bash
BACKUP_PASSPHRASE='…' ops/deploy.sh
```

- **Where it lives.** The founder's password manager, and nowhere on the deploy host.
- **It is the SAME passphrase `ops/backup.sh` uses** — the path `ops/restore-drill.sh` proves. Its custody
  *is* the restore's custody.
- ⚠️ **Losing it makes every encrypted backup permanently unreadable.** A backup you cannot decrypt is not a
  backup. Rehearse with `ops/restore-drill.sh` **before** you need it.
- **Rotation** re-encrypts future archives only. ⚠️ **Old archives remain bound to the old passphrase — keep
  it for as long as you keep them.**

#### ⚠️ 7.4.5 THE STRIPE GROUP — verified, with exact consequences

Verified at source by `docs/audits/STRIPE-CAPTURE-CONFIRM-2026-08-20.md` and the probe runbook's (A) bracket.

**`STRIPE_FAKE` is the master switch.** `StripeSafetyGuard::forceFakeOutsideProduction()` returns early on it:
while `true`, **no other Stripe secret is even read** and `FakeStripeClient` is bound. Production: `false`.

**The guard keys on the KEY PREFIX, not on "any key off-production":**

```php
isLiveKey($s) => str_starts_with($s,'sk_live_') || str_starts_with($s,'rk_live_')
forceFake     => $configuredFake || ($environment !== 'production' && isLiveKey($secret))
```

- ⚠️ **`APP_ENV=production` is load-bearing for LIVE keys specifically.** A `sk_live_` key anywhere else is
  **refused** — the fake binds and **nothing charges**. This is why the go-live checklist requires it.
- ⚠️ **A `sk_test_` key does NOT require it** — test keys are permitted off-production by design, for the
  manual live-test checkpoint.

**⚠️⚠️ `STRIPE_WEBHOOK_SECRET` — THE ONE THAT FAILS SILENTLY.** Fulfilment happens on the webhook, never on
the redirect: `'payment_intent.succeeded' => FolioService::settlePayment()`. If the secret is wrong, or **the
live endpoint was never registered**, then:

> **The guest is charged by Stripe and the folio never settles — money taken, balance still showing.**
> Everything else in this inventory fails loudly. This one does not.

**The paired non-secret step (a go-live checklist item, not just a variable):** register the endpoint in the
Stripe dashboard at `POST https://<host>/webhooks/stripe`, subscribed to **`payment_intent.succeeded`**,
`payment_intent.payment_failed` and `payment_intent.canceled`.

**`STRIPE_CONNECT_WEBHOOK_SECRET` — absent today.** It signs `/webhooks/stripe/connect`, a **separate**
endpoint with its **own** secret, serving the **owner-payout Connect rail**. Required before payouts go live;
it does **not** affect a guest paying a folio.

#### 7.4.5b ⚠️ `APP_URL` — promoted to load-bearing by the front website (Gate 1, 2026-08-21)

`APP_URL` was cosmetic for the app realms (links in emails aside). ⚠️ **The marketing site's SEO derives
every `<link rel="canonical">` and `og:url` from it** (`<x-marketing.seo>` → `url()`), so a wrong `APP_URL`
in production emits wrong canonicals on every marketing page — a silent SEO failure (nothing errors; Google
just indexes the wrong origin). **Go-live: `APP_URL=https://vyrobel.com` exactly, before the marketing
routes are registered.**

#### 7.4.6 Provisioning, in one line

Copy `.env.example` → `.env`, then fill in: `APP_KEY` (generate) · the five `DB_*` · `REDIS_PASSWORD` ·
`MAIL_MAILER` + `POSTMARK_TOKEN` · the Stripe group · `AWS_*` if used. Export `BACKUP_PASSPHRASE` at deploy
time. Run `ops/deploy.sh --dry-run` first — it prints all twelve steps and executes none.


---

### 7.5 RUNTIME — ✅ DOCUMENTED 2026-08-20 (rank 5)

⚠️ **The deploy is server PULL-IN-PLACE + maintenance mode (§7.3), not containers.** So this is the contract
the **host** must satisfy — what must be installed and configured for the app to boot and run correctly — not
a production image. Any Dockerfile here would be dev/CI parity only (§7.5.5).

⚠️ **Every line below is derived from source and cited.** A requirement *claimed but never exercised* is as
wrong as one *exercised but undocumented* — §N.29 applied to a runtime doc: assert the requirement **runs**,
not that it is named.

#### ⚠️ 7.5.1 THREE FINDINGS THIS DERIVATION PRODUCED

**(a) ⚠️ `REDIS_CLIENT` defaults to `phpredis`, which needs an extension nothing declares.**

| Fact | Source |
|---|---|
| The config default is **`phpredis`** | `config/database.php:148` — `env('REDIS_CLIENT', 'phpredis')` |
| `phpredis` is the **C extension** `ext-redis` | — |
| `composer.json` ships **`predis/predis ^3.5`** — the *pure-PHP* client, needing no extension | `composer.json` require |
| `.env.example` correctly advises **`REDIS_CLIENT=predis`** | `.env.example:50` |

> ⚠️ **A production `.env` that omits `REDIS_CLIENT` falls back to `phpredis` and needs `ext-redis`.** Because
> that extension is not in `composer.json`, **`composer check-platform-reqs` cannot catch it** — the very
> pre-flight §0 recommends is blind here. The failure surfaces as the queue refusing to connect: no emails,
> no payout runs, no scheduled work.

**Resolution — one of two, both safe:** set `REDIS_CLIENT=predis` explicitly in the production `.env` (what
`.env.example` already advises, needs no extension), **or** install `ext-redis` and keep the default.
⚠️ Do not leave it unset and uninstalled. It has never bitten here only because this dev machine happens to
have `ext-redis` installed — an instrument masking the default.

**(b) ✅ `ext-intl` is OPTIONAL — a nicety, never a requirement.** Money does **not** need it: it is formatted
with plain `number_format` (`app/Support/Money/MoneyFormatter.php:17`), and there is no `NumberFormatter`,
`IntlDateFormatter` or `Collator` anywhere in `app/`.

⚠️ **But it is not unused, and my first pass said it was.** There is exactly one call —
`\Locale::getDisplayRegion()` at `app/Livewire/Operator/Concerns/FormatsOperatorData.php:129`, turning a
guest's `IE` into `Ireland` on the Guests CRM. It is **guarded** by `extension_loaded('intl')` at `:128` with
a real fallback: without the extension the page shows the true alpha-2 code instead of the prettier name.
Both outputs are correct; one is nicer. The code says so itself at `:112`.

> ⚠️ **The correction is the point (§N.29).** My first probe searched for `NumberFormatter`, `IntlDateFormatter`
> and `Collator` — a needle set drawn from what I *expected* intl to be used for. `Locale` was not in it, so
> the search returned nothing and "unused" looked proven. **A grep proves the absence of its needles, not the
> absence of the thing.** It is installed on this machine, so nothing would have contradicted it either.

**Decision: do not declare it.** A hard requirement to gild one display label is not a trade worth making —
the same reasoning §0 applied to `ext-gd`. Install it if you want country names spelled out; the app is
correct without it.

**(c) ⚠️ Horizon needs `ext-pcntl` + `ext-posix` — and `disable_functions` can defeat them invisibly.**

`vendor/laravel/horizon/composer.json` requires **`ext-pcntl`** and **`ext-posix`**. Because they *are*
declared, `composer check-platform-reqs` catches a **missing** extension — so far so good.

> ⚠️ **What it cannot catch: a loaded extension whose functions are disabled.** Shared and hardened hosts
> routinely put `pcntl_fork`, `pcntl_signal`, `posix_kill` or `proc_open` in php.ini's **`disable_functions`**.
> `extension_loaded('pcntl')` still returns **true**, Composer still reports success, and Horizon still
> *starts* — then cannot fork or signal its workers. This machine has `disable_functions` empty, so nothing
> here would ever have shown it.

**Verify the functions, not the extension** (§7.5.6 does this):

```bash
php -r 'var_dump(function_exists("pcntl_fork"), function_exists("posix_kill"), function_exists("proc_open"));'
php -r 'echo "disable_functions=[", ini_get("disable_functions"), "]", PHP_EOL;'
```

All three must be `true` and the list must not name them. ⚠️ If the host refuses to allow them, Horizon
cannot run there — and with it go the queued emails, the payout runs, and every scheduled job.

#### 7.5.2 PHP — version and extensions, each cited to a usage site

| Requirement | Source of the constraint | Exercised by |
|---|---|---|
| **PHP ≥ 8.4.1** | `composer.json` → `"php": "^8.4.1"` | the whole graph; `openspout` needs `~8.4.0\|~8.5.0`, `symfony/*` v8.1 needs `>= 8.4.1` (§0) |
| **`ext-zip`** | declared: `composer.json` → `"ext-zip": "*"` | ✅ `app/Http/Controllers/Owner/OwnerArchiveZipController.php` (`ZipArchive`) — **and** required transitively by `openspout` |
| `ext-mbstring` | framework baseline | ✅ `app/Models/Guest.php`, `app/Livewire/Owner/OwnerSettings.php` (`mb_str*`). ⚠️ Composer reports it *"provided by symfony/polyfill-mbstring"* — the app works without the C extension, but the polyfill is markedly slower; install the real one |
| `ext-json` | bundled with PHP ≥ 8 | ✅ used throughout; 34 migrations declare `->json()` columns |
| `ext-openssl` | framework baseline | ✅ `Crypt` (encrypted guest PII + encrypted ID blobs); `ops/backup.sh:73` shells to the `openssl` **binary** — a separate host requirement |
| `pdo_mysql` | `config/database.php` (`DB_CONNECTION=mysql`) | ✅ every query |
| ⚠️ **`ext-pcntl` · `ext-posix`** | `laravel/horizon` require | ✅ Horizon's supervisor forks/signals workers — see §7.5.1(c) for the `disable_functions` trap |
| `ext-sodium` | `firebase/php-jwt` + `lcobucci/jwt` (via **Passport**) | ✅ OAuth token signing (§N — the OAuth server) |
| `ext-curl` · `ext-fileinfo` | framework / SDK baseline | ✅ the Stripe + Postmark HTTP clients; upload MIME detection |
| `ext-iconv` · `ext-hash` · `ext-pcre` · `ext-session` · `ext-ctype` | transitive (`openspout`, `bacon-qr-code`, framework) | Composer enforces each |
| `ext-dom` · `ext-filter` · `ext-libxml` · `ext-xmlreader` | required transitively by `openspout`; Composer enforces them | (§0) |
| ⚠️ `ext-redis` | **only if** `REDIS_CLIENT` is left at its `phpredis` default | see §7.5.1(a) — **not** in composer, so unchecked by the pre-flight |

**Deliberately NOT requirements:**

- ⚠️ **`ext-intl`** — **optional, not unused** (§7.5.1(b)): one guarded `\Locale` call with a correct fallback.
- ⚠️ **`ext-gd`** — §0 already establishes this: its only caller is `PlaceholderImage`, reached solely by
  `DemoSeeder` and a test, and **production is never seeded (§4)**. That reasoning survives re-checking.
- **`ext-bcmath`** — no `bc*` call exists in `app/`. Money is **integer minor units**, so arbitrary-precision
  arithmetic is never needed. That is the money design working: no float, no bcmath.

#### 7.5.3 Services

| Service | Floor | What pins it |
|---|---|---|
| **MySQL 8 (InnoDB), genuine — not MariaDB** | 8.x | `SELECT … FOR UPDATE` row locks on the no-double-booking and money paths (`AssignmentService`, `FolioService`, `GuestWalletService`, and the payout double-transfer guard in `OwnerPayoutService`); a **generated column** (`2026_08_21_000000_add_unique_identity_to_guest_waitlist_entries.php`); 34 migrations with `->json()` columns |
| **Redis** | any current | cache · sessions · **queue + Horizon**. ⚠️ Client selection: §7.5.1(a) |
| **Horizon (long-running)** | — | `config/horizon.php:200` `supervisor-1` on the `redis` connection, queues `['default','sync']`; production `maxProcesses: 10` (`:225`). The host must keep it running under **systemd or Supervisor** — §2 has the block |
| **The `schedule:run` cron** | every minute | ⚠️⚠️ **LOAD-BEARING — see below** |
| Node/npm | build only | `npm ci && npm run build`; **not needed at runtime** (§0) |
| `openssl` **binary** | — | `ops/backup.sh:73` — `openssl enc -aes-256-cbc -pbkdf2`; without it there is no encrypted backup and the deploy pre-flight has nothing to take |

⚠️⚠️ **THE CRON IS NOT OPTIONAL.** All **eight** scheduled commands run *only* via `schedule:run`
(`routes/console.php`), and every one of them fires on a wall-clock time — so the host's **server timezone**
is part of this contract too:

| Time | Command | `routes/console.php` |
|---|---|---|
| 02:00 | `properties:process-removals` | `:36` |
| **03:00** | ⚠️ **`guests:process-deletions`** — the GDPR 14-day erasure | `:40` |
| **03:30** | ⚠️ **`guest-documents:purge-expired`** — the 30-day ID purge | `:45` |
| 06:00 | `payouts:run` | `:33` |
| 09:00 | `emails:send-payment-reminders` | `:19` |
| 09:00 | `emails:send-pre-arrival` | `:24` |
| daily | `invites:prune-expired` | `:15` |
| daily | `platform:prune-activity` | `:28` |

> **The last two are the GDPR 14-day erasure and the 30-day ID purge. Without the cron there is no error, no
> failed job and no alert — erasure requests are accepted and silently never served.** §7.3's deploy script
> installs the entry for exactly this reason, and §1 documents the three independent checks (only the third,
> reading `storage/logs/schedule-*.log`, proves it actually *fired*).

#### 7.5.4 PHP configuration the app actually assumes

Derived from real paths; anything the app does not need is omitted rather than listed for completeness.

| Setting | Value | Why — cited |
|---|---|---|
| `upload_max_filesize` | **≥ 12M** | The largest accepted upload is **10 MB** — `OwnerMessageActionController:38` `ATTACH_MAX_KB = 10240`. Others: guest ID photo 8 MB (`GuestCheckinController:41`), unit-request photo and room-type photo 5 MB, avatar 4 MB. ⚠️ PHP's default **2M silently truncates**, and Laravel then reports a validation error that looks like the guest's fault |
| `post_max_size` | **≥ 14M** | must exceed `upload_max_filesize` plus the rest of the form |
| `memory_limit` | **≥ 256M** | ⚠️ **The ID-document path is the driver:** `app/Services/Guest/GuestIdDocumentService.php:63` does `Crypt::encryptString((string) $file->get())` — the whole file is read into memory **and** its base64 ciphertext (~1.37×) held alongside it. An 8 MB ID is ~20 MB transient before framework overhead. The test suite already needs `512M` (`phpunit.xml:27`) |
| `opcache.enable` | **1** | Performance only, not correctness — but a production PHP without it re-parses every file on every request |
| `max_execution_time` | default is fine | No long-running **web** path: exports and PDFs are queued, and Horizon workers are CLI (no limit) |

#### 7.5.5 A parity image — deferred to §7.6, deliberately

⚠️ **No Dockerfile is written here.** A parity image only earns its place when something consumes it, and
that consumer is CI (§7.6). Writing one now would create a second declaration of the runtime with nothing
exercising it — a copy free to drift from this contract, which is worse than none (§N.8f).

When §7.6 lands, its image must match **this** section exactly: PHP **8.4.1+**, `ext-zip`, `pdo_mysql`, the
`openspout` XML set, MySQL **8**, Redis, and `REDIS_CLIENT=predis` (or `ext-redis`). ⚠️ And CI must pin
`STRIPE_FAKE=true` — `phpunit.xml:49` already does.

#### 7.5.6 Verifying the contract on the target host

```bash
php -v                                   # ≥ 8.4.1
composer check-platform-reqs --no-dev    # every line "success"
php -m | grep -E '^(zip|mbstring|openssl|pdo_mysql|pcntl|posix|sodium|curl|fileinfo)$'
# ⚠️ Horizon: the FUNCTIONS, not just the extension (§7.5.1c)
php -r 'var_dump(function_exists("pcntl_fork"), function_exists("posix_kill"), function_exists("proc_open"));'
php -r 'echo "disable_functions=[", ini_get("disable_functions"), "]", PHP_EOL;'
php -r 'echo ini_get("upload_max_filesize"), " ", ini_get("post_max_size"), " ", ini_get("memory_limit"), PHP_EOL;'
mysql --version                          # genuine MySQL 8.x, not MariaDB
redis-cli ping                           # PONG
grep -E '^REDIS_CLIENT=' .env            # predis, or install ext-redis
crontab -l | grep schedule:run           # the entry exists
```

⚠️ **`composer check-platform-reqs` is necessary but not sufficient.** It validates only what a
`composer.json` **declares**, so it is blind to both traps above: the undeclared `ext-redis` (§7.5.1a) and a
declared-but-**function-disabled** `pcntl`/`posix` (§7.5.1c). Both report clean and both break the queue. That
is why those lines are in this list and not left to the pre-flight.

---


---

### 7.6 CI — ✅ BUILT 2026-08-20 (rank 6) · **the deploy track is COMPLETE**

> ⚠️⚠️ **CORRECTION, 2026-10-06 (the GitHub and memory pass): on GitHub this pipeline never ran a gate until 2026-10-06.**
> 🔬 `gh run list`: 106 runs from 2026-08-20 to 2026-10-04 — 104 failures, 2 cancelled, 0 passes — and every one stopped at
> "Prepare the environment" with `set: Illegal option -o pipefail`: inside a job container GitHub runs `run:` steps with `sh -e`
> (dash), and the workflow writes bash. The proofs in §7.6.1 were made BY HAND on this machine; the workflow itself never reached
> them. Fixed 2026-10-06 (`defaults.run.shell: bash` on the gates job); the first run able to reach the gates, and its result, are in
> `docs/memory/HISTORY.md` ("GITHUB AND MEMORY"). ⚠️ Found with it: the container runs as **root**, which ignores folder
> permissions — a fixture that refuses a write or a delete with a read-only folder refuses nothing there; two were rebuilt to refuse
> by structure. The first full runs found more, all fixed the same day: a `set_time_limit(300)` that lowered the test process's
> "unlimited"; concurrency workers hardcoded to `127.0.0.1`; Passport keys never generated; `.env.example` wrong for a new
> environment (`APP_NAME=Laravel`, and empty `SESSION_SECURE_COOKIE=` / `FORCE_HTTPS=` lines that turned the production defaults
> off); production-path billing tests that built the real Stripe client; country-name tests that assumed `ext-intl`.
> ✅ **Green end to end since run 37503697885 (2026-10-06, `693b68fc`) — the first in the pipeline's history.**

⚠️ **What this closes.** Every gate in this project has been run **by hand** and reported in prose: the full
suite, PHPStan L5, Pint, and the money-as-seeded re-measure. That worked because one person ran them every
time. It is not a control — it is a habit, and a habit has no failure mode that announces itself. §7.6 makes
the four gates run on **every push and every pull request**, on a runtime that matches what §7.5 promises
production will be.

**The founder chose a live workflow** (`.github/workflows/ci.yml`) over a documented-only spec, so the
pipeline is executable, not descriptive.

#### 7.6.1 The four gates — and the proof each one can FAIL

⚠️ **§N.29 applied to CI.** A step that reports green *without running its gate* is the runtime form of the
label-vs-protection failure this project has been bitten by repeatedly. So before this workflow was
committed, each gate was **violated on purpose** and observed to red:

| Gate | Command | Proven to fail by | Result |
|---|---|---|---|
| **0 · runtime** | `ops/runtime-contract.sh --services` | running it on this dev machine | ❌ **3 real failures** — see §7.6.5 |
| **1 · Pint** | `./vendor/bin/pint --test` | planting a malformed class in `app/` | exit **1** → removed → exit **0** |
| **2 · PHPStan L5** | `./vendor/bin/phpstan analyse` | planting a call to an undefined method | exit **1** (`method.notFound`) → removed → exit **0** |
| **3 · the suite** | `php artisan test` | reverting the flake fix (§7.6.3) | **1 failed, deterministically** → restored → 6/6 |
| **4 · money-as-seeded** | `ops/money-as-seeded.sh` | moving the baseline to 17 payments | exit **1** with both figures printed → restored → exit **0** |

⚠️ **Nowhere in the workflow is there a `|| true`, a `continue-on-error`, a swallowed exit code, or a
narrowed test filter.** Gate 3 runs `php artisan test` with **no** `--filter`, `--group` or `--parallel` —
a narrowed run is precisely the shape of a green that did not test what it claimed. (Parallel is excluded
for a second reason: the workers share one database and corrupt each other's fixtures.)

#### 7.6.2 ⚠️ The runtime is CONSUMED from §7.5, not restated

A CI runtime free to drift from §7.5 tests against a machine production does not have — the drift §N.8f
exists to prevent. Three files, one direction of authority:

| File | Role |
|---|---|
| **§7.5** (this document) | the contract, in prose — the human-readable authority |
| **`ops/runtime-contract.sh`** | ⚠️ the **executable** form. Asserts the contract against a live machine and exits non-zero when it is not met |
| **`Dockerfile.ci`** | the parity image. **Installs**; it does not define. Gate 0 runs the script *inside* it, so an image that drifts reds the build |

⚠️ It checks the two things `composer check-platform-reqs` **cannot** see — precisely the traps §7.5.1
recorded: **(a)** the `REDIS_CLIENT`/`phpredis` default needing an extension no `composer.json` declares,
and **(c)** `pcntl`/`posix` **functions** neutered by `disable_functions` while the extension still reports
loaded. It also reads the live MySQL banner and **reds on a MariaDB string**, so "genuine MySQL 8" is
enforced rather than requested.

> ⚠️ **One honest residue.** §7.5.6 lists the same verification commands in prose, and this pass was scoped
> not to edit §7.5. So there are two expressions of that check-list — the script (enforced) and §7.5.6
> (read). They agree today. **A one-line pointer from §7.5.6 to `ops/runtime-contract.sh` would collapse
> them; it is a founder's call to make, and it is the only §N.8f duplication §7.6 leaves behind.**

#### 7.6.3 ⚠️ The `OperatorPortfolioTest` flake — and the correction that came with it

**The register called it "order-dependent" for a year. It is not.** The diagnosis, from source:

`seedScenario` codes its units `U1`, `U2`… ([tests/Support/booking_helpers.php:50](../tests/Support/booking_helpers.php#L50)),
and the *"add → select → book"* test asserted `assertDontSee('U1')` on the new empty property's units page.
Every page carries a 40-character random CSRF token, and `Str::random(40)` is alphanumeric — so it contains
the two-character string `U1` **about 1% of the time**, twice per page.

**Measured, not assumed: 212 hits in 20,000 draws = 1.06%** — which matches the 0.95% this repo had already
computed the last time it hit this. `assertDontSee('U1')` was therefore failing on a page that had leaked
**nothing whatsoever**: the assertion was testing the **needle**, not the behaviour.

> ⚠️ **It is randomness-dependent, not order-dependent — the token is redrawn every run regardless of test
> order.** The distinction matters: an ordering fix (deterministic sort, isolated fixtures) would have
> changed nothing at all here, and the test would have kept flaking with a "fix" recorded against it.

⚠️⚠️ **And this exact flake was diagnosed and fixed twice before, in this repo, on 2026-07-17.**
`OperatorReservationsListTest:86` renames its foreign unit to `FOREIGNUNIT9`;
`BackOfficeAccessTest:132` renames to `BRUNOUNIT9` and carries the full arithmetic in a comment.
`OperatorPortfolioTest` is simply the site that sweep missed — *the defect sat beside the flagged thing*
(§N.13.1), one file over, for a year.

**The fix reuses that established remedy rather than inventing a new one**, and adds two things:

1. **A leak-proof needle** — `ORIGINALUNIT9`, a string that could only appear on the page **by leaking**.
2. **The positive first** (§N.29) — the test now asserts the code *does* render on the original property
   before asserting it does not on the new one. Without that, a needle that can never appear would pass
   forever while guarding nothing.
3. ⚠️ **The worst case is PINNED, not left to chance.** The negative assertion runs with a CSRF token
   deliberately containing `U1`. The 1-in-50 collision now happens on **every single run**: a leak-proof
   needle sails through it, and a short one reds **immediately and deterministically** instead of flaking
   months later. *The protection RUNS — it is not merely described in a comment.*

**Mutation proof:** with the adversarial token pinned, reverting the needle to `U1` fails the test
**1/6, every time**; restoring it passes **6/6**. Assertions went **29 → 31** — two added, none removed.
⚠️ **No product code was touched.** This was a fixture and needle defect, never an ordering bug.

#### 7.6.4 The parity image — and the one place it deliberately exceeds §7.5

`Dockerfile.ci` is **dev/CI only and must never be deployed** — the production path is pull-in-place (§7.3),
with no container in it. It carries no application code, no `.env`, and no secrets; CI checks the repo out
and mounts it.

⚠️ **It installs one extension §7.5 excludes: `ext-gd`.** That is not drift, it is a real asymmetry worth
stating plainly. §7.5.2 excludes gd from *production* on sound reasoning: its only caller is
`PlaceholderImage`, reached solely by `DemoSeeder` and one test, and production is never seeded. But that
"one test" is [`tests/Feature/RoomPhotosTest.php:79`](../tests/Feature/RoomPhotosTest.php#L79), which calls
`PlaceholderImage::jpeg()` and **throws without gd**. So the **test suite** needs what production does not.
The contract script prints gd under a separate **CI-ONLY** heading (`CI_IMAGE=1`) so the two sets can never
quietly blur into one.

⚠️ **`ext-intl` is deliberately NOT installed** (§7.5.1b). CI therefore runs the way a host without it runs,
so any future code that starts depending on `intl` **unguarded** fails here rather than in production.

⚠️ **Status: written, not yet built.** Docker is not installed on the development machine, so the image has
never been built or run. **Gate 0 is what verifies it** — the first CI run either satisfies the contract or
reds with the exact requirement it missed. It is not assumed correct.

#### 7.6.5 ⚠️ Gate 0 found something on its first run — on this machine

Executing `ops/runtime-contract.sh` locally **failed three checks**, and they are true:

| Setting | This machine | §7.5.4 requires |
|---|---|---|
| `upload_max_filesize` | **2M** | ≥ 12M |
| `post_max_size` | **8M** | ≥ 14M |
| `memory_limit` | **128M** | ≥ 256M |

`php artisan serve` runs on the **CLI** SAPI, so these are the limits local development actually has.

> ⚠️ **Consequence: the 8 MB guest ID upload and the 10 MB message attachment cannot be exercised on this
> machine at all** — PHP rejects the file before Laravel ever sees it. Those paths have only ever been
> tested below 2 MB. The fix is `/opt/homebrew/etc/php/8.4/php.ini` (or a `conf.d` drop-in mirroring
> `Dockerfile.ci`'s `zz-vyrobel-contract.ini`); it is a **local environment** change, so it is the
> founder's to make. The parity image already sets all three correctly.

#### 7.6.7 Verification of this pass

| | |
|---|---|
| Suite | **2,524 tests · 2,522 passed · 2 skipped · 0 failed** · 16,075 assertions (exit 0) |
| PHPStan | L5, **0 errors** |
| Pint | clean |
| Money as seeded | `929 / 10,912,000 / 0 / 41 / 16 / 0` — unchanged |
| `OperatorPortfolioTest` | 6/6, assertions **29 → 31** (two added, none removed) |
| Product code | ⚠️ **none changed** — the flake was a fixture-and-needle defect, not an ordering bug |

⚠️ **The documented non-green band did not appear in this run:** the whole suite was green. That is one
observation, not a proof the band is gone — but it is the run this pass was verified against, reported as it
happened.

#### 7.6.6 Cost, and what runs when

| | |
|---|---|
| **Triggers** | every push on any branch · every pull request · manual (`workflow_dispatch`) |
| **Concurrency** | superseded runs on the same ref are cancelled — pushing twice does not pay twice |
| **The image** | rebuilt **only when `Dockerfile.ci` changes** (the tag is its content hash) and pushed to GHCR; every other run pulls the identical published runtime |
| **Roughly** | a full run is the suite (~2,190 tests) plus a seed — expect **8–15 minutes** of billed private-repo minutes per push |
| **Secrets** | ⚠️ **none.** CI holds no Stripe key, no mail credential, no `APP_KEY` of consequence — `phpunit.xml:49` pins `STRIPE_FAKE=true` and Gate 0 asserts that pin still exists (#45) |

### 7.7 HOST PINNING — ✅ BUILT 2026-08-30 (audit H-1)

**The defect.** `route()`/`url()` build an absolute URL from the **request** host unless a root URL is
forced, and password-reset mail is rendered **synchronously inside the request**. A poisoned `Host` therefore
put the attacker's domain into the victim's email — carrying a valid reset **token**.

Reproduced against the running app, both ways:

```
Host: attacker.example          -> http://attacker.example/reset-password/<valid token>
X-Forwarded-Host: evil.example  -> http://evil.example/reset-password/<valid token>
```

The second needs `TRUSTED_PROXIES=*`, which `config/security.php` recommends behind a load balancer — i.e.
the normal production posture. The first needed **nothing**: it worked with proxy trust empty.

**What changed in code.**
1. `AppServiceProvider` forces the root URL from `APP_URL`, so every generated URL is independent of the
   request. **This is the fix.** It is guarded: with no usable host in `APP_URL` it does nothing, because
   forcing a root URL from an unset config would break every link in the product.
2. `bootstrap/app.php` pins `trustHosts()` to the `APP_URL` host, so an unexpected `Host` is **refused**
   rather than silently served. **Defence in depth, not the fix** — trusted hosts bound *which* hosts may be
   supplied; forcing the root URL removes the request from the decision entirely.

⚠️ **The web server still matters.** Laravel refuses the request, but a vhost that answers any `Host` still
lets it reach PHP. Pin `server_name` and add a `default_server` that returns 444 (nginx) or a refusing
default vhost (Apache).

**VERIFY AFTER EVERY DEPLOY** — from the server, against the live app:

```bash
# 1. The canonical host is served.
curl -s -o /dev/null -w '%{http_code}\n' -H "Host: $CANONICAL_HOST" https://127.0.0.1/login --resolve "$CANONICAL_HOST:443:127.0.0.1"
#    EXPECT: 200

# 2. An unexpected Host is REFUSED — not served.
curl -s -o /dev/null -w '%{http_code}\n' -H 'Host: attacker.example' https://127.0.0.1/login -k
#    EXPECT: 400 (Laravel's trusted-host rejection) or 444/403 from the web server.
#    ⚠️ A 200 here means BOTH controls are off. Do not go live.

# 3. The emailed link is built from APP_URL, not the request.
#    Trigger one password reset for a mailbox you control, WITH a wrong Host, and read the link:
curl -s -X POST https://127.0.0.1/forgot-password -H 'Host: attacker.example' -k \
     -d "_token=$CSRF" -d 'email=you@yourdomain'
#    EXPECT: the link in the received mail begins with APP_URL. If it begins with attacker.example,
#    the root URL is not being forced — check APP_URL is set and absolute.
```

⚠️ **`APP_URL` is now load-bearing for security, not just for links.** `ops/deploy.sh` pre-flight refuses an
empty or relative `APP_URL` for this reason.

### Ranked by harm if missing at launch — the recommended build order

Each of these is **its own pass**; they are deliberately not built together.

1. ✅ **Backups + a proven restore — BUILT 2026-08-16, see §7.1 above.** ⚠️ The only *unrecoverable* failure on the list. Everything else can be
   fixed after the fact; lost tenant data, folios and owner ledgers cannot. ⚠️ A backup nobody has restored
   is a hypothesis, not a backup — the pass must include an actual restore into a scratch database.
2. ✅ **Scheduler + queue liveness alerting — BUILT 2026-08-16, see §7.2 above.** §1 documents the cron correctly, but **its failure mode is
   silence** — no error, no alert, and GDPR erasure requests are accepted and never served while
   `payouts:run` also quietly stops. Documentation cannot detect its own omission; a heartbeat can.
3. ✅ **A deploy script — BUILT 2026-08-18, see §7.3 above** (`ops/deploy.sh`). §5 is order-sensitive
   (migrate before caching, `horizon:terminate` after) and was executed by hand. The risk was a skipped or
   reordered step under pressure, not ignorance — the script encodes the order and refuses unsafe states.
4. ✅ **Secrets management — DOCUMENTED 2026-08-20, see §7.4 above.** The inventory (every secret: purpose ·
   read-site · prod-required · consequence), the APP_KEY custody procedure and the BACKUP_PASSPHRASE home,
   reconciled to exactly what the pre-flight refuses without. ⚠️ Moving credentials off the filesystem into a
   managed secret store is the remaining *infrastructure* half and is the founder's platform choice.
5. ✅ **Runtime contract — DOCUMENTED 2026-08-20, see §7.5 above.** Derived from source and cited: the PHP
   floor and extensions (each tied to a usage site), the services, the load-bearing cron, and the PHP ini the
   app actually assumes. ⚠️ It surfaced a real gap the §0 pre-flight cannot see — the `REDIS_CLIENT`
   `phpredis` default needing an undeclared `ext-redis` (§7.5.1a). A **pinned parity image is deferred to
   §7.6**, where CI will consume it.
6. ✅ **CI pipeline — BUILT 2026-08-20, see §7.6 above.** ⚠️ (2026-10-06: it never ran a gate on GitHub until then — the correction at §7.6.) `.github/workflows/ci.yml` runs all four gates —
   the full suite · PHPStan L5 · Pint · money-as-seeded — on every push and PR, **inside the §7.5 parity
   image**, with each gate individually proven to red the build (§N.29). The runtime is *consumed* from
   §7.5 via `ops/runtime-contract.sh`, not restated (§N.8f). ⚠️ The suite runs **alone** — no `--parallel`
   (the workers share one database) and no filter. The `OperatorPortfolioTest` flake is fixed and
   mutation-confirmed (§7.6.3 — it was never order-dependent).

⚠️ **All six are now built. THE DEPLOY TRACK IS COMPLETE.** What remains before launch is founder
infrastructure and the founder's own cutovers, not engineering work — see the note below.

⚠️ **Not on this list, because they are the founder's calls, not engineering gaps:** the Stripe live-key flip
and Connect KYC, the payout-engine go-live flip, `passport:keys` per environment, `tax:set-rate` per
jurisdiction, and the VAT-validation fork (send-as-is vs VIES-first). See §3 and the linked production docs.

---

### 7.8 THE HOST ITSELF — ⚠️⚠️ FOUR FACTS EVERY EARLIER SECTION GOT WRONG OR NEVER KNEW (2026-09-23)

#### 7.8.1 ⚠️⚠️ IT IS **LiteSpeed**, NOT APACHE

🔬 Measured: the origin answers `server: LiteSpeed`. It reads `.htaccess` in **compatibility mode** — which
is why every rule in `ops/reference/public_html.htaccess` works — but it is **not Apache**, and its edge
cases are its own. ⚠️ **Every section above this one was written assuming Apache.** If you are debugging a
rewrite, an auth directive or a redirect and the Apache manual says it should work, that is not evidence.

🔬 Two behaviours already observed that Apache documentation would not have predicted:
- A trailing-slash path is **301'd before authentication runs** (`/webhooks/` → `/current/public/webhooks`),
  so a lock cannot refuse it — ⚠️ but the redirect **target** is refused, which is what actually matters.
- `WWW-Authenticate` is emitted on **200** responses, not only on 401s. ⚠️ See 7.8.4.

#### 7.8.2 ⚠️⚠️ TWO PRODUCTS SHARE THIS HOSTING ACCOUNT — **`ps` IS AMBIGUOUS EVIDENCE FOREVER**

`hrmzy.com` is a second Laravel application on the same account, with its own scheduler and **its own
`queue:work`**. 🔬 A session on 2026-09-23 found it, mistook it for Vyrobel's, and nearly overturned a
correct record.

**THE DISCRIMINATOR IS THE PHP BINARY, AND IT IS RELIABLE:**

| | Vyrobel | hrmzy |
|---|---|---|
| binary | `/opt/alt/php84/usr/bin/php` | `/opt/alt/php83/usr/bin/php` |
| command | `queue:work --stop-when-empty --max-time=55` | `queue:work database --tries=3 --stop-when-empty` |
| composer floor | `^8.4.1` — 🔬 **artisan exits 255 under 8.3** | `^8.1` — runs happily on 8.3 |
| cron log | `~/.logs/cronjob_r11hhxmHpy` | `~/.logs/cronjob_3RZtQmADsE` |

⚠️ **NEVER grep `ps` for a bare `queue:work` on this host.** Always pin `php84`, or read the
`CRONJOBID:` comment that the panel leaves on the command line. ⚠️ `ops/ps-check.sh` exists so `ps | grep`
cannot match its **own** command line — it cannot tell you **whose** application a correctly-identified
process belongs to. **A correctly-identified process on a shared account is still somebody else's.**

⚠️ **AND THE PANEL CRONS ARE READABLE AFTER ALL** — not via `crontab` (which exits 127 here), but off the
process's own `ps` line during its execution window. The full Vyrobel worker entry is:

```
/bin/sh -c /usr/bin/flock -w 1 /tmp/cron_lock_r11hhxmHpy timeout -s 9 1800 \
  /opt/alt/php84/usr/bin/php ~/domains/vyrobel.com/public_html/artisan \
  queue:work --stop-when-empty --max-time=55 > ~/.logs/cronjob_r11hhxmHpy 2>&1   # CRONJOBID:r11hhxmHpy
```

🔬 `flock -w 1` **is** real mutual exclusion — proven by holding the lock and watching a second run refuse
after exactly 1 s. **Two workers cannot land on the same money job.** ⚠️ **But a skipped tick is SILENT**:
`-w 1` gives up without running and without saying so, and `timeout -s 9 1800` only SIGKILLs a wedged worker
after **thirty minutes** — so the worst case is ~30 minutes of silently-skipped ticks. ⚠️ An empty
`~/.logs/cronjob_r11hhxmHpy` is **not** a cron that never ran; a parked worker exits quietly.

#### 7.8.3 ⚠️⚠️ A SAMPLE INTERVAL LONGER THAN A PROCESS'S LIFETIME CANNOT SEE IT

🔬 Sampling `ps` every **10 s for 150 s** found the php84 worker **zero times**, and the flat conclusion
drawn from that was wrong. Sampling every **1 s** found it on the first tick: while the site is parked the
worker lives about **two seconds**.

⚠️⚠️ **ABSENCE IN A SPARSE SAMPLE IS NOT ABSENCE.** Match the interval to the lifetime of the thing you are
hunting, or you are measuring your own patience. ⚠️ And beware the loop that lies: `grep … | head -1 && break`
breaks on the **first** iteration whether or not anything matched, because `head` exits 0 on empty input.

#### 7.8.4 ⚠️⚠️ THE TWO PRIVACY LAYERS, AND THE ORDER THEY COME OFF ON LAUNCH DAY

The site currently carries **two independent layers**, added in this order:

1. **Maintenance** (`artisan down`) — Laravel, answers **503**, and ⚠️ **pauses the queue**.
2. **The private-preview lock** (Basic Auth in `public_html/.htaccess`) — the web server, answers **401**,
   **before PHP runs**. Five machine-only routes are exempt (see `docs/THE-LOCK-2026-09-23.md`).

⚠️⚠️ **THEY COME OFF IN THE REVERSE ORDER THEY WENT ON: MAINTENANCE FIRST, THE LOCK SECOND.**

🔬 Taking the **lock** off first would make the site **public while it is still serving a maintenance
page** — the worst of both: strangers can see it, and what they see is broken. Taking **maintenance** off
first leaves the lock as the only layer, which is exactly the intended private-preview state.

⚠️ **AND THE DISCRIMINATOR BETWEEN THEM IS THE STATUS CODE, NOT THE HEADER.** 🔬 `WWW-Authenticate` rides
along on **200** and **503** responses too, so its presence proves nothing:

- **401** = the lock refused, **before PHP**.
- **503** = the request reached PHP and the maintenance page answered.
- **200** = served.

⚠️ Re-parking must **restore the backed-up `storage/framework/down` file**, never re-run `artisan down` —
a fresh run mints a **new bypass secret** and silently invalidates the one the founder is holding.
⚠️⚠️ **That file IS the secret. Verify a backup of it by comparing digests. Never print it, never `head` it,
never print a prefix of it.**
