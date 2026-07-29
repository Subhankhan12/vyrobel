<?php

use App\Payouts\PayoutSchedule;
use Carbon\CarbonImmutable;

// The pure payout CADENCE rule (payout engine · Phase 2) — deterministic, no DB/clock.
// 2026 anchors (verified): 08-01 Sat, 08-03 Mon(1st biz day); 09-01 Tue, 09-02 Wed, 09-03 Thu;
// 09-07 Mon; 09-16 Wed, 09-17 Thu; 10-01 Thu, 10-02 Fri, 10-05 Mon(3rd biz day).

$D = fn (string $d) => CarbonImmutable::parse($d);

it('monthly — due on the Nth business day of the month', function () use ($D) {
    $prefs = ['frequency' => 'monthly', 'payout_day' => 3];

    expect(PayoutSchedule::isDue($prefs, $D('2026-09-03')))->toBeTrue()   // 3rd business day
        ->and(PayoutSchedule::isDue($prefs, $D('2026-09-02')))->toBeFalse()
        ->and(PayoutSchedule::isDue($prefs, $D('2026-09-04')))->toBeFalse();
});

it('monthly — the Nth business day SKIPS weekends (a month starting on a weekend)', function () use ($D) {
    $prefs = ['frequency' => 'monthly', 'payout_day' => 1];

    // August 2026 starts on Sat; the 1st business day is Mon 08-03, not 08-01.
    expect(PayoutSchedule::isDue($prefs, $D('2026-08-01')))->toBeFalse()
        ->and(PayoutSchedule::isDue($prefs, $D('2026-08-03')))->toBeTrue();
});

it('weekly — due on the chosen ISO weekday only', function () use ($D) {
    $prefs = ['frequency' => 'weekly', 'payout_day' => 1];   // Monday

    expect(PayoutSchedule::isDue($prefs, $D('2026-09-07')))->toBeTrue()    // Monday
        ->and(PayoutSchedule::isDue($prefs, $D('2026-09-03')))->toBeFalse(); // Thursday
});

it('twice monthly — due on the Nth business day from the 1st AND from the 16th', function () use ($D) {
    $prefs = ['frequency' => 'twice_monthly', 'payout_day' => 2];

    expect(PayoutSchedule::isDue($prefs, $D('2026-09-02')))->toBeTrue()    // 2nd biz day from the 1st
        ->and(PayoutSchedule::isDue($prefs, $D('2026-09-17')))->toBeTrue() // 2nd biz day from the 16th
        ->and(PayoutSchedule::isDue($prefs, $D('2026-09-03')))->toBeFalse();
});

it('nextRunDate — the first future date the cadence fires (never fabricated)', function () use ($D) {
    $prefs = ['frequency' => 'monthly', 'payout_day' => 3];

    // From mid-September, the next 3rd-business-day is Mon 2026-10-05 (Oct starts Thu; 10-01,10-02,10-05).
    expect(PayoutSchedule::nextRunDate($prefs, $D('2026-09-10'))->toDateString())->toBe('2026-10-05');

    // On a due day, nextRunDate returns that same day.
    expect(PayoutSchedule::nextRunDate($prefs, $D('2026-09-03'))->toDateString())->toBe('2026-09-03');
});
