<?php

/*
 * DO-EMAIL-4 — `--vy-amber-text` IS ONE NAME WITH ONE VALUE (2026-08-12).
 *
 * The token was declared twice with different values — guest `rgb(126,88,17)` and owner `rgb(128,76,5)` — by two
 * independent gates (the guest Reservation pass and the Owner Statement pass) that each derived an amber that
 * read on their own plates. Neither knew about the other. `var(--vy-amber-text)` therefore resolved to whichever
 * shell was in scope, a shared component would have picked up the wrong one, and "the amber-text value" became
 * ambiguous enough to infect DO-EMAIL-1's record.
 *
 * ⚠️ WHAT THIS FILE DOES **NOT** CLAIM. It is NOT a rule that a token must be identical across shells. Seven
 * others legitimately differ: the guest realm uses smaller radii and a lighter shadow, `--vy-glass-border` is
 * warm-dark on guest and white on owner/operator, and `--vy-on-grad` was measured SEPARATELY per shell on
 * purpose (§N.4i / §N.4j). Per-shell theming is the design system working. What was wrong here is narrower and
 * specific: **this** token carries one intent — amber text on a light warm plate — and had no reason to differ.
 * A blanket "all tokens must agree" guard would be false against the design, so this one is scoped to the token.
 */

/** Every shell that declares the palette. */
function amberShells(): array
{
    return [
        // ⚠️ G8 — AMENDED BY DO-TOKEN-1. This guard was born when `--vy-amber-text` had TWO values under one
        // name, and it asserted the token was declared in ≥2 shells with ONE value. The refactor made that
        // structurally impossible: the token is now declared ONCE, in the canonical base, and no shell holds a
        // copy. The invariant is unchanged and STRONGER — one value everywhere — so the base is simply added
        // to the sources this reads. (Its own non-vacuity message fired when the token vanished from the
        // shells, which is the guard working, not failing.)
        'base' => resource_path('views/components/design/tokens.blade.php'),
        'guest' => resource_path('views/components/guest/shell.blade.php'),
        'owner' => resource_path('views/components/layouts/owner.blade.php'),
        'operator' => resource_path('views/components/layouts/operator.blade.php'),
    ];
}

/** Relative luminance per WCAG 2.1. */
function amberLuminance(string $hex): float
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

function amberRatio(string $ink, string $plate): float
{
    $a = amberLuminance($ink);
    $b = amberLuminance($plate);

    return round((max($a, $b) + 0.05) / (min($a, $b) + 0.05), 2);
}

const VY_AMBER_TEXT = '#704204';   // rgb(112, 66, 4) — the canonical value
// ⚠️ MOVED in the Part-B data sweep: the old value measured 4.48 on the owner Expenses "Pending review"
// chip, a tint visible only once expenses are POPULATED. Darkening the token alone would have left the
// email and PDF literals behind — recreating the exact two-values-one-name drift this file exists to
// catch — so the canonical value moved everywhere at once. It only IMPROVES the surfaces it already
// served (email 6.39 → 7.64, PDF pill 6.24 → 7.47).

it('⚠️ declares --vy-amber-text with ONE value everywhere it is declared', function () {
    $values = [];

    foreach (amberShells() as $realm => $path) {
        $source = (string) file_get_contents($path);
        preg_match_all('/--vy-amber-text\s*:\s*([^;]+);/', $source, $m);
        foreach ($m[1] as $raw) {
            $values[$realm][] = preg_replace('/\s+/', '', $raw);
        }
    }

    // ⚠️ G8 — DO-TOKEN-1 STRENGTHENED THIS. It used to require the token in ≥2 shells with one value, because
    // the only way to catch drift was to compare copies. There are no copies now: it is declared EXACTLY ONCE,
    // in the canonical base, and a shell cannot hold a second. "One value everywhere" is no longer something
    // to check — it is a property of the structure — so the assertion becomes the stronger "exactly one".
    $flat = array_merge(...array_values($values ?: [[]]));
    expect(count($flat))->toBe(1, 'the token is no longer declared — this guard would pass on nothing');

    // ⚠️ THE LANDMINE: one name, one value. A second, differing declaration must fail here.
    expect(array_unique($flat))->toHaveCount(1, 'the amber-text token is declared with more than one value: '.implode(' | ', array_unique($flat)));

    // …and it is the measured value, so a silent re-tune of one shell also fails. Moved to rgb(112,66,4) in
    // the Part-B data sweep (the old value read 4.48 on a populated-only chip); see the VY_AMBER_TEXT note.
    expect($flat[0])->toBe('rgb(112,66,4)');
});

it('clears AA on every plate it is rendered on, in BOTH realms — the measurement that chose it', function (string $plate, string $where) {
    // Pixel-sampled from the real renders (two self-validating anchors, both reading 5.18 — §N.8e). The rejected
    // value #7E5811 scored 4.65 on the worst of these; the canonical one scores 5.18 there, so unifying moved
    // every site in the safer direction.
    expect(amberRatio(VY_AMBER_TEXT, $plate))->toBeGreaterThanOrEqual(4.5, "amber text fails AA on {$where} ({$plate})");
})->with([
    'guest pending badge' => ['#F9EAD5', 'the guest Balance-due badge'],
    'guest card' => ['#FEFBF8', 'the guest payments card'],
    'owner card' => ['#FEFCFB', 'the owner Team card'],
    'owner peach invite row' => ['#FAD5BA', 'the owner pending-invite row — the WORST plate measured'],
    'owner tinted row' => ['#FDF5EE', 'the owner Resend row'],
    'email card' => ['#FFFDFB', 'the email card (literal, not the token)'],
    'email wash' => ['#F6EBE2', 'the email footer wash'],
    'email inset panel' => ['#F2E8DF', 'the darkest email inset panel'],
]);

it('⚠️ keeps the email + PDF literals tracking the canonical value — "the amber-text value" means one thing', function () {
    // Emails and PDFs cannot read a custom property, so they carry a literal. That literal must still be THE
    // value, or the ambiguity this item exists to remove just moves somewhere else.
    foreach (['pre-arrival', 'payment-reminder'] as $template) {
        $source = (string) file_get_contents(resource_path("views/emails/{$template}.blade.php"));
        expect($source)->toContain(VY_AMBER_TEXT)
            ->and($source)->not->toContain('#7E5811');   // the superseded guest-only value
    }

    // The owner remittance PDF already used the canonical value — a happy confirmation, not a change.
    expect((string) file_get_contents(resource_path('views/pdfs/owner-remittance.blade.php')))
        ->toContain(VY_AMBER_TEXT);

    // And the superseded value is gone from every view.
    $hits = [];
    foreach (glob(resource_path('views/**/*.blade.php')) ?: [] as $f) {
        if (str_contains((string) file_get_contents($f), '#7E5811')) {
            $hits[] = basename($f);
        }
    }
    expect($hits)->toBe([]);
});
