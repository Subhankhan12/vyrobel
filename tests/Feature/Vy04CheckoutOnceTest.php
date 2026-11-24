<?php

declare(strict_types=1);

use App\Models\BookingQuote;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ⚠️⚠️ VY-04 (audit of 2026-11-24, reproduced) — ONE CHECKOUT PAGE MAKES ONE BOOKING.
//
// The same checkout form posted twice produced 2 confirmed reservations and took both units: every POST minted a new quote and booked
// it. The button already locks itself on the first press (`data-guard-submit`), but that is the browser's promise, not the server's — a
// double click that beats it, a retry after a timeout, a re-sent request all arrive as a second POST.
//
// The page now carries a ONE-TIME token and the quote stores it under a UNIQUE index: the first POST claims it, and a repeat of the same
// page is sent to the booking the first one made. It books nothing more.
//
// ⚠️ EACH REFUSAL HAS ITS CONTROL: two DIFFERENT pages (two tokens) still make two bookings, so "one booking" is the token and not a
// form that books only once ever.

uses(DatabaseTransactions::class);

function vy04Body(array $s, array $overrides = []): array
{
    return array_merge([
        'room_type_id' => $s['roomType']->id,
        'rate_plan_id' => $s['ratePlan']->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',
        'adults' => 2,
        'children' => 0,
        'first_name' => 'Zephyrina',
        'last_name' => 'Quibblewick',
        'email' => 'zephyrina.quibblewick@guestmail.dev',
        'payment_option' => 'on_arrival',
    ], acceptedTerms($s['property']), checkoutFormToken(), $overrides);
}

function vy04Bookings(array $s): int
{
    return Reservation::query()->where('property_id', $s['property']->id)->count();
}

it('⚠️⚠️ VY-04 (as reproduced): the same checkout form posted twice makes ONE booking and takes ONE unit', function () {
    $s = publicPropertyScenario(units: 2);
    config(['services.payments.gateway' => 'manual']);
    $url = "/book/{$s['property']->slug}/checkout";
    $body = vy04Body($s);   // ONE page: one token

    $first = test()->post($url, $body)->assertRedirect();
    $second = test()->post($url, $body)->assertRedirect();

    // one booking, one unit — and the repeat lands on the SAME confirmation, never an error and never a second reference
    expect(vy04Bookings($s))->toBe(1)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1)
        ->and(BookingQuote::query()->where('property_id', $s['property']->id)->count())->toBe(1)
        ->and($second->headers->get('Location'))->toBe($first->headers->get('Location'));

    test()->get((string) $second->headers->get('Location'))->assertOk()
        ->assertSee(Reservation::query()->where('property_id', $s['property']->id)->firstOrFail()->reference);

    // a third and a fourth press change nothing either
    test()->post($url, $body)->assertRedirect();
    test()->post($url, $body)->assertRedirect();
    expect(vy04Bookings($s))->toBe(1)->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1);
});

it('VY-04 CONTROL: two different checkout pages (two tokens) still make two bookings', function () {
    $s = publicPropertyScenario(units: 2);
    config(['services.payments.gateway' => 'manual']);
    $url = "/book/{$s['property']->slug}/checkout";

    test()->post($url, vy04Body($s, ['email' => 'first@guestmail.dev']))->assertRedirect();
    test()->post($url, vy04Body($s, ['email' => 'second@guestmail.dev']))->assertRedirect();

    expect(vy04Bookings($s))->toBe(2)->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(2);
});

it('⚠️ VY-04: a form with NO token, or a malformed one, books nothing — the guest is sent back to a fresh page with their details', function (mixed $token) {
    $s = publicPropertyScenario(units: 2);
    config(['services.payments.gateway' => 'manual']);
    $url = "/book/{$s['property']->slug}/checkout";
    $body = vy04Body($s);
    if ($token === null) {
        unset($body['checkout_token']);
    } else {
        $body['checkout_token'] = $token;
    }

    test()->from($url.'?room_type_id='.$s['roomType']->id)->post($url, $body)
        ->assertRedirect()
        ->assertSessionHas('booking_error')
        ->assertSessionHasInput('first_name', 'Zephyrina');

    expect(vy04Bookings($s))->toBe(0)
        ->and(BookingQuote::query()->where('property_id', $s['property']->id)->count())->toBe(0)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);
})->with([
    'missing' => [null],
    'empty' => [''],
    'too short' => ['abc'],
    'not letters and digits' => [str_repeat('<', 40)],
    'an array' => [['x']],
]);

