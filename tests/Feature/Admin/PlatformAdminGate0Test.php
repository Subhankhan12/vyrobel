<?php

declare(strict_types=1);

use App\Exceptions\TenantContextMissingException;
use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAction;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Platform\PlatformQuery;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

// SUPER ADMIN · GATE 0 — the ISOLATION PROOFS. This is the point of Gate 0: prove the /admin realm is airtight
// BEFORE any feature is built on it. Every test here is a security assertion about the highest-privilege surface.

/** An enrolled (2FA-confirmed) platform admin with a KNOWN secret so we can generate live TOTP codes. */
function enrolledAdmin(string $email = 'root@vyrobel.test'): array
{
    $secret = (new Google2FA)->generateSecretKey();
    $admin = PlatformAdmin::factory()->create([
        'email' => $email,
        'two_factor_secret' => $secret,
        'two_factor_enabled' => true,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => [['code' => 'AAAAA-BBBBB', 'used_at' => null]],
    ]);

    return [$admin, $secret];
}

// ── 🔒 PROOF 1 — A TENANT USER CANNOT REACH /admin ─────────────────────────────────────────────────
it('refuses an authenticated OPERATOR at /admin — bounced to the admin login, never served', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s);

    $this->actingAs($operator)                       // authed on the WEB guard (a tenant user)
        ->get('/admin')
        ->assertRedirect(route('admin.login'));      // auth:admin is not satisfied → refused

    expect(auth()->guard('admin')->check())->toBeFalse();
})->group('security');

it('refuses an authenticated OWNER at /admin', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s);

    $this->actingAs($owner)->get('/admin')->assertRedirect(route('admin.login'));
})->group('security');

it('refuses an authenticated GUEST at /admin', function () {
    $s = seedScenario(1);

    $this->actingAs($s['guest'], 'guest')            // authed on the GUEST guard
        ->get('/admin')
        ->assertRedirect(route('admin.login'));
})->group('security');

it('a TENANT USER cannot authenticate into the admin guard — their credentials are not platform_admins', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['email' => 'staff@tenant.test']);
    // operatorUser uses the factory password 'password'.

    $this->post('/admin/login', ['email' => 'staff@tenant.test', 'password' => 'password'])
        ->assertSessionHasErrors('email');           // generic failure — no admin exists for that email

    expect(auth()->guard('admin')->check())->toBeFalse();
    expect(PlatformAdmin::query()->where('email', 'staff@tenant.test')->exists())->toBeFalse();
})->group('security');

// ── 🔒 PROOF 2 — A PLATFORM ADMIN IS NOT A TENANT USER ─────────────────────────────────────────────
it('a platform admin is not a tenant user and is REFUSED from the tenant realms', function () {
    [$admin] = enrolledAdmin();
    $this->actingAs($admin, 'admin');

    // The admin is resolved ONLY by the admin guard — never by the tenant web guard.
    expect(auth()->guard('admin')->check())->toBeTrue();
    expect(auth()->guard('web')->check())->toBeFalse();

    // Defense in depth: even presented to the /app pipeline, the back-office gate refuses a non-User (403) —
    // a PlatformAdmin can never be mistaken for staff. (In a real request the default guard stays `web`, so an
    // unauthenticated admin is instead redirected to the tenant login — either way, never served.)
    $this->get(route('app.dashboard'))->assertForbidden();

    // The owner portal never serves an admin either.
    expect($this->get(route('owner.dashboard'))->status())->not->toBe(200);
})->group('security');

it('the platform_admins table has NO organization_id — the admin sits above tenancy', function () {
    expect(Schema::hasColumn('platform_admins', 'organization_id'))->toBeFalse();
    expect(Schema::hasColumn('platform_admins', 'organization_id'))->toBeFalse();
    // And a platform admin is a different class from a tenant User.
    [$admin] = enrolledAdmin();
    expect($admin)->toBeInstanceOf(PlatformAdmin::class);
    expect($admin)->not->toBeInstanceOf(User::class);
})->group('security');

// The escalation-surface confirmation: there is no is_super_admin flag on users (nothing to migrate off).
it('there is NO is_super_admin / admin flag on the users table (the escalation surface never existed there)', function () {
    expect(Schema::hasColumn('users', 'is_super_admin'))->toBeFalse();
    expect(Schema::hasColumn('users', 'is_admin'))->toBeFalse();
})->group('security');

// ── 🔒 PROOF 3 — THE CROSS-TENANT BYPASS NEVER LEAKS ───────────────────────────────────────────────
it('PlatformQuery::read is ADMIN-GUARD-GATED — it refuses when no platform admin is authenticated', function () {
    // No admin authed → the deliberate bypass must not be triggerable.
    expect(fn () => app(PlatformQuery::class)->read('x', fn () => 1))
        ->toThrow(RuntimeException::class);
})->group('security');

