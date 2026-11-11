<?php

declare(strict_types=1);

use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Services\OwnerUnitStatusService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

// ⚠️⚠️ D-UNIT-SINGLE-TXN (found 2026-09-30 by reading; fixed 2026-11-11, his "do all").
// The SINGLE Pause / Resume — shared by the operator's Units page and the owner portal's pause (#69) — wrote the flag, recomputed
// the room type's availability, then wrote the audit row as three separate commits. A recompute that failed left the unit flipped,
// availability stale, and no audit row to say so. The bulk action was already all-or-nothing; the single one now is too.

function ustFailingRecompute(): void
{
    app()->instance(AvailabilityService::class, new class extends AvailabilityService
    {
        public function recompute(string $roomTypeId, CarbonInterface|string $start, CarbonInterface|string $end): void
        {
            throw new RuntimeException('recompute failed');
        }
    });
}

it('⚠️⚠️ a failed recompute leaves the unit, its availability and the audit trail exactly as they were (operator pause)', function () {
    $s = seedScenario(1);
    $unit = Unit::query()->findOrFail($s['units'][0]->id);
    expect((bool) $unit->is_active)->toBeTrue();

    ustFailingRecompute();
    expect(fn () => app(OwnerUnitStatusService::class)->deactivateByOperator($unit))->toThrow(RuntimeException::class, 'recompute failed');

    expect((bool) DB::table('units')->where('id', $unit->id)->value('is_active'))->toBeTrue()   // NOT flipped
        ->and(DB::table('audit_events')->where('action', 'unit.deactivated')->where('subject_id', $unit->id)->count())->toBe(0);
})->group('security');

it('⚠️⚠️ …and the same for the owner portal\'s resume path, which shares the write', function () {
    $s = seedScenario(1);
    $unit = Unit::query()->findOrFail($s['units'][0]->id);
    DB::table('units')->where('id', $unit->id)->update(['is_active' => false]);
    $unit->refresh();

    ustFailingRecompute();
    expect(fn () => app(OwnerUnitStatusService::class)->reactivateByOperator($unit))->toThrow(RuntimeException::class);

    expect((bool) DB::table('units')->where('id', $unit->id)->value('is_active'))->toBeFalse()   // still paused
        ->and(DB::table('audit_events')->where('action', 'unit.reactivated')->where('subject_id', $unit->id)->count())->toBe(0);
})->group('security');

it('CONTROL: with a working recompute the pause lands — the flag, the availability and ONE audit row', function () {
    $s = seedScenario(1);
    $unit = Unit::query()->findOrFail($s['units'][0]->id);

    app(OwnerUnitStatusService::class)->deactivateByOperator($unit);

    expect((bool) DB::table('units')->where('id', $unit->id)->value('is_active'))->toBeFalse()
        ->and(DB::table('audit_events')->where('action', 'unit.deactivated')->where('subject_id', $unit->id)->count())->toBe(1);

    // a second pause is a no-op (idempotent), not a second row
    app(OwnerUnitStatusService::class)->deactivateByOperator($unit->refresh());
    expect(DB::table('audit_events')->where('action', 'unit.deactivated')->where('subject_id', $unit->id)->count())->toBe(1);
});
