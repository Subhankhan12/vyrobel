<?php

use App\Http\Controllers\Operator\InventoryActionController;
use App\Http\Middleware\SetCurrentPropertyForWebRequest;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\ArrivalInfoService;
use App\Services\AssignmentService;
use App\Support\Guest\ArrivalPack;
use App\Support\Property\PropertyContext;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Assert;

uses(RefreshDatabase::class);

// ══ CLUSTER E — THE ARRIVAL SECRETS GET A WRITER ═════════════════════════════════════════════════════
//
// ⚠️⚠️ WHAT WAS WRONG. Every reference to `door_code`, `wifi_ssid`, `wifi_password` and `arrival_notes` in
// the application was a READ — the guest arrival screen, the pre-arrival email, the arrival PDF. The only
// WRITER was the demo seeder. So invariant #33 was carefully gating the release of data that no operator
// could enter: the guard was real, and what it guarded was unreachable.
//
// ⚠️ THESE ARE CREDENTIALS TO A PHYSICAL SPACE. The tests below pin all three security positions, because
// each of them is the kind of thing that quietly degrades: encrypted at rest, never rendered back into the
// page, and audited BY FIELD NAME rather than by value.

const AI_CODE = 'HUNTER-4417';

function arrivalScenario(): array
{
    $s = seedScenario(1);
    $admin = operatorUser(['org' => $s['org']], ['name' => 'Ada Admin'], 'Group Admin');

    return ['s' => $s, 'unit' => $s['units'][0], 'admin' => $admin, 'org' => $s['org']];
}

// ══ REACHABILITY ═════════════════════════════════════════════════════════════════════════════════════
it('⚠️ registers an app-reachable, permission-gated writer for the arrival secrets', function () {
    foreach (['app.inventory.units.arrival-info', 'app.inventory.units.arrival-info.reveal'] as $name) {
        $route = Route::getRoutes()->getByName($name);
        Assert::assertNotNull($route, "⚠️ {$name} is missing — the arrival secrets are seeder-only again");
        Assert::assertContains('POST', $route->methods());
        Assert::assertContains('can:units.manage', $route->gatherMiddleware());
    }
});

// ══ ⚠️ SECRET HANDLING — the three positions ═════════════════════════════════════════════════════════
it('⚠️ stores the door code ENCRYPTED at rest, never as plaintext in the column', function () {
    ['unit' => $unit, 'admin' => $admin] = arrivalScenario();

    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info", [
        'door_code' => AI_CODE, 'wifi_ssid' => 'Villa-Guest', 'wifi_password' => 'sekrit-pw',
    ])->assertRedirect();

    Assert::assertSame(AI_CODE, (string) $unit->fresh()->door_code);   // reads back through the cast

    // ⚠️ THE RAW COLUMN, read past Eloquent — the cast would hide a plaintext column perfectly.
    $raw = (string) DB::table('units')->where('id', $unit->id)->value('door_code');
    Assert::assertNotSame(AI_CODE, $raw, '⚠️ the door code is stored in PLAINTEXT');
    Assert::assertStringNotContainsString(AI_CODE, $raw);

    // The SSID is deliberately NOT encrypted — a router broadcasts it to the street.
    Assert::assertSame('Villa-Guest', (string) DB::table('units')->where('id', $unit->id)->value('wifi_ssid'));
});

it('⚠️ audits WHICH fields changed and never their values', function () {
    ['unit' => $unit, 'admin' => $admin] = arrivalScenario();

    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info", ['door_code' => AI_CODE])->assertRedirect();

    $row = DB::table('audit_events')->where('action', 'unit.arrival_info_changed')->first();
    Assert::assertNotNull($row, 'an arrival-info write was not audited');
    Assert::assertStringContainsString('door_code', (string) $row->after);

    // ⚠️ THE WHOLE POINT: an encrypted column is undone the moment its value is copied somewhere plaintext,
    // and NOTHING sweeps audit_events. Assert across the entire table, not just this row.
    foreach (DB::table('audit_events')->get() as $event) {
        Assert::assertStringNotContainsString(AI_CODE, json_encode($event),
            '⚠️ the door code was copied into the audit log — the encrypted column is undone');
    }
});

it('⚠️ never renders a stored door code back into the units page', function () {
    ['unit' => $unit, 'admin' => $admin] = arrivalScenario();

    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info", ['door_code' => AI_CODE])->assertRedirect();

    $this->actingAs($admin)->get('/app/units')->assertOk()
        ->assertDontSee(AI_CODE)                 // ⚠️ not in the page, the cache, or a screenshot of it
        ->assertSee('code set');                 // only the FACT that one exists
});

it('reveals the stored values only on an explicit, audited action', function () {
    ['unit' => $unit, 'admin' => $admin] = arrivalScenario();

    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info", ['door_code' => AI_CODE])->assertRedirect();
    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info/reveal")->assertRedirect();

    $this->actingAs($admin)->get('/app/units')->assertOk()->assertSee(AI_CODE);

    Assert::assertSame(1, DB::table('audit_events')->where('action', 'unit.arrival_secrets_revealed')->count(),
        '⚠️ a credential was read with no record of who read it');
});

