# Go-live runbook (2026-08-26)

⚠️ **Nothing in this file has been performed.** No server, no domain, no credentials, no live keys.

⚠️⚠️ **READ THIS BEFORE STEP 1 — THE PAYMENT PATH HAS NEVER RUN AGAINST REAL STRIPE.**
`StripeSafetyGuard` refuses a live key outside `production`, **so production is the first place it ever
executes for real.** Everything to date ran against `FakeStripeClient`. That is why the money switch is the
**last** thing flipped, after every other part of the system is proven up — and why Phase 4 is deliberately
a small real charge you refund yourself, not a customer's booking.

⚠️ **Where the honest answer is unknown, this file says UNKNOWN.** A runbook that guesses is worse than one
with a gap, because a gap gets checked.

**Legend:** 🧑 = the founder does this by hand · ⚙️ = a command or the deploy script does it.

---

## Phase 0 — Before touching a server

| | step | how to know it worked |
|---|---|---|
| 🧑 | Work through ⛔ BLOCKING items **B1–B15** in `docs/GO-LIVE-REQUIREMENTS.md` and gather every value | Every blank in `.env.example` that applies has a real value ready. |
| 🧑 | ⚠️ **B12/B13 — the lawyer.** The DPA and the flow-of-funds question. Hand them `docs/DATA-RESIDENCY-INVENTORY.md` and `docs/CONNECT-TARGET-DESIGN.md` §A5 | You have written answers. ⚠️ **Nobody in this repository can do this step.** |
| 🧑 | Provision the host, domain, TLS, web server / PHP-FPM | `curl -I https://<domain>` returns a TLS handshake and an HTTP status. ⚠️ **The deploy script never configures any of this — it assumes it exists.** |
| 🧑 | Install Redis and confirm it starts | `redis-cli ping` → `PONG`. ⚠️ **The deploy only warns if Redis is down, and the first symptom is queued work silently never running.** |

## Phase 1 — First deploy, money OFF

⚠️ Keep `PAYMENTS_GATEWAY=manual` and use **test** Stripe keys (or none) for this whole phase.

| | step | how to know it worked |
|---|---|---|
| 🧑 | Place `.env` with `APP_ENV=production`, `APP_DEBUG=false`, a generated `APP_KEY`, DB credentials, `BACKUP_PASSPHRASE`, `SESSION_SECURE_COOKIE`, `FORCE_HTTPS`, `TRUSTED_PROXIES` | `php artisan about` runs and shows environment `production`. |
| 🧑 | `php artisan passport:keys` (B15) | The key files exist and are **not** in git. |
| ⚙️ | `BACKUP_PASSPHRASE='…' ops/deploy.sh` | Reaches **"Deploy complete"**. ⚠️ Step 1 exits 2 on a missing `.env`, empty `APP_KEY`, `APP_DEBUG=true`, or an unreachable database — those are loud. |
| 🧑 | ⚠️ Read the step-10 output | It must say the cron is **present** or that it **installed** it. ⚠️ **If you set `DEPLOY_NO_CRON_INSTALL=1`, the deploy prints a red line and STILL COMPLETES** — and the GDPR deletion and ID purge will never run. |
| ⚙️ | Verify the schedule | `php artisan schedule:list` shows **8 commands**, each with a next-due time. |
| 🧑 | Start Horizon (however you supervise it) | The Horizon dashboard is reachable and shows the queues; `php artisan queue:failed` is empty. |
| 🧑 | ⚠️ Confirm the health check passes | Step 12 already asserted 200. If it exited 1, the app is **up but not healthy** — roll back with the command the script printed. |

## Phase 2 — Prove the parts that have never run for real

⚠️ **Each of these has only ever run in tests.** Do them deliberately, in this order.

| | what has never run | do this | ⚠️ first real failure looks like |
|---|---|---|---|
| 🧑 | **Mail delivery** | Trigger one real transactional email (e.g. invite yourself to the org) | ⚠️ **Silent** — a missing Postmark token or unverified sending domain means nothing arrives and no user sees an error. Check Postmark's activity feed, not the app. |
| ⚙️ | **The scheduler + its 8 commands** | Wait for one natural tick, then check the log | ⚠️ ⚠️ **Silent and DELAYED** — a broken retention job produces no error and no failed job; you find out when data that should have been purged is still there. |
| 🧑 | **Queue workers** | Cause one queued job and watch Horizon | **Loud in Horizon, silent in the app** — the user sees success while nothing happens. |
| 🧑 | ⚠️ **File storage, guest ID documents** | Complete one online check-in with a document | **Loud** if the disk is unwritable. ⚠️ Verify it landed in `storage/app/private` and is **not** reachable by URL. |
| 🧑 | **Webhook endpoints from the real internet** | Use Stripe's dashboard to send a test event to **both** `/webhooks/stripe` **and** `/webhooks/stripe/connect` | ⚠️ **Silent** — a wrong secret means events are rejected with a 400 that only Stripe sees. **This is payment-taken-booking-unpaid by a second route.** Check Stripe's webhook delivery log shows 2xx. |

