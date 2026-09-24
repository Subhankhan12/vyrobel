<?php

declare(strict_types=1);

use App\Http\Middleware\EnsurePlatformAdminTwoFactor;
use App\Http\Middleware\EnsurePlatformCapability;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformAdmin;
use App\Models\PlatformAnnouncement;
use App\Models\PrivacyRequest;
use App\Models\StatusIncident;
use App\Models\User;
use App\Models\UserIdentity;
use App\Support\Platform\PlatformAdminRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;

// ADMIN 01 · PHASE 1 — THE ROLE GATES. Owner (everything) · Support (tenants, support; no billing, no switches) ·
// Billing (revenue, plans; no support) · Analyst (read-only). ⚠️ Every console route carries a gate, and every
// gate is proved by a role that must be REFUSED by it — so removing any gate turns a row of this file red.

uses(DatabaseTransactions::class);

/** Every route behind the mandatory-2FA console gate — i.e. the console itself. */
function consoleRoutes(): array
{
    return collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn (Route $r): bool => in_array(EnsurePlatformAdminTwoFactor::class, $r->gatherMiddleware(), true))
        ->values()
        ->all();
}

/** The capability a route's gate names, or null if it has none. */
function routeCapability(Route $route): ?string
{
    foreach ($route->gatherMiddleware() as $mw) {
        if (is_string($mw) && str_starts_with($mw, EnsurePlatformCapability::class.':')) {
            return Str::after($mw, ':');
        }
    }

    return null;
}

/** Concrete URL for a console route — fixture ids for its parameters. */
function consoleUrl(Route $route, array $ids): string
{
    $params = [];
    foreach ($route->parameterNames() as $name) {
        $params[$name] = $ids[$name] ?? throw new RuntimeException("No fixture for route parameter [{$name}] on [{$route->getName()}] — add one.");
    }

    return route($route->getName(), $params);
}

