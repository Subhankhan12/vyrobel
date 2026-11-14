{{-- Public guest privacy notice of ONE property's booking page (his ruling 2026-11-14, option B). It states ONLY what the booking form really
     collects and what the system really does. ⚠️ It deliberately names NO retention period (the law fixes them — OPEN F6) and makes no
     legal claim it cannot back. The property is the one responsible; Vyrobel processes for it. Every string is escaped. --}}
<x-public.shell :property="$property" :title="__('booking/privacy.heading')" :seo="$seo">
    <x-slot:head>
        <style>
            main { max-width: 760px; }
            .vy-pp-card { background: rgba(255, 252, 248, 0.6); backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); border-radius: 20px; box-shadow: var(--vy-shadow); padding: 26px 28px; }
            .vy-pp-h1 { margin: 0 0 10px; font-family: var(--vy-display); font-weight: 600; font-size: 30px; letter-spacing: -0.02em; }
            .vy-pp-h2 { margin: 22px 0 6px; font-size: 14px; font-weight: 600; color: var(--vy-ink); }
            .vy-pp-p, .vy-pp-ul { margin: 0 0 8px; font-size: 13.5px; line-height: 1.55; color: var(--vy-muted); }
            .vy-pp-ul { padding-left: 18px; display: flex; flex-direction: column; gap: 4px; }
            .vy-pp-links { margin-top: 24px; display: flex; flex-wrap: wrap; gap: 8px 22px; font-size: 13px; }
            .vy-pp-links a, .vy-pp-p a { color: var(--vy-accent-text); text-decoration: none; }
        </style>
    </x-slot:head>

    <div class="vy-pp-card">
        <h1 class="vy-pp-h1">{{ __('booking/privacy.heading') }}</h1>
        <p class="vy-pp-p">{{ __('booking/privacy.intro', ['property' => $property['name']]) }}</p>

        <h2 class="vy-pp-h2">{{ __('booking/privacy.who_title') }}</h2>
        <p class="vy-pp-p">{{ __('booking/privacy.who_body', ['property' => $property['name']]) }}</p>
        <p class="vy-pp-p">{{ $contact !== '' ? __('booking/privacy.who_contact', ['contact' => $contact]) : __('booking/privacy.who_no_contact') }}</p>

        <h2 class="vy-pp-h2">{{ __('booking/privacy.collect_title') }}</h2>
        <ul class="vy-pp-ul">
            <li>{{ __('booking/privacy.collect_name') }}</li>
            <li>{{ __('booking/privacy.collect_stay') }}</li>
            <li>{{ __('booking/privacy.collect_payment') }}</li>
        </ul>
        <p class="vy-pp-p">{{ __('booking/privacy.collect_more') }}</p>

        <h2 class="vy-pp-h2">{{ __('booking/privacy.why_title') }}</h2>
        <p class="vy-pp-p">{{ __('booking/privacy.why_body') }}</p>

        <h2 class="vy-pp-h2">{{ __('booking/privacy.who_sees_title') }}</h2>
        <p class="vy-pp-p">{{ __('booking/privacy.who_sees_body') }}</p>

        <h2 class="vy-pp-h2">{{ __('booking/privacy.keep_title') }}</h2>
        <p class="vy-pp-p">{{ __('booking/privacy.keep_body') }}</p>

        <h2 class="vy-pp-h2">{{ __('booking/privacy.rights_title') }}</h2>
        <p class="vy-pp-p">{{ __('booking/privacy.rights_body') }}</p>

        <h2 class="vy-pp-h2">{{ __('booking/privacy.more_title') }}</h2>
        <p class="vy-pp-p">{{ __('booking/privacy.more_body') }} <a href="{{ $platformPrivacyUrl }}">{{ __('booking/privacy.platform_link') }}</a></p>

        <div class="vy-pp-links">
            <a href="{{ $termsUrl }}">{{ __('booking/privacy.terms_link') }}</a>
            <a href="{{ route('booking.browse', $property['slug']) }}">{{ __('booking/privacy.back', ['property' => $property['name']]) }}</a>
        </div>
    </div>
</x-public.shell>
