<?php

use App\Models\HousekeepingTask;
use App\Services\HousekeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── F-13 · F-16 — ONE SCREEN, TWO FINDINGS ───────────────────────────────────────────────────────
// F-16 was recorded BEFORE Fix 03 gave the server a guard, so it was RE-MEASURED rather than inherited.
// 🔬 The result refined it: `assertTransition()` bounds STATUS only and nothing in the status path reads
// `assigned_to`, so the server agrees with the LIST view exactly — and the BOARD's "Assign to start" was
// asserting a rule that does not exist anywhere.

function hbTask(array $s, array $o = []): HousekeepingTask
{
    return HousekeepingTask::create(array_merge([
        'organization_id' => $s['org']->id, 'property_id' => $s['property']->id,
        'unit_id' => $s['units'][0]->id, 'date' => '2026-09-03',
        'type' => 'departure', 'status' => 'pending', 'priority' => 'normal',
    ], $o));
}

it('⚠️⚠️ THE SERVER IS THE AUTHORITY — it starts an UNASSIGNED task, so no view may claim otherwise', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Housekeeping');
    $task = hbTask($s);

    expect($task->assigned_to)->toBeNull();

    // ⚠️ THIS IS THE ANCHOR FOR THE WRONG-FIX MUTATION. Two views that agree with EACH OTHER but not with
    // this are still wrong. The claim is measured against the server, never against the other view.
    $this->actingAs($actor);
    app(HousekeepingService::class)->updateTaskStatus($task, 'in_progress', $actor);

    expect((string) $task->fresh()->status)->toBe('in_progress')
        ->and($task->fresh()->assigned_to)->toBeNull();
});

it('⚠️ F-16 — BOTH views offer the forward control on an unassigned pending task, matching the server', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Housekeeping');
    hbTask($s);

    $html = $this->actingAs($actor)->get(route('app.housekeeping'))->assertOk()->getContent();

    // ⚠️ COUNT THE ACTUAL STATUS FORMS, PER VIEW. A first version counted occurrences of 'housekeeping/',
    // which also matches the ASSIGN routes — so it stayed green under the consistent-but-false mutation.
    // The mutation exposed the weakness; this is the corrected assertion.
    $statusForms = preg_match_all('~<form[^>]+action="[^"]*/housekeeping/[^"]+/status"~', $html);

    // One from the board card, one from the list row — the same unassigned pending task, both views.
    expect($statusForms)->toBe(2, "expected BOTH views to offer the forward control, saw {$statusForms}");

    // ⚠️ THE FALSE CLAIM IS GONE. It said "Assign to start" — a rule the server has never had.
    expect($html)->not->toContain('Assign to start');
});

it('⚠️ F-16 — the DRAG gate matches the button gate, so a card is draggable exactly when it can advance', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Housekeeping');
    hbTask($s);   // unassigned + pending: the exact case the old gate blocked

    $html = $this->actingAs($actor)->get(route('app.housekeeping'))->assertOk()->getContent();

    // Third surface of one rule: the drag gate encoded the same false condition and its docblock said so.
    expect($html)->toContain('vy-hk-drag')
        ->and($html)->toContain('draggable="true"');
});

it('⚠️ F-13 — a role that may look but not act is TOLD SO, and told who can grant it', function (string $role) {
    $s = seedScenario(1);
    $actor = operatorUser($s, [], $role);
    hbTask($s);

    $html = $this->actingAs($actor)->get(route('app.housekeeping'))->assertOk()->getContent();

    // ⚠️ READ THE RENDERED PAGE. Asserting the copy exists in a Blade file is the shape that let three
    // flash findings hide.
    expect($html)->toContain('but not update it')
        ->and($html)->toContain('Property Owner or Group Admin');

    // And the controls stay ABSENT — the better outcome. This adds an explanation, never a control.
    expect($html)->not->toContain('name="status"');
})->with(['Front Desk', 'Maintenance', 'Guest Support Agent']);

it('⚠️ F-13 — a role that CAN act is not shown the explanation', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Housekeeping');
    hbTask($s);

    $html = $this->actingAs($actor)->get(route('app.housekeeping'))->assertOk()->getContent();

    expect($html)->not->toContain('but not update it')
        ->and($html)->toContain('name="status"');   // non-vacuity: this role really does get controls
});
