<?php

declare(strict_types=1);

use App\Mail\PaymentReminderMail;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * REGRESSION — wireframe-parity sweep WAVE 2: copy that promised something the product does not do
 * (#4, #7, #9, #15, #19). Prototype fiction that survived into shipped text.
 */

/**
 * #9 — the search help told operators to search `VYR-…`.
 *
 * Real references are `R-…`, so an operator following the on-screen instruction got ZERO results and would
 * reasonably conclude search was broken. Prototype fiction that reached help text.
 */
it('tells operators the REAL reservation reference format', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // The format the help promises must be the format the data actually uses.
    expect($reservation->reference)->toStartWith('R-');

    $help = file_get_contents(resource_path('views/livewire/operator/operator-search.blade.php'));
    expect($help)->toContain('(R-…)')
        ->and($help)->not->toContain('VYR-');
});

/**
 * #7 — the payment reminder promised Apple Pay.
 *
 * The destination it links to explicitly refuses Apple Pay (SAQ A / hosted Checkout, wallets not enabled),
 * so the email advertised a payment method the guest could not use.
 */
it('does not promise a payment method the checkout refuses', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $html = (new PaymentReminderMail(Reservation::query()->findOrFail($reservation->id), 10000))->render();

    expect($html)->not->toContain('Apple Pay')
        ->and($html)->toContain('Visa');   // the methods that ARE offered still are
});

/**
 * #4 — the owner Help page showed a hardcoded green "All systems operational".
 *
 * No health check exists behind it, so it read green during any outage — the one moment the words matter.
 * Reworded rather than faked; building a real status page is a feature, not a copy fix.
 */
it('does not assert live system status it cannot observe', function () {
    $help = file_get_contents(resource_path('views/livewire/owner/help.blade.php'));

    expect($help)->not->toContain('All systems operational');
});

/**
 * #15 — the waitlist promised "we'll email you first" when a spot opens.
 *
 * No trigger exists that watches for a freed spot, so nothing would ever send. The adjacent deferrals in the
 * same sentence were already honest ("coming soon"); this clause was not.
 */
it('does not promise an email no trigger can send', function () {
    $saved = file_get_contents(resource_path('views/guest/saved.blade.php'));

    expect($saved)->not->toContain('we’ll email you first')
        ->and($saved)->toContain('we’ll be in touch');
});

/**
 * #19 — the inbox composer footnote assumed every thread was a GUEST thread.
 *
 * The owner↔operator relay has no guest and no booking channel, so "The guest replies wherever they booked"
 * was simply untrue there — and, worse, implied a guest could see an owner thread.
 */
it('branches the inbox footnote on what the thread actually is', function () {
    $inbox = file_get_contents(resource_path('views/livewire/operator/operator-inbox.blade.php'));

    expect($inbox)->toContain('is_owner_thread')
        ->and($inbox)->toContain('Your owner replies in their portal')
        ->and($inbox)->toContain('The guest replies wherever they booked');   // still true for guest threads
});
