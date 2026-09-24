<?php

declare(strict_types=1);

use App\Http\Middleware\RefuseSuspendedOrganization;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\PlatformAdminAction;
use App\Services\TenantProvisioningService;
use App\Support\Platform\PlatformQuery;
use App\Support\Platform\TenantLifecycle;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\BillingPlanSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RoleCatalogSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

// ADMIN 01 · PHASE 2 — THE TENANT LIFECYCLE. Suspend is REAL (staff + owners refused, storefront closed, no money
// touched), reactivate restores what was there, archive is a reversible soft delete of a suspended organisation,
// and the protected organisations refuse — the baseline outright, `confirm` without its name typed.

uses(DatabaseTransactions::class);

function tlReason(): string
{
    return 'Phase 2 lifecycle test — a written reason.';
}

/** A disposable organisation with one operator and one owner, both able to reach their realm before anything. */
function tlWorld(string $status = 'active'): array
{
    $s = seedScenario(1);
    $s['org']->forceFill(['status' => $status])->save();
    $s['operator'] = operatorUser($s);
    $s['owner'] = ownerRoleUser($s);

    return $s;
}

/** Every row the suspension must NOT touch, counted for one organisation. */
function tlMoneySnapshot(string $orgId): array
{
    $out = [];
    foreach (['reservations', 'reservation_units', 'folios', 'folio_items', 'payments', 'owner_ledger', 'availability'] as $table) {
        $out[$table] = DB::table($table)->where('organization_id', $orgId)->count();
    }
    $out['reservation_states'] = DB::table('reservations')->where('organization_id', $orgId)->orderBy('id')->pluck('status', 'id')->all();

    return $out;
}

// ══ 1 · SUSPEND IS REAL — the other side is refused ═══════════════════════════════════════════════════════════

it('⚠️⚠️ SUSPEND refuses the organisation\'s STAFF at /app and its OWNERS at /owner — with a plain explanation', function () {
    $s = tlWorld();

    // THE CONTROL: before the suspension both realms open.
    $this->actingAs($s['operator'], 'web')->get('/app/dashboard')->assertOk();
    $this->actingAs($s['owner'], 'web')->get('/owner')->assertOk();

    $this->actingAs(platformAdmin(), 'admin')
        ->post(route('admin.customers.suspend', $s['org']->id), ['reason' => tlReason(), 'current_password' => 'password'])
        ->assertRedirect(route('admin.customers.show', $s['org']->id))
        ->assertSessionHasNoErrors();

    expect($s['org']->fresh()->status)->toBe('suspended');

    foreach (['/app/dashboard', '/app/billing', '/app/reservations'] as $path) {
        $this->actingAs($s['operator'], 'web')->get($path)->assertForbidden()->assertSee('This workspace is suspended')->assertSee('Sign out');
    }
    $this->actingAs($s['owner'], 'web')->get('/owner')->assertForbidden()->assertSee('This workspace is suspended');
    $this->actingAs($s['operator'], 'web')->getJson('/app/dashboard')->assertForbidden()->assertJson(['message' => 'Access to this organisation’s Vyrobel workspace is suspended.']);
})->group('security');

it('a suspension reaches ONLY that organisation — another tenant\'s staff carry on', function () {
    $mine = tlWorld();
    $theirs = tlWorld();

    $this->actingAs(platformAdmin(), 'admin')
        ->post(route('admin.customers.suspend', $mine['org']->id), ['reason' => tlReason(), 'current_password' => 'password']);

    $this->actingAs($mine['operator'], 'web')->get('/app/dashboard')->assertForbidden();
    $this->actingAs($theirs['operator'], 'web')->get('/app/dashboard')->assertOk();
})->group('security');

