<?php

declare(strict_types=1);

use App\Events\DomainEventRecorded;
use App\Models\Fee;
use App\Models\Guest;
use App\Models\PropertyAssignment;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\UnitRateOverride;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE NEW RESERVATION WIZARD (2026-10-04, the founder: "on this page there is functionality issues. it is not possible to do
// reservation. Fix it … find out more issues on this page and fix everything"). 🔬 Read-only on production: his workspace's first
// property has no rooms (the wizard said "No units are free"), the other has rooms with no price on any of the next 60 nights
// (the wizard's only way out was a link that threw the whole booking away), and the wizard hides the app's property switcher.
// Each test below failed first against the page as it stood. Test clock: 1 Aug 2026; the stay is 2–5 Sep (3 nights).

const NRW_STAY = '?check_in=2026-09-02&check_out=2026-09-05';

/** A scenario, priced (every night of September) or not. */
function nrwScenario(int $units = 2, ?int $nightlyMinor = 10000, string $currency = 'EUR'): array
{
    $s = seedScenario($units);
    if ($currency !== 'EUR') {
        $s['property']->update(['currency' => $currency]);
        RatePlan::query()->whereKey($s['ratePlan']->id)->update(['currency' => $currency]);
        $s['ratePlan'] = $s['ratePlan']->fresh();
    }
    if ($nightlyMinor !== null) {
        app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-30', Money::of($nightlyMinor, $currency));
    }

    return $s;
}

function nrwPage(User $user, string $query = NRW_STAY, array $session = []): string
{
    return (string) test()->actingAs($user)->withSession($session)->get(route('app.reservations.create').$query)->assertOk()->getContent();
}

function nrwBooking(array $s, array $overrides = []): array
{
    return array_merge(['check_in' => '2026-09-02', 'check_out' => '2026-09-05', 'room_type_id' => $s['roomType']->id,
        'unit_id' => $s['units'][0]->id, 'guest_first_name' => 'Grace', 'guest_last_name' => 'Hopper', 'adults' => '2',
        'children' => '0', 'source' => 'direct'], $overrides);
}

// ── No price yet — set it here, never a dead end ────────────────────────────────────────────────

it('⚠️⚠️ lets a person who manages rates set the price for the stay’s nights in the wizard, and comes back ready to book', function () {
    $s = nrwScenario(2, null);
    $manager = operatorUser($s, [], 'Hotel Manager');

    // 🔬 before: "2 free — no rate set. Set a rate first →", a link to the Rates page that threw the booking away
    $html = nrwPage($manager);
    expect($html)->toContain('action="'.route('app.rates.update').'"')
        ->toContain('<label class="vy-nr-label" for="vy-nr-rate-0">Price a night</label>')   // the visible field, joined to its form
        ->toContain('form="vy-nr-price-0" type="text" name="rate"')
        ->toContain('name="return" value="new-reservation"')
        ->toContain('name="start_date" value="2026-09-02"')
        ->toContain('name="end_date" value="2026-09-04"')   // the last night, not the check-out day
        ->toContain('name="set_rate" value="1"');

    test()->actingAs($manager)->post(route('app.rates.update'), ['room_type_id' => $s['roomType']->id, 'start_date' => '2026-09-02',
        'end_date' => '2026-09-04', 'set_rate' => '1', 'rate' => '120', 'return' => 'new-reservation',
        'return_check_in' => '2026-09-02', 'return_check_out' => '2026-09-05'])
        ->assertRedirect(route('app.reservations.create', ['check_in' => '2026-09-02', 'check_out' => '2026-09-05']))
        ->assertSessionHas('new_reservation_ok', 'Price set — €120.00 a night for these 3 nights. Pick a unit to continue.');

    // now bookable: the units are offered at that price
    expect(nrwPage($manager))->toContain((string) $s['units'][0]->code)->toContain('€120.00 a night');
});

it('tells a person who cannot set prices who can — no dead link, no form', function () {
    $s = nrwScenario(2, null);
    $html = nrwPage(operatorUser($s, [], 'Front Desk'));

    expect($html)->toContain('No price is set for these nights')
        ->toContain('someone who manages rates')
        ->not->toContain('action="'.route('app.rates.update').'"');
});

it('brings a refused price back to the wizard, not to the Rates page', function () {
    $s = nrwScenario(2, null);

    test()->actingAs(operatorUser($s, [], 'Hotel Manager'))->post(route('app.rates.update'), ['room_type_id' => $s['roomType']->id,
        'start_date' => '2026-09-02', 'end_date' => '2026-09-04', 'set_rate' => '1', 'rate' => 'abc', 'return' => 'new-reservation',
        'return_check_in' => '2026-09-02', 'return_check_out' => '2026-09-05'])
        ->assertRedirect(route('app.reservations.create', ['check_in' => '2026-09-02', 'check_out' => '2026-09-05']))
        ->assertSessionHas('new_reservation_notice', 'Enter a valid nightly rate, e.g. 175 or 175.50.');
});

// ── The property — which one, and what it has ───────────────────────────────────────────────────

it('⚠️⚠️ asks which property when “All properties” is selected — a booking belongs to one', function () {
    $s = nrwScenario(2);
    $far = secondProperty($s, 'Villa Azure');
    $html = nrwPage(operatorUser($s, [], 'Hotel Manager'), NRW_STAY, ['current_property_id' => 'all']);

    // 🔬 before: it silently used the first property — on production, one with no rooms
    expect($html)->toContain('Which property is this booking for?')
        ->toContain('action="'.route('app.property.switch').'"')
        ->toContain('value="'.$far->id.'"')
        ->toContain('No rooms yet')     // the second property has none — said before it is chosen
        ->toContain('2 rooms')
        ->not->toContain('class="vy-nr-cal"');
});

it('⚠️ says a property has no rooms yet, instead of “no units are free”', function () {
    $s = nrwScenario(2);
    $far = secondProperty($s, 'Internal Test Property');
    $html = nrwPage(operatorUser($s, [], 'Hotel Manager'), NRW_STAY, ['current_property_id' => (string) $far->id]);

    expect($html)->toContain(e($far->name).' has no rooms yet')
        ->not->toContain('No units are free for those dates');
});

it('shows which property you are booking into, and lets you switch', function () {
    $s = nrwScenario(2);
    $far = secondProperty($s, 'Villa Azure');
    $html = nrwPage(operatorUser($s, [], 'Hotel Manager'), NRW_STAY, ['current_property_id' => (string) $s['property']->id]);

    expect($html)->toContain('action="'.route('app.property.switch').'"')
        ->toContain('value="'.$far->id.'"');
});

// ── A refusal keeps the booking ─────────────────────────────────────────────────────────────────

it('⚠️⚠️ keeps everything entered when a booking is refused, and reopens at the step that needs fixing', function () {
    $s = nrwScenario(2);
    $manager = operatorUser($s, [], 'Hotel Manager');

    $html = (string) test()->actingAs($manager)->from(route('app.reservations.create').NRW_STAY)->followingRedirects()
        ->post(route('app.reservations.store'), nrwBooking($s, ['payment_method' => 'cash', 'payment_amount' => 'abc', 'arrival_window' => '3–6 PM']))
        ->assertOk()->getContent();

    // 🔬 before: back at step 1 with every step empty — the draft was cleared on Confirm and nothing came back
    expect($html)->toContain('Enter a valid payment amount')
        ->toContain('"step":4')   // Confirm — it was step 7 of seven (2026-10-04)
        ->toContain('"newFirst":"Grace"')
        ->toContain('"unitId":"'.$s['units'][0]->id.'"')
        ->toContain('"arrivalWindow":"3–6 PM"');
});

it('never echoes a new guest’s email or phone back after a refusal — not even into the session', function () {
    $s = nrwScenario(2);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $contact = ['guest_email' => 'grace.secret@example.test', 'guest_phone' => '+351 910 555 123', 'payment_method' => 'cash', 'payment_amount' => 'abc'];

    test()->actingAs($manager)->post(route('app.reservations.store'), nrwBooking($s, $contact))
        ->assertSessionHasInput('guest_first_name', 'Grace')
        ->assertSessionHas('new_reservation_contact_cleared', true);
    expect(session()->getOldInput())->not->toHaveKey('guest_email')->not->toHaveKey('guest_phone');

    $html = (string) test()->actingAs($manager)->from(route('app.reservations.create').NRW_STAY)->followingRedirects()
        ->post(route('app.reservations.store'), nrwBooking($s, $contact))
        ->assertOk()->getContent();

    expect($html)->not->toContain('grace.secret@example.test')->not->toContain('910 555 123')
        ->toContain('The email and phone you typed weren’t kept when the booking came back — enter them again.');
});

it('⚠️ reopens with the override and the whole booking after a restricted date is refused', function () {
    $s = nrwScenario(2);
    app(RateAndRestrictionService::class)->setRestriction(['room_type_id' => (string) $s['roomType']->id, 'min_stay' => 5], '2026-09-02', '2026-09-03');

    $html = (string) test()->actingAs(operatorUser($s, [], 'Hotel Manager'))->from(route('app.reservations.create').NRW_STAY)->followingRedirects()
        ->post(route('app.reservations.store'), nrwBooking($s))
        ->assertOk()->getContent();

    // 🔬 before: the override appeared over an empty wizard, so choosing a reason resubmitted no unit and no guest
    expect($html)->toContain('name="restriction_override_reason"')
        ->toContain('"unitId":"'.$s['units'][0]->id.'"')
        ->toContain('"newFirst":"Grace"');
    expect(Reservation::query()->count())->toBe(0);
});

// ── Money, keyboard, design ─────────────────────────────────────────────────────────────────────

it('⚠️ shows prices in the property’s own currency, a unit’s price a night and how many it sleeps', function () {
    $s = nrwScenario(2, 9000, 'GBP');
    $html = nrwPage(operatorUser($s, [], 'Hotel Manager'));

    // 🔬 before: the summary always printed "€", and the unit's price was read back out of the displayed text
    expect($html)->toContain('£90.00 a night')
        ->toContain('Sleeps '.(int) $s['units'][0]->fresh()?->max_occupancy)   // the column's default, read back
        ->toContain('"currency":"GBP"');
    $source = (string) file_get_contents(resource_path('views/livewire/operator/new-reservation.blade.php'));
    expect($source)->not->toContain("'€' +")->not->toContain("preg_replace('/[^0-9]/'");
});

it('lets the keyboard pick a unit — the choice is a real, focusable radio', function () {
    $s = nrwScenario(2);
    $html = nrwPage(operatorUser($s, [], 'Hotel Manager'));

    // 🔬 before: `style="display:none;"` — a radio no keyboard could reach
    expect($html)->toMatch('/<input type="radio" class="vy-sr-only" name="unit_pick"/');
});

it('⚠️ uses no inline style — every display is a class, and every class has a rule', function () {
    $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents(resource_path('views/livewire/operator/new-reservation.blade.php')));
    expect(substr_count($source, 'style="'))->toBe(0);

    $css = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    preg_match_all('/<style>(.*?)<\/style>/s', $source, $styles);
    $css .= implode("\n", $styles[1]);
    preg_match_all('/\sclass="([^"]*)"/', $source, $m);
    $missing = [];
    foreach ($m[1] as $attr) {
        $attr = (string) preg_replace('/\{\{.*?\}\}|@[a-z]+\s*\(.*?\)|@end[a-z]+/s', ' ', $attr);
        foreach (preg_split('/\s+/', $attr, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            if (! str_ends_with($token, '-') && preg_match('/^vyn?[a-z]*-[a-z0-9-]+$/', $token)
                && preg_match('/\.'.preg_quote($token, '/').'(?![a-zA-Z0-9_-])/', $css) !== 1) {
                $missing[] = $token;
            }
        }
    }
    expect(array_values(array_unique($missing)))->toBe([]);
});

// ── Found while walking the page (2026-10-04) ────────────────────────────────────────────────────

it('⚠️⚠️ never sends a booking to an error page when "Other" has no note — it comes back to the override', function () {
    $s = nrwScenario(2);
    app(RateAndRestrictionService::class)->setRestriction(['room_type_id' => (string) $s['roomType']->id, 'min_stay' => 5], '2026-09-02', '2026-09-03');
    $seen = json_encode([['restriction' => 'min_stay', 'date' => '2026-09-02', 'value' => 5]]);

    // 🔬 before: the service threw an InvalidArgumentException nothing caught — a 500 for the person told to choose a reason
    $html = (string) test()->actingAs(operatorUser($s, [], 'Hotel Manager'))->from(route('app.reservations.create').NRW_STAY)->followingRedirects()
        ->post(route('app.reservations.store'), nrwBooking($s, ['restriction_override_reason' => 'other', 'restriction_override_note' => ' ',
            'restriction_override_seen' => $seen]))
        ->assertOk()->getContent();

    expect($html)->toContain('Say why in the note — it’s needed when you choose Other.')
        ->toContain('name="restriction_override_reason"')
        ->toContain('Arriving Wed 2 Sep, a stay must be at least 5 nights')
        ->toContain('"newFirst":"Grace"');
    expect(Reservation::query()->count())->toBe(0);
});

it('refuses an unknown override reason without an error page', function () {
    $s = nrwScenario(2);
    app(RateAndRestrictionService::class)->setRestriction(['room_type_id' => (string) $s['roomType']->id, 'min_stay' => 5], '2026-09-02', '2026-09-03');

    test()->actingAs(operatorUser($s, [], 'Hotel Manager'))
        ->post(route('app.reservations.store'), nrwBooking($s, ['restriction_override_reason' => 'because-i-said-so']))
        ->assertRedirect()
        ->assertSessionHas('new_reservation_notice', 'Choose a reason from the list to book anyway.');
    expect(Reservation::query()->count())->toBe(0);
});

it('says a restricted booking was refused in plain words, not the engine’s', function () {
    $s = nrwScenario(2);
    app(RateAndRestrictionService::class)->setRestriction(['room_type_id' => (string) $s['roomType']->id, 'min_stay' => 5], '2026-09-02', '2026-09-03');

    // 🔬 before: "A minimum stay of 5 night(s) applies to arrivals on 2026-09-02 — this booking is 3 night(s)."
    test()->actingAs(operatorUser($s, [], 'Hotel Manager'))->post(route('app.reservations.store'), nrwBooking($s))
        ->assertSessionHas('new_reservation_notice', 'This booking wasn’t made — these dates are restricted. Book anyway with a reason below, or change the dates in step 1.')
        ->assertSessionHas('new_reservation_step', 4);   // Confirm — it was step 7 of seven (2026-10-04)
});

it('⚠️ shows a unit’s own price — the one the booking charges', function () {
    $s = nrwScenario(2);   // €100 a night for the room type
    UnitRateOverride::create(['organization_id' => $s['org']->id, 'rate_plan_id' => $s['ratePlan']->id, 'unit_id' => $s['units'][0]->id,
        'date' => '2026-09-03', 'price_minor' => 15000, 'currency' => 'EUR']);
    $manager = operatorUser($s, [], 'Hotel Manager');

    // 🔬 before: every unit showed the room type's €300.00 while the folio charged unit 1 its own €350.00
    expect(nrwPage($manager))->toContain('Own price · €350.00')
        ->toContain('"unitPrices":{"'.$s['ratePlan']->id.'":{"'.$s['units'][0]->id.'":35000}}');

    test()->actingAs($manager)->post(route('app.reservations.store'), nrwBooking($s))->assertRedirect();
    expect((int) Reservation::query()->firstOrFail()->total_minor)->toBe(35000);
});

it('⚠️ estimates with the same taxes and fees the folio charges — no included VAT, no optional fee, and the cleaning fee', function () {
    $s = nrwScenario(1);
    $pid = $s['property']->id;
    Tax::create(['property_id' => $pid, 'name' => 'VAT included', 'mode' => 'percentage', 'rate' => 6, 'inclusive' => true, 'currency' => 'EUR']);
    Tax::create(['property_id' => $pid, 'name' => 'City tax', 'mode' => 'per_person_night', 'amount_minor' => 200, 'currency' => 'EUR']);
    Fee::create(['property_id' => $pid, 'name' => 'Cleaning', 'kind' => 'cleaning', 'mode' => 'flat', 'amount_minor' => 4500, 'currency' => 'EUR', 'optional' => false]);
    Fee::create(['property_id' => $pid, 'name' => 'Late check-out', 'kind' => 'other', 'mode' => 'flat', 'amount_minor' => 2000, 'currency' => 'EUR', 'optional' => true]);

    // 🔬 before: the estimate added every tax (an included VAT counted twice) and no fee at all
    $html = nrwPage(operatorUser($s, [], 'Hotel Manager'));
    expect($html)->toContain('"name":"City tax"')->toContain('"name":"Cleaning"')
        ->not->toContain('"name":"VAT included"')->not->toContain('"name":"Late check-out"');
});

it('⚠️⚠️ leaves no guest behind when a booking is refused — and never touches an existing one', function () {
    $s = nrwScenario(2);
    app(RateAndRestrictionService::class)->setRestriction(['room_type_id' => (string) $s['roomType']->id, 'min_stay' => 5], '2026-09-02', '2026-09-03');
    $manager = operatorUser($s, [], 'Hotel Manager');
    $before = Guest::withTrashed()->count();

    // 🔬 before: every refusal left the new guest's record — name, email, phone — with no stay at all
    test()->actingAs($manager)->post(route('app.reservations.store'), nrwBooking($s, ['guest_email' => 'orphan@example.test']))
        ->assertSessionHas('restriction_breaches');
    expect(Guest::withTrashed()->count())->toBe($before);

    test()->actingAs($manager)->post(route('app.reservations.store'), nrwBooking($s, ['existing_guest_id' => $s['guest']->id]))
        ->assertSessionHas('restriction_breaches');
    expect(Guest::query()->whereKey($s['guest']->id)->exists())->toBeTrue();
});

it('tells same-name guests apart by their stays — never by a contact detail', function () {
    $s = nrwScenario(2);
    Guest::create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada.one@example.test']);
    $returning = Guest::create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada.two@example.test']);
    book($s, ['unit_id' => $s['units'][1]->id]);
    Reservation::query()->latest('created_at')->firstOrFail()->update(['lead_guest_id' => $returning->id]);

    $html = nrwPage(operatorUser($s, [], 'Front Desk'), NRW_STAY.'&q=Lovelace');

    // 🔬 before: two identical "Ada Lovelace" rows and a "Returning" tag on both, with nothing to choose between them
    expect($html)->toContain('No stays yet')->toContain('1 stay · guest since 2026')
        ->not->toContain('ada.one@example.test')->not->toContain('ada.two@example.test');
});

// ── The independent review (2026-10-04) ──────────────────────────────────────────────────────────

/** The wizard's data block, decoded — what its script reads. */
function nrwBoot(string $html): array
{
    preg_match('~<script type="application/json" id="vy-nr-boot">(.*?)</script>~s', $html, $m);

    return (array) json_decode($m[1] ?? 'null', true);
}

it('⚠️⚠️ never strips a saved booking of its new guest when something after the save fails', function () {
    $s = nrwScenario(2);
    Event::listen(DomainEventRecorded::class, function (DomainEventRecorded $e): void {
        if ($e->domainEvent->event_type === 'reservation.confirmed') {
            throw new RuntimeException('a listener after the save failed');
        }
    });

    // 🔬 before: the refusal clean-up removed the guest — and the saved booking's lead guest went to NULL with it (nullOnDelete)
    test()->actingAs(operatorUser($s, [], 'Hotel Manager'))->post(route('app.reservations.store'), nrwBooking($s))->assertServerError();

    $reservation = Reservation::query()->firstOrFail();   // saved: the failure came after the commit
    expect($reservation->lead_guest_id)->not->toBeNull()
        ->and(Guest::query()->whereKey($reservation->lead_guest_id)->exists())->toBeTrue();
});

it('says when a property’s rooms are all closed, not that it has none', function () {
    $s = nrwScenario(2);
    $far = secondProperty($s, 'Shut House');
    $rt = RoomType::create(['property_id' => $far->id, 'name' => 'Shut', 'kind' => 'room']);
    Unit::create(['property_id' => $far->id, 'room_type_id' => $rt->id, 'code' => 'S1', 'is_active' => false]);

    $html = nrwPage(operatorUser($s, [], 'Hotel Manager'), NRW_STAY, ['current_property_id' => 'all']);

    expect($html)->toContain('No rooms open for booking')->not->toContain('No rooms yet');
});

it('says a stay is too long instead of “nothing is free”', function () {
    $s = nrwScenario(2);

    $manager = operatorUser($s, [], 'Hotel Manager');
    $html = nrwPage($manager, '?check_in=2026-09-01&check_out=2026-12-15');

    expect($html)->toContain('A booking can be at most 90 nights')->not->toContain('Nothing is free for');
    test()->actingAs($manager)->post(route('app.reservations.store'), nrwBooking($s, ['check_in' => '2026-09-01', 'check_out' => '2026-12-15']))
        ->assertSessionHas('new_reservation_notice', 'A booking can be at most 90 nights — pick a shorter stay.');
});

it('⚠️ shows the done card only for a booking in a property the person works in', function () {
    $s = nrwScenario(1);
    $far = secondProperty($s, 'Far House');
    $rt = RoomType::create(['property_id' => $far->id, 'name' => 'Far', 'kind' => 'room']);
    $unit = Unit::create(['property_id' => $far->id, 'room_type_id' => $rt->id, 'code' => 'F1']);
    $plan = RatePlan::create(['property_id' => $far->id, 'room_type_id' => $rt->id, 'name' => 'Flex', 'currency' => 'EUR']);
    app(AvailabilityService::class)->recompute((string) $rt->id, '2026-09-01', '2026-09-10');
    $booking = book(['property' => $far, 'roomType' => $rt, 'ratePlan' => $plan, 'guest' => $s['guest'], 'units' => [$unit]]);

    $desk = operatorUser($s, [], 'Front Desk');
    PropertyAssignment::create(['organization_id' => $s['org']->id, 'user_id' => $desk->id, 'property_id' => $s['property']->id]);

    // 🔬 before: any reservation of the organisation — its guest's name, reference and check-in code — by its id
    test()->actingAs($desk)->get(route('app.reservations.create', ['created' => $booking->id]))
        ->assertOk()->assertDontSee('Self check-in code')->assertDontSee((string) $booking->reference);
});

it('turns hand-made array inputs into a refusal, never an error page', function () {
    $s = nrwScenario(2);
    app(RateAndRestrictionService::class)->setRestriction(['room_type_id' => (string) $s['roomType']->id, 'min_stay' => 5], '2026-09-02', '2026-09-03');
    $manager = operatorUser($s, [], 'Hotel Manager');

    foreach ([['restriction_override_reason' => ['regular_guest']], ['existing_guest_id' => ['x']],
        ['restriction_override_reason' => 'regular_guest', 'restriction_override_seen' => ['not', 'json']],
        ['restriction_override_reason' => 'regular_guest', 'restriction_override_seen' => '[1, "x", {"restriction": "min_stay"}]'],
        ['restriction_override_reason' => 'regular_guest', 'restriction_override_seen' => '[{"restriction": ["min_stay"], "date": "2026-09-02", "value": 5}]'],
        ['restriction_override_reason' => 'regular_guest', 'restriction_override_seen' => '[{"date": "2026-09-02"}]']] as $crafted) {
        test()->actingAs($manager)->post(route('app.reservations.store'), nrwBooking($s, $crafted))->assertRedirect();
    }
    expect(Reservation::query()->count())->toBe(0);
});

it('⚠️ estimates with the taxes of the stay’s own currency', function () {
    $s = nrwScenario(1, null);
    RatePlan::query()->whereKey($s['ratePlan']->id)->update(['currency' => 'GBP']);   // a plan priced apart from the property
    app(RateAndRestrictionService::class)->setRate($s['ratePlan']->fresh(), '2026-09-01', '2026-09-30', Money::of(9000, 'GBP'));
    $pid = $s['property']->id;
    Tax::create(['property_id' => $pid, 'name' => 'Any currency', 'mode' => 'per_night', 'amount_minor' => 100, 'currency' => null]);
    Tax::create(['property_id' => $pid, 'name' => 'Pounds only', 'mode' => 'per_night', 'amount_minor' => 200, 'currency' => 'GBP']);
    Tax::create(['property_id' => $pid, 'name' => 'Euros only', 'mode' => 'per_night', 'amount_minor' => 300, 'currency' => 'EUR']);

    // 🔬 before: filtered by the PROPERTY's currency (EUR) — the folio posts by the STAY's (GBP)
    $taxes = nrwBoot(nrwPage(operatorUser($s, [], 'Hotel Manager')))['taxes']['GBP'] ?? null;
    expect(array_column((array) $taxes, 'name'))->toBe(['Any currency', 'Pounds only']);
});

it('only says “pick a unit” when the price went on the plan the wizard books', function () {
    $s = nrwScenario(2, null);
    $second = RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id, 'name' => 'Second', 'currency' => 'EUR']);

    test()->actingAs(operatorUser($s, [], 'Hotel Manager'))->post(route('app.rates.update'), ['room_type_id' => $s['roomType']->id,
        'rate_plan_id' => $second->id, 'start_date' => '2026-09-02', 'end_date' => '2026-09-04', 'set_rate' => '1', 'rate' => '120',
        'return' => 'new-reservation', 'return_check_in' => '2026-09-02', 'return_check_out' => '2026-09-05'])
        ->assertSessionHas('new_reservation_ok', 'Updated the nightly price on Second for 1 room type across 3 nights.');
});

it('never restores a unit from another property out of a refused booking', function () {
    $s = nrwScenario(2);
    $far = secondProperty($s, 'Far House');
    $rt = RoomType::create(['property_id' => $far->id, 'name' => 'Far', 'kind' => 'room']);
    $farUnit = Unit::create(['property_id' => $far->id, 'room_type_id' => $rt->id, 'code' => 'FARUNIT9']);

    $html = (string) test()->actingAs(operatorUser($s, [], 'Hotel Manager'))
        ->withSession(['_old_input' => ['room_type_id' => (string) $rt->id, 'unit_id' => (string) $farUnit->id, 'guest_first_name' => 'Grace'],
            'new_reservation_step' => 4])
        ->get(route('app.reservations.create').NRW_STAY)->assertOk()->getContent();

    $restore = nrwBoot($html)['restore'];
    expect($restore['unitId'])->toBe('')->and($restore['step'])->toBe(1)->and($restore['newFirst'])->toBe('Grace');
});
