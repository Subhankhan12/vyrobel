{{-- SHARED star rating (DO10-4). The glyph was hand-written in ELEVEN files; this is the one definition.

     Owner-26 found why that matters: the owner Reviews hero repeated the SAME filled glyph for both the
     filled AND the empty half, so every rating rendered as five full stars — a 4.5 average looked like a
     perfect 5, and a 3.0 would have too. On a social-proof surface that overstates the real figure.

     RULES this component enforces for every caller:
       • filled vs empty differ by GLYPH, not only colour (WCAG 1.4.1) — a greyscale or colour-blind
         reader still sees which stars are filled.
       • the row carries ONE accessible name ("4.5 out of 5 stars") and the glyphs are hidden from
         assistive tech, so a screen reader hears the rating once instead of ten separate stars.
       • the filled count is derived from the REAL value; nothing rounds up to flatter it.

     Usage: <x-star-rating :value="$summary['average']" /> · optional :max, size, color, gap.

     DO10-4 is CLOSED (2026-08-12). Every web rating ROW now renders through this component — owner Reviews
     (O26), guest Review (G8) and owner Unit detail (the capstone, which had kept `round()` and so printed a
     4.6 as five filled stars). The other ★ occurrences in the codebase are NOT rating rows: they are
     single-glyph adornments beside a printed numeral, distribution-row labels, or the operator notification
     strings, which already build ★+☆ by glyph. Email cannot reach a Blade component, so the review-request
     mail applies the same rules in email-safe markup. --}}
@props([
    'value' => 0,
    'max' => 5,
    'size' => '16px',
    // Self-contained literals on purpose: this component renders in the owner, operator, guest, public
    // and EMAIL layouts, and a shared partial must not depend on a token only one shell defines (the
    // trap that 500'd every owner page once). A caller may still pass its own colour.
    // ⚠️ DO10-4 capstone (2026-08-12) — DO-G8-2 answered by MEASURING, and both defaults FAILED. A rating row
    // is a meaningful non-text graphic (WCAG 1.4.11 → 3:1), and the shipped pair measured 2.08 (filled) and
    // 2.25 (empty) on the plates that use it: the component everything is being migrated TO was drawing a
    // star you could barely see. Retargeted and re-measured on BOTH real plates — the email/guest card
    // #FFFDFB and the owner card #F8F4EF — keeping the hierarchy (filled reads stronger than empty):
    // ⚠️ AND THE MEASUREMENT CORRECTED THE FIRST CHOICE. A first pass picked #B26A08/#8F8073 against the two
    // CARD plates — then the live owner Unit-detail render showed the row actually sits on the PEACH hero
    // plate #FBD3BF, where the empty star measured 2.76 and failed. Re-chosen against the WORST real plate,
    // and re-measured on all three (peach #FBD3BF · owner card #F8F4EF · light card #FFFDFB):
    //     filled #8F5A06 → 4.18 / 5.28 / 5.70
    //     empty  #7A6E64 → 3.58 / 4.52 / 4.88   (always LOWER than filled, so the hierarchy survives)
    // Hues preserved (amber h37 / warm stone h27) — luminance-only, so greyscale is unaffected.
    // ⚠️ Now the TOKEN, not the literal — same measured values, one definition (§N.8f). Overridable per
    // call site as before. Not usable from an email; see the token comment.
    'color' => 'var(--vy-star)',
    'emptyColor' => 'var(--vy-star-empty)',
    'gap' => '2px',
])
@php
    $max = max(1, (int) $max);
    $value = max(0.0, min((float) $max, (float) $value));
    // NEVER ROUND UP. Without a half-star glyph, rounding 4.5 to five would print a PERFECT score for a
    // rating that is not perfect — the same overstatement this component was written to remove, just
    // smaller. Flooring can only ever understate, and the exact figure is always printed beside the row.
    $filled = (int) floor($value);
    $label = __('components/star-rating.label', ['rating' => rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.'), 'max' => $max]);
@endphp
<span role="img" aria-label="{{ $label }}" {{ $attributes->merge(['style' => 'display:inline-flex;align-items:center;gap:'.$gap.';font-size:'.$size.';line-height:1']) }}>
    @for ($i = 1; $i <= $max; $i++)
        <span aria-hidden="true" style="color:{{ $i <= $filled ? $color : $emptyColor }}">{{ $i <= $filled ? '★' : '☆' }}</span>
    @endfor
</span>
