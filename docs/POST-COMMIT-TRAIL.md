# The settlement commits, the recorder throws, the trail is holed

**2026-09-04.** Named by the event-loss pass as a residual and correctly left; opened here.

## ⚠️⚠️ TOP FINDING — something ledger-shaped runs after the commit, so this was an ACCOUNTING defect, not only a forensics gap

Rule #1 asked whether anything ledger-shaped runs post-commit. **It does.**
`AdjustOwnerLedgerOnRefund` posts the owner's debit for a guest refund, **synchronously**, on the
`DomainEventRecorded` bus — and it was registered **behind** two best-effort listeners on that same bus.

Measured end to end on an owner-attributed reservation, with `DispatchNotificationsForDomainEvent` throwing:

| | before this pass |
|---|---|
| refund status | **`succeeded`** — the guest's money went back |
| `domain_events` | `payment.refunded` row **present** (written before its listeners run) |
| `audit_events` | **missing** |
| `owner_ledger` adjustment | ⚠️ **NEVER POSTED** |
| `webhook_events` | `failed`, with the reason |

**The guest was refunded and the owner was never debited** — so on the next statement close the owner keeps
revenue that was returned, and the operator absorbs it. That is an accounting defect and it outranks the
audit row this pass was sent for.

## #1 · Every post-commit recorder, per handler

| handler | after its commit | what its loss costs |
|---|---|---|
| `FolioService::settlePayment` | `domainEvents->record('payment.settled')` → listener chain | notifications; no money listener subscribes to this type |
| | `audit->record('payment.settled')` | forensics: who settled what, and when |
| `FolioService::settleRefund` | `domainEvents->record('payment.refunded')` → chain → ⚠️ **`AdjustOwnerLedgerOnRefund`** | ⚠️ **the owner's ledger debit — real money on a statement** |
| | `audit->record('payment.refund_settled')` | forensics |
| `OwnerPayoutService::confirmTransfer` | recorders only — no ledger write behind them | forensics |

**Who is harmed and when.** A missing audit row harms nobody today and everybody at a dispute: the payment
still *shows* as settled, so the product looks right, and the gap surfaces only when someone asks *who
settled this and when* — a chargeback, an owner query, an audit. `audit_events` and `domain_events` are
**deliberately never pruned because they are evidence**, so a hole in them is **permanent by design**: it
cannot age out and it cannot be reconstructed later.

## #2 · The guard was not weakened, and the settlement still commits

The status guard stays exactly as it is. Re-settling to recover a record would trade a missing row for a
**double write on a money path**, which is a worse trade and is forbidden here. Instead **the recording
became retryable independently of the settlement**.

**Rejected — moving the recorders inside the transaction.** It is the obvious fix and it has a real cost: a
failing recorder would then **roll back the settlement**, and a payment the gateway TOOK which this product
refused to record at all is worse than one recorded without its audit row. **Money first, trail second.**

**Rejected — re-recording the domain event on resume.** It is written *before* its listeners run, so after a
post-commit failure it already exists; re-recording would create a second `domain_events` row and **re-fire
the guest's refund email**. The resume writes the audit row only — inert evidence with no listeners.

**Rejected — queueing the recording.** New machinery, and the queue is another thing that can be down.

### What shipped

1. **Ordering.** The two money listeners (`AdjustOwnerLedgerOnRefund`, `ReattributeOwnerOnUnitMove`) run
   **before** the notification and email listeners. Nothing best-effort may sit between a committed
   settlement and the ledger row that settlement implies.

   ⚠️⚠️ **THIS ORDERING WAS HALF-EFFECTIVE FROM THE DAY IT SHIPPED, AND FIX 19 (2026-09-14) IS WHAT MADE IT
   WHOLE.** It was written as explicit `Event::listen` calls in `AppServiceProvider` — but Laravel 13 ships
   `EventServiceProvider::$shouldDiscoverEvents = true`, so every class in `app/Listeners` was ALSO registered
   automatically as `Class@handle`. 🔬 MEASURED: **14 registrations on `DomainEventRecorded` where 7 were
   intended**, in two different orders — and in the discovered set `DispatchNotificationsForDomainEvent`
   preceded `AdjustOwnerLedgerOnRefund`, the exact arrangement this fix exists to prevent. Every listener ran
   twice, and what kept the money safe was each listener's own idempotence guard, not the order.

   ⚠️ **Discovery is now OFF** (`bootstrap/app.php`, `->withEvents(discover: false)`), after measuring that
   every discovered listener also had an explicit registration — **DISCOVERED-ONLY was ZERO**, so nothing lost
   its listener. 🔬 `DomainEventRecorded` now holds exactly 7 registrations in the order written above, and
   `RefundOwnerLedgerTest` asserts BOTH the count and the resolved order — a count alone would pass a
   deduplication that reordered them.

   ⚠️ **It also cost an instrument.** FIX 18's ordering mutation could not discriminate: deleting the explicit
   money registration changed nothing, because the discovered copy still ran, and that pass nearly concluded
   the ordering was irrelevant. 🔬 After this fix the same mutation reddens.
