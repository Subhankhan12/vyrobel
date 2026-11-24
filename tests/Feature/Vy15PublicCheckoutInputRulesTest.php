<?php

declare(strict_types=1);

use App\Exceptions\Booking\StayNotBookableException;
use App\Models\BookingQuote;
use App\Models\Reservation;
use App\Services\BookingEngineService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// ⚠️⚠️ VY-15 (audit of 2026-11-24, reproduced) — THE PUBLIC CHECKOUT REFUSES A STAY IT COULD NEVER HAVE OFFERED.
//
// Three things the pages never offered were accepted when POSTED: a stay in the past (2–4 July booked with the clock at 1 August),
// more guests than the room takes (nine adults and seven children into a room for 2 + 2), and a rate plan the operator had switched
// off. The search hides all three; the two write paths (the web checkout and the JSON API) checked none.
//
// The rules now live in the ONE place both paths create a quote — BookingEngineService::quote() — and are checked again when a quote
// is turned into a booking, so an old quote cannot carry a stale stay past them.
//
// ⚠️ EACH REFUSAL HAS ITS CONTROL: the same request with a valid stay still books.

uses(DatabaseTransactions::class);

function vy15Form(array $s, array $overrides = []): array
{
    return array_merge([
        'room_type_id' => $s['roomType']->id,
        'rate_plan_id' => $s['ratePlan']->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',
        'adults' => 2,
        'children' => 0,
        'first_name' => 'Peregrine',
        'last_name' => 'Thistlewood',
        'email' => 'peregrine.vy15@guestmail.dev',
        'payment_option' => 'on_arrival',
    ], acceptedTerms($s['property'], (string) ($overrides['check_in'] ?? '2026-09-01')), checkoutFormToken(), $overrides);
}

function vy15Api(array $s, array $overrides = []): array
{
    return array_merge([
        'room_type_id' => $s['roomType']->id,
        'rate_plan_id' => $s['ratePlan']->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',
        'adults' => 2,
        'children' => 0,
    ], $overrides);
}

function vy15Nothing(array $s): void
{
    expect(Reservation::query()->where('property_id', $s['property']->id)->count())->toBe(0)
        ->and(BookingQuote::query()->where('property_id', $s['property']->id)->count())->toBe(0)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);
}

// ── past dates ──────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ VY-15 (as reproduced): a stay in the past is refused on the web checkout — nothing is booked, and the guest is told why', function () {
    $s = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);
    test()->travelTo('2026-09-05 10:00:00');   // 1–3 September is over

    test()->post("/book/{$s['property']->slug}/checkout", vy15Form($s))
        ->assertRedirect()
        ->assertSessionHas('booking_error', __('php/services/booking_engine_service.stay_in_past'));

    vy15Nothing($s);
});

it('⚠️⚠️ VY-15: a stay in the past is refused on the JSON API with a 422 on the date field', function () {
    $s = publicPropertyScenario(units: 1);
    test()->travelTo('2026-09-05 10:00:00');

    test()->postJson("/book/{$s['property']->slug}/quote", vy15Api($s))
        ->assertStatus(422)->assertJsonValidationErrors(['check_in']);

    vy15Nothing($s);
});

it('⚠️ VY-15: "the past" is judged on the PROPERTY\'S own day, not the server\'s', function () {
    $s = publicPropertyScenario(units: 1);
    $engine = app(BookingEngineService::class);
    test()->travelTo('2026-09-01 22:30:00');   // UTC — already 2 September in Baku (UTC+4)

    // a UTC property: 1 September is still today → bookable
    expect($engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0))->toBeInstanceOf(BookingQuote::class);

    // the same instant at a Baku property: 1 September is yesterday → refused
    $s['property']->forceFill(['timezone' => 'Asia/Baku'])->save();
    expect(fn () => $engine->quote($s['property']->refresh(), $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0))
        ->toThrow(StayNotBookableException::class, __('php/services/booking_engine_service.stay_in_past'));
});

it('⚠️ VY-15: a quote made in time cannot be turned into a booking after its arrival day has passed', function () {
    $s = publicPropertyScenario(units: 1);
    onboardOrgForCards($s);
    $quote = test()->postJson("/book/{$s['property']->slug}/quote", vy15Api($s))->assertCreated()->json('data');

    test()->travelTo('2026-09-05 10:00:00');

    test()->postJson("/book/{$s['property']->slug}/book", array_merge([
        'quote_id' => $quote['quote_id'], 'payment_token' => 'pm_card_visa', 'email' => 'late.vy15@guestmail.dev',
    ], acceptedTerms($s['property'])))->assertStatus(422)->assertJsonValidationErrors(['check_in']);

    expect(Reservation::query()->where('property_id', $s['property']->id)->count())->toBe(0)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);
});

