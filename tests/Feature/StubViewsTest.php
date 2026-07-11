<?php

use App\Models\Membership;
use App\Models\Organization;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Step 9 — the unstyled contract-stub pages render REAL data through the services, and the
// request-scoped SetTenantForWebRequest middleware resolves the active org (isolation intact).

it('renders demo data through the stub pages for an authenticated member', function () {
    $this->seed(DemoSeeder::class);

    $owner = User::query()->where('email', 'demo-owner@vyrobel-demo.test')->firstOrFail();
    $org = Organization::query()->where('slug', 'vyrobel-demo')->firstOrFail();

    // A 200 here already proves the middleware set a tenant context (otherwise the org-scoped
    // query would trip the guard and 500). The demo unit code '101' is unique to the demo data.
    $this->actingAs($owner)->get('/app/units')->assertOk()->assertSee('101');
    $this->actingAs($owner)->get('/app/rack')->assertOk()->assertSee('Double Room');
    // The housekeeping board is styled (design pass · Operator Screen 3): the real departure task the
    // demo checkout generates now renders with the humanized type label rather than the raw enum.
    $this->actingAs($owner)->get('/app/housekeeping')->assertOk()->assertSee('Departure clean');

    app(TenantContext::class)->set($org->id);
    $reservation = Reservation::query()->firstOrFail();
    app(TenantContext::class)->forget();

    $this->actingAs($owner)->get("/app/reservations/{$reservation->id}")
        ->assertOk()
        ->assertSee($reservation->reference);
});

it('isolates tenants at the web layer through the request middleware', function () {
    $sA = seedScenario(1);
    $orgA = $sA['org'];
    $reservationA = book($sA, ['unit_id' => $sA['units'][0]->id]);
    $userA = User::factory()->create();
    Membership::create(['organization_id' => $orgA->id, 'user_id' => $userA->id, 'status' => 'active']);

    $sB = seedScenario(1);
    $orgB = $sB['org'];
    $userB = User::factory()->create();
    Membership::create(['organization_id' => $orgB->id, 'user_id' => $userB->id, 'status' => 'active']);

    // Both members can load their own units page (200 proves the middleware resolved a context).
    $this->actingAs($userA)->get('/app/units')->assertOk();
    $this->actingAs($userB)->get('/app/units')->assertOk();

    // Org A's reservation is visible to A but NOT to B (tenant-scoped route binding → 404).
    $this->actingAs($userA)->get("/app/reservations/{$reservationA->id}")
        ->assertOk()->assertSee($reservationA->reference);
    $this->actingAs($userB)->get("/app/reservations/{$reservationA->id}")
        ->assertNotFound();
});
