<?php

declare(strict_types=1);

use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\RateSeason;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE CALENDAR, SECOND PASS (2026-10-04, the founder: "on this page there is functionality issues. it is not possible to do
// reservation. Fix it … And if there's any other issue on this page"). 🔬 On production (read-only, 19:02 UTC) his working property
// has no rooms, his other one has three units and no price on any night, and the €200 "Winter" season he saved was never applied.
// The New reservation page offers the way through since `rel_AV`; the Calendar's dialog said "No price for these nights yet" with a
// link away, and on the property with no rooms only "Go to Units". Each test below failed first against the Calendar as it stood.
// Test clock: 1 Aug 2026; the scenario's rooms are published 1–10 Sep.

const CBK_STAY = '?start=2026-09-01&days=7&new=1&ci=2026-09-02&co=2026-09-05';

function cbkScenario(int $units = 2, ?int $nightlyMinor = null): array
{
    $s = seedScenario($units);
    if ($nightlyMinor !== null) {
        app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-30', Money::of($nightlyMinor, 'EUR'));
    }

    return $s;
}

function cbkPage(User $user, string $query = CBK_STAY, array $session = []): string
{
    return (string) test()->actingAs($user)->withSession($session)->get(route('app.calendar').$query)->assertOk()->getContent();
}

/** A saved, NEVER applied season on the scenario's plan — what he has on production. */
function cbkWinter(array $s, string $from = '2026-09-01', string $to = '2026-09-30'): RateSeason
{
    return RateSeason::create(['rate_plan_id' => $s['ratePlan']->id, 'name' => 'Winter', 'starts_on' => $from, 'ends_on' => $to,
        'price_minor' => 20000, 'currency' => 'EUR']);
}

/** What the dialog sends with a price or a season so the answer comes back into it — the same stay, the unit clicked. */
function cbkReturn(array $s, array $extra = []): array
{
    return array_merge(['return' => 'calendar', 'return_start' => '2026-09-01', 'return_days' => '7', 'return_check_in' => '2026-09-02',
        'return_check_out' => '2026-09-05', 'return_adults' => '3', 'return_source' => 'walk_in', 'return_unit' => (string) $s['units'][0]->id], $extra);
}

/** The dialog it comes back to. ⚠️ No guest name in it — the names come back from the tab (`kept`), never the address (#28). */
function cbkDialogUrl(array $s, array $extra = []): string
{
    return route('app.calendar', ['start' => '2026-09-01', 'days' => 7] + $extra + ['new' => 1, 'ci' => '2026-09-02', 'co' => '2026-09-05',
        'adults' => 3, 'source' => 'walk_in', 'unit' => (string) $s['units'][0]->id, 'kept' => 1]);
}

/** One form of the page, by where it posts — its whole markup. */
function cbkForm(string $html, string $action): string
{
    preg_match('~<form[^>]*action="'.preg_quote(e($action), '~').'"[^>]*>.*?</form>~s', $html, $m);

    return $m[0] ?? '';
}

function cbkSecondType(array $s, string $name = 'Suite'): RoomType
{
    $rt = RoomType::create(['property_id' => $s['property']->id, 'name' => $name, 'kind' => 'room']);
    Unit::create(['property_id' => $s['property']->id, 'room_type_id' => $rt->id, 'code' => 'S1']);
    app(AvailabilityService::class)->recompute($rt->id, '2026-09-01', '2026-09-10');

    return $rt;
}

// ── His report: a booking that cannot be made — a room with no price ────────────────────────────────────────────

it('⚠️⚠️ sets a missing price inside the dialog — for whoever manages rates', function () {
    $s = cbkScenario();
    $u = (string) $s['units'][0]->id;
    $html = cbkPage(operatorUser($s, [], 'Hotel Manager'), CBK_STAY.'&unit='.$u);

    // 🔬 before: "No price for these nights yet. Set a price in the full form" — a link away, the booking left behind
    expect($html)->toContain('action="'.e(route('app.rates.update')).'"')
        ->toContain('name="start_date" value="2026-09-02"')->toContain('name="end_date" value="2026-09-04"')
        ->toContain('name="return" value="calendar"')->toContain('name="return_check_in" value="2026-09-02"')
        ->toContain('name="return_check_out" value="2026-09-05"')->toContain('name="return_unit" value="'.$u.'"')
        ->toContain('>Set price</button>')
        ->toContain('The same price goes on each night from 2 Sep to 4 Sep on the Flex plan')
        ->not->toContain('Set a price in the full form');
});

it('⚠️ will not set a price or apply a season for dates changed but not checked', function () {
    $s = cbkScenario();
    cbkWinter($s);
    $html = cbkPage(operatorUser($s, [], 'Hotel Manager'));

    // 🔬 found writing the docs: with new dates typed and "Check rack" not pressed, Set price went on the OLD stay's nights (its
    //    form carries the dates the dialog was opened with) — Confirm was already locked in that state, these were not
    expect($html)->toContain('x-bind:disabled="dirty || priceBusy === 0"')
        ->toMatch('~<button type="button" class="vy-btn vy-btn--accent vy-rnr-season-go"[^>]*x-bind:disabled="dirty"~');
});

it('has no attribute broken by a doubled quote in the Calendar’s views', function () {
    // 🔬 2026-10-04: an edit wrote `x-text=""priceBusy …` — the label's binding gone in the browser, every server check green
    foreach (['livewire/reservation-rack.blade.php', 'livewire/operator/partials/rack-new-reservation.blade.php',
        'livewire/operator/partials/calendar-return-fields.blade.php'] as $view) {
        expect(preg_match('/=""[A-Za-z!$(\'{]/', (string) file_get_contents(resource_path('views/'.$view))))->toBe(0, $view);
    }
});

it('keeps the price controls from whoever cannot set one — and says who can', function () {
    $s = cbkScenario();
    $html = cbkPage(operatorUser($s, [], 'Front Desk'));

    expect($html)->toContain('No price for these nights yet')->toContain('Ask someone who manages rates to set one')
        ->not->toContain('action="'.e(route('app.rates.update')).'"');
});

it('⚠️⚠️ comes back into the dialog after the price is set — the same stay, the unit that was clicked', function () {
    $s = cbkScenario();
    $manager = operatorUser($s, [], 'Hotel Manager');

    // 🔬 before: the Rates page — the booking, its dates and its unit gone
    test()->actingAs($manager)->post(route('app.rates.update'), ['room_type_id' => $s['roomType']->id, 'start_date' => '2026-09-02',
        'end_date' => '2026-09-04', 'set_rate' => '1', 'rate' => '120'] + cbkReturn($s))
        ->assertRedirect(cbkDialogUrl($s))
        ->assertSessionHas('calendar_dialog_ok', 'Price set — €120.00 a night for these 3 nights.');

    // the next step is said by the dialog, from what it shows: the unit clicked is picked (🔬 the walk: "Pick a unit to
    // continue" stood above a unit already picked)
    $html = cbkPage($manager, '?'.parse_url(cbkDialogUrl($s), PHP_URL_QUERY), ['calendar_dialog_ok' => 'Price set — €120.00 a night for these 3 nights.']);
    expect($html)->toContain("sel: '".$s['units'][0]->id."'")->toContain('€360.00')->toContain('Confirm reservation')
        ->toContain('adults: 3,')->toContain("source: 'walk_in',")
        ->toContain('<p class="vy-rnr-msg is-ok" role="status">Price set — €120.00 a night for these 3 nights. U1 is picked — confirm to book it.</p>');
});

it('asks for a unit when none was picked', function () {
    $s = cbkScenario(2, 10000);
    $html = cbkPage(operatorUser($s, [], 'Hotel Manager'), CBK_STAY, ['calendar_dialog_ok' => 'Price set — €100.00 a night for these 3 nights.']);

    expect($html)->toContain('<p class="vy-rnr-msg is-ok" role="status">Price set — €100.00 a night for these 3 nights. Pick a unit below to continue.</p>');
});

it('names one night as one night — never "these 1 night"', function () {
    $s = cbkScenario();

    test()->actingAs(operatorUser($s, [], 'Hotel Manager'))->post(route('app.rates.update'), ['room_type_id' => $s['roomType']->id,
        'start_date' => '2026-09-02', 'end_date' => '2026-09-02', 'set_rate' => '1', 'rate' => '120'] + cbkReturn($s, ['return_check_out' => '2026-09-03']))
        ->assertSessionHas('calendar_dialog_ok', 'Price set — €120.00 for the night of 2 Sep.');
});

it('refuses a price it cannot read back inside the dialog, as an alert', function () {
    $s = cbkScenario();
    $manager = operatorUser($s, [], 'Hotel Manager');

    test()->actingAs($manager)->post(route('app.rates.update'), ['room_type_id' => $s['roomType']->id, 'start_date' => '2026-09-02',
        'end_date' => '2026-09-04', 'set_rate' => '1', 'rate' => 'abc'] + cbkReturn($s))
        ->assertRedirect(cbkDialogUrl($s))
        ->assertSessionHas('calendar_dialog_error', 'Enter a valid nightly rate, e.g. 175 or 175.50.');
    expect(RateCalendar::query()->where('rate_plan_id', $s['ratePlan']->id)->count())->toBe(0);

    $html = cbkPage($manager, CBK_STAY, ['calendar_dialog_error' => 'Enter a valid nightly rate, e.g. 175 or 175.50.']);
    expect($html)->toContain('<p class="vy-rnr-msg is-err" role="alert">Enter a valid nightly rate, e.g. 175 or 175.50.</p>');
});

it('keeps the way back clean — a hand-made return field is dropped, never written into the address', function () {
    $s = cbkScenario();

    test()->actingAs(operatorUser($s, [], 'Hotel Manager'))->post(route('app.rates.update'), ['room_type_id' => $s['roomType']->id,
        'start_date' => '2026-09-02', 'end_date' => '2026-09-04', 'set_rate' => '1', 'rate' => '120']
        + cbkReturn($s, ['return_days' => '9', 'return_adults' => '99', 'return_source' => 'https://evil.test', 'return_unit' => '"><script>',
            'return_rt' => '../x', 'return_check_out' => '2026-02-31']))
        ->assertRedirect(route('app.calendar', ['start' => '2026-09-01', 'new' => 1, 'ci' => '2026-09-02', 'kept' => 1]));
});

// ── His report: the season he saved and never applied ───────────────────────────────────────────────────────────

it('⚠️⚠️ offers a saved season that covers the stay — behind the Rates page’s own warning', function () {
    $s = cbkScenario();
    $winter = cbkWinter($s);
    $html = cbkPage(operatorUser($s, [], 'Hotel Manager'));

    // 🔬 before: not a word about it — the dialog said only "No price for these nights yet"
    expect($html)->toContain('<strong>Winter · €200.00 a night</strong> · 1 Sep → 30 Sep — saved but not applied yet, so these nights have no price.')
        ->toContain('x-on:click="openSeason(\''.$winter->id.'\')"')->toContain('>Apply Winter…</button>')
        // the Rates page's own disclosure (one partial) — nothing is applied until its own button
        ->toContain('The prices it replaces are not stored anywhere. <strong>This cannot be undone.</strong>')
        ->toContain('For this booking: it prices 3 of the 3 nights of the stay that have no price.')
        ->toContain('action="'.e(route('app.seasons.apply', $winter->id)).'"')
        ->toContain('>Apply and overwrite those nights</button>');
});

it('⚠️ sends the season’s Apply with the way back into the dialog — the stay and the unit that was clicked', function () {
    $s = cbkScenario();
    $winter = cbkWinter($s);
    $u = (string) $s['units'][0]->id;
    $form = cbkForm(cbkPage(operatorUser($s, [], 'Hotel Manager'), CBK_STAY.'&unit='.$u), route('app.seasons.apply', $winter->id));

    expect($form)->toContain('name="return" value="calendar"')->toContain('name="return_check_in" value="2026-09-02"')
        ->toContain('name="return_check_out" value="2026-09-05"')->toContain('name="return_unit" value="'.$u.'"')
        ->not->toContain('guest_first_name')->not->toContain('name="gf"');   // never the guest's name (#28)
});

it('keeps Apply from whoever cannot apply a season — and says who can', function () {
    $s = cbkScenario();
    cbkWinter($s);
    $html = cbkPage(operatorUser($s, [], 'Front Desk'));

    expect($html)->toContain('saved but not applied yet')->toContain('Someone who manages rates can apply it on the Rates page')
        ->not->toContain('x-on:click="openSeason(')->not->toContain('Apply and overwrite those nights');   // the button, not the script
});

it('⚠️⚠️ applies the season from the dialog and comes back into it', function () {
    $s = cbkScenario();
    $winter = cbkWinter($s);

    test()->actingAs(operatorUser($s, [], 'Hotel Manager'))->post(route('app.seasons.apply', $winter->id), cbkReturn($s))
        ->assertRedirect(cbkDialogUrl($s))
        ->assertSessionHas('calendar_dialog_ok', 'Winter applied — 30 nights rewritten across 2 units.');   // the dialog says what's next

    expect(RateCalendar::query()->where('rate_plan_id', $s['ratePlan']->id)->where('date', '>=', '2026-09-02')->where('date', '<', '2026-09-05')->count())->toBe(3);
});

it('refuses the season from the dialog while the workspace is paused — and says so on the page', function () {
    $s = cbkScenario();
    $winter = cbkWinter($s);
    $manager = operatorUser($s, [], 'Hotel Manager');
    pauseWorkspace($s);

    test()->actingAs($manager)->post(route('app.seasons.apply', $winter->id), cbkReturn($s))
        ->assertRedirect(cbkDialogUrl($s))
        ->assertSessionHas('calendar_dialog_error', 'Your workspace is read-only right now — rate changes are paused.');
    expect($winter->fresh()->applied_at)->toBeNull();

    // the dialog does not open in a paused workspace — so the page says it
    $html = cbkPage($manager, '?'.parse_url(cbkDialogUrl($s), PHP_URL_QUERY), ['calendar_dialog_error' => 'Your workspace is read-only right now — rate changes are paused.']);
    expect($html)->toContain('role="alert">Your workspace is read-only right now — rate changes are paused.</div>')
        ->not->toContain('id="vy-rnr-title"');
});

it('puts the room type of the unit clicked first — its fix is the one wanted', function () {
    $s = cbkScenario();
    $suite = cbkSecondType($s, 'Alpine Suite');   // sorts before "Std" by name
    $s1 = (string) Unit::query()->where('room_type_id', $suite->id)->value('id');

    // 🔬 the walk: unit 101 clicked, and its room type's Set price and season sat below the fold, under another room type's
    $html = cbkPage(operatorUser($s, [], 'Hotel Manager'), CBK_STAY.'&unit='.$s['units'][0]->id);
    expect(strpos($html, '<strong>Std</strong>'))->toBeLessThan(strpos($html, '<strong>Alpine Suite</strong>'));

    $other = cbkPage(operatorUser($s, [], 'Hotel Manager'), CBK_STAY.'&unit='.$s1);
    expect(strpos($other, '<strong>Alpine Suite</strong>'))->toBeLessThan(strpos($other, '<strong>Std</strong>'));
});

// ── His report: the property with no rooms ──────────────────────────────────────────────────────────────────────

it('⚠️⚠️ offers the properties with rooms when this one has none — inside the dialog', function () {
    $s = cbkScenario();
    $empty = secondProperty($s, 'Internal Test Property');
    $html = cbkPage(operatorUser($s, [], 'Hotel Manager'), '?new=1', ['current_property_id' => (string) $empty->id]);

    // 🔬 before: "has no rooms yet" and "Go to Units" — not a word about the property next door with two
    expect($html)->toContain(e($empty->name).' has no rooms yet')
        ->toContain('action="'.e(route('app.property.switch')).'"')->toContain('Book into another property')
        ->toContain('<button type="submit" name="property_id" value="'.$s['property']->id.'" class="vy-rnr-other">')
        ->not->toContain('value="'.$empty->id.'" class="vy-rnr-other"');
});

it('⚠️ gives the empty Calendar a way forward — the Units page and the properties with rooms', function () {
    $s = cbkScenario();
    $empty = secondProperty($s, 'Internal Test Property');
    $html = cbkPage(operatorUser($s, [], 'Hotel Manager'), '?start=2026-09-01&days=7', ['current_property_id' => (string) $empty->id]);

    // 🔬 before: "No room types on … yet." and nothing to do about it
    // (inside the empty card — the sidebar links to Units on every page; CB-M28 stayed green against a page-wide check)
    expect($html)->toContain(e($empty->name).' has no rooms yet')
        ->toMatch('~<div class="vy-rack-empty">.*?<a class="vy-btn vy-btn--ghost" href="'.preg_quote(e(route('app.units')), '~').'">Go to Units</a>~s')
        ->toContain('<button type="submit" name="property_id" value="'.$s['property']->id.'" class="vy-rnr-other">')
        ->not->toContain('No room types on');
});

// ── A booking made here comes back here ─────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ brings a booking made from the Calendar back to the Calendar — its reference and a way to open it', function () {
    $s = cbkScenario(2, 10000);
    $desk = operatorUser($s, [], 'Front Desk');

    $response = test()->actingAs($desk)->post(route('app.reservations.store'), [
        'room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'check_in' => '2026-09-02', 'check_out' => '2026-09-05',
        'adults' => 2, 'source' => 'direct', 'guest_first_name' => 'Lena', 'guest_last_name' => 'Fischer',
        'return' => 'calendar', 'return_start' => '2026-09-01', 'return_days' => '7',
    ]);

    // 🔬 before: the full form's done screen — away from the rack the booking was made on
    $id = (string) Reservation::query()->whereHas('reservationUnits', fn ($q) => $q->where('unit_id', $s['units'][0]->id))->value('id');
    expect($id)->not->toBe('');
    $response->assertRedirect(route('app.calendar', ['start' => '2026-09-01', 'days' => 7]))
        ->assertSessionHas('calendar_created', $id)
        ->assertSessionHas('folio_notice', fn (string $m): bool => str_starts_with($m, 'Reservation ') && str_ends_with($m, ' created.'));

    $html = cbkPage($desk, '?start=2026-09-01&days=7', ['folio_notice' => 'Reservation R-TEST created.', 'calendar_created' => $id]);
    // (Livewire marks each @if block with an HTML comment — the link sits after one)
    expect($html)->toMatch('~Reservation R-TEST created\.(?:<!--\[if BLOCK\]><!\[endif\]-->)? <a class="vy-rnr-link" href="'
        .preg_quote(e(route('app.reservations.show', $id)), '~').'">Open it</a>~');
});

