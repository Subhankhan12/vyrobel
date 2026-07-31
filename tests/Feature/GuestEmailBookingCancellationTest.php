<?php

use App\Mail\BookingConfirmationMail;
use App\Mail\CancellationMail;
use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Services\EmailTemplateService;
use App\Services\FolioService;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// #109 — the Booking Confirmation + Cancellation guest emails rebuilt exact-match to their wireframes + threaded
// through the #86 editor. RECONCILING (#17): the confirmation Total === total_minor over the real folio lines.
// HONEST (#28/#39): no masked card (SAQ A → "your original payment method"); the cancellation is TWO cases
// (a real refund vs an honest "no refund due" — never a fabricated split); the door code is a 48h POINTER (#33).

function bcBooking(array $s, array $line = [], string $email = 'ada@example.test'): Reservation
{
    $s['guest']->update(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => $email]);

    return book($s, array_merge(['unit_id' => $s['units'][0]->id], $line));
}

// ── 1. Confirmation — reconciling folio breakdown (#17) + honest paid-with + calendar + door pointer ──
it('renders the booking confirmation with a reconciling breakdown and honest details', function () {
    $s = seedScenario(1);
    $res = bcBooking($s);

    // A real folio with a room line + a tax line (Σ === the reservation total → #17 reconciles).
    $folio = app(FolioService::class)->openFolio($res);
    app(FolioService::class)->postCharge($folio, 'room', 'Room stay', Money::of(30000, 'EUR'));
    app(FolioService::class)->postCharge($folio, 'tax', 'VAT', Money::of(6900, 'EUR'));
    $res->update(['total_minor' => 36900, 'currency' => 'EUR']);
    $total = MoneyFormatter::display(36900, 'EUR');

    $html = (new BookingConfirmationMail($res->refresh()))->render();

    expect($html)
        ->toContain((string) $res->reference)
        ->toContain('Ada Lovelace')                       // the guest's real display name in the greeting
        ->toContain($total)                               // #17 — the reconciling grand total (total_minor)
        ->toContain('Accommodation')                      // the real room folio line
        ->toContain('VAT')                                // the real tax folio line
        ->toContain('Total')
        ->not->toContain('····')                          // #28 — never a fabricated masked card
        ->toContain('your original payment method')       // honest paid-with
        ->toContain('calendar.google.com')                // real add-to-calendar link
        ->toContain('48h before')                         // #33 — door code is a POINTER, never the code
        ->toContain('View receipt');
});

// ── 2. Confirmation — threads through #86 (a saved heading changes it; OFF stops the real send) ──────
it('threads the booking confirmation through the #86 editor and honors the on/off toggle', function () {
    $s = seedScenario(1);
    $res = bcBooking($s);

    // A saved heading genuinely replaces the default in the real email.
    app(EmailTemplateService::class)->save((string) $s['org']->id, 'booking-confirmation', ['heading' => 'Your Faro escape is booked'], null);
    expect((new BookingConfirmationMail($res))->render())->toContain('Your Faro escape is booked');

    // ON (default): the real reservation.confirmed trigger sends (a fresh, non-overlapping stay in the window).
    Mail::fake();
    book($s, ['unit_id' => $s['units'][0]->id, 'check_in' => '2026-09-04', 'check_out' => '2026-09-06']);
    Mail::assertSent(BookingConfirmationMail::class);

    // OFF: disabling stops the send on its real trigger.
    Mail::fake();
    app(EmailTemplateService::class)->setEnabled((string) $s['org']->id, 'booking-confirmation', false, null);
    book($s, ['unit_id' => $s['units'][0]->id, 'check_in' => '2026-09-07', 'check_out' => '2026-09-09']);
    Mail::assertNotSent(BookingConfirmationMail::class);
});

// ── 3. Cancellation — Case A: a real refund figure, branded, no fabricated card/promo ───────────────
it('renders the cancellation with a real refund (case A) — branded and honest', function () {
    $s = seedScenario(1);
    $res = bcBooking($s);

    $mail = new CancellationMail($res, 'Change of plans', 5000);   // a real recorded refund of €50.00
    $html = $mail->render();

    expect($html)
        ->toContain('Refund issued')
        ->toContain(MoneyFormatter::display(5000, (string) $res->currency))
        ->toContain('your original payment method')
        ->not->toContain('····')
        ->not->toContain('WELCOME10');                    // #39 — no fabricated promo
    // Branded envelope (BrandsGuestMail — the gap this rebuild closed).
    expect($mail->envelope()->subject)->toContain((string) $res->reference);
});

// ── 4. Cancellation — Case B: honest "no refund due", never a fabricated refund ─────────────────────
it('renders the cancellation honestly when no refund is due (case B)', function () {
    $s = seedScenario(1);
    $res = bcBooking($s);

    $html = (new CancellationMail($res, 'Non-refundable rate', null))->render();

    expect($html)
        ->toContain('No refund due')
        ->not->toContain('Refund issued');
});

// ── 5. Cancellation — threads through #86 + real trigger sends ───────────────────────────────────────
it('threads the cancellation through #86 and sends on the real cancel trigger', function () {
    $s = seedScenario(1);
    $res = bcBooking($s);

    app(EmailTemplateService::class)->save((string) $s['org']->id, 'cancellation', ['heading' => 'That booking is now cancelled'], null);
    expect((new CancellationMail($res, 'x', null))->render())->toContain('That booking is now cancelled');

    Mail::fake();
    $res2 = book($s, ['unit_id' => $s['units'][0]->id, 'check_in' => '2026-09-04', 'check_out' => '2026-09-06']);
    app(AssignmentService::class)->cancelReservation($res2, 'Change of plans');
    Mail::assertSent(CancellationMail::class);
});
