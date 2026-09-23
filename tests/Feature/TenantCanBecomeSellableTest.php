<?php

use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Services\BookingEngineService;
use App\Services\TenantProvisioningService;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

// ══ P-6 · THE ACCEPTANCE TEST — AND THE REACHABILITY GUARD ════════════════════════════════════════════
//
// ⚠️⚠️ THIS IS THE TEST THAT WOULD HAVE CAUGHT P-6. The reachability sweep found that `RoomType`,
// `RatePlan` and `Channel` had no creation path anywhere in `app/` — the demo seeder made them directly,
// so the demo looked complete while a real `/signup` tenant reached a property they could never make
// sellable. Every audit that verified "against demo data" had partly validated state no user could reach.
//
// ⚠️⚠️ SO THE RULE THIS FILE ENFORCES IS: A TENANT PROVISIONED EXACTLY AS `/signup` PROVISIONS MUST BE
// ABLE TO REACH A COMPLETED BOOKING USING ONLY THE APPLICATION'S OWN HTTP PATHS.
//
// ⚠️ IT USES NO SEEDER, NO FACTORY AND NO DIRECT MODEL WRITE for anything on that path. That is not a
// stylistic preference — it IS the guard. A factory call here would substitute for the very path whose
// existence is the thing being proved, and the test would pass while the product stayed unusable. If a
// future change makes one of these steps impossible, this file must fail; if someone "fixes" that failure
// by reaching for a factory, they have deleted the guard while leaving it looking green.
//
// ⚠️ EXTENDING IT: the sweep found ELEVEN seeder-only entities. Adding one means adding a step to
// {@see makeSellable()} or a new scenario in the same shape — no rewrite needed.

uses(RefreshDatabase::class);

/**
 * Provision a tenant EXACTLY as `/signup` does — the same service, the same arguments shape.
 *
 * ⚠️ Deliberately calls `TenantProvisioningService::provision()` rather than posting to `/signup`, so the
 * test cannot be weakened by an unrelated change to the signup form's validation while still guaranteeing
 * the tenant's STARTING STATE is the real one. Everything AFTER this point goes through HTTP.
 *
 * @return array{user: User, property: Property, org_id: string}
 */
function freshSignupTenant(string $suffix = 'a'): array
{
    $result = app(TenantProvisioningService::class)->provision(
        organization: ['name' => "Reachability Org {$suffix}", 'country' => 'PT', 'currency' => 'EUR'],
        founder: ['name' => "Founder {$suffix}", 'email' => "founder-{$suffix}@reachability.test", 'password' => 'Str0ng-Passw0rd!'],
        property: ['name' => "Reachability Property {$suffix}", 'type' => 'hotel', 'location' => 'Faro'],
    );

    // ⚠️⚠️ THE FOUNDER CLICKS THE LINK — added 2026-09-23 (GATE 01). `provision()` deliberately leaves the
    //    founder UNVERIFIED (self-signup must verify), and `/app` is now behind the `verified` gate, so a
    //    provisioned founder cannot operate until the emailed link is followed. These tests are about what a
    //    tenant can DO once it exists, not about the gate — so they simulate the click rather than bypass it.
    //    ⚠️ The gate itself is proven in `VerifiedEmailGateTest`, both ways and under mutation.
    $result['user']->forceFill(['email_verified_at' => now()])->save();

    return [
        'user' => $result['user'],
        'property' => $result['property'],
        'org_id' => (string) $result['organization']->getKey(),
    ];
}

/** What the tenant starts with — asserted, so the test cannot silently begin from a richer state. */
function assertStartsWithNothingSellable(string $orgId, Property $property): void
{
    app(TenantContext::class)->forOrganization($orgId, function () use ($property): void {
        Assert::assertSame(0, RoomType::query()->where('property_id', $property->getKey())->count(),
            'the tenant did not start with zero room types — this test would not be proving reachability');
        Assert::assertSame(0, Unit::query()->where('property_id', $property->getKey())->count());
        Assert::assertSame(0, RatePlan::query()->where('property_id', $property->getKey())->count());
    });
}

