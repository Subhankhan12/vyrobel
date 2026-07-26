<?php

declare(strict_types=1);

use App\Models\LoginActivity;
use App\Models\PlatformAdminAction;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

// SUPER ADMIN · BUILD 2 · GATE 1 — the auth security-signal capture (ADDED listeners; tenant flow unmodified),
// privacy retention, and the activity view.

// ── capture (via the ADDED listeners on the framework auth events) ─────────────────────────────────
it('captures a successful login (the Login event → login_activity)', function () {
    $user = User::factory()->create(['email' => 'staff@t.test', 'status' => 'active']);

    Auth::guard('web')->login($user);   // fires the framework Login event → RecordAuthActivity

    $row = LoginActivity::query()->where('event', 'login')->where('subject_id', $user->id)->first();
    expect($row)->not->toBeNull();
    expect($row->guard)->toBe('web');
    expect($row->identifier)->toBe('staff@t.test');
})->group('security');

it('NEVER persists a guest email in plaintext (#28) — the guest login is captured by id, no identifier', function () {
    $s = seedScenario(1);

    Auth::guard('guest')->login($s['guest']);

    $row = LoginActivity::query()->where('event', 'login')->where('guard', 'guest')->first();
    expect($row)->not->toBeNull();
    expect($row->subject_type)->toBe('guest');
    expect($row->identifier)->toBeNull();   // 🔒 guest email (encrypted PII) is never written plaintext
})->group('security');

it('captures a failed attempt (the Failed event → login_activity)', function () {
    event(new Failed('web', null, ['email' => 'attacker@probe.test', 'password' => 'secret']));

    $row = LoginActivity::query()->where('event', 'failed')->first();
    expect($row)->not->toBeNull();
    expect($row->identifier)->toBe('attacker@probe.test');   // the targeted account (a security signal)
    // The password is NEVER stored anywhere.
    expect(LoginActivity::query()->where('identifier', 'secret')->exists())->toBeFalse();
})->group('security');

it('captures a lockout, inferring the realm from the path', function () {
    event(new Lockout(Request::create('/admin/login', 'POST', ['email' => 'root@vyrobel.test'])));

    $row = LoginActivity::query()->where('event', 'lockout')->first();
    expect($row)->not->toBeNull();
    expect($row->guard)->toBe('admin');
})->group('security');

it('the ADMIN realm captures its OWN failed attempts end-to-end (dispatched Failed)', function () {
    platformAdmin(['email' => 'admin@x.test']);   // real admin exists; wrong password below

    $this->post('/admin/login', ['email' => 'admin@x.test', 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');

    expect(LoginActivity::query()->where('event', 'failed')->where('guard', 'admin')->where('identifier', 'admin@x.test')->exists())->toBeTrue();
})->group('security');

// ── 🔒 the tenant auth flow is UNMODIFIED (a real login still works + is captured) ──────────────────
it('a real successful login still works AND is captured (auth flow unmodified)', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['email' => 'realstaff@t.test']);

    $this->post('/login', ['email' => 'realstaff@t.test', 'password' => 'password'])
        ->assertRedirect();   // login succeeds exactly as before

    expect(Auth::check())->toBeTrue();
    expect(LoginActivity::query()->where('event', 'login')->where('subject_id', $operator->id)->exists())->toBeTrue();
})->group('security');

// ── 🔒 privacy retention ────────────────────────────────────────────────────────────────────────────
it('the retention prune purges rows older than the window (90 days) and keeps recent ones', function () {
    LoginActivity::create(['event' => 'login', 'occurred_at' => now()->subDays(120)]);   // old → purge
    LoginActivity::create(['event' => 'login', 'occurred_at' => now()->subDays(30)]);    // recent → keep

    $this->artisan('platform:prune-activity')->assertExitCode(0);

    expect(LoginActivity::query()->count())->toBe(1);
    expect(LoginActivity::query()->where('occurred_at', '<', now()->subDays(90))->exists())->toBeFalse();
})->group('security');

// ── the view ────────────────────────────────────────────────────────────────────────────────────────
it('the activity view renders real signals + AUDITS the cross-tenant account-events read', function () {
    LoginActivity::create(['event' => 'login', 'guard' => 'admin', 'identifier' => 'root@vyrobel.test', 'ip' => '203.0.113.9', 'occurred_at' => now()]);

    $this->actingAs(platformAdmin(), 'admin')
        ->get(route('admin.activity.index'))
        ->assertOk()
        ->assertSee('Security &amp; activity', false)
        ->assertSee('203.0.113.9')
        ->assertSee('90 days');   // the privacy posture surfaced

    expect(PlatformAdminAction::query()->where('action', 'platform.activity.audit_slice')->exists())->toBeTrue();
})->group('security');

it('a tenant user cannot reach the activity view', function () {
    $s = seedScenario(1);
    $this->actingAs(operatorUser($s))->get(route('admin.activity.index'))->assertRedirect(route('admin.login'));
})->group('security');