it('sends each booking from the dialog with the way back to this Calendar', function () {
    $s = cbkScenario(2, 10000);
    $html = cbkPage(operatorUser($s, [], 'Front Desk'));

    expect(substr_count($html, 'action="'.e(route('app.reservations.store')).'"'))->toBe(2)
        ->and(substr_count($html, '<input type="hidden" name="return" value="calendar">'))->toBe(2)
        ->and($html)->toContain('<input type="hidden" name="return_start" value="2026-09-01">');
});

it('a booking from the full form still opens its done screen', function () {
    $s = cbkScenario(2, 10000);

    test()->actingAs(operatorUser($s, [], 'Front Desk'))->post(route('app.reservations.store'), [
        'room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'check_in' => '2026-09-02', 'check_out' => '2026-09-05',
        'adults' => 2, 'source' => 'direct', 'guest_first_name' => 'Lena', 'guest_last_name' => 'Fischer',
    ])->assertSessionMissing('calendar_created')->assertRedirectContains('created=');
});

// ── The grid says what it can sell ──────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ marks the nights with rooms free but no price — they cannot be booked yet', function () {
    $s = cbkScenario();
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-04', Money::of(10000, 'EUR'));   // 1–3 Sep
    $html = cbkPage(operatorUser($s, [], 'Front Desk'), '?start=2026-09-01&days=7');

    // 🔬 before: "2" on every night — the same as a night that can be sold
    expect($html)->toMatch('~<div class="vy-avail[^"]*is-noprice[^"]*"\s+title="2 available on Fri 4 Sep — no price yet, so it can’t be booked here">2</div>~')
        ->toMatch('~<div class="vy-avail[^"]*"\s+title="2 available on Thu 3 Sep">2</div>~')
        ->toContain('<span class="vy-legend-swatch swatch-noprice"></span> No price yet')
        ->toContain('No price yet on the marked nights for Std, so they can’t be booked here.');
});

