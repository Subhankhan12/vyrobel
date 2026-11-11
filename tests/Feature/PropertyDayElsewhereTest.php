<?php

declare(strict_types=1);

use App\Models\HousekeepingTask;
use App\Models\Membership;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Support\Property\PropertyContext;
use App\Support\Property\PropertyDay;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

// ⚠️ D-DAY-ELSEWHERE (found 2026-11-11 fixing D-RACK-START-TZ; fixed 2026-11-11, his go). The Calendar, the Front Desk and the walk-in
// already open on the PROPERTY's day; the top bar's date pill, the Housekeeping board (Today / Tomorrow / Overdue, the "guest arriving today"
// urgency) and "Assign all"'s order were still on the app's day (UTC), hours off wherever the property is. Same instant used throughout:
// Saturday 1 Aug 22:30 in UTC is already Sunday 2 Aug in Baku.

function pdy_at(string $utc, string $timezone, callable $assert): void
{
    $previous = Carbon::getTestNow();
    try {
        Carbon::setTestNow($utc);
        $s = seedScenario(2);
        DB::table('properties')->where('id', $s['property']->id)->update(['timezone' => $timezone]);
        $user = User::factory()->create();
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $assert($s, grantBackOfficeRole($user, $s['org']));
    } finally {
        Carbon::setTestNow($previous);
    }
}

/** the housekeeping task (pending departure clean) for one of the scenario's units, on a given date */
function pdy_task(array $s, int $unit, string $date): HousekeepingTask
{
    return HousekeepingTask::create([
        'organization_id' => $s['org']->id, 'property_id' => $s['property']->id, 'unit_id' => $s['units'][$unit]->id,
        'date' => $date, 'type' => 'departure', 'status' => 'pending', 'priority' => 'normal',
    ]);
}

/** a guest arriving on $date into one of the scenario's units */
function pdy_arrival(array $s, int $unit, string $date): void
{
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-07-30', '2026-08-10');
    book($s, ['unit_id' => $s['units'][$unit]->id, 'check_in' => $date, 'check_out' => '2026-08-05']);
}

it('⚠️⚠️ the top bar\'s date pill is the property\'s day — Baku is already Sunday while the app (UTC) is still on Saturday', function () {
    pdy_at('2026-08-01 22:30:00', 'Asia/Baku', function (array $s, User $user): void {
        $html = test()->actingAs($user)->get('/app/dashboard')->assertOk()->getContent();

        expect($html)->toContain('Today · Sun · 2 Aug')->not->toContain('Today · Sat · 1 Aug');
    });
});

it('CONTROL: a UTC property\'s pill is the app\'s day, as before', function () {
    pdy_at('2026-08-01 22:30:00', 'UTC', function (array $s, User $user): void {
        $html = test()->actingAs($user)->get('/app/dashboard')->assertOk()->getContent();

        expect($html)->toContain('Today · Sat · 1 Aug')->not->toContain('Today · Sun · 2 Aug');
    });
});

it('⚠️ the pill gets the same day on a Livewire UPDATE, where the property context is not active', function () {
    // `/livewire/update` is not in the /app group, so the context middleware has not run; the shell re-renders there and must not fall back
    // to the app's day — it asks the session (the person's allow-list, then the stored selection) the question the middleware asks
    pdy_at('2026-08-01 22:30:00', 'Asia/Baku', function (array $s, User $user): void {
        test()->actingAs($user);
        app(PropertyContext::class)->deactivate();

        session(['current_property_id' => (string) $s['property']->id]);
        expect(app(PropertyContext::class)->active())->toBeFalse()
            ->and(PropertyDay::workingTimezone())->toBe('Asia/Baku')
            ->and(PropertyDay::workingToday()->toDateString())->toBe('2026-08-02');

        // "all properties", or a stale id, falls back to the first property the person may use — never to nothing
        session(['current_property_id' => PropertyContext::ALL]);
        expect(PropertyDay::workingToday()->toDateString())->toBe('2026-08-02');
        session(['current_property_id' => '01ZZZZZZZZZZZZZZZZZZZZZZZZ']);
        expect(PropertyDay::workingToday()->toDateString())->toBe('2026-08-02');

        // no signed-in person → the app's day
        auth()->guard('web')->logout();
        expect(PropertyDay::workingTimezone())->toBeNull()
            ->and(PropertyDay::workingToday()->toDateString())->toBe('2026-08-01');
    });
});

it('⚠️⚠️ the Housekeeping board judges a room\'s day against ITS property — 2 Aug is Today at Baku, Tomorrow in UTC', function () {
    pdy_at('2026-08-01 22:30:00', 'Asia/Baku', function (array $s, User $user): void {
        pdy_task($s, 0, '2026-08-02');
        $html = test()->actingAs($user)->get(route('app.housekeeping'))->assertOk()->getContent();

        expect($html)->toContain('vy-hk-day is-today')->not->toContain('vy-hk-day is-later');
    });
    pdy_at('2026-08-01 22:30:00', 'UTC', function (array $s, User $user): void {
        pdy_task($s, 0, '2026-08-02');
        $html = test()->actingAs($user)->get(route('app.housekeeping'))->assertOk()->getContent();

        expect($html)->toContain('vy-hk-day is-later')->not->toContain('vy-hk-day is-today');   // CONTROL
    });
});

