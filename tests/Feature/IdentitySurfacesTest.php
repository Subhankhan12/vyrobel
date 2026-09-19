<?php

use App\Http\Middleware\EnsureOwnerPortalAccess;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── F-23 · F-27 — TWO SURFACES THAT SAID THEY WERE YOURS WHEN THEY WERE NOT ──────────────────────
// ⚠️ NEITHER WAS A LEAK. The owner scope held completely: every figure €0.00, every list empty, the CSV
// two lines with a watermark naming the downloader. What was wrong is that the product RENDERED an
// owner's portal for someone it does not belong to. These tests assert ACCESS, not secrecy.

function isOwnerUser(array $s): User
{
    $u = User::factory()->create(['email' => 'fix08-owner@example.test', 'password' => 'password', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $u->id, 'status' => 'active']);
    Owner::factory()->create(['user_id' => $u->id, 'status' => 'active']);

    return $u;
}

// ⚠️⚠️ RULED BY THE FOUNDER, 2026-09-19 (R-GATE): WIRE IT, AND GATE BEFORE SCOPE. These four cases were
// SKIPPED from `702efe1f` until that ruling, and the skip reason was itself the finding:
// `EnsureOwnerPortalAccess` was written and mutation-proven, but wiring it turned 35 CERTIFIED TESTS RED
// across 29 files — 14 asserting the portal RENDERS EMPTY for a non-owner ("fail closed via the middleware":
// assertOk() plus "No units"/"No statements"), 9 asserting an owner-scoped 404 the gate pre-empts, and 7
// asserting that EVERY back-office role reaches /owner and sees no owner data.
//
// ⚠️⚠️ THAT WAS A DIRECT CONFLICT between a recorded audit finding and ~30 recorded decisions encoding the
// opposite as intended. Amending certified assertions was correctly refused as a founder's call. The founder
// has now made it: the refusal is the intended behaviour, so those assertions moved and these un-skipped.
//
// ⚠️ THE ORDERING IS PART OF THE RULING. Gate-before-scope means a non-owner is refused BEFORE any owner
// resolution runs — so the 403 discloses nothing about whether the resource behind it exists.
it('⚠️⚠️ F-23 — an OPERATOR is bounced from EVERY owner entrance, not just the dashboard', function (string $route) {
    $s = seedScenario(1);
    $operator = operatorUser($s, [], 'Hotel Manager');

    // ⚠️ THE WRONG-FIX MUTATION'S TARGET: gating one route leaves the others admitting. Each is asserted.
    $this->actingAs($operator)->get(route($route))
        ->assertRedirect(route('app.dashboard'));
})->with(['owner.dashboard', 'owner.balance', 'owner.payouts', 'owner.settings', 'owner.bookings', 'owner.documents']);

it('⚠️ F-23 — the bounce sends an operator somewhere USEFUL, mirroring invariant #26', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, [], 'Hotel Manager');

    // #26 sends an owner who lands on /app to their own home rather than a dead end. This is its mirror.
    $this->actingAs($operator)->get(route('owner.dashboard'))->assertRedirect(route('app.dashboard'));
    $this->actingAs($operator)->get(route('app.dashboard'))->assertOk();
});

it('⚠️ F-23 — a REAL OWNER still reaches every one of those screens', function (string $route) {
    $s = seedScenario(1);
    $owner = isOwnerUser($s);

    // NON-VACUITY: if the gate refused everyone, the test above would pass for the wrong reason.
    $this->actingAs($owner)->get(route($route))->assertOk();
})->with(['owner.dashboard', 'owner.balance', 'owner.payouts', 'owner.settings']);

