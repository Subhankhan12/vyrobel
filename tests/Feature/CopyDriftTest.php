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

/**
 * ⚠️ G10 — THE WALLET'S "How credits work" CARD STATED A POLICY THE CODE DOES NOT IMPLEMENT.
 *
 * Three sentences, checked one at a time against the engine; two were false.
 *
 *  (a) "Credit comes from refunds, cancelled add-ons and any code you redeem." Only the CODE has a
 *      production writer ({@see GuestWalletService::redeemCode()}). A refund returns money to the payment
 *      method ({@see FolioService::refundPayment()}) and posts NO wallet credit; TYPE_ADD_ON_CANCELLED
 *      exists as a constant and a display label with no writer anywhere in app/. The DEMO looks right only
 *      because DemoSeeder posts a refund credit directly — a demo-only appearance, the #39 shape.
 *
 *  (b) "Most credits are valid for 12 months from the day they land." No 12-month rule exists in any path:
 *      `credit()` defaults `expiresAt` to NULL (never expires) and a promo credit inherits the PROMO's own
 *      expiry. The sentence came from the prototype mock — the SAME failure as the door code's hardcoded
 *      "48h", which GuestReservationTest already guards ("derives the unlock date from ArrivalPack, never a
 *      hardcoded interval"). Both of the demo's real credits disproved it: one never expires, the other
 *      expires in 60 days — and the page printed that 60-day date directly above the claim.
 *
 * ⚠️ §N.8d BOTH DIRECTIONS: (a) OVERSTATES (a guest waits for credit after a refund that will never arrive);
 * (b) issues a FALSE INSTRUCTION (spend within a year, or treat older credit as dead when it never expires).
 */
it('never restates a credit expiry policy or a credit source the wallet engine does not implement', function () {
    $blade = file_get_contents(resource_path('views/guest/wallet.blade.php'));
    $rendered = preg_replace('/\{\{--.*?--\}\}/s', '', $blade);   // Blade comments never reach the guest

    expect($rendered)->not->toContain('12 months')                 // no fabricated expiry policy
        ->and($rendered)->not->toContain('after a refund')         // no refund→credit promise (no writer)
        ->and($rendered)->not->toContain('cancelled add-ons');     // TYPE_ADD_ON_CANCELLED has no writer

    // …and it still says the two things that ARE true.
    expect($rendered)->toContain('any code you redeem')            // redeemCode() — the one real source
        ->and($rendered)->toContain('doesn’t expire');             // expiresAt defaults to NULL
});

/**
 * ⚠️ G10 — AN EXPIRED CREDIT MUST NOT LOOK LIKE MONEY YOU HAVE (§N.8d — the VISUAL outlives the text).
 *
 * `spendableMinor()` excludes an expired credit, and the hero says so. The history row still rendered its
 * amount in the same confident green "+" as a live credit, with the word "expired" alone in the label:
 * the figure contradicted the balance directly above it. The row now renders muted + struck, like a debit.
 */
it('renders an EXPIRED wallet credit as excluded — muted and struck, never as live green money', function () {
    $blade = file_get_contents(resource_path('views/guest/wallet.blade.php'));

    // The amount's colour must depend on `expired`, not on `is_debit` alone.
    expect($blade)->toMatch('/\$entry\[\047is_debit\047\]\s*\|\|\s*\$entry\[\047expired\047\]/')
        ->and($blade)->toContain('line-through');
});

/**
 * ⚠️ G10 — THE LAST `--vy-amber`-AS-TEXT SITE IN THE GUEST REALM (DO-G6-2 CLOSED).
 *
 * `--vy-amber` (rgb 178,106,8) is a FILL token — rails, borders, washes. As TEXT it measured 4.08 on the
 * Saved card plate (10.5px "· expired"), under AA. `--vy-amber-text` (rgb 126,88,17) measures 6.15 there.
 * G6 fixed payments/receipt/extras; this closes the tail. Borders and left-rails may still use the fill.
 */
it('never uses the --vy-amber FILL token as text anywhere in the guest realm', function () {
    $offenders = [];

    foreach (['views/guest', 'views/components/guest'] as $dir) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path($dir)));
        foreach ($it as $file) {
            if ($file->isDir() || ! str_ends_with((string) $file, '.blade.php')) {
                continue;
            }
            $src = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents((string) $file));
            // `color:` (never `border-left-color:` / `background`) set to the raw fill token.
            if (preg_match('/(?<![\w-])color:\s*var\(--vy-amber\)/', (string) $src)) {
                $offenders[] = basename((string) $file);
            }
        }
    }

    expect($offenders)->toBe([]);
});