it('⚠️⚠️ REACTIVATE restores full access — and the status it had BEFORE (a trialing hotel stays trialing)', function () {
    $s = tlWorld('trialing');
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.customers.suspend', $s['org']->id), ['reason' => tlReason(), 'current_password' => 'password']);
    $this->actingAs($s['operator'], 'web')->get('/app/dashboard')->assertForbidden();

    $this->actingAs($admin, 'admin')->post(route('admin.customers.reactivate', $s['org']->id), ['reason' => tlReason()])->assertSessionHasNoErrors();

    expect($s['org']->fresh()->status)->toBe('trialing')->and($s['org']->fresh()->suspended_from_status)->toBeNull();
    $this->actingAs($s['operator'], 'web')->get('/app/dashboard')->assertOk();
    $this->actingAs($s['owner'], 'web')->get('/owner')->assertOk();
})->group('security');

it('⚠️⚠️ SUSPENSION TOUCHES NO MONEY — no reservation, folio, payment, ledger or inventory row moves', function () {
    $s = tlWorld();
    $reservation = book($s);
    $before = tlMoneySnapshot((string) $s['org']->id);
    expect($before['reservations'])->toBe(1);   // the instrument sees the booking, not nothing

    $this->actingAs(platformAdmin(), 'admin')
        ->post(route('admin.customers.suspend', $s['org']->id), ['reason' => tlReason(), 'current_password' => 'password', 'acknowledge_guests' => '1'])
        ->assertSessionHasNoErrors();

    expect($s['org']->fresh()->status)->toBe('suspended')
        ->and(tlMoneySnapshot((string) $s['org']->id))->toBe($before)
        ->and($reservation->fresh()->status)->toBe($reservation->status);
})->group('security');

it('⚠️⚠️ the reservation WARNING — counted on the page, and a suspension without acknowledging it is refused', function () {
    $s = tlWorld();
    book($s);   // 2026-09-01 → 03: upcoming against the frozen clock
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->get(route('admin.customers.show', $s['org']->id))
        ->assertOk()->assertSee('guest stays in house now', false)->assertSee('I have seen that this hotel has 0 guest stays in house and 1 upcoming', false);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.customers.suspend', $s['org']->id), ['reason' => tlReason(), 'current_password' => 'password']);
    expect(sessionErrorKeys())->toContain('acknowledge_guests')
        ->and($s['org']->fresh()->status)->toBe('active');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.customers.suspend', $s['org']->id), ['reason' => tlReason(), 'current_password' => 'password', 'acknowledge_guests' => '1'])
        ->assertSessionHasNoErrors();
    expect($s['org']->fresh()->status)->toBe('suspended');

    $row = PlatformAdminAction::query()->where('action', 'platform.customer.suspend')->where('target_id', $s['org']->id)->latest('created_at')->first();
    expect($row->reason)->toBe(tlReason())->and($row->meta)->toMatchArray(['in_house' => 0, 'upcoming' => 1, 'from_status' => 'active']);
})->group('security');

it('suspend needs the admin\'s password — a wrong one changes nothing', function () {
    $s = tlWorld();

    $this->actingAs(platformAdmin(), 'admin')
        ->post(route('admin.customers.suspend', $s['org']->id), ['reason' => tlReason(), 'current_password' => 'not-it']);

    expect(sessionErrorKeys())->toContain('current_password')->and($s['org']->fresh()->status)->toBe('active');
})->group('security');

it('⚠️⚠️ the lifecycle is TENANTS.MANAGE — an Analyst or a Billing admin is refused every one of its writes', function () {
    $s = tlWorld('suspended');

    foreach (['admin.customers.suspend', 'admin.customers.reactivate', 'admin.customers.archive', 'admin.customers.restore', 'admin.customers.internal'] as $name) {
        foreach (['analyst', 'billing'] as $role) {
            $this->flushSession();
            $this->actingAs(platformAdmin(['role' => $role]), 'admin')
                ->post(route($name, $s['org']->id), ['reason' => tlReason(), 'current_password' => 'password'])
                ->assertForbidden();
        }
    }
    expect($s['org']->fresh()->status)->toBe('suspended')->and($s['org']->fresh()->trashed())->toBeFalse();

    // …and Support, whose job this is, is admitted (the gate does not over-refuse).
    $this->flushSession();
    $this->actingAs(platformAdmin(['role' => 'support']), 'admin')
        ->post(route('admin.customers.reactivate', $s['org']->id), ['reason' => tlReason()])->assertSessionHasNoErrors();
    expect($s['org']->fresh()->status)->toBe('active');
})->group('security');

