# FUNDS-FLOW VERIFICATION — what actually happens to money between a guest paying and an owner being paid

**Read-only · 2026-08-22 · prepared to be handed to a payments lawyer**

> ⚠️⚠️ **THIS DOCUMENT ESTABLISHES FACTS. IT RENDERS NO LEGAL VERDICT.**
> Whether funds passing through a platform Stripe balance constitutes *holding client money*, and what that
> would trigger under EU or Swiss payments law — safeguarding, licensing, PSD2 / PSP or e-money
> characterisation — is a question for a **qualified payments lawyer**. Nothing here says the claim is fine,
> defensible, safe, or a problem. Every statement below is a fact with a citation, or it is labelled as an
> open question.
>
> ⚠️ **Nothing was changed, charged, written or published.** No key was flipped, no Stripe call was made, no
> code was modified. The claim under review has **not** been published, softened, reworded or removed — it
> remains held. The pending Stripe test-mode probe (brackets B and C) is a separate exercise and was **not**
> run.

---

## THE ONE-PARAGRAPH ANSWER

The application is written as a **platform charge plus a separate transfer**: guest payments would be created
on **Vyrobel's own Stripe account** (no `stripe_account`, no `transfer_data`, no `on_behalf_of`, no
`application_fee_amount` — none of those parameters exists anywhere in `app/`), and owners would be paid by a
**Stripe `Transfer` out of the platform balance** to their connected account. ⚠️ **But none of that is
switched on.** As configured today the fake Stripe client is bound, the payout rail is `manual`, no owner has
a connected account, and the live data shows **16 payments and 6 payouts, every one of them `manual`, and
zero Stripe references of any kind**. **Today no guest money and no owner money passes through Stripe at
all**: guests pay the property directly and operators pay owners by their own bank transfer, with the
application recording that it happened. ⚠️ The two pictures — today and live — differ in exactly the way that
matters, and the claim under review describes neither of them accurately.

⚠️ **Three further facts a reader should not have to reach §2 to find.** (1) The claim itself is **not
published** — it lives only in the design master for a page that was deferred; but **four live pages carry
funds-adjacent claims**, one of which tells operators they "connect Stripe for card payments" (§5). (2) The
internal record of what owners are owed is an **accrual on charges, not on money received** — a pay-on-arrival
booking credits the owner in full with no payment recorded anywhere (§2). (3) **Nothing in the running
application finalises an owner statement** — the only callers are the demo seeder and the tests — so on a
real deployment the payout engine would have nothing to select (§2).

---

## §1 ⚠️ WHOSE STRIPE BALANCE — the decisive architectural fact

### The charge model

`StripeClient::createPaymentCheckout()` — `app/Payments/Stripe/StripeClient.php:174-196` — creates the
Checkout Session with exactly these parameters:

```
mode, line_items[0].{quantity, price_data.{currency, unit_amount, product_data.name}},
success_url, cancel_url, metadata, payment_intent_data.metadata
```

and `$this->options($idempotencyKey)`, which (`:99-102`) returns **only** `['idempotency_key' => …]` or `[]`.

⚠️ **A repository-wide search of `app/` finds NONE of the following, anywhere:**

| parameter | what its presence would mean | found in `app/` |
|---|---|---|
| `stripe_account` / `Stripe-Account` | **direct charge** — funds land in the **owner's** balance | **absent** |
| `transfer_data` | **destination charge** — funds transit the platform | **absent** |
| `on_behalf_of` | settlement merchant of record | **absent** |
| `application_fee_amount` / `application_fee` | the platform's fee on a direct/destination charge | **absent** |
| `transfer_group` | linking a charge to a later transfer | **absent** |

The SDK client is constructed once, with a single platform secret and no per-request account override
(`StripeClient.php:25-38`). Both call sites pass only amount, currency, description, URLs, metadata and an
idempotency key — `app/Http/Controllers/BookingEngineController.php:523-535` (storefront) and
`app/Http/Controllers/Guest/GuestFolioPaymentController.php:52-61` (a guest paying a folio balance).

> **Fact: as written, a guest's card payment would be a plain charge on Vyrobel's own Stripe account.
> The money would land in the platform's Stripe balance.** There is no code path that would place it
> anywhere else.

### How an owner would be paid

`app/Payouts/Rails/StripeConnectPayoutRail.php:52-59` calls
`createTransfer(amountMinor, currency, destinationAccountId: $owner->stripe_connect_account_id, …)` — a
Stripe **`Transfer`**, which moves funds **from the platform balance** to a connected account. It is the
**only** caller of `createTransfer()` in the codebase.

