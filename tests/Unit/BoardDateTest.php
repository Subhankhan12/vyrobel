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
