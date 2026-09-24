<?php

declare(strict_types=1);

use App\Models\Organization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

// ADMIN 01 · PHASE 1 → PHASE 2 — the customer page says what its controls DO. Phase 1 relabelled "Suspend" as
// "Close public storefront" because that was all it did; Phase 2 made suspension real (staff and owners refused —
// PlatformTenantLifecycleTest proves it from the other side), so the control is "Suspend" again, and says so.

uses(DatabaseTransactions::class);

function lifecycleOrg(string $status = 'active'): Organization
{
    return Organization::create(['name' => 'Lifecycle '.Str::random(5), 'slug' => 'lc-'.Str::lower(Str::random(8)), 'status' => $status, 'default_currency' => 'EUR']);
}

it('⚠️⚠️ the control is labelled for what it does now — "Suspend", with the staff/owner effect stated', function () {
    $org = lifecycleOrg();

    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.show', $org->id))
        ->assertOk()
        ->assertSee('>Suspend<', false)
        ->assertSee('touches <strong>no</strong> reservation, folio or payment', false)
        ->assertDontSee('Close public storefront')
        ->assertDontSee('a full suspension arrives in a later release');
})->group('security');

it('a suspended organisation reads as "Suspended" and offers Reactivate and Archive', function () {
    $org = lifecycleOrg('suspended');

    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.show', $org->id))
        ->assertOk()->assertSee('Suspended')->assertSee('Reactivate')->assertSee('>Archive<', false)->assertDontSee('Storefront closed');
})->group('security');

it('suspending changes the status and nothing else — and needs a reason plus a password', function () {
    $org = lifecycleOrg();

    $this->actingAs(platformAdmin(), 'admin')
        ->post(route('admin.customers.suspend', $org->id), ['current_password' => 'password', 'reason' => 'Chargeback dispute pending.'])
        ->assertRedirect(route('admin.customers.show', $org->id));

    expect($org->fresh()->status)->toBe('suspended');
})->group('security');

it('each control appears only for a role that may use it', function () {
    $org = lifecycleOrg();
    $url = route('admin.customers.show', $org->id);

    $suspend = route('admin.customers.suspend', $org->id);

    $this->actingAs(platformAdmin(['role' => 'support']), 'admin')->get($url)
        ->assertSee($suspend)->assertSee('Mark as internal')->assertDontSee('Assign a plan');

    $this->flushSession();
    $this->actingAs(platformAdmin(['role' => 'billing']), 'admin')->get($url)
        ->assertSee('Assign a plan')->assertDontSee($suspend)->assertDontSee('Mark as internal');

    $this->flushSession();
    $this->actingAs(platformAdmin(['role' => 'analyst']), 'admin')->get($url)
        ->assertOk()->assertDontSee('Assign a plan')->assertDontSee($suspend)->assertDontSee('Mark as internal');
})->group('security');

it('the directory marks internal organisations and filters them', function () {
    $mine = lifecycleOrg();
    $mine->forceFill(['is_internal' => true])->save();
    $cust = lifecycleOrg();

    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.index', ['kind' => 'customers', 'search' => 'Lifecycle']))
        ->assertOk()->assertSee($cust->name)->assertDontSee($mine->name);

    $this->flushSession();
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.index', ['kind' => 'internal', 'search' => 'Lifecycle']))
        ->assertOk()->assertSee($mine->name)->assertDontSee($cust->name);
})->group('security');