2. **Isolation.** `DispatchNotificationsForDomainEvent` now catches and logs, exactly as its sibling
   `SendGuestTransactionalEmails` already did — one convention, not two. A notification failure can no
   longer abort the chain at all.
3. **Resume.** `settlePayment()`/`settleRefund()` write the audit row on a retry when the settlement is
   already terminal and the row never landed. Idempotent **by query** — `audit_events` has no unique index,
   being an append-only evidence table — so a normal settlement still records exactly once.

## #3 · Inside or after, per recorder

| recorder | where | why |
|---|---|---|
| the payment status write + `recompute()` | **inside** | they must be atomic with each other |
| `domainEvents->record(...)` | **after**, correctly | its listeners send mail and dispatch notifications; inside a transaction those would fire on writes that later roll back — the orphan-send defect this project has already recorded |
| `audit->record(...)` | **after**, and it could safely be inside | but moving it there would let an audit failure roll back money, which is the trade rejected above |
| `AdjustOwnerLedgerOnRefund` | **after**, via the bus | ⚠️ it is a money write reached through a best-effort mechanism. Its **order** was the defect, not its position — fixed by (1) |

A single rule applied to all of them would have been wrong; the answer genuinely differs per recorder.

## #4 · Reachable, but nobody is affected

**How it throws, ranked:** a listener failing (most likely — `DispatchNotificationsForDomainEvent` had no
try/catch and does tenant-scoped reads and inserts) · a database error on the audit insert · a null on an
unexpected payload shape. A queue being down is *not* a path: every listener here is synchronous.

**Has it ever happened? No.** Queried: **0** settled payments or refunds with no settlement audit row, **0**
`webhook_events` rows in `failed`, **0** owner-ledger adjustments outstanding. `webhook_events` is empty
because no Stripe traffic has ever reached this deployment.

**What would arm it:** live Stripe webhook traffic (`PAYMENTS_GATEWAY=stripe` plus the live-key flip) and any
throw in the post-commit chain. It was **latent** — real in code, never triggered.

## #5 · The state a delivery reaches — driven, not reasoned

First delivery: HTTP **500**, `webhook_events` = **`failed`** with the reason. So Stripe's retry **is**
admitted and the row **is** visible.

⚠️ **But the visibility did not survive the retry, and that is the sharper finding.** On redelivery the claim
re-claims the `failed` row, `settleRefund` returned early on the status guard, and the row was marked
**`processed`** — with the audit row and the ledger adjustment still missing. **The retry erased the only
trace.** The loss was diagnosable for minutes, then looked like a clean success forever. Second and third
retries change nothing further: the row is `processed` and re-delivery is a no-op.

After this pass the retry writes the audit row, so `processed` is honest rather than a lie.

## #6 · The query the founder can run

```sql
-- a settled payment or refund whose settlement was never recorded
select p.id, p.type, p.status, p.processed_at
from payments p
where p.status in ('succeeded','failed')
  and not exists (
    select 1 from audit_events a
    where a.subject_id = p.id
      and a.action in ('payment.settled','payment.refund_settled','payment.recorded','payment.refunded')
  );

-- and the deliveries that failed while unrepaired
select event_type, error, received_at from webhook_events where status = 'failed' order by id desc;
```

Returns **0** rows today. No second silent path was added: everything that refuses or degrades logs.

## #8 · Mutations — every one landed, every one red

| mutation | landed | result |
|---|---|---|
| **the wrong fix** — relax the status guard so the retry re-settles | ✅ | **RED** |
| remove the resume entirely | ✅ | **RED** |
| put the money listener back behind the best-effort ones | ✅ | **RED** |
| remove the notification listener's `catch (Throwable)` | ✅ | **RED** |

All three touched files byte-identical after restore.

## ⚠️ Where this brief was wrong

It framed the loss as *"an audit row … a compliance and forensics gap"* and asked, conditionally, whether
anything ledger-shaped ran post-commit. **It did**, so the pass's own top-line was the smaller half. And its
hope that *"the loss may already be diagnosable"* held only until the first retry, which **erased** the
evidence by marking the row `processed`.

## ⚠️ What I cannot prove, and what remains

- A throw from `AdjustOwnerLedgerOnRefund` **itself** still loses its ledger row on that delivery. It is now
  first in the chain, so nothing else can prevent it, and `attributeRefund` is idempotent
  (`firstOrCreate` on `refund:{key}:{owner}`) so a future recovery could re-run it safely — but this pass
  does not re-run it, because doing so from `FolioService` would couple the folio layer to owner attribution.
  **Reported, not fixed.**
- That real Stripe traffic behaves as these probes simulate. Nothing here ran against live Stripe.
