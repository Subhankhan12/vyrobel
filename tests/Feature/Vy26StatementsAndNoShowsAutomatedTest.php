<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\PropertyPolicy;
use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Services\OwnerAttributionService;
use App\Support\Reservations\CancellationReason;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;

// ⚠️⚠️ VY-26 (audit of 2026-11-24, read in code) — STATEMENTS AND NO-SHOWS WERE NOT AUTOMATED.
//
// Statements were closed only by hand from each owner's page, and a guest who never came kept their room until somebody pressed
// "No-show". Both are now automated ON THE OPERATOR'S CHOICE — off until chosen, so nothing changes for anyone who has not asked:
//   · `statements:close-month` closes LAST month for every owner with something to state, on the day of the month the organisation picked
//     (Payout run page) — through exactly the two calls "Close period" makes, never twice for one period, never guessing an exchange rate;
//   · `reservations:mark-no-shows` marks a confirmed booking a no-show once the property's check-out time on the day after arrival has
//     passed in the property's own time zone (Settings → Policies), through the certified markNoShow (the room is freed).
// The night audit is not built and nothing on the site claims one (VY-09 removed "The night audit runs on its own").

uses(DatabaseTransactions::class);

function vy26Stay(array $s, string $in = '2026-09-02', string $out = '2026-09-05', int $unit = 0): Reservation
{
    return app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][$unit]->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $in, 'check_out' => $out, 'price_minor' => 30000, 'currency' => 'EUR']],
    );
}

function vy26AutoNoShow(array $s, bool $on = true, string $checkOut = '11:00'): void
{
    $s['property']->update(['check_out_time' => $checkOut]);
    PropertyPolicy::query()->updateOrCreate(['property_id' => $s['property']->id], ['auto_no_show' => $on]);
}

// ── no-shows ─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ VY-26 (no-shows): a guest who never arrived is marked a no-show once check-out time the day after arrival has passed — not a minute before', function () {
    $s = seedScenario(2);
    vy26AutoNoShow($s);
    $stay = vy26Stay($s);   // arrives 2 Sep, leaves 5 Sep

    $this->travelTo('2026-09-03 10:59:00');
    Artisan::call('reservations:mark-no-shows');
    expect($stay->fresh()->status)->toBe('confirmed');

    $this->travelTo('2026-09-03 11:00:00');
    Artisan::call('reservations:mark-no-shows');

    $fresh = $stay->fresh();
    expect($fresh->status)->toBe('no_show')
        ->and($fresh->cancellation_reason)->toBe(CancellationReason::AUTO_NO_SHOW)
        ->and(soldOn($s['roomType']->id, '2026-09-03'))->toBe(0)    // the nights left are released
        ->and(soldOn($s['roomType']->id, '2026-09-04'))->toBe(0);
});

it('VY-26 (no-shows) NEVER: a guest who checked in, a property that has not switched it on, or a stay that is already over', function () {
    $s = seedScenario(2);
    vy26AutoNoShow($s);
    $checkedIn = vy26Stay($s, unit: 0);
    $checkedIn->forceFill(['status' => 'checked_in'])->save();
    $over = vy26Stay($s, '2026-09-01', '2026-09-03', unit: 1);   // already over when the rule runs (4 Sep): its record is never rewritten

    $other = seedScenario(1);
    vy26AutoNoShow($other, on: false);
    $offProperty = vy26Stay($other);

    $this->travelTo('2026-09-04 12:00:00');
    Artisan::call('reservations:mark-no-shows');

    expect($checkedIn->fresh()->status)->toBe('checked_in')
        ->and($over->fresh()->status)->toBe('confirmed')
        ->and($offProperty->fresh()->status)->toBe('confirmed');
});

it('⚠️ VY-26 (no-shows): the line falls in the PROPERTY\'s time — 11:00 in Auckland is still 22:59 the evening before in UTC', function () {
    $s = seedScenario(1);
    $s['property']->update(['timezone' => 'Pacific/Auckland']);
    vy26AutoNoShow($s);
    $stay = vy26Stay($s);   // arrives 2 Sep (Auckland)

    $this->travelTo('2026-09-02 22:59:00');   // UTC — Auckland: 3 Sep, 10:59
    Artisan::call('reservations:mark-no-shows');
    expect($stay->fresh()->status)->toBe('confirmed');

    $this->travelTo('2026-09-02 23:00:00');   // Auckland: 3 Sep, 11:00
    Artisan::call('reservations:mark-no-shows');
    expect($stay->fresh()->status)->toBe('no_show');
});

it('VY-26 (no-shows): the manager switches it on and off in Settings → Policies', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Property Owner');
    $base = ['pet' => 'allowed', 'smoking' => 'not_allowed', 'min_stay' => '1'];

    $this->actingAs($manager)->post('/app/settings/policies', $base + ['auto_no_show' => '1'])->assertRedirect();
    expect(PropertyPolicy::query()->where('property_id', $s['property']->id)->value('auto_no_show'))->toBeTruthy();

    $this->actingAs($manager)->post('/app/settings/policies', $base + ['auto_no_show' => '0'])->assertRedirect();
    expect(PropertyPolicy::query()->where('property_id', $s['property']->id)->value('auto_no_show'))->toBeFalsy();
});

