<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Livewire\Owner\OwnerBookingDetail;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Services\PdfService;
use App\Support\Exports\ExportAudit;
use App\Support\Pdf\OwnerBookingConfirmationDocument;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Owner per-booking CONFIRMATION PDF download (#78) — the wireframe's "Download confirmation" action.
 *
 * ⚠️ THE SAME OWNER GUARD AS THE PAGE, DELIBERATELY DUPLICATED. Reservations carry no owner_id (owner A and
 * owner B share the operator's ORG), so a `{booking}` id is NOT self-scoping. This controller applies the
 * IDENTICAL check as {@see OwnerBookingDetail}: resolve the owner's OWN units from the
 * owner-scoped {@see OwnerPortalService::units()}, then 404 unless the reservation touches one of them — never
 * trusting the request id. Without it, an owner could download another owner's booking by guessing an id.
 *
 * 🔒 EXPORTS gate — the route sits behind `EnsureOwnerCapability:exports` (co-owner + accountant; a view-only
 * sub-user is refused, like every other owner PDF). The download is watermarked + audited (who / when) via the
 * shared {@see ExportAudit}. Guest NAME only (#28) — the document never renders contact.
 */
class OwnerBookingConfirmationPdfController extends Controller
{
    public function download(Request $request, string $booking, PdfService $pdf): Response
    {
        $units = app(OwnerPortalService::class)->units();   // owner-scoped, fail-closed when empty
        /** @var array<string, Unit> $unitById */
        $unitById = $units->keyBy(fn (Unit $u): string => (string) $u->id)->all();
        $ownerUnitIds = array_map('strval', $units->pluck('id')->all());

        $reservation = Reservation::query()->with('leadGuest')->findOrFail($booking);

        $ownedLines = $ownerUnitIds === [] ? collect() : ReservationUnit::query()
            ->ownerStays()                                      // D-OWNERSINCE-WINDOWS: a stay that began before the owner's day is not theirs → 404
            ->where('reservation_id', $reservation->id)
            ->whereIn('unit_id', $ownerUnitIds)
            ->where('status', 'active')
            ->get(['unit_id']);

        abort_if($ownedLines->isEmpty(), 404);   // ← the owner boundary

        /** @var list<array{id: string, label: string}> $ownedUnits */
        $ownedUnits = $ownedLines
            ->map(fn (ReservationUnit $l): string => (string) $l->unit_id)
            ->unique()
            ->filter(fn (string $id): bool => isset($unitById[$id]))
            ->map(fn (string $id): array => [
                'id' => $id,
                'label' => (string) ($unitById[$id]->name ?? $unitById[$id]->code),
            ])
            ->values()
            ->all();

        $filename = 'vyrobel-booking-'.$reservation->reference.'.pdf';

        $watermark = app(ExportAudit::class)->stamp($request, 'booking_confirmation_pdf', 'Booking '.$reservation->reference, (string) $reservation->id);

        return $pdf->download('pdfs.owner-booking-confirmation', OwnerBookingConfirmationDocument::data($reservation, $ownedUnits), $filename, $watermark);
    }
}
