<?php

use App\Exceptions\TenantContextMissingException;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Obligation 6 — tenant isolation across the model set; guard throws with no context;
// super-admin bypass works.

it('never leaks another org\'s rows across the model set', function () {
    $sA = seedScenario(1);
    $reservationA = book($sA, ['unit_id' => $sA['units'][0]->id]);
    $guestA = $sA['guest'];
    $propertyA = $sA['property'];
    $unitA = $sA['units'][0];

    // seedScenario switches context to a fresh org B.
    seedScenario(1);

    expect(Property::count())->toBe(1)                 // only B's property
        ->and(Property::find($propertyA->id))->toBeNull()
        ->and(Guest::find($guestA->id))->toBeNull()
        ->and(Unit::find($unitA->id))->toBeNull()
        ->and(Reservation::find($reservationA->id))->toBeNull()
        ->and(ReservationUnit::query()->count())->toBe(0); // B has no reservations yet

    app(TenantContext::class)->set($sA['org']->id);
    expect(Reservation::count())->toBe(1)
        ->and(Reservation::find($reservationA->id))->not->toBeNull();
});

it('throws when any tenant model is queried with no context', function () {
    app(TenantContext::class)->forget();

    expect(fn () => Reservation::query()->count())->toThrow(TenantContextMissingException::class);
    expect(fn () => Guest::query()->count())->toThrow(TenantContextMissingException::class);
    expect(fn () => Property::query()->count())->toThrow(TenantContextMissingException::class);
});

it('lets an explicit super-admin context read across all tenants', function () {
    $sA = seedScenario(1);
    book($sA, ['unit_id' => $sA['units'][0]->id]);
    seedScenario(1); // org B (context now B)

    $tenant = app(TenantContext::class);
    $tenant->forget();

    $total = $tenant->runAsSuperAdmin(fn () => Property::query()->count());
    expect($total)->toBe(2); // both orgs' properties visible under super-admin
});
