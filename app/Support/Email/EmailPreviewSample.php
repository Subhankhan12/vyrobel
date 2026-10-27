<?php

declare(strict_types=1);

namespace App\Support\Email;

use App\Http\Controllers\Operator\EmailPreviewController;
use App\Mail\BookingConfirmationMail;
use App\Mail\CancellationMail;
use App\Mail\PaymentReminderMail;
use App\Mail\PreArrivalMail;
use App\Mail\RefundConfirmationMail;
use App\Mail\ReviewRequestMail;
use App\Mail\VerifyEmailMail;
use App\Models\Folio;
use App\Models\Payment;
use App\Models\Reservation;
use App\Support\Folio\FolioTotals;
use App\Support\Tenancy\TenantContext;
use Illuminate\Mail\Mailable;

/**
 * Builds a customizable guest email's Mailable over the tenant's REAL sample data (Operator Email Templates
 * #86) — the single source the editor's live preview and the Test-send both use, so what an operator previews
 * and what a test delivers are the exact same Mailable (and blade) the real trigger sends. Mirrors the sample
 * selection {@see EmailPreviewController} uses (latest reservation / a real
 * refund), scoped to the 5 customizable templates. Runs inside operator tenant context.
 */
final class EmailPreviewSample
{
    /** The customized Mailable for a template key over real sample data, or null if there's no sample yet. */
    public static function mailable(string $key): ?Mailable
    {
        if (EmailTemplateCatalog::get($key) === null) {
            return null;
        }

        // verify-email carries no reservation — it renders from primitives + the org's stored template.
        if ($key === 'verify-email') {
            return new VerifyEmailMail('Alex Traveller', url('/verify-email/sample-token'), (string) __('php/support/email/email_preview_sample.verify_expires'), null, (string) app(TenantContext::class)->id());
        }

        $reservation = Reservation::query()->with(['leadGuest', 'property'])->latest()->first();
        if ($reservation === null) {
            return null;
        }

        return match ($key) {
            'booking-confirmation' => new BookingConfirmationMail($reservation),
            // Sample a refund figure so the editor preview shows Case A; the real send passes null unless a refund exists.
            'cancellation' => new CancellationMail($reservation, (string) __('php/support/email/email_preview_sample.cancellation_reason'), (int) $reservation->total_minor),
            'pre-arrival' => new PreArrivalMail($reservation),
            'payment-reminder' => new PaymentReminderMail($reservation, self::sampleBalance($reservation)),
            'refund-confirmation' => new RefundConfirmationMail(self::sampleRefund($reservation)),
            'review-request' => new ReviewRequestMail($reservation, url('/')),
            default => null,
        };
    }

    /**
     * The view data the real blade renders from (`content()->with`) — the base the editor overlays the draft
     * onto, so the preview reuses the REAL `emails.{key}` blade + layout and can never drift from the send.
     *
     * @return array<string, mixed>|null
     */
    public static function viewData(string $key): ?array
    {
        $mailable = self::mailable($key);
        if ($mailable === null) {
            return null;
        }

        // render() hydrates the Mailable's content() (its view + `with` data) into the view data; buildViewData()
        // then returns the full merged set the real blade renders from — the exact keys the send uses.
        $mailable->render();

        return $mailable->buildViewData();
    }

    /** A real successful refund if one exists; else an in-memory sample bound to the sample reservation. */
    /**
     * The currency the preview's sample figures should read in — the same reservation the preview renders
     * from, so an operator on a GBP property never previews their own template in euros.
     */
    public static function sampleCurrency(): string
    {
        $reservation = Reservation::query()->latest()->first();

        return $reservation === null
            ? (string) config('billing.default_currency', 'EUR')
            : (string) $reservation->currency;
    }

    private static function sampleRefund(Reservation $reservation): Payment
    {
        $real = Payment::query()->where('type', 'refund')->where('status', 'succeeded')
            ->with(['folio.reservation.leadGuest'])->latest()->first();
        if ($real instanceof Payment) {
            return $real;
        }

        $folio = new Folio(['currency' => $reservation->currency]);
        $folio->setRelation('reservation', $reservation);
        $refund = new Payment(['type' => 'refund', 'status' => 'succeeded', 'amount_minor' => (int) $reservation->total_minor, 'currency' => $reservation->currency]);
        $refund->setRelation('folio', $folio);

        return $refund;
    }

    /** The reservation's real outstanding balance, or its total as a sample when the folio is settled/absent. */
    private static function sampleBalance(Reservation $reservation): int
    {
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->first();
        $balance = $folio !== null ? FolioTotals::for($folio)['balance_minor'] : 0;

        return $balance > 0 ? $balance : (int) $reservation->total_minor;
    }
}