// ── statements ───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────

/** An owner of the scenario's unit with one COLLECTED September stay (a statement has something to state). */
function vy26OwnerWithSeptember(array $s): Owner
{
    $owner = ownUnit($s['units'][0]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);

    return $owner;
}

function vy26CloseDay(array $s, ?int $day): void
{
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($s['org']->id)->update(['statement_close_day' => $day]));
}

it('⚠️⚠️ VY-26 (statements): on the day the organisation picked, last month is closed for its owners — the same statement "Close period" makes', function () {
    $s = seedScenario(1);
    $owner = vy26OwnerWithSeptember($s);
    vy26CloseDay($s, 5);

    $this->travelTo('2026-10-04 05:00:00');   // not the day yet
    Artisan::call('statements:close-month');
    expect(OwnerStatement::query()->where('owner_id', $owner->id)->where('locked', true)->count())->toBe(0);

    $this->travelTo('2026-10-05 05:00:00');
    Artisan::call('statements:close-month');

    $statement = OwnerStatement::query()->where('owner_id', $owner->id)->where('locked', true)->firstOrFail();
    expect($statement->period_start->toDateString())->toBe('2026-09-01')
        ->and($statement->period_end->toDateString())->toBe('2026-09-30')
        ->and($statement->status)->toBe('finalized')
        ->and($statement->sequence_no)->toBe(1)
        ->and($statement->gross_minor)->toBe(20000);

    // a second run the same day closes nothing twice
    Artisan::call('statements:close-month');
    expect(OwnerStatement::query()->where('owner_id', $owner->id)->count())->toBe(1);
});

it('VY-26 (statements) CONTROL: an organisation that picked no day keeps closing by hand — nothing is closed for it', function () {
    $s = seedScenario(1);
    $owner = vy26OwnerWithSeptember($s);
    vy26CloseDay($s, null);

    $this->travelTo('2026-10-05 05:00:00');
    Artisan::call('statements:close-month');

    expect(OwnerStatement::query()->where('owner_id', $owner->id)->count())->toBe(0);
});

it('⚠️ VY-26 (statements): an owner paid in another currency is left for a person — the job never guesses an exchange rate — and the run goes on', function () {
    $s = seedScenario(1);
    $owner = vy26OwnerWithSeptember($s);
    $owner->update(['payout_currency' => 'USD']);
    vy26CloseDay($s, 5);

    $this->travelTo('2026-10-05 05:00:00');
    expect(Artisan::call('statements:close-month'))->toBe(0);
    expect(OwnerStatement::query()->where('owner_id', $owner->id)->where('locked', true)->count())->toBe(0);
});

it('VY-26 (statements): the day is set on the Payout run page by someone who may close periods — and refused to anyone else', function () {
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s, [], 'Front Desk'))->post(route('app.payouts.statement-schedule'), ['close_day' => 3])->assertForbidden();
    auth()->logout();

    $accountant = operatorUser($s, [], 'Accountant');   // holds payouts.manage
    $this->actingAs($accountant)->get(route('app.payouts.run'))->assertOk()->assertSee('data-statement-schedule', false);
    $this->actingAs($accountant)->post(route('app.payouts.statement-schedule'), ['close_day' => 3])->assertRedirect(route('app.payouts.run'));
    expect(Organization::query()->whereKey($s['org']->id)->value('statement_close_day'))->toBe(3);

    $this->actingAs($accountant)->post(route('app.payouts.statement-schedule'), ['close_day' => 0])->assertRedirect();
    expect(Organization::query()->whereKey($s['org']->id)->value('statement_close_day'))->toBeNull();

    $this->actingAs($accountant)->post(route('app.payouts.statement-schedule'), ['close_day' => 31])->assertSessionHasErrors('close_day');
});

// ── what the site says ───────────────────────────────────────────────────────────────────────────────────────────────────────────────────

it('⚠️ VY-26: the site says what is built — statements close on the day the operator chooses; payouts are not claimed to run by themselves', function (string $key, array $gone, array $present) {
    $page = str_replace("\u{2019}", "'", html_entity_decode(mkPage($key), ENT_QUOTES | ENT_HTML5));

    foreach ($gone as $words) {
        expect($page)->not->toContain($words);
    }
    foreach ($present as $words) {
        expect($page)->toContain($words);
    }
})->with([
    'how it works' => ['how-it-works', ['statements and payouts run without anyone', 'Statements generate on your schedule', 'first automatic statements'], ['owner statements close by themselves on the day you choose']],
    'for villas' => ['for-villas', ['automated owner statements and payouts', 'Statements that write themselves', 'generated monthly as PDF'], ['closed on the day of the month you choose']],
    'for apartments' => ['for-apartments', ['generate statements and payouts automatically'], ['owner statements that close on the day you choose']],
    'the example scenarios' => ['case-studies', ['Statements now generate themselves'], ['Statements now close themselves at month end']],
    'for hotels (VY-09 already removed it, and it stays removed)' => ['for-hotels', ['night audit'], []],
]);