it('names a saved season beside the unpriced nights it would price', function () {
    $s = cbkScenario();
    cbkWinter($s);
    $html = cbkPage(operatorUser($s, [], 'Hotel Manager'), '?start=2026-09-01&days=7');

    expect($html)->toContain('No price yet on the marked nights for Std, so they can’t be booked here.')
        ->toContain('Winter (€200.00 a night, 1 Sep → 30 Sep) is saved but not applied.')
        ->toContain('href="'.e(route('app.rates')).'"');
});

it('says nothing about prices when every night in view has one', function () {
    $s = cbkScenario(2, 10000);
    $html = cbkPage(operatorUser($s, [], 'Front Desk'), '?start=2026-09-01&days=7');

    // (the class attribute, not the stylesheet's `.vy-avail.is-noprice`)
    expect($html)->not->toMatch('~class="vy-avail[^"]*is-noprice~')
        ->not->toContain('No price yet on the marked nights')->not->toContain('swatch-noprice"></span>');
});

it('greys the nights before the property’s today', function () {
    $s = cbkScenario(2, 10000);
    $html = cbkPage(operatorUser($s, [], 'Front Desk'), '?start=2026-07-29&days=7');

    // the clock is 1 Aug 2026 (a Saturday): 29–31 Jul are gone, 1 Aug is today
    expect($html)->toMatch('~<div class="vy-day[^"]*is-past[^"]*">\s*<div class="vy-day-dow">Fri</div>\s*<div class="vy-day-num">31</div>~')
        ->not->toMatch('~<div class="vy-day[^"]*is-past[^"]*">\s*<div class="vy-day-dow">Sat</div>\s*<div class="vy-day-num">1</div>~');
});

