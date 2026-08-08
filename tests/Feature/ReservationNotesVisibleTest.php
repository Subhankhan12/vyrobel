<?php

declare(strict_types=1);

use App\Livewire\ReservationDetail;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * REGRESSION — the wireframe-parity sweep's S2 (WAVE 2 · structural MEDIUM).
 *
 * `reservations.notes_guest` and `reservations.notes_internal` were WRITE-ONLY. Three paths wrote them —
 * the guest's arrival note at online check-in, and the guest note + internal team note an operator types on
 * New Reservation — and NO view in the application rendered either column. A guest could ask for a cot and
 * no member of staff would ever see it; the operator's own internal note was equally lost.
 *
 * The blind spot: both columns had passing tests. `OperatorNewReservationTest` asserted they PERSIST and
 * `GuestErasureTest` asserted they are ERASED — so the write path and the GDPR path were both proven, and
 * nothing asserted anyone could ever READ them. Persistence is not visibility.
 *
 * ⚠️ Operator-only by construction (`/app` is behind EnsureBackOfficeAccess). The internal note is
 * staff-authored context and must never reach an owner or guest surface (#28).
 */
it('shows a guest’s arrival note from online check-in to an operator on the reservation', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // Exactly what GuestCheckinController writes when a guest completes online check-in.
    $reservation->forceFill(['notes_guest' => 'Travelling with a cot, arriving late.'])->save();

    Livewire::actingAs(operatorUser($s))
        ->test(ReservationDetail::class, ['reservation' => $reservation->id])
        ->assertSee('Notes & requests')
        ->assertSee('From the guest')
        ->assertSee('Travelling with a cot, arriving late.');
});

it('shows the operator’s internal team note on the reservation', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $reservation->forceFill(['notes_internal' => 'VIP — comp the breakfast.'])->save();

    Livewire::actingAs(operatorUser($s))
        ->test(ReservationDetail::class, ['reservation' => $reservation->id])
        ->assertSee('Internal team note')
        ->assertSee('VIP — comp the breakfast.');
});

it('omits the card entirely when there are no notes, rather than rendering an empty shell', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    expect($reservation->notes_guest)->toBeNull()
        ->and($reservation->notes_internal)->toBeNull();

    Livewire::actingAs(operatorUser($s))
        ->test(ReservationDetail::class, ['reservation' => $reservation->id])
        ->assertDontSee('Notes & requests')
        // 🪤 and never the wireframe's fabricated sample notes / unbuilt action (#39)
        ->assertDontSee('Late checkout requested')
        ->assertDontSee('High floor')
        ->assertDontSee('Add note');
});

it('stops showing an erased guest’s notes — the GDPR path still clears both', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $reservation->forceFill([
        'notes_guest' => 'Please leave a cot for my daughter Mia',
        'notes_internal' => 'Called from +351 910 000 001',
    ])->save();

    $s['guest']->anonymize();

    $fresh = Reservation::query()->findOrFail($reservation->id);
    expect($fresh->notes_guest)->toBeNull()
        ->and($fresh->notes_internal)->toBeNull();

    // Surfacing the columns must not outlive erasure — the card disappears with the data.
    Livewire::actingAs(operatorUser($s))
        ->test(ReservationDetail::class, ['reservation' => $reservation->id])
        ->assertDontSee('Please leave a cot for my daughter Mia')
        ->assertDontSee('+351 910 000 001')
        ->assertDontSee('Notes & requests');
});
