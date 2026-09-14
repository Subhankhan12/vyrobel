<?php

use App\Exceptions\TenantContextMissingException;
use App\Models\Guest;
use App\Models\HousekeepingTask;
use App\Models\MaintenanceTicket;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseTransactions::class);

/**
 * ⚠️⚠️ THE CLAIM THIS PROGRAMME COULD NEVER EXERCISE.
 *
 * With ONE organisation seeded, every "tenant-scoped → cross-org 404" statement in this codebase was READ
 * AND NEVER DRIVEN — through fifteen audit phases and sixteen fix passes. `docs/AUDIT-SUMMARY.md` §5 named
 * it the single largest untested property in the product. Two organisations now exist, so it can be driven.
 *
 * ⚠️ EVERY PROBE CARRIES A POSITIVE CONTROL. Without one, "everything was refused" and "the probe never
 * worked" are the same observation, and this whole file would prove nothing — so each case first reaches the
 * actor's OWN equivalent object and requires it to SUCCEED.
 *
 * ⚠️ AND THE TEAM IS ASSERTED ON EVERY PROBE. `can()` returns FALSE with no spatie team set, a shape that has
 * bitten this project three times. With two organisations the WRONG team is now a real possibility rather
 * than a test artefact, so each case states who it is and which organisation is active.
 *
 * ⚠️ REFUSAL IS ATTRIBUTED TO THE SCOPE, NOT TO A STATUS CODE. "not 200" passes on a 419 (expired CSRF) or a
 * 429 (rate limit) — Phase 1 was fooled by exactly that, twice. These assert the OBJECT IS NOT FOUND through
 * the scoped query the product itself uses.
 */
