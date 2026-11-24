<?php

declare(strict_types=1);

use App\Exceptions\Housekeeping\SecondPersonInspectionRequired;
use App\Models\HousekeepingTask;
use App\Models\PropertyPolicy;
use App\Services\HousekeepingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// ⚠️⚠️ VY-27 (audit of 2026-11-24, read in code) — A ROOM COULD GO FROM CLEAN TO INSPECTED BY THE SAME PERSON.
//
// The ladder already refuses pending → inspected (F-15). What it allowed was one person pressing "clean" and then "inspected", so the
// inspection said nothing the cleaner had not already said. The fix is the optional setting the audit asked for: "Inspection by a second
// person" (Settings → Policies, default OFF — every property keeps working exactly as before until it chooses the rule). When on, whoever
// marked the room clean (`cleaned_by`, recorded from now on) cannot also mark it inspected; anybody else can.

uses(DatabaseTransactions::class);

function vy27Task(array $s, string $status = 'pending'): HousekeepingTask
{
    return HousekeepingTask::create([
        'organization_id' => $s['org']->id, 'property_id' => $s['property']->id, 'unit_id' => $s['units'][0]->id,
        'date' => '2026-09-03', 'type' => 'departure', 'status' => $status, 'priority' => 'normal',
    ]);
}

function vy27Require(array $s, bool $on = true): void
{
    PropertyPolicy::query()->updateOrCreate(['property_id' => $s['property']->id], ['inspection_by_second_person' => $on]);
}

it('VY-27: marking a room clean records WHO cleaned it', function () {
    $s = seedScenario(1);
    $task = vy27Task($s);
    $cleaner = operatorUser($s, [], 'Housekeeping');

    $this->actingAs($cleaner)->post(route('app.housekeeping.status', $task->id), ['status' => 'clean'])->assertRedirect();

    expect((string) $task->fresh()->cleaned_by)->toBe((string) $cleaner->id);
});

it('⚠️⚠️ VY-27 (as read): with the setting on, the person who cleaned the room is refused the inspection — and told why; the room stays clean', function () {
    $s = seedScenario(1);
    vy27Require($s);
    $task = vy27Task($s);
    $cleaner = operatorUser($s, [], 'Housekeeping');

    $this->actingAs($cleaner)->post(route('app.housekeeping.status', $task->id), ['status' => 'clean']);
    $this->actingAs($cleaner)->post(route('app.housekeeping.status', $task->id), ['status' => 'inspected'])
        ->assertRedirect(route('app.housekeeping'))
        ->assertSessionHas('hk_error', __('php/http/controllers/housekeeping_action_controller.second_person_required', ['unit' => $s['units'][0]->code]));

    $fresh = $task->fresh();
    expect($fresh->status)->toBe('clean')->and($fresh->inspected_by)->toBeNull();

    // and at the service, for every caller
    expect(fn () => app(HousekeepingService::class)->updateTaskStatus($fresh, 'inspected', $cleaner))->toThrow(SecondPersonInspectionRequired::class);
});

it('VY-27 ALLOW: with the setting on, a SECOND person inspects — and is recorded as the inspector', function () {
    $s = seedScenario(1);
    vy27Require($s);
    $task = vy27Task($s);
    $cleaner = operatorUser($s, [], 'Housekeeping');
    $supervisor = operatorUser($s, [], 'Hotel Manager');

    $this->actingAs($cleaner)->post(route('app.housekeeping.status', $task->id), ['status' => 'clean']);
    $this->actingAs($supervisor)->post(route('app.housekeeping.status', $task->id), ['status' => 'inspected'])->assertSessionHas('hk_notice');

    $fresh = $task->fresh();
    expect($fresh->status)->toBe('inspected')->and((string) $fresh->inspected_by)->toBe((string) $supervisor->id);
});

it('VY-27 CONTROL: with the setting OFF (the default), one person may clean and inspect, exactly as before', function () {
    $s = seedScenario(1);
    $task = vy27Task($s);
    $cleaner = operatorUser($s, [], 'Housekeeping');

    $this->actingAs($cleaner)->post(route('app.housekeeping.status', $task->id), ['status' => 'clean']);
    $this->actingAs($cleaner)->post(route('app.housekeeping.status', $task->id), ['status' => 'inspected'])->assertSessionHas('hk_notice');

    expect($task->fresh()->status)->toBe('inspected');
});

it('VY-27: a room cleaned before the rule could know who cleaned it (cleaned_by empty) is not refused', function () {
    $s = seedScenario(1);
    vy27Require($s);
    $task = vy27Task($s, 'clean');   // cleaned before 2026-11-25: nobody recorded

    $this->actingAs(operatorUser($s, [], 'Housekeeping'))->post(route('app.housekeeping.status', $task->id), ['status' => 'inspected'])
        ->assertSessionHas('hk_notice');
    expect($task->fresh()->status)->toBe('inspected');
});

it('VY-27: the manager switches the rule on and off in Settings → Policies; a form that does not send it leaves it alone', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Property Owner');
    $base = ['pet' => 'allowed', 'smoking' => 'not_allowed', 'min_stay' => '1'];

    $this->actingAs($manager)->post('/app/settings/policies', $base + ['inspection_by_second_person' => '1'])->assertRedirect();
    $policy = PropertyPolicy::query()->where('property_id', $s['property']->id)->firstOrFail();
    expect($policy->inspection_by_second_person)->toBeTrue();

    $this->actingAs($manager)->post('/app/settings/policies', $base)->assertRedirect();   // an older tab without the switch
    expect($policy->fresh()->inspection_by_second_person)->toBeTrue();

    $this->actingAs($manager)->post('/app/settings/policies', $base + ['inspection_by_second_person' => '0'])->assertRedirect();
    expect($policy->fresh()->inspection_by_second_person)->toBeFalse();
});