it('VY-04: the checkout page prints a fresh token every time it is shown', function () {
    $s = publicPropertyScenario(units: 1);
    $url = "/book/{$s['property']->slug}/checkout?room_type_id={$s['roomType']->id}&check_in=2026-09-01&check_out=2026-09-03&adults=2";

    $token = function (string $html): string {
        expect(preg_match('~<input type="hidden" name="checkout_token" value="([A-Za-z0-9]{40})">~', $html, $m))->toBe(1);

        return $m[1];
    };

    $a = $token(test()->get($url)->assertOk()->getContent());
    $b = $token(test()->get($url)->assertOk()->getContent());

    expect($a)->not->toBe($b);
});

it('⚠️ VY-04: a booking that could not be made does NOT use the page up — the guest may try the same page again', function () {
    $s = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);
    $url = "/book/{$s['property']->slug}/checkout";

    // somebody else takes the only unit first
    test()->post($url, vy04Body($s, ['email' => 'first@guestmail.dev']))->assertRedirect();

    // this guest's page: refused honestly (the room was just taken) — twice the same answer, never a confirmation of the other booking
    $body = vy04Body($s, ['email' => 'late@guestmail.dev']);
    foreach ([1, 2] as $attempt) {
        $response = test()->post($url, $body)->assertRedirect();
        expect($response->headers->get('Location'))->toContain('/checkout')
            ->and(session('booking_error'))->not->toBeNull();
    }

    expect(vy04Bookings($s))->toBe(1)
        ->and(DB::table('booking_quotes')->where('checkout_token', $body['checkout_token'])->count())->toBe(0);   // released, not burnt
});

it('⚠️ VY-04: one token cannot be replayed on ANOTHER property — the guest is never sent to the first property\'s booking', function () {
    $x = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);
    $body = vy04Body($x);
    test()->post("/book/{$x['property']->slug}/checkout", $body)->assertRedirect();
    $firstQuote = BookingQuote::query()->where('checkout_token', $body['checkout_token'])->firstOrFail();

    // a SECOND property of the SAME organisation (the tenant scope does not separate them — only the property does)
    $second = Property::create(['name' => 'Second Public', 'slug' => 'public-'.Str::lower(Str::random(8)), 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC', 'status' => 'active']);
    $rt = RoomType::create(['property_id' => $second->id, 'name' => 'Std', 'kind' => 'room', 'max_adults' => 2, 'max_children' => 2]);
    Unit::create(['property_id' => $second->id, 'room_type_id' => $rt->id, 'code' => 'S1']);
    $plan = RatePlan::create(['property_id' => $second->id, 'room_type_id' => $rt->id, 'name' => 'Flex', 'currency' => 'EUR']);
    app(AvailabilityService::class)->recompute($rt->id, '2026-09-01', '2026-09-10');
    app(RateAndRestrictionService::class)->setRate($plan, '2026-09-01', '2026-09-10', Money::of(12000, 'EUR'));
    $y = ['property' => $second, 'roomType' => $rt, 'ratePlan' => $plan];

    $replay = vy04Body($y, ['checkout_token' => $body['checkout_token']]);
    $response = test()->from("/book/{$second->slug}/checkout")->post("/book/{$second->slug}/checkout", $replay)->assertRedirect();

    // a stale page on the second property: an error, their details kept, nothing booked there — and NOT a redirect to the first booking
    expect((string) $response->headers->get('Location'))->not->toContain((string) $firstQuote->getKey())
        ->and((string) $response->headers->get('Location'))->not->toContain('/reservation/')
        ->and(session('booking_error'))->not->toBeNull()
        ->and(vy04Bookings($y))->toBe(0)
        ->and(vy04Bookings($x))->toBe(1);
});

it('VY-04: the token is a random 40-character page token, not derived from the booking', function () {
    // the helper mirrors the page; this pins the shape the controller accepts
    expect(checkoutFormToken()['checkout_token'])->toMatch('/^[A-Za-z0-9]{40}$/')
        ->and(Str::length(checkoutFormToken()['checkout_token']))->toBe(40);
});
