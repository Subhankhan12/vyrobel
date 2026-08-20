# Live Stripe capture — CONFIRM-FIRST (read-only; nothing built, charged, or flipped)

**2026-08-20.** Read-only. No build, no key flip, no charge, no DB write, no 17th payment. Money as seeded:
**929 / 10,912,000 / 0 `unit_rate_overrides` / 41 / 16 / 0**, plus 0 guest notifications and 0 guest email
preference rows. Baseline `main` `8dd2cbf`.

---

## ⚠️ VERDICT: **BUILT-AND-DORMANT.** Go-live is a KEY-FLIP. There is no capture gap.

And ⚠️ **the premise this pass was given is wrong in an important way:** the guest audit's
*"`payments.pay` is a REAL charge path, 0-EXERCISED"* is true of the **demo data** and false of the **build**.
Five tests drive that exact route, and four of them drive the settling webhook. Details in §3 — it is the
same instrument-vs-build confusion that the `book()` correction already cost this project once.

---

## §1 — THE TRACED CAPTURE PATH

The storefront does **not** capture. `booking.reserve` (POST `book/{property}/checkout`) runs `quote()` +
`book()` in one request and produces a folio; it sends no `payment_token`. Payment is a **separate step**:

| # | Step | Code |
|---|---|---|
| 1 | Guest submits pay for one booking | `POST guest/payments/{reservation}/pay` → `GuestFolioPaymentController::pay` |
| 2 | 🔒 Resolve the guest's OWN booking | `$this->guest()->reservations()->whereKey($reservation)->firstOrFail()` — from the guard, never a request id (#28); a foreign id 404s |
| 3 | Compute what is owed | `FolioTotals::for($folio)['balance_minor']`; `<= 0` short-circuits with "nothing due" |
| 4 | Open a **hosted** Stripe Checkout | `StripeClientInterface::createPaymentCheckout(...)` with a **deterministic** `StripeIdempotency::keyFor('folio_checkout', folioId)` |
| 5 | Pre-create a **PENDING** payment | `FolioService::recordCheckoutIntent($folio, $amount, $intentId, …)` inside the booking's org context |
| 6 | Redirect off-site | `redirect()->away($session->url)` — **the card never touches our server (SAQ A)** |
| 7 | Stripe calls back | `POST /webhooks/stripe` (registered, unauthenticated so Stripe can reach it) |
| 8 | ⚠️ **Signature verified against the RAW body** | `constructWebhookEvent($request->getContent(), $request->header('Stripe-Signature'))`; invalid → **400**, nothing written |
| 9 | ⚠️ **THE MONEY LANDS HERE** | `'payment_intent.succeeded' => $folios->settlePayment($payment, 'succeeded')` · failed/canceled → `settlePayment($payment, 'failed')` |

**Fulfilment is on the webhook, not the redirect** — the canon's rule, implemented. The success URL is
UX only; a guest who closes the tab still gets settled.

---

## §2 — THE DECIDING CODE (why BUILT, not stubbed)

**The real client is a real SDK call, not a placeholder.** `StripeClient::createPaymentCheckout()` calls
`$this->sdk->checkout->sessions->create([...])` with `mode: 'payment'`, real `price_data`, the idempotency
option, and metadata propagated onto the PaymentIntent for reconciliation. There is no TODO, no fake token,
no short-circuit.

**The fake/real seam is config-gated, with a safety guard:**

```php
$forceFake = StripeSafetyGuard::forceFakeOutsideProduction($env, config('services.stripe.fake', true), $secret);
// → true if `fake` is on, OR if a LIVE key appears outside `production`
```

So today it binds `FakeStripeClient` (`services.stripe.fake` defaults **true**), and ⚠️ **a live key dropped
into a dev `.env` cannot charge** — the guard forces the fake and logs a warning. Both ends of the chain
exist: `FolioService::recordCheckoutIntent()` and `FolioService::settlePayment()` are both present.

**Nothing is missing, stubbed, or unhandled.** The only thing standing between this and live money is
configuration.

---

## §3 — ⚠️ THE 16 SEEDED PAYMENTS, AND WHAT "0-EXERCISED" ACTUALLY MEANT

**The seed bypasses the capture path completely.** `DemoSeeder:1567` calls:

```php
$folios->recordPayment($folio, Money::of($due, 'EUR'), 'tok_demo_card');
```

— the direct FolioService record path with a **fake token string**. The data agrees: all **16** payments are
`status = succeeded`, `method = (null)`, and ⚠️ **zero carry a Stripe intent id**. Not one of them was
captured.

Per the probe rule, that makes the demo an **instrument that cannot produce a positive** for this path: a
"0-exercised" observation drawn from it is evidence about the seeder, not about the build.

