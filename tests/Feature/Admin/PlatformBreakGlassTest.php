<?php

declare(strict_types=1);

use App\Exceptions\PlatformWriteRefused;
use App\Http\Middleware\EnterBreakGlassSession;
use App\Models\BreakGlassSession;
use App\Models\OperatorTaskState;
use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAction;
use App\Notifications\Operator\SupportAccessNotification;
use App\Services\NotificationPreferences;
use App\Support\Platform\BreakGlass;
use Database\Seeders\BillingPlanSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RoleCatalogSeeder;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\ViewException;
use PragmaRX\Google2FA\Google2FA;

// ⚠️⚠️ ADMIN 01 · PHASE 3 — BREAK-GLASS. Reason + step-up 2FA + a chosen organisation → a READ-ONLY, 30-minute,
// bannered, page-by-page-audited look at its back office, after which its team is told. Read-only TWICE, each layer
// proved alone. And it ships DORMANT — so the first test is that, switched off, it does not exist.

uses(DatabaseTransactions::class);

function bgReason(): string
{
    return 'Ticket 42 — the operator reports a booking missing from their list.';
}

function bgCode(PlatformAdmin $admin): string
{
    return (new Google2FA)->getCurrentOtp((string) $admin->two_factor_secret);
}

/** A disposable organisation with one verified Hotel Manager — the view break-glass would borrow. */
function bgWorld(): array
{
    $s = seedScenario(1);
    $s['manager'] = operatorUser($s);

    return $s;
}

function bgOn(): void
{
    config(['platform.break_glass.enabled' => true]);
}

/**
 * ⚠️ `actingAs($admin, 'admin')` leaves `admin` as this TEST PROCESS's default guard for every later request. A real
 * request boots with `web` as the default, so the admin is NOT authenticated at /app. Restore that before asserting
 * what an admin who is not (or no longer) in a support session gets at /app — otherwise the harness, not the
 * product, is what answers.
 */
function bgRealRequestGuard(): void
{
    app('auth')->shouldUse('web');
}

// ══ 0 · DORMANT ═══════════════════════════════════════════════════════════════════════════════════════════════

it('⚠️⚠️ SWITCHED OFF IT DOES NOT EXIST — every entry point 404s, no button, and a leftover key is ignored and cleared', function () {
    // ⚠️ Shipped ON since 2026-09-24 (ADMIN 03 #0 — the known default password stopped verifying on the host). The
    //    OFF switch still has to work — it is how break-glass is turned off again — so this proves it with it off.
    config(['platform.break_glass.enabled' => false]);
    $s = bgWorld();
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->get(route('admin.break-glass.index'))->assertNotFound();
    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()])->assertNotFound();
    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.end'))->assertNotFound();
    $this->actingAs($admin, 'admin')->get(route('admin.support.index'))->assertOk()->assertDontSee(route('admin.break-glass.index'));
    expect(BreakGlassSession::query()->where('target_organization_id', $s['org']->id)->count())->toBe(0);

    // A session opened while it was on, then switched off: the key is cleared and /app is NOT entered.
    bgOn();
    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()]);
    bgRealRequestGuard();
    config(['platform.break_glass.enabled' => false]);
    bgRealRequestGuard();
    $this->get('/app/dashboard')->assertRedirect(route('login'));
    expect(session()->has(BreakGlass::SESSION_KEY))->toBeFalse();
})->group('security');

// ══ 1 · WHAT IT REQUIRES ═══════════════════════════════════════════════════════════════════════════════════════

it('⚠️⚠️ it requires a FRESH 2FA code — a wrong one opens nothing, and the failed attempt is on the ledger', function () {
    bgOn();
    $s = bgWorld();
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => '000000', 'reason' => bgReason()]);

    expect(sessionErrorKeys())->toContain('code')
        ->and(BreakGlassSession::query()->where('target_organization_id', $s['org']->id)->count())->toBe(0)
        ->and(session()->has(BreakGlass::SESSION_KEY))->toBeFalse()
        ->and(PlatformAdminAction::query()->where('action', 'platform.break_glass.step_up_failed')->where('target_id', $s['org']->id)->exists())->toBeTrue();
})->group('security');

it('⚠️ it requires a written reason, and an organisation with a VERIFIED back-office user to view as', function () {
    bgOn();
    $s = bgWorld();
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin)]);
    expect(sessionErrorKeys())->toContain('reason');

    $s['manager']->forceFill(['email_verified_at' => null])->save();   // the only back-office user, now unverified
    $this->flushSession();
    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()]);
    bgRealRequestGuard();
    expect(sessionErrorKeys())->toContain('organization_id')
        ->and(BreakGlassSession::query()->where('target_organization_id', $s['org']->id)->count())->toBe(0);
})->group('security');