// ══ 2 · EVERY DOOR — the structure a new route cannot forget ══════════════════════════════════════════════════

it('⚠️⚠️ EVERY /app and /owner route carries the suspension gate — and no guest or webhook route does', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes());
    $has = fn (Route $r): bool => in_array(RefuseSuspendedOrganization::class, $r->gatherMiddleware(), true);

    $realm = $routes->filter(fn (Route $r): bool => str_starts_with($r->uri(), 'app/') || str_starts_with($r->uri(), 'owner/') || $r->uri() === 'owner');
    expect($realm->count())->toBeGreaterThan(100);   // the instrument sees the realms
    $missing = $realm->reject($has)->map(fn (Route $r) => $r->uri())
        // ⚠️ The two owner routes registered OUTSIDE the owner group by design: a pending invitee and a signed
        //    email-change link. Neither is a working surface of the organisation.
        ->reject(fn (string $uri): bool => str_contains($uri, 'invite') || str_contains($uri, 'email/verify'))
        ->values()->all();
    expect($missing)->toBe([]);

    $mustNot = $routes->filter(fn (Route $r): bool => str_starts_with($r->uri(), 'guest') || str_starts_with($r->uri(), 'book/')
        || str_contains($r->uri(), 'webhook') || str_starts_with($r->uri(), 'admin'));
    expect($mustNot->count())->toBeGreaterThan(20)
        ->and($mustNot->filter($has)->map(fn (Route $r) => $r->uri())->values()->all())->toBe([]);

    // …and the owner API refuses too, after its owner context resolves the organisation.
    $api = $routes->filter(fn (Route $r): bool => str_starts_with($r->uri(), 'api/owner'));
    expect($api->count())->toBeGreaterThan(0)->and($api->reject($has)->count())->toBe(0);
})->group('security');

it('⚠️ a page opened BEFORE the suspension is refused on its next Livewire update (persistent middleware)', function () {
    expect(Livewire::getPersistentMiddleware())->toContain(RefuseSuspendedOrganization::class);
})->group('security');

it('the API answer is JSON 403, not a page', function () {
    $s = tlWorld();
    $s['org']->forceFill(['status' => 'suspended'])->save();
    app(TenantContext::class)->set((string) $s['org']->id);

    $response = (new RefuseSuspendedOrganization)->handle(Request::create('/api/owner/bookings'), fn () => response('open'));

    expect($response->getStatusCode())->toBe(403)
        ->and(json_decode((string) $response->getContent(), true))->toBe(['message' => 'Access to this organisation’s Vyrobel workspace is suspended.']);
})->group('security');

it('a person who ALSO belongs to a working organisation is offered it — and the switch is let through', function () {
    $s = tlWorld();
    $other = tlWorld();
    Membership::create(['organization_id' => $other['org']->id, 'user_id' => $s['operator']->id, 'status' => 'active']);
    grantBackOfficeRole($s['operator'], $other['org'], 'Hotel Manager');

    $s['org']->forceFill(['status' => 'suspended'])->save();

    $this->actingAs($s['operator'], 'web')->withSession(['active_organization_id' => (string) $s['org']->id])
        ->get('/app/dashboard')->assertForbidden()->assertSee('Switch to '.$other['org']->name);

    $this->actingAs($s['operator'], 'web')->post(route('app.workspace.switch'), ['organization_id' => (string) $other['org']->id])
        ->assertRedirect(route('app.dashboard'));
    $this->actingAs($s['operator'], 'web')->get('/app/dashboard')->assertOk();
})->group('security');

// ══ 3 · ARCHIVE — a reversible soft delete of a suspended organisation ═══════════════════════════════════════

