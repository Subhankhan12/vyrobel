# Stripe Connect — the target design (2026-08-26)

⚠️⚠️ **THIS DOCUMENT MOVES NO MONEY PATH.** No payment call, charge type, schema or onboarding flow was
changed. It establishes where funds sit in each arrangement so a qualified person can answer the legal
question quickly, and sizes what each would cost.

⚠️⚠️ **NO LEGAL CONCLUSION IS ASSERTED ANYWHERE IN THIS DOCUMENT.** Whether a given settlement pattern
constitutes holding client money — and what that requires — is a regulated question that varies by
jurisdiction and by the platform's own registrations. §A5 states it as a question, not an answer.

---

## §0 — The current path, guest card to owner bank (established, not assumed)

1. **Guest pays.** `BookingEngineController:547` / `GuestFolioPaymentController:52` →
   `StripeClient::createPaymentCheckout()`. ⚠️ The session is created on the **PLATFORM** account: there is
   no `Stripe-Account` header, no `on_behalf_of`, no `transfer_data`, no `application_fee_amount`.
2. **Funds settle into the Vyrobel platform Stripe balance.**
3. **Webhook** (`StripeWebhookController:109`, platform endpoint) → `FolioService::settlePayment()` writes a
   payment row against the folio.
4. **Attribution.** `OwnerAttributionService` writes `owner_ledger` lines — room revenue credit, commission
   debit, `ota_cost` debit.
5. **Collection gate.** `OwnerCollection::reservationIsCollected()` → `FolioTotals::for($folio)
   ['balance_minor'] <= 0`. ⚠️ **Collection is evidenced by a FOLIO ROW, not by a Stripe balance.** Keep
   this in view — §A3 turns on it.
6. **Statement** claims the collected lines and locks them (`statement_id` stamped).
7. **Payout.** `StripeConnectPayoutRail:53` → `createTransfer(amount, currency,
   owner.stripe_connect_account_id)` — a **transfer out of the platform balance** into the owner's Connect
   account, then Stripe pays out to their bank.

⚠️ **`organizations` carries NO Stripe account of any kind.** The only Stripe ids on the operator side are
`subscriptions.stripe_customer_id` / `stripe_subscription_id` — the operator's own SUBSCRIPTION to Vyrobel,
which is unrelated to guest money. **Only OWNERS hold `stripe_connect_account_id`.**

---

## §A1 — The four shapes, and where funds sit

⚠️ Verified against Stripe's own documentation (`docs.stripe.com/connect/charges`,
`/connect/direct-charges`). Stripe's categorisation is the load-bearing fact: **"Direct charges: payments
made directly to a connected account"** versus **"Indirect charges: Payments made to your platform"**, which
is the category containing destination charges AND separate charges and transfers.

