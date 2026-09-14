<?php

use App\Services\FolioService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * F-39 — THE IRREVERSIBILITY WARNING PRECEDES BOTH REFUND ACTIONS.
 *
 * ⚠️⚠️ THIS ASSERTS ORDER, NOT EXISTENCE. A test that only checks the warning is PRESENT passes with it
 * sitting below the button that issues the refund — which is exactly the state this finding describes, and
 * exactly the shape that let three flash findings hide. The assertion here is the warning's OFFSET IN THE
 * RENDERED DOCUMENT against each button's offset.
 *
 * ⚠️⚠️ AND THE BRIEF'S PREMISE WAS WRONG, WHICH IS WHY BOTH PATHS ARE ASSERTED. The fix was described as
 * "copy the amount-aware form, which is ordered correctly". It was NOT ordered correctly. The warning lived
 * in the right rail (`.vy-detail-cols` → a 340px aside), which at 1280 begins higher than the second form —
 * so the warning MEASURED above the amount-aware button (661 vs 829) and looked right. That was a COLUMN
 * ARTEFACT. At 375 the grid collapses to one column and source order governs: 🖥 MEASURED, the warning fell
 * to 2152, BELOW the one-click button at 1113 AND the amount-aware button at 1472. There was no correctly
 * ordered sibling to copy, so BOTH paths are pinned here.
 *
 * ⚠️ WHY DOM ORDER IS NOW A SOUND PROXY FOR THE GEOMETRY. The confounder was the two-column grid: the
 * warning and the buttons were in DIFFERENT columns, so document order did not imply visual order. The
 * warning now sits in the SAME column and the SAME card as the buttons, above them in one flow — so at
 * every width its position follows document order. (The geometry itself was read with
 * `getBoundingClientRect()` in a browser; that reading is real but NOT re-runnable — there is no E2E suite
 * in this repo — which is precisely why this committed test exists.)
 */
/**
 * A settled payment on a real folio — {@see OperatorRefundScreenTest}'s idiom, reused rather than
 * re-invented. ⚠️ ACCOUNTANT, not the Hotel Manager default: the screen is gated on `payments.refund`
 * (#30), which the catalog deliberately withholds from a manager. The fixture moves to a role that
 * genuinely holds it — the check is never weakened to suit the test.
 */
function rwpaScreen(): string
{
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', moneyEur(30000));
    $folios->recordPayment($folio, moneyEur(30000));

    return test()->actingAs(operatorUser($s, [], 'Accountant'))
        ->get("/app/reservations/{$reservation->id}/refund")
        ->assertOk()
        ->getContent();
}

it('renders the irreversibility warning BEFORE both refund buttons', function () {
    $html = rwpaScreen();

    $warning = mb_strpos($html, 'It can’t be reversed');
    $oneClick = mb_strpos($html, 'Refund the remaining');
    $amountAware = mb_strpos($html, 'Review &amp; issue refund');

    expect($warning)->not->toBeFalse('the warning must be on the page at all');
    expect($oneClick)->not->toBeFalse('the one-click refund button must be on the page');
    expect($amountAware)->not->toBeFalse('the amount-aware refund button must be on the page');

    // ⚠️ THE WHOLE FINDING, IN TWO COMPARISONS. A consequence disclosed after the action is not a disclosure.
    expect($warning)->toBeLessThan($oneClick, 'the warning must precede the ONE-CLICK refund button');
    expect($warning)->toBeLessThan($amountAware, 'the warning must precede the AMOUNT-AWARE refund button');
});

/**
 * ⚠️ MOVED, NOT COPIED. Two copies of one sentence is how surfaces drift (§N.8f) — and a second copy left
 * behind in the right rail would satisfy the ordering assertion above while the original stayed put.
 */
it('carries exactly one copy of the warning', function () {
    expect(mb_substr_count(rwpaScreen(), 'It can’t be reversed'))->toBe(1);
});

/**
 * ⚠️ THE WORDING IS UNTOUCHED — this is a POSITION fix. Phase 7 called this the best disclosure in the
 * product and two later passes copied its register, so the sentence is pinned verbatim here: a future
 * "tidy" of the copy has to break this test deliberately rather than by accident.
 */
it('keeps the warning wording verbatim', function () {
    expect(rwpaScreen())->toContain(
        'A refund reverses a payment, not the charges — so the folio shows the guest owing again. Refunds cannot be reversed once issued.'
    );
});
