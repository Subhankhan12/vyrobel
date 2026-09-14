<?php

use App\Models\Availability;
use App\Models\Organization;
use App\Models\Property;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\BookingEngineService;
use App\Support\Marketing\TryBookingSandbox;
use App\Support\Money\MoneyFormatter;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

// FRONT-WEBSITE GATE 4 · THE TRY-BOOKING SANDBOX GUARD (ruling D3 = (b)), EXTENDED IN GATE 4b.
//
// ⚠️ EVERY ASSERTION BELOW READS THE REAL PAGE, not a fixture. Gate 3b's claims gate was proven on
// fixtures and was VACUOUS on the real ledger — the predicate was correct and nothing applied it to what
// mattered. So this file renders `marketing.try-booking` and interrogates the actual output.
//
// ⚠️⚠️ AND IT SEEDS THE DEMO PROPERTY, BECAUSE THE FIRST VERSION OF THIS FILE WAS VACUOUS. It ran against
// the empty test database, where `TryBookingSandbox::data()` is null — so every meaningful assertion hit an
// early `return` and the suite went green while checking nothing. ⚠️ Mutation caught it: removing the #33
// URL-defusing entirely (which really does put five `/guest/` links, including the arrival-secrets page,
// into the preview) left this file PASSING. The header even claimed it ran against the demo database.
// That is Gate 3b's lesson repeating inside the guard written to honour it: *the function is correct* is
// not *the function is applied to what matters*.
//
// So the demo property is seeded here, and {@see tbAssertNotVacuous()} refuses to let these tests pass
// while there is nothing to check.
//
// ⚠️⚠️ GATE 4b — THE FIXTURE NOW CONTAINS THE DANGEROUS SHAPES, not only the happy one. A guard can only
// find what its data can express, so the seed deliberately includes a SOLD-OUT night, a GAP in the rate
// calendar, VARYING nightly prices (a flat rate would let a wrong sum look right), and ⚠️ A SECOND
// PROPERTY IN A SECOND ORGANISATION with its own distinct rates — the thing a pricing oracle would leak.

uses(RefreshDatabase::class);

beforeEach(function () {
    tbSeedDemoProperty();
    tbSeedRivalProperty();
});

/** The page as a visitor gets it, with no dates picked. */
function tbPage(): string
{
    return view('marketing.try-booking')->render();
}

/**
 * ⚠️ REFUSES TO BE VACUOUS. Every assertion below is meaningless without a property to mirror, and the
 * early-return that used to handle that turned the whole file into a no-op. Now the absence is itself a
 * failure.
 */
function tbAssertNotVacuous(): array
{
    $data = TryBookingSandbox::fromRequest();
    Assert::assertNotNull($data, 'the sandbox has no demo property to mirror — every assertion in this file would be vacuous');

    return $data;
}

/** The property's own today — the day the sandbox works in (stay dates are the property's calendar days). */
function tbToday(): CarbonImmutable
{
    return CarbonImmutable::parse(CarbonImmutable::now('Europe/Lisbon')->toDateString());
}

/** ⚠️ The engine's own price, resolved INDEPENDENTLY of the sandbox (§N.18.1). */
function tbEnginePrice(string $from, string $to, string $slug = TryBookingSandbox::DEMO_SLUG): ?int
{
    return app(TenantContext::class)->runAsSuperAdmin(function () use ($from, $to, $slug): ?int {
        $property = Property::query()->where('slug', $slug)->firstOrFail();
        $roomType = RoomType::query()->where('property_id', $property->getKey())->orderBy('name')->firstOrFail();
        $ratePlan = RatePlan::query()->where('room_type_id', $roomType->getKey())->orderBy('name')->firstOrFail();

        return app(BookingEngineService::class)->priceOver((string) $ratePlan->getKey(), $from, $to);
    });
}

/**
 * Seed a property shaped like the real `seaside-demo`: the same slug, a room type, a rate plan, and a run
 * of published nightly rates. ⚠️ The RATES are what matter — the sandbox prices through the engine's own
 * `priceOver()`, so without published nights it returns null and the page shows its honest empty state.
 *
 * ⚠️ THE SHAPE IS DELIBERATELY AWKWARD (Gate 4b): prices VARY per night (a flat rate cannot tell a correct
 * sum from a wrong one), night 4 is SOLD OUT, and nights 12–13 are a GAP with no published rate. Those are
 * the two states the page has to refuse honestly, and a fixture without them could not prove it does.
 */
