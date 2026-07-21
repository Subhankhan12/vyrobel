<?php

use App\Actions\Reservations\CheckOutReservation;
use App\Exceptions\Guest\ReviewNotAllowedException;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\AssignmentService;
use App\Services\ReviewService;
use App\Support\Reviews\ReviewRating;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// REVIEW MODERATION + RATING DERIVATION (Phase 5 · Sub-gate 2). Proven: a submitted review is pending (not
// auto-public); publish → public + counts toward the DERIVED rating; reject → never counts; a private review is
// never public; the rating is derived from real PUBLISHED+PUBLIC reviews only (pending/rejected/private excluded —
// mutation-proven); no reviews → "No reviews yet" (has_reviews false).

if (! function_exists('modBook')) {
    function modBook(array $s, string $in, string $out): Reservation
    {
        return app(AssignmentService::class)->createBooking(
            ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
            [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $in, 'check_out' => $out, 'price_minor' => 10000, 'currency' => 'EUR']],
        );
    }
}

if (! function_exists('makeReview')) {
    function makeReview(array $s, Reservation $res, int $rating, string $status, string $visibility): Review
    {
        return Review::create([
            'guest_id' => $s['guest']->getKey(),
            'reservation_id' => $res->getKey(),
            'unit_id' => $s['units'][0]->getKey(),
            'room_type_id' => $s['roomType']->getKey(),
            'rating' => $rating,
            'tags' => [],
            'status' => $status,
            'visibility' => $visibility,
        ]);
    }
}

// ── A pending review counts only once PUBLISHED ───────────────────────────────────────────────────
it('publishes a pending review so it counts toward the derived rating', function () {
    $s = seedScenario(1);
    $review = makeReview($s, modBook($s, '2026-09-01', '2026-09-02'), 5, Review::STATUS_PENDING, Review::VISIBILITY_PUBLIC);

    expect(ReviewRating::forRoomType($s['roomType']->getKey())['has_reviews'])->toBeFalse();   // pending → not public

    app(ReviewService::class)->publish($review);

    $rt = ReviewRating::forRoomType($s['roomType']->getKey());
    expect($rt['has_reviews'])->toBeTrue();
    expect($rt['count'])->toBe(1);
    expect($rt['average'])->toBe(5.0);
    expect($review->fresh()->status)->toBe(Review::STATUS_PUBLISHED);
});

// ── Only published+public reviews count — pending / rejected / private are excluded ───────────────
it('never counts a pending, rejected or private review toward the public rating', function () {
    $s = seedScenario(1);
    makeReview($s, modBook($s, '2026-09-01', '2026-09-02'), 5, Review::STATUS_PENDING, Review::VISIBILITY_PUBLIC);     // pending
    makeReview($s, modBook($s, '2026-09-02', '2026-09-03'), 1, Review::STATUS_REJECTED, Review::VISIBILITY_PUBLIC);    // rejected
    makeReview($s, modBook($s, '2026-09-03', '2026-09-04'), 2, Review::STATUS_PENDING, Review::VISIBILITY_PRIVATE);    // private
    makeReview($s, modBook($s, '2026-09-04', '2026-09-05'), 4, Review::STATUS_PUBLISHED, Review::VISIBILITY_PUBLIC);   // the only public one

    $rt = ReviewRating::forRoomType($s['roomType']->getKey());
    expect($rt['count'])->toBe(1);
    expect($rt['average'])->toBe(4.0);
    // Same figure at the property level (via the room type).
    expect(ReviewRating::forProperty($s['property']->getKey())['count'])->toBe(1);
});

// ── The average is derived from the real published ratings ────────────────────────────────────────
it('derives the average from real published reviews', function () {
    $s = seedScenario(1);
    makeReview($s, modBook($s, '2026-09-01', '2026-09-02'), 5, Review::STATUS_PUBLISHED, Review::VISIBILITY_PUBLIC);
    makeReview($s, modBook($s, '2026-09-02', '2026-09-03'), 3, Review::STATUS_PUBLISHED, Review::VISIBILITY_PUBLIC);

    $rt = ReviewRating::forRoomType($s['roomType']->getKey());
    expect($rt['count'])->toBe(2);
    expect($rt['average'])->toBe(4.0);   // (5 + 3) / 2
});

// ── No reviews → "No reviews yet" (no fabricated rating) ──────────────────────────────────────────
it('reports no reviews for a subject with none', function () {
    $s = seedScenario(1);
    expect(ReviewRating::forRoomType($s['roomType']->getKey()))->toBe(['count' => 0, 'average' => null, 'has_reviews' => false]);
    expect(ReviewRating::forUnit($s['units'][0]->getKey())['has_reviews'])->toBeFalse();
});

// ── Reject unpublishes — it never appears in the rating again ─────────────────────────────────────
it('rejects a review so it never appears in the rating', function () {
    $s = seedScenario(1);
    $review = makeReview($s, modBook($s, '2026-09-01', '2026-09-02'), 5, Review::STATUS_PUBLISHED, Review::VISIBILITY_PUBLIC);
    expect(ReviewRating::forRoomType($s['roomType']->getKey())['count'])->toBe(1);

    app(ReviewService::class)->reject($review);

    expect(ReviewRating::forRoomType($s['roomType']->getKey())['has_reviews'])->toBeFalse();
    expect($review->fresh()->status)->toBe(Review::STATUS_REJECTED);
});

// ── A private review can never be published ───────────────────────────────────────────────────────
it('refuses to publish a private review', function () {
    $s = seedScenario(1);
    $private = makeReview($s, modBook($s, '2026-09-01', '2026-09-02'), 3, Review::STATUS_PENDING, Review::VISIBILITY_PRIVATE);

    expect(fn () => app(ReviewService::class)->publish($private))->toThrow(ReviewNotAllowedException::class);
    expect($private->fresh()->status)->toBe(Review::STATUS_PENDING);   // unchanged, still not public
});

// ── The full moderation state machine (submitted → published → rejected) ──────────────────────────
it('moves a submitted review through the moderation states', function () {
    $s = seedScenario(1);
    $res = modBook($s, '2026-09-01', '2026-09-04');
    app(CheckOutReservation::class)->handle($res);

    $review = app(ReviewService::class)->submit($s['guest'], $res->fresh(), ['rating' => 5]);
    expect($review->status)->toBe(Review::STATUS_PENDING);            // NOT auto-public

    app(ReviewService::class)->publish($review);
    expect($review->fresh()->status)->toBe(Review::STATUS_PUBLISHED);

    app(ReviewService::class)->reject($review);
    expect($review->fresh()->status)->toBe(Review::STATUS_REJECTED);
});
