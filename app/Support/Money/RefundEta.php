<?php

declare(strict_types=1);

namespace App\Support\Money;

use App\Support\Folio\FolioTotals;

/**
 * The ONE statement of how long a refund takes to land (wireframe-parity WAVE 2 · #8).
 *
 * Two mailables carried two different hardcoded literals for the SAME money movement — the cancellation
 * email promised "3–5 business days" and the refund-confirmation email said "5–10 business days" — so a
 * guest whose booking was cancelled and refunded received both, about one refund. Two literals for one
 * concept is the drift rule that {@see MoneyFormatter} and {@see FolioTotals} exist to
 * prevent; this is the same rule applied to a sentence.
 *
 * 🪤 HONEST, NOT PRECISE. The real time depends on the guest's bank and card scheme and we cannot observe
 * it, so this is deliberately a RANGE and is worded as one ("usually"), never a promise or a date. The
 * conservative end is used: under-promising a refund is recoverable, over-promising is a complaint.
 */
final class RefundEta
{
    /** The window itself, in English — the only place the figure exists; {@see window()} is what a person reads. */
    public const WINDOW = '5–10 business days';

    /** The window in the reader's language (the same figure as {@see WINDOW}). */
    public static function window(): string
    {
        return __('php/support/money/refund_eta.window');
    }

    /** A full sentence for email/UI copy, so callers never re-word the promise around it. */
    public static function sentence(): string
    {
        return __('php/support/money/refund_eta.sentence', ['window' => self::window()]);
    }
}
