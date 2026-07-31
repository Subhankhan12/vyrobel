<?php

use App\Models\IpAllowRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104b GATE 1 — the operator Security IP-allowlist is a REAL, genuinely-enforced control (EnforceIpAllowlist on
// /app): 0 rules ⇒ no restriction; a rule that doesn't cover the request IP ⇒ 403; a covering rule ⇒ reachable.
// Writes are settings.manage-gated + audited; the first rule auto-adds the current IP (lockout mitigation).

it('does not restrict /app when the org has no IP rules', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $this->actingAs($manager)->withSession(['current_property_id' => $s['property']->id])
        ->get('/app/dashboard')->assertOk();
});

it('403s /app when a rule exists that does not cover the request IP, and admits a covering rule', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $session = ['current_property_id' => $s['property']->id];

    // A rule that does NOT match the test request IP (127.0.0.1) → the allowlist genuinely blocks.
    $blocking = IpAllowRule::create(['organization_id' => $s['org']->id, 'ip_address' => '203.0.113.0/24']);
    $this->actingAs($manager)->withSession($session)->get('/app/dashboard')->assertForbidden();

    // Add a rule covering the request IP → reachable again (real enforcement, both directions).
    IpAllowRule::create(['organization_id' => $s['org']->id, 'ip_address' => '127.0.0.1']);
    $this->actingAs($manager)->withSession($session)->get('/app/dashboard')->assertOk();
});

it('lets an admin add a rule (auditing + lockout auto-add) and refuses a non-admin', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');   // holds settings.manage
    $session = ['current_property_id' => $s['property']->id];

    // Adding the FIRST rule (an office range that doesn't cover 127.0.0.1) also auto-adds the current IP so the
    // admin isn't locked out — so the workspace stays reachable right after enabling.
    $this->actingAs($admin)->withSession($session)
        ->post(route('app.security.ip-rules.add'), ['ip_address' => '10.0.0.0/8', 'label' => 'Office'])
        ->assertRedirect(route('app.security'));

    expect(IpAllowRule::query()->where('ip_address', '10.0.0.0/8')->exists())->toBeTrue()
        ->and(IpAllowRule::query()->where('ip_address', '127.0.0.1')->exists())->toBeTrue();   // auto-added
    $this->assertDatabaseHas('audit_events', ['action' => 'app.ip_allow_rule.added']);

    // Still reachable (the auto-add kept the admin in).
    $this->actingAs($admin)->withSession($session)->get('/app/dashboard')->assertOk();

    // A non-admin (no settings.manage) cannot add a rule.
    $frontDesk = operatorUser($s, [], 'Front Desk');
    expect($frontDesk->can('settings.manage'))->toBeFalse();
    $this->actingAs($frontDesk)->withSession($session)
        ->post(route('app.security.ip-rules.add'), ['ip_address' => '8.8.8.8'])->assertForbidden();
});

it('treats a duplicate IP add as a no-op notice instead of a unique-constraint 500', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');
    $session = ['current_property_id' => $s['property']->id];

    $this->actingAs($admin)->withSession($session)
        ->post(route('app.security.ip-rules.add'), ['ip_address' => '198.51.100.5'])->assertRedirect();
    // Re-adding the SAME address must not hit the unique(organization_id, ip_address) index → friendly no-op.
    $this->actingAs($admin)->withSession($session)
        ->post(route('app.security.ip-rules.add'), ['ip_address' => '198.51.100.5'])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect(IpAllowRule::query()->where('ip_address', '198.51.100.5')->count())->toBe(1);   // not duplicated
});

it('removes a rule and exports the account data (self-scoped)', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, ['email' => 'admin@demo.test'], 'Property Owner');
    $session = ['current_property_id' => $s['property']->id];
    $rule = IpAllowRule::create(['organization_id' => $s['org']->id, 'ip_address' => '127.0.0.1']);

    $this->actingAs($admin)->withSession($session)
        ->delete(route('app.security.ip-rules.remove', $rule->id))->assertRedirect();
    expect(IpAllowRule::query()->whereKey($rule->id)->exists())->toBeFalse();

    // Data export = the acting user's own account (self-scoped, #28 — not org/guest data).
    $json = $this->actingAs($admin)->withSession($session)->get(route('app.security.export'));
    $json->assertOk();
    expect($json->streamedContent())->toContain('admin@demo.test')->toContain('exported_for');
});
