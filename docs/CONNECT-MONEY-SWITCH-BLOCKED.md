# The money switch — STOPPED before any code, and why (2026-08-26)

⚠️⚠️ **NOTHING WAS BUILT. NO CHARGE CALL, NO WEBHOOK ROUTING, NO REFUND PATH, NO SCHEMA.**
`git diff -- app/ database/ routes/` is empty for this pass.

⚠️ Rule #5 said a reachable pending refund stops the pass, and rule #1 said ship all three or none. A
pending refund is not merely reachable — **it re-opens invariant #27, the over-refund bound this project
already closed once.** Details below, established from the code and from Stripe's own documentation.

---

## ⚠️⚠️ THE BLOCKER: a queued refund re-opens the over-refund bound (#27)

**Stripe's documented behaviour for a direct charge**, verbatim: *"Stripe debits the refund amount from the
connected account's balance directly when you create a refund. If the connected account's balance is
insufficient, we set the refund status to `pending`. When the connected account's balance has enough
funds, Stripe automatically processes pending refunds in the order they were created and updates their
status to `successful`."*

⚠️ **This is not an edge case.** Any operator on automatic daily payouts runs a near-zero balance by
design. A refund issued the day after a payout queues.

**What our code does with that** — read, not assumed:

1. `StripeGateway::refund()` (`:86`) returns `new GatewayResult($refund->status === 'succeeded', …)`.
   ⚠️ A `pending` refund is therefore **not successful**.
2. `FolioService::refundPayment()` (`:466`) posts the row with
   `'status' => $result->successful ? 'succeeded' : 'failed'`. ⚠️ **There is no `pending`. The folio
   records the refund as FAILED.**
3. ⚠️⚠️ **But Stripe has ACCEPTED it and will complete it automatically.** The desk is told a refund
   failed that is, in fact, on its way.
4. `refundedTotalMinor()` (`:506-514`) sums **only** `status = 'succeeded'`. ⚠️ The queued refund is not
   counted, so `remainingRefundableMinor()` still shows the full amount available.
5. The desk re-issues.

⚠️ **THE BRIEF EXPECTED THE OPPOSITE FAILURE.** It warned about posting a queued refund as *settled*.
The code would post it as *failed* — which is worse, because it actively invites a second refund of money
that is already moving.

### ⚠️ The idempotency key catches SOME of this, and that is why it needed checking

`FolioService:450-453` derives the gateway key as
`folio_refund:{payment_id}:{alreadyRefunded}:{amount}`.

- **Re-issuing the SAME amount** → `alreadyRefunded` is still 0 and the amount matches → **the same key** →
  Stripe returns the original refund. ✅ **No double refund.**
- ⚠️⚠️ **Re-issuing a DIFFERENT amount is NOT caught.** Two €250 refunds after a "failed" €500 produce two
  distinct keys; both are permitted because `alreadyRefunded` is still 0; Stripe queues both **on top of
  the original pending €500**. ⚠️ **€1,000 refunded against a €500 payment, and invariant #27 does not
  stop it, because #27 bounds the sum of SUCCEEDED refunds.**

⚠️ **#27 was found by reading, not by a failing test, and closed once. This charge-type switch would
re-open it through a status the folio does not model.** That is the whole reason for stopping.

### What closing it needs (NOT built here)

A `pending` refund state the folio understands, and it touches:
- `Payment` status handling and every surface that renders a refund;
- ⚠️ `refundedTotalMinor()` — a pending refund **must** count toward the #27 bound, or the bound is
  bypassable by definition;
- the Connect webhook, to settle `pending → succeeded` (or reverse it on failure) when Stripe completes it;
- the owner-ledger adjustment and the borne-by attribution, which must not post until the money moves;
- the seven certified refund suites (`RefundGuardTest`, `RefundBorneByTest`, `ConcurrentRefundTest`,
  `RefundOnCancelTest`, `OperatorRefundGate2Test`, `OperatorRefundScreenTest`,
  `OwnerAbsorbedRefundNoteTest`).

⚠️ **That is its own pass, and it must land BEFORE the charge switches** — not after, because the moment
charges are direct, every refund can queue.

---

## What was established while orienting (reusable — do not re-derive)

**#7 · How payments are created today.** The seeder (`DemoSeeder:1750`) and the tests
(`owner_helpers:76`, `GuestPaymentsTest:44`) call `FolioService::recordPayment()`. ⚠️ **That is NOT a
Stripe bypass** — `recordPayment()` does call `$this->gateway->charge()`. It works because
`PAYMENTS_GATEWAY` defaults to `manual` (`config/services.php:74`, `.env.example:108`, `phpunit.xml:52`),
and the manual gateway returns success without touching Stripe. ⚠️ **So the seeder is unaffected by a
charge-type switch only while the gateway stays `manual`** — and nothing would need fabricating in seed
data. Nothing was fabricated, because nothing was built.

**#3 · Is the platform receiver's idempotency reusable? YES — it is already there.**
`StripeConnectWebhookController::once()` (`:82`) exists and is backed by the same `webhook_events` unique
index as `StripeWebhookController::once()` (`:83`). Routing payment events into the Connect receiver would
reuse it; no third implementation is needed.
⚠️ **BUT A PRE-EXISTING §N.8f DUPLICATION IS RECORDED HERE:** the two `once()` bodies are byte-identical
apart from line formatting — one rule, two copies. It has not drifted yet. It is exactly the shape that
does. **Reported, not fixed** (out of scope for this pass).

**#8 · What the guest's card statement will read.** With direct charges the **operator is the merchant of
record**. Stripe: *"For direct charges, Checkout uses the brand settings of the connected account."* No
`statement_descriptor` is set anywhere in this codebase, so the descriptor defaults to the connected
account's own — derived from the operator's business name unless they set one in their Stripe dashboard.
⚠️ **A guest who booked "Seaside Apartments" and sees an unfamiliar legal entity on their statement
disputes the charge.** Prompting the operator to set a recognisable descriptor belongs to the onboarding
UI, which does not exist yet.

**#2 · No fallback was built** — there was nothing to build, since nothing was built. The design position
stands: an operator without `charges_enabled` cannot take a payment, and that makes onboarding a real gate.

**#6 · Nothing moved.** `owner_ledger` is unchanged at 29 rows / 730640. No certified test was amended.
