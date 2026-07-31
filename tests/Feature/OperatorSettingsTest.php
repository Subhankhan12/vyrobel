<?php

use App\Models\Fee;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyPolicy;
use App\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OPERATOR SETTINGS (Wave 4 · Sub-gate 3) — the FIRST code in app/ to write properties / property_policies /
// organizations config (until now only the seeder did). Proves: the backed writers PERSIST (not toast-only),
// per-section permission separation (properties.manage vs settings.manage — never Front Desk), and that the
// unbacked fields are honestly "Soon", not a fake save.

// ── 1. BACKED WRITERS PERSIST ─────────────────────────────────────────────────────────────────────
it('persists property details through the backed writer', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, ['name' => 'Priya Owner'], 'Property Owner');   // holds properties.manage

    $this->actingAs($owner)->post('/app/settings/property', [
        'name' => 'Seaside Retreat',
        'timezone' => 'Europe/Lisbon',
        'star_rating' => '4',
        'check_in_time' => '15:00',
        'check_out_time' => '11:00',
        'status' => 'active',
    ])->assertRedirect(route('app.settings'))->assertSessionHas('settings_notice');

    $property = $s['property']->fresh();
    expect((string) $property->name)->toBe('Seaside Retreat')
        ->and((string) $property->timezone)->toBe('Europe/Lisbon')
        ->and((int) $property->star_rating)->toBe(4)
        ->and(substr((string) $property->check_in_time, 0, 5))->toBe('15:00');
});

it('persists stay policies, creating the property policy row', function () {
    $s = seedScenario(1);

    expect(PropertyPolicy::query()->where('property_id', $s['property']->id)->exists())->toBeFalse();

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post('/app/settings/policies', [
        'pet' => 'on_request',
        'smoking' => 'not_allowed',
        'min_stay' => '2',
        'max_stay' => '28',
    ])->assertRedirect()->assertSessionHas('settings_notice');

    $policy = PropertyPolicy::query()->where('property_id', $s['property']->id)->firstOrFail();
    expect((string) $policy->pet)->toBe('on_request')
        ->and((string) $policy->smoking)->toBe('not_allowed')
        ->and((int) $policy->min_stay)->toBe(2)
        ->and((int) $policy->max_stay)->toBe(28);
});

it('persists organization settings', function () {
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post('/app/settings/organization', [
        'default_currency' => 'GBP',
        'default_locale' => 'en_GB',
        'billing_email' => 'billing@seaside.test',
    ])->assertRedirect()->assertSessionHas('settings_notice');

    $org = Organization::query()->findOrFail($s['org']->id);
    expect((string) $org->default_currency)->toBe('GBP')
        ->and((string) $org->default_locale)->toBe('en_GB')
        ->and((string) $org->billing_email)->toBe('billing@seaside.test');
});

// ── 2. PERMISSION (#30) — per-section separation, mutation-proven ─────────────────────────────────
it('gates property writes on properties.manage — Hotel Manager forbidden, Group Admin allowed with the SAME payload', function () {
    $s = seedScenario(1);
    $payload = ['name' => 'Renamed', 'timezone' => 'UTC', 'status' => 'active'];

    // Hotel Manager holds properties.VIEW but not properties.MANAGE.
    $this->actingAs(operatorUser($s, ['name' => 'Mo Manager'], 'Hotel Manager'))
        ->post('/app/settings/property', $payload)->assertForbidden();
    expect((string) $s['property']->fresh()->name)->toBe('H');   // the 403 wrote nothing

    // The SAME payload from Group Admin (who holds properties.manage) succeeds → the 403 was the gate.
    $this->actingAs(operatorUser($s, ['name' => 'Ada Admin'], 'Group Admin'))
        ->post('/app/settings/property', $payload)->assertRedirect();
    expect((string) $s['property']->fresh()->name)->toBe('Renamed');
});

it('gates org writes on settings.manage — even a Group Admin is refused; a Property Owner is not', function () {
    $s = seedScenario(1);
    $payload = ['default_currency' => 'USD', 'default_locale' => 'en_US'];

    // Group Admin manages the property but NOT org-level settings (settings.manage is Property-Owner-only).
    $this->actingAs(operatorUser($s, ['name' => 'Ada Admin'], 'Group Admin'))
        ->post('/app/settings/organization', $payload)->assertForbidden();
    expect((string) $s['org']->fresh()->default_currency)->toBe('EUR');   // unchanged

    $this->actingAs(operatorUser($s, ['name' => 'Priya Owner'], 'Property Owner'))
        ->post('/app/settings/organization', $payload)->assertRedirect();
    expect((string) $s['org']->fresh()->default_currency)->toBe('USD');
});

