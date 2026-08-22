{{-- ⚠️ S2 — THE GUIDE / ARTICLE COUNTER, DERIVED FROM THE PUBLISHED ROUTE SET.

     ⚠️⚠️ THE DEFECT THIS EXISTS TO PREVENT. Every guide and article master ends with a counter — "Guide 3
     of 16", "Article 2 of 7" — and every guide also renders the full sixteen-item sibling sidebar. Each page
     therefore ASSERTS THE WHOLE SET EXISTS. Publishing two of seven articles would ship "Article 4 of 7" and
     "Article 2 of 7" onto a site with two articles: the same false-count shape the chrome's "sixteen written
     guides" already had, on the very pages published to fix it. That is why the publishable set was ZERO
     under every policy until this landed — not one page could go out honestly.

     ⚠️ ONE SOURCE. The count comes from MarketingUrls::publishedSeries(), which reads the route table —
     exactly what MarketingUrls::pending() reads. Nothing here is written down, so nothing here can go stale:
     publish a sixteenth guide and the other fifteen say "of 16" the same day.

     ⚠️ THE ZERO CASE, AND WHY IT RENDERS NOTHING. With no published siblings there is no true sentence to
     write. "Guide 1 of 0" is a contradiction, "Guide 1 of 1" on an unpublished page is a lie, and a bare
     "Guide" is noise. So the component emits NOTHING AT ALL — the honest empty state, the same discipline as
     an omitted og:image. `MarketingDocSeriesTest` pins this, because "renders nothing" is exactly the kind of
     behaviour that looks identical to "the component is broken".

     ⚠️ A page not in its own published series also renders nothing: it has no honest position. --}}
@props([
    'key',                    // this page's MarketingUrls key, e.g. 'guide-rates'
    'prefix' => 'guide-',     // the series it belongs to
    'label' => 'Guide',       // the noun rendered: "Guide 3 of 16"
])
@php
    $vySeriesTotal = count(\App\Support\Marketing\MarketingUrls::publishedSeries($prefix));
    $vySeriesPosition = \App\Support\Marketing\MarketingUrls::seriesPosition($key, $prefix);
@endphp
@if ($vySeriesPosition !== null && $vySeriesTotal > 0)
<span {{ $attributes->merge(['class' => 'vy-mk-seriescount']) }} data-vy-series="{{ $prefix }}">{{ $label }} {{ $vySeriesPosition }} of {{ $vySeriesTotal }}</span>
@endif
