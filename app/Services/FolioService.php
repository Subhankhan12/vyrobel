<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Folio\RefundAccountUnknownException;
use App\Exceptions\Folio\RefundExceedsPaymentException;
use App\Models\AddOn;
use App\Models\AuditEvent;
use App\Models\Fee;
use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PaymentAuthorization;
use App\Models\Reservation;
use App\Models\Tax;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\GatewayResult;
use App\Payments\Stripe\StripeCheckoutSession;
use App\Payments\Stripe\StripeIdempotency;
use App\Support\Billing\CheckoutSessions;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\Money\Money;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Owns the folio as an APPEND-ONLY ledger. All money is integer minor units end-to-end (Money
 * value object) — no float ever. Derived caches (folio total/paid/balance, reservation
 * total/balance/payment_status) are recomputed from the ledger after every posting.
 *
 * Correct-by-reversal: posted folio_items and payments are NEVER deleted or mutated to correct
 * them. Discounts/credits are new negative lines; refunds/voids are new offsetting Payment rows.
 */
class FolioService
{
    public function __construct(
        private PaymentGateway $gateway,
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    public function openFolio(Reservation $reservation): Folio
    {
        return Folio::query()->firstOrCreate(
            ['reservation_id' => $reservation->getKey()],
            ['currency' => $reservation->currency, 'status' => 'open'],
        );
    }

    /**
     * Append a charge line and re-derive folio/reservation totals.
     */
    public function postCharge(
        Folio $folio,
        string $type,
        string $description,
        Money $unitAmount,
        int $quantity = 1,
        ?Money $tax = null,
        ?Model $reference = null,
        ?string $serviceDate = null,
        ?string $idempotencyKey = null,
    ): FolioItem {
        $amount = $unitAmount->multiply($quantity);

        // 🔒 IDEMPOTENT (mirrors recordPayment / the guest-charge firstOrCreate) when a key is given — a re-submit /
        // double-click of the SAME add-charge (same per-submit nonce) records ONCE: pre-checked here, and backed by
        // the UNIQUE index on `idempotency_key` (the concurrent-race backstop). A null key preserves EVERY existing
        // caller (postAddOn / applyTax / applyFee, the room posting) — each still writes a distinct line as before.
        if ($idempotencyKey !== null) {
            $existing = FolioItem::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing instanceof FolioItem) {
                return $existing;   // a duplicate submission → the ORIGINAL line, once (no second row, no re-fired event)
            }
        }

        try {
            $item = DB::transaction(function () use ($folio, $type, $description, $unitAmount, $quantity, $amount, $tax, $reference, $serviceDate, $idempotencyKey): FolioItem {
                $item = FolioItem::create([
                    'folio_id' => $folio->getKey(),
                    'type' => $type,
                    'description' => $description,
                    'unit_amount' => $unitAmount,
                    'quantity' => $quantity,
                    'amount' => $amount,
                    'tax' => $tax,
                    'currency' => $folio->currency,
                    'reference_type' => $reference?->getMorphClass(),
                    'reference_id' => $reference?->getKey(),
                    'service_date' => $serviceDate,
                    // posted_by → users: the OPERATOR (web guard) or null — never a signed-in guest's id
                    // (a guest booking/checkout while logged in must not leak their guest id into this FK).
                    'posted_by' => Auth::guard('web')->id(),
                    'posted_at' => now(),
                    'idempotency_key' => $idempotencyKey,
                ]);

                $this->recompute($folio);

                return $item;
            });
        } catch (UniqueConstraintViolationException $e) {
            // A near-simultaneous double-submit: the other request won the unique key. Return ITS line so the caller
            // sees exactly one charge (the folio total is unchanged — the same single line either way).
            $existing = $idempotencyKey !== null
                ? FolioItem::query()->where('idempotency_key', $idempotencyKey)->first()
                : null;
            if ($existing instanceof FolioItem) {
                return $existing;
            }
            throw $e;
        }

        $this->domainEvents->record('folio.charge_posted', $item, [
            'type' => $type,
            'amount_minor' => $amount->amountMinor(),
        ]);

        // ⚠️⚠️ WHO POSTED THIS. A charge INCREASES WHAT A GUEST OWES and recorded no author: this method wrote
        // the domain event above and nothing else, while every payment method in this same file
        // ({@see recordPayment}, {@see recordCheckoutIntent}, {@see settlePayment}) writes BOTH. That is
        // F-17/F-22's class — a write with no actor — in a writer FIX 05's sweep never reached.
        //
        // ⚠️ A DOMAIN EVENT CANNOT ANSWER "WHO": `domain_events` has NO actor column. The business outbox
        // records that a charge happened; only `audit_events` records who made it happen.
        //
        // ⚠️ ONE GUARD, EVERY CALLER. `postAddOn()`, `applyTax()` and `applyFee()` all funnel through this
        // method, so the trail covers them without a second recorder on each entrance.
        //
        // ⚠️ `before` IS NULL BY FACT, NOT BY OMISSION — a charge is an APPEND: no previous value exists for
        // a line that did not exist. (Contrast the rate path, where eleven sites pass null over a real prior
        // value, which is why undo is unbuildable there.)
        //
        // ⚠️ PII-SAFE (#28): the type and the integer amount — never the guest, never free text.
        $this->audit->record('folio.charge_posted', $item, null, [
            'type' => $type,
            'amount_minor' => $amount->amountMinor(),
        ]);

        return $item->refresh();
    }

