<?php

use App\Models\Guest;
use App\Models\Organization;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// Guest Portal · GATE 1 — the MULTI-PROPERTY storefront LANDING (/stay/{org}, founder decision D1).
// Every figure is REAL: org rating (#76), per-town from-price, featured rooms, recent reviews (#28
// byline). Nothing is fabricated (#39): no "369 stays", the rating badge hides with no reviews, an
// unpriced room omits its from-price, and a foreign org's data never appears (tenant isolation).

/** A property in a real town, with a priced room type + rate calendar so a REAL from-price exists. */
function landingProperty(string $town, int $nightlyMinor, string $roomName): array
{
    $property = Property::create([
        'name' => $town.' House',
        'slug' => 'lp-'.Str::lower(Str::random(8)),
        'type' => 'villa',
        'currency' => 'EUR',
        'timezone' => 'UTC',
        'status' => 'active',
        'address' => ['city' => $town, 'country' => 'Portugal'],
    ]);
    $roomType = RoomType::create(['property_id' => $property->id, 'name' => $roomName, 'kind' => 'room', 'max_adults' => 2, 'max_children' => 2]);
    $unit = Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => 'U1']);
    $ratePlan = RatePlan::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'name' => 'Flex', 'currency' => 'EUR']);

    app(AvailabilityService::class)->recompute($roomType->id, '2026-09-01', '2026-09-20');
    app(RateAndRestrictionService::class)->setRate($ratePlan, '2026-09-01', '2026-09-20', Money::of($nightlyMinor, 'EUR'));

    return compact('property', 'roomType', 'unit', 'ratePlan');
}

