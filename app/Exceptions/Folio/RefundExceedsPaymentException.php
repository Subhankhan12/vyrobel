<?php

declare(strict_types=1);

namespace App\Exceptions\Folio;

use App\Services\FolioService;
use RuntimeException;

/**
 * An expected, clean refund rejection thrown by {@see FolioService::refundPayment()} — the
 * cumulative over-refund guard. Extends RuntimeException so callers can surface it as a flash the same way
 * the maintenance/booking rejections are handled, rather than a 500.
 *
 * THE INVARIANT THIS DEFENDS: the CUMULATIVE total refunded against a payment may EQUAL, but never EXCEED,
 * that payment's original amount.
 *
 * Deliberately distinct from invariant #7 (append-only, correct-by-reversal). #7 was never violated: every
 * refund IS a legitimate new offsetting row and the original is never touched. Append-only guarantees you
 * cannot EDIT a refund — it never bounded the SUM. This is the bound. (See docs/DECISIONS.md 2026-07-16:
 * a €369.00 payment accepted three full refunds = €1,107.00, because the old check compared each refund
 * against the ORIGINAL rather than against what remained.)
 */
class RefundExceedsPaymentException extends RuntimeException
{
    /**
     * The requested refund would push the cumulative total past the original payment.
     *
     * @param  int  $requestedMinor  what this call asked to refund
     * @param  int  $alreadyRefundedMinor  Σ prior succeeded refunds against the payment
     * @param  int  $originalMinor  the payment being reversed
     */
    public static function forPayment(string $paymentId, int $requestedMinor, int $alreadyRefundedMinor, int $originalMinor): self
    {
        $remaining = $originalMinor - $alreadyRefundedMinor;

        return new self(
            "Refund of [{$requestedMinor}] on payment [{$paymentId}] would exceed the original: ".
            "[{$alreadyRefundedMinor}] of [{$originalMinor}] is already refunded, leaving [{$remaining}] refundable."
        );
    }

    /**
     * The refund currency does not match the payment's. Minor units are not comparable across currencies,
     * so neither the bound nor the sum would mean anything — reject rather than mis-sum.
     */
    public static function currencyMismatch(string $paymentId, string $refundCurrency, string $paymentCurrency): self
    {
        return new self(
            "Refund currency [{$refundCurrency}] does not match payment [{$paymentId}] currency [{$paymentCurrency}]; ".
            'a refund must be issued in the currency it was paid in.'
        );
    }
}