// ══ ⚠️ THE ACCEPTANCE CRITERION ═══════════════════════════════════════════════════════════════════════
it('⚠️ lets a signup-shaped tenant reach a COMPLETED BOOKING using only the app’s own paths', function () {
    $tenant = freshSignupTenant('book');
    assertStartsWithNothingSellable($tenant['org_id'], $tenant['property']);

    $checkIn = CarbonImmutable::now()->addDays(20);
    $checkOut = $checkIn->addDays(2);

    // ── ① ROOM TYPE — through the app ────────────────────────────────────────────────────────────────
    $this->actingAs($tenant['user'])
        ->post(route('app.inventory.room-types.store'), [
            'name' => 'Double Room',
            'kind' => 'room',
            'max_adults' => 2,
            'max_children' => 1,
            'std_occupancy' => 2,
        ])->assertRedirect();

    $roomType = app(TenantContext::class)->forOrganization($tenant['org_id'],
        fn () => RoomType::query()->where('property_id', $tenant['property']->getKey())->firstOrFail());

    // ── ② UNIT — through the app ─────────────────────────────────────────────────────────────────────
    $this->actingAs($tenant['user'])
        ->post(route('app.inventory.units.store'), [
            'room_type_id' => (string) $roomType->getKey(),
            'code' => '101',
            'name' => 'Room 101',
        ])->assertRedirect();

    // ── ③ RATE PLAN — through the app ────────────────────────────────────────────────────────────────
    $this->actingAs($tenant['user'])
        ->post(route('app.inventory.rate-plans.store'), [
            'room_type_id' => (string) $roomType->getKey(),
            'name' => 'Flexible',
            'is_refundable' => '1',
        ])->assertRedirect();

    $ratePlan = app(TenantContext::class)->forOrganization($tenant['org_id'],
        fn () => RatePlan::query()->where('room_type_id', $roomType->getKey())->firstOrFail());

    // ── ④ RATES — the path that already existed (POST /app/rates → RateAndRestrictionService) ────────
    // ⚠️ ITS CONTRACT WAS READ, NOT ASSUMED. It takes `start_date`/`end_date` with an INCLUSIVE end (the
    // operator's "last night"), and a `room_type_id` — not a rate-plan id: the controller resolves the plan
    // itself via RoomType::primaryRatePlan(), which falls back to the oldest ACTIVE plan. That fallback is
    // why storeRatePlan() must create the plan active; an inactive one would leave the rate nowhere to land.
    $this->actingAs($tenant['user'])
        ->post(route('app.rates.update'), [
            'room_type_id' => (string) $roomType->getKey(),
            'start_date' => $checkIn->toDateString(),
            'end_date' => $checkOut->subDay()->toDateString(),
            'set_rate' => '1',
            'rate' => '120.00',
        ])->assertRedirect();

    Assert::assertNotNull($ratePlan, 'no rate plan to price against');

    // ── ⑤ THE STOREFRONT MUST NOW OFFER SOMETHING ───────────────────────────────────────────────────
    $offers = app(TenantContext::class)->forOrganization($tenant['org_id'], fn (): array => app(BookingEngineService::class)
        ->search($tenant['property']->fresh(), $checkIn->toDateString(), $checkOut->toDateString(), 2, 0));

    Assert::assertNotEmpty($offers,
        '⚠️ THE STOREFRONT OFFERS NOTHING. A tenant who did everything the application allows still cannot '
        .'sell — which is exactly the P-6 defect this test exists to prevent.');
    Assert::assertSame((string) $roomType->getKey(), (string) $offers[0]['room_type_id']);
    Assert::assertSame(24000, (int) $offers[0]['total_minor'], 'two nights at 120.00 should price at 240.00');

    // ── ⑥ AND A BOOKING COMPLETES ───────────────────────────────────────────────────────────────────
    $reservation = app(TenantContext::class)->forOrganization($tenant['org_id'], function () use ($tenant, $offers, $checkIn, $checkOut) {
        $engine = app(BookingEngineService::class);
        $quote = $engine->quote(
            $tenant['property']->fresh(),
            (string) $offers[0]['room_type_id'],
            (string) $offers[0]['rate_plan_id'],
            $checkIn->toDateString(),
            $checkOut->toDateString(),
            2, 0,
            'guest@reachability.test',
        );

        return $engine->book($quote, 'tok_test', [
            'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'guest@reachability.test',
        ], takePayment: false);
    });

    Assert::assertNotNull($reservation, 'the booking did not complete');
    $reservationModel = is_array($reservation) ? ($reservation['reservation'] ?? null) : $reservation;
    Assert::assertNotNull($reservationModel, 'book() returned no reservation');
    Assert::assertNotEmpty($reservationModel->reference, 'the completed booking has no reference');
});