it('gates the page on properties.manage, and shows the org section only to settings.manage', function () {
    $s = seedScenario(1);

    // Hotel Manager can't manage the property → no page, and no live nav link.
    $this->actingAs(operatorUser($s, ['name' => 'Mo'], 'Hotel Manager'))->get('/app/settings')->assertForbidden();
    $this->actingAs(operatorUser($s, ['name' => 'Mo'], 'Hotel Manager'))->get('/app/dashboard')
        ->assertOk()->assertDontSee(route('app.settings'));

    // Group Admin sees the property + policy forms but NOT the org section (settings.manage). (#98 rebuild:
    // "Property details" → "Property basics", "Stay policies" → "Policies".)
    $this->actingAs(operatorUser($s, ['name' => 'Ada'], 'Group Admin'))->get('/app/settings')
        ->assertOk()->assertSee('Property basics')->assertSee('Policies')->assertDontSee('Save organization settings');

    // Property Owner sees the org section too.
    $this->actingAs(operatorUser($s, ['name' => 'Priya'], 'Property Owner'))->get('/app/settings')
        ->assertOk()->assertSee('Save organization settings');
});

// ── 3. HONESTY — unbacked fields are "Soon", not a fake save; input validates ────────────────────
// (#98 rebuild: Branding is now a REAL guest-brand section; Invoice numbering + Notification routing remain
// genuinely-unbuilt subsystems, honestly "Soon".)
it('stubs unbacked settings as Soon rather than faking a save', function () {
    $s = seedScenario(1);

    $response = $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/settings');

    $response->assertOk()
        ->assertSee('Guest-facing brand')   // branding is now REAL (not a "Soon" stub)
        ->assertSee('Invoice numbering')
        ->assertSee('Notification routing')
        ->assertSee('not wired yet');       // the honest note — these are subsystems, not a form

    assertNoRawBlade($response);
});

it('rejects an invalid property write and changes nothing', function () {
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post('/app/settings/property', [
        'name' => '',                       // required
        'timezone' => 'Not/AZone',          // invalid tz
        'status' => 'active',
    ])->assertSessionHasErrors(['name', 'timezone']);

    expect((string) $s['property']->fresh()->name)->toBe('H');   // untouched
});

// ── Batch C · GATE 4 — Taxes & fees editing (bounded rate/amount, properties.manage) ──────────────
it('edits a tax rate + a fee amount through the bounded SettingsService writer', function () {
    $s = seedScenario(1);
    $tax = Tax::factory()->create(['organization_id' => $s['org']->id, 'property_id' => $s['property']->id, 'mode' => 'percentage', 'rate' => 6.0]);
    $fee = Fee::factory()->create(['organization_id' => $s['org']->id, 'property_id' => $s['property']->id, 'mode' => 'flat', 'amount_minor' => 4500]);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->post('/app/settings/tax-fees', [
            'taxes' => [$tax->id => '10'],       // 6% → 10%
            'fees' => [$fee->id => '55.00'],     // €45.00 → €55.00 (integer minor #17)
        ])->assertRedirect();

    expect((float) $tax->fresh()->rate)->toBe(10.0)
        ->and((int) $fee->fresh()->amount_minor)->toBe(5500);
});

it('clamps a percentage tax rate to 0–100 (bounded write, no absurd rate)', function () {
    $s = seedScenario(1);
    $tax = Tax::factory()->create(['organization_id' => $s['org']->id, 'property_id' => $s['property']->id, 'mode' => 'percentage', 'rate' => 6.0]);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->post('/app/settings/tax-fees', ['taxes' => [$tax->id => '250']]);   // over 100 → clamped

    expect((float) $tax->fresh()->rate)->toBe(100.0);
});

it('gates tax-fees editing on properties.manage — Hotel Manager forbidden', function () {
    $s = seedScenario(1);
    $tax = Tax::factory()->create(['organization_id' => $s['org']->id, 'property_id' => $s['property']->id, 'mode' => 'percentage', 'rate' => 6.0]);

    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))   // holds no properties.manage
        ->post('/app/settings/tax-fees', ['taxes' => [$tax->id => '10']])
        ->assertForbidden();

    expect((float) $tax->fresh()->rate)->toBe(6.0);   // unchanged
})->group('security');

// ── Batch C · GATE 4 — the tax/fee edit is PROPERTY-scoped (no cross-property IDOR within the org) ──
it('will not edit a SIBLING property\'s tax — property-scoped, no cross-property IDOR', function () {
    $s = seedScenario(1);   // the current settings property (A)

    // A tax on ANOTHER property in the SAME org — a property a restricted user may not access (#38 pivot).
    $otherProperty = Property::factory()->create(['organization_id' => $s['org']->id]);
    $foreignTax = Tax::factory()->create([
        'organization_id' => $s['org']->id, 'property_id' => $otherProperty->id, 'mode' => 'percentage', 'rate' => 9.0,
    ]);

    // Editing from property A's settings page (the context is pinned to A): the write scopes to A's rows,
    // so a POST carrying B's tax id is skipped — the sibling property's tax cannot be touched from here.
    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->withSession(['current_property_id' => $s['property']->id])
        ->post('/app/settings/tax-fees', ['taxes' => [$foreignTax->id => '25']])
        ->assertRedirect();

    expect((float) $foreignTax->fresh()->rate)->toBe(9.0);   // UNCHANGED — the cross-property id was skipped
})->group('security');
