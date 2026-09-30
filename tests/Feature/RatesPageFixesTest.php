<?php

declare(strict_types=1);

use App\Models\Property;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\RateSeason;
use App\Models\Restriction;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE RATES PAGE — WHAT ITS WRITES DO (2026-09-30, the founder: "every single operation should be also working fine …
// test every single thing on this page and fix all of them"; plan approved, DEC-l included). Each test below was written
// against the code as it stood and failed first; the page's look is RatesPageRedesignTest.

/** @return array{s: array<string, mixed>, far: Property, farType: RoomType, farPlan: RatePlan} */
function ratesTwoProperties(): array
{
    $s = seedScenario(1);
    $far = secondProperty($s);
    $farType = RoomType::create(['property_id' => $far->id, 'name' => 'Far Suite', 'kind' => 'room']);
    $farPlan = RatePlan::create(['property_id' => $far->id, 'room_type_id' => $farType->id, 'name' => 'Far Flexible', 'currency' => 'EUR']);

    return ['s' => $s, 'far' => $far, 'farType' => $farType, 'farPlan' => $farPlan];
}

function nightRate(RatePlan $plan, string $date): ?int
{
    $v = RateCalendar::query()->where('rate_plan_id', $plan->id)->whereDate('date', $date)->value('price_minor');

    return $v === null ? null : (int) $v;
}

/** A manager limited to the scenario's own property (the `property_user` allow-list, #38). */
function ratesScopedManager(array $s): User
{
    $user = operatorUser($s);
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $user->id,
        'property_id' => $s['property']->id, 'created_at' => now(), 'updated_at' => now()]);

    return $user;
}

// ── "All room types" means the property on screen ────────────────────────────────────────────────

it('⚠️⚠️ reads "All room types" as the room types on screen — never another property’s', function () {
    $c = ratesTwoProperties();
    $night = CarbonImmutable::today()->addDays(5)->toDateString();
    $manager = operatorUser($c['s']);

    $this->actingAs($manager)->withSession(['current_property_id' => $c['s']['property']->id])
        ->post(route('app.rates.update'), ['room_type_id' => 'all', 'start_date' => $night, 'end_date' => $night, 'set_rate' => '1', 'rate' => '140'])
        ->assertRedirect();
    $this->actingAs($manager)->withSession(['current_property_id' => $c['s']['property']->id])
        ->post(route('app.rates.bulk'), ['room_type_id' => 'all', 'start_date' => $night, 'end_date' => $night, 'mode' => 'set_exact', 'amount' => '150', 'nights' => 'whole'])
        ->assertRedirect();

    expect(nightRate($c['s']['ratePlan'], $night))->toBe(15000)
        ->and(nightRate($c['farPlan'], $night))->toBeNull();
})->group('security');

// ── DEC-l: every rate write stays inside the person's properties ─────────────────────────────────

