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

    // The genuinely net-new subsystems stay honestly "Soon".
    $res->assertSee('Connected apps')->assertSee('IP allow-list');
});
