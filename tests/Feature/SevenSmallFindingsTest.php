<?php

use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Services\AssignmentService;
use App\Support\ErrorRealm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

// ── F-2 · F-3 · F-7 · F-8 · F-11 · F-12 · F-19 ───────────────────────────────────────────────────
// ⚠️ SEVEN FINDINGS, SEVEN TESTS. A single test covering several stays green when one of them
// regresses, so each is asserted independently and each was mutated on its own.
// ⚠️ Every assertion reads a RENDERED page or the REAL code — never "this markup exists in a Blade
// file", which is the shape that let three flash findings hide.

function svnActor(array $s): User
{
    return operatorUser($s, [], 'Hotel Manager');
}

it('⚠️ F-19 — the reservation-lifecycle comment now matches what terminate() actually does', function () {
    $routes = file_get_contents(base_path('routes/web.php'));
    $i = strpos($routes, "Route::post('/reservations/{reservation}/cancel'");
    expect($i)->toBeGreaterThan(0);
    $comment = substr($routes, max(0, $i - 1400), 1400);

    // ⚠️ THE CLAIM THAT WAS FALSE. Asserted as absent so it cannot silently return.
    expect($comment)->not->toContain('has NO status guard')
        ->and($comment)->not->toContain('recorded as outstanding');

    // ⚠️ AND THE CODE IT DESCRIBES — asserted for real, so the comment cannot drift back into fiction
    // without this failing. A comment test that only greps the comment proves nothing about the code.
    expect(AssignmentService::TERMINAL_STATUSES)->toContain('cancelled', 'no_show', 'checked_out');

    $service = file_get_contents(app_path('Services/AssignmentService.php'));
    expect($service)->toContain('throw new ReservationAlreadyTerminalException');

    // ⚠️ THE SIBLING. The identical false claim was written a SECOND time, in the controller's own
    // docblock — found by sweeping for the shape rather than the wording, as F-19 asked.
    $controller = file_get_contents(app_path('Http/Controllers/Operator/ReservationLifecycleController.php'));
    expect($controller)->not->toContain('has NO')
        ->and($controller)->not->toContain('does not throw');
});

it('⚠️ F-2 — the device-authorization grant is not offered at all', function () {
    // It was reachable UNAUTHENTICATED and threw a container BindingResolutionException → 500.
    // The honest fix is not to offer a feature this product never designed.
    foreach (['passport.device', 'passport.device.code', 'passport.device.authorizations.authorize'] as $name) {
        expect(Route::has($name))->toBeFalse("{$name} should not be registered");
    }

    // NON-VACUITY: the OAuth server this product DOES implement is untouched.
    expect(Route::has('passport.authorizations.authorize'))->toBeTrue()
        ->and(Route::has('passport.token'))->toBeTrue();
});

it('⚠️ F-3 — a signed-out stranger is not told to ask an administrator on their team', function () {
    $public = ErrorRealm::forbiddenMessage('public');

    expect($public)->not->toContain('administrator on your team')
        ->and($public)->not->toContain('Your role')
        ->and($public)->toContain('signing in');

    // ⚠️ ENUMERATION DISCIPLINE (Fix 06): one wording for every reason, so it cannot be used to tell an
    // expired link from a forged one, or a real page from an imaginary one.
    expect($public)->not->toContain('expired link')
        ->and($public)->not->toContain('does not exist');

    // The realms that legitimately HAVE a role keep the role wording.
    expect(ErrorRealm::forbiddenMessage('app'))->toContain('administrator on your team');
});

// ⚠️ THE PLATFORM-ADMIN CASE IS REPORTED, NOT FIXED. It looks like F-3, but F-3 is scoped to the two
// UNAUTHENTICATED doors, and branching the 'admin' realm contradicts a certified assertion that records
// what an earlier pass did (admin === app). See the note in ErrorRealm::forbiddenMessage().

it('⚠️ F-7 — both focused create screens render exactly one h1, RENDERED not asserted in source', function (string $route) {
    $s = seedScenario(1);
    $html = $this->actingAs(svnActor($s))->get(route($route))->assertOk()->getContent();

    expect(preg_match_all('~<h1[\s>]~', $html))->toBe(1, "expected exactly one h1 on {$route}");
})->with(['app.reservations.create', 'app.group-booking']);

