# SYSTEMS — the map of what runs where

Written 2026-09-27 by MEMORY 01. Drafted read-only from the repo; the facts marked **Verified** were read from
production the same day (booleans and names only). How each fact is known:
**Verified** = checked 2026-09-27 · **(git)** = read from the repository at HEAD `91b83801` or the working tree ·
**(Reported: doc, date)** = a doc says so, not checked here · **unknown** = not found, with how to find out.

⚠️ The working tree holds **uncommitted ADMIN 06 work** (git status, 2026-09-27): `OrganizationDeletion`, the Stripe
webhooks, a new migration, and edits to `docs/KNOWN-LIMITS.md` (L19 closed, L20 new), `docs/STRIPE-PRODUCTION.md`
(new top box), `docs/GO-LIVE-REQUIREMENTS.md` (B19, B20) and `ops/release.sh` (drift check). It is **not deployed**.

⚠️ **Stale claims to ignore:** `CLAUDE.md`'s older "Current status" text says the site is parked behind `artisan down`
and a Basic Auth lock.
Commit `5d631d9f` (2026-09-23) says **both layers are off and the site is public** (git). `docs/STATE.md` §2 says
"nothing has ever been deployed" — contradicted by every deploy record since 2026-09-20 (git).

---

## 1 · Hosting

- **Host:** Hostinger shared hosting. How to reach it (SSH target, key name, paths): `docs/DEPLOYMENT.md`
  "HOW TO REACH THE PRODUCTION HOST". Copy it from there; never retype it. (Reported: DEPLOYMENT.md, 2026-09-23)
- **Web server:** LiteSpeed, not Apache. It reads `.htaccess` in compatibility mode. Apache docs are not evidence
  for how it behaves. (Reported: DEPLOYMENT.md §7.8.1, 2026-09-23)
- **CDN in front:** Hostinger's CDN answers as `server: hcdn`. HTML is `no-cache, private` (never cached); POSTs
  always reach the origin, webhooks included; static assets are cached 7 days; a 301 was cached for 2+ hours.
  Any measurement through `vyrobel.com` measures the edge unless cache-busted. (Reported: KNOWN-LIMITS L12m, 2026-09-20)
- **CDN browser check:** the CDN shows some fresh clients a "Checking your browser…" page (HTTP 403). That is not
  the app refusing — `ops/public-checks.sh` counts it as COULD NOT MEASURE. (git: `ops/public-checks.sh` header)
