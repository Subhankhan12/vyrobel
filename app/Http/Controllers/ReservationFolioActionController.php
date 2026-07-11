<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Reservation;
use App\Services\FolioService;
use App\Support\Money\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Reservation folio money actions — REAL, append-only, driven from the styled Reservation Detail
 * (design pass · Operator Screen 4) via the proven POST → action → redirect (PRG) pattern. A normal
 * POST re-runs the web middleware (SetTenantForWebRequest sets context), so every mutation is
 * tenant-scoped; `{reservation}` is a plain STRING param resolved via the tenant-scoped global scope
 * AFTER the middleware (never RMB) → a cross-org id 404s.
 *
 * Both actions delegate to {@see FolioService} — the single append-only ledger writer. A payment is a
 * NEW `payment` row; a refund is a NEW offsetting `refund` row (the original is NEVER edited). No
 * ledger row is ever mutated or deleted here. Money is integer minor units end-to-end.
 */
class ReservationFolioActionController extends Controller
{
    /** Record a payment against the reservation's folio (through the configured gateway; manual = offline). */
    public function recordPayment(Request $request, FolioService $folios, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);   // tenant-scoped → cross-org 404

        $minor = $this->parseAmountMinor($request->input('amount'));
        if ($minor <= 0) {
            return $this->back($model->id, 'Enter a payment amount greater than zero.');
        }

        $folio = $folios->openFolio($model);
        $folios->recordPayment($folio, new Money($minor, (string) $model->currency));

        return $this->back($model->id, 'Payment recorded.');
    }

    /** Refund a specific prior payment in FULL — a NEW offsetting entry; the original is untouched. */
    public function refund(Request $request, FolioService $folios, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);

        // The payment must belong to THIS reservation (and is tenant-scoped itself) — cross-org 404.
        $payment = Payment::query()
            ->where('reservation_id', $model->id)
            ->findOrFail((string) $request->input('payment'));

        if (! in_array($payment->status, ['succeeded', 'captured'], true) || ! in_array($payment->type, ['payment', 'capture'], true)) {
            return $this->back($model->id, 'Only a settled payment can be refunded.');
        }

        $folios->refundPayment($payment, new Money((int) $payment->amount_minor, (string) $payment->currency));

        return $this->back($model->id, 'Refund issued.');
    }

    /** Parse an operator-entered major-unit amount (e.g. "50.00") to integer minor units. */
    private function parseAmountMinor(mixed $raw): int
    {
        if (! is_string($raw) && ! is_numeric($raw)) {
            return 0;
        }

        $value = (float) $raw;

        return $value > 0 ? (int) round($value * 100) : 0;
    }

    private function back(string $reservationId, string $notice): RedirectResponse
    {
        return redirect()->to(route('app.reservations.show', $reservationId))->with('folio_notice', $notice);
    }
}
