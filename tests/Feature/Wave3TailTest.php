<?php

declare(strict_types=1);

use App\Livewire\HousekeepingBoard;
use App\Models\HousekeepingTask;
use App\Models\Membership;
use App\Models\User;
use App\Support\Property\PropertyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * REGRESSION — the WAVE 3 tail.
 */

/**
 * A real HOUSEKEEPER: an active member of the scenario's org holding the team-scoped `Housekeeping` role —
 * which is what StaffRoster::housekeepers() actually filters on (StaffDepartment maps that role to the
 * HOUSEKEEPING department). A back-office role alone would NOT put them in the crew list.
 *
 * Declared locally: a `function` in a Pest file only loads when THAT file loads, and the equivalents in
 * OperatorHousekeepingViewTest are not visible here.
 */
function w3Housekeeper(array $scenario, string $name): User
{
    $user = User::factory()->create(['name' => $name, 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $scenario['org'], 'Housekeeping');
}

/**
 * Activate the property context the board captures at mount. Livewire::test does not run the /app route
 * middleware (SetCurrentPropertyForWebRequest), so without this the read-set is empty, no tasks are in
 * scope, and every crew load reads 0 — which would make the suggestion test pass for the wrong reason.
 */
function w3Context(array $scenario): void
{
    app(PropertyContext::class)->activate([(string) $scenario['property']->id], (string) $scenario['property']->id);
}

function w3Task(array $scenario, string $userId, string $status): HousekeepingTask
{
    return HousekeepingTask::create([
        'organization_id' => $scenario['org']->id,
        'property_id' => $scenario['property']->id,
        'unit_id' => $scenario['units'][0]->id,
        'date' => '2026-09-03',
        'type' => 'departure',
        'status' => $status,
        'priority' => 'high',
        'assigned_to' => $userId,
    ]);
}

/**
 * "Suggested" meant "first in the list", not "least loaded".
 *
 * The assign picker badged AND pre-selected `$i === 0` — whoever `StaffRoster::housekeepers()` happened to
 * return first (name order) — while the SAME row printed that person's real open-task load right beside it.
 * A suggestion that contradicts the number next to it is a false claim (#39).
 *
 * ⚠️ The fix COMPUTES the suggestion instead of re-sorting the crew list, because that list also drives the
 * filter chips and the crew panel; ordering those by load would make the filters shuffle as work is assigned.
 */
it('suggests the genuinely least-loaded housekeeper, not the alphabetically first', function () {
    $s = seedScenario(1);

    // Two real housekeepers. "Aaron" sorts first by name but is BUSY; "Zoe" sorts last but is FREE.
    $aaron = w3Housekeeper($s, 'Aaron Busy');
    $zoe = w3Housekeeper($s, 'Zoe Free');

    // Aaron holds two open tasks; Zoe holds none.
    foreach ([1, 2] as $_) {
        w3Task($s, (string) $aaron->id, 'pending');
    }

    w3Context($s);
    $data = Livewire::actingAs($aaron)->test(HousekeepingBoard::class);

    $crew = collect($data->viewData('crew'));
    $suggested = $data->viewData('suggestedCrewId');

    // The crew list itself is UNCHANGED in order (the filter chips must stay stable)…
    expect($crew->pluck('name')->all())->toContain('Aaron Busy', 'Zoe Free');

    // …and the suggestion is the free one, not the first one.
    expect($suggested)->toBe((string) $zoe->id)
        ->and($crew->firstWhere('id', $suggested)['load'])->toBe(0)
        ->and($crew->firstWhere('name', 'Aaron Busy')['load'])->toBe(2);
});

it('still suggests someone when every housekeeper is equally loaded', function () {
    $s = seedScenario(1);
    $a = w3Housekeeper($s, 'Aaron Even');
    w3Housekeeper($s, 'Zoe Even');

    // No tasks at all — every load is 0, so the tie must resolve stably rather than returning null.
    w3Context($s);
    $suggested = Livewire::actingAs($a)->test(HousekeepingBoard::class)->viewData('suggestedCrewId');

    expect($suggested)->not->toBeNull();
});

it('does not count finished work against a housekeeper’s load', function () {
    $s = seedScenario(1);
    $aaron = w3Housekeeper($s, 'Aaron Busy');
    $zoe = w3Housekeeper($s, 'Zoe Free');

    // Aaron's tasks are CLEAN (finished) — he is not actually busy, so he should be suggestible again.
    foreach ([1, 2, 3] as $_) {
        w3Task($s, (string) $aaron->id, 'clean');
    }
    w3Task($s, (string) $zoe->id, 'pending');

    w3Context($s);
    $data = Livewire::actingAs($aaron)->test(HousekeepingBoard::class);

    expect($data->viewData('suggestedCrewId'))->toBe((string) $aaron->id)
        ->and(collect($data->viewData('crew'))->firstWhere('id', (string) $aaron->id)['load'])->toBe(0);
});

it('counts only open work — the load a suggestion is based on is real', function () {
    $s = seedScenario(1);
    $aaron = w3Housekeeper($s, 'Aaron Busy');

    w3Task($s, (string) $aaron->id, 'pending');
    w3Task($s, (string) $aaron->id, 'in_progress');
    w3Task($s, (string) $aaron->id, 'clean');

    w3Context($s);
    $crew = collect(Livewire::actingAs($aaron)->test(HousekeepingBoard::class)->viewData('crew'));

    // pending + in_progress count; a finished ('clean') task does not.
    expect($crew->firstWhere('id', (string) $aaron->id)['load'])->toBe(2);

    expect(HousekeepingTask::query()->count())->toBe(3);
});