// ══ BLANK ≠ ERASE ════════════════════════════════════════════════════════════════════════════════════
it('⚠️ leaves a stored code alone when the field is submitted blank', function () {
    ['unit' => $unit, 'admin' => $admin] = arrivalScenario();

    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info", ['door_code' => AI_CODE])->assertRedirect();
    // Editing the wifi name beside it must not silently wipe a working door code.
    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info", ['door_code' => '', 'wifi_ssid' => 'New-SSID'])->assertRedirect();

    Assert::assertSame(AI_CODE, (string) $unit->fresh()->door_code);
    Assert::assertSame('New-SSID', (string) $unit->fresh()->wifi_ssid);
});

it('clears a value only when clearing is explicit', function () {
    ['unit' => $unit, 'admin' => $admin] = arrivalScenario();

    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info", ['door_code' => AI_CODE])->assertRedirect();
    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info", ['door_code' => ArrivalInfoService::CLEAR])->assertRedirect();

    Assert::assertNull($unit->fresh()->door_code);
});

// ══ ⚠️ #33 STILL HOLDS — the window, both directions ═════════════════════════════════════════════════
it('⚠️ withholds a newly written door code from a guest outside the arrival window', function () {
    ['s' => $s, 'unit' => $unit, 'admin' => $admin] = arrivalScenario();

    $this->actingAs($admin)->post("/app/units/{$unit->id}/arrival-info", ['door_code' => AI_CODE])->assertRedirect();

    $in = CarbonImmutable::parse('2026-09-05');
    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in->toDateString(),
            'check_out' => $in->addDays(2)->toDateString(), 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $unit->id, 'rate_plan_id' => $s['ratePlan']->id,
            'check_in' => $in->toDateString(), 'check_out' => $in->addDays(2)->toDateString(),
            'price_minor' => 10000, 'currency' => 'EUR']],
    );

    // FAR OUTSIDE the window — LEAD_DAYS before check-in is 2026-09-03.
    $far = ArrivalPack::for($reservation, CarbonImmutable::parse('2026-07-01'));
    Assert::assertFalse($far['released'], 'the pack claims released far outside the window');
    Assert::assertNull($far['door_code'], '⚠️ WRITING A DOOR CODE LEAKED IT OUTSIDE THE ARRIVAL WINDOW (#33)');

    // INSIDE the window.
    $near = ArrivalPack::for($reservation, $in->subDay());
    Assert::assertTrue($near['released']);
    Assert::assertSame(AI_CODE, $near['door_code'], 'the code was withheld INSIDE the window');
});

// ══ ⚠️ TENANCY + THE PAYOFF FROM CLUSTER J ═══════════════════════════════════════════════════════════
it('⚠️ refuses to write another organisation’s unit', function () {
    ['unit' => $mine, 'admin' => $admin, 'org' => $org] = arrivalScenario();

    $other = arrivalScenario();
    $victim = $other['unit'];
    app(TenantContext::class)->set((string) $org->id);

    $this->actingAs($admin)->post("/app/units/{$victim->id}/arrival-info", ['door_code' => AI_CODE])
        ->assertNotFound();

    // ⚠️ MEASURED ON THE VICTIM'S ROW, raw — a cross-org write would land THERE, and a count of the
    // attacker's own units would see nothing and pass.
    Assert::assertNull(DB::table('units')->where('id', $victim->id)->value('door_code'),
        '⚠️ another organisation’s door code was written');
});

it('⚠️ refuses a unit outside the actor’s per-property scope (cluster J’s payoff)', function () {
    ['s' => $s, 'admin' => $admin, 'org' => $org] = arrivalScenario();

    // A second property in the SAME org, with its own unit.
    $other = Property::create(['name' => 'Bravo House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $otherRt = RoomType::create(['property_id' => $other->id, 'name' => 'Std', 'kind' => 'room']);
    $otherUnit = Unit::create(['property_id' => $other->id, 'room_type_id' => $otherRt->id, 'code' => 'B1']);

    // The actor may work only in the FIRST property — a state that was unreachable before cluster J.
    app(PropertyContext::class)->activate([(string) $s['property']->id], (string) $s['property']->id);

    $refused = false;
    try {
        app(InventoryActionController::class);
        $this->actingAs($admin)->withoutMiddleware(SetCurrentPropertyForWebRequest::class)
            ->post("/app/units/{$otherUnit->id}/arrival-info", ['door_code' => AI_CODE]);
    } catch (Throwable $e) {
        $refused = true;
    }

    Assert::assertNull(DB::table('units')->where('id', $otherUnit->id)->value('door_code'),
        '⚠️ a property-scoped operator wrote a door code in a property they may not access');
});

it('refuses an operator without units.manage', function () {
    ['unit' => $unit, 'org' => $org] = arrivalScenario();
    $desk = operatorUser(['org' => $org], [], 'Front Desk');

    $this->actingAs($desk)->post("/app/units/{$unit->id}/arrival-info", ['door_code' => AI_CODE])
        ->assertForbidden();
});
