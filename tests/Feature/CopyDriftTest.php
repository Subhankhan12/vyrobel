<?php

declare(strict_types=1);

use App\Mail\CancellationMail;
use App\Mail\RefundConfirmationMail;
use App\Models\OwnerPayoutPreference;
use App\Support\Money\RefundEta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * REGRESSION — wireframe-parity sweep WAVE 2, the ANTI-DRIFT copy items (#3, #5, #8).
 *
 * Each was the same failure as two `match`es for one concept, applied to a SENTENCE: one idea written down
 * twice, in two files, already disagreeing. The project's own rule — one definition per concept — is what
 * these restore.
 */

/**
 * #8 — two mailables carried two different refund ETAs for the SAME money movement.
 *
 * CancellationMail promised "3–5 business days"; RefundConfirmationMail said "5–10". A guest whose booking
 * was cancelled AND refunded received both, about one refund.
 */
it('states ONE refund ETA across every email that mentions it', function () {
    $etas = [];

    foreach (['app/Mail/CancellationMail.php', 'app/Mail/RefundConfirmationMail.php'] as $file) {
        $source = file_get_contents(base_path($file));
        // No hardcoded day-range literal may survive in either mailable.
        expect($source)->not->toMatch('/\d+[–-]\d+ business days/');
        $etas[] = str_contains($source, 'RefundEta::WINDOW');
    }

    expect($etas)->toBe([true, true])
        ->and(RefundEta::WINDOW)->toBe('5–10 business days');
});

/**
 * #3 — the payout-frequency option had two labels, and one of them was WRONG.
 *
 * Payout Method offered "Every 2 weeks" while Payouts offered "Twice monthly" for the identical
 * `twice_monthly` value — and `PayoutSchedule` actually runs the Nth business day from the 1st and again
 * from the 16th. That is 24 runs a year; "every 2 weeks" is 26. The canonical label is the one the
 * scheduler's own model publishes.
 */
it('labels the payout frequency from the ONE canonical source, never a second literal', function () {
    expect(OwnerPayoutPreference::frequencyLabel('twice_monthly'))->toBe('Twice monthly');

    foreach (['payout-method', 'payouts'] as $view) {
        $blade = file_get_contents(resource_path("views/livewire/owner/{$view}.blade.php"));

        expect($blade)->toContain('frequencyOptions')
            // the wrong label, and the right one hardcoded, are both gone from the selects
            ->and($blade)->not->toContain("'twice_monthly' => 'Every 2 weeks'")
            ->and($blade)->not->toContain("'twice_monthly' => 'Twice monthly'");
    }
});

/**
 * #5 — the annual tax card asserted "All 12 months" for any year.
 *
 * A year with three finalized statements is not twelve months, and the annual PDF beside it prints the
 * REAL span — so the card disagreed with its own document.
 */
it('describes the annual tax year by its REAL statement span, not "All 12 months"', function () {
    $blade = file_get_contents(resource_path('views/livewire/owner/documents.blade.php'));

    expect($blade)->not->toContain('All 12 months')
        ->and($blade)->toContain('statement_count')
        ->and($blade)->toContain('span_label');
});

it('still renders the refund emails with the shared ETA', function () {
    // Renders through the real templates — a shared constant is worthless if the view stops printing it.
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    expect((new CancellationMail($reservation, 'Guest requested', 10000))->render())
        ->toContain(RefundEta::WINDOW);
});
