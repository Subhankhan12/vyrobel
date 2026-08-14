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
 * ⚠️ HALO REJECTION IS ADAPTIVE, NOT A FIXED TOLERANCE — and this was learned the hard way TWICE.
 *
 * Anti-aliased pixels lie ON THE SEGMENT between the ink colour and the plate colour. A fixed RGB tolerance
 * cannot separate them, because how far a halo pixel sits from the ink depends entirely on the ink/plate
 * distance for that particular element. The first version used a fixed tolerance plus a 10th-percentile worst
 * and validated cleanly — on a fixture whose text was SPARSE inside generously padded blocks, where halo was a
 * tiny share of the samples. Pointed at a real page of dense 12px labels it reported 74 "failures" on plates
 * like rgb(136,129,127) — a perfect ink/white midpoint, i.e. halo, wearing the costume of a background.
 *
 * ⚠️ THE FIXTURE WAS NOT REPRESENTATIVE OF THE INPUT, which is its own lesson: a validated instrument is
 * validated *for the cases it was validated on*. So the rejection is now RELATIVE — a pixel is halo if it lies
 * more than a fraction of the way from the modal plate toward the ink — which adapts to every element.
 */
const HALO_FRACTION = 0.45;   // closer to the ink than this share of the ink→plate distance ⇒ halo, not plate

/**
 * ⚠️ THE THIRD CORRECTION — SAMPLE BESIDE THE GLYPHS, NOT AMONG THEM (DO-PROBE-2).
 *
 * Rejecting ink and its halo pixel-by-pixel is not enough in a DENSE SMALL-TEXT box, because there the halo is
 * the MAJORITY of what is left: at 12px the glyph fringe touches nearly every background pixel between the
 * letterforms. Measured on a real page, the box interior gave median 4.35 while the true plate immediately
 * BESIDE the same text was 4.50+ — and a plate cannot be darker where the text is than where it is not. Only
 * ink-blend darkens at the glyphs, so the interior median being lower was proof the samples were still halo.
 * That mismeasurement had already been written up as a product defect before it was caught (DO-PLATE-1).
 *
 * So the plate is read from CLEAR COLUMNS — columns crossing the glyph band containing no ink-adjacent pixel
 * at all (leading/trailing space, the gaps between words, the element's own padding). Those are background at
 * the SAME vertical position as the text, so the gradient value matches what is behind it.
 */
const MIN_CLEAR_COLUMNS = 6;   // fewer clear columns than this ⇒ the plate cannot be isolated ⇒ UNMEASURABLE
const EDGE_INSET = 2;          // skip the element's own border/rounding

/**
 * ⚠️ THE CLEAR-COLUMN SCAN AND THE PERCENTILE ARE COMPLEMENTARY, NOT ALTERNATIVES — learned by removing one.
 *
 * With halo excluded by construction it looked safe to take the true minimum. It was not: a padded block's own
 * top and bottom rows blend into whatever is outside it, so the darkest "clear" pixel was the element's EDGE.
 * The accent anchor read 2.08 on rgb(226,166,141) — an accent→cream boundary blend, not a plate.
 *
 * Columns remove the HALO; the percentile removes the EDGE. Both are needed, and the vertical inset keeps the
 * scan away from the block's own boundary in the first place.
 */
const EDGE_PERCENTILE = 0.05;   // ignore the darkest 5% of clear-column pixels — element edges, shadows, bleed
const VERTICAL_INSET_RATIO = 0.15;

/**
 * ⚠️ A COLUMN IS CLEAR IF ITS PIXELS ARE THE PLATE — not merely if they are FAR FROM THE INK.
 *
 * The first attempt reused the halo cut for the column test, which is far too permissive: a halo pixel 70% of
 * the way toward the background passes "not ink" easily, so a column full of light fringe still counted as
 * clear and the reading came back 4.07 against a KNOWN 4.50. Defining clear positively — every pixel close to
 * the modal plate — is what actually isolates background.
 *
 * The tolerance scales with the ink→plate distance so a gradient's genuine variation across a small element
 * still qualifies, while the ink→plate blend never does.
 */