// ── Drag-to-move: only for whoever may move a booking, and a refusal said as one ───────────────────────────────

it('⚠️⚠️ offers drag-to-move only to someone who may move a booking', function () {
    $s = cbkScenario(2, 10000);
    book($s, ['unit_id' => $s['units'][0]->id]);

    // 🔬 before: every back-office role got draggable bars and "Drag a booking … to reschedule it"; the move needs
    //    reservations.assign, which seven of the eleven back-office roles do not hold — the server refused the drop
    $agent = cbkPage(operatorUser($s, [], 'Reservation Agent'), '?start=2026-09-01&days=7');
    expect($agent)->not->toContain('draggable="true"')->not->toContain('<div class="vy-lane-drop"')->not->toContain('Drag a booking');

    $desk = cbkPage(operatorUser($s, [], 'Front Desk'), '?start=2026-09-01&days=7');
    expect($desk)->toContain('draggable="true"')->toContain('<div class="vy-lane-drop"')->toContain('Drag a booking');
});

it('⚠️ shows a refused move as an error — not like a move that worked', function () {
    $s = cbkScenario(2, 10000);
    $a = book($s, ['unit_id' => $s['units'][0]->id]);
    book($s, ['unit_id' => $s['units'][1]->id]);
    $desk = operatorUser($s, [], 'Front Desk');

    test()->actingAs($desk)->post(route('app.reservations.reschedule', $a->id), ['reservation_unit_id' => $a->reservationUnits()->value('id'),
        'new_check_in' => '2026-09-01', 'unit_id' => $s['units'][1]->id, 'return_start' => '2026-09-01', 'return_days' => '7'])
        ->assertSessionHas('calendar_refused', true)
        ->assertSessionHas('new_reservation_notice', 'That move isn’t available — the unit or nights are already taken.');

    // 🔬 before: the same quiet status line as "Moved R-… to 1 Sep."
    $html = cbkPage($desk, '?start=2026-09-01&days=7', ['new_reservation_notice' => 'That move isn’t available — the unit or nights are already taken.', 'calendar_refused' => true]);
    expect($html)->toContain('<div class="vy-flash is-err vy-rack-notice vy-fade" role="alert">That move isn’t available');
});

