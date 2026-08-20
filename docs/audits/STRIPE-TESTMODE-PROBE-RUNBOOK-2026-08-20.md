# Stripe test-mode probe — RUNBOOK (founder-executed) + verification brackets

**2026-08-20.** Bracket **(A)** complete — nothing charged, no key touched, no CLI run. Brackets **(B)** and
**(C)** run *after* the founder executes the runbook below.

⚠️ **Claude never handles a real key or secret, never runs `stripe listen`, never places a secret value.**
Every step in §2 is the founder's.

---

## §A — THE GUARD: what it keys on, and the exact env to set

`StripeSafetyGuard` keys on **two things, in order**:

```php
public static function forceFakeOutsideProduction(string $environment, bool $configuredFake, ?string $secret): bool
{
    if ($configuredFake) {
        return true;                                    // 1. STRIPE_FAKE=true  → always the fake
    }
    return $environment !== 'production' && self::isLiveKey($secret);   // 2. live key off-prod → refused
}

public static function isLiveKey(?string $secret): bool
{
    return is_string($secret) && (str_starts_with($secret, 'sk_live_') || str_starts_with($secret, 'rk_live_'));
}
```

⚠️ **It keys on the KEY PREFIX, not on "any key outside production".** `isLiveKey()` matches only `sk_live_`
/ `rk_live_`. A `sk_test_` key is **not** a live key, so the second clause is false and the **real client
binds**.

> ✅ **The guard PERMITS this probe. It was designed for it** — its own docblock says so: *"Test-mode keys are
> allowed outside production for the manual live-test checkpoint; CI still pins `STRIPE_FAKE=true`."*

### The exact `.env` the founder must set

| Key | Value | Note |
|---|---|---|
| `STRIPE_FAKE` | **`false`** | ⚠️ the only switch that matters — while `true`, nothing else is even read |
| `STRIPE_SECRET` | `sk_test_…` | ⚠️ **TEST** mode. A `sk_live_` here is refused by the guard *and* would be wrong |
| `STRIPE_KEY` | `pk_test_…` | the matching publishable key |
| `STRIPE_WEBHOOK_SECRET` | `whsec_…` | ⚠️ **from `stripe listen`** (§2 step 3), *not* from the dashboard |
| `APP_ENV` | ⚠️ **leave as `local` — DO NOT CHANGE** | changing it to `production` is unnecessary here and would arm production behaviour on a laptop |

⚠️ **`APP_ENV` deliberately stays `local`.** The go-live checklist requires `APP_ENV=production` because a
*live* key demands it. A *test* key does not — the guard lets it through on any environment. Setting
`production` locally would gain nothing and switch on production posture on a dev machine.

### The suite is insulated

`phpunit.xml:49` pins `STRIPE_FAKE=true` (and its own `whsec_test_*` secrets), so **the founder's `.env` edit
cannot leak into the test suite.** The 2,524-test suite keeps running against the fake throughout.

---

## §2 — THE FOUNDER'S RUNBOOK

⚠️ All five steps are yours. Claude performs none of them.

**1 · Get TEST-mode keys.** Stripe dashboard → **make sure the "Test mode" toggle is ON** → Developers → API
keys. Copy the secret (`sk_test_…`) and publishable (`pk_test_…`). Test mode is what makes `4242…` work and
guarantees no real money can move.

**2 · Set the local `.env`** exactly as the table in §A. Leave `APP_ENV=local`.

**3 · Forward the webhook.** In its own terminal:

```bash
stripe listen --forward-to http://127.0.0.1:8000/webhooks/stripe
```

It prints `> Ready! Your webhook signing secret is whsec_…`. **Copy that value into `STRIPE_WEBHOOK_SECRET`**
and leave the process running for the whole probe.
⚠️ That secret is *not* the dashboard's endpoint secret — the CLI mints its own, and the signature check will
fail with a 400 if you use the wrong one.

**4 · Drive the success case.** Serve the app (`php artisan serve`, port 8000 to match step 3), sign in as
`demo.traveller@vyrobel-demo.test` / `password`, open **Payments**, and pay a booking that shows a balance.
On Stripe's hosted page use:

```
card  4242 4242 4242 4242      any future expiry · any CVC · any postcode
```

**5 · Drive the failure case.** Repeat on a second booking with the declined card:

```
card  4000 0000 0000 0002
```

Then tell me it's done — I'll run brackets (B) and (C).

---

## §B — WHAT I WILL VERIFY (after your run)

From the **database and the Stripe result**, never from a unit test — the whole point is the real API:

1. The `4242` payment row is `gateway = 'stripe'` with a **real** `gateway_ref` starting `pi_` — ⚠️ the
   **first real Stripe intent id this system has ever held**.
2. The folio settles **on the `payment_intent.succeeded` webhook**, not on the redirect: the row is `pending`
   until the webhook lands, then `succeeded`, and only then does the balance move.
3. A **repeat** pay on the same folio does **not** double-charge — `recordCheckoutIntent` is
   `firstOrCreate(['gateway_ref' => $intentId])` behind a deterministic idempotency key.
4. The `4000 0000 0000 0002` booking's folio stays **unpaid**.
5. #28 holds — only the paying guest's own folio moved.

---

## §C — CLEANUP (planned now, so it cannot be forgotten)

⚠️ **This probe deliberately moves money-as-seeded** (16 → 17+, and a folio balance changes).

**The discriminator is clean:** every seeded payment is `gateway = 'manual'`; every probe payment will be
`gateway = 'stripe'`. So the probe's rows are identifiable without guessing.

Default plan: **delete the probe's `gateway='stripe'` rows, recompute the affected folios, and restore the
baseline to 16 payments / 0 Stripe intents** — then re-verify by sums and counts. If you would rather keep
the row as evidence of a real capture, say so and I will instead **report the exact new counts** so canon
records the new baseline rather than a silent drift.

---

## ⚠️ A CORRECTION TO MY OWN CONFIRM-FIRST REPORT

`STRIPE-CAPTURE-CONFIRM-2026-08-20.md` §3 said the 16 seeded payments have "`method = (null)`" and "zero
Stripe intent ids", citing a probe on **`provider_ref`**.

⚠️ **There is no `provider_ref` column, and no `method` column.** The real columns are **`gateway_ref`** and
**`gateway`**. My probe read `$r->provider_ref ?? null` — a property that does not exist — so it returned 0
for every row *unfailably*: the §N.29 label form, in my own verification, one pass after I wrote the rule.

**Re-measured on the correct columns, the conclusion is unchanged and now actually evidenced:**

| | |
|---|---|
| payments | **16** |
| `gateway` | **`manual`** on all 16 (not null) |
| non-null `gateway_ref` | **16** (they all carry a ref — it just isn't a Stripe one) |
| real Stripe intents (`pi_` / `cs_`) | ⚠️ **0** |

The verdict stands — the seed bypasses capture via `recordPayment(..., 'tok_demo_card')` — and it never
rested on the bad probe: the seeder call itself is direct evidence, and `gateway = 'manual'` confirms it
independently. But the supporting figure was reached by a probe incapable of failing, and that is worth the
correction.

---

## Status

| | |
|---|---|
| **Bracket A** | ✅ complete — the guard **permits** test keys off-production; the exact env is in §A |
| **Charged / keys touched / CLI run** | ⚠️ **none** — read-only so far |
| **Money** | unchanged: **929 / 10,912,000 / 0 / 41 / 16 / 0**; 0 real Stripe intents |
| **Next** | ⛔ the founder runs §2, then tells me — brackets (B) and (C) follow |
