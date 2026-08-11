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
     ⚠️ Only the owner Reviews page is wired to it so far — migrating the other ten call sites is DO10-4. --}}
@props([
    'value' => 0,
    'max' => 5,
    'size' => '16px',
    // Self-contained literals on purpose: this component renders in the owner, operator, guest, public
    // and EMAIL layouts, and a shared partial must not depend on a token only one shell defines (the
    // trap that 500'd every owner page once). A caller may still pass its own colour.
    'color' => '#E8A63A',
    'emptyColor' => '#B9A99A',
    'gap' => '2px',
])
@php
    $max = max(1, (int) $max);
    $value = max(0.0, min((float) $max, (float) $value));
    // NEVER ROUND UP. Without a half-star glyph, rounding 4.5 to five would print a PERFECT score for a
    // rating that is not perfect — the same overstatement this component was written to remove, just
    // smaller. Flooring can only ever understate, and the exact figure is always printed beside the row.
    $filled = (int) floor($value);
    $label = rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.').' out of '.$max.' stars';
@endphp
<span role="img" aria-label="{{ $label }}" {{ $attributes->merge(['style' => 'display:inline-flex;align-items:center;gap:'.$gap.';font-size:'.$size.';line-height:1']) }}>
    @for ($i = 1; $i <= $max; $i++)
        <span aria-hidden="true" style="color:{{ $i <= $filled ? $color : $emptyColor }}">{{ $i <= $filled ? '★' : '☆' }}</span>
    @endfor
</span>
