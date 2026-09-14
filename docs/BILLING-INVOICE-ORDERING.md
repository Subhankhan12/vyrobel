# A late invoice must not reactivate a cancelled account

**2026-09-04.** Third instance of a shape fixed twice before (`docs/BILLING-WEBHOOK-ORDERING.md`,
`docs/CONNECT-ACCOUNT-ORDERING.md`) — with a wrinkle that makes it different from both.

## The defect

`renew()` wrote `status = 'active'` **unconditionally** and `current_period_end` straight from the invoice
payload. `SubscriptionStatus::hasFullAccess()` is `onTrial() || active()`, so an out-of-order
`invoice.paid` handed a **cancelled org the entire app back** and extended its period with it. Nothing
would have caught it: there is still no reconciliation anywhere in this product, which is the second of the
three reasons the re-fetch was chosen over a watermark in the first place.

`invoice.payment_failed` had the same shape in the other direction — it wrote `past_due` from the payload
just as unconditionally.

## #1 · The event is not the object

| handler | event carries | writes to | so re-fetch |
|---|---|---|---|
| `invoice.paid` / `invoice.payment_failed` | an **invoice** | a **subscription** | **the subscription** |
| `settlePayment` | a payment intent | the `Payment` the intent belongs to | *nothing — already safe* |
| `settleRefund` | a refund | the `Payment` refund row it names | *nothing — already safe* |

Re-fetching the **invoice** would have proven the invoice is still paid and said nothing whatever about
whether the subscription is still active. **The object to re-fetch is the one being changed, not the one
that arrived.** `retrieveSubscription()` already existed from the first ordering pass; no new client method
was needed, so no new fake was needed either.

**When an invoice names no subscription** — a one-off charge — it **refuses**: logs and writes nothing. It
does not fall back to the payload, and it does not throw, because a retry would deliver the same
subscription-less invoice forever. A second refusal was added alongside it: an invoice naming a *different*
subscription than the org holds also writes nothing.

**The period comes from the subscription, argued.** An invoice's `period_end` is the span that invoice
**billed for**; `current_period_end` is how long the subscription is **paid up until**. They coincide on a
clean renewal and diverge on proration, mid-cycle plan changes and one-off items — and the second is what
the column means. Taking the invoice's period because it arrived with the payment is the same class of
mistake as taking its status.

⚠️ **No API version is pinned anywhere in this app**, so the payload shape is whatever the Stripe account's
default emits. Older versions put the id at `invoice.subscription`; 2025-era versions moved it to
`invoice.parent.subscription_details.subscription`. **Both are read.** Reading only one would have been
silently inert on half the possible accounts — the failure mode that looks exactly like success.

## #2 · What a reactivated cancellation causes — tested, not reasoned

`hasFullAccess()` = `onTrial() || active()`, so writing `active` on a cancelled org **restores full app
access** and re-opens the plan gates. The probe cancels an org, delivers a stale `invoice.paid`, and
asserts the account is **still cancelled** and `hasFullAccess()` still false — **and that the period did not
move either**, since a restored period end on a lapsed account is service that was never paid for.

**The reverse direction is real too**, and it is the one that costs a *paying* customer their service: a
stale `invoice.payment_failed` marking a live account `past_due`. **No asymmetry was applied to the write
itself** — both directions now read the live subscription, so the live object decides both ways, and there
is no direction left in which a payload can win. The asymmetry that *does* apply is in the failure
handling, below.

## #3 · Both settlement handlers were already safe — proven, not assumed

**Neither was changed.** A handler that needs no fix is the best outcome available.

Both are **monotonic**: a `Payment` leaves `pending` exactly once. `settlePayment` returns early unless
`status === 'pending'`; `settleRefund` re-checks the same condition **inside a `lockForUpdate`**. A second
webhook of either kind, in any order, finds a non-pending row and writes nothing. `tests/Feature/SettlementOrderingTest.php`
delivers two distinct, contradicting, correctly-signed events and asserts the second changes neither the
status nor even the `processed_at` stamp.

Their inputs genuinely differ and were checked separately: `settlePayment` derives its status from the
**event type**; `settleRefund` reads it from the **payload's own `status`** — deliberately, because
`refund.updated` also fires for metadata changes, so the name is not trusted. A probe asserts a
`refund.updated` carrying `status: pending` settles nothing.

