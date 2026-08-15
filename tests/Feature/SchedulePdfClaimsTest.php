<?php

/*
 * DO-CORRECTNESS-1 · FIX 2 — THE SCHEDULE PDF NO LONGER PRINTS CLAIMS THE CODE CANNOT SUPPORT (2026-08-15).
 *
 * ⚠️ THE DEFECT (§N.8d, and rendered to a user — a rota gets PRINTED AND PINNED UP). The footer asserted
 * three things and TWO were false:
 *
 *   1. "there is no draft or published state"  — FALSE. `schedule_publications` is a real table,
 *      `ScheduleController::publishWeek()` writes it, and the screen has a working Publish button. The
 *      document was denying a feature the product ships.
 *   2. "statutory rest periods are observed between late and early shifts" — FALSE, and the worst of the
 *      three: NOTHING in the codebase computes or enforces a rest period. `RosterWeek::$conflictCount` is
 *      leave-vs-shift only. A printed rota was making a labour-law assurance the software does not stand
 *      behind. ⚠️ This one is NOT in the register — it was found by reading the whole sentence the
 *      register's D34-1 entry merely pointed near (§N.13.1: the defect sits beside the flagged thing).
 *   3. "what you see here is what the team sees" — technically still true (publishing stamps a date and
 *      notifies; it does NOT snapshot). Dropped anyway: on a printout the honest risk is staleness.
 *
 * ⚠️ THIS FILE ASSERTS ON THE RENDERED PDF, NOT ON THE BLADE. A fix in the file is not a fix on the page
 * (§N.21.6), and for a dompdf document the gap between the two is exactly where earlier passes lost time.
 * The text is decompressed out of the PDF's own content streams, so what is checked is what a reader sees.
 *
 * ⚠️ WHAT THIS FIX DELIBERATELY DID NOT DO: build the feature. Actually SHOWING open shifts or the
 * publication state needs an accessor on the shared `RosterWeek` builder (a PDF template must never query
 * models to work around its absence — that is how the PDF and the grid drift apart). That is **D34-1**,
 * deferred. The document now stays SILENT on both rather than DENYING them.
 */

use App\Models\OpenShift;
use App\Models\Property;
use App\Models\SchedulePublication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The words a reader actually sees, pulled from the PDF's content streams — letters only, lowercased, so the
 * assertion cannot be defeated by dompdf's per-glyph spacing or by a line break landing mid-sentence.
 * ⚠️ Prefixed — a top-level function in a Pest file is GLOBAL.
 */
function fix2RenderedPdfText(string $bytes): string
{
    $text = '';
    if (preg_match_all('/stream\r?\n(.*?)endstream/s', $bytes, $streams)) {
        foreach ($streams[1] as $stream) {
            $plain = @gzuncompress($stream);
            if ($plain === false) {
                continue;
            }
            // PDF show-text operands are parenthesised strings.
            if (preg_match_all('/\((?:\\\\.|[^()\\\\])*\)/', $plain, $shown)) {
                foreach ($shown[0] as $chunk) {
                    $text .= str_replace(['\\(', '\\)'], ['(', ')'], substr($chunk, 1, -1));
                }
            }
        }
    }

    return strtolower((string) preg_replace('/[^A-Za-z]/', '', $text));
}

it('⚠️ FIX 2 — the RENDERED schedule PDF asserts nothing the code cannot support', function () {
    $org = newDemoOrg();
    $property = Property::create(['name' => 'Alpha House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $s = ['org' => $org];
    $manager = operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');

    $response = $this->actingAs($manager)
        ->withSession(['current_property_id' => $property->id])
        ->get('/app/schedule/pdf')
        ->assertOk();

    $rendered = fix2RenderedPdfText((string) $response->getContent());

    // ⚠️ NON-VACUITY, AND IT IS LOAD-BEARING HERE. If the extraction silently returned '' every "absent"
    // assertion below would pass on nothing — the exact vacuous-guard shape §N.4p exists to catch. Anchor on
    // real content the document must always carry.
    expect(strlen($rendered))->toBeGreaterThan(500)
        ->and($rendered)->toContain('weeklyrota')
        ->and($rendered)->toContain('alphahouse');

    // ⚠️ THE TWO FALSE ASSERTIONS — gone from the page a human reads.
    expect($rendered)->not->toContain('thereisnodraftorpublishedstate')
        ->and($rendered)->not->toContain('statutoryrestperiods');

    // The soft assurance dropped with them (a printout cannot promise it stays current).
    expect($rendered)->not->toContain('whattheteamsees');

    // ⚠️ AND WHAT REPLACED THEM IS VERIFIABLY TRUE: the one thing a printed rota can always vouch for is
    // WHEN it was taken. `$generatedAt` is already computed in the property's timezone for the header.
    expect($rendered)->toContain('snapshotoftherosterasitstoodon')
        ->and($rendered)->toContain('anylaterchangewillnotappearonthisprintout')
        // The genuinely-true clause of the original sentence is kept, not thrown out with the false ones.
        ->and($rendered)->toContain('shiftswaprequests');
});

it('⚠️ FIX 2 — the source comments no longer deny features the product ships', function () {
    // The Blade's own record was false in two more places, and a stale comment is what let the printed
    // sentence survive this long. Reads the SOURCE (not a pinned constant).
    $blade = (string) file_get_contents(resource_path('views/pdfs/operator-schedule.blade.php'));

    expect($blade)->toContain('Weekly rota')                                  // non-vacuity: right file
        ->and($blade)->not->toContain('no unassigned-shift model — every shift is bound to a person')
        ->and($blade)->not->toContain('no unassigned-shift model exists')
        ->and($blade)->not->toContain('there is no publish/provisional state');

    // ⚠️ AND THE DEFERRAL IS NAMED IN THE FILE, not just in a commit message — the next reader of this
    // template needs to know the omission is now deliberate and where the work is tracked.
    expect($blade)->toContain('D34-1');
});

it('⚠️ the features the PDF stopped denying are genuinely REAL — the claims were false, not premature', function () {
    // ⚠️ CONFIRM THE PREMISE BEFORE TRUSTING THE FIX. Removing a sentence because it is "probably out of
    // date" would be guessing. These four facts are why it was false, and if any of them stops being true
    // the removal needs revisiting rather than silently standing.
    expect(class_exists(SchedulePublication::class))->toBeTrue()
        ->and(class_exists(OpenShift::class))->toBeTrue();

    $controller = (string) file_get_contents(app_path('Http/Controllers/Operator/ScheduleController.php'));
    expect($controller)->toContain('public function publishWeek')
        ->and($controller)->toContain('SchedulePublication::updateOrCreate');

    $routes = (string) file_get_contents(base_path('routes/web.php'));
    expect($routes)->toContain("'publishWeek'");

    // ⚠️ AND THE REST-PERIOD CLAIM HAD NO IMPLEMENTATION AT ALL — the reason it was the worst of the three.
    // If a real rest-period rule is ever built, this expectation is the thing that should fail and force the
    // reassurance back onto the page deliberately.
    $restLogic = 0;
    foreach (['Support/Schedule/RosterWeek.php', 'Services/ShiftRosterService.php'] as $relative) {
        $path = app_path($relative);
        if (is_file($path) && preg_match('/rest[_ ]?period|statutory/i', (string) file_get_contents($path))) {
            $restLogic++;
        }
    }
    expect($restLogic)->toBe(0, 'a rest-period rule now exists — the PDF may legitimately mention it again');
});