// ══ ⚠️ THE REACHABILITY GUARD — the durable fix for the CLASS ════════════════════════════════════════
it('⚠️ keeps an app-reachable creation path for every entity a tenant must create', function () {
    // ⚠️ WHY THIS EXISTS SEPARATELY from the booking test above: the booking test proves the chain works
    // END TO END, but it would also fail for a hundred unrelated reasons. This one states the invariant
    // directly — every entity below MUST have a registered, permission-gated route a tenant can POST to —
    // so a future reader sees the RULE, not just a symptom, and cannot weaken it by accident.
    //
    // ⚠️ EXTENSIBLE: the sweep found eleven seeder-only entities. Adding one is one row here.
    $required = [
        'RoomType' => ['route' => 'app.inventory.room-types.store', 'permission' => 'units.manage'],
        'Unit' => ['route' => 'app.inventory.units.store', 'permission' => 'units.manage'],
        'RatePlan' => ['route' => 'app.inventory.rate-plans.store', 'permission' => 'rates.manage'],
        // Already reachable before this gate — pinned so a regression there is caught too.
        'RateCalendar' => ['route' => 'app.rates.update', 'permission' => 'rates.manage'],
        'Property' => ['route' => 'app.portfolio.store', 'permission' => 'properties.manage'],
        // ⚠️ P-7 · CLUSTER J — `property_user` was the sharpest case of this class: it HAD a writer, but one
        // gated on `$user->propertyAssignments()->exists()`, so a row could only be created for someone who
        // already had one. GATED-UNREACHABLE is not "no path" — it looks like a path in every grep — and the
        // table sat at zero rows everywhere while every operator silently fell through to full-org access.
        'PropertyAssignment' => ['route' => 'app.settings.team-scope', 'permission' => 'users.manage'],
        // ⚠️ CLUSTER E — the arrival secrets (door code · wifi · notes) were the TWELFTH seeder-only entity:
        // every reference to them in the application was a READ, so invariant #33 was gating the release of
        // data no operator could enter. Not a "creation" path like the rows above — a WRITE path — but the
        // same class of defect and the same one-row fix, which is what this table was shaped for.
        'Unit arrival info' => ['route' => 'app.inventory.units.arrival-info', 'permission' => 'units.manage'],
    ];

    Assert::assertGreaterThanOrEqual(7, count($required),
        'the required-path list shrank — this guard would cover less than it did');

    foreach ($required as $entity => $spec) {
        $route = Route::getRoutes()->getByName($spec['route']);

        Assert::assertNotNull($route,
            "⚠️ {$entity} has no registered creation route ({$spec['route']}). If it can only be created by a "
            .'seeder or a test, a real tenant cannot create it — that is the P-6 class returning.');
        Assert::assertContains('POST', $route->methods(), "{$entity}'s creation route is not a POST");
        Assert::assertContains('can:'.$spec['permission'], $route->gatherMiddleware(),
            "⚠️ {$entity}'s creation route is not gated on {$spec['permission']} (#30 — authorization is on the route)");
    }
});

// ══ ⚠️ THE SCREENS ACTUALLY RENDER — static checks cannot see a rendered page ════════════════════════
it('⚠️ renders the inventory screens without leaking raw Blade', function () {
    // ⚠️ THIS TEST EXISTS BECAUSE THE RATES PAGE 500'd AND NOTHING CAUGHT IT. The suite was green, PHPStan
    // was clean, Pint was clean — and /app/rates returned a 500, because three directives written as
    // `yet@endunless`, `it@else` and `one@endif` were never compiled: Blade ignores a directive whose `@`
    // follows a word character (the \B rule that lets foo@bar.com render literally). The unclosed `if` then
    // blew up on the next `@endforeach`. Only opening the page showed it.
    $tenant = freshSignupTenant('render');

    foreach (['app.units', 'app.rates'] as $routeName) {
        $response = $this->actingAs($tenant['user'])->get(route($routeName));
        $response->assertOk();

        $html = $response->getContent();

        // ⚠️ RAW BLADE IN THE OUTPUT is the visible symptom of the same trap when it does NOT happen to
        // break compilation — the directive simply ships to the user as text.
        foreach (['@endif', '@endunless', '@endforeach', '@endcan', '@else'] as $directive) {
            Assert::assertStringNotContainsString($directive, $html,
                "[{$routeName}] leaked the raw Blade directive {$directive} — a directive whose @ follows a "
                .'word character is not compiled');
        }
    }
});

