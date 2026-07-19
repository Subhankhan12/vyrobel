<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OPERATOR PROFILE (Phase 1 · Sub-gate 5) — the logged-in operator edits their OWN name + work email.
//   The write is a REAL persist on `users` (not toast-only), and SELF-ONLY: the controller resolves
//   $request->user(), so a request-supplied id can NEVER redirect the write onto another account. Behind
//   auth + #26 (no fine-grained gate) so every back-office role reaches it, but an owner-role user is refused.

// ── 1 — a real self persist (name + email land on the users row) ──────────────────────────────────
it('persists the operator’s own name and work email', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Marco Reyes', 'email' => 'marco@vyrobel-demo.test'], 'Hotel Manager');

    $this->actingAs($user)
        ->post('/app/profile', ['name' => 'Marco A. Reyes', 'email' => 'marco.reyes@vyrobel-demo.test'])
        ->assertRedirect(route('app.profile'))
        ->assertSessionHas('profile_notice');

    $fresh = $user->fresh();
    expect($fresh->name)->toBe('Marco A. Reyes')                    // NOT toast-only — the row actually changed
        ->and($fresh->email)->toBe('marco.reyes@vyrobel-demo.test');
});

// ── 2 — SELF-ONLY: a request-supplied id is ignored; only the ACTOR’s row changes ─────────────────
it('ignores a request-supplied id and only ever edits the actor’s own row', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, ['name' => 'Marco Reyes', 'email' => 'marco@vyrobel-demo.test'], 'Hotel Manager');
    $victim = operatorUser($s, ['name' => 'Vera Voss', 'email' => 'vera@vyrobel-demo.test'], 'Front Desk');

    // The attacker tries to steer the write onto someone else by smuggling their id in the body.
    $this->actingAs($actor)->post('/app/profile', [
        'name' => 'Marco Renamed',
        'email' => 'marco.renamed@vyrobel-demo.test',
        'user_id' => $victim->id,   // ← ignored
        'id' => $victim->id,        // ← ignored
    ])->assertRedirect(route('app.profile'));

    expect($actor->fresh()->name)->toBe('Marco Renamed')                          // the actor changed…
        ->and($victim->fresh()->name)->toBe('Vera Voss')                          // …and the victim did NOT
        ->and($victim->fresh()->email)->toBe('vera@vyrobel-demo.test');
});

// ── 3 — email stays UNIQUE (can’t collide with another account) ───────────────────────────────────
it('rejects an email already taken by another account', function () {
    $s = seedScenario(1);
    operatorUser($s, ['name' => 'Vera Voss', 'email' => 'taken@vyrobel-demo.test'], 'Front Desk');
    $actor = operatorUser($s, ['name' => 'Marco Reyes', 'email' => 'marco@vyrobel-demo.test'], 'Hotel Manager');

    $this->actingAs($actor)
        ->post('/app/profile', ['name' => 'Marco Reyes', 'email' => 'taken@vyrobel-demo.test'])
        ->assertSessionHasErrors('email');

    expect($actor->fresh()->email)->toBe('marco@vyrobel-demo.test');   // unchanged — the collision was refused
});

// ── 4 — keeping your OWN email is fine (unique-ignoring-self) ─────────────────────────────────────
it('allows keeping your own email while changing your name', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, ['name' => 'Marco Reyes', 'email' => 'marco@vyrobel-demo.test'], 'Hotel Manager');

    $this->actingAs($actor)
        ->post('/app/profile', ['name' => 'Marco R.', 'email' => 'marco@vyrobel-demo.test'])
        ->assertSessionHasNoErrors()->assertRedirect(route('app.profile'));

    expect($actor->fresh()->name)->toBe('Marco R.');
});

// ── 5 — name is required (an empty submit is refused, nothing persists) ───────────────────────────
it('requires a name', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, ['name' => 'Marco Reyes', 'email' => 'marco@vyrobel-demo.test'], 'Hotel Manager');

    $this->actingAs($actor)
        ->post('/app/profile', ['name' => '', 'email' => 'marco@vyrobel-demo.test'])
        ->assertSessionHasErrors('name');

    expect($actor->fresh()->name)->toBe('Marco Reyes');   // unchanged
});

// ── 6 — reachable by ANY back-office role (no fine-grained gate, like /security) ───────────────────
it('is reachable by any back-office role including front desk', function () {
    $s = seedScenario(1);
    $frontDesk = operatorUser($s, ['name' => 'Fran Desk'], 'Front Desk');   // NO users.manage / properties.manage

    $this->actingAs($frontDesk)->get('/app/profile')
        ->assertOk()->assertSee('Fran Desk')->assertSee('Personal details');
});

// ── 7 — an owner-role user is REFUSED from the operator profile (invariant #26) ────────────────────
it('refuses an owner-role user from the operator profile', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s);   // a Membership but NO back-office role — the #26 shape

    $this->actingAs($owner)->get('/app/profile')->assertRedirect(route('owner.dashboard'));
    $this->actingAs($owner)->post('/app/profile', ['name' => 'Hacker', 'email' => 'x@y.test'])
        ->assertRedirect(route('owner.dashboard'));
});

// ── 8 — the profile shows the viewer’s role + status (read-only identity) ──────────────────────────
it('shows the operator’s role and status read-only', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Marco Reyes'], 'Hotel Manager');

    $this->actingAs($user)->get('/app/profile')
        ->assertOk()
        ->assertSee('Hotel Manager')   // their real spatie role, team-scoped
        ->assertSee('Active');         // status
});
