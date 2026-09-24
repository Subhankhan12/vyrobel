<?php

declare(strict_types=1);

use App\Models\Organization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

// ADMIN 01 · PHASE 1 — the customer page says what its controls DO. 🔬 `status = suspended` closes the public
// booking pages and does NOT stop the hotel's staff reaching /app — so the control is "Close public storefront",
// not "Suspend customer", until Phase 2 makes suspension real.

uses(DatabaseTransactions::class);

function lifecycleOrg(string $status = 'active'): Organization
{
    return Organization::create(['name' => 'Lifecycle '.Str::random(5), 'slug' => 'lc-'.Str::lower(Str::random(8)), 'status' => $status, 'default_currency' => 'EUR']);
}

it('⚠️⚠️ the control is labelled for what it does — "Close public storefront", never "Suspend customer"', function () {
    $org = lifecycleOrg();

    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.show', $org->id))
        ->assertOk()
        ->assertSee('Close public storefront')
        ->assertSee('does <strong>not</strong> sign the hotel', false)
        ->assertDontSee('Suspend customer');
})->group('security');

it('a closed storefront reads as "Storefront closed" and offers "Reopen public storefront"', function () {
    $org = lifecycleOrg('suspended');

    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.show', $org->id))
        ->assertOk()->assertSee('Storefront closed')->assertSee('Reopen public storefront');
})->group('security');

it('closing the storefront changes the status and nothing else — and needs a reason plus a password', function () {
    $org = lifecycleOrg();

    $this->actingAs(platformAdmin(), 'admin')
        ->post(route('admin.customers.suspend', $org->id), ['current_password' => 'password', 'reason' => 'Chargeback dispute pending.'])
        ->assertRedirect(route('admin.customers.show', $org->id));

    expect($org->fresh()->status)->toBe('suspended');
})->group('security');

it('each control appears only for a role that may use it', function () {
    $org = lifecycleOrg();
    $url = route('admin.customers.show', $org->id);

    $this->actingAs(platformAdmin(['role' => 'support']), 'admin')->get($url)
        ->assertSee('Close public storefront')->assertSee('Mark as internal')->assertDontSee('Assign a plan');

    $this->flushSession();
    $this->actingAs(platformAdmin(['role' => 'billing']), 'admin')->get($url)
        ->assertSee('Assign a plan')->assertDontSee('Close public storefront')->assertDontSee('Mark as internal');

    $this->flushSession();
    $this->actingAs(platformAdmin(['role' => 'analyst']), 'admin')->get($url)
        ->assertOk()->assertDontSee('Assign a plan')->assertDontSee('Close public storefront')->assertDontSee('Mark as internal');
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
