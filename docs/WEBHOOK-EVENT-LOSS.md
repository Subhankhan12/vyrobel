# A throw inside `once()` destroyed its own event

**2026-09-03.** The idempotency guard and the loss mechanism were the same line.

---

## 1 · What `webhook_events` recorded, and what the check actually read

⚠️ **The states already existed.** `WebhookEvent` has had **`received` · `processing` · `processed` ·
`failed`** and an `error` column all along — **so this needed no migration.**

⚠️⚠️ **But the duplicate check never read them.** Both Stripe receivers did:

```php
$row = WebhookEvent::createOrFirst([...]);
if (! $row->wasRecentlyCreated) { return 'duplicate'; }   // ← the ROW's existence, never its STATE
$apply();                                                  // ← a throw here leaves the row behind
$row->update(['status' => 'processed']);
```

So a failure inside `$apply()` **consumed the delivery**: Stripe retried, the row existed, the retry was
answered "duplicate", and the event was **lost permanently**.

### Retryable yet not concurrent — how both are met

| requirement | how |
|---|---|
| a **completed** delivery never runs twice | `status === processed` → `duplicate`, unconditionally |
| a **failed** claim is retryable at once | the catch writes `failed`, which the re-claim admits |
| a **concurrent** second delivery is refused | the eligibility test and the claim are **one `UPDATE`** — the database decides and the loser affects zero rows. A `SELECT` then `UPDATE` would let both through |
| a **dead** claim is recoverable | `processing` older than **15 minutes** may be taken over (§4) |

---

## 2 · ⚠️ CHECKED FIRST: is the closure in a transaction? — and it is half the answer

**The controllers wrap nothing.** But each inner service wraps **its own writes**: `settlePayment`
(`FolioService:353`), `settleRefund` (`:577`) and `confirmTransfer` (`OwnerPayoutService:167`) each use
`DB::transaction`.

⚠️ **So the money writes always rolled back — only the claim did not**, because it is created *outside* those
transactions. **A retry was therefore always clean; it was simply never admitted.** That is why the fix is a
claim-lifecycle change and not a rewrite of the handlers.

⚠️ **And all three are already partially idempotent**, which matters: each guards on state and returns early
(`settlePayment`: `status !== 'pending'`; `confirmTransfer`: no matching `processing` payout → no-op;
`settleRefund`: type/status guard inside the lock). **So "letting retries through" cannot double-write.**

**What realistically throws, ranked by what is lost:**

| rank | handler | realistic throw | lost if the event never lands |
|---|---|---|---|
| **1** | `settlePayment` | a DB error/deadlock inside the transaction; ⚠️ **a domain-event or audit recorder failing AFTER it commits** | ⚠️⚠️ **a guest who paid and a folio that never learned** — money in, desk shows unsettled |
| **2** | `settleRefund` | `lockForUpdate()->findOrFail()` on a vanished row; the same post-commit recorders | **money returned that the product still believes it holds** |
| **3** | `confirmTransfer` | the same post-commit recorders | a payout **stuck at `processing` forever** — recorded and visible, but never closed |

⚠️ **One residual this fix does not close, reported:** if the transaction commits and the *post-transaction*
domain/audit recorder then throws, the retry hits the handler's own idempotency guard and returns early — so
the **audit row stays permanently missing** even though the event is now processed. The money is right; the
trail has a hole.

---

## 3 · ⚠️⚠️ The re-fetch DID make this worse — said plainly

Two passes ago the subscription handler gained a **network call** to Stripe. A network call is the single
most likely thing in the whole handler to throw.

**It sits inside `once()`**: `$this->once($event, fn () => $billing->handle($event))` → `handle()` → `sync()`
→ `retrieveSubscription()`.

⚠️ **So a correct fix increased the chance of permanent event loss.** That is the shape this project
catalogues — a fix creating a new failure elsewhere — and it should be recorded as such rather than glossed.

⚠️ **And the two endpoints genuinely differed**, one pass apart: the Connect pass moved its read-back
**outside** `once()` for exactly this reason; the subscription path did not. **The claim/process separation
subsumes that difference** — a throw inside the claim is no longer fatal — so the inconsistency stops
mattering rather than needing a second edit. The billing call was therefore left where it is. **Reported.**

---

## 4 · Concurrency, and the harder case an exception handler cannot reach

**Concurrency:** the re-claim is a single conditional `UPDATE`; a second simultaneous delivery finds the row
`processing` and is answered `in_flight` (202, so Stripe is not stampeded — and if the in-flight delivery
then fails, *that* delivery returns 500 and Stripe retries it).