it('⚠️⚠️ keeps every rate write inside the properties the person may access (DEC-l)', function () {
    $c = ratesTwoProperties();
    $scoped = ratesScopedManager($c['s']);
    $night = CarbonImmutable::today()->addDays(5)->toDateString();
    $farUnit = Unit::create(['property_id' => $c['far']->id, 'room_type_id' => $c['farType']->id, 'code' => 'F1']);
    $farSeason = RateSeason::create(['rate_plan_id' => $c['farPlan']->id, 'name' => 'Far summer', 'starts_on' => $night,
        'ends_on' => $night, 'price_minor' => 99900, 'currency' => 'EUR']);

    $posts = [
        [route('app.rates.update'), ['room_type_id' => $c['farType']->id, 'start_date' => $night, 'end_date' => $night, 'set_rate' => '1', 'rate' => '140']],
        [route('app.rates.bulk'), ['room_type_id' => $c['farType']->id, 'start_date' => $night, 'end_date' => $night, 'mode' => 'set_exact', 'amount' => '150', 'nights' => 'whole']],
        [route('app.rates.units.preview'), ['room_type_id' => $c['farType']->id, 'unit_ids' => [$farUnit->id], 'start_date' => $night, 'end_date' => $night, 'mode' => 'set', 'amount' => '120']],
        [route('app.seasons.store'), ['rate_plan_id' => $c['farPlan']->id, 'name' => 'X', 'starts_on' => $night, 'ends_on' => $night, 'price' => '120']],
        [route('app.seasons.apply', $farSeason->id), []],
        [route('app.inventory.rate-plans.active', $c['farPlan']->id), ['active' => '0']],
        [route('app.inventory.rate-plans.store'), ['room_type_id' => $c['farType']->id, 'name' => 'Sneaky']],
    ];
    foreach ($posts as [$url, $body]) {
        $this->actingAs($scoped)->post($url, $body)->assertNotFound();
    }
    $this->actingAs($scoped)->put(route('app.seasons.update', $farSeason->id), ['name' => 'Y', 'starts_on' => $night, 'ends_on' => $night, 'price' => '1'])->assertNotFound();
    $this->actingAs($scoped)->delete(route('app.seasons.destroy', $farSeason->id))->assertNotFound();
    $this->actingAs($scoped)->put(route('app.inventory.rate-plans.update', $c['farPlan']->id), ['name' => 'Renamed'])->assertNotFound();

    expect(nightRate($c['farPlan'], $night))->toBeNull()
        ->and($c['farPlan']->fresh()->is_active)->toBeTrue()
        ->and($c['farPlan']->fresh()->name)->toBe('Far Flexible')
        ->and(RateSeason::query()->whereKey($farSeason->id)->value('name'))->toBe('Far summer')
        ->and(RatePlan::query()->where('name', 'Sneaky')->exists())->toBeFalse();

    // …and the person's own property still takes every one of them
    $this->actingAs($scoped)->post(route('app.rates.update'), ['room_type_id' => $c['s']['roomType']->id, 'start_date' => $night,
        'end_date' => $night, 'set_rate' => '1', 'rate' => '140'])->assertRedirect();
    expect(nightRate($c['s']['ratePlan'], $night))->toBe(14000);
})->group('security');

// ── Money: a price is never made €0 ──────────────────────────────────────────────────────────────

it('⚠️⚠️ never writes a price of €0 or less — a 100% decrease is refused, and a night that would round to nothing is skipped', function () {
    $s = seedScenario(1);
    $plan = $s['ratePlan'];
    $night = CarbonImmutable::today()->addDays(4);
    app(RateAndRestrictionService::class)->setRate($plan, $night->toDateString(), $night->addDays(2)->toDateString(), Money::of(20000, 'EUR'));
    $manager = operatorUser($s);

    $this->actingAs($manager)->post(route('app.rates.bulk'), ['room_type_id' => $s['roomType']->id, 'start_date' => $night->toDateString(),
        'end_date' => $night->toDateString(), 'mode' => 'decrease_pct', 'amount' => '100', 'nights' => 'whole'])
        ->assertRedirect()->assertSessionHas('rates_error');
    expect(nightRate($plan, $night->toDateString()))->toBe(20000);

    // a 1-cent night decreased by 60% would round to €0: it is skipped, the other night is lowered
    app(RateAndRestrictionService::class)->setRate($plan, $night->toDateString(), $night->addDay()->toDateString(), Money::of(1, 'EUR'));
    $this->actingAs($manager)->post(route('app.rates.bulk'), ['room_type_id' => $s['roomType']->id, 'start_date' => $night->toDateString(),
        'end_date' => $night->addDay()->toDateString(), 'mode' => 'decrease_pct', 'amount' => '60', 'nights' => 'whole'])->assertRedirect();
    expect(nightRate($plan, $night->toDateString()))->toBe(1)
        ->and(nightRate($plan, $night->addDay()->toDateString()))->toBe(8000)
        ->and((string) session('rates_notice'))->toContain('1 night')->toContain('made the price zero');
});