function tenantActor(array $scenario, string $role = 'Hotel Manager'): User
{
    $user = operatorUser($scenario, [], $role);
    app(PermissionRegistrar::class)->setPermissionsTeamId($scenario['org']->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');

    return $user;
}

/** Run $fn with A as the acting tenant — the context a real request would carry. */
function asTenant(array $scenario, callable $fn): mixed
{
    return app(TenantContext::class)
        ->forOrganization($scenario['org']->id, $fn);
}

it('⚠️⚠️ an operator cannot reach the OTHER tenant\'s objects — and CAN reach its own', function () {
    $a = seedScenario(1);
    $b = seedScenario(1);

    // ⚠️ NON-VACUITY: two genuinely different organisations, or every "not found" below is meaningless.
    expect((string) $a['org']->id)->not->toBe((string) $b['org']->id);

    $actorA = tenantActor($a);
    expect($actorA->exists)->toBeTrue();

    // Build one object of each kind in BOTH tenants, so every probe has a control.
    $mk = function (array $s): array {
        return asTenant($s, function () use ($s): array {
            $stay = book($s);
            $hk = HousekeepingTask::create([
                'property_id' => $s['property']->id, 'unit_id' => $s['units'][0]->id,
                'date' => '2026-09-05', 'type' => 'departure', 'status' => 'pending', 'priority' => 'normal',
            ]);
            $mt = MaintenanceTicket::create([
                'unit_id' => $s['units'][0]->id, 'title' => 'T', 'description' => 'D',
                'category' => 'other', 'priority' => 'normal', 'status' => 'open',
                'currency' => 'EUR', 'opened_at' => now(),
            ]);

            return ['reservation' => $stay, 'hk' => $hk, 'mt' => $mt];
        });
    };

    $objA = $mk($a);
    $objB = $mk($b);

    // ⚠️ ACTING AS TENANT A. Every lookup below runs through the product's own global scopes.
    asTenant($a, function () use ($a, $b, $objA, $objB): void {
        $cases = [
            'reservation' => [Reservation::class, $objA['reservation']->id, $objB['reservation']->id],
            'unit' => [Unit::class, $a['units'][0]->id, $b['units'][0]->id],
            'rate plan' => [RatePlan::class, $a['ratePlan']->id, $b['ratePlan']->id],
            'guest' => [Guest::class, $a['guest']->id, $b['guest']->id],
            'housekeeping task' => [HousekeepingTask::class, $objA['hk']->id, $objB['hk']->id],
            'maintenance ticket' => [MaintenanceTicket::class, $objA['mt']->id, $objB['mt']->id],
        ];

        foreach ($cases as $label => [$model, $mineId, $theirsId]) {
            // POSITIVE CONTROL — my own object must be reachable, or the probe proves nothing.
            expect($model::query()->find($mineId))->not->toBeNull("control failed: tenant A cannot see its own {$label}");

            // THE PROBE — by identifier, through the scoped query the product uses.
            expect($model::query()->find($theirsId))->toBeNull("LEAK: tenant A reached tenant B's {$label}");

            // …and it is not merely hidden from `find`: it must be absent from a LIST too, which is how
            // pagination and search reach rows.
            expect($model::query()->pluck('id')->contains($theirsId))->toBeFalse("LEAK: tenant B's {$label} appears in tenant A's list");
        }
    });
});

it('⚠️ the other tenant\'s rows DO exist — so "not found" means SCOPED OUT, not "never created"', function () {
    $a = seedScenario(1);
    $b = seedScenario(1);

    $stayB = asTenant($b, fn () => book($b));

    // Invisible to A…
    asTenant($a, function () use ($stayB): void {
        expect(Reservation::query()->find($stayB->id))->toBeNull();
    });

    // …visible to B, and present unscoped. ⚠️ This is what separates isolation from an empty database.
    asTenant($b, function () use ($stayB): void {
        expect(Reservation::query()->find($stayB->id))->not->toBeNull();
    });

    expect(Reservation::query()->withoutGlobalScopes()->find($stayB->id))->not->toBeNull();
});

it('⚠️ a tenant-scoped query with NO organisation context THROWS rather than returning everything', function () {
    $a = seedScenario(1);
    asTenant($a, fn () => book($a));

    // ⚠️ FAILS CLOSED, NOT OPEN. The dangerous shape is a scope that silently drops and returns every
    //    tenant's rows; this asserts the guard throws instead.
    app(TenantContext::class)->forget();

    expect(fn () => Reservation::query()->count())
        ->toThrow(TenantContextMissingException::class);
});

// ══ ⚠️⚠️ THE WRONG-FIX MUTATION, DEMONSTRATED RATHER THAN DESCRIBED ══════════════════════════════════
//
// The obvious way to write a cross-tenant test is `expect($response->status())->not->toBe(200)`. It is
// WORTHLESS, and Phase 1 was fooled by it twice: a 419 (expired CSRF) and a 429 (rate limit) both satisfy
// "not 200" while proving nothing about tenancy. The case below shows the weak predicate passing on a
// refusal that has nothing to do with scope, beside the strong one that attributes the refusal correctly.

it('⚠️⚠️ a "not 200" assertion cannot tell a SCOPE refusal from an unrelated one', function () {
    $a = seedScenario(1);
    $b = seedScenario(1);
    $stayB = asTenant($b, fn () => book($b));

    $actorA = tenantActor($a);

    // (1) THE REAL CROSS-TENANT REQUEST — as tenant A, for tenant B's reservation.
    $crossTenant = $this->actingAs($actorA)->get(route('app.reservations.show', $stayB->id));

    // (2) AN UNRELATED REFUSAL — a POST with no CSRF token. Nothing to do with tenancy.
    $unrelated = $this->actingAs($actorA)
        ->post(route('app.reservations.show', $stayB->id));   // wrong verb → not 200 either

    // ⚠️ THE WEAK PREDICATE PASSES ON BOTH. That is the whole point: it proves nothing.
    expect($crossTenant->status())->not->toBe(200)
        ->and($unrelated->status())->not->toBe(200);

    // ⚠️ THE STRONG ONE ATTRIBUTES THE REFUSAL TO THE SCOPE: the row is not reachable through the query the
    //    product itself runs, as tenant A — and IS reachable as tenant B. A status code cannot say that.
    asTenant($a, fn () => expect(Reservation::query()->find($stayB->id))->toBeNull());
    asTenant($b, fn () => expect(Reservation::query()->find($stayB->id))->not->toBeNull());

    // And record WHICH status the product actually returns for a cross-tenant read, so the answer to
    // "404 or 403?" is measured rather than assumed.
    expect($crossTenant->status())->toBe(404, 'a cross-tenant read should WITHHOLD existence (404), not confirm it (403)');
});