| shape | where funds land FIRST | how long they sit there | settlement merchant | disputes debited from | Stripe fees debited from |
|---|---|---|---|---|---|
| **Direct charges** (`Stripe-Account` header) | ⚠️ **the connected account. The platform balance is never touched.** | n/a — platform never holds them | the connected account | the connected account | platform or connected account, configurable |
| **Destination charges** (`transfer_data[destination]`) | ⚠️ **the PLATFORM balance** | Stripe's word is *"immediately transfers"* — sub-second in the normal path, but it is a real credit to the platform balance | the platform | ⚠️ **the platform** | the platform |
| **Destination + `on_behalf_of`** | ⚠️ **still the PLATFORM balance** | as above | the connected account is *"the business of record"* — its country, fee structure, statement descriptor and address | ⚠️ **the platform** — Stripe says *"with or without `on_behalf_of`, your platform balance is automatically debited"* | the platform (at the connected account's country rates) |
| **Separate charges and transfers** (today's shape) | ⚠️ **the PLATFORM balance** | ⚠️ **indefinitely — until the platform chooses to transfer.** In Vyrobel that is the statement/payout cycle: potentially weeks | the platform | the platform | the platform |

⚠️⚠️ **TWO OF THE FOUR DO NOT CHANGE WHERE THE MONEY LANDS.** Destination charges — with or without
`on_behalf_of` — settle into the platform balance exactly as today's separate charges do. What
`on_behalf_of` changes is **whose business is of record** for the payment, not **whose balance receives
it**. The whole rewire could be completed and the platform would still receive guest funds.

⚠️ **WHAT THE DOCUMENTATION DOES NOT SAY IN ONE SENTENCE.** There is no single sentence reading
"`on_behalf_of` does not change which balance funds settle into". The conclusion rests on three statements
that each appear verbatim: destination charges are classified under *"Indirect charges: Payments made to
your platform"*; *"You create a charge on your platform, so the payment appears in your platform's
balance"*; and the dispute rule quoted above naming `on_behalf_of` explicitly. ⚠️ **A qualified person
should confirm this against their own reading rather than take it from here.**

⚠️ **Also not confirmed from the docs:** the precise dwell time of the platform credit in a destination
charge. Stripe says the transfer is immediate; it does not state whether the platform balance is credited
and debited as two ledger events or netted. **That distinction may matter to the legal question and it is
NOT settled here.**

---

## §A2 — What each shape costs

**MERCHANT OF RECORD / the guest's card statement.** Direct: the operator's own branding — Stripe says
*"For direct charges, Checkout uses the brand settings of the connected account"*. Destination without
`on_behalf_of`: Vyrobel. Destination with `on_behalf_of`: the connected account's descriptor and address.
Separate (today): Vyrobel. ⚠️ **Today the guest's statement says Vyrobel, not the hotel they booked.**

⚠️⚠️ **ONBOARDING — SIZED, NOT WAVED AT.** Direct charges require the connected account to have the
`card_payments` capability **active**, which means the operator completes Stripe's identity and business
verification **before taking a single booking**. Concretely, for a new tenant that means: today
`/signup` → `TenantProvisioningService` → a working trial in one step, with a storefront that can sell
immediately. Under direct charges the storefront **cannot take a card** until Stripe has verified the
business — typically business identifiers, a representative's identity, and bank details. ⚠️ **That is a
hard gate between a self-signup and a first paid booking, and it lands squarely on the funnel Phase 7a was
built to make frictionless.** The trial itself is unaffected; only card acceptance is gated.

⚠️ **REFUNDS — AND THE BRIEF IS WRONG ON THIS POINT.** The brief states that with direct charges a refund
from an empty operator balance **FAILS**. Stripe's documentation says otherwise, verbatim: *"If the
connected account's balance is insufficient, we set the refund status to `pending`. When the connected
account's balance has enough funds, Stripe automatically processes pending refunds in the order they were
created and updates their status to `successful`."* ⚠️ **So it does not fail — it QUEUES.** That is a
materially different problem, and a better one: no lost refund, but an **unbounded delay** the guest
experiences and Vyrobel cannot shorten.
  · ⚠️ **What that does to our refund path:** `FolioService::refundPayment()` is the single certified path
    and enforces invariant #27 (cumulative refunds may equal but never exceed the original), with the guard
    running **before** the gateway call. That ordering still holds. What changes is that
    `StripeGateway::refund()` would need the `Stripe-Account` header, and a `pending` refund is a state the
    folio does not currently model — today a refund is recorded as settled. ⚠️ **A refund that is pending
    at Stripe but posted on the folio would misstate the guest's balance**, which is exactly the shape
    invariant #17 exists to prevent.
  · **Refund borne-by** (owner vs operator attribution) is unaffected in principle — it is a ledger
    attribution decision, not a rail decision — but the pending state above would need modelling first.

**DISPUTES.** Direct: debited from the operator's balance, and the operator can respond via Connect
embedded components. Destination/separate (incl. `on_behalf_of`): **debited from the Vyrobel platform
balance**, recoverable only by reversing the transfer. ⚠️ Today, a chargeback on a guest booking hits
Vyrobel's balance and Vyrobel must respond, having none of the evidence.

⚠️ **THE APPLICATION FEE — THE BRIEF IS RIGHT, AND IT MATTERS.** Vyrobel charges a **flat subscription**,
not a commission: all four seeded plans are `pricing_type='flat'` with `unit_rate_minor` NULL, and the
operator's subscription is billed through an entirely separate Stripe Customer. **No per-booking platform
fee is needed at all.** `application_fee_amount` is optional on a direct charge — omit it and the whole
application-fee apparatus (fee refunds, `refund_application_fee`, proportional fee reversal on partial
refunds, `ApplicationFee` reporting) never has to be built. ⚠️ **This removes the single largest source of
complexity in a direct-charge integration, and it is a genuine structural advantage of the flat-fee model.**

⚠️ **A COST THE BRIEF DID NOT NAME — PLATFORM VISIBILITY.** Stripe: *"Transaction objects such as
PaymentIntents and Charges exist on the connected account, not on the platform. To access direct charge
data, you must query the Stripe API using the connected account ID in the `Stripe-Account` header."*
⚠️ **Our guest-payment webhook is a PLATFORM endpoint** (`StripeWebhookController` →
`constructWebhookEvent`, platform secret). Under direct charges those events arrive on the **Connect**
endpoint instead (`StripeConnectWebhookController`, a **different signing secret**), and every reconciliation
query must be account-scoped. ⚠️ **This is not a parameter change; it is a different event plane.**

---

## §A3 — ⚠️⚠️ What each does to the owner-payout system (the hard part)

**The dependency, stated plainly:** `StripeConnectPayoutRail` pays an owner by `createTransfer()` **out of
the platform balance**. A transfer needs a source. Today the source is the guest money that settled with
the platform.

| shape | is the platform balance funded by guest money? | can Vyrobel still PAY owners? |
|---|---|---|
| Direct charges | ⚠️ **No — never touched** | ⚠️ **No.** There is nothing to transfer from. |
| Destination (± `on_behalf_of`) | Yes, then immediately transferred to the **operator** | ⚠️ Only if the operator's share is transferred back, or the owner split is carved out at charge time |
| Separate charges and transfers (today) | Yes, held until payout | ✅ Yes — this is why it works today |

⚠️⚠️ **UNDER DIRECT CHARGES THE OWNER PAYOUT STOPS BEING A MOVEMENT AND BECOMES AN INSTRUCTION.** Vyrobel
would compute what the operator owes each owner and could no longer move it. That is not a refactor of
`OwnerPayoutService` — it is a **different product**: the owner portal becomes a statement-and-reconciliation
system, and the operator settles by their own bank transfer. ⚠️ **Everything about the accounting — the
attribution engine, the statements, the ledger, the tax and withholding engines, the balance clearing model
— survives intact. Only the RAIL dies.** That distinction is the whole decision, and it is a product call,
not a technical one.

⚠️⚠️ **THE COLLECTION GATE SURVIVES, AND THIS IS THE ONE PIECE OF GOOD NEWS.** The brief asks what
evidences collection if Vyrobel never sees the money. **The gate already does not look at Stripe.**
`OwnerCollection::reservationIsCollected()` reads `FolioTotals::for($folio)['balance_minor'] <= 0` — a folio
row written from a webhook. Under direct charges that webhook simply arrives on the Connect plane instead
of the platform plane. ⚠️ **The evidence of collection is unchanged: a settled payment recorded against the
folio.** The gate was built against the folio rather than the balance, and that decision — made for
unrelated reasons — is what makes it portable.

⚠️ **OWNERS' EXISTING `stripe_connect_account_id`:**
  · Direct charges: ⚠️ **DEAD.** With no platform balance there is no transfer to make. The onboarding
    flow, `OwnerConnectController`, the Connect webhook for owner accounts, `PayoutRecovery` and the
    payout-engine rail all become inert. ⚠️ Unless the OPERATOR's connected account transfers to the OWNER's
    connected account — which is a connected-account-to-connected-account transfer, a materially more
    complex arrangement not designed here.
  · Destination / separate: unchanged and still meaningful.

---

## §A4 — What each shape would invalidate, sized honestly

⚠️ **Counted, not estimated:** 13 application files reference `StripeClientInterface`; **105 test files**
match owner/payout/statement/folio/refund/stripe/payment.

**Destination charges (± `on_behalf_of`) — SMALL, and it does not solve the stated problem.**
  · One call site changes (`createPaymentCheckout` gains `transfer_data`/`on_behalf_of`), plus an operator
    Connect account (a new column, onboarding flow, and verification gate — the §A2 onboarding cost applies
    here too, because a destination requires a connected account).
  · The owner payout, the ledger, the statements and the collection gate are **untouched**.
  · ⚠️ **But funds still land in the platform balance, so if the objective is to stop receiving guest money,
    this buys nothing.** It buys a correct merchant-of-record and moves dispute liability only if combined
    with… no: disputes stay with the platform either way.

**Direct charges — LARGE, and structural.**
  · Payment creation, refunds, and every Stripe read become account-scoped (`Stripe-Account`).
  · ⚠️ The guest-payment webhook moves from the platform plane to the **Connect** plane — a different
    endpoint, a different signing secret, and every `webhook_events` idempotency assumption re-examined.
  · An operator Connect account: new column, onboarding flow, verification gating on the storefront, and a
    **hard block on selling before verification completes**.
  · A `pending` refund state the folio does not model.
  · ⚠️ **`StripeConnectPayoutRail` and the owner-payout rail are retired**, and with them the payout engine's
    reason for existing as a *mover*. `PayoutRunService`, `PayoutSchedule`, the held/processing/paid
    lifecycle, `PayoutRecovery`, the retry path and the Connect webhook for owners all become either inert
    or reinterpreted as record-keeping.
  · **What SURVIVES untouched:** `OwnerAttributionService`, `owner_ledger`, `OwnerStatementService`, the
    collection gate, `OwnerStatementBreakdown`, the tax/VAT/withholding engines, invariant #17
    reconciliation, refund borne-by, company accounts, the checkout block, the mid-stay split and the
    unit-move re-attribution. ⚠️ **None of those touch the rail.**
  · ⚠️ **Honest size: this is a multi-pass programme, not a pass.** The payment/refund/webhook rewire is
    perhaps a third of it; the operator onboarding gate and the payout-system reinterpretation are the rest,
    and the second of those needs a product decision before any code.

---

## §A5 — Recommendation, with the three kinds of question separated

**TECHNICAL FACTS** (established above): only direct charges keep guest money out of the platform balance;
destination charges do not, with or without `on_behalf_of`; the flat subscription removes the need for any
application fee; the collection gate is already rail-independent; the owner-payout **rail** cannot survive
direct charges while the whole accounting stack can.

**PRODUCT DECISIONS** (the founder's, and they come first):
  1. ⚠️ **Is "Vyrobel pays your owners" a feature we are selling, or a convenience?** If it is the product,
     direct charges end it. If the owner portal's value is the statement, the transparency and the
     reconciliation, then the rail is a convenience and it can go.
  2. ⚠️ **Can a new operator be asked to complete Stripe verification before their first booking?** That is
     the real cost of direct charges and it is paid by every customer at the worst moment.

**THE LEGAL QUESTION — written for one sitting.** ⚠️ Not answered here.

> Vyrobel is a hotel-management SaaS billing a flat monthly subscription, taking no commission on bookings.
> Today a guest's card payment for a stay is charged on **Vyrobel's own Stripe account** and settles into
> Vyrobel's Stripe balance. Vyrobel later transfers a share to the property owner's Stripe Connect account
> on the operator's behalf, on a monthly statement cycle — so guest funds can sit in Vyrobel's balance for
> **days to weeks**. Vyrobel is not the accommodation provider and takes no cut of the booking.
>
> 1. In [jurisdiction], does receiving and holding guest booking funds in this way constitute holding client
>    money / operating as a payment institution requiring authorisation or safeguarding?
> 2. Would the answer change under **destination charges**, where funds are credited to Vyrobel's Stripe
>    balance and transferred to the operator's connected account **immediately** (sub-second) rather than on
>    a monthly cycle — i.e. does dwell time change the analysis, and is there a threshold?
> 3. Would it change again with `on_behalf_of`, where the operator is the **business of record** for the
>    payment and their descriptor appears on the guest's statement, but funds still credit Vyrobel's balance?
> 4. Under **direct charges**, funds never touch Vyrobel's balance at all — but Vyrobel would then compute
>    and instruct owner settlements it cannot execute. Does *instructing* a settlement between an operator
>    and a property owner carry any separate regulated status?

**RECOMMENDATION.** ⚠️ **Do not choose a charge type yet — the legal answer to Q2 decides it, and it is
cheap to get.** If a sub-second platform credit is acceptable, **destination charges with `on_behalf_of`**
are a small change that fixes the merchant-of-record problem and keeps the entire owner-payout system
working. If any platform credit is unacceptable, **direct charges** are the only shape that qualifies, and
the founder should treat it as a programme and decide product question (1) before a line is written.

⚠️ **In the meantime the FAQ answer has already been corrected** to describe what actually happens, so
nothing is being misrepresented while this is decided.
