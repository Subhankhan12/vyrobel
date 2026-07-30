<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmationMail;
use App\Mail\CancellationMail;
use App\Mail\PaymentReminderMail;
use App\Mail\PreArrivalMail;
use App\Mail\RefundConfirmationMail;
use App\Mail\ReviewRequestMail;
use App\Mail\VerifyEmailMail;
use App\Mail\WaitlistNotificationMail;
use App\Mail\WinbackMail;
use App\Models\Folio;
use App\Models\Payment;
use App\Models\Reservation;
use App\Support\Folio\FolioTotals;
use App\Support\Guest\GuestName;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Email Previews (Phase 3 · Sub-gate 4) — an operator tool to render every transactional email in the browser
 * with the tenant's REAL sample data (the latest reservation / a real refund), so the designs can be reviewed
 * without sending. Behind the back-office gate (#26). The catalog is honest about each email's trigger: which
 * send on a real event today, which are template-ready with their trigger deferred to a later phase.
 *
 * Rendering a {@see Mailable} from a controller returns its HTML — no mail is sent here.
 */
class EmailPreviewController extends Controller
{
    /**
     * key => [title, status] — status is the honest trigger state shown on the index.
     *
     * @var array<string, array{title: string, status: string}>
     */
    private const CATALOG = [
        'booking-confirmation' => ['title' => 'Booking confirmation', 'status' => 'Live · sends on booking confirmed'],
        'cancellation' => ['title' => 'Cancellation', 'status' => 'Live · sends on booking cancelled'],
        'refund-confirmation' => ['title' => 'Refund confirmation', 'status' => 'Live · sends on refund processed'],
        'payment-reminder' => ['title' => 'Payment reminder', 'status' => 'Live · daily emails:send-payment-reminders'],
        'pre-arrival' => ['title' => 'Pre-arrival', 'status' => 'Live · daily emails:send-pre-arrival (48h before)'],
        'review-request' => ['title' => 'Review request', 'status' => 'Live · sends on check-out'],
        'waitlist-notification' => ['title' => 'Waitlist notification', 'status' => 'Template ready · trigger deferred (Phase 4 · Waitlist)'],
        'winback' => ['title' => 'Winback', 'status' => 'Template ready · trigger deferred (scheduler)'],
        'verify-email' => ['title' => 'Verify email', 'status' => 'Live · sends on booking-tied account claim'],
    ];

    public function index(): Response
    {
        return response()->view('operator.email-previews', ['catalog' => self::CATALOG]);
    }

    public function show(string $key): SymfonyResponse
    {
        abort_unless(array_key_exists($key, self::CATALOG), 404);

        $reservation = Reservation::query()->with(['leadGuest', 'property'])->latest()->first();
        abort_if($reservation === null, 404, 'No sample reservation exists in this organization yet — create a booking first.');

        $guestName = GuestName::display($reservation->leadGuest);
        $propertyName = (string) ($reservation->property->name ?? 'Your property');

        $mailable = match ($key) {
            'booking-confirmation' => new BookingConfirmationMail($reservation),
            'cancellation' => new CancellationMail($reservation, 'Guest requested cancellation', (int) $reservation->total_minor),
            'refund-confirmation' => new RefundConfirmationMail($this->sampleRefund($reservation)),
            'payment-reminder' => new PaymentReminderMail($reservation, $this->sampleBalance($reservation)),
            'pre-arrival' => new PreArrivalMail($reservation),
            'review-request' => new ReviewRequestMail($reservation, url('/')),
            'waitlist-notification' => new WaitlistNotificationMail($guestName, $propertyName, '12–15 Aug 2026', url('/')),
            'winback' => new WinbackMail($guestName, $propertyName),
            'verify-email' => new VerifyEmailMail($guestName, url('/verify-email/sample-token')),
        };

        return response($mailable->render());
    }

    /** A real successful refund if one exists; otherwise an in-memory sample bound to the sample reservation. */
    private function sampleRefund(Reservation $reservation): Payment
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
    private function sampleBalance(Reservation $reservation): int
    {
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->first();
        $balance = $folio !== null ? FolioTotals::for($folio)['balance_minor'] : 0;

        return $balance > 0 ? $balance : (int) $reservation->total_minor;
    }
}
