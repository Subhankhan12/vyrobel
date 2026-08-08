<?php

declare(strict_types=1);

use App\Livewire\Owner\OwnerBookingDetail;
use App\Support\Owner\OwnerCommissionRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * REGRESSION — wireframe-parity sweep WAVE 3 (minor gaps), owner realm.
 */

/**
 * The commission rate was written TWO ways on ONE page.
 *
 * `management_agreements.commission_pct` is a DB decimal, so `(string) $pct` yields "18.00".
 * `OwnerAgreement::commissionDisplay()` did exactly that while `OwnerAgreement::pct()`, two methods below,
 * trimmed to "18%" — so the hero read "18.00%" and the per-villa table read "18%" for the same agreement.
 * A legally-meaningful rate written two ways on one screen is the drift rule applied to a percentage.
 */
it('leaves no hand-rolled commission formatter to drift from the shared one', function () {
    // ⚠️ SCOPE, stated honestly: the PAGE-level proof for this item is the browser render (the agreement
    // page showed "18.00%" twice beside "18%" twice, and now shows "18%" four times). Reproducing that in a
    // fixture needs the owner middleware to activate OwnerContext, which Livewire::test skips and which the
    // HTTP fixture here does not populate with an effective agreement. What IS worth pinning permanently is
    // the cause: three separate formatters for one rate. This asserts none can come back.
    $agreement = file_get_contents(app_path('Livewire/Owner/OwnerAgreement.php'));
    $terms = file_get_contents(app_path('Support/Owner/OwnerAgreementTerms.php'));

    foreach ([$agreement, $terms] as $src) {
        expect($src)->toContain('OwnerCommissionRate::display')
            // no local rtrim/number_format percent trimming, and no raw decimal cast
            ->and($src)->not->toMatch('/rtrim\\(rtrim\\(number_format/')
            ->and($src)->not->toContain("((string) \$pct).'%'");
    }
});

it('formats a commission through the ONE shared helper', function () {
    // The canonical form: trimmed, with the caller appending the unit.
    expect(OwnerCommissionRate::trim('18.00'))->toBe('18')
        ->and(OwnerCommissionRate::display('18.00'))->toBe('18%')
        ->and(OwnerCommissionRate::display('17.50'))->toBe('17.5%')
        ->and(OwnerCommissionRate::display(null))->toBeNull();
});

/**
 * The booking timeline rendered "Check-in — upcoming" ABOVE a completed "Checked out".
 *
 * The check-in branch fired on any future-dated check-in and never consulted the reservation's terminal
 * status, so a stay that had ended still advertised an upcoming arrival. One demo reservation is genuinely
 * in this state (checked_out, future check_in, no guest_checked_in_at stamp).
 */
it('does not show an upcoming check-in on a booking that has already ended', function () {
    $s = seedScenario(1);
    ownUnit($s['units'][0]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);   // Sept 2026 — future
    $reservation->forceFill([
        'status' => 'checked_out',
        'guest_checked_in_at' => null,   // never stamped, exactly like the demo row
    ])->save();

    $timeline = Livewire::actingAs(ownerRoleUser($s))
        ->test(OwnerBookingDetail::class, ['booking' => $reservation->id])
        ->viewData('timeline');

    $labels = array_column($timeline, 'label');

    // The completed departure is shown; no upcoming arrival is claimed above it.
    expect($labels)->toContain('Checked out')
        ->and($labels)->not->toContain('Check-in');
});

it('still shows the upcoming check-in on a live future booking', function () {
    $s = seedScenario(1);
    ownUnit($s['units'][0]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);   // confirmed, future

    $timeline = Livewire::actingAs(ownerRoleUser($s))
        ->test(OwnerBookingDetail::class, ['booking' => $reservation->id])
        ->viewData('timeline');

    // The fix must not swallow the legitimate case — a live booking DOES have an upcoming check-in.
    expect(array_column($timeline, 'label'))->toContain('Check-in');
});