/** A published, public review on a room type (via a real booking) — feeds the REAL rating + reviews strip. */
function landingReview(array $s, array $p, string $body, int $rating = 5): Review
{
    $guest = Guest::create(['first_name' => 'Marta', 'last_name' => 'Lopes']);
    $res = app(AssignmentService::class)->createBooking(
        ['property_id' => $p['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-02', 'check_out' => '2026-09-05', 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
        [['room_type_id' => $p['roomType']->id, 'unit_id' => $p['unit']->id, 'rate_plan_id' => $p['ratePlan']->id, 'check_in' => '2026-09-02', 'check_out' => '2026-09-05', 'price_minor' => 10000, 'currency' => 'EUR']],
    );

    return Review::create([
        'guest_id' => $guest->getKey(), 'reservation_id' => $res->getKey(),
        'unit_id' => $p['unit']->getKey(), 'room_type_id' => $p['roomType']->getKey(),
        'rating' => $rating, 'tags' => [], 'body' => $body,
        'visibility' => 'public', 'status' => 'published', 'published_at' => now(),
    ]);
}

/** A two-property org (Faro €120 + Lagos €95), tenant context left set to the org. */
function landingOrg(): array
{
    $org = newDemoOrg();
    $faro = landingProperty('Faro', 12000, 'Villa Azure');
    $lagos = landingProperty('Lagos', 9500, 'Casa Marina');

    return ['org' => $org, 'faro' => $faro, 'lagos' => $lagos];
}

// ── 1. the landing renders REAL towns, REAL from-prices, REAL featured rooms ─────────────────────────
it('renders the org’s real towns, from-prices and featured rooms', function () {
    $s = landingOrg();

    $this->get("/stay/{$s['org']->slug}")
        ->assertOk()
        ->assertSee('Where to stay')
        ->assertSee('Faro')->assertSee('Lagos')            // real towns from address.city
        ->assertSee('from €120')->assertSee('from €95')    // real cheapest forward nightly (rate_calendar)
        ->assertSee('Villa Azure')->assertSee('Casa Marina'); // real featured room names
});

// ── 2. #76 — a REAL rating badge + REAL review, and NO fabricated "369 stays" ────────────────────────
it('shows the real derived rating and review, never a fabricated count', function () {
    $s = landingOrg();
    landingReview($s, $s['faro'], 'The balcony caught the sunrise every morning.');

    $response = $this->get("/stay/{$s['org']->slug}")
        ->assertOk()
        ->assertSee('The balcony caught the sunrise every morning.')  // real published review body
        ->assertSee('Marta L.')                                       // #28 first-name + initial byline
        ->assertSee('1 review');                                      // REAL count (one published review)
    // 🪤 the wireframe's fabricated figure — read past the page's random values (a token or ULID can hold "369" by chance).
    expect(withoutRandomTokens((string) $response->getContent()))->not->toContain('369');
});

// ── 3. honesty — no reviews → NO rating badge, an honest empty state (never a fabricated "4.8") ──────
it('hides the rating badge and shows an honest empty state with no reviews', function () {
    $s = landingOrg();

    $response = $this->get("/stay/{$s['org']->slug}")
        ->assertOk()
        ->assertSee('No reviews yet')      // honest empty state
        ->assertDontSee('reviews ·');      // the "N reviews" rating chip is absent (no fabricated 4.8)
    expect(withoutRandomTokens((string) $response->getContent()))->not->toContain('369');   // 🪤 the wireframe's fabricated figure
});

// ── 4. a room with NO rate calendar omits its from-price (never a fabricated price) ─────────────────
it('omits the from-price for an unpriced room rather than inventing one', function () {
    $org = newDemoOrg();
    // A property with a room type + rate plan but NO priced rate calendar.
    $property = Property::create(['name' => 'Tavira House', 'slug' => 'lp-'.Str::lower(Str::random(8)), 'type' => 'villa', 'currency' => 'EUR', 'timezone' => 'UTC', 'status' => 'active', 'address' => ['city' => 'Tavira', 'country' => 'Portugal']]);
    $rt = RoomType::create(['property_id' => $property->id, 'name' => 'Casa Sol', 'kind' => 'room', 'max_adults' => 2, 'max_children' => 0]);
    RatePlan::create(['property_id' => $property->id, 'room_type_id' => $rt->id, 'name' => 'Flex', 'currency' => 'EUR']);

    $this->get("/stay/{$org->slug}")
        ->assertOk()
        ->assertSee('Tavira')
        ->assertSee('Casa Sol')
        ->assertDontSee('from €');   // no priced calendar → no fabricated from-price
});

// ── 5. search — a valid property redirects to its real booking.browse; unknown falls back ───────────
it('redirects the landing search to the chosen property’s real storefront', function () {
    $s = landingOrg();
    $slug = $s['faro']['property']->slug;

    $this->get("/stay/{$s['org']->slug}/search?property={$slug}&check_in=2026-09-02&check_out=2026-09-05&adults=2")
        ->assertRedirect()
        ->assertRedirectContains("/book/{$slug}");

    // An unknown / foreign property slug falls back to the landing (never a cross-org leak).
    $this->get("/stay/{$s['org']->slug}/search?property=does-not-exist")
        ->assertRedirect("/stay/{$s['org']->slug}");
});

// ── 6. tenant isolation — org A's landing never shows org B's town or review ────────────────────────
it('never shows another org’s properties or reviews (tenant-isolated)', function () {
    $a = landingOrg();

    // A separate org with a distinctive town + review.
    $tenant = app(TenantContext::class);
    $bOrg = newDemoOrg();
    $b = landingProperty('Sintra', 20000, 'Palace Suite');
    landingReview(['org' => $bOrg], $b, 'Secret B-org review XYZZY');

    // Re-set context to org A (as its request would) and load A's landing.
    $tenant->set($a['org']->id);

    $this->get("/stay/{$a['org']->slug}")
        ->assertOk()
        ->assertSee('Faro')
        ->assertDontSee('Sintra')                    // org B's town
        ->assertDontSee('Palace Suite')              // org B's room
        ->assertDontSee('Secret B-org review XYZZY'); // org B's review
});

// ── 7. a suspended org's public landing is down (404) ───────────────────────────────────────────────
it('404s the landing for a suspended org', function () {
    $s = landingOrg();
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($s['org']->id)->update(['status' => 'suspended']));

    $this->get("/stay/{$s['org']->slug}")->assertNotFound();
});

// ── ⚠️ G12 — A DE-LISTED PROPERTY IS OFF THE PUBLIC INTERNET, ON BOTH STOREFRONTS ──────────────────
// `properties.status` is an enum of exactly two values, and `inactive` is how an operator takes a property
// off sale — renovation, a lost contract, off-boarding. Two independent places enforce it: the landing
// filters `where('status','active')`, and `ResolvePublicProperty` re-checks it before setting tenant context.
//
// ⚠️ NEITHER WAS DEFENDED. No test on either storefront ever seeded an inactive property, so deleting either
// filter left the whole suite green — while re-publishing a withdrawn property's public listing AND its
// entire booking funnel (browse · room · search · quote · checkout · reserve), because every one of those
// routes is gated by that single middleware line. The tenant scope was well covered; the SELL scope was not.
it('never shows a de-listed (inactive) property on the org landing', function () {
    $s = landingOrg();
    $gone = landingProperty('Sagres', 8000, 'Withdrawn Suite');
    $gone['property']->update(['status' => 'inactive']);

    $html = $this->get("/stay/{$s['org']->slug}")->assertOk()
        ->assertSee('Faro')                 // the live towns still render…
        ->assertSee('Lagos')
        ->assertDontSee('Sagres')           // …and the withdrawn town does not
        ->assertDontSee('Withdrawn Suite')
        ->getContent();

    // Its from-price must not survive in a town tile or a featured card either.
    expect($html)->not->toContain('€80.00')->and($html)->not->toContain('from €80');
});

it('404s every public booking route of a de-listed property, not just its listing', function () {
    $s = landingOrg();
    $gone = landingProperty('Sagres', 8000, 'Withdrawn Suite');
    $slug = (string) $gone['property']->slug;

    // Live while active — so the 404s below can never pass vacuously on a bad slug.
    $this->get("/book/{$slug}")->assertOk();

    $gone['property']->update(['status' => 'inactive']);

    $this->get("/book/{$slug}")->assertNotFound();                                        // browse
    $this->get("/book/{$slug}/search")->assertNotFound();                                 // search
    $this->get("/book/{$slug}/rooms/{$gone['roomType']->id}")->assertNotFound();          // room detail
    $this->get("/book/{$slug}/checkout")->assertNotFound();                               // checkout
    $this->post("/book/{$slug}/quote", [])->assertNotFound();                             // the quote write
});
