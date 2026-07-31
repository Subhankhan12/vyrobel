<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DomainEventRecorded;
use App\Mail\BookingConfirmationMail;
use App\Mail\CancellationMail;
use App\Mail\RefundConfirmationMail;
use App\Mail\ReviewRequestMail;
use App\Models\DomainEvent;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Tenancy\TenantContext;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the GUEST transactional emails (Phase 3 · Sub-gate 4) on the REAL domain events that fire today —
 * booking confirmed, booking cancelled, refund processed — to the lead guest's OWN email address only. Mirrors
 * the notification dispatcher: same synchronous {@see DomainEventRecorded} bus, same org-context wrap, same
 * idempotency guard (the bus double-delivers). Only these three events are mapped; the other emails
 * (pre-arrival, review, waitlist, winback, verify) have their templates built but their triggers deferred to a
 * later phase / a scheduled command, so nothing fake fires here.
 *
 * ⚠️ A mail failure is a SIDE EFFECT and must NEVER break the operation that triggered it. reservation.confirmed
 * can fire inside the booking transaction, so a thrown SMTP error would roll the booking back — every send is
 * wrapped in try/catch and only logged (never the email address — no PII in logs) on failure.
 */
class SendGuestTransactionalEmails
{
    /** @var array<int, string> */
    private const HANDLED = ['reservation.confirmed', 'reservation.cancelled', 'payment.refunded', 'reservation.checked_out'];

    /** @var array<string, true> */
    private static array $processed = [];

    public function __construct(private TenantContext $tenant) {}

    public function handle(DomainEventRecorded $event): void
    {
        $domainEvent = $event->domainEvent;

        if (! in_array($domainEvent->event_type, self::HANDLED, true)) {
            return;
        }

        $key = (string) $domainEvent->id;
        if (isset(self::$processed[$key])) {
            return;   // the double-fire guard
        }
        self::$processed[$key] = true;

        $orgId = $domainEvent->organization_id;
        if (! is_string($orgId) || $orgId === '') {
            return;
        }

        $this->tenant->forOrganization($orgId, function () use ($domainEvent): void {
            match ($domainEvent->event_type) {
                'reservation.confirmed' => $this->bookingConfirmation($domainEvent),
                'reservation.cancelled' => $this->cancellation($domainEvent),
                'payment.refunded' => $this->refundConfirmation($domainEvent),
                'reservation.checked_out' => $this->reviewRequest($domainEvent),
            };
        });
    }

    private function bookingConfirmation(DomainEvent $domainEvent): void
    {
        $reservation = Reservation::query()->with(['leadGuest', 'property'])->find($domainEvent->subject_id);
        if (! $reservation instanceof Reservation) {
            return;
        }
        // #86 — the operator can disable this email per-org; OFF genuinely stops the send.
        if (! EmailTemplateResolver::enabled((string) $reservation->organization_id, 'booking-confirmation')) {
            return;
        }
        $this->sendToGuest($reservation->leadGuest, new BookingConfirmationMail($reservation));
    }

    private function cancellation(DomainEvent $domainEvent): void
    {
        $reservation = Reservation::query()->with(['leadGuest', 'property'])->find($domainEvent->subject_id);
        if (! $reservation instanceof Reservation) {
            return;
        }
        if (! EmailTemplateResolver::enabled((string) $reservation->organization_id, 'cancellation')) {
            return;   // #86 — disabled by the operator
        }

        $reason = is_array($domainEvent->payload) ? ($domainEvent->payload['reason'] ?? null) : null;
        // A refund, if any, gets its OWN payment.refunded → RefundConfirmationMail; the cancellation email doesn't
        // guess a figure, so refundMinor is left null here.
        $this->sendToGuest($reservation->leadGuest, new CancellationMail($reservation, is_string($reason) ? $reason : null));
    }

    /**
     * Phase 5 — the Review Request email, fired on a REAL completed stay (post-checkout). The (previously deferred)
     * trigger is now wired: it links the guest to the review page for THIS stay. Mailpit in dev; prod account-gated.
     */
    private function reviewRequest(DomainEvent $domainEvent): void
    {
        $reservation = Reservation::query()->with(['leadGuest', 'property'])->find($domainEvent->subject_id);
        if (! $reservation instanceof Reservation) {
            return;
        }
        // #86 — the operator can disable this email per-org; OFF genuinely stops the send.
        if (! EmailTemplateResolver::enabled((string) $reservation->organization_id, 'review-request')) {
            return;
        }
        $this->sendToGuest($reservation->leadGuest, new ReviewRequestMail($reservation, route('guest.review', $reservation->getKey())));
    }

    private function refundConfirmation(DomainEvent $domainEvent): void
    {
        $refund = Payment::query()->with(['folio.reservation.leadGuest'])->find($domainEvent->subject_id);
        if (! $refund instanceof Payment || (string) $refund->status !== 'succeeded') {
            return;   // only a genuinely successful refund emails the guest
        }
        if (! EmailTemplateResolver::enabled((string) $refund->organization_id, 'refund-confirmation')) {
            return;   // #86 — disabled by the operator
        }

        $this->sendToGuest($refund->folio?->reservation?->leadGuest, new RefundConfirmationMail($refund));
    }

    /** Send to the guest's OWN email only (encrypted cast → plaintext here); no email → no send. Never throws. */
    private function sendToGuest(?Guest $guest, Mailable $mail): void
    {
        $email = $guest?->email;
        if (! is_string($email) || $email === '') {
            return;
        }

        try {
            Mail::to($email)->send($mail);
        } catch (Throwable $e) {
            // A side effect must never break the triggering operation. Log the class + reason, never the address.
            Log::warning('Guest transactional email failed to send', ['mailable' => $mail::class, 'error' => $e->getMessage()]);
        }
    }
}
