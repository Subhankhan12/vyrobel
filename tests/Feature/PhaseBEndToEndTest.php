<?php

use App\Mail\OwnerInviteMail;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\Unit;
use App\Models\User;
use App\Support\Auth\PasswordPolicy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

// Auth Phase B — the WHOLE owner-invite flow proven in ONE continuous chain, with the security
// recap asserted at each hop:
//
//   operator (logged in, tenant-scoped) invites an owner via the modal action
//     → OwnerInviteMail dispatched with a single-use, sha256-hashed, tokened /invite/{token} link
//       → the invitee visits that link → sets a valid password
//         → login activates + owner activates + assigned units + commission agreement become REAL
//           → auto-login → lands on /owner seeing THEIR own owner-scoped data
//             → the token is now SPENT (a second visit fails with the SAME generic page as a garbage token)
//
// Security recap asserted: tokens hashed / single-use / never stored in plaintext; passwords hashed;
// auto-login establishes the session on activation; owner-scope binds to the activated owner; no
// invite enumeration (spent === garbage response).

it('runs the full Phase B chain end-to-end: operator invite → email link → activate → owner portal → token spent', function () {
    $email = 'e2e-owner@example.test';
    // ⚠️ THE FIXTURE ROTTED WHEN THE POLICY TIGHTENED, AND THE ERROR THAT REPORTED IT WAS UNREADABLE.
    // This was 'a-strong-owner-pass' — letters and a symbol, and NO DIGIT. PasswordPolicy::rule() now
    // requires ->numbers(), so the activation POST was rejected, the controller redirected back to the
    // invite screen instead of /owner, and the assertRedirect failed. The register recorded this test as an
    // "assertion-shape error" only because the real message was hidden (see the note at the foot of this
    // file) — the assertions were never wrong.
    $password = 'a-strong-owner-pass-42';

    // ⚠️ AND THE FIXTURE NOW PROVES ITSELF AGAINST THE LIVE POLICY, so the next tightening fails HERE, with
    // a sentence that says what happened, instead of surfacing three hops later as a redirect mismatch.
    expect(Validator::make(['password' => $password], ['password' => PasswordPolicy::rule()])->passes())
        ->toBeTrue('the end-to-end fixture password must satisfy the live PasswordPolicy');

    // ── Operator, logged in + tenant-scoped, with a real unit to assign ──────────────────────────
    $s = seedScenario(1);
    $unit = $s['units'][0];
    $operator = User::factory()->create(['email' => 'op-e2e@vyrobel.test', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $operator->id, 'status' => 'active']);
    // A Membership alone is NOT operator access — /app is an allow-list gate (EnsureBackOfficeAccess).
    // The inviting operator holds a real back-office role, exactly as a live invited staff member does.
    // Group Admin: this chain invites staff, and the invite route is gated on `users.manage` (#30).
    $operator = grantBackOfficeRole($operator, $s['org'], 'Group Admin');

    // ── 1. The operator invites an owner via the REAL modal action (POST → controller → service → PRG)
    Mail::fake();
    $this->actingAs($operator)->post('/app/owners/invite', ['current_password' => 'password',
        'name' => 'Nadia Owner',
        'email' => $email,
        'role' => 'owner',
        'commission_pct' => '18',
        'unit_ids' => [$unit->id],
    ])->assertRedirect(route('app.owners'))->assertSessionHas('owners_notice');

    // ── 2. The email carried a single-use tokened /invite/{token} link (captured from the Mailable) ──
    $token = null;
    Mail::assertSent(OwnerInviteMail::class, function (OwnerInviteMail $m) use (&$token, $email) {
        $token = $m->token;

        return $m->hasTo($email);
    });
    expect($token)->toBeString()->not->toBeEmpty();

    // Security: the invite persists ONLY the sha256 hash, never the plaintext token.
    $invite = OwnerInvite::query()->where('email', $email)->firstOrFail();
    expect($invite->status)->toBe('pending')
        ->and($invite->token_hash)->toBe(hash('sha256', $token))
        ->and($invite->token_hash)->not->toBe($token)
        ->and($invite->organization_id)->toBe($s['org']->id);

    // ── 3. The invitee is a guest — the operator is not them: end the operator session ──────────────
    $this->post('/logout');
    $this->assertGuest();

    // The login exists but is INACTIVE (Phase A login is active-only → the invite can't be used to sign in yet).
    $invitedUser = User::query()->where('email', $email)->firstOrFail();
    expect($invitedUser->status)->toBe('invited');
    $this->post('/login', ['email' => $email, 'password' => $password])->assertSessionHasErrors();   // can't sign in yet
    $this->assertGuest();

    // The invitee opens the tokened link and is greeted by their own name.
    $this->get("/invite/{$token}")->assertOk()->assertSee('Set your password')->assertSee('Nadia Owner');

    // ── 4. Sets a valid password → activation + auto-login → redirect to the owner portal ───────────
    $this->post("/invite/{$token}", ['password' => $password, 'password_confirmation' => $password])
        ->assertRedirect('/owner');

    $ownerUser = User::query()->where('email', $email)->firstOrFail();
    $this->assertAuthenticatedAs($ownerUser);            // auto-login established the session on activation
    expect($ownerUser->status)->toBe('active');

    // Security: the password is HASHED (never stored plaintext) and verifies.
    expect($ownerUser->password)->not->toBe($password)
        ->and(Hash::check($password, $ownerUser->password))->toBeTrue();

    // ── 5. The operator's choices became REAL: owner active, unit assigned, commission agreement live ─
    app(TenantContext::class)->set($s['org']->id);
    $owner = Owner::query()->where('user_id', $ownerUser->id)->firstOrFail();
    expect($owner->status)->toBe('active')
        ->and((string) Unit::query()->find($unit->id)->owner_id)->toBe((string) $owner->id)
        ->and((float) ManagementAgreement::query()->where('owner_id', $owner->id)->where('status', 'active')->value('commission_pct'))->toBe(18.0)
        ->and(OwnerInvite::query()->find($invite->id)->status)->toBe('accepted');   // token SPENT

    // ── 6. The owner portal renders THIS owner's own scoped data (scope bound, not fail-closed) ─────
    $unit->update(['code' => 'E2E-OWNER-SUITE']);
    $this->get('/owner/units')->assertOk()->assertSee('E2E-OWNER-SUITE');

    // ── 7. Single-use + no enumeration: the SPENT token now gives the SAME generic page as garbage ──
    $spent = $this->get("/invite/{$token}")->assertOk()->assertSee('isn’t valid');
    $garbage = $this->get('/invite/totally-made-up-token')->assertOk()->assertSee('isn’t valid');
    expect($spent->getContent())->toBe($garbage->getContent());   // indistinguishable → no invite enumeration

    // A reuse POST cannot re-activate; the invite stays accepted-once.
    $this->post("/invite/{$token}", ['password' => 'attacker-repass-1', 'password_confirmation' => 'attacker-repass-1'])
        ->assertOk()->assertSee('isn’t valid');
    expect(OwnerInvite::query()->find($invite->id)->status)->toBe('accepted');
});

