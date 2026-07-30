<?php

use App\Models\AuditEvent;
use App\Models\Guest;
use App\Models\HousekeepingTask;
use App\Models\MessageThread;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\FolioService;
use App\Support\Guest\GuestMarketing;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OPERATOR GUEST PROFILE + the CRM FLOWS (#87 GATE 2) — the latest review (#76), the per-stay refund, the
// preferences store flowing to the front desk + housekeeping (the once-empty promise made true), the
// consent gate, and the edit write. The operator sees full contact (the manager, not the owner #28).

function crmFlowGuest(string $first, string $last, array $extra = []): Guest
{
    return Guest::create(array_merge(['first_name' => $first, 'last_name' => $last], $extra));
}

function crmFlowStay(array $s, int $unitIndex, Guest $guest, string $status, string $checkIn, string $checkOut): Reservation
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id, 'check_in' => $checkIn, 'check_out' => $checkOut]);
    Reservation::query()->whereKey($reservation->getKey())->update(['lead_guest_id' => $guest->id, 'status' => $status, 'check_in' => $checkIn, 'check_out' => $checkOut]);

    return $reservation->fresh();
}

// ── The latest review renders (#76) — real, honest empty state otherwise ─────────────────────────
it('renders the guest’s latest real review on the profile', function () {
    $s = seedScenario(1);
    $guest = crmFlowGuest('Ada', 'Lovelace');
    $stay = crmFlowStay($s, 0, $guest, 'checked_out', '2026-09-05', '2026-09-07');

    Review::create([
        'guest_id' => $guest->id,
        'reservation_id' => $stay->id,
        'unit_id' => $s['units'][0]->id,
        'rating' => 5,
        'body' => 'Spotless villa and a seamless check-in.',
        'visibility' => 'public',
        'status' => 'published',
    ]);

    $this->actingAs(operatorUser($s))->get('/app/guests/'.$guest->id)
        ->assertOk()
        ->assertSee('Latest review')
        ->assertSee('Spotless villa and a seamless check-in.')
        ->assertSee('5.0');
});

// ── The per-stay refund line is real (succeeded refund payments) ─────────────────────────────────
it('shows the real refund on a stay that was partially refunded', function () {
    $s = seedScenario(1);
    $guest = crmFlowGuest('Ada', 'Lovelace');
    $stay = crmFlowStay($s, 0, $guest, 'checked_out', '2026-09-05', '2026-09-07');

    $folios = app(FolioService::class);
    $folio = $folios->openFolio($stay);
    $folios->postCharge($folio, 'room', 'Room', Money::of(30000, 'EUR'));
    $payment = $folios->recordPayment($folio, Money::of(30000, 'EUR'));
    $folios->refundPayment($payment, Money::of(5000, 'EUR'));   // a real €50 refund

    $this->actingAs(operatorUser($s))->get('/app/guests/'.$guest->id)
        ->assertOk()
        ->assertSee('€50.00 refund');
});

// ── Preferences persist through the edit + FLOW to the front desk (VIP + prefs) ──────────────────
it('persists preferences and surfaces them, with VIP, on the front desk', function () {
    $s = seedScenario(1);
    $guest = crmFlowGuest('Ada', 'Lovelace');
    crmFlowStay($s, 0, $guest, 'confirmed', '2026-09-01', '2026-09-03');   // arrives 1 Sep

    // Edit the profile: set VIP + two preferences.
    $this->actingAs(operatorUser($s))->post('/app/guests/'.$guest->id, [
        'last_name' => 'Lovelace', 'is_vip' => '1', 'preferences' => ['sea_view', 'late_checkout'],
    ])->assertRedirect();

    $fresh = $guest->fresh();
    expect($fresh->is_vip)->toBeTrue()
        ->and($fresh->preferences)->toBe(['sea_view', 'late_checkout']);

    // The front desk (arrivals for 1 Sep) now shows the VIP flag + the preference labels.
    $this->actingAs(operatorUser($s))->get('/app/front-desk?date=2026-09-01')
        ->assertOk()
        ->assertSee('VIP')
        ->assertSee('Sea view')
        ->assertSee('Late checkout')
        // #28 — still name-only: no contact PII leaks onto the front desk.
        ->assertDontSee('sea_view');
});

// ── Preferences flow to HOUSEKEEPING (the arriving guest for the task's unit + day) ──────────────
it('surfaces the arriving guest’s preferences on the housekeeping board', function () {
    $s = seedScenario(1);
    $guest = crmFlowGuest('Ada', 'Lovelace', ['is_vip' => true, 'preferences' => ['quiet_room']]);
    crmFlowStay($s, 0, $guest, 'confirmed', '2026-09-01', '2026-09-03');

    HousekeepingTask::create([
        'property_id' => $s['property']->id,
        'unit_id' => $s['units'][0]->id,
        'date' => '2026-09-01',
        'type' => 'arrival_prep',
        'status' => 'pending',
        'priority' => 'normal',
    ]);

    $this->actingAs(operatorUser($s))->get('/app/housekeeping')
        ->assertOk()
        ->assertSee('Quiet room')
        ->assertSee('VIP arrival');
});

// ── Marketing consent genuinely gates a marketing send (the gate, from the profile edit) ─────────
it('governs marketing eligibility through the consent toggle', function () {
    $s = seedScenario(1);
    $guest = crmFlowGuest('Ada', 'Lovelace', ['email' => 'ada@example.test', 'marketing_consent' => true]);

    expect(GuestMarketing::canReceive($guest->fresh()))->toBeTrue();

    // Toggle consent OFF through the real edit — the checkbox posts nothing when unticked.
    $this->actingAs(operatorUser($s))->post('/app/guests/'.$guest->id, ['last_name' => 'Lovelace', 'email' => 'ada@example.test'])
        ->assertRedirect();

    expect($guest->fresh()->marketing_consent)->toBeFalse()
        ->and(GuestMarketing::canReceive($guest->fresh()))->toBeFalse();
});

// ── The edit is a real, audited write (city/prefers_email persisted) ─────────────────────────────
it('persists city and prefers_email through the edit, audited', function () {
    $s = seedScenario(1);
    $guest = crmFlowGuest('Ada', 'Lovelace');

    $this->actingAs(operatorUser($s))->post('/app/guests/'.$guest->id, [
        'last_name' => 'Lovelace', 'city' => 'Dublin', 'prefers_email' => '1',
    ])->assertRedirect();

    expect((string) $guest->fresh()->city)->toBe('Dublin')
        ->and($guest->fresh()->prefers_email)->toBeTrue();
    expect(AuditEvent::query()->where('action', 'guest.profile_updated')->exists())->toBeTrue();
});

// ── The profile "Message" button opens the real guest↔operator channel ───────────────────────────
it('starts a real inbox thread from the profile Message button', function () {
    $s = seedScenario(1);
    $guest = crmFlowGuest('Ada', 'Lovelace', ['email' => 'ada@example.test']);

    $response = $this->actingAs(operatorUser($s))->post('/app/guests/'.$guest->id.'/message');

    $thread = MessageThread::query()->where('guest_id', $guest->id)->where('channel', 'manual')->first();
    expect($thread)->not->toBeNull();
    $response->assertRedirect(route('app.inbox.show', $thread->id));
});