it('after an admin cross-tenant read, the tenant scope is RESTORED — a later tenant query is still scoped', function () {
    [$admin] = enrolledAdmin();
    $this->actingAs($admin, 'admin');

    $tenant = app(TenantContext::class);
    expect($tenant->isSuperAdmin())->toBeFalse();     // baseline: not in a bypass

    $count = app(PlatformQuery::class)->read('platform.count_reservations', fn (): int => Reservation::query()->count());
    expect($count)->toBeInt();

    // The bypass was TEMPORARY: it is restored (finally) — we are NOT left in super-admin mode.
    expect($tenant->isSuperAdmin())->toBeFalse();
    expect($tenant->hasContext())->toBeFalse();

    // And the scope is genuinely active again: a tenant-scoped query with NO context throws (never left open).
    expect(fn () => Reservation::query()->count())
        ->toThrow(TenantContextMissingException::class);
})->group('security');

// ── 🔒 PROOF 4 — MANDATORY 2FA ─────────────────────────────────────────────────────────────────────
it('an admin WITHOUT 2FA is forced to enrol — cannot reach the console', function () {
    $admin = PlatformAdmin::factory()->create();      // no 2FA

    $this->actingAs($admin, 'admin')
        ->get('/admin')
        ->assertRedirect(route('admin.2fa.setup'));
})->group('security');

it('an admin WITH confirmed 2FA reaches the console', function () {
    [$admin] = enrolledAdmin();

    $this->actingAs($admin, 'admin')->get('/admin')->assertOk()->assertSee('Platform console');
})->group('security');

it('login with a correct password for an ENROLLED admin does NOT log in — it challenges for 2FA (mutation proof)', function () {
    [$admin] = enrolledAdmin('gate@vyrobel.test');    // factory password = 'password'

    $this->post('/admin/login', ['email' => 'gate@vyrobel.test', 'password' => 'password'])
        ->assertRedirect(route('admin.2fa.challenge'));

    // The password alone did NOT authenticate — the 2FA gate held.
    expect(auth()->guard('admin')->check())->toBeFalse();
})->group('security');

it('the full login → 2FA challenge → console path works with a valid TOTP', function () {
    [$admin, $secret] = enrolledAdmin('flow@vyrobel.test');

    $this->post('/admin/login', ['email' => 'flow@vyrobel.test', 'password' => 'password'])
        ->assertRedirect(route('admin.2fa.challenge'));

    $code = (new Google2FA)->getCurrentOtp($secret);
    $this->post('/admin/two-factor', ['code' => $code])->assertRedirect(route('admin.dashboard'));

    expect(auth()->guard('admin')->check())->toBeTrue();
})->group('security');

// ── 🔒 PROOF 5 — NO PUBLIC ADMIN SIGN-UP ───────────────────────────────────────────────────────────
it('there is NO public route to create a platform admin', function () {
    expect(Route::has('admin.register'))->toBeFalse();
    expect(Route::has('admin.signup'))->toBeFalse();

    // The ONLY creation path is the seeded command.
    $this->artisan('admin:create', ['--name' => 'Root', '--email' => 'seed@vyrobel.test'])
        ->expectsQuestion('Password (min 12 chars, mixed case + a number)', 'Sup3rSecret-Pass')
        ->expectsQuestion('Confirm password', 'Sup3rSecret-Pass')
        ->assertExitCode(0);

    expect(PlatformAdmin::query()->where('email', 'seed@vyrobel.test')->exists())->toBeTrue();
})->group('security');

// ── 🔒 PROOF 6 — ADMIN ACTIONS ARE AUDITED ─────────────────────────────────────────────────────────
it('the cross-tenant read on the console is AUDITED to the platform action ledger', function () {
    [$admin] = enrolledAdmin();

    $this->actingAs($admin, 'admin')->get('/admin')->assertOk();

    // The console's cross-tenant read is audited. (Build 1 · Gate 1 grew the Overview from a trivial org count
    // to the full metrics read — same audited PlatformQuery::read(), now under the accurate `platform.dashboard`
    // action. The isolation guarantee is unchanged: the read IS audited to the platform ledger.)
    $action = PlatformAdminAction::query()->where('action', 'platform.dashboard')->first();
    expect($action)->not->toBeNull();
    expect($action->platform_admin_id)->toBe($admin->id);
})->group('security');

// ── 🔒 noindex + robots ────────────────────────────────────────────────────────────────────────────
it('every /admin response is noindexed (header) and /admin is disallowed in robots.txt', function () {
    $this->get('/admin/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

    expect(file_get_contents(public_path('robots.txt')))->toContain('Disallow: /admin');
})->group('security');
