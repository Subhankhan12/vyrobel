<?php

declare(strict_types=1);

use App\Livewire\Owner\OwnerMyUnits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * REGRESSION — wireframe-parity sweep WAVE 2, items #11 and #12: figures shown under a label that
 * described something else. Both are on Owner My Units.
 */

/**
 * #11 — "This month" was the month of the owner's LATEST BOOKING.
 *
 * `resolveMonth()` defaulted to `max(check_in)` over the owner's active lines, so a forward booking in
 * December made the page report DECEMBER's occupancy under a caption reading "This month". The operator
 * Dashboard and My Units therefore printed different occupancy for the same unit on the same day.
 */
it('shows the CURRENT month, not the month of the furthest-forward booking', function () {
    $s = seedScenario(1);
    ownUnit($s['units'][0]);

    // seedScenario books into Sept 2026; whatever "now" is, the page must report NOW.
    book($s, ['unit_id' => $s['units'][0]->id]);

    $month = Livewire::actingAs(ownerRoleUser($s))
        ->test(OwnerMyUnits::class)
        ->viewData('month');

    expect($month['label'])->toBe(Carbon::now()->format('F Y'));
});

it('still honours an explicit ?month= so navigation keeps working', function () {
    $s = seedScenario(1);
    ownUnit($s['units'][0]);

    $month = Livewire::withQueryParams(['month' => '2026-09'])
        ->actingAs(ownerRoleUser($s))
        ->test(OwnerMyUnits::class)
        ->viewData('month');

    expect($month['label'])->toBe('September 2026');
});

/**
 * #12 — "Nightly rate" was fed the ACHIEVED ADR.
 *
 * A unit configured at €120 that sold two discounted nights displayed "Nightly rate €95". An owner reads
 * that as the price on their calendar, and it isn't — it is what the nights happened to sell for. The
 * configured rate was already loaded (it fills the editor's "Reset to …") and simply wasn't used.
 */
it('labels the configured rate as the nightly rate and the achieved average as ADR', function () {
    $s = seedScenario(1);
    ownUnit($s['units'][0]);

    $units = Livewire::actingAs(ownerRoleUser($s))
        ->test(OwnerMyUnits::class)
        ->viewData('units');

    $card = $units[0];

    // Both keys exist and are distinct concepts — the point of the fix is that they stop sharing a label.
    expect($card)->toHaveKey('nightly_rate')
        ->and($card)->toHaveKey('adr')
        // "Nightly rate" is now the CONFIGURED rate, which is null when no rate is set — never the ADR
        // silently standing in for it.
        ->and($card['nightly_rate'])->toBe($card['base_display']);
});