> **Fact: the intended live shape is platform charge → platform balance → separate `Transfer` → owner's
> connected account.** That is the third of the three Connect models, and it is the one in which funds
> demonstrably rest in the platform's balance between the two hops.

⚠️ **No Stripe `Payout` object (a payout to a bank account) is ever created by this application.** The only
occurrences of "payout" against the Stripe SDK are reads of `account.payouts_enabled`
(`StripeClient.php:218, :225`).

⚠️ **Nothing records which Stripe account a charge landed in.** The `payments` table columns are
`id, organization_id, folio_id, reservation_id, gateway, method, gateway_ref, idempotency_key, type,
amount_minor, currency, status, card_token, metadata, processed_at, …` — there is **no account identifier
column**. `gateway_ref` holds a PaymentIntent id, which under this model would belong to the **platform**
account.

### What the webhook does and does not cover

Only **three** Stripe event types ever change a payment row: `payment_intent.succeeded` → `succeeded`, and
`payment_intent.payment_failed` / `payment_intent.canceled` → `failed`
(`app/Http/Controllers/StripeWebhookController.php:106-113`). `FolioService::settlePayment()` updates only
`status` and `processed_at` (`app/Services/FolioService.php:321-345`); the `gateway_ref` is written
**before** the guest pays, by `recordCheckoutIntent()` (`:288-303`).

⚠️ **There is no handler for `charge.refunded`, `charge.dispute.*`, `payout.*` or `balance.available`.** A
refund or a chargeback initiated from the Stripe dashboard rather than from inside the application would not
be reflected in the folio, the owner ledger or the statement.

⚠️ Connected-account identifiers exist **only on the money-out side** — `owners.stripe_connect_account_id`,
`owners.stripe_connect_payouts_enabled`, `payouts.connect_account_id`. **Nothing on the charge side.**

---

## §2 ⚠️ WHERE THE MONEY SITS, AND FOR HOW LONG

### The internal record of what is owed

⚠️ **Yes — the application maintains its own record of money owed to owners.** It is the `owner_ledger`
table (`entry_type`, `direction`, `amount_minor`, `currency`, `statement_id`, `posted_at`, `reverses_id`,
`unverified`, `prior_period_adjustment`, …). Entries accrue per reservation and per expense; a statement
period is **finalised** (`app/Services/OwnerStatementService.php:105`), which stamps those ledger rows with a
`statement_id` and writes `net_payable_minor` (`:202`) — the figure the owner is owed for that period.

A `Payout` row is then created **from a finalised statement only** — `OwnerPayoutService::createFromStatement`
throws unless the statement is locked, and refuses a second live payout for the same statement.

⚠️ **That "one live payout per statement" rule is enforced in application code only, not by the database.**
`SHOW INDEX FROM payouts WHERE Non_unique=0` returns **only `PRIMARY`**. It is a different class of guarantee
from `owner_ledger.idempotency_key`, which carries a real unique index — a distinction worth drawing when
assessing what controls exist.

The owner-facing Balance page (`app/Livewire/Owner/OwnerBalance.php`) presents five buckets whose identity is
maintained to the cent: **Available + On hold + In transit + Paid + Carried forward == Net position**.
"Available" is the sum of ledger rows **not yet stamped into a statement**.

⚠️⚠️ **BUT IT IS AN ACCRUAL ON CHARGES, NOT A RECORD OF MONEY RECEIVED.** `attributeReservation()` is called
at **booking** time (`app/Services/BookingEngineService.php:234`, immediately before the line that observes
*"nothing paid yet → balance == total"*), and it derives room revenue from
`folio_items WHERE type='room'` — the **charge** ledger — not from the `payments` table
(`app/Services/OwnerAttributionService.php:97-109`). ⚠️ **A "pay on arrival" booking, for which no payment
is recorded at all (`BookingEngineController.php:518-521`), still writes the owner's full room-revenue
credit in the same request.** The ledger records what the owner is *owed under the management agreement*,
not what has been *collected*.

⚠️⚠️ **And today that is not an edge case — it is the only case.** `.env` sets `PAYMENTS_GATEWAY=manual`, and
the card rail is offered only when that value is exactly `stripe` (`BookingEngineController.php:515-516`).
**Every storefront booking made on the current configuration is pay-on-arrival**, so the owner is credited on
100% of them with no payment recorded anywhere. The live data agrees: all 16 payment rows are
`gateway='manual'`.

