# Live Stripe (Phase 8) — the go-live runbook

> ## ⛔⛔ NO LIVE KEY UNTIL THESE REFUSALS ARE LIVE (ADMIN 06, 2026-09-27)
>
> Before a live key (`sk_live_…`) or a live webhook secret goes into production's `.env`, **read the live release's
> `.release`** (never type it) and confirm the commit contains ADMIN 06. Until then, deleting an organisation could run
> over money that is still moving. A permanent deletion refuses while any of the following holds, and **it never calls
> Stripe**. Closing anything at Stripe stays the founder's job, by hand, in Stripe's dashboard. Each refusal is proved by a
> test in `tests/Feature/Admin/Admin06DeleteMoneyAndCountTest.php`, and each test by a mutation in
> `tests/Mutations/sets/admin-06.php`:
>
> | The deletion refuses while… | Test |
> |---|---|
> | the organisation has **live-mode** money records: a card payment, a refund, a payout, or live Stripe billing and its invoices. **A hold pending the founder's lawyer, not a policy** (`DECISIONS`, ADMIN 06) | *#2 — LIVE-mode money records are never deleted…* · *a REFUND takes its payment's mode…* · *livemode is STRIPE'S OWN…* |
> | its Stripe subscription is in any state but cancelled | *refuses while Stripe billing is live in ANY state but cancelled* |
> | a Stripe **Checkout page** for it is still open (B19) — a subscription page, or a guest's card-payment page | *B19 — an open Stripe Checkout page refuses the deletion…* · *B19 — a page past its expiry no longer refuses…* · *R2-4 — B19, the GUEST half…* |
> | a **live** Checkout page is past its expiry but Stripe has **not reported** whether it was paid | *R2-2 — a LIVE payment page past its expiry that Stripe has not reported on still refuses…* |
> | a card payment, a card hold or a refund is still open at Stripe | *refuses while a card payment, a card hold or a refund is still open at Stripe* |
> | any card **dispute** on one of its payments is not finished — a status Stripe adds later counts as open (each tracked by Stripe's own id, so a late or redelivered event never re-opens a finished one). ⚠️ A dispute on its own **subscription** charge cannot be linked to it and does not refuse (GO-LIVE B19) | *refuses while ANY card dispute is open…* · *a late or redelivered dispute event never re-opens a finished dispute…* · *a dispute on a payment that still exists is stored on it…* · *R2-5 — two disputes on ONE payment…* · *R2-7 — a dispute status this code does not know counts as OPEN…* |
> | a payout is in transit, still owed, or on hold | *refuses while a payout is in transit, owed or on hold…* |
> | a payout run is in progress | *refuses while a payout run is in progress* |
> | a webhook for it is still being processed | *refuses while a webhook for it is still being processed* |
> | **another organisation's** records point into it — a cascade would delete them too | `Admin05DeleteOrganisationTest` › *refuses when another organisation's records point into this one…* |
> | the database links records **deeper** than the confirmation's count can follow (so it could not say what goes) | *the count follows ONE level of cascade…* · *R2-10 — the premise refuses a table the plan clears DIRECTLY…* |
>
> *(The last two rows were added 2026-09-29 by ADMIN 06's second review, D-R2-16: the refusals existed, and this list
> left them out.)*
>
> And after a deletion, a correctly signed Stripe event for the organisation, however late (a dispute can come months
> after the charge), is **recorded and answered 2xx on both endpoints**, never dropped. It is `failed` where the event
> shows it was ours, and `ignored` with its reason where it names no organisation of ours — or is a Connect **account**
> update, which nothing here acts on (an operator's own account carries its organisation's id, and must not turn the daily
> reconciliation red). An unsigned one still gets a 400. Tests: *a signed event for an organisation that no longer exists
> is RECORDED…* · *an `ignored` event is still taken over and applied…* · *R2-1 — an OPERATOR's own Connect account update
> is routine…* · *R2-15 — an event type neither plane acts on…*. ⚠️ **The endpoints must be subscribed to the ADMIN 06 events** (§5 and §6 below), or none of
> this receives anything.
>
> ⛔ **Separately, before relying on guest card payments: GO-LIVE B20 (`KNOWN-LIMITS` L20).** Under the SDK's pinned API
> version, a guest's card payment page cannot open with real Stripe (📖 read from Stripe's docs, not yet run). Confirm it
> with one test-mode session first.