it('⚠️⚠️ ARCHIVE: only once suspended · hidden from the directory · still refused · every row kept · restorable', function () {
    $s = tlWorld();
    $admin = platformAdmin();
    $url = route('admin.customers.archive', $s['org']->id);
    $counts = fn () => ['properties' => DB::table('properties')->where('organization_id', $s['org']->id)->count(), 'members' => DB::table('organization_user')->where('organization_id', $s['org']->id)->count()];
    $before = $counts();

    // An ACTIVE organisation cannot be archived — suspend first.
    $this->actingAs($admin, 'admin')->post($url, ['reason' => tlReason(), 'current_password' => 'password']);
    expect(sessionErrorKeys())->toContain('status')->and($s['org']->fresh()->trashed())->toBeFalse();

    $this->actingAs($admin, 'admin')->post(route('admin.customers.suspend', $s['org']->id), ['reason' => tlReason(), 'current_password' => 'password']);
    $this->actingAs($admin, 'admin')->post($url, ['reason' => tlReason(), 'current_password' => 'password'])->assertSessionHasNoErrors();

    $org = Organization::withTrashed()->find($s['org']->id);
    expect($org->trashed())->toBeTrue()->and($org->status)->toBe('suspended')->and($counts())->toBe($before);

    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['search' => $org->name]))->assertOk()->assertDontSee($org->slug);
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['search' => $org->name, 'status' => 'archived']))->assertOk()->assertSee($org->slug);
    $this->actingAs($admin, 'admin')->get(route('admin.customers.show', $org->id))->assertOk()->assertSee('Archived')->assertSee('Restore (stays suspended)');

    $this->actingAs($s['operator'], 'web')->get('/app/dashboard')->assertForbidden();
    $this->actingAs($s['owner'], 'web')->get('/owner')->assertForbidden();

    $this->actingAs($admin, 'admin')->post(route('admin.customers.restore', $org->id), ['reason' => tlReason()])->assertSessionHasNoErrors();
    expect($org->fresh()->trashed())->toBeFalse()->and($org->fresh()->status)->toBe('suspended');
    $this->actingAs($s['operator'], 'web')->get('/app/dashboard')->assertForbidden();   // still suspended, on purpose

    expect(PlatformAdminAction::query()->where('target_id', $org->id)->whereIn('action', ['platform.customer.archive', 'platform.customer.restore'])->count())->toBe(2);
})->group('security');

it('⚠️ an ARCHIVED organisation\'s people are refused on the archive ALONE — even if its status were active', function () {
    $s = tlWorld();
    $this->actingAs($s['operator'], 'web')->get('/app/dashboard')->assertOk();   // THE CONTROL

    Organization::query()->whereKey($s['org']->id)->update(['deleted_at' => now()]);   // trashed, status still active

    $this->actingAs($s['operator'], 'web')->get('/app/dashboard')->assertForbidden();
    $this->actingAs($s['owner'], 'web')->get('/owner')->assertForbidden();
})->group('security');

it('⚠️⚠️ an ARCHIVED organisation\'s storefront is closed — even if its status were not', function () {
    $s = publicPropertyScenario(units: 1);
    $slug = $s['property']->slug;

    $this->get("book/{$slug}")->assertOk();   // THE CONTROL: the door is open to begin with

    Organization::query()->whereKey($s['org']->id)->update(['deleted_at' => now()]);   // trashed, status still active

    $this->get("book/{$slug}")->assertNotFound()->assertSee('Online booking isn’t available here');
})->group('security');

it('⚠️ an archived organisation\'s slug is never handed to a new signup (the unique index would 500 it)', function () {
    $archived = Organization::create(['name' => 'Slug Clash Hotel', 'slug' => 'slug-clash-hotel', 'status' => 'suspended', 'default_currency' => 'EUR']);
    $archived->delete();

    $result = app(TenantProvisioningService::class)->provision(
        organization: ['name' => 'Slug Clash Hotel', 'currency' => 'EUR'],
        founder: ['name' => 'New Founder', 'email' => 'clash-'.Str::lower(Str::random(8)).'@example.net', 'password' => 'LongEnough+Passw0rd'],
        property: ['name' => 'Clash House', 'type' => 'hotel'],
    );

    expect($result['organization']->slug)->not->toBe('slug-clash-hotel')->toStartWith('slug-clash-hotel');
})->group('security');