// ── The room-type filter is kept ────────────────────────────────────────────────────────────────────────────────

it('⚠️ keeps the room-type filter on every way round the Calendar', function () {
    $s = cbkScenario(2, 10000);
    $suite = cbkSecondType($s);
    $html = cbkPage(operatorUser($s, [], 'Front Desk'), '?start=2026-09-01&days=7&rt='.$suite->id);

    // 🔬 before: the arrows, Today, 7n/14n/30n and New reservation all dropped it; choosing a filter dropped 7/14/30
    $rt = '&amp;rt='.$suite->id;
    expect($html)->toContain('href="?start=2026-08-25&days=7'.$rt.'"')    // the previous week
        ->toContain('href="?start=2026-09-08&days=7'.$rt.'"')              // the next
        ->toContain('href="?start=2026-08-01&days=7'.$rt.'"')              // Today
        ->toContain('href="?start=2026-09-01&days=30'.$rt.'"')             // 30 nights
        ->toContain('href="?start=2026-09-01&days=7'.$rt.'&new=1"')        // New reservation
        ->toMatch('~<form method="GET" class="vy-rack-filter">\s*<input type="hidden" name="start" value="2026-09-01">\s*<input type="hidden" name="days" value="7">~')
        ->toContain('<span class="vy-rt-name">Suite</span>')->not->toContain('<span class="vy-rt-name">Std</span>');
});