it('⚠️ "Guest arriving today" is a guest arriving today AT THE PROPERTY', function () {
    pdy_at('2026-08-01 22:30:00', 'Asia/Baku', function (array $s, User $user): void {
        pdy_arrival($s, 0, '2026-08-02');
        pdy_task($s, 0, '2026-08-02');
        $html = test()->actingAs($user)->get(route('app.housekeeping'))->assertOk()->getContent();

        expect($html)->toContain('Guest arriving today');
    });
    pdy_at('2026-08-01 22:30:00', 'UTC', function (array $s, User $user): void {
        pdy_arrival($s, 0, '2026-08-02');
        pdy_task($s, 0, '2026-08-02');
        $html = test()->actingAs($user)->get(route('app.housekeeping'))->assertOk()->getContent();

        expect($html)->not->toContain('Guest arriving today');   // CONTROL: 2 Aug is tomorrow there
    });
});

it('⚠️⚠️ "Assign all" puts the rooms with a guest arriving today (at the property) first', function () {
    pdy_at('2026-08-01 22:30:00', 'Asia/Baku', function (array $s, User $user): void {
        // the room with NO arrival is the earlier date, so it would go first by date alone: only the arrival can put the other ahead of it
        $first = pdy_task($s, 0, '2026-08-01');
        $arriving = pdy_task($s, 1, '2026-08-02'); // a guest arrives here on the property's 2 Aug = today
        pdy_arrival($s, 1, '2026-08-02');
        foreach (['Ana Cleaner', 'Bo Cleaner'] as $name) {
            $c = User::factory()->create(['name' => $name, 'status' => 'active']);
            Membership::create(['organization_id' => $s['org']->id, 'user_id' => $c->id, 'status' => 'active']);
            grantBackOfficeRole($c, $s['org'], 'Housekeeping');
        }

        test()->actingAs($user)->post(route('app.housekeeping.assign-all'))->assertRedirect(route('app.housekeeping'));
        $ids = session('hk_undo')['ids'] ?? [];

        expect($ids)->toHaveCount(2)
            ->and($ids[0])->toBe((string) $arriving->id)->and($ids[1])->toBe((string) $first->id);
    });
});

it('⚠️ "Assign all" ordering: a waiting guest first, then a high priority, then the earlier date — each a real comparison', function () {
    // found beside the day fix: the three sort keys were one-argument closures handed to `sortBy([...])`, which calls each as a
    // COMPARATOR with two arguments — so each looked only at the first room and returned 0 or 1 (the date key returned the date string),
    // and the order held only by luck on two rooms. Six rooms in a deliberately awkward order make it a real test.
    pdy_at('2026-08-01 10:00:00', 'UTC', function (array $s, User $user): void {
        $s = seedScenario(6);
        DB::table('properties')->where('id', $s['property']->id)->update(['timezone' => 'UTC']);
        $user = grantBackOfficeRole(tap(User::factory()->create(), fn (User $u) => Membership::create(['organization_id' => $s['org']->id, 'user_id' => $u->id, 'status' => 'active'])), $s['org']);
        $make = fn (int $unit, string $date, string $priority): HousekeepingTask => HousekeepingTask::create([
            'organization_id' => $s['org']->id, 'property_id' => $s['property']->id, 'unit_id' => $s['units'][$unit]->id,
            'date' => $date, 'type' => 'departure', 'status' => 'pending', 'priority' => $priority,
        ]);
        $late = $make(0, '2026-08-05', 'normal');
        $highLate = $make(1, '2026-08-06', 'high');
        $waiting = $make(2, '2026-08-07', 'normal');   // a guest arrives here TODAY
        $early = $make(3, '2026-08-01', 'normal');
        $waitingHigh = $make(4, '2026-08-09', 'high'); // a guest arrives here TODAY, and it is a high priority
        $mid = $make(5, '2026-08-03', 'normal');
        pdy_arrival($s, 2, '2026-08-01');
        pdy_arrival($s, 4, '2026-08-01');
        foreach (['Ana Cleaner', 'Bo Cleaner'] as $name) {
            $c = User::factory()->create(['name' => $name, 'status' => 'active']);
            Membership::create(['organization_id' => $s['org']->id, 'user_id' => $c->id, 'status' => 'active']);
            grantBackOfficeRole($c, $s['org'], 'Housekeeping');
        }

        test()->actingAs($user)->post(route('app.housekeeping.assign-all'))->assertRedirect(route('app.housekeeping'));
        $ids = session('hk_undo')['ids'] ?? [];

        // waiting guests first (the high priority of the two ahead), then the other high priority, then by date
        expect($ids)->toBe(array_map(fn (HousekeepingTask $t): string => (string) $t->id, [$waitingHigh, $waiting, $highLate, $early, $mid, $late]));
    });
});

it('CONTROL: grouping — properties on different days are judged each on its own', function () {
    pdy_at('2026-08-01 22:30:00', 'Asia/Baku', function (array $s, User $user): void {
        $utcSite = secondProperty($s, 'Utc House');   // seedScenario's second property is UTC
        $todays = PropertyDay::todays([(string) $s['property']->id, (string) $utcSite->id]);

        expect($todays[(string) $s['property']->id])->toBe('2026-08-02')
            ->and($todays[(string) $utcSite->id])->toBe('2026-08-01')
            ->and(PropertyDay::groupedByToday([(string) $s['property']->id, (string) $utcSite->id]))
            ->toBe(['2026-08-02' => [(string) $s['property']->id], '2026-08-01' => [(string) $utcSite->id]])
            ->and(PropertyDay::todays([]))->toBe([]);
    });
});
