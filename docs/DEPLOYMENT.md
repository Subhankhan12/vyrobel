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
  deployment sprint, not config.
