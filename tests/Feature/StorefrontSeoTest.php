<?php

use App\Http\Controllers\StorefrontSitemapController;
use App\Models\Organization;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\User;
use App\Services\BookingEngineService;
use App\Services\TenantProvisioningService;
use App\Support\Storefront\StorefrontOffer;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Assert;

// ══ STOREFRONT SEO (D10) ══════════════════════════════════════════════════════════════════════════════
//
// ⚠️ WHAT THIS FILE PROTECTS, IN ORDER OF HOW BADLY IT WOULD HURT:
//   1. THE MONEY PATH. The whole pass is additive `<head>` output on a SEALED, money-carrying surface. If
//      the storefront stops resolving or stops booking, nothing else here matters.
//   2. THE RATING CLAIM. `aggregateRating` is a machine-readable assertion about real customers. A
//      fabricated one shipped on 201 pages in another of this founder's products. The guard below is
//      MUTATION-PROVEN in both directions: it must fire when a rating is planted onto a review-less
//      property, and it must NOT fire on a property with real reviews (or it proves nothing).
//   3. THE SITEMAP'S TWO DIRECTIONS. A sitemap that lists an unsellable property lands a visitor on an
//      empty page; a sitemap that omits a sellable one silently costs the tenant the traffic. Both are
//      proven, because proving only inclusion would leave the exclusion rule untested and vice versa.
//
// ⚠️ EVERY GUARD HERE CARRIES ITS OWN VACUITY CHECK (§N.31.1). Eight vacuity shapes have shipped green in
// this project, two of them by measuring the WRONG SIDE of the thing they protected. So each assertion
// below is accompanied by a positive control that proves its needle can fire at all.

uses(RefreshDatabase::class);

/**
 * A tenant provisioned exactly as `/signup` provisions, made SELLABLE through the application's own HTTP
 * paths — no seeder, no factory on the sellable chain (the P-6 Gate 1 discipline).
 *
 * @return array{user: User, property: Property, org_id: string, room_type: RoomType, check_in: string, check_out: string}
 */
function seoSellableTenant(string $suffix, string $location = 'Faro', string $rate = '120.00'): array
{
    $result = app(TenantProvisioningService::class)->provision(
        organization: ['name' => "SEO Org {$suffix}", 'country' => 'PT', 'currency' => 'EUR'],
        founder: ['name' => "Founder {$suffix}", 'email' => "seo-{$suffix}@storefront.test", 'password' => 'Str0ng-Passw0rd!'],
        property: ['name' => "SEO Property {$suffix}", 'type' => 'hotel', 'location' => $location],
    );

    $user = $result['user'];
    $property = $result['property'];
    $orgId = (string) $result['organization']->getKey();

    // ⚠️ The rates must cover EVERY night StorefrontOffer probes, or the property reads as unsellable and
    // the sitemap assertions below would be testing the wrong thing. The probe ladder reaches +33 days.
    $start = CarbonImmutable::now()->startOfDay();
    $end = $start->addDays(60);

    test()->actingAs($user)->post(route('app.inventory.room-types.store'), [
        'name' => 'Double Room', 'kind' => 'room',
        'max_adults' => 2, 'max_children' => 1, 'std_occupancy' => 2,
    ])->assertRedirect();

    $roomType = app(TenantContext::class)->forOrganization($orgId,
        fn () => RoomType::query()->where('property_id', $property->getKey())->firstOrFail());

    test()->actingAs($user)->post(route('app.inventory.units.store'), [
        'room_type_id' => (string) $roomType->getKey(), 'code' => '101', 'name' => 'Room 101',
    ])->assertRedirect();

    test()->actingAs($user)->post(route('app.inventory.rate-plans.store'), [
        'room_type_id' => (string) $roomType->getKey(), 'name' => 'Flexible', 'is_refundable' => '1',
    ])->assertRedirect();

    test()->actingAs($user)->post(route('app.rates.update'), [
        'room_type_id' => (string) $roomType->getKey(),
        'start_date' => $start->toDateString(),
        'end_date' => $end->toDateString(),
        'set_rate' => '1',
        'rate' => $rate,
    ])->assertRedirect();

    return [
        'user' => $user,
        'property' => $property->fresh(),
        'org_id' => $orgId,
        'room_type' => $roomType,
        'check_in' => $start->addDays(5)->toDateString(),
        'check_out' => $start->addDays(7)->toDateString(),
    ];
}

