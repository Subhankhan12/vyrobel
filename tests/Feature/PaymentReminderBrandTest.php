<?php

use App\Mail\PaymentReminderMail;
use App\Models\Folio;
use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * A GUEST MONEY EMAIL MUST LOOK LIKE ITS SIBLINGS (audit 2026-08-07 · §5 parity).
 *
 * PaymentReminderMail was the ONE guest-facing email that sent as the generic Vyrobel brand.
 * BookingConfirmation, Cancellation, RefundConfirmation, PreArrival and ReviewRequest all resolve the
 * property brand through the shared BrandsGuestMail trait; this one built a bare Envelope.
 *
 * ⚠️ AND IT IS THE EMAIL THAT ASKS FOR MONEY. A payment request arriving from a different sender name
 * than the booking confirmation the guest already has is precisely the shape a guest is taught to treat
 * as phishing — so the brand regression lands hardest on the message that can least afford it.
 */

function reminderScenario(): Reservation
{
    $s = seedScenario(1);

    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-05',
            'check_out' => '2026-09-08', 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id,
            'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'price_minor' => 30000, 'currency' => 'EUR']],
    );

    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room stay', Money::of(30000, 'EUR'));
    app(FolioService::class)->recordPayment($folio, Money::of(10000, 'EUR'), 'cash');

    $s['property']->forceFill([
        'brand_email_from_name' => 'Seaside Apartments',
        'brand_reply_to' => 'stay@seaside.example',
    ])->save();

    return $reservation->fresh();
}

it('sends under the PROPERTY brand, like every other guest email', function () {
    $reservation = reminderScenario();

    $envelope = (new PaymentReminderMail($reservation, 20000))->envelope();

    // The display name is the property's, not "Vyrobel".
    expect($envelope->from?->name)->toBe('Seaside Apartments');

    // Replies reach the property, not the platform.
    expect(collect($envelope->replyTo)->pluck('address')->all())->toContain('stay@seaside.example');
});

it('renders the folio figures so that Total − Paid = Balance (#17)', function () {
    $reservation = reminderScenario();
    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->with(['items', 'payments'])->firstOrFail();
    $totals = FolioTotals::for($folio);

    // 30000 charged, 10000 paid ⇒ 20000 due. The mail is handed the REAL balance.
    expect($totals)->toMatchArray(['charges_minor' => 30000, 'paid_minor' => 10000, 'balance_minor' => 20000]);

    $html = (new PaymentReminderMail($reservation, $totals['balance_minor']))->render();

    expect($html)->toContain('300.00')->toContain('100.00')->toContain('200.00');
});

it('previews over a booking that actually OWES money — never an impossible email', function () {
    // ⚠️ THE DEFECT: the preview substituted the reservation TOTAL as the "balance" whenever the sample
    // folio was settled, while Total and Paid still came from the real folio — so an operator checking
    // their template saw "Total €369 − Paid €369 = Balance due €369". A money email that cannot do
    // arithmetic is worse than an empty one.
    $s = seedScenario(2);
    $staff = operatorUser($s, ['name' => 'Mia Manager'], 'Hotel Manager');

    // A SETTLED booking — the exact state that used to produce the impossible preview. It is also the
    // newest, so the old code would have picked it.
    $settled = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-04',
            'check_out' => '2026-09-06', 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id,
            'check_in' => '2026-09-04', 'check_out' => '2026-09-06', 'price_minor' => 36900, 'currency' => 'EUR']],
    );
    $settledFolio = app(FolioService::class)->openFolio($settled);
    app(FolioService::class)->postCharge($settledFolio, 'room', 'Room stay', Money::of(36900, 'EUR'));
    app(FolioService::class)->recordPayment($settledFolio, Money::of(36900, 'EUR'), 'cash');

    // …and one that genuinely owes, which is the only state this email is ever really sent in.
    $owing = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-07',
            'check_out' => '2026-09-09', 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][1]->id, 'rate_plan_id' => $s['ratePlan']->id,
            'check_in' => '2026-09-07', 'check_out' => '2026-09-09', 'price_minor' => 30000, 'currency' => 'EUR']],
    );
    $owingFolio = app(FolioService::class)->openFolio($owing);
    app(FolioService::class)->postCharge($owingFolio, 'room', 'Room stay', Money::of(30000, 'EUR'));
    app(FolioService::class)->recordPayment($owingFolio, Money::of(10000, 'EUR'), 'cash');

    $html = $this->actingAs($staff)->get('/app/email-previews/payment-reminder')->assertOk()->getContent();

    // The preview is built over the OWING booking: 300.00 charged − 100.00 paid = 200.00 due.
    expect($html)->toContain('300.00')->toContain('100.00')->toContain('200.00');

    // ⚠️ And the settled booking's total is NOT presented as a balance due — the old impossible figure.
    expect(FolioTotals::for($settledFolio->fresh()->load(['items', 'payments']))['balance_minor'])->toBe(0);
});