    /**
     * Apply a guest WALLET CREDIT to this folio (Phase 4c · SG2) — a new negative `discount` line that reduces the
     * balance owed, through the single write path + recompute (the balance stays the derived truth; this is the
     * "credits are new negative lines" rule above). A SYSTEM/guest credit, not an operator action, so `posted_by`
     * stays null — unlike {@see postCharge()} it never reads Auth::id() (a guest request's default guard is the
     * guest, not an operator user). The wallet-side debit is recorded by {@see GuestWalletService::applyToBooking()}.
     *
     * @param  Money  $amount  the POSITIVE credit amount; stored as a negative line
     */
    public function applyWalletCredit(Folio $folio, Money $amount, ?Model $reference = null): FolioItem
    {
        $line = Money::of(-abs($amount->amountMinor()), $amount->currency());

        $item = DB::transaction(function () use ($folio, $line, $reference): FolioItem {
            $item = FolioItem::create([
                'folio_id' => $folio->getKey(),
                'type' => 'discount',
                'description' => 'Wallet credit applied',
                'unit_amount' => $line,
                'quantity' => 1,
                'amount' => $line,
                'currency' => $folio->currency,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'posted_at' => now(),
            ]);

            $this->recompute($folio);

            return $item;
        });

        $this->domainEvents->record('folio.wallet_credit_applied', $item, ['amount_minor' => $line->amountMinor()]);

        // ⚠️ THE SAME GAP, THE SECOND (AND LAST) WRITER. 🔬 Exactly two places in this codebase create a
        // `FolioItem` — this one and {@see postCharge()} — so with both recording an author, no folio line
        // can be written without one. A wallet credit REDUCES what is owed rather than increasing it, but
        // "who moved this guest's money" is the same question.
        $this->audit->record('folio.wallet_credit_applied', $item, null, ['amount_minor' => $line->amountMinor()]);

        return $item->refresh();
    }

    public function postAddOn(Folio $folio, AddOn $addOn, int $quantity = 1): FolioItem
    {
        return $this->postCharge($folio, 'add_on', $addOn->name, $addOn->price, $quantity, reference: $addOn);
    }

    public function applyTax(Folio $folio, Tax $tax, Money $base, int $nights = 1, int $persons = 1): FolioItem
    {
        return $this->postCharge($folio, 'tax', $tax->name, $this->computeTax($tax, $base, $nights, $persons), reference: $tax);
    }

    public function applyFee(Folio $folio, Fee $fee, ?Money $base = null, int $nights = 1, int $persons = 1): FolioItem
    {
        return $this->postCharge($folio, 'fee', $fee->name, $this->computeFee($fee, $base, $nights, $persons), reference: $fee);
    }

    public function computeTax(Tax $tax, Money $base, int $nights = 1, int $persons = 1): Money
    {
        $currency = $base->currency();

        return match ($tax->mode) {
            'percentage' => Money::of((int) round($base->amountMinor() * (float) $tax->rate / 100), $currency),
            'per_night' => Money::of((int) $tax->amount_minor * max(1, $nights), $currency),
            'per_person_night' => Money::of((int) $tax->amount_minor * max(1, $persons) * max(1, $nights), $currency),
            default => Money::of((int) $tax->amount_minor, $currency), // flat
        };
    }

    public function computeFee(Fee $fee, ?Money $base = null, int $nights = 1, int $persons = 1): Money
    {
        $currency = $base?->currency() ?? (string) $fee->currency;

        return match ($fee->mode) {
            'percentage' => Money::of((int) round(($base?->amountMinor() ?? 0) * (float) $fee->rate / 100), $currency),
            'per_night' => Money::of((int) $fee->amount_minor * max(1, $nights), $currency),
            'per_person' => Money::of((int) $fee->amount_minor * max(1, $persons), $currency),
            default => Money::of((int) $fee->amount_minor, $currency), // flat
        };
    }