it('⚠️ never says "No room types" because of a filter from somewhere else — it shows them all', function () {
    $s = cbkScenario(2, 10000);
    $html = cbkPage(operatorUser($s, [], 'Front Desk'), '?start=2026-09-01&days=7&rt=01notaroomtypeofthisplace0');

    // 🔬 before: a stale filter, or another property's, emptied the grid and said "No room types on H yet."
    expect($html)->not->toContain('No room types on')->toContain('<span class="vy-rt-name">Std</span>')
        ->not->toContain('rt=01notaroomtypeofthisplace0');
});

it('keeps the filter through the dialog and through a move', function () {
    $s = cbkScenario(2, 10000);
    $suite = cbkSecondType($s);
    $desk = operatorUser($s, [], 'Front Desk');

    $html = cbkPage($desk, CBK_STAY.'&rt='.$suite->id);
    expect($html)->toContain('<input type="hidden" name="rt" value="'.$suite->id.'">')        // Check rack keeps it
        ->toContain('<input type="hidden" name="return_rt" value="'.$suite->id.'">');         // so does a booking

    $a = book($s, ['unit_id' => $s['units'][0]->id]);
    test()->actingAs($desk)->post(route('app.reservations.reschedule', $a->id), ['reservation_unit_id' => $a->reservationUnits()->value('id'),
        'new_check_in' => '2026-09-04', 'unit_id' => $s['units'][0]->id, 'return_start' => '2026-09-01', 'return_days' => '7', 'return_rt' => (string) $suite->id])
        ->assertRedirect(route('app.calendar', ['start' => '2026-09-01', 'days' => 7, 'rt' => (string) $suite->id]));
});

