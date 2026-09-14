# A late `account.updated` re-enabled payouts Stripe had disabled

**2026-09-03.** The subscription re-fetch shape, applied to the rail where money leaves.

---

## 1 · Who reads the flag, and what a stale TRUE actually causes

`owners.stripe_connect_payouts_enabled` has **three functional readers** (plus the model's cast/fillable and
the webhook writer):

| reader | what it does | consequence of a stale TRUE |
|---|---|---|
| **`StripeConnectPayoutRail::send()`** | pre-flight check before `createTransfer()` | ⚠️ a transfer is **attempted** |
| `PayoutRecovery` | builds the owner's "why can't I be paid" blockers | the owner is not told the real blocker |
| `OwnerPayoutMethod` (Livewire) | display only (`connect_enabled`) | a wrong badge |

### ⚠️⚠️ Attempted-and-refused, NOT recorded-as-sent — which lowers the severity, and the brief should know

Traced through `OwnerPayoutService::pay()`:

- the rail returns `PayoutResult(successful: false, …)` → the payout is written
  **`status = 'failed'`, `failure_reason`, `failed_at`**, plus a `payout.failed` **domain event** and an
  **audit row**. Visible, recoverable, and not an accounting lie.
- on success the rail returns **`pending: true`** → `status = 'processing'`, **`paid_at` stays null**, and the
  statement is **not** marked paid. Only the `transfer.created` webhook flips it to `paid`.
- **no `owner_ledger` row is written on either branch** by this service.

⚠️ **And there IS a belt beside this brace: Stripe itself.** The flag is a **filter, not the gate** — the real
authority is `createTransfer()`, and a refusal is caught and recorded. That is a deliberate belt of the kind
this project has built before, and it is why a stale flag is recoverable rather than catastrophic.

⚠️ **The residual I cannot verify from here, stated as such.** Stripe's `payouts_enabled` governs the
connected account paying out **to its bank**; a platform→account **transfer** may still be accepted. If it
is, a stale TRUE produces a transfer that goes `processing → paid` on `transfer.created` while the owner's
bank never receives it — money parked in their Stripe balance, recorded as paid. **That is code-verified on
our side and unverified on Stripe's**, and it is the case that keeps this worth fixing.

---

## 2 · Every Connect field written from a payload — and the good news

⚠️ **The brief expected another twelve-where-three. It is not.** `account.updated` writes **exactly one**
field from the payload, and the two fields the brief worried about are **not written from payloads at all**:

| field | written from a payload? |
|---|---|
| **`payouts_enabled`** → `owners.stripe_connect_payouts_enabled` | ⚠️ **YES** — this defect |
| **`charges_enabled`** | ❌ **no** — it exists on the `StripeConnectAccount` DTO and is only ever read through `retrieveConnectAccount()`, i.e. already live |
| **`details_submitted`** | ❌ **no** — same |
| requirements / country / currency / capabilities | ❌ not modelled, not cached, not written |

⚠️ **So the "money COMING IN on a closed rail" case does not exist**: `charges_enabled` is already read live
everywhere it is used. Recorded because the brief asked and the answer is reassuring.

**The other Connect handlers do write from payloads**, and are reported rather than changed:

| handler | payload-derived write | risk |
|---|---|---|
| `handleRefundSettlement` | `status` → `settleRefund($refund, $to)` | a late `refund.failed` after a success would settle a refund the wrong way — **reported** |
| `handlePaymentIntent` | the event **type** → `settlePayment($payment, $to)` | a late `payment_intent.payment_failed` after a success — **reported** |
| `handleTransfer` | `confirmTransfer($id)` | `processing → paid` is monotonic and terminal; a stale repeat is inert |

---

## 3 · Re-fetch — and the degraded path needed different reasoning here

`retrieveConnectAccount($accountId)` already existed and returns a `StripeConnectAccount` whose three booleans
default **`?? false`** in the fake. The account is now read back and **that** is written, so two events in any
order write the same answer.

⚠️⚠️ **"A one-line change" was REPORTED and it was wrong — the ordering was the real content.** `once()` claims
the `webhook_events` row **first** and only then runs its closure:

```php
$webhookEvent = WebhookEvent::createOrFirst([...]);
if (! $webhookEvent->wasRecentlyCreated) { return 'duplicate'; }
$apply();                                   // ← a throw HERE leaves the row claimed
```

So a read-back placed inside the closure would, on failure, **consume the event**: Stripe's retry reads
`duplicate` and the event is **lost permanently** — the exact failure this sequence of passes exists to
prevent. **The read-back therefore happens before `once()`.** A mutation that moves it inside goes RED.

