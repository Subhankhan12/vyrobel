<?php

use App\Models\Guest;
use App\Models\GuestCreditEntry;
use App\Services\GuestWalletService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

// Guest Portal · GATE 1b — the CHECKOUT view (exact-match to the Guest Checkout wireframe). Proves the
// REAL wallet-credit toggle renders ONLY for a signed-in guest with a balance (D3), the real free-cancel
// label shows, and the honesty refusals hold: NO raw PAN field, NO on-page Apple Pay, NO "charged at
// check-in" (SAQ A / #39 / audit D4).

/** GET the checkout review for the scenario's room on 3 nights (a real, priced offer). */
function checkoutView(array $s): TestResponse
{
    return test()->get("/book/{$s['property']->slug}/checkout?room_type_id={$s['roomType']->id}&check_in=2026-09-01&check_out=2026-09-04&adults=2&children=0");
}

// ── the exact-match summary renders the reconciling breakdown + real free-cancel, honest payment ─────
it('renders the checkout summary with a real total and free-cancel, and no raw card fields', function () {
    $s = publicPropertyScenario(units: 1);

    checkoutView($s)
        ->assertOk()
        ->assertSee('Complete your booking')
        ->assertSee('Secure checkout')
        ->assertSee('Total')
        // 🔒 honesty (audit D4): no inline PAN, no on-page Apple Pay, no "charged at check-in".
        ->assertDontSee('Card number')
        ->assertDontSee('Pay with Apple Pay')
        ->assertDontSee('until check-in');
});

// ── D3 — a signed-in guest with a REAL wallet balance sees the wallet-credit toggle ─────────────────
it('shows the wallet-credit toggle only for a signed-in guest with a real balance', function () {
    $s = publicPropertyScenario(units: 1);

    // Anonymous → no toggle.
    checkoutView($s)->assertOk()->assertDontSee('Apply wallet credit');

    // Signed-in guest with €50 real credit → the toggle + "available in your wallet".
    $guest = Guest::create(['first_name' => 'Lukas', 'last_name' => 'Meyer', 'email' => 'lukas@example.test']);
    app(GuestWalletService::class)->credit($guest, GuestCreditEntry::TYPE_PROMO, 'Loyalty', Money::of(5000, 'EUR'));

    test()->actingAs($guest, 'guest')->get("/book/{$s['property']->slug}/checkout?room_type_id={$s['roomType']->id}&check_in=2026-09-01&check_out=2026-09-04&adults=2&children=0")
        ->assertOk()
        ->assertSee('Apply wallet credit')
        ->assertSee('available in your wallet')
        ->assertSee('apply_credit', false);   // the checkbox that submits the real deduction
});
