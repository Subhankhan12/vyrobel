{{-- PENDING-AWARE MARKETING LINK (Gate 1). A page key resolves through the ONE MarketingUrls map; until
     Gate 2+ registers `marketing.{key}`, the item renders as a clearly-marked NON-LINK (data-vy-pending +
     aria-disabled) — never an href that 404s while looking fine. When the route lands, the link lights up
     with no template change. --}}
@props(['key', 'fragment' => ''])
@php $pending = \App\Support\Marketing\MarketingUrls::pending($key); @endphp
@if ($pending)
    <span {{ $attributes }} data-vy-pending="{{ $key }}" aria-disabled="true">{{ $slot }}</span>
@else
    <a {{ $attributes }} href="{{ \App\Support\Marketing\MarketingUrls::path($key) . $fragment }}">{{ $slot }}</a>
@endif