it('⚠️ VY-15: the search offers nothing for dates that have passed (shown = enforced)', function () {
    $s = publicPropertyScenario(units: 1);
    $url = "/book/{$s['property']->slug}/search?check_in=2026-09-01&check_out=2026-09-03&adults=2";

    expect(test()->getJson($url)->assertOk()->json('data'))->toHaveCount(1);   // control: bookable while it is still ahead

    test()->travelTo('2026-09-05 10:00:00');
    expect(test()->getJson($url)->assertOk()->json('data'))->toBe([]);
});

// ── guest counts ────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ VY-15 (as reproduced): nine adults and seven children are refused for a room that takes 2 + 2 — on the web checkout', function () {
    $s = publicPropertyScenario(units: 1);   // max_adults 2, max_children 2
    config(['services.payments.gateway' => 'manual']);

    test()->post("/book/{$s['property']->slug}/checkout", vy15Form($s, ['adults' => 9, 'children' => 7]))
        ->assertRedirect()
        ->assertSessionHas('booking_error', __('php/services/booking_engine_service.too_many_guests', ['adults' => 2, 'children' => 2]));

    vy15Nothing($s);
});

it('⚠️ VY-15: too many adults ALONE, and too many children ALONE, are each refused — on the JSON API', function () {
    $s = publicPropertyScenario(units: 1);

    test()->postJson("/book/{$s['property']->slug}/quote", vy15Api($s, ['adults' => 3]))->assertStatus(422)->assertJsonValidationErrors(['adults']);
    test()->postJson("/book/{$s['property']->slug}/quote", vy15Api($s, ['children' => 3]))->assertStatus(422)->assertJsonValidationErrors(['children']);

    vy15Nothing($s);
});

it('VY-15 CONTROL: the room\'s full capacity (2 + 2) still books', function () {
    $s = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);

    test()->post("/book/{$s['property']->slug}/checkout", vy15Form($s, ['adults' => 2, 'children' => 2]))->assertRedirect()->assertSessionMissing('booking_error');

    expect(Reservation::query()->where('property_id', $s['property']->id)->count())->toBe(1);
});

// ── a switched-off rate plan ────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ VY-15 (as reproduced): a rate plan the operator switched off cannot be booked — web checkout and JSON API', function () {
    $s = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);
    $s['ratePlan']->forceFill(['is_active' => false])->save();

    test()->post("/book/{$s['property']->slug}/checkout", vy15Form($s))
        ->assertRedirect()
        ->assertSessionHas('booking_error', __('php/services/booking_engine_service.plan_unavailable'));
    test()->postJson("/book/{$s['property']->slug}/quote", vy15Api($s))->assertStatus(422)->assertJsonValidationErrors(['rate_plan_id']);

    vy15Nothing($s);
});

it('⚠️ VY-15: a plan switched off AFTER the quote stops that quote from booking', function () {
    $s = publicPropertyScenario(units: 1);
    onboardOrgForCards($s);
    $quote = test()->postJson("/book/{$s['property']->slug}/quote", vy15Api($s))->assertCreated()->json('data');

    $s['ratePlan']->forceFill(['is_active' => false])->save();

    test()->postJson("/book/{$s['property']->slug}/book", array_merge([
        'quote_id' => $quote['quote_id'], 'payment_token' => 'pm_card_visa', 'email' => 'off.vy15@guestmail.dev',
    ], acceptedTerms($s['property'])))->assertStatus(422)->assertJsonValidationErrors(['rate_plan_id']);

    expect(Reservation::query()->where('property_id', $s['property']->id)->count())->toBe(0);
});

// ── the guest fields on the pages ───────────────────────────────────────────────────────────────────────

it('VY-15: the room page caps its guest fields at the room\'s own limits, and its dates at the property\'s today', function () {
    $s = publicPropertyScenario(units: 1);
    $s['roomType']->forceFill(['max_adults' => 3, 'max_children' => 1])->save();

    $html = (string) test()->get("/book/{$s['property']->slug}/rooms/{$s['roomType']->id}")->assertOk()->getContent();

    expect($html)->toMatch('/name="adults"[^>]*max="3"/')
        ->and($html)->toMatch('/name="children"[^>]*max="1"/')
        ->and($html)->toMatch('/name="check_in"[^>]*min="2026-08-01"/');   // the suite's clock
});
