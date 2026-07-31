<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104 GATE 0 — the operator OPERATIONS write routes that shipped with NO `can:` middleware (their
// controllers carry no authorize()) are now capability-gated. Each gate is mutation-proven the honest way:
// a back-office role WITHOUT the permission is 403'd by the middleware BEFORE any model resolution, while a
// role WITH the permission passes the gate and only then hits the tenant-scoped findOrFail → 404 on a bogus
// id. 403-vs-404 on the SAME request isolates the gate itself (not the record, not the tenant scope).
//
// This closes a privilege-within-org (#20/#30) hole — most seriously `maintenance.resolve`, which posts a
// REAL deductible owner expense and was reachable by Front Desk / Reservation Agent / Accountant.

$FAKE = '00000000000000000000000000'; // a well-formed but non-existent ULID

/**
 * @param  list<array{0:string,1:string,2:string,3:string,4:string}>  ...
 * route name · route params · permission · a role that LACKS it · a role that HOLDS it
 */
dataset('gated ops writes', [
    'maintenance.resolve' => ['app.maintenance.resolve', 'maintenance.manage', 'Front Desk', 'Maintenance'],
    'maintenance.start' => ['app.maintenance.start', 'maintenance.manage', 'Reservation Agent', 'Hotel Manager'],
    'maintenance.cancel' => ['app.maintenance.cancel', 'maintenance.manage', 'Accountant', 'Maintenance'],
    'maintenance.assign' => ['app.maintenance.assign', 'maintenance.manage', 'Front Desk', 'Hotel Manager'],
    'maintenance.parts' => ['app.maintenance.parts', 'maintenance.manage', 'Front Desk', 'Maintenance'],
    'housekeeping.status' => ['app.housekeeping.status', 'housekeeping.manage', 'Front Desk', 'Housekeeping'],
    'reservations.move' => ['app.reservations.move', 'reservations.assign', 'Reservation Agent', 'Front Desk'],
    'front-desk.check-in' => ['app.front-desk.check-in', 'reservations.checkin', 'Reservation Agent', 'Front Desk'],
    'front-desk.check-out' => ['app.front-desk.check-out', 'reservations.checkout', 'Accountant', 'Front Desk'],
]);

it('403s a role that lacks the permission and 404s a role that holds it', function (string $routeName, string $permission, string $withoutRole, string $withRole) use ($FAKE) {
    $s = seedScenario(1);

    // The {task}/{ticket}/{reservation} param name varies; every one of these routes takes a single id param.
    $param = match ($routeName) {
        'app.housekeeping.status' => ['task' => $FAKE],
        'app.reservations.move', 'app.front-desk.check-in', 'app.front-desk.check-out' => ['reservation' => $FAKE],
        default => ['ticket' => $FAKE],
    };
    $url = route($routeName, $param);

    // WITHOUT the permission → the `can:` middleware refuses BEFORE the controller runs (no record needed).
    $lacking = operatorUser($s, [], $withoutRole);
    expect($lacking->can($permission))->toBeFalse();
    $this->actingAs($lacking)->withSession(['current_property_id' => $s['property']->id])
        ->post($url)->assertForbidden();

    // WITH the permission → passes the gate; the downstream response is NOT a 403 (it is a 404 on the bogus
    // id, or a 422 where the controller validates the body first) — either way the gate let the actor through.
    $holding = operatorUser($s, [], $withRole);
    expect($holding->can($permission))->toBeTrue();
    $response = $this->actingAs($holding)->withSession(['current_property_id' => $s['property']->id])->post($url);
    expect($response->status())->not->toBe(403);
})->with('gated ops writes');
