<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104b GATE 2 — the Settings hub's Invoice details + numbering is REAL config on the ONE store (the org),
// settings.manage-gated + audited. The certified issuance + SAF-T (PT) export stay honestly deferred.

it('persists invoice details config on the org, gated + audited', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');   // settings.manage
    $session = ['current_property_id' => $s['property']->id];

    $this->actingAs($admin)->withSession($session)->post(route('app.settings.invoice'), [
        'billing_company' => 'Seaside Villas Lda',
        'billing_vat_id' => 'PT123456789',
        'billing_city' => 'Lisboa',
        'invoice_series_prefix' => 'FT2026/',
        'invoice_next_number' => '42',
        'invoice_auto_issue' => '1',
    ])->assertRedirect();

    $org = Organization::query()->find($s['org']->id);
    expect((string) $org->billing_company)->toBe('Seaside Villas Lda')
        ->and((string) $org->billing_vat_id)->toBe('PT123456789')
        ->and((string) $org->billing_city)->toBe('Lisboa')
        ->and((string) $org->invoice_series_prefix)->toBe('FT2026/')
        ->and((int) $org->invoice_next_number)->toBe(42)
        ->and((bool) $org->invoice_auto_issue)->toBeTrue();
    $this->assertDatabaseHas('audit_events', ['action' => 'organization.invoice_details_updated']);
});

it('refuses invoice config to a role without settings.manage', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');   // no settings.manage
    expect($manager->can('settings.manage'))->toBeFalse();

    $this->actingAs($manager)->withSession(['current_property_id' => $s['property']->id])
        ->post(route('app.settings.invoice'), ['billing_company' => 'X'])->assertForbidden();
});

it('renders the invoice section with the honest SAF-T deferral + the REAL plan name', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');

    // The plan card shows the org's ACTUAL subscription plan (not a hardcoded tier). On Pro → "Vyrobel Pro".
    app(BillingService::class)->ensurePlans();
    $pro = Plan::query()->where('code', 'pro_monthly')->firstOrFail();
    Subscription::create(['organization_id' => $s['org']->id, 'plan_id' => $pro->id, 'status' => 'active', 'current_period_end' => now()->addMonth()]);

    $this->actingAs($admin)->withSession(['current_property_id' => $s['property']->id])->get(route('app.settings'))
        ->assertOk()
        ->assertSee('Invoice details')
        ->assertSee('SAF-T')
        ->assertSee('Your plan')
        ->assertSee('Vyrobel Pro');   // the REAL plan name, derived from the subscription
});

it('shows the REAL plan name for an Essential org (no drift with /app/billing)', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');

    // An Essential org must NOT read "Vyrobel Pro" — the old hardcoded tier bug.
    app(BillingService::class)->ensurePlans();
    $essential = Plan::query()->where('code', 'essential_monthly')->firstOrFail();
    Subscription::create(['organization_id' => $s['org']->id, 'plan_id' => $essential->id, 'status' => 'active', 'current_period_end' => now()->addMonth()]);

    $this->actingAs($admin)->withSession(['current_property_id' => $s['property']->id])->get(route('app.settings'))
        ->assertOk()
        ->assertSee('Vyrobel Essential')
        ->assertDontSee('Vyrobel Pro');
});

it('coerces a cleared "Next number" to 1 instead of 500ing on the NOT-NULL column', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');

    // A cleared number field arrives as null (empty string → null) — must coerce to the floor, never 500.
    $this->actingAs($admin)->withSession(['current_property_id' => $s['property']->id])->post(route('app.settings.invoice'), [
        'billing_company' => 'Seaside Villas Lda',
        'invoice_series_prefix' => 'FT2026/',
        'invoice_next_number' => '',
        'invoice_auto_issue' => '0',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect((int) Organization::query()->find($s['org']->id)->invoice_next_number)->toBe(1);
});
