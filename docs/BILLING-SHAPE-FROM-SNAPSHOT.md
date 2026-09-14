# The billing shape is a TERM — so it comes from the snapshot

**2026-09-03.** Founder ruling, implemented. The code now does what three separate places already claimed.

---

## 0 · Three surfaces stated the rule; only the code disagreed

| surface | what it says |
|---|---|
| `PlanTerms` docblock | **"Terms frozen, quantity live."** |
| `docs/BILLING-PLAN-ASSUMPTIONS.md` §4 | *"a TERM is anything that decides what the customer pays"* |
| **the admin plan-edit form** | *"Changes apply to **new subscriptions only** — existing subscribers keep the terms they agreed to."* |

The code froze the **rate** and read the **shape** live. Whether a plan is flat or per-unit *is* the billing
model, so by the project's own definition it is a term — and reading it live is exactly a term that can
change under a customer.

⚠️ **So the admin form's sentence was FALSE on a money path until this change.** An administrator converting
a live plan from flat to per-unit re-modelled every existing subscriber's quantity sync, on a form promising
the opposite. **The comment was right, the copy was right, the code is now what they describe.**

---

## 1 · Every value the sync read from the live plan

`syncSubscriptionQuantity()` touched the plan row exactly **twice**, and both were the same thing:

| read | classification | now |
|---|---|---|
| `$plan->isPerUnit()` | ⚠️ **TERM** — it *is* the billing model | `$terms->isPerUnit()` (snapshot) |
| `$plan->quantityFor()` → `isPerUnit() ? BillableUnits::count() : 1` | **TERM** (the shape) **+** the count, which is **not** a term | `$terms->quantity()` — same expression, shape frozen, **count still live** |

**Nothing else.** The unit rate is not read here (Stripe holds it on the Price); the Stripe subscription id
and the idempotency key come from the subscription. So there was exactly one term to fix and it is fixed.

⚠️ **`Plan::quantityFor()` and `PlanTerms::quantity()` are byte-identical** — `isPerUnit() ? BillableUnits::count() : 1`
— so the arithmetic did not change at all. Only *where the shape came from* did.

⚠️ **`startCheckout()` still reads the LIVE plan (`isPerUnit`, `quantityFor`) and that is correct**: at
checkout the customer is *agreeing* to that plan's terms, so the live row is the right source. It is not a
term changing under anyone.

⚠️ **The docblock was NOT corrected — it was already right.** It survives unchanged.

---

## 2 · The missing-plan case — removed, not handled (but say precisely how far)

The sync used to **throw** when `$subscription->plan` was null. Deciding whether to sync no longer needs the
plan row at all, so **that branch is gone**.

⚠️ **It does not vanish "entirely", and the difference matters:**

| state | before | after |
|---|---|---|
| captured terms + plan row archived | **threw** | ✅ **syncs normally** — the terms it needs are on the subscription |
| captured terms + plan row present | synced | ✅ syncs |
| **no capture AND no plan row** | threw | ⚠️ **still refuses** — via `terms()`, because the answer is genuinely unknown |

**A case removed beats a case handled**, and this removed the one that was reachable while keeping the
refusal for the one that is genuinely unanswerable.

**Who still reaches `MissingPlanTermsException`:** `Subscription::terms()` (no capture, no plan) —
which is what the sync now calls, and what `chargeFor()` has always called; `PlanTerms::fromCaptured()`
(empty or basis-less snapshot); and, new in this pass, `Subscription::fillCapturedTerms()` on a plan change
it cannot re-capture. **The exception and its tests were not deleted.**

---

## 3 · Does a plan change re-capture? — TESTED, and it split in two

**✅ The model re-captures correctly.** `booted()`'s `updating` hook fires on `isDirty('plan_id')` and calls
`fillCapturedTerms()`. Measured: moving a subscription from Essential to Pro moved the captured price
**4900 → 9900**. The platform-admin path (`Admin\CustomerController::assignPlan`) writes through the model,
so it re-captures.

### ⚠️⚠️ But the CUSTOMER-FACING path never changes `plan_id` at all — reported, not fixed

Driven end-to-end (checkout → signed webhook), with the fixture deliberately started on a *different* plan
so the probe could actually show a change:

```
BEFORE   plan_id -> pro_monthly · captured price 9900 · shape flat
PAID FOR: essential_monthly  (Stripe billed 49.00)
AFTER    plan_id -> pro_monthly · captured price 9900 · status active
⚠️ plan_id followed the purchase: NO
```

`startCheckout()` resolves the target plan, sends its Price to Stripe, and even stamps
`metadata: ['plan' => $plan->code]` — and `BillingWebhookService::activate()` **never reads it**. It writes
`status`, `stripe_subscription_id` and `stripe_customer_id` only. **No code path anywhere writes `plan_id`
in response to a purchase.**

⚠️ **This is not an upgrade edge case — it is the ordinary first subscription.** An org starts on the trial
tier (`trialPlan()` = `pro_monthly`) and then subscribes to whatever it chose; Stripe bills the chosen tier
while the app keeps showing the trial's plan and its captured terms.

