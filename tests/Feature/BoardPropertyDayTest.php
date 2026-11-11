<?php

declare(strict_types=1);

use App\Models\Membership;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

// ⚠️ D-RACK-START-TZ (found 2026-10-04 by reading; seen live 2026-10-05 00:15 Paris; fixed 2026-11-11, his "do all").
// The Calendar and the Front Desk opened on the APP's day (UTC), not the property's — so far from UTC the default day was a day off for
// hours each day, and the walk-in's "tonight" could be the wrong night. They open on the day it is AT THE PROPERTY.

function bpdAt(string $utc, string $timezone, callable $assert): void
{
    $previous = Carbon::getTestNow();
    try {
        Carbon::setTestNow($utc);
        $s = seedScenario(1);
        DB::table('properties')->where('id', $s['property']->id)->update(['timezone' => $timezone]);
        $user = User::factory()->create();
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $assert($s, grantBackOfficeRole($user, $s['org']));
    } finally {
        Carbon::setTestNow($previous);
    }
}

it('⚠️ the Front Desk opens on the PROPERTY\'s day — Baku is already Sunday while the app (UTC) is still on Saturday', function () {
    bpdAt('2026-08-01 22:30:00', 'Asia/Baku', function (array $s, User $user): void {
        $html = test()->actingAs($user)->get('/app/front-desk')->assertOk()->getContent();

        expect($html)->toContain('Sunday, 2 Aug 2026')->not->toContain('Saturday, 1 Aug 2026');
        // the prev/next links are anchored on the property's day, so Today is today THERE
        expect($html)->toContain('date=2026-08-01')->toContain('date=2026-08-03');
    });
});

it('CONTROL: a UTC property opens on the app\'s day, as before', function () {
    bpdAt('2026-08-01 22:30:00', 'UTC', function (array $s, User $user): void {
        $html = test()->actingAs($user)->get('/app/front-desk')->assertOk()->getContent();

        expect($html)->toContain('Saturday, 1 Aug 2026')->not->toContain('Sunday, 2 Aug 2026');
    });
});

it('⚠️ the Calendar\'s default week starts on the property\'s day too', function () {
    bpdAt('2026-08-01 22:30:00', 'Asia/Baku', function (array $s, User $user): void {
        $html = test()->actingAs($user)->get(route('app.calendar'))->assertOk()->getContent();
        // the window starts on Sunday 2 Aug at the property, so "previous" (14 days back) starts 19 Jul; a UTC property's would start 18 Jul
        expect($html)->toContain('start=2026-07-19')->not->toContain('start=2026-07-18');
    });

    bpdAt('2026-08-01 22:30:00', 'UTC', function (array $s, User $user): void {
        $html = test()->actingAs($user)->get(route('app.calendar'))->assertOk()->getContent();
        expect($html)->toContain('start=2026-07-18')->not->toContain('start=2026-07-19');
    });
});