it('refuses to open while a REAL tenant user is signed in in the same browser — the two must never mix', function () {
    bgOn();
    $s = bgWorld();
    $admin = platformAdmin();

    $this->actingAs($s['manager'], 'web');
    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()]);
    bgRealRequestGuard();

    expect(sessionErrorKeys())->toContain('organization_id')->and(BreakGlassSession::query()->where('target_organization_id', $s['org']->id)->count())->toBe(0);
})->group('security');

// ══ 2 · WHAT IT GRANTS — and how it is SEEN ═══════════════════════════════════════════════════════════════════

it('⚠️⚠️ opens a 30-minute session into THAT organisation — bannered, audited page by page, never a login', function () {
    bgOn();
    $s = bgWorld();
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()])
        ->assertRedirect(route('app.dashboard'));
    bgRealRequestGuard();

    $session = BreakGlassSession::query()->where('target_organization_id', $s['org']->id)->sole();
    expect($session->viewing_user_id)->toBe($s['manager']->id)
        ->and($session->started_at->diffInMinutes($session->expires_at))->toEqual(30)
        ->and(PlatformAdminAction::query()->where('action', 'platform.break_glass.start')->where('target_id', $s['org']->id)->value('reason'))->toBe(bgReason());

    $this->get('/app/dashboard')->assertOk()
        ->assertSee('Vyrobel support session — read-only — ends at '.$session->expires_at->utc()->format('H:i').' UTC')
        ->assertSee('viewing as '.$s['manager']->name);
    $this->get('/app/reservations')->assertOk()->assertSee('data-break-glass-banner', false);

    expect($session->fresh()->pages_viewed)->toBe(2)
        ->and(PlatformAdminAction::query()->where('action', 'platform.break_glass.page')->where('target_id', $s['org']->id)->pluck('meta')->pluck('path')->all())
        ->toBe(['/app/dashboard', '/app/reservations']);

    // ⚠️ NEVER A LOGIN — the tenant user's view is borrowed per request; the web session holds no login.
    expect(session()->has(Auth::guard('web')->getName()))->toBeFalse();
})->group('security');

// ══ 3 · ⚠️⚠️ READ-ONLY — each layer proved on its own ═══════════════════════════════════════════════════════════

it('⚠️⚠️ LAYER 1 — every non-GET request during break-glass is REFUSED, and the refusal is audited', function () {
    bgOn();
    $s = bgWorld();
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()]);
    bgRealRequestGuard();
    $before = OperatorTaskState::query()->withoutGlobalScopes()->count();

    // ⚠️ LAYER 1's OWN page ("nothing can be changed") — not layer 2's ("tried to save something"), so this assertion
    //    cannot be satisfied by the connection guard standing in for a missing layer 1.
    $this->post(route('app.dashboard.tasks.toggle'), ['task_key' => OperatorTaskState::KEYS[0], 'ticked' => '1'])
        ->assertForbidden()->assertSee('nothing can be changed');
    $this->postJson(route('default-livewire.update'), ['components' => []])->assertForbidden();   // every component action
    $this->post('/owner/security/deactivate')->assertForbidden();

    expect(OperatorTaskState::query()->withoutGlobalScopes()->count())->toBe($before)
        ->and(PlatformAdminAction::query()->where('action', 'platform.break_glass.write_refused')->count())->toBeGreaterThanOrEqual(3);

    // …while the admin's OWN console still works — ending the session is a POST.
    $this->post(route('admin.break-glass.end'))->assertRedirect(route('admin.break-glass.index'));
})->group('security');

it('⚠️⚠️ LAYER 2 — a GET that tries to WRITE while being viewed is stopped at the connection', function () {
    bgOn();
    $s = bgWorld();
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()]);
    bgRealRequestGuard();

    $request = Request::create('/app/dashboard', 'GET');
    $request->setLaravelSession(app('session.store'));
    Auth::guard('admin')->setUser($admin);

    $before = OperatorTaskState::query()->withoutGlobalScopes()->count();
    expect(fn () => app(EnterBreakGlassSession::class)->handle($request, function () use ($s) {
        DB::table('operator_task_states')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $s['manager']->id,
            'scope_key' => 'all', 'task_key' => OperatorTaskState::KEYS[0], 'task_date' => '2026-08-01', 'created_at' => now(), 'updated_at' => now()]);

        return response('wrote');
    }))->toThrow(PlatformWriteRefused::class);
    expect(OperatorTaskState::query()->withoutGlobalScopes()->count())->toBe($before);

    // …and the refusal renders as a plain 403, not a 500 — bare, AND wrapped the way Laravel's Blade engine wraps an
    //    exception thrown from inside a template (a ViewException whose `previous` is the refusal).
    $request->attributes->set(BreakGlass::ATTRIBUTE, true);
    $bare = PlatformWriteRefused::readOnlyDoor('insert');
    $wrapped = new ViewException('while rendering a view', 0, 1, __FILE__, __LINE__, $bare);
    foreach ([$bare, $wrapped] as $refusal) {
        expect(app(ExceptionHandler::class)->render($request, $refusal)->getStatusCode())->toBe(403);
    }
})->group('security');

