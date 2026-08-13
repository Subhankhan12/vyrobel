<?php

declare(strict_types=1);

/**
 * THE COMPOSITED-PLATE CONTRAST SAMPLER (DO-PROBE-1, 2026-08-13).
 *
 * ⚠️ WHY THIS EXISTS. Three DOM-based probes failed in one pass (`2bb4b54`), all for the same root reason:
 * `getComputedStyle` reads DECLARED values, and a gradient is a RENDERED fact. This product paints gradients on
 * the body, on cards and on buttons, so no ancestor walk can say which element's gradient a given pixel sits on.
 *   1. a naive walk fell through to white → 13 FALSE failures at 1.00;
 *   2. bailing out on gradients went honest-but-BLIND → 166 unmeasurable, "0 failures";
 *   3. an analytic gradient evaluation passed its anchors and STILL scored a white button label 1.2 on a cream
 *      plate — it resolved a card's gradient, not the button's own.
 *
 * ⚠️ THE LESSON THIS ENCODES: an instrument that produces PLAUSIBLE NUMBERS is more dangerous than one that
 * crashes, and anchor-validation proves only that a tool can measure SOMETHING — never that it measured the
 * RIGHT PLATE. So this tool reads the actual painted pixels, and refuses rather than guesses.
 *
 * INPUT   argv[1] screenshot PNG (full page, 1 image px == 1 CSS px)
 *         argv[2] JSON: {docWidth, docHeight, candidates:[{t,css,fg:[r,g,b],x,y,w,h,need}]}
 * OUTPUT  JSON: {measured, failed, unmeasurable, failures:[…], unmeasurableReasons:{…}}
 *
 * ⚠️ EVERY RESULT CARRIES ITS COUNTS. "0 failures" is meaningless without "N measured" beside it — a
 * 0-failures/0-measured is exactly attempt 2's blind zero, and this output shape makes it impossible to
 * report one without exposing the other.
 */
const GLYPH_TOLERANCE = 60;    // RGB distance within which a pixel is treated as glyph ink or its AA halo
const MIN_PLATE_RATIO = 0.30;  // at least this share of sampled pixels must be plate, else UNMEASURABLE

/**
 * ⚠️ THE PERCENTILE, AND WHY IT IS NOT A FUDGE. "Take the worst pixel" is right for a GRADIENT (the plate is
 * positional) and catastrophic next to ANTI-ALIASING: the halo around every glyph is a blend of ink and plate,
 * so the single darkest pixel in a text element is almost always halo, not background. Validation caught
 * exactly that — a white label on the accent gradient scored 1.6 on "peach", and dark ink on the body gradient
 * scored 2.15 on "brown"; both were halo pixels that survived the ink filter and then won the minimum.
 *
 * So the worst is taken at the 10th percentile of the plate distribution: still the WORST END (a gradient's
 * dark corner is thousands of pixels and sits well inside p10), but immune to the thin halo fringe, which is
 * only a few percent of any sample.
 */
const WORST_PERCENTILE = 0.10;

function luminance(array $c): float
{
    $s = array_map(static function ($v) {
        $v /= 255;

        return $v <= 0.03928 ? $v / 12.92 : (float) pow(($v + 0.055) / 1.055, 2.4);
    }, $c);

    return 0.2126 * $s[0] + 0.7152 * $s[1] + 0.0722 * $s[2];
}

function contrast(array $a, array $b): float
{
    $x = luminance($a);
    $y = luminance($b);
    if ($x < $y) {
        [$x, $y] = [$y, $x];
    }

    return round(($x + 0.05) / ($y + 0.05), 2);
}

/** Squared RGB distance — cheap, and only used to separate ink from plate. */
function dist2(array $a, array $b): int
{
    return ($a[0] - $b[0]) ** 2 + ($a[1] - $b[1]) ** 2 + ($a[2] - $b[2]) ** 2;
}

$png = $argv[1] ?? '';
$meta = json_decode((string) file_get_contents($argv[2] ?? ''), true);

if (! is_file($png) || ! is_array($meta)) {
    fwrite(STDERR, "usage: contrast-sampler.php <screenshot.png> <candidates.json>\n");
    exit(2);
}

$img = @imagecreatefrompng($png);
if ($img === false) {
    fwrite(STDERR, "cannot read PNG\n");
    exit(2);
}

$imgW = imagesx($img);
$imgH = imagesy($img);

