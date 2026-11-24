<?php

declare(strict_types=1);

use App\Models\BookingQuote;
use App\Models\CheckoutSession;
use App\Models\Payment;
use App\Models\PropertyPolicy;
use App\Models\Reservation;
use App\Payments\Stripe\FakeStripeClient;
use App\Support\Booking\BookingTerms;
use App\Support\Booking\StorefrontPayment;
use App\Support\Booking\UnpaidBookingRelease;
use App\Support\Reservations\CancellationReason;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

// ⚠️⚠️ VY-23 (audit of 2026-11-24, read in code) — UNPAID BOOKINGS HELD ROOMS INDEFINITELY.
//
// The storefront books first and then sends the guest to Stripe's page; a guest who closed that page left a CONFIRMED booking holding the
// room for ever (the expiry webhook closed the page's record and nothing else), quotes were never expired by any schedule, and a property
// that ticked "Deposit at booking" took bookings with nothing paid. Now:
//   · the Stripe page closes 31 minutes after it opens; its expiry releases the booking at once, and a five-minute job releases any whose
//     expiry never arrived — never a booking with money in, never one whose page was completed, never one a person already changed;
//   · `booking-quotes:expire` runs every fifteen minutes;
//   · the deposit is real: the storefront charges its share by card, offers no "pay on arrival", and with no card takes no booking.

uses(DatabaseTransactions::class);

/** The storefront checkout body for the scenario's room, 2 nights (2 × 120.00 = 240.00). */
function vy23Body(array $s, array $overrides = []): array
{
    return array_merge([
        'room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id,
        'check_in' => '2026-09-02', 'check_out' => '2026-09-04', 'adults' => 2, 'children' => 0,
        'first_name' => 'Lukas', 'last_name' => 'Meyer', 'email' => 'lukas.'.Str::lower(Str::random(6)).'@example.test',
    ], acceptedTerms($s['property'], '2026-09-02'), checkoutFormToken(), $overrides);
}

/** A sellable storefront on the Stripe gateway whose operator can take cards. */
function vy23World(): array
{
    test()->travelTo('2026-09-01 10:00:00');
    $s = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'stripe']);
    onboardOrgForCards($s);

    return $s;
}

function vy23Webhook(string $type, array $object): TestResponse
{
    $event = ['id' => 'evt_'.Str::lower(Str::random(18)), 'type' => $type, 'livemode' => false, 'data' => ['object' => $object]];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);
    $sig = FakeStripeClient::signature($raw, (string) config('services.stripe.webhook_secret'));

    return test()->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $raw);
}

/** Book by card and return [reservation, pending payment, the page's Stripe id]. */
function vy23BookByCard(array $s): array
{
    test()->post("/book/{$s['property']->slug}/checkout", vy23Body($s))->assertRedirect();
    $payment = Payment::query()->where('status', 'pending')->latest('processed_at')->firstOrFail();

    return [Reservation::query()->findOrFail($payment->reservation_id), $payment, (string) $payment->metadata['checkout_session_id']];
}

it('⚠️⚠️ VY-23 (as read): a card checkout left unpaid gives its room back once its page has closed — the booking is released, the payment marked failed', function () {
    $s = vy23World();
    [$reservation, $payment, $sessionId] = vy23BookByCard($s);
    expect(soldOn($s['roomType']->id, '2026-09-02'))->toBe(1);

    // the page asked Stripe to close itself 31 minutes after it opened (Stripe's least is 30)
    $page = CheckoutSession::query()->where('stripe_session_id', $sessionId)->firstOrFail();
    expect($page->expires_at->timestamp)->toBe(now()->addMinutes(UnpaidBookingRelease::HOLD_MINUTES)->timestamp);

    // while the page is still open, or within the grace after it closed, nothing is released
    $this->travel(UnpaidBookingRelease::HOLD_MINUTES + 5)->minutes();
    Artisan::call('bookings:release-unpaid');
    expect($reservation->fresh()->status)->toBe('confirmed');

    $this->travel(10)->minutes();   // 46 minutes: the page closed 15 minutes ago and Stripe never said so
    Artisan::call('bookings:release-unpaid');

    $fresh = $reservation->fresh();
    expect($fresh->status)->toBe('cancelled')
        ->and($fresh->cancellation_reason)->toBe(CancellationReason::PAYMENT_NOT_COMPLETED)
        ->and($payment->fresh()->status)->toBe('failed')
        ->and(soldOn($s['roomType']->id, '2026-09-02'))->toBe(0);   // the room is bookable again
});

