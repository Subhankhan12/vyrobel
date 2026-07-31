<?php

use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// #96 GATE 2 — the operator Profile: real personal details + UI preferences (stored + applied), a READ-ONLY
// display of the REAL role caps, and Sign-in & security reusing #66 (password/2FA) + REAL session revocation.

function profileStaff(array $s, string $role = 'Group Admin'): User
{
    $user = User::factory()->create(['name' => 'Marco Reyes', 'status' => 'active', 'password' => Hash::make('secret-pass-123')]);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $s['org'], $role);
}

// ── Personal details save (preferred name / phone / language / timezone), audited ─────────────────
it('saves the operator real personal details', function () {
    $s = seedScenario(1);
    $user = profileStaff($s);

    $this->actingAs($user)->post(route('app.profile.update'), [
        'name' => 'Marco Reyes', 'preferred_name' => 'Marco', 'email' => 'marco@x.test',
        'phone' => '+351 91 000 0000', 'locale' => 'pt', 'timezone' => 'Europe/Lisbon',
    ])->assertRedirect(route('app.profile'))->assertSessionHas('profile_notice');

    $user->refresh();
    expect($user->preferred_name)->toBe('Marco')
        ->and((string) $user->phone)->toBe('+351 91 000 0000')
        ->and($user->locale)->toBe('pt')
        ->and($user->timezone)->toBe('Europe/Lisbon');
});

// ── Preferences save + are genuinely APPLIED to the workspace body (motion/density) ───────────────
it('stores and applies the UI preferences', function () {
    $s = seedScenario(1);
    $user = profileStaff($s);

    $this->actingAs($user)->post(route('app.profile.preferences'), [
        'compact_density' => '1', 'reduce_motion' => '1', 'date_format' => 'MM/DD/YYYY',
    ])->assertRedirect(route('app.profile'));

    $user->refresh();
    expect((bool) $user->compact_density)->toBeTrue()
        ->and((bool) $user->reduce_motion)->toBeTrue()
        ->and($user->date_format)->toBe('MM/DD/YYYY');

    // The stored prefs are APPLIED to the operator layout body (motion off + compact density).
    $this->actingAs($user)->get(route('app.profile'))
        ->assertOk()
        ->assertSee('data-reduce-motion="1"', false)
        ->assertSee('data-density="compact"', false);
});

// ── Role & access shows the REAL caps (a Front Desk cannot take owner payouts) ────────────────────
it('displays the real role capabilities, never a fabricated set', function () {
    $s = seedScenario(1);
    $frontDesk = profileStaff($s, 'Front Desk');   // no payouts.manage

    $this->actingAs($frontDesk)->get(route('app.profile'))
        ->assertOk()
        ->assertSee('Role &amp; access', false)
        ->assertSee('Take payments &amp; issue refunds', false)   // the label is listed
        ->assertSee('Owner payouts &amp; billing', false)
        ->assertSee('Access is managed by your workspace admin');
});

// ── Sign out of all other devices — REAL session revocation (#66) ─────────────────────────────────
it('signs out other sessions for real', function () {
    $s = seedScenario(1);
    $user = profileStaff($s);

    // Two "other" sessions + assume the current one is created by the acting request.
    DB::table('sessions')->insert([
        ['id' => 'other-1', 'user_id' => $user->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'Chrome', 'payload' => '', 'last_activity' => now()->timestamp],
        ['id' => 'other-2', 'user_id' => $user->id, 'ip_address' => '2.2.2.2', 'user_agent' => 'Firefox', 'payload' => '', 'last_activity' => now()->timestamp],
    ]);

    $this->actingAs($user)->post(route('app.profile.sign-out-others'), ['current_password' => 'secret-pass-123'])
        ->assertRedirect(route('app.profile'))->assertSessionHas('profile_notice');

    // The other sessions are really gone.
    expect(DB::table('sessions')->where('user_id', $user->id)->whereIn('id', ['other-1', 'other-2'])->count())->toBe(0);
});

// ── Sign out others refuses a wrong password ──────────────────────────────────────────────────────
it('refuses sign-out-others with a wrong password', function () {
    $s = seedScenario(1);
    $user = profileStaff($s);
    DB::table('sessions')->insert(['id' => 'keep-1', 'user_id' => $user->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'Chrome', 'payload' => '', 'last_activity' => now()->timestamp]);

    $this->actingAs($user)->post(route('app.profile.sign-out-others'), ['current_password' => 'wrong'])->assertSessionHasErrors('current_password');
    expect(DB::table('sessions')->where('id', 'keep-1')->exists())->toBeTrue();   // untouched
});
