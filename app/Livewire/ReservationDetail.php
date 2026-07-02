<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * CONTRACT STUB — Reservation detail (unstyled). Read-only.
 *
 * The reservation id is a plain string prop; the model is resolved in render() via a
 * TENANT-SCOPED findOrFail. This runs AFTER the SetTenantForWebRequest middleware has set the
 * active org — so another org's reservation 404s. (Deliberately NOT a typed model property /
 * route-model binding, which Livewire would resolve during SubstituteBindings, before the
 * tenant middleware.)
 *
 * The view receives:
 *   reservation: array{ id, reference, source, status, check_in, check_out, adults, children,
 *                       currency, payment_status, guest: string|null }
 *   units: list<array{ id, room_type, unit: string|null, check_in, check_out, status, price_minor: int }>
 *   folio: array{ id, status, total_minor: int, paid_minor: int, balance_minor: int,
 *                 items: list<array{ type, description, amount_minor: int }>,
 *                 payments: list<array{ type, status, amount_minor: int }> }|null
 */
#[Layout('components.layouts.app')]
class ReservationDetail extends Component
{
    public string $reservationId;

    public function mount(string $reservation): void
    {
        $this->reservationId = $reservation;
    }

    public function render(): View
    {
        $reservation = Reservation::query()
            ->with(['leadGuest', 'reservationUnits.roomType', 'reservationUnits.unit'])
            ->findOrFail($this->reservationId);

        $guest = $reservation->leadGuest;

        $units = $reservation->reservationUnits->map(fn (ReservationUnit $line): array => [
            'id' => $line->id,
            'room_type' => $line->roomType->name,
            'unit' => $line->unit?->code,
            'check_in' => CarbonImmutable::parse($line->check_in)->toDateString(),
            'check_out' => CarbonImmutable::parse($line->check_out)->toDateString(),
            'status' => $line->status,
            'price_minor' => (int) $line->price_minor,
        ])->all();

        $folioModel = Folio::query()->where('reservation_id', $reservation->id)->with(['items', 'payments'])->first();

        return view('livewire.reservation-detail', [
            'reservation' => [
                'id' => $reservation->id,
                'reference' => $reservation->reference,
                'source' => $reservation->source,
                'status' => $reservation->status,
                'check_in' => CarbonImmutable::parse($reservation->check_in)->toDateString(),
                'check_out' => CarbonImmutable::parse($reservation->check_out)->toDateString(),
                'adults' => (int) $reservation->adults,
                'children' => (int) $reservation->children,
                'currency' => $reservation->currency,
                'payment_status' => $reservation->payment_status,
                'guest' => $guest !== null ? trim(($guest->first_name ?? '').' '.($guest->last_name ?? '')) : null,
            ],
            'units' => $units,
            'folio' => $folioModel !== null ? [
                'id' => $folioModel->id,
                'status' => $folioModel->status,
                'total_minor' => (int) $folioModel->total_minor,
                'paid_minor' => (int) $folioModel->paid_minor,
                'balance_minor' => (int) $folioModel->balance_minor,
                'items' => $folioModel->items->map(fn (FolioItem $item): array => [
                    'type' => $item->type,
                    'description' => $item->description,
                    'amount_minor' => (int) $item->amount_minor,
                ])->all(),
                'payments' => $folioModel->payments->map(fn (Payment $payment): array => [
                    'type' => $payment->type,
                    'status' => $payment->status,
                    'amount_minor' => (int) $payment->amount_minor,
                ])->all(),
            ] : null,
        ]);
    }
}