    /**
     * Take a payment through the gateway and post it to the ledger.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function recordPayment(Folio $folio, Money $amount, ?string $cardToken = null, array $metadata = [], ?string $idempotencyKey = null): Payment
    {
        // 🔒 IDEMPOTENT (Batch C · GATE 3) when a key is given — the guest-charge firstOrCreate discipline
        // applied to the on-desk manual payment. A re-submit / double-click with the SAME key records ONCE:
        // checked BEFORE the gateway charge (so a duplicate never re-hits the gateway) and backed by the
        // UNIQUE index on `idempotency_key` (the concurrent-race backstop). A null key preserves every
        // existing caller (guest hosted-checkout keys on the PaymentIntent id via recordCheckoutIntent).
        if ($idempotencyKey !== null) {
            $existing = Payment::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing instanceof Payment) {
                return $existing;
            }
        }

        // ⚠️⚠️ THE THIRD PATH — THE DESK CARD CHARGE. The two hosted-Checkout paths were switched first;
        //    this one was not, so under PAYMENTS_GATEWAY=stripe it charged the PLATFORM and stamped nothing.
        //    ⚠️ Resolved ONLY for a gateway that actually reaches Stripe: a `manual` payment is money taken
        //    outside any gateway (cash, bank transfer, a terminal we never see), so requiring onboarding for
        //    it would block the desk from recording cash — and would break every seeded payment.
        //    ⚠️ NO FALLBACK: if the operator is not `charges_enabled`, `accountForCharge()` REFUSES. There is
        //    no platform charge to fall back to, on this path either.
        $connectedAccountId = $this->gateway->name() === 'stripe'
            ? app(OperatorConnectService::class)->accountForCharge(
                Organization::query()->findOrFail((string) $folio->organization_id),
            )
            : null;

        $result = $this->gateway->charge($amount, $cardToken, $metadata, $connectedAccountId);

        $status = $this->mapResultStatus($result);
        // Carry the SCA/3DS next-step secret so the frontend can complete authentication. Never
        // any card data — only Stripe's client_secret for the pending intent.
        $storedMetadata = $result->clientSecret !== null
            ? array_merge($metadata, ['client_secret' => $result->clientSecret])
            : $metadata;

        try {
            $payment = DB::transaction(function () use ($folio, $amount, $cardToken, $storedMetadata, $result, $status, $idempotencyKey, $connectedAccountId): Payment {
                $payment = Payment::create([
                    'folio_id' => $folio->getKey(),
                    'reservation_id' => $folio->reservation_id,
                    'gateway' => $this->gateway->name(),
                    // QA D2-OBS-1 — the REAL method (cash / bank_transfer / card / …) the caller chose, stored as a
                    // distinct, queryable column rather than only inside metadata (where the generic `gateway`
                    // hid it). Label only — the amount/reconciliation are untouched. Null when a caller records
                    // no explicit method (the gateway still identifies the rail).
                    'method' => is_string($storedMetadata['method'] ?? null) ? $storedMetadata['method'] : null,
                    'gateway_ref' => $result->reference,
                    // ⚠️ Stamped so a refund can find the account that TOOK the charge. Null on `manual`,
                    //    which is correct: there is no Stripe charge behind it.
                    'stripe_account_id' => $connectedAccountId,
                    // ⚠️ ADMIN 06 — Stripe's own livemode for the charge (null on the manual rail: no Stripe object)
                    'livemode' => $result->livemode,
                    'idempotency_key' => $idempotencyKey,
                    'type' => 'payment',
                    'amount' => $amount,
                    'currency' => $amount->currency(),
                    'status' => $status,
                    'card_token' => $cardToken,
                    'metadata' => $storedMetadata,
                    'processed_at' => now(),
                ]);

                $this->recompute($folio); // a pending (3DS) payment is NOT counted as paid yet

                return $payment;
            });
        } catch (UniqueConstraintViolationException $e) {
            // A near-simultaneous double-submit: the other request won the unique key. Return ITS payment so
            // the caller sees exactly one recorded payment (the manual gateway charge is a harmless no-op).
            $existing = $idempotencyKey !== null
                ? Payment::query()->where('idempotency_key', $idempotencyKey)->first()
                : null;
            if ($existing instanceof Payment) {
                return $existing;
            }
            throw $e;
        }

        $this->domainEvents->record('payment.recorded', $payment, [
            'amount_minor' => $amount->amountMinor(),
            'status' => $payment->status,
        ]);
        $this->audit->record('payment.recorded', $payment, null, ['amount_minor' => $amount->amountMinor()]);

        return $payment->refresh();
    }

    /**
     * Record a PENDING payment for a hosted Stripe CHECKOUT (Phase 8 · SG2 — the guest folio charge). The card
     * is charged on Stripe's page, so no gateway.charge() here; we pre-create a `pending` Payment against the
     * session's PaymentIntent id, and the existing `payment_intent.succeeded` webhook settles it
     * ({@see settlePayment()}) — the folio records the money ONLY on the webhook. Pending is not counted as
     * paid, so an abandoned checkout never shows as paid.
     *
     * 🔒 Idempotent by construction: `firstOrCreate` on `gateway_ref` (the PaymentIntent id) — a double-click,
     * which reuses the SAME Stripe session (and PI) via the sending-side idempotency key, yields ONE pending row.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function recordCheckoutIntent(Folio $folio, Money $amount, string $paymentIntentId, array $metadata = [], ?string $connectedAccountId = null, ?StripeCheckoutSession $session = null): Payment
    {
        // ⚠️ ADMIN 06 — the open payment page itself is recorded (KNOWN-LIMITS L19), so a deletion can see it
        if ($session !== null) {
            CheckoutSessions::record($session, 'payment');
        }

        $payment = Payment::query()->firstOrCreate(
            ['gateway_ref' => $paymentIntentId],
            [
                'folio_id' => $folio->getKey(),
                'reservation_id' => $folio->reservation_id,
                'gateway' => 'stripe',
                // ⚠️ Stamped at CHARGE time so the refund path can find the right account later.
                'stripe_account_id' => $connectedAccountId,
                'livemode' => $session?->livemode,
                'type' => 'payment',
                'amount' => $amount,
                'currency' => $amount->currency(),
                'status' => 'pending',
                'metadata' => $metadata,
                'processed_at' => now(),
            ],
        );

        if ($payment->wasRecentlyCreated) {
            $this->recompute($folio);   // pending is NOT counted as paid — the folio changes on the webhook
            $this->domainEvents->record('payment.recorded', $payment, ['amount_minor' => $amount->amountMinor(), 'status' => 'pending']);
            $this->audit->record('payment.checkout_started', $payment, null, ['amount_minor' => $amount->amountMinor()]);
        }

        return $payment;
    }

    /**
     * Settle a PENDING payment once Stripe confirms it asynchronously (SCA/3DS or `processing`).
     * Idempotent: only a `pending` row transitions — a redelivered webhook (or one racing another)
     * is a no-op. This is NOT mutating a settled ledger entry; it completes the lifecycle of an
     * unsettled one (mirrors the authorize→capture lifecycle), then recomputes so a now-succeeded
     * payment counts as paid.
     */
    public function settlePayment(Payment $payment, string $toStatus, ?bool $livemode = null): Payment
    {
        if (! in_array($toStatus, ['succeeded', 'failed'], true)) {
            throw new InvalidArgumentException("settlePayment expects succeeded|failed, got [{$toStatus}].");
        }

        if ($payment->status !== 'pending') {
            // ⚠️⚠️ THE GUARD STAYS EXACTLY AS IT IS — the money is NOT re-settled. What changes is that a
            //    retry no longer walks away with nothing: if this payment reached its terminal status but
            //    its audit row never landed, the row is written now. See {@see resumeSettlementTrail()}.
            $this->resumeSettlementTrail($payment, 'payment.settled', null, ['status' => (string) $payment->status]);

            return $payment; // already settled (or never pending) — the settlement itself is untouched
        }

        $folio = $payment->folio;

        DB::transaction(function () use ($payment, $toStatus, $folio, $livemode): void {
            $payment->update(['status' => $toStatus, 'processed_at' => now()]
                + ($livemode !== null && $payment->livemode === null ? ['livemode' => $livemode] : []));

            if ($folio !== null) {
                $this->recompute($folio);
            }
        });

        $this->domainEvents->record('payment.settled', $payment, ['status' => $toStatus]);
        $this->audit->record('payment.settled', $payment, null, ['status' => $toStatus]);

        return $payment->refresh();
    }

