# Reconciliation — making the silence loud

**2026-09-04.** Founder ruling after fourteen passes reported it. It is the answer to *"how would anyone
know?"* in every one of them.

## Why it exists

**Every defect in the billing sequence was silent.** The plan divergence, the stale payouts flag, the lost
events, the confident €0, the inert invoice handlers, the missing ledger debit — **none announced itself**.
Each was found because somebody happened to look, and the absence of a check decided three separate design
arguments.

⚠️ **It reports; it never repairs.** A job that silently corrects drift removes the evidence that drift
happened — and every one of these was found *because a trace survived*.

## #1 · Seven checks, each traced to the pass that found its defect

| check | needs Stripe | from | what it would have caught |
|---|---|---|---|
| Subscription plan vs the Stripe Price | **yes** | `BILLING-PURCHASED-PLAN` | an org bought Essential, Stripe billed €49, `plan_id` still said Pro |
| Subscription status vs Stripe | **yes** | `BILLING-WEBHOOK-ORDERING` · `-INVOICE-ORDERING` · `-EVENT-RESOLUTION` | a cancelled account reactivated by a late invoice; a failed card that never marked `past_due` |
| Owner payout flag vs Connect | **yes** | `CONNECT-ACCOUNT-ORDERING` | a late `account.updated` re-enabling payouts Stripe had disabled |
| Webhook deliveries not `processed` | no | `WEBHOOK-EVENT-LOSS` · `BILLING-EVENT-RESOLUTION` | an event lost inside `once()`; one that could not be placed at all |
| Settled payments with no audit row | no | `POST-COMMIT-TRAIL` | the trail holed by a post-commit throw the retry could not recover |
| Refunds with no owner-ledger adjustment | no | `POST-COMMIT-TRAIL` | ⚠️ the guest refunded and **the owner never debited** — the accounting half |
| Plans colliding on `(tier, interval)` | no | `PLAN-CATALOGUE-RESIDUALS` | the collider taking the real plan's card on `/pricing` |

**Four are purely local** — no network, so they keep working during a Stripe outage, and a test asserts they
report nothing "unchecked" when Stripe is unreachable. That is the cheap half and it is the half that runs.

## #2 · What a repair would mean for each, and why every one is refused

| check | a "repair" would be | refused because |
|---|---|---|
| plan vs Price | write `plan_id` | what a customer is on is decided by what they bought, not a nightly job's opinion |
| status vs Stripe | flip `status` | that is the access gate — silently restoring or revoking a whole back office at 04:00 |
| payout flag | flip the flag | real money leaving; the Connect pass threw rather than guess for the same reason |
| unprocessed webhooks | re-claim the row | ⚠️ **the arguable one** — re-running a webhook re-runs whatever it does, and the claim already admits Stripe's own retry. Deciding for the founder that a stuck delivery should be replayed is the silent correction this exists to prevent |
| missing audit row | write it now | an audit row asserts *who* did what and *when*; a reconciliation knows none of those. A fabricated actor on an evidence table is worse than an absence |
| missing ledger adjustment | post the debit | real money on an owner's statement, weighted across owners — refused outright |
| plan collision | rename a plan | a code is an identity a founder chose |

**Nothing was left out for being unactionable** — every finding has an investigation behind it.
**No alerting, no mail, no dashboard** was built.

## #3 · Three outcomes, never two

Each check reports **AGREES · DISAGREES · COULD NOT CHECK**, with the reason. `unchecked` is its own
column and its own summary clause:

```
Examined 21 object(s): 21 agreed, 0 DISAGREED, 0 UNCHECKED.
```

⚠️ **"0 discrepancies across 40" and "0 across 3, 37 unchecked" cannot render the same** — `isClear()`
requires *both* lists empty, and a run that examined nothing prints
*"Nothing was examined. That is not 'no drift' — it is an empty world."*

**The fake fails closed and so does this:** `retrieveSubscription()` returns null for an object Stripe does
not know, and null becomes UNCHECKED, never agreement. **Partial runs keep what they got** — each object is
compared independently and a failure on one records *that object* as unchecked and continues; the per-check
`SAMPLE` cap of 200 bounds both runtime and Stripe's rate limits.