- **Web process user:** the web process runs as the hosting account's own user (the same user that owns the files).
  (Reported: DECISIONS "TONIGHT 01" #2, 2026-09-25)
- **PHP:** use `/opt/alt/php84/usr/bin/php` (8.4.19). The default `php` is 8.3.33 and `artisan` exits 255 under
  it. (Reported: CLAUDE.md; KNOWN-LIMITS L12h)
- **Database:** MariaDB 11.8.9 on production (local dev is MySQL 8.4). Schema reads can be ~1 s per call there —
  time any new schema-reading path **on the host** before a swap. (Reported: DECISIONS "ADMIN 05", 2026-09-26)
- **Shared files — paths only:** `~/domains/vyrobel.com/public_html/.env` (mode 600, one copy, linked into every
  release) and `public_html/storage/` (one copy, survives a swap). `public_html` also holds `.htaccess` and the
  `artisan` shim. (Reported: KNOWN-LIMITS L12k; DECISIONS "TONIGHT 01" #2, 2026-09-25)
- **Backups of `.env`:** `~/domains/vyrobel.com/env-backups/` (dir 700, files 600), outside the web root
  (Reported: DECISIONS "TONIGHT 01" #1, 2026-09-25). Database backups: `docs/DEPLOYMENT.md` §7.1, GO-LIVE
  S2/B3/A5/A7 — whether one runs on production: unknown (ask the founder; check hPanel).
- **The doc root IS the app root:** `.env`, `storage/` and the maintenance file sit inside the served tree; the
  rewrite into `current/public/` is the wall. Check it with `ops/htaccess-drift-check.sh` against
  `ops/reference/public_html.htaccess`. (Reported: KNOWN-LIMITS L12p, 2026-09-21)

## 2 · Deploy — the L12k release layout

```
public_html/current  →  ../releases/rel_<X>     (the one pointer; a swap is one `mv -T`)
releases/rel_<X>/.env     → ../../public_html/.env
releases/rel_<X>/storage  → ../../public_html/storage
```
(Reported: KNOWN-LIMITS L12k, adopted 2026-09-20)

- **Script:** `ops/release.sh` — `status · stage · recache · migrate · swap · rollback` (git). Full procedure and
  traps: `docs/KNOWN-LIMITS.md` L12k "HOW TO DEPLOY INTO THIS LAYOUT"; older runbook `docs/DEPLOYMENT.md`.
- **Which commit is live:** read `public_html/current/.release` (`ops/release.sh status` does this). Never type a
  hash. **Verified 2026-09-27 (MEMORY 01): `rel_AE @ 1ede4c63`**; read it again before relying on it.
- **`stage`** (git): refuses a dirty tree; `cp -al` of the live release (hardlinks); ships only files changed
  between the live commit and HEAD, each written as a **new inode** (never `scp` onto a hardlinked file); writes
  `.release`; rebuilds `config:cache` + `route:cache` **in the new release every time** (the config cache bakes
  absolute paths — a copied one silently serves the old views); `chmod 600` the config cache; checks `.env` is the
  link and re-applies 600; proves the live release's inodes are untouched.
- **vendor/ and assets:** `stage` refuses a change to `composer.*`, `package*.json`, `vite.config.*`,
  `resources/css|js` — a separate, deliberate rebuild step (git). Composer has run on the host under the 8.4 binary
  (Reported: KNOWN-LIMITS L12h); no Node on the host, so `public/build` is built off-host (Reported: L12k step 2).
  The vendor step is not scripted and its last run is not recorded — unknown; ask, or compare `vendor/` across releases.
- **Migrations:** `ops/release.sh migrate rel_<X>` runs pending migrations from the new release while the old one
  serves, and refuses any `up()` that drops, renames or changes (git). A schema change that is not additive still
  needs a park (Reported: KNOWN-LIMITS L12k "SCOPE IT HONESTLY").
- **Boot check:** a by-hand read on the host, through the staged release's own `artisan`, of what the change should
  make true (e.g. a config value) — before the swap. Not a script. (Reported: DECISIONS "ADMIN 01 Phase 3", 2026-09-24)
- **After the swap:** run `ops/public-checks.sh` (PASS / FAIL / COULD NOT MEASURE; any FAIL → roll back first).
  A 200 does not prove the new code landed — diff a marker only the new code emits. (git; KNOWN-LIMITS L12k step 3)
- **Rollback:** `ops/release.sh rollback rel_<previous>` — repoints the symlink; the old release is never modified (git).
- **Prune:** no sooner than 180 s after a swap (pruning at once gave 60/60 HTTP 500). (Reported: KNOWN-LIMITS L12k)
- `ops/deploy.sh` cannot run on this host (no `.git` there). Do not use it. (Reported: KNOWN-LIMITS L12h)

## 3 · Config is cached on production

- Production runs from a cached config, so **a `.env` edit alone changes nothing**. After any `.env` change:
  `ops/release.sh recache rel_<new>` (same commit, config + routes rebuilt from today's `.env`), then `swap`.
  Rollback restores the old config exactly. (git: `ops/release.sh`; Reported: KNOWN-LIMITS L14, L12k step 7)
- **Prove it by behaviour**, never by reading `.env` (a health board card, a real request). Code reads settings
  through `config()`, never `env()` outside `config/`. (Reported: KNOWN-LIMITS L14, 2026-09-24)

## 4 · Queue and scheduler

- **Queue:** `QUEUE_CONNECTION=database` on production; no Redis, no Horizon there. Mail is queued (e.g. the booking
  confirmation). (Reported: GO-LIVE B4, measured 2026-09-23; CLAUDE.md)
- **Worker:** a hosting-panel cron, every minute:
  `flock -w 1 … timeout -s 9 1800 /opt/alt/php84/usr/bin/php …/public_html/artisan queue:work --stop-when-empty --max-time=55`.
  `flock` stops two workers overlapping; a skipped tick is silent. (Reported: DEPLOYMENT.md §7.8.2, 2026-09-23)
- **Scheduler:** a panel cron runs `schedule:run` through `public_html/artisan` — a **shim** that loads
  `current/artisan`. The path is hardcoded in the panel; moving the app once killed it silently. Its exact cron
  line is not recorded. (Reported: KNOWN-LIMITS L12k "THREE THINGS…" #3; GO-LIVE B5)
- Crons are panel-managed: a session cannot edit them; `crontab` is not a command there (exit 127). You can read a
  cron's line off `ps` while it runs — sample every 1 s, not every 10 s. (Reported: DEPLOYMENT.md §7.8.2–7.8.3)
- ⚠️ **The decoy:** `/opt/alt/php83/usr/bin/php artisan queue:work database --tries=3 --stop-when-empty` belongs to
  `hrmzy.com`, not Vyrobel. Tell them apart by the binary (`php84` = ours). (Reported: DEPLOYMENT.md §7.8.2)
- **What is scheduled:** `routes/console.php` (git) — list with `php artisan schedule:list`, don't copy a count. It
  includes the GDPR guest deletion (03:00), the ID-document purge (03:30), `retention:prune` (04:00),
  `payouts:run` (06:00, inert unless `PAYOUT_ENGINE_ENABLED`), `break-glass:expire` and two heartbeats every minute (git).
- **Liveness:** `/up/liveness` answers 503 when the scheduler or queue heartbeat is stale (git: routes/web.php).
  The external ping `OPS_HEARTBEAT_PING_URL` is **not set** (**Verified 2026-09-27**), so the alarm is unarmed
  (`OPEN.md` D-HEARTBEAT, F4).

## 5 · Mail

- **Provider:** Hostinger's own mail over SMTP, sending as `contact@vyrobel.com` (the From must equal the login).
  Postmark was superseded. Settings, by name: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`,
  `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_SUPPRESS_ADDRESSES`.
  (Reported: `docs/EMAIL-PRODUCTION.md` top, 2026-09-25; git `f6cd45a4`)
- **Is it switched on?** Last record: `MAIL_MAILER=log` ("mail still off") because `MAIL_PASSWORD` was not in `.env`
  (Reported: DECISIONS "TONIGHT 01", rel_AA, 2026-09-25). **Verified 2026-09-27: still `log`, `MAIL_PASSWORD` not set**,
  so every email is discarded. Re-check on the `/admin` mail card.
- **"Mail discards":** one definition, `App\Support\Platform\MailDelivery::discards()` — `log` and `array` discard.
  Read by the health board and the support buttons. (git)
- **Skip list:** `App\Support\Mail\UndeliverableRecipients`, applied before every send by
  `App\Listeners\Mail\SuppressUndeliverableRecipients` — reserved names (`.invalid`, `.test`, `.example`,
  `.localhost`, example.com/.net/.org) plus the exact addresses in `MAIL_SUPPRESS_ADDRESSES`. Probe accounts live
  on `.invalid`. A message with nobody real left is cancelled. (git)
- **Send log:** `mail_send_log` — accepted / suppressed per email type; no address, subject or body (git `f6cd45a4`).
- **Bounces are invisible to the app:** they come back as email to `contact@`, and "accepted" is not "delivered".
  (Reported: EMAIL-PRODUCTION.md top, 2026-09-25)
- **Daily limit:** 100 emails a day (Reported: `docs/memory/briefs/TONIGHT-01.md` — a brief, untracked, not measured).
  **When the free mail plan ends: 2 April 2027** (Reported: read in hPanel by the session that ran MAIL 02 / TONIGHT 01,
  2026-09-25; no repo doc records it). Re-check in hPanel → Emails.
- **`sales@vyrobel.com` has no mailbox**; mail to it is rejected. Ruling 2026-09-26: `sales@` stays on /privacy and
  /terms, everything else prints `contact@`. The fix is a one-minute hPanel alias `sales@` → `contact@`.
  (Reported: KNOWN-LIMITS L18; DECISIONS 2026-09-26.) **Reported 2026-09-26: the alias WAS created in hPanel by the
  session**, but delivery is not proven (`OPEN.md` F8). L18's own text predates it.
- **Postmark is still installed:** `composer.json` requires `symfony/postmark-mailer` + `symfony/http-client`;
  `config/mail.php` keeps a `postmark` mailer (git). Switching = `.env` settings + `recache` + swap, plus the Postmark
  account and DNS (EMAIL-PRODUCTION.md, history part). In production's `vendor/`? unknown — check `vendor/symfony/`.

## 6 · Sign-in

- **Realms:** operator/owner (`web` guard), guest (`guest` guard), platform admin (`admin` guard), and the owner API
  (`api`, Passport) (git: `config/auth.php`).
- **Email + verify gate:** `/app` carries the `verified` middleware — the founder's ruling "verify before /app"
  (git: routes/web.php, the `/app` group). Sign-up sends the first verification link (Reported: DECISIONS "TONIGHT 01" #5).
  If mail is down, `docs/DEPLOYMENT.md` "HOW TO VERIFY AN ACCOUNT WHEN MAIL IS DOWN", or the console's support button.
- **Google and X:** one contract, `App\Contracts\Auth\SocialIdentityProvider`; a closed registry,
  `App\Support\Auth\SocialProviders`; implementations in `app/Services/Auth/`. Links live in `user_identities`
  (UNIQUE provider + provider id). Routes `/auth/google/*`, `/auth/twitter/*` (git). Rules: `docs/GOOGLE-SIGN-IN.md`.
- **The linking rule** (`App\Support\Auth\SocialLinking`): match on the provider id, never on email.
  `RefuseEmailTaken` — a provider address that matches an existing account is refused; sign in with the password,
  then link from the security page. (git; GOOGLE-SIGN-IN.md §1)
- **The X exception:** X reports no email verification, so X is on `SocialProviders::TRUSTED_FOR_SIGNUP` and may
  create accounts — a named founder ruling of 2026-09-24. Cost: someone could squat an unclaimed address. Google is
  deliberately not on the list. (git: commit `c31ea8c2`, class docblock)
- **Sessions:** `SESSION_DRIVER=database` on production since rel_W (2026-09-25); a password change or reset ends
  every other session. A rollback before rel_W returns sessions to files. (Reported: KNOWN-LIMITS L16)
- **Kill switches** turn Google/X sign-in and sign-up off (§7). Owner API (Passport): `docs/OAUTH-DESIGN.md`, L13.

## 7 · The admin console (`/admin`)

Separate realm: `admin` guard, mandatory 2FA, no public sign-up (first admin: `php artisan admin:create`); every
route has a role gate from `App\Support\Platform\PlatformAdminRole`; controllers in `app/Http/Controllers/Admin/` (git).
Roles Owner · Support · Billing · Analyst; every console write needs a written reason (Reported: DECISIONS "ADMIN 01 P1").

| Page | Does | Cannot / notes |
|---|---|---|
| `/admin` health board | mail · queue · scheduler + dead-man's switch · server errors · live release · silent config · switches · exposure battery · Channex (git: `HealthBoard`) | A check that throws is grey, never green. No deploy button. |
| `overview`, `hotels`, `funnel` | business overview; hotel aggregates, adoption, cohorts; sign-up funnel | Aggregates only, customers only unless asked; no guest names. |
| `customers`, `customers/{id}`, `triage` | directory; detail with account holders and sign-in activity (ADMIN 04); triage buckets | — |
| customer writes (Owner, Support) | create (through `TenantProvisioningService`), update details, suspend, reactivate, archive, restore, mark internal | Protected orgs (`lifecycle_lock`) refuse or need the name typed. Currency and slug not editable. |
| `customers/{id}/delete` (Owner only) | permanent delete (ADMIN 05, `App\Services\Platform\OrganizationDeletion`) | Only suspended/archived orgs; refuses while money is moving; Stripe ids go to the ledger to close by hand. Signed-in live pass not yet done (Reported: DECISIONS ADMIN 05, 2026-09-26). |
| `revenue` | MRR/ARR from processed `invoice.paid` rows | Read-only. No console route charges, refunds or transfers (git: route comment). |
| `privacy` | GDPR deletion queue (watch) · request register · retention meter (`RetentionCoverage`, reads `resources/data/data-inventory.json`) · sub-processors (`SubProcessors`) | Register never erases; no free text. Retention ruling is still `null`. |
| `support` | look up a user, unlink a provider, mark email verified, resend verification, send reset | Refuses an address that can never receive mail, or when mail discards. |
| `break-glass` | 30-min read-only view as a verified back-office user; reason + fresh TOTP; tenant is told | Switched ON 2026-09-24 (`config/platform.php` `break_glass.enabled`, a literal) (git `9d33eee5`). |
| `switches` (Owner) | `App\Support\Platform\PlatformSwitches`: sign-ups (all), sign-up Google, sign-up X, sign-in Google, sign-in X | Missing row = ON; 30 s cache. |
| `plans`, assign plan (Billing) | plan catalogue | Stripe sync deferred (git: route comment). |
| `integrations` | master channel credentials, encrypted, write-only, test | — |
| `admins`, `account`, `activity` | invite / role / revoke / restore / sign out admins; own account; sign-in activity | Last Owner cannot be removed (Reported: ADMIN 01 P1). |
| `audit` | the ledger, `platform_admin_actions` | Append-only in the app, not in the DB (KNOWN-LIMITS L17). |
| `announcements`, `status` | notices shown in `/app` and `/owner`; public `/status` page | `/status` shows posted incidents only, never "all operational". Posting is public at once. |

- **The read-only door:** `App\Support\Platform\PlatformQuery` — `read()` is read-only at the DB connection
  (`PlatformWriteGuard`); `mutate()` refuses without a reason and writes the ledger row first (Reported: ADMIN 01 P1–P2).
- **Channex:** health card only, "Not connected"; the fake client is the default (Reported: ADMIN 01 P5; STATE.md §2).
- The console cannot: deploy, purge the CDN, edit crons, erase a guest, comp a plan or extend a trial (not built —
  Reported: ADMIN 01 P4), or close anything at Stripe.

## 8 · Stripe

- **Today:** production runs the **fake Stripe client**, with **no Stripe key**, no webhook secrets and placeholder
  prices — not even Stripe test mode. (**Verified 2026-09-27**, booleans only)
  `PAYMENTS_GATEWAY` / `PAYOUTS_RAIL` default to `manual` (Reported: STATE.md §2, 2026-08-26).
- **Webhooks:** `POST /webhooks/stripe` (account; `STRIPE_WEBHOOK_SECRET`) and `POST /webhooks/stripe/connect`
  (Connect; `STRIPE_CONNECT_WEBHOOK_SECRET`) — two different secrets (git: routes/web.php; STRIPE-PRODUCTION.md §1).
  Each event is stored, then applied inline in the request — not queued (git: `WebhookEventClaim::once()`). So a
  maintenance park 503s them and leaves no trace (Reported: KNOWN-LIMITS L12g). The CDN passes them straight
  through (Reported: KNOWN-LIMITS L12m).
- **Before any live key:** read `docs/STRIPE-PRODUCTION.md` from the top. In the working tree its first box is
  "⛔⛔ NO LIVE KEY UNTIL THESE REFUSALS ARE LIVE (ADMIN 06)" — read the live `.release` and confirm it contains
  ADMIN 06 (uncommitted and undeployed as of 2026-09-27, git). Also GO-LIVE **B20** / KNOWN-LIMITS **L20**: a guest's
  card page may not open with real Stripe — confirm with one real test-mode session first. Then §3 "THE LIVE-KEY
  FLIP" and §C "THE NUMBERED DASHBOARD LIST". The lawyer items (Article 28, flow of funds) come first:
  `docs/STATE.md` §5. Every key and secret is the founder's to type.

## 9 · Tooling — Playwright MCP

- Browser checks use the Playwright MCP server driving `php artisan serve` — not an E2E suite; artefacts in
  `.playwright-mcp/` are git-ignored (Reported: CLAUDE.md).
- `.mcp.json` is git-ignored (git: `.gitignore`, commit `a85a1b27`, 2026-09-24). Its shape on this machine
  (read 2026-09-27, file dated 2026-09-24; it carries no env values):
  one server `playwright` — `npx -y @playwright/mcp@latest --browser chromium --isolated`.
  No `--headless` (a visible browser) and no `--no-sandbox` (the browser sandbox stays on). `--isolated` = a fresh
  profile each session.
- **On a new machine:** create `.mcp.json` at the repo root with exactly that one server and those args (`command`
  `npx`), add nothing secret, then restart Claude Code — MCP config is read at start (Reported: AUDIT-PHASE-0.md §4a).
  Its tools appear as `mcp__playwright__*`. Needs Node/npx. The official `playwright@claude-plugins-official`
  plugin is a separate server (`mcp__plugin_playwright_playwright__*`) and is not configured by this file.
- On 2026-09-12 it was headless + `--no-sandbox` (Reported: AUDIT-PHASE-0.md §4a); why it changed: unknown.

---

## Sources

- **git:** commits `91b83801` `5d631d9f` `f6cd45a4` `6747d128` `8fb16054` `c31ea8c2` `9d33eee5` `a85a1b27`; status +
  diff (2026-09-27); `routes/web.php`, `routes/console.php`, `ops/release.sh`, `ops/public-checks.sh`, `config/`,
  `composer.json`, `.gitignore`, `.env.example` (names only), `app/Support/{Platform,Mail,Auth,Webhooks}/`; `.mcp.json` (shape).
- **docs:** `CLAUDE.md` · `docs/DEPLOYMENT.md` · `docs/KNOWN-LIMITS.md` (L12g/h/k/m/p, L13–L20) · `docs/DECISIONS.md`
  (ADMIN 01 P1–P5, TONIGHT 01, ADMIN 04/05/06) · `docs/EMAIL-PRODUCTION.md` · `docs/GOOGLE-SIGN-IN.md` ·
  `docs/OAUTH-DESIGN.md` · `docs/STRIPE-PRODUCTION.md` · `docs/GO-LIVE-REQUIREMENTS.md` · `docs/STATE.md` ·
  `docs/THE-WALK-2026-09-23.md` · `docs/AUDIT-PHASE-0.md` §4a · `docs/memory/briefs/TONIGHT-01.md` (a brief).
