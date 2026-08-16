# Vyrobel — Deployment runbook

> Created 2026-08-08 as part of the audit fix pass (`docs/AUDIT-COMPLETE-2026-08-07.md`, finding **F6-4**).
>
> ⚠️ **The gap this document exists to close.** The application's 8 scheduled commands are correctly
> registered in `routes/console.php` and `php artisan schedule:list` shows all of them — but **nothing in the
> repository or the docs ever defined the one cron entry that makes Laravel's scheduler actually fire.**
> Without it every command below silently never runs, in a way that produces no error and no alert. Two of
> them are legally load-bearing: the **GDPR 14-day guest deletion** and the **30-day ID-document purge**.
> A "successful" deploy with no cron entry means erasure requests are accepted and never executed.

---

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
php artisan schedule:list     # must list all 8 commands with their next due time
```

### The 8 scheduled commands

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

### Ranked by harm if missing at launch — the recommended build order

Each of these is **its own pass**; they are deliberately not built together.

1. ✅ **Backups + a proven restore — BUILT 2026-08-16, see §7.1 above.** ⚠️ The only *unrecoverable* failure on the list. Everything else can be
   fixed after the fact; lost tenant data, folios and owner ledgers cannot. ⚠️ A backup nobody has restored
   is a hypothesis, not a backup — the pass must include an actual restore into a scratch database.
2. ✅ **Scheduler + queue liveness alerting — BUILT 2026-08-16, see §7.2 above.** §1 documents the cron correctly, but **its failure mode is
   silence** — no error, no alert, and GDPR erasure requests are accepted and never served while
   `payouts:run` also quietly stops. Documentation cannot detect its own omission; a heartbeat can.
3. **A deploy script.** §5 is order-sensitive (migrate before caching, `horizon:terminate` after) and is
   currently executed by hand. The risk is a skipped or reordered step under pressure, not ignorance.
4. **Secrets management.** Moves credentials off the filesystem; real, but the exposure is narrower than 1–3.
5. **Dockerfile / pinned runtime.** Now that **PHP ≥ 8.4.1** is a hard floor (§0), a pinned image removes an
   entire class of provisioning error — but the pre-flight in §0 already catches it.
6. **CI pipeline.** Regression safety and convenience. Valuable, and the least harmful of the six to lack on
   day one. ⚠️ Note the suite must run **alone** (a parallel run corrupts the shared test database) and the
   clock is frozen for determinism.

⚠️ **Not on this list, because they are the founder's calls, not engineering gaps:** the Stripe live-key flip
and Connect KYC, the payout-engine go-live flip, `passport:keys` per environment, `tax:set-rate` per
jurisdiction, and the VAT-validation fork (send-as-is vs VIES-first). See §3 and the linked production docs.