it('⚠️ F-12 — the maintenance ticket detail renders exactly one h1', function () {
    $s = seedScenario(1);
    $t = MaintenanceTicket::create([
        'organization_id' => $s['org']->id, 'property_id' => $s['property']->id,
        'unit_id' => $s['units'][0]->id, 'title' => 'Leaking tap', 'status' => 'open',
        'priority' => 'normal', 'category' => 'plumbing', 'opened_at' => now(),
    ]);

    $html = $this->actingAs(svnActor($s))->get(route('app.maintenance.show', $t->id))->assertOk()->getContent();

    expect(preg_match_all('~<h1[\s>]~', $html))->toBe(1);
    // The ticket title is still a heading, just the right level — demoted, never deleted.
    expect($html)->toContain('Leaking tap');
});

it('⚠️ F-8 — every date input on the group-booking screen has an accessible name', function () {
    $s = seedScenario(1);
    $html = $this->actingAs(svnActor($s))->get(route('app.group-booking'))->assertOk()->getContent();

    preg_match_all('~<input[^>]+type="date"[^>]*>~', $html, $m);

    // ⚠️ THE AUDIT RECORDED TWO. There are THREE — the third sits behind x-show/x-cloak, which is why a
    // browser walk missed it. Non-vacuity: if the count drops, this test is measuring the wrong screen.
    expect(count($m[0]))->toBe(3);

    foreach ($m[0] as $tag) {
        $named = str_contains($tag, 'aria-label');
        if (preg_match('~id="([^"]+)"~', $tag, $im)) {
            $named = $named || str_contains($html, 'for="'.$im[1].'"');
        }
        expect($named)->toBeTrue("an unnamed date input remains: {$tag}");
    }
});

it('⚠️ F-11 — no navigation link is offered whose route the viewer cannot enter', function () {
    $shell = file_get_contents(resource_path('views/components/operator/shell.blade.php'));

    // ⚠️ FIXED WHERE THE FILTER LIVES, not per role: the nav drops any item whose route resolves to null,
    // so the gap was two items that never produced null. Both now check the permission their route enforces.
    preg_match_all("~\['([a-z0-9\-]+)',\s*'[^']+',\s*'[^']*',\s*(.+?)\],\n~", $shell, $m, PREG_SET_ORDER);

    $gaps = [];
    foreach ($m as $row) {
        [$all, $key, $expr] = $row;
        if (! preg_match("~route\('([a-z0-9_.\-]+)'~", $expr, $rm)) {
            continue;
        }
        $r = Route::getRoutes()->getByName($rm[1]);
        if ($r === null) {
            continue;
        }
        // ⚠️ `gatherMiddleware()` returns the UNRESOLVED alias (`can:x`); only `route:list` resolves it to
        // `Authorize:x`. A first version of this test looked only for `Authorize:` — so it found a
        // permission on NO route, flagged nothing, and stayed green with a guard deliberately removed.
        // Caught by mutation 8. Both spellings are accepted now.
        $needs = null;
        foreach ($r->gatherMiddleware() as $mw) {
            if (is_string($mw) && (str_starts_with($mw, 'can:') || str_contains($mw, 'Authorize:'))) {
                $needs = explode(':', $mw)[1];
            }
        }
        if ($needs !== null && ! str_contains($expr, "can('{$needs}')")) {
            $gaps[] = "{$key} (route needs {$needs})";
        }
    }

    expect($gaps)->toBe([], 'navigation links whose permission is not checked: '.implode(', ', $gaps));
    expect(count($m))->toBeGreaterThan(20);   // non-vacuity: the parse found the real nav

    // ⚠️ AND the scan must actually SEE permissions, or the loop above proves nothing.
    $withPerms = 0;
    foreach ($m as $row) {
        if (preg_match("~route\('([a-z0-9_.\-]+)'~", $row[2], $rm)) {
            $rr = Route::getRoutes()->getByName($rm[1]);
            if ($rr && array_filter($rr->gatherMiddleware(), fn ($x) => is_string($x) && str_starts_with($x, 'can:'))) {
                $withPerms++;
            }
        }
    }
    expect($withPerms)->toBeGreaterThan(10, 'the permission scan found nothing — it would pass vacuously');
});