function tbSeedDemoProperty(): void
{
    app(TenantContext::class)->runAsSuperAdmin(function (): void {
        $org = Organization::factory()->create();

        $property = Property::factory()->create([
            'organization_id' => $org->getKey(),
            'slug' => TryBookingSandbox::DEMO_SLUG,
            'name' => 'Demo Seaside Property',
            'currency' => 'EUR',
            'timezone' => 'Europe/Lisbon',
            'address' => ['city' => 'Faro', 'region' => 'Algarve', 'country' => 'Portugal'],
        ]);

        $roomType = RoomType::factory()->create([
            'organization_id' => $org->getKey(),
            'property_id' => $property->getKey(),
            'name' => 'Double Room',
        ]);

        Unit::factory()->count(3)->create([
            'organization_id' => $org->getKey(),
            'property_id' => $property->getKey(),
            'room_type_id' => $roomType->getKey(),
        ]);

        $ratePlan = RatePlan::factory()->create([
            'organization_id' => $org->getKey(),
            'room_type_id' => $roomType->getKey(),
            'name' => 'Flexible',
            'currency' => 'EUR',
        ]);

        $today = tbToday();

        for ($i = 0; $i < 20; $i++) {
            $date = $today->addDays($i);

            // ⚠️ THE GAP. priceOver() refuses a stay containing an unpriced night; without a gap here,
            // the "unpriced" branch of the page would never be reachable and its guard would be theatre.
            if ($i !== 12 && $i !== 13) {
                RateCalendar::query()->create([
                    'organization_id' => $org->getKey(),
                    'rate_plan_id' => $ratePlan->getKey(),
                    'date' => $date->toDateString(),
                    // ⚠️ VARYING, so a sum cannot be right by accident.
                    'price_minor' => 10000 + ($i * 500),
                    'currency' => 'EUR',
                ]);
            }

            Availability::query()->create([
                'organization_id' => $org->getKey(),
                'room_type_id' => $roomType->getKey(),
                'date' => $date->toDateString(),
                'total' => 3,
                // ⚠️ THE SOLD-OUT NIGHT. The page must price it honestly and still refuse the stay.
                'sold' => $i === 4 ? 3 : 0,
                'out_of_order' => 0,
                'blocked' => 0,
            ]);
        }
    });
}

/**
 * ⚠️ THE THING A PRICING ORACLE WOULD LEAK. A second property, in a SECOND ORGANISATION, with its own
 * distinct rates. Nothing in the sandbox may ever reach it — and a guard that says so is worthless unless
 * this actually exists and actually differs, which {@see tbAssertRivalIsDistinct()} checks.
 */
function tbSeedRivalProperty(): void
{
    app(TenantContext::class)->runAsSuperAdmin(function (): void {
        $org = Organization::factory()->create();

        $property = Property::factory()->create([
            'organization_id' => $org->getKey(),
            'slug' => 'rival-cliffside',
            'name' => 'Rival Cliffside Resort',
            'currency' => 'EUR',
            'timezone' => 'Europe/Lisbon',
            'address' => ['city' => 'Nazare', 'region' => 'Centro', 'country' => 'Portugal'],
        ]);

        $roomType = RoomType::factory()->create([
            'organization_id' => $org->getKey(),
            'property_id' => $property->getKey(),
            'name' => 'Cliff Suite',
        ]);

        Unit::factory()->count(2)->create([
            'organization_id' => $org->getKey(),
            'property_id' => $property->getKey(),
            'room_type_id' => $roomType->getKey(),
        ]);

        $ratePlan = RatePlan::factory()->create([
            'organization_id' => $org->getKey(),
            'room_type_id' => $roomType->getKey(),
            'name' => 'Cliff Flexible',
            'currency' => 'EUR',
        ]);

        $today = tbToday();

        for ($i = 0; $i < 20; $i++) {
            RateCalendar::query()->create([
                'organization_id' => $org->getKey(),
                'rate_plan_id' => $ratePlan->getKey(),
                'date' => $today->addDays($i)->toDateString(),
                'price_minor' => 77700,     // unmistakable, and nothing like the demo's rates
                'currency' => 'EUR',
            ]);
            Availability::query()->create([
                'organization_id' => $org->getKey(),
                'room_type_id' => $roomType->getKey(),
                'date' => $today->addDays($i)->toDateString(),
                'total' => 2, 'sold' => 0, 'out_of_order' => 0, 'blocked' => 0,
            ]);
        }
    });
}

/** The rival must really exist and really cost something else, or every oracle needle below is vacuous. */
function tbAssertRivalIsDistinct(string $from, string $to): int
{
    // ⚠️ SAY WHY, rather than dying inside a firstOrFail. Without a second organisation to leak, every
    // needle in the oracle test is a needle with nothing to find.
    Assert::assertTrue(
        app(TenantContext::class)->runAsSuperAdmin(
            fn (): bool => Property::query()->where('slug', 'rival-cliffside')->exists(),
        ),
        'no second organisation exists — the pricing-oracle guard would have nothing to catch',
    );

    $rival = tbEnginePrice($from, $to, 'rival-cliffside');
    Assert::assertNotNull($rival, 'the rival property is unpriced — the pricing-oracle guard would be vacuous');
    Assert::assertNotSame(tbEnginePrice($from, $to), $rival,
        'the rival costs the same as the demo — a leak would be invisible to every assertion here');

    return $rival;
}

// ══ CONSTRAINT 4 — MIRROR THE REAL PROPERTY, never a parallel fiction ══════════════════════════════
it('⚠️ names no invented property, and mirrors the real demo property when it exists', function () {
    $html = tbPage();

    // The design's fiction, in every form it appeared.
    foreach (['Villa Azure', 'Paros, Greece', 'sleeps 6'] as $fiction) {
        Assert::assertStringNotContainsString($fiction, $html, "the page still carries the invented property detail \"{$fiction}\"");
    }

    $data = tbAssertNotVacuous();
    Assert::assertStringContainsString($data['propertyName'], $html, 'the page does not name the real demo property');
    Assert::assertStringContainsString($data['roomTypeName'], $html, 'the page does not name the real room type');
    Assert::assertStringContainsString('/book/'.TryBookingSandbox::DEMO_SLUG, $html, 'no path to the real storefront');
});

