# SYSTEMS — the map of what runs where

Written 2026-09-27 by MEMORY 01; **updated 2026-10-01, and §13 on 2026-10-02 (the payout run)** (§2, §9, §10 brought up to date; §11–§13 added for the operator
page rebuilds of 2026-09-29 → 10-01). Drafted read-only from the repo; the facts marked **Verified** were read from
production on the date given (booleans and names only). How each fact is known:
**Verified** = checked 2026-09-27 · **(git)** = read from the repository at HEAD `91b83801` (2026-09-27) or the working tree ·
**(Reported: doc, date)** = a doc says so, not checked here · **unknown** = not found, with how to find out.

⚠️ **ADMIN 06 was finished and committed on 2026-09-29** (`OrganizationDeletion`, the Stripe webhooks, a migration,
`ops/release.sh`'s drift check, and `docs/KNOWN-LIMITS.md` L19/L20, `docs/STRIPE-PRODUCTION.md`'s top box, GO-LIVE B19/B20).
Whether it is live: `STATE.md` — never this line.

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
  the app refusing — `ops/public-checks.sh` counts it as COULD NOT MEASURE. (git: `ops/public-checks.sh` header) ⚠️ After it
  fires on a client, the edge may answer that client itself for a while — 504s and 307s to the same address in ~80 ms, `/up`
  included — which the script reads as FAIL (V 2026-10-06, D-EDGE-CHECKS); and a signed-in automated browser came back from it
  signed out, its session row untouched (V 2026-10-06, D-EDGE-SIGNOUT).
