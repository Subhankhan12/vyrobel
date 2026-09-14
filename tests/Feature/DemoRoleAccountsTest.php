<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Demo\DemoDataSafetyGuard;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlatformAdminSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;

/**
 * ⚠️⚠️ SEVEN OF THE ELEVEN BACK-OFFICE ROLES HAD NO DEMO ACCOUNT — INCLUDING FRONT DESK.
 *
 * The four that did — Property Owner, Hotel Manager, Accountant, Housekeeping — are the TOP and the BOTTOM
 * of the permission ladder. Every non-wildcard role is a strict subset of Group Admin, and Front Desk is a
 * strict subset of Hotel Manager, so auditing as Hotel Manager exercises everything Front Desk MAY do and
 * nothing Front Desk is DENIED. The middle of the ladder had never been driven, which is why the "19 ungated
 * screens" census was taken as Housekeeping, the narrowest role in the catalogue.
 *
 * ⚠️ `can()` RETURNS FALSE WITH NO TEAM SET — pinned by a test, and that shape has bitten three times. Every
 *    probe below sets the team and asserts the identity, because an account with no team context appears to
 *    hold NO permissions, and every audit using it would then be wrong in the same direction.
 */
uses(DatabaseTransactions::class);

/** The seven roles this pass gave accounts, with one thing each may do and one it may not. */
dataset('seeded_back_office_roles', [
    // role                    may                          may NOT
    'Front Desk' => ['Front Desk', 'reservations.checkin', 'reservations.cancel'],
    'Group Admin' => ['Group Admin', 'users.manage', 'owner_portal.view'],
    'Revenue Manager' => ['Revenue Manager', 'rates.manage', 'guests.view'],
    'Reservation Agent' => ['Reservation Agent', 'reservations.cancel', 'payments.record'],
    'Maintenance' => ['Maintenance', 'maintenance.manage', 'reservations.view'],
    'Marketing Manager' => ['Marketing Manager', 'reviews.manage', 'reservations.view'],
    'Guest Support Agent' => ['Guest Support Agent', 'reviews.manage', 'reservations.create'],
]);