// ══ CONSTRAINT 5 — the price comes from the ENGINE'S OWN source, for EVERY stay ════════════════════
it('⚠️ prices every picked stay exactly as the engine does, across many ranges', function () {
    $today = tbToday();

    // ⚠️ SEVERAL RANGES, NOT ONE. A single agreeing range proves the wiring, not the rule: different
    // lengths, different start days and a stay crossing the sold-out night all have to agree.
    $ranges = [[0, 1], [0, 3], [1, 2], [2, 6], [3, 4], [5, 9], [6, 11], [8, 12], [14, 19], [1, 15]];
    $seen = [];

    foreach ($ranges as [$a, $b]) {
        $from = $today->addDays($a)->toDateString();
        $to = $today->addDays($b)->toDateString();

        $data = TryBookingSandbox::data($from, $to);
        Assert::assertNotNull($data);

        $engine = tbEnginePrice($from, $to);
        Assert::assertSame($engine, $data['totalMinor'],
            "the sandbox total disagrees with the engine for {$from} → {$to}");

        if ($engine !== null) {
            $seen[] = $engine;
            $response = $this->get('/try-booking?from='.$from.'&to='.$to);
            $response->assertOk();
            Assert::assertStringContainsString(MoneyFormatter::display($engine, 'EUR'), $response->getContent(),
                "the page does not show the engine's total for {$from} → {$to}");
        }
    }

    // ⚠️ A POSITIVE CONTROL ON THE DATA. If every range priced null, or every total were identical, the
    // loop above would pass while proving nothing.
    Assert::assertGreaterThanOrEqual(6, count($seen), 'too few ranges priced — this test would be near-vacuous');
    Assert::assertGreaterThan(1, count(array_unique($seen)), 'every range cost the same — a wrong sum would be invisible');
});

it('⚠️ shows each calendar night at the price the engine gives for that night', function () {
    tbAssertNotVacuous();

    $html = $this->get('/try-booking')->getContent();
    preg_match_all('/data-night="(\d{4}-\d{2}-\d{2})" data-night-price-minor="(\d*)"/', $html, $matches, PREG_SET_ORDER);

    Assert::assertGreaterThan(40, count($matches), 'the calendar rendered almost no nights — this sweep would be vacuous');

    $priced = 0;
    foreach ($matches as [, $date, $minor]) {
        $engine = tbEnginePrice($date, CarbonImmutable::parse($date)->addDay()->toDateString());
        $shown = $minor === '' ? null : (int) $minor;

        // Nights before the property's today are deliberately blank: the engine may still price them.
        if ($shown === null && CarbonImmutable::parse($date)->lt(tbToday())) {
            continue;
        }

        Assert::assertSame($engine, $shown, "the calendar shows the wrong price for {$date}");
        $priced += $engine === null ? 0 : 1;
    }

    Assert::assertGreaterThan(10, $priced, 'no priced nights were checked — the sweep would be vacuous');
});

it('⚠️ refuses to price an unpriced stay, and invents nothing in its place', function () {
    tbAssertNotVacuous();   // ⚠️ say WHY on an empty database, rather than dying on a null offset
    $today = tbToday();
    // Nights 12 and 13 have no published rate; a stay across them cannot be priced.
    $from = $today->addDays(11)->toDateString();
    $to = $today->addDays(15)->toDateString();

    Assert::assertNull(tbEnginePrice($from, $to), 'the fixture has no gap — this test would be vacuous');

    $data = TryBookingSandbox::data($from, $to);
    Assert::assertSame('unpriced', $data['status']);
    Assert::assertNull($data['totalMinor'], 'a stay the engine refuses to price got a price anyway');
    Assert::assertNull($data['total']);

    $html = $this->get('/try-booking?from='.$from.'&to='.$to)->getContent();
    Assert::assertStringContainsString('no published rate', $html, 'the page does not say why it will not price this stay');
    Assert::assertStringNotContainsString('data-sandbox-total', $html, 'an unpriced stay still rendered a total');
    // ⚠️ AND NO CONFIRMATION FOR IT. An email confirming a stay that cannot be priced is the exact
    // fabrication Gate 4 removed.
    Assert::assertStringNotContainsString('data-mail-preview', $html, 'an unpriced stay rendered a confirmation email');
    Assert::assertStringNotContainsString('€0.00', $html, 'an unpriced stay was shown as free');
});

it('⚠️ marks a sold-out stay as unavailable rather than confirming it', function () {
    tbAssertNotVacuous();   // ⚠️ same: a diagnostic refusal beats a TypeError
    $today = tbToday();
    // Night 4 has no room free.
    $from = $today->addDays(3)->toDateString();
    $to = $today->addDays(6)->toDateString();

    $data = TryBookingSandbox::data($from, $to);
    Assert::assertSame('unavailable', $data['status'], 'the fixture has no sold-out night — this test would be vacuous');
    // The PRICE is still the engine's real published rate; what is refused is the confirmation.
    Assert::assertSame(tbEnginePrice($from, $to), $data['totalMinor']);

    $html = $this->get('/try-booking?from='.$from.'&to='.$to)->getContent();
    Assert::assertStringContainsString('no room free', $html);
    Assert::assertStringNotContainsString('data-mail-preview', $html, 'a stay with no room free rendered a confirmation email');
});

