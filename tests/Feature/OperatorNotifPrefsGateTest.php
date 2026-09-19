<?php

use App\Actions\Reservations\CheckInReservation;
use App\Actions\Reservations\CheckOutReservation;
use App\Models\Guest;
use App\Models\User;
use App\Notifications\Operator\LowRatingNotification;
use App\Notifications\Operator\NewReviewNotification;
use App\Services\AssignmentService;
use App\Services\NotificationPreferences;
use App\Services\RateAndRestrictionService;
use App\Services\ReviewService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #90 GATE 3 — the operator side of #79: the EMAIL column genuinely gates delivery (reused engine), Push + SMS
// are honest-gated (never submitted), and the new `low_rating` alert fires only on a REAL rating under 4★.

if (! function_exists('opNotifs')) {
    function opNotifs(User $user, string $type): int
    {
        return $user->fresh()->notifications()->where('type', $type)->count();
    }
}

/** A completed (checked-out) stay the guest can review — fires review.submitted through the real ReviewService. */
function submitReview(array $s, int $rating): void
{
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-10', Money::of(10000, 'EUR'));
    $guest = Guest::create(['first_name' => 'Rev', 'last_name' => 'Guest']);
    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'price_minor' => 30000, 'currency' => 'EUR']],
    );
    // ⚠️ R4 (founder, 2026-09-19): a stay must be CHECKED IN before it can be checked out.
    app(CheckInReservation::class)->handle($reservation);
    app(CheckOutReservation::class)->handle($reservation->refresh());
    app(ReviewService::class)->submit($guest, $reservation->refresh(), ['rating' => $rating]);
}

// ── The EMAIL pref genuinely gates delivery (mutation-proven) ─────────────────────────────────────
it('suppresses the email channel for an operator event when the email pref is off', function () {
    $prefs = app(NotificationPreferences::class);
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops']);

    // With email OFF for new_booking, via() must not include 'mail'.
    $prefs->save($operator, 'operator', ['new_booking' => ['in_app' => true, 'email' => false]]);
    expect($prefs->allows($operator, 'new_booking', 'email'))->toBeFalse()
        ->and($prefs->allows($operator, 'new_booking', 'in_app'))->toBeTrue();

    // Flip it on → email allowed again.
    $prefs->save($operator, 'operator', ['new_booking' => ['in_app' => true, 'email' => true]]);
    expect($prefs->allows($operator, 'new_booking', 'email'))->toBeTrue();
});

// ── low_rating fires ONLY on a real rating under 4★ ───────────────────────────────────────────────
it('fires the low-rating alert only when the real rating is under 4', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops']);

    submitReview($s, 3);   // under 4 → both new_review AND low_rating
    expect(opNotifs($operator, NewReviewNotification::class))->toBe(1)
        ->and(opNotifs($operator, LowRatingNotification::class))->toBe(1);
});

it('does not fire the low-rating alert for a 4+ rating', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops']);

    submitReview($s, 5);   // 5★ → only new_review
    expect(opNotifs($operator, NewReviewNotification::class))->toBe(1)
        ->and(opNotifs($operator, LowRatingNotification::class))->toBe(0);
});

// ── The page renders; Push/SMS are honest-gated (never a submitted input) ─────────────────────────
it('renders the operator prefs page with honest Push and SMS and never a submitted push/sms field', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops']);

    $html = $this->actingAs($operator)->get(route('app.notification-preferences'))->assertOk()
        ->assertSee('Notification preferences')
        ->assertSee('Low rating (under 4★)')   // the new event row is on the grid
        ->assertSee('not set up')              // Push + SMS honest
        ->getContent();

    // No push/sms is ever submittable — those columns carry no name attribute.
    expect($html)->not->toContain('prefs[new_booking][push]')
        ->and($html)->not->toContain('prefs[new_booking][sms]');
});

// ── Saving persists the email toggle + quiet hours (self-only, reused controller) ─────────────────
it('saves the operator email prefs and quiet hours through the shared controller', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops']);

    $this->actingAs($operator)->post(route('app.notification-preferences.update'), [
        'prefs' => ['new_booking' => ['in_app' => '1', 'email' => '1'], 'low_rating' => ['email' => '1']],
        'quiet_hours_enabled' => '1', 'quiet_start' => '22:00', 'quiet_end' => '07:00',
    ])->assertRedirect(route('app.notification-preferences'));

    expect(app(NotificationPreferences::class)->allows($operator->fresh(), 'new_booking', 'email'))->toBeTrue()
        ->and($operator->fresh()->quiet_hours_start)->not->toBeNull();
});

// ═══════════════════════════════════════════════════════════════════════════════════════════════════
// P30 (design-parity) — this page's CONTROLS must be operable and its states must not ride on colour.
// ═══════════════════════════════════════════════════════════════════════════════════════════════════

it('keeps the email toggles operable by KEYBOARD (WCAG 2.1.1)', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops']);

    $html = $this->actingAs($operator)->get(route('app.notification-preferences'))->assertOk()->getContent();

    // ⚠️ `display: none` on the checkbox removes it from the tab order AND the a11y tree — the page's only
    // real controls become unoperable by keyboard. It must stay visually hidden but FOCUSABLE.
    expect($html)->not->toContain('.vy-np-check { display: none; }');
    expect($html)->toContain('.vy-np-check:focus-visible + .vy-np-cell');
    // …and each toggle carries a real accessible name (the styled cell is decoration).
    expect($html)->toContain('aria-label="Email me when: New booking"');
});

it('gives the no-provider Push/SMS cells real text, not colour alone (1.4.1)', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, ['name' => 'Ops']);

    $this->actingAs($operator)->get(route('app.notification-preferences'))->assertOk()
        ->assertSee('Push not set up')
        ->assertSee('SMS not set up');
});

it('links the Settings breadcrumb only for a viewer the Settings route would admit (#30)', function () {
    $s = seedScenario(1);

    // A Group Admin holds properties.manage → the crumb links, and the target really opens.
    $admin = operatorUser($s, [], 'Group Admin');
    $this->actingAs($admin)->get(route('app.notification-preferences'))->assertOk()
        ->assertSee(route('app.settings'), false);
    $this->actingAs($admin)->get(route('app.settings'))->assertOk();

    // ⚠️ Front Desk can set their OWN preferences (the page carries no permission gate) but cannot open
    // Settings — so the crumb must not offer a link that would 403.
    $desk = operatorUser($s, [], 'Front Desk');
    $this->actingAs($desk)->get(route('app.notification-preferences'))->assertOk()
        ->assertDontSee(route('app.settings'), false);
    $this->actingAs($desk)->get(route('app.settings'))->assertForbidden();
});