function demoRoleActor(array $scenario, string $role): User
{
    $user = operatorUser($scenario, [], $role);

    // ⚠️ THE TEAM. Without it every `can()` below returns false and this whole file would "pass" by denying
    //    a user who genuinely holds the permission — the exact false-green this project has hit three times.
    app(PermissionRegistrar::class)->setPermissionsTeamId($scenario['org']->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');

    return $user;
}

// ══ #2 · ⚠️⚠️ THE SEEDER MUST BE UNABLE TO RUN OUTSIDE DEVELOPMENT ═══════════════════════════════════

it('⚠️⚠️ permits demo data ONLY in a named development environment — an allow-list, not a deny-list', function () {
    // ⚠️⚠️ THIS IS THE WHOLE FINDING. The previous check was `app()->isProduction()`, which compares APP_ENV
    //    to ONE EXACT STRING and therefore fails OPEN everywhere else. Driven against a scratch database
    //    before the fix, `APP_ENV=staging` seeded `admin@vyrobel-demo.test` with the password `password` —
    //    a platform super-admin that sits ABOVE tenancy.
    expect(DemoDataSafetyGuard::permits('local'))->toBeTrue()
        ->and(DemoDataSafetyGuard::permits('testing'))->toBeTrue();

    foreach (['production', 'staging', 'uat', 'qa', 'demo', 'preprod', 'producton', ''] as $environment) {
        expect(DemoDataSafetyGuard::permits($environment))->toBeFalse(
            "environment '{$environment}' must not be allowed to seed published credentials",
        );
    }
});

it('⚠️⚠️ THROWS when asked to seed outside development — the refusal, not merely the guard', function () {
    // ⚠️ Not "a guard exists" — that the seeding call itself refuses.
    expect(fn () => DemoDataSafetyGuard::ensurePermitted('staging', 'the demo organisation'))
        ->toThrow(RuntimeException::class);

    // …and the message names the environment and the reason, because a refusal nobody understands gets
    // "fixed" by removing it.
    try {
        DemoDataSafetyGuard::ensurePermitted('staging');
        $this->fail('expected a refusal');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain('staging')
            ->and($e->getMessage())->toContain('password')
            ->and($e->getMessage())->toContain('admin:create');
    }

    // ⚠️ BOTH DIRECTIONS: it runs in development. A guard proven only in the deny direction may be denying
    //    everything, which would break `migrate:fresh --seed`.
    DemoDataSafetyGuard::ensurePermitted('local');
    DemoDataSafetyGuard::ensurePermitted('testing');
    expect(true)->toBeTrue();
});

it('⚠️ guards every seeder that creates a credential, including the --class= path', function () {
    // ⚠️⚠️ `db:seed --class=PlatformAdminSeeder` NEVER RUNS DatabaseSeeder, so a guard only in DatabaseSeeder
    //    is no guard at all for that command — which is exactly how the hole was demonstrated.
    foreach ([
        'database/seeders/DatabaseSeeder.php',
        'database/seeders/PlatformAdminSeeder.php',
        'database/seeders/DemoSeeder.php',
        // ⚠️ FIX 17 — the second tenant's seeder creates accounts with the published password too.
        //    More accounts is more ways in, so it inherits the guard and this list grows with it.
        'database/seeders/SecondTenantSeeder.php',
    ] as $file) {
        $source = file_get_contents(base_path($file));
        expect($source)->toContain('DemoDataSafetyGuard::ensurePermitted');
    }

    // And the old fail-open shape is gone from all three.
    foreach ([DemoSeeder::class, PlatformAdminSeeder::class] as $seeder) {
        $source = file_get_contents((new ReflectionClass($seeder))->getFileName());
        expect(str_contains($source, 'isProduction()'))->toBeFalse(
            $seeder.' still carries the deny-list check that failed open on staging',
        );
    }
});

// ══ #5 · ⚠️ BOTH DIRECTIONS, PER ROLE ════════════════════════════════════════════════════════════════

it('gives each seeded role exactly what its catalogue entry says — and withholds the rest',
    function (string $role, string $may, string $mayNot) {
        $s = seedScenario(1);
        $user = demoRoleActor($s, $role);

        // ⚠️ ASSERT WHO WE ARE AND THAT THE TEAM IS SET, before believing any answer below.
        $this->actingAs($user);
        expect(auth()->id())->toBe((string) $user->getKey())
            ->and(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe((string) $s['org']->id)
            ->and($user->getRoleNames()->all())->toBe([$role]);

        // ⚠️ BOTH DIRECTIONS. An account proven only in the deny direction may be denied everything.
        expect($user->can($may))->toBeTrue("{$role} should hold {$may}")
            ->and($user->can($mayNot))->toBeFalse("{$role} must NOT hold {$mayNot}");
    })->with('seeded_back_office_roles');

it('⚠️ lets every seeded role through the back-office gate, and shows them no owner data', function (string $role) {
    $s = seedScenario(1);
    $user = demoRoleActor($s, $role);

    // Each reaches /app (EnsureBackOfficeAccess is a role allow-list, invariant #26) …
    $this->actingAs($user)->get('/app/dashboard')->assertOk();

    // ⚠️⚠️ … AND /owner ANSWERS 200, WHICH IS NOT WHAT I EXPECTED AND IS RECORDED AS MEASURED. The owner
    //    portal is not gated on `owner_portal.view` — none of these roles holds it — so what protects it is
    //    `SetOwnerScopeForPortal` resolving to no owner and every query returning nothing. The page renders
    //    an EMPTY SHELL: no owner name, no bank details, no statement figures.
    //
    // ⚠️ SO THIS IS DEFENCE BY SCOPE ALONE, and it is asserted here rather than assumed: the protection is
    //    that the data is not there, not that the door is shut. Reported for the outstanding phantom-
    //    permission ruling — `owner_portal.view` gates none of these routes — and deliberately NOT gated
    //    here, because gating it is the founder's call and would change who reaches what.
    expect($user->can('owner_portal.view'))->toBeFalse();

    $response = $this->actingAs($user)->get('/owner');
    expect($response->getStatusCode())->toBe(200);

    $body = $response->getContent();
    expect(str_contains($body, 'Demo Owner'))->toBeFalse('an operator must never see an owner name on /owner')
        ->and(preg_match('/[A-Z]{2}\d{2}[A-Z0-9]{10,}/', $body))->toBe(0, 'an operator must never see an IBAN');
})->with('seeded_back_office_roles');

// ══ #4 · ⚠️⚠️ FRONT DESK — USED, NOT MERELY SEEDED ═══════════════════════════════════════════════════

it('⚠️⚠️ re-measures the 19 ungated screens AS FRONT DESK — reads open, writes gated', function () {
    $s = seedScenario(1);
    $user = demoRoleActor($s, 'Front Desk');
    $this->actingAs($user);
    expect(auth()->id())->toBe((string) $user->getKey());

    // The parameterless members of the 19, taken from the route table rather than a hand-kept list.
    $ungated = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($r): bool => in_array('GET', $r->methods(), true))
        ->filter(fn ($r): bool => str_starts_with($r->uri(), 'app/'))
        ->reject(fn ($r): bool => str_contains($r->uri(), '{'))
        // ⚠️ BOTH SPELLINGS. In-process `gatherMiddleware()` returns the ALIAS `can:units.view`, while
        //    `route:list` prints the RESOLVED `Illuminate\Auth\Middleware\Authorize:units.view`. Matching
        //    only the resolved form counted 45 routes as ungated when there are 19 — my own measurement was
        //    wrong in the direction that would have overstated the finding.
        ->reject(fn ($r): bool => collect($r->gatherMiddleware())
            ->contains(fn ($m): bool => is_string($m)
                && (str_contains($m, 'Authorize:') || str_starts_with($m, 'can:'))))
        ->map(fn ($r): string => '/'.$r->uri())
        ->unique()->values();

    expect($ungated)->not->toBeEmpty();

    $open = [];
    foreach ($ungated as $path) {
        if ($this->get($path)->getStatusCode() === 200) {
            $open[] = $path;
        }
    }

    // ⚠️⚠️ THE MEASUREMENT, PINNED. Front Desk reads EVERY ungated screen — 15 parameterless of the 19 —
    //    exactly as Housekeeping did. ⚠️ THE NUMBER DOES NOT MOVE WITH THE ROLE, and that is the finding:
    //    these routes carry no permission at all, so there is nothing for a wider role to unlock and
    //    nothing for a narrower one to lose. Measuring the census as Housekeeping was not the limitation
    //    it looked like.
    expect($ungated)->toHaveCount(15)
        ->and($open)->toHaveCount($ungated->count());

    // ⚠️ AND THE WRITES ARE STILL GATED, which is what makes this a privacy question and not an integrity
    //    one. Front Desk genuinely lacks `reservations.cancel`.
    expect($user->can('reservations.cancel'))->toBeFalse();
});

it('⚠️⚠️ FRONT DESK CAN DO ITS OWN JOB — book, check in, and take a payment', function () {
    $s = seedScenario(1);
    $user = demoRoleActor($s, 'Front Desk');
    $this->actingAs($user);
    expect(auth()->id())->toBe((string) $user->getKey());

    // ⚠️ The three primary daily actions of the primary daily role. If a permission blocked any of these it
    //    would be a finding for the founder, NOT something to grant here.
    expect($user->can('reservations.create'))->toBeTrue()   // take a booking
        ->and($user->can('reservations.checkin'))->toBeTrue()   // check someone in
        ->and($user->can('reservations.checkout'))->toBeTrue()
        ->and($user->can('payments.record'))->toBeTrue()        // take a payment at the desk
        ->and($user->can('folios.manage'))->toBeTrue()          // post the charge it pays for
        ->and($user->can('invoices.issue'))->toBeTrue();        // hand over a receipt

    // ⚠️⚠️ AND THE SHAPE THE PERMISSION PASS WARNED ABOUT — the matrix promising access the ROUTE refuses.
    //    Checked against the REAL routes, not the catalogue: a held permission must actually open its door.
    //
    // ⚠️ THIS BLOCK WAS VACUOUS ON ITS FIRST DRAFT AND THAT IS RECORDED, because it is the exact failure
    //    this project keeps catching. It read `$s['reservations'][0] ?? null` and guarded both assertions
    //    with `if ($reservation !== null)` — but `seedScenario()` returns no `reservations` key at all, so
    //    the value was ALWAYS null and NEITHER assertion ever ran. The test passed while checking nothing.
    //    A real booking is made here instead, and the assertions are unconditional.
    $reservation = book($s);
    expect($reservation->exists)->toBeTrue();   // the fixture is real, so the two probes below cannot skip

    // A permission-gated write it HOLDS must not answer 403 — the door opens for a permission it has.
    $response = $this->post(route('app.reservations.payment', $reservation->id), []);
    expect($response->getStatusCode())->not->toBe(403);

    // …and one it does NOT hold answers 403, so the gate is real in both directions.
    $this->post(route('app.reservations.cancel', $reservation->id), ['reason' => 'x'])->assertForbidden();
});

// ══ #1 · ⚠️ THE CLASS, NOT THE INSTANCE — every back-office role must have a login ═══════════════════

it('⚠️⚠️ every BACK-OFFICE role in the catalogue has a demo account in the seeder', function () {
    // ⚠️ THE POINT OF GUARDING THE CLASS: seven roles went unrepresented for the whole life of this project
    //    and no test noticed, because nothing asserted the relationship between the catalogue and the
    //    seeder. Add a fifteenth back-office role tomorrow and this fails until it gets a login.
    $backOffice = (array) config('authorization.back_office_roles', []);
    expect($backOffice)->not->toBeEmpty();

    $seeder = file_get_contents(base_path('database/seeders/DemoSeeder.php'));

    $missing = array_values(array_filter(
        $backOffice,
        fn (string $role): bool => ! str_contains($seeder, "'".$role."'"),
    ));

    expect($missing)->toBe([], 'back-office roles with no demo account: '.implode(', ', $missing));
});

it('⚠️ and the two roles WITHOUT an account are deliberate, not forgotten', function () {
    $all = array_keys((array) config('authorization.roles', []));
    $backOffice = (array) config('authorization.back_office_roles', []);

    // ⚠️ `Super Admin` is a GLOBAL cross-tenant role. Phase 7a stopped assigning it to any founder (RBAC
    //    defect #2) and the platform realm is a separate model and guard, so seeding a User with it would
    //    rebuild the exact credential that was removed. ⚠️ `Guest` belongs to the guest realm, which
    //    authenticates against `Guest`, not `User` — a User holding it reaches nothing.
    $deliberatelyUnseeded = ['Super Admin', 'Guest'];

    // Neither is a back-office role, which is WHY neither needs an /app login — asserted from the config
    // rather than asserted as a comment.
    foreach ($deliberatelyUnseeded as $role) {
        expect($all)->toContain($role)
            ->and($backOffice)->not->toContain($role);
    }

    // And `External Owner` is represented but is not back-office either: it lands on /owner.
    expect($all)->toContain('External Owner')
        ->and($backOffice)->not->toContain('External Owner');
});