// ══ ⚠️ GATE 4b — NO PRICING ORACLE. The mutation, performed and observed refused. ══════════════════
it('⚠️ cannot be pointed at any property but the demo one', function () {
    tbAssertNotVacuous();   // ⚠️ same
    $today = tbToday();
    $from = $today->addDay()->toDateString();
    $to = $today->addDays(3)->toDateString();

    // ⚠️ THE POSITIVE CONTROL ON THE DATA FIRST. A second organisation's property really exists and really
    // costs something else; without that, every needle below would pass on an empty world.
    $rivalPrice = tbAssertRivalIsDistinct($from, $to);
    $demoPrice = tbEnginePrice($from, $to);
    Assert::assertNotNull($demoPrice);

    // ⚠️ THE ATTACK. Every shape a property could plausibly be smuggled in as — slug, id, organisation.
    $rival = app(TenantContext::class)->runAsSuperAdmin(
        fn () => Property::query()->where('slug', 'rival-cliffside')->firstOrFail(),
    );

    $attacks = [
        'property=rival-cliffside',
        'slug=rival-cliffside',
        'property_id='.$rival->getKey(),
        'property='.$rival->getKey(),
        'organization='.$rival->organization_id,
        'organization_id='.$rival->organization_id,
        'p=rival-cliffside&property=rival-cliffside&property_id='.$rival->getKey(),
    ];

    foreach ($attacks as $attack) {
        $response = $this->get('/try-booking?from='.$from.'&to='.$to.'&'.$attack);
        $response->assertOk();
        $html = $response->getContent();

        Assert::assertStringNotContainsString('Rival Cliffside Resort', $html, "[{$attack}] named another organisation's property");
        Assert::assertStringNotContainsString('Cliff Suite', $html, "[{$attack}] named another organisation's room type");
        Assert::assertStringNotContainsString('rival-cliffside', $html, "[{$attack}] leaked another property's slug");
        Assert::assertStringNotContainsString((string) $rival->getKey(), $html, "[{$attack}] leaked another property's id");
        Assert::assertStringNotContainsString(MoneyFormatter::display($rivalPrice, 'EUR'), $html, "[{$attack}] priced another organisation's property");

        // …and it still prices the DEMO property, so the refusal is a refusal and not a blank page.
        Assert::assertStringContainsString('Demo Seaside Property', $html, "[{$attack}] stopped showing the demo property");
        Assert::assertStringContainsString(MoneyFormatter::display($demoPrice, 'EUR'), $html, "[{$attack}] stopped showing the demo price");
    }
});

it('PROVES the pricing-oracle needle can fail (the positive control)', function () {
    // ⚠️ A needle never seen to fire proves nothing — least of all one guarding cross-tenant rate exposure.
    $failed = false;
    try {
        Assert::assertStringNotContainsString('Rival Cliffside Resort', '<div>Rival Cliffside Resort — €777.00</div>');
    } catch (AssertionFailedError) {
        $failed = true;
    }
    expect($failed)->toBeTrue('the oracle needle cannot fire — it proves nothing');
});

// ══ ⚠️ CONSTRAINT 3 + #33 — the REAL mail, with nothing actionable in it ═══════════════════════════
it('⚠️ renders the REAL confirmation mail with every link defused (#33)', function () {
    $data = tbAssertNotVacuous();

    $preview = TryBookingSandbox::confirmationPreview($data);
    Assert::assertNotNull($preview);

    // It is the REAL template: the mail's own heading and reconciling total are present.
    Assert::assertStringContainsString($data['total'], $preview, 'the preview is not showing the real reconciling total');
    Assert::assertStringContainsString(TryBookingSandbox::SANDBOX_REFERENCE, $preview, 'the preview does not carry the sandbox reference');

    tbAssertNothingActionable($preview, tbPage(), 'the default stay');
});

// ══ ⚠️⚠️ F-7b — THE EMBEDDED EMAIL MAY NOT CARRY A SECOND PAGE H1 ═════════════════════════════════
//
// ⚠️ /try-booking inlines a COMPLETE email document, and that document opens with its own `<h1>` — correct
// for an email, where it is the only heading. Inlined, it gave the page TWO `<h1>`s, so the page advertised
// two subjects and a screen-reader user met "You're all set, Guest" as a peer of the page's real title.
//
// ⚠️⚠️ THIS ASSERTION LIVES HERE, NOT WITH THE OTHER HEADING GUARDS, AND THE REASON IS THE POINT. The
// generic rule in `MarketingProgressiveEnhancementTest` renders the VIEW, and the sandbox only renders the
// confirmation when a real bookable stay exists — which it does not in a bare render, nor on a plain GET of
// the route in the test environment. So that guard came back GREEN with the fix removed: it could not see
// the defect at all. This file already builds a real preview, so it is the only place the rule can bite.
//
// ⚠️ THE EMAIL TEMPLATE ITSELF IS UNTOUCHED — the demotion is applied to the EMBEDDED COPY only, so every
// real send is byte-identical. Asserting on the template would have been the wrong layer entirely.
it('⚠️ demotes the embedded mail’s h1 so the page keeps exactly one', function () {
    $data = tbAssertNotVacuous();

    $preview = TryBookingSandbox::confirmationPreview($data);
    Assert::assertNotNull($preview);

    // ⚠️ NOT VACUOUS: the mail's heading really is in there — it has simply stopped being an <h1>.
    Assert::assertStringContainsString('all set', $preview, 'the preview is not showing the mail heading at all');
    Assert::assertStringNotContainsString('<h1', $preview, 'the embedded mail still carries an <h1>');
    Assert::assertStringContainsString('<h2', $preview, 'the mail heading was removed rather than demoted');
});