⚠️ There is a **fourth** accrual writer beyond the three booking paths: the operator-runnable command
`owners:backfill-attribution` (`app/Console/Commands/BackfillOwnerAttribution.php:77`). It is idempotent and
unscheduled. ⚠️ Its own docblock (`:16`) records that `attributeReservation()` once had **no production caller
at all** — *"every booking made through a real entry point left the owner ledger empty"* — which is why a
backfill exists.

⚠️⚠️ **AND NOTHING IN THE RUNNING APPLICATION FINALISES A STATEMENT.** `OwnerStatementService::generateDraft()`
and `::finalize()` have **zero callers in `app/` or `routes/`** — verified by repo-wide search: 74 call sites
exist and every one is in `database/seeders/` or `tests/`. There is no route, no command, no scheduled job
and no UI action that finalises an owner statement in production.

⚠️ **That gap propagates to the whole payout chain.** `PayoutRunService` selects only statements that are
`finalized` **and** locked **and** have `net_payable_minor > 0` (`app/Services/PayoutRunService.php:139-144`).
With no production finaliser, a real deployment would present the engine with **nothing to pay**. The demo
data's six payouts exist because `DemoSeeder` finalised those statements.

### Dwell time

- **Between a guest paying and a statement being finalised**, the amount sits as **unstamped ledger rows** —
  an internal accrual. The period is a **month** in the demo data; finalisation is an explicit act.
- **Between a statement being finalised and the owner being paid**, the amount sits as a `Payout` row moving
  through `pending → processing → paid`, or `held` / `failed`.
- ⚠️ **What moves it onward, today, is a human.** See §3.
- ⚠️ **Nothing reconciles any of this against a Stripe balance.** There is **no call to Stripe's Balance or
  Balance Transactions API anywhere in `app/`** (searched; zero occurrences). The internal ledger and the
  Stripe side are entirely separate bookkeeping.

---

## §3 ⚠️ DOES THE PAYOUT RAIL EXIST, OR IS IT ONLY DOCUMENTED?

**Two rails exist in code.** The container binds one or the other on a config value
(`app/Providers/AppServiceProvider.php:149-151`):

```php
config('services.payouts.rail') === 'stripe_connect'
    ? StripeConnectPayoutRail::class
    : ManualPayoutRail::class
```

`config/services.php:110` — `'rail' => env('PAYOUTS_RAIL', 'manual')`.
⚠️ **`PAYOUTS_RAIL` is absent from `.env`, so the default applies and `ManualPayoutRail` is bound.**

### ⚠️ What `ManualPayoutRail` actually does

`app/Payouts/Rails/ManualPayoutRail.php:11-26`, in full:

> *"Records a payout as paid **OFFLINE** — the operator has made the bank transfer **outside the system** and
> is confirming it here. **The only working rail.** No external reference is generated by the system."*

Its `send()` method takes the payout and returns `new PayoutResult(successful: true)`. **It moves no money.
It contacts nothing. It is a bookkeeping entry that the operator paid the owner by some other means.**

⚠️ **So: owners are paid MANUALLY today, outside the system, by the operator's own bank transfer.** The
application records that this happened. This is stated plainly because it materially changes both the factual
picture and the claim.

`config/services.php:103-106` says the same thing in its own words: `manual` is *"the only working rail"*.

⚠️ **But that same comment is wrong about the other rail, and the correction matters.** It calls
`stripe_connect` *"a config-gated **SKELETON** only"*. It is **not a skeleton**:
`StripeConnectPayoutRail::send()` resolves the payout's own owner's connected account, refuses when there is
none or when Stripe has not enabled payouts, calls `createTransfer` under a deterministic idempotency key,
catches failures and returns a pending result for the webhook to confirm. **It is a complete implementation
that has simply never been run against real Stripe.** The accurate statement is *built but unexercised and
not currently bound* — not *unbuilt*.

⚠️ **A related comment defect, recorded because someone will otherwise reason from it:**
`app/Livewire/Owner/OwnerBalance.php:56` states that `StripeConnectPayoutRail::send()` **throws**. It does
not — it **returns** `PayoutResult(successful: false, …)` (`StripeConnectPayoutRail.php:45-50`). A reader
relying on that comment would believe a safety property the code does not have.

### The automated engine