// ── The independent review (2026-10-04): eight low findings, each fixed ──────────────────────────────────────────────

it('offers in the note only the ways this person has — and none in a paused workspace', function () {
    $s = cbkScenario();
    $calendar = '?start=2026-09-01&days=7';

    // the review, L1: a Revenue Manager prices but cannot book — "set one from New reservation" pointed at a button they lack
    $revenue = cbkPage(operatorUser($s, [], 'Revenue Manager'), $calendar);
    expect($revenue)->toContain('No price yet on the marked nights')->toContain('Set prices on the Rates page</a>.')
        ->not->toContain('or set one for a stay from New reservation');

    $manager = operatorUser($s, [], 'Hotel Manager');
    expect(cbkPage($manager, $calendar))->toContain('Set prices on the Rates page</a>, or set one for a stay from New reservation.');

    // …and in a paused workspace both writes refuse, so neither is offered
    pauseWorkspace($s);
    expect(cbkPage($manager, $calendar))->toContain('No price yet on the marked nights')->not->toContain('Set prices on the Rates page');
});

it('promises a booking after a season only when the season prices the whole stay', function () {
    $s = cbkScenario();
    cbkWinter($s, '2026-09-04', '2026-09-30');   // covers 4 Sep only — 2 and 3 Sep stay without a price

    // the review, L3: "its units can be booked here once they have" stood under "It would price 1 of the 3 nights"
    $html = cbkPage(operatorUser($s, [], 'Front Desk'));
    expect($html)->toContain('It would price 1 of the 3 nights that have none.')
        ->toContain('Someone who manages rates can apply it on the Rates page.')
        ->not->toContain('its units can be booked here once they have');
});