Live Stripe is wired **test-mode-first** across all three seams — subscription billing, the guest card charge,
and the owner payout rail (Connect) — and proven against the `FakeStripeClient` (no network) + real Stripe
signature verification. **No live key touches dev/test/CI** (`StripeSafetyGuard` refuses `sk_live_` outside
`production`). This file is the runbook: what is code (done) and what is **Subhan's** final go-live step.

---

## 1. The three seams (all TEST-MODE-proven, webhook-driven)

| Seam | Flow | Webhook (source of truth) | Idempotency |
|---|---|---|---|
| **Subscription billing** | subscribe → Checkout Session (subscription) → Stripe | `checkout.session.completed` / `customer.subscription.*` / `invoice.*` → active/past_due/canceled | create key + `event.id` |
| **Guest card charge** | Pay by card → Checkout Session (payment) → Stripe | `payment_intent.succeeded` → folio paid (reconciles) | create key + `firstOrCreate(gateway_ref)` + `event.id` |
| **Owner payout (Connect)** | payout run → Transfer to the owner's connected account | `transfer.created` → payout `processing → paid` | transfer key + **DB row-lock** + `event.id` |

**Iron rules followed:** the card never touches the server (hosted Checkout — PCI SAQ A); fulfilment is on the
**webhook, not the redirect**; every webhook is **signature-verified against the raw body**; **two/three-layer
idempotency** eradicates double-charges/activations/transfers structurally; the **payout can't double-transfer**
(unique idempotency key **+** a `lockForUpdate` row-lock on the payout, proven to double *without* and once
*with*); a payout only ever goes to **its own owner's** connected account.

**Two DISTINCT webhook endpoints, each with its own signing secret:**
- `/webhooks/stripe` — the ACCOUNT webhook (`STRIPE_WEBHOOK_SECRET`): payments + subscriptions.
- `/webhooks/stripe/connect` — the CONNECT webhook (`STRIPE_CONNECT_WEBHOOK_SECRET`): owner accounts + transfers.

---

## 2. Secrets — ENV only (never committed)

Set in **production** only (a secrets manager / host env). `.env.example` documents the keys with no values.

```
STRIPE_FAKE=false                       # use the real SDK client (StripeSafetyGuard still blocks a live key outside production)
STRIPE_KEY=pk_live_…                     # publishable
STRIPE_SECRET=sk_live_…                  # secret — LIVE
STRIPE_WEBHOOK_SECRET=whsec_…            # the account endpoint's signing secret
STRIPE_CONNECT_WEBHOOK_SECRET=whsec_…    # the CONNECT endpoint's signing secret (distinct)
PAYMENTS_GATEWAY=stripe                  # route guest/folio charges through Stripe
PAYOUTS_RAIL=stripe_connect              # route owner payouts through Connect transfers
# Subscription Price ids (create the products/prices in the Stripe dashboard, live-mode):
STRIPE_PRICE_ESSENTIAL_MONTHLY=price_…   STRIPE_PRICE_ESSENTIAL_YEARLY=price_…
STRIPE_PRICE_PRO_MONTHLY=price_…         STRIPE_PRICE_PRO_YEARLY=price_…
```

---

## 3. ⚠️ THE LIVE-KEY FLIP — Subhan's final go-live step (NOT done in the build)

Do this **once**, in production, per Stripe's go-live checklist:

1. **Activate** the Stripe account (business details, bank account) — live mode.
2. **Products & Prices** (live): create the Essential/Pro monthly+yearly prices → put the `price_…` ids in the
   ENV above. (Confirm the real amounts with the placeholders in `config/billing.php`.)
