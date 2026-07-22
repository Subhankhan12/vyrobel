<?php

use App\Models\Reservation;
use App\Services\AssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

uses(RefreshDatabase::class);

// PHASE 8 · production email — re-proving the PHASE-3 INVARIANT with the REAL transport failure mode: a
// provider send failure (network / 5xx / rate-limit — what Postmark can really do) NEVER rolls back the
// domain transaction. The guest email is a best-effort side effect (caught + logged in
// SendGuestTransactionalEmails); the booking commits regardless. This holds for ANY transport, proven here
// against one that ALWAYS throws — the shape of a live-provider outage.

/** Register an "exploding" mail transport that fails on every send — a real-provider outage in a test. */
function useExplodingMailer(): void
{
    Mail::extend('exploding', fn (): AbstractTransport => new class extends AbstractTransport
    {
        protected function doSend(SentMessage $message): void
        {
            throw new RuntimeException('Postmark 500 — provider unavailable');
        }

        public function __toString(): string
        {
            return 'exploding://';
        }
    });

    // Set AFTER boot (the MailSafetyGuard already ran); 'exploding' is not a real provider, so it stays put.
    config(['mail.mailers.exploding' => ['transport' => 'exploding'], 'mail.default' => 'exploding']);
}

// ── 🔒 A PROVIDER SEND FAILURE NEVER ROLLS BACK THE BOOKING (the Phase-3 invariant, real transport) ──
it('never rolls back a booking when the mail provider fails', function () {
    useExplodingMailer();
    Log::spy();

    $s = seedScenario(1);
    $s['guest']->update(['email' => 'ada@example.test', 'first_name' => 'Ada', 'last_name' => 'Lovelace']);

    // Booking fires reservation.confirmed → the guest-email listener → the send THROWS. The listener catches
    // it, so book() returns normally and the reservation is committed.
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    expect($reservation->exists)->toBeTrue();
    expect(Reservation::find($reservation->getKey()))->not->toBeNull()   // the booking SURVIVED the mail failure
        ->and($reservation->fresh()->status)->toBe('confirmed');

    // The failure was logged (never the address — no PII), never rethrown.
    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $ctx = []): bool => str_contains($message, 'failed to send'))
        ->atLeast()->once();
});

// ── 🔒 THE SAME FAILURE MODE DOESN'T BREAK A CANCELLATION EITHER (the side effect stays best-effort) ─
it('never rolls back a cancellation when the mail provider fails', function () {
    useExplodingMailer();

    $s = seedScenario(1);
    $s['guest']->update(['email' => 'ada@example.test', 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // Cancelling fires reservation.cancelled → the cancellation email → the send THROWS (caught). The cancel
    // still commits.
    app(AssignmentService::class)->cancelReservation($reservation, 'guest requested');

    expect($reservation->fresh()->status)->toBe('cancelled');
});
