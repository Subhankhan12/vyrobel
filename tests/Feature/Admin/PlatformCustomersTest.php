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
    makeOrg('Zseaside Group', 'zseaside', 'active');
    makeOrg('Zmountain Stays', 'zmountain', 'trialing');

    // Search-isolate so the assertion is robust to pagination + any pre-existing committed orgs.
    $this->actingAs(platformAdmin(), 'admin')
        ->get(route('admin.customers.index', ['search' => 'Z']))
        ->assertOk()
        ->assertSee('Zseaside Group')
        ->assertSee('Zmountain Stays')
        ->assertSee('zseaside');

    expect(PlatformAdminAction::query()->where('action', 'platform.customers.list')->exists())->toBeTrue();
})->group('security');

it('the customer list is status-filterable (real filter, honest empty state)', function () {
    makeOrg('Zactive Co', 'zactive-co', 'active');
    makeOrg('Zsuspended Co', 'zsuspended-co', 'suspended');

    $this->actingAs(platformAdmin(), 'admin')
        ->get(route('admin.customers.index', ['search' => 'Zsuspended', 'status' => 'suspended']))
        ->assertOk()
        ->assertSee('Zsuspended Co')
        ->assertDontSee('Zactive Co');
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
    // A UNIQUE prefix so a SEARCH isolates exactly these 30 (robust to any pre-existing committed orgs).
    for ($i = 1; $i <= 30; $i++) {
        makeOrg('Zpaged Co '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'zpaged-'.$i, 'active');
    }
    $admin = platformAdmin();

    // Page 1 of the search → deterministic "of 30" + a pager.
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['search' => 'Zpaged']))
        ->assertOk()->assertSee('of 30')->assertSee('Next');

    // Page 2 of the SAME search carries the filter into the link + renders the remainder.
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['search' => 'Zpaged', 'page' => 2]))
        ->assertOk()->assertSee('Previous')->assertSee('Zpaged Co 30');

    // The status filter is applied under pagination — a suspended search finds only the suspended org.
    makeOrg('Zsolo Suspended', 'zsolo-susp', 'suspended');
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['search' => 'Zsolo', 'status' => 'suspended']))
        ->assertOk()->assertSee('Zsolo Suspended');
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['search' => 'Zpaged', 'status' => 'suspended']))
        ->assertOk()->assertDontSee('Zpaged Co');   // the active orgs are excluded by the status filter
})->group('security');
