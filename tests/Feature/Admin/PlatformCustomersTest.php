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
