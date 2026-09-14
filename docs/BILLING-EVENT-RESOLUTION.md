# Two billing handlers were inert in production, and nothing said so

**2026-09-04.** Found by a probe while fixing something else, not by any signal the product emits.

## The defect

Billing events resolved their organisation from `data.object.metadata.organization_id`.
`createSubscriptionCheckout()` stamps that metadata on the Checkout **Session** and on `subscription_data`
— i.e. on the **Subscription**. **Stripe does not copy subscription metadata onto the invoices it
generates.** So a real `invoice.paid` or `invoice.payment_failed` arrived carrying none, resolved to
nothing, and was answered `202 ignored`.

⚠️ **The consequence was revenue, not display.** `invoice.payment_failed` is the only thing that ever moves
an organisation to `past_due`, and `hasFullAccess()` is `onTrial() || active()`. With the handler inert, a
customer whose card stopped working **kept full access indefinitely** — the app's view of who is paying was
frozen at whatever the checkout last set.

⚠️ **And a code comment asserted the opposite.** `StripeClient::createSubscriptionCheckout():153` said the
stamp was *"so subscription/invoice webhooks resolve the org"*. The subscription half is true; the invoice
half never was. That sentence is why nobody looked.

## #1 · Every handled event — live or silently inert

| event | object delivered | carries `metadata.organization_id`? | was |
|---|---|---|---|
| `checkout.session.completed` | Checkout Session | ✅ `'metadata' => $metadata` on the session | **live** |
| `customer.subscription.created` | Subscription | ✅ via `subscription_data.metadata` | **live** |
| `customer.subscription.updated` | Subscription | ✅ | **live** |
| `customer.subscription.deleted` | Subscription | ✅ | **live** |
| `invoice.paid` | Invoice | ❌ **never** | ⚠️ **INERT** |
| `invoice.payment_failed` | Invoice | ❌ **never** | ⚠️ **INERT** |

**No other handler was inert** — the two named were the two, and the brief was right about that.

**The Connect endpoint does not share the weakness.** It never used metadata: it resolves
`Owner::where('stripe_connect_account_id', …)`, `Payment::where('gateway_ref', …)` and
`Payout::where('external_ref', …)` — resolution from the object, already. The payout side is clean.

## #2 · Resolved from the object

One resolver, `App\Support\Billing\BillingEventOrganization`, used by every handled event.

| order | anchor | what it proves |
|---|---|---|
| 1 | `stripe_subscription_id` | the most specific — names exactly one subscription |
| 2 | `stripe_customer_id` | broader; one customer can hold several subscriptions over time |
| 3 | metadata | what the app **intended** at checkout |

⚠️ **An ambiguous resolution is a refusal, not a guess.** Neither column is unique —
`stripe_customer_id` carries a plain index and **`stripe_subscription_id` has no index at all** — so "two
rows match" is a shape the schema permits. Same rule `PurchasedPlan` reached on `plans.stripe_price_id`:
refuse, name the ambiguous id, write nothing.

⚠️ **Scopes are dropped to read across tenants (a webhook has no tenant context — this *is* the tenant
resolution), but `SoftDeletingScope` is deliberately kept**, or a deleted subscription could resurrect an
organisation's billing.

### ⚠️ Where this brief was wrong: metadata cannot be "a cross-check, never a fallback"

On `checkout.session.completed` and `customer.subscription.created` — the **first** events of a purchase —
the local row has no `stripe_subscription_id` and no `stripe_customer_id` yet, because **those ids are what
the event is delivering**. There is nothing to anchor to, and metadata is the only evidence that exists.
Making it purely a cross-check would have broken the one path that already worked.

The rule shipped is therefore: **anchors first where they resolve, metadata where they cannot, and a
cross-check whenever both are present** — and a disagreement between them is a **refusal**, because writing
billing state into the wrong tenant is the worst outcome on this path.

## #3 · What `past_due` actually does — tested

`past_due` → `isLapsed()` → `EnsureSubscriptionActive` redirects **the entire `/app` group** to the
paywall. Front desk, reservations, calendar — everything except the billing routes themselves. Proven:
`/app/dashboard` and `/app/reservations` redirect; `/app/billing` stays 200.

