<?php

use App\Mail\BookingConfirmationMail;
use App\Mail\CancellationMail;
use App\Mail\PaymentReminderMail;
use App\Mail\PreArrivalMail;
use App\Mail\RefundConfirmationMail;
use App\Mail\ReviewRequestMail;
use App\Mail\VerifyEmailMail;
use App\Mail\WaitlistNotificationMail;
use App\Mail\WinbackMail;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// GUEST TRANSACTIONAL EMAILS (Phase 3 · Sub-gate 4) — Mailables that ride the mail channel (Mailpit in dev),
// styled to the email brand, carrying that BOOKING's real data with integer money (MoneyFormatter). The three
// with a real event today send automatically (booking confirmed / cancelled / refunded); the payment reminder
// sends from its real command. Proven here: they fire on the real event/command, to the GUEST's own address
// ONLY (no PII leak), with the real figures; the deferred-trigger templates still render.

/** seedScenario's guest has no email; give it one so the guest is reachable. */
function emailGuest(array $s, string $email = 'ada@example.test'): void
{
    $s['guest']->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
}

// ── Booking confirmation — on the real reservation.confirmed, to the guest ─────────────────────────
it('emails the guest a booking confirmation on a confirmed booking', function () {
    Mail::fake();
    $s = seedScenario(1);
    emailGuest($s);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    Mail::assertQueued(BookingConfirmationMail::class, fn (BookingConfirmationMail $mail): bool => $mail->hasTo('ada@example.test') && $mail->reservation->is($reservation));
});

// ── Cancellation — on the real reservation.cancelled, to the guest ─────────────────────────────────
it('emails the guest a cancellation on a cancelled booking', function () {
    Mail::fake();
    $s = seedScenario(1);
    emailGuest($s);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    app(AssignmentService::class)->cancelReservation($reservation, 'Change of plans');

    Mail::assertSent(CancellationMail::class, fn (CancellationMail $mail): bool => $mail->hasTo('ada@example.test') && $mail->reservation->is($reservation));
});

// ── Refund confirmation — on the real payment.refunded, real amount, to the guest ─────────────────
it('emails the guest a refund confirmation with the real amount', function () {
    Mail::fake();
    $s = seedScenario(1);
    emailGuest($s);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(20000, 'EUR'));
    $payment = app(FolioService::class)->recordPayment($folio, Money::of(20000, 'EUR'));
    app(FolioService::class)->refundPayment($payment, Money::of(5000, 'EUR'));   // fires payment.refunded

    Mail::assertSent(RefundConfirmationMail::class, fn (RefundConfirmationMail $mail): bool => $mail->hasTo('ada@example.test') && (int) $mail->refund->amount_minor === 5000);
});

// ── PII: a guest email goes to that guest's OWN address ONLY ───────────────────────────────────────
it('sends a guest email to exactly the guest and nobody else', function () {
    Mail::fake();
    $s = seedScenario(1);
    emailGuest($s);

    book($s, ['unit_id' => $s['units'][0]->id]);

    Mail::assertQueued(BookingConfirmationMail::class, function (BookingConfirmationMail $mail): bool {
        return $mail->hasTo('ada@example.test')
            && count($mail->to) === 1        // exactly one recipient
            && count($mail->cc) === 0
            && count($mail->bcc) === 0;
    });
});

// ── No address → no send (the guard) ──────────────────────────────────────────────────────────────
it('sends no email when the guest has no address on file', function () {
    Mail::fake();
    $s = seedScenario(1);   // seed guest has NO email

    book($s, ['unit_id' => $s['units'][0]->id]);

    Mail::assertNotQueued(BookingConfirmationMail::class);
});

// ── Payment reminder command — arriving soon + a balance → the guest, real balance ────────────────
it('emails a payment reminder to a soon-arriving guest who still owes a balance', function () {
    Carbon::setTestNow('2026-08-30');   // so the 2026-09-01 booking is 2 days out (inside the 3-day window)
    Mail::fake();
    $s = seedScenario(1);
    emailGuest($s);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);   // a charge, no payment → balance due

    $this->artisan('emails:send-payment-reminders')->assertSuccessful();

    Mail::assertSent(PaymentReminderMail::class, fn (PaymentReminderMail $mail): bool => $mail->hasTo('ada@example.test') && $mail->balanceMinor === 20000);
    Carbon::setTestNow();
});

it('sends no payment reminder when the balance is settled', function () {
    Carbon::setTestNow('2026-08-30');
    Mail::fake();
    $s = seedScenario(1);
    emailGuest($s);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(20000, 'EUR'));
    app(FolioService::class)->recordPayment($folio, Money::of(20000, 'EUR'));   // settled

    $this->artisan('emails:send-payment-reminders')->assertSuccessful();

    Mail::assertNotSent(PaymentReminderMail::class);
    Carbon::setTestNow();
});

// ── Rendered content — real reference, guest name, integer money matching the reservation ─────────
it('renders the booking confirmation with the reservation’s real reference, guest and total', function () {
    $s = seedScenario(1);
    emailGuest($s);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $html = (new BookingConfirmationMail($reservation))->render();

    expect($html)
        ->toContain((string) $reservation->reference)
        ->toContain('Ada Lovelace')
        ->toContain(MoneyFormatter::display((int) $reservation->total_minor, (string) $reservation->currency));
});

