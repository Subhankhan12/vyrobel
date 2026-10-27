{{-- I18N-01 — the language menu for people who are not signed in (the booking engine, the sign-in pages). The founder's ruling:
     English is the default; every FINISHED language is offered. Each choice is the SAME page with `?lang=<code>` — no
     `/{locale}/` route (risk E1) — and SetVisitorLocale remembers it (the `vy_lang` cookie). Renders nothing while English is
     the only finished language, so a page is unchanged until a second language ships. Signed-in people choose on their profile. --}}
@php
    $vyLangOptions = \App\Support\I18n\Locales::finishedOptions();
    $vyLangCurrent = app()->getLocale();
@endphp
@if (count($vyLangOptions) > 1 && ! auth()->check() && ! auth('guest')->check())
    <style>.vy-langmenu{margin-inline-start:auto}.vy-langmenu-select{font:inherit;font-size:13px;padding:6px 28px 6px 10px;border-radius:10px}</style>
    <nav class="vy-langmenu" aria-label="{{ __('components/language-menu.label') }}">
        <select class="vy-langmenu-select" aria-label="{{ __('components/language-menu.label') }}"
                onchange="if (this.value) { var u = new URL(window.location.href); u.searchParams.set('lang', this.value); window.location.href = u.toString(); }">
            @foreach ($vyLangOptions as $code => $name)
                <option value="{{ $code }}" lang="{{ \App\Support\I18n\Locales::tag($code) }}" @selected($code === $vyLangCurrent)>{{ $name }}</option>
            @endforeach
        </select>
    </nav>
@endif
