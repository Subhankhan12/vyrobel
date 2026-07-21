<?php

use App\Actions\Reservations\CheckOutReservation;
use App\Mail\ReviewRequestMail;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Support\Guest\GuestName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// GUEST REVIEW (Phase 5 · Sub-gate 1). Proven: a guest reviews ONLY their own COMPLETED (checked-out) stay
// (future/cancelled/foreign → 404, mutation-proven); one review per stay; a public review is `pending` (not
// auto-public); "private to the manager" sets visibility=private; the Review Request email fires on checkout; and
// anonymize() de-identifies the review.

if (! function_exists('revGuest')) {
    function revGuest(Guest $g, string $email): Guest
    {
        $g->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $g->password = 'guest-password-1234';
        $g->email_verified_at = now();
        $g->save();

        return $g->fresh();
    }
}

if (! function_exists('revBook')) {
    function revBook(array $s, Guest $guest, Unit $unit): Reservation
    {
        return app(AssignmentService::class)->createBooking(
            ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
            [['room_type_id' => $s['roomType']->id, 'unit_id' => $unit->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'price_minor' => 30000, 'currency' => 'EUR']],
        );
    }
}

if (! function_exists('revCompletedBook')) {
    function revCompletedBook(array $s, Guest $guest, Unit $unit): Reservation
    {
        $res = revBook($s, $guest, $unit);
        app(CheckOutReservation::class)->handle($res);   // checked_out → a completed (PAST) stay

        return $res->fresh();
    }
}

// ── A guest reviews their OWN completed stay (pending, tied to the real reservation + unit) ────────
it('lets a guest review their own completed stay', function () {
    $s = seedScenario(1);
    $a = revGuest($s['guest'], 'ada@example.test');
    $res = revCompletedBook($s, $a, $s['units'][0]);

    $this->actingAs($a, 'guest')->post(route('guest.review.submit', $res->getKey()), ['rating' => 5, 'tags' => ['Great location'], 'body' => 'Lovely stay'])
        ->assertRedirect(route('guest.trips'));

    $review = Review::withoutGlobalScopes()->where('reservation_id', $res->getKey())->first();
    expect($review)->not->toBeNull();
    expect((int) $review->rating)->toBe(5);
    expect($review->tags)->toBe(['Great location']);
    expect((string) $review->status)->toBe('pending');        // NOT auto-public
    expect((string) $review->visibility)->toBe('public');
    expect((string) $review->unit_id)->toBe((string) $s['units'][0]->getKey());   // derived from the stay
});

// ── Can't review a FUTURE or CANCELLED stay ───────────────────────────────────────────────────────
it('refuses to review a future or cancelled stay', function () {
    $s = seedScenario(2);
    $a = revGuest($s['guest'], 'ada@example.test');

    // Build the bookings BEFORE acting as a guest (createBooking writes changed_by = Auth::id(); the guest guard
    // would otherwise poison it with a guest id — the users FK).
    $future = revBook($s, $a, $s['units'][0]);   // confirmed, Sep — UPCOMING
    $cancelled = revBook($s, $a, $s['units'][1]);
    app(AssignmentService::class)->cancelReservation($cancelled, 'Change of plans');

    $this->actingAs($a, 'guest')->get(route('guest.review', $future->getKey()))->assertNotFound();
    $this->actingAs($a, 'guest')->post(route('guest.review.submit', $future->getKey()), ['rating' => 5])->assertNotFound();
    $this->actingAs($a, 'guest')->get(route('guest.review', $cancelled->getKey()))->assertNotFound();

    expect(Review::withoutGlobalScopes()->count())->toBe(0);   // nothing written
});

// ── ISOLATION (mutation-proven): a guest can't review another guest's stay ────────────────────────
it('404s when a guest reviews another guest’s stay', function () {
    $s = seedScenario(2);
    $a = revGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);
    $bRes = revCompletedBook($s, $b, $s['units'][1]);

    $this->actingAs($a, 'guest')->get(route('guest.review', $bRes->getKey()))->assertNotFound();
    $this->actingAs($a, 'guest')->post(route('guest.review.submit', $bRes->getKey()), ['rating' => 5])->assertNotFound();
    expect(Review::withoutGlobalScopes()->count())->toBe(0);
});

// ── ONE review per stay ───────────────────────────────────────────────────────────────────────────
it('allows only one review per stay', function () {
    $s = seedScenario(1);
    $a = revGuest($s['guest'], 'ada@example.test');
    $res = revCompletedBook($s, $a, $s['units'][0]);

    $this->actingAs($a, 'guest')->post(route('guest.review.submit', $res->getKey()), ['rating' => 5])->assertRedirect(route('guest.trips'));
    $this->actingAs($a, 'guest')->post(route('guest.review.submit', $res->getKey()), ['rating' => 1])->assertRedirect(route('guest.review', $res->getKey()));   // refused → back with error

    expect(Review::withoutGlobalScopes()->where('reservation_id', $res->getKey())->count())->toBe(1);   // still one
});

// ── "Private to the manager" sets visibility=private (never public) ───────────────────────────────
it('records a private-to-manager review', function () {
    $s = seedScenario(1);
    $a = revGuest($s['guest'], 'ada@example.test');
    $res = revCompletedBook($s, $a, $s['units'][0]);

    $this->actingAs($a, 'guest')->post(route('guest.review.submit', $res->getKey()), ['rating' => 2, 'body' => 'A quiet word', 'private' => '1'])->assertRedirect();

    $review = Review::withoutGlobalScopes()->where('reservation_id', $res->getKey())->first();
    expect((string) $review->visibility)->toBe('private');
});

// ── The Review Request email fires on checkout (the wired Phase-3 trigger) ─────────────────────────
it('fires the review-request email on checkout', function () {
    Mail::fake();
    $s = seedScenario(1);
    $a = revGuest($s['guest'], 'ada@example.test');
    $res = revBook($s, $a, $s['units'][0]);

    app(CheckOutReservation::class)->handle($res);

    Mail::assertSent(ReviewRequestMail::class);
});

// ── The Trips page shows a review link for a completed stay ───────────────────────────────────────
it('shows a review link on Trips for a completed stay', function () {
    $s = seedScenario(1);
    $a = revGuest($s['guest'], 'ada@example.test');
    revCompletedBook($s, $a, $s['units'][0]);

    $this->actingAs($a, 'guest')->get(route('guest.trips'))->assertOk()->assertSee('Review your stay');
});

// ── GDPR: anonymize() de-identifies the review's author ───────────────────────────────────────────
it('de-identifies a review when the guest is anonymized', function () {
    $s = seedScenario(1);
    $a = revGuest($s['guest'], 'ada@example.test');
    $res = revCompletedBook($s, $a, $s['units'][0]);
    $this->actingAs($a, 'guest')->post(route('guest.review.submit', $res->getKey()), ['rating' => 5, 'body' => 'Great']);
    $review = Review::withoutGlobalScopes()->where('reservation_id', $res->getKey())->first();

    $a->anonymize();

    expect(GuestName::display($review->fresh()->guest))->toBe('Guest');   // no longer "Ada Lovelace"
});
