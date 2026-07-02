<?php

use App\Models\Channel;
use App\Models\ChannelMapping;
use App\Models\Reservation;
use App\Services\BookingEngineService;
use App\Services\Channex\ChannelManagerClient;
use App\Services\Channex\FakeChannexClient;
use App\Support\Tenancy\TenantContext;

// Phase 2 Step 7 — public booking engine (backend + unstyled contract stub). Does NOT use
// RefreshDatabase: book routes through AssignmentService and the post-commit ARI push only fires
// on a real commit (same reasoning as AriPushTest). Each test cleans up afterward. Channex/Stripe
// are the in-memory fakes — no network. The booking engine is a pure consumer: advisory holds
// never touch sold, and inventory is occupied only at BOOK time through AssignmentService.

beforeEach(fn () => flushQueue());
afterEach(fn () => cleanupCommittedTenantData());

it('public search returns availability + prices and never leaks another tenant\'s inventory', function () {
    $a = publicPropertyScenario(units: 2);
    $b = publicPropertyScenario(units: 2); // a different org + property

    $response = test()->getJson("/book/{$a['property']->slug}/search?check_in=2026-09-01&check_out=2026-09-03&adults=2");

    $response->assertOk();
    $offers = $response->json('data');

    expect($offers)->toHaveCount(1)
        ->and($offers[0]['room_type_id'])->toBe($a['roomType']->id)
        ->and($offers[0]['available'])->toBe(2)
        ->and($offers[0]['total_minor'])->toBe(24000)               // 2 nights × 12000
        ->and($offers[0]['currency'])->toBe('EUR');

    // Property B's room_type is never present in A's search results.
    expect(collect($offers)->pluck('room_type_id'))->not->toContain($b['roomType']->id);
});

it('a quote is an advisory hold that does not touch sold, and expiring it marks the quote abandoned (still no sold change)', function () {
    $s = publicPropertyScenario(units: 1);
    $engine = app(BookingEngineService::class);

    expect(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);

    $quote = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'g@example.com');

    expect($quote->status)->toBe('held')
        ->and($quote->held_until)->not->toBeNull()
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0)   // advisory — sold untouched
        ->and(soldOn($s['roomType']->id, '2026-09-02'))->toBe(0);

    // Past expiry, the sweep marks it abandoned — still no inventory was ever held.
    $this->travel(31)->minutes();
    $swept = $engine->expireStaleQuotes();

    expect($swept)->toBe(1)
        ->and($quote->refresh()->status)->toBe('abandoned')
        ->and($quote->recovery_status)->toBe('pending')
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);
});

it('book+pay routes through AssignmentService (sold++ at confirm), pays via Stripe through FolioService, and pushes ARI outward', function () {
    $s = publicPropertyScenario(units: 1);

    // A connected channel + mapping so the booking's domain event produces an outward ARI push.
    $channel = Channel::factory()->create(['property_id' => $s['property']->id, 'external_property_id' => 'chx-p']);
    ChannelMapping::factory()->roomType($s['roomType'])->create(['channel_id' => $channel->id, 'external_id' => 'chx-rt']);

    $engine = app(BookingEngineService::class);
    $quote = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'guest@example.com');

    expect(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0); // still nothing sold at hold time

    $result = $engine->book($quote, 'pm_card_visa', ['email' => 'guest@example.com', 'first_name' => 'Pat']);

    expect($result['status'])->toBe('confirmed')
        ->and($result['reservation']->source)->toBe('direct')
        ->and($result['payment']->gateway)->toBe('stripe')
        ->and($result['payment']->status)->toBe('succeeded')
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1)   // sold++ at CONFIRM, not at hold
        ->and(soldOn($s['roomType']->id, '2026-09-02'))->toBe(1)
        ->and($quote->refresh()->status)->toBe('converted');

    // Folio settled from the ledger; reservation shows paid.
    $reservation = $result['reservation'];
    expect($reservation->refresh()->payment_status)->toBe('paid');

    // Outward ARI push fired (loop closed).
    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);
    expect($fake->pushCount())->toBeGreaterThanOrEqual(1)
        ->and($fake->pushesFor($s['roomType']->id))->not->toBeEmpty();
});

it('two advisory holds on the last unit both succeed, but only ONE conversion seats; the second is cleanly rejected (never overbooks)', function () {
    $s = publicPropertyScenario(units: 1); // ONE unit
    $engine = app(BookingEngineService::class);

    $q1 = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'a@example.com');
    $q2 = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'b@example.com');

    // Both advisory holds succeeded, nothing sold.
    expect($q1->status)->toBe('held')->and($q2->status)->toBe('held')
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);

    $r1 = $engine->book($q1, 'pm_card_visa', ['email' => 'a@example.com']);
    $r2 = $engine->book($q2, 'pm_card_visa', ['email' => 'b@example.com']);

    expect($r1['status'])->toBe('confirmed')
        ->and($r2['status'])->toBe('unavailable')                  // direct rejects when full
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1)   // never 2 — no overbook
        ->and(Reservation::query()->where('property_id', $s['property']->id)->where('status', 'confirmed')->count())->toBe(1);
});

it('public tenant isolation: a request in property A\'s context cannot read or book property B\'s data', function () {
    $a = publicPropertyScenario(units: 1);
    $b = publicPropertyScenario(units: 1);

    // A quote that belongs to property B.
    $quoteB = app(BookingEngineService::class)->quote($b['property'], $b['roomType']->id, $b['ratePlan']->id, '2026-09-01', '2026-09-03', 1, 0);

    // Booking B's quote through A's public endpoint must not resolve it (property-scoped lookup).
    test()->postJson("/book/{$a['property']->slug}/book", [
        'quote_id' => $quoteB->id,
        'payment_token' => 'pm_card_visa',
        'email' => 'x@example.com',
    ])->assertNotFound();

    // And A's search never exposes B's room_type.
    $offers = test()->getJson("/book/{$a['property']->slug}/search?check_in=2026-09-01&check_out=2026-09-03")->json('data');
    expect(collect($offers)->pluck('room_type_id'))->not->toContain($b['roomType']->id);

    // B's inventory was never touched by anything aimed at A.
    app(TenantContext::class)->set($b['org']->id);
    expect(soldOn($b['roomType']->id, '2026-09-01'))->toBe(0);
});

it('rate-limits the public endpoints', function () {
    config(['services.booking.rate_limit' => 3]);
    $s = publicPropertyScenario(units: 1);
    $url = "/book/{$s['property']->slug}/search?check_in=2026-09-01&check_out=2026-09-03";

    for ($i = 1; $i <= 3; $i++) {
        test()->getJson($url)->assertOk();
    }

    test()->getJson($url)->assertStatus(429); // 4th request over the per-minute cap
});