// ⚠️ THE COORDINATE CONTRACT, CHECKED RATHER THAN ASSUMED. Rects come from the DOM in CSS pixels; the
// screenshot must be 1 image px per CSS px or every sample lands somewhere else. A silent mismatch would
// produce perfectly plausible numbers from the wrong pixels — the failure this tool exists to prevent.
if (abs($imgW - (int) $meta['docWidth']) > 2) {
    fwrite(STDERR, "DPR/scale mismatch: image {$imgW}px vs document {$meta['docWidth']}px — refusing to sample\n");
    exit(3);
}

$measured = 0;
$ratios = [];
$failures = [];
$unmeasurable = [];

foreach ($meta['candidates'] as $c) {
    $x0 = (int) round($c['x']);
    $y0 = (int) round($c['y']);
    $w = (int) round($c['w']);
    $h = (int) round($c['h']);
    $label = $c['t'];

    if ($w < 3 || $h < 3) {
        $unmeasurable[] = [$label, 'zero-area'];

        continue;
    }
    if ($x0 < 0 || $y0 < 0 || $x0 + $w > $imgW || $y0 + $h > $imgH) {
        // ⚠️ An out-of-bounds read returns BLACK and would score as a dramatic, entirely fictional failure.
        $unmeasurable[] = [$label, 'outside-screenshot'];

        continue;
    }

    // Sample a grid across the element's OWN box. The plate is whatever is painted behind this element's text,
    // so the box is the correct sampling region: a button's box is the button (its own gradient), not the card
    // beneath it. That is precisely what attempt 3 got wrong.
    $plate = [];
    $ink = 0;
    $total = 0;
    $stepX = max(1, intdiv($w, 24));
    $stepY = max(1, intdiv($h, 8));

    for ($y = $y0 + 1; $y < $y0 + $h - 1; $y += $stepY) {
        for ($x = $x0 + 1; $x < $x0 + $w - 1; $x += $stepX) {
            $rgb = imagecolorat($img, $x, $y);
            $p = [($rgb >> 16) & 255, ($rgb >> 8) & 255, $rgb & 255];
            $total++;

            // Glyph ink and its anti-aliased halo sit near the text colour; sampling those would report the
            // text against itself (~1.0) and manufacture a failure on perfectly legible copy.
            if (dist2($p, $c['fg']) < GLYPH_TOLERANCE ** 2) {
                $ink++;

                continue;
            }
            $plate[] = $p;
        }
    }

    if ($total === 0 || count($plate) / $total < MIN_PLATE_RATIO) {
        // Mostly ink, or an icon/graphic filling its box: the plate cannot be isolated with confidence.
        $unmeasurable[] = [$label, 'plate-not-isolable'];

        continue;
    }

    // ⚠️ THE WORST POSITION, taken at a percentile rather than the extreme (see WORST_PERCENTILE). A
    // translucent element over a gradient has no single plate — its contrast varies across its own width —
    // so the worst end is the honest summary (DO-EMAIL-7's positional rule); the percentile is what keeps
    // that from degenerating into "the darkest anti-alias pixel".
    $scored = [];
    foreach ($plate as $p) {
        $scored[] = [contrast($c['fg'], $p), $p];
    }
    usort($scored, static fn ($a, $b) => $a[0] <=> $b[0]);
    $worst = $scored[(int) floor(count($scored) * WORST_PERCENTILE)];

    $measured++;
    $ratios[$label] = $worst[0];

    if ($worst[0] < $c['need']) {
        $failures[] = [
            'text' => $label,
            'colour' => $c['css'],
            'plate' => 'rgb('.implode(',', $worst[1]).')',
            'ratio' => $worst[0],
            'need' => $c['need'],
        ];
    }
}

$reasons = [];
foreach ($unmeasurable as [$l, $why]) {
    $reasons[$why] = ($reasons[$why] ?? 0) + 1;
}

echo json_encode([
    'measured' => $measured,
    'failed' => count($failures),
    'unmeasurable' => count($unmeasurable),
    'unmeasurableReasons' => $reasons,
    'failures' => array_slice($failures, 0, 25),
    // Every measured ratio, so a validation run can check the ANCHORS by value rather than by their absence
    // from the failure list (§N.18.1 — the check independent of the tool).
    'ratios' => in_array('--report-all', $argv, true) ? $ratios : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
