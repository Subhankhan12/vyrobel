<?php

use App\Models\Organization;
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

it('renders the invoice section with the honest SAF-T deferral + the real plan card', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');

    $this->actingAs($admin)->withSession(['current_property_id' => $s['property']->id])->get(route('app.settings'))
        ->assertOk()
        ->assertSee('Invoice details')
        ->assertSee('SAF-T')
        ->assertSee('Your plan')
        ->assertSee('Vyrobel Pro');
});