it('steps back from the Apply view on Escape, the cross or a click outside — the booking and its name kept', function () {
    $s = cbkScenario();
    cbkWinter($s);

    // the review, L4: all three closed the whole dialog from the Apply view, and close() forgot the guest's name
    expect(cbkPage(operatorUser($s, [], 'Hotel Manager')))->toContain('close() { if (this.confirmSeason) { this.closeSeason(); return; }');
});

it('names each price box and its button by room type, and ties its error to it', function () {
    $s = cbkScenario();
    $suite = cbkSecondType($s, 'Suite');
    RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $suite->id, 'name' => 'Suite Flex', 'currency' => 'EUR']);   // a plan, no price
    $html = cbkPage(operatorUser($s, [], 'Hotel Manager'));

    // the review, L5: two "Price a night" boxes and two "Set price" buttons a screen reader could not tell apart
    expect($html)->toContain('Price a night<span class="vy-sr-only"> for Std</span></label>')
        ->toContain('Price a night<span class="vy-sr-only"> for Suite</span></label>')
        ->toContain('aria-label="Set price for Std"')->toContain('aria-label="Set price for Suite"')
        ->toContain('aria-describedby="vy-rnr-rate-err-0"')->toContain('id="vy-rnr-rate-err-0"');
});

it('offers the properties with rooms when this one has room types but no unit that can take a booking', function () {
    $s = cbkScenario();
    $paused = secondProperty($s, 'Paused House');
    $rt = RoomType::create(['property_id' => $paused->id, 'name' => 'Loft', 'kind' => 'room']);
    Unit::create(['property_id' => $paused->id, 'room_type_id' => $rt->id, 'code' => 'L1', 'is_active' => false]);

    // the review, L6: a grid of "0 units" and no way on — the offer came only from the dialog, and only to someone who books
    $html = cbkPage(operatorUser($s, [], 'Hotel Manager'), '?start=2026-09-01&days=7', ['current_property_id' => (string) $paused->id]);
    expect($html)->toContain('None of '.e($paused->name).'’s units can take a booking')
        ->toContain('<button type="submit" name="property_id" value="'.$s['property']->id.'" class="vy-rnr-other">')
        ->toContain('<span class="vy-rt-name">Loft</span>');   // the grid is still shown
});

it('says what a price or a season did even when the dialog comes back on a property with no rooms', function () {
    $s = cbkScenario();
    $empty = secondProperty($s, 'Internal Test Property');

    // the review, unverified: the working property changed in another tab — the dialog reopened there and the message was lost
    $html = cbkPage(operatorUser($s, [], 'Hotel Manager'), '?new=1', ['current_property_id' => (string) $empty->id,
        'calendar_dialog_ok' => 'Price set — €120.00 a night for these 3 nights.']);
    expect($html)->toContain('<p class="vy-rnr-msg is-ok" role="status">Price set — €120.00 a night for these 3 nights.</p>');
});
