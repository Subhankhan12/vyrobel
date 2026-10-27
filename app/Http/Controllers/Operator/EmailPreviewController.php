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
use App\Support\Guest\MarketingUnsubscribe;
use App\Support\Mail\MailSafetyGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
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
     * The catalog's keys — the email codes the gallery offers; {@see catalog()} names each one.
     *
     * @var list<string>
     */
    private const KEYS = [
        'booking-confirmation', 'cancellation', 'refund-confirmation', 'payment-reminder', 'pre-arrival',
        'review-request', 'waitlist-notification', 'winback', 'verify-email',
    ];

    /**
     * key => [title, status] — status is the honest trigger state shown on the index. Translated at render time.
     *
     * @return array<string, array{title: string, status: string}>
     */
    private function catalog(): array
    {
        return [
            'booking-confirmation' => ['title' => __('php/http/controllers/operator/email_preview_controller.booking_confirmation_title'), 'status' => __('php/http/controllers/operator/email_preview_controller.booking_confirmation_status')],
            'cancellation' => ['title' => __('php/http/controllers/operator/email_preview_controller.cancellation_title'), 'status' => __('php/http/controllers/operator/email_preview_controller.cancellation_status')],
            'refund-confirmation' => ['title' => __('php/http/controllers/operator/email_preview_controller.refund_confirmation_title'), 'status' => __('php/http/controllers/operator/email_preview_controller.refund_confirmation_status')],
            'payment-reminder' => ['title' => __('php/http/controllers/operator/email_preview_controller.payment_reminder_title'), 'status' => __('php/http/controllers/operator/email_preview_controller.payment_reminder_status')],
            'pre-arrival' => ['title' => __('php/http/controllers/operator/email_preview_controller.pre_arrival_title'), 'status' => __('php/http/controllers/operator/email_preview_controller.pre_arrival_status')],
            'review-request' => ['title' => __('php/http/controllers/operator/email_preview_controller.review_request_title'), 'status' => __('php/http/controllers/operator/email_preview_controller.review_request_status')],
            'waitlist-notification' => ['title' => __('php/http/controllers/operator/email_preview_controller.waitlist_notification_title'), 'status' => __('php/http/controllers/operator/email_preview_controller.waitlist_notification_status')],
            'winback' => ['title' => __('php/http/controllers/operator/email_preview_controller.winback_title'), 'status' => __('php/http/controllers/operator/email_preview_controller.winback_status')],
            'verify-email' => ['title' => __('php/http/controllers/operator/email_preview_controller.verify_email_title'), 'status' => __('php/http/controllers/operator/email_preview_controller.verify_email_status')],
        ];
    }

    public function index(Request $request): Response
    {
        return response()->view('operator.email-previews', [
            'catalog' => $this->catalog(),
            'operatorEmail' => (string) ($request->user()->email ?? ''),
        ]);
    }

    public function show(string $key): SymfonyResponse
    {
        abort_unless(in_array($key, self::KEYS, true), 404);

        $reservation = $this->sampleReservation();
        abort_if($reservation === null, 404, __('php/http/controllers/operator/email_preview_controller.no_sample_reservation'));

        return response($this->buildMailable($key, $reservation)->render());
    }

    /**
     * A REAL test send from the previews gallery — the SAME sample Mailable the preview renders, sent to the
     * OPERATOR'S OWN email ONLY (never an arbitrary/guest inbox; the certified {@see EmailTemplateActionController}
     * rule). Goes through the real mail path, so {@see MailSafetyGuard} downgrades to log/Mailpit
     * outside production. Gated `can:properties.manage` at the route (#30). Never a fake toast (#39).
     */
    public function testSend(Request $request, string $key): RedirectResponse
    {
        abort_unless(in_array($key, self::KEYS, true), 404);

        $reservation = $this->sampleReservation();
        abort_if($reservation === null, 404, __('php/http/controllers/operator/email_preview_controller.no_sample_reservation'));

        $email = (string) $request->user()->email;
        abort_if($email === '', 422, __('php/http/controllers/operator/email_preview_controller.no_email_address'));

        Mail::to($email)->locale($request->user()->preferredLocale())->send($this->buildMailable($key, $reservation));

        return redirect()->route('app.email-previews')->with('email_preview_notice', __('php/http/controllers/operator/email_preview_controller.test_sent', ['email' => $email]));
    }

    /**
     * The tenant's sample reservation for the previews — F-OP-4: prefer the latest booking with a REAL total
     * (a posted folio) so previews render a meaningful figure instead of €0.00 for a folio-less confirmed
     * booking. Falls back to the latest reservation of any kind so a fresh org can still preview. Tenant-scoped.
     */
    private function sampleReservation(): ?Reservation
    {
        return Reservation::query()->with(['leadGuest', 'property'])->where('total_minor', '>', 0)->latest()->first()
            ?? Reservation::query()->with(['leadGuest', 'property'])->latest()->first();
    }

    /** The real sample Mailable for a preview key over the tenant's sample reservation (the ONE builder). */
    private function buildMailable(string $key, Reservation $reservation): Mailable
    {
        $guestName = GuestName::display($reservation->leadGuest);
        $propertyName = (string) ($reservation->property->name ?? __('php/http/controllers/operator/email_preview_controller.sample_property_fallback'));

        // ⚠️ The payment reminder previews over a booking that really OWES money, so Total − Paid == Balance
        // holds (#17). Every other preview uses the shared sample reservation.
        $payable = $this->samplePayableReservation() ?? $reservation;

        return match ($key) {
            'booking-confirmation' => new BookingConfirmationMail($reservation),
            'cancellation' => new CancellationMail($reservation, __('php/http/controllers/operator/email_preview_controller.sample_cancellation_reason'), (int) $reservation->total_minor),
            'refund-confirmation' => new RefundConfirmationMail($this->sampleRefund($reservation)),
            'payment-reminder' => new PaymentReminderMail($payable, $this->sampleBalance($payable)),
            'pre-arrival' => new PreArrivalMail($reservation),
            'review-request' => new ReviewRequestMail($reservation, url('/')),
            'waitlist-notification' => new WaitlistNotificationMail($guestName, $propertyName, '12–15 Aug 2026', url('/')),
            'winback' => new WinbackMail(
                $guestName,
                $propertyName,
                $reservation->leadGuest !== null ? MarketingUnsubscribe::url($reservation->leadGuest) : null,
                $reservation->leadGuest !== null ? MarketingUnsubscribe::oneClickUrl($reservation->leadGuest) : null,
            ),
            'verify-email' => new VerifyEmailMail($guestName, url('/verify-email/sample-token')),
            default => abort(404),   // unreachable — callers guard on the KEYS first
        };
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

    /**
     * The reservation's REAL outstanding balance.
     *
     * ⚠️ THIS USED TO RENDER AN ARITHMETICALLY IMPOSSIBLE EMAIL (audit 2026-08-07 · §5 parity). When the
     * sample folio was settled it substituted the reservation TOTAL as the "balance" — but the Mailable
     * reads Total and Paid from the real {@see FolioTotals}, so the preview showed:
     *
     *     Total €369.00  −  Paid €369.00  =  Balance due €369.00
     *
     * breaking the mail's own stated invariant (Total − Paid = Balance, #17) on the very screen an operator
     * uses to check their template. A money email that cannot do arithmetic is worse than an empty one.
     *
     * The balance is now always the real one. {@see samplePayableReservation()} picks a booking that
     * genuinely OWES money, so the preview is both meaningful and coherent — the figures come from one
     * folio and reconcile by construction rather than being made to look plausible.
     */
    private function sampleBalance(Reservation $reservation): int
    {
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->first();

        return $folio !== null ? FolioTotals::for($folio)['balance_minor'] : 0;
    }

    /**
     * A reservation that genuinely has an outstanding balance — the honest subject for a PAYMENT REMINDER
     * preview, since that is the only state in which this email is ever really sent (the sending command
     * skips a settled folio). Falls back to the general sample when the tenant owes nothing anywhere, in
     * which case the preview shows a real €0.00 rather than an invented figure.
     */
    private function samplePayableReservation(): ?Reservation
    {
        $owing = Reservation::query()
            ->with(['leadGuest', 'property'])
            ->whereIn('id', Folio::query()->select('reservation_id'))
            ->latest()
            ->get()
            ->first(function (Reservation $r): bool {
                $folio = Folio::query()->where('reservation_id', $r->getKey())->first();

                return $folio !== null && FolioTotals::for($folio)['balance_minor'] > 0;
            });

        return $owing ?? $this->sampleReservation();
    }
}