`config/services.php:116` — `'engine_enabled' => env('PAYOUT_ENGINE_ENABLED', false)`.
⚠️ **`PAYOUT_ENGINE_ENABLED` is absent from `.env`**, so the scheduled `payouts:run` command is inert:
`RunScheduledPayouts::handle()` (`app/Console/Commands/RunScheduledPayouts.php:32-39`) warns and returns
without calling the engine. The command is nevertheless registered on the daily scheduler at 06:00
(`routes/console.php:33`), so cron invokes it every day and it no-ops on the flag.

⚠️⚠️ **THE FLAG IS NOT THE ONLY DOOR TO THE ENGINE.** `POST /app/payouts/run`
(`app/Http/Controllers/Operator/PayoutRunActionController.php:59-65`, route name `payouts.run-now`, gated
`can:payouts.manage`) calls `PayoutRunService::run(TRIGGER_MANUAL)` **directly, with no `engine_enabled`
check at all**. An operator holding that permission can start a payout run today.
⚠️ To be precise about what that would and would not do **today**: it would create and settle `Payout` rows
through the **bound rail, which is `manual`** — so it records offline payments and **still makes no Stripe
call**. The flag guards the *scheduled* path only, not the engine itself.

### The Connect onboarding path

`OwnerConnectController` and `createConnectAccount` / `createConnectAccountLink` exist. ⚠️ But
`STRIPE_CONNECT_WEBHOOK_SECRET` is **absent** from `.env` and `config/services.php:184` therefore reads
`null` — so the Connect webhook (`transfer.created` → mark `paid`; `account.updated` → set
`stripe_connect_payouts_enabled`) has **no signing secret configured**. `StripeConnectPayoutRail` marks a
payout `processing` and relies on that webhook to complete it.

### ⚠️ What the live data shows

Queried read-only against the development database:

| | |
|---|---|
| owners with a `stripe_connect_account_id` | **0 of 1** |
| payouts by rail | **`manual` = 6** (5 `paid`, 1 `pending`) — no other rail has ever been used |
| payments by gateway | **`manual` = 16** — no other gateway has ever been used |
| payments carrying a Stripe PaymentIntent reference (`pi_…`) | **0** |

> **Fact: no money has ever moved through Stripe in this system, in either direction.**

⚠️ **One caveat on that table, because it would otherwise mislead.** The database also holds five paid and one
finalised statement. **Those rows were written by `DemoSeeder`, not by the application** — they are not
evidence that anything finalises statements or issues payouts in production. As established above, nothing
does.

---

## §4 TODAY versus LIVE — two different pictures, not conflated

### ⚠️ TODAY (as configured)

| flag | value | consequence |
|---|---|---|
| `STRIPE_FAKE` | `true` | `FakeStripeClient` is bound (`AppServiceProvider.php:165-173`). Checkout returns `cs_fake_…` at `https://checkout.stripe.test/…` — a host that does not exist. **No Stripe API call is made.** |
| `PAYMENTS_GATEWAY` | `manual` | The storefront offers **no card option**: `$wantsCard` requires `config('services.payments.gateway') === 'stripe'` (`BookingEngineController.php:515-516`), so the guest pays on arrival and **no Checkout session is created at all**. ⚠️ The guest is not even shown a choice — with `$payByCard` false (`resources/views/booking/checkout.blade.php:14`) the form renders a **hidden** `payment_option=on_arrival` (`:178`) and the button reads "Confirm reservation" rather than "Confirm & pay" (`:238`). |
| `PAYOUTS_RAIL` | absent → `manual` | `ManualPayoutRail` — owners paid offline by the operator. |
| `PAYOUT_ENGINE_ENABLED` | absent → `false` | the scheduled payout run is inert. |
| `STRIPE_CONNECT_WEBHOOK_SECRET` | absent | the Connect webhook cannot verify a signature. |
| `APP_ENV` | `local` | `StripeSafetyGuard` refuses a live key outside production. |

> **Today: the guest pays the property directly (cash/card at the property, recorded as a `manual` payment);
> the operator pays the owner by their own bank transfer; the application is a system of record for both.
> Vyrobel's Stripe account receives nothing and disburses nothing.**
>
> ⚠️ One asymmetry worth noting: the **guest folio payment path**
> (`GuestFolioPaymentController.php:52`) does **not** check `services.payments.gateway` before calling
> `createPaymentCheckout()`. Today it reaches the **fake** client, so nothing happens. It is recorded here
> because it is the path that would behave differently from the storefront if only `STRIPE_FAKE` were changed.

