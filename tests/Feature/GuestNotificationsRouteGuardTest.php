<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Assert;

uses(RefreshDatabase::class);

// GUEST NOTIFICATION ROUTES — THE GUARD PIN (guest notifications · GATE 1).
//
// ⚠️ WHY THIS EXISTS. The guest realm REUSES the shared NotificationController (§N.8f) instead of forking a
// guest one. That works because Laravel's `Authenticate` middleware calls `shouldUse($guard)` for the guard
// that succeeded, so inside an `auth:guest` route `$request->user()` resolves to the Guest.
//
// ⚠️⚠️ The cost of that reuse is an IMPLICIT: the controller learns WHO you are from the request's resolved
// guard, so the route's guard middleware is what makes the isolation correct. A guest notification route
// placed OUTSIDE `auth:guest` would resolve `$request->user()` on the `web` guard — handing a signed-in
// OPERATOR a guest's mark-read endpoint, silently. Nothing in the controller could detect that.
//
// So the implicit is pinned here as a checked invariant. This is a ROUTE-TABLE assertion on purpose: it reads
// what the application actually registered, not what a comment claims.

it('keeps every guest notification route behind the guest guard', function () {
    $names = ['guest.notifications.read', 'guest.notifications.read-all', 'guest.notifications.dismiss'];

    foreach ($names as $name) {
        $route = Route::getRoutes()->getByName($name);
        Assert::assertNotNull($route, "route {$name} is missing — the pin must not pass by finding nothing");

        // ⚠️ EXCLUSIONS COUNT. `gatherMiddleware()` returns what is DECLARED; a route can still opt out with
        // `->withoutMiddleware('auth:guest')`, which leaves the declaration intact and the guard gone. An
        // earlier version of this pin read only the declaration and stayed GREEN through exactly that
        // mutation — it asserted the label, not the protection.
        $middleware = $route->gatherMiddleware();
        $excluded = $route->excludedMiddleware() ?? [];

        Assert::assertContains('auth:guest', $middleware,
            "{$name} is NOT behind auth:guest — \$request->user() would resolve on the web guard and an "
            .'operator could reach a guest mark-read endpoint');
        Assert::assertNotContains('auth:guest', $excluded,
            "{$name} declares auth:guest but EXCLUDES it — the guard is off and the isolation is gone");
    }
});

it('keeps the operator/owner notification routes on the web guard, unchanged', function () {
    // The other side of the same boundary: reusing the controller for guests must not have moved these.
    foreach (['notifications.read', 'notifications.read-all', 'notifications.dismiss'] as $name) {
        $route = Route::getRoutes()->getByName($name);
        Assert::assertNotNull($route, "route {$name} is missing");

        $middleware = $route->gatherMiddleware();

        Assert::assertContains('auth', $middleware, "{$name} lost its auth middleware");
        Assert::assertNotContains('auth:guest', $middleware, "{$name} must not be on the guest guard");
    }
});