- **Web process user:** the web process runs as the hosting account's own user (the same user that owns the files).
  (Reported: DECISIONS "TONIGHT 01" #2, 2026-09-25)
- **PHP:** use `/opt/alt/php84/usr/bin/php` (**8.4.26**, V 2026-10-10 — the host updated it; 8.4.19 through 2026-10-06). The default `php` is 8.3.33 and `artisan` exits 255 under
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
  hash. **Verified 2026-10-03 21:32 Baku (the owners module deploy): `rel_AS @ 4751d2c9`**; read it again before relying on it.
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
  make true (e.g. a config value) — before the swap. (Reported: DECISIONS "ADMIN 01 Phase 3", 2026-09-24)
- **The read-only preflight (every pass since 2026-09-29):** a PHP script piped over stdin into the STAGED release —
  `ssh … "cd ~/domains/vyrobel.com/releases/rel_<X> && /opt/alt/php84/usr/bin/php" < preflight.php` — that first sets
  `SET SESSION TRANSACTION READ ONLY`, then checks the new routes' permission gates, compiles the changed views in memory,
  times the new reads on production's own data, and prints yes/no, names, counts and timings only. A template:
  `tools/preflight-owners.php.txt` (the Owners pass's). (Reported: the DECISIONS deploy records 2026-09-29 → 10-01; each
  pass kept its script in its session's scratchpad. Verified for `rel_AQ`, 2026-10-01)
- **After the swap, the byte check:** a sha256 manifest of `git archive <live commit>` streamed to `sha256sum -c -` inside
  `public_html/current` — "OK" for every deployable file, "FAILED open or read" for files never deployed (tests, docs,
  tools). Count both; a content FAILED is a stop. (Verified 2026-10-01: 1,438 OK, 0 differ, 1,203 never deployed)
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
- **Daily limit:** 100 emails a day (Reported: `docs/memory/briefs/TONIGHT-01.md` — a brief, not measured).
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
  ADMIN 06 (finished 2026-09-29; whether it is live: `STATE.md`). Also GO-LIVE **B20** / KNOWN-LIMITS **L20**: a guest's
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
- **The live look (each page pass since 2026-09-29):** open `https://vyrobel.com/login` in the `mcp__playwright__*` window
  (the CDN's browser check answers 403 once, then the page), ask him to sign in himself, then READ the window's URL before
  navigating — on 2026-10-01 it was still on `/login` when he answered and reached `/app/dashboard` seconds later. Look
  only (his workspace is a protected organisation); at the end submit the logout form, confirm `/app/…` redirects to
  sign-in, and close the browser. (Verified 2026-10-01)
- ⚠️ **Within one session the profile keeps its cookies.** A local walk on 2026-10-01 found the window still signed in as
  tenant one's Hotel Manager from an earlier look — read WHO is signed in (the top-right name) before any write.
- **Local walks sign in at `http://localhost:8000`** (the app's URL): a sign-in form opened at `127.0.0.1:8000` posts to
  `localhost`, and Chrome's CSP blocks it (Verified 2026-10-01, the console). Tenant three (`demo-harbour@harbourpoint-demo.test`)
  is the only local tenant to write in; reseeding resets `email_verified_at`, so set it again after `migrate:fresh --seed`.
  Mailpit catches local mail (UI `:8025`, SMTP `:1025`); never print an invite token — accept a probe invite through
  `OwnerInviteService::accept()` in tinker instead.

## 10 · This Mac is shared

- ⚠️ **`advisor-platform` uses the same local MySQL 8.4 on 3306** — its test database (`advisor_platform_test`) and a long-running
  Octane/FrankenPHP dev server on `:8347` (Verified 2026-09-29, by working folder). The MariaDB engine swap stops that server
  for ~25 minutes: ask him to pause that project first (F 2026-09-29). Its `pest` runs also block the mutation harness.
  **F 2026-09-30: "stop advisor and complete vyrobel" — its dev server was stopped and left stopped** (Verified 2026-10-01
  18:46 Baku, `ps`: no Octane or FrankenPHP process). To restart it, in its own folder:
  `php artisan octane:start --server=frankenphp --host=127.0.0.1 --port=8347`.
- Other projects' long-running `php -S` servers (Iftihar, Gul Heights, G&S Builders) hold no database connection — the
  MySQL processlist was empty before each MariaDB swap (Verified 2026-10-01). Never stop them.
- `restiox` runs tests here too (Verified 2026-09-27). Identify any process by its working folder; never stop another
  project's process.

## 11 · The operator back office — the shared kit (2026-09-29 → 10-01)

Seven passes rebuilt operator pages on one kit — Guests, Maintenance, Units, Rates, Owners, plus the Portfolio's remove and
Open (git; `docs/DECISIONS.md` 2026-09-29 → 10-01; `HISTORY.md` §1).
- **List pages** (`/app/guests`, `/app/maintenance`, `/app/units`, `/app/owners`): `resources/views/partials/operator-list-script.blade.php`
  defines `window.vyListBehaviour` — search that follows typing, filter/sort/page links swapped in place (it fetches the SAME
  URL and swaps `[data-gl-region="chips"]` and `"results"`; anything unexpected loads the page normally), the one row menu,
  select mode. Every link and form works without it. Each page reads its query through one allow-list class —
  `App\Support\Guest\GuestListParams`, `…\Maintenance\MaintenanceListParams`, `…\Unit\UnitListParams`,
  `…\Rates\RatesListParams`, `…\Owner\OwnerListParams` — which also rebuilds the way back (`return=list` +
  `return_query`, parsed and rebuilt, never used as a URL). Units and Owners have a rows class (`UnitRows`, `OwnerRows`)
  read by both the page and its CSV export, so the two cannot disagree. ⚠️ A control OUTSIDE the swapped regions goes stale
  after an in-place change: the Owners Export link takes `location.search` at the click; the Units one does not yet
  (`OPEN.md` A12).
- **Dialogs:** `.vy-modal-backdrop` (carries the display and centres) › `.vy-modal.vy-modal-card` (near-opaque, so muted
  text clears AA), with `role=dialog`, `aria-modal`, `aria-labelledby`, `tabindex=-1`, `x-trap.noscroll` and Escape.
  ⚠️ `x-show` erases an inline `display`, so every display is a class — `XShowInlineDisplayContractTest` holds the other
  screens at a ceiling of 12 (`OPEN.md` A8; 22 until 2026-10-05, 14 until 2026-10-24); a string `:style` replaces the whole attribute — `AlpineStyleBindingContractTest`,
  ceiling 9 (A10). `.vy-modal`'s own 26 px padding stacks with the head/body/foot's; the rebuilt dialogs set it to 0.
- **Page styles** live in the operator layout's `<style>` (`resources/views/components/layouts/operator.blade.php`): the
  shared `.vy-gl-*`, then one block per page (`.vy-mt-*` Maintenance, `.vy-ul-*` Units, `.vyr-*` Rates, `.vyo-*` Owners — and since 2026-10-03 the owner's page, `.vyo-pr-*` the payout run and `.vyo-ur-*` unit requests; since
  2026-10-04 `.vy-nr-*` the New reservation wizard, which replaced the old `.vyn-*` rules — only `.vyn-grid3` stays — and `.vy-rnr-*` the Calendar's New reservation dialog, which replaced `.vy-newres-*`; since 2026-10-05 `.vy-gst`/`.vy-gs-*` the Guests
  second pass — its card is a size container, `vy-gst-list`, and its rows stack at 960 px of card and below; since 2026-10-05 the five-page
  pass: `.vy-ibx-*` the Inbox dialogs, `.vy-hk-*` Housekeeping (container `vy-hk-list`, stacks at 820 px of card), `.vy-wo-*` the work
  order and the Maintenance board's container `vy-mt-list` (980 px), the Units sections, compact room type cards and container
  `vy-ul-list` (860 px), and `.vy-sec-*` Security). The shared search box
  (`.vy-gl-search*`, the four list pages) is 40 px like the toolbar's buttons, a centred 16 px icon, the browser's search styling off. ⚠️ Check a new class name for an EXISTING rule before using it: the owner page's root once took `.vyo-owner`, the Owners list's flex-row cell.
  ⚠️ That stylesheet ships on every operator page, so its comments are page text (`RULES.md` 2d).
- **Writes:** each a POST to a controller through every middleware (#20); the Livewire components expose `render()` only
  (`OperatorLivewireMutationBoundaryTest`). While the workspace is paused (`OperatorWorkspace::isReadOnly()`) every write
  refuses and the page offers none.
- **The property allow-list on writes** (`PropertyContext::readIds()`, #38): every unit write including Add unit (DEC-k),
  every rate write through `App\Support\Rates\RateScope` (DEC-l), and the units an owner invite may give (Owners).
  ⚠️ NOT yet: the Portfolio's Edit (DEC-i) and the maintenance writes (DEC-j) — `OPEN.md`.
- **Whether email is sent:** `App\Support\Mail\MailSafetyGuard::sendingNow()` — the ONE question a screen asks before it says
  "sent by email" (the Guests Message refuses without it; the New reservation page asks it). ⚠️ Other screens still say an email
    went out without asking (`OPEN.md` D-MAIL-CLAIMS). The Guests Message is sent AS THE PROPERTY (`BrandsGuestMail`: its name and
  reply-to address, like every other guest email) — which property is ONE definition, `App\Support\Guest\GuestMessageSender::property()`,
  read by the page (it names it) and the controller; there is no inbound mail, so no email reply reaches the Inbox.
- **Units (2026-10-05):** what may be deleted is ONE rule, `App\Support\Unit\InventoryDeletion` (`unitBlockers()` /
  `roomTypeBlockers()` — every table that CASCADEs or SET NULLs on a unit or room type, plus the FK-less moves and unit requests, the
  morph notes/documents/files, an owner, a pending owner invitation), read by the page (each row and card says beforehand) and asked
  again inside the delete's transaction with the row locked. Several codes at once: `App\Support\Unit\UnitCodes` ("101-110, 120",
  up to 100; the page's script mirrors it only to preview). Priced or not: `App\Support\Unit\RoomTypePricing::pricedIds()` (the
  setup guide's third step, the card's state, the add notices). A room type's kind maps to its units' (`dorm` → `dorm_bed`).
- **Security (2026-10-05):** `App\Support\Security\ActiveSessions::key()` names a session by a one-way hash (never its id) and
  `signOutOne()` deletes one of the person's own other sessions (route `app.security.sessions.sign-out`). Each password form carries a
  hidden `_form`, and the shared two-factor panel and passkey card show an error only for their own form; the operator page shows
  an unplaced error at its top only.
- **One stay, one rule:** `App\Support\Reservations\StayLabel::for()` — the Guests Stay column and the Inbox's New message list.
- **Shared partials:** `partials.submit-guard` (`data-guard-submit`: one submit per click) and `partials.count-up` (figures
  animate up on load — read the data, not a screenshot taken mid-count).
- **Each page's proof:** a `*PageFixesTest` (what the writes do; each RED first) and a `*PageRedesignTest` (what the page
  shows, read from the rendered HTML), plus a mutation set in `tests/Mutations/sets/` (`guests-list`, `maintenance-board`,
  `units-list`, `rates-page`, `owners-page`, `owner-module`, `new-reservation`, `new-reservation-bookable`, `calendar-new-reservation`,
  `calendar-bookable`, `guests-second`, and since 2026-10-05 `inbox-compose`, `housekeeping-five`, `maintenance-five`, `units-five`,
  `security-five`) run by `php tests/Mutations/run.php` — ⚠️ WITHOUT `PAO_DISABLE=1`: the harness reads the runner's JSON summary
  line, which that setting switches off (every result then reads "no result").

## 12 · Photos and message attachments

- **Public uploads answer at `/media/{path}`** (`App\Http\Controllers\PublicMediaController`, route `media.show`,
  registered in `bootstrap/app.php` outside the web group; live since `rel_AM`, 2026-09-30). Production refuses every
  `/storage…` address by design (KNOWN-LIMITS L12p), so room photos, logos, avatars and work-order photos had answered 404.
  ⚠️ A rollback to `rel_AL` or earlier puts the addresses back on `/storage` — every photo 404s again. (git; Verified on
  production 2026-09-30: his photos answer 200 `image/jpeg` at `/media`)
- **Owner message attachments are private** (DEC-m, live since `rel_AN`): stored on the private disk and opened only through
  two signed-in routes — `owner.messages.attachment` (the owner scope: that owner and their team) and `app.inbox.attachment`
  (`guests.view`, that organisation) — both through `App\Support\Inbox\MessageAttachments`. Signed out, both send you to
  sign-in; no public address serves one. (git; Verified on production 2026-09-30)

## 13 · Owners, invites and the payout engine

- **Invites** — one writer, `App\Services\OwnerInviteService`: `invite()` (owner or staff: `manager` → Hotel Manager,
  `accountant` → Accountant), `resend()` (a fresh link; asks the operator's password at the route), `cancel()` (the link stops
  working; a never-activated owner record leaves the list, an active one never), `accept()`, `pruneExpired()`.
  `owner_invite.sent` / `.resent` / `.cancelled` go on the audit trail with ids and figures, never the address (the
  `owner_invite` morph alias makes an invite recordable). A founder's set-password link (`FOUNDER_ROLE`, ADMIN 04) is never
  the Owners page's: a 404 there, and the service refuses it. ⚠️ An invite never changes a unit's owner: refused at the
  invite, kept at acceptance (`whereNull('owner_id')`), dropped on a resend. (git, 2026-10-01)
- **The Owners list's statuses** come from `App\Support\Owner\OwnerRows::status()`, in order: not activated → Invited /
  Not activated; no closed statement → No statement; paid; a held payout → On hold; money due with no tax details or no
  payout account → On hold with the reason; lines the booking channel has not confirmed → Needs review; paused with money
  due → Payouts paused ("no payout goes out until resumed"); Ready; Nothing due. A statement closes only when someone presses Close period on an owner
  (`OwnerManageController::closePeriod`, the only caller of `finalize()`).
- **The payout engine:** `PayoutRunService::runScheduled()` (the daily `payouts:run`, inert unless `PAYOUT_ENGINE_ENABLED`)
  picks the owners due through `dueOwnerIds()`, which skips a paused owner and one below their minimum. `run()` with NO owner
  list selects every organisation, under super-admin — the platform's mode, for the daily run and the engine's own tests.
  **Since 2026-10-02 (A11) "Run payouts now" names its own organisation's owners** (`PayoutRunActionController::runNow`,
  through the tenant scope) and its run key carries the organisation (keys are unique platform-wide; an existing key is
  handed back as it is). The payout run page's **"Last run here"** is read from this organisation's own payouts — run rows
  carry no organisation. (git; 🔬 `PayoutRunScopeAndPauseTest`)
- **A pause stops every payout (DEC-n, ruled 2026-10-02):** `OwnerPayoutPreference::isPaused()` is the one read;
  `OwnerPayoutService` throws `PayoutsPausedException` in `createFromStatement()`, in `pay()` (inside the row lock, before the
  rail, beside the tax hold) and in `retry()`; `PayoutRunService::owners()` never selects a paused owner; the payout run lists
  paused owners under "Payouts paused" and its batch buttons skip and count them (in owners). (git)
- **`PAYOUT_ENGINE_ENABLED` is OFF on production** (Verified 2026-10-03, the read-only preflight). The payout run page's
  Create payouts / Mark paid record by hand (`ManualPayoutRail`); since 2026-10-03 its "Run payouts now" is shown disabled with
  the reason while the engine is off (D-PAYRUN-BUTTON fixed).
- **A commission change (2026-10-03) — ONE rule, `App\Support\Owner\CommissionChange`**, read by the owner's page and run by
  `OwnerManageController::update()`: a change starts on the first of next month — or TODAY for an owner with no agreement in
  effect (`startsOn()`); EVERY active owner-wide agreement covering that day is bounded the day before (ENDED if it would only
  have started that day — "Replaced before it started"), so a save also collapses rows an older write stacked; the new one
  carries the newest covering row's terms (`AgreementAmendmentService::TERM_KEYS`), keeps that row's end date, and ends the day
  before any change already planned for later (`plannedAfter()`); the owner row is locked for the save. `governing()` = the
  latest to start, then the latest made — one row, never the database's choice. Owner earnings are worked out from the agreement
  covering the stay's date when a booking is made (later for one imported without them; again when it moves to another owner's
  unit), so a change applies to bookings worked out after it is saved. ⚠️ Edit owner no longer reads
  `portal_active` — portal access is not switched from that page (DEC-o, his). (git; 🔬 `OwnerPageFixesTest`)
- **A unit's owner is written in two places only** — an accepted invite (`OwnerInviteService`, unowned units only) and an
  approved unit request (a new unit, `OwnerUnitRequestService::approve()`) — and cleared only when the owner is removed
  (`OwnerManageController::remove()`). No screen gives an existing owner a unit (DEC-p, his). (git, every writer read 2026-10-03)
- **The owner's page dialogs** (`owner-detail.blade.php`): each form posts `_form` (commission · close · remove · resend ·
  propose · note); a refused write comes back with its input, and the page reopens THAT dialog with the reason beside its field.
  Resend and Cancel of a waiting invite post `return=owner` — `OwnerInviteActionController` sends the operator back to the
  invite's own owner (read off the invite, never the request; a cancel that removed a never-activated owner goes to the list).
- **The payout run asks first (2026-10-03):** each money form carries `x-on:submit="ask($event)"` and `data-confirm` (title, sub,
  facts, button); `ask()` stops the submit and opens the one shared dialog; `go()` marks the form answered and calls
  `requestSubmit()`, so the submit event runs again, passes `ask()`, and the double-submit guard (`partials/submit-guard`) takes
  it. Without the script the forms post as before. The focus lands on Cancel. "Run payouts now" is shown disabled, with the
  reason, while `PAYOUT_ENGINE_ENABLED` is off (`PayoutRun` passes `engineOn`; D-PAYRUN-BUTTON fixed).
- **Unit requests (2026-10-03):** a request whose owner was removed can only be declined (`OwnerUnitRequestService::approve()`
  refuses it). `UnitRequestActionController::approve()` finds the property inside
  `PropertyContext::readIds()` (DEC-k — a property the person does not work in is a 404, nothing written); the page
  (`OperatorUnitRequests`) offers the same set. Each pending card carries `data-review` (its own approve and decline addresses,
  for `units.manage` only); the two dialogs read it.


## 14 · The New reservation wizard (`/app/reservations/create`, 2026-10-04; four steps since the second pass)

- **The page** (`App\Livewire\Operator\NewReservation`, render only) reads; **the booking** posts to
  `ReservationCreateController::store()` → `AssignmentService::createBooking()` (the one inventory write, row lock). **Four
  steps** (`NewReservation::steps()`: 1 Stay · 2 Guest · 3 Extras, only where the property has add-ons · 4 Confirm) are
  client-side Alpine (`newReservationWizard()`, walking `BOOT.steps`), fed by ONE JSON block (`id="vy-nr-boot"`): bookable room
  types keyed by id (units, plans, per-unit prices), extras, taxes and fees per stay currency, the calendar's nights, the
  property's today, which listed guests have an email (booleans only, #28) and the season dialogs' facts. The page never parses a
  figure back out of its own text. A refusal reopens at `NewReservation::STEP_*` (a step this property does not ask → Confirm).
- **The property:** a booking needs one. Under "All properties" the page asks (the app's own switcher, `app.property.switch`,
  which comes back by Referer); with several, the top bar has a "Booking into" picker. A property with no bookable unit gets
  one card — and the properties that CAN take a booking as one-click buttons.
- **The calendar** shows, per night, the main rate of the cheapest room still free, "—" when none has a price, "Full" when nothing
  is left (`nightlyCalendar()`: the availability rows and each room type's `primaryRatePlan()`, the property's currency, a year
  from the property's today; plain rows and a plain date range — no models, no `whereDate()`; `Availability::left()` the one
  formula). Read only when the steps render.
- **A missing price** is set by the Rates write itself (`RateActionController::apply`, `can:rates.manage`, `RateScope`) from
  hidden forms OUTSIDE the booking form (`id="vy-nr-price-N"`), joined to the visible field by `form=`;
  `return=RateActionController::RETURN_WIZARD` + `return_check_in/out` send the answer back (`toWizard()`).
- ⚠️⚠️ **A saved, unapplied season** that would price a night the stay lacks is offered (`savedSeasons()`), and applied only
  through the Rates page's own **"Apply this season?"** disclosure — one partial (`partials/season-apply-disclosure`), one builder
  (`App\Support\Rates\SeasonApplyFacts`, from `SeasonalRateService::preview()`), read by both pages. Its form posts
  `app.seasons.apply` (`SeasonActionController::apply()` — `rates.manage`, `RateScope`, the paused guard, R-SEASON untouched),
  which with the same return token comes back to the booking with the Rates page's own sentence and what the stay still lacks.
- **A refusal** comes back through `back()`: the reason, the step and the input — never `guest_email`, `guest_phone` or the
  override's fields (`NEVER_ECHOED`, #28); `restored()` re-checks each value against what the page offers now, and carries
  whether a restored returning guest has an email. A new guest the write just created is removed when the booking is refused —
  only when no booking points at it. The override reason and note are checked before anything is written.
- **The money** the summary shows uses the write's own rules: plan price, or the unit's own (`NightlyRate::stayTotalForUnit`),
  extras in the stay's currency, `FolioService`'s tax and fee formulas over the taxes and fees `StayEstimator` says the folio
  posts; "Paid now" never above the total (the write caps a payment at the balance). ⚠️ The shared `MoneyInput` reads a comma as
  thousands (D-MONEY-COMMA); the wizard refuses "150,50" before it is sent.
- **What it says** is derived, never assumed: house rules in the Settings words (`PropertyPolicy::ruleLabel()`, "Not set" where
  nothing was set) with the stay's free-cancellation date (`CancellationPolicy::freeUntilForCheckIn()`); how directions reach the
  guest (`arrivalNote()`: `MailSafetyGuard::isSending`, the pre-arrival template's on/off, `ArrivalPack::LEAD_DAYS`); the
  confirmation box only with an address; the done screen's self check-in code only for a guest with an email.
- **The draft:** sessionStorage, this tab only, `vyrobel:new-reservation:draft` **v4** (v3's step numbers meant other steps) —
  ids and counts, never a guest name, never payment, credit or the rules answer; the party size across a date change; the rest
  only for the same stay. The done screen clears it.

## 15 · The Calendar's New reservation dialog and its empty nights (`/app/calendar?new=1`, 2026-10-04)

- **The page** (`App\Livewire\ReservationRack`, render only) builds the dialog's data in `newReservation()` and the view
  includes `livewire/operator/partials/rack-new-reservation.blade.php` when it is open. ⚠️ **ONE guard:** it opens only for
  someone who may book (`reservations.create`) in a workspace that is not paused (`$canBook`, the toolbar button's own rule);
  `?new=1` alone opens nothing, and the dialog's reads run only when it is open.
- **Two phases, #20-safe:** "Check rack" is a GET (dates, adults, source, the picked unit, `kept=1`) → the free units from
  `UnitAvailabilityService::availableRoomTypes()` → Confirm submits the picked unit's hidden POST form to
  `ReservationCreateController::store()` → `AssignmentService::createBooking()`. No price is sent; the server re-derives it. A
  refusal (a stay rule, a unit just taken) lands on the full form with the booking kept (`back()`, §14).
- ⚠️⚠️ **The guest's name never rides the GET (#28).** The name boxes have no `name`; the dialog keeps them in this tab's
  sessionStorage (`vyrobel:rack-new-reservation:guest`, with a short fingerprint of the signed-in user) and restores them only
  when the address carries `kept=1` and the fingerprint matches; any other way in clears them. The done screen clears it too.
- **What it offers:** priced room types' free units, each at its OWN price when it has one (`NightlyRate::stayTotalForUnit`, one
  `UnitRateOverride` query for all) — what the write charges; room types with free units and no price (`waiting`, with
  `has_plan`) said, never dropped; a property with no rooms (`rooms`, `rooms_all`) one card. "Room only — the property's taxes
  and fees are added on the folio" when `StayEstimator` says the folio adds one in the stay's currency.
- **Dates:** the property's own today (`$bookFrom`, its timezone) is the first night offered and the column marked today. A
  check-out on or before check-in, a check-in before today, or more than `UnitAvailabilityService::MAX_NIGHTS` nights is named
  (`date_problem`) and nothing is offered.
- **The empty nights:** `freeNights()` gives each active unit row the nights a booking could start — from today, not under one
  of its bars or blocks, and only where the room type has a room to sell that night (the header strip's count). Each is a
  one-night `?new=1&ci=…&co=…&unit=…` link in a layer UNDER the bars (`.vy-lane-book`, z-index 0; the bars and blocks take
  pointer events, so drag and tooltips still work), out of the tab order and `aria-hidden`; the toolbar button is the keyboard
  way in. `unit` is honoured only when that unit is in the free list.
- ⚠️ **A double quote anywhere inside the dialog's `x-data` ends the HTML attribute** — comments included — and every binding
  fails while server-side tests stay green; `CalendarNewReservationTest` refuses one.
- **Second pass (2026-10-04, his "it is not possible to do reservation"):** a waiting room type (`waitingRoomType()`) carries what
  fixes it, for whoever holds `rates.manage`: a **Set price** form → `app.rates.update` for exactly the stay's nights on its main
  plan, and each saved, unapplied season that would price a missing night (`App\Support\Rates\SavedSeasonOffer`, shared with
  the New reservation page) → an **Apply view** inside the dialog (the shared `season-apply-disclosure` partial, then
  `app.seasons.apply`). Both send `return=calendar` + the return fields (`partials/calendar-return-fields`) and come back INTO the
  dialog through `App\Support\Operator\CalendarReturn::dialog()` — start, 7/14/30, filter, dates, adults, source, the clicked
  unit (`clicked`), `kept=1`; each part checked or dropped; never the guest's name — with `calendar_dialog_ok` / `_error`, shown in
  the dialog (on the page when the dialog cannot open, e.g. a paused workspace). The dialog adds the next step from what it shows.
  The clicked unit's room type is listed first. Set price and Apply are locked while the dates typed differ from the ones checked
  (`dirty`, as Confirm is); from the Apply view, Escape, the cross and a click outside step back to the booking (`close()`); each
  price box and button is named by its room type. The season's facts (a preview each) are built only for `rates.manage`.
- **A booking made here comes back here:** the per-unit forms send `return=calendar`; `ReservationCreateController` returns to
  `CalendarReturn::window()` with `folio_notice` and `calendar_created` ("Open it"). Refusals still open the full form (§14).
- **No rooms:** `others` (the dialog) and `elsewhere` (the Calendar's card, for any property with no unit that can take a booking —
  `noRooms()`: none added, or all paused or out of service; the grid stays under it when there are room types) are
  `App\Support\Property\BookableProperties::elsewhere()` — the person's properties with a bookable unit — as buttons to
  `app.property.switch` (this session's working property only).
- **The grid:** `pricedNights()` reads each room type's main-plan rates for the window in one query; a night from today with a
  room to sell and no rate is `no_price` (`is-noprice`, a dashed underline; the legend's "No price yet"; `priceNote()` names the
  room types and any unapplied season covering a marked night). `past` greys the nights before today. ⚠️ Drag-to-move (bars,
  drop layer, hint) only with `$canMove` = `reservations.assign` and a workspace not paused; a refused move sets
  `calendar_refused` (an alert). `rt` must be one of this property's room types or it is ignored, and every link keeps it.

## 16 · Messages: team chat, the live Inbox, the pulse (`/app/inbox`, `/app/inbox/team`, 2026-10-06)

- **Two sides, one toggle** (`partials/messages-toggle`): Guests (the Inbox — `guests.view`) | Team (every back-office user). The
  sidebar's Messages leads someone without `guests.view` to Team; its count is `.vy-menu-count` (⚠️ never a class with "av" in it
  plus a gradient — the avatar contrast sweep reads it as an avatar).
- **Team chat** — its own tables (`team_conversations`, `team_conversation_members` with `last_read_message_id`, `team_messages`);
  one writer `App\Services\TeamChatService`, one reader `App\Support\TeamChat\TeamChatReader` (the poll looks
  `LOOK_BACK_SECONDS` = 30 back from its cursor), one file rule `App\Support\TeamChat\TeamChatFiles` (private disk,
  `team-chat/{conversation}/{message}/`); routes `app.inbox.team*`; a support session gets a 404 everywhere and its bell hides
  team entries (`NotificationFeed::withoutPrivate()`).
- **The live Inbox** — `app.inbox.live` (a stamp), the page re-reading itself with `live=1` (reads nothing), `app.inbox.read`
  (the person's own read). Live parts: `data-ibx-live="stats|rail|head|thread"`; the thread part carries `data-thread-id` and
  `data-unread`.
- **The pulse** (`partials/live-pulse`, `window.VyLive`) on every back-office page: `app.pulse` → the bell's count
  (`NotificationFeed::unreadCount()`), team unread, guests unread (for `guests.view`); `?bell=1` re-renders the bell component.
- **Files** — one definition `App\Support\Messaging\ChatFiles` (types, `limits()` = this PHP's real ceilings, `displayName()` =
  the name's words + the extension its bytes earned); team files via `TeamChatFiles`, guest/owner files via
  `App\Support\Inbox\MessageAttachments` (`keep()` → send → `record()` for the Inbox's reply; `store()` for the owner portal);
  the guest's own door `guest.messages.attachment`. The recorder and the player: `partials/chat-media` (`window.VyRecorder`,
  `Alpine.data('vyVoice')`).
- **Notifications** — `TeamMessageNotification`, `InboxMessageNotification` (database only), written by
  `DispatchNotificationsForDomainEvent` on `team_chat.message_sent` / `inbox.message_received`; one entry per conversation.

## 17 · GitHub and its checks (2026-10-06)

- **The repository** `Subhankhan12/vyrobel` is **private** (read from the API, V 2026-10-06); default branch `main`. Pushed only at
  his word, under the four hard stops (`RULES.md` Part 1). Other branches on GitHub are audit and fix branches from earlier passes —
  all but `audit/operator-deep-parity` contained in `main`; seven merged local branches were never published and need not be.
- **The checks** — `.github/workflows/ci.yml`, on every push to any branch and on pull requests: job `image` builds or reuses the
  parity image (`Dockerfile.ci`, tagged by its hash, on ghcr.io); job `gates` runs IN that image with MySQL 8.4 and Redis 7 services —
  prepare (`.env` from `.env.example`, a key, fresh Passport keys, a second `vyrobel` schema) → Gate 0 the runtime contract (`ops/runtime-contract.sh --services`) → the
  assets (`npm ci`, `npm run build`) → Pint over the whole repo → PHPStan L5 → the full suite → the money gate (seeded at
  2026-09-01) → the marketing content, claims and cookie gates. Sparse checkout: `docs/design/front/` is never downloaded.
- ⚠️ **Container facts:** `run:` steps use `sh` unless told otherwise (hence `defaults.run.shell: bash` — without it no gate had ever
  run, `OPEN.md` D-CI-NEVER-RAN); the container runs as **root** (no `USER` in `Dockerfile.ci`), so a permission-based fixture
  proves nothing there; `ext-intl` is left out on purpose (country names fall back to codes, §7.5.1b); the `.env` is
  `.env.example`'s, so the template must be right for a new environment; no secret is available there (Stripe stays fake).
- **Reading it:** `gh run list --limit 5` · `gh run view <id>` (which step) · `gh run view <id> --log-failed` (the error).
- ✅ **Green end to end since 2026-10-06** (run 37503697885 on `693b68fc`, ~42 minutes: the suite alone ~35 — GitHub's machine is
  about twice as slow as this Mac). Before that it had never run a gate (`OPEN.md` D-CI-NEVER-RAN, closed).

## 18 · When a trial ends — the lapsed-workspace page (2026-10-09)

- **`EnsureSubscriptionActive`** on the `/app` group: a LAPSED organisation (a trial past `trial_ends_at`, or a cancelled / past-due
  subscription; `SubscriptionStatus::isLapsed()`, failing OPEN on no subscription) is closed. ⚠️ Only someone who can renew — by the
  PERMISSION `billing.manage` (the `Property Owner` wildcard, or any role granted it on the Roles page) — is sent to the Paywall
  (`/app/billing`); everyone else gets a plain 403 page ("This workspace's subscription has ended … ask your administrator") with
  the suspended page's actions (`errors.partials.suspended-actions`: Switch to another workspace, Sign out, Contact Vyrobel); a JSON
  request a JSON 403. The workspace switcher (`app.workspace.switch`) is let through. Before 2026-10-09 every role was sent to the
  Paywall, which refused ten of eleven roles, and the 403's "Back to the dashboard" looped (`OPEN.md` D-LAPSED-LOOP).
- **`App\Support\Platform\OtherWorkspaces::for()`** — the person's other active workspaces that are not suspended; ONE list, read by
  both the suspended and the lapsed page.
- ⚠️ Every production organisation was made in late September with a 14-day trial, so most have lapsed (`OPEN.md`
  D-INTERNAL-TRIALS). The internal test tenant's runs to 2027-03-31 (extended at his word, 2026-10-09). There is no "Extend trial"
  in the console: the extension was a guarded one-row write with an audit row (`subscription.trial_extended`).

## 19 · The shells' still chrome: the sidebar, the top bars, the bell (2026-10-09)

- **`<x-sidebar-position for key>`** (`components/sidebar-position.blade.php`), inline straight after each shell's sidebar: puts the
  scroll box back where this tab left it before the first paint (sessionStorage `vy.sidebar.<realm>`), brings the current item
  (`aria-current="page"`) into view when nothing is remembered, remembers on scroll / click / `pagehide`; `overflow-anchor: none` on
  the box and the exact (unrounded) value — both found as half- and one-pixel creeps per page in the walk. ⚠️ The box that scrolls:
  `.vy-side` (operator), `.vy-side .vy-nav` (owner portal — the menu INSIDE the sidebar), `.ad-side` (console).
- **No entrance animation on persistent chrome:** the three sidebars and the owner portal's top bar no longer slide in on every page
  (the operator's and the console's top bars never did). The page CONTENT still fades in.
- **The bell** (`components/notifications/bell-glyph.blade.php`, shared by the operator, owner and guest shells): the unread badge on
  the circle's OUTER top-right corner (`-5px` / `-6px` — the prototype put it inside, where a "9+" covered the bell); no shell sets
  `--vy-bell-badge-*`; the operator and owner top bars space their items 14px apart.

## 20 · Drop-downs: the one select engine (2026-10-10)

- **`components/vy-selects.blade.php`** — included once from the head of every shell that carries the scrollbar theme (19: operator,
  owner, guest, admin and its sign-in, public, the auth pages, onboarding, billing, oauth; not the error pages or Laravel's welcome).
  No Alpine (the guest portal and the public pages load none). ⚠️ **The native select stays in charge** — element, name, options,
  form, `x-model`, `onchange`, validation, tests. The engine changes only what is seen and clicked: a CSS skin on every closed select
  (no class added — a Livewire morph would strip it; the chevron with `!important` past a field's own `background` shorthand; the app's
  focus ring at zero specificity), and on a MOUSE press or a key the Vyrobel panel instead of the operating system's list. A finger
  keeps the phone's picker. A choice sets `selectedIndex` and fires `input` and `change`.
- **The panel:** a search above 8 options (never a list of plain numbers; `data-search` forces it, `data-search="off"` stops it);
  `<optgroup>` → quiet headings; `data-hint` (muted beside the label), `data-clock` (an IANA zone: the live time), `data-keywords`;
  a terracotta tick; as wide as its longest choice up to 420px (`data-panel-width` for a floor); upward without room below. Focus
  never leaves the select (APG select-only combobox): ↑/↓, Page Up/Down, Home/End, Enter, Esc (stopped — a dialog behind stays open),
  Tab, Backspace in a search. ⚠️ Inside `[aria-modal="true"]`, `[role="dialog"]` or `<dialog>` the panel is appended TO the dialog (its
  trap and click-outside see it as inside), positioned from a still probe when the dialog is the containing block. Opt out with
  `data-native` (none today; `SelectEngineTest` counts them). `window.VySelect` = `{ open, close, refresh }`.
- **The rich field** — `components/select.blade.php`: a real select (named by the page's own `<label for>`; the label's id rides as
  `data-labelledby` to name the list) with the hint and live clock drawn over its right side (`.vys-rich` / `.vys-deco`; an x-model
  change is followed every 0.4s, since x-model fires no event). Used by the profile (Language, Timezone), the Portfolio's two zone
  pickers, Units (property, room type) and Maintenance (unit). Timezones come from `App\Support\Time\TimezoneOptions::choices()`
  (city first, region groups, offsets, clocks, search words; a saved zone the list lacks is kept) beside the older `all()`.
- **The menus** (account menus, the bell's panel, the row ⋯ menus `.vy-gl-menu` / `.vy-ov-menu`, the owner statement and report
  pickers) take the panel's surface, rows, tint and opening motion from the engine's sheet; each keeps its own layout.
- ⚠️ **The console's own searchable drop-down** (ADMIN 05's field system, `admin/partials/fields`, `select[data-searchable]` → `ad-combo`:
  a checklist for `multiple`, flags for countries) is a SECOND engine with the same look — restyled from the engine's sheet (written
  `[class~="ad-combo-panel"]` so ADMIN 05's once-only count keeps counting its own block) and its card lifted while open (`OPEN.md`
  D-ADMIN-COMBO). The retired `<x-operator.combobox>` is gone.

## 21 · The 2026-10-21 UI polish components (favicon, date field, password field)

Four small UI passes on 2026-10-21 added three reusable Blade components. Each shipped live through `ops/release.sh` (favicon →
`rel_BH`, calendar room-type filter → `rel_BI`, insights date picker → `rel_BJ` with its stacking-context overlap fix → `rel_BK`,
security password show/hide → `rel_BL`), committed locally and **not pushed** (his push-at-the-end rule). All are views only — no DB,
no logic, no migration.
- **`<x-favicon>`** — `components/favicon.blade.php`: the one place the browser-tab icon is declared, so every surface carries the same
  terracotta "V". Emits an SVG `rel=icon` (`public/favicon.svg`, guarded `filemtime` cache-bust), the admin panels' inline data-URI as
  a no-file fallback, and an `apple-touch-icon`. Included in all browser `<head>`s (marketing, public storefront, operator, owner,
  guest, both admin panels, and every shared auth/error/status/oauth/billing front door). The admin panels' old inline favicon folded
  into it.
- **`<x-date-field>`** — `components/date-field.blade.php`: a themed, progressively-enhanced date picker used by the Insights custom
  range (`/app/insights`) and the Search check-in range (`/app/search`). The native `<input type="date">` stays the truth (name/value, submitted, no-JS fallback kept — D3-1); when
  Alpine runs it hides the native control and shows a colourful terracotta-gradient trigger pill + a custom calendar popover
  (gradient title, month nav, a terracotta-gradient selected day, a today ring, Today/Clear), writing the chosen ISO date back onto the
  native input. ⚠️ Its Alpine fn is an inline `@once <script>` because the operator layout has no `@stack('scripts')`. CSS `.vy-df*` /
  `.vy-ins-range` in the operator layout. ⚠️ Overlap lesson: the `.vy-fade` entrance animation leaves a `transform` on the toolbar and
  the KPI cards, each a stacking context — the popover's z-index was trapped; the fix raises the toolbar's context
  (`.vy-ins .vy-rack-toolbar:has(.vy-df-trigger.is-open) { z-index:60 }`) only while a picker is open.
- **`<x-password-field>`** — `components/password-field.blade.php`: a password input with a show/hide eye toggle, used by the five
  password fields on `/app/security` (change/set password: current + new + confirm; the sign-out-others and revoke-device dialogs).
  Defaults to `type=password`; an Alpine eye button toggles `:type` and swaps an open/closed eye icon. The input keeps name/required/
  autocomplete and submits unchanged (works with no JS); the eye is `type=button` + `tabindex=-1`, so it never submits and never fights
  the dialogs' focus traps. CSS `.vy-pw` / `.vy-pw-eye` in the operator layout. (The auth pages keep their own page-local `.vy-eye` +
  `data-toggle` reveal; this is the back-office equivalent.)
- The calendar room-type filter (`rel_BI`) resized its glass pill to 34px (level with the nights pill) and neutralised the select
  engine's forced sizing inside it while keeping its chevron/panel — CSS-only, `.vy-cal .vy-rack-filter` in the operator layout.

## 22 · The Security pages' kit — both pages, one design (2026-10-24)

His design (the `Owner Security.html` wireframe), on BOTH `/app/security` and `/owner/security`, each keeping only what is its own (the back
office: sign-in accounts, sessions with a per-device sign-out, the IP allowlist; the owner portal: connected apps, Deactivate).
- **What both pages read about the person — ONE class:** `App\Support\Security\SecurityOverview::for($user, $request, $approvalHere)` →
  the score, two-factor, recovery codes left, passkeys, trusted devices, login approval (`shown` / `on` / `available`) and sign-in problems.
- **The score — ONE rule:** `App\Support\Security\SecurityScore` — weights password 25 · two-factor 30 · recovery codes 15 · passkey 15 ·
  login approval 10 · login alerts 5 (=100). Only OFFERED protections count; they share the 100 by largest remainder, so the ring, "points
  available" and every "+N pts" are the same arithmetic. Tiers: Strong ≥ 85, Good ≥ 55, Basic. ⚠️ Login approval is offered only while
  email really goes out (`MailSafetyGuard::sendingNow()`), and login alerts not until Part 3 — so today's denominator is 85.
- **Sign-in problems:** `App\Support\Security\SignInProblems` — `login_activity` LOCKOUT rows (five wrong passwords in a minute, `LoginRequest`)
  on the person's own address, `guard = web` only, the 90 days the log keeps, after `users.security_alerts_seen_at` ("This was me",
  `SecurityAlertController@seen`, routes `app.security.alerts.seen` / `owner.security.alerts.seen`, self-only, audited
  `user.security_alert_dismissed`). Browser and time; the IP pending D-SX-IP. Never a place name.
- **The kit:** `components/security/{styles,icon,hero,boost,problems}.blade.php` — every class `vy-sx-*`, the sheet included by each page (its
  CSS ships in the page, so no comments in it, and never a component tag in the file outside its Blade comment). The shared two-factor
  section (`livewire/partials/two-factor-panel`) and passkey section (`x-auth.passkeys`) are rows of the "Signing in" card; their dialogs live
  at the PAGE ROOT (`two-factor-off-dialog`, `x-auth.passkey-remove-dialog`) — ⚠️ a card's `backdrop-filter` makes it the box a
  `position: fixed` dialog is placed in. The page's Alpine state opens them (`pwOpen`, `tfOff`, `pkRemove`, …).
- **Dialogs:** `.vy-sx-scrim` › `.vy-sx-dialog` (near-opaque, centred, `x-trap` on its own show expression, Escape); `autofocus` on the first
  field (Alpine's trap focuses it). `tests/Support/dialog_helpers.php`'s census knows `vy-sx-scrim`, so every one is under the dialog contract.
  Each password form posts `_form`; its error shows in its own dialog, once, and that dialog reopens.
- **Login approval** (owner page today; the back office in Part 3): switching ON is refused while email is not sent (D-LOGIN-APPROVAL-MAIL);
  OFF always works. The switch is `aria-disabled` (still reachable) with its reason beside it when it cannot be turned on.
- **Tests and mutations:** `SecurityRedesignTest`, `SecurityPassFiveTest` (rewritten to the new layout), mutation sets `security-redesign`
  (25) and `security-five` (22, re-aimed). ⚠️ The kit's class names are in the stylesheet on every page: assert the ELEMENT
  (`<section class="vy-sx-alert`), never the bare class.

---

## Sources

- **git:** commits `91b83801` `5d631d9f` `f6cd45a4` `6747d128` `8fb16054` `c31ea8c2` `9d33eee5` `a85a1b27`; status +
  diff (2026-09-27); for §11–§13 (2026-10-01): the working tree at `38ba2a0d` — `app/Support/{Guest,Maintenance,Unit,Rates,Owner,Inbox}/`,
  `app/Services/{OwnerInviteService,PayoutRunService}.php`, `app/Http/Controllers/{PublicMediaController,Operator/*}.php`,
  `bootstrap/app.php`, `routes/web.php`, `tests/Mutations/sets/`; `routes/web.php`, `routes/console.php`, `ops/release.sh`, `ops/public-checks.sh`, `config/`,
  `composer.json`, `.gitignore`, `.env.example` (names only), `app/Support/{Platform,Mail,Auth,Webhooks}/`; `.mcp.json` (shape);
  for §16 (2026-10-06): the working tree of the team chat pass — `app/Services/TeamChatService.php`,
  `app/Support/{TeamChat,Messaging,Inbox,Notifications}/`, `app/Http/Controllers/Operator/{TeamChat*,Pulse,InboxLive,InboxAction}Controller.php`,
  `app/Listeners/DispatchNotificationsForDomainEvent.php`, `resources/views/partials/{live-pulse,chat-media,messages-toggle}.blade.php`,
  `resources/views/livewire/operator/{team-chat,operator-inbox}.blade.php`, `routes/web.php`; for §18–§20 (2026-10-09/10):
  `app/Http/Middleware/EnsureSubscriptionActive.php`, `app/Support/Platform/OtherWorkspaces.php`,
  `resources/views/components/{sidebar-position,vy-selects,select}.blade.php`, `resources/views/components/notifications/bell-glyph.blade.php`,
  `app/Support/Time/TimezoneOptions.php`, the shells under `resources/views/components/`, `admin/partials/fields.blade.php`.
- **docs:** `CLAUDE.md` · `docs/DEPLOYMENT.md` · `docs/KNOWN-LIMITS.md` (L12g/h/k/m/p, L13–L20) · `docs/DECISIONS.md`
  (ADMIN 01 P1–P5, TONIGHT 01, ADMIN 04/05/06) · `docs/EMAIL-PRODUCTION.md` · `docs/GOOGLE-SIGN-IN.md` ·
  `docs/OAUTH-DESIGN.md` · `docs/STRIPE-PRODUCTION.md` · `docs/GO-LIVE-REQUIREMENTS.md` · `docs/STATE.md` ·
  `docs/THE-WALK-2026-09-23.md` · `docs/AUDIT-PHASE-0.md` §4a · `docs/memory/briefs/TONIGHT-01.md` (a brief).
