# A purchase now writes the plan it was for — resolved from the Stripe Price

**2026-09-03.** Stripe and the application agreed about the amount and disagreed about the product.

---

## 0 · What was wrong

Nothing wrote `subscriptions.plan_id` in response to a purchase. `startCheckout()` resolves the target plan,
sends its Price to Stripe, and even stamps `metadata['plan']` — and `activate()` writes only `status` and
the two Stripe ids. **Every org begins on the trial tier (`billing.trial_tier = pro`) and then buys
something**, so the divergence started at the ordinary first subscription, not at some later upgrade.

⚠️ **And it always erred in the same direction — over-granting.** Measured:

```
BEFORE the purchase (trial tier = pro): plan=pro_monthly       · planAllows(channels)=true
AFTER  buying Essential:                plan=essential_monthly · planAllows(channels)=false
```

Because the trial tier is the *most generous* one, a purchase of anything cheaper never took the extra
features away. An org paying €49 for Essential kept **the channel manager**, which Essential does not
include. ⚠️ **`planAllows()` reads `plan_features` BY `plan_id`, so that is ACCESS, not display.**

---

## 1 · The Price is the truth — and which event carries it

| event | handled? | carries the Price? |
|---|---|---|
| `checkout.session.completed` | ✅ | ❌ **no** — a session payload has no `line_items` unless the session was *expanded*. It knows only what the app *intended* (its metadata). |
| `customer.subscription.created` / `.updated` | ✅ | ✅ **yes** — `items.data[].price.id` (and legacy `plan.id`) |
| `invoice.paid` / `.payment_failed` | ✅ | carries line prices, but is a *renewal* signal — see §6 |
| `customer.subscription.deleted` | ✅ | n/a |

⚠️ **So the fix lands on the subscription event, not on `activate()`** — a correction to the brief's framing.
`activate()` cannot answer "what was bought" without expanding the session, and asking it to would mean
falling back to metadata, which is exactly the guess this pass exists to avoid.

**`App\Support\Billing\PurchasedPlan`** is the one resolver. It reads the Price off the subscription object
and maps it through `plans.stripe_price_id`.

### The reverse map is NOT unique

`plans` carries unique indexes on **`id`** and **`code`** only — **none on `stripe_price_id`** (verified with
`SHOW INDEX`). So two plan rows holding one Price id is a representable state, not a hypothetical.

| state | what happens |
|---|---|
| Price → exactly one active plan row | ✅ `plan_id` is written to it |
| Price → **no** plan row (incl. one whose only row is soft-deleted) | ⚠️ **refuses** — `plan_id` untouched, logged |
| Price → **more than one** plan row | ⚠️ **refuses** — picking the first would assign a customer by sort order |
| **two different Prices** on one subscription | ⚠️ **refuses** — this product sells one line per subscription |
| event carries no Price at all | nothing happens — silence must never move `plan_id` |

---

## 2 · The metadata is a cross-check — and the mismatch rule is NOT a blanket refusal

⚠️ **Here I diverge from the brief, deliberately, and it concedes the point.** The brief says *"refuse on a
mismatch"*. What actually ships:

| case | response | why |
|---|---|---|
| the Price resolves, metadata **agrees** | write it | nothing to say |
| the Price resolves, metadata **disagrees** | ⚠️ **write the CHARGED plan**, log the disagreement loudly | **the money already moved.** Refusing to record a plan would leave a paying customer on the wrong one *and* still charged — a second harm stacked on the first. The customer is recorded against what the money was actually taken for. |
| the Price does **not** resolve | ⚠️ **refuse** — leave `plan_id` alone, log | there is nothing trustworthy to write |

⚠️ **The metadata is NEVER a fallback.** When the Price cannot be resolved, the handler does *not* reach for
`metadata['plan']` — that is what the app hoped for, not what was charged, and using it would be a guess
wearing evidence's clothes. A mutation that resolves from the metadata instead of the Price goes red.

⚠️ **And the refusal is a RESULT, not an exception.** This runs inside a webhook: a throw is retried by
Stripe and becomes a poison message. So the subscription still activates, gets its status, its Stripe ids
and its period — only `plan_id` is withheld. **A Stripe retry of the same event is a no-op** (§6), so a
refusal does not spin.

---

## 3 · The re-capture follows — confirmed, not assumed

`plan_id` goes into the **same `update()`** as the status and ids, so the model's `updating` hook sees
`isDirty('plan_id')` and re-freezes the terms **in that same write**. Two saves would open a window where
the plan and its captured terms disagreed.

**Asserted, not reasoned:** after buying Essential from a Pro trial, `terms_price_minor` is **4900** and
`terms_pricing_type` is `flat`. A mutation that writes `plan_id` outside the model — so the hook never
fires — goes red on exactly that assertion.

