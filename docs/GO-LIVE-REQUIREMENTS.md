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
| B17 | ⚠️ **Verify every owner's payout destination BEFORE enabling the Stripe Connect rail.** With `PAYOUTS_RAIL=manual` a payout destination moves no money, so a destination corrupted while the rail was off sits **harmless and invisible** — and goes **live the moment the rail is switched on**. | founder | ⚠️ **A staged redirect.** Changing a destination now requires the owner's password and raises an always-on account-security notification, so a *new* corruption is hard and loud. That does not retroactively validate destinations recorded **before** those controls existed. Before flipping `PAYOUTS_RAIL`, confirm each owner's default account against `audit_events` (`owner.payout_method_added` / `_default_changed` / `_removed`) — the ledger is intact and unpruned by design (see `DATA-RESIDENCY-INVENTORY.md` §3). |
| B16 | **Web-server host pinning** — the vhost must answer ONLY the canonical host (nginx: a `server_name` match plus a `default_server` that returns 444; Apache: a default vhost that refuses). And **`APP_URL` must be the exact canonical origin**, scheme included. | founder | ⚠️ **A password-reset email's link is built from `APP_URL` (audit H-1). If `APP_URL` is wrong, every emailed link points at the wrong host.** The app now REFUSES an unexpected `Host` (`trustHosts()`), so a permissive vhost no longer poisons a link — but a *wrong* `APP_URL` still sends every reset, invite and claim link to the wrong place. ⚠️ `ops/deploy.sh` pre-flight refuses an empty or relative `APP_URL`. **Verify with the curl in `DEPLOYMENT.md` §7.7.** |
| B15 | **Passport keys** (`php artisan passport:keys` per environment) | founder | The OAuth API cannot issue or verify tokens. ⚠️ The private key is gitignored and must never be committed. |
| B18 | **Google sign-in credentials** (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`) — the console steps are in `docs/GOOGLE-SIGN-IN.md` | founder | ⚠️ **NOT blocking, and safe to skip: with both empty the feature is entirely absent** — no button, no route, no error page. It is listed here only so the credentials sit with the other founder-entered secrets. ⚠️ Three things cannot be tested without a real account and must be checked by hand at go-live: that Google accepts the client and each redirect URI, that the consent screen names Vyrobel and requests only `openid email profile`, and that one real sign-in completes end to end. |

## ⚠️ ADVISABLE — not blocking, but each has a real cost if skipped

| # | requirement | who | if skipped |
|---|---|---|---|
| A1 | **Real tax rates** (`tax:set-rate` per jurisdiction) | founder | The seeded rates are clearly-labelled EXAMPLES. Outputs stay honestly "not configured" rather than wrong — but no VAT figure is usable. |
| A2 | **Replace the PLACEHOLDER plan prices** in `config/billing.php` | founder | Marketing and checkout show placeholder figures. `MarketingPricing::isPlaceholder()` exists so a page can say so. |
| A3 | **Tell operators to check their statement descriptor** | founder (guidance already on Billing → Card acceptance) | ⚠️ Vyrobel **cannot** set it — on a Standard account with direct charges it is the operator's own. A guest who books one name and sees an unfamiliar legal entity **disputes the charge**. |
| A4 | **Decide on Accounts v2 / controller properties** | founder | ⚠️ Stripe now marks Standard/Express/Custom **legacy** and points new platforms at Accounts v2. This integration uses v1 `type: 'standard'`. Consistent today; worth a decision before scale. |
| A5 | **Offsite backup target** (`AWS_*` / S3) | founder | Backups stay on the same host they protect against losing. |
| A6 | ~~Verify the §7.2 heartbeat exists~~ | ✅ **VERIFIED 2026-08-31** | **It is wired** — two every-minute entries in `routes/console.php` (`ops-scheduler-heartbeat`, `ops-queue-heartbeat`) feeding `/up/liveness`. ⚠️ **But it certifies CRON LIVENESS ONLY.** A scheduled command that throws writes one error log line, `schedule:run` still exits 0, the heartbeat still beats and `/up/liveness` stays **200**. ⛔ Founder steps remain: set `OPS_HEARTBEAT_PING_URL` and point a monitor at `/up/liveness`; and to catch a *failing* command you need log alerting or an `onFailure()` hook. |
| A8 | **Structured data — the five types REFUSED until their values exist** | founder | ⚠️ Only `FAQPage` ships today (`/pricing`), because it needs nothing but Q&A already visible on the page. Each of these was refused rather than guessed, and machine-readable values are worse than on-page ones because a search engine may reprint them to someone who never reaches the page: **`Organization`** needs a real `url` (APP_URL is still `localhost`) and a `logo` file (`public/` has none) · **`Product`/`Offer`** needs a real `price` — `MarketingPricing::isPlaceholder()` is TRUE, so today it could only publish an unset number (see A2) · **`SoftwareApplication`** needs `offers`, `aggregateRating` or `review`; the first is that placeholder and the other two do not exist — a rating would be a fabricated review · **`WebSite`** and **`BreadcrumbList`** both need URLs, so both wait on the domain. ⚠️ `MarketingStructuredDataTest` reds if any of them appears before its value is real. Also waiting on the domain: `canonical` and `og:url`, which are already correct in shape and only carry the placeholder host. |
| A7 | **A restore drill** (`ops/restore-drill.sh`) before relying on backups | founder | An unrehearsed backup is a hypothesis. |
| A9 | **Re-verify the OTA terminal-modification refusal against a REAL channel** (F-29 / FIX 14, 2026-09-13) | founder, at Channex cutover | ⚠️ The guard is proven by row state against the SERVICE, driven directly with a realistic payload — **but `CHANNEX_FAKE=true` and there is no live channel, so the full webhook→job→service path has never run end to end.** A fake proving a fake is not proof. **What is unverified is the TRANSPORT, not the rule**: that a real Channex `booking_modification` for a cancelled booking arrives, is refused quietly, leaves the `reservation.ota_modification_refused` audit row, and that Channex does **not** treat the 2xx acknowledgement as a reason to re-send. ⚠️ **Also confirm what a real channel does when we accept but do not apply a modification** — the refusal is correct for us and may leave the channel believing a change landed. |

## 🚫 Known limits to state to customers, not fix

| limit | detail |
|---|---|
| **One Connect account per organisation** | ⚠️ A Connect account's **country cannot be changed after creation**, `organizations.country` is nullable and `properties` has **no** country column. An operator with properties in several countries is not modelled. The Billing screen warns **before** the account is created. |
| **Channel sync is STOPPED** | Not switched on; no availability or rates are pushed anywhere. |
| **No guest self-serve export/erasure** | Erasure and export are run by the operator from the guest record. A data-subject-rights gap, recorded. |
| **Fixed retention windows** | 14-day guest deletion, 30-day ID purge, 90-day activity pruning — set in code, not per-tenant. ⚠️ Statutory registration-retention periods differ by jurisdiction. |
| **No org-wide 2FA enforcement** | Each person enables it for themselves. |

---

## ⚠️ FIRST-DEPLOY CHECKLIST — proxy, host and scheme

⚠️ **Nothing below can be proven before a server exists.** The configuration is correct and tested against
forged headers (`ProxySchemeAndAssetOriginTest`), but *that a real proxy sends those headers* is not
something a test can establish. These five checks take about two minutes in a browser and cover the failure
this project has already had once — a cross-origin `asset()` URL that the CSP refused, killing every script
behind a single console error.

| # | check | how | what a failure looks like |
|---|---|---|---|
| 1 | **Every page loads over `https://` with no mixed-content warning** | open `/`, `/pricing`, `/login` | the padlock is broken, or scripts are refused |
| 2 | **The console is CLEAN** — no CSP violation | DevTools → Console | `Refused to load … because it violates … 'self'` — the exact incident |
| 3 | **Assets are SAME-ORIGIN** | DevTools → Network: every `.js`/`.css` host matches the page host and port | a different host or port means `ASSET_URL`/`APP_URL` disagree |
| 4 | **An emailed link points at the canonical host** | trigger a password reset; read the link in the mail | `http://localhost/…` or an internal hostname means `APP_URL` is wrong |
| 5 | **A signed link actually opens** | click the reset link, and separately a guest-claim link | *"This link is invalid"* — indistinguishable from expired, and it is `APP_URL` |

⚠️ **What the server itself must guarantee for `TRUSTED_PROXIES` to be safe:** the application must be
**unreachable except through the proxy**. If the app port answers directly, anyone who reaches it can forge
`X-Forwarded-Proto` and `X-Forwarded-Host` and the app will believe them. Bind the app to loopback or a
private interface, or firewall the port — and set `TRUSTED_PROXIES` to the proxy's address rather than `*`
unless that isolation is certain. See **B16** for the vhost half of the same requirement.

⚠️ **Cannot be proven without the server, and therefore not claimed:** that the proxy sends the forwarded
headers at all; that TLS terminates where expected; that the CSP header survives the proxy unmodified; that
the certificate covers the canonical host.
