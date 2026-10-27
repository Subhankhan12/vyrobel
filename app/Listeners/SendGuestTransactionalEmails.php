<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Reservations\ConfirmTentativeReservation;
use App\Events\DomainEventRecorded;
use App\Jobs\SendBookingConfirmationEmail;
use App\Mail\CancellationMail;
use App\Mail\RefundConfirmationMail;
use App\Mail\ReviewRequestMail;
use App\Models\DomainEvent;
use App\Models\Guest;
use App\Models\GuestReviewInvite;
use App\Models\Payment;
use App\Models\Reservation;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Email\GuestEmailPreferences;
use App\Support\Log\LogSafe;
use App\Support\Notifications\BookingNotificationIntent;
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
    /**
     * ⚠️⚠️ `reservation.hold_confirmed` IS HERE BECAUSE OF F-64, AND IT IS THE SECOND HALF OF THAT FIX.
     * {@see ConfirmTentativeReservation} records it when an operator turns a
     * tentative hold into a real stay. That is the FIRST MOMENT the sentence "your booking is confirmed"
     * becomes true for a held booking, so that is when the guest is told it.
     *
     * ⚠️ Every entry here needs a `match` arm below or PHP throws `UnhandledMatchError` — the two move together,
     * deliberately, so a type cannot be added and silently do nothing.
     */
    private const HANDLED = ['reservation.confirmed', 'reservation.hold_confirmed', 'reservation.cancelled', 'payment.refunded', 'reservation.checked_out'];

    /** Delay (seconds) on the booking-confirmation send so it renders after the folio is posted (≫ request time). */
    private const CONFIRMATION_DELAY_SECONDS = 60;

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
                'reservation.confirmed',
                'reservation.hold_confirmed' => $this->bookingConfirmation($domainEvent),
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

        // ⚠️⚠️ F-64 — THE GUEST IS TOLD A BOOKING IS CONFIRMED ONLY WHEN IT IS.
        //
        // 🔬 `AssignmentService::createGroupBooking()` records `reservation.confirmed` for EVERY persisted
        // member including TENTATIVE ones — correctly, because a hold occupies inventory and the channels must
        // learn the room is gone. This listener used to take that event at its name and email the guest
        // "booking confirmed" for a hold that carried a pickup deadline and promised them nothing. That is the
        // product stating a falsehood to a member of the public, in writing.
        //
        // ⚠️⚠️ AND THIS GATE IS HERE — AT DISPATCH — RATHER THAN INSIDE THE JOB, WHICH IS THE WHOLE FIX.
        // 🔬 The dispatch below carries a SIXTY-SECOND delay. A status check inside the job would be RACY:
        // hold at T+0 queues a job for T+60; an operator who confirms at T+10 makes the job re-read
        // `confirmed` at T+60 and send — on top of the send the confirm itself triggered. Two emails, from a
        // check written to prevent one.
        //
        // ⚠️⚠️ DECIDED HERE, THE FIX IS IMMUNE TO ORDERING: the status is read synchronously at the moment the
        // event is recorded — the value the very same committed transaction just wrote — so a held booking
        // never puts a job on the queue at all, and there is no pending send for a later confirm to race with.
        // tests/Feature/HoldDoesNotEmailTheGuestTest.php asserts the queue is EMPTY after a hold.
        //
        // ⚠️ THE EVENT IS DELIBERATELY NOT SUPPRESSED UPSTREAM. Two other listeners consume it at hold time and
        // both are correct: the ARI push (removing it oversells the room) and the OPERATOR new-booking notice
        // (the front desk must learn the hold arrived). The email was the only wrong consumer.
        if ((string) $reservation->status !== 'confirmed') {
            return;
        }

        // #86 — the operator can disable this email per-org; OFF genuinely stops the send.
        if (! EmailTemplateResolver::enabled((string) $reservation->organization_id, 'booking-confirmation')) {
            return;
        }
        // GATE 1 — the New Reservation wizard's "Send confirmation email" checkbox: an operator can suppress
        // THIS booking's confirmation (the flag is set on the request before createBooking fires this event).
        if (app(BookingNotificationIntent::class)->confirmationSuppressed()) {
            return;
        }
        // Delayed so it renders AFTER the caller posts the folio — the Total/breakdown reconcile (#17).
        //
        // ⚠️ Dispatched as a JOB carrying SCALAR ids, never `Mail::later(new BookingConfirmationMail($res))`.
        // That queued a Mailable holding a tenant-scoped Reservation via SerializesModels. The payload
        // restores fine on the worker, but RENDERING it touches leadGuest/property — tenant-scoped, with no
        // ambient TenantContext there — so every confirmation threw and failed permanently (audit H2 /
        // F-2b-01 / P5-04). The job restores tenancy FIRST and does the whole load-and-send inside it. It
        // also resolves the recipient address in-context rather than serialising it, keeping guest PII out
        // of jobs/failed_jobs.
        // No `guest?->email` pre-check here: the address is resolved at send time, inside the job.
        //
        // ⚠️ WRAPPED, and the wrap is load-bearing. `reservation.confirmed` fires INSIDE the booking
        // transaction, and this listener's whole contract is that a mail side effect NEVER breaks the
        // operation that triggered it (see the class docblock + MailDeliveryBoundaryTest). On the `sync`
        // driver the dispatched job executes INLINE, so a provider outage would throw straight through and
        // roll the booking back. Catching here restores the boundary that `sendToGuest()` provides for the
        // inline sends.
        // On a REAL queue the exception is NOT swallowed — it happens on the worker, where it correctly
        // fails the job and triggers the retry/backoff the job declares.
        if ($reservation->leadGuest !== null) {
            try {
                SendBookingConfirmationEmail::dispatch(
                    (string) $reservation->organization_id,
                    (string) $reservation->getKey(),
                )->delay(now()->addSeconds(self::CONFIRMATION_DELAY_SECONDS));
            } catch (Throwable $e) {
                // Log the class + reason, never the address (no PII in logs) — same rule as sendToGuest().
                Log::warning('Guest transactional email failed to send', [
                    'mailable' => SendBookingConfirmationEmail::class,
                    'error' => LogSafe::message($e),
                ]);
            }
        }
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
        $this->sendToGuest($reservation->leadGuest, new CancellationMail($reservation, is_string($reason) ? $reason : null), 'cancellation');
    }

    /**
     * Phase 5 — the Review Request email, fired on a REAL completed stay (post-checkout). The (previously deferred)
     * trigger is now wired: it links the guest to the review page for THIS stay. Mailpit in dev; prod account-gated.
     */
    /**
     * ⚠️⚠️ R3 — EVERY PATH THROUGH THIS METHOD NOW LEAVES A ROW, AND THAT IS THE POINT. The row's EXISTENCE
     * is what separates "no request was sent" from "this stay predates the record". If an early return here
     * ever stops recording, a stay checked out after the migration would look exactly like one from before
     * it, and the undo would start guessing again — see {@see GuestReviewInvite}.
     */
    private function reviewRequest(DomainEvent $domainEvent): void
    {
        $reservation = Reservation::query()->with(['leadGuest', 'property'])->find($domainEvent->subject_id);
        if (! $reservation instanceof Reservation) {
            return;   // no subject — there is no stay to record anything against
        }
        // #86 — the operator can disable this email per-org; OFF genuinely stops the send.
        if (! EmailTemplateResolver::enabled((string) $reservation->organization_id, 'review-request')) {
            $this->recordInvite($reservation, GuestReviewInvite::SUPPRESSED_BY_OPERATOR, sent: false);

            return;
        }
        // ⚠️ AND THE GUEST'S OWN CHOICE — a SECOND, INDEPENDENT gate (see the payment-reminder command for
        // the same pair). A guest who does not want to be asked how their stay went is not asked.
        if (! GuestEmailPreferences::mayReceive($reservation->leadGuest, 'reviews')) {
            $this->recordInvite($reservation, GuestReviewInvite::SUPPRESSED_BY_GUEST, sent: false);

            return;
        }

        // ⚠️ RECORDED ON SUCCESS, NOT ON INTENT.
        $sent = $this->sendToGuest($reservation->leadGuest, new ReviewRequestMail($reservation, route('guest.review', $reservation->getKey())), 'review-request');

        $this->recordInvite(
            $reservation,
            $sent ? GuestReviewInvite::SENT : GuestReviewInvite::NO_CONTACT,
            sent: $sent,
        );
    }

    /**
     * ⚠️ `sent_at` IS STICKY. A stay can be checked out, reversed and checked out again; if a request ever
     * left, that stays true forever. The OUTCOME reflects the latest attempt; the TIMESTAMP reflects the
     * first success, because "was this guest ever invited" is the question the undo asks.
     */
    private function recordInvite(Reservation $reservation, string $outcome, bool $sent): void
    {
        $existing = GuestReviewInvite::query()->where('reservation_id', $reservation->getKey())->first();

        // ⚠️ STICKY, SPELLED OUT: an earlier success is never overwritten by a later suppression.
        $sentAt = $existing instanceof GuestReviewInvite && $existing->sent_at !== null
            ? $existing->sent_at
            : ($sent ? now() : null);

        GuestReviewInvite::query()->updateOrCreate(
            ['reservation_id' => (string) $reservation->getKey()],
            [
                'organization_id' => (string) $reservation->organization_id,
                'outcome' => $outcome,
                'channel' => 'email',
                'sent_at' => $sentAt,
            ],
        );
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

        $this->sendToGuest($refund->folio?->reservation?->leadGuest, new RefundConfirmationMail($refund), 'refund-confirmation');
    }

    /**
     * Send to the guest's OWN email only (encrypted cast → plaintext here); no email → no send. Never throws.
     *
     * `$delaySeconds` QUEUES the send with a short delay instead of rendering inline — used by the booking
     * confirmation because `reservation.confirmed` is recorded INSIDE `createBooking`, BEFORE the caller posts
     * the folio (BookingEngineService / ReservationCreateController). Rendering inline would read `total_minor=0`
     * with no folio items (a "€0.00" email). The delayed job re-loads the reservation (SerializesModels) once
     * the request has committed the folio, so the Total + breakdown reconcile (#17). The delay ≫ request time.
     *
     * ⚠️ Requires a real queue driver that honours the delay — `redis` (prod · Horizon) or `database` (fallback),
     * both configured here. The `sync` driver runs delayed jobs inline (ignoring the delay), so a `sync` config
     * would still render before the folio; that is a dev/test-only connection, never prod (see config/queue.php +
     * `.env`). Tests use `Mail::fake` (assertQueued), which captures the deferral regardless of driver.
     */
    /**
     * ⚠️ RETURNS WHETHER THE SEND ACTUALLY LEFT. R3's record must state what HAPPENED, not what was intended:
     * a row written before this returns would claim a send that a thrown exception swallowed.
     * 🔬 `ReviewRequestMail` does not implement `ShouldQueue` and no caller passes a delay for it, so the
     * review path is SYNCHRONOUS and a clean return here genuinely means it went.
     */
    private function sendToGuest(?Guest $guest, Mailable $mail, string $templateKey, ?int $delaySeconds = null): bool
    {
        $email = $guest?->email;
        if (! is_string($email) || $email === '') {
            return false;
        }

        try {
            // I18N-01 — the guest's language, or the organisation's when it has rewritten this email's words
            $pending = Mail::to($email)->locale(EmailTemplateResolver::localeFor((string) $guest->organization_id, $templateKey, $guest->preferredLocale()));
            $delaySeconds !== null
                ? $pending->later(now()->addSeconds($delaySeconds), $mail)
                : $pending->send($mail);
        } catch (Throwable $e) {
            // A side effect must never break the triggering operation. Log the class + reason, never the address.
            Log::warning('Guest transactional email failed to send', ['mailable' => $mail::class, 'error' => LogSafe::message($e)]);

            return false;
        }

        return true;
    }
}
