<?php

use App\Models\Folio;
use App\Services\FolioService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Assert;

// ══ D9 · D8 · D7 — FALSE CLAIMS ON PUBLISHED PAGES, GUARDED AT THE RENDERED LAYER ═════════════════
//
// ⚠️⚠️ EVERY ASSERTION BELOW READS THE RENDERED PAGE, never `tools/marketing/core/*.html`. Reading the
// design MASTER to decide what a live page says is the wrong-layer error this project has made THREE
// times — most expensively when it wrote a false founder-decision row into the state file. `mkPage()`
// renders the same view the router serves.
//
// ⚠️ These pages are GENERATED (`pxbuild.py` / `salesbuild.py` over `convert.py`). A guard here is what
// makes the generator's honesty transforms load-bearing: reintroducing a claim upstream reds this file.

// ── D9 · THE FALSE DATA-PORTABILITY PROMISE ───────────────────────────────────────────────────────
//
// ⚠️ THE MOST CONSEQUENTIAL OF THE FOUR. It sat on the MIGRATION page, read by someone leaving a
// competitor, and a portability promise is also read as a COMPLIANCE statement.
// ⚠️ MEASURED against `route:list`: reservations ✗ (Reports emits AGGREGATES), units ✗ operator-side,
// rates ✗ anywhere, statements ✗ operator-side, guests ✓ — and all 14 `app.settings.*` routes
// enumerated, NONE an export, so the LOCATION was false too.
it('⚠️ D9 — /switching no longer promises a full export, or names Settings', function () {
    $page = mkPage('switching');

    expect($page)->not->toContain('Full export of reservations');
    expect($page)->not->toContain('on demand, from Settings');
    // ⚠️ NOT NEGATED, NARROWED. The canon forbids answering a false claim by quoting it — a denial leaves
    //    the phrase on the page for a skimmer — so the page must not carry the words at all.
    expect(strtolower($page))->not->toContain('full export');
});

it('⚠️ D9 — what /switching now says is true of a real route, and names real screens', function () {
    $page = mkPage('switching');

    expect($page)->toContain('You export from the screen that owns the data');
    // Every capability named below was verified against a registered route.
    expect($page)->toContain('guest records to CSV from Guests');          // app.guests.bulk.export
    expect($page)->toContain('reports to CSV, Excel or PDF from Reports'); // app.reports.export(.xlsx/.pdf)
    expect($page)->toContain('Your own account record downloads from Security'); // app.security.export

    // ⚠️ A commercial term nothing in this repository can evidence — dropped, not reworded.
    expect($page)->not->toContain('No exit fee');
});

it('⚠️ D9 — the capabilities the page names still exist as routes (the claim cannot outlive them)', function () {
    // ⚠️ THE POINT OF THIS TEST: copy and code drift apart silently. If any export route below is ever
    //    removed, the sentence on /switching becomes false again — and this reds instead of shipping.
    foreach (['app.guests.bulk.export', 'app.reports.export', 'app.security.export'] as $name) {
        expect(app('router')->has($name))->toBeTrue("route {$name} is named on /switching but does not exist");
    }
});

// ── D8 · RESIDUE OF A DELETED CONTROL ─────────────────────────────────────────────────────────────
//
// ⚠️ The EUR/GBP/USD switcher was deleted in `3b155dc` (inert buttons over a EUR-only engine). The COPY
// describing it survived: the PDF blurb still said "in your selected currency" when nothing selects.
// ⚠️ THE CLASS: a deletion is not finished until the sentences that depended on it are gone too.
it('⚠️ D8 — /pricing no longer offers a currency the reader cannot choose', function () {
    $page = mkPage('pricing');

    expect($page)->not->toContain('in your selected currency');

    // The switcher itself must stay gone — the control and its copy are one fix.
    expect($page)->not->toContain('Display currency');

    // ⚠️⚠️ THE POSITIVE ANCHOR MOVED, AND WHY IT HAD TO. This line used to read
    //     expect($page)->toContain("Prints as a one-page summary with the plan you're viewing");
    // which was never a claim that the sentence must exist for ever — it was an ANTI-VACUITY ANCHOR,
    // proving the negation above was checked against a real rendered page rather than an error page or an
    // empty string. ⚠️ That sentence has since been removed BY THIS RULE, one step further out: it
    // described the "Download pricing as PDF" button, the button was a dead control with no PDF behind it
    // anywhere in the app, and a deletion is not finished until the sentences that depended on it are gone
    // too. Keeping the old anchor would have required keeping a description of a button that no longer
    // exists — the exact residue D8 was written to forbid.
    // ⚠️ SO THE ANCHOR IS RE-POINTED, NOT DROPPED: at the surviving sibling in the same row, which keeps
    // the negations above honest.
    expect($page)->toContain('Request a formal quote');

    // ⚠️ AND THE RULE EXTENDS TO THIS REMOVAL, so it cannot come back unnoticed.
    expect($page)->not->toContain('Download pricing as PDF');
    expect($page)->not->toContain('Prints as a one-page summary');
});