it('⚠️⚠️ never takes a price past the page’s 1,000,000 ceiling — such a night is left as it was and counted', function () {
    $s = seedScenario(1);
    $plan = $s['ratePlan'];
    $night = CarbonImmutable::today()->addDays(4);
    app(RateAndRestrictionService::class)->setRate($plan, $night->toDateString(), $night->addDay()->toDateString(), Money::of(90_000_000, 'EUR'));
    app(RateAndRestrictionService::class)->setRate($plan, $night->addDay()->toDateString(), $night->addDays(2)->toDateString(), Money::of(10000, 'EUR'));

    $this->actingAs(operatorUser($s))->post(route('app.rates.bulk'), ['room_type_id' => $s['roomType']->id, 'start_date' => $night->toDateString(),
        'end_date' => $night->addDay()->toDateString(), 'mode' => 'increase_pct', 'amount' => '20', 'nights' => 'whole'])->assertRedirect();

    expect(nightRate($plan, $night->toDateString()))->toBe(90_000_000)         // €1,080,000 would pass the ceiling — left alone
        ->and(nightRate($plan, $night->addDay()->toDateString()))->toBe(12000)   // the other night is raised
        ->and((string) session('rates_notice'))->toContain('1 night')->toContain('passed the 1,000,000 limit');
});

// ── Seasons: read-only, a price cap, refusals seen ───────────────────────────────────────────────

it('⚠️⚠️ refuses every season write while the workspace is paused — saving, editing, deleting and applying', function () {
    $s = seedScenario(1);
    $night = CarbonImmutable::today()->addDays(6)->toDateString();
    $season = RateSeason::create(['rate_plan_id' => $s['ratePlan']->id, 'name' => 'High', 'starts_on' => $night, 'ends_on' => $night,
        'price_minor' => 30000, 'currency' => 'EUR']);
    $manager = operatorUser($s);
    pauseWorkspace($s);

    $this->actingAs($manager)->post(route('app.seasons.store'), ['rate_plan_id' => $s['ratePlan']->id, 'name' => 'New', 'starts_on' => $night,
        'ends_on' => $night, 'price' => '200'])->assertSessionHas('rates_error');
    $this->actingAs($manager)->put(route('app.seasons.update', $season->id), ['name' => 'Renamed', 'starts_on' => $night, 'ends_on' => $night,
        'price' => '250'])->assertSessionHas('rates_error');
    $this->actingAs($manager)->post(route('app.seasons.apply', $season->id))->assertSessionHas('rates_error');
    $this->actingAs($manager)->delete(route('app.seasons.destroy', $season->id))->assertSessionHas('rates_error');

    expect(RateSeason::query()->where('name', 'New')->exists())->toBeFalse()
        ->and($season->fresh()?->name)->toBe('High')
        ->and($season->fresh()?->applied_at)->toBeNull()
        ->and(nightRate($s['ratePlan'], $night))->toBeNull();
})->group('security');

it('caps a season price like every other price field on the page (€1,000,000)', function () {
    $s = seedScenario(1);
    $night = CarbonImmutable::today()->addDays(6)->toDateString();

    $this->actingAs(operatorUser($s))->post(route('app.seasons.store'), ['rate_plan_id' => $s['ratePlan']->id, 'name' => 'Huge',
        'starts_on' => $night, 'ends_on' => $night, 'price' => '1000000.01'])->assertSessionHasErrors('price');
    expect(RateSeason::query()->where('name', 'Huge')->exists())->toBeFalse();
});

// ── Rate plans: refundable, audited, the right read-only sentence ────────────────────────────────

