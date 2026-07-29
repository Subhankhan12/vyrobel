# Live Stripe (Phase 8) — the go-live runbook

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
   - `https://<host>/webhooks/stripe` → events: `checkout.session.completed`, `customer.subscription.created|updated|deleted`, `invoice.paid`, `invoice.payment_failed`, `payment_intent.succeeded`, `payment_intent.payment_failed`, `payment_intent.canceled`. Copy its signing secret → `STRIPE_WEBHOOK_SECRET`.
   - `https://<host>/webhooks/stripe/connect` → events: `account.updated`, `transfer.created`. Copy its signing secret → `STRIPE_CONNECT_WEBHOOK_SECRET`.
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