3. **Connect**: enable Connect (Standard), set the platform branding + the payout/onboarding settings.
4. **Webhook endpoints** (live): register BOTH —
   - `https://<host>/webhooks/stripe` → events: `checkout.session.completed`, `customer.subscription.created|updated|deleted`, `invoice.paid`, `invoice.payment_failed`, `payment_intent.succeeded`, `payment_intent.payment_failed`, `payment_intent.canceled`, `refund.updated`, `refund.failed`, ⚠️ ADMIN 06: `checkout.session.expired` and the five `charge.dispute.*` (§5 below has the full list). Copy its signing secret → `STRIPE_WEBHOOK_SECRET`.
   - `https://<host>/webhooks/stripe/connect` → events: the full list in §6 below (`account.updated`, `transfer.*`, `payment_intent.*`, `refund.*`, and ⚠️ ADMIN 06: `checkout.session.completed|expired` and the five `charge.dispute.*`). Copy its signing secret → `STRIPE_CONNECT_WEBHOOK_SECRET`.
5. **Flip the ENV** (section 2) in production and deploy. `StripeSafetyGuard` now allows the live key because
   `APP_ENV=production`.
6. **Smoke test with real money, then reverse it** (Stripe's advice): make a **$1 real-card** subscription/charge
   → confirm the webhook activated/settled → **refund** it. For payouts, connect a real owner account and make a
   small test transfer, then confirm the `transfer.created` webhook marked it paid.

Until the flip, the app runs on the fake client (dev/demo) or real **test-mode** keys (a manual checkpoint) — no
real money moves. Everything above the flip is **code (done)**; the flip itself is **Subhan's**.

---

## 3b. ⚠️ THE AUTOMATED PAYOUT ENGINE go-live (Subhan's step — NOT done in the build)

The automated payout engine (`docs/PAYOUT-ENGINE-DESIGN.md`) is **built and TEST-MODE-proven**: a scheduled,
preference-driven run selects owners due today and disburses through the Connect rail. It is **inert + test-mode**
until deliberately switched on. Going live is a **separate, explicit** step on top of the §3 flip:

1. **Do the §3 live-key flip first** (Connect enabled, both webhooks registered incl. `transfer.created`, live keys
   in a `production` env). `PayoutEngineMode` reports `live` ONLY when the Stripe secret is a live key AND
   `APP_ENV=production` — otherwise every run stays `test` and no funds move.
2. **Owner onboarding + KYC (real):** each owner completes real Stripe Connect (Standard) onboarding; a payout is
   REFUSED until `stripe_connect_payouts_enabled` flips true via the real `account.updated` webhook (never optimistic).
3. **Compliance decisions (founder + counsel) — business/legal, not code:** PSD2/SCA, PCI (SAQ A — no raw PAN,
   already held), GDPR (owner PII + retention), money-transmission / agent-of-payee posture, tax withholding/reporting.
4. **Enable the engine:** set `PAYOUT_ENGINE_ENABLED=true` (+ `PAYOUTS_RAIL=stripe_connect`). The daily
   `payouts:run` scheduler now fires; a dry-run (`php artisan payouts:run --force --date=…` in a staging/test-mode
   env, or the fake client) should reconcile every run's totals to the source statements to the cent (#17) first.
5. **Monitor the first live run.** The double-transfer guard (row-lock + re-check + deterministic idempotency key)
   and webhook-replay idempotency are already proven — but watch the first real transfers land + confirm via webhook.

Guarantees already in code (nothing to build at go-live): amount == reconciling statement net (#17); no double-pay
(idempotent `run_key` + the transfer guards); owner-scoped (#28); every transition audited (#20); a tax-incomplete
owner is HELD, never paid; the engine never fabricates a date/fee.

---

## 4. What remains account-gated (still pending, as accounts land)

Stripe is now lit (test-mode). Still deferred honestly: **Channex** live channel sync · **WhatsApp / OTA** inbox
channels · **SMS** · **guest ID verification (KYC)**. Production **email** is already live (Postmark — see
`docs/EMAIL-PRODUCTION.md`).

---

# ⚠️⚠️ GATE 3 — THE ONE SITTING (written 2026-09-20, DEPLOY 10)

⚠️ **Everything below was established by READING the integration and MEASURING the host. No Stripe API call
was made, no key was handled, no dashboard was opened, and `STRIPE_FAKE` was not flipped.** The point is that
this is a short session, not a discovery exercise.

## A. WHAT THE CODE ALREADY DOES — 🔬 so you know what you are switching on

| question | 🔬 answer |
|---|---|
| **An event type we don't handle?** | **`202 {"status":"ignored"}` — never a 500.** Every branch of both receivers ends in 202; `applyPayment`'s `match` has `default => null`; the Connect receiver's `match` has `default => 202 ignored`. ⚠️ **So Stripe sending types nobody asked for cannot cause a retry storm.** |
| **A bad signature?** | `400 {"error":"invalid_signature"}` — no retry storm either (4xx is a permanent failure to Stripe). |
| **A handler that genuinely fails?** | The error is recorded on the `webhook_events` row and **rethrown → 500**, so Stripe retries. That is deliberate: the retry is *admitted* rather than swallowed. |
| **Idempotency key** | `unique(source, external_event_id)` — Stripe's own globally-unique event id. |
| **Two duplicate deliveries at the same instant?** | ✅ Safe. `createOrFirst` turns the losing INSERT into a fetch rather than a 500, and `reclaim()` is a **compare-and-swap**: the eligibility test and the claim are the *same* UPDATE, so **the loser's update affects zero rows** and `$apply()` runs once. A `SELECT`-then-`UPDATE` would have let both through; it was written this way on purpose. |
| **Signature tolerance** | **300 seconds** — Stripe's SDK default (`Webhook::DEFAULT_TOLERANCE`), not overridden. |
| **Server clock drift** | 🔬 **0 s** against an external HTTPS `Date` header (±1 s granularity), measured 2026-09-20. ⚠️ A drifted clock rejects every genuine webhook while logging what looks like an attack — this one is fine. |
| **Webhook reachable through the CDN?** | ✅ 🔬 Yes — POSTs are pure pass-through, never cached, no rate-limit observed at 25 rapid requests (L12m). |

## B. WHAT THE THREE SWITCHES ACTUALLY DO — ⚠️ they are not one switch

⚠️⚠️ **`STRIPE_FAKE=false` ALONE DOES NOT TAKE A SINGLE PAYMENT.** There are three, and they are independent:

| env | default | what it controls |
|---|---|---|
| `STRIPE_FAKE` | `true` | **Which client is bound.** `true` → `FakeStripeClient` ("never touches the network"). `false` → the real SDK — ⚠️ **but only if `APP_ENV=production`**: `StripeSafetyGuard` forces the fake whenever a **live** key appears outside production. |
| `PAYMENTS_GATEWAY` | `manual` | ⚠️⚠️ **THE REAL MONEY SWITCH.** `manual` → card payments stay on the offline rail; **`stripe` → cards actually charge.** Set this **last**. |
| `PAYOUTS_RAIL` + `PAYOUT_ENGINE_ENABLED` | `manual` / `false` | The owner **payout** side (Connect transfers). Independent of the two above; leave alone until Connect KYC is done. |

🔬 Production today: `APP_ENV=production` · `STRIPE_FAKE=true` · `PAYMENTS_GATEWAY=manual` · `PAYOUTS_RAIL=manual`.

## C. ⚠️ THE NUMBERED DASHBOARD LIST — work top to bottom

⚠️ **Do this in Stripe's dashboard in LIVE mode. Nobody but you ever sees these values; do not paste them into
a chat, a session, or a file in this repository.**

1. **Create the two products and four prices** (Products → Add product), live mode:
   Essential — monthly and yearly · Pro — monthly and yearly.
   ⚠️ **The amounts are yours to decide.** The code currently ships **placeholders** (`config/billing.php`:
   Essential €49/mo · €490/yr, Pro €99/mo · €990/yr, each marked `PLACEHOLDER`).
   Copy each **price id** (`price_…`) — you need all four.
2. **Put those four ids in `.env`** on the server:
   `STRIPE_PRICE_ESSENTIAL_MONTHLY=` · `STRIPE_PRICE_ESSENTIAL_YEARLY=` ·
   `STRIPE_PRICE_PRO_MONTHLY=` · `STRIPE_PRICE_PRO_YEARLY=`
3. **Set the real display amounts** in `config/billing.php` (`prices_minor`, integer minor units) so the
   marketing pages stop showing placeholders. ⚠️ These must agree with step 1 — nothing checks that they do.
4. **Copy the API keys** (Developers → API keys, live mode) into `.env`:
   `STRIPE_KEY=pk_live_…` · `STRIPE_SECRET=sk_live_…`
5. **Create webhook endpoint #1 — the account endpoint** (Developers → Webhooks → Add endpoint):
   URL: **`https://vyrobel.com/webhooks/stripe`**
   Events: `checkout.session.completed` · `customer.subscription.created` · `customer.subscription.updated` ·
   `customer.subscription.deleted` · `invoice.paid` · `invoice.payment_failed` ·
   `payment_intent.succeeded` · `payment_intent.payment_failed` · `payment_intent.canceled` ·
   `refund.updated` · `refund.failed` ·
   ⚠️ ADMIN 06: `checkout.session.expired` · `charge.dispute.created` · `charge.dispute.updated` · `charge.dispute.closed` · `charge.dispute.funds_withdrawn` · `charge.dispute.funds_reinstated`
   Copy its signing secret → `.env` as `STRIPE_WEBHOOK_SECRET=whsec_…`
6. **Create webhook endpoint #2 — the CONNECT endpoint**, scoped to *Connected accounts*:
   URL: **`https://vyrobel.com/webhooks/stripe/connect`**
   Events: `account.updated` · `transfer.created` · `transfer.updated` ·
   `payment_intent.succeeded` · `payment_intent.payment_failed` · `payment_intent.canceled` ·
   `refund.updated` · `refund.failed` ·
   ⚠️ ADMIN 06: `checkout.session.completed` · `checkout.session.expired` · `charge.dispute.created` · `charge.dispute.updated` · `charge.dispute.closed` · `charge.dispute.funds_withdrawn` · `charge.dispute.funds_reinstated`
   Copy **its own** signing secret → `.env` as `STRIPE_CONNECT_WEBHOOK_SECRET=whsec_…`
   ⚠️ **Without the ADMIN 06 events a deletion is blind to them:** the dispute refusal never fires (a guest's card
   payment and its disputes live on the connected account), and a guest's closed payment page keeps refusing a
   deletion until its 24-hour expiry.
   ⚠️⚠️ **These are two different secrets. Swapping them makes every signature fail.**
7. **Flip the client:** `STRIPE_FAKE=false` in `.env`. (`APP_ENV` is already `production`, so the safety guard
   will allow the live key.)
8. **Rebuild the config cache and re-close it:**
   `/opt/alt/php84/usr/bin/php artisan config:cache && chmod 600 bootstrap/cache/config.php`
   ⚠️ `config:cache` writes that file **0644** and it contains your database password — see L12k.
9. **Send a test event** from the dashboard (Webhooks → your endpoint → Send test webhook) and confirm a
   **2xx**. Then check the row landed:
   `select event_type, status, received_at from webhook_events order by id desc limit 5;`
10. ⚠️⚠️ **LAST, AND ONLY WHEN 1–9 ARE DONE: `PAYMENTS_GATEWAY=stripe`.** This is the switch that lets a real
    card be charged. Nothing before it moves money.

⚠️ **The owner payout rail (`PAYOUTS_RAIL`, `PAYOUT_ENGINE_ENABLED`) is a separate cutover** and needs Connect
KYC first — §3b above. Leave both alone during this sitting.

## D. ⚠️ WHAT IS NOT IN THIS LIST, BECAUSE IT IS NOT STRIPE

⚠️ The alarm (`OPS_HEARTBEAT_PING_URL`), the `APP_KEY` copy, and the super-admin password are the **other**
sitting (`GO-LIVE-REQUIREMENTS.md`). ⚠️ **Do the alarm before step 10** — it is Gate 3 condition (1), and
without it a forgotten maintenance park loses webhook events silently (L12n).