// ── The deferred-trigger templates all render (built now; triggers land in their phase) ───────────
it('renders every deferred-trigger email template', function () {
    $s = seedScenario(1);
    emailGuest($s);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    expect((new PreArrivalMail($reservation))->render())->toContain('Your stay is ready');
    expect((new ReviewRequestMail($reservation))->render())->toContain('Thanks for staying with us');
    expect((new WaitlistNotificationMail('Ada', 'Seaside', '12–15 Aug 2026'))->render())->toContain('just opened up');
    expect((new WinbackMail('Ada', 'Seaside'))->render())->toContain('welcome you back');
    expect((new VerifyEmailMail('Ada', 'https://vyrobel.test/verify/abc'))->render())->toContain('Confirm your email');
});

// ══ Transactional-emails exact-match · GATE 1 (guest stay) ══════════════════════════════════════════

// ── Pre-arrival — the REAL scheduled trigger sends 48h out with the guest's OWN door code + wifi ───
it('sends the pre-arrival email 48h before check-in with the real door code and wifi, guest-only', function () {
    Carbon::setTestNow('2026-08-30');   // book() → 2026-09-01 check-in = 2 days out = the arrival window
    Mail::fake();
    $s = seedScenario(1);
    emailGuest($s);
    // The guest's OWN access secrets live on the assigned unit (encrypted at rest).
    $s['units'][0]->update(['door_code' => '4821', 'wifi_ssid' => 'VillaNet', 'wifi_password' => 'sunset123']);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $this->artisan('emails:send-pre-arrival')->assertSuccessful();

    Mail::assertSent(PreArrivalMail::class, function (PreArrivalMail $mail): bool {
        return $mail->hasTo('ada@example.test')
            && count($mail->to) === 1 && count($mail->cc) === 0 && count($mail->bcc) === 0;   // guest-only, #33 perimeter intact
    });

    // The rendered email carries the REAL secrets (released because we're inside the window).
    $html = (new PreArrivalMail($reservation))->render();
    expect($html)->toContain('4821')->toContain('VillaNet')->toContain('sunset123')->toContain((string) $reservation->reference);
    Carbon::setTestNow();
});

it('withholds the door code in the pre-arrival email for a stay outside the arrival window', function () {
    Carbon::setTestNow('2026-08-30');
    $s = seedScenario(1);
    emailGuest($s);
    $s['units'][0]->update(['door_code' => '4821']);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // Rendered "as of" a date well before the window → the code is NOT released (never fabricated).
    $html = (new PreArrivalMail($reservation, Carbon::parse('2026-08-01')))->render();
    expect($html)->not->toContain('4821')->toContain('share your access details');
    Carbon::setTestNow();
});

// ── Payment reminder — Total / Paid / Balance reconcile (Total − Paid == Balance, #17) ────────────
it('renders the payment reminder with a reconciling Total − Paid == Balance', function () {
    $s = seedScenario(1);
    emailGuest($s);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(20000, 'EUR'));
    app(FolioService::class)->recordPayment($folio, Money::of(8000, 'EUR'));   // paid 80.00 → balance 120.00

    $html = (new PaymentReminderMail($reservation, 12000))->render();

    expect($html)
        ->toContain(MoneyFormatter::display(20000, 'EUR'))   // Total
        ->toContain(MoneyFormatter::display(8000, 'EUR'))    // Paid
        ->toContain(MoneyFormatter::display(12000, 'EUR'))   // Balance == Total − Paid
        ->toContain('Pay '.MoneyFormatter::display(12000, 'EUR').' now');
});

// ── Refund — real amount + HONEST "original payment method" (no fabricated card, #28/#39) ──────────
it('renders the refund email with the real amount and an honest payment-method (no fabricated card)', function () {
    $s = seedScenario(1);
    emailGuest($s);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(20000, 'EUR'));
    $payment = app(FolioService::class)->recordPayment($folio, Money::of(20000, 'EUR'));
    $refund = app(FolioService::class)->refundPayment($payment, Money::of(5000, 'EUR'));

    $html = (new RefundConfirmationMail($refund))->render();

    expect($html)
        ->toContain(MoneyFormatter::display(5000, 'EUR'))          // real refund amount
        ->toContain('your original payment method')                // honest — no card_last4 stored
        ->toContain('Requested')->toContain('Processed')           // the real status timeline
        ->not->toContain('····');                                  // never a fabricated masked card
});

// ══ Transactional-emails exact-match · GATE 2 (account) ════════════════════════════════════════════

// ── Review request — star deep-links carry ?rating=N + "we only ask once" ─────────────────────────
it('renders the review request with star deep-links that pre-fill the rating', function () {
    $s = seedScenario(1);
    emailGuest($s);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $html = (new ReviewRequestMail($reservation, route('guest.review', $reservation->getKey())))->render();

    expect($html)
        ->toContain('rating=1')->toContain('rating=5')   // each star opens the review pre-filled
        ->toContain('Leave a review')
        ->toContain('only ask once');
});

// ── Verify — the REAL 1-hour expiry, and NO fabricated welcome credit ─────────────────────────────
it('renders the verify email with the real 1-hour expiry and no fabricated welcome credit', function () {
    $html = (new VerifyEmailMail('Ada', 'https://vyrobel.test/verify?token=abc', '1 hour', 'ada@example.test'))->render();

    expect($html)
        ->toContain('Confirm your email, Ada')
        ->toContain('Verify my email')
        ->toContain('expires in 1 hour')                 // the REAL claim-link lifetime, never a fabricated 24h
        ->toContain('no account is created until')
        ->not->toContain('credit')                       // no welcome-credit feature → never teased
        ->not->toContain('waiting for you');
});
