<?php

declare(strict_types=1);

use App\Models\Availability;
use App\Models\Organization;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Support\Inventory\CalendarHorizon;
use App\Support\Platform\Health\HealthBoard;
use App\Support\Platform\Health\HealthCard;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// ⚠️⚠️ VY-07 (audit of 2026-11-24) — THE BOOKABLE CALENDAR IS KEPT A YEAR AHEAD, EVERY DAY, FOR EVERY ORGANISATION.
//
// Nights were published for 365 days when a unit or room type was created or switched — and by nothing else. No scheduled task added a
// day, so the window shrank daily and closed a year after the last inventory change. `availability:extend` (daily) adds the missing
// nights; the console's health board reads the shortfall with its OWN query, because a job cannot report that it did not run.
//
// ⚠️ The suite's clock is fixed, and its fixtures publish only 2026-09-01 → 09-10, so every seedScenario() room type starts SHORT —
//    which is the defect's own shape (a calendar that ends long before a year ahead).

uses(DatabaseTransactions::class);

function vy07LastNight(string $roomTypeId): ?string
{
    $last = DB::table('availability')->where('room_type_id', $roomTypeId)->max('date');

    return $last === null ? null : CarbonImmutable::parse((string) $last)->toDateString();
}

function vy07Shortfall(string $roomTypeId): ?array
{
    return collect(app(CalendarHorizon::class)->shortfalls())->firstWhere('room_type_id', $roomTypeId);
}

it('⚠️⚠️ VY-07: the daily job publishes a short calendar through today + 365 days — and never touches a night already there', function () {
    $s = seedScenario(2);
    $rt = (string) $s['roomType']->id;
    $today = CarbonImmutable::today();
    book($s, ['unit_id' => $s['units'][0]->id]);   // sold = 1 on 2026-09-01 and 09-02

    $before = DB::table('availability')->where('room_type_id', $rt)->orderBy('date')->get()->map(fn ($r) => (array) $r)->all();
    expect(vy07LastNight($rt))->toBe('2026-09-09')            // the fixture's window: far short of a year
        ->and(vy07Shortfall($rt))->not->toBeNull();

    app(TenantContext::class)->forget();                        // the scheduler has no tenant
    $this->artisan('availability:extend')->assertExitCode(0);

    // published through today + 364 (365 nights from today), every night present, each with the room type's real capacity
    app(TenantContext::class)->set($s['org']->id);
    expect(vy07LastNight($rt))->toBe($today->addDays(CalendarHorizon::DAYS - 1)->toDateString())
        ->and(vy07Shortfall($rt))->toBeNull();

    $added = Availability::query()->where('room_type_id', $rt)->whereDate('date', '>', '2026-09-09')->get();
    expect($added->count())->toBe((int) CarbonImmutable::parse('2026-09-10')->diffInDays($today->addDays(CalendarHorizon::DAYS)))
        ->and($added->every(fn (Availability $a): bool => $a->total === 2 && $a->sold === 0 && $a->organization_id === $s['org']->id))->toBeTrue();

    // ⚠️ it only ever ADDS after the last published night: nothing is created before the first one either
    expect(vy07LastNight($rt))->not->toBeNull()
        ->and(CarbonImmutable::parse((string) DB::table('availability')->where('room_type_id', $rt)->min('date'))->toDateString())->toBe('2026-09-01');

    // ⚠️ the nights that were already published are byte-identical — `sold` above all
    $after = DB::table('availability')->where('room_type_id', $rt)->whereDate('date', '<=', '2026-09-09')->orderBy('date')->get()->map(fn ($r) => (array) $r)->all();
    expect($after)->toBe($before)
        ->and(soldOn($rt, '2026-09-01'))->toBe(1);
});

it('VY-07: a second run the same day adds nothing; the next day adds exactly one night', function () {
    $s = seedScenario(1);
    $rt = (string) $s['roomType']->id;
    $horizon = app(CalendarHorizon::class);
    $today = CarbonImmutable::today();

    $horizon->extend($today);
    $rows = DB::table('availability')->where('room_type_id', $rt)->count();

    $again = $horizon->extend($today);
    expect(DB::table('availability')->where('room_type_id', $rt)->count())->toBe($rows);

    $tomorrow = $horizon->extend($today->addDay());
    expect(DB::table('availability')->where('room_type_id', $rt)->count())->toBe($rows + 1)
        ->and(vy07LastNight($rt))->toBe($today->addDays(CalendarHorizon::DAYS)->toDateString());

    // (other organisations' room types live in this persistent test database too, so the totals are ≥, never ==)
    expect($again['room_types'])->toBeGreaterThanOrEqual(1)
        ->and($tomorrow['nights'])->toBeGreaterThanOrEqual(1);
});

