<?php

declare(strict_types=1);

use App\Models\HousekeepingTask;
use App\Models\Membership;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

// ⚠️ D-HK-UTC-CLOCK (found 2026-11-11 by the second open-list pass, fixed 2026-11-12). The Housekeeping board's "started 22:30" and "done 23:15" printed the
// STORED clock time — UTC — while the board's days (Today / Tomorrow / Overdue) already read the property's own. A cleaner who started at 02:30 in Baku was
// told "started 22:30". The instants are stored in UTC and stay so; only the clock printed on the card is the property's.

/** a board with one task started at 22:30 UTC and (optionally) finished at 23:15 UTC on 1 Aug, for a property in the given timezone */
function hkc_board(string $timezone, string $status, callable $assert): void
{
    $previous = Carbon::getTestNow();
    try {
        Carbon::setTestNow('2026-08-01 23:30:00');
        $s = seedScenario(1);
        DB::table('properties')->where('id', $s['property']->id)->update(['timezone' => $timezone]);
        HousekeepingTask::create([
            'organization_id' => $s['org']->id, 'property_id' => $s['property']->id, 'unit_id' => $s['units'][0]->id,
            'date' => '2026-08-01', 'type' => 'departure', 'status' => $status, 'priority' => 'normal',
            'started_at' => '2026-08-01 22:30:00', 'completed_at' => $status === 'clean' ? '2026-08-01 23:15:00' : null,
        ]);
        $user = User::factory()->create();
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $assert(test()->actingAs(grantBackOfficeRole($user, $s['org']))->get(route('app.housekeeping'))->assertOk()->getContent());
    } finally {
        Carbon::setTestNow($previous);
    }
}

it('⚠️ an in-progress room says it started at the PROPERTY\'s clock — 22:30 UTC is 02:30 in Baku', function () {
    hkc_board('Asia/Baku', 'in_progress', function (string $html): void {
        expect($html)->toContain('started 02:30')->not->toContain('started 22:30');
    });
});

it('⚠️ a finished room says it was done at the PROPERTY\'s clock — 23:15 UTC is 03:15 in Baku', function () {
    hkc_board('Asia/Baku', 'clean', function (string $html): void {
        expect($html)->toContain('03:15')->not->toContain('23:15');
    });
});

it('CONTROL: a UTC property reads exactly the stored clock, as before', function () {
    hkc_board('UTC', 'in_progress', function (string $html): void {
        expect($html)->toContain('started 22:30');
    });
});
