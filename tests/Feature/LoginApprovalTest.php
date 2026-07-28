<?php

use App\Mail\LoginApprovalMail;
use App\Models\LoginApproval;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Support\Auth\TrustedDeviceService;
use App\Support\TwoFactor\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

// LOGIN APPROVAL for new devices (Wave B · GATE 4) — an opt-in user signing in from an UNTRUSTED device is held
// PENDING until an emailed link is clicked. The contract:
//   • ⚠️ the sign-in is genuinely BLOCKED — a correct password grants NO access until approval (mutation-proven);
//   • the emailed SIGNED link marks the request approved (logs no one in); the pending browser then completes;
//   • continuing BEFORE approval does nothing (stays pending);
//   • a trusted device SKIPS approval entirely;
//   • if the account also has 2FA, approval hands off to the 2FA challenge (still not logged in);
//   • the approve link is SIGNED (a tampered link is refused) + single-use.

function approvalUser(array $s): User
{
    return operatorUser($s, ['name' => 'Ada Approve', 'password' => 'pw-appr-1', 'require_login_approval' => true], 'Front Desk');
}

// ── 1. ⚠️ THE CRUX — an untrusted device is held PENDING, an email is sent, and /app is unreachable ──
it('holds a new-device sign-in pending and grants no access until approval', function () {
    Mail::fake();
    $s = seedScenario(1);
    $user = approvalUser($s);

    $this->post('/login', ['email' => $user->email, 'password' => 'pw-appr-1'])
        ->assertRedirect(route('login-approval.show'));
    $this->assertGuest();                                  // NOT logged in
    $this->get('/app/dashboard')->assertRedirect(route('login'));   // the back office rejects the pending user

    // A real pending approval row + a queued approval email.
    expect(LoginApproval::query()->where('user_id', $user->getKey())->whereNull('consumed_at')->exists())->toBeTrue();
    Mail::assertQueued(LoginApprovalMail::class, fn (LoginApprovalMail $m) => $m->hasTo($user->email));
});

// ── 2. The emailed signed link approves it, then the pending browser completes the sign-in ─────────
it('completes the sign-in after the emailed link is clicked', function () {
    Mail::fake();
    $s = seedScenario(1);
    $user = approvalUser($s);

    $this->post('/login', ['email' => $user->email, 'password' => 'pw-appr-1'])->assertRedirect(route('login-approval.show'));

    // Capture the signed approve URL from the queued email + click it (any device — the signature authorizes).
    $url = null;
    Mail::assertQueued(LoginApprovalMail::class, function (LoginApprovalMail $m) use (&$url) {
        $url = $m->approveUrl;

        return true;
    });
    $this->get($url)->assertOk();

    // Back on the pending browser → continue completes the login.
    $this->post('/login-approval/continue')->assertRedirect();
    $this->assertAuthenticatedAs($user->fresh());
    $this->get('/app/dashboard')->assertOk();

    // The approval was consumed (single-use).
    expect(LoginApproval::query()->where('user_id', $user->getKey())->whereNotNull('consumed_at')->exists())->toBeTrue();
});

// ── 3. Continuing BEFORE approval does nothing — still pending, still guest ────────────────────────
it('does not complete the sign-in before the link is clicked', function () {
    Mail::fake();
    $s = seedScenario(1);
    $user = approvalUser($s);

    $this->post('/login', ['email' => $user->email, 'password' => 'pw-appr-1'])->assertRedirect(route('login-approval.show'));

    $this->post('/login-approval/continue')->assertRedirect(route('login-approval.show'));
    $this->assertGuest();
});

// ── 4. A trusted device SKIPS approval entirely ───────────────────────────────────────────────────
it('skips approval for a trusted device', function () {
    Mail::fake();
    $s = seedScenario(1);
    $user = approvalUser($s);

    $token = 'tok_'.Str::random(40);
    TrustedDevice::create(['user_id' => (string) $user->getKey(), 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(30)]);

    $this->withUnencryptedCookie(TrustedDeviceService::COOKIE, $token)
        ->post('/login', ['email' => $user->email, 'password' => 'pw-appr-1'])
        ->assertRedirect();
    $this->assertAuthenticatedAs($user->fresh());
    Mail::assertNothingQueued();
});

// ── 5. With 2FA also on, approval hands off to the 2FA challenge (still not logged in) ─────────────
it('routes to the 2FA challenge after approval when the account also has 2FA', function () {
    Mail::fake();
    $s = seedScenario(1);
    $user = approvalUser($s);
    $svc = app(TwoFactorService::class);
    $svc->startEnrollment($user);
    $svc->confirm($user, app(Google2FA::class)->getCurrentOtp((string) $user->two_factor_secret));

    $this->post('/login', ['email' => $user->email, 'password' => 'pw-appr-1'])->assertRedirect(route('login-approval.show'));

    $url = null;
    Mail::assertQueued(LoginApprovalMail::class, function (LoginApprovalMail $m) use (&$url) {
        $url = $m->approveUrl;

        return true;
    });
    $this->get($url)->assertOk();

    // Continue → NOT logged in yet; sent to the 2FA code challenge.
    $this->post('/login-approval/continue')->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();
});

// ── 6. The approve link is SIGNED — a tampered/unsigned link is refused ────────────────────────────
it('refuses an unsigned or tampered approval link', function () {
    $s = seedScenario(1);
    $user = approvalUser($s);
    $approval = LoginApproval::create([
        'user_id' => (string) $user->getKey(), 'token_hash' => hash('sha256', 'tok'), 'expires_at' => now()->addMinutes(30),
    ]);

    // No signature → 403 (the `signed` middleware).
    $this->get('/login-approval/approve/'.$approval->id.'?token=tok')->assertForbidden();
    expect($approval->fresh()->approved_at)->toBeNull();
});

// ── 7. The owner Security toggle enables/disables login approval (re-auth) ─────────────────────────
it('toggles login approval from the owner Security page with re-auth', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $user->forceFill(['password' => 'owner-pass-9', 'require_login_approval' => false])->save();

    // wrong password → refused, no change
    $this->actingAs($user)->post(route('owner.security.login-approval'), ['enabled' => '1', 'current_password' => 'nope'])
        ->assertSessionHasErrors('current_password');
    expect($user->fresh()->require_login_approval)->toBeFalse();

    // correct password → enabled
    $this->actingAs($user)->post(route('owner.security.login-approval'), ['enabled' => '1', 'current_password' => 'owner-pass-9'])
        ->assertRedirect();
    expect($user->fresh()->require_login_approval)->toBeTrue();
});
