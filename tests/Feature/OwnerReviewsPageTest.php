<?php

use App\Actions\Reservations\CheckInReservation;
use App\Actions\Reservations\CheckOutReservation;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\Review;
use App\Models\Unit;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Owner Reviews & reputation (exact-match rebuild) — the page renders REAL owner-scoped aggregates (overall
// rating, response rate, last-30, by-villa, rating breakdown) + the operator's read-only RESPONSES, all DERIVED
// from real published reviews (#39). Guest byline is first-name + initial (#28). The pending state is the honest
// "Awaiting a reply"; the OTA review INGEST is honestly account-gated; no fabricated SLA.

/**
 * A published review on $unit, optionally with the operator's response + per-category sub-ratings
 * [cleanliness, communication, location, value]. Real reservation via AssignmentService.
 *
 * @param  array<int, int>  $cats
 */
function reviewOn(array $s, Unit $unit, int $rating, string $body, ?string $response, string $in, string $out, array $cats = []): Review
{
    $res = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $unit->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $in, 'check_out' => $out, 'price_minor' => 10000, 'currency' => 'EUR']],
    );

    $review = Review::create([
        'guest_id' => $s['guest']->getKey(), 'reservation_id' => $res->getKey(),
        'unit_id' => $unit->getKey(), 'room_type_id' => $unit->room_type_id,
        'rating' => $rating, 'tags' => [], 'body' => $body,
        'rating_cleanliness' => $cats[0] ?? null, 'rating_communication' => $cats[1] ?? null,
        'rating_location' => $cats[2] ?? null, 'rating_value' => $cats[3] ?? null,
        'visibility' => 'public', 'status' => 'published', 'published_at' => now(),
    ]);

    if ($response !== null) {
        app(ReviewService::class)->respond($review, $response);
    }

    return $review;
}

/** A linked owner + unit + a published (optionally category-rated) review. Returns [user, owner, unit]. */
function reviewsOwner(array $s): array
{
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-06-01', '2026-06-30');

    return ['user' => $user, 'owner' => $owner, 'unit' => $s['units'][0]];
}

it('renders the exact-match Reviews page on REAL aggregates + the operator responses (read-only)', function () {
    $s = seedScenario(1);
    // ⚠️ Contact is seeded ON PURPOSE. The surname assertion below proves the byline is trimmed, but it
    // could not prove the review projection withholds CONTACT — a guest with no email cannot leak one.
    $s['guest']->update([
        'first_name' => 'Sofia', 'last_name' => 'Marino',
        'email' => 'sofia.marino@icloud.test', 'phone' => '+351913004422',
    ]);
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-06-01', '2026-06-30');   // publish the window for the review stays

    reviewOn($s, $s['units'][0], 5, 'Immaculate villa — spotless.', 'Thank you Sofia — a pleasure hosting you.', '2026-06-01', '2026-06-04');
    reviewOn($s, $s['units'][0], 4, 'Lovely, comfortable stay.', 'Thanks — so glad you enjoyed it.', '2026-06-06', '2026-06-09');
    reviewOn($s, $s['units'][0], 3, 'Great location but the AC struggled.', null, '2026-06-11', '2026-06-14');   // awaiting

    $res = $this->actingAs($user)->get('/owner/reviews')->assertOk()
        // The exact-match sections
        ->assertSee('Overall rating')->assertSee('Response rate')->assertSee('Last 30 days')
        ->assertSee('By unit')->assertSee('Recent reviews')->assertSee('Rating breakdown')->assertSee('Handled for you')
        // REAL aggregates: 3 reviews, avg (5+4+3)/3 = 4.0; response rate 2/3 = 67%
        ->assertSee('4.0')->assertSee('67%')
        // The operator's REAL response is shown read-only; an un-responded review is honestly "Awaiting a reply"
        ->assertSee('Thank you Sofia — a pleasure hosting you.')
        ->assertSee('Awaiting a reply')
        // #28 — first name + initial, NEVER the full last name
        ->assertSee('Sofia M.')->assertDontSee('Marino')
        ->assertDontSee('sofia.marino@icloud.test')   // #28 — never the guest's email…
        ->assertDontSee('+351913004422')              // …nor their phone
        // HONEST — the OTA review ingest is account-gated; NO fabricated "within 24 hours" SLA
        ->assertSee('review sync activates once those channels are connected')
        ->assertDontSee('within 24 hours');

    assertNoRawBlade($res);
});

