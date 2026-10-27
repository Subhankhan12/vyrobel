<?php

use App\Support\Reports\ReportPeriod;
use Carbon\CarbonImmutable;
use Tests\TestCase;

uses(TestCase::class);   // I18N-01 — the period's labels are translated, so the app (and its translator) must be booted

// GATE 10.1 — the ONE Reports period resolver (shared by the page + CSV + PDF exports, so they can't drift). A
// valid ?month=YYYY-MM is a specific calendar month; otherwise the named range; a malformed month falls through.

beforeEach(fn () => CarbonImmutable::setTestNow('2026-08-15'));
afterEach(fn () => CarbonImmutable::setTestNow());

it('resolves a specific calendar month from ?month=YYYY-MM', function () {
    $p = ReportPeriod::resolve(null, '2026-03');

    expect($p['key'])->toBe('month:2026-03')
        ->and($p['label'])->toBe('March 2026')
        ->and($p['start']->toDateString())->toBe('2026-03-01')
        ->and($p['end']->toDateString())->toBe('2026-04-01');   // half-open [start, end)
});

it('lets a valid month win over a named range', function () {
    $p = ReportPeriod::resolve('6m', '2026-03');
    expect($p['key'])->toBe('month:2026-03');
});

it('falls through to this-year on a malformed month', function () {
    foreach (['garbage', '2026-13-99', '26-3', ''] as $bad) {
        expect(ReportPeriod::resolve(null, $bad)['key'])->toBe('this-year');
    }
});

it('resolves the named roll-up ranges', function () {
    expect(ReportPeriod::resolve('this-year')['key'])->toBe('this-year')
        ->and(ReportPeriod::resolve('this-year')['label'])->toBe('2026')
        ->and(ReportPeriod::resolve('ytd')['key'])->toBe('ytd')
        ->and(ReportPeriod::resolve('6m')['key'])->toBe('6m')
        ->and(ReportPeriod::resolve('nonsense')['key'])->toBe('this-year');   // default
});

it('offers the last 12 months and marks the active one', function () {
    $months = ReportPeriod::monthOptions('month:2026-03');

    expect($months)->toHaveCount(12)
        ->and($months[0]['value'])->toBe('2026-08')          // newest first (this month)
        ->and(collect($months)->firstWhere('value', '2026-03')['active'])->toBeTrue()
        ->and(collect($months)->firstWhere('value', '2026-08')['active'])->toBeFalse();
});
