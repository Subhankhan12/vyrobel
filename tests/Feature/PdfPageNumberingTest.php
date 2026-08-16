<?php

use App\Services\PdfService;
use Illuminate\Support\Facades\View;

// DO20-1 — every PDF this product renders carries "Page N of M" in its bottom margin.
//
// ⚠️ WHY IT MATTERS: an unnumbered multi-page statement cannot be checked for completeness. Page 3 of an
// unnumbered document is indistinguishable from the end of it, so a page lost in printing or forwarding
// leaves no trace at all.
//
// ⚠️ EVERY ASSERTION BELOW READS THE RENDERED PDF BYTES, never the source. A page number is a fact only when
// it is in the file (the DO24-1 / D4-1 open-the-bytes discipline), and dompdf draws this one through a canvas
// page-script rather than markup — so nothing about it is visible in the HTML at all.
//
// ⚠️ AND THE FIXTURES FORCE A REAL OVERFLOW. A one-page document prints "Page 1 of 1" whether the paging
// logic is right or wrong; only a document that genuinely spills onto further pages can distinguish a real
// total from a hardcoded one, which is why the row counts below are tuned to 1, 3 and 4 pages.

const PPN_ROWS_ONE = 8;

const PPN_ROWS_THREE = 130;

const PPN_ROWS_FOUR = 190;

beforeEach(function (): void {
    View::addLocation(base_path('tests/Fixtures/views'));
});

/** Render the fixture at a given length and return [pageCount, [stamps…]] read out of the real bytes. */
function ppnRender(int $rows): array
{
    $raw = app(PdfService::class)->raw('paging', ['rows' => $rows]);

    expect(str_starts_with($raw, '%PDF'))->toBeTrue();

    $pages = preg_match_all('/\/Type\s*\/Page[^s]/', $raw);

    // ⚠️ dompdf compresses its content streams, so the text is unreadable until inflated — which is exactly
    // why asserting on the HTML would prove nothing about what the PDF contains.
    $stamps = [];
    if (preg_match_all('/stream\r?\n(.*?)endstream/s', $raw, $m)) {
        foreach ($m[1] as $stream) {
            $inflated = @gzuncompress($stream);
            $body = $inflated === false ? $stream : $inflated;
            if (preg_match_all('/\((Page \d+ of \d+)\)/', $body, $hits)) {
                $stamps = array_merge($stamps, $hits[1]);
            }
        }
    }

    return [$pages, $stamps];
}

// ── 1. ⚠️⚠️ A REAL MULTI-PAGE DOCUMENT — every page numbered, none skipped ────────────────────────
it('numbers every page of a multi-page pdf', function () {
    [$pages, $stamps] = ppnRender(PPN_ROWS_THREE);

    expect($pages)->toBeGreaterThanOrEqual(3);   // the fixture genuinely overflowed

    // ⚠️ EVERY page, in order, with the true total — this is what catches an off-by-one that drops the
    // first or last page's footer, the classic footer-callback bug.
    $expected = [];
    for ($i = 1; $i <= $pages; $i++) {
        $expected[] = "Page {$i} of {$pages}";
    }
    expect($stamps)->toBe($expected);
});

// ── 2. ⚠️⚠️ M IS THE REAL TOTAL — grow the document and the total follows ─────────────────────────
it('uses the real page total, not a fixed number', function () {
    [$pagesA, $stampsA] = ppnRender(PPN_ROWS_THREE);
    [$pagesB, $stampsB] = ppnRender(PPN_ROWS_FOUR);

    // ⚠️ The two documents must genuinely differ in length, or this proves nothing.
    expect($pagesB)->toBeGreaterThan($pagesA);

    // The denominator moved with the document.
    expect($stampsA[0])->toBe("Page 1 of {$pagesA}")
        ->and($stampsB[0])->toBe("Page 1 of {$pagesB}")
        ->and(end($stampsB))->toBe("Page {$pagesB} of {$pagesB}");
});

// ── 3. The trivial case is still right ────────────────────────────────────────────────────────────
it('numbers a single-page pdf as 1 of 1', function () {
    [$pages, $stamps] = ppnRender(PPN_ROWS_ONE);

    expect($pages)->toBe(1)
        ->and($stamps)->toBe(['Page 1 of 1']);
});

// ── 4. ⚠️ THE DOWNLOADER WATERMARK STILL STAMPS ALONGSIDE — one footer did not evict the other ────
it('keeps the downloader watermark beside the page number', function () {
    $raw = app(PdfService::class)->raw('paging', ['rows' => PPN_ROWS_ONE], 'Downloaded by Test · Confidential');

    $text = '';
    if (preg_match_all('/stream\r?\n(.*?)endstream/s', $raw, $m)) {
        foreach ($m[1] as $stream) {
            $inflated = @gzuncompress($stream);
            $text .= $inflated === false ? $stream : $inflated;
        }
    }

    expect($text)->toContain('Page 1 of 1')
        ->and($text)->toContain('Confidential');
});