### LIVE (if the documented go-live steps were taken)

With a real `STRIPE_SECRET` in `production`, `PAYMENTS_GATEWAY=stripe`, `PAYOUTS_RAIL=stripe_connect`,
`PAYOUT_ENGINE_ENABLED=true`, `STRIPE_CONNECT_WEBHOOK_SECRET` set, and each owner onboarded through Connect:

1. A guest pays via hosted Checkout → **a charge on Vyrobel's platform account** → funds settle to **the
   platform's Stripe balance**.
2. The `payment_intent.succeeded` webhook records a `Payment` against the folio.
3. Ledger entries accrue; at period end a statement is finalised and `net_payable_minor` is fixed.
4. A `Payout` is created from that statement; the engine or an operator triggers it.
5. `StripeConnectPayoutRail` creates a **`Transfer` from the platform balance** to the owner's connected
   account; the payout is marked `processing`.
6. The Connect webhook (`transfer.created`) marks it `paid`.

> **Live: guest funds would rest in Vyrobel's own Stripe balance from settlement until the transfer is
> initiated — an interval determined by the statement period and the payout schedule, not by the payment.**

---

## §5 ⚠️ THE FACTS A PAYMENTS LAWYER WOULD NEED

### The claim, verbatim, and where it lives

> **Q: Does Vyrobel hold my money?**
> **A: No. Guest payments settle directly to you through your own payment provider. We never hold client
> funds, and we store payment references rather than card numbers.**

**Location:** the FAQ page's question array, inside a `DCLogic` `<script>` in
`docs/design/front/Vyrobel FAQ.dc.html` (extracted text: `g5text/faq-questions.txt:25-26`).

⚠️ **Publication status: NOT PUBLISHED.** `/faq` is not a registered route — the fifteen routed marketing
pages are home, product, features, modules, how-it-works, pricing, try-booking, switching, for-hotels,
for-apartments, for-villas, case-studies and the three scenarios. A search of `resources/` for this wording
returns **zero** hits. It is also **not** in `resources/marketing/claims.json`. Gate 5 deferred the FAQ; the
claim has never been on the live site.

