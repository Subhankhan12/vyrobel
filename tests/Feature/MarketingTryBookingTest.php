<?php

use App\Models\Organization;
use App\Models\Property;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\BookingEngineService;
use App\Support\Marketing\TryBookingSandbox;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

// FRONT-WEBSITE GATE 4 · THE TRY-BOOKING SANDBOX GUARD (ruling D3 = (b)).
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

uses(RefreshDatabase::class);

beforeEach(function () {
    tbSeedDemoProperty();
});

/** The page as a visitor gets it. */
function tbPage(): string
{
    return view('marketing.try-booking')->render();
}

/** True when this environment actually has the demo property to mirror. */
function tbHasDemo(): bool
{
    return TryBookingSandbox::data() !== null;
}

/**
 * ⚠️ REFUSES TO BE VACUOUS. Every assertion below is meaningless without a property to mirror, and the
 * early-return that used to handle that turned the whole file into a no-op. Now the absence is itself a
 * failure.
 */
function tbAssertNotVacuous(): array
{
    $data = TryBookingSandbox::data();
    Assert::assertNotNull($data, 'the sandbox has no demo property to mirror — every assertion in this file would be vacuous');

    return $data;
}

/**
 * Seed a property shaped like the real `seaside-demo`: the same slug, a room type, a rate plan, and a run
 * of published nightly rates. ⚠️ The RATES are what matter — the sandbox prices through the engine's own
 * `priceOver()`, so without published nights it returns null and the page shows its honest empty state.
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

        // Ten consecutive published nights from today, at a flat €100 — the same shape the demo seeds.
        for ($i = 0; $i < 10; $i++) {
            RateCalendar::query()->create([
                'organization_id' => $org->getKey(),
                'rate_plan_id' => $ratePlan->getKey(),
                'date' => CarbonImmutable::now()->addDays($i)->toDateString(),
                'price_minor' => 10000,
                'currency' => 'EUR',
            ]);
        }
    });
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

// ══ CONSTRAINT 5 — the price comes from the ENGINE'S OWN source ════════════════════════════════════
it('⚠️ shows the price the engine itself computes, not a second computation', function () {
    $data = tbAssertNotVacuous();

    // ⚠️ Recompute through the ENGINE, independently of the sandbox (§N.18.1 — the check must not run
    // through the thing it checks), and require agreement to the cent.
    $engineTotal = app(TenantContext::class)->runAsSuperAdmin(function () use ($data): ?int {
        $ratePlan = RatePlan::query()
            ->whereHas('roomType', fn ($q) => $q->where('property_id', $data['property']->getKey()))
            ->orderBy('name')->first();

        return app(BookingEngineService::class)->priceOver(
            (string) $ratePlan->getKey(),
            $data['checkIn']->toDateString(),
            $data['checkOut']->toDateString(),
        );
    });

    Assert::assertSame($engineTotal, $data['totalMinor'], 'the sandbox total disagrees with the engine');
    Assert::assertStringContainsString($data['total'], tbPage(), 'the page does not show the engine-derived total');
});

// ══ ⚠️ CONSTRAINT 3 + #33 — the REAL mail, with nothing actionable in it ═══════════════════════════
it('⚠️ renders the REAL confirmation mail with every link defused (#33)', function () {
    tbAssertNotVacuous();

    $preview = TryBookingSandbox::confirmationPreview();
    Assert::assertNotNull($preview);

    // It is the REAL template: the mail's own heading and reconciling total are present.
    Assert::assertStringContainsString(TryBookingSandbox::data()['total'], $preview, 'the preview is not showing the real reconciling total');
    Assert::assertStringContainsString(TryBookingSandbox::SANDBOX_REFERENCE, $preview, 'the preview does not carry the sandbox reference');

    // ⚠️ AND NOTHING IN IT CAN BE ACTIONED. `arrivalUrl` is the arrival-secrets page (#33); the rest carry
    // a reservation identifier. None may survive into a public page.
    //
    // ⚠️ NARROWED, AND THE NARROWING IS THE POINT. A blanket `http://` needle fired on
    // `xmlns="http://www.w3.org/1999/xhtml"` — an XML namespace declaration, not a link. A guard that
    // cries wolf on a namespace gets loosened by the next person; one that names what it actually forbids
    // survives. So: the guest-realm paths themselves, and any absolute link back to this application.
    // ⚠️ THE NEEDLES ARE THE ROUTES' OWN PATHS, derived — not guessed. A hand-written "/guest/arrival"
    // would never have matched: the real path is "/guest/reservation/{id}/arrival". A guard whose needle
    // cannot occur is the purest form of vacuity, and this one protects the arrival-secrets window (#33).
    foreach (['guest.receipt', 'guest.manage', 'guest.arrival', 'guest.checkin'] as $routeName) {
        $path = (string) parse_url(route($routeName, TryBookingSandbox::SANDBOX_REFERENCE), PHP_URL_PATH);
        Assert::assertNotSame('', $path);
        Assert::assertStringNotContainsString($path, $preview, "the mail preview leaks the actionable path \"{$path}\"");
    }
    Assert::assertStringNotContainsString('href="'.rtrim(url('/'), '/'), $preview,
        'the mail preview links back into this application');
    Assert::assertDoesNotMatchRegularExpression('/href="https?:\/\/(?!www\.w3\.org)/i', $preview,
        'the mail preview carries an absolute outbound link');
    // …and the same must hold of the whole rendered PAGE, not just the preview fragment.
    // ⚠️ SCOPED TO THE ACTIONABLE PATHS, not to "/guest/" as a whole: the footer carries a legitimate
    // "Booked a stay? Guest sign-in" link to /guest/login (ruling D9, Gate 1). A blanket needle fired on
    // that honest link — and the fix is to name what is actually forbidden (a per-reservation deep link),
    // not to weaken the guard.
    $html = tbPage();
    // ⚠️ MATCH THE ROUTE *PATTERN*, NOT ONE KEY. The first version substituted the SANDBOX reference into
    // the route and searched for that exact string — so a leak carrying a REAL reservation id (which is
    // the leak that would actually matter) sailed straight past it. Mutation caught it: planting
    // `/guest/reservation/X/arrival` left the guard green. The per-reservation prefix is what is
    // forbidden; `/guest/login` in the footer is a different path and stays legitimate (ruling D9).
    Assert::assertStringNotContainsString('/guest/reservation/', $html,
        'the page carries a per-reservation guest deep link — the #33 surface');
});

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

it('⚠️ frames the sandbox prominently, above the content', function () {
    $html = tbPage();

    Assert::assertStringContainsString('data-sandbox-banner', $html, 'the sandbox banner is gone');
    Assert::assertStringContainsString('This is a preview, not a booking', $html);
    Assert::assertStringContainsString('Nothing on this page reserves a room', $html);

    // ⚠️ PROMINENCE IS POSITIONAL. The banner must sit ABOVE the confirmation preview — the point where
    // the illusion is strongest is exactly where a footnote would be too late.
    $banner = strpos($html, 'data-sandbox-banner');
    $preview = strpos($html, 'data-mail-preview');
    Assert::assertNotFalse($banner);
    if ($preview !== false) {
        Assert::assertLessThan($preview, $banner, 'the sandbox framing sits BELOW the confirmation preview');
    }
});

// ══ ⚠️ NO WRITES — rendering the page must change nothing ══════════════════════════════════════════
it('⚠️ writes nothing: no reservation, no quote row, no folio', function () {
    $counts = fn (): array => app(TenantContext::class)->runAsSuperAdmin(fn (): array => [
        'reservations' => DB::table('reservations')->count(),
        'booking_quotes' => DB::table('booking_quotes')->count(),
        'folios' => DB::table('folios')->count(),
        'payments' => DB::table('payments')->count(),
    ]);

    $before = $counts();
    tbPage();
    tbPage();   // twice — a write that only happens once would hide behind a single render
    $after = $counts();

    // ⚠️ The engine's quote() persists a booking_quotes row; the sandbox calls only its read-only
    // priceOver() half. If someone ever "simplifies" that to quote(), this reds immediately.
    Assert::assertSame($before, $after, 'rendering the sandbox wrote to the database');
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
    Assert::assertStringNotContainsString('Pick your dates', $source, 'the hero invites an interaction the page no longer has');
});

// ══ ⚠️ THE SITEWIDE SWEEP — a ruling scoped to one page reaches neither the chrome nor its neighbours ══
it('⚠️ promises a booking on NO marketing page, and not in the chrome', function () {
    // ⚠️ THIS SWEEP FOUND A REAL DEFECT THE RULING COULD NOT. D3 was scoped to /try-booking, but the NAV's
    // own row for it read "A real booking, no signup" — rendered on EVERY page, promising exactly what the
    // rebuilt page refuses to do. Gate 3b had already learned this shape (three live Sales pages carrying
    // a claim deleted elsewhere); this is the same lesson, made permanent.
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