⚠️⚠️ **A process that DIES mid-handler** — OOM, a killed container, a dropped connection — leaves **no throw
to catch and no completion**. The row would sit `processing` forever and every retry would be refused: the
same permanent loss by another route. **A solution covering only exceptions does not cover it.**

**So a claim expires.** `processing` older than **`STALE_CLAIM_MINUTES = 15`** may be taken over — chosen well
above any realistic inline handler (these complete in far under a second) and well below Stripe's retry
window. Both directions are tested: a stale claim **is** taken over; a claim one minute inside the window is
**not**.

---

## 5 · How anyone would ever know — and the query to run

⚠️ **There is still no reconciliation job, no drift check and no alert.** That absence has now decided **four**
passes in a row. Nothing was built here either.

⚠️ **But the fix genuinely improves observability**, and that is the point worth making: a lost event left
**no trace distinguishable from a duplicate**; a stuck or failed claim is a row you can see, with the error
on it.

```sql
-- Anything that arrived and never completed. Empty is healthy.
SELECT source, event_type, external_event_id, status, error, received_at, updated_at
FROM webhook_events
WHERE status <> 'processed'
ORDER BY received_at DESC;
```

Rows sitting at `failed` are retryable and Stripe is probably already retrying them; rows at `processing`
older than fifteen minutes are dead claims that the next delivery will take over — and if none arrives,
they are exactly the events worth chasing in the Stripe dashboard.

---

## 6 · Which handlers throw, which degrade — and that retries expire

| path | on trouble |
|---|---|
| billing `sync()` | **degrades** — falls back to the event payload (Stripe's own signed statement) when the read-back returns null; **throws** only if the client itself throws |
| Connect `handleAccountUpdated` | **throws** on an ENABLE it cannot verify (deliberate, and *before* the claim); **degrades** on a DISABLE |
| `settlePayment` / `settleRefund` / `confirmTransfer` | no degradation — succeed or throw |

⚠️ **Stripe retries for a limited window** — roughly **three days** of exponential backoff in live mode, then
it stops. **So a fix that only works during the retry window is partial**, and this one is: an event whose
handler fails for three days straight is still lost at the end of it. What changes is that it now leaves a
`failed` row with the error on it, instead of a row indistinguishable from a successful delivery.

⚠️ That is also why the degraded paths matter: a handler that **completes** (billing's payload fallback)
survives an outage, while one that **throws for the whole window** does not.

---

## 7 · What is guarded

`tests/Feature/WebhookEventClaimTest.php` — 6 tests, driven through signed webhooks.

⚠️ **Both halves, because neither alone is enough:** a **failed** delivery is retried and **processed**; a
**succeeded** one is **never reprocessed**. Plus the concurrent refusal, the stale takeover, the fresh claim
left alone, and that both receivers share one implementation.

⚠️ **The tests run against the BILLING endpoint deliberately** — its read-back is inside `once()`, so it is
the only one that can produce a genuine in-claim throw. My first draft used the Connect endpoint and found
**zero** rows: that handler fails *before* claiming, so it cannot exercise this at all. **The test was wrong,
not the code**, and the probe that showed it is why I did not report a phantom.

**Mutations** — each applied, confirmed landed in its own block, run, reverted:

| mutation | |
|---|---|
| ⚠️ **THE WRONG FIX — ANY existing row retryable, including a `processed` one** | **RED (5)**, incl. HALF TWO |
| the original defect restored — existence, not state | **RED (2)** |
| the stale-claim expiry removed | **RED (1)** |
| the `failed` marking removed (claim never released, no trace) | **RED (1)** |

⚠️ The first was **re-run against the final code** after Pint's edits.

---

## 8 · One rule, and why merging was not scope creep

The receivers' own docblock pre-committed to merging on the **first** of *"a THIRD receiver needing this
guard"* or *"ANY change to the rule's shape (a new field, a different key, a TTL, **a status**)"*.

⚠️ **Both had fired.** `ChannexWebhookController` is a third writer of `webhook_events` — that trigger was
**already met before this pass** — and this change is precisely a status change. The rule now lives once, in
`WebhookEventClaim`.

⚠️ **The stale docblock was corrected rather than left.** It still said *"NOT MERGED NOW"* and described *"two
implementations"* in the present tense after they had been merged — a false comment on a money path, which is
the shape this project has now caught five times.

⚠️ **Channex is deliberately NOT merged in.** It **queues** its work, and `ProcessChannexWebhookJob` already
drives this exact state machine — check `processed`, set `processing`, write `failed` on a throw. **The fix
here is that codebase's own existing pattern, applied to the two inline receivers.** Its residuals (a
read-then-write claim rather than an atomic one, and no stale expiry) are lower risk behind a queue with its
own dedupe key. **Reported, not changed.**