**A period change follows too**, because each interval is its own `plans` row: buying `pro_yearly` moves
`plan_id` and the captured price to **99000**. The rate is unambiguously a term, and it follows.

⚠️ **If the newly-resolved plan were itself unresolvable**, `cannotRecapture()` would throw *inside the
webhook*. That cannot arise from this path — the plan was just resolved from a live `Plan::query()`, so it
exists at that moment — but if it ever did, Stripe would retry and hit the same state. **Reported as the
residual**; the resolver deliberately refuses *before* the write rather than letting the model throw after it.

---

## 4 · Is anything already wrong?

**No — established, not assumed.** There is exactly **one** subscription, and its
`stripe_subscription_id` is **NULL**: it has never been through Stripe, so there is no Price for it to
disagree with. Nothing is deployed.

**What a repair would need, if it ever applied** (reported, not written — it would be a no-op today and a
data change is not a code fix): for each subscription with a `stripe_subscription_id`, fetch the live Stripe
subscription, resolve its Price through the same `PurchasedPlan` map, and update `plan_id` through the model
so the terms re-capture. Any Price that does not resolve is a row for a human, not a default.

⚠️ **What an operator in that state would have seen:** the wrong plan name on `/app/billing` and in
Settings, the wrong price beside it, and — the sharp part — **the wrong features**, believing they had
bought something else.

---

## 5 · Every downstream reader, before and after

All of these read the same two fields, so they were **coherent with each other and wrong together**:

| reader | reads | before | after |
|---|---|---|---|
| `Organization::planAllows()` → the plan gates | `plan_features` **by `plan_id`** | ⚠️ **the trial tier's features — access, not display** | the purchased plan's |
| `/app/billing` | `$subscription->plan->name/code/interval` + captured terms | "Pro", €99.00 | "Essential", €49.00 |
| `/app/settings` summary | the same | "Vyrobel Pro", €99.00/mo | "Vyrobel Essential", €49.00/mo |
| `syncSubscriptionQuantity()` | the captured **shape** | the trial tier's shape | the purchased plan's |
| `Admin\CustomerController` / `PlatformCustomers` / `PlatformMetrics` | `plan_id`, plan name | the trial tier — so **platform revenue metrics grouped by the wrong plan** | correct |
| `Subscription::chargeFor()` | captured terms | the trial tier's price | the purchased price |

---

## 6 · Idempotency and ordering

**Re-delivery is already safe.** `webhook_events` carries
`UNIQUE (source, external_event_id)`, so a re-delivered event is recorded once and never re-applied.
Asserted here directly: posting the same event id twice leaves both `plan_id` **and `terms_captured_at`**
unchanged — the second delivery does not even re-freeze the terms. Writing `plan_id` does not weaken that,
because it rides inside the same single `update()`.

**Signatures are verified** against the raw body, and an invalid one is a `400` before anything is written.
The subscription-billing endpoint uses `services.stripe.webhook_secret`; the Connect endpoint is separate
with its own secret. Both are on the go-live list; the path is fully testable without live secrets because
the fake client signs with the configured test secret.

⚠️ **OUT-OF-ORDER IS NOT PREVENTED, AND THAT IS REPORTED RATHER THAN PAPERED OVER.** If Stripe delivered an
older `customer.subscription.updated` after a newer one, its older Price would overwrite a correct
`plan_id`. **This is pre-existing and not specific to the plan**: `sync()` already writes `status` and
`current_period_end` from whatever event arrives, with the same exposure. Closing it properly means a
watermark — storing the event's `created` timestamp and ignoring anything older — which is a schema change
and its own decision. **Not done here; named.**

---

## 7 · Money did not move

The money gate is quoted with its mode in the pass report. The demo's single subscription ends the pass
**coherent**: `plan_id = essential_monthly`, captured terms **4900**, and **no Stripe subscription id**
(it never purchased) — so there is no Price for the other two to disagree with.

---

## 8 · What is guarded

`tests/Feature/BillingPurchasedPlanTest.php` — 7 tests, driven through a **genuinely signed webhook POST**.

⚠️ **Every fixture starts on a DIFFERENT plan from the one it buys**, and asserts that it did — a probe that
begins where it expects to end proves nothing, which the previous pass learned the hard way.

**Mutations** — each applied, confirmed landed in its own block, run, reverted:

| mutation | |
|---|---|
| the Price resolution removed (`plan_id` stays stale) | **RED (4)** |
| the ambiguity refusal removed (take the first match) | **RED (1)** |
| `plan_id` written outside the model, so the re-capture never fires | **RED (2)** |
| ⚠️ **THE WRONG FIX — resolve from the METADATA instead of the Price** | **RED (4)** |

The last one is the point: it defends *the Price is the truth*, not merely *something writes `plan_id`*.
