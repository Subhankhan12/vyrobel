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

/*
 * DO-EMAIL-1 (2026-08-12) — the six literals DO-INV-4 surfaced and deliberately left. Each was re-measured on
 * its OWN plate before being touched; all six failed, so all six were mapped:
 *
 *   #8A7A6A  3.72 on #FBF1E9  → #5F5853  6.27   (closes DO-G4-2 — the pre-arrival grey)
 *   #B26A08  3.80 on #FBF1E9  → #7E5811  5.73   (the RAW --vy-amber fill used as TEXT → the guest realm's
 *                                                --vy-amber-text VALUE, inlined; email cannot read a token)
 *   #9A8C7F  3.22 / 2.78      → #5F5853  6.88 / 5.95
 *   #9C9089  3.06 on #FFFDFB  → #5F5853  6.88
 *   #64748B  3.62 on #111B2E  → #94A3B8  6.71   ⚠️ LIGHT-ON-DARK: this one had to go LIGHTER, not darker,
 *                                                and #94A3B8 was already the body copy in that same email.
 *   the CTA gradient — ⚠️ NOT the literal the register named. DO-INV-4 recorded "#E8EDF6 on #818CF8", but the
 *   button declares `color:#fff`, which the first probe could not read (6-digit-hex regex) so it inherited the
 *   parent's #E8EDF6. The real run is WHITE on the indigo gradient, and a gradient must be PIXEL-SAMPLED:
 *   the lightest rendered pixel was #7E89F7 → white 3.08 FAIL. Stops darkened to #5B5FE3→#4338CA; re-sampled
 *   lightest pixel #595CE1 → white 5.19 PASS. The gradient is preserved; the eyebrow (#818CF8) and mark
 *   (#6366F1) are untouched because they pass on the dark card.
 *
 * ⚠️ #E8A317 (the review-request ★, 2.14) is EXCLUDED on purpose: it is a COLOUR-ONLY rating signal, so
 * darkening the hex would not fix it. It belongs to DO10-4 (glyph-differentiated stars), not to a contrast pass.
 */
it('ships none of the six DO-EMAIL-1 literals in any email template', function () {
    $emailViews = glob(resource_path('views/emails/*.blade.php')) ?: [];
    expect($emailViews)->not->toBeEmpty();

    $offenders = [];
    foreach ($emailViews as $path) {
        if (basename($path) === 'owner-invite.blade.php') {
            continue;   // Blade docblock only — see above
        }
        $source = (string) file_get_contents($path);
        foreach (['#8A7A6A', '#B26A08', '#9A8C7F', '#9C9089'] as $literal) {
            if (stripos($source, $literal) !== false) {
                $offenders[] = basename($path).' → '.$literal;
            }
        }
        // #64748B is only ever a TEXT colour here; the bare hex may not reappear as one.
        if (stripos($source, 'color:#64748B') !== false) {
            $offenders[] = basename($path).' → color:#64748B';
        }
        // The CTA gradient must stay on the darkened stops — its lightest rendered pixel carries white text.
        if (stripos($source, 'linear-gradient(135deg,#818CF8,#6366F1)') !== false) {
            $offenders[] = basename($path).' → the pre-AA CTA gradient';
        }
    }

    expect($offenders)->toBe([]);
});

it('keeps the DO-EMAIL-1 replacements above AA on the plates they actually land on', function () {
    // Warm greys on the card / wash / the door-code + balance panel.
    foreach (['#FFFDFB', '#F6EBE2', '#FBF1E9'] as $plate) {
        expect(emailSweepRatio('#5F5853', $plate))->toBeGreaterThanOrEqual(4.5)
            ->and(emailSweepRatio('#7E5811', $plate))->toBeGreaterThanOrEqual(4.5);
    }

    // ⚠️ Light-on-dark: the platform-admin email's card. Darkening would have made this WORSE.
    expect(emailSweepRatio('#94A3B8', '#111B2E'))->toBeGreaterThanOrEqual(4.5);

    // The CTA gradient's LIGHTEST declared stop is the worst case for the white label. The rendered
    // lightest pixel was pixel-sampled at #595CE1 (5.19); the declared stop bounds it.
    expect(emailSweepRatio('#FFFFFF', '#5B5FE3'))->toBeGreaterThanOrEqual(4.5)
        ->and(emailSweepRatio('#FFFFFF', '#4338CA'))->toBeGreaterThanOrEqual(4.5);

    // And the value it replaced genuinely failed — so this guard is not vacuous.
    expect(emailSweepRatio('#FFFFFF', '#818CF8'))->toBeLessThan(4.5);
});

it('keeps the shared layout email-safe — literal hex, never a stripped custom property', function () {
    $layout = (string) file_get_contents(resource_path('views/emails/layout.blade.php'));

    expect($layout)->toContain('#6C645F')          // both footer lines, 4.94 on the #F6EBE2 wash
        ->and($layout)->toContain('#F6EBE2')       // the wash they are measured against
        ->and($layout)->toContain('#FFFDFB')       // the card every @section('content') renders on
        ->and($layout)->not->toContain('var(--vy-');
});
