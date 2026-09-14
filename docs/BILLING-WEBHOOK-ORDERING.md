# Out-of-order webhooks — solved by reading the object back, not by guarding

**2026-09-03.** Re-delivery was already handled. **Order was not.** Two distinct events in the wrong
sequence wrote the older payload over newer state.

---

## 1 · Re-fetch or watermark — argued, and the client's capability established first

⚠️ **`StripeClientInterface` had NO `retrieveSubscription`.** It *does* have `retrieveConnectAccount`, which
gave an exact precedent — a retrieve-by-id returning a small DTO, with a live implementation and a fake.
So re-fetch was **not "nearly free"** (a correction to the brief), but it was small and well-understood:
one DTO, three method implementations, and a shared resolver core.

| | re-fetch | watermark |
|---|---|---|
| what it does | writes **current truth**, so order is irrelevant | compares `event.created` and **skips** older events |
| the case | **removed** | **guarded** |
| same-second events | ⚠️ irrelevant | ⚠️ **cannot be ordered at all** |
| worst failure | a network call fails → the delivery **retries** | a legitimate event is **dropped** → permanent divergence |
| cost | one API call per subscription event | a migration, plus a comparison to get right |

**Chosen: re-fetch.** Three reasons, in order of weight:

1. ⚠️⚠️ **Stripe's `created` is a UNIX SECOND, and the subscription object carries no version or sequence
   field** — checked, not hoped. So a watermark facing two events in one second must either *apply the
   older* (defeating itself) or *drop a real event*. There is no third option, and a subscription update
   sharing a second with its invoice is ordinary.
2. ⚠️⚠️ **A wrong skip WOULD NEVER BE NOTICED.** There is no reconciliation job anywhere in this product,
   no drift check, no alert. The application would diverge from Stripe permanently and silently — which is
   the defect the previous pass fixed, wearing a new face. **Said in those words because the brief asked for
   exactly that test, and the answer decides it.**
3. **A case removed beats a case handled** — this project's own line, from two passes ago.

**The honest costs, stated:** the handler now depends on a network call. A Stripe outage means
`retrieveSubscription` throws, the delivery fails, and Stripe **retries** — which is correct behaviour and
already how this endpoint is designed to fail, but it does change the handler's failure mode from
"always completes" to "may retry". Rate limits are not a concern at subscription-event volume (a handful
per org per year).

⚠️ **If the founder would rather not put a network call on the webhook path, the watermark is the fallback**
— it would need a per-subscription `last_event_at` column, an apply-on-tie rule (never drop), and a log on
every skip. It would still not order same-second events. Recorded so the choice stays reversible.

---

## 2 · Nothing is skipped — there is no drop branch to get wrong

⚠️ **Under re-fetch, every event is applied.** It is simply applied to current truth. The failure mode the
brief feared — discarding a legitimate event — **does not exist here**, because nothing is ever discarded.
Asserted directly: two out-of-order deliveries are both recorded in `webhook_events` and both reach
`processed`.

**One degraded path exists and it is loud.** When the object cannot be read back, the handler logs
`Could not read the Stripe subscription back — applying the EVENT payload, which may be stale` and then
applies the event. ⚠️ The event payload is **Stripe's own signed statement** of state at that moment — it is
evidence, only older, not a guess. In production this is close to unreachable: an unreachable Stripe
*throws* and the delivery retries, so landing here means Stripe returned "no such subscription".

**A mutation that removes that log goes RED** — a silent degraded path is the thing being prevented.

---

## 3 · The tie — tested, not reasoned

Two events carrying **the same `created` second**, delivered backwards, with Stripe holding the newer truth:
the final state is the newer one. Under re-fetch the tie is simply not a question, and the test says so.

⚠️ **Nothing better than a second exists to break it.** The event carries `created` (seconds); the
subscription object carries no version, sequence or revision field. **That absence is itself an argument for
re-fetch**, and it is why the tie test is written the way it is.

---

## 4 · Scope — and what a null means

⚠️ **The scope question dissolves.** A watermark would have had to be **per subscription, never global** (a
global one drops a legitimate event for a different customer), and existing rows with no watermark would
have needed a documented "first event always applies" rule. **None of that exists**, because nothing is
stored and nothing is compared.

**Other objects with their own event streams, and their exposure — reported, not changed:**

