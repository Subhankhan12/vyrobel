<?php

use App\Models\HousekeepingTask;
use App\Services\HousekeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── F-15 · THE HOUSEKEEPING STATUS LADDER IS BOUNDED ─────────────────────────────────────────────
// The controller once validated only the TARGET, so a `pending` task jumped straight to `inspected`:
// a dirty room became guest-ready in one request, `started_at` NULL, and `inspected_by` named the
// person who skipped both steps. The bound now lives in HousekeepingService::assertTransition().
//
// ⚠️ EVERY ASSERTION HERE READS THE ROW, NOT THE STATUS CODE. Phase 10 recorded that three of four
// probes returned 302 and told nothing; a PRG redirect is indistinguishable from a refusal.

function hkLadderTask(array $s, string $status = 'pending', array $overrides = []): HousekeepingTask
{
    return HousekeepingTask::create(array_merge([
        'organization_id' => $s['org']->id,
        'property_id' => $s['property']->id,
        'unit_id' => $s['units'][0]->id,
        'date' => '2026-09-03',
        'type' => 'departure',
        'status' => $status,
        'priority' => 'normal',
    ], $overrides));
}

/** Every column the defect moved, read straight off the row. */
function hkLadderRow(HousekeepingTask $t): array
{
    $f = $t->fresh();

    return [
        'status' => (string) $f->status,
        'started_at' => $f->started_at?->toDateTimeString(),
        'completed_at' => $f->completed_at?->toDateTimeString(),
        'inspected_by' => $f->inspected_by,
    ];
}

it('⚠️ F-15 — refuses pending → inspected through the product’s own form, PROVEN BY ROW STATE', function () {
    $s = seedScenario(1);
    $task = hkLadderTask($s, 'pending');

    // NON-VACUITY: the row really is the untouched shape the defect started from.
    expect(hkLadderRow($task))->toBe([
        'status' => 'pending', 'started_at' => null, 'completed_at' => null, 'inspected_by' => null,
    ]);

    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))
        ->post(route('app.housekeeping.status', $task->id), ['status' => 'inspected'])
        ->assertRedirect(route('app.housekeeping'));

    // ⚠️ THE ROW IS THE EVIDENCE — not the 302. Nothing moved: not the status, not a timestamp,
    // and above all not the column that names a person.
    expect(hkLadderRow($task))->toBe([
        'status' => 'pending', 'started_at' => null, 'completed_at' => null, 'inspected_by' => null,
    ]);

    // And the unit never became guest-ready.
    expect((string) $s['units'][0]->fresh()->housekeeping_status)->not->toBe('inspected');
});

it('⚠️ refuses in_progress → inspected — skipping the CLEAN assertion, not merely the start marker', function () {
    $s = seedScenario(1);
    $task = hkLadderTask($s, 'in_progress', ['started_at' => now()]);
    $before = hkLadderRow($task);

    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))
        ->post(route('app.housekeeping.status', $task->id), ['status' => 'inspected'])
        ->assertRedirect(route('app.housekeeping'));

    expect(hkLadderRow($task))->toBe($before)
        ->and(hkLadderRow($task)['inspected_by'])->toBeNull();
});

it('✅ POSITIVE CONTROL — the whole legal ladder still runs end to end for the role the board exists for', function (string $role) {
    $s = seedScenario(1);
    $task = hkLadderTask($s, 'pending');
    $actor = operatorUser($s, [], $role);

    foreach (['in_progress', 'clean', 'inspected'] as $step) {
        $this->actingAs($actor)
            ->post(route('app.housekeeping.status', $task->id), ['status' => $step])
            ->assertRedirect(route('app.housekeeping'));

        expect((string) $task->fresh()->status)->toBe($step);
    }

    // ⚠️ Without this control, "everything refused" and "the path is broken" are the same observation.
    $row = hkLadderRow($task);
    expect($row['started_at'])->not->toBeNull()
        ->and($row['completed_at'])->not->toBeNull()
        ->and($row['inspected_by'])->not->toBeNull()
        ->and((string) $s['units'][0]->fresh()->housekeeping_status)->toBe('inspected');
})->with(['Housekeeping', 'Hotel Manager']);   // ⚠️ the HOUSEKEEPER first — the role the board exists for

it('⚠️ EVERY ENTRANCE — the SERVICE itself refuses the jump, so a guard on the controller alone is not a guard', function () {
    $s = seedScenario(1);
    $task = hkLadderTask($s, 'pending');

    // F-18's shape was a correct map the writing path never consulted. This probe bypasses the
    // controller entirely — it is the test that goes RED if the guard is moved up into the route layer.
    expect(fn () => app(HousekeepingService::class)->updateTaskStatus($task, 'inspected'))
        ->toThrow(RuntimeException::class, 'Cannot mark a pending housekeeping task inspected.');

    expect(hkLadderRow($task))->toBe([
        'status' => 'pending', 'started_at' => null, 'completed_at' => null, 'inspected_by' => null,
    ]);
});

it('⚠️ the refusal EXPLAINS ITSELF on the board — naming the attempt and the actual state', function () {
    $s = seedScenario(1);
    $task = hkLadderTask($s, 'pending');
    $actor = operatorUser($s, [], 'Hotel Manager');

    // F-1's shape, three times over: a flash a controller SETS and no view RENDERS. Follow the
    // redirect and read the rendered page, never the session bag.
    $this->actingAs($actor)->post(route('app.housekeeping.status', $task->id), ['status' => 'inspected']);

    $html = $this->actingAs($actor)->get(route('app.housekeeping'))->assertOk()->getContent();

    expect($html)->toContain('is pending')          // the state it is actually in
        ->and($html)->toContain('marked Inspected'); // what was attempted
});

it('⚠️ AMBIGUOUS-AND-PERMITTED — the moves the founder has not ruled on still work exactly as today', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Hotel Manager');

    // One person cleans without pressing Start (skips the PROGRESS MARKER — no assertion is lost).
    $a = hkLadderTask($s, 'pending');
    $this->actingAs($actor)->post(route('app.housekeeping.status', $a->id), ['status' => 'clean']);
    expect((string) $a->fresh()->status)->toBe('clean');

    // A clean room is found dirty again and sent back to in_progress (a BACKWARD move).
    $b = hkLadderTask($s, 'clean', ['unit_id' => $s['units'][0]->id]);
    $this->actingAs($actor)->post(route('app.housekeeping.status', $b->id), ['status' => 'in_progress']);
    expect((string) $b->fresh()->status)->toBe('in_progress');

    // ⚠️ NOT ENDORSED — asserted so that tightening the map is a DELIBERATE act that breaks a test,
    // never a silent narrowing. Each is a ruling in docs/HOUSEKEEPING-LADDER.md.
});
