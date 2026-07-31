<?php

use App\Services\FolioService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104 GATE 1c — the reservation-detail activity trail now merges the money actions (from the real audit_events
// on the reservation's payments) with the lifecycle transitions — the who-did-what-when the status-only trail
// missed — and the guest card cross-links to the CRM (the #28-honest form of the wireframe's inline guest card).

it('shows a recorded payment in the activity trail with the acting operator', function () {
    $s = seedScenario(1);
    $reservation = book($s);
    $manager = operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');

    // Record a real payment as the manager (writes a payment.recorded audit_event, subject = the Payment).
    $this->actingAs($manager);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->recordPayment($folio, Money::of(5000, 'EUR'));

    $html = $this->actingAs($manager)->get(route('app.reservations.show', $reservation->id))
        ->assertOk()
        ->assertSee('Activity')          // the card is no longer "Status history"
        ->assertSee('Payment recorded')  // the money action is in the trail
        ->assertSee('Rosa Manager');     // with the real actor

    expect($html->getContent())->toContain('Payment recorded');
});

it('cross-links the guest to the CRM profile (name-only on this operational surface, #28)', function () {
    $s = seedScenario(1);
    $reservation = book($s);
    $manager = operatorUser($s, [], 'Hotel Manager');   // holds guests.view

    $this->actingAs($manager)->get(route('app.reservations.show', $reservation->id))
        ->assertOk()
        ->assertSee('View profile')
        ->assertSee(route('app.guests.show', $s['guest']->id));
});
