<?php

declare(strict_types=1);

use App\Models\HousekeepingTask;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ⚠️⚠️ VY-24 (audit of 2026-11-24, read in code) — A HOUSEKEEPER CANNOT REPORT A DEFECT.
//
// Opening a work order needed `maintenance.manage`, which the Housekeeping role does not hold: the person most likely to find a leaking
// shower had no way to say so in the product. The board's room card now has "Report a problem": a POST gated on the housekeeper's own
// `housekeeping.manage`, which opens a work order through the certified MaintenanceService. ⚠️ REPORTING IS NOT MANAGING — the room is
// never taken out of order from here (that stays maintenance's decision, with VY-03's overbooking check), and nothing else of
// maintenance becomes reachable: the maintenance write routes still refuse the Housekeeping role.

uses(DatabaseTransactions::class);

function vy24Task(array $s): HousekeepingTask
{
    return HousekeepingTask::create([
        'organization_id' => $s['org']->id, 'property_id' => $s['property']->id, 'unit_id' => $s['units'][0]->id,
        'date' => '2026-09-03', 'type' => 'departure', 'status' => 'pending', 'priority' => 'normal',
    ]);
}

it('⚠️⚠️ VY-24 (as read): a housekeeper still cannot open a work order on the maintenance route — and CAN report from the board, which opens one', function () {
    $s = seedScenario(1);
    $task = vy24Task($s);
    $housekeeper = operatorUser($s, [], 'Housekeeping');

    // the maintenance write route is unchanged: reporting did not widen it
    $this->actingAs($housekeeper)->post(route('app.maintenance.store'), [
        'unit_id' => $s['units'][0]->id, 'title' => 'Leak', 'category' => 'plumbing', 'priority' => 'normal',
    ])->assertForbidden();

    $this->actingAs($housekeeper)->post(route('app.housekeeping.report', $task->id), [
        'title' => 'Shower is leaking', 'description' => 'Under the tray, bathroom floor wet.', 'category' => 'plumbing',
    ])->assertRedirect(route('app.housekeeping', ['task' => $task->id]))->assertSessionHas('hk_notice');

    $ticket = MaintenanceTicket::query()->where('unit_id', $s['units'][0]->id)->latest('opened_at')->firstOrFail();
    expect($ticket->title)->toBe('Shower is leaking')
        ->and($ticket->description)->toBe('Under the tray, bathroom floor wet.')
        ->and($ticket->category)->toBe('plumbing')
        ->and($ticket->priority)->toBe('normal')
        ->and($ticket->status)->toBe('open')
        ->and((string) $ticket->reported_by)->toBe((string) $housekeeper->id)
        ->and((bool) $ticket->takes_out_of_service)->toBeFalse();

    // the room is left exactly as it was — only maintenance takes a room out of order
    expect((string) $s['units'][0]->fresh()->condition_status)->toBe('in_service');
});

it('VY-24: "urgent" opens an urgent work order; a report that says nothing is refused and opens nothing', function () {
    $s = seedScenario(1);
    $task = vy24Task($s);
    $housekeeper = operatorUser($s, [], 'Housekeeping');

    $this->actingAs($housekeeper)->post(route('app.housekeeping.report', $task->id), ['title' => 'Water on the floor', 'category' => 'plumbing', 'urgent' => '1']);
    expect(MaintenanceTicket::query()->where('unit_id', $s['units'][0]->id)->value('priority'))->toBe('urgent');

    $before = MaintenanceTicket::query()->count();
    $this->actingAs($housekeeper)->post(route('app.housekeeping.report', $task->id), ['title' => '', 'category' => 'plumbing'])
        ->assertSessionHasErrors('title');
    $this->actingAs($housekeeper)->post(route('app.housekeeping.report', $task->id), ['title' => 'x', 'category' => 'not-a-kind'])
        ->assertSessionHasErrors('category');
    expect(MaintenanceTicket::query()->count())->toBe($before);
});

it('⚠️ VY-24: the gate is the housekeeper\'s own permission — Front Desk (view only) is refused; another property\'s or organisation\'s room is a 404', function () {
    $s = seedScenario(1);
    $task = vy24Task($s);

    $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->post(route('app.housekeeping.report', $task->id), ['title' => 'Lamp', 'category' => 'electrical'])->assertForbidden();
    auth()->logout();

    $other = seedScenario(1);
    $this->actingAs(operatorUser($other, [], 'Housekeeping'))
        ->post(route('app.housekeeping.report', $task->id), ['title' => 'Lamp', 'category' => 'electrical'])->assertNotFound();
    auth()->logout();

    app(TenantContext::class)->set($s['org']->id);   // the far property is the SAME organisation's (seedScenario() moved the context)
    $far = Property::create(['name' => 'Far', 'slug' => 'far-'.Str::lower(Str::random(6)), 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC', 'status' => 'active']);
    $scoped = operatorUser($s, [], 'Housekeeping');
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $scoped->id,
        'property_id' => $far->id, 'created_at' => now(), 'updated_at' => now()]);
    $this->actingAs($scoped)
        ->post(route('app.housekeeping.report', $task->id), ['title' => 'Lamp', 'category' => 'electrical'])->assertNotFound();

    expect(MaintenanceTicket::query()->where('unit_id', $s['units'][0]->id)->count())->toBe(0);
});

it('VY-24: the board offers "Report a problem" to the housekeeper, and not to a role that may only look', function () {
    $s = seedScenario(1);
    vy24Task($s);
    $this->travelTo('2026-09-03 09:00:00');

    $this->actingAs(operatorUser($s, [], 'Housekeeping'))->get(route('app.housekeeping'))
        ->assertOk()->assertSee('data-hk-report', false)->assertSee(__('livewire/housekeeping-board.problem_send'));
    auth()->logout();

    $this->actingAs(operatorUser($s, [], 'Front Desk'))->get(route('app.housekeeping'))
        ->assertOk()->assertDontSee('data-hk-report', false);
});