it('⚠️⚠️ there is NO hard delete — no route, and no forceDelete anywhere in the console', function () {
    $names = collect(app('router')->getRoutes()->getRoutes())->map(fn (Route $r) => (string) $r->getName())
        ->filter(fn (string $n): bool => str_starts_with($n, 'admin.'))->values();

    expect($names->count())->toBeGreaterThan(40);   // the instrument sees the console's routes
    $deleteNamed = $names->filter(fn (string $n): bool => (bool) preg_match('/destroy|force|purge|prune/', $n))->values()->all();
    expect($deleteNamed)->toBe([]);

    foreach (['app/Http/Controllers/Admin', 'app/Support/Platform'] as $dir) {
        foreach (glob(base_path($dir).'/*.php') as $file) {
            $code = collect(token_get_all((string) file_get_contents($file)))
                ->reject(fn ($t) => is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true))
                ->map(fn ($t) => is_array($t) ? $t[1] : $t)->implode('');
            expect(str_contains($code, 'forceDelete'))->toBeFalse("{$file} calls forceDelete");
        }
    }
})->group('security');

// ══ 4 · PROTECTED ORGANISATIONS ════════════════════════════════════════════════════════════════════════════════

it('⚠️⚠️ TENANT ONE refuses EVERY console write — and each refusal is on the ledger', function () {
    $this->seed(RoleCatalogSeeder::class);
    $this->seed(BillingPlanSeeder::class);
    $this->seed(DemoSeeder::class);
    $org = Organization::query()->where('slug', 'vyrobel-demo')->firstOrFail();
    expect(TenantLifecycle::lockFor($org))->toBe('baseline');   // the seed carries the lock

    $row = fn () => (array) DB::table('organizations')->where('id', $org->id)->first();
    $subs = fn () => DB::table('subscriptions')->where('organization_id', $org->id)->get()->map(fn ($r) => (array) $r)->all();
    [$before, $subsBefore] = [$row(), $subs()];
    $plan = DB::table('plans')->where('is_active', true)->value('id');
    $admin = platformAdmin();

    $attempts = [
        'admin.customers.suspend' => ['current_password' => 'password', 'acknowledge_guests' => '1'],
        'admin.customers.reactivate' => [],
        'admin.customers.archive' => ['current_password' => 'password', 'acknowledge_guests' => '1'],
        'admin.customers.restore' => [],
        'admin.customers.internal' => ['internal' => '0'],
        'admin.customers.assign-plan' => ['plan_id' => $plan],
    ];
    foreach ($attempts as $name => $payload) {
        $this->flushSession();
        $this->actingAs($admin, 'admin')->post(route($name, $org->id), $payload + ['reason' => tlReason()]);
        expect(sessionErrorKeys())->toContain('lock');
    }

    expect($row())->toBe($before)->and($subs())->toBe($subsBefore);
    expect(PlatformAdminAction::query()->where('action', 'platform.customer.refused')->where('target_id', $org->id)->count())->toBe(count($attempts));

    $this->actingAs($admin, 'admin')->get(route('admin.customers.show', $org->id))
        ->assertOk()->assertSee('Protected baseline')->assertDontSee(route('admin.customers.suspend', $org->id));
})->group('security');

it('⚠️ the baseline guard\'s organisation is refused even with no lock in its row', function () {
    $s = tlWorld();
    config(['security.baseline_tenant_guard' => (string) $s['org']->id]);

    $this->actingAs(platformAdmin(), 'admin')
        ->post(route('admin.customers.suspend', $s['org']->id), ['reason' => tlReason(), 'current_password' => 'password']);

    expect(sessionErrorKeys())->toContain('lock')->and($s['org']->fresh()->status)->toBe('active');
})->group('security');

