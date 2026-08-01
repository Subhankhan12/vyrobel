<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Folio\RefundExceedsPaymentException;
use App\Models\AddOn;
use App\Models\Fee;
use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\Payment;
use App\Models\PaymentAuthorization;
use App\Models\Reservation;
use App\Models\Tax;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\GatewayResult;
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

        $result = $this->gateway->charge($amount, $cardToken, $metadata);

        $status = $this->mapResultStatus($result);
        // Carry the SCA/3DS next-step secret so the frontend can complete authentication. Never
        // any card data — only Stripe's client_secret for the pending intent.
        $storedMetadata = $result->clientSecret !== null
            ? array_merge($metadata, ['client_secret' => $result->clientSecret])
            : $metadata;

        try {
            $payment = DB::transaction(function () use ($folio, $amount, $cardToken, $storedMetadata, $result, $status, $idempotencyKey): Payment {
                $payment = Payment::create([
                    'folio_id' => $folio->getKey(),
                    'reservation_id' => $folio->reservation_id,
                    'gateway' => $this->gateway->name(),
                    'gateway_ref' => $result->reference,
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
    public function recordCheckoutIntent(Folio $folio, Money $amount, string $paymentIntentId, array $metadata = []): Payment
    {
        $payment = Payment::query()->firstOrCreate(
            ['gateway_ref' => $paymentIntentId],
            [
                'folio_id' => $folio->getKey(),
                'reservation_id' => $folio->reservation_id,
                'gateway' => 'stripe',
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
    public function settlePayment(Payment $payment, string $toStatus): Payment
    {
        if (! in_array($toStatus, ['succeeded', 'failed'], true)) {
            throw new InvalidArgumentException("settlePayment expects succeeded|failed, got [{$toStatus}].");
        }

        if ($payment->status !== 'pending') {
            return $payment; // already settled (or never pending) — idempotent no-op
        }

        $folio = $payment->folio;

        DB::transaction(function () use ($payment, $toStatus, $folio): void {
            $payment->update(['status' => $toStatus, 'processed_at' => now()]);

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
     * @throws RefundExceedsPaymentException when the cumulative bound or the currency rule is broken
     */
    public function refundPayment(Payment $original, Money $amount, ?string $reason = null): Payment
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

        // THE CUMULATIVE BOUND. Subsumes the old per-call `amount > original` check: with no prior refunds
        // the remainder IS the original. Runs BEFORE the gateway call below — ordering is load-bearing, or
        // we would move real money and only then reject.
        $alreadyRefunded = $this->refundedTotalMinor($original);
        $remaining = (int) $original->amount_minor - $alreadyRefunded;

        if ($amount->amountMinor() > $remaining) {
            throw RefundExceedsPaymentException::forPayment(
                (string) $original->getKey(), $amount->amountMinor(), $alreadyRefunded, (int) $original->amount_minor,
            );
        }

        $result = $this->gateway->refund((string) $original->gateway_ref, $amount);
        $folio = $original->folio;

        // #100 — the operator's refund reason is recorded on the refund row's metadata (JSON — no schema change),
        // so it surfaces on the folio + reports. It is operator context, not guest PII.
        $metadata = ['refunds_payment_id' => $original->getKey()];
        if ($reason !== null && trim($reason) !== '') {
            $metadata['reason'] = trim($reason);
        }

        $refund = DB::transaction(function () use ($original, $amount, $result, $folio, $metadata): Payment {
            $refund = Payment::create([
                'folio_id' => $original->folio_id,
                'reservation_id' => $original->reservation_id,
                'gateway' => $this->gateway->name(),
                'gateway_ref' => $result->reference,
                'type' => 'refund',
                'amount' => $amount,
                'currency' => $amount->currency(),
                'status' => $result->successful ? 'succeeded' : 'failed',
                'metadata' => $metadata,
                'processed_at' => now(),
            ]);

            if ($folio !== null) {
                $this->recompute($folio);
            }

            return $refund;
        });

        $this->domainEvents->record('payment.refunded', $refund, [
            'refunds_payment_id' => $original->getKey(),
            'amount_minor' => $amount->amountMinor(),
        ]);
        $this->audit->record('payment.refunded', $refund, null, ['refunds_payment_id' => $original->getKey()]);

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
    public function refundedTotalMinor(Payment $original): int
    {
        return (int) Payment::withTrashed()
            ->where('type', 'refund')
            ->where('status', 'succeeded')
            ->where('metadata->refunds_payment_id', $original->getKey())
            ->where('currency', (string) $original->currency)
            ->sum('amount_minor');
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

        return max(0, (int) $original->amount_minor - $this->refundedTotalMinor($original));
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