| stream | exposure |
|---|---|
| **Connect `account.updated`** | ⚠️⚠️ **the same defect, on money leaving** — see §5 |
| `invoice.paid` → `renew()` | ⚠️ writes `status = active` and `current_period_end` **straight from the event**. A late invoice could reactivate a cancelled account. **Out of scope this pass** (the brief excludes the invoice path); the same re-fetch would close it. |
| `transfer.created/updated` → `confirmTransfer()` | lower — `processing → paid` is a monotonic, terminal transition, so a stale repeat is inert |
| `payment_intent.*` → folio settlement | lower, same monotonic shape |

---

## 5 · Every field at risk — twelve, not three

⚠️ **The report named three. The real set is five written directly, plus seven written derivatively.**

| rank | field | what a stale write means |
|---|---|---|
| **1** | `status` | ⚠️⚠️ **the sharpest.** A stale `active` **restores access to a cancelled account**; a stale `past_due`/`canceled` **revokes it from a paying one**. Written by five of the six handlers. |
| **2** | `plan_id` | the wrong plan → **the wrong FEATURES** (`planAllows()` reads `plan_features` by `plan_id`) and the wrong price |
| **3** | the seven `terms_*` columns | ⚠️ **derived, and easy to miss**: a stale `plan_id` fires the model's re-capture hook and silently re-freezes the *wrong* terms |
| **4** | `current_period_end` | a stale later value **extends a lapsed subscription**; a stale earlier one lapses a live one |
| **5** | `stripe_customer_id` | misdirects the VAT-id push to the wrong Stripe customer |
| **6** | `stripe_subscription_id` | misdirects a later quantity sync |

### ⚠️⚠️ The Connect endpoint has the same defect, on money leaving — reported, not fixed

`StripeConnectWebhookController::handleAccountUpdated()` reads
`$enabled = $event->objectBool('payouts_enabled')` **straight from the event payload** and writes
`owners.stripe_connect_payouts_enabled`. A late `account.updated` carrying an older `true` would
**re-enable payouts for an owner Stripe has since disabled**.

⚠️ **And the fix is already available there**: `retrieveConnectAccount($accountId)` exists on the interface
today, so it is a one-line change — read the account back instead of trusting the payload. **Out of scope
by instruction; named precisely so it is not rediscovered.**

---

## 6 · The fake, and testability without live keys

**The fake FAILS CLOSED**, mirroring `retrieveConnectAccount`'s `?? false`: an unregistered subscription
returns **null**, it does not synthesise one. A fake that invented a plausible subscription would let a test
pass against data nobody wrote — the `FakeChannexClient` lesson applied where failing open is not
acceptable, because this fake stands in on a **charging** path.

⚠️ **A re-fetch can never reach live Stripe from a test.** `StripeSafetyGuard::forceFakeOutsideProduction()`
binds the fake whenever `services.stripe.fake` is true (the default) **and** whenever a live key appears
outside `production`. The ordering behaviour is fully testable without either webhook secret, because the
fake signs with the configured test secret.

⚠️ **`BillingStripeTest` — certified and UNAMENDED — now exercises the degraded path**: it posts a
`customer.subscription.updated` for a subscription the fake does not know, gets null, falls back to the
event, and still asserts `past_due`. That is a real, useful second coverage of the fallback, and it required
no change to the file.

---

## 7 · What is guarded

`tests/Feature/BillingWebhookOrderingTest.php` — 5 tests.

⚠️ **The proof is TWO DISTINCT correctly-signed events delivered BACKWARDS** — never a duplicate, which the
unique constraint already handles and which would prove the wrong thing. Every fixture starts on
`pro_monthly`, **a plan neither event would produce**, and asserts that it did.

**Mutations** — each applied, confirmed landed in its own block, run, reverted:

| mutation | |
|---|---|
| the re-fetch removed (write the event payload again) | **RED (2)** |
| ⚠️ **the wrong way round — the EVENT preferred over the live object** | **RED (2)** |
| the degraded path made **silent** (log removed) | **RED (1)** |

⚠️ The first was **re-run against the final code** after a later PHPStan-driven edit, rather than trusting
the earlier run.

---

## 8 · Two instrument notes

- **Three of my own tests failed on the first run** — they asserted `Log::warning` was called *exactly once*
  and now saw two, because they never registered a subscription in the fake. My assertions were over-tight,
  not the code. They now register the live object, which is the normal path.
- ⚠️ **I doubted PHPStan and was wrong.** It flagged `$live?->status ?? …` as a redundant nullsafe and I
  suspected a false positive, since my own test proves `$live` can be null. PHPStan was right: **PHP's `??`
  uses isset() semantics**, so a property read on a null object yields the right-hand side rather than
  erroring. The `?->` really was redundant. A comment now says so, so nobody "fixes" it back.
