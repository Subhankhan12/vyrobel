<?php

use App\Models\RatePlan;
use App\Models\UnitRateOverride;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// D18-5 — the rates grid's blast-radius label, reconciled for per-unit pricing.
//
// ⚠️ WHY THIS LABEL EXISTS AT ALL. A rate plan prices a ROOM TYPE, so one save moves the price of every unit
// underneath it. The row says "3 units" precisely so an operator is never misled about how far a save
// reaches — the grid's own docblock says so. That was exactly right while type-level was the only pricing
// there was, and became a small lie the moment a unit could carry its own price: a type-level save does not
// reach an overridden unit.
//
// ⚠️ TRANSITION-CORRECT IS THE WHOLE DIFFICULTY. With no overrides the corrected count IS the old count, so
// the label must be byte-identical to what it has always shown. The fix may only change what it says once
// there is genuinely something to exclude.

const RGB_FROM = '2026-09-01';

/** An operator who may see the rates grid, on a property whose type has two units. */
function rgbSetup(): array
{
    $s = seedScenario(2);
    $user = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s, [], 'Revenue Manager');

    return ['s' => $s, 'user' => $user, 'session' => ['current_property_id' => $s['property']->id]];
}

function rgbLabel(array $f): string
{
    $html = test()->actingAs($f['user'])->withSession($f['session'])
        ->get('/app/rates?from='.RGB_FROM)->assertOk()->getContent();

    preg_match('/(\d+ of \d+ units · \d+ priced individually|\d+ units?)/', $html, $m);

    return $m[1] ?? '(no label found)';
}

function rgbOverride(array $f, int $unitIndex, string $date): UnitRateOverride
{
    $plan = RatePlan::query()->where('room_type_id', $f['s']['roomType']->id)->firstOrFail();

    return UnitRateOverride::create([
        'organization_id' => $f['s']['org']->id,
        'rate_plan_id' => $plan->id,
        'unit_id' => $f['s']['units'][$unitIndex]->id,
        'date' => $date,
        'price_minor' => 22500,
        'currency' => 'EUR',
    ]);
}

// ── 1. ⚠️⚠️ ZERO OVERRIDES — the label is EXACTLY what it has always been ─────────────────────────
it('shows the unchanged unit count while no unit carries its own price', function () {
    $f = rgbSetup();

    expect(UnitRateOverride::count())->toBe(0)
        // ⚠️ The old wording, to the character. A "fix" that reworded this at zero overrides would have
        // regressed a warning that was already telling the truth.
        ->and(rgbLabel($f))->toBe('2 units');
});

// ── 2. ⚠️⚠️ ONE OVERRIDE — the count drops by exactly one, and the label says why ────────────────
it('excludes a unit that carries its own price', function () {
    $f = rgbSetup();
    rgbOverride($f, 0, RGB_FROM);

    // ⚠️ 1 of 2, not "2 units" — a type-level save reaches only the un-overridden unit, and the wording
    // follows the number rather than quietly excluding one behind the old phrasing.
    expect(rgbLabel($f))->toBe('1 of 2 units · 1 priced individually');
});

// ── 3. ⚠️ SCOPE — an override OUTSIDE the displayed window does not change the count ──────────────
it('ignores an override outside the shown date window', function () {
    $f = rgbSetup();
    // The grid shows a fortnight; this override sits well beyond it, so a save in THIS window still
    // reaches that unit and it must stay counted.
    rgbOverride($f, 0, CarbonImmutable::parse(RGB_FROM)->addMonths(3)->toDateString());

    expect(rgbLabel($f))->toBe('2 units');
});

// ── 4. ⚠️ BOTH UNITS OVERRIDDEN — a type save reaches nobody, and the label admits it ─────────────
it('reports zero affected when every unit is priced individually', function () {
    $f = rgbSetup();
    rgbOverride($f, 0, RGB_FROM);
    rgbOverride($f, 1, RGB_FROM);

    expect(rgbLabel($f))->toBe('0 of 2 units · 2 priced individually');
});
