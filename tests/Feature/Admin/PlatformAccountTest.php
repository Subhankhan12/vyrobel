<?php

declare(strict_types=1);

use App\Mail\AdminEmailChangeMail;
use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

// SUPER ADMIN · ACCOUNT — the admin's own account: profile · password · email (re-verified) · 2FA recovery.
// Mirrors the tenant discipline (current-password re-auth, real rules, email re-verify), audited, self-only.

/** Assert the admin guard accepts a given email+password (a login would succeed). */
function adminCredsValid(string $email, string $password): bool
{
    return Auth::guard('admin')->validate(['email' => $email, 'password' => $password, 'status' => 'active']);
}

// ── GATE 1 — profile ────────────────────────────────────────────────────────────────────────────────
it('the admin can edit their profile name (audited)', function () {
    $admin = platformAdmin(['name' => 'Old Name']);

    $this->actingAs($admin, 'admin')
        ->put(route('admin.account.profile'), ['name' => 'New Platform Name'])
        ->assertRedirect(route('admin.account.edit'));

    expect($admin->fresh()->name)->toBe('New Platform Name');
    expect(PlatformAdminAction::query()->where('action', 'platform.account.profile_updated')->exists())->toBeTrue();
})->group('security');

it('the account page renders on the control-room design', function () {
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.account.edit'))
        ->assertOk()->assertSee('Your account')->assertSee('Change password')->assertSee('Login email');
})->group('security');

// ── GATE 1 — password (current-password required, real rules, session kept) ─────────────────────────
it('changing the password REQUIRES the current password (anti-hijack) + the real rules; then the new works, the old fails', function () {
    $admin = platformAdmin(['email' => 'root@x.test']);   // factory password = 'password'

    // Wrong current password → rejected, unchanged.
    $this->actingAs($admin, 'admin')
        ->put(route('admin.account.password'), ['current_password' => 'wrong', 'password' => 'BrandNewPass9!', 'password_confirmation' => 'BrandNewPass9!'])
        ->assertSessionHasErrors('current_password');
    expect(adminCredsValid('root@x.test', 'password'))->toBeTrue();   // still the old password

    // A too-short new password → rejected (real server rule).
    $this->actingAs($admin, 'admin')
        ->put(route('admin.account.password'), ['current_password' => 'password', 'password' => 'short', 'password_confirmation' => 'short'])
        ->assertSessionHasErrors('password');

    // Correct → changed: the new password works, the OLD no longer does.
    $this->actingAs($admin, 'admin')
        ->put(route('admin.account.password'), ['current_password' => 'password', 'password' => 'BrandNewPass9!', 'password_confirmation' => 'BrandNewPass9!'])
        ->assertRedirect(route('admin.account.edit'));

    expect(adminCredsValid('root@x.test', 'BrandNewPass9!'))->toBeTrue();
    expect(adminCredsValid('root@x.test', 'password'))->toBeFalse();
    expect(PlatformAdminAction::query()->where('action', 'platform.account.password_changed')->exists())->toBeTrue();
})->group('security');

// ── GATE 1 — 2FA recovery codes ──────────────────────────────────────────────────────────────────────
it('regenerating recovery codes requires the current password + is audited', function () {
    $admin = platformAdmin();   // withTwoFactor + password 'password'

    // Wrong password → rejected.
    $this->actingAs($admin, 'admin')->post(route('admin.account.recovery-codes'), ['current_password' => 'nope'])->assertSessionHasErrors('current_password');

    // Correct → new codes flashed once + audited.
    $this->actingAs($admin, 'admin')->post(route('admin.account.recovery-codes'), ['current_password' => 'password'])
        ->assertRedirect(route('admin.account.edit'))
        ->assertSessionHas('admin_recovery_codes');
    expect(PlatformAdminAction::query()->where('action', 'platform.account.recovery_codes_regenerated')->exists())->toBeTrue();
})->group('security');