// ── D7 · THREE NUMBERS ON ONE PAGE ────────────────────────────────────────────────────────────────
//
// ⚠️ "16 modules" while 18 render and the <h1> says "Eighteen". The canon rule is explicit: never type a
// number into a page. The counter is now DERIVED in the converter from the cards actually present.
it('⚠️ D7 — /modules counts what it renders', function () {
    $page = mkPage('modules');

    $cards = substr_count($page, 'data-module="');
    expect($cards)->toBeGreaterThan(0);           // ⚠️ the fixture bites: a zero count would pass vacuously

    expect($page)->toContain($cards.' modules');
    expect($page)->not->toContain('16 modules');
});

it('⚠️ D7 — the headline word and the rendered cards cannot disagree', function () {
    $page = mkPage('modules');
    $cards = substr_count($page, 'data-module="');

    // ⚠️ The <h1> word is TYPED, not derived — spelling a number for one headline is not worth a map, and
    //    the display copy is hand-set. So it is ASSERTED instead: if the cards ever move, this reds.
    $words = [16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty'];
    expect($words)->toHaveKey($cards);
    expect($page)->toContain($words[$cards].' modules.');
});

it('⚠️ D7 — the sweep found a FOURTH number: / and /modules cannot disagree', function () {
    // ⚠️ The brief said "three numbers, one page". It was FOUR across TWO: `/` said "Sixteen modules" while
    //    `/modules` renders eighteen. Found by sweeping the CLASS, not the instance — which is the rule.
    //    The home word is now derived from the modules design itself, cross-page.
    $cards = substr_count(mkPage('modules'), 'data-module="');
    $words = [16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty'];

    expect($words)->toHaveKey($cards);
    expect(mkPage('home'))->toContain($words[$cards].' modules, and every one is in every plan');
    expect(mkPage('home'))->not->toContain('Sixteen modules, and every one');
});

// ── ⚠️⚠️ THE CONVERTER'S OWN DEFECT — a needle that cut a sentence in half ────────────────────────
//
// ⚠️ The `switching-body` transform's needle stopped at "…self-serve with a CSV" while the master's
// sentence ran on "…importer and written guides, which is fine for one property." The tail was published
// BEHIND the replacement: "…ask us rather than assume it importer and written guides, which is fine for
// one property." — prose ending mid-thought, on a live page.
//
// ⚠️⚠️ WHY EVERY EARLIER SWEEP MISSED IT: THE MASTER IS CORRECT AND THE OUTPUT IS NOT. A check that reads
// `core/*.html` sees a clean sentence. This is the wrong-layer read from the OTHER direction — and it is
// why this file only ever asserts against the rendered view.
//
// ⚠️ AND WHY THE ZERO-MATCH GUARD MISSED IT: `_NEEDLE_LOG` proves a needle matched SOMETHING. It cannot
// know it matched the RIGHT SPAN, and it never reads the output. A needle can match and still be wrong.
it('⚠️ the /switching migration sentence is not cut in half by its own transform', function () {
    $page = mkPage('switching');

    // The dangling remainder must not appear at all.
    expect($page)->not->toContain('assume it importer and written guides');
    expect($page)->not->toContain('importer and written guides');

    // And the replacement must terminate as a sentence.
    expect($page)->toContain('ask us rather than assume it.');
});

// ── D4 · A REFUSAL RENDERED AS A SUCCESS ──────────────────────────────────────────────────────────
//
// ⚠️⚠️ MONEY-ADJACENT, AND THE MESSAGE WAS NEVER THE PROBLEM. Invariant #27 refuses an over-refund and
// says so correctly — but `folio_notice` carried no OUTCOME, and all three views hardcoded a CHECKMARK.
// A desk clerk reads the tick and tells the guest the money is on its way.
// ⚠️ THE REFUSAL LOGIC IS UNTOUCHED by this pass. These assert PRESENTATION over a working guard.
uses(RefreshDatabase::class);

/**
 * A stay with a SETTLED €200 payment — the only shape invariant #27 can refuse against.
 *
 * ⚠️ Deliberately a LOCAL fixture, not a reach into `OperatorRefundGate2Test`: Pest scopes helper functions
 * to their file, and a cross-file call resolves only when both happen to load — a green that depends on
 * run order. Built from the SHARED helpers (`seedScenario`/`book`/`postRoom`/`collectFolio`) instead.
 */
function d4Folio(): array
{
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);       // €200 room revenue
    collectFolio($reservation);          // the guest actually PAID

    $folio = Folio::query()->where('reservation_id', $reservation->id)->firstOrFail();
    $payment = app(FolioService::class)->recordPayment($folio, Money::of(20000, 'EUR'));

    return compact('s', 'reservation', 'folio', 'payment');
}

it('⚠️ D4 — a REFUSED refund does not render as a success', function () {
    $c = d4Folio();
    $admin = operatorUser($c['s'], ['name' => 'Ada Admin'], 'Property Owner');

    // Refund MORE than was taken → #27 refuses. ⚠️ The guard, not the test, decides this.
    // ⚠️ The field is `payment`, not `payment_id` — read from the controller, not assumed.
    $html = $this->actingAs($admin)->followingRedirects()
        ->post(route('app.reservations.refund', $c['reservation']->id), [
            'payment' => (string) $c['payment']->id,
            'amount' => '9999.00',
        ])
        ->assertOk()
        ->getContent();

    expect($html)->toContain('That refund was refused');

    // ⚠️ (a) THE ICON — a refusal must not wear the success skin.
    // ⚠️ THE CLASS ATTRIBUTE, NOT THE BARE NAME. `.vy-toast--refused` is defined in the operator layout's
    //    inline <style>, so the bare string is present on EVERY operator page and an assertion on it could
    //    never fail — the assertion-that-cannot-fail shape. Caught by the success half below going red.
    expect($html)->toContain('class="vy-toast vy-toast--refused');
    expect($html)->toContain('Refused: ');

    // ⚠️ (b) THE ANNOUNCEMENT — `role="status"` present at page load never announces; a refusal is
    //    `role="alert"` and takes focus, which is what actually reaches a screen-reader user.
    expect($html)->toContain('role="alert"');
});

it('⚠️ D4 — a SUCCESSFUL folio action still renders as a success (the other half)', function () {
    $c = d4Folio();
    $admin = operatorUser($c['s'], ['name' => 'Ada Admin'], 'Property Owner');

    $html = $this->actingAs($admin)->followingRedirects()
        ->post(route('app.reservations.charge', $c['reservation']->id), [
            // ⚠️ Read from the controller's own validate() — `type` and the idempotency nonce are REQUIRED,
            //    and omitting them redirects back (to `/` in a test, with no referer) rather than charging.
            'type' => 'manual',
            'description' => 'Minibar',
            'amount' => '12.00',
            'idempotency_key' => 'd4-success-half',
        ])
        ->assertOk()
        ->getContent();

    expect($html)->toContain('Charge added.');
    // ⚠️ Without this half the guard is untested in the direction that costs: a component rendering
    //    EVERYTHING as refused would satisfy the test above. (This half is what caught the CSS-string
    //    false needle noted in the refusal test.)
    expect($html)->not->toContain('class="vy-toast vy-toast--refused');
    expect($html)->not->toContain('Refused: ');
});

// ── ⚠️⚠️ THE SWEEP GAP — A TYPED NUMBER SURVIVED A SWEEP FOR TYPED NUMBERS ────────────────────────
//
// ⚠️ The D7 sweep matched `\d+ (modules|guides|…)` — a number followed by a NOUN. A PERCENTAGE HAS NO
// NOUN, so "−15%" sat on /pricing through a pass whose entire subject was typed numbers. The class was
// right and the pattern was too narrow.
//
// ⚠️ THIS GUARD IS SHAPE-BASED, NOT PHRASE-BASED: it bans a bare typed PERCENTAGE anywhere in the
// commercial copy of /pricing, so the next one cannot survive the same way — whatever words surround it.
// ⚠️ It is deliberately scoped to /pricing: percentages elsewhere are illustrative mockup figures
// (a sample owner statement's "Management fee · 15% on net") or real derived rates, and banning those
// would red the build for correct work.
it('⚠️ /pricing carries no typed percentage at all — the shape, not the phrase', function () {
    $page = mkPage('pricing');

    // Strip scripts, styles and comments: a percentage inside CSS is not a claim.
    $visible = preg_replace('/<(script|style)\b.*?<\/\1>/is', '', $page);
    $visible = preg_replace('/<!--.*?-->/s', '', (string) $visible);
    $visible = preg_replace('/<[^>]+>/', ' ', (string) $visible);

    expect((string) $visible)->not->toMatch('/\d+(?:[.,]\d+)?\s*%/');
});

// ── ⚠️⚠️ THE COMMERCIAL LINE ON /pricing — THREE MONEY CLAIMS, PINNED TO THE CODE ─────────────────
//
// ⚠️ "No setup fee · No commission on direct bookings · Card processing is billed by your payment
//    provider" has been VISIBLE on the live page all along, and nothing guarded it. A promise about money
//    with no test behind it is the shape this project keeps finding: the copy stays while the code moves.
//
// ⚠️ A brief described this line as hidden behind `data-portfolionote`. It is not — that element is a
//    separate, EMPTY div (now removed) which the prototype filled from the ROI calculator with a per-unit
//    figure. This line is its own element, unhidden, two lines below.
//
// ⚠️ EACH CLAIM IS PINNED TO THE FACT THAT MAKES IT TRUE, not to its own words — so if the billing model
//    ever takes a per-booking cut, this reds rather than the sentence quietly becoming a lie.
it('⚠️ /pricing — the three money claims are still true of the code', function () {
    $page = mkPage('pricing');
    expect($page)->toContain('No setup fee');
    expect($page)->toContain('No commission on direct bookings');
    expect($page)->toContain('Card processing is billed by your payment provider');

    $app = collect(File::allFiles(base_path('app')))
        ->filter(fn ($f): bool => $f->getExtension() === 'php')
        ->map(fn ($f): string => (string) file_get_contents($f->getPathname()));

    // ⚠️ 1 · "No setup fee" — there is no such concept. A positive control below proves the sweep works.
    Assert::assertNotEmpty($app, 'no PHP was scanned — every assertion here would be vacuous');
    foreach (['setup_fee', 'setupFee', 'onboarding_fee', 'activation_fee'] as $fee) {
        Assert::assertFalse($app->contains(fn (string $s): bool => str_contains($s, $fee)),
            "app/ now references \"{$fee}\" — the page promises no setup fee");
    }

    // ⚠️ 2 · "No commission on direct bookings" — ON STRIPE CONNECT A PLATFORM TAKES ITS CUT VIA
    //    `application_fee_amount`. Its absence is what makes the promise true, so its absence is the test.
    foreach (['application_fee', 'application_fee_amount', 'transfer_data'] as $cut) {
        Assert::assertFalse($app->contains(fn (string $s): bool => str_contains($s, $cut)),
            "app/ now sets \"{$cut}\" — that IS a commission on a booking, and /pricing promises none");
    }

    // ⚠️ A POSITIVE CONTROL ON THE SWEEP. Two absence-assertions in a row are exactly the shape that
    //    passes on a broken scan; this proves the same scan finds a string that IS there.
    Assert::assertTrue($app->contains(fn (string $s): bool => str_contains($s, 'stripe_account')),
        'the source sweep found nothing it should have — the absences above prove nothing');

    // ⚠️ 3 · "Card processing is billed by your payment provider" — true because the charge is DIRECT:
    //    the `Stripe-Account` header puts the payment in the operator's connected account, so Stripe's
    //    fees come out of their balance and never the platform's.
    $client = (string) file_get_contents(app_path('Payments/Stripe/StripeClient.php'));
    Assert::assertStringContainsString("\$options['stripe_account'] = \$connectedAccountId;", $client,
        'charges no longer carry Stripe-Account — they would land on the PLATFORM, and the page says '.
        'card processing is billed by the operator’s own provider');
});
