{{-- Public booking terms of ONE property (his ruling 2026-11-14, option B). READ-ONLY, server-rendered. The text is built by
     App\Support\Booking\BookingTerms from what the property actually set — the SAME builder the checkout shows and the booking
     stores. The hotel's own words are escaped by the component (plain text, never HTML). --}}
<x-public.shell :property="$property" :title="__('booking/terms.heading')" :seo="$seo">
    <x-slot:head>
        <style>
            main { max-width: 760px; }
            .vy-tp-card { background: rgba(255, 252, 248, 0.6); backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); border-radius: 20px; box-shadow: var(--vy-shadow); padding: 26px 28px; }
            .vy-tp-h1 { margin: 0 0 10px; font-family: var(--vy-display); font-weight: 600; font-size: 30px; letter-spacing: -0.02em; }
            .vy-tp-p { margin: 0 0 20px; font-size: 14px; line-height: 1.55; color: var(--vy-ink); }
            .vy-tp-links { margin-top: 22px; display: flex; flex-wrap: wrap; gap: 8px 22px; font-size: 13px; }
            .vy-tp-links a { color: var(--vy-accent-text); text-decoration: none; }
            .vy-tp-ver { margin-top: 18px; font-size: 11.5px; color: var(--vy-faint); }
        </style>
    </x-slot:head>

    <div class="vy-tp-card">
        <h1 class="vy-tp-h1">{{ __('booking/terms.heading') }}</h1>
        <p class="vy-tp-p">{{ __('booking/terms.intro', ['property' => $property['name']]) }}</p>
        <x-booking.terms :terms="$terms" />
        <div class="vy-tp-ver">{{ __('booking/terms.version', ['version' => $terms['version']]) }}</div>
        <div class="vy-tp-links">
            <a href="{{ $privacyUrl }}">{{ __('booking/terms.privacy_link') }}</a>
            <a href="{{ route('booking.browse', $property['slug']) }}">{{ __('booking/terms.back', ['property' => $property['name']]) }}</a>
        </div>
    </div>
</x-public.shell>