⚠️ **The same assertion appears in two further unrouted prototypes**, both also unpublished:
`docs/design/front/Vyrobel Legal Packet.dc.html` (an internal legal-review document: *"Whether we touch
guest money — The drafts assume payments settle directly to the operator…"*) and
`docs/design/front/Vyrobel Privacy.dc.html`, whose data table carries the row
*"Payment references (tokens) | Contract — taking payment | **Held by the payment provider, not by us**"*.

### ⚠️ WHAT *IS* PUBLISHED — four live pages carry funds-adjacent claims

The claim under review is unpublished, but adjacent assertions on **routed, live** pages are not, and a
lawyer will want them:

| page (live route) | verbatim | file |
|---|---|---|
| `/pricing` | "No setup fee · No commission on direct bookings · **Card processing is billed by your payment provider**" | `resources/views/marketing/pricing.blade.php:117` |
| `/how-it-works` | "Publish the branded storefront, **connect Stripe for card payments**, and link your existing OTA listings" | `resources/views/marketing/how-it-works.blade.php:108` |
| `/features` | under "Commission-free booking engine" — a branded storefront with your photos and policies | `resources/views/marketing/features.blade.php:261, :416-417` |
| `/switching` | under "Stored card details" — "**Card tokens belong to your payment provider, not your PMS**" | `resources/views/marketing/switching.blade.php:64` |

⚠️ **The `/how-it-works` line is the one to look at.** "Connect Stripe for card payments" tells an operator
they connect **their own** Stripe account. As established in §1, no charge-side code references a connected
account, and there is exactly one Stripe credential in the configuration — the platform's. Whether that line
is a description of the present product, of an intended one, or of the Connect *onboarding* an owner does for
payouts, is not something the code can settle. ⚠️ **It is flagged, not changed.**

⚠️ The nav and footer render a "FAQ" item on every published page. Because `/faq` is unrouted it renders as a
marked, non-clickable pending item — so the label is visible sitewide while the answer is not reachable.

### The factual points the claim asserts, set against the code

| the claim says | the code says |
|---|---|
| "Guest payments **settle directly to you**" | As written, they would settle to **Vyrobel's** Stripe balance (§1). **Today** they do not go through Stripe at all — they are collected by the property and recorded as `manual` (§4). |
| "through **your own** payment provider" | There is one Stripe account in the configuration, the platform's. No per-organisation Stripe credential exists anywhere in `config/` or the schema. |
| "We **never hold** client funds" | ⚠️ Not assessed here — that is the legal question. The **facts** are in §1–§4. |
| "we store payment references rather than card numbers" | Consistent with the code: hosted Checkout, `gateway_ref` holds a PaymentIntent id, no PAN is handled. |

### ⚠️ Open questions — LISTED, NOT ANSWERED

These are for the lawyer, and this document deliberately does not attempt them.

1. Does receiving guest funds into the platform's own Stripe balance, and disbursing them later by
   `Transfer`, constitute holding or controlling client money in the relevant jurisdiction(s)?
2. If so, what follows — safeguarding obligations, licensing or registration, PSP/PI characterisation,
   e-money characterisation?
3. Does the answer differ between the **live** design (§4) and the **current** manual arrangement (§4), where
   the operator collects from the guest and pays the owner directly and Vyrobel touches no funds?
4. Does the interval matter — funds resting from settlement until a monthly statement is finalised and a
   payout is triggered — and is there a duration beyond which the characterisation changes?
5. Does maintaining an internal ledger of amounts owed to owners (§2) affect the characterisation, even in
   the current arrangement where no funds are held?
6. Which entity is the merchant of record for a guest's card payment, and what does that make Vyrobel
   contractually to the guest, to the operator and to the owner?
7. What does Stripe's own Connect platform agreement require or prohibit for this model, and does it impose
   obligations independent of statute?
8. Is a claim about fund custody made on a marketing page a regulated communication in any relevant
   jurisdiction, and what standard must it meet?
9. Since the owner is often a third party to the operator's contract with Vyrobel, does paying an owner from
   the platform balance create a distinct relationship or obligation to that owner?
10. ⚠️ **Which Stripe account does the configured secret actually belong to** — a standalone account, or a
    Connect platform with operator organisations as connected accounts? **The repository cannot answer
    this.** No charge-side code references a connected account, so the code establishes only that charges
    are created on whatever account the secret belongs to. This is a fact only the founder's Stripe
    dashboard can settle, and several of the questions above depend on it.
11. Does it matter that the internal owner ledger is an **accrual on charges** rather than a record of funds
    received (§2) — i.e. that Vyrobel records an amount owed to an owner for a booking on which no money has
    yet been collected by anyone?
12. Does the published line "connect Stripe for card payments" (`/how-it-works`) create an expectation, in
    contract or in consumer-facing terms, that the operator's own Stripe account processes guest payments?

---

## §6 What would change the picture — ⚠️ INFORMATIONAL ONLY

⚠️ **This is not a recommendation, not a proposal, and not scoped.** It is recorded only because the lawyer
will ask what the alternatives are. **The founder decides, with advice.**

The architecture that removes platform transit is a **direct charge on the connected account**: the Checkout
Session is created with a `Stripe-Account` / `stripe_account` option naming the owner's or operator's
connected account, and the platform takes an `application_fee_amount`. Funds then settle into **that
account's** balance and never enter the platform's.

Factual trade-offs, stated without recommending:

- **Refunds** are issued from the connected account's balance, so a refund can fail for insufficient funds
  there — a case the current `FolioService` refund path does not contemplate.
- **Dispute and chargeback liability** shifts to the connected account under direct charges; the platform's
  exposure and the fee mechanics differ.
- **Reporting** changes: charges no longer appear in the platform's own balance transactions, so any
  reconciliation built on that would need to read per-account.
- **Onboarding friction** rises: every party who is to receive funds must complete Stripe KYC before they can
  be paid at all — today **0 of 1** owners have a connected account.
- **The existing transfer-based payout code would no longer apply** in the same form, since there would be
  nothing in the platform balance to transfer.

---

## Provenance

Every fact above was established by opening the cited file or by a read-only query. ⚠️ Four independent
traces of the money path were then run and **each was adversarially re-checked by a second reader whose
instruction was to refute it, opening every citation**. Nothing material was overturned; three partial
corrections were citation offsets or strengthened an existing finding, and every fact they added that
appears above was independently re-verified before being written here. **Money as seeded is
byte-identical: 929 / 10,912,000 / 0 `unit_rate_overrides` / 41 / 16 / 0.** No code was changed, no charge
made, no key flipped, no test-mode probe run, and the claim was not touched.