it('creates a NON-refundable plan when Refundable is switched off — and records who created it', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);

    $this->actingAs($manager)->post(route('app.inventory.rate-plans.store'), ['room_type_id' => $s['roomType']->id, 'name' => 'Saver'])
        ->assertRedirect();
    $this->actingAs($manager)->post(route('app.inventory.rate-plans.store'), ['room_type_id' => $s['roomType']->id, 'name' => 'Flex', 'is_refundable' => '1'])
        ->assertRedirect();

    $saver = RatePlan::query()->where('name', 'Saver')->firstOrFail();
    expect((bool) $saver->is_refundable)->toBeFalse()
        ->and((bool) RatePlan::query()->where('name', 'Flex')->value('is_refundable'))->toBeTrue()
        ->and(DB::table('audit_events')->where('action', 'rate_plan.created')->where('subject_id', $saver->id)->count())->toBe(1);
});

it('says a paused workspace pauses RATE changes on the rate-plan writes — and never ends a sentence on "until ."', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    pauseWorkspace($s);

    $this->actingAs($manager)->put(route('app.inventory.rate-plans.update', $s['ratePlan']->id), ['name' => 'Renamed']);
    $message = (string) session('rates_error');
    expect($message)->toContain('rate')->not->toContain('adding inventory')->not->toContain('until .');
});

// ── The editor sets every rule it can show ───────────────────────────────────────────────────────

it('sets a maximum stay, closed-to-arrival or closed-to-departure on its own — each rule the grid shows can be set', function () {
    $s = seedScenario(1);
    $night = CarbonImmutable::today()->addDays(8)->toDateString();
    $manager = operatorUser($s);

    foreach ([['set_max_stay' => '1', 'max_stay' => '7'], ['set_closed_to_arrival' => '1', 'closed_to_arrival' => '1'],
        ['set_closed_to_departure' => '1', 'closed_to_departure' => '1']] as $rule) {
        $this->actingAs($manager)->post(route('app.rates.update'), ['room_type_id' => $s['roomType']->id, 'start_date' => $night,
            'end_date' => $night] + $rule)->assertRedirect()->assertSessionMissing('rates_error');
    }

    $row = Restriction::query()->where('room_type_id', $s['roomType']->id)->whereNull('rate_plan_id')->whereDate('date', $night)->firstOrFail();
    expect((int) $row->max_stay)->toBe(7)
        ->and((bool) $row->closed_to_arrival)->toBeTrue()
        ->and((bool) $row->closed_to_departure)->toBeTrue();

    // and it says what it did in a sentence — never "rate + minimum stay"
    $this->actingAs($manager)->post(route('app.rates.update'), ['room_type_id' => $s['roomType']->id, 'start_date' => $night, 'end_date' => $night,
        'set_rate' => '1', 'rate' => '140', 'set_min_stay' => '1', 'min_stay' => '2', 'set_closed_to_arrival' => '1', 'closed_to_arrival' => '0'])
        ->assertSessionHas('rates_notice', 'Updated the nightly price, the minimum stay and the arrival rule for 1 room type across 1 night.');
});

// ── A second plan can be priced ──────────────────────────────────────────────────────────────────

it('⚠️ prices a room type’s SECOND plan when it is chosen — a plan that can be added can be priced', function () {
    // 🖥 2026-10-01: "Add rate plan" made a second plan the grid could never price (it prices each room type's MAIN plan), and
    // the success message sent the operator to the grid to do it. The storefront skips an unpriced plan, so it sat unused.
    $s = seedScenario(1);
    $main = $s['ratePlan'];
    $saver = RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id, 'name' => 'Saver', 'currency' => 'EUR']);
    $night = CarbonImmutable::today()->addDays(9)->toDateString();

    $this->actingAs(operatorUser($s))->post(route('app.rates.update'), ['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $saver->id,
        'start_date' => $night, 'end_date' => $night, 'set_rate' => '1', 'rate' => '99'])->assertSessionMissing('rates_error')
        ->assertSessionHas('rates_notice', 'Updated the nightly price on Saver for 1 room type across 1 night.');

    expect(nightRate($saver, $night))->toBe(9900)
        ->and(nightRate($main, $night))->toBeNull();   // the main plan is untouched
});

