# Go-live requirements — the single list (2026-08-26)

⚠️ **Why this file exists.** These were recorded across a dozen passes and lived only inside reports.
**A requirement nobody can find is a requirement nobody meets.** This is the one place to look.

⚠️ **Nothing here has been done.** No credentials, no server, no domain, no live keys, no dashboard
actions — those are the founder's and cannot be performed from this repository.

## ⛔ BLOCKING — the product cannot correctly serve a paying customer without these

| # | requirement | who | if skipped |
|---|---|---|---|
| B1 | **Server, domain, TLS, web server / PHP-FPM** | founder | ⚠️ `ops/deploy.sh` **does not configure any of these** — it assumes they exist. The health check at step 12 is the first thing that notices. |
| B2 | **`APP_KEY`** generated and kept | founder | ⚠️ **Encrypted guest PII is unreadable without it.** Deploy exits 2 if empty. Losing it later is unrecoverable. |
| B3 | **`BACKUP_PASSPHRASE`** set | founder | Deploy exits 2. A migration with no restorable snapshot has no way back. |
| B4 | **Redis running** (Horizon) | founder | ⚠️ **The deploy only warns.** First symptom is queued work silently never running. |
| B5 | **The `schedule:run` cron** | deploy installs it | ⚠️ Skippable via `DEPLOY_NO_CRON_INSTALL=1`, which **warns and completes**. Without it the **14-day guest deletion and 30-day ID purge never run — silently.** A GDPR obligation. |
| B6 | **Postmark token + sending domain DNS** | founder | No transactional email at all: no booking confirmations, no invites, no password resets. |
| B7 | **Stripe live keys** (`STRIPE_KEY`, `STRIPE_SECRET`) | founder | No card can be taken. ⚠️ `StripeSafetyGuard` refuses a live key outside `production` — see R1 below. |
| B8 | **Both webhook signing secrets** — `STRIPE_WEBHOOK_SECRET` **and** `STRIPE_CONNECT_WEBHOOK_SECRET` | founder | ⚠️ **Two separate endpoints with separate secrets.** A wrong or missing secret means events are rejected — **payment taken, booking unpaid.** |
| B9 | **Stripe Connect enabled on the platform account**, Standard accounts | founder | Operators cannot onboard, so **nobody can take any card** — all three payment paths charge on the connected account. |
| B10 | **`PAYMENTS_GATEWAY=stripe`** | founder | Cards stay on the manual (offline) rail. ⚠️ **This is the real money switch — see the runbook; it is LAST.** |
| B11 | **Stripe Price IDs** for the four subscription plans | founder | Subscription checkout has nothing to charge against. See `docs/BILLING-STRIPE-PRICES.md`. |
| B12 | **A Data Processing Agreement (Article 28)** | ⚠️ **a lawyer — nobody in this repo** | Vyrobel acts as processor for guest data. The sub-processor list it needs is `docs/DATA-RESIDENCY-INVENTORY.md`. |
| B13 | **The flow-of-funds legal question** | ⚠️ **a lawyer — nobody in this repo** | Written for one sitting in `docs/CONNECT-TARGET-DESIGN.md` §A5. Direct charges were chosen as the conservative answer, but the question is not closed. |
| B14 | **`SESSION_SECURE_COOKIE`, `FORCE_HTTPS`, `TRUSTED_PROXIES`** | founder | Sessions over plain HTTP, or broken redirects behind a proxy/load balancer. |
| B15 | **Passport keys** (`php artisan passport:keys` per environment) | founder | The OAuth API cannot issue or verify tokens. ⚠️ The private key is gitignored and must never be committed. |

## ⚠️ ADVISABLE — not blocking, but each has a real cost if skipped

| # | requirement | who | if skipped |
|---|---|---|---|
| A1 | **Real tax rates** (`tax:set-rate` per jurisdiction) | founder | The seeded rates are clearly-labelled EXAMPLES. Outputs stay honestly "not configured" rather than wrong — but no VAT figure is usable. |
| A2 | **Replace the PLACEHOLDER plan prices** in `config/billing.php` | founder | Marketing and checkout show placeholder figures. `MarketingPricing::isPlaceholder()` exists so a page can say so. |
| A3 | **Tell operators to check their statement descriptor** | founder (guidance already on Billing → Card acceptance) | ⚠️ Vyrobel **cannot** set it — on a Standard account with direct charges it is the operator's own. A guest who books one name and sees an unfamiliar legal entity **disputes the charge**. |
| A4 | **Decide on Accounts v2 / controller properties** | founder | ⚠️ Stripe now marks Standard/Express/Custom **legacy** and points new platforms at Accounts v2. This integration uses v1 `type: 'standard'`. Consistent today; worth a decision before scale. |
| A5 | **Offsite backup target** (`AWS_*` / S3) | founder | Backups stay on the same host they protect against losing. |
| A6 | **Verify the §7.2 heartbeat exists** | founder / next pass | ⚠️ **UNKNOWN** — the deploy script's comments refer to it as the thing that catches a silently-missing cron. I did not verify it is wired. |
| A7 | **A restore drill** (`ops/restore-drill.sh`) before relying on backups | founder | An unrehearsed backup is a hypothesis. |

## 🚫 Known limits to state to customers, not fix

| limit | detail |
|---|---|
| **One Connect account per organisation** | ⚠️ A Connect account's **country cannot be changed after creation**, `organizations.country` is nullable and `properties` has **no** country column. An operator with properties in several countries is not modelled. The Billing screen warns **before** the account is created. |
| **Channel sync is STOPPED** | Not switched on; no availability or rates are pushed anywhere. |
| **No guest self-serve export/erasure** | Erasure and export are run by the operator from the guest record. A data-subject-rights gap, recorded. |
| **Fixed retention windows** | 14-day guest deletion, 30-day ID purge, 90-day activity pruning — set in code, not per-tenant. ⚠️ Statutory registration-retention periods differ by jurisdiction. |
| **No org-wide 2FA enforcement** | Each person enables it for themselves. |