⚠️ **This is a pre-existing hazard for the other handlers too** — `settlePayment`, `settleRefund` and
`confirmTransfer` all run inside that closure and can throw. **Reported, not changed.**

### Should the fallback apply the payload at all here? — argued

| option | verdict |
|---|---|
| apply the payload whenever the read fails | ❌ on an ENABLE this is the whole defect, restored |
| write `false` whenever the read fails | ❌ a Stripe outage would **wrongly disable healthy owners**, and `PayoutRecovery` would tell them "your payout account is not ready" — a false statement to a customer |
| **leave it untouched and let the delivery fail** | ✅ for an **ENABLE**: the flag keeps its last **confirmed** value and Stripe retries |
| **apply the payload** | ✅ for a **DISABLE**: see §4 |

**A Stripe outage therefore fails the delivery on the enable direction and Stripe retries** — the established
pattern on this endpoint, and correct: a money gate must not be opened on unverified evidence.

---

## 4 · Asymmetric by direction, and the symmetric case named

**Wrongly disabling** delays an owner's money and they tell us. **Wrongly enabling** sends money at a rail
Stripe has closed, and — with no reconciliation job, no drift check and no alert anywhere in this product —
nobody finds out. These are not equally bad, so they are not treated alike:

- **ENABLE requires a confirmed live read.** If the account cannot be read, rethrow before the idempotency
  claim; Stripe retries; the flag keeps its last confirmed value.
- **DISABLE may apply from the payload alone.** It is Stripe's own **signed** statement, and acting on it
  early errs towards *not* sending money.

⚠️ **The symmetric alternative — never apply the payload in either direction — was considered and rejected.**
It is simpler and it never writes on unverified evidence, but during a Stripe outage it would leave a stale
**ENABLED** flag standing while Stripe had already closed the rail. That is choosing simplicity over the
money-leaving direction, which is exactly the trade this project declines to make.

---

## 5 · The payout run: filter, not gate

`StripeConnectPayoutRail::send()` reads `$payout->owner->stripe_connect_payouts_enabled` **live from the
database at send time**, so a stale row is enough to get past the pre-flight. It does **not** re-check the
account with Stripe first — but it immediately calls `createTransfer()`, where **Stripe is the real
authority**. So the flag filters; Stripe gates.

⚠️ **A refused payout inside the nightly run would be quiet.** The failure is recorded on the payout row
(`failed` + reason) and as a `payout.failed` domain event and audit row — but the scheduler surfaces nothing
and there is no monitor. **Reported; no monitoring built.**

---

## 6 · The fake cannot invent an enabled account

`FakeStripeClient::retrieveConnectAccount()` answers `$this->connectAccounts[$id] ?? false` for all three
booleans — an **unregistered account reads as fully disabled**. `FakeChannexClient` defaults to ON because
that is right for a channel manager; on a money rail the same default would be catastrophic, and this fake
does the opposite. **Asserted directly** by a test that deliberately does not register the account.

`StripeSafetyGuard::forceFakeOutsideProduction()` binds the fake whenever `stripe.fake` is true **or** a live
key appears outside `production`, so **a re-fetch can never reach live Stripe from a test**. The path is fully
testable without the Connect webhook secret, because the fake signs with the configured test secret.

---

## 7 · What is guarded

`tests/Feature/ConnectAccountOrderingTest.php` — 5 tests.

- **two DISTINCT correctly-signed `account.updated` events delivered BACKWARDS** (newer *disabled* first,
  older *enabled* late), Stripe holding disabled → final state **disabled**. Non-vacuous: the flag genuinely
  moves from its starting `true`, and **both** events are asserted `processed` rather than one dropped.
- the **ENABLE** degraded path: the delivery **fails (500)**, the flag is untouched, **and the
  `webhook_events` row is not created** — so Stripe's retry is still alive.
- the **DISABLE** degraded path: applied, and logged.
- the fake cannot invent an enabled account.
- re-delivery on this endpoint is still idempotent.

**Mutations** — each applied, confirmed landed in its own block, run, reverted:

| mutation | |
|---|---|
| ⚠️ **the WRONG FIX — the EVENT preferred over the live account** | **RED (4)** |
| the degraded path made **silent** | **RED (2)** |
| the read-back moved **inside `once()`** (the event gets consumed) | **RED (1)** |

⚠️ The first was **re-run against the final code** after Pint's edits, rather than trusting the earlier run.