it('⚠️ keeps #33 defused on the INTERACTIVE path too, for every picked stay', function () {
    // ⚠️ THE PICKED PATH IS A NEW WAY TO REACH THE SAME RENDER — which is exactly why it needs its own
    // assertion. A defusing that only ran for the default window would leak the moment anyone picked.
    $today = tbToday();
    $checked = 0;

    foreach ([[0, 2], [1, 5], [5, 8], [8, 12], [15, 19]] as [$a, $b]) {
        $from = $today->addDays($a)->toDateString();
        $to = $today->addDays($b)->toDateString();

        $data = TryBookingSandbox::data($from, $to);
        $preview = TryBookingSandbox::confirmationPreview($data);
        $html = $this->get('/try-booking?from='.$from.'&to='.$to)->getContent();

        tbAssertNothingActionable($preview, $html, "{$from} → {$to}");
        $checked += $preview === null ? 0 : 1;
    }

    Assert::assertGreaterThanOrEqual(3, $checked,
        'almost no picked stay rendered a mail preview — this sweep would be vacuous');
});

/**
 * ⚠️ NOTHING IN IT CAN BE ACTIONED. `arrivalUrl` is the arrival-secrets page (#33); the rest carry a
 * reservation identifier. None may survive into a public page.
 *
 * ⚠️ NARROWED, AND THE NARROWING IS THE POINT. A blanket `http://` needle fired on
 * `xmlns="http://www.w3.org/1999/xhtml"` — an XML namespace declaration, not a link. A guard that cries
 * wolf on a namespace gets loosened by the next person; one that names what it actually forbids survives.
 * ⚠️ THE NEEDLES ARE THE ROUTES' OWN PATHS, derived — not guessed. A hand-written "/guest/arrival" would
 * never have matched: the real path is "/guest/reservation/{id}/arrival". A guard whose needle cannot
 * occur is the purest form of vacuity, and this one protects the arrival-secrets window (#33).
 */
function tbAssertNothingActionable(?string $preview, string $html, string $context): void
{
    if ($preview !== null) {
        foreach (['guest.receipt', 'guest.manage', 'guest.arrival', 'guest.checkin'] as $routeName) {
            $path = (string) parse_url(route($routeName, TryBookingSandbox::SANDBOX_REFERENCE), PHP_URL_PATH);
            Assert::assertNotSame('', $path);
            Assert::assertStringNotContainsString($path, $preview, "[{$context}] the mail preview leaks the actionable path \"{$path}\"");
        }
        Assert::assertStringNotContainsString('href="'.rtrim(url('/'), '/'), $preview,
            "[{$context}] the mail preview links back into this application");
        Assert::assertDoesNotMatchRegularExpression('/href="https?:\/\/(?!www\.w3\.org)/i', $preview,
            "[{$context}] the mail preview carries an absolute outbound link");
    }

    // …and the same must hold of the whole rendered PAGE, not just the preview fragment.
    // ⚠️ SCOPED TO THE ACTIONABLE PATHS, not to "/guest/" as a whole: the footer carries a legitimate
    // "Booked a stay? Guest sign-in" link to /guest/login (ruling D9, Gate 1). A blanket needle fired on
    // that honest link — and the fix is to name what is actually forbidden (a per-reservation deep link),
    // not to weaken the guard.
    // ⚠️ MATCH THE ROUTE *PATTERN*, NOT ONE KEY. The first version substituted the SANDBOX reference into
    // the route and searched for that exact string — so a leak carrying a REAL reservation id (which is
    // the leak that would actually matter) sailed straight past it. Mutation caught it: planting
    // `/guest/reservation/X/arrival` left the guard green.
    Assert::assertStringNotContainsString('/guest/reservation/', $html,
        "[{$context}] the page carries a per-reservation guest deep link — the #33 surface");
}

it('PROVES the leak detector can fail (the positive control)', function () {
    // ⚠️ A detector never seen to fire proves nothing — and this one guards an arrival-secrets leak, so a
    // silent pass would be the worst kind. Feed it exactly what it exists to catch.
    $poisoned = (string) TryBookingSandbox::confirmationPreview()
        .'<a href="/guest/arrival/01ABCDEF">Your arrival details</a>';

    $failed = false;
    try {
        Assert::assertStringNotContainsString('/guest/', $poisoned);
    } catch (AssertionFailedError) {
        $failed = true;
    }
    expect($failed)->toBeTrue('the leak detector cannot fire — it proves nothing');

    // ⚠️ And the absolute-link detector too, since it is the one that guards a linkable arrival page.
    $failedAbsolute = false;
    try {
        Assert::assertDoesNotMatchRegularExpression('/href="https?:\/\/(?!www\.w3\.org)/i',
            '<a href="https://vyrobel.com/guest/arrival/01ABC">arrival</a>');
    } catch (AssertionFailedError) {
        $failedAbsolute = true;
    }
    expect($failedAbsolute)->toBeTrue('the absolute-link detector cannot fire');
});

// ══ CONSTRAINTS 1 + 2 — honest meta, and framing that cannot be missed ═════════════════════════════
it('⚠️ promises no real booking, anywhere on the page', function () {
    $html = tbPage();

    // The design's own promises, each in the form it appeared.
    foreach (['Make a real booking', 'a real booking on a demo property', 'Make a booking, the way your guests would',
        'the real direct booking engine running against a demo property'] as $promise) {
        Assert::assertStringNotContainsStringIgnoringCase($promise, $html, "the page still promises: \"{$promise}\"");
    }
});

