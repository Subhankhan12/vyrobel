{{-- ⚠️ ADMIN 03 · F7 — the ONE way the console prints a moment: absolute, in UTC, and saying so ("24 Sep 2026, 16:05 UTC"),
     with the relative time only beside it (the hover title). Before this, twelve sites printed "3 hours ago" alone or a
     time with no zone — an admin reading a ledger or a sign-in list could not tell WHEN something happened, only how long
     ago relative to a page load they could not see. `date` prints the day only (its title still carries the time). --}}
@props(['at' => null, 'date' => false, 'empty' => '—'])
@if ($at)
    @php $utc = \Carbon\CarbonImmutable::instance($at)->utc(); @endphp
    <time {{ $attributes->merge(['class' => 'ad-time']) }} datetime="{{ $utc->toIso8601String() }}" title="{{ __('components/admin/time.title', ['time' => $utc->translatedFormat('j M Y, H:i'), 'ago' => $utc->diffForHumans()]) }}">{{ $date ? $utc->translatedFormat('j M Y') : __('components/admin/time.utc', ['time' => $utc->translatedFormat('j M Y, H:i')]) }}</time>
@else
    {{ $empty }}
@endif