const PLATE_TOLERANCE_FLOOR = 28;
const PLATE_TOLERANCE_RATIO = 0.20;

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

/*
 * ⚠️ GAP A (DO-PROBE-3) — WHAT AN ANCESTOR'S OPACITY ACTUALLY PAINTS.
 *
 * CSS opacity composites the whole subtree against what is behind it, so an element under `opacity: .55` paints
 * its text at 55% over the plate — NOT at its declared colour. Measuring the declared colour there is
 * optimistic, and it produced the worst false clean bill this instrument has had: DO-A11Y-1's locked nav item
 * DECLARED a colour measuring 9.40 while PAINTING at 2.89.
 *
 * The effective alpha is the PRODUCT down the chain (the collector supplies it). The painted colour is then the
 * ordinary source-over composite, per channel: out = alpha*text + (1-alpha)*plate.
 *
 * ⚠️ An alpha at or near zero is INVISIBLE, not low-contrast — it stays unmeasurable rather than returning 1.0.
 */
function compositeAt(array $text, array $plate, float $alpha): array
{
    return [
        (int) round($alpha * $text[0] + (1 - $alpha) * $plate[0]),
        (int) round($alpha * $text[1] + (1 - $alpha) * $plate[1]),
        (int) round($alpha * $text[2] + (1 - $alpha) * $plate[2]),
    ];
}

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

    // ⚠️ CLEAR-COLUMN SCAN. Walk the element's own box column by column across the glyph band; a column counts
    // as CLEAR only if EVERY pixel in it sits farther from the ink than the halo threshold. Those columns are
    // plate — beside the letterforms, at the text's own gradient position.
    $legacyInterior = in_array('--legacy-interior', $argv, true);

    $x1 = $x0 + EDGE_INSET;
    $x2 = $x0 + $w - EDGE_INSET;
    // ⚠️ Inset vertically in PROPORTION to the box: a padded block's outermost rows are its boundary with
    // whatever sits behind it, and sampling those reads a blend rather than the plate.
    $vInset = max(EDGE_INSET, (int) round($h * VERTICAL_INSET_RATIO));
    $y1 = $y0 + $vInset;
    $y2 = $y0 + $h - $vInset;

    if ($x2 - $x1 < 4 || $y2 - $y1 < 3) {
        $unmeasurable[] = [$label, 'zero-area'];

        continue;
    }

    // A first pass finds the modal background, which is what the halo cut is measured against.
    $rough = [];
    for ($y = $y1; $y < $y2; $y++) {
        for ($x = $x1; $x < $x2; $x += 2) {
            $rgb = imagecolorat($img, $x, $y);
            $rough[] = [($rgb >> 16) & 255, ($rgb >> 8) & 255, $rgb & 255];
        }
    }
    if ($rough === []) {
        $unmeasurable[] = [$label, 'zero-area'];

        continue;
    }
    $buckets = [];
    foreach ($rough as $p) {
        $k = (intdiv($p[0], 16) << 8) | (intdiv($p[1], 16) << 4) | intdiv($p[2], 16);
        $buckets[$k][] = $p;
    }
    uasort($buckets, static fn ($a, $b) => count($b) <=> count($a));
    $modal = reset($buckets)[0];
    $inkToPlate = sqrt(dist2($c['fg'], $modal));
    $cut = ($inkToPlate * HALO_FRACTION) ** 2;                                    // legacy path only
    $plateTol2 = max(PLATE_TOLERANCE_FLOOR, $inkToPlate * PLATE_TOLERANCE_RATIO) ** 2;

    // ⚠️ GAP B RECOVERY (i) (DO-PROBE-3): a SOLID background IS the plate, exactly. No column hunting, no
    // anti-alias fringe — and, critically, it works when text fills the element EDGE TO EDGE, which is the
    // whole no-clear-band class. Short-circuits the scan below, so the `continue` that refuses dense text is
    // never reached for these. Only trusted when the collector saw no gradient or image anywhere up the chain.
    if (! empty($c['solidBg'])) {
        $solid = array_map('intval', $c['solidBg']);
        $alphaS = isset($c['alpha']) ? (float) $c['alpha'] : 1.0;

        if ($alphaS < 0.05) {
            $unmeasurable[] = [$label, 'invisible-zero-opacity'];

            continue;
        }

        $paintedS = $alphaS >= 1.0 ? $c['fg'] : compositeAt($c['fg'], $solid, $alphaS);
        $ratioS = contrast($paintedS, $solid);

        $measured++;
        $ratios[$label] = $ratioS;

        if ($ratioS < $c['need']) {
            $failures[] = [
                'text' => $label,
                'colour' => $c['css'],
                'plate' => 'rgb('.implode(',', $solid).')',
                'ratio' => $ratioS,
                'need' => $c['need'],
            ];
        }

        continue;
    }

    $plate = [];
    $clearColumns = 0;
    for ($x = $x1; $x < $x2; $x++) {
        $column = [];
        $clean = true;
        for ($y = $y1; $y < $y2; $y++) {
            $rgb = imagecolorat($img, $x, $y);
            $p = [($rgb >> 16) & 255, ($rgb >> 8) & 255, $rgb & 255];
            if (dist2($p, $modal) > $plateTol2) {   // not plate ⇒ ink, halo or something else ⇒ not clear
                $clean = false;
                break;
            }
            $column[] = $p;
        }
        if ($clean && $column !== []) {
            $clearColumns++;
            foreach ($column as $p) {
                $plate[] = $p;
            }
        }
    }

    if ($legacyInterior) {
        // ⚠️ THE OLD, WRONG PATH — kept ONLY so the harness can prove it would have missed the true plate.
        // Never used for a real measurement.
        $plate = array_values(array_filter($rough, static fn ($p) => dist2($p, $c['fg']) >= $cut));
        $clearColumns = MIN_CLEAR_COLUMNS;
    }

    if ($clearColumns < MIN_CLEAR_COLUMNS || $plate === []) {
        // ⚠️ NO FALLBACK TO THE INTERIOR — that is precisely what cannot be trusted here. A box whose text runs
        // edge to edge is reported unmeasurable rather than measured badly.
        $unmeasurable[] = [$label, 'no-clear-band'];

        continue;
    }

    // ⚠️ GAP A's floor: a subtree at (near) zero opacity is INVISIBLE. Compositing would return ~1.0, which
    // reads as a catastrophic contrast failure rather than "there is nothing on screen". Refuse instead.
    if (isset($c['alpha']) && (float) $c['alpha'] < 0.05) {
        $unmeasurable[] = [$label, 'invisible-zero-opacity'];

        continue;
    }

    // ⚠️ THE WORST CLEAR COLUMN. These pixels are genuine background, so the true minimum is the honest worst
    // position (DO-EMAIL-7) — halo is now excluded by CONSTRUCTION rather than by statistics, so no percentile
    // is needed. The percentile survives only for the legacy path.
    // ⚠️ GAP A: measure what is PAINTED, not what is declared. `alpha` is the product of the element's own
    // opacity and every ancestor's (supplied by the collector); 1.0 for the ordinary case, so this is a no-op
    // everywhere except the dimmed subtrees the probe previously refused to measure at all.
    $alpha = isset($c['alpha']) ? (float) $c['alpha'] : 1.0;

    $scored = [];
    foreach ($plate as $p) {
        $painted = $alpha >= 1.0 ? $c['fg'] : compositeAt($c['fg'], $p, $alpha);
        $scored[] = [contrast($painted, $p), $p];
    }
    usort($scored, static fn ($a, $b) => $a[0] <=> $b[0]);
    $worst = $legacyInterior
        ? $scored[(int) floor(count($scored) * WORST_PERCENTILE)]
        : $scored[(int) floor(count($scored) * EDGE_PERCENTILE)];

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
