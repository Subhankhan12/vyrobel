<?php

declare(strict_types=1);

namespace App\Services;

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
    ): FolioItem {
        $amount = $unitAmount->multiply($quantity);

        $item = DB::transaction(function () use ($folio, $type, $description, $unitAmount, $quantity, $amount, $tax, $reference, $serviceDate): FolioItem {
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
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            $this->recompute($folio);

            return $item;
        });

        $this->domainEvents->record('folio.charge_posted', $item, [
            'type' => $type,
            'amount_minor' => $amount->amountMinor(),
        ]);

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
    public function recordPayment(Folio $folio, Money $amount, ?string $cardToken = null, array $metadata = []): Payment
    {
        $result = $this->gateway->charge($amount, $cardToken, $metadata);

        $status = $this->mapResultStatus($result);
        // Carry the SCA/3DS next-step secret so the frontend can complete authentication. Never
        // any card data — only Stripe's client_secret for the pending intent.
        $storedMetadata = $result->clientSecret !== null
            ? array_merge($metadata, ['client_secret' => $result->clientSecret])
            : $metadata;

        $payment = DB::transaction(function () use ($folio, $amount, $cardToken, $storedMetadata, $result, $status): Payment {
            $payment = Payment::create([
                'folio_id' => $folio->getKey(),
                'reservation_id' => $folio->reservation_id,
                'gateway' => $this->gateway->name(),
                'gateway_ref' => $result->reference,
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

        $this->domainEvents->record('payment.recorded', $payment, [
            'amount_minor' => $amount->amountMinor(),
            'status' => $payment->status,
        ]);
        $this->audit->record('payment.recorded', $payment, null, ['amount_minor' => $amount->amountMinor()]);

        return $payment->refresh();
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
     */
    public function refundPayment(Payment $original, Money $amount): Payment
    {
        if (! in_array($original->type, ['payment', 'capture'], true)) {
            throw new InvalidArgumentException('Only a payment/capture can be refunded.');
        }
        if ($amount->amountMinor() > $original->amount_minor) {
            throw new InvalidArgumentException('Refund exceeds the original payment amount.');
        }

        $result = $this->gateway->refund((string) $original->gateway_ref, $amount);
        $folio = $original->folio;

        $refund = DB::transaction(function () use ($original, $amount, $result, $folio): Payment {
            $refund = Payment::create([
                'folio_id' => $original->folio_id,
                'reservation_id' => $original->reservation_id,
                'gateway' => $this->gateway->name(),
                'gateway_ref' => $result->reference,
                'type' => 'refund',
                'amount' => $amount,
                'currency' => $amount->currency(),
                'status' => $result->successful ? 'succeeded' : 'failed',
                'metadata' => ['refunds_payment_id' => $original->getKey()],
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
     * Place a deposit/incidental hold (authorize). Not counted as paid until captured.
     */
    public function authorizeDeposit(Folio $folio, Money $amount, ?DateTimeInterface $expiresAt = null): PaymentAuthorization
    {
        $result = $this->gateway->authorize($amount, null, []);

        $authorization = DB::transaction(function () use ($folio, $amount, $result, $expiresAt): PaymentAuthorization {
            $payment = Payment::create([
                'folio_id' => $folio->getKey(),
                'reservation_id' => $folio->reservation_id,
                'gateway' => $this->gateway->name(),
                'gateway_ref' => $result->reference,
                'type' => 'deposit_hold',
                'amount' => $amount,
                'currency' => $amount->currency(),
                'status' => 'authorized',
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
                'payment_status' => $this->paymentStatus($total, $paid, $balance),
            ]);
        }
    }

    private function paymentStatus(int $total, int $paid, int $balance): string
    {
        return match (true) {
            $total > 0 && $balance <= 0 => 'paid',
            $paid > 0 => 'partial',
            default => 'unpaid',
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