/** Extract and DECODE the one JSON-LD graph on a page — failing loudly if it is absent or malformed. */
function seoJsonLd(string $html): array
{
    preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m);

    Assert::assertCount(1, $m[1], 'expected exactly one JSON-LD block on the page');

    $decoded = json_decode($m[1][0], true);

    Assert::assertIsArray($decoded, '⚠️ the JSON-LD did not PARSE — rendering is not validity');

    return $decoded;
}

/**
 * Complete a REAL booking through the certified engine and return its reservation.
 *
 * ⚠️ NEEDED BECAUSE `reviews.guest_id` AND `reviews.reservation_id` ARE NOT NULL. That is the product's own
 * integrity rule — a review is structurally tied to a real stay by a real guest (#76), so there is no such
 * thing as a free-floating review to fixture up. ⚠️ The first draft of this file tried to create one and hit
 * a 1364; the FIXTURE was fixed, never the rule (§N.27.7).
 *
 * @param  array{property: Property, org_id: string, check_in: string, check_out: string}  $t
 */
function seoBooking(array $t): Reservation
{
    return app(TenantContext::class)->forOrganization($t['org_id'], function () use ($t) {
        $engine = app(BookingEngineService::class);
        $offers = $engine->search($t['property'], $t['check_in'], $t['check_out'], 1, 0);

        Assert::assertNotEmpty($offers, 'the fixture tenant is not sellable');

        $quote = $engine->quote(
            $t['property'], (string) $offers[0]['room_type_id'], (string) $offers[0]['rate_plan_id'],
            $t['check_in'], $t['check_out'], 1, 0, 'reviewer@storefront.test',
        );

        $result = $engine->book($quote, 'tok_test', [
            'first_name' => 'Rev', 'last_name' => 'Iewer', 'email' => 'reviewer@storefront.test',
        ], takePayment: false);

        return is_array($result) ? $result['reservation'] : $result;
    });
}

// ══ ① THE MONEY PATH — the storefront still resolves AND still books ═══════════════════════════════════

it('⚠️ still resolves every storefront route and still COMPLETES A BOOKING with the SEO head in place', function () {
    $t = seoSellableTenant('books');
    $slug = (string) $t['property']->slug;

    // ── the four public HTML surfaces still render ────────────────────────────────────────────────────
    $this->get("/book/{$slug}")->assertOk();
    $this->get("/book/{$slug}/rooms/{$t['room_type']->getKey()}")->assertOk();
    $this->get("/book/{$slug}/checkout?room_type_id={$t['room_type']->getKey()}"
        ."&check_in={$t['check_in']}&check_out={$t['check_out']}&adults=2&children=0")->assertOk();
    $this->get('/stay/'.app(TenantContext::class)->forOrganization($t['org_id'],
        fn () => Organization::query()->withoutGlobalScopes()->findOrFail($t['org_id'])->slug))->assertOk();

    // ── and the money path itself still completes ─────────────────────────────────────────────────────
    $reservation = app(TenantContext::class)->forOrganization($t['org_id'], function () use ($t) {
        $engine = app(BookingEngineService::class);
        $offers = $engine->search($t['property'], $t['check_in'], $t['check_out'], 2, 0);

        Assert::assertNotEmpty($offers, 'the tenant is not sellable — the fixture, not the feature, is broken');

        $quote = $engine->quote(
            $t['property'], (string) $offers[0]['room_type_id'], (string) $offers[0]['rate_plan_id'],
            $t['check_in'], $t['check_out'], 2, 0, 'guest@storefront.test',
        );

        return $engine->book($quote, 'tok_test', [
            'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'guest@storefront.test',
        ], takePayment: false);
    });

    $model = is_array($reservation) ? ($reservation['reservation'] ?? null) : $reservation;

    Assert::assertNotNull($model, '⚠️ THE BOOKING DID NOT COMPLETE — the SEO pass has reached the money path');
    Assert::assertNotEmpty($model->reference);
});

// ══ ② THE RATING CLAIM — proven in BOTH directions ═════════════════════════════════════════════════════

it('⚠️ emits NO aggregateRating for a property with no published reviews', function () {
    $t = seoSellableTenant('norating');

    $graph = seoJsonLd($this->get('/book/'.$t['property']->slug)->getContent());

    expect($graph)->not->toHaveKey('aggregateRating');
    expect($graph)->not->toHaveKey('starRating');
})->group('storefront-seo');