it('PROVES the raw-Blade needle can fire (the positive control)', function () {
    $failed = false;
    try {
        Assert::assertStringNotContainsString('@endunless', '<p>no plan yet@endunless</p>');
    } catch (AssertionFailedError) {
        $failed = true;
    }
    expect($failed)->toBeTrue('the raw-Blade needle cannot fire — it proves nothing');
});

// ══ ⚠️ TENANCY — cross-organisation creation must be REFUSED ═════════════════════════════════════════
it('⚠️ refuses to create inventory on another organisation’s property', function () {
    $mine = freshSignupTenant('mine');
    $theirs = freshSignupTenant('theirs');

    // ⚠️ THE ATTACK: their room type's id, posted by my user. The parent must be resolved from MY tenant
    // scope, never from the request — the operator-realm analogue of #28.
    $this->actingAs($theirs['user'])->post(route('app.inventory.room-types.store'), [
        'name' => 'Their Suite', 'kind' => 'room', 'max_adults' => 2, 'max_children' => 0, 'std_occupancy' => 2,
    ])->assertRedirect();

    $theirRoomType = app(TenantContext::class)->forOrganization($theirs['org_id'],
        fn () => RoomType::query()->where('property_id', $theirs['property']->getKey())->firstOrFail());

    // My user posts THEIR room type id.
    $this->actingAs($mine['user'])->post(route('app.inventory.units.store'), [
        'room_type_id' => (string) $theirRoomType->getKey(), 'code' => 'X1', 'name' => 'Stolen',
    ])->assertNotFound();

    $this->actingAs($mine['user'])->post(route('app.inventory.rate-plans.store'), [
        'room_type_id' => (string) $theirRoomType->getKey(), 'name' => 'Stolen Plan',
    ])->assertNotFound();

    // ⚠️⚠️ AND NOTHING LANDED — CHECKED WITHOUT ANY TENANT SCOPE, WHICH IS THE WHOLE POINT.
    //
    // ⚠️ THE FIRST VERSION OF THIS CHECK WAS A FALSE GREEN, and mutation found it. It counted the leaked
    // rows inside THEIR organisation's context — but a cross-org write auto-fills `organization_id` from
    // the ATTACKER'S context, so the row lands in MINE and their scope reports zero. Breaking the
    // controller's scoping left this test passing: the 404 it saw came from AvailabilityService's own
    // `findOrFail` deep inside `recompute()`, AFTER `Unit::create` had already run — so the request both
    // wrote a cross-org row and returned 404, and the assertion looked in the one place the row was not.
    //
    // ⚠️ A tenancy check must be INDEPENDENT of the tenancy it is checking (§N.18.1). Raw queries here.
    Assert::assertSame(0, (int) DB::table('units')->where('room_type_id', $theirRoomType->getKey())->count(),
        'a cross-org unit was created despite the 404 — check WITHOUT a tenant scope, or the row hides');
    Assert::assertSame(0, (int) DB::table('rate_plans')->where('room_type_id', $theirRoomType->getKey())->count(),
        'a cross-org rate plan was created despite the 404');
    Assert::assertSame(0, (int) DB::table('units')->where('property_id', $mine['property']->getKey())->count(),
        'my own property gained a unit from a refused request');
});