it('⚠️⚠️ TENANT ONE — break-glass may READ it, and a write attempt into it is REFUSED', function () {
    bgOn();
    $this->seed(RoleCatalogSeeder::class);
    $this->seed(BillingPlanSeeder::class);
    $this->seed(DemoSeeder::class);
    $org = Organization::query()->where('slug', 'vyrobel-demo')->firstOrFail();
    $admin = platformAdmin();
    $states = fn () => DB::table('operator_task_states')->where('organization_id', $org->id)->count();
    $before = $states();

    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $org->id, 'code' => bgCode($admin), 'reason' => bgReason()])
        ->assertRedirect(route('app.dashboard'));
    bgRealRequestGuard();
    $this->get('/app/dashboard')->assertOk()->assertSee('Vyrobel support session');

    $this->post(route('app.dashboard.tasks.toggle'), ['task_key' => OperatorTaskState::KEYS[0], 'ticked' => '1'])->assertForbidden();

    expect($states())->toBe($before);
})->group('security');

// ══ 4 · TIME-BOXED, ENDED, TOLD ═══════════════════════════════════════════════════════════════════════════════

it('⚠️⚠️ it ENDS ITSELF at 30 minutes (time travel) — refused on the next request, ended AT expiry, and the team is told', function () {
    bgOn();
    $s = bgWorld();
    $admin = platformAdmin(['name' => 'Ada Admin']);
    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()]);
    bgRealRequestGuard();
    $this->get('/app/dashboard')->assertOk();

    $this->travel(29)->minutes();
    $this->get('/app/dashboard')->assertOk();   // still inside the box

    $this->travel(2)->minutes();
    $this->get('/app/dashboard')->assertRedirect(route('admin.break-glass.index'));

    $session = BreakGlassSession::query()->where('target_organization_id', $s['org']->id)->sole();
    expect($session->ended_reason)->toBe('expired')
        ->and($session->ended_at->equalTo($session->expires_at))->toBeTrue()
        ->and($session->tenant_notified_at)->not->toBeNull()
        ->and(session()->has(BreakGlass::SESSION_KEY))->toBeFalse();

    $notice = DB::table('notifications')->where('notifiable_id', $s['manager']->id)->where('type', SupportAccessNotification::class)->sole();
    $data = json_decode((string) $notice->data, true);
    expect($data['title'])->toContain('Vyrobel support viewed your workspace')
        ->and($data['context'])->toContain('Ada Admin')->toContain(bgReason())->toContain('UTC');
    expect(PlatformAdminAction::query()->where('action', 'platform.break_glass.end')->where('target_id', $s['org']->id)->value('meta'))
        ->toMatchArray(['ended_reason' => 'expired']);
})->group('security');

it('ending it by hand closes it at once, tells the team, and leaves nobody signed in to /app', function () {
    bgOn();
    $s = bgWorld();
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()]);
    bgRealRequestGuard();

    $this->post(route('admin.break-glass.end'))->assertRedirect(route('admin.break-glass.index'));

    $session = BreakGlassSession::query()->where('target_organization_id', $s['org']->id)->sole();
    expect($session->ended_reason)->toBe('ended')->and($session->tenant_notified_at)->not->toBeNull();
    bgRealRequestGuard();
    $this->get('/app/dashboard')->assertRedirect(route('login'));
})->group('security');

it('the scheduled sweep ends and announces a session nobody touched again', function () {
    bgOn();
    $s = bgWorld();
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()]);
    bgRealRequestGuard();

    $this->travel(31)->minutes();
    $this->artisan('break-glass:expire')->assertSuccessful();

    $session = BreakGlassSession::query()->where('target_organization_id', $s['org']->id)->sole();
    expect($session->ended_reason)->toBe('expired')
        ->and(DB::table('notifications')->where('notifiable_id', $s['manager']->id)->where('type', SupportAccessNotification::class)->count())->toBe(1);
})->group('security');

