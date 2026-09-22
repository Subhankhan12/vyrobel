<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;

/**
 * ⚠️⚠️ APPLY 04 · ITEM 3 — THE FOUR RECORDED OAUTH FACTS, PINNED SO THE DOCUMENT CANNOT GO STALE.
 *
 * ⚠️ These tests decide NOTHING. Two of the four are open founder rulings (`R-TOKEN-LIFE`,
 * `R-TOKEN-SURVIVES`) and this file must never be read as an endorsement of the values it asserts.
 *
 * ⚠️⚠️ WHAT THEY ARE FOR. `docs/OAUTH-DESIGN.md` §12 now states four measured facts, and a document that
 * states a measurement goes stale the moment the code moves — which is exactly the failure this pass
 * corrected in §4 of that same file, where *"the owner must actively Authorize"* had been false for as long
 * as re-authorization existed and nothing anywhere noticed. So each recorded fact is pinned HERE: if the
 * behaviour changes, this reddens and whoever changed it is told which document to correct in the same
 * commit. ⚠️ A red here is NOT a bug — it means a decision was made and the record must catch up.
 */
it('⚠️ pins the token lifetimes §12(b) records — access 15d / refresh 30d / personal 6mo', function () {
    // ⚠️ Read from Passport itself, not from the source line, so a move of the call still measures the value.
    // ⚠️⚠️ AND MEASURED BY APPLYING THE INTERVAL, NOT BY READING `->days`. 🔬 `Passport::tokensExpireIn()`
    //    returns a CarbonInterval built from `now()->addDays(15)`, so a few microseconds later its `->days`
    //    reads **14** (14d 23h 59m) and a `toBe(15)` pin fails for a reason that has nothing to do with the
    //    lifetime. Applying it to a fixed instant and measuring the elapsed days is the same fact, stably.
    $at = CarbonImmutable::parse('2026-01-01 00:00:00');

    $days = fn (DateInterval $i): int => (int) round(($at->add($i)->getTimestamp() - $at->getTimestamp()) / 86400);

    expect($days(Passport::tokensExpireIn()))->toBe(15, 'R-TOKEN-LIFE is OPEN — if this changed, update OAUTH-DESIGN.md §12(b) and the register')
        ->and($days(Passport::refreshTokensExpireIn()))->toBe(30)
        ->and($days(Passport::refreshTokensExpireIn()))->toBeGreaterThan($days(Passport::tokensExpireIn()),
            'the refresh window must outlive the access token, or refresh is pointless');

    // ⚠️⚠️ THE PERSONAL-ACCESS LIFETIME IS DELIBERATELY NOT PINNED, AND THAT IS A DECISION, NOT AN OMISSION.
    //    🔬 It is built as `now()->addMonths(6)`, so the stored interval carries whatever the month lengths
    //    were at BOOT — and the suite freezes the clock, so boot-now and test-now disagree and the interval
    //    lands a few days off whatever a fixed anchor predicts. A pin like that reddens on a calendar roll
    //    for no behavioural reason, which teaches the next reader to re-pin on reflex; the memory's rule is
    //    PIN WHAT MUST NEVER MOVE, DO NOT PIN WHAT LEGITIMATELY MOVES.
    //    ⚠️ It also does not matter here: 🔬 no personal-access client exists in this application (creating
    //    one was refused twice, deliberately — all access is owner-consented authorization-code), so the
    //    value configures a grant nothing can currently use. `R-TOKEN-LIFE` is about the other two.
});

it('⚠️⚠️ pins that the api guard carries NO subscription or organisation-state gate — §12(c)+(d)', function () {
    // ⚠️ THE MEASUREMENT IS THE ROUTER'S, NOT A FILE READ. A grep of routes/api.php would be satisfied by a
    //    comment; this asks the booted router what middleware the three routes actually run.
    $apiRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($r): bool => str_starts_with((string) $r->getName(), 'api.owner.'));

    expect($apiRoutes)->toHaveCount(3, 'the api surface is three routes — §6');

    $middleware = $apiRoutes->flatMap(fn ($r): array => $r->gatherMiddleware())
        ->map(fn ($m): string => is_string($m) ? $m : '')->unique()->values()->all();

    // ⚠️ Pinned as an ABSENCE, which is the whole point: the web back office carries this and the API does not.
    expect(collect($middleware)->contains(fn (string $m): bool => str_contains($m, 'EnsureSubscriptionActive')))
        ->toBeFalse('R-TOKEN-SURVIVES is OPEN — if a gate was added, update OAUTH-DESIGN.md §12(c)/(d) and the register');

    // ⚠️ POSITIVE CONTROL — without it the assertion above is equally green on an empty middleware list.
    expect(collect($middleware)->contains(fn (string $m): bool => str_contains($m, 'ResolveApiOwnerContext')))
        ->toBeTrue('the owner-context gate must still be on every api route');
});

it('⚠️ pins that the /app back office DOES carry the subscription gate — the contrast §12(c) rests on', function () {
    $appRoute = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($r): bool => $r->getName() === 'app.front-desk');

    expect($appRoute)->not->toBeNull()
        ->and(collect($appRoute->gatherMiddleware())->contains(fn ($m): bool => is_string($m) && str_contains($m, 'EnsureSubscriptionActive')))
        ->toBeTrue('§12(c) compares the API to THIS — if the web gate moved, the comparison in the doc is stale');
});
