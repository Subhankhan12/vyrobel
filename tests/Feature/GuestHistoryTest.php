<?php

use App\Models\Guest;
use App\Models\MessageThread;
use App\Models\Reservation;
use App\Services\InboxService;
use App\Support\Guest\GuestHistory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// THE SHARED STAY-HISTORY DEFINITION (Wave 2 · Sub-gate 5) — stays · nights · "guest since".
//
// WHY IT EXISTS: THREE SCREENS WERE ABOUT TO DISAGREE, and one of them already shipped. InboxThread's guest
// card counted EVERY reservation (cancellations included) and dated "guest since" from the earliest of them;
// Sub-gate 4 then locked the CRM's rule as "a cancelled booking is not a stay". Same guest, same afternoon,
// two sincere answers — on BOTH numbers. Test 3 is the one that matters: it renders the inbox and the CRM
// for a guest with a cancelled booking and asserts they agree.

beforeEach(function () {
    // book() always stays 2026-09-01→03 (the window seedScenario publishes availability for).
    Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00'));
});

function historyStay(array $s, int $unitIndex, Guest $guest, string $status, string $checkIn, string $checkOut): Reservation
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);

    Reservation::query()->whereKey($reservation->getKey())->update([
        'lead_guest_id' => $guest->id,
        'status' => $status,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
    ]);

    return $reservation->fresh();
}

// ── 1. THE RULE — a cancelled booking is not a stay, and does not date "guest since" ─────────────
it('counts only stays that happened, and dates guest-since from the earliest of those', function () {
    $s = seedScenario(3);
    $guest = Guest::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    historyStay($s, 0, $guest, 'checked_out', '2025-08-01', '2025-08-04');   // 3 nights, the real first stay
    historyStay($s, 1, $guest, 'checked_in', '2026-09-01', '2026-09-03');    // 2 nights
    // Cancelled, and EARLIER than either — so a naive rule would both over-count and mis-date "since".
    historyStay($s, 2, $guest, 'cancelled', '2023-01-01', '2023-01-05');

    $history = GuestHistory::for($guest->reservations()->get());

    expect($history['stays'])->toBe(2)
        ->and($history['nights'])->toBe(5)            // 3 + 2, half-open [check_in, check_out)
        ->and($history['since_year'])->toBe('2025')   // NOT 2023 — that visit never happened
        ->and($history['stat'])->toBe('2 stays · guest since 2025');
});

it('reports nothing for a guest who has never stayed, rather than "0 stays"', function () {
    $s = seedScenario(1);
    $guest = Guest::create(['first_name' => 'Never', 'last_name' => 'Booked']);

    $history = GuestHistory::for($guest->reservations()->get());

    // Null, not "0 stays" — the inbox card omits the line entirely rather than printing something that
    // reads like a data error next to a guest who is clearly talking to us.
    expect($history['stays'])->toBe(0)
        ->and($history['since_year'])->toBeNull()
        ->and($history['stat'])->toBeNull();
});

it('says "1 stay", not "1 stays"', function () {
    $s = seedScenario(1);
    $guest = Guest::create(['first_name' => 'One', 'last_name' => 'Timer']);
    historyStay($s, 0, $guest, 'checked_out', '2026-08-01', '2026-08-02');

    expect(GuestHistory::for($guest->reservations()->get())['stat'])->toBe('1 stay · guest since 2026');
});

// ── 2. THE DRIFT THIS CLOSED — the inbox and the CRM now agree, and are asserted to ──────────────
it('gives the inbox guest card and the guests CRM the same stay count for the same guest', function () {
    $s = seedScenario(2);
    $guest = Guest::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    $real = historyStay($s, 0, $guest, 'checked_in', '2026-09-01', '2026-09-03');
    // The row the two rules disagreed about: cancelled, and older than the real stay.
    historyStay($s, 1, $guest, 'cancelled', '2024-01-01', '2024-01-03');

    app(InboxService::class)->startThread([
        'channel' => 'manual',
        'subject' => 'Late arrival',
        'guest_id' => $guest->id,
        'reservation_id' => $real->id,
    ]);

    $operator = operatorUser($s);

    $inbox = $this->actingAs($operator)->get('/app/inbox');
    $threadModel = MessageThread::query()->where('guest_id', $guest->id)->firstOrFail();
    $conversation = $this->actingAs($operator)->get('/app/inbox/'.$threadModel->id);
    $crm = $this->actingAs($operator)->get('/app/guests');
    $profile = $this->actingAs($operator)->get('/app/guests/'.$guest->id);

    // The inbox card used to say "2 stays · guest since 2024". It now agrees with the CRM.
    $conversation->assertOk()->assertSee('1 stay · guest since 2026')->assertDontSee('2 stays');
    $profile->assertOk()->assertSee('1 stay · guest since 2026');

    // And the CRM's own count is the same rule — 2 bookings, 1 stay.
    expect($guest->reservations()->count())->toBe(2)
        ->and(GuestHistory::for($guest->reservations()->get())['stays'])->toBe(1);

    $crm->assertOk();
    $inbox->assertOk();
});