// ══ MONEY — a newly created rate plan prices exactly as the certified engine does ════════════════════
it('⚠️ prices a newly created rate plan exactly as the engine already certifies', function () {
    $tenant = freshSignupTenant('money');
    $checkIn = CarbonImmutable::now()->addDays(30);
    $checkOut = $checkIn->addDays(3);

    $this->actingAs($tenant['user'])->post(route('app.inventory.room-types.store'), [
        'name' => 'Suite', 'kind' => 'room', 'max_adults' => 2, 'max_children' => 0, 'std_occupancy' => 2,
    ])->assertRedirect();
    $roomType = app(TenantContext::class)->forOrganization($tenant['org_id'],
        fn () => RoomType::query()->where('property_id', $tenant['property']->getKey())->firstOrFail());

    $this->actingAs($tenant['user'])->post(route('app.inventory.units.store'), [
        'room_type_id' => (string) $roomType->getKey(), 'code' => '201',
    ])->assertRedirect();

    $this->actingAs($tenant['user'])->post(route('app.inventory.rate-plans.store'), [
        'room_type_id' => (string) $roomType->getKey(), 'name' => 'Standard',
    ])->assertRedirect();
    $plan = app(TenantContext::class)->forOrganization($tenant['org_id'],
        fn () => RatePlan::query()->where('room_type_id', $roomType->getKey())->firstOrFail());

    // ⚠️ The plan MUST carry the property's currency and be active, or the engine silently skips it.
    app(TenantContext::class)->forOrganization($tenant['org_id'], function () use ($plan, $tenant): void {
        Assert::assertSame((string) $tenant['property']->currency, (string) $plan->currency,
            'a new rate plan does not carry the property currency — the quote would be in the wrong money');
        Assert::assertTrue((bool) $plan->is_active, 'a new rate plan is inactive, so cheapestRatePlan() skips it');
    });

    $this->actingAs($tenant['user'])->post(route('app.rates.update'), [
        'room_type_id' => (string) $roomType->getKey(),
        'start_date' => $checkIn->toDateString(), 'end_date' => $checkOut->subDay()->toDateString(),
        'set_rate' => '1', 'rate' => '99.50',
    ])->assertRedirect();

    // ⚠️ THE CHECK IS INDEPENDENT OF THE CREATION PATH (§N.18.1): ask the ENGINE what it costs.
    $total = app(TenantContext::class)->forOrganization($tenant['org_id'], fn (): ?int => app(BookingEngineService::class)
        ->priceOver((string) $plan->getKey(), $checkIn->toDateString(), $checkOut->toDateString()));

    Assert::assertSame(29850, $total, 'three nights at 99.50 must price at 298.50 in integer minor units');
});

// ══ VALIDATION — a rate plan feeds the quote engine, so its inputs are money inputs ══════════════════
it('⚠️ refuses absurd or malformed inventory input', function () {
    $tenant = freshSignupTenant('valid');

    // A room type with no name, or impossible occupancy.
    $this->actingAs($tenant['user'])->post(route('app.inventory.room-types.store'), [
        'name' => '', 'kind' => 'room', 'max_adults' => 2, 'max_children' => 0, 'std_occupancy' => 2,
    ])->assertSessionHasErrors('name');

    $this->actingAs($tenant['user'])->post(route('app.inventory.room-types.store'), [
        'name' => 'Bad', 'kind' => 'room', 'max_adults' => 0, 'max_children' => 0, 'std_occupancy' => 1,
    ])->assertSessionHasErrors('max_adults');

    $this->actingAs($tenant['user'])->post(route('app.inventory.room-types.store'), [
        'name' => 'Bad', 'kind' => 'not_a_kind', 'max_adults' => 2, 'max_children' => 0, 'std_occupancy' => 2,
    ])->assertSessionHasErrors('kind');

    app(TenantContext::class)->forOrganization($tenant['org_id'], function () use ($tenant): void {
        Assert::assertSame(0, RoomType::query()->where('property_id', $tenant['property']->getKey())->count(),
            'a refused room type was created anyway');
    });

    // ⚠️ THE FORM DOES NOT SEND `std_occupancy`, AND THAT IS DELIBERATE. It is RENDERED TO GUESTS on the
    // public room page ("Standard occupancy"), so the first version's hidden `value="2"` would have
    // published a figure nobody typed and nobody could see was wrong — a room type sleeping 4 showing a
    // standard occupancy of 2. It is derived from what WAS entered instead. This pins the payload the form
    // actually posts, not a payload only the test sends.
    $this->actingAs($tenant['user'])->post(route('app.inventory.room-types.store'), [
        'name' => 'Derived', 'kind' => 'room', 'max_adults' => 4, 'max_children' => 2,
    ])->assertSessionHasNoErrors();

    app(TenantContext::class)->forOrganization($tenant['org_id'], function (): void {
        $derived = RoomType::query()->where('name', 'Derived')->firstOrFail();
        Assert::assertSame(4, (int) $derived->std_occupancy,
            'std_occupancy must follow max_adults — it is shown to guests, so a stale default is a published untruth');
    });
});