it('⚠️⚠️ a `confirm`-locked organisation needs its EXACT name typed — nothing less', function () {
    $s = tlWorld();
    $s['org']->forceFill(['lifecycle_lock' => 'confirm'])->save();
    $url = route('admin.customers.suspend', $s['org']->id);
    $admin = platformAdmin();

    foreach ([null, '', 'vyrobel internal', substr((string) $s['org']->name, 0, -1)] as $typed) {
        $this->flushSession();
        $this->actingAs($admin, 'admin')->post($url, array_filter(['reason' => tlReason(), 'current_password' => 'password', 'confirm_name' => $typed], fn ($v) => $v !== null));
        expect(sessionErrorKeys())->toContain('confirm_name');
        expect($s['org']->fresh()->status)->toBe('active');
    }

    $this->flushSession();
    $this->actingAs($admin, 'admin')->post($url, ['reason' => tlReason(), 'current_password' => 'password', 'confirm_name' => $s['org']->name])->assertSessionHasNoErrors();
    expect($s['org']->fresh()->status)->toBe('suspended');
})->group('security');

// ══ 5 · SIGNUP SOURCE, DIRECTORY, TRIAGE ══════════════════════════════════════════════════════════════════════

it('⚠️ the signup SOURCE is recorded from the signup — and anything else stays NULL ("Unknown"), never guessed', function () {
    $email = 'src-'.Str::lower(Str::random(8)).'@example.net';
    $this->post('/signup', ['org_name' => 'Source Probe Hotel', 'location' => 'Faro', 'property_type' => 'villa', 'currency' => 'EUR',
        'property_name' => 'Source Villas', 'name' => 'Ada', 'email' => $email, 'password' => 'Password123!', 'password_confirmation' => 'Password123!'])
        ->assertRedirect('/app/portfolio');
    $org = Organization::query()->whereIn('id', Membership::query()->whereIn('user_id', DB::table('users')->where('email', $email)->pluck('id'))->pluck('organization_id'))->firstOrFail();
    expect($org->signup_source)->toBe('email');

    $provision = function (?string $source): Organization {
        $r = app(TenantProvisioningService::class)->provision(
            organization: ['name' => 'Src '.Str::random(6), 'currency' => 'EUR'],
            founder: ['name' => 'F', 'email' => 'src-'.Str::lower(Str::random(10)).'@example.net', 'password' => 'LongEnough+Passw0rd'],
            property: ['name' => 'P', 'type' => 'hotel'],
            signupSource: $source,
        );

        return $r['organization']->fresh();
    };

    expect($provision('google')->signup_source)->toBe('google')
        ->and($provision('twitter')->signup_source)->toBe('twitter')
        ->and($provision(null)->signup_source)->toBeNull()
        ->and($provision('bogus')->signup_source)->toBeNull()
        ->and(TenantLifecycle::sourceLabel(null))->toBe('Unknown')
        ->and(TenantLifecycle::sourceLabel('twitter'))->toBe('X');
})->group('security');

it('the directory shows every column, filters by source and verified, and sorts by last sign-in', function () {
    $tag = 'Zdir'.Str::random(5);
    $a = tlWorld();
    $a['org']->forceFill(['name' => $tag.' Alpha', 'signup_source' => 'google', 'created_at' => now()->subDay()])->save();
    $a['operator']->forceFill(['last_login_at' => Carbon::parse('2026-07-30 10:00')])->save();
    $b = tlWorld();
    $b['org']->forceFill(['name' => $tag.' Beta'])->save();
    DB::table('users')->whereIn('id', DB::table('organization_user')->where('organization_id', $b['org']->id)->pluck('user_id'))->update(['email_verified_at' => null, 'last_login_at' => null]);
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['search' => $tag]))
        ->assertOk()->assertSee('Signed up with')->assertSee('Verified')->assertSee('Last sign-in')->assertSee('Google')->assertSee('Unknown')
        ->assertSeeInOrder([$tag.' Beta', $tag.' Alpha']);   // default: newest first

    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['search' => $tag, 'source' => 'google']))->assertSee($tag.' Alpha')->assertDontSee($tag.' Beta');
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['search' => $tag, 'source' => 'unknown']))->assertSee($tag.' Beta')->assertDontSee($tag.' Alpha');
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['search' => $tag, 'verified' => 'no']))->assertSee($tag.' Beta')->assertDontSee($tag.' Alpha');
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['search' => $tag, 'sort' => 'last_login', 'dir' => 'desc']))->assertSeeInOrder([$tag.' Alpha', $tag.' Beta']);
})->group('security');

