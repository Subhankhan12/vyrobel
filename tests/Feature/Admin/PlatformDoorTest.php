<?php

declare(strict_types=1);

use App\Exceptions\PlatformWriteRefused;
use App\Models\Plan;
use App\Models\PlatformAdminAction;
use App\Models\Unit;
use App\Support\Owner\OwnerContext;
use App\Support\Platform\PlatformQuery;
use App\Support\Platform\PlatformWriteGuard;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ADMIN 01 · PHASE 1 — THE ONE DOOR, HARDENED: both refusal scopes lifted, read-only enforced, a reason on every
// write, and no scope bypass anywhere else in the admin realm. Each property below has a test that goes RED when
// the property is removed.

uses(DatabaseTransactions::class);

// ══ 1 · ⚠️⚠️ THE OWNER-SCOPE TRAP — proven real, then proven closed ═══════════════════════════════════════════

it('⚠️⚠️ signed in as BOTH an owner (web) and a platform admin: the door counts EVERY unit, in every condition — and the control shows the ONE condition the old door did not', function () {
    $s = seedScenario(3);
    $ownerA = ownUnit($s['units'][0]);
    ownUnit($s['units'][1]);
    // units[2] belongs to no owner at all

    $this->actingAs(ownerActor($ownerA, $s));            // the web guard: an OWNER-LINKED user
    $this->actingAs(platformAdmin(), 'admin');           // …and a platform admin, in the same "browser"

    $everyUnit = DB::table('units')->count();            // raw — no scope of any kind
    $door = fn (): int => app(PlatformQuery::class)->read('test.units', fn (): int => Unit::query()->count());
    // ⚠️ The door as it was BEFORE Phase 1: tenant scope lifted, owner scope NOT.
    $oldDoor = fn (): int => app(TenantContext::class)->runAsSuperAdmin(fn (): int => Unit::query()->count());
    $ctx = app(OwnerContext::class);

    // ── A · 🔬 the default guard is `admin` — what `auth:admin` does on EVERY real console request (it calls
    //    shouldUse() on the guard that admitted it). OwnerScope's actor is then the platform admin. Even the OLD
    //    door counted every unit here.
    Auth::shouldUse('admin');
    expect($oldDoor())->toBe($everyUnit)->and($door())->toBe($everyUnit);

    // ── B · 🔬 the default guard is `web` (the owner), but no owner context is active. STILL no narrowing: inside
    //    runAsSuperAdmin there is no tenant context, and OwnerContext::actorIsOwnerLinked() answers "not linked"
    //    without one. ⚠️ So the undercount Phase 0 predicted ("an admin also signed in as an owner is silently
    //    undercounted") does NOT happen on the HTTP path — measured here, and corrected in the phase report.
    Auth::shouldUse('web');
    $ctx->deactivate();
    expect($oldDoor())->toBe($everyUnit)->and($door())->toBe($everyUnit);

    // ── C · ⚠️⚠️ the ONE condition that DOES narrow: an owner context already ACTIVE when the door runs (a resolved
    //    owner read earlier in the same process — the singleton outlives a request in a long-running worker, and
    //    nothing deactivates it between jobs). THE CONTROL: the old door must reproduce the narrowing, or this
    //    test would prove nothing. The lift makes the door independent of it.
    $ctx->resolveForActor(Auth::guard('web')->user());
    expect($ctx->active())->toBeTrue();
    expect($oldDoor())->toBeLessThan($everyUnit, 'the control failed to reproduce the narrowing — this test would prove nothing');
    expect($door())->toBe($everyUnit);
})->group('security');

it('the owner-scope lift refuses to run for anyone but an authenticated platform admin', function () {
    expect(fn () => app(OwnerContext::class)->liftForPlatformConsole(fn () => 1))
        ->toThrow(RuntimeException::class, 'only to an authenticated platform admin');
})->group('security');

it('the lift is restored afterwards — it never outlives the door', function () {
    $this->actingAs(platformAdmin(), 'admin');
    $ctx = app(OwnerContext::class);

    $inside = app(PlatformQuery::class)->read('test.lift', fn (): bool => $ctx->liftedForPlatformConsole());

    expect($inside)->toBeTrue()->and($ctx->liftedForPlatformConsole())->toBeFalse();
})->group('security');

// ══ 2 · ⚠️⚠️ READ() IS READ-ONLY — enforced at the connection, not by convention ══════════════════════════════

