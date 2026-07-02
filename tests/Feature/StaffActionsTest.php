<?php

use App\Exceptions\Booking\UnitUnavailableException;
use App\Models\HousekeepingTask;
use App\Models\Membership;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\User;
use App\Services\FolioService;
use App\Services\FrontDeskService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Phase 2 Step 8 — staff action layer. This is an ASSEMBLY step: FrontDeskService is a thin
// orchestration over existing services (CheckIn/CheckOut actions, AssignmentService, FolioService)
// — these tests prove the wiring, not re-implemented logic. Stripe is the in-memory fake.

it('checks a reservation in as a pure state transition, touching no inventory', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $soldBefore = soldOn($s['roomType']->id, '2026-09-01');

    $result = app(FrontDeskService::class)->checkIn($reservation);

    expect($result->status)->toBe('checked_in')
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe($soldBefore)  // inventory untouched
        ->and($reservation->refresh()->reservationUnits()->first()->status)->toBe('active');
});

it('checks out by reusing the existing action — the departure housekeeping task is still auto-generated', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    app(FrontDeskService::class)->checkIn($reservation);

    expect(HousekeepingTask::count())->toBe(0);

    app(FrontDeskService::class)->checkOut($reservation);

    // The Phase 1 checkout→listener still fires (proves reuse, not a reimplementation).
    expect($reservation->refresh()->status)->toBe('checked_out')
        ->and(HousekeepingTask::query()->where('type', 'departure')->count())->toBe(1);
});

it('moves a room THROUGH AssignmentService: re-checks the target unit and frees the old one', function () {
    $s = seedScenario(2); // two units
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $line = $reservation->reservationUnits()->first();

    $moved = app(FrontDeskService::class)->moveRoom($line, $s['units'][1]->id);

    expect($moved->unit_id)->toBe($s['units'][1]->id)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1); // still exactly one sold
});

it('rejects a room move onto an already-occupied unit (Level B holds via AssignmentService)', function () {
    $s = seedScenario(2);
    // Two reservations, one on each unit, same nights.
    $r1 = book($s, ['unit_id' => $s['units'][0]->id]);
    $r2 = book($s, ['unit_id' => $s['units'][1]->id]);
    $line2 = $r2->reservationUnits()->first();

    // Moving r2's line onto unit[0] (occupied by r1) must be rejected — no overbook.
    expect(fn () => app(FrontDeskService::class)->moveRoom($line2, $s['units'][0]->id))
        ->toThrow(UnitUnavailableException::class);

    expect(ReservationUnit::query()->where('unit_id', $s['units'][0]->id)->where('status', 'active')->count())->toBe(1);
});

it('collects a payment through FolioService — Manual gateway', function () {
    config(['services.payments.gateway' => 'manual']);
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $desk = app(FrontDeskService::class);
    $desk->checkIn($reservation);

    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', moneyEur(20000));

    $payment = $desk->collectPayment($reservation, moneyEur(20000), 'manual-token');

    expect($payment->gateway)->toBe('manual')
        ->and($payment->status)->toBe('succeeded')
        ->and($folio->refresh()->paid_minor)->toBe(20000)
        ->and($reservation->refresh()->payment_status)->toBe('paid');
});

it('collects a payment through FolioService — Stripe gateway', function () {
    config(['services.payments.gateway' => 'stripe']);
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $desk = app(FrontDeskService::class);

    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', moneyEur(20000));

    $payment = $desk->collectPayment($reservation, moneyEur(20000), 'pm_card_visa');

    expect($payment->gateway)->toBe('stripe')
        ->and($payment->status)->toBe('succeeded')
        ->and($payment->card_token)->toBe('pm_card_visa')  // token-only
        ->and($folio->refresh()->paid_minor)->toBe(20000);
});

it('reads today\'s arrivals, departures, and in-house correctly and tenant-scoped', function () {
    $s = seedScenario(2);
    // Arrival: confirmed, checks in 2026-09-01.
    $arriving = book($s, ['unit_id' => $s['units'][0]->id]); // 09-01 → 09-03
    // In-house: checked in, covering 09-02.
    $inHouse = book($s, ['unit_id' => $s['units'][1]->id]);
    $desk = app(FrontDeskService::class);
    $desk->checkIn($inHouse);

    $arrivals = $desk->arrivals($s['property']->id, '2026-09-01');
    $departures = $desk->departures($s['property']->id, '2026-09-03');
    $inHouseList = $desk->inHouse($s['property']->id, '2026-09-02');

    expect($arrivals->pluck('id'))->toContain($arriving->id)
        ->and($departures->pluck('id'))->toContain($arriving->id)   // checks out 09-03
        ->and($inHouseList->pluck('id'))->toContain($inHouse->id)
        ->and($inHouseList->pluck('id'))->not->toContain($arriving->id); // not checked in

    // Tenant-scoped: another org's front desk sees none of this property's reservations.
    seedScenario(1); // switches context to a fresh org
    expect($desk->arrivals($s['property']->id, '2026-09-01'))->toHaveCount(0);
});

it('protects staff surfaces with auth and isolates orgs at the web layer', function () {
    $sA = seedScenario(1);
    $orgA = $sA['org'];
    $reservationA = book($sA, ['unit_id' => $sA['units'][0]->id]);
    $userA = User::factory()->create();
    Membership::create(['organization_id' => $orgA->id, 'user_id' => $userA->id, 'status' => 'active']);

    $sB = seedScenario(1);
    $userB = User::factory()->create();
    Membership::create(['organization_id' => $sB['org']->id, 'user_id' => $userB->id, 'status' => 'active']);

    // Auth-protected: the route sits behind the `auth` middleware. Asserted structurally because
    // there is no login screen yet (that's the design/auth pass — consistent with Phase 1 Step 9,
    // which likewise verified staff surfaces via actingAs, not an unauthenticated redirect).
    $middleware = app('router')->getRoutes()->getByName('app.front-desk')->gatherMiddleware();
    expect($middleware)->toContain('auth');

    // Authenticated members can load their own front desk (200 proves middleware set context).
    $this->actingAs($userA)->get('/app/front-desk')->assertOk();
    $this->actingAs($userB)->get('/app/front-desk')->assertOk();

    // A staff user of org B cannot act on org A's reservation — it isn't visible in B's context.
    app(TenantContext::class)->set($sB['org']->id);
    expect(Reservation::query()->find($reservationA->id))->toBeNull();
});