it('⚠️ F-23 — the two routes that must serve NON-owners are deliberately NOT gated', function () {
    // A pending owner-team invitee is not `active`, so they are not owner-linked — gating the acceptance
    // route would make an invitation impossible to accept. The signed email-verify link is opened from a
    // NEW inbox, possibly with no session at all.
    $gated = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($r) => str_starts_with((string) $r->getName(), 'owner.'))
        ->partition(fn ($r) => in_array(EnsureOwnerPortalAccess::class, $r->gatherMiddleware(), true));

    expect($gated[0]->count())->toBeGreaterThan(100)
        ->and($gated[1]->pluck('action.as')->values()->all())
        ->toBe(['owner.team.invite.accept', 'owner.account.email.verify']);
});

it('⚠️⚠️ F-27 — a guest arriving from a booking link is NOT greeted as a returning user', function () {
    // The five email links all sit behind auth:guest, so an unclaimed guest is bounced here with the
    // booking URL held as the intended destination.
    $html = $this->withSession(['url.intended' => url('/guest/reservation/abc/manage')])
        ->get(route('guest.login'))->assertOk()->getContent();

    expect($html)->toContain('<h1 class="vy-title">Access your booking</h1>')
        ->and($html)->not->toContain('Welcome back')
        ->and($html)->toContain('Set up access to your booking');

    // ⚠️ ONE h1 — the guest realm is the cleanest surface in the product and must stay that way.
    expect(substr_count($html, '<h1'))->toBe(1);
});

it('⚠️ F-27 — a plain visit to the guest sign-in is UNCHANGED', function () {
    $html = $this->get(route('guest.login'))->assertOk()->getContent();

    // Someone who simply navigated here IS a returning user; the original greeting is right for them.
    // ⚠️ ASSERT THE HEADING, not a loose substring: the footer link has always read "Access your bookings",
    // so `not->toContain('Access your booking')` matched the footer and failed for the wrong reason.
    expect($html)->toContain('<h1 class="vy-title">Welcome back</h1>')
        ->and($html)->not->toContain('<h1 class="vy-title">Access your booking</h1>')
        ->and(substr_count($html, '<h1'))->toBe(1);
});

/**
 * ⚠️⚠️ R-GATE'S ORDERING, MADE OBSERVABLE. The founder ruled GATE BEFORE SCOPE, and an ordering nothing can
 * tell apart is an unproven ruling rather than a satisfied one — so this is the test that discriminates.
 *
 * ⚠️ THE RESPONSE IS IDENTICAL EITHER WAY: the gate's decision does not depend on the scope, so a refused
 * actor gets the same redirect whichever order the two middlewares run in. What differs is the WORK DONE FOR
 * SOMEONE WHO IS NOT AN OWNER. `SetOwnerScopeForPortal` calls `OwnerContext::resolveForActor()`, which runs
 * its own owner queries AND ACTIVATES the owner context. Gate-first means none of that happens: a non-owner
 * is turned away before the product does any owner resolution on their behalf.
 *
 * ⚠️ THAT IS THE POINT OF FAIL-CLOSED-EARLIEST, and it is why the ordering was ruled rather than left open.
 *
 * ⚠️ THE MUTATION: swap the two middlewares in routes/web.php → the refused request resolves the owner
 * context first and the owner-table query count rises → RED.
 */
it('⚠️⚠️ R-GATE — the gate runs BEFORE the scope: a refused actor costs NO owner resolution', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, [], 'Hotel Manager');

    $ownerQueries = 0;
    DB::listen(function ($q) use (&$ownerQueries): void {
        if (str_contains($q->sql, '`owners`') || str_contains($q->sql, '`owner_users`')) {
            $ownerQueries++;
        }
    });

    $this->actingAs($operator)->get(route('owner.dashboard'))->assertRedirect(route('app.dashboard'));

    // ⚠️ THE GATE'S OWN CHECK IS ALLOWED TO ASK (that IS the gate) — what must NOT happen is the SCOPE also
    //    resolving this actor's owners on the way to refusing them.
    expect($ownerQueries)->toBeLessThanOrEqual(2,
        'gate-before-scope: a non-owner must be refused before any owner resolution runs — got '.$ownerQueries.' owner queries');
});