it('⚠️ VY-23: Stripe\'s own "the page expired" releases the booking at once — and a repeat of the same word changes nothing', function () {
    $s = vy23World();
    [$reservation, , $sessionId] = vy23BookByCard($s);

    $object = ['id' => $sessionId, 'object' => 'checkout.session', 'metadata' => ['organization_id' => (string) $s['org']->id, 'reservation_id' => (string) $reservation->id]];
    vy23Webhook('checkout.session.expired', $object)->assertStatus(202);

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and(soldOn($s['roomType']->id, '2026-09-02'))->toBe(0);

    vy23Webhook('checkout.session.expired', $object)->assertStatus(202);   // another delivery: nothing to do, no error
    expect(Reservation::query()->whereKey($reservation->id)->value('status'))->toBe('cancelled');
});

it('VY-23 NEVER: a booking whose payment SUCCEEDED is never released, however late', function () {
    $s = vy23World();
    [$reservation, $payment] = vy23BookByCard($s);
    vy23Webhook('payment_intent.succeeded', ['id' => (string) $payment->gateway_ref, 'object' => 'payment_intent'])->assertStatus(202);

    $this->travel(2)->hours();
    Artisan::call('bookings:release-unpaid');

    expect($reservation->fresh()->status)->toBe('confirmed')->and($reservation->fresh()->payment_status)->toBe('paid');
});

it('VY-23 NEVER: a page that was COMPLETED (a payment still in flight, e.g. a bank debit) is never released', function () {
    $s = vy23World();
    [$reservation, , $sessionId] = vy23BookByCard($s);
    CheckoutSession::query()->where('stripe_session_id', $sessionId)->update(['completed_at' => now()]);

    $this->travel(3)->hours();
    Artisan::call('bookings:release-unpaid');

    expect($reservation->fresh()->status)->toBe('confirmed');
});

it('VY-23 NEVER: a pay-on-arrival booking (no card page at all) is never released by the timer', function () {
    $s = vy23World();
    $this->post("/book/{$s['property']->slug}/checkout", vy23Body($s, ['payment_option' => 'on_arrival']))->assertRedirect();
    $reservation = Reservation::query()->where('property_id', $s['property']->id)->latest('created_at')->firstOrFail();

    $this->travel(3)->days();
    Artisan::call('bookings:release-unpaid');

    expect($reservation->fresh()->status)->toBe('confirmed');
});

it('⚠️ VY-23: quotes past their expiry are marked abandoned on a schedule (nothing called the sweep before)', function () {
    $s = vy23World();
    $this->post("/book/{$s['property']->slug}/quote", [
        'room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-05', 'check_out' => '2026-09-07', 'adults' => 1,
    ])->assertSuccessful();
    $quote = BookingQuote::query()->where('property_id', $s['property']->id)->latest('created_at')->firstOrFail();
    expect($quote->status)->toBe(BookingQuote::STATUS_HELD);

    $this->travel(31)->minutes();
    Artisan::call('booking-quotes:expire');

    expect($quote->fresh()->status)->toBe(BookingQuote::STATUS_ABANDONED);

    $events = collect(app(Schedule::class)->events())->map(fn ($e) => (string) $e->command)->implode("\n");
    expect($events)->toContain('bookings:release-unpaid')->toContain('booking-quotes:expire');
});

it('⚠️⚠️ VY-23 (deposit): a property that asks for a deposit charges its share by card — no "pay on arrival" — and a paid deposit keeps the booking', function () {
    $s = vy23World();
    PropertyPolicy::query()->create(['property_id' => $s['property']->id, 'deposit' => ['required' => true, 'percent' => 30]]);

    $page = $this->get("/book/{$s['property']->slug}/checkout?room_type_id={$s['roomType']->id}&check_in=2026-09-02&check_out=2026-09-04&adults=2")->assertOk();
    $page->assertSee('data-deposit', false)->assertDontSee('value="on_arrival"', false)
        ->assertSee(__('php/support/booking/booking_terms.stay_deposit_percent', ['percent' => 30]));

    // even a hand-made "pay on arrival" is charged the deposit
    $this->post("/book/{$s['property']->slug}/checkout", vy23Body($s, ['payment_option' => 'on_arrival']))->assertRedirect();
    $payment = Payment::query()->where('status', 'pending')->latest('processed_at')->firstOrFail();
    expect((int) $payment->amount_minor)->toBe(7200);   // 30% of 240.00, rounded up

    vy23Webhook('payment_intent.succeeded', ['id' => (string) $payment->gateway_ref, 'object' => 'payment_intent'])->assertStatus(202);
    $reservation = Reservation::query()->findOrFail($payment->reservation_id);
    expect($reservation->payment_status)->toBe('partial');

    $this->travel(2)->hours();
    Artisan::call('bookings:release-unpaid');
    expect($reservation->fresh()->status)->toBe('confirmed');   // a paid deposit is a real booking
});

