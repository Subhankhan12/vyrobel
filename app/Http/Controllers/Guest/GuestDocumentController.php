<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\Folio;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\PdfService;
use App\Support\I18n\Locales;
use App\Support\Pdf\FolioDocument;
use App\Support\Pdf\GuestInvoiceDocument;
use App\Support\Pdf\GuestReversalDocument;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guest money documents (Phase 4c · Sub-gate 1) — the guest's downloadable PDFs, each rendered from the REAL
 * append-only folio ledger (via PdfService + the shared document builders) and reconciling to the on-screen
 * figures. Read-only; invents nothing.
 *
 * 🔒 ISOLATION: EVERY document resolves the booking THROUGH `$this->guest()->reservations()` — a cross-guest id
 * → 404 (a guest can export ONLY their own booking's receipt/invoice/statement). Refund Receipt + Credit Note
 * additionally require a REAL refund on the booking (else 404 — no fabricated reversal document).
 */
class GuestDocumentController extends Controller
{
    use ResolvesGuest;

    public function __construct(private readonly PdfService $pdf) {}

    public function folioReceipt(string $reservation): Response
    {
        $model = $this->owned($reservation);

        return $this->pdf->download('pdfs.folio', FolioDocument::data($model), $this->filename('receipt', $model));
    }

    public function statement(string $reservation): Response
    {
        $model = $this->owned($reservation);

        return $this->pdf->download('pdfs.guest-statement', FolioDocument::data($model), $this->filename('statement', $model));
    }

    public function vatInvoice(string $reservation): Response
    {
        $model = $this->owned($reservation);

        // I18N-01 (PLAN 5.5) — a tax document is issued in the ORGANISATION's language, not the reader's, until the lawyer rules.
        return $this->pdf->download('pdfs.guest-vat-invoice', GuestInvoiceDocument::data($model), $this->filename('invoice', $model), null, Locales::organizationDefault((string) $model->organization_id));
    }

    public function refundReceipt(string $reservation): Response
    {
        $model = $this->ownedWithRefund($reservation);

        return $this->pdf->download('pdfs.guest-refund-receipt', GuestReversalDocument::data($model), $this->filename('refund', $model));
    }

    public function creditNote(string $reservation): Response
    {
        $model = $this->ownedWithRefund($reservation);

        // I18N-01 (PLAN 5.5) — a tax document is issued in the ORGANISATION's language, as the invoice it corrects.
        return $this->pdf->download('pdfs.guest-credit-note', GuestReversalDocument::data($model), $this->filename('credit-note', $model), null, Locales::organizationDefault((string) $model->organization_id));
    }

    /** The authed guest's OWN reservation, or 404 — resolved through their own relation (never a request id). */
    private function owned(string $reservation): Reservation
    {
        return $this->guest()->reservations()->with('property')->findOrFail($reservation);
    }

    /** Owned AND carrying a real refund — else 404 (no reversal document without a real reversal). */
    private function ownedWithRefund(string $reservation): Reservation
    {
        $model = $this->owned($reservation);

        $folio = Folio::query()->where('reservation_id', $model->getKey())->with('payments')->first();
        $hasRefund = $folio !== null && $folio->payments->contains(
            fn (Payment $p): bool => (string) $p->type === 'refund' && (string) $p->status === 'succeeded'
        );
        abort_unless($hasRefund, 404);

        return $model;
    }

    private function filename(string $kind, Reservation $reservation): string
    {
        return 'vyrobel-'.$kind.'-'.$reservation->reference.'.pdf';
    }
}