function consoleFixtureIds(): array
{
    $org = Organization::create(['name' => 'Gate Fixture Org', 'slug' => 'gate-'.Str::lower(Str::random(8)), 'status' => 'active', 'default_currency' => 'EUR']);
    $plan = Plan::create(['code' => 'gate_'.Str::lower(Str::random(6)), 'name' => 'GATE', 'price_minor' => 100, 'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => false]);
    $other = platformAdmin(['role' => 'analyst']);
    $user = User::factory()->create(['email' => 'gate-'.Str::lower(Str::random(8)).'@example.net']);
    $identity = UserIdentity::create(['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => Str::random(12), 'provider_email' => $user->email]);

    $privacy = PrivacyRequest::create(['kind' => 'export', 'subject_kind' => 'relayed_guest', 'related_organization_id' => $org->id, 'channel' => 'hotel', 'status' => 'received', 'received_at' => now(), 'due_at' => now()->addMonth(), 'recorded_by' => $other->id]);
    $announcement = PlatformAnnouncement::create(['title' => 'Gate', 'body' => 'Gate', 'level' => 'info', 'audience' => 'chosen', 'organization_ids' => [], 'starts_at' => now()->addYear(), 'created_by' => $other->id]);
    $incident = StatusIncident::create(['title' => 'Gate', 'impact' => 'minor', 'status' => 'investigating', 'message' => 'Gate', 'started_at' => now(), 'created_by' => $other->id]);

    return ['organization' => $org->id, 'plan' => $plan->id, 'channel' => 'channex', 'switch' => 'signup.enabled', 'admin' => $other->id, 'user' => $user->id, 'identity' => $identity->id, 'privacyRequest' => $privacy->id, 'announcement' => $announcement->id, 'incident' => $incident->id];
}

// ══ 1 · STRUCTURE — no console route without a gate, no gate naming a capability that does not exist ══════════

it('⚠️⚠️ EVERY console route carries a role gate — a new route that forgets one fails here', function () {
    $routes = consoleRoutes();
    expect(count($routes))->toBeGreaterThan(30);   // the instrument must be seeing the console, not nothing

    $ungated = collect($routes)->filter(fn (Route $r): bool => routeCapability($r) === null)->map(fn (Route $r) => $r->getName())->values()->all();
    expect($ungated)->toBe([], 'console routes with NO role gate: '.implode(', ', $ungated));

    $unknown = collect($routes)->map(fn (Route $r) => routeCapability($r))->filter(fn ($c) => ! in_array($c, PlatformAdminRole::allCapabilities(), true))->values()->all();
    expect($unknown)->toBe([]);
})->group('security');

it('a gate naming a capability that does not exist throws instead of admitting', function () {
    $mw = new EnsurePlatformCapability;
    $this->actingAs(platformAdmin(), 'admin');

    expect(fn () => $mw->handle(request(), fn () => response('open'), 'console.typo'))
        ->toThrow(InvalidArgumentException::class);
})->group('security');

// ══ 2 · ⚠️⚠️ BEHAVIOUR — every gate refuses every role the matrix says it must refuse ═══════════════════════

it('⚠️⚠️ EVERY console route REFUSES (403) every role that lacks its capability — each gate proved by a refusal', function () {
    $ids = consoleFixtureIds();
    $checked = 0;

    foreach (consoleRoutes() as $route) {
        $capability = routeCapability($route);
        $method = strtolower(collect($route->methods())->first(fn ($m) => $m !== 'HEAD'));

        foreach (PlatformAdminRole::cases() as $role) {
            if ($role->allows($capability)) {
                continue;
            }

            $admin = platformAdmin(['role' => $role->value]);
            // A fresh session per actor: the session tracker (correctly) ends a session whose key belongs to
            // another admin, and that must not be mistaken for the ROLE gate refusing.
            $this->flushSession();
            $response = $this->actingAs($admin, 'admin')->call(strtoupper($method), consoleUrl($route, $ids), ['reason' => 'role-gate probe']);

            expect($response->status())->toBe(403, "{$role->value} reached [{$route->getName()}] ({$capability}) — got {$response->status()}");
            $checked++;
        }
    }

    // Owner can do everything, so only the other three roles produce refusals: the count proves the loop ran.
    expect($checked)->toBeGreaterThan(40);
})->group('security');

it('the Owner is admitted by every GET console page (the gates do not over-refuse)', function () {
    // ⚠️ Break-glass ships DORMANT (a 404 while off). Switched on here so its page is measured like every other —
    //    a dormant 404 is not a gate over-refusing, and leaving it off would exclude it from this check.
    config(['platform.break_glass.enabled' => true]);
    $ids = consoleFixtureIds();

    foreach (consoleRoutes() as $route) {
        if (! in_array('GET', $route->methods(), true)) {
            continue;
        }
        $this->flushSession();
        $status = $this->actingAs(platformAdmin(), 'admin')->get(consoleUrl($route, $ids))->status();
        expect($status)->toBe(200, "Owner got {$status} on [{$route->getName()}]");
    }
})->group('security');

it('⚠️ a NULL or UNKNOWN role can do NOTHING — not even look at the health board (fail closed)', function () {
    foreach ([null, 'superuser', ''] as $role) {
        $this->flushSession();
        $this->actingAs(platformAdmin(['role' => $role]), 'admin')->get(route('admin.dashboard'))->assertForbidden();
    }
})->group('security');

it('the matrix is exactly as ruled', function () {
    $can = fn (PlatformAdminRole $r) => $r->capabilities();

    expect($can(PlatformAdminRole::Owner))->toEqualCanonicalizing(PlatformAdminRole::allCapabilities())
        ->and(PlatformAdminRole::Support->allows('billing.manage'))->toBeFalse()
        ->and(PlatformAdminRole::Support->allows('switches.manage'))->toBeFalse()
        ->and(PlatformAdminRole::Support->allows('support.use'))->toBeTrue()
        ->and(PlatformAdminRole::Billing->allows('support.use'))->toBeFalse()
        ->and(PlatformAdminRole::Billing->allows('billing.manage'))->toBeTrue()
        ->and(collect($can(PlatformAdminRole::Analyst))->filter(fn ($c) => str_ends_with($c, '.manage') || $c === 'support.use')->all())->toBe([]);
})->group('security');

// ══ 3 · THE UI READS THE SAME MATRIX — a link never leads to a 403 ════════════════════════════════════════

it('the nav shows a role only what its gates admit (the same matrix as the routes)', function () {
    $this->flushSession();
    $this->actingAs(platformAdmin(['role' => 'support']), 'admin')->get(route('admin.dashboard'))
        ->assertOk()->assertDontSee(route('admin.admins.index'))->assertDontSee(route('admin.integrations.index'));

    $this->flushSession();
    $this->actingAs(platformAdmin(['role' => 'analyst']), 'admin')->get(route('admin.dashboard'))
        ->assertOk()->assertSee(route('admin.admins.index'))->assertSee(route('admin.integrations.index'));
})->group('security');

it('a disabled admin with a live session is refused on the next request — not left signed in', function () {
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->get(route('admin.dashboard'))->assertOk();

    $admin->forceFill(['status' => PlatformAdmin::STATUS_DISABLED])->save();

    $this->actingAs($admin->fresh(), 'admin')->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
})->group('security');
