<?php

use App\Models\Membership;
use App\Models\Organization;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// Step 9 — the unstyled contract-stub pages render REAL data through the services, and the
// request-scoped SetTenantForWebRequest middleware resolves the active org (isolation intact).

it('renders demo data through the stub pages for an authenticated staff member', function () {
    $this->seed(DemoSeeder::class);

    // These are OPERATOR pages, so the actor must be an operator: demo-staff holds the real back-office
    // role `Hotel Manager`. This used to act as demo-owner and passed only because the seeder wrongly
    // granted that unit owner `Property Owner` (all 44 permissions) — the inverted demo data that the
    // /app back-office gate exposed. An owner is now correctly refused here (docs/DECISIONS.md 2026-07-16).
    $staff = User::query()->where('email', 'demo-staff@vyrobel-demo.test')->firstOrFail();
    $org = Organization::query()->where('slug', 'vyrobel-demo')->firstOrFail();

    // A 200 here already proves the middleware set a tenant context (otherwise the org-scoped
    // query would trip the guard and 500). The demo unit code '101' is unique to the demo data.
    $this->actingAs($staff)->get('/app/units')->assertOk()->assertSee('101');
    $this->actingAs($staff)->get('/app/rack')->assertOk()->assertSee('Double Room');
    // The housekeeping board is styled (design pass · Operator Screen 3): the real departure task the
    // demo checkout generates now renders with the humanized type label rather than the raw enum.
    $this->actingAs($staff)->get('/app/housekeeping')->assertOk()->assertSee('Departure clean');

    app(TenantContext::class)->set($org->id);
    $reservation = Reservation::query()->firstOrFail();
    app(TenantContext::class)->forget();

    $this->actingAs($staff)->get("/app/reservations/{$reservation->id}")
        ->assertOk()
        ->assertSee($reservation->reference);
});

// The demo data must MODEL PRODUCTION, or the security gate is not what the demo journeys prove. This
// locks the correction: demo-owner is a unit owner (owner-only role, refused the back office), demo-staff
// is a real operator. Before the fix, demo-owner held `Property Owner` — all 44 permissions, including
// payments.refund — and could walk the whole operator app.
it('seeds a demo owner who is NOT operator-capable and a demo staff member who is', function () {
    $this->seed(DemoSeeder::class);

    $owner = User::query()->where('email', 'demo-owner@vyrobel-demo.test')->firstOrFail();
    $staff = User::query()->where('email', 'demo-staff@vyrobel-demo.test')->firstOrFail();
    $org = Organization::query()->where('slug', 'vyrobel-demo')->firstOrFail();

    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

    expect($owner->getRoleNames()->all())->toBe(['External Owner'])
        ->and($owner->hasAnyRole(config('authorization.back_office_roles')))->toBeFalse()
        ->and($owner->can('owner_portal.view'))->toBeTrue()
        ->and($owner->can('payments.refund'))->toBeFalse()   // ← what the old seeder wrongly granted
        ->and($staff->getRoleNames()->all())->toBe(['Hotel Manager'])
        ->and($staff->hasAnyRole(config('authorization.back_office_roles')))->toBeTrue();

    // …and it holds over HTTP: the owner is sent home, the operator gets in.
    $this->actingAs($owner)->get('/app/dashboard')->assertRedirect(route('owner.dashboard'));
    $this->actingAs($owner)->get('/owner')->assertOk();
    $this->actingAs($staff)->get('/app/dashboard')->assertOk();
})->group('security');

it('isolates tenants at the web layer through the request middleware', function () {
    $sA = seedScenario(1);
    $orgA = $sA['org'];
    $reservationA = book($sA, ['unit_id' => $sA['units'][0]->id]);
    $userA = User::factory()->create();
    Membership::create(['organization_id' => $orgA->id, 'user_id' => $userA->id, 'status' => 'active']);
    // A Membership alone is NOT operator access — /app is an allow-list gate (EnsureBackOfficeAccess).
    // Each user holds a back-office role in their OWN org only: spatie's team filter means A's role
    // grants nothing in B, so the cross-org 404 below still proves tenant isolation, not a missing role.
    $userA = grantBackOfficeRole($userA, $orgA);

    $sB = seedScenario(1);
    $orgB = $sB['org'];
    $userB = User::factory()->create();
    Membership::create(['organization_id' => $orgB->id, 'user_id' => $userB->id, 'status' => 'active']);
    $userB = grantBackOfficeRole($userB, $orgB);

    // Both members can load their own units page (200 proves the middleware resolved a context).
    $this->actingAs($userA)->get('/app/units')->assertOk();
    $this->actingAs($userB)->get('/app/units')->assertOk();

    // Org A's reservation is visible to A but NOT to B (tenant-scoped route binding → 404).
    $this->actingAs($userA)->get("/app/reservations/{$reservationA->id}")
        ->assertOk()->assertSee($reservationA->reference);
    $this->actingAs($userB)->get("/app/reservations/{$reservationA->id}")
        ->assertNotFound();
});