it('the tenant notice is ALWAYS ON — an organisation cannot switch off being told', function () {
    expect(NotificationPreferences::isAlwaysOn('support_access'))->toBeTrue()
        ->and(NotificationPreferences::CATALOG['support_access']['audience'])->toBe('operator');
})->group('security');

it('a tampered key opens nothing — the session is bound to the browser that started it', function () {
    bgOn();
    $s = bgWorld();
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()]);
    bgRealRequestGuard();

    $stash = session(BreakGlass::SESSION_KEY);
    session([BreakGlass::SESSION_KEY => ['id' => $stash['id'], 'key' => str_repeat('x', 64)]]);

    $this->get('/app/dashboard')->assertRedirect(route('admin.break-glass.index'));
    expect(session()->has(BreakGlass::SESSION_KEY))->toBeFalse();
})->group('security');

it('a SUSPENDED organisation can still be looked at — suspension refuses its own people, not Vyrobel support', function () {
    bgOn();
    $s = bgWorld();
    $s['org']->forceFill(['status' => 'suspended'])->save();
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()]);
    bgRealRequestGuard();
    $this->get('/app/dashboard')->assertOk()->assertSee('Vyrobel support session');
})->group('security');

it('⚠️ every parameter-free /app page answers during break-glass — never a 5xx, and a write-on-view is the read-only page', function () {
    bgOn();
    $s = bgWorld();
    book($s);
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()]);
    bgRealRequestGuard();

    $paths = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($r) => in_array('GET', $r->methods(), true) && str_starts_with($r->uri(), 'app/') && ! str_contains($r->uri(), '{'))
        ->map(fn ($r) => '/'.$r->uri())->unique()->values();
    expect($paths->count())->toBeGreaterThan(40);   // the instrument sees the back office

    $broken = [];
    foreach ($paths as $path) {
        $response = $this->get($path);
        $refused = PlatformAdminAction::query()->where('action', 'platform.break_glass.write_refused')->where('meta->path', $path)->exists();

        // ⚠️ NEVER A 5xx. A page that tries to WRITE while being viewed (🔬 2026-09-24: the maintenance pages create
        //    their settings row on first view) must be the plain read-only 403, audited. Any other 403 is the borrowed
        //    user's own permission limit (a Hotel Manager here) — break-glass sees exactly what they see, no more.
        $ok = $response->getStatusCode() < 500
            && (! $refused || ($response->getStatusCode() === 403 && str_contains((string) $response->getContent(), 'Read-only support session')));
        if (! $ok) {
            $broken[] = "{$path} ({$response->getStatusCode()})";
        }
    }

    expect($broken)->toBe([]);
})->group('security');

it('⚠️⚠️ SESSIONS 01 — with the DATABASE session driver, a support session is filed under the ADMIN, never the viewed user, and writes no password hash into the web session', function () {
    config(['session.driver' => 'database']);   // production's driver from SESSIONS 01 on
    bgOn();
    $s = bgWorld();
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.break-glass.start'), ['organization_id' => $s['org']->id, 'code' => bgCode($admin), 'reason' => bgReason()])
        ->assertRedirect(route('app.dashboard'));
    bgRealRequestGuard();
    // ⚠️ `auth.driver` is a SINGLETON: the admin request above resolved it to the admin guard, and in this test process
    //    it would stay that way — so the row would be filed under the admin whether or not the product resets the
    //    guard (measured: the mutation that removes the reset stayed green). A real request resolves it fresh.
    app()->forgetInstance('auth.driver');

    $this->get('/app/dashboard')->assertOk()->assertSee('data-break-glass-banner', false);

    // The session row's `user_id` is whatever `auth.driver` (Guard::class) says when the session is SAVED, at the end of
    // the request — DatabaseSessionHandler::userId(). ⚠️ Asserted on that value, not on a row: this test process reuses
    // ONE session handler whose "row exists" flag turns the second request's write into an UPDATE of an id that has no
    // row, so no row is written here at all (measured) — a row count would pass with or without the fix.
    expect(app('auth.driver')->id())->toBe($admin->id)
        // …so the viewed hotel user's Security page can never list a session from a Vyrobel admin's browser…
        ->and(DB::table('sessions')->where('user_id', $s['manager']->id)->count())->toBe(0)
        // …and nothing about the borrowed user was stored in the session: it is not a login.
        ->and(session()->has('password_hash_web'))->toBeFalse();
})->group('security');