**Ranked, had they been unsafe:** a stale *refund* settlement is worse — money has left the business while
the product believes it still holds it, and the folio is wrong in the customer's favour with no trace. A
stale *payment* settlement marks a folio paid on a charge that was reversed: also wrong, but it shows up as
an unreconciled balance rather than as money already gone.

⚠️ **What the monotonicity does cost, recorded honestly: it is FIRST-wins, not CORRECT-wins.** A
PaymentIntent that fails an attempt and then succeeds emits `payment_intent.payment_failed` *then*
`payment_intent.succeeded`, and the first delivery locks the row. That is **not an ordering defect** — it is
the in-order case — and changing it means allowing a `failed → succeeded` transition on a money path.
**Reported, not fixed here.**

## #4 · The claim separation holds for this endpoint

`invoice.paid` runs through `StripeWebhookController::once()` → `WebhookEventClaim::once()`, the single
merged rule. A throw inside the handler is therefore no longer fatal: the row is written **`failed` with
its reason**, it is re-claimable, and Stripe's retry is admitted. The probe asserts the failed row exists
and that its `error` names the subscription it could not read — a diagnosable failure, not a silent return.

## #5 · The fake fails closed

`FakeStripeClient::retrieveSubscription()` returns `null` for an unregistered id rather than synthesising
one, so **an unregistered subscription can never read as active**. Not registering it is exactly how the
unreachable-Stripe probe is written. No new client method was added, so no new fake was owed.

## #6 · Degrade or throw, per handler

| handler | on an unreadable subscription | rejected |
|---|---|---|
| `invoice.paid` | **THROW** | degrading to the payload (*that is the defect*), and returning silently (*hides a real renewal failure and leaves the period unextended*) |
| `invoice.payment_failed` | **THROW**, same path | as above |
| `settlePayment` / `settleRefund` | *n/a — no re-fetch* | — |

`invoice.paid` moves an account **towards service**, which is the Connect pass's ENABLE direction, and its
rule was: throw rather than write an unverifiable enable. A throw is right here rather than a refusal
because **a retry can genuinely succeed** — an unreachable Stripe is transient — which is the test that
separates the two. Both invoice events share the one code path deliberately: they had the same defect in
the same shape, and one definition cannot drift from itself.

Everything that refuses **logs what it refused and why**.

## #8 · Mutations — every one landed, every one red

| mutation | landed | result |
|---|---|---|
| prefer the EVENT payload over the live object (the wrong fix) | ✅ | **RED** |
| make the unverifiable path silent instead of refusing | ✅ | **RED** |
| read only the OLD payload shape | ✅ | **RED** |
| drop the "names a different subscription" guard | ✅ | **RED** |
| take the period from the INVOICE instead of the subscription | ✅ | **RED** |

Re-run against the final code after every restore; the service file is byte-identical to its pre-mutation
state.

## ⚠️ Where this brief was wrong, and a reachability finding it did not mention

**The live path does not currently reach `renew()` at all.** Billing events resolve their org from
`metadataOrganizationId()` — the **event object's** metadata — and `createSubscriptionCheckout()` stamps
that metadata on the Checkout **Session** and on `subscription_data` (the **Subscription**). Stripe does not
copy subscription metadata onto the invoices it generates, so a production `invoice.paid` arrives with no
`organization_id` and is answered **`202 ignored`** at the door. A probe asserts exactly that.

So the reported defect is **real in the code and currently unreachable in production** — which cuts both
ways, and the second way is larger than the first: **`invoice.paid` and `invoice.payment_failed` are both
declared handled and both are effectively inert**, meaning a genuine renewal never marks an org active and a
genuine failed payment never marks one `past_due`. That is a live-path gap in its own right, it is not an
ordering defect, and it was **not** fixed here.

## ⚠️ What I cannot prove

That Stripe's own delivery order matches what these probes simulate, and that a real account's API version
emits one payload shape rather than the other — both are read, but only one can be exercised against a real
account. And no assertion here has run against live Stripe: `StripeSafetyGuard` refuses a live key outside
production, and every probe runs on the fake.