it('⚠️ frames the sandbox prominently — above the CONTROL, and again at the total', function () {
    $today = tbToday();
    $html = $this->get('/try-booking?from='.$today->addDay()->toDateString().'&to='.$today->addDays(4)->toDateString())->getContent();

    Assert::assertStringContainsString('data-sandbox-banner', $html, 'the sandbox banner is gone');
    Assert::assertStringContainsString('This is a preview, not a booking', $html);
    Assert::assertStringContainsString('nothing on', strtolower($html));

    // ⚠️ PROMINENCE IS POSITIONAL — AND INTERACTIVITY MOVED THE POINT OF MAXIMUM ILLUSION. Gate 4 only had
    // to sit above the confirmation; Gate 4b must also sit above the CONTROL, because a visitor who has
    // just picked dates and seen a price is closer to believing they booked something than one who only
    // read a page.
    $banner = strpos($html, 'data-sandbox-banner');
    $calendar = strpos($html, 'data-sandbox-calendar');
    $note = strpos($html, 'data-sandbox-interaction-note');
    $preview = strpos($html, 'data-mail-preview');

    Assert::assertNotFalse($banner, 'no banner');
    Assert::assertNotFalse($calendar, 'no picker — this positional assertion would be vacuous');
    Assert::assertNotFalse($note, 'the framing at the point of interaction is gone');
    Assert::assertLessThan($calendar, $banner, 'the sandbox framing sits BELOW the control the visitor uses');

    // The second refusal lands with the number the visitor just produced.
    $total = strpos($html, 'data-sandbox-total');
    Assert::assertNotFalse($total);
    Assert::assertGreaterThan($total, $note, 'the framing at the total sits above the total, not with it');
    if ($preview !== false) {
        Assert::assertLessThan($preview, $banner, 'the sandbox framing sits BELOW the confirmation preview');
    }
});

// ══ ⚠️ GATE 4b — THE COPY AND THE CONTROL MUST AGREE, IN BOTH DIRECTIONS ═══════════════════════════
it('⚠️ invites a date picker if and only if a date picker renders', function () {
    $html = tbPage();

    // ⚠️ THIS IS THE DEFECT GATE 4 FOUND BY READING THE RENDER. With the simulation removed, the hero
    // still said "Pick your dates, watch the rate build itself" — an invitation to use a control that was
    // not there. The picker is back now, so the invitation is true again; pinning it as a BICONDITIONAL is
    // what stops the two halves from ever moving apart, in either direction.
    $copyInvites = str_contains($html, 'Pick your dates');
    $pickerRenders = str_contains($html, 'data-sandbox-calendar');

    Assert::assertSame($copyInvites, $pickerRenders,
        $copyInvites
            ? 'the copy invites a date picker that does not render'
            : 'a date picker renders but no copy invites it');

    // ⚠️ AND NOT VACUOUSLY: `false === false` would satisfy the biconditional with the whole feature gone.
    Assert::assertTrue($pickerRenders, 'the picker is gone — the biconditional above would be satisfied by absence');

    // The control is really a control: it offers real, followable choices.
    Assert::assertMatchesRegularExpression('/href="\/try-booking\?from=\d{4}-\d{2}-\d{2}/', $html,
        'the calendar renders no pickable date');
});

it('⚠️ can actually be picked with — two clicks produce a priced stay', function () {
    // ⚠️ THIS TEST EXISTS BECAUSE THE PICKER ONCE RENDERED PERFECTLY AND COULD NOT BE USED. Every cell was
    // a real link to a real date, so "the calendar renders a pickable date" passed — but with a check-in
    // already chosen, every LATER cell restarted the range instead of completing it, and no two clicks
    // could ever price a stay. ⚠️ Found by clicking twice in a browser, not by any assertion here: a
    // control is only proven by following it to its outcome. So this walks the picker the way a visitor
    // does, by following the page's OWN hrefs rather than by constructing URLs the page never offers.
    tbAssertNotVacuous();

    // Click one — the first pickable day the page itself offers.
    $html = $this->get('/try-booking')->getContent();
    preg_match_all('/href="(\/try-booking\?from=(\d{4}-\d{2}-\d{2})[^"]*)"/', $html, $starts, PREG_SET_ORDER);
    Assert::assertNotEmpty($starts, 'the calendar offers no check-in to click');

    $checkIn = $starts[0][2];
    $step1 = $this->get(html_entity_decode($starts[0][1]));
    $step1->assertOk();
    $afterFirst = $step1->getContent();
    Assert::assertStringNotContainsString('data-sandbox-total', $afterFirst,
        'a half-picked range was priced — there is no stay yet');

    // Click two — and the page must now offer a link that COMPLETES the range, not one that restarts it.
    preg_match_all('/href="(\/try-booking\?from='.preg_quote($checkIn, '/').'&amp;to=(\d{4}-\d{2}-\d{2})[^"]*)"/', $afterFirst, $completions, PREG_SET_ORDER);
    Assert::assertNotEmpty($completions,
        'no cell completes the range — the picker renders but cannot be picked with');

    $completion = $completions[0];
    $checkOut = $completion[2];
    $step2 = $this->get(html_entity_decode($completion[1]));
    $step2->assertOk();
    $afterSecond = $step2->getContent();

    // ⚠️ AND THE OUTCOME IS THE ENGINE'S. Two clicks, a real stay, a real price, a real confirmation.
    $engine = tbEnginePrice($checkIn, $checkOut);
    Assert::assertNotNull($engine, "two clicks produced an unpriceable stay {$checkIn} → {$checkOut}");
    Assert::assertStringContainsString('data-sandbox-total', $afterSecond, 'the picked stay has no total');
    Assert::assertStringContainsString(MoneyFormatter::display($engine, 'EUR'), $afterSecond,
        'the picked stay is not priced by the engine');
    Assert::assertStringContainsString('data-mail-preview', $afterSecond,
        'the picked stay renders no confirmation email');
});

