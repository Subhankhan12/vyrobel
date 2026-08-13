# Stripe Prices — the founder's go-live step (BILLING PASS 2, 2026-08-13)

This is the **live-account half** of billing pass ②. The app is now wired to read real Stripe Price ids from the
environment and to **fail loudly** in production if they are missing or still placeholders. What remains is the
part no code in this repo can do: **creating the Price objects in the Stripe dashboard and pasting their ids
into the production environment.**

> ⚠️ **Why the pass could not do it.** `StripeSafetyGuard` refuses a live Stripe key anywhere but `production`
> and binds the fake client instead. That is deliberate and should not be weakened — it is why this build has
> never been able to charge real money by accident. The consequence is that the app's *wiring* is proven from
> dev, but the *existence and amounts of the real Prices* can only be confirmed by you, in the dashboard.

---

## ✅ DECIDED — Stripe Tax (Option B), wired by billing pass 2b

⚠️ **How VAT reaches Stripe on a PER-UNIT plan: `tax_behavior: exclusive` + `automatic_tax`.** The €9 unit Price
is PRE-TAX; Stripe computes the customer's own tax at checkout, including **€0 for an EU business under reverse
charge**. Flat tiers are unchanged — VAT-inclusive, €49/€99 final.

Pass 2b wired it: `automatic_tax` is enabled for the exclusive shape, the session collects a billing address and
a tax ID, and the pass-2 refusal is lifted because the correct path now exists (unset `STRIPE_TAX_BEHAVIOR` and
production refuses to bill per-unit again).

⚠️ **THE APP STOPPED QUOTING A POST-TAX TOTAL FOR PER-UNIT.** It cannot know the customer's rate before
checkout, so the page shows the pre-tax subtotal — "€72.00/mo · 8 units × €9.00 · tax calculated at checkout".
That subtotal is exactly `Price × quantity`, so what is displayed is what Stripe receives before tax.

### ⛔ What you must do in the dashboard — `automatic_tax` is INERT until you do

1. **Enable Stripe Tax** (Settings → Tax).
2. **Register your tax jurisdictions** — Stripe only computes for jurisdictions you have registered. Without
   any, it will not add tax, and the charge will silently be the pre-tax subtotal.
3. Confirm your origin address is set.

⚠️ **Until both are done, a per-unit customer is charged €72.00 with no tax** — the app asks correctly and
Stripe declines to add anything.

### ✅ The VAT-ID gap is CLOSED (`DO-BILL-1`, 2026-08-13)

Vyrobel's own VAT number (`organizations.billing_vat_id`) used to be PDF-only, so a customer who entered it in
Vyrobel and skipped Stripe's prompt was charged VAT they could have reverse-charged. It is **now attached to the
Stripe customer as a typed `tax_id`**, from every in-app writer and from the webhook — the latter being what
covers **renewals**, where Stripe re-computes tax on each invoice.

⚠️ **Fail-safe by design: when in doubt, the customer is TAXABLE.** A number the app cannot confidently type is
never sent, and a number Stripe rejects is never treated as success — because overcharging is visible and
refundable, while wrongly zero-rating is silent and lands on you at an audit.

⛔ ⚠️ **ONE DECISION IS YOURS — VAT VALIDATION.** The app checks that a number is *structurally* plausible for a
country it can name; it does **not** check that the number *exists and belongs to that business* (only VIES
can). Today it **sends as-is and lets Stripe validate against VIES asynchronously** — which means a sale can be
zero-rated before validation completes. The alternative is to **require VIES validation before honouring reverse
charge**: safer, but a build that needs a "pending validation" state. Tell the session which you want; the safe
half is already in place either way.

---

## The Prices to create

Create each in **Stripe → Product catalogue → Add product → recurring price**, then paste the resulting
`price_…` id into the matching environment variable.

| plan | env key | currency | amount | interval | billing scheme | tax behaviour |
|---|---|---|---|---|---|---|
| Essential monthly | `STRIPE_PRICE_ESSENTIAL_MONTHLY` | EUR | **€49.00** (`4900`) | monthly | licensed (fixed) | **inclusive** |
| Essential yearly | `STRIPE_PRICE_ESSENTIAL_YEARLY` | EUR | **€490.00** (`49000`) | yearly | licensed (fixed) | **inclusive** |
| Pro monthly | `STRIPE_PRICE_PRO_MONTHLY` | EUR | **€99.00** (`9900`) | monthly | licensed (fixed) | **inclusive** |
| Pro yearly | `STRIPE_PRICE_PRO_YEARLY` | EUR | **€990.00** (`99000`) | yearly | licensed (fixed) | **inclusive** |
| *(per-unit, if you create one)* | *(its own key)* | EUR | **€9.00** (`900`) | monthly | **per-unit** | ⚠️ **exclusive** |

⚠️ **The amounts above are the CONFIGURED PLACEHOLDERS** from `config/billing.php`, which that file labels as
placeholders you set. If the real commercial prices differ, change the config **and** the Stripe Prices together
— the app's displayed figure comes from the config/plan row, so a mismatch would show a customer one number and
charge another.

⚠️ **`billing_scheme` must match the plan's shape.** A flat plan on a per-unit Price (or the reverse) charges the
wrong amount. The app sends `quantity = 1` for flat and the real unit count for per-unit.

---

## After you paste the ids

1. Set the `STRIPE_PRICE_*` values in the **production** environment. `STRIPE_TAX_BEHAVIOR` already defaults to
   `exclusive` (the ruling) — set it explicitly only to override.
2. Re-run the plan seeder so the `plans.stripe_price_id` column mirrors the environment.
3. ⚠️ **Confirm in the dashboard** that each Price's amount, interval and tax behaviour match the table above.
   This is the step the test suite cannot perform for you — the guards prove the app sends the *configured* id
   and refuses a placeholder, not that the Price behind it is correct.

If anything is missing, production now tells you plainly instead of guessing: the seeder throws at seed time and
the checkout throws before creating a session. **Nothing silently falls back to a fake id.**

---

## What is already proven (from dev, against the fake client)

- the **right Price id per shape** reaches the Stripe call — a flat plan's flat Price, a per-unit plan's per-unit Price;
- **no `price_fake_*` can reach a production money path** — it throws;
- an **unset** `STRIPE_PRICE_*` in production throws rather than falling back;
- a **per-unit plan with no declared tax convention** refuses to bill in production;
- the **quantity and amount** that would reach Stripe equal `Plan::chargeFor()` — the same figure the page shows;
- **Stripe Tax is requested for the per-unit shape and NOT for flat**, and only while the convention says
  `exclusive` — flipping it back to `inclusive` stops the app asking Stripe to add tax on top.