// ─────────────────────────────────────────────────────────────────────────────────────────────────
// ⚠️⚠️ WHY THIS TEST WAS MIS-DIAGNOSED FOR SO LONG — READ THIS BEFORE TRUSTING A FAILURE MESSAGE HERE.
//
// It was recorded as an "assertion-shape error: Call to a member function all() on array". There is no
// mis-shaped assertion in this file. That crash comes from the FRAMEWORK, in
// Illuminate\Testing\TestResponseAssert::injectResponseContext(), which runs ONLY AFTER an assertion has
// already failed, purely to enrich the message:
//
//     $session->get('errors')->all()
//
// `config/session.php` sets `'serialization' => 'json'` (the Laravel 12+ hardening against gadget-chain
// attacks if APP_KEY leaks). Under JSON serialization a ViewErrorBag round-trips through the session store
// as a plain ARRAY, so `->all()` fatals — and the REAL failure message is destroyed with it.
//
// ⚠️ THIS IS NOT A PRODUCTION DEFECT, AND THAT WAS VERIFIED RATHER THAN ASSUMED: a genuine failed login
// against a running server (database driver, json serialization) returns HTTP 200 and renders "These
// credentials do not match" — Blade's $errors is a real ViewErrorBag on the request path. Only the test
// helper's direct read of the raw session store sees the serialized form.
//
// ⚠️ SO IT AFFECTS EVERY FAILED ASSERTION ON A REDIRECT RESPONSE THAT CARRIES VALIDATION ERRORS, SUITE-WIDE
// — the message is replaced by the fatal, which is exactly how a stale fixture password got filed as an
// assertion-shape bug. If you meet it again, the failure is real and its message is being eaten; recover it
// by temporarily guarding that framework line (is_array($bag) ? $bag : $bag->all()) and re-running.
// ─────────────────────────────────────────────────────────────────────────────────────────────────
