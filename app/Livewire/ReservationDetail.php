<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Folio;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Reservation Detail — one reservation + its folio (design pass · Operator Screen 4). Styled
 * from the catalogued "Operator Reservation Detail" design onto the SAME operator design system as
 * Screens 1–3 (operator layout, <x-operator.shell>, FormatsOperatorData — reused, never redefined).
 * Presentation + the real folio money actions; NO change to FolioService, AssignmentService, the
 * append-only ledger, schema, or any guarantee — this component only READS.
 *
 * RMB rule: `{reservation}` is a plain string prop resolved in render() via a TENANT-SCOPED
 * findOrFail (after SetTenantForWebRequest) — a cross-org id 404s. Never a route-model-bound prop.
 *
 * THE FOLIO IS RENDERED AS THE REAL APPEND-ONLY LEDGER IT IS: every folio_item and every payment is
 * its own line, chronological — charges positive, payments negative, refunds their OWN offsetting
 * lines (a prior line is NEVER edited or struck; correct-by-reversal). Totals are derived from the
 * SAME rows and reconcile to the folio's cached figures to the cent. No line the ledger doesn't
 * contain is ever rendered (the owner-statement fake-fee precedent).
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — guest name, stay summary, units, reservation status (real enum), and the folio ledger.
 *                Record payment + full refund are REAL and persist (append-only, via FolioService).
 *   • DERIVED  — charges / paid / balance totals, computed from the real ledger rows only.
 *   • DEFERRED — add charge, partial refund, void, capture-hold, modify / move room / cancel: shown
 *                real-looking but flagged "not yet available" — never a faked money move (move-room
 *                goes through the guarded AssignmentService; deferred like the rack drag).
 *   • NO BACKEND — export / print: disabled "Soon". A deposit/pre-auth line shows ONLY when a real
 *                deposit_hold authorization exists — never a fabricated pre-auth.
 *   • Guest PII — name only; encrypted email/phone never rendered.
 */
#[Layout('components.layouts.operator')]
class ReservationDetail extends Component
{
    use FormatsOperatorData;

    public string $reservationId;

    public function mount(string $reservation): void
    {
        $this->reservationId = $reservation;
    }

    public function render(): View
    {
        $reservation = Reservation::query()
            ->with(['leadGuest', 'reservationUnits.roomType', 'reservationUnits.unit'])
            ->findOrFail($this->reservationId);   // tenant-scoped → cross-org 404

        $guest = $reservation->leadGuest;
        $name = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : '';
        $status = (string) $reservation->status;
        $meta = $this->reservationStatusMeta($status);
        $currency = (string) ($reservation->currency ?? 'EUR');

        $checkIn = CarbonImmutable::parse($reservation->check_in)->startOfDay();
        $checkOut = CarbonImmutable::parse($reservation->check_out)->startOfDay();

        $folioModel = Folio::query()
            ->where('reservation_id', $reservation->id)
            ->with(['items', 'payments'])
            ->first();

        $folio = $folioModel !== null ? $this->buildFolio($folioModel, $currency) : null;

        return view('livewire.reservation-detail', [
            'viewer' => $this->viewer(),
            'reservation' => [
                'id' => $reservation->id,
                'reference' => (string) $reservation->reference,
                'source' => $this->sourceLabel((string) $reservation->source),
                'status' => $status,
                'status_label' => $meta['label'],
                'tone' => $meta['tone'],
                'check_in' => $checkIn->format('j M Y'),
                'check_out' => $checkOut->format('j M Y'),
                'nights' => (int) $checkIn->diffInDays($checkOut),
                'guests' => (int) $reservation->adults + (int) $reservation->children,
                'currency' => $currency,
                'guest' => $name !== '' ? $name : null,
            ],
            'units' => $reservation->reservationUnits->map(fn (ReservationUnit $line): array => [
                'room_type' => (string) $line->roomType->name,
                'unit' => $line->unit?->code,
                'check_in' => CarbonImmutable::parse($line->check_in)->format('j M'),
                'check_out' => CarbonImmutable::parse($line->check_out)->format('j M'),
                'status' => (string) $line->status,
                'price' => $this->money((int) $line->price_minor, $currency),
            ])->all(),
            'folio' => $folio,
        ]);
    }

