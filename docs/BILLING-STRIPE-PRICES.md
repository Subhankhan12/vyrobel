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

## ⛔ FIRST — a decision only you can make

⚠️ **How should VAT reach Stripe on a PER-UNIT plan?**

The engine computes a per-unit total as `units × rate + VAT`, and the page shows that figure. But the checkout
sends only `price` and `quantity` — **nothing about tax**. So with a €9.00 Price and 8 units, Stripe would take
**€72.00** while the page says **€88.56**: a silent **€16.56/month under-charge**.

Until you choose, **the app refuses to bill a per-unit plan in production** (it throws rather than under-charge).
Flat tiers are unaffected — they are VAT-inclusive by your ruling, so €49 is already the final amount.

| option | set `STRIPE_TAX_BEHAVIOR=` | what you create | trade-off |
|---|---|---|---|
| **A · inclusive** | `inclusive` | a per-unit Price at the **VAT-inclusive** amount (€11.07 at 23%) | Simplest, and consistent with the flat tiers. You account for the VAT inside the price. One rate for everyone — no cross-border handling. |
| **B · exclusive** | `exclusive` | a per-unit Price at **€9.00** + Stripe Tax enabled | Correct for cross-border and EU B2B reverse-charge; Stripe computes the right rate per customer. ⚠️ Requires Stripe Tax configured **and** a further code change — the checkout does not yet send `automatic_tax`. |

⚠️ **Option B is not just a config value** — it needs `automatic_tax: ['enabled' => true]` added to the checkout,
which is a separate build. Option A works with the code as it stands today.

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
| *(per-unit, if you create one)* | *(its own key)* | EUR | **A:** €11.07 · **B:** €9.00 | monthly | **per-unit** | **A:** inclusive · **B:** exclusive |

⚠️ **The amounts above are the CONFIGURED PLACEHOLDERS** from `config/billing.php`, which that file labels as
placeholders you set. If the real commercial prices differ, change the config **and** the Stripe Prices together
— the app's displayed figure comes from the config/plan row, so a mismatch would show a customer one number and
charge another.

⚠️ **`billing_scheme` must match the plan's shape.** A flat plan on a per-unit Price (or the reverse) charges the
wrong amount. The app sends `quantity = 1` for flat and the real unit count for per-unit.

---

## After you paste the ids

1. Set the `STRIPE_PRICE_*` values (and `STRIPE_TAX_BEHAVIOR` if you have a per-unit plan) in the **production**
   environment.
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
- the **quantity and amount** that would reach Stripe equal `Plan::chargeFor()` — the same figure the page shows.