**And the build is exercised — by tests.** `GuestCardChargeTest` hits the real pay route **6 times** and the
`payment_intent.succeeded` webhook **4 times**, proving:

- starts a hosted Checkout and pre-creates a pending payment **without settling the folio**
- ⚠️ **settles the folio on the `payment_intent.succeeded` webhook**
- leaves the folio unpaid when the charge fails
- refuses to pay **another guest's** booking (#28)
- **does not double-charge** on a repeated pay (the deterministic idempotency key)

> ⚠️ **The audit's flag was scoped to what it could see and the scope was lost downstream.** "0-exercised"
> described the demo dataset; carried forward it reads as "the capture may not work". It is the `book()`
> lesson exactly — *a limit of the instrument is not a gap in the build* — and it would have sent this
> project into a build pass for something already finished.

---

> ⚠️ **CORRECTION (2026-08-20, §7.4 pass).** The figures above cite `provider_ref` and `method`. **Neither
> column exists** — they are `gateway_ref` and `gateway`, and the probe's `?? null` made it return 0
> unfailably (§N.30.1). Re-measured: 16 payments, `gateway='manual'` on all, all 16 with a non-null
> `gateway_ref`, **0 real Stripe intents**. The verdict is unchanged — the seeder call and `gateway='manual'`
> are independent evidence — but the supporting figure was reached by a probe incapable of failing. Left in
> place with this note rather than rewritten, so the audit trail shows both.

## §4 — WHAT GO-LIVE ACTUALLY NEEDS: **KEYS ONLY**

No build. The founder's checklist:

| # | Step | Note |
|---|---|---|
| 1 | `STRIPE_SECRET` + `STRIPE_KEY` → **live** values | present in `.env` today (test values) |
| 2 | **`STRIPE_FAKE=false`** | present today; this is the switch that binds the real client |
| 3 | **`APP_ENV=production`** | ⚠️ **load-bearing** — with a live key in any other environment `StripeSafetyGuard` forces the fake and nothing charges |
| 4 | `STRIPE_WEBHOOK_SECRET` → the **live endpoint's** signing secret | present today; must be re-issued for the live endpoint |
| 5 | Register the endpoint in the Stripe dashboard | `POST https://<host>/webhooks/stripe`, subscribed to **`payment_intent.succeeded`**, `payment_intent.payment_failed`, `payment_intent.canceled` |

⚠️ **Step 5 is the one that fails silently if missed.** Without a registered endpoint the guest is charged by
Stripe and the folio **never settles** — money taken, balance still showing. Everything else fails loudly.

⚠️ **Separately noted, NOT a capture blocker:** `STRIPE_CONNECT_WEBHOOK_SECRET` is **absent** from `.env`.
That secret serves `/webhooks/stripe/connect` — the **owner payout** rail, not guest capture. It needs to be
set before payouts go live; it does not affect a guest paying a folio.

---

## §5 — THE ONE THING NOT PROVEN, AND THE PROBE THAT WOULD SETTLE IT (not run)

Every test runs against `FakeStripeClient` — correct for CI, and the canon's posture. So what is proven is
**our half**: the route, the isolation, the idempotency, the pending-payment write, the signature check, the
webhook settlement, the failure path. What is **not** proven is the **real `StripeClient` against Stripe's
actual API** — that its `checkout.sessions.create` payload is accepted and returns a usable
`payment_intent`.

**The probe that would settle it — NOT RUN, because it requires a real charge:**

> With **test-mode** keys and `STRIPE_FAKE=false` on a non-production host, drive one guest folio payment
> end to end against Stripe's test API using card `4242 4242 4242 4242`, with the webhook endpoint pointed
> at the host (Stripe CLI `stripe listen --forward-to .../webhooks/stripe`), and confirm the folio settles.

⚠️ That is a **write and a charge** — out of scope here by instruction. It is also, in my view, the single
highest-value pre-launch check remaining, and it costs nothing real in test mode. Recommended as its own
pass before the live flip.

---

## Status

| | |
|---|---|
| **Verdict** | ⚠️ **BUILT-AND-DORMANT** — go-live is a key-flip; **no build required** |
| **Built / charged / flipped** | ⚠️ **Nothing.** Read-only throughout; no 17th payment; no key touched. |
| **Money** | byte-identical: 929 / 10,912,000 / 0 / 41 / **16** / 0; 0 guest notifications; 0 guest email prefs |
| **Correction** | the *"`payments.pay` 0-EXERCISED"* flag describes the **demo seed**, not the build — 5 tests drive the route, 4 drive the settling webhook |
| **Residual risk** | the real client has never spoken to Stripe's API; the test-mode end-to-end probe (§5) is named and **not run** |
| **Silent-failure watch** | an unregistered live webhook endpoint = charged guest, unsettled folio |