    /**
     * Assemble the append-only ledger + reconciled totals from the real folio rows.
     *
     * @return array<string, mixed>
     */
    private function buildFolio(Folio $folioModel, string $currency): array
    {
        /** @var list<array{ts: int, entry: array<string, mixed>}> $entries */
        $entries = [];

        $chargesMinor = 0;
        foreach ($folioModel->items as $item) {
            $amount = (int) $item->amount_minor;
            $chargesMinor += $amount;
            $entries[] = [
                'ts' => CarbonImmutable::parse($item->posted_at ?? $item->created_at)->getTimestamp(),
                'entry' => [
                    'label' => $this->folioItemLabel((string) $item->type),
                    'desc' => (string) $item->description,
                    'date' => CarbonImmutable::parse($item->service_date ?? $item->posted_at ?? $item->created_at)->format('j M'),
                    'amount' => ($amount < 0 ? '' : '+').$this->money($amount, $currency),
                    'tone' => $amount < 0 ? 'refund' : 'charge',
                    'flag' => null,
                    'refundable' => false,
                    'payment_id' => null,
                ],
            ];
        }

        $receivedMinor = 0;
        $refundedMinor = 0;
        foreach ($folioModel->payments as $payment) {
            $entries[] = ['ts' => CarbonImmutable::parse($payment->processed_at ?? $payment->created_at)->getTimestamp(), 'entry' => $this->paymentEntry($payment, $currency)];

            $amount = (int) $payment->amount_minor;
            $type = (string) $payment->type;
            $pstatus = (string) $payment->status;
            if (in_array($type, ['payment', 'capture'], true) && in_array($pstatus, ['succeeded', 'captured'], true)) {
                $receivedMinor += $amount;
            } elseif ($type === 'refund' && $pstatus === 'succeeded') {
                $refundedMinor += $amount;
            }
        }

        usort($entries, fn (array $a, array $b): int => $a['ts'] <=> $b['ts']);

        $paidMinor = $receivedMinor - $refundedMinor;
        $balanceMinor = $chargesMinor - $paidMinor;

        return [
            'ledger' => array_map(fn (array $e): array => $e['entry'], $entries),
            'charges' => $this->money($chargesMinor, $currency),
            'paid' => $this->money($paidMinor, $currency),
            'balance' => $this->money($balanceMinor, $currency),
            'balance_minor' => $balanceMinor,
            'has_balance' => $balanceMinor > 0,
            // Cross-check against the folio's own cached figures — the presentation reconciles to the
            // cent with FolioService's ledger recompute (they are computed from the same rows).
            'reconciles' => $balanceMinor === (int) $folioModel->balance_minor,
        ];
    }

    /**
     * One payment → a ledger line. Payments negative; refunds their own positive offsetting line;
     * holds/pending/failed shown honestly with no balance effect.
     *
     * @return array<string, mixed>
     */
    private function paymentEntry(Payment $payment, string $currency): array
    {
        $amount = (int) $payment->amount_minor;
        $type = (string) $payment->type;
        $status = (string) $payment->status;
        $date = CarbonImmutable::parse($payment->processed_at ?? $payment->created_at)->format('j M');

        // Settled inbound money — reduces the balance; refundable in full.
        if (in_array($type, ['payment', 'capture'], true) && in_array($status, ['succeeded', 'captured'], true)) {
            return [
                'label' => $type === 'capture' ? 'Capture' : 'Payment',
                'desc' => 'Received',
                'date' => $date,
                'amount' => '−'.$this->money($amount, $currency),
                'tone' => 'paid',
                'flag' => null,
                'refundable' => true,
                'payment_id' => $payment->id,
            ];
        }

        // Refund — its OWN offsetting line; the original payment line is untouched (correct-by-reversal).
        if ($type === 'refund' && $status === 'succeeded') {
            return ['label' => 'Refund', 'desc' => 'Reverses a payment', 'date' => $date, 'amount' => '+'.$this->money($amount, $currency), 'tone' => 'refund', 'flag' => null, 'refundable' => false, 'payment_id' => null];
        }

        // A real deposit/pre-authorization hold (shown only because the row genuinely exists).
        if ($type === 'deposit_hold' && $status === 'authorized') {
            return ['label' => 'Deposit hold', 'desc' => 'Pre-authorized', 'date' => $date, 'amount' => $this->money($amount, $currency), 'tone' => 'hold', 'flag' => 'Hold', 'refundable' => false, 'payment_id' => null];
        }

        // Pending (3DS/processing) or failed/voided — real rows, no balance effect, flagged honestly.
        $flag = in_array($status, ['pending'], true) ? 'Pending' : ucfirst($status);

        return ['label' => ucfirst($type === 'deposit_hold' ? 'Deposit hold' : $type), 'desc' => 'Not counted in the balance', 'date' => $date, 'amount' => $this->money($amount, $currency), 'tone' => 'muted', 'flag' => $flag, 'refundable' => false, 'payment_id' => null];
    }
}