it('⚠️ VY-07: it reaches EVERY organisation, each inside its own tenant — and a room type with no night at all is published from today', function () {
    $a = seedScenario(1);
    $aRt = (string) $a['roomType']->id;

    $b = seedScenario(1);   // a second organisation; the tenant context is now B's
    $bare = RoomType::create(['property_id' => $b['property']->id, 'name' => 'Never published', 'kind' => 'room']);
    Unit::create(['property_id' => $b['property']->id, 'room_type_id' => $bare->id, 'code' => 'NP1']);
    expect(vy07LastNight((string) $bare->id))->toBeNull();

    app(TenantContext::class)->forget();
    $this->artisan('availability:extend')->assertExitCode(0);

    $want = CarbonImmutable::today()->addDays(CalendarHorizon::DAYS - 1)->toDateString();
    expect(vy07LastNight($aRt))->toBe($want)
        ->and(vy07LastNight((string) $b['roomType']->id))->toBe($want)
        ->and(vy07LastNight((string) $bare->id))->toBe($want)
        ->and(DB::table('availability')->where('room_type_id', $bare->id)->min('date'))->toStartWith(CarbonImmutable::today()->toDateString());

    // each row carries ITS OWN organisation — never the other's, never none
    expect(DB::table('availability')->where('room_type_id', $aRt)->distinct()->pluck('organization_id')->all())->toBe([(string) $a['org']->id])
        ->and(DB::table('availability')->where('room_type_id', $bare->id)->distinct()->pluck('organization_id')->all())->toBe([(string) $b['org']->id]);
});

it('VY-07: an archived property and an archived organisation are left alone — nothing is published for them', function () {
    $s = seedScenario(1);
    $gone = secondProperty($s, 'Archived House');
    $goneRt = RoomType::create(['property_id' => $gone->id, 'name' => 'Gone', 'kind' => 'room']);
    Property::query()->whereKey($gone->id)->delete();   // soft delete = archived

    $t = seedScenario(1);
    $archivedRt = (string) $t['roomType']->id;
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($t['org']->id)->update(['deleted_at' => now()]));

    app(TenantContext::class)->forget();
    $this->artisan('availability:extend')->assertExitCode(0);

    expect(vy07LastNight((string) $goneRt->id))->toBeNull()
        ->and(vy07LastNight($archivedRt))->toBe('2026-09-09')
        ->and(vy07Shortfall((string) $goneRt->id))->toBeNull()        // …and neither is reported as short
        ->and(vy07Shortfall($archivedRt))->toBeNull();
});

it('⚠️⚠️ VY-07 ALARM: the health board reads the shortfall with its own query — red when weeks short, green once the job has run', function () {
    $s = seedScenario(1);
    $rt = (string) $s['roomType']->id;
    $card = fn (): HealthCard => collect(app(HealthBoard::class)->cards())->firstOrFail(fn (HealthCard $c): bool => $c->key === 'calendar');

    // the fixture's calendar ended long ago → short by far more than two weeks
    expect($card()->status)->toBe(HealthCard::RED)
        ->and(vy07Shortfall($rt)['short_by'])->toBeGreaterThan(CalendarHorizon::RED_AFTER_DAYS);

    app(CalendarHorizon::class)->extend();
    expect($card()->status)->toBe(HealthCard::GREEN)
        ->and($card()->headline)->toContain((string) CalendarHorizon::DAYS);

    // a few days without the job → amber (short, but not yet weeks)
    $inAWeek = CarbonImmutable::today()->addDays(5);
    $short = collect(app(CalendarHorizon::class)->shortfalls($inAWeek))->firstWhere('room_type_id', $rt);
    expect($short['short_by'])->toBe(5)->and($short['days_ahead'])->toBe(CalendarHorizon::DAYS - 5);

    // one night short is the normal state between midnight and the run — not a finding
    expect(collect(app(CalendarHorizon::class)->shortfalls(CarbonImmutable::today()->addDay()))->firstWhere('room_type_id', $rt))->toBeNull();
});

it('⚠️ VY-07: the command FAILS, with one error line, when a calendar is still short after it ran', function () {
    $s = seedScenario(1);
    $rt = (string) $s['roomType']->id;

    // Make the job unable to publish: the recompute is replaced by one that does nothing.
    app()->instance(AvailabilityService::class, new class extends AvailabilityService
    {
        public function recompute(string $roomTypeId, CarbonInterface|string $start, CarbonInterface|string $end): void {}
    });
    app()->forgetInstance(CalendarHorizon::class);
    Log::spy();

    app(TenantContext::class)->forget();
    $this->artisan('availability:extend')->assertExitCode(1);

    Log::shouldHaveReceived('error')->once()->withArgs(fn (string $message): bool => str_contains($message, 'STILL short'));
    expect(vy07LastNight($rt))->toBe('2026-09-09');
});

it('VY-07: the job is on the schedule, daily', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($e): bool => is_string($e->command ?? null) && str_contains($e->command, 'availability:extend'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('30 1 * * *');
});