    /**
     * Refund (fully or partially) a prior payment. Correct-by-reversal: a NEW offsetting Payment
     * is created; the original row is never touched.
     *
     * BOUNDED: the CUMULATIVE total refunded against a payment may equal, but never exceed, the original.
     * A fully-refunded payment refuses any further refund; a partially-refunded one accepts only up to
     * {@see remainingRefundableMinor()}. This is a DIFFERENT invariant from append-only (#7) — #7 stops you
     * EDITING a refund, it never bounded the SUM, which is how a €369.00 payment once took three full
     * refunds (docs/DECISIONS.md 2026-07-16). Enforced HERE, in the single write path, so every caller is
     * covered rather than each controller re-implementing it.
     *
     * 🔒 CONCURRENCY-SAFE (audit 2026-08-07 · H1/L1-01/F4-2). The bound used to be a check-then-act with no
     * lock: two concurrent refunds both read alreadyRefunded=0, both passed, and both called the gateway — so
     * the SUM could exceed the original, which is exactly what this invariant exists to forbid. It now mirrors
     * {@see OwnerPayoutService::pay()} — the sibling money-OUT path that already had the right shape: the whole
     * operation runs in a DB transaction that ROW-LOCKS the original payment and RE-READS the cumulative total
     * INSIDE the lock, so the check and the write are serialised. A second concurrent caller blocks on the lock,
     * then re-reads the now-updated total and is refused; the throw rolls the transaction back, so a refused
     * refund writes NOTHING (no partial rows, folio still reconciles #17).
     *
     * ⚠️ The gateway call is deliberately INSIDE the lock, exactly as the payout rail is, so money can never be
     * moved by a caller that would fail the bound. That does hold the lock across network I/O once live Stripe
     * is flipped on — a known, separately-tracked cost (audit P5-02, same as the payout rail), accepted here
     * because correctness of the money bound outranks lock duration.
     *
     * @param  string|null  $idempotencyKey  OPTIONAL caller token that collapses a double-submit of the SAME
     *                                       logical refund (mirrors {@see recordPayment()}, backed by the UNIQUE
     *                                       index on payments.idempotency_key). Deliberately NOT auto-derived:
     *                                       two sequential PARTIAL refunds of equal amount are legitimate and
     *                                       must not be silently collapsed — the bound, not the key, is what
     *                                       protects the total.
     *
     * @throws RefundExceedsPaymentException when the cumulative bound or the currency rule is broken
     */
    /**
     * ⚠️ `$borneBy` — WHO BEARS THIS REFUND, captured AT REFUND TIME and never editable afterwards.
     * ⚠️ It must be persisted on the refund row INSIDE this transaction, because
     * {@see AdjustOwnerLedgerOnRefund} reads that row synchronously the moment `payment.refunded` is
     * recorded below. Setting it afterwards would let the listener post an owner debit the operator had
     * explicitly declined. NULL keeps today's behaviour exactly: the owner bears it.
     */
    public function refundPayment(Payment $original, Money $amount, ?string $reason = null, ?string $idempotencyKey = null, ?string $borneBy = null): Payment
    {
        if (! in_array($original->type, ['payment', 'capture'], true)) {
            throw new InvalidArgumentException('Only a payment/capture can be refunded.');
        }

        // A refund must move money. Zero/negative would pass the bound below (0 > remaining is false) and
        // post a meaningless €0.00 row into an APPEND-ONLY ledger that can never be tidied away.
        if ($amount->amountMinor() <= 0) {
            throw new InvalidArgumentException('A refund amount must be greater than zero.');
        }

        // Same-currency only: minor units are not comparable across currencies, so both the bound below and
        // the sum it relies on would be meaningless. (Previously unchecked — a USD refund against an EUR
        // payment passed the old raw-minor-unit comparison.)
        if ($amount->currency() !== (string) $original->currency) {
            throw RefundExceedsPaymentException::currencyMismatch(
                (string) $original->getKey(), $amount->currency(), (string) $original->currency,
            );
        }

        // 🔒 Caller-supplied idempotency (the recordPayment() discipline): a replayed submit returns the
        // ORIGINAL refund instead of issuing a second one. Checked before the lock as a cheap fast path; the
        // UNIQUE index on payments.idempotency_key is the real backstop if two replays race here.
        if ($idempotencyKey !== null) {
            $existing = Payment::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $folio = $original->folio;

        // #100 — the operator's refund reason is recorded on the refund row's metadata (JSON — no schema change),
        // so it surfaces on the folio + reports. It is operator context, not guest PII.
        $metadata = ['refunds_payment_id' => $original->getKey()];
        if ($reason !== null && trim($reason) !== '') {
            $metadata['reason'] = trim($reason);
        }

        $refund = DB::transaction(function () use ($original, $amount, $folio, $metadata, $idempotencyKey, $borneBy): Payment {
            // 🔒 Row-lock THE ORIGINAL payment; a concurrent refundPayment() for it waits here until we commit.
            $locked = Payment::query()->lockForUpdate()->findOrFail($original->getKey());

            // THE CUMULATIVE BOUND, re-read INSIDE the lock. Subsumes the old per-call `amount > original`
            // check: with no prior refunds the remainder IS the original. Still runs BEFORE the gateway call
            // below — ordering is load-bearing, or we would move real money and only then reject.
            $alreadyRefunded = $this->refundedCommittedMinor($locked);
            $remaining = (int) $locked->amount_minor - $alreadyRefunded;

            if ($amount->amountMinor() > $remaining) {
                // Throwing here rolls the transaction back: a refused refund writes NOTHING.
                throw RefundExceedsPaymentException::forPayment(
                    (string) $locked->getKey(), $amount->amountMinor(), $alreadyRefunded, (int) $locked->amount_minor,
                );
            }

            // Deterministic per LOGICAL refund: the same payment at the same cumulative-refunded state for the
            // same amount replays to one gateway refund, while a genuine second PARTIAL refund (different
            // $alreadyRefunded) gets a distinct key. Only meaningful once live Stripe is flipped on; the manual
            // gateway ignores it.
            $gatewayKey = $idempotencyKey ?? StripeIdempotency::keyFor(
                'folio_refund',
                $locked->getKey().':'.$alreadyRefunded.':'.$amount->amountMinor(),
            );

            // ⚠️ The account is read from the PAYMENT, not re-derived from the organisation: a refund must
            //    follow the money it is reversing, even if the operator has since re-onboarded.
            //
            // ⚠️⚠️ A STRIPE PAYMENT WITH NO STAMPED ACCOUNT REFUSES — IT DOES NOT FALL BACK.
            //    Passing null omits the `Stripe-Account` header, attempting the refund on the PLATFORM:
            //    either a 404 (the charge lives on the connected account) or, worse, platform funds
            //    settling an operator's guest. That is a silent fallback and the switch forbids one.
            //
            // ⚠️ THIS GUARD WAS WITHDRAWN ONCE, CORRECTLY. It was written while only the two hosted-Checkout
            //    paths were switched, and it blocked a legitimate DESK payment that was genuinely a platform
            //    charge. ⚠️ It is correct now, and only now, because all THREE charge paths stamp the
            //    account: the storefront, the guest portal, and `recordPayment()`.
            //
            // ⚠️ A `manual` payment is the other case and is CORRECT: no gateway charge exists behind it, so
            //    its refund is a folio ADJUSTMENT and a null account is the honest value.
            if ((string) $locked->gateway === 'stripe' && ($locked->stripe_account_id === null || $locked->stripe_account_id === '')) {
                throw RefundAccountUnknownException::make();
            }

            $result = $this->gateway->refund((string) $locked->gateway_ref, $amount, $gatewayKey, $locked->stripe_account_id);

            $refund = Payment::create([
                'folio_id' => $locked->folio_id,
                'reservation_id' => $locked->reservation_id,
                'gateway' => $this->gateway->name(),
                'gateway_ref' => $result->reference,
                // ⚠️ ADMIN 06 — Stripe's livemode for the refund; a refund lives in the same mode as its charge
                'livemode' => $result->livemode ?? $locked->livemode,
                'type' => 'refund',
                'amount' => $amount,
                'currency' => $amount->currency(),
                // ⚠️⚠️ THREE OUTCOMES, NOT TWO. A refund Stripe has ACCEPTED but not yet moved is `pending`
                //    — it completes automatically when the balance covers it. Filing that as `failed` is
                //    what made the money vanish from the running total and invited a second refund.
                'status' => self::refundRowStatus($result),
                'metadata' => $metadata,
                'refund_borne_by' => $borneBy,
                'idempotency_key' => $idempotencyKey,
                'processed_at' => now(),
            ]);

            if ($folio !== null) {
                $this->recompute($folio);
            }

            return $refund;
        });

        // ⚠️⚠️ THE EFFECT DEFERS TO SETTLEMENT. `payment.refunded` drives the owner-ledger debit AND the
        //    guest's "refund processed" email — neither may fire while the money is still queued. A refund
        //    that ultimately FAILS must therefore have posted NOTHING, and a guest must not be told a
        //    refund completed before it did. {@see settleRefund()} emits it when Stripe confirms.
        //    ⚠️ An IMMEDIATE refund is unchanged: it is already `succeeded` here, so the event fires now,
        //    exactly as before.
        if ((string) $refund->status === 'succeeded') {
            $this->domainEvents->record('payment.refunded', $refund, [
                'refunds_payment_id' => $original->getKey(),
                'amount_minor' => $amount->amountMinor(),
            ]);
        }
        // ⚠️ THIS MOVES MONEY BETWEEN TWO PARTIES, so it must be ATTRIBUTABLE: who decided, when, on which
        //    refund. Only an EXPLICIT choice is recorded — the default is an absence, not a decision, and
        //    recording "owner" for every untouched refund would make the audit trail claim a choice nobody
        //    made.
        $this->audit->record('payment.refunded', $refund, null, array_filter([
            'refunds_payment_id' => $original->getKey(),
            'refund_borne_by' => $borneBy,
        ], static fn ($v): bool => $v !== null));

        return $refund;
    }

    /**
     * Σ of the SUCCEEDED refunds already recorded against a payment, in that payment's currency.
     *
     * The link is `payments.metadata->refunds_payment_id` (a json path — there is no FK column), stamped by
     * {@see refundPayment()}. Deliberate choices:
     *   • `status = 'succeeded'` ONLY — a failed refund moved no money and must not consume the headroom.
     *   • `withTrashed()` — a soft-deleted refund STILL bounds. Posted financial rows should never be
     *     deleted (invariant #7), so this is belt-and-braces: a delete must never hand back refundable
     *     headroom. It fails safe if that discipline ever slips.
     *   • currency-matched — see the guard in {@see refundPayment()}.
     */
    /**
     * Settle a PENDING refund once Stripe confirms it — `pending` → `succeeded` or `failed`.
     *
     * ⚠️⚠️ THIS IS WHERE THE EFFECT LANDS. The owner-ledger debit and the guest's refund email both hang
     * off the `payment.refunded` domain event, which {@see refundPayment()} withholds while a refund is in
     * flight. Emitting it here — and only on success — is what makes "the owner is debited when the money
     * LEAVES, not when it is queued" true.
     *
     * ⚠️ A refund that ultimately FAILS posts NOTHING and RELEASES its headroom: the row leaves `pending`,
     * so {@see refundedCommittedMinor()} stops counting it and the amount becomes refundable again. The
     * bound must never permanently consume money that never left.
     *
     * ⚠️ IDEMPOTENT BY STATUS: only a row still `pending` transitions, so a redelivered settlement event is
     * a no-op and can never debit an owner twice. The row lock makes the check-and-set atomic.
     */
    public function settleRefund(Payment $refund, string $toStatus, ?bool $livemode = null): Payment
    {
        if (! in_array($toStatus, ['succeeded', 'failed'], true)) {
            throw new InvalidArgumentException('A refund settles to succeeded or failed.');
        }

        $settled = DB::transaction(function () use ($refund, $toStatus, $livemode): ?Payment {
            $locked = Payment::query()->lockForUpdate()->findOrFail($refund->getKey());

            // ⚠️ Re-checked INSIDE the lock — the caller's copy is a snapshot, and a redelivered webhook
            //    racing the first one must resolve to exactly one transition.
            if ((string) $locked->type !== 'refund' || (string) $locked->status !== 'pending') {
                return null;
            }

            $locked->forceFill(['status' => $toStatus, 'processed_at' => now()]
                + ($livemode !== null && $locked->livemode === null ? ['livemode' => $livemode] : []))->save();

            $folio = $locked->folio;
            if ($folio !== null) {
                $this->recompute($folio);
            }

            return $locked;
        });

        if ($settled === null) {
            // ⚠️ Same resume as `settlePayment()`, and the same restraint: nothing is re-settled and no
            //    money moves. Only a refund that genuinely reached THIS status is eligible — a row that
            //    was never a pending refund is left completely alone.
            $current = $refund->fresh();

            if ($current !== null && (string) $current->type === 'refund' && (string) $current->status === $toStatus) {
                $this->resumeSettlementTrail($current, 'payment.refund_settled', ['status' => 'pending'], ['status' => $toStatus]);
            }

            return $refund;   // already settled, or not a pending refund — nothing to do, nothing posted.
        }

        if ($toStatus === 'succeeded') {
            // ⚠️ The borne-by choice was captured at refund time and rides on the ROW, so it survives the
            //    wait untouched — the listener reads `refund_borne_by` from this same payment.
            // `metadata` is an `array` cast; read it through `data_get()` so a malformed/absent payload
            // degrades to null rather than throwing while settling real money.
            $this->domainEvents->record('payment.refunded', $settled, [
                'refunds_payment_id' => data_get($settled->metadata, 'refunds_payment_id'),
                'amount_minor' => (int) $settled->amount_minor,
            ]);
        }

        $this->audit->record('payment.refund_settled', $settled, ['status' => 'pending'], ['status' => $toStatus]);

        return $settled;
    }

    /**
     * Write a settlement's AUDIT ROW after the fact, when the settlement committed but the trail did not.
     *
     * ⚠️⚠️ THE DEFECT THIS EXISTS FOR. `settlePayment()`/`settleRefund()` commit the money in a transaction
     * and record the trail AFTER it. If a post-commit recorder throws, the money is right and the trail is
     * holed — and the retry Stripe sends hits the handlers' own status guard and returns early, so the row
     * stays permanently missing. **The protection and the hole were the same line.**
     *
     * ⚠️⚠️ THE GUARD IS NOT WEAKENED, AND THAT IS THE WHOLE POINT. Making a settled payment re-settleable
     * to recover a record would trade a missing row for a DOUBLE WRITE ON A MONEY PATH, which is a worse
     * trade. Nothing here re-settles anything: the recording became retryable INDEPENDENTLY of the
     * settlement, which is the only shape that recovers the trail without touching the money.
     *
     * ⚠️ MONEY FIRST, TRAIL SECOND. The recorders were NOT moved inside the transaction, deliberately: a
     * failing recorder would then roll the settlement back, and a payment the gateway TOOK which this
     * product refused to record at all is worse than one recorded without its audit row.
     *
     * ⚠️ THE AUDIT ROW ONLY — the domain event is deliberately NOT re-recorded. It is written BEFORE its
     * listeners run, so on a post-commit failure it already exists; re-recording would create a second
     * `domain_events` row and re-fire the guest's refund email. An audit row has no such consequence: it
     * is inert evidence with no listeners.
     *
     * ⚠️ IDEMPOTENT BY QUERY, not by a unique index — `audit_events` has none, being an append-only
     * evidence table that is deliberately never pruned. A row that already exists is left alone, so a
     * normal settlement still records exactly once.
     */
    private function resumeSettlementTrail(Payment $payment, string $action, ?array $before, array $after): void
    {
        $exists = AuditEvent::query()
            ->where('action', $action)
            ->where('subject_type', $payment->getMorphClass())
            ->where('subject_id', (string) $payment->getKey())
            ->exists();

        if ($exists) {
            return;
        }

        $this->audit->record($action, $payment, $before, $after);
    }

    public function refundedSettledMinor(Payment $original): int
    {
        return $this->refundedMinor($original, ['succeeded']);
    }

    /**
     * ⚠️⚠️ WHAT BOUNDS FURTHER REFUNDS — Σ SUCCEEDED **AND PENDING**, in the payment's currency.
     *
     * ⚠️ THIS IS THE ONE LINE THE WHOLE FIX TURNS ON. A bound that counts only what has SUCCEEDED can be
     * bypassed by anything that has not succeeded YET. A €500 refund sitting `pending` at Stripe is money
     * that WILL leave; if it does not consume headroom, the desk is offered the full €500 again and a
     * second refund is permitted on top of it.
     *
     * ⚠️ IT ALSO CLOSES THE TWO-HALVES HOLE AS A CONSEQUENCE, NOT AS A SEPARATE PATCH: the gateway
     * idempotency key is `folio_refund:{payment}:{alreadyRefunded}:{amount}`, and `alreadyRefunded` is this
     * figure — so once a pending refund counts, a second attempt derives a DIFFERENT key and is bounded on
     * its way in rather than replaying.
     *
     * ⚠️ DO NOT USE THIS TO TELL A GUEST WHAT THEY HAVE BEEN REFUNDED — that is
     * {@see refundedSettledMinor()}. Two questions, two answers; the money has not left yet.
     */
    public function refundedCommittedMinor(Payment $original): int
    {
        return $this->refundedMinor($original, ['succeeded', 'pending']);
    }

    /** @param  list<string>  $statuses */
    private function refundedMinor(Payment $original, array $statuses): int
    {
        return (int) Payment::withTrashed()
            ->where('type', 'refund')
            ->whereIn('status', $statuses)
            ->where('metadata->refunds_payment_id', $original->getKey())
            ->where('currency', (string) $original->currency)
            ->sum('amount_minor');
    }

    /** Stripe's refund status → the folio row's status. `requires_action` is in flight, not a failure. */
    private static function refundRowStatus(GatewayResult $result): string
    {
        if ($result->successful) {
            return 'succeeded';
        }

        return in_array($result->status, ['pending', 'requires_action'], true) ? 'pending' : 'failed';
    }

    /**
     * What may still be refunded against a payment: original − Σ prior succeeded refunds (never negative).
     *
     * Read-only. Exposed so a surface can hide the affordance and cap its input rather than inviting a
     * rejection — the operator screen offering "Refund" on an already fully-refunded payment is exactly
     * what made the over-refund reachable.
     */
    public function remainingRefundableMinor(Payment $original): int
    {
        if (! in_array($original->type, ['payment', 'capture'], true)) {
            return 0;
        }

        return max(0, (int) $original->amount_minor - $this->refundedCommittedMinor($original));
    }

    /**
     * Place a deposit/incidental hold (authorize). Not counted as paid until captured.
     */
    public function authorizeDeposit(Folio $folio, Money $amount, ?string $cardToken = null, ?DateTimeInterface $expiresAt = null): PaymentAuthorization
    {
        // A hold needs a real payment method: live Stripe returns `requires_payment_method` (and the
        // later capture then fails) if you authorize with no card. Token-only — $cardToken is a
        // Stripe payment_method id, never a PAN. (Reconciled against live Stripe test-mode 2026-07-03.)
        $result = $this->gateway->authorize($amount, $cardToken, []);

        $authorization = DB::transaction(function () use ($folio, $amount, $cardToken, $result, $expiresAt): PaymentAuthorization {
            $payment = Payment::create([
                'folio_id' => $folio->getKey(),
                'reservation_id' => $folio->reservation_id,
                'gateway' => $this->gateway->name(),
                'gateway_ref' => $result->reference,
                'livemode' => $result->livemode,
                'type' => 'deposit_hold',
                'amount' => $amount,
                'currency' => $amount->currency(),
                'status' => 'authorized',
                'card_token' => $cardToken,
                'processed_at' => now(),
            ]);

            $authorization = PaymentAuthorization::create([
                'payment_id' => $payment->getKey(),
                'authorized' => $amount,
                'currency' => $amount->currency(),
                'expires_at' => $expiresAt,
                'status' => 'authorized',
                'livemode' => $result->livemode,
            ]);

            $this->recompute($folio); // deposit_hold is excluded from paid

            return $authorization;
        });

        $this->domainEvents->record('deposit.authorized', $authorization, ['amount_minor' => $amount->amountMinor()]);

        return $authorization;
    }

    /**
     * Capture a prior hold (fully or partially). Capture is a NEW Payment (counts as paid); the
     * original deposit_hold Payment is not mutated. The authorization row advances its lifecycle.
     */
    public function captureDeposit(PaymentAuthorization $authorization, ?Money $amount = null): Payment
    {
        $holdPayment = $authorization->payment;
        if ($holdPayment === null) {
            throw new InvalidArgumentException('Authorization has no underlying hold payment.');
        }

        $captureAmount = $amount ?? new Money((int) $authorization->authorized_minor, (string) $authorization->currency);
        $result = $this->gateway->capture((string) $holdPayment->gateway_ref, $captureAmount);
        $folio = $holdPayment->folio;

        $capture = DB::transaction(function () use ($authorization, $holdPayment, $captureAmount, $result, $folio): Payment {
            $capture = Payment::create([
                'folio_id' => $holdPayment->folio_id,
                'reservation_id' => $holdPayment->reservation_id,
                'gateway' => $this->gateway->name(),
                'gateway_ref' => $result->reference,
                'livemode' => $result->livemode ?? $holdPayment->livemode,
                'type' => 'capture',
                'amount' => $captureAmount,
                'currency' => $captureAmount->currency(),
                'status' => 'captured',
                'metadata' => ['authorization_id' => $authorization->getKey()],
                'processed_at' => now(),
            ]);

            $authorization->update(['captured' => $captureAmount, 'status' => 'captured']);

            if ($folio !== null) {
                $this->recompute($folio);
            }

            return $capture;
        });

        $this->domainEvents->record('deposit.captured', $capture, ['amount_minor' => $captureAmount->amountMinor()]);

        return $capture;
    }

    /**
     * Re-derive folio total/paid/balance from the append-only ledger, then sync the reservation.
     * total = SUM(folio_items.amount_minor); paid = payments/captures − refunds (deposit holds
     * are NOT paid until captured). Nothing here mutates a posted ledger row.
     */
    private function recompute(Folio $folio): void
    {
        $total = (int) $folio->items()->sum('amount_minor');

        $received = (int) $folio->payments()
            ->whereIn('type', ['payment', 'capture'])
            ->whereIn('status', ['succeeded', 'captured'])
            ->sum('amount_minor');
        $refunded = (int) $folio->payments()
            ->where('type', 'refund')
            ->where('status', 'succeeded')
            ->sum('amount_minor');

        $paid = $received - $refunded;
        $balance = $total - $paid;

        $folio->update(['total_minor' => $total, 'paid_minor' => $paid, 'balance_minor' => $balance]);

        $reservation = $folio->reservation;
        if ($reservation !== null) {
            $reservation->update([
                'total_minor' => $total,
                'balance_minor' => $balance,
                'payment_status' => $this->paymentStatus($total, $balance, $received, $refunded),
            ]);
        }
    }

    /**
     * The reservation-level `payment_status`, derived from the ledger. `'refunded'` means money came IN and has
     * been (net) fully refunded back OUT — a distinct, truthful state from `'unpaid'` (nothing ever received): a
     * fully-refunded stay must not read as if the guest never paid. (Phase 0.2 made this real; `'charged_back'`
     * was dropped from the enum — no chargeback/dispute flow exists.)
     */
    private function paymentStatus(int $total, int $balance, int $received, int $refunded): string
    {
        $paid = $received - $refunded;

        return match (true) {
            $total > 0 && $balance <= 0 => 'paid',           // net settled (or overpaid)
            $paid > 0 => 'partial',                          // some net money still in, not fully settled
            $received > 0 && $refunded > 0 => 'refunded',    // received, then refunded (net) back out
            default => 'unpaid',                             // nothing ever received
        };
    }

    /**
     * Map a gateway result to a `payments.status`. A successful result settles immediately;
     * `requires_action` (SCA/3DS) / `pending` / `processing` become a PENDING payment (not counted
     * as paid until a webhook confirms it); anything else is a failure. Manual/synchronous gateways
     * always return successful → `succeeded`, so Phase 1 behavior is unchanged.
     */
    private function mapResultStatus(GatewayResult $result): string
    {
        return match (true) {
            $result->successful => 'succeeded',
            in_array($result->status, ['requires_action', 'pending', 'processing'], true) => 'pending',
            default => 'failed',
        };
    }
}
