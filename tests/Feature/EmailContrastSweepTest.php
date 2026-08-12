<?php

/*
 * THE EMAIL-CONTRAST SWEEP (DO-INV-4, 2026-08-12) — the regression guard.
 *
 * The transactional emails shipped the SAME pre-AA greys the PDF sweep had already mapped, plus the shared
 * layout's own footer grey. Re-measured in the EMAIL context (declared colours on the real declared plates —
 * NEVER getComputedStyle, which false-PASSED the owner-invite email by defaulting to white and reading
 * white-on-orange as 1.00), all three failed on EVERY email plate:
 *
 *   #8A8079  3.19–3.80   63 occurrences / 16 templates   → #5F5853  (5.78–6.88)
 *   #A69A92  2.27–2.70    7 occurrences /  3 templates   → #6C645F  (4.79–5.71)
 *   #A99E95  2.17–2.58    7 occurrences / 15 templates   → #6C645F   (one shared-layout declaration
 *                                                                     carried two footer lines on every
 *                                                                     template that extends it)
 *
 * ⚠️ THE MEASUREMENT DECIDED, NOT THE PRECEDENT. The PDF sweep's numbers are measured on white paper; the
 * email card is #FFFDFB and the wash #F6EBE2, so every occurrence was re-measured on its own plate before
 * being changed. They happened to fail everywhere — but an inset panel (#FCF7F2 / #F8F1EA / #FBF1E9 /
 * #F2E8DF) is darker than the card, so a NEW muted colour must be measured against ITS OWN plate, not
 * assumed from this file.
 *
 * EMAIL-SAFE: literal hex only. A --vy-* custom property is stripped by many clients, so the guard also
 * refuses one appearing in an email template.
 *
 * ⚠️ owner-invite.blade.php is EXEMPT from the literal scan: it never extended the shared layout, was
 * rebuilt and measured at its own gate (22 text runs, 0 failures), and names the refused prototype greys
 * inside a Blade DOCBLOCK. Blade comments do not render — {@see OwnerInviteEmailTest} guards its OUTPUT.
 */

/** Relative luminance per WCAG 2.1 — named apart from DesignTokenContrastTest's globals. */
function emailSweepLuminance(string $hex): float
{
    $hex = ltrim($hex, '#');
    $f = static function (float $v): float {
        $v /= 255;

        return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    };

    return 0.2126 * $f((float) hexdec(substr($hex, 0, 2)))
        + 0.7152 * $f((float) hexdec(substr($hex, 2, 2)))
        + 0.0722 * $f((float) hexdec(substr($hex, 4, 2)));
}

function emailSweepRatio(string $ink, string $plate): float
{
    $a = emailSweepLuminance($ink);
    $b = emailSweepLuminance($plate);

    return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
}

it('measures the sweep helper against two self-validating anchors', function () {
    // Without anchors the instrument is unvalidated (G1). These two reproduce the PDF sweep's published
    // white-paper figures exactly, which is what proves this helper agrees with that record.
    expect(round(emailSweepRatio('#8A8079', '#FFFFFF'), 2))->toBe(3.86)   // known-FAILING
        ->and(round(emailSweepRatio('#5F5853', '#FFFFFF'), 2))->toBe(6.98) // known-PASSING
        ->and(round(emailSweepRatio('#A69A92', '#FFFFFF'), 2))->toBe(2.74)
        ->and(round(emailSweepRatio('#6C645F', '#FFFFFF'), 2))->toBe(5.79);
});

it('keeps the retargeted greys above AA on every plate the email set actually uses', function () {
    // The plates declared across emails/*: the card, the outer/footer wash, and the four inset panels.
    $plates = ['#FFFDFB', '#F6EBE2', '#FCF7F2', '#F8F1EA', '#FBF1E9', '#F2E8DF'];

    foreach ($plates as $plate) {
        foreach (['#5F5853', '#6C645F'] as $ink) {
            expect(emailSweepRatio($ink, $plate))
                ->toBeGreaterThanOrEqual(4.5, "$ink on $plate is below AA for normal text");
        }
    }
});

it('ships none of the three pre-AA greys in any email template', function () {
    $emailViews = glob(resource_path('views/emails/*.blade.php')) ?: [];
    expect($emailViews)->not->toBeEmpty();

    $offenders = [];
    foreach ($emailViews as $path) {
        // See the header: owner-invite carries them only inside a Blade docblock, and its output is guarded
        // by OwnerInviteEmailTest.
        if (basename($path) === 'owner-invite.blade.php') {
            continue;
        }
        $source = (string) file_get_contents($path);
        foreach (['#8A8079', '#A69A92', '#A99E95'] as $grey) {
            if (stripos($source, $grey) !== false) {
                $offenders[] = basename($path).' → '.$grey;
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('keeps the shared layout email-safe — literal hex, never a stripped custom property', function () {
    $layout = (string) file_get_contents(resource_path('views/emails/layout.blade.php'));

    expect($layout)->toContain('#6C645F')          // both footer lines, 4.94 on the #F6EBE2 wash
        ->and($layout)->toContain('#F6EBE2')       // the wash they are measured against
        ->and($layout)->toContain('#FFFDFB')       // the card every @section('content') renders on
        ->and($layout)->not->toContain('var(--vy-');
});
