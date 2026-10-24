<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// #104 GATE 3b — the operator Security page renders the REAL active-sessions panel (the same ActiveSessions the
// Profile page ships) instead of a "Soon" stub. Genuinely net-new subsystems (connected apps / trusted devices /
// IP allow-list) stay honestly "Soon".

it('renders the real active-sessions panel with the user’s sessions, not a Soon stub', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');

    DB::table('sessions')->insert([
        ['id' => 'sess-a', 'user_id' => $manager->id, 'ip_address' => '1.2.3.4', 'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X)', 'last_activity' => time(), 'payload' => ''],
        ['id' => 'sess-b', 'user_id' => $manager->id, 'ip_address' => '5.6.7.8', 'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS)', 'last_activity' => time() - 3600, 'payload' => ''],
    ]);

    $res = $this->actingAs($manager)->get(route('app.security'))->assertOk();

    // The panel is REAL now: it lists the sessions + offers the (password-confirmed) sign-out-others.
    $res->assertSee('Active sessions &amp; devices', false)
        ->assertSee('1.2.3.4')
        ->assertSee('5.6.7.8')
        ->assertSee('Sign out of all other devices');

    // #104b GATE 1 — the IP allowlist is now a REAL section (built, genuinely enforced); Connected apps is the
    // honest empty state (no operator OAuth catalog).
    $res->assertSee('IP allowlist')->assertSee('No IP restrictions')
        ->assertSee('Connected apps')->assertSee('No apps connected');
});

// ── ⚠️ THE SAFETY BADGE MUST TRACK THE REAL 2FA STATE ────────────────────────────────────────────
// The "Account security" badge used to be a HARDCODED green "Protected", rendered unconditionally — so an
// account with two-factor OFF was told it was protected. On an auth page that is a safety CLAIM, not
// decoration (the same class as the hardcoded "Verified" badge the finish-to-100 pass removed from Owners).
// It is now derived from $twoFactor['state']; this proves it genuinely flips, in BOTH directions.
// ⚠️ 2026-10-24 — his design replaced the badge with the score's tier (App\Support\Security\SecurityScore) and the two-factor
// row's own On / Off chip. The ruling this pins is unchanged: with two-factor OFF nothing on the page claims protection.
it('derives the account-security state from the REAL two-factor state, never a fixed “Protected”', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');

    // 2FA OFF (the seeded default) → nothing claims protection, and the row says Off.
    expect($manager->hasTwoFactorEnabled())->toBeFalse();
    $this->actingAs($manager)->get(route('app.security'))->assertOk()
        ->assertSee('<span class="vy-sx-chip is-quiet">Off</span>', false)
        ->assertSee('<span class="vy-sx-tier">Basic</span>', false)
        ->assertSee('Turn on two-factor to strengthen your account.')
        ->assertDontSee('Protected')
        ->assertDontSee('<span class="vy-sx-tier">Strong</span>', false);

    // Turn 2FA genuinely ON (the real columns the panel reads) → the row says On and the tier moves up.
    $manager->forceFill([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => encrypt(json_encode(['aaaa-bbbb'])),
        'two_factor_confirmed_at' => now(),
    ])->save();
    expect($manager->fresh()->hasTwoFactorEnabled())->toBeTrue();

    $this->actingAs($manager->fresh())->get(route('app.security'))->assertOk()
        ->assertSee('<span class="vy-sx-chip">On</span>', false)
        ->assertSee('<span class="vy-sx-tier">Good</span>', false)
        ->assertDontSee('<span class="vy-sx-chip is-quiet">Off</span>', false);
});