⚠️ **`BillingAccessGateTest` covered an expired trial and a `canceled` subscription. It never covered
`past_due`** — the state this pass just made reachable — so its effect was untested until now.

⚠️⚠️ **Locking on the first failure is almost certainly wrong, and this pass did not do it.** Stripe retries
a failed invoice over days (typically 3–4 attempts across ~2 weeks under Smart Retries) before giving up.
`invoice.payment_failed` fires on the **first** attempt. So the status is now accurate — and **what gates on
it was not touched**. A first declined card would otherwise put a paying customer out of their own front
desk mid-shift, which would be worse than the silence this pass removed.

## #5 · The grace options — costs stated, none decided

| option | cost |
|---|---|
| **Revoke on the first `invoice.payment_failed`** | a transient decline (expired card, travel block, bank hiccup) locks a live hotel out of its own front desk mid-shift. Fastest to collect, worst customer harm. |
| **Revoke only when Stripe gives up** (`customer.subscription.updated` → `unpaid`/`canceled` after retries) | matches the money: access ends when Stripe stops trying. Costs ~2 weeks of service to a genuine non-payer. Needs no new state — that event is already live. |
| **Revoke after a notice period** | kindest and most conventional; needs a new timestamp, a notice email and a scheduled check — i.e. new machinery, and the scheduler's own silence is a known open item. |
| **Never revoke; warn only** | zero lockout risk, no collection pressure. |

⚠️ **This is commercial and it is the founder's.** Nothing was chosen and nothing that gates on `past_due`
was changed.

**The operator's owners and guests are unaffected.** `EnsureSubscriptionActive` appears on exactly **one**
route group — `/app`. The owner portal and the guest realm do not carry it, so third parties who did
nothing wrong keep their access. Asserted, not assumed.

## #4 · "Ignored" is not a result

Before: an event whose org could not be resolved returned `202 ignored` **and left no trace at all** — no
log line, no `webhook_events` row. It was indistinguishable from an event handled perfectly. That
indistinguishability is what hid two permanently inert handlers for this product's entire life.

Now it is recorded through `WebhookEventClaim::recordUnresolved()` — one definition, beside `once()`, for
the same reason the two receivers' idempotency rule was merged there — as a row that **does not read as
`processed`**: status `failed`, the reason in `error`, `signature_verified` true. It is re-claimable, so a
redelivery once the org exists can still apply for real. It stays **202**, because retrying an event naming
an organisation this deployment does not have would become a poison message.

**The query the founder can run:**

```sql
select event_type, error, received_at from webhook_events where status = 'failed' order by id desc;
```

## #6 · Nothing that worked was moved

`customer.subscription.*` still resolves (proven with a first-purchase fixture whose local row has **no**
Stripe ids). The plan still comes from the **price**, the shape from the **snapshot**, the quantity **live**
— all three untouched. The settlement handlers were proven already safe last pass and were not touched.

## #8 · Mutations — every one landed, every one red

| mutation | landed | result |
|---|---|---|
| **the wrong fix** — metadata first, object only as a fallback | ✅ | **RED** |
| drop the ambiguity refusal (take the first match) | ✅ | **RED** |
| drop the metadata/object cross-check | ✅ | **RED** |
| read only the OLD invoice payload shape | ✅ | **RED** |
| go back to silence — record nothing for an unresolved event | ✅ | **RED** |

⚠️ Honest note on the first: for an invoice carrying **no** metadata, metadata-first and object-first
coincide, so the probe that actually discriminates them is the **cross-check** one (metadata naming a
different org than the Stripe ids). That is the test that caught it.

⚠️ **A test from the previous pass had to be inverted, not deleted.** It asserted *"a real renewal reaches
the handler only when the invoice itself carries the org metadata"* — a correct record of the defect. Same
event, opposite expectation now.

## ⚠️ What I cannot prove

That a real Stripe invoice omits the metadata — that is Stripe's documented behaviour and the probe
reproduces the shape, but nothing here has run against live Stripe (`StripeSafetyGuard` refuses a live key
outside production). And which payload shape a given account emits: both are read, only one can be
exercised per account.