// ── GATE 2 — email change (re-verified) ──────────────────────────────────────────────────────────────
it('an email change REQUIRES the current password, parks pending_email, and DOES NOT switch the login yet', function () {
    Mail::fake();
    $admin = platformAdmin(['email' => 'old@x.test']);

    // Wrong current password → rejected.
    $this->actingAs($admin, 'admin')->post(route('admin.account.email.request'), ['current_password' => 'wrong', 'email' => 'new@x.test'])
        ->assertSessionHasErrors('current_password');
    expect($admin->fresh()->pending_email)->toBeNull();

    // Correct → parked + mail sent; the live email is UNCHANGED.
    $this->actingAs($admin, 'admin')->post(route('admin.account.email.request'), ['current_password' => 'password', 'email' => 'new@x.test'])
        ->assertRedirect(route('admin.account.edit'));

    $admin->refresh();
    expect($admin->email)->toBe('old@x.test');            // 🔒 login unchanged
    expect($admin->pending_email)->toBe('new@x.test');
    Mail::assertSent(AdminEmailChangeMail::class);
    expect(PlatformAdminAction::query()->where('action', 'platform.account.email_change_requested')->exists())->toBeTrue();

    // 🔒 THE PENDING EMAIL CANNOT LOG IN before verification.
    expect(adminCredsValid('new@x.test', 'password'))->toBeFalse();
    expect(adminCredsValid('old@x.test', 'password'))->toBeTrue();
})->group('security');

it('the SIGNED verify link switches the email; then the new email logs in and the old does not', function () {
    $admin = platformAdmin(['email' => 'old@x.test']);
    $admin->forceFill(['pending_email' => 'new@x.test'])->save();

    $url = URL::temporarySignedRoute('admin.account.email.verify', now()->addHour(), [
        'admin' => $admin->getKey(), 'hash' => sha1('new@x.test'),
    ]);

    $this->get($url)->assertRedirect(route('admin.login'));

    $admin->refresh();
    expect($admin->email)->toBe('new@x.test');
    expect($admin->pending_email)->toBeNull();
    expect(adminCredsValid('new@x.test', 'password'))->toBeTrue();
    expect(adminCredsValid('old@x.test', 'password'))->toBeFalse();
    expect(PlatformAdminAction::query()->where('action', 'platform.account.email_changed')->exists())->toBeTrue();
})->group('security');

it('the verify link fails closed on a bad signature or a stale/mismatched hash (no takeover)', function () {
    $admin = platformAdmin(['email' => 'old@x.test']);
    $admin->forceFill(['pending_email' => 'new@x.test'])->save();

    // Unsigned URL → 403 (the `signed` middleware).
    $this->get(route('admin.account.email.verify', ['admin' => $admin->getKey(), 'hash' => sha1('new@x.test')]))->assertForbidden();

    // Signed but WRONG hash (doesn't match the parked pending email) → not switched.
    $badHashUrl = URL::temporarySignedRoute('admin.account.email.verify', now()->addHour(), ['admin' => $admin->getKey(), 'hash' => sha1('attacker@x.test')]);
    $this->get($badHashUrl)->assertRedirect(route('admin.login'));
    expect($admin->fresh()->email)->toBe('old@x.test');   // unchanged
})->group('security');

it('a taken email fails closed — the address is not switched', function () {
    PlatformAdmin::factory()->create(['email' => 'taken@x.test']);
    $admin = platformAdmin(['email' => 'old@x.test']);
    $admin->forceFill(['pending_email' => 'taken@x.test'])->save();

    $url = URL::temporarySignedRoute('admin.account.email.verify', now()->addHour(), ['admin' => $admin->getKey(), 'hash' => sha1('taken@x.test')]);
    $this->get($url)->assertRedirect(route('admin.login'));

    expect($admin->fresh()->email)->toBe('old@x.test');   // fail-closed
})->group('security');

// ── isolation ──────────────────────────────────────────────────────────────────────────────────────
it('a tenant user cannot reach any account route', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s);

    $this->actingAs($operator)->get(route('admin.account.edit'))->assertRedirect(route('admin.login'));
    $this->actingAs($operator)->put(route('admin.account.password'), ['current_password' => 'x', 'password' => 'y'])->assertRedirect(route('admin.login'));
    $this->actingAs($operator)->put(route('admin.account.profile'), ['name' => 'Hax'])->assertRedirect(route('admin.login'));
})->group('security');