## Phase 3 — Operator onboarding (still no live money)

| | step | how to know it worked |
|---|---|---|
| 🧑 | Enable Connect on the Stripe account, Standard accounts (B9) | Connect appears in the Stripe dashboard. |
| 🧑 | On **Billing → Card acceptance** in the app, start setup for your own org | The panel moves from "Not set up" to "Started — not finished", then to "Verified — cards can be taken" once Stripe clears it. ⚠️ **"Submitted — Stripe is reviewing" is NOT ready** — Stripe has the form but has not enabled charges. |
| 🧑 | ⚠️ Note the country warning **before** you create the account | It cannot be changed afterwards. |
| 🧑 | ⚠️ Check the statement descriptor in **your own Stripe dashboard** | ⚠️ Vyrobel **cannot** set it. A guest who books one name and sees an unfamiliar legal entity disputes the charge. |

## Phase 4 — ⚠️⚠️ THE MONEY SWITCH — LAST, AND ONLY WHEN EVERYTHING ABOVE IS PROVEN

⚠️ **This is the first time this code path executes against real Stripe.** Do it with your own card, on a
booking you create, outside business hours.

| | step | how to know it worked |
|---|---|---|
| 🧑 | Put the **live** keys in `.env`: `STRIPE_KEY`, `STRIPE_SECRET`, and **both** `STRIPE_WEBHOOK_SECRET` and `STRIPE_CONNECT_WEBHOOK_SECRET` (B7, B8) | `StripeSafetyGuard` permits a live key only when `APP_ENV=production`. |
| 🧑 | Set `PAYMENTS_GATEWAY=stripe` (B10) | ⚠️ **This is the switch.** |
| ⚙️ | Re-run the deploy (or at minimum `php artisan config:cache`) | "Deploy complete". |
| 🧑 | ⚠️ **Do NOT run the seeder.** | ⚠️ Under `stripe` the seeder **aborts loudly** with `OperatorNotReadyForPayments`. **That is the guard working, not a bug to work around.** Production is never seeded. |
| 🧑 | **Test 1 — a small real charge.** Make a booking on your own storefront and pay it with your own card | The guest is redirected to Stripe's hosted page. ⚠️ **Then check the folio: it must show the payment as paid.** If Stripe shows a successful charge and the folio does not, **the webhook did not arrive** — check Stripe's delivery log and `STRIPE_CONNECT_WEBHOOK_SECRET`. **This is the failure to look for.** |
| 🧑 | ⚠️ **Confirm the money landed in the OPERATOR's Stripe balance, not Vyrobel's** | The charge appears on the **connected account**, and Vyrobel's platform balance is unchanged. That is the whole point of direct charges. |
| 🧑 | **Test 2 — refund it.** Refund that payment from the app | The folio shows the refund, and Stripe shows it on the **same connected account**. ⚠️ If the refund shows as **pending**, that is expected when the connected balance is empty — the folio should say a refund is in progress and the remaining refundable amount should DROP. It settles on the `refund.updated` webhook. |
| 🧑 | **Test 3 — the guest's card statement** | ⚠️ **UNKNOWN until a real statement appears** (days). Check what the descriptor reads and correct it in the operator's Stripe dashboard if it is not recognisable. |

## Phase 5 — Advisable, after you are live

Work through the ⚠️ ADVISABLE list (A1–A7) in `docs/GO-LIVE-REQUIREMENTS.md` — real tax rates, real plan
prices, an offsite backup target, and ⚠️ **a restore drill** (`ops/restore-drill.sh`), because an unrehearsed
backup is a hypothesis.

---

## ⚠️ Rollback

`ops/deploy.sh` prints the exact command with the previous SHA at both the health-check failure and on
success. ⚠️ **A code rollback does NOT undo a migration** — the schema stays new. Restoring the database is
a separate, deliberate decision that discards everything written since the backup.
