<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\PlatformAdminAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

// SUPER ADMIN · BUILD 1 · GATE 2 — Customers: real list + detail via the audited read; audited lifecycle actions.

function makeOrg(string $name, string $slug, string $status = 'active'): Organization
{
    return Organization::create(['name' => $name, 'slug' => $slug, 'status' => $status, 'default_currency' => 'EUR']);
}

it('the customer list shows REAL orgs and audits the read', function () {
    makeOrg('Seaside Group', 'seaside', 'active');
    makeOrg('Mountain Stays', 'mountain', 'trialing');

    $this->actingAs(platformAdmin(), 'admin')
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->assertSee('Seaside Group')
        ->assertSee('Mountain Stays')
        ->assertSee('seaside');

    expect(PlatformAdminAction::query()->where('action', 'platform.customers.list')->exists())->toBeTrue();
})->group('security');

it('the customer list is status-filterable (real filter, honest empty state)', function () {
    makeOrg('Active Co', 'active-co', 'active');
    makeOrg('Suspended Co', 'suspended-co', 'suspended');

    $this->actingAs(platformAdmin(), 'admin')
        ->get(route('admin.customers.index', ['status' => 'suspended']))
        ->assertOk()
        ->assertSee('Suspended Co')
        ->assertDontSee('Active Co');
})->group('security');

it('the customer detail shows real facts and audits the view', function () {
    $org = makeOrg('Drill Co', 'drill', 'active');

    $this->actingAs(platformAdmin(), 'admin')
        ->get(route('admin.customers.show', $org->id))
        ->assertOk()
        ->assertSee('Drill Co')
        ->assertSee('Footprint');       // operational counts panel

    expect(PlatformAdminAction::query()->where('action', 'platform.customers.view')->exists())->toBeTrue();
})->group('security');

it('suspend + reactivate are AUDITED admin actions that really change the status', function () {
    $org = makeOrg('Lifecycle Co', 'lifecycle', 'active');
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.customers.suspend', $org->id))
        ->assertRedirect(route('admin.customers.show', $org->id));

    expect($org->fresh()->status)->toBe('suspended');
    expect(PlatformAdminAction::query()->where('action', 'platform.customer.suspend')->where('target_id', $org->id)->exists())->toBeTrue();

    $this->actingAs($admin, 'admin')->post(route('admin.customers.reactivate', $org->id))->assertRedirect();
    expect($org->fresh()->status)->toBe('active');
    expect(PlatformAdminAction::query()->where('action', 'platform.customer.reactivate')->exists())->toBeTrue();
})->group('security');

it('a TENANT USER cannot reach any Customers route (the whole realm is admin-only)', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s);
    $org = makeOrg('Hidden Co', 'hidden', 'active');

    $this->actingAs($operator)->get(route('admin.customers.index'))->assertRedirect(route('admin.login'));
    $this->actingAs($operator)->get(route('admin.customers.show', $org->id))->assertRedirect(route('admin.login'));
    // The status action is refused too (not authenticated on the admin guard).
    $this->actingAs($operator)->post(route('admin.customers.suspend', $org->id))->assertRedirect(route('admin.login'));
    expect($org->fresh()->status)->toBe('active');   // unchanged — the tenant user could not act
})->group('security');

it('no impersonation / view-as-tenant route exists (deferred, high-risk)', function () {
    expect(Route::has('admin.customers.impersonate'))->toBeFalse();
    expect(Route::has('admin.impersonate'))->toBeFalse();
})->group('security');

// ── audit fix P2-1 — the customer list is PAGINATED (scales past a handful of orgs) ─────────────────
it('the customer list is paginated and carries the search/status filters into the pages', function () {
    for ($i = 1; $i <= 30; $i++) {
        makeOrg('Paged Co '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'paged-'.$i, 'active');
    }
    $admin = platformAdmin();

    // Page 1 shows the pager (30 orgs > 25/page) + a "Showing 1–25 of 30".
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index'))
        ->assertOk()
        ->assertSee('of 30')
        ->assertSee('Next');

    // Page 2 renders the remainder.
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Previous');

    // A filter narrows the total (so no pager), proving the filter is applied under pagination.
    makeOrg('Solo Suspended', 'solo-susp', 'suspended');
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['status' => 'suspended']))
        ->assertOk()
        ->assertSee('Solo Suspended')
        ->assertDontSee('Paged Co');
})->group('security');