it('renders REAL By-category averages + the softest-category note linking the real rate-proposals (owner.rates)', function () {
    $s = seedScenario(1);
    $c = reviewsOwner($s);

    // Value scores lowest → it is the "softest" category. Cleanliness/Communication/Location all high.
    reviewOn($s, $c['unit'], 5, 'Great.', null, '2026-06-01', '2026-06-04', [5, 5, 5, 4]);
    reviewOn($s, $c['unit'], 5, 'Lovely.', null, '2026-06-06', '2026-06-09', [5, 5, 4, 3]);

    $this->actingAs($c['user'])->get('/owner/reviews')->assertOk()
        ->assertSee('By category')
        ->assertSee('Cleanliness')->assertSee('Communication')->assertSee('Location')->assertSee('Value')
        ->assertSee('5.0')   // Cleanliness avg (5+5)/2
        ->assertSee('3.5')   // Value avg (4+3)/2 — the softest
        ->assertSee('Value is your softest score', false)
        ->assertSee(route('owner.rates'));   // the REAL Wave-C rate-proposals link (not a refusal)
});

it('surfaces an operator response on the owner page (operator writes → owner reads, end-to-end #20)', function () {
    $s = seedScenario(1);
    $c = reviewsOwner($s);
    $review = reviewOn($s, $c['unit'], 4, 'Comfortable stay, AC struggled.', null, '2026-06-01', '2026-06-04');   // awaiting

    // Before: the owner sees it awaiting, no response.
    $this->actingAs($c['user'])->get('/owner/reviews')->assertOk()->assertSee('Awaiting a reply')->assertDontSee('We fixed the AC');

    // The OPERATOR (reviews.manage) posts a response through the real controller route (#20, audited).
    $manager = operatorUser($s, [], 'Hotel Manager');
    $this->actingAs($manager, 'web')->post(route('app.reviews.respond', $review->getKey()), ['response' => 'Thanks — we fixed the AC that week.'])->assertRedirect();

    // After: the owner sees the operator's response, read-only (no owner reply control).
    $this->actingAs($c['user'])->get('/owner/reviews')->assertOk()
        ->assertSee('Thanks — we fixed the AC that week.')
        ->assertDontSee('Awaiting a reply');
});

it('persists the guest per-category sub-ratings through ReviewService::submit', function () {
    $s = seedScenario(1);
    $c = reviewsOwner($s);
    $res = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-06-01', 'check_out' => '2026-06-04', 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $c['unit']->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-06-01', 'check_out' => '2026-06-04', 'price_minor' => 10000, 'currency' => 'EUR']],
    );
    // ⚠️ R4 (founder, 2026-09-19): a stay must be CHECKED IN before it can be checked out.
    app(CheckInReservation::class)->handle($res);
    app(CheckOutReservation::class)->handle($res);   // → completed → reviewable

    $review = app(ReviewService::class)->submit($s['guest'], $res->fresh(), [
        'rating' => 5,
        'categories' => ['rating_cleanliness' => 5, 'rating_communication' => 4, 'rating_value' => 2, 'rating_location' => null],
    ]);

    expect($review->rating_cleanliness)->toBe(5)
        ->and($review->rating_communication)->toBe(4)
        ->and($review->rating_value)->toBe(2)
        ->and($review->rating_location)->toBeNull();   // a blank category is stored null, never fabricated
});

it('shows an honest empty state when the owner has no reviews', function () {
    $s = seedScenario(1);
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $owner->id]);

    $this->actingAs($user)->get('/owner/reviews')->assertOk()
        ->assertSee('No reviews yet for your units')
        ->assertDontSee('Response rate');   // no fabricated aggregate on an empty page
});

// ── The star row must never show more filled stars than the rating earned ─────────────────────────
// Owner-26 found the hero repeating the SAME filled glyph for both halves of the row, so EVERY rating
// rendered as five full stars: a 4.5 average looked like a perfect 5, and a 3.0 would have too. On a
// page that reads as social proof that overstates the real figure. The shared <x-star-rating> now
// floors the filled count and marks the remainder with a DIFFERENT glyph, so filled-vs-empty survives
// greyscale (1.4.1) and the row carries one accessible name instead of five anonymous stars.
it('never renders more filled stars than the rating earned', function () {
    $s = seedScenario(1);
    $c = reviewsOwner($s);
    reviewOn($s, $s['units'][0], 4, 'Good, not perfect.', null, '2026-06-01', '2026-06-04');

    $html = $this->actingAs($c['user'])->get('/owner/reviews')->assertOk()->getContent();

    // A row that earned less than five must contain an EMPTY glyph — the bug shipped exactly because
    // every glyph was the filled one.
    expect($html)->toContain('☆')
        // …and the row is named, so a screen reader hears the rating once.
        ->and($html)->toContain('out of 5 stars');
});