it('⚠️ PROVES THE RATING NEEDLE CAN FIRE — a real published review DOES produce an aggregateRating', function () {
    // ⚠️ THE VACUITY CHECK FOR THE TEST ABOVE. Without this, "no aggregateRating" would pass just as
    // happily on a page that can never emit one — the assertion would be structurally incapable of failing
    // and would still report green (§N.31.1 shape 3).
    $t = seoSellableTenant('hasrating');

    $reservation = seoBooking($t);

    app(TenantContext::class)->forOrganization($t['org_id'], function () use ($t, $reservation) {
        Review::query()->create([
            'organization_id' => $t['org_id'],
            'guest_id' => (string) $reservation->lead_guest_id,
            'reservation_id' => (string) $reservation->getKey(),
            'room_type_id' => (string) $t['room_type']->getKey(),
            'rating' => 5,
            'body' => 'A real published review.',
            'status' => Review::STATUS_PUBLISHED,
            'visibility' => Review::VISIBILITY_PUBLIC,
        ]);
    });

    $graph = seoJsonLd($this->get('/book/'.$t['property']->slug)->getContent());

    expect($graph)->toHaveKey('aggregateRating');
    expect($graph['aggregateRating']['reviewCount'])->toBe(1);
    expect($graph['aggregateRating']['ratingValue'])->toEqual(5);
})->group('storefront-seo');

it('⚠️ keeps a PENDING or PRIVATE review out of the rating — the claim tracks what is actually published', function () {
    $t = seoSellableTenant('hiddenrating');

    $reservation = seoBooking($t);

    app(TenantContext::class)->forOrganization($t['org_id'], function () use ($t, $reservation) {
        Review::query()->create([
            'organization_id' => $t['org_id'],
            'guest_id' => (string) $reservation->lead_guest_id,
            'reservation_id' => (string) $reservation->getKey(),
            'room_type_id' => (string) $t['room_type']->getKey(),
            'rating' => 5,
            'body' => 'Not published yet.',
            'status' => Review::STATUS_PENDING,
            'visibility' => Review::VISIBILITY_PUBLIC,
        ]);
    });

    $graph = seoJsonLd($this->get('/book/'.$t['property']->slug)->getContent());

    expect($graph)->not->toHaveKey('aggregateRating');
})->group('storefront-seo');

it('⚠️ omits starRating unless the tenant actually set one, and emits it when they did', function () {
    $t = seoSellableTenant('stars');

    expect(seoJsonLd($this->get('/book/'.$t['property']->slug)->getContent()))->not->toHaveKey('starRating');

    // The positive control: a REAL tenant-set value must actually reach the graph, or the omission above
    // proves only that the field is unreachable.
    app(TenantContext::class)->forOrganization($t['org_id'],
        fn () => Property::query()->whereKey($t['property']->getKey())->update(['star_rating' => 4]));

    $graph = seoJsonLd($this->get('/book/'.$t['property']->slug)->getContent());

    expect($graph['starRating']['ratingValue'])->toBe(4);
})->group('storefront-seo');

// ══ ③ THE OFFER PRICE — bound to the engine, and to what the page shows ════════════════════════════════

it('⚠️ prices the Offer from the ENGINE and at the SAME total the page displays (#17, B12)', function () {
    $t = seoSellableTenant('price', rate: '100.00');

    $graph = seoJsonLd($this->get('/book/'.$t['property']->slug)->getContent());

    expect($graph)->toHaveKey('makesOffer');
    $offer = $graph['makesOffer'];

    // The window is part of the claim — a price with no dates is an unbounded assertion.
    expect($offer)->toHaveKeys(['lowPrice', 'priceCurrency', 'availabilityStarts', 'availabilityEnds']);

    // ⚠️ THE INDEPENDENT CHECK: re-derive the number from the certified probe, then require the RENDERED
    // page to contain that exact total. Comparing the graph against the builder that made it would be
    // checking the source against itself — the `/pricing` vacuity (§N.31.1 shape 8).
    $probe = app(TenantContext::class)->forOrganization($t['org_id'],
        fn () => app(StorefrontOffer::class)->probe($t['property']));

    Assert::assertNotNull($probe);

    $expected = number_format($probe['low_minor'] / 100, 2, '.', '');
    expect($offer['lowPrice'])->toBe($expected);

    $rendered = $this->get('/book/'.$t['property']->slug
        ."?check_in={$probe['check_in']}&check_out={$probe['check_out']}&adults=1&children=0")->getContent();

    expect($rendered)->toContain($expected.' total');
})->group('storefront-seo');

