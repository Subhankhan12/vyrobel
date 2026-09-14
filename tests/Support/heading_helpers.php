<?php

/**
 * THE ONE HEADING-OUTLINE RULE (§N.8f) — shared by the marketing guard and the app-realm guard.
 *
 * ⚠️ WHY IT IS SHARED. The marketing site got this rule first (a page with no `<h1>`, one with two, one
 * that skipped a level). The guest realm then turned out to have the same class — twelve signed-in screens
 * with NO heading element at all — and the storefront checkout's empty branch with none either. Two copies
 * of one rule is how they drift; the second realm did not get a second implementation.
 *
 * ⚠️⚠️ WHAT THIS PROVES, AND WHAT IT CANNOT. It proves a heading EXISTS at the right level and that the
 * outline is unbroken. It CANNOT prove the heading says the right thing — a page titled "Untitled" passes.
 * Reading the text is a human's job and is recorded per screen in `docs/GUEST-HEADINGS.md`.
 *
 * @return list<string> one entry per problem, empty when the outline is sound
 */
function headingOutlineOffenders(string $label, string $html): array
{
    // ⚠️ STYLE, SCRIPT AND COMMENTS OUT FIRST. A `<h1` written inside a Blade comment is not a heading, and
    //    this pass proved the point on itself: the comment explaining one of the promotions contains the
    //    literal `<h1`, and an unstripped count reported 3 where the truth was 2.
    $clean = (string) preg_replace([
        '/<style\b[^>]*>.*?<\/style>/is',
        '/<script\b[^>]*>.*?<\/script>/is',
        '/<!--.*?-->/s',
        '/\{\{--.*?--\}\}/s',
    ], '', $html);

    preg_match_all('/<h([1-6])\b/', $clean, $m);
    $levels = array_map('intval', $m[1]);

    $offenders = [];

    $h1 = count(array_filter($levels, fn (int $l): bool => $l === 1));
    if ($h1 !== 1) {
        $offenders[] = "{$label}: {$h1} h1 elements";
    }

    for ($i = 1; $i < count($levels); $i++) {
        if ($levels[$i] - $levels[$i - 1] > 1) {
            $offenders[] = "{$label}: skips h{$levels[$i - 1]}→h{$levels[$i]}";
            break;
        }
    }

    return $offenders;
}
