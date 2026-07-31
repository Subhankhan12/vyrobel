<?php

use App\Mail\BookingConfirmationMail;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// #109 GATE 2 — the Email Previews gallery renders EACH real Mailable (can't drift) + a real test-send to the
// operator's OWN inbox only (the certified rule; never an arbitrary/guest address), gated properties.manage (#30).

function previewBooking(array $s): Reservation
{
    $s['guest']->update(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.test']);

    return book($s, ['unit_id' => $s['units'][0]->id]);
}

// ── 1. The gallery lists all 9 automated emails + honest status ─────────────────────────────────────
it('renders the previews gallery with every automated email and honest status', function () {
    $s = seedScenario(1);
    previewBooking($s);
    $admin = operatorUser($s, [], 'Property Owner');

    $this->actingAs($admin)->withSession(['current_property_id' => $s['property']->id])
        ->get(route('app.email-previews'))
        ->assertOk()
        ->assertSee('Booking confirmation')
        ->assertSee('Cancellation')
        ->assertSee('Refund confirmation')
        ->assertSee('Verify email')
        ->assertSee('Live · sends on booking confirmed')   // the honest status caption
        ->assertSee('Send test');
});

// ── 2. Each preview renders the REAL Mailable (can't drift) ──────────────────────────────────────────
it('renders each preview as the real mailable', function () {
    $s = seedScenario(1);
    previewBooking($s);
    $admin = operatorUser($s, [], 'Property Owner');
    $session = ['current_property_id' => $s['property']->id];

    $this->actingAs($admin)->withSession($session)->get(route('app.email-previews.show', 'booking-confirmation'))
        ->assertOk()->assertSee('Booking confirmed', false);
    $this->actingAs($admin)->withSession($session)->get(route('app.email-previews.show', 'cancellation'))
        ->assertOk()->assertSee('Your booking is cancelled', false);
    // Unknown key 404s.
    $this->actingAs($admin)->withSession($session)->get(route('app.email-previews.show', 'nope'))->assertNotFound();
});

// ── 3. Test-send delivers the real email to the operator's OWN inbox, and is capability-gated ────────
it('test-sends the real email to the operator’s own inbox only, gated properties.manage', function () {
    Mail::fake();
    $s = seedScenario(1);
    previewBooking($s);
    $admin = operatorUser($s, ['email' => 'boss@demo.test'], 'Property Owner');
    $session = ['current_property_id' => $s['property']->id];

    $this->actingAs($admin)->withSession($session)
        ->post(route('app.email-previews.test', 'booking-confirmation'))
        ->assertRedirect(route('app.email-previews'))
        ->assertSessionHas('email_preview_notice');

    // Sent the REAL Mailable to the OPERATOR'S OWN email (never a guest/arbitrary address).
    Mail::assertSent(BookingConfirmationMail::class, fn (BookingConfirmationMail $m): bool => $m->hasTo('boss@demo.test'));

    // A role without properties.manage cannot test-send.
    $frontDesk = operatorUser($s, [], 'Front Desk');
    $this->actingAs($frontDesk)->withSession($session)
        ->post(route('app.email-previews.test', 'booking-confirmation'))->assertForbidden();
});