it('⚠️⚠️ VY-23 (deposit): with no way to take a card, a deposit property takes NO booking — on the page, the form and the JSON endpoint', function () {
    $this->travelTo('2026-09-01 10:00:00');
    $s = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);
    PropertyPolicy::query()->create(['property_id' => $s['property']->id, 'deposit' => ['required' => true, 'percent' => 50]]);

    $this->get("/book/{$s['property']->slug}/checkout?room_type_id={$s['roomType']->id}&check_in=2026-09-02&check_out=2026-09-04&adults=2")
        ->assertOk()->assertSee('data-deposit-unavailable', false)->assertDontSee(__('booking/checkout.confirm_reservation'));

    $this->post("/book/{$s['property']->slug}/checkout", vy23Body($s))
        ->assertSessionHasErrors(['payment_option' => __('php/http/controllers/booking_engine_controller.deposit_unavailable')]);

    $quote = $this->postJson("/book/{$s['property']->slug}/quote", [
        'room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-02', 'check_out' => '2026-09-04', 'adults' => 1,
    ])->assertSuccessful()->json('quote.id') ?? BookingQuote::query()->latest('created_at')->value('id');
    $this->postJson("/book/{$s['property']->slug}/book", ['quote_id' => $quote, 'email' => 'x@example.test', 'terms_accepted' => true])
        ->assertStatus(422)->assertJsonValidationErrors('payment_token');

    expect(Reservation::query()->where('property_id', $s['property']->id)->count())->toBe(0);
});

it('VY-23 CONTROL: a property with no deposit keeps "pay on arrival", and its booking terms version is exactly as before', function () {
    $s = vy23World();
    $before = BookingTerms::for($s['property'], '2026-09-02')['version'];

    $this->get("/book/{$s['property']->slug}/checkout?room_type_id={$s['roomType']->id}&check_in=2026-09-02&check_out=2026-09-04&adults=2")
        ->assertOk()->assertSee('value="on_arrival"', false)->assertDontSee('data-deposit', false);
    expect(BookingTerms::for($s['property'], '2026-09-02')['version'])->toBe($before)
        ->and(StorefrontPayment::depositPercent($s['property']))->toBeNull();
});

it('VY-23: the deposit share is set in Settings → Policies; a box ticked before the share existed reads the default', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Property Owner');

    $this->actingAs($manager)->post('/app/settings/policies', ['pet' => 'allowed', 'smoking' => 'not_allowed', 'min_stay' => '1', 'deposit_required' => '1', 'deposit_percent' => '40'])
        ->assertRedirect()->assertSessionHasNoErrors();
    expect(PropertyPolicy::query()->where('property_id', $s['property']->id)->value('deposit'))->toEqual(['required' => true, 'percent' => 40])
        ->and(StorefrontPayment::depositPercent($s['property']->fresh()))->toBe(40);

    PropertyPolicy::query()->where('property_id', $s['property']->id)->update(['deposit' => json_encode(['required' => true])]);
    expect(StorefrontPayment::depositPercent($s['property']->fresh()))->toBe(StorefrontPayment::DEFAULT_PERCENT);

    $this->actingAs($manager)->post('/app/settings/policies', ['pet' => 'allowed', 'smoking' => 'not_allowed', 'min_stay' => '1', 'deposit_required' => '1', 'deposit_percent' => '0'])
        ->assertSessionHasErrors('deposit_percent');
});

it('VY-23: the deposit share rounds UP and never exceeds the amount', function (int $amount, int $percent, int $expected) {
    expect(StorefrontPayment::depositMinor($amount, $percent))->toBe($expected);
})->with([
    [24000, 30, 7200],
    [10001, 30, 3001],
    [999, 100, 999],
    [0, 30, 0],
]);