it('⚠️ claims NO price on the canonical dateless room URL, and a real one once dates are supplied', function () {
    $t = seoSellableTenant('roomoffer');
    $url = '/book/'.$t['property']->slug.'/rooms/'.$t['room_type']->getKey();

    expect(seoJsonLd($this->get($url)->getContent()))->not->toHaveKey('offers');

    $dated = seoJsonLd($this->get($url."?check_in={$t['check_in']}&check_out={$t['check_out']}&adults=2")->getContent());

    expect($dated)->toHaveKey('offers');
    expect($dated['offers']['availabilityStarts'])->toBe($t['check_in']);
})->group('storefront-seo');

// ══ ④ CANONICAL + ROBOTS ══════════════════════════════════════════════════════════════════════════════

it('⚠️ canonicalises every query-string variant of a property page to the bare URL', function () {
    $t = seoSellableTenant('canon');
    $bare = route('booking.browse', ['property' => (string) $t['property']->slug]);

    $noisy = $this->get('/book/'.$t['property']->slug
        .'?check_in='.$t['check_in'].'&check_out='.$t['check_out'].'&adults=2&sort=price_asc&bedrooms=1')->getContent();

    expect($noisy)->toContain('<link rel="canonical" href="'.$bare.'">');
})->group('storefront-seo');

it('⚠️ NOINDEXES the transactional pages — a booking confirmation must never be indexable', function () {
    $t = seoSellableTenant('noindex');

    $checkout = $this->get('/book/'.$t['property']->slug.'/checkout?room_type_id='.$t['room_type']->getKey()
        ."&check_in={$t['check_in']}&check_out={$t['check_out']}&adults=2&children=0")->getContent();

    expect($checkout)->toContain('content="noindex, nofollow"');

    // The positive control: an indexable page must really say so, or "noindex" proves nothing about the
    // directive being computed rather than hardcoded.
    expect($this->get('/book/'.$t['property']->slug)->getContent())
        ->toContain('content="index, follow"');
})->group('storefront-seo');

it('⚠️ renders exactly ONE title element per storefront page', function () {
    // ⚠️ The shell composes a bare <title> of its own. Suppressing it when a :seo payload arrives is the
    // kind of edit that silently leaves two title tags — invisible to every other assertion in this file.
    $t = seoSellableTenant('titles');
    $slug = (string) $t['property']->slug;

    foreach ([
        "/book/{$slug}",
        "/book/{$slug}/rooms/{$t['room_type']->getKey()}",
        "/book/{$slug}/checkout?room_type_id={$t['room_type']->getKey()}&check_in={$t['check_in']}&check_out={$t['check_out']}&adults=2&children=0",
    ] as $url) {
        expect(substr_count($this->get($url)->getContent(), '<title>'))->toBe(1, "two titles on {$url}");
    }
})->group('storefront-seo');

// ══ ⑤ THE SITEMAP — both directions, plus its own vacuity check ════════════════════════════════════════

it('⚠️ lists a SELLABLE property — and DROPS it the moment it stops being sellable', function () {
    $t = seoSellableTenant('sitemap');
    $url = route('booking.browse', ['property' => (string) $t['property']->slug]);

    Cache::forget(StorefrontSitemapController::CACHE_KEY);
    expect($this->get('/sitemap-storefront.xml')->getContent())->toContain($url);

    // ── DIRECTION 2: make it genuinely unsellable through the engine's own chain (deactivate the rate
    // plan → cheapestRatePlan() finds none → search() returns nothing) and require it to LEAVE.
    app(TenantContext::class)->forOrganization($t['org_id'],
        fn () => RatePlan::query()->where('room_type_id', $t['room_type']->getKey())->update(['is_active' => false]));

    Cache::forget(StorefrontSitemapController::CACHE_KEY);
    expect($this->get('/sitemap-storefront.xml')->getContent())->not->toContain($url);
})->group('storefront-seo');

