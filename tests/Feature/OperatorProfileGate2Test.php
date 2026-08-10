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

// ═══════════════════════════════════════════════════════════════════════════════════════════════════
// P33 (design-parity) — the design decorates this page with things the product cannot know, and one
// live label read the opposite of the control it drove.
// ═══════════════════════════════════════════════════════════════════════════════════════════════════

it('never invents a sign-in LOCATION — there is no geolocation anywhere (#39)', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops']);
    $operator->forceFill(['last_login_at' => now()->subHour()])->save();

    // ⚠️ A REAL session row, so the "Active sessions" block actually renders. Without one the block is
    // empty and any assertion about its contents passes vacuously (the P27 lesson).
    DB::table('sessions')->insert([
        'id' => 'p33-session-id', 'user_id' => $operator->getKey(),
        'ip_address' => '203.0.113.9', 'user_agent' => 'Mozilla/5.0 (Macintosh) Chrome/120',
        'payload' => '', 'last_activity' => now()->getTimestamp(),
    ]);

    $html = $this->actingAs($operator)->get(route('app.profile'))->assertOk()
        ->assertSee('Last sign-in')
        ->assertSee('Active sessions')
        ->assertSee('203.0.113.9')          // the REAL ip renders — proving the block is populated
        ->getContent();

    // ⚠️ The design shows "Today 08:14 · Faro, PT" and "Chrome · macOS · Faro, PT". `sessions` stores an
    // ip_address and a user_agent and NOTHING else — no geoip package, no location column. A city here
    // would be invented, and it would read as security evidence.
    expect($html)->not->toContain('Faro, PT')
        ->and($html)->not->toContain('Last sign-in from');
});

it('never claims when the password was last changed — the column does not exist (#39)', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops']);

    $html = $this->actingAs($operator)->get(route('app.profile'))->assertOk()->getContent();

    // The design says "Changed 3 months ago"; there is no `password_changed_at` on users, so the honest
    // line points at the page that really does change it.
    expect($html)->not->toContain('Changed 3 months ago')
        ->and($html)->not->toContain('Password changed');
    expect($html)->toContain('Change it on the Security page');
});

it('labels the motion preference as what the control actually does', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops']);

    $html = $this->actingAs($operator)->get(route('app.profile'))->assertOk()->getContent();

    // ⚠️ The control is `reduce_motion`: checked = LESS motion. It used to be labelled "Interface motion"
    // with a tiny trailing "reduce", so switching it on read as turning motion ON.
    expect($html)->toContain('Reduce motion')
        ->and($html)->toContain('name="reduce_motion"');
    expect($html)->not->toContain('<strong>Interface motion</strong>');
});

it('keeps the preference switches real, focusable inputs (2.1.1)', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops']);

    $html = $this->actingAs($operator)->get(route('app.profile'))->assertOk()->getContent();

    // Styled as switches/segments but still real checkboxes and radios — never display:none (the P30 defect).
    expect($html)->toContain('type="checkbox" class="vyp-switch"')
        ->and($html)->toContain('type="radio" name="date_format"')
        ->and($html)->not->toContain('.vyp-switch { display: none')
        ->and($html)->toContain('.vyp-switch:focus-visible');
});