it('⚠️⚠️ refuses an Eloquent write inside read() — and nothing is written', function () {
    $this->actingAs(platformAdmin(), 'admin');
    $plan = Plan::create(['code' => 'ro_'.Str::lower(Str::random(6)), 'name' => 'RO', 'price_minor' => 100, 'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => false]);

    expect(fn () => app(PlatformQuery::class)->read('test.write', fn () => $plan->update(['name' => 'CHANGED'])))
        ->toThrow(PlatformWriteRefused::class);

    expect($plan->fresh()->name)->toBe('RO');
})->group('security');

it('⚠️⚠️ refuses a QUERY-BUILDER write inside read() — the path a model event never sees', function () {
    $this->actingAs(platformAdmin(), 'admin');
    $before = DB::table('plans')->count();

    expect(fn () => app(PlatformQuery::class)->read('test.qb', fn () => DB::table('plans')->insert([
        'id' => (string) Str::ulid(), 'code' => 'qb_'.Str::lower(Str::random(6)), 'name' => 'QB', 'price_minor' => 1,
        'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => false, 'created_at' => now(), 'updated_at' => now(),
    ])))->toThrow(PlatformWriteRefused::class);

    expect(fn () => app(PlatformQuery::class)->read('test.mass', fn () => Plan::query()->where('id', 'none')->update(['name' => 'X'])))
        ->toThrow(PlatformWriteRefused::class);

    expect(fn () => app(PlatformQuery::class)->read('test.raw', fn () => DB::statement("UPDATE plans SET name = name WHERE id = 'none'")))
        ->toThrow(PlatformWriteRefused::class);

    expect(DB::table('plans')->count())->toBe($before);
})->group('security');

it('read() still reads, and a read-only section never leaks past the door', function () {
    $this->actingAs(platformAdmin(), 'admin');

    expect(app(PlatformQuery::class)->read('test.read', fn (): int => Plan::query()->count()))->toBeInt()
        ->and(PlatformWriteGuard::inReadOnly())->toBeFalse();
})->group('security');

it('the read-only rule is a pure function of the SQL — reads pass, every write verb is refused', function () {
    PlatformWriteGuard::readOnly(function (): void {
        foreach (['select 1', '  SELECT * FROM x', 'show tables', 'explain select 1', '/* c */ select 1', 'with a as (select 1) select * from a'] as $sql) {
            PlatformWriteGuard::inspect($sql);   // must not throw
        }
        foreach (['insert into x values (1)', 'update x set a=1', 'delete from x', 'replace into x values (1)', 'truncate x', 'alter table x add y int', 'drop table x', 'create table x (a int)', 'with a as (select 1) update x set y=1'] as $sql) {
            expect(fn () => PlatformWriteGuard::inspect($sql))->toThrow(PlatformWriteRefused::class);
        }
    });
})->group('security');

// ══ 3 · ⚠️⚠️ A WRITE NEEDS A REASON ═══════════════════════════════════════════════════════════════════════════

it('⚠️⚠️ mutate() refuses to run without a written reason — and audits nothing', function () {
    $this->actingAs(platformAdmin(), 'admin');
    $before = PlatformAdminAction::query()->where('action', 'test.no_reason')->count();
    $ran = false;

    expect(fn () => app(PlatformQuery::class)->mutate('test.no_reason', '   ', function () use (&$ran): void {
        $ran = true;
    }))->toThrow(InvalidArgumentException::class);

    expect($ran)->toBeFalse()
        ->and(PlatformAdminAction::query()->where('action', 'test.no_reason')->count())->toBe($before);
})->group('security');

it('mutate() records the reason on the audit row, against the acting admin', function () {
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin');

    app(PlatformQuery::class)->mutate('test.with_reason', 'Because the founder asked, in writing.', fn () => null);

    $row = PlatformAdminAction::query()->where('action', 'test.with_reason')->latest('created_at')->first();
    expect($row?->reason)->toBe('Because the founder asked, in writing.')
        ->and($row?->platform_admin_id)->toBe($admin->id);
})->group('security');

it('neither door opens outside the admin guard', function () {
    expect(fn () => app(PlatformQuery::class)->read('x', fn () => 1))->toThrow(RuntimeException::class)
        ->and(fn () => app(PlatformQuery::class)->mutate('x', 'a reason', fn () => 1))->toThrow(RuntimeException::class);
})->group('security');

// ══ 4 · ⚠️⚠️ NO SCOPE BYPASS ANYWHERE ELSE IN THE ADMIN REALM ═══════════════════════════════════════════════

it('⚠️⚠️ `withoutGlobalScope(s)` appears NOWHERE in the admin realm outside the door class — tokenised, comments skipped', function () {
    $roots = [app_path('Http/Controllers/Admin'), app_path('Support/Platform'), resource_path('views/admin'), resource_path('views/components/admin')];
    $door = realpath(app_path('Support/Platform/PlatformQuery.php'));
    $hits = [];

    foreach ($roots as $root) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php') || $file->getRealPath() === $door) {
                continue;
            }
            // ⚠️ Tokenised, never grepped — a grep reads its own needle in comments (this file's own words included).
            foreach (token_get_all((string) file_get_contents($file->getPathname())) as $t) {
                if (is_array($t) && $t[0] === T_STRING && in_array($t[1], ['withoutGlobalScope', 'withoutGlobalScopes'], true)) {
                    $hits[] = str_replace(base_path().'/', '', $file->getPathname()).':'.$t[2];
                }
            }
        }
    }

    expect($hits)->toBe([], 'a scope bypass outside the one door: '.implode(', ', $hits));
})->group('security');
