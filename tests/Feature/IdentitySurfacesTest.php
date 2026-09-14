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

// ⚠️⚠️ THE F-23 TESTS BELOW ARE SKIPPED, AND THE REASON IS THE FINDING.
// EnsureOwnerPortalAccess is written, tested and proven (3 mutations, all discriminating) — but wiring it
// onto the owner route group turned 35 CERTIFIED TESTS RED across 29 files, and they are not oversights:
// 14 assert the portal RENDERS EMPTY for a non-owner ("fail closed via the middleware" — assertOk() plus
// "No units"/"No statements"), 9 assert an owner-scoped 404 that the gate now pre-empts with 403, and 7
// assert that EVERY back-office role reaches /owner and sees no owner data.
// ⚠️ That is a DIRECT CONFLICT between a recorded audit finding (F-23) and ~30 recorded decisions that
// encode the opposite as intended behaviour. Amending 30 certified assertions is a founder's call, not a
// fix pass's. The gate is UNWIRED pending that ruling; re-enabling it is one line in routes/web.php.
it('⚠️⚠️ F-23 — an OPERATOR is bounced from EVERY owner entrance, not just the dashboard', function (string $route) {
    $s = seedScenario(1);
    $operator = operatorUser($s, [], 'Hotel Manager');

    // ⚠️ THE WRONG-FIX MUTATION'S TARGET: gating one route leaves the others admitting. Each is asserted.
    $this->actingAs($operator)->get(route($route))
        ->assertRedirect(route('app.dashboard'));
})->with(['owner.dashboard', 'owner.balance', 'owner.payouts', 'owner.settings', 'owner.bookings', 'owner.documents'])->skip('F-23 gate unwired pending a founder ruling — see the banner above');

it('⚠️ F-23 — the bounce sends an operator somewhere USEFUL, mirroring invariant #26', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, [], 'Hotel Manager');

    // #26 sends an owner who lands on /app to their own home rather than a dead end. This is its mirror.
    $this->actingAs($operator)->get(route('owner.dashboard'))->assertRedirect(route('app.dashboard'));
    $this->actingAs($operator)->get(route('app.dashboard'))->assertOk();
})->skip('F-23 gate unwired pending a founder ruling');

it('⚠️ F-23 — a REAL OWNER still reaches every one of those screens', function (string $route) {
    $s = seedScenario(1);
    $owner = isOwnerUser($s);

    // NON-VACUITY: if the gate refused everyone, the test above would pass for the wrong reason.
    $this->actingAs($owner)->get(route($route))->assertOk();
})->with(['owner.dashboard', 'owner.balance', 'owner.payouts', 'owner.settings'])->skip('F-23 gate unwired pending a founder ruling');

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
})->skip('F-23 gate unwired pending a founder ruling');

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
