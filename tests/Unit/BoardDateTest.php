<?php

use App\Support\Schedule\BoardDate;
use Carbon\CarbonImmutable;

// F-OP-1 — the ONE shared rule for the operator Front Desk + Calendar/Rack default day. Pure, no DB.

it('honors an explicit valid date param', function () {
    expect(BoardDate::resolve('2026-12-13', true, '2026-07-01')->toDateString())->toBe('2026-12-13');
});

it('ignores an invalid date param and applies the default rule', function () {
    expect(BoardDate::resolve('not-a-date', true, null)->toDateString())
        ->toBe(CarbonImmutable::today()->toDateString());
});

it('opens on TODAY when there is current or upcoming activity', function () {
    // Even though a past arrival exists, current/future activity → today (the board's purpose).
    expect(BoardDate::resolve(null, true, '2026-07-01')->toDateString())
        ->toBe(CarbonImmutable::today()->toDateString());
});

it('falls back to the most recent PAST arrival for a stale dataset — never the future', function () {
    // No current/future activity → land on the most recent arrival ON OR BEFORE today (real occupancy),
    // NEVER a furthest-future max (the F-OP-1 bug).
    expect(BoardDate::resolve(null, false, '2026-07-01')->toDateString())->toBe('2026-07-01');
});

it('defaults to today when there is no activity at all', function () {
    expect(BoardDate::resolve(null, false, null)->toDateString())
        ->toBe(CarbonImmutable::today()->toDateString());
});

// ── D-RACK-START-TZ (2026-11-11, his "do all") — the board's day is the day AT THE PROPERTY ──────────────────
it('⚠️ opens on the PROPERTY\'s today, not the app\'s (UTC) day — seen live: a Paris property at 00:15 Monday opened on Sunday', function () {
    CarbonImmutable::setTestNow('2026-10-04 22:30:00 UTC');   // 00:30 on Monday 5 Oct in Paris, still Sunday 4 Oct in UTC and in Lisbon (WEST = UTC+1 → 23:30)

    expect(BoardDate::today('Europe/Paris')->toDateString())->toBe('2026-10-05')
        ->and(BoardDate::today('UTC')->toDateString())->toBe('2026-10-04')
        ->and(BoardDate::today(null)->toDateString())->toBe('2026-10-04')   // no property → the app's day, as before
        ->and(BoardDate::today('')->toDateString())->toBe('2026-10-04')
        // the rule: an explicit date still wins; otherwise today AT THE PROPERTY
        ->and(BoardDate::resolve(null, true, null, 'Europe/Paris')->toDateString())->toBe('2026-10-05')
        ->and(BoardDate::resolve('2026-12-13', true, null, 'Europe/Paris')->toDateString())->toBe('2026-12-13')
        ->and(BoardDate::resolve(null, true, null)->toDateString())->toBe('2026-10-04');   // 3-arg callers unchanged
    // and a far-west property is BEHIND UTC
    CarbonImmutable::setTestNow('2026-10-05 02:00:00 UTC');
    expect(BoardDate::today('America/New_York')->toDateString())->toBe('2026-10-04')   // 22:00 on the 4th in New York
        ->and(BoardDate::today('Asia/Baku')->toDateString())->toBe('2026-10-05');
    CarbonImmutable::setTestNow();
});
