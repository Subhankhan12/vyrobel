{{-- The booking terms (App\Support\Booking\BookingTerms), rendered from the array — a live build OR a stored `terms_snapshot`, the same shape,
     so what a guest accepted reads exactly like what was shown. ⚠️ Every string is ESCAPED: the hotel's own text is plain text, never HTML. --}}
@props(['terms'])
<div {{ $attributes->merge(['style' => 'display:flex;flex-direction:column;gap:14px;']) }}>
    @foreach ((array) ($terms['sections'] ?? []) as $section)
        <section>
            <h3 style="margin:0 0 6px;font-size:13px;font-weight:600;color:var(--vy-ink, #2a2320);">{{ $section['title'] ?? '' }}</h3>
            <ul style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:4px;font-size:13px;line-height:1.5;color:var(--vy-muted, #6b625c);">
                @foreach ((array) ($section['lines'] ?? []) as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </section>
    @endforeach
    @if (is_string($terms['extra'] ?? null) && $terms['extra'] !== '')
        <section>
            <h3 style="margin:0 0 6px;font-size:13px;font-weight:600;color:var(--vy-ink, #2a2320);">{{ __('components/booking/terms.own_terms') }}</h3>
            <p style="margin:0;font-size:13px;line-height:1.5;color:var(--vy-muted, #6b625c);white-space:pre-line;overflow-wrap:anywhere;">{{ $terms['extra'] }}</p>
        </section>
    @endif
</div>