it('⚠️ never lists a property whose page would 404 (status != active)', function () {
    $t = seoSellableTenant('inactive');
    $url = route('booking.browse', ['property' => (string) $t['property']->slug]);

    Cache::forget(StorefrontSitemapController::CACHE_KEY);
    expect($this->get('/sitemap-storefront.xml')->getContent())->toContain($url);

    app(TenantContext::class)->forOrganization($t['org_id'],
        fn () => Property::query()->whereKey($t['property']->getKey())->update(['status' => 'inactive']));

    Cache::forget(StorefrontSitemapController::CACHE_KEY);
    $xml = $this->get('/sitemap-storefront.xml')->getContent();

    expect($xml)->not->toContain($url);
    // ⚠️ AND the page really does 404 — which is WHY it must not be listed. Asserting the omission alone
    // would not establish that the sitemap rule matches the routing rule.
    $this->get('/book/'.$t['property']->slug)->assertNotFound();
})->group('storefront-seo');

it('⚠️ THE SITEMAP GUARD IS NOT VACUOUS — an empty sellable set yields an EMPTY urlset', function () {
    // ⚠️ THE VACUITY CHECK. `assertDontSee($url)` passes trivially against a sitemap that lists nothing at
    // all — including one produced by a crashed builder. This pins the empty case explicitly, so a sitemap
    // that silently stopped enumerating could not masquerade as a correct exclusion (§N.31.1 shape 5, and
    // §N.31.2 — an empty result from a broken instrument is an artefact, not a verdict).
    Cache::forget(StorefrontSitemapController::CACHE_KEY);
    $xml = $this->get('/sitemap-storefront.xml')->assertOk()->getContent();

    expect($xml)->toContain('<urlset');
    expect($xml)->not->toContain('<loc>');

    // And the document is still well-formed XML rather than an error page with a 200 on it.
    Assert::assertNotFalse(simplexml_load_string($xml), 'the empty sitemap is not valid XML');
})->group('storefront-seo');

it('⚠️ lists an organisation landing ONLY when it has at least one sellable property', function () {
    $t = seoSellableTenant('orgmap');

    $orgSlug = app(TenantContext::class)->forOrganization($t['org_id'],
        fn () => (string) Organization::query()->withoutGlobalScopes()->findOrFail($t['org_id'])->slug);

    Cache::forget(StorefrontSitemapController::CACHE_KEY);
    expect($this->get('/sitemap-storefront.xml')->getContent())
        ->toContain(route('storefront.landing', ['organization' => $orgSlug]));

    app(TenantContext::class)->forOrganization($t['org_id'],
        fn () => RatePlan::query()->where('room_type_id', $t['room_type']->getKey())->update(['is_active' => false]));

    Cache::forget(StorefrontSitemapController::CACHE_KEY);
    expect($this->get('/sitemap-storefront.xml')->getContent())
        ->not->toContain(route('storefront.landing', ['organization' => $orgSlug]));
})->group('storefront-seo');

it('⚠️ keeps the storefront ALLOWED in robots.txt', function () {
    // ⚠️ The whole pass is pointless if a later robots edit disallows the very pages it makes findable, and
    // nothing else in the suite reads this file. Deliberately does NOT assert the marketing rules — those
    // belong to SEO finalisation.
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->not->toContain('Disallow: /book');
    expect($robots)->not->toContain('Disallow: /stay');
    expect($robots)->not->toContain("Disallow: /\n");
})->group('storefront-seo');

// ══ ⑥ HONEST DEGRADATION — the normal case for a real new tenant ══════════════════════════════════════

it('⚠️ produces a VALID graph for a freshly provisioned tenant with almost nothing filled in', function () {
    // ⚠️ THIS IS THE REALISTIC CASE, NOT AN EDGE CASE. A tenant provisioned through /signup has no
    // description column to draw on (none exists), no lat/lng, no star rating, no photos and no reviews.
    $t = seoSellableTenant('minimal', location: '');

    $html = $this->get('/book/'.$t['property']->slug)->getContent();
    $graph = seoJsonLd($html);

    expect($graph)->toHaveKeys(['@context', '@type', 'name', 'url']);
    expect($graph)->not->toHaveKey('aggregateRating');
    expect($graph)->not->toHaveKey('starRating');
    expect($graph)->not->toHaveKey('geo');
    expect($graph)->not->toHaveKey('address');

    // No photo → NO og:image, and the card degrades to `summary` rather than pointing at a placeholder.
    expect($html)->not->toContain('property="og:image"');
    expect($html)->toContain('name="twitter:card" content="summary"');

    // The description still says something true rather than collapsing to an empty tag.
    expect($html)->toContain('<meta name="description" content="Book SEO Property minimal direct.');
})->group('storefront-seo');