## #4 · It cannot become silent itself

The scheduler's own failure is one log line with nothing watching. So:

- a run row is written **before** the checks, so a run that dies midway leaves a `running` row with a start
  time — *"it broke"* and *"it was never invoked"* are different states;
- a midway throw records `failed` **with the reason** and the command exits non-zero;
- the header prints the **last successful run and its age**, and warns `STALE` past 36h;
- with no completed run ever, it says so explicitly.

**No monitoring was built** — a stale-run timestamp costs nothing and is the honest half.

## #5 · Zero drift today proves nothing

Nothing has ever been through Stripe. A real run examines 21 objects and finds nothing:

```
Webhook deliveries that never reached `processed`      no    0   0   0   0
Settled payments and refunds with no audit row         no   16  16   0   0
Refunds on owner stays with no ledger adjustment       no    1   1   0   0
Plans resolving to the same (tier, interval)           no    4   4   0   0
Subscription plan / status / owner payouts vs Stripe  yes    0   0   0   0
```

**So every check is proven against drift that was BUILT**: a subscription billed on another plan, a status
Stripe has cancelled, an owner flagged payable that Stripe disables, a `failed` webhook row, a settled
payment with its audit row deleted, a succeeded refund with no adjustment, and a plan written *directly past
the validator* to collide. Each has an agreement half asserting the check stays quiet.

## #6 · Command and table, argued

**Both.** The command is what a founder will actually run; the table survives the terminal closing and makes
history and staleness visible — which is the entire point, since a console-only report would be as silent as
the scheduler. There is deliberately **no route**.

```sql
select started_at, status, examined, agreed, disagreed, unchecked from reconciliation_runs order by started_at desc;
```

**Retention follows the existing convention** rather than inventing one: `reconciliation_runs` is declared in
`RetentionPolicy::WINDOWS` at 90 days, so the existing `PruneRetainedRecords` prunes it with everything else.
A nightly report is **not** evidence — unlike `audit_events`/`domain_events`, which are deliberately never
pruned.

## #7 · Read-only, console-only

Asserted by fingerprinting every subscription, owner, plan, payment, webhook row, ledger row and the audit
count around a real run **that had drift to walk over**. Tenant scopes are dropped via `runAsSuperAdmin` —
a reconciliation spans every tenant and has no request context, so that *is* the tenant resolution, the
precedent `BillingEventOrganization` set — while **soft-delete scopes are kept**, so a deleted row cannot
resurrect into a finding. No route matches `reconcil`, asserted.

## #8 · Mutations — and two of them found gaps in my own tests

| mutation | landed | result |
|---|---|---|
| **wrong fix** — unreachable Stripe reports *agrees* | ✅ | **RED** |
| **wrong fix** — a check REPAIRS instead of reporting | ✅ | ⚠️ **GREEN twice, then RED** |
| collapse UNCHECKED into CLEAR | ✅ | **RED** |
| stop persisting the run row | ✅ | **RED** |
| drop the serialised `examined` count | ✅ | ⚠️ **GREEN, then RED** |

⚠️ **The repair mutation was green twice, and both reasons were mine.** First the read-only probe registered
the *same* price, so the plan agreed and the repair branch never executed — *"it did not write"* only proved
*"it did not look"*. Then, with real drift added, the probe called the reconciler **before** photographing
the world, so the repair had already finished by the time the baseline was taken. Fixed by taking the
fingerprint first and asserting the run found more than one disagreement.

⚠️ **The examined-count mutation was green** because the assertion read the object property while the
mutation zeroed only `toArray()`. The floor is now asserted where it is **serialised and persisted** — which
is what a founder actually sees.

M1 re-run against the final code; all files byte-identical after restore.

## ⚠️ What I cannot prove

That these checks would have *caught* the historical defects at the moment they happened — they are derived
from them, and each is proven against a reconstruction, but nothing here has run against live Stripe
(`StripeSafetyGuard` refuses a live key outside production). And the Connect fake never throws — an
unregistered account reads `payouts_enabled=false` rather than raising — so the *unchecked* path for that
one check is exercised only through a thrown client, not through the fake's own absence.
