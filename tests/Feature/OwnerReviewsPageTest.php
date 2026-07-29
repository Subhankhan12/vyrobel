<?php

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

/** A published review on $unit, optionally with the operator's response. Real reservation via AssignmentService. */
function reviewOn(array $s, Unit $unit, int $rating, string $body, ?string $response, string $in, string $out): Review
{
    $res = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $unit->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $in, 'check_out' => $out, 'price_minor' => 10000, 'currency' => 'EUR']],
    );

    $review = Review::create([
        'guest_id' => $s['guest']->getKey(), 'reservation_id' => $res->getKey(),
        'unit_id' => $unit->getKey(), 'room_type_id' => $unit->room_type_id,
        'rating' => $rating, 'tags' => [], 'body' => $body,
        'visibility' => 'public', 'status' => 'published', 'published_at' => now(),
    ]);

    if ($response !== null) {
        app(ReviewService::class)->respond($review, $response);
    }

    return $review;
}

it('renders the exact-match Reviews page on REAL aggregates + the operator responses (read-only)', function () {
    $s = seedScenario(1);
    $s['guest']->update(['first_name' => 'Sofia', 'last_name' => 'Marino']);
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
        ->assertSee('By villa')->assertSee('Recent reviews')->assertSee('Rating breakdown')->assertSee('Handled for you')
        // REAL aggregates: 3 reviews, avg (5+4+3)/3 = 4.0; response rate 2/3 = 67%
        ->assertSee('4.0')->assertSee('67%')
        // The operator's REAL response is shown read-only; an un-responded review is honestly "Awaiting a reply"
        ->assertSee('Thank you Sofia — a pleasure hosting you.')
        ->assertSee('Awaiting a reply')
        // #28 — first name + initial, NEVER the full last name
        ->assertSee('Sofia M.')->assertDontSee('Marino')
        // HONEST — the OTA review ingest is account-gated; NO fabricated "within 24 hours" SLA
        ->assertSee('review sync activates once those channels are connected')
        ->assertDontSee('within 24 hours');

    assertNoRawBlade($res);
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