it('⚠️ invites a date picker on no OTHER marketing page', function () {
    // ⚠️ THE SAME SHAPE THAT CAUGHT THE NAV IN GATE 4: a ruling scoped to one page does not reach its
    // neighbours or the chrome, and an invitation to pick dates is false everywhere the picker is not.
    $views = collect((array) glob(resource_path('views/marketing/*.blade.php')))
        ->map(fn (string $f): string => basename($f, '.blade.php'))->sort()->values();

    Assert::assertGreaterThanOrEqual(15, $views->count(), 'the marketing page set shrank — this sweep would cover less');

    foreach ($views as $view) {
        if ($view === 'try-booking') {
            continue;
        }
        $html = view('marketing.'.$view)->render();
        Assert::assertStringNotContainsStringIgnoringCase('Pick your dates', $html,
            "[{$view}] invites a date picker it does not have");
    }
});

// ══ ⚠️ NO WRITES — HEAVY INTERACTION MUST CHANGE NOTHING ═══════════════════════════════════════════
it('⚠️ writes nothing, however hard the picker is used', function () {
    $counts = fn (): array => app(TenantContext::class)->runAsSuperAdmin(fn (): array => [
        'reservations' => DB::table('reservations')->count(),
        'booking_quotes' => DB::table('booking_quotes')->count(),
        'folios' => DB::table('folios')->count(),
        'payments' => DB::table('payments')->count(),
        // ⚠️ THE READ PATH MUST NOT MOVE THE PRICES EITHER. `unit_rate_overrides` is the "0 overrides"
        // term of money-as-seeded; rate_calendar and availability are what the sandbox reads.
        'unit_rate_overrides' => DB::table('unit_rate_overrides')->count(),
        'rate_calendar' => DB::table('rate_calendar')->count(),
        'availability' => DB::table('availability')->count(),
        'rate_sum' => (int) DB::table('rate_calendar')->sum('price_minor'),
    ]);

    // ⚠️ AND THIS TEST WAS ITSELF VACUOUS ON AN EMPTY DATABASE — found by the same mutation that found
    // Gate 4's. With no demo property, every call below returns null immediately, nothing is priced, and
    // the counts match trivially: PASSING while proving nothing about writes. So the exercise now COUNTS
    // WHAT IT ACTUALLY DID, and refuses to pass without having done it.
    tbAssertNotVacuous();

    $before = $counts();
    $today = tbToday();
    $requests = 0;
    $reallyPriced = 0;
    $reallyConfirmed = 0;

    // ⚠️ HEAVILY, NOT ONCE. A write that happens on the tenth distinct range, or only when a stay is
    // sold out, or only when the month cursor moves, would hide behind a single render.
    foreach (range(0, 8) as $a) {
        foreach ([1, 3, 6] as $len) {
            $data = TryBookingSandbox::data($today->addDays($a)->toDateString(), $today->addDays($a + $len)->toDateString());
            $reallyPriced += ($data['totalMinor'] ?? null) === null ? 0 : 1;
            $reallyConfirmed += TryBookingSandbox::confirmationPreview() === null ? 0 : 1;
            $requests++;
        }
    }
    foreach ([['from' => $today->addDay()->toDateString()], ['m' => $today->addMonth()->format('Y-m')], []] as $params) {
        $response = $this->get('/try-booking'.($params === [] ? '' : '?'.http_build_query($params)));
        $response->assertOk();
        Assert::assertStringContainsString('data-sandbox-calendar', $response->getContent(),
            'a request rendered no picker — it exercised nothing');
        $requests++;
    }

    $after = $counts();

    Assert::assertGreaterThanOrEqual(27, $requests, 'the picker was barely exercised — this test would be near-vacuous');
    // ⚠️ THE POSITIVE CONTROLS ON THE WORK, not on the request count. Real stays were really priced and
    // real confirmation mails were really rendered; without these the loop can spin over nothing.
    Assert::assertGreaterThanOrEqual(15, $reallyPriced, 'almost nothing was actually priced — this test would be vacuous');
    Assert::assertGreaterThanOrEqual(10, $reallyConfirmed, 'almost no confirmation was actually rendered — this test would be vacuous');
    // ⚠️ The engine's quote() persists a booking_quotes row; the sandbox calls only its read-only
    // priceOver() half. If someone ever "simplifies" that to quote(), this reds immediately.
    Assert::assertSame($before, $after, 'exercising the sandbox wrote to the database');
});