it('⚠️⚠️ refuses to price a plan of another room type, a derived plan, or a chosen plan across "All room types"', function () {
    $s = seedScenario(1);
    $other = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Other', 'kind' => 'room']);
    $otherPlan = RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $other->id, 'name' => 'Other Flex', 'currency' => 'EUR']);
    $derived = RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id, 'name' => 'Non-refundable',
        'currency' => 'EUR', 'derived_from_rate_plan_id' => $s['ratePlan']->id, 'adjustment' => ['pct' => -10]]);
    $night = CarbonImmutable::today()->addDays(9)->toDateString();
    $manager = operatorUser($s);
    $post = fn (array $extra) => $this->actingAs($manager)->post(route('app.rates.update'), $extra + ['start_date' => $night,
        'end_date' => $night, 'set_rate' => '1', 'rate' => '99']);

    // ⚠️ Each by its OWN sentence: with "All room types" the other-room-type check would refuse too, so a bare "some refusal"
    //    let the first guard be deleted unnoticed (mutation RP-M12, 2026-10-01).
    $post(['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $otherPlan->id])
        ->assertSessionHas('rates_error', 'That plan belongs to another room type.');
    $post(['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $derived->id])
        ->assertSessionHas('rates_error', 'A derived plan follows its base plan’s price — set the base plan instead.');
    $post(['room_type_id' => 'all', 'rate_plan_id' => $s['ratePlan']->id])
        ->assertSessionHas('rates_error', 'Choose one room type to price a particular plan.');

    expect(nightRate($otherPlan, $night))->toBeNull()
        ->and(nightRate($derived, $night))->toBeNull()
        ->and(nightRate($s['ratePlan'], $night))->toBeNull();
})->group('security');

it('⚠️⚠️ keeps a chosen plan inside the person’s properties (DEC-l)', function () {
    ['s' => $s, 'farType' => $farType, 'farPlan' => $farPlan] = ratesTwoProperties();
    $night = CarbonImmutable::today()->addDays(9)->toDateString();

    $this->actingAs(ratesScopedManager($s))->post(route('app.rates.update'), ['room_type_id' => $farType->id, 'rate_plan_id' => $farPlan->id,
        'start_date' => $night, 'end_date' => $night, 'set_rate' => '1', 'rate' => '99'])->assertNotFound();

    expect(nightRate($farPlan, $night))->toBeNull();
})->group('security');

it('does not send the operator to the grid to price a new plan the grid does not show', function () {
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s))->post(route('app.inventory.rate-plans.store'), ['room_type_id' => $s['roomType']->id, 'name' => 'Saver',
        'is_refundable' => '1'])->assertSessionHas('rates_notice');

    expect((string) session('rates_notice'))->not->toContain('in the grid')->toContain('Set prices');
});

// ── Refusals are alerts; every write comes back to the page as it was ────────────────────────────

it('shows a refused change as an alert, and brings every write back to the window and filter it started from', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    $from = CarbonImmutable::today()->addDays(14)->toDateString();
    $night = CarbonImmutable::today()->addDays(20)->toDateString();
    $rt = (string) $s['roomType']->id;

    // a refusal is an error the page shows as an alert, not a plain notice
    $this->actingAs($manager)->post(route('app.rates.update'), ['room_type_id' => $rt, 'start_date' => 'nope', 'end_date' => $night, 'set_rate' => '1', 'rate' => '1'])
        ->assertSessionHas('rates_error');

    // back to the window and filter the operator was on — rebuilt from an allow-list, never taken as a URL
    $query = 'from='.$from.'&rt='.$rt.'&evil=1&next=https://evil.example';
    $this->actingAs($manager)->post(route('app.rates.update'), ['room_type_id' => $rt, 'start_date' => $night, 'end_date' => $night,
        'set_rate' => '1', 'rate' => '140', 'return' => 'list', 'return_query' => $query])
        ->assertRedirect(route('app.rates', ['from' => $from, 'rt' => $rt]));
    $this->actingAs($manager)->post(route('app.rates.update'), ['room_type_id' => $rt, 'start_date' => $night, 'end_date' => $night,
        'set_rate' => '1', 'rate' => '140', 'return' => 'list', 'return_query' => '//evil.example/x'])
        ->assertRedirect(route('app.rates'));
});

