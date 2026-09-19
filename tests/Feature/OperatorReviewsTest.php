<?php

use App\Actions\Reservations\CheckInReservation;
use App\Actions\Reservations\CheckOutReservation;
use App\Models\NotificationPreference;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\AssignmentService;
use App\Services\ReviewService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OPERATOR REVIEWS (Phase 5 · Sub-gate 3). Proven: the operator sees their org's reviews, responds, and moderates
// (publish/reject); a new review fires the (wired Phase-3) new-review notification, respecting the preference; and
// the screen + actions are gated on reviews.view / reviews.manage — NOT Front Desk (mutation-proven).

if (! function_exists('revComplete')) {
    function revComplete(array $s, string $in = '2026-09-01', string $out = '2026-09-03'): Reservation
    {
        $res = app(AssignmentService::class)->createBooking(
            ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
            [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $in, 'check_out' => $out, 'price_minor' => 10000, 'currency' => 'EUR']],
        );
        // ⚠️ R4 (founder, 2026-09-19): a stay must be CHECKED IN before it can be checked out.
        app(CheckInReservation::class)->handle($res);
        app(CheckOutReservation::class)->handle($res);   // → checked_out (no folio → no balance-due noise)

        return $res->fresh();
    }
}

// ── The operator sees their org's reviews + responds ──────────────────────────────────────────────
it('lets the operator see reviews and attach a response', function () {
    $s = seedScenario(1);
    $review = app(ReviewService::class)->submit($s['guest'], revComplete($s), ['rating' => 5, 'body' => 'Lovely place']);
    $manager = operatorUser($s, [], 'Hotel Manager');
    app(TenantContext::class)->forget();

    $this->actingAs($manager, 'web')->get(route('app.reviews'))->assertOk()->assertSee('Lovely place');

    app(TenantContext::class)->forget();
    $this->actingAs($manager, 'web')->post(route('app.reviews.respond', $review->getKey()), ['response' => 'Thank you so much!'])->assertRedirect();
    expect((string) $review->fresh()->operator_response)->toBe('Thank you so much!');
});

// ── The operator moderates: publish then reject ───────────────────────────────────────────────────
it('lets the operator publish and reject a review', function () {
    $s = seedScenario(1);
    $review = app(ReviewService::class)->submit($s['guest'], revComplete($s), ['rating' => 4]);
    $manager = operatorUser($s, [], 'Hotel Manager');

    app(TenantContext::class)->forget();
    $this->actingAs($manager, 'web')->post(route('app.reviews.publish', $review->getKey()))->assertRedirect();
    expect($review->fresh()->status)->toBe(Review::STATUS_PUBLISHED);

    app(TenantContext::class)->forget();
    $this->actingAs($manager, 'web')->post(route('app.reviews.reject', $review->getKey()))->assertRedirect();
    expect($review->fresh()->status)->toBe(Review::STATUS_REJECTED);
});

// ── A new review fires the operator notification, respecting the preference ────────────────────────
it('notifies the operator of a new review and respects the off toggle', function () {
    $s = seedScenario(1);
    // Build both completed stays BEFORE the operator exists, so the booking's own new-booking notification has no
    // recipient — isolating the new-review notification for the count.
    $res1 = revComplete($s, '2026-09-01', '2026-09-03');
    $res2 = revComplete($s, '2026-09-05', '2026-09-08');
    $operator = operatorUser($s, [], 'Hotel Manager');

    app(ReviewService::class)->submit($s['guest'], $res1, ['rating' => 5]);
    expect($operator->fresh()->notifications()->count())->toBe(1);   // the new-review notification

    // Turn the preference OFF → the next review does not notify.
    NotificationPreference::create(['user_id' => $operator->getKey(), 'event_key' => 'new_review', 'channel' => 'in_app', 'enabled' => false]);
    app(ReviewService::class)->submit($s['guest'], $res2, ['rating' => 4]);
    expect($operator->fresh()->notifications()->count())->toBe(1);   // unchanged — suppressed
});

// ── reviews.manage gates it — Front Desk is refused, a permitted role is not ──────────────────────
it('gates the reviews screen + actions on the reviews permission, not Front Desk', function () {
    $s = seedScenario(1);
    $review = app(ReviewService::class)->submit($s['guest'], revComplete($s), ['rating' => 3]);

    $frontDesk = operatorUser($s, [], 'Front Desk');   // has NO reviews.view / reviews.manage
    app(TenantContext::class)->forget();
    $this->actingAs($frontDesk, 'web')->get(route('app.reviews'))->assertForbidden();
    app(TenantContext::class)->forget();
    $this->actingAs($frontDesk, 'web')->post(route('app.reviews.publish', $review->getKey()))->assertForbidden();

    $manager = operatorUser($s, [], 'Hotel Manager');   // HAS reviews.view + reviews.manage
    app(TenantContext::class)->forget();
    $this->actingAs($manager, 'web')->get(route('app.reviews'))->assertOk();
    app(TenantContext::class)->forget();
    $this->actingAs($manager, 'web')->post(route('app.reviews.publish', $review->getKey()))->assertRedirect();
});