⚠️ **This pass neither causes nor worsens it.** The sync already used the plan behind the stale `plan_id`;
it now uses that same plan's captured terms. **Reported at the top; fixing it is a webhook/checkout change
beyond "the shape's source".**

⚠️ **A period change (monthly → yearly) is the same path**, so it has the same outcome — and the rate is
unambiguously a term.

---

## 4 · The backfill's NULLs — checked BEFORE changing anything

⚠️ **This was the most likely way the pass could do harm, so it was settled first.** The backfill writes:

```php
'terms_pricing_type'    => $plan->pricing_type ?? Plan::PRICING_FLAT,   // ← the SHAPE, always written
'terms_unit_rate_minor' => $plan->unit_rate_minor,                      // ← the plan's own NULL, for flat plans
```

**Every backfilled row has a captured shape.** Reading the shape from the snapshot cannot break them. The
column is `string(16) NULLABLE`, so an absent shape is *representable* — hence the guard below.

**What a NULL captured rate means now:** captured **flat** + null rate is **coherent** (a flat plan bills its
price and the rate is meaningless — and the previous pass narrowed its guard for exactly this reason).
Captured **per-unit** + null rate is **incoherent**; `fromCaptured()` already refuses it (`noBasis`).

**The fallback when the shape is absent: there isn't one — it refuses.** ⚠️ *"Assume flat"* is **not** safe:
it would bill a per-unit customer **once** instead of per unit — a wrong number, confidently, which is the
shape this codebase refuses. A mutation that assumes flat goes red.

---

## 5 · An admin's shape change now reaches no existing subscriber

**Stated plainly:** converting a live plan from flat to per-unit changes what **new** subscribers agree to
and **nothing** for anyone already subscribed. That is correct — it is what a snapshot is for — and it is
now true rather than merely claimed.

**Does anything say so?** ✅ Yes — the admin edit form already carries *"Changes apply to new subscriptions
only — existing subscribers keep the terms they agreed to."* **That sentence is now true.** No admin-UI
change was needed or made.

⚠️ **How would a subscriber ever move to a new shape?** Only by their `plan_id` changing. Today that happens
**only** through the platform-admin "assign plan" action — the customer-facing checkout does not do it
(§3). **So a shape change reaches an existing customer only if a platform administrator moves them by hand.**
Reported.

---

## 6 · The silent capture no-op — now load-bearing, and split

`fillCapturedTerms()` did `if ($plan !== null)` and otherwise captured nothing and said nothing. **This pass
is what makes it matter**, so it changed — but only where a quiet failure produces a *wrong answer* rather
than *no answer*:

| caller | quiet failure produces | verdict |
|---|---|---|
| `creating` | **no** captured terms and no plan → `terms()` refuses immediately, and nothing can be billed | ✅ **may stay quiet** — the downstream refusal is sufficient and loud |
| `updating` (a plan **change**) | the **PREVIOUS** terms surviving under a new `plan_id` — every screen shows the new plan while billing keeps the old model | ⚠️ **must refuse** — `MissingPlanTermsException::cannotRecapture()` |

⚠️ **A subscription against an unresolvable plan can still be created** — a foreign key forbids a
*non-existent* `plan_id`, but a **soft-deleted** plan satisfies the FK while resolving to null through the
model. That is the only reachable route, and the create path is deliberately left permissive so the
refusal happens where a number would otherwise be produced.

---

## 7 · Money did not move — proven, not asserted

- **Every plan is flat and there are zero per-unit subscriptions**, so no charge can change: a flat
  subscription returns `null` and sends **nothing** to Stripe — asserted directly against the fake client's
  recorded calls, not inferred.
- The **admin-converts-the-plan** test proves the stronger claim: an existing flat subscriber stays flat and
  **still makes no Stripe call** even after the catalogue row becomes per-unit.

---

## 8 · Mutations — each applied, confirmed landed in its own block, reverted

| mutation | |
|---|---|
| the **shape** read live again | **RED (2)** |
| ⚠️ **the WRONG FIX — the quantity frozen instead of live** | **RED (1)** |
| a row with **no captured shape** silently assumed flat | **RED (1)** |
| the **re-capture refusal** reverted (quiet plan change) | **RED (1)** |

⚠️ **One assertion was vacuous and was caught before it could mislead.** The seeded scenario has exactly
**one** billable unit, so `assertSame(BillableUnits::count(), $quantity)` would have passed against a frozen
`return 1` — the wrong-fix mutation could not have gone red. The fixture now adds units and asserts the
count exceeds 1 first.

⚠️ **And one mutation run produced non-JSON output** (a zsh word-splitting slip meant two test paths reached
Pest as one argument). Per the standing rule — *if a run returns empty, suspect a fatal* — those results were
discarded rather than read, the files were confirmed byte-identical to their backups, and both mutations were
re-run with a working instrument.

**`artisan serve` was not used, and was never started.** Every claim was settled by calling the service in a
fresh process or through the test kernel.