it('⚠️ triage COUNTS by the same definition the directory filters by — internal left out unless asked', function () {
    $admin = platformAdmin();
    $count = function (bool $internal = false) use ($admin): array {
        $this->flushSession();
        $html = $this->actingAs($admin, 'admin')->get(route('admin.customers.triage', $internal ? ['internal' => 'include'] : []))->assertOk()->getContent();
        preg_match_all('/ad-bucket-count">([\d,]+)</', (string) $html, $m);

        return array_map(fn ($v) => (int) str_replace(',', '', $v), $m[1]);
    };
    // ⚠️ BOTH baselines are measured BEFORE the fixtures — the test database persists between runs.
    [$unverified0, $never0, $x0] = $count();
    [$unverifiedAll0, $neverAll0, $xAll0] = $count(true);

    $old = tlWorld();   // unverified, 10 days old, never signed in, via X
    $old['org']->forceFill(['signup_source' => 'twitter', 'created_at' => now()->subDays(10)])->save();
    DB::table('users')->whereIn('id', DB::table('organization_user')->where('organization_id', $old['org']->id)->pluck('user_id'))->update(['email_verified_at' => null, 'last_login_at' => null]);
    $young = tlWorld();   // unverified but only 2 days old → NOT in the 7-day bucket; never signed in
    $young['org']->forceFill(['created_at' => now()->subDays(2)])->save();
    DB::table('users')->whereIn('id', DB::table('organization_user')->where('organization_id', $young['org']->id)->pluck('user_id'))->update(['email_verified_at' => null, 'last_login_at' => null]);
    $internal = tlWorld();   // would be in every bucket — but it is Vyrobel's own
    $internal['org']->forceFill(['signup_source' => 'twitter', 'created_at' => now()->subDays(30), 'is_internal' => true])->save();
    DB::table('users')->whereIn('id', DB::table('organization_user')->where('organization_id', $internal['org']->id)->pluck('user_id'))->update(['email_verified_at' => null, 'last_login_at' => null]);

    expect($count())->toBe([$unverified0 + 1, $never0 + 2, $x0 + 1])
        ->and($count(true))->toBe([$unverifiedAll0 + 2, $neverAll0 + 3, $xAll0 + 2]);

    // …and the directory's bucket filter lists exactly the orgs the count counted.
    $this->flushSession();
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['bucket' => 'unverified', 'kind' => 'customers', 'search' => $old['org']->name]))
        ->assertSee($old['org']->slug);
    $this->actingAs($admin, 'admin')->get(route('admin.customers.index', ['bucket' => 'unverified', 'kind' => 'customers', 'search' => $young['org']->name]))
        ->assertDontSee($young['org']->slug);
})->group('security');

// ══ 6 · THE LEDGER STAYS HONEST ════════════════════════════════════════════════════════════════════════════════

it('an action that THROWS gets a second ledger row saying so — the ledger never claims what did not happen', function () {
    $this->actingAs(platformAdmin(), 'admin');

    expect(fn () => app(PlatformQuery::class)->mutate('platform.test.explodes', tlReason(), fn () => throw new RuntimeException('boom')))
        ->toThrow(RuntimeException::class, 'boom');

    expect(PlatformAdminAction::query()->where('action', 'platform.test.explodes')->count())->toBe(1)
        ->and(PlatformAdminAction::query()->where('action', 'platform.test.explodes.failed')->value('meta'))->toBe(['exception' => RuntimeException::class]);
})->group('security');
