<?php

declare(strict_types=1);

use App\Livewire\Owner\OwnerDashboard;
use App\Models\Owner;
use App\Models\OwnerMonthlyTarget;
use App\Models\OwnerStatement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * REGRESSION — the WAVE 3 tail: the owner earnings chart scaled to the GOAL, not the data.
 *
 * `earningsSeries()` scaled bars against `max($maxNet, $goalMinor)` so the goal line always sat inside the
 * chart. A goal well above actual earnings then crushed every real bar. Measured on the demo before the fix:
 * a €6,000 goal against a €885.60 best month rendered the bars at 7–15% height; against the data max the
 * same months are 50–100%. The chart exists to make the REAL months readable.
 *
 * ⚠️ #39 — nothing is invented to fill the axis. When the goal sits above the plotted range the line pins to
 * the top AND the caption says "above this range", so its position cannot imply a proximity that isn't real.
 */
function ownerWithMonths(array $scenario, array $netsMinor, ?int $goalMinor): Owner
{
    $owner = ownUnit($scenario['units'][0]);

    $month = 1;
    foreach ($netsMinor as $net) {
        OwnerStatement::create([
            'organization_id' => $scenario['org']->id, 'owner_id' => $owner->id,
            'period_start' => sprintf('2026-%02d-01', $month), 'period_end' => sprintf('2026-%02d-28', $month),
            'currency' => 'EUR', 'source_currency' => 'EUR',
            'net_payable_minor' => $net, 'status' => 'finalized',
        ]);
        $month++;
    }

    if ($goalMinor !== null) {
        OwnerMonthlyTarget::create([
            'organization_id' => $scenario['org']->id, 'owner_id' => $owner->id,
            'currency' => 'EUR', 'target_minor' => $goalMinor,
        ]);
    }

    return $owner;
}

it('scales the bars to the real data, not to an out-of-reach goal', function () {
    $s = seedScenario(1);
    // A goal ~7x the best month — the demo's own shape before the fix.
    $owner = ownerWithMonths($s, [40000, 60000, 88560], 600000);

    $series = Livewire::actingAs(ownerActor($owner, $s))->test(OwnerDashboard::class)->viewData('series');

    $pcts = array_column($series['bars'], 'pct');

    // The best month tops the chart; the smallest is still readable — NOT the 7–15% the goal-scale produced.
    expect(max($pcts))->toBe(100)
        ->and(min($pcts))->toBeGreaterThan(30);
});

it('says so when the goal sits above the plotted range, rather than implying proximity', function () {
    $s = seedScenario(1);
    $owner = ownerWithMonths($s, [40000, 88560], 600000);

    $goal = Livewire::actingAs(ownerActor($owner, $s))->test(OwnerDashboard::class)->viewData('series')['goal'];

    expect($goal['above_range'])->toBeTrue()
        ->and($goal['display'])->toContain('6,000.00');   // the REAL figure is still stated
});

it('keeps the goal inside the chart when it is genuinely within reach', function () {
    $s = seedScenario(1);
    // A goal BELOW the best month — it must plot normally, not be flagged.
    $owner = ownerWithMonths($s, [40000, 88560], 50000);

    $goal = Livewire::actingAs(ownerActor($owner, $s))->test(OwnerDashboard::class)->viewData('series')['goal'];

    expect($goal['above_range'])->toBeFalse()
        ->and($goal['pct'])->toBeLessThan(100)
        ->and($goal['pct'])->toBeGreaterThan(0);
});

it('renders no goal line at all when the owner has set no target', function () {
    $s = seedScenario(1);
    $owner = ownerWithMonths($s, [40000, 88560], null);

    // Never a fabricated goal (#39).
    expect(Livewire::actingAs(ownerActor($owner, $s))->test(OwnerDashboard::class)->viewData('series')['goal'])->toBeNull();
});