// ── The window keeps its filter; per-unit prices leave a trail ───────────────────────────────────

it('keeps the room-type filter on the window’s links', function () {
    $s = seedScenario(1);
    RoomType::create(['property_id' => $s['property']->id, 'name' => 'Other', 'kind' => 'room']);
    $rt = (string) $s['roomType']->id;

    $html = (string) $this->actingAs(operatorUser($s))->get(route('app.rates', ['rt' => $rt]))->assertOk()->getContent();
    preg_match_all('/href="([^"]*\/app\/rates\?[^"]*from=[^"]*)"/', $html, $m);
    $links = array_map('html_entity_decode', $m[1]);

    expect($links)->not->toBe([]);
    foreach ($links as $link) {
        expect($link)->toContain('rt='.$rt);
    }
});

it('offers to jump only to prices on screen — another property’s rates never send the operator to an empty fortnight', function () {
    ['s' => $s, 'farPlan' => $farPlan] = ratesTwoProperties();
    $manager = ratesScopedManager($s);
    $far = CarbonImmutable::today()->addDays(40)->toDateString();
    app(RateAndRestrictionService::class)->setRate($farPlan, $far, CarbonImmutable::parse($far)->addDay()->toDateString(), Money::of(15000, 'EUR'));

    // nothing priced on screen, only in the other property: no jump
    $html = (string) $this->actingAs($manager)->get(route('app.rates'))->assertOk()->getContent();
    expect($html)->not->toContain('go to the first priced night');

    // a price of its own further on: the jump goes there, and keeps the filter
    $own = CarbonImmutable::today()->addDays(50)->toDateString();
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], $own, CarbonImmutable::parse($own)->addDay()->toDateString(), Money::of(15000, 'EUR'));
    $html = (string) $this->actingAs($manager)->get(route('app.rates', ['rt' => $s['roomType']->id]))->assertOk()->getContent();
    expect($html)->toContain('go to the first priced night')
        ->and(html_entity_decode($html))->toContain(route('app.rates', ['from' => $own, 'rt' => $s['roomType']->id]));
});

it('records who priced units individually — and counts NIGHTS in what it tells the operator, not rows', function () {
    $s = seedScenario(2);
    $night = CarbonImmutable::today()->addDays(9);
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], $night->toDateString(), $night->addDays(2)->toDateString(), Money::of(10000, 'EUR'));
    $manager = operatorUser($s);
    $units = [$s['units'][0]->id, $s['units'][1]->id];
    $payload = ['room_type_id' => $s['roomType']->id, 'unit_ids' => $units, 'start_date' => $night->toDateString(),
        'end_date' => $night->addDay()->toDateString(), 'mode' => 'set', 'amount' => '120'];

    $this->actingAs($manager)->post(route('app.rates.units.preview'), $payload)->assertRedirect();
    $preview = session('unit_rate_preview');
    expect($preview)->not->toBeNull();

    // 🖥 two units over two nights said "across 4 nights" — it counted the rows written
    $this->actingAs($manager)->post(route('app.rates.units.apply'), $payload + ['digest' => $preview['digest']])
        ->assertRedirect()->assertSessionHas('rates_notice', 'Priced 2 units individually for 2 nights.');

    expect(DB::table('audit_events')->where('action', 'unit_rate.applied')->where('subject_id', $s['ratePlan']->id)->count())->toBe(1);
});