// ══ ⚠️ THE MONEY DESCRIPTOR — generated from MoneyFormatter, never re-typed ════════════════════════
it('⚠️ describes money exactly as MoneyFormatter renders it', function () {
    $format = TryBookingSandbox::moneyFormat('EUR');

    // ⚠️ REASSEMBLED FROM THE DESCRIPTOR, then required to equal the formatter. If MoneyFormatter ever
    // changes shape — a different symbol position, separator or precision — this reds instead of silently
    // describing the old one. §N.8f: two representations of one concept, pinned together or they drift.
    foreach ([0, 1, 99, 100, 12345, 999999, 10000000, 123456789] as $minor) {
        $rebuilt = $format['symbol'].number_format(
            abs($minor) / 100, $format['decimals'], $format['decimal'], $format['thousands'],
        );
        Assert::assertSame(MoneyFormatter::display($minor, 'EUR'), $rebuilt,
            "the descriptor cannot reproduce MoneyFormatter for {$minor}");
    }
});

// ══ ⚠️ THE RATE LIMIT — on the parameterised path, and NOT on the crawlable page ═══════════════════
it('⚠️ rate-limits the parameterised sandbox but leaves the plain page crawlable', function () {
    $limiter = app(RateLimiter::class)->limiter('try-booking');
    Assert::assertNotNull($limiter, 'the try-booking limiter is not registered — the route names one that does not exist');

    $plain = $limiter(Request::create('/try-booking'));
    $dated = $limiter(Request::create('/try-booking?from=2026-01-01&to=2026-01-03'));

    Assert::assertSame(PHP_INT_MAX, $plain->maxAttempts, 'the plain marketing page is throttled — crawlers would be 429ed');
    Assert::assertLessThan(PHP_INT_MAX, $dated->maxAttempts, 'the parameterised path is unlimited');
    Assert::assertGreaterThan(0, $dated->maxAttempts);

    // …and the route really carries it, or the limiter above is decoration.
    $middleware = Route::getRoutes()
        ->getByName('marketing.page.try-booking')->gatherMiddleware();
    Assert::assertContains('throttle:try-booking', $middleware, 'the route does not use the limiter');
});

// ══ THE GENERATION LESSON — the transforms must survive regeneration ═══════════════════════════════
it('⚠️ keeps the honesty transforms in the generated page (not a post-generation patch)', function () {
    // ⚠️ Gate 3b proved twice that a fix applied AFTER generation is undone by the next generation. These
    // assertions read the FILE, so if the page is ever regenerated without the converter's transforms,
    // they red — which is the only way that regression can be caught.
    // ⚠️ STRIP THE BLADE COMMENTS FIRST. The page's own header comment QUOTES the promise it removed
    // ("Make a real booking on a demo property") to explain why — and the first version of this sweep
    // fired on that explanation. A source-scanning guard that reads its own needle is a recurring trap in
    // this codebase (§N.27.5); comments do not ship, so they are not what this guard is about.
    $raw = (string) file_get_contents(resource_path('views/marketing/try-booking.blade.php'));
    $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $raw);
    Assert::assertNotSame($raw, $source, 'no Blade comment was stripped — this guard would be reading its own documentation');

    Assert::assertStringNotContainsString('Villa Azure', $source, 'the invented property came back — the transform is not in the converter');
    Assert::assertStringContainsString('sandbox-confirmation', $source, 'the real-mail component is gone from the generated page');
    Assert::assertStringNotContainsString('Make a real booking', $source);
    // ⚠️ The page reaches the real property THROUGH the component (which resolves its own data), so the
    // component tag is the transform to pin — not a class name the page never mentions. My first version
    // asserted the class name and failed for the wrong reason.
    // ⚠️ GATE 4b: "Pick your dates" is now REQUIRED in the generated source, because the picker exists.
    // The converter restores it; a regeneration that dropped the restoration reds here.
    Assert::assertStringContainsString('Pick your dates', $source, 'the hero no longer invites the picker that renders');
    // …and what the design promised on top of it stays refused: no animated rate build, no invented
    // "an OTA would have taken €X" comparison (#39).
    Assert::assertStringNotContainsString('watch the rate build itself', $source);
    Assert::assertStringNotContainsStringIgnoringCase('commission that doesn', $source);
});

// ══ ⚠️ THE SITEWIDE SWEEP — a ruling scoped to one page reaches neither the chrome nor its neighbours ══
it('⚠️ promises a booking on NO marketing page, and not in the chrome', function () {
    // ⚠️ THIS SWEEP FOUND A REAL DEFECT THE RULING COULD NOT. D3 was scoped to /try-booking, but the NAV's
    // own row for it read "A real booking, no signup" — rendered on EVERY page, promising exactly what the
    // rebuilt page refuses to do. Gate 3b had already learned this shape (three live Sales pages carrying
    // a deleted claim); this is the same lesson, made permanent.
    $views = collect((array) glob(resource_path('views/marketing/*.blade.php')))
        ->map(fn (string $f): string => basename($f, '.blade.php'))->sort()->values();

    Assert::assertGreaterThanOrEqual(15, $views->count(), 'the marketing page set shrank — this sweep would cover less');

    foreach ($views as $view) {
        $html = view('marketing.'.$view)->render();
        foreach (['Make a real booking', 'a real booking', 'Book now', 'Reserve now', 'Book your stay'] as $promise) {
            Assert::assertStringNotContainsStringIgnoringCase($promise, $html,
                "[{$view}] promises \"{$promise}\" — no marketing page makes a reservation");
        }
    }
});
