<?php

declare(strict_types=1);

use App\Mail\PasswordResetMail;
use App\Models\PlatformAdminAction;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;

// ADMIN 01 · PHASE 3 — SUPPORT TOOLS for Vyrobel ACCOUNT HOLDERS: lookup · unlink (the product's own no-way-in rule) ·
// mark verified (read back) · resend verification / password reset (and say plainly when mail discards them).

uses(DatabaseTransactions::class);

function stReason(): string
{
    return 'Support ticket — confirmed by phone with the account holder.';
}

function stUser(array $attributes = []): User
{
    return User::factory()->create(array_merge(['email' => 'st-'.Str::lower(Str::random(10)).'@example.net', 'status' => 'active'], $attributes));
}

function stIdentity(User $user, string $provider): UserIdentity
{
    return UserIdentity::create(['user_id' => $user->id, 'provider' => $provider, 'provider_user_id' => Str::random(12), 'provider_email' => $user->email, 'last_used_at' => now()]);
}

it('⚠️ LOOKUP by email finds the account holder — and the ledger records WHO was found, never the address typed', function () {
    $user = stUser(['name' => 'Lookup Person']);
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->get(route('admin.support.index', ['email' => $user->email]))
        ->assertOk()->assertSee('Lookup Person')->assertSee('Sign-in providers');
    $this->actingAs($admin, 'admin')->get(route('admin.support.index', ['email' => 'nobody-'.Str::random(8).'@example.net']))
        ->assertOk()->assertSee('No account holder has that email address.');

    $rows = PlatformAdminAction::query()->where('platform_admin_id', $admin->id)->where('action', 'platform.support.lookup')->get();
    expect($rows)->toHaveCount(2)
        ->and(json_encode($rows->pluck('meta')->all()))->not->toContain($user->email)->not->toContain('@example.net');
})->group('security');

it('⚠️⚠️ UNLINK follows the PRODUCT\'S rule — refused when it is their only way in, allowed when something survives', function () {
    $admin = platformAdmin();

    // Only way in: no password, one provider → REFUSED, and the row survives.
    $stranded = stUser(['password' => null]);
    DB::table('users')->where('id', $stranded->id)->update(['password' => null]);
    $only = stIdentity($stranded->fresh(), 'google');
    $this->actingAs($admin, 'admin')->post(route('admin.support.unlink', [$stranded->id, $only->id]), ['reason' => stReason()]);
    expect(sessionErrorKeys())->toContain('identity')->and(UserIdentity::query()->whereKey($only->id)->exists())->toBeTrue();

    // No password, TWO providers → one may go.
    $second = stIdentity($stranded->fresh(), 'twitter');
    $this->flushSession();
    $this->actingAs($admin, 'admin')->post(route('admin.support.unlink', [$stranded->id, $second->id]), ['reason' => stReason()])->assertSessionHasNoErrors();
    expect(UserIdentity::query()->whereKey($second->id)->exists())->toBeFalse();

    // A password survives the unlink → allowed.
    $withPassword = stUser();
    $link = stIdentity($withPassword, 'google');
    $this->flushSession();
    $this->actingAs($admin, 'admin')->post(route('admin.support.unlink', [$withPassword->id, $link->id]), ['reason' => stReason()])->assertSessionHasNoErrors();
    expect(UserIdentity::query()->whereKey($link->id)->exists())->toBeFalse()
        ->and(PlatformAdminAction::query()->where('action', 'platform.support.unlink_identity')->where('target_id', $withPassword->id)->value('reason'))->toBe(stReason());
})->group('security');

it('⚠️⚠️ MARK VERIFIED writes the column and READS IT BACK — and refuses an already-verified account', function () {
    $user = stUser(['email_verified_at' => null]);
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.support.verify-email', $user->id), ['reason' => stReason()])
        ->assertSessionHasNoErrors()->assertSessionHas('admin_notice', fn (string $m) => str_contains($m, 'read back from the database'));
    expect(DB::table('users')->where('id', $user->id)->value('email_verified_at'))->not->toBeNull();

    $this->actingAs($admin, 'admin')->post(route('admin.support.verify-email', $user->id), ['reason' => stReason()]);
    expect(sessionErrorKeys())->toContain('verify');
})->group('security');

it('⚠️⚠️ while mail DISCARDS, resend and reset say "NOT DELIVERED" — never a silent success', function () {
    config(['mail.default' => 'log']);
    $user = stUser(['email_verified_at' => null]);
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->get(route('admin.support.index', ['user' => $user->id]))->assertSee('Mail is not being delivered.');

    $this->actingAs($admin, 'admin')->post(route('admin.support.resend-verification', $user->id), ['reason' => stReason()]);
    $errors = session('errors');
    $message = $errors instanceof ViewErrorBag ? $errors->first('mail') : ($errors['default']['messages']['mail'][0] ?? '');
    expect($message)->toStartWith('NOT DELIVERED — mail is “log”')->toContain('Nobody received anything');
    $this->actingAs($admin, 'admin')->post(route('admin.support.password-reset', $user->id), ['reason' => stReason()]);
    expect(sessionErrorKeys())->toContain('mail');

    expect(PlatformAdminAction::query()->where('action', 'platform.support.resend_verification')->where('target_id', $user->id)->value('meta'))
        ->toMatchArray(['mailer' => 'log', 'delivered' => false]);
})->group('security');

it('with a delivering mailer, resend and reset really send the product\'s own notifications', function () {
    config(['mail.default' => 'smtp']);
    Notification::fake();
    Mail::fake();   // ⚠️ the reset is a Mailable (User::sendPasswordResetNotification) — never let a test reach a real SMTP
    $user = stUser(['email_verified_at' => null]);
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.support.resend-verification', $user->id), ['reason' => stReason()])->assertSessionHasNoErrors();
    $this->actingAs($admin, 'admin')->post(route('admin.support.password-reset', $user->id), ['reason' => stReason()])->assertSessionHasNoErrors();

    Notification::assertSentTo($user, VerifyEmail::class);
    Mail::assertSent(PasswordResetMail::class, fn (PasswordResetMail $m) => $m->hasTo($user->email));
})->group('security');

it('the support tools are Owner and Support only — Billing and Analyst are refused, and the nav agrees', function () {
    $user = stUser();

    foreach (['billing', 'analyst'] as $role) {
        $this->flushSession();
        $this->actingAs(platformAdmin(['role' => $role]), 'admin')->get(route('admin.support.index'))->assertForbidden();
        $this->flushSession();
        $this->actingAs(platformAdmin(['role' => $role]), 'admin')->post(route('admin.support.verify-email', $user->id), ['reason' => stReason()])->assertForbidden();
    }
    $this->flushSession();
    $this->actingAs(platformAdmin(['role' => 'support']), 'admin')->get(route('admin.support.index'))->assertOk()->assertSee(route('admin.support.index'));
    $this->flushSession();
    $this->actingAs(platformAdmin(['role' => 'analyst']), 'admin')->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.support.index'));
})->group('security');
